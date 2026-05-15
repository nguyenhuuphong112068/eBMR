@section('css')
    <style>
        .highlight-row {
            background-color: #ecfeff !important;
        }

        /* DataTable Personalization */
        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: var(--primary) !important;
            border-color: var(--primary) !important;
            color: white !important;
            border-radius: 8px !important;
        }

        .dataTables_wrapper .dataTables_filter input {
            border: 2px solid #e2e8f0 !important;
            border-radius: 10px !important;
            padding: 6px 12px !important;
            transition: all var(--transition);
            background: white !important;
        }

        .dataTables_wrapper .dataTables_filter input:focus {
            border-color: var(--primary) !important;
            outline: none;
            box-shadow: 0 0 0 4px rgba(8, 145, 178, 0.1);
        }

        .table thead th {
            background-color: #f1f5f9;
            color: var(--bg-dark);
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
            border-bottom: 2px solid #e2e8f0 !important;
            vertical-align: middle !important;
        }

        .dataTables_scrollBody {
            border-bottom: 1px solid #e2e8f0;
        }
    </style>
@append

<div class="content-wrapper">
    <div class="card border-0 shadow-sm">
        <div class="card-header border-0 bg-transparent pt-4 pb-0 px-4">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <h3 class="card-title fw-bold text-dark">Danh sách Qui Cách Đóng Gói</h3>
                <button class="btn btn-primary d-flex align-items-center px-4 fw-bold shadow-sm rounded-pill" 
                    data-toggle="modal" data-target="#createModal">
                    <i class="fas fa-plus-circle me-2"></i> Thêm mới
                </button>
            </div>
        </div>
        <div class="card-body">
            <table id="data_table_specification" class="table table-hover w-100">
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Tên Qui Cách</th>
                        <th>Người Tạo</th>
                        <th>Ngày Tạo</th>
                        <th class="text-center">Thao Tác</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($datas as $data)
                        <tr>
                            <td>{{ $loop->iteration }} </td>
                            <td class="fw-bold text-primary">{{ $data->name }}</td>
                            <td><span class="small fw-bold">{{ $data->created_by ?? '-' }}</span></td>
                            <td><span class="text-muted small">{{ \Carbon\Carbon::parse($data->created_at)->format('d/m/Y') }}</span></td>
                            <td class="text-center align-middle">
                                <div class="d-flex gap-1 justify-content-center">
                                    <button type="button" class="btn btn-sm btn-icon btn-light-warning border shadow-sm btn-edit" 
                                        data-id="{{ $data->id }}" 
                                        data-name="{{ $data->name }}"
                                        data-toggle="modal" 
                                        data-target="#updateModal" title="Sửa">
                                        <i class="fas fa-pen"></i>
                                    </button>

                                    <form class="form-delete d-inline" action="{{ route('pages.materData.specification.destroy') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="id" value="{{ $data->id }}">
                                        <button type="submit" class="btn btn-sm btn-icon btn-light-danger border shadow-sm btn-delete-confirm" 
                                            data-name="{{ $data->name }}" title="Xóa">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@section('script')
    @if (session('success'))
        <script>
            Swal.fire({
                title: 'Thành công!',
                text: '{{ session('success') }}',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            });
        </script>
    @endif

    <script>
        $(document).ready(function() {
            if ($.fn.DataTable.isDataTable('#data_table_specification')) {
                $('#data_table_specification').DataTable().destroy();
            }

            const table = $('#data_table_specification').DataTable({
                paging: true,
                lengthChange: true,
                searching: true,
                ordering: true,
                info: true,
                autoWidth: false,
                pageLength: 25,
                scrollY: "55vh",
                scrollX: true,
                scrollCollapse: true,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Tất cả"]],
                language: {
                    search: "Tìm nhanh:",
                    lengthMenu: "Xem _MENU_ dòng",
                    info: "Dòng _START_ - _END_ / Tổng _TOTAL_",
                    paginate: {
                        previous: "<i class='fas fa-chevron-left'></i>",
                        next: "<i class='fas fa-chevron-right'></i>"
                    }
                }
            });

            $('.btn-edit').click(function() {
                const button = $(this);
                const modal = $('#updateModal');
                modal.find('#update_id').val(button.data('id'));
                modal.find('#update_name').val(button.data('name'));
            });

            $('.form-delete').on('submit', function(e) {
                e.preventDefault();
                const form = this;
                const name = $(form).find('button').data('name');

                Swal.fire({
                    title: `Xác nhận xóa?`,
                    text: `Bạn có chắc chắn muốn xóa qui cách: ${name}?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Xóa ngay',
                    cancelButtonText: 'Hủy'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });
    </script>
@append
