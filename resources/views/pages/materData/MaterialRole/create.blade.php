<div class="modal fade" id="createModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form action="{{ route('pages.materData.materialRole.store') }}" method="POST">
            @csrf
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-light border-0 py-3 px-4">
                    <h5 class="modal-title fw-bold text-primary">Thêm Chức Năng Nguyên Liệu</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-4">
                    <div class="form-group mb-3">
                        <label class="fw-bold small text-uppercase text-muted mb-2">Tên Chức Năng</label>
                        <input type="text" class="form-control" name="name" value="{{ old('name') }}" required>
                        @error('name', 'createErrors')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 py-3 px-4">
                    <button type="button" class="btn btn-light px-4" data-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary px-4 fw-bold">Lưu lại</button>
                </div>
            </div>
        </form>
    </div>
</div>
