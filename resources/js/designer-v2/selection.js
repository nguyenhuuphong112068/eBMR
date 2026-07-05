/**
 * Selection — chọn đối tượng kiểu Word/Excel cho Designer V2.
 * =============================================================
 * Port từ hệ thống EbmrSelection + initTableAdvancedFeatures của V1
 * (scripts/selection.blade.php + events.blade.php) sang mô hình V2:
 *
 *  - State sống trong JS (Map key "{blockId}::{r}::{c}"), KHÔNG dựa vào DOM
 *    class vì renderDocument() wipe innerHTML — sau mỗi render gọi
 *    onAfterRender() để re-resolve key -> td và gắn lại class.
 *  - Coexist với editor-on-demand: click GIỮA ô vẫn mount TipTap như cũ;
 *    click MÉP ô (mũi tên đen) / Ctrl+Click / Shift+Click / kéo ô-sang-ô
 *    là thao tác CHỌN — chặn mount bằng click suppressor ở capture phase.
 *  - Ctrl+Alt+kéo = marquee quét biến số (badge) -> panel sửa hàng loạt.
 *
 * Bảng thao tác (theo spec):
 *  1. Click nút ⊕ góc bảng            -> chọn cả bảng
 *  2. Click mép trái/trên ô           -> chọn 1 ô (giữa ô = mở editor)
 *  3. Ctrl+Click                      -> chọn/bỏ nhiều ô rời rạc
 *  4. Shift+Click                     -> chọn dải chữ nhật từ anchor
 *  5. D-Click gutter trái hàng        -> chọn cả hàng
 *  6. D-Click gutter trên cột         -> chọn cả cột
 *  7. Kéo từ ô sang ô                 -> chọn khối chữ nhật
 *  8. Click vị trí bất kỳ             -> đặt con trỏ (editor, giữ nguyên)
 *  9. Kéo bôi đen chữ trong editor    -> native selection (giữ nguyên)
 * 10. Click 1 biến số                 -> chọn biến (viền) + mở panel
 * 11. Ctrl+Alt+kéo                    -> quét nhiều biến -> panel hàng loạt
 */

const DRAG_THRESHOLD_PX = 4;
const EDGE_PX = 10; // V1 dùng 15; V2 padding ô sát hơn

// Thuộc tính ô được sao chép khi Copy (giữ nội dung + định dạng, KHÔNG đụng rs/cs/hidden của ô đích)
const COPY_PROPS = ['content', 'backgroundColor', 'textAlign', 'fontWeight', 'fontStyle'];

