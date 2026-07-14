/**
 * Tài liệu PDF đính kèm theo phân đoạn — chạy thử/thực thi lô V2.
 * =========================================================
 * 1 nút kẹp giấy trên thanh công cụ (#v2-btn-attachments, kèm badge TỔNG số tài liệu
 * của cả hồ sơ) mở modal #v2AttachmentsModal. Trong modal có dropdown "Phân đoạn" để
 * chọn xem/tải lên tài liệu cho ĐÚNG phân đoạn nào (khoá record_id + section_id, xem
 * EbmrExecutionController::uploadSectionAttachment/deleteSectionAttachment) — mặc định
 * chọn sẵn phân đoạn đang ghi chép (?section=) nếu có.
 * Dữ liệu khởi tạo lấy từ BOOT.sectionAttachments (section_id -> mảng), sau đó chỉ
 * cập nhật cục bộ (không re-fetch toàn trang) khi tải lên/xoá thành công.
 */

export function createAttachmentsV2({ BOOT }) {
    const state = {};
    Object.keys(BOOT.sectionAttachments || {}).forEach((sectionId) => {
        state[sectionId] = (BOOT.sectionAttachments[sectionId] || []).slice();
    });

    let currentSectionId = null;

    function escHtml(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, (c) => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
        }[c]));
    }

    // Danh sách phân đoạn NGƯỜI DÙNG (bỏ section hệ thống/ảo) hiện có trên tài liệu —
    // nguồn cho dropdown chọn phân đoạn trong modal.
    function sectionList() {
        return (BOOT.items || []).filter(
            (i) => i.type === 'section' && !i.isVirtual && !i.locked,
        );
    }

    function totalCount() {
        return Object.values(state).reduce((sum, list) => sum + list.length, 0);
    }

    function updateToolbarBadge() {
        const badge = document.getElementById('v2-attach-total-badge');
        if (!badge) return;
        const count = totalCount();
        badge.textContent = count;
        badge.style.display = count ? '' : 'none';
    }

    function renderList() {
        const tbody = document.getElementById('v2-attach-tbody');
        if (!tbody) return;
        const list = state[currentSectionId] || [];
        if (!list.length) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-4">Chưa có tài liệu nào được đính kèm cho phân đoạn này.</td></tr>';
            return;
        }
        tbody.innerHTML = list.map((a) => `
            <tr>
                <td>
                    <i class="fas fa-file-pdf text-danger me-1"></i>${escHtml(a.title)}
                    <div class="small text-muted">${escHtml(a.file_name)}</div>
                </td>
                <td class="small">
                    ${escHtml(a.uploaded_by_name || '-')}
                    <div class="text-muted">${escHtml(a.uploaded_at || '')}</div>
                </td>
                <td class="text-center small">${escHtml(a.size_label)}</td>
                <td class="text-center">
                    <a href="${escHtml(a.url)}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary py-0 px-2 me-1" title="Xem"><i class="fas fa-eye"></i></a>
                    ${BOOT.isReadOnly ? '' : `<button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" data-del-id="${a.id}" title="Xoá"><i class="fas fa-trash-alt"></i></button>`}
                </td>
            </tr>`).join('');

        tbody.querySelectorAll('[data-del-id]').forEach((btn) => {
            btn.addEventListener('click', () => deleteAttachment(btn.dataset.delId));
        });
    }

    function deleteAttachment(id) {
        if (!confirm('Xoá tài liệu này khỏi phân đoạn?')) return;
        fetch(`${BOOT.urls.deleteSectionAttachmentBase}/${id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': BOOT.csrf } })
            .then((r) => r.json())
            .then((res) => {
                if (!res.success) { alert(res.message || 'Không xoá được tài liệu.'); return; }
                state[currentSectionId] = (state[currentSectionId] || []).filter((a) => String(a.id) !== String(id));
                renderList();
                updateToolbarBadge();
            })
            .catch(() => alert('Lỗi kết nối, không xoá được tài liệu.'));
    }

    function handleUpload(e) {
        e.preventDefault();
        const titleInput = document.getElementById('v2-attach-title-input');
        const fileInput = document.getElementById('v2-attach-file-input');
        const submitBtn = document.getElementById('v2-attach-submit-btn');
        if (!fileInput.files.length || !currentSectionId) return;

        const sec = sectionList().find((s) => String(s.section_id) === String(currentSectionId));

        const fd = new FormData();
        fd.append('record_id', BOOT.recordId);
        fd.append('section_id', currentSectionId);
        fd.append('section_label', (sec && sec.label) || '');
        fd.append('title', titleInput.value);
        fd.append('file', fileInput.files[0]);

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Đang tải...';

        fetch(BOOT.urls.uploadSectionAttachment, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': BOOT.csrf },
            body: fd,
        })
            .then((r) => r.json())
            .then((res) => {
                if (!res.success) { alert(res.message || 'Không tải lên được tài liệu.'); return; }
                if (!state[currentSectionId]) state[currentSectionId] = [];
                state[currentSectionId].unshift(res.attachment);
                renderList();
                updateToolbarBadge();
                titleInput.value = '';
                fileInput.value = '';
            })
            .catch(() => alert('Lỗi kết nối, không tải lên được tài liệu.'))
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-upload me-1"></i> Tải lên';
            });
    }

    // Đổ danh sách phân đoạn vào dropdown, kèm số lượng tài liệu từng phân đoạn để
    // nhìn lướt là biết chỗ nào đã có tài liệu mà không cần chọn thử từng cái.
    function populateSectionSelect() {
        const select = document.getElementById('v2-attach-section-select');
        if (!select) return;
        select.innerHTML = sectionList().map((s) => {
            const count = (state[s.section_id] || []).length;
            return `<option value="${escHtml(s.section_id)}">${escHtml(s.label || 'Phân đoạn')}${count ? ` (${count} tài liệu)` : ''}</option>`;
        }).join('');
        if (currentSectionId) select.value = String(currentSectionId);
        if (!select.value && select.options.length) select.selectedIndex = 0;
        currentSectionId = select.value || null;
    }

    function open() {
        // Ưu tiên phân đoạn đang ghi chép (?section= — có thể là danh sách "id1,id2"
        // khi nhiều công đoạn nối trang, lấy id đầu), nếu không giữ lựa chọn lần trước.
        if (!currentSectionId && BOOT.activeSectionId) {
            currentSectionId = String(BOOT.activeSectionId).split(',')[0].trim();
        }
        populateSectionSelect();

        const form = document.getElementById('v2-attach-upload-form');
        if (form) form.style.display = BOOT.isReadOnly ? 'none' : '';

        renderList();
        if (window.jQuery) window.jQuery('#v2AttachmentsModal').modal('show');
    }

    function init() {
        if (!BOOT.isExecutionMode) return;
        const btn = document.getElementById('v2-btn-attachments');
        if (btn) btn.addEventListener('click', open);
        const select = document.getElementById('v2-attach-section-select');
        if (select) {
            select.addEventListener('change', () => {
                currentSectionId = select.value || null;
                renderList();
            });
        }
        const form = document.getElementById('v2-attach-upload-form');
        if (form) form.addEventListener('submit', handleUpload);
        updateToolbarBadge();
    }

    return { init, open };
}
