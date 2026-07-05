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
import { EbmrField, FIELD_TYPES } from './ebmr-field';
import { createSelectionController } from './selection';

/** Bộ extension dùng chung cho mọi editor instance (StarterKit v3 đã gồm Underline + Link) */
function buildExtensions() {
    return [
        StarterKit.configure({
            heading: { levels: [1, 2, 3] },
            link: { openOnClick: false },
        }),
        TextStyleKit, // TextStyle + Color + FontFamily + FontSize + BackgroundColor + LineHeight
        TextAlign.configure({ types: ['heading', 'paragraph'] }),
        Highlight.configure({ multicolor: true }),
        Subscript,
        Superscript,
        EbmrField,
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
let isDirtyDoc = false;       // có thay đổi chưa lưu

// Bộ điều khiển CHỌN đối tượng (ô/hàng/cột/bảng/biến số) — xem selection.js
const selection = createSelectionController({
    BOOT,
    getItems: () => items,
    hasActiveEditor: () => !!activeEditor,
    getActiveHost: () => activeHost,
    unmountEditor: () => unmountEditor(),
    saveDocState: () => saveDocState(),
    markDirty: () => markDirty(),
    renderDocument: () => renderDocument(),
    refreshToolbarState: () => refreshToolbarState(),
    openBatchFieldPanel: (ids) => openBatchFieldPanelV2(ids),
    duplicateFieldsInHtml: (html) => duplicateFieldsInHtmlV2(html),
});

/* =========================================================
 * 0. UNDO/REDO CẤP TÀI LIỆU (thao tác block: thêm/dán/resize...)
 * Trong vùng đang gõ thì TipTap tự có history riêng; stack này
 * phục vụ các thao tác NGOÀI editor (giống undoStack của V1).
 * ========================================================= */
const docUndoStack = [];
const docRedoStack = [];
const DOC_MAX_HISTORY = 50;

function docSnapshot() {
    return JSON.stringify({ items, fieldsConfig });
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
function decorateBadges(html) {
    if (!html) return '';
    const tmp = document.createElement('div');
    tmp.innerHTML = html;
    tmp.querySelectorAll('span.ebmr-field-badge').forEach((el) => {
        const fid = el.getAttribute('data-field-id');
        const cfg = fieldsConfig[fid] || {};
        const t = FIELD_TYPES[cfg.type] || FIELD_TYPES.text;
        el.className = 'v2-field-badge v2-field-badge--static';
        el.innerHTML = `<i class="fas ${t.icon} me-1" style="font-size:0.7em;"></i><span>${esc(cfg.label || cfg.name || fid)}</span>`;
    });
    return tmp.innerHTML;
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

        if (e.dataTransfer.getData('action') === 'insertEquipmentTable') {
            const d = e.dataTransfer.getData('equipmentData');
            if (d) insertEquipmentTableV2(afterIndex, JSON.parse(d));
            return;
        }
        const compId = e.dataTransfer.getData('componentId');
        if (compId) {
            importComponentV2(parseInt(compId, 10), e.dataTransfer.getData('componentName') || '', afterIndex);
        }
    });
    return ins;
}

function renderDocument() {
    const container = document.getElementById('v2-pages');
    if (!container) return;
    container.innerHTML = '';

    // Mỗi SECTION tự động bắt đầu 1 TRANG MỚI (ngắt trang như yêu cầu).
    let page = null;
    let pageNo = 0;
    const newPage = () => {
        pageNo++;
        page = document.createElement('div');
        page.className = 'v2-page shadow';
        page.setAttribute('data-page-label', 'Trang ' + pageNo);
        container.appendChild(page);
    };

    let renderedAny = false;

    items.forEach((item, idx) => {
        if (item.type === 'document-settings') return; // block cấu hình ẩn, giữ nguyên khi lưu
        const isLocked = item.locked || item.isVirtual;
        let el = null;

        if (item.type === 'section') {
            newPage(); // ngắt trang tại mỗi section
            el = document.createElement('div');
            el.className = 'v2-section';
            el.id = 'v2-sec-' + item.id;
            // Markup đồng bộ style section của V1: icon tròn + tiêu đề + gạch gradient
            el.innerHTML = `
                <div class="v2-section-icon"><i class="fas fa-layer-group"></i></div>
                <div class="v2-section-body">
                    <div class="v2-section-title">${esc(item.label || 'Tên phân đoạn')}${isLocked ? ' <i class="fas fa-lock ms-1" style="font-size:0.65em;opacity:0.55;" title="Section hệ thống (khóa)"></i>' : ''}</div>
                    <div class="v2-section-line"></div>
                </div>`;
        } else if (item.type === 'static-text') {
            el = document.createElement('div');
            el.className = 'v2-block v2-static-text' + (isLocked ? ' v2-locked' : '');
            el.setAttribute('data-id', item.id);
            const inner = document.createElement('div');
            inner.className = 'v2-editable';
            inner.innerHTML = decorateBadges(item.content || '<p></p>');
            el.appendChild(inner);
            if (!BOOT.isReadOnly && !BOOT.isExecutionMode && !isLocked) {
                inner.addEventListener('click', (e) => {
                    if (activeHost === inner) return;
                    e.stopPropagation();
                    mountEditor(inner,
                        () => item.content || '',
                        (html) => { item.content = html; item.dirty = true; markDirty(); },
                        { kind: 'text', item });
                });
            }
        } else if (item.type === 'table') {
            el = renderTable(item);
            if (isLocked) el.classList.add('v2-locked');
        } else {
            // Loại block chưa hỗ trợ trong pilot (chart, linked-template...) — hiển thị placeholder
            el = document.createElement('div');
            el.className = 'v2-block v2-unsupported';
            el.innerHTML = `<i class="fas fa-cube me-2"></i>[${esc(item.type)}] ${esc(item.label || '')} — <em>chưa hỗ trợ trong bản thử nghiệm, giữ nguyên khi lưu</em>`;
        }

        if (el) {
            if (!page) newPage(); // block đứng trước section đầu tiên vẫn có trang

            // Nút bình luận trên từng block (trừ section) — kiểu Google Docs
            if (item.type !== 'section' && item.id) {
                const cmtCount = getBlockComments(item.id).length;
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'v2-comment-btn' + (cmtCount > 0 ? ' has-comments' : '');
                btn.title = cmtCount > 0 ? `${cmtCount} bình luận` : 'Thêm bình luận';
                btn.innerHTML = '<i class="fas fa-comment"></i>' +
                    (cmtCount > 0 ? `<span class="v2-cmt-count">${cmtCount}</span>` : '');
                btn.addEventListener('mousedown', (e) => e.preventDefault());
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    openCommentsPanel(item.id);
                });
                el.appendChild(btn);
            }

            page.appendChild(el);
            renderedAny = true;
            // Cho chèn khối mới dưới block thường, hoặc dưới block ảo CUỐI CÙNG
            // (để thêm nội dung ngay sau vùng hệ thống)
            const isLastVirtual = item.isVirtual && (!items[idx + 1] || !items[idx + 1].isVirtual);
            if (!BOOT.isReadOnly && !BOOT.isExecutionMode && (!item.isVirtual || isLastVirtual)) {
                page.appendChild(makeInserter(idx));
            }
        }
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
}

/* =========================================================
 * 1b. THÊM KHỐI MỚI (Văn bản / Bảng)
 * ========================================================= */
function newBlockId() {
    return 'blk_v2_' + Date.now().toString(36) + Math.random().toString(36).slice(2, 6);
}

function addBlock(type, afterIndex) {
    if (BOOT.isReadOnly || BOOT.isExecutionMode) return; // Chạy thử: không cho thêm khối mới
    saveDocState();
    // Kế thừa section_id từ block đứng trước để khối mới nằm đúng công đoạn
    const anchor = items[afterIndex] || null;
    const sectionId = anchor ? (anchor.type === 'section' ? (anchor.section_id || anchor.id) : anchor.section_id) : null;

    let block;
    if (type === 'table') {
        const cols = 3, rows = 2;
        block = {
            id: newBlockId(), type: 'table', label: 'Bảng mới',
            rows, cols,
            columns: Array.from({ length: cols }, (_, i) => ({ label: `Cột ${i + 1}`, width: '' })),
            data: Array.from({ length: rows }, () =>
                Array.from({ length: cols }, () => ({ content: '', rs: 1, cs: 1, hidden: false }))),
            rowHeights: [], borderMode: 'all', hideHeader: false,
            section_id: sectionId, dirty: true,
        };
    } else {
        block = {
            id: newBlockId(), type: 'static-text', label: 'Văn bản',
            content: '<p></p>', section_id: sectionId, dirty: true,
        };
    }

    const insertAt = Math.min(afterIndex + 1, items.length);
    items.splice(insertAt, 0, block);
    markDirty();
    renderDocument();

    // Tự mở editor vào khối văn bản vừa thêm để gõ được ngay
    const el = document.querySelector(`.v2-block[data-id="${block.id}"] .v2-editable`);
    if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
        if (type === 'static-text') el.click();
    }
}

