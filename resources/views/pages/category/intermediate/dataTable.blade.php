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

        .auto-resize {
            overflow: hidden;
            resize: none;
            min-height: 31px;
            line-height: 1.5;
            padding-top: 5px;
            padding-bottom: 5px;
        }

        /* Make modal inputs slightly taller */
        .modal-body .form-control:not(textarea) {
            min-height: 42px;
            padding: 0.5rem 0.75rem;
        }
        
        /* Fix summernote dialog in modal */
        .note-modal {
            z-index: 1060 !important;
        }
        
        /* Fix to hide note-codable in case CSS fails to load */
        .note-codable {
            display: none !important;
        }

        /* Remove border-radius from Summernote */
        .note-editor, .note-editor .note-toolbar, .note-editor .note-editing-area {
            border-radius: 0 !important;
        }
    </style>
    <!-- Summernote -->
    <link rel="stylesheet" href="{{ asset('dataTable/plugins/summernote/summernote-bs4.min.css') }}">

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

                        @if ($create_i_Hypothesis_category)
                            <button
                                class="btn btn-outline-info d-flex align-items-center px-4 fw-bold shadow-sm rounded-pill"
                                data-toggle="modal" data-target="#create_hypothesis_modal">
                                <i class="fas fa-vial me-2"></i> Thêm Giả Định
                            </button>
                        @endif
                    </div>

                    {{-- Filter Phân Xưởng --}}
                    <div style="min-width: 150px;">
                        <select id="filter_department"
                            class="form-select border-primary shadow-sm rounded-pill px-4 fw-bold text-primary">
                            <option value="" class="text-secondary">-Tất cả-</option>
                            @php
                                $departments = $datas->pluck('deparment_code')->unique()->sort();
                            @endphp
                            @foreach ($departments as $dept)
                                <option value="{{ $dept }}">{{ $dept }}</option>
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
                        <th rowspan="2">STT</th>
                        <th rowspan="2">Mã BTP</th>
                        <th rowspan="2">Tên Sản Phẩm</th>
                        <th rowspan="2">Cỡ Lô</th>
                        <th rowspan="2">Dạng Bào Chế</th>

                        <!-- Gom nhóm 6 cột -->
                        <th colspan="6" class="text-center">Công Đoạn/Thời gian Biệt Trữ</th>

                        <th rowspan="2" class="text-center">Phân Xưởng</th>
                        <th rowspan="2" class="text-center">Người Tạo/ Ngày Tạo</th>
                        <th rowspan="2" class="text-center">Thao tác</th>
                    </tr>
                    <tr>
                        <th>Cân NL</th>
                        <th>Cân NL Khác</th>
                        <th>PC</th>
                        <th>THT</th>
                        <th>ĐH</th>
                        <th>BP</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($datas as $data)
                        @php
                            $data->quarantine_time_unit == 1
                                ? ($quarantine_time_unit = 'ngày')
                                : ($quarantine_time_unit = 'giờ');
                        @endphp

                        <tr class = "{{ $data->IsHypothesis ? 'highlight-row' : '' }}">
                            <td>{{ $loop->iteration }}
                                @if (session('user')['userGroup'] == 'Admin')
                                    <div class="text-muted small">ID: {{ $data->id }}</div>
                                @endif
                            </td>
                            <td class="{{ $data->active ? 'text-primary' : 'text-danger' }} fw-bold">
                                {{ $data->intermediate_code }}
                            </td>

                            <td class="fw-medium">{{ $data->product_name }}</td>
                            <td>
                                <div class="fw-bold text-nowrap">
                                    {{ $data->batch_size . ' ' . $data->unit_batch_size . '#' }} </div>
                                <div class="text-muted small"> {{ $data->batch_qty . ' ' . $data->unit_batch_qty }}
                                </div>
                            </td>
                            <td> <span class="badge bg-light text-dark border">{{ $data->dosage_name }}</span></td>

                            <td class="text-center align-middle">
                                <div class="d-flex flex-column align-items-center">
                                    @if ($data->weight_1)
                                        <i class="fas fa-check-circle text-primary fs-5"></i>
                                        <span
                                            class="small">{{ $data->quarantine_weight . ' ' . $quarantine_time_unit }}</span>
                                    @endif
                                </div>
                            </td>

                            <td class="text-center align-middle">
                                <div class="d-flex flex-column align-items-center">
                                    @if ($data->weight_2)
                                        <i class="fas fa-check-circle text-primary fs-5"></i>
                                        <span class="small">
                                            @if ($data->quarantine_total == 0)
                                                {{ $data->quarantine_weight . ' ' . $quarantine_time_unit }}
                                            @endif
                                        </span>
                                    @endif
                                </div>
                            </td>

                            <td class="text-center align-middle">
                                <div class="d-flex flex-column align-items-center">
                                    @if ($data->prepering)
                                        <i class="fas fa-check-circle text-primary fs-5"></i>
                                        <span class="small">
                                            @if ($data->quarantine_total == 0)
                                                {{ $data->quarantine_preparing . ' ' . $quarantine_time_unit }}
                                            @endif
                                        </span>
                                    @endif
                                </div>
                            </td>

                            <td class="text-center align-middle">
                                <div class="d-flex flex-column align-items-center">
                                    @if ($data->blending)
                                        <i class="fas fa-check-circle text-primary fs-5"></i>
                                        <span class="small">
                                            @if ($data->quarantine_total == 0)
                                                {{ $data->quarantine_blending . ' ' . $quarantine_time_unit }}
                                            @endif
                                        </span>
                                    @endif
                                </div>
                            </td>

                            <td class="text-center align-middle">
                                <div class="d-flex flex-column align-items-center">
                                    @if ($data->forming)
                                        <i class="fas fa-check-circle text-primary fs-5"></i>
                                        <span class="small">
                                            @if ($data->quarantine_total == 0)
                                                {{ $data->quarantine_forming . ' ' . $quarantine_time_unit }}
                                            @endif
                                        </span>
                                    @endif
                                </div>
                            </td>

                            <td class="text-center align-middle">
                                <div class="d-flex flex-column align-items-center">
                                    @if ($data->coating)
                                        <i class="fas fa-check-circle text-primary fs-5"></i>
                                        <span class="small">
                                            @if ($data->quarantine_total == 0)
                                                {{ $data->quarantine_coating . ' ' . $quarantine_time_unit }}
                                            @endif
                                        </span>
                                    @endif

                                    @if ($data->quarantine_total > 0)
                                        <span
                                            class="badge bg-info text-white">{{ 'T: ' . $data->quarantine_total . ' ' . $quarantine_time_unit }}</span>
                                    @endif
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
                                            data-prepering="{{ $data->prepering }}"
                                            data-blending="{{ $data->blending }}" data-forming="{{ $data->forming }}"
                                            data-coating="{{ $data->coating }}"
                                            data-quarantine_total="{{ $data->quarantine_total }}"
                                            data-quarantine_weight="{{ $data->quarantine_weight }}"
                                            data-quarantine_preparing="{{ $data->quarantine_preparing }}"
                                            data-quarantine_blending="{{ $data->quarantine_blending }}"
                                            data-quarantine_forming="{{ $data->quarantine_forming }}"
                                            data-quarantine_coating="{{ $data->quarantine_coating }}"
                                            data-quarantine_time_unit="{{ $data->quarantine_time_unit }}"
                                            data-API_name="{{ $data->API_name }}"
                                            data-content="{{ $data->content }}"
                                            data-description="{{ $data->description }}"
                                            data-storage_conditions="{{ $data->storage_conditions }}"
                                            data-avg_core="{{ $data->avg_core }}"
                                            data-average_unit_weight="{{ $data->average_unit_weight }}"
                                            data-toggle="modal" data-target="#update_modal" title="Sửa">
                                            <i class="fas fa-pen"></i>
                                        </button>
                                    @endif

                                    {{-- Nút Sửa Giả Định --}}
                                    @if ($create_i_Hypothesis_category)
                                        <button type="button"
                                            class="btn btn-sm btn-icon btn-light-info border shadow-sm btn-edit-hypothesis"
                                            data-id="{{ $data->id }}"
                                            data-intermediate_code="{{ $data->intermediate_code }}"
                                            data-product_name_id="{{ $data->product_name_id }}"
                                            data-batch_size="{{ $data->batch_size }}"
                                            data-unit_batch_size="{{ $data->unit_batch_size }}"
                                            data-batch_qty="{{ $data->batch_qty }}"
                                            data-unit_batch_qty="{{ $data->unit_batch_qty }}"
                                            data-dosage_id="{{ $data->dosage_id }}" data-toggle="modal"
                                            data-target="#update_hypothesis_modal"
                                            {{ $data->IsHypothesis == 0 ? $auth_update : '' }} title="Sửa Giả Định">
                                            <i class="fas fa-magic"></i>
                                        </button>
                                    @endif

                                    {{-- Nút Vô Hiệu / Kích Hoạt --}}
                                    <form class="form-deActive d-inline"
                                        action="{{ route('pages.category.intermediate.deActive') }}" method="post">
                                        @csrf
                                        <input type="hidden" name="id" value = "{{ $data->id }}">
                                        <input type="hidden" name="active" value="{{ $data->active }}">
                                        <input type="hidden" name="IsHypothesis" value="{{ $data->IsHypothesis }}">

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
                                        data-product_name="{{ $data->product_name }} - {{ $data->batch_size }} {{ $data->unit_batch_size }}"
                                        data-id =  "{{ $data->id }}"
                                        data-is_hypothesis="{{ $data->IsHypothesis }}" data-toggle="modal"
                                        data-target="#intermediateRecipeModal" title="Xem công thức">
                                        <i class="fas fa-file-invoice"></i>
                                    </button>

                                    {{-- Nút Tạo hồ sơ BMR --}}
                                    <a href="{{ route('pages.ebmr.templates') }}?prefill_btp={{ $data->intermediate_code }}"
                                        class="btn btn-sm btn-icon btn-light-warning border shadow-sm"
                                        title="Tạo hồ sơ BMR">
                                        <i class="fas fa-file-signature"></i>
                                    </a>

                                    {{-- Nút Tạo BOM (chỉ cho Giả Định) --}}
                                    @if ($data->IsHypothesis)
                                        <button type="button"
                                            class="btn btn-sm btn-icon btn-light-success border shadow-sm btn-create-bom"
                                            data-id="{{ $data->id }}"
                                            data-product_name="{{ $data->product_name }} - {{ $data->batch_size }} {{ $data->unit_batch_size }}"
                                            data-toggle="modal" data-target="#createBOMModal" title="Tạo BOM">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    @endif
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

    <!-- Summernote JS -->
    <script src="{{ asset('dataTable/plugins/summernote/summernote-bs4.min.js') }}"></script>

    <script>
        $(document).ready(function() {
            // Master Data for BOM
            const materialRoles = @json($materialRoles);
            const materialSpecs = @json($materialSpecs);

            // Initialize Summernote
            $('.summernote').summernote({
                minHeight: 100,
                placeholder: 'Nhập nội dung...',
                toolbar: [
                    ['style', ['bold', 'italic', 'underline', 'clear']],
                    ['font', ['strikethrough', 'superscript', 'subscript']],
                    ['insert', ['picture']],
                    ['view', ['fullscreen', 'codeview']]
                ],
                dialogsInBody: true
            });

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
                const val = $(this).val();
                table.column(11).search(val ? '^' + val + '$' : '', true, false).draw();
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
                modal.find('input[name="batch_qty"]').val(button.data('batch_qty'));
                modal.find('select[name="unit_batch_qty"]').val(button.data('unit_batch_qty'));
                modal.find('select[name="dosage_id"]').val(button.data('dosage_id'));

                // New fields
                modal.find('input[name="API_name"]').val(button.data('api_name'));
                modal.find('input[name="content"]').val(button.data('content'));
                $('#update_description_editor').summernote('code', button.data('description') || '');
                $('#update_storage_conditions_editor').summernote('code', button.data('storage_conditions') || '');
                modal.find('input[name="avg_core"]').val(button.data('avg_core'));
                modal.find('input[name="average_unit_weight"]').val(button.data('average_unit_weight'));

                // Trạng thái các bước
                modal.find('input[name="weight_1"]').prop('checked', button.data('weight_1') == 1);
                modal.find('input[name="prepering"]').prop('checked', button.data('prepering') == 1);
                modal.find('input[name="blending"]').prop('checked', button.data('blending') == 1);
                modal.find('input[name="forming"]').prop('checked', button.data('forming') == 1);
                modal.find('input[name="coating"]').prop('checked', button.data('coating') == 1);

                // Thời gian biệt trữ
                modal.find('input[name="quarantine_weight"]').val(button.data('quarantine_weight'));
                modal.find('input[name="quarantine_preparing"]').val(button.data('quarantine_preparing'));
                modal.find('input[name="quarantine_blending"]').val(button.data('quarantine_blending'));
                modal.find('input[name="quarantine_forming"]').val(button.data('quarantine_forming'));
                modal.find('input[name="quarantine_coating"]').val(button.data('quarantine_coating'));
                modal.find('input[name="quarantine_total"]').val(button.data('quarantine_total'));

                // Bootstrap Switch unit
                const switchInput = modal.find('input[name="quarantine_time_unit"]');
                const state = button.data('quarantine_time_unit') == 1;
                if (typeof $.fn.bootstrapSwitch === 'function') {
                    switchInput.bootstrapSwitch('state', state);
                } else {
                    switchInput.prop('checked', state);
                }
            });

            $('.btn-edit-hypothesis').click(function() {
                const button = $(this);
                const modal = $('#update_hypothesis_modal');

                modal.find('input[name="id"]').val(button.data('id'));
                modal.find('input[name="intermediate_code"]').val(button.data('intermediate_code'));
                modal.find('select[name="product_name_id"]').val(button.data('product_name_id'));
                modal.find('input[name="batch_size"]').val(button.data('batch_size'));
                modal.find('input[name="batch_qty"]').val(button.data('batch_qty'));
                modal.find('select[name="unit_batch_qty"]').val(button.data('unit_batch_qty'));
                modal.find('select[name="dosage_id"]').val(button.data('dosage_id'));
            });

            // Delete/Deactive confirmation
            $('.form-deActive').on('submit', function(e) {
                e.preventDefault();
                const form = this;
                const productName = $(form).find('button').data('name');
                const active = $(form).find('button').data('type');

                Swal.fire({
                    title: active ? 'Vô hiệu hóa danh mục?' : 'Kích hoạt lại danh mục?',
                    text: `Sản phẩm: ${productName}`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#0891b2',
                    cancelButtonColor: '#ef4444',
                    confirmButtonText: 'Xác nhận',
                    cancelButtonText: 'Hủy'
                }).then((result) => {
                    if (result.isConfirmed) form.submit();
                });
            });

            // Sync Summernote div content to hidden inputs on form submit
            $('#create_modal form').on('submit', function() {
                $('#create_description_input').val($('#create_description_editor').summernote('code'));
                $('#create_storage_conditions_input').val($('#create_storage_conditions_editor').summernote('code'));
            });

            $('#update_modal form').on('submit', function() {
                $('#update_description_input').val($('#update_description_editor').summernote('code'));
                $('#update_storage_conditions_input').val($('#update_storage_conditions_editor').summernote('code'));
            });

            // Add BOM row logic
            let bomRowIndex = 0;
            
            function addBOMRow(type, targetTableId) {
                let roleOptions = '<option value="">-Chọn-</option>';
                materialRoles.forEach(role => {
                    roleOptions += `<option value="${role.name}">${role.name}</option>`;
                });

                let specOptions = '<option value="">-Chọn-</option>';
                materialSpecs.forEach(spec => {
                    specOptions += `<option value="${spec.name}">${spec.name}</option>`;
                });

                const tr = `
                    <tr class="bom-row" data-index="${bomRowIndex}">
                        <td class="text-center align-middle stt-col" style="font-weight:bold;"></td>
                        <input type="hidden" name="bom[${bomRowIndex}][type]" value="${type}">
                        <td><textarea class="form-control form-control-sm auto-resize" name="bom[${bomRowIndex}][code]" placeholder="Mã NL" rows="1"></textarea></td>
                        <td><textarea class="form-control form-control-sm auto-resize" name="bom[${bomRowIndex}][name]" placeholder="Thành phần" rows="1"></textarea></td>
                        <td>
                            <select class="form-control form-control-sm" name="bom[${bomRowIndex}][role]">
                                ${roleOptions}
                            </select>
                        </td>
                        <td><textarea class="form-control form-control-sm auto-resize" name="bom[${bomRowIndex}][manufacturer]" placeholder="Nhà SX" rows="1"></textarea></td>
                        <td>
                            <select class="form-control form-control-sm" name="bom[${bomRowIndex}][Spec]">
                                ${specOptions}
                            </select>
                        </td>
                        <td class="align-middle">
                            <div class="d-flex align-items-center mb-1">
                                <input type="number" step="any" class="form-control form-control-sm" name="bom[${bomRowIndex}][total_amount_per_unit]" placeholder="Tổng">
                                <button type="button" class="btn btn-xs btn-outline-info ms-1 btn_add_sub_amount" title="Chia phần"><i class="fa fa-plus"></i></button>
                            </div>
                            <div class="sub-amounts-container"></div>
                        </td>
                        <td><input type="number" step="any" class="form-control form-control-sm" name="bom[${bomRowIndex}][total_amount_per_batch]" placeholder="1 lô"></td>
                        <td class="text-center align-middle">
                            <button type="button" class="btn btn-xs btn-danger btn_remove_bom_row"><i class="fa fa-trash"></i></button>
                        </td>
                    </tr>
                `;
                $(`#${targetTableId}`).append(tr);
                bomRowIndex++;
                updateBOMSTT();
            }

            // Handle adding sub-amounts
            $(document).on('click', '.btn_add_sub_amount', function() {
                const row = $(this).closest('.bom-row');
                const rowIndex = row.data('index');
                const container = row.find('.sub-amounts-container');
                const subIndex = container.find('.sub-amount-item').length;

                const subHtml = `
                    <div class="sub-amount-item d-flex align-items-center mt-1">
                        <input type="number" step="any" class="form-control form-control-sm py-0" 
                            name="bom[${rowIndex}][sub_amounts][${subIndex}][amount_per_unit]" 
                            placeholder="Lượng" style="height: 22px; font-size: 0.7rem; width: 80px;">
                        <textarea class="form-control form-control-sm py-0 ms-1 auto-resize" 
                            name="bom[${rowIndex}][sub_amounts][${subIndex}][note]" 
                            placeholder="Ghi chú" rows="1" style="min-height: 22px; font-size: 0.7rem;"></textarea>
                        <button type="button" class="btn btn-xs btn-link text-danger p-0 ms-1 btn_remove_sub_amount"><i class="fa fa-times"></i></button>
                    </div>
                `;
                container.append(subHtml);
            });

            // Auto-resize logic
            $(document).on('input', '.auto-resize', function() {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
            });

            $(document).on('click', '.btn_remove_sub_amount', function() {
                $(this).closest('.sub-amount-item').remove();
            });

            $('#btn_add_bom_row_type_0').click(function() {
                addBOMRow(0, 'bom_table_body_type_0');
            });

            $('#btn_add_bom_row_type_1').click(function() {
                addBOMRow(1, 'bom_table_body_type_1');
            });

            $(document).on('click', '.btn_remove_bom_row', function() {
                $(this).closest('tr').remove();
                updateBOMSTT();
            });

            function updateBOMSTT() {
                $('#bom_table_body_type_0 tr').each(function(index) {
                    $(this).find('.stt-col').text(index + 1);
                });
                $('#bom_table_body_type_1 tr').each(function(index) {
                    $(this).find('.stt-col').text(index + 1);
                });
            }
        });

        $(document).on('click', '.btn-recipe', function() {
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
    </script>
@append
