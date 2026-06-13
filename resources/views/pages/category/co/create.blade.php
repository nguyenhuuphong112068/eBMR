<div class="modal fade" id="modal-create" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-navy text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-plus me-2"></i> THÊM DANH MỤC THÀNH PHẦN</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('pages.category.co.store') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Mã Danh Mục <span class="text-danger">*</span></label>
                        <input type="text" class="form-control rounded-pill" name="code" required placeholder="Nhập mã">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Tên Danh Mục <span class="text-danger">*</span></label>
                        <input type="text" class="form-control rounded-pill" name="name" required placeholder="Nhập tên">
                    </div>
                </div>
                <div class="modal-footer bg-light p-3">
                    <button type="button" class="btn btn-white rounded-pill px-4" data-dismiss="modal">Hủy bỏ</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">
                        <i class="fas fa-save me-2"></i> LƯU LẠI
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
