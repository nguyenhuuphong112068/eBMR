    <style>
        .highlight-row {
            background-color: #ecfeff !important;
            /* Pale cyan for highlight */
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

        #data_table_intermediate_category tbody tr[data-href] {
            cursor: pointer;
            transition: background-color 0.15s ease-in-out;
        }
        #data_table_intermediate_category tbody tr[data-href]:hover {
            background-color: #e0f2fe !important;
        }
    </style>

    <div class="content-wrapper">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                @php
                    $auth_update = user_has_permission(
                        session('user')['userId'],
                        'category_intermediate_update',
                        'disabled',
                    );
                    $auth_deActive = user_has_permission(
                        session('user')['userId'],
                        'category_intermediate_deActive',
                        'disabled',
                    );
                    $category_intermediate_create = user_has_permission(
                        session('user')['userId'],
                        'category_intermediate_create',
                        'boolean',
                    );
                    $create_i_Hypothesis_category = user_has_permission(
                        session('user')['userId'],
                        'create_intermediate_Hypothesis_category',
                        'boolean',
                    );
                @endphp

                <div class="card-header border-0 bg-transparent pt-4 pb-0 px-4 mb-4">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                        <div class="d-flex gap-2">
                            @if ($category_intermediate_create)
                                <button
                                    class="btn btn-primary d-flex align-items-center px-4 fw-bold shadow-sm rounded-pill"
                                    data-toggle="modal" data-target="#create_modal">
                                    <i class="fas fa-plus-circle me-2"></i> Thêm Sản Phẩm
                                </button>
                            @endif


                        </div>

                        {{-- Filter Phân Xưởng --}}
                        <div style="min-width: 150px;">
                            <select id="filter_department"
                                class="form-select border-primary shadow-sm rounded-pill px-4 fw-bold text-primary">
                                @php
                                    $departments = $datas
                                        ->pluck('deparment_code')
                                        ->filter()
                                        ->unique()
                                        ->sort()
                                        ->values()
                                        ->toArray();
                                    if (!in_array('PXV1', $departments)) {
                                        $departments[] = 'PXV1';
                                    }
                                    sort($departments);
                                @endphp
                                @foreach ($departments as $dept)
                                    <option value="{{ $dept }}" {{ $dept == 'PXV1' ? 'selected' : '' }}>
                                        {{ $dept }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                {{-- <div class="d-flex flex-wrap gap-2 mb-4">
                @if ($category_intermediate_create)
                    <button class="btn btn-primary d-flex align-items-center gap-2" data-toggle="modal" data-target="#create_modal">
                        <i class="fas fa-plus-circle"></i> <span>Thêm Danh Mục</span>
                    </button>
                @endif

                @if ($create_i_Hypothesis_category)
                    <button class="btn btn-outline-primary d-flex align-items-center gap-2" data-toggle="modal" data-target="#create_hypothesis_modal">
                        <i class="fas fa-magic"></i> <span>Thêm Danh Mục Giả Định</span>
                    </button>
                @endif
            </div> --}}

                <table id="data_table_intermediate_category" class="table table-hover w-100">
                    <thead>
                        <tr>
                            <th rowspan="2" class="align-middle text-center">STT</th>
                            <th rowspan="2" class="align-middle">Mã BTP</th>
                            <th rowspan="2" class="align-middle">Tên Sản Phẩm</th>
                            <th rowspan="2" class="align-middle">Cỡ Lô</th>
                            <th rowspan="2" class="align-middle">Dạng Bào Chế</th>
                            <th colspan="6" class="text-center border-bottom">Công Đoạn Bao Gồm</th>
                            <th rowspan="2" class="align-middle text-center">Thời Gian Biệt Trữ</th>
                            <th rowspan="2" class="align-middle text-center">Phân Xưởng</th>
                            <th rowspan="2" class="align-middle text-center">Người Tạo/ Ngày Tạo</th>
                            <th rowspan="2" class="align-middle text-center">Thao tác</th>
                        </tr>
                        <tr>
                            <th class="text-center px-1" title="Cân 1">Cân 1</th>
                            <th class="text-center px-1" title="Cân 2">Cân 2</th>
                            <th class="text-center px-1" title="Pha chế">Pha chế</th>
                            <th class="text-center px-1" title="Trộn hoàn tất">Trộn</th>
                            <th class="text-center px-1" title="Định hình">Định hình</th>
                            <th class="text-center px-1" title="Bao phim">Bao phim</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($datas as $data)

                            <tr data-href="{{ route('pages.ebmr.templates') }}?type=BMR&code={{ urlencode($data->intermediate_code) }}" class="{{ $data->IsHypothesis ? 'highlight-row' : '' }}">
                                <td class="align-middle text-center">{{ $loop->iteration }}
                                    @if (session('user')['userGroup'] == 'Admin')
                                        <div class="text-muted small">ID: {{ $data->id }}</div>
                                    @endif
                                </td>
                                <td class="{{ $data->active ? 'text-primary' : 'text-danger' }} fw-bold align-middle">
                                    {{ $data->intermediate_code }}
                                </td>

                                <td class="fw-medium align-middle">{{ $data->product_name }}</td>
                                <td class="align-middle">
                                    <div class="fw-bold text-nowrap">
                                        {{ $data->batch_size . ' ' . $data->unit_batch_size . '#' }} </div>
                                    <div class="text-muted small"> {{ $data->batch_qty . ' ' . $data->unit_batch_qty }}
                                    </div>
                                </td>
                                <td class="align-middle"><span class="badge bg-light text-dark border">{{ $data->dosage_name }}</span></td>
                                <td class="text-center align-middle">
                                    @if($data->weight_1) <i class="fas fa-check text-success"></i> @else <span class="text-muted">-</span> @endif
                                </td>
                                <td class="text-center align-middle">
                                    @if($data->weight_2) <i class="fas fa-check text-success"></i> @else <span class="text-muted">-</span> @endif
                                </td>
                                <td class="text-center align-middle">
                                    @if($data->prepering) <i class="fas fa-check text-success"></i> @else <span class="text-muted">-</span> @endif
                                </td>
                                <td class="text-center align-middle">
                                    @if($data->blending) <i class="fas fa-check text-success"></i> @else <span class="text-muted">-</span> @endif
                                </td>
                                <td class="text-center align-middle">
                                    @if($data->forming) <i class="fas fa-check text-success"></i> @else <span class="text-muted">-</span> @endif
                                </td>
                                <td class="text-center align-middle">
                                    @if($data->coating) <i class="fas fa-check text-success"></i> @else <span class="text-muted">-</span> @endif
                                </td>
                                <td class="align-middle">
                                    <div class="small" style="max-width: 180px;">
                                        @php $unit = $data->quarantine_time_unit ? 'Ngày' : 'Giờ'; @endphp
                                        @if($data->weight_1 && $data->quarantine_weight) <div><span class="text-muted">Cân:</span> <span class="fw-bold">{{ $data->quarantine_weight }} {{ $unit }}</span></div> @endif
                                        @if($data->prepering && $data->quarantine_preparing) <div><span class="text-muted">Pha chế:</span> <span class="fw-bold">{{ $data->quarantine_preparing }} {{ $unit }}</span></div> @endif
                                        @if($data->blending && $data->quarantine_blending) <div><span class="text-muted">Trộn:</span> <span class="fw-bold">{{ $data->quarantine_blending }} {{ $unit }}</span></div> @endif
                                        @if($data->forming && $data->quarantine_forming) <div><span class="text-muted">Định hình:</span> <span class="fw-bold">{{ $data->quarantine_forming }} {{ $unit }}</span></div> @endif
                                        @if($data->coating && $data->quarantine_coating) <div><span class="text-muted">Bao phim:</span> <span class="fw-bold">{{ $data->quarantine_coating }} {{ $unit }}</span></div> @endif
                                        @if($data->quarantine_total) <div class="text-info fw-bold border-top mt-1 pt-1"><span class="text-muted">Tổng:</span> {{ $data->quarantine_total }} {{ $unit }}</div> @endif
                                    </div>
                                </td>
                                <td><span class="badge bg-secondary">{{ $data->deparment_code }}</span></td>
                                <td>
                                    <div class="small fw-bold"> {{ $data->prepared_by }} </div>
                                    <div class="text-muted small">
                                        @if ($data->updated_at)
                                            {{ \Carbon\Carbon::parse($data->updated_at)->format('d/m/Y') }}
                                        @else
                                            {{ $data->created_at ? \Carbon\Carbon::parse($data->created_at)->format('d/m/Y') : '' }}
                                        @endif
                                    </div>
                                </td>

                                <td class="text-center align-middle">
                                    <div class="d-flex gap-1 justify-content-center">
                                        {{-- Nút Sửa --}}
                                        @if (!$auth_update)
                                            <button type="button"
                                                class="btn btn-sm btn-icon btn-light-warning border shadow-sm btn-edit"
                                                data-id="{{ $data->id }}"
                                                data-intermediate_code="{{ $data->intermediate_code }}"
                                                data-product_name_id="{{ $data->product_name_id }}"
                                                data-batch_size="{{ $data->batch_size }}"
                                                data-unit_batch_size="{{ $data->unit_batch_size }}"
                                                data-batch_qty="{{ $data->batch_qty }}"
                                                data-unit_batch_qty="{{ $data->unit_batch_qty }}"
                                                data-dosage_id="{{ $data->dosage_id }}"
                                                data-weight_1="{{ $data->weight_1 }}"
                                                data-weight_2="{{ $data->weight_2 }}"
                                                data-prepering="{{ $data->prepering }}"
                                                data-blending="{{ $data->blending }}"
                                                data-forming="{{ $data->forming }}"
                                                data-coating="{{ $data->coating }}"
                                                data-quarantine_total="{{ $data->quarantine_total }}"
                                                data-quarantine_weight="{{ $data->quarantine_weight }}"
                                                data-quarantine_preparing="{{ $data->quarantine_preparing }}"
                                                data-quarantine_blending="{{ $data->quarantine_blending }}"
                                                data-quarantine_forming="{{ $data->quarantine_forming }}"
                                                data-quarantine_coating="{{ $data->quarantine_coating }}"
                                                data-quarantine_time_unit="{{ $data->quarantine_time_unit }}"
                                                data-toggle="modal" data-target="#update_modal" title="Sửa">
                                                <i class="fas fa-pen"></i>
                                            </button>
                                        @endif


                                        {{-- Nút Vô Hiệu / Kích Hoạt --}}
                                        <form class="form-deActive d-inline"
                                            action="{{ route('pages.category.intermediate.deActive') }}"
                                            method="post">
                                            @csrf
                                            <input type="hidden" name="id" value = "{{ $data->id }}">
                                            <input type="hidden" name="active" value="{{ $data->active }}">
                                            <input type="hidden" name="IsHypothesis"
                                                value="{{ $data->IsHypothesis }}">

                                            <button type="submit"
                                                class="btn btn-sm btn-icon {{ $data->active ? 'btn-light-danger' : 'btn-light-success' }} border shadow-sm"
                                                data-type="{{ $data->active }}"
                                                data-name="{{ $data->intermediate_code . ' - ' . $data->product_name }}"
                                                {{ $data->IsHypothesis == 0 ? $auth_update : '' }}
                                                title="{{ $data->active ? 'Vô hiệu hóa' : 'Kích hoạt' }}">
                                                <i class="fas {{ $data->active ? 'fa-lock' : 'fa-unlock' }}"></i>
                                            </button>
                                        </form>

                                        {{-- Nút Công Thức --}}
                                        <button type="button"
                                            class="btn btn-sm btn-icon btn-light-primary border shadow-sm btn-recipe"
                                            data-intermediate_code="{{ $data->intermediate_code }}"
                                            data-product_name="{{ $data->product_name }}"
                                            data-batch_size="{{ $data->batch_size }}"
                                            data-unit_batch_size="{{ $data->unit_batch_size }}"
                                            data-batch_qty="{{ $data->batch_qty }}"
                                            data-id="{{ $data->id }}"
                                            data-toggle="modal"
                                            data-target="#intermediateRecipeModal" title="Xem công thức">
                                            <i class="fas fa-file-invoice"></i>
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

                if ($.fn.DataTable.isDataTable('#data_table_intermediate_category')) {
                    $('#data_table_intermediate_category').DataTable().destroy();
                }

                const table = $('#data_table_intermediate_category').DataTable({
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
                    },
                    infoCallback: function(settings, start, end, max, total, pre) {
                        let activeCount = 0;
                        let inactiveCount = 0;
                        settings.aoData.forEach(function(row) {
                            const td = $(row.anCells[1]);
                            if (td.hasClass('text-primary')) {
                                activeCount++;
                            } else if (td.hasClass('text-danger')) {
                                inactiveCount++;
                            }
                        });
                        return pre + ` (Hiệu lực: ${activeCount} | Vô hiệu: ${inactiveCount})`;
                    }
                });

                // Filter Phân Xưởng
                $('#filter_department').on('change', function() {
                    const val = $(this).val() ? $(this).val().trim() : '';
                    let deptColIdx = 12; // default fallback
                    
                    table.columns().every(function(index) {
                        const headerElement = this.header();
                        if (headerElement) {
                            const headerText = $(headerElement).text().trim();
                            if (headerText === 'Phân Xưởng') {
                                deptColIdx = index;
                            }
                        }
                    });
                    
                    table.column(deptColIdx).search(val ? '^\\s*' + $.fn.dataTable.util.escapeRegex(val) + '\\s*$' : '', true, false).draw();
                    $('#create_deparment_code_input').val(val);
                    $('#update_deparment_code_input').val(val);
                });
                $('#filter_department').trigger('change');

                // Click row to navigate to template
                $('#data_table_intermediate_category tbody').on('click', 'tr[data-href]', function(e) {
                    if ($(e.target).closest('td').is(':last-child') || $(e.target).closest('button, a, input, form, select').length) {
                        return;
                    }
                    window.location.href = $(this).data('href');
                });

                $('.btn-create-bom').click(function() {
                    const button = $(this);
                    const modal = $('#createBOMModal');
                    const product_caterogy_id = $(this).data('id')
                    // Gán dữ liệu vào input
                    modal.find('#product_caterogy_id').val(button.data('id'));
                    modal.find('#recipe_i_title').text(button.data('product_name'));

                    const history_modal = modal.find('#data_table_create_recipe_body')
                    history_modal.empty();

                    // Gọi Ajax lấy dữ liệu history
                    $.ajax({
                        url: "{{ route('pages.category.intermediate.recipe') }}",
                        type: 'post',
                        data: {
                            IsHypothesis: 1,
                            product_caterogy_id: product_caterogy_id,
                            _token: "{{ csrf_token() }}"
                        },
                        success: function(res) {
                            if (res.length === 0) {
                                history_modal.append(
                                    `<tr><td colspan="6" class="text-center">Không có công thức</td></tr>`
                                );
                            } else {
                                res.forEach((item, index) => {
                                    let code = item.MatID ?? '';
                                    let name = item.MaterialName ?? '';
                                    let qty = item.MatQty ?? '';
                                    let uom = item.uom ?? '';

                                    history_modal.append(`
                                    <tr>
                                        <td>${index + 1}</td>
                                        <td><input type="text" class="form-control code" value="${code}"></td>
                                        <td><input type="text" class="form-control name" value="${name}"></td>
                                        <td><input type="number" step="0.001" class="form-control qty" value="${qty}"></td>
                                        <td><input type="text" class="form-control uom" value="${uom}"></td>
                                        <td><button type="button" class="btn btn-danger btn-sm btn_remove"><i class="fa fa-trash"></i></button></td>
                                    </tr>
                                `);
                                });
                            }
                        },
                        error: function() {
                            history_modal.append(
                                `<tr><td colspan="6" class="text-center text-danger">Lỗi tải dữ liệu</td></tr>`
                            );
                        }
                    });
                });

                // Edit button triggers
                $('.btn-edit').click(function() {
                    const button = $(this);
                    const modal = $('#update_modal');

                    modal.find('input[name="id"]').val(button.data('id'));
                    modal.find('input[name="intermediate_code"]').val(button.data('intermediate_code'));
                    modal.find('select[name="product_name_id"]').val(button.data('product_name_id'));
                    modal.find('input[name="batch_size"]').val(button.data('batch_size'));
                    modal.find('select[name="unit_batch_size"]').val(button.data('unit_batch_size'));
                    modal.find('input[name="batch_qty"]').val(button.data('batch_qty'));
                    modal.find('select[name="unit_batch_qty"]').val(button.data('unit_batch_qty'));
                    modal.find('select[name="dosage_id"]').val(button.data('dosage_id'));

                    modal.find('input[name="weight_1"]').prop('checked', button.data('weight_1') == 1);
                    modal.find('input[name="weight_2"]').prop('checked', button.data('weight_2') == 1);
                    modal.find('input[name="prepering"]').prop('checked', button.data('prepering') == 1);
                    modal.find('input[name="blending"]').prop('checked', button.data('blending') == 1);
                    modal.find('input[name="forming"]').prop('checked', button.data('forming') == 1);
                    modal.find('input[name="coating"]').prop('checked', button.data('coating') == 1);
                    
                    modal.find('input[name="quarantine_total"]').val(button.data('quarantine_total'));
                    modal.find('input[name="quarantine_weight"]').val(button.data('quarantine_weight'));
                    modal.find('input[name="quarantine_preparing"]').val(button.data('quarantine_preparing'));
                    modal.find('input[name="quarantine_blending"]').val(button.data('quarantine_blending'));
                    modal.find('input[name="quarantine_forming"]').val(button.data('quarantine_forming'));
                    modal.find('input[name="quarantine_coating"]').val(button.data('quarantine_coating'));

                    const isTimeUnitDay = button.data('quarantine_time_unit') == 1;
                    modal.find('#update_quarantine_time_unit').prop('checked', isTimeUnitDay);
                    modal.find('#label_update_quarantine_time_unit').text(isTimeUnitDay ? 'Tính theo: Ngày' : 'Tính theo: Giờ');
                });

                $('#create_quarantine_time_unit').change(function() {
                    $('#label_create_quarantine_time_unit').text($(this).is(':checked') ? 'Tính theo: Ngày' : 'Tính theo: Giờ');
                });

                $('#update_quarantine_time_unit').change(function() {
                    $('#label_update_quarantine_time_unit').text($(this).is(':checked') ? 'Tính theo: Ngày' : 'Tính theo: Giờ');
                });

                $('.btn-recipe').click(function() {
                    const history_modal = $('#data_table_recipe_body');
                    const intermediate_code = $(this).data('intermediate_code');
                    const product_name = $(this).data('product_name');
                    const IsHypothesis = $(this).data('is_hypothesis');
                    const product_caterogy_id = $(this).data('id');

                    $('#recipe_intermediate_code').text(`${intermediate_code} - ${product_name}`);
                    history_modal.empty();

                    $.ajax({
                        url: "{{ route('pages.category.intermediate.recipe') }}",
                        type: 'post',
                        data: {
                            IsHypothesis: IsHypothesis,
                            product_caterogy_id: product_caterogy_id,
                            intermediate_code: intermediate_code,
                            _token: "{{ csrf_token() }}"
                        },
                        success: function(res) {
                            if (res.length === 0) {
                                history_modal.append(
                                    `<tr><td colspan="5" class="text-center">Không có công thức</td></tr>`
                                );
                            } else {
                                res.forEach((item, index) => {
                                    history_modal.append(`
                                        <tr>
                                            <td>${index + 1}</td>
                                            <td>${item.MatID ?? ''}</td>
                                            <td>${item.MaterialName ?? ''}</td>
                                            <td style="text-align:center">${item.MatQty != null ? Number(item.MatQty).toLocaleString(undefined, {maximumFractionDigits: 3}) : ''}</td>
                                            <td style="text-align:center">${item.uom ?? ''}</td>
                                            <td>${Math.round(item.Revno1 ?? 0)}</td>
                                        </tr>
                                    `);
                                });
                            }
                        }
                    });
                });
            });
        </script>
    @append
