<script>
    let propertiesSidebarOpen = false;
    let documentProperties = [];
    let propertySaveDebounceTimers = {};

    function togglePropertiesSidebar() {
        propertiesSidebarOpen = !propertiesSidebarOpen;
        if (propertiesSidebarOpen) {
            // Close criteria sidebar if open
            if (typeof criteriaSidebarOpen !== 'undefined' && criteriaSidebarOpen) {
                closeCriteriaSidebar();
            }
            
            // Reset position to right-docked on open
            const sidebar = document.getElementById('propertiesSidebar');
            if (sidebar) {
                sidebar.style.right = '';
                sidebar.style.left = '';
                sidebar.style.top = '';
                sidebar.style.transition = '';
            }
            
            $('#propertiesSidebar').removeClass('d-none');
            loadProperties();
        } else {
            $('#propertiesSidebar').addClass('d-none');
        }
    }

    function closePropertiesSidebar() {
        propertiesSidebarOpen = false;
        $('#propertiesSidebar').addClass('d-none');
    }

    function loadProperties() {
        const listContainer = $('#propertiesSidebarList');
        listContainer.html(`
            <div class="text-center py-4 text-muted small">
                <div class="spinner-border spinner-border-sm text-info me-2"></div> Đang tải thuộc tính...
            </div>
        `);

        $.ajax({
            url: `/ebmr/templates/${currentTemplateId}/properties`,
            method: 'GET',
            success: function(res) {
                if (res.success) {
                    documentProperties = res.properties;
                    renderPropertiesList();
                } else {
                    listContainer.html('<div class="text-center text-danger py-3">Lỗi khi tải thuộc tính</div>');
                }
            },
            error: function() {
                listContainer.html('<div class="text-center text-danger py-3">Không thể kết nối đến máy chủ</div>');
            }
        });
    }

    function renderPropertiesList() {
        const listContainer = $('#propertiesSidebarList');
        if (documentProperties.length === 0) {
            listContainer.html('<div class="text-center text-muted py-4 small">Chưa có thuộc tính nào được tạo.</div>');
            return;
        }

        let html = '';
        documentProperties.forEach(prop => {
            const valPreview = prop.value ? (prop.value.length > 40 ? prop.value.substring(0, 37) + '...' : prop.value) : '<em>Trống</em>';
            
            // Escape names and values for safe inline onclick JS parameters
            const nameEscaped = prop.name.replace(/\\/g, '\\\\').replace(/'/g, "\\'").replace(/"/g, '\\"');
            const valueEscaped = (prop.value || '').replace(/\\/g, '\\\\').replace(/'/g, "\\'").replace(/"/g, '\\"');
            
            html += `
                <div class="card p-3 criteria-card shadow-sm border-0 mb-2">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="badge bg-info text-white px-2 py-1 fw-bold small">${prop.name}</span>
                        <div class="d-flex gap-1">
                            <button class="btn btn-xs btn-outline-primary" onclick="insertProperty('${nameEscaped}', '${valueEscaped}')" title="Chèn thuộc tính">
                                <i class="fas fa-plus"></i> Chèn
                            </button>
                            <button class="btn btn-xs btn-outline-secondary" onclick="editPropertyPrompt(${prop.id}, '${nameEscaped}', '${valueEscaped}')" title="Sửa giá trị">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-xs btn-outline-danger" onclick="deleteProperty(${prop.id}, '${nameEscaped}')" title="Xóa thuộc tính">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    </div>
                    <div class="small text-muted text-break">
                        <strong>Giá trị:</strong> ${valPreview}
                    </div>
                </div>
            `;
        });
        listContainer.html(html);
    }

    function addNewPropertyPrompt() {
        Swal.fire({
            title: 'Tạo thuộc tính mới',
            html: `
                <div class="text-left text-start mb-3">
                    <label class="small fw-bold mb-1 text-muted">Tên thuộc tính (Ví dụ: Abstract, Author, Version...)</label>
                    <input id="swal-input-name" class="form-control form-control-sm" placeholder="Nhập tên thuộc tính...">
                </div>
                <div class="text-left text-start">
                    <label class="small fw-bold mb-1 text-muted">Giá trị thuộc tính</label>
                    <textarea id="swal-input-value" class="form-control form-control-sm" rows="3" placeholder="Nhập giá trị thuộc tính..."></textarea>
                </div>
            `,
            focusConfirm: false,
            showCancelButton: true,
            confirmButtonText: 'Tạo',
            cancelButtonText: 'Hủy',
            confirmButtonColor: '#0f766e',
            preConfirm: () => {
                const name = document.getElementById('swal-input-name').value.trim();
                const value = document.getElementById('swal-input-value').value;
                if (!name) {
                    Swal.showValidationMessage('Tên thuộc tính không được để trống!');
                    return false;
                }
                if (name.includes('"')) {
                    Swal.showValidationMessage('Tên thuộc tính không được chứa dấu ngoặc kép (")!');
                    return false;
                }
                // Check for duplicates
                const exists = documentProperties.some(p => p.name.toLowerCase() === name.toLowerCase());
                if (exists) {
                    Swal.showValidationMessage('Tên thuộc tính này đã tồn tại!');
                    return false;
                }
                return { name, value };
            }
        }).then((result) => {
            if (result.value) {
                const { name, value } = result.value;
                $.ajax({
                    url: `/ebmr/templates/${currentTemplateId}/properties`,
                    method: 'POST',
                    data: {
                        name: name,
                        value: value,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(res) {
                        if (res.success) {
                            loadProperties();
                        }
                    }
                });
            }
        });
    }

    function editPropertyPrompt(id, currentName, currentValue) {
        Swal.fire({
            title: `Sửa thuộc tính`,
            html: `
                <div class="text-left text-start mb-3">
                    <label class="small fw-bold mb-1 text-muted">Tên thuộc tính</label>
                    <input id="swal-input-name" class="form-control form-control-sm" value="${currentName.replace(/"/g, '&quot;')}" placeholder="Nhập tên thuộc tính...">
                </div>
                <div class="text-left text-start">
                    <label class="small fw-bold mb-1 text-muted">Giá trị thuộc tính</label>
                    <textarea id="swal-input-value" class="form-control form-control-sm" rows="3" placeholder="Nhập giá trị thuộc tính...">${currentValue}</textarea>
                </div>
            `,
            focusConfirm: false,
            showCancelButton: true,
            confirmButtonText: 'Lưu',
            cancelButtonText: 'Hủy',
            confirmButtonColor: '#0f766e',
            preConfirm: () => {
                const name = document.getElementById('swal-input-name').value.trim();
                const value = document.getElementById('swal-input-value').value;
                if (!name) {
                    Swal.showValidationMessage('Tên thuộc tính không được để trống!');
                    return false;
                }
                if (name.includes('"')) {
                    Swal.showValidationMessage('Tên thuộc tính không được chứa dấu ngoặc kép (")!');
                    return false;
                }
                // Check for duplicates excluding current name
                const exists = documentProperties.some(p => p.id !== id && p.name.toLowerCase() === name.toLowerCase());
                if (exists) {
                    Swal.showValidationMessage('Tên thuộc tính này đã tồn tại!');
                    return false;
                }
                return { name, value };
            }
        }).then((result) => {
            if (result.value) {
                const { name, value } = result.value;
                $.ajax({
                    url: `/ebmr/templates/${currentTemplateId}/properties`,
                    method: 'POST',
                    data: {
                        prop_id: id,
                        name: name,
                        value: value,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(res) {
                        if (res.success) {
                            if (currentName !== name) {
                                renamePropertyBadges(currentName, name, value);
                            } else {
                                syncPropertyBadges(name, value);
                            }
                            loadProperties();
                        }
                    }
                });
            }
        });
    }

    function renamePropertyBadges(oldName, newName, newValue) {
        // 1. Update the badges in the DOM
        $('.ebmr-property-badge[data-property-name="' + oldName + '"]').each(function() {
            $(this).attr('data-property-name', newName);
            $(this).attr('title', 'Thuộc tính: ' + newName);
            $(this).text(newValue);
            
            const blockEl = $(this).closest('.block-item');
            if (blockEl.length > 0) {
                const blockId = blockEl.attr('data-id');
                const item = items.find(i => i.id === blockId);
                if (item) {
                    if (item.type === 'static-text') {
                        const displayEl = blockEl.find('.static-text-display');
                        if (displayEl.length > 0) {
                            item.content = displayEl.html();
                            item.dirty = true;
                        }
                    } else if (item.type === 'table') {
                        const td = $(this).closest('td');
                        if (td.length > 0) {
                            const r = parseInt(td.attr('data-row')) - 1;
                            const c = parseInt(td.attr('data-col'));
                            if (item.data[r] && item.data[r][c]) {
                                if (typeof item.data[r][c] === 'object') {
                                    item.data[r][c].content = td.html();
                                } else {
                                    item.data[r][c] = {
                                        content: td.html(),
                                        rs: 1,
                                        cs: 1,
                                        hidden: false
                                    };
                                }
                                item.dirty = true;
                            }
                        }
                    }
                }
            }
        });

        // 2. Sync unrendered/all blocks in local state array
        items.forEach(item => {
            if (item.type === 'static-text' && item.content && item.content.includes(`data-property-name="${oldName}"`)) {
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = item.content;
                $(tempDiv).find(`.ebmr-property-badge[data-property-name="${oldName}"]`).each(function() {
                    $(this).attr('data-property-name', newName);
                    $(this).attr('title', 'Thuộc tính: ' + newName);
                    $(this).text(newValue);
                });
                const newContent = tempDiv.innerHTML;
                if (item.content !== newContent) {
                    item.content = newContent;
                    item.dirty = true;
                }
            } else if (item.type === 'table' && item.data) {
                item.data.forEach((row, r) => {
                    row.forEach((cell, c) => {
                        const cellContent = (cell && typeof cell === 'object') ? cell.content : cell;
                        if (cellContent && cellContent.includes(`data-property-name="${oldName}"`)) {
                            const tempDiv = document.createElement('div');
                            tempDiv.innerHTML = cellContent;
                            $(tempDiv).find(`.ebmr-property-badge[data-property-name="${oldName}"]`).each(function() {
                                $(this).attr('data-property-name', newName);
                                $(this).attr('title', 'Thuộc tính: ' + newName);
                                $(this).text(newValue);
                            });
                            const newContent = tempDiv.innerHTML;
                            if (cellContent !== newContent) {
                                if (typeof cell === 'object') {
                                    cell.content = newContent;
                                } else {
                                    item.data[r][c] = {
                                        content: newContent,
                                        rs: 1,
                                        cs: 1,
                                        hidden: false
                                    };
                                }
                                item.dirty = true;
                            }
                        }
                    });
                });
            }
        });
    }

    function deleteProperty(id, name) {
        Swal.fire({
            title: 'Xóa thuộc tính?',
            text: `Bạn có chắc muốn xóa thuộc tính "${name}"? Các vị trí liên kết trong tài liệu sẽ không còn tự động cập nhật.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Xóa',
            cancelButtonText: 'Hủy'
        }).then((result) => {
            if (result.value) {
                $.ajax({
                    url: `/ebmr/templates/${currentTemplateId}/properties/${id}`,
                    method: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(res) {
                        if (res.success) {
                            Swal.fire('Đã xóa', 'Đã xóa thuộc tính thành công.', 'success');
                            loadProperties();
                        }
                    }
                });
            }
        });
    }

    function insertProperty(name, value) {
        if (savedTextSelection) {
            const currentSel = window.getSelection();
            currentSel.removeAllRanges();
            currentSel.addRange(savedTextSelection);
        }

        const currentSel = window.getSelection();
        if (currentSel.rangeCount > 0) {
            const range = currentSel.getRangeAt(0);
            
            let container = range.commonAncestorContainer;
            if (container.nodeType === 3) container = container.parentNode;
            
            const editableArea = $(container).closest('[contenteditable="true"]');
            if (editableArea.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Lỗi vị trí',
                    text: 'Vui lòng click chọn một ô trong bảng hoặc vùng văn bản trước khi chèn.'
                });
                return;
            }

            const span = document.createElement('span');
            span.contentEditable = "true";
            span.className = "ebmr-property-badge";
            span.setAttribute('data-property-name', name);
            span.setAttribute('title', 'Thuộc tính: ' + name);
            span.textContent = value || name;
            
            const zeroWidthSpace = document.createTextNode('\u200B');
            range.deleteContents();
            range.insertNode(zeroWidthSpace);
            range.insertNode(span);
            range.setStartAfter(zeroWidthSpace);
            range.collapse(true);
            currentSel.removeAllRanges();
            currentSel.addRange(range);
            
            // Trigger input event to update items state
            const ce = span.closest('[contenteditable="true"]');
            if (ce) ce.dispatchEvent(new Event('input', {
                bubbles: true
            }));
            
            syncPropertyBadges(name, value || name);
        } else {
            Swal.fire({
                icon: 'warning',
                title: 'Lỗi vị trí',
                text: 'Vui lòng chọn một vị trí trong tài liệu trước khi chèn.'
            });
        }
    }

    function syncPropertyBadges(name, value, excludeElement = null) {
        // 1. Update other badges in the DOM
        $('.ebmr-property-badge[data-property-name="' + name + '"]').each(function() {
            if (this !== excludeElement) {
                $(this).text(value);
                
                const blockEl = $(this).closest('.block-item');
                if (blockEl.length > 0) {
                    const blockId = blockEl.attr('data-id');
                    const item = items.find(i => i.id === blockId);
                    if (item) {
                        if (item.type === 'static-text') {
                            const displayEl = blockEl.find('.static-text-display');
                            if (displayEl.length > 0) {
                                item.content = displayEl.html();
                                item.dirty = true;
                            }
                        } else if (item.type === 'table') {
                            const td = $(this).closest('td');
                            if (td.length > 0) {
                                const r = parseInt(td.attr('data-row')) - 1;
                                const c = parseInt(td.attr('data-col'));
                                if (item.data[r] && item.data[r][c]) {
                                    if (typeof item.data[r][c] === 'object') {
                                        item.data[r][c].content = td.html();
                                    } else {
                                        item.data[r][c] = {
                                            content: td.html(),
                                            rs: 1,
                                            cs: 1,
                                            hidden: false
                                        };
                                    }
                                    item.dirty = true;
                                }
                            }
                        }
                    }
                }
            }
        });

        // 2. Sync unrendered/all blocks in local state array
        items.forEach(item => {
            if (item.type === 'static-text' && item.content && item.content.includes(`data-property-name="${name}"`)) {
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = item.content;
                $(tempDiv).find(`.ebmr-property-badge[data-property-name="${name}"]`).text(value);
                const newContent = tempDiv.innerHTML;
                if (item.content !== newContent) {
                    item.content = newContent;
                    item.dirty = true;
                }
            } else if (item.type === 'table' && item.data) {
                item.data.forEach((row, r) => {
                    row.forEach((cell, c) => {
                        const cellContent = (cell && typeof cell === 'object') ? cell.content : cell;
                        if (cellContent && cellContent.includes(`data-property-name="${name}"`)) {
                            const tempDiv = document.createElement('div');
                            tempDiv.innerHTML = cellContent;
                            $(tempDiv).find(`.ebmr-property-badge[data-property-name="${name}"]`).text(value);
                            const newContent = tempDiv.innerHTML;
                            if (cellContent !== newContent) {
                                if (typeof cell === 'object') {
                                    cell.content = newContent;
                                } else {
                                    item.data[r][c] = {
                                        content: newContent,
                                        rs: 1,
                                        cs: 1,
                                        hidden: false
                                    };
                                }
                                item.dirty = true;
                            }
                        }
                    });
                });
            }
        });
    }

    function savePropertyToDb(name, value) {
        if (propertySaveDebounceTimers[name]) {
            clearTimeout(propertySaveDebounceTimers[name]);
        }
        
        propertySaveDebounceTimers[name] = setTimeout(() => {
            const url = `/ebmr/templates/${currentTemplateId}/properties`;
            $.ajax({
                url: url,
                method: 'POST',
                data: {
                    name: name,
                    value: value,
                    _token: '{{ csrf_token() }}'
                },
                success: function(res) {
                    console.log(`Saved property ${name} to DB:`, res);
                },
                error: function(err) {
                    console.error(`Failed to save property ${name}:`, err);
                }
            });
        }, 1000);
    }

    // Capture inline editing on the property badges
    $(document).on('input', '.ebmr-property-badge', function(e) {
        const name = $(this).attr('data-property-name');
        const value = $(this).text();
        syncPropertyBadges(name, value, this);
        savePropertyToDb(name, value);
    });

    // Highlight toggling handlers
    function handlePropertyHighlightChange(isHighlight) {
        localStorage.setItem('ebmr_property_highlight', isHighlight);
        updatePropertyHighlightClass(isHighlight);
    }

    function updatePropertyHighlightClass(isHighlight) {
        const mainContent = $('#mainContent');
        if (isHighlight) {
            mainContent.addClass('show-property-highlight');
        } else {
            mainContent.removeClass('show-property-highlight');
        }
    }

    $(document).ready(function() {
        const savedHighlight = localStorage.getItem('ebmr_property_highlight');
        // Default to true if not set
        const isHighlight = savedHighlight === null ? true : (savedHighlight === 'true');
        
        const checkbox = $('#togglePropertyHighlight');
        if (checkbox.length) {
            checkbox.prop('checked', isHighlight);
        }
        
        updatePropertyHighlightClass(isHighlight);

        // Draggable Properties Sidebar implementation
        const sidebar = document.getElementById('propertiesSidebar');
        if (sidebar) {
            const header = sidebar.querySelector('.properties-sidebar-header');
            if (header) {
                header.style.cursor = 'move';
                let isDragging = false;
                let offset = { x: 0, y: 0 };

                header.addEventListener('mousedown', function(e) {
                    if (e.target.closest('button') || e.target.closest('.fa-times')) return;
                    isDragging = true;

                    const rect = sidebar.getBoundingClientRect();
                    offset.x = e.clientX - rect.left;
                    offset.y = e.clientY - rect.top;

                    header.style.cursor = 'grabbing';
                    e.preventDefault();
                });

                document.addEventListener('mousemove', function(e) {
                    if (!isDragging) return;

                    let newLeft = e.clientX - offset.x;
                    let newTop = e.clientY - offset.y;

                    // Constrain within viewport boundaries
                    const viewportWidth = window.innerWidth;
                    const viewportHeight = window.innerHeight;
                    const sidebarWidth = sidebar.offsetWidth;
                    const sidebarHeight = sidebar.offsetHeight;

                    if (newLeft < 0) newLeft = 0;
                    if (newLeft > viewportWidth - sidebarWidth) newLeft = viewportWidth - sidebarWidth;
                    if (newTop < 0) newTop = 0;
                    if (newTop > viewportHeight - sidebarHeight) newTop = viewportHeight - sidebarHeight;

                    sidebar.style.right = 'auto';
                    sidebar.style.left = newLeft + 'px';
                    sidebar.style.top = newTop + 'px';
                    sidebar.style.transition = 'none';
                });

                document.addEventListener('mouseup', function() {
                    if (isDragging) {
                        isDragging = false;
                        header.style.cursor = 'move';
                    }
                });
            }
        }
    });
</script>
