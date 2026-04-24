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
                
                // Only clear and restart selection if not shift-clicking an already selected range
                if (!e.shiftKey || !cell.classList.contains('selected-cell')) {
                    clearSelection();
                    cell.classList.add('selected-cell');
                }
                
                const blockItem = cell.closest('.block-item');
                const bId = blockItem ? blockItem.getAttribute('data-id') : null;
                
                // Default: select table/item (we handle field selection on mouseup if it was a single click)
                if (bId) selectItem(bId, false);
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

        document.addEventListener('mouseup', (e) => {
            isSelecting = false;
            
            // --- Batch Field Selection Logic (Shift + Drag) ---
            if (e.shiftKey) {
                const selectedCells = document.querySelectorAll('.selected-cell');
                if (selectedCells.length > 1) {
                    let fieldIdsInSelection = [];
                    selectedCells.forEach(td => {
                        const badge = td.querySelector('.ebmr-field-badge');
                        if (badge) {
                            const fid = badge.getAttribute('data-field-id');
                            if (fid) fieldIdsInSelection.push(fid);
                        }
                    });

                    if (fieldIdsInSelection.length > 0) {
                        // Use a tiny timeout to ensure it runs AFTER any other mousedown/mouseup logic that might update the panel
                        setTimeout(() => {
                            if (typeof selectMultipleFields === 'function') {
                                selectMultipleFields(fieldIdsInSelection);
                            }
                        }, 50);
                    }
                } else if (selectedCells.length === 1) {
                    const badge = selectedCells[0].querySelector('.ebmr-field-badge');
                    if (badge) {
                        const fid = badge.getAttribute('data-field-id');
                        if (fid) {
                            setTimeout(() => {
                                selectField(null, fid);
                            }, 50);
                        }
                    }
                }
            }
        });

        // 2. Paste Logic (Smart Paste)
        document.addEventListener('paste', (e) => {
            if (window.isExecutionMode) return;
            // Ignore if target is a standard input or textarea (allow native paste)
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;

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

            // --- Ctrl + F / H for Search & Replace ---
            if (e.ctrlKey) {
                const key = e.key.toLowerCase();
                if (key === 'f' || key === 'h') {
                    e.preventDefault();
                    if (typeof openSearchModal === 'function') openSearchModal(key === 'h');
                    return;
                }
            }

            // --- Ctrl + C / X / V for Cells ---
            if (e.ctrlKey) {
                const selectedCells = document.querySelectorAll('.selected-cell');
                const key = e.key.toLowerCase();
                
                if (['c', 'x', 'v'].includes(key)) {
                    const isInput = e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA';
                    const isWriting = isInput || e.target.closest('[contenteditable="true"]');
                    const hasMultiSelection = selectedCells.length > 0;

                    // If NOT writing in a text box/input, or if we have a table selection, handle cell-level operations
                    if (!isWriting || (hasMultiSelection && !isInput)) {
                        const targetCell = hasMultiSelection ? selectedCells[0] : e.target.closest('td, th');
                        if (!targetCell) return;
                        
                        const table = targetCell.closest('.mini-table');
                        if (!table) return;
                        const blockItem = table.closest('.block-item');
                        const itemId = blockItem ? blockItem.getAttribute('data-id') : null;
                        const item = items.find(i => i.id === itemId);
                        if (!item) return;

                        if (key === 'c' || key === 'x') {
                            e.preventDefault();
                            let minR = 999, maxR = -1, minC = 999, maxC = -1;
                            const selection = hasMultiSelection ? selectedCells : [targetCell];
                            
                            selection.forEach(c => {
                                const r = parseInt(c.dataset.row);
                                const co = parseInt(c.dataset.col);
                                minR = Math.min(minR, r);
                                maxR = Math.max(maxR, r);
                                minC = Math.min(minC, co);
                                maxC = Math.max(maxC, co);
                            });

                            // Capture the grid
                            const capturedGrid = [];
                            for (let r = minR; r <= maxR; r++) {
                                const rowData = [];
                                for (let c = minC; c <= maxC; c++) {
                                    if (r === 0) rowData.push({ type: 'header', content: item.columns[c].label });
                                    else {
                                        const cell = item.data[r-1][c];
                                        rowData.push({ 
                                            type: 'cell', 
                                            data: (typeof cell === 'object' && cell !== null) ? {...cell} : {content: cell, rs:1, cs:1, hidden:false}
                                        });
                                    }
                                }
                                capturedGrid.push(rowData);
                            }
                            cellClipboard = capturedGrid;
                            
                            if (key === 'x') {
                                saveState();
                                selection.forEach(c => {
                                    const r = parseInt(c.dataset.row);
                                    const co = parseInt(c.dataset.col);
                                    if (r === 0) item.columns[co].label = '';
                                    else {
                                        if (typeof item.data[r-1][co] === 'object') item.data[r-1][co].content = '';
                                        else item.data[r-1][co] = '';
                                    }
                                });
                                renderBlocks();
                                saveStateDebounced();
                            }
                            
                            toastr.info(key === 'c' ? 'Đã sao chép ô' : 'Đã cắt ô');
                        } else if (key === 'v') {
                            if (!cellClipboard) return;
                            e.preventDefault();
                            saveState();
                            
                            const startR = parseInt(targetCell.dataset.row);
                            const startC = parseInt(targetCell.dataset.col);
                            
                            cellClipboard.forEach((rowData, rOff) => {
                                const targetR = startR + rOff;
                                rowData.forEach((clipCell, cOff) => {
                                    const targetC = startC + cOff;
                                    
                                    if (targetR === 0) {
                                        if (item.columns[targetC]) item.columns[targetC].label = clipCell.content || '';
                                    } else {
                                        const rIdx = targetR - 1;
                                        if (item.data[rIdx] && item.data[rIdx][targetC] !== undefined) {
                                            if (clipCell.type === 'header') {
                                                if (typeof item.data[rIdx][targetC] === 'object') item.data[rIdx][targetC].content = clipCell.content;
                                                else item.data[rIdx][targetC] = clipCell.content;
                                            } else {
                                                item.data[rIdx][targetC] = {...clipCell.data};
                                            }
                                        }
                                    }
                                });
                            });
                            
                            renderBlocks();
                            saveStateDebounced();
                            toastr.success('Đã dán nội dung ô');
                        }
                        return; // Handled
                    }
                }
            }

            // --- Multi-cell Deletion ---
            if (e.key === 'Delete' || e.key === 'Backspace') {
                const selectedCells = document.querySelectorAll('.selected-cell');
                // Only trigger bulk delete if 2+ cells are selected, or if focus is not on a contenteditable
                const isWriting = e.target.closest('[contenteditable="true"]');
                
                if (selectedCells.length > 1 || (selectedCells.length === 1 && !isWriting)) {
                    e.preventDefault();
                    saveState();
                    let dataChanged = false;

                    selectedCells.forEach(cell => {
                        const table = cell.closest('.mini-table');
                        if (!table) return;
                        const blockItem = table.closest('.block-item');
                        if (!blockItem) return;
                        
                        const itemId = blockItem.getAttribute('data-id');
                        const item = items.find(i => i.id === itemId);
                        
                        if (!item) {
                            console.warn("Item not found for ID:", itemId);
                            return;
                        }

                        const rStr = cell.dataset.row;
                        const cStr = cell.dataset.col;
                        if (rStr === undefined || cStr === undefined) return;
                        
                        const r = parseInt(rStr);
                        const c = parseInt(cStr);

                        console.log("Cleaning cell:", r, c, "in item:", itemId);

                        if (r === 0) {
                            if (item.columns && item.columns[c]) {
                                item.columns[c].label = '';
                                dataChanged = true;
                            }
                        } else {
                            if (item.data && item.data[r - 1]) {
                                let cellRef = item.data[r - 1][c];
                                if (typeof cellRef !== 'object' || cellRef === null) {
                                    item.data[r - 1][c] = { content: '', rs: 1, cs: 1, hidden: false };
                                    cellRef = item.data[r - 1][c];
                                }
                                cellRef.content = '';
                                dataChanged = true;
                            }
                        }
                    });

                    if (dataChanged) {
                        renderBlocks();
                        // Re-highlight selection after render
                        setTimeout(() => {
                            const newTable = document.querySelector('.block-item.active .mini-table');
                            if (newTable) {
                                // Logic to re-apply the classes if we want, but renderBlocks clears them.
                                // Actually better to NOT re-render if only content changes?
                                // No, renderBlocks is needed for consistency.
                            }
                        }, 50);
                    }
                    return;
                }
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

    function sanitizePastedHtml(html) {
        if (!html) return html;
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        
        // Remove style attributes that might cause issues, but keep important ones
        // AND enforce min font size 14pt
        const allElements = doc.querySelectorAll('*');
        allElements.forEach(el => {
            if (el.style.fontSize) {
                const size = parseFloat(el.style.fontSize);
                const unit = el.style.fontSize.replace(/[0-9.]/g, '').trim().toLowerCase();
                
                if (unit === 'pt' && size < 14) el.style.fontSize = '14pt';
                else if (unit === 'px' && size < 18.6) el.style.fontSize = '14pt';
                else if (unit === 'em' && size < 1.1) el.style.fontSize = '14pt';
                else if (unit === 'rem' && size < 1.1) el.style.fontSize = '14pt';
            } else {
                // If no font size specified, default to 14pt for modern look
                el.style.fontSize = '14pt';
            }
            
            // Clean up other intrusive styles
            el.style.fontFamily = "'Inter', 'Roboto', sans-serif";
            if (el.style.lineHeight && parseFloat(el.style.lineHeight) < 1.5) el.style.lineHeight = '1.5';
        });

        return doc.body.innerHTML;
    }

    function handleGlobalPaste(e, forcedIndex = null) {
        if (e.target.closest('[contenteditable="true"]')) return;
        const htmlData = (e.clipboardData || window.clipboardData).getData('text/html');
        const plainText = (e.clipboardData || window.clipboardData).getData('text/plain');
        if (!htmlData && !plainText) return;

        e.preventDefault();
        saveState();

        let insertIndex = items.length;
        if (forcedIndex !== null) {
            insertIndex = forcedIndex;
        } else if (selectedId) {
            const currentIdx = items.findIndex(i => i.id === selectedId);
            if (currentIdx !== -1) insertIndex = currentIdx + 1;
        } else if (window.activeSectionId) {
            // Fallback: Add to the end of the active section if no specific block is selected
            let lastIdxInSection = -1;
            for (let i = items.length - 1; i >= 0; i--) {
                const itemSectId = (items[i].type === 'section') ? items[i].id : items[i].section_id;
                if (itemSectId === window.activeSectionId) {
                    lastIdxInSection = i;
                    break;
                }
            }
            if (lastIdxInSection !== -1) insertIndex = lastIdxInSection + 1;
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
                } else {
                    // Collect everything else (div, p, span, br, text) into pending
                    if (node.nodeType === Node.ELEMENT_NODE) {
                        pendingContent += sanitizePastedHtml(node.outerHTML);
                    } else if (node.nodeType === Node.TEXT_NODE) {
                        pendingContent += node.textContent;
                    }
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
        let sectionId = window.activeSectionId || null;
        if (!sectionId && index !== null) {
            if (index > 0 && items[index-1]) sectionId = items[index - 1].section_id;
            else if (items.length > 0) sectionId = items[0].section_id;
        }

        items.splice(index, 0, { 
            id: id, 
            type: 'static-text', 
            section_id: sectionId,
            label: 'Ghi chú (Pasted)', 
            content: html, 
            columns: [],
            borderMode: 'none'
        });
    }

    function addPastedTableBlock(tableEl, index) {
        const rows = Array.from(tableEl.querySelectorAll('tr'));
        if (rows.length === 0) return;
        
        let sectionId = window.activeSectionId || null;
        if (!sectionId && index !== null) {
            if (index > 0 && items[index-1]) sectionId = items[index - 1].section_id;
            else if (items.length > 0) sectionId = items[0].section_id;
        }
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
                rowData.push({ content: cells[c] ? sanitizePastedHtml(cells[c].innerHTML) : '', rs: 1, cs: 1, hidden: false });
            }
            data.push(rowData);
        });

        items.splice(index, 0, {
            id: id, type: 'table', section_id: sectionId, label: 'Bảng (Pasted)', rows: rowCount, cols: colCount,
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
                // Better parsing for rowspan/colspan
                const rows = table.querySelectorAll('tr');
                const virtualGrid = [];
                
                rows.forEach((tr, rIdx) => {
                    if (!virtualGrid[rIdx]) virtualGrid[rIdx] = [];
                    let cIdx = 0;
                    
                    tr.querySelectorAll('td, th').forEach(cell => {
                        // Find next available column
                        while (virtualGrid[rIdx][cIdx]) cIdx++;
                        
                        const rs = parseInt(cell.getAttribute('rowspan')) || 1;
                        const cs = parseInt(cell.getAttribute('colspan')) || 1;
                        const content = sanitizePastedHtml(cell.innerHTML);
                        
                        // Extract styling
                        const style = cell.style || {};
                        const bgColor = cell.getAttribute('bgcolor') || style.backgroundColor || '';
                        const textAlign = cell.getAttribute('align') || style.textAlign || '';
                        const fontWeight = style.fontWeight || '';
                        const fontStyle = style.fontStyle || '';

                        // Place main cell
                        virtualGrid[rIdx][cIdx] = { 
                            content, rs, cs, hidden: false,
                            backgroundColor: bgColor,
                            textAlign: textAlign,
                            fontWeight: fontWeight,
                            fontStyle: fontStyle
                        };
                        
                        // Fill shadow cells
                        for (let dr = 0; dr < rs; dr++) {
                            for (let dc = 0; dc < cs; dc++) {
                                if (dr === 0 && dc === 0) continue;
                                const trIndex = rIdx + dr;
                                const tcIndex = cIdx + dc;
                                if (!virtualGrid[trIndex]) virtualGrid[trIndex] = [];
                                virtualGrid[trIndex][tcIndex] = { content: '', rs: 1, cs: 1, hidden: true };
                            }
                        }
                        cIdx += cs;
                    });
                });
                grid = virtualGrid;
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
                const newRow = [];
                for(let i=0; i<item.cols; i++) newRow.push({content:'', rs:1, cs:1, hidden:false});
                item.data.push(newRow);
                dataChanged = true;
            }
            rowData.forEach((cellObj, cOffset) => {
                const cIndex = startC + cOffset;
                if (cIndex >= item.cols) {
                    item.cols++;
                    item.columns.push({ label: 'Cột ' + item.cols, type: 'text', width: 'auto' });
                    item.data.forEach(row => row.push({content:'', rs:1, cs:1, hidden:false}));
                    dataChanged = true;
                }
                
                // cellObj is now an object: { content, rs, cs, hidden, backgroundColor, textAlign, ... }
                if (rIndex === 0) {
                    item.columns[cIndex].label = cellObj.content || cellObj;
                } else {
                    item.data[rIndex - 1][cIndex] = {
                        content: cellObj.content || cellObj,
                        rs: cellObj.rs || 1,
                        cs: cellObj.cs || 1,
                        hidden: cellObj.hidden || false,
                        backgroundColor: cellObj.backgroundColor || '',
                        textAlign: cellObj.textAlign || '',
                        fontWeight: cellObj.fontWeight || '',
                        fontStyle: cellObj.fontStyle || ''
                    };
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
    // --- Real-time Toolbar Updates ---
    document.addEventListener('selectionchange', () => {
        // Only update if we are in a contenteditable area
        const selection = window.getSelection();
        if (selection.rangeCount > 0) {
            let node = selection.anchorNode;
            if (!node) return;
            if (node.nodeType === 3) node = node.parentElement;
            
            if (node && (node.closest('[contenteditable="true"]') || node.getAttribute('contenteditable') === 'true')) {
                const styles = window.getComputedStyle(node);
                const fontSize = styles.fontSize;
                
                // Convert px back to pt for the display
                // 1pt = 1.333px -> pt = px * 0.75
                const sizeInPx = parseFloat(fontSize);
                const sizeInPt = Math.round(sizeInPx * 0.75);
                
                const fontSizeInput = document.getElementById('customFontSize');
                if (fontSizeInput && document.activeElement !== fontSizeInput) {
                    fontSizeInput.value = sizeInPt;
                }
            }
        }
    });
</script>
