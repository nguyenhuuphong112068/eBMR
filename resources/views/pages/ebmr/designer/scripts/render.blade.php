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
        left: -32px;
        transform: translateY(-50%);
        flex-direction: column;
        padding: 4px;
        padding-right: 8px;
        /* Cầu nối hover để chuột không bị mất tiêu điểm */
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

    /* Section Loop Style */
    .section-loop-tabs-header {
        border-bottom: 2px solid #e2e8f0;
        padding-bottom: 8px;
    }

    .section-loop-tabs-header .btn {
        font-weight: 600;
        font-size: 0.8rem;
        border-radius: 6px;
        padding: 4px 12px;
        transition: all 0.2s ease;
    }

    .section-loop-tabs-header .btn.active {
        background-color: #0ea5e9;
        border-color: #0ea5e9;
        color: #ffffff;
        box-shadow: 0 2px 4px rgba(14, 165, 233, 0.25);
    }

    .section-loop-tab-content {
        animation: fadeInTab 0.3s ease-in-out;
    }

    @keyframes fadeInTab {
        from {
            opacity: 0;
            transform: translateY(4px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @media print {
        .section-loop-tabs-header {
            display: none !important;
        }

        .section-loop-tab-content {
            display: block !important;
            margin-bottom: 30px !important;
            page-break-after: always;
        }

        .section-loop-tab-content:last-child {
            page-break-after: avoid;
        }

        .section-loop-tab-content::before {
            content: "Lần thực hiện thứ " attr(data-loop-idx);
            display: block;
            font-size: 0.95rem;
            font-weight: bold;
            text-transform: uppercase;
            color: #1e293b;
            border-bottom: 1.5px dashed #cbd5e1;
            padding-bottom: 4px;
            margin-top: 20px;
            margin-bottom: 15px;
        }
    }

    /* Loop Group and Selection Highlighting in Designer */
    .selected-range-member {
        outline: 2px dashed #0284c7 !important;
        background-color: rgba(14, 165, 233, 0.04) !important;
        position: relative;
        box-shadow: 0 0 8px rgba(14, 165, 233, 0.2);
    }

    .selected-range-member::after {
        content: 'Đã chọn';
        position: absolute;
        top: 4px;
        right: 4px;
        background-color: #0284c7;
        color: #ffffff;
        font-size: 0.65rem;
        font-weight: bold;
        padding: 2px 6px;
        border-radius: 4px;
        z-index: 100;
        pointer-events: none;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .designer-loop-group-wrapper {
        transition: all 0.2s ease;
    }

    .designer-loop-group-wrapper:hover {
        border-color: #0ea5e9 !important;
        box-shadow: 0 4px 12px rgba(14, 165, 233, 0.08);
    }

    /* ========================================
       KHỐI NGẮT TRANG (PAGE BREAK)
    ======================================== */

    /* Wrapper block-item cho page-break: không có viền, padding thoáng */
    .block-item.type-page-break {
        background: transparent !important;
        border: none !important;
        box-shadow: none !important;
        padding: 4px 0 !important;
        margin: 4px 0 !important;
        cursor: default;
    }

    .page-break-block {
        position: relative;
        width: 100%;
        margin: 0;
        display: flex;
        flex-direction: column;
        align-items: stretch;
        user-select: none;
    }

    /* Dải màu nền rộng toàn bộ chiều ngang */
    .page-break-band {
        height: 8px;
        background: #f1f3f4 !important;
        margin-left: -23px !important;
        margin-right: -23px !important;
        margin-top: 50px !important;
        margin-bottom: 50px !important;
        border-top: 1px solid #ddd;
        border-bottom: 1px solid #ddd;
        box-shadow: inset 0 4px 6px -4px rgba(0, 0, 0, 0.1), inset 0 -4px 6px -4px rgba(0, 0, 0, 0.1);
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .execution-input-cell--sig:hover {
        background-color: #f0fdf4 !important;
        cursor: pointer;
    }

    /* N/A State Styling */
    .cell-na-state {
        position: relative;
    }

    .cell-na-state::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: repeating-linear-gradient(45deg, transparent, transparent 10px, rgba(0, 0, 0, 0.06) 10px, rgba(0, 0, 0, 0.06) 20px);
        pointer-events: none;
        z-index: 5;
    }

    .cell-na-state .cell-wrapper {
        opacity: 0.4;
        pointer-events: none !important;
    }

    .cell-na-state .ebmr-field-badge,
    .cell-na-state input,
    .cell-na-state select,
    .cell-na-state textarea,
    .cell-na-state .execution-badge {
        pointer-events: none !important;
    }

    .page-break-badge {
        background-color: #cbd5e1 !important;
        color: white !important;
        font-size: 0.65rem !important;
        font-weight: 800 !important;
        text-transform: uppercase;
        letter-spacing: 1px;
        padding: 4px 16px;
        border-radius: 50rem;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border: none;
        box-shadow: none;
    }

    .page-break-badge i {
        font-size: 0.7rem;
        color: white !important;
    }

    /* Khi in: bắt buộc ngắt trang thật, ẩn toàn bộ nội dung hiển thị */
    @media print {
        .type-page-break .page-break-block {
            display: none !important;
        }

        .type-page-break {
            page-break-after: always !important;
            break-after: page !important;
            margin: 0 !important;
            padding: 0 !important;
            height: 0 !important;
        }
    }
</style>
<script>
    /**
     * Hàm helper hiển thị chữ ký dưới dạng hình ảnh (nếu là base64) hoặc text (nếu là chữ viết thường).
     */
    function getSignatureDisplayHtml(runVal, type = 'signature') {
        if (!runVal) return '';
        let iconClass = 'fa-check-circle text-success';
        if (type === 'executor') iconClass = 'fa-user-check text-primary';
        if (type === 'checker') iconClass = 'fa-user-shield text-success';

        if (runVal.startsWith('data:image/')) {
            return `<div class="e-signature-done" style="display: inline-flex; align-items: center; justify-content: center; gap: 4px;">
                <img src="${runVal}" style="max-height: 45px; max-width: 130px; object-fit: contain; mix-blend-mode: multiply; vertical-align: middle;" />
            </div>`;
        }
        return `<div class="e-signature-done"><i class="fas ${iconClass} me-1"></i>${runVal}</div>`;
    }

    /**
     * Hàm helper vẽ từng block riêng lẻ (bảng, văn bản tĩnh, biểu đồ...)
     */
    function renderSingleBlock(item, idx, loopSuffix = '') {
        const blockKey = (item.uuid || item.id) + loopSuffix;
        const selectedRangeIds = (typeof window.getSelectedBlockRangeIds === 'function') ? window
            .getSelectedBlockRangeIds() : [];
        const isRangeMember = selectedRangeIds.includes(item.id);
        const div = document.createElement('div');
        div.className =
            `block-item type-${item.type} ${selectedId === item.id ? 'active' : ''} ${isRangeMember ? 'selected-range-member' : ''} ${window.isExecutionMode ? 'execution-mode' : ''}`;
        div.setAttribute('data-id', item.id);
        div.setAttribute('data-block-key', blockKey);

        if (item.marginLeft) div.style.marginLeft = item.marginLeft;
        if (item.marginRight) div.style.marginRight = item.marginRight;
        if (item.backgroundColor) div.style.backgroundColor = item.backgroundColor;

        if (!window.isExecutionMode) {
            div.onclick = (e) => {
                window.handleBlockClick(e, item);
            };
        }

        let content = `<div class="block-mock"></div>`;

        // --- XỬ LÝ KHỐI BẢNG (TABLE) ---
        if (item.type === 'table') {
            const borderMode = item.borderMode || 'all';
            const borderClass = `border-mode-${borderMode}`;

            let thead = '';
            if (!item.hideHeader && item.columns) {
                let colsHtml = item.columns.map((c, cIdx) => {
                    const s = c.style || {};
                    const bg = s.backgroundColor || '';
                    const align = s.textAlign || '';
                    const fw = s.fontWeight || '';
                    const fs = s.fontStyle || '';
                    const td = s.textDecoration || '';
                    const fsz = s.fontSize || '';
                    const tc = s.textColor || '';
                    const wm = s.writingMode || '';
                    const tf = s.transform || '';
                    
                    let st = 'position: relative; width: ' + (c.width || 'auto') + '; background-color: ' + bg + '; text-align: ' + align + '; font-weight: ' + fw + '; font-style: ' + fs + '; text-decoration: ' + td + '; font-size: ' + fsz + '; color: ' + tc + '; border-top: ' + (s.borderTop ? s.borderTop + ' !important' : '') + '; border-bottom: ' + (s.borderBottom ? s.borderBottom + ' !important' : '') + '; border-left: ' + (s.borderLeft ? s.borderLeft + ' !important' : '') + '; border-right: ' + (s.borderRight ? s.borderRight + ' !important' : '') + '; writing-mode: ' + wm + ';';
                    
                    let html = '<th contenteditable="false" spellcheck="false" data-row="0" data-col="' + cIdx + '" class="table-header-cell" style="' + st + '">';
                    html += '<div class="header-content" style="transform: ' + tf + '; transform-origin: center center; display: inline-block; width: 100%;">' + (c.label || '') + '</div>';
                    
                    if (!window.isExecutionMode) {
                        html += '<div class="col-actions">';
                        html += '<button class="btn-col-add" onclick="event.stopPropagation(); tableAddColumn(\'' + item.id + '\', ' + (cIdx+1) + ')" title="Thêm cột bên phải"><i class="fas fa-plus"></i></button>';
                        html += '<button class="btn-col-del" onclick="event.stopPropagation(); tableRemoveColumn(\'' + item.id + '\', ' + cIdx + ')" title="Xóa cột"><i class="fas fa-times"></i></button>';
                        html += '</div>';
                        html += '<div class="col-resizer" onmousedown="initResize(event, \'' + item.id + '\', \'col\', ' + cIdx + ')"></div>';
                    }
                    html += '</th>';
                    return html;
                }).join('');
                
                let extraCell = (window.isExecutionMode && item.canAddRows) ? '<th class="execution-delete-cell no-print" style="width: 30px; border: none; background: transparent;"></th>' : '';
                
                thead = '<thead><tr>' + colsHtml + extraCell + '</tr></thead>';
            }

            let rowsHtml = '';
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

                    let isSoloBadge = false;
                    if (cell.content) {
                        const tempDiv = document.createElement('div');
                        tempDiv.innerHTML = cell.content;
                        const badges = tempDiv.querySelectorAll('.ebmr-field-badge');
                        if (badges.length === 1) {
                            badges[0].remove();
                            if (tempDiv.textContent.trim() === '') {
                                isSoloBadge = true;
                            }
                        }
                    }

                    let displayContent = decorateContent(cell.content, null, loopSuffix);
                    if (displayContent === null || displayContent === 'null' || displayContent === undefined) {
                        displayContent = '';
                    }

                    if (displayContent.includes('#STT#')) {
                        displayContent = displayContent.replace(/#STT#/g, '<span class="ebmr-css-stt"></span>');
                    }
                    
                    let cellClass = isSoloBadge ? "solo-badge-cell " : "";
                    let onclickAttr = "";
                    let finalEditable = "false";

                    if (window.isExecutionMode) {
                        const cellKey = r + '_' + c;
                        const runVal = runDataForBlock[cellKey];
                        const isNA = runDataForBlock._na_state && runDataForBlock._na_state[cellKey];

                        // Bỏ qua logic cũ nếu trong ô có chứa thẻ biến số động (tránh ghi đè toàn bộ nội dung ô)
                        const hasDynamicFields = cell.content && cell.content.includes('ebmr-field-badge');
                        
                        if (!hasDynamicFields) {
                            if (displayContent.includes('[Nhập dữ liệu]')) {
                                cellClass += "execution-input-cell ";
                                onclickAttr = isNA ? '' : 'onclick="if(event.ctrlKey || event.shiftKey) return; openExecutionInputModal(\'' + blockKey + '\', ' + r + ', ' + c + ', \'text\')"';
                                displayContent = runVal ? '<span style="color: #2563eb; font-weight: 500;">' + runVal + '</span>' : '<span class="execution-badge input"><i class="fas fa-edit"></i> [Nhập dữ liệu]</span>';
                            }
    else if (displayContent.includes('[Ký tên]')) {
        cellClass += "execution-input-cell execution-input-cell--sig ";
        onclickAttr = isNA ? '' :
            `onclick="if(event.ctrlKey || event.shiftKey) return; openExecutionInputModal('${blockKey}', ${r}, ${c}, 'signature')"`;
        displayContent = runVal ? getSignatureDisplayHtml(runVal, 'signature') :
            `<span class="execution-badge signature"><i class="fas fa-pen"></i> [Ký tên]</span>`;
    } else if (displayContent.includes('[Tự động lấy thời gian]')) {
        cellClass += "execution-input-cell ";
        onclickAttr = isNA ? '' :
            `ondblclick="if(event.ctrlKey || event.shiftKey) return; autoFillTime('${blockKey}', ${r}, ${c})" title="Nháy đúp chuột (Double-click) để lấy ngày giờ hệ thống"`;
        displayContent = runVal ? `<span style="color: #2563eb; font-weight: 500;">${runVal}</span>` :
            `<span class="execution-badge time"><i class="fas fa-clock"></i> [Double-click lấy giờ]</span>`;
    } else if (displayContent.includes('[Người thực hiện]')) {
        cellClass += "execution-input-cell execution-input-cell--sig ";
        onclickAttr = isNA ? '' :
            `onclick="if(event.ctrlKey || event.shiftKey) return; autoFillExecutor('${blockKey}', ${r}, ${c})" title="Click để xác nhận người thực hiện"`;
        displayContent = runVal ? getSignatureDisplayHtml(runVal, 'executor') :
            `<span class="execution-badge executor"><i class="fas fa-user-edit"></i> [Người thực hiện]</span>`;
    } else if (displayContent.includes('[Người kiểm tra]')) {
        cellClass += "execution-input-cell execution-input-cell--sig ";
        onclickAttr = isNA ? '' :
            `onclick="if(event.ctrlKey || event.shiftKey) return; openCheckerAuthModal('${blockKey}', ${r}, ${c})" title="Click để xác thực người kiểm tra"`;
        displayContent = runVal ? getSignatureDisplayHtml(runVal, 'checker') :
            `<span class="execution-badge checker"><i class="fas fa-check-double"></i> [Người kiểm tra]</span>`;
    }
    }

    if (isNA) {
        cellClass += "cell-na-state strike-through-zone ";
    }
    } else {
        finalEditable = (item.locked || window.isReadOnly) ? 'false' : 'true';
    }

    let metaHtml = '';
    if (window.isExecutionMode) {
        const naMeta = runDataForBlock._na_state && runDataForBlock._na_state[`${r}_${c}_meta`];
        const meta = runDataForBlock._meta && runDataForBlock._meta[`${r}_${c}`];

        let historyBadge = '';
        if (meta && meta.history_count && meta.history_count > 0) {
            historyBadge =
                `<span class="badge bg-warning text-dark ms-1" style="cursor:pointer;" onclick="showRunDataHistory(event, '${window.currentRecordId}', '${item.id}', '${r}_${c}')" title="Xem lịch sử thay đổi">Lịch sử (${meta.history_count})</span>`;
        }

        let metaText = '';
        if (naMeta) {
            const reasonText = naMeta.reason || 'N/A';
            metaText =
                `<span style="color: #2563eb; font-weight: bold; margin-right: 4px;">${reasonText} -</span> ${naMeta.by || ''} ${naMeta.at || ''}`;
        } else if (meta && (meta.by || meta.at)) {
            metaText = `${meta.by || ''} ${meta.at || ''}`;
        }

        if (metaText || historyBadge) {
            metaHtml = `<div class="execution-meta">${metaText} ${historyBadge}</div>`;
        }
    }

    cellsHtml += `<td contenteditable="${finalEditable}"
                    spellcheck="false"
                    data-row="${r+1}"
                    data-col="${c}"
                    rowspan="${cell.rs || 1}"
                    colspan="${cell.cs || 1}"
                    ${onclickAttr}
                    class="${cellClass} ${item.locked ? 'locked-cell' : ''}"
                    style="position: relative; width: ${cellWidth}; height: ${rowH}; background-color: ${cellBg}; text-align: ${cell.textAlign || ''}; font-weight: ${cell.fontWeight || ''}; font-style: ${cell.fontStyle || ''}; text-decoration: ${cell.textDecoration || ''}; font-size: ${cell.fontSize || ''}; color: ${cell.textColor || ''}; text-transform: ${cell.textTransform || ''}; border-top: ${cell.borderTop ? cell.borderTop + ' !important' : ''}; border-bottom: ${cell.borderBottom ? cell.borderBottom + ' !important' : ''}; border-left: ${cell.borderLeft ? cell.borderLeft + ' !important' : ''}; border-right: ${cell.borderRight ? cell.borderRight + ' !important' : ''}; writing-mode: ${cell.writingMode || ''};"
                    oninput="updateTableInline('${blockKey}', 'cell', ${r}, ${c}, this.innerHTML)"
                    ${!window.isExecutionMode ? `
                        ondragover="event.preventDefault(); this.classList.add('criteria-drag-over');"
                        ondragleave="this.classList.remove('criteria-drag-over');"
                        ondrop="window.handleCriteriaDrop(event, '${item.id}', ${r}, ${c})"` : ''}>
                        <div class="cell-wrapper" style="transform: ${cell.transform || ''}; transform-origin: center center; display: inline-block; width: 100%;">${displayContent}</div>
                        ${metaHtml}
                        ${!window.isExecutionMode && c === 0 ? `
                        <div class="row-actions">
                            <button class="btn-row-add" onclick="event.stopPropagation(); tableAddRow(${r+1})" title="Thêm dòng bên dưới"><i class="fas fa-plus"></i></button>
                            <button class="btn-row-del" onclick="event.stopPropagation(); tableRemoveRow(${r})" title="Xóa dòng"><i class="fas fa-times"></i></button>
                        </div>` : ''}
                        ${!window.isExecutionMode ? `<div class="col-resizer" onmousedown="initResize(event, '${item.id}', 'col', ${c + (cell.cs || 1) - 1})"></div>
                        <div class="row-resizer" onmousedown="initResize(event, '${item.id}', 'row', ${r + (cell.rs || 1) - 1})"></div>` : ''}
                    </td>`;
    }

    let deleteCell = '';
    if (window.isExecutionMode && item.canAddRows) {
        const isDynamicRow = item.data[r] && item.data[r][0] && item.data[r][0].is_dynamic;
        if (isDynamicRow) {
            deleteCell = `<td class="execution-delete-cell no-print" style="width: 30px; border: none; background: transparent; vertical-align: middle;">
                                                                <button class="btn btn-link text-danger p-0" title="Xóa dòng" onclick="executeDeleteTableRow('${item.id}', ${r})">
                                                                    <i class="fas fa-times-circle"></i>
                                                                </button>
                                                              </td>`;
        } else {
            deleteCell =
                `<td class="execution-delete-cell no-print" style="width: 30px; border: none; background: transparent;"></td>`;
        }
    }
    rowsHtml += `<tr>${cellsHtml}${deleteCell}</tr>`;
    }

    let addRowBtn = '';
    if (item.canAddRows) {
        const btnLabel = window.isExecutionMode ? 'THÊM DÒNG CUỐI' : 'THỬ THÊM DÒNG (CẤP 2)';
        addRowBtn = `
                                            <div class="mt-2 text-start no-print">
                                                <button class="btn btn-xs btn-outline-primary py-0 px-2 fw-bold" style="font-size: 0.65rem; border-radius: 4px;" onclick="executeAddTableRow('${item.id}')">
                                                    <i class="fas fa-plus me-1"></i> ${btnLabel}
                                                </button>
                                            </div>
                                        `;
    }

    let titleHtml = '';
    if (item.isAbbreviationTable) {
        let titleText = 'Danh Sách Viết Tắt';
        const langMode = window.currentLangMode || 'vi';
        if (langMode === 'en') {
            titleText = 'List of Abbreviations';
        } else if (langMode === 'dual') {
            titleText = 'Danh Sách Viết Tắt / List of Abbreviations';
        }
        titleHtml = `
                    <div class="ebmr-section-header d-flex align-items-center mb-3 mt-4">
                        <div class="section-icon bg-info text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; min-width: 40px;">
                            <i class="fas fa-list-ul"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="section-title fw-bold text-uppercase" style="font-size: 1.2rem; color: #164e63; letter-spacing: 1px;">
                                ${titleText}
                            </div>
                            <div class="section-line mt-1" style="height: 3px; background: linear-gradient(to right, #0ea5e9, transparent); border-radius: 2px;"></div>
                        </div>
                    </div>
                `;
    }

    let progressHtml = '';
    if (window.isExecutionMode && item.freq_minutes) {
        const countdownState = window._activeCountdowns && window._activeCountdowns[blockKey];
        const isRunning = !!countdownState;

        let initialWidth = '100%';
        let initialText = '--:--';
        let initialClass = 'bg-success';

        if (isRunning) {
            const elapsed = Date.now() - countdownState.startTime;
            const remaining = countdownState.freqMs - elapsed;
            if (remaining > 0) {
                const percent = (remaining / countdownState.freqMs) * 100;
                initialWidth = `${percent}%`;

                if (percent > 50) {
                    initialClass = 'bg-success';
                } else if (percent > 20) {
                    initialClass = 'bg-warning';
                } else {
                    initialClass = 'bg-danger';
                }

                const totalSeconds = Math.ceil(remaining / 1000);
                const mins = Math.floor(totalSeconds / 60).toString().padStart(2, '0');
                const secs = (totalSeconds % 60).toString().padStart(2, '0');
                initialText = `${mins}:${secs}`;
            } else {
                initialWidth = '0%';
                initialText = 'Đã đến giờ lấy mẫu!';
                initialClass = 'bg-danger';
            }
        }

        progressHtml = `
                    <div class="sampling-countdown-container my-3 px-1" id="countdown-container-${blockKey}" style="display: ${isRunning ? 'block' : 'none'};">
                        <div class="d-flex justify-content-between align-items-center mb-1" style="font-size: 0.85rem; color: #475569; font-weight: 600;">
                            <span><i class="fas fa-hourglass-half me-1 text-primary"></i> Thời gian đến lần lấy mẫu tiếp theo:</span>
                            <span id="countdown-text-${blockKey}" style="font-family: monospace; font-size: 0.95rem; color: #1e293b;">${initialText}</span>
                        </div>
                        <div class="progress" style="height: 12px; background-color: #e2e8f0; border-radius: 6px; overflow: hidden; box-shadow: inset 0 1px 2px rgba(0,0,0,0.05);">
                            <div id="countdown-bar-${blockKey}" class="progress-bar progress-bar-striped progress-bar-animated ${initialClass}" role="progressbar" style="width: ${initialWidth}; transition: width 1s linear, background-color 0.5s ease;"></div>
                        </div>
                    </div>
                `;
    }

    content =
        `${titleHtml}${progressHtml}<div class="table-responsive-wrapper"><table class="mini-table ${borderClass}" style="--table-border-width: ${item.borderWeight || '1px'}; --table-border-color: #dee2e6;">${thead}<tbody>${rowsHtml}</tbody></table></div>${addRowBtn}`;
    }

    // --- XỬ LÝ KHỐI VĂN BẢN TĨNH (STATIC TEXT) ---
    else if (item.type === 'static-text') {
        const displayContent = decorateContent(item.content || '', null, loopSuffix);
        const textEditable = (window.isReadOnly || window.isExecutionMode) ? 'false' : 'true';
        const borderClass = item.borderMode === 'dashed' ? 'border-dashed' : (item.borderMode === 'visible' ?
            'border-visible' : 'border-none');

        let titleHtml = '';
        if (item.isCalculationBlock && item.section_id === window.catId) {
            let titleText = 'Tính Toán Công Thức';
            const langMode = window.currentLangMode || 'vi';
            if (langMode === 'en') {
                titleText = 'Formula Calculation';
            } else if (langMode === 'dual') {
                titleText = 'Tính Toán Công Thức / Formula Calculation';
            }
            titleHtml = `
                    <div class="ebmr-section-header d-flex align-items-center mb-3 mt-4">
                        <div class="section-icon bg-info text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; min-width: 40px;">
                            <i class="fas fa-calculator"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="section-title fw-bold text-uppercase" style="font-size: 1.2rem; color: #164e63; letter-spacing: 1px;">
                                ${titleText}
                            </div>
                            <div class="section-line mt-1" style="height: 3px; background: linear-gradient(to right, #0ea5e9, transparent); border-radius: 2px;"></div>
                        </div>
                    </div>
                `;
        }

        content =
            `${titleHtml}<div class="static-text-display ${borderClass}" contenteditable="${textEditable}" spellcheck="false" 
                                                oninput="updateStaticTextInline('${item.id}', this.innerHTML); handleAutoCapitalize(this)">${displayContent}</div>`;
    }

    // --- XỬ LÝ KHỐI BIỂU MẪU NHÚNG (LINKED TEMPLATE) ---
    else if (item.type === 'linked-template') {
        const isPreviewing = window.isExecutionMode || item.showPreview || false;
        const hideControls = window.isExecutionMode;
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
                                             <div class="fw-bold text-navy ${isPreviewing ? 'd-none' : ''}">Biểu mẫu chung: ${item.label || 'Đang tải...'}</div>
                                             ${!isPreviewing ? `<div class="small text-muted mt-1">Nội dung sẽ được tự động chèn vào khi ban hành/thực thi</div>` : ''}
                                             ${previewContent}
                                           </div>`;

        if (isPreviewing) {
            setTimeout(() => fetchAndRenderGfPreview(item.id, item.template_id), 50);
        }
    }

    // --- XỬ LÝ KHỐI PHÂN ĐOẠN (SECTION) ---
    else if (item.type === 'section') {
        const labelEditable = (window.isReadOnly || window.isExecutionMode) ? 'false' : 'true';
        const isRecalc = (item.stage_code === 'recalc' || (item.label && item.label.toUpperCase().includes(
            'TÍNH TOÁN CÔNG THỨC')));
        const iconClass = isRecalc ? 'fas fa-calculator' : 'fas fa-layer-group';
        content = `<div class="ebmr-section-header d-flex align-items-center" id="section-${item.id}">
                                             <div class="section-icon bg-info text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; min-width: 40px;">
                                                <i class="${iconClass}"></i>
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
        const canvasId = 'chart_canvas_' + blockKey;
        content = `<div class="chart-container" style="position: relative; height:300px; width:100%; padding: 15px; background: #fff; border: 1px solid #eee; border-radius: 8px;">
                                             <canvas id="${canvasId}"></canvas>
                                           </div>`;
        setTimeout(() => {
            if (typeof renderChart === 'function') {
                renderChart(canvasId, item.chartConfig);
            }
        }, 50);
    }

    // --- XỬ LÝ KHỐI NGẮT TRANG (PAGE BREAK) ---
    else if (item.type === 'page-break') {
        const label = item.label || 'Ngắt trang';
        const execNote = window.isExecutionMode ? ' — Bước mới' : '';
        content = `<div class="page-break-block">
                <div class="page-break-band">
                </div>
            </div>`;
    }

    // --- THANH HÀNH ĐỘNG (XÓA, DI CHUYỂN) ---
    const isPageBreak = item.type === 'page-break';
    const actions = (item.locked || window.isReadOnly || window.isExecutionMode) ? '' : `
                                <div class="block-actions">
                                    <button class="btn btn-sm btn-light border shadow-sm text-danger" onclick="removeItem('${item.id}')"><i class="fas fa-trash"></i></button>
                                    ${!isPageBreak ? `
                                    <button class="btn btn-sm btn-light border shadow-sm" onclick="moveItem(${idx}, -1)"><i class="fas fa-chevron-up"></i></button>
                                    <button class="btn btn-sm btn-light border shadow-sm" onclick="moveItem(${idx}, 1)"><i class="fas fa-chevron-down"></i></button>` : ''}
                                </div>`;

    div.innerHTML = `
                                ${actions}
                                ${item.type !== 'static-text' && item.type !== 'section' && item.type !== 'page-break' && !window.isExecutionMode && item.label && item.label !== 'null' && !item.isGfHeader && !item.isBmrHeader && !item.isAbbreviationTable ? `<span class="block-label">${item.label} ${item.locked ? '<i class="fas fa-lock ms-1 small"></i>' : ''}</span>` : ''}
                                ${content}
                            `;
    return div;
    }

    /**
     * Hàm chính để vẽ toàn bộ giao diện dựa trên mảng dữ liệu `items`.
     */
    function renderBlocks() {
        const container = document.getElementById('editor-content');
        const hint = document.getElementById('drop-hint');
        if (!container) return;

        // Save selection state to restore after rendering
        const savedSelected = [];
        document.querySelectorAll('.selected-cell').forEach(cell => {
            const blockItem = cell.closest('.block-item');
            const bId = blockItem ? blockItem.getAttribute('data-id') : null;
            const r = cell.dataset.row;
            const c = cell.dataset.col;
            if (bId && r !== undefined && c !== undefined) {
                savedSelected.push({
                    bId,
                    r,
                    c
                });
            }
        });

        if (window.isExecutionMode) {
            selectedId = null;
        }

        container.innerHTML = '';

        if (hint) {
            if (!window.isExecutionMode) container.appendChild(hint);
            else hint.remove();
        }

        // Group items into sections
        let sections = [];
        let currentSection = null;
        let activeSectionIdTracker = window.activeSectionId || null;

        items.forEach((item) => {
            if (item.type === 'section') {
                activeSectionIdTracker = item.section_id || item.id;
            }
            if (!item.section_id) {
                item.section_id = activeSectionIdTracker || 'section_0';
            }

            if (item.type === 'section') {
                currentSection = {
                    sectionBlock: item,
                    blocks: []
                };
                sections.push(currentSection);
            } else {
                if (!currentSection) {
                    currentSection = {
                        sectionBlock: {
                            id: 'section_0',
                            type: 'section',
                            label: 'PHÂN ĐOẠN MẶC ĐỊNH',
                            locked: true
                        },
                        blocks: []
                    };
                    sections.push(currentSection);
                }
                currentSection.blocks.push(item);
            }
        });

        let lastSectionId = null;
        let currentGroup = container;

        sections.forEach((sec) => {
            const sectionBlock = sec.sectionBlock;
            const blocks = sec.blocks;
            const itemSectionId = sectionBlock.id;

            const isHeader = sectionBlock.isGfHeader || sectionBlock.isBmrHeader || (sectionBlock.type ===
                'section' && sectionBlock.locked) || sectionBlock.isAbbreviationTable;

            if (!window.isViewAllMode && window.activeSectionId) {
                if (itemSectionId !== window.activeSectionId && !isHeader) {
                    return;
                }
            }

            if (window.isViewAllMode || !window.activeSectionId) {
                if (lastSectionId === null || itemSectionId !== lastSectionId) {
                    if (lastSectionId !== null) {
                        const pageBreak = document.createElement('div');
                        pageBreak.className =
                            'page-break-divider my-4 d-flex align-items-center justify-content-center';
                        const parts = (itemSectionId || '').split('_');
                        const labelText = parts.length > 1 ? `Công đoạn ${parts[parts.length-1]}` :
                            'Phân đoạn mới';
                        pageBreak.innerHTML = ``;
                        container.appendChild(pageBreak);
                    }

                    currentGroup = document.createElement('div');
                    currentGroup.className = 'section-group-wrapper' + (window.activeSectionId ===
                        itemSectionId ? ' active' : '');
                    currentGroup.onclick = (e) => {
                        if (!e.target.closest('.block-item')) {
                            window.activeSectionId = itemSectionId;
                            selectedId = null;
                            renderBlocks();
                        }
                    };
                    currentGroup.ondblclick = (e) => {
                        if (!e.target.closest('.block-item') && window.isViewAllMode) {
                            window.activeSectionId = itemSectionId;
                            window.isViewAllMode = false;
                            selectedId = null;
                            renderBlocks();
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
                currentGroup = container;
            }

            // Draw section block itself
            const secIdxInItems = items.indexOf(sectionBlock);
            if (!window.isReadOnly && !window.isExecutionMode) {
                addInsertionDivider(currentGroup, secIdxInItems);
            }

            const secDiv = document.createElement('div');
            secDiv.className =
                `block-item type-section ${selectedId === sectionBlock.id ? 'active' : ''} ${window.isExecutionMode ? 'execution-mode' : ''}`;
            secDiv.setAttribute('data-id', sectionBlock.id);
            if (sectionBlock.backgroundColor) secDiv.style.backgroundColor = sectionBlock.backgroundColor;
            if (!window.isExecutionMode) {
                secDiv.onclick = (e) => {
                    window.handleBlockClick(e, sectionBlock);
                };
            }

            const labelEditable = (window.isReadOnly || window.isExecutionMode) ? 'false' : 'true';
            const isRecalc = (sectionBlock.stage_code === 'recalc' || (sectionBlock.label && sectionBlock.label
                .toUpperCase().includes('TÍNH TOÁN CÔNG THỨC')));
            const iconClass = isRecalc ? 'fas fa-calculator' : 'fas fa-layer-group';

            let sectionBlockContent = `
                <div class="ebmr-section-header d-flex align-items-center" id="section-${sectionBlock.id}">
                    <div class="section-icon bg-info text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; min-width: 40px;">
                        <i class="${iconClass}"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="section-title fw-bold text-uppercase" contenteditable="${labelEditable}" 
                             onblur="updateItemProp('label', this.innerText)" 
                             style="font-size: 1.2rem; color: #164e63; letter-spacing: 1px;">${sectionBlock.label || 'Tên phân đoạn'}</div>
                        <div class="section-line mt-1" style="height: 3px; background: linear-gradient(to right, #0ea5e9, transparent); border-radius: 2px;"></div>
                    </div>
                </div>
            `;
            const secActions = (sectionBlock.locked || window.isReadOnly || window.isExecutionMode) ? '' : `
                                <div class="block-actions">
                                    <button class="btn btn-sm btn-light border shadow-sm text-danger" onclick="removeItem('${sectionBlock.id}')"><i class="fas fa-trash"></i></button>
                                    <button class="btn btn-sm btn-light border shadow-sm" onclick="moveItem(${secIdxInItems}, -1)"><i class="fas fa-chevron-up"></i></button>
                                    <button class="btn btn-sm btn-light border shadow-sm" onclick="moveItem(${secIdxInItems}, 1)"><i class="fas fa-chevron-down"></i></button>
                                </div>`;

            secDiv.innerHTML = `
                ${secActions}
                ${sectionBlockContent}
            `;
            currentGroup.appendChild(secDiv);

            // Group blocks by loop_group_id
            let groupedBlocks = [];
            let currentRun = null;

            blocks.forEach((block) => {
                const loopGroupId = block.loop_group_id || null;
                const loopCount = block.loop_count || null;

                if (loopGroupId) {
                    if (currentRun && currentRun.isLoopGroup && currentRun.loop_group_id ===
                        loopGroupId) {
                        currentRun.blocks.push(block);
                    } else {
                        currentRun = {
                            isLoopGroup: true,
                            loop_group_id: loopGroupId,
                            loop_count: parseInt(loopCount) || 1,
                            blocks: [block]
                        };
                        groupedBlocks.push(currentRun);
                    }
                } else {
                    currentRun = null;
                    groupedBlocks.push({
                        isLoopGroup: false,
                        blocks: [block]
                    });
                }
            });

            // Render groups
            groupedBlocks.forEach((group) => {
                if (group.isLoopGroup) {
                    if (window.isExecutionMode) {
                        const loopCount = group.loop_count;
                        let activeLoopIdx = window.activeBlockLoopIndices ? window
                            .activeBlockLoopIndices[group.loop_group_id] : 1;
                        if (activeLoopIdx === undefined || activeLoopIdx > loopCount) {
                            activeLoopIdx = 1;
                        }
                        if (!window.activeBlockLoopIndices) window.activeBlockLoopIndices = {};
                        window.activeBlockLoopIndices[group.loop_group_id] = activeLoopIdx;

                        // Render Tab headers
                        const headerDiv = document.createElement('div');
                        headerDiv.className =
                            `section-loop-tabs-header block-loop-tabs-header-${group.loop_group_id} d-flex align-items-center gap-2 mt-2 mb-3`;
                        for (let i = 1; i <= loopCount; i++) {
                            const isActive = (i === activeLoopIdx) ? 'active' : '';
                            const btn = document.createElement('button');
                            btn.className = `btn btn-sm btn-outline-primary ${isActive}`;
                            btn.setAttribute('data-loop-idx', i);
                            btn.innerText = `Lần ${i}`;
                            btn.onclick = () => switchBlockLoopTab(group.loop_group_id, i);
                            headerDiv.appendChild(btn);
                        }
                        currentGroup.appendChild(headerDiv);

                        // Render tab contents
                        for (let i = 1; i <= loopCount; i++) {
                            const tabContentDiv = document.createElement('div');
                            tabContentDiv.className =
                                `section-loop-tab-content block-loop-tab-content-${group.loop_group_id}`;
                            tabContentDiv.setAttribute('data-loop-idx', i);
                            tabContentDiv.style.display = (i === activeLoopIdx) ? 'block' : 'none';

                            group.blocks.forEach((block) => {
                                const blockIdx = items.indexOf(block);
                                const blockDiv = renderSingleBlock(block, blockIdx,
                                    `_loop_${i}`);
                                tabContentDiv.appendChild(blockDiv);
                            });

                            currentGroup.appendChild(tabContentDiv);
                        }
                    } else {
                        // Designer mode: Draw loop group boundary wrapper
                        const loopGroupWrapper = document.createElement('div');
                        loopGroupWrapper.className =
                            'designer-loop-group-wrapper border border-dashed border-primary rounded p-3 my-3 position-relative';
                        loopGroupWrapper.style.backgroundColor = 'rgba(14, 165, 233, 0.03)';

                        const badge = document.createElement('div');
                        badge.className =
                            'position-absolute bg-primary text-white px-2 py-1 rounded-bottom small fw-bold';
                        badge.style.top = '0';
                        badge.style.left = '15px';
                        badge.style.zIndex = '5';
                        if (!window.isReadOnly) {
                            badge.style.cursor = 'pointer';
                            badge.title = "Nhấp để chỉnh sửa cài đặt lặp nhóm";
                            badge.onclick = (e) => {
                                e.stopPropagation();
                                if (typeof window.editLoopGroup === 'function') {
                                    window.editLoopGroup(group.loop_group_id);
                                }
                            };
                        }
                        badge.innerHTML =
                            `<i class="fas fa-redo me-1"></i> Lặp nhóm: ${group.loop_count} lần`;
                        loopGroupWrapper.appendChild(badge);

                        group.blocks.forEach((block) => {
                            const blockIdx = items.indexOf(block);
                            if (!window.isReadOnly) {
                                addInsertionDivider(loopGroupWrapper, blockIdx);
                            }
                            const blockDiv = renderSingleBlock(block, blockIdx);
                            blockDiv.classList.add('loop-group-member');
                            loopGroupWrapper.appendChild(blockDiv);
                        });

                        currentGroup.appendChild(loopGroupWrapper);
                    }
                } else {
                    // Not a loop group
                    group.blocks.forEach((block) => {
                        const blockIdx = items.indexOf(block);
                        if (!window.isReadOnly && !window.isExecutionMode) {
                            addInsertionDivider(currentGroup, blockIdx);
                        }
                        const blockDiv = renderSingleBlock(block, blockIdx);
                        currentGroup.appendChild(blockDiv);
                    });
                }
            });
            
            if (!window.isReadOnly && !window.isExecutionMode) {
                let lastIdxInSection = -1;
                const secIdToMatch = sectionBlock ? (sectionBlock.section_id || sectionBlock.id) : null;
                if (secIdToMatch) {
                    for (let i = items.length - 1; i >= 0; i--) {
                        if (items[i].section_id === secIdToMatch || items[i].id === secIdToMatch) {
                            lastIdxInSection = i;
                            break;
                        }
                    }
                } else {
                    lastIdxInSection = items.indexOf(blocks[blocks.length - 1] || sectionBlock);
                }
                
                if (lastIdxInSection !== -1) {
                    addInsertionDivider(currentGroup, lastIdxInSection + 1);
                }
            }
        });

        // Rebuild Outline when DOM changes
        if (typeof buildOutline === 'function') {
            setTimeout(buildOutline, 100);
        }

        // Load dynamic options for any master data select fields
        if (window.isExecutionMode) {
            setTimeout(loadDynamicSelectOptions, 100);
        }

        // Refresh comments positioning after rendering blocks
        if (typeof window.renderComments === 'function') {
            window.renderComments();
        }

        // Sync split screen preview if active
        if (typeof window.syncPreviewContent === 'function') {
            window.syncPreviewContent();
        }

        // Restore selection state
        savedSelected.forEach(sel => {
            const cell = document.querySelector(
                `.block-item[data-id="${sel.bId}"] [data-row="${sel.r}"][data-col="${sel.c}"]`);
            if (cell) cell.classList.add('selected-cell');
        });
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
        divider.ondragover = (e) => {
            // Check body class instead of types for better cross-browser support
            if (document.body.classList.contains('component-dragging')) {
                e.preventDefault();
                divider.classList.add('drag-over-active');
            }
        };
        divider.ondragleave = (e) => {
            divider.classList.remove('drag-over-active');
        };
        divider.ondrop = (e) => {
            e.preventDefault();
            divider.classList.remove('drag-over-active');
            
            const action = e.dataTransfer.getData('action');
            if (action === 'insertEquipmentTable') {
                const equipmentDataStr = e.dataTransfer.getData('equipmentData');
                if (equipmentDataStr && typeof insertEquipmentTable === 'function') {
                    insertEquipmentTable(idx, equipmentDataStr);
                }
                return;
            }
            
            const componentId = e.dataTransfer.getData('componentId');
            const componentName = e.dataTransfer.getData('componentName');
            if (componentId) {
                // Determine actual insert index taking into account section definitions etc if needed.
                // idx here is the block's array index.
                importMasterForm(componentId, componentName, idx);
            }
        };

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

            // Gợi ý logic xử lý dán dữ liệu tại vị trí idx đã chọn
            handleGlobalPaste(mockEvent, idx);

        } catch (err) {
            console.error(err);
            Swal.fire('Quyền truy cập',
                'Vui lòng cho phép trình duyệt truy cập bộ nhớ tạm (Clipboard) để dán nội dung.', 'info');
        }
    };

    /**
     * Xử lý nội dung HTML để hiển thị các "Thẻ biến số" (Variable Badges).
     * Hàm này sẽ thay thế các thẻ thô bằng giao diện có icon và màu sắc tùy theo loại dữ liệu (Ngày, Số, Chữ ký...).
     * @param {string} html - Nội dung HTML gốc chứa thẻ biến.
     */
    function decorateContent(html, customConfig = null, loopSuffix = '') {
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
                // Apply style properties if they exist
                if (field.style) {
                    if (field.style.width) {
                        badge.style.setProperty('width', field.style.width, 'important');
                    } else {
                        badge.style.removeProperty('width');
                    }
                    if (field.style.height) {
                        badge.style.setProperty('height', field.style.height, 'important');
                    } else {
                        badge.style.removeProperty('height');
                    }
                    if (field.style.marginLeft) {
                        badge.style.setProperty('margin-left', field.style.marginLeft, 'important');
                    } else {
                        badge.style.removeProperty('margin-left');
                    }
                    if (field.style.badgeAlign) {
                        badge.style.setProperty('display', 'table', 'important');
                        if (field.style.badgeAlign === 'center') {
                            badge.style.setProperty('margin-left', 'auto', 'important');
                            badge.style.setProperty('margin-right', 'auto', 'important');
                        } else if (field.style.badgeAlign === 'right') {
                            badge.style.setProperty('margin-left', 'auto', 'important');
                            badge.style.setProperty('margin-right', '0', 'important');
                        } else {
                            badge.style.setProperty('margin-left', '0', 'important');
                            badge.style.setProperty('margin-right', 'auto', 'important');
                        }
                    } else {
                        badge.style.removeProperty('display');
                        badge.style.removeProperty('margin-right');
                    }
                } else {
                    badge.style.removeProperty('width');
                    badge.style.removeProperty('margin-left');
                }

                // TRƯỜNG HỢP 1: Chế độ THỰC THI (Cho phép nhập thử để xem kết quả)
                if (window.isExecutionMode) {

                    // Nếu là thẻ công thức, thực hiện tính toán ngay
                    if (field.type === 'formula') {
                        const dPlaces = (field.validation && field.validation.decimal_places !== null) ? field
                            .validation.decimal_places : 2;
                        const result = calculateFormula(field.formula || '', dPlaces, field.id, loopSuffix);
                        applyFormulaLimits(badge, field, result);
                        badge.className = 'ebmr-field-badge ebmr-field-value formula-result';
                        if (badge.hasAttribute('data-limit-info')) badge.className += ' formula-out-of-bounds';
                        badge.setAttribute('data-field-id', fieldId + loopSuffix); // Để recalculate tìm được

                        const isNA = window.executionValues[`_na_state_${field.id}`] === true;
                        if (isNA) {
                            badge.className += ' cell-na-state strike-through-zone';
                            badge.style.pointerEvents = 'none';
                            badge.innerHTML = `<span style="opacity:0.4">${badge.innerHTML}</span>`;
                        }
                        return;
                    }

                    let rawVal = window.executionValues[fieldId + loopSuffix];
                    let val = '';
                    if (rawVal !== undefined && rawVal !== null && rawVal !== '') {
                        val = rawVal;
                    } else if (field.defaultValue !== undefined && field.defaultValue !== null) {
                        val = field.defaultValue;
                    }

                    // Handle nested structure from DB (cell_id = 'default')
                    if (val && typeof val === 'object' && val.hasOwnProperty('default')) {
                        val = val.default;
                    } else if (val && typeof val === 'object' && !Array.isArray(val)) {
                        // Fallback: if it's an object, try to get the first property value
                        const keys = Object.keys(val);
                        if (keys.length > 0) val = val[keys[0]];
                    }

                    let metaHtml = '';
                    const fieldData = window.executionValues[fieldId + loopSuffix];
                    const isNA = window.executionValues[`_na_state_${field.id}`] === true;

                    if (window.isExecutionMode) {
                        const naMeta = window.executionValues[`_na_state_${field.id}_meta`];
                        const meta = fieldData && fieldData._meta && fieldData._meta['default'];

                        let historyBadge = '';
                        if (meta && meta.history_count && meta.history_count > 0) {
                            historyBadge =
                                `<span class="badge bg-warning text-dark ms-1" style="cursor:pointer;" onclick="showRunDataHistory(event, '${window.currentRecordId}', '${fieldId + loopSuffix}', 'default')" title="Xem lịch sử thay đổi">Lịch sử (${meta.history_count})</span>`;
                        }

                        let metaText = '';
                        if (naMeta) {
                            const reasonText = naMeta.reason || 'N/A';
                            metaText =
                                `<span style="color: #2563eb; font-weight: bold; margin-right: 4px;">${reasonText} -</span> ${naMeta.at || ''}<br><span style="font-size: 9px; color: #6c757d;">${naMeta.by || ''}</span>`;
                        } else if (meta && (meta.by || meta.at)) {
                            metaText =
                                `${meta.at || ''}<br><span style="font-size: 9px; color: #6c757d;">${meta.by || ''}</span>`;
                        }

                        if (metaText || historyBadge) {
                            metaHtml =
                                `<div class="execution-meta execution-meta-variable" style="font-size: 10px; margin-top: 2px; text-align: center; white-space: nowrap; line-height: 1.1; pointer-events: none;">${metaText} <span style="pointer-events: auto;">${historyBadge}</span></div>`;
                        }
                    }

                    badge.setAttribute('data-field-id', fieldId + loopSuffix);

                    if (field.type === 'signature') {
                        let clickAttr = '';
                        if (!window.isReadOnly) {
                            if (field.is_checker) {
                                clickAttr =
                                    `onclick="openCheckerAuthModal('${fieldId + loopSuffix}', 'default', 'default')" title="Click để xác thực người kiểm tra"`;
                            } else {
                                clickAttr =
                                    `onclick="openExecutionInputModal('${fieldId + loopSuffix}', 'default', 'default', 'signature')"`;
                            }
                        }

                        let signatureHtml = '';
                        if (val) {
                            if (val.startsWith('data:image/')) {
                                signatureHtml =
                                    `<img src="${val}" style="max-height: 40px; max-width: 120px; object-fit: contain; mix-blend-mode: multiply; vertical-align: middle; cursor: ${window.isReadOnly ? 'default' : 'pointer'};" ${clickAttr} />`;
                            } else {
                                signatureHtml =
                                    `<span class="badge bg-light text-success border" style="cursor: ${window.isReadOnly ? 'default' : 'pointer'}; font-weight: 600;" ${clickAttr}><i class="fas fa-check-circle me-1"></i>${val}</span>`;
                            }
                        } else {
                            if (field.is_checker) {
                                signatureHtml =
                                    `<span class="badge bg-light text-warning border border-warning" style="cursor: ${window.isReadOnly ? 'default' : 'pointer'};" ${clickAttr}><i class="fas fa-check-double me-1"></i> [Người kiểm tra]</span>`;
                            } else {
                                signatureHtml =
                                    `<span class="badge bg-light text-primary border" style="cursor: ${window.isReadOnly ? 'default' : 'pointer'};" ${clickAttr}><i class="fas fa-signature me-1"></i> [Ký tên]</span>`;
                            }
                        }
                        badge.innerHTML = `${signatureHtml}`;
                        badge.className = 'ebmr-field-badge ebmr-field-value';
                    } else if (field.type === 'checkbox') {
                        let isChecked = false;
                        let disabledAttr = window.isReadOnly ? 'disabled' : '';

                        if (field.formula && window.isExecutionMode) {
                            // Tự động tick theo công thức
                            const result = calculateFormula(field.formula || '', 2, field.id, loopSuffix);
                            isChecked = parseFloat(result) > 0;
                            disabledAttr = 'disabled'; // Khóa không cho tick tay

                            // Cập nhật giá trị vào executionValues để đồng bộ
                            if (typeof window.executionValues[fieldId + loopSuffix] !== 'object' || window
                                .executionValues[fieldId + loopSuffix] === null) {
                                window.executionValues[fieldId + loopSuffix] = {};
                            }
                            window.executionValues[fieldId + loopSuffix]['default'] = isChecked;
                        } else {
                            if (typeof val === 'boolean') {
                                isChecked = val;
                            } else if (val !== undefined && val !== null) {
                                const valStr = String(val).toLowerCase().trim();
                                isChecked = (valStr === '1' || valStr === 'true' || valStr === 'yes' ||
                                    valStr === 'có' || valStr === 'checked');
                            }
                        }

                        const checkboxLabel = field.label ? `<label class="ms-1 cursor-pointer" style="font-size: inherit; color: inherit; cursor: pointer; user-select: none;">${field.label}</label>` : '';
                        badge.innerHTML =
                            `<span class="execution-checkbox-wrapper d-inline-flex align-items-center gap-1"><input type="checkbox" id="chk_${fieldId + loopSuffix}" class="execution-checkbox" ${isChecked ? 'checked' : ''} ${disabledAttr} onchange="window.handleCheckboxChange('${fieldId + loopSuffix}', this.checked, this)">${checkboxLabel.replace('<label', `<label for="chk_${fieldId + loopSuffix}"`)}</span>`;
                        badge.className = 'ebmr-field-badge ebmr-field-value';
                    } else if (field.type === 'select') {
                        const dsType = field.dataSource ? field.dataSource.type : 'manual';
                        if (dsType === 'database') {
                            const ds = field.dataSource;
                            badge.innerHTML =
                                `<select class="form-select-sm border-0 border-bottom bg-transparent dynamic-select" data-field-id="${fieldId + loopSuffix}" data-table="${ds.table || ''}" data-label="${ds.labelCol || ''}" data-value="${ds.valueCol || ''}" data-where="${ds.where || ''}" data-selected="${val}" ${window.isReadOnly ? 'disabled' : ''} onchange="window.handleSelectChange('${fieldId + loopSuffix}', this.value, this)"><option value="">-- Đang tải... --</option></select>`;
                        } else {
                            let optionsArr = [];
                            if (Array.isArray(field.options)) {
                                optionsArr = field.options;
                            } else if (typeof field.options === 'string') {
                                optionsArr = field.options.split(/[,;\n]/).map(o => o.trim()).filter(o => o);
                            }
                            badge.innerHTML =
                                `<select class="form-select-sm border-0 border-bottom bg-transparent" ${window.isReadOnly ? 'disabled' : ''} onchange="window.handleSelectChange('${fieldId + loopSuffix}', this.value)"><option value="">--</option>${optionsArr.map(o => `<option value="${o}" ${val === o ? 'selected' : ''}>${o}</option>`).join('')}</select>`;
                        }
                        badge.className = 'ebmr-field-badge ebmr-field-value';
                    } else if (field.type === 'date') {
                        const placeholder = field.label || 'Chọn thời gian...';
                        const displayVal = val || '';
                        const isNow = (field.autoSystemTime !== false);
                        const titleAttr = isNow ? 'title="Nhấp chuột để tự động điền ngày giờ hệ thống"' : '';

                        badge.innerHTML =
                            `<span class="execution-input-test" ${!window.isReadOnly ? `onclick="handleDateVariableClick(event, '${fieldId + loopSuffix}', ${isNow})"` : ''} style="cursor: ${window.isReadOnly ? 'default' : 'pointer'}; border-bottom: 1px dotted #1a73e8; min-width: 30px; display: inline-block; outline: none; position: relative;" ${titleAttr}>${displayVal || `<span style="color: #6c757d; font-style: italic;">${placeholder}</span>`}</span>`;
                        badge.className = 'ebmr-field-badge ebmr-field-value';
                    } else {
                        // Các loại khác: Văn bản, Số
                        const placeholder = field.label || '...';
                        const displayVal = val || '';

                        let extraStyle = '';
                        if (field.type === 'number' && val !== null && val !== undefined && val !== '') {
                            const numVal = Number(val);
                            if (!isNaN(numVal) && field.validation) {
                                let isOutOfBounds = false;
                                const minVal = field.validation.min;
                                const maxVal = field.validation.max;
                                if (minVal !== null && minVal !== undefined && minVal !== '' && !isNaN(Number(
                                        minVal)) && numVal < Number(minVal)) {
                                    isOutOfBounds = true;
                                }
                                if (maxVal !== null && maxVal !== undefined && maxVal !== '' && !isNaN(Number(
                                        maxVal)) && numVal > Number(maxVal)) {
                                    isOutOfBounds = true;
                                }
                                if (isOutOfBounds) {
                                    extraStyle =
                                        'color: #d93025; font-weight: bold; background-color: #fce8e6; border: 1px solid #fad2cf; padding: 2px 4px; border-radius: 4px;';
                                }
                            }
                        }

                        // Nút đọc cân: chỉ hiện khi người thiết kế bật "Lấy dữ liệu từ cân" trong cấu hình biến số
                        const scaleEnabled = field.type === 'number' &&
                            !window.isReadOnly &&
                            field.scaleEnabled === true &&
                            ('serial' in navigator);
                        const scaleBtnHtml = scaleEnabled ?
                            `<button class="btn-read-scale" onclick="event.stopPropagation(); window.readScaleValueIntoField('${fieldId + loopSuffix}')" title="⚖️ Đọc giá trị từ Cân điện tử (RS-232)"><i class="fas fa-balance-scale"></i></button>` :
                            '';

                        const meta = fieldData && fieldData._meta && fieldData._meta['default'];
                        if (meta && meta.device) {
                            badge.innerHTML = `
                                <div style="margin-top: 4px; padding: 6px 10px; background: #fff; border: 1px solid #111; border-radius: 2px; text-align: left; font-family: 'Courier New', Courier, monospace; font-size: 11px; color: #000; line-height: 1.5; min-width: 160px; box-shadow: 2px 2px 4px rgba(0,0,0,0.1); cursor: ${window.isReadOnly ? 'default' : 'pointer'}; display: inline-block; position: relative;" ${!window.isReadOnly ? 'onclick="openVariableInputModal(\''+(fieldId + loopSuffix)+'\')"' : ''}>
                                    <div style="text-align: center; font-weight: bold; font-size: 12px; border-bottom: 1px dashed #111; padding-bottom: 4px; margin-bottom: 4px;">MÃ THIẾT BỊ: ${meta.deviceCode || '---'}</div>
                                    <div style="margin-bottom: 2px; white-space: normal;"><b>Tên TB:</b> ${meta.device}</div>
                                    <div style="margin-bottom: 2px;"><b>T.gian:</b> ${meta.at || ''}</div>
                                    <div style="margin-bottom: 2px;"><b>K.Lượng:</b> <span style="font-size: 14px; font-weight: bold;">${displayVal || ''} ${meta.unit || ''}</span></div>
                                    <div><b>Người Cân:</b> ${meta.by || ''}</div>
                                    ${scaleBtnHtml ? `<div style="position:absolute; top:-10px; right:-10px; z-index: 10;" onclick="event.stopPropagation();">${scaleBtnHtml}</div>` : ''}
                                </div>
                            `;
                            badge.className = 'ebmr-field-receipt ebmr-field-value';
                        } else {
                            badge.innerHTML =
                                `<span class="execution-input-test" ${!window.isReadOnly ? 'onclick="openVariableInputModal(\''+(fieldId + loopSuffix)+'\')"' : ''} style="cursor: ${window.isReadOnly ? 'default' : 'pointer'}; border-bottom: 1px dotted #1a73e8; min-width: 30px; display: inline-block; outline: none; position: relative; ${extraStyle}">${displayVal || `<span style="color: #6c757d; font-style: italic;">${placeholder}</span>`}</span>${scaleBtnHtml}`;
                            badge.className = 'ebmr-field-badge ebmr-field-value';
                        }
                    }

                    if (isNA) {
                        badge.className += ' cell-na-state strike-through-zone';
                        badge.style.pointerEvents = 'none';
                        badge.innerHTML = `<span style="opacity:0.4">${badge.innerHTML}</span>`;
                    }

                    if (metaHtml) {
                        const wrapper = document.createElement('div');
                        wrapper.className = 'ebmr-field-with-meta';
                        wrapper.style.display = 'inline-flex';
                        wrapper.style.flexDirection = 'column';
                        wrapper.style.alignItems = 'center';
                        wrapper.style.verticalAlign = 'middle';
                        wrapper.style.margin = '0 2px';

                        // Apply alignment config to the wrapper as well
                        if (field.style) {
                            if (field.style.badgeAlign) {
                                wrapper.style.setProperty('display', 'flex', 'important');
                                wrapper.style.setProperty('width', 'max-content', 'important');
                                if (field.style.badgeAlign === 'center') {
                                    wrapper.style.setProperty('margin-left', 'auto', 'important');
                                    wrapper.style.setProperty('margin-right', 'auto', 'important');
                                } else if (field.style.badgeAlign === 'right') {
                                    wrapper.style.setProperty('margin-left', 'auto', 'important');
                                    wrapper.style.setProperty('margin-right', '0', 'important');
                                } else {
                                    wrapper.style.setProperty('margin-left', '0', 'important');
                                    wrapper.style.setProperty('margin-right', 'auto', 'important');
                                }
                            } else if (field.style.marginLeft) {
                                wrapper.style.setProperty('margin-left', field.style.marginLeft, 'important');
                            }
                        }

                        // Move badge into wrapper
                        badge.parentNode.insertBefore(wrapper, badge);
                        wrapper.appendChild(badge);

                        // Add meta below badge
                        wrapper.insertAdjacentHTML('beforeend', metaHtml);
                    }
                }
                // TRƯỜNG HỢP 2: Chế độ THIẾT KẾ (Hiển thị badge kèm icon loại dữ liệu)
                else {
                    let icon = 'fa-edit';
                    let typeLabel = '';
                    let extra = '';

                    // Xác định Icon dựa theo loại trường (field type)
                    if (field.type === 'signature') {
                        if (field.is_checker) {
                            icon = 'fa-check-double text-warning';
                            typeLabel = 'Người kiểm tra';
                        } else {
                            icon = 'fa-signature';
                            typeLabel = 'Chữ ký';
                        }
                    } else if (field.type === 'date') {
                        icon = 'fa-clock';
                        typeLabel = 'Thời Gian';
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
                        const testResult = calculateFormula(field.formula || '', dPlaces, field.id);

                        // Resolve IDs to Labels for display
                        const formulaDisplay = (field.formula || '').replace(/\(([^()]+)\)/g, (match, id) => {
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
                    const scaleIndicator = (field.type === 'number' && field.scaleEnabled) ?
                        `<span class="ms-1 text-danger" title="⚖️ Đã bật kết nối Cân điện tử" style="cursor: pointer;" onclick="event.stopPropagation(); window.openScaleConnectionModal('${fieldId}')"><i class="fas fa-balance-scale"></i></span>` :
                        '';
                    badge.className =
                        `ebmr-field-badge ${selectedFieldId === fieldId ? 'active' : ''} ${field.type === 'formula' ? 'formula-preview' : ''}`;
                    badge.innerHTML = `
                        <span class="badge-drag-handle badge-left-handle" onmousedown="window.initBadgeResize(event, '${fieldId}', 'left')"></span>
                        <i class="fas ${icon}"></i> ${label}${extra}${scaleIndicator}
                        <span class="badge-drag-handle badge-right-handle" onmousedown="window.initBadgeResize(event, '${fieldId}', 'right')"></span>
                    `;
                }
            }
        });

        // Populate and validate result-inputs in execution mode
        const resultInputs = div.querySelectorAll('.result-input');
        resultInputs.forEach(inp => {
            const fieldId = inp.getAttribute('data-field-id');
            if (fieldId && window.executionValues) {
                let val = window.executionValues[fieldId] || '';
                // Handle nested structure if any
                if (val && typeof val === 'object' && val.hasOwnProperty('default')) {
                    val = val.default;
                } else if (val && typeof val === 'object' && !Array.isArray(val)) {
                    const keys = Object.keys(val);
                    if (keys.length > 0) val = val[keys[0]];
                }

                inp.setAttribute('value', val); // populate value in DOM

                if (window.isExecutionMode) {
                    // Activate execution class
                    inp.classList.remove('criteria-design-mode');
                    inp.classList.add('criteria-exec-mode');
                    inp.style.pointerEvents = 'auto';
                    inp.removeAttribute('readonly');

                    // If value is set, do validation
                    if (val !== '') {
                        const op = inp.getAttribute('data-criteria-op') || '';
                        const minAttr = inp.getAttribute('data-criteria-min') || '';
                        const maxAttr = inp.getAttribute('data-criteria-max') || '';
                        const min = parseFloat(minAttr);
                        const max = parseFloat(maxAttr);
                        const numVal = parseFloat(val);

                        let pass = false;
                        let isNumeric = !isNaN(numVal) && !isNaN(parseFloat(minAttr)) && op !== 'N/A';

                        if (isNumeric) {
                            if (op === 'range') {
                                pass = (numVal >= min && numVal <= max);
                            } else if (op === '±') {
                                pass = (numVal >= (min - max) && numVal <= (min + max));
                            } else if (op === '<') {
                                pass = (numVal < min);
                            } else if (op === '<=') {
                                pass = (numVal <= min);
                            } else if (op === '>') {
                                pass = (numVal > min);
                            } else if (op === '>=') {
                                pass = (numVal >= min);
                            } else if (op === '=' || op === '') {
                                pass = (numVal === min);
                            } else {
                                if (!isNaN(min) && !isNaN(max) && min !== max) {
                                    pass = (numVal >= min && numVal <= max);
                                } else if (!isNaN(min)) {
                                    pass = (numVal === min);
                                }
                            }
                        } else {
                            // Text validation (case-insensitive string comparison)
                            const minStr = minAttr.trim().toLowerCase();
                            const valStr = String(val).trim().toLowerCase();
                            if (minStr !== '') {
                                pass = (valStr === minStr);
                            } else {
                                return; // Không đủ thông tin
                            }
                        }

                        inp.classList.remove('criteria-pass', 'criteria-fail');
                        if (pass) {
                            inp.classList.add('criteria-pass');
                            inp.style.backgroundColor = '#d4edda';
                            inp.style.color = '#155724';
                            inp.style.borderColor = '#28a745';
                        } else {
                            inp.classList.add('criteria-fail');
                            inp.style.backgroundColor = '#f8d7da';
                            inp.style.color = '#721c24';
                            inp.style.borderColor = '#dc3545';
                        }
                    }
                } else {
                    // Design mode
                    inp.classList.remove('criteria-exec-mode');
                    inp.classList.add('criteria-design-mode');
                    inp.style.pointerEvents = 'none';
                    inp.setAttribute('readonly', 'true');
                }
            }
        });

        return div.innerHTML;
    }

    // Keep track of fields currently being calculated to prevent infinite loops (circular references)
    let calculatingFields = new Set();

    /**
     * Hàm helper để chuyển đổi chuỗi giá trị (có thể chứa dấu phẩy phân tách phần nghìn) sang Float một cách an toàn
     */
    const parseNumberSafe = function(val) {
        if (val === undefined || val === null || val === '') return 0;
        if (typeof val === 'number') return val;
        if (typeof val === 'boolean') return val ? 1 : 0;
        // Loại bỏ thẻ HTML và dấu phẩy phân cách phần nghìn
        const clean = String(val).replace(/<[^>]*>/g, '').replace(/,/g, '').trim();
        const parsed = parseFloat(clean);
        return isNaN(parsed) ? 0 : parsed;
    };

    window.showFormulaLimitWarning = function(el) {
        if (window.Swal) {
            Swal.fire({
                title: 'Cảnh báo giới hạn',
                html: `Giá trị tính toán: <b class="text-danger">${el.getAttribute('data-result')}</b><br>Nằm ngoài giới hạn cho phép: <b>${el.getAttribute('data-limit-info')}</b>`,
                icon: 'warning'
            });
        }
    };

    window.applyFormulaLimits = function(badge, field, resultStr) {
        badge.innerHTML = `<span style="color: #2563eb; font-weight: 500;">${resultStr}</span>`;
        if (window.isExecutionMode && field.validation) {
            const minStr = field.validation.min;
            const maxStr = field.validation.max;
            if ((minStr !== undefined && minStr !== null && minStr !== '') ||
                (maxStr !== undefined && maxStr !== null && maxStr !== '')) {

                const numericResult = parseFloat(String(resultStr).replace(/,/g, ''));
                if (!isNaN(numericResult)) {
                    let isOut = false;
                    let limitInfo = [];
                    if (minStr !== undefined && minStr !== null && minStr !== '') {
                        const min = parseFloat(minStr);
                        if (numericResult < min) isOut = true;
                        limitInfo.push(`Min: ${minStr}`);
                    }
                    if (maxStr !== undefined && maxStr !== null && maxStr !== '') {
                        const max = parseFloat(maxStr);
                        if (numericResult > max) isOut = true;
                        limitInfo.push(`Max: ${maxStr}`);
                    }

                    if (isOut) {
                        badge.setAttribute('title', 'Vượt giới hạn: ' + limitInfo.join(' - '));
                        badge.setAttribute('data-limit-info', limitInfo.join(' - '));
                        badge.setAttribute('data-result', resultStr);
                        badge.setAttribute('onclick', 'window.showFormulaLimitWarning(this)');
                        badge.style.cursor = 'pointer';
                        badge.innerHTML = `<span style="color: #dc3545; font-weight: bold;">${resultStr}</span>`;
                    } else {
                        badge.removeAttribute('title');
                        badge.removeAttribute('data-limit-info');
                        badge.removeAttribute('data-result');
                        badge.removeAttribute('onclick');
                        badge.style.cursor = '';
                    }
                }
            }
        }
    };

    /**

     * Tính toán giá trị của một công thức toán học.
     * Nó tự động tìm kiếm các giá trị từ các ô có ID hoặc các biến số được định nghĩa trong công thức.
     * @param {string} formula - Chuỗi công thức, ví dụ: "(kl_tong) - (kl_bao)".
     */
    window.calculateFormula = function(formula, decimalPlaces = 2, targetFieldId = null, loopSuffix = '') {
        if (!formula) return '0';

        if (targetFieldId) {
            if (calculatingFields.has(targetFieldId)) {
                return '0'; // Tránh lặp vô hạn do tham chiếu vòng
            }
            calculatingFields.add(targetFieldId);
        }

        let processed = formula;
        try {
            const valMap = {};
            const valArrayMap = {};
            const dPlaces = (decimalPlaces !== null && decimalPlaces !== '') ? parseInt(decimalPlaces) : 2;

            // BƯỚC 1: Thu thập giá trị từ các ô bảng (Table Cells) có đặt ID (cellId)
            items.forEach(item => {
                if (item.type === 'table' && item.data) {
                    const blockKey = (item.uuid || item.id) + loopSuffix;
                    const runDataForBlock = window.executionValues[blockKey] || {};
                    item.data.forEach((row, r) => {
                        row.forEach((cell, c) => {
                            if (cell && cell.cellId) {
                                // If in execution mode, prefer execution values from the looped block
                                let raw = undefined;
                                if (window.isExecutionMode) {
                                    raw = runDataForBlock[`${r}_${c}`];
                                }
                                if (raw === undefined || raw === null || raw === '') {
                                    raw = (cell.defaultValue !== undefined && cell
                                        .defaultValue !== '') ? cell.defaultValue : (
                                        cell.content || '0');
                                }
                                const parsedVal = parseNumberSafe(raw);
                                valMap[cell.cellId] = parsedVal;
                                valMap[cell.cellId + loopSuffix] = parsedVal;

                                if (!valArrayMap[cell.cellId]) valArrayMap[cell
                                    .cellId] = [];
                                valArrayMap[cell.cellId].push(parsedVal);
                            }
                        });
                    });
                }
            });

            // BƯỚC 2: Thu thập giá trị từ các "Trường động" (Dynamic Fields) theo Tên hoặc Nhãn
            Object.values(fieldsConfig).forEach(field => {
                if (field.label || field.name) {
                    // CẢI TIẾN HIỆU SUẤT: Tránh vòng lặp O(N!) bằng cách chỉ tính toán giá trị của biến số 
                    // nếu tên hoặc nhãn của nó CÓ XUẤT HIỆN trong chuỗi công thức hiện tại.
                    let isUsed = false;
                    const loopedName = field.name ? field.name + loopSuffix : null;
                    const loopedLabel = field.label ? field.label + loopSuffix : null;

                    if (field.name) {
                        if (formula.includes(field.name)) isUsed = true;
                        else if (field.sum_group && formula.includes(field.sum_group)) isUsed = true;
                    }
                    if (!isUsed && loopedName) {
                        if (formula.includes(loopedName)) isUsed = true;
                        else if (field.sum_group && formula.includes(field.sum_group + loopSuffix)) isUsed =
                            true;
                    }
                    if (!isUsed && field.label && formula.includes(field.label)) isUsed = true;
                    if (!isUsed && loopedLabel && formula.includes(loopedLabel)) isUsed = true;

                    if (!isUsed) return; // BỎ QUA nều biến này không được dùng trong công thức!

                    let val = 0;
                    const loopedFieldId = field.id + loopSuffix;
                    if (field.type === 'formula') {
                        // Nếu là trường công thức khác, tính toán đệ quy
                        if (calculatingFields.has(loopedFieldId)) {
                            val = 0; // Tránh lặp vô hạn
                        } else {
                            val = window.calculateFormula(field.formula || '', field.validation ? field
                                .validation.decimal_places : 2, loopedFieldId, loopSuffix);
                        }
                    } else if (window.isExecutionMode && window.executionValues && window.executionValues[
                            loopedFieldId] !== undefined) {
                        let raw = window.executionValues[loopedFieldId];
                        if (raw && typeof raw === 'object' && raw.hasOwnProperty('default')) {
                            val = raw.default;
                        } else if (raw && typeof raw === 'object' && !Array.isArray(raw)) {
                            const keys = Object.keys(raw);
                            if (keys.length > 0) val = raw[keys[0]];
                        } else {
                            val = raw;
                        }
                    } else {
                        val = (field.defaultValue !== undefined && field.defaultValue !== '') ? field
                            .defaultValue : 0;
                    }

                    const parsedVal = parseNumberSafe(val);
                    if (field.label) {
                        valMap[field.label] = parsedVal;
                        valMap[field.label + loopSuffix] = parsedVal;
                        if (!valArrayMap[field.label]) valArrayMap[field.label] = [];
                        valArrayMap[field.label].push(parsedVal);
                    }
                    if (field.name) {
                        valMap[field.name] = parsedVal;
                        valMap[field.name + loopSuffix] = parsedVal;
                        if (!valArrayMap[field.name]) valArrayMap[field.name] = [];
                        valArrayMap[field.name].push(parsedVal);

                        // Sử dụng sum_group để nhóm chính xác các biến nhân bản
                        if (field.sum_group && field.sum_group !== field.name) {
                            if (!valArrayMap[field.sum_group]) valArrayMap[field.sum_group] = [];
                            valArrayMap[field.sum_group].push(parsedVal);
                        }
                    }
                }
            });

            // BƯỚC 2.5: Xử lý các hàm tổng hợp _ALL
            const aggregateRegex = /(SUM_ALL|AVG_ALL|MAX_ALL|MIN_ALL)\(\s*\(*(.*?)\)*\s*\)/gi;
            processed = processed.replace(aggregateRegex, (match, funcRaw, id) => {
                const func = funcRaw.toUpperCase();
                const trimmedId = id.trim();
                let arr = valArrayMap[trimmedId] || [];

                // Cải tiến: Nếu mảng chỉ có 1 phần tử (do user click trực tiếp vào biến KL thực1 thay vì KL thực), 
                // thử tìm xem nó có thuộc sum_group nào không để gom nhóm lại.
                if (arr.length <= 1) {
                    const field = Object.values(fieldsConfig).find(f => f.name === trimmedId || f.label ===
                        trimmedId);
                    if (field && field.sum_group && valArrayMap[field.sum_group] && valArrayMap[field
                            .sum_group].length > arr.length) {
                        arr = valArrayMap[field.sum_group];
                    }
                }

                if (arr.length === 0) return '0';

                if (func === 'SUM_ALL') return `(${arr.join(' + ')})`;
                if (func === 'AVG_ALL') {
                    const sum = arr.reduce((a, b) => a + b, 0);
                    return `(${sum} / ${arr.length})`;
                }
                if (func === 'MAX_ALL') return `Math.max(${arr.join(', ')})`;
                if (func === 'MIN_ALL') return `Math.min(${arr.join(', ')})`;

                return '0';
            });

            // BƯỚC 3: Thay thế các định danh trong công thức bằng giá trị thực tế
            // Ví dụ: "(kl_tong) - (kl_bao)" -> "100 - 5"
            processed = processed.replace(/\(([^()]+)\)/g, (match, id) => {
                const trimmedId = id.trim();
                const loopedKey = trimmedId + loopSuffix;
                if (valMap[loopedKey] !== undefined) {
                    return valMap[loopedKey];
                }
                if (valMap[trimmedId] !== undefined) {
                    return valMap[trimmedId];
                }
                // Nếu không có trong valMap, giữ nguyên (vì có thể là biểu thức toán học như (5 + 2) hoặc (SUM_ALL...))
                return match;
            });

            // BƯỚC 4: Tính toán biểu thức toán học cơ bản bằng hàm Function (an toàn hơn eval một chút)
            const result = new Function(`
                const MAX = Math.max; const max = Math.max;
                const MIN = Math.min; const min = Math.min;
                const SUM = function(...args) { return args.reduce((a,b)=>a+b,0); }; const sum = SUM;
                const AVG = function(...args) { return args.length ? args.reduce((a,b)=>a+b,0)/args.length : 0; }; const avg = AVG;
                const ROUND = function(val, dec) { const p = Math.pow(10, dec||0); return Math.round(val * p) / p; }; const round = ROUND;
                return ${processed};
            `)();
            // Định dạng kết quả hiển thị (phân tách hàng nghìn, số chữ số thập phân theo thiết lập)
            return (typeof result === 'number') ? result.toLocaleString('en-US', {
                minimumFractionDigits: dPlaces,
                maximumFractionDigits: dPlaces
            }) : result;
        } catch (e) {
            // Không in ra console.error để tránh làm người dùng hoang mang khi họ gõ sai công thức
            // console.warn('Cú pháp công thức chưa hoàn thiện hoặc bị sai:', formula);
            return '#ERR'; // Trả về lỗi nếu công thức không hợp lệ
        } finally {
            if (targetFieldId) {
                calculatingFields.delete(targetFieldId);
            }
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
                const loopedFieldId = badge.getAttribute('data-field-id'); // e.g. field_xxx_loop_1
                // Extract original field ID and loop suffix
                let fieldId = loopedFieldId;
                let loopSuffix = '';
                const loopMatch = loopedFieldId.match(/(.+)(_loop_\d+)$/);
                if (loopMatch) {
                    fieldId = loopMatch[1];
                    loopSuffix = loopMatch[2];
                }
                const field = fieldsConfig[fieldId];
                if (field && field.type === 'formula') {
                    const dPlaces = (field.validation && field.validation.decimal_places !== null) ? field
                        .validation.decimal_places : 2;
                    const resultStr = calculateFormula(field.formula || '', dPlaces, loopedFieldId,
                        loopSuffix);
                    applyFormulaLimits(badge, field, resultStr);
                    if (badge.hasAttribute('data-limit-info')) {
                        badge.classList.add('formula-out-of-bounds');
                    } else {
                        badge.classList.remove('formula-out-of-bounds');
                    }
                }
            }
        });

        if (typeof window.evaluateAllConditions === 'function') {
            window.evaluateAllConditions();
        }
    };

    window.evaluateAllConditions = function() {
        if (!window.isExecutionMode) return;
        let hasChanges = false;

        Object.values(fieldsConfig).forEach(field => {
            if (field.na_condition && field.na_condition.target_id) {
                const targetId = field.na_condition.target_id;
                const operator = field.na_condition.operator || '=';
                const targetValue = field.na_condition.value;

                let runVal = null;
                const searchId = String(targetId).trim().toLowerCase();
                const targetField = Object.values(fieldsConfig).find(f =>
                    String(f.id).trim().toLowerCase() === searchId ||
                    String(f.name).trim().toLowerCase() === searchId ||
                    String(f.label || '').trim().toLowerCase() === searchId
                );

                if (targetField) {
                    if (window.executionValues[targetField.id] !== undefined) {
                        runVal = window.executionValues[targetField.id];
                    } else if (targetField.defaultValue !== undefined && targetField.defaultValue !==
                        null) {
                        runVal = targetField.defaultValue;
                    }
                }

                if (runVal === null || runVal === undefined) {
                    items.forEach(item => {
                        if (item.type === 'table' && item.data) {
                            const blockKey = item.uuid || item.id;
                            for (let tr = 0; tr < item.rows; tr++) {
                                for (let tc = 0; tc < item.cols; tc++) {
                                    if (item.data[tr][tc] && String(item.data[tr][tc].cellId || '')
                                        .trim().toLowerCase() === searchId) {
                                        if (window.executionValues[blockKey] && window
                                            .executionValues[blockKey][`${tr}_${tc}`] !== undefined
                                        ) {
                                            runVal = window.executionValues[blockKey][
                                                `${tr}_${tc}`
                                            ];
                                        } else if (item.data[tr][tc].defaultValue !== undefined) {
                                            runVal = item.data[tr][tc].defaultValue;
                                        } else {
                                            // Fallback to text content if no value set
                                            const tempDiv = document.createElement('div');
                                            tempDiv.innerHTML = item.data[tr][tc].content || '';
                                            runVal = tempDiv.textContent.trim();
                                        }
                                    }
                                }
                            }
                        }
                    });
                }

                if (runVal && typeof runVal === 'object' && runVal.hasOwnProperty('default')) {
                    runVal = runVal.default;
                } else if (runVal && typeof runVal === 'object' && !Array.isArray(runVal)) {
                    const keys = Object.keys(runVal);
                    if (keys.length > 0) runVal = runVal[keys[0]];
                }

                if (runVal !== null && runVal !== undefined) {
                    let conditionMet = false;

                    // Xử lý giá trị cho checkbox (boolean)
                    let runValStr = String(runVal).trim().toLowerCase();
                    let targetValStr = String(targetValue || '').trim().toLowerCase();

                    console.log(`[evaluateAllConditions] ID: ${field.id}, targetId: ${targetId}, runVal:`,
                        runVal, `targetValue:`, targetValue);

                    if (typeof runVal === 'boolean') {
                        runValStr = runVal ? 'true' : 'false';
                    }
                    if (targetValStr === '1' || targetValStr === 'yes' || targetValStr === 'có')
                        targetValStr = 'true';
                    if (targetValStr === '0' || targetValStr === 'no' || targetValStr === 'không')
                        targetValStr = 'false';
                    if (runValStr === '1' || runValStr === 'yes' || runValStr === 'có') runValStr = 'true';
                    if (runValStr === '0' || runValStr === 'no' || runValStr === 'không') runValStr =
                        'false';

                    console.log(
                        `[evaluateAllConditions] runValStr: ${runValStr}, targetValStr: ${targetValStr}, operator: ${operator}`
                    );

                    if (operator === '=') {
                        conditionMet = (runValStr === targetValStr);
                    } else if (operator === '!=') {
                        conditionMet = (runValStr !== targetValStr);
                    }

                    const naKey = `_na_state_${field.id}`;
                    const isCurrentlyNA = window.executionValues[naKey];

                    if (conditionMet && !isCurrentlyNA) {
                        const user =
                            '{{ session('user.fullName') ?? (session('user.username') ?? 'Hệ thống (Auto)') }}';
                        window.executionValues[naKey] = true;
                        window.executionValues[`${naKey}_meta`] = {
                            by: user,
                            at: new Date().toLocaleString('vi-VN'),
                            reason: 'ĐK Không áp dụng'
                        };
                        hasChanges = true;
                    } else if (!conditionMet && isCurrentlyNA) {
                        window.executionValues[naKey] = false;
                        hasChanges = true;
                    }
                }
            }
        });

        if (hasChanges) {
            renderBlocks();
        }
    };

    window.activeSectionLoopIndices = {};
    window.switchSectionLoopTab = function(sectionId, loopIdx) {
        window.activeSectionLoopIndices[sectionId] = loopIdx;

        // Hide all tab contents for this section
        const contents = document.querySelectorAll(`.section-loop-tab-content-${sectionId}`);
        contents.forEach(content => {
            if (parseInt(content.getAttribute('data-loop-idx')) === loopIdx) {
                content.style.display = 'block';
            } else {
                content.style.display = 'none';
            }
        });

        // Update active class on tab buttons
        const header = document.querySelector(`.section-loop-tabs-header-${sectionId}`);
        if (header) {
            const buttons = header.querySelectorAll('.btn');
            buttons.forEach(btn => {
                if (parseInt(btn.getAttribute('data-loop-idx')) === loopIdx) {
                    btn.classList.add('active');
                } else {
                    btn.classList.remove('active');
                }
            });
        }
    };

    window.activeBlockLoopIndices = {};
    window.switchBlockLoopTab = function(loopGroupId, loopIdx) {
        if (!window.activeBlockLoopIndices) window.activeBlockLoopIndices = {};
        window.activeBlockLoopIndices[loopGroupId] = loopIdx;

        // Hide all tab contents for this group
        const contents = document.querySelectorAll(`.block-loop-tab-content-${loopGroupId}`);
        contents.forEach(content => {
            if (parseInt(content.getAttribute('data-loop-idx')) === loopIdx) {
                content.style.display = 'block';
            } else {
                content.style.display = 'none';
            }
        });

        // Update active class on tab buttons
        const header = document.querySelector(`.block-loop-tabs-header-${loopGroupId}`);
        if (header) {
            const buttons = header.querySelectorAll('.btn');
            buttons.forEach(btn => {
                if (parseInt(btn.getAttribute('data-loop-idx')) === loopIdx) {
                    btn.classList.add('active');
                } else {
                    btn.classList.remove('active');
                }
            });
        }
    };
</script>
