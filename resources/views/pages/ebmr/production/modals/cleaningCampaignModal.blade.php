{{-- Modal thực hiện quy trình vệ sinh phòng (Step-by-step Campaign) --}}
<div class="modal fade" id="cleaningCampaignModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">

            {{-- HEADER --}}
            <div class="modal-header py-3 px-4"
                style="background: linear-gradient(135deg, #f59e0b, #d97706); border-bottom: none;">
                <div class="d-flex align-items-center gap-3 w-100">
                    <div class="p-2 bg-white bg-opacity-25 rounded-3">
                        <i class="fas fa-broom text-white fa-lg"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h5 class="modal-title fw-bold text-white mb-0" id="ccm-title">Vệ Sinh Phòng</h5>
                        <div class="text-white small opacity-75" id="ccm-subtitle">...</div>
                    </div>
                    <button type="button" class="close border-0 bg-transparent text-white fs-4 p-0 m-0"
                        data-dismiss="modal" aria-label="Close" style="line-height: 1; outline: none; opacity: 0.9;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            </div>

            {{-- PROGRESS BAR --}}
            <div class="px-4 pt-3 pb-2 bg-white border-bottom">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="small fw-bold text-muted">Tiến độ thực hiện</span>
                    <span class="small fw-bold text-warning" id="ccm-progress-text">0 / 0 bước</span>
                </div>
                <div class="progress" style="height: 8px; border-radius: 999px;">
                    <div class="progress-bar bg-warning" id="ccm-progress-bar" role="progressbar"
                        style="width: 0%; border-radius: 999px; transition: width 0.5s ease;"></div>
                </div>
            </div>

            {{-- BODY --}}
            <div class="modal-body p-0" style="max-height: 70vh; overflow-y: auto;">
                <div class="row g-0 h-100">

                    {{-- LEFT: Danh sách bước --}}
                    <div class="col-md-3 border-end bg-light" style="min-height: 400px;">
                        <div class="p-3 border-bottom bg-white">
                            <div class="small fw-bold text-muted text-uppercase" style="font-size: 0.7rem;">
                                <i class="fas fa-list-ol me-1"></i> Các bước
                            </div>
                        </div>
                        <div id="ccm-steps-sidebar" class="py-2">
                            {{-- Filled by JS --}}
                        </div>
                    </div>

                    {{-- RIGHT: Nội dung bước hiện tại --}}
                    <div class="col-md-9">
                        <div id="ccm-step-loading" class="text-center py-5" style="display: none;">
                            <div class="spinner-border text-warning" role="status"></div>
                            <div class="mt-2 text-muted small">Đang tải...</div>
                        </div>

                        <div id="ccm-step-content" class="p-4">
                            {{-- Hiển thị nội dung bước --}}
                            <div class="d-flex align-items-center gap-2 mb-3">
                                <span class="badge bg-warning text-dark fw-bold px-3 py-2 fs-6" id="ccm-step-number">Bước 1</span>
                                <span class="fw-bold text-navy" id="ccm-step-done-badge" style="display:none;">
                                    <i class="fas fa-check-circle text-success me-1"></i>
                                    <span class="text-success small">Đã hoàn thành</span>
                                </span>
                            </div>

                            {{-- Nội dung quy trình --}}
                            <div class="card border-0 bg-light rounded-3 p-3 mb-3">
                                <div class="small fw-bold text-muted text-uppercase mb-2" style="font-size: 0.7rem;">
                                    <i class="fas fa-clipboard-list me-1"></i> Nội dung thực hiện
                                </div>
                                <div id="ccm-step-html" class="text-navy" style="font-size: 0.9rem; line-height: 1.6;">
                                    {{-- HTML content from process step --}}
                                </div>
                            </div>

                            {{-- Tiêu chuẩn --}}
                            <div class="card border-0 border-start border-4 border-info rounded-3 p-3 mb-4" id="ccm-standard-card">
                                <div class="small fw-bold text-muted text-uppercase mb-2" style="font-size: 0.7rem;">
                                    <i class="fas fa-ruler-combined me-1 text-info"></i> Tiêu chuẩn
                                </div>
                                <div id="ccm-step-standard" class="text-navy" style="font-size: 0.85rem; line-height: 1.6;"></div>
                            </div>

                            {{-- Ghi chú kết quả --}}
                            <div id="ccm-note-section">
                                <label class="fw-bold small text-muted text-uppercase mb-2 d-block" style="font-size: 0.7rem;">
                                    <i class="fas fa-pen me-1"></i> Ghi chú kết quả (tuỳ chọn)
                                </label>
                                <textarea id="ccm-result-note" class="form-control rounded-3 border"
                                    rows="3" placeholder="Nhập ghi chú kết quả bước này..."
                                    style="font-size: 0.9rem; resize: vertical;"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- FOOTER --}}
            <div class="modal-footer bg-white border-top py-3 px-4 d-flex justify-content-between align-items-center">
                <div>
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4"
                        id="ccm-btn-prev" onclick="ccmPrevStep()" disabled>
                        <i class="fas fa-chevron-left me-1"></i> Bước trước
                    </button>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-dismiss="modal">
                        Đóng
                    </button>
                    <button type="button" class="btn btn-warning rounded-pill px-4 fw-bold shadow-sm"
                        id="ccm-btn-complete-step" onclick="ccmCompleteCurrentStep()">
                        <i class="fas fa-check me-2"></i> Ghi nhận & Chuyển bước
                    </button>
                    <button type="button" class="btn btn-success rounded-pill px-4 fw-bold shadow-sm d-none"
                        id="ccm-btn-finish" onclick="ccmFinishCampaign()">
                        <i class="fas fa-flag-checkered me-2"></i> Hoàn thành Vệ Sinh
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Sidebar step items */
    .ccm-step-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 14px;
        margin: 4px 8px;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.2s;
        border: 1px solid transparent;
    }
    .ccm-step-item:hover {
        background: rgba(0,0,0,0.04);
    }
    .ccm-step-item.active {
        background: rgba(245, 158, 11, 0.12);
        border-color: rgba(245, 158, 11, 0.35);
    }
    .ccm-step-item.done {
        opacity: 0.75;
    }
    .ccm-step-item.done .ccm-step-circle {
        background: #28a745 !important;
        color: white !important;
    }
    .ccm-step-circle {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        background: #e2e8f0;
        color: #64748b;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        font-weight: 700;
        flex-shrink: 0;
        transition: all 0.3s;
    }
    .ccm-step-item.active .ccm-step-circle {
        background: #f59e0b;
        color: white;
    }
    .ccm-step-label {
        font-size: 0.8rem;
        font-weight: 500;
        color: #374151;
        line-height: 1.3;
    }
    #cleaningCampaignModal .text-navy { color: #003A4F !important; }
