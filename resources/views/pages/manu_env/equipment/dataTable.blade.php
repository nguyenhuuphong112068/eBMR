@section('css')
    <style>
        .highlight-row {
            background-color: #ecfeff !important;
        }

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

        .cal-label-container {
            border: 2px solid #000;
            padding: 0;
            background: #fff;
            color: #000;
            font-family: Arial, sans-serif;
            width: 100%;
        }

        .cal-label-header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding: 10px;
        }

        .cal-label-title {
            background-color: #8bc34a;
            /* Green matching image */
            text-align: center;
            border-bottom: 2px solid #000;
            padding: 10px;
            font-weight: bold;
        }

        .cal-label-info {
            padding: 10px;
        }

        .cal-label-table {
            width: 100%;
            border-collapse: collapse !important;
            border: 1px solid #000 !important;
        }

        .cal-label-table th,
        .cal-label-table td {
            border: 1px solid #000 !important;
            padding: 8px 5px !important;
            text-align: center !important;
            font-size: 13px !important;
            color: #000 !important;
        }

        .cal-label-table th {
            font-weight: bold !important;
            background-color: transparent !important;
            text-transform: none !important;
        }

        .cal-label-table tbody tr:nth-child(even) {
            background-color: #f2f2f2 !important;
        }
    </style>
@append

