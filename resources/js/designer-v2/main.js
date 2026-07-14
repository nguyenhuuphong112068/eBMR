/**
 * eBMR Designer V2 — Pilot ProseMirror/TipTap
 * ============================================
 * Chạy SONG SONG với trình soạn thảo hiện tại (không đụng vào code cũ).
 *
 * Kiến trúc pilot:
 *  - Block model giữ nguyên (section / static-text / table với merge cell) —
 *    KHÔNG dùng prosemirror-tables; TipTap chỉ sống BÊN TRONG nội dung từng
 *    ô bảng và block văn bản (pattern "editor-on-demand": đúng 1 instance
 *    TipTap, click vào đâu mount vào đó, blur thì ghi HTML về items và unmount).
 *  - Biến số = custom atom node EbmrField (xem ebmr-field.js), round-trip
 *    tương thích 100% với badge HTML của trình soạn thảo cũ.
 *  - Lưu qua ĐÚNG endpoint incremental save hiện có (storeTemplate) với cùng
 *    payload shape — dữ liệu lưu từ V2 mở lại được bằng V1 và ngược lại.
 */
import { Editor } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import { TextStyleKit } from '@tiptap/extension-text-style';
import TextAlign from '@tiptap/extension-text-align';
import Highlight from '@tiptap/extension-highlight';
import Subscript from '@tiptap/extension-subscript';
import Superscript from '@tiptap/extension-superscript';
import { EbmrField, FIELD_TYPES, paintFieldElement, handleFieldBadgeClick } from './ebmr-field';
import { createSelectionController } from './selection';
import { initScaleReaderV2 } from './scale-reader';
import { initMmsBarcodeV2 } from './mms-barcode';
import { createNaMarksV2 } from './na-marks';
import { createEnvMonitorV2 } from './env-monitor';
import { createAttachmentsV2 } from './attachments';
import { MathEquation, V2Image, V2InlineImage, DocPropField, paintEquationBadge, paintDocPropBadge, refreshAllDocPropBadges } from './media-nodes';
import katex from 'katex';
import 'katex/dist/katex.min.css';

/** Bộ extension dùng chung cho mọi editor instance (StarterKit v3 đã gồm Underline + Link) */
function buildExtensions() {
    return [
        StarterKit.configure({
            heading: { levels: [1, 2, 3] },
            link: { openOnClick: false },
        }),
        // TextStyle + Color + FontSize + BackgroundColor + LineHeight — fontFamily tắt hẳn (luôn dùng font mặc định Arimo)
        TextStyleKit.configure({ fontFamily: false }),
        TextAlign.configure({ types: ['heading', 'paragraph'] }),
        Highlight.configure({ multicolor: true }),
        Subscript,
        Superscript,
        EbmrField,
        MathEquation,
        V2Image,
        V2InlineImage,
        DocPropField,
    ];
}

const BOOT = window.__V2__ || {};
let items = BOOT.items || [];
const fieldsConfig = BOOT.fieldsConfig || {};
BOOT.fieldsConfig = fieldsConfig; // để NodeView đọc được

// Nạp VIRTUAL BLOCKS (header/phê duyệt/mô tả SP/công thức pha chế...) — dùng chung
// generator với V1 (partial virtual_blocks_v2). Block ảo luôn locked, không lưu DB.
if (typeof window.__V2_buildVirtualBlocks === 'function') {
    const sysLabels = window.__V2_sysLabels || [];
    items = items.filter((i) => !sysLabels.includes(i.label)); // bỏ bản cũ còn lưu trong DB
    items.unshift(...window.__V2_buildVirtualBlocks());
}

let activeEditor = null;      // instance TipTap duy nhất (editor-on-demand)
let activeHost = null;        // element đang mount editor
let activeSync = null;        // hàm ghi HTML về items khi unmount
// Ghi nhớ Ô/đoạn văn bản SOẠN GẦN NHẤT để có thể mở lại và chèn (Symbol/Công thức/
// Hình ảnh/Document Property) ngay cả khi editor đã đóng — miễn là host còn trong DOM.
let lastEditorArgs = null;    // { host, getHTML, setHTML, context }
let lastEditorSel = null;     // { from, to } vị trí con trỏ lần cuối
let isDirtyDoc = false;       // có thay đổi chưa lưu
let activeBlockId = null;     // ID của block đang được chọn để hiển thị toolbar

// ── Lặp nhóm khối (giống V1): chọn 1 dải khối liên tiếp cùng section rồi cài số lần lặp ──
let pasteAnchorIdV2 = null;   // id item (block/section) vừa click — điểm chèn cho global paste (Ctrl+V khi chưa mở editor)
let lastClickInPagesV2 = false; // lần click gần nhất có nằm trong vùng thiết kế (#v2-pages) không — điều kiện cho gõ-phím-tạo-khối
let blockPickMode = false;    // đang ở CHẾ ĐỘ CHỌN KHỐI: click thẳng vào khối để chọn (không mở editor)
let blockPickAnchor = null;   // id khối neo cho Shift+Click mở rộng dải
let blockPickIds = [];        // ids khối đang được chọn để cài/sửa Lặp nhóm (dùng chung cho copy/cắt/dán cả cụm)
let blockClipboardV2 = null;  // { blocks: [...] } bản JSON thô của khối đã Copy/Cắt — field id đổi mới MỖI LẦN Dán
let internalPasteHandledV2 = false; // true trong khoảnh khắc vừa Ctrl+V dán khối nội bộ — chặn listener 'paste' (dán từ OS) xử lý trùng
let activeLoopTabIdx = {};    // groupId -> tab "Lần i" đang xem (Chạy thử)
const loopIterFieldMapCache = {}; // groupId -> { [i]: idMap } bản đồ field riêng cho từng lần lặp (Chạy thử)
let loopClonedFieldIds = new Set(); // id các field đã nhân bản riêng cho lần lặp — dọn khi thoát Chạy thử
let dynamicClonedFieldIds = new Set(); // id các field nhân bản cho DÒNG THÊM (Cấp 2) — dọn khi thoát Chạy thử

// Lắng nghe sự kiện click toàn cục để set activeBlockId (dùng capture phase để tránh bị stopPropagation chặn)
document.addEventListener('click', (e) => {
    if (e.target.closest('.v2-block-toolbar') || e.target.closest('.v2-field-panel')) return;

    lastClickInPagesV2 = !!(e.target.closest && e.target.closest('#v2-pages'));

    const block = e.target.closest('.v2-block');
    let oldActive = activeBlockId;

    if (block) {
        activeBlockId = block.getAttribute('data-id');
    } else {
        if (!e.target.closest('.modal') && !e.target.closest('.swal2-container')) {
            activeBlockId = null;
        }
    }

    if (oldActive !== activeBlockId) {
        document.querySelectorAll('.v2-block').forEach(el => {
            if (el.getAttribute('data-id') === activeBlockId) {
                el.classList.add('v2-block-active');
            } else {
                el.classList.remove('v2-block-active');
            }
        });
    }

    // Ghi nhận "điểm chèn" cho global paste (Ctrl+V khi chưa mở editor nào):
    // click vào block -> chèn ngay dưới block đó; click vào tiêu đề section -> chèn ngay
    // đầu section đó; click vào vùng trống của 1 trang -> chèn sau phần tử cuối trang đó.
    if (block) {
        pasteAnchorIdV2 = block.getAttribute('data-id');
    } else {
        const secEl = e.target.closest('.v2-section');
        if (secEl) {
            pasteAnchorIdV2 = secEl.id.replace(/^v2-sec-/, '');
        } else {
            const pageEl = e.target.closest('.v2-page');
            if (pageEl) {
                const anchors = pageEl.querySelectorAll('.v2-block[data-id], .v2-section[id^="v2-sec-"]');
                const last = anchors[anchors.length - 1];
                if (last) {
                    pasteAnchorIdV2 = last.classList.contains('v2-section')
                        ? last.id.replace(/^v2-sec-/, '')
                        : last.getAttribute('data-id');
                }
            }
        }
    }

    // Vẽ con trỏ nhấp nháy tại điểm chèn — chờ hết lượt click hiện tại vì editor
    // (nếu có) chỉ được mount ở bubble phase, sau capture listener này.
    // Click lên toolbar/modal không đụng tới caret (giữ điểm chèn khi chọn lệnh chèn).
    if (e.target.closest('#v2-toolbar') || e.target.closest('.modal') || e.target.closest('.swal2-container')) return;
    setTimeout(() => {
        if (!activeEditor && lastClickInPagesV2 && !selection.hasCells()
            && !BOOT.isReadOnly && !BOOT.isExecutionMode && !blockPickMode) {
            showInsertCaretV2();
        } else {
            hideInsertCaretV2();
        }
    }, 0);
}, true);

/** Con trỏ nhấp nháy đánh dấu ĐIỂM CHÈN khi click vào trang mà chưa mở editor nào
 *  (click tiêu đề section / vùng trống) — gõ chữ, Ctrl+V hoặc chèn từ toolbar sẽ vào đây. */
function showInsertCaretV2() {
    hideInsertCaretV2();
    if (pasteAnchorIdV2 == null) return;
    const anchorEl = document.querySelector(`.v2-block[data-id="${pasteAnchorIdV2}"]`)
        || document.getElementById('v2-sec-' + pasteAnchorIdV2);
    if (!anchorEl) return;
    const caret = document.createElement('div');
    caret.id = 'v2-insert-caret';
    caret.title = 'Điểm chèn: gõ chữ / dán (Ctrl+V) / chèn từ toolbar sẽ vào vị trí này';
    anchorEl.insertAdjacentElement('afterend', caret);
}

function hideInsertCaretV2() {
    document.getElementById('v2-insert-caret')?.remove();
}

let deletedBlockIds = [];     // db_id các khối đã xóa, gửi lên server khi lưu (xem saveTemplate)
let deletedFieldKeys = [];    // field_key các BIẾN SỐ đã xóa thật (deleteVariablesV2), gửi tường minh
                               // lên server để xoá — KHÔNG suy luận "biến nào vắng mặt trong lượt lưu
                               // này thì coi là mồ côi", vì cách suy luận cũ có thể xoá nhầm biến vừa
                               // được TAB/LƯỢT LƯU KHÁC tạo ra nếu ảnh chụp dữ liệu bị cũ hơn (race).

// Bộ điều khiển CHỌN đối tượng (ô/hàng/cột/bảng/biến số) — xem selection.js
const selection = createSelectionController({
    BOOT,
    getItems: () => items,
    hasActiveEditor: () => !!activeEditor,
    getActiveHost: () => activeHost,
    getActiveBlockId: () => activeBlockId,
    unmountEditor: () => unmountEditor(),
    saveDocState: () => saveDocState(),
    markDirty: () => markDirty(),
    renderDocument: () => renderDocument(),
    refreshToolbarState: () => refreshToolbarState(),
    openBatchFieldPanel: (ids) => openBatchFieldPanelV2(ids),
    duplicateFieldsInHtml: (html) => duplicateFieldsInHtmlV2(html),
    buildFieldDuplicateMap: (htmlList) => buildFieldDuplicateMapV2(htmlList),
    applyFieldDuplicateMap: (idMap, nameMap) => applyFieldDuplicateMapV2(idMap, nameMap),
    rewriteFieldIdsInHtml: (html, idMap) => rewriteFieldIdsInHtmlV2(html, idMap),
    copyFields: (ids) => copyFieldsV2(ids),
    cutFields: (ids) => cutFieldsV2(ids),
    pasteFieldsIntoCell: (item, r, c) => pasteFieldsIntoCellV2(item, r, c),
    pasteFieldsAtCursor: () => pasteFieldsV2(),
    hasFieldClipboard: () => hasFieldClipboardV2(),
    deleteBlock: (id) => deleteBlock(id),
    convertStaticSelectionToEditable: (action) => convertStaticSelectionToEditableV2(action),
});

// Gạch chéo "KHÔNG SỬ DỤNG" (N/A) lúc Chạy thử/Thực thi — xem na-marks.js
const naMarks = createNaMarksV2({
    BOOT,
    renderDocument: () => renderDocument(),
    unmountEditor: () => unmountEditor(),
});

// Tài liệu PDF đính kèm theo phân đoạn lúc Chạy thử/Thực thi — xem attachments.js
const sectionAttachments = createAttachmentsV2({ BOOT });

/* =========================================================
 * 0. UNDO/REDO CẤP TÀI LIỆU (thao tác block: thêm/dán/resize...)
 * Trong vùng đang gõ thì TipTap tự có history riêng; stack này
 * phục vụ các thao tác NGOÀI editor (giống undoStack của V1).
 * ========================================================= */
const docUndoStack = [];
const docRedoStack = [];
const DOC_MAX_HISTORY = 50;

// Viền mặc định của ô bảng — render inline cho cạnh chưa tuỳ biến để giữ nguyên
// giao diện cũ, đồng thời để cạnh 'hidden' (đã xoá) thắng khi border-collapse.
const TABLE_DEFAULT_BORDER = '1px solid #64748b';

function docSnapshot() {
    return JSON.stringify({ items, fieldsConfig, deletedBlockIds, deletedFieldKeys });
}

/** Gọi TRƯỚC mỗi thao tác cấp tài liệu để lưu trạng thái hoàn tác */
function saveDocState() {
    const snap = docSnapshot();
    if (docUndoStack.length && docUndoStack[docUndoStack.length - 1] === snap) return;
    docUndoStack.push(snap);
    if (docUndoStack.length > DOC_MAX_HISTORY) docUndoStack.shift();
    docRedoStack.length = 0;
}

function restoreDocState(snap) {
    const data = JSON.parse(snap);
    items = data.items;
    deletedBlockIds = data.deletedBlockIds || [];
    deletedFieldKeys = data.deletedFieldKeys || [];
    // fieldsConfig phải mutate tại chỗ (NodeView giữ tham chiếu qua BOOT.fieldsConfig)
    Object.keys(fieldsConfig).forEach((k) => delete fieldsConfig[k]);
    Object.assign(fieldsConfig, data.fieldsConfig);
    renderDocument();
    markDirty();
}

function undoDoc() {
    if (BOOT.isReadOnly || BOOT.isExecutionMode) return; // Chạy thử: không cho sửa cấu trúc
    if (docUndoStack.length === 0) return;
    unmountEditor();
    docRedoStack.push(docSnapshot());
    restoreDocState(docUndoStack.pop());
}

function redoDoc() {
    if (BOOT.isReadOnly || BOOT.isExecutionMode) return; // Chạy thử: không cho sửa cấu trúc
    if (docRedoStack.length === 0) return;
    unmountEditor();
    docUndoStack.push(docSnapshot());
    restoreDocState(docRedoStack.pop());
}

/* =========================================================
 * 1. RENDER DOCUMENT FLOW (HTML tĩnh, nhẹ — không editor)
 * ========================================================= */
function esc(s) {
    return String(s ?? '').replace(/[&<>"']/g, (c) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
    }[c]));
}

/** Thay badge cũ bằng badge hiển thị đẹp (chỉ để XEM — khi edit sẽ do TipTap NodeView vẽ) */
function decorateBadges(html, fieldsOverride) {
    if (!html) return '';
    const tmp = document.createElement('div');
    tmp.innerHTML = html;
    tmp.querySelectorAll('span.ebmr-field-badge').forEach((el) => {
        const fid = el.getAttribute('data-field-id');
        const cfg = (fieldsOverride && fieldsOverride[fid]) || fieldsConfig[fid] || {};
        const t = FIELD_TYPES[cfg.type] || FIELD_TYPES.text;
        // HTML lưu từ V1 mang theo onclick="selectField(...)" — hàm không tồn tại
        // trong V2, phải gỡ bỏ kẻo click là ReferenceError.
        el.removeAttribute('onclick');
        el.className = 'v2-field-badge v2-field-badge--static';
        el.innerHTML = `<i class="fas ${t.icon} me-1" style="font-size:0.7em;"></i><span>${esc(cfg.label || cfg.name || fid)}</span>`;
    });
    tmp.querySelectorAll('span.v2-equation-badge').forEach((el) => paintEquationBadge(el));
    tmp.querySelectorAll('span.v2-docprop-badge').forEach((el) => paintDocPropBadge(el));
    replaceSttMarkersV2(tmp);
    return tmp.innerHTML;
}

/** Thay CHỮ "#STT#" (đánh dấu cột số thứ tự tự động — xem context menu "Đánh số thứ tự
 *  tự động cho cột này") bằng 1 span đếm bằng CSS counter (giống V1: .v2-table đặt
 *  counter-reset, mỗi span .v2-css-stt tự +1 theo ĐÚNG thứ tự xuất hiện trong DOM) —
 *  nhờ vậy thêm/xoá hàng (kể cả "Thêm dòng (Cấp 2)") không cần JS tính lại số, trình
 *  duyệt tự đếm lại theo số span đang có. Chỉ thay ở bản HIỂN THỊ tĩnh — nội dung lưu
 *  vẫn giữ nguyên chữ "#STT#" (round-trip an toàn, sửa lại được bất cứ lúc nào). */
function replaceSttMarkersV2(root) {
    if (!root.innerHTML.includes('#STT#')) return;
    const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT);
    const textNodes = [];
    let n;
    while ((n = walker.nextNode())) { if (n.nodeValue.includes('#STT#')) textNodes.push(n); }
    textNodes.forEach((node) => {
        const parts = node.nodeValue.split('#STT#');
        const frag = document.createDocumentFragment();
        parts.forEach((part, i) => {
            if (part) frag.appendChild(document.createTextNode(part));
            if (i < parts.length - 1) {
                const span = document.createElement('span');
                span.className = 'v2-css-stt';
                span.setAttribute('contenteditable', 'false');
                frag.appendChild(span);
            }
        });
        node.parentNode.replaceChild(frag, node);
    });
}

/* ----------------------------------------------------------
 * Kích hoạt badge TĨNH cho Chạy thử: khi ở execution mode, KHÔNG
 * block nào mount TipTap nên NodeView không chạy — phải tự vẽ UI
 * chạy thử (checkbox/select/chữ ký/công thức...) lên các badge tĩnh
 * và gắn click dispatch, dùng chung painter với NodeView.
 * ---------------------------------------------------------- */
let staticPaintRegs = []; // [{fieldId, fn}] — gỡ đăng ký trước mỗi lần render lại
function activateStaticBadges() {
    staticPaintRegs.forEach(({ fieldId, fn }) => window.__V2__.unregisterFieldPaint(fieldId, fn));
    staticPaintRegs = [];
    if (!BOOT.isExecutionMode) return;
    activateStaticBadgesIn(document.getElementById('v2-pages'));
}

/** Kích hoạt badge tĩnh TRONG 1 vùng DOM — tách riêng để gọi thêm cho nội dung GF liên kết
 *  (fetch bất đồng bộ, chỉ xuất hiện SAU khi activateStaticBadges của renderDocument chạy xong). */
function activateStaticBadgesIn(root) {
    if (!root || !BOOT.isExecutionMode) return;
    root.querySelectorAll('.v2-field-badge--static').forEach((el) => {
        if (el.dataset.v2BadgeActive) return; // đã kích hoạt (tránh gắn listener trùng)
        const fid = el.getAttribute('data-field-id');
        if (!fid || !fieldsConfig[fid]) return;
        el.dataset.v2BadgeActive = '1';
        const paint = () => paintFieldElement(el, fid);
        paint();
        window.__V2__.registerFieldPaint(fid, paint);
        staticPaintRegs.push({ fieldId: fid, fn: paint });
        el.addEventListener('click', (e) => handleFieldBadgeClick(e, fid, paint));
    });
}

/** Thanh chèn khối mới (hiện khi rê chuột) — đặt sau mỗi block */
function makeInserter(afterIndex) {
    const ins = document.createElement('div');
    ins.className = 'v2-inserter';
    ins.innerHTML = `
        <div class="v2-inserter-line"></div>
        <div class="v2-inserter-btns">
            <button type="button" data-add="static-text" data-after="${afterIndex}" title="Thêm khối văn bản">
                <i class="fas fa-font me-1"></i> Văn bản
            </button>
            <button type="button" data-add="table" data-after="${afterIndex}" title="Thêm bảng">
                <i class="fas fa-table me-1"></i> Bảng
            </button>
        </div>`;

    // Drop-target cho kéo thả từ sidebar Thiết bị / Thành phần CO
    ins.addEventListener('dragover', (e) => {
        if (!document.body.classList.contains('v2-dragging')) return;
        e.preventDefault();
        ins.classList.add('v2-drop-active');
    });
    ins.addEventListener('dragleave', () => ins.classList.remove('v2-drop-active'));
    ins.addEventListener('drop', (e) => {
        e.preventDefault();
        ins.classList.remove('v2-drop-active');
        document.body.classList.remove('v2-dragging');

        const action = e.dataTransfer.getData('action');
        if (action === 'insertEquipmentTable') {
            const d = e.dataTransfer.getData('equipmentData');
            if (d) insertEquipmentTableV2(afterIndex, JSON.parse(d));
            return;
        }
        if (action === 'insertLinkedGf') {
            insertLinkedGfV2(afterIndex, e.dataTransfer.getData('gfDocCode'), e.dataTransfer.getData('gfName'));
            return;
        }
        const compId = e.dataTransfer.getData('componentId');
        if (compId) {
            importComponentV2(parseInt(compId, 10), e.dataTransfer.getData('componentName') || '', afterIndex);
        }
    });
    return ins;
}

/** Chỉ dựng phần NỘI DUNG của 1 block (không toolbar). Dùng chung cho luồng phẳng,
 *  wrapper Lặp nhóm (thiết kế) và từng tab "Lần i" (Chạy thử — khi đó contentOverride/
 *  dataOverride đã được thay data-field-id sang bản sao riêng của lần lặp đó). */
function renderBlockContentV2(item, contentOverride, dataOverride, fieldsOverride) {
    const isLocked = item.locked || item.isVirtual;
    if (item.type === 'static-text') {
        const el = document.createElement('div');
        el.className = 'v2-block v2-static-text' + (isLocked ? ' v2-locked' : '');
        el.setAttribute('data-id', item.id);
        const inner = document.createElement('div');
        inner.className = 'v2-editable';
        const content = contentOverride !== undefined ? contentOverride : (item.content || '<p></p>');
        inner.innerHTML = decorateBadges(content, fieldsOverride);
        el.appendChild(inner);
        if (!BOOT.isReadOnly && !BOOT.isExecutionMode && !isLocked && contentOverride === undefined) {
            inner.addEventListener('click', (e) => {
                if (activeHost === inner) return;
                if (hasNativeTextSelectionV2()) return; // vừa quét chọn chữ để copy — giữ selection, không mở editor
                e.stopPropagation();
                mountEditor(inner,
                    () => item.content || '',
                    (html) => { item.content = html; item.dirty = true; markDirty(); },
                    { kind: 'text', item }, { x: e.clientX, y: e.clientY });
            });
        }
        return el;
    }
    if (item.type === 'table') {
        const el = renderTable(dataOverride ? { ...item, data: dataOverride } : item, fieldsOverride);
        if (isLocked) el.classList.add('v2-locked');
        if (item.isAbbreviationTable) {
            const title = document.createElement('div');
            title.className = 'ebmr-section-header d-flex align-items-center mb-3 mt-2';
            title.innerHTML = `<div class="section-icon bg-info text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width:40px;height:40px;min-width:40px;"><i class="fas fa-list-ul"></i></div>
                <div class="flex-grow-1">
                    <div class="section-title fw-bold text-uppercase" style="font-size:1.1rem;color:#164e63;letter-spacing:1px;">Danh Sách Viết Tắt</div>
                    <div class="section-line mt-1" style="height:3px;background:linear-gradient(to right,#0ea5e9,transparent);border-radius:2px;"></div>
                </div>`;
            el.prepend(title);
        }
        return el;
    }
    if (item.type === 'chart' && item.chartConfig) {
        const el = document.createElement('div');
        el.className = 'v2-block v2-chart' + (isLocked ? ' v2-locked' : '');
        el.setAttribute('data-id', item.id);
        const canvasId = 'v2_chart_canvas_' + item.id;
        el.innerHTML = `<div class="v2-chart-container"><canvas id="${canvasId}"></canvas></div>`;
        // Canvas phải nằm trong DOM rồi mới vẽ được — đợi hết lượt render hiện tại
        setTimeout(() => renderChartV2(canvasId, item), 50);
        return el;
    }
    if (item.type === 'linked-template') {
        // Chạy thử/thực thi: GF liên kết LUÔN bung toàn bộ nội dung để điền dữ liệu và
        // KHÔNG có nút Thu gọn/Xem trước — tránh người dùng thu gọn rồi bỏ sót field.
        // Thiết kế: đóng mặc định, bấm "Xem trước" mới mở.
        const expanded = BOOT.isExecutionMode || !!item.showPreview;
        const el = document.createElement('div');
        el.className = 'v2-block v2-linked-gf' + (isLocked ? ' v2-locked' : '');
        el.setAttribute('data-id', item.id);
        el.innerHTML = `
            <div class="d-flex align-items-center justify-content-between py-3 px-3 border border-primary border-dashed rounded bg-light">
                <div class="text-navy">
                    <i class="fas fa-link me-2 text-primary"></i><strong>Biểu mẫu chung: ${esc(item.label || item.ref_doc_code || '')}</strong>
                    ${item.ref_doc_code ? `<span class="badge bg-primary ms-2">${esc(item.ref_doc_code)}</span>` : ''}
                </div>
                ${BOOT.isExecutionMode ? '' : `<button type="button" class="btn btn-sm btn-outline-primary" data-act="toggle-gf-preview">
                    <i class="fas fa-eye me-1"></i> ${expanded ? 'Thu gọn' : 'Xem trước'}
                </button>`}
            </div>
            <div class="v2-gf-preview mt-2" id="v2-gf-preview-${item.id}" style="display:${expanded ? 'block' : 'none'};"></div>`;
        el.querySelector('[data-act="toggle-gf-preview"]')?.addEventListener('click', (e) => {
            e.stopPropagation();
            toggleGfPreviewV2(item.id, item.ref_doc_code);
        });
        // el CHƯA nằm trong DOM lúc này (renderDocument gắn sau) — getElementById trong
        // fetchAndRenderGfPreviewV2 sẽ ra null nếu gọi ngay. Đợi hết lượt render (như chart).
        if (expanded) setTimeout(() => fetchAndRenderGfPreviewV2(item.id, item.ref_doc_code), 0);
        return el;
    }
    // Loại block chưa hỗ trợ trong pilot — hiển thị placeholder
    const el = document.createElement('div');
    el.className = 'v2-block v2-unsupported';
    el.innerHTML = `<i class="fas fa-cube me-2"></i>[${esc(item.type)}] ${esc(item.label || '')} — <em>chưa hỗ trợ trong bản thử nghiệm, giữ nguyên khi lưu</em>`;
    return el;
}

/** Dựng 1 block hoàn chỉnh (nội dung + toolbar dọc: chọn khối lặp / di chuyển / xóa / bình luận). */
function buildBlockElV2(item, idx) {
    const isLocked = item.locked || item.isVirtual;
    const el = renderBlockContentV2(item);
    if (!el) return null;
    if (item.id && item.id === activeBlockId) el.classList.add('v2-block-active');
    if (blockPickIds.includes(item.id)) el.classList.add('v2-block-picked');

    if (item.id) {
        const toolbar = document.createElement('div');
        toolbar.className = 'v2-block-toolbar';

        if (!isLocked && !BOOT.isReadOnly && !BOOT.isExecutionMode) {
            const prev = items[idx - 1];
            const next = items[idx + 1];
            const canMoveUp = !!prev && prev.type !== 'section' && !prev.locked && !prev.isVirtual && prev.section_id === item.section_id;
            const canMoveDown = !!next && next.type !== 'section' && !next.locked && !next.isVirtual && next.section_id === item.section_id;
            const isPicked = blockPickIds.includes(item.id);
            const isWeightChart = item.type === 'chart' && !!item.chartConfig?.tableSourceId;

            toolbar.innerHTML = `
                <button type="button" class="v2-block-action-btn${isPicked ? ' active' : ''}" data-act="pick" title="Chọn khối (giữ Shift để chọn dải nhiều khối liên tiếp) — Ctrl+C/X/V để copy/cắt/dán cả cụm, hoặc dùng để cài Lặp nhóm"><i class="fas fa-clone"></i></button>
                ${isWeightChart ? '<button type="button" class="v2-block-action-btn" data-act="edit-weight" title="Sửa thông số Bảng KT Khối lượng Trung bình"><i class="fas fa-pen"></i></button>' : ''}
                <button type="button" class="v2-block-action-btn" data-act="up" title="Di chuyển lên"${canMoveUp ? '' : ' disabled'}><i class="fas fa-arrow-up"></i></button>
                <button type="button" class="v2-block-action-btn" data-act="down" title="Di chuyển xuống"${canMoveDown ? '' : ' disabled'}><i class="fas fa-arrow-down"></i></button>
                <button type="button" class="v2-block-action-btn v2-block-action-danger" data-act="del" title="Xóa khối"><i class="fas fa-trash-alt"></i></button>`;
            toolbar.querySelectorAll('button').forEach((b) => b.addEventListener('mousedown', (e) => e.preventDefault()));
            toolbar.querySelector('[data-act="pick"]').addEventListener('click', (e) => { e.stopPropagation(); pickBlockV2(item, e.shiftKey); });
            if (isWeightChart) {
                toolbar.querySelector('[data-act="edit-weight"]').addEventListener('click', (e) => { e.stopPropagation(); openWeightChartEditModalV2(item); });
            }
            toolbar.querySelector('[data-act="up"]').addEventListener('click', (e) => { e.stopPropagation(); moveBlock(item.id, -1); });
            toolbar.querySelector('[data-act="down"]').addEventListener('click', (e) => { e.stopPropagation(); moveBlock(item.id, 1); });
            toolbar.querySelector('[data-act="del"]').addEventListener('click', (e) => { e.stopPropagation(); deleteBlock(item.id); });
        }

        el.appendChild(toolbar);
    }
    return el;
}

/** Bản sao field riêng cho lần lặp thứ i (i=1 dùng field GỐC, không nhân bản).
 *  Cache theo groupId để giữ ổn định ID qua các lần renderDocument() (không mất giá trị đã nhập).
 *  ID nhân bản là XÁC ĐỊNH (`${oldId}__it${i}`) — nhờ vậy ở trang Thực thi thật, giá trị của
 *  "Lần i" lưu trong ebmr_run_data theo id này sẽ tự khớp lại sau khi tải lại trang, không cần
 *  lưu bản đồ id theo lô. */
function getLoopIterFieldMapV2(groupId, i, entries) {
    if (!loopIterFieldMapCache[groupId]) loopIterFieldMapCache[groupId] = {};
    if (loopIterFieldMapCache[groupId][i]) return loopIterFieldMapCache[groupId][i];
    const htmlList = [];
    entries.forEach(({ item }) => {
        if (item.type === 'static-text') htmlList.push(item.content || '');
        else if (item.type === 'table') {
            (item.data || []).forEach((row) => (row || []).forEach((cell) => {
                if (cell && typeof cell === 'object' && cell.content) htmlList.push(cell.content);
            }));
        }
    });
    const { idMap, nameMap } = buildFieldDuplicateMapV2(htmlList, (oldId) => `${oldId}__it${i}`);
    if (Object.keys(idMap).length) {
        applyFieldDuplicateMapV2(idMap, nameMap);
        Object.values(idMap).forEach((newId) => loopClonedFieldIds.add(newId));
    }
    loopIterFieldMapCache[groupId][i] = idMap;
    return idMap;
}

/** Tên tab cho lần lặp thứ i: ưu tiên tên riêng (loopLabels[i-1]) nếu có,
 *  nếu không thì dùng "<loopLabel> <i>" (VD: "Lần 1", "Mẻ 2"...). */
function getLoopIterNameV2(run, i) {
    const custom = (run.loopLabels && run.loopLabels[i - 1] || '').trim();
    if (custom) return custom;
    return (run.loopLabel || 'Lần') + ' ' + i;
}

/** Thiết kế: bọc các khối cùng 1 nhóm lặp trong khung nét đứt + badge "Lặp nhóm: N lần". */
function renderLoopGroupDesignV2(run, page) {
    const wrap = document.createElement('div');
    wrap.className = 'v2-loop-group-wrap';

    const badge = document.createElement('div');
    badge.className = 'v2-loop-group-badge';
    const hasCustomNames = (run.loopLabels || []).some((s) => (s || '').trim());
    const lbl = hasCustomNames
        ? ` (${Array.from({ length: run.loopCount }, (_, k) => esc(getLoopIterNameV2(run, k + 1))).join(', ')})`
        : (run.loopLabel && run.loopLabel !== 'Lần' ? ` (${esc(run.loopLabel)} 1..${run.loopCount})` : '');
    badge.innerHTML = `<i class="fas fa-redo me-1"></i> Lặp nhóm: ${run.loopCount} lần${lbl}`;
    if (!BOOT.isReadOnly) {
        badge.title = 'Nhấp để chỉnh sửa cài đặt lặp nhóm';
        badge.addEventListener('click', (e) => { e.stopPropagation(); editLoopGroupV2(run.groupId); });
    }
    wrap.appendChild(badge);

    run.entries.forEach(({ item, idx }) => {
        const el = buildBlockElV2(item, idx);
        if (el) { el.classList.add('v2-loop-group-member'); wrap.appendChild(el); }
        const isLastVirtual = item.isVirtual && (!items[idx + 1] || !items[idx + 1].isVirtual);
        if (!BOOT.isReadOnly && !BOOT.isExecutionMode && (!item.isVirtual || isLastVirtual)) {
            wrap.appendChild(makeInserter(idx));
        }
    });

    page.appendChild(wrap);
}

/** Chạy thử: hiển thị nhóm lặp dưới dạng tab "Lần 1..N", mỗi tab có giá trị biến ĐỘC LẬP
 *  (field của lần i>1 được nhân bản riêng qua getLoopIterFieldMapV2). */
function renderLoopGroupExecutionV2(run, page) {
    const { groupId, loopCount, entries } = run;
    let activeIdx = activeLoopTabIdx[groupId] || 1;
    if (activeIdx > loopCount) activeIdx = 1;
    activeLoopTabIdx[groupId] = activeIdx;

    const header = document.createElement('div');
    header.className = 'v2-loop-tabs-header';
    for (let i = 1; i <= loopCount; i++) {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn btn-sm ' + (i === activeIdx ? 'btn-primary' : 'btn-outline-primary');
        btn.textContent = getLoopIterNameV2(run, i);
        btn.addEventListener('click', () => { activeLoopTabIdx[groupId] = i; renderDocument(); });
        header.appendChild(btn);
    }
    page.appendChild(header);

    for (let i = 1; i <= loopCount; i++) {
        const tab = document.createElement('div');
        tab.className = 'v2-loop-tab-content';
        tab.style.display = i === activeIdx ? '' : 'none';
        const idMap = i === 1 ? null : getLoopIterFieldMapV2(groupId, i, entries);

        entries.forEach(({ item }) => {
            const content = (idMap && item.type === 'static-text')
                ? rewriteFieldIdsInHtmlV2(item.content || '<p></p>', idMap) : undefined;
            const dataOverride = (idMap && item.type === 'table')
                ? (item.data || []).map((row) => (row || []).map((cell) => (cell && typeof cell === 'object'
                    ? { ...cell, content: rewriteFieldIdsInHtmlV2(cell.content || '', idMap) }
                    : cell)))
                : undefined;
            const el = renderBlockContentV2(item, content, dataOverride);
            if (el) tab.appendChild(el);
        });
        page.appendChild(tab);
    }
}

function renderDocument() {
    const container = document.getElementById('v2-pages');
    if (!container) return;
    container.innerHTML = '';

    // Mỗi SECTION tự động bắt đầu 1 TRANG MỚI (ngắt trang như yêu cầu).
    let page = null;
    const newPage = () => {
        page = document.createElement('div');
        page.className = 'v2-page shadow';
        page.__allVirtual = true; // true nếu tới giờ trang này chỉ chứa block/section ẢO (hệ thống)
        container.appendChild(page);
    };

    // Dựng cụm con dấu (chọn lúc ban hành, nằm ngang, cạnh nhau) — null nếu không có dấu.
    // Đóng lên: section THƯỜNG, section ảo CÔNG THỨC PHA CHẾ, và bảng HEADER hệ thống.
    const buildSealStampsV2 = () => {
        if (!BOOT.isExecutionMode || !Array.isArray(BOOT.recordSeals) || !BOOT.recordSeals.length) return null;
        const wrap = document.createElement('div');
        wrap.className = 'v2-seal-stamps';
        BOOT.recordSeals.forEach((s) => {
            if (!s || !s.content) return;
            const seal = document.createElement('div');
            seal.className = 'v2-seal-stamp' + ((s.border_style || 'double') === 'double' ? ' seal-border-double' : '');
            seal.style.color = s.color || '#dc3545';
            // Kích thước dấu (%): nhân vào cỡ chuẩn 0.85rem của .v2-seal-stamp
            seal.style.fontSize = (0.85 * ((parseInt(s.size, 10) || 100) / 100)) + 'rem';
            seal.title = s.name || '';
            const addLine = (cls, text) => {
                const line = document.createElement('div');
                line.className = cls;
                line.textContent = text;
                seal.appendChild(line);
            };
            if (s.header) addLine('seal-line-header', s.header);
            addLine('seal-line-content', s.content);
            if (s.footer) addLine('seal-line-footer', s.footer);
            wrap.appendChild(seal);
        });
        return wrap.childElementCount ? wrap : null;
    };

    // Tem "SỐ LÔ" — đóng lên góc trên bên TRÁI mỗi phân đoạn (màu đỏ). Hiện ở MỌI
    // hồ sơ gắn với 1 lô (ebmr_record): nhận ban hành, đang thực thi, đã hoàn thành —
    // tức bất cứ khi nào mở qua execute() (isExecutionMode). null nếu lô chưa có số.
    const buildBatchStampV2 = () => {
        if (!BOOT.isExecutionMode || !BOOT.batchNumber) return null;
        const el = document.createElement('div');
        el.className = 'v2-batch-stamp';
        el.title = 'Số lô';
        el.innerHTML = `<span class="v2-batch-label">Số lô:</span> <span>${esc(String(BOOT.batchNumber))}</span>`;
        return el;
    };

    let renderedAny = false;

    // Gom các block liên tiếp (không phải section) có cùng loop_group_id thành 1 "run"
    // (giống V1 groupedBlocks) — cho phép bọc khung Lặp nhóm / vẽ tab "Lần i" khi Chạy thử.
    const runs = [];
    let curRun = null;
    items.forEach((item, idx) => {
        if (item.type === 'document-settings') return;
        if (item.type === 'section') { curRun = null; runs.push({ kind: 'section', item, idx }); return; }
        const gid = item.loop_group_id || null;
        if (gid && curRun && curRun.kind === 'loop' && curRun.groupId === gid) {
            curRun.entries.push({ item, idx });
        } else {
            curRun = gid
                ? {
                    kind: 'loop', groupId: gid,
                    loopCount: Math.max(1, parseInt(item.loop_count, 10) || 1),
                    loopLabel: (item.loop_label || '').trim() || 'Lần', // tên gọi mỗi lần lặp (VD: Mẻ, Lô)
                    loopLabels: Array.isArray(item.loop_labels) ? item.loop_labels : [], // tên riêng cho từng lần (VD: Đầu lô, Giữa lô, Cuối lô)
                    entries: [{ item, idx }],
                }
                : { kind: 'plain', entries: [{ item, idx }] };
            runs.push(curRun);
        }
    });

    runs.forEach((run) => {
        if (run.kind === 'section') {
            const { item } = run;
            const isLocked = item.locked || item.isVirtual;
            // Ngắt trang tại mỗi section, TRỪ KHI "nối lên trang trước":
            //  - section tự đánh dấu noPageBreak (VD: PHÊ DUYỆT nối sau HEADER, hoặc section rỗng
            //    tự nối lên), HOẶC
            //  - đây là NHÁNH PHÒNG (track>=2) mà section GỐC (track 1) của nhóm đã bật noPageBreak
            //    — "nối trang" ở TIÊU ĐỀ CHÍNH kéo mọi nhánh con lên CÙNG trang với tiêu đề chính.
            // Riêng section gốc CÓ nhánh: noPageBreak dành cho việc kéo nhánh lên, nên BẢN THÂN gốc
            // vẫn mở trang mới (đứng đầu trang của mình) — khớp kết quả người dùng mong muốn.
            const _ti = sectionTrackInfoV2(item);
            let joinUp;
            if (_ti.track >= 2) {
                const root = sectionTrackRootV2(item);
                joinUp = item.noPageBreak || !!(root && root.noPageBreak);
            } else {
                joinUp = sectionHasBranchesV2(item) ? false : item.noPageBreak;
            }
            // section CHÍNH của trang = section KHÔNG nối lên trang trước (tự mở trang mới,
            // hoặc là section đầu tiên). Dùng để quyết định chỉ đóng SỐ LÔ đúng 1 lần/trang.
            const startsNewPageV2 = !page || !joinUp;
            if (startsNewPageV2) newPage();
            const el = document.createElement('div');
            el.className = 'v2-section' + (item.hideTitle ? ' v2-section-notitle' : '');
            el.id = 'v2-sec-' + item.id;
            // hideTitle: chỉ giữ lại làm MỐC ĐIỀU HƯỚNG (TOC/click), không hiện tiêu đề trên tài liệu.
            if (!item.hideTitle) {
                // Markup đồng bộ style section của V1: icon tròn + tiêu đề + gạch gradient
                el.innerHTML = `
                    <div class="v2-section-icon"><i class="fas fa-layer-group"></i></div>
                    <div class="v2-section-body">
                        <div class="v2-section-title">${esc(item.label || 'Tên phân đoạn')}${isLocked ? ' <i class="fas fa-lock ms-1" style="font-size:0.65em;opacity:0.55;" title="Section hệ thống (khóa)"></i>' : ''}</div>
                        <div class="v2-section-line"></div>
                    </div>`;

                if (!isLocked && !BOOT.isReadOnly && !BOOT.isExecutionMode) {
                    const toolbar = document.createElement('div');
                    toolbar.className = 'v2-section-toolbar';
                    const { track } = sectionTrackInfoV2(item);
                    // TIÊU ĐỀ CHÍNH (gốc) có nhánh phòng: nút "nối trang" kéo các nhánh con lên cùng
                    // trang. Section thường/nhánh: nút nối chính section này lên trang trước.
                    const isBranchRoot = track === 1 && sectionHasBranchesV2(item);
                    const pbTitle = isBranchRoot
                        ? (item.noPageBreak
                            ? 'Đang nối các nhánh phòng lên cùng trang với tiêu đề này — bấm để tách mỗi nhánh sang trang riêng'
                            : 'Mỗi nhánh phòng đang ở trang riêng — bấm để nối tất cả nhánh lên cùng trang với tiêu đề này')
                        : (item.noPageBreak
                            ? 'Đang nối liền trang trước — bấm để tách sang trang riêng'
                            : 'Đang tự sang trang riêng — bấm để nối liền ngay sau trang trước (section không có nội dung)');
                    toolbar.innerHTML = `
                        <button type="button" class="v2-block-action-btn" data-act="rename" title="Đổi tên phân đoạn"><i class="fas fa-pen"></i></button>
                        ${isRoomTrackEligibleV2(item) ? '<button type="button" class="v2-block-action-btn" data-act="split" title="Tách nhánh phòng (công đoạn này chạy song song ở phòng khác)"><i class="fas fa-code-branch"></i></button>' : ''}
                        <button type="button" class="v2-block-action-btn" data-act="toggle-pagebreak" title="${pbTitle}"><i class="fas fa-${item.noPageBreak ? 'link' : 'file'}"></i></button>
                        ${track >= 2 ? '<button type="button" class="v2-block-action-btn v2-block-action-danger" data-act="del-track" title="Xóa nhánh này"><i class="fas fa-trash-alt"></i></button>' : ''}`;
                    toolbar.querySelectorAll('button').forEach((b) => b.addEventListener('mousedown', (e) => e.preventDefault()));
                    toolbar.querySelector('[data-act="rename"]').addEventListener('click', (e) => { e.stopPropagation(); renameSectionV2(item); });
                    toolbar.querySelector('[data-act="split"]')?.addEventListener('click', (e) => { e.stopPropagation(); splitSectionIntoRoomTrackV2(item); });
                    toolbar.querySelector('[data-act="toggle-pagebreak"]').addEventListener('click', (e) => { e.stopPropagation(); toggleSectionPageBreakV2(item); });
                    toolbar.querySelector('[data-act="del-track"]')?.addEventListener('click', (e) => { e.stopPropagation(); deleteBlock(item.id); });
                    el.appendChild(toolbar);
                }

                // Con dấu: section THƯỜNG luôn đóng; section ẢO (hệ thống) chỉ đóng ở
                // CÔNG THỨC PHA CHẾ (PHÊ DUYỆT / LỊCH SỬ ẤN BẢN / THÔNG TIN SẢN PHẨM không
                // đóng). Bảng HEADER đóng riêng trên chính bảng — xem nhánh run 'plain'.
                let sealStampsV2 = null;
                if (!item.isVirtual || item.id === 'sys_bmr_sec_recipe') {
                    sealStampsV2 = buildSealStampsV2();
                    if (sealStampsV2) el.appendChild(sealStampsV2);
                }

                // Số lô: chỉ đóng ở block THỰC SỰ CÓ MỘC (đồng bộ điều kiện con dấu ở trên),
                // VÀ chỉ ở section CHÍNH của trang — nếu 2 section đã nối chung 1 trang thì
                // không lặp lại số lô ở section nối theo sau (tránh chồng chữ lên tiêu đề).
                if (startsNewPageV2 && sealStampsV2) {
                    const batch = buildBatchStampV2();
                    if (batch) el.appendChild(batch);
                }
            }
            page.appendChild(el);
            if (!item.isVirtual) page.__allVirtual = false;
            renderedAny = true;
            return;
        }

        if (!page) newPage(); // block đứng trước section đầu tiên vẫn có trang

        if (run.kind === 'plain') {
            run.entries.forEach(({ item, idx }) => {
                const el = buildBlockElV2(item, idx);
                if (!el) return;
                // Bảng HEADER hệ thống (sys_bmr_tbl_header / sys_gf_tbl_header): số lô + con dấu
                // giờ nằm ngay TRONG bảng (hàng cuối, do virtual_blocks_v2.blade.php dựng sẵn) —
                // không còn đóng dấu nổi ở góc bảng nữa (tránh trùng lặp/che chữ khóa hệ thống).
                page.appendChild(el);
                if (!item.isVirtual) page.__allVirtual = false;
                renderedAny = true;
                // Cho chèn khối mới dưới block thường, hoặc dưới block ảo CUỐI CÙNG
                // (để thêm nội dung ngay sau vùng hệ thống)
                const isLastVirtual = item.isVirtual && (!items[idx + 1] || !items[idx + 1].isVirtual);
                if (!BOOT.isReadOnly && !BOOT.isExecutionMode && (!item.isVirtual || isLastVirtual)) {
                    page.appendChild(makeInserter(idx));
                }
            });
            return;
        }

        // run.kind === 'loop'
        if (run.entries.some(({ item }) => !item.isVirtual)) page.__allVirtual = false;
        if (BOOT.isExecutionMode) renderLoopGroupExecutionV2(run, page);
        else renderLoopGroupDesignV2(run, page);
        renderedAny = true;
    });

    // Trang chỉ chứa block/section HỆ THỐNG (isVirtual, VD: HEADER + PHÊ DUYỆT) thường có ít
    // nội dung -> co chiều cao theo nội dung thay vì luôn cao full A4, tránh dư khoảng trắng.
    Array.from(container.children).forEach((p) => {
        p.classList.toggle('v2-page-auto', !!p.__allVirtual);
    });

    if (!renderedAny) {
        if (!page) newPage();
        const hint = document.createElement('div');
        hint.className = 'text-center text-muted py-4';
        hint.innerHTML = '<i class="fas fa-file-alt fa-2x mb-2 opacity-50"></i><br>Hồ sơ chưa có nội dung. Hãy thêm khối văn bản hoặc bảng bên dưới để bắt đầu.';
        page.appendChild(hint);
        if (!BOOT.isReadOnly && !BOOT.isExecutionMode) page.appendChild(makeInserter(items.length - 1));
    }

    selection.onAfterRender(); // gắn lại class chọn ô/biến sau khi wipe DOM
    activateStaticBadges(); // Chạy thử: vẽ UI nhập liệu lên badge tĩnh (block không mount TipTap)
    updateHeadingNumbersV2(); // đánh số tiêu đề tự động (nếu đang bật)
    syncSplitPreviewV2(); // Split View: đồng bộ pane xem-thử nếu đang mở
    cmtApplyAll(); // vẽ lại highlight + rail bình luận lên DOM vừa dựng
    naMarks.onAfterRender(); // vẽ lại lớp gạch chéo "Không sử dụng" (Chạy thử/Thực thi)
}

/* ----------------------------------------------------------
 * 1a-ter. ĐÁNH SỐ TIÊU ĐỀ TỰ ĐỘNG nhiều cấp (giống Word: 1. / 1.1. / 1.1.1.)
 *   Số được TÍNH BẰNG JS theo thứ tự đọc toàn tài liệu (xuyên qua ranh giới block —
 *   CSS counter thuần không reset đúng cấp con giữa các block) rồi gắn vào data-hnum,
 *   CSS ::before hiển thị. Số KHÔNG nằm trong nội dung lưu => bật/tắt không đổi dữ liệu.
 * ---------------------------------------------------------- */
function headingNumberingOnV2() {
    // Mặc định BẬT — chỉ tắt khi người dùng chủ động tắt (lưu false trong docProperties)
    const v = BOOT.docProperties ? BOOT.docProperties.__headingNumbering : undefined;
    return v !== false && v !== 'false' && v !== 0;
}

function updateHeadingNumbersV2() {
    const on = headingNumberingOnV2();
    document.body.classList.toggle('v2-heading-num', on);
    document.getElementById('v2-btn-heading-num')?.classList.toggle('active', on);
    if (!on) {
        // Dọn số cũ để nơi khác đọc data-hnum (VD: mục lục) không thấy số stale
        document.querySelectorAll('#v2-pages [data-hnum]').forEach((h) => h.removeAttribute('data-hnum'));
        return;
    }
    // Sơ đồ đánh số: TITLE SECTION = cấp 1 (1., 2., ...); tiêu đề bên trong section
    // là cấp con của section đó (h1 -> N.k, h2 -> N.k.m, h3 -> N.k.m.j).
    // Section/block ẢO hoặc KHÓA (hệ thống: HEADER, PHÊ DUYỆT...) KHÔNG đánh số.
    // NHÁNH PHÒNG (xem sectionTrackInfoV2): nhánh GỐC (track=1) luôn giữ số "N." KHÔNG đổi —
    // đây là tiêu đề CHUNG của công đoạn dù đã tách nhánh hay chưa. Nhánh MỚI (track>=2) được
    // đánh số con "N.1.", "N.2.", ... theo đúng số track (không phụ thuộc thứ tự DOM), để
    // xoá 1 nhánh không làm đổi số của các nhánh còn lại.
    // Xem qua ?section= (VD: iframe xem trước trong modal Phân phối): DOM chỉ còn đúng 1 công
    // đoạn nên đếm từ 0 sẽ luôn ra "1." dù công đoạn đó là số mấy trong hồ sơ gốc. Server đã
    // tính sẵn số thật (activeSectionNumber) -> khởi tạo bộ đếm từ đó thay vì từ 0.
    let secN = BOOT.activeSectionNumber ? BOOT.activeSectionNumber - 1 : 0; // số thứ tự section thường
    let inSystemSec = false; // đang duyệt bên trong section hệ thống -> bỏ qua tiêu đề
    let curPrefix = '';     // tiền tố số của section hiện tại (VD "3." hoặc "3.2.") cho tiêu đề con
    const groupSecN = new Map(); // key nhóm (category::stageCode) -> số cấp 1 đã gán (gán khi gặp lần đầu)
    let n1 = 0, n2 = 0, n3 = 0;
    document.querySelectorAll('#v2-pages .v2-section[id^="v2-sec-"], #v2-pages h1, #v2-pages h2, #v2-pages h3')
        .forEach((el) => {
            if (el.classList.contains('v2-section')) {
                const item = items.find((it) => it.id === el.id.replace(/^v2-sec-/, ''));
                const titleEl = el.querySelector('.v2-section-title');
                if (!item || item.isVirtual || item.locked) {
                    inSystemSec = true;
                    titleEl?.removeAttribute('data-hnum');
                } else {
                    inSystemSec = false; n1 = 0; n2 = 0; n3 = 0;
                    const { category, stageCode, track } = sectionTrackInfoV2(item);
                    const key = category + '::' + stageCode;
                    if (!groupSecN.has(key)) { secN++; groupSecN.set(key, secN); }
                    const gSecN = groupSecN.get(key);
                    const numStr = track >= 2 ? `${gSecN}.${track - 1}` : String(gSecN);
                    curPrefix = numStr + '.';
                    titleEl?.setAttribute('data-hnum', `${numStr}. `);
                }
                return;
            }
            // Tiêu đề trong section hệ thống hoặc trong block ảo/khóa: không đánh số
            if (inSystemSec || el.closest('.v2-locked')) { el.removeAttribute('data-hnum'); return; }
            const pre = secN ? curPrefix : '';
            if (el.tagName === 'H1') { n1++; n2 = 0; n3 = 0; el.setAttribute('data-hnum', `${pre}${n1}. `); }
            else if (el.tagName === 'H2') { n2++; n3 = 0; el.setAttribute('data-hnum', `${pre}${n1}.${n2}. `); }
            else { n3++; el.setAttribute('data-hnum', `${pre}${n1}.${n2}.${n3}. `); }
        });
}

function toggleHeadingNumberingV2() {
    BOOT.docProperties = BOOT.docProperties || {};
    BOOT.docProperties.__headingNumbering = !headingNumberingOnV2(); // true/false tường minh
    markDirty(); // setting lưu trong docProperties (cột doc_properties) — gửi kèm lượt lưu sau
    unmountEditor(); // chốt nội dung + render lại để số áp lên DOM tĩnh mới, chắc chắn thấy ngay
    renderDocument();
    showToast('success', headingNumberingOnV2() ? 'Đã bật đánh số tiêu đề tự động' : 'Đã tắt đánh số tiêu đề');
}

/* ----------------------------------------------------------
 * 1a-bis. CHỌN DẢI KHỐI cho Lặp nhóm (nút "Lặp nhóm" trên toolbar mỗi block)
 * ---------------------------------------------------------- */
function paintBlockPickV2() {
    document.querySelectorAll('.v2-block.v2-block-picked').forEach((el) => el.classList.remove('v2-block-picked'));
    document.querySelectorAll('.v2-block-toolbar [data-act="pick"].active').forEach((b) => b.classList.remove('active'));
    blockPickIds.forEach((id) => {
        const el = document.querySelector(`.v2-block[data-id="${id}"]`);
        el?.classList.add('v2-block-picked');
        el?.querySelector('[data-act="pick"]')?.classList.add('active');
    });
    updateLoopPickBarV2();
}

function clearBlockPickV2() {
    blockPickAnchor = null;
    blockPickIds = [];
    paintBlockPickV2();
}

function pickBlockV2(item, extend) {
    if (!extend) {
        if (blockPickIds.length === 1 && blockPickIds[0] === item.id) { clearBlockPickV2(); return; } // click lại -> bỏ chọn
        blockPickAnchor = item.id;
        blockPickIds = [item.id];
        paintBlockPickV2();
        return;
    }
    if (!blockPickAnchor) { blockPickAnchor = item.id; blockPickIds = [item.id]; paintBlockPickV2(); return; }
    const anchorIdx = items.findIndex((i) => i.id === blockPickAnchor);
    const targetIdx = items.findIndex((i) => i.id === item.id);
    if (anchorIdx === -1 || targetIdx === -1) return;
    const lo = Math.min(anchorIdx, targetIdx), hi = Math.max(anchorIdx, targetIdx);
    const range = [];
    for (let i = lo; i <= hi; i++) {
        const it = items[i];
        if (it.type === 'section' || it.type === 'document-settings') {
            showToast('warning', 'Các khối được chọn phải nằm trong cùng 1 phân đoạn');
            return;
        }
        range.push(it.id);
    }
    blockPickIds = range;
    paintBlockPickV2();
}

/* ── CHẾ ĐỘ CHỌN KHỐI: bấm nút toolbar để bật, rồi click thẳng vào khối đầu →
 *    khối cuối là chọn cả dải (không cần Shift, không mở editor). Esc/Hủy để thoát. ── */
function setBlockPickModeV2(on) {
    if (blockPickMode === on) return;
    blockPickMode = on;
    document.body.classList.toggle('v2-loop-pick-mode', on);
    document.getElementById('v2-btn-loop-group')?.classList.toggle('active', on);
    if (on) {
        unmountEditor();
        selection.clearAll();
    } else {
        clearBlockPickV2();
    }
    updateLoopPickBarV2();
}

/** Click khối trong chế độ chọn: lần 1 = neo, lần 2 (khối khác) = chọn cả dải giữa 2 khối. */
function pickBlockRangeV2(item) {
    if (!blockPickIds.length) {
        blockPickAnchor = item.id;
        blockPickIds = [item.id];
        paintBlockPickV2();
        return;
    }
    if (blockPickIds.length === 1 && blockPickIds[0] === item.id) { // click lại khối duy nhất -> bỏ chọn
        blockPickAnchor = null;
        blockPickIds = [];
        paintBlockPickV2();
        return;
    }
    if (blockPickIds.includes(item.id)) { // click 1 khối trong dải đã chọn -> đặt lại neo mới từ khối đó
        blockPickAnchor = item.id;
        blockPickIds = [item.id];
        paintBlockPickV2();
        return;
    }
    pickBlockV2(item, true); // mở rộng dải từ neo tới khối này (kèm kiểm tra cùng phân đoạn)
}

/** Thanh nổi dưới màn hình khi có khối đang "chọn" (blockPickIds — dù bật chế độ Lặp
 *  nhóm hay chỉ bấm nút "Chọn khối" đơn lẻ trên toolbar): đếm số khối + nút Sao
 *  chép/Cắt (dùng chung, đi kèm phím tắt Ctrl+C/X/V) + Cài đặt lặp (chỉ khi đang ở
 *  chế độ chọn khối cho Lặp nhóm) + Hủy. */
function updateLoopPickBarV2() {
    let bar = document.getElementById('v2-loop-pickbar');
    const show = blockPickMode || blockPickIds.length > 0;
    if (!show) { bar?.remove(); return; }
    if (!bar) {
        bar = document.createElement('div');
        bar.id = 'v2-loop-pickbar';
        bar.innerHTML = `
            <span id="v2-loop-pickbar-count"></span>
            <button type="button" class="btn btn-sm btn-outline-primary" id="v2-loop-pickbar-copy"><i class="fas fa-copy me-1"></i>Sao chép</button>
            <button type="button" class="btn btn-sm btn-outline-primary" id="v2-loop-pickbar-cut"><i class="fas fa-cut me-1"></i>Cắt</button>
            <button type="button" class="btn btn-sm btn-primary" id="v2-loop-pickbar-apply">
                <i class="fas fa-redo me-1"></i>Cài đặt lặp</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="v2-loop-pickbar-cancel">Hủy (Esc)</button>`;
        document.body.appendChild(bar);
        bar.querySelector('#v2-loop-pickbar-apply').addEventListener('click', () => openBlockLoopModalV2());
        bar.querySelector('#v2-loop-pickbar-cancel').addEventListener('click', () => { clearBlockPickV2(); if (blockPickMode) setBlockPickModeV2(false); });
        bar.querySelector('#v2-loop-pickbar-copy').addEventListener('click', () => copyPickedBlocksV2(false));
        bar.querySelector('#v2-loop-pickbar-cut').addEventListener('click', () => copyPickedBlocksV2(true));
    }
    const hasPick = blockPickIds.length > 0;
    const count = bar.querySelector('#v2-loop-pickbar-count');
    count.textContent = hasPick
        ? `Đã chọn ${blockPickIds.length} khối`
        : 'Click khối ĐẦU, rồi click khối CUỐI để chọn dải';
    bar.querySelector('#v2-loop-pickbar-apply').style.display = blockPickMode ? '' : 'none';
    bar.querySelector('#v2-loop-pickbar-apply').disabled = !hasPick;
    bar.querySelector('#v2-loop-pickbar-copy').disabled = !hasPick;
    bar.querySelector('#v2-loop-pickbar-cut').disabled = !hasPick;
}

/** Mở lại đúng dải khối của 1 nhóm lặp đã có (click vào badge) rồi mở modal sửa. */
function editLoopGroupV2(groupId) {
    const ids = items.filter((i) => i.loop_group_id === groupId).map((i) => i.id);
    if (!ids.length) return;
    blockPickAnchor = ids[0];
    blockPickIds = ids;
    paintBlockPickV2();
    openBlockLoopModalV2();
}

/** Cài đặt / sửa / gỡ Lặp nhóm khối cho dải khối đang được chọn (blockPickIds). */
function openBlockLoopModalV2() {
    if (BOOT.isReadOnly || BOOT.isExecutionMode) return;
    if (!blockPickIds.length) {
        window.Swal.fire({
            icon: 'info',
            title: 'Hướng dẫn chọn nhóm khối',
            text: 'Nhấn nút "Lặp nhóm khối" trên thanh công cụ để vào chế độ chọn, sau đó click vào khối ĐẦU rồi click khối CUỐI để chọn dải khối cần lặp.',
            confirmButtonText: 'Đã hiểu',
        });
        return;
    }

    let existingCount = 3;
    let existingGroupId = null;
    let existingLabel = '';
    let existingLabels = [];
    for (const id of blockPickIds) {
        const b = items.find((i) => i.id === id);
        if (b && b.loop_group_id) {
            existingCount = b.loop_count || 3;
            existingGroupId = b.loop_group_id;
            existingLabel = b.loop_label || '';
            existingLabels = Array.isArray(b.loop_labels) ? b.loop_labels : [];
            break;
        }
    }

    const renderNameRows = (count, prefix, labels) => Array.from({ length: count }, (_, k) => {
        const i = k + 1;
        return `<div class="d-flex align-items-center gap-2 mb-1">
                <span class="text-muted small" style="width:20px">${i}.</span>
                <input type="text" class="form-control form-control-sm v2-loop-name-item" data-idx="${i}" maxlength="40"
                    placeholder="${esc(prefix + ' ' + i)}" value="${esc((labels && labels[k]) || '')}">
            </div>`;
    }).join('');

    window.Swal.fire({
        title: existingGroupId ? 'Chỉnh sửa Lặp nhóm khối' : 'Cài đặt Lặp nhóm khối',
        html: `<div class="text-start">
                <label class="form-label small fw-bold mb-1">Số lần lặp</label>
                <input type="number" id="v2-loop-count-input" class="form-control" min="1" max="50" value="${existingCount}">
                <label class="form-label small fw-bold mb-1 mt-3">Tên gọi mặc định</label>
                <input type="text" id="v2-loop-label-input" class="form-control" maxlength="30"
                    placeholder="Mặc định: Lần" value="${esc(existingLabel)}">
                <div class="form-text" style="font-size:0.72rem">Dùng khi 1 lần lặp không có tên riêng bên dưới — VD: Mẻ, Lô, Thùng. Bỏ trống = "Lần".</div>
                <label class="form-label small fw-bold mb-1 mt-3">Đặt tên riêng cho từng lần (tùy chọn)</label>
                <div id="v2-loop-names-list">${renderNameRows(existingCount, existingLabel || 'Lần', existingLabels)}</div>
                <div class="form-text" style="font-size:0.72rem">Bỏ trống dòng nào thì tab đó dùng tên mặc định ở trên. VD: Đầu lô, Giữa lô, Cuối lô.</div>
                <div class="form-text" style="font-size:0.72rem">${blockPickIds.length} khối đang được chọn.</div>
            </div>`,
        showCancelButton: true,
        showDenyButton: !!existingGroupId,
        confirmButtonText: existingGroupId ? 'Cập nhật' : 'Áp dụng',
        denyButtonText: 'Gỡ bỏ lặp',
        denyButtonColor: '#dc3545',
        cancelButtonText: 'Hủy',
        didOpen: () => {
            const countInput = document.getElementById('v2-loop-count-input');
            const labelInput = document.getElementById('v2-loop-label-input');
            const listEl = document.getElementById('v2-loop-names-list');
            const regenerate = () => {
                const v = Math.min(50, Math.max(1, parseInt(countInput.value, 10) || 1));
                const curLabels = Array.from(listEl.querySelectorAll('.v2-loop-name-item')).map((el) => el.value);
                listEl.innerHTML = renderNameRows(v, (labelInput.value || '').trim() || 'Lần', curLabels);
            };
            countInput.addEventListener('input', regenerate);
            labelInput.addEventListener('input', regenerate);
        },
        preConfirm: () => {
            const v = parseInt(document.getElementById('v2-loop-count-input').value, 10);
            if (!v || v < 1) { window.Swal.showValidationMessage('Số lần lặp phải >= 1'); return false; }
            const names = Array.from(document.querySelectorAll('#v2-loop-names-list .v2-loop-name-item'))
                .slice(0, v).map((el) => el.value.trim());
            return {
                count: v,
                label: (document.getElementById('v2-loop-label-input').value || '').trim(),
                labels: names.some((s) => s) ? names : [],
            };
        },
    }).then((result) => {
        if (result.isConfirmed) {
            const { count: loopCount, label: loopLabel, labels: loopLabels } = result.value;
            const groupId = existingGroupId || ('loopgrp_v2_' + Date.now().toString(36));
            saveDocState();
            blockPickIds.forEach((id) => {
                const b = items.find((i) => i.id === id);
                if (b) {
                    b.loop_group_id = groupId;
                    b.loop_count = loopCount;
                    if (loopLabel) b.loop_label = loopLabel; else delete b.loop_label;
                    if (loopLabels.length) b.loop_labels = loopLabels; else delete b.loop_labels;
                    b.dirty = true;
                }
            });
            setBlockPickModeV2(false);
            clearBlockPickV2();
            markDirty();
            renderDocument();
            showToast('success', 'Đã áp dụng lặp cho nhóm khối');
        } else if (result.isDenied) {
            saveDocState();
            blockPickIds.forEach((id) => {
                const b = items.find((i) => i.id === id);
                if (b) { delete b.loop_group_id; delete b.loop_count; delete b.loop_label; delete b.loop_labels; b.dirty = true; }
            });
            setBlockPickModeV2(false);
            clearBlockPickV2();
            markDirty();
            renderDocument();
            showToast('info', 'Đã gỡ bỏ lặp nhóm khối');
        }
    });
}

/* ── Copy / Cắt / Dán CẢ CỤM khối đang "chọn" (blockPickIds) — Ctrl+C/X/V ──
 *   Dán được vào bất kỳ vị trí nào (kể cả section/sub_section khác): section_id của
 *   bản sao luôn ghi lại theo ĐIỂM CHÈN đang nhắm tới (getInsertPointV2), không giữ
 *   section_id gốc. Biến số trong vùng copy được NHÂN BẢN sang id mới (tái dùng đúng
 *   cơ chế buildFieldDuplicateMapV2 đã dùng cho copy/paste Ô bảng) để bản dán không
 *   dùng chung biến với bản gốc — mỗi lần Dán lại tạo bản sao biến MỚI, dán nhiều lần
 *   không bị trùng id. */
/** Điểm chèn block mới = item (block/section) được click gần nhất; không có thì cuối
 *  tài liệu. Click vào tiêu đề section -> chèn ngay đầu section đó. Dùng chung cho
 *  global paste (Ctrl+V), gõ-phím-tạo-khối, và Dán cụm khối (pasteBlockClipboardV2). */
function getInsertPointV2() {
    let insertIdx = items.length;
    let anchor = items.length ? items[items.length - 1] : null;
    if (pasteAnchorIdV2 != null) {
        const aIdx = items.findIndex((i) => String(i.id) === String(pasteAnchorIdV2));
        if (aIdx !== -1) { insertIdx = aIdx + 1; anchor = items[aIdx]; }
    }
    // Anchor là block ảo hệ thống: không chèn xen giữa vùng hệ thống, dời xuống dưới block ảo cuối
    if (anchor && anchor.isVirtual) {
        while (insertIdx < items.length && items[insertIdx].isVirtual) insertIdx++;
    }
    const sectionId = anchor ? (anchor.type === 'section' ? (anchor.section_id || anchor.id) : anchor.section_id) : null;
    return { insertIdx, sectionId };
}
function collectBlockHtmlListV2(blocks) {
    const htmlList = [];
    blocks.forEach((b) => {
        if (typeof b.content === 'string') htmlList.push(b.content);
        if (b.type === 'table' && Array.isArray(b.data)) {
            b.data.forEach((row) => row.forEach((cell) => {
                if (cell && typeof cell === 'object' && typeof cell.content === 'string') htmlList.push(cell.content);
            }));
        }
    });
    return htmlList;
}

function copyPickedBlocksV2(isCut) {
    if (!blockPickIds.length) return;
    const picked = blockPickIds
        .map((id) => items.find((i) => i.id === id))
        .filter(Boolean);
    if (!picked.length) return;
    blockClipboardV2 = { blocks: JSON.parse(JSON.stringify(picked)) };
    showToast('info', isCut ? `Đã cắt ${picked.length} khối` : `Đã sao chép ${picked.length} khối`);
    if (isCut) {
        saveDocState();
        picked.forEach((it) => { if (it.db_id) deletedBlockIds.push(it.db_id); });
        items = items.filter((i) => !blockPickIds.includes(i.id));
        clearBlockPickV2();
        markDirty();
        renderDocument();
    }
}

function pasteBlockClipboardV2() {
    if (BOOT.isReadOnly || BOOT.isExecutionMode) return;
    if (!blockClipboardV2 || !blockClipboardV2.blocks.length) return;
    const { insertIdx, sectionId } = getInsertPointV2();
    const srcBlocks = blockClipboardV2.blocks;

    const htmlList = collectBlockHtmlListV2(srcBlocks);
    const { idMap, nameMap } = buildFieldDuplicateMapV2(htmlList);
    if (Object.keys(idMap).length) applyFieldDuplicateMapV2(idMap, nameMap);

    const newBlocks = srcBlocks.map((b) => {
        const nb = JSON.parse(JSON.stringify(b));
        nb.id = newBlockId();
        nb.section_id = sectionId;
        nb.dirty = true;
        delete nb.db_id;
        delete nb.content_db_id;
        delete nb.loop_group_id; // không mang theo nhóm Lặp của bản gốc
        delete nb.loop_count;
        delete nb.loop_label;
        delete nb.loop_labels;
        if (typeof nb.content === 'string') nb.content = rewriteFieldIdsInHtmlV2(nb.content, idMap);
        if (nb.type === 'table' && Array.isArray(nb.data)) {
            nb.data.forEach((row) => row.forEach((cell) => {
                if (cell && typeof cell === 'object') {
                    if (typeof cell.content === 'string') cell.content = rewriteFieldIdsInHtmlV2(cell.content, idMap);
                    delete cell.db_id;
                    delete cell.content_db_id;
                }
            }));
        }
        return nb;
    });

    saveDocState();
    items.splice(insertIdx, 0, ...newBlocks);
    pasteAnchorIdV2 = newBlocks[newBlocks.length - 1].id;
    markDirty();
    renderDocument();
    showToast('success', `Đã dán ${newBlocks.length} khối`);
}

/* =========================================================
 * 1b. THÊM KHỐI MỚI (Văn bản / Bảng)
 * ========================================================= */
function newBlockId() {
    return 'blk_v2_' + Date.now().toString(36) + Math.random().toString(36).slice(2, 6);
}

/** Dựng object 1 khối mới (văn bản hoặc bảng rows x cols) — dùng chung cho nút hover
 *  giữa 2 khối (addBlock) và 2 nút "Chèn văn bản"/"Chèn bảng" trên toolbar (chèn tại
 *  đúng vị trí con trỏ / điểm click gần nhất, xem getInsertPointV2()). */
function buildNewBlockV2(type, sectionId, rows = 2, cols = 3) {
    if (type === 'table') {
        return {
            id: newBlockId(), type: 'table', label: 'Bảng mới',
            rows, cols,
            columns: Array.from({ length: cols }, (_, i) => ({ label: `Cột ${i + 1}`, width: '' })),
            data: Array.from({ length: rows }, () =>
                Array.from({ length: cols }, () => ({ content: '', rs: 1, cs: 1, hidden: false }))),
            rowHeights: [], borderMode: 'all', hideHeader: true, // không hiện hàng tiêu đề "Cột 1/2/3..."
            section_id: sectionId, dirty: true,
        };
    }
    return {
        id: newBlockId(), type: 'static-text', label: 'Văn bản',
        content: '<p></p>', section_id: sectionId, dirty: true,
    };
}

/* =========================================================
 * 1b-bis. SIDEBAR BIỂU MẪU CHUNG (GF) — kéo thả chèn liên kết SỐNG theo doc_code
 * (như sidebar Thành phần CO/Thiết bị: cho phép thả vào BẤT KỲ vị trí nào giữa
 * 2 khối liên tiếp, thay vì chỉ chèn tại điểm click cuối cùng qua modal).
 * Khối chỉ lưu ref_doc_code + label, KHÔNG copy nội dung; nội dung thật được
 * server (LinkedGfResolver) resolve lúc thiết kế/thực thi. */
let allGfsV2 = null;

async function loadGfListV2() {
    const list = document.getElementById('v2-gf-list');
    list.innerHTML = '<div class="text-center text-muted small py-3"><div class="spinner-border spinner-border-sm me-1"></div> Đang tải...</div>';
    const data = await fetch(BOOT.urls.templates).then((r) => r.json()).catch(() => null);
    if (!data) { list.innerHTML = '<div class="text-danger small text-center py-3">Lỗi tải dữ liệu.</div>'; return; }

    // Chỉ giữ GF đang active, gộp theo doc_code (mỗi Số BM chỉ hiện 1 dòng — bản mới nhất để hiển thị,
    // việc resolve bản active thật sự luôn được server làm lại tại thời điểm chèn/thực thi).
    const gfs = data.filter((t) => t.type === 'GF' && t.status === 'active' && t.doc_code);
    const byDocCode = new Map();
    gfs.forEach((t) => {
        const existed = byDocCode.get(t.doc_code);
        if (!existed || (t.version || 0) > (existed.version || 0)) byDocCode.set(t.doc_code, t);
    });
    allGfsV2 = Array.from(byDocCode.values());
    renderGfListV2(allGfsV2);
}

function renderGfListV2(gfs) {
    const list = document.getElementById('v2-gf-list');
    const kw = (document.getElementById('v2-gf-search').value || '').toLowerCase();
    const filtered = gfs.filter((t) => !kw || (t.name || '').toLowerCase().includes(kw) || (t.doc_code || '').toLowerCase().includes(kw));
    if (!filtered.length) {
        list.innerHTML = '<div class="text-muted small text-center py-3">Không có biểu mẫu chung nào.</div>';
        return;
    }
    list.innerHTML = filtered.map((t) => `
        <div class="v2-drag-card" draggable="true" data-gf-doc-code="${esc(t.doc_code)}" data-gf-name="${esc(t.name)}">
            <i class="fas fa-grip-vertical text-muted mt-1"></i>
            <div style="min-width:0;">
                <div class="fw-bold text-truncate" title="${esc(t.name)}">${esc(t.name)}</div>
                <div class="small-muted">Số BM: ${esc(t.doc_code)} · Cập nhật: ${new Date(t.updated_at).toLocaleString('vi-VN')}</div>
            </div>
        </div>`).join('');
    list.querySelectorAll('.v2-drag-card').forEach((card) => {
        card.addEventListener('dragstart', (e) => {
            e.dataTransfer.setData('action', 'insertLinkedGf');
            e.dataTransfer.setData('gfDocCode', card.getAttribute('data-gf-doc-code'));
            e.dataTransfer.setData('gfName', card.getAttribute('data-gf-name'));
            e.dataTransfer.effectAllowed = 'copy';
            document.body.classList.add('v2-dragging');
        });
    });
}

function filterGfsV2() {
    renderGfListV2(allGfsV2 || []);
}

function insertLinkedGfV2(afterIndex, docCode, name) {
    if (BOOT.isReadOnly || BOOT.isExecutionMode) return; // Chạy thử: không cho chèn khối mới
    const anchor = items[afterIndex] || null;
    const sectionId = anchor ? (anchor.type === 'section' ? (anchor.section_id || anchor.id) : anchor.section_id) : null;
    const block = {
        id: newBlockId(), type: 'linked-template', ref_doc_code: docCode, label: name,
        locked: false, section_id: sectionId, dirty: true,
    };
    saveDocState();
    insertBlockAndFocusV2(block, Math.min(afterIndex + 1, items.length));
}

function toggleGfPreviewV2(id, docCode) {
    const item = items.find((i) => i.id === id);
    if (item) {
        // Chỉ gọi được ở chế độ Thiết kế — Chạy thử/thực thi không render nút toggle (GF luôn bung).
        item.showPreview = !item.showPreview;
        if (!BOOT.isExecutionMode) { item.dirty = true; markDirty(); }
        renderDocument();
        return;
    }
    // Khối linked-template LỒNG bên trong 1 GF khác (chỉ tồn tại trong bản xem trước
    // fetch riêng qua API, không nằm trong items[] của tài liệu chủ) — không có state
    // showPreview để markDirty/renderDocument lại, nên bật/tắt trực tiếp trên DOM.
    const container = document.getElementById(`v2-gf-preview-${id}`);
    if (!container) return;
    const show = container.style.display === 'none';
    container.style.display = show ? 'block' : 'none';
    if (show && !container.dataset.loaded) {
        container.dataset.loaded = '1';
        fetchAndRenderGfPreviewV2(id, docCode);
    }
}

let gfPreviewCacheV2 = {};

async function fetchAndRenderGfPreviewV2(blockId, docCode) {
    const container = document.getElementById(`v2-gf-preview-${blockId}`);
    if (!container || !docCode) return;
    if (gfPreviewCacheV2[docCode]) {
        renderGfPreviewContentV2(container, gfPreviewCacheV2[docCode].blocks, gfPreviewCacheV2[docCode].fields, gfPreviewCacheV2[docCode].template);
        return;
    }
    container.innerHTML = '<div class="text-muted small py-2"><div class="spinner-border spinner-border-sm me-1"></div> Đang tải xem trước...</div>';
    const data = await fetch(`${BOOT.urls.gfBlocksByDocCode}?doc_code=${encodeURIComponent(docCode)}`)
        .then((r) => r.json()).catch(() => null);
    if (!data || !Array.isArray(data.blocks)) {
        container.innerHTML = '<div class="text-danger small py-2">Không tải được nội dung xem trước.</div>';
        return;
    }
    const fields = data.fields || {};
    gfPreviewCacheV2[docCode] = { blocks: data.blocks, fields, template: data.template || null };
    renderGfPreviewContentV2(container, data.blocks, fields, data.template || null);
}

/** Thanh chú thích GF liên kết: cho biết đang gắn Số biểu mẫu nào / ấn bản mấy / SOP đối
 *  chiếu. Số biểu mẫu = gf_category.code + "-" + version (khớp footer GF gốc). Đồng bộ với
 *  buildLinkedGfCaptionField phía server (EbmrExecutionController). */
function buildGfCaptionHtmlV2(template) {
    if (!template) return '';
    const code = template.category_code || template.doc_code || '';
    if (!code) return '';
    const version = (template.version === null || template.version === undefined) ? '' : template.version;
    const formNo = version !== '' ? `${code}-${version}` : code;
    const sop = template.relatived_sop_no || '—';
    return `<div class="ebmr-gf-caption" style="display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;gap:6px 16px;padding:7px 14px;margin:0 0 10px;background:#eff5ff;border:1px solid #bcd3fb;border-left:4px solid #2563eb;border-radius:6px;font-size:.88rem;line-height:1.35;">
        <span style="font-weight:600;color:#1e40af;"><i class="fas fa-link" style="margin-right:6px;"></i>Biểu mẫu chung đính kèm</span>
        <span style="color:#334155;">Số biểu mẫu: <strong>${esc(formNo)}</strong> &nbsp;·&nbsp; Ấn bản: <strong>${esc(String(version))}</strong> &nbsp;·&nbsp; SOP đối chiếu: <strong>${esc(sop)}</strong></span>
    </div>`;
}

/** Bảng của GF được thiết kế theo bề rộng trang đầy đủ (cột lưu px). Khung xem trước
 *  hẹp hơn trang (padding + viền) nên tổng px vượt bề rộng vùng chứa → bảng tràn khỏi
 *  trang giấy. Đổi px sang % (giữ nguyên tỉ lệ) để bảng co theo khung chứa.
 *  Trả về BẢN SAO nông — không sửa block trong cache gfPreviewCacheV2. */
function scaleGfTableColumnsV2(block) {
    const cols = Array.isArray(block.columns) ? block.columns : [];
    const px = cols.map((c) => (typeof c?.width === 'string' && c.width.endsWith('px')) ? parseFloat(c.width) : null);
    const total = px.reduce((s, v) => s + (v || 0), 0);
    // Có cột auto/thiếu px thì bỏ qua (không suy được tỉ lệ); toàn auto thì tự chia đều, không tràn.
    if (!total || px.some((v) => v === null || isNaN(v))) return block;
    return {
        ...block,
        columns: cols.map((c, i) => ({ ...c, width: (px[i] / total * 100).toFixed(2) + '%' })),
    };
}

/** Render các khối của GF (đọc riêng qua API, không nằm trong items[]) ở chế độ CHỈ ĐỌC —
 *  dùng lại renderBlockContentV2 với contentOverride để không gắn listener chỉnh sửa.
 *  fields: fieldsConfig RIÊNG của GF (field-id của GF không tồn tại trong fieldsConfig
 *  của tài liệu chủ) — phải truyền vào để badge hiện đúng nhãn/loại thay vì rơi về mặc định.
 *  Mỗi block được bọc try/catch riêng để 1 khối lỗi không chặn các khối còn lại hiển thị. */
function renderGfPreviewContentV2(container, blocks, fields, template) {
    if (!blocks.length) {
        container.innerHTML = '<div class="text-muted small text-center py-3 fst-italic">Biểu mẫu này chưa có nội dung.</div>';
        return;
    }
    container.innerHTML = '';
    const wrap = document.createElement('div');
    wrap.className = 'v2-gf-preview-content border rounded p-3 bg-white';
    const captionHtml = buildGfCaptionHtmlV2(template);
    if (captionHtml) wrap.insertAdjacentHTML('beforeend', captionHtml);
    blocks.filter((b) => b.type !== 'section').forEach((canonicalBlock) => {
        try {
            let b = canonicalBlock;
            if (b.type === 'table') {
                // scaleGfTableColumnsV2 trả BẢN SAO nông (khi cột dạng px) → "Thêm/Xóa dòng"
                // sẽ mutate rows trên bản sao mà mất write-back về cache. Giữ tham chiếu block
                // gốc trong cache để đồng bộ ngược (xem syncGfCanonicalRowsV2).
                b = scaleGfTableColumnsV2(b);
                if (b !== canonicalBlock) b.__gfCanonical = canonicalBlock;
            }
            const el = renderBlockContentV2(
                b,
                b.type === 'static-text' ? (b.content || '') : undefined,
                b.type === 'table' ? b.data : undefined,
                fields
            );
            if (el) wrap.appendChild(el);
        } catch (err) {
            console.error('Lỗi render khối xem trước GF:', b, err);
            const warn = document.createElement('div');
            warn.className = 'text-danger small py-1';
            warn.textContent = `[Không hiển thị được khối "${b.label || b.type || ''}"]`;
            wrap.appendChild(warn);
        }
    });
    container.appendChild(wrap);

    // Chạy thử: field của GF không có trong fieldsConfig tài liệu chủ — bổ sung (không đè
    // key trùng) rồi kích hoạt badge trong vùng này để điền dữ liệu được như field thường.
    if (BOOT.isExecutionMode && fields) {
        Object.keys(fields).forEach((k) => { if (!fieldsConfig[k]) fieldsConfig[k] = fields[k]; });
        activateStaticBadgesIn(wrap);
    }
}

/** Chèn 1 khối đã dựng sẵn vào items tại insertAt, render lại rồi focus/scroll tới đó. */
function insertBlockAndFocusV2(block, insertAt) {
    items.splice(insertAt, 0, block);
    pasteAnchorIdV2 = block.id; // chèn/gõ/dán tiếp theo sẽ nối ngay sau khối này
    markDirty();
    renderDocument();

    const el = document.querySelector(`.v2-block[data-id="${block.id}"] .v2-editable`);
    if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
        if (block.type === 'static-text') el.click();
    } else {
        document.querySelector(`.v2-block[data-id="${block.id}"]`)?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
}

function addBlock(type, afterIndex) {
    if (BOOT.isReadOnly || BOOT.isExecutionMode) return; // Chạy thử: không cho thêm khối mới
    saveDocState();
    // Kế thừa section_id từ block đứng trước để khối mới nằm đúng công đoạn
    const anchor = items[afterIndex] || null;
    const sectionId = anchor ? (anchor.type === 'section' ? (anchor.section_id || anchor.id) : anchor.section_id) : null;

    const block = buildNewBlockV2(type, sectionId);
    const insertAt = Math.min(afterIndex + 1, items.length);
    insertBlockAndFocusV2(block, insertAt);
}

/** Xóa 1 khối khỏi tài liệu (không cho xóa khối hệ thống/khóa) */
async function deleteBlock(id) {
    if (BOOT.isReadOnly || BOOT.isExecutionMode) return;
    const item = items.find((i) => i.id === id);
    if (!item || item.locked || item.isVirtual) return;

    const res = await window.Swal.fire({
        title: 'Xóa khối này?',
        text: 'Nội dung khối sẽ bị xóa vĩnh viễn sau khi lưu hồ sơ.',
        icon: 'warning', showCancelButton: true,
        confirmButtonColor: '#d33', confirmButtonText: 'Xóa', cancelButtonText: 'Hủy',
    });
    if (!res.isConfirmed) return;

    unmountEditor();
    saveDocState();
    if (item.db_id) deletedBlockIds.push(item.db_id);
    items = items.filter((i) => i.id !== id);
    markDirty();
    renderDocument();
}

/* =========================================================
 * 1b. TÁCH SECTION THÀNH NHIỀU "NHÁNH PHÒNG"
 *   1 công đoạn sản xuất (section, stage_code số 1-7) trong thực tế có thể chạy song song
 *   ở nhiều phòng khác nhau — nhưng hệ thống phân phối/thực thi chỉ cho phép 1 section đi
 *   xuống ĐÚNG 1 phòng. Giải pháp: cho phép tách 1 section thành nhiều section_id riêng
 *   (mỗi cái là 1 "nhánh"), cùng stage_code, được soạn và phân phối ĐỘC LẬP với nhau.
 *   Sơ đồ section_id: nhánh gốc giữ nguyên "{category}_{stageCode}" (không đổi, để không vỡ
 *   các bản ghi phân phối đã tham chiếu ID cũ); nhánh tách thêm mang dạng
 *   "{category}_{track}_{stageCode}" — hậu tố chèn Ở GIỮA (không phải cuối) vì MỌI nơi khác
 *   trong hệ thống (getRoomsConfig, TOC, nhãn "Công đoạn N"...) đều lấy segment CUỐI làm
 *   stage_code; chèn giữa giữ nguyên hành vi đó ở mọi nơi khác.
 * ========================================================= */

/** Suy ra {category, stageCode, track} từ section_id hiện tại của 1 section item. */
function sectionTrackInfoV2(item) {
    const parts = String(item.section_id || '').split('_');
    if (parts.length >= 3) {
        const track = parseInt(parts[1], 10);
        if (!isNaN(track)) return { category: parts[0], stageCode: parts.slice(2).join('_'), track };
    }
    return { category: parts.slice(0, -1).join('_'), stageCode: parts[parts.length - 1] || '', track: 1 };
}

/** Khóa nhóm CÔNG ĐOẠN (category::stageCode) — mọi nhánh phòng cùng công đoạn dùng chung khóa này. */
function sectionGroupKeyV2(item) {
    const { category, stageCode } = sectionTrackInfoV2(item);
    return category + '::' + stageCode;
}

/** Section GỐC (track 1) của nhóm công đoạn chứa item — là tiêu đề CHUNG "N." của các nhánh phòng. */
function sectionTrackRootV2(item) {
    const key = sectionGroupKeyV2(item);
    return items.find((it) => it.type === 'section' && !it.isVirtual
        && sectionGroupKeyV2(it) === key && sectionTrackInfoV2(it).track === 1) || null;
}

/** Nhóm công đoạn của item có ít nhất 1 nhánh phòng (track>=2) hay không. */
function sectionHasBranchesV2(item) {
    const key = sectionGroupKeyV2(item);
    return items.some((it) => it.type === 'section' && !it.isVirtual
        && sectionGroupKeyV2(it) === key && sectionTrackInfoV2(it).track >= 2);
}

/** Chỉ công đoạn sản xuất THẬT (stage_code số 1-7, không hệ thống/khóa) mới được tách nhánh —
 *  đúng dải mà getRoomsConfig() phía server đã công nhận là công đoạn sản xuất. */
function isRoomTrackEligibleV2(item) {
    if (!item || item.type !== 'section' || item.isVirtual || item.locked) return false;
    const code = Number(item.stage_code);
    return Number.isInteger(code) && code >= 1 && code <= 7;
}

/** Tách 1 section thành nhánh phòng mới — nhánh mới RỖNG, người soạn tự viết/copy nội dung. */
function splitSectionIntoRoomTrackV2(item) {
    if (BOOT.isReadOnly || BOOT.isExecutionMode) return;
    if (!isRoomTrackEligibleV2(item)) return;
    const { category, stageCode } = sectionTrackInfoV2(item);

    // Đếm số nhánh hiện có (kể cả gốc) cùng stage_code để suy ra số thứ tự nhánh mới.
    const siblingCount = items.filter((it) => it.type === 'section' && !it.isVirtual
        && String(it.stage_code) === String(stageCode)).length;
    const nextTrack = siblingCount + 1;
    const newSectionId = `${category}_${nextTrack}_${stageCode}`;

    // Hỏi tên riêng cho nhánh mới ngay lúc tách — số thứ tự (VD "3.2.") được gắn TỰ ĐỘNG
    // bởi updateHeadingNumbersV2() nên label chỉ cần phần tên, không cần ghép "Nhánh N".
    const name = window.prompt('Tên tiêu đề cho nhánh phòng mới:', '');
    if (name === null) return; // hủy tách nhánh nếu bấm Cancel

    const newItem = {
        id: newBlockId(),
        type: 'section',
        section_id: newSectionId,
        stage_code: item.stage_code,
        label: name.trim() || `Nhánh ${nextTrack}`,
        locked: false,
        isVirtual: false,
        dirty: true,
    };

    // Chèn ngay sau phần tử CUỐI CÙNG hiện thuộc nhóm nhánh cùng stage_code này (quét từ vị trí
    // section vừa bấm trở đi), để các nhánh luôn nằm liền nhau trong tài liệu.
    const startIdx = items.indexOf(item);
    let insertIdx = startIdx + 1;
    for (let i = startIdx + 1; i < items.length; i++) {
        const it = items[i];
        if (it.type === 'section' && !it.isVirtual && String(it.stage_code) !== String(stageCode)) break;
        insertIdx = i + 1;
    }

    saveDocState();
    items.splice(insertIdx, 0, newItem);
    markDirty();
    renderDocument();
}

/** Đổi tên (label hiển thị) của 1 section — dùng cho cả section gốc lẫn nhánh vừa tách. */
function renameSectionV2(item) {
    if (BOOT.isReadOnly || BOOT.isExecutionMode) return;
    if (!item || item.locked || item.isVirtual) return;
    const name = window.prompt('Đổi tên phân đoạn:', item.label || '');
    if (name === null) return; // hủy
    const trimmed = name.trim();
    if (!trimmed || trimmed === item.label) return;
    saveDocState();
    item.label = trimmed;
    item.dirty = true;
    markDirty();
    renderDocument();
}

/** Bật/tắt ngắt trang riêng cho section — dùng khi section (thường là section cha không có
 *  nội dung, chỉ giữ tiêu đề chung cho các nhánh con) không cần chiếm 1 trang riêng: section
 *  tiếp theo sẽ nối liền ngay bên dưới thay vì luôn tự sang trang mới. */
function toggleSectionPageBreakV2(item) {
    if (BOOT.isReadOnly || BOOT.isExecutionMode) return;
    if (!item || item.locked || item.isVirtual) return;
    saveDocState();
    item.noPageBreak = !item.noPageBreak;
    item.dirty = true;
    markDirty();
    renderDocument();
}

/**
 * Đổi vị trí 1 khối lên/xuống TRONG CÙNG SECTION (không cho vượt ranh giới section).
 * @param {string} id
 * @param {number} dir -1 = lên, 1 = xuống
 */
function moveBlock(id, dir) {
    if (BOOT.isReadOnly || BOOT.isExecutionMode) return;
    const idx = items.findIndex((i) => i.id === id);
    if (idx === -1) return;
    const item = items[idx];
    if (item.locked || item.isVirtual) return;

    const targetIdx = idx + dir;
    const target = items[targetIdx];
    if (!target || target.type === 'section' || target.locked || target.isVirtual) return;
    if (target.section_id !== item.section_id) return;

    unmountEditor();
    saveDocState();
    item.dirty = true;
    target.dirty = true;
    items[idx] = target;
    items[targetIdx] = item;
    markDirty();
    renderDocument();

    const el = document.querySelector(`.v2-block[data-id="${id}"]`);
    if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function renderTable(item, fieldsOverride) {
    const wrap = document.createElement('div');
    wrap.className = 'v2-block v2-table-wrap';
    wrap.setAttribute('data-id', item.id);

    const table = document.createElement('table');
    table.className = 'v2-table';

    // <colgroup> để bề rộng cột đúng theo cấu hình kể cả khi hàng đầu có colspan
    // (table-layout: fixed chỉ nhìn hàng đầu tiên nếu thiếu colgroup -> bảng mất cân đối)
    if (Array.isArray(item.columns) && item.columns.length > 0) {
        const colgroup = document.createElement('colgroup');
        item.columns.forEach((c) => {
            const col = document.createElement('col');
            if (c.width && c.width !== 'auto') col.style.width = c.width;
            colgroup.appendChild(col);
        });
        table.appendChild(colgroup);
    }

    // "Thêm dòng (Cấp 2)" CHỈ hoạt động ở Chạy thử/thực thi — bấm ở Thiết kế sẽ đổi
    // luôn cấu trúc bảng GỐC của template (không phải bản nháp), nên ẩn hẳn nút này khi
    // đang thiết kế để không ai lỡ tay sửa cấu trúc gốc qua đường này. Khi hiện, chèn
    // thêm 1 cột RIÊNG (không thuộc item.cols/columns, chỉ để hiện nút xoá hàng động).
    const showAddRowUI = !!item.canAddRows && BOOT.isExecutionMode && !BOOT.isReadOnly;

    if (!item.hideHeader && Array.isArray(item.columns)) {
        const thead = document.createElement('thead');
        const tr = document.createElement('tr');
        item.columns.forEach((c) => {
            const th = document.createElement('th');
            th.innerHTML = c.label || '';
            if (c.width) th.style.width = c.width;
            const s = c.style || {};
            if (s.backgroundColor) th.style.backgroundColor = s.backgroundColor;
            if (s.textAlign) th.style.textAlign = s.textAlign;
            tr.appendChild(th);
        });
        if (showAddRowUI) {
            const th = document.createElement('th');
            th.className = 'v2-table-extra-col';
            tr.appendChild(th);
        }
        thead.appendChild(tr);
        table.appendChild(thead);
    }

    const tbody = document.createElement('tbody');
    for (let r = 0; r < (item.rows || 0); r++) {
        if (!item.data || !item.data[r]) continue;
        const tr = document.createElement('tr');
        tr.dataset.rowIdx = r;
        if (item.rowHeights && item.rowHeights[r]) tr.style.height = item.rowHeights[r];

        for (let c = 0; c < (item.cols || 0); c++) {
            let cell = item.data[r][c];
            if (!cell || typeof cell !== 'object') {
                cell = item.data[r][c] = { content: cell || '', rs: 1, cs: 1, hidden: false };
            }
            if (cell.hidden) continue;

            const td = document.createElement('td');
            td.dataset.row = r;
            td.dataset.col = c;
            if ((cell.rs || 1) > 1) td.rowSpan = cell.rs;
            if ((cell.cs || 1) > 1) td.colSpan = cell.cs;
            if (cell.backgroundColor) td.style.backgroundColor = cell.backgroundColor;
            if (cell.color) td.style.color = cell.color;
            if (cell.textAlign) td.style.textAlign = cell.textAlign;
            if (cell.fontWeight) td.style.fontWeight = cell.fontWeight;
            if (cell.fontStyle) td.style.fontStyle = cell.fontStyle;
            if (cell.verticalAlign) td.style.verticalAlign = cell.verticalAlign;
            if (item.columns && item.columns[c] && item.columns[c].width) td.style.width = item.columns[c].width;
            // Viền bảng — luôn set cả 4 cạnh (inline) để đè viền mặc định của CSS.
            // Cạnh chưa tuỳ biến -> dùng viền mặc định; cạnh đã xoá lưu dạng 'hidden'
            // nên thắng khi border-collapse (none sẽ thua viền của ô kề bên).
            td.style.borderTop = cell.borderTop || TABLE_DEFAULT_BORDER;
            td.style.borderBottom = cell.borderBottom || TABLE_DEFAULT_BORDER;
            td.style.borderLeft = cell.borderLeft || TABLE_DEFAULT_BORDER;
            td.style.borderRight = cell.borderRight || TABLE_DEFAULT_BORDER;

            const inner = document.createElement('div');
            inner.className = 'v2-editable v2-cell';
            inner.innerHTML = decorateBadges(cell.content || '', fieldsOverride);
            td.appendChild(inner);

            if (!BOOT.isReadOnly && !BOOT.isExecutionMode && !item.locked) {
                inner.addEventListener('click', (e) => {
                    if (activeHost === inner) return;
                    if (hasNativeTextSelectionV2()) return; // vừa quét chọn chữ để copy — giữ selection, không mở editor
                    e.stopPropagation();
                    const cellRef = cell;
                    const rowIdx = r, colIdx = c;
                    mountEditor(inner,
                        () => cellRef.content || '',
                        (html) => { cellRef.content = html; item.dirty = true; markDirty(); },
                        { kind: 'cell', item, r: rowIdx, c: colIdx }, { x: e.clientX, y: e.clientY });
                });
            }
            tr.appendChild(td);
        }
        if (showAddRowUI) {
            const extraTd = document.createElement('td');
            extraTd.className = 'v2-table-extra-col';
            const isDynamicRow = item.data[r][0] && item.data[r][0].is_dynamic;
            if (isDynamicRow) { // showAddRowUI đã đảm bảo đang ở Chạy thử/thực thi
                const delBtn = document.createElement('button');
                delBtn.type = 'button';
                delBtn.className = 'v2-table-row-del-btn';
                delBtn.title = 'Xóa dòng';
                delBtn.innerHTML = '<i class="fas fa-times-circle"></i>';
                delBtn.addEventListener('mousedown', (e) => e.preventDefault());
                delBtn.addEventListener('click', (e) => { e.stopPropagation(); deleteRuntimeTableRowV2(item, r); });
                extraTd.appendChild(delBtn);
            }
            tr.appendChild(extraTd);
        }
        tbody.appendChild(tr);
    }
    table.appendChild(tbody);
    attachTableResizers(item, table);
    wrap.appendChild(table);
    attachTableSizerV2(item, wrap, table); // núm kéo góc dưới-phải: resize cả bảng (như Word)
    selection.decorateTable(item, wrap, table); // nút ⊕ + gutter chọn hàng/cột

    if (showAddRowUI) {
        const addWrap = document.createElement('div');
        addWrap.className = 'v2-table-addrow-wrap';
        const addBtn = document.createElement('button');
        addBtn.type = 'button';
        addBtn.className = 'v2-table-addrow-btn';
        addBtn.innerHTML = '<i class="fas fa-plus me-1"></i> Thêm dòng';
        addBtn.addEventListener('mousedown', (e) => e.preventDefault());
        addBtn.addEventListener('click', (e) => { e.stopPropagation(); addRuntimeTableRowV2(item); });
        addWrap.appendChild(addBtn);
        wrap.appendChild(addWrap);
    }

    // Thiết kế: đánh dấu góc bảng đã bật "Thêm dòng (Cấp 2)" để không cần mở menu
    // chuột phải mới biết — xem toggle ở buildTableContextMenuItemsV2 (fa-plus-circle).
    if (!!item.canAddRows && !BOOT.isExecutionMode && !BOOT.isReadOnly) {
        const capBadge = document.createElement('div');
        capBadge.className = 'v2-table-canaddrows-badge';
        capBadge.title = 'Bảng đã bật "Cho phép người dùng cấp 2 thêm dòng" lúc Chạy thử/thực thi';
        capBadge.innerHTML = '<i class="fas fa-plus-circle"></i>';
        wrap.appendChild(capBadge);
    }
    return wrap;
}

/** Kéo cạnh phải ô để đổi bề rộng cột, kéo cạnh dưới để đổi chiều cao hàng (như V1) */
function attachTableResizers(item, table) {
    if (BOOT.isReadOnly || BOOT.isExecutionMode || item.locked) return;
    const cols = table.querySelectorAll('colgroup col');

    const startDrag = (e, handle, onMove, onEnd) => {
        e.preventDefault();
        e.stopPropagation();
        handle.classList.add('resizing');
        const move = (ev) => onMove(ev);
        const up = (ev) => {
            document.removeEventListener('mousemove', move);
            document.removeEventListener('mouseup', up);
            handle.classList.remove('resizing');
            onEnd(ev);
        };
        document.addEventListener('mousemove', move);
        document.addEventListener('mouseup', up);
    };

    // --- Bề rộng CỘT: resizer ở cạnh phải MỌI Ô (như Word) — ô colspan chỉnh cột cuối nó phủ ---
    const addColResizer = (cell, targetCol) => {
        if (!item.columns || !item.columns[targetCol]) return;
        const rz = document.createElement('div');
        rz.className = 'v2-col-resizer';
        rz.addEventListener('mousedown', (e) => {
            // "ĐÓNG BĂNG" bề rộng HIỆN TẠI của MỌI cột trước khi kéo: bảng dùng
            // table-layout:fixed, cột nào CHƯA từng được set width cố định sẽ được
            // trình duyệt tự chia đều phần còn lại — set width cho 1 mình cột đang
            // kéo sẽ khiến TỔNG width lệch khỏi 100% và trình duyệt tự co giãn LẠI
            // TOÀN BỘ cột theo tỉ lệ để khớp 100%, làm mọi cạnh khác cũng trôi theo.
            cols.forEach((c, i) => {
                if (!c) return;
                const w = c.getBoundingClientRect().width;
                c.style.width = w + 'px';
                if (item.columns[i] && (!item.columns[i].width || item.columns[i].width === 'auto')) {
                    item.columns[i].width = Math.round(w) + 'px';
                }
            });

            const colEl = cols[targetCol];
            // Cột LIỀN KỀ bên phải: cùng "cặp đôi" hấp thụ đúng phần bù trừ (giống Word/
            // Excel — kéo 1 đường phân cách cột chỉ đổi 2 cột 2 bên nó, KHÔNG đụng tới
            // các cột khác lẫn tổng bề rộng bảng). Nếu đây là cột cuối (không có cột kế
            // tiếp, tức đang kéo mép ngoài phải của bảng) thì không có cặp để bù trừ —
            // giữ hành vi cũ (chỉ đổi 1 cột, mép ngoài bảng vẫn cố định do width:100%).
            const nextColEl = cols[targetCol + 1] || null;
            const startX = e.clientX;
            const startW = (colEl && colEl.getBoundingClientRect().width) || cell.getBoundingClientRect().width;
            const startWNext = nextColEl ? nextColEl.getBoundingClientRect().width : null;
            saveDocState();

            const clampDelta = (rawDelta) => {
                let delta = Math.max(30 - startW, rawDelta); // cột đang kéo tối thiểu 30px
                if (startWNext !== null) delta = Math.min(delta, startWNext - 30); // cột kế tối thiểu 30px
                return delta;
            };

            startDrag(e, rz,
                (ev) => {
                    const delta = clampDelta(ev.clientX - startX);
                    if (colEl) colEl.style.width = (startW + delta) + 'px';
                    if (nextColEl) nextColEl.style.width = (startWNext - delta) + 'px';
                },
                (ev) => {
                    const delta = clampDelta(ev.clientX - startX);
                    item.columns[targetCol].width = Math.round(startW + delta) + 'px';
                    if (nextColEl && item.columns[targetCol + 1]) {
                        item.columns[targetCol + 1].width = Math.round(startWNext - delta) + 'px';
                    }
                    item.dirty = true;
                    markDirty();
                });
        });
        cell.appendChild(rz);
    };
    const headRow = table.querySelector('thead tr');
    if (headRow) {
        let colCursor = 0;
        Array.from(headRow.children).forEach((cell) => {
            const span = cell.colSpan || 1;
            addColResizer(cell, colCursor + span - 1);
            colCursor += span;
        });
    }
    table.querySelectorAll('tbody td[data-col]').forEach((td) => {
        const c = parseInt(td.dataset.col, 10);
        addColResizer(td, c + (td.colSpan || 1) - 1);
    });

    // --- Chiều cao HÀNG: resizer ở cạnh dưới MỌI Ô (như Word) — ô rowspan chỉnh hàng cuối nó phủ ---
    const trByIdx = {};
    table.querySelectorAll('tbody tr').forEach((tr) => { trByIdx[tr.dataset.rowIdx] = tr; });
    table.querySelectorAll('tbody td[data-row]').forEach((td) => {
        const r = parseInt(td.dataset.row, 10);
        const targetRow = r + (td.rowSpan || 1) - 1;
        const tr = trByIdx[targetRow];
        if (!tr) return;
        const rz = document.createElement('div');
        rz.className = 'v2-row-resizer';
        rz.addEventListener('mousedown', (e) => {
            const startY = e.clientY;
            const startH = tr.getBoundingClientRect().height;
            saveDocState();
            startDrag(e, rz,
                (ev) => { tr.style.height = Math.max(18, startH + ev.clientY - startY) + 'px'; },
                (ev) => {
                    const h = Math.max(18, Math.round(startH + ev.clientY - startY));
                    if (!Array.isArray(item.rowHeights)) item.rowHeights = [];
                    item.rowHeights[targetRow] = h + 'px';
                    item.dirty = true;
                    markDirty();
                });
        });
        td.appendChild(rz);
    });
}

/* =========================================================
 * 2. EDITOR-ON-DEMAND — 1 instance TipTap duy nhất
 * ========================================================= */
/**
 * Làm sạch HTML dán từ MS Word: bỏ conditional comment, thẻ Office (<o:p>),
 * style mso-*, class Mso* — nếu không ProseMirror có thể parse ra rỗng.
 */
function cleanWordHtml(html) {
    if (!html) return html;
    // Đổi ký hiệu Wingdings/Symbol -> Unicode TRƯỚC khi gỡ style
    // (cần đọc font-family trong style để biết ký tự thuộc font biểu tượng nào).
    html = convertSymbolFontsHtmlV2(html);
    return html
        .replace(/<!--\[if[\s\S]*?<!\[endif\]-->/gi, '')   // conditional comments
        .replace(/<!--[\s\S]*?-->/g, '')                    // comment thường
        .replace(/<\/?o:p[^>]*>/gi, '')                     // thẻ Office namespace
        .replace(/<style[^>]*>[\s\S]*?<\/style>/gi, '')     // block <style> + toàn bộ CSS bên trong
        .replace(/<xml[^>]*>[\s\S]*?<\/xml>/gi, '')         // block <xml> của Office
        .replace(/<\/?(meta|link)[^>]*>/gi, '')             // thẻ meta/link đơn lẻ
        .replace(/\sclass="?Mso[a-zA-Z]+"?/g, '')           // class MsoNormal...
        // Chỉ gỡ TỪNG khai báo mso-* trong style, GIỮ NGUYÊN màu chữ/nền/cỡ chữ... của dữ liệu gốc
        // (trước đây xoá cả thuộc tính style nếu chứa mso-* -> mất luôn color đi kèm).
        .replace(/\sstyle="([^"]*)"/gi, (m, css) => {
            const kept = css.split(';').map((s) => s.trim()).filter((d) => d && !/^mso-/i.test(d));
            return kept.length ? ` style="${kept.join('; ')}"` : '';
        })
        .replace(/<span[^>]*>\s*<\/span>/g, '');            // span rỗng còn sót
}

/* ---------- Chuyển ký tự font biểu tượng (Wingdings/Symbol) -> Unicode ---------- */
// Word chèn checkbox/ký hiệu bằng CHỮ CÁI THƯỜNG + font Wingdings/Symbol
// (vd: 'o' font Wingdings = ô vuông ☐). V2 luôn dùng font mặc định (fontFamily tắt)
// nên nếu không chuyển sang Unicode thật thì ký hiệu biến thành chữ 'o', '¨'...
const V2_SYMBOL_FONT_MAPS = {
    wingdings: {
        'J': '☺', 'L': '☹',
        'l': '●', 'm': '○', 'n': '■', 'o': '☐', 'p': '❒', 'q': '❑',
        'u': '◆', 'v': '❖', '§': '▪', '¨': '☐',
        'ß': '←', 'à': '→', 'á': '↑', 'â': '↓',
        'û': '✗', 'ü': '✓', 'ý': '☒', 'þ': '☑',
    },
    'wingdings 2': {
        'O': '✗', 'P': '✓', 'Q': '✓', 'R': '☑', 'S': '☒',
    },
    symbol: {
        'a': 'α', 'b': 'β', 'g': 'γ', 'd': 'δ', 'e': 'ε', 'z': 'ζ', 'h': 'η', 'q': 'θ',
        'i': 'ι', 'k': 'κ', 'l': 'λ', 'm': 'μ', 'n': 'ν', 'x': 'ξ', 'o': 'ο', 'p': 'π',
        'r': 'ρ', 's': 'σ', 'V': 'ς', 't': 'τ', 'u': 'υ', 'f': 'φ', 'c': 'χ', 'y': 'ψ', 'w': 'ω',
        'G': 'Γ', 'D': 'Δ', 'Q': 'Θ', 'L': 'Λ', 'X': 'Ξ', 'P': 'Π', 'S': 'Σ',
        'F': 'Φ', 'Y': 'Ψ', 'W': 'Ω',
        '£': '≤', '³': '≥', '¹': '≠', '»': '≈', '´': '×', '¸': '÷', 'Ö': '√', '°': '°', '±': '±',
    },
};

/** Duyệt DOM đã parse, đổi text bên trong phần tử mang font biểu tượng, rồi gỡ font đó */
function convertSymbolFontsV2(root) {
    root.querySelectorAll('[style*="font-family" i], font[face]').forEach((el) => {
        const fam = ((el.style && el.style.fontFamily) || el.getAttribute('face') || '')
            .toLowerCase().replace(/["']/g, '').split(',')[0].trim();
        const key = ['wingdings 2', 'wingdings 3', 'wingdings', 'webdings', 'symbol']
            .find((k) => fam === k);
        if (!key) return;
        const map = V2_SYMBOL_FONT_MAPS[key === 'wingdings 3' ? 'wingdings' : key] || {};
        const walker = document.createTreeWalker(el, NodeFilter.SHOW_TEXT);
        let node;
        while ((node = walker.nextNode())) {
            node.nodeValue = Array.from(node.nodeValue).map((ch) => map[ch] || ch).join('');
        }
        if (el.style) el.style.fontFamily = '';
        el.removeAttribute('face');
    });
}

/** Bọc cleanWordHtml: chỉ tốn thêm 1 lần parse DOM khi HTML có nhắc tới font biểu tượng */
function convertSymbolFontsHtmlV2(html) {
    if (!html || !/wingdings|webdings|symbol/i.test(html)) return html;
    const doc = new DOMParser().parseFromString(html, 'text/html');
    convertSymbolFontsV2(doc.body);
    return doc.body.innerHTML;
}

/**
 * Gỡ màu chữ / màu nền INLINE bên trong HTML của ô.
 * Chữ dán từ Word thường mang sẵn <span style="color:...">, mà style inline
 * luôn thắng màu đặt ở cấp ô (td) — nên khi người dùng đổi màu cả ô phải gỡ
 * màu inline cũ đi thì màu mới mới nhìn thấy được.
 */
function stripInlineColorV2(html, prop) {
    if (!html) return html;
    const re = prop === 'color' ? /^color\s*:/i : /^background(-color)?\s*:/i;
    let out = String(html).replace(/\sstyle="([^"]*)"/gi, (m, css) => {
        const kept = css.split(';').map((s) => s.trim()).filter((d) => d && !re.test(d));
        return kept.length ? ` style="${kept.join('; ')}"` : '';
    });
    if (prop === 'color') {
        out = out.replace(/(<font\b[^>]*?)\s+color="[^"]*"/gi, '$1'); // <font color="..">
    } else {
        out = out.replace(/<\/?mark\b[^>]*>/gi, ''); // bỏ highlight cũ để nền ô thắng
    }
    return out;
}

/* ---------- Các helper dán bảng/lưới (tái tạo hành vi V1) ---------- */

/** Parse <table> HTML thành lưới ảo giữ rowspan/colspan/màu nền/căn lề (giống V1) */
function parseHtmlTableToGrid(tableEl) {
    const rows = Array.from(tableEl.querySelectorAll('tr'));
    if (rows.length === 0) return null;
    const grid = [];
    let colCount = 0;
    rows.forEach((tr, rIdx) => {
        if (!grid[rIdx]) grid[rIdx] = [];
        let cIdx = 0;
        tr.querySelectorAll('td, th').forEach((cell) => {
            while (grid[rIdx][cIdx]) cIdx++;
            const rs = parseInt(cell.getAttribute('rowspan')) || 1;
            const cs = parseInt(cell.getAttribute('colspan')) || 1;
            const style = cell.style || {};
            grid[rIdx][cIdx] = {
                content: cleanWordHtml(cell.innerHTML),
                rs, cs, hidden: false,
                backgroundColor: cell.getAttribute('bgcolor') || style.backgroundColor || '',
                color: style.color || '',
                textAlign: cell.getAttribute('align') || style.textAlign || '',
                fontWeight: style.fontWeight || '',
                fontStyle: style.fontStyle || '',
            };
            for (let dr = 0; dr < rs; dr++) {
                for (let dc = 0; dc < cs; dc++) {
                    if (dr === 0 && dc === 0) continue;
                    if (!grid[rIdx + dr]) grid[rIdx + dr] = [];
                    grid[rIdx + dr][cIdx + dc] = { content: '', rs: 1, cs: 1, hidden: true };
                }
            }
            cIdx += cs;
            if (cIdx > colCount) colCount = cIdx;
        });
    });
    for (let r = 0; r < rows.length; r++) {
        if (!grid[r]) grid[r] = [];
        while (grid[r].length < colCount) grid[r].push({ content: '', rs: 1, cs: 1, hidden: false });
    }

    // Word thường kèm 1 "cột ma" trống ở cuối bảng (artifact định dạng) — cắt các cột cuối
    // mà MỌI ô đều trống (không chữ, không badge/ảnh) và không bị ô nào span phủ sang.
    const isBlankCol = (cIdx) => grid.every((row) => {
        const c = row[cIdx];
        if (!c) return true;
        if (c.hidden) return false; // bị rowspan/colspan từ ô khác phủ sang -> cột đang được dùng
        const html = c.content || '';
        const text = html.replace(/<[^>]*>/g, '').replace(/&nbsp;/gi, ' ').trim();
        return !text && !/data-field-id|<img/i.test(html);
    });
    while (colCount > 1 && isBlankCol(colCount - 1)) {
        grid.forEach((row) => { if (row.length >= colCount) row.length = colCount - 1; });
        colCount--;
    }
    return grid;
}

/** Text nhiều dòng / có tab (Excel) -> lưới ô text */
function plainTextToGrid(text) {
    const rows = String(text).replace(/\r\n/g, '\n').replace(/\n+$/, '').split('\n');
    if (rows.length <= 1 && !rows[0]?.includes('\t')) return null;
    return rows.map((row) => row.split('\t').map((t) => ({
        content: esc(t), rs: 1, cs: 1, hidden: false,
    })));
}

/** Trải lưới vào bảng hiện có bắt đầu từ ô (startR, startC) — tự nới hàng/cột như V1 */
function spreadGridIntoTable(item, startR, startC, grid) {
    grid.forEach((rowData, rOff) => {
        const rIndex = startR + rOff;
        while (rIndex >= item.rows) {
            item.rows++;
            item.data.push(Array.from({ length: item.cols }, () => ({ content: '', rs: 1, cs: 1, hidden: false })));
        }
        rowData.forEach((cellObj, cOff) => {
            const cIndex = startC + cOff;
            while (cIndex >= item.cols) {
                item.cols++;
                item.columns.push({ label: 'Cột ' + item.cols, type: 'text', width: 'auto' });
                item.data.forEach((row) => row.push({ content: '', rs: 1, cs: 1, hidden: false }));
            }
            item.data[rIndex][cIndex] = {
                content: cellObj.content || '',
                rs: cellObj.rs || 1, cs: cellObj.cs || 1, hidden: cellObj.hidden || false,
                backgroundColor: cellObj.backgroundColor || '',
                color: cellObj.color || '',
                textAlign: cellObj.textAlign || '',
                fontWeight: cellObj.fontWeight || '',
                fontStyle: cellObj.fontStyle || '',
            };
        });
    });
    item.dirty = true;
}

/** Tạo block bảng mới từ lưới ảo (dùng khi dán bảng ra ngoài / vào block văn bản) */
function gridToTableBlock(grid, sectionId) {
    const rows = grid.length;
    const cols = grid[0] ? grid[0].length : 0;
    return {
        id: newBlockId(), type: 'table', label: 'Bảng (Pasted)',
        rows, cols,
        columns: Array.from({ length: cols }, (_, i) => ({ label: 'Cột ' + (i + 1), type: 'text', width: 'auto' })),
        data: grid,
        rowHeights: [], borderMode: 'visible', hideHeader: true,
        section_id: sectionId, dirty: true,
    };
}

/** Tạo block bảng mới là bản sao đầy đủ của 1 bảng đã copy (Ctrl+C khi chọn cả bảng qua nút ⊕) */
function fullTableClipboardToBlock(fullTable, sectionId) {
    return {
        id: newBlockId(), type: 'table', label: 'Bảng (Copy)',
        rows: fullTable.rows, cols: fullTable.cols,
        columns: JSON.parse(JSON.stringify(fullTable.columns || [])),
        data: JSON.parse(JSON.stringify(fullTable.data || [])),
        rowHeights: JSON.parse(JSON.stringify(fullTable.rowHeights || [])),
        borderMode: fullTable.borderMode, hideHeader: fullTable.hideHeader,
        section_id: sectionId, dirty: true,
    };
}

/** Đổi kiểu chữ CẢ KHỐI văn bản: 'p' | 'h1' | 'h2' | 'h3' — gọi từ menu chuột phải,
 *  không cần đặt con trỏ vào khối như dropdown "Kiểu văn bản" trên toolbar. */
function setBlockTextTagV2(item, tag) {
    if (BOOT.isReadOnly || BOOT.isExecutionMode || item.locked || item.isVirtual) return;
    unmountEditor(); // chốt nội dung đang gõ dở (nếu chính khối này đang mở editor)
    saveDocState();
    const tmp = document.createElement('div');
    tmp.innerHTML = item.content || '<p></p>';
    let changed = false;
    Array.from(tmp.children).forEach((child) => {
        if (/^(P|H[1-6])$/.test(child.tagName)) {
            const rep = document.createElement(tag);
            rep.innerHTML = child.innerHTML;
            if (child.style && child.style.textAlign) rep.style.textAlign = child.style.textAlign;
            child.replaceWith(rep);
            changed = true;
        }
    });
    if (!changed) tmp.innerHTML = `<${tag}>${tmp.innerHTML}</${tag}>`;
    item.content = tmp.innerHTML;
    item.dirty = true;
    markDirty();
    renderDocument();
}

/** Cắt các đoạn <p> RỖNG ở cuối nội dung (hàng thừa dưới tiêu đề/đoạn văn khi đóng editor). */
function trimTrailingEmptyParagraphsV2(html) {
    const out = String(html || '').replace(/(?:<p[^>]*>(?:\s|&nbsp;|<br[^>]*\/?>)*<\/p>\s*)+$/gi, '');
    return out || '<p></p>'; // khối trống hoàn toàn vẫn giữ 1 đoạn để còn click vào gõ
}

/** Đang có vùng chữ bôi đen (native)? — dùng để KHÔNG mở editor ngay sau thao tác
 *  quét chọn chữ, kẻo mountEditor thay DOM làm mất vùng chọn trước khi kịp Ctrl+C. */
function hasNativeTextSelectionV2() {
    const s = window.getSelection && window.getSelection();
    return !!(s && !s.isCollapsed && String(s).trim());
}

/** Suy ra (getHTML/setHTML/context) cho 1 phần tử .v2-editable — dùng để tự mount
 *  editor đúng ô bảng hoặc khối văn bản tĩnh đang chứa vùng chọn của người dùng. */
function resolveEditableContextV2(editableEl) {
    const td = editableEl.closest('td[data-row][data-col]');
    const wrap = editableEl.closest('.v2-table-wrap[data-id]');
    if (td && wrap) {
        const item = items.find((i) => i.id === wrap.getAttribute('data-id'));
        const r = parseInt(td.dataset.row, 10), c = parseInt(td.dataset.col, 10);
        const cellRef = item && item.data && item.data[r] && item.data[r][c];
        if (!item || !cellRef) return null;
        return {
            getHTML: () => cellRef.content || '',
            setHTML: (html) => { cellRef.content = html; item.dirty = true; markDirty(); },
            context: { kind: 'cell', item, r, c },
        };
    }
    const blockEl = editableEl.closest('.v2-block[data-id]');
    if (blockEl) {
        const item = items.find((i) => i.id === blockEl.getAttribute('data-id'));
        if (!item || item.type !== 'static-text') return null;
        return {
            getHTML: () => item.content || '',
            setHTML: (html) => { item.content = html; item.dirty = true; markDirty(); },
            context: { kind: 'text', item },
        };
    }
    return null;
}

/** Đếm số ký tự VĂN BẢN THUẦN (bỏ qua thẻ) từ đầu container tới (node, offset) của Range. */
function textOffsetInContainerV2(container, node, offset) {
    let count = 0;
    const walker = document.createTreeWalker(container, NodeFilter.SHOW_TEXT);
    let n;
    while ((n = walker.nextNode())) {
        if (n === node) return count + offset;
        count += n.nodeValue.length;
    }
    return count; // không khớp node nào (offset trỏ vào phần tử, không phải text) -> coi như cuối
}

/** Tìm vị trí ProseMirror ứng với 1 mốc ký tự (tính theo văn bản thuần, cùng cách đếm
 *  với textOffsetInContainerV2) trong tài liệu doc vừa parse lại từ CÙNG HTML gốc. */
function pmPosFromTextOffsetV2(doc, targetOffset) {
    let count = 0;
    let found = null;
    doc.descendants((node, nodePos) => {
        if (found !== null) return false;
        if (node.isText) {
            const len = node.text.length;
            if (count + len >= targetOffset) {
                found = nodePos + (targetOffset - count);
                return false;
            }
            count += len;
        }
        return true;
    });
    return found !== null ? found : doc.content.size;
}

/**
 * Bôi đen (native selection) trên 1 ô/đoạn văn bản CHƯA MỞ EDITOR (đang hiển thị tĩnh)
 * rồi bấm Delete/Backspace hoặc Ctrl+X: DOM tĩnh KHÔNG PHẢI contenteditable, nên trình
 * duyệt không có cách nào tự xoá/cắt được nội dung — phím tắt coi như vô tác dụng, dù
 * vùng bôi đen vẫn hiện trên màn hình. Xử lý: tự MỞ editor đúng ô/khối đó, TÁI TẠO lại
 * đúng vùng chọn (quy về mốc ký tự thuần rồi tìm vị trí ProseMirror tương ứng — vì DOM
 * cũ bị thay hoàn toàn khi mount nên không thể tái sử dụng Range cũ), rồi mới xoá (và
 * copy vào clipboard hệ điều hành trước nếu là Cắt).
 * Trả về true nếu đã xử lý xong, false nếu không áp dụng được (nhường lại cho nơi gọi).
 */
function convertStaticSelectionToEditableV2(action) {
    const sel = window.getSelection && window.getSelection();
    if (!sel || sel.isCollapsed || sel.rangeCount === 0 || !String(sel).trim()) return false;
    const range = sel.getRangeAt(0);
    const anchorEl = range.commonAncestorContainer.nodeType === 1
        ? range.commonAncestorContainer
        : range.commonAncestorContainer.parentElement;
    const editableEl = anchorEl && anchorEl.closest ? anchorEl.closest('.v2-editable') : null;
    if (!editableEl || editableEl.closest('.v2-editing')) return false; // đã là editor sống -> không phải trường hợp này

    const resolved = resolveEditableContextV2(editableEl);
    if (!resolved) return false;

    const selectedText = String(sel);
    const startOffset = textOffsetInContainerV2(editableEl, range.startContainer, range.startOffset);
    const endOffset = textOffsetInContainerV2(editableEl, range.endContainer, range.endOffset);
    const from = Math.min(startOffset, endOffset), to = Math.max(startOffset, endOffset);
    if (from === to) return false;

    if (action === 'cut' && navigator.clipboard?.writeText) {
        navigator.clipboard.writeText(selectedText).catch(() => { /* trình duyệt/quyền hạn chặn -> vẫn xoá bình thường */ });
    }

    mountEditor(editableEl, resolved.getHTML, resolved.setHTML, resolved.context);
    if (!activeEditor) return false;
    const pmFrom = pmPosFromTextOffsetV2(activeEditor.state.doc, from);
    const pmTo = pmPosFromTextOffsetV2(activeEditor.state.doc, to);
    activeEditor.chain().focus().setTextSelection({ from: pmFrom, to: pmTo }).deleteSelection().run();
    return true;
}

function mountEditor(host, getHTML, setHTML, context = null, clickPos = null) {
    if (BOOT.isReadOnly || BOOT.isExecutionMode) return; // Chạy thử: không cho sửa cấu trúc
    selection.clearCells(); // mở editor = thoát chế độ chọn ô
    hideInsertCaretV2();    // đã có con trỏ thật trong editor, bỏ con trỏ điểm chèn
    unmountEditor(); // đóng editor đang mở (ghi dữ liệu về trước)

    host.classList.add('v2-editing');
    host.innerHTML = '';

    activeEditor = new Editor({
        element: host,
        extensions: buildExtensions(),
        content: getHTML() || '<p></p>',
        autofocus: false,
        onUpdate: () => { markDirty(); if (headingNumberingOnV2()) updateHeadingNumbersV2(); },
        editorProps: {
            // Dọn rác Word trước khi ProseMirror parse nội dung dán vào
            transformPastedHTML: (html) => cleanWordHtml(html),
            // Tái tạo hành vi dán của V1: bảng/lưới Excel không dán inline mà
            // trải vào lưới bảng (nếu đang ở ô) hoặc thành block bảng mới.
            handlePaste: (view, event) => handleEditorPaste(event, context),
        },
    });
    activeHost = host;
    lastEditorArgs = { host, getHTML, setHTML, context }; // nhớ để mở lại khi cần chèn
    const editorInstance = activeEditor;

    // Đặt con trỏ đúng vị trí người dùng vừa click (thay vì luôn nhảy về cuối nội dung):
    // nội dung tĩnh bị thay bằng DOM của TipTap ở trên nên phải tự tính lại vị trí theo tọa độ click.
    let focusPos = 'end';
    if (clickPos) {
        const coordsPos = editorInstance.view.posAtCoords({ left: clickPos.x, top: clickPos.y });
        if (coordsPos) focusPos = coordsPos.pos;
    }
    editorInstance.commands.focus(focusPos);
    activeSync = () => {
        let html = editorInstance.getHTML() || '';
        html = html.replace(/ ProseMirror-selectednode/g, '').replace(/ProseMirror-selectednode/g, '').replace(/ class=""/g, '');
        setHTML(trimTrailingEmptyParagraphsV2(html));
    };
    refreshToolbarState();

    activeEditor.on('selectionUpdate', refreshToolbarState);
    // Mỗi transaction ProseMirror có thể vẽ lại node (mất data-hnum) -> đánh số lại luôn
    activeEditor.on('transaction', () => {
        refreshToolbarState();
        if (headingNumberingOnV2()) updateHeadingNumbersV2();
    });
    if (headingNumberingOnV2()) updateHeadingNumbersV2(); // mount xong: áp số lên DOM editor mới
    // Mount xoá sạch DOM của host -> highlight trong khối này biến mất. Vẽ lại phần còn lại
    // và bỏ đường nối tới khối đang sửa (TipTap quản lý DOM của nó, không chèn span vào được).
    cmtApplyAll();
}

/**
 * Xử lý dán bên trong editor đang mở.
 * Trả về true = đã tự xử lý (TipTap bỏ qua); false = để TipTap dán inline.
 */
function handleEditorPaste(event, context) {
    const cb = event.clipboardData || window.clipboardData;
    if (!cb) return false;

    // 0) Dán ẢNH trực tiếp từ clipboard (screenshot Windows/Snipping Tool, copy 1 icon/ký
    //    hiệu nhỏ nằm giữa câu chữ như Word...) — trình duyệt đưa ảnh vào clipboard dưới
    //    dạng FILE (image/png...), không phải text/html nên các nhánh bên dưới không thấy
    //    được. Đọc file -> dataURL (FileReader bất đồng bộ) rồi chèn NGAY tại con trỏ dạng
    //    node INLINE v2InlineImage (khác v2Image của nút toolbar "Chèn hình ảnh": node đó
    //    LUÔN là block riêng 1 dòng, canh giữa, % theo bề rộng cột — không hợp cho 1 icon
    //    nhỏ nằm lẫn trong câu). Kích thước mặc định lấy theo ẢNH GỐC (giữ nguyên tỉ lệ,
    //    giới hạn tối đa ~32px cao bằng khoảng 1 dòng chữ) để giống hệt cách Word chèn icon
    //    inline — người dùng kéo tay cầm để phóng to/thu nhỏ thêm nếu cần. Chặn hành vi dán
    //    mặc định ngay lập tức (return true) vì việc đọc file xảy ra sau đó, không đồng bộ.
    const imageItem = context && (context.kind === 'text' || context.kind === 'cell')
        ? Array.from(cb.items || []).find((it) => it.kind === 'file' && it.type && it.type.startsWith('image/'))
        : null;
    if (imageItem) {
        const file = imageItem.getAsFile();
        if (file) {
            const editor = activeEditor;
            const reader = new FileReader();
            reader.onload = () => {
                if (!editor || editor.isDestroyed) return;
                const dataUrl = reader.result;
                const probe = new Image();
                probe.onload = () => {
                    if (!editor || editor.isDestroyed) return;
                    const naturalW = probe.naturalWidth || 32;
                    const naturalH = probe.naturalHeight || 32;
                    const maxH = 32; // ~ cao 1 dòng chữ, giống icon inline trong Word
                    const width = naturalH > maxH ? Math.round(naturalW * (maxH / naturalH)) : naturalW;
                    editor.chain().focus().insertContent({ type: 'v2InlineImage', attrs: { src: dataUrl, width } }).run();
                };
                probe.onerror = () => {
                    if (!editor || editor.isDestroyed) return;
                    editor.chain().focus().insertContent({ type: 'v2InlineImage', attrs: { src: dataUrl, width: 24 } }).run();
                };
                probe.src = dataUrl;
            };
            reader.readAsDataURL(file);
            return true;
        }
    }

    const htmlData = cb.getData('text/html');
    const plainText = cb.getData('text/plain');

    // 1) Tìm bảng trong clipboard
    let tables = [];
    let docBody = null;
    if (htmlData) {
        const doc = new DOMParser().parseFromString(cleanWordHtml(htmlData), 'text/html');
        docBody = doc.body;
        tables = Array.from(docBody.querySelectorAll('table'));
    }

    // 2) Đang ở Ô BẢNG: bảng hoặc lưới Excel -> trải vào lưới từ ô này (như V1)
    if (context && context.kind === 'cell') {
        let grid = null;
        if (tables.length > 0) grid = parseHtmlTableToGrid(tables[0]);
        if (!grid && !htmlData && plainText) grid = plainTextToGrid(plainText);

        if (grid && (grid.length > 1 || (grid[0] && grid[0].length > 1))) {
            const { item, r, c } = context;
            // Defer: không destroy editor ngay trong event handler của ProseMirror
            setTimeout(() => {
                unmountEditor(); // chốt nội dung đang gõ trước khi trải lưới
                saveDocState();
                spreadGridIntoTable(item, r, c, grid);
                markDirty();
                renderDocument();
            }, 0);
            return true;
        }
        return false; // 1 ô đơn / text thường -> dán inline bình thường
    }

    // 3) Đang ở BLOCK VĂN BẢN mà clipboard có bảng: phần văn bản dán inline,
    //    mỗi bảng tách thành BLOCK BẢNG MỚI ngay dưới block hiện tại (như V1 global paste)
    if (context && context.kind === 'text' && tables.length > 0 && docBody) {
        const { item } = context;
        const editor = activeEditor;

        // Gom phần KHÔNG phải bảng để dán inline
        let inlineHtml = '';
        const walk = (node) => {
            if (node.nodeName === 'TABLE') return;
            if (node.nodeType === Node.ELEMENT_NODE && node.querySelector && node.querySelector('table')) {
                Array.from(node.childNodes).forEach(walk);
            } else if (node.nodeType === Node.ELEMENT_NODE) {
                inlineHtml += node.outerHTML;
            } else if (node.nodeType === Node.TEXT_NODE) {
                inlineHtml += esc(node.textContent);
            }
        };
        Array.from(docBody.childNodes).forEach(walk);

        if (inlineHtml.trim() && editor) {
            editor.chain().focus().insertContent(inlineHtml).run();
        }

        const newBlocks = tables.map((t) => parseHtmlTableToGrid(t)).filter(Boolean)
            .map((g) => gridToTableBlock(g, item.section_id));
        if (newBlocks.length > 0) {
            // Defer: không destroy editor ngay trong event handler của ProseMirror
            setTimeout(() => {
                const baseIdx = items.indexOf(item);
                unmountEditor(); // ghi phần inline vừa chèn về items trước khi re-render
                saveDocState();
                items.splice(baseIdx + 1, 0, ...newBlocks);
                markDirty();
                renderDocument();
            }, 0);
        }
        return true;
    }

    // 4) Đang ở BLOCK VĂN BẢN, clipboard hệ thống không có bảng/nội dung, nhưng lần
    //    Ctrl+C gần nhất là "chọn cả bảng" (nút ⊕, xem selection.js) -> dán bản sao
    //    bảng đó thành BLOCK BẢNG MỚI ngay dưới block hiện tại (khắc phục: chọn bảng
    //    -> click ra chỗ khác -> Ctrl+V không có tác dụng gì).
    if (context && context.kind === 'text' && tables.length === 0 && !htmlData && !plainText) {
        const fullTable = selection.getFullTableClipboard();
        if (fullTable) {
            const { item } = context;
            setTimeout(() => {
                const baseIdx = items.indexOf(item);
                unmountEditor();
                saveDocState();
                items.splice(baseIdx + 1, 0, fullTableClipboardToBlock(fullTable, item.section_id));
                markDirty();
                renderDocument();
            }, 0);
            return true;
        }
    }

    return false; // mặc định: TipTap dán inline (đã qua cleanWordHtml)
}

function unmountEditor() {
    if (!activeEditor) return;
    const host = activeHost;
    const sync = activeSync;
    const editor = activeEditor;
    try { const s = editor.state.selection; lastEditorSel = { from: s.from, to: s.to }; } catch (e) { /* giữ vị trí cũ */ }
    activeEditor = null; activeHost = null; activeSync = null;

    // BỌC TRY/CATCH: trước đây nếu sync() (ghi HTML về items) ném lỗi vì bất kỳ lý do gì,
    // toàn bộ hàm dừng NGAY — editor.destroy() và host.innerHTML KHÔNG BAO GIỜ chạy tới,
    // để lại 1 editor "ma": activeEditor đã null (coi như đã đóng) nhưng NodeView cũ vẫn
    // còn sống nguyên trong DOM, badge vẫn hiển thị trên màn hình dù dữ liệu chưa được ghi
    // — đúng triệu chứng "biến hiện trên màn hình nhưng Lưu báo thiếu dữ liệu". Giờ dù bước
    // nào lỗi, vẫn ĐẢM BẢO destroy() + reset DOM chạy, và lỗi được log rõ để truy vết.
    try {
        sync();
    } catch (err) {
        console.error('[V2] unmountEditor: sync() (ghi dữ liệu về ô/khối) thất bại — nội dung vừa sửa có thể bị mất:', err);
    }
    let html = '';
    try {
        html = editor.getHTML() || '';
    } catch (err) {
        console.error('[V2] unmountEditor: editor.getHTML() thất bại:', err);
    }
    html = html.replace(/ ProseMirror-selectednode/g, '').replace(/ProseMirror-selectednode/g, '').replace(/ class=""/g, '');
    html = trimTrailingEmptyParagraphsV2(html); // bỏ hàng rỗng thừa cuối khối
    try { editor.destroy(); } catch (err) { console.error('[V2] unmountEditor: editor.destroy() thất bại:', err); }
    host.classList.remove('v2-editing');
    host.innerHTML = decorateBadges(html); // trở lại chế độ xem tĩnh — LUÔN chạy dù các bước trên lỗi
    cmtApplyAll(); // chữ vừa sửa có thể làm lệch neo -> dò lại và vẽ lại highlight
    refreshToolbarState();
}

// Click ra ngoài vùng đang edit -> unmount (giữ nguyên khi click vào toolbar/panel/modal —
// các modal chèn Symbol/Công thức/Hình ảnh/Document Property cần activeEditor còn sống
// để insertContent() vào đúng vị trí con trỏ đã chọn trước khi mở modal).
document.addEventListener('mousedown', (e) => {
    if (!activeHost) return;
    if (activeHost.contains(e.target)) return;
    if (e.target.closest('#v2-toolbar') || e.target.closest('#v2-field-panel') || e.target.closest('.modal')
        || e.target.closest('#v2-context-menu')) return; // menu chuột phải: giữ editor + con trỏ để "Dán biến" đúng vị trí
    // Rail bình luận: unmount sẽ render lại rail ngay trong lượt mousedown, phần tử dưới con trỏ
    // bị thay mới nên sự kiện click (Trả lời/Xóa) không bao giờ bắn ra. Giữ editor sống.
    if (e.target.closest('#v2-cmt-rail')) return;
    unmountEditor();
});

/* =========================================================
 * 3. TOOLBAR
 * ========================================================= */
function cmd(fn, name = null) {
    // Không có editor nhưng đang CHỌN nhiều ô -> áp dụng cấp Ô (batch)
    if (!activeEditor && selection.hasCells()) {
        if (name) applyCellCommand(name);
        return;
    }
    if (!activeEditor) return;
    fn(activeEditor.chain().focus());
}

/** Áp dụng lệnh toolbar cho TẤT CẢ ô đang chọn (prop cấp ô, tương thích V1) */
function applyCellCommand(name) {
    const anchor = selection.getAnchorCell();
    if (!anchor) return;
    const alignMap = { 'align-left': 'left', 'align-center': 'center', 'align-right': 'right', 'align-justify': 'justify' };
    if (alignMap[name]) {
        selection.applyToCells((cell) => { cell.textAlign = alignMap[name]; });
    } else if (name === 'bold') {
        const on = anchor.cell.fontWeight !== 'bold';
        selection.applyToCells((cell) => { cell.fontWeight = on ? 'bold' : ''; });
    } else if (name === 'italic') {
        const on = anchor.cell.fontStyle !== 'italic';
        selection.applyToCells((cell) => { cell.fontStyle = on ? 'italic' : ''; });
    } else if (name === 'clear-format') {
        selection.applyToCells((cell) => {
            cell.fontWeight = ''; cell.fontStyle = ''; cell.textAlign = ''; cell.backgroundColor = '';
        });
    } else {
        showToast('info', 'Định dạng này chỉ áp dụng khi gõ trong ô. Click vào 1 ô để dùng.');
    }
}

const TOOLBAR_ACTIONS = {
    bold: (ch) => ch.toggleBold().run(),
    italic: (ch) => ch.toggleItalic().run(),
    underline: (ch) => ch.toggleUnderline().run(),
    strike: (ch) => ch.toggleStrike().run(),
    subscript: (ch) => ch.toggleSubscript().run(),
    superscript: (ch) => ch.toggleSuperscript().run(),
    bullet: (ch) => ch.toggleBulletList().run(),
    ordered: (ch) => ch.toggleOrderedList().run(),
    'align-left': (ch) => ch.setTextAlign('left').run(),
    'align-center': (ch) => ch.setTextAlign('center').run(),
    'align-right': (ch) => ch.setTextAlign('right').run(),
    'align-justify': (ch) => ch.setTextAlign('justify').run(),
    indent: (ch) => ch.sinkListItem('listItem').run(),
    outdent: (ch) => ch.liftListItem('listItem').run(),
    'clear-format': (ch) => ch.unsetAllMarks().clearNodes().run(),
    undo: (ch) => ch.undo().run(),
    redo: (ch) => ch.redo().run(),
};

/** Undo/Redo 2 cấp: đang gõ -> history của TipTap; ngoài editor -> history tài liệu */
function smartUndo() {
    if (activeEditor && activeEditor.can().undo()) activeEditor.chain().focus().undo().run();
    else undoDoc();
}
function smartRedo() {
    if (activeEditor && activeEditor.can().redo()) activeEditor.chain().focus().redo().run();
    else redoDoc();
}

// Các lệnh áp dụng được ở CẤP Ô khi chọn nhiều ô (không cần editor)
const CELL_LEVEL_CMDS = ['bold', 'italic', 'align-left', 'align-center', 'align-right', 'align-justify', 'clear-format'];

function refreshToolbarState() {
    const bar = document.getElementById('v2-toolbar');
    if (!bar) return;
    const hasCellSel = selection.hasCells();
    bar.querySelectorAll('[data-cmd], [data-fmt]').forEach((el) => {
        // Undo/Redo luôn bật (hoạt động cả ở cấp tài liệu khi không gõ trong editor)
        const cmdName = el.getAttribute && el.getAttribute('data-cmd');
        if (cmdName === 'undo' || cmdName === 'redo') { el.disabled = false; return; }
        if (cmdName === 'merge-cells') { el.disabled = !(hasCellSel && selection.cellCount() >= 2); return; }
        if (cmdName === 'split-cell') {
            const a = selection.getAnchorCell();
            el.disabled = !(hasCellSel && selection.cellCount() === 1 && a && ((a.cell.rs || 1) > 1 || (a.cell.cs || 1) > 1));
            return;
        }
        if (!activeEditor && hasCellSel && cmdName && CELL_LEVEL_CMDS.includes(cmdName)) { el.disabled = false; return; }
        el.disabled = !activeEditor;
    });

    // Phản chiếu trạng thái từ Ô ANCHOR khi chọn nhiều ô (không có editor)
    if (!activeEditor && hasCellSel) {
        const a = selection.getAnchorCell();
        const boldBtn = document.querySelector('[data-cmd="bold"]');
        if (boldBtn) boldBtn.classList.toggle('active', !!(a && a.cell.fontWeight === 'bold'));
        const italicBtn = document.querySelector('[data-cmd="italic"]');
        if (italicBtn) italicBtn.classList.toggle('active', !!(a && a.cell.fontStyle === 'italic'));
        ['left', 'center', 'right', 'justify'].forEach((al) => {
            const btn = document.querySelector(`[data-cmd="align-${al}"]`);
            if (btn) btn.classList.toggle('active', !!(a && a.cell.textAlign === al));
        });
        return;
    }
    if (!activeEditor) return;

    const map = {
        bold: 'bold', italic: 'italic', underline: 'underline', strike: 'strike',
        subscript: 'subscript', superscript: 'superscript',
        bullet: 'bulletList', ordered: 'orderedList',
    };
    Object.entries(map).forEach(([k, name]) => {
        const btn = document.querySelector(`[data-cmd="${k}"]`);
        if (btn) btn.classList.toggle('active', activeEditor.isActive(name));
    });
    ['left', 'center', 'right', 'justify'].forEach((a) => {
        const btn = document.querySelector(`[data-cmd="align-${a}"]`);
        if (btn) btn.classList.toggle('active', activeEditor.isActive({ textAlign: a }));
    });

    // Đồng bộ các dropdown theo vị trí con trỏ
    const styleSel = document.getElementById('v2-sel-style');
    if (styleSel) {
        let v = 'p';
        for (const lv of [1, 2, 3]) if (activeEditor.isActive('heading', { level: lv })) v = 'h' + lv;
        styleSel.value = v;
    }
    const ts = activeEditor.getAttributes('textStyle') || {};
    const sizeSel = document.getElementById('v2-sel-size');
    if (sizeSel) sizeSel.value = ts.fontSize || '';
    const lhSel = document.getElementById('v2-sel-lineheight');
    if (lhSel) lhSel.value = ts.lineHeight || '';
}

/**
 * Gắn 1 ô chọn màu (<input type="color">) hoạt động bền vững với hộp màu của HĐH.
 * editorFn(chain, value): áp lên vùng chữ đang chọn trong editor.
 * cellFn(value): áp khi đang chọn ô (không có editor).
 */
function bindColorControlV2(inputId, editorFn, cellFn) {
    const input = document.getElementById(inputId);
    if (!input) return;
    let snap = null; // ngữ cảnh chụp lúc bấm swatch

    const grab = () => {
        if (activeEditor) {
            const s = activeEditor.state.selection;
            snap = { editor: activeEditor, from: s.from, to: s.to };
        } else if (selection.hasCells()) {
            snap = { cells: true };
        } else {
            snap = null;
        }
    };
    // mousedown/focus xảy ra TRƯỚC khi editor mất focus -> vùng chọn còn nguyên.
    input.addEventListener('mousedown', grab);
    input.addEventListener('focus', grab);

    const apply = () => {
        const val = input.value;
        if (snap && snap.editor) {
            // Khôi phục đúng vùng chữ đã chọn (chống thu gọn khi mở hộp màu) rồi tô.
            editorFn(snap.editor.chain().focus().setTextSelection({ from: snap.from, to: snap.to }), val).run();
        } else if (snap && snap.cells) {
            cellFn(val);
        }
    };
    input.addEventListener('input', apply);   // xem trước khi kéo trong hộp màu
    input.addEventListener('change', apply);  // chốt khi đóng hộp màu
}

/** Gắn sự kiện cho các dropdown/màu của toolbar định dạng */
function initFormatControls() {
    const on = (id, fn) => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('change', () => { if (activeEditor) fn(el.value); });
        return el;
    };

    on('v2-sel-style', (v) => {
        const ch = activeEditor.chain().focus();
        if (v === 'p') ch.setParagraph().run();
        else ch.toggleHeading({ level: parseInt(v.slice(1), 10) }).run();
    });
    on('v2-sel-size', (v) => {
        const ch = activeEditor.chain().focus();
        v ? ch.setFontSize(v).run() : ch.unsetFontSize().run();
    });
    on('v2-sel-lineheight', (v) => {
        const ch = activeEditor.chain().focus();
        v ? ch.setLineHeight(v).run() : ch.unsetLineHeight().run();
    });

    // Màu chữ / Màu nền — hoạt động ở CẢ 2 chế độ:
    //  • Đang mở editor: tô cho VÙNG CHỮ đang chọn.
    //  • Đang chọn Ô (không editor): tô cho cả ô (prop cấp ô, tương thích V1).
    // Mở hộp màu của hệ điều hành làm editor mất focus và có thể thu gọn vùng chọn,
    // nên phải CHỤP ngữ cảnh (editor + from/to, hoặc cờ chọn ô) ngay lúc bấm swatch,
    // rồi khôi phục đúng vùng đó khi áp màu.
    bindColorControlV2('v2-color-text',
        (chain, val) => chain.setColor(val),
        (val) => selection.applyToCells((cell) => {
            cell.color = val;
            // Gỡ màu inline (chữ dán từ Word) để màu cấp ô thắng
            cell.content = stripInlineColorV2(cell.content, 'color');
        }));
    bindColorControlV2('v2-color-highlight',
        (chain, val) => chain.setHighlight({ color: val }),
        (val) => selection.applyToCells((cell) => {
            cell.backgroundColor = val;
            cell.content = stripInlineColorV2(cell.content, 'background');
        }));
    const hlOff = document.getElementById('v2-btn-unhighlight');
    if (hlOff) {
        hlOff.addEventListener('mousedown', (e) => e.preventDefault());
        hlOff.addEventListener('click', () => {
            if (activeEditor) { activeEditor.chain().focus().unsetHighlight().run(); return; }
            if (selection.hasCells()) selection.applyToCells((cell) => {
                cell.backgroundColor = '';
                cell.content = stripInlineColorV2(cell.content, 'background');
            });
        });
    }
    // Căn nội dung ô bảng theo chiều dọc (trên/giữa/dưới)
    [['v2-btn-valign-top', 'top'], ['v2-btn-valign-middle', 'middle'], ['v2-btn-valign-bottom', 'bottom']]
        .forEach(([bid, v]) => {
            const b = document.getElementById(bid);
            if (!b) return;
            b.addEventListener('mousedown', (e) => e.preventDefault()); // giữ selection/focus hiện tại
            b.addEventListener('click', () => applyVAlignToolbarV2(v));
        });

    // Đánh số tiêu đề tự động nhiều cấp (toggle, lưu trong docProperties)
    const hnumBtn = document.getElementById('v2-btn-heading-num');
    if (hnumBtn) {
        hnumBtn.addEventListener('mousedown', (e) => e.preventDefault());
        hnumBtn.addEventListener('click', toggleHeadingNumberingV2);
    }
}

/* =========================================================
 * 3a-bis. SAO CHÉP ĐỊNH DẠNG (Format Painter — kiểu Word/Google Docs)
 *   Click 1 lần: dùng 1 lần rồi tự tắt. Double-click (trong 400ms): khoá,
 *   dán liên tục tới khi bấm lại nút hoặc nhấn Esc.
 *   - Có editor đang mở + đang bôi đen text -> lấy/dán định dạng KÝ TỰ
 *     (bold/italic/underline/strike/sub/sup/màu chữ/cỡ chữ/giãn dòng/
 *     highlight/căn lề) qua TipTap mark commands.
 *   - Không có editor, đang chọn 1 ô bảng -> lấy/dán định dạng Ô
 *     (nền/căn lề/đậm/nghiêng/căn dọc) qua selection.applyToCells.
 * ========================================================= */
let painterActive = false;
let painterLocked = false;
let painterFmt = null;
let painterClickTimer = null;

function captureFormatPainterV2() {
    if (activeEditor && !activeEditor.state.selection.empty) {
        const attrs = activeEditor.getAttributes('textStyle') || {};
        const align = ['left', 'center', 'right', 'justify'].find((a) => activeEditor.isActive({ textAlign: a })) || null;
        const hlActive = activeEditor.isActive('highlight');
        return {
            type: 'text',
            bold: activeEditor.isActive('bold'),
            italic: activeEditor.isActive('italic'),
            underline: activeEditor.isActive('underline'),
            strike: activeEditor.isActive('strike'),
            subscript: activeEditor.isActive('subscript'),
            superscript: activeEditor.isActive('superscript'),
            color: attrs.color || null,
            fontSize: attrs.fontSize || null,
            lineHeight: attrs.lineHeight || null,
            highlight: hlActive ? (activeEditor.getAttributes('highlight').color || null) : null,
            textAlign: align,
        };
    }
    if (!activeEditor && selection.hasCells() && selection.cellCount() === 1) {
        const a = selection.getAnchorCell();
        if (a) {
            return {
                type: 'cell',
                backgroundColor: a.cell.backgroundColor || '',
                textAlign: a.cell.textAlign || '',
                fontWeight: a.cell.fontWeight || '',
                fontStyle: a.cell.fontStyle || '',
                verticalAlign: a.cell.verticalAlign || '',
            };
        }
    }
    return null;
}

function applyFormatPainterV2(fmt) {
    if (!fmt) return false;
    if (fmt.type === 'text') {
        if (!activeEditor || activeEditor.state.selection.empty) return false;
        const ch = activeEditor.chain().focus().unsetAllMarks();
        if (fmt.bold) ch.setBold();
        if (fmt.italic) ch.setItalic();
        if (fmt.underline) ch.setUnderline();
        if (fmt.strike) ch.setStrike();
        if (fmt.subscript) ch.setSubscript();
        if (fmt.superscript) ch.setSuperscript();
        if (fmt.color) ch.setColor(fmt.color);
        if (fmt.fontSize) ch.setFontSize(fmt.fontSize);
        if (fmt.lineHeight) ch.setLineHeight(fmt.lineHeight);
        if (fmt.highlight) ch.setHighlight({ color: fmt.highlight });
        fmt.textAlign ? ch.setTextAlign(fmt.textAlign) : ch.unsetTextAlign();
        ch.run();
        return true;
    }
    if (fmt.type === 'cell' && selection.hasCells()) {
        selection.applyToCells((cell) => {
            cell.backgroundColor = fmt.backgroundColor || '';
            cell.textAlign = fmt.textAlign || '';
            cell.fontWeight = fmt.fontWeight || '';
            cell.fontStyle = fmt.fontStyle || '';
            cell.verticalAlign = fmt.verticalAlign || '';
        });
        return true;
    }
    return false;
}

function enableFormatPainterV2() {
    painterActive = true;
    document.getElementById('v2-btn-format-painter')?.classList.add('active');
    document.body.classList.add('v2-format-painter-active');
    document.addEventListener('keydown', painterKeydownV2);
    document.addEventListener('mouseup', painterMouseUpV2);
}

function disableFormatPainterV2() {
    painterActive = false;
    painterLocked = false;
    painterFmt = null;
    document.getElementById('v2-btn-format-painter')?.classList.remove('active');
    document.body.classList.remove('v2-format-painter-active');
    document.removeEventListener('keydown', painterKeydownV2);
    document.removeEventListener('mouseup', painterMouseUpV2);
}

function painterKeydownV2(e) {
    if (e.key === 'Escape') disableFormatPainterV2();
}

function painterMouseUpV2(e) {
    if (e.target.closest('#v2-btn-format-painter')) return; // bấm nút -> để click handler xử lý bật/tắt
    // Đợi 1 tick để TipTap/selection cập nhật xong sau khi thả chuột
    setTimeout(() => {
        let applied = false;
        if (painterFmt?.type === 'text' && activeEditor && !activeEditor.state.selection.empty) {
            applied = applyFormatPainterV2(painterFmt);
            if (applied) markDirty();
        } else if (painterFmt?.type === 'cell' && selection.hasCells()) {
            applied = applyFormatPainterV2(painterFmt);
        }
        if (applied && !painterLocked) disableFormatPainterV2();
    }, 0);
}

function toggleFormatPainterV2() {
    if (painterActive) { disableFormatPainterV2(); return; }

    if (painterClickTimer) {
        clearTimeout(painterClickTimer);
        painterClickTimer = null;
        painterLocked = true;
    } else {
        painterLocked = false;
        painterClickTimer = setTimeout(() => { painterClickTimer = null; }, 400);
    }

    painterFmt = captureFormatPainterV2();
    if (!painterFmt) {
        showToast('info', 'Hãy bôi đen văn bản hoặc chọn 1 ô để sao chép định dạng');
        return;
    }
    enableFormatPainterV2();
    showToast('info', painterLocked ? '🖌️ Đã khoá — nhấn Esc để dừng' : '🖌️ Đã lấy định dạng — chọn đích để dán');
}

function initFormatPainterV2() {
    const btn = document.getElementById('v2-btn-format-painter');
    if (!btn) return;
    btn.addEventListener('mousedown', (e) => e.preventDefault());
    btn.addEventListener('click', toggleFormatPainterV2);
}

/* =========================================================
 * 3b. GỘP / TÁCH Ô — thao tác trên vùng ô đang chọn
 *     (port mergeSelectedCells từ V1 table_advanced, model V2
 *      sạch hơn: rs/cs/hidden first-class, không offset header)
 * ========================================================= */

/** Ô "trống" = không có badge/ảnh/icon và text chỉ toàn khoảng trắng (giữ nguyên logic V1) */
function isCellContentEmpty(html) {
    if (!html || typeof html !== 'string') return true;
    const tmp = document.createElement('div');
    tmp.innerHTML = html;
    if (tmp.querySelector('img, .ebmr-field-badge, i.fas, i.far, i.fal')) return false;
    return (tmp.textContent || '').trim() === '';
}

function mergeSelectedCellsV2() {
    if (BOOT.isReadOnly || BOOT.isExecutionMode) return;
    const targets = selection.getCells();
    if (targets.length < 2) {
        showToast('info', 'Hãy chọn từ 2 ô trở lên để gộp (kéo hoặc Ctrl+Click).');
        return;
    }
    const item = targets[0].item;
    if (targets.some((t) => t.item !== item)) {
        showToast('warning', 'Chỉ gộp được các ô trong CÙNG một bảng.');
        return;
    }
    if (item.locked) return;

    saveDocState();

    // Bounding box tính cả span sẵn có của từng ô được chọn
    let minR = Infinity, maxR = -1, minC = Infinity, maxC = -1;
    targets.forEach(({ r, c, cell }) => {
        minR = Math.min(minR, r);
        maxR = Math.max(maxR, r + (cell.rs || 1) - 1);
        minC = Math.min(minC, c);
        maxC = Math.max(maxC, c + (cell.cs || 1) - 1);
    });
    if (minR > maxR || minC > maxC) return;

    if (!item.data[minR][minC] || typeof item.data[minR][minC] !== 'object') {
        item.data[minR][minC] = { content: '', rs: 1, cs: 1, hidden: false };
    }
    const mainCell = item.data[minR][minC];
    mainCell.rs = maxR - minR + 1;
    mainCell.cs = maxC - minC + 1;
    mainCell.hidden = false;

    // Gom nội dung không trống (badge/ảnh không được mất) rồi ẩn các ô phủ
    const combinedParts = [];
    for (let r = minR; r <= maxR; r++) {
        if (!item.data[r]) continue;
        for (let c = minC; c <= maxC; c++) {
            if (r === minR && c === minC) continue;
            if (!item.data[r][c] || typeof item.data[r][c] !== 'object') {
                item.data[r][c] = { content: '', rs: 1, cs: 1, hidden: false };
            }
            const cellHtml = item.data[r][c].content || '';
            if (!item.data[r][c].hidden && !isCellContentEmpty(cellHtml)) combinedParts.push(cellHtml);
            item.data[r][c].hidden = true;
            item.data[r][c].rs = 1;
            item.data[r][c].cs = 1;
            item.data[r][c].content = '';
        }
    }
    if (combinedParts.length > 0) {
        mainCell.content = isCellContentEmpty(mainCell.content)
            ? combinedParts.join(' ')
            : mainCell.content + ' ' + combinedParts.join(' ');
    } else if (isCellContentEmpty(mainCell.content)) {
        mainCell.content = '';
    }

    item.dirty = true;
    markDirty();
    renderDocument();
    // Chọn lại ô vừa gộp
    const td = document.querySelector(`.v2-table-wrap[data-id="${item.id}"] td[data-row="${minR}"][data-col="${minC}"]`);
    if (td) selection.setRange(td, td);
}

function splitCellV2() {
    if (BOOT.isReadOnly || BOOT.isExecutionMode) return;
    const targets = selection.getCells();
    if (targets.length !== 1) {
        showToast('info', 'Hãy chọn đúng 1 ô đã gộp để tách.');
        return;
    }
    const { item, r, c, cell } = targets[0];
    if (item.locked) return;
    if ((cell.rs || 1) <= 1 && (cell.cs || 1) <= 1) {
        showToast('info', 'Ô này chưa được gộp — không cần tách.');
        return;
    }

    saveDocState();
    const rs = cell.rs || 1, cs = cell.cs || 1;
    for (let rr = r; rr < r + rs; rr++) {
        if (!item.data[rr]) continue;
        for (let cc = c; cc < c + cs; cc++) {
            if (!item.data[rr][cc] || typeof item.data[rr][cc] !== 'object') {
                item.data[rr][cc] = { content: '', rs: 1, cs: 1, hidden: false };
            }
            item.data[rr][cc].hidden = false;
            item.data[rr][cc].rs = 1;
            item.data[rr][cc].cs = 1;
        }
    }
    // Nội dung ở lại ô anchor (trên-trái)

    item.dirty = true;
    markDirty();
    renderDocument();
    // Chọn lại vùng vừa tách
    const wrapSel = `.v2-table-wrap[data-id="${item.id}"]`;
    const tdA = document.querySelector(`${wrapSel} td[data-row="${r}"][data-col="${c}"]`);
    const tdB = document.querySelector(`${wrapSel} td[data-row="${r + rs - 1}"][data-col="${c + cs - 1}"]`);
    if (tdA && tdB) selection.setRange(tdA, tdB);
}

/* =========================================================
 * 3-ter. THAO TÁC BẢNG KIỂU WORD — chèn/xóa hàng-cột, phân bố
 *        đều, AutoFit, căn dọc ô (gọi từ menu chuột phải)
 * ========================================================= */
function canEditTableV2(item) {
    return item && !BOOT.isReadOnly && !BOOT.isExecutionMode && !item.locked && !item.isVirtual;
}

/** Sao chép các thuộc tính ĐỊNH DẠNG (không copy nội dung/span) từ ô mẫu sang ô mới */
function cloneCellStyleV2(src) {
    const cell = { content: '', rs: 1, cs: 1, hidden: false };
    if (src && typeof src === 'object') {
        ['backgroundColor', 'textAlign', 'fontWeight', 'fontStyle', 'verticalAlign'].forEach((k) => {
            if (src[k]) cell[k] = src[k];
        });
    }
    return cell;
}

/** Chèn 1 hàng mới tại vị trí idx (hàng mới chiếm index idx). Span dọc vắt qua đường chèn được nới thêm 1. */
function insertTableRowV2(item, idx) {
    if (!canEditTableV2(item)) return;
    unmountEditor();
    saveDocState();

    const cols = item.cols || 0;
    const srcRow = item.data[idx - 1] || item.data[idx] || [];
    const newRow = Array.from({ length: cols }, (_, c) => cloneCellStyleV2(srcRow[c]));

    // Anchor phía trên có rowspan phủ QUA đường chèn -> rs+1, ô mới trong vùng phủ bị ẩn
    for (let r = 0; r < idx; r++) {
        if (!item.data[r]) continue;
        for (let c = 0; c < cols; c++) {
            const cell = item.data[r][c];
            if (!cell || typeof cell !== 'object' || cell.hidden) continue;
            const rs = cell.rs || 1, cs = cell.cs || 1;
            if (r + rs - 1 >= idx) {
                cell.rs = rs + 1;
                for (let cc = c; cc < c + cs && cc < cols; cc++) {
                    newRow[cc] = { content: '', rs: 1, cs: 1, hidden: true };
                }
            }
        }
    }

    item.data.splice(idx, 0, newRow);
    item.rows = (item.rows || 0) + 1;
    if (Array.isArray(item.rowHeights) && item.rowHeights.length) {
        item.rowHeights.splice(idx, 0, item.rowHeights[Math.max(0, idx - 1)] || '');
    }
    item.dirty = true;
    selection.clearCells();
    markDirty();
    renderDocument();
}

/** Chèn 1 cột mới tại vị trí idx. Span ngang vắt qua đường chèn được nới thêm 1. */
function insertTableColV2(item, idx) {
    if (!canEditTableV2(item)) return;
    unmountEditor();
    saveDocState();

    const rows = item.rows || 0;
    if (!Array.isArray(item.columns)) item.columns = [];

    // Anchor bên trái có colspan phủ QUA đường chèn -> cs+1; mọi hàng nó phủ dọc đều phải ẩn ô mới
    const hiddenRows = new Set();
    for (let r = 0; r < rows; r++) {
        if (!item.data[r]) continue;
        for (let c = 0; c < idx; c++) {
            const cell = item.data[r][c];
            if (!cell || typeof cell !== 'object' || cell.hidden) continue;
            const rs = cell.rs || 1, cs = cell.cs || 1;
            if (c + cs - 1 >= idx) {
                cell.cs = cs + 1;
                for (let rr = r; rr < r + rs && rr < rows; rr++) hiddenRows.add(rr);
            }
        }
    }

    for (let r = 0; r < rows; r++) {
        if (!item.data[r]) continue;
        const src = item.data[r][idx - 1] || item.data[r][idx];
        const cell = hiddenRows.has(r) ? { content: '', rs: 1, cs: 1, hidden: true } : cloneCellStyleV2(src);
        item.data[r].splice(idx, 0, cell);
    }
    const srcCol = item.columns[idx - 1] || item.columns[idx] || {};
    item.columns.splice(idx, 0, { label: 'Cột mới', type: 'text', width: srcCol.width || 'auto' });
    item.cols = (item.cols || 0) + 1;
    item.dirty = true;
    selection.clearCells();
    markDirty();
    renderDocument();
}

/** Xóa hàng idx. Anchor rowspan trên hàng bị xóa được dời xuống hàng kế tiếp, span vắt qua bị co lại 1. */
function deleteTableRowV2(item, idx) {
    if (!canEditTableV2(item)) return;
    if ((item.rows || 0) <= 1) { showToast('info', 'Bảng chỉ còn 1 hàng — hãy dùng "Xóa bảng".'); return; }
    unmountEditor();
    saveDocState();

    const cols = item.cols || 0;
    // Anchor phía trên có span phủ qua hàng bị xóa: rs-1
    for (let r = 0; r < idx; r++) {
        if (!item.data[r]) continue;
        for (let c = 0; c < cols; c++) {
            const cell = item.data[r][c];
            if (!cell || typeof cell !== 'object' || cell.hidden) continue;
            const rs = cell.rs || 1;
            if (r + rs - 1 >= idx) cell.rs = rs - 1;
        }
    }
    // Anchor NẰM TRÊN hàng bị xóa có rs>1: dời anchor xuống hàng kế (giữ nội dung + span còn lại)
    for (let c = 0; c < cols; c++) {
        const cell = item.data[idx] && item.data[idx][c];
        if (!cell || typeof cell !== 'object' || cell.hidden) continue;
        if ((cell.rs || 1) > 1 && item.data[idx + 1]) {
            item.data[idx + 1][c] = { ...cell, rs: (cell.rs || 1) - 1, hidden: false };
        }
    }

    item.data.splice(idx, 1);
    item.rows--;
    if (Array.isArray(item.rowHeights)) item.rowHeights.splice(idx, 1);
    item.dirty = true;
    selection.clearCells();
    markDirty();
    renderDocument();
}

/** Xóa cột idx. Anchor colspan trên cột bị xóa được dời sang cột kế, span vắt qua bị co lại 1. */
function deleteTableColV2(item, idx) {
    if (!canEditTableV2(item)) return;
    if ((item.cols || 0) <= 1) { showToast('info', 'Bảng chỉ còn 1 cột — hãy dùng "Xóa bảng".'); return; }
    unmountEditor();
    saveDocState();

    const rows = item.rows || 0;
    for (let r = 0; r < rows; r++) {
        if (!item.data[r]) continue;
        for (let c = 0; c < idx; c++) {
            const cell = item.data[r][c];
            if (!cell || typeof cell !== 'object' || cell.hidden) continue;
            const cs = cell.cs || 1;
            if (c + cs - 1 >= idx) cell.cs = cs - 1;
        }
        const cell = item.data[r][idx];
        if (cell && typeof cell === 'object' && !cell.hidden && (cell.cs || 1) > 1 && item.data[r][idx + 1] !== undefined) {
            item.data[r][idx + 1] = { ...cell, cs: (cell.cs || 1) - 1, hidden: false };
        }
        item.data[r].splice(idx, 1);
    }
    if (Array.isArray(item.columns)) item.columns.splice(idx, 1);
    item.cols--;
    item.dirty = true;
    selection.clearCells();
    markDirty();
    renderDocument();
}

/** Phân bố đều chiều cao các hàng (giữ nguyên tổng chiều cao hiện tại — như Word Distribute Rows) */
function distributeRowsV2(item) {
    if (!canEditTableV2(item)) return;
    const trs = document.querySelectorAll(`.v2-table-wrap[data-id="${item.id}"] tbody tr`);
    if (!trs.length) return;
    saveDocState();
    let total = 0;
    trs.forEach((tr) => { total += tr.getBoundingClientRect().height; });
    const h = Math.max(18, Math.round(total / trs.length));
    item.rowHeights = Array.from({ length: item.rows || trs.length }, () => h + 'px');
    item.dirty = true;
    markDirty();
    renderDocument();
}

/** Phân bố đều bề rộng các cột (giữ nguyên tổng bề rộng bảng — như Word Distribute Columns) */
function distributeColsV2(item) {
    if (!canEditTableV2(item)) return;
    const tableEl = document.querySelector(`.v2-table-wrap[data-id="${item.id}"] table`);
    if (!tableEl || !Array.isArray(item.columns) || !item.columns.length) return;
    saveDocState();
    const w = Math.max(30, Math.round(tableEl.getBoundingClientRect().width / item.columns.length));
    item.columns.forEach((col) => { col.width = w + 'px'; });
    item.dirty = true;
    markDirty();
    renderDocument();
}

/** AutoFit: bỏ mọi bề rộng cột / chiều cao hàng cố định — bảng tự co giãn theo nội dung */
function autoFitTableV2(item) {
    if (!canEditTableV2(item)) return;
    saveDocState();
    (item.columns || []).forEach((col) => { col.width = 'auto'; });
    item.rowHeights = [];
    item.dirty = true;
    markDirty();
    renderDocument();
}

/** Căn nội dung ô theo chiều dọc (top/middle/bottom) — áp dụng cho vùng ô đang chọn, hoặc ô vừa chuột phải */
function setCellVAlignV2(item, td, v) {
    if (!canEditTableV2(item)) return;
    saveDocState();
    const targets = selection.hasCells() ? selection.getCells().filter((t) => t.item === item) : [];
    if (targets.length) {
        targets.forEach(({ cell }) => { cell.verticalAlign = v; });
    } else {
        const r = parseInt(td.dataset.row, 10), c = parseInt(td.dataset.col, 10);
        if (item.data[r] && item.data[r][c] && typeof item.data[r][c] === 'object') {
            item.data[r][c].verticalAlign = v;
        }
    }
    item.dirty = true;
    markDirty();
    renderDocument();
}

/**
 * Căn dọc từ TOOLBAR: áp cho vùng ô đang chọn; nếu không có vùng chọn nhưng con trỏ
 * đang gõ TRONG 1 ô bảng thì áp cho chính ô đó (chốt nội dung đang gõ trước khi render lại).
 */
function applyVAlignToolbarV2(v) {
    if (selection.hasCells()) {
        selection.applyToCells((cell) => { cell.verticalAlign = v; });
        return;
    }
    const ctxArgs = (activeEditor && lastEditorArgs) ? lastEditorArgs.context : null;
    if (ctxArgs && ctxArgs.kind === 'cell' && canEditTableV2(ctxArgs.item)) {
        unmountEditor(); // ghi nội dung đang gõ về items trước khi render lại
        saveDocState();
        const cell = ctxArgs.item.data?.[ctxArgs.r]?.[ctxArgs.c];
        if (cell && typeof cell === 'object') cell.verticalAlign = v;
        ctxArgs.item.dirty = true;
        markDirty();
        renderDocument();
        return;
    }
    showToast('info', 'Hãy chọn ô bảng (hoặc đặt con trỏ trong ô) trước khi căn dọc.');
}

/** Ô kề ô (r,c) theo hướng cạnh — nhảy qua phần bị gộp (rowspan/colspan) của chính ô đó. */
function neighborCellV2(item, r, c, cell, side) {
    let nr = r, nc = c;
    if (side === 'top') nr = r - 1;
    else if (side === 'bottom') nr = r + (cell.rs || 1);
    else if (side === 'left') nc = c - 1;
    else if (side === 'right') nc = c + (cell.cs || 1);
    if (nr < 0 || nc < 0 || nr >= (item.rows || 0) || nc >= (item.cols || 0)) return null;
    return (item.data[nr] && item.data[nr][nc]) ? item.data[nr][nc] : null;
}

/** Áp dụng border cho các ô được chọn hoặc tất cả ô bảng (như Word Border tools) */
function applyBorderToSelectedCellsV2(item, borderType) {
    if (!canEditTableV2(item)) return;
    saveDocState();
    const borderStyle = '1px solid #000';
    // 'hidden' thắng trong border-collapse (đè cả viền của ô kề bên) => xoá viền thật sự.
    const noBorder = '1px hidden #000';
    const cells = selection.hasCells() ? selection.getCells().filter((t) => t.item === item) : [];
    const allCells = [];

    if (cells.length) {
        allCells.push(...cells.map((c) => ({ r: c.r, c: c.c, cell: c.cell })));
    } else {
        for (let r = 0; r < (item.rows || 0); r++) {
            for (let c = 0; c < (item.cols || 0); c++) {
                if (item.data[r] && item.data[r][c] && !item.data[r][c].hidden) {
                    allCells.push({ r, c, cell: item.data[r][c] });
                }
            }
        }
    }

    const rows = item.rows || 0, cols = item.cols || 0;
    const minR = Math.min(...allCells.map((x) => x.r)), maxR = Math.max(...allCells.map((x) => x.r));
    const minC = Math.min(...allCells.map((x) => x.c)), maxC = Math.max(...allCells.map((x) => x.c));

    // ── Viền 1 cạnh (trên/dưới/trái/phải) — như Word: TOGGLE cạnh NGOÀI của vùng chọn.
    //    Để đè được viền của ô kề (border-collapse), phải set cả 2 ô chung cạnh đó.
    const SIDE = { top: 'borderTop', bottom: 'borderBottom', left: 'borderLeft', right: 'borderRight' };
    if (SIDE[borderType]) {
        const prop = SIDE[borderType];
        const oppProp = { borderTop: 'borderBottom', borderBottom: 'borderTop', borderLeft: 'borderRight', borderRight: 'borderLeft' }[prop];
        // Chỉ lấy các ô nằm ở cạnh ngoài tương ứng của vùng chọn.
        const edgeCells = allCells.filter(({ r, c }) =>
            (borderType === 'top' && r === minR) ||
            (borderType === 'bottom' && r === maxR) ||
            (borderType === 'left' && c === minC) ||
            (borderType === 'right' && c === maxC));
        // Toggle: nếu tất cả cạnh đó đang CÓ viền -> tắt; ngược lại -> bật.
        const hasBorder = (v) => v == null || (typeof v === 'string' && v.indexOf('hidden') === -1);
        const target = edgeCells.every(({ cell }) => hasBorder(cell[prop])) ? noBorder : borderStyle;
        edgeCells.forEach(({ r, c, cell }) => {
            cell[prop] = target;
            const nb = neighborCellV2(item, r, c, cell, borderType);
            if (nb) nb[oppProp] = target;
        });
        item.dirty = true;
        markDirty();
        renderDocument();
        return;
    }

    allCells.forEach(({ r, c, cell }) => {
        if (borderType === 'no-border') {
            cell.borderTop = cell.borderBottom = cell.borderLeft = cell.borderRight = noBorder;
        } else if (borderType === 'all-borders') {
            cell.borderTop = cell.borderBottom = cell.borderLeft = cell.borderRight = borderStyle;
        } else if (borderType === 'outside-borders') {
            cell.borderTop = (r === minR) ? borderStyle : noBorder;
            cell.borderBottom = (r === maxR) ? borderStyle : noBorder;
            cell.borderLeft = (c === minC) ? borderStyle : noBorder;
            cell.borderRight = (c === maxC) ? borderStyle : noBorder;
        } else if (borderType === 'inside-borders') {
            cell.borderTop = (r > minR) ? borderStyle : noBorder;
            cell.borderBottom = (r < maxR) ? borderStyle : noBorder;
            cell.borderLeft = (c > minC) ? borderStyle : noBorder;
            cell.borderRight = (c < maxC) ? borderStyle : noBorder;
        } else if (borderType === 'inside-horizontal') {
            cell.borderTop = (r > minR) ? borderStyle : noBorder;
            cell.borderBottom = (r < maxR) ? borderStyle : noBorder;
        } else if (borderType === 'inside-vertical') {
            cell.borderLeft = (c > minC) ? borderStyle : noBorder;
            cell.borderRight = (c < maxC) ? borderStyle : noBorder;
        }
    });

    item.dirty = true;
    markDirty();
    renderDocument();
}

/** Núm kéo góc dưới-phải phóng to/thu nhỏ CẢ BẢNG theo tỉ lệ (như Word) */
function attachTableSizerV2(item, wrap, table) {
    if (BOOT.isReadOnly || BOOT.isExecutionMode || item.locked || item.isVirtual) return;

    const sizer = document.createElement('div');
    sizer.className = 'v2-table-sizer';
    sizer.title = 'Kéo để thay đổi kích thước cả bảng';

    // Đo bề rộng pixel thực của từng cột (dùng ô KHÔNG gộp; cột nào không đo được thì chia đều)
    const measureColWidths = () => {
        const widths = new Array(item.cols || 0).fill(0);
        table.querySelectorAll('tbody td').forEach((tdEl) => {
            const c = parseInt(tdEl.dataset.col, 10);
            if ((tdEl.colSpan || 1) === 1 && widths[c] === 0) widths[c] = tdEl.getBoundingClientRect().width;
        });
        const fallback = table.getBoundingClientRect().width / Math.max(1, item.cols || 1);
        return widths.map((w) => w || fallback);
    };

    sizer.addEventListener('mousedown', (e) => {
        e.preventDefault();
        e.stopPropagation();
        const startX = e.clientX, startY = e.clientY;
        const rect = table.getBoundingClientRect();
        const startW = rect.width, startH = rect.height;
        const startColW = measureColWidths();
        const trEls = Array.from(table.querySelectorAll('tbody tr'));
        const startRowH = trEls.map((tr) => tr.getBoundingClientRect().height);
        const colEls = Array.from(table.querySelectorAll('colgroup col'));
        saveDocState();
        sizer.classList.add('resizing');

        const scaleOf = (ev) => ({
            sx: Math.max(0.1, (startW + ev.clientX - startX) / startW),
            sy: Math.max(0.1, (startH + ev.clientY - startY) / startH),
        });
        const move = (ev) => {
            const { sx, sy } = scaleOf(ev);
            colEls.forEach((el, i) => { el.style.width = Math.max(30, Math.round(startColW[i] * sx)) + 'px'; });
            trEls.forEach((tr, i) => { tr.style.height = Math.max(18, Math.round(startRowH[i] * sy)) + 'px'; });
        };
        const up = (ev) => {
            document.removeEventListener('mousemove', move);
            document.removeEventListener('mouseup', up);
            sizer.classList.remove('resizing');
            const { sx, sy } = scaleOf(ev);
            (item.columns || []).forEach((col, i) => { col.width = Math.max(30, Math.round(startColW[i] * sx)) + 'px'; });
            if (!Array.isArray(item.rowHeights)) item.rowHeights = [];
            trEls.forEach((tr, i) => {
                const rIdx = parseInt(tr.dataset.rowIdx, 10);
                if (!isNaN(rIdx)) item.rowHeights[rIdx] = Math.max(18, Math.round(startRowH[i] * sy)) + 'px';
            });
            item.dirty = true;
            markDirty();
            renderDocument();
        };
        document.addEventListener('mousemove', move);
        document.addEventListener('mouseup', up);
    });

    wrap.appendChild(sizer);
}

/* =========================================================
 * 4. BIẾN SỐ — chèn node + panel cấu hình (đầy đủ V1-parity)
 * ========================================================= */
function insertVariable(type) {
    if (!activeEditor) {
        window.Swal?.fire('Chưa chọn vị trí', 'Hãy click vào một ô bảng hoặc đoạn văn bản trước khi chèn biến.', 'info');
        return;
    }
    const fieldId = 'field_v2_' + Date.now().toString(36) + Math.random().toString(36).slice(2, 6);
    const t = FIELD_TYPES[type] || FIELD_TYPES.text;

    fieldsConfig[fieldId] = {
        id: fieldId,
        name: fieldId,
        label: 'Nhập ' + t.label,
        type,
        validation: { required: false, min: null, max: null, decimal_places: null },
        options: [],
        instruction: '',
        block_id: null,
        section_id: null,
    };

    activeEditor.chain().focus().insertContent({ type: 'ebmrField', attrs: { fieldId } }).run();
    // Chốt ngay nội dung (kèm badge vừa chèn) về item/ô — không đợi tới lúc unmount,
    // để tránh mất biến nếu sau đó người dùng chuyển chế độ / lưu mà chưa rời khỏi ô.
    if (activeSync) activeSync();
    markDirty();
    openFieldPanel(fieldId);
}

/* ----------------------------------------------------------
 * 4a-bis. Nhân bản biến số trong 1 đoạn HTML (dùng khi COPY/PASTE ô)
 *   Mỗi badge data-field-id được clone config sang id MỚI để 2 ô
 *   không trỏ chung 1 biến. Trả về HTML đã thay id.
 * ---------------------------------------------------------- */
/**
 * Xây map oldId->newId (+ oldName->newName) cho MỌI data-field-id xuất hiện
 * trong 1 danh sách đoạn HTML — dùng chung cho cả vùng đang dán, để công
 * thức tham chiếu 1 biến khác CŨNG nằm trong vùng copy được trỏ sang đúng
 * bản sao mới thay vì trỏ ngược về biến gốc (bug: copy cả formula + biến
 * phụ thuộc của nó thì formula bản sao vẫn tính theo biến GỐC).
 */
function buildFieldDuplicateMapV2(htmlList, makeId) {
    const idMap = {};   // oldId -> newId
    const nameMap = {}; // oldName -> newName (name cũ của field bị nhân bản)
    const tmp = document.createElement('div');
    (htmlList || []).forEach((html, i) => {
        if (!html || typeof html !== 'string' || html.indexOf('data-field-id') === -1) return;
        tmp.innerHTML = html;
        tmp.querySelectorAll('[data-field-id]').forEach((el) => {
            const oldId = el.getAttribute('data-field-id');
            if (!oldId || idMap[oldId] || !fieldsConfig[oldId]) return;
            const newId = makeId
                ? makeId(oldId)
                : 'field_v2_' + Date.now().toString(36) + Math.random().toString(36).slice(2, 8) + i;
            idMap[oldId] = newId;
            nameMap[fieldsConfig[oldId].name || oldId] = newId;
        });
    });
    return { idMap, nameMap };
}

/** Thay (tenBien) trong chuỗi công thức theo nameMap — giữ nguyên phần không khớp */
function remapFormulaStringV2(formula, nameMap) {
    if (!formula || !nameMap) return formula;
    return formula.replace(/\(([^()]+)\)/g, (match, varName) => {
        const key = varName.trim();
        return nameMap[key] ? `(${nameMap[key]})` : match;
    });
}

/**
 * Tạo bản sao config cho mọi field trong idMap. Nếu bản sao là formula
 * (hoặc có na_condition) tham chiếu 1 field khác CŨNG có trong nameMap,
 * viết lại tham chiếu đó sang tên MỚI — giữ vùng vừa dán tự trỏ vào nhau.
 */
function applyFieldDuplicateMapV2(idMap, nameMap) {
    Object.keys(idMap).forEach((oldId) => {
        const newId = idMap[oldId];
        const clone = JSON.parse(JSON.stringify(fieldsConfig[oldId]));
        clone.id = newId;
        clone.name = newId;
        // formula (type=formula) VÀ công thức tự-động-tick (type=checkbox) đều lưu chung ở cfg.formula
        if (clone.formula) {
            clone.formula = remapFormulaStringV2(clone.formula, nameMap);
        }
        if (clone.na_condition && clone.na_condition.target_id && nameMap[clone.na_condition.target_id]) {
            clone.na_condition.target_id = nameMap[clone.na_condition.target_id];
        }
        fieldsConfig[newId] = clone;
    });
    markDirty();
}

/** Thay data-field-id trong 1 đoạn HTML theo idMap đã build sẵn (không tạo config mới) */
function rewriteFieldIdsInHtmlV2(html, idMap) {
    if (!html || typeof html !== 'string' || !idMap) return html;
    let out = html;
    Object.keys(idMap).forEach((oldId) => { out = out.split(oldId).join(idMap[oldId]); });
    return out;
}

/** Tiện ích cho 1 đoạn HTML đơn lẻ (map chỉ xây trong phạm vi đoạn này) */
function duplicateFieldsInHtmlV2(html) {
    if (!html || typeof html !== 'string' || html.indexOf('data-field-id') === -1) return html;
    const { idMap, nameMap } = buildFieldDuplicateMapV2([html]);
    if (!Object.keys(idMap).length) return html;
    applyFieldDuplicateMapV2(idMap, nameMap);
    return rewriteFieldIdsInHtmlV2(html, idMap);
}

/* ----------------------------------------------------------
 * 4a. syncFieldConfigV2 — deep-path update + type coercion
 *     Clone logic từ V1 (syncFieldConfig trong ui_handlers)
 * ---------------------------------------------------------- */
function syncFieldConfigV2(fieldId, path, value) {
    if (!fieldsConfig[fieldId]) return;
    let target = fieldsConfig[fieldId];
    const keys = path.split('.');
    const lastKey = keys.pop();
    for (const key of keys) {
        if (!target[key] || typeof target[key] !== 'object') target[key] = {};
        target = target[key];
    }
    // Type coercion (giống V1)
    if (value === '' || value === undefined) value = null;
    else if (['min', 'max', 'decimal_places'].some(k => path.includes(k))) {
        value = value !== null ? Number(value) : null;
    } else if (typeof value === 'string' && path.endsWith('required') || path.endsWith('is_checker') ||
        path.endsWith('autoSystemTime') || path.endsWith('scaleEnabled') ||
        path.endsWith('barcodeScanEnabled') || path.endsWith('allow_out_of_bounds') ||
        path.endsWith('showLabel')) {
        // boolean giữ nguyên (đã là boolean từ checkbox.checked)
    }
    if (path === 'options' && typeof value === 'string' && value !== null) {
        value = value.split(',').map(s => s.trim()).filter(Boolean);
    }
    target[lastKey] = value;
    markDirty();
}

/* ----------------------------------------------------------
 * 4b. Formula helpers (V2 — không dùng jQuery)
 * ---------------------------------------------------------- */
/**
 * Chèn 1 token vào vùng nhập công thức (contenteditable).
 * isVarRef=true → bọc token bằng span.formula-var-token để hiển thị màu đẹp.
 */
let selectFormulaVarMode = null; // fieldId nếu đang bắt biến, null nếu không

// Bảng màu viền cho các biến tham chiếu trong công thức — mỗi biến 1 màu theo thứ tự xuất hiện
const FORMULA_VAR_COLORS = ['#e11d48', '#2563eb', '#d97706', '#16a34a', '#9333ea', '#0891b2', '#db2777', '#65a30d'];

function toggleSelectFormulaVarModeV2(fieldId) {
    if (selectFormulaVarMode === fieldId) {
        // Tắt chế độ bắt biến
        selectFormulaVarMode = null;
        const btn = document.getElementById(`v2fp-pick-var-${fieldId}`);
        if (btn) {
            btn.innerHTML = '<i class="fas fa-hand-pointer me-1"></i>Bắt biến từ màn hình';
            btn.classList.remove('btn-warning', 'text-dark');
            btn.classList.add('btn-outline-warning');
        }
        document.body.classList.remove('v2-select-formula-var-active');
        // Xóa highlight của các biến
        document.querySelectorAll('.v2-field-badge, .ebmr-field-badge').forEach(badge => {
            badge.classList.remove('v2-formula-var-highlighted', 'v2-formula-var-referenced');
        });
    } else {
        // Bật chế độ bắt biến
        selectFormulaVarMode = fieldId;
        const btn = document.getElementById(`v2fp-pick-var-${fieldId}`);
        if (btn) {
            btn.innerHTML = '<i class="fas fa-times me-1"></i>Đang bắt biến... (Hủy)';
            btn.classList.remove('btn-outline-warning');
            btn.classList.add('btn-warning', 'text-dark');
        }
        document.body.classList.add('v2-select-formula-var-active');
        // Highlight biến đang được cài đặt
        document.querySelectorAll(`.v2-field-badge[data-field-id="${fieldId}"], .ebmr-field-badge[data-field-id="${fieldId}"]`).forEach(badge => {
            badge.classList.add('v2-formula-var-highlighted');
        });
        // Highlight biến được tham chiếu trong công thức
        const el = document.getElementById(`v2-formula-input-${fieldId}`);
        if (el) highlightFormulaReferencesV2(el);
    }
}

/** Highlight các biến được tham chiếu trong công thức */
function highlightFormulaReferencesV2(formulaInputEl) {
    // Biến đích (đang cài đặt công thức) suy ra từ id của ô nhập: v2-formula-input-<fieldId>
    const targetFieldId = (formulaInputEl.id || '').replace('v2-formula-input-', '');
    const formula = serializeFormulaElementV2(formulaInputEl) || '';
    // Tách (varName) từ công thức — giữ thứ tự xuất hiện, bỏ trùng
    const refVarNames = [];
    let match;
    const regex = /\(([^()]+)\)/g;
    while ((match = regex.exec(formula)) !== null) {
        const vn = match[1].trim();
        if (!refVarNames.includes(vn)) refVarNames.push(vn);
    }
    // Mỗi biến một màu riêng theo thứ tự xuất hiện trong công thức
    const colorOf = (vn) => FORMULA_VAR_COLORS[refVarNames.indexOf(vn) % FORMULA_VAR_COLORS.length];

    // Badge sống trong editor là .v2-field-badge (NodeView), badge tĩnh là .ebmr-field-badge
    document.querySelectorAll('.v2-field-badge, .ebmr-field-badge').forEach(badge => {
        const fid = badge.getAttribute('data-field-id');
        const cfg = fieldsConfig[fid] || {};
        const isTarget = fid === targetFieldId;
        badge.classList.toggle('v2-formula-var-highlighted', isTarget);
        const refName = !isTarget && refVarNames.find(vn => vn === fid || vn === cfg.name || vn === cfg.label);
        if (refName) {
            badge.classList.add('v2-formula-var-referenced');
            badge.style.setProperty('--v2-ref-color', colorOf(refName));
        } else {
            badge.classList.remove('v2-formula-var-referenced');
            badge.style.removeProperty('--v2-ref-color');
        }
    });

    // Token trong ô công thức tô cùng màu với badge ngoài tài liệu để dễ đối chiếu
    formulaInputEl.querySelectorAll('.v2-formula-var').forEach(span => {
        const vn = (span.dataset.var || span.textContent).trim();
        if (refVarNames.includes(vn)) {
            const c = colorOf(vn);
            span.style.borderColor = c;
            span.style.color = c;
            span.style.background = c + '1a'; // ~10% alpha
        }
    });
}

/** Xóa highlight của các biến tham chiếu (trừ khi đang ở "Bắt" mode) */
function clearFormulaHighlightsV2() {
    if (selectFormulaVarMode) return; // Giữ highlight khi đang ở "Bắt" mode
    document.querySelectorAll('.v2-field-badge, .ebmr-field-badge').forEach(badge => {
        badge.classList.remove('v2-formula-var-referenced', 'v2-formula-var-highlighted');
        badge.style.removeProperty('--v2-ref-color');
    });
}

function insertFormulaTokenV2(fieldId, token, isVarRef = false) {
    const el = document.getElementById(`v2-formula-input-${fieldId}`);
    if (!el) return;
    el.focus();
    // Token hiển thị NHÃN cho người dùng đọc; data-var vẫn giữ name để serialize đúng
    const makeVarSpan = (varName) => {
        const cfg = Object.values(fieldsConfig).find(f => f.name === varName) || {};
        const span = document.createElement('span');
        span.className = 'v2-formula-var';
        span.contentEditable = 'false';
        span.textContent = cfg.label || varName;
        span.title = varName;
        span.dataset.var = varName;
        return span;
    };
    const sel = window.getSelection();
    if (sel && sel.rangeCount > 0 && el.contains(sel.anchorNode)) {
        const range = sel.getRangeAt(0);
        range.deleteContents();
        const node = isVarRef ? makeVarSpan(token) : document.createTextNode(token);
        range.insertNode(node);
        range.setStartAfter(node);
        range.collapse(true);
        sel.removeAllRanges();
        sel.addRange(range);
    } else {
        el.appendChild(isVarRef ? makeVarSpan(token) : document.createTextNode(token));
    }
    // Serialize và sync về fieldsConfig
    const formulaStr = serializeFormulaElementV2(el);
    syncFieldConfigV2(fieldId, 'formula', formulaStr);
    highlightFormulaReferencesV2(el);
}

/**
 * Chuyển DOM contenteditable → chuỗi công thức thuần (span.v2-formula-var → (varName))
 */
function serializeFormulaElementV2(el) {
    if (!el) return '';
    let result = '';
    el.childNodes.forEach(node => {
        if (node.nodeType === Node.TEXT_NODE) {
            result += node.textContent;
        } else if (node.nodeType === Node.ELEMENT_NODE && node.classList.contains('v2-formula-var')) {
            result += `(${node.dataset.var || node.textContent})`;
        } else if (node.nodeType === Node.ELEMENT_NODE) {
            result += node.textContent;
        }
    });
    return result.trim();
}

/**
 * Chuyển chuỗi công thức → HTML có màu (chuẩn bị để đổ vào contenteditable)
 */
function deserializeFormulaToHtmlV2(formula, fieldId) {
    if (!formula) return '';
    // Tách (varName) thành span màu — hiển thị NHÃN, data-var giữ name để serialize đúng
    return formula.replace(/\(([^()]+)\)/g, (match, varName) => {
        const vn = varName.trim();
        const cfg = Object.values(fieldsConfig).find(f => f.name === vn);
        const label = cfg ? (cfg.label || vn) : vn;
        return `<span class="v2-formula-var" contenteditable="false" data-var="${esc(vn)}" title="${esc(vn)}">${esc(label)}</span>`;
    });
}

/* ----------------------------------------------------------
 * 4c. Copy / Cut / Paste / Delete biến số
 * ---------------------------------------------------------- */
let _copiedVar = null;

function copyVariableV2(fieldId) {
    const field = fieldsConfig[fieldId];
    if (!field) return;
    _copiedVar = { ...JSON.parse(JSON.stringify(field)), __isCut: false, __sourceFieldId: null };
    showToast('success', 'Đã sao chép biến: ' + (field.label || field.name));
}

function cutVariableV2(fieldId) {
    const field = fieldsConfig[fieldId];
    if (!field) return;
    _copiedVar = { ...JSON.parse(JSON.stringify(field)), __isCut: true, __sourceFieldId: fieldId };
    showToast('info', 'Đã cắt biến: ' + (field.label || field.name) + '. Hãy click vào vị trí mới và dán.');
}

/** Core xóa N biến số: gỡ badge khỏi mọi block + xóa config (dùng chung đơn lẻ/hàng loạt) */
function deleteVariablesV2(fieldIds) {
    const ids = (fieldIds || []).filter((id) => fieldsConfig[id]);
    if (ids.length === 0) return;
    ids.forEach((id) => delete fieldsConfig[id]);
    deletedFieldKeys.push(...ids);
    items.forEach(item => {
        const scanAndRemove = (html) => {
            if (!html || !ids.some((id) => html.includes(id))) return html;
            const tmp = document.createElement('div');
            tmp.innerHTML = html;
            ids.forEach((id) =>
                tmp.querySelectorAll(`.ebmr-field-badge[data-field-id="${id}"]`).forEach(el => el.remove()));
            return tmp.innerHTML;
        };
        if (item.content) item.content = scanAndRemove(item.content);
        if (item.data) item.data.forEach(row => row.forEach(cell => {
            if (cell && cell.content) cell.content = scanAndRemove(cell.content);
        }));
        item.dirty = true;
    });
    selection.clearFields();
    markDirty();
    renderDocument();
    document.getElementById('v2-field-panel')?.classList.remove('open');
}

function deleteVariableV2(fieldId) {
    if (!fieldsConfig[fieldId]) return;
    deleteVariablesV2([fieldId]);
    showToast('success', 'Đã xóa biến số');
}

/* ----------------------------------------------------------
 * 4c-bis. Copy / Cut / Paste BIẾN SỐ (bổ sung cho copy/paste Ô ở selection.js)
 *   - Copy: lưu bản sao sâu config các biến đang chọn vào clipboard.
 *   - Cut : copy rồi xóa badge + config khỏi tài liệu.
 *   - Paste: chèn badge của các biến trong clipboard vào Ô ĐÍCH đang chọn.
 *       • Nếu config gốc VẪN còn (Copy) -> cấp id/name MỚI để không dùng chung
 *         cấu hình (giống duplicateFieldsInHtmlV2).
 *       • Nếu config gốc đã mất (Cut) -> khôi phục ĐÚNG id/name cũ = di chuyển
 *         thật, giữ được tham chiếu công thức theo tên.
 * ---------------------------------------------------------- */
let fieldClipboardV2 = null; // [configClone] — bản sao sâu, cấp id khi dán

function copyFieldsV2(ids) {
    const valid = (ids || []).filter((id) => fieldsConfig[id]);
    if (!valid.length) return 0;
    fieldClipboardV2 = valid.map((id) => JSON.parse(JSON.stringify(fieldsConfig[id])));
    return valid.length;
}

function cutFieldsV2(ids) {
    const n = copyFieldsV2(ids);
    if (!n) return 0;
    saveDocState();
    deleteVariablesV2(ids); // gỡ badge + config, clearFields, renderDocument
    return n;
}

/**
 * Chuẩn bị 1 lượt DÁN từ fieldClipboardV2: quyết định id/name mới cho từng biến
 * (Copy = luôn cấp id mới, Cut/Move = khôi phục lại đúng id/name cũ), gom
 * oldName->newName để formula tham chiếu 1 biến khác CŨNG được dán cùng lượt này
 * trỏ đúng sang bản sao mới thay vì trỏ ngược về biến gốc — rồi ĐĂNG KÝ luôn config
 * mới vào fieldsConfig. Trả về plan [{id}] để nơi gọi tự chèn badge vào đúng chỗ
 * (ô bảng hoặc editor tại vị trí con trỏ). Dùng chung cho cả 2 đích dán.
 */
function registerPasteFieldsPlanV2() {
    const nameMap = {};
    const plan = fieldClipboardV2.map((cfg, i) => {
        const isMove = !fieldsConfig[cfg.id]; // config gốc đã mất => là thao tác Cut => khôi phục id cũ
        let id = cfg.id;
        if (!isMove) {
            id = 'field_v2_' + Date.now().toString(36) + Math.random().toString(36).slice(2, 6) + i;
            nameMap[cfg.name || cfg.id] = id; // tên tự sinh để tránh trùng tên với biến gốc (ảnh hưởng công thức)
        }
        return { cfg, id, isMove };
    });
    plan.forEach(({ cfg, id, isMove }) => {
        const clone = JSON.parse(JSON.stringify(cfg));
        clone.id = id;
        if (!isMove) clone.name = id;
        // formula (type=formula) VÀ công thức tự-động-tick (type=checkbox) đều lưu chung ở cfg.formula
        if (clone.formula) {
            clone.formula = remapFormulaStringV2(clone.formula, nameMap);
        }
        if (clone.na_condition && clone.na_condition.target_id && nameMap[clone.na_condition.target_id]) {
            clone.na_condition.target_id = nameMap[clone.na_condition.target_id];
        }
        fieldsConfig[id] = clone;
    });
    return plan;
}

function pasteFieldsIntoCellV2(item, r, c) {
    if (!fieldClipboardV2 || !fieldClipboardV2.length) return false;
    const cell = item?.data?.[r]?.[c];
    if (!cell || cell.hidden) return false;
    saveDocState();
    const plan = registerPasteFieldsPlanV2();
    let html = '';
    plan.forEach(({ id }) => { html += `<span contenteditable="false" class="ebmr-field-badge" data-field-id="${id}"></span>​`; });
    cell.content = (cell.content || '') + html;
    item.dirty = true;
    markDirty();
    renderDocument();
    return true;
}

/**
 * Dán biến từ clipboard NGAY TẠI VỊ TRÍ CON TRỎ trong editor — dùng khi đích không
 * phải Ô bảng (đoạn văn bản thường), gọi từ context menu "Dán biến" và Ctrl+V khi
 * đang gõ trong editor. Tự mở lại vị trí soạn gần nhất nếu editor đã đóng (runEditorInsertV2).
 */
function pasteFieldsV2() {
    if (!fieldClipboardV2 || !fieldClipboardV2.length) {
        showToast('warning', 'Chưa sao chép biến số nào.');
        return false;
    }
    saveDocState();
    const plan = registerPasteFieldsPlanV2();
    const ok = runEditorInsertV2((ed) => {
        plan.forEach(({ id }) => {
            ed.chain().focus().insertContent({ type: 'ebmrField', attrs: { fieldId: id } }).run();
        });
    });
    if (!ok) {
        showToast('warning', 'Hãy bấm vào một đoạn văn bản/ô có thể sửa rồi dán.');
        return false;
    }
    if (activeSync) activeSync();
    markDirty();
    showToast('success', `Đã dán ${plan.length} biến số`);
    return true;
}

const hasFieldClipboardV2 = () => !!(fieldClipboardV2 && fieldClipboardV2.length);

function showToast(type, msg) {
    if (window.toastr) { window.toastr[type](msg); return; }
    if (window.Swal?.mixin) {
        window.Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 2500 })
            .fire({ icon: type === 'info' ? 'info' : type === 'success' ? 'success' : 'warning', title: msg });
    }
}

/* ----------------------------------------------------------
 * 4d. openFieldPanel — Panel 3 Tab đầy đủ (V1-parity)
 * ---------------------------------------------------------- */
function openFieldPanel(fieldId, onSaved) {
    const cfg = fieldsConfig[fieldId];
    if (!cfg) return;
    selection.setFields([fieldId]); // viền chọn trên badge (thao tác 10)
    if (!cfg.validation) cfg.validation = { required: false, min: null, max: null, decimal_places: null };

    const panel = document.getElementById('v2-field-panel');
    if (!panel) return;
    panel.classList.add('open');

    // ── Helpers build HTML ──────────────────────────────────
    const importantVars = (BOOT.importantVars || []);
    const allNumberFields = Object.values(fieldsConfig).filter(f =>
        f.id !== fieldId && (f.type === 'number' || f.type === 'formula' || f.type === 'checkbox')
    );
    const numberFieldsOptions = '<option value="">-- Chọn biến số --</option>' +
        allNumberFields.map(f => `<option value="${esc(f.name)}">${esc(f.label ? f.label + ' = ' + f.name : f.name)}</option>`).join('');

    // ── Tab 1: Cơ bản ────────────────────────────────────────
    const basicHtml = `
        <div class="mb-3">
            <label class="v2-prop-label">Loại dữ liệu</label>
            <select class="form-select form-select-sm border-primary" id="v2fp-type-${fieldId}"
                onchange="syncFieldConfigV2('${fieldId}','type',this.value); openFieldPanel('${fieldId}')">
                <option value="text"      ${cfg.type === 'text' ? 'selected' : ''}>Văn bản tự do</option>
                <option value="number"    ${cfg.type === 'number' ? 'selected' : ''}>Số liệu (Tính toán)</option>
                <option value="checkbox"  ${cfg.type === 'checkbox' ? 'selected' : ''}>Hộp kiểm (Tick)</option>
                <option value="formula"   ${cfg.type === 'formula' ? 'selected' : ''}>Công thức tự động (=)</option>
                <option value="date"      ${cfg.type === 'date' ? 'selected' : ''}>Thời Gian</option>
                <option value="signature" ${cfg.type === 'signature' ? 'selected' : ''}>Chữ ký điện tử</option>
                <option value="select"    ${cfg.type === 'select' ? 'selected' : ''}>Chọn từ danh sách</option>
                <option value="radio"     ${cfg.type === 'radio' ? 'selected' : ''}>Chọn 1 trong nhiều (Radio)</option>
            </select>
        </div>
        <hr class="opacity-25 my-2">
        <div class="mb-3">
            <label class="v2-prop-label">Nhãn hiển thị</label>
            <input type="text" class="form-control form-control-sm" id="v2fp-label-${fieldId}"
                value="${esc(cfg.label || '')}"
                oninput="syncFieldConfigV2('${fieldId}','label',this.value); _v2RepaintBadge('${fieldId}')">
            
            <div class="form-check form-switch mt-1">
                <input class="form-check-input" type="checkbox" id="v2fp-showlabel-${fieldId}"
                    ${cfg.showLabel !== false ? 'checked' : ''}
                    onchange="syncFieldConfigV2('${fieldId}','showLabel',this.checked)">
                <label class="form-check-label" style="font-size:0.72rem" for="v2fp-showlabel-${fieldId}">
                    Hiển thị nhãn khi chạy thử
                </label>
            </div>
        </div>
        <div class="mb-3">
            <label class="v2-prop-label"><i class="fas fa-fingerprint me-1"></i>Mã biến số (ID)</label>
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-light border-end-0">@</span>
                <input type="text" class="form-control border-start-0 font-monospace"
                    value="${esc(cfg.name || '')}"
                    oninput="syncFieldConfigV2('${fieldId}','name',this.value)">
            </div>
            <div class="form-text" style="font-size:0.65rem">Viết liền không dấu. VD: sl, kl_tong</div>
        </div>
        <div class="mb-2">
            <label class="v2-prop-label">Giá trị mặc định</label>
            <input type="text" class="form-control form-control-sm"
                value="${esc(cfg.defaultValue || '')}" placeholder="VD: 4.6"
                oninput="syncFieldConfigV2('${fieldId}','defaultValue',this.value)">
            <div class="form-text" style="font-size:0.68rem">Dùng để chạy thử trong thiết kế.</div>
        </div>`;

    // ── Tab 2: Công thức / Logic ─────────────────────────────
    // Phần N/A condition — chung cho mọi loại
    let logicHtml = `
        <div class="mb-3">
            <label class="v2-prop-label text-success"><i class="fas fa-ban me-1"></i>Điều kiện Không áp dụng (N/A)</label>
            <div class="p-2 border border-success border-opacity-50 rounded bg-light">
                <div class="mb-2">
                    <label class="v2-prop-sublabel">Mã ID Biến phụ thuộc</label>
                    <input type="text" class="form-control form-control-sm border-success"
                        placeholder="Nhập ID biến (VD: tram_1)"
                        value="${esc((cfg.na_condition && cfg.na_condition.target_id) || '')}"
                        oninput="syncFieldConfigV2('${fieldId}','na_condition.target_id',this.value)">
                </div>
                <div class="row g-2">
                    <div class="col-5">
                        <label class="v2-prop-sublabel">Toán tử</label>
                        <select class="form-select form-select-sm border-success"
                            onchange="syncFieldConfigV2('${fieldId}','na_condition.operator',this.value)">
                            <option value="=" ${(cfg.na_condition && cfg.na_condition.operator === '=') ? 'selected' : ''}>Bằng (=)</option>
                            <option value="!=" ${(cfg.na_condition && cfg.na_condition.operator === '!=') ? 'selected' : ''}>Khác (!=)</option>
                        </select>
                    </div>
                    <div class="col-7">
                        <label class="v2-prop-sublabel">Giá trị so sánh</label>
                        <input type="text" class="form-control form-control-sm border-success"
                            value="${esc((cfg.na_condition && cfg.na_condition.value) || '')}"
                            oninput="syncFieldConfigV2('${fieldId}','na_condition.value',this.value)">
                    </div>
                </div>
                <div class="form-text mt-1" style="font-size:0.65rem">Nếu điều kiện đúng → biến tự động N/A.</div>
            </div>
        </div>`;

    // Phần riêng theo type
    if (cfg.type === 'formula') {
        logicHtml += `
            <hr class="opacity-25 my-2">
            <div class="mb-3">
                <label class="v2-prop-label text-success"><i class="fas fa-calculator me-1"></i>Công thức tính toán</label>
                <div class="input-group input-group-sm mb-2">
                    <select class="form-select border-success" style="max-width:65%"
                        onchange="if(this.value){insertFormulaTokenV2('${fieldId}',this.value,true);this.value=''}">
                        ${numberFieldsOptions}
                    </select>
                    <button class="btn btn-outline-success" type="button"
                        onclick="insertFormulaTokenV2('${fieldId}',' + ')">+</button>
                    <button class="btn btn-outline-success" type="button"
                        onclick="insertFormulaTokenV2('${fieldId}',' - ')">-</button>
                    <button class="btn btn-outline-success" type="button"
                        onclick="insertFormulaTokenV2('${fieldId}',' * ')">×</button>
                    <button class="btn btn-outline-success" type="button"
                        onclick="insertFormulaTokenV2('${fieldId}',' / ')">÷</button>
                </div>
                <div class="input-group input-group-sm mb-2">
                    <button class="btn btn-outline-success" type="button"
                        onclick="insertFormulaTokenV2('${fieldId}','AVG(')">AVG()</button>
                    <button class="btn btn-outline-success" type="button"
                        onclick="insertFormulaTokenV2('${fieldId}','MAX(')">MAX()</button>
                    <button class="btn btn-outline-success" type="button"
                        onclick="insertFormulaTokenV2('${fieldId}','MIN(')">MIN()</button>
                    <button class="btn btn-outline-secondary" type="button"
                        onclick="insertFormulaTokenV2('${fieldId}',', ')">,</button>
                    <button class="btn btn-outline-secondary" type="button"
                        onclick="insertFormulaTokenV2('${fieldId}',')')">)</button>
                    <button class="btn btn-outline-warning" id="v2fp-pick-var-${fieldId}" type="button"
                        onclick="toggleSelectFormulaVarModeV2('${fieldId}')"><i class="fas fa-hand-pointer me-1"></i>Bắt</button>
                </div>
                <div id="v2-formula-input-${fieldId}"
                    class="form-control form-control-sm border-success font-monospace v2-formula-editor"
                    contenteditable="true"
                    placeholder="Gõ công thức hoặc chọn biến từ dropdown..."
                    oninput="syncFieldConfigV2('${fieldId}','formula',serializeFormulaElementV2(this)); highlightFormulaReferencesV2(this);"
                    onfocus="highlightFormulaReferencesV2(this);"
                    onblur="clearFormulaHighlightsV2();"
                    >${deserializeFormulaToHtmlV2(cfg.formula || '', fieldId)}</div>
                <div class="mt-2">
                    <label class="v2-prop-sublabel">Làm tròn số thập phân</label>
                    <input type="number" class="form-control form-control-sm" min="0" max="6"
                        placeholder="VD: 2"
                        value="${cfg.validation.decimal_places !== null && cfg.validation.decimal_places !== undefined ? cfg.validation.decimal_places : ''}"
                        oninput="syncFieldConfigV2('${fieldId}','validation.decimal_places',this.value)">
                </div>
            </div>`;
    } else if (cfg.type === 'checkbox') {
        logicHtml += `
            <hr class="opacity-25 my-2">
            <div class="mb-3 p-2 bg-light rounded border border-success border-opacity-25">
                <label class="v2-prop-label text-success"><i class="fas fa-calculator me-1"></i>Công thức tự động Tick</label>
                <div class="input-group input-group-sm mb-2">
                    <select class="form-select border-success" style="max-width:65%"
                        onchange="if(this.value){insertFormulaTokenV2('${fieldId}',this.value,true);this.value=''}">
                        ${numberFieldsOptions}
                    </select>
                    <button class="btn btn-outline-success" type="button"
                        onclick="insertFormulaTokenV2('${fieldId}',' + ')">+</button>
                    <button class="btn btn-outline-success" type="button"
                        onclick="insertFormulaTokenV2('${fieldId}',' - ')">-</button>
                    <button class="btn btn-outline-success" type="button"
                        onclick="insertFormulaTokenV2('${fieldId}',' * ')">×</button>
                    <button class="btn btn-outline-success" type="button"
                        onclick="insertFormulaTokenV2('${fieldId}',' / ')">÷</button>
                    <button class="btn btn-outline-success" type="button"
                        onclick="insertFormulaTokenV2('${fieldId}',' == ')">==</button>
                    <button class="btn btn-outline-warning" id="v2fp-pick-var-${fieldId}" type="button"
                        onclick="toggleSelectFormulaVarModeV2('${fieldId}')"><i class="fas fa-hand-pointer me-1"></i>Bắt</button>
                </div>
                <div id="v2-formula-input-${fieldId}"
                    class="form-control form-control-sm border-success font-monospace v2-formula-editor"
                    contenteditable="true"
                    placeholder="Công thức > 0 hoặc TRUE → tự Tick..."
                    oninput="syncFieldConfigV2('${fieldId}','formula',serializeFormulaElementV2(this)); highlightFormulaReferencesV2(this);"
                    onfocus="highlightFormulaReferencesV2(this);"
                    onblur="clearFormulaHighlightsV2();"
                    >${deserializeFormulaToHtmlV2(cfg.formula || '', fieldId)}</div>
                <div class="form-text mt-1" style="font-size:0.65rem">Nếu công thức > 0 hoặc TRUE → ô tự động Tick.</div>
            </div>`;
    } else if (cfg.type === 'date') {
        logicHtml += `
            <hr class="opacity-25 my-2">
            <div class="mb-3">
                <label class="v2-prop-label text-success"><i class="fas fa-clock me-1"></i>Định dạng thời gian</label>
                <select class="form-select form-select-sm border-success"
                    onchange="syncFieldConfigV2('${fieldId}','date_format',this.value)">
                    <option value="dd/mm/yyyy" ${(!cfg.date_format || cfg.date_format === 'dd/mm/yyyy') ? 'selected' : ''}>Ngày (dd/mm/yyyy)</option>
                    <option value="hh:mm dd/mm/yyyy" ${cfg.date_format === 'hh:mm dd/mm/yyyy' ? 'selected' : ''}>Giờ và Ngày (hh:mm dd/mm/yyyy)</option>
                </select>
            </div>
            <div class="mb-3 p-2 bg-light rounded border border-success border-opacity-25">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="v2fp-autotime-${fieldId}"
                        ${(cfg.autoSystemTime !== false) ? 'checked' : ''}
                        onchange="syncFieldConfigV2('${fieldId}','autoSystemTime',this.checked)">
                    <label class="form-check-label small fw-bold text-muted" for="v2fp-autotime-${fieldId}">
                        <i class="fas fa-bolt me-1 text-warning"></i>Tự động lấy giờ hệ thống
                    </label>
                </div>
                <div class="form-text mt-1" style="font-size:0.65rem">Click chuột → hệ thống tự điền giờ hiện tại.</div>
            </div>`;
    } else if (cfg.type === 'signature') {
        logicHtml += `
            <hr class="opacity-25 my-2">
            <div class="mb-3 p-3 bg-light rounded border border-success border-opacity-25 text-center">
                <i class="fas fa-user-check text-success fa-2x mb-2"></i>
                <div class="form-check form-switch d-inline-block text-start w-100">
                    <input class="form-check-input" type="checkbox" id="v2fp-checker-${fieldId}"
                        ${cfg.is_checker ? 'checked' : ''}
                        onchange="syncFieldConfigV2('${fieldId}','is_checker',this.checked)">
                    <label class="form-check-label small fw-bold text-muted ms-1" for="v2fp-checker-${fieldId}">
                        Chữ ký người không đăng nhập
                    </label>
                </div>
                <div class="form-text text-start mt-1" style="font-size:0.65rem">Người dùng phải nhập lại user + mật khẩu để ký.</div>
            </div>`;
    } else if (cfg.type === 'number') {
        logicHtml += `
            <hr class="opacity-25 my-2">
            <div class="card border-success border-opacity-25 shadow-none mb-3">
                <div class="card-header bg-light py-1">
                    <label class="small fw-bold text-success mb-0"><i class="fas fa-balance-scale me-1"></i>Giới hạn giá trị (Min/Max)</label>
                </div>
                <div class="card-body p-2">
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="v2-prop-sublabel">Tối thiểu (Min)</label>
                            <input type="number" class="form-control form-control-sm"
                                placeholder="VD: 71.0"
                                value="${cfg.validation.min !== null && cfg.validation.min !== undefined ? cfg.validation.min : ''}"
                                oninput="syncFieldConfigV2('${fieldId}','validation.min',this.value)">
                        </div>
                        <div class="col-6">
                            <label class="v2-prop-sublabel">Tối đa (Max)</label>
                            <input type="number" class="form-control form-control-sm"
                                placeholder="VD: 81.0"
                                value="${cfg.validation.max !== null && cfg.validation.max !== undefined ? cfg.validation.max : ''}"
                                oninput="syncFieldConfigV2('${fieldId}','validation.max',this.value)">
                        </div>
                    </div>
                    <div class="mt-2 pt-2 border-top">
                        <label class="v2-prop-sublabel">Chữ số thập phân</label>
                        <input type="number" class="form-control form-control-sm" min="0" max="6"
                            placeholder="Bỏ trống nếu là số nguyên"
                            value="${cfg.validation.decimal_places !== null && cfg.validation.decimal_places !== undefined ? cfg.validation.decimal_places : ''}"
                            oninput="syncFieldConfigV2('${fieldId}','validation.decimal_places',this.value)">
                    </div>
                    <div class="form-check form-switch pt-2 mt-2 border-top">
                        <input class="form-check-input" type="checkbox" id="v2fp-outofbounds-${fieldId}"
                            ${cfg.validation && cfg.validation.allow_out_of_bounds ? 'checked' : ''}
                            onchange="syncFieldConfigV2('${fieldId}','validation.allow_out_of_bounds',this.checked)">
                        <label class="form-check-label small text-muted" style="font-size:0.75em" for="v2fp-outofbounds-${fieldId}">
                            Cho phép nhập ngoài giới hạn?
                        </label>
                    </div>
                </div>
            </div>`;
    } else if (cfg.type === 'select') {
        const dsType = (cfg.dataSource && cfg.dataSource.type) || 'manual';
        const ds = cfg.dataSource || {};
        logicHtml += `
            <hr class="opacity-25 my-2">
            <div class="mb-3">
                <label class="v2-prop-label text-success"><i class="fas fa-database me-1"></i>Nguồn dữ liệu Dropdown</label>
                <select class="form-select form-select-sm mb-2 border-success"
                    onchange="syncFieldConfigV2('${fieldId}','dataSource.type',this.value); openFieldPanel('${fieldId}')">
                    <option value="manual"   ${dsType === 'manual' ? 'selected' : ''}>Nhập thủ công (Tự định nghĩa)</option>
                    <option value="database" ${dsType === 'database' ? 'selected' : ''}>Lấy tự động từ CSDL (Database)</option>
                </select>
                ${dsType === 'manual' ? `
                <textarea class="form-control form-control-sm" rows="3"
                    placeholder="Ví dụ: Đạt, Tốt, Không đạt"
                    oninput="syncFieldConfigV2('${fieldId}','options',this.value)"
                    >${Array.isArray(cfg.options) ? cfg.options.join(', ') : (cfg.options || '')}</textarea>
                <div class="form-text" style="font-size:0.7rem">Mỗi lựa chọn cách nhau bởi dấu phẩy (,).</div>` : `
                <div class="border border-success border-opacity-50 rounded p-2 bg-light">
                    <div class="mb-2">
                        <label class="v2-prop-sublabel">Tên Bảng (Table DB)</label>
                        <input type="text" class="form-control form-control-sm border-success"
                            placeholder="VD: departments"
                            value="${esc(ds.table || '')}"
                            oninput="syncFieldConfigV2('${fieldId}','dataSource.table',this.value)">
                    </div>
                    <div class="mb-2">
                        <label class="v2-prop-sublabel">Cột hiển thị (Label Col)</label>
                        <input type="text" class="form-control form-control-sm border-success"
                            placeholder="VD: name"
                            value="${esc(ds.labelCol || '')}"
                            oninput="syncFieldConfigV2('${fieldId}','dataSource.labelCol',this.value)">
                    </div>
                    <div class="mb-2">
                        <label class="v2-prop-sublabel">Cột giá trị lưu (Value Col)</label>
                        <input type="text" class="form-control form-control-sm"
                            placeholder="Mặc định = Label Col"
                            value="${esc(ds.valueCol || '')}"
                            oninput="syncFieldConfigV2('${fieldId}','dataSource.valueCol',this.value)">
                    </div>
                    <div class="mb-0">
                        <label class="v2-prop-sublabel">Lọc Where (Tùy chọn)</label>
                        <input type="text" class="form-control form-control-sm"
                            placeholder="VD: active=1"
                            value="${esc(ds.where || '')}"
                            oninput="syncFieldConfigV2('${fieldId}','dataSource.where',this.value)">
                    </div>
                </div>`}
            </div>`;
    } else if (cfg.type === 'radio') {
        logicHtml += `
            <hr class="opacity-25 my-2">
            <div class="mb-3">
                <label class="v2-prop-label text-success"><i class="fas fa-dot-circle me-1"></i>Danh sách lựa chọn (chỉ chọn 1)</label>
                <textarea class="form-control form-control-sm" rows="3"
                    placeholder="Ví dụ: Đạt, Không đạt, Không áp dụng"
                    oninput="syncFieldConfigV2('${fieldId}','options',this.value)"
                    >${Array.isArray(cfg.options) ? cfg.options.join(', ') : (cfg.options || '')}</textarea>
                <div class="form-text" style="font-size:0.7rem">Mỗi lựa chọn cách nhau bởi dấu phẩy (,). Khi chạy thử sẽ hiện sẵn dạng nút tròn, chạm để chọn — chỉ chọn được 1 mục.</div>
            </div>`;
    }

    // ── Tab 3: Mở rộng ───────────────────────────────────────
    const styleObj = cfg.style || {};
    let advancedHtml = `
        <div class="mb-3">
            <label class="v2-prop-label"><i class="fas fa-star text-warning me-1"></i>Thông số quan trọng (CPP/CMA)</label>
            <select class="form-select form-select-sm border-warning shadow-sm"
                onchange="syncFieldConfigV2('${fieldId}','important_var_id',this.value)">
                <option value="">-- Không --</option>
                ${importantVars.map(v =>
        `<option value="${v.id}" ${cfg.important_var_id == v.id ? 'selected' : ''}>${esc(v.name)}${v.description ? ' (' + esc(v.description) + ')' : ''}</option>`
    ).join('')}
            </select>
            <div class="form-text" style="font-size:0.65rem">Gắn cờ CPP/CMA để lọc báo cáo PVR/PQR.</div>
        </div>
        <hr class="opacity-25 my-2">
        <div class="mb-3">
            <label class="v2-prop-label text-primary"><i class="fas fa-info-circle me-1"></i>Hướng dẫn ghi chép</label>
            <textarea class="form-control form-control-sm border-primary" rows="3"
                placeholder="VD: Kiểm tra nhiệt độ trước khi ghi..."
                oninput="syncFieldConfigV2('${fieldId}','instruction',this.value)"
                >${esc(cfg.instruction || '')}</textarea>
            <div class="form-text" style="font-size:0.68rem">Hiển thị khi người thực hiện nhấp vào ô nhập.</div>
        </div>
        <hr class="opacity-25 my-2">
        <div class="mb-3">
            <div class="form-check form-switch ps-4 pt-1">
                <input class="form-check-input ms-n4" type="checkbox" id="v2fp-req-${fieldId}"
                    ${cfg.validation.required ? 'checked' : ''}
                    onchange="syncFieldConfigV2('${fieldId}','validation.required',this.checked)">
                <label class="form-check-label small fw-bold" for="v2fp-req-${fieldId}">Bắt buộc điền</label>
            </div>
        </div>
        <hr class="opacity-25 my-2">
        <div class="card bg-light border-0 shadow-none mb-3">
            <div class="card-body p-3">
                <label class="v2-prop-label mb-2">Kích thước biến <i class="fas fa-arrows-alt-h me-1"></i></label>
                <div class="row g-2 mb-2">
                    <div class="col-6">
                        <label class="v2-prop-sublabel">Rộng (px)</label>
                        <input type="number" class="form-control form-control-sm" placeholder="Mặc định" min="50"
                            value="${styleObj.width && !styleObj.width.includes('%') ? parseInt(styleObj.width) : ''}"
                            oninput="const v=this.value?this.value+'px':''; syncFieldConfigV2('${fieldId}','style.width',v); _v2ApplyBadgeStyle('${fieldId}','width',v)">
                    </div>
                    <div class="col-6">
                        <label class="v2-prop-sublabel">Lề trái (px)</label>
                        <input type="number" class="form-control form-control-sm" placeholder="Mặc định" min="0"
                            value="${styleObj.marginLeft ? parseInt(styleObj.marginLeft) : ''}"
                            oninput="const v=this.value?this.value+'px':''; syncFieldConfigV2('${fieldId}','style.marginLeft',v); _v2ApplyBadgeStyle('${fieldId}','margin-left',v)">
                    </div>
                </div>
                <div class="d-flex gap-2 mb-2">
                    <button type="button" id="v2fp-maxw-${fieldId}"
                        class="btn btn-sm ${styleObj.width === '100%' ? 'btn-primary active' : 'btn-outline-secondary'} flex-fill"
                        onclick="_v2ToggleBadgeMaxDim('${fieldId}','width','100%',this)">
                        <i class="fas fa-arrows-alt-h"></i> Max Rộng
                    </button>
                    <button type="button" id="v2fp-maxh-${fieldId}"
                        class="btn btn-sm ${styleObj.height === '100%' ? 'btn-primary active' : 'btn-outline-secondary'} flex-fill"
                        onclick="_v2ToggleBadgeMaxDim('${fieldId}','height','100%',this)">
                        <i class="fas fa-arrows-alt-v"></i> Max Cao
                    </button>
                </div>
                <div class="btn-group w-100" role="group">
                    ${['left', 'center', 'right'].map(a => `
                    <button type="button"
                        class="btn btn-sm ${styleObj.badgeAlign === a ? 'btn-primary active' : 'btn-outline-secondary'}"
                        onclick="_v2SetBadgeAlign('${fieldId}','${a}',this)">
                        <i class="fas fa-align-${a}"></i> ${a === 'left' ? 'Trái' : a === 'center' ? 'Giữa' : 'Phải'}
                    </button>`).join('')}
                </div>
            </div>
        </div>`;

    // Cân RS-232 (chỉ number)
    if (cfg.type === 'number') {
        advancedHtml += `
        <div class="card border-0 shadow-none mb-3" style="background:linear-gradient(135deg,#fef2f2,#fee2e2);border:1px solid #fecaca!important">
            <div class="card-body p-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <i class="fas fa-balance-scale text-danger"></i>
                    <label class="small fw-bold mb-0 text-danger-emphasis">Kết nối Cân điện tử</label>
                </div>
                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" id="v2fp-scale-${fieldId}"
                        ${cfg.scaleEnabled ? 'checked' : ''}
                        onchange="syncFieldConfigV2('${fieldId}','scaleEnabled',this.checked); document.getElementById('v2fp-scalebrand-${fieldId}').classList.toggle('d-none',!this.checked)">
                    <label class="form-check-label small fw-bold" for="v2fp-scale-${fieldId}">Bật RS-232 / Web Serial</label>
                </div>
                <div id="v2fp-scalebrand-${fieldId}" class="${cfg.scaleEnabled ? '' : 'd-none'} p-2 bg-white rounded border border-danger border-opacity-25">
                    <label class="v2-prop-sublabel">Hãng cân mặc định</label>
                    <select class="form-select form-select-sm" onchange="syncFieldConfigV2('${fieldId}','scalePreset',this.value)">
                        <option value="and"      ${(cfg.scalePreset || 'and') === 'and' ? 'selected' : ''}>⚖️ A&D (AND)</option>
                        <option value="mettler"  ${cfg.scalePreset === 'mettler' ? 'selected' : ''}>🏋️ Mettler Toledo</option>
                        <option value="sartorius" ${cfg.scalePreset === 'sartorius' ? 'selected' : ''}>🔬 Sartorius</option>
                        <option value="custom"   ${cfg.scalePreset === 'custom' ? 'selected' : ''}>⚙️ Tùy chỉnh</option>
                    </select>
                </div>
            </div>
        </div>`;
    }

    // Barcode (chỉ text)
    if (cfg.type === 'text') {
        advancedHtml += `
        <div class="card border-0 shadow-none mb-3" style="background:linear-gradient(135deg,#f0fdf4,#dcfce7);border:1px solid #bbf7d0!important">
            <div class="card-body p-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <i class="fas fa-barcode text-success"></i>
                    <label class="small fw-bold mb-0 text-success-emphasis">Kích hoạt Quét Barcode</label>
                </div>
                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" id="v2fp-barcode-${fieldId}"
                        ${cfg.barcodeScanEnabled ? 'checked' : ''}
                        onchange="syncFieldConfigV2('${fieldId}','barcodeScanEnabled',this.checked); openFieldPanel('${fieldId}')">
                    <label class="form-check-label small fw-bold" for="v2fp-barcode-${fieldId}">Cho phép quét Barcode</label>
                </div>
                ${cfg.barcodeScanEnabled ? `
                <div class="mb-0">
                    <label class="v2-prop-sublabel">Mã đối chiếu (Tùy chọn)</label>
                    <input type="text" class="form-control form-control-sm border-success"
                        placeholder="VD: 22120040279"
                        value="${esc(cfg.barcodeMatchValue || '')}"
                        oninput="syncFieldConfigV2('${fieldId}','barcodeMatchValue',this.value)">
                </div>` : ''}
            </div>
        </div>`;
    }

    // ── Compose 3-tab panel HTML ─────────────────────────────
    panel.innerHTML = `
        <div class="v2-panel-head">
            <span><i class="fas fa-tag me-2"></i>Biến số</span>
            <button class="btn-close-panel" id="v2fp-close"><i class="fas fa-times"></i></button>
        </div>
        <div style="overflow-y:auto; height:calc(100vh - 130px)">
            <ul class="nav nav-tabs nav-fill border-0 mb-0 v2fp-tabs" style="font-size:0.78rem; background:#f8f9fa">
                <li class="nav-item">
                    <button class="nav-link active v2fp-tab fw-bold px-1 py-2" data-pane="v2fp-pane-basic-${fieldId}"
                        style="border-top:3px solid #0d6efd; background:#fff">
                        <i class="fas fa-sliders-h d-block mb-1" style="font-size:1rem"></i>Cơ bản
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link v2fp-tab fw-bold text-success px-1 py-2" data-pane="v2fp-pane-logic-${fieldId}"
                        style="border-top:3px solid transparent; background:#f8f9fa">
                        <i class="fas fa-calculator d-block mb-1" style="font-size:1rem"></i>Công thức
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link v2fp-tab fw-bold text-warning px-1 py-2" data-pane="v2fp-pane-adv-${fieldId}"
                        style="border-top:3px solid transparent; background:#f8f9fa">
                        <i class="fas fa-tools d-block mb-1" style="font-size:1rem"></i>Mở rộng
                    </button>
                </li>
            </ul>

            <div id="v2fp-pane-basic-${fieldId}" class="v2fp-pane p-3">${basicHtml}</div>
            <div id="v2fp-pane-logic-${fieldId}" class="v2fp-pane p-3 d-none">${logicHtml}</div>
            <div id="v2fp-pane-adv-${fieldId}"   class="v2fp-pane p-3 d-none">${advancedHtml}</div>

            <div class="p-3 border-top bg-light">
                <button class="btn btn-sm btn-outline-danger w-100"
                    onclick="if(confirm('Xóa biến số này?')) deleteVariableV2('${fieldId}')">
                    <i class="fas fa-trash-alt me-1"></i>Xóa bỏ hoàn toàn
                </button>
            </div>
        </div>`;

    // ── Tab switching (thủ công, không dùng Bootstrap JS) ───
    const tabColors = { 0: '#0d6efd', 1: '#198754', 2: '#ffc107' };
    panel.querySelectorAll('.v2fp-tab').forEach((btn, idx) => {
        btn.addEventListener('click', () => {
            panel.querySelectorAll('.v2fp-tab').forEach((b, i) => {
                b.classList.remove('active');
                b.style.borderTopColor = 'transparent';
                b.style.background = '#f8f9fa';
            });
            btn.classList.add('active');
            btn.style.borderTopColor = tabColors[idx] || '#0d6efd';
            btn.style.background = '#fff';
            panel.querySelectorAll('.v2fp-pane').forEach(p => p.classList.add('d-none'));
            const pane = document.getElementById(btn.dataset.pane);
            if (pane) pane.classList.remove('d-none');
        });
    });

    panel.querySelector('#v2fp-close').onclick = () => panel.classList.remove('open');
}
BOOT.openFieldPanel = openFieldPanel;

/* ----------------------------------------------------------
 * 4d-bis. Panel SỬA HÀNG LOẠT biến số (Ctrl+Alt+quét marquee)
 *         Port batch_field_ops.blade.php của V1 sang V2
 * ---------------------------------------------------------- */
function batchSyncFieldConfigV2(path, value) {
    const ids = selection.getFieldIds();
    if (ids.length === 0) return;
    ids.forEach((id) => syncFieldConfigV2(id, path, value));
    if (path === 'type') {
        updateBatchSpecificOptionsV2(value);
        renderDocument(); // repaint badge tĩnh theo type mới
    }
}

function updateBatchSpecificOptionsV2(type) {
    const container = document.getElementById('v2-batch-specific-options');
    if (!container) return;
    let html = '';
    if (type === 'number') {
        html = `
            <div class="card bg-light border-0 shadow-none mb-3">
                <div class="card-body p-3">
                    <label class="small fw-bold mb-2"><i class="fas fa-balance-scale me-1"></i> Giới hạn giá trị chung</label>
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="small text-muted" style="font-size:0.75em;">Tối thiểu (Min)</label>
                            <input type="number" class="form-control form-control-sm" oninput="batchSyncFieldConfigV2('validation.min', this.value)">
                        </div>
                        <div class="col-6">
                            <label class="small text-muted" style="font-size:0.75em;">Tối đa (Max)</label>
                            <input type="number" class="form-control form-control-sm" oninput="batchSyncFieldConfigV2('validation.max', this.value)">
                        </div>
                    </div>
                    <div class="form-check form-switch ps-4 pt-1 mt-2">
                        <input class="form-check-input ms-n4" type="checkbox" id="v2-batch-oob" onchange="batchSyncFieldConfigV2('validation.allow_out_of_bounds', this.checked)">
                        <label class="form-check-label small text-muted" style="font-size:0.75em;" for="v2-batch-oob">Cho phép nhập ngoài giới hạn?</label>
                    </div>
                </div>
            </div>`;
    } else if (type === 'select' || type === 'radio') {
        html = `
            <div class="mb-3">
                <label class="small fw-bold text-muted text-uppercase mb-2">Danh sách lựa chọn chung</label>
                <textarea class="form-control form-control-sm" rows="3" placeholder="Ví dụ: Đạt, Tốt, Không đạt"
                    oninput="batchSyncFieldConfigV2('options', this.value)"></textarea>
            </div>`;
    }
    container.innerHTML = html;
}

function batchDeleteFieldsV2() {
    const ids = selection.getFieldIds();
    if (ids.length === 0) return;
    window.Swal.fire({
        title: 'Xác nhận xóa?',
        text: `Bạn có chắc chắn muốn xóa ${ids.length} biến số đã chọn?`,
        icon: 'warning', showCancelButton: true,
        confirmButtonColor: '#d33', confirmButtonText: 'Xóa ngay', cancelButtonText: 'Hủy',
    }).then((result) => {
        if (!result.value) return;
        saveDocState();
        deleteVariablesV2(ids);
        showToast('success', `Đã xóa ${ids.length} biến số`);
    });
}

function openBatchFieldPanelV2(fieldIds) {
    const ids = (fieldIds || []).filter((id) => fieldsConfig[id]);
    if (ids.length === 0) return;
    if (ids.length === 1) { openFieldPanel(ids[0]); return; } // 1 biến: panel thường

    const panel = document.getElementById('v2-field-panel');
    if (!panel) return;
    panel.classList.add('open');

    const firstField = fieldsConfig[ids[0]];
    panel.innerHTML = `
        <div class="v2-panel-head">
            <span><i class="fas fa-layer-group me-2"></i>Sửa hàng loạt</span>
            <button class="btn-close-panel" id="v2fp-close"><i class="fas fa-times"></i></button>
        </div>
        <div class="p-3" style="overflow-y:auto; height:calc(100vh - 130px)">
            <div class="alert alert-info py-2 mb-2 small">
                <i class="fas fa-layer-group me-1"></i> Đang chỉnh sửa <strong>${ids.length} biến số</strong> cùng lúc.
            </div>
            <div class="text-muted mb-3" style="font-size:0.65rem; max-height:40px; overflow-y:auto;">
                Mã: ${ids.map(esc).join(', ')}
            </div>

            <div class="mb-3">
                <label class="v2-prop-label">Tên thẻ chung (Nhãn hiển thị)</label>
                <input type="text" class="form-control form-control-sm" value="${esc(firstField.label || '')}"
                    placeholder="Nhập tên thẻ chung..." oninput="batchSyncFieldConfigV2('label', this.value)">
                <div class="form-text small" style="font-size:0.7rem;">Áp dụng nhãn cho tất cả biến đã chọn.</div>
            </div>

            <div class="mb-3">
                <label class="v2-prop-label">Kiểu dữ liệu chung</label>
                <select class="form-select form-select-sm" onchange="batchSyncFieldConfigV2('type', this.value)">
                    <option value="" disabled selected>-- Chọn kiểu để áp dụng cho tất cả --</option>
                    <option value="text">✒️ Văn bản (Text)</option>
                    <option value="number">🔢 Số (Number)</option>
                    <option value="date">📅 Thời gian (Date)</option>
                    <option value="select">🔘 Khóa chọn (Dropdown)</option>
                    <option value="radio">⚪ Chọn 1 trong nhiều (Radio)</option>
                    <option value="signature">✍️ Chữ ký (Signature)</option>
                    <option value="checkbox">☑️ Hộp kiểm (Checkbox)</option>
                </select>
            </div>

            <div class="mb-3">
                <div class="form-check form-switch ps-4 pt-1">
                    <input class="form-check-input ms-n4" type="checkbox" id="v2-batch-required"
                        onchange="batchSyncFieldConfigV2('validation.required', this.checked)">
                    <label class="form-check-label small fw-bold" for="v2-batch-required">Tất cả bắt buộc điền</label>
                </div>
            </div>

            <hr class="my-3">
            <div id="v2-batch-specific-options"></div>

            <div class="mt-4">
                <button class="btn btn-sm btn-outline-danger w-100" onclick="batchDeleteFieldsV2()">
                    <i class="fas fa-trash-alt me-1"></i> Xóa tất cả ${ids.length} biến
                </button>
            </div>
        </div>`;

    panel.querySelector('#v2fp-close').onclick = () => {
        panel.classList.remove('open');
        selection.clearFields();
    };
}

/* ----------------------------------------------------------
 * 4e. Helpers style badge (dùng inline trong oninput/onclick)
 * ---------------------------------------------------------- */
window.syncFieldConfigV2 = syncFieldConfigV2;
window.insertFormulaTokenV2 = insertFormulaTokenV2;
window.serializeFormulaElementV2 = serializeFormulaElementV2;
window.deserializeFormulaToHtmlV2 = deserializeFormulaToHtmlV2;
window.toggleSelectFormulaVarModeV2 = toggleSelectFormulaVarModeV2;
window.highlightFormulaReferencesV2 = highlightFormulaReferencesV2;
window.clearFormulaHighlightsV2 = clearFormulaHighlightsV2;
window.copyVariableV2 = copyVariableV2;
window.cutVariableV2 = cutVariableV2;
window.deleteVariableV2 = deleteVariableV2;
window.openFieldPanel = openFieldPanel;
window.batchSyncFieldConfigV2 = batchSyncFieldConfigV2;
window.batchDeleteFieldsV2 = batchDeleteFieldsV2;
window.openBatchFieldPanelV2 = openBatchFieldPanelV2;

function markDirty() {
    // Chạy thử/thực thi KHÔNG BAO GIỜ sửa được cấu trúc template — mọi biến động cấu trúc
    // trong 2 chế độ này (thêm dòng Cấp 2, nhân bản field Lặp nhóm...) là tạm thời/theo lô,
    // không được làm nút Lưu template chuyển "có thay đổi".
    if (BOOT.isExecutionMode) return;
    isDirtyDoc = true;
    const btn = document.getElementById('v2-btn-save');
    if (btn) {
        btn.classList.remove('btn-success');
        btn.classList.add('btn-warning');
        btn.title = 'Có thay đổi cần lưu';
    }
}

function markSaved() {
    isDirtyDoc = false;
    const btn = document.getElementById('v2-btn-save');
    if (btn) {
        btn.classList.remove('btn-warning');
        btn.classList.add('btn-success');
        btn.title = 'Đã lưu (Không có thay đổi)';
    }
}

window._v2RepaintBadge = function (fieldId) {
    document.querySelectorAll(`.v2-field-badge[data-field-id="${fieldId}"]`).forEach(el => {
        const cfg = fieldsConfig[fieldId] || {};
        const t = FIELD_TYPES[cfg.type] || FIELD_TYPES.text;
        el.querySelector('span') && (el.querySelector('span').textContent = cfg.label || cfg.name || fieldId);
    });
};

window._v2ApplyBadgeStyle = function (fieldId, prop, value) {
    document.querySelectorAll(`.v2-field-badge[data-field-id="${fieldId}"]`).forEach(el => {
        if (value) el.style.setProperty(prop, value, 'important');
        else el.style.removeProperty(prop);
    });
};

window._v2ToggleBadgeMaxDim = function (fieldId, dim, val, btn) {
    const isActive = btn.classList.contains('active');
    const realVal = isActive ? null : val;
    syncFieldConfigV2(fieldId, `style.${dim}`, realVal);
    window._v2ApplyBadgeStyle(fieldId, dim === 'width' ? 'width' : 'height', realVal || '');
    const group = btn.parentElement;
    group.querySelectorAll('button').forEach(b => {
        b.classList.remove('active', 'btn-primary');
        b.classList.add('btn-outline-secondary');
    });
    if (!isActive) { btn.classList.add('active', 'btn-primary'); btn.classList.remove('btn-outline-secondary'); }
};

window._v2SetBadgeAlign = function (fieldId, align, btn) {
    syncFieldConfigV2(fieldId, 'style.badgeAlign', align);
    const badge = document.querySelector(`.v2-field-badge[data-field-id="${fieldId}"]`);
    if (badge) {
        badge.style.setProperty('display', 'table', 'important');
        badge.style.setProperty('margin-left', align === 'center' || align === 'right' ? 'auto' : '0', 'important');
        badge.style.setProperty('margin-right', align === 'center' || align === 'left' ? 'auto' : '0', 'important');
    }
    btn.closest('.btn-group').querySelectorAll('button').forEach(b => {
        b.classList.remove('active', 'btn-primary');
        b.classList.add('btn-outline-secondary');
    });
    btn.classList.add('active', 'btn-primary');
    btn.classList.remove('btn-outline-secondary');
};

/* =========================================================
 * 5. LƯU — cùng payload incremental với trình soạn thảo cũ
 * ========================================================= */


window.addEventListener('beforeunload', (e) => {
    if (isDirtyDoc) { e.preventDefault(); e.returnValue = ''; }
});

/** Quét biến đang dùng bằng cách duyệt CONTENT (không phụ thuộc DOM đang mount) */
function collectUsedFieldIds() {
    const used = new Set();
    const scanHtml = (html) => {
        if (!html) return;
        const m = html.match(/data-field-id="([^"]+)"/g);
        if (m) m.forEach((s) => used.add(s.slice(15, -1)));
    };
    items.forEach((it) => {
        if (it.isVirtual) return;
        scanHtml(it.content);
        (it.data || []).forEach((row) => (row || []).forEach((cell) => {
            if (cell && typeof cell === 'object') scanHtml(cell.content);
        }));
    });
    // Biến được tham chiếu trong công thức cũng phải giữ
    Object.values(fieldsConfig).forEach((f) => {
        if (f.type === 'formula' && f.formula) {
            const m = f.formula.match(/field_[a-zA-Z0-9_]+/g);
            if (m) m.forEach((x) => used.add(x));
        }
    });
    return used;
}

let savingDocV2 = false; // đang có request lưu chạy — chặn double-submit (nguồn gây INSERT trùng block)

/**
 * KIỂM TRA TOÀN VẸN trước khi lưu: so sánh biến số ĐANG HIỂN THỊ trên màn hình (DOM)
 * với biến số THỰC SỰ có trong dữ liệu sắp gửi lên server (items[].content/cell.content).
 * Có trường hợp badge vẫn hiện trên màn hình (vd. do NodeView chưa kịp/không đồng bộ
 * ngược về item.data — race giữa thao tác dán + đổi tên biến ngay sau đó) nhưng chuỗi
 * content thực tế lại KHÔNG có data-field-id đó — lúc lưu, biến âm thầm biến mất dù
 * server báo "thành công" vì đơn giản là nó chưa từng được gửi lên. Hàm này chặn lại
 * TRƯỚC khi gửi, thay vì để mất dữ liệu trong im lặng.
 */
function verifyFieldBadgesConsistencyV2() {
    const pagesEl = document.getElementById('v2-pages');
    if (!pagesEl) return [];
    // Block hệ thống (isVirtual, vd HEADER/PHÊ DUYỆT) cố tình KHÔNG được collectUsedFieldIds()
    // quét tới — badge trong đó (nếu có) không phải orphan, phải loại khỏi danh sách so sánh.
    const virtualIds = new Set(items.filter((i) => i.isVirtual).map((i) => i.id));
    const domIds = new Set();
    pagesEl.querySelectorAll('[data-field-id]').forEach((el) => {
        const id = el.getAttribute('data-field-id');
        if (!id) return;
        const blockId = el.closest('.v2-block')?.getAttribute('data-id');
        if (blockId && virtualIds.has(blockId)) return;
        domIds.add(id);
    });
    if (!domIds.size) return [];
    const dataIds = collectUsedFieldIds();
    const missing = [];
    domIds.forEach((id) => { if (!dataIds.has(id)) missing.push(id); });

    // CHẨN ĐOÁN: log chi tiết từng biến bị thiếu để lần sau tái hiện có thể xác định
    // CHÍNH XÁC data thực tế đang là gì (thay vì phải đoán) — xem trong DevTools Console.
    if (missing.length) {
        missing.forEach((id) => {
            const els = Array.from(pagesEl.querySelectorAll(`[data-field-id="${id}"]`));
            const info = els.map((el) => {
                const td = el.closest('td[data-row][data-col]');
                const wrap = el.closest('.v2-table-wrap[data-id]');
                const blockEl = el.closest('.v2-block[data-id]');
                let cellContent = null, itemContent = null, itemId = null;
                if (td && wrap) {
                    const it = items.find((i) => i.id === wrap.getAttribute('data-id'));
                    const r = parseInt(td.dataset.row, 10), c = parseInt(td.dataset.col, 10);
                    itemId = it && it.id;
                    cellContent = it && it.data && it.data[r] && it.data[r][c] ? it.data[r][c].content : '(không tìm thấy cell trong item.data)';
                } else if (blockEl) {
                    const it = items.find((i) => i.id === blockEl.getAttribute('data-id'));
                    itemId = it && it.id;
                    itemContent = it ? it.content : '(không tìm thấy item)';
                }
                return {
                    domEditing: !!el.closest('.v2-editing'),
                    domOuterHTML: el.outerHTML,
                    itemId, cellContent, itemContent,
                };
            });
            console.error(`[V2] Biến "${id}" hiển thị trên DOM nhưng KHÔNG có trong dữ liệu sẽ lưu. Chi tiết:`, info);
        });
    }
    return missing;
}

function saveTemplate() {
    if (savingDocV2) return; // request trước chưa xong: block mới chưa nhận db_id, gửi tiếp sẽ bị nhân đôi
    savingDocV2 = true;
    unmountEditor(); // chốt nội dung đang gõ dở

    const missingBadges = verifyFieldBadgesConsistencyV2();
    if (missingBadges.length) {
        savingDocV2 = false;
        const labels = missingBadges.map((id) => (fieldsConfig[id] && (fieldsConfig[id].label || fieldsConfig[id].name)) || id);
        window.Swal?.fire({
            icon: 'error',
            title: 'Lưu KHÔNG thành công',
            html: 'Phát hiện ' + missingBadges.length + ' biến số đang hiển thị trên màn hình nhưng CHƯA được ghi nhận vào dữ liệu để lưu:<br><b>' + labels.map(esc).join(', ') + '</b>'
                + '<br><br>Hãy bấm ra ngoài ô/đoạn văn bản đang chứa biến đó (để chốt nội dung vừa dán), rồi bấm Lưu lại.',
            confirmButtonText: 'Đã hiểu',
        });
        return;
    }

    const dirtyFields = items.filter((i) => !i.isVirtual && (i.dirty || !i.db_id)).map((i) => ({
        db_id: i.db_id || null,
        content_db_id: i.content_db_id || null,
        id: i.id, type: i.type, label: i.label,
        content: i.content || '',
        rows: i.rows || 0, cols: i.cols || 0,
        columns: i.columns || [], data: i.data || [],
        rowHeights: i.rowHeights || [],
        borderMode: i.borderMode || 'visible',
        borderWeight: i.borderWeight || null, borderColor: i.borderColor || null,
        borderStyle: i.borderStyle || null, cellBorders: i.cellBorders || null,
        hideHeader: i.hideHeader || false,
        canAddRows: i.canAddRows || false, addRowsCount: i.addRowsCount || 1,
        freq_minutes: i.freq_minutes || null,
        locked: i.locked || false, template_id: i.template_id || null, ref_doc_code: i.ref_doc_code || null,
        showPreview: i.showPreview || false, stage_code: i.stage_code || null,
        chartConfig: i.chartConfig || null, backgroundColor: i.backgroundColor || null,
        section_id: i.section_id || null,
        isBmrHeader: i.isBmrHeader || false, isGfHeader: i.isGfHeader || false,
        isAbbreviationTable: i.isAbbreviationTable || false,
        loop_group_id: i.loop_group_id || null, loop_count: i.loop_count || null,
        loop_label: i.loop_label || null, loop_labels: i.loop_labels || null,
        typography: i.typography || null,
        cell_notes: i.cell_notes || null, conditional_logic: i.conditional_logic || null,
        textAlign: i.textAlign || null, verticalAlign: i.verticalAlign || null,
        pageBreakBefore: i.pageBreakBefore || false,
        noPageBreak: i.noPageBreak || false,
    }));

    const used = collectUsedFieldIds();
    const pruned = {};
    used.forEach((fid) => { if (fieldsConfig[fid]) pruned[fid] = fieldsConfig[fid]; });

    const btn = document.getElementById('v2-btn-save');
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Đang lưu...'; }

    fetch(BOOT.saveUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        body: JSON.stringify({
            id: BOOT.templateId,
            schema: {
                type: 'document-flow',
                pageOrientation: BOOT.pageOrientation || 'portrait',
                fieldsConfig: pruned,
                fields: dirtyFields,
                block_order: items.filter((i) => !i.isVirtual).map((i) => i.id),
                deleted_ids: deletedBlockIds,
                deleted_field_keys: deletedFieldKeys,
                docProperties: BOOT.docProperties || {},
                incremental: true,
            },
            log_history: false,
            section_id: '',
            lang: 'vi',
            _token: BOOT.csrf,
        }),
    })
        .then(async (r) => {
            // Đọc text trước rồi mới parse JSON — khi server lỗi (500) hoặc hết phiên (419)
            // thường trả về HTML, gọi thẳng r.json() sẽ văng "Unexpected token <" khó hiểu.
            const text = await r.text();
            let res = null;
            try { res = JSON.parse(text); } catch (e) { /* không phải JSON */ }
            if (r.status === 401 || r.status === 419) {
                throw new Error('Phiên đăng nhập đã HẾT HẠN. Hãy mở tab mới đăng nhập lại rồi quay lại đây bấm Lưu — nội dung đang soạn vẫn còn nguyên trên trang.');
            }
            if (!r.ok) {
                throw new Error((res && res.message) || `Máy chủ trả lỗi HTTP ${r.status}. Vui lòng thử lưu lại.`);
            }
            if (!res) throw new Error('Phản hồi của máy chủ không hợp lệ (không phải JSON).');
            return res;
        })
        .then((res) => {
            if (res.success) {
                if (res.block_ids) {
                    Object.keys(res.block_ids).forEach((fId) => {
                        const it = items.find((i) => i.id === fId);
                        if (it) {
                            const info = res.block_ids[fId];
                            if (info.db_id) it.db_id = info.db_id;
                            if (info.content_db_id) it.content_db_id = info.content_db_id;
                            // Server chỉ bổ sung db_id cho TỪNG Ô (để lần lưu sau UPDATE thay vì
                            // INSERT trùng) — phần content trong info.data chỉ là ảnh chụp lúc gửi.
                            // KHÔNG được thay cả mảng (it.data = info.data): DOM đang hiển thị giữ
                            // closure cellRef trỏ vào object ô CŨ (renderTable), thay mảng làm mọi
                            // chỉnh sửa sau đó (gõ chữ, chèn biến) ghi vào object mồ côi — biến số
                            // hiện trên màn hình nhưng biến mất khỏi dữ liệu ở lần lưu kế tiếp.
                            if (info.data && Array.isArray(it.data)) {
                                info.data.forEach((row, r) => (row || []).forEach((srvCell, c) => {
                                    const cur = it.data[r] && it.data[r][c];
                                    if (cur && typeof cur === 'object' && srvCell && typeof srvCell === 'object' && srvCell.db_id) {
                                        cur.db_id = srvCell.db_id;
                                    }
                                }));
                            }
                        }
                    });
                }
                // Chỉ hạ cờ dirty cho block ĐÃ gửi trong lượt lưu này — block được sửa
                // trong lúc request đang chạy (dirty bật sau khi payload đã chốt) phải
                // giữ nguyên cờ, kẻo lần lưu sau bỏ sót nội dung mới.
                const sentIds = new Set(dirtyFields.map((f) => f.id));
                items.forEach((i) => { if (sentIds.has(i.id)) i.dirty = false; });
                deletedBlockIds = [];
                deletedFieldKeys = [];
                markSaved();
                window.Swal?.fire({
                    toast: true, position: 'top-end', icon: 'success',
                    title: 'Đã lưu hồ sơ thành công', showConfirmButton: false, timer: 2200,
                });
            } else {
                window.Swal?.fire({
                    icon: 'error', title: 'Lưu KHÔNG thành công',
                    text: res.message || 'Không thể lưu hồ sơ. Vui lòng thử lại.',
                    confirmButtonText: 'Đóng',
                });
            }
        })
        .catch((err) => window.Swal?.fire({
            icon: 'error', title: 'Lưu KHÔNG thành công',
            text: err.message || 'Không thể kết nối máy chủ. Kiểm tra mạng rồi thử lại.',
            confirmButtonText: 'Đóng',
        }))
        .finally(() => {
            savingDocV2 = false;
            if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-save me-1"></i> Lưu'; }
        });
}

/* =========================================================
 * 5b. MỤC LỤC (TOC) — danh sách section, click cuộn tới nơi
 * ========================================================= */
// Mục lục dựng từ DOM đang hiển thị: section + TIÊU ĐỀ CÁC CẤP (h1/h2/h3, kèm số
// data-hnum nếu đánh số đang bật) — số trang lấy từ .v2-page chứa phần tử.
const TOC_NODE_SEL = '#v2-pages .v2-section[id^="v2-sec-"], #v2-pages h1, #v2-pages h2, #v2-pages h3';

function buildToc() {
    const body = document.getElementById('v2-toc-body');
    if (!body) return;
    updateHeadingNumbersV2(); // chốt data-hnum mới nhất trước khi đọc
    const pages = Array.from(document.querySelectorAll('#v2-pages .v2-page'));
    let html = '';
    document.querySelectorAll(TOC_NODE_SEL).forEach((el, i) => {
        const pageNo = pages.indexOf(el.closest('.v2-page')) + 1;
        if (el.classList.contains('v2-section')) {
            const secId = el.id.replace(/^v2-sec-/, '');
            const item = items.find((it) => it.id === secId);
            const secNum = el.querySelector('.v2-section-title')?.getAttribute('data-hnum') || '';
            html += `<div class="v2-toc-item" data-toc-idx="${i}">
                <span><i class="fas fa-layer-group me-2" style="opacity:0.5;"></i>${esc(secNum + ((item && item.label) || 'Section'))}</span>
                <span class="v2-toc-page">Tr.${pageNo}</span>
            </div>`;
        } else {
            const text = (el.textContent || '').trim();
            if (!text) return; // tiêu đề rỗng (đang gõ dở) không đưa vào mục lục
            const lv = parseInt(el.tagName.slice(1), 10); // 1..3
            const num = el.getAttribute('data-hnum') || '';
            html += `<div class="v2-toc-item v2-toc-h" style="padding-left:${12 + lv * 14}px;" data-toc-idx="${i}">
                <span class="text-truncate">${esc(num + text)}</span>
                <span class="v2-toc-page">Tr.${pageNo}</span>
            </div>`;
        }
    });
    body.innerHTML = html || '<div class="text-muted small text-center py-3">Chưa có công đoạn nào.</div>';
    body.querySelectorAll('.v2-toc-item').forEach((el) => {
        el.addEventListener('click', () => {
            // Truy vấn lại tại thời điểm click — DOM có thể đã render lại sau khi mở mục lục
            const target = document.querySelectorAll(TOC_NODE_SEL)[parseInt(el.getAttribute('data-toc-idx'), 10)];
            if (target) target.scrollIntoView({ behavior: 'smooth', block: 'center' });
        });
    });
}

/* =========================================================
 * 5c. BÌNH LUẬN VÙNG CHỌN (kiểu MS Word)
 *
 * Người dùng quét chọn một đoạn chữ -> bấm bong bóng bình luận -> nhập nội dung.
 * Đoạn đó được tô nền tím và nối bằng một đường kẻ sang card bình luận nằm ở rail
 * bên phải trang giấy. Card hỗ trợ trả lời (thread) và xoá.
 *
 * NEO (anchor) — điểm mấu chốt:
 *   Vùng chọn KHÔNG được ghi vào item.content dưới dạng thẻ <span>. Thay vào đó nó được
 *   mô tả bằng `selection_data` = {blockId, r, c, start, end, quote, prefix, suffix} và
 *   highlight được VẼ LẠI lên DOM sau mỗi lượt render. Nhờ vậy:
 *     • nội dung template sạch tuyệt đối — không có markup bình luận rò xuống trang
 *       Thực thi / In / Export;
 *     • tạo và xoá bình luận có hiệu lực ngay, không cần bấm Lưu template;
 *     • xoá bình luận là mất sạch highlight, không để lại rác cần dọn.
 *   Đổi lại, khi người dùng sửa chữ phía trước đoạn neo thì {start,end} lệch đi. Lúc đó
 *   cmtLocate() dò lại bằng `quote` (chọn lần xuất hiện gần offset cũ nhất) rồi ghi offset
 *   mới về server qua cmtQueueReanchor(). Nếu đoạn neo bị xoá hẳn, bình luận thành "mồ côi":
 *   vẫn hiện ở rail (viền vàng, không có đường nối) chứ không biến mất.
 *
 * OFFSET được đếm trên DOM ĐÃ RENDER (sau decorateBadges), không phải trên HTML gốc —
 * cả lúc bắt vùng chọn lẫn lúc dò lại đều dùng cùng một phép duyệt text node nên khớp nhau.
 * ========================================================= */

const CMT_CONTEXT_LEN = 32; // số ký tự trước/sau đoạn neo lưu lại để phân biệt các đoạn trùng chữ

let cmtPending = null;      // bình luận đang soạn (chưa gửi): { sd }
let cmtActiveId = null;     // id bình luận đang được chọn (tô đậm + đường nối đậm)
// Vùng chọn gần nhất còn hợp lệ. Bấm bong bóng bình luận sẽ kích hoạt handler mousedown
// toàn cục -> unmountEditor() -> DOM dựng lại và selection biến mất TRƯỚC khi sự kiện click
// tới được cmtStartNew(). Cache này giữ lại neo đã bắt được để không mất vùng chọn.
let cmtLastSel = null;

/* ---------- 5c.1 Tiện ích chung ---------- */

function cmtVisible() {
    return document.body.classList.contains('v2-cmt-on');
}

/** selection_data có thể là chuỗi JSON (từ DB) hoặc object (vừa tạo tại client). */
function cmtSel(c) {
    if (!c.selection_data) return null;
    if (typeof c.selection_data === 'object') return c.selection_data;
    try {
        const sd = JSON.parse(c.selection_data);
        c.selection_data = sd; // chuẩn hoá về object để các lần sau khỏi parse lại
        return sd && typeof sd === 'object' && sd.blockId ? sd : null;
    } catch (e) {
        return null;
    }
}

/** content trong DB có thể là text thuần hoặc JSON {text, replies[]} */
function parseCommentContent(raw) {
    try {
        const d = JSON.parse(raw);
        if (d && typeof d === 'object' && d.text !== undefined) {
            return { text: d.text, replies: d.replies || [] };
        }
    } catch (e) { /* text thuần */ }
    return { text: raw, replies: [] };
}

/* ---------- 5c.2 Định vị "root" neo (khối văn bản hoặc ô bảng) ---------- */

/** Phần tử .v2-editable chứa node — đơn vị nhỏ nhất mà offset được tính bên trong. */
function cmtRootOf(node) {
    const el = node && node.nodeType === 1 ? node : (node && node.parentElement);
    return el ? el.closest('.v2-editable') : null;
}

/** Khoá nhận diện root: khối văn bản {blockId,r:null} hoặc ô bảng {blockId,r,c}. */
function cmtRootKey(root) {
    const blockEl = root.closest('#v2-pages .v2-block[data-id]');
    if (!blockEl) return null;
    const blockId = blockEl.getAttribute('data-id');
    const td = root.closest('td[data-row][data-col]');
    if (td) return { blockId, r: parseInt(td.dataset.row, 10), c: parseInt(td.dataset.col, 10) };
    return { blockId, r: null, c: null };
}

/** Tìm lại root trong DOM hiện tại từ selection_data. */
function cmtFindRoot(sd) {
    const blockEl = document.querySelector(`#v2-pages .v2-block[data-id="${window.CSS.escape(sd.blockId)}"]`);
    if (!blockEl) return null;
    if (sd.r === null || sd.r === undefined) return blockEl.querySelector('.v2-editable');
    const td = blockEl.querySelector(`td[data-row="${sd.r}"][data-col="${sd.c}"]`);
    return td ? td.querySelector('.v2-editable') : null;
}

/* ---------- 5c.3 Quy đổi offset ký tự <-> Range ---------- */

function cmtTextNodes(root) {
    const out = [];
    const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, null);
    let n;
    while ((n = walker.nextNode())) out.push(n);
    return out;
}

function cmtRootText(root) {
    return cmtTextNodes(root).map((t) => t.nodeValue).join('');
}

/** Offset ký tự của một điểm (node,offset) tính từ đầu root. */
function cmtOffsetOfPoint(root, node, offset) {
    const r = document.createRange();
    r.setStart(root, 0);
    r.setEnd(node, offset);
    return r.toString().length;
}

function cmtRangeFromOffsets(root, start, end) {
    const range = document.createRange();
    let acc = 0, started = false;
    for (const t of cmtTextNodes(root)) {
        const len = t.nodeValue.length;
        if (!started && start <= acc + len) { range.setStart(t, start - acc); started = true; }
        if (started && end <= acc + len) { range.setEnd(t, end - acc); return range; }
        acc += len;
    }
    return null;
}

/* ---------- 5c.4 Bắt vùng chọn & dò lại vị trí neo ---------- */

/** Dựng selection_data từ vùng chọn hiện tại. Trả null nếu không chọn được (rỗng, xuyên khối...). */
function cmtCaptureSelection() {
    const sel = window.getSelection();
    if (!sel || sel.rangeCount === 0 || sel.isCollapsed || !String(sel).trim()) return null;
    const range = sel.getRangeAt(0);
    const root = cmtRootOf(range.startContainer);
    // Chỉ nhận vùng chọn NẰM GỌN trong một khối/ô — bình luận xuyên khối không có neo rõ ràng
    if (!root || !root.contains(range.endContainer)) return null;
    const key = cmtRootKey(root);
    if (!key) return null;

    const start = cmtOffsetOfPoint(root, range.startContainer, range.startOffset);
    const end = cmtOffsetOfPoint(root, range.endContainer, range.endOffset);
    if (end <= start) return null;

    const full = cmtRootText(root);
    return {
        ...key,
        start,
        end,
        quote: full.slice(start, end),
        prefix: full.slice(Math.max(0, start - CMT_CONTEXT_LEN), start),
        suffix: full.slice(end, end + CMT_CONTEXT_LEN),
    };
}

/**
 * Dò vị trí đoạn neo trong root.
 * Trả { start, end, drift } — drift=true nghĩa là offset đã lệch và cần ghi lại về server.
 * Trả null nếu đoạn neo không còn tồn tại (bình luận mồ côi).
 */
function cmtLocate(root, sd) {
    const full = cmtRootText(root);
    if (!sd.quote) return null;
    if (full.slice(sd.start, sd.end) === sd.quote) return { start: sd.start, end: sd.end, drift: false };

    // Chữ đã bị sửa -> tìm lại `quote`. Nếu đoạn đó xuất hiện nhiều lần, chọn lần gần
    // offset cũ nhất; hoà nhau thì ưu tiên lần có prefix khớp (phân biệt các ô/dòng giống hệt).
    let best = -1, bestScore = Infinity;
    for (let i = full.indexOf(sd.quote); i !== -1; i = full.indexOf(sd.quote, i + 1)) {
        const prefixOk = !sd.prefix || full.slice(Math.max(0, i - sd.prefix.length), i) === sd.prefix;
        const score = Math.abs(i - sd.start) - (prefixOk ? 0.5 : 0);
        if (score < bestScore) { bestScore = score; best = i; }
    }
    if (best < 0) return null;
    return { start: best, end: best + sd.quote.length, drift: true };
}

/** Ghi offset mới về server (im lặng) sau khi dò lại được đoạn neo bị lệch. */
function cmtQueueReanchor(comment, sd) {
    if (!BOOT.commentUrls?.reanchor || BOOT.isReadOnly) return;
    fetch(BOOT.commentUrls.reanchor, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        body: JSON.stringify({ id: parseInt(comment.id, 10), selection_data: sd, _token: BOOT.csrf }),
    }).catch(() => { /* lệch offset không phải lỗi chặn người dùng — lần sau dò lại bằng quote */ });
}

/* ---------- 5c.5 Vẽ / xoá highlight trên DOM ---------- */

function cmtClearHighlights() {
    document.querySelectorAll('#v2-pages .v2-cmt-hl').forEach((span) => {
        const parent = span.parentNode;
        if (!parent) return;
        while (span.firstChild) parent.insertBefore(span.firstChild, span);
        parent.removeChild(span);
        parent.normalize(); // gộp lại các text node đã bị splitText cắt ra
    });
}

/**
 * Bọc đoạn [start,end) trong root bằng các <span class="v2-cmt-hl">.
 * Vùng chọn có thể trải qua nhiều text node (in đậm, xuống dòng...) nên phải bọc từng mảnh.
 * Bọc KHÔNG làm đổi text content của root, nên các bình luận sau vẫn dùng đúng offset cũ.
 */
function cmtHighlight(root, start, end, cid) {
    const jobs = [];
    let acc = 0;
    for (const node of cmtTextNodes(root)) {
        const len = node.nodeValue.length;
        const s = Math.max(start, acc), e = Math.min(end, acc + len);
        // Badge biến số là DOM sinh ra (contenteditable=false) — vẫn tính vào offset nhưng
        // không bọc span vào trong, tránh phá cấu trúc badge.
        if (e > s && !node.parentElement.closest('[contenteditable="false"]')) {
            jobs.push({ node, s: s - acc, e: e - acc });
        }
        acc += len;
        if (acc >= end) break;
    }
    jobs.forEach(({ node, s, e }) => {
        let n = node;
        if (e < n.nodeValue.length) n.splitText(e);
        if (s > 0) n = n.splitText(s);
        const span = document.createElement('span');
        span.className = 'v2-cmt-hl';
        span.dataset.cid = String(cid);
        n.parentNode.insertBefore(span, n);
        span.appendChild(n);
    });
    return jobs.length > 0;
}

/* ---------- 5c.6 Rail: card bình luận + đường nối ---------- */

function cmtCardHtml(c) {
    const d = parseCommentContent(c.content);
    const sd = cmtSel(c);
    const replies = d.replies.map((r) => `
        <div class="v2-comment-reply">
            <span class="v2-comment-author">${esc(r.user_name)}</span>
            <span class="v2-comment-time ms-1">${esc(String(r.created_at || '').slice(0, 16))}</span>
            <div class="v2-comment-text">${esc(r.content)}</div>
        </div>`).join('');
    const quote = sd && sd.quote
        ? `<div class="v2-comment-quote" title="${esc(sd.quote)}">${esc(sd.quote)}</div>` : '';
    const orphanNote = c.__orphan
        ? '<div class="v2-comment-quote" style="color:#b45309;background:#fef3c7;border-left-color:#f59e0b;">Đoạn văn bản neo đã bị xoá hoặc sửa</div>' : '';
    return `
        <div class="v2-comment-card${c.__orphan ? ' v2-cmt-orphan' : ''}" data-cid="${c.id}">
            ${orphanNote}${quote}
            <div class="d-flex justify-content-between">
                <span class="v2-comment-author"><i class="fas fa-user-circle me-1"></i>${esc(c.user_name || '?')}</span>
                <span class="v2-comment-time">${esc(String(c.created_at || '').slice(0, 16))}</span>
            </div>
            <div class="v2-comment-text">${esc(d.text)}</div>
            ${replies}
            <div class="v2-comment-actions">
                <a data-reply="${c.id}"><i class="fas fa-reply me-1"></i>Trả lời</a>
            </div>
        </div>`;
}

/** Card soạn bình luận mới — neo vào highlight tạm (data-cid="__new"). */
function cmtComposerHtml() {
    return `
        <div class="v2-comment-card active v2-cmt-editor" data-cid="__new">
            <div class="v2-comment-quote">${esc(cmtPending.sd.quote)}</div>
            <textarea class="form-control form-control-sm" rows="2" id="v2-cmt-new-input"
                placeholder="Nhập nội dung bình luận..."></textarea>
            <div class="v2-cmt-editor-btns">
                <button type="button" class="btn btn-sm btn-light" data-cmt-cancel>Hủy</button>
                <button type="button" class="btn btn-sm btn-navy text-white" data-cmt-submit>
                    <i class="fas fa-paper-plane me-1"></i>Gửi
                </button>
            </div>
        </div>`;
}

/** Dựng lại toàn bộ card trong rail, theo THỨ TỰ XUẤT HIỆN của highlight trong tài liệu. */
function cmtRenderRail() {
    const rail = document.getElementById('v2-cmt-rail');
    if (!rail) return;
    const all = BOOT.comments || [];

    // Thứ tự đọc: lấy theo highlight đã vẽ trên DOM. Card soạn thảo ("__new") phải nằm ĐÚNG
    // vị trí đọc của nó chứ không phải đầu rail — cmtLayout() xếp card theo thứ tự DOM và đẩy
    // dần xuống, nên một composer đặt sai chỗ sẽ kéo mọi bình luận phía trên nó tụt xuống theo.
    const order = [];
    document.querySelectorAll('#v2-pages .v2-cmt-hl').forEach((hl) => {
        const cid = hl.dataset.cid;
        if (!order.includes(cid)) order.push(cid);
    });
    const seen = new Set(order);

    let html = order.map((cid) => {
        if (cid === '__new') return cmtPending ? cmtComposerHtml() : '';
        const c = all.find((x) => String(x.id) === cid);
        return c ? cmtCardHtml(c) : '';
    }).join('');
    // Composer chưa dò được đoạn neo (VD: khối đang mở editor) -> vẫn phải hiện, đặt lên đầu
    if (cmtPending && !seen.has('__new')) html = cmtComposerHtml() + html;
    // Bình luận mồ côi / khối đang sửa: không có highlight -> xếp cuối, không có đường nối
    html += all.filter((c) => !seen.has(String(c.id))).map(cmtCardHtml).join('');

    rail.innerHTML = html;
    cmtBindRail(rail);
    cmtLayout();
    if (cmtPending) document.getElementById('v2-cmt-new-input')?.focus();
}

/**
 * Đặt mỗi card thẳng hàng với đoạn neo của nó, đẩy xuống khi chồng nhau (như Word),
 * rồi vẽ đường nối từ mép phải highlight sang mép trái card.
 * Toạ độ tính tương đối với #v2-canvas-wrap (position:relative) nên không cần bám scroll.
 */
function cmtLayout() {
    const wrap = document.getElementById('v2-canvas-wrap');
    const rail = document.getElementById('v2-cmt-rail');
    const svg = document.getElementById('v2-cmt-links');
    if (!wrap || !rail || !svg) return;
    if (!cmtVisible()) { svg.innerHTML = ''; return; }

    const wrapRect = wrap.getBoundingClientRect();
    const page = document.querySelector('#v2-pages .v2-page');
    // Rail phải NẰM GỌN trong wrap: tràn ra ngoài sẽ sinh thanh cuộn ngang cho cả trang, và
    // khi cuộn ngang thì thanh công cụ (sticky theo trục dọc) trượt xuống dưới sidebar trái.
    const RAIL_W = 320, RAIL_GAP = 28;
    const railLeft = Math.max(8, Math.min(
        page ? page.getBoundingClientRect().right - wrapRect.left + RAIL_GAP : Infinity,
        wrap.clientWidth - RAIL_W - 8,
    ));
    rail.style.left = railLeft + 'px';

    const paths = [];
    let prevBottom = 12;
    rail.querySelectorAll('.v2-comment-card').forEach((card) => {
        const cid = card.dataset.cid;
        const hl = document.querySelector(`#v2-pages .v2-cmt-hl[data-cid="${window.CSS.escape(cid)}"]`);
        let top = prevBottom;
        if (hl) {
            const r = hl.getBoundingClientRect();
            top = Math.max(r.top - wrapRect.top, prevBottom);
        }
        card.style.top = top + 'px';
        prevBottom = top + card.offsetHeight + 10;

        if (hl) {
            const r = hl.getBoundingClientRect();
            const x1 = r.right - wrapRect.left;
            const y1 = (r.top + r.bottom) / 2 - wrapRect.top;
            const y2 = top + 14; // ngang dòng đầu của card
            const active = cid === String(cmtActiveId) ? ' active' : '';
            paths.push(`<path class="v2-cmt-link${active}" data-cid="${esc(cid)}" `
                + `d="M ${x1} ${y1} H ${railLeft - 16} V ${y2} H ${railLeft}" />`);
        }
    });

    const h = Math.max(wrap.scrollHeight, prevBottom + 20);
    svg.setAttribute('height', h);
    svg.style.height = h + 'px';
    svg.innerHTML = paths.join('');
}

function cmtBindRail(rail) {
    rail.querySelectorAll('.v2-comment-card').forEach((card) => {
        card.addEventListener('click', (e) => {
            if (e.target.closest('a, button, textarea')) return;
            cmtSetActive(card.dataset.cid, true);
        });
    });
    rail.querySelector('[data-cmt-cancel]')?.addEventListener('click', cmtCancelPending);
    rail.querySelector('[data-cmt-submit]')?.addEventListener('click', cmtSubmitPending);
    rail.querySelectorAll('[data-reply]').forEach((el) =>
        el.addEventListener('click', () => cmtOpenReplyBox(el.closest('.v2-comment-card'), el.getAttribute('data-reply'))));
}

/** Làm nổi bật cặp (highlight, card) đang chọn; scroll=true thì cuộn tới đoạn văn bản. */
function cmtSetActive(cid, scrollToAnchor) {
    cmtActiveId = cid === cmtActiveId ? null : cid;
    document.querySelectorAll('#v2-pages .v2-cmt-hl').forEach((h) =>
        h.classList.toggle('active', h.dataset.cid === String(cmtActiveId)));
    document.querySelectorAll('#v2-cmt-rail .v2-comment-card').forEach((c) =>
        c.classList.toggle('active', c.dataset.cid === String(cmtActiveId)));
    document.querySelectorAll('.v2-cmt-link').forEach((p) =>
        p.classList.toggle('active', p.dataset.cid === String(cmtActiveId)));
    if (scrollToAnchor && cmtActiveId) {
        document.querySelector(`#v2-pages .v2-cmt-hl[data-cid="${window.CSS.escape(cmtActiveId)}"]`)
            ?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
}

/**
 * Vẽ lại toàn bộ highlight + rail. Gọi sau mỗi renderDocument()/unmountEditor().
 * Khối đang mở editor (.v2-editing) bị bỏ qua: DOM của TipTap do ProseMirror quản lý,
 * chèn span lạ vào sẽ bị nó dọn hoặc gây lỗi vị trí con trỏ.
 */
function cmtApplyAll() {
    cmtClearHighlights();
    cmtUpdateToggleBtn(); // số đếm phải đúng cả khi đang ẩn (vừa thêm/xoá bình luận)
    if (!cmtVisible()) {
        document.getElementById('v2-cmt-links')?.replaceChildren();
        return;
    }
    (BOOT.comments || []).forEach((c) => {
        c.__orphan = false;
        const sd = cmtSel(c);
        if (!sd) { c.__orphan = true; return; } // bình luận cấp khối cũ (chưa có neo vùng chọn)
        const root = cmtFindRoot(sd);
        if (!root || root.classList.contains('v2-editing')) { c.__orphan = !root; return; }
        const loc = cmtLocate(root, sd);
        if (!loc) { c.__orphan = true; return; }
        if (loc.drift) {
            sd.start = loc.start;
            sd.end = loc.end;
            cmtQueueReanchor(c, sd);
        }
        if (!cmtHighlight(root, loc.start, loc.end, c.id)) c.__orphan = true;
    });
    if (cmtPending) {
        const root = cmtFindRoot(cmtPending.sd);
        if (root && !root.classList.contains('v2-editing')) {
            const loc = cmtLocate(root, cmtPending.sd);
            if (loc) {
                // Neo bắt từ DOM của editor thường lệch vài ký tự so với DOM tĩnh — chốt lại
                // theo vị trí vừa dò được, nếu không lúc Gửi sẽ lưu offset sai vào DB.
                cmtPending.sd.start = loc.start;
                cmtPending.sd.end = loc.end;
                cmtHighlight(root, loc.start, loc.end, '__new');
                document.querySelectorAll('#v2-pages .v2-cmt-hl[data-cid="__new"]')
                    .forEach((el) => el.classList.add('v2-cmt-pending'));
            }
        }
    }
    cmtRenderRail();
}

/* ---------- 5c.7 Tạo / trả lời ---------- */

/** Mở ô soạn bình luận cho vùng đang chọn. Lối vào duy nhất: menu chuột phải. */
function cmtStartNew() {
    const sd = cmtCaptureSelection() || cmtLastSel;
    if (!sd) {
        showToast('info', 'Hãy quét chọn đoạn văn bản cần bình luận trước.');
        return;
    }
    // Neo bắt trong DOM của TipTap có thể lệch vài ký tự so với DOM tĩnh (badge biến số render
    // khác nhau). Đóng editor trước để chốt nội dung, sau đó cmtLocate() dò lại bằng `quote`.
    unmountEditor();
    cmtPending = { sd };
    cmtLastSel = null;
    if (!cmtVisible()) cmtToggle(true);
    else cmtApplyAll();
    window.getSelection()?.removeAllRanges();
}

function cmtCancelPending() {
    cmtPending = null;
    cmtApplyAll();
}

async function cmtSubmitPending() {
    const input = document.getElementById('v2-cmt-new-input');
    const content = (input?.value || '').trim();
    if (!content || !cmtPending) return;
    const sd = cmtPending.sd;
    const res = await fetch(BOOT.commentUrls.store, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        body: JSON.stringify({
            template_id: BOOT.templateId,
            content,
            selection_id: sd.blockId,
            selection_data: sd,
            _token: BOOT.csrf,
        }),
    }).then((r) => r.json()).catch(() => ({ success: false }));

    if (!res.success) {
        window.Swal?.fire('Lỗi', 'Không thể gửi bình luận', 'error');
        return;
    }
    BOOT.comments = BOOT.comments || [];
    BOOT.comments.push({ ...res.comment, selection_data: sd });
    cmtPending = null;
    cmtActiveId = String(res.comment.id);
    cmtApplyAll();
}

/** Ô trả lời hiện ngay trong card (như Word), thay cho hộp thoại Swal. */
function cmtOpenReplyBox(card, id) {
    if (card.querySelector('.v2-cmt-reply-box')) return;
    const box = document.createElement('div');
    box.className = 'v2-cmt-reply-box v2-cmt-editor mt-2';
    box.innerHTML = `
        <textarea class="form-control form-control-sm" rows="2" placeholder="Nhập nội dung trả lời..."></textarea>
        <div class="v2-cmt-editor-btns">
            <button type="button" class="btn btn-sm btn-light" data-cancel>Hủy</button>
            <button type="button" class="btn btn-sm btn-navy text-white" data-send>Trả lời</button>
        </div>`;
    card.appendChild(box);
    const ta = box.querySelector('textarea');
    ta.focus();
    box.querySelector('[data-cancel]').addEventListener('click', () => { box.remove(); cmtLayout(); });
    box.querySelector('[data-send]').addEventListener('click', async () => {
        const value = ta.value.trim();
        if (!value) return;
        const res = await fetch(BOOT.commentUrls.reply, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
            body: JSON.stringify({ id: parseInt(id, 10), content: value, _token: BOOT.csrf }),
        }).then((r) => r.json()).catch(() => ({ success: false }));
        if (!res.success) { window.Swal.fire('Lỗi', res.message || 'Không thể trả lời', 'error'); return; }
        const c = (BOOT.comments || []).find((x) => String(x.id) === String(id));
        if (c) {
            const d = parseCommentContent(c.content);
            d.replies.push(res.reply);
            c.content = JSON.stringify({ text: d.text, replies: d.replies });
        }
        cmtRenderRail();
    });
    cmtLayout(); // card cao thêm -> xếp lại các card phía dưới
}

/* ---------- 5c.8 Điều hướng: nhảy tới bình luận kế tiếp ---------- */

/**
 * Nhảy tới bình luận kế tiếp theo THỨ TỰ ĐỌC của tài liệu (thứ tự card trong rail),
 * quay vòng về đầu khi hết. Tự bật rail nếu đang ẩn.
 */
function cmtGotoNext() {
    if (!(BOOT.comments || []).length) {
        showToast('info', 'Hồ sơ chưa có bình luận nào.');
        return;
    }
    if (!cmtVisible()) cmtToggle(true);

    const ids = Array.from(document.querySelectorAll('#v2-cmt-rail .v2-comment-card'))
        .map((c) => c.dataset.cid)
        .filter((cid) => cid !== '__new');
    if (!ids.length) return;

    const cur = ids.indexOf(String(cmtActiveId));
    const next = ids[(cur + 1) % ids.length];

    cmtActiveId = null; // cmtSetActive() bật/tắt theo id — ép nó luôn CHỌN chứ không tắt
    cmtSetActive(next, true);

    // Bình luận mồ côi không còn đoạn neo để cuộn tới -> cuộn tới chính card của nó
    if (!document.querySelector(`#v2-pages .v2-cmt-hl[data-cid="${window.CSS.escape(next)}"]`)) {
        document.querySelector(`#v2-cmt-rail .v2-comment-card[data-cid="${window.CSS.escape(next)}"]`)
            ?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
}

/* ---------- 5c.9 Bật/tắt + gắn sự kiện ---------- */

function cmtToggle(force) {
    const on = force === undefined ? !cmtVisible() : !!force;
    document.body.classList.toggle('v2-cmt-on', on);
    document.getElementById('v2-btn-comments')?.classList.toggle('active', on);
    if (!on) cmtPending = null;
    cmtApplyAll();
}

/** Bình luận mặc định ẩn -> nút toolbar phải cho biết hồ sơ đang có bao nhiêu bình luận. */
function cmtUpdateToggleBtn() {
    const btn = document.getElementById('v2-btn-comments');
    if (!btn) return;
    const n = (BOOT.comments || []).length;
    btn.innerHTML = '<i class="fas fa-comment-dots"></i>'
        + (n ? `<span class="v2-cmt-badge">${n}</span>` : '');
    btn.title = n
        ? `${cmtVisible() ? 'Ẩn' : 'Hiện'} ${n} bình luận`
        : 'Chưa có bình luận (quét chọn đoạn chữ rồi bấm chuột phải để thêm)';
}

function initCommentsV2() {
    document.getElementById('v2-btn-comments')?.addEventListener('click', () => cmtToggle());
    document.getElementById('v2-btn-next-comment')?.addEventListener('click', cmtGotoNext);

    // Bấm vào đoạn được bình luận -> làm nổi card tương ứng. KHÔNG chặn sự kiện: click vẫn
    // phải chạy tiếp xuống handler của khối để mở editor như mọi chỗ khác trong tài liệu.
    document.getElementById('v2-pages')?.addEventListener('click', (e) => {
        const hl = e.target.closest('.v2-cmt-hl');
        if (!hl || hl.dataset.cid === '__new') return;
        cmtSetActive(hl.dataset.cid, false);
        document.querySelector(`#v2-cmt-rail .v2-comment-card[data-cid="${window.CSS.escape(hl.dataset.cid)}"]`)
            ?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }, true);

    window.addEventListener('resize', cmtLayout);
    // Mặc định ẨN: mở tài liệu là thấy đúng bản in, không bị rail và highlight che.
    // Nút toolbar (#v2-btn-comments) bật/tắt; tạo bình luận mới cũng tự bật.
}

/* =========================================================
 * 5c2. SIDEBAR THIẾT BỊ LIÊN QUAN — kéo thả tạo bảng (như V1)
 * ========================================================= */
let equipmentCache = null;

async function loadEquipmentList() {
    const deptSel = document.getElementById('v2-eq-dept');
    const listEl = document.getElementById('v2-eq-list');
    const dept = deptSel.value || (deptSel.options.length <= 1 ? (BOOT.templateDepartmentCode || '') : '');
    listEl.innerHTML = '<div class="text-center text-muted small py-3"><div class="spinner-border spinner-border-sm me-1"></div> Đang tải...</div>';

    const res = await fetch(`${BOOT.urls.equipmentList}?department=${encodeURIComponent(dept)}`)
        .then((r) => r.json()).catch(() => null);
    if (!res || !res.success) {
        listEl.innerHTML = '<div class="text-danger small text-center py-3">Lỗi tải dữ liệu thiết bị.</div>';
        return;
    }
    if (deptSel.options.length <= 1) {
        (res.departments || []).forEach((d) => {
            const o = document.createElement('option');
            o.value = d; o.textContent = 'Phân xưởng ' + d;
            deptSel.appendChild(o);
        });
        if (Array.from(deptSel.options).some((o) => o.value === dept)) deptSel.value = dept;
    }
    equipmentCache = res.equipments || [];
    renderEquipmentList();
}

function renderEquipmentList() {
    const el = document.getElementById('v2-eq-list');
    const kw = (document.getElementById('v2-eq-search').value || '').toLowerCase();
    const list = (equipmentCache || []).filter((it) =>
        !kw || (it.name || '').toLowerCase().includes(kw) || (it.code || '').toLowerCase().includes(kw));
    if (list.length === 0) {
        el.innerHTML = '<div class="text-muted small text-center py-3">Không tìm thấy thiết bị nào.</div>';
        return;
    }
    el.innerHTML = list.map((it) => `
        <label class="v2-drag-card" draggable="true" data-eq='${JSON.stringify(it).replace(/'/g, '&#39;')}'>
            <input type="checkbox" class="v2-eq-check form-check-input mt-1" onclick="event.stopPropagation()">
            <div style="min-width:0;">
                <div class="fw-bold text-truncate" title="${esc(it.name)}">${esc(it.name)}</div>
                <div class="small-muted"><i class="fas fa-barcode me-1"></i>${esc(it.code)}
                    <span class="badge bg-secondary ms-1" style="font-size:0.6rem;">${esc(it.department_code || '')}</span></div>
            </div>
        </label>`).join('');

    el.querySelectorAll('.v2-drag-card').forEach((card) => {
        card.addEventListener('dragstart', (e) => {
            const cb = card.querySelector('.v2-eq-check');
            if (cb && !cb.checked) cb.checked = true;
            const chosen = Array.from(el.querySelectorAll('.v2-eq-check:checked'))
                .map((c) => JSON.parse(c.closest('.v2-drag-card').getAttribute('data-eq')));
            e.dataTransfer.setData('action', 'insertEquipmentTable');
            e.dataTransfer.setData('equipmentData', JSON.stringify(chosen));
            e.dataTransfer.effectAllowed = 'copy';
            document.body.classList.add('v2-dragging');
        });
    });
}

/** Tạo block "Danh sách thiết bị liên quan" — cấu trúc bảng y hệt V1 (5 cột + link SOP) */
function insertEquipmentTableV2(afterIndex, equipments) {
    if (BOOT.isReadOnly || BOOT.isExecutionMode) return; // Chạy thử: không cho chèn khối mới
    const grouped = {};
    equipments.forEach((eq) => {
        if (!grouped[eq.name]) {
            grouped[eq.name] = { name: eq.name, codes: [], op_sop: eq.operation_SOP_code || '', cl_sop: eq.clearing_SOP_code || '' };
        }
        grouped[eq.name].codes.push(eq.code);
    });
    const rows = Object.values(grouped);
    if (rows.length === 0) return;

    const sopLink = (code, label) => {
        if (!code) return '';
        const url = BOOT.urls.docViewBase.replace('__CODE__', encodeURIComponent(code));
        return `<a href="${url}" target="_blank" class="badge bg-light text-primary border border-primary text-decoration-none" title="${label}: ${esc(code)}" contenteditable="false"><i class="fas fa-file-pdf text-primary me-1"></i> ${esc(code)}</a>`;
    };

    const data = [[
        { content: '<p style="text-align: center;"><strong>STT</strong></p>', rs: 1, cs: 1, hidden: false },
        { content: '<p style="text-align: center;"><strong>Tên thiết bị</strong></p>', rs: 1, cs: 1, hidden: false },
        { content: '<p style="text-align: center;"><strong>Mã số thiết bị</strong></p>', rs: 1, cs: 1, hidden: false },
        { content: '<p style="text-align: center;"><strong>Số SOP vận hành</strong></p>', rs: 1, cs: 1, hidden: false },
        { content: '<p style="text-align: center;"><strong>Số SOP vệ sinh</strong></p>', rs: 1, cs: 1, hidden: false },
    ]];
    rows.forEach((row, i) => {
        data.push([
            { content: `<p style="text-align: center;">${i + 1}.</p>`, rs: 1, cs: 1, hidden: false },
            { content: `<p>${esc(row.name)}</p>`, rs: 1, cs: 1, hidden: false },
            { content: `<p style="text-align: center;">${row.codes.map(esc).join('<br>')}</p>`, rs: 1, cs: 1, hidden: false },
            { content: `<p style="text-align: center;">${sopLink(row.op_sop, 'Xem SOP Vận Hành')}</p>`, rs: 1, cs: 1, hidden: false },
            { content: `<p style="text-align: center;">${sopLink(row.cl_sop, 'Xem SOP Vệ Sinh')}</p>`, rs: 1, cs: 1, hidden: false },
        ]);
    });

    const anchor = items[afterIndex] || null;
    const sectionId = anchor ? (anchor.type === 'section' ? (anchor.section_id || anchor.id) : anchor.section_id) : null;

    saveDocState();
    items.splice(Math.min(afterIndex + 1, items.length), 0, {
        id: newBlockId(), type: 'table',
        label: 'Danh sách thiết bị liên quan',
        rows: rows.length + 1, cols: 5,
        columns: [
            { label: 'STT', type: 'text', width: '8%' },
            { label: 'Tên thiết bị', type: 'text', width: '32%' },
            { label: 'Mã số thiết bị', type: 'text', width: '25%' },
            { label: 'Số SOP vận hành', type: 'text', width: '17.5%' },
            { label: 'Số SOP vệ sinh', type: 'text', width: '17.5%' },
        ],
        data, align: 'center', hideHeader: true,
        properties: { allowRowSplit: true, repeatHeader: true },
        section_id: sectionId, dirty: true,
    });
    markDirty();
    renderDocument();
    document.querySelectorAll('.v2-eq-check:checked').forEach((cb) => { cb.checked = false; });
}

/* =========================================================
 * 5c2b. BIỂU ĐỒ + BẢNG KT KHỐI LƯỢNG TRUNG BÌNH (port từ V1 chart_ops)
 *   - renderChartV2: vẽ block type 'chart' bằng Chart.js (2.x, nạp sẵn từ layout)
 *   - generateWeightChartTableV2: chèn cặp Biểu đồ + Bảng nhập KL trung bình
 *   - Chạy thử: dữ liệu biểu đồ đọc trực tiếp từ giá trị biến số của bảng nguồn
 * ========================================================= */
const chartInstancesV2 = {};
// ID của block chart đang được SỬA (Bảng KT Khối lượng Trung bình) — null nghĩa là modal
// đang ở chế độ CHÈN MỚI. Xem generateWeightChartTableV2/updateWeightChartTableV2.
let weightChartEditId = null;

function setWeightChartModalModeV2(isEdit) {
    const title = document.getElementById('v2-wc-modal-title');
    const btn = document.getElementById('v2-wc-generate');
    if (title) title.innerHTML = `<i class="fas fa-balance-scale me-2"></i> ${isEdit ? 'SỬA' : 'TẠO'} BẢNG KT KHỐI LƯỢNG TRUNG BÌNH`;
    if (btn) btn.textContent = isEdit ? 'Cập nhật' : 'Chèn Bảng & Biểu Đồ';
}

/** Mở modal Bảng KT Khối lượng Trung bình để SỬA thông số của 1 cặp biểu đồ+bảng đã chèn. */
function openWeightChartEditModalV2(chartItem) {
    if (BOOT.isReadOnly || BOOT.isExecutionMode) return;
    const cfg = chartItem.chartConfig || {};
    const table = items.find((i) => i.id === cfg.tableSourceId && i.type === 'table');

    weightChartEditId = chartItem.id;
    const weightInput = document.getElementById('v2-wc-weight');
    const devInput = document.getElementById('v2-wc-dev');
    const freqInput = document.getElementById('v2-wc-freq');
    if (weightInput) weightInput.value = cfg.weight ?? (((cfg.minY ?? 0) + (cfg.maxY ?? 0)) / 2).toFixed(2);
    if (devInput) devInput.value = cfg.devPercent ?? 3;
    if (freqInput) freqInput.value = table?.freq_minutes ?? 15;
    setWeightChartModalModeV2(true);
    showModalV2('v2WeightChartModal');
}

/** Cập nhật thông số của cặp biểu đồ+bảng đã tồn tại (không tạo biến/hàng mới): đổi tiêu
 *  chuẩn min/max hiển thị trên biểu đồ, tần suất lấy mẫu, và validation min/max của các
 *  biến số (number) đã sinh ra trong bảng nguồn — kể cả các hàng do "Thêm dòng" tạo thêm. */
function updateWeightChartTableV2(chartId, weight, devPercent, freq, minY, maxY) {
    const chartItem = items.find((i) => i.id === chartId && i.type === 'chart');
    if (!chartItem) { weightChartEditId = null; hideModalV2('v2WeightChartModal'); return; }
    const table = items.find((i) => i.id === chartItem.chartConfig?.tableSourceId && i.type === 'table');

    saveDocState();

    chartItem.chartConfig = {
        ...chartItem.chartConfig,
        title: `VẼ BIỂU ĐỒ THEO KHỐI LƯỢNG MỖI 10 VIÊN\nTiêu chuẩn: Khối lượng 10 viên ± ${devPercent}% = ${weight} g ± ${devPercent}% (${minY} - ${maxY} g)\nTần suất kiểm tra: Mỗi ${freq} phút`,
        minY: parseFloat(minY),
        maxY: parseFloat(maxY),
        weight,
        devPercent,
    };
    chartItem.label = `Biểu đồ Khối lượng (W10: ${weight}g ± ${devPercent}%)`;
    chartItem.dirty = true;

    if (table) {
        table.freq_minutes = freq;
        table.dirty = true;
        Object.values(fieldsConfig).forEach((f) => {
            if (f.block_id === table.id && f.type === 'number') {
                f.validation = { ...(f.validation || {}), min: parseFloat(minY), max: parseFloat(maxY), decimal_places: 2 };
            }
        });
    }

    weightChartEditId = null;
    hideModalV2('v2WeightChartModal');
    markDirty();
    renderDocument();
    showToast('success', 'Đã cập nhật thông số Bảng KT Khối lượng Trung bình');
}

function stripHtmlV2(html) {
    if (!html) return '';
    const tmp = document.createElement('div');
    tmp.innerHTML = String(html);
    return tmp.textContent || tmp.innerText || '';
}

function showModalV2(id) {
    const el = document.getElementById(id);
    if (!el) return;
    if (window.jQuery) {
        window.jQuery(el).modal('show');
    }
}

function hideModalV2(id) {
    const el = document.getElementById(id);
    if (!el) return;
    if (window.jQuery) {
        window.jQuery(el).modal('hide');
    }
}

/** Trích dữ liệu vẽ biểu đồ từ bảng nguồn. Ở Chạy thử: ô chứa biến số đọc theo
 *  giá trị đã nhập (executionValues); ở Thiết kế: đọc text tĩnh của ô. */
function extractChartDataV2(tableItem, cfg) {
    const labels = [];
    const data = [];
    const xIdx = cfg.xIdx ?? 0;
    const yIdx = cfg.yIdx ?? 1;
    (tableItem.data || []).forEach((row) => {
        if (!Array.isArray(row)) return;
        const cellText = (idx) => {
            const cell = row[idx];
            const html = cell && typeof cell === 'object' ? cell.content : cell;
            if (!html) return '';
            const m = String(html).match(/data-field-id=["']([^"']+)["']/);
            if (m && BOOT.isExecutionMode) {
                const v = getExecDefaultV2(m[1]);
                return v === undefined || v === null ? '' : String(v);
            }
            return stripHtmlV2(html).trim();
        };
        const labelText = cellText(xIdx);
        const valText = cellText(yIdx);
        if (!labelText && !valText) return;
        labels.push(String(labels.length + 1));
        const num = parseFloat(String(valText).replace(/,/g, ''));
        data.push(!valText || valText.toUpperCase() === 'NA' || isNaN(num) ? null : num);
    });
    return { labels, data };
}

/** Vẽ biểu đồ lên canvas (Chart.js 2.x — cùng cấu hình với renderChart V1:
 *  đường Min/Max tiêu chuẩn, tô đỏ khi vượt chuẩn, customGrid chia 10 nấc). */
function renderChartV2(canvasId, item) {
    const canvas = document.getElementById(canvasId);
    if (!canvas || typeof window.Chart === 'undefined') return;
    const config = item.chartConfig || {};

    let labels = config.labels || [];
    let data = config.data || [];
    if (BOOT.isExecutionMode && config.tableSourceId) {
        const src = items.find((i) => i.id === config.tableSourceId);
        if (src && src.type === 'table') ({ labels, data } = extractChartDataV2(src, config));
    }

    if (chartInstancesV2[canvasId]) chartInstancesV2[canvasId].destroy();

    const minY = config.minY ?? null;
    const maxY = config.maxY ?? null;
    let calculatedMinY = minY;
    let calculatedMaxY = maxY;
    let isOutOfSpec = false;
    const numVals = (data || []).map((v) => parseFloat(v)).filter((v) => !isNaN(v) && isFinite(v));
    if (numVals.length > 0) {
        const dataMin = Math.min(...numVals);
        const dataMax = Math.max(...numVals);
        // Nới trục Y để điểm vượt chuẩn vẫn hiện trên biểu đồ
        if (calculatedMinY !== null && dataMin < calculatedMinY) calculatedMinY = Math.floor(dataMin * 100) / 100 - 0.02;
        if (calculatedMaxY !== null && dataMax > calculatedMaxY) calculatedMaxY = Math.ceil(dataMax * 100) / 100 + 0.02;
        if (minY !== null && dataMin < minY) isOutOfSpec = true;
        if (maxY !== null && dataMax > maxY) isOutOfSpec = true;
    }

    const yAxesTicks = { beginAtZero: calculatedMinY === null };
    if (calculatedMinY !== null) yAxesTicks.min = calculatedMinY;
    if (calculatedMaxY !== null) yAxesTicks.max = calculatedMaxY;
    if (config.customGrid && calculatedMinY !== null && calculatedMaxY !== null) {
        yAxesTicks.stepSize = (calculatedMaxY - calculatedMinY) / 10;
    }

    const datasets = [{
        label: Array.isArray(config.title) ? config.title[0] : (config.title || 'Dữ liệu'),
        data,
        backgroundColor: config.type === 'bar' ? 'rgba(26, 115, 232, 0.5)' : 'rgba(26, 115, 232, 0.1)',
        borderColor: 'rgba(26, 115, 232, 1)',
        borderWidth: 2,
        pointRadius: 4,
        pointBackgroundColor: '#ff0000',
        fill: config.type === 'line',
        spanGaps: false,
    }];
    const specLine = (label, value) => ({
        label,
        data: Array(labels.length).fill(value),
        borderColor: isOutOfSpec ? 'rgba(239, 68, 68, 1)' : 'rgba(148, 163, 184, 0.8)',
        borderWidth: isOutOfSpec ? 2.5 : 1.5,
        borderDash: isOutOfSpec ? [] : [5, 5],
        pointRadius: 0,
        fill: false,
        tension: 0,
    });
    if (minY !== null && labels.length > 0) datasets.push(specLine('Tiêu chuẩn Dưới (Min)', minY));
    if (maxY !== null && labels.length > 0) datasets.push(specLine('Tiêu chuẩn Trên (Max)', maxY));

    chartInstancesV2[canvasId] = new window.Chart(canvas.getContext('2d'), {
        type: config.type || 'line',
        data: { labels, datasets },
        options: {
            animation: { duration: 0 },
            responsive: true,
            maintainAspectRatio: false,
            title: {
                display: !!config.title,
                text: typeof config.title === 'string' ? config.title.split('\n') : config.title,
                fontSize: 14,
                fontColor: '#000',
            },
            scales: {
                yAxes: [{
                    ticks: yAxesTicks,
                    gridLines: { color: config.customGrid ? 'rgba(0,0,0,0.3)' : 'rgba(0, 0, 0, 0.1)', lineWidth: 1 },
                }],
                xAxes: [{
                    gridLines: { color: config.customGrid ? 'rgba(0,0,0,0.3)' : 'rgba(0, 0, 0, 0.1)', lineWidth: 1 },
                }],
            },
            tooltips: {
                callbacks: {
                    label: (tooltipItem, d) => d.datasets[tooltipItem.datasetIndex].label + ': ' + tooltipItem.yLabel,
                },
            },
        },
    });
}

/** Vẽ lại mọi biểu đồ đang hiển thị — gọi khi giá trị biến số đổi lúc Chạy thử */
function refreshChartsV2() {
    items.forEach((it) => {
        if (it.type !== 'chart' || !it.chartConfig) return;
        const canvasId = 'v2_chart_canvas_' + it.id;
        if (document.getElementById(canvasId)) renderChartV2(canvasId, it);
    });
}

/** Chèn cặp "Biểu đồ + Bảng nhập Khối lượng Trung bình" — cấu trúc y hệt V1
 *  (generateWeightChartTable): 3 dòng mặc định, mỗi dòng 1 biến số kiểu số. */
function generateWeightChartTableV2() {
    if (BOOT.isReadOnly || BOOT.isExecutionMode) return;
    const weight = parseFloat(document.getElementById('v2-wc-weight')?.value) || 7.10;
    const devPercent = parseFloat(document.getElementById('v2-wc-dev')?.value) || 3;
    const freq = parseInt(document.getElementById('v2-wc-freq')?.value, 10) || 15;

    const maxDev = (weight * devPercent) / 100;
    const minY = (weight - maxDev).toFixed(2);
    const maxY = (weight + maxDev).toFixed(2);

    // Modal đang ở chế độ SỬA (mở từ nút bút chì trên 1 biểu đồ đã chèn) -> cập nhật
    // thông số của cặp biểu đồ+bảng hiện có, KHÔNG tạo biến/hàng mới.
    if (weightChartEditId) {
        updateWeightChartTableV2(weightChartEditId, weight, devPercent, freq, minY, maxY);
        return;
    }

    // Chèn ngay tại điểm click gần nhất trong vùng thiết kế (giống mọi thao tác chèn khác
    // — xem getInsertPointV2()), KHÔNG dùng activeBlockId: bấm nút mở modal này đã tự làm
    // activeBlockId về null (click ra ngoài .v2-block), nên trước đây luôn rơi xuống cuối
    // tài liệu bất kể con trỏ đang đặt ở đâu.
    const { insertIdx, sectionId } = getInsertPointV2();

    saveDocState();

    const tableId = newBlockId();
    const chartId = newBlockId();

    // Đặt tên biến không trùng với các bảng KL đã chèn trước đó (áp dụng chung 1 số thứ tự
    // cho cả 4 biến/dòng để dễ đối chiếu: "Khối lượng TB 1", "Thời gian lấy mẫu 1"...).
    const nameOffset = Object.values(fieldsConfig).filter((f) => /^Khối lượng TB \d+$/.test(f.name || '')).length;
    const rowsData = [1, 2, 3].map((n) => {
        const seq = nameOffset + n;
        const uid = (suffix) => 'field_v2_' + Date.now().toString(36) + Math.random().toString(36).slice(2, 6) + suffix;

        const numFid = uid('n' + n);
        fieldsConfig[numFid] = {
            id: numFid, name: 'Khối lượng TB ' + seq, label: 'Nhập Số', type: 'number',
            validation: { required: false, min: parseFloat(minY), max: parseFloat(maxY), decimal_places: 2 },
            options: [], instruction: '', block_id: tableId, section_id: sectionId,
            scaleEnabled: true, scalePreset: 'and',
        };

        // "Thời gian lấy mẫu": biến kiểu Thời Gian THẬT (type: date), click Chạy thử tự
        // điền giờ hệ thống (autoSystemTime) — thay cho badge tĩnh cũ không cấu hình được.
        const timeFid = uid('t' + n);
        fieldsConfig[timeFid] = {
            id: timeFid, name: 'Thời gian lấy mẫu ' + seq, label: 'Thời gian lấy mẫu', type: 'date',
            autoSystemTime: true, validation: { required: false }, options: [], instruction: '',
            block_id: tableId, section_id: sectionId,
        };

        // "Người thực hiện": biến Chữ ký thường.
        const execFid = uid('e' + n);
        fieldsConfig[execFid] = {
            id: execFid, name: 'Người thực hiện ' + seq, label: 'Người thực hiện', type: 'signature',
            is_checker: false, validation: { required: false }, options: [], instruction: '',
            block_id: tableId, section_id: sectionId,
        };

        // "Người kiểm tra": biến Chữ ký với thuộc tính is_checker — mở màn xác thực người
        // kiểm tra riêng (openCheckerAuthModal) thay vì ký thường.
        const checkFid = uid('c' + n);
        fieldsConfig[checkFid] = {
            id: checkFid, name: 'Người kiểm tra ' + seq, label: 'Người kiểm tra', type: 'signature',
            is_checker: true, validation: { required: false }, options: [], instruction: '',
            block_id: tableId, section_id: sectionId,
        };

        const badge = (fid) => `<span contenteditable="false" class="ebmr-field-badge" data-field-id="${fid}"></span>​`;
        return [
            { content: '<p style="text-align: center;">#STT#</p>', rs: 1, cs: 1, hidden: false }, // STT tự đánh số bằng CSS counter — xem decorateBadges()
            { content: badge(timeFid), rs: 1, cs: 1, hidden: false },
            { content: badge(numFid), rs: 1, cs: 1, hidden: false },
            { content: badge(execFid), rs: 1, cs: 1, hidden: false },
            { content: badge(checkFid), rs: 1, cs: 1, hidden: false },
        ];
    });

    const chartItem = {
        id: chartId, type: 'chart', section_id: sectionId, dirty: true,
        label: `Biểu đồ Khối lượng (W10: ${weight}g ± ${devPercent}%)`,
        chartConfig: {
            type: 'line',
            title: `VẼ BIỂU ĐỒ THEO KHỐI LƯỢNG MỖI 10 VIÊN\nTiêu chuẩn: Khối lượng 10 viên ± ${devPercent}% = ${weight} g ± ${devPercent}% (${minY} - ${maxY} g)\nTần suất kiểm tra: Mỗi ${freq} phút`,
            labels: ['1', '2', '3'],
            data: [null, null, null],
            minY: parseFloat(minY),
            maxY: parseFloat(maxY),
            weight,
            devPercent,
            tableSourceId: tableId,
            isMatrix: false,
            xIdx: 1, // cột "Thời gian lấy mẫu" (đã lùi 1 bậc vì thêm cột STT đầu bảng)
            yIdx: 2, // cột "W10"
            customGrid: true,
        },
    };
    const tableItem = {
        id: tableId, type: 'table', section_id: sectionId, dirty: true,
        label: 'Bảng nhập Khối lượng Trung bình',
        borderMode: 'all', borderWeight: '1px', hideHeader: false,
        canAddRows: true, addRowsCount: 1,
        freq_minutes: freq,
        rows: 3, cols: 5,
        columns: [
            { label: 'STT', width: '8%' },
            { label: 'Thời gian lấy mẫu', width: '23%' },
            { label: 'W10', width: '23%' },
            { label: 'Người thực hiện', width: '23%' },
            { label: 'Người kiểm tra', width: '23%' },
        ],
        rowHeights: ['auto', 'auto', 'auto'],
        data: rowsData,
    };

    // Giống V1: biểu đồ đứng TRÊN, bảng nhập liệu ngay DƯỚI
    items.splice(insertIdx, 0, chartItem, tableItem);

    hideModalV2('v2WeightChartModal');
    markDirty();
    renderDocument();
    setTimeout(() => {
        document.querySelector(`.v2-block[data-id="${tableId}"]`)?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }, 120);
}

/* =========================================================
 * 5c2b. "THÊM DÒNG (CẤP 2)" — bảng có canAddRows bật, CHỈ hoạt động lúc Chạy thử/thực
 *   thi (không đổi cấu trúc bảng gốc lúc Thiết kế): tự nhân bản (các) hàng CUỐI thành
 *   hàng mới, mỗi biến số trong hàng nguồn được đổi sang field id/tên MỚI (không dùng
 *   chung dữ liệu với hàng gốc). Hàng mới đánh dấu is_dynamic — chỉ hàng này mới xoá
 *   lại được. Cột "STT" (nếu có, xem #STT# ở decorateBadges) tự đánh số lại bằng CSS
 *   counter — KHÔNG cần tính lại bằng JS mỗi lần thêm/xoá hàng.
 * ========================================================= */

/** Bảng GF liên kết được render từ BẢN SAO nông của block trong gfPreviewCacheV2. Mảng
 *  data/rowHeights dùng chung tham chiếu (push/splice đã tự thấm về cache), nhưng số rows
 *  là primitive nằm trên bản sao → phải ghi ngược để lần render sau đọc đúng số dòng. */
function syncGfCanonicalRowsV2(item) {
    const canon = item && item.__gfCanonical;
    if (!canon || canon === item) return;
    canon.data = item.data;
    canon.rows = item.rows;
    if (item.rowHeights) canon.rowHeights = item.rowHeights;
}

function addRuntimeTableRowV2(item) {
    if (BOOT.isReadOnly || !item || !item.canAddRows || !item.rows) return;
    const addRowsCount = Math.max(1, parseInt(item.addRowsCount, 10) || 1);
    const actualRowsToAdd = Math.min(addRowsCount, item.rows);
    const baseRows = item.rows; // chốt TRƯỚC khi thêm để tính đúng hàng nguồn (giống V1)

    // KHÔNG saveDocState(): hàm này chỉ chạy lúc Chạy thử/thực thi — snapshot chứa dòng
    // động sẽ làm Ctrl+Z ở Thiết kế "hồi sinh" dòng thử vào template.
    for (let offset = actualRowsToAdd; offset >= 1; offset--) {
        const sourceRowIdx = baseRows - offset;
        const sourceRow = item.data[sourceRowIdx];
        if (!sourceRow) continue;

        // Nhân bản MỌI biến số trong hàng nguồn sang id mới (tái dùng đúng cơ chế
        // copy/paste khối), rồi đặt lại TÊN theo quy tắc "tên cũ + số thứ tự kế tiếp"
        // (khớp cách đặt tên biến của bảng này: "Thời gian lấy mẫu 1" -> "... 2"...).
        const htmlList = sourceRow.map((cell) => (cell && cell.content) || '');
        const { idMap, nameMap } = buildFieldDuplicateMapV2(htmlList);
        if (Object.keys(idMap).length) {
            applyFieldDuplicateMapV2(idMap, nameMap);
            Object.values(idMap).forEach((newId) => dynamicClonedFieldIds.add(newId));
            Object.entries(idMap).forEach(([oldId, newId]) => {
                const oldCfg = fieldsConfig[oldId];
                const newCfg = fieldsConfig[newId];
                if (!oldCfg || !newCfg) return;
                const m = String(oldCfg.name || '').match(/^(.*?)(\d+)$/);
                const base = m ? m[1] : ((oldCfg.name || 'Var') + ' ');
                let n = m ? parseInt(m[2], 10) + 1 : 2;
                while (Object.values(fieldsConfig).some((f) => f !== newCfg && f.name === base + n)) n++;
                newCfg.name = base + n;
            });
        }

        const newRow = sourceRow.map((cell) => {
            const nc = { ...(cell || {}) };
            if (nc.content) nc.content = rewriteFieldIdsInHtmlV2(nc.content, idMap);
            nc.db_id = null;
            nc.content_db_id = null;
            nc.is_dynamic = true;
            return nc;
        });
        item.data.push(newRow);
        if (item.rowHeights) item.rowHeights.push(item.rowHeights[sourceRowIdx] || 'auto');
    }
    item.rows += actualRowsToAdd;
    syncGfCanonicalRowsV2(item); // GF liên kết: ghi số rows mới về block trong cache
    // Bảng nằm trong Lặp nhóm: bản đồ field theo "Lần i" đã cache TRƯỚC khi có dòng mới
    // → xoá cache để rebuild (id xác định nên field cũ giữ nguyên id, không mất giá trị).
    if (item.loop_group_id) delete loopIterFieldMapCache[item.loop_group_id];
    syncRecordStructureV2(item); // thực thi thật: lưu overlay dòng động của lô (no-op lúc Chạy thử)
    renderDocument();
    showToast('success', `Đã thêm ${actualRowsToAdd} dòng mới`);
}

async function deleteRuntimeTableRowV2(item, rowIdx) {
    if (BOOT.isReadOnly) return;
    const row = item.data && item.data[rowIdx];
    if (!row || !row[0] || !row[0].is_dynamic) return; // chỉ xoá được hàng do "cấp 2" tự thêm
    if (item.data.length <= 1) return;

    const res = await window.Swal.fire({
        title: 'Xóa dòng này?', icon: 'warning', showCancelButton: true,
        confirmButtonColor: '#d33', confirmButtonText: 'Xóa', cancelButtonText: 'Hủy',
    });
    if (!res.isConfirmed) return;

    // KHÔNG saveDocState() — xem ghi chú ở addRuntimeTableRowV2
    item.data.splice(rowIdx, 1);
    if (item.rowHeights) item.rowHeights.splice(rowIdx, 1);
    item.rows = Math.max(0, item.rows - 1);
    syncGfCanonicalRowsV2(item); // GF liên kết: ghi số rows mới về block trong cache
    if (item.loop_group_id) delete loopIterFieldMapCache[item.loop_group_id];
    syncRecordStructureV2(item);
    renderDocument();
    showToast('info', 'Đã xóa dòng');
}

/** Thoát Chạy thử → Thiết kế: gỡ MỌI dòng động (is_dynamic) khỏi mọi bảng + xoá config
 *  các field đã nhân bản cho chúng — trả cấu trúc BMR về đúng bản thiết kế gốc. */
function cleanupDynamicRowsV2() {
    const purgeDynamicRows = (tbl) => {
        if (!tbl || tbl.type !== 'table' || !Array.isArray(tbl.data)) return;
        for (let r = tbl.data.length - 1; r >= 0; r--) {
            const row = tbl.data[r];
            if (!row || !row[0] || !row[0].is_dynamic) continue;
            tbl.data.splice(r, 1);
            if (tbl.rowHeights) tbl.rowHeights.splice(r, 1);
            tbl.rows = Math.max(0, (tbl.rows || 0) - 1);
        }
    };
    items.forEach(purgeDynamicRows);
    // GF liên kết render từ gfPreviewCacheV2 (không nằm trong items[]) — cũng phải dọn dòng
    // động ở đây, nếu không lần Chạy thử sau vẫn còn dòng thử của lần trước.
    Object.values(gfPreviewCacheV2).forEach((entry) => (entry.blocks || []).forEach(purgeDynamicRows));
    dynamicClonedFieldIds.forEach((id) => delete fieldsConfig[id]);
    dynamicClonedFieldIds.clear();
}

/** Thực thi thật (BOOT.recordId): đồng bộ overlay dòng động của 1 bảng lên server —
 *  gửi TOÀN BỘ dòng is_dynamic hiện có (thay thế overlay cũ; rỗng = server xoá overlay).
 *  Chạy thử trong Designer (không có recordId): no-op, dòng động chỉ sống trong RAM. */
function syncRecordStructureV2(item) {
    if (!BOOT.recordId || !item || item.type !== 'table') return;
    const rows = [];
    const rowHeights = [];
    (item.data || []).forEach((row, r) => {
        if (!row || !row[0] || !row[0].is_dynamic) return;
        rows.push(row);
        rowHeights.push((item.rowHeights && item.rowHeights[r]) || 'auto');
    });
    // Gom config các biến số xuất hiện trong những dòng động này
    const cfgOut = {};
    const tmp = document.createElement('div');
    rows.forEach((row) => row.forEach((cell) => {
        if (!cell || !cell.content || cell.content.indexOf('data-field-id') === -1) return;
        tmp.innerHTML = cell.content;
        tmp.querySelectorAll('[data-field-id]').forEach((el) => {
            const fid = el.getAttribute('data-field-id');
            if (fid && fieldsConfig[fid]) cfgOut[fid] = fieldsConfig[fid];
        });
    }));

    fetch(BOOT.urls.saveRecordStructure, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({
            record_id: BOOT.recordId,
            block_uuid: item.id,
            kind: 'dynamic_rows',
            payload: { rows, rowHeights, fieldsConfig: cfgOut },
            _token: BOOT.csrf,
        }),
    }).then((r) => r.json()).then((res) => {
        if (!res.success) showToast('error', res.message || 'Không lưu được cấu trúc dòng thêm');
    }).catch(() => showToast('error', 'Mất kết nối — dòng vừa thêm/xoá CHƯA được lưu lên máy chủ'));
}

/** Mở lại lô đang thực thi: ghép các dòng động đã lưu (ebmr_record_structures) vào bảng
 *  tương ứng + nạp lại config biến số của chúng — gọi 1 lần TRƯỚC renderDocument() đầu tiên. */
function mergeRecordStructuresV2() {
    const structures = BOOT.recordStructures || {};
    Object.keys(structures).forEach((blockUuid) => {
        const overlay = structures[blockUuid];
        const payload = (overlay && overlay.payload) || overlay;
        if (!payload || !Array.isArray(payload.rows) || !payload.rows.length) return;
        const item = items.find((i) => i.id === blockUuid && i.type === 'table');
        if (!item || !Array.isArray(item.data)) return;
        payload.rows.forEach((row, k) => {
            item.data.push(row);
            if (item.rowHeights) item.rowHeights.push((payload.rowHeights && payload.rowHeights[k]) || 'auto');
        });
        item.rows = (item.rows || 0) + payload.rows.length;
        Object.assign(fieldsConfig, payload.fieldsConfig || {});
        Object.keys(payload.fieldsConfig || {}).forEach((id) => dynamicClonedFieldIds.add(id));
    });
}

/* =========================================================
 * 5c2c. DANH MỤC CHỮ VIẾT TẮT (port từ V1 ui_handlers.addAbbreviation/saveAbbreviation)
 *   Lưu riêng ở cột ebmr_templates.abbreviations_List (đã có sẵn xử lý ở backend save()),
 *   không nằm trong danh sách block bình thường.
 * ========================================================= */
function addAbbreviationV2() {
    const selectedText = window.getSelection().toString().trim();
    if (!selectedText) {
        window.Swal?.fire('Lỗi', 'Vui lòng bôi đen (chọn) một từ viết tắt trong tài liệu trước khi bấm nút này.', 'warning');
        return;
    }
    document.getElementById('v2-abbr-word').value = selectedText;
    document.getElementById('v2-abbr-meaning').value = '';
    showModalV2('v2AbbreviationModal');
}

function saveAbbreviationV2() {
    const word = document.getElementById('v2-abbr-word').value.trim();
    const meaning = document.getElementById('v2-abbr-meaning').value.trim();
    if (!word || !meaning) {
        window.Swal?.fire('Lỗi', 'Vui lòng nhập đầy đủ ý nghĩa của từ viết tắt.', 'warning');
        return;
    }

    let abbrevTable = items.find((item) => item.isAbbreviationTable === true);
    if (!abbrevTable) {
        abbrevTable = {
            id: newBlockId(), type: 'table', label: 'DANH MỤC CHỮ VIẾT TẮT', isAbbreviationTable: true,
            rows: 1, cols: 3, borderMode: 'visible', hideHeader: false, section_id: null,
            columns: [
                { label: 'STT', type: 'text', width: '10%' },
                { label: 'Chữ viết tắt', type: 'text', width: '30%' },
                { label: 'Ý nghĩa', type: 'text', width: '60%' },
            ],
            data: [[
                { content: '1', rs: 1, cs: 1, textAlign: 'center' },
                { content: word, rs: 1, cs: 1, textAlign: 'center', fontWeight: 'bold' },
                { content: meaning, rs: 1, cs: 1, textAlign: 'left' },
            ]],
            dirty: true,
        };
        let insertIdx = 0;
        for (let i = 0; i < items.length; i++) { if (items[i].isVirtual) insertIdx = i + 1; }
        items.splice(insertIdx, 0, abbrevTable);
    } else {
        const exists = abbrevTable.data.some((row) => row[1] && stripHtmlV2(row[1].content || '').trim().toLowerCase() === word.toLowerCase());
        if (exists) {
            window.Swal?.fire('Lỗi', 'Từ viết tắt này đã tồn tại trong danh mục!', 'warning');
            return;
        }
        const stt = abbrevTable.data.length + 1;
        abbrevTable.data.push([
            { content: stt.toString(), rs: 1, cs: 1, textAlign: 'center' },
            { content: word, rs: 1, cs: 1, textAlign: 'center', fontWeight: 'bold' },
            { content: meaning, rs: 1, cs: 1, textAlign: 'left' },
        ]);
        abbrevTable.rows = abbrevTable.data.length;
        abbrevTable.dirty = true;
    }

    hideModalV2('v2AbbreviationModal');
    markDirty();
    renderDocument();
    window.Swal?.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Đã thêm vào Danh mục chữ viết tắt', showConfirmButton: false, timer: 2000 });
}

/* =========================================================
 * 5c2d. CHÈN SYMBOL (port từ V1 symbol_ops.blade.php)
 * ========================================================= */
const SYMBOLS_V2 = {
    math: ['±', '≥', '≤', '×', '÷', '≈', '≠', '∞', '∑', 'π', '√', '²', '³', '°', '‰', 'µ', 'λ', 'Ω'],
    greek: ['α', 'β', 'γ', 'δ', 'Δ', 'ε', 'θ', 'λ', 'μ', 'Ω', 'ρ', 'σ', 'φ', 'ψ', 'ω'],
    misc: ['™', '®', '©', '✓', '✗', '→', '←', '↑', '↓', '↔', '▲', '▼', '◄', '►', '■', '•', '★'],
};
let currentSymbolTabV2 = 'math';

function renderSymbolGridV2() {
    const grid = document.getElementById('v2-symbol-grid');
    if (!grid) return;
    grid.innerHTML = '';
    (SYMBOLS_V2[currentSymbolTabV2] || []).forEach((sym) => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn btn-outline-secondary p-0 d-flex align-items-center justify-content-center';
        btn.style.width = '38px'; btn.style.height = '38px'; btn.style.fontSize = '1.2rem';
        btn.textContent = sym;
        btn.addEventListener('click', () => insertSymbolV2(sym));
        grid.appendChild(btn);
    });
}

function insertSymbolV2(sym) {
    if (!activeEditor) {
        window.Swal?.fire({ toast: true, position: 'top-end', icon: 'info', title: 'Hãy click vào một ô/đoạn văn bản trước khi chèn ký hiệu.', showConfirmButton: false, timer: 2500 });
        return;
    }
    activeEditor.chain().focus().insertContent(sym).run();
    if (activeSync) activeSync();
    markDirty();
    hideModalV2('v2SymbolModal');
}

/* =========================================================
 * 5c2e. CHÈN CÔNG THỨC TOÁN HỌC (KaTeX) — node atom mathEquation (xem media-nodes.js)
 * ========================================================= */
let equationEditCallbackV2 = null;

function updateEquationPreviewV2() {
    const input = document.getElementById('v2-eq-input');
    const preview = document.getElementById('v2-eq-preview');
    if (!input || !preview) return;
    try { preview.innerHTML = katex.renderToString(input.value || '', { throwOnError: false }); }
    catch (e) { preview.textContent = input.value; }
}

function openEquationEditorV2(latex, onSave) {
    const input = document.getElementById('v2-eq-input');
    input.value = latex || '';
    updateEquationPreviewV2();
    equationEditCallbackV2 = onSave || null;
    showModalV2('v2EquationModal');
}
window.__V2__.openEquationEditor = openEquationEditorV2;

function insertEquationV2() {
    const latex = document.getElementById('v2-eq-input').value.trim();
    if (!latex) { hideModalV2('v2EquationModal'); return; }

    if (equationEditCallbackV2) {
        equationEditCallbackV2(latex);
        equationEditCallbackV2 = null;
        if (activeSync) activeSync();
        markDirty();
        hideModalV2('v2EquationModal');
        return;
    }
    if (!activeEditor) {
        window.Swal?.fire({ toast: true, position: 'top-end', icon: 'info', title: 'Hãy click vào một ô/đoạn văn bản trước khi chèn công thức.', showConfirmButton: false, timer: 2500 });
        return;
    }
    activeEditor.chain().focus().insertContent({ type: 'mathEquation', attrs: { latex } }).run();
    if (activeSync) activeSync();
    markDirty();
    hideModalV2('v2EquationModal');
}

/* =========================================================
 * 5c2f. CHÈN HÌNH ẢNH (base64, node atom v2Image — xem media-nodes.js)
 * ========================================================= */
let pendingImageDataUrlV2 = null;

function resetImageModalV2() {
    pendingImageDataUrlV2 = null;
    const file = document.getElementById('v2-img-file');
    const preview = document.getElementById('v2-img-preview');
    if (file) file.value = '';
    if (preview) { preview.src = ''; preview.style.display = 'none'; }
}

function insertImageV2() {
    if (!pendingImageDataUrlV2) {
        window.Swal?.fire('Lỗi', 'Vui lòng chọn 1 tệp hình ảnh.', 'warning');
        return;
    }
    if (!activeEditor) {
        window.Swal?.fire({ toast: true, position: 'top-end', icon: 'info', title: 'Hãy click vào một đoạn văn bản trước khi chèn hình ảnh.', showConfirmButton: false, timer: 2500 });
        return;
    }
    const width = document.getElementById('v2-img-width').value + '%';
    activeEditor.chain().focus().insertContent({ type: 'v2Image', attrs: { src: pendingImageDataUrlV2, width } }).run();
    if (activeSync) activeSync();
    markDirty();
    resetImageModalV2();
    hideModalV2('v2ImageModal');
}

/* =========================================================
 * 5c2g. DOCUMENT PROPERTY — danh mục key/value tự định nghĩa theo template (giống Word),
 *   lưu trong BOOT.docProperties, gửi kèm payload lưu (saveTemplate) ở khoá riêng.
 *   Bấm vào dòng (tên/giá trị) để chèn; nút bút chì để sửa giá trị; nút thùng rác để xóa.
 * ========================================================= */
let editingDocPropKeyV2 = null;

function renderDocPropListV2() {
    const tbody = document.getElementById('v2-dp-list');
    if (!tbody) return;
    const props = BOOT.docProperties || {};
    const keys = Object.keys(props).filter((k) => !k.startsWith('__')); // khóa __ là setting nội bộ (VD: đánh số tiêu đề)
    if (keys.length === 0) {
        tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted small py-3">Chưa có thuộc tính nào.</td></tr>';
        return;
    }
    tbody.innerHTML = keys.map((k) => `
        <tr>
            <td class="fw-bold v2-dp-row" data-dp-row="${esc(k)}" style="cursor:pointer;" title="Bấm để chèn vào tài liệu">${esc(k)}</td>
            <td class="v2-dp-row" data-dp-row="${esc(k)}" style="cursor:pointer;" title="Bấm để chèn vào tài liệu">${esc(props[k])}</td>
            <td class="text-center text-nowrap">
                <button type="button" class="btn btn-xs btn-outline-primary py-0 px-2 me-1" data-dp-insert="${esc(k)}" title="Chèn vào tài liệu"><i class="fas fa-file-import"></i></button>
                <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2 me-1" data-dp-edit="${esc(k)}" title="Sửa giá trị"><i class="fas fa-pen"></i></button>
                <button type="button" class="btn btn-xs btn-outline-danger py-0 px-2" data-dp-del="${esc(k)}" title="Xóa"><i class="fas fa-trash"></i></button>
            </td>
        </tr>`).join('');
    tbody.querySelectorAll('[data-dp-row]').forEach((el) =>
        el.addEventListener('click', () => insertDocPropV2(el.getAttribute('data-dp-row'))));
    tbody.querySelectorAll('[data-dp-insert]').forEach((btn) =>
        btn.addEventListener('click', (e) => { e.stopPropagation(); insertDocPropV2(btn.getAttribute('data-dp-insert')); }));
    tbody.querySelectorAll('[data-dp-edit]').forEach((btn) =>
        btn.addEventListener('click', (e) => { e.stopPropagation(); startEditDocPropV2(btn.getAttribute('data-dp-edit')); }));
    tbody.querySelectorAll('[data-dp-del]').forEach((btn) =>
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            const key = btn.getAttribute('data-dp-del');
            delete BOOT.docProperties[key];
            if (editingDocPropKeyV2 === key) resetDocPropFormV2();
            markDirty();
            renderDocPropListV2();
            refreshAllDocPropBadges();
        }));
}

function startEditDocPropV2(key) {
    const keyInput = document.getElementById('v2-dp-key');
    const valInput = document.getElementById('v2-dp-value');
    keyInput.value = key;
    keyInput.readOnly = true;
    valInput.value = (BOOT.docProperties || {})[key] ?? '';
    editingDocPropKeyV2 = key;
    const addBtn = document.getElementById('v2-dp-add');
    if (addBtn) addBtn.innerHTML = '<i class="fas fa-save me-1"></i>Lưu';
    valInput.focus();
}

function resetDocPropFormV2() {
    editingDocPropKeyV2 = null;
    const keyInput = document.getElementById('v2-dp-key');
    const valInput = document.getElementById('v2-dp-value');
    if (keyInput) { keyInput.value = ''; keyInput.readOnly = false; }
    if (valInput) valInput.value = '';
    const addBtn = document.getElementById('v2-dp-add');
    if (addBtn) addBtn.innerHTML = '<i class="fas fa-plus me-1"></i>Thêm';
}

/**
 * Chèn nội dung vào editor. Nếu chưa mở editor nào thì TỰ MỞ LẠI ô/đoạn văn bản
 * soạn gần nhất (host còn trong DOM) và khôi phục con trỏ rồi chèn — nhờ vậy các nút
 * Chèn (Symbol/Công thức/Hình ảnh/Document Property) hoạt động ngay cả khi editor đã đóng.
 * Trả về true nếu chèn được. insertFn(editor) tự thực hiện thao tác chèn.
 */
function runEditorInsertV2(insertFn) {
    if (activeEditor) { insertFn(activeEditor); return true; }
    const a = lastEditorArgs;
    if (a && a.host && document.body.contains(a.host)) {
        mountEditor(a.host, a.getHTML, a.setHTML, a.context);
        if (activeEditor) {
            if (lastEditorSel) { try { activeEditor.commands.setTextSelection(lastEditorSel); } catch (e) { /* ignore */ } }
            insertFn(activeEditor);
            return true;
        }
    }
    return false;
}

function insertDocPropV2(key) {
    const ok = runEditorInsertV2((ed) => ed.chain().focus().insertContent({ type: 'docProp', attrs: { key } }).run());
    if (!ok) {
        window.Swal?.fire({ toast: true, position: 'top-end', icon: 'info', title: 'Hãy bấm vào một ô/đoạn văn bản CÓ THỂ SỬA (không bị khóa) rồi chèn.', showConfirmButton: false, timer: 3000 });
        return;
    }
    if (activeSync) activeSync();
    markDirty();
    refreshAllDocPropBadges();
    hideModalV2('v2DocPropModal');
}

/* =========================================================
 * 5c2h. CHIA MÀN HÌNH (Split View) — trái/phải, kéo thanh giữa để đổi bề rộng.
 *   Pane trái = vùng đang soạn thảo thật (#v2-pages di chuyển vào), pane phải = bản
 *   sao chỉ-xem đồng bộ mỗi lần renderDocument() chạy. Nút toolbar hoạt động dạng
 *   toggle: bấm lần 1 mở, bấm lần 2 tắt.
 * ========================================================= */
let workspaceSplitActiveV2 = false;

function syncSplitPreviewV2() {
    if (!workspaceSplitActiveV2) return;
    const preview = document.getElementById('v2-split-preview');
    const live = document.getElementById('v2-pages');
    if (!preview || !live) return;
    let html = live.innerHTML;
    html = html.replace(/id="([^"]+)"/g, 'id="v2sp-$1"');
    html = html.replace(/contenteditable="true"/g, 'contenteditable="false"');
    preview.innerHTML = html;
}

/** Kéo thanh chia để đổi bề rộng 2 pane (giới hạn tối thiểu mỗi bên) */
function attachSplitResizerV2() {
    const resizer = document.getElementById('v2-split-resizer');
    const paneLeft = document.getElementById('v2-split-pane-left');
    const wrapper = document.getElementById('v2-split-wrapper');
    if (!resizer || !paneLeft || !wrapper) return;

    resizer.addEventListener('mousedown', (e) => {
        e.preventDefault();
        resizer.classList.add('dragging');
        const startX = e.clientX;
        const startWidth = paneLeft.getBoundingClientRect().width;
        const totalWidth = wrapper.getBoundingClientRect().width;
        const minWidth = 200;

        const onMove = (ev) => {
            let newWidth = startWidth + (ev.clientX - startX);
            newWidth = Math.max(minWidth, Math.min(totalWidth - minWidth - 10, newWidth));
            paneLeft.style.flex = `0 0 ${newWidth}px`;
        };
        const onUp = () => {
            resizer.classList.remove('dragging');
            document.removeEventListener('mousemove', onMove);
            document.removeEventListener('mouseup', onUp);
        };
        document.addEventListener('mousemove', onMove);
        document.addEventListener('mouseup', onUp);
    });
}

function toggleWorkspaceSplitV2() {
    workspaceSplitActiveV2 = !workspaceSplitActiveV2;
    const btn = document.getElementById('v2-btn-split');
    const pagesEl = document.getElementById('v2-pages');
    if (!pagesEl) return;

    if (workspaceSplitActiveV2) {
        const canvasParent = pagesEl.parentElement; // cha GỐC — chỉ hợp lệ lúc MỞ (lúc đóng pagesEl đã chuyển cha)
        if (!canvasParent) return;
        btn?.classList.add('text-success', 'border-success');
        if (btn) btn.title = 'Tắt chia màn hình';

        const wrapper = document.createElement('div');
        wrapper.id = 'v2-split-wrapper';
        wrapper.className = 'v2-split-wrapper';
        wrapper.innerHTML = `
            <div id="v2-split-pane-left" class="v2-split-pane">
                <div class="v2-split-pane-header">
                    <span class="badge bg-primary text-white py-2 px-3 fw-bold"><i class="fas fa-edit me-1"></i>Vùng soạn thảo</span>
                </div>
            </div>
            <div id="v2-split-resizer" class="v2-split-resizer" title="Kéo để đổi bề rộng"></div>
            <div id="v2-split-pane-right" class="v2-split-pane">
                <div class="v2-split-pane-header">
                    <span class="badge bg-secondary text-white py-2 px-3 fw-bold"><i class="fas fa-eye me-1"></i>Vùng xem (chỉ đọc)</span>
                </div>
                <div id="v2-split-preview" class="d-flex flex-column align-items-center py-4 gap-4"></div>
            </div>`;
        canvasParent.insertBefore(wrapper, pagesEl);
        document.getElementById('v2-split-pane-left').appendChild(pagesEl);
        attachSplitResizerV2();
        syncSplitPreviewV2();
    } else {
        btn?.classList.remove('text-success', 'border-success');
        if (btn) btn.title = 'Chia đôi màn hình (Split View)';
        const wrapper = document.getElementById('v2-split-wrapper');
        // Cha GỐC lúc đóng = cha của wrapper (pagesEl lúc này đang nằm trong pane trái, không phải cha gốc)
        const originalParent = wrapper?.parentElement;
        if (originalParent && wrapper) originalParent.insertBefore(pagesEl, wrapper);
        wrapper?.remove();
    }
}

/* =========================================================
 * 5c3. SIDEBAR THÀNH PHẦN (CO) — kéo thả chèn nội dung (như V1)
 * ========================================================= */
let componentsCache = null;

async function loadComponentsList() {
    const el = document.getElementById('v2-co-list');
    el.innerHTML = '<div class="text-center text-muted small py-3"><div class="spinner-border spinner-border-sm me-1"></div> Đang tải...</div>';
    const data = await fetch(BOOT.urls.templates).then((r) => r.json()).catch(() => null);
    if (!data) { el.innerHTML = '<div class="text-danger small text-center py-3">Lỗi tải dữ liệu.</div>'; return; }
    componentsCache = data.filter((t) => t.type === 'CO');
    renderComponentsList();
}

function renderComponentsList() {
    const el = document.getElementById('v2-co-list');
    const kw = (document.getElementById('v2-co-search').value || '').toLowerCase();
    const list = (componentsCache || []).filter((t) => !kw || (t.name || '').toLowerCase().includes(kw));
    if (list.length === 0) {
        el.innerHTML = '<div class="text-muted small text-center py-3">Không có Thành phần nào.</div>';
        return;
    }
    el.innerHTML = list.map((t) => `
        <div class="v2-drag-card" draggable="true" data-co-id="${t.id}" data-co-name="${esc(t.name)}">
            <i class="fas fa-grip-vertical text-muted mt-1"></i>
            <div style="min-width:0;">
                <div class="fw-bold text-truncate" title="${esc(t.name)}">${esc(t.name)}</div>
                <div class="small-muted">Cập nhật: ${new Date(t.updated_at).toLocaleString('vi-VN')}</div>
            </div>
        </div>`).join('');
    el.querySelectorAll('.v2-drag-card').forEach((card) => {
        card.addEventListener('dragstart', (e) => {
            e.dataTransfer.setData('componentId', card.getAttribute('data-co-id'));
            e.dataTransfer.setData('componentName', card.getAttribute('data-co-name'));
            e.dataTransfer.effectAllowed = 'copy';
            document.body.classList.add('v2-dragging');
        });
    });
}

function importUUID() {
    return Date.now().toString(36) + Math.random().toString(36).slice(2, 8);
}

/** Deep-copy toàn bộ block + biến số của 1 Thành phần CO vào vị trí thả (port từ importMasterForm V1) */
async function importComponentV2(templateId, templateName, afterIndex) {
    if (BOOT.isReadOnly || BOOT.isExecutionMode) return; // Chạy thử: không cho chèn khối mới
    const ok = await window.Swal.fire({
        title: 'Chèn thành phần?',
        text: `Chèn nội dung từ "${templateName}" vào vị trí đã thả?`,
        icon: 'question', showCancelButton: true, confirmButtonText: 'Chèn', cancelButtonText: 'Hủy',
    });
    if (!ok.isConfirmed) return;

    window.Swal.fire({ title: 'Đang tải dữ liệu...', allowOutsideClick: false, didOpen: () => window.Swal.showLoading() });
    const data = await fetch(`${BOOT.urls.templateBlocksBase}/${templateId}/blocks`)
        .then((r) => r.json()).catch(() => null);
    window.Swal.close();
    if (!data || !data.blocks) {
        window.Swal.fire('Lỗi', 'Không thể tải dữ liệu thành phần.', 'error');
        return;
    }

    const anchor = items[afterIndex] || null;
    const targetSectionId = anchor ? (anchor.type === 'section' ? (anchor.section_id || anchor.id) : anchor.section_id) : null;
    const importedConfig = data.fields || {};

    // Map field id + block id cũ -> mới để tránh trùng (giống V1)
    const fieldMap = {};
    Object.keys(importedConfig).forEach((k) => { fieldMap[k] = 'field_' + importUUID(); });
    const blockMap = {};
    data.blocks.forEach((b) => { blockMap[b.id] = 'blk_' + importUUID(); });

    const replaceFieldIds = (str) => {
        let out = str;
        Object.entries(fieldMap).forEach(([oldK, newK]) => { out = out.split(oldK).join(newK); });
        return out;
    };

    const newBlocks = [];
    data.blocks.forEach((b) => {
        if (b.type === 'section') return; // chỉ lấy nội dung, bỏ section của CO
        const nb = JSON.parse(JSON.stringify(b));
        nb.id = blockMap[b.id];
        nb.section_id = targetSectionId;
        nb.dirty = true;
        delete nb.db_id;
        delete nb.content_db_id;
        if (nb.content) nb.content = replaceFieldIds(nb.content);
        if (nb.type === 'table' && nb.data) {
            nb.data.forEach((row) => row.forEach((cell) => {
                if (cell && typeof cell === 'object') {
                    if (cell.content) cell.content = replaceFieldIds(cell.content);
                    delete cell.db_id;
                    delete cell.content_db_id;
                    cell.id = 'cell_' + importUUID();
                }
            }));
        }
        newBlocks.push(nb);
    });

    if (newBlocks.length === 0) {
        window.Swal.fire('Chú ý', 'Thành phần này không có nội dung để chèn.', 'info');
        return;
    }

    saveDocState();
    Object.entries(importedConfig).forEach(([oldK, cfg]) => {
        const c = JSON.parse(JSON.stringify(cfg));
        c.id = fieldMap[oldK];
        c.section_id = targetSectionId;
        if (c.block_id && blockMap[c.block_id]) c.block_id = blockMap[c.block_id];
        fieldsConfig[fieldMap[oldK]] = c;
    });
    items.splice(Math.min(afterIndex + 1, items.length), 0, ...newBlocks);
    markDirty();
    renderDocument();
    window.Swal.fire({
        toast: true, position: 'top', icon: 'success', showConfirmButton: false, timer: 2200,
        title: `Đã chèn ${newBlocks.length} khối từ "${templateName}"`
    });
}

/* =========================================================
 * 5d. TOOLBAR CỐ ĐỊNH KHI CUỘN
 * (position: sticky bị vô hiệu bởi overflow của layout AdminLTE
 *  nên dùng JS chuyển sang fixed + chèn spacer giữ chỗ)
 * ========================================================= */
function initFixedToolbar() {
    const tb = document.getElementById('v2-toolbar');
    if (!tb) return;
    const spacer = document.createElement('div');
    spacer.style.display = 'none';
    tb.parentNode.insertBefore(spacer, tb.nextSibling);
    let tbTop = null;

    const onScroll = () => {
        if (tbTop === null) tbTop = tb.getBoundingClientRect().top + window.scrollY;
        if (window.scrollY > tbTop) {
            if (!tb.classList.contains('v2-toolbar-fixed')) {
                spacer.style.height = tb.offsetHeight + 'px';
                spacer.style.display = 'block';
                tb.classList.add('v2-toolbar-fixed');
                // Ẩn topNAV để không che toolbar khi đang cuộn giữa tài liệu
                document.body.classList.add('v2-scrolled');
            }
        } else if (tb.classList.contains('v2-toolbar-fixed')) {
            tb.classList.remove('v2-toolbar-fixed');
            spacer.style.display = 'none';
            document.body.classList.remove('v2-scrolled');
        }
    };
    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('resize', () => { tbTop = null; }, { passive: true });
}

/* =========================================================
 * 6. CHẾ ĐỘ CHẠY THỬ (EXECUTION MODE)
 * ========================================================= */
function toggleExecutionModeV2() {
    BOOT.isExecutionMode = !BOOT.isExecutionMode;
    const isExec = BOOT.isExecutionMode;

    // Đổi giao diện nút Toggle
    const btnToggle = document.getElementById('v2-btn-toggle-mode');
    if (btnToggle) {
        if (isExec) {
            btnToggle.classList.remove('btn-primary');
            btnToggle.classList.add('btn-success');
            btnToggle.innerHTML = '<i class="fas fa-edit me-1"></i> Thiết kế';
            btnToggle.title = "Chuyển sang Thiết kế";
        } else {
            btnToggle.classList.remove('btn-success');
            btnToggle.classList.add('btn-primary');
            btnToggle.innerHTML = '<i class="fas fa-play me-1"></i> Chạy thử';
            btnToggle.title = "Chuyển sang Chạy thử";
        }
    }

    // Unmount editor đang active nếu có + bỏ mọi selection
    unmountEditor();
    selection.clearAll();
    setBlockPickModeV2(false);
    clearBlockPickV2();

    // Dọn field nhân bản riêng cho từng lần lặp + reset tab đang xem — mỗi phiên Chạy thử bắt đầu lại từ Lần 1
    loopClonedFieldIds.forEach((id) => delete fieldsConfig[id]);
    loopClonedFieldIds.clear();
    Object.keys(loopIterFieldMapCache).forEach((k) => delete loopIterFieldMapCache[k]);
    activeLoopTabIdx = {};

    // Dọn các DÒNG THÊM (Cấp 2) tạo lúc Chạy thử — không được lọt vào cấu trúc Thiết kế
    cleanupDynamicRowsV2();

    // Thêm/Xóa class CSS cho mode
    const contentWrapper = document.querySelector('.content-wrapper');
    if (contentWrapper) {
        if (isExec) contentWrapper.classList.add('execution-mode-active');
        else contentWrapper.classList.remove('execution-mode-active');
    }

    // Nút "Gạch chéo N/A" chỉ dùng được lúc Chạy thử — cập nhật hiển thị,
    // đồng thời thoát chế độ gạch chéo nếu đang bật mà quay về Thiết kế.
    naMarks.refreshButton();

    // Repaint lại tất cả các field badge
    renderDocument();

    window.Swal.fire({
        toast: true,
        position: 'top-end',
        icon: isExec ? 'success' : 'info',
        title: isExec ? 'Đã chuyển sang chế độ Chạy thử' : 'Đã quay lại chế độ Thiết kế',
        showConfirmButton: false,
        timer: 3000
    });
}

/* ---------------------------------------------------------
 * 6a. Hạ tầng dùng chung cho Chạy thử (mọi loại biến)
 * --------------------------------------------------------- */
function escapeHtmlV2(s) {
    return String(s).replace(/[&<>"']/g, (c) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
    }[c]));
}

function nowViV2() {
    const now = new Date();
    return now.toLocaleDateString('vi-VN') + ' ' + now.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
}

function getExecDefaultV2(fieldId) {
    return (BOOT.executionValues[fieldId] || {}).default;
}

// NodeView đang mount tự đăng ký paint() vào đây (xem ebmr-field.js). Cần thiết vì
// thay đổi executionValues không tự kích update() của TipTap NodeView (document
// không đổi) — nên khi 1 field thay đổi (VD: field số nuôi 1 field công thức khác),
// phải chủ động "vẽ lại" MỌI field đang hiển thị để công thức phụ thuộc cập nhật theo.
const fieldPaintRegistryV2 = new Map(); // fieldId -> Set<paintFn>
window.__V2__.registerFieldPaint = function (fieldId, fn) {
    if (!fieldPaintRegistryV2.has(fieldId)) fieldPaintRegistryV2.set(fieldId, new Set());
    fieldPaintRegistryV2.get(fieldId).add(fn);
};
window.__V2__.unregisterFieldPaint = function (fieldId, fn) {
    const set = fieldPaintRegistryV2.get(fieldId);
    if (!set) return;
    set.delete(fn);
    if (!set.size) fieldPaintRegistryV2.delete(fieldId);
};
window.__V2__.repaintAllFields = function () {
    fieldPaintRegistryV2.forEach((fns) => fns.forEach((fn) => { try { fn(); } catch (e) { /* noop */ } }));
    refreshChartsV2(); // biểu đồ liên kết bảng nguồn cập nhật theo giá trị vừa nhập
};

// Cân điện tử (RS-232/WebSocket) + Quét Barcode/MMS — xem scale-reader.js, mms-barcode.js
initScaleReaderV2(BOOT);
initMmsBarcodeV2(BOOT);

// Nhiệt độ/Độ ẩm/Chênh áp phòng — giá trị live + lịch sử trên toolbar (xem env-monitor.js)
createEnvMonitorV2(BOOT).init();

// Tài liệu PDF đính kèm theo phân đoạn — nút kẹp giấy trên mỗi section (xem attachments.js)
sectionAttachments.init();

// Ghi giá trị + đóng dấu người/giờ. Nếu GHI ĐÈ 1 giá trị đã có (khác giá trị cũ),
// bắt buộc nhập "Lý do thay đổi" trước khi áp dụng + lưu vào lịch sử (giống V1).
window.__V2__.applyExecutionValue = function (fieldId, finalValue, onDone) {
    // Gate trung tâm: hồ sơ readonly (đã hoàn thành/đã duyệt, hoặc không được phân phối)
    // thì MỌI ngõ nhập liệu đều bị chặn tại đây.
    if (BOOT.isReadOnly) { if (onDone) onDone(false); return; }
    const existing = getExecDefaultV2(fieldId);
    const hasExisting = existing !== undefined && existing !== null && existing !== '';

    const commit = (reason) => {
        if (typeof BOOT.executionValues[fieldId] !== 'object' || BOOT.executionValues[fieldId] === null) {
            BOOT.executionValues[fieldId] = {};
        }
        const rec = BOOT.executionValues[fieldId];
        const oldVal = rec.default;
        rec.default = finalValue;
        if (!rec._meta) rec._meta = {};
        if (!rec._meta.default) rec._meta.default = {};
        rec._meta.default.by = BOOT.currentUserName || 'Người dùng thử';
        rec._meta.default.at = nowViV2();
        if (reason) {
            rec._meta.default.reason = reason;
            rec._meta.default.history_count = (rec._meta.default.history_count || 0) + 1;
            if (!rec._meta.default.history_list) rec._meta.default.history_list = [];
            rec._meta.default.history_list.push({ val: finalValue, old_val: oldVal, reason, by: rec._meta.default.by, at: rec._meta.default.at });
        }
        window.__V2__.repaintAllFields();
        // Lưu ngay sau mỗi thao tác nhập liệu (không còn nút Lưu bản nháp)
        window.__V2__.autoSaveRecordData && window.__V2__.autoSaveRecordData();
        if (onDone) onDone(true);
    };

    if (hasExisting && existing !== finalValue) {
        window.Swal.fire({
            title: 'Lý do thay đổi',
            text: 'Vui lòng nhập lý do thay đổi dữ liệu:',
            input: 'textarea',
            inputPlaceholder: 'Nhập lý do thay đổi (bắt buộc)...',
            showCancelButton: true,
            confirmButtonText: 'Xác nhận',
            cancelButtonText: 'Hủy',
            inputValidator: (val) => (!val || !val.trim()) ? 'Vui lòng nhập lý do thay đổi dữ liệu!' : undefined,
        }).then((res) => {
            if (res.isConfirmed) commit(res.value.trim());
            else if (onDone) onDone(false);
        });
    } else {
        commit('');
    }
};

/* ---------------------------------------------------------
 * 6a2. LƯU HỒ SƠ LÔ (trang Thực thi thật — BOOT.recordId)
 *   Gửi BOOT.executionValues (fieldId -> {default, _meta}) tới updateRecordData:
 *   backend lưu từng field vào ebmr_run_data với block_uuid=fieldId, cell_id='default'
 *   (bỏ qua khoá '_meta') — cùng endpoint/format với trang thực thi V1 cũ.
 * --------------------------------------------------------- */
function saveRecordDataV2(status, opts) {
    if (!BOOT.recordId) return;
    const statusOnly = !!(opts && opts.statusOnly); // "Xác nhận đã đọc": chỉ chuyển trạng thái
    const data = statusOnly ? {} : (BOOT.executionValues || {});
    const reasons = {};
    if (!statusOnly) {
        Object.keys(data).forEach((fieldId) => {
            const reason = data[fieldId] && data[fieldId]._meta && data[fieldId]._meta.default
                && data[fieldId]._meta.default.reason;
            if (reason) reasons[fieldId] = { default: reason };
        });
        // Lý do gạch chéo / hủy gạch chéo N/A trong phiên — server đòi lý do khi
        // ghi đè giá trị cũ (hủy gạch = JSON cũ -> '') nên phải gửi kèm.
        const naReasons = naMarks.getPendingReasons();
        if (Object.keys(naReasons).length) reasons['__na__'] = { ...naReasons };
    }

    window.Swal.fire({
        title: 'Đang lưu dữ liệu...',
        allowOutsideClick: false,
        didOpen: () => window.Swal.showLoading(),
    });

    fetch(BOOT.urls.updateRecordData, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ record_id: BOOT.recordId, data, reasons, status, _token: BOOT.csrf }),
    })
        .then((res) => res.json())
        .then((res) => {
            if (!res.success) {
                window.Swal.fire('Lỗi', res.message || 'Có lỗi xảy ra', 'error');
                return;
            }
            // Đóng dấu người/giờ vừa lưu lên mọi field đã có giá trị (giống V1)
            if (res.updated_by) {
                Object.keys(BOOT.executionValues || {}).forEach((fieldId) => {
                    const rec = BOOT.executionValues[fieldId];
                    if (!rec || typeof rec !== 'object') return;
                    if (!rec._meta) rec._meta = {};
                    if (!rec._meta.default) rec._meta.default = {};
                    rec._meta.default.by = res.updated_by;
                    rec._meta.default.at = res.updated_at;
                });
                window.__V2__.repaintAllFields();
            }
            window.Swal.fire({
                title: 'Thành công', text: 'Đã lưu dữ liệu hồ sơ lô!', icon: 'success',
                showConfirmButton: false, timer: 1500,
            }).then(() => {
                if (status === 'completed' || status === 'reviewed') {
                    window.location.href = BOOT.urls.recordsIndex;
                }
            });
        })
        .catch(() => window.Swal.fire('Lỗi mạng', 'Không thể kết nối đến máy chủ', 'error'));
}

/* ---------------------------------------------------------
 * 6a3. LƯU TỰ ĐỘNG (trang Thực thi lô — bỏ nút "Lưu bản nháp")
 *   Mỗi thao tác nhập liệu / gạch chéo N/A gọi hàm này để ghi NGAY vào hồ sơ.
 *   Khác saveRecordDataV2: KHÔNG mở modal chặn, KHÔNG đổi trạng thái hồ sơ
 *   (status rỗng -> server bỏ qua), chỉ hiện toast nhỏ khi lưu xong. Gửi kèm
 *   reasons (lý do thay đổi giá trị + lý do gạch/hủy N/A) như saveRecordDataV2.
 *   Ghi tuần tự (autoSaveInFlight) để 2 thao tác liên tiếp không đua nhau.
 * --------------------------------------------------------- */
let autoSaveInFlight = false;
let autoSavePending = false;
window.__V2__.autoSaveRecordData = function () {
    if (!BOOT.recordId || BOOT.isReadOnly) return;
    if (autoSaveInFlight) { autoSavePending = true; return; }
    autoSaveInFlight = true;

    const data = BOOT.executionValues || {};
    const reasons = {};
    Object.keys(data).forEach((fieldId) => {
        const reason = data[fieldId] && data[fieldId]._meta && data[fieldId]._meta.default
            && data[fieldId]._meta.default.reason;
        if (reason) reasons[fieldId] = { default: reason };
    });
    const naReasons = naMarks.getPendingReasons();
    if (Object.keys(naReasons).length) reasons['__na__'] = { ...naReasons };

    fetch(BOOT.urls.updateRecordData, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        // status rỗng: chỉ lưu dữ liệu, không đụng trạng thái hồ sơ
        body: JSON.stringify({ record_id: BOOT.recordId, data, reasons, status: '', _token: BOOT.csrf }),
    })
        .then((res) => res.json())
        .then((res) => {
            if (!res.success) { showToast('error', res.message || 'Lưu tự động thất bại'); return; }
            if (res.updated_by) {
                Object.keys(BOOT.executionValues || {}).forEach((fieldId) => {
                    const rec = BOOT.executionValues[fieldId];
                    if (!rec || typeof rec !== 'object') return;
                    if (!rec._meta) rec._meta = {};
                    if (!rec._meta.default) rec._meta.default = {};
                    rec._meta.default.by = res.updated_by;
                    rec._meta.default.at = res.updated_at;
                });
                window.__V2__.repaintAllFields();
            }
            showToast('success', 'Đã lưu');
        })
        .catch(() => showToast('error', 'Lỗi mạng khi lưu tự động'))
        .finally(() => {
            autoSaveInFlight = false;
            if (autoSavePending) { autoSavePending = false; window.__V2__.autoSaveRecordData(); }
        });
};

/* Style dùng riêng cho modal "Lịch sử thay đổi" — nhúng kèm HTML để hiển thị
 * đẹp, chuyên nghiệp (dạng timeline) ở mọi trang có nạp bundle designer-v2. */
const HIST_MODAL_STYLE = `
<style>
.v2-hist-popup { border-radius: 16px !important; }
.v2-hist-popup .swal2-title { padding-top: 1.1em !important; }
.v2-hist-popup .swal2-html-container { margin: 0.6em 0 0 !important; padding: 0 1.1em 0.3em !important; }
.v2-hist-timeline { position: relative; text-align: left; max-height: 58vh; overflow-y: auto; padding: 4px 6px 4px 4px; }
.v2-hist-item { position: relative; display: flex; gap: 12px; padding-bottom: 14px; }
.v2-hist-item:last-child { padding-bottom: 2px; }
.v2-hist-line { position: relative; flex: 0 0 14px; display: flex; justify-content: center; }
.v2-hist-line::before { content: ''; position: absolute; top: 5px; bottom: -14px; width: 2px; background: #e2e8f0; }
.v2-hist-item:last-child .v2-hist-line::before { display: none; }
.v2-hist-dot { position: relative; z-index: 1; width: 12px; height: 12px; margin-top: 3px; border-radius: 50%; background: #f59e0b; box-shadow: 0 0 0 3px #fef3c7; }
.v2-hist-card { flex: 1; min-width: 0; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 9px 12px; }
.v2-hist-values { display: flex; align-items: center; flex-wrap: wrap; gap: 8px; font-size: 0.95rem; word-break: break-word; }
.v2-hist-old { color: #94a3b8; text-decoration: line-through; }
.v2-hist-arrow { color: #cbd5e1; font-size: 0.75rem; }
.v2-hist-new { color: #0f172a; font-weight: 700; }
.v2-hist-empty { color: #cbd5e1; font-weight: 400; font-style: italic; }
.v2-hist-reason { display: flex; gap: 6px; margin-top: 7px; font-size: 0.8rem; color: #64748b; line-height: 1.4; }
.v2-hist-reason i { color: #f59e0b; margin-top: 2px; flex-shrink: 0; }
.v2-hist-foot { display: flex; flex-wrap: wrap; gap: 8px 16px; margin-top: 9px; padding-top: 8px; border-top: 1px dashed #e2e8f0; font-size: 0.72rem; color: #64748b; }
.v2-hist-foot i { margin-right: 4px; color: #94a3b8; }
.v2-hist-by { font-weight: 600; color: #475569; }
</style>`;

// entries: [{ oldVal, newVal, reason, by, at }] — mới nhất tuỳ nguồn dữ liệu
function renderFieldHistoryModal(entries) {
    if (!entries || !entries.length) return;
    const items = entries.map((e) => {
        const oldV = escapeHtmlV2(String(e.oldVal ?? ''));
        const newV = escapeHtmlV2(String(e.newVal ?? ''));
        const reason = e.reason
            ? `<div class="v2-hist-reason"><i class="fas fa-comment-dots"></i><span>${escapeHtmlV2(e.reason)}</span></div>`
            : '';
        return `
            <div class="v2-hist-item">
                <div class="v2-hist-line"><span class="v2-hist-dot"></span></div>
                <div class="v2-hist-card">
                    <div class="v2-hist-values">
                        ${oldV ? `<span class="v2-hist-old">${oldV}</span><i class="fas fa-arrow-right v2-hist-arrow"></i>` : ''}
                        <span class="v2-hist-new">${newV || '<span class="v2-hist-empty">(trống)</span>'}</span>
                    </div>
                    ${reason}
                    <div class="v2-hist-foot">
                        <span class="v2-hist-by"><i class="fas fa-user-edit"></i>${escapeHtmlV2(e.by || '—')}</span>
                        <span class="v2-hist-at"><i class="fas fa-clock"></i>${escapeHtmlV2(e.at || '')}</span>
                    </div>
                </div>
            </div>`;
    }).join('');
    window.Swal.fire({
        title: '<i class="fas fa-history" style="color:#f59e0b; margin-right:8px;"></i>Lịch sử thay đổi',
        html: `${HIST_MODAL_STYLE}<div class="v2-hist-timeline">${items}</div>`,
        confirmButtonText: 'Đóng',
        width: 540,
        customClass: { popup: 'v2-hist-popup' },
    });
}

window.__V2__.showFieldHistory = function (fieldId) {
    // Trang thực thi lô: lịch sử nằm ở server (ebmr_run_data_history), _meta chỉ có history_count
    if (BOOT.recordId) {
        fetch(`${BOOT.urls.runDataHistoryBase}/${BOOT.recordId}/${encodeURIComponent(fieldId)}/default`, {
            headers: { Accept: 'application/json' },
        })
            .then((r) => r.json())
            .then((res) => {
                const list = (res && res.data) || [];
                renderFieldHistoryModal(list.map((h) => ({
                    oldVal: h.old_value, newVal: h.new_value, reason: h.reason, by: h.changed_by, at: h.changed_at,
                })));
            })
            .catch(() => showToast('error', 'Không tải được lịch sử thay đổi'));
        return;
    }
    const rec = BOOT.executionValues[fieldId];
    const list = (rec && rec._meta && rec._meta.default && rec._meta.default.history_list) || [];
    renderFieldHistoryModal(list.map((h) => ({
        oldVal: h.old_val, newVal: h.val, reason: h.reason, by: h.by, at: h.at,
    })));
};

/* ---------------------------------------------------------
 * 6b. Công thức tự động — engine tính toán (rút gọn từ V1: bỏ quét
 *     table-cellId và sum_group vì V2 không có 2 khái niệm này —
 *     mọi biến trong V2 đều đi qua fieldsConfig với tên độc lập).
 * --------------------------------------------------------- */
const calculatingFieldsV2 = new Set();
function parseNumberSafeV2(v) {
    if (v === null || v === undefined || v === '') return 0;
    const n = parseFloat(String(v).replace(/,/g, ''));
    return isNaN(n) ? 0 : n;
}

function calculateFormulaV2(formula, decimalPlaces, targetFieldId) {
    if (!formula) return '0';
    if (targetFieldId) {
        if (calculatingFieldsV2.has(targetFieldId)) return '0'; // tránh lặp vô hạn
        calculatingFieldsV2.add(targetFieldId);
    }
    let processed = formula;
    try {
        const valMap = {};
        const valArrayMap = {};
        const dPlaces = (decimalPlaces !== null && decimalPlaces !== undefined && decimalPlaces !== '') ? parseInt(decimalPlaces) : 2;

        Object.values(fieldsConfig).forEach((field) => {
            if (!field.label && !field.name) return;
            const isUsed = (field.name && formula.includes(field.name)) || (field.label && formula.includes(field.label));
            if (!isUsed) return;

            let val = 0;
            if (field.type === 'formula') {
                val = calculatingFieldsV2.has(field.id) ? 0 :
                    calculateFormulaV2(field.formula || '', field.validation ? field.validation.decimal_places : 2, field.id);
            } else if (BOOT.isExecutionMode && BOOT.executionValues && BOOT.executionValues[field.id] !== undefined) {
                const raw = BOOT.executionValues[field.id];
                val = (raw && typeof raw === 'object' && raw.hasOwnProperty('default')) ? raw.default : raw;
            } else {
                val = (field.defaultValue !== undefined && field.defaultValue !== '') ? field.defaultValue : 0;
            }

            // Checkbox: tick = 1, không tick = 0 (giá trị lưu là true/'true' — parseFloat sẽ ra NaN)
            if (field.type === 'checkbox' && field.id !== targetFieldId) {
                if (field.formula) {
                    // Checkbox tự tick theo công thức: tính công thức của nó thay vì đọc giá trị lưu
                    const r = calculatingFieldsV2.has(field.id) ? 0 :
                        calculateFormulaV2(field.formula, 2, field.id);
                    val = parseNumberSafeV2(r) > 0 ? 1 : 0;
                } else {
                    val = window.__V2__.isCheckboxTrue(val) ? 1 : 0;
                }
            }

            const parsedVal = parseNumberSafeV2(val);
            [field.label, field.name].forEach((key) => {
                if (!key) return;
                valMap[key] = parsedVal;
                if (!valArrayMap[key]) valArrayMap[key] = [];
                valArrayMap[key].push(parsedVal);
            });
        });

        processed = processed.replace(/(SUM_ALL|AVG_ALL|MAX_ALL|MIN_ALL)\(\s*\(*(.*?)\)*\s*\)/gi, (match, funcRaw, id) => {
            const func = funcRaw.toUpperCase();
            const arr = valArrayMap[id.trim()] || [];
            if (!arr.length) return '0';
            if (func === 'SUM_ALL') return `(${arr.join(' + ')})`;
            if (func === 'AVG_ALL') return `(${arr.reduce((a, b) => a + b, 0)} / ${arr.length})`;
            if (func === 'MAX_ALL') return `Math.max(${arr.join(', ')})`;
            if (func === 'MIN_ALL') return `Math.min(${arr.join(', ')})`;
            return '0';
        });

        processed = processed.replace(/\(([^()]+)\)/g, (match, id) => {
            const trimmed = id.trim();
            return valMap[trimmed] !== undefined ? valMap[trimmed] : match;
        });

        const result = new Function(`
            const MAX = Math.max; const max = Math.max;
            const MIN = Math.min; const min = Math.min;
            const SUM = function(...args) { return args.reduce((a,b)=>a+b,0); }; const sum = SUM;
            const AVG = function(...args) { return args.length ? args.reduce((a,b)=>a+b,0)/args.length : 0; }; const avg = AVG;
            const ROUND = function(val, dec) { const p = Math.pow(10, dec||0); return Math.round(val * p) / p; }; const round = ROUND;
            return ${processed};
        `)();
        return (typeof result === 'number') ? result.toLocaleString('en-US', {
            minimumFractionDigits: dPlaces, maximumFractionDigits: dPlaces,
        }) : result;
    } catch (e) {
        return '#ERR';
    } finally {
        if (targetFieldId) calculatingFieldsV2.delete(targetFieldId);
    }
}
window.__V2__.calculateFormula = calculateFormulaV2;

/* ---------------------------------------------------------
 * 6b2. CẢNH BÁO ÂM THANH khi tới chu kỳ lấy mẫu tiếp theo (port từ V1
 *   startContinuousBeep/stopContinuousBeep + autoFillTime — xem
 *   designer/scripts/ui_handlers.blade.php). Áp dụng cho bảng KT Khối lượng
 *   Trung bình (freq_minutes): mỗi lần 1 hàng bất kỳ trong bảng lấy giờ mới
 *   (autoFillExecutionDate), hẹn lại đồng hồ đếm — hết giờ thì phát còi hú
 *   liên tục + hiện hộp thoại nhắc, đến khi người dùng bấm "Đã hiểu".
 * --------------------------------------------------------- */
const sampleTimersV2 = {};
let sampleBeepIntervalV2 = null;
let sampleAudioCtxV2 = null;

/** Tạo (1 lần) + ĐÁNH THỨC AudioContext dùng chung cho còi nhắc lấy mẫu.
 *  BẮT BUỘC gọi trong 1 cử chỉ chạm của người dùng (vd tap ô "Thời gian lấy mẫu"):
 *  chính sách autoplay của trình duyệt để AudioContext tạo/đánh thức NGOÀI cử chỉ
 *  người dùng ở trạng thái "suspended" -> oscillator KHÔNG phát ra tiếng. Trước đây
 *  context được tạo ngay trong setTimeout (nổ sau vài phút, ngoài cử chỉ) nên còi
 *  luôn IM LẶNG ở trang Thực thi — dù hộp thoại nhắc vẫn hiện. */
function primeSampleAudioV2() {
    try {
        const AudioContext = window.AudioContext || window.webkitAudioContext;
        if (!AudioContext) return;
        if (!sampleAudioCtxV2) sampleAudioCtxV2 = new AudioContext();
        if (sampleAudioCtxV2.state === 'suspended') sampleAudioCtxV2.resume();
    } catch (e) { /* trình duyệt không hỗ trợ Web Audio */ }
}

function startContinuousBeepV2() {
    stopContinuousBeepV2();
    try {
        const AudioContext = window.AudioContext || window.webkitAudioContext;
        if (!AudioContext) return;
        // Dùng lại context đã được đánh thức lúc người dùng chạm ô giờ (primeSampleAudioV2).
        // Không tạo mới trong đây: context mới sinh ngoài cử chỉ người dùng sẽ bị treo (câm).
        if (!sampleAudioCtxV2) sampleAudioCtxV2 = new AudioContext();
        const audioCtx = sampleAudioCtxV2;
        if (audioCtx.state === 'suspended') audioCtx.resume(); // thử đánh thức lại phòng khi bị treo

        const playBeep = () => {
            try {
                // Còi hú đặc chủng phòng sản xuất (Dual Sawtooth + Frequency Sweep) — y hệt V1
                const now = audioCtx.currentTime;
                const osc1 = audioCtx.createOscillator();
                const osc2 = audioCtx.createOscillator();
                const gainNode = audioCtx.createGain();
                osc1.connect(gainNode);
                osc2.connect(gainNode);
                gainNode.connect(audioCtx.destination);
                osc1.type = 'sawtooth';
                osc2.type = 'sawtooth';
                osc1.frequency.setValueAtTime(2000, now);
                osc2.frequency.setValueAtTime(2025, now);
                osc1.frequency.exponentialRampToValueAtTime(2800, now + 0.25);
                osc2.frequency.exponentialRampToValueAtTime(2825, now + 0.25);
                gainNode.gain.setValueAtTime(0.45, now);
                gainNode.gain.linearRampToValueAtTime(0.45, now + 0.2);
                gainNode.gain.exponentialRampToValueAtTime(0.001, now + 0.3);
                osc1.start(now);
                osc2.start(now);
                osc1.stop(now + 0.3);
                osc2.stop(now + 0.3);
            } catch (e) { /* bỏ qua lỗi 1 nhịp còi, vòng lặp vẫn tiếp tục */ }
        };
        playBeep();
        sampleBeepIntervalV2 = setInterval(playBeep, 700); // lặp dồn dập mỗi 0.7s
    } catch (e) { /* trình duyệt không hỗ trợ Web Audio */ }
}

function stopContinuousBeepV2() {
    if (sampleBeepIntervalV2) {
        clearInterval(sampleBeepIntervalV2);
        sampleBeepIntervalV2 = null;
    }
}

/* ---------------------------------------------------------
 * Đồng hồ ĐẾM NGƯỢC tới lần lấy mẫu kế tiếp — để người thực hiện chủ động canh
 * giờ (thay vì bị động chờ còi). Mỗi bảng KT Khối lượng Trung bình có 1 mốc hết
 * giờ riêng; pill nổi luôn hiển thị mốc GẦN NHẤT sắp tới. Hết giờ -> đồng thời
 * còi hú + hộp thoại nhắc (xem scheduleWeightSampleAlertV2).
 * --------------------------------------------------------- */
const sampleDeadlinesV2 = {}; // tableId -> { deadline: epoch ms, label }
let sampleCountdownIntervalV2 = null;

function fmtCountdownV2(ms) {
    const totalSec = Math.max(0, Math.round(ms / 1000));
    const m = Math.floor(totalSec / 60);
    const s = totalSec % 60;
    return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
}

/** Giờ đồng hồ (HH:MM) của 1 mốc epoch — dùng để hiện "thời gian lấy mẫu kế tiếp". */
function fmtClockV2(epochMs) {
    const d = new Date(epochMs);
    return `${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`;
}

function renderSampleCountdownV2() {
    let pill = document.getElementById('v2-sample-countdown');
    const ids = Object.keys(sampleDeadlinesV2);
    if (!ids.length) {
        if (pill) pill.remove();
        return;
    }
    // Mốc gần nhất (còn ít thời gian nhất) là thông tin cần hiển thị.
    let soonest = null;
    ids.forEach((id) => {
        const d = sampleDeadlinesV2[id];
        if (!soonest || d.deadline < soonest.deadline) soonest = d;
    });
    const remain = soonest.deadline - Date.now();

    if (!pill) {
        pill = document.createElement('div');
        pill.id = 'v2-sample-countdown';
        pill.style.cssText = [
            'position:fixed', 'left:50%', 'bottom:18px', 'transform:translateX(-50%)',
            'z-index:2000', 'display:flex', 'align-items:center', 'gap:10px',
            'padding:10px 18px', 'border-radius:999px', 'font-family:inherit',
            'box-shadow:0 6px 20px rgba(0,0,0,0.25)', 'color:#fff', 'user-select:none',
            'font-size:1.05rem', 'font-weight:600', 'white-space:nowrap',
        ].join(';');
        pill.innerHTML =
            '<i class="fas fa-stopwatch"></i>' +
            '<span id="v2-sample-countdown-label" style="font-weight:500;opacity:0.95;"></span>' +
            '<span id="v2-sample-countdown-time" style="font-variant-numeric:tabular-nums;font-size:1.25rem;letter-spacing:0.5px;"></span>' +
            '<span id="v2-sample-countdown-at" style="font-weight:500;opacity:0.9;padding-left:6px;border-left:1px solid rgba(255,255,255,0.45);"></span>';
        document.body.appendChild(pill);
    }

    // Đổi màu theo mức khẩn: xanh -> cam (<60s) -> đỏ nhấp nháy (<15s).
    let bg = 'linear-gradient(135deg,#15803d,#22c55e)';
    let pulse = '';
    if (remain <= 15000) { bg = 'linear-gradient(135deg,#b91c1c,#ef4444)'; pulse = 'v2sc-pulse 0.8s ease-in-out infinite'; }
    else if (remain <= 60000) { bg = 'linear-gradient(135deg,#b45309,#f59e0b)'; }
    pill.style.background = bg;
    pill.style.animation = pulse;

    const labelEl = document.getElementById('v2-sample-countdown-label');
    const timeEl = document.getElementById('v2-sample-countdown-time');
    const atEl = document.getElementById('v2-sample-countdown-at');
    if (labelEl) labelEl.textContent = soonest.label || 'Lấy mẫu kế tiếp sau:';
    if (timeEl) timeEl.textContent = fmtCountdownV2(remain);
    if (atEl) atEl.textContent = `lúc ${fmtClockV2(soonest.deadline)}`;
}

function ensureSampleCountdownStyleV2() {
    if (document.getElementById('v2-sample-countdown-style')) return;
    const st = document.createElement('style');
    st.id = 'v2-sample-countdown-style';
    st.textContent = '@keyframes v2sc-pulse{0%,100%{transform:translateX(-50%) scale(1);}50%{transform:translateX(-50%) scale(1.06);}}';
    document.head.appendChild(st);
}

function startSampleCountdownV2(tableId, freqMinutes, label) {
    ensureSampleCountdownStyleV2();
    sampleDeadlinesV2[tableId] = { deadline: Date.now() + freqMinutes * 60 * 1000, label };
    renderSampleCountdownV2();
    if (!sampleCountdownIntervalV2) {
        sampleCountdownIntervalV2 = setInterval(renderSampleCountdownV2, 1000);
    }
}

function stopSampleCountdownV2(tableId) {
    delete sampleDeadlinesV2[tableId];
    renderSampleCountdownV2();
    if (!Object.keys(sampleDeadlinesV2).length && sampleCountdownIntervalV2) {
        clearInterval(sampleCountdownIntervalV2);
        sampleCountdownIntervalV2 = null;
    }
}

/** Tìm bảng KT Khối lượng Trung bình (có freq_minutes) đang CHỨA badge của field này.
 *  KHÔNG dựa vào field.block_id: ở hồ sơ thật cột này thường NULL hoặc là db_id dạng số,
 *  không khớp id chuỗi (blk_v2_...) của bảng trong `items` — nên trước đây tra theo block_id
 *  luôn trượt, khiến còi + đồng hồ đếm ngược không bao giờ chạy. Tra theo DOM chắc chắn hơn:
 *  badge nằm trong .v2-block[data-id] nào thì đó chính là id bảng chứa nó. */
function findFreqTableForFieldV2(fieldId) {
    const badge = document.querySelector(`#v2-pages [data-field-id="${fieldId}"]`);
    const blockEl = badge && badge.closest('.v2-block[data-id]');
    const blockId = blockEl && blockEl.getAttribute('data-id');
    if (!blockId) return null;
    return items.find((i) => i.id === blockId && i.type === 'table' && parseInt(i.freq_minutes, 10) > 0) || null;
}

/** Hẹn lại đồng hồ nhắc chu kỳ lấy mẫu của 1 bảng KT Khối lượng Trung bình. */
function scheduleWeightSampleAlertV2(tableId, freqMinutes) {
    if (!freqMinutes) return;
    if (sampleTimersV2[tableId]) clearTimeout(sampleTimersV2[tableId]);

    startSampleCountdownV2(tableId, freqMinutes, 'Lấy mẫu kế tiếp sau:');

    sampleTimersV2[tableId] = setTimeout(() => {
        delete sampleTimersV2[tableId];
        stopSampleCountdownV2(tableId);
        startContinuousBeepV2();
        window.Swal.fire({
            title: 'Đến giờ lấy mẫu!',
            html: `Đã qua <b>${freqMinutes} phút</b> kể từ lần ghi nhận trước.<br>Vui lòng tiến hành lấy mẫu và cân trọng lượng!`,
            icon: 'warning',
            confirmButtonText: 'Đã hiểu',
            allowOutsideClick: false,
            allowEscapeKey: false,
        }).then(() => stopContinuousBeepV2());
    }, freqMinutes * 60 * 1000);
}

/* ---------------------------------------------------------
 * 6c. Tương tác theo từng loại biến trong Chạy thử
 * --------------------------------------------------------- */
window.__V2__.toggleExecutionCheckbox = function (fieldId) {
    if (!BOOT.isExecutionMode || BOOT.isReadOnly) return;
    const field = fieldsConfig[fieldId];
    if (!field || field.formula) return; // biến khoá theo công thức: không cho tick tay

    // Biến TICK có 3 trạng thái: chưa xác định (chưa chạm) — Có (1) — Không (0). Vòng lặp
    // mỗi lần chạm: chưa xác định -> Có -> Không -> chưa xác định... Cố tình KHÔNG dùng
    // field.defaultValue làm trạng thái ban đầu (khác các biến khác) — cần phân biệt rạch
    // ròi "người dùng đã trả lời Không" khỏi "chưa từng chạm vào" để điều kiện "Kết thúc sản
    // xuất" (mọi biến số phải có giá trị hoặc bị gạch N/A) không hiểu nhầm 2 trạng thái này.
    const stored = getExecDefaultV2(fieldId);
    const isTrue = window.__V2__.isCheckboxTrue(stored);
    const isFalseExplicit = window.__V2__.isCheckboxFalseExplicit(stored);

    let next;
    if (isTrue) next = 0;
    else if (isFalseExplicit) next = null;
    else next = 1;

    window.__V2__.applyExecutionValue(fieldId, next);
};

// Dùng chung giữa toggle/paint/formula để đồng nhất cách hiểu 3 trạng thái tick.
window.__V2__.isCheckboxTrue = function (v) {
    return v === true || v === 'true' || v === 1 || v === '1' || v === 'yes' || v === 'có';
};
window.__V2__.isCheckboxFalseExplicit = function (v) {
    return v === false || v === 'false' || v === 0 || v === '0' || v === 'no' || v === 'không';
};

window.__V2__.handleSelectChange = function (fieldId, value) {
    if (!BOOT.isExecutionMode || BOOT.isReadOnly) return;
    window.__V2__.applyExecutionValue(fieldId, value);
};

window.__V2__.autoFillExecutionDate = function (fieldId) {
    if (!BOOT.isExecutionMode || BOOT.isReadOnly) return;
    const field = fieldsConfig[fieldId];
    if (!field) return;

    // Ô này có thuộc bảng KT Khối lượng Trung bình (có freq_minutes) không? Nếu có, ĐÁNH THỨC
    // AudioContext NGAY trong cử chỉ chạm này — còi nhắc sẽ nổ sau vài phút bằng setTimeout
    // (ngoài cử chỉ người dùng), nếu để tới lúc đó mới tạo context thì trình duyệt tắt tiếng.
    const freqTable = findFreqTableForFieldV2(fieldId);
    if (freqTable) primeSampleAudioV2();

    const now = new Date();
    const timeString = field.date_format === 'hh:mm dd/mm/yyyy'
        ? `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')} ${String(now.getDate()).padStart(2, '0')}/${String(now.getMonth() + 1).padStart(2, '0')}/${now.getFullYear()}`
        : `${String(now.getDate()).padStart(2, '0')}/${String(now.getMonth() + 1).padStart(2, '0')}/${now.getFullYear()}`;
    window.__V2__.applyExecutionValue(fieldId, timeString, (applied) => {
        // Biến này thuộc bảng KT Khối lượng Trung bình (có freq_minutes) -> hẹn lại
        // đồng hồ báo còi cho chu kỳ lấy mẫu tiếp theo (xem scheduleWeightSampleAlertV2).
        if (!applied || !freqTable) return;
        const freq = parseInt(freqTable.freq_minutes, 10);
        scheduleWeightSampleAlertV2(freqTable.id, freq);
        showToast('info', `Đã lấy giờ. Hệ thống sẽ nhắc nhở sau ${freq} phút.`);
    });
};

window.__V2__.openExecutionModal = function (fieldId) {
    if (!BOOT.isExecutionMode || BOOT.isReadOnly) return;
    const field = fieldsConfig[fieldId];
    if (!field) return;

    // Biến số kết nối Cân điện tử: không cho nhập tay qua modal chung — bắt buộc
    // đi qua modal kết nối cân (readScaleValueIntoField) để tránh sai lệch số liệu cân.
    if (field.type === 'number' && field.scaleEnabled) {
        window.__V2__.readScaleValueIntoField(fieldId);
        return;
    }

    let currentVal = getExecDefaultV2(fieldId);
    if (currentVal === undefined || currentVal === null || currentVal === '') currentVal = field.defaultValue || '';

    let inputType = 'text';
    let inputAttrs = {};
    if (field.type === 'number') {
        inputType = 'number';
        if (field.validation) {
            if (!field.validation.allow_out_of_bounds) {
                if (field.validation.min !== null && field.validation.min !== '') inputAttrs.min = field.validation.min;
                if (field.validation.max !== null && field.validation.max !== '') inputAttrs.max = field.validation.max;
            }
            inputAttrs.step = (field.validation.decimal_places && parseInt(field.validation.decimal_places) > 0)
                ? '0.' + '0'.repeat(parseInt(field.validation.decimal_places) - 1) + '1'
                : 'any';
        }
    } else if (field.type === 'date') {
        inputType = 'text';
        inputAttrs.type = field.date_format === 'hh:mm dd/mm/yyyy' ? 'datetime-local' : 'date';
        const d = new Date();
        currentVal = field.date_format === 'hh:mm dd/mm/yyyy'
            ? `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}T${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`
            : `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
    } else if (field.type === 'text') {
        if (field.barcodeScanEnabled) {
            inputAttrs.placeholder = 'Nhập mã Barcode thủ công...';
        } else {
            inputType = 'textarea';
            inputAttrs.rows = 4;
        }
    }

    let hints = [];
    if (field.type === 'number' && field.validation) {
        if (field.validation.min !== null && field.validation.min !== undefined && field.validation.min !== '')
            hints.push(`Tối thiểu: <b>${field.validation.min}</b>`);
        if (field.validation.max !== null && field.validation.max !== undefined && field.validation.max !== '')
            hints.push(`Tối đa: <b>${field.validation.max}</b>`);
    }
    if (field.defaultValue !== null && field.defaultValue !== undefined && field.defaultValue !== '') {
        hints.push(`Mặc định: <b>${field.defaultValue}</b>`);
    }

    let instructionHtml = '';
    if (field.type === 'text' && field.barcodeScanEnabled) {
        instructionHtml += `<div class="mb-3">
            <button type="button" class="btn btn-success w-100 fw-bold shadow-sm" style="padding: 10px; font-size: 1.1rem; border-radius: 8px;" onclick="window.__V2__.startMmsBarcodeScan('${fieldId}')">
                <i class="fas fa-camera me-2"></i> Quét Barcode (MMS)
            </button>
            <div class="text-center mt-2 small text-muted fw-bold">Hoặc nhập mã Barcode thủ công vào ô bên dưới và nhấn Xác nhận:</div>
        </div>`;
    }
    if (field.instruction) {
        instructionHtml += `<div class="alert alert-info text-start small mb-3" style="font-size: 0.85rem; line-height: 1.4; border-left: 4px solid #0dcaf0;"><i class="fas fa-info-circle me-2"></i><b>Hướng dẫn:</b> ${field.instruction}</div>`;
    }
    if (hints.length > 0) {
        instructionHtml += `<div class="text-start mb-2 small text-muted"><i class="fas fa-lightbulb me-1 text-warning"></i> Gợi ý: ${hints.join(' | ')}</div>`;
    }

    window.Swal.fire({
        title: field.label || field.name || 'Nhập dữ liệu',
        html: instructionHtml,
        input: inputType,
        inputValue: currentVal,
        inputAttributes: inputAttrs,
        showCancelButton: true,
        confirmButtonText: 'Xác nhận',
        cancelButtonText: 'Hủy',
        inputValidator: (value) => {
            if (field.validation && field.validation.required && !value) {
                return 'Vui lòng không để trống ô này!';
            }
            if (field.type === 'number' && value !== '' && field.validation && !field.validation.allow_out_of_bounds) {
                const num = Number(value);
                if (field.validation.min !== null && field.validation.min !== '' && num < parseFloat(field.validation.min)) {
                    return 'Giá trị phải lớn hơn hoặc bằng ' + field.validation.min;
                }
                if (field.validation.max !== null && field.validation.max !== '' && num > parseFloat(field.validation.max)) {
                    return 'Giá trị phải nhỏ hơn hoặc bằng ' + field.validation.max;
                }
            }
        },
    }).then(result => {
        if (!result.isConfirmed) return;
        let finalValue = result.value;

        // Barcode nhập tay (không quét camera): tra cứu MMS trước, giá trị thật sự
        // được áp dụng sau khi người dùng chọn thông tin trong modal MMS — không
        // ghi thẳng chuỗi barcode thô vào biến số.
        if (field.type === 'text' && field.barcodeScanEnabled && finalValue && finalValue.trim() !== '') {
            window.__V2__.fetchMmsDataAndShowModal(finalValue.trim(), fieldId);
            return;
        }

        if (field.type === 'date' && finalValue) {
            if (field.date_format === 'hh:mm dd/mm/yyyy') {
                const [dateStr, timeStr] = finalValue.split('T');
                const parts = (dateStr || '').split('-');
                if (parts.length === 3 && timeStr) finalValue = `${timeStr} ${parts[2]}/${parts[1]}/${parts[0]}`;
            } else {
                const parts = finalValue.split('-');
                if (parts.length === 3) finalValue = `${parts[2]}/${parts[1]}/${parts[0]}`;
            }
        }

        window.__V2__.applyExecutionValue(fieldId, finalValue);
    });
};

/* ---------------------------------------------------------
 * 6d. Chữ ký điện tử — xác thực mật khẩu / xác thực người kiểm tra
 * --------------------------------------------------------- */
window.__V2__.openSignatureModal = function (fieldId) {
    if (!BOOT.isExecutionMode || BOOT.isReadOnly) return;
    if (!fieldsConfig[fieldId]) return;

    window.Swal.fire({
        title: '<i class="fas fa-signature me-2 text-primary"></i>Xác nhận chữ ký điện tử',
        html: `<div class="text-start mb-3 small text-muted"><i class="fas fa-info-circle me-1 text-info"></i> Nhập mật khẩu tài khoản của bạn để xác nhận chữ ký điện tử.</div>
               <input type="password" id="v2-sig-password" class="swal2-input" placeholder="Mật khẩu xác nhận" autocomplete="current-password">`,
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-check me-1"></i> Xác nhận',
        cancelButtonText: 'Hủy',
        showLoaderOnConfirm: true,
        didOpen: () => { const inp = document.getElementById('v2-sig-password'); if (inp) inp.focus(); },
        preConfirm: () => {
            const password = document.getElementById('v2-sig-password').value;
            if (!password) { window.Swal.showValidationMessage('Vui lòng nhập mật khẩu xác nhận'); return false; }
            return fetch(BOOT.urls.verifyPassword, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
                body: JSON.stringify({ password, _token: BOOT.csrf }),
            }).then((res) => res.json()).then((data) => {
                if (!data.success) { window.Swal.showValidationMessage(data.message || 'Mật khẩu không chính xác'); return false; }
                return data;
            }).catch((err) => { window.Swal.showValidationMessage('Không thể kết nối đến máy chủ: ' + err.message); return false; });
        },
        allowOutsideClick: () => !window.Swal.isLoading(),
    }).then((result) => {
        if (!result.isConfirmed || !result.value) return;
        const data = result.value;
        if (typeof BOOT.executionValues[fieldId] !== 'object' || BOOT.executionValues[fieldId] === null) BOOT.executionValues[fieldId] = {};
        const oldSigVal = BOOT.executionValues[fieldId].default;
        const newSigVal = data.signature_image || data.fullName || 'Đã ký';
        BOOT.executionValues[fieldId].default = newSigVal;
        BOOT.executionValues[fieldId]._meta = { default: { by: data.fullName || '', at: nowViV2() } };
        // Ký lại (ghi đè chữ ký cũ): tự sinh lý do — không chặn cả lô lưu tự động vì thiếu lý do.
        if (oldSigVal !== undefined && oldSigVal !== null && oldSigVal !== '' && String(oldSigVal) !== String(newSigVal)) {
            BOOT.executionValues[fieldId]._meta.default.reason = `Ký lại bởi ${data.fullName || ''} lúc ${nowViV2()}`;
        }
        window.__V2__.repaintAllFields();
        window.__V2__.autoSaveRecordData && window.__V2__.autoSaveRecordData();
        window.Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: `Đã xác nhận chữ ký: ${data.fullName || ''}`, showConfirmButton: false, timer: 2000 });
    });
};

window.__V2__.openCheckerAuthModal = function (fieldId) {
    if (!BOOT.isExecutionMode || BOOT.isReadOnly) return;
    if (!fieldsConfig[fieldId]) return;

    window.Swal.fire({
        title: '<i class="fas fa-check-double me-2 text-warning"></i>Xác thực người kiểm tra',
        html: `<input type="text" id="v2-checker-user" class="swal2-input" placeholder="Tài khoản người kiểm tra" autocomplete="username">
               <input type="password" id="v2-checker-pass" class="swal2-input" placeholder="Mật khẩu" autocomplete="current-password">`,
        showCancelButton: true,
        confirmButtonText: 'Xác nhận',
        cancelButtonText: 'Hủy',
        showLoaderOnConfirm: true,
        didOpen: () => { const inp = document.getElementById('v2-checker-user'); if (inp) inp.focus(); },
        preConfirm: () => {
            const username = document.getElementById('v2-checker-user').value.trim();
            const password = document.getElementById('v2-checker-pass').value;
            if (!username || !password) { window.Swal.showValidationMessage('Vui lòng nhập tài khoản và mật khẩu.'); return false; }
            return fetch(BOOT.urls.verifyChecker, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
                body: JSON.stringify({ username, password, _token: BOOT.csrf }),
            }).then((res) => res.json()).then((data) => {
                if (!data.success) { window.Swal.showValidationMessage(data.message || 'Xác thực thất bại.'); return false; }
                return data;
            }).catch((err) => { window.Swal.showValidationMessage('Có lỗi xảy ra: ' + err.message); return false; });
        },
        allowOutsideClick: () => !window.Swal.isLoading(),
    }).then((result) => {
        if (!result.isConfirmed || !result.value) return;
        const data = result.value;
        if (typeof BOOT.executionValues[fieldId] !== 'object' || BOOT.executionValues[fieldId] === null) BOOT.executionValues[fieldId] = {};
        const oldCheckerVal = BOOT.executionValues[fieldId].default;
        const newCheckerVal = data.signature_image || data.fullName;
        BOOT.executionValues[fieldId].default = newCheckerVal;
        BOOT.executionValues[fieldId]._meta = { default: { by: data.fullName, at: nowViV2() } };
        // Xác thực lại (ghi đè người kiểm tra cũ): tự sinh lý do — không chặn cả lô lưu tự động vì thiếu lý do.
        if (oldCheckerVal !== undefined && oldCheckerVal !== null && oldCheckerVal !== '' && String(oldCheckerVal) !== String(newCheckerVal)) {
            BOOT.executionValues[fieldId]._meta.default.reason = `Xác thực lại bởi ${data.fullName} lúc ${nowViV2()}`;
        }
        window.__V2__.repaintAllFields();
        window.__V2__.autoSaveRecordData && window.__V2__.autoSaveRecordData();
        window.Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: `Đã xác thực: ${data.fullName}`, showConfirmButton: false, timer: 2000 });
    });
};

/* ---------------------------------------------------------
 * 6e. Select — options thủ công / lấy động từ database (có cache)
 * --------------------------------------------------------- */
const dynamicOptionsCacheV2 = {};
window.__V2__.loadDynamicSelectOptions = function (field, onLoaded) {
    const ds = field.dataSource || {};
    if (!ds.table || !ds.labelCol) { onLoaded([]); return; }
    const cacheKey = [ds.table, ds.labelCol, ds.valueCol || '', ds.where || ''].join('|');
    if (dynamicOptionsCacheV2[cacheKey]) { onLoaded(dynamicOptionsCacheV2[cacheKey]); return; }

    fetch(BOOT.urls.dynamicOptions, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        body: JSON.stringify({ table: ds.table, labelCol: ds.labelCol, valueCol: ds.valueCol || '', where: ds.where || '', _token: BOOT.csrf }),
    }).then((res) => res.json()).then((data) => {
        const options = (data.success && Array.isArray(data.options)) ? data.options : [];
        dynamicOptionsCacheV2[cacheKey] = options;
        onLoaded(options);
    }).catch(() => onLoaded([]));
};

/* =========================================================
 * 6f. TÌM KIẾM & THAY THẾ (Ctrl+F / Ctrl+H) — giống Google Docs
 *   • Tìm/tô sáng trên NỘI DUNG TĨNH đang hiển thị (#v2-pages).
 *   • Thay thế thao tác trực tiếp trên DỮ LIỆU (item.content / cell.content)
 *     bằng cách parse HTML rồi chỉ sửa TEXT NODE (giữ nguyên badge/field/định dạng),
 *     nên không làm hỏng cấu trúc. Match tính trong TỪNG text node (không bắc cầu
 *     qua badge / thẻ định dạng) để đảm bảo thay thế an toàn tuyệt đối.
 * ========================================================= */
const findState = { open: false, replace: false, term: '', replaceTerm: '', matchCase: false, matches: [], current: -1 };

function escapeRegExpV2(s) {
    return String(s).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

/** Các text node có thể tìm kiếm trong 1 phần tử — BỎ QUA phần bên trong badge/field. */
function collectSearchNodesV2(root) {
    const nodes = [];
    const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, {
        acceptNode(n) {
            if (!n.nodeValue) return NodeFilter.FILTER_REJECT;
            if (n.parentElement && n.parentElement.closest(
                '.v2-field-badge, .ebmr-field-badge, .v2-equation-badge, .v2-docprop-badge, .v2-find-hit')) {
                return NodeFilter.FILTER_REJECT;
            }
            return NodeFilter.FILTER_ACCEPT;
        },
    });
    let n; while ((n = walker.nextNode())) nodes.push(n);
    return nodes;
}

/** Duyệt mọi vị trí chứa văn bản của tài liệu (khối văn bản + từng ô bảng). */
function forEachSearchLocationV2(cb) {
    items.forEach((item) => {
        const locked = !!(item.locked || item.isVirtual);
        if (item.type === 'static-text') {
            cb({
                item, locked,
                getHtml: () => item.content || '',
                setHtml: (h) => { item.content = h; },
                domSel: `.v2-block[data-id="${item.id}"] .v2-editable`,
            });
        } else if (item.type === 'table') {
            for (let r = 0; r < (item.rows || 0); r++) {
                for (let c = 0; c < (item.cols || 0); c++) {
                    const cell = item.data && item.data[r] && item.data[r][c];
                    if (!cell || cell.hidden) continue;
                    cb({
                        item, locked, r, c,
                        getHtml: () => cell.content || '',
                        setHtml: (h) => { cell.content = h; },
                        domSel: `.v2-table-wrap[data-id="${item.id}"] td[data-row="${r}"][data-col="${c}"] .v2-cell`,
                    });
                }
            }
        }
    });
}

function findRegexV2() {
    if (!findState.term) return null;
    try { return new RegExp(escapeRegExpV2(findState.term), findState.matchCase ? 'g' : 'gi'); }
    catch (e) { return null; }
}

// Tính match TRỰC TIẾP trên LIVE DOM (offset luôn hợp lệ với chính node sẽ tô sáng).
// Với thay thế, ta map lại về dữ liệu gốc bằng CHỈ SỐ LẦN XUẤT HIỆN (occ) trong từng
// vị trí — vì text tìm-kiếm (bỏ badge) theo thứ tự đọc là như nhau giữa live và dữ liệu,
// dù ranh giới text-node có thể khác. Gọi khi LIVE DOM đã sạch highlight.
function computeMatchesV2() {
    findState.matches = [];
    const re = findRegexV2();
    if (!re) return;
    forEachSearchLocationV2((loc) => {
        const el = document.querySelector(loc.domSel);
        if (!el) return;
        let occ = 0; // thứ tự match trong vị trí này (theo thứ tự đọc)
        collectSearchNodesV2(el).forEach((node) => {
            const text = node.nodeValue || '';
            re.lastIndex = 0;
            let m;
            while ((m = re.exec(text)) !== null) {
                findState.matches.push({ loc, node, start: m.index, length: m[0].length, occ: occ++ });
                if (m.index === re.lastIndex) re.lastIndex++; // tránh kẹt với match rỗng
            }
        });
    });
}

function clearHighlightsV2() {
    document.querySelectorAll('#v2-pages .v2-find-hit').forEach((el) => {
        const parent = el.parentNode;
        if (!parent) return;
        parent.replaceChild(document.createTextNode(el.textContent), el);
        parent.normalize();
    });
}

/** Bọc các match trong 1 text node (xử lý từ CUỐI về ĐẦU để offset không lệch). */
function wrapMatchesInNodeV2(node, list) {
    list.slice().sort((a, b) => b.start - a.start).forEach((mm) => {
        const len = node.nodeValue ? node.nodeValue.length : 0;
        if (mm.start >= len) return; // an toàn: bỏ qua nếu lệch (không bao giờ nên xảy ra)
        const matched = node.splitText(mm.start);
        matched.splitText(Math.min(mm.length, matched.nodeValue.length));
        const span = document.createElement('span');
        span.className = 'v2-find-hit';
        span.dataset.mi = mm.globalIndex;
        matched.parentNode.replaceChild(span, matched);
        span.appendChild(matched);
    });
}

function renderHighlightsV2() {
    // Gom theo chính NODE live (offset đã tính trên node đó nên luôn hợp lệ).
    const groups = new Map();
    findState.matches.forEach((mt, gi) => {
        if (!groups.has(mt.node)) groups.set(mt.node, []);
        groups.get(mt.node).push({ start: mt.start, length: mt.length, globalIndex: gi });
    });
    groups.forEach((list, node) => {
        if (node && node.parentNode) wrapMatchesInNodeV2(node, list);
    });
    updateCurrentHighlightV2();
}

/** Phần tử cuộn gần nhất (nếu tài liệu cuộn trong 1 div overflow thay vì cả cửa sổ). */
function scrollParentV2(el) {
    let p = el.parentElement;
    while (p && p !== document.body) {
        const oy = getComputedStyle(p).overflowY;
        if (/(auto|scroll|overlay)/.test(oy) && p.scrollHeight > p.clientHeight + 1) return p;
        p = p.parentElement;
    }
    return null; // cuộn ở cấp cửa sổ
}

/** Cuộn để đưa 1 match ra GIỮA vùng nhìn — tự nhận diện đúng vùng cuộn (div hoặc window). */
function scrollMatchIntoViewV2(el) {
    const container = scrollParentV2(el);
    const eRect = el.getBoundingClientRect();
    if (container) {
        const cRect = container.getBoundingClientRect();
        const delta = (eRect.top - cRect.top) - (container.clientHeight / 2) + (eRect.height / 2);
        container.scrollBy({ top: delta, behavior: 'smooth' });
    } else {
        const top = window.scrollY + eRect.top - (window.innerHeight / 2) + (eRect.height / 2);
        window.scrollTo({ top: Math.max(0, top), behavior: 'smooth' });
    }
}

function updateCurrentHighlightV2() {
    let curEl = null;
    document.querySelectorAll('#v2-pages .v2-find-hit').forEach((el) => {
        const isCur = parseInt(el.dataset.mi, 10) === findState.current;
        el.classList.toggle('v2-find-current', isCur);
        if (isCur) curEl = el;
    });
    if (curEl) scrollMatchIntoViewV2(curEl);
    const counter = document.getElementById('v2-find-counter');
    if (counter) counter.textContent = findState.matches.length
        ? `${findState.current + 1}/${findState.matches.length}` : '0/0';
}

function gotoMatchV2(idx) {
    if (!findState.matches.length) { findState.current = -1; updateCurrentHighlightV2(); return; }
    const n = findState.matches.length;
    findState.current = ((idx % n) + n) % n;
    updateCurrentHighlightV2();
}

/** Chạy lại tìm kiếm: dọn highlight cũ -> tính trên LIVE DOM sạch -> tô sáng. */
function runFindV2(resetCurrent = true) {
    const input = document.getElementById('v2-find-input');
    findState.term = input ? input.value : '';
    clearHighlightsV2();     // LIVE DOM phải sạch trước khi tính (compute đọc live)
    computeMatchesV2();
    renderHighlightsV2();
    if (resetCurrent) findState.current = findState.matches.length ? 0 : -1;
    else if (findState.current >= findState.matches.length) findState.current = findState.matches.length - 1;
    updateCurrentHighlightV2();
}

/** Thay match hiện tại: định vị trong DỮ LIỆU GỐC bằng chỉ số lần xuất hiện (occ) của vị trí. */
function replaceCurrentV2() {
    const mt = findState.matches[findState.current];
    if (!mt) return;
    if (mt.loc.locked) { gotoMatchV2(findState.current + 1); return; } // khối khóa: chỉ nhảy qua
    const re = findRegexV2();
    if (!re) return;
    saveDocState();
    const doc = new DOMParser().parseFromString(`<div>${mt.loc.getHtml()}</div>`, 'text/html');
    const root = doc.body.firstChild;
    let occ = 0, done = false;
    if (root) {
        for (const node of collectSearchNodesV2(root)) {
            const text = node.nodeValue || '';
            re.lastIndex = 0;
            let m;
            while ((m = re.exec(text)) !== null) {
                if (occ === mt.occ) {
                    node.nodeValue = text.slice(0, m.index) + findState.replaceTerm + text.slice(m.index + m[0].length);
                    done = true;
                    break;
                }
                occ++;
                if (m.index === re.lastIndex) re.lastIndex++;
            }
            if (done) break;
        }
    }
    if (done) {
        mt.loc.setHtml(root.innerHTML);
        mt.loc.item.dirty = true;
        markDirty();
        renderDocument();
    }
    const keep = findState.current;
    runFindV2(false);
    gotoMatchV2(keep); // ở lại vị trí cũ (giờ là match kế tiếp)
}

function replaceAllV2() {
    const re = findRegexV2();
    if (!re) return;
    saveDocState();
    let count = 0;
    forEachSearchLocationV2((loc) => {
        if (loc.locked) return;
        const html = loc.getHtml();
        if (!html) return;
        const doc = new DOMParser().parseFromString(`<div>${html}</div>`, 'text/html');
        const root = doc.body.firstChild;
        if (!root) return;
        let changed = false;
        collectSearchNodesV2(root).forEach((node) => {
            const t = node.nodeValue || '';
            re.lastIndex = 0;
            if (!re.test(t)) return;
            node.nodeValue = t.replace(re, () => { count++; return findState.replaceTerm; });
            changed = true;
        });
        if (changed) { loc.setHtml(root.innerHTML); loc.item.dirty = true; }
    });
    if (count) { markDirty(); renderDocument(); }
    runFindV2(true);
    window.Swal && window.Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: `Đã thay thế ${count} vị trí`, showConfirmButton: false, timer: 2000 });
}

function ensureFindPanelV2() {
    let panel = document.getElementById('v2-find-panel');
    if (panel) return panel;
    panel = document.createElement('div');
    panel.id = 'v2-find-panel';
    panel.innerHTML = `
        <div class="v2-find-row">
            <input type="text" id="v2-find-input" class="v2-find-field" placeholder="Tìm kiếm" autocomplete="off">
            <span id="v2-find-counter" class="v2-find-counter">0/0</span>
            <button type="button" id="v2-find-prev" class="v2-find-btn" title="Kết quả trước (Shift+Enter)"><i class="fas fa-chevron-up"></i></button>
            <button type="button" id="v2-find-next" class="v2-find-btn" title="Kết quả sau (Enter)"><i class="fas fa-chevron-down"></i></button>
            <label class="v2-find-case" title="Phân biệt hoa/thường"><input type="checkbox" id="v2-find-case"> Aa</label>
            <button type="button" id="v2-find-close" class="v2-find-btn" title="Đóng (Esc)"><i class="fas fa-times"></i></button>
        </div>
        <div class="v2-find-row" id="v2-find-replace-row">
            <input type="text" id="v2-replace-input" class="v2-find-field" placeholder="Thay thế bằng" autocomplete="off">
            <button type="button" id="v2-replace-one" class="v2-find-textbtn">Thay thế</button>
            <button type="button" id="v2-replace-all" class="v2-find-textbtn">Tất cả</button>
        </div>`;
    document.body.appendChild(panel);

    const input = panel.querySelector('#v2-find-input');
    const replaceInput = panel.querySelector('#v2-replace-input');
    let deb;
    input.addEventListener('input', () => { clearTimeout(deb); deb = setTimeout(() => runFindV2(true), 150); });
    input.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') { e.preventDefault(); gotoMatchV2(findState.current + (e.shiftKey ? -1 : 1)); }
        else if (e.key === 'Escape') { e.preventDefault(); closeFindV2(); }
    });
    replaceInput.addEventListener('input', () => { findState.replaceTerm = replaceInput.value; });
    replaceInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') { e.preventDefault(); replaceCurrentV2(); }
        else if (e.key === 'Escape') { e.preventDefault(); closeFindV2(); }
    });
    panel.querySelector('#v2-find-prev').addEventListener('click', () => gotoMatchV2(findState.current - 1));
    panel.querySelector('#v2-find-next').addEventListener('click', () => gotoMatchV2(findState.current + 1));
    panel.querySelector('#v2-find-close').addEventListener('click', closeFindV2);
    panel.querySelector('#v2-find-case').addEventListener('change', (e) => { findState.matchCase = e.target.checked; runFindV2(true); });
    panel.querySelector('#v2-replace-one').addEventListener('click', replaceCurrentV2);
    panel.querySelector('#v2-replace-all').addEventListener('click', replaceAllV2);
    return panel;
}

function openFindV2(withReplace) {
    unmountEditor(); // chốt nội dung đang gõ để tìm trên dữ liệu mới nhất
    const panel = ensureFindPanelV2();
    findState.open = true;
    findState.replace = !!withReplace;
    panel.classList.add('open');
    panel.querySelector('#v2-find-replace-row').style.display = withReplace ? 'flex' : 'none';
    const input = panel.querySelector('#v2-find-input');
    findState.replaceTerm = panel.querySelector('#v2-replace-input').value;
    input.focus();
    input.select();
    if (input.value) runFindV2(true);
}

function closeFindV2() {
    findState.open = false;
    clearHighlightsV2();
    findState.matches = [];
    findState.current = -1;
    const panel = document.getElementById('v2-find-panel');
    if (panel) panel.classList.remove('open');
}

/* =========================================================
 * 7. KHỞI ĐỘNG + gắn sự kiện toolbar
 * ========================================================= */
document.addEventListener('DOMContentLoaded', () => {
    selection.init(); // gắn state machine chuột/bàn phím cho chức năng CHỌN

    // Khi đang ở chế độ bắt biến cho công thức: click badge → chèn vào công thức
    document.addEventListener('click', (e) => {
        if (!selectFormulaVarMode) return; // chỉ hoạt động khi đang bắt biến
        const badge = e.target.closest('[data-field-id]');
        if (!badge) return;
        e.preventDefault();
        e.stopPropagation();
        const fieldId = badge.getAttribute('data-field-id');
        const cfg = fieldsConfig[fieldId];
        if (cfg && cfg.name) {
            insertFormulaTokenV2(selectFormulaVarMode, cfg.name, true);
        }
    }, true); // capture phase để đón click trước handler khác

    // Trang THỰC THI LÔ: ghép các dòng động (Thêm dòng Cấp 2) đã lưu của lô này vào
    // bảng tương ứng TRƯỚC lần render đầu — giá trị của chúng tự khớp qua BOOT.executionValues.
    if (BOOT.recordId) mergeRecordStructuresV2();

    renderDocument();
    initFormatControls();
    initFormatPainterV2();
    initFixedToolbar();
    refreshToolbarState();

    // Số tiêu đề phải LUÔN hiện: bất kỳ thay đổi DOM nào trong #v2-pages (mount/unmount
    // editor, repaint badge, đổi tab lặp...) đều có thể thay node tiêu đề làm mất data-hnum
    // -> quan sát childList và đánh số lại (debounce theo frame). Chỉ quan sát childList
    // (không quan sát attributes) nên chính việc gắn data-hnum không tạo vòng lặp.
    const pagesElForHnum = document.getElementById('v2-pages');
    if (pagesElForHnum) {
        let hnumRaf = 0;
        new MutationObserver(() => {
            if (!headingNumberingOnV2()) return;
            cancelAnimationFrame(hnumRaf);
            hnumRaf = requestAnimationFrame(() => updateHeadingNumbersV2());
        }).observe(pagesElForHnum, { childList: true, subtree: true });
    }

    // Phím tắt lưu (Ctrl + S)
    document.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
            e.preventDefault();
            if (BOOT.isReadOnly || BOOT.isExecutionMode) return;
            const btnSave = document.getElementById('v2-btn-save');
            if (btnSave && !btnSave.disabled) {
                saveTemplate();
            } else if (!btnSave) {
                saveTemplate(); // Fallback if button is not present
            }
        }
    });

    // Trang thực thi lô không có nút toggle (blade đã bỏ) — guard thêm cho chắc:
    // không bao giờ cho phép rời execution mode khi đang ghi chép 1 lô thật.
    const btnToggleMode = document.getElementById('v2-btn-toggle-mode');
    if (btnToggleMode && !BOOT.recordId) btnToggleMode.addEventListener('click', toggleExecutionModeV2);

    // Gạch chéo "KHÔNG SỬ DỤNG" (N/A): gắn listener chọn-bằng-chạm + nút toolbar
    naMarks.init();

    document.getElementById('v2-btn-record-confirm-read')?.addEventListener('click', () => {
        window.Swal.fire({
            title: 'Xác nhận Đọc hồ sơ',
            text: 'Bạn có chắc chắn đã xem xét kỹ các số liệu trong hồ sơ này?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Xác nhận',
            cancelButtonText: 'Hủy',
        }).then((result) => {
            if (result.isConfirmed) saveRecordDataV2('reviewed', { statusOnly: true });
        });
    });

    const btnLoopGroup = document.getElementById('v2-btn-loop-group');
    if (btnLoopGroup) {
        btnLoopGroup.addEventListener('mousedown', (e) => e.preventDefault());
        btnLoopGroup.addEventListener('click', () => {
            if (BOOT.isReadOnly || BOOT.isExecutionMode) return;
            setBlockPickModeV2(!blockPickMode);
            if (blockPickMode) showToast('info', 'Click khối ĐẦU, rồi click khối CUỐI để chọn dải khối cần lặp');
        });
    }

    // CHẾ ĐỘ CHỌN KHỐI (Lặp nhóm): đón click/mousedown ở capture phase để khối
    // không mở editor / không kích hoạt chọn ô khi đang chọn dải khối.
    document.addEventListener('mousedown', (e) => {
        if (!blockPickMode) return;
        if (e.target.closest('#v2-loop-pickbar') || e.target.closest('#v2-toolbar') || e.target.closest('.swal2-container')) return;
        if (e.target.closest('.v2-block')) { e.preventDefault(); e.stopPropagation(); }
    }, true);
    document.addEventListener('click', (e) => {
        if (!blockPickMode) return;
        if (e.target.closest('#v2-loop-pickbar') || e.target.closest('#v2-toolbar') || e.target.closest('.swal2-container')) return;
        const blockEl = e.target.closest('.v2-block');
        if (!blockEl) return;
        e.preventDefault();
        e.stopPropagation();
        const item = items.find((i) => i.id === blockEl.getAttribute('data-id'));
        if (!item) return;
        if (item.locked || item.isVirtual) { showToast('warning', 'Khối hệ thống/khóa không thể đưa vào nhóm lặp'); return; }
        pickBlockRangeV2(item);
        updateLoopPickBarV2();
    }, true);
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            if (blockPickMode) setBlockPickModeV2(false);
            else if (blockPickIds.length) clearBlockPickV2();
        }
    });

    // Mục lục
    document.getElementById('v2-btn-toc')?.addEventListener('click', () => {
        const toc = document.getElementById('v2-toc');
        if (toc.classList.contains('open')) toc.classList.remove('open');
        else { buildToc(); toc.classList.add('open'); }
    });
    document.getElementById('v2-toc-close')?.addEventListener('click', () =>
        document.getElementById('v2-toc').classList.remove('open'));

    // Bình luận vùng chọn (kiểu Word) — xem section 5c
    initCommentsV2();

    // Sidebar Thiết bị / Thành phần CO / Biểu mẫu chung GF (chỉ 1 panel trái mở tại 1 thời điểm)
    const leftPanels = ['v2-toc', 'v2-equipment', 'v2-components', 'v2-gf'];
    const togglePanel = (id, onOpen) => {
        const panel = document.getElementById(id);
        if (panel.classList.contains('open')) { panel.classList.remove('open'); return; }
        leftPanels.forEach((p) => document.getElementById(p)?.classList.remove('open'));
        panel.classList.add('open');
        if (onOpen) onOpen();
    };
    document.getElementById('v2-btn-equipment')?.addEventListener('click', () =>
        togglePanel('v2-equipment', () => { if (!equipmentCache) loadEquipmentList(); }));
    document.getElementById('v2-btn-components')?.addEventListener('click', () =>
        togglePanel('v2-components', () => { if (!componentsCache) loadComponentsList(); }));
    document.getElementById('v2-btn-link-gf')?.addEventListener('click', () => {
        if (BOOT.isReadOnly || BOOT.isExecutionMode) {
            window.Swal?.fire({ toast: true, position: 'top-end', icon: 'info', title: 'Chỉ dùng được ở chế độ Thiết kế', showConfirmButton: false, timer: 2500 });
            return;
        }
        togglePanel('v2-gf', () => { if (!allGfsV2) loadGfListV2(); });
    });
    document.querySelectorAll('[data-close-panel]').forEach((btn) =>
        btn.addEventListener('click', () => document.getElementById(btn.getAttribute('data-close-panel')).classList.remove('open')));
    document.getElementById('v2-eq-dept')?.addEventListener('change', loadEquipmentList);
    document.getElementById('v2-eq-search')?.addEventListener('keyup', renderEquipmentList);
    document.getElementById('v2-co-search')?.addEventListener('keyup', renderComponentsList);
    document.getElementById('v2-gf-search')?.addEventListener('keyup', filterGfsV2);

    // Bảng KT Khối lượng Trung bình (chèn bảng nhập + biểu đồ, như V1)
    document.getElementById('v2-btn-weight-chart')?.addEventListener('click', () => {
        if (BOOT.isReadOnly || BOOT.isExecutionMode) {
            window.Swal?.fire({ toast: true, position: 'top-end', icon: 'info', title: 'Chỉ dùng được ở chế độ Thiết kế', showConfirmButton: false, timer: 2500 });
            return;
        }
        weightChartEditId = null;
        setWeightChartModalModeV2(false);
        document.getElementById('v2-wc-weight').value = '7.10';
        document.getElementById('v2-wc-dev').value = '3';
        document.getElementById('v2-wc-freq').value = '15';
        showModalV2('v2WeightChartModal');
    });
    document.getElementById('v2-wc-generate')?.addEventListener('click', generateWeightChartTableV2);
    // Modal bị đóng bằng nút Hủy/X (không qua updateWeightChartTableV2) -> luôn thoát chế độ SỬA
    if (window.jQuery) {
        window.jQuery('#v2WeightChartModal').on('hidden.bs.modal', () => {
            weightChartEditId = null;
            setWeightChartModalModeV2(false);
        });
    }

    // Danh mục chữ viết tắt
    document.getElementById('v2-btn-abbreviation')?.addEventListener('click', () => {
        if (BOOT.isReadOnly || BOOT.isExecutionMode) {
            window.Swal?.fire({ toast: true, position: 'top-end', icon: 'info', title: 'Chỉ dùng được ở chế độ Thiết kế', showConfirmButton: false, timer: 2500 });
            return;
        }
        addAbbreviationV2();
    });
    document.getElementById('v2-abbr-save')?.addEventListener('click', saveAbbreviationV2);

    // Chèn khối VĂN BẢN mới tại đúng vị trí con trỏ / điểm click gần nhất (giống Word)
    document.getElementById('v2-btn-insert-text')?.addEventListener('click', () => {
        if (BOOT.isReadOnly || BOOT.isExecutionMode) {
            window.Swal?.fire({ toast: true, position: 'top-end', icon: 'info', title: 'Chỉ dùng được ở chế độ Thiết kế', showConfirmButton: false, timer: 2500 });
            return;
        }
        unmountEditor(); // chốt nội dung đang gõ dở trước khi chèn khối mới
        const { insertIdx, sectionId } = getInsertPointV2();
        saveDocState();
        insertBlockAndFocusV2(buildNewBlockV2('static-text', sectionId), insertIdx);
    });

    // ── Chèn BẢNG: dropdown lưới rê-chuột chọn nhanh số hàng x cột (giống Word) ──
    const closeTableGridMenuV2 = () => {
        const menu = document.getElementById('v2-table-grid-menu');
        menu?.classList.remove('show');
        menu?.closest('.dropdown')?.classList.remove('show');
    };
    const tgGrid = document.getElementById('v2-table-grid');
    if (tgGrid) {
        const TG_ROWS = 8, TG_COLS = 10;
        for (let r = 1; r <= TG_ROWS; r++) {
            for (let c = 1; c <= TG_COLS; c++) {
                const cell = document.createElement('div');
                cell.className = 'v2-tg-cell';
                cell.dataset.r = r;
                cell.dataset.c = c;
                tgGrid.appendChild(cell);
            }
        }
        const tgLabel = document.getElementById('v2-table-grid-label');
        const paintHot = (R, C) => {
            tgGrid.querySelectorAll('.v2-tg-cell').forEach((el) => {
                el.classList.toggle('hot', +el.dataset.r <= R && +el.dataset.c <= C);
            });
            if (tgLabel) tgLabel.textContent = R && C ? `Bảng ${C} x ${R}` : 'Chèn bảng';
        };
        tgGrid.addEventListener('mouseover', (e) => {
            const cell = e.target.closest('.v2-tg-cell');
            if (cell) paintHot(+cell.dataset.r, +cell.dataset.c);
        });
        tgGrid.addEventListener('mouseleave', () => paintHot(0, 0));
        tgGrid.addEventListener('click', (e) => {
            const cell = e.target.closest('.v2-tg-cell');
            if (!cell) return;
            closeTableGridMenuV2();
            if (BOOT.isReadOnly || BOOT.isExecutionMode) return;
            unmountEditor();
            const { insertIdx, sectionId } = getInsertPointV2();
            saveDocState();
            insertBlockAndFocusV2(buildNewBlockV2('table', sectionId, +cell.dataset.r, +cell.dataset.c), insertIdx);
        });
    }

    // "Chèn bảng..." -> modal nhập chính xác số hàng/cột
    document.getElementById('v2-table-grid-custom')?.addEventListener('click', () => {
        closeTableGridMenuV2();
        if (BOOT.isReadOnly || BOOT.isExecutionMode) {
            window.Swal?.fire({ toast: true, position: 'top-end', icon: 'info', title: 'Chỉ dùng được ở chế độ Thiết kế', showConfirmButton: false, timer: 2500 });
            return;
        }
        showModalV2('v2TableInsertModal');
    });
    document.getElementById('v2-table-insert-confirm')?.addEventListener('click', () => {
        const rows = Math.max(1, Math.min(50, parseInt(document.getElementById('v2-table-rows').value, 10) || 1));
        const cols = Math.max(1, Math.min(20, parseInt(document.getElementById('v2-table-cols').value, 10) || 1));

        unmountEditor();
        const { insertIdx, sectionId } = getInsertPointV2();
        saveDocState();
        insertBlockAndFocusV2(buildNewBlockV2('table', sectionId, rows, cols), insertIdx);
        hideModalV2('v2TableInsertModal');
    });

    // Viền bảng (Borders)
    document.querySelectorAll('#v2-borders-menu [data-border-type]').forEach((link) => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const borderType = link.getAttribute('data-border-type');
            const wrap = selection.getActiveTable();
            if (!wrap) {
                showToast('warning', 'Vui lòng chọn bảng trước');
                return;
            }
            const item = items.find((i) => i.id === wrap.getAttribute('data-id'));
            if (item) {
                applyBorderToSelectedCellsV2(item, borderType);
                showToast('success', 'Đã cập nhật viền bảng');
            }
        });
    });

    // Chèn Symbol
    document.getElementById('v2-btn-symbol')?.addEventListener('click', () => {
        if (BOOT.isReadOnly || BOOT.isExecutionMode) {
            window.Swal?.fire({ toast: true, position: 'top-end', icon: 'info', title: 'Chỉ dùng được ở chế độ Thiết kế', showConfirmButton: false, timer: 2500 });
            return;
        }
        renderSymbolGridV2();
        showModalV2('v2SymbolModal');
    });
    document.querySelectorAll('#v2-symbol-tabs [data-v2-symtab]').forEach((a) => {
        a.addEventListener('click', (e) => {
            e.preventDefault();
            currentSymbolTabV2 = a.getAttribute('data-v2-symtab');
            document.querySelectorAll('#v2-symbol-tabs .nav-link').forEach((x) => x.classList.remove('active'));
            a.classList.add('active');
            renderSymbolGridV2();
        });
    });

    // Chèn Công thức toán học (KaTeX)
    document.getElementById('v2-btn-equation')?.addEventListener('click', () => {
        if (BOOT.isReadOnly || BOOT.isExecutionMode) {
            window.Swal?.fire({ toast: true, position: 'top-end', icon: 'info', title: 'Chỉ dùng được ở chế độ Thiết kế', showConfirmButton: false, timer: 2500 });
            return;
        }
        openEquationEditorV2('', null);
    });
    document.getElementById('v2-eq-input')?.addEventListener('input', updateEquationPreviewV2);
    document.getElementById('v2-eq-insert')?.addEventListener('click', insertEquationV2);
    document.querySelectorAll('.v2-eq-tpl').forEach((btn) => {
        btn.addEventListener('click', () => {
            const input = document.getElementById('v2-eq-input');
            input.value += btn.getAttribute('data-tpl');
            updateEquationPreviewV2();
            input.focus();
        });
    });

    // Chèn hình ảnh
    document.getElementById('v2-btn-image')?.addEventListener('click', () => {
        if (BOOT.isReadOnly || BOOT.isExecutionMode) {
            window.Swal?.fire({ toast: true, position: 'top-end', icon: 'info', title: 'Chỉ dùng được ở chế độ Thiết kế', showConfirmButton: false, timer: 2500 });
            return;
        }
        resetImageModalV2();
        showModalV2('v2ImageModal');
    });
    document.getElementById('v2-img-file')?.addEventListener('change', (e) => {
        const file = e.target.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = () => {
            pendingImageDataUrlV2 = reader.result;
            const prev = document.getElementById('v2-img-preview');
            prev.src = pendingImageDataUrlV2;
            prev.style.display = 'block';
        };
        reader.readAsDataURL(file);
    });
    document.getElementById('v2-img-width')?.addEventListener('input', (e) => {
        document.getElementById('v2-img-width-label').textContent = e.target.value + '%';
    });
    document.getElementById('v2-img-insert')?.addEventListener('click', insertImageV2);

    // Document Property
    document.getElementById('v2-btn-docprop')?.addEventListener('click', () => {
        resetDocPropFormV2();
        renderDocPropListV2();
        showModalV2('v2DocPropModal');
    });
    document.getElementById('v2-dp-add')?.addEventListener('click', () => {
        const keyInput = document.getElementById('v2-dp-key');
        const valInput = document.getElementById('v2-dp-value');
        const key = keyInput.value.trim();
        const val = valInput.value.trim();
        if (!key) { window.Swal?.fire('Lỗi', 'Vui lòng nhập tên thuộc tính.', 'warning'); return; }
        BOOT.docProperties = BOOT.docProperties || {};
        BOOT.docProperties[key] = val;
        resetDocPropFormV2();
        markDirty();
        renderDocPropListV2();
        refreshAllDocPropBadges();
    });

    // Chia đôi màn hình (Split View)
    document.getElementById('v2-btn-split')?.addEventListener('click', toggleWorkspaceSplitV2);

    // Dọn trạng thái kéo-thả khi kết thúc drag (kể cả hủy giữa chừng)
    document.addEventListener('dragend', () => {
        document.body.classList.remove('v2-dragging');
        document.querySelectorAll('.v2-inserter.v2-drop-active').forEach((el) => el.classList.remove('v2-drop-active'));
        const ind = document.getElementById('v2-drop-indicator');
        if (ind) ind.style.display = 'none';
    });

    // ── Kéo-thả Thiết bị/Thành phần: thả TRỰC TIẾP lên trang tại vị trí trỏ chuột ──
    // Không phụ thuộc thanh chèn giữa các khối (đã ẩn) — hoạt động cả với SECTION RỖNG.
    const dropIndicator = document.createElement('div');
    dropIndicator.id = 'v2-drop-indicator';
    document.body.appendChild(dropIndicator);

    /** Điểm chèn theo tọa độ thả: phần tử (block/section) đứng NGAY TRÊN con trỏ.
     *  Trả về { afterIndex, y } — y là vị trí vẽ vạch chỉ dẫn. */
    const dropPointV2 = (pageEl, clientY) => {
        const anchors = Array.from(pageEl.querySelectorAll('.v2-block[data-id], .v2-section[id^="v2-sec-"]'));
        let chosen = null;
        anchors.forEach((el) => {
            const r = el.getBoundingClientRect();
            if (r.top + r.height / 2 <= clientY) chosen = el;
        });
        // Thả phía trên phần tử đầu tiên: coi như thả vào đầu trang (sau anchor đầu — thường là tiêu đề section)
        if (!chosen && anchors.length) chosen = anchors[0];
        if (!chosen) return { afterIndex: items.length - 1, y: pageEl.getBoundingClientRect().top + 8 };
        const cid = chosen.classList.contains('v2-section')
            ? chosen.id.replace(/^v2-sec-/, '') : chosen.getAttribute('data-id');
        const idx = items.findIndex((i) => i.id === cid);
        return { afterIndex: idx !== -1 ? idx : items.length - 1, y: chosen.getBoundingClientRect().bottom + 2 };
    };

    document.addEventListener('dragover', (e) => {
        if (!document.body.classList.contains('v2-dragging')) return;
        const pageEl = e.target.closest && e.target.closest('.v2-page');
        if (!pageEl) { dropIndicator.style.display = 'none'; return; }
        e.preventDefault(); // bắt buộc để trình duyệt cho phép drop
        e.dataTransfer.dropEffect = 'copy';
        const pr = pageEl.getBoundingClientRect();
        const { y } = dropPointV2(pageEl, e.clientY);
        Object.assign(dropIndicator.style, {
            display: 'block', left: (pr.left + 16) + 'px', width: (pr.width - 32) + 'px', top: y + 'px',
        });
    });

    document.addEventListener('drop', (e) => {
        if (!document.body.classList.contains('v2-dragging')) return;
        dropIndicator.style.display = 'none';
        const pageEl = e.target.closest && e.target.closest('.v2-page');
        if (!pageEl) return;
        const action = e.dataTransfer.getData('action');
        // Thiết bị/Thành phần thả trúng thanh chèn: inserter tự xử lý (tránh chèn đôi);
        // khối mới từ toolbar thì inserter không biết action này nên xử lý luôn ở đây.
        if (action !== 'insertNewBlock' && e.target.closest && e.target.closest('.v2-inserter')) return;
        e.preventDefault();
        document.body.classList.remove('v2-dragging');
        const { afterIndex } = dropPointV2(pageEl, e.clientY);
        if (action === 'insertNewBlock') {
            addBlock(e.dataTransfer.getData('blockType') || 'static-text', afterIndex);
            return;
        }
        if (action === 'insertEquipmentTable') {
            const d = e.dataTransfer.getData('equipmentData');
            if (d) insertEquipmentTableV2(afterIndex, JSON.parse(d));
            return;
        }
        if (action === 'insertLinkedGf') {
            insertLinkedGfV2(afterIndex, e.dataTransfer.getData('gfDocCode'), e.dataTransfer.getData('gfName'));
            return;
        }
        const compId = e.dataTransfer.getData('componentId');
        if (compId) importComponentV2(parseInt(compId, 10), e.dataTransfer.getData('componentName') || '', afterIndex);
    });

    // Nút "Chèn văn bản"/"Chèn bảng" trên toolbar: KÉO-THẢ vào giữa 2 khối bất kỳ
    // (dùng chung vạch chỉ vị trí + dropPointV2 với kéo-thả Thiết bị/Thành phần).
    [['v2-btn-insert-text', 'static-text'], ['v2-btn-insert-table', 'table']].forEach(([bid, type]) => {
        const b = document.getElementById(bid);
        if (!b) return;
        b.addEventListener('dragstart', (e) => {
            e.dataTransfer.setData('action', 'insertNewBlock');
            e.dataTransfer.setData('blockType', type);
            e.dataTransfer.effectAllowed = 'copy';
            document.body.classList.add('v2-dragging');
        });
    });

    // Ctrl+F Tìm kiếm / Ctrl+H Tìm & Thay thế (giống Google Docs) — chặn tìm kiếm mặc định của trình duyệt
    document.addEventListener('keydown', (e) => {
        if (!(e.ctrlKey || e.metaKey)) return;
        const k = e.key.toLowerCase();
        if (k === 'f') { e.preventDefault(); openFindV2(false); }
        else if (k === 'h') { e.preventDefault(); openFindV2(true); }
    });

    // Delete/Backspace trong 1 KHỐI VĂN BẢN RỖNG -> xóa cả khối (giống Word).
    // (Khi khối còn nội dung, phím xóa do TipTap xử lý như thường; khối đã đóng editor
    // thì selection.js tự xử lý Delete xóa block qua activeBlockId.)
    document.addEventListener('keydown', (e) => {
        if (e.key !== 'Delete' && e.key !== 'Backspace') return;
        if (!activeEditor || BOOT.isReadOnly || BOOT.isExecutionMode) return;
        const ctxArgs = lastEditorArgs && lastEditorArgs.context;
        if (!ctxArgs || ctxArgs.kind !== 'text') return;   // chỉ khối văn bản (ô bảng không xóa block)
        if (!activeEditor.isEmpty) return;                  // còn chữ -> để TipTap xóa chữ
        e.preventDefault();
        const blockId = ctxArgs.item.id;
        unmountEditor();
        deleteBlock(blockId);
    });

    document.querySelectorAll('#v2-toolbar [data-cmd]').forEach((btn) => {
        btn.addEventListener('mousedown', (e) => e.preventDefault()); // giữ focus editor
        btn.addEventListener('click', () => {
            const name = btn.getAttribute('data-cmd');
            if (name === 'undo') return smartUndo();
            if (name === 'redo') return smartRedo();
            if (name === 'merge-cells') return mergeSelectedCellsV2();
            if (name === 'split-cell') return splitCellV2();
            const action = TOOLBAR_ACTIONS[name];
            if (action) cmd(action, name);
        });
    });

    // Ctrl+Z / Ctrl+Y cấp tài liệu khi KHÔNG gõ trong editor (TipTap tự xử lý khi đang gõ)
    document.addEventListener('keydown', (e) => {
        if (!(e.ctrlKey || e.metaKey) || activeEditor) return;
        if (BOOT.isReadOnly || BOOT.isExecutionMode) return;
        const k = e.key.toLowerCase();
        if (k === 'z') { e.preventDefault(); e.shiftKey ? redoDoc() : undoDoc(); }
        else if (k === 'y') { e.preventDefault(); redoDoc(); }
    });

    // Ctrl+C / Ctrl+X / Ctrl+V trên CẢ CỤM khối đang "chọn" (blockPickIds — bấm nút
    // "Chọn khối" trên toolbar mỗi khối, giữ Shift để chọn cả dải). Nhường hẳn cho
    // selection.js khi đang chọn Ô bảng/biến số (selection.hasCells()) hoặc đang gõ.
    document.addEventListener('keydown', (e) => {
        if (!(e.ctrlKey || e.metaKey) || e.altKey) return;
        if (activeEditor || selection.hasCells()) return;
        if (BOOT.isReadOnly || BOOT.isExecutionMode) return;
        const k = e.key.toLowerCase();
        if ((k === 'c' || k === 'x') && blockPickIds.length) {
            e.preventDefault();
            copyPickedBlocksV2(k === 'x');
        } else if (k === 'v' && blockClipboardV2) {
            const t = e.target;
            if (t && t.closest && (t.closest('input, textarea, select') || t.isContentEditable)) return;
            e.preventDefault();
            internalPasteHandledV2 = true;
            pasteBlockClipboardV2();
        }
    });

    document.querySelectorAll('[data-insert-field]').forEach((el) => {
        el.addEventListener('mousedown', (e) => e.preventDefault());
        el.addEventListener('click', () => insertVariable(el.getAttribute('data-insert-field')));
    });

    const saveBtn = document.getElementById('v2-btn-save');
    if (saveBtn) saveBtn.addEventListener('click', saveTemplate);

    // Chèn khối mới (event delegation vì inserter được tạo lại sau mỗi render)
    const page = document.getElementById('v2-pages');
    if (page) {
        page.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-add]');
            if (!btn) return;
            e.stopPropagation();
            addBlock(btn.getAttribute('data-add'), parseInt(btn.getAttribute('data-after'), 10));
        });
    }

    // (Ctrl+S đã đăng ký Ở TRÊN với đầy đủ guard — TUYỆT ĐỐI không đăng ký thêm listener
    // thứ 2 ở đây: trước đây 1 lần nhấn Ctrl+S kích hoạt CẢ 2 listener -> saveTemplate()
    // chạy 2 lần song song, cả 2 request đều mang db_id=null cho block mới -> server
    // INSERT trùng mỗi block 2 dòng.)

    // Điểm chèn block mới = item (block/section) được click gần nhất — xem getInsertPointV2()
    // ở scope module (dùng chung cho global paste Ctrl+V và gõ-phím-tạo-khối bên dưới).

    // Gõ phím khi CHƯA mở editor nào (giống Word): tự tạo KHỐI VĂN BẢN mới tại điểm
    // chèn (cú click gần nhất trong vùng thiết kế) rồi tiếp tục gõ vào đó luôn,
    // không cần bấm nút "Văn bản" trước.
    document.addEventListener('keydown', (e) => {
        if (BOOT.isReadOnly || BOOT.isExecutionMode) return;
        if (activeEditor) return;                        // đang gõ trong editor: TipTap tự xử lý
        if (e.ctrlKey || e.metaKey || e.altKey) return;  // phím tắt, không phải gõ chữ
        if (e.key.length !== 1) return;                  // bỏ qua Enter/Esc/mũi tên/F1-12...
        if (!lastClickInPagesV2) return;                 // lần click gần nhất không nằm trong vùng thiết kế
        if (blockPickMode || selection.hasCells()) return; // đang chọn khối / chọn ô bảng
        const t = e.target;
        if (t && t.closest && (t.closest('input, textarea, select') || t.isContentEditable
            || t.closest('.modal') || t.closest('.swal2-container'))) return;

        e.preventDefault();
        const ch = e.key;
        const { insertIdx, sectionId } = getInsertPointV2();
        saveDocState();
        const block = {
            id: newBlockId(), type: 'static-text', label: 'Văn bản',
            content: '<p></p>', section_id: sectionId, borderMode: 'none', dirty: true,
        };
        items.splice(insertIdx, 0, block);
        pasteAnchorIdV2 = block.id;
        markDirty();
        renderDocument();

        const inner = document.querySelector(`.v2-block[data-id="${block.id}"] .v2-editable`);
        if (!inner) return;
        mountEditor(inner,
            () => block.content || '',
            (html) => { block.content = html; block.dirty = true; markDirty(); },
            { kind: 'text', item: block });
        // Đưa ký tự vừa gõ vào editor (dạng text node — không parse HTML)
        if (activeEditor) activeEditor.chain().focus().insertContent({ type: 'text', text: ch }).run();
    });

    // Dán (Ctrl+V) khi CHƯA mở editor nào -> tạo block mới như V1 (handleGlobalPaste):
    // bảng thành block bảng, phần còn lại thành block văn bản, thêm vào cuối tài liệu.
    document.addEventListener('paste', (e) => {
        if (internalPasteHandledV2) { internalPasteHandledV2 = false; return; } // vừa Ctrl+V dán cụm khối nội bộ — không dán trùng từ clipboard OS
        if (BOOT.isReadOnly || BOOT.isExecutionMode) return;
        if (activeEditor) return; // đang có editor mở: TipTap tự xử lý (handleEditorPaste)
        if (e.target.closest && (e.target.closest('input') || e.target.closest('textarea'))) return;

        const cb = e.clipboardData || window.clipboardData;
        const htmlData = cb ? cb.getData('text/html') : '';
        const plainText = cb ? cb.getData('text/plain') : '';

        const { insertIdx, sectionId } = getInsertPointV2();

        // Clipboard hệ thống rỗng nhưng lần Ctrl+C gần nhất là "chọn cả bảng" (nút ⊕)
        // -> dán bản sao bảng đó thành block mới tại điểm chèn.
        if (!htmlData && !plainText) {
            const fullTable = selection.getFullTableClipboard();
            if (!fullTable) return;
            e.preventDefault();
            saveDocState();
            const tb = fullTableClipboardToBlock(fullTable, sectionId);
            items.splice(insertIdx, 0, tb);
            pasteAnchorIdV2 = tb.id; // dán tiếp sẽ nằm ngay dưới bảng vừa dán
            markDirty();
            renderDocument();
            return;
        }
        e.preventDefault();

        const newBlocks = [];

        const pushText = (html) => {
            if (!html || !html.trim()) return;
            newBlocks.push({
                id: newBlockId(), type: 'static-text', label: 'Ghi chú (Pasted)',
                content: html, section_id: sectionId, borderMode: 'none', dirty: true,
            });
        };

        if (htmlData) {
            const doc = new DOMParser().parseFromString(cleanWordHtml(htmlData), 'text/html');
            let pending = '';
            const walk = (node) => {
                if (node.nodeName === 'TABLE') {
                    pushText(pending); pending = '';
                    const grid = parseHtmlTableToGrid(node);
                    if (grid) newBlocks.push(gridToTableBlock(grid, sectionId));
                } else if (node.nodeType === Node.ELEMENT_NODE && node.querySelector && node.querySelector('table')) {
                    Array.from(node.childNodes).forEach(walk);
                } else if (node.nodeType === Node.ELEMENT_NODE) {
                    pending += node.outerHTML;
                } else if (node.nodeType === Node.TEXT_NODE) {
                    pending += esc(node.textContent);
                }
            };
            Array.from(doc.body.childNodes).forEach(walk);
            pushText(pending);
        } else {
            pushText('<p>' + esc(plainText).replace(/\r?\n/g, '</p><p>') + '</p>');
        }

        if (newBlocks.length > 0) {
            saveDocState();
            items.splice(insertIdx, 0, ...newBlocks);
            pasteAnchorIdV2 = newBlocks[newBlocks.length - 1].id; // dán tiếp sẽ nối tiếp phía dưới
            markDirty();
            renderDocument();
            const el = document.querySelector(`.v2-block[data-id="${newBlocks[0].id}"]`);
            if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });
});

/* =========================================================
 * RIGHT-CLICK CONTEXT MENU (biến số, ngắt trang)
 * ========================================================= */
document.addEventListener('contextmenu', (e) => {
    const pagesContainer = document.getElementById('v2-pages');
    if (!pagesContainer || !pagesContainer.contains(e.target)) return;

    const varEl = e.target.closest('.v2-field-badge, .ebmr-field-badge');
    const blockEl = e.target.closest('.v2-block');
    const isPageBreak = blockEl && blockEl.getAttribute('data-type') === 'page-break';

    if (!varEl && !blockEl) return;

    e.preventDefault();

    let menu = document.getElementById('v2-context-menu');
    if (!menu) {
        menu = document.createElement('div');
        menu.id = 'v2-context-menu';
        menu.className = 'dropdown-menu shadow-sm';
        menu.style.cssText = 'position:fixed; z-index:99999; min-width:220px; border-radius:4px; max-height:80vh; overflow-y:auto;';
        document.body.appendChild(menu);
    }

    menu.innerHTML = '';

    // ── BÌNH LUẬN VÙNG CHỌN ── (đứng đầu menu, như Word)
    // Chuột phải KHÔNG xoá vùng chọn hiện có, nên bắt neo ngay tại đây là chính xác nhất.
    // Có cả ở chế độ chỉ-đọc: người kiểm tra/phê duyệt BMR cần góp ý mà không được sửa nội dung.
    const cmtSd = cmtCaptureSelection();
    if (cmtSd) {
        const btnCmt = document.createElement('button');
        btnCmt.className = 'dropdown-item small';
        btnCmt.innerHTML = '<i class="fas fa-comment-medical me-2 text-primary"></i> Thêm bình luận';
        btnCmt.onclick = () => {
            hideContextMenuV2();
            cmtLastSel = cmtSd; // cmtStartNew() dùng cache này vì menu đóng làm mất selection
            cmtStartNew();
        };
        menu.appendChild(btnCmt);
        menu.appendChild(document.createElement('hr'));
    }

    // ── BIẾN SỐ ──
    if (varEl) {
        const fieldId = varEl.getAttribute('data-field-id');

        // Copy variable
        const btnCopy = document.createElement('button');
        btnCopy.className = 'dropdown-item small';
        btnCopy.innerHTML = '<i class="fas fa-copy me-2 text-primary"></i> Sao chép biến';
        btnCopy.onclick = () => {
            hideContextMenuV2();
            copyFieldsV2([fieldId]);
            selection.notifyFieldsCopied(); // để Ctrl+V (khi đang gõ) biết clipboard đang giữ BIẾN, không phải Ô
            showToast('success', 'Đã sao chép biến số');
        };
        menu.appendChild(btnCopy);

        // Paste variable
        if (hasFieldClipboardV2()) {
            const btnPaste = document.createElement('button');
            btnPaste.className = 'dropdown-item small';
            btnPaste.innerHTML = '<i class="fas fa-paste me-2 text-success"></i> Dán biến';
            btnPaste.onclick = () => {
                hideContextMenuV2();
                pasteFieldsV2();
            };
            menu.appendChild(btnPaste);
            menu.appendChild(document.createElement('hr'));
        }

        // Delete variable
        const btnDelete = document.createElement('button');
        btnDelete.className = 'dropdown-item small text-danger';
        btnDelete.innerHTML = '<i class="fas fa-trash me-2"></i> Xóa biến';
        btnDelete.onclick = () => {
            hideContextMenuV2();
            deleteVariableV2(fieldId);
        };
        menu.appendChild(btnDelete);
    }

    // ── BẢNG (thao tác kiểu Word — chỉ ở chế độ Thiết kế) ──
    const tableTd = e.target.closest('.v2-table-wrap td');
    let tableItem = null;
    if (tableTd && !BOOT.isReadOnly && !BOOT.isExecutionMode) {
        const twrap = tableTd.closest('.v2-table-wrap');
        const it = items.find((i) => i.id === twrap?.getAttribute('data-id'));
        if (it && !it.locked && !it.isVirtual) tableItem = it;
    }
    if (tableItem) {
        const addItem = (html, onClick, danger = false) => {
            const b = document.createElement('button');
            b.className = 'dropdown-item small' + (danger ? ' text-danger' : '');
            b.innerHTML = html;
            b.onclick = () => { hideContextMenuV2(); onClick(); };
            menu.appendChild(b);
        };
        const addDivider = () => {
            if (!menu.childElementCount) return;
            const hr = document.createElement('hr');
            hr.className = 'dropdown-divider my-1';
            menu.appendChild(hr);
        };

        const r = parseInt(tableTd.dataset.row, 10);
        const c = parseInt(tableTd.dataset.col, 10);
        const cell = (tableItem.data[r] && tableItem.data[r][c]) || {};
        const rs = cell.rs || 1, cs = cell.cs || 1;
        const it = tableItem;

        addDivider();
        addItem('<i class="fas fa-arrow-up me-2 text-primary"></i> Chèn hàng phía trên', () => insertTableRowV2(it, r));
        addItem('<i class="fas fa-arrow-down me-2 text-primary"></i> Chèn hàng phía dưới', () => insertTableRowV2(it, r + rs));
        addItem('<i class="fas fa-arrow-left me-2 text-primary"></i> Chèn cột bên trái', () => insertTableColV2(it, c));
        addItem('<i class="fas fa-arrow-right me-2 text-primary"></i> Chèn cột bên phải', () => insertTableColV2(it, c + cs));

        addDivider();
        if (selection.cellCount() >= 2) {
            addItem('<i class="fas fa-object-group me-2 text-primary"></i> Gộp ô', () => mergeSelectedCellsV2());
        }
        if (rs > 1 || cs > 1) {
            addItem('<i class="fas fa-object-ungroup me-2 text-primary"></i> Tách ô', () => {
                selection.setRange(tableTd, tableTd);
                splitCellV2();
            });
        }
        addItem('<i class="fas fa-arrow-up me-2 text-muted"></i> Căn nội dung: Trên', () => setCellVAlignV2(it, tableTd, 'top'));
        addItem('<i class="fas fa-grip-lines me-2 text-muted"></i> Căn nội dung: Giữa', () => setCellVAlignV2(it, tableTd, 'middle'));
        addItem('<i class="fas fa-arrow-down me-2 text-muted"></i> Căn nội dung: Dưới', () => setCellVAlignV2(it, tableTd, 'bottom'));

        addDivider();
        addItem('<i class="fas fa-arrows-alt-v me-2 text-primary"></i> Phân bố đều các hàng', () => distributeRowsV2(it));
        addItem('<i class="fas fa-arrows-alt-h me-2 text-primary"></i> Phân bố đều các cột', () => distributeColsV2(it));
        addItem('<i class="fas fa-magic me-2 text-primary"></i> AutoFit theo nội dung', () => autoFitTableV2(it));
        addItem(`<i class="fas fa-heading me-2 text-muted"></i> ${it.hideHeader ? 'Hiện' : 'Ẩn'} hàng tiêu đề cột`, () => {
            saveDocState();
            it.hideHeader = !it.hideHeader;
            it.dirty = true;
            markDirty();
            renderDocument();
        });
        // "Cấp 2" = người dùng cuối, CHỈ thao tác được lúc Chạy thử/thực thi (xem
        // showAddRowUI trong renderTable) — bấm ở Thiết kế không hiện nút thêm dòng nên
        // không ai lỡ tay đổi cấu trúc bảng gốc. Tự nhân bản hàng cuối thành hàng mới —
        // xem addRuntimeTableRowV2()/deleteRuntimeTableRowV2(), giống V1.
        addItem(`<i class="fas fa-plus-circle me-2 text-muted"></i> ${it.canAddRows ? 'Tắt' : 'Bật'} cho phép thêm dòng (Cấp 2)`, () => {
            const turningOn = !it.canAddRows;
            let count = it.addRowsCount || 1;
            if (turningOn) {
                const val = window.prompt('Mỗi lần bấm "Thêm dòng" lúc Chạy thử/thực thi, thêm bao nhiêu dòng?', String(count));
                if (val === null) return; // hủy bật
                count = Math.max(1, parseInt(val, 10) || 1);
            }
            saveDocState();
            it.canAddRows = turningOn;
            it.addRowsCount = count;
            it.dirty = true;
            markDirty();
            renderDocument();
        });
        if (it.canAddRows) {
            addItem('<i class="fas fa-list-ol me-2 text-muted"></i> Đổi số dòng thêm mỗi lần...', () => {
                const cur = it.addRowsCount || 1;
                const val = window.prompt('Mỗi lần bấm "Thêm dòng", thêm bao nhiêu dòng?', String(cur));
                if (val === null) return;
                const n = Math.max(1, parseInt(val, 10) || 1);
                saveDocState();
                it.addRowsCount = n;
                it.dirty = true;
                markDirty();
                renderDocument();
            });
        }
        // Đánh dấu CẢ CỘT (không chỉ 1 ô) đang chuột phải thành cột tự động đánh số thứ
        // tự — ghi đè nội dung mọi ô trong cột này thành "#STT#", CSS counter tự đếm lại
        // đúng theo số hàng hiện có (xem replaceSttMarkersV2), thêm/xoá hàng không cần
        // tính lại bằng JS. Dùng được cho BẤT KỲ cột nào, không riêng bảng khối lượng.
        addItem('<i class="fas fa-list-ol me-2 text-muted"></i> Đánh số thứ tự tự động cho cột này (STT)', () => {
            saveDocState();
            for (let rr = 0; rr < it.rows; rr++) {
                if (!it.data[rr]) continue;
                if (!it.data[rr][c] || typeof it.data[rr][c] !== 'object') it.data[rr][c] = { rs: 1, cs: 1, hidden: false };
                it.data[rr][c].content = '<p style="text-align: center;">#STT#</p>';
            }
            it.dirty = true;
            markDirty();
            renderDocument();
            showToast('success', 'Đã đặt cột này tự động đánh số thứ tự');
        });

        addDivider();
        addItem('<i class="fas fa-trash-alt me-2"></i> Xóa hàng', () => deleteTableRowV2(it, r), true);
        addItem('<i class="fas fa-trash-alt me-2"></i> Xóa cột', () => deleteTableColV2(it, c), true);
        addItem('<i class="fas fa-trash me-2"></i> Xóa bảng', () => deleteBlock(it.id), true);
    }

    // ── DÁN BIẾN tại vị trí con trỏ / ô vừa chuột phải — hiện cả khi KHÔNG bấm trúng badge ──
    if (!varEl && hasFieldClipboardV2() && !BOOT.isReadOnly && !BOOT.isExecutionMode) {
        const tdTarget = e.target.closest('.v2-table-wrap td');
        const blkItem = blockEl ? items.find((i) => i.id === blockEl.getAttribute('data-id')) : null;
        const cellOk = tdTarget && !tdTarget.closest('.v2-locked');
        const textOk = blkItem && blkItem.type === 'static-text' && !blkItem.locked && !blkItem.isVirtual;
        if (cellOk || textOk) {
            if (menu.innerHTML) menu.appendChild(document.createElement('hr'));
            const btnPasteVar = document.createElement('button');
            btnPasteVar.className = 'dropdown-item small';
            btnPasteVar.innerHTML = '<i class="fas fa-paste me-2 text-success"></i> Dán biến';
            btnPasteVar.onclick = () => {
                hideContextMenuV2();
                // Editor đang mở (chuột phải ngay chỗ con trỏ) -> dán tại con trỏ;
                // không thì dán vào Ô vừa chuột phải; cuối cùng mới rơi về vị trí soạn gần nhất.
                if (activeEditor) { pasteFieldsV2(); return; }
                if (cellOk) {
                    const twrap = tdTarget.closest('.v2-table-wrap');
                    const it = items.find((i) => i.id === twrap?.getAttribute('data-id'));
                    const r = parseInt(tdTarget.dataset.row, 10), c = parseInt(tdTarget.dataset.col, 10);
                    if (it && !isNaN(r) && !isNaN(c) && pasteFieldsIntoCellV2(it, r, c)) {
                        showToast('success', 'Đã dán biến số vào ô');
                        return;
                    }
                }
                pasteFieldsV2();
            };
            menu.appendChild(btnPasteVar);
        }
    }

    // ── KIỂU CHỮ CẢ KHỐI VĂN BẢN (đổi cấp tiêu đề nhanh, không cần đặt con trỏ) ──
    const txtBlockItem = blockEl ? items.find((i) => i.id === blockEl.getAttribute('data-id')) : null;
    if (txtBlockItem && txtBlockItem.type === 'static-text' && !txtBlockItem.locked && !txtBlockItem.isVirtual
        && !BOOT.isReadOnly && !BOOT.isExecutionMode) {
        if (menu.innerHTML) menu.appendChild(document.createElement('hr'));
        [['p', 'Văn bản thường'], ['h1', 'Tiêu đề 1'], ['h2', 'Tiêu đề 2'], ['h3', 'Tiêu đề 3']].forEach(([tag, label]) => {
            const b = document.createElement('button');
            b.className = 'dropdown-item small';
            b.innerHTML = `<i class="fas fa-heading me-2 text-muted" style="${tag === 'p' ? 'opacity:0.3;' : ''}"></i> ${label}`;
            b.onclick = () => { hideContextMenuV2(); setBlockTextTagV2(txtBlockItem, tag); };
            menu.appendChild(b);
        });
    }

    // ── NGẮT TRANG ──
    if (blockEl) {
        if (menu.innerHTML) menu.appendChild(document.createElement('hr'));

        if (!isPageBreak) {
            const btnInsert = document.createElement('button');
            btnInsert.className = 'dropdown-item small';
            btnInsert.innerHTML = '<i class="fas fa-scissors me-2"></i> Chèn ngắt trang';
            btnInsert.onclick = () => {
                hideContextMenuV2();
                const idx = items.findIndex(i => i.id === blockEl.getAttribute('data-id'));
                if (idx !== -1) {
                    saveDocState();
                    items.splice(idx + 1, 0, { id: 'pb_' + Date.now(), type: 'page-break' });
                    markDirty();
                    renderDocument();
                }
            };
            menu.appendChild(btnInsert);
        } else {
            const btnRemove = document.createElement('button');
            btnRemove.className = 'dropdown-item small text-danger';
            btnRemove.innerHTML = '<i class="fas fa-trash me-2"></i> Xóa ngắt trang';
            btnRemove.onclick = () => {
                hideContextMenuV2();
                const blockId = blockEl.getAttribute('data-id');
                const idx = items.findIndex(i => i.id === blockId);
                if (idx !== -1) {
                    saveDocState();
                    items.splice(idx, 1);
                    markDirty();
                    renderDocument();
                }
            };
            menu.appendChild(btnRemove);
        }
    }

    // Bỏ đường kẻ thừa ở cuối (VD: chỉ có mục "Thêm bình luận", không có mục nào phía sau)
    while (menu.lastElementChild?.tagName === 'HR') menu.lastElementChild.remove();
    if (!menu.childElementCount) return;

    menu.style.left = e.clientX + 'px';
    menu.style.top = e.clientY + 'px';
    menu.classList.add('show');
    // Không cho menu tràn khỏi màn hình (menu bảng khá dài)
    const mRect = menu.getBoundingClientRect();
    if (mRect.bottom > window.innerHeight) menu.style.top = Math.max(8, window.innerHeight - mRect.height - 8) + 'px';
    if (mRect.right > window.innerWidth) menu.style.left = Math.max(8, window.innerWidth - mRect.width - 8) + 'px';

    const closeOnOutside = (ev) => {
        if (!menu.contains(ev.target)) {
            hideContextMenuV2();
            document.removeEventListener('click', closeOnOutside, true);
        }
    };
    setTimeout(() => document.addEventListener('click', closeOnOutside, true), 50);
});

function hideContextMenuV2() {
    const menu = document.getElementById('v2-context-menu');
    if (menu) menu.classList.remove('show');
}

window.hideContextMenuV2 = hideContextMenuV2;
