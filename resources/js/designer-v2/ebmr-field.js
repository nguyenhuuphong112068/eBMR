/**
 * EbmrField — Custom TipTap Node cho BIẾN SỐ eBMR.
 *
 * Nguyên tắc thiết kế:
 *  - Node atom (nguyên tử): không thể đặt caret vào trong, Backspace xóa nguyên
 *    badge, copy/paste giữ nguyên attrs — thay thế hoàn toàn hack ​ +
 *    execCommand('insertHTML') của trình soạn thảo cũ.
 *  - parseHTML đọc được đúng markup cũ (<span class="ebmr-field-badge"
 *    data-field-id="...">) nên nội dung đang lưu trong DB mở lên là tự chuyển
 *    đổi, KHÔNG cần migration.
 *  - renderHTML xuất ra lại đúng markup cũ, nên nội dung lưu từ V2 vẫn mở được
 *    bằng trình soạn thảo hiện tại và execution mode (round-trip an toàn).
 *  - Metadata biến (label, type, validation...) vẫn nằm trong registry
 *    fieldsConfig bên ngoài — node chỉ giữ con trỏ fieldId, y hệt mô hình cũ.
 */
import { Node } from '@tiptap/core';

// Icon + tên loại biến, khớp bộ type của trình soạn thảo hiện tại
export const FIELD_TYPES = {
    text: { label: 'Văn bản', icon: 'fa-font' },
    number: { label: 'Số', icon: 'fa-hashtag' },
    date: { label: 'Thời Gian', icon: 'fa-calendar-alt' },
    signature: { label: 'Chữ ký', icon: 'fa-signature' },
    checkbox: { label: 'Tick', icon: 'fa-check-square' },
    select: { label: 'Lựa chọn', icon: 'fa-list-ul' },
    formula: { label: 'Công thức', icon: 'fa-square-root-alt' },
};

export const EbmrField = Node.create({
    name: 'ebmrField',

    group: 'inline',
    inline: true,
    atom: true,
    selectable: true,
    draggable: true,

    addAttributes() {
        return {
            fieldId: { default: null },
        };
    },

    parseHTML() {
        return [
            {
                tag: 'span.ebmr-field-badge',
                getAttrs: (el) => ({ fieldId: el.getAttribute('data-field-id') }),
            },
        ];
    },

    // Xuất HTML tương thích ngược với trình soạn thảo cũ + execution mode.
    renderHTML({ node }) {
        return [
            'span',
            {
                class: 'ebmr-field-badge',
                'data-field-id': node.attrs.fieldId,
                contenteditable: 'false',
            },
        ];
    },

    // NodeView: badge hiển thị label + icon (thiết kế) hoặc control tương tác (chạy thử).
    addNodeView() {
        return ({ node, editor }) => {
            const dom = document.createElement('span');
            dom.className = 'v2-field-badge';
            dom.setAttribute('data-field-id', node.attrs.fieldId || '');

            const fieldId = node.attrs.fieldId;
            const paint = () => paintFieldElement(dom, fieldId);
            paint();
            window.__V2__?.registerFieldPaint?.(fieldId, paint);

            dom.addEventListener('click', (e) => handleFieldBadgeClick(e, fieldId, paint));

            return {
                dom,
                update(updated) {
                    if (updated.type.name !== 'ebmrField') return false;
                    paint();
                    return true;
                },
                destroy() {
                    window.__V2__?.unregisterFieldPaint?.(fieldId, paint);
                },
            };
        };
    },
});

/* ============================================================
 * Bộ vẽ + xử lý click DÙNG CHUNG cho badge biến số.
 * Được gọi từ 2 nơi: NodeView TipTap (block đang mount editor)
 * và badge TĨNH trong main.js (block không mount — toàn bộ tài
 * liệu khi ở chế độ Chạy thử render tĩnh, không qua TipTap).
 * ============================================================ */

