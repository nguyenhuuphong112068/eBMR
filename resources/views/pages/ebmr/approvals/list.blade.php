@extends('layout.master')

@section('title', 'Phê Duyệt Hồ Sơ Sản Xuất BMR')

@section('mainContent')
    <div class="content-wrapper">
        <div class="container-fluid py-4">
            <div class="row">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 text-navy fw-bold"><i class="fas fa-clipboard-check me-2"></i> Hồ Sơ Chờ Bạn Duyệt
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle table-striped">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>Mã Hồ Sơ</th>
                                            <th>Tên Hồ Sơ</th>
                                            <th>Người Thiết Kế</th>
                                            <th>Vai Trò Của Bạn</th>
                                            <th>Ngày Yêu Cầu</th>
                                            <th>Hạn Hoàn Thành</th>
                                            <th class="text-center">Thao Tác</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($templates as $t)
                                            <tr>
                                                <td class="fw-bold text-navy">{{ $t->document_code }}</td>
                                                <td>{{ $t->name }}</td>
                                                <td><i class="fas fa-user text-muted me-1"></i>
                                                    {{ $t->owner_name ?? 'N/A' }}</td>
                                                <td>
                                                    @if ($t->my_role === 'reviewer')
                                                        <span class="badge bg-soft-info cursor-pointer"
                                                            onclick="showWorkflowHistory('{{ $t->workflow_type }}', {{ $t->id }})"
                                                            title="Xem lịch sử duyệt"><i class="fas fa-search me-1"></i>
                                                            Kiểm tra (Reviewer)</span>
                                                    @elseif($t->my_role === 'approver')
                                                        <span class="badge bg-warning text-dark cursor-pointer"
                                                            onclick="showWorkflowHistory('{{ $t->workflow_type }}', {{ $t->id }})"
                                                            title="Xem lịch sử duyệt"><i
                                                                class="fas fa-check-double me-1"></i> Phê duyệt
                                                            (Approver)</span>
                                                    @elseif($t->my_role === 'authorizer')
                                                        <span class="badge bg-success cursor-pointer"
                                                            onclick="showWorkflowHistory('{{ $t->workflow_type }}', {{ $t->id }})"
                                                            title="Xem lịch sử duyệt"><i
                                                                class="fas fa-file-signature me-1"></i> Ban hành
                                                            (Authorizer)</span>
                                                    @endif
                                                </td>
                                                <td>{{ \Carbon\Carbon::parse($t->updated_at)->format('d/m/Y H:i') }}</td>
                                                <td>
                                                    @if ($t->my_due_date)
                                                        @php $isOverdue = \Carbon\Carbon::parse($t->my_due_date)->isPast() && !\Carbon\Carbon::parse($t->my_due_date)->isToday(); @endphp
                                                        <span class="badge {{ $isOverdue ? 'bg-danger' : 'bg-soft-info' }}"
                                                            title="{{ $t->my_reason ?? '' }}">
                                                            <i
                                                                class="far fa-clock me-1"></i>{{ \Carbon\Carbon::parse($t->my_due_date)->format('d/m/Y') }}
                                                        </span>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    <div class="btn-group shadow-sm rounded-pill overflow-hidden">
                                                        <a href="{{ $t->view_url }}" target="_blank"
                                                            class="btn btn-sm btn-light text-navy"
                                                            title="Xem nội dung và duyệt trong trình soạn thảo">
                                                            <i class="fas fa-eye"></i> Xem nội dung
                                                        </a>
                                                        {{-- Hồ sơ thiết kế eBMR: duyệt ngay trong trình soạn thảo (nút trên toolbar)
                                                     nên bỏ nút ở đây. Quy trình vệ sinh/clearance chưa có nút trong trang xem
                                                     nên vẫn giữ để không mất chức năng duyệt. --}}
                                                        @if ($t->workflow_type !== 'ebmr')
                                                            <button class="btn btn-sm btn-success text-white"
                                                                onclick="openPerformApprovalModal({{ $t->workflow_id }}, '{{ $t->workflow_type }}', 'approve')"
                                                                title="Duyệt">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                            <button class="btn btn-sm btn-danger text-white"
                                                                onclick="openPerformApprovalModal({{ $t->workflow_id }}, '{{ $t->workflow_type }}', 'reject')"
                                                                title="Từ chối">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="text-center text-muted py-4">
                                                    <i class="fas fa-inbox fa-3x mb-3" style="opacity:0.3"></i><br>
                                                    Chưa có hồ sơ thiết kế nào cần bạn duyệt lúc này.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Xử lý Duyệt -->
        <div class="modal fade" id="performApprovalModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content border-0 shadow-lg">
                    <form id="performApprovalForm">
                        @csrf
                        <input type="hidden" id="approvalWorkflowId" name="workflow_id">
                        <input type="hidden" id="approvalWorkflowType" name="workflow_type">
                        <input type="hidden" id="approvalAction" name="action">

                        <div class="modal-header text-white" id="modalApprovalHeader">
                            <h5 class="modal-title" id="modalApprovalTitle">Xử Lý Phê Duyệt</h5>
                            <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body p-4">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Ý kiến / Ghi chú (Optional)</label>
                                <textarea class="form-control" name="comment" rows="3" placeholder="Nhập ý kiến của bạn..."></textarea>
                            </div>
                            <div class="alert alert-warning border-0 shadow-none small mb-0" id="rejectWarning"
                                style="display:none;">
                                <i class="fas fa-exclamation-triangle me-1"></i> Lưu ý: Khi bạn từ chối, toàn bộ tiến trình
                                trình ký của hồ sơ này sẽ bị hủy bỏ và hồ sơ chuyển về trạng thái Nháp.
                            </div>
                        </div>
                        <div class="modal-footer bg-light p-3">
                            <button type="button" class="btn btn-white rounded-pill px-4" data-dismiss="modal">Hủy</button>
                            <button type="submit" class="btn rounded-pill px-4 shadow-sm" id="btnSubmitApproval">
                                Xác nhận
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <style>
        .bg-navy {
            background-color: var(--primary-navy) !important;
        }

        .text-navy {
            color: var(--primary-navy) !important;
        }

        .bg-soft-info {
            background-color: rgba(23, 162, 184, 0.1);
            color: #17a2b8;
        }

        .btn-light {
            background-color: #f8f9fa;
            border: 1px solid #dae0e5;
        }

        .btn-light:hover {
            background-color: #e2e6ea;
        }

        .rounded-pill {
            border-radius: 50px !important;
        }

        #modalApprovalHeader.bg-success {
            background-color: #28a745 !important;
        }

        #modalApprovalHeader.bg-danger {
            background-color: #dc3545 !important;
        }
    </style>
