<script>
    // ==========================================================================
    // EbmrSelection — nguồn sự thật duy nhất cho việc "đang chọn gì" trong designer.
    // Tách rõ 3 loại đối tượng độc lập (item = block/section, cell = ô bảng,
    // field = biến số), mỗi loại có set/toggle/clear/get riêng. Các biến toàn cục
    // cũ (selectedId, selectedFieldId, selectedFieldIds, activeRowIdx, activeColIdx,
    // class .selected-cell) vẫn được giữ và đồng bộ tại đây để mọi nơi khác trong
    // codebase đọc trực tiếp các biến đó vẫn luôn thấy giá trị chính xác — nhưng
    // từ nay CHỈ module này được phép ghi vào chúng.
    // Không đặt tên "Selection" vì đó là interface built-in của trình duyệt
    // (kiểu trả về của window.getSelection()) — tránh che khuất global đó.
    // ==========================================================================

    let activeRowIdx = 0; // 0 = hàng tiêu đề, 1+ = hàng dữ liệu. Ô "active" gần nhất.
    let activeColIdx = 0;

    window.EbmrSelection = (function() {

        // ---------------- ITEM (block / section) ----------------
        const _item = { ids: [], anchorId: null, mode: null };

        function itemSyncLegacy() {
            selectedId = _item.ids.length > 0 ? _item.ids[_item.ids.length - 1] : null;
        }

        function itemSet(ids, opts = {}) {
            const arr = Array.isArray(ids) ? ids.filter(Boolean) : (ids ? [ids] : []);
            _item.ids = [...new Set(arr)];
            _item.anchorId = opts.anchorId !== undefined ? opts.anchorId : (_item.ids[0] || null);
            _item.mode = opts.mode || (_item.ids.length > 1 ? 'discrete' : (_item.ids.length === 1 ? 'single' : null));
            itemSyncLegacy();
        }

        function itemToggle(id) {
            if (!id) return;
            const idx = _item.ids.indexOf(id);
            if (idx === -1) {
                _item.ids.push(id);
                if (!_item.anchorId) _item.anchorId = id;
            } else {
                _item.ids.splice(idx, 1);
                if (_item.anchorId === id) _item.anchorId = _item.ids[0] || null;
            }
            _item.mode = _item.ids.length > 1 ? 'discrete' : (_item.ids.length === 1 ? 'single' : null);
            itemSyncLegacy();
        }

        function itemClear() {
            _item.ids = [];
            _item.anchorId = null;
            _item.mode = null;
            itemSyncLegacy();
        }

        // ---------------- CELL (ô bảng — thiết kế) ----------------
        // Đại diện thực tế vẫn là class .selected-cell trên DOM (rất nhiều nơi
        // trong codebase đọc trực tiếp qua document.querySelectorAll('.selected-cell')),
        // nên EbmrSelection chỉ giữ vai trò NGƯỜI GHI DUY NHẤT của class này,
        // đồng thời đồng bộ activeRowIdx/activeColIdx (ô active gần nhất).

        function cellKeyFor(cellEl) {
            const blockItem = cellEl.closest('.block-item');
            const blockId = blockItem ? blockItem.getAttribute('data-id') : '';
            return `${blockId}::${cellEl.dataset.row}::${cellEl.dataset.col}`;
        }

        function cellFindEl(blockId, row, col) {
            return document.querySelector(`.block-item[data-id="${blockId}"] [data-row="${row}"][data-col="${col}"]`);
        }

        function cellResolveEl(keyOrEl) {
            if (typeof keyOrEl === 'string') {
                const [blockId, row, col] = keyOrEl.split('::');
                return cellFindEl(blockId, row, col);
            }
            return keyOrEl;
        }

        function cellClear() {
            document.querySelectorAll('.selected-cell').forEach(c => c.classList.remove('selected-cell'));
        }

        function cellSet(keysOrEls) {
            cellClear();
            let lastRow = null, lastCol = null;
            (keysOrEls || []).forEach(keyOrEl => {
                const el = cellResolveEl(keyOrEl);
                if (!el) return;
                el.classList.add('selected-cell');
                lastRow = parseInt(el.dataset.row);
                lastCol = parseInt(el.dataset.col);
            });
            if (lastRow !== null) {
                activeRowIdx = lastRow;
                activeColIdx = lastCol;
            }
        }

        function cellToggle(keyOrEl) {
            const el = cellResolveEl(keyOrEl);
            if (!el) return;
            if (el.classList.contains('selected-cell')) {
                el.classList.remove('selected-cell');
            } else {
                el.classList.add('selected-cell');
                activeRowIdx = parseInt(el.dataset.row);
                activeColIdx = parseInt(el.dataset.col);
            }
        }

        function cellSetRange(startEl, endEl) {
            cellClear();
            const table = startEl.closest('.mini-table');
            if (!table) return;
            const r1 = parseInt(startEl.dataset.row),
                c1 = parseInt(startEl.dataset.col);
            const r2 = parseInt(endEl.dataset.row),
                c2 = parseInt(endEl.dataset.col);
            const minR = Math.min(r1, r2),
                maxR = Math.max(r1, r2);
            const minC = Math.min(c1, c2),
                maxC = Math.max(c1, c2);
            for (let r = minR; r <= maxR; r++) {
                for (let c = minC; c <= maxC; c++) {
                    const cellEl = table.querySelector(`[data-row="${r}"][data-col="${c}"]`);
                    if (cellEl) cellEl.classList.add('selected-cell');
                }
            }
            activeRowIdx = r2;
            activeColIdx = c2;
        }

        function cellGetActiveEl() {
            if (selectedId === null || selectedId === undefined) return null;
            return cellFindEl(selectedId, activeRowIdx, activeColIdx);
        }

        // Logic dùng chung: quyết định thao tác chèn/xoá/dán biến số nên áp dụng
        // cho MỘT ô đang đặt con trỏ hay BATCH nhiều ô đang được chọn.
        // Thay cho 3 bản gần như y hệt từng bị copy-paste trong variable_ops.blade.php.
        function cellResolveOperationTargets() {
            const cells = document.querySelectorAll('.selected-cell');
            let useBatch = false;
            if (cells.length > 1) {
                useBatch = true;
            } else if (cells.length === 1) {
                let hasCursorInCell = false;
                const selForCheck = (typeof savedTextSelection !== 'undefined' && savedTextSelection) ?
                    [savedTextSelection] :
                    (window.getSelection().rangeCount > 0 ? [window.getSelection().getRangeAt(0)] : []);
                if (selForCheck.length > 0) {
                    let node = selForCheck[0].commonAncestorContainer;
                    if (node.nodeType === 3) node = node.parentNode;
                    if (node.closest && node.closest('td, th') === cells[0]) {
                        hasCursorInCell = true;
                    }
                }
                if (!hasCursorInCell) useBatch = true;
            }
            return { cells, useBatch };
        }

        // ---------------- FIELD (biến số / "field") ----------------
        const _field = { ids: [] };

        function fieldSyncLegacy() {
            selectedFieldIds = [..._field.ids];
            selectedFieldId = _field.ids.length > 0 ? _field.ids[0] : null;
        }

        function fieldSet(ids) {
            const arr = Array.isArray(ids) ? ids.filter(Boolean) : (ids ? [ids] : []);
            _field.ids = [...new Set(arr)];
            fieldSyncLegacy();
        }

        function fieldToggle(id) {
            if (!id) return;
            const idx = _field.ids.indexOf(id);
            if (idx === -1) _field.ids.push(id);
            else _field.ids.splice(idx, 1);
            fieldSyncLegacy();
        }

        function fieldClear() {
            _field.ids = [];
            fieldSyncLegacy();
        }

        // ---------------- EXEC CELL (ô bảng — chế độ Chạy thử / N-A) ----------------
        // Theo dõi bằng {blockId,row,col} thay vì tham chiếu DOM trực tiếp, để không
        // bị "mất" highlight khi renderBlocks() chạy lại (vd sau khi tính lại công thức).
        const _execCell = { keys: [] };

        function execCellNormalize(cellEl) {
            if (!cellEl) return null;
            if (cellEl.hasAttribute && cellEl.hasAttribute('data-row')) return cellEl;
            return cellEl.closest ? (cellEl.closest('[data-row]') || cellEl) : cellEl;
        }

        function execCellKeyFor(cellEl) {
            const blockItem = cellEl.closest('.block-item');
            const blockId = blockItem ? (blockItem.getAttribute('data-block-key') || blockItem.getAttribute('data-id')) : '';
            return `${blockId}::${cellEl.dataset.row}::${cellEl.dataset.col}`;
        }

        function execCellElFor(key) {
            const [blockId, row, col] = key.split('::');
            return document.querySelector(`.block-item[data-block-key="${blockId}"] [data-row="${row}"][data-col="${col}"]`) ||
                document.querySelector(`.block-item[data-id="${blockId}"] [data-row="${row}"][data-col="${col}"]`);
        }

        function execCellIsSelected(cellEl) {
            cellEl = execCellNormalize(cellEl);
            if (!cellEl) return false;
            return _execCell.keys.includes(execCellKeyFor(cellEl));
        }

        function execCellToggle(cellEl) {
            cellEl = execCellNormalize(cellEl);
            if (!cellEl) return;
            const key = execCellKeyFor(cellEl);
            const idx = _execCell.keys.indexOf(key);
            if (idx === -1) {
                _execCell.keys.push(key);
                cellEl.classList.add('cell-selected-execution');
            } else {
                _execCell.keys.splice(idx, 1);
                cellEl.classList.remove('cell-selected-execution');
            }
        }

        function execCellSetSingle(cellEl) {
            cellEl = execCellNormalize(cellEl);
            execCellClear();
            if (!cellEl) return;
            _execCell.keys = [execCellKeyFor(cellEl)];
            cellEl.classList.add('cell-selected-execution');
        }

        function execCellClear() {
            _execCell.keys.forEach(key => {
                const el = execCellElFor(key);
                if (el) el.classList.remove('cell-selected-execution');
            });
            _execCell.keys = [];
        }

        function execCellGetElements() {
            return _execCell.keys.map(execCellElFor).filter(Boolean);
        }

        function execCellReapplyAfterRender() {
            _execCell.keys.forEach(key => {
                const el = execCellElFor(key);
                if (el) el.classList.add('cell-selected-execution');
            });
        }

        // ---------------- Deselect toàn bộ ----------------
        function clearAll() {
            itemClear();
            cellClear();
            fieldClear();
            const body = document.getElementById('prop-body');
            if (body) body.innerHTML = '';
        }

        return {
            item: {
                set: itemSet,
                toggle: itemToggle,
                clear: itemClear,
                getIds: () => [..._item.ids],
                getAnchorId: () => _item.anchorId,
                getMode: () => _item.mode,
                isSelected: (id) => _item.ids.includes(id)
            },
            cell: {
                keyFor: cellKeyFor,
                set: cellSet,
                toggle: cellToggle,
                setRange: cellSetRange,
                clear: cellClear,
                getActiveEl: cellGetActiveEl,
                resolveOperationTargets: cellResolveOperationTargets
            },
            field: {
                set: fieldSet,
                toggle: fieldToggle,
                clear: fieldClear,
                getIds: () => [..._field.ids],
                isSelected: (id) => _field.ids.includes(id)
            },
            execCell: {
                toggle: execCellToggle,
                setSingle: execCellSetSingle,
                clear: execCellClear,
                isSelected: execCellIsSelected,
                getElements: execCellGetElements,
                isEmpty: () => _execCell.keys.length === 0,
                count: () => _execCell.keys.length,
                reapplyAfterRender: execCellReapplyAfterRender
            },
            clearAll: clearAll
        };
    })();
</script>
