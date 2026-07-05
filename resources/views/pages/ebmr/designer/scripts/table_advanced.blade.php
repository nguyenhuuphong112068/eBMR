<script>
    // --- Selection Logic ---
    // isSelecting/startCell/activeRowIdx/activeColIdx và các thao tác chọn ô nay
    // sống trong EbmrSelection (scripts/selection.blade.php). Hai hàm dưới đây được
    // giữ lại như alias mỏng vì tên của chúng đã được dùng rải rác trong codebase.
    function clearSelection() {
        EbmrSelection.cell.clear();
    }

    function highlightRange(start, end) {
        EbmrSelection.cell.setRange(start, end);
    }

    // --- Merge & Split ---
    function mergeSelectedCells() {
        const cells = document.querySelectorAll('.selected-cell:not([data-row="0"])');
        if (cells.length < 2) return;

        saveState();

        // Xác định block từ ô được chọn (không chỉ dựa vào selectedId)
        let item = items.find(i => i.id === selectedId);
        if (!item && cells.length > 0) {
            const blockEl = cells[0].closest('.block-item');
            if (blockEl) {
                const blockId = blockEl.getAttribute('data-id');
                item = items.find(i => i.id === blockId);
            }
        }

        if (!item || !item.data) {
            toastr.error('Không tìm thấy bảng dữ liệu. Vui lòng click vào bảng trước rồi thử lại.');
            return;
        }

        // Tính toán vùng gộp dựa trên data-col và cs thực tế của từng ô được chọn
        let minR = 999, maxR = -1, minC = 999, maxC = -1;

        cells.forEach(tdEl => {
            const r = parseInt(tdEl.dataset.row) - 1;
            const co = parseInt(tdEl.dataset.col);
            if (isNaN(r) || isNaN(co) || r < 0) return;

            // Lấy cs, rs thực tế từ data để tính đúng phạm vi
            const cellData = item.data[r] && item.data[r][co];
            const cs = (cellData && cellData.cs) ? cellData.cs : 1;
            const rs = (cellData && cellData.rs) ? cellData.rs : 1;

            minR = Math.min(minR, r);
            maxR = Math.max(maxR, r + rs - 1);  // tính đến cuối của rs
            minC = Math.min(minC, co);
            maxC = Math.max(maxC, co + cs - 1);  // tính đến cuối của cs
        });

        if (minR > maxR || minC > maxC) return;

        // Khởi tạo ô chính (top-left)
        if (!item.data[minR][minC] || typeof item.data[minR][minC] !== 'object') {
            item.data[minR][minC] = { content: '', rs: 1, cs: 1, hidden: false };
        }
        const mainCell = item.data[minR][minC];
        mainCell.rs = maxR - minR + 1;
        mainCell.cs = maxC - minC + 1;
        mainCell.hidden = false;

        // Gộp nội dung và đánh dấu hidden cho các ô trong vùng (trừ ô chính)
        let combinedParts = [];
        
        const isContentEmpty = (html) => {
            if (!html || typeof html !== 'string') return true;
            let tempDiv = document.createElement('div');
            tempDiv.innerHTML = html;
            // Nếu có các thẻ đặc biệt mang ý nghĩa nội dung thì không trống
            if (tempDiv.querySelector('img, .ebmr-field-badge, i.fas, i.far, i.fal')) return false;
            // Nếu chỉ có text trắng (sau khi loại bỏ HTML tags) thì là trống
            let text = tempDiv.textContent || tempDiv.innerText || '';
            if (text.trim() === '') return true;
            return false;
        };

        for (let r = minR; r <= maxR; r++) {
            if (!item.data[r]) continue;
            for (let c = minC; c <= maxC; c++) {
                if (r === minR && c === minC) continue;
                if (!item.data[r][c] || typeof item.data[r][c] !== 'object') {
                    item.data[r][c] = { content: '', rs: 1, cs: 1, hidden: false };
                }
                
                let cellHtml = item.data[r][c].content || '';
                if (!item.data[r][c].hidden && !isContentEmpty(cellHtml)) {
                    combinedParts.push(cellHtml);
                }
                
                item.data[r][c].hidden = true;
                item.data[r][c].rs = 1;
                item.data[r][c].cs = 1;
            }
        }
        
        if (combinedParts.length > 0) {
            if (isContentEmpty(mainCell.content)) {
                mainCell.content = combinedParts.join(" ");
            } else {
                mainCell.content += " " + combinedParts.join(" ");
            }
        } else if (isContentEmpty(mainCell.content)) {
            // Dọn dẹp thẻ <br> rác nếu ô chính thực sự trống
            mainCell.content = "";
        }

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
