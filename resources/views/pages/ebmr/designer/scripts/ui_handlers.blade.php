<script>
    // Auto-detect Review Mode from URL
    document.addEventListener('DOMContentLoaded', () => {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('mode') === 'review') {
            // Set to full view mode
            window.isViewAllMode = true;
            window.activeSectionId = null;

            // Switch to Trial Mode
            setDesignerMode(true);

            // Toast notification already handled in setDesignerMode
        }
    });

    /**
     * Chuyển đổi giữa chế độ Thiết kế và Chế độ Chạy thử.
     * @param {boolean} isExecute - true nếu chuyển sang chế độ chạy thử, false nếu về thiết kế.
     */
    function setDesignerMode(isExecute) {
        window.isExecutionMode = isExecute;

        // Cập nhật trạng thái nút bấm trên toolbar (nút Toggle duy nhất)
        const toggleBtn = document.getElementById('btn-mode-toggle');
        const mainContent = document.getElementById('mainContent');

        if (toggleBtn) {
            if (isExecute) {
                toggleBtn.classList.remove('btn-primary');
                toggleBtn.classList.add('btn-success');
                toggleBtn.innerHTML = '<i class="fas fa-play me-1"></i> <span id="mode-text">Chạy thử</span>';
                toggleBtn.title = "Chuyển sang Thiết kế (Ctrl + E)";
            } else {
                toggleBtn.classList.remove('btn-success');
                toggleBtn.classList.add('btn-primary');
                toggleBtn.innerHTML = '<i class="fas fa-edit me-1"></i> <span id="mode-text">Thiết kế</span>';
                toggleBtn.title = "Chuyển sang Chạy thử (Ctrl + E)";
            }
        }

        if (mainContent) {
            if (isExecute) mainContent.classList.add('execution-mode-active');
            else mainContent.classList.remove('execution-mode-active');
        }

        if (isExecute) {
            // Bỏ chọn khối hiện tại khi chạy thử
            selectedId = null;
        }

        // Render lại hồ sơ để áp dụng các thay đổi logic (ví dụ: ẩn nút thêm khối)
        renderBlocks();
        updateCanvasWidth();

        // Hiển thị thông báo nhanh
        if (typeof Swal !== 'undefined') {
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });

            Toast.fire({
                icon: isExecute ? 'success' : 'info',
                title: isExecute ? 'Đã chuyển sang chế độ Chạy thử' : 'Đã quay lại chế độ Thiết kế'
            });
        }
    }

    /**
     * Chọn một khối (block) để chỉnh sửa và hiển thị bảng thuộc tính.
     * Cách hoạt động: 
     * 1. Cập nhật ID khối đang chọn (selectedId).
     * 2. Tìm thông tin khối trong mảng dữ liệu `items`.
     * 3. Tự động xác định và chuyển đổi Phân đoạn (Section) đang làm việc dựa trên vị trí khối.
     * 4. Dựng giao diện bảng điều khiển (Property Panel) động dựa trên loại khối (Bảng, Văn bản...).
     * @param {string} id - ID của khối cần chọn.
     * @param {boolean} doRender - Có thực hiện render lại giao diện hay không.
     */
    function selectItem(id, doRender = true) {
        selectedId = id;
        if (doRender) renderBlocks();
        if (window.isReadOnly) return;

        const item = items.find(i => i.id === id);

        // Update active section context based on selection
        if (item) {
            const newActiveId = (item.type === 'section') ? (item.section_id || item.id) : item.section_id;
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
                    <div class="border-selector-grid d-flex flex-wrap gap-2">
                        <button class="btn btn-sm btn-light border ${item.borderMode === 'all' || !item.borderMode ? 'active border-primary' : ''}" 
                                onclick="setTableBorderMode('all')" title="Tất cả viền">
                            <i class="fas fa-border-all"></i>
                        </button>
                        <button class="btn btn-sm btn-light border ${item.borderMode === 'none' ? 'active border-primary' : ''}" 
                                onclick="setTableBorderMode('none')" title="Không viền">
                            <i class="fas fa-border-none"></i>
                        </button>
                        <button class="btn btn-sm btn-light border ${item.borderMode === 'outer' ? 'active border-primary' : ''}" 
                                onclick="setTableBorderMode('outer')" title="Viền ngoài">
                            <i class="fas fa-square"></i>
                        </button>
                        <button class="btn btn-sm btn-light border ${item.borderMode === 'rows' ? 'active border-primary' : ''}" 
                                onclick="setTableBorderMode('rows')" title="Chỉ viền ngang">
                            <i class="fas fa-stream"></i>
                        </button>
                        <button class="btn btn-sm btn-light border ${item.borderMode === 'cols' ? 'active border-primary' : ''}" 
                                onclick="setTableBorderMode('cols')" title="Chỉ viền dọc">
                            <i class="fas fa-columns"></i>
                        </button>
                         <button class="btn btn-sm btn-light border ${item.borderMode === 'dashed' ? 'active border-primary' : ''}" 
                                onclick="setTableBorderMode('dashed')" title="Viền nét đứt (Chỉ Editor)">
                            <i class="fas fa-grip-lines"></i>
                        </button>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="small fw-bold mb-2">Độ dày viền</label>
                    <select class="form-select form-select-sm" onchange="updateItemProp('borderWeight', this.value)">
                        <option value="1px" ${item.borderWeight === '1px' ? 'selected' : ''}>1px</option>
                        <option value="1.5px" ${item.borderWeight === '1.5px' ? 'selected' : ''}>1.5px</option>
                        <option value="2px" ${item.borderWeight === '2px' ? 'selected' : ''}>2px</option>
                        <option value="3px" ${item.borderWeight === '3px' ? 'selected' : ''}>3px</option>
                        <option value="4px" ${item.borderWeight === '4px' ? 'selected' : ''}>4px</option>
                    </select>
                </div>
            `;
        }

        if (item.type === 'table') {
            html += `
                <div class="mb-3">
                    <div class="form-check form-switch mb-1">
                        <input class="form-check-input" type="checkbox" id="hideHeaderCheck" ${!item.hideHeader ? 'checked' : ''} onchange="updateItemProp('hideHeader', !this.checked)">
                        <label class="form-check-label small fw-bold" for="hideHeaderCheck">Hiển thị hàng tiêu đề</label>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="canAddRowsCheck" ${item.canAddRows ? 'checked' : ''} onchange="updateItemProp('canAddRows', this.checked)">
                        <label class="form-check-label small fw-bold text-primary" for="canAddRowsCheck"><i class="fas fa-plus-circle me-1"></i>Cho phép thêm dòng (Cấp 2)</label>
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

                    <label class="small fw-bold mt-3 mb-2">Cài đặt ô đang chọn</label>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="small text-muted mb-1">Mã ID ô</label>
                            <input type="text" class="form-control form-control-sm" placeholder="VD: 1, 2" 
                                   value="${(item.data[activeRowIdx-1] && item.data[activeRowIdx-1][activeColIdx]) ? (item.data[activeRowIdx-1][activeColIdx].cellId || '') : ''}" 
                                   oninput="updateTableCellProp('${item.id}', ${activeRowIdx-1}, ${activeColIdx}, 'cellId', this.value)">
                        </div>
                        <div class="col-6">
                            <label class="small text-muted mb-1">Giá trị mặc định</label>
                            <input type="text" class="form-control form-control-sm" placeholder="VD: 4.6" 
                                   value="${(item.data[activeRowIdx-1] && item.data[activeRowIdx-1][activeColIdx]) ? (item.data[activeRowIdx-1][activeColIdx].defaultValue || '') : ''}" 
                                   oninput="updateTableCellProp('${item.id}', ${activeRowIdx-1}, ${activeColIdx}, 'defaultValue', this.value); renderBlocks();">
                        </div>
                        <div class="col-12 mt-1">
                            <div class="form-text small" style="font-size: 0.65rem;">Dùng ID này để đặt công thức. Giá trị mặc định sẽ dùng để tính thử.</div>
                        </div>
                    </div>

                    <label class="small fw-bold mt-1 mb-2">Công cụ Bảng</label>
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
            if (hInput) hInput.value = (item.rowHeights && item.rowHeights[activeRowIdx - 1]) ? item.rowHeights[
                activeRowIdx - 1] : 'auto';
            if (wInput) wInput.value = (item.columns && item.columns[activeColIdx]) ? item.columns[activeColIdx].width :
                'auto';
        }
    }

    /**
     * Thêm một khối Trang tính (Spreadsheet/Table) mới vào tài liệu.
     * Cách hoạt động:
     * 1. Khởi tạo cấu hình dữ liệu mặc định (mảng 2 chiều chứa giá trị rỗng).
     * 2. Tự động gán khối vào Phân đoạn (Section) đang được chọn.
     * 3. Chèn khối vào mảng `items` tại vị trí cuối cùng hoặc vị trí được chỉ định.
     * 4. Render lại giao diện và tự động chọn khối mới vừa tạo.
     * @param {number} rows - Số hàng mặc định.
     * @param {number} cols - Số cột mặc định.
     * @param {number|null} insertIndex - Vị trí chèn khối (nếu null sẽ chèn vào cuối).
     */
    // window.addSpreadsheet = function(rows = 5, cols = 5, insertIndex = null) {
    //     const hint = document.getElementById('drop-hint');
    //     if (hint) hint.classList.add('d-none');

    //     saveState();
    //     const id = 'blk_' + Date.now();

    //     let data = [];
    //     for (let r = 0; r < rows; r++) {
    //         let rowData = [];
    //         for (let c = 0; c < cols; c++) rowData.push({
    //             v: '',
    //             f: ''
    //         }); // v: value, f: formula
    //         data.push(rowData);
    //     }

    //     let sectionId = window.activeSectionId || null;
    //     if (!sectionId && items.length > 0) sectionId = items[items.length - 1].section_id;

    //     const item = {
    //         id: id,
    //         type: 'spreadsheet',
    //         section_id: sectionId,
    //         label: 'Trang tính ' + cols + 'x' + rows,
    //         rows: rows,
    //         cols: cols,
    //         data: data,
    //         borderMode: 'visible'
    //     };

    //     if (insertIndex !== null) items.splice(insertIndex, 0, item);
    //     else items.push(item);

    //     renderBlocks();
    //     selectItem(id);
    // };

    /**
     * Thêm một khối nội dung mới (không phải bảng) như Tiêu đề, Ghi chú...
     * Cách hoạt động:
     * 1. Tạo đối tượng dữ liệu cho khối mới với các thuộc tính mặc định.
     * 2. Xác định Phân đoạn mục tiêu (Section) để gán khối vào đúng nhóm.
     * 3. Sử dụng splice để chèn vào vị trí chỉ định hoặc push để thêm vào cuối mảng.
     * 4. Gọi renderBlocks để hiển thị lên màn hình.
     * @param {string} type - Loại khối (static-text, header...).
     * @param {number|null} insertIndex - Vị trí chèn.
     * @param {string|null} initialTag - Thẻ HTML khởi tạo (H1, H2...).
     */
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
        let sectionId = null;

        if (insertIndex !== null) {
            // Priority 1: Use neighboring items if inserting via divider (+)
            if (insertIndex > 0 && items[insertIndex - 1]) {
                sectionId = items[insertIndex - 1].section_id || items[insertIndex - 1].id;
            } else if (items.length > 0 && items[insertIndex]) {
                sectionId = items[insertIndex].section_id || items[insertIndex].id;
            }
        }

        // Priority 2: Use currently active section
        if (!sectionId) {
            sectionId = window.activeSectionId;
        }

        // Priority 3: Fallback to last section
        if (!sectionId && items.length > 0) {
            sectionId = items[items.length - 1].section_id;
        }

        const item = {
            id: id,
            type: type,
            section_id: sectionId,
            label: (type === 'static-text' ? 'Ghi chú' : 'Tiêu đề ' + type),
            content: defaultContent,
            columns: [],
            borderMode: type === 'static-text' ? 'none' : 'visible',
            dirty: true
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

    /**
     * Thêm một khối Phân đoạn (Section) mới để nhóm các linh kiện.
     * Cách hoạt động: Tạo một đối tượng có type là 'section', đối tượng này đóng vai trò là "container" 
     * hoặc điểm đánh dấu để hàm renderBlocks biết khi nào cần ngắt trang và tạo nhóm mới.
     */
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
            locked: false,
            dirty: true
        };
        items.push(item);
        renderBlocks();
        selectItem(id);
    }

    /**
     * Cập nhật một thuộc tính của khối đang được chọn.
     * Cách hoạt động: Tìm khối trong mảng `items` theo selectedId, thay đổi giá trị thuộc tính 
     * và gọi renderBlocks để cập nhật ngay lập tức giao diện (WYSIWYG).
     * @param {string} prop - Tên thuộc tính cần cập nhật.
     * @param {any} value - Giá trị mới.
     */
    function updateItemProp(prop, value) {
        if (window.isExecutionMode) return;
        const item = items.find(i => i.id === selectedId);
        if (!item) return;
        saveStateDebounced();
        item[prop] = value;
        item.dirty = true;
        renderBlocks();
    }

    /**
     * QUẢN LÝ BẢNG NÂNG CAO (Thêm/Xóa dòng cột nhanh)
     */
    function tableAddRow(index = -1) {
        const item = items.find(i => i.id === selectedId);
        if (!item || item.type !== 'table') return;
        saveStateDebounced();

        const numCols = item.columns.length;
        const newRow = Array(numCols).fill('');

        if (index === -1) {
            item.data.push(newRow);
        } else {
            item.data.splice(index, 0, newRow);
        }

        item.rows = item.data.length;
        item.dirty = true;
        renderBlocks();
    }

    function tableRemoveRow(index) {
        const item = items.find(i => i.id === selectedId);
        if (!item || item.type !== 'table' || item.data.length <= 1) return;
        saveStateDebounced();

        item.data.splice(index, 1);
        item.rows = item.data.length;
        item.dirty = true;
        renderBlocks();
    }

    function tableAddColumn(index = -1) {
        const item = items.find(i => i.id === selectedId);
        if (!item || item.type !== 'table') return;
        saveStateDebounced();

        const newColHeader = 'Cột mới';
        if (index === -1) {
            item.columns.push(newColHeader);
            item.data.forEach(row => row.push(''));
        } else {
            item.columns.splice(index, 0, newColHeader);
            item.data.forEach(row => row.splice(index, 0, ''));
        }

        item.cols = item.columns.length;
        item.dirty = true;
        renderBlocks();
    }

    function tableRemoveColumn(index) {
        const item = items.find(i => i.id === selectedId);
        if (!item || item.type !== 'table' || item.columns.length <= 1) return;
        saveStateDebounced();

        item.columns.splice(index, 1);
        item.data.forEach(row => row.splice(index, 1));

        item.cols = item.columns.length;
        item.dirty = true;
        renderBlocks();
    }

    /**
     * THIẾT LẬP VIỀN BẢNG (Giống Google Doc)
     * @param {string} mode - 'all', 'none', 'outer', 'rows', 'cols'
     */
    function setTableBorderMode(mode) {
        const item = items.find(i => i.id === selectedId);
        if (!item || item.type !== 'table') return;

        const selectedCells = document.querySelectorAll('.selected-cell');

        // Determine range
        let minR = 999,
            maxR = -1,
            minC = 999,
            maxC = -1;
        selectedCells.forEach(cell => {
            const r = parseInt(cell.dataset.row);
            const c = parseInt(cell.dataset.col);
            minR = Math.min(minR, r);
            maxR = Math.max(maxR, r);
            minC = Math.min(minC, c);
            maxC = Math.max(maxC, c);
        });

        // Check if selection covers the entire table (header + all data rows)
        const isFullSelection = selectedCells.length > 0 &&
            minR === 0 &&
            maxR === item.rows &&
            minC === 0 &&
            maxC === item.cols - 1;

        if (selectedCells.length > 0 && !isFullSelection) {
            saveStateDebounced();

            const weight = item.borderWeight || '1px';
            const borderStyle = `${weight} solid #dee2e6`;

            selectedCells.forEach(cell => {
                const r = parseInt(cell.dataset.row);
                const c = parseInt(cell.dataset.col);

                let target;
                if (r === 0) {
                    if (!item.columns[c].style) item.columns[c].style = {};
                    target = item.columns[c].style;
                } else {
                    const rIdx = r - 1;
                    if (!item.data[rIdx][c] || typeof item.data[rIdx][c] !== 'object') {
                        item.data[rIdx][c] = {
                            content: item.data[rIdx][c] || '',
                            rs: 1,
                            cs: 1,
                            hidden: false
                        };
                    }
                    target = item.data[rIdx][c];
                }

                if (mode === 'all') {
                    target.borderTop = target.borderBottom = target.borderLeft = target.borderRight =
                        borderStyle;
                } else if (mode === 'none') {
                    target.borderTop = target.borderBottom = target.borderLeft = target.borderRight = 'none';
                } else if (mode === 'outer') {
                    if (r === minR) target.borderTop = borderStyle;
                    if (r === maxR) target.borderBottom = borderStyle;
                    if (c === minC) target.borderLeft = borderStyle;
                    if (c === maxC) target.borderRight = borderStyle;
                } else if (mode === 'rows') {
                    target.borderTop = target.borderBottom = borderStyle;
                    target.borderLeft = target.borderRight = 'none';
                } else if (mode === 'cols') {
                    target.borderTop = target.borderBottom = 'none';
                    target.borderLeft = target.borderRight = borderStyle;
                } else if (mode === 'dashed') {
                    target.borderTop = target.borderBottom = target.borderLeft = target.borderRight =
                        '1px dashed #ccc';
                }
            });

            item.dirty = true;
            renderBlocks();
            return;
        }

        // Full selection or no selection: apply to entire table
        saveStateDebounced();
        item.borderMode = mode;

        // Clear all cell-level border overrides to keep data clean
        if (item.columns) {
            item.columns.forEach(col => {
                if (col.style) {
                    delete col.style.borderTop;
                    delete col.style.borderBottom;
                    delete col.style.borderLeft;
                    delete col.style.borderRight;
                }
            });
        }
        if (item.data) {
            item.data.forEach(row => {
                row.forEach(cell => {
                    if (cell && typeof cell === 'object') {
                        delete cell.borderTop;
                        delete cell.borderBottom;
                        delete cell.borderLeft;
                        delete cell.borderRight;
                    }
                });
            });
        }

        item.dirty = true;
        renderBlocks();
    }

    /**
     * Xóa một khối khỏi tài liệu dựa trên ID.
     * Cách hoạt động: Kiểm tra trạng thái khóa (locked) của khối, nếu cho phép xóa thì lọc 
     * bỏ khối đó khỏi mảng `items` và ẩn bảng thuộc tính đi.
     * @param {string} id - ID của khối cần xóa.
     */
    function removeItem(id) {
        if (window.isExecutionMode) return;
        const item = items.find(i => i.id === id);
        if (item && item.locked) {
            Swal.fire('Thất bại', 'Không thể xóa khối đã bị khóa!', 'error');
            return;
        }

        // Track DB ID for incremental deletion
        if (item && item.db_id) {
            window.deletedBlockIds.push(item.db_id);
        }

        saveState();
        items = items.filter(i => i.id !== id);
        selectedId = null;
        if (!isSidebarMinimized) {
            const panel = document.getElementById('property-panel');
            if (panel) panel.classList.add('d-none');
        }
        renderBlocks();
    }

    /**
     * Thay đổi thứ tự của khối (lên hoặc xuống).
     * Cách hoạt động: Hoán đổi vị trí của khối hiện tại với khối liền kề trong mảng `items` 
     * dựa trên hướng di chuyển (dir), sau đó vẽ lại toàn bộ giao diện.
     * @param {number} idx - Chỉ số hiện tại của khối trong mảng items.
     * @param {number} dir - Hướng di chuyển (-1 là lên, 1 là xuống).
     */
    function moveItem(idx, dir) {
        saveState();
        const newIdx = idx + dir;
        if (newIdx < 0 || newIdx >= items.length) return;

        // Mark both items as dirty because their order (and potentially section) changed
        items[idx].dirty = true;
        items[newIdx].dirty = true;

        const temp = items[idx];
        items[idx] = items[newIdx];
        items[newIdx] = temp;
        renderBlocks();
    }

    /**
     * Cập nhật nội dung của bảng trực tiếp khi người dùng gõ vào ô.
     * @param {string} id - ID của khối bảng.
     * @param {string} type - Loại cập nhật ('col' hoặc 'cell').
     * @param {number} r - Chỉ số hàng.
     * @param {number} c - Chỉ số cột.
     * @param {string} val - Nội dung mới.
     */
    function updateTableInline(id, type, r, c, val) {
        if (window.isExecutionMode) {
            // Optional: sync to executionValues if it has a cellId
            const item = items.find(i => i.id === id);
            if (item && item.data[r][c] && item.data[r][c].cellId) {
                window.executionValues[item.data[r][c].cellId] = val.replace(/<[^>]*>/g, '').trim();
                recalculateAllFormulas();
            }
            return;
        }
        saveStateDebounced();
        const item = items.find(i => i.id === id);
        if (!item) return;
        item.dirty = true;
        if (type === 'col') item.columns[c].label = val;
        else if (type === 'cell') {
            if (!item.data[r][c] || typeof item.data[r][c] !== 'object') {
                item.data[r][c] = {
                    content: val,
                    rs: 1,
                    cs: 1,
                    hidden: false
                };
            } else {
                item.data[r][c].content = val;
            }
        }
        if (typeof syncLinkedCharts === 'function') syncLinkedCharts(id);

        // Recalculate all formula fields if we are in execution mode
        if (window.isExecutionMode && typeof recalculateAllFormulas === 'function') {
            recalculateAllFormulas();
        }
    }

    /**
     * Cập nhật nội dung văn bản tĩnh trực tiếp khi người dùng gõ.
     * @param {string} id - ID của khối văn bản.
     * @param {string} val - Nội dung HTML mới.
     */
    function updateStaticTextInline(id, val) {
        if (window.isExecutionMode) return;
        saveStateDebounced();
        const item = items.find(i => i.id === id);
        if (!item) return;
        item.dirty = true;

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

    /**
     * Tự động viết hoa chữ cái đầu tiên khi người dùng bắt đầu gõ vào khối văn bản.
     * @param {HTMLElement} el - Phần tử đang chỉnh sửa.
     */
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

    /**
     * Thực hiện các lệnh định dạng văn bản (In đậm, Nghiêng, Căn lề...) cho vùng chọn hoặc ô bảng.
     * Cách hoạt động: 
     * 1. Ưu tiên 1: Áp dụng lệnh cho văn bản đang được bôi đen bằng `document.execCommand`.
     * 2. Ưu tiên 2: Nếu có nhiều ô bảng được chọn, duyệt qua từng ô và cập nhật style vào JSON dữ liệu.
     * 3. Ưu tiên 3: Nếu đang ở trong 1 ô đơn lẻ, cập nhật định dạng cho riêng ô đó.
     * @param {string} command - Lệnh định dạng (bold, italic, foreColor...).
     * @param {any} value - Tham số bổ sung cho lệnh (ví dụ: mã màu).
     */
    function formatDoc(command, value = null) {
        const selection = window.getSelection();
        const selectedText = selection.toString().trim();
        const selectedCells = document.querySelectorAll('.selected-cell');

        // Priority 1: If text is selected within a cell/block, use standard execCommand
        // EXCEPT if it's a command that can be applied to the entire cell (like Bold/Italic)
        // and we are inside a table cell.
        const cellCommands = ['bold', 'italic', 'underline', 'strikethrough', 'justifyLeft', 'justifyCenter',
            'justifyRight', 'justifyFull'
        ];
        const selectionNode = selection.anchorNode;
        const activeCell = selectionNode ? (selectionNode.nodeType === 3 ? selectionNode.parentElement : selectionNode)
            .closest('.mini-table td') : null;

        if (selectedText.length > 0) {
            document.execCommand(command, false, value);

            // Force data sync for the active cell/block
            const editable = activeCell || (selectionNode ? (selectionNode.nodeType === 3 ? selectionNode
                .parentElement : selectionNode).closest('[contenteditable="true"]') : null);
            if (editable && editable.oninput) {
                editable.oninput();
            }
            // Mark item as dirty if we're in a specific block
            const blockItem = editable ? editable.closest('.block-item') : null;
            if (blockItem) {
                const item = items.find(i => i.id === blockItem.getAttribute('data-id'));
                if (item) item.dirty = true;
            }
            saveStateDebounced();
            return;
        }

        // Priority 2: If no text selected but cells are selected, use bulk cell formatting
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
                    let prop = null;
                    let val = null;
                    let domProp = null;

                    if (command === 'bold') {
                        prop = 'fontWeight';
                        const currentVal = (r === 0) ?
                            (item.columns[c].style ? item.columns[c].style.fontWeight : 'normal') :
                            (item.data[r - 1] && item.data[r - 1][c] && item.data[r - 1][c].fontWeight);
                        val = (currentVal === 'bold') ? 'normal' : 'bold';
                        domProp = 'fontWeight';
                    } else if (command === 'italic') {
                        prop = 'fontStyle';
                        const currentVal = (r === 0) ?
                            (item.columns[c].style ? item.columns[c].style.fontStyle : 'normal') :
                            (item.data[r - 1] && item.data[r - 1][c] && item.data[r - 1][c].fontStyle);
                        val = (currentVal === 'italic') ? 'normal' : 'italic';
                        domProp = 'fontStyle';
                    } else if (command === 'underline') {
                        prop = 'textDecoration';
                        const currentVal = (r === 0) ?
                            (item.columns[c].style ? item.columns[c].style.textDecoration : 'none') :
                            (item.data[r - 1] && item.data[r - 1][c] && item.data[r - 1][c].textDecoration);
                        val = (currentVal === 'underline') ? 'none' : 'underline';
                        domProp = 'textDecoration';
                    } else if (command === 'strikethrough') {
                        prop = 'textDecoration';
                        const currentVal = (r === 0) ?
                            (item.columns[c].style ? item.columns[c].style.textDecoration : 'none') :
                            (item.data[r - 1] && item.data[r - 1][c] && item.data[r - 1][c].textDecoration);
                        val = (currentVal === 'line-through') ? 'none' : 'line-through';
                        domProp = 'textDecoration';
                    } else if (command === 'foreColor') {
                        prop = 'textColor';
                        val = value;
                        domProp = 'color';
                    } else if (command === 'superscript' || command === 'subscript') {
                        const tag = command === 'superscript' ? 'sup' : 'sub';
                        if (r > 0) {
                            const rIdx = r - 1;
                            if (item.data && item.data[rIdx] && item.data[rIdx][c] !== undefined) {
                                if (!item.data[rIdx][c] || typeof item.data[rIdx][c] !== 'object') {
                                    item.data[rIdx][c] = {
                                        content: item.data[rIdx][c],
                                        rs: 1,
                                        cs: 1,
                                        hidden: false
                                    };
                                }

                                const cellData = item.data[rIdx][c];
                                let content = cellData.content || "";

                                if (content.includes(`<${tag}>`) && content.includes(`</${tag}>`)) {
                                    const regex = new RegExp(`<\/?${tag}>`, 'g');
                                    cellData.content = content.replace(regex, '');
                                } else {
                                    cellData.content = `<${tag}>${content}</${tag}>`;
                                }
                                cell.innerHTML = decorateContent(cellData.content);
                            }
                        }
                    } else if (command === 'justifyLeft') {
                        prop = 'textAlign';
                        val = 'left';
                        domProp = 'textAlign';
                    } else if (command === 'justifyCenter') {
                        prop = 'textAlign';
                        val = 'center';
                        domProp = 'textAlign';
                    } else if (command === 'justifyRight') {
                        prop = 'textAlign';
                        val = 'right';
                        domProp = 'textAlign';
                    } else if (command === 'justifyFull') {
                        prop = 'textAlign';
                        val = 'justify';
                        domProp = 'textAlign';
                    }

                    if (prop && r > 0) {
                        const rIdx = r - 1;
                        if (item.data && item.data[rIdx] && item.data[rIdx][c] !== undefined) {
                            if (!item.data[rIdx][c] || typeof item.data[rIdx][c] !== 'object') {
                                item.data[rIdx][c] = {
                                    content: item.data[rIdx][c],
                                    rs: 1,
                                    cs: 1,
                                    hidden: false
                                };
                            }
                            item.data[rIdx][c][prop] = val;
                        }
                        if (domProp) cell.style[domProp] = val;
                    } else if (prop && r === 0) {
                        if (item.columns && item.columns[c]) {
                            if (!item.columns[c].style) item.columns[c].style = {};
                            item.columns[c].style[prop] = val;
                        }
                        if (domProp) cell.style[domProp] = val;
                    }
                }
            });
            saveStateDebounced();
            return;
        }

        // Priority 3: If NO multiple cells selected but cursor is in a single table cell and it's a cell command
        if (activeCell && cellCommands.includes(command)) {
            const r = parseInt(activeCell.dataset.row);
            const c = parseInt(activeCell.dataset.col);
            const table = activeCell.closest('.mini-table');
            const blockItem = table ? table.closest('.block-item') : null;
            const itemId = blockItem ? blockItem.getAttribute('data-id') : null;
            const item = items.find(i => i.id === itemId);

            if (item) {
                let prop = null;
                let val = null;
                let domProp = null;

                const getCellData = () => {
                    if (r === 0) return {
                        style: item.columns[c].style || {}
                    };
                    const rIdx = r - 1;
                    if (!item.data[rIdx][c] || typeof item.data[rIdx][c] !== 'object') {
                        item.data[rIdx][c] = {
                            content: activeCell.innerHTML,
                            rs: 1,
                            cs: 1,
                            hidden: false
                        };
                    }
                    return item.data[rIdx][c];
                };

                const cellData = getCellData();

                if (command === 'bold') {
                    prop = 'fontWeight';
                    val = (r === 0 ? (cellData.style ? cellData.style.fontWeight : 'normal') : cellData.fontWeight) ===
                        'bold' ? 'normal' : 'bold';
                    domProp = 'fontWeight';
                } else if (command === 'italic') {
                    prop = 'fontStyle';
                    val = (r === 0 ? (cellData.style ? cellData.style.fontStyle : 'normal') : cellData.fontStyle) ===
                        'italic' ? 'normal' : 'italic';
                    domProp = 'fontStyle';
                } else if (command === 'underline') {
                    prop = 'textDecoration';
                    val = (r === 0 ? (cellData.style ? cellData.style.textDecoration : 'none') : cellData
                        .textDecoration) === 'underline' ? 'none' : 'underline';
                    domProp = 'textDecoration';
                } else if (command === 'strikethrough') {
                    prop = 'textDecoration';
                    val = (r === 0 ? (cellData.style ? cellData.style.textDecoration : 'none') : cellData
                        .textDecoration) === 'line-through' ? 'none' : 'line-through';
                    domProp = 'textDecoration';
                } else if (command === 'justifyLeft') {
                    prop = 'textAlign';
                    val = 'left';
                    domProp = 'textAlign';
                } else if (command === 'justifyCenter') {
                    prop = 'textAlign';
                    val = 'center';
                    domProp = 'textAlign';
                } else if (command === 'justifyRight') {
                    prop = 'textAlign';
                    val = 'right';
                    domProp = 'textAlign';
                } else if (command === 'justifyFull') {
                    prop = 'textAlign';
                    val = 'justify';
                    domProp = 'textAlign';
                }

                if (prop) {
                    if (r === 0) {
                        if (!item.columns[c].style) item.columns[c].style = {};
                        item.columns[c].style[prop] = val;
                    } else {
                        item.data[r - 1][c][prop] = val;
                    }
                    if (domProp) activeCell.style[domProp] = val;
                    saveStateDebounced();
                    return;
                }
            }
        }

        // Fallback for simple cursor focus (no selection)
        document.execCommand(command, false, value);
        saveStateDebounced();
    }

    // Smart Paste Handler to clean up unwanted line breaks from PDF/Word
    document.addEventListener('paste', function(e) {
        const target = e.target.closest('.static-text-display, .mini-table td');
        if (!target) return;

        // Try to get HTML content first to check for ebmr-field-badge
        const html = (e.clipboardData || window.clipboardData).getData('text/html');
        if (html && html.includes('ebmr-field-badge')) {
            e.preventDefault();
            saveState();
            if (typeof window.duplicateFieldBadgesInHtml === 'function') {
                const duplicatedHtml = window.duplicateFieldBadgesInHtml(html);
                document.execCommand("insertHTML", false, duplicatedHtml);
            } else {
                document.execCommand("insertHTML", false, html);
            }
            saveStateDebounced();
            return;
        }

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

    /**
     * Tạo mã HTML cho bảng chọn màu sắc (Color Picker).
     * @param {string} callbackName - Tên hàm callback sẽ được gọi khi chọn màu.
     */
    function getThemeColorsHTML(callbackName) {
        const colors = [
            '#000000', '#434343', '#666666', '#999999', '#b7b7b7', '#cccccc', '#d9d9d9', '#efefef', '#f3f3f3',
            '#ffffff',
            '#980000', '#ff0000', '#ff9900', '#ffff00', '#00ff00', '#00ffff', '#4a86e8', '#0000ff', '#9900ff',
            '#ff00ff',
            '#e6b8af', '#f4cccc', '#fce5cd', '#fff2cc', '#d9ead3', '#d0e0e3', '#c9daf8', '#cfe2f3', '#d9d2e9',
            '#ead1dc',
            '#dd7e6b', '#ea9999', '#f9cb9c', '#ffe599', '#b6d7a8', '#a2c4c9', '#a4c2f4', '#9fc5e8', '#b4a7d6',
            '#d5a6bd',
            '#cc4125', '#e06666', '#f6b26b', '#ffd966', '#93c47d', '#76a5af', '#6d9eeb', '#6fa8dc', '#8e7cc3',
            '#c27ba0',
            '#a61c00', '#cc0000', '#e69138', '#f1c232', '#6aa84f', '#45818e', '#3c78d8', '#3d85c6', '#674ea7',
            '#a64d79',
            '#85200c', '#990000', '#b45f06', '#bf9000', '#38761d', '#134f5c', '#1155cc', '#0b5394', '#351c75',
            '#741b47',
            '#5b0f00', '#660000', '#783f04', '#7f6000', '#274e13', '#0c343d', '#1c4587', '#073763', '#20124d',
            '#4c1130'
        ];

        let html = '<div class="d-flex flex-wrap gap-1" style="width: 240px; justify-content: space-between;">';
        colors.forEach(c => {
            const isLight = c === '#ffffff' || c === '#efefef' || c === '#f3f3f3';
            const cls = isLight ? 'color-swatch light-color' : 'color-swatch';
            html +=
                `<div class="${cls}" style="background-color: ${c};" onclick="${callbackName}('${c}')" onmousedown="event.preventDefault()"></div>`;
        });
        html += '</div>';
        return html;
    }

    window.currentTextColor = '#ff0000';
    window.applyCurrentTextColor = function() {
        formatDoc('foreColor', window.currentTextColor);
    };
    /**
     * Cập nhật màu chữ đang chọn và áp dụng cho các ô hoặc văn bản đang được chọn.
     * @param {string} color - Mã màu HEX.
     */
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
                                item.data[rIdx][c] = {
                                    content: item.data[rIdx][c],
                                    rs: 1,
                                    cs: 1,
                                    hidden: false
                                };
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

    /**
     * Thay đổi kích thước chữ (tăng hoặc giảm).
     * @param {number} delta - Giá trị thay đổi (ví dụ: 1 hoặc -1).
     */
    function changeFontSize(delta) {
        const display = document.getElementById('fontSizeDisplay');
        if (!display) return;
        let current = parseInt(display.innerText) || 16;
        let next = current + delta;
        if (next < 8) next = 8;
        if (next > 72) next = 72;
        applyCustomFontSize(next);
    }

    /**
     * Áp dụng kích thước chữ cụ thể cho vùng chọn hoặc các ô đang chọn.
     * @param {number} pt - Kích thước chữ tính bằng point (pt).
     */
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
                                item.data[rIdx][c] = {
                                    content: item.data[rIdx][c],
                                    rs: 1,
                                    cs: 1,
                                    hidden: false
                                };
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

    /**
     * Thay đổi hướng chữ (Text Direction) cho các ô/cột được chọn.
     * @param {string} direction - Hướng chữ ('horizontal', 'vertical-down', 'vertical-up').
     */
    window.changeTextDirection = function(direction) {
        const selection = window.getSelection();
        const selectedCells = document.querySelectorAll('.selected-cell');
        const selectionNode = selection.anchorNode;
        const activeCell = selectionNode ? (selectionNode.nodeType === 3 ? selectionNode.parentElement : selectionNode)
            .closest('.mini-table td, .mini-table th') : null;

        let writingMode = '';
        let transform = '';

        if (direction === 'vertical-down') {
            writingMode = 'vertical-rl';
            transform = 'none';
        } else if (direction === 'vertical-up') {
            writingMode = 'vertical-rl';
            transform = 'rotate(180deg)';
        } else {
            writingMode = 'horizontal-tb';
            transform = 'none';
        }

        const targets = [];
        if (selectedCells.length > 0) {
            selectedCells.forEach(cell => targets.push(cell));
        } else if (activeCell) {
            targets.push(activeCell);
        }

        if (targets.length > 0) {
            saveState();
            targets.forEach(cell => {
                const rStr = cell.dataset.row;
                const cStr = cell.dataset.col;
                if (rStr === undefined || cStr === undefined) return;
                const r = parseInt(rStr);
                const c = parseInt(cStr);

                const table = cell.closest('.mini-table');
                const blockItem = table ? table.closest('.block-item') : null;
                const itemId = blockItem ? blockItem.getAttribute('data-id') : null;
                const item = items.find(i => i.id === itemId);

                if (item) {
                    item.dirty = true;
                    if (r === 0) {
                        // Header row
                        if (item.columns && item.columns[c]) {
                            if (!item.columns[c].style) item.columns[c].style = {};
                            item.columns[c].style.writingMode = writingMode;
                            item.columns[c].style.transform = transform;
                        }
                    } else {
                        // Body row
                        const rIdx = r - 1;
                        if (item.data && item.data[rIdx] && item.data[rIdx][c] !== undefined) {
                            if (!item.data[rIdx][c] || typeof item.data[rIdx][c] !== 'object') {
                                item.data[rIdx][c] = {
                                    content: cell.innerHTML,
                                    rs: 1,
                                    cs: 1,
                                    hidden: false
                                };
                            }
                            item.data[rIdx][c].writingMode = writingMode;
                            item.data[rIdx][c].transform = transform;
                        }
                    }
                }

                // Update DOM directly
                cell.style.writingMode = writingMode;
                const inner = cell.querySelector('.cell-wrapper, .header-content');
                if (inner) {
                    inner.style.transform = transform;
                    inner.style.transformOrigin = 'center center';
                    inner.style.display = 'inline-block';
                    inner.style.width = '100%';
                }
            });
            saveStateDebounced();
        }
    };

    /**
     * Xử lý tải ảnh lên và chèn vào vị trí con trỏ dưới dạng Base64.
     * @param {HTMLInputElement} inputElement - Phần tử input file.
     */
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

    /**
     * Mở modal Tìm kiếm và Thay thế.
     * @param {boolean} isReplace - Nếu true sẽ mở tab Thay thế, ngược lại mở tab Tìm kiếm.
     */
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
            if (typeof updateSearchButtons === 'function') updateSearchButtons(true);
        } else {
            $('#find-tab').tab('show');
            if (typeof updateSearchButtons === 'function') updateSearchButtons(false);
        }

        setTimeout(() => findInput.focus(), 500);
        $('#searchStats').text('');
    };

    /**
     * Thực hiện lệnh tìm kiếm nội dung trong văn bản.
     * Cách hoạt động: Sử dụng hàm `window.find()` mặc định của trình duyệt để tìm và bôi đen cụm từ. 
     * Nếu không tìm thấy, nó sẽ xóa vùng chọn và thử tìm lại từ đầu trang.
     * @param {boolean} silent - Nếu true sẽ không hiện thông báo khi không tìm thấy.
     */
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

    /**
     * Thực hiện thay thế kết quả tìm kiếm hiện tại bằng nội dung mới.
     */
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

    /**
     * Thực hiện thay thế tất cả các cụm từ tìm thấy trong toàn bộ tài liệu.
     * Cách hoạt động: Sử dụng vòng lặp `while` kết hợp với `window.find()`. Mỗi khi tìm thấy 1 từ, 
     * nó dùng `execCommand('insertText')` để thay thế, sau đó tiếp tục tìm từ tiếp theo cho đến hết.
     */
    window.executeReplaceAll = function() {
        const findTerm = document.getElementById('findInput').value;
        const replaceTerm = document.getElementById('replaceInput').value;
        if (!findTerm) return;

        saveState();
        let count = 0;

        // Move to start of the editor to begin a clean forward search
        const editor = document.getElementById('editor-content');
        window.getSelection().removeAllRanges();
        const range = document.createRange();
        range.setStart(editor, 0);
        range.collapse(true);
        window.getSelection().addRange(range);

        // IMPORTANT: aWrapAround MUST be false here to prevent finding the search term inside 
        // the newly replaced text and causing an infinite loop.
        while (window.find(findTerm, false, false, false, false, true, false)) {
            document.execCommand('insertText', false, replaceTerm);
            count++;
            // Safety break
            if (count > 5000) break;
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


    /**
     * Wrapper để cập nhật màu nền khối và đóng dropdown chọn màu.
     * @param {string} color - Mã màu HEX.
     */
    window.updateBlockBackgroundWrapper = function(color) {
        if (!selectedId) return;
        updateBlockBackground(selectedId, color);
        // Bootstrap 4 style close
        $('.dropdown-toggle').dropdown('hide');
    };

    /**
     * Cập nhật màu nền cho khối hoặc các ô đang được chọn trong bảng.
     * Cách hoạt động: 
     * 1. Nếu có nhiều ô đang chọn (selected-cell), cập nhật màu cho từng ô trong JSON.
     * 2. Nếu không, cập nhật màu nền cho toàn bộ khối (item.backgroundColor).
     * @param {string} id - ID của khối.
     * @param {string} color - Mã màu HEX.
     */
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
                            item.data[r][col] = {
                                content: item.data[r][col] || '',
                                rs: 1,
                                cs: 1,
                                hidden: false
                            };
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

    /**
     * Khởi tạo thanh thước kẻ (Ruler) để điều chỉnh lề trái/phải của các khối.
     */
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

    /**
     * Cập nhật vị trí các điểm mốc trên thước kẻ dựa theo lề của khối đang chọn.
     */
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

    /**
     * Thu nhỏ hoặc mở rộng thanh Mục lục (Outline) bên trái.
     * Cách hoạt động: Thay đổi class CSS của cột mục lục để thay đổi độ rộng (Bootstrap col-lg-x) 
     * và ẩn/hiện các phần tử con, đồng thời gọi updateCanvasWidth để căn chỉnh lại trang giấy.
     * @param {boolean} minimize - Trạng thái thu nhỏ.
     */
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

    /**
     * Thu nhỏ hoặc mở rộng bảng thuộc tính (Sidebar) bên phải.
     * Cách hoạt động: Tương tự như thanh mục lục, hàm này thay đổi class CSS của cột bên phải 
     * và điều chỉnh lại style của Property Panel để tối ưu không gian hiển thị.
     * @param {boolean} minimize - Trạng thái thu nhỏ.
     */
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

    /**
     * Tự động tính toán và cập nhật chiều rộng của vùng làm việc (Canvas) dựa trên trạng thái của 2 thanh bên.
     * Cách hoạt động: Sử dụng các điều kiện logic để gán class Bootstrap (col-lg-7/8/9/10) sao cho 
     * tổng số cột luôn là 12, giúp trang giấy luôn nằm ở trung tâm và có kích thước phù hợp nhất.
     */
    function updateCanvasWidth() {
        const canvas = document.getElementById('canvas-col');
        if (!canvas) return;

        if (window.isExecutionMode) {
            if (isOutlineMinimized) {
                canvas.className = 'col-lg-12 transition-all';
            } else {
                canvas.className = 'col-lg-10 transition-all';
            }
            return;
        }

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
    /**
     * Xử lý khi nhấn nút Chèn Chữ Ký. 
     * Tự động xác định chèn vào vị trí con trỏ (inline) hoặc tạo một khối chữ ký mới.
     */
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

    /**
     * Cập nhật một thuộc tính cụ thể của ô trong bảng (ví dụ: cellId, defaultValue).
     * @param {string} itemId - ID của khối bảng.
     * @param {number} r - Chỉ số hàng.
     * @param {number} c - Chỉ số cột.
     * @param {string} prop - Tên thuộc tính.
     * @param {any} val - Giá trị mới.
     */
    function updateTableCellProp(itemId, r, c, prop, val) {
        const item = items.find(i => i.id === itemId);
        if (!item) return;
        item.dirty = true;

        const selectedCells = Array.from(document.querySelectorAll('.selected-cell'))
            .filter(cell => cell.closest('.block-item').getAttribute('data-id') === itemId);

        if (selectedCells.length > 1) {
            saveStateDebounced();
            selectedCells.forEach(cell => {
                const row = parseInt(cell.dataset.row);
                const col = parseInt(cell.dataset.col);
                if (row > 0) {
                    const rIdx = row - 1;
                    if (!item.data[rIdx] || !item.data[rIdx][col] || typeof item.data[rIdx][col] !== 'object') {
                        item.data[rIdx][col] = {
                            content: item.data[rIdx][col] || '',
                            rs: 1,
                            cs: 1,
                            hidden: false
                        };
                    }
                    item.data[rIdx][col][prop] = val;
                }
            });
        } else if (item.data && item.data[r] && item.data[r][c] !== undefined) {
            if (typeof item.data[r][c] !== 'object') {
                item.data[r][c] = {
                    content: item.data[r][c],
                    rs: 1,
                    cs: 1,
                    hidden: false
                };
            }
            item.data[r][c][prop] = val;
            saveStateDebounced();
        }
    }

    /**
     * Chọn một thẻ dữ liệu (Field) để cấu hình thuộc tính (loại dữ liệu, mã biến...).
     * @param {Event} event - Sự kiện click.
     * @param {string} fieldId - ID của thẻ dữ liệu.
     */
    function selectField(event, fieldId) {
        if (event) event.stopPropagation();
        if (window.isExecutionMode) return;

        selectedFieldId = fieldId;
        selectedId = null;

        document.querySelectorAll('.block-item').forEach(el => el.classList.remove('active'));

        const field = fieldsConfig[fieldId];
        if (!field) return;

        if (!field.validation) {
            field.validation = {
                required: false,
                min: null,
                max: null,
                decimal_places: null
            };
        }

        const panel = document.getElementById('property-panel');
        const body = document.getElementById('prop-body');

        if (panel) {
            panel.classList.remove('d-none');
            if (isSidebarMinimized) toggleSidebar(false);
        }

        let typeHtml = `
            <div class="mb-3">
                <label class="small fw-bold mb-2">Loại dữ liệu</label>
                <select class="form-select form-select-sm" onchange="syncFieldConfig('${fieldId}', 'type', this.value)">
                    <option value="text" ${field.type === 'text' ? 'selected' : ''}>Văn bản tự do</option>
                    <option value="number" ${field.type === 'number' ? 'selected' : ''}>Số liệu (Tính toán)</option>
                    <option value="checkbox" ${field.type === 'checkbox' ? 'selected' : ''}>Hộp kiểm tra (Tick)</option>
                    <option value="formula" ${field.type === 'formula' ? 'selected' : ''}>Công thức tự động (=)</option>
                    <option value="date" ${field.type === 'date' ? 'selected' : ''}>Ngày tháng</option>
                    <option value="signature" ${field.type === 'signature' ? 'selected' : ''}>Chữ ký điện tử</option>
                </select>
            </div>
        `;

        if (field.type === 'formula') {
            // Find all numeric fields available in the document to help user
            let numberFieldsOptions = '<option value="">-- Chọn biến số --</option>';
            Object.values(fieldsConfig).forEach(f => {
                if (f.type === 'number' || f.type === 'formula') {
                    // Tránh vòng lặp (không tự thêm chính nó)
                    if (f.id !== fieldId) {
                        const displayName = f.label ? `${f.label} = ${f.name}` : f.name;
                        numberFieldsOptions += `<option value="${f.name}">${displayName}</option>`;
                    }
                }
            });

            typeHtml += `
                <div class="mb-3">
                    <label class="small fw-bold text-primary mb-1"><i class="fas fa-calculator me-1"></i>Công thức tính</label>
                    <div class="input-group input-group-sm mb-2">
                        <select class="form-select border-primary" style="max-width: 60%;" id="formula-var-helper" onchange="if(this.value) { const input = document.getElementById('formula-input-${fieldId}'); input.value += '(' + this.value + ')'; syncFieldConfig('${fieldId}', 'formula', input.value); this.value=''; }">
                            ${numberFieldsOptions}
                        </select>
                        <button class="btn btn-outline-primary" type="button" onclick="const input = document.getElementById('formula-input-${fieldId}'); input.value += ' + '; syncFieldConfig('${fieldId}', 'formula', input.value);">+</button>
                        <button class="btn btn-outline-primary" type="button" onclick="const input = document.getElementById('formula-input-${fieldId}'); input.value += ' - '; syncFieldConfig('${fieldId}', 'formula', input.value);">-</button>
                        <button class="btn btn-outline-primary" type="button" onclick="const input = document.getElementById('formula-input-${fieldId}'); input.value += ' * '; syncFieldConfig('${fieldId}', 'formula', input.value);">×</button>
                        <button class="btn btn-outline-primary" type="button" onclick="const input = document.getElementById('formula-input-${fieldId}'); input.value += ' / '; syncFieldConfig('${fieldId}', 'formula', input.value);">÷</button>
                    </div>
                    <textarea id="formula-input-${fieldId}" class="form-control form-control-sm border-primary font-monospace" 
                              style="overflow:hidden; resize:none;"
                              placeholder="VD: (var_1) - (var_2)" 
                              oninput="syncFieldConfig('${fieldId}', 'formula', this.value); this.style.height = 'auto'; this.style.height = (this.scrollHeight) + 'px'; 
                                       const preview = document.getElementById('formula-friendly-preview');
                                       if(preview) preview.innerText = this.value.replace(/\\\(([^)]+)\\\)/g, (match, id) => {
                                           const target = Object.values(fieldsConfig).find(f => f.name === id || f.label === id);
                                           return target ? (target.label || id) : id;
                                       });">${field.formula || ''}</textarea>
                    
                    <div class="mt-1 p-1 bg-light border rounded small" style="font-size: 0.75rem;">
                        <span class="text-muted">Xem trước (Nhãn):</span> 
                        <span id="formula-friendly-preview" class="text-primary fw-bold">${(field.formula || '').replace(/\(([^)]+)\)/g, (match, id) => {
                            const target = Object.values(fieldsConfig).find(f => f.name === id || f.label === id);
                            return target ? (target.label || id) : id;
                        })}</span>
                    </div>

                    <div class="form-text small" style="font-size: 0.65rem;">Chọn biến số từ thanh công cụ trên, hoặc tự gõ. Biến số phải đặt trong ngoặc đơn, VD: (var_1) + (var_2)</div>
                    
                    <div class="mt-2">
                        <label class="small fw-bold text-muted" style="font-size: 0.75em;">Làm tròn số thập phân</label>
                        <input type="number" class="form-control form-control-sm" min="0" max="6" 
                               placeholder="VD: 2" 
                               value="${field.validation.decimal_places !== null ? field.validation.decimal_places : ''}" 
                               oninput="syncFieldConfig('${fieldId}', 'validation.decimal_places', this.value); recalculateAllFormulas();">
                    </div>
                </div>
            `;
        }

        typeHtml += `
            <div class="mb-3">
                <label class="small fw-bold text-muted text-uppercase mb-1"><i class="fas fa-fingerprint me-1"></i>Mã biến số (ID trong công thức)</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light border-end-0">@</span>
                    <input type="text" class="form-control border-start-0 font-monospace" value="${field.name || ''}" 
                           oninput="syncFieldConfig('${fieldId}', 'name', this.value)">
                </div>
                <div class="form-text small" style="font-size: 0.65rem;">Viết liền không dấu. VD: sl, kl_tong.</div>
            </div>

            <div class="mb-3">
                <label class="small fw-bold text-muted text-uppercase mb-1"><i class="fas fa-star text-warning me-1"></i>Thông số quan trọng (Critical Var)</label>
                <select class="form-select form-select-sm border-warning shadow-sm" onchange="syncFieldConfig('${fieldId}', 'important_var_id', this.value)">
                    <option value="">-- Không --</option>
                    ${(window.importantVars || []).map(v => `<option value="${v.id}" ${field.important_var_id == v.id ? 'selected' : ''}>${v.name} (${v.description || ''})</option>`).join('')}
                </select>
                <div class="form-text small" style="font-size: 0.65rem;">Gắn cờ CPP/CMA để lọc báo cáo thông số quan trọng.</div>
            </div>

            <div class="mb-3">
                <label class="small fw-bold text-muted text-uppercase mb-2">Tên thẻ (Nhãn hiển thị)</label>
                <input type="text" class="form-control form-control-sm" value="${field.label || ''}" oninput="syncFieldConfig('${fieldId}', 'label', this.value)">
                <div class="form-text small" style="font-size: 0.7rem;">Hiển thị cho người dùng. VD: Số lượng.</div>
            </div>

            <div class="mb-3">
                <label class="small fw-bold text-muted text-uppercase mb-2">Giá trị mặc định (Dùng chạy thử)</label>
                <input type="text" class="form-control form-control-sm" value="${field.defaultValue || ''}" placeholder="VD: 4.6" oninput="syncFieldConfig('${fieldId}', 'defaultValue', this.value); recalculateAllFormulas();">
                <div class="form-text small" style="font-size: 0.7rem;">Dùng để tính toán thử ngay trong trình thiết kế.</div>
            </div>

            <div class="mb-3">
                <label class="small fw-bold text-primary text-uppercase mb-2"><i class="fas fa-info-circle me-1"></i> Hướng dẫn ghi chép</label>
                <textarea class="form-control form-control-sm border-primary" rows="2" placeholder="VD: Kiểm tra nhiệt độ trước khi ghi..." oninput="syncFieldConfig('${fieldId}', 'instruction', this.value)">${field.instruction || ''}</textarea>
                <div class="form-text small" style="font-size: 0.7rem;">Hiện nội dung này trong modal khi người thực hiện nhập liệu.</div>
            </div>

            <hr class="my-3">
        `;

        typeHtml += `
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
                        <div class="form-check form-switch ps-4 pt-1 mt-2">
                            <input class="form-check-input ms-n4" type="checkbox" id="fieldAllowOutOfBounds" ${field.validation && field.validation.allow_out_of_bounds ? 'checked' : ''} onchange="syncFieldConfig('${fieldId}', 'validation.allow_out_of_bounds', this.checked)">
                            <label class="form-check-label small text-muted" style="font-size: 0.75em;" for="fieldAllowOutOfBounds">Cho phép nhập ngoài giới hạn?</label>
                        </div>
                    </div>
                </div>

                <!-- KẾT NỐI CÂN ĐIỆN TỬ -->
                <div class="card border-0 shadow-none mb-3" style="background: linear-gradient(135deg, #f0fdf4, #dcfce7); border: 1px solid #bbf7d0 !important;">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="fas fa-balance-scale text-success"></i>
                            <label class="small fw-bold mb-0 text-success-emphasis">Kết nối Cân điện tử (RS-232)</label>
                        </div>
                        <div class="form-check form-switch ps-4 mb-2">
                            <input class="form-check-input ms-n4" type="checkbox" id="scaleEnabledCheck_${fieldId}"
                                   ${field.scaleEnabled ? 'checked' : ''}
                                   onchange="syncFieldConfig('${fieldId}', 'scaleEnabled', this.checked); document.getElementById('scaleBrandRow_${fieldId}').classList.toggle('d-none', !this.checked);">
                            <label class="form-check-label small fw-bold" for="scaleEnabledCheck_${fieldId}">
                                Cho phép đọc từ cân điện tử
                            </label>
                        </div>
                        <div id="scaleBrandRow_${fieldId}" class="${field.scaleEnabled ? '' : 'd-none'}">
                            <label class="small text-muted mb-1" style="font-size: 0.72rem;">Hãng cân mặc định</label>
                            <select class="form-select form-select-sm" onchange="syncFieldConfig('${fieldId}', 'scalePreset', this.value)">
                                <option value="and"     ${(field.scalePreset || 'and') === 'and'      ? 'selected' : ''}>⚖️ A&D (AND)</option>
                                <option value="mettler" ${(field.scalePreset) === 'mettler'           ? 'selected' : ''}>🏋️ Mettler Toledo</option>
                                <option value="sartorius" ${(field.scalePreset) === 'sartorius'       ? 'selected' : ''}>🔬 Sartorius</option>
                                <option value="custom"  ${(field.scalePreset) === 'custom'            ? 'selected' : ''}>⚙️ Tùy chỉnh</option>
                            </select>
                            <div class="form-text" style="font-size: 0.65rem;">
                                Nút <i class="fas fa-balance-scale text-success"></i> sẽ xuất hiện cạnh ô nhập liệu ở chế độ Thực thi.
                            </div>
                        </div>
                    </div>
                </div>
            `;
        } else if (field.type === 'select') {

            const dsType = field.dataSource ? field.dataSource.type : 'manual';
            typeHtml += `
                <div class="mb-3">
                    <label class="small fw-bold text-muted text-uppercase mb-2"><i class="fas fa-database me-1"></i> Nguồn dữ liệu</label>
                    <select class="form-select form-select-sm mb-2" onchange="syncFieldConfig('${fieldId}', 'dataSource.type', this.value); selectField(null, '${fieldId}')">
                        <option value="manual" ${dsType === 'manual' ? 'selected' : ''}>Nhập thủ công</option>
                        <option value="database" ${dsType === 'database' ? 'selected' : ''}>Lấy từ cơ sở dữ liệu</option>
                    </select>
            `;

            if (dsType === 'manual') {
                typeHtml += `
                    <textarea class="form-control form-control-sm" rows="3" placeholder="Ví dụ: Đạt, Tốt, Không đạt" oninput="syncFieldConfig('${fieldId}', 'options', this.value)">${Array.isArray(field.options) ? field.options.join(', ') : (field.options || '')}</textarea>
                    <div class="form-text small" style="font-size: 0.7rem;">Mỗi lựa chọn cách nhau bởi dấu phẩy (,).</div>
                </div>`;
            } else {
                const ds = field.dataSource || {};
                typeHtml += `
                    <div class="border rounded p-2 bg-light">
                        <div class="mb-2">
                            <label class="small text-muted" style="font-size: 0.75em;">Tên Bảng (Table)</label>
                            <input type="text" class="form-control form-control-sm" placeholder="VD: deparments" value="${ds.table || ''}" oninput="syncFieldConfig('${fieldId}', 'dataSource.table', this.value)">
                        </div>
                        <div class="mb-2">
                            <label class="small text-muted" style="font-size: 0.75em;">Cột hiển thị (Label)</label>
                            <input type="text" class="form-control form-control-sm" placeholder="VD: name" value="${ds.labelCol || ''}" oninput="syncFieldConfig('${fieldId}', 'dataSource.labelCol', this.value)">
                        </div>
                        <div class="mb-2">
                            <label class="small text-muted" style="font-size: 0.75em;">Cột giá trị (Value) - Tùy chọn</label>
                            <input type="text" class="form-control form-control-sm" placeholder="Mặc định lấy Cột hiển thị" value="${ds.valueCol || ''}" oninput="syncFieldConfig('${fieldId}', 'dataSource.valueCol', this.value)">
                        </div>
                        <div class="mb-2">
                            <label class="small text-muted" style="font-size: 0.75em;">Điều kiện Where (Tùy chọn)</label>
                            <input type="text" class="form-control form-control-sm" placeholder="VD: active=1" value="${ds.where || ''}" oninput="syncFieldConfig('${fieldId}', 'dataSource.where', this.value)">
                        </div>
                    </div>
                </div>`;
            }
        }

        typeHtml += `
            <div class="card bg-light border-0 shadow-none mb-3">
                <div class="card-body p-3">
                    <label class="small fw-bold mb-2"><i class="fas fa-arrows-alt-h me-1"></i>Kích thước & Vị trí</label>
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="small text-muted" style="font-size: 0.75em;">Chiều rộng (px)</label>
                            <input type="number" class="form-control form-control-sm" placeholder="Mặc định" min="50"
                                   value="${(field.style && field.style.width) ? parseInt(field.style.width) : ''}" 
                                   oninput="const val = this.value ? this.value + 'px' : ''; syncFieldConfig('${fieldId}', 'style.width', val); const badge = document.querySelector('.ebmr-field-badge[data-field-id=\\'${fieldId}\\']'); if(badge) { if(val) badge.style.setProperty('width', val, 'important'); else badge.style.removeProperty('width'); }">
                        </div>
                        <div class="col-6">
                            <label class="small text-muted" style="font-size: 0.75em;">Lề trái (px)</label>
                            <input type="number" class="form-control form-control-sm" placeholder="Mặc định" min="0"
                                   value="${(field.style && field.style.marginLeft) ? parseInt(field.style.marginLeft) : ''}" 
                                   oninput="const val = this.value ? this.value + 'px' : ''; syncFieldConfig('${fieldId}', 'style.marginLeft', val); const badge = document.querySelector('.ebmr-field-badge[data-field-id=\\'${fieldId}\\']'); if(badge) { if(val) badge.style.setProperty('margin-left', val, 'important'); else badge.style.removeProperty('margin-left'); }">
                        </div>
                    </div>
                </div>
            </div>
        `;

        typeHtml += `
            <div class="mt-4 text-center">
                <button class="btn btn-sm btn-outline-primary w-100 mb-2" onclick="copyVariable('${fieldId}')"><i class="fas fa-copy me-1"></i> Sao chép cấu hình</button>
                <button class="btn btn-sm btn-outline-danger w-100" onclick="deleteDynamicField('${fieldId}')"><i class="fas fa-trash-alt me-1"></i> Xóa biến số</button>
            </div>
        `;

        body.innerHTML = typeHtml;

        // Auto-adjust height for formula textarea
        setTimeout(() => {
            const formulaInput = document.getElementById(`formula-input-${fieldId}`);
            if (formulaInput) {
                formulaInput.style.height = 'auto';
                formulaInput.style.height = (formulaInput.scrollHeight) + 'px';
            }
        }, 50);
    }

    /**
     * Đồng bộ cấu hình của một thẻ dữ liệu (Field) khi người dùng thay đổi trong bảng thuộc tính.
     * Cách hoạt động: Sử dụng kỹ thuật duyệt cây (path split) để cập nhật sâu vào các thuộc tính 
     * lồng nhau (như validation.min). Sau đó ép kiểu dữ liệu (số, mảng) tùy theo loại thuộc tính.
     * @param {string} fieldId - ID của thẻ dữ liệu.
     * @param {string} path - Đường dẫn đến thuộc tính (ví dụ: 'validation.min').
     * @param {any} value - Giá trị mới.
     */
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

    /**
     * Xóa một thẻ dữ liệu khỏi tài liệu và cấu hình hệ thống.
     * @param {string} fieldId - ID của thẻ cần xóa.
     */
    function deleteDynamicField(fieldId) {
        // Find which item/cell this field belongs to
        let found = false;
        items.forEach(item => {
            if (item.type === 'table' && item.data) {
                item.data.forEach((row, r) => {
                    row.forEach((cell, c) => {
                        if (cell.content && cell.content.includes(
                                `data-field-id="${fieldId}"`)) {
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

    let currentGfInsertIndex = null;

    /**
     * Mở modal để liên kết với một Biểu mẫu chung (General Form - GF).
     */
    function openLinkGfModal(insertIndex = null) {
        currentGfInsertIndex = insertIndex;
        if (window.bootstrap) {
            const modal = new bootstrap.Modal(document.getElementById('linkGfModal'));
            modal.show();
            fetchGfs();
        }
    }

    /**
     * Tải danh sách các Biểu mẫu chung (GF) từ máy chủ.
     */
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

    /**
     * Hiển thị danh sách GF vào trong modal để người dùng chọn.
     * @param {Array} gfs - Mảng danh sách các biểu mẫu.
     */
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

    /**
     * Lọc danh sách GF theo từ khóa tìm kiếm.
     * @param {string} query - Từ khóa tìm kiếm.
     */
    function filterGfs(query) {
        const filtered = allGfs.filter(t => t.name.toLowerCase().includes(query.toLowerCase()));
        renderGfList(filtered);
    }

    /**
     * Chèn một liên kết đến Biểu mẫu chung (GF) vào tài liệu hiện tại.
     * Cách hoạt động: Tạo một khối có type là 'linked-template', lưu templateId của biểu mẫu gốc 
     * để hệ thống có thể truy xuất và hiển thị nội dung khi cần thiết.
     * @param {number} templateId - ID của biểu mẫu GF.
     * @param {string} templateName - Tên biểu mẫu GF.
     */
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
            borderMode: 'visible',
            locked: false, // Để người dùng có thể xóa hoặc di chuyển block sau khi chèn
            section_id: window.activeSectionId || null
        };

        if (currentGfInsertIndex !== null) {
            // Determine section_id based on surrounding blocks if not already in activeSectionId mode
            if (!item.section_id && currentGfInsertIndex > 0) {
                item.section_id = items[currentGfInsertIndex - 1].section_id;
            } else if (!item.section_id && items.length > 0) {
                item.section_id = items[0].section_id;
            }
            items.splice(currentGfInsertIndex, 0, item);
        } else {
            items.push(item);
        }

        renderBlocks();

        const modalEl = document.getElementById('linkGfModal');
        if (modalEl) {
            const closeBtn = modalEl.querySelector('[data-dismiss="modal"], [data-bs-dismiss="modal"]');
            if (closeBtn) {
                closeBtn.click();
            } else if (window.bootstrap && bootstrap.Modal && typeof bootstrap.Modal.getInstance === 'function') {
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();
            } else if (window.jQuery) {
                $(modalEl).modal('hide');
            }
        }
    }

    /**
     * Bật/Tắt chế độ xem trước nội dung của biểu mẫu GF đang được liên kết.
     * @param {string} id - ID của khối liên kết.
     */
    function toggleGfPreview(id) {
        const item = items.find(i => i.id === id);
        if (!item) return;
        item.showPreview = !item.showPreview;
        renderBlocks();
    }

    /**
     * Tải nội dung chi tiết của GF và hiển thị bản xem trước.
     * Cách hoạt động: Sử dụng Fetch API để lấy dữ liệu khối từ máy chủ, sau đó lưu vào bộ nhớ đệm (cache) 
     * để tránh tải lại nhiều lần khi render giao diện.
     * @param {string} blockId - ID khối liên kết.
     * @param {number} templateId - ID của biểu mẫu GF gốc.
     */
    /**
     * Tải và hiển thị nội dung xem trước của biểu mẫu GF được liên kết.
     * Cách hoạt động: Sử dụng Fetch API để lấy dữ liệu khối từ máy chủ, sau đó lưu vào bộ nhớ đệm (cache) 
     * để tránh tải lại nhiều lần khi render giao diện.
     * @param {string} blockId - ID khối liên kết.
     * @param {number} templateId - ID của biểu mẫu GF gốc.
     */
    function fetchAndRenderGfPreview(blockId, templateId) {
        const container = document.getElementById(`preview-${blockId}`);
        if (!container) return;

        // Use cache if available
        if (window.gfPreviewCache && window.gfPreviewCache[templateId]) {
            renderGfPreviewContent(container, window.gfPreviewCache[templateId], window.gfFieldsCache ? window
                .gfFieldsCache[templateId] : {});
            return;
        }

        fetch(`/ebmr/templates/${templateId}/blocks`)
            .then(res => res.json())
            .then(data => {
                const blocks = data.blocks || data;
                const fields = data.fields || {};

                if (!window.gfPreviewCache) window.gfPreviewCache = {};
                if (!window.gfFieldsCache) window.gfFieldsCache = {};

                window.gfPreviewCache[templateId] = blocks;
                window.gfFieldsCache[templateId] = fields;

                // Merge into global fieldsConfig to support formulas and execution mode logic
                let targetFieldsConfig = null;
                if (typeof fieldsConfig !== 'undefined') {
                    targetFieldsConfig = fieldsConfig;
                } else {
                    if (!window.fieldsConfig) window.fieldsConfig = {};
                    targetFieldsConfig = window.fieldsConfig;
                }
                Object.assign(targetFieldsConfig, fields);

                renderGfPreviewContent(container, blocks, fields);
            })
            .catch(err => {
                console.error("GF Preview Error:", err);
                container.innerHTML = `<div class="text-danger small">Lỗi tải nội dung: ${err.message}</div>`;
            });
    }

    /**
     * Render nội dung chi tiết của GF vào container xem trước.
     * @param {HTMLElement} container - Vùng hiển thị xem trước.
     * @param {Array} blocks - Danh sách các khối nội dung của GF.
     * @param {Object} fields - Cấu hình biến số của GF.
     */
    function renderGfPreviewContent(container, blocks, fields = {}) {
        if (!blocks || blocks.length === 0) {
            container.innerHTML =
                '<div class="text-muted small italic text-center py-3">Biểu mẫu này chưa có nội dung.</div>';
            return;
        }

        let html = '';
        blocks.forEach(b => {
            if (b.type === 'static-text') {
                const content = typeof decorateContent === 'function' ? decorateContent(b.content || '',
                    fields) : (b.content || '');
                html += `<div class="mb-3 p-1 small" style="line-height: 1.6;">${content}</div>`;
            } else if (b.type === 'table') {
                const borderMode = b.borderMode || 'all';
                const borderClass = `border-mode-${borderMode}`;

                let thead = '';
                if (!b.hideHeader && b.columns) {
                    thead = `<thead><tr>${b.columns.map((c, cIdx) => {
                        const s = c.style || {};
                        const bg = s.backgroundColor || '';
                        const align = s.textAlign || '';
                        const fw = s.fontWeight || '';
                        const fs = s.fontStyle || '';
                        const td = s.textDecoration || '';
                        const fsz = s.fontSize || '';
                        const tc = s.textColor || '';
                        const wm = s.writingMode || '';
                        const tf = s.transform || '';
                        return `<th contenteditable="false" spellcheck="false" data-row="0" data-col="${cIdx}" class="table-header-cell" style="width: ${c.width || 'auto'}; background-color: ${bg}; text-align: ${align}; font-weight: ${fw}; font-style: ${fs}; text-decoration: ${td}; font-size: ${fsz}; color: ${tc}; writing-mode: ${wm};"><div class="header-content" style="transform: ${tf}; transform-origin: center center; display: inline-block; width: 100%;">${c.label || ''}</div></th>`;
                    }).join('')}</tr></thead>`;
                }

                let rowsHtml = '';
                const blockKey = b.uuid || b.id;
                const runDataForBlock = window.executionValues && window.executionValues[blockKey] ? window
                    .executionValues[blockKey] : {};

                for (let r = 0; r < (b.rows || 0); r++) {
                    let cellsHtml = '';
                    if (!b.data || !b.data[r]) continue;
                    const rowH = (b.rowHeights && b.rowHeights[r]) ? b.rowHeights[r] : 'auto';
                    for (let c = 0; c < (b.cols || 0); c++) {
                        if (!b.data[r][c] || typeof b.data[r][c] !== 'object') {
                            b.data[r][c] = {
                                content: '',
                                rs: 1,
                                cs: 1,
                                hidden: false
                            };
                        }
                        const cell = b.data[r][c];
                        if (cell.hidden) continue;

                        const cellWidth = (b.columns && b.columns[c] && b.columns[c].width) ? b.columns[c]
                            .width : 'auto';
                        const cellBg = (cell.backgroundColor) ? cell.backgroundColor : '';

                        let displayContent = typeof decorateContent === 'function' ? decorateContent(cell
                            .content, fields) : cell.content;
                        if (displayContent === null || displayContent === 'null' || displayContent ===
                            undefined) {
                            displayContent = '';
                        }

                        let cellClass = "";
                        let onclickAttr = "";

                        if (window.isExecutionMode) {
                            const runVal = runDataForBlock[`${r}_${c}`];
                            if (displayContent.includes('[Nhập dữ liệu]')) {
                                cellClass = "execution-input-cell";
                                onclickAttr =
                                    `onclick="openExecutionInputModal('${blockKey}', ${r}, ${c}, 'text')"`;
                                displayContent = runVal ? runVal :
                                    `<span class="execution-badge input"><i class="fas fa-edit"></i> [Nhập dữ liệu]</span>`;
                            } else if (displayContent.includes('[Ký tên]')) {
                                cellClass = "execution-input-cell";
                                onclickAttr =
                                    `onclick="openExecutionInputModal('${blockKey}', ${r}, ${c}, 'signature')"`;
                                displayContent = runVal ?
                                    `<div class="e-signature-done"><i class="fas fa-check-circle text-success me-1"></i>${runVal}</div>` :
                                    `<span class="execution-badge signature"><i class="fas fa-pen"></i> [Ký tên]</span>`;
                            }
                        }

                        cellsHtml +=
                            `<td contenteditable="false" spellcheck="false" data-row="${r+1}" data-col="${c}" rowspan="${cell.rs || 1}" colspan="${cell.cs || 1}" ${onclickAttr} class="${cellClass}" style="width: ${cellWidth}; height: ${rowH}; background-color: ${cellBg}; text-align: ${cell.textAlign || ''}; font-weight: ${cell.fontWeight || ''}; font-style: ${cell.fontStyle || ''}; text-decoration: ${cell.textDecoration || ''}; font-size: ${cell.fontSize || ''}; color: ${cell.textColor || ''}; text-transform: ${cell.textTransform || ''}; writing-mode: ${cell.writingMode || ''};"><div class="cell-wrapper" style="transform: ${cell.transform || ''}; transform-origin: center center; display: inline-block; width: 100%;">${displayContent}</div></td>`;
                    }
                    rowsHtml += `<tr>${cellsHtml}</tr>`;
                }

                html +=
                    `<div class="table-responsive-wrapper"><table class="mini-table ${borderClass}">${thead}<tbody>${rowsHtml}</tbody></table></div>`;
            } else if (b.type === 'signature') {
                html += `<div class="mb-3 p-2 border rounded bg-light small d-flex align-items-center">
                            <div class="bg-white rounded-circle d-flex align-items-center justify-content-center me-2 shadow-sm" style="width: 30px; height: 30px;">
                                <i class="fas fa-signature text-primary"></i>
                            </div>
                            <div class="fw-bold text-muted">${b.label || 'Chữ ký'}</div>
                        </div>`;
            } else if (b.type === 'section') {
                html += `<div class="mt-4 mb-2 pb-1 border-bottom d-flex align-items-center">
                            <i class="fas fa-layer-group text-info me-2"></i>
                            <span class="fw-bold text-uppercase small text-navy">${b.label || 'Phân đoạn'}</span>
                        </div>`;
            }
        });
        container.innerHTML = html;
    }

    /**
     * Chuyển đổi giữa chế độ Xem theo Phân đoạn và Xem toàn bộ hồ sơ.
     */
    function toggleViewMode() {
        const templateId = '{{ $template->id }}';
        const catId = "{{ $template->caterogy_id ?? 0 }}";

        // Chuyển đổi trạng thái Xem tất cả / Xem 1 phân đoạn
        window.isViewAllMode = !window.isViewAllMode;

        if (!window.isViewAllMode) {
            // Nếu chuyển sang xem 1 phân đoạn, đảm bảo activeSectionId trỏ vào 1 công đoạn thực tế (không phải virtual header)
            if (!window.activeSectionId || window.activeSectionId === catId || window.activeSectionId === catId + '_0') {
                let lastSection = localStorage.getItem('ebmr_last_section_' + templateId);
                // Kiểm tra nếu lastSection cũng trỏ vào header thì bỏ qua để tìm công đoạn thực
                if (lastSection === catId || lastSection === catId + '_0') lastSection = null;

                if (!lastSection && typeof items !== 'undefined' && items.length > 0) {
                    const firstStageBlock = items.find(i => i.section_id && !i.isVirtual && i.type === 'section' && i.stage_code !== undefined);
                    if (firstStageBlock) lastSection = firstStageBlock.section_id;
                }
                if (lastSection) window.activeSectionId = lastSection;
            }
        }

        // Lưu trạng thái cuối cùng nếu có (chỉ lưu nếu là công đoạn thực)
        if (window.activeSectionId && window.activeSectionId !== catId && window.activeSectionId !== catId + '_0') {
            localStorage.setItem('ebmr_last_section_' + templateId, window.activeSectionId);
        }

        renderBlocks();

        // Cập nhật giao diện nút bấm
        const toggleBtn = document.getElementById('viewModeToggle');
        if (toggleBtn) {
            if (window.isViewAllMode) {
                toggleBtn.innerHTML = '<i class="fas fa-compress-arrows-alt"></i>';
                toggleBtn.classList.remove('btn-outline-info');
                toggleBtn.classList.add('btn-info');
                toggleBtn.title = "Chuyển sang xem 1 phân đoạn";
            } else {
                toggleBtn.innerHTML = '<i class="fas fa-expand-arrows-alt"></i>';
                toggleBtn.classList.remove('btn-info');
                toggleBtn.classList.add('btn-outline-info');
                toggleBtn.title = "Chuyển sang xem tất cả";
            }
        }

        // Thông báo nhanh
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 2000,
            timerProgressBar: true
        });
        Toast.fire({
            icon: 'info',
            title: window.isViewAllMode ? 'Chế độ xem tất cả' : 'Chế độ xem 1 phân đoạn'
        });
    }
    // --- Format Painter Logic ---
    let isFormatPainterActive = false;
    let storedFormat = null;

    /**
     * Bật/Tắt công cụ Sao chép định dạng (Format Painter).
     * Cách hoạt động: 
     * 1. Khi bật: Lấy tất cả style (màu, font, bold...) tại vị trí con trỏ hoặc khối hiện tại.
     * 2. Lưu các style này vào biến `storedFormat`.
     * 3. Khi người dùng click hoặc quét vùng khác, áp dụng style này và tự động tắt công cụ.
     */
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

        if (targetEl && (targetEl.closest('[contenteditable="true"]') || targetEl.getAttribute('contenteditable') ===
                'true')) {
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

    /**
     * Kích hoạt chế độ Sao chép định dạng, đổi con trỏ chuột thành dạng copy.
     */
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

    /**
     * Hủy bỏ chế độ Sao chép định dạng.
     */
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

    /**
     * Xử lý khi người dùng thả chuột để áp dụng định dạng đã sao chép vào vùng chọn mới.
     * @param {MouseEvent} e - Sự kiện chuột.
     */
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

    /**
     * Xóa toàn bộ định dạng của vùng văn bản đang chọn hoặc khối đang chọn.
     */
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

    /**
     * Sử dụng AI để dịch nội dung của một khối hoặc một ô bảng sang Tiếng Anh.
     * @param {string} blockId - ID của khối cần dịch.
     * @param {boolean} isWholeBlock - Dịch toàn bộ khối (true) hay chỉ ô đang chọn (false).
     */
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
                Swal.fire('Lưu ý',
                    'Không tìm thấy dữ liệu gốc để dịch ô này. Vui lòng nhấn LƯU HỒ SƠ trước khi dịch.',
                    'warning');
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
    /**
     * Mở modal nhập liệu an toàn cho các biến số ở chế độ chạy thử
     */
    window.openVariableInputModal = function(fieldId) {
        if (!window.isExecutionMode) return;
        if (window.isReadOnly) return;

        const field = fieldsConfig[fieldId];
        if (!field) return;

        let currentVal = window.executionValues[fieldId] || '';
        if (currentVal && typeof currentVal === 'object' && currentVal.hasOwnProperty('default')) {
            currentVal = currentVal.default;
        } else if (currentVal && typeof currentVal === 'object' && !Array.isArray(currentVal)) {
            const keys = Object.keys(currentVal);
            if (keys.length > 0) currentVal = currentVal[keys[0]];
        }
        let inputType = 'text';
        let inputAttributes = {};

        // Cấu hình loại input và validation
        const importantVar = (window.importantVars || []).find(v => v.id == field.important_var_id);
        const isCritical = !!importantVar;

        if (field.type === 'number') {
            inputType = 'number';
            if (field.validation) {
                if (!field.validation.allow_out_of_bounds) {
                    if (field.validation.min !== null && field.validation.min !== '') inputAttributes.min = field.validation.min;
                    if (field.validation.max !== null && field.validation.max !== '') inputAttributes.max = field.validation.max;
                }
                if (field.validation.decimal_places && parseInt(field.validation.decimal_places) > 0) {
                    inputAttributes.step = '0.' + '0'.repeat(parseInt(field.validation.decimal_places) - 1) + '1';
                } else {
                    inputAttributes.step = 'any';
                }
            }
        } else if (field.type === 'date') {
            inputType = 'text'; // Fallback for SweetAlert2 older versions
            inputAttributes.type = 'date';
            
            // Luôn luôn hiển thị giá trị input là ngày hiện tại (now)
            const d = new Date();
            currentVal = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
        } else if (field.type === 'text') {
            inputType = 'textarea';
            inputAttributes.rows = 4;
            inputAttributes.placeholder = 'Nhập nội dung văn bản tại đây...';
        }

        // Build HTML for instruction and hints
        let instructionHtml = '';
        if (field.instruction) {
            instructionHtml += `<div class="alert alert-info text-start small mb-3" style="font-size: 0.85rem; line-height: 1.4; border-left: 4px solid #0dcaf0;">
                                    <i class="fas fa-info-circle me-2"></i><b>Hướng dẫn:</b> ${field.instruction}
                                </div>`;
        }

        let hints = [];
        if (field.type === 'number' && field.validation) {
            if (field.validation.min !== null && field.validation.min !== undefined && field.validation.min !== '')
                hints.push(`Tối thiểu: <b>${field.validation.min}</b>`);
            if (field.validation.max !== null && field.validation.max !== undefined && field.validation.max !== '')
                hints.push(`Tối đa: <b>${field.validation.max}</b>`);
        }
        if (field.defaultValue !== null && field.defaultValue !== undefined && field.defaultValue !== '') {
            hints.push(`Mặc định: <b>${field.defaultValue}</b>`);
        }

        if (hints.length > 0) {
            instructionHtml += `<div class="text-start mb-2 small text-muted">
                                    <i class="fas fa-lightbulb me-1 text-warning"></i> Gợi ý: ${hints.join(' | ')}
                                </div>`;
        }

        // Title with logo (Rebalanced layout - More Compact & Informative)
        const titleHtml = `
            <div class="d-flex align-items-center w-100 px-1 py-0" style="border-bottom: 1px solid #eef2f7; margin-bottom: 8px; padding-bottom: 8px;">
                <img src="{{ asset('img/logo/Stella-Pharm-logo.png') }}" style="height: 35px; width: auto; object-fit: contain; margin-right: 12px;" onerror="this.style.display='none'">
                <div class="text-start flex-grow-1">
                    <div class="fw-bold" style="font-size: 1.35rem; color: #0f172a; line-height: 1.1; letter-spacing: -0.01em;">${field.label || 'Nhập dữ liệu'}</div>
                </div>
                ${isCritical ? `
                    <div class="ms-2 text-end">
                        <span class="badge bg-danger rounded-pill px-2 py-1 shadow-sm animate__animated animate__pulse animate__infinite" style="font-size: 0.65rem; border: 1.5px solid rgba(255,255,255,0.2); white-space: nowrap;">
                            <i class="fas fa-exclamation-triangle me-1"></i> ${importantVar.name} ${importantVar.description ? ` - ${importantVar.description}` : ''}
                        </span>
                    </div>` : ''}
            </div>
        `;

        Swal.fire({
            title: titleHtml,
            html: instructionHtml,
            input: inputType,
            inputValue: currentVal,
            inputAttributes: inputAttributes,
            showCancelButton: true,
            confirmButtonText: 'Xác nhận',
            cancelButtonText: 'Hủy',
            width: field.type === 'text' ? '600px' : '450px',
            background: isCritical ? '#fff5f5' : '#ffffff',
            padding: '0 1rem 1.25rem', // Reduced top padding
            customClass: {
                popup: (isCritical ? 'border-danger border-2 shadow-lg' : 'border-0 shadow-lg') +
                    ' rounded-4',
                title: 'p-0 w-100 mt-2', // Add slight top margin to title for balance
                input: 'form-control shadow-sm'
            },
            inputValidator: (value) => {
                if (field.validation && field.validation.required && !value) {
                    return 'Vui lòng không để trống ô này!';
                }
                if (field.type === 'number' && value !== '') {
                    const num = Number(value);
                    if (field.validation && !field.validation.allow_out_of_bounds) {
                        if (field.validation.min !== null && field.validation.min !== '' && num <
                            parseFloat(field.validation.min)) {
                            return 'Giá trị phải lớn hơn hoặc bằng ' + field.validation.min;
                        }
                        if (field.validation.max !== null && field.validation.max !== '' && num >
                            parseFloat(field.validation.max)) {
                            return 'Giá trị phải nhỏ hơn hoặc bằng ' + field.validation.max;
                        }
                    }
                }
            },
            didOpen: () => {
                if (field.type === 'date') {
                    const input = Swal.getInput();
                    if (input) input.type = 'date';
                }
            },
            onOpen: () => { // Hỗ trợ phiên bản SweetAlert2 cũ
                if (field.type === 'date') {
                    const input = Swal.getInput();
                    if (input) input.type = 'date';
                }
            }
        }).then((result) => {
            // Hỗ trợ cả SweetAlert2 bản mới (isConfirmed) và bản cũ (value)
            const isConfirmed = result.isConfirmed || (result.value !== undefined && !result.dismiss);
            
            if (isConfirmed) {
                let finalValue = result.value;
                
                console.log("Modal confirmed with value:", finalValue);
                console.log("fieldId:", fieldId);

                // Định dạng lại YYYY-MM-DD từ datepicker thành DD/MM/YYYY để hiển thị và lưu thống nhất
                if (field.type === 'date' && finalValue) {
                    const parts = finalValue.split('-');
                    if (parts.length === 3) {
                        finalValue = `${parts[2]}/${parts[1]}/${parts[0]}`;
                    }
                }

                // Đảm bảo cấu trúc đồng nhất với Server (cell_id = 'default' cho các biến đơn)
                if (typeof window.executionValues[fieldId] !== 'object' || window.executionValues[fieldId] === null) {
                    window.executionValues[fieldId] = {};
                }
                window.executionValues[fieldId]['default'] = finalValue;

                // Ghi nhận thông tin người ký và thời gian tương tự tự động điền để phục vụ kiểm toán (BMR)
                if (field.type === 'date') {
                    if (!window.executionValues[fieldId]._meta) window.executionValues[fieldId]._meta = {};
                    window.executionValues[fieldId]._meta['default'] = {
                        by: '{{ session("user.fullName") ?? session("user.username") ?? "" }}',
                        at: new Date().toISOString()
                    };
                }

                console.log("window.executionValues after update:", window.executionValues);

                if (typeof window.recalculateAllFormulas === 'function') window.recalculateAllFormulas();
                renderBlocks();
                if (field.block_id && typeof syncLinkedCharts === 'function') {
                    syncLinkedCharts(field.block_id);
                }
            }
        }).catch(err => {
            console.error("Error in modal promise:", err);
        });
    };

    /**
     * ABBREVIATION (DANH MỤC CHỮ VIẾT TẮT)
     */
    function addAbbreviation() {
        const selection = window.getSelection().toString().trim();
        if (!selection) {
            Swal.fire('Lỗi', 'Vui lòng bôi đen (chọn) một từ viết tắt trong tài liệu trước khi bấm nút này.', 'warning');
            return;
        }

        const wordInput = document.getElementById('abbrWord');
        const meaningInput = document.getElementById('abbrMeaning');
        if (wordInput) wordInput.value = selection;
        if (meaningInput) meaningInput.value = '';

        if (window.jQuery) {
            $('#abbreviationModal').modal('show');
        }
    }

    function saveAbbreviation() {
        const word = document.getElementById('abbrWord').value.trim();
        const meaning = document.getElementById('abbrMeaning').value.trim();

        if (!word || !meaning) {
            Swal.fire('Lỗi', 'Vui lòng nhập đầy đủ ý nghĩa của từ viết tắt.', 'warning');
            return;
        }

        // Tìm block Danh mục chữ viết tắt đã có chưa
        let abbrevTable = items.find(item => item.isAbbreviationTable === true);

        if (!abbrevTable) {
            const catId = "{{ $template->caterogy_id ?? 0 }}";
            // Nếu chưa có, tạo bảng mới
            abbrevTable = {
                id: 'blk_abbrev_' + Date.now(),
                type: 'table',
                label: 'DANH MỤC CHỮ VIẾT TẮT',
                isAbbreviationTable: true,
                rows: 1,
                cols: 3,
                borderMode: 'visible',
                hideHeader: false,
                section_id: catId,
                columns: [
                    { label: 'STT', type: 'text', width: '10%' },
                    { label: 'Chữ viết tắt', type: 'text', width: '30%' },
                    { label: 'Ý nghĩa', type: 'text', width: '60%' }
                ],
                data: [
                    [
                        { content: '1', rs: 1, cs: 1, textAlign: 'center' },
                        { content: word, rs: 1, cs: 1, textAlign: 'center', fontWeight: 'bold' },
                        { content: meaning, rs: 1, cs: 1, textAlign: 'left' }
                    ]
                ],
                dirty: true
            };
            
            // Tìm vị trí cuối cùng của các block ảo (virtual blocks) để chèn ngay dưới đó
            let insertIdx = 0;
            for (let i = 0; i < items.length; i++) {
                if (items[i].isVirtual) {
                    insertIdx = i + 1;
                }
            }
            items.splice(insertIdx, 0, abbrevTable);
            saveStateDebounced();
        } else {
            // Nếu có rồi, thêm dòng mới
            abbrevTable.section_id = "{{ $template->caterogy_id ?? 0 }}";
            const stt = abbrevTable.data.length + 1;
            
            // Kiểm tra xem từ viết tắt đã tồn tại chưa
            const exists = abbrevTable.data.some(row => {
                if (row[1] && row[1].content) {
                    const textContent = row[1].content.replace(/<[^>]*>?/gm, '').trim();
                    return textContent.toLowerCase() === word.toLowerCase();
                }
                return false;
            });

            if (exists) {
                Swal.fire('Lỗi', 'Từ viết tắt này đã tồn tại trong danh mục!', 'warning');
                return;
            }

            abbrevTable.data.push([
                { content: stt.toString(), rs: 1, cs: 1, textAlign: 'center' },
                { content: word, rs: 1, cs: 1, textAlign: 'center', fontWeight: 'bold' },
                { content: meaning, rs: 1, cs: 1, textAlign: 'left' }
            ]);
            abbrevTable.rows = abbrevTable.data.length;
            abbrevTable.dirty = true;
            saveStateDebounced();
        }

        if (window.jQuery) {
            $('#abbreviationModal').modal('hide');
        }
        
        renderBlocks();
        Swal.fire({
            title: 'Thành công',
            text: 'Đã thêm vào Danh mục chữ viết tắt',
            icon: 'success',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 2000
        });
    }

    window.startContinuousBeep = function() {
        window.stopContinuousBeep(); // Dọn dẹp trước nếu có
        try {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            if (!AudioContext) return;
            const audioCtx = new AudioContext();
            
            function playBeep() {
                try {
                    // Còi hú đặc chủng phòng sản xuất (Dual Sawtooth + Frequency Sweep)
                    const now = audioCtx.currentTime;
                    
                    // Tạo 2 bộ phát tần số cao để cộng hưởng giao thoa âm thanh (Acoustic Beating) cực kỳ chói tai
                    const osc1 = audioCtx.createOscillator();
                    const osc2 = audioCtx.createOscillator();
                    const gainNode = audioCtx.createGain();
                    
                    osc1.connect(gainNode);
                    osc2.connect(gainNode);
                    gainNode.connect(audioCtx.destination);
                    
                    osc1.type = 'sawtooth';
                    osc2.type = 'sawtooth';
                    
                    // Tần số cơ bản 2000Hz & 2025Hz (dải tần tai người nhạy cảm nhất)
                    osc1.frequency.setValueAtTime(2000, now);
                    osc2.frequency.setValueAtTime(2025, now);
                    
                    // Quét tần số cực nhanh lên 2800Hz (Hiệu ứng tiếng hú còi khẩn cấp)
                    osc1.frequency.exponentialRampToValueAtTime(2800, now + 0.25);
                    osc2.frequency.exponentialRampToValueAtTime(2825, now + 0.25);
                    
                    // Tăng âm lượng tối đa (Gain = 0.45)
                    gainNode.gain.setValueAtTime(0.45, now);
                    gainNode.gain.linearRampToValueAtTime(0.45, now + 0.2);
                    gainNode.gain.exponentialRampToValueAtTime(0.001, now + 0.3);
                    
                    osc1.start(now);
                    osc2.start(now);
                    
                    osc1.stop(now + 0.3);
                    osc2.stop(now + 0.3);
                } catch(e) { console.log('Audio loop error', e); }
            }
            playBeep();
            window._alertBeepInterval = setInterval(playBeep, 700); // Lặp liên tục dồn dập (mỗi 0.7 giây)
        } catch(e) { console.log('Beeper init error', e); }
    };

    window.stopContinuousBeep = function() {
        if (window._alertBeepInterval) {
            clearInterval(window._alertBeepInterval);
            window._alertBeepInterval = null;
        }
    };

    let dateClickTimer = null;
    window.handleDateVariableClick = function(event, fieldId, hasDefaultNow) {
        if (event) event.stopPropagation();
        if (!window.isExecutionMode) return;
        if (window.isReadOnly) return;
        
        // Kiểm tra hasDefaultNow động từ fieldsConfig đề phòng trường hợp sidebar thiết lập giá trị mặc định "now" mà chưa re-render block
        const field = fieldsConfig[fieldId];
        const isNow = hasDefaultNow || (field && field.defaultValue && field.defaultValue.toLowerCase() === 'now');
        
        if (!isNow) {
            openVariableInputModal(fieldId);
            return;
        }
        
        if (dateClickTimer) {
            clearTimeout(dateClickTimer);
            dateClickTimer = null;
            autoFillDateVariable(fieldId);
        } else {
            dateClickTimer = setTimeout(() => {
                dateClickTimer = null;
                openVariableInputModal(fieldId);
            }, 450);
        }
    };

    window.autoFillDateVariable = function(fieldId) {
        if (!window.isExecutionMode) return;
        if (window.isReadOnly) return;

        const now = new Date();
        const timeString = `${now.getHours().toString().padStart(2, '0')}:${now.getMinutes().toString().padStart(2, '0')} ${now.getDate().toString().padStart(2, '0')}/${(now.getMonth()+1).toString().padStart(2, '0')}/${now.getFullYear()}`;

        if (!window.executionValues) window.executionValues = {};
        if (typeof window.executionValues[fieldId] !== 'object' || window.executionValues[fieldId] === null) {
            window.executionValues[fieldId] = {};
        }
        window.executionValues[fieldId]['default'] = timeString;

        if (!window.executionValues[fieldId]._meta) window.executionValues[fieldId]._meta = {};
        window.executionValues[fieldId]._meta['default'] = {
            by: '{{ session("user.fullName") ?? session("user.username") ?? "" }}',
            at: new Date().toISOString()
        };

        if (typeof window.recalculateAllFormulas === 'function') window.recalculateAllFormulas();
        if (typeof renderBlocks === 'function') renderBlocks();
        const field = fieldsConfig[fieldId];
        if (field && field.block_id && typeof syncLinkedCharts === 'function') {
            syncLinkedCharts(field.block_id);
        }
    };

    function autoFillTime(blockId, r, c) {
        if (!window.isExecutionMode) return;
        
        const now = new Date();
        const timeString = `${now.getHours().toString().padStart(2, '0')}:${now.getMinutes().toString().padStart(2, '0')} ${now.getDate().toString().padStart(2, '0')}/${(now.getMonth()+1).toString().padStart(2, '0')}/${now.getFullYear()}`;
        
        if (!window.executionValues) window.executionValues = {};
        if (!window.executionValues[blockId]) window.executionValues[blockId] = {};
        
        window.executionValues[blockId][`${r}_${c}`] = timeString;
        
        if (!window.executionValues[blockId]._meta) window.executionValues[blockId]._meta = {};
        window.executionValues[blockId]._meta[`${r}_${c}`] = {
            by: '{{ session("user.fullName") ?? session("user.username") ?? "" }}',
            at: new Date().toISOString()
        };
        
        if (typeof renderBlocks === 'function') renderBlocks();
        
        // Cập nhật biểu đồ nếu bảng này có liên kết
        if (typeof syncLinkedCharts === 'function') syncLinkedCharts(blockId);

        // Logic nhắc nhở (Timer & Countdown bar)
        let alertMsg = 'Đã tự động lấy giờ hệ thống';
        try {
            let itemFreq = null;
            // Lấy từ biến items (Chế độ Designer Preview)
            if (typeof items !== 'undefined') {
                const itm = items.find(i => i.id === blockId || i.uuid === blockId);
                if (itm && itm.freq_minutes) itemFreq = parseInt(itm.freq_minutes);
            } else if (window.items) {
                const itm = window.items.find(i => i.id === blockId || i.uuid === blockId);
                if (itm && itm.freq_minutes) itemFreq = parseInt(itm.freq_minutes);
            }
            
            // Fallback: Tìm thẻ span trong table cell để lấy data-freq (Hỗ trợ chế độ Execute)
            if (!itemFreq) {
                const cellBadge = document.querySelector(`td[data-row="${r+1}"][data-col="${c}"] .execution-badge.time`);
                if (cellBadge && cellBadge.getAttribute('data-freq')) {
                    itemFreq = parseInt(cellBadge.getAttribute('data-freq'));
                }
            }
            
            console.log("Timer debug: blockId=", blockId, "itemFreq=", itemFreq);
            
            if (itemFreq) {
                const freqMs = itemFreq * 60 * 1000;
                
                // Khởi tạo hoặc xóa đếm ngược cũ
                if (!window._activeCountdowns) window._activeCountdowns = {};
                window._activeCountdowns[blockId] = {
                    startTime: Date.now(),
                    freqMs: freqMs
                };
                
                // Hiển thị thanh tiến trình
                const container = document.getElementById(`countdown-container-${blockId}`);
                if (container) container.style.display = 'block';
                
                if (!window._countdownIntervals) window._countdownIntervals = {};
                if (window._countdownIntervals[blockId]) {
                    clearInterval(window._countdownIntervals[blockId]);
                }
                
                const updateCountdown = () => {
                    const state = window._activeCountdowns[blockId];
                    if (!state) return;
                    
                    const elapsed = Date.now() - state.startTime;
                    const remaining = state.freqMs - elapsed;
                    
                    const bar = document.getElementById(`countdown-bar-${blockId}`);
                    const text = document.getElementById(`countdown-text-${blockId}`);
                    
                    if (remaining <= 0) {
                        if (bar) {
                            bar.style.width = '0%';
                            bar.className = 'progress-bar progress-bar-striped progress-bar-animated bg-danger';
                        }
                        if (text) text.innerText = 'Đã đến giờ lấy mẫu!';
                        clearInterval(window._countdownIntervals[blockId]);
                        delete window._activeCountdowns[blockId];
                        return;
                    }
                    
                    const percent = (remaining / state.freqMs) * 100;
                    if (bar) {
                        bar.style.width = `${percent}%`;
                        if (percent > 50) {
                            bar.className = 'progress-bar progress-bar-striped progress-bar-animated bg-success';
                        } else if (percent > 20) {
                            bar.className = 'progress-bar progress-bar-striped progress-bar-animated bg-warning';
                        } else {
                            bar.className = 'progress-bar progress-bar-striped progress-bar-animated bg-danger';
                        }
                    }
                    
                    if (text) {
                        const totalSeconds = Math.ceil(remaining / 1000);
                        const mins = Math.floor(totalSeconds / 60).toString().padStart(2, '0');
                        const secs = (totalSeconds % 60).toString().padStart(2, '0');
                        text.innerText = `${mins}:${secs}`;
                    }
                };
                
                updateCountdown();
                window._countdownIntervals[blockId] = setInterval(updateCountdown, 1000);
                
                // Xóa timer kêu cũ nếu có
                if (window._sampleTimers && window._sampleTimers[blockId]) {
                    clearTimeout(window._sampleTimers[blockId]);
                }
                if (!window._sampleTimers) window._sampleTimers = {};
                
                window._sampleTimers[blockId] = setTimeout(() => {
                    // Bắt đầu còi hú liên tục
                    window.startContinuousBeep();
                    
                    Swal.fire({
                        title: 'Đến giờ lấy mẫu!',
                        html: `Đã qua <b>${itemFreq} phút</b> kể từ lần ghi nhận trước.<br>Vui lòng tiến hành lấy mẫu và cân trọng lượng!`,
                        icon: 'warning',
                        confirmButtonText: 'Đã hiểu',
                        allowOutsideClick: false,
                        allowEscapeKey: false
                    }).then((result) => {
                        window.stopContinuousBeep();
                    });
                }, freqMs); // Chờ freqMs
                
                alertMsg = `Đã lấy giờ. Hệ thống sẽ nhắc nhở sau ${itemFreq} phút.`;
            }
        } catch(e) { console.error(e); }

        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: alertMsg,
            showConfirmButton: false,
            timer: 3000
        });
    }

    function autoFillExecutor(blockId, r, c) {
        if (!window.isExecutionMode) return;
        
        const currentUserSignature = @json(session('user.signature_image') ?? session('user')['signature_image'] ?? null);
        const currentUser = '{{ session("user.fullName") ?? session("user.username") ?? "Người thực hiện" }}';
        const signatureVal = currentUserSignature ? currentUserSignature : currentUser;
        
        if (!window.executionValues) window.executionValues = {};
        if (!window.executionValues[blockId]) window.executionValues[blockId] = {};
        
        window.executionValues[blockId][`${r}_${c}`] = signatureVal;
        
        if (!window.executionValues[blockId]._meta) window.executionValues[blockId]._meta = {};
        window.executionValues[blockId]._meta[`${r}_${c}`] = {
            by: currentUser,
            at: new Date().toISOString()
        };
        
        if (typeof renderBlocks === 'function') renderBlocks();
    }

    function openCheckerAuthModal(blockId, r, c) {
        if (!window.isExecutionMode) return;
        
        document.getElementById('checkerBlockId').value = blockId;
        document.getElementById('checkerRowIdx').value = r;
        document.getElementById('checkerColIdx').value = c;
        
        document.getElementById('checkerUsername').value = '';
        document.getElementById('checkerPassword').value = '';
        document.getElementById('checkerAuthError').classList.add('d-none');
        
        $('#checkerAuthModal').modal('show');
        
        setTimeout(() => {
            document.getElementById('checkerUsername').focus();
        }, 500);
    }

    function submitCheckerAuth() {
        const blockId = document.getElementById('checkerBlockId').value;
        const r = document.getElementById('checkerRowIdx').value;
        const c = document.getElementById('checkerColIdx').value;
        
        const username = document.getElementById('checkerUsername').value.trim();
        const password = document.getElementById('checkerPassword').value;
        const errorEl = document.getElementById('checkerAuthError');
        
        if (!username || !password) {
            errorEl.innerText = 'Vui lòng nhập tài khoản và mật khẩu.';
            errorEl.classList.remove('d-none');
            return;
        }

        const btn = document.querySelector('#checkerAuthModal .btn-primary');
        const oldText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xác thực...';
        btn.disabled = true;

        fetch('{{ route("pages.ebmr.verifyChecker") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').content : ''
            },
            body: JSON.stringify({ username: username, password: password })
        })
        .then(res => res.json())
        .then(data => {
            btn.innerHTML = oldText;
            btn.disabled = false;
            
            if (data.success) {
                $('#checkerAuthModal').modal('hide');
                
                if (!window.executionValues) window.executionValues = {};
                if (!window.executionValues[blockId]) window.executionValues[blockId] = {};
                
                window.executionValues[blockId][`${r}_${c}`] = data.signature_image ? data.signature_image : data.fullName;
                
                if (!window.executionValues[blockId]._meta) window.executionValues[blockId]._meta = {};
                window.executionValues[blockId]._meta[`${r}_${c}`] = {
                    by: data.fullName,
                    at: new Date().toISOString()
                };
                
                if (typeof renderBlocks === 'function') renderBlocks();
                
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: `Đã xác thực: ${data.fullName}`,
                    showConfirmButton: false,
                    timer: 2000
                });
            } else {
                errorEl.innerText = data.message || 'Xác thực thất bại.';
                errorEl.classList.remove('d-none');
            }
        })
        .catch(err => {
            console.error('Lỗi xác thực:', err);
            btn.innerHTML = oldText;
            btn.disabled = false;
            errorEl.innerText = 'Có lỗi xảy ra, vui lòng thử lại.';
            errorEl.classList.remove('d-none');
        });
    }

    /**
     * Biến chuỗi ký tự đang chọn thành liên kết tài liệu mạng.
     */
    function insertDocumentNetworkLink() {
        const selection = window.getSelection();
        if (selection.rangeCount === 0) return;
        
        const range = selection.getRangeAt(0);
        const selectedText = range.toString().trim();

        if (!selectedText) {
            if (typeof toastr !== 'undefined') {
                toastr.warning("Vui lòng bôi chọn mã tài liệu (ví dụ: SSP-SOP-026) trước khi liên kết tài liệu mạng!");
            } else {
                alert("Vui lòng bôi chọn mã tài liệu (ví dụ: SSP-SOP-026) trước khi liên kết tài liệu mạng!");
            }
            return;
        }

        // Lưu trữ vùng chọn để phục hồi sau khi gọi Ajax bất đồng bộ kết thúc
        const savedRange = range.cloneRange();
        const originalText = selectedText;
        
        // Chuẩn bị URL kiểm tra sự tồn tại của tài liệu
        const docCode = encodeURIComponent(selectedText);
        const checkUrl = `/ebmr/document/check-exists/${docCode}`;

        // Đổi con trỏ chuột sang trạng thái chờ xử lý
        document.body.style.cursor = 'wait';

        // Gọi Ajax kiểm tra ngầm
        $.ajax({
            url: checkUrl,
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                // Khôi phục con trỏ chuột về mặc định
                document.body.style.cursor = 'default';

                if (response && response.exists) {
                    // Phục hồi lại vùng chọn ban đầu để chèn thẻ liên kết HTML
                    const sel = window.getSelection();
                    sel.removeAllRanges();
                    sel.addRange(savedRange);

                    // Tạo thẻ liên kết HTML trỏ tới route viewDocumentByCode
                    const linkHtml = `<a href="/ebmr/document/view-by-code/${docCode}" class="ebmr-doc-link" target="_blank" data-doc-code="${originalText}">${originalText}</a>`;

                    // Chèn thẻ liên kết HTML vào văn bản
                    document.execCommand('insertHTML', false, linkHtml);

                    // Kích hoạt đồng bộ hóa dữ liệu (trigger oninput)
                    const selectionNode = sel.anchorNode;
                    const activeCell = selectionNode ? (selectionNode.nodeType === 3 ? selectionNode.parentElement : selectionNode).closest('.mini-table td') : null;
                    const editable = activeCell || (selectionNode ? (selectionNode.nodeType === 3 ? selectionNode.parentElement : selectionNode).closest('[contenteditable="true"]') : null);
                    
                    if (editable && typeof editable.oninput === 'function') {
                        editable.oninput();
                    }
                    
                    // Lưu trạng thái ngay lập tức
                    if (typeof saveStateDebounced === 'function') {
                        saveStateDebounced();
                    }
                    
                    // Hiển thị Toast thông báo thành công nếu hệ thống có tích hợp toastr
                    if (typeof toastr !== 'undefined') {
                        toastr.success(`Liên kết thành công tài liệu: ${response.fileName} (Version ${response.version})`);
                    }
                } else {
                    const errorMsg = (response && response.message) ? response.message : 'Không tìm thấy tài liệu thực tế trên máy chủ.';
                    if (typeof toastr !== 'undefined') {
                        toastr.error(errorMsg, 'Cảnh báo tài liệu không tồn tại', { timeOut: 8000, closeButton: true });
                    } else {
                        alert(`[Cảnh báo tài liệu không tồn tại]\n\n${errorMsg}`);
                    }
                }
            },
            error: function(xhr) {
                // Khôi phục con trỏ chuột về mặc định
                document.body.style.cursor = 'default';
                
                let errorMsg = 'Có lỗi xảy ra khi kết nối tới máy chủ.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                if (typeof toastr !== 'undefined') {
                    toastr.error(errorMsg, 'Lỗi kết nối hệ thống', { timeOut: 8000, closeButton: true });
                } else {
                    alert(`[Lỗi kết nối]\n\n${errorMsg}`);
                }
            }
        });
    }

    /**
     * Hủy liên kết tài liệu mạng đã cài đặt cho chuỗi ký tự đang chọn.
     */
    function removeDocumentNetworkLink() {
        // Thực hiện lệnh hủy liên kết HTML trên vùng chọn hiện tại
        document.execCommand('unlink', false, null);
        
        // Kích hoạt đồng bộ hóa dữ liệu (trigger oninput)
        const selection = window.getSelection();
        const selectionNode = selection.anchorNode;
        const activeCell = selectionNode ? (selectionNode.nodeType === 3 ? selectionNode.parentElement : selectionNode).closest('.mini-table td') : null;
        const editable = activeCell || (selectionNode ? (selectionNode.nodeType === 3 ? selectionNode.parentElement : selectionNode).closest('[contenteditable="true"]') : null);
        
        if (editable && typeof editable.oninput === 'function') {
            editable.oninput();
        }
        
        // Lưu trạng thái ngay lập tức
        if (typeof saveStateDebounced === 'function') {
            saveStateDebounced();
        }
    }
