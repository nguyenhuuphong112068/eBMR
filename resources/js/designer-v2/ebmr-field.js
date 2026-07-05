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

    // NodeView: badge hiển thị label + icon lấy từ fieldsConfig (registry ngoài).
    addNodeView() {
        return ({ node, editor }) => {
            const dom = document.createElement('span');
            dom.className = 'v2-field-badge';
            dom.setAttribute('data-field-id', node.attrs.fieldId || '');

            const paint = () => {
                const cfg = (window.__V2__?.fieldsConfig || {})[node.attrs.fieldId] || {};
                
                if (window.__V2__?.isExecutionMode) {
                    // Chế độ chạy thử: Render giá trị thực tế
                    let val = (window.__V2__?.executionValues || {})[node.attrs.fieldId]?.default;
                    if (val === undefined || val === null || val === '') val = cfg.defaultValue || '';
                    
                    if (cfg.type === 'checkbox') {
                        const isChecked = val === true || val === 'true' || val === '1' || val === 'yes' || val === 'có';
                        dom.innerHTML = `<span style="display:inline-flex; align-items:center; gap:4px;"><input type="checkbox" ${isChecked ? 'checked' : ''} onclick="event.preventDefault()"><span>${escapeHtml(cfg.label || '')}</span></span>`;
                    } else if (cfg.type === 'signature') {
                        if (val) {
                            if (String(val).startsWith('data:image/')) dom.innerHTML = `<img src="${val}" style="max-height:30px; vertical-align:middle;">`;
                            else dom.innerHTML = `<span class="badge bg-light text-success border"><i class="fas fa-check-circle me-1"></i>${escapeHtml(val)}</span>`;
                        } else {
                            dom.innerHTML = `<span class="badge bg-light text-primary border"><i class="fas fa-signature me-1"></i> [Ký tên]</span>`;
                        }
                    } else {
                        dom.innerHTML = val ? `<span>${escapeHtml(val)}</span>` : `<span style="opacity:0.3; font-style:italic;">[Nhập dữ liệu]</span>`;
                    }
                    dom.title = "Click để nhập liệu (Chạy thử)";
                } else {
                    // Chế độ thiết kế
                    const t = FIELD_TYPES[cfg.type] || FIELD_TYPES.text;
                    dom.innerHTML =
                        `<i class="fas ${t.icon} me-1" style="font-size:0.7em;"></i>` +
                        `<span>${escapeHtml(cfg.label || cfg.name || node.attrs.fieldId || '?')}</span>`;
                    dom.title = `${t.label}${cfg.name ? ' — ' + cfg.name : ''}`;
                }
            };
            paint();

            dom.addEventListener('click', (e) => {
                e.preventDefault();
                if (window.__V2__?.isExecutionMode) {
                    if (typeof window.__V2__?.openExecutionModal === 'function') {
                        window.__V2__.openExecutionModal(node.attrs.fieldId, paint);
                    }
                } else {
                    if (typeof window.__V2__?.openFieldPanel === 'function') {
                        window.__V2__.openFieldPanel(node.attrs.fieldId, paint);
                    }
                }
            });

            return {
                dom,
                update(updated) {
                    if (updated.type.name !== 'ebmrField') return false;
                    paint();
                    return true;
                },
            };
        };
    },
});

function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, (c) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
    }[c]));
}
