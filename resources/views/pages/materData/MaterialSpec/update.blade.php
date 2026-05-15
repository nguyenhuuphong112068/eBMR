<div class="modal fade" id="updateModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form action="{{ route('pages.materData.materialSpec.update') }}" method="POST">
            @csrf
            <input type="hidden" name="id" id="update_id">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-light border-0 py-3 px-4">
                    <h5 class="modal-title fw-bold text-warning">Cập Nhật Tiêu Chuẩn</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-4">
                    <div class="form-group mb-3">
                        <label class="fw-bold small text-uppercase text-muted mb-2">Tên Tiêu Chuẩn</label>
                        <input type="text" class="form-control" name="name" id="update_name" required>
                        @error('name', 'updateErrors')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 py-3 px-4">
                    <button type="button" class="btn btn-light px-4" data-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-warning px-4 fw-bold">Cập nhật</button>
                </div>
            </div>
        </form>
    </div>
</div>
