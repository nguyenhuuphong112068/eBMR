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
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="templateTable" class="table table-hover align-middle" style="width:100%">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Mã Hồ Sơ</th>
                                        <th>Tên Hồ Sơ</th>
                                        <th>Ấn Bản</th>
                                        <th>Dạng Bào Chế</th>
                                        <th>Cỡ Lô</th>
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
                                        <td>{{ $t->batch_size }}</td>
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

@endsection

@section('script')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function() {
        $('#templateTable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.10.24/i18n/Vietnamese.json'
            },
            order: [[6, 'desc']]
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
</script>
@endsection
