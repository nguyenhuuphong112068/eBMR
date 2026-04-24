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

                const defaultLabel = 'Nhập ' + typeLabel;

                // Register in fieldsConfig
                fieldsConfig[fieldId] = {
                    id: fieldId,
                    name: dynamicName,
                    label: defaultLabel,
                    type: defaultType,
                    validation: { required: false, min: null, max: null, decimal_places: null },
                    options: []
                };

                // Create the badge HTML
                const badgeHtml = `<span contenteditable="false" class="ebmr-field-badge" data-field-id="${fieldId}" onclick="selectField(event, '${fieldId}')"></span>\u200B`;
                
                // Update item data
                if (!item.data[r][c] || typeof item.data[r][c] !== 'object') {
                    item.data[r][c] = { content: badgeHtml, rs: 1, cs: 1, hidden: false };
                } else {
                    item.data[r][c].content = badgeHtml;
                }
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

        const defaultLabel = 'Nhập ' + typeLabel;

        fieldsConfig[fieldId] = {
            id: fieldId,
            name: dynamicName,
            label: defaultLabel,
            type: defaultType,
            validation: { required: false, min: null, max: null, decimal_places: null },
            options: []
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
            if (ce) ce.dispatchEvent(new Event('input', { bubbles: true }));
        } else {
             const html = `<span contenteditable="false" class="ebmr-field-badge" data-field-id="${fieldId}" onclick="selectField(event, '${fieldId}')"></span>\u200B`;
             document.execCommand('insertHTML', false, html);
        }
        
        saveStateDebounced();
        selectField(null, fieldId);
    }
</script>