// Đường viền đỏ khi giá trị số/công thức vượt min-max (khi validation không cho phép out-of-bounds)
function isOutOfBounds(cfg, numVal) {
    if (numVal === null || numVal === undefined || isNaN(numVal)) return false;
    const v = cfg.validation || {};
    if (v.allow_out_of_bounds) return false;
    if (v.min !== null && v.min !== undefined && v.min !== '' && numVal < Number(v.min)) return true;
    if (v.max !== null && v.max !== undefined && v.max !== '' && numVal > Number(v.max)) return true;
    return false;
}

function buildMetaHtml(fieldId) {
    const rec = (window.__V2__?.executionValues || {})[fieldId];
    const meta = rec && rec._meta && rec._meta.default;
    if (!meta || (!meta.by && !meta.at)) return '';
    let historyBadge = '';
    if (meta.history_count > 0) {
        historyBadge = `<span class="v2-exec-history-pill" onclick="event.stopPropagation(); window.__V2__.showFieldHistory('${fieldId}')">Lịch sử (${meta.history_count})</span>`;
    }
    return `<div class="v2-exec-meta">${escapeHtml(meta.at || '')} · ${escapeHtml(meta.by || '')} ${historyBadge}</div>`;
}

export function paintFieldElement(dom, fieldId) {
    const cfg = (window.__V2__?.fieldsConfig || {})[fieldId] || {};

    if (window.__V2__?.isExecutionMode) {
        dom.classList.add('v2-field-exec');
        let val = (window.__V2__?.executionValues || {})[fieldId]?.default;
        if (val === undefined || val === null || val === '') val = cfg.defaultValue || '';

        let valueHtml = '';
        let metaHtml = buildMetaHtml(fieldId);
        // "Hiển thị nhãn khi chạy thử" (mặc định bật). Tắt → ẩn text nhãn của
        // checkbox và placeholder gợi ý của ô nhập text/số/thời gian.
        const showLabel = cfg.showLabel !== false;

        if (cfg.type === 'checkbox') {
            let isChecked = val === true || val === 'true' || val === '1' || val === 'yes' || val === 'có';
            let locked = false;
            if (cfg.formula) {
                const result = window.__V2__?.calculateFormula ? window.__V2__.calculateFormula(cfg.formula, 2, fieldId) : '0';
                isChecked = parseFloat(String(result).replace(/,/g, '')) > 0;
                locked = true;
            }
            valueHtml =
                `<span class="v2-exec-checkbox${locked ? ' is-locked' : ''}${isChecked ? ' is-checked' : ''}" title="${locked ? 'Tự động theo công thức' : 'Click để tick'}">` +
                `<span class="v2-exec-checkbox-box"><i class="fas fa-check"></i></span>` +
                (showLabel ? `<span class="v2-exec-checkbox-text">${escapeHtml(cfg.label || '')}</span>` : '') +
                `</span>`;
            metaHtml = locked ? '' : metaHtml;
        } else if (cfg.type === 'select') {
            const optionsFromCfg = Array.isArray(cfg.options)
                ? cfg.options
                : (typeof cfg.options === 'string' ? cfg.options.split(/[,;\n]/).map((o) => o.trim()).filter(Boolean) : []);
            const dsType = cfg.dataSource && cfg.dataSource.type;
            if (dsType === 'database') {
                valueHtml = `<select class="v2-exec-select" onchange="window.__V2__.handleSelectChange('${fieldId}', this.value)"><option value="">-- Đang tải... --</option></select>`;
            } else {
                valueHtml =
                    `<select class="v2-exec-select" onchange="window.__V2__.handleSelectChange('${fieldId}', this.value)">` +
                    `<option value="">--</option>` +
                    optionsFromCfg.map((o) => `<option value="${escapeHtml(o)}" ${val === o ? 'selected' : ''}>${escapeHtml(o)}</option>`).join('') +
                    `</select>`;
            }
        } else if (cfg.type === 'signature') {
            metaHtml = ''; // chữ ký tự có dòng "đã ký bởi" riêng, không lặp lại meta chung
            const isChecker = !!cfg.is_checker;
            if (val) {
                if (String(val).startsWith('data:image/')) {
                    valueHtml = `<img src="${val}" style="max-height:30px; vertical-align:middle;">`;
                } else {
                    valueHtml = `<span class="badge bg-light text-success border"><i class="fas fa-check-circle me-1"></i>${escapeHtml(val)}</span>`;
                }
            } else if (isChecker) {
                valueHtml = `<span class="badge bg-light text-warning border border-warning"><i class="fas fa-check-double me-1"></i> [Người kiểm tra]</span>`;
            } else {
                valueHtml = `<span class="badge bg-light text-primary border"><i class="fas fa-signature me-1"></i> [Ký tên]</span>`;
            }
            const rec = (window.__V2__?.executionValues || {})[fieldId];
            const meta = rec && rec._meta && rec._meta.default;
            if (val && meta && meta.at) {
                metaHtml = `<div class="v2-exec-meta">${escapeHtml(meta.at)} · ${escapeHtml(meta.by || '')}</div>`;
            }
        } else if (cfg.type === 'formula') {
            const dPlaces = (cfg.validation && cfg.validation.decimal_places !== null && cfg.validation.decimal_places !== undefined) ? cfg.validation.decimal_places : 2;
            const result = window.__V2__?.calculateFormula ? window.__V2__.calculateFormula(cfg.formula || '', dPlaces, fieldId) : '0';
            const numResult = parseFloat(String(result).replace(/,/g, ''));
            valueHtml = `<span class="v2-exec-formula-result${isOutOfBounds(cfg, numResult) ? ' out-of-bounds' : ''}">${escapeHtml(String(result))}</span>`;
            metaHtml = ''; // công thức không phải do người điền — không có meta người/giờ
        } else if (cfg.type === 'date') {
            const isNow = cfg.autoSystemTime !== false;
            const ph = showLabel ? (cfg.label || 'Chọn thời gian...') : 'Chọn thời gian...';
            valueHtml = `<span class="v2-exec-input" title="${isNow ? 'Click để tự động điền ngày giờ hệ thống' : ''}">${val ? escapeHtml(val) : `<span class="v2-exec-placeholder">${escapeHtml(ph)}</span>`}</span>`;
        } else {
            // text / number
            const numVal = cfg.type === 'number' ? parseFloat(val) : NaN;
            const ph = showLabel ? (cfg.label || '...') : '...';
            const scaleBtnHtml = (cfg.type === 'number' && cfg.scaleEnabled)
                ? `<button type="button" class="btn-read-scale" onclick="event.stopPropagation(); window.__V2__.readScaleValueIntoField('${fieldId}')" title="⚖️ Đọc giá trị từ Cân điện tử (RS-232)"><i class="fas fa-balance-scale"></i></button>`
                : '';

            const scaleMeta = (window.__V2__?.executionValues || {})[fieldId]?._meta?.default;
            if (cfg.type === 'number' && cfg.scaleEnabled && scaleMeta && scaleMeta.device && val !== '' && val !== undefined && val !== null) {
                // Giá trị đến từ Cân điện tử: hiển thị dạng "giấy cân" (phiếu in nhiệt), giống trình soạn thảo V1
                valueHtml = `
                    <div class="v2-scale-receipt" onclick="window.__V2__.openExecutionModal('${fieldId}')">
                        <div class="v2-scale-receipt-head">MÃ THIẾT BỊ: ${escapeHtml(scaleMeta.deviceCode || '---')}</div>
                        <div><b>Tên TB:</b> ${escapeHtml(scaleMeta.device || '')}</div>
                        <div><b>T.gian:</b> ${escapeHtml(scaleMeta.at || '')}</div>
                        <div><b>K.Lượng:</b> <span class="v2-scale-receipt-weight">${escapeHtml(String(val))} ${escapeHtml(scaleMeta.unit || '')}</span></div>
                        <div><b>Người Cân:</b> ${escapeHtml(scaleMeta.by || '')}</div>
                        ${scaleBtnHtml ? `<div class="v2-scale-receipt-btn" onclick="event.stopPropagation();">${scaleBtnHtml}</div>` : ''}
                    </div>`;
                metaHtml = ''; // meta đã nhúng sẵn trong phiếu, không lặp dòng người/giờ chung nữa
            } else {
                // Biến số kết nối Cân điện tử nhưng chưa có giá trị: nút ⚖️ mở modal kết nối cân riêng,
                // không lẫn với modal nhập liệu thường (giống trình soạn thảo V1).
                valueHtml = `<span class="v2-exec-input${isOutOfBounds(cfg, numVal) ? ' out-of-bounds' : ''}">${val ? escapeHtml(val) : `<span class="v2-exec-placeholder">${escapeHtml(ph)}</span>`}</span>${scaleBtnHtml}`;
            }
        }

        dom.innerHTML = valueHtml + metaHtml;
        if (cfg.type !== 'signature' && cfg.type !== 'formula') {
            dom.title = cfg.type === 'checkbox' ? 'Click để tick (Chạy thử)' : 'Click để nhập liệu (Chạy thử)';
        } else if (cfg.type === 'signature') {
            dom.title = cfg.is_checker ? 'Click để xác thực người kiểm tra' : 'Click để ký (Chạy thử)';
        } else {
            dom.title = '';
        }

        // Select với nguồn database: tải option 1 lần rồi bơm vào (giữ cache ở main.js)
        if (cfg.type === 'select' && cfg.dataSource && cfg.dataSource.type === 'database') {
            window.__V2__?.loadDynamicSelectOptions?.(cfg, (options) => {
                const sel = dom.querySelector('select.v2-exec-select');
                if (!sel) return;
                sel.innerHTML = '<option value="">--</option>' +
                    options.map((o) => `<option value="${escapeHtml(o.value)}" ${String(val) === String(o.value) ? 'selected' : ''}>${escapeHtml(o.label)}</option>`).join('');
            });
        }
    } else {
        // Chế độ thiết kế
        dom.classList.remove('v2-field-exec');
        const t = FIELD_TYPES[cfg.type] || FIELD_TYPES.text;
        let extra = '';
        if (cfg.type === 'formula' && cfg.formula) {
            const dPlaces = (cfg.validation && cfg.validation.decimal_places !== null && cfg.validation.decimal_places !== undefined) ? cfg.validation.decimal_places : 2;
            const result = window.__V2__?.calculateFormula ? window.__V2__.calculateFormula(cfg.formula, dPlaces, fieldId) : '0';
            extra = ` <span class="v2-formula-preview">= ${escapeHtml(String(result))}</span>`;
        }
        dom.innerHTML =
            `<i class="fas ${t.icon} me-1" style="font-size:0.7em;"></i>` +
            `<span>${escapeHtml(cfg.label || cfg.name || fieldId || '?')}</span>${extra}`;
        dom.title = `${t.label}${cfg.name ? ' — ' + cfg.name : ''}`;
    }
}

export function handleFieldBadgeClick(e, fieldId, paint) {
    if (e.target.closest('select.v2-exec-select')) return; // để trình duyệt tự mở dropdown
    e.preventDefault();

    if (window.__V2__?.isExecutionMode) {
        const cfg = (window.__V2__?.fieldsConfig || {})[fieldId] || {};
        if (cfg.type === 'select') return; // tương tác qua onchange của <select>, không qua click
        if (cfg.type === 'formula') return; // chỉ đọc

        if (cfg.type === 'checkbox') {
            window.__V2__?.toggleExecutionCheckbox?.(fieldId);
        } else if (cfg.type === 'signature') {
            if (cfg.is_checker) window.__V2__?.openCheckerAuthModal?.(fieldId);
            else window.__V2__?.openSignatureModal?.(fieldId);
        } else if (cfg.type === 'date' && cfg.autoSystemTime !== false) {
            window.__V2__?.autoFillExecutionDate?.(fieldId);
        } else {
            window.__V2__?.openExecutionModal?.(fieldId);
        }
    } else if (typeof window.__V2__?.openFieldPanel === 'function') {
        window.__V2__.openFieldPanel(fieldId, paint);
    }
}

function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, (c) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
    }[c]));
}
