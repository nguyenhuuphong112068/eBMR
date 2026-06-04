@php
    if (!function_exists('formatConditionPHP')) {
        function formatConditionPHP($op, $val1, $val2, $unit)
        {
            $hasVal1 = !is_null($val1) && $val1 !== '';
            $hasVal2 = !is_null($val2) && $val2 !== '';
            if (!$hasVal1) {
                return '-';
            }
            if ($op === '≤' || $op === '≥' || $op === '=') {
                return "{$op} {$val1}{$unit}";
            }
            if ($op === '±') {
                return "{$val1} &plusmn; {$val2}{$unit}";
            }
            if ($op === 'between') {
                return "{$val1} - {$val2}{$unit}";
            }
            return "{$val1}{$unit}";
        }
    }
@endphp
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
    </style>
@append

<div class="content-wrapper">
    <div class="card border-0 shadow-sm">
        <div class="card-header border-0 bg-transparent pt-4 pb-0 px-4">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="d-flex align-items-center">
                        <label for="filter_workshop" class="fw-bold text-muted mb-0 mr-2 text-nowrap">Phân xưởng:</label>
                        <select id="filter_workshop" class="form-control shadow-sm"
                            style="width: 220px; height: 38px; border-radius: 8px;">
                            @foreach ($workshops as $ws)
                                <option value="{{ $ws->shortName }}"
                                    {{ $selectedDept === $ws->shortName ? 'selected' : '' }}>
                                    {{ $ws->shortName }} - {{ $ws->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body">
            <table id="data_table_room" class="table table-hover w-100">
                <thead>
                    <tr>
                        <th style="width: 50px;">STT</th>
                        <th>Mã Phòng</th>
                        <th>Tên Phòng</th>
                        <th class="text-center">Tình Trạng Vệ Sinh</th>
                        <th>Công Đoạn</th>
                        <th>Tổ Sản Xuất</th>
                        <th>Thiết Bị</th>
                        <th>Điều kiện sản xuất</th>
                        <th>Biểu mẫu dọn quang</th>
                        <th>Biểu mẫu vệ sinh</th>
                        <th>Người Tạo</th>
                        <th class="text-center" style="width: 180px;">Thao Tác</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($datas as $data)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td class="fw-bold text-primary">{{ $data->code }}</td>
                            <td class="fw-medium">{{ $data->name }}</td>
                            <td class="text-center">
                                @php
                                    $rs = $data->room_status ?? 'ready';
                                    if ($rs === 'producing') {
                                        $statusText = 'Đang sản xuất';
                                        $statusClass = 'bg-primary text-white';
                                    } elseif ($rs === 'maintenance') {
                                        $statusText = 'Bảo trì';
                                        $statusClass = 'bg-danger text-white';
                                    } elseif ($rs === 'cleaning') {
                                        $statusText = 'Đang vệ sinh';
                                        $statusClass = 'bg-warning text-dark';
                                    } elseif ($rs === 'dirty' || $rs === 'line_clearance_required') {
                                        $statusText = 'Cần vệ sinh';
                                        $statusClass = 'bg-warning text-dark';
                                    } elseif ($rs === 'cleaned') {
                                        $statusText = 'Đã vệ sinh';
                                        $statusClass = 'bg-success text-white';
                                    } else {
                                        $statusText = 'Sẵn sàng';
                                        $statusClass = 'bg-success text-white';
                                    }
                                @endphp
                                <button class="badge {{ $statusClass }} border-0 shadow-sm" style="cursor: pointer; padding: 5px 10px;" onclick="showLabel('room', '{{ $data->id }}')"><i class="fas fa-tag me-1"></i> {{ $statusText }}</button>
                            </td>
                            <td>
                                <span
                                    class="badge bg-light text-dark border px-2 py-1 small fw-bold">{{ $data->stage }}</span>
                            </td>
                            <td>{{ $data->production_group }}</td>
                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    @forelse($data->equipments as $eq)
                                        <span class="badge bg-light-info text-info border px-2 py-1 small fw-bold"
                                            title="{{ $eq->name }}">
                                            <i class="fas fa-plug me-1"></i>{{ $eq->code }}
                                        </span>
                                    @empty
                                        <span class="text-muted small">-</span>
                                    @endforelse
                                </div>
                            </td>
                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    @forelse($data->conditions as $cond)
                                        @php
                                            $t1 = formatConditionPHP(
                                                $cond->temp_op_1,
                                                $cond->temp_val1_1,
                                                $cond->temp_val2_1,
                                                '°C',
                                            );
                                            $t2 = formatConditionPHP(
                                                $cond->temp_op_2,
                                                $cond->temp_val1_2,
                                                $cond->temp_val2_2,
                                                '°C',
                                            );
                                            $tempStr = '-';
                                            if ($t1 !== '-' && $t2 !== '-') {
                                                $tempStr = "{$t1} & {$t2}";
                                            } elseif ($t1 !== '-') {
                                                $tempStr = $t1;
                                            } else {
                                                $tempStr = $t2;
                                            }

                                            $h1 = formatConditionPHP(
                                                $cond->humidity_op_1,
                                                $cond->humidity_val1_1,
                                                $cond->humidity_val2_1,
                                                '%',
                                            );
                                            $h2 = formatConditionPHP(
                                                $cond->humidity_op_2,
                                                $cond->humidity_val1_2,
                                                $cond->humidity_val2_2,
                                                '%',
                                            );
                                            $humidityStr = '-';
                                            if ($h1 !== '-' && $h2 !== '-') {
                                                $humidityStr = "{$h1} & {$h2}";
                                            } elseif ($h1 !== '-') {
                                                $humidityStr = $h1;
                                            } else {
                                                $humidityStr = $h2;
                                            }

                                            $corridor = formatConditionPHP(
                                                $cond->diff_press_corridor_op,
                                                $cond->diff_press_corridor_val1,
                                                $cond->diff_press_corridor_val2,
                                                ' Pa',
                                            );
                                            $pal = formatConditionPHP(
                                                $cond->diff_press_pal_op,
                                                $cond->diff_press_pal_val1,
                                                $cond->diff_press_pal_val2,
                                                ' Pa',
                                            );
                                            $mal = formatConditionPHP(
                                                $cond->diff_press_mal_op,
                                                $cond->diff_press_mal_val1,
                                                $cond->diff_press_mal_val2,
                                                ' Pa',
                                            );
                                            $hepa = formatConditionPHP(
                                                $cond->hepa_filter_op,
                                                $cond->hepa_filter_val1,
                                                $cond->hepa_filter_val2,
                                                ' Pa',
                                            );

                                            $tooltipContent =
                                                '<b>' .
                                                e($cond->name) .
                                                '</b><br>' .
                                                'Nhiệt độ: ' .
                                                $tempStr .
                                                '<br>' .
                                                'Độ ẩm: ' .
                                                $humidityStr .
                                                '<br>' .
                                                'Chênh áp P/HL: ' .
                                                $corridor .
                                                '<br>' .
                                                'Chênh áp P/PAL: ' .
                                                $pal .
                                                '<br>' .
                                                'Chênh áp P/MAL: ' .
                                                $mal .
                                                '<br>' .
                                                'Chênh áp HEPA: ' .
                                                $hepa;
                                        @endphp
                                        <span
                                            class="badge bg-light-success text-success border px-2 py-1 small fw-bold mb-1"
                                            data-toggle="tooltip" data-html="true" title="{{ $tooltipContent }}"
                                            style="cursor: pointer;">
                                            <i class="fas fa-thermometer-half me-1"></i>{{ $cond->name }}
                                        </span>
                                    @empty
                                        <span class="text-muted small">-</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="text-center align-middle">
                                <a href="{{ route('pages.manu_env.clearance_process.list', ['type' => 'room', 'id' => $data->id]) }}"
                                    class="btn btn-sm btn-icon btn-light-success border shadow-sm"
                                    title="Thiết kế quy trình dọn quang">
                                    <i class="fas fa-broom"></i>
                                </a>
                            </td>
                            <td class="text-center align-middle">
                                <a href="{{ route('pages.manu_env.cleaning_process.list', ['type' => 'room', 'id' => $data->id]) }}"
                                    class="btn btn-sm btn-icon btn-light-warning border shadow-sm"
                                    title="Thiết kế quy trình vệ sinh">
                                    <i class="fas fa-soap"></i>
                                </a>
                            </td>
                            <td><span class="small fw-bold">{{ $data->prepareBy ?? '-' }}</span></td>
                            <td class="text-center align-middle">
                                <div class="d-flex gap-1 justify-content-center">
                                    <button type="button"
                                        class="btn btn-sm btn-icon btn-light-info border shadow-sm btn-assign"
                                        data-id="{{ $data->id }}" data-code="{{ $data->code }}"
                                        data-name="{{ $data->name }}" data-toggle="modal" data-target="#assignModal"
                                        title="Khai báo thiết bị">
                                        <i class="fas fa-desktop"></i>
                                    </button>

                                    <button type="button"
                                        class="btn btn-sm btn-icon btn-light-success border shadow-sm btn-condition"
                                        data-id="{{ $data->id }}" data-code="{{ $data->code }}"
                                        data-name="{{ $data->name }}" data-toggle="modal"
                                        data-target="#conditionModal" title="Cài đặt điều kiện sản xuất">
                                        <i class="fas fa-thermometer-half"></i>
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

@section('model')
    @include('pages.ebmr.production.modals.labelModal')
@append

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
            if ($.fn.DataTable.isDataTable('#data_table_room')) {
                $('#data_table_room').DataTable().destroy();
            }

            $('#data_table_room').DataTable({
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

            window.showLabel = function(type, id) {
                Swal.fire({
                    title: 'Đang tải...',
                    text: 'Vui lòng chờ trong giây lát',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                $.ajax({
                    url: "{{ route('pages.ebmr.getLogbookLabel') }}",
                    type: 'GET',
                    data: {
                        type: type,
                        id: id
                    },
                    success: function(response) {
                        Swal.close();
                        if (response.success) {
                            const data = response.data;
                            const labelTitle = type === 'room' ? 'NHÃN PHÒNG' : 'NHÃN THIẾT BỊ';
                            const labelSub = type === 'room' ? 'ROOM LABEL' : 'EQUIPMENT LABEL';
                            
                            if (data.current_status !== 'cleaned') {
                                $('#cleanRequiredLabelType').text(labelTitle);
                                $('#cleanRequiredLabelType').next('small').text(labelSub);
                                
                                $('#lblReqName').text(data.entity_name);
                                $('#lblReqCode').text(data.entity_code);
                                
                                $('#reqLevel1').prop('checked', data.clean_level === 'level_1');
                                $('#reqLevel2').prop('checked', data.clean_level === 'level_2');
                                $('#reqReClean').prop('checked', data.clean_level === 're_cleaning');
                                
                                $('#reqFinishedDate').text(data.end_time || '-');
                                $('#reqCleanBefore').text(data.to_be_cleaned_before || '-');
                                $('#reqDoneBy').text(data.done_by || '-');

                                $('#modalCleanRequired').modal('show');
                            } else {
                                $('#cleanedLabelType').text(labelTitle);
                                $('#cleanedLabelType').next('small').text(labelSub);

                                $('#lblCldName').text(data.entity_name);
                                $('#lblCldCode').text(data.entity_code);

                                $('#cldLevel1').prop('checked', data.clean_level === 'level_1');
                                $('#cldLevel2').prop('checked', data.clean_level === 'level_2');
                                $('#cldReClean').prop('checked', data.clean_level === 're_cleaning');

                                $('#cldFinishedDate').text(data.end_time || '-');
                                $('#cldValidUntil').text(data.clean_expiry_date || '-');
                                $('#cldDoneBy').text(data.done_by || '-');
                                $('#cldCheckedBy').text(data.checked_by || '-');
                                
                                $('#cldNextProduct').text(data.next_product_name || '-');
                                $('#cldNextBatch').text(data.next_batch_number || '-');
                                $('#cldAttachedBy').text(data.attached_by || '-');

                                $('#modalCleaned').modal('show');
                            }
                        } else {
                            Swal.fire('Lỗi!', response.message, 'error');
                        }
                    },
                    error: function() {
                        Swal.close();
                        Swal.fire('Lỗi!', 'Không thể tải dữ liệu nhãn', 'error');
                    }
                });
            };

            $('.btn-assign').click(function() {
                const button = $(this);
                $('#assign_room_id').val(button.data('id'));
                $('#assign_room_title').html(
                    '<i class="fas fa-desktop me-1"></i> Khai báo thiết bị - Phòng ' + button.data(
                        'code') + ' (' + button.data('name') + ')');
                window.loadRoomEquipments(button.data('id'));
            });

            $('.btn-condition').click(function() {
                const button = $(this);
                $('#condition_room_id').val(button.data('id'));
                $('#condition_room_title').html(
                    '<i class="fas fa-thermometer-half me-1"></i> Thiết lập điều kiện sản xuất - Phòng ' +
                    button.data('code') + ' (' + button.data('name') + ')');
                window.loadRoomConditions(button.data('id'));
            });

            $('.btn-related-form').click(function() {
                const button = $(this);
                $('#related_room_id').val(button.data('id'));
                $('#related_form_title').html(
                    '<i class="fas fa-file-alt me-1"></i> Liên kết biểu mẫu - Phòng ' + button.data(
                        'code') + ' (' + button.data('name') + ')');
                window.loadRoomRelatedForms(button.data('id'));
            });

            $('#filter_workshop').change(function() {
                const dept = $(this).val();
                window.location.href = "{{ route('pages.manu_env.room.list') }}?department=" + dept;
            });

        });
    </script>
@append
