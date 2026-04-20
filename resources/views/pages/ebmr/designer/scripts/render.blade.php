<script>
    function renderBlocks() {
        const container = document.getElementById('editor-content');
        const hint = document.getElementById('drop-hint');
        if (!container) return;

        if (window.isExecutionMode) {
            selectedId = null; // Luôn bỏ chọn khối ở chế độ ghi chép
        }
        
        container.innerHTML = '';
        if (hint) {
            if (!window.isExecutionMode) container.appendChild(hint);
            else hint.remove();
        }

        items.forEach((item, idx) => {
            if (!window.isReadOnly && !window.isExecutionMode) {
                addInsertionDivider(container, idx);
            }

            const div = document.createElement('div');
            // 'active' class will never be added in execution mode because selectedId is null
            div.className = `block-item type-${item.type} ${selectedId === item.id ? 'active' : ''} ${window.isExecutionMode ? 'execution-mode' : ''}`;
            div.setAttribute('data-id', item.id);
            if (item.marginLeft) div.style.marginLeft = item.marginLeft;
            if (item.marginRight) div.style.marginRight = item.marginRight;
            if (item.backgroundColor) div.style.backgroundColor = item.backgroundColor;
            
            if (!window.isExecutionMode) {
                div.onclick = (e) => {
                    e.stopPropagation();
                    if (selectedId !== item.id) {
                        document.querySelectorAll('.block-item').forEach(el => el.classList.remove('active'));
                        div.classList.add('active');
                        selectItem(item.id, false);
                    }
                };
            }

            let content = `<div class="block-mock"></div>`;
            if (item.type === 'table') {
                const borderClass = item.borderMode === 'dashed' ? 'border-dashed' : (item.borderMode === 'none' ? 'border-none' : '');

                let thead = '';
                if (!item.hideHeader) {
                    thead = `<thead><tr>${item.columns.map((c, cIdx) => {
                        const bg = (item.columns[cIdx].style && item.columns[cIdx].style.backgroundColor) ? item.columns[cIdx].style.backgroundColor : '';
                        return `<th contenteditable="false" spellcheck="false" style="width: ${c.width || 'auto'}; background-color: ${bg};">${c.label}</th>`;
                    }).join('')}</tr></thead>`;
                }

                let rowsHtml = '';
                const blockKey = item.uuid || item.id;
                const runDataForBlock = window.executionValues[blockKey] || {};

                for (let r = 0; r < (item.rows || 1); r++) {
                    let cellsHtml = '';
                    const rowH = (item.rowHeights && item.rowHeights[r]) ? item.rowHeights[r] : 'auto';
                    for (let c = 0; c < (item.cols || 1); c++) {
                        if (!item.data[r][c] || typeof item.data[r][c] !== 'object') {
                            item.data[r][c] = { content: item.data[r][c] || '', rs: 1, cs: 1, hidden: false };
                        }
                        const cell = item.data[r][c];
                        if (cell.hidden) continue;

                        const cellWidth = (item.columns && item.columns[c] && item.columns[c].width) ? item.columns[c].width : 'auto';
                        const cellBg = (cell.backgroundColor) ? cell.backgroundColor : '';

                        let displayContent = cell.content;
                        let cellClass = "";
                        let onclickAttr = "";
                        let finalEditable = "false";

                        if (window.isExecutionMode) {
                            const runVal = runDataForBlock[`${r}_${c}`];
                            if (displayContent.includes('[Nhập dữ liệu]')) {
                                cellClass = "execution-input-cell";
                                onclickAttr = `onclick="openExecutionInputModal('${blockKey}', ${r}, ${c}, 'text')"`;
                                displayContent = runVal ? runVal : `<span class="execution-badge input"><i class="fas fa-edit"></i> [Nhập dữ liệu]</span>`;
                            } else if (displayContent.includes('[Ký tên]')) {
                                cellClass = "execution-input-cell";
                                onclickAttr = `onclick="openExecutionInputModal('${blockKey}', ${r}, ${c}, 'signature')"`;
                                displayContent = runVal ? `<div class="e-signature-done"><i class="fas fa-check-circle text-success me-1"></i>${runVal}</div>` : `<span class="execution-badge signature"><i class="fas fa-pen"></i> [Ký tên]</span>`;
                            }
                        } else {
                            finalEditable = (item.locked || window.isReadOnly) ? 'false' : 'true';
                        }

                        cellsHtml += `
                            <td contenteditable="${finalEditable}" spellcheck="false" data-row="${r+1}" data-col="${c}" 
                                rowspan="${cell.rs || 1}" colspan="${cell.cs || 1}" ${onclickAttr}
                                class="${cellClass} ${item.locked ? 'locked-cell' : ''}" 
                                style="width: ${cellWidth}; height: ${rowH}; background-color: ${cellBg};"
                                oninput="updateTableInline('${item.id}', 'cell', ${r}, ${c}, this.innerHTML)">
                                ${displayContent}
                            </td>`;
                    }
                    rowsHtml += `<tr>${cellsHtml}</tr>`;
                }
                content = `<table class="mini-table ${borderClass}">${thead}<tbody>${rowsHtml}</tbody></table>`;
            } else if (item.type === 'signature') {
                const blockKey = item.uuid || item.id;
                const runVal = window.executionValues[blockKey];
                const onclickAttr = window.isExecutionMode ? `onclick="openExecutionInputModal('${blockKey}', 0, 0, 'signature')"` : '';
                content = `<div class="block-mock d-flex align-items-center justify-content-center text-muted py-5" ${onclickAttr} style="cursor: pointer;">
                             ${runVal ? `<div class="text-success capitalise"><i class="fas fa-check-circle me-2"></i>Đã ký: ${runVal}</div>` : `<i class="fas fa-pen-nib me-2"></i>${item.content || 'Ký xác nhận (Click để ký)'}`}
                           </div>`;
            } else if (item.type === 'static-text') {
                const displayContent = item.content || '';
                const textEditable = (window.isReadOnly || window.isExecutionMode) ? 'false' : 'true';
                content = `<div class="static-text-display" contenteditable="${textEditable}" spellcheck="false" 
                                oninput="updateStaticTextInline('${item.id}', this.innerHTML); handleAutoCapitalize(this)">
                                ${displayContent}
                           </div>`;
            }

            const actions = (item.locked || window.isReadOnly || window.isExecutionMode) ? '' : `
                <div class="block-actions">
                    <button class="btn btn-sm btn-light border shadow-sm text-danger" onclick="removeItem('${item.id}')"><i class="fas fa-trash"></i></button>
                    <button class="btn btn-sm btn-light border shadow-sm" onclick="moveItem(${idx}, -1)"><i class="fas fa-chevron-up"></i></button>
                    <button class="btn btn-sm btn-light border shadow-sm" onclick="moveItem(${idx}, 1)"><i class="fas fa-chevron-down"></i></button>
                </div>`;

            div.innerHTML = `
                ${actions}
                ${item.type !== 'static-text' && !window.isExecutionMode ? `<span class="block-label">${item.label} ${item.locked ? '<i class="fas fa-lock ms-1 small"></i>' : ''}</span>` : ''}
                ${content}
            `;
            container.appendChild(div);
        });

        if (items.length > 0) {
            if (!window.isReadOnly && !window.isExecutionMode) {
                addInsertionDivider(container, items.length);
            }
        }
        
        // Rebuild Outline when DOM changes
        if (typeof buildOutline === 'function') {
            setTimeout(buildOutline, 100);
        }
    }

    function addInsertionDivider(container, idx) {
        const divider = document.createElement('div');
        divider.className = 'insert-divider';
        divider.innerHTML = `
            <div class="insert-click-zone" title="Click đúp để gõ văn bản tại đây" ondblclick="quickAddText(event, ${idx})"></div>
            <button class="insert-btn" title="Chèn vào đây"><i class="fas fa-plus"></i></button>
            <div class="insert-menu shadow-lg">
                <div class="small fw-bold text-muted px-2 mb-1">TIÊU ĐỀ</div>
                <button onclick="addItem('static-text', ${idx}, 'H1')"><i class="fas fa-heading me-2" style="width: 15px;"></i> Cấp 1</button>
                <button onclick="addItem('static-text', ${idx}, 'H2')"><i class="fas fa-heading me-2" style="width: 15px; font-size: 0.9em;"></i> Cấp 2</button>
                <button onclick="addItem('static-text', ${idx}, 'H3')"><i class="fas fa-heading me-2" style="width: 15px; font-size: 0.8em;"></i> Cấp 3</button>
                <button onclick="addItem('static-text', ${idx}, 'H4')"><i class="fas fa-heading me-2" style="width: 15px; font-size: 0.7em;"></i> Cấp 4</button>
                <hr class="my-1 mx-2">
                <div class="small fw-bold text-muted px-2 mb-1">KHÁC</div>
                <button onclick="addItem('static-text', ${idx})"><i class="fas fa-paragraph me-2" style="width: 15px;"></i> Văn bản</button>
                <button onclick="showTableSelectorAt(${idx}, this)"><i class="fas fa-table me-2" style="width: 15px;"></i> Bảng</button>
            </div>
        `;

        divider.onclick = (e) => {
            e.stopPropagation();
            const menu = divider.querySelector('.insert-menu');
            document.querySelectorAll('.insert-menu').forEach(m => {
                if (m !== menu) m.classList.remove('show');
            });
            menu.classList.toggle('show');
        };

        container.appendChild(divider);
    }

    function showTableSelectorAt(idx, btn) {
        addTable(3, 2, idx);
    }

    window.quickAddText = function(e, idx) {
        e.stopPropagation();
        const newItemId = 'item_' + Date.now();
        items.splice(idx, 0, {
            id: newItemId,
            type: 'static-text',
            label: 'Nội dung',
            content: ''
        });
        saveStateDebounced();
        renderBlocks();
        
        setTimeout(() => {
            const blocks = document.querySelectorAll('.block-item');
            if(blocks[idx]) {
                const textEl = blocks[idx].querySelector('.static-text-display');
                if(textEl) {
                    textEl.focus();
                    // Optional: remove placeholder class if typing
                }
            }
        }, 50);
    };
</script>
