<script>
    function saveTemplate() {
        const name = document.getElementById('templateName').value || 'Hồ sơ không tên';
        const schema = {
            type: 'document-flow',
            fieldsConfig: fieldsConfig,
            fields: items.map(i => ({
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
                hideHeader: i.hideHeader || false
            }))
        };

        fetch('{{ route('pages.ebmr.storeTemplate') }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({
                id: currentTemplateId,
                name: name,
                schema: schema,
                log_history: historyEnabled,
                _token: '{{ csrf_token() }}'
            })
        })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                currentTemplateId = res.id;
                Swal.fire({ title: 'Thành công', text: 'Đã lưu hồ sơ mẫu!', icon: 'success', showConfirmButton: false, timer: 1500 });
            }
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
</script>
