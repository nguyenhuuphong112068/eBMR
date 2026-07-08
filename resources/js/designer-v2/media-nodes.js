/**
 * Node TipTap tùy biến cho 2 loại nội dung "giống Word": Công thức toán học (KaTeX)
 * và Hình ảnh chèn trực tiếp (base64). Theo đúng mô hình của EbmrField (ebmr-field.js):
 * node atom, parseHTML đọc lại đúng markup cũ để round-trip an toàn giữa các lần mở.
 */
import { Node } from '@tiptap/core';
import katex from 'katex';

/** Vẽ KaTeX vào 1 badge tĩnh (.v2-equation-badge) — dùng chung cho decorateBadges() ở main.js */
export function paintEquationBadge(el) {
    const latex = el.getAttribute('data-latex') || '';
    try {
        el.innerHTML = katex.renderToString(latex, { throwOnError: false });
    } catch (e) { el.textContent = latex; }
}

export const MathEquation = Node.create({
    name: 'mathEquation',
    group: 'inline',
    inline: true,
    atom: true,
    selectable: true,
    draggable: true,

    addAttributes() {
        return {
            latex: { default: '' },
        };
    },

    parseHTML() {
        return [
            {
                tag: 'span.v2-equation-badge',
                getAttrs: (el) => ({ latex: el.getAttribute('data-latex') || '' }),
            },
        ];
    },

    // Chỉ xuất markup rỗng tối thiểu (round-trip an toàn) — nội dung KaTeX được vẽ
    // sau đó bằng JS (NodeView khi đang mount editor, activateStaticEquations() khi tĩnh),
    // vì DOMOutputSpec của ProseMirror không hỗ trợ chèn HTML thô qua renderHTML.
    renderHTML({ node }) {
        return ['span', { class: 'v2-equation-badge', 'data-latex': node.attrs.latex || '', contenteditable: 'false' }];
    },

    addNodeView() {
        return ({ node, editor, getPos }) => {
            const dom = document.createElement('span');
            dom.className = 'v2-equation-badge';
            dom.setAttribute('data-latex', node.attrs.latex || '');
            dom.title = 'Nhấp đôi để sửa công thức';

            const paint = () => {
                try {
                    dom.innerHTML = katex.renderToString(node.attrs.latex || '', { throwOnError: false });
                } catch (e) { dom.textContent = node.attrs.latex || ''; }
            };
            paint();

            dom.addEventListener('dblclick', (e) => {
                e.preventDefault();
                e.stopPropagation();
                window.__V2__?.openEquationEditor?.(node.attrs.latex || '', (newLatex) => {
                    if (typeof getPos === 'function') {
                        editor.chain().focus().command(({ tr }) => {
                            tr.setNodeMarkup(getPos(), undefined, { latex: newLatex });
                            return true;
                        }).run();
                    }
                });
            });

            return {
                dom,
                update(updated) {
                    if (updated.type.name !== 'mathEquation') return false;
                    dom.setAttribute('data-latex', updated.attrs.latex || '');
                    try {
                        dom.innerHTML = katex.renderToString(updated.attrs.latex || '', { throwOnError: false });
                    } catch (e) { dom.textContent = updated.attrs.latex || ''; }
                    return true;
                },
            };
        };
    },
});