<div class="content-wrapper">
    <div class="card border-0 shadow-sm">
        <div class="card-header border-0 bg-transparent pt-4 pb-0 px-4">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <h3 class="card-title fw-bold text-dark mb-0">Danh sách Thiết Bị Sản Xuất</h3>
                <div class="d-flex align-items-center gap-3">
                    <select class="form-select form-select-sm border-secondary shadow-sm"
                        onchange="window.location.href='?department=' + this.value"
                        style="width: auto; min-width: 200px; padding: 0.375rem 2.25rem 0.375rem 0.75rem; border-radius: 50rem;">
                        <option value="">-- Tất cả Phân Xưởng --</option>
                        @foreach ($departments as $dept)
                            <option value="{{ $dept }}"
                                {{ isset($selectedDepartment) && $selectedDepartment == $dept ? 'selected' : '' }}>
                                Phân xưởng {{ $dept }}</option>
                        @endforeach
                    </select>

                    <button class="btn btn-primary d-flex align-items-center px-4 fw-bold shadow-sm rounded-pill"
                        data-toggle="modal" data-target="#createModal">
                        <i class="fas fa-plus-circle me-2"></i> Thêm mới
                    </button>
                </div>
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
                        <th>SOP Vận Hành</th>
                        <th>SOP Vệ Sinh</th>
                        <th>Phân Loại</th>
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
                            <td>
                                {{ $data->name }}
                                <div class="equip-status-badges mt-1" data-code="{{ $data->code }}"></div>
                            </td>
                            <td>
                                @if ($data->type === 'scale')
                                    <span class="badge bg-success text-white px-2 py-1 small fw-bold"><i
                                            class="fas fa-balance-scale me-1"></i> Cân Điện Tử</span>
                                    <div class="small mt-1 text-muted" style="font-size: 0.75rem;">
                                        @if ($data->connection_type === 'websocket')
                                            <span class="text-primary"><i class="fas fa-wifi me-1"></i> Wifi:</span>
                                            {{ $data->ip }}:{{ $data->port }} <span
                                                class="badge bg-light text-dark border ms-1">{{ strtoupper($data->brand) }}</span>
                                        @else
                                            <span class="text-secondary"><i class="fas fa-plug me-1"></i> Cáp:</span>
                                            <span
                                                class="badge bg-light text-dark border me-1">{{ strtoupper($data->brand) }}</span>({{ $data->baud_rate }}-{{ $data->data_bits }}-{{ strtoupper(substr($data->parity ?? 'N', 0, 1)) }}-{{ $data->stop_bits }})
                                        @endif
                                    </div>
                                @else
                                    <span class="badge bg-secondary text-white px-2 py-1 small fw-bold">Khác</span>
                                @endif
                            </td>
                            <td>
                                @if ($data->stage_id)
                                    <span
                                        class="badge bg-light text-dark border px-2 py-1 small fw-bold">{{ $stages[$data->stage_id] ?? '-' }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>{{ $data->operation_SOP_code ?? '-' }}</td>
                            <td>{{ $data->clearing_SOP_code ?? '-' }}</td>
                            <td>
                                @if ($data->is_Portable_equipment)
                                    <span class="badge bg-warning text-dark px-2 py-1 small fw-bold">Di Động</span>
                                @else
                                    <span class="badge bg-light text-dark border px-2 py-1 small fw-bold">Cố Định</span>
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
                                        data-id="{{ $data->id }}" data-code="{{ $data->code }}"
                                        data-name="{{ $data->name }}" data-stage_id="{{ $data->stage_id }}"
                                        data-type="{{ $data->type }}"
                                        data-connection_type="{{ $data->connection_type }}"
                                        data-ip="{{ $data->ip }}" data-port="{{ $data->port }}"
                                        data-brand="{{ $data->brand }}" data-baud_rate="{{ $data->baud_rate }}"
                                        data-data_bits="{{ $data->data_bits }}" data-parity="{{ $data->parity }}"
                                        data-stop_bits="{{ $data->stop_bits }}"
                                        data-operation_sop_code="{{ $data->operation_SOP_code }}"
                                        data-clearing_sop_code="{{ $data->clearing_SOP_code }}"
                                        data-is_portable_equipment="{{ $data->is_Portable_equipment }}"
                                        data-toggle="modal" data-target="#updateModal" title="Sửa">
                                        <i class="fas fa-pen"></i>
                                    </button>

                                    <button type="button"
                                        class="btn btn-sm btn-icon btn-light-info border shadow-sm btn-view-cal-label"
                                        data-code="{{ $data->code }}" title="Xem Nhãn Hiệu chuẩn">
                                        <i class="fas fa-tag"></i>
                                    </button>

                                    <button type="button"
                                        class="btn btn-sm btn-icon btn-light-primary border shadow-sm btn-view-maint-label"
                                        data-code="{{ $data->code }}" title="Xem Nhãn Bảo trì">
                                        <i class="fas fa-wrench"></i>
                                    </button>

                                    <form class="form-delete d-inline"
                                        action="{{ route('pages.manu_env.equipment.delete') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="id" value="{{ $data->id }}">
                                        <button type="submit"
                                            class="btn btn-sm btn-icon btn-light-danger border shadow-sm btn-delete-confirm"
                                            data-code="{{ $data->code }}" data-name="{{ $data->name }}"
                                            title="Xóa">
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

<!-- Calibration Label Modal -->
<div class="modal fade" id="calLabelModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-tag mr-2"></i>NHÃN HIỆU CHUẨN THIẾT BỊ</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="text-center py-5" id="calLabelLoading" style="display: none;">
                    <div class="spinner-border text-info" role="status"></div>
                    <div class="mt-2 text-muted">Đang tải dữ liệu...</div>
                </div>
                <div id="calLabelContent" style="display: none;">
                    <div class="cal-label-container">
                        <div class="cal-label-info">
                            <table style="width: 100%; border: none;">
                                <tr>
                                    <td style="width: 30%; font-weight: bold; font-size: 15px; padding: 4px;">Tên thiết
                                        bị</td>
                                    <td style="width: 70%; font-size: 15px; padding: 4px;">: <span
                                            id="lblEquipName"></span></td>
                                </tr>
                                <tr>
                                    <td style="font-weight: bold; font-size: 15px; padding: 4px;">Mã số thiết bị</td>
                                    <td style="font-size: 15px; padding: 4px;">: <span id="lblEquipCode"></span></td>
                                </tr>
                            </table>
                        </div>

                        <table class="cal-label-table">
                            <thead>
                                <tr>
                                    <th style="width: 8%;">STT</th>
                                    <th style="width: 24%;">Mã số</th>
                                    <th style="width: 24%;">Tên</th>
                                    <th style="width: 22%;">Ngày hiệu chuẩn</th>
                                    <th style="width: 22%;">Hạn sử dụng</th>
                                </tr>
                            </thead>
                            <tbody id="calLabelTableBody">
                                <!-- Data rows -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Maintenance Label Modal -->
<div class="modal fade" id="maintLabelModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-wrench mr-2"></i>NHÃN BẢO TRÌ THIẾT BỊ</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="text-center py-5" id="maintLabelLoading" style="display: none;">
                    <div class="spinner-border text-primary" role="status"></div>
                    <div class="mt-2 text-muted">Đang tải dữ liệu...</div>
                </div>
                <div id="maintLabelContent" style="display: none;">
                    <div class="cal-label-container">
                        <div class="cal-label-info">
                            <table style="width: 100%; border: none;">
                                <tr>
                                    <td style="width: 30%; font-weight: bold; font-size: 15px; padding: 4px;">Tên thiết
                                        bị</td>
                                    <td style="width: 70%; font-size: 15px; padding: 4px;">: <span
                                            id="lblMaintEquipName"></span></td>
                                </tr>
                                <tr>
                                    <td style="font-weight: bold; font-size: 15px; padding: 4px;">Mã số thiết bị</td>
                                    <td style="font-size: 15px; padding: 4px;">: <span id="lblMaintEquipCode"></span>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <table class="cal-label-table">
                            <thead>
                                <tr>
                                    <th style="width: 8%;">STT</th>
                                    <th style="width: 18%;">Mã số</th>
                                    <th style="width: 20%;">Tên</th>
                                    <th style="width: 15%;">Chu kỳ</th>
                                    <th style="width: 19%;">Ngày bảo trì</th>
                                    <th style="width: 20%;">Hạn bảo trì</th>
                                </tr>
                            </thead>
                            <tbody id="maintLabelTableBody">
                                <!-- Data rows -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
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

            $('#data_table_instrument').on('draw.dt', function () {
                let codes = [];
                let badgeContainers = [];

                $('#data_table_instrument tbody tr').each(function () {
                    const container = $(this).find('.equip-status-badges');
                    if (container.length) {
                        const code = container.data('code');
                        if (code && container.is(':empty')) {
                            codes.push(code);
                            badgeContainers.push(container);
                        }
                    }
                });

                if (codes.length > 0) {
                    $.ajax({
                        url: "{{ route('pages.manu_env.equipment.getStatusBatch') }}",
                        type: 'POST',
                        data: {
                            _token: "{{ csrf_token() }}",
                            codes: codes
                        },
                        success: function (res) {
                            badgeContainers.forEach(container => {
                                const code = container.data('code');
                                if (res[code]) {
                                    let html = '';
                                    if (res[code].cal_expired) {
                                        html += '<span class="badge bg-danger text-white me-1" title="Quá hạn Hiệu chuẩn"><i class="fas fa-exclamation-triangle"></i> HC</span>';
                                    }
                                    if (res[code].maint_warning === 2) {
                                        html += '<span class="badge bg-danger text-white me-1" title="Quá hạn Bảo trì"><i class="fas fa-exclamation-triangle"></i> BT</span>';
                                    } else if (res[code].maint_warning === 1) {
                                        html += '<span class="badge text-white me-1" style="background-color: #fd7e14;" title="Sắp đến hạn Bảo trì"><i class="fas fa-exclamation-circle"></i> BT</span>';
                                    }
                                    container.html(html);
                                }
                            });
                        }
                    });
                }
            });

            setTimeout(() => {
                $('#data_table_instrument').trigger('draw.dt');
            }, 500);

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

                // Populate SOP and portable fields
                modal.find('#update_operation_SOP_code').val(button.data('operation_sop_code') || '');
                modal.find('#update_clearing_SOP_code').val(button.data('clearing_sop_code') || '');
                modal.find('#update_is_Portable_equipment').prop('checked', button.data(
                    'is_portable_equipment') == 1);

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

            $('#data_table_instrument tbody').on('click', '.btn-view-cal-label', function() {
                const code = $(this).data('code');
                $('#calLabelModal').modal('show');
                $('#calLabelContent').hide();
                $('#calLabelLoading').show();

                $.ajax({
                    url: "{{ route('pages.manu_env.equipment.getCalibrationLabel', ':code') }}"
                        .replace(':code', code),
                    type: "GET",
                    success: function(res) {
                        $('#calLabelLoading').hide();
                        if (res.success) {
                            $('#lblEquipName').text(res.parent.name);
                            $('#lblEquipCode').text(res.parent.code);

                            let hasExpired = false;
                            let tbody = '';
                            if (res.children.length === 0) {
                                tbody = '<tr><td colspan="5">Không có thiết bị con</td></tr>';
                            } else {
                                res.children.forEach((child, idx) => {
                                    if (child.is_expired) hasExpired = true;
                                    let rowClass = child.is_expired ?
                                        'class="table-danger text-danger font-weight-bold"' :
                                        '';
                                    tbody += `
                                        <tr ${rowClass}>
                                            <td>${idx + 1}.</td>
                                            <td>${child.id}</td>
                                            <td>${child.name}</td>
                                            <td>${child.calibrated_on}</td>
                                            <td>${child.exp_date}</td>
                                        </tr>
                                    `;
                                });
                            }

                            const headerTitle = $('#calLabelModal .modal-header');
                            if (hasExpired) {
                                headerTitle.removeClass('bg-info').css('background-color', '')
                                    .addClass('bg-danger');
                            } else {
                                headerTitle.removeClass('bg-info bg-danger').css(
                                    'background-color', '#8bc34a');
                            }

                            $('#calLabelTableBody').html(tbody);
                            $('#calLabelContent').show();
                        } else {
                            Swal.fire('Thông báo', res.message || 'Có lỗi xảy ra', 'info');
                            $('#calLabelModal').modal('hide');
                        }
                    },
                    error: function() {
                        $('#calLabelLoading').hide();
                        Swal.fire('Lỗi', 'Không thể kết nối đến máy chủ', 'error');
                        $('#calLabelModal').modal('hide');
                    }
                });
            });

            $('#data_table_instrument tbody').on('click', '.btn-view-maint-label', function() {
                const code = $(this).data('code');
                $('#maintLabelModal').modal('show');
                $('#maintLabelContent').hide();
                $('#maintLabelLoading').show();

                $.ajax({
                    url: "{{ route('pages.manu_env.equipment.getMaintenanceLabel', ':code') }}"
                        .replace(':code', code),
                    type: "GET",
                    success: function(res) {
                        $('#maintLabelLoading').hide();
                        if (res.success) {
                            $('#lblMaintEquipName').text(res.parent.name);
                            $('#lblMaintEquipCode').text(res.parent.code);

                            let tbody = '';
                            if (res.children.length === 0) {
                                tbody = '<tr><td colspan="6">Không có thiết bị con</td></tr>';
                            } else {
                                res.children.forEach((child, idx) => {
                                    let rowClass = '';
                                    if (child.warning_level === 2) {
                                        rowClass =
                                            'class="table-danger text-danger font-weight-bold"';
                                    } else if (child.warning_level === 1) {
                                        // Orange background
                                        rowClass =
                                            'style="background-color: #fff3cd; color: #856404; font-weight: bold;"';
                                    }

                                    tbody += `
                                        <tr ${rowClass}>
                                            <td>${idx + 1}.</td>
                                            <td>${child.id}</td>
                                            <td>${child.name}</td>
                                            <td>${child.cycle}</td>
                                            <td>${child.calibrated_on}</td>
                                            <td>${child.exp_date}</td>
                                        </tr>
                                    `;
                                });
                            }

                            const headerTitle = $('#maintLabelModal .modal-header');
                            headerTitle.removeClass('bg-primary bg-danger bg-warning').css(
                                'background-color', '');

                            if (res.header_warning === 2) {
                                headerTitle.addClass('bg-danger').css('color', '#fff');
                            } else if (res.header_warning === 1) {
                                headerTitle.css({
                                    'background-color': '#fd7e14',
                                    'color': '#fff'
                                }); // Orange
                            } else {
                                headerTitle.css({
                                    'background-color': '#8bc34a',
                                    'color': '#000'
                                }); // Green
                            }

                            $('#maintLabelTableBody').html(tbody);
                            $('#maintLabelContent').show();
                        } else {
                            Swal.fire('Thông báo', res.message || 'Có lỗi xảy ra', 'info');
                            $('#maintLabelModal').modal('hide');
                        }
                    },
                    error: function() {
                        $('#maintLabelLoading').hide();
                        Swal.fire('Lỗi', 'Không thể kết nối đến máy chủ', 'error');
                        $('#maintLabelModal').modal('hide');
                    }
                });
            });
        });
    </script>
@append
