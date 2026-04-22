<div class="modal fade" id="updateMfModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-edit me-2"></i> CẬP NHẬT BIỂU MẪU GỐC</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('pages.category.mf.update') }}" method="POST">
                @csrf
                <input type="hidden" name="id" id="up_id">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Mã Biểu Mẫu <span class="text-danger">*</span></label>
                        <input type="text" class="form-control rounded-pill" name="code" id="up_code" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Tên Biểu Mẫu <span class="text-danger">*</span></label>
                        <input type="text" class="form-control rounded-pill" name="name" id="up_name" required>
                    </div>
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label fw-bold">Tên Công Đoạn</label>
                            <input type="text" class="form-control rounded-pill" name="stage_name" id="up_stage_name">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Mã C.Đoạn</label>
                            <input type="number" class="form-control rounded-pill" name="stage_code" id="up_stage_code">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light p-3">
                    <button type="button" class="btn btn-white rounded-pill px-4" data-dismiss="modal">Hủy bỏ</button>
                    <button type="submit" class="btn btn-info rounded-pill px-4 shadow-sm text-white">
                        <i class="fas fa-save me-2"></i> CẬP NHẬT
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
