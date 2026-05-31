<div class="content-wrapper">
    <div class="container-fluid py-4">
        <div class="card border-0 shadow-sm"
            style="border-radius: 24px; background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px);">
            @php
                $auth_update = user_has_permission(session('user')['userId'], 'category_product_update', 'disabled');
                $auth_deActive = user_has_permission(
                    session('user')['userId'],
                    'category_product_deActive',
                    'disabled',
                );
                $create_i_Hypothesis_category = user_has_permission(
                    session('user')['userId'],
                    'create_intermediate_Hypothesis_category',
                    'boolean',
                );
            @endphp

            <div class="card-header border-0 bg-transparent pt-4 pb-0 px-4">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">


                    <div class="d-flex gap-2">
                        @if (user_has_permission(session('user')['userId'], 'category_product_create', 'boolean'))
                            <button
                                class="btn btn-primary d-flex align-items-center px-4 fw-bold shadow-sm rounded-pill"
                                data-toggle="modal" data-target="#intermediate_category"
                                data-modal_type="#create_modal">
                                <i class="fas fa-plus-circle me-2"></i> Thêm Sản Phẩm
                            </button>
                        @endif

                        @if ($create_i_Hypothesis_category)
                            <button
                                class="btn btn-outline-info d-flex align-items-center px-4 fw-bold shadow-sm rounded-pill"
                                data-toggle="modal" data-target="#intermediate_category"
                                data-modal_type="#create_hypothesis_modal">
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

            <div class="card-body p-4">
                <div class="table-responsive rounded-3 overflow-hidden">
                    <table id="data_table_product_category" class="table table-hover border-0 align-middle w-100">
                        <thead>
                            <tr>
                                <th class="text-center py-3">STT</th>
                                <th>Mã Sản Phẩm / BTP</th>
                                <th>Tên Sản Phẩm / BTP</th>
                                <th>Cỡ Lô</th>
                                <th>Thị Trường</th>
                                <th>Qui Cách</th>
                                <th class="text-center">Đóng Gói</th>
                                <th>Phân Xưởng</th>
                                <th>Người Tạo</th>
                                <th class="text-center">Thao Tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($datas as $data)
                                <tr data-href="{{ route('pages.ebmr.templates') }}?type=BPR&code={{ urlencode($data->finished_product_code) }}" class="{{ $data->IsHypothesis ? 'table-warning' : '' }}">
                                    <td class="text-center fw-bold text-muted small">
                                        {{ $loop->iteration }}
                                        @if (session('user')['userGroup'] == 'Admin')
                                            <div class="badge bg-light text-dark fw-normal" style="font-size: 10px;">ID:
                                                {{ $data->id }}</div>
                                        @endif
                                    </td>

                                    <td>
                                        <div
                                            class="fw-bold {{ $data->active ? 'text-primary' : 'text-danger text-decoration-line-through' }} mb-1">
                                            {{ $data->finished_product_code }}
                                        </div>
                                        <div class="text-muted small fw-bold">{{ $data->intermediate_code }}</div>
                                    </td>

                                    <td>
                                        <div class="fw-bold text-dark mb-1">{{ $data->finished_product_name }}</div>
                                        <div class="text-muted small fst-italic">{{ $data->intermediate_product_name }}
                                        </div>
                                    </td>

                                    <td>
                                        <div class="text-dark"><span class="fw-bold">{{ $data->batch_size }}</span>
                                            <span class="small">{{ $data->unit_batch_size }}</span>
                                        </div>
                                        <div class="text-muted small"><span
                                                class="fw-bold">{{ $data->batch_qty }}</span>
                                            <span>{{ $data->unit_batch_qty }}</span>
                                        </div>
                                    </td>

                                    <td><span
                                            class="badge bg-light text-dark fw-bold px-3 py-2 border">{{ $data->market }}</span>
                                    </td>
                                    <td class="small">{{ $data->specification }}</td>

                                    <td class="text-center">
                                        @if ($data->primary_parkaging)
                                            <span class="badge bg-info-soft text-info p-2 rounded-circle"
                                                style="background: rgba(34, 211, 238, 0.1);">
                                                <i class="fas fa-box-open"></i>
                                            </span>
                                        @endif
                                    </td>

                                    <td><span class="badge bg-primary-soft text-primary px-3 py-2"
                                            style="background: rgba(8, 145, 178, 0.1);">{{ $data->deparment_code }}</span>
                                    </td>

                                    <td>
                                        <div class="fw-bold small text-dark">{{ $data->prepared_by }}</div>
                                        <div class="text-muted small">
                                            {{ $data->created_at ? \Carbon\Carbon::parse($data->created_at)->format('d/m/Y') : '-' }}
                                        </div>
                                    </td>

                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-1">
                                            {{-- Nút Update --}}
                                            @if (!$auth_update)
                                                <button type="button"
                                                    class="btn btn-sm btn-icon btn-light-warning btn-edit border shadow-sm"
                                                    data-id="{{ $data->id }}"
                                                    data-finished_product_code="{{ $data->finished_product_code }}"
                                                    data-intermediate_code="{{ $data->intermediate_code }}"
                                                    data-product_name_id="{{ $data->product_name_id }}"
                                                    data-market_id="{{ $data->market_id }}"
                                                    data-specification_id="{{ $data->specification_id }}"
                                                    data-batch_size="{{ $data->batch_size }}"
                                                    data-batch_qty="{{ $data->batch_qty }}"
                                                    data-unit_batch_qty="{{ $data->unit_batch_qty }}"
                                                    data-primary_parkaging="{{ $data->primary_parkaging }}"
                                                    title="Chỉnh sửa">
                                                    <i class="fas fa-pen"></i>
                                                </button>
                                            @endif

                                            {{-- Nút Update Giả Định --}}
                                            @if ($create_i_Hypothesis_category)
                                                <button type="button"
                                                    class="btn btn-sm btn-icon btn-light-info btn-edit-hypothesis border shadow-sm"
                                                    data-id="{{ $data->id }}"
                                                    data-finished_product_code="{{ $data->finished_product_code }}"
                                                    data-intermediate_code="{{ $data->intermediate_code }}"
                                                    data-product_name_id="{{ $data->product_name_id }}"
                                                    data-market_id="{{ $data->market_id }}"
                                                    data-specification_id="{{ $data->specification_id }}"
                                                    data-batch_size="{{ $data->batch_size }}"
                                                    data-batch_qty="{{ $data->batch_qty }}"
                                                    data-unit_batch_qty="{{ $data->unit_batch_qty }}"
                                                    data-primary_parkaging="{{ $data->primary_parkaging }}"
                                                    data-toggle="modal" data-target="#update_hypothesis_modal"
                                                    {{ $data->IsHypothesis == 0 ? $auth_update : '' }}
                                                    title="Sửa giả định">
                                                    <i class="fas fa-vial"></i>
                                                </button>
                                            @endif

                                            {{-- Nút Công thức --}}
                                            <button type="button"
                                                class="btn btn-sm btn-icon btn-light-primary btn-recipe border shadow-sm"
                                                data-finished_product_code="{{ $data->finished_product_code }}"
                                                data-product_name="{{ $data->finished_product_name }} - {{ $data->batch_qty }} {{ $data->unit_batch_qty }}"
                                                data-id="{{ $data->id }}"
                                                data-is_hypothesis="{{ $data->IsHypothesis }}" data-toggle="modal"
                                                data-target="#intermediateRecipeModal" title="Xem công thức">
                                                <i class="fas fa-file-invoice"></i>
                                            </button>

                                            {{-- Nút Tạo BOM --}}
                                            @if ($data->IsHypothesis)
                                                <button type="button"
                                                    class="btn btn-sm btn-icon btn-light-success btn-create-bom border shadow-sm"
                                                    data-id="{{ $data->id }}"
                                                    data-product_name="{{ $data->finished_product_name }} - {{ $data->batch_qty }} {{ $data->unit_batch_qty }}"
                                                    data-toggle="modal" data-target="#createBOMModal" title="Tạo BOM">
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                            @endif

                                            {{-- Nút Lock/Unlock --}}
                                            <form class="form-deActive d-inline"
                                                action="{{ route('pages.category.product.deActive') }}"
                                                method="post">
                                                @csrf
                                                <input type="hidden" name="id" value="{{ $data->id }}">
                                                <input type="hidden" name="active" value="{{ $data->active }}">
                                                <input type="hidden" name="IsHypothesis"
                                                    value="{{ $data->IsHypothesis }}">
                                                <button type="submit"
                                                    class="btn btn-sm btn-icon {{ $data->active ? 'btn-light-danger' : 'btn-light-success' }} border shadow-sm"
                                                    data-type="{{ $data->active }}"
                                                    data-name="{{ $data->finished_product_code }} - {{ $data->finished_product_name }}"
                                                    {{ $data->IsHypothesis == 0 ? $auth_update : '' }}
                                                    title="{{ $data->active ? 'Vô hiệu hóa' : 'Kích hoạt' }}">
                                                    <i class="fas {{ $data->active ? 'fa-lock' : 'fa-unlock' }}"></i>
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
    </div>
