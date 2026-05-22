@section('css')
    <style>
        .highlight-row { background-color: #ecfeff !important; }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: var(--primary) !important;
            border-color: var(--primary) !important;
            color: white !important;
            border-radius: 8px !important;
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
    </style>
@append

<div class="content-wrapper">
    <div class="card border-0 shadow-sm">
        <div class="card-header border-0 bg-transparent pt-4 pb-0 px-4">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <h3 class="card-title fw-bold text-dark">Danh sách Thiết Bị Sản Xuất</h3>
                <button class="btn btn-primary d-flex align-items-center px-4 fw-bold shadow-sm rounded-pill" 
                    data-toggle="modal" data-target="#createModal">
                    <i class="fas fa-plus-circle me-2"></i> Thêm mới
                </button>
            </div>
        </div>
        <div class="card-body">
            <table id="data_table_instrument" class="table table-hover w-100">
                <thead>
                    <tr>
                        <th style="width: 50px;">STT</th>
                        <th>Mã Thiết Bị</th>
                        <th>Tên Thiết Bị</th>
                        <th>Loại & Kết Nối</th>
                        <th>Công Đoạn</th>
                        <th>Người Tạo</th>
                        <th>Ngày Tạo</th>
                        <th class="text-center" style="width: 150px;">Thao Tác</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($datas as $data)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td class="fw-bold text-primary">{{ $data->code }}</td>
                            <td>{{ $data->name }}</td>
                            <td>
                                @if($data->type === 'scale')
                                    <span class="badge bg-success text-white px-2 py-1 small fw-bold"><i class="fas fa-balance-scale me-1"></i> Cân Điện Tử</span>
                                    <div class="small mt-1 text-muted" style="font-size: 0.75rem;">
                                        @if($data->connection_type === 'websocket')
                                            <span class="text-primary"><i class="fas fa-wifi me-1"></i> Wifi:</span> {{ $data->ip }}:{{ $data->port }} <span class="badge bg-light text-dark border ms-1">{{ strtoupper($data->brand) }}</span>
                                        @else
                                            <span class="text-secondary"><i class="fas fa-plug me-1"></i> Cáp:</span> <span class="badge bg-light text-dark border me-1">{{ strtoupper($data->brand) }}</span>({{ $data->baud_rate }}-{{ $data->data_bits }}-{{ strtoupper(substr($data->parity ?? 'N', 0, 1)) }}-{{ $data->stop_bits }})
                                        @endif
                                    </div>
                                @else
                                    <span class="badge bg-secondary text-white px-2 py-1 small fw-bold">Khác</span>
                                @endif
                            </td>
                            <td>
                                @if($data->stage_id)
                                    <span class="badge bg-light text-dark border px-2 py-1 small fw-bold">{{ $stages[$data->stage_id] ?? '-' }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td><span class="small fw-bold">{{ $data->created_by ?? '-' }}</span></td>
                            <td><span class="text-muted small">{{ \Carbon\Carbon::parse($data->created_at)->format('d/m/Y') }}</span></td>
                            <td class="text-center align-middle">
                                <div class="d-flex gap-1 justify-content-center">
                                    <button type="button" class="btn btn-sm btn-icon btn-light-warning border shadow-sm btn-edit" 
                                        data-id="{{ $data->id }}" 
                                        data-code="{{ $data->code }}"
                                        data-name="{{ $data->name }}"
                                        data-stage_id="{{ $data->stage_id }}"
                                        data-type="{{ $data->type }}"
                                        data-connection_type="{{ $data->connection_type }}"
                                        data-ip="{{ $data->ip }}"
                                        data-port="{{ $data->port }}"
                                        data-brand="{{ $data->brand }}"
                                        data-baud_rate="{{ $data->baud_rate }}"
                                        data-data_bits="{{ $data->data_bits }}"
                                        data-parity="{{ $data->parity }}"
                                        data-stop_bits="{{ $data->stop_bits }}"
                                        data-toggle="modal" 
                                        data-target="#updateModal" title="Sửa">
                                        <i class="fas fa-pen"></i>
                                    </button>

                                    <form class="form-delete d-inline" action="{{ route('pages.materData.instrument.delete') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="id" value="{{ $data->id }}">
                                        <button type="submit" class="btn btn-sm btn-icon btn-light-danger border shadow-sm btn-delete-confirm" 
                                            data-code="{{ $data->code }}" data-name="{{ $data->name }}" title="Xóa">
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
            if ($.fn.DataTable.isDataTable('#data_table_instrument')) {
                $('#data_table_instrument').DataTable().destroy();
            }

            $('#data_table_instrument').DataTable({
                paging: true,
                lengthChange: true,
                searching: true,
                ordering: true,
                info: true,
                autoWidth: false,
                pageLength: 25,
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
                modal.find('#update_code').val(button.data('code'));
                modal.find('#update_name').val(button.data('name'));
                modal.find('#update_stage_id').val(button.data('stage_id'));
                
                // Populate scale connection fields
                modal.find('#update_type').val(button.data('type') || 'other');
                modal.find('#update_connection_type').val(button.data('connection_type') || 'serial');
                modal.find('#update_ip').val(button.data('ip') || '');
                modal.find('#update_port').val(button.data('port') || '');
                modal.find('#update_brand').val(button.data('brand') || 'and');
                modal.find('#update_baud_rate').val(button.data('baud_rate') || 9600);
                modal.find('#update_data_bits').val(button.data('data_bits') || 8);
                modal.find('#update_parity').val(button.data('parity') || 'none');
                modal.find('#update_stop_bits').val(button.data('stop_bits') || 1);
                
                // Trigger visibility updates in update modal
                if (window.initUpdateModalScaleFields) {
                    window.initUpdateModalScaleFields();
                }
            });

            $('.form-delete').on('submit', function(e) {
                e.preventDefault();
                const form = this;
                const button = $(form).find('button');
                const code = button.data('code');
                const name = button.data('name');

                Swal.fire({
                    title: `Xác nhận xóa?`,
                    text: `Bạn có chắc chắn muốn xóa thiết bị: ${code} - ${name}?`,
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
