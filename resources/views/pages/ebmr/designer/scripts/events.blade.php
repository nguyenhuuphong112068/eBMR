<script>
    function initTableAdvancedFeatures() {
        // 1. Selection Logic
        document.addEventListener('mousedown', (e) => {
            if (window.isExecutionMode) return; // Chặn chọn khối ở chế độ ghi chép

            const cell = e.target.closest('td, th');
            if (cell && cell.closest('.mini-table')) {
                isSelecting = true;
                startCell = cell;
                activeRowIdx = parseInt(cell.dataset.row);
                activeColIdx = parseInt(cell.dataset.col);
                clearSelection();
                cell.classList.add('selected-cell');
                selectItem(selectedId, false);
            } else if (!e.target.closest('#property-panel') && !e.target.closest('.editor-toolbar')) {
                clearSelection();
            }
        });

        document.addEventListener('mouseover', (e) => {
            if (window.isExecutionMode || !isSelecting) return;
            const cell = e.target.closest('td, th');
            if (cell && cell.closest('.mini-table') === startCell.closest('.mini-table')) {
                highlightRange(startCell, cell);
            }
        });

        document.addEventListener('mouseup', () => {
            isSelecting = false;
        });

        // 2. Paste Logic (Smart Paste)
        document.addEventListener('paste', (e) => {
            if (window.isExecutionMode) return; // Không cho phép paste cấu trúc/khối mới
            const cell = e.target.closest('td, th');
            if (cell) {
                saveState();
                handleTablePaste(e);
            } else {
                handleGlobalPaste(e);
            }
        });

        // 3. Shortcuts & Navigation
        document.addEventListener('keydown', (e) => {
            if (window.isExecutionMode) {
                // Chỉ cho phép các phím di chuyển cơ bản, không cho phép phím tắt hệ thống (Ctrl+S, Ctrl+Z...)
                if (e.ctrlKey) {
                    if (['s', 'z', 'y'].includes(e.key.toLowerCase())) e.preventDefault();
                }
                return;
            }

            if (e.key === 'Tab' && e.target.closest('[contenteditable="true"]')) {
                e.preventDefault();
                document.execCommand('insertHTML', false, '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;');
                if (typeof saveStateDebounced === 'function') saveStateDebounced();
                return;
            }
            if (e.ctrlKey && e.key.toLowerCase() === 'z') {
                e.preventDefault(); undo();
            } else if (e.ctrlKey && (e.key.toLowerCase() === 'y' || (e.shiftKey && e.key.toLowerCase() === 'z'))) {
                e.preventDefault(); redo();
            } else if (e.ctrlKey && e.key.toLowerCase() === 's') {
                e.preventDefault();
                if (typeof saveTemplate === 'function') saveTemplate();
            }
            handleTableNavigation(e);
        });
    }

    function handleGlobalPaste(e) {
        if (e.target.closest('[contenteditable="true"]')) return;
        const htmlData = (e.clipboardData || window.clipboardData).getData('text/html');
        const plainText = (e.clipboardData || window.clipboardData).getData('text/plain');
        if (!htmlData && !plainText) return;

        e.preventDefault();
        saveState();

        let insertIndex = items.length;
        if (selectedId) {
            const currentIdx = items.findIndex(i => i.id === selectedId);
            if (currentIdx !== -1) insertIndex = currentIdx + 1;
        }

        if (htmlData) {
            const parser = new DOMParser();
            const doc = parser.parseFromString(htmlData, 'text/html');
            const body = doc.body;
            let pendingContent = "";

            const flushPending = () => {
                if (pendingContent.trim()) {
                    addPastedTextBlock(pendingContent, insertIndex++);
                    pendingContent = "";
                }
            };

            Array.from(body.childNodes).forEach(node => {
                if (node.nodeName === 'TABLE') {
                    flushPending();
                    addPastedTableBlock(node, insertIndex++);
                } else if (node.nodeType === Node.ELEMENT_NODE || (node.nodeType === Node.TEXT_NODE && node.textContent.trim())) {
                    pendingContent += (node.outerHTML || node.textContent);
                }
            });
            flushPending();
        } else {
            addPastedTextBlock(plainText, insertIndex);
        }
        renderBlocks();
    }

    function addPastedTextBlock(html, index) {
        const id = 'blk_' + Date.now() + Math.random().toString(36).substr(2, 5);
        items.splice(index, 0, { id: id, type: 'static-text', label: 'Ghi chú (Pasted)', content: html, columns: [] });
    }

    function addPastedTableBlock(tableEl, index) {
        const rows = Array.from(tableEl.querySelectorAll('tr'));
        if (rows.length === 0) return;
        const rowCount = rows.length;
        let colCount = 0;
        rows.forEach(r => {
            const cells = r.querySelectorAll('td, th');
            if (cells.length > colCount) colCount = cells.length;
        });

        const id = 'blk_' + Date.now() + Math.random().toString(36).substr(2, 5);
        let columns = [];
        let rowHeights = [];
        for (let i = 0; i < colCount; i++) {
            let width = 'auto';
            const firstCell = rows[0].querySelectorAll('td, th')[i];
            if (firstCell) {
                width = firstCell.style.width || firstCell.getAttribute('width') || 'auto';
                if (width !== 'auto' && !width.includes('px') && !width.includes('%')) width += 'px';
            }
            columns.push({ label: 'Cột ' + (i + 1), type: 'text', width: width });
        }

        let data = [];
        rows.forEach((r, rIdx) => {
            let rowData = [];
            let height = r.style.height || r.getAttribute('height') || 'auto';
            if (height !== 'auto' && !height.includes('px')) height += 'px';
            rowHeights.push(height);
            const cells = r.querySelectorAll('td, th');
            for (let c = 0; c < colCount; c++) {
                rowData.push({ content: cells[c] ? cells[c].innerHTML : '', rs: 1, cs: 1, hidden: false });
            }
            data.push(rowData);
        });

        items.splice(index, 0, {
            id: id, type: 'table', label: 'Bảng (Pasted)', rows: rowCount, cols: colCount,
            columns: columns, data: data, rowHeights: rowHeights, borderMode: 'visible', hideHeader: true
        });
    }

    function handleTablePaste(e) {
        const target = e.target.closest('td, th');
        if (!target) return;
        const tableEl = target.closest('.mini-table');
        const blockItem = target.closest('.block-item');
        const item = items.find(i => blockItem && blockItem.contains(tableEl));
        if (!item || item.type !== 'table') return;

        let grid = [];
        const htmlData = (e.clipboardData || window.clipboardData).getData('text/html');
        if (htmlData) {
            const parser = new DOMParser();
            const doc = parser.parseFromString(htmlData, 'text/html');
            const table = doc.querySelector('table');
            if (table) {
                table.querySelectorAll('tr').forEach(row => {
                    const rowData = [];
                    row.querySelectorAll('td, th').forEach(cell => rowData.push(cell.innerHTML));
                    if (rowData.length > 0) grid.push(rowData);
                });
            }
        }
        if (grid.length === 0) {
            const plainText = (e.clipboardData || window.clipboardData).getData('text/plain');
            if (plainText) grid = plainText.trim().split(/\r\n|\n/).map(row => row.split('\t'));
        }
        if (grid.length === 0) return;

        e.preventDefault();
        const startR = parseInt(target.dataset.row); 
        const startC = parseInt(target.dataset.col);
        let dataChanged = false;

        grid.forEach((rowData, rOffset) => {
            const rIndex = startR + rOffset;
            if (rIndex > item.rows) {
                item.rows++;
                item.data.push(new Array(item.cols).fill({content:'', rs:1, cs:1, hidden:false}));
                dataChanged = true;
            }
            rowData.forEach((cellContent, cOffset) => {
                const cIndex = startC + cOffset;
                if (cIndex >= item.cols) {
                    item.cols++;
                    item.columns.push({ label: 'Cột ' + item.cols, type: 'text', width: 'auto' });
                    item.data.forEach(row => row.push({content:'', rs:1, cs:1, hidden:false}));
                    dataChanged = true;
                }
                if (rIndex === 0) item.columns[cIndex].label = cellContent;
                else {
                    if (typeof item.data[rIndex - 1][cIndex] === 'object') item.data[rIndex - 1][cIndex].content = cellContent;
                    else item.data[rIndex - 1][cIndex] = {content: cellContent, rs:1, cs:1, hidden:false};
                }
                dataChanged = true;
            });
        });

        if (dataChanged) {
            renderBlocks();
            setTimeout(() => {
                const newCell = document.querySelector(`[data-row="${startR}"][data-col="${startC}"]`);
                if (newCell) newCell.focus();
            }, 10);
        }
    }

    function handleTableNavigation(e) {
        const active = document.activeElement;
        if (!active || !active.hasAttribute('data-row')) return;
        const r = parseInt(active.dataset.row);
        const c = parseInt(active.dataset.col);
        const table = active.closest('.mini-table');
        let target = null;

        if (e.key === 'ArrowUp') target = table.querySelector(`[data-row="${r - 1}"][data-col="${c}"]`);
        else if (e.key === 'ArrowDown') target = table.querySelector(`[data-row="${r + 1}"][data-col="${c}"]`);
        else if (e.key === 'ArrowLeft') {
            const sel = window.getSelection();
            if (sel.rangeCount > 0 && sel.getRangeAt(0).startOffset === 0) target = table.querySelector(`[data-row="${r}"][data-col="${c - 1}"]`);
        } else if (e.key === 'ArrowRight') {
            const sel = window.getSelection();
            if (sel.rangeCount > 0 && sel.getRangeAt(0).startOffset === active.innerText.length) target = table.querySelector(`[data-row="${r}"][data-col="${c + 1}"]`);
        } else if (e.key === 'Delete') {
            const selectedCells = table.querySelectorAll('.selected-cell');
            if (selectedCells.length > 0) {
                e.preventDefault();
                const blockItem = active.closest('.block-item');
                const item = items.find(i => blockItem && blockItem.contains(table));
                selectedCells.forEach(cell => {
                    const cellR = parseInt(cell.dataset.row);
                    const cellC = parseInt(cell.dataset.col);
                    if (cellR === 0) item.columns[cellC].label = '';
                    else {
                        if (typeof item.data[cellR-1][cellC] === 'object') item.data[cellR-1][cellC].content = '';
                        else item.data[cellR-1][cellC] = {content:'', rs:1, cs:1, hidden:false};
                    }
                });
                renderBlocks();
                return;
            }
        }
        if (target) { e.preventDefault(); target.focus(); }
    }

    // Initialize all event listeners
    document.addEventListener('DOMContentLoaded', () => {
        initGridSelector();
        initTableAdvancedFeatures();
        if (typeof initRuler === 'function') initRuler();
        
        const mainContent = document.getElementById('mainContent');
        if (mainContent) {
            mainContent.onclick = (e) => {
                if (!e.target.closest('.block-item') && !e.target.closest('#property-panel') && !e.target.closest('.editor-toolbar') && !e.target.closest('.insert-divider')) {
                    selectedId = null;
                    document.querySelectorAll('.insert-menu').forEach(m => m.classList.remove('show'));
                    selectItem(null);
                }
            };
        }
        selectItem(null, false);
    });
</script>