</div>

@section('css')
    <style>
        #data_table_product_category tbody tr[data-href] {
            cursor: pointer;
            transition: background-color 0.15s ease-in-out;
        }
        #data_table_product_category tbody tr[data-href]:hover {
            background-color: #e0f2fe !important;
        }

        .bg-info-soft {
            background: rgba(34, 211, 238, 0.1);
        }

        .bg-primary-soft {
            background: rgba(8, 145, 178, 0.1);
        }

        .btn-light-warning {
            background: #fffcf0;
            color: #b45309;
        }

        .btn-light-warning:hover {
            background: #fef3c7;
            color: #92400e;
        }

        .btn-light-info {
            background: #ecfeff;
            color: #0891b2;
        }

        .btn-light-info:hover {
            background: #cffafe;
            color: #0e7490;
        }

        .btn-light-primary {
            background: #f0f9ff;
            color: #0369a1;
        }

        .btn-light-primary:hover {
            background: #e0f2fe;
            color: #075985;
        }

        .btn-light-success {
            background: #f0fdf4;
            color: #15803d;
        }

        .btn-light-success:hover {
            background: #dcfce7;
            color: #166534;
        }

        .btn-light-danger {
            background: #fff1f2;
            color: #be123c;
        }

        .btn-light-danger:hover {
            background: #ffe4e6;
            color: #9f1239;
        }

        .btn-icon {
            width: 32px;
            height: 32px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            transition: all 0.2s;
        }

        .btn-icon:hover {
            transform: translateY(-2px);
        }

        #data_table_product_category_wrapper .dataTables_scroll {
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid #f1f5f9;
            margin-bottom: 20px;
        }

        #data_table_product_category thead th {
            background: #f8fafc;
            color: #475569;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.5px;
            font-weight: 700;
            border-bottom: 2px solid #e2e8f0;
            padding: 15px 12px;
        }

        .dataTables_filter input {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            padding: 8px 20px;
            font-size: 13px;
            min-width: 250px;
            transition: all 0.3s;
        }

        .dataTables_filter input:focus {
            background: #fff;
            border-color: #22d3ee;
            box-shadow: 0 0 0 4px rgba(34, 211, 238, 0.1);
            outline: none;
        }

        .page-item.active .page-link {
            background-color: #0891b2;
            border-color: #0891b2;
            box-shadow: 0 4px 12px rgba(8, 145, 178, 0.2);
        }
    </style>
