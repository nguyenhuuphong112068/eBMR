<style>
    /* Nút hành động nhanh cho Bảng (Google Docs style) */
    .table-header-cell,
    .mini-table td {
        position: relative;
    }

    .cell-wrapper {
        min-height: 1.5em;
        /* Đảm bảo dòng rỗng có chiều cao bằng dòng có 1 chữ */
        padding: 2px;
    }

    .col-actions,
    .row-actions {
        position: absolute;
        display: none;
        background: rgba(255, 255, 255, 0.95);
        border: 1px solid #0d6efd;
        border-radius: 4px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        z-index: 1050;
        padding: 2px;
    }

    /* Chỉnh lại vị trí để không bị cắt bởi wrapper có overflow:hidden */
    .col-actions {
        top: 5px;
        right: 5px;
        flex-direction: row;
    }

    .row-actions {
        top: 50%;
        left: 5px;
        transform: translateY(-50%);
        flex-direction: row;
    }

    .table-header-cell:hover .col-actions,
    .mini-table td:hover .row-actions {
        display: flex;
    }

    .btn-col-add,
    .btn-col-del,
    .btn-row-add,
    .btn-row-del {
        border: none;
        background: transparent;
        color: #6c757d;
        padding: 2px 5px;
        font-size: 10px;
        cursor: pointer;
        transition: all 0.2s;
    }

    .btn-col-add:hover,
    .btn-row-add:hover {
        color: #0d6efd;
        background: #e7f1ff;
    }

    .btn-col-del:hover,
    .btn-row-del:hover {
        color: #dc3545;
        background: #fff5f5;
    }

    /* Border Modes */
    .border-mode-none,
    .border-mode-none th,
    .border-mode-none td {
        border: 1px solid transparent;
    }

    .border-mode-none:hover {
        border: 1px dashed #ccc;
    }

    .border-mode-all th,
    .border-mode-all td {
        border: var(--table-border-width, 1px) solid var(--table-border-color, #dee2e6);
    }

    .border-mode-outer {
        border: var(--table-border-width, 1px) solid var(--table-border-color, #dee2e6);
    }

    .border-mode-outer th,
    .border-mode-outer td {
        border: none;
    }

    .border-mode-rows th,
    .border-mode-rows td {
        border: none;
        border-bottom: var(--table-border-width, 1px) solid var(--table-border-color, #dee2e6);
    }

    .border-mode-rows tr:last-child td {
        border-bottom: none;
    }

    .border-mode-cols th,
    .border-mode-cols td {
        border: none;
        border-right: var(--table-border-width, 1px) solid var(--table-border-color, #dee2e6);
    }

    .border-mode-cols tr td:last-child,
    .border-mode-cols tr th:last-child {
        border-right: none;
    }

    .mini-table.border-mode-none tr:hover td {
        border: 1px dashed #eee !important;
    }

    /* Active state for border selector */
    .border-selector-grid .btn.active {
        background-color: #e7f1ff !important;
        color: #0d6efd !important;
        border-color: #0d6efd !important;
        box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
    }

    @media print {

        .col-actions,
        .row-actions,
        .btn-row-add,
        .btn-row-del,
        .btn-col-add,
        .btn-col-del {
            display: none !important;
        }
    }
</style>
<script>
    /**
     * Hàm chính để vẽ toàn bộ giao diện dựa trên mảng dữ liệu `items`.
     * Nó dọn dẹp nội dung cũ và tạo mới các phần tử HTML tương ứng với từng loại khối (bảng, văn bản, phân đoạn...).
     */
    function renderBlocks() {
        const container = document.getElementById('editor-content');
        const hint = document.getElementById('drop-hint');
        if (!container) return;

        // Reset trạng thái chọn nếu đang ở chế độ thực thi (điền dữ liệu)
        if (window.isExecutionMode) {
            selectedId = null;
        }

        // Xóa sạch nội dung cũ trước khi vẽ lại
        container.innerHTML = '';

        // Xử lý gợi ý kéo thả (chỉ hiện ở chế độ thiết kế)
        if (hint) {
            if (!window.isExecutionMode) container.appendChild(hint);
            else hint.remove();
        }

        let lastSectionId = null;
        let currentGroup = container;
        let activeSectionIdTracker = window.activeSectionId || null;

        // Duyệt qua từng phần tử trong mảng items để tạo HTML tương ứng
        items.forEach((item, idx) => {
                // Xác định phân đoạn (section) cho item hiện tại nếu nó chưa có hoặc là khối section
                if (item.type === 'section') {
                    activeSectionIdTracker = item.id;
                }
                
                // Chỉ tự động gán section_id nếu item chưa có section_id
                if (!item.section_id) {
                    item.section_id = activeSectionIdTracker || 'section_0';
                }
                
                const itemSectionId = item.section_id;

                const isHeader = item.isGfHeader || item.isBmrHeader || (item.type === 'section' && item.locked);

                // Logic lọc phân đoạn: Nếu đang xem 1 phân đoạn cụ thể, chỉ hiện blocks của phân đoạn đó HOẶC các khối Header
                if (!window.isViewAllMode && window.activeSectionId) {
                    if (itemSectionId !== window.activeSectionId && !isHeader) {
                        return; // Bỏ qua nếu không đúng phân đoạn và không phải header
                    }
                }

                // Bỏ qua các khối chữ ký cũ (đang dần thay thế bằng badge chữ ký linh hoạt hơn)
                if (item.type === 'signature') return;

                // Logic phân trang và gom nhóm theo phân đoạn (Section)
                if (window.isViewAllMode || !window.activeSectionId) {
                    if (lastSectionId === null || itemSectionId !== lastSectionId) {
                        // Tạo đường phân cách trang nếu không phải phân đoạn đầu tiên
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

                        // Tạo bao vùng (Wrapper) cho một nhóm các khối thuộc cùng một phân đoạn
                        currentGroup = document.createElement('div');
                        currentGroup.className = 'section-group-wrapper' + (window.activeSectionId ===
                            itemSectionId ? ' active' : '');
                        currentGroup.onclick = (e) => {
                            // Kích hoạt phân đoạn khi click vào vùng nền của nhóm (không click vào block con)
                            if (!e.target.closest('.block-item')) {
                                window.activeSectionId = itemSectionId;
                                selectedId = null; 
                                renderBlocks();
                            }
                        };

                        // D-CLICK: Nếu đang ở "Xem tất cả", d-click vào vùng trắng sẽ tự chuyển sang "Xem 1 section"
                        currentGroup.ondblclick = (e) => {
                            // Nếu click vào vùng trắng (không phải block)
                            if (!e.target.closest('.block-item') && window.isViewAllMode) {
                                window.activeSectionId = itemSectionId;
                                window.isViewAllMode = false; // Chuyển sang chế độ xem 1 section
                                selectedId = null;
                                renderBlocks();
                                
                                // Cập nhật icon trên nút toggle view (nếu có)
                                const toggleBtn = document.getElementById('viewModeToggle');
                                if (toggleBtn) {
                                    toggleBtn.innerHTML = '<i class="fas fa-expand-arrows-alt"></i>';
                                    toggleBtn.classList.remove('btn-info');
                                    toggleBtn.classList.add('btn-outline-info');
                                    toggleBtn.title = "Chuyển sang xem tất cả";
                                }
                            }
                        };
                        container.appendChild(currentGroup);

                        lastSectionId = itemSectionId;
                    }
                } else {
                    // Nếu đang lọc xem một phân đoạn cụ thể, không cần Wrapper nhóm
                    currentGroup = container;
                }

                // Thêm vạch chèn (dấu cộng) giữa các khối ở chế độ chỉnh sửa
                if (!window.isReadOnly && !window.isExecutionMode) {
                    addInsertionDivider(currentGroup, idx);
                }

                // Tạo thẻ bao ngoài cho khối (block-item)
                const div = document.createElement('div');
                div.className =
                    `block-item type-${item.type} ${selectedId === item.id ? 'active' : ''} ${window.isExecutionMode ? 'execution-mode' : ''}`;
                div.setAttribute('data-id', item.id);

                // Áp dụng các style tùy chỉnh (lề, màu nền) từ dữ liệu
                if (item.marginLeft) div.style.marginLeft = item.marginLeft;
                if (item.marginRight) div.style.marginRight = item.marginRight;
                if (item.backgroundColor) div.style.backgroundColor = item.backgroundColor;

                // Xử lý sự kiện click để chọn khối (chỉ ở chế độ thiết kế)
                if (!window.isExecutionMode) {
                    div.onclick = (e) => {
                        e.stopPropagation();
                        if (selectedId !== item.id) {
                            // Chọn item và vẽ lại thanh thuộc tính
                            selectItem(item.id, true);
                        }
                    };
                }

                let content = `<div class="block-mock"></div>`;

                // --- XỬ LÝ KHỐI BẢNG (TABLE) ---
                if (item.type === 'table') {
                    const borderMode = item.borderMode || 'all';
                    const borderClass = `border-mode-${borderMode}`;

                    let thead = '';
                    if (!item.hideHeader && item.columns) {
                        thead = `<thead><tr>${item.columns.map((c, cIdx) => {
                        const s = c.style || {};
                        const bg = s.backgroundColor || '';
                        const align = s.textAlign || '';
                        const fw = s.fontWeight || '';
                        const fs = s.fontStyle || '';
                        const td = s.textDecoration || '';
                        const fsz = s.fontSize || '';
                        const tc = s.textColor || '';
                        return `<th contenteditable="false"
                        spellcheck="false"
                        data-row="0"
                        data-col="${cIdx}"
                        class="table-header-cell"
                        style="width: ${c.width || 'auto'}; background-color: ${bg}; text-align: ${align}; font-weight: ${fw}; font-style: ${fs}; text-decoration: ${td}; font-size: ${fsz}; color: ${tc}; border-top: ${s.borderTop || ''}; border-bottom: ${s.borderBottom || ''}; border-left: ${s.borderLeft || ''}; border-right: ${s.borderRight || ''};">
                            <div class="header-content">${c.label}</div>
                            ${!window.isExecutionMode ? `
                            <div class="col-actions">
                                <button class="btn-col-add" onclick="event.stopPropagation(); tableAddColumn(${cIdx+1})" title="Thêm cột bên phải"><i class="fas fa-plus"></i></button>
                                <button class="btn-col-del" onclick="event.stopPropagation(); tableRemoveColumn(${cIdx})" title="Xóa cột"><i class="fas fa-times"></i></button>
                            </div>` : ''}
                        </th>`;
                    }).join('')}
                    ${window.isExecutionMode && item.canAddRows ? '<th style="width: 30px; border: none; background: transparent;"></th>' : ''}
                    </tr></thead>`;
                }

                let rowsHtml = '';
                const blockKey = item.uuid || item.id;
                const runDataForBlock = window.executionValues[blockKey] || {};

                for (let r = 0; r < (item.rows || 0); r++) {
                    let cellsHtml = '';
                    const rowH = (item.rowHeights && item.rowHeights[r]) ? item.rowHeights[r] : 'auto';
                    if (!item.data || !item.data[r]) continue;
                    for (let c = 0; c < (item.cols || 0); c++) {
                        if (!item.data[r][c] || typeof item.data[r][c] !== 'object') {
                            item.data[r][c] = { content: '', rs: 1, cs: 1, hidden: false };
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

                        cellsHtml += `<td contenteditable="${finalEditable}"
                        spellcheck="false"
                        data-row="${r+1}"
                        data-col="${c}"
                        rowspan="${cell.rs || 1}"
                        colspan="${cell.cs || 1}"
                        ${onclickAttr}
                        class="${cellClass} ${item.locked ? 'locked-cell' : ''}"
                        style="width: ${cellWidth}; height: ${rowH}; background-color: ${cellBg}; text-align: ${cell.textAlign || ''}; font-weight: ${cell.fontWeight || ''}; font-style: ${cell.fontStyle || ''}; text-decoration: ${cell.textDecoration || ''}; font-size: ${cell.fontSize || ''}; color: ${cell.textColor || ''}; text-transform: ${cell.textTransform || ''}; border-top: ${cell.borderTop || ''}; border-bottom: ${cell.borderBottom || ''}; border-left: ${cell.borderLeft || ''}; border-right: ${cell.borderRight || ''};"
                        oninput="updateTableInline('${item.id}', 'cell', ${r}, ${c}, this.innerHTML)">
                            <div class="cell-wrapper">${displayContent}</div>
                            ${!window.isExecutionMode && c === 0 ? `
                            <div class="row-actions">
                                <button class="btn-row-add" onclick="event.stopPropagation(); tableAddRow(${r+1})" title="Thêm dòng bên dưới"><i class="fas fa-plus"></i></button>
                                <button class="btn-row-del" onclick="event.stopPropagation(); tableRemoveRow(${r})" title="Xóa dòng"><i class="fas fa-times"></i></button>
                            </div>` : ''}
                        </td>`;
        }

        let deleteCell = '';
        if (window.isExecutionMode && item.canAddRows) {
            const isDynamicRow = item.data[r] && item.data[r][0] && item.data[r][0].is_dynamic;
            if (isDynamicRow) {
                deleteCell = `<td class="execution-delete-cell" style="width: 30px; border: none; background: transparent; vertical-align: middle;">
                                                        <button class="btn btn-link text-danger p-0" title="Xóa dòng" onclick="executeDeleteTableRow('${item.id}', ${r})">
                                                            <i class="fas fa-times-circle"></i>
                                                        </button>
                                                      </td>`;
            } else {
                deleteCell = `<td style="width: 30px; border: none; background: transparent;"></td>`;
            }
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

    content =
        `<div class="table-responsive-wrapper"><table class="mini-table ${borderClass}" style="--table-border-width: ${item.borderWeight || '1px'}; --table-border-color: #dee2e6;">${thead}<tbody>${rowsHtml}</tbody></table></div>${addRowBtn}`;
    }

    // --- XỬ LÝ KHỐI VĂN BẢN TĨNH (STATIC TEXT) ---
    else if (item.type === 'static-text') {
        const displayContent = decorateContent(item.content || '');
        const textEditable = (window.isReadOnly || window.isExecutionMode) ? 'false' : 'true';
        const borderClass = item.borderMode === 'dashed' ? 'border-dashed' : (item.borderMode === 'visible' ?
            'border-visible' : 'border-none');

        content =
            `<div class="static-text-display ${borderClass}" contenteditable="${textEditable}" spellcheck="false" 
                                            oninput="updateStaticTextInline('${item.id}', this.innerHTML); handleAutoCapitalize(this)">${displayContent}</div>`;
    }

    // --- XỬ LÝ KHỐI BIỂU MẪU NHÚNG (LINKED TEMPLATE) ---
    else if (item.type === 'linked-template') {
        // Tự động mở xem trước nếu đang ở chế độ thực thi hoặc người dùng đã chọn mở
        const isPreviewing = window.isExecutionMode || item.showPreview || false;

        // Ẩn các nút điều khiển nội bộ nếu ở chế độ thực thi
        const hideControls = window.isExecutionMode;

        // Vùng chứa nội dung xem trước của biểu mẫu nhúng
        const previewContent = isPreviewing ? `<div id="preview-${item.id}" class="mt-3 p-4 border rounded bg-white w-100 shadow-sm" style="${window.isExecutionMode ? '' : 'pointer-events: none; opacity: 0.9;'}">
                                <div class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary"></div></div>
                            </div>` : '';

        content = `<div class="block-mock d-flex flex-column align-items-center justify-content-center py-4 px-3 position-relative" style="background-color: #f8f9fa; border: 2px dashed #0d6efd; border-radius: 12px; min-height: 120px;">
                                         ${!hideControls ? `<div class="position-absolute" style="top: 10px; right: 10px; z-index: 100;">
                                            <button class="btn btn-sm btn-primary shadow-sm px-3" onclick="event.stopPropagation(); toggleGfPreview('${item.id}')" style="border-radius: 20px;">
                                                <i class="fas ${isPreviewing ? 'fa-eye-slash' : 'fa-eye'} me-1"></i> ${isPreviewing ? 'Ẩn nội dung' : 'Xem nội dung'}
                                            </button>
                                         </div>` : ''}
                                         <i class="fas fa-link fa-2x text-primary mb-2 ${isPreviewing ? 'd-none' : ''}"></i>
                                         <div class="fw-bold text-navy ${isPreviewing ? 'mb-2 border-bottom pb-2 w-100' : ''}">Biểu mẫu chung: ${item.label || 'Đang tải...'}</div>
                                         ${!isPreviewing ? `<div class="small text-muted mt-1">Nội dung sẽ được tự động chèn vào khi ban hành/thực thi</div>` : ''}
                                         ${previewContent}
                                       </div>`;

        // Nếu đang mở xem trước, gọi hàm fetch dữ liệu từ server
        if (isPreviewing) {
            setTimeout(() => fetchAndRenderGfPreview(item.id, item.template_id), 50);
        }
    }

    // --- XỬ LÝ KHỐI PHÂN ĐOẠN (SECTION) ---
    else if (item.type === 'section') {
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
    }

    // --- XỬ LÝ KHỐI BIỂU ĐỒ (CHART) ---
    else if (item.type === 'chart') {
        const canvasId = 'chart_canvas_' + item.id;
        content = `<div class="chart-container" style="position: relative; height:300px; width:100%; padding: 15px; background: #fff; border: 1px solid #eee; border-radius: 8px;">
                                         <canvas id="${canvasId}"></canvas>
                                       </div>`;
        // Đăng ký khởi tạo Chart.js sau khi DOM đã sẵn sàng
        setTimeout(() => {
            if (typeof renderChart === 'function') {
                renderChart(canvasId, item.chartConfig);
            }
        }, 50);
    }

    // --- THANH HÀNH ĐỘNG (XÓA, DI CHUYỂN) ---
    const actions = (item.locked || window.isReadOnly || window.isExecutionMode) ? '' : `
                            <div class="block-actions">
                                <button class="btn btn-sm btn-light border shadow-sm text-danger" onclick="removeItem('${item.id}')"><i class="fas fa-trash"></i></button>
                                <button class="btn btn-sm btn-light border shadow-sm" onclick="moveItem(${idx}, -1)"><i class="fas fa-chevron-up"></i></button>
                                <button class="btn btn-sm btn-light border shadow-sm" onclick="moveItem(${idx}, 1)"><i class="fas fa-chevron-down"></i></button>
                            </div>`;

    // Tổng hợp HTML của khối
    div.innerHTML = `
                            ${actions}
                            ${item.type !== 'static-text' && item.type !== 'section' && !window.isExecutionMode && item.label && item.label !== 'null' && !item.isGfHeader && !item.isBmrHeader ? `<span class="block-label">${item.label} ${item.locked ? '<i class="fas fa-lock ms-1 small"></i>' : ''}</span>` : ''}
                            ${content}
                        `;
    currentGroup.appendChild(div);
    });

    if (!window.isReadOnly && !window.isExecutionMode) {
        if (window.isViewAllMode || !window.activeSectionId) {
            // Chế độ xem tất cả: Thêm dấu cộng ở cuối cùng hồ sơ
            addInsertionDivider(currentGroup, items.length);
        } else {
            // Chế độ xem 1 phân đoạn: Tìm index cuối cùng của phân đoạn này để chèn vào cuối phân đoạn
            let lastIdxInSection = -1;
            for (let i = items.length - 1; i >= 0; i--) {
                if (items[i].section_id === window.activeSectionId || items[i].id === window.activeSectionId) {
                    lastIdxInSection = i;
                    break;
                }
            }
            if (lastIdxInSection !== -1) {
                addInsertionDivider(currentGroup, lastIdxInSection + 1);
            }
        }
    }

    // Rebuild Outline when DOM changes
    if (typeof buildOutline === 'function') {
        setTimeout(buildOutline, 100);
    }

    // Load dynamic options for any master data select fields
    if (window.isExecutionMode) {
        setTimeout(loadDynamicSelectOptions, 100);
    }
    }

    window.dynamicOptionsCache = {};

    /**
     * Fetch and populate options for 'select' fields that use a 'database' data source.
     */
    async function loadDynamicSelectOptions() {
        const selects = document.querySelectorAll('.dynamic-select:not(.loaded)');
        if (selects.length === 0) return;

        for (let sel of selects) {
            sel.classList.add('loaded'); // prevent duplicate fetches
            const table = sel.getAttribute('data-table');
            const labelCol = sel.getAttribute('data-label');
            const valueCol = sel.getAttribute('data-value');
            const whereClause = sel.getAttribute('data-where');
            const selectedVal = sel.getAttribute('data-selected');

            if (!table || !labelCol) {
                sel.innerHTML = '<option value="">-- Cấu hình lỗi --</option>';
                continue;
            }

            const cacheKey = `${table}_${labelCol}_${valueCol}_${whereClause}`;
            let optionsData = window.dynamicOptionsCache[cacheKey];

            if (!optionsData) {
                try {
                    const response = await fetch("{{ route('pages.ebmr.dynamicOptions') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ||
                                '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            table,
                            labelCol,
                            valueCol,
                            where: whereClause
                        })
                    });

                    const res = await response.json();
                    if (res.success) {
                        optionsData = res.options;
                        window.dynamicOptionsCache[cacheKey] = optionsData;
                    } else {
                        sel.innerHTML = `<option value="">-- Lỗi: ${res.message} --</option>`;
                        continue;
                    }
                } catch (e) {
                    sel.innerHTML = '<option value="">-- Lỗi kết nối --</option>';
                    continue;
                }
            }

            if (optionsData) {
                let html = '<option value="">-- Chọn --</option>';
                optionsData.forEach(opt => {
                    html +=
                        `<option value="${opt.value}" ${selectedVal === String(opt.value) ? 'selected' : ''}>${opt.label}</option>`;
                });
                sel.innerHTML = html;
            }
        }
    }

    /**
     * Thêm thanh công cụ chèn nhanh (dấu cộng) giữa các khối.
     * @param {HTMLElement} container - Vùng chứa để chèn thanh công cụ.
     * @param {number} idx - Vị trí chèn trong mảng items.
     */
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
                <button onclick="openLinkGfModal(${idx})"><i class="fas fa-link me-2" style="width: 15px;"></i> BM Chung</button>
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

    /**
     * Mở bộ chọn để thêm bảng vào vị trí cụ thể (mặc định thêm bảng 3x2).
     */
    function showTableSelectorAt(idx, btn) {
        addTable(3, 2, idx);
    }

    /**
     * Chèn nhanh một khối văn bản khi người dùng click đúp vào vùng trống giữa các khối.
     */
    window.quickAddText = function(e, idx) {
        e.stopPropagation();
        // Calculate sectionId for quick add
        let sectionId = null;
        if (idx > 0 && items[idx - 1]) {
            sectionId = items[idx - 1].section_id || items[idx - 1].id;
        } else if (items.length > 0 && items[idx]) {
            sectionId = items[idx].section_id || items[idx].id;
        }
        if (!sectionId) sectionId = window.activeSectionId;

        items.splice(idx, 0, {
            id: newItemId,
            type: 'static-text',
            section_id: sectionId,
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
    /**
     * Dán nội dung từ bộ nhớ tạm (Clipboard) vào một vị trí cụ thể trong tài liệu.
     */
    window.pasteAt = async function(idx) {
        try {
            // Đọc dữ liệu từ Clipboard
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

            // Tạo đối tượng sự kiện giả lập để tái sử dụng hàm handleGlobalPaste hiện có
            const mockEvent = {
                clipboardData: {
                    getData: (type) => (type === 'text/html' ? htmlData : plainText)
                },
                preventDefault: () => {},
                target: {
                    closest: () => null
                }
            };

            // Gọi logic xử lý dán dữ liệu tại vị trí idx đã chọn
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
    /**
     * Xử lý nội dung HTML để hiển thị các "Thẻ biến số" (Variable Badges).
     * Hàm này sẽ thay thế các thẻ thô bằng giao diện có icon và màu sắc tùy theo loại dữ liệu (Ngày, Số, Chữ ký...).
     * @param {string} html - Nội dung HTML gốc chứa thẻ biến.
     */
    function decorateContent(html, customConfig = null) {
        if (!html) return '';
        const div = document.createElement('div');
        div.innerHTML = html;
        const badges = div.querySelectorAll('.ebmr-field-badge');
        
        // Use customConfig if provided (for linked templates), otherwise use global fieldsConfig
        const config = customConfig || fieldsConfig;

        badges.forEach(badge => {
            const fieldId = badge.getAttribute('data-field-id');
            const field = config[fieldId];
            if (field) {
                // TRƯỜNG HỢP 1: Chế độ THỰC THI (Cho phép nhập thử để xem kết quả)
                if (window.isExecutionMode) {
                    // Nếu là thẻ công thức, thực hiện tính toán ngay
                    if (field.type === 'formula') {
                        const dPlaces = (field.validation && field.validation.decimal_places !== null) ? field
                            .validation.decimal_places : 2;
                        const result = calculateFormula(field.formula || '', dPlaces);
                        badge.innerHTML = result;
                        badge.className = 'ebmr-field-value formula-result';
                        badge.setAttribute('data-field-id', fieldId); // Để recalculate tìm được
                        return;
                    }

                    let val = window.executionValues[fieldId] || '';
                    // Handle nested structure from DB (cell_id = 'default')
                    if (val && typeof val === 'object' && val.hasOwnProperty('default')) {
                        val = val.default;
                    } else if (val && typeof val === 'object' && !Array.isArray(val)) {
                        // Fallback: if it's an object, try to get the first property value
                        const keys = Object.keys(val);
                        if (keys.length > 0) val = val[keys[0]];
                    }

                    badge.setAttribute('data-field-id', fieldId);

                    if (field.type === 'signature') {
                        badge.innerHTML =
                            `<span class="badge bg-light text-primary border" style="cursor: pointer;" onclick="Swal.fire('Chế độ chạy thử', 'Đây là mô phỏng chữ ký', 'info')"><i class="fas fa-signature me-1"></i> Ký tên</span>`;
                        badge.className = 'ebmr-field-badge ebmr-field-value';
                    } else if (field.type === 'checkbox') {
                        badge.innerHTML =
                            `<input type="checkbox" ${val ? 'checked' : ''} onchange="window.executionValues['${fieldId}'] = this.checked; if(typeof window.recalculateAllFormulas === 'function') window.recalculateAllFormulas()">`;
                        badge.className = 'ebmr-field-badge ebmr-field-value';
                    } else if (field.type === 'select') {
                        const dsType = field.dataSource ? field.dataSource.type : 'manual';
                        if (dsType === 'database') {
                            const ds = field.dataSource;
                            badge.innerHTML =
                                `<select class="form-select-sm border-0 border-bottom bg-transparent dynamic-select" data-field-id="${fieldId}" data-table="${ds.table || ''}" data-label="${ds.labelCol || ''}" data-value="${ds.valueCol || ''}" data-where="${ds.where || ''}" data-selected="${val}" onchange="window.executionValues['${fieldId}'] = this.value; if(typeof window.recalculateAllFormulas === 'function') window.recalculateAllFormulas()"><option value="">-- Đang tải... --</option></select>`;
                        } else {
                            let optionsArr = [];
                            if (Array.isArray(field.options)) {
                                optionsArr = field.options;
                            } else if (typeof field.options === 'string') {
                                optionsArr = field.options.split(/[,;\n]/).map(o => o.trim()).filter(o => o);
                            }
                            badge.innerHTML =
                                `<select class="form-select-sm border-0 border-bottom bg-transparent" onchange="window.executionValues['${fieldId}'] = this.value; if(typeof window.recalculateAllFormulas === 'function') window.recalculateAllFormulas()"><option value="">--</option>${optionsArr.map(o => `<option value="${o}" ${val === o ? 'selected' : ''}>${o}</option>`).join('')}</select>`;
                        }
                        badge.className = 'ebmr-field-badge ebmr-field-value';
                    } else {
                        // Các loại khác: Văn bản, Số, Ngày
                        const placeholder = field.label || '...';
                        const displayVal = val || '';
                        badge.innerHTML =
                            `<span class="execution-input-test" onclick="openVariableInputModal('${fieldId}')" style="cursor: pointer; border-bottom: 1px dotted #1a73e8; min-width: 30px; display: inline-block; outline: none; position: relative;">${displayVal || `<span style="color: #6c757d; font-style: italic;">${placeholder}</span>`}</span>`;
                        badge.className = 'ebmr-field-badge ebmr-field-value';
                    }
                }
                // TRƯỜNG HỢP 2: Chế độ THIẾT KẾ (Hiển thị badge kèm icon loại dữ liệu)
                else {
                    let icon = 'fa-edit';
                    let typeLabel = '';
                    let extra = '';

                    // Xác định Icon dựa theo loại trường (field type)
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
                        const dPlaces = (field.validation && field.validation.decimal_places !== null) ? field
                            .validation.decimal_places : 2;
                        const testResult = calculateFormula(field.formula || '', dPlaces);

                        // Resolve IDs to Labels for display
                        const formulaDisplay = (field.formula || '').replace(/\(([^)]+)\)/g, (match, id) => {
                            const targetField = Object.values(fieldsConfig).find(f => f.name === id || f
                                .label === id);
                            return targetField ? (targetField.label || id) : id;
                        });

                        extra =
                            `<span class="ms-1 border-start ps-1 text-primary">${testResult}</span>${formulaDisplay ? `<span class="ms-2 small text-muted" style="font-size: 0.8em; font-style: italic;">(${formulaDisplay})</span>` : ''}`;
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

    /**
     * Tính toán giá trị của một công thức toán học.
     * Nó tự động tìm kiếm các giá trị từ các ô có ID hoặc các biến số được định nghĩa trong công thức.
     * @param {string} formula - Chuỗi công thức, ví dụ: "(kl_tong) - (kl_bao)".
     */
    window.calculateFormula = function(formula, decimalPlaces = 2) {
        if (!formula) return '0';

        const valMap = {};
        const dPlaces = (decimalPlaces !== null && decimalPlaces !== '') ? parseInt(decimalPlaces) : 2;

        // BƯỚC 1: Thu thập giá trị từ các ô bảng (Table Cells) có đặt ID (cellId)
        items.forEach(item => {
            if (item.type === 'table' && item.data) {
                item.data.forEach(row => {
                    row.forEach(cell => {
                        if (cell && cell.cellId) {
                            // Ưu tiên giá trị mặc định nếu có, nếu không lấy nội dung ô
                            const raw = (cell.defaultValue !== undefined && cell
                                .defaultValue !== '') ? cell.defaultValue : (cell
                                .content || '0');
                            // Làm sạch HTML và chuyển đổi sang số
                            const clean = typeof raw === 'string' ? raw.replace(/<[^>]*>/g,
                                '').trim() : raw;
                            valMap[cell.cellId] = parseFloat(clean) || 0;
                        }
                    });
                });
            }
        });

        // BƯỚC 2: Thu thập giá trị từ các "Trường động" (Dynamic Fields) theo Tên hoặc Nhãn
        Object.values(fieldsConfig).forEach(field => {
            if (field.label || field.name) {
                let val = 0;
                if (window.isExecutionMode && window.executionValues && window.executionValues[field.id] !== undefined) {
                    let raw = window.executionValues[field.id];
                    if (raw && typeof raw === 'object' && raw.hasOwnProperty('default')) {
                        val = raw.default;
                    } else if (raw && typeof raw === 'object' && !Array.isArray(raw)) {
                        const keys = Object.keys(raw);
                        if (keys.length > 0) val = raw[keys[0]];
                    } else {
                        val = raw;
                    }
                } else {
                    val = (field.defaultValue !== undefined && field.defaultValue !== '') ? field.defaultValue : 0;
                }
                if (field.label) valMap[field.label] = parseFloat(val) || 0;
                if (field.name) valMap[field.name] = parseFloat(val) || 0;
            }
        });

        // BƯỚC 3: Thay thế các định danh trong công thức bằng giá trị thực tế
        // Ví dụ: "(kl_tong) - (kl_bao)" -> "100 - 5"
        let processed = formula.replace(/\(([^)]+)\)/g, (match, id) => {
            const trimmedId = id.trim();
            return valMap[trimmedId] !== undefined ? valMap[trimmedId] : 0;
        });

        // BƯỚC 4: Tính toán biểu thức toán học cơ bản bằng hàm Function (an toàn hơn eval một chút)
        try {
            const result = new Function(`return ${processed}`)();
            // Định dạng kết quả hiển thị (phân tách hàng nghìn, số chữ số thập phân theo thiết lập)
            return (typeof result === 'number') ? result.toLocaleString('en-US', {
                minimumFractionDigits: dPlaces,
                maximumFractionDigits: dPlaces
            }) : result;
        } catch (e) {
            return '#ERR'; // Trả về lỗi nếu công thức không hợp lệ
        }
    };

    /**
     * Quét toàn bộ tài liệu và cập nhật lại tất cả các kết quả công thức đang hiển thị.
     * Thường được gọi sau khi người dùng thay đổi một giá trị đầu vào nào đó.
     */
    window.recalculateAllFormulas = function() {
        // Find all formula result elements in the DOM and update them
        document.querySelectorAll('.formula-result').forEach(el => {
            const blockItem = el.closest('.block-item');
            if (blockItem) {
                const badge = el.closest('.ebmr-field-badge') || el;
                const fieldId = badge.getAttribute('data-field-id');
                const field = fieldsConfig[fieldId];
                if (field && field.type === 'formula') {
                    const dPlaces = (field.validation && field.validation.decimal_places !== null) ? field
                        .validation.decimal_places : 2;
                    badge.innerHTML = calculateFormula(field.formula || '', dPlaces);
                }
            }
        });
    };
</script>
