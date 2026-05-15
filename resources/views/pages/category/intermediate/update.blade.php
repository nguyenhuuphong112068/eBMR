@section('css')
    <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
@append

<!-- Modal -->
<div class="modal fade" id="update_modal" tabindex="-1" role="dialog" aria-labelledby="productNameModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" style="max-width: 95%;" role="document">
        @php
            if (user_has_permission(session('user')['userId'], 'create_Hypothesis_category', 'boolean')) {
                $title = 'Cập Nhật Danh Mục BTP';
                $is_Hypothesis = 1;
            } else {
                $title = 'Cập Nhật Danh Mục BTP';
                $is_Hypothesis = 0;
            }
        @endphp

        <form action="{{ route('pages.category.intermediate.update') }}" method="POST">
            @csrf

            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
                <div class="modal-header border-0 bg-light py-2 px-4">
                    <div class="d-flex align-items-center">
                        <img src="{{ asset('img/iconstella.svg') }}" style="max-height: 35px; margin-right: 15px;">
                        <h5 class="modal-title fw-bold" id="productNameModalLabel" style="color: var(--primary); font-size: 1.1rem;">
                            {{ $title }}
                        </h5>
                    </div>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Đóng">
                        <span aria-hidden="true" style="font-size: 1.5rem;">&times;</span>
                    </button>
                </div>

                <div class="modal-body p-4 pt-3">
                    <input type="hidden" name="id" value="{{ old('id') }}">
                    <input type="hidden" name="is_Hypothesis" value="{{ $is_Hypothesis }}">

                    <div class="row">
                        <!-- Left Column: Product Info -->
                        <div class="col-lg-4 border-right pr-4">
                            {{-- Row 1: Tên & Mã --}}
                            <div class="row mb-3">
                                <div class="col-md-8">
                                    <div class="form-group mb-2">
                                         <label class="fw-bold small text-uppercase text-muted mb-1">Tên Sản Phẩm</label>
                                         <select class="form-control" name="product_name_id">
                                             <option> --- Chọn Sản Phẩm --- </option>
                                             @foreach ($productNames as $productName)
                                                 <option value="{{ $productName->id }}"
                                                     {{ old('product_name_id') == $productName->id ? 'selected' : '' }}>
                                                     {{ $productName->name }}
                                                 </option>
                                             @endforeach
                                         </select>
                                         @error('product_name_id', 'updateErrors')
                                             <div class="alert alert-danger mt-1 py-0 small px-2">{{ $message }}</div>
                                         @enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group mb-2">
                                        <label class="fw-bold small text-uppercase text-muted mb-1">Mã Bán Thành Phẩm</label>
                                        <input type="text" class="form-control bg-light" name="intermediate_code"
                                            value="{{ old('intermediate_code') }}" readonly>
                                        @error('intermediate_code', 'updateErrors')
                                            <div class="alert alert-danger mt-1 py-0 small px-2">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            {{-- Row 2: Dạng bào chế & Hàm lượng --}}
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <div class="form-group mb-2">
                                        <label class="fw-bold small text-uppercase text-muted mb-1">Dạng Bào Chế</label>
                                        <select class="form-control" name="dosage_id">
                                            <option> --- Chọn --- </option>
                                            @foreach ($dosages as $dosage)
                                                <option value="{{ $dosage->id }}"
                                                    {{ old('dosage_id') == $dosage->id ? 'selected' : '' }}>
                                                    {{ $dosage->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('dosage_id', 'updateErrors')
                                            <div class="alert alert-danger mt-1 py-0 small px-2">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-5">
                                    <div class="form-group mb-2">
                                        <label class="fw-bold small text-uppercase text-muted mb-1">Hàm lượng nhãn (Hoạt Chất)</label>
                                        <input type="text" class="form-control" name="API_name" value="{{ old('API_name') }}">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group mb-2">
                                        <label class="fw-bold small text-uppercase text-muted mb-1">Hàm lượng</label>
                                        <input type="text" class="form-control" name="content" value="{{ old('content') }}">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group mb-2">
                                <label class="fw-bold small text-uppercase text-muted mb-1">Mô tả sản phẩm</label>
                                <div class="summernote" id="update_description_editor"></div>
                                <input type="hidden" name="description" id="update_description_input">
                            </div>

                            <div class="form-group mb-3">
                                <label class="fw-bold small text-uppercase text-muted mb-1">Điều kiện bảo quản</label>
                                <div class="summernote" id="update_storage_conditions_editor"></div>
                                <input type="hidden" name="storage_conditions" id="update_storage_conditions_input">
                            </div>

                            {{-- Row 4: Cỡ lô --}}
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="form-group mb-2">
                                        <div class="row no-gutters">
                                            <div class="col-8 pe-1">
                                                <label class="fw-bold small text-uppercase text-muted mb-1">Cỡ Lô (KL)</label>
                                                <input type="number" min="0" step="0.001" class="form-control" name="batch_size"
                                                    value="{{ old('batch_size') }}">
                                            </div>
                                            <div class="col-4 ps-1">
                                                <label class="fw-bold small text-uppercase text-muted mb-1">Đơn Vị</label>
                                                <select class="form-control" name="unit_batch_size">
                                                    <option value="Kg" {{ old('unit_batch_size') == 'Kg' ? 'selected' : '' }}>Kg</option>
                                                    <option value="Lít" {{ old('unit_batch_size') == 'Lít' ? 'selected' : '' }}>Lít</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group mb-2">
                                        <div class="row no-gutters">
                                            <div class="col-8 pe-1">
                                                <label class="fw-bold small text-uppercase text-muted mb-1">Cỡ Lô (ĐV Liều)</label>
                                                <input type="number" min="0" class="form-control" name="batch_qty"
                                                    value="{{ old('batch_qty') }}">
                                            </div>
                                            <div class="col-4 ps-1">
                                                <label class="fw-bold small text-uppercase text-muted mb-1">Đơn Vị</label>
                                                <select class="form-control" name="unit_batch_qty">
                                                    <option value="">-Chọn-</option>
                                                    @foreach ($units as $unit)
                                                        <option value="{{ $unit->code }}"
                                                            {{ old('unit_batch_qty') == $unit->code ? 'selected' : '' }}>
                                                            {{ $unit->code }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Section Công đoạn --}}
                            <div class="row align-items-center mb-2">
                                <div class="col-md-6">
                                    <label class="fw-bold small text-uppercase text-primary mb-0">Công Đoạn Bao Gồm</label>
                                </div>
                                <div class="col-md-6 text-right">
                                    <div class="d-inline-flex align-items-center bg-light rounded-pill px-2 py-0 border">
                                        <span class="small fw-bold me-2" style="font-size: 0.75rem;">Đơn vị:</span>
                                        <input type="checkbox" name="quarantine_time_unit" checked data-bootstrap-switch>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-light p-3 rounded" style="border: 1px dashed rgba(8, 145, 178, 0.3);">
                                <div class="row">
                                    <!-- Column 1 -->
                                    <div class="col-md-6 border-right">
                                        <!-- Cân Nguyên Liệu -->
                                        <div class="form-group mb-2">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <div class="icheck-primary d-inline">
                                                    <input type="checkbox" class="step-checkbox" id="update_checkbox1" name="weight_1">
                                                    <label for="update_checkbox1" class="small fw-bold mb-0">Cân Nguyên Liệu</label>
                                                </div>
                                                <input type="number" min="0" class="form-control form-control-sm step-input shadow-sm ms-2"
                                                    style="width: 80px;" placeholder="BT" name="quarantine_weight">
                                            </div>
                                        </div>

                                        <!-- Pha Chế -->
                                        <div class="form-group mb-2">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <div class="icheck-primary d-inline">
                                                    <input type="checkbox" class="step-checkbox" id="update_checkbox2" name="prepering">
                                                    <label for="update_checkbox2" class="small fw-bold mb-0">Pha Chế</label>
                                                </div>
                                                <input type="number" min="0" class="form-control form-control-sm step-input shadow-sm ms-2"
                                                    style="width: 80px;" placeholder="BT" name="quarantine_preparing">
                                            </div>
                                        </div>

                                        <!-- Trộn Hoàn Tất -->
                                        <div class="form-group mb-0">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <div class="icheck-primary d-inline">
                                                    <input type="checkbox" class="step-checkbox" id="update_checkbox3" name="blending">
                                                    <label for="update_checkbox3" class="small fw-bold mb-0">Trộn Hoàn Tất</label>
                                                </div>
                                                <input type="number" min="0" class="form-control form-control-sm step-input shadow-sm ms-2"
                                                    style="width: 80px;" placeholder="BT" name="quarantine_blending">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Column 2 -->
                                    <div class="col-md-6">
                                        <!-- Định Hình -->
                                        <div class="form-group mb-2">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <div class="icheck-primary d-inline">
                                                    <input type="checkbox" class="step-checkbox" id="update_checkbox4" name="forming">
                                                    <label for="update_checkbox4" class="small fw-bold mb-0">Định Hình</label>
                                                </div>
                                                <input type="number" min="0" class="form-control form-control-sm step-input shadow-sm ms-2"
                                                    style="width: 80px;" placeholder="BT" name="quarantine_forming">
                                            </div>
                                        </div>

                                        <!-- Bao Phim -->
                                        <div class="form-group mb-2">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <div class="icheck-primary d-inline">
                                                    <input type="checkbox" class="step-checkbox" id="update_checkbox5" name="coating">
                                                    <label for="update_checkbox5" class="small fw-bold mb-0">Bao Phim</label>
                                                </div>
                                                <input type="number" min="0" class="form-control form-control-sm step-input shadow-sm ms-2"
                                                    style="width: 80px;" placeholder="BT" name="quarantine_coating">
                                            </div>
                                        </div>

                                        <!-- Tổng -->
                                        <div class="form-group mb-0">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <div class="icheck-info d-inline">
                                                    <input type="checkbox" class="step-checkbox" id="update_checkbox6" name="quarantine_total_checked">
                                                    <label for="update_checkbox6" class="small fw-bold text-info mb-0">Tổng Biệt Trữ</label>
                                                </div>
                                                <input type="number" min="0" class="form-control form-control-sm step-input shadow-sm border-info ms-2"
                                                    style="width: 80px;" placeholder="Tổng" name="quarantine_total">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column: BOM (Placeholder for now, or copy from create if needed) -->
                        <div class="col-lg-8 pl-4">
                            <label class="fw-bold small text-uppercase text-primary mb-1">1. NGUYÊN LIỆU PHA CHẾ</label>
                            <div class="table-responsive bg-white border rounded mb-4">
                                <table class="table table-sm table-bordered mb-0">
                                    <thead class="bg-light text-center align-middle" style="font-size: 0.8rem;">
                                        <tr>
                                            <th style="width: 40px;">STT</th>
                                            <th>Mã nguyên liệu</th>
                                            <th>Thành phần</th>
                                            <th>Chức năng</th>
                                            <th>Nhà sản xuất</th>
                                            <th>Tiêu chuẩn</th>
                                            <th style="width: 150px;">
                                                1 viên (mg)
                                                <input type="number" step="any" class="form-control form-control-sm mt-1 text-center border-primary" 
                                                       name="avg_core" id="update_avg_core" placeholder="Nhân TB" title="Khối lượng nhân trung bình">
                                            </th>
                                            <th style="width: 80px;">Lô tiêu chuẩn</th>
                                            <th style="width: 40px;"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="update_bom_table_body_type_0">
                                        <!-- Will need AJAX to load items here -->
                                    </tbody>
                                </table>
                            </div>

                            <label class="fw-bold small text-uppercase text-primary mb-1">2. NGUYÊN LIỆU KHÁC (BAO PHIM/NANG)</label>
                            <div class="table-responsive bg-white border rounded">
                                <table class="table table-sm table-bordered mb-0">
                                    <thead class="bg-light text-center align-middle" style="font-size: 0.8rem;">
                                        <tr>
                                            <th style="width: 40px;">STT</th>
                                            <th>Mã nguyên liệu</th>
                                            <th>Thành phần</th>
                                            <th>Chức năng</th>
                                            <th>Nhà sản xuất</th>
                                            <th>Tiêu chuẩn</th>
                                            <th style="width: 150px;">
                                                1 viên (mg)
                                                <input type="number" step="any" class="form-control form-control-sm mt-1 text-center border-primary" 
                                                       name="average_unit_weight" id="update_average_unit_weight" placeholder="Viên TB" title="Khối lượng viên trung bình">
                                            </th>
                                            <th style="width: 80px;">Lô tiêu chuẩn</th>
                                            <th style="width: 40px;"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="update_bom_table_body_type_1">
                                        <!-- Will need AJAX to load items here -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0 py-2 px-4 mt-2">
                    <button type="button" class="btn btn-light btn-sm px-3" data-dismiss="modal">Hủy bỏ</button>
                    <button type="submit" class="btn btn-primary btn-sm px-4 shadow-sm fw-bold">
                        Lưu Cập Nhật
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

@section('script')
    {{-- Tự động mở modal nếu có lỗi --}}
    @if ($errors->updateErrors->any())
        <script>
            $(document).ready(function() {
                $('#update_modal').modal('show');
            });
        </script>
    @endif

    <script>
        $(document).ready(function() {
            if (typeof $.fn.bootstrapSwitch === 'function') {
                $("input[data-bootstrap-switch]").bootstrapSwitch({
                    onText: 'Ngày',
                    offText: 'Giờ',
                    onColor: 'success',
                    offColor: 'danger',
                    size: 'mini'
                });
                $("input[data-bootstrap-switch]").each(function() {
                    $(this).bootstrapSwitch('state', $(this).prop('checked'));
                });
            }

            $('#update_modal').on('shown.bs.modal', function() {
                if (typeof $.fn.bootstrapSwitch === 'function') {
                    $("input[data-bootstrap-switch]").each(function() {
                        $(this).bootstrapSwitch('state', $(this).prop('checked'));
                    });
                }
            });

            function updateInputs() {
                if ($("#update_checkbox6").is(":checked")) {
                    for (let i = 1; i <= 5; i++) {
                        const cb = $("#update_checkbox" + i);
                        const input = cb.closest(".form-group").find(".step-input");
                        input.val(0).prop("readonly", true);
                    }
                    $("#update_checkbox6").closest(".form-group").find(".step-input").prop("readonly", false);
                } else {
                    for (let i = 1; i <= 5; i++) {
                        const cb = $("#update_checkbox" + i);
                        const input = cb.closest(".form-group").find(".step-input");
                        if (cb.is(":checked")) {
                            input.prop("readonly", false);
                        } else {
                            input.val(0).prop("readonly", true);
                        }
                    }
                    $("#update_checkbox6").closest(".form-group").find(".step-input").val(0).prop("readonly", true);
                }
            }

            $(".step-checkbox, #update_checkbox6").on("change", function() {
                updateInputs();
            });

            updateInputs();
        });
    </script>
@append
