@section('css')
    <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
@append

<!-- Modal -->
<div class="modal fade" id="update_modal" tabindex="-1" role="dialog" aria-labelledby="productNameModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        @php
            if (user_has_permission(session('user')['userId'], 'create_Hypothesis_category', 'boolean')) {
                $title = 'Tạo Mới Danh Mục Bán Thành Phẩm Giả Định';
                $is_Hypothesis = 1;
            } else {
                $title = 'Tạo Mới Danh Mục Bán Thành Phẩm';
                $is_Hypothesis = 0;
            }
        @endphp

        <form action="{{ route('pages.category.intermediate.update') }}" method="POST">
            @csrf

            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
                <div class="modal-header border-0 bg-light py-3 px-4">
                    <div class="d-flex align-items-center">
                        <img src="{{ asset('img/iconstella.svg') }}" style="max-height: 40px; margin-right: 15px;">
                        <h5 class="modal-title fw-bold" id="productNameModalLabel" style="color: var(--primary);">
                            Cập Nhật Danh Mục BTP
                        </h5>
                    </div>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Đóng">
                        <span aria-hidden="true" style="font-size: 1.5rem;">&times;</span>
                    </button>
                </div>

                <div class="modal-body p-4">
                    <input type="hidden" name="id" value="{{ old('id') }}">
                    <input type="hidden" name="is_Hypothesis" value="{{ $is_Hypothesis }}">

                    {{-- NAME --}}
                    <div class="form-group mb-4">
                        <label class="fw-bold small text-uppercase text-muted mb-2">Tên Sản Phẩm</label>
                        <select class="form-control" name="product_name_id">
                            <option> --- Chọn Sản Phẩm --- </option>
                            @foreach ($productNames as $productName)
                                <option value="{{ $productName->id }}"
                                    {{ old('product_name_id') == $productName->id ? 'selected' : '' }}>
                                    {{ $productName->name }}
                                </option>
                            @endforeach
                        </select>
                        @error(' product_name_id', 'updateErrors')
                            <div class="alert alert-danger mt-1 py-1 small">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Mã TBP và Dạng Bào Chế --}}
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="fw-bold small text-uppercase text-muted mb-2">Mã Bán Thành Phẩm</label>
                                <input type="text" class="form-control bg-light" name="intermediate_code"
                                    value="{{ old('intermediate_code') }}" readonly>
                                @error('intermediate_code', 'updateErrors')
                                    <div class="alert alert-danger py-1 small">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="fw-bold small text-uppercase text-muted mb-2">Dạng Bào Chế</label>
                                <select class="form-control" name="dosage_id">
                                    <option> --- Chọn Dạng Bào Chế --- </option>
                                    @foreach ($dosages as $dosage)
                                        <option value="{{ $dosage->id }}"
                                            {{ old('dosage_id') == $dosage->id ? 'selected' : '' }}>
                                            {{ $dosage->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('dosage_id', 'updateErrors')
                                    <div class="alert alert-danger mt-1 py-1 small">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>


                    {{-- Cở lô --}}
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="form-group">
                                <div class="row">
                                    <div class="col-md-8">
                                        <label class="fw-bold small text-uppercase text-muted mb-2">Cỡ Lô (Khối Lượng)</label>
                                        <input type="number" min = "0" step="0.01" class="form-control"
                                            name="batch_size" value="{{ old('batch_size') }}">
                                        @error('batch_size', 'updateErrors')
                                            <div class="alert alert-danger py-1 small">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-4">
                                        <label class="fw-bold small text-uppercase text-muted mb-2">Đơn Vị</label>
                                        <input type="text" class="form-control bg-light" name="unit_batch_size" value="Kg"
                                            readonly>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <div class="row">
                                    <div class="col-md-8">
                                        <label class="fw-bold small text-uppercase text-muted mb-2">Cỡ Lô (ĐV Liều) </label>
                                        <input type="number" min = "0" class="form-control" name="batch_qty"
                                            value="{{ old('batch_qty') }}">
                                        @error('batch_qty', 'updateErrors')
                                            <div class="alert alert-danger py-1 small">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="fw-bold small text-uppercase text-muted mb-2">Đơn Vị</label>
                                        <select class="form-control" name="unit_batch_qty">
                                            <option> - Chọn - </option>
                                            @foreach ($units as $unit)
                                                <option value="{{ $unit->code }}"
                                                    {{ old('unit_batch_qty') == $unit->code ? 'selected' : '' }}>
                                                    {{ $unit->code }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('unit_batch_qty', 'updateErrors')
                                            <div class="alert alert-danger py-1 small">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="fw-bold small text-uppercase text-primary">Công Đoạn Bao Gồm</label>
                        </div>
                        <div class="col-md-6 text-end">
                            <div class="d-inline-flex align-items-center bg-light rounded-pill px-3 py-1">
                                <span class="small fw-bold me-2">Thời Gian Biệt Trữ</span>
                                <input type="checkbox" name="quarantine_time_unit" checked data-bootstrap-switch>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="bg-light p-3 rounded" style="border: 1px dashed rgba(8, 145, 178, 0.3);">

                                <!-- Cân Nguyên Liệu -->
                                <div class="form-group row align-items-center mb-3">
                                    <div class="col-md-6">
                                        <div class="icheck-primary">
                                            <input type="checkbox" class="step-checkbox" id="update_checkbox1"
                                                name = "weight_1">
                                            <label for="update_checkbox1" class="fw-bold">Cân Nguyên Liệu</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <input type="number" min="0" class="form-control step-input shadow-sm"
                                            placeholder="Thời gian biệt trữ" name ="quarantine_weight">
                                    </div>
                                </div>

                                <!-- Pha Chế -->
                                <div class="form-group row align-items-center mb-3">
                                    <div class="col-md-6">
                                        <div class="icheck-primary">
                                            <input type="checkbox" class="step-checkbox" id="update_checkbox2"
                                                name = "prepering">
                                            <label for="update_checkbox2" class="fw-bold">Pha Chế</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <input type="number" min="0" class="form-control step-input shadow-sm"
                                            placeholder="Thời gian biệt trữ" name ="quarantine_preparing">
                                    </div>
                                </div>

                                <!-- Trộn Hoàn Tất -->
                                <div class="form-group row align-items-center mb-3">
                                    <div class="col-md-6">
                                        <div class="icheck-primary">
                                            <input type="checkbox" class="step-checkbox" id="update_checkbox3"
                                                name = "blending">
                                            <label for="update_checkbox3" class="fw-bold">Trộn Hoàn Tất</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <input type="number" min="0" class="form-control step-input shadow-sm"
                                            placeholder="Thời gian biệt trữ" name ="quarantine_blending">
                                    </div>
                                </div>

                                <!-- Định Hình -->
                                <div class="form-group row align-items-center mb-3">
                                    <div class="col-md-6">
                                        <div class="icheck-primary">
                                            <input type="checkbox" class="step-checkbox" id="update_checkbox4"
                                                name = "forming">
                                            <label for="update_checkbox4" class="fw-bold">Định Hình</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <input type="number" min="0" class="form-control step-input shadow-sm"
                                            placeholder="Thời gian biệt trữ" name ="quarantine_forming">
                                    </div>
                                </div>

                                <!-- Bao Phim -->
                                <div class="form-group row align-items-center mb-3">
                                    <div class="col-md-6">
                                        <div class="icheck-primary">
                                            <input type="checkbox" class="step-checkbox" id="update_checkbox5"
                                                name = "coating">
                                            <label for="update_checkbox5" class="fw-bold">Bao Phim</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <input type="number" min="0" class="form-control step-input shadow-sm"
                                            placeholder="Thời gian biệt trữ" name ="quarantine_coating">
                                    </div>
                                </div>

                                <!-- Tổng -->
                                <div class="form-group row align-items-center mb-0 border-top pt-3 mt-2">
                                    <div class="col-md-6">
                                        <div class="icheck-info">
                                            <input type="checkbox" class="step-checkbox" id="update_checkbox6"
                                                name ="quarantine_total_checked">
                                            <label for="update_checkbox6" class="fw-bold text-info small">Tổng biệt trữ từ PC đến trước
                                                ĐGSC</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <input type="number" min="0" class="form-control step-input shadow-sm border-info"
                                            placeholder="Tổng cộng" name ="quarantine_total">
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                </div>

                <div class="modal-footer bg-light border-0 py-3 px-4">
                    <button type="button" class="btn btn-light" data-dismiss="modal">Hủy bỏ</button>
                    <button type="submit" class="btn btn-primary px-4 shadow-sm">
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

    {{-- Gán mã chỉ tiêu tương ứng với chọn lựa --}}
    <script>
        $(document).ready(function() {
            if (typeof $.fn.bootstrapSwitch === 'function') {
                $("input[data-bootstrap-switch]").bootstrapSwitch({
                    onText: 'Ngày',
                    offText: 'Giờ',
                    onColor: 'success',
                    offColor: 'danger'
                });
                // Khi trang load
                $("input[data-bootstrap-switch]").each(function() {
                    $(this).bootstrapSwitch('state', $(this).prop('checked'));
                });
            }

            // Nếu muốn khi modal mở mới khởi tạo
            $('#update_modal').on('shown.bs.modal', function() {
                if (typeof $.fn.bootstrapSwitch === 'function') {
                    $("input[data-bootstrap-switch]").each(function() {
                        $(this).bootstrapSwitch('state', $(this).prop('checked'));
                    });
                }
            });


            // Xử lý check
            function updateInputs() {
                if ($("#update_checkbox6").is(":checked")) {

                    // Chỉ tác động input 1-5, không đổi trạng thái checkbox
                    for (let i = 2; i <= 5; i++) {
                        const cb = $("#update_checkbox" + i);
                        const input = cb.closest(".form-group.row").find(".step-input");
                        input.val(0).prop("readonly", true);
                    }
                    $("#update_checkbox6").closest(".form-group.row").find(".step-input").prop("readonly", false);
                } else {
                    // Quay lại logic cũ

                    for (let i = 1; i <= 5; i++) {
                        const cb = $("#update_checkbox" + i);
                        const input = cb.closest(".form-group.row").find(".step-input");

                        if (cb.is(":checked")) {
                            input.prop("readonly", false);
                        } else {
                            input.val(0).prop("readonly", true);
                        }
                    }
                    $("#update_checkbox6").closest(".form-group.row").find(".step-input").val(0).prop("readonly",
                        true);
                }
            }

            // Lắng nghe thay đổi của tất cả checkbox
            $(".step-checkbox, #update_checkbox6").on("change", function() {
                updateInputs();
            });

            // Chạy khi load trang
            updateInputs();


        });
    </script>
@append
