<script>
    /**
     * Handles selection and batch editing of multiple dynamic fields
     */
    function selectMultipleFields(fieldIds) {
        if (!fieldIds || fieldIds.length === 0) return;
        
        selectedFieldIds = fieldIds;
        selectedFieldId = fieldIds[0]; // Primary field for initial display
        selectedId = null;
        
        // Remove active class from blocks
        document.querySelectorAll('.block-item').forEach(el => el.classList.remove('active'));
        
        const firstField = fieldsConfig[selectedFieldId];
        if (!firstField) return;

        const panel = document.getElementById('property-panel');
        const body = document.getElementById('prop-body');
        
        if (panel) {
            panel.classList.remove('d-none');
            if (isSidebarMinimized) toggleSidebar(false);
        }

        let typeHtml = `
            <div class="mb-3">
                <div class="alert alert-info py-2 mb-2 small">
                    <i class="fas fa-layer-group me-1"></i> Đang chỉnh sửa <strong>${fieldIds.length} biến số</strong> cùng lúc.
                </div>
                <div class="text-muted" style="font-size: 0.65rem; max-height: 40px; overflow-y: auto;">
                    Mã: ${fieldIds.join(', ')}
                </div>
            </div>
            
            <div class="mb-3">
                <label class="small fw-bold text-muted text-uppercase mb-2">Kiểu dữ liệu chung</label>
                <select class="form-select form-select-sm" onchange="batchSyncFieldConfig('type', this.value)">
                    <option value="" disabled selected>-- Chọn kiểu để áp dụng cho tất cả --</option>
                    <option value="text">✒️ Văn bản (Text)</option>
                    <option value="number">🔢 Số (Number)</option>
                    <option value="date">📅 Ngày tháng (Date)</option>
                    <option value="select">🔘 Khóa chọn (Dropdown)</option>
                    <option value="signature">✍️ Chữ ký (Signature)</option>
                    <option value="checkbox">☑️ Hộp kiểm tra (Checkbox)</option>
                </select>
                <div class="form-text small" style="font-size: 0.7rem;">Thay đổi kiểu sẽ áp dụng cho tất cả biến được chọn.</div>
            </div>

            <div class="mb-3">
                <div class="form-check form-switch ps-4 pt-1">
                    <input class="form-check-input ms-n4" type="checkbox" id="batchFieldRequired" onchange="batchSyncFieldConfig('validation.required', this.checked)">
                    <label class="form-check-label small fw-bold" for="batchFieldRequired">Tất cả bắt buộc điền</label>
                </div>
            </div>

            <hr class="my-3">
            
            <div id="batch-specific-options"></div>

            <div class="mt-4 text-center">
                <button class="btn btn-sm btn-outline-danger w-100" onclick="batchDeleteFields()"><i class="fas fa-trash-alt me-1"></i> Xóa tất cả ${fieldIds.length} biến</button>
            </div>
        `;

        body.innerHTML = typeHtml;
    }

    function batchSyncFieldConfig(path, value) {
        if (!selectedFieldIds || selectedFieldIds.length === 0) return;
        
        selectedFieldIds.forEach(id => {
            syncFieldConfig(id, path, value);
        });
        
        // Re-render the batch panel to reflect changes if necessary
        // Or update specific parts
        if (path === 'type') {
            updateBatchSpecificOptions(value);
            renderBlocks(); // Visual refresh
        }
    }

    function updateBatchSpecificOptions(type) {
        const container = document.getElementById('batch-specific-options');
        if (!container) return;
        
        let html = '';
        if (type === 'number') {
            html = `
                <div class="card bg-light border-0 shadow-none mb-3">
                    <div class="card-body p-3">
                        <label class="small fw-bold mb-2"><i class="fas fa-balance-scale me-1"></i> Giới hạn giá trị chung</label>
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="small text-muted" style="font-size: 0.75em;">Tối thiểu (Min)</label>
                                <input type="number" class="form-control form-control-sm" oninput="batchSyncFieldConfig('validation.min', this.value)">
                            </div>
                            <div class="col-6">
                                <label class="small text-muted" style="font-size: 0.75em;">Tối đa (Max)</label>
                                <input type="number" class="form-control form-control-sm" oninput="batchSyncFieldConfig('validation.max', this.value)">
                            </div>
                        </div>
                    </div>
                </div>
            `;
        } else if (type === 'select') {
            html = `
                <div class="mb-3">
                    <label class="small fw-bold text-muted text-uppercase mb-2">Danh sách lựa chọn chung</label>
                    <textarea class="form-control form-control-sm" rows="3" placeholder="Ví dụ: Đạt, Tốt, Không đạt" oninput="batchSyncFieldConfig('options', this.value)"></textarea>
                </div>
            `;
        }
        container.innerHTML = html;
    }

    function batchDeleteFields() {
        Swal.fire({
            title: 'Xác nhận xóa?',
            text: `Bạn có chắc chắn muốn xóa ${selectedFieldIds.length} biến số đã chọn?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Xóa ngay',
            cancelButtonText: 'Hủy'
        }).then((result) => {
            if (result.isConfirmed) {
                const ids = [...selectedFieldIds];
                
                // Update data model to clear content for all selected field IDs
                items.forEach(item => {
                    if (item.type === 'table' && item.data) {
                        item.data.forEach((row, r) => {
                            row.forEach((cell, c) => {
                                if (cell.content) {
                                    ids.forEach(id => {
                                        if (cell.content.includes(`data-field-id="${id}"`)) {
                                            cell.content = '';
                                        }
                                    });
                                }
                            });
                        });
                    }
                });

                // Clear from config
                ids.forEach(id => {
                    delete fieldsConfig[id];
                });

                selectedFieldIds = [];
                selectedFieldId = null;
                renderBlocks();
                document.getElementById('property-panel').classList.add('d-none');
                saveStateDebounced();
            }
        });
    }
</script>
