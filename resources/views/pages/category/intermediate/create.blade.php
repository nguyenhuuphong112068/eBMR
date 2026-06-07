<!-- Modal -->
<div class="modal fade" id="create_modal" tabindex="-1" role="dialog" aria-labelledby="productNameModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">

        <form action="{{ route('pages.category.intermediate.store') }}" method="POST">
            @csrf

            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
                <div class="modal-header border-0 bg-light py-2 px-4">
                    <div class="d-flex align-items-center">
                        <img src="{{ asset('img/iconstella.svg') }}" style="max-height: 35px; margin-right: 15px;">
                        <h5 class="modal-title fw-bold" id="productNameModalLabel"
                            style="color: var(--primary); font-size: 1.1rem;">
                            Tạo Mới Danh Mục Bán Thành Phẩm
                        </h5>
                    </div>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Đóng">
                        <span aria-hidden="true" style="font-size: 1.5rem;">&times;</span>
                    </button>
                </div>

                <div class="modal-body p-4 pt-3">
                    <input type="hidden" name="is_Hypothesis" value="0">
                    <input type="hidden" name="deparment_code" id="create_deparment_code_input" value="PXV1">

                    <div class="row">
                        <div class="col-12">
                            {{-- Row 1: Tên & Mã --}}
                            <div class="row mb-3">
                                <div class="col-md-8">
                                    <div class="form-group mb-2">
                                        <label class="fw-bold small text-uppercase text-muted mb-1">Tên Sản Phẩm <span
                                                class="text-danger">*</span></label>
                                        <select class="form-control" name="product_name_id">
                                            <option value=""> --- Chọn Sản Phẩm --- </option>
                                            @foreach ($productNames as $productName)
                                                <option value="{{ $productName->id }}"
                                                    {{ old('product_name_id') == $productName->id ? 'selected' : '' }}>
                                                    {{ $productName->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('product_name_id', 'createErrors')
                                            <div class="alert alert-danger mt-1 py-0 small px-2">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group mb-2">
                                        <label class="fw-bold small text-uppercase text-muted mb-1">Mã Bán Thành Phẩm
                                            <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="intermediate_code"
                                            value="{{ old('intermediate_code') }}" placeholder="Mã định danh">
                                        @error('intermediate_code', 'createErrors')
                                            <div class="alert alert-danger mt-1 py-0 small px-2">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            {{-- Row 2: Cỡ lô --}}
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="form-group mb-2">
                                        <div class="row no-gutters">
                                            <div class="col-8 pe-1">
                                                <label class="fw-bold small text-uppercase text-muted mb-1">Cỡ Lô (Khối
                                                    Lượng) <span class="text-danger">*</span></label>
                                                <input type="number" min="0" step="0.001"
                                                    class="form-control" name="batch_size"
                                                    value="{{ old('batch_size') }}">
                                            </div>
                                            <div class="col-4 ps-1">
                                                <label class="fw-bold small text-uppercase text-muted mb-1">Đơn Vị
                                                    <span class="text-danger">*</span></label>
                                                <select class="form-control" name="unit_batch_size">
                                                    <option value="Kg"
                                                        {{ old('unit_batch_size') == 'Kg' ? 'selected' : '' }}>Kg
                                                    </option>
                                                    <option value="Lít"
                                                        {{ old('unit_batch_size') == 'Lít' ? 'selected' : '' }}>Lít
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                        @error('batch_size', 'createErrors')
                                            <div class="alert alert-danger mt-1 py-0 small px-2">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group mb-2">
                                        <div class="row no-gutters">
                                            <div class="col-8 pe-1">
                                                <label class="fw-bold small text-uppercase text-muted mb-1">Cỡ Lô (ĐV
                                                    Liều) <span class="text-danger">*</span></label>
                                                <input type="number" min="0" class="form-control"
                                                    name="batch_qty" value="{{ old('batch_qty') }}">
                                            </div>
                                            <div class="col-4 ps-1">
                                                <label class="fw-bold small text-uppercase text-muted mb-1">Đơn Vị
                                                    <span class="text-danger">*</span></label>
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
                                        @error('batch_qty', 'createErrors')
                                            <div class="alert alert-danger mt-1 py-0 small px-2">{{ $message }}</div>
                                        @enderror
                                        @error('unit_batch_qty', 'createErrors')
                                            <div class="alert alert-danger mt-1 py-0 small px-2">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mb-2">
                                <div class="col-md-6">
                                    <div class="form-group mb-2">
                                        <label class="fw-bold small text-uppercase text-muted mb-1">Dạng Bào Chế <span class="text-danger">*</span></label>
                                        <select class="form-control" name="dosage_id">
                                            <option value="">-Chọn-</option>
                                            @foreach ($dosages as $dosage)
                                                <option value="{{ $dosage->id }}" {{ old('dosage_id') == $dosage->id ? 'selected' : '' }}>
                                                    {{ $dosage->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('dosage_id', 'createErrors')
                                            <div class="alert alert-danger mt-1 py-0 small px-2">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-2">
                                        <label class="fw-bold small text-uppercase text-muted mb-1">Phân Loại</label>
                                        <select class="form-control" name="classification">
                                            <option value="">-Chọn-</option>
                                            <option value="Thuốc kê đơn" {{ old('classification') == 'Thuốc kê đơn' ? 'selected' : '' }}>Thuốc kê đơn</option>
                                            <option value="Thuốc không kê đơn" {{ old('classification') == 'Thuốc không kê đơn' ? 'selected' : '' }}>Thuốc không kê đơn</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            {{-- Section Công đoạn --}}
                            <div class="row align-items-center mb-2 mt-3 pt-3 border-top">
                                <div class="col-md-6">
                                    <label class="fw-bold small text-uppercase text-primary mb-0">Công Đoạn Bao Gồm</label>
                                </div>
                                <div class="col-md-6 text-right">
                                    <div class="custom-control custom-switch d-inline-block align-middle">
                                        <input type="checkbox" class="custom-control-input" id="create_quarantine_time_unit" name="quarantine_time_unit" checked>
                                        <label class="custom-control-label small fw-bold text-muted" for="create_quarantine_time_unit" id="label_create_quarantine_time_unit">Tính theo: Ngày</label>
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
                                                    <input type="checkbox" class="step-checkbox" id="checkbox1_create"
                                                        checked name="weight_1">
                                                    <label for="checkbox1_create" class="small fw-bold mb-0">Cân Nguyên Liệu</label>
                                                </div>
                                                <input type="number" min="0"
                                                    class="form-control form-control-sm step-input shadow-sm ms-2"
                                                    style="width: 110px;" placeholder="BT" name="quarantine_weight">
                                            </div>
                                        </div>

                                        <!-- Pha Chế -->
                                        <div class="form-group mb-2">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <div class="icheck-primary d-inline">
                                                    <input type="checkbox" class="step-checkbox" id="checkbox2_create"
                                                        checked name="prepering">
                                                    <label for="checkbox2_create" class="small fw-bold mb-0">Pha Chế</label>
                                                </div>
                                                <input type="number" min="0"
                                                    class="form-control form-control-sm step-input shadow-sm ms-2"
                                                    style="width: 110px;" placeholder="BT"
                                                    name="quarantine_preparing">
                                            </div>
                                        </div>

                                        <!-- Trộn Hoàn Tất -->
                                        <div class="form-group mb-0">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <div class="icheck-primary d-inline">
                                                    <input type="checkbox" class="step-checkbox" id="checkbox3_create"
                                                        checked name="blending">
                                                    <label for="checkbox3_create" class="small fw-bold mb-0">Trộn Hoàn Tất</label>
                                                </div>
                                                <input type="number" min="0"
                                                    class="form-control form-control-sm step-input shadow-sm ms-2"
                                                    style="width: 110px;" placeholder="BT" name="quarantine_blending">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Column 2 -->
                                    <div class="col-md-6">
                                        <!-- Định Hình -->
                                        <div class="form-group mb-2">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <div class="icheck-primary d-inline">
                                                    <input type="checkbox" class="step-checkbox" id="checkbox4_create"
                                                        checked name="forming">
                                                    <label for="checkbox4_create" class="small fw-bold mb-0">Định Hình</label>
                                                </div>
                                                <input type="number" min="0"
                                                    class="form-control form-control-sm step-input shadow-sm ms-2"
                                                    style="width: 110px;" placeholder="BT" name="quarantine_forming">
                                            </div>
                                        </div>

                                        <!-- Bao Phim -->
                                        <div class="form-group mb-2">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <div class="icheck-primary d-inline">
                                                    <input type="checkbox" class="step-checkbox" id="checkbox5_create"
                                                        checked name="coating">
                                                    <label for="checkbox5_create" class="small fw-bold mb-0">Bao Phim</label>
                                                </div>
                                                <input type="number" min="0"
                                                    class="form-control form-control-sm step-input shadow-sm ms-2"
                                                    style="width: 110px;" placeholder="BT" name="quarantine_coating">
                                            </div>
                                        </div>

                                        <!-- Tổng -->
                                        <div class="form-group mb-0">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <div class="icheck-info d-inline">
                                                    <input type="checkbox" class="step-checkbox" id="checkbox6_create"
                                                        name="quarantine_total_checked">
                                                    <label for="checkbox6_create" class="small fw-bold text-info mb-0">Tổng Biệt Trữ</label>
                                                </div>
                                                <input type="number" min="0"
                                                    class="form-control form-control-sm step-input shadow-sm border-info ms-2"
                                                    style="width: 110px;" placeholder="Tổng" name="quarantine_total">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light py-2 px-4 border-0">
                    <button type="button" class="btn btn-secondary rounded-pill px-4"
                        data-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm"
                        style="background-color: var(--primary); border-color: var(--primary);">
                        <i class="fas fa-save me-1"></i> Lưu Dữ Liệu
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