@append



@section('script')
    <script>
        $(document).ready(function() {
            // Prevent multiple initializations
            if ($.fn.DataTable.isDataTable('#data_table_product_category')) {
                $('#data_table_product_category').DataTable().destroy();
            }

            // Custom DataTable Search Placeholder
            $.extend(true, $.fn.dataTable.defaults, {
                language: {
                    search: "",
                    searchPlaceholder: "Tìm kiếm sản phẩm..."
                }
            });

            const table = $('#data_table_product_category').DataTable({
                paging: true,
                lengthChange: true,
                searching: true,
                ordering: true,
                info: true,
                autoWidth: false,
                pageLength: 15,
                scrollX: true,
                scrollY: '55vh',
                scrollCollapse: true,
                lengthMenu: [
                    [15, 25, 50, 100, -1],
                    [15, 25, 50, 100, "Tất cả"]
                ],
                dom: '<"d-flex justify-content-between align-items-center mb-3"lf>rt<"d-flex justify-content-between align-items-center mt-3"ip>',
                language: {
                    search: "Tìm nhanh:",
                    lengthMenu: "Hiện _MENU_ dòng",
                    info: "Dòng _START_ - _END_ của _TOTAL_",
                    infoEmpty: "Không có dữ liệu",
                    infoFiltered: "(lọc từ _MAX_ dòng)",
                    paginate: {
                        first: '<i class="fas fa-angle-double-left"></i>',
                        last: '<i class="fas fa-angle-double-right"></i>',
                        next: '<i class="fas fa-angle-right"></i>',
                        previous: '<i class="fas fa-angle-left"></i>'
                    }
                }
            });

            // Filter Phân Xưởng
            $('#filter_department').on('change', function() {
                const val = $(this).val() ? $(this).val().trim() : '';
                let deptColIdx = 7; // default fallback
                
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
            });

            // Click row to navigate to template
            $('#data_table_product_category tbody').on('click', 'tr[data-href]', function(e) {
                if ($(e.target).closest('td').is(':last-child') || $(e.target).closest('button, a, input, form, select').length) {
                    return;
                }
                window.location.href = $(this).data('href');
            });

            // Edit button triggers
            $('.btn-edit').click(function() {
                const button = $(this);
                const modal = $('#update_modal');
                modal.find('input[name="id"]').val(button.data('id'));
                modal.find('input[name="finished_product_code"]').val(button.data('finished_product_code'));
                modal.find('input[name="intermediate_code"]').val(button.data('intermediate_code'));
                modal.find('select[name="product_name_id"]').val(button.data('product_name_id'));
                modal.find('select[name="market_id"]').val(button.data('market_id'));
                modal.find('select[name="specification_id"]').val(button.data('specification_id'));
                modal.find('input[name="batch_size"]').val(button.data('batch_size'));
                modal.find('input[name="batch_qty"]').val(button.data('batch_qty'));
                modal.find('input[name="unit_batch_qty"]').val(button.data('unit_batch_qty'));
                modal.find('input[name="primary_parkaging"]').prop('checked', button.data(
                    'primary_parkaging'));
            });

            $('.btn-edit-hypothesis').click(function() {
                const button = $(this);
                const modal = $('#update_hypothesis_modal');
                modal.find('input[name="id"]').val(button.data('id'));
                modal.find('input[name="finished_product_code"]').val(button.data('finished_product_code'));
                modal.find('input[name="intermediate_code"]').val(button.data('intermediate_code'));
                modal.find('select[name="product_name_id"]').val(button.data('product_name_id'));
                modal.find('select[name="market_id"]').val(button.data('market_id'));
                modal.find('select[name="specification_id"]').val(button.data('specification_id'));
                modal.find('input[name="batch_size"]').val(button.data('batch_size'));
                modal.find('input[name="batch_qty"]').val(button.data('batch_qty'));
                modal.find('input[name="unit_batch_qty"]').val(button.data('unit_batch_qty'));
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

            // AJAX for Recipe and BOM
            $('.btn-create-bom').click(function() {
                const button = $(this);
                const modal = $('#createBOMModal');
                const product_caterogy_id = button.data('id');
                modal.find('#product_caterogy_id').val(product_caterogy_id);
                modal.find('#recipe_i_title').val(button.data('product_name'));

                const history_modal = modal.find('#data_table_create_recipe_body');
                history_modal.empty().append(
                    '<tr><td colspan="6" class="text-center py-4"><div class="spinner-border text-primary spinner-border-sm"></div> Đang tải...</td></tr>'
                );

                $.ajax({
                    url: "{{ route('pages.category.intermediate.recipe') }}",
                    type: 'post',
                    data: {
                        IsHypothesis: 1,
                        product_caterogy_id: product_caterogy_id,
                        _token: "{{ csrf_token() }}"
                    },
                    success: function(res) {
                        history_modal.empty();
                        if (res.length === 0) {
                            history_modal.append(
                                '<tr><td colspan="6" class="text-center">Không có công thức</td></tr>'
                            );
                        } else {
                            res.forEach((item, index) => {
                                history_modal.append(`
                                <tr>
                                    <td class="text-center">${index + 1}</td>
                                    <td><input type="text" class="form-control form-control-sm" value="${item.MatID ?? ''}"></td>
                                    <td><input type="text" class="form-control form-control-sm" value="${item.MaterialName ?? ''}"></td>
                                    <td><input type="number" step="0.001" class="form-control form-control-sm" value="${item.MatQty ?? ''}"></td>
                                    <td><input type="text" class="form-control form-control-sm" value="${item.uom ?? ''}"></td>
                                    <td class="text-center"><button class="btn btn-sm btn-light-danger btn_remove btn-icon"><i class="fa fa-trash"></i></button></td>
                                </tr>
                            `);
                            });
                        }
                    }
                });
            });

            $('.btn-recipe').click(function() {
                const button = $(this);
                const history_modal = $('#data_table_recipe_body');
                const intermediate_code = button.data('finished_product_code');
                const product_name = button.data('product_name');
                const product_caterogy_id = button.data('id');

                $('#recipe_intermediate_code').html(
                    `<span class="badge bg-primary px-3 py-2 me-2">${intermediate_code}</span> ${product_name}`
                );
                history_modal.empty().append(
                    '<tr><td colspan="6" class="text-center py-4"><div class="spinner-border text-primary spinner-border-sm"></div> Đang lấy công thức...</td></tr>'
                );

                $.ajax({
                    url: "{{ route('pages.category.intermediate.recipe') }}",
                    type: 'post',
                    data: {
                        IsHypothesis: button.data('is_hypothesis'),
                        product_caterogy_id: product_caterogy_id,
                        intermediate_code: intermediate_code,
                        _token: "{{ csrf_token() }}"
                    },
                    success: function(res) {
                        history_modal.empty();
                        if (res.length === 0) {
                            history_modal.append(
                                '<tr><td colspan="6" class="text-center">Chưa có dữ liệu công thức</td></tr>'
                            );
                        } else {
                            res.forEach((item, index) => {
                                history_modal.append(`
                                <tr>
                                    <td class="text-center small fw-bold">${index + 1}</td>
                                    <td class="fw-bold">${item.MatID ?? ''}</td>
                                    <td>${item.MaterialName ?? ''}</td>
                                    <td class="text-center fw-bold text-primary">${Number(item.MatQty || 0).toLocaleString(undefined, {maximumFractionDigits: 3})}</td>
                                    <td class="text-center"><span class="badge bg-light text-dark">${item.uom ?? ''}</span></td>
                                    <td class="text-center small">${Math.round(item.Revno1 ?? 0)}</td>
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
