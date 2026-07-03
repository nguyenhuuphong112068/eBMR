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

    $('#componentSidebarSearch').on('keyup', function() {
        const value = $(this).val().toLowerCase();
        $('.component-drag-item').filter(function() {
            $(this).toggle($(this).find('.template-name').text().toLowerCase().indexOf(value) > -1)
        });
    });

    let componentSidebarOpen = false;

    function toggleComponentSidebar(keepMargin = false) {
        const sidebar = document.getElementById('componentsSidebar');
        if (!sidebar) return;
        
        componentSidebarOpen = !componentSidebarOpen;
        if (componentSidebarOpen) {
            sidebar.classList.remove('d-none');
            document.getElementById('mainContent').style.setProperty('margin-left', '250px', 'important');
            // Give it a tiny delay to ensure d-none is removed before triggering CSS transition
            setTimeout(() => sidebar.classList.add('show'), 10);
            
            if (typeof equipmentSidebarOpen !== 'undefined' && equipmentSidebarOpen) {
                if (typeof toggleEquipmentSidebar === 'function') toggleEquipmentSidebar(true);
            }
            
            loadComponentsSidebar();
        } else {
            sidebar.classList.remove('show');
            if (!keepMargin) {
                document.getElementById('mainContent').style.removeProperty('margin-left');
            }
            setTimeout(() => sidebar.classList.add('d-none'), 300); // wait for transition
        }
    }

    function loadComponentsSidebar() {
        const listContainer = $('#componentsSidebarList');
        listContainer.html(`
            <div class="text-center py-4 text-muted small">
                <div class="spinner-border spinner-border-sm text-info me-2"></div> Đang tải...
            </div>
        `);

        fetch('/ebmr/get-templates')
            .then(res => res.json())
            .then(data => {
                let html = '';
                const coTemplates = data.filter(t => t.type === 'CO');
                
                if (coTemplates.length === 0) {
                    html = `
                        <div class="text-center py-4 text-muted small">
                            <i class="fas fa-info-circle me-2"></i> Không có Thành phần nào.
                        </div>
                    `;
                } else {
                    coTemplates.forEach((t, index) => {
                        const date = new Date(t.updated_at).toLocaleString('vi-VN');
                        html += `
                            <div class="card p-2 shadow-sm border-0 mb-2 component-drag-item" 
                                 draggable="true" 
                                 ondragstart="onComponentDragStart(event, ${t.id}, '${t.name.replace(/'/g, "\\'")}')"
                                 title="Kéo thả thẻ này vào dải phân cách giữa các khối trên văn bản">
                                <div class="d-flex align-items-center">
                                    <div class="bg-light text-primary rounded px-2 py-1 me-2" style="font-size: 0.8em;">
                                        <i class="fas fa-grip-vertical text-muted"></i>
                                    </div>
                                    <div class="flex-grow-1 overflow-hidden">
                                        <div class="fw-bold text-dark template-name text-truncate" style="font-size: 0.85em;">${t.name}</div>
                                        <div class="text-muted" style="font-size: 0.65em;">Cập nhật: ${date}</div>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                }
                listContainer.html(html);
            })
            .catch(err => {
                console.error(err);
                listContainer.html(`
                    <div class="text-center py-4 text-danger small">
                        <i class="fas fa-exclamation-triangle me-2"></i> Lỗi tải dữ liệu!
                    </div>
                `);
            });
    }

    function onComponentDragStart(event, templateId, templateName) {
        event.dataTransfer.setData('componentId', templateId);
        event.dataTransfer.setData('componentName', templateName);
        event.dataTransfer.effectAllowed = 'copy';
        // Add a visual class to indicate dragging is active
        document.body.classList.add('component-dragging');
    }
    
    document.addEventListener('dragend', function() {
        document.body.classList.remove('component-dragging');
        // Clear drag over styles
        document.querySelectorAll('.insert-divider').forEach(el => el.classList.remove('drag-over-active'));
        
        // Stop auto scroll if active
        if (window.dragScrollInterval) {
            clearInterval(window.dragScrollInterval);
            window.dragScrollInterval = null;
        }
    });

    // Handle auto scroll when dragging near the edge of the window
    document.addEventListener('dragover', function(e) {
        if (!document.body.classList.contains('component-dragging')) return;
        
        const threshold = 80; // pixels from edge to trigger scroll
        const scrollSpeed = 15; // pixels to scroll per interval
        const windowHeight = window.innerHeight;
        
        // Clear previous interval if any
        if (window.dragScrollInterval) {
            clearInterval(window.dragScrollInterval);
            window.dragScrollInterval = null;
        }

        // Determine if mouse is near top or bottom
        if (e.clientY < threshold) {
            // Scroll UP
            window.dragScrollInterval = setInterval(() => {
                window.scrollBy(0, -scrollSpeed);
            }, 20);
        } else if (e.clientY > windowHeight - threshold) {
            // Scroll DOWN
            window.dragScrollInterval = setInterval(() => {
                window.scrollBy(0, scrollSpeed);
            }, 20);
        }
    });

    // Import logic (Deep Copy)
    function importMasterForm(templateId, templateName, insertIndex = -1) {
        if (!confirm(`Bạn có chắc chắn muốn chèn dữ liệu từ biểu mẫu/thành phần "${templateName}" vào công đoạn hiện tại không?`)) {
            return;
        }

        // When called from drag-drop, there's no button context
        const btn = (typeof event !== 'undefined' && event && event.currentTarget && event.currentTarget.tagName === 'BUTTON')
            ? event.currentTarget
            : null;

        let originalContent = '';
        if (btn) {
            originalContent = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang tải...';
            btn.disabled = true;
        } else {
            Swal.fire({
                title: 'Đang tải dữ liệu...',
                text: 'Vui lòng chờ trong giây lát',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
        }

        fetch(`/ebmr/templates/${templateId}/blocks`)
            .then(res => {
                if (!res.ok) throw new Error('HTTP ' + res.status);
                return res.json();
            })
            .then(data => {
                if (btn) {
                    btn.innerHTML = originalContent;
                    btn.disabled = false;
                }

                if (data && data.blocks) {
                    const importedBlocks = data.blocks;
                    const importedConfig = data.fields || {};
                    // Determine the correct section_id based on the insertion point
                    let calculatedInsertIndex = insertIndex >= 0 ? insertIndex : (items.length > 0 ? items.length : 0);
                    let targetSectionId = window.resolveTargetSectionId(calculatedInsertIndex, 'section_0');

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
                        // Skip section blocks from CO/MF, we just want content
                        if (b.type === 'section') return;

                        let newBlock = JSON.parse(JSON.stringify(b)); // Deep clone
                        let newBlockId = blockMap[b.id];
                        
                        newBlock.id = newBlockId;
                        newBlock.section_id = targetSectionId;
                        delete newBlock.db_id; // Remove DB id so it's treated as new

                        // Replace field IDs in content (for static-text or table cells)
                        if (newBlock.content) {
                            for (let oldKey in fieldMap) {
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
                                        delete cell.db_id;
                                        delete cell.content_db_id;
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

                        // Add to global items at the correct position
                        if (insertIndex >= 0) {
                            items.splice(insertIndex + importedCount, 0, newBlock);
                        } else {
                            items.push(newBlock);
                        }
                        importedCount++;
                    });

                    // 4. Add new variables to fieldsConfig
                    for (let oldKey in importedConfig) {
                        let newKey = fieldMap[oldKey];
                        let config = JSON.parse(JSON.stringify(importedConfig[oldKey]));
                        
                        config.id = newKey;
                        config.section_id = targetSectionId;
                        // Map block_id if it exists
                        if (config.block_id && blockMap[config.block_id]) {
                            config.block_id = blockMap[config.block_id];
                        }
                        
                        fieldsConfig[newKey] = config;
                    }

                    if (importedCount > 0) {
                        saveState();
                        renderBlocks();
                        $('#masterFormModal').modal('hide');
                        if (typeof componentSidebarOpen !== 'undefined' && componentSidebarOpen) toggleComponentSidebar();
                        Swal.close();
                        toastr.success(`Đã chèn thành công ${importedCount} khối dữ liệu từ "${templateName}"!`);
                    } else {
                        Swal.close();
                        toastr.warning('Mẫu này không có nội dung nào để chèn (chỉ có section/header).');
                    }
                } else {
                    Swal.close();
                    toastr.error('Không tìm thấy nội dung trong biểu mẫu này.');
                }
            })
            .catch(err => {
                console.error('importMasterForm error:', err);
                if (btn) {
                    btn.innerHTML = originalContent;
                    btn.disabled = false;
                } else {
                    Swal.close();
                }
                toastr.error('Lỗi kết nối khi tải dữ liệu: ' + err.message);
            });
    }
</script>
