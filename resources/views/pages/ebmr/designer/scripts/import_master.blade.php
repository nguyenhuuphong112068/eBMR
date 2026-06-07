<script>
    // UUID generator helper
    function generateImportUUID() {
        return Date.now() + Math.random().toString(36).substr(2, 5);
    }

    // Open Modal and Load Templates
    function openMasterFormModal() {
        $('#masterFormModal').modal('show');
        $('#masterFormListContainer').html(`
            <tr>
                <td colspan="4" class="text-center py-4 text-muted">
                    <i class="fas fa-spinner fa-spin me-2"></i> Đang tải dữ liệu biểu mẫu gốc...
                </td>
            </tr>
        `);

        // Fetch templates
        fetch('/ebmr/get-templates')
            .then(res => res.json())
            .then(data => {
                let html = '';
                // Filter only Master Forms (type = MF)
                const mfTemplates = data.filter(t => t.type === 'MF');
                
                if (mfTemplates.length === 0) {
                    html = `
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted">
                                <i class="fas fa-info-circle me-2"></i> Không có Biểu mẫu gốc nào trong hệ thống.
                            </td>
                        </tr>
                    `;
                } else {
                    mfTemplates.forEach((t, index) => {
                        const date = new Date(t.updated_at).toLocaleString('vi-VN');
                        html += `
                            <tr class="master-form-row">
                                <td class="text-center align-middle">${index + 1}</td>
                                <td class="align-middle fw-bold text-primary template-name">${t.name}</td>
                                <td class="align-middle text-muted small">${date}</td>
                                <td class="text-center align-middle">
                                    <button class="btn btn-sm btn-success rounded-pill px-3 fw-bold" onclick="importMasterForm(${t.id}, '${t.name.replace(/'/g, "\\'")}')">
                                        <i class="fas fa-download me-1"></i> Nhập
                                    </button>
                                </td>
                            </tr>
                        `;
                    });
                }
                $('#masterFormListContainer').html(html);
            })
            .catch(err => {
                console.error(err);
                $('#masterFormListContainer').html(`
                    <tr>
                        <td colspan="4" class="text-center py-4 text-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i> Lỗi khi tải dữ liệu!
                        </td>
                    </tr>
                `);
            });
    }

    // Search functionality inside modal
    $('#masterFormSearch').on('keyup', function() {
        const value = $(this).val().toLowerCase();
        $('.master-form-row').filter(function() {
            $(this).toggle($(this).find('.template-name').text().toLowerCase().indexOf(value) > -1)
        });
    });

    // Import logic (Deep Copy)
    function importMasterForm(templateId, templateName) {
        if (!confirm(`Bạn có chắc chắn muốn nhập dữ liệu từ biểu mẫu "${templateName}" vào công đoạn hiện tại không?`)) {
            return;
        }

        const btn = event.currentTarget;
        const originalContent = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang tải...';
        btn.disabled = true;

        fetch(`/ebmr/templates/${templateId}/blocks`)
            .then(res => res.json())
            .then(data => {
                btn.innerHTML = originalContent;
                btn.disabled = false;

                if (data.success && data.blocks) {
                    const importedBlocks = data.blocks;
                    const importedConfig = data.fieldsConfig || {};
                    const targetSectionId = window.activeSectionId || (window.items.find(i => i.type === 'section')?.section_id) || 'section_0';

                    // 1. Map old field IDs (variables) to new UUIDs to prevent collisions
                    let fieldMap = {};
                    for (let key in importedConfig) {
                        fieldMap[key] = 'field_' + generateImportUUID();
                    }

                    // 2. Map old block IDs to new block IDs
                    let blockMap = {};
                    importedBlocks.forEach(b => {
                        blockMap[b.id] = 'blk_' + generateImportUUID();
                    });

                    // 3. Process blocks
                    let importedCount = 0;
                    importedBlocks.forEach(b => {
                        // Skip if block is header or section definition, unless it's a generic section
                        // Usually MF has 1 section. We will override it with targetSectionId.
                        if (b.type === 'section') return; // Skip section blocks from MF, we just want content

                        let newBlock = JSON.parse(JSON.stringify(b)); // Deep clone
                        let newBlockId = blockMap[b.id];
                        
                        newBlock.id = newBlockId;
                        newBlock.section_id = targetSectionId;
                        delete newBlock.db_id; // Remove DB id so it's treated as new

                        // Replace field IDs in content (for static-text or table cells)
                        if (newBlock.content) {
                            for (let oldKey in fieldMap) {
                                // Replace data-field-id="oldKey" and @{{oldKey}}
                                let regex = new RegExp(oldKey, 'g');
                                newBlock.content = newBlock.content.replace(regex, fieldMap[oldKey]);
                            }
                        }

                        // Replace field IDs in table data
                        if (newBlock.type === 'table' && newBlock.data) {
                            newBlock.data.forEach(row => {
                                row.forEach(cell => {
                                    if (cell && typeof cell === 'object') {
                                        if (cell.content) {
                                            for (let oldKey in fieldMap) {
                                                let regex = new RegExp(oldKey, 'g');
                                                cell.content = cell.content.replace(regex, fieldMap[oldKey]);
                                            }
                                        }
                                        // Generate new cell ID to be safe
                                        cell.id = 'cell_' + generateImportUUID();
                                    }
                                });
                            });
                        }

                        // Generate new table component IDs
                        if (newBlock.type === 'table') {
                            if (newBlock.rows_ids) {
                                newBlock.rows_ids = newBlock.rows_ids.map(() => 'row_' + generateImportUUID());
                            }
                            if (newBlock.cols_ids) {
                                newBlock.cols_ids = newBlock.cols_ids.map(() => 'col_' + generateImportUUID());
                            }
                        }

                        // Add to global items
                        window.items.push(newBlock);
                        importedCount++;
                    });

                    // 4. Add new variables to fieldsConfig
                    for (let oldKey in importedConfig) {
                        let newKey = fieldMap[oldKey];
                        let config = JSON.parse(JSON.stringify(importedConfig[oldKey]));
                        
                        config.id = newKey;
                        config.section_id = targetSectionId;
                        // Map block_id if it exists, otherwise leave null
                        if (config.block_id && blockMap[config.block_id]) {
                            config.block_id = blockMap[config.block_id];
                        }
                        
                        window.fieldsConfig[newKey] = config;
                    }

                    if (importedCount > 0) {
                        // Save state, re-render, and close modal
                        saveState();
                        renderBlocks();
                        updateVariableSummary();
                        $('#masterFormModal').modal('hide');
                        toastr.success(`Đã nhập thành công ${importedCount} khối dữ liệu từ Biểu mẫu gốc!`);
                    } else {
                        toastr.warning('Biểu mẫu gốc này không có nội dung nào để nhập.');
                    }
                } else {
                    toastr.error('Lỗi khi tải dữ liệu cấu trúc của biểu mẫu.');
                }
            })
            .catch(err => {
                console.error(err);
                btn.innerHTML = originalContent;
                btn.disabled = false;
                toastr.error('Lỗi kết nối khi tải biểu mẫu.');
            });
    }
</script>