export const V2Image = Node.create({
    name: 'v2Image',
    group: 'block',
    atom: true,
    selectable: true,
    draggable: true,

    addAttributes() {
        return {
            src: { default: null },
            width: { default: '60%' },
        };
    },

    parseHTML() {
        return [
            {
                tag: 'img.v2-inline-image',
                getAttrs: (el) => ({ src: el.getAttribute('src'), width: el.style.width || el.getAttribute('data-width') || '60%' }),
            },
        ];
    },

    renderHTML({ node }) {
        return [
            'img',
            {
                class: 'v2-inline-image',
                src: node.attrs.src,
                'data-width': node.attrs.width,
                style: `width:${node.attrs.width};height:auto;display:block;margin:8px auto;`,
            },
        ];
    },

    // NodeView riêng khi ĐANG MOUNT editor: bọc <img> trong 1 wrapper có tay kéo ở góc
    // dưới-phải để đổi kích thước bằng chuột (giống Word) — kéo xong ghi % mới vào attrs
    // qua setNodeMarkup. Khi KHÔNG mount (hiển thị tĩnh) vẫn dùng đúng renderHTML ở trên
    // (không wrapper, không tay kéo) nên round-trip HTML lưu/đọc lại không đổi.
    addNodeView() {
        return ({ node, editor, getPos }) => {
            let currentNode = node;

            const wrapper = document.createElement('div');
            wrapper.className = 'v2-image-wrap';

            const img = document.createElement('img');
            img.className = 'v2-inline-image';
            img.draggable = false;

            const handle = document.createElement('span');
            handle.className = 'v2-image-resize-handle';
            handle.title = 'Kéo để đổi kích thước';
            handle.contentEditable = 'false';

            const applyAttrs = (attrs) => {
                img.src = attrs.src || '';
                img.setAttribute('data-width', attrs.width || '60%');
                wrapper.style.width = attrs.width || '60%';
            };
            applyAttrs(node.attrs);

            wrapper.appendChild(img);
            wrapper.appendChild(handle);

            let dragging = false;
            let startX = 0;
            let startPercent = 60;
            let containerWidth = 0;

            const onMove = (e) => {
                if (!dragging || !containerWidth) return;
                const deltaPercent = ((e.clientX - startX) / containerWidth) * 100;
                const next = Math.min(100, Math.max(10, Math.round(startPercent + deltaPercent)));
                wrapper.style.width = next + '%';
            };
            const onUp = () => {
                if (!dragging) return;
                dragging = false;
                document.removeEventListener('mousemove', onMove);
                document.removeEventListener('mouseup', onUp);
                const finalPercent = (parseFloat(wrapper.style.width) || 60) + '%';
                if (typeof getPos === 'function') {
                    editor.chain().command(({ tr }) => {
                        tr.setNodeMarkup(getPos(), undefined, { ...currentNode.attrs, width: finalPercent });
                        return true;
                    }).run();
                }
            };
            handle.addEventListener('mousedown', (e) => {
                e.preventDefault();
                e.stopPropagation();
                dragging = true;
                startX = e.clientX;
                startPercent = parseFloat(wrapper.style.width) || 60;
                containerWidth = wrapper.parentElement ? wrapper.parentElement.clientWidth : wrapper.clientWidth;
                document.addEventListener('mousemove', onMove);
                document.addEventListener('mouseup', onUp);
            });

            return {
                dom: wrapper,
                selectNode() { wrapper.classList.add('v2-image-selected'); },
                deselectNode() { wrapper.classList.remove('v2-image-selected'); },
                update(updated) {
                    if (updated.type.name !== 'v2Image') return false;
                    currentNode = updated;
                    applyAttrs(updated.attrs);
                    return true;
                },
                destroy() {
                    document.removeEventListener('mousemove', onMove);
                    document.removeEventListener('mouseup', onUp);
                },
            };
        };
    },
});

/** Ảnh/icon INLINE nằm lẫn trong dòng chữ (giống Word) — khác V2Image (luôn là block
 *  riêng 1 dòng, % theo bề rộng cột): node này là "inline", kích thước tính bằng PIXEL
 *  cố định (không phụ thuộc bề rộng cột/bảng), phù hợp cho icon/ký hiệu nhỏ chèn giữa
 *  câu (VD: paste 1 icon nhãn hiệu từ Word). Chèn qua Ctrl+V ảnh trực tiếp (xem
 *  handleEditorPaste trong main.js); nút toolbar "Chèn hình ảnh" vẫn dùng V2Image. */
