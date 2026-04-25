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
        let lastSectionId = null;
        let currentGroup = container;
        let activeSectionIdTracker = null;

        items.forEach((item, idx) => {
                // Determine the section this item belongs to based on sequence
                if (item.type === 'section') {
                    activeSectionIdTracker = item.id;
                }

                const itemSectionId = activeSectionIdTracker || 'section_0';
                item.section_id = itemSectionId; // Sync data with sequence

                // Skip rendering if it's the old signature block (being deprecated)
                if (item.type === 'signature') return;

                // Page Break & Grouping Logic
                if (window.isViewAllMode || !window.activeSectionId) {
                    if (lastSectionId === null || itemSectionId !== lastSectionId) {
                        // Create Page Break if not the first section
                        if (lastSectionId !== null) {
                            const pageBreak = document.createElement('div');
                            pageBreak.className =
                                'page-break-divider my-4 d-flex align-items-center justify-content-center';
                            const parts = (itemSectionId || '').split('_');
                            const labelText = parts.length > 1 ? `Công đoạn ${parts[parts.length-1]}` :
                                'Phân đoạn mới';
                            pageBreak.innerHTML =
                                `<span class="bg-light px-3 py-1 rounded-pill small fw-bold text-muted border"><i class="fas fa-file-alt me-2"></i>${labelText}</span>`;
                            container.appendChild(pageBreak);
                        }

                        // Create a new Section Group Wrapper
                        currentGroup = document.createElement('div');
                        currentGroup.className = 'section-group-wrapper' + (window.activeSectionId ===
                            itemSectionId ? ' active' : '');
                        currentGroup.setAttribute('data-section-id', itemSectionId);
                        currentGroup.onclick = (e) => {
                            // Activate section when clicking its wrapper background
                            if (e.target === currentGroup) {
                                window.activeSectionId = itemSectionId;
                                selectedId = null; // Deselect specific block
                                renderBlocks();
                            }
                        };
                        container.appendChild(currentGroup);

                        lastSectionId = itemSectionId;
                    }
                } else {
                    // If filtered to a specific section, no wrappers needed or just one
                    currentGroup = container;
                }

                if (!window.isReadOnly && !window.isExecutionMode) {
                    addInsertionDivider(currentGroup, idx);
                }

                const div = document.createElement('div');
                // 'active' class will never be added in execution mode because selectedId is null
                div.className =
                    `block-item type-${item.type} ${selectedId === item.id ? 'active' : ''} ${window.isExecutionMode ? 'execution-mode' : ''}`;
                div.setAttribute('data-id', item.id);
                if (item.marginLeft) div.style.marginLeft = item.marginLeft;
                if (item.marginRight) div.style.marginRight = item.marginRight;
                if (item.backgroundColor) div.style.backgroundColor = item.backgroundColor;

                if (!window.isExecutionMode) {
                    div.onclick = (e) => {
                        e.stopPropagation();
                        if (selectedId !== item.id) {
                            // Update active section highlight without full re-render if possible
                            // But for simplicity, we call selectItem which might re-render
                            selectItem(item.id, true); // doRender=true to update the wrapper's 'active' class
                        }
                    };
                }

                let content = `<div class="block-mock"></div>`;
                if (item.type === 'table') {
                    const borderClass = item.borderMode === 'dashed' ? 'border-dashed' : (item.borderMode ===
                        'none' ? 'border-none' : '');

                    let thead = '';
                    if (!item.hideHeader) {
                        thead = `<thead><tr>${item.columns.map((c, cIdx) => {
                        const s = c.style || {};
                        const bg = s.backgroundColor || '';
                        const align = s.textAlign || '';
                        const fw = s.fontWeight || '';
                        const fs = s.fontStyle || '';
                        const td = s.textDecoration || '';
                        const fsz = s.fontSize || '';
                        const tc = s.textColor || '';
                        return `<th contenteditable="false" spellcheck="false" data-row="0" data-col="${cIdx}" style="width: ${c.width || 'auto'}; background-color: ${bg}; text-align: ${align}; font-weight: ${fw}; font-style: ${fs}; text-decoration: ${td}; font-size: ${fsz}; color: ${tc};">${c.label}</th>`;
                    }).join('')}
                    ${window.isExecutionMode && item.canAddRows ? '<th style="width: 30px; border: none; background: transparent;"></th>' : ''}
                </tr></thead>`;
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

                        let displayContent = decorateContent(cell.content);
                        if (displayContent === null || displayContent === 'null' || displayContent === undefined) {
                            displayContent = '';
                        }
                        
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

                        cellsHtml += `<td contenteditable="${finalEditable}" spellcheck="false" data-row="${r+1}" data-col="${c}" rowspan="${cell.rs || 1}" colspan="${cell.cs || 1}" ${onclickAttr} class="${cellClass} ${item.locked ? 'locked-cell' : ''}" style="width: ${cellWidth}; height: ${rowH}; background-color: ${cellBg}; text-align: ${cell.textAlign || ''}; font-weight: ${cell.fontWeight || ''}; font-style: ${cell.fontStyle || ''}; text-decoration: ${cell.textDecoration || ''}; font-size: ${cell.fontSize || ''}; color: ${cell.textColor || ''}; text-transform: ${cell.textTransform || ''};" oninput="updateTableInline('${item.id}', 'cell', ${r}, ${c}, this.innerHTML)">${displayContent}</td>`;
        }
        let deleteCell = '';
        if (window.isExecutionMode && item.canAddRows) {
            deleteCell = `<td class="execution-delete-cell" style="width: 30px; border: none; background: transparent; vertical-align: middle;">
                                        <button class="btn btn-link text-danger p-0" title="Xóa dòng" onclick="executeDeleteTableRow('${item.id}', ${r})">
                                            <i class="fas fa-times-circle"></i>
                                        </button>
                                      </td>`;
        }
        rowsHtml += `<tr>${cellsHtml}${deleteCell}</tr>`;
    }

    let addRowBtn = '';
    if (item.canAddRows) {
        const btnLabel = window.isExecutionMode ? 'THÊM DÒNG CUỐI' : 'THỬ THÊM DÒNG (CẤP 2)';
        addRowBtn = `
                        <div class="mt-2 text-start">
                            <button class="btn btn-xs btn-outline-primary py-0 px-2 fw-bold" style="font-size: 0.65rem; border-radius: 4px;" onclick="executeAddTableRow('${item.id}')">
                                <i class="fas fa-plus me-1"></i> ${btnLabel}
                            </button>
                        </div>
                    `;
    }

    content = `<table class="mini-table ${borderClass}">${thead}<tbody>${rowsHtml}</tbody></table>${addRowBtn}`;
    }
    else if (item.type === 'static-text') {
        const displayContent = decorateContent(item.content || '');
        const textEditable = (window.isReadOnly || window.isExecutionMode) ? 'false' : 'true';
        const borderClass = item.borderMode === 'dashed' ? 'border-dashed' : (item.borderMode === 'visible' ?
            'border-visible' : 'border-none');

        content =
            `<div class="static-text-display ${borderClass}" contenteditable="${textEditable}" spellcheck="false" 
                                oninput="updateStaticTextInline('${item.id}', this.innerHTML); handleAutoCapitalize(this)">${displayContent}</div>`;
    } else if (item.type === 'linked-template') {
        const isPreviewing = item.showPreview || false;
        const previewContent = isPreviewing ? `<div id="preview-${item.id}" class="mt-3 p-4 border rounded bg-white w-100 shadow-sm" style="pointer-events: none; opacity: 0.9;">
                    <div class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary"></div></div>
                </div>` : '';

        content = `<div class="block-mock d-flex flex-column align-items-center justify-content-center py-4 px-3 position-relative" style="background-color: #f8f9fa; border: 2px dashed #0d6efd; border-radius: 12px; min-height: 120px;">
                             <div class="position-absolute" style="top: 10px; right: 10px; z-index: 100;">
                                <button class="btn btn-sm btn-primary shadow-sm px-3" onclick="event.stopPropagation(); toggleGfPreview('${item.id}')" style="border-radius: 20px;">
                                    <i class="fas ${isPreviewing ? 'fa-eye-slash' : 'fa-eye'} me-1"></i> ${isPreviewing ? 'Ẩn nội dung' : 'Xem nội dung'}
                                </button>
                             </div>
                             <i class="fas fa-link fa-2x text-primary mb-2 ${isPreviewing ? 'd-none' : ''}"></i>
                             <div class="fw-bold text-navy ${isPreviewing ? 'mb-2 border-bottom pb-2 w-100' : ''}">Biểu mẫu chung: ${item.label || 'Đang tải...'}</div>
                             ${!isPreviewing ? `<div class="small text-muted mt-1">Nội dung sẽ được tự động chèn vào khi ban hành/thực thi</div>` : ''}
                             ${previewContent}
                           </div>`;

        if (isPreviewing) {
            setTimeout(() => fetchAndRenderGfPreview(item.id, item.template_id), 50);
        }
    } else if (item.type === 'section') {
        const labelEditable = (window.isReadOnly || window.isExecutionMode) ? 'false' : 'true';
        content = `<div class="ebmr-section-header d-flex align-items-center" id="section-${item.id}">
                             <div class="section-icon bg-info text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; min-width: 40px;">
                                <i class="fas fa-layer-group"></i>
                             </div>
                             <div class="flex-grow-1">
                                <div class="section-title fw-bold text-uppercase" contenteditable="${labelEditable}" 
                                     onblur="updateItemProp('label', this.innerText)" 
                                     style="font-size: 1.2rem; color: #164e63; letter-spacing: 1px;">${item.label || 'Tên phân đoạn'}</div>
                                <div class="section-line mt-1" style="height: 3px; background: linear-gradient(to right, #0ea5e9, transparent); border-radius: 2px;"></div>
                             </div>
                           </div>`;
    } else if (item.type === 'chart') {
        const canvasId = 'chart_canvas_' + item.id;
        content = `<div class="chart-container" style="position: relative; height:300px; width:100%; padding: 15px; background: #fff; border: 1px solid #eee; border-radius: 8px;">
                             <canvas id="${canvasId}"></canvas>
                           </div>`;
        // Schedule chart initialization after DOM is ready
        setTimeout(() => {
            if (typeof renderChart === 'function') {
                renderChart(canvasId, item.chartConfig);
            }
        }, 50);
    }

    const actions = (item.locked || window.isReadOnly || window.isExecutionMode) ? '' : `
                <div class="block-actions">
                    <button class="btn btn-sm btn-light border shadow-sm text-danger" onclick="removeItem('${item.id}')"><i class="fas fa-trash"></i></button>
                    <button class="btn btn-sm btn-light border shadow-sm" onclick="moveItem(${idx}, -1)"><i class="fas fa-chevron-up"></i></button>
                    <button class="btn btn-sm btn-light border shadow-sm" onclick="moveItem(${idx}, 1)"><i class="fas fa-chevron-down"></i></button>
                </div>`;

    div.innerHTML = `
                ${actions}
                ${item.type !== 'static-text' && !window.isExecutionMode && item.label && item.label !== 'null' && !item.isGfHeader ? `<span class="block-label">${item.label} ${item.locked ? '<i class="fas fa-lock ms-1 small"></i>' : ''}</span>` : ''}
                ${content}
            `;
    currentGroup.appendChild(div);
    });

    if (items.length > 0) {
        if (!window.isReadOnly && !window.isExecutionMode) {
            addInsertionDivider(currentGroup, items.length);
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
                <hr class="my-1 mx-2">
                <button onclick="pasteAt(${idx})"><i class="fas fa-paste me-2" style="width: 15px;"></i> Dán nội dung</button>
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
            content: '',
            borderMode: 'none'
        });
        saveStateDebounced();
        renderBlocks();

        setTimeout(() => {
            const blocks = document.querySelectorAll('.block-item');
            if (blocks[idx]) {
                const textEl = blocks[idx].querySelector('.static-text-display');
                if (textEl) {
                    textEl.focus();
                    // Optional: remove placeholder class if typing
                }
            }
        }, 50);
    };
    window.pasteAt = async function(idx) {
        try {
            const clipboardItems = await navigator.clipboard.read();
            let htmlData = "";
            let plainText = "";

            for (const item of clipboardItems) {
                if (item.types.includes('text/html')) {
                    const blob = await item.getType('text/html');
                    htmlData = await blob.text();
                }
                if (item.types.includes('text/plain')) {
                    const blob = await item.getType('text/plain');
                    plainText = await blob.text();
                }
            }

            if (!htmlData && !plainText) {
                Swal.fire('Chú ý', 'Bộ nhớ tạm trống hoặc không có nội dung hợp lệ.', 'warning');
                return;
            }

            // Create a mock event for handleGlobalPaste or just call logic
            const mockEvent = {
                clipboardData: {
                    getData: (type) => (type === 'text/html' ? htmlData : plainText)
                },
                preventDefault: () => {},
                target: {
                    closest: () => null
                } // Ensure it doesn't think it's a table paste
            };

            // Temporarily set a global flag or pass index?
            // Let's just manually trigger the paste logic but with our index
            handleGlobalPaste(mockEvent, idx);

        } catch (err) {
            console.error(err);
            Swal.fire('Quyền truy cập',
                'Vui lòng cho phép trình duyệt truy cập bộ nhớ tạm (Clipboard) để dán nội dung.', 'info');
        }
    };

    /**
     * Decorates variable badges with visual icons based on their data type
     */
    function decorateContent(html) {
        if (!html) return '';
        const div = document.createElement('div');
        div.innerHTML = html;
        const badges = div.querySelectorAll('.ebmr-field-badge');

        badges.forEach(badge => {
            const fieldId = badge.getAttribute('data-field-id');
            const field = fieldsConfig[fieldId];
            if (field) {
                if (window.isExecutionMode) {
                    if (field.type === 'formula') {
                        const result = calculateFormula(field.formula || '');
                        badge.innerHTML = result;
                        badge.className = 'ebmr-field-value formula-result';
                        return;
                    }

                    const val = window.executionValues[fieldId] || '';
                    if (val) {
                        badge.innerHTML = val;
                        badge.className = 'ebmr-field-value';
                    } else {
                        badge.innerHTML = `[${field.label}]`;
                    }
                } else {
                    let icon = 'fa-edit';
                    let typeLabel = '';
                    let extra = '';

                    if (field.type === 'signature') {
                        icon = 'fa-signature';
                        typeLabel = 'Chữ ký';
                    } else if (field.type === 'date') {
                        icon = 'fa-calendar-alt';
                        typeLabel = 'Ngày';
                    } else if (field.type === 'checkbox') {
                        icon = 'fa-check-square';
                        typeLabel = 'Tick';
                    } else if (field.type === 'number') {
                        icon = 'fa-calculator';
                        typeLabel = 'Số';
                    } else if (field.type === 'formula') {
                        icon = 'fa-square-root-alt';
                        typeLabel = 'Công thức';
                        const testResult = calculateFormula(field.formula || '');
                        extra = `<span class="ms-1 border-start ps-1 text-primary">${testResult}</span>`;
                    } else if (field.type === 'select') {
                        icon = 'fa-list-ul';
                        typeLabel = 'Chọn';
                    } else {
                        typeLabel = 'Text';
                    }

                    const label = field.label || `[${typeLabel}]`;
                    badge.className =
                        `ebmr-field-badge ${selectedFieldId === fieldId ? 'active' : ''} ${field.type === 'formula' ? 'formula-preview' : ''}`;
                    badge.innerHTML = `<i class="fas ${icon}"></i> ${label}${extra}`;
                }
            }
        });
        return div.innerHTML;
    }

    window.calculateFormula = function(formula) {
        if (!formula) return '0';

        const valMap = {};

        // 1. Build value map from all table cells with IDs
        items.forEach(item => {
            if (item.type === 'table' && item.data) {
                item.data.forEach(row => {
                    row.forEach(cell => {
                        if (cell && cell.cellId) {
                            const raw = (cell.defaultValue !== undefined && cell
                                .defaultValue !== '') ? cell.defaultValue : (cell
                                .content || '0');
                            const clean = typeof raw === 'string' ? raw.replace(/<[^>]*>/g,
                                '').trim() : raw;
                            valMap[cell.cellId] = parseFloat(clean) || 0;
                        }
                    });
                });
            }
        });

        // 2. Build value map from all Dynamic Fields (by Label or Name)
        Object.values(fieldsConfig).forEach(field => {
            if (field.label || field.name) {
                const val = (field.defaultValue !== undefined && field.defaultValue !== '') ? field
                    .defaultValue : 0;
                if (field.label) valMap[field.label] = parseFloat(val) || 0;
                if (field.name) valMap[field.name] = parseFloat(val) || 0;
            }
        });

        // 3. Replace IDs in formula: (1) -> valMap['1']
        let processed = formula.replace(/\(([^)]+)\)/g, (match, id) => {
            const trimmedId = id.trim();
            return valMap[trimmedId] !== undefined ? valMap[trimmedId] : 0;
        });

        // 4. Evaluate basic math
        try {
            const result = new Function(`return ${processed}`)();
            return (typeof result === 'number') ? result.toLocaleString('en-US', {
                maximumFractionDigits: 2
            }) : result;
        } catch (e) {
            return '#ERR';
        }
    };

    window.recalculateAllFormulas = function() {
        // Find all formula result elements in the DOM and update them
        document.querySelectorAll('.formula-result').forEach(el => {
            const blockItem = el.closest('.block-item');
            if (blockItem) {
                // If it's inside a static text, we might need to re-render or just update the innerHTML
                // For performance, let's just find the parent badge and update
                const badge = el.closest('.ebmr-field-badge') || el;
                const fieldId = badge.getAttribute('data-field-id');
                const field = fieldsConfig[fieldId];
                if (field && field.type === 'formula') {
                    badge.innerHTML = calculateFormula(field.formula || '');
                }
            }
        });
    };
</script>
