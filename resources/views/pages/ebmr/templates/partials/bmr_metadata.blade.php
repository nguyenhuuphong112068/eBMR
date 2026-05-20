<div class="bmr-specific-fields mt-3 pt-3 border-top" id="bmr_specific_fields"
    style="display: {{ request('type') == 'BMR' ? 'block' : 'none' }};">
    <div class="form-group mb-2">
        <label class="fw-bold small text-uppercase text-muted mb-1">Mô tả sản phẩm</label>
        <div class="summernote" id="create_description_editor">{!! old('description') !!}</div>
        <input type="hidden" name="description" id="create_description_input" value="{{ old('description') }}">
    </div>

    <div class="form-group mb-3">
        <label class="fw-bold small text-uppercase text-muted mb-1">Điều kiện bảo quản</label>
        <div class="summernote" id="create_storage_conditions_editor">{!! old('storage_conditions') !!}</div>
        <input type="hidden" name="storage_conditions" id="create_storage_conditions_input"
            value="{{ old('storage_conditions') }}">
    </div>
    <div class="form-group mb-3 border-top pt-3">
        <div class="custom-control custom-switch custom-switch-lg">
            <input type="checkbox" class="custom-control-input" id="enable_recalculation" name="is_recalculation" value="1">
            <label class="custom-control-label fw-bold text-navy" for="enable_recalculation" style="cursor: pointer;">
                <i class="fas fa-calculator text-info me-1"></i> Thiết lập tính toán lại công thức
            </label>
        </div>
    </div>
</div>
