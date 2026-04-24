<script>
    function saveTemplate() {
        const name = document.getElementById('templateName').value || 'Hồ sơ không tên';
        const schema = {
            type: 'document-flow',
            pageOrientation: pageOrientation,
            fieldsConfig: fieldsConfig,
            fields: items.map(i => ({
                db_id: i.db_id || null, 
                content_db_id: i.content_db_id || null,
                id: i.id,
                type: i.type,
                label: i.label,
                content: i.content || '',
                rows: i.rows || 0,
                cols: i.cols || 0,
                columns: i.columns || [],
                data: i.data || [],
                rowHeights: i.rowHeights || [],
                borderMode: i.borderMode || 'visible',
                hideHeader: i.hideHeader || false,
                template_id: i.template_id || null,
                showPreview: i.showPreview || false,
                stage_code: i.stage_code || null,
                chartConfig: i.chartConfig || null,
                backgroundColor: i.backgroundColor || null,
                section_id: i.section_id || null
            }))
        };

        fetch('{{ route('pages.ebmr.storeTemplate') }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({
                id: currentTemplateId,
                schema: schema,
                log_history: historyEnabled,
                section_id: '{{ $activeSectionId ?? '' }}',
                lang: window.currentLangMode || 'vi',
                _token: '{{ csrf_token() }}'
            })
        })
        .then(res => {
            if (!res.ok) {
                return res.json().then(err => { throw new Error(err.message || 'Lỗi hệ thống') });
            }
            return res.json();
        })
        .then(res => {
            if (res.success) {
                currentTemplateId = res.id;
                Swal.fire({ title: 'Thành công', text: 'Đã lưu hồ sơ mẫu!', icon: 'success', showConfirmButton: false, timer: 1500 });
            } else {
                Swal.fire('Thất bại', res.message || 'Không thể lưu hồ sơ', 'error');
            }
        })
        .catch(err => {
            console.error(err);
            Swal.fire('Lỗi kết nối', err.message || 'Vui lòng kiểm tra lại kết nối mạng hoặc thử lại sau.', 'error');
        });
    }

    let allTemplates = [];
    function openTemplateModal() {
        if (window.bootstrap) {
            const modal = new bootstrap.Modal(document.getElementById('openTemplateModal'));
            modal.show();
            fetchTemplates();
        }
    }

    function fetchTemplates() {
        const listLoading = document.getElementById('templateListLoading');
        const list = document.getElementById('templateList');
        listLoading.classList.remove('d-none');
        list.classList.add('d-none');

        fetch('{{ route('pages.ebmr.getTemplates') }}')
            .then(res => res.json())
            .then(data => {
                allTemplates = data;
                renderTemplateList(data);
                listLoading.classList.add('d-none');
                list.classList.remove('d-none');
            });
    }

    function renderTemplateList(templates) {
        const list = document.getElementById('templateList');
        if (templates.length === 0) {
            list.innerHTML = '<div class="text-center py-4 text-muted">Không có hồ sơ nào.</div>';
            return;
        }
        list.innerHTML = templates.map(t => `
            <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3">
                <div>
                    <div class="fw-bold text-navy">${t.name}</div>
                    <div class="small text-muted">Cập nhật: ${new Date(t.updated_at).toLocaleString()}</div>
                </div>
                <button class="btn btn-sm btn-navy rounded-pill px-3" onclick="loadTemplateIntoDesigner(${t.id})">
                    <i class="fas fa-edit me-1"></i> Mở
                </button>
            </div>
        `).join('');
    }

    function filterTemplates(query) {
        const filtered = allTemplates.filter(t => t.name.toLowerCase().includes(query.toLowerCase()));
        renderTemplateList(filtered);
    }

    function loadTemplateIntoDesigner(id) {
        window.location.href = `{{ url('/ebmr/designer') }}/${id}`;
    }

    function toggleHistoryEnabled(val) {
        historyEnabled = val;
        saveStateDebounced();
    }

    function openHistoryModal() {
        if (!currentTemplateId) {
            Swal.fire('Chú ý', 'Vui lòng lưu hồ sơ trước khi xem lịch sử', 'warning');
            return;
        }
        const modal = new bootstrap.Modal(document.getElementById('historyModal'));
        modal.show();
        fetchHistory();
    }

    function fetchHistory() {
        const timeline = document.getElementById('historyTimeline');
        timeline.innerHTML = '<div class="text-center py-5"><div class="spinner-border spinner-border-sm"></div></div>';
        fetch(`{{ url('/ebmr/get-history') }}/${currentTemplateId}`)
            .then(res => res.json())
            .then(data => renderHistoryTimeline(data));
    }

    function renderHistoryTimeline(history) {
        const timeline = document.getElementById('historyTimeline');
        if (history.length === 0) {
            timeline.innerHTML = '<div class="text-center py-4 text-muted">Chưa có lịch sử thay đổi.</div>';
            return;
        }
        timeline.innerHTML = history.map(h => {
            const details = JSON.parse(h.details);
            return `
                <div class="card mb-3 border-0 shadow-sm" style="border-left: 4px solid #ffc107;">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="badge bg-light text-dark fw-bold"><i class="fas fa-user me-1"></i> Admin</span>
                            <span class="small text-muted">${new Date(h.created_at).toLocaleString()}</span>
                        </div>
                        <div class="fw-bold small mb-2 text-navy">${h.change_summary}</div>
                        <div class="mt-2 border-top pt-2" style="font-size: 0.75rem;">
                            ${details.added.length ? `<div class="text-success small"><b>Thêm:</b> ${details.added.join(', ')}</div>` : ''}
                            ${details.deleted.length ? `<div class="text-danger small"><b>Xóa:</b> ${details.deleted.join(', ')}</div>` : ''}
                            ${details.modified.length ? `<div class="text-warning small"><b>Sửa:</b> ${details.modified.join(', ')}</div>` : ''}
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    }

    // --- Language & AI Logic ---
    window.currentLangMode = @json($lang ?? 'vi');
    function setLanguageMode(mode) {
        if (mode === window.currentLangMode) return;
        
        const url = new URL(window.location.href);
        url.searchParams.set('lang', mode);
        window.location.href = url.toString();
    }

    function translateCurrentDocument() {
        if (!currentTemplateId) {
            Swal.fire('Chú ý', 'Vui lòng lưu hồ sơ trước khi phiên dịch', 'warning');
            return;
        }

        Swal.fire({
            title: 'Khởi động AI Translation',
            text: 'Bạn có muốn dùng Google Gemini AI để dịch toàn bộ nội dung Tiếng Việt sang Tiếng Anh không? Quá trình này có thể mất vài giây.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Bắt đầu dịch',
            cancelButtonText: 'Để sau',
            confirmButtonColor: '#0ea5e9'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Đang xử lý...',
                    text: 'AI đang biên dịch dữ liệu, vui lòng đợi...',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });

                fetch('{{ route('pages.ebmr.aiTranslate') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({
                        template_id: currentTemplateId,
                        _token: '{{ csrf_token() }}'
                    })
                })
                .then(res => res.json())
                .then(res => {
                    if (res.success) {
                        Swal.fire({
                            title: 'Hoàn tất!',
                            text: `Đã dịch thành công ${res.count} khối nội dung. Hệ thống sẽ tải lại ở chế độ Tiếng Anh để bạn kiểm tra và lưu lại.`,
                            icon: 'success'
                        }).then(() => {
                            window.location.href = '{{ route('pages.ebmr.designer', $template->id) }}?lang=en';
                        });
                    } else {
                        Swal.fire('Lỗi AI', res.message || 'Không thể thực hiện phiên dịch', 'error');
                    }
                })
                .catch(err => {
                    Swal.fire('Lỗi kết nối', 'Không thể kết nối với máy chủ AI', 'error');
                });
            }
        });
    }

    // Initialize UI state
    document.addEventListener('DOMContentLoaded', () => {
        const mode = window.currentLangMode;
        const labels = { 'vi': 'Tiếng Việt', 'en': 'Tiếng Anh', 'dual': 'Song ngữ' };
        if (document.getElementById('currentLangLabel')) {
            document.getElementById('currentLangLabel').innerText = labels[mode] || labels['vi'];
        }
        
        // Highlight active mode in dropdown
        ['vi', 'en', 'dual'].forEach(m => {
            const el = document.getElementById('check-' + m);
            if (el) {
                if (m === mode) {
                    el.classList.remove('d-none');
                    el.parentElement.classList.add('bg-light', 'fw-bold');
                } else {
                    el.classList.add('d-none');
                    el.parentElement.classList.remove('bg-light', 'fw-bold');
                }
            }
        });
    });
</script>
