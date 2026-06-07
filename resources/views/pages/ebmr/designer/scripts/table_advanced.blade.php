<script>
    let isSelecting = false;
    let startCell = null;
    let activeRowIdx = 0; // 0 for header, 1+ for body
    let activeColIdx = 0;

    // --- Selection Logic ---
    function clearSelection() {
        document.querySelectorAll('.selected-cell').forEach(c => c.classList.remove('selected-cell'));
    }

    function highlightRange(start, end) {
        clearSelection();
        const table = start.closest('.mini-table');
        if (!table) return;

        const r1 = parseInt(start.dataset.row);
        const c1 = parseInt(start.dataset.col);
        const r2 = parseInt(end.dataset.row);
        const c2 = parseInt(end.dataset.col);

        const minR = Math.min(r1, r2);
        const maxR = Math.max(r1, r2);
        const minC = Math.min(c1, c2);
        const maxC = Math.max(c1, c2);

        for (let r = minR; r <= maxR; r++) {
            for (let c = minC; c <= maxC; c++) {
                const cell = table.querySelector(`[data-row="${r}"][data-col="${c}"]`);
                if (cell) cell.classList.add('selected-cell');
            }
        }
    }

    // --- Merge & Split ---
    function mergeSelectedCells() {
        const cells = document.querySelectorAll('.selected-cell:not([data-row="0"])');
        if (cells.length < 2) return;

        saveState();
        const item = items.find(i => i.id === selectedId);

        let minR = 999,
            maxR = -1,
            minC = 999,
            maxC = -1;
        cells.forEach(c => {
            const r = parseInt(c.dataset.row) - 1;
            const co = parseInt(c.dataset.col);
            minR = Math.min(minR, r);
            maxR = Math.max(maxR, r);
            minC = Math.min(minC, co);
            maxC = Math.max(maxC, co);
        });

        if (!item.data[minR][minC] || typeof item.data[minR][minC] !== 'object') {
            item.data[minR][minC] = {
                content: item.data[minR][minC] || '',
                rs: 1,
                cs: 1,
                hidden: false
            };
        }
        const mainCell = item.data[minR][minC];
        mainCell.rs = maxR - minR + 1;
        mainCell.cs = maxC - minC + 1;

        let combinedContent = "";
        for (let r = minR; r <= maxR; r++) {
            for (let c = minC; c <= maxC; c++) {
                if (r === minR && c === minC) continue;
                if (!item.data[r][c] || typeof item.data[r][c] !== 'object') {
                    item.data[r][c] = {
                        content: item.data[r][c] || '',
                        rs: 1,
                        cs: 1,
                        hidden: false
                    };
                }
                if (item.data[r][c].content) {
                    combinedContent += (combinedContent ? " " : "") + item.data[r][c].content;
                }
                item.data[r][c].hidden = true;
            }
        }
        if (combinedContent) mainCell.content += (mainCell.content ? " " : "") + combinedContent;

        item.dirty = true;
        renderBlocks();
    }

    function openSplitModal() {
        const item = items.find(i => i.id === selectedId);
        const r = activeRowIdx - 1;
        const c = activeColIdx;
        if (r < 0 || !item) return;

        if (!item.data[r][c] || typeof item.data[r][c] !== 'object') {
            item.data[r][c] = {
                content: item.data[r][c] || '',
                rs: 1,
                cs: 1,
                hidden: false
            };
        }
        const cell = item.data[r][c];

        document.getElementById('splitRows').value = cell.rs || 1;
        document.getElementById('splitCols').value = cell.cs || 1;

        if (window.jQuery) {
            $('#splitModal').modal('show');
        } else {
            const modal = new bootstrap.Modal(document.getElementById('splitModal'));
            modal.show();
        }
    }

    function executeAdvancedSplit() {
        const rCount = parseInt(document.getElementById('splitRows').value);
        const cCount = parseInt(document.getElementById('splitCols').value);
        if (rCount < 1 || cCount < 1) return;

        saveState();
        const item = items.find(i => i.id === selectedId);
        
        if (!item) {
            if (window.jQuery) {
                $('#splitModal').modal('hide');
            } else {
                const modalEl = document.getElementById('splitModal');
                if (modalEl) {
                    const modalInstance = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                    modalInstance.hide();
                }
            }
            return;
        }

        const r = activeRowIdx - 1;
        const c = activeColIdx;
        const cell = item.data[r][c];

        const totalRs = cell.rs || 1;
        const totalCs = cell.cs || 1;

        if (totalRs >= rCount && totalCs >= cCount && totalRs % rCount === 0 && totalCs % cCount === 0) {
            const subRs = totalRs / rCount;
            const subCs = totalCs / cCount;

            for (let ir = r; ir < r + totalRs; ir++) {
                for (let ic = c; ic < c + totalCs; ic++) {
                    if (!item.data[ir][ic] || typeof item.data[ir][ic] !== 'object') {
                        item.data[ir][ic] = {
                            content: item.data[ir][ic] || '',
                            rs: 1,
                            cs: 1,
                            hidden: false
                        };
                    }
                    item.data[ir][ic].hidden = true;
                    item.data[ir][ic].rs = 1;
                    item.data[ir][ic].cs = 1;
                }
            }

            for (let ir = 0; ir < rCount; ir++) {
                for (let ic = 0; ic < cCount; ic++) {
                    const targetR = r + (ir * subRs);
                    const targetC = c + (ic * subCs);
                    item.data[targetR][targetC].hidden = false;
                    item.data[targetR][targetC].rs = subRs;
                    item.data[targetR][targetC].cs = subCs;
                }
            }
        } else {
            if (cCount > totalCs) {
                const extraCols = cCount - totalCs;
                for (let i = 0; i < extraCols; i++) {
                    const insertAt = c + 1;
                    item.cols++;
                    item.columns.splice(insertAt, 0, {
                        label: 'Split',
                        type: 'text',
                        width: 'auto'
                    });
                    for (let ir = 0; ir < item.rows; ir++) {
                        item.data[ir].splice(insertAt, 0, {
                            content: '',
                            rs: 1,
                            cs: 1,
                            hidden: true
                        });
                        const rowCell = item.data[ir][c];
                        if (ir >= r && ir < r + totalRs) {
                            if (ir === r) item.data[ir][insertAt].hidden = false;
                        } else {
                            if (!rowCell.hidden) {
                                rowCell.cs = (rowCell.cs || 1) + 1;
                                item.data[ir][insertAt].hidden = true;
                            }
                        }
                    }
                }
            }
            if (rCount > totalRs) {
                const extraRows = rCount - totalRs;
                for (let i = 0; i < extraRows; i++) {
                    const insertAt = r + 1;
                    item.rows++;
                    if (!item.rowHeights) item.rowHeights = new Array(item.rows - 1).fill('auto');
                    item.rowHeights.splice(insertAt, 0, 'auto');
                    const newRow = [];
                    for (let ic = 0; ic < item.cols; ic++) newRow.push({
                        content: '',
                        rs: 1,
                        cs: 1,
                        hidden: true
                    });
                    item.data.splice(insertAt, 0, newRow);

                    for (let ic = 0; ic < item.cols; ic++) {
                        const colCell = item.data[r][ic];
                        if (ic >= c && ic < c + (cCount > totalCs ? cCount : totalCs)) {
                            if (ic === c) item.data[insertAt][ic].hidden = false;
                        } else {
                            if (!colCell.hidden) {
                                colCell.rs = (colCell.rs || 1) + 1;
                                item.data[insertAt][ic].hidden = true;
                            }
                        }
                    }
                }
            }
        }

        if (window.jQuery) {
            $('#splitModal').modal('hide');
        } else {
            const modalEl = document.getElementById('splitModal');
            const modalInstance = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
            modalInstance.hide();
        }
        item.dirty = true;
        renderBlocks();
    }

    // --- Manual Sizing ---
    function updateManualSize(type, val) {

        saveState();
        const item = items.find(i => i.id === selectedId);
        if (!item || item.type !== 'table') return;

        const r = activeRowIdx - 1;
        const c = activeColIdx;

        if (val && !isNaN(val)) val = val + 'px';

        if (type === 'width') {
            const cells = document.querySelectorAll('.selected-cell');
            const selectedCols = new Set();
            cells.forEach(cell => selectedCols.add(parseInt(cell.dataset.col)));
            if (selectedCols.size === 0) selectedCols.add(c);
            selectedCols.forEach(colIdx => item.columns[colIdx].width = val);
        } else {
            const cells = document.querySelectorAll('.selected-cell:not([data-row="0"])');
            const selectedRows = new Set();
            cells.forEach(cell => selectedRows.add(parseInt(cell.dataset.row) - 1));
            if (selectedRows.size === 0) selectedRows.add(r);
            selectedRows.forEach(rowIdx => {
                if (!item.rowHeights) item.rowHeights = new Array(item.rows).fill('auto');
                item.rowHeights[rowIdx] = val;
            });
        }
        item.dirty = true;
        renderBlocks();
    }

    function distributeTableSizes(type) {

        saveState();
        const item = items.find(i => i.id === selectedId);
        if (!item || item.type !== 'table') return;
        const cells = document.querySelectorAll('.selected-cell');

        if (type === 'cols') {
            const selectedCols = new Set();
            cells.forEach(cell => selectedCols.add(parseInt(cell.dataset.col)));
            if (selectedCols.size < 2) {
                const avg = (100 / item.cols).toFixed(2) + '%';
                item.columns.forEach(col => col.width = avg);
            } else {
                const firstColWidth = item.columns[Array.from(selectedCols)[0]].width || 'auto';
                selectedCols.forEach(cIdx => item.columns[cIdx].width = firstColWidth);
            }
        } else {
            const selectedRows = new Set();
            cells.forEach(cell => {
                const row = parseInt(cell.dataset.row) - 1;
                if (row >= 0) selectedRows.add(row);
            });
            if (selectedRows.size < 2) {
                item.rowHeights = new Array(item.rows).fill('auto');
            } else {
                const firstRowH = (item.rowHeights && item.rowHeights[Array.from(selectedRows)[0]]) || 'auto';
                if (!item.rowHeights) item.rowHeights = new Array(item.rows).fill('auto');
                selectedRows.forEach(rIdx => item.rowHeights[rIdx] = firstRowH);
            }
        }
        item.dirty = true;
        renderBlocks();
    }
</script>