export const V2InlineImage = Node.create({
    name: 'v2InlineImage',
    group: 'inline',
    inline: true,
    atom: true,
    selectable: true,
    draggable: true,

    addAttributes() {
        return {
            src: { default: null },
            width: { default: 24 }, // px
        };
    },

    parseHTML() {
        return [
            {
                tag: 'img.v2-inline-icon',
                getAttrs: (el) => ({
                    src: el.getAttribute('src'),
                    width: parseInt(el.style.width, 10) || parseInt(el.getAttribute('data-width'), 10) || 24,
                }),
            },
        ];
    },

    renderHTML({ node }) {
        return [
            'img',
            {
                class: 'v2-inline-icon',
                src: node.attrs.src,
                'data-width': node.attrs.width,
                style: `width:${node.attrs.width}px;height:auto;vertical-align:middle;display:inline-block;`,
            },
        ];
    },

    addNodeView() {
        return ({ node, editor, getPos }) => {
            let currentNode = node;

            const wrapper = document.createElement('span');
            wrapper.className = 'v2-inline-icon-wrap';

            const img = document.createElement('img');
            img.className = 'v2-inline-icon';
            img.draggable = false;

            const handle = document.createElement('span');
            handle.className = 'v2-inline-icon-resize-handle';
            handle.title = 'Kéo để đổi kích thước';
            handle.contentEditable = 'false';

            const applyAttrs = (attrs) => {
                img.src = attrs.src || '';
                const w = attrs.width || 24;
                img.style.width = w + 'px';
                wrapper.style.width = w + 'px';
            };
            applyAttrs(node.attrs);

            wrapper.appendChild(img);
            wrapper.appendChild(handle);

            let dragging = false;
            let startX = 0;
            let startWidth = 24;

            const onMove = (e) => {
                if (!dragging) return;
                const delta = e.clientX - startX;
                const next = Math.min(400, Math.max(8, Math.round(startWidth + delta)));
                img.style.width = next + 'px';
                wrapper.style.width = next + 'px';
            };
            const onUp = () => {
                if (!dragging) return;
                dragging = false;
                document.removeEventListener('mousemove', onMove);
                document.removeEventListener('mouseup', onUp);
                const finalWidth = parseInt(img.style.width, 10) || 24;
                if (typeof getPos === 'function') {
                    editor.chain().command(({ tr }) => {
                        tr.setNodeMarkup(getPos(), undefined, { ...currentNode.attrs, width: finalWidth });
                        return true;
                    }).run();
                }
            };
            handle.addEventListener('mousedown', (e) => {
                e.preventDefault();
                e.stopPropagation();
                dragging = true;
                startX = e.clientX;
                startWidth = parseInt(img.style.width, 10) || 24;
                document.addEventListener('mousemove', onMove);
                document.addEventListener('mouseup', onUp);
            });

            return {
                dom: wrapper,
                selectNode() { wrapper.classList.add('v2-inline-icon-selected'); },
                deselectNode() { wrapper.classList.remove('v2-inline-icon-selected'); },
                update(updated) {
                    if (updated.type.name !== 'v2InlineImage') return false;
                    currentNode = updated;
                    applyAttrs(updated.attrs);
                    return true;
                },
                destroy() {
                    document.removeEventListener('mousemove', onMove);
                    document.removeEventListener('mouseup', onUp);
                },
            };
        };
    },
});

/** Badge "Document Property" — key trỏ tới window.__V2__.docProperties, giống merge field của Word.
 *  Không cần NodeView riêng: badge chỉ hiển thị nên vẽ lại qua paintDocPropBadge() là đủ,
 *  gọi từ decorateBadges() (main.js) khi hiển thị tĩnh, và refreshAllDocPropBadges() khi giá trị đổi. */
export const DocPropField = Node.create({
    name: 'docProp',
    group: 'inline',
    inline: true,
    atom: true,
    selectable: true,
    draggable: true,

    addAttributes() {
        return {
            key: { default: '' },
        };
    },

    parseHTML() {
        return [
            {
                tag: 'span.v2-docprop-badge',
                getAttrs: (el) => ({ key: el.getAttribute('data-key') || '' }),
            },
        ];
    },

    renderHTML({ node }) {
        return ['span', { class: 'v2-docprop-badge', 'data-key': node.attrs.key || '', contenteditable: 'false' }];
    },
});

/**
 * Ở chế độ THIẾT KẾ: hiện dạng badge (viền + nền nhạt + icon thẻ) để dễ nhận ra vị trí
 * đã chèn property giữa văn bản khi đang soạn. Ở CHẠY THỬ/THỰC THI (hoặc xem read-only,
 * tức là dữ liệu đã "chốt"): bỏ hết khung/nền/icon, chỉ in giá trị thuần theo đúng định
 * dạng của đoạn/ô cha — giống Word khi in ra kết quả cuối cùng.
 */
export function paintDocPropBadge(el) {
    const key = el.getAttribute('data-key') || '';
    const props = window.__V2__?.docProperties || {};
    const has = Object.prototype.hasOwnProperty.call(props, key);
    const isPlain = !!(window.__V2__?.isExecutionMode || window.__V2__?.isReadOnly);
    el.classList.toggle('v2-docprop-plain', isPlain);
    el.classList.toggle('v2-docprop-missing', !has);
    if (isPlain) {
        el.innerHTML = has ? escapeHtmlLocal(String(props[key])) : `⚠ ${escapeHtmlLocal(key)}`;
    } else {
        const label = has ? String(props[key]) : `⚠ ${key}`;
        el.innerHTML = `<i class="fas fa-tag me-1" style="font-size:0.7em;"></i>${escapeHtmlLocal(label)}`;
    }
    el.title = key;
}

export function refreshAllDocPropBadges() {
    document.querySelectorAll('span.v2-docprop-badge').forEach((el) => paintDocPropBadge(el));
}

function escapeHtmlLocal(s) {
    return String(s).replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
}
