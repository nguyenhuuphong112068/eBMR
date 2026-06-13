<script>
    // Auto-detect Review Mode from URL
    document.addEventListener('DOMContentLoaded', () => {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('mode') === 'review' || window.isReadOnly) {
            // Set to full view mode
            window.isViewAllMode = true;
            window.activeSectionId = null;

            // Switch to Trial Mode
            setDesignerMode(true);

            // Toast notification already handled in setDesignerMode
        }
    });

    /**
     * In biểu mẫu trắng: mở cửa sổ mới chỉ chứa nội dung form, không có sidebar/header.
     */
    function printBlankForm() {
        const pageContent = document.getElementById('document-page');
        if (!pageContent) { window.print(); return; }

        // Lấy tên biểu mẫu
        const titleEl = document.querySelector('.page-a4 h1, .page-a4 h2, .page-a4 .form-title');
        const formTitle = titleEl ? titleEl.textContent.trim() : 'Biểu mẫu trắng';

        // Thu thập các stylesheet đang hoạt động
        const styles = Array.from(document.styleSheets).map(ss => {
            try {
                return Array.from(ss.cssRules).map(r => r.cssText).join('\n');
            } catch(e) { return ''; }
        }).join('\n');

        // Clone nội dung trang
        const cloned = pageContent.cloneNode(true);

        // Xóa các element không cần in
        const toRemove = cloned.querySelectorAll(
            '.no-print, .print-blank-btn, .test-mode-badge, .btn-read-scale, ' +
            '.ebmr-note-badge, .page-break-divider, [data-no-print], ' +
            '.add-block-row, .execution-delete-cell, .block-tools, .resize-h, .resize-v, ' +
            '.badge-drag-handle, .ebmr-property-badge, .type-section'
        );
        toRemove.forEach(el => el.remove());

        // Chuyển select thành span gạch chân
        cloned.querySelectorAll('select').forEach(sel => {
            const span = document.createElement('span');
            span.style.cssText = 'display:block; width:100%; border-bottom:1.5px solid #000; height:55px; min-height:55px; color:transparent;';
            
            const td = sel.closest('td');
            if (td) {
                td.style.cssText = 'height:60px; min-height:60px; vertical-align:bottom;';
            }
            
            sel.parentNode.replaceChild(span, sel);
        });

        // Chuyển execution-input-test và execution-badge thành gạch chân
        cloned.querySelectorAll('.execution-input-test, .execution-badge:not(.execution-checkbox-wrapper)').forEach(el => {
            if (el.tagName === 'INPUT' && el.type === 'checkbox') return; // giữ checkbox
            el.style.cssText = 'display:block; width:100%; border:none; border-bottom:1.5px solid #000; height:55px; min-height:55px; color:transparent; background:transparent; box-shadow:none; border-radius:0;';
            // Xóa text bên trong nhưng giữ chiều rộng
            el.innerHTML = '&nbsp;';
            
            // Căn đáy cho thẻ cha (td) để các gạch chân nằm ngang hàng
            const td = el.closest('td');
            if (td) {
                td.style.cssText = 'height:60px; min-height:60px; vertical-align:bottom;';
            }
        });

        // Xóa viền vàng badge
        cloned.querySelectorAll('.ebmr-field-badge').forEach(b => {
            b.style.cssText = 'background:transparent; border:none; box-shadow:none; padding:0; border-radius:0; display:block; width:100%;';
        });

        const printWindow = window.open('', '_blank', 'width=900,height=700');
        printWindow.document.write(`
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>${formTitle}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Mầu in của app */
        ${styles}

        /* Tối ưu lề in bản in biểu mẫu trắng, tránh mất chữ 2 bên */
        @page { 
            size: A4 portrait; 
            margin: 5mm 5mm !important; 
        }
        
        body { 
            font-family: 'Arimon', 'Arial', sans-serif !important; 
            font-size: 12.5px; 
            color: #000; 
            margin: 0 !important; 
            padding: 0 !important; 
            background: #fff !important;
        }
        
        /* Đảm bảo toàn bộ nội dung hiển thị hết mà không bị tràn hay mất lề */
        .page-a4 { 
            width: 100% !important; 
            max-width: 100% !important; 
            margin: 0 !important; 
            padding: 10mm 10mm !important; /* Lề trong an toàn giúp đẩy nội dung vào trong vùng in được */
            border: none !important; 
            box-shadow: none !important; 
            background: transparent !important;
            box-sizing: border-box !important;
        }

        table { 
            border-collapse: collapse; 
            width: 100% !important; 
            table-layout: fixed !important; 
            word-break: break-word !important;
        }
        
        table td, table th { 
            border: 1px solid #000 !important; 
            padding: 4px 6px !important; 
            word-wrap: break-word !important;
        }
        
        img { 
            max-width: 100% !important; 
            height: auto !important; 
        }

        /* Thiết lập ngắt trang tự động cho dòng */
        tr { 
            page-break-inside: avoid !important; 
        }
        
        /* Loại bỏ các lề thừa của div hoặc container */
        div, p, span { 
            max-width: 100% !important;
        }
        
        /* Nhập liệu từ bảng */
        .execution-input-cell { 
            vertical-align: bottom; 
            padding: 2px 4px !important; 
        }
    </style>
</head>
<body>
    ${cloned.outerHTML}
    <script>
        window.onload = function() { window.print(); window.close(); };
    <\/script>
</body>
</html>`);
        printWindow.document.close();
    }


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
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" id="canAddRowsCheck" ${item.canAddRows ? 'checked' : ''} onchange="updateItemProp('canAddRows', this.checked); selectItem(selectedId);">
                        <label class="form-check-label small fw-bold text-primary" for="canAddRowsCheck"><i class="fas fa-plus-circle me-1"></i>Cho phép thêm dòng (Cấp 2)</label>
                    </div>
                    ${item.canAddRows ? `
                    <div class="mb-3 ps-3 border-start border-2 border-primary" style="margin-left: 0.5rem;">
                        <label class="small text-muted mb-1">Số dòng mỗi lần thêm</label>
                        <input type="number" class="form-control form-control-sm" min="1" value="${item.addRowsCount || 1}" onchange="updateItemProp('addRowsCount', parseInt(this.value) || 1)">
                        <div class="form-text" style="font-size: 0.65rem;">Hệ thống sẽ nhân bản N dòng cuối.</div>
                    </div>` : '<div class="mb-3"></div>'}

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

            `;
        } else if (item.type === 'static-text') {
            html += ``;
        } else if (item.type === 'section') {
            // Section has no sidebar configuration
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
        
        // Cần lấy editable block TRƯỚC khi gọi execCommand vì execCommand có thể thay thế node, làm mất DOM hierarchy
        const currentEditable = activeCell || (selectionNode ? (selectionNode.nodeType === 3 ? selectionNode
            .parentElement : selectionNode).closest('[contenteditable="true"]') : null);

        // Helper to robustly toggle superscript/subscript
        const handleSupSubToggle = (cmd, val) => {
            if (cmd !== 'superscript' && cmd !== 'subscript') {
                document.execCommand(cmd, false, val);
                return;
            }
            const targetTag = cmd === 'superscript' ? 'SUP' : 'SUB';
            const parentElement = selectionNode && selectionNode.nodeType === 3 ? selectionNode.parentElement : selectionNode;
            const existingTag = parentElement ? parentElement.closest('sup, sub') : null;
            
            if (existingTag && currentEditable && currentEditable.contains(existingTag)) {
                if (existingTag.tagName === targetTag) {
                    const fragment = document.createDocumentFragment();
                    while (existingTag.firstChild) {
                        fragment.appendChild(existingTag.firstChild);
                    }
                    existingTag.parentNode.replaceChild(fragment, existingTag);
                } else {
                    const newTag = document.createElement(targetTag);
                    while (existingTag.firstChild) {
                        newTag.appendChild(existingTag.firstChild);
                    }
                    existingTag.parentNode.replaceChild(newTag, existingTag);
                }
            } else {
                document.execCommand(cmd, false, val);
            }
        };

        if (selectedText.length > 0) {
            handleSupSubToggle(command, value);

            // Force data sync for the active cell/block
            if (currentEditable && currentEditable.oninput) {
                currentEditable.oninput();
            }
            // Mark item as dirty if we're in a specific block
            const blockItem = currentEditable ? currentEditable.closest('.block-item') : null;
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
                    
                    // --- Dọn dẹp thẻ nội bộ để đảm bảo format toàn ô (td) có hiệu lực ---
                    if (domProp && ['foreColor', 'bold', 'italic', 'underline', 'strikethrough', 'justifyLeft', 'justifyCenter', 'justifyRight', 'justifyFull'].includes(command)) {
                        let selectors = [];
                        if (command === 'foreColor') selectors = ['font[color]', '[style*="color"]'];
                        else if (command === 'bold') selectors = ['b', 'strong', '[style*="font-weight"]'];
                        else if (command === 'italic') selectors = ['i', 'em', '[style*="font-style"]'];
                        else if (['underline', 'strikethrough'].includes(command)) selectors = ['u', 'strike', 's', '[style*="text-decoration"]'];
                        else if (command.startsWith('justify')) selectors = ['[style*="text-align"]', 'center'];

                        const innerNodes = cell.querySelectorAll(selectors.join(', '));
                        innerNodes.forEach(node => {
                            if (!node.classList.contains('ebmr-field-badge')) {
                                const tag = node.tagName.toLowerCase();
                                if (['b', 'strong', 'i', 'em', 'u', 'strike', 's', 'center'].includes(tag)) {
                                    // Loại bỏ thẻ nhưng giữ lại nội dung bên trong
                                    const parent = node.parentNode;
                                    while (node.firstChild) parent.insertBefore(node.firstChild, node);
                                    parent.removeChild(node);
                                } else if (tag === 'font' && command === 'foreColor') {
                                    // Unwrap thẻ font hoàn toàn thay vì chỉ xóa thuộc tính color (tránh lỗi trình duyệt tự fallback về màu đen)
                                    const parent = node.parentNode;
                                    while (node.firstChild) parent.insertBefore(node.firstChild, node);
                                    parent.removeChild(node);
                                } else {
                                    // Xoá style tương ứng
                                    if (command === 'foreColor') node.style.color = '';
                                    else if (command === 'bold') node.style.fontWeight = '';
                                    else if (command === 'italic') node.style.fontStyle = '';
                                    else if (['underline', 'strikethrough'].includes(command)) node.style.textDecoration = '';
                                    else if (command.startsWith('justify')) node.style.textAlign = '';
                                    
                                    if (node.getAttribute('style') === '') {
                                        // Nếu span không còn style nào khác, unwrap luôn cho sạch HTML
                                        const parent = node.parentNode;
                                        if (node.tagName.toLowerCase() === 'span') {
                                            while (node.firstChild) parent.insertBefore(node.firstChild, node);
                                            parent.removeChild(node);
                                        } else {
                                            node.removeAttribute('style');
                                        }
                                    }
                                }
                            }
                        });

                        // Cập nhật lại HTML model sau khi dọn dẹp
                        const wrapper = cell.querySelector('.cell-wrapper');
                        if (r > 0) {
                            const rIdx = r - 1;
                            if (item.data && item.data[rIdx] && item.data[rIdx][c]) {
                                item.data[rIdx][c].content = wrapper ? wrapper.innerHTML : cell.innerHTML;
                            }
                        }
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
        handleSupSubToggle(command, value);
        if (currentEditable && currentEditable.oninput) {
            currentEditable.oninput();
        }
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
            e.stopImmediatePropagation(); // Ngăn chặn sự kiện truyền tới events.blade.php
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

        // Nếu là bảng copy từ Excel/Word thì để events.blade.php xử lý
        if (html && html.includes('<table')) {
            return;
        }

        // Kiểm tra xem events.blade.php có nên xử lý text này thành grid không (chứa tab)
        let text = (e.clipboardData || window.clipboardData).getData('text');
        if (text && target.closest('.mini-table td') && text.includes('\t')) {
            // Có chứa tab, khả năng là copy từ Excel dưới dạng plain text -> Để events xử lý grid
            return;
        }

        e.preventDefault();
        e.stopImmediatePropagation();

        // Logic: Replace single newlines with a space (reflow), but keep double newlines (paragraphs)
        text = text.replace(/\r\n/g, '\n');
        text = text.replace(/\n\n+/g, '[[PARAGRAPH_BREAK]]');
        text = text.replace(/\n/g, ' ');
        text = text.replace(/\[\[PARAGRAPH_BREAK\]\]/g, '\n\n');
        text = text.replace(/[ ]+/g, ' ');

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

        // Khôi phục vùng chọn trước khi áp dụng màu
        if (savedTextSelection) {
            const sel = window.getSelection();
            sel.removeAllRanges();
            sel.addRange(savedTextSelection);
        }

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

                // Dọn dẹp các thẻ font hoặc style màu bên trong ô để đảm bảo màu của ô (td) được áp dụng đồng nhất
                const innerColorNodes = cell.querySelectorAll('font[color], [style*="color"]');
                innerColorNodes.forEach(node => {
                    if (!node.classList.contains('ebmr-field-badge')) {
                        if (node.tagName.toLowerCase() === 'font') {
                            // Unwrap thẻ font hoàn toàn thay vì chỉ xóa thuộc tính color (tránh lỗi trình duyệt tự fallback về màu đen)
                            const parent = node.parentNode;
                            while (node.firstChild) parent.insertBefore(node.firstChild, node);
                            parent.removeChild(node);
                        } else {
                            node.style.color = '';
                            if (node.getAttribute('style') === '') {
                                // Nếu span không còn style nào khác, unwrap luôn cho sạch HTML
                                const parent = node.parentNode;
                                if (node.tagName.toLowerCase() === 'span') {
                                    while (node.firstChild) parent.insertBefore(node.firstChild, node);
                                    parent.removeChild(node);
                                } else {
                                    node.removeAttribute('style');
                                }
                            }
                        }
                    }
                });

                // Lấy nội dung bên trong .cell-wrapper để tránh bị bọc lồng nhau (nesting) khi lưu
                const wrapper = cell.querySelector('.cell-wrapper');
                if (item && r > 0) {
                    const rIdx = r - 1;
                    if (item.data && item.data[rIdx] && item.data[rIdx][c]) {
                        item.data[rIdx][c].content = wrapper ? wrapper.innerHTML : cell.innerHTML;
                    }
                }
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
                
                // Dọn dẹp inner font-size để đảm bảo style td được áp dụng đồng nhất
                const innerNodes = cell.querySelectorAll('font[size], [style*="font-size"]');
                innerNodes.forEach(node => {
                    if (!node.classList.contains('ebmr-field-badge')) {
                        if (node.tagName.toLowerCase() === 'font') {
                            const parent = node.parentNode;
                            while (node.firstChild) parent.insertBefore(node.firstChild, node);
                            parent.removeChild(node);
                        }
                        else {
                            node.style.fontSize = '';
                            if (node.getAttribute('style') === '') {
                                const parent = node.parentNode;
                                // Không unwrap P, DIV, B, I, U, v.v. chỉ unwrap SPAN rỗng
                                if (node.tagName.toLowerCase() === 'span') {
                                    while (node.firstChild) parent.insertBefore(node.firstChild, node);
                                    parent.removeChild(node);
                                } else {
                                    node.removeAttribute('style');
                                }
                            }
                        }
                    }
                });
                
                // Cập nhật lại HTML đã dọn dẹp vào model
                const rStr = cell.dataset.row;
                const cStr = cell.dataset.col;
                const r = parseInt(rStr);
                const c = parseInt(cStr);
                const table = cell.closest('.mini-table');
                const blockItem = table ? table.closest('.block-item') : null;
                const itemId = blockItem ? blockItem.getAttribute('data-id') : null;
                const item = items.find(i => i.id === itemId);
                const wrapper = cell.querySelector('.cell-wrapper');
                if (item && r > 0) {
                    const rIdx = r - 1;
                    if (item.data && item.data[rIdx] && item.data[rIdx][c]) {
                        item.data[rIdx][c].content = wrapper ? wrapper.innerHTML : cell.innerHTML;
                    }
                }
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

        // Force data sync for the active cell/block
        const selection = window.getSelection();
        const selectionNode = selection.anchorNode;
        const editable = selectionNode ? (selectionNode.nodeType === 3 ? selectionNode.parentElement : selectionNode).closest('[contenteditable="true"]') : null;
        if (editable && editable.oninput) {
            editable.oninput();
        }

        const blockItem = editable ? editable.closest('.block-item') : null;
        if (blockItem) {
            const item = items.find(i => i.id === blockItem.getAttribute('data-id'));
            if (item) item.dirty = true;
        }

        if (typeof saveStateDebounced === 'function') saveStateDebounced();
    }

    /**
     * Thay đổi hướng chữ (Text Direction) cho các ô/cột được chọn.
     * @param {string} direction - Hướng chữ ('horizontal', 'vertical-down', 'vertical-up').
     */
    window.changeTextDirection = function(direction) {
        if (savedTextSelection) {
            const sel = window.getSelection();
            sel.removeAllRanges();
            sel.addRange(savedTextSelection);
        }
        const selection = window.getSelection();
        const selectedCells = document.querySelectorAll('.selected-cell');
        const selectionNode = selection.anchorNode;
        const activeCell = selectionNode ? (selectionNode.nodeType === 3 ? selectionNode.parentElement :
                selectionNode)
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

    // ============================================================
    // SEARCH & REPLACE ENGINE (CSS Custom Highlight API)
    // Không modify DOM — tô màu hoàn toàn qua CSS pseudo-elements
    // Chrome 105+, Edge 105+, Firefox 117+, Safari 17.2+
    // ============================================================

    /** State nội bộ */
    const _srState = {
        matches: [],
        currentIndex: -1
    };

    /** Kiểm tra trình duyệt hỗ trợ CSS Highlight API không */
    const _hasCssHighlight = (typeof CSS !== 'undefined' && CSS.highlights);

    /**
     * Thu thập tất cả text node bên trong root (dùng TreeWalker).
     */
    function _collectTextNodes(root) {
        const nodes = [];
        const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, null, false);
        let node;
        while ((node = walker.nextNode())) {
            if (node.nodeValue && node.nodeValue.trim().length > 0) {
                nodes.push(node);
            }
        }
        return nodes;
    }

    /**
     * Tìm tất cả vị trí khớp với `term` trong editor.
     * Trả về mảng Range[]. Case-insensitive.
     */
    function _findAllMatches(term) {
        const editor = document.getElementById('editor-content');
        if (!editor || !term) return [];

        const ranges = [];
        const lowerTerm = term.toLowerCase();
        const termLen = term.length;

        _collectTextNodes(editor).forEach(node => {
            const lowerText = node.nodeValue.toLowerCase();
            let startIdx = 0;
            while (true) {
                const idx = lowerText.indexOf(lowerTerm, startIdx);
                if (idx === -1) break;
                const range = document.createRange();
                range.setStart(node, idx);
                range.setEnd(node, idx + termLen);
                ranges.push(range);
                startIdx = idx + termLen;
            }
        });
        return ranges;
    }

    /**
     * Xóa tất cả highlight (CSS Highlight API hoặc span fallback).
     */
    function _clearHighlights() {
        if (_hasCssHighlight) {
            CSS.highlights.delete('sr-all');
            CSS.highlights.delete('sr-current');
        }
        // Xóa span fallback nếu có
        document.querySelectorAll('span.sr-hl').forEach(span => {
            const parent = span.parentNode;
            if (parent) {
                parent.replaceChild(document.createTextNode(span.textContent), span);
                parent.normalize();
            }
        });
        document.querySelectorAll('.has-search-match').forEach(el => el.classList.remove('has-search-match'));
    }

    /**
     * Render highlight: dùng CSS Custom Highlight API nếu có,
     * fallback sang border-only (không modify text node).
     */
    function _renderHighlights(currentIdx) {
        const matches = _srState.matches;
        if (!matches.length) return;

        if (_hasCssHighlight) {
            // === CSS Custom Highlight API (không modify DOM) ===
            // Tất cả matches — vàng nhạt
            const allHighlight = new Highlight(...matches);
            CSS.highlights.set('sr-all', allHighlight);

            // Match hiện tại — cam đậm (override sr-all)
            if (currentIdx >= 0 && matches[currentIdx]) {
                const currentHighlight = new Highlight(matches[currentIdx]);
                CSS.highlights.set('sr-current', currentHighlight);
            }
        }
        // Dù có CSS Highlight API hay không, vẫn thêm viền cho container hiện tại
        _highlightContainer(currentIdx);
    }

    /**
     * Thêm viền cam cho container chứa match hiện tại và scroll tới.
     */
    function _highlightContainer(currentIdx) {
        document.querySelectorAll('.has-search-match').forEach(el => el.classList.remove('has-search-match'));
        const matches = _srState.matches;
        if (currentIdx < 0 || !matches[currentIdx]) return;

        const range = matches[currentIdx];
        let node = range.startContainer;
        if (node.nodeType === Node.TEXT_NODE) node = node.parentElement;

        const container = node ? node.closest(
            '.mini-table td, .mini-table th, .static-text-display, [contenteditable="true"], .block-item') : null;
        if (container) {
            container.classList.add('has-search-match');
            container.scrollIntoView({
                behavior: 'smooth',
                block: 'center',
                inline: 'nearest'
            });
        }
    }

    /**
     * Thực hiện tìm kiếm.
     * @param {boolean} silent - Không hiện toast khi không tìm thấy.
     * @param {boolean} backwards - Tìm ngược.
     */
    window.executeSearch = function(silent = false, backwards = false) {
        const term = document.getElementById('findInput').value;
        if (!term) return;

        _clearHighlights();
        _srState.matches = _findAllMatches(term);
        const total = _srState.matches.length;

        if (total === 0) {
            _srState.currentIndex = -1;
            if (!silent) toastr.warning('Không tìm thấy nội dung: "' + term + '"');
            $('#searchStats').text('Không tìm thấy kết quả nào.');
            return;
        }

        // Điều hướng
        if (_srState.currentIndex === -1) {
            _srState.currentIndex = 0;
        } else if (backwards) {
            _srState.currentIndex = (_srState.currentIndex - 1 + total) % total;
        } else {
            _srState.currentIndex = (_srState.currentIndex + 1) % total;
        }

        _renderHighlights(_srState.currentIndex);
        $('#searchStats').text(`${_srState.currentIndex + 1} / ${total} kết quả`);
    };

    /**
     * Thay thế kết quả hiện tại.
     * Dùng trực tiếp Range object — không cần tìm DOM span.
     */
    window.executeReplace = function() {
        const findTerm = document.getElementById('findInput').value;
        const replaceTerm = document.getElementById('replaceInput').value;
        if (!findTerm) return;

        const total = _srState.matches.length;
        if (total === 0 || _srState.currentIndex === -1) {
            executeSearch(false);
            return;
        }

        saveState();

        const range = _srState.matches[_srState.currentIndex];
        if (!range || range.collapsed) {
            executeSearch(false);
            return;
        }

        // Xác định container để sync lại data
        let targetEl = range.startContainer;
        if (targetEl.nodeType === Node.TEXT_NODE) targetEl = targetEl.parentElement;
        const container = targetEl ? targetEl.closest('[contenteditable="true"]') : null;

        // Xóa highlight trước khi modify DOM
        _clearHighlights();

        // Thay thế nội dung range
        range.deleteContents();
        range.insertNode(document.createTextNode(replaceTerm));
        if (container) container.normalize();

        // Sync data
        if (container) {
            if (container.oninput) container.oninput();
            const blockItem = container.closest('.block-item');
            if (blockItem) {
                const item = items.find(i => i.id === blockItem.getAttribute('data-id'));
                if (item) item.dirty = true;
            }
        }

        saveStateDebounced();

        // Re-search
        _srState.currentIndex = -1;
        executeSearch(true);
    };

    /**
     * Thay thế tất cả.
     */
    window.executeReplaceAll = function() {
        const findTerm = document.getElementById('findInput').value;
        const replaceTerm = document.getElementById('replaceInput').value;
        if (!findTerm) return;

        _clearHighlights();
        const matches = _findAllMatches(findTerm);
        const count = matches.length;

        if (count === 0) {
            toastr.info('Không tìm thấy nội dung để thay thế.');
            $('#searchStats').text('Không tìm thấy kết quả nào.');
            return;
        }

        saveState();
        const dirtyContainers = new Set();

        // Thay thế ngược từ cuối để không làm lệch vị trí các Range trước đó
        for (let i = count - 1; i >= 0; i--) {
            const range = matches[i];
            try {
                let targetEl = range.startContainer;
                if (targetEl.nodeType === Node.TEXT_NODE) targetEl = targetEl.parentElement;
                const container = targetEl ? targetEl.closest('[contenteditable="true"]') : null;
                if (container) dirtyContainers.add(container);

                range.deleteContents();
                range.insertNode(document.createTextNode(replaceTerm));
            } catch (e) {
                /* bỏ qua range không hợp lệ */
            }
        }

        dirtyContainers.forEach(container => {
            container.normalize();
            if (container.oninput) container.oninput();
            const blockItem = container.closest('.block-item');
            if (blockItem) {
                const item = items.find(i => i.id === blockItem.getAttribute('data-id'));
                if (item) item.dirty = true;
            }
        });

        _srState.matches = [];
        _srState.currentIndex = -1;

        saveStateDebounced();
        toastr.success(`Đã thay thế ${count} vị trí`);
        $('#searchStats').text(`Đã thay thế ${count} vị trí.`);
    };

    // Xóa highlight khi đóng modal
    document.addEventListener('DOMContentLoaded', () => {
        $('#searchReplaceModal').on('hidden.bs.modal', function() {
            _clearHighlights();
            _srState.matches = [];
            _srState.currentIndex = -1;
        });
    });


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
            if (col) col.className = 'col-lg-2 transition-all';
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
     * Cách hoạt động: Sử dụng các điều kiện logic để gán class Bootstrap (col-lg-8/9/10/12) sao cho 
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
            canvas.className = 'col-lg-9 transition-all';
        } else if (isSidebarMinimized) {
            canvas.className = 'col-lg-9 transition-all';
        } else {
            canvas.className = 'col-lg-8 transition-all';
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
     * Cập nhật điều kiện N/A cho ô hiện tại.
     * @param {string} itemId - ID của khối bảng.
     * @param {number} r - Chỉ số hàng.
     * @param {number} c - Chỉ số cột.
     * @param {string} prop - Tên thuộc tính trong na_condition (target_id, operator, value).
     * @param {any} val - Giá trị mới.
     */
    function updateTableCellCondition(itemId, r, c, prop, val) {
        const item = items.find(i => i.id === itemId);
        if (!item) return;
        item.dirty = true;

        if (item.data && item.data[r] && item.data[r][c] !== undefined) {
            if (typeof item.data[r][c] !== 'object') {
                item.data[r][c] = {
                    content: item.data[r][c],
                    rs: 1,
                    cs: 1,
                    hidden: false
                };
            }
            if (!item.data[r][c].na_condition) {
                item.data[r][c].na_condition = { target_id: '', operator: '=', value: '' };
            }
            item.data[r][c].na_condition[prop] = val;
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
                    <option value="date" ${field.type === 'date' ? 'selected' : ''}>Thời Gian</option>
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
        } else if (field.type === 'date') {
            typeHtml += `
                <div class="mb-3">
                    <label class="small fw-bold text-primary mb-2"><i class="fas fa-clock me-1"></i>Định dạng thời gian</label>
                    <select class="form-select form-select-sm border-primary" onchange="syncFieldConfig('${fieldId}', 'date_format', this.value)">
                        <option value="dd/mm/yyyy" ${(!field.date_format || field.date_format === 'dd/mm/yyyy') ? 'selected' : ''}>Ngày (dd/mm/yyyy)</option>
                        <option value="hh:mm dd/mm/yyyy" ${field.date_format === 'hh:mm dd/mm/yyyy' ? 'selected' : ''}>Giờ và Ngày (hh:mm dd/mm/yyyy)</option>
                    </select>
                </div>
                <div class="mb-3">
                    <div class="form-check form-switch mt-2">
                        <input class="form-check-input" type="checkbox" id="checkAutoSystemTime_${fieldId}" 
                               ${(field.autoSystemTime !== false) ? 'checked' : ''} 
                               onchange="syncFieldConfig('${fieldId}', 'autoSystemTime', this.checked)">
                        <label class="form-check-label small fw-bold text-muted" for="checkAutoSystemTime_${fieldId}">
                            <i class="fas fa-bolt me-1 text-warning"></i>Tự động lấy giờ hệ thống
                        </label>
                    </div>
                    <div class="form-text small" style="font-size: 0.65rem;">Nếu bật, nhấp chuột vào biến sẽ tự động điền giờ hiện tại thay vì mở form nhập liệu.</div>
                </div>
            `;
        } else if (field.type === 'signature') {
            typeHtml += `
                <div class="mb-3">
                    <div class="form-check form-switch mt-2">
                        <input class="form-check-input" type="checkbox" id="checkIsChecker" 
                               ${field.is_checker ? 'checked' : ''} 
                               onchange="syncFieldConfig('${fieldId}', 'is_checker', this.checked)">
                        <label class="form-check-label small fw-bold text-muted" for="checkIsChecker">
                            <i class="fas fa-user-check me-1"></i>Chữ ký của Người kiểm tra
                        </label>
                    </div>
                    <div class="form-text small" style="font-size: 0.65rem;">Người dùng cấp 2 sẽ phải nhập tài khoản và mật khẩu của họ để ký.</div>
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

            <hr class="my-3">
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
                <div class="card border-0 shadow-none mb-3" style="background: linear-gradient(135deg, #fef2f2, #fee2e2); border: 1px solid #fecaca !important;">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="fas fa-balance-scale text-danger"></i>
                            <label class="small fw-bold mb-0 text-danger-emphasis">Kết nối Cân điện tử (RS-232)</label>
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
                                Nút <i class="fas fa-balance-scale text-danger"></i> sẽ xuất hiện cạnh ô nhập liệu ở chế độ Thực thi.
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

            <div class="mb-3">
                <label class="small fw-bold mt-2 mb-2"><i class="fas fa-ban me-1 text-secondary"></i> Điều kiện Không áp dụng (N/A)</label>
                <div class="p-2 border rounded bg-light">
                    <div class="mb-2">
                        <label class="small text-muted mb-1">Mã ID Biến phụ thuộc</label>
                        <input type="text" class="form-control form-control-sm" placeholder="Nhập ID biến (VD: tram_1)" 
                               value="${(field.na_condition && field.na_condition.target_id) ? field.na_condition.target_id : ''}" 
                               oninput="syncFieldConfig('${fieldId}', 'na_condition.target_id', this.value)">
                    </div>
                    <div class="row g-2">
                        <div class="col-5">
                            <label class="small text-muted mb-1">Toán tử</label>
                            <select class="form-select form-select-sm" onchange="syncFieldConfig('${fieldId}', 'na_condition.operator', this.value)">
                                <option value="=" ${(field.na_condition && field.na_condition.operator === '=') ? 'selected' : ''}>Bằng (=)</option>
                                <option value="!=" ${(field.na_condition && field.na_condition.operator === '!=') ? 'selected' : ''}>Khác (!=)</option>
                            </select>
                        </div>
                        <div class="col-7">
                            <label class="small text-muted mb-1">Giá trị</label>
                            <input type="text" class="form-control form-control-sm" placeholder="Giá trị so sánh" 
                                   value="${(field.na_condition && field.na_condition.value) ? field.na_condition.value : ''}" 
                                   oninput="syncFieldConfig('${fieldId}', 'na_condition.value', this.value)">
                        </div>
                    </div>
                    <div class="form-text small mt-2" style="font-size: 0.65rem;">Nếu điều kiện đúng, biến này sẽ tự động chuyển thành N/A khi thực thi.</div>
                </div>
            </div>
        `;


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
                allGfs = data.filter(t => t.type === 'GF' && t.status === 'active');
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
                .gfFieldsCache[templateId] : {}, window.gfTemplateCache ? window.gfTemplateCache[templateId] : null);
            return;
        }

        fetch(`/ebmr/templates/${templateId}/blocks`)
            .then(res => res.json())
            .then(data => {
                const blocks = data.blocks || data;
                const fields = data.fields || {};
                const template = data.template || null;

                if (!window.gfPreviewCache) window.gfPreviewCache = {};
                if (!window.gfFieldsCache) window.gfFieldsCache = {};
                if (!window.gfTemplateCache) window.gfTemplateCache = {};

                window.gfPreviewCache[templateId] = blocks;
                window.gfFieldsCache[templateId] = fields;
                window.gfTemplateCache[templateId] = template;

                // Merge into global fieldsConfig to support formulas and execution mode logic
                let targetFieldsConfig = null;
                if (typeof fieldsConfig !== 'undefined') {
                    targetFieldsConfig = fieldsConfig;
                } else {
                    if (!window.fieldsConfig) window.fieldsConfig = {};
                    targetFieldsConfig = window.fieldsConfig;
                }
                Object.assign(targetFieldsConfig, fields);

                renderGfPreviewContent(container, blocks, fields, template);
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
     * @param {Object} template - Thông tin template metadata.
     */
    function renderGfPreviewContent(container, blocks, fields = {}, template = null) {
        if (!blocks || blocks.length === 0) {
            container.innerHTML =
                '<div class="text-muted small italic text-center py-3">Biểu mẫu này chưa có nội dung.</div>';
            return;
        }

        let html = '';
        let displayBlocks = [...blocks];
        
        // Render header block for GF and BPR automatically
        if (template && (template.type === 'GF' || template.type === 'BPR')) {
            const t = {
                sop: template.relatived_sop_no || '',
                format: template.category_code || '',
                version: template.version || '1',
                name: template.category_name || template.name || '',
                caterogy_id: template.caterogy_id || 0
            };
            
            displayBlocks.unshift({
                id: 'blk_header_' + Date.now(),
                type: 'table',
                label: 'GF Header',
                rows: 3,
                cols: 2,
                columns: [
                    { label: 'C1', type: 'text', width: '60%' },
                    { label: 'C2', type: 'text', width: '40%' }
                ],
                data: [
                    [{ content: `Số SOP đối chiếu: ${t.sop}`, rs: 1, cs: 1, textAlign: 'left', fontStyle: 'italic', fontSize: '1rem', backgroundColor: '#dcdcdc' },
                     { content: ` Số biểu mẫu: ${t.format}-${t.version}`, rs: 1, cs: 1, textAlign: 'right', fontStyle: 'italic', fontSize: '1rem', backgroundColor: '#dcdcdc' }],
                    [{ content: t.name, rs: 1, cs: 2, textAlign: 'center', fontSize: '1.2rem', fontWeight: 'bold', textTransform: 'uppercase', backgroundColor: '#dcdcdc' },
                     { content: '', hidden: true }]
                ],
                rowHeights: ['auto', '5px', 'auto'],
                borderMode: 'none',
                hideHeader: true,
                locked: true,
                isGfHeader: true,
                section_id: t.caterogy_id
            });
        }

        displayBlocks.forEach(b => {
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
                        return ` < th contenteditable = "false"
                    spellcheck = "false"
                    data - row = "0"
                    data - col = "${cIdx}"
                    class = "table-header-cell"
                    style =
                        "width: ${c.width || 'auto'}; background-color: ${bg}; text-align: ${align}; font-weight: ${fw}; font-style: ${fs}; text-decoration: ${td}; font-size: ${fsz}; color: ${tc}; writing-mode: ${wm};" >
                        <
                        div class = "header-content"
                    style =
                        "transform: ${tf}; transform-origin: center center; display: inline-block; width: 100%;" >
                        $ {
                            c.label || ''
                        } < /div></th > `;
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
            if (!window.activeSectionId || window.activeSectionId === catId || window.activeSectionId === catId +
                '_0') {
                let lastSection = localStorage.getItem('ebmr_last_section_' + templateId);
                // Kiểm tra nếu lastSection cũng trỏ vào header thì bỏ qua để tìm công đoạn thực
                if (lastSection === catId || lastSection === catId + '_0') lastSection = null;

                if (!lastSection && typeof items !== 'undefined' && items.length > 0) {
                    const firstStageBlock = items.find(i => i.section_id && !i.isVirtual && i.type === 'section' && i
                        .stage_code !== undefined);
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
    // --- Format Painter Logic (Google Docs style) ---
    let isFormatPainterActive = false;
    let formatPainterLocked = false; // double-click: painter stays active
    let storedFormat = null;

    /**
     * Lấy định dạng thực của node văn bản đang được bôi đen.
     * Ưu tiên inline style trên <span>, <font>, <b>, <i>, <u>... thay vì getComputedStyle().
     */
    function _captureTextFormat() {
        const selection = window.getSelection();
        if (selection.rangeCount === 0) return null;

        const range = selection.getRangeAt(0);
        const container = range.commonAncestorContainer;
        let el = container.nodeType === 3 ? container.parentElement : container;

        // Collect inline styles by walking up to editable boundary
        let bold = false, italic = false, underline = false, strikethrough = false;
        let fontSize = '', color = '', bgColor = '';

        // queryCommandState reflects the selection state accurately
        bold = document.queryCommandState('bold');
        italic = document.queryCommandState('italic');
        underline = document.queryCommandState('underline');
        strikethrough = document.queryCommandState('strikeThrough');

        // Walk up the DOM to find explicit font-size / color overrides
        let node = el;
        while (node && node.getAttribute && node.getAttribute('contenteditable') !== 'true') {
            const cs = window.getComputedStyle(node);
            if (!fontSize && node.style && node.style.fontSize) fontSize = node.style.fontSize;
            if (!color && node.style && node.style.color) color = node.style.color;
            if (!bgColor && node.style && node.style.backgroundColor && node.style.backgroundColor !== 'rgba(0, 0, 0, 0)') {
                bgColor = node.style.backgroundColor;
            }
            node = node.parentElement;
        }

        // Fallback to computed style if nothing found inline
        if (!fontSize || !color) {
            const cs = window.getComputedStyle(el);
            if (!fontSize) fontSize = cs.fontSize;
            if (!color) color = cs.color;
        }

        // Chuẩn hóa fontSize: nếu đang là px (từ computed style) thì chuyển về pt
        if (fontSize && fontSize.endsWith('px')) {
            const pxVal = parseFloat(fontSize);
            // 1pt ≈ 1.333px  →  1px ≈ 0.75pt
            fontSize = Math.round(pxVal * 0.75) + 'pt';
        }

        return {
            type: 'text',
            bold, italic, underline, strikethrough,
            fontSize,
            color,
            bgColor
        };
    }

    /**
     * Bật/Tắt công cụ Sao chép định dạng (Format Painter) – giống Google Docs:
     * - Single click: bật một lần, tự tắt sau khi áp dụng.
     * - Double click (gọi lại trong 400ms): bật liên tục cho đến khi click nút lần nữa hoặc nhấn Esc.
     */
    let _painterClickTimer = null;
    function toggleFormatPainter() {
        if (isFormatPainterActive) {
            disableFormatPainter();
            return;
        }

        // Detect double-click
        if (_painterClickTimer) {
            clearTimeout(_painterClickTimer);
            _painterClickTimer = null;
            formatPainterLocked = true;
        } else {
            formatPainterLocked = false;
            _painterClickTimer = setTimeout(() => { _painterClickTimer = null; }, 400);
        }

        const selection = window.getSelection();
        const selectedText = selection.toString().trim();
        let targetEl = null;
        if (selection.rangeCount > 0) {
            targetEl = selection.anchorNode.nodeType === 3 ? selection.anchorNode.parentElement : selection.anchorNode;
        }

        storedFormat = null;

        // --- Priority 1: Text selected inside contenteditable ---
        if (selectedText.length > 0 && targetEl && targetEl.closest('[contenteditable="true"]')) {
            storedFormat = _captureTextFormat();
        }
        // --- Priority 2: Single active table cell selected (even without text selection) ---
        else if (typeof activeRowIdx !== 'undefined' && selectedId) {
            const item = items.find(i => i.id === selectedId);
            if (item && item.type === 'table') {
                const rIdx = activeRowIdx - 1;
                const cIdx = activeColIdx;
                if (rIdx >= 0 && item.data[rIdx] && item.data[rIdx][cIdx] && typeof item.data[rIdx][cIdx] === 'object') {
                    const cell = item.data[rIdx][cIdx];
                    storedFormat = {
                        type: 'cell',
                        backgroundColor: cell.backgroundColor || '',
                        textAlign: cell.textAlign || '',
                        fontWeight: cell.fontWeight || '',
                        fontStyle: cell.fontStyle || '',
                        textDecoration: cell.textDecoration || '',
                        fontSize: cell.fontSize || '',
                        textColor: cell.textColor || '',
                        textTransform: cell.textTransform || '',
                        borderTop: cell.borderTop || '',
                        borderBottom: cell.borderBottom || '',
                        borderLeft: cell.borderLeft || '',
                        borderRight: cell.borderRight || '',
                        writingMode: cell.writingMode || ''
                    };
                }
                // Header cell (row 0)
                else if (activeRowIdx === 0 && item.columns[cIdx]) {
                    const col = item.columns[cIdx];
                    storedFormat = {
                        type: 'cell',
                        backgroundColor: (col.style && col.style.backgroundColor) || '',
                        textAlign: (col.style && col.style.textAlign) || '',
                        fontWeight: (col.style && col.style.fontWeight) || '',
                        fontStyle: (col.style && col.style.fontStyle) || '',
                        textDecoration: '',
                        fontSize: (col.style && col.style.fontSize) || '',
                        textColor: (col.style && col.style.color) || '',
                        textTransform: '',
                        borderTop: '', borderBottom: '', borderLeft: '', borderRight: '',
                        writingMode: ''
                    };
                }
            }
        }
        // --- Priority 3: Block selected ---
        else if (selectedId) {
            const item = items.find(i => i.id === selectedId);
            if (item) {
                storedFormat = {
                    type: 'block',
                    backgroundColor: item.backgroundColor || '',
                    textAlign: item.textAlign || '',
                    fontSize: item.fontSize || '',
                    borderMode: item.borderMode || ''
                };
            }
        }

        if (storedFormat) {
            enableFormatPainter();
            // Toast
            const Toast = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 1800 });
            Toast.fire({ icon: 'info', title: formatPainterLocked ? '🖌️ Sao chép định dạng (đã khoá – nhấn Esc để thoát)' : '🖌️ Đã lấy định dạng – click vào đích để dán' });
        } else {
            Swal.fire('Thông báo', 'Đặt con trỏ vào văn bản hoặc chọn ô/khối để sao chép định dạng', 'info');
        }
    }

    // SVG cursor hình cây chổi sơn (paint-roller) dùng khi Format Painter đang hoạt động
    const _painterCursorSVG = `url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 512 512'%3E%3Cpath fill='%231a73e8' d='M352 64h-48V32a32 32 0 0 0-64 0v32H32A32 32 0 0 0 0 96v96a32 32 0 0 0 32 32h288a32 32 0 0 0 32-32v-16h32a16 16 0 0 1 16 16v32a16 16 0 0 1-16 16H272a48 48 0 0 0-48 48v176a48 48 0 0 0 96 0V288h64a80 80 0 0 0 80-80v-32a112 112 0 0 0-112-112z'/%3E%3C/svg%3E") 0 20, crosshair`;

    // Snapshot selection khi mousedown để mouseup có thể lấy lại chính xác
    let _painterSelectionSnapshot = null;
    function _handlePainterMouseDown(e) {
        // Đặt lại snapshot khi bắt đầu drag bôi đen mới
        _painterSelectionSnapshot = null;
    }
    function _handlePainterSelectionChange() {
        const sel = window.getSelection();
        if (sel && sel.rangeCount > 0 && sel.toString().trim().length > 0) {
            _painterSelectionSnapshot = sel.getRangeAt(0).cloneRange();
        }
    }

    function enableFormatPainter() {
        isFormatPainterActive = true;
        _painterSelectionSnapshot = null;
        const btn = document.getElementById('btn-format-painter');
        if (btn) {
            btn.style.backgroundColor = '#e8f0fe';
            btn.style.color = '#1a73e8';
            btn.style.boxShadow = '0 0 0 2px #1a73e8';
            btn.title = formatPainterLocked ? 'Sao chép định dạng (đã khoá – nhấn Esc để dừng)' : 'Sao chép định dạng (đang hoạt động)';
        }
        document.body.classList.add('format-painter-active');
        document.addEventListener('mousedown', _handlePainterMouseDown);
        document.addEventListener('selectionchange', _handlePainterSelectionChange);
        document.addEventListener('mouseup', handlePainterMouseUp);
        document.addEventListener('keydown', handlePainterKeydown);
    }

    function disableFormatPainter() {
        isFormatPainterActive = false;
        formatPainterLocked = false;
        storedFormat = null;
        _painterSelectionSnapshot = null;
        const btn = document.getElementById('btn-format-painter');
        if (btn) {
            btn.style.backgroundColor = '';
            btn.style.color = '';
            btn.style.boxShadow = '';
            btn.title = 'Sao chép định dạng';
        }
        document.body.classList.remove('format-painter-active');
        document.removeEventListener('mousedown', _handlePainterMouseDown);
        document.removeEventListener('selectionchange', _handlePainterSelectionChange);
        document.removeEventListener('mouseup', handlePainterMouseUp);
        document.removeEventListener('keydown', handlePainterKeydown);
    }

    function handlePainterKeydown(e) {
        if (e.key === 'Escape') disableFormatPainter();
    }

    /**
     * Áp dụng format text vào vùng text đang chọn.
     * Nếu không có selection, không làm gì (yêu cầu bôi đen trước khi dán text format).
     */
    function _applyTextFormat(fmt) {
        const sel = window.getSelection();
        if (!sel || sel.rangeCount === 0) return false;
        const selectedText = sel.toString().trim();
        if (selectedText.length === 0) return false;

        const selNode = sel.anchorNode;
        const editable = selNode ? (selNode.nodeType === 3 ? selNode.parentElement : selNode).closest('[contenteditable="true"]') : null;
        if (!editable) return false;

        // 1. Xóa format hiện tại của vùng chọn
        document.execCommand('removeFormat', false, null);

        // 2. Áp dụng lại từng thuộc tính
        if (fmt.bold) document.execCommand('bold', false, null);
        if (fmt.italic) document.execCommand('italic', false, null);
        if (fmt.underline) document.execCommand('underline', false, null);
        if (fmt.strikethrough) document.execCommand('strikeThrough', false, null);
        if (fmt.color && fmt.color !== 'rgb(0, 0, 0)') document.execCommand('foreColor', false, fmt.color);

        if (fmt.fontSize) {
            document.execCommand('fontSize', false, '7');
            const fonts = editable.querySelectorAll('font[size="7"]');
            fonts.forEach(font => {
                font.removeAttribute('size');
                font.style.fontSize = fmt.fontSize;
            });
        }

        // Nếu ô bảng (td) đang giữ font-size riêng thì cũng cập nhật luôn
        const td = editable.closest('td');
        if (td && fmt.fontSize) {
            td.style.fontSize = fmt.fontSize;
            // Dọn dẹp các inline font-size bên trong để tránh xung đột
            td.querySelectorAll('[style*="font-size"]').forEach(node => {
                if (!node.classList.contains('ebmr-field-badge')) node.style.fontSize = '';
            });
        }

        // 3. Sync về model
        if (editable.oninput) editable.oninput();
        const blockItem = editable.closest('.block-item');
        if (blockItem) {
            const item = items.find(i => i.id === blockItem.getAttribute('data-id'));
            if (item) item.dirty = true;
        }
        saveStateDebounced();
        return true;
    }

    function handlePainterMouseUp(e) {
        if (!isFormatPainterActive || !storedFormat) return;

        // Ignore clicks on toolbar itself (except the painter button)
        if (e.target.closest('.editor-toolbar')) {
            if (e.target.closest('#btn-format-painter')) {
                // Re-toggle handled by toggleFormatPainter
            } else {
                disableFormatPainter();
            }
            return;
        }

        let applied = false;

        // ============================================================
        // TYPE: text → dán vào vùng text đang được bôi đen
        // ============================================================
        if (storedFormat.type === 'text') {
            // Ưu tiên dùng snapshot (lưu trong quá trình bôi đen), fallback sang getSelection hiện tại
            let targetRange = _painterSelectionSnapshot;
            if (!targetRange) {
                const sel = window.getSelection();
                if (sel && sel.rangeCount > 0 && sel.toString().trim().length > 0) {
                    targetRange = sel.getRangeAt(0);
                }
            }

            if (targetRange && targetRange.toString().trim().length > 0) {
                // Khôi phục selection rồi áp dụng format
                const sel = window.getSelection();
                sel.removeAllRanges();
                sel.addRange(targetRange);
                applied = _applyTextFormat(storedFormat);
                if (!formatPainterLocked) disableFormatPainter();
                else {
                    const Toast = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 1200 });
                    Toast.fire({ icon: 'success', title: 'Đã dán định dạng' });
                }
                return;
            }

            // Không bôi đen: thử áp dụng vào ô bảng (định dạng cấp ô)
            const td = e.target.closest('td[data-row]');
            if (td) {
                const block = td.closest('.block-item');
                if (block) {
                    const item = items.find(i => i.id === block.dataset.id);
                    if (item && item.type === 'table') {
                        const r = parseInt(td.dataset.row) - 1;
                        const c = parseInt(td.dataset.col);
                        if (r >= 0 && item.data[r] && typeof item.data[r][c] === 'object') {
                            const tc = item.data[r][c];
                            if (storedFormat.bold !== undefined) tc.fontWeight = storedFormat.bold ? 'bold' : '';
                            if (storedFormat.italic !== undefined) tc.fontStyle = storedFormat.italic ? 'italic' : '';
                            if (storedFormat.underline !== undefined) tc.textDecoration = storedFormat.underline ? 'underline' : '';
                            if (storedFormat.fontSize) tc.fontSize = storedFormat.fontSize;
                            if (storedFormat.color && storedFormat.color !== 'rgb(0, 0, 0)') tc.textColor = storedFormat.color;
                            
                            // Dọn dẹp nội dung bên trong để format của td không bị các thẻ con ghi đè
                            tc.content = _cleanUpHtmlContent(tc.content, storedFormat);
                            
                            renderBlocks(); saveStateDebounced(); applied = true;
                        }
                    }
                }
            }
        }
        // ============================================================
        // TYPE: cell → dán vào ô bảng đích (click vào ô, hoặc nhiều ô được chọn)
        // ============================================================
        else if (storedFormat.type === 'cell') {
            const selectedCells = document.querySelectorAll('.selected-cell');

            const applyToCell = (targetCell) => {
                targetCell.backgroundColor = storedFormat.backgroundColor;
                targetCell.textAlign = storedFormat.textAlign;
                targetCell.fontWeight = storedFormat.fontWeight;
                targetCell.fontStyle = storedFormat.fontStyle;
                targetCell.textDecoration = storedFormat.textDecoration;
                targetCell.fontSize = storedFormat.fontSize;
                targetCell.textColor = storedFormat.textColor;
                targetCell.textTransform = storedFormat.textTransform;
                targetCell.borderTop = storedFormat.borderTop;
                targetCell.borderBottom = storedFormat.borderBottom;
                targetCell.borderLeft = storedFormat.borderLeft;
                targetCell.borderRight = storedFormat.borderRight;
                if (storedFormat.writingMode) targetCell.writingMode = storedFormat.writingMode;
                
                // Dọn dẹp thẻ con
                targetCell.content = _cleanUpHtmlContent(targetCell.content, storedFormat);
            };

            if (selectedCells.length > 1) {
                // Nhiều ô được chọn → áp dụng cho tất cả
                saveState();
                selectedCells.forEach(td => {
                    const block = td.closest('.block-item');
                    if (!block) return;
                    const item = items.find(i => i.id === block.dataset.id);
                    if (!item || item.type !== 'table') return;
                    const r = parseInt(td.dataset.row) - 1;
                    const c = parseInt(td.dataset.col);
                    if (r >= 0 && item.data[r] && typeof item.data[r][c] === 'object') applyToCell(item.data[r][c]);
                });
                renderBlocks(); saveStateDebounced(); applied = true;
            } else {
                // Click vào 1 ô cụ thể
                const td = e.target.closest('td[data-row]');
                if (td) {
                    const block = td.closest('.block-item');
                    if (block) {
                        const item = items.find(i => i.id === block.dataset.id);
                        if (item && item.type === 'table') {
                            const r = parseInt(td.dataset.row) - 1;
                            const c = parseInt(td.dataset.col);
                            if (r >= 0 && item.data[r] && item.data[r][c]) {
                                if (typeof item.data[r][c] !== 'object') {
                                    item.data[r][c] = { content: item.data[r][c] || '', rs: 1, cs: 1, hidden: false };
                                }
                                applyToCell(item.data[r][c]);
                                renderBlocks(); saveStateDebounced(); applied = true;
                            }
                        }
                    }
                }
            }
        }
        // ============================================================
        // TYPE: block → dán vào khối (block)
        // ============================================================
        else if (storedFormat.type === 'block') {
            const block = e.target.closest('.block-item');
            if (block) {
                const item = items.find(i => i.id === block.dataset.id);
                if (item) {
                    item.backgroundColor = storedFormat.backgroundColor;
                    item.textAlign = storedFormat.textAlign;
                    item.fontSize = storedFormat.fontSize;
                    item.borderMode = storedFormat.borderMode;
                    renderBlocks(); saveStateDebounced(); applied = true;
                }
            }
        }

        if (applied) {
            if (!formatPainterLocked) {
                disableFormatPainter();
            } else {
                const Toast = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 1200 });
                Toast.fire({ icon: 'success', title: 'Đã dán định dạng – chọn tiếp vùng khác' });
            }
        } else {
            // Nếu không áp dụng được và không lock → tắt
            // Nếu không áp dụng được và không lock → tắt
            if (!formatPainterLocked) disableFormatPainter();
        }
    }

    /**
     * Dọn dẹp nội dung HTML bên trong ô bảng để tránh các thẻ định dạng cũ
     * (như font-size, color, b, i, u) ghi đè lên định dạng cấp độ ô (td).
     */
    function _cleanUpHtmlContent(htmlStr, fmt) {
        if (!htmlStr) return '';
        const div = document.createElement('div');
        div.innerHTML = htmlStr;

        if (fmt.fontSize !== undefined) {
            div.querySelectorAll('[style*="font-size"], font[size]').forEach(n => {
                if (!n.classList.contains('ebmr-field-badge')) {
                    n.style.fontSize = '';
                    n.removeAttribute('size');
                    if (n.tagName.toLowerCase() === 'font' && !n.getAttribute('color') && !n.getAttribute('face')) {
                        _unwrapNode(n);
                    } else if (n.tagName.toLowerCase() === 'span' && !n.getAttribute('style')) {
                        _unwrapNode(n);
                    }
                }
            });
        }
        if (fmt.color !== undefined || fmt.textColor !== undefined) {
            div.querySelectorAll('[style*="color"], font[color]').forEach(n => {
                if (!n.classList.contains('ebmr-field-badge')) {
                    n.style.color = '';
                    n.removeAttribute('color');
                    if (n.tagName.toLowerCase() === 'font' && !n.getAttribute('size') && !n.getAttribute('face')) {
                        _unwrapNode(n);
                    } else if (n.tagName.toLowerCase() === 'span' && !n.getAttribute('style')) {
                        _unwrapNode(n);
                    }
                }
            });
        }
        if (fmt.bold !== undefined || fmt.fontWeight !== undefined) {
            div.querySelectorAll('b, strong').forEach(n => _unwrapNode(n));
            div.querySelectorAll('[style*="font-weight"]').forEach(n => n.style.fontWeight = '');
        }
        if (fmt.italic !== undefined || fmt.fontStyle !== undefined) {
            div.querySelectorAll('i, em').forEach(n => _unwrapNode(n));
            div.querySelectorAll('[style*="font-style"]').forEach(n => n.style.fontStyle = '');
        }
        if (fmt.underline !== undefined || fmt.textDecoration !== undefined) {
            div.querySelectorAll('u').forEach(n => _unwrapNode(n));
            div.querySelectorAll('[style*="text-decoration"]').forEach(n => n.style.textDecoration = '');
        }

        return div.innerHTML;

        function _unwrapNode(node) {
            const p = node.parentNode;
            while (node.firstChild) p.insertBefore(node.firstChild, node);
            p.removeChild(node);
        }
    }

    /**
     * Xóa toàn bộ định dạng của vùng văn bản đang chọn hoặc khối đang chọn.
     */
    function clearFormatting() {
        const selection = window.getSelection();
        if (selection.rangeCount > 0 && selection.toString().trim().length > 0) {
            document.execCommand('removeFormat', false, null);
            
            const selectionNode = selection.anchorNode;
            const editable = selectionNode ? (selectionNode.nodeType === 3 ? selectionNode.parentElement : selectionNode).closest('[contenteditable="true"]') : null;
            if (editable && editable.oninput) {
                editable.oninput();
            }
            const blockItem = editable ? editable.closest('.block-item') : null;
            if (blockItem) {
                const item = items.find(i => i.id === blockItem.getAttribute('data-id'));
                if (item) item.dirty = true;
            }
            saveStateDebounced();
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

        let originalId = fieldId;
        let loopSuffix = '';
        const loopMatch = fieldId.match(/(.+)(_loop_\d+)$/);
        if (loopMatch) {
            originalId = loopMatch[1];
            loopSuffix = loopMatch[2];
        }

        const field = fieldsConfig[originalId];
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
                    if (field.validation.min !== null && field.validation.min !== '') inputAttributes.min = field
                        .validation.min;
                    if (field.validation.max !== null && field.validation.max !== '') inputAttributes.max = field
                        .validation.max;
                }
                if (field.validation.decimal_places && parseInt(field.validation.decimal_places) > 0) {
                    inputAttributes.step = '0.' + '0'.repeat(parseInt(field.validation.decimal_places) - 1) + '1';
                } else {
                    inputAttributes.step = 'any';
                }
            }
        } else if (field.type === 'date') {
            inputType = 'text'; // Fallback for SweetAlert2 older versions
            inputAttributes.type = field.date_format === 'hh:mm dd/mm/yyyy' ? 'datetime-local' : 'date';

            // Luôn luôn hiển thị giá trị input là ngày hiện tại (now)
            const d = new Date();
            if (field.date_format === 'hh:mm dd/mm/yyyy') {
                currentVal = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}T${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`;
            } else {
                currentVal = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
            }
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
                    if (input) input.type = field.date_format === 'hh:mm dd/mm/yyyy' ? 'datetime-local' : 'date';
                }
            },
            onOpen: () => { // Hỗ trợ phiên bản SweetAlert2 cũ
                if (field.type === 'date') {
                    const input = Swal.getInput();
                    if (input) input.type = field.date_format === 'hh:mm dd/mm/yyyy' ? 'datetime-local' : 'date';
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
                    if (field.date_format === 'hh:mm dd/mm/yyyy') {
                        // datetime-local string format: "YYYY-MM-DDTHH:mm"
                        const dtParts = finalValue.split('T');
                        if (dtParts.length === 2) {
                            const dateStr = dtParts[0];
                            const timeStr = dtParts[1];
                            const parts = dateStr.split('-');
                            if (parts.length === 3) {
                                finalValue = `${timeStr} ${parts[2]}/${parts[1]}/${parts[0]}`;
                            }
                        }
                    } else {
                        const parts = finalValue.split('-');
                        if (parts.length === 3) {
                            finalValue = `${parts[2]}/${parts[1]}/${parts[0]}`;
                        }
                    }
                }

                // Lấy giá trị cũ
                const existing = (window.executionValues[fieldId] && window.executionValues[fieldId]['default'] !== undefined) 
                                 ? window.executionValues[fieldId]['default'] : '';
                
                if (existing !== '' && existing !== finalValue) {
                    Swal.fire({
                        title: 'Lý do thay đổi',
                        text: 'Vui lòng nhập lý do thay đổi dữ liệu:',
                        input: 'textarea',
                        inputPlaceholder: 'Nhập lý do thay đổi (bắt buộc)...',
                        showCancelButton: true,
                        confirmButtonText: 'Xác nhận',
                        cancelButtonText: 'Hủy',
                        inputValidator: (val) => {
                            if (!val || !val.trim()) {
                                return 'Vui lòng nhập lý do thay đổi dữ liệu!';
                            }
                        }
                    }).then((reasonResult) => {
                        if (reasonResult.isConfirmed) {
                            applyVariableValue(fieldId, finalValue, reasonResult.value.trim(), field, loopSuffix);
                        }
                    });
                } else {
                    applyVariableValue(fieldId, finalValue, '', field, loopSuffix);
                }
            }
        }).catch(err => {
            console.error("Error in modal promise:", err);
        });
    };

    function applyVariableValue(fieldId, finalValue, reason, field, loopSuffix) {
        if (typeof window.executionValues[fieldId] !== 'object' || window.executionValues[fieldId] === null) {
            window.executionValues[fieldId] = {};
        }

        const oldVal = window.executionValues[fieldId]['default'] !== undefined ? window.executionValues[fieldId]['default'] : '';
        window.executionValues[fieldId]['default'] = finalValue;

        const now = new Date();
        const formattedTime = now.toLocaleDateString('vi-VN') + ' ' + now.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
        
        if (!window.executionValues[fieldId]._meta) window.executionValues[fieldId]._meta = {};
        if (!window.executionValues[fieldId]._meta['default']) window.executionValues[fieldId]._meta['default'] = {};
        
        window.executionValues[fieldId]._meta['default'].by = '{{ session("user.fullName") ?? (session("user.username") ?? "Người dùng thử") }}';
        window.executionValues[fieldId]._meta['default'].at = formattedTime;
        if (reason) {
            window.executionValues[fieldId]._meta['default'].reason = reason;
            window.executionValues[fieldId]._meta['default'].history_count = (window.executionValues[fieldId]._meta['default'].history_count || 0) + 1;
            
            if (!window.executionValues[fieldId]._meta['default'].history_list) {
                window.executionValues[fieldId]._meta['default'].history_list = [];
            }
            window.executionValues[fieldId]._meta['default'].history_list.push({
                val: finalValue,
                old_val: oldVal,
                reason: reason,
                by: window.executionValues[fieldId]._meta['default'].by,
                at: formattedTime
            });
        }

        console.log("window.executionValues after update:", window.executionValues);

        if (typeof window.recalculateAllFormulas === 'function') window.recalculateAllFormulas();
        if (typeof window.renderBlocks === 'function') {
            window.renderBlocks();
        } else if (typeof renderBlocks === 'function') {
            renderBlocks();
        }
        if (field && field.block_id && typeof window.syncLinkedCharts === 'function') {
            window.syncLinkedCharts(field.block_id + (loopSuffix || ''));
        } else if (field && field.block_id && typeof syncLinkedCharts === 'function') {
            syncLinkedCharts(field.block_id + (loopSuffix || ''));
        }
    }

    /**
     * ABBREVIATION (DANH MỤC CHỮ VIẾT TẮT)
     */
    function addAbbreviation() {
        const selection = window.getSelection().toString().trim();
        if (!selection) {
            Swal.fire('Lỗi', 'Vui lòng bôi đen (chọn) một từ viết tắt trong tài liệu trước khi bấm nút này.',
                'warning');
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
                columns: [{
                        label: 'STT',
                        type: 'text',
                        width: '10%'
                    },
                    {
                        label: 'Chữ viết tắt',
                        type: 'text',
                        width: '30%'
                    },
                    {
                        label: 'Ý nghĩa',
                        type: 'text',
                        width: '60%'
                    }
                ],
                data: [
                    [{
                            content: '1',
                            rs: 1,
                            cs: 1,
                            textAlign: 'center'
                        },
                        {
                            content: word,
                            rs: 1,
                            cs: 1,
                            textAlign: 'center',
                            fontWeight: 'bold'
                        },
                        {
                            content: meaning,
                            rs: 1,
                            cs: 1,
                            textAlign: 'left'
                        }
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

            abbrevTable.data.push([{
                    content: stt.toString(),
                    rs: 1,
                    cs: 1,
                    textAlign: 'center'
                },
                {
                    content: word,
                    rs: 1,
                    cs: 1,
                    textAlign: 'center',
                    fontWeight: 'bold'
                },
                {
                    content: meaning,
                    rs: 1,
                    cs: 1,
                    textAlign: 'left'
                }
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
                } catch (e) {
                    console.log('Audio loop error', e);
                }
            }
            playBeep();
            window._alertBeepInterval = setInterval(playBeep, 700); // Lặp liên tục dồn dập (mỗi 0.7 giây)
        } catch (e) {
            console.log('Beeper init error', e);
        }
    };

    window.stopContinuousBeep = function() {
        if (window._alertBeepInterval) {
            clearInterval(window._alertBeepInterval);
            window._alertBeepInterval = null;
        }
    };

    window.handleDateVariableClick = function(event, fieldId, hasDefaultNow) {
        if (event) event.stopPropagation();
        if (!window.isExecutionMode) return;
        if (window.isReadOnly) return;

        let originalId = fieldId;
        let loopSuffix = '';
        const loopMatch = fieldId.match(/(.+)(_loop_\d+)$/);
        if (loopMatch) {
            originalId = loopMatch[1];
            loopSuffix = loopMatch[2];
        }

        const field = fieldsConfig[originalId];
        const isNow = hasDefaultNow || (field && field.defaultValue && field.defaultValue.toLowerCase() === 'now');

        if (isNow) {
            autoFillDateVariable(fieldId);
        } else {
            openVariableInputModal(fieldId);
        }
    };

    window.autoFillDateVariable = function(fieldId) {
        if (!window.isExecutionMode) return;
        if (window.isReadOnly) return;

        let originalId = fieldId;
        let loopSuffix = '';
        const loopMatch = fieldId.match(/(.+)(_loop_\d+)$/);
        if (loopMatch) {
            originalId = loopMatch[1];
            loopSuffix = loopMatch[2];
        }

        const field = fieldsConfig[originalId] || {};
        const now = new Date();
        let timeString = '';
        if (field.date_format === 'hh:mm dd/mm/yyyy') {
            timeString = `${now.getHours().toString().padStart(2, '0')}:${now.getMinutes().toString().padStart(2, '0')} ${now.getDate().toString().padStart(2, '0')}/${(now.getMonth()+1).toString().padStart(2, '0')}/${now.getFullYear()}`;
        } else {
            timeString = `${now.getDate().toString().padStart(2, '0')}/${(now.getMonth()+1).toString().padStart(2, '0')}/${now.getFullYear()}`;
        }

        if (!window.executionValues) window.executionValues = {};
        if (typeof window.executionValues[fieldId] !== 'object' || window.executionValues[fieldId] === null) {
            window.executionValues[fieldId] = {};
        }
        window.executionValues[fieldId]['default'] = timeString;

        const formattedTime = new Date().toLocaleDateString('vi-VN') + ' ' + new Date().toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
        if (!window.executionValues[fieldId]._meta) window.executionValues[fieldId]._meta = {};
        window.executionValues[fieldId]._meta['default'] = {
            by: '{{ session('user.fullName') ?? (session('user.username') ?? '') }}',
            at: formattedTime
        };

        if (typeof window.recalculateAllFormulas === 'function') window.recalculateAllFormulas();
        if (typeof renderBlocks === 'function') renderBlocks();
        if (field && field.block_id && typeof syncLinkedCharts === 'function') {
            syncLinkedCharts(field.block_id + loopSuffix);
        }
    };

    function autoFillTime(blockId, r, c) {
        if (!window.isExecutionMode) return;

        let originalId = blockId;
        let loopSuffix = '';
        const loopMatch = blockId.match(/(.+)(_loop_\d+)$/);
        if (loopMatch) {
            originalId = loopMatch[1];
            loopSuffix = loopMatch[2];
        }

        const now = new Date();
        const timeString =
            `${now.getHours().toString().padStart(2, '0')}:${now.getMinutes().toString().padStart(2, '0')} ${now.getDate().toString().padStart(2, '0')}/${(now.getMonth()+1).toString().padStart(2, '0')}/${now.getFullYear()}`;

        if (!window.executionValues) window.executionValues = {};
        if (!window.executionValues[blockId]) window.executionValues[blockId] = {};

        window.executionValues[blockId][`${r}_${c}`] = timeString;

        if (!window.executionValues[blockId]._meta) window.executionValues[blockId]._meta = {};
        window.executionValues[blockId]._meta[`${r}_${c}`] = {
            by: '{{ session('user.fullName') ?? (session('user.username') ?? '') }}',
            at: now.toLocaleDateString('vi-VN') + ' ' + now.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' })
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
                const itm = items.find(i => i.id === originalId || i.uuid === originalId);
                if (itm && itm.freq_minutes) itemFreq = parseInt(itm.freq_minutes);
            } else if (window.items) {
                const itm = window.items.find(i => i.id === originalId || i.uuid === originalId);
                if (itm && itm.freq_minutes) itemFreq = parseInt(itm.freq_minutes);
            }

            // Fallback: Tìm thẻ span trong table cell để lấy data-freq (Hỗ trợ chế độ Execute)
            if (!itemFreq) {
                const cellBadge = document.querySelector(
                    `td[data-row="${r+1}"][data-col="${c}"] .execution-badge.time`);
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
        } catch (e) {
            console.error(e);
        }

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

        const currentUserSignature = @json(session('user.signature_image') ?? (session('user')['signature_image'] ?? null));
        const currentUser = '{{ session('user.fullName') ?? (session('user.username') ?? 'Người thực hiện') }}';
        const signatureVal = currentUserSignature ? currentUserSignature : currentUser;

        if (!window.executionValues) window.executionValues = {};
        if (!window.executionValues[blockId]) window.executionValues[blockId] = {};

        window.executionValues[blockId][`${r}_${c}`] = signatureVal;

        const formattedTime = new Date().toLocaleDateString('vi-VN') + ' ' + new Date().toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
        
        if (!window.executionValues[blockId]._meta) window.executionValues[blockId]._meta = {};
        window.executionValues[blockId]._meta[`${r}_${c}`] = {
            by: currentUser,
            at: formattedTime
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

        fetch('{{ route('pages.ebmr.verifyChecker') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]') ? document.querySelector(
                        'meta[name="csrf-token"]').content : ''
                },
                body: JSON.stringify({
                    username: username,
                    password: password
                })
            })
            .then(res => res.json())
            .then(data => {
                btn.innerHTML = oldText;
                btn.disabled = false;

                if (data.success) {
                    $('#checkerAuthModal').modal('hide');

                    if (!window.executionValues) window.executionValues = {};
                    if (!window.executionValues[blockId]) window.executionValues[blockId] = {};

                    const cellKey = (r === 'default' && c === 'default') ? 'default' : `${r}_${c}`;

                    window.executionValues[blockId][cellKey] = data.signature_image ? data.signature_image :
                        data.fullName;

                    const formattedTime = new Date().toLocaleDateString('vi-VN') + ' ' + new Date().toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
                    if (!window.executionValues[blockId]._meta) window.executionValues[blockId]._meta = {};
                    window.executionValues[blockId]._meta[cellKey] = {
                        by: data.fullName,
                        at: formattedTime
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
                    const linkHtml =
                        `<a href="/ebmr/document/view-by-code/${docCode}" class="ebmr-doc-link" target="_blank" data-doc-code="${originalText}">${originalText}</a>`;

                    // Chèn thẻ liên kết HTML vào văn bản
                    document.execCommand('insertHTML', false, linkHtml);

                    // Kích hoạt đồng bộ hóa dữ liệu (trigger oninput)
                    const selectionNode = sel.anchorNode;
                    const activeCell = selectionNode ? (selectionNode.nodeType === 3 ? selectionNode
                        .parentElement : selectionNode).closest('.mini-table td') : null;
                    const editable = activeCell || (selectionNode ? (selectionNode.nodeType === 3 ?
                        selectionNode.parentElement : selectionNode).closest(
                        '[contenteditable="true"]') : null);

                    if (editable && typeof editable.oninput === 'function') {
                        editable.oninput();
                    }

                    // Lưu trạng thái ngay lập tức
                    if (typeof saveStateDebounced === 'function') {
                        saveStateDebounced();
                    }

                    // Hiển thị Toast thông báo thành công nếu hệ thống có tích hợp toastr
                    if (typeof toastr !== 'undefined') {
                        toastr.success(
                            `Liên kết thành công tài liệu: ${response.fileName} (Version ${response.version})`
                        );
                    }
                } else {
                    const errorMsg = (response && response.message) ? response.message :
                        'Không tìm thấy tài liệu thực tế trên máy chủ.';
                    if (typeof toastr !== 'undefined') {
                        toastr.error(errorMsg, 'Cảnh báo tài liệu không tồn tại', {
                            timeOut: 8000,
                            closeButton: true
                        });
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
                    toastr.error(errorMsg, 'Lỗi kết nối hệ thống', {
                        timeOut: 8000,
                        closeButton: true
                    });
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
        const activeCell = selectionNode ? (selectionNode.nodeType === 3 ? selectionNode.parentElement : selectionNode)
            .closest('.mini-table td') : null;
        const editable = activeCell || (selectionNode ? (selectionNode.nodeType === 3 ? selectionNode.parentElement :
            selectionNode).closest('[contenteditable="true"]') : null);

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

                        return fetch('{{ route('pages.ebmr.verifyPassword') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': csrfToken
                                },
                                body: JSON.stringify({
                                    password: password,
                                    _token: csrfToken
                                })
                            })
                            .then(res => {
                                if (!res.ok) throw new Error('Lỗi kết nối máy chủ');
                                return res.json();
                            })
                            .then(data => {
                                if (!data.success) {
                                    Swal.showValidationMessage(data.message ||
                                        'Mật khẩu không chính xác');
                                    return false;
                                }
                                return data;
                            })
                            .catch(err => {
                                Swal.showValidationMessage('Không thể kết nối đến máy chủ: ' + err
                                    .message);
                                return false;
                            });
                    },
                    allowOutsideClick: () => !Swal.isLoading()
                }).then(result => {
                    if (result.isConfirmed && result.value) {
                        const data = result.value;
                        const signatureVal = data.signature_image ? data.signature_image : (data.fullName ||
                            'Đã ký');

                        if (!window.executionValues) window.executionValues = {};
                        if (!window.executionValues[blockId]) window.executionValues[blockId] = {};
                        window.executionValues[blockId][cellKey] = signatureVal;

                        // Gán metadata ngay lập tức
                        const now = new Date();
                        const formattedTime = now.toLocaleDateString('vi-VN') + ' ' + now
                            .toLocaleTimeString('vi-VN', {
                                hour: '2-digit',
                                minute: '2-digit'
                            });
                        if (!window.executionValues[blockId]._meta) window.executionValues[blockId]
                            ._meta = {};
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
                const existingVal = (window.executionValues && window.executionValues[blockId]) ?
                    (window.executionValues[blockId][cellKey] || '') :
                    '';

                Swal.fire({
                    title: 'Nhập dữ liệu',
                    input: 'textarea',
                    inputValue: existingVal,
                    inputAttributes: {
                        rows: 3,
                        placeholder: 'Nhập nội dung...'
                    },
                    showCancelButton: true,
                    confirmButtonText: 'Xác nhận',
                    cancelButtonText: 'Hủy',
                }).then(result => {
                    if (result.isConfirmed) {
                        if (!window.executionValues) window.executionValues = {};
                        if (!window.executionValues[blockId]) window.executionValues[blockId] = {};
                        window.executionValues[blockId][cellKey] = result.value;
                        
                        const now = new Date();
                        const formattedTime = now.toLocaleDateString('vi-VN') + ' ' + now.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
                        if (!window.executionValues[blockId]._meta) window.executionValues[blockId]._meta = {};
                        window.executionValues[blockId]._meta[cellKey] = {
                            by: '{{ session("user.fullName") ?? (session("user.username") ?? "") }}',
                            at: formattedTime
                        };

                        if (typeof renderBlocks === 'function') renderBlocks();
                        if (typeof syncLinkedCharts === 'function') syncLinkedCharts(blockId);
                    }
                });
            }
        };
    }

    window.handleCheckboxChange = function(fieldId, isChecked, element) {
        if (!window.executionValues) window.executionValues = {};
        
        const existing = (window.executionValues[fieldId] && window.executionValues[fieldId]['default'] !== undefined) 
                         ? window.executionValues[fieldId]['default'] : '';

        if (existing !== '' && existing !== isChecked) {
            if (element) {
                element.checked = !isChecked; // revert visually
            }
            
            Swal.fire({
                title: 'Lý do thay đổi',
                text: 'Vui lòng nhập lý do thay đổi dữ liệu:',
                input: 'textarea',
                inputPlaceholder: 'Nhập lý do thay đổi (bắt buộc)...',
                showCancelButton: true,
                confirmButtonText: 'Xác nhận',
                cancelButtonText: 'Hủy',
                inputValidator: (value) => {
                    if (!value || !value.trim()) {
                        return 'Vui lòng nhập lý do thay đổi dữ liệu!'
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    if (element) element.checked = isChecked; // apply visually
                    applyCheckboxChange(fieldId, isChecked, result.value.trim());
                }
            });
        } else {
            applyCheckboxChange(fieldId, isChecked, '');
        }
    };

    function applyCheckboxChange(fieldId, isChecked, reason) {
        if (typeof window.executionValues[fieldId] !== 'object' || window.executionValues[fieldId] === null) {
            window.executionValues[fieldId] = {};
        }
        window.executionValues[fieldId]['default'] = isChecked;

        const now = new Date();
        const formattedTime = now.toLocaleDateString('vi-VN') + ' ' + now.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
        
        if (!window.executionValues[fieldId]._meta) window.executionValues[fieldId]._meta = {};
        if (!window.executionValues[fieldId]._meta['default']) window.executionValues[fieldId]._meta['default'] = {};
        
        window.executionValues[fieldId]._meta['default'].by = '{{ session("user.fullName") ?? (session("user.username") ?? "") }}';
        window.executionValues[fieldId]._meta['default'].at = formattedTime;
        if (reason) {
            window.executionValues[fieldId]._meta['default'].reason = reason;
            window.executionValues[fieldId]._meta['default'].history_count = (window.executionValues[fieldId]._meta['default'].history_count || 0) + 1;
        }

        if (typeof window.recalculateAllFormulas === 'function') window.recalculateAllFormulas();
        if (typeof window.renderBlocks === 'function') window.renderBlocks();
    }

    window.handleSelectChange = function(fieldId, value, element) {
        if (!window.executionValues) window.executionValues = {};
        
        const existing = (window.executionValues[fieldId] && window.executionValues[fieldId]['default'] !== undefined) 
                         ? window.executionValues[fieldId]['default'] : '';

        if (existing !== '' && existing !== value) {
            if (element) {
                element.value = existing; // revert visually
            }
            
            Swal.fire({
                title: 'Lý do thay đổi',
                text: 'Vui lòng nhập lý do thay đổi dữ liệu:',
                input: 'textarea',
                inputPlaceholder: 'Nhập lý do thay đổi (bắt buộc)...',
                showCancelButton: true,
                confirmButtonText: 'Xác nhận',
                cancelButtonText: 'Hủy',
                inputValidator: (val) => {
                    if (!val || !val.trim()) {
                        return 'Vui lòng nhập lý do thay đổi dữ liệu!'
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    if (element) element.value = value; // apply visually
                    applySelectChange(fieldId, value, result.value.trim());
                }
            });
        } else {
            applySelectChange(fieldId, value, '');
        }
    };

    function applySelectChange(fieldId, value, reason) {
        if (typeof window.executionValues[fieldId] !== 'object' || window.executionValues[fieldId] === null) {
            window.executionValues[fieldId] = {};
        }
        window.executionValues[fieldId]['default'] = value;

        const now = new Date();
        const formattedTime = now.toLocaleDateString('vi-VN') + ' ' + now.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
        
        if (!window.executionValues[fieldId]._meta) window.executionValues[fieldId]._meta = {};
        if (!window.executionValues[fieldId]._meta['default']) window.executionValues[fieldId]._meta['default'] = {};
        
        window.executionValues[fieldId]._meta['default'].by = '{{ session("user.fullName") ?? (session("user.username") ?? "") }}';
        window.executionValues[fieldId]._meta['default'].at = formattedTime;
        if (reason) {
            window.executionValues[fieldId]._meta['default'].reason = reason;
            window.executionValues[fieldId]._meta['default'].history_count = (window.executionValues[fieldId]._meta['default'].history_count || 0) + 1;
        }

        if (typeof window.recalculateAllFormulas === 'function') window.recalculateAllFormulas();
        if (typeof window.renderBlocks === 'function') window.renderBlocks();
    }

    // --- Block Range Selection & Loop Modal Control ---
    window.selectedBlockRange = {
        startId: null,
        endId: null
    };

    window.handleBlockClick = function(e, item) {
        if (window.isExecutionMode) return;
        e.stopPropagation();

        if (e.shiftKey) {
            if (!window.selectedBlockRange) {
                window.selectedBlockRange = {
                    startId: null,
                    endId: null
                };
            }
            // Nếu chưa có startId nhưng đã có block đang active (selectedId), dùng nó làm startId
            let startId = window.selectedBlockRange.startId || selectedId;

            if (!startId) {
                window.selectedBlockRange.startId = item.id;
                window.selectedBlockRange.endId = null;
                selectItem(item.id, true);
            } else {
                window.selectedBlockRange.startId = startId;
                const startItem = items.find(i => i.id === startId);
                if (!startItem) {
                    window.selectedBlockRange.startId = item.id;
                    window.selectedBlockRange.endId = null;
                    selectItem(item.id, true);
                    return;
                }
                const startSectionId = startItem.type === 'section' ? startItem.id : startItem.section_id;
                const currentSectionId = item.type === 'section' ? item.id : item.section_id;
                if (startSectionId !== currentSectionId) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Không hợp lệ',
                        text: 'Các khối được chọn để lặp phải nằm trong cùng một phân đoạn!',
                        confirmButtonText: 'Đồng ý'
                    });
                    return;
                }
                window.selectedBlockRange.endId = item.id;
                renderBlocks();
            }
        } else {
            window.selectedBlockRange = {
                startId: null,
                endId: null
            };
            if (selectedId !== item.id) {
                selectItem(item.id, true);
            }
        }
    };

    window.getSelectedBlockRangeIds = function() {
        if (!window.selectedBlockRange || !window.selectedBlockRange.startId || !window.selectedBlockRange.endId) {
            return [];
        }
        const startIdx = items.findIndex(i => i.id === window.selectedBlockRange.startId);
        const endIdx = items.findIndex(i => i.id === window.selectedBlockRange.endId);
        if (startIdx === -1 || endIdx === -1) return [];

        const minIdx = Math.min(startIdx, endIdx);
        const maxIdx = Math.max(startIdx, endIdx);

        return items.slice(minIdx, maxIdx + 1).map(i => i.id);
    };

    window.openBlockLoopModal = function() {
        if (!window.selectedBlockRange || !window.selectedBlockRange.startId || !window.selectedBlockRange.endId) {
            Swal.fire({
                icon: 'info',
                title: 'Hướng dẫn chọn nhóm khối',
                text: 'Vui lòng giữ phím Shift và Click chọn khối đầu tiên, sau đó tiếp tục giữ Shift và Click chọn khối cuối cùng để chọn nhóm khối cần cài đặt lặp.',
                confirmButtonText: 'Đã hiểu'
            });
            return;
        }

        const rangeIds = window.getSelectedBlockRangeIds();
        if (rangeIds.length === 0) return;

        let existingLoopCount = 3;
        for (let id of rangeIds) {
            const block = items.find(i => i.id === id);
            if (block && block.loop_count) {
                existingLoopCount = block.loop_count;
                break;
            }
        }

        const input = document.getElementById('blockLoopCount');
        if (input) {
            input.value = existingLoopCount;
        }

        $('#blockLoopModal').modal('show');
    };

    window.editLoopGroup = function(groupId) {
        if (!groupId) return;
        
        const blocksInGroup = items.filter(i => i.loop_group_id === groupId);
        if (blocksInGroup.length === 0) return;

        window.selectedBlockRange = {
            startId: blocksInGroup[0].id,
            endId: blocksInGroup[blocksInGroup.length - 1].id
        };
        
        selectedId = null;
        renderBlocks();
        
        const existingLoopCount = blocksInGroup[0].loop_count || 3;
        const input = document.getElementById('blockLoopCount');
        if (input) input.value = existingLoopCount;
        
        $('#blockLoopModal').modal('show');
    };

    window.applyBlockLoopGroup = function() {
        const rangeIds = window.getSelectedBlockRangeIds();
        if (rangeIds.length === 0) return;

        const loopCountInput = document.getElementById('blockLoopCount');
        const loopCount = parseInt(loopCountInput.value) || 3;

        let groupId = 'group_' + Date.now();
        for (let id of rangeIds) {
            const block = items.find(i => i.id === id);
            if (block && block.loop_group_id) {
                groupId = block.loop_group_id;
                break;
            }
        }

        saveState();
        rangeIds.forEach(id => {
            const block = items.find(i => i.id === id);
            if (block) {
                block.loop_group_id = groupId;
                block.loop_count = loopCount;
                block.dirty = true;
            }
        });

        window.selectedBlockRange = {
            startId: null,
            endId: null
        };

        $('#blockLoopModal').modal('hide');
        renderBlocks();
        saveStateDebounced();
        toastr.success('Đã áp dụng lặp cho nhóm khối!');
    };

    window.removeBlockLoopGroup = function() {
        const rangeIds = window.getSelectedBlockRangeIds();
        if (rangeIds.length === 0) return;

        saveState();
        rangeIds.forEach(id => {
            const block = items.find(i => i.id === id);
            if (block) {
                delete block.loop_group_id;
                delete block.loop_count;
                block.dirty = true;
            }
        });

        window.selectedBlockRange = {
            startId: null,
            endId: null
        };

        $('#blockLoopModal').modal('hide');
        renderBlocks();
        saveStateDebounced();
        toastr.info('Đã gỡ bỏ lặp nhóm khối!');
    };

    window.blockClipboard = null;

    window.copyBlock = function() {
        if (window.isExecutionMode) return;

        let targetIds = [];
        const rangeIds = (typeof getSelectedBlockRangeIds === 'function') ? getSelectedBlockRangeIds() : [];
        if (rangeIds && rangeIds.length > 0) {
            targetIds = [...rangeIds];
        } else if (selectedId) {
            targetIds = [selectedId];
        }

        if (targetIds.length === 0) {
            toastr.warning('Vui lòng chọn ít nhất 1 khối để sao chép.');
            return;
        }

        const blocksToCopy = [];
        for (let id of targetIds) {
            const block = items.find(i => i.id === id);
            if (block) {
                blocksToCopy.push(JSON.parse(JSON.stringify(block)));
            }
        }

        if (blocksToCopy.length === 0) return;

        window.blockClipboard = {
            action: 'copy',
            blocks: blocksToCopy
        };

        toastr.success(`Đã sao chép ${blocksToCopy.length} khối.`);
    };

    window.cutBlock = function() {
        if (window.isExecutionMode) return;

        let targetIds = [];
        const rangeIds = (typeof getSelectedBlockRangeIds === 'function') ? getSelectedBlockRangeIds() : [];
        if (rangeIds && rangeIds.length > 0) {
            targetIds = [...rangeIds];
        } else if (selectedId) {
            targetIds = [selectedId];
        }

        if (targetIds.length === 0) {
            toastr.warning('Vui lòng chọn ít nhất 1 khối để cắt.');
            return;
        }

        const blocksToCut = [];
        saveState();

        for (let id of targetIds) {
            const block = items.find(i => i.id === id);
            if (block) {
                if (block.locked) {
                    toastr.warning(`Khối "${block.label || block.id}" đã bị khóa và không thể cắt.`);
                    continue;
                }
                blocksToCut.push(JSON.parse(JSON.stringify(block)));

                if (block.db_id) {
                    window.deletedBlockIds.push(block.db_id);
                }
            }
        }

        if (blocksToCut.length === 0) return;

        window.blockClipboard = {
            action: 'cut',
            blocks: blocksToCut
        };

        items = items.filter(i => !targetIds.includes(i.id));
        selectedId = null;
        window.selectedBlockRange = {
            startId: null,
            endId: null
        };

        const panel = document.getElementById('property-panel');
        if (panel) panel.classList.add('d-none');

        renderBlocks();
        saveStateDebounced();
        toastr.success(`Đã cắt ${blocksToCut.length} khối.`);
    };

    window.pasteBlock = function() {
        if (window.isExecutionMode) return;
        if (!window.blockClipboard || !window.blockClipboard.blocks || window.blockClipboard.blocks.length === 0) {
            toastr.warning('Bộ nhớ tạm trống hoặc không chứa khối hợp lệ.');
            return;
        }

        saveState();

        let insertIndex = items.length;
        let targetSectionId = window.activeSectionId || null;

        if (selectedId) {
            const currentIdx = items.findIndex(i => i.id === selectedId);
            if (currentIdx !== -1) {
                insertIndex = currentIdx + 1;
                const currentItem = items[currentIdx];
                targetSectionId = currentItem.section_id || (currentItem.type === 'section' ? currentItem.id :
                    null);
            }
        } else if (window.activeSectionId) {
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

        if (!targetSectionId && items.length > 0) {
            targetSectionId = items[0].section_id || (items[0].type === 'section' ? items[0].id : null);
        }

        const clonedBlocks = window.blockClipboard.blocks.map(block => {
            const newBlock = JSON.parse(JSON.stringify(block));
            delete newBlock.db_id;

            const newBlockId = 'blk_' + Date.now() + '_' + Math.floor(Math.random() * 1000) + '_' + Math
                .random().toString(36).substr(2, 5);
            newBlock.id = newBlockId;
            newBlock.section_id = targetSectionId;
            newBlock.dirty = true;

            if (newBlock.type === 'static-text' && newBlock.content) {
                newBlock.content = window.duplicateFieldBadgesInHtml(newBlock.content, newBlockId,
                    targetSectionId);
            } else if (newBlock.type === 'table' && newBlock.data) {
                for (let r = 0; r < newBlock.data.length; r++) {
                    for (let c = 0; c < newBlock.data[r].length; c++) {
                        if (newBlock.data[r][c] && typeof newBlock.data[r][c] === 'object') {
                            delete newBlock.data[r][c].db_id;
                            delete newBlock.data[r][c].content_db_id;
                            if (newBlock.data[r][c].content) {
                                newBlock.data[r][c].content = window.duplicateFieldBadgesInHtml(newBlock.data[r]
                                    [c].content, newBlockId, targetSectionId);
                            }
                        }
                    }
                }
            }

            return newBlock;
        });

        items.splice(insertIndex, 0, ...clonedBlocks);

        if (clonedBlocks.length === 1) {
            selectedId = clonedBlocks[0].id;
        } else if (clonedBlocks.length > 1) {
            window.selectedBlockRange = {
                startId: clonedBlocks[0].id,
                endId: clonedBlocks[clonedBlocks.length - 1].id
            };
            selectedId = null;
        }

        renderBlocks();
        saveStateDebounced();
        toastr.success(`Đã dán thành công ${clonedBlocks.length} khối.`);
    };

    /**
     * Chuyển đổi kiểu chữ cho một chuỗi văn bản thô (Unicode-safe và tương thích ngược cao).
     */
    function transformStringCase(str, caseType) {
        if (!str) return '';
        const letterRegex = /[a-zA-ZáàảãạăắằẳẵặâấầẩẫậéèẻẽẹêếềểễệíìỉĩịóòỏõọôốồổỗộơớờởỡợúùủũụưứừửữựýỳỷỹỵđĐ]/;
        switch (caseType) {
            case 'lower':
                return str.toLowerCase();
            case 'upper':
                return str.toUpperCase();
            case 'title':
                // Viết hoa chữ cái đầu tiên của mỗi từ
                return str.toLowerCase().split(' ').map(word => {
                    if (!word) return '';
                    const letterIdx = word.search(letterRegex);
                    if (letterIdx !== -1) {
                        return word.substring(0, letterIdx) + word.charAt(letterIdx).toUpperCase() + word
                            .substring(letterIdx + 1);
                    }
                    return word;
                }).join(' ');
            case 'sentence':
                // Viết hoa chữ cái đầu tiên của câu (sau dấu chấm, hỏi chấm, chấm than và khoảng trắng)
                let lower = str.toLowerCase();
                let parts = lower.split(/([\.\?\!]\s+)/);
                for (let i = 0; i < parts.length; i += 2) {
                    let s = parts[i];
                    if (s) {
                        const letterIdx = s.search(letterRegex);
                        if (letterIdx !== -1) {
                            parts[i] = s.substring(0, letterIdx) + s.charAt(letterIdx).toUpperCase() + s.substring(
                                letterIdx + 1);
                        } else if (s.length > 0) {
                            parts[i] = s.charAt(0).toUpperCase() + s.slice(1);
                        }
                    }
                }
                return parts.join('');
            case 'toggle':
                // Đảo ngược chữ hoa/chữ thường
                return str.split('').map(c => {
                    const u = c.toUpperCase();
                    const l = c.toLowerCase();
                    return c === u ? l : u;
                }).join('');
            default:
                return str;
        }
    }

    /**
     * Duyệt qua toàn bộ các node văn bản trong một DOM Fragment/Node và thay đổi kiểu chữ của chúng.
     * Thuật toán này bảo toàn toàn bộ cấu trúc HTML bên trong (không làm thay đổi thẻ HTML).
     */
    function modifyTextNodesInFragment(fragment, caseType) {
        const textNodes = [];

        function traverse(node) {
            if (node.nodeType === Node.TEXT_NODE) {
                textNodes.push(node);
            } else {
                for (let i = 0; i < node.childNodes.length; i++) {
                    traverse(node.childNodes[i]);
                }
            }
        }
        traverse(fragment);

        if (textNodes.length === 0) return;

        // Nối các chuỗi text lại với nhau
        let fullText = textNodes.map(node => node.nodeValue).join('');

        // Thực hiện chuyển đổi
        let transformedText = transformStringCase(fullText, caseType);

        // Ánh xạ lại các ký tự đã được chuyển đổi về từng text node ban đầu
        let charOffset = 0;
        for (const node of textNodes) {
            const len = node.nodeValue.length;
            node.nodeValue = transformedText.substring(charOffset, charOffset + len);
            charOffset += len;
        }
    }

    /**
     * Chuyển đổi nội dung của một chuỗi HTML mà không làm hư hại các thẻ HTML tag.
     */
    function changeHtmlStringCase(html, caseType) {
        if (!html) return '';
        const temp = document.createElement('div');
        temp.innerHTML = html;
        modifyTextNodesInFragment(temp, caseType);
        return temp.innerHTML;
    }

    /**
     * Áp dụng chuyển đổi kiểu chữ tự động dựa trên trạng thái selection của người dùng.
     */
    window.applyTextChangeCase = function(caseType) {
        if (savedTextSelection) {
            const sel = window.getSelection();
            sel.removeAllRanges();
            sel.addRange(savedTextSelection);
        }
        const selection = window.getSelection();
        const selectedText = selection.toString().trim();
        const selectedCells = document.querySelectorAll('.selected-cell');

        // TH 1: Có vùng văn bản đang được bôi đen
        if (selectedText.length > 0) {
            saveState();
            let range = selection.getRangeAt(0);
            let fragment = range.extractContents();

            modifyTextNodesInFragment(fragment, caseType);

            let firstChild = fragment.firstChild;
            let lastChild = fragment.lastChild;

            range.insertNode(fragment);

            // Chọn lại vùng văn bản đã thay đổi để giữ selection cho người dùng
            if (firstChild && lastChild) {
                let newRange = document.createRange();
                newRange.setStartBefore(firstChild);
                newRange.setEndAfter(lastChild);
                selection.removeAllRanges();
                selection.addRange(newRange);
            }

            // Đồng bộ ngược lại dữ liệu JSON
            const activeEditable = range.startContainer.parentElement.closest('[contenteditable="true"]');
            if (activeEditable) {
                if (activeEditable.oninput) {
                    activeEditable.oninput();
                }
                const blockItem = activeEditable.closest('.block-item');
                if (blockItem) {
                    const item = items.find(i => i.id === blockItem.getAttribute('data-id'));
                    if (item) item.dirty = true;
                }
            }
            saveStateDebounced();
            return;
        }

        // TH 2: Nhiều ô bảng được chọn (selected cells)
        if (selectedCells.length > 0) {
            saveState();
            selectedCells.forEach(cell => {
                const r = parseInt(cell.dataset.row);
                const c = parseInt(cell.dataset.col);
                const table = cell.closest('.mini-table');
                const blockItem = table ? table.closest('.block-item') : null;
                const itemId = blockItem ? blockItem.getAttribute('data-id') : null;
                const item = items.find(i => i.id === itemId);

                if (item) {
                    if (r === 0) {
                        item.columns[c].label = changeHtmlStringCase(item.columns[c].label, caseType);
                    } else {
                        const rIdx = r - 1;
                        if (item.data && item.data[rIdx] && item.data[rIdx][c] !== undefined) {
                            if (typeof item.data[rIdx][c] !== 'object') {
                                item.data[rIdx][c] = {
                                    content: changeHtmlStringCase(item.data[rIdx][c], caseType),
                                    rs: 1,
                                    cs: 1,
                                    hidden: false
                                };
                            } else {
                                item.data[rIdx][c].content = changeHtmlStringCase(item.data[rIdx][c]
                                    .content, caseType);
                            }
                        }
                    }
                }
            });
            renderBlocks();
            saveStateDebounced();
            return;
        }

        // TH 3: Một khối (block) được chọn
        if (selectedId) {
            const item = items.find(i => i.id === selectedId);
            if (item) {
                if (item.type === 'static-text') {
                    saveState();
                    item.content = changeHtmlStringCase(item.content, caseType);
                    item.dirty = true;
                    renderBlocks();
                    saveStateDebounced();
                } else if (item.type === 'table') {
                    saveState();
                    if (item.columns) {
                        item.columns.forEach(col => {
                            col.label = changeHtmlStringCase(col.label, caseType);
                        });
                    }
                    if (item.data) {
                        for (let r = 0; r < item.data.length; r++) {
                            for (let c = 0; c < item.data[r].length; c++) {
                                let cell = item.data[r][c];
                                if (cell !== undefined && cell !== null) {
                                    if (typeof cell === 'object') {
                                        cell.content = changeHtmlStringCase(cell.content, caseType);
                                    } else {
                                        item.data[r][c] = changeHtmlStringCase(cell, caseType);
                                    }
                                }
                            }
                        }
                    }
                    item.dirty = true;
                    renderBlocks();
                    saveStateDebounced();
                }
            }
        }
    };

    // ============================================================
    // BULLET / LINE PREFIX ENGINE
    // Chèn hoặc xóa ký tự đầu dòng cho từng dòng HTML.
    // Không dùng <ul>/<li> để đảm bảo in đúng định dạng.
    // ============================================================

    /** Danh sách các ký tự bullet đã biết (để nhận dạng và xóa). */
    const _KNOWN_BULLETS = ['•', '○', '■', '□', '➤', '✓', '✗', '◆', '–'];
    /** Regex nhận dạng bullet đầu dòng (bullet ký tự HOẶC số thứ tự "1. ") */
    const _BULLET_PREFIX_RE = /^(\s*)(•|○|■|□|➤|✓|✗|◆|–|\d+\.\s)/;

    /**
     * Xóa prefix bullet/số khỏi một chuỗi text (plain text, không phải HTML).
     */
    function _stripBulletFromText(text) {
        return text.replace(_BULLET_PREFIX_RE, '$1');
    }

    /**
     * Xóa prefix bullet/số khỏi nội dung HTML của một dòng.
     * Chỉ xử lý text node đầu tiên, giữ nguyên các thẻ HTML khác.
     */
    function _stripBulletFromLineHtml(html) {
        const div = document.createElement('div');
        div.innerHTML = html;
        // Tìm text node đầu tiên có nội dung
        const walker = document.createTreeWalker(div, NodeFilter.SHOW_TEXT);
        let node;
        while ((node = walker.nextNode())) {
            if (node.nodeValue && node.nodeValue.trim()) {
                node.nodeValue = _stripBulletFromText(node.nodeValue);
                break;
            }
        }
        return div.innerHTML;
    }

    /**
     * Thêm prefix bullet/số vào đầu nội dung HTML của một dòng.
     * @param {string} html - HTML của dòng
     * @param {string} bullet - Ký tự bullet hoặc 'none'/'ordered'
     * @param {number} orderNum - Số thứ tự (dùng khi bullet === 'ordered')
     */
    function _addBulletToLineHtml(html, bullet, orderNum = 1) {
        if (bullet === 'none') return _stripBulletFromLineHtml(html);

        const prefix = bullet === 'ordered' ? `${orderNum}. ` : `${bullet} `;
        const stripped = _stripBulletFromLineHtml(html);

        const div = document.createElement('div');
        div.innerHTML = stripped;
        // Tìm và prefix text node đầu tiên
        const walker = document.createTreeWalker(div, NodeFilter.SHOW_TEXT);
        let node;
        while ((node = walker.nextNode())) {
            if (node.nodeValue && node.nodeValue.trim()) {
                node.nodeValue = prefix + node.nodeValue;
                break;
            }
        }
        // Nếu không có text node (dòng trống), chèn text node mới
        if (!walker.currentNode || !div.textContent.startsWith(prefix)) {
            div.insertBefore(document.createTextNode(prefix), div.firstChild);
        }
        return div.innerHTML;
    }

    /**
     * Xử lý bullet cho một chuỗi HTML nhiều dòng (nội dung của static-text block).
     * Mỗi "dòng" là một thẻ <div>, <p>, <br>, hoặc text node trực tiếp.
     * @param {string} html
     * @param {string} bullet
     * @returns {string} HTML đã được xử lý
     */
    function _applyBulletToHtmlContent(html, bullet) {
        const container = document.createElement('div');
        container.innerHTML = html || '';

        // Thu thập các "dòng" — mỗi child trực tiếp là một dòng
        const children = Array.from(container.childNodes);

        // Nếu toàn bộ nội dung là text node phẳng (không có div/p con)
        // → bọc thành div trước
        const hasBlockChildren = children.some(n =>
            n.nodeType === Node.ELEMENT_NODE && /^(div|p|h[1-6]|li)$/i.test(n.tagName)
        );

        if (!hasBlockChildren) {
            // Nội dung phẳng: xử lý toàn bộ như 1 dòng
            container.innerHTML = _addBulletToLineHtml(container.innerHTML, bullet, 1);
            return container.innerHTML;
        }

        // Có các block children: xử lý từng block
        let orderedCounter = 1;
        children.forEach(child => {
            if (child.nodeType === Node.ELEMENT_NODE) {
                const outerTag = child.tagName.toLowerCase();
                const inner = child.innerHTML;
                child.innerHTML = _addBulletToLineHtml(inner, bullet, orderedCounter);
                if (bullet === 'ordered') orderedCounter++;
            }
            // Text node trực tiếp: bỏ qua (thường là whitespace)
        });
        return container.innerHTML;
    }

    /**
     * Hàm chính: Áp dụng bullet/prefix đầu dòng dựa trên trạng thái selection.
     * Hỗ trợ toggle: nếu đã có cùng bullet → xóa đi.
     * @param {string} bullet - Ký tự bullet, 'ordered', hoặc 'none'
     */
    window.applyLineBullet = function(bullet) {
        // Đóng dropdown
        if (window.jQuery) $('.dropdown-menu.show').removeClass('show');

        const selectedCells = document.querySelectorAll('.selected-cell');

        // --- TH 1: Có contenteditable đang focus với text selection hoặc cursor ---
        const activeEl = document.activeElement;
        const editableEl = activeEl && activeEl.closest('[contenteditable="true"]') ? activeEl : null;
        const focusedEditable = editableEl || document.querySelector('[contenteditable="true"]:focus');

        if (focusedEditable && selectedCells.length === 0) {
            saveState();

            // Lấy tất cả các dòng (div/p) trong editable
            const lines = Array.from(focusedEditable.querySelectorAll('div, p'));

            // Nếu không có dòng block, xử lý toàn bộ innerHTML
            if (lines.length === 0) {
                focusedEditable.innerHTML = _applyBulletToHtmlContent(focusedEditable.innerHTML, bullet);
            } else {
                let orderedCounter = 1;
                lines.forEach(line => {
                    line.innerHTML = _addBulletToLineHtml(line.innerHTML, bullet, orderedCounter);
                    if (bullet === 'ordered') orderedCounter++;
                });
            }

            // Sync data
            if (focusedEditable.oninput) focusedEditable.oninput();
            const blockItem = focusedEditable.closest('.block-item');
            if (blockItem) {
                const item = items.find(i => i.id === blockItem.getAttribute('data-id'));
                if (item) item.dirty = true;
            }
            saveStateDebounced();
            return;
        }

        // --- TH 2: Các ô bảng đang được chọn ---
        if (selectedCells.length > 0) {
            saveState();
            selectedCells.forEach(cell => {
                const r = parseInt(cell.dataset.row);
                const c = parseInt(cell.dataset.col);
                const table = cell.closest('.mini-table');
                const blockItem = table ? table.closest('.block-item') : null;
                const itemId = blockItem ? blockItem.getAttribute('data-id') : null;
                const item = items.find(i => i.id === itemId);
                if (!item) return;

                if (r === 0) {
                    item.columns[c].label = _applyBulletToHtmlContent(item.columns[c].label, bullet);
                } else {
                    const rIdx = r - 1;
                    if (item.data && item.data[rIdx] && item.data[rIdx][c] !== undefined) {
                        if (typeof item.data[rIdx][c] !== 'object') {
                            item.data[rIdx][c] = {
                                content: _applyBulletToHtmlContent(item.data[rIdx][c], bullet),
                                rs: 1,
                                cs: 1,
                                hidden: false
                            };
                        } else {
                            item.data[rIdx][c].content = _applyBulletToHtmlContent(
                                item.data[rIdx][c].content, bullet
                            );
                        }
                    }
                }
            });
            renderBlocks();
            saveStateDebounced();
            return;
        }

        // --- TH 3: Một khối (block) đang được chọn ---
        if (selectedId) {
            const item = items.find(i => i.id === selectedId);
            if (!item) return;
            saveState();

            if (item.type === 'static-text') {
                item.content = _applyBulletToHtmlContent(item.content, bullet);
                item.dirty = true;
                renderBlocks();
                saveStateDebounced();
            } else if (item.type === 'table') {
                if (item.columns) {
                    item.columns.forEach(col => {
                        col.label = _applyBulletToHtmlContent(col.label, bullet);
                    });
                }
                if (item.data) {
                    for (let r = 0; r < item.data.length; r++) {
                        let orderedCounter = 1;
                        for (let c = 0; c < item.data[r].length; c++) {
                            let cell = item.data[r][c];
                            if (cell !== undefined && cell !== null) {
                                if (typeof cell === 'object') {
                                    cell.content = _applyBulletToHtmlContent(cell.content, bullet);
                                } else {
                                    item.data[r][c] = _applyBulletToHtmlContent(cell, bullet);
                                }
                            }
                            if (bullet === 'ordered') orderedCounter++;
                        }
                    }
                }
                item.dirty = true;
                renderBlocks();
            }
            return;
        }

        toastr.info('Vui lòng chọn một khối, ô bảng hoặc đặt con trỏ vào vùng văn bản trước.');
    };

    /**
     * CHỨC NĂNG THÊM GHI CHÚ (ADD NOTE)
     */
    function escapeHtml(text) {
        if (!text) return '';
        return text.toString()
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function syncBlockContent(node) {
        if (!node) return;
        const blockEl = node.closest('.block-item');
        if (!blockEl) return;

        const blockId = blockEl.getAttribute('data-id');
        const item = items.find(i => i.id === blockId);
        if (!item) return;

        item.dirty = true;

        if (item.type === 'static-text') {
            const displayEl = blockEl.querySelector('.static-text-display');
            if (displayEl) {
                item.content = displayEl.innerHTML;
            }
        } else if (item.type === 'table') {
            const tdEl = node.closest('td, th');
            if (tdEl) {
                const rStr = tdEl.getAttribute('data-row');
                const cStr = tdEl.getAttribute('data-col');
                if (rStr !== null && cStr !== null) {
                    const r = parseInt(rStr) - 1;
                    const c = parseInt(cStr);
                    if (item.data && item.data[r] && item.data[r][c] !== undefined) {
                        // Extract just the inner content, ignoring wrappers and resizers
                        const wrapper = tdEl.querySelector('.cell-wrapper');
                        const actualContent = wrapper ? wrapper.innerHTML : tdEl.innerHTML;
                        
                        if (typeof item.data[r][c] === 'object' && item.data[r][c] !== null) {
                            item.data[r][c].content = actualContent;
                        } else {
                            item.data[r][c] = {
                                content: actualContent,
                                rs: 1,
                                cs: 1,
                                hidden: false
                            };
                        }
                    }
                }
            }
        }

        if (typeof saveStateDebounced === 'function') {
            saveStateDebounced();
        }
    }

    window.addNote = function() {
        const sel = window.getSelection();
        if (!sel || sel.rangeCount === 0) {
            toastr.warning('Vui lòng đặt con trỏ ở vị trí bất kỳ trong vùng soạn thảo');
            return;
        }

        const range = sel.getRangeAt(0);
        let node = sel.anchorNode;
        if (!node) {
            toastr.warning('Vui lòng đặt con trỏ ở vị trí bất kỳ trong vùng soạn thảo');
            return;
        }
        if (node.nodeType === 3) node = node.parentNode;

        const editorContent = document.getElementById('editor-content');
        if (!editorContent || !editorContent.contains(node)) {
            toastr.warning('Vui lòng đặt con trỏ ở vị trí bất kỳ trong vùng soạn thảo');
            return;
        }

        const editableContainer = node.closest('[contenteditable="true"]');
        if (!editableContainer) {
            toastr.warning('Vui lòng đặt con trỏ ở vị trí bất kỳ trong vùng soạn thảo');
            return;
        }

        const savedRange = range.cloneRange();

        Swal.fire({
            title: 'Thêm ghi chú',
            input: 'textarea',
            inputPlaceholder: 'Nhập nội dung ghi chú...',
            showCancelButton: true,
            confirmButtonText: 'Lưu',
            cancelButtonText: 'Hủy',
            confirmButtonColor: '#f59e0b',
            cancelButtonColor: '#6c757d',
            inputValidator: (value) => {
                if (!value.trim()) {
                    return 'Nội dung ghi chú không được để trống!';
                }
            }
        }).then((result) => {
            if (result.value) {
                const noteText = result.value.trim();

                // Restore range
                sel.removeAllRanges();
                sel.addRange(savedRange);

                // Create badge
                const badge = document.createElement('span');
                badge.contentEditable = "false";
                badge.className = "ebmr-note-badge";
                badge.setAttribute('data-note', noteText);
                badge.setAttribute('onclick', 'window.viewNoteBadge(event, this)');
                badge.title = `Ghi chú: ${noteText}`;
                badge.innerHTML = `<i class="fas fa-sticky-note"></i>`;

                const zeroWidthSpace = document.createTextNode('\u200B');

                savedRange.deleteContents();
                savedRange.insertNode(zeroWidthSpace);
                savedRange.insertNode(badge);
                savedRange.setStartAfter(zeroWidthSpace);
                savedRange.collapse(true);

                sel.removeAllRanges();
                sel.addRange(savedRange);

                // Sync content
                syncBlockContent(badge);

                toastr.success('Đã thêm ghi chú thành công');
            }
        });
    };

    window.viewNoteBadge = function(event, badgeEl) {
        event.stopPropagation();
        event.preventDefault();

        const noteText = badgeEl.getAttribute('data-note') || '';

        if (window.isExecutionMode) {
            Swal.fire({
                title: 'Nội dung ghi chú',
                html: `<div style="border: 1px solid #f59e0b; padding: 12px; border-radius: 6px; background-color: #fffbeb; font-size: 0.95rem; line-height: 1.5; white-space: pre-wrap; text-align: left;">${escapeHtml(noteText)}</div>`,
                showConfirmButton: false,
                allowOutsideClick: true
            });
            return;
        }

        Swal.fire({
            title: 'Chi tiết ghi chú',
            html: `<div style="border: 1px solid #f59e0b; padding: 12px; border-radius: 6px; background-color: #fffbeb; font-size: 0.95rem; line-height: 1.5; white-space: pre-wrap; text-align: left; margin-bottom: 10px;">${escapeHtml(noteText)}</div>`,
            showConfirmButton: true,
            showDenyButton: true,
            showCancelButton: false,
            confirmButtonText: 'Điều chỉnh ghi chú',
            denyButtonText: 'Xóa ghi chú',
            confirmButtonColor: '#3085d6',
            denyButtonColor: '#d33',
            allowOutsideClick: true
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Điều chỉnh ghi chú',
                    input: 'textarea',
                    inputValue: noteText,
                    inputPlaceholder: 'Nhập nội dung ghi chú...',
                    showCancelButton: true,
                    confirmButtonText: 'Lưu',
                    cancelButtonText: 'Hủy',
                    confirmButtonColor: '#f59e0b',
                    cancelButtonColor: '#6c757d',
                    inputValidator: (value) => {
                        if (!value.trim()) {
                            return 'Nội dung ghi chú không được để trống!';
                        }
                    }
                }).then((editResult) => {
                    if (editResult.value) {
                        const newContent = editResult.value.trim();
                        badgeEl.setAttribute('data-note', newContent);
                        badgeEl.title = `Ghi chú: ${newContent}`;

                        syncBlockContent(badgeEl);
                        toastr.success('Đã cập nhật ghi chú');
                    }
                });
            } else if (result.isDenied) {
                Swal.fire({
                    title: 'Xóa ghi chú này?',
                    text: 'Hành động này sẽ gỡ bỏ ghi chú tại vị trí này.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'Xóa',
                    cancelButtonText: 'Hủy'
                }).then((deleteConfirm) => {
                    if (deleteConfirm.value) {
                        const parent = badgeEl.parentNode;
                        badgeEl.remove();
                        if (parent) {
                            syncBlockContent(parent);
                        }
                        toastr.success('Đã xóa ghi chú');
                    }
                });
            }
        });
    };

    // Chặn mousedown trên badge ghi chú để tránh kích hoạt chọn khối/ô trong editor
    document.addEventListener('mousedown', function(e) {
        if (e.target.closest('.ebmr-note-badge')) {
            e.stopPropagation();
        }
    }, true);

    // Lắng nghe sự kiện click vào badge ghi chú (sử dụng Capture Phase để bỏ qua stopPropagation của block)
    document.addEventListener('click', function(e) {
        const badge = e.target.closest('.ebmr-note-badge');
        if (badge) {
            e.stopPropagation();
            e.preventDefault();
            window.viewNoteBadge(e, badge);
        }
    }, true);

    window.showRunDataHistory = async function(event, recordId, blockId, cellId) {
        if (event) event.stopPropagation();
        
        if (!recordId || recordId === 'undefined' || recordId === 'null') {
            if (typeof Swal !== 'undefined') {
                const valObj = window.executionValues[blockId];
                let historyHtml = '<div class="text-muted small mb-3">Lịch sử chi tiết (chỉ lưu tạm thời trong chế độ Chạy thử):</div>';
                historyHtml += '<div class="table-responsive"><table class="table table-bordered table-striped table-hover mb-0 text-center" style="font-size: 13px;">';
                historyHtml += '<thead class="bg-light"><tr><th width="5%">Lần</th><th width="20%">Giá trị cũ</th><th width="20%">Giá trị mới</th><th width="25%">Lý do</th><th width="15%">Người đổi</th><th width="15%">Thời gian</th></tr></thead><tbody>';
                
                if (valObj && valObj._meta && valObj._meta[cellId] && valObj._meta[cellId].history_list && valObj._meta[cellId].history_list.length > 0) {
                    valObj._meta[cellId].history_list.forEach((h, index) => {
                        historyHtml += `<tr>
                            <td>${index + 1}</td>
                            <td>${escapeHtml(h.old_val || '')}</td>
                            <td class="text-primary fw-bold">${escapeHtml(h.val || '')}</td>
                            <td>${escapeHtml(h.reason || '')}</td>
                            <td>${escapeHtml(h.by || '')}</td>
                            <td>${escapeHtml(h.at || '')}</td>
                        </tr>`;
                    });
                } else {
                    historyHtml += `<tr><td colspan="6" class="text-muted">Chưa có thay đổi nào.</td></tr>`;
                }
                historyHtml += '</tbody></table></div>';

                Swal.fire({
                    title: '<i class="fas fa-history me-2"></i> LỊCH SỬ THAY ĐỔI DỮ LIỆU',
                    html: historyHtml,
                    width: '800px',
                    showConfirmButton: false,
                    showCloseButton: true,
                    customClass: {
                        popup: 'rounded-3'
                    }
                });
            } else if (typeof toastr !== 'undefined') {
                toastr.info('Lịch sử chi tiết chỉ được lưu vào cơ sở dữ liệu trong chế độ Thực thi thật.');
            }
            return;
        }
        
        try {
            const response = await fetch(`/ebmr/run-data-history/${recordId}/${blockId}/${cellId}`);
            const res = await response.json();
            
            if (res.success) {
                const tbody = document.getElementById('historyTableBody');
                if (!tbody) {
                    if (typeof toastr !== 'undefined') toastr.error('Không tìm thấy bảng hiển thị lịch sử trên giao diện này.');
                    return;
                }
                tbody.innerHTML = '';
                
                if (res.data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">Không có lịch sử thay đổi</td></tr>';
                } else {
                    res.data.forEach(item => {
                        tbody.innerHTML += `
                            <tr>
                                <td>${item.change_index}</td>
                                <td>${item.old_value}</td>
                                <td>${item.new_value}</td>
                                <td>${item.reason || ''}</td>
                                <td>${item.changed_by}</td>
                                <td>${item.changed_at}</td>
                            </tr>
                        `;
                    });
                }
                
                if (typeof $ !== 'undefined') $('#runDataHistoryModal').modal('show');
            } else {
                if (typeof toastr !== 'undefined') toastr.error('Không thể lấy lịch sử dữ liệu');
            }
        } catch (err) {
            console.error(err);
            if (typeof toastr !== 'undefined') toastr.error('Lỗi kết nối máy chủ');
        }
    };

    // ==========================================
    // TÍNH NĂNG ĐÁNH DẤU N/A (KHÔNG ÁP DỤNG)
    // ==========================================
    let executionSelectedCells = [];
    
    // Gắn sự kiện chọn nhiều ô và context menu trong chế độ thực thi
    document.addEventListener('DOMContentLoaded', () => {
        const container = document.getElementById('editor-content');
        if (!container) return;

        // Context Menu khi right-click
        container.addEventListener('contextmenu', (e) => {
            if (!window.isExecutionMode) return;
            const targetCell = e.target.closest('.execution-input-cell, td');
            if (!targetCell) return;
            
            e.preventDefault();

            // Nếu ô click không nằm trong danh sách đang chọn, chọn riêng ô đó
            if (!executionSelectedCells.includes(targetCell)) {
                clearExecutionSelection();
                targetCell.classList.add('cell-selected-execution');
                executionSelectedCells.push(targetCell);
            }

            // Hiển thị menu N/A tùy chỉnh
            showNAMenu(e.pageX, e.pageY);
        });

        // Click để chọn vùng (Đơn giản hóa: ctrl/shift click hoặc drag)
        container.addEventListener('click', (e) => {
            if (!window.isExecutionMode) return;
            hideNAMenu();

            const targetCell = e.target.closest('td');
            if (!targetCell) {
                clearExecutionSelection();
                return;
            }

            if (e.ctrlKey || e.shiftKey) {
                // Multi-select
                if (executionSelectedCells.includes(targetCell)) {
                    targetCell.classList.remove('cell-selected-execution');
                    executionSelectedCells = executionSelectedCells.filter(c => c !== targetCell);
                } else {
                    targetCell.classList.add('cell-selected-execution');
                    executionSelectedCells.push(targetCell);
                }
            } else {
                // Mặc định click không có phím bổ trợ sẽ vẫn làm hành động mặc định của cell (ví dụ mở modal nhập)
                // Do đó chỉ xóa selection cũ đi
                clearExecutionSelection();
            }
            toggleNAZoneButton();
        });
    });

    function showNAMenu(x, y) {
        let menu = document.getElementById('na-context-menu');
        if (!menu) {
            menu = document.createElement('div');
            menu.id = 'na-context-menu';
            menu.className = 'dropdown-menu shadow-sm p-1';
            menu.style.position = 'absolute';
            menu.style.zIndex = '9999';
            menu.innerHTML = `
                <button class="dropdown-item text-danger small fw-bold rounded" onclick="markSelectedZoneAsNA()">
                    <i class="fas fa-ban me-2"></i> Đánh dấu Không áp dụng (N/A)
                </button>
                <button class="dropdown-item text-primary small fw-bold rounded mt-1" onclick="unmarkNAZone()">
                    <i class="fas fa-undo me-2"></i> Hủy đánh dấu N/A
                </button>
            `;
            document.body.appendChild(menu);
        }
        menu.style.left = x + 'px';
        menu.style.top = y + 'px';
        menu.classList.add('show');
    }

    function hideNAMenu() {
        const menu = document.getElementById('na-context-menu');
        if (menu) menu.classList.remove('show');
    }

    function clearExecutionSelection() {
        executionSelectedCells.forEach(c => c.classList.remove('cell-selected-execution'));
        executionSelectedCells = [];
        toggleNAZoneButton();
    }

    function toggleNAZoneButton() {
        const btn = document.getElementById('btn-na-zone');
        if (!btn) return;
        if (executionSelectedCells.length > 0 && window.isExecutionMode) {
            btn.classList.remove('d-none');
        } else {
            btn.classList.add('d-none');
        }
    }

    // Đánh dấu mảng các ô là N/A
    window.markSelectedZoneAsNA = function() {
        hideNAMenu();
        if (executionSelectedCells.length === 0) return;

        Swal.fire({
            title: '<i class="fas fa-ban me-2 text-danger"></i>Xác nhận N/A',
            html: `
                <div class="text-start mb-3">Bạn có chắc chắn muốn đánh dấu vùng này là <strong>Không áp dụng (N/A)</strong>? Dữ liệu bên dưới sẽ không bị xóa nhưng sẽ được đánh dấu N/A.</div>
                <div class="form-group text-start">
                    <label for="na-reason" class="fw-bold mb-1">Lý do N/A (Tùy chọn):</label>
                    <input type="text" id="na-reason" class="form-control" placeholder="Nhập lý do không áp dụng...">
                </div>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Đồng ý',
            cancelButtonText: 'Hủy',
            preConfirm: () => {
                const reasonInput = document.getElementById('na-reason');
                return reasonInput ? reasonInput.value.trim() : '';
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const reason = result.value || 'N/A';
                applyNAToCells(executionSelectedCells, true, reason);
                clearExecutionSelection();
                renderBlocks();
                if (typeof toastr !== 'undefined') toastr.success('Đã đánh dấu N/A.');
            }
        });
    };

    window.unmarkNAZone = function() {
        hideNAMenu();
        if (executionSelectedCells.length === 0) return;
        applyNAToCells(executionSelectedCells, false);
        clearExecutionSelection();
        renderBlocks();
        if (typeof toastr !== 'undefined') toastr.info('Đã hủy đánh dấu N/A.');
    }

    function applyNAToCells(cells, isNA, reason = 'N/A') {
        cells.forEach(cell => {
            const blockItem = cell.closest('.block-item');
            if (!blockItem) return;
            const blockId = blockItem.getAttribute('data-id');
            const row = parseInt(cell.getAttribute('data-row')) - 1; // data-row is 1-indexed
            const col = parseInt(cell.getAttribute('data-col'));

            if (!window.executionValues[blockId]) window.executionValues[blockId] = {};
            const key = `${row}_${col}`;
            
            // Xử lý meta history nếu cần (Mô phỏng như thay đổi dữ liệu)
            if (!window.executionValues[blockId]._meta) window.executionValues[blockId]._meta = {};
            if (!window.executionValues[blockId]._meta[key]) window.executionValues[blockId]._meta[key] = { history_list: [], history_count: 0 };
            
            const user = '{{ session("user.fullName") ?? (session("user.username") ?? "Người dùng") }}';
            const time = new Date().toLocaleString('vi-VN');

            if (isNA) {
                // Đánh dấu N/A
                window.executionValues[blockId]._na_state = window.executionValues[blockId]._na_state || {};
                window.executionValues[blockId]._na_state[key] = true;
                window.executionValues[blockId]._na_state[`${key}_meta`] = { by: user, at: time, reason: reason };
            } else {
                // Hủy N/A
                window.executionValues[blockId][key] = '';
                if (window.executionValues[blockId]._na_state) {
                    window.executionValues[blockId]._na_state[key] = false;
                }
            }
        });
    }

</script>
