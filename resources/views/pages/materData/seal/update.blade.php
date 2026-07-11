<div class="modal fade" id="updateModal" tabindex="-1" role="dialog" aria-labelledby="updateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="updateModalLabel">Cập nhật Con Dấu</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('pages.materData.seal.update') }}" method="POST">
                @csrf
                <input type="hidden" id="update_id" name="id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="update_name">Tên Con Dấu <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="update_name" name="name" required>
                                @if ($errors->updateErrors->has('name'))
                                    <span class="text-danger">{{ $errors->updateErrors->first('name') }}</span>
                                @endif
                            </div>
                            <div class="form-group">
                                <label for="update_header">Dòng Tiêu Đề Trên <span class="text-muted small">(tuỳ
                                        chọn)</span></label>
                                <input type="text" class="form-control" id="update_header" name="header"
                                    placeholder="VD: CÔNG TY DP TW 25" oninput="updateSealPreview('update')">
                            </div>
                            <div class="form-group">
                                <label for="update_content">Nội Dung Chính <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="update_content" name="content" required
                                    oninput="updateSealPreview('update')">
                                @if ($errors->updateErrors->has('content'))
                                    <span class="text-danger">{{ $errors->updateErrors->first('content') }}</span>
                                @endif
                            </div>
                            <div class="form-group">
                                <label for="update_footer">Dòng Phụ Dưới <span class="text-muted small">(tuỳ
                                        chọn)</span></label>
                                <input type="text" class="form-control" id="update_footer" name="footer"
                                    placeholder="VD: Ngày……tháng……năm 20……" oninput="updateSealPreview('update')">
                            </div>
                            <div class="row">
                                <div class="col-6">
                                    <div class="form-group">
                                        <label for="update_border">Kiểu Viền</label>
                                        <select class="form-control" id="update_border" name="border_style"
                                            onchange="updateSealPreview('update')">
                                            <option value="double">Viền đôi</option>
                                            <option value="single">Viền đơn</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-group">
                                        <label for="update_color">Màu Dấu <span class="text-danger">*</span></label>
                                        <input type="color" class="form-control" id="update_color" name="color"
                                            style="height: 38px; padding: 4px;" required
                                            oninput="updateSealPreview('update')">
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="update_size">Kích Thước Dấu
                                    <span id="update_size_label" class="badge badge-info ml-1">100%</span>
                                </label>
                                <input type="range" class="form-control-range w-100" id="update_size" name="size"
                                    min="50" max="200" step="5" value="100"
                                    oninput="updateSealPreview('update')">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label>Xem Trước</label>
                            <div class="d-flex align-items-center justify-content-center bg-light border rounded"
                                style="min-height: 220px;">
                                <span id="update_seal_preview" class="seal-stamp-preview seal-border-double"
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
                    <button type="submit" class="btn btn-primary">Cập nhật</button>
                </div>
            </form>
        </div>
    </div>
</div>

@if ($errors->updateErrors->any())
    <script>
        $(document).ready(function() {
            $('#updateModal').modal('show');
        });
    </script>
@endif
