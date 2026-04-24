<script>
    function selectItem(id, doRender = true) {
        selectedId = id;
        if (doRender) renderBlocks();
        if (window.isReadOnly) return; 

        const item = items.find(i => i.id === id);
        
        // Update active section context based on selection
        if (item) {
            const newActiveId = (item.type === 'section') ? item.id : item.section_id;
            if (newActiveId) {
                window.activeSectionId = newActiveId;
                // Update the section selector in toolbar to show which section is "Active"
                const selector = document.getElementById('section-filter');
                if (selector && window.isViewAllMode) {
                    // Temporarily remove listener if needed to prevent filtering
                    const originalVal = selector.value;
                    selector.value = newActiveId;
                }
            }
        }

        const panel = document.getElementById('property-panel');
        const body = document.getElementById('prop-body');

        if (!item) {
            if (panel) panel.classList.remove('d-none');
            // ... (keep common settings)
            return;
        }

        if (item.locked) {
            if (panel) panel.classList.remove('d-none');
            if (body) {
                body.innerHTML = `
                    <div class="text-center py-5">
                        <i class="fas fa-lock fa-3x text-muted mb-3"></i>
                        <h6 class="fw-bold">Khối đã bị khóa</h6>
                        <p class="small text-muted px-3">Khối này chứa thông tin tiêu đề hệ thống và không được phép chỉnh sửa hoặc xóa.</p>
                        <hr class="my-4">
                        <div class="text-start px-3">
                            <label class="small fw-bold text-muted text-uppercase mb-2">Thông tin khối</label>
                            <div class="mb-2 small"><strong>Loại:</strong> ${item.type}</div>
                            <div class="mb-2 small"><strong>Nhãn:</strong> ${item.label}</div>
                            <div class="mb-2 small"><strong>ID:</strong> ${item.id}</div>
                        </div>
                    </div>
                `;
            }
            return;
        }
        
        if (panel) panel.classList.remove('d-none');
        if (typeof updateRulerForCurrentBlock === 'function') updateRulerForCurrentBlock();

        let html = `
            <div class="mb-3 border-bottom pb-3">
                <label class="form-label small fw-bold text-muted text-uppercase mb-2">Đổ màu khối / Ô chọn</label>
                <div class="dropdown">
                    <button class="btn btn-outline-secondary btn-sm w-100 d-flex align-items-center justify-content-between" type="button" data-toggle="dropdown" aria-expanded="false">
                        <div class="d-flex align-items-center gap-2">
                            <div style="width: 16px; height: 16px; background-color: ${item.backgroundColor || '#ffffff'}; border: 1px solid #ccc; border-radius: 2px;"></div>
                            <span>Chọn màu nền...</span>
                        </div>
                        <i class="fas fa-caret-down"></i>
                    </button>
                    <div class="dropdown-menu p-2 shadow-sm" style="min-width: 250px;" onclick="event.stopPropagation()">
                        <div class="small fw-bold text-muted mb-2">Bảng màu chủ đề</div>
                        ${getThemeColorsHTML('updateBlockBackgroundWrapper')}
                        <hr class="my-2">
                        <div class="d-flex align-items-center gap-2">
                            <input type="color" class="form-control form-control-color p-1" style="height: 30px; width: 40px;" id="customBgColor" value="${item.backgroundColor || '#ffffff'}" onchange="updateBlockBackground('${item.id}', this.value)">
                            <label class="small text-muted mb-0" for="customBgColor">Màu tuỳ chỉnh...</label>
                        </div>
                        <button class="btn btn-light btn-sm w-100 mt-2 text-danger text-start" onclick="updateBlockBackground('${item.id}', '')"><i class="fas fa-eraser me-2"></i>Xoá màu nền</button>
                    </div>
                </div>
            </div>
            <div class="mb-3">
                <label class="small fw-bold">Nhãn hiển thị</label>
                <input type="text" class="form-control" value="${(item.label && item.label !== 'null') ? item.label : ''}" placeholder="Nhập nhãn hiển thị..." oninput="updateItemProp('label', this.value)">
            </div>
        `;

        if (item.type === 'table' || item.type === 'static-text') {
            html += `
                <div class="mb-3">
                    <label class="small fw-bold mb-2">Chế độ viền</label>
                    <select class="form-select form-select-sm mb-3" onchange="updateItemProp('borderMode', this.value)">
                        <option value="visible" ${item.borderMode === 'visible' ? 'selected' : ''}>Hiện viền</option>
                        <option value="dashed" ${item.borderMode === 'dashed' ? 'selected' : ''}>Mờ (Editor only)</option>
                        <option value="none" ${item.borderMode === 'none' ? 'selected' : ''}>Ẩn hoàn toàn</option>
                    </select>
                </div>
            `;
        }

        if (item.type === 'table') {
            html += `
                <div class="mb-3">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="hideHeaderCheck" ${!item.hideHeader ? 'checked' : ''} onchange="updateItemProp('hideHeader', !this.checked)">
                        <label class="form-check-label small fw-bold" for="hideHeaderCheck">Hiển thị hàng tiêu đề</label>
                    </div>

                    <label class="small fw-bold mb-2">Công cụ Bảng (${item.cols}x${item.rows})</label>
                    <div class="alert alert-info py-1 px-2 small mb-2" style="font-size: 0.7rem;">
                        Đang chọn: Hàng ${activeRowIdx === 0 ? 'Tiêu đề' : activeRowIdx}, Cột ${activeColIdx + 1}
                    </div>
                    
                    <div class="d-grid gap-2">
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-secondary" onclick="modifyTable('addRow', 'up')" title="Chèn hàng phía trên"><i class="fas fa-arrow-up"></i> Chèn trên</button>
                            <button class="btn btn-outline-secondary" onclick="modifyTable('addRow', 'down')" title="Chèn hàng phía dưới"><i class="fas fa-arrow-down"></i> Chèn dưới</button>
                        </div>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-secondary" onclick="modifyTable('addCol', 'left')" title="Chèn cột bên trái"><i class="fas fa-arrow-left"></i> Chèn trái</button>
                            <button class="btn btn-outline-secondary" onclick="modifyTable('addCol', 'right')" title="Chèn cột bên phải"><i class="fas fa-arrow-right"></i> Chèn phải</button>
                        </div>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-danger" onclick="modifyTable('deleteRow')" title="Xóa hàng đang chọn"><i class="fas fa-trash-alt"></i> Xóa hàng</button>
                            <button class="btn btn-outline-danger" onclick="modifyTable('deleteCol')" title="Xóa cột đang chọn"><i class="fas fa-trash-alt"></i> Xóa cột</button>
                        </div>
                        <button class="btn btn-info btn-sm w-100 mt-2" onclick="openChartCreator('${item.id}')">
                            <i class="fas fa-chart-line me-1"></i> Tạo biểu đồ từ bảng này
                        </button>
                    </div>

                    <label class="small fw-bold mt-3 mb-2">Ô đã chọn</label>
                    <div class="btn-group btn-group-sm w-100">
                        <button class="btn btn-outline-primary" id="mergeBtn" onclick="mergeSelectedCells()" title="Gộp các ô đã quét"><i class="fas fa-object-group"></i> Gộp ô</button>
                        <button class="btn btn-outline-primary" id="splitBtn" onclick="openSplitModal()" title="Tách ô chuyên sâu"><i class="fas fa-columns"></i> Tách ô</button>
                    </div>

                    <label class="small fw-bold mt-3 mb-2">Kích thước ô</label>
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text"><i class="fas fa-arrows-alt-v"></i></span>
                                <input type="text" class="form-control" id="manualHeight" placeholder="H" onchange="updateManualSize('height', this.value)">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text"><i class="fas fa-arrows-alt-h"></i></span>
                                <input type="text" class="form-control" id="manualWidth" placeholder="W" onchange="updateManualSize('width', this.value)">
                            </div>
                        </div>
                    </div>
                </div>

                <hr class="my-3">
                <div class="mb-3">
                    <label class="small fw-bold text-muted text-uppercase mb-2"><i class="fas fa-robot me-1"></i>Dịch thuật AI</label>
                    <div class="alert alert-light border py-2 mb-2 small" style="font-size: 0.75rem; background-color: #f8fafc;">
                        Cập nhật nội dung Tiếng Anh dựa trên bản Tiếng Việt hiện tại của khối này.
                    </div>
                    <button class="btn btn-primary btn-sm w-100 mb-2" onclick="translateBlockWithAI('${item.id}', true)">
                        <i class="fas fa-language me-1"></i> Dịch lại toàn bộ bảng
                    </button>
                    ${(activeRowIdx > 0 && activeColIdx >= 0) ? `
                        <button class="btn btn-outline-primary btn-sm w-100" onclick="translateBlockWithAI('${item.id}', false)">
                            <i class="fas fa-magic me-1"></i> Dịch lại ô đang chọn
                        </button>
                    ` : ''}
                </div>
            `;
        } else if (item.type === 'static-text') {
            html += `
                <hr class="my-3">
                <div class="mb-3">
                    <label class="small fw-bold text-muted text-uppercase mb-2"><i class="fas fa-robot me-1"></i>Dịch thuật AI</label>
                    <button class="btn btn-primary btn-sm w-100" onclick="translateBlockWithAI('${item.id}', true)">
                        <i class="fas fa-language me-1"></i> Dịch lại khối này
                    </button>
                </div>
            `;
        }
        body.innerHTML = html;

        // Initialize dynamic dropdowns for Bootstrap 4
        if (window.jQuery) {
            $(body).find('.dropdown-toggle').dropdown();
        }

        if (item && item.type === 'table') {
            const hInput = document.getElementById('manualHeight');
            const wInput = document.getElementById('manualWidth');
            if (hInput) hInput.value = (item.rowHeights && item.rowHeights[activeRowIdx-1]) ? item.rowHeights[activeRowIdx-1] : 'auto';
            if (wInput) wInput.value = (item.columns && item.columns[activeColIdx]) ? item.columns[activeColIdx].width : 'auto';
        }
    }

    function addItem(type, insertIndex = null, initialTag = null) {
        if (type === 'table') return;
        const hint = document.getElementById('drop-hint');
        if (hint) hint.classList.add('d-none');
        
        const id = 'blk_' + Date.now();
        let defaultContent = '';
        if (initialTag) {
            defaultContent = `<${initialTag}></${initialTag}>`;
        }

        // Determine section_id for the new item
        let sectionId = window.activeSectionId || null;
        if (!sectionId && insertIndex !== null) {
            if (insertIndex > 0) {
                // Add to end of previous item's section
                sectionId = items[insertIndex - 1].section_id;
            } else if (items.length > 0) {
                // Add to start of first item's section
                sectionId = items[0].section_id;
            }
        } else if (!sectionId && items.length > 0) {
            sectionId = items[items.length - 1].section_id;
        }

        const item = {
            id: id,
            type: type,
            section_id: sectionId,
            label: (type === 'static-text' ? 'Ghi chú' : 'Tiêu đề ' + type),
            content: defaultContent,
            columns: [],
            borderMode: type === 'static-text' ? 'none' : 'visible'
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

    function addSection() {
        const id = 'blk_section_' + Date.now();
        
        // When adding a new section block, we should ideally know its stage code
        // For now, it will inherit the current section until saved/configured
        const item = {
            id: id,
            type: 'section',
            section_id: window.activeSectionId || (items.length > 0 ? items[items.length - 1].section_id : null),
            label: 'Tên phân đoạn (VD: Pha chế, Đóng gói...)',
            content: '',
            locked: false
        };
        items.push(item);
        renderBlocks();
        selectItem(id);
    }

    function updateItemProp(prop, value) {
        const item = items.find(i => i.id === selectedId);
        if (!item) return;
        saveStateDebounced();
        item[prop] = value;
        renderBlocks();
    }

    function removeItem(id) {
        const item = items.find(i => i.id === id);
        if (item && item.locked) {
            Swal.fire('Thất bại', 'Không thể xóa khối đã bị khóa!', 'error');
            return;
        }
        saveState();
        items = items.filter(i => i.id !== id);
        selectedId = null;
        if (!isSidebarMinimized) {
            document.getElementById('property-panel').classList.add('d-none');
        }
        renderBlocks();
    }

    function moveItem(idx, dir) {
        saveState();
        const newIdx = idx + dir;
        if (newIdx < 0 || newIdx >= items.length) return;
        const temp = items[idx];
        items[idx] = items[newIdx];
        items[newIdx] = temp;
        renderBlocks();
    }

    function updateTableInline(id, type, r, c, val) {
        saveStateDebounced();
        const item = items.find(i => i.id === id);
        if (!item) return;
        if (type === 'col') item.columns[c].label = val;
        else if (type === 'cell') {
            if (!item.data[r][c] || typeof item.data[r][c] !== 'object') {
                item.data[r][c] = { content: val, rs: 1, cs: 1, hidden: false };
            } else {
                item.data[r][c].content = val;
            }
        }
        if (typeof syncLinkedCharts === 'function') syncLinkedCharts(id);
    }

    function updateStaticTextInline(id, val) {
        saveStateDebounced();
        const item = items.find(i => i.id === id);
        if (!item) return;

        // Auto-capitalize first visible character and trim leading/trailing whitespace
        let processedVal = val.trim();
        const match = processedVal.match(/^(<[^>]+>)?([a-zà-ỹ])/);
        if (match) {
            const prefix = match[1] || '';
            const char = match[2];
            processedVal = prefix + char.toUpperCase() + processedVal.slice(prefix.length + 1);
        }

        item.content = (processedVal === '<br>' || processedVal === '') ? '' : processedVal;
        
        // Debounce outline rebuild while typing
        if (window.outlineTimeout) clearTimeout(window.outlineTimeout);
        window.outlineTimeout = setTimeout(() => {
            buildOutline();
        }, 800);
    }

    window.handleAutoCapitalize = function(el) {
        // Real-time capitalization of the first letter as you type
        const selection = window.getSelection();
        if (!selection.rangeCount) return;
        
        const range = selection.getRangeAt(0);
        const node = range.startContainer;
        
        // If we're at the very beginning of the editable area
        if (node.nodeType === Node.TEXT_NODE && range.startOffset === 1 && node.textContent.length === 1) {
            const text = node.textContent;
            if (/^[a-zà-ỹ]/.test(text)) {
                node.textContent = text.toUpperCase();
                range.setStart(node, 1);
                range.setEnd(node, 1);
                selection.removeAllRanges();
                selection.addRange(range);
            }
        }
    };

    function formatDoc(command, value = null) {
        document.execCommand(command, false, value);
        saveStateDebounced();
    }

    // Smart Paste Handler to clean up unwanted line breaks from PDF/Word
    document.addEventListener('paste', function(e) {
        const target = e.target.closest('.static-text-display, .mini-table td');
        if (!target) return;

        e.preventDefault();
        
        // Get plain text from clipboard
        let text = (e.clipboardData || window.clipboardData).getData('text');
        
        // Logic: Replace single newlines with a space (reflow), but keep double newlines (paragraphs)
        // 1. Normalize line endings
        text = text.replace(/\r\n/g, '\n');
        // 2. Protect double newlines (paragraphs) by temporarily replacing them with a unique marker
        text = text.replace(/\n\n+/g, '[[PARAGRAPH_BREAK]]');
        // 3. Replace remaining single newlines (the unwanted ones) with a space
        text = text.replace(/\n/g, ' ');
        // 4. Restore paragraph breaks
        text = text.replace(/\[\[PARAGRAPH_BREAK\]\]/g, '\n\n');
        // 5. Clean up multiple spaces
        text = text.replace(/[ ]+/g, ' ');

        // Insert the cleaned text
        document.execCommand("insertText", false, text.trim());
    });

    function getThemeColorsHTML(callbackName) {
        const colors = [
            '#000000', '#434343', '#666666', '#999999', '#b7b7b7', '#cccccc', '#d9d9d9', '#efefef', '#f3f3f3', '#ffffff',
            '#980000', '#ff0000', '#ff9900', '#ffff00', '#00ff00', '#00ffff', '#4a86e8', '#0000ff', '#9900ff', '#ff00ff',
            '#e6b8af', '#f4cccc', '#fce5cd', '#fff2cc', '#d9ead3', '#d0e0e3', '#c9daf8', '#cfe2f3', '#d9d2e9', '#ead1dc',
            '#dd7e6b', '#ea9999', '#f9cb9c', '#ffe599', '#b6d7a8', '#a2c4c9', '#a4c2f4', '#9fc5e8', '#b4a7d6', '#d5a6bd',
            '#cc4125', '#e06666', '#f6b26b', '#ffd966', '#93c47d', '#76a5af', '#6d9eeb', '#6fa8dc', '#8e7cc3', '#c27ba0',
            '#a61c00', '#cc0000', '#e69138', '#f1c232', '#6aa84f', '#45818e', '#3c78d8', '#3d85c6', '#674ea7', '#a64d79',
            '#85200c', '#990000', '#b45f06', '#bf9000', '#38761d', '#134f5c', '#1155cc', '#0b5394', '#351c75', '#741b47',
            '#5b0f00', '#660000', '#783f04', '#7f6000', '#274e13', '#0c343d', '#1c4587', '#073763', '#20124d', '#4c1130'
        ];
        
        let html = '<div class="d-flex flex-wrap gap-1" style="width: 240px; justify-content: space-between;">';
        colors.forEach(c => {
            const isLight = c === '#ffffff' || c === '#efefef' || c === '#f3f3f3';
            const cls = isLight ? 'color-swatch light-color' : 'color-swatch';
            html += `<div class="${cls}" style="background-color: ${c};" onclick="${callbackName}('${c}')" onmousedown="event.preventDefault()"></div>`;
        });
        html += '</div>';
        return html;
    }

    window.currentTextColor = '#ff0000';
    window.applyCurrentTextColor = function() {
        formatDoc('foreColor', window.currentTextColor);
    };
    window.updateTextColorPicker = function(color) {
        window.currentTextColor = color;
        const indicator = document.getElementById('textColorIndicator');
        if (indicator) indicator.style.background = color;

        const selectedCells = document.querySelectorAll('.selected-cell');
        if (selectedCells.length > 0) {
            saveState();
            selectedCells.forEach(cell => {
                const rStr = cell.dataset.row;
                const cStr = cell.dataset.col;
                const r = parseInt(rStr);
                const c = parseInt(cStr);
                
                const table = cell.closest('.mini-table');
                const blockItem = table ? table.closest('.block-item') : null;
                const itemId = blockItem ? blockItem.getAttribute('data-id') : null;
                const item = items.find(i => i.id === itemId);

                if (item) {
                    if (r === 0) {
                        if (item.columns && item.columns[c]) {
                            if (!item.columns[c].style) item.columns[c].style = {};
                            item.columns[c].style.textColor = color;
                        }
                    } else {
                        const rIdx = r - 1;
                        if (item.data && item.data[rIdx] && item.data[rIdx][c]) {
                            if (typeof item.data[rIdx][c] !== 'object') {
                                item.data[rIdx][c] = { content: item.data[rIdx][c], rs:1, cs:1, hidden:false };
                            }
                            item.data[rIdx][c].textColor = color;
                        }
                    }
                }
                // Direct DOM update to keep selection
                cell.style.color = color;
            });
            saveStateDebounced();
            return;
        }

        formatDoc('foreColor', color);
    };

    let savedTextSelection = null;
    function saveCurrentSelection() {
        const sel = window.getSelection();
        if (sel.rangeCount > 0) {
            savedTextSelection = sel.getRangeAt(0);
        }
    }

    function changeFontSize(delta) {
        const display = document.getElementById('fontSizeDisplay');
        if (!display) return;
        let current = parseInt(display.innerText) || 16;
        let next = current + delta;
        if (next < 8) next = 8;
        if (next > 72) next = 72;
        applyCustomFontSize(next);
    }

    function applyCustomFontSize(pt) {
        if (!pt) return;
        
        const display = document.getElementById('fontSizeDisplay');
        if (display) display.innerText = pt;

        const selectedCells = document.querySelectorAll('.selected-cell');
        if (selectedCells.length > 0) {
            saveState();
            selectedCells.forEach(cell => {
                const rStr = cell.dataset.row;
                const cStr = cell.dataset.col;
                const r = parseInt(rStr);
                const c = parseInt(cStr);
                
                const table = cell.closest('.mini-table');
                const blockItem = table ? table.closest('.block-item') : null;
                const itemId = blockItem ? blockItem.getAttribute('data-id') : null;
                const item = items.find(i => i.id === itemId);

                if (item) {
                    if (r === 0) {
                        if (item.columns && item.columns[c]) {
                            if (!item.columns[c].style) item.columns[c].style = {};
                            item.columns[c].style.fontSize = pt + 'pt';
                        }
                    } else {
                        const rIdx = r - 1;
                        if (item.data && item.data[rIdx] && item.data[rIdx][c]) {
                            if (typeof item.data[rIdx][c] !== 'object') {
                                item.data[rIdx][c] = { content: item.data[rIdx][c], rs:1, cs:1, hidden:false };
                            }
                            item.data[rIdx][c].fontSize = pt + 'pt';
                        }
                    }
                }
            });
            selectedCells.forEach(cell => {
                cell.style.fontSize = pt + 'pt';
            });
            saveStateDebounced();
            return;
        }

        if (savedTextSelection) {
            const sel = window.getSelection();
            sel.removeAllRanges();
            sel.addRange(savedTextSelection);
        }

        document.execCommand('fontSize', false, '7');
        
        const fonts = document.querySelectorAll('font[size="7"]');
        for (let i = 0; i < fonts.length; i++) {
            const font = fonts[i];
            font.removeAttribute('size');
            font.style.fontSize = pt + 'pt';
        }
        
        if (typeof saveStateDebounced === 'function') saveStateDebounced();
    }

    function uploadImageBase64(inputElement) {
        const file = inputElement.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = function(e) {
            formatDoc('insertImage', e.target.result);
            inputElement.value = '';
        };
        reader.readAsDataURL(file);
    }

    window.openSearchModal = function(isReplace = false) {
        const sel = window.getSelection();
        const selectedText = sel.toString().trim();
        
        const findInput = document.getElementById('findInput');
        if (selectedText) {
            findInput.value = selectedText;
        }

        // Show modal (Bootstrap 4 jQuery style)
        $('#searchReplaceModal').modal('show');

        // Switch to correct tab
        if (isReplace) {
            $('#replace-tab').tab('show');
        } else {
            $('#find-tab').tab('show');
        }

        setTimeout(() => findInput.focus(), 500);
        $('#searchStats').text('');
    };

    window.executeSearch = function(silent = false) {
        const term = document.getElementById('findInput').value;
        if (!term) return;
        
        // Use window.find(aString, aCaseSensitive, aBackwards, aWrapAround, aWholeWord, aSearchInFrames, aShowDialog);
        const found = window.find(term, false, false, true, false, true, false);
        
        if (!found) {
            // Reset to top and search again
            window.getSelection().removeAllRanges();
            const foundAgain = window.find(term, false, false, true, false, true, false);
            if (!foundAgain && !silent) {
                toastr.warning('Không tìm thấy nội dung: "' + term + '"');
                $('#searchStats').text('Không tìm thấy kết quả nào.');
            } else if (foundAgain) {
                $('#searchStats').text('Đã quay lại đầu trang.');
            }
        } else {
            $('#searchStats').text('Đã tìm thấy.');
        }
    };

    window.executeReplace = function() {
        const findTerm = document.getElementById('findInput').value;
        const replaceTerm = document.getElementById('replaceInput').value;
        if (!findTerm) return;

        const sel = window.getSelection();
        const selectedText = sel.toString().trim().toLowerCase();
        
        if (selectedText === findTerm.trim().toLowerCase()) {
            document.execCommand('insertText', false, replaceTerm);
            saveStateDebounced();
            executeSearch(true); // Find next
        } else {
            executeSearch(false); // Find current/first
        }
    };

    window.executeReplaceAll = function() {
        const findTerm = document.getElementById('findInput').value;
        const replaceTerm = document.getElementById('replaceInput').value;
        if (!findTerm) return;

        saveState();
        let count = 0;
        window.getSelection().removeAllRanges();
        
        // Move to start
        const editor = document.getElementById('editor-content');
        const range = document.createRange();
        range.setStart(editor, 0);
        range.collapse(true);
        window.getSelection().addRange(range);

        while (window.find(findTerm, false, false, true, false, true, false)) {
            document.execCommand('insertText', false, replaceTerm);
            count++;
            // Prevent infinite loop if something goes wrong
            if (count > 1000) break;
        }
        
        if (count > 0) {
            saveStateDebounced();
            toastr.success(`Đã thay thế ${count} vị trí`);
            $('#searchStats').text(`Đã thay thế ${count} vị trí.`);
        } else {
            toastr.info('Không tìm thấy nội dung để thay thế.');
            $('#searchStats').text('Không tìm thấy kết quả nào.');
        }
    };


    window.updateBlockBackgroundWrapper = function(color) {
        if (!selectedId) return;
        updateBlockBackground(selectedId, color);
        // Bootstrap 4 style close
        $('.dropdown-toggle').dropdown('hide');
    };

    window.updateBlockBackground = function(id, color) {
        const item = items.find(i => i.id === id);
        if (!item) return;

        if (item.type === 'table') {
            const cells = document.querySelectorAll('.selected-cell');
            if (cells.length > 0) {
                cells.forEach(c => {
                    const r = parseInt(c.dataset.row) - 1; 
                    const col = parseInt(c.dataset.col);
                    
                    if (r === -1) {
                        if (!item.columns[col].style) item.columns[col].style = {};
                        item.columns[col].style.backgroundColor = color;
                        c.style.backgroundColor = color;
                    } else {
                        if (!item.data[r][col] || typeof item.data[r][col] !== 'object') {
                            item.data[r][col] = { content: item.data[r][col] || '', rs: 1, cs: 1, hidden: false };
                        }
                        item.data[r][col].backgroundColor = color;
                        c.style.backgroundColor = color;
                    }
                });
                saveStateDebounced();
                return;
            }
        }

        item.backgroundColor = color;
        const blockEl = document.querySelector(`.block-item.active`);
        if (blockEl) {
            blockEl.style.backgroundColor = color;
        }
        saveStateDebounced();
    };

    let isDraggingRuler = false;
    let currentMarker = null;
    let startX = 0;
    let startLeft = 0;
    let rulerWidth = 0;

    function initRuler() {
        const ruler = document.getElementById('editor-ruler');
        const markerLeft = document.getElementById('ruler-marker-left');
        const markerRight = document.getElementById('ruler-marker-right');
        const marginL = document.getElementById('ruler-margin-left');
        const marginR = document.getElementById('ruler-margin-right');
        
        if (!ruler || !markerLeft || !markerRight) return;

        function onMouseDown(e, type) {
            if (!selectedId) {
                alert("Vui lòng chọn hoặc click vào một đoạn văn/bảng trên giấy trước khi chỉnh lề!");
                return;
            }
            isDraggingRuler = true;
            currentMarker = type;
            startX = e.clientX;
            rulerWidth = ruler.offsetWidth;
            
            if (type === 'left') {
                startLeft = parseFloat(markerLeft.style.left) || 48;
            } else {
                startLeft = parseFloat(markerRight.style.right) || 48;
            }
            document.body.style.cursor = 'ew-resize';
            e.preventDefault();
        }

        markerLeft.addEventListener('mousedown', (e) => onMouseDown(e, 'left'));
        markerRight.addEventListener('mousedown', (e) => onMouseDown(e, 'right'));

        document.addEventListener('mousemove', (e) => {
            if (!isDraggingRuler) return;
            
            const deltaX = e.clientX - startX;
            const activeBlock = document.querySelector('.block-item.active');
            if (!activeBlock) return;

            let newPos;
            if (currentMarker === 'left') {
                newPos = startLeft + deltaX;
                if (newPos < 0) newPos = 0;
                if (newPos > rulerWidth / 2) newPos = rulerWidth / 2;
                
                markerLeft.style.left = newPos + 'px';
                marginL.style.width = newPos + 'px';
                activeBlock.style.marginLeft = (newPos - 48) + 'px';
            } else {
                newPos = startLeft - deltaX;
                if (newPos < 0) newPos = 0;
                if (newPos > rulerWidth / 2) newPos = rulerWidth / 2;
                
                markerRight.style.right = newPos + 'px';
                marginR.style.width = newPos + 'px';
                activeBlock.style.marginRight = (newPos - 48) + 'px';
            }
        });

        document.addEventListener('mouseup', () => {
            if (isDraggingRuler) {
                isDraggingRuler = false;
                document.body.style.cursor = 'default';
                
                if (selectedId) {
                    const item = items.find(i => i.id === selectedId);
                    if (item) {
                        const activeBlock = document.querySelector('.block-item.active');
                        if (activeBlock) {
                            item.marginLeft = activeBlock.style.marginLeft || '0px';
                            item.marginRight = activeBlock.style.marginRight || '0px';
                            saveStateDebounced();
                        }
                    }
                }
            }
        });
    }

    function updateRulerForCurrentBlock() {
        const markerLeft = document.getElementById('ruler-marker-left');
        const markerRight = document.getElementById('ruler-marker-right');
        const marginL = document.getElementById('ruler-margin-left');
        const marginR = document.getElementById('ruler-margin-right');
        
        if (!markerLeft) return;
        
        const item = items.find(i => i.id === selectedId);
        let leftPx = 40; // matching padding 40px
        let rightPx = 40;

        if (item) {
            if (item.marginLeft) leftPx = 40 + parseFloat(item.marginLeft);
            if (item.marginRight) rightPx = 40 + parseFloat(item.marginRight);
        }

        markerLeft.style.left = leftPx + 'px';
        marginL.style.width = leftPx + 'px';
        markerRight.style.right = rightPx + 'px';
        marginR.style.width = rightPx + 'px';
    }

    let isOutlineMinimized = false;
    let isSidebarMinimized = true;

    window.toggleOutline = function(minimize) {
        isOutlineMinimized = minimize;
        const col = document.getElementById('outline-col');
        const content = col.querySelector('.outline-sidebar');
        const minimized = document.getElementById('outline-minimized');

        if (minimize) {
            col.className = 'col-lg-1 transition-all p-0';
            content.classList.add('d-none');
            minimized.classList.remove('d-none');
            updateCanvasWidth();
        } else {
            col.className = 'col-lg-2 transition-all';
            content.classList.remove('d-none');
            minimized.classList.add('d-none');
            updateCanvasWidth();
        }
    };

    window.toggleSidebar = function(minimize) {
        isSidebarMinimized = minimize;
        const col = document.getElementById('sidebar-col');
        const minimized = document.getElementById('sidebar-minimized');
        const panel = document.getElementById('property-panel');
        const full = document.getElementById('sidebar-full');

        if (minimize) {
            if (col) col.className = 'col-lg-1 transition-all p-0';
            if (full) full.classList.add('d-none');
            if (minimized) minimized.classList.remove('d-none');
            if (panel) {
                panel.classList.remove('card', 'shadow-sm');
                panel.classList.add('bg-transparent', 'shadow-none', 'border-0');
                panel.style.boxShadow = 'none';
            }
            updateCanvasWidth();
        } else {
            if (col) col.className = 'col-lg-3 transition-all';
            if (full) full.classList.remove('d-none');
            if (minimized) minimized.classList.add('d-none');
            if (panel) {
                panel.classList.add('card', 'shadow-sm');
                panel.classList.remove('bg-transparent', 'shadow-none', 'border-0');
                if (selectedId || selectedFieldId) panel.classList.remove('d-none');
                else panel.classList.add('d-none');
            }
            updateCanvasWidth();
        }
    };

    function updateCanvasWidth() {
        const canvas = document.getElementById('canvas-col');
        
        if (isOutlineMinimized && isSidebarMinimized) {
            canvas.className = 'col-lg-10 transition-all';
        } else if (isOutlineMinimized) {
            canvas.className = 'col-lg-8 transition-all';
        } else if (isSidebarMinimized) {
            canvas.className = 'col-lg-9 transition-all';
        } else {
            canvas.className = 'col-lg-7 transition-all';
        }
    }

    // Smart Signature Handler
    function handleSignatureClick() {
        let targetRange = null;
        
        // Try saved text selection first (if we have one)
        if (savedTextSelection) {
            targetRange = savedTextSelection;
        } else {
            // Try native selection
            const sel = window.getSelection();
            if (sel && sel.rangeCount > 0) {
                targetRange = sel.getRangeAt(0);
            }
        }
        
        if (targetRange) {
            let node = targetRange.startContainer;
            if (node.nodeType === 3) node = node.parentNode;
            
            if (node && node.closest && node.closest('[contenteditable="true"]')) {
                // If cursor is inside a table cell or a text block, insert an inline signature tag instead of a block
                insertDynamicField('signature');
                return;
            }
        }
        
        // Default behavior: create a new Signature Block at the root level
        if (typeof addItem === 'function') {
            addItem('signature');
        }
    }

    // Dynamic Fields Data Handling

    function selectField(event, fieldId) {
        if (event) event.stopPropagation(); // Prevents triggering block selection
        
        selectedFieldId = fieldId;
        selectedId = null; // Clear block selection
        
        // Remove active class from blocks
        document.querySelectorAll('.block-item').forEach(el => el.classList.remove('active'));
        
        const field = fieldsConfig[fieldId];
        if (!field) return;

        // Ensure validation object exists to prevent crashes
        if (!field.validation) {
            field.validation = { required: false, min: null, max: null, decimal_places: null };
        }

        const panel = document.getElementById('property-panel');
        const body = document.getElementById('prop-body');
        
        if (panel) {
            panel.classList.remove('d-none');
            // If sidebar is hidden, open it
            if (isSidebarMinimized) {
                toggleSidebar(false);
            }
        }

        let typeHtml = `
            <div class="mb-3">
                <label class="small fw-bold mb-2">Biến số Hệ thống</label>
                <div class="alert alert-warning py-2 mb-2 small">
                    <i class="fas fa-info-circle me-1"></i> Đây là trường dữ liệu động để thu thập thông tin trong quá trình sản xuất.
                </div>
            </div>
            <div class="mb-3">
                <label class="small fw-bold text-muted text-uppercase mb-2">Tên thẻ (Nhãn hiển thị)</label>
                <input type="text" class="form-control form-control-sm" value="${field.label || ''}" oninput="syncFieldConfig('${fieldId}', 'label', this.value)">
                <div class="form-text small" style="font-size: 0.7rem;">Hiển thị ngắn gọn cho người dùng. VD: Khối lượng (g).</div>
            </div>
            
            @if(session('user') && session('user')['userGroup'] === 'Admin')
            <div class="mb-3">
                <label class="small fw-bold text-muted text-uppercase mb-2 text-danger"><i class="fas fa-tools me-1"></i>Tên biến (Machine-readable)</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light border-end-0">@</span>
                    <input type="text" class="form-control border-start-0" value="${field.name || ''}" oninput="syncFieldConfig('${fieldId}', 'name', this.value)">
                </div>
                <div class="form-text small text-muted" style="font-size: 0.7rem;">Chỉ dành cho Admin. Hệ thống tự động tạo mã này theo cấu hình vị trí.</div>
            </div>
            @endif

            <hr class="my-3">

            <div class="mb-3">
                <label class="small fw-bold text-muted text-uppercase mb-2">Kiểu dữ liệu</label>
                <select class="form-select form-select-sm" onchange="syncFieldConfig('${fieldId}', 'type', this.value)">
                    <option value="text" ${field.type === 'text' ? 'selected' : ''}>✒️ Văn bản (Text)</option>
                    <option value="number" ${field.type === 'number' ? 'selected' : ''}>🔢 Số (Number)</option>
                    <option value="date" ${field.type === 'date' ? 'selected' : ''}>📅 Ngày tháng (Date)</option>
                    <option value="select" ${field.type === 'select' ? 'selected' : ''}>🔘 Khóa chọn (Dropdown)</option>
                    <option value="signature" ${field.type === 'signature' ? 'selected' : ''}>✍️ Chữ ký (Signature)</option>
                    <option value="checkbox" ${field.type === 'checkbox' ? 'selected' : ''}>☑️ Hộp kiểm tra (Checkbox)</option>
                </select>
            </div>
            
            <div class="mb-3">
                <div class="form-check form-switch ps-4 pt-1">
                    <input class="form-check-input ms-n4" type="checkbox" id="fieldRequired" ${field.validation.required ? 'checked' : ''} onchange="syncFieldConfig('${fieldId}', 'validation.required', this.checked)">
                    <label class="form-check-label small fw-bold" for="fieldRequired">Bắt buộc điền</label>
                </div>
            </div>
        `;

        if (field.type === 'number') {
            typeHtml += `
                <div class="card bg-light border-0 shadow-none mb-3">
                    <div class="card-body p-3">
                        <label class="small fw-bold mb-2"><i class="fas fa-balance-scale me-1"></i> Giới hạn giá trị</label>
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="small text-muted" style="font-size: 0.75em;">Tối thiểu (Min)</label>
                                <input type="number" class="form-control form-control-sm" placeholder="VD: 71.0" value="${field.validation.min !== null ? field.validation.min : ''}" oninput="syncFieldConfig('${fieldId}', 'validation.min', this.value)">
                            </div>
                            <div class="col-6">
                                <label class="small text-muted" style="font-size: 0.75em;">Tối đa (Max)</label>
                                <input type="number" class="form-control form-control-sm" placeholder="VD: 81.0" value="${field.validation.max !== null ? field.validation.max : ''}" oninput="syncFieldConfig('${fieldId}', 'validation.max', this.value)">
                            </div>
                        </div>
                        <div class="mt-2">
                            <label class="small text-muted" style="font-size: 0.75em;">Chữ số thập phân</label>
                            <input type="number" class="form-control form-control-sm" min="0" max="6" placeholder="Bỏ trống nếu là số nguyên" value="${field.validation.decimal_places !== null ? field.validation.decimal_places : ''}" oninput="syncFieldConfig('${fieldId}', 'validation.decimal_places', this.value)">
                        </div>
                    </div>
                </div>
            `;
        } else if (field.type === 'select') {
            typeHtml += `
                <div class="mb-3">
                    <label class="small fw-bold text-muted text-uppercase mb-2">Danh sách lựa chọn</label>
                    <textarea class="form-control form-control-sm" rows="3" placeholder="Ví dụ: Đạt, Tốt, Không đạt" oninput="syncFieldConfig('${fieldId}', 'options', this.value)">${(field.options || []).join(', ')}</textarea>
                    <div class="form-text small" style="font-size: 0.7rem;">Mỗi lựa chọn cách nhau bởi dấu phẩy (,).</div>
                </div>
            `;
        }
        
        typeHtml += `
            <div class="mt-4 text-center">
                <button class="btn btn-sm btn-outline-danger w-100" onclick="deleteDynamicField('${fieldId}')"><i class="fas fa-trash-alt me-1"></i> Xóa biến số</button>
            </div>
        `;

        body.innerHTML = typeHtml;
    }

    function syncFieldConfig(fieldId, path, value) {
        if (!fieldsConfig[fieldId]) return;
        
        let target = fieldsConfig[fieldId];
        const keys = path.split('.');
        const lastKey = keys.pop();
        
        // Traverse path to deeply update
        for (let key of keys) {
            if (!target[key]) target[key] = {};
            target = target[key];
        }

        // Type coercion
        if (value === '') value = null;
        else if (path.includes('min') || path.includes('max') || path.includes('decimal_places')) {
            value = value !== null ? Number(value) : null;
        } else if (path === 'options') {
            value = value ? value.split(',').map(s => s.trim()).filter(s => s) : [];
        }

        target[lastKey] = value;
        
        // If label changes, update the DOM badge immediately
        if (path === 'label') {
            const el = document.querySelector(`.ebmr-field-badge[data-field-id="${fieldId}"]`);
            if (el) el.innerHTML = `<i class="fas fa-edit me-1"></i> ${value || '[Trống]'}`;
        } else if (path === 'type') {
            selectField(null, fieldId); // Re-render panel
        }
        
        saveStateDebounced();
    }
    
    function deleteDynamicField(fieldId) {
        // Find which item/cell this field belongs to
        let found = false;
        items.forEach(item => {
            if (item.type === 'table' && item.data) {
                item.data.forEach((row, r) => {
                    row.forEach((cell, c) => {
                        if (cell.content && cell.content.includes(`data-field-id="${fieldId}"`)) {
                            cell.content = ''; // Clear cell content
                            found = true;
                        }
                    });
                });
            }
        });

        delete fieldsConfig[fieldId];
        renderBlocks(); // Re-render to show it's gone
        saveStateDebounced();
        document.getElementById('property-panel').classList.add('d-none');
    }

    // --- Linked Template (GF) Logic ---
    let allGfs = [];
    function openLinkGfModal() {
        if (window.bootstrap) {
            const modal = new bootstrap.Modal(document.getElementById('linkGfModal'));
            modal.show();
            fetchGfs();
        }
    }

    function fetchGfs() {
        const listLoading = document.getElementById('gfListLoading');
        const list = document.getElementById('gfList');
        listLoading.classList.remove('d-none');
        list.classList.add('d-none');

        // Reuse getTemplates but filter locally or create a specific endpoint. Since we just updated getTemplates to return type and caterogy_id, we can filter here.
        fetch('{{ route('pages.ebmr.getTemplates') }}')
            .then(res => res.json())
            .then(data => {
                allGfs = data.filter(t => t.type === 'GF');
                renderGfList(allGfs);
                listLoading.classList.add('d-none');
                list.classList.remove('d-none');
            });
    }

    function renderGfList(gfs) {
        const list = document.getElementById('gfList');
        if (gfs.length === 0) {
            list.innerHTML = '<div class="text-center py-4 text-muted">Không có biểu mẫu chung nào.</div>';
            return;
        }
        list.innerHTML = gfs.map(t => `
            <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3">
                <div>
                    <div class="fw-bold text-navy">${t.name}</div>
                    <div class="small text-muted">Cập nhật: ${new Date(t.updated_at).toLocaleString()}</div>
                </div>
                <button class="btn btn-sm btn-outline-primary rounded-pill px-3" onclick="insertLinkedGf(${t.id}, '${t.name.replace(/'/g, "\\'")}')">
                    <i class="fas fa-plus me-1"></i> Chèn
                </button>
            </div>
        `).join('');
    }

    function filterGfs(query) {
        const filtered = allGfs.filter(t => t.name.toLowerCase().includes(query.toLowerCase()));
        renderGfList(filtered);
    }

    function insertLinkedGf(templateId, templateName) {
        const hint = document.getElementById('drop-hint');
        if (hint) hint.classList.add('d-none');
        
        const id = 'blk_' + Date.now();
        const item = {
            id: id,
            type: 'linked-template',
            template_id: templateId,
            label: templateName,
            content: '',
            columns: [],
            borderMode: 'visible'
        };
        items.push(item);
        renderBlocks();
        
        const modalEl = document.getElementById('linkGfModal');
        if (modalEl && window.bootstrap) {
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();
        }
    }

    function toggleGfPreview(id) {
        const item = items.find(i => i.id === id);
        if (!item) return;
        item.showPreview = !item.showPreview;
        renderBlocks();
    }

    function fetchAndRenderGfPreview(blockId, templateId) {
        const container = document.getElementById(`preview-${blockId}`);
        if (!container) return;

        // Use a simple cache to avoid repeated network requests during re-renders if possible
        if (window.gfPreviewCache && window.gfPreviewCache[templateId]) {
            renderGfPreviewContent(container, window.gfPreviewCache[templateId]);
            return;
        }

        fetch(`/ebmr/templates/${templateId}/blocks`)
            .then(res => res.json())
            .then(blocks => {
                if (!window.gfPreviewCache) window.gfPreviewCache = {};
                window.gfPreviewCache[templateId] = blocks;
                renderGfPreviewContent(container, blocks);
            })
            .catch(err => {
                container.innerHTML = `<div class="text-danger small">Lỗi tải nội dung: ${err.message}</div>`;
            });
    }

    function renderGfPreviewContent(container, blocks) {
        if (!blocks || blocks.length === 0) {
            container.innerHTML = '<div class="text-muted small italic">Biểu mẫu này chưa có nội dung.</div>';
            return;
        }

        let html = '';
        blocks.forEach(b => {
            if (b.type === 'static-text') {
                html += `<div class="mb-2 p-1 border-bottom small">${b.content || ''}</div>`;
            } else if (b.type === 'table') {
                html += `<div class="mb-2 small">
                    <table class="table table-bordered table-sm m-0" style="font-size: 0.7rem;">
                        <thead><tr>${(b.columns || []).map(c => `<th>${c.label || ''}</th>`).join('')}</tr></thead>
                        <tbody>${(b.data || []).slice(0, 3).map(row => `<tr>${row.map(cell => `<td>${typeof cell === 'object' ? (cell.content || '') : (cell || '')}</td>`).join('')}</tr>`).join('')}${b.data.length > 3 ? '<tr><td colspan="100%" class="text-center">...</td></tr>' : ''}</tbody>
                    </table>
                </div>`;
            } else if (b.type === 'signature') {
                html += `<div class="mb-2 p-1 border rounded bg-light small text-muted"><i class="fas fa-signature me-1"></i> [Chữ ký: ${b.label || ''}]</div>`;
            } else {
                html += `<div class="mb-2 small text-muted">[Khối: ${b.type}]</div>`;
            }
        });
        container.innerHTML = html;
    }
    function toggleViewMode() {
        const currentSection = '{{ $activeSectionId ?? '' }}';
        const templateId = '{{ $template->id }}';
        
        if (currentSection) {
            // Currently in a section, toggle to VIEW ALL
            // Store current section so we can come back
            localStorage.setItem('ebmr_last_section_' + templateId, currentSection);
            window.location.href = '{{ route('pages.ebmr.designer', $template->id) }}';
        } else {
            // Currently in VIEW ALL, toggle back to LAST section or first one
            let lastSection = localStorage.getItem('ebmr_last_section_' + templateId);
            
            // If no last section, try to find the first section ID from the items
            if (!lastSection && typeof items !== 'undefined' && items.length > 0) {
                const firstBlock = items.find(i => i.section_id);
                if (firstBlock) lastSection = firstBlock.section_id;
            }
            
            if (lastSection) {
                window.location.href = '{{ route('pages.ebmr.designer', $template->id) }}?section=' + lastSection;
            } else {
                // Fallback: just reload or show alert
                Swal.fire('Thông báo', 'Không xác định được phân đoạn cuối cùng để quay lại.', 'info');
            }
        }
    }
    // --- Format Painter Logic ---
    let isFormatPainterActive = false;
    let storedFormat = null;

    function toggleFormatPainter() {
        if (isFormatPainterActive) {
            disableFormatPainter();
            return;
        }

        // 1. Get Style from current cursor position or selection
        const selection = window.getSelection();
        let targetEl = null;
        
        if (selection.rangeCount > 0) {
            // Even if no text is selected, anchorNode tells us where the cursor is
            targetEl = selection.anchorNode.nodeType === 3 ? selection.anchorNode.parentElement : selection.anchorNode;
        }

        if (targetEl && (targetEl.closest('[contenteditable="true"]') || targetEl.getAttribute('contenteditable') === 'true')) {
            const styles = window.getComputedStyle(targetEl);
            storedFormat = {
                type: 'text',
                bold: document.queryCommandState('bold'),
                italic: document.queryCommandState('italic'),
                underline: document.queryCommandState('underline'),
                fontSize: styles.fontSize,
                color: styles.color,
                fontWeight: styles.fontWeight,
                fontStyle: styles.fontStyle
            };
        } else if (selectedId) {
            const item = items.find(i => i.id === selectedId);
            if (item) {
                storedFormat = {
                    type: 'block',
                    backgroundColor: item.backgroundColor,
                    textAlign: item.textAlign,
                    fontSize: item.fontSize,
                    borderMode: item.borderMode
                };
            }
        }

        if (storedFormat) {
            enableFormatPainter();
        } else {
            Swal.fire('Thông báo', 'Đặt con trỏ vào văn bản hoặc chọn khối để sao chép định dạng', 'info');
        }
    }

    function enableFormatPainter() {
        isFormatPainterActive = true;
        const btn = document.getElementById('btn-format-painter');
        if (btn) {
            btn.style.backgroundColor = '#e8f0fe';
            btn.style.color = '#1a73e8';
        }
        document.body.style.cursor = 'copy'; // Visual indicator
        
        // Use a persistent listener for the next interaction
        // We listen for mouseup to detect the "highlight" (selection) completion
        document.addEventListener('mouseup', handlePainterMouseUp);
    }

    function disableFormatPainter() {
        isFormatPainterActive = false;
        const btn = document.getElementById('btn-format-painter');
        if (btn) {
            btn.style.backgroundColor = '';
            btn.style.color = '';
        }
        document.body.style.cursor = 'default';
        document.removeEventListener('mouseup', handlePainterMouseUp);
    }

    function handlePainterMouseUp(e) {
        if (!isFormatPainterActive || !storedFormat) return;
        
        // Ignore if clicking the painter button itself or toolbar
        if (e.target.closest('.editor-toolbar')) {
            if (!e.target.closest('#btn-format-painter')) disableFormatPainter();
            return;
        }

        const selection = window.getSelection();
        const selectedText = selection.toString().trim();

        if (storedFormat.type === 'text' && selectedText.length > 0) {
            // User has highlighted text, apply styles!
            
            // Note: execCommand is a bit temperamental with "forcing" styles.
            // We use a small delay to ensure the selection is finalized.
            setTimeout(() => {
                if (storedFormat.bold) document.execCommand('bold', false, null);
                if (storedFormat.italic) document.execCommand('italic', false, null);
                if (storedFormat.underline) document.execCommand('underline', false, null);
                
                // For font size and color, we apply them directly
                if (storedFormat.color) document.execCommand('foreColor', false, storedFormat.color);
                
                disableFormatPainter();
                saveStateDebounced();
            }, 10);
        } else if (storedFormat.type === 'block') {
            const block = e.target.closest('.block-item');
            if (block) {
                const id = block.dataset.id;
                const item = items.find(i => i.id === id);
                if (item) {
                    item.backgroundColor = storedFormat.backgroundColor;
                    item.textAlign = storedFormat.textAlign;
                    item.fontSize = storedFormat.fontSize;
                    item.borderMode = storedFormat.borderMode;
                    renderBlocks();
                    saveStateDebounced();
                    disableFormatPainter();
                }
            }
        }
    }

    function clearFormatting() {
        const selection = window.getSelection();
        if (selection.rangeCount > 0 && selection.toString().trim().length > 0) {
            document.execCommand('removeFormat', false, null);
        } else if (selectedId) {
            const item = items.find(i => i.id === selectedId);
            if (item) {
                item.backgroundColor = '#ffffff';
                item.textAlign = 'left';
                item.fontSize = '14pt';
                item.borderMode = 'visible';
                renderBlocks();
                saveStateDebounced();
            }
        }
    }

    async function translateBlockWithAI(blockId, isWholeBlock = true) {
        const item = items.find(i => i.id === blockId);
        if (!item) return;

        let contentId = null;
        if (!isWholeBlock && item.type === 'table') {
            const r = activeRowIdx - 1;
            const c = activeColIdx;
            if (item.data[r] && item.data[r][c]) {
                contentId = item.data[r][c].db_id;
            }
            if (!contentId) {
                Swal.fire('Lưu ý', 'Không tìm thấy dữ liệu gốc để dịch ô này. Vui lòng nhấn LƯU HỒ SƠ trước khi dịch.', 'warning');
                return;
            }
        } else {
            // For whole block, we pass blockId (db_id)
            if (!item.db_id) {
                Swal.fire('Lưu ý', 'Vui lòng nhấn LƯU HỒ SƠ trước khi thực hiện dịch bằng AI.', 'warning');
                return;
            }
        }

        const btn = event.currentTarget;
        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Đang dịch...';

        try {
            const response = await fetch("{{ route('pages.ebmr.aiTranslateSingle') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                },
                body: JSON.stringify({
                    content_id: contentId,
                    block_id: isWholeBlock ? item.db_id : null
                })
            });

            const res = await response.json();
            if (res.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Thành công',
                    text: `Đã dịch xong ${res.count} mục. Vui lòng chuyển sang chế độ Tiếng Anh để xem kết quả.`,
                    timer: 2000,
                    showConfirmButton: false
                });
                
                // If we are currently in English or Dual mode, we might want to refresh the UI
                // But since the DB updated, a reload or re-fetching might be needed.
                // For now, we just notify.
            } else {
                Swal.fire('Lỗi', res.message || 'Không thể dịch nội dung này.', 'error');
            }
        } catch (e) {
            Swal.fire('Lỗi', 'Lỗi kết nối AI.', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
    }
</script>
