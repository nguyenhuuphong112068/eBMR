<div class="modal fade" id="createModal" tabindex="-1" role="dialog" aria-labelledby="createModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createModalLabel">Thiết kế Con Dấu mới</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('pages.materData.seal.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="create_name">Tên Con Dấu <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="create_name" name="name"
                                    value="{{ old('name') }}" placeholder="VD: Dấu Bản Sao" required>
                                @if ($errors->createErrors->has('name'))
                                    <span class="text-danger">{{ $errors->createErrors->first('name') }}</span>
                                @endif
                            </div>
                            <div class="form-group">
                                <label for="create_header">Dòng Tiêu Đề Trên <span class="text-muted small">(tuỳ
                                        chọn)</span></label>
                                <input type="text" class="form-control" id="create_header" name="header"
                                    value="{{ old('header') }}" placeholder="VD: CÔNG TY DP TW 25"
                                    oninput="updateSealPreview('create')">
                            </div>
                            <div class="form-group">
                                <label for="create_content">Nội Dung Chính <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="create_content" name="content"
                                    value="{{ old('content') }}" placeholder="VD: BẢN SAO - COPY" required
                                    oninput="updateSealPreview('create')">
                                @if ($errors->createErrors->has('content'))
                                    <span class="text-danger">{{ $errors->createErrors->first('content') }}</span>
                                @endif
                            </div>
                            <div class="form-group">
                                <label for="create_footer">Dòng Phụ Dưới <span class="text-muted small">(tuỳ
                                        chọn)</span></label>
                                <input type="text" class="form-control" id="create_footer" name="footer"
                                    value="{{ old('footer') }}" placeholder="VD: Ngày……tháng……năm 20……"
                                    oninput="updateSealPreview('create')">
                            </div>
                            <div class="row">
                                <div class="col-6">
                                    <div class="form-group">
                                        <label for="create_border">Kiểu Viền</label>
                                        <select class="form-control" id="create_border" name="border_style"
                                            onchange="updateSealPreview('create')">
                                            <option value="double" {{ old('border_style', 'double') === 'double' ? 'selected' : '' }}>Viền đôi</option>
                                            <option value="single" {{ old('border_style') === 'single' ? 'selected' : '' }}>Viền đơn</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-group">
                                        <label for="create_color">Màu Dấu <span class="text-danger">*</span></label>
                                        <input type="color" class="form-control" id="create_color" name="color"
                                            value="{{ old('color', '#dc3545') }}" style="height: 38px; padding: 4px;"
                                            required oninput="updateSealPreview('create')">
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="create_size">Kích Thước Dấu
                                    <span id="create_size_label" class="badge badge-info ml-1">{{ old('size', 100) }}%</span>
                                </label>
                                <input type="range" class="form-control-range w-100" id="create_size" name="size"
                                    min="50" max="200" step="5" value="{{ old('size', 100) }}"
                                    oninput="updateSealPreview('create')">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label>Xem Trước</label>
                            <div class="d-flex align-items-center justify-content-center bg-light border rounded"
                                style="min-height: 220px;">
                                <span id="create_seal_preview" class="seal-stamp-preview seal-border-double"
                                    style="color: #dc3545;">
                                    <span class="seal-line-content">NỘI DUNG CHÍNH</span>
                                </span>
                            </div>
                            <p class="text-muted small mt-2 mb-0"><i class="fas fa-info-circle me-1"></i>
                                Dòng tiêu đề / dòng phụ để trống sẽ không hiện trên dấu.</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">Lưu</button>
                </div>
            </form>
        </div>
    </div>
</div>

@if ($errors->createErrors->any())
    <script>
        $(document).ready(function() {
            $('#createModal').modal('show');
            updateSealPreview('create');
        });
    </script>
@endif