</style>

<script>
    // ═══════════════════════════════════════════════════════════════════
    //  CLEANING CAMPAIGN MODAL – JS
    // ═══════════════════════════════════════════════════════════════════
    let ccmCampaignId    = null;
    let ccmSteps         = [];
    let ccmCurrentIndex  = 0;
    let ccmTotalSteps    = 0;

    // Mở modal khi bấm nút "Vệ Sinh Phòng"
    $(document).on('click', '.btn-start-cleaning', function () {
        const roomId   = $(this).data('room-id');
        const roomCode = $(this).data('room-code');
        const roomName = $(this).data('room-name');

        ccmCampaignId = null;
        ccmSteps      = [];
        ccmCurrentIndex = 0;

        // Reset UI
        $('#ccm-title').text('Vệ Sinh Phòng – ' + roomCode);
        $('#ccm-subtitle').text('Đang khởi động...');
        $('#ccm-steps-sidebar').html('');
        $('#ccm-step-html').html('');
        $('#ccm-step-standard').html('');
        $('#ccm-result-note').val('');
        $('#ccm-progress-bar').css('width', '0%');
        $('#ccm-progress-text').text('0 / 0 bước');
        $('#ccm-btn-finish').addClass('d-none');
        $('#ccm-btn-complete-step').removeClass('d-none');

        $('#cleaningCampaignModal').modal('show');

        // Gọi API khởi tạo campaign
        $.ajax({
            url: '{{ route("pages.manu_env.cleaning_process.campaign.start", ["room_id" => "ROOM_ID"]) }}'
                    .replace('ROOM_ID', roomId),
            method: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function (res) {
                if (res.success) {
                    ccmCampaignId = res.campaign_id;
                    ccmLoadCampaign();
                } else {
                    $('#cleaningCampaignModal').modal('hide');
                    Swal.fire({
                        icon: 'warning',
                        title: 'Không thể bắt đầu',
                        text: res.message
                    });
                }
            },
            error: function () {
                $('#cleaningCampaignModal').modal('hide');
                Swal.fire('Lỗi', 'Không thể kết nối máy chủ', 'error');
            }
        });
    });

    // Load dữ liệu campaign từ server
    function ccmLoadCampaign() {
        $.get(
            '{{ route("pages.manu_env.cleaning_process.campaign.get", ["campaign_id" => "CAMPAIGN_ID"]) }}'
                .replace('CAMPAIGN_ID', ccmCampaignId),
            function (res) {
                if (!res.success) return;

                const c = res.campaign;
                ccmSteps = res.steps;
                ccmTotalSteps = c.total_steps;

                $('#ccm-title').text('Vệ Sinh Phòng – ' + c.room_code);
                $('#ccm-subtitle').text(c.process_name + ' | V.' + c.version);

                ccmRenderSidebar();
                ccmUpdateProgress(c.done_steps, c.total_steps);

                // Tìm bước đầu tiên chưa xong
                const firstUndone = ccmSteps.findIndex(s => !s.is_done);
                ccmGoToStep(firstUndone >= 0 ? firstUndone : 0);
            }
        );
    }

    // Render sidebar danh sách bước
    function ccmRenderSidebar() {
        let html = '';
        ccmSteps.forEach(function (step, idx) {
            const isDone = step.is_done;
            html += `
                <div class="ccm-step-item ${isDone ? 'done' : ''}" id="ccm-sidebar-${idx}" onclick="ccmGoToStep(${idx})">
                    <div class="ccm-step-circle">
                        ${isDone ? '<i class="fas fa-check" style="font-size:0.7rem;"></i>' : step.step}
                    </div>
                    <div class="ccm-step-label">Bước ${step.step}</div>
                </div>`;
        });
        $('#ccm-steps-sidebar').html(html);
    }

    // Chuyển đến bước index
    function ccmGoToStep(idx) {
        if (idx < 0 || idx >= ccmSteps.length) return;
        ccmCurrentIndex = idx;
        const step = ccmSteps[idx];

        // Update sidebar active
        $('.ccm-step-item').removeClass('active');
        $('#ccm-sidebar-' + idx).addClass('active');

        // Update content
        $('#ccm-step-number').text('Bước ' + step.step);

        if (step.is_done) {
            $('#ccm-step-done-badge').show();
            $('#ccm-note-section').hide();
            $('#ccm-btn-complete-step').prop('disabled', true);
        } else {
            $('#ccm-step-done-badge').hide();
            $('#ccm-note-section').show();
            $('#ccm-btn-complete-step').prop('disabled', false);
        }

        // Render HTML content (from summernote – contains HTML)
        const contentHtml = step.content || '<span class="text-muted">Không có nội dung</span>';
        $('#ccm-step-html').html(contentHtml);

        const standardHtml = step.standard || '';
        if (standardHtml && standardHtml.trim() !== '<p><br></p>') {
            $('#ccm-standard-card').show();
            $('#ccm-step-standard').html(standardHtml);
        } else {
            $('#ccm-standard-card').hide();
        }

        $('#ccm-result-note').val(step.is_done ? (step.result_note || '') : '');

        // Prev button
        $('#ccm-btn-prev').prop('disabled', idx === 0);

        // Check if all done → show Hoàn thành button
        const allDone = ccmSteps.every(s => s.is_done);
        if (allDone) {
            $('#ccm-btn-finish').removeClass('d-none');
            $('#ccm-btn-complete-step').addClass('d-none');
        } else if (idx === ccmSteps.length - 1 && !step.is_done) {
            // Bước cuối, chưa done → nút vẫn là "Ghi nhận & Chuyển bước" (sẽ auto finish sau)
            $('#ccm-btn-complete-step').text(' Ghi nhận & Hoàn thành').prepend($('<i class="fas fa-check me-2"></i>'));
        } else {
            $('#ccm-btn-complete-step').html('<i class="fas fa-check me-2"></i> Ghi nhận & Chuyển bước');
        }
    }

    // Chuyển bước trước
    function ccmPrevStep() {
        if (ccmCurrentIndex > 0) {
            ccmGoToStep(ccmCurrentIndex - 1);
        }
    }

    // Ghi nhận hoàn thành bước hiện tại
    function ccmCompleteCurrentStep() {
        const step = ccmSteps[ccmCurrentIndex];
        if (!step || step.is_done) return;

        const note = $('#ccm-result-note').val();
        const stepId = step.id;

        $('#ccm-btn-complete-step').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i> Đang lưu...');

        $.ajax({
            url: '{{ route("pages.manu_env.cleaning_process.campaign.completeStep", ["campaign_id" => "CAMP_ID", "step_id" => "STEP_ID"]) }}'
                    .replace('CAMP_ID', ccmCampaignId)
                    .replace('STEP_ID', stepId),
            method: 'POST',
            data: { _token: '{{ csrf_token() }}', result_note: note },
            success: function (res) {
                if (res.success) {
                    // Update local data
                    ccmSteps[ccmCurrentIndex].is_done     = true;
                    ccmSteps[ccmCurrentIndex].result_note = note;
                    ccmSteps[ccmCurrentIndex].done_by     = res.done_by;

                    ccmUpdateProgress(res.done_steps, res.total_steps);
                    ccmRenderSidebar();

                    // Chuyển sang bước tiếp theo (nếu còn)
                    const nextIdx = ccmSteps.findIndex((s, i) => i > ccmCurrentIndex && !s.is_done);
                    if (nextIdx >= 0) {
                        ccmGoToStep(nextIdx);
                    } else {
                        // Tất cả bước đã xong
                        ccmGoToStep(ccmCurrentIndex);
                    }
                } else {
                    Swal.fire('Lỗi', res.message, 'error');
                    $('#ccm-btn-complete-step').prop('disabled', false)
                        .html('<i class="fas fa-check me-2"></i> Ghi nhận & Chuyển bước');
                }
            },
            error: function () {
                Swal.fire('Lỗi', 'Không thể kết nối máy chủ', 'error');
                $('#ccm-btn-complete-step').prop('disabled', false)
                    .html('<i class="fas fa-check me-2"></i> Ghi nhận & Chuyển bước');
            }
        });
    }

    // Cập nhật progress bar
    function ccmUpdateProgress(done, total) {
        const pct = total > 0 ? Math.round((done / total) * 100) : 0;
        $('#ccm-progress-bar').css('width', pct + '%');
        $('#ccm-progress-text').text(done + ' / ' + total + ' bước');
    }

    // Hoàn thành toàn bộ vệ sinh
    function ccmFinishCampaign() {
        Swal.fire({
            icon: 'question',
            title: 'Xác nhận hoàn thành?',
            text: 'Sau khi xác nhận, trạng thái phòng sẽ được cập nhật thành "Đã vệ sinh".',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-flag-checkered me-1"></i> Hoàn thành',
            cancelButtonText: 'Kiểm tra lại'
        }).then(function (result) {
            if (!result.isConfirmed) return;

            $.ajax({
                url: '{{ route("pages.manu_env.cleaning_process.campaign.complete", ["campaign_id" => "CAMPAIGN_ID"]) }}'
                        .replace('CAMPAIGN_ID', ccmCampaignId),
                method: 'POST',
                data: { _token: '{{ csrf_token() }}' },
                success: function (res) {
                    if (res.success) {
                        $('#cleaningCampaignModal').modal('hide');
                        Swal.fire({
                            icon: 'success',
                            title: 'Hoàn thành!',
                            text: res.message,
                            timer: 2500,
                            showConfirmButton: false
                        }).then(function () {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Lỗi', res.message, 'error');
                    }
                },
                error: function () {
                    Swal.fire('Lỗi', 'Không thể kết nối máy chủ', 'error');
                }
            });
        });
    }
</script>
