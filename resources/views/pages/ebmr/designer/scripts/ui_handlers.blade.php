<script>
    function selectItem(id, doRender = true) {
        selectedId = id;
        if (doRender) renderBlocks();
        const item = items.find(i => i.id === id);
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
                <input type="text" class="form-control" value="${item.label}" oninput="updateItemProp('label', this.value)">
            </div>
        `;

        if (item.type === 'table') {
            html += `
                <div class="mb-3">
                    <label class="small fw-bold mb-2">Chế độ viền</label>
                    <select class="form-select form-select-sm mb-3" onchange="updateItemProp('borderMode', this.value)">
                        <option value="visible" ${item.borderMode === 'visible' ? 'selected' : ''}>Hiện viền</option>
                        <option value="dashed" ${item.borderMode === 'dashed' ? 'selected' : ''}>Mờ (Editor only)</option>
                        <option value="none" ${item.borderMode === 'none' ? 'selected' : ''}>Ẩn hoàn toàn</option>
                    </select>

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

        const item = {
            id: id,
            type: type,
            label: (type === 'static-text' ? 'Ghi chú' : 'Tiêu đề ' + type),
            content: defaultContent,
            columns: []
        };
        if (insertIndex !== null) items.splice(insertIndex, 0, item);
        else items.push(item);
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
        document.getElementById('property-panel').classList.add('d-none');
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
    }

    function updateStaticTextInline(id, val) {
        saveStateDebounced();
        const item = items.find(i => i.id === id);
        if (!item) return;

        // Auto-capitalize first visible character if it's currently a lowercase letter
        let processedVal = val.trim();
        // Match start of string or start of a tag like <h1>, then a lowercase letter
        const match = processedVal.match(/^(<[^>]+>)?([a-zà-ỹ])/);
        if (match) {
            const prefix = match[1] || '';
            const char = match[2];
            processedVal = prefix + char.toUpperCase() + processedVal.slice(prefix.length + 1);
        }

        item.content = (val === '<br>' || val.trim() === '') ? '' : (match ? processedVal : val);
        
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
        formatDoc('foreColor', color);
    };

    let savedTextSelection = null;
    function saveCurrentSelection() {
        const sel = window.getSelection();
        if (sel.rangeCount > 0) {
            savedTextSelection = sel.getRangeAt(0);
        }
    }

    function applyCustomFontSize(px) {
        if (!px) return;
        
        // Restore selection if it was lost when input gained focus
        if (savedTextSelection) {
            const sel = window.getSelection();
            sel.removeAllRanges();
            sel.addRange(savedTextSelection);
        }

        // Use standard execCommand to generate a font tag
        document.execCommand('fontSize', false, '7');
        
        // Find the generated tag and convert its size to precise px
        const fonts = document.querySelectorAll('font[size="7"]');
        for (let i = 0; i < fonts.length; i++) {
            const font = fonts[i];
            font.removeAttribute('size');
            font.style.fontSize = px + 'px';
            
            // Convert to span for better HTML standard compliance
            const span = document.createElement('span');
            span.style.fontSize = px + 'px';
            span.innerHTML = font.innerHTML;
            font.parentNode.replaceChild(span, font);
        }
        
        // Force an input trigger to save state
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

    function searchDoc() {
        const term = document.getElementById('searchBox').value;
        if (!term) return;
        const found = window.find(term, false, false, true, false, true, false);
        if (!found) {
            window.getSelection().removeAllRanges();
            window.find(term, false, false, true, false, true, false);
        }
    }

    function buildOutline() {
        const outlineContainer = document.getElementById('document-outline');
        if (!outlineContainer) return;
        
        const editor = document.getElementById('editor-content');
        if (!editor) return;
        
        const headings = editor.querySelectorAll('h1, h2, h3, h4');
        if (headings.length === 0) {
            outlineContainer.innerHTML = '<div class="outline-empty">Chưa có nội dung. Nhập văn bản và định dạng Tiêu đề (H1, H2,...) để tạo thẻ.</div>';
            return;
        }

        let html = '';
        let c1 = 0, c2 = 0, c3 = 0, c4 = 0;

        headings.forEach((h, index) => {
            if (!h.id) h.id = 'heading_' + index + '_' + Math.random().toString(36).substr(2, 9);
            const level = h.tagName.toLowerCase();
            const text = h.innerText.trim() || '[Tiêu đề trống]';
            
            let numberPrefix = "";
            if (level === 'h1') {
                c1++; c2 = 0; c3 = 0; c4 = 0;
                numberPrefix = c1 + ". ";
            } else if (level === 'h2') {
                c2++; c3 = 0; c4 = 0;
                numberPrefix = c1 + "." + c2 + " ";
            } else if (level === 'h3') {
                c3++; c4 = 0;
                numberPrefix = c1 + "." + c2 + "." + c3 + " ";
            } else if (level === 'h4') {
                c4++;
                numberPrefix = c1 + "." + c2 + "." + c3 + "." + c4 + " ";
            }

            html += `<a class="outline-item outline-${level}" onclick="document.getElementById('${h.id}').scrollIntoView({behavior: 'smooth', block: 'center'})" title="${numberPrefix}${text}">
                <span class="fw-bold me-1">${numberPrefix}</span> ${text}
            </a>`;
        });
        
        outlineContainer.innerHTML = html;
    }

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
        let leftPx = 48; // default page padding
        let rightPx = 48;

        if (item) {
            if (item.marginLeft) leftPx = 48 + parseFloat(item.marginLeft);
            if (item.marginRight) rightPx = 48 + parseFloat(item.marginRight);
        }

        markerLeft.style.left = leftPx + 'px';
        marginL.style.width = leftPx + 'px';
        markerRight.style.right = rightPx + 'px';
        marginR.style.width = rightPx + 'px';
    }
</script>