@endsection

@section('script')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function openPerformApprovalModal(workflowId, workflowType, action) {
            $('#performApprovalForm')[0].reset();
            $('#approvalWorkflowId').val(workflowId);
            $('#approvalWorkflowType').val(workflowType);
            $('#approvalAction').val(action);

            const header = $('#modalApprovalHeader');
            const title = $('#modalApprovalTitle');
            const btn = $('#btnSubmitApproval');

            header.removeClass('bg-success bg-danger');
            btn.removeClass('btn-success btn-danger');

            if (action === 'approve') {
                header.addClass('bg-success');
                title.html('<i class="fas fa-check-circle me-2"></i> Đồng ý Phê Duyệt');
                btn.addClass('btn-success').html('<i class="fas fa-check me-2"></i> Phê Duyệt');
                $('#rejectWarning').hide();
            } else {
                header.addClass('bg-danger');
                title.html('<i class="fas fa-times-circle me-2"></i> Từ chối Hồ Sơ');
                btn.addClass('btn-danger').html('<i class="fas fa-times me-2"></i> Từ Chối');
                $('#rejectWarning').show();
            }

            $('#performApprovalModal').modal('show');
        }

        $('#performApprovalForm').submit(function(e) {
            e.preventDefault();
            const data = $(this).serialize();
            const btn = $('#btnSubmitApproval');
            btn.prop('disabled', true);

            $.post('{{ route('pages.ebmr.processApproval') }}', data, function(res) {
                btn.prop('disabled', false);
                if (res.success) {
                    Swal.fire('Thành công', res.message, 'success').then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Lỗi', res.message, 'error');
                }
            }).fail(function() {
                btn.prop('disabled', false);
                Swal.fire('Lỗi', 'Có lỗi xảy ra khi thực hiện xử lý.', 'error');
            });
        });
    </script>
@endsection
