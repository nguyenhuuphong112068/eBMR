@extends('layout.master')

@section('title', 'Ban Hành Hồ Sơ Lô')

@section('mainContent')
<div class="content-wrapper">
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <div class="card border-0 shadow-sm overflow-hidden">
                    <div class="card-header bg-primary py-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 text-white fw-bold"><i class="fas fa-file-export me-2"></i> Hồ Sơ Hiện Hành & Ban Hành Lô</h5>
                        <div class="badge bg-white text-primary rounded-pill px-3">{{ $templates->count() }} Hồ sơ sẵn sàng</div>
                    </div>
                    <div class="card-body p-4">
                        <div class="alert alert-info border-0 shadow-none d-flex align-items-center mb-4">
                            <i class="fas fa-info-circle fa-2x me-3"></i>
                            <div>
                                <h6 class="fw-bold mb-1">Cần lưu ý:</h6>
                                <p class="mb-0 small">Danh sách dưới đây là các hồ sơ đã được phê duyệt và ban hành chính thức. Người phụ trách sản xuất chọn hồ sơ và nhập số lô để bắt đầu quá trình ghi chép dữ liệu.</p>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table id="issuanceTable" class="table table-hover align-middle" style="width:100%">
                                <thead class="bg-light text-navy">
                                    <tr>
                                        <th>Mã Hồ Sơ</th>
                                        <th>Tên Hồ Sơ</th>
                                        <th>Ấn Bản</th>
                                        <th>Dạng Bào Chế</th>
                                        <th>Cỡ Lô</th>
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
                                        <td>{{ $t->batch_size }}</td>
                                        <td>{{ \Carbon\Carbon::parse($t->effective_date)->format('d/m/Y') }}</td>
                                        <td class="text-center">
                                            <button class="btn btn-primary rounded-pill px-3 shadow-sm btn-sm" onclick="openIssueModal({{ $t->id }}, '{{ $t->name }}')">
                                                <i class="fas fa-rocket me-1"></i> Ban hành lô
                                            </button>
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
</div>

<style>
    .bg-navy { background-color: #003A4F !important; }
    .text-navy { color: #003A4F !important; }
    .bg-soft-info { background-color: rgba(23, 162, 184, 0.1); color: #17a2b8; }
</style>
@endsection

@section('script')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function() {
        $('#issuanceTable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.10.24/i18n/Vietnamese.json'
            },
            order: [[0, 'asc']]
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
</script>
@endsection
