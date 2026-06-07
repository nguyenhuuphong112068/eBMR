<!-- Master Form Import Modal -->
<div class="modal fade" id="masterFormModal" tabindex="-1" role="dialog" aria-labelledby="masterFormModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title font-weight-bold" id="masterFormModalLabel">
                    <i class="fas fa-file-import me-2"></i>Nhập từ Biểu mẫu gốc (Master Form)
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close" style="opacity: 1;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body bg-light p-0">
                <div class="p-3 bg-white border-bottom d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="mb-1 text-dark fw-bold">Chọn biểu mẫu để nhập</h6>
                        <small class="text-muted">Dữ liệu từ biểu mẫu sẽ được chèn vào công đoạn hiện tại của bạn.</small>
                    </div>
                    <div class="input-group" style="width: 250px;">
                        <input type="text" id="masterFormSearch" class="form-control form-control-sm" placeholder="Tìm kiếm biểu mẫu...">
                        <div class="input-group-append">
                            <span class="input-group-text bg-white text-muted"><i class="fas fa-search"></i></span>
                        </div>
                    </div>
                </div>
                
                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-hover table-striped mb-0" id="masterFormTable">
                        <thead class="bg-white" style="position: sticky; top: 0; z-index: 10;">
                            <tr>
                                <th class="text-center" style="width: 50px;">#</th>
                                <th>Tên công đoạn / Biểu mẫu</th>
                                <th>Cập nhật lần cuối</th>
                                <th class="text-center" style="width: 100px;">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody id="masterFormListContainer">
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">
                                    <i class="fas fa-spinner fa-spin me-2"></i> Đang tải dữ liệu...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-white">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-dismiss="modal">Hủy</button>
            </div>
        </div>
    </div>
</div>