</script>

<script>
    /**
     * FALLBACK: openExecutionInputModal cho chế độ Chạy thử (Designer Preview).
     * Chỉ được khai báo nếu hàm này chưa tồn tại (tức là không phải trang Thực thi).
     * Trang execute.blade.php đã tự định nghĩa hàm này với Bootstrap modal riêng.
     */
    if (typeof window.openExecutionInputModal !== 'function') {
        window.openExecutionInputModal = function(blockId, row, col, type) {
            if (!window.isExecutionMode) return;
            if (window.isReadOnly) return;

            // Tính toán cellKey chuẩn (khớp với cấu trúc DB)
            const cellKey = (row === 'default' && col === 'default') ? 'default' : `${row}_${col}`;

            if (type === 'signature') {
                // Hiển thị dialog nhập mật khẩu xác nhận chữ ký
                Swal.fire({
                    title: '<i class="fas fa-signature me-2 text-primary"></i>Xác nhận chữ ký điện tử',
                    html: `
                        <div class="text-start mb-3 small text-muted">
                            <i class="fas fa-info-circle me-1 text-info"></i>
                            Nhập mật khẩu tài khoản của bạn để xác nhận chữ ký điện tử.
                        </div>
                        <input type="password" id="swal-sig-password" class="swal2-input" placeholder="Mật khẩu xác nhận" autocomplete="current-password">
                    `,
                    showCancelButton: true,
                    confirmButtonText: '<i class="fas fa-check me-1"></i> Xác nhận',
                    cancelButtonText: 'Hủy',
                    confirmButtonColor: '#0d6efd',
                    showLoaderOnConfirm: true,
                    didOpen: () => {
                        const inp = document.getElementById('swal-sig-password');
                        if (inp) inp.focus();
                    },
                    preConfirm: () => {
                        const password = document.getElementById('swal-sig-password').value;
                        if (!password) {
                            Swal.showValidationMessage('Vui lòng nhập mật khẩu xác nhận');
                            return false;
                        }

                        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
                        const csrfToken = csrfMeta ? csrfMeta.content : '';

                        return fetch('{{ route("pages.ebmr.verifyPassword") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrfToken
                            },
                            body: JSON.stringify({ password: password, _token: csrfToken })
                        })
                        .then(res => {
                            if (!res.ok) throw new Error('Lỗi kết nối máy chủ');
                            return res.json();
                        })
                        .then(data => {
                            if (!data.success) {
                                Swal.showValidationMessage(data.message || 'Mật khẩu không chính xác');
                                return false;
                            }
                            return data;
                        })
                        .catch(err => {
                            Swal.showValidationMessage('Không thể kết nối đến máy chủ: ' + err.message);
                            return false;
                        });
                    },
                    allowOutsideClick: () => !Swal.isLoading()
                }).then(result => {
                    if (result.isConfirmed && result.value) {
                        const data = result.value;
                        const signatureVal = data.signature_image ? data.signature_image : (data.fullName || 'Đã ký');

                        if (!window.executionValues) window.executionValues = {};
                        if (!window.executionValues[blockId]) window.executionValues[blockId] = {};
                        window.executionValues[blockId][cellKey] = signatureVal;

                        // Gán metadata ngay lập tức
                        const now = new Date();
                        const formattedTime = now.toLocaleDateString('vi-VN') + ' ' + now.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
                        if (!window.executionValues[blockId]._meta) window.executionValues[blockId]._meta = {};
                        window.executionValues[blockId]._meta[cellKey] = {
                            by: data.fullName || '',
                            at: formattedTime
                        };

                        if (typeof renderBlocks === 'function') renderBlocks();
                        if (typeof syncLinkedCharts === 'function') syncLinkedCharts(blockId);

                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'success',
                            title: `Đã xác nhận chữ ký: ${data.fullName || ''}`,
                            showConfirmButton: false,
                            timer: 2000
                        });
                    }
                });

            } else {
                // Chế độ nhập dữ liệu văn bản thông thường
                const existingVal = (window.executionValues && window.executionValues[blockId])
                    ? (window.executionValues[blockId][cellKey] || '')
                    : '';

                Swal.fire({
                    title: 'Nhập dữ liệu',
                    input: 'textarea',
                    inputValue: existingVal,
                    inputAttributes: { rows: 3, placeholder: 'Nhập nội dung...' },
                    showCancelButton: true,
                    confirmButtonText: 'Xác nhận',
                    cancelButtonText: 'Hủy',
                }).then(result => {
                    if (result.isConfirmed) {
                        if (!window.executionValues) window.executionValues = {};
                        if (!window.executionValues[blockId]) window.executionValues[blockId] = {};
                        window.executionValues[blockId][cellKey] = result.value;
                        if (typeof renderBlocks === 'function') renderBlocks();
                        if (typeof syncLinkedCharts === 'function') syncLinkedCharts(blockId);
                    }
                });
            }
        };
    }
</script>
