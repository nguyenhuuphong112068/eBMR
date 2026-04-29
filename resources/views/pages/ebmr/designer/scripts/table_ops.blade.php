<script>
    // --- Grid Selector Logic ---
    function initGridSelector() {
        const grid = document.getElementById('table-grid');
        if (!grid) return;
        
        const size = 10;
        grid.innerHTML = ''; // Clear existing

        for (let r = 1; r <= size; r++) {
            for (let c = 1; c <= size; c++) {
                const square = document.createElement('div');
                square.className = 'grid-square';
                square.dataset.row = r;
                square.dataset.col = c;

                square.onmouseover = () => highlightGrid(r, c);
                square.onclick = (e) => {
                    e.stopPropagation();
                    addTable(r, c);
                };
                grid.appendChild(square);
            }
        }
    }

    function highlightGrid(rows, cols) {
        const squares = document.querySelectorAll('.grid-square');
        const label = document.getElementById('grid-selection-label');
        if (label) label.innerText = `${cols} x ${rows} Table`;

        squares.forEach(sq => {
            const r = parseInt(sq.dataset.row);
            const c = parseInt(sq.dataset.col);
            if (r <= rows && c <= cols) {
                sq.classList.add('highlighted');
            } else {
                sq.classList.remove('highlighted');
            }
        });
    }

    // --- Table Creation ---
    function addTable(rows, cols, insertIndex = null) {
        const hint = document.getElementById('drop-hint');
        if (hint) hint.classList.add('d-none');
        
        saveState();
        const id = 'blk_' + Date.now();

        let columns = [];
        for (let i = 1; i <= cols; i++) {
            columns.push({ label: 'Cột ' + i, type: 'text', width: 'auto' });
        }

        let data = [];
        for (let r = 0; r < rows; r++) {
            let rowData = [];
            for (let c = 0; c < cols; c++) rowData.push({ content: '', rs: 1, cs: 1, hidden: false });
            data.push(rowData);
        }

        // Determine section_id for the new item
        let sectionId = window.activeSectionId || null;
        if (!sectionId && insertIndex !== null) {
            if (insertIndex > 0) {
                sectionId = items[insertIndex - 1].section_id;
            } else if (items.length > 0) {
                sectionId = items[0].section_id;
            }
        } else if (!sectionId && items.length > 0) {
            sectionId = items[items.length - 1].section_id;
        }

        const item = {
            id: id,
            type: 'table',
            section_id: sectionId,
            label: 'Bảng ' + cols + 'x' + rows,
            rows: rows,
            cols: cols,
            columns: columns,
            data: data,
            rowHeights: new Array(rows).fill('auto'),
            borderMode: 'visible',
            hideHeader: false
        };

        if (insertIndex !== null) {
            items.splice(insertIndex, 0, item);
        } else if (sectionId) {
            // Find last block of this section and insert after it
            let lastIdx = -1;
            for (let i = items.length - 1; i >= 0; i--) {
                if (items[i].section_id === sectionId || items[i].id === sectionId) {
                    lastIdx = i;
                    break;
                }
            }
            if (lastIdx !== -1) items.splice(lastIdx + 1, 0, item);
            else items.push(item);
        } else {
            items.push(item);
        }

        renderBlocks();
        selectItem(id);
    }

    window.executeAddTableRow = function(itemId) {
        saveState();
        const item = items.find(i => i.id === itemId);
        if (!item || item.type !== 'table') return;

        if (item.rows > 0) {
            const lastRowIndex = item.rows - 1;
            const lastRow = item.data[lastRowIndex];
            
            // Create a deep copy of the last row's structure and content
            // Tag with is_dynamic: true so we know this row was added during execution
            let newRow = lastRow.map(cell => {
                return {
                    ...cell,
                    content: cell.content, // Copy content/variables from the previous row as requested
                    is_dynamic: true 
                };
            });

            item.rows++;
            item.data.push(newRow);
            
            // Handle row heights if defined
            if (item.rowHeights) {
                const lastHeight = item.rowHeights[lastRowIndex] || 'auto';
                item.rowHeights.push(lastHeight);
            }

            renderBlocks();
            toastr.success('Đã thêm dòng mới vào bảng');
        }
    };

    window.executeDeleteTableRow = function(itemId, rowIdx) {
        const item = items.find(i => i.id === itemId);
        if (!item || item.type !== 'table') return;

        Swal.fire({
            title: 'Xóa dòng?',
            text: "Bạn có chắc chắn muốn xóa dòng này không?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Xóa ngay',
            cancelButtonText: 'Hủy'
        }).then((result) => {
            if (result.isConfirmed) {
                if (item.data && item.data.length > 1) {
                    item.data.splice(rowIdx, 1);
                    item.rows--;
                    if (item.rowHeights) item.rowHeights.splice(rowIdx, 1);
                    
                    renderBlocks();
                    saveStateDebounced();
                    toastr.info('Đã xóa dòng');
                } else {
                    Swal.fire('Lỗi', 'Không thể xóa dòng duy nhất còn lại', 'error');
                }
            }
        });
    };

    // --- Table Manipulations ---
    function modifyTable(action, param) {
        saveState();
        const item = items.find(i => i.id === selectedId);
        if (!item || item.type !== 'table') return;
        
        if (!item.data) item.data = [];
        if (!item.rowHeights) item.rowHeights = new Array(item.rows).fill('auto');

        const rIdx = activeRowIdx; 
        const cIdx = activeColIdx;

        if (action === 'addRow') {
            item.rows++;
            let newRow = [];
            for (let i = 0; i < item.cols; i++) {
                newRow.push({ content: '', rs: 1, cs: 1, hidden: false });
            }
            let insertAt = (param === 'up') ? Math.max(0, rIdx - 1) : rIdx;
            if (rIdx === 0 && param === 'up') insertAt = 0; 

            item.data.splice(insertAt, 0, newRow);
            item.rowHeights.splice(insertAt, 0, 'auto');
        } else if (action === 'deleteRow') {
            const selectedCells = Array.from(document.querySelectorAll('.selected-cell:not([data-row="0"])'))
                .filter(cell => cell.closest('.block-item').getAttribute('data-id') === selectedId);
            
            const rowsToDelete = new Set();
            if (selectedCells.length > 0) {
                selectedCells.forEach(cell => rowsToDelete.add(parseInt(cell.dataset.row) - 1));
            } else if (rIdx > 0) {
                rowsToDelete.add(rIdx - 1);
            }

            if (rowsToDelete.size > 0 && item.rows > rowsToDelete.size) {
                const sortedRows = Array.from(rowsToDelete).sort((a, b) => b - a);
                sortedRows.forEach(idx => {
                    item.data.splice(idx, 1);
                    if (item.rowHeights) item.rowHeights.splice(idx, 1);
                    item.rows--;
                });
                toastr.success(`Đã xóa ${rowsToDelete.size} hàng`);
            } else if (rowsToDelete.size > 0) {
                toastr.warning('Không thể xóa tất cả các hàng của bảng');
            }
        } else if (action === 'addCol') {
            item.cols++;
            let insertAt = (param === 'left') ? cIdx : cIdx + 1;
            item.columns.splice(insertAt, 0, {
                label: 'Cột mới',
                type: 'text',
                width: 'auto'
            });
            item.data.forEach(row => row.splice(insertAt, 0, {
                content: '',
                rs: 1,
                cs: 1,
                hidden: false
            }));
        } else if (action === 'deleteCol') {
            const selectedCells = Array.from(document.querySelectorAll('.selected-cell'))
                .filter(cell => cell.closest('.block-item').getAttribute('data-id') === selectedId);
            
            const colsToDelete = new Set();
            if (selectedCells.length > 0) {
                selectedCells.forEach(cell => colsToDelete.add(parseInt(cell.dataset.col)));
            } else {
                colsToDelete.add(cIdx);
            }

            if (colsToDelete.size > 0 && item.cols > colsToDelete.size) {
                const sortedCols = Array.from(colsToDelete).sort((a, b) => b - a);
                sortedCols.forEach(idx => {
                    item.columns.splice(idx, 1);
                    item.data.forEach(row => row.splice(idx, 1));
                    item.cols--;
                });
                toastr.success(`Đã xóa ${colsToDelete.size} cột`);
            } else if (colsToDelete.size > 0) {
                toastr.warning('Không thể xóa tất cả các cột của bảng');
            }
        }
        renderBlocks();
        selectItem(selectedId);
    }

    // --- Resize Engine ---
    let resizing = false;
    let resItem = null;
    let resType = null;
    let resIdx = null;
    let resStartDim = 0;
    let resStartMouse = 0;

    function initResize(e, itemId, type, idx) {
        e.preventDefault();
        e.stopPropagation();
        resizing = true;
        resItem = items.find(i => i.id === itemId);
        resType = type;
        resIdx = idx;

        saveState();

        const el = e.target.parentElement;
        if (type === 'col') {
            resStartDim = el.offsetWidth;
            resStartMouse = e.pageX;
        } else {
            resStartDim = el.offsetHeight;
            resStartMouse = e.pageY;
        }

        const blockEl = el.closest('.block-item');
        const tableEl = blockEl ? blockEl.querySelector('.mini-table') : null;
        
        const onMouseMove = (moveEvent) => {
            if (!resizing) return;
            
            if (resType === 'col') {
                const delta = moveEvent.pageX - resStartMouse;
                const newWidth = Math.max(20, resStartDim + delta);
                const widthVal = newWidth + 'px';
                
                if (resItem.columns[resIdx]) {
                    resItem.columns[resIdx].width = widthVal;
                }
                
                if (tableEl) {
                    const colCells = tableEl.querySelectorAll(`[data-col="${resIdx}"]`);
                    colCells.forEach(cell => {
                        cell.style.width = widthVal;
                        cell.style.minWidth = widthVal;
                        cell.style.maxWidth = widthVal;
                    });
                    
                    const wInput = document.getElementById('manualWidth');
                    if (wInput && selectedId === itemId) wInput.value = widthVal;
                }
            } else {
                const delta = moveEvent.pageY - resStartMouse;
                const newHeight = Math.max(20, resStartDim + delta);
                const heightVal = newHeight + 'px';
                
                if (!resItem.rowHeights) resItem.rowHeights = new Array(resItem.rows).fill('auto');
                resItem.rowHeights[resIdx] = heightVal;
                
                if (tableEl) {
                    const rowCells = tableEl.querySelectorAll(`[data-row="${resIdx + 1}"]`);
                    rowCells.forEach(hc => {
                        hc.style.height = heightVal;
                    });

                    const hInput = document.getElementById('manualHeight');
                    if (hInput && selectedId === itemId) hInput.value = heightVal;
                }
            }
        };

        const onMouseUp = () => {
            resizing = false;
            document.removeEventListener('mousemove', onMouseMove);
            document.removeEventListener('mouseup', onMouseUp);
            renderBlocks(); 
        };

        document.addEventListener('mousemove', onMouseMove);
        document.addEventListener('mouseup', onMouseUp);
    }
</script>