function renderTable(item) {
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
            if (cell.textAlign) td.style.textAlign = cell.textAlign;
            if (cell.fontWeight) td.style.fontWeight = cell.fontWeight;
            if (cell.fontStyle) td.style.fontStyle = cell.fontStyle;
            if (item.columns && item.columns[c] && item.columns[c].width) td.style.width = item.columns[c].width;

            const inner = document.createElement('div');
            inner.className = 'v2-editable v2-cell';
            inner.innerHTML = decorateBadges(cell.content || '');
            td.appendChild(inner);

            if (!BOOT.isReadOnly && !BOOT.isExecutionMode && !item.locked) {
                inner.addEventListener('click', (e) => {
                    if (activeHost === inner) return;
                    e.stopPropagation();
                    const cellRef = cell;
                    const rowIdx = r, colIdx = c;
                    mountEditor(inner,
                        () => cellRef.content || '',
                        (html) => { cellRef.content = html; item.dirty = true; markDirty(); },
                        { kind: 'cell', item, r: rowIdx, c: colIdx });
                });
            }
            tr.appendChild(td);
        }
        tbody.appendChild(tr);
    }
    table.appendChild(tbody);
    attachTableResizers(item, table);
    wrap.appendChild(table);
    selection.decorateTable(item, wrap, table); // nút ⊕ + gutter chọn hàng/cột
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

    // --- Bề rộng CỘT: resizer ở cạnh phải các ô hàng đầu tiên (ô colspan chỉnh cột cuối nó phủ) ---
    const firstRow = table.querySelector('thead tr') || table.querySelector('tbody tr');
    if (firstRow) {
        let colCursor = 0;
        Array.from(firstRow.children).forEach((cell) => {
            const span = cell.colSpan || 1;
            const targetCol = colCursor + span - 1;
            colCursor += span;
            if (!item.columns || !item.columns[targetCol]) return;
            const rz = document.createElement('div');
            rz.className = 'v2-col-resizer';
            rz.addEventListener('mousedown', (e) => {
                const colEl = cols[targetCol];
                const startX = e.clientX;
                const startW = (colEl && colEl.getBoundingClientRect().width) || cell.getBoundingClientRect().width;
                saveDocState();
                startDrag(e, rz,
                    (ev) => {
                        const w = Math.max(30, startW + ev.clientX - startX);
                        if (colEl) colEl.style.width = w + 'px';
                    },
                    (ev) => {
                        const w = Math.max(30, Math.round(startW + ev.clientX - startX));
                        item.columns[targetCol].width = w + 'px';
                        item.dirty = true;
                        markDirty();
                    });
            });
            cell.appendChild(rz);
        });
    }

    // --- Chiều cao HÀNG: resizer ở cạnh dưới ô đầu mỗi hàng ---
    table.querySelectorAll('tbody tr').forEach((tr) => {
        const firstCell = tr.querySelector('td');
        if (!firstCell) return;
        const rIdx = parseInt(tr.dataset.rowIdx, 10);
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
                    item.rowHeights[rIdx] = h + 'px';
                    item.dirty = true;
                    markDirty();
                });
        });
        firstCell.appendChild(rz);
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
    return html
        .replace(/<!--\[if[\s\S]*?<!\[endif\]-->/gi, '')   // conditional comments
        .replace(/<!--[\s\S]*?-->/g, '')                    // comment thường
        .replace(/<\/?o:p[^>]*>/gi, '')                     // thẻ Office namespace
        .replace(/<style[^>]*>[\s\S]*?<\/style>/gi, '')     // block <style> + toàn bộ CSS bên trong
        .replace(/<xml[^>]*>[\s\S]*?<\/xml>/gi, '')         // block <xml> của Office
        .replace(/<\/?(meta|link)[^>]*>/gi, '')             // thẻ meta/link đơn lẻ
        .replace(/\sclass="?Mso[a-zA-Z]+"?/g, '')           // class MsoNormal...
        .replace(/\sstyle="[^"]*mso-[^"]*"/gi, '')          // style chứa mso-*
        .replace(/<span[^>]*>\s*<\/span>/g, '');            // span rỗng còn sót
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

function mountEditor(host, getHTML, setHTML, context = null) {
    if (BOOT.isReadOnly || BOOT.isExecutionMode) return; // Chạy thử: không cho sửa cấu trúc
    selection.clearCells(); // mở editor = thoát chế độ chọn ô
    unmountEditor(); // đóng editor đang mở (ghi dữ liệu về trước)

    host.classList.add('v2-editing');
    host.innerHTML = '';

    activeEditor = new Editor({
        element: host,
        extensions: buildExtensions(),
        content: getHTML() || '<p></p>',
        autofocus: 'end',
        onUpdate: () => markDirty(),
        editorProps: {
            // Dọn rác Word trước khi ProseMirror parse nội dung dán vào
            transformPastedHTML: (html) => cleanWordHtml(html),
            // Tái tạo hành vi dán của V1: bảng/lưới Excel không dán inline mà
            // trải vào lưới bảng (nếu đang ở ô) hoặc thành block bảng mới.
            handlePaste: (view, event) => handleEditorPaste(event, context),
        },
    });
    activeHost = host;
    const editorInstance = activeEditor;
    activeSync = () => {
        let html = editorInstance.getHTML() || '';
        html = html.replace(/ ProseMirror-selectednode/g, '').replace(/ProseMirror-selectednode/g, '').replace(/ class=""/g, '');
        setHTML(html);
    };
    refreshToolbarState();

    activeEditor.on('selectionUpdate', refreshToolbarState);
    activeEditor.on('transaction', refreshToolbarState);
}

/**
 * Xử lý dán bên trong editor đang mở.
 * Trả về true = đã tự xử lý (TipTap bỏ qua); false = để TipTap dán inline.
 */
function handleEditorPaste(event, context) {
    const cb = event.clipboardData || window.clipboardData;
    if (!cb) return false;
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

    return false; // mặc định: TipTap dán inline (đã qua cleanWordHtml)
}

function unmountEditor() {
    if (!activeEditor) return;
    const host = activeHost;
    const sync = activeSync;
    const editor = activeEditor;
    activeEditor = null; activeHost = null; activeSync = null;

    sync();                              // ghi HTML về items (TipTap xuất badge chuẩn cũ)
    let html = editor.getHTML() || '';
    html = html.replace(/ ProseMirror-selectednode/g, '').replace(/ProseMirror-selectednode/g, '').replace(/ class=""/g, '');
    editor.destroy();
    host.classList.remove('v2-editing');
    host.innerHTML = decorateBadges(html); // trở lại chế độ xem tĩnh
    refreshToolbarState();
}

// Click ra ngoài vùng đang edit -> unmount (giữ nguyên khi click vào toolbar/panel)
document.addEventListener('mousedown', (e) => {
    if (!activeHost) return;
    if (activeHost.contains(e.target)) return;
    if (e.target.closest('#v2-toolbar') || e.target.closest('#v2-field-panel')) return;
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

/** Chèn/sửa liên kết như Google Docs (Ctrl+K) */
function promptLink() {
    if (!activeEditor) return;
    const prev = activeEditor.getAttributes('link').href || '';
    const url = window.prompt('Địa chỉ liên kết (để trống để gỡ liên kết):', prev);
    if (url === null) return;
    if (url === '') activeEditor.chain().focus().unsetLink().run();
    else activeEditor.chain().focus().extendMarkRange('link').setLink({ href: url }).run();
}

// Các lệnh áp dụng được ở CẤP Ô khi chọn nhiều ô (không cần editor)
const CELL_LEVEL_CMDS = ['bold', 'italic', 'align-left', 'align-center', 'align-right', 'align-justify', 'clear-format'];

function refreshToolbarState() {
    const bar = document.getElementById('v2-toolbar');
    if (!bar) return;
    const hasCellSel = selection.hasCells();
    bar.querySelectorAll('[data-cmd], [data-fmt], #v2-btn-link').forEach((el) => {
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
    const fontSel = document.getElementById('v2-sel-font');
    if (fontSel) fontSel.value = ts.fontFamily || '';
    const sizeSel = document.getElementById('v2-sel-size');
    if (sizeSel) sizeSel.value = ts.fontSize || '';
    const lhSel = document.getElementById('v2-sel-lineheight');
    if (lhSel) lhSel.value = ts.lineHeight || '';
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
    on('v2-sel-font', (v) => {
        const ch = activeEditor.chain().focus();
        v ? ch.setFontFamily(v).run() : ch.unsetFontFamily().run();
    });
    on('v2-sel-size', (v) => {
        const ch = activeEditor.chain().focus();
        v ? ch.setFontSize(v).run() : ch.unsetFontSize().run();
    });
    on('v2-sel-lineheight', (v) => {
        const ch = activeEditor.chain().focus();
        v ? ch.setLineHeight(v).run() : ch.unsetLineHeight().run();
    });

    const color = document.getElementById('v2-color-text');
    if (color) color.addEventListener('input', () => {
        if (activeEditor) activeEditor.chain().focus().setColor(color.value).run();
    });
    const hl = document.getElementById('v2-color-highlight');
    if (hl) hl.addEventListener('input', () => {
        if (activeEditor) { activeEditor.chain().focus().setHighlight({ color: hl.value }).run(); return; }
        // Chọn nhiều ô: tô màu NỀN cả ô (prop cấp ô, tương thích V1)
        if (selection.hasCells()) selection.applyToCells((cell) => { cell.backgroundColor = hl.value; });
    });
    const hlOff = document.getElementById('v2-btn-unhighlight');
    if (hlOff) {
        hlOff.addEventListener('mousedown', (e) => e.preventDefault());
        hlOff.addEventListener('click', () => {
            if (activeEditor) { activeEditor.chain().focus().unsetHighlight().run(); return; }
            if (selection.hasCells()) selection.applyToCells((cell) => { cell.backgroundColor = ''; });
        });
    }
    const linkBtn = document.getElementById('v2-btn-link');
    if (linkBtn) {
        linkBtn.addEventListener('mousedown', (e) => e.preventDefault());
        linkBtn.addEventListener('click', promptLink);
    }
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
    markDirty();
    openFieldPanel(fieldId);
}

/* ----------------------------------------------------------
 * 4a-bis. Nhân bản biến số trong 1 đoạn HTML (dùng khi COPY/PASTE ô)
 *   Mỗi badge data-field-id được clone config sang id MỚI để 2 ô
 *   không trỏ chung 1 biến. Trả về HTML đã thay id.
 * ---------------------------------------------------------- */
function duplicateFieldsInHtmlV2(html) {
    if (!html || typeof html !== 'string' || html.indexOf('data-field-id') === -1) return html;
    const tmp = document.createElement('div');
    tmp.innerHTML = html;
    let out = html;
    const seen = new Set();
    tmp.querySelectorAll('[data-field-id]').forEach((el) => {
        const oldId = el.getAttribute('data-field-id');
        if (!oldId || seen.has(oldId) || !fieldsConfig[oldId]) return;
        seen.add(oldId);
        const newId = 'field_v2_' + Date.now().toString(36) + Math.random().toString(36).slice(2, 8);
        const clone = JSON.parse(JSON.stringify(fieldsConfig[oldId]));
        clone.id = newId;
        clone.name = newId;
        fieldsConfig[newId] = clone;
        out = out.split(oldId).join(newId);
    });
    markDirty();
    return out;
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
               path.endsWith('barcodeScanEnabled') || path.endsWith('allow_out_of_bounds')) {
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
function insertFormulaTokenV2(fieldId, token, isVarRef = false) {
    const el = document.getElementById(`v2-formula-input-${fieldId}`);
    if (!el) return;
    el.focus();
    const sel = window.getSelection();
    if (sel && sel.rangeCount > 0 && el.contains(sel.anchorNode)) {
        const range = sel.getRangeAt(0);
        range.deleteContents();
        let node;
        if (isVarRef) {
            node = document.createElement('span');
            node.className = 'v2-formula-var';
            node.contentEditable = 'false';
            node.textContent = token;
            node.dataset.var = token;
        } else {
            node = document.createTextNode(token);
        }
        range.insertNode(node);
        range.setStartAfter(node);
        range.collapse(true);
        sel.removeAllRanges();
        sel.addRange(range);
    } else {
        if (isVarRef) {
            const span = document.createElement('span');
            span.className = 'v2-formula-var';
            span.contentEditable = 'false';
            span.textContent = token;
            span.dataset.var = token;
            el.appendChild(span);
        } else {
            el.appendChild(document.createTextNode(token));
        }
    }
    // Serialize và sync về fieldsConfig
    const formulaStr = serializeFormulaElementV2(el);
    syncFieldConfigV2(fieldId, 'formula', formulaStr);
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
    // Tách (varName) thành span màu, phần còn lại là text thuần
    return formula.replace(/\(([^()]+)\)/g, (match, varName) => {
        const cfg = Object.values(fieldsConfig).find(f => f.name === varName.trim());
        const label = cfg ? (cfg.label || varName.trim()) : varName.trim();
        return `<span class="v2-formula-var" contenteditable="false" data-var="${esc(varName.trim())}" title="${esc(label)}">${esc(varName.trim())}</span>`;
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
                <option value="text"      ${cfg.type==='text'      ?'selected':''}>Văn bản tự do</option>
                <option value="number"    ${cfg.type==='number'    ?'selected':''}>Số liệu (Tính toán)</option>
                <option value="checkbox"  ${cfg.type==='checkbox'  ?'selected':''}>Hộp kiểm (Tick)</option>
                <option value="formula"   ${cfg.type==='formula'   ?'selected':''}>Công thức tự động (=)</option>
                <option value="date"      ${cfg.type==='date'      ?'selected':''}>Thời Gian</option>
                <option value="signature" ${cfg.type==='signature' ?'selected':''}>Chữ ký điện tử</option>
                <option value="select"    ${cfg.type==='select'    ?'selected':''}>Chọn từ danh sách</option>
            </select>
        </div>
        <hr class="opacity-25 my-2">
        <div class="mb-3">
            <label class="v2-prop-label">Nhãn hiển thị</label>
            <input type="text" class="form-control form-control-sm" id="v2fp-label-${fieldId}"
                value="${esc(cfg.label || '')}"
                oninput="syncFieldConfigV2('${fieldId}','label',this.value); _v2RepaintBadge('${fieldId}')">
            <div class="form-text" style="font-size:0.68rem">VD: Số lượng, Kết quả</div>
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
                            <option value="=" ${(cfg.na_condition && cfg.na_condition.operator==='=') ?'selected':''}>Bằng (=)</option>
                            <option value="!=" ${(cfg.na_condition && cfg.na_condition.operator==='!=') ?'selected':''}>Khác (!=)</option>
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
                </div>
                <div id="v2-formula-input-${fieldId}"
                    class="form-control form-control-sm border-success font-monospace v2-formula-editor"
                    contenteditable="true"
                    placeholder="Gõ công thức hoặc chọn biến từ dropdown..."
                    oninput="syncFieldConfigV2('${fieldId}','formula',serializeFormulaElementV2(this))"
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
                </div>
                <div id="v2-formula-input-${fieldId}"
                    class="form-control form-control-sm border-success font-monospace v2-formula-editor"
                    contenteditable="true"
                    placeholder="Công thức > 0 hoặc TRUE → tự Tick..."
                    oninput="syncFieldConfigV2('${fieldId}','formula',serializeFormulaElementV2(this))"
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
                    <option value="dd/mm/yyyy" ${(!cfg.date_format||cfg.date_format==='dd/mm/yyyy')?'selected':''}>Ngày (dd/mm/yyyy)</option>
                    <option value="hh:mm dd/mm/yyyy" ${cfg.date_format==='hh:mm dd/mm/yyyy'?'selected':''}>Giờ và Ngày (hh:mm dd/mm/yyyy)</option>
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
                    <option value="manual"   ${dsType==='manual'   ?'selected':''}>Nhập thủ công (Tự định nghĩa)</option>
                    <option value="database" ${dsType==='database' ?'selected':''}>Lấy tự động từ CSDL (Database)</option>
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
                        class="btn btn-sm ${styleObj.width==='100%'?'btn-primary active':'btn-outline-secondary'} flex-fill"
                        onclick="_v2ToggleBadgeMaxDim('${fieldId}','width','100%',this)">
                        <i class="fas fa-arrows-alt-h"></i> Max Rộng
                    </button>
                    <button type="button" id="v2fp-maxh-${fieldId}"
                        class="btn btn-sm ${styleObj.height==='100%'?'btn-primary active':'btn-outline-secondary'} flex-fill"
                        onclick="_v2ToggleBadgeMaxDim('${fieldId}','height','100%',this)">
                        <i class="fas fa-arrows-alt-v"></i> Max Cao
                    </button>
                </div>
                <div class="btn-group w-100" role="group">
                    ${['left','center','right'].map(a => `
                    <button type="button"
                        class="btn btn-sm ${styleObj.badgeAlign===a?'btn-primary active':'btn-outline-secondary'}"
                        onclick="_v2SetBadgeAlign('${fieldId}','${a}',this)">
                        <i class="fas fa-align-${a}"></i> ${a==='left'?'Trái':a==='center'?'Giữa':'Phải'}
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
                <div id="v2fp-scalebrand-${fieldId}" class="${cfg.scaleEnabled?'':'d-none'} p-2 bg-white rounded border border-danger border-opacity-25">
                    <label class="v2-prop-sublabel">Hãng cân mặc định</label>
                    <select class="form-select form-select-sm" onchange="syncFieldConfigV2('${fieldId}','scalePreset',this.value)">
                        <option value="and"      ${(cfg.scalePreset||'and')==='and'      ?'selected':''}>⚖️ A&D (AND)</option>
                        <option value="mettler"  ${cfg.scalePreset==='mettler'           ?'selected':''}>🏋️ Mettler Toledo</option>
                        <option value="sartorius" ${cfg.scalePreset==='sartorius'        ?'selected':''}>🔬 Sartorius</option>
                        <option value="custom"   ${cfg.scalePreset==='custom'            ?'selected':''}>⚙️ Tùy chỉnh</option>
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
                <div class="d-flex gap-2 mb-2">
                    <button class="btn btn-sm btn-outline-primary flex-fill"
                        onclick="copyVariableV2('${fieldId}')"><i class="fas fa-copy me-1"></i>Sao chép</button>
                    <button class="btn btn-sm btn-outline-warning flex-fill"
                        onclick="cutVariableV2('${fieldId}')"><i class="fas fa-cut me-1"></i>Cắt</button>
                </div>
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
    } else if (type === 'select') {
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
window.copyVariableV2 = copyVariableV2;
window.cutVariableV2 = cutVariableV2;
window.deleteVariableV2 = deleteVariableV2;
window.openFieldPanel = openFieldPanel;
window.batchSyncFieldConfigV2 = batchSyncFieldConfigV2;
window.batchDeleteFieldsV2 = batchDeleteFieldsV2;
window.openBatchFieldPanelV2 = openBatchFieldPanelV2;

function markDirty() {
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

window._v2RepaintBadge = function(fieldId) {
    document.querySelectorAll(`.v2-field-badge[data-field-id="${fieldId}"]`).forEach(el => {
        const cfg = fieldsConfig[fieldId] || {};
        const t = FIELD_TYPES[cfg.type] || FIELD_TYPES.text;
        el.querySelector('span') && (el.querySelector('span').textContent = cfg.label || cfg.name || fieldId);
    });
};

window._v2ApplyBadgeStyle = function(fieldId, prop, value) {
    document.querySelectorAll(`.v2-field-badge[data-field-id="${fieldId}"]`).forEach(el => {
        if (value) el.style.setProperty(prop, value, 'important');
        else el.style.removeProperty(prop);
    });
};

window._v2ToggleBadgeMaxDim = function(fieldId, dim, val, btn) {
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

window._v2SetBadgeAlign = function(fieldId, align, btn) {
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

function saveTemplate() {
    unmountEditor(); // chốt nội dung đang gõ dở

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
        locked: i.locked || false, template_id: i.template_id || null,
        showPreview: i.showPreview || false, stage_code: i.stage_code || null,
        chartConfig: i.chartConfig || null, backgroundColor: i.backgroundColor || null,
        section_id: i.section_id || null,
        isBmrHeader: i.isBmrHeader || false, isGfHeader: i.isGfHeader || false,
        isAbbreviationTable: i.isAbbreviationTable || false,
        loop_group_id: i.loop_group_id || null, loop_count: i.loop_count || null,
        typography: i.typography || null,
        cell_notes: i.cell_notes || null, conditional_logic: i.conditional_logic || null,
        textAlign: i.textAlign || null, verticalAlign: i.verticalAlign || null,
        pageBreakBefore: i.pageBreakBefore || false,
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
                deleted_ids: [],
                incremental: true,
            },
            log_history: false,
            section_id: '',
            lang: 'vi',
            _token: BOOT.csrf,
        }),
    })
        .then((r) => r.json())
        .then((res) => {
            if (res.success) {
                if (res.block_ids) {
                    Object.keys(res.block_ids).forEach((fId) => {
                        const it = items.find((i) => i.id === fId);
                        if (it) {
                            const info = res.block_ids[fId];
                            if (info.db_id) it.db_id = info.db_id;
                            if (info.content_db_id) it.content_db_id = info.content_db_id;
                            if (info.data) it.data = info.data;
                        }
                    });
                }
                items.forEach((i) => (i.dirty = false));
                markSaved();
            } else {
                window.Swal?.fire('Thất bại', res.message || 'Không thể lưu hồ sơ', 'error');
            }
        })
        .catch((err) => window.Swal?.fire('Lỗi kết nối', err.message, 'error'))
        .finally(() => {
            if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-save me-1"></i> Lưu'; }
        });
}

/* =========================================================
 * 5b. MỤC LỤC (TOC) — danh sách section, click cuộn tới nơi
 * ========================================================= */
function buildToc() {
    const body = document.getElementById('v2-toc-body');
    if (!body) return;
    let html = '';
    let pageNo = 0;
    items.forEach((item) => {
        if (item.type !== 'section') return;
        pageNo++;
        html += `<div class="v2-toc-item" data-target="v2-sec-${item.id}">
            <span><i class="fas fa-layer-group me-2" style="opacity:0.5;"></i>${esc(item.label || 'Section')}</span>
            <span class="v2-toc-page">Tr.${pageNo}</span>
        </div>`;
    });
    body.innerHTML = html || '<div class="text-muted small text-center py-3">Chưa có công đoạn nào.</div>';
    body.querySelectorAll('.v2-toc-item').forEach((el) => {
        el.addEventListener('click', () => {
            const target = document.getElementById(el.getAttribute('data-target'));
            if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });
}

/* =========================================================
 * 5c. BÌNH LUẬN (kiểu Google Docs) — dùng API comment sẵn có của V1
 * ========================================================= */
let commentsFocusBlockId = null; // block đang được bình luận (null = bình luận chung)

function getBlockComments(blockId) {
    return (BOOT.comments || []).filter((c) => c.selection_id === blockId);
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

function blockLabelOf(blockId) {
    const it = items.find((i) => i.id === blockId);
    if (!it) return blockId;
    return `${it.label || it.type}`;
}

function renderCommentsList() {
    const list = document.getElementById('v2-comments-list');
    if (!list) return;
    const all = BOOT.comments || [];
    const shown = commentsFocusBlockId ? all.filter((c) => c.selection_id === commentsFocusBlockId) : all;

    const target = document.getElementById('v2-comment-target');
    if (target) {
        target.innerHTML = commentsFocusBlockId
            ? `Bình luận cho khối: <b>${esc(blockLabelOf(commentsFocusBlockId))}</b>
               <a href="javascript:void(0)" id="v2-cmt-showall" class="ms-2">(xem tất cả)</a>`
            : 'Bình luận chung cho hồ sơ';
        const showAll = target.querySelector('#v2-cmt-showall');
        if (showAll) showAll.addEventListener('click', () => { commentsFocusBlockId = null; renderCommentsList(); });
    }

    if (shown.length === 0) {
        list.innerHTML = '<div class="text-muted small text-center py-4"><i class="far fa-comments fa-2x mb-2 d-block opacity-50"></i>Chưa có bình luận nào.</div>';
        return;
    }

    list.innerHTML = shown.map((c) => {
        const d = parseCommentContent(c.content);
        const replies = d.replies.map((r) => `
            <div class="v2-comment-reply">
                <span class="v2-comment-author">${esc(r.user_name)}</span>
                <span class="v2-comment-time ms-1">${esc(r.created_at || '')}</span>
                <div class="v2-comment-text">${esc(r.content)}</div>
            </div>`).join('');
        const blockRef = c.selection_id
            ? `<span class="v2-comment-block-ref" data-goto="${esc(c.selection_id)}"><i class="fas fa-cube me-1"></i>${esc(blockLabelOf(c.selection_id))}</span>` : '';
        return `
            <div class="v2-comment-card" data-cid="${c.id}">
                ${blockRef}
                <div class="d-flex justify-content-between">
                    <span class="v2-comment-author"><i class="fas fa-user-circle me-1"></i>${esc(c.user_name || '?')}</span>
                    <span class="v2-comment-time">${esc(String(c.created_at || '').slice(0, 16))}</span>
                </div>
                <div class="v2-comment-text">${esc(d.text)}</div>
                ${replies}
                <div class="v2-comment-actions">
                    <a data-reply="${c.id}"><i class="fas fa-reply me-1"></i>Trả lời</a>
                    <a data-del="${c.id}" class="text-danger"><i class="fas fa-trash me-1"></i>Xóa</a>
                </div>
            </div>`;
    }).join('');

    // Gắn hành vi: nhảy tới block / trả lời / xóa
    list.querySelectorAll('[data-goto]').forEach((el) => el.addEventListener('click', () => {
        const blk = document.querySelector(`.v2-block[data-id="${el.getAttribute('data-goto')}"]`);
        if (blk) blk.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }));
    list.querySelectorAll('[data-reply]').forEach((el) => el.addEventListener('click', async () => {
        const id = el.getAttribute('data-reply');
        const { value } = await window.Swal.fire({
            title: 'Trả lời bình luận', input: 'textarea', inputPlaceholder: 'Nội dung trả lời...',
            showCancelButton: true, confirmButtonText: 'Gửi', cancelButtonText: 'Hủy',
        });
        if (!value) return;
        const res = await fetch(BOOT.commentUrls.reply, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
            body: JSON.stringify({ id: parseInt(id, 10), content: value, _token: BOOT.csrf }),
        }).then((r) => r.json());
        if (res.success) {
            const c = (BOOT.comments || []).find((x) => String(x.id) === String(id));
            if (c) {
                const d = parseCommentContent(c.content);
                d.replies.push(res.reply);
                c.content = JSON.stringify({ text: d.text, replies: d.replies });
            }
            renderCommentsList();
        } else window.Swal.fire('Lỗi', res.message || 'Không thể trả lời', 'error');
    }));
    list.querySelectorAll('[data-del]').forEach((el) => el.addEventListener('click', async () => {
        const id = el.getAttribute('data-del');
        const ok = await window.Swal.fire({
            title: 'Xóa bình luận?', icon: 'warning', showCancelButton: true,
            confirmButtonText: 'Xóa', cancelButtonText: 'Hủy',
        });
        if (!ok.isConfirmed) return;
        const res = await fetch(BOOT.commentUrls.remove, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
            body: JSON.stringify({ id: parseInt(id, 10), _token: BOOT.csrf }),
        }).then((r) => r.json()).catch(() => ({ success: false }));
        if (res.success) {
            BOOT.comments = (BOOT.comments || []).filter((x) => String(x.id) !== String(id));
            renderCommentsList();
            renderDocument(); // cập nhật số đếm trên nút comment của block
        } else window.Swal.fire('Không thể xóa', res.message || 'Bạn không có quyền xóa bình luận này.', 'error');
    }));
}

function openCommentsPanel(blockId) {
    commentsFocusBlockId = blockId || null;
    document.getElementById('v2-comments')?.classList.add('open');
    renderCommentsList();
    document.getElementById('v2-comment-input')?.focus();
}

async function sendComment() {
    const input = document.getElementById('v2-comment-input');
    const content = (input?.value || '').trim();
    if (!content) return;
    const res = await fetch(BOOT.commentUrls.store, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        body: JSON.stringify({
            template_id: BOOT.templateId,
            content,
            selection_id: commentsFocusBlockId,
            _token: BOOT.csrf,
        }),
    }).then((r) => r.json()).catch(() => ({ success: false }));
    if (res.success) {
        BOOT.comments = BOOT.comments || [];
        BOOT.comments.push(res.comment);
        input.value = '';
        renderCommentsList();
        renderDocument(); // cập nhật số đếm trên nút comment của block
    } else {
        window.Swal?.fire('Lỗi', 'Không thể gửi bình luận', 'error');
    }
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
    window.Swal.fire({ toast: true, position: 'top', icon: 'success', showConfirmButton: false, timer: 2200,
        title: `Đã chèn ${newBlocks.length} khối từ "${templateName}"` });
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

    // Thêm/Xóa class CSS cho mode
    const contentWrapper = document.querySelector('.content-wrapper');
    if (contentWrapper) {
        if (isExec) contentWrapper.classList.add('execution-mode-active');
        else contentWrapper.classList.remove('execution-mode-active');
    }

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

window.__V2__.openExecutionModal = function(fieldId, paintCallback) {
    if (!BOOT.isExecutionMode) return;
    const field = fieldsConfig[fieldId];
    if (!field) return;

    let currentVal = BOOT.executionValues[fieldId] || '';
    if (currentVal && typeof currentVal === 'object' && currentVal.hasOwnProperty('default')) {
        currentVal = currentVal.default;
    }

    let inputType = 'text';
    let inputAttrs = {};
    if (field.type === 'number') {
        inputType = 'number';
        if (field.validation && !field.validation.allow_out_of_bounds) {
            if (field.validation.min !== null) inputAttrs.min = field.validation.min;
            if (field.validation.max !== null) inputAttrs.max = field.validation.max;
        }
    } else if (field.type === 'date') {
        inputType = 'text';
        inputAttrs.type = field.date_format === 'hh:mm dd/mm/yyyy' ? 'datetime-local' : 'date';
    } else if (field.type === 'text' && field.barcodeScanEnabled) {
        inputAttrs.placeholder = 'Nhập mã Barcode thủ công...';
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
        cancelButtonText: 'Hủy'
    }).then(result => {
        if (result.isConfirmed) {
            BOOT.executionValues[fieldId] = { 'default': result.value };
            // Simulate saving user metadata
            if (!BOOT.executionValues[fieldId]._meta) BOOT.executionValues[fieldId]._meta = {};
            if (!BOOT.executionValues[fieldId]._meta['default']) BOOT.executionValues[fieldId]._meta['default'] = {};
            BOOT.executionValues[fieldId]._meta['default'].by = BOOT.currentUserName || 'Người dùng thử';
            const now = new Date();
            BOOT.executionValues[fieldId]._meta['default'].at = now.toLocaleDateString('vi-VN') + ' ' + now.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
            
            if (paintCallback) paintCallback();
            
            // Xử lý barcode update liên kết nếu cần thiết (optional fallback)
            if (field.type === 'text' && field.barcodeScanEnabled && typeof fetchMmsDataAndShowModal === 'function') {
                fetchMmsDataAndShowModal(result.value, fieldId);
            }
        }
    });
};

/* =========================================================
 * 7. KHỞI ĐỘNG + gắn sự kiện toolbar
 * ========================================================= */
document.addEventListener('DOMContentLoaded', () => {
    selection.init(); // gắn state machine chuột/bàn phím cho chức năng CHỌN
    renderDocument();
    initFormatControls();
    initFixedToolbar();
    refreshToolbarState();

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

    const btnToggleMode = document.getElementById('v2-btn-toggle-mode');
    if (btnToggleMode) btnToggleMode.addEventListener('click', toggleExecutionModeV2);

    // Mục lục
    document.getElementById('v2-btn-toc')?.addEventListener('click', () => {
        const toc = document.getElementById('v2-toc');
        if (toc.classList.contains('open')) toc.classList.remove('open');
        else { buildToc(); toc.classList.add('open'); }
    });
    document.getElementById('v2-toc-close')?.addEventListener('click', () =>
        document.getElementById('v2-toc').classList.remove('open'));

    // Bình luận
    document.getElementById('v2-btn-comments')?.addEventListener('click', () => {
        const panel = document.getElementById('v2-comments');
        if (panel.classList.contains('open')) panel.classList.remove('open');
        else openCommentsPanel(null);
    });
    document.getElementById('v2-comments-close')?.addEventListener('click', () =>
        document.getElementById('v2-comments').classList.remove('open'));
    document.getElementById('v2-comment-send')?.addEventListener('click', sendComment);

    // Sidebar Thiết bị / Thành phần CO (chỉ 1 panel trái mở tại 1 thời điểm)
    const leftPanels = ['v2-toc', 'v2-equipment', 'v2-components'];
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
    document.querySelectorAll('[data-close-panel]').forEach((btn) =>
        btn.addEventListener('click', () => document.getElementById(btn.getAttribute('data-close-panel')).classList.remove('open')));
    document.getElementById('v2-eq-dept')?.addEventListener('change', loadEquipmentList);
    document.getElementById('v2-eq-search')?.addEventListener('keyup', renderEquipmentList);
    document.getElementById('v2-co-search')?.addEventListener('keyup', renderComponentsList);

    // Dọn trạng thái kéo-thả khi kết thúc drag (kể cả hủy giữa chừng)
    document.addEventListener('dragend', () => {
        document.body.classList.remove('v2-dragging');
        document.querySelectorAll('.v2-inserter.v2-drop-active').forEach((el) => el.classList.remove('v2-drop-active'));
    });

    // Ctrl+K chèn liên kết (giống Google Docs)
    document.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k' && activeEditor) {
            e.preventDefault();
            promptLink();
        }
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

    // Ctrl+S
    document.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key === 's') { e.preventDefault(); saveTemplate(); }
    });

    // Dán (Ctrl+V) khi CHƯA mở editor nào -> tạo block mới như V1 (handleGlobalPaste):
    // bảng thành block bảng, phần còn lại thành block văn bản, thêm vào cuối tài liệu.
    document.addEventListener('paste', (e) => {
        if (BOOT.isReadOnly || BOOT.isExecutionMode) return;
        if (activeEditor) return; // đang có editor mở: TipTap tự xử lý (handleEditorPaste)
        if (e.target.closest && (e.target.closest('input') || e.target.closest('textarea'))) return;

        const cb = e.clipboardData || window.clipboardData;
        const htmlData = cb ? cb.getData('text/html') : '';
        const plainText = cb ? cb.getData('text/plain') : '';
        if (!htmlData && !plainText) return;
        e.preventDefault();

        const lastItem = items.length ? items[items.length - 1] : null;
        const sectionId = lastItem ? (lastItem.type === 'section' ? (lastItem.section_id || lastItem.id) : lastItem.section_id) : null;
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
            items.push(...newBlocks);
            markDirty();
            renderDocument();
            const el = document.querySelector(`.v2-block[data-id="${newBlocks[0].id}"]`);
            if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });
});
