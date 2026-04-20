@extends('layout.master')

@section('title', 'Hồ Sơ Sản Xuất BMR')

@section('mainContent')
<div class="content-wrapper">
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 text-navy fw-bold"><i class="fas fa-file-medical me-2"></i> Danh Sách Hồ Sơ Sản Xuất BMR</h5>
                        <button class="btn btn-navy rounded-pill px-4 shadow-sm" onclick="openCreateModal()">
                            <i class="fas fa-plus me-2"></i> Soạn mới hồ sơ
                        </button>
                    </div>
                    <div class="card-body p-4">
                        <div class="table-responsive">
                            <table id="draftingTable" class="table table-hover align-middle bmr-datatable" style="width:100%">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Mã Hồ Sơ</th>
                                        <th>Tên Hồ Sơ</th>
                                        <th>Ấn Bản</th>
                                        <th>Dạng Bào Chế</th>
                                        <th>Trạng Thái</th>
                                        <th>Người Sở Hữu</th>
                                        <th>Ngày Hiệu Lực</th>
                                        <th class="text-center">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($templates as $t)
                                    <tr>
                                        <td class="fw-bold text-navy">{{ $t->document_code }}</td>
                                        <td>{{ $t->name }}</td>
                                        <td><span class="badge bg-soft-info">{{ $t->edition }}</span></td>
                                        <td>{{ $t->dosage_form }}</td>
                                        <td>
                                            @if($t->status === 'draft')
                                                <span class="badge bg-secondary"><i class="fas fa-edit me-1"></i> Nháp</span>
                                            @else
                                                <span class="badge bg-warning text-dark"><i class="fas fa-paper-plane me-1"></i> Chờ duyệt</span>
                                            @endif
                                        </td>
                                        <td><i class="fas fa-user-circle me-1 text-muted"></i> {{ $t->owner_name ?? 'N/A' }}</td>
                                        <td>{{ \Carbon\Carbon::parse($t->effective_date)->format('d/m/Y') }}</td>
                                        <td class="text-center">
                                            <div class="btn-group shadow-sm rounded-pill overflow-hidden">
                                                <a href="{{ route('pages.ebmr.designer', $t->id) }}" class="btn btn-sm btn-white text-navy" title="Thiết kế nội dung">
                                                    <i class="fas fa-pencil-ruler"></i>
                                                </a>
                                                <button class="btn btn-sm btn-white text-info" onclick="openEditModal({{ $t->id }})" title="Sửa thông tin gốc">
                                                    <i class="fas fa-cog"></i>
                                                </button>
                                                <button class="btn btn-sm btn-white text-success" onclick="openWorkflowModal({{ $t->id }})" title="Trình ký">
                                                    <i class="fas fa-paper-plane"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Soạn mới / Cập nhật -->
    <div class="modal fade" id="templateMetadataModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content border-0 shadow-lg">
                <form id="metadataForm">
                    @csrf
                    <input type="hidden" id="templateId" name="id">
                    <div class="modal-header bg-navy text-white">
                        <h5 class="modal-title" id="modalTitle"><i class="fas fa-file-medical me-2"></i> Soạn Mới Hồ Sơ Gốc</h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Mã Hồ Sơ <span class="text-danger">*</span></label>
                                <input type="text" class="form-control rounded-pill" name="document_code" id="docCode" required placeholder="VD: HS-P-01">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Ấn Bản <span class="text-danger">*</span></label>
                                <input type="text" class="form-control rounded-pill" name="edition" id="edition" required placeholder="VD: 01">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Tên Hồ Sơ <span class="text-danger">*</span></label>
                            <input type="text" class="form-control rounded-pill" name="name" id="templateName" required placeholder="VD: Hồ sơ lô sản xuất Paracetamol 500mg">
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Dạng Bào Chế</label>
                                <input type="text" class="form-control rounded-pill" name="dosage_form" id="dosageForm" placeholder="VD: Viên nén">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Cỡ Lô</label>
                                <input type="text" class="form-control rounded-pill" name="batch_size" id="batchSize" placeholder="VD: 1.000.000 viên">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Ngày Hiệu Lực <span class="text-danger">*</span></label>
                                <input type="date" class="form-control rounded-pill" name="effective_date" id="effectiveDate" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light p-3">
                        <button type="button" class="btn btn-white rounded-pill px-4" data-dismiss="modal">Hủy bỏ</button>
                        <button type="submit" class="btn btn-navy rounded-pill px-4 shadow-sm">
                            <i class="fas fa-save me-2"></i> Lưu dữ liệu
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Trình Ký (Workflow) -->
    <div class="modal fade" id="workflowModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content border-0 shadow-lg">
                <form id="workflowForm">
                    @csrf
                    <input type="hidden" id="workflowTemplateId" name="template_id">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title"><i class="fas fa-paper-plane me-2"></i> Thiết lập luồng Trình Ký</h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="alert alert-info border-0 shadow-none small mb-4">
                            <i class="fas fa-info-circle me-1"></i> Sau khi trình ký, hồ sơ thiết kế sẽ được gửi đến những người liên quan để xem xét và ban hành.
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold text-navy"><i class="fas fa-search me-1"></i> Người kiểm tra (Reviewers)</label>
                            <select class="form-select select2-workflow" name="reviewers[]" id="wfReviewers" multiple="multiple" data-placeholder="Chọn một hoặc nhiều người...">
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">Có thể chọn nhiều người kiểm tra (VD: QA, QC, Sản xuất).</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold text-navy"><i class="fas fa-check-double me-1"></i> Người phê duyệt (Approver) <span class="text-danger">*</span></label>
                            <select class="form-select select2-workflow" name="approver" id="wfApprover" required>
                                <option value="">-- Chọn một người phê duyệt --</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">Trưởng phòng/Giám đốc phê duyệt nội dung.</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold text-navy"><i class="fas fa-file-signature me-1"></i> Người cho phép ban hành <span class="text-danger">*</span></label>
                            <select class="form-select select2-workflow" name="authorizer" id="wfAuthorizer" required>
                                <option value="">-- Chọn một người ban hành --</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">Giám đốc chất lượng/Người đại diện ban hành chính thức.</small>
                        </div>
                    </div>
                    <div class="modal-footer bg-light p-3">
                        <button type="button" class="btn btn-white rounded-pill px-4" data-dismiss="modal">Hủy bỏ</button>
                        <button type="submit" class="btn btn-success rounded-pill px-4 shadow-sm">
                            <i class="fas fa-paper-plane me-2"></i> Gửi trình ký
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    .bg-navy { background-color: var(--primary-navy) !important; }
    .text-navy { color: var(--primary-navy) !important; }
    .btn-navy { background-color: var(--primary-navy) !important; color: white !important; }
    .btn-navy:hover { opacity: 0.9; }
    .bg-soft-info { background-color: rgba(23, 162, 184, 0.1); color: #17a2b8; }
    .form-control:focus { border-color: var(--primary-navy); box-shadow: 0 0 0 0.2rem rgba(0, 58, 79, 0.15); }
    .rounded-pill { border-radius: 50px !important; }
</style>

    <!-- Modal Ban Hành (Issue Record) -->
    <div class="modal fade" id="issueModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content border-0 shadow-lg">
                <form id="issueForm">
                    @csrf
                    <input type="hidden" id="issueTemplateId" name="template_id">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title font-weight-bold"><i class="fas fa-file-export me-2"></i> Ban Hành Hồ Sơ Lô</h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted text-uppercase">Hồ sơ mẫu đang chọn</label>
                            <div id="issueTemplateNameDisplay" class="p-3 mb-3 bg-light rounded text-navy fw-bold border" style="font-size: 1.1rem;"></div>
                        </div>
                        
                        <div class="mb-1">
                            <label class="form-label fw-bold">Số Lô Sản Xuất (Batch No.) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control rounded-pill px-4" name="batch_number" id="batchNumber" required 
                                placeholder="VD: BN2024-001" style="height: 50px; border: 2px solid #007bff; font-weight: bold; font-size: 1.2rem;">
                        </div>
                        <p class="text-muted small mt-2"><i class="fas fa-info-circle me-1"></i> Số lô này sẽ được dùng để định danh cho hồ sơ lô sản xuất thực tế trên hệ thống.</p>
                    </div>
                    <div class="modal-footer bg-light p-3">
                        <button type="button" class="btn btn-light rounded-pill px-4" data-dismiss="modal">Hủy bỏ</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">
                            <i class="fas fa-rocket me-2"></i> Xác nhận Ban Hành
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function() {
        $('.bmr-datatable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.10.24/i18n/Vietnamese.json'
            },
            order: [[0, 'asc']]
        });
        
        // Initialize Select2 in the modal
        $('.select2-workflow').select2({
            theme: 'bootstrap4',
            width: '100%',
            dropdownParent: $('#workflowModal')
        });

        $('#metadataForm').submit(function(e) {
            e.preventDefault();
            const data = $(this).serialize();
            
            $.post('{{ route('pages.ebmr.storeTemplateMetadata') }}', data, function(res) {
                if (res.success) {
                    Swal.fire('Thành công', res.message, 'success').then(() => {
                        location.reload();
                    });
                }
            });
        });
    });

    function openIssueModal(id, name) {
        $('#issueTemplateId').val(id);
        $('#issueTemplateNameDisplay').text(name);
        $('#batchNumber').val('');
        $('#issueModal').modal('show');
    }

    $('#issueForm').on('submit', function(e) {
        e.preventDefault();
        const btn = $(this).find('button[type="submit"]');
        const originalHtml = btn.html();
        
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i> Đang xử lý...');

        $.ajax({
            url: '{{ route('pages.ebmr.issueTemplate') }}',
            method: 'POST',
            data: $(this).serialize(),
            success: function(res) {
                if (res.success) {
                    Swal.fire({
                        title: 'Thành công!',
                        text: res.message,
                        icon: 'success',
                        confirmButtonColor: '#003A4F'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Lỗi', res.message, 'error');
                    btn.prop('disabled', false).html(originalHtml);
                }
            },
            error: function(err) {
                let msg = 'Đã có lỗi xảy ra';
                if(err.responseJSON && err.responseJSON.message) msg = err.responseJSON.message;
                Swal.fire('Lỗi', msg, 'error');
                btn.prop('disabled', false).html(originalHtml);
            }
        });
    });

    function openCreateModal() {
        $('#metadataForm')[0].reset();
        $('#templateId').val('');
        $('#modalTitle').html('<i class="fas fa-file-medical me-2"></i> Soạn Mới Hồ Sơ Gốc');
        $('#templateMetadataModal').modal('show');
    }

    function openEditModal(id) {
        $('#metadataForm')[0].reset();
        $('#modalTitle').html('<i class="fas fa-cog me-2"></i> Cập Nhật Thông Tin Hồ Sơ Gốc');
        
        $.get(`/ebmr/templates/${id}/data`, function(data) {
            $('#templateId').val(data.id);
            $('#docCode').val(data.document_code);
            $('#edition').val(data.edition);
            $('#templateName').val(data.name);
            $('#dosageForm').val(data.dosage_form);
            $('#batchSize').val(data.batch_size);
            $('#effectiveDate').val(data.effective_date);
            $('#templateMetadataModal').modal('show');
        });
    }

    function openWorkflowModal(id) {
        $('#workflowForm')[0].reset();
        $('#workflowTemplateId').val(id);
        
        // Reset selections
        $('#wfReviewers').val([]).trigger('change');
        $('#wfApprover').val('').trigger('change');
        $('#wfAuthorizer').val('').trigger('change');

        // Load existing workflows if any
        $.get(`/ebmr/templates/${id}/workflow`, function(data) {
            let reviewers = [];
            data.forEach(item => {
                if (item.role === 'reviewer') reviewers.push(item.user_id);
                if (item.role === 'approver') $('#wfApprover').val(item.user_id).trigger('change');
                if (item.role === 'authorizer') $('#wfAuthorizer').val(item.user_id).trigger('change');
            });
            $('#wfReviewers').val(reviewers).trigger('change');
            $('#workflowModal').modal('show');
        });
    }

    $('#workflowForm').submit(function(e) {
        e.preventDefault();
        const id = $('#workflowTemplateId').val();
        const data = $(this).serialize();
        
        $.post(`/ebmr/templates/${id}/workflow`, data, function(res) {
            if (res.success) {
                Swal.fire('Thành công', res.message, 'success').then(() => {
                    $('#workflowModal').modal('hide');
                    location.reload();
                });
            }
        });
    });
</script>
@endsection
