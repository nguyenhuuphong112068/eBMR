{{-- Layout master không @yield('css') nên KHÔNG dùng @section('css') — style phải
     đặt nội tuyến trong nội dung trang thì mới được render. --}}
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

        /* Kiểu con dấu mộc: khung viền + tối đa 3 dòng (tiêu đề / nội dung chính / dòng phụ).
           Kích thước dấu chỉnh bằng font-size của khung (inline style, theo % đã lưu) —
           các dòng bên trong dùng đơn vị em nên phóng to/thu nhỏ đồng bộ. */
        .seal-stamp-preview {
            display: inline-block;
            border: 2px solid currentColor;
            border-radius: 6px;
            white-space: nowrap;
            text-align: center;
            overflow: hidden;
            font-size: 1rem;
        }

        .seal-stamp-preview.seal-border-double {
            border: 4px double currentColor;
        }

        .seal-stamp-preview .seal-line-header {
            display: block;
            font-size: 0.7em;
            font-weight: 700;
            padding: 0.15em 1.1em;
            border-bottom: 1.5px solid currentColor;
        }

        .seal-stamp-preview .seal-line-content {
            display: block;
            font-size: 0.95em;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 0.2em 1em;
        }

        .seal-stamp-preview .seal-line-footer {
            display: block;
            font-size: 0.7em;
            font-weight: 600;
            padding: 0.15em 1.1em;
            border-top: 1.5px solid currentColor;
        }
</style>

<div class="content-wrapper">
    <div class="card border-0 shadow-sm">
        <div class="card-header border-0 bg-transparent pt-4 pb-0 px-4">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <h3 class="card-title fw-bold text-dark">Danh sách Con Dấu</h3>
                <button class="btn btn-primary d-flex align-items-center px-4 fw-bold shadow-sm rounded-pill"
                    data-toggle="modal" data-target="#createModal">
                    <i class="fas fa-plus-circle me-2"></i> Thêm mới
                </button>
            </div>
        </div>
        <div class="card-body">
            <table id="data_table_seal" class="table table-hover w-100">
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Tên Con Dấu</th>
                        <th>Nội Dung Dấu</th>
                        <th class="text-center">Xem Trước</th>
                        <th class="text-center">Trạng Thái</th>
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
                            <td class="fw-medium">{{ $data->content }}</td>
                            <td class="text-center">
                                <span class="seal-stamp-preview {{ ($data->border_style ?? 'double') === 'double' ? 'seal-border-double' : '' }}"
                                    style="color: {{ $data->color }}; font-size: {{ ($data->size ?? 100) / 100 }}rem;">
                                    @if (!empty($data->header))
                                        <span class="seal-line-header">{{ $data->header }}</span>
                                    @endif
                                    <span class="seal-line-content">{{ $data->content }}</span>
                                    @if (!empty($data->footer))
                                        <span class="seal-line-footer">{{ $data->footer }}</span>
                                    @endif
                                </span>
                            </td>
                            <td class="text-center">
                                @if ($data->active)
                                    <span
                                        class="badge bg-light-success text-success border border-success px-3 rounded-pill">Hoạt
                                        động</span>
                                @else
                                    <span
                                        class="badge bg-light-danger text-danger border border-danger px-3 rounded-pill">Tạm
                                        ngưng</span>
                                @endif
                            </td>
                            <td><span class="small fw-bold">{{ $data->created_by ?? '-' }}</span></td>
                            <td><span
                                    class="text-muted small">{{ \Carbon\Carbon::parse($data->created_at)->format('d/m/Y') }}</span>
                            </td>
                            <td class="text-center align-middle">
                                <div class="d-flex gap-1 justify-content-center">
                                    <button type="button"
                                        class="btn btn-sm btn-icon btn-light-warning border shadow-sm btn-edit"
                                        data-id="{{ $data->id }}" data-name="{{ $data->name }}"
                                        data-header="{{ $data->header }}" data-content="{{ $data->content }}"
                                        data-footer="{{ $data->footer }}" data-color="{{ $data->color }}"
                                        data-border="{{ $data->border_style ?? 'double' }}"
                                        data-size="{{ $data->size ?? 100 }}"
                                        data-toggle="modal" data-target="#updateModal" title="Sửa">
                                        <i class="fas fa-pen"></i>
                                    </button>

                                    <form class="form-deActive d-inline"
                                        action="{{ route('pages.materData.seal.deActive') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="id" value="{{ $data->id }}">
                                        <input type="hidden" name="active" value="{{ $data->active }}">
                                        <button type="submit"
                                            class="btn btn-sm btn-icon {{ $data->active ? 'btn-light-danger' : 'btn-light-success' }} border shadow-sm btn-deactive-confirm"
                                            data-name="{{ $data->name }}" data-active="{{ $data->active }}"
                                            title="{{ $data->active ? 'Vô hiệu hóa' : 'Kích hoạt' }}">
                                            <i class="fas fa-{{ $data->active ? 'lock' : 'unlock' }}"></i>
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
            if ($.fn.DataTable.isDataTable('#data_table_seal')) {
                $('#data_table_seal').DataTable().destroy();
            }

            $('#data_table_seal').DataTable({
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
                lengthMenu: [
                    [10, 25, 50, 100, -1],
                    [10, 25, 50, 100, "Tất cả"]
                ],
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
                modal.find('#update_header').val(button.data('header'));
                modal.find('#update_content').val(button.data('content'));
                modal.find('#update_footer').val(button.data('footer'));
                modal.find('#update_color').val(button.data('color'));
                modal.find('#update_border').val(button.data('border') || 'double');
                modal.find('#update_size').val(button.data('size') || 100);
                updateSealPreview('update');
            });

            $('.form-deActive').on('submit', function(e) {
                e.preventDefault();
                const form = this;
                const name = $(form).find('button').data('name');
                const active = $(form).find('button').data('active');
                const actionText = active ? 'vô hiệu hóa' : 'kích hoạt';

                Swal.fire({
                    title: `Xác nhận ${actionText}?`,
                    text: `Bạn có chắc chắn muốn ${actionText} con dấu: ${name}?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#0891b2',
                    cancelButtonColor: '#ef4444',
                    confirmButtonText: 'Xác nhận',
                    cancelButtonText: 'Hủy'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });

        // Cập nhật ô xem trước con dấu trong modal thêm mới / cập nhật
        // Dựng lại đủ 3 dòng (tiêu đề / nội dung chính / dòng phụ) + kiểu viền + kích thước theo form
        function updateSealPreview(prefix) {
            const header = ($('#' + prefix + '_header').val() || '').trim();
            const content = ($('#' + prefix + '_content').val() || '').trim() || 'NỘI DUNG CHÍNH';
            const footer = ($('#' + prefix + '_footer').val() || '').trim();
            const color = $('#' + prefix + '_color').val() || '#dc3545';
            const border = $('#' + prefix + '_border').val() || 'double';
            const size = parseInt($('#' + prefix + '_size').val(), 10) || 100;
            $('#' + prefix + '_size_label').text(size + '%');

            const stamp = $('#' + prefix + '_seal_preview');
            stamp.empty()
                .css({ color: color, 'font-size': (size / 100) + 'rem' })
                .toggleClass('seal-border-double', border === 'double');
            if (header) stamp.append($('<span class="seal-line-header"></span>').text(header));
            stamp.append($('<span class="seal-line-content"></span>').text(content));
            if (footer) stamp.append($('<span class="seal-line-footer"></span>').text(footer));
        }
    </script>
@append
