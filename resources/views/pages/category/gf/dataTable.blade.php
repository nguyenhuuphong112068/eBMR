<div class="content-wrapper">
    <div class="container-fluid py-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 text-navy fw-bold"><i class="fas fa-layer-group me-2"></i> DANH MỤC BIỂU MẪU DÙNG CHUNG
                </h5>
                <button class="btn btn-primary rounded-pill px-4 shadow-sm" data-toggle="modal"
                    data-target="#createGfModal">
                    <i class="fas fa-plus me-2"></i> THÊM MỚI
                </button>
            </div>
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table id="gfTable" class="table table-hover align-middle w-100">
                        <thead class="bg-light">
                            <tr>
                                <th>Mã Biểu Mẫu</th>
                                <th>Tên Biểu Mẫu</th>
                                <th>SOP Liên Quan</th>
                                <th>Trạng Thái</th>
                                <th class="text-center">Thao Tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($datas as $data)
                                <tr data-href="{{ route('pages.ebmr.templates') }}?type=GF&code={{ urlencode($data->code) }}">
                                    <td class="fw-bold text-navy">{{ $data->code }}</td>
                                    <td>{{ $data->name }}</td>
                                    <td>{{ $data->relatived_sop_no }}</td>
                                    <td>
                                        <span class="badge {{ $data->active ? 'bg-success' : 'bg-danger' }}">
                                            {{ $data->active ? 'Đang Hoạt Động' : 'Ngưng Hoạt Động' }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-outline-info rounded-circle me-1"
                                            onclick="editGf({{ json_encode($data) }})" data-toggle="modal"
                                            data-target="#updateGfModal">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger rounded-circle"
                                            onclick="confirmDelete('{{ route('pages.category.gf.delete', ['id' => $data->id]) }}')">
                                            <i class="fas fa-trash"></i>
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

<style>
    #gfTable tbody tr[data-href] {
        cursor: pointer;
        transition: background-color 0.15s ease-in-out;
    }
    #gfTable tbody tr[data-href]:hover {
        background-color: #e0f2fe !important;
    }
</style>
@section('script')
<script>
    $(document).ready(function() {
        $('#gfTable tbody').on('click', 'tr[data-href]', function(e) {
            if ($(e.target).closest('td').is(':last-child') || $(e.target).closest('button, a, input, form, select').length) {
                return;
            }
            window.location.href = $(this).data('href');
        });
    });

    function editGf(data) {
        $('#up_id').val(data.id);
        $('#up_code').val(data.code);
        $('#up_name').val(data.name);
        $('#up_relatived_sop_no').val(data.relatived_sop_no);
    }

    function confirmDelete(url) {
        if (confirm('Bạn có chắc chắn muốn xóa danh mục này?')) {
            window.location.href = url;
        }
    }
</script>
@append
