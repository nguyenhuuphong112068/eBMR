<!-- Modal -->
<div class="modal fade" id="update_hypothesis_modal" tabindex="-1" role="dialog" aria-labelledby="updateHypothesisModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">

        <form action="{{ route('pages.category.intermediate.update') }}" method="POST">
            @csrf

            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
                <div class="modal-header border-0 bg-light py-3 px-4">
                    <div class="d-flex align-items-center">
                        <img src="{{ asset('img/iconstella.svg') }}" style="max-height: 40px; margin-right: 15px;">
                        <h5 class="modal-title fw-bold" id="updateHypothesisModalLabel" style="color: var(--primary);">
                            Cập Nhật Danh Mục Giả Định BTP
                        </h5>
                    </div>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Đóng">
                        <span aria-hidden="true" style="font-size: 1.5rem;">&times;</span>
                    </button>
                </div>

                <div class="modal-body p-4">
                    <input type="hidden" name="id" value="{{ old('id') }}">
                    <input type="hidden" name="is_Hypothesis" value="1">

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
                        @error('product_name_id', 'updateErrors')
                            <div class="alert alert-danger mt-1 py-2 small">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Mã TBP và Dạng Bào Chế --}}
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="fw-bold small text-uppercase text-muted mb-2">Mã Bán Thành Phẩm</label>
                                <input type="text" class="form-control" name="intermediate_code"
                                    value="{{ old('intermediate_code') }}" readonly>
                                @error('intermediate_code', 'updateErrors')
                                    <div class="alert alert-danger py-2 small">{{ $message }}</div>
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
                                    <div class="alert alert-danger py-2 small">{{ $message }}</div>
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
                                        <input type="number" min="0" step="0.001" class="form-control"
                                            name="batch_size" value="{{ old('batch_size') }}">
                                        @error('batch_size', 'updateErrors')
                                            <div class="alert alert-danger py-2 small">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-4">
                                        <label class="fw-bold small text-uppercase text-muted mb-2">Đơn Vị</label>
                                        <input type="text" class="form-control bg-light" name="unit_batch_size" value="Kg" readonly>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <div class="row">
                                    <div class="col-md-8">
                                        <label class="fw-bold small text-uppercase text-muted mb-2">Cỡ Lô (ĐV Liều)</label>
                                        <input type="number" min="0" class="form-control" name="batch_qty"
                                            value="{{ old('batch_qty') }}">
                                        @error('batch_qty', 'updateErrors')
                                            <div class="alert alert-danger py-2 small">{{ $message }}</div>
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
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0 py-3 px-4">
                    <button type="button" class="btn btn-light fw-bold" data-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary px-4 fw-bold shadow-sm">
                        Cập Nhật Giả Định
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

@section('script')
    @if ($errors->updateErrors->any() && old('is_Hypothesis') == 1)
        <script>
            $(document).ready(function() {
                $('#update_hypothesis_modal').modal('show');
            });
        </script>
    @endif
@append
