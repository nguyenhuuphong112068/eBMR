<script>
    function initTableAdvancedFeatures() {
        let marqueeDiv = null;
        let marqueeStartX = 0;
        let marqueeStartY = 0;
        let isMarqueeActive = false;


        // 1. Selection Logic
        document.addEventListener('mousedown', (e) => {
            if (window.isExecutionMode) return; // Chặn chọn khối ở chế độ ghi chép
            if (window.isSelectVarMode) return; // Chặn chọn khối/ô khi đang bắt biến

            // -- MARQUEE SELECTOR LOGIC (Shift + Left Click) --
            if (e.shiftKey && e.button === 0 && e.target.closest('#editor-content')) {
                e.preventDefault();
                window.getSelection().removeAllRanges();
                
                isMarqueeActive = true;
                marqueeStartX = e.clientX;
                marqueeStartY = e.clientY;
                
                marqueeDiv = document.createElement('div');
                marqueeDiv.className = 'marquee-selector';
                marqueeDiv.style.left = marqueeStartX + 'px';
                marqueeDiv.style.top = marqueeStartY + 'px';
                marqueeDiv.style.width = '0px';
                marqueeDiv.style.height = '0px';
                document.body.appendChild(marqueeDiv);
                
                clearSelection();
                return; // Dừng lại, không chạy logic chọn ô cũ
            }

            const cell = e.target.closest('td, th');
            if (cell && cell.closest('.mini-table')) {
                const blockItem = cell.closest('.block-item');
                const bId = blockItem ? blockItem.getAttribute('data-id') : null;
                
                // Nếu click chuột phải
                if (e.button === 2) {
                    // Nếu ô click phải chưa được chọn, thì mới xoá chọn cũ và chọn ô này. Nếu đã chọn rồi thì giữ nguyên.
                    if (!cell.classList.contains('selected-cell')) {
                        clearSelection();
                        cell.classList.add('selected-cell');
                        if (bId) selectItem(bId, false);
                    }
                    return;
                }

                // Nếu click vào block khác, chuyển active block sang block mới
                if (bId && bId !== selectedId) {
                    selectItem(bId, false);
                }

                // Kiểm tra xem click có ở sát lề ô không (dành cho chọn ô)
                const rect = cell.getBoundingClientRect();
                const isNearLeftEdge = (e.clientX - rect.left) < 15;
                const isNearTopEdge = (e.clientY - rect.top) < 15;
                const isNearEdge = isNearLeftEdge || isNearTopEdge;

                // Nếu KHÔNG phải đang giữ Shift, KHÔNG click sát lề 
                // => Xử lý như bôi đen văn bản bình thường (Native Text Selection)
                if (!e.shiftKey && !isNearEdge) {
                    activeRowIdx = parseInt(cell.dataset.row);
                    activeColIdx = parseInt(cell.dataset.col);
                    clearSelection();
                    cell.classList.add('selected-cell');
                    if (bId) selectItem(bId, false); // Vẫn báo cho panel biết đang ở block nào
                    return; // Dừng lại ở đây, KHÔNG bật isSelecting để tránh đụng độ
                }

                // Bắt đầu chế độ chọn Ô (Cell Selection cũ - rê mép lề)
                // Ngăn chặn native text selection flash khi kéo thả nhiều ô
                if (isNearEdge) {
                    e.preventDefault(); 
                }

                isSelecting = true;
                startCell = cell;
                activeRowIdx = parseInt(cell.dataset.row);
                activeColIdx = parseInt(cell.dataset.col);
                
                clearSelection();
                cell.classList.add('selected-cell');
                
                if (bId) selectItem(bId, false);
            } else if (!e.target.closest('#property-panel') && !e.target.closest('.editor-toolbar') && !e.target.closest('#design-context-menu') && !e.target.closest('#na-context-menu')) {
                clearSelection();
            }
        });

        document.addEventListener('mousemove', (e) => {

            if (isMarqueeActive && marqueeDiv) {
                const currentX = e.clientX;
                const currentY = e.clientY;
                
                const x = Math.min(marqueeStartX, currentX);
                const y = Math.min(marqueeStartY, currentY);
                const w = Math.abs(currentX - marqueeStartX);
                const h = Math.abs(currentY - marqueeStartY);
                
                marqueeDiv.style.left = x + 'px';
                marqueeDiv.style.top = y + 'px';
                marqueeDiv.style.width = w + 'px';
                marqueeDiv.style.height = h + 'px';
                
                // Dùng toạ độ toán học trực tiếp thay vì getBoundingClientRect để tránh độ trễ DOM render
                const rect = {
                    left: x,
                    right: x + w,
                    top: y,
                    bottom: y + h
                };
                
                const cells = document.querySelectorAll('#editor-content td, #editor-content th');
                cells.forEach(cell => {
                    const cellRect = cell.getBoundingClientRect();
                    const intersect = !(rect.right < cellRect.left || 
                                      rect.left > cellRect.right || 
                                      rect.bottom < cellRect.top || 
                                      rect.top > cellRect.bottom);
                    if (intersect) {
                        cell.classList.add('selected-cell');
                    } else {
                        cell.classList.remove('selected-cell');
                    }
                });
                return;
            }
        });

        document.addEventListener('mouseover', (e) => {
            if (window.isExecutionMode || !isSelecting || isMarqueeActive) return;
            const cell = e.target.closest('td, th');
            if (cell && cell.closest('.mini-table') === startCell.closest('.mini-table')) {
                highlightRange(startCell, cell);
            }
        });

        let justFinishedMarquee = false;

        // Chặn sự kiện click ngay sau khi kết thúc quét khối
        document.addEventListener('click', (e) => {
            if (justFinishedMarquee) {
                e.preventDefault();
                e.stopPropagation();
            }
        }, true); // Dùng capture phase để chặn sớm nhất

        document.addEventListener('mouseup', (e) => {
            isSelecting = false;

            if (isMarqueeActive) {
                isMarqueeActive = false;
                justFinishedMarquee = true;
                setTimeout(() => justFinishedMarquee = false, 150); // Chặn click trong 150ms
                
                if (marqueeDiv) {
                    marqueeDiv.remove();
                    marqueeDiv = null;
                }
            }
            
            // --- Cập nhật Properties Panel sau khi nhả chuột ---
            if (e.target.closest('#property-panel')) return;
            if (window.isSelectVarMode) return;
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
                    // Dùng timeout để đảm bảo logic chạy sau các sự kiện click mặc định khác
                    setTimeout(() => {
                        if (typeof selectMultipleFields === 'function') {
                            selectMultipleFields(fieldIdsInSelection);
                        }
                    }, 50);
                }
            } else {
                // Lựa chọn thông minh: Chỉ chọn cấu hình biến nếu chuột click trực tiếp vào biến số
                const badge = e.target.closest('.ebmr-field-badge');
                if (badge) {
                    const fid = badge.getAttribute('data-field-id');
                    if (fid) {
                        setTimeout(() => {
                            selectField(null, fid);
                        }, 50);
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

                    // Allow native text copy/cut if text is highlighted
                    if (isWriting && ['c', 'x'].includes(key)) {
                        const selText = window.getSelection().toString();
                        if (selText && selText.length > 0) {
                            return; // Do not intercept, let browser copy the text
                        }
                    }

                    // If NOT writing in a text box/input, or if we have a table selection, handle cell-level operations
                    if (!isWriting || (hasMultiSelection && !isInput)) {
                        const focusedCell = e.target.closest('td, th');
                        const targetCell = focusedCell ? focusedCell : (hasMultiSelection ? selectedCells[0] : null);
                        if (!targetCell) {
                            if (key === 'c') {
                                e.preventDefault();
                                copyBlock();
                            } else if (key === 'x') {
                                e.preventDefault();
                                cutBlock();
                            } else if (key === 'v') {
                                if (window.blockClipboard && window.blockClipboard.blocks && window.blockClipboard.blocks.length > 0) {
                                    e.preventDefault();
                                    pasteBlock();
                                }
                            }
                            return;
                        }
                        
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

                            // Initialize name mapping for formula translation
                            window.__ebmrPastedNameMapping = {};
                            window.__ebmrPastedFormulaFields = [];
                            
                            const isSingleCellCopied = cellClipboard.length === 1 && cellClipboard[0].length === 1;

                            if (isSingleCellCopied && hasMultiSelection && selectedCells.length > 1) {
                                const clipCell = cellClipboard[0][0];
                                selectedCells.forEach(cell => {
                                    const tr = parseInt(cell.dataset.row);
                                    const tc = parseInt(cell.dataset.col);
                                    
                                    if (tr === 0) {
                                        if (item.columns[tc]) {
                                            item.columns[tc].label = window.duplicateFieldBadgesInHtml ? window.duplicateFieldBadgesInHtml(clipCell.content || '', itemId) : (clipCell.content || '');
                                        }
                                    } else {
                                        const rIdx = tr - 1;
                                        if (item.data[rIdx] && item.data[rIdx][tc] !== undefined) {
                                            if (clipCell.type === 'header') {
                                                const duplicated = window.duplicateFieldBadgesInHtml ? window.duplicateFieldBadgesInHtml(clipCell.content || '', itemId) : clipCell.content;
                                                if (typeof item.data[rIdx][tc] === 'object') item.data[rIdx][tc].content = duplicated;
                                                else item.data[rIdx][tc] = duplicated;
                                            } else {
                                                item.data[rIdx][tc] = {...clipCell.data};
                                                if (item.data[rIdx][tc].content) {
                                                    item.data[rIdx][tc].content = window.duplicateFieldBadgesInHtml ? window.duplicateFieldBadgesInHtml(item.data[rIdx][tc].content, itemId) : item.data[rIdx][tc].content;
                                                }
                                                delete item.data[rIdx][tc].db_id;
                                                delete item.data[rIdx][tc].content_db_id;
                                            }
                                        }
                                    }
                                });
                            } else {
                                const startR = parseInt(targetCell.dataset.row);
                                const startC = parseInt(targetCell.dataset.col);
                                
                                cellClipboard.forEach((rowData, rOff) => {
                                    const targetR = startR + rOff;
                                    rowData.forEach((clipCell, cOff) => {
                                        const targetC = startC + cOff;
                                        
                                        if (targetR === 0) {
                                            if (item.columns[targetC]) {
                                                item.columns[targetC].label = window.duplicateFieldBadgesInHtml ? window.duplicateFieldBadgesInHtml(clipCell.content || '', itemId) : (clipCell.content || '');
                                            }
                                        } else {
                                            const rIdx = targetR - 1;
                                            if (item.data[rIdx] && item.data[rIdx][targetC] !== undefined) {
                                                if (clipCell.type === 'header') {
                                                    const duplicated = window.duplicateFieldBadgesInHtml ? window.duplicateFieldBadgesInHtml(clipCell.content || '', itemId) : clipCell.content;
                                                    if (typeof item.data[rIdx][targetC] === 'object') item.data[rIdx][targetC].content = duplicated;
                                                    else item.data[rIdx][targetC] = duplicated;
                                                } else {
                                                    item.data[rIdx][targetC] = {...clipCell.data};
                                                    if (item.data[rIdx][targetC].content) {
                                                        item.data[rIdx][targetC].content = window.duplicateFieldBadgesInHtml ? window.duplicateFieldBadgesInHtml(item.data[rIdx][targetC].content, itemId) : item.data[rIdx][targetC].content;
                                                    }
                                                    delete item.data[rIdx][targetC].db_id;
                                                    delete item.data[rIdx][targetC].content_db_id;
                                                }
                                            }
                                        }
                                    });
                                });
                            }

                            item.dirty = true;

                            if (typeof window.translateFormulaReferencesOfPastedFields === 'function') {
                                window.translateFormulaReferencesOfPastedFields();
                            }
                            
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
                const isWriting = e.target.closest('[contenteditable="true"]') || e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA';
                
                if (!isWriting && selectedCells.length > 0) {
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
            } else if (e.ctrlKey && e.key.toLowerCase() === 'e') {
                e.preventDefault();
                if (typeof setDesignerMode === 'function') setDesignerMode(!window.isExecutionMode);
            }
            handleTableNavigation(e);
        });
    }

    function sanitizePastedHtml(html) {
        if (!html) return html;
        if (typeof window.duplicateFieldBadgesInHtml === 'function') {
            html = window.duplicateFieldBadgesInHtml(html);
        }
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
            el.style.color = '#000000'; // Force black color
            if (el.style.lineHeight && parseFloat(el.style.lineHeight) < 1.5) el.style.lineHeight = '1.5';
        });

        return doc.body.innerHTML;
    }

    function handleGlobalPaste(e, forcedIndex = null) {
        if (e.target.closest('[contenteditable="true"]') || e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;
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
            const secIdx = items.findIndex(item => item.type === 'section' && item.id === window.activeSectionId);
            if (secIdx !== -1) {
                let lastIdxInSection = secIdx;
                for (let i = secIdx + 1; i < items.length; i++) {
                    if (items[i].type === 'section') {
                        break;
                    }
                    lastIdxInSection = i;
                }
                insertIndex = lastIdxInSection + 1;
            }
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

            const processNode = (node) => {
                if (node.nodeName === 'TABLE') {
                    flushPending();
                    addPastedTableBlock(node, insertIndex++);
                } else if (node.nodeType === Node.ELEMENT_NODE && node.querySelector('table')) {
                    // Container element containing tables, recurse into its children
                    Array.from(node.childNodes).forEach(processNode);
                } else {
                    // Collect everything else (div, p, span, br, text) into pending
                    if (node.nodeType === Node.ELEMENT_NODE) {
                        pendingContent += sanitizePastedHtml(node.outerHTML);
                    } else if (node.nodeType === Node.TEXT_NODE) {
                        pendingContent += node.textContent;
                    }
                }
            };

            Array.from(body.childNodes).forEach(processNode);
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
            if (index > 0 && items[index-1]) {
                const prevItem = items[index - 1];
                sectionId = prevItem.section_id || (prevItem.type === 'section' ? prevItem.id : null);
            }
            else if (items.length > 0) {
                const firstItem = items[0];
                sectionId = firstItem.section_id || (firstItem.type === 'section' ? firstItem.id : null);
            }
        }

        let pastedHtml = html;
        if (typeof pastedHtml === 'string' && window.duplicateFieldBadgesInHtml) {
            pastedHtml = window.duplicateFieldBadgesInHtml(pastedHtml, id);
        }

        items.splice(index, 0, { 
            id: id, 
            type: 'static-text', 
            section_id: sectionId,
            label: 'Ghi chú (Pasted)', 
            content: pastedHtml, 
            columns: [],
            borderMode: 'none'
        });
    }

    function addPastedTableBlock(tableEl, index) {
        const rows = Array.from(tableEl.querySelectorAll('tr'));
        if (rows.length === 0) return;
        
        let sectionId = window.activeSectionId || null;
        if (!sectionId && index !== null) {
            if (index > 0 && items[index-1]) {
                const prevItem = items[index - 1];
                sectionId = prevItem.section_id || (prevItem.type === 'section' ? prevItem.id : null);
            }
            else if (items.length > 0) {
                const firstItem = items[0];
                sectionId = firstItem.section_id || (firstItem.type === 'section' ? firstItem.id : null);
            }
        }
        const rowCount = rows.length;

        // Use virtual grid parsing to preserve rowspan and colspan
        const virtualGrid = [];
        let colCount = 0;
        
        rows.forEach((tr, rIdx) => {
            if (!virtualGrid[rIdx]) virtualGrid[rIdx] = [];
            let cIdx = 0;
            
            tr.querySelectorAll('td, th').forEach(cell => {
                while (virtualGrid[rIdx][cIdx]) cIdx++;
                
                const rs = parseInt(cell.getAttribute('rowspan')) || 1;
                const cs = parseInt(cell.getAttribute('colspan')) || 1;
                let cellHtml = sanitizePastedHtml(cell.innerHTML);
                
                const style = cell.style || {};
                const bgColor = cell.getAttribute('bgcolor') || style.backgroundColor || '';
                const textAlign = cell.getAttribute('align') || style.textAlign || '';
                const fontWeight = style.fontWeight || '';
                const fontStyle = style.fontStyle || '';

                virtualGrid[rIdx][cIdx] = { 
                    content: cellHtml, rs, cs, hidden: false,
                    backgroundColor: bgColor,
                    textAlign: textAlign,
                    fontWeight: fontWeight,
                    fontStyle: fontStyle
                };
                
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
                if (cIdx > colCount) colCount = cIdx;
            });
        });

        // Ensure all rows have equal columns length
        for (let r = 0; r < rowCount; r++) {
            if (!virtualGrid[r]) virtualGrid[r] = [];
            while (virtualGrid[r].length < colCount) {
                virtualGrid[r].push({ content: '', rs: 1, cs: 1, hidden: false });
            }
        }

        const id = 'blk_' + Date.now() + Math.random().toString(36).substr(2, 5);
        let columns = [];
        let rowHeights = [];
        for (let i = 0; i < colCount; i++) {
            let width = 'auto';
            const firstCell = rows[0] ? rows[0].querySelectorAll('td, th')[i] : null;
            if (firstCell) {
                width = firstCell.style.width || firstCell.getAttribute('width') || 'auto';
                if (width !== 'auto' && !width.includes('px') && !width.includes('%')) width += 'px';
            }
            columns.push({ label: 'Cột ' + (i + 1), type: 'text', width: width });
        }

        // Apply duplicate fields and clean up
        let data = [];
        virtualGrid.forEach((rowData, rIdx) => {
            let height = rows[rIdx] ? (rows[rIdx].style.height || rows[rIdx].getAttribute('height') || 'auto') : 'auto';
            if (height !== 'auto' && !height.includes('px')) height += 'px';
            rowHeights.push(height);

            const processedRow = rowData.map(cell => {
                let cellHtml = cell.content || '';
                if (typeof cellHtml === 'string' && window.duplicateFieldBadgesInHtml) {
                    cellHtml = window.duplicateFieldBadgesInHtml(cellHtml, id);
                }
                return {
                    content: cellHtml,
                    rs: cell.rs || 1,
                    cs: cell.cs || 1,
                    hidden: cell.hidden || false,
                    backgroundColor: cell.backgroundColor || '',
                    textAlign: cell.textAlign || '',
                    fontWeight: cell.fontWeight || '',
                    fontStyle: cell.fontStyle || ''
                };
            });
            data.push(processedRow);
        });

        items.splice(index, 0, { 
            id: id, 
            type: 'table', 
            section_id: sectionId,
            label: 'Bảng (Pasted)', 
            rows: rowCount, 
            cols: colCount,
            columns: columns, 
            data: data, 
            rowHeights: rowHeights, 
            borderMode: 'visible', 
            hideHeader: true
        });
    }

    function handleTablePaste(e) {
        const target = e.target.closest('td, th');
        if (!target) return;

        // Initialize name mapping for formula translation
        window.__ebmrPastedNameMapping = {};
        window.__ebmrPastedFormulaFields = [];

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

        // MULTI-CELL PASTE FOR SINGLE VALUE
        if (grid.length === 1 && grid[0].length === 1) {
            const selectedCells = document.querySelectorAll('.selected-cell');
            if (selectedCells.length > 1) {
                e.preventDefault();
                saveState();
                selectedCells.forEach(td => {
                    const blockItem = td.closest('.block-item');
                    if (!blockItem) return;
                    const item = items.find(i => i.id === blockItem.getAttribute('data-id'));
                    if (!item || item.type !== 'table') return;

                    const rStr = td.dataset.row;
                    const cStr = td.dataset.col;
                    if (rStr === undefined || cStr === undefined) return;
                    
                    const r = parseInt(rStr);
                    const c = parseInt(cStr);
                    
                    let cellObj = grid[0][0];
                    let cellContentToPaste = cellObj.content || cellObj;
                    if (typeof cellContentToPaste === 'string' && window.duplicateFieldBadgesInHtml) {
                        cellContentToPaste = window.duplicateFieldBadgesInHtml(cellContentToPaste, item.id);
                    }
                    
                    if (r === 0) {
                        item.columns[c].label = cellContentToPaste;
                    } else {
                        if (!item.data[r - 1][c]) {
                            item.data[r - 1][c] = { content: '', rs: 1, cs: 1, hidden: false };
                        }
                        item.data[r - 1][c].content = cellContentToPaste;
                        if (cellObj.backgroundColor) item.data[r - 1][c].backgroundColor = cellObj.backgroundColor;
                        if (cellObj.textAlign) item.data[r - 1][c].textAlign = cellObj.textAlign;
                        if (cellObj.fontWeight) item.data[r - 1][c].fontWeight = cellObj.fontWeight;
                        if (cellObj.fontStyle) item.data[r - 1][c].fontStyle = cellObj.fontStyle;
                    }
                    item.dirty = true;
                });
                renderBlocks();
                return;
            }
        }

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
                
                let cellContentToPaste = cellObj.content || cellObj;
                if (typeof cellContentToPaste === 'string' && window.duplicateFieldBadgesInHtml) {
                    cellContentToPaste = window.duplicateFieldBadgesInHtml(cellContentToPaste, item.id);
                }
                
                // cellObj is now an object: { content, rs, cs, hidden, backgroundColor, textAlign, ... }
                if (rIndex === 0) {
                    item.columns[cIndex].label = cellContentToPaste;
                } else {
                    item.data[rIndex - 1][cIndex] = {
                        content: cellContentToPaste,
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
            item.dirty = true;
            if (typeof window.translateFormulaReferencesOfPastedFields === 'function') {
                window.translateFormulaReferencesOfPastedFields();
            }
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
                if (window.isSelectVarMode) return; // Chặn clear selection khi đang bắt biến
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
                
                const fontSizeDisplay = document.getElementById('fontSizeDisplay');
                if (fontSizeDisplay) {
                    fontSizeDisplay.innerText = sizeInPt;
                }
            }
        }
    });

    /**
     * ======================================================
     * CRITERIA DATA-BINDING – Execution Mode Validation
     * ======================================================
     * Khi người dùng chuyển sang chế độ Chạy thử (Execution Mode),
     * các ô nhập liệu data-criteria-bind="RESULT" sẽ:
     *  1. Được kích hoạt (pointer-events: auto) để nhập được.
     *  2. Tự động bôi đỏ / xanh khi giá trị vượt / nằm trong tiêu chuẩn.
     */

    // Hook vào setDesignerMode để kích hoạt / vô hiệu hoá các result-input
    const _originalSetDesignerMode = window.setDesignerMode || setDesignerMode;
    window.setDesignerMode = function(isExecute) {
        _originalSetDesignerMode(isExecute);
        activateCriteriaInputs(isExecute);
    };

    function activateCriteriaInputs(isExecute) {
        // Tìm tất cả các input kết quả kiểm nghiệm đã được gắn thuộc tính
        const resultInputs = document.querySelectorAll('.result-input[data-criteria-bind="RESULT"]');
        resultInputs.forEach(function(inp) {
            if (isExecute) {
                // Execution mode: cho phép nhập liệu
                inp.style.pointerEvents = 'auto';
                inp.readOnly = false;
                inp.classList.remove('criteria-design-mode');
                inp.classList.add('criteria-exec-mode');
            } else {
                // Design mode: chỉ placeholder, không cho nhập
                inp.style.pointerEvents = 'none';
                inp.readOnly = true;
                inp.classList.remove('criteria-exec-mode');
                inp.classList.add('criteria-design-mode');
                // Reset về trạng thái chưa nhập
                inp.classList.remove('criteria-pass', 'criteria-fail');
                inp.style.backgroundColor = '';
                inp.style.color = '';
            }
        });
    }

    // Lắng nghe sự kiện input trực tiếp trên tài liệu (event delegation)
    document.addEventListener('input', function(e) {
        const inp = e.target;
        if (!inp.classList.contains('result-input')) return;
        if (!window.isExecutionMode) return;

        // Auto-save input to execution values if it has data-field-id
        const fieldId = inp.getAttribute('data-field-id');
        if (fieldId) {
            if (!window.executionValues) window.executionValues = {};
            window.executionValues[fieldId] = inp.value;
            if (typeof window.recalculateAllFormulas === 'function') {
                window.recalculateAllFormulas();
            }
        }

        const op  = inp.getAttribute('data-criteria-op')  || '';
        const minAttr = inp.getAttribute('data-criteria-min') || '';
        const maxAttr = inp.getAttribute('data-criteria-max') || '';

        const min = parseFloat(minAttr);
        const max = parseFloat(maxAttr);
        const val = parseFloat(inp.value);

        // Xoá trạng thái cũ
        inp.classList.remove('criteria-pass', 'criteria-fail');
        inp.style.backgroundColor = '';
        inp.style.color = '';
        inp.title = inp.getAttribute('title') || '';

        if (inp.value.trim() === '') return;

        let pass = false;
        let isNumeric = !isNaN(val) && !isNaN(parseFloat(minAttr)) && op !== 'N/A';

        if (isNumeric) {
            if (op === 'range') {
                pass = (val >= min && val <= max);
            } else if (op === '±') {
                // min = giá trị trung tâm, max = ± delta
                pass = (val >= (min - max) && val <= (min + max));
            } else if (op === '<') {
                pass = (val < min);
            } else if (op === '<=') {
                pass = (val <= min);
            } else if (op === '>') {
                pass = (val > min);
            } else if (op === '>=') {
                pass = (val >= min);
            } else if (op === '=' || op === '') {
                pass = (val === min);
            } else {
                // Không có toán tử rõ ràng: nếu có cả min và max thì kiểm tra range
                if (!isNaN(min) && !isNaN(max) && min !== max) {
                    pass = (val >= min && val <= max);
                } else if (!isNaN(min)) {
                    pass = (val === min);
                } else {
                    return; // Không đủ thông tin để kiểm tra
                }
            }
        } else {
            // Text validation (case-insensitive string comparison)
            const minStr = minAttr.trim().toLowerCase();
            const valStr = inp.value.trim().toLowerCase();
            if (minStr === '') {
                return; // Không đủ thông tin để kiểm tra
            }
            pass = (valStr === minStr);
        }

        if (pass) {
            inp.classList.add('criteria-pass');
            inp.style.backgroundColor = '#d4edda';
            inp.style.color = '#155724';
            inp.style.borderColor = '#28a745';
        } else {
            inp.classList.add('criteria-fail');
            inp.style.backgroundColor = '#f8d7da';
            inp.style.color = '#721c24';
            inp.style.borderColor = '#dc3545';
        }
    });
</script>