export function createSelectionController(ctx) {
    // ── State ────────────────────────────────────────────────
    const cells = new Map();   // key "{blockId}::{r}::{c}" -> { item, r, c }
    let anchorKey = null;      // ô neo cho Shift+Click / kéo dải
    let fieldIds = new Set();  // biến số đang chọn
    let mode = null;           // 'cells' | 'row' | 'col' | 'table' | 'fields'

    let _drag = null;          // {kind:'cell-drag'|'badge-marquee', startX, startY, committed, startTd?, marqueeEl?}
    let _suppressClick = false;
    let _clipboard = null;     // { rows, cols, count, grid: [[cellCopy|null]] } — Copy vùng ô

    const blocked = () => ctx.BOOT.isReadOnly || ctx.BOOT.isExecutionMode;

    function toast(title, icon = 'success') {
        window.Swal?.fire({ toast: true, position: 'top-end', icon, title, showConfirmButton: false, timer: 1800 });
    }

    // ── Key helpers ──────────────────────────────────────────
    const keyOf = (blockId, r, c) => `${blockId}::${r}::${c}`;

    /** Đọc {item, r, c} từ 1 td đã có data-row/data-col */
    function tdInfo(td) {
        const wrap = td.closest('.v2-table-wrap');
        if (!wrap) return null;
        const blockId = wrap.getAttribute('data-id');
        const item = ctx.getItems().find((i) => i.id === blockId);
        if (!item) return null;
        const r = parseInt(td.dataset.row, 10);
        const c = parseInt(td.dataset.col, 10);
        if (isNaN(r) || isNaN(c)) return null;
        return { item, r, c, blockId };
    }

    /** Tìm td từ key (sau khi render lại) */
    function findTd(key) {
        const [blockId, r, c] = key.split('::');
        return document.querySelector(
            `.v2-table-wrap[data-id="${blockId}"] td[data-row="${r}"][data-col="${c}"]`);
    }

    function paintCells() {
        document.querySelectorAll('.v2-table td.v2-cell-selected')
            .forEach((el) => el.classList.remove('v2-cell-selected'));
        document.querySelectorAll('.v2-table-wrap.v2-table-selected')
            .forEach((el) => el.classList.remove('v2-table-selected'));
        const wraps = new Set();
        cells.forEach((info, key) => {
            const td = findTd(key);
            if (td) { td.classList.add('v2-cell-selected'); wraps.add(td.closest('.v2-table-wrap')); }
        });
        if (mode === 'table') wraps.forEach((w) => w && w.classList.add('v2-table-selected'));
        ctx.refreshToolbarState();
    }

    function paintFields() {
        document.querySelectorAll('#v2-pages .v2-field-badge.v2-field-selected, #v2-pages .ebmr-field-badge.v2-field-selected')
            .forEach((el) => el.classList.remove('v2-field-selected'));
        fieldIds.forEach((fid) => {
            document.querySelectorAll(`#v2-pages [data-field-id="${fid}"]`)
                .forEach((el) => el.classList.add('v2-field-selected'));
        });
    }

    // ── Mutators ─────────────────────────────────────────────
    function setCells(tds, newMode = 'cells') {
        cells.clear();
        tds.forEach((td) => {
            const info = tdInfo(td);
            if (info) cells.set(keyOf(info.blockId, info.r, info.c), info);
        });
        mode = cells.size ? newMode : null;
        if (tds.length && !anchorKey) {
            const info = tdInfo(tds[0]);
            if (info) anchorKey = keyOf(info.blockId, info.r, info.c);
        }
        paintCells();
    }

    function toggleCell(td) {
        const info = tdInfo(td);
        if (!info) return;
        const key = keyOf(info.blockId, info.r, info.c);
        if (cells.has(key)) cells.delete(key);
        else { cells.set(key, info); anchorKey = key; }
        mode = cells.size ? 'cells' : null;
        paintCells();
    }

    /** Chọn chữ nhật giữa 2 td cùng bảng (thuật toán cellSetRange V1, không offset header) */
    function setRange(startTd, endTd) {
        const a = tdInfo(startTd), b = tdInfo(endTd);
        if (!a || !b || a.blockId !== b.blockId) return;
        const minR = Math.min(a.r, b.r), maxR = Math.max(a.r, b.r);
        const minC = Math.min(a.c, b.c), maxC = Math.max(a.c, b.c);
        cells.clear();
        const item = a.item;
        for (let r = minR; r <= maxR; r++) {
            for (let c = minC; c <= maxC; c++) {
                const cell = item.data?.[r]?.[c];
                if (!cell || cell.hidden) continue;
                cells.set(keyOf(a.blockId, r, c), { item, r, c, blockId: a.blockId });
            }
        }
        anchorKey = keyOf(a.blockId, a.r, a.c);
        mode = 'cells';
        paintCells();
    }

    function selectRow(item, blockId, rowIdx) {
        cells.clear();
        for (let c = 0; c < (item.cols || 0); c++) {
            const cell = item.data?.[rowIdx]?.[c];
            if (!cell || cell.hidden) continue;
            cells.set(keyOf(blockId, rowIdx, c), { item, r: rowIdx, c, blockId });
        }
        anchorKey = cells.size ? cells.keys().next().value : null;
        mode = 'row';
        paintCells();
    }

    function selectCol(item, blockId, colIdx) {
        cells.clear();
        for (let r = 0; r < (item.rows || 0); r++) {
            const cell = item.data?.[r]?.[colIdx];
            if (!cell || cell.hidden) continue;
            cells.set(keyOf(blockId, r, colIdx), { item, r, c: colIdx, blockId });
        }
        anchorKey = cells.size ? cells.keys().next().value : null;
        mode = 'col';
        paintCells();
    }

    function selectTable(item, blockId) {
        cells.clear();
        for (let r = 0; r < (item.rows || 0); r++) {
            for (let c = 0; c < (item.cols || 0); c++) {
                const cell = item.data?.[r]?.[c];
                if (!cell || cell.hidden) continue;
                cells.set(keyOf(blockId, r, c), { item, r, c, blockId });
            }
        }
        anchorKey = cells.size ? cells.keys().next().value : null;
        mode = 'table';
        paintCells();
    }

    function clearCells() {
        if (!cells.size && mode !== 'table') return;
        cells.clear();
        anchorKey = null;
        if (mode !== 'fields') mode = null;
        paintCells();
    }

    function clearFields() {
        if (!fieldIds.size) return;
        fieldIds = new Set();
        if (mode === 'fields') mode = null;
        paintFields();
    }

    function clearAll() { clearCells(); clearFields(); }

    // ── Public queries ───────────────────────────────────────
    const hasCells = () => cells.size > 0;
    const cellCount = () => cells.size;

    /** Trả về [{item, r, c, cell}] resolve trực tiếp từ items (live) */
    function getCells() {
        const out = [];
        cells.forEach(({ item, r, c }) => {
            const cell = item.data?.[r]?.[c];
            if (cell && !cell.hidden) out.push({ item, r, c, cell });
        });
        return out;
    }

    function getAnchorCell() {
        if (!anchorKey || !cells.has(anchorKey)) {
            const first = getCells()[0];
            return first || null;
        }
        const { item, r, c } = cells.get(anchorKey);
        const cell = item.data?.[r]?.[c];
        return cell ? { item, r, c, cell } : null;
    }

    /** saveDocState -> mutate từng ô -> dirty -> render (selection tự re-apply) */
    function applyToCells(mutator) {
        const targets = getCells();
        if (!targets.length) return;
        ctx.saveDocState();
        const touched = new Set();
        targets.forEach(({ item, r, c, cell }) => { mutator(cell, item, r, c); touched.add(item); });
        touched.forEach((item) => { item.dirty = true; });
        ctx.markDirty();
        ctx.renderDocument(); // renderDocument gọi onAfterRender() -> selection giữ nguyên
    }

    // ── Copy / Paste vùng ô (kiểu Excel) ─────────────────────
    /** Chỉ lấy nội dung + định dạng của 1 ô (không rs/cs/hidden) */
    function pickCell(cell) {
        const o = {};
        COPY_PROPS.forEach((p) => { if (cell[p] !== undefined && cell[p] !== '') o[p] = cell[p]; });
        return o;
    }

    /** Ctrl+C: chụp vùng chữ nhật bao các ô đang chọn (theo bảng của ô anchor) */
    function copyCells() {
        const anchor = getAnchorCell();
        if (!anchor) return false;
        const item = anchor.item;
        const mine = getCells().filter((x) => x.item === item);
        if (!mine.length) return false;
        let minR = Infinity, maxR = -Infinity, minC = Infinity, maxC = -Infinity;
        mine.forEach(({ r, c }) => {
            minR = Math.min(minR, r); maxR = Math.max(maxR, r);
            minC = Math.min(minC, c); maxC = Math.max(maxC, c);
        });
        const grid = [];
        for (let r = minR; r <= maxR; r++) {
            const row = [];
            for (let c = minC; c <= maxC; c++) {
                const cell = item.data?.[r]?.[c];
                row.push(cell && !cell.hidden ? pickCell(cell) : null);
            }
            grid.push(row);
        }
        _clipboard = { rows: maxR - minR + 1, cols: maxC - minC + 1, count: mine.length, grid };
        return true;
    }

    /** Ghi nội dung/định dạng của clip vào ô đích (giữ nguyên rs/cs/hidden của đích) */
    function writeCell(item, r, c, clip) {
        const target = item.data?.[r]?.[c];
        if (!target || typeof target !== 'object' || target.hidden) return false;
        COPY_PROPS.forEach((p) => {
            if (p === 'content') target.content = clip && clip.content ? ctx.duplicateFieldsInHtml(clip.content) : '';
            else target[p] = (clip && clip[p]) || '';
        });
        return true;
    }

    /** Ctrl+V: dán clipboard bắt đầu từ ô anchor (1 ô copy + nhiều ô chọn = tô đều) */
    function pasteCells() {
        if (!_clipboard) return false;
        const anchor = getAnchorCell();
        if (!anchor) return false;
        const item = anchor.item;
        ctx.saveDocState();
        const single = _clipboard.rows === 1 && _clipboard.cols === 1;
        const mine = getCells().filter((x) => x.item === item);
        let changed = false;
        if (single && mine.length > 1) {
            const clip = _clipboard.grid[0][0];
            mine.forEach(({ r, c }) => { if (writeCell(item, r, c, clip)) changed = true; });
        } else {
            _clipboard.grid.forEach((row, rOff) => row.forEach((clip, cOff) => {
                if (clip && writeCell(item, anchor.r + rOff, anchor.c + cOff, clip)) changed = true;
            }));
        }
        if (!changed) return false;
        item.dirty = true;
        ctx.markDirty();
        ctx.renderDocument(); // selection tự re-apply qua onAfterRender
        return true;
    }

    function setFields(ids) {
        fieldIds = new Set(ids || []);
        mode = fieldIds.size ? 'fields' : mode;
        paintFields();
    }

    const getFieldIds = () => Array.from(fieldIds);

    /** Sau mỗi renderDocument: re-apply class, drop key không còn resolve được */
    function onAfterRender() {
        Array.from(cells.keys()).forEach((key) => {
            const { item, r, c } = cells.get(key);
            const cell = item.data?.[r]?.[c];
            if (!cell || cell.hidden || !ctx.getItems().includes(item)) cells.delete(key);
        });
        if (anchorKey && !cells.has(anchorKey)) anchorKey = cells.size ? cells.keys().next().value : null;
        if (!cells.size && mode !== 'fields') mode = null;
        paintCells();
        paintFields();
    }

    // ── decorateTable: nút ⊕ + gutter hàng/cột (gọi từ renderTable) ──
    function decorateTable(item, wrap, table) {
        if (blocked() || item.locked || item.isVirtual) return;
        const blockId = item.id;

        // Nút ⊕ chọn cả bảng (thao tác 1) — kiểu Word
        const handle = document.createElement('div');
        handle.className = 'v2-table-handle';
        handle.title = 'Chọn cả bảng';
        handle.innerHTML = '<i class="fas fa-arrows-alt"></i>';
        handle.addEventListener('mousedown', (e) => { e.preventDefault(); e.stopPropagation(); });
        handle.addEventListener('click', (e) => {
            e.stopPropagation();
            if (blocked()) return;
            ctx.unmountEditor();
            clearFields();
            selectTable(item, blockId);
        });
        wrap.appendChild(handle);

        // Gutter HÀNG (thao tác 5): dải hẹp bên trái bảng, D-Click chọn cả hàng
        const rowGutter = document.createElement('div');
        rowGutter.className = 'v2-row-gutter';
        rowGutter.title = 'Nhấp đúp để chọn cả hàng';
        const rowFromEvent = (e) => {
            let found = null;
            table.querySelectorAll('tbody tr').forEach((tr) => {
                const rect = tr.getBoundingClientRect();
                if (e.clientY >= rect.top && e.clientY <= rect.bottom) found = tr;
            });
            return found;
        };
        rowGutter.addEventListener('mousedown', (e) => { e.preventDefault(); e.stopPropagation(); });
        rowGutter.addEventListener('click', (e) => {
            e.stopPropagation();
            if (blocked()) return;
            const tr = rowFromEvent(e);
            if (!tr) return;
            ctx.unmountEditor();
            clearFields();
            // Click đơn: chọn ô đầu hàng (đặt anchor) — spec chỉ bắt buộc D-Click
            const firstTd = tr.querySelector('td[data-row]');
            if (firstTd) setCells([firstTd]);
        });
        rowGutter.addEventListener('dblclick', (e) => {
            e.stopPropagation();
            if (blocked()) return;
            const tr = rowFromEvent(e);
            if (!tr) return;
            ctx.unmountEditor();
            clearFields();
            selectRow(item, blockId, parseInt(tr.dataset.rowIdx, 10));
        });
        wrap.appendChild(rowGutter);

        // Gutter CỘT (thao tác 6): dải hẹp phía trên bảng, D-Click chọn cả cột
        const colGutter = document.createElement('div');
        colGutter.className = 'v2-col-gutter';
        colGutter.title = 'Nhấp đúp để chọn cả cột';
        const colFromEvent = (e) => {
            // Đi theo colSpan hàng đầu (giống attachTableResizers)
            const firstRow = table.querySelector('thead tr') || table.querySelector('tbody tr');
            if (!firstRow) return -1;
            let colCursor = 0;
            for (const cell of Array.from(firstRow.children)) {
                const rect = cell.getBoundingClientRect();
                const span = cell.colSpan || 1;
                if (e.clientX >= rect.left && e.clientX <= rect.right) {
                    // Trong ô colspan: chia đều theo tỉ lệ ngang để xác định cột con
                    const ratio = (e.clientX - rect.left) / Math.max(rect.width, 1);
                    return colCursor + Math.min(span - 1, Math.floor(ratio * span));
                }
                colCursor += span;
            }
            return -1;
        };
        colGutter.addEventListener('mousedown', (e) => { e.preventDefault(); e.stopPropagation(); });
        colGutter.addEventListener('dblclick', (e) => {
            e.stopPropagation();
            if (blocked()) return;
            const col = colFromEvent(e);
            if (col < 0) return;
            ctx.unmountEditor();
            clearFields();
            selectCol(item, blockId, col);
        });
        wrap.appendChild(colGutter);
    }

    // ── State machine chuột toàn cục ─────────────────────────
    function onMouseDown(e) {
        if (blocked()) return;
        if (e.button !== 0) return;
        if (e.target.closest('#v2-toolbar') || e.target.closest('#v2-field-panel')
            || e.target.closest('.v2-toc') || e.target.closest('.modal')
            || e.target.closest('.swal2-container')) return;

        // (11) Ctrl+Alt+kéo: marquee quét biến số
        if (e.ctrlKey && e.altKey && e.target.closest('#v2-pages')) {
            e.preventDefault();
            _drag = { kind: 'badge-marquee', startX: e.clientX, startY: e.clientY, committed: false };
            return;
        }

        const td = e.target.closest('td[data-row]');
        const inTable = td && td.closest('.v2-table-wrap') && !td.closest('.v2-locked');

        if (!inTable) {
            // Click nền trang/khối khác: bỏ chọn (trừ khi click chính badge — NodeView tự lo)
            if (!e.target.closest('.v2-field-badge') && !e.target.closest('.ebmr-field-badge')
                && !e.target.closest('.v2-table-handle') && !e.target.closest('.v2-row-gutter')
                && !e.target.closest('.v2-col-gutter')) {
                clearAll();
            }
            return;
        }

        // Resizer có handler riêng — không đụng
        if (e.target.closest('.v2-col-resizer') || e.target.closest('.v2-row-resizer')) return;

        // (8)(9) Trong editor đang mở: caret + bôi đen chữ native
        const host = ctx.getActiveHost();
        if (host && host.contains(e.target)) return;

        // (3) Ctrl+Click: toggle rời rạc
        if (e.ctrlKey || e.metaKey) {
            e.preventDefault();
            ctx.unmountEditor();
            clearFields();
            toggleCell(td);
            _suppressClick = true;
            setTimeout(() => (_suppressClick = false), 150);
            return;
        }

        // (4) Shift+Click: dải chữ nhật từ anchor
        if (e.shiftKey) {
            e.preventDefault();
            ctx.unmountEditor();
            clearFields();
            const anchorTd = anchorKey ? findTd(anchorKey) : null;
            if (anchorTd && anchorTd.closest('.v2-table-wrap') === td.closest('.v2-table-wrap')) {
                setRange(anchorTd, td);
            } else {
                setCells([td]);
            }
            _suppressClick = true;
            setTimeout(() => (_suppressClick = false), 150);
            return;
        }

        // (2) Click mép trái/trên ô = chọn ô; (7) kéo tiếp = mở rộng dải
        const rect = td.getBoundingClientRect();
        const nearEdge = (e.clientX - rect.left) < EDGE_PX || (e.clientY - rect.top) < EDGE_PX;
        if (nearEdge) {
            e.preventDefault(); // chặn caret + native selection
            ctx.unmountEditor();
            clearFields();
            anchorKey = null;
            setCells([td]);
            _drag = { kind: 'cell-drag', startX: e.clientX, startY: e.clientY, committed: false, startTd: td };
            _suppressClick = true;
            setTimeout(() => (_suppressClick = false), 300);
            return;
        }

        // Giữa ô: KHÔNG preventDefault — click thường vẫn mount editor như cũ.
        // Chỉ arm drag: nếu kéo sang ô khác thì thành chọn khối (7).
        _drag = { kind: 'cell-drag', startX: e.clientX, startY: e.clientY, committed: false, startTd: td };
    }

    function onMouseMove(e) {
        // Cursor mũi tên đen khi hover gần mép ô (thao tác 2)
        if (!_drag && !blocked()) {
            const td = e.target.closest && e.target.closest('td[data-row]');
            document.querySelectorAll('td.v2-cur-cellsel').forEach((el) => {
                if (el !== td) el.classList.remove('v2-cur-cellsel');
            });
            if (td && td.closest('.v2-table-wrap') && !td.closest('.v2-locked')) {
                const host = ctx.getActiveHost();
                if (!host || !host.contains(e.target)) {
                    const rect = td.getBoundingClientRect();
                    const nearEdge = (e.clientX - rect.left) < EDGE_PX || (e.clientY - rect.top) < EDGE_PX;
                    td.classList.toggle('v2-cur-cellsel', nearEdge);
                }
            }
        }

        if (!_drag) return;

        if (!_drag.committed) {
            if (Math.hypot(e.clientX - _drag.startX, e.clientY - _drag.startY) < DRAG_THRESHOLD_PX) return;

            if (_drag.kind === 'badge-marquee') {
                _drag.committed = true;
                window.getSelection().removeAllRanges();
                clearAll();
                const m = document.createElement('div');
                m.className = 'v2-marquee';
                m.style.left = _drag.startX + 'px';
                m.style.top = _drag.startY + 'px';
                document.body.appendChild(m);
                _drag.marqueeEl = m;
            } else {
                // cell-drag: chỉ commit khi pointer sang TD KHÁC cùng bảng
                const td = e.target.closest && e.target.closest('td[data-row]');
                if (!td || td === _drag.startTd) return;
                if (td.closest('.v2-table-wrap') !== _drag.startTd.closest('.v2-table-wrap')) return;
                _drag.committed = true;
                ctx.unmountEditor();
                clearFields();
                window.getSelection().removeAllRanges();
                document.body.classList.add('v2-cell-dragging');
            }
        }

        if (_drag.kind === 'badge-marquee') {
            const x = Math.min(_drag.startX, e.clientX);
            const y = Math.min(_drag.startY, e.clientY);
            const w = Math.abs(e.clientX - _drag.startX);
            const h = Math.abs(e.clientY - _drag.startY);
            Object.assign(_drag.marqueeEl.style, { left: x + 'px', top: y + 'px', width: w + 'px', height: h + 'px' });

            const box = { left: x, right: x + w, top: y, bottom: y + h };
            document.querySelectorAll('#v2-pages [data-field-id]').forEach((el) => {
                const r = el.getBoundingClientRect();
                const hit = !(box.right < r.left || box.left > r.right || box.bottom < r.top || box.top > r.bottom);
                el.classList.toggle('v2-field-selected', hit);
            });
            return;
        }

        // cell-drag committed: cập nhật dải liên tục (thao tác 7)
        const td = e.target.closest && e.target.closest('td[data-row]');
        if (td && td.closest('.v2-table-wrap') === _drag.startTd.closest('.v2-table-wrap')) {
            setRange(_drag.startTd, td);
        }
    }

    function onMouseUp(e) {
        if (!_drag) return;
        const drag = _drag;
        _drag = null;
        document.body.classList.remove('v2-cell-dragging');

        if (!drag.committed) return; // click thường: để event click gốc tự xử lý

        _suppressClick = true;
        setTimeout(() => (_suppressClick = false), 150);

        if (drag.kind === 'badge-marquee') {
            if (drag.marqueeEl) drag.marqueeEl.remove();
            const ids = new Set();
            document.querySelectorAll('#v2-pages .v2-field-selected[data-field-id]')
                .forEach((el) => { const fid = el.getAttribute('data-field-id'); if (fid) ids.add(fid); });
            if (ids.size > 0) {
                setFields(Array.from(ids));
                ctx.openBatchFieldPanel(Array.from(ids));
            }
            return;
        }
        ctx.refreshToolbarState();
    }

    /** Capture-phase: nuốt click ngay sau thao tác chọn để KHÔNG mount editor */
    function onClickCapture(e) {
        if (_suppressClick) {
            e.preventDefault();
            e.stopPropagation();
        }
    }

    // ── Bàn phím ─────────────────────────────────────────────
    function onKeyDown(e) {
        if (e.key === 'Escape') {
            if (ctx.hasActiveEditor()) { ctx.unmountEditor(); return; }
            if (cells.size || fieldIds.size) {
                clearAll();
                document.getElementById('v2-field-panel')?.classList.remove('open');
            }
            return;
        }
        if ((e.key === 'Delete' || e.key === 'Backspace') && !blocked()) {
            if (ctx.hasActiveEditor()) return; // TipTap tự xử lý khi đang gõ
            const t = e.target;
            if (t.closest && (t.closest('input') || t.closest('textarea') || t.closest('[contenteditable="true"]'))) return;
            if (!cells.size) return;
            e.preventDefault();
            applyToCells((cell) => { cell.content = ''; });
            return;
        }

        // Ctrl+C / Ctrl+X / Ctrl+V trên vùng ô đã chọn (kiểu Excel)
        if ((e.ctrlKey || e.metaKey) && !e.altKey && !blocked()) {
            const k = e.key.toLowerCase();
            if (k !== 'c' && k !== 'x' && k !== 'v') return;
            // Đang gõ trong editor/ô nhập → để trình duyệt copy/paste chữ như thường
            if (ctx.hasActiveEditor()) return;
            const t = e.target;
            if (t.closest && (t.closest('input') || t.closest('textarea') || t.closest('[contenteditable="true"]'))) return;

            if (k === 'c' || k === 'x') {
                if (!cells.size) return;
                // Người dùng đang bôi đen chữ → nhường copy văn bản native
                if (window.getSelection && String(window.getSelection())) return;
                if (!copyCells()) return;
                e.preventDefault();
                if (k === 'x') applyToCells((cell) => { cell.content = ''; });
                toast(k === 'x' ? `Đã cắt ${_clipboard.count} ô` : `Đã sao chép ${_clipboard.count} ô`, 'info');
                return;
            }
            // k === 'v'
            if (!_clipboard || !cells.size) return; // chưa chọn ô đích → nhường paste native
            e.preventDefault();
            if (pasteCells()) toast('Đã dán nội dung ô');
        }
    }

    function init() {
        document.addEventListener('mousedown', onMouseDown);
        document.addEventListener('mousemove', onMouseMove);
        document.addEventListener('mouseup', onMouseUp);
        document.addEventListener('click', onClickCapture, true);
        document.addEventListener('keydown', onKeyDown);
    }

    return {
        init, decorateTable, onAfterRender,
        clearCells, clearFields, clearAll,
        hasCells, cellCount, getCells, getAnchorCell, applyToCells,
        setFields, getFieldIds,
        setRange, selectTable,
    };
}
