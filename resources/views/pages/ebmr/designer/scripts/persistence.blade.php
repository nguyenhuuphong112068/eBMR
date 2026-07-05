<script>
    function saveScrollPosition() {
        const scrollPositions = [];

        // 1. Save window scroll
        scrollPositions.push({
            element: window,
            top: window.scrollY || window.pageYOffset || 0,
            left: window.scrollX || window.pageXOffset || 0
        });

        // 2. Save scroll position of designer-workspace and all its parents
        const workspace = document.getElementById('designer-workspace');
        if (workspace) {
            let parent = workspace;
            while (parent) {
                if (parent.scrollTop > 0 || parent.scrollLeft > 0) {
                    scrollPositions.push({
                        element: parent,
                        top: parent.scrollTop,
                        left: parent.scrollLeft
                    });
                }
                parent = parent.parentElement;
            }
        }

        return scrollPositions;
    }

    function restoreScrollPosition(scrollPositions) {
        if (!scrollPositions) return;
        scrollPositions.forEach(pos => {
            if (pos.element === window) {
                window.scrollTo(pos.left, pos.top);
            } else if (document.body.contains(pos.element)) {
                pos.element.scrollTop = pos.top;
                pos.element.scrollLeft = pos.left;
            }
        });
    }

    function saveTemplate() {
        if (window.isSelectVarMode && typeof window.toggleSelectVarMode === 'function') {
            window.toggleSelectVarMode(window.targetFormulaFieldId);
        }

        if (window.isExecutionMode) {
            Swal.fire({
                icon: 'warning',
                title: 'Đang ở chế độ Chạy thử',
                text: 'Hệ thống không lưu dữ liệu trong chế độ này. Hãy quay lại chế độ Thiết kế để lưu thay đổi.',
                confirmButtonText: 'Tôi đã hiểu'
            });
            return;
        }

        const savedScroll = saveScrollPosition();

        // Show loading swal to prevent multiple clicks
        Swal.fire({
            title: 'Đang lưu hồ sơ...',
            text: 'Quá trình lưu có thể mất vài giây tùy vào độ lớn dữ liệu.',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // INCREMENTAL SAVE LOGIC: Only send dirty or new non-virtual blocks
        // Bug #7 Fix: Th\u00eam \u0111\u1ee7 t\u1ea5t c\u1ea3 props \u0111\u1ec3 kh\u00f4ng m\u1ea5t d\u1eef li\u1ec7u \u00e2m th\u1ea7m
        const dirtyFields = items.filter(i => !i.isVirtual && (i.dirty || !i.db_id)).map(i => ({
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
            borderWeight: i.borderWeight || null,
            borderColor: i.borderColor || null,
            borderStyle: i.borderStyle || null,
            cellBorders: i.cellBorders || null,
            hideHeader: i.hideHeader || false,
            canAddRows: i.canAddRows || false,
            addRowsCount: i.addRowsCount || 1,
            locked: i.locked || false,
            template_id: i.template_id || null,
            showPreview: i.showPreview || false,
            stage_code: i.stage_code || null,
            chartConfig: i.chartConfig || null,
            backgroundColor: i.backgroundColor || null,
            section_id: i.section_id || null,
            isBmrHeader: i.isBmrHeader || false,
            isGfHeader: i.isGfHeader || false,
            isAbbreviationTable: i.isAbbreviationTable || false,
            loop_group_id: i.loop_group_id || null,
            loop_count: i.loop_count || null,
            typography: i.typography || null,
            // Bug #7 Fix: Th\u00eam c\u00e1c props m\u1edbi \u0111\u01b0\u1ee3c b\u1ecf s\u00f3t tr\u01b0\u1edbc \u0111\u00e2y
            cell_notes: i.cell_notes || null,
            conditional_logic: i.conditional_logic || null,
            textAlign: i.textAlign || null,
            verticalAlign: i.verticalAlign || null,
            pageBreakBefore: i.pageBreakBefore || false,
        }));

        // --- PRUNING & LOCATION SYNC: Only send fieldsConfig for variables that actually exist in the document ---
        const usedFieldIds = new Set();
        document.querySelectorAll('.ebmr-field-badge').forEach(el => {
            const fid = el.getAttribute('data-field-id');
            if (fid) {
                usedFieldIds.add(fid);

                // Cập nhật lại vị trí thực tế của biến ngay lúc lưu
                const blockEl = el.closest('.block-item');
                if (blockEl && fieldsConfig[fid]) {
                    fieldsConfig[fid].block_id = blockEl.getAttribute('data-id');
                    const sectionEl = blockEl.closest('.section-group-wrapper');
                    if (sectionEl) {
                        fieldsConfig[fid].section_id = sectionEl.getAttribute('data-section-id');
                    }
                }
            }
        });

        // Also check if any field is used in a formula
        Object.values(fieldsConfig).forEach(f => {
            if (f.type === 'formula' && f.formula) {
                const matches = f.formula.match(/field_[a-zA-Z0-9_]+/g);
                if (matches) matches.forEach(m => usedFieldIds.add(m));
            }
        });

        const prunedFieldsConfig = {};
        usedFieldIds.forEach(fid => {
            if (fieldsConfig[fid]) prunedFieldsConfig[fid] = fieldsConfig[fid];
        });

        const schema = {
            type: 'document-flow',
            pageOrientation: pageOrientation,
            fieldsConfig: prunedFieldsConfig,
            fields: dirtyFields, // Only dirty fields
            block_order: items.filter(i => !i.isVirtual).map(i => i
            .id), // Send current order of all non-virtual blocks
            deleted_ids: window.deletedBlockIds || [],
            incremental: true
        };

        fetch('{{ route('pages.ebmr.storeTemplate') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
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
                    return res.json().then(err => {
                        throw new Error(err.message || 'Lỗi hệ thống')
                    });
                }
                return res.json();
            })
            .then(res => {
                if (res.success) {
                    currentTemplateId = res.id;

                    // Reset dirty flags and deleted IDs
                    items.forEach(i => i.dirty = false);
                    window.deletedBlockIds = [];

                    // Update IDs and data for items if returned
                    if (res.block_ids) {
                        Object.keys(res.block_ids).forEach(fId => {
                            const item = items.find(i => i.id === fId);
                            if (item) {
                                const info = res.block_ids[fId];
                                if (info.db_id) item.db_id = info.db_id;
                                if (info.content_db_id) item.content_db_id = info.content_db_id;
                                if (info.section_id) item.section_id = info.section_id;
                                if (info.data) item.data = info.data; // Sync cell IDs
                            }
                        });
                    }

                    renderBlocks();

                    // Restore scroll position immediately
                    restoreScrollPosition(savedScroll);
                    setTimeout(() => restoreScrollPosition(savedScroll), 0);

                    Swal.fire({
                        title: 'Thành công',
                        text: 'Đã lưu hồ sơ mẫu!',
                        icon: 'success',
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        // Restore scroll position again after sweetalert closes
                        restoreScrollPosition(savedScroll);
                        setTimeout(() => restoreScrollPosition(savedScroll), 0);
                    });
                } else {
                    Swal.fire('Thất bại', res.message || 'Không thể lưu hồ sơ', 'error');
                }
            })
            .catch(err => {
                console.error(err);
                Swal.fire('Lỗi kết nối', err.message || 'Vui lòng kiểm tra lại kết nối mạng hoặc thử lại sau.',
                    'error');
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
            const userName = h.user_name || 'Không xác định';
            return `
                <div class="card mb-3 border-0 shadow-sm" style="border-left: 4px solid #ffc107;">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="badge bg-light text-dark fw-bold"><i class="fas fa-user me-1"></i> ${userName}</span>
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

    // Ngôn ngữ mặc định: Tiếng Việt (chỉ hỗ trợ 1 ngôn ngữ)
    window.currentLangMode = 'vi';
</script>
