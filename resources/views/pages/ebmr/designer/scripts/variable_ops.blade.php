<script>
    // Dynamic Fields Data Handling
    function insertDynamicField(defaultType = 'text') {
        const selectedCells = document.querySelectorAll('.selected-cell');

        if (selectedCells.length > 0) {
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
                else if (defaultType === 'date') typeLabel = 'Ngày';
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
                    options: []
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
                    item.data[r][c].content = badgeHtml;
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
        else if (defaultType === 'date') typeLabel = 'Ngày';
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
            block_id: detectedBlockId
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
        } else {
            const html =
                `<span contenteditable="false" class="ebmr-field-badge" data-field-id="${fieldId}" onclick="selectField(event, '${fieldId}')"></span>\u200B`;
            document.execCommand('insertHTML', false, html);
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
</script>
