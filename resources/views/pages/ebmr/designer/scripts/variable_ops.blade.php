<script>
    // Dynamic Fields Data Handling
    function insertDynamicField(defaultType = 'text') {
        const selectedCells = document.querySelectorAll('.selected-cell');

        let useBatch = false;
        if (selectedCells.length > 1) {
            useBatch = true;
        } else if (selectedCells.length === 1) {
            let hasCursorInCell = false;
            const selForCheck = savedTextSelection ? [savedTextSelection] : (window.getSelection().rangeCount > 0 ? [window.getSelection().getRangeAt(0)] : []);
            if (selForCheck.length > 0) {
                let node = selForCheck[0].commonAncestorContainer;
                if (node.nodeType === 3) node = node.parentNode;
                if (node.closest && node.closest('td, th') === selectedCells[0]) {
                    hasCursorInCell = true;
                }
            }
            if (!hasCursorInCell) {
                useBatch = true;
            }
        }

        if (useBatch) {
            // MULTI-CELL BATCH CONVERSION
            saveState();
            selectedCells.forEach(td => {
                const block = td.closest('.block-item');
                if (!block) return;

                const blockId = block.getAttribute('data-id');
                const item = items.find(i => i.id === blockId);
                if (!item || item.type !== 'table') return;

                const rStr = td.dataset.row;
                const cStr = td.dataset.col;
                if (rStr === undefined || cStr === undefined) return;

                const r = parseInt(rStr) - 1; // 1-indexed in DOM for non-header
                const c = parseInt(cStr);

                const fieldId = 'field_' + Math.floor(Math.random() * 1000000);
                const dynamicName = `${blockId}_r${r}_c${c}`;

                let typeLabel = 'Dữ liệu';
                if (defaultType === 'text') typeLabel = 'Văn bản';
                else if (defaultType === 'number') typeLabel = 'Số';
                else if (defaultType === 'date') typeLabel = 'Thời Gian';
                else if (defaultType === 'signature') typeLabel = 'Chữ ký';
                else if (defaultType === 'checkbox') typeLabel = 'Tick';
                else if (defaultType === 'select') typeLabel = 'Lựa chọn';
                else if (defaultType === 'formula') typeLabel = 'Công thức';

                const defaultLabel = 'Nhập ' + typeLabel;

                // Register in fieldsConfig
                fieldsConfig[fieldId] = {
                    id: fieldId,
                    name: dynamicName,
                    label: defaultLabel,
                    type: defaultType,
                    validation: {
                        required: false,
                        min: null,
                        max: null,
                        decimal_places: null
                    },
                    options: [],
                    instruction: '',
                    block_id: blockId,
                    section_id: item.section_id || null
                };

                // Create the badge HTML
                const badgeHtml =
                    `<span contenteditable="false" class="ebmr-field-badge" data-field-id="${fieldId}" onclick="selectField(event, '${fieldId}')"></span>\u200B`;

                // Update item data
                if (!item.data[r][c] || typeof item.data[r][c] !== 'object') {
                    item.data[r][c] = {
                        content: badgeHtml,
                        rs: 1,
                        cs: 1,
                        hidden: false
                    };
                } else {
                    if (item.data[r][c].content && item.data[r][c].content.trim() !== '') {
                        item.data[r][c].content += badgeHtml;
                    } else {
                        item.data[r][c].content = badgeHtml;
                    }
                }
                item.dirty = true;
            });

            renderBlocks();
            return;
        }

        // SINGLE CURSOR INSERTION (Fallback)
        const fieldId = 'field_' + Math.floor(Math.random() * 1000000);
        let dynamicName = 'var_' + fieldId;

        const sel = window.getSelection();
        if (sel && sel.rangeCount > 0) {
            let node = sel.getRangeAt(0).startContainer;
            if (node.nodeType === 3) node = node.parentNode;
            const td = node.closest('td, th');
            const block = node.closest('.block-item');
            if (block) {
                const blockId = block.getAttribute('data-id');
                if (td) {
                    const r = td.getAttribute('data-row');
                    const c = td.getAttribute('data-col');
                    dynamicName = `${blockId}_r${r}_c${c}`;
                } else {
                    dynamicName = `${blockId}_text`;
                }
            }
        }

        let typeLabel = 'Dữ liệu';
        if (defaultType === 'text') typeLabel = 'Văn bản';
        else if (defaultType === 'number') typeLabel = 'Số';
        else if (defaultType === 'date') typeLabel = 'Thời Gian';
        else if (defaultType === 'signature') typeLabel = 'Chữ ký';
        else if (defaultType === 'checkbox') typeLabel = 'Tick';
        else if (defaultType === 'select') typeLabel = 'Lựa chọn';
        else if (defaultType === 'formula') typeLabel = 'Công thức';

        const defaultLabel = 'Nhập ' + typeLabel;

        // Xác định Block ID và Section ID chính xác từ vị trí con trỏ
        let detectedBlockId = selectedId;
        let detectedSectionId = window.activeSectionId;

        const selection = window.getSelection();
        if (selection.rangeCount > 0) {
            const container = selection.getRangeAt(0).commonAncestorContainer;
            const blockEl = (container.nodeType === 1 ? container : container.parentElement).closest('.block-item');
            if (blockEl) {
                detectedBlockId = blockEl.getAttribute('data-id');
                // Tìm section_id từ wrapper nếu có
                const sectionEl = blockEl.closest('.section-group-wrapper');
                if (sectionEl) {
                    detectedSectionId = sectionEl.getAttribute('data-section-id');
                }
            }
        }

        fieldsConfig[fieldId] = {
            id: fieldId,
            name: dynamicName,
            label: defaultLabel,
            type: defaultType,
            validation: {
                required: false,
                min: null,
                max: null,
                decimal_places: null
            },
            options: [],
            section_id: detectedSectionId,
            block_id: detectedBlockId,
            instruction: ''
        };

        if (savedTextSelection) {
            const currentSel = window.getSelection();
            currentSel.removeAllRanges();
            currentSel.addRange(savedTextSelection);
        }

        const currentSel = window.getSelection();
        if (currentSel.rangeCount > 0) {
            const range = currentSel.getRangeAt(0);
            const span = document.createElement('span');
            span.contentEditable = "false";
            span.className = "ebmr-field-badge";
            span.setAttribute('data-field-id', fieldId);
            span.setAttribute('onclick', `selectField(event, '${fieldId}')`);
            const zeroWidthSpace = document.createTextNode('\u200B');
            range.deleteContents();
            range.insertNode(zeroWidthSpace);
            range.insertNode(span);
            range.setStartAfter(zeroWidthSpace);
            range.collapse(true);
            currentSel.removeAllRanges();
            currentSel.addRange(range);
            const ce = span.closest('[contenteditable="true"]');
            if (ce) ce.dispatchEvent(new Event('input', {
                bubbles: true
            }));
            if (typeof syncBlockContent === 'function') {
                syncBlockContent(span);
            }
        } else {
            const html =
                `<span contenteditable="false" class="ebmr-field-badge" data-field-id="${fieldId}" onclick="selectField(event, '${fieldId}')"></span>\u200B`;
            document.execCommand('insertHTML', false, html);
            
            // Try to sync content if possible
            const sel = window.getSelection();
            if (sel && sel.rangeCount > 0) {
                const node = sel.getRangeAt(0).startContainer;
                if (typeof syncBlockContent === 'function') {
                    syncBlockContent(node.nodeType === 3 ? node.parentNode : node);
                }
            }
        }

        saveStateDebounced();
        renderBlocks();
        selectField(null, fieldId);
    }
    /**
     * Mở modal hiển thị bảng tổng hợp toàn bộ các biến số đã được cài đặt trong tài liệu.
     * Giúp người dùng kiểm tra nhanh các thông số như ID, Nhãn, Loại, CPP/CMA...
     */
    window.openVariableSummaryModal = function() {
        const body = document.getElementById('variableSummaryTableBody');
        if (!body) return;

        body.innerHTML = '';
        let index = 1;

        // Sắp xếp các biến theo tên hoặc ID để dễ theo dõi
        const sortedFields = Object.values(fieldsConfig).sort((a, b) => (a.name || '').localeCompare(b.name || ''));

        if (sortedFields.length === 0) {
            body.innerHTML =
                '<tr><td colspan="7" class="text-center py-4 text-muted">Chưa có biến số nào được cài đặt.</td></tr>';
        }

        sortedFields.forEach(field => {
            const tr = document.createElement('tr');

            // Tìm tên thông số quan trọng (CPP/CMA)
            const importantVar = (window.importantVars || []).find(v => v.id == field.important_var_id);
            const impText = importantVar ?
                `<span class="badge bg-warning text-dark">${importantVar.name}</span>` :
                '<span class="text-muted">-</span>';

            // Định dạng chi tiết cấu hình tùy theo loại biến
            let details = '';
            if (field.type === 'number') {
                details =
                    `Min: ${field.validation.min || '-'} | Max: ${field.validation.max || '-'} | Dec: ${field.validation.decimal_places || '0'}`;
            } else if (field.type === 'formula') {
                details =
                    `<code class="small text-primary">${field.formula || ''}</code> (Round: ${field.validation.decimal_places || '2'})`;
            } else if (field.type === 'select') {
                const dsType = (field.dataSource && field.dataSource.type) || 'manual';
                details = dsType === 'database' ?
                    `<i class="fas fa-database me-1"></i> DB: ${field.dataSource.table}` :
                    `<i class="fas fa-list me-1"></i> Manual: ${Array.isArray(field.options) ? field.options.length : 0} items`;
            } else {
                details = field.validation && field.validation.required ?
                    '<span class="text-danger small"><i class="fas fa-asterisk me-1"></i>Bắt buộc</span>' :
                    '<span class="text-muted small">Tùy chọn</span>';
            }

            tr.innerHTML = `
                <td class="text-center py-2">${index++}</td>
                <td class="font-monospace small py-2">${field.name}</td>
                <td class="fw-bold py-2">${field.label || '-'}</td>
                <td class="py-2"><span class="badge bg-light text-dark border" style="font-size: 0.7rem;">${field.type.toUpperCase()}</span></td>
                <td class="text-center py-2">${impText}</td>
                <td class="small py-2">${details}</td>
                <td class="text-center py-2">
                    <button class="btn btn-xs btn-outline-primary" title="Đi tới vị trí" onclick="$('#variableSummaryModal').modal('hide'); selectField(null, '${field.id}')">
                        <i class="fas fa-search-location"></i>
                    </button>
                </td>
            `;
            body.appendChild(tr);
        });

        $('#variableSummaryModal').modal('show');
    };

    /**
     * ==============================================================
     * CRITERIA DATA-BINDING – Drag & Drop Right Sidebar Operations
     * ==============================================================
     */
    let allCriteriaData = [];
    let allStagesMap = {};

    window.toggleCriteriaSidebar = function() {
        const sidebar = document.getElementById('criteriaSidebar');
        if (!sidebar) return;

        if (sidebar.classList.contains('d-none')) {
            sidebar.classList.remove('d-none');
            loadCriteriaSidebarData();
        } else {
            closeCriteriaSidebar();
        }
    };

    window.closeCriteriaSidebar = function() {
        const sidebar = document.getElementById('criteriaSidebar');
        if (sidebar) {
            sidebar.classList.add('d-none');
        }
    };

    function loadCriteriaSidebarData() {
        const currentTemplateId = window.templateId || '{{ $template->id ?? 0 }}';
        const listContainer = document.getElementById('criteriaSidebarList');
        if (!listContainer) return;

        if (!currentTemplateId || currentTemplateId === '0' || currentTemplateId === '') {
            listContainer.innerHTML = '<div class="text-center text-danger py-4 small fw-bold">Không tìm thấy mã hồ sơ hiện tại. Vui lòng lưu hồ sơ trước khi liên kết tiêu chuẩn!</div>';
            return;
        }

        listContainer.innerHTML = '<div class="text-center py-4 text-muted small"><div class="spinner-border spinner-border-sm text-info me-2"></div> Đang tải tiêu chuẩn...</div>';

        $.ajax({
            url: '/ebmr/templates/' + currentTemplateId + '/testing-data',
            method: 'GET',
            success: function(res) {
                if (res.success && res.testing && res.testing.length > 0) {
                    allCriteriaData = res.testing;
                    allStagesMap = {};

                    // Build stages map and dropdown filter options
                    const filterDropdown = document.getElementById('criteriaSidebarStageFilter');
                    if (filterDropdown) {
                        filterDropdown.innerHTML = '<option value="">-- Tất cả công đoạn --</option>';
                    }

                    if (res.sections) {
                        res.sections.forEach(s => {
                            allStagesMap[s.id] = s.label;
                            if (filterDropdown) {
                                const opt = document.createElement('option');
                                opt.value = s.id;
                                opt.textContent = s.label;
                                filterDropdown.appendChild(opt);
                            }
                        });
                    }

                    // Render cards
                    renderCriteriaSidebarCards();

                    // Attach filter events
                    const searchInput = document.getElementById('criteriaSidebarSearch');
                    if (searchInput) {
                        searchInput.addEventListener('input', renderCriteriaSidebarCards);
                    }
                    if (filterDropdown) {
                        filterDropdown.addEventListener('change', renderCriteriaSidebarCards);
                    }

                    // Dynamic update image indicator
                    updateCriteriaImageIndicators();

                } else {
                    listContainer.innerHTML = '<div class="text-center py-4 text-muted small">Chưa có tiêu chuẩn kiểm nghiệm nào được thiết lập cho hồ sơ này.<br><small class="text-danger d-block mt-2">Mở Danh sách hồ sơ -> Nhấp chuột phải -> Thiết lập Tiêu chuẩn.</small></div>';
                }
            },
            error: function(err) {
                listContainer.innerHTML = '<div class="text-center text-danger py-4 small">Lỗi kết nối khi tải dữ liệu tiêu chuẩn!</div>';
            }
        });
    }

    function renderCriteriaSidebarCards() {
        const listContainer = document.getElementById('criteriaSidebarList');
        if (!listContainer) return;

        const searchVal = (document.getElementById('criteriaSidebarSearch')?.value || '').toLowerCase().trim();
        const stageVal = document.getElementById('criteriaSidebarStageFilter')?.value || '';

        let filtered = allCriteriaData.filter(item => {
            const matchesSearch = item.name.toLowerCase().includes(searchVal);
            const matchesStage = stageVal === "" || item.stage === stageVal;
            return matchesSearch && matchesStage;
        });

        if (filtered.length === 0) {
            listContainer.innerHTML = '<div class="text-center py-4 text-muted small">Không tìm thấy tiêu chuẩn phù hợp</div>';
            return;
        }

        let html = '';
        filtered.forEach(item => {
            let min = '', max = '', op = '', unit = '';
            if (item.limits) {
                try {
                    const l = typeof item.limits === 'string' ? JSON.parse(item.limits) : item.limits;
                    op = l.operator || '';
                    unit = l.unit || '';
                    if (op === 'range' || op === '±') {
                        min = l.value || '';
                        max = l.value_high || '';
                    } else {
                        min = max = l.value || '';
                    }
                } catch(e) {}
            }

            const specHtmlStr = typeof item.specifictions === 'string' ? item.specifictions : '';
            const stageName = allStagesMap[item.stage] || item.stage || 'Chung';
            const cleanSpec = stripHtmlTags(specHtmlStr) || '...';

            html += `
            <div class="card criteria-card shadow-sm p-2 mb-2">
                <div class="d-flex justify-content-between align-items-start mb-1" style="font-size: 0.7rem;">
                    <span class="badge bg-secondary py-1 px-2" style="border-radius: 4px;">${stageName}</span>
                    <span class="text-muted fw-bold">STT: ${item.stt || '-'}</span>
                </div>
                <div class="fw-bold text-navy mb-2" style="font-size: 0.8rem; line-height: 1.2;">${item.name}</div>
                
                <div class="d-flex flex-column gap-1">
                    <!-- Name Pill -->
                    <div class="draggable-pill" 
                         draggable="true" 
                         ondragstart="window.onCriteriaDragStart(event, '${item.id}', 'NAME', '${encodeURIComponent(item.name)}')"
                         title="Kéo thả để điền Tên chỉ tiêu">
                         <i class="fas fa-tag text-muted me-2" style="font-size: 0.7rem;"></i>
                         <span class="fw-bold me-1">1. Chỉ tiêu:</span> 
                         <span class="text-truncate" style="max-width: 200px; font-style: italic;">${item.name}</span>
                    </div>
                    
                    <!-- Spec Pill -->
                    <div class="draggable-pill" 
                         draggable="true" 
                         ondragstart="window.onCriteriaDragStart(event, '${item.id}', 'SPEC', '${encodeURIComponent(item.name)}', '${encodeURIComponent(specHtmlStr)}')"
                         title="Kéo thả để điền Tiêu chuẩn">
                         <i class="fas fa-file-alt text-primary me-2" style="font-size: 0.7rem;"></i>
                         <span class="fw-bold me-1">2. Tiêu chuẩn:</span> 
                         <span class="text-truncate" style="max-width: 180px; color: #475569;">${cleanSpec}</span>
                    </div>
                    
                    <!-- Result Pill -->
                    <div class="draggable-pill" 
                         draggable="true" 
                         ondragstart="window.onCriteriaDragStart(event, '${item.id}', 'RESULT', '${encodeURIComponent(item.name)}', '', '${min}', '${max}', '${op}', '${unit}')"
                         title="Kéo thả để chèn ô kết quả kiểm nghiệm">
                         <i class="fas fa-sliders-h text-danger me-2" style="font-size: 0.7rem;"></i>
                         <span class="fw-bold me-1">3. Giới hạn:</span>
                         <span class="fw-bold text-danger text-uppercase">${op} ${min} ${max ? 'đến ' + max : ''} ${unit}</span>
                    </div>
                </div>
            </div>
            `;
        });

        listContainer.innerHTML = html;
    }

    function stripHtmlTags(html) {
        if (!html) return '';
        const doc = new DOMParser().parseFromString(html, 'text/html');
        return doc.body.textContent || "";
    }

    window.onCriteriaDragStart = function(event, id, type, nameEnc, specEnc, min, max, op, unit) {
        event.dataTransfer.setData('criteria-id', id);
        event.dataTransfer.setData('criteria-type', type);
        event.dataTransfer.setData('criteria-name', nameEnc);
        event.dataTransfer.setData('criteria-spec', specEnc || '');
        event.dataTransfer.setData('criteria-min', min || '');
        event.dataTransfer.setData('criteria-max', max || '');
        event.dataTransfer.setData('criteria-op', op || '');
        event.dataTransfer.setData('criteria-unit', unit || '');
    };

    function normalizeVarName(str) {
        if (!str) return 'kq';
        return str
            .toLowerCase()
            .replace(/à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ/g, "a")
            .replace(/è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ/g, "e")
            .replace(/ì|í|ị|ỉ|ĩ/g, "i")
            .replace(/ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ/g, "o")
            .replace(/ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ/g, "u")
            .replace(/ỳ|ý|ỵ|ỷ|ỹ/g, "y")
            .replace(/đ/g, "d")
            .replace(/[^a-z0-9_]/g, "_")
            .replace(/_+/g, "_")
            .replace(/^_+|_+$/g, "");
    }

    window.handleCriteriaDrop = function(event, blockId, r, c) {
        event.preventDefault();
        const cell = event.currentTarget;
        cell.classList.remove('criteria-drag-over');

        const id = event.dataTransfer.getData('criteria-id');
        if (!id) return;

        const type = event.dataTransfer.getData('criteria-type');
        const name = decodeURIComponent(event.dataTransfer.getData('criteria-name') || '');
        const spec = decodeURIComponent(event.dataTransfer.getData('criteria-spec') || '');
        const min = event.dataTransfer.getData('criteria-min');
        const max = event.dataTransfer.getData('criteria-max');
        const op = event.dataTransfer.getData('criteria-op');
        const unit = event.dataTransfer.getData('criteria-unit');

        let html = '';
        if (type === 'NAME') {
            html = `<span class="criteria-name-display text-navy fw-bold" contenteditable="false" data-criteria-bind="NAME" data-criteria-id="${id}" title="Chỉ tiêu: ${name}">${name}</span>`;
        } else if (type === 'SPEC') {
            html = `<span class="criteria-display text-primary fw-bold" contenteditable="false" data-criteria-bind="SPEC" data-criteria-id="${id}" title="Tiêu chuẩn: ${name}">${spec || ('[Tiêu chuẩn: ' + name + ']')}</span>`;
        } else if (type === 'RESULT') {
            // Auto-create a corresponding variable in fieldsConfig
            const fieldId = 'field_crit_' + id;
            if (!fieldsConfig[fieldId]) {
                // Determine appropriate field type (number vs checkbox/tick)
                let isNumeric = true;
                if (op === 'N/A' || op === '') {
                    if (min === '' || isNaN(parseFloat(min))) {
                        isNumeric = false;
                    }
                } else if (op === 'range' || op === '±') {
                    if (min === '' || isNaN(parseFloat(min)) || max === '' || isNaN(parseFloat(max))) {
                        isNumeric = false;
                    }
                } else {
                    if (min === '' || isNaN(parseFloat(min))) {
                        isNumeric = false;
                    }
                }

                let varMin = null;
                let varMax = null;

                if (isNumeric) {
                    const parsedMin = min !== '' && !isNaN(parseFloat(min)) ? parseFloat(min) : null;
                    const parsedMax = max !== '' && !isNaN(parseFloat(max)) ? parseFloat(max) : null;

                    if (op === '<' || op === '<=') {
                        varMax = parsedMin;
                    } else if (op === '>' || op === '>=') {
                        varMin = parsedMin;
                    } else if (op === 'range') {
                        varMin = parsedMin;
                        varMax = parsedMax;
                    } else if (op === '±') {
                        if (parsedMin !== null && parsedMax !== null) {
                            varMin = parsedMin - parsedMax;
                            varMax = parsedMin + parsedMax;
                        }
                    } else if (op === '=' || op === '') {
                        varMin = parsedMin;
                        varMax = parsedMin;
                    }
                }

                fieldsConfig[fieldId] = {
                    id: fieldId,
                    name: normalizeVarName(name),
                    label: name,
                    type: isNumeric ? 'number' : 'select',
                    validation: {
                        required: true,
                        min: varMin,
                        max: varMax,
                        decimal_places: null
                    },
                    options: isNumeric ? [] : ['Đạt', 'Không đạt'],
                    section_id: window.activeSectionId || null,
                    block_id: blockId,
                    instruction: 'Giới hạn tiêu chuẩn: ' + op + ' ' + min + ' ' + (max ? 'đến ' + max : '') + ' ' + unit
                };
            }
            
            html = `<span contenteditable="false" class="ebmr-field-badge" data-field-id="${fieldId}" onclick="selectField(event, '${fieldId}')"></span>\u200B`;
        }

        // Insert into the cell's wrapper
        const wrapper = cell.querySelector('.cell-wrapper');
        if (wrapper) {
            wrapper.innerHTML = html;
            updateTableInline(blockId, 'cell', r, c, wrapper.innerHTML);
            saveStateDebounced();
            renderBlocks();
            // Trigger check for newly dropped criteria
            setTimeout(updateCriteriaImageIndicators, 50);
        }
    };

    // Auto-load criteria data on page load
    document.addEventListener('DOMContentLoaded', () => {
        const currentTemplateId = window.templateId || '{{ $template->id ?? 0 }}';
        if (currentTemplateId && currentTemplateId !== '0' && currentTemplateId !== '') {
            $.ajax({
                url: '/ebmr/templates/' + currentTemplateId + '/testing-data',
                method: 'GET',
                success: function(res) {
                    if (res.success && res.testing) {
                        allCriteriaData = res.testing;
                        if (res.sections) {
                            res.sections.forEach(s => {
                                allStagesMap[s.id] = s.label;
                            });
                        }
                        updateCriteriaImageIndicators();
                    }
                }
            });
        }

        // MutationObserver to watch for newly added criteria tags on canvas
        const editorContent = document.getElementById('editor-content');
        if (editorContent) {
            const observer = new MutationObserver(() => {
                updateCriteriaImageIndicators();
            });
            observer.observe(editorContent, {
                childList: true,
                subtree: true
            });
        }
    });

    // Function to update visual indicators for standards with images
    window.updateCriteriaImageIndicators = function() {
        document.querySelectorAll('span.criteria-display[data-criteria-id]').forEach(span => {
            const id = span.getAttribute('data-criteria-id');
            const item = (allCriteriaData || []).find(c => String(c.id) === String(id));
            if (item && item.images && item.images.length > 0) {
                if (span.getAttribute('data-has-images') !== 'true') {
                    span.setAttribute('data-has-images', 'true');
                    span.setAttribute('title', 'Xem hình ảnh đính kèm (Nhấp chuột để mở)');
                }
            } else {
                span.removeAttribute('data-has-images');
            }
        });
    };

    // Click handler for criteria display tags that contain images
    document.addEventListener('click', function(e) {
        const span = e.target.closest('span.criteria-display[data-criteria-id]');
        if (span && span.getAttribute('data-has-images') === 'true') {
            const id = span.getAttribute('data-criteria-id');
            const item = (allCriteriaData || []).find(c => String(c.id) === String(id));
            if (item && item.images && item.images.length > 0) {
                showCriteriaImagesCarousel(item);
            }
        }
    });

    function showCriteriaImagesCarousel(item) {
        $('#carouselViewerTitle').text('Hình ảnh minh họa: ' + item.name);

        const indicators = $('#testingCarouselIndicators');
        const inner = $('#testingCarouselInner');
        
        indicators.empty();
        inner.empty();

        item.images.forEach((img, idx) => {
            const activeClass = idx === 0 ? 'active' : '';
            indicators.append(`
                <li data-target="#testingCarousel" data-slide-to="${idx}" class="${activeClass}"></li>
            `);

            const descHtml = img.image_description 
                ? `<p class="mb-0 small">${escapeHtml(img.image_description)}</p>` 
                : '';

            inner.append(`
                <div class="carousel-item ${activeClass} h-100" style="position: relative;">
                    <div class="carousel-item-premium">
                        <img src="${img.image_path}" alt="${escapeHtml(img.image_name)}">
                    </div>
                    <div class="carousel-caption-premium">
                        <h6>${escapeHtml(img.image_name)}</h6>
                        ${descHtml}
                    </div>
                </div>
            `);
        });

        // Initialize bootstrap carousel
        $('#testingCarousel').carousel({
            interval: false
        }).carousel(0);

        $('#modalCarouselViewer').modal('show');
    }

    function escapeHtml(text) {
        if (!text) return '';
        return text
            .toString()
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    window.duplicateFieldBadgesInHtml = function(html, newBlockId = null, newSectionId = null) {
        if (!html || typeof html !== 'string' || !html.includes('ebmr-field-badge')) {
            return html;
        }
        
        let configObj = null;
        if (typeof fieldsConfig !== 'undefined') {
            configObj = fieldsConfig;
        } else if (window.fieldsConfig) {
            configObj = window.fieldsConfig;
        }
        if (!configObj) return html;

        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = html;
        const badges = tempDiv.querySelectorAll('.ebmr-field-badge');
        
        badges.forEach(badge => {
            const oldId = badge.getAttribute('data-field-id');
            if (oldId && configObj[oldId]) {
                const newId = 'field_' + Math.floor(Math.random() * 1000000);
                const oldConfig = configObj[oldId];
                
                // Tạo config mới, đổi tên tránh trùng
                let newName = oldConfig.name || `Var_${newId}`;
                let baseName = newName;
                let num = 1;
                const match = newName.match(/^(.*?)(\d+)$/);
                if (match) {
                    baseName = match[1];
                    num = parseInt(match[2]) + 1;
                }
                
                newName = baseName + num;
                while (Object.values(configObj).some(f => f && f.name === newName)) {
                    num++;
                    newName = baseName + num;
                }
                
                configObj[newId] = {
                    ...oldConfig,
                    id: newId,
                    name: newName
                };

                if (newBlockId) configObj[newId].block_id = newBlockId;
                if (newSectionId) configObj[newId].section_id = newSectionId;
                
                badge.setAttribute('data-field-id', newId);
                badge.setAttribute('onclick', `selectField(event, '${newId}')`);
            }
        });
        
        return tempDiv.innerHTML;
    };

    window.copyVariable = function(fieldId) {
        const field = fieldsConfig[fieldId];
        if (!field) return;
        window.copiedVariableConfig = JSON.parse(JSON.stringify(field));
        toastr.success('Đã sao chép cấu hình biến số: ' + (field.label || field.name));
    };

    window.pasteVariable = function() {
        if (!window.copiedVariableConfig) {
            toastr.warning('Chưa có biến số nào được sao chép.');
            return;
        }

        const oldConfig = window.copiedVariableConfig;

        // Check if there is an active table cell selection or selected cells
        const selectedCells = document.querySelectorAll('.selected-cell');

        let useBatch = false;
        if (selectedCells.length > 1) {
            useBatch = true;
        } else if (selectedCells.length === 1) {
            let hasCursorInCell = false;
            const selForCheck = savedTextSelection ? [savedTextSelection] : (window.getSelection().rangeCount > 0 ? [window.getSelection().getRangeAt(0)] : []);
            if (selForCheck.length > 0) {
                let node = selForCheck[0].commonAncestorContainer;
                if (node.nodeType === 3) node = node.parentNode;
                if (node.closest && node.closest('td, th') === selectedCells[0]) {
                    hasCursorInCell = true;
                }
            }
            if (!hasCursorInCell) {
                useBatch = true;
            }
        }

        if (useBatch) {
            saveState();
            selectedCells.forEach(td => {
                const block = td.closest('.block-item');
                if (!block) return;
                const blockId = block.getAttribute('data-id');
                const item = items.find(i => i.id === blockId);
                if (!item || item.type !== 'table') return;

                const rStr = td.dataset.row;
                const cStr = td.dataset.col;
                if (rStr === undefined || cStr === undefined) return;

                const r = parseInt(rStr) - 1; // 1-indexed for non-header
                const c = parseInt(cStr);

                // For multi-cell, each cell needs a unique ID!
                const cellId = 'field_' + Math.floor(Math.random() * 1000000);
                let cellName = oldConfig.name || `Var_${cellId}`;
                let cellBaseName = cellName;
                let cellNum = 1;
                const cellMatch = cellName.match(/^(.*?)(\d+)$/);
                if (cellMatch) {
                    cellBaseName = cellMatch[1];
                    cellNum = parseInt(cellMatch[2]) + 1;
                }
                
                cellName = cellBaseName + cellNum;
                while (Object.values(fieldsConfig).some(f => f && f.name === cellName)) {
                    cellNum++;
                    cellName = cellBaseName + cellNum;
                }

                fieldsConfig[cellId] = {
                    ...oldConfig,
                    id: cellId,
                    name: cellName,
                    block_id: blockId,
                    section_id: item.section_id || null
                };

                const cellBadgeHtml = `<span contenteditable="false" class="ebmr-field-badge" data-field-id="${cellId}" onclick="selectField(event, '${cellId}')"></span>\u200B`;

                if (!item.data[r][c] || typeof item.data[r][c] !== 'object') {
                    item.data[r][c] = {
                        content: cellBadgeHtml,
                        rs: 1,
                        cs: 1,
                        hidden: false
                    };
                } else {
                    if (item.data[r][c].content && item.data[r][c].content.trim() !== '') {
                        item.data[r][c].content += cellBadgeHtml;
                    } else {
                        item.data[r][c].content = cellBadgeHtml;
                    }
                }
                item.dirty = true;
            });
            renderBlocks();
            toastr.success('Đã dán biến số vào các ô đã chọn');
            return;
        }

        // Insert at cursor
        if (savedTextSelection) {
            const currentSel = window.getSelection();
            currentSel.removeAllRanges();
            currentSel.addRange(savedTextSelection);
        }

        const currentSel = window.getSelection();
        if (currentSel && currentSel.rangeCount > 0) {
            const range = currentSel.getRangeAt(0);
            
            // Check if cursor is actually inside a contenteditable area
            const startContainer = range.startContainer;
            const element = startContainer.nodeType === 1 ? startContainer : startContainer.parentElement;
            if (element && element.closest('[contenteditable="true"]')) {
                const newId = 'field_' + Math.floor(Math.random() * 1000000);
                
                let newName = oldConfig.name || `Var_${newId}`;
                let baseName = newName;
                let num = 1;
                const match = newName.match(/^(.*?)(\d+)$/);
                if (match) {
                    baseName = match[1];
                    num = parseInt(match[2]) + 1;
                }
                
                newName = baseName + num;
                while (Object.values(fieldsConfig).some(f => f && f.name === newName)) {
                    num++;
                    newName = baseName + num;
                }

                let detectedBlockId = selectedId;
                let detectedSectionId = window.activeSectionId;
                const blockEl = element.closest('.block-item');
                if (blockEl) {
                    detectedBlockId = blockEl.getAttribute('data-id');
                    const sectionEl = blockEl.closest('.section-group-wrapper');
                    if (sectionEl) {
                        detectedSectionId = sectionEl.getAttribute('data-section-id');
                    }
                }

                fieldsConfig[newId] = {
                    ...oldConfig,
                    id: newId,
                    name: newName,
                    block_id: detectedBlockId,
                    section_id: detectedSectionId
                };

                const span = document.createElement('span');
                span.contentEditable = "false";
                span.className = "ebmr-field-badge";
                span.setAttribute('data-field-id', newId);
                span.setAttribute('onclick', `selectField(event, '${newId}')`);
                const zeroWidthSpace = document.createTextNode('\u200B');
                
                range.deleteContents();
                range.insertNode(zeroWidthSpace);
                range.insertNode(span);
                range.setStartAfter(zeroWidthSpace);
                range.collapse(true);
                currentSel.removeAllRanges();
                currentSel.addRange(range);
                
                const ce = span.closest('[contenteditable="true"]');
                if (ce) {
                    ce.dispatchEvent(new Event('input', { bubbles: true }));
                }
                if (typeof syncBlockContent === 'function') {
                    syncBlockContent(span);
                }
                saveStateDebounced();
                renderBlocks();
                selectField(null, newId);
                toastr.success('Đã dán biến số thành công');
                return;
            }
        }
        
        // If not in contenteditable, check if we have a single active table cell
        const activeCell = document.querySelector('td.active, th.active') || document.activeElement?.closest('td, th');
        if (activeCell) {
            const block = activeCell.closest('.block-item');
            if (block) {
                const blockId = block.getAttribute('data-id');
                const item = items.find(i => i.id === blockId);
                if (item && item.type === 'table') {
                    const rStr = activeCell.dataset.row;
                    const cStr = activeCell.dataset.col;
                    if (rStr !== undefined && cStr !== undefined) {
                        saveState();
                        const r = parseInt(rStr) - 1;
                        const c = parseInt(cStr);
                        if (r >= 0) {
                            const newId = 'field_' + Math.floor(Math.random() * 1000000);
                            
                            let newName = oldConfig.name || `Var_${newId}`;
                            let baseName = newName;
                            let num = 1;
                            const match = newName.match(/^(.*?)(\d+)$/);
                            if (match) {
                                baseName = match[1];
                                num = parseInt(match[2]) + 1;
                            }
                            
                            newName = baseName + num;
                            while (Object.values(fieldsConfig).some(f => f && f.name === newName)) {
                                num++;
                                newName = baseName + num;
                            }

                            fieldsConfig[newId] = {
                                ...oldConfig,
                                id: newId,
                                name: newName,
                                block_id: blockId,
                                section_id: item.section_id || null
                            };

                            const cellBadgeHtml = `<span contenteditable="false" class="ebmr-field-badge" data-field-id="${newId}" onclick="selectField(event, '${newId}')"></span>\u200B`;

                            if (!item.data[r][c] || typeof item.data[r][c] !== 'object') {
                                item.data[r][c] = { content: cellBadgeHtml, rs: 1, cs: 1, hidden: false };
                            } else {
                                item.data[r][c].content = cellBadgeHtml;
                            }
                            item.dirty = true;
                            renderBlocks();
                            selectField(null, newId);
                            toastr.success('Đã dán biến số vào ô đang chọn');
                            return;
                        }
                    }
                }
            }
        }

        toastr.warning('Vui lòng đặt con trỏ chuột vào vùng soạn thảo hoặc chọn ô bảng để dán biến số.');
    };

    /**
     * Kích hoạt chế độ kéo thả điều chỉnh kích thước (width) và vị trí (margin-left) của thẻ biến số.
     * @param {MouseEvent} e - Sự kiện mousedown.
     * @param {string} fieldId - ID của biến số.
     * @param {string} handleType - 'left' để chỉnh lề trái, 'right' để chỉnh độ rộng.
     */
    window.initBadgeResize = function(e, fieldId, handleType) {
        e.preventDefault();
        e.stopPropagation();
        
        // Chọn biến số để cập nhật Properties Panel
        if (typeof selectField === 'function') {
            selectField(null, fieldId);
        }
        
        const badgeEl = document.querySelector(`.ebmr-field-badge[data-field-id="${fieldId}"]`);
        if (!badgeEl) return;
        
        const initialMouseX = e.clientX;
        
        if (!fieldsConfig[fieldId]) {
            fieldsConfig[fieldId] = { id: fieldId };
        }
        if (!fieldsConfig[fieldId].style) {
            fieldsConfig[fieldId].style = {};
        }
        const styleObj = fieldsConfig[fieldId].style;
        
        const computedStyle = window.getComputedStyle(badgeEl);
        const initialWidth = parseInt(styleObj.width || computedStyle.width) || badgeEl.offsetWidth;
        const initialMarginLeft = parseInt(styleObj.marginLeft || computedStyle.marginLeft) || 0;
        
        const originalBodyCursor = document.body.style.cursor;
        document.body.style.cursor = 'ew-resize';
        
        const onMouseMove = (moveEvent) => {
            const deltaX = moveEvent.clientX - initialMouseX;
            
            if (handleType === 'right') {
                const newWidth = Math.max(50, initialWidth + deltaX);
                badgeEl.style.setProperty('width', `${newWidth}px`, 'important');
                styleObj.width = `${newWidth}px`;
                
                // Đồng bộ lên Input chiều rộng trong Properties Panel nếu đang chọn
                const wInput = document.querySelector('input[oninput*="style.width"]');
                if (wInput && selectedFieldId === fieldId) {
                    wInput.value = newWidth;
                }
            } else if (handleType === 'left') {
                const newMarginLeft = Math.max(0, initialMarginLeft + deltaX);
                badgeEl.style.setProperty('margin-left', `${newMarginLeft}px`, 'important');
                styleObj.marginLeft = `${newMarginLeft}px`;
                
                // Đồng bộ lên Input lề trái trong Properties Panel nếu đang chọn
                const mlInput = document.querySelector('input[oninput*="style.marginLeft"]');
                if (mlInput && selectedFieldId === fieldId) {
                    mlInput.value = newMarginLeft;
                }
            }
        };
        
        const onMouseUp = () => {
            document.removeEventListener('mousemove', onMouseMove);
            document.removeEventListener('mouseup', onMouseUp);
            document.body.style.cursor = originalBodyCursor;
            
            if (typeof saveStateDebounced === 'function') {
                saveStateDebounced();
            }
        };
        
        document.addEventListener('mousemove', onMouseMove);
        document.addEventListener('mouseup', onMouseUp);
    };
</script>
