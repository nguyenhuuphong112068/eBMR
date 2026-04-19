<!-- Open Template Modal -->
<div class="modal fade" id="openTemplateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content shadow-lg border-0" style="border-radius: 12px;">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold text-navy"><i class="fas fa-folder-open me-2"></i> MỞ HỒ SƠ MẪU</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="input-group mb-4">
                    <span class="input-group-text bg-white border-end-0"><i
                            class="fas fa-search text-muted"></i></span>
                    <input type="text" class="form-control border-start-0" id="templateSearch"
                        placeholder="Tìm kiếm tên hồ sơ..." oninput="filterTemplates(this.value)">
                </div>
                <div class="list-group list-group-flush" id="templateListLoading"
                    style="max-height: 400px; overflow-y: auto;">
                    <div class="text-center py-5">
                        <div class="spinner-border text-navy" role="status"></div>
                        <p class="mt-2 text-muted">Đang tải danh sách...</p>
                    </div>
                </div>
                <div class="list-group list-group-flush d-none" id="templateList"
                    style="max-height: 400px; overflow-y: auto;">
                    <!-- Templates go here -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- History Modal -->
<div class="modal fade" id="historyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content shadow-lg border-0" style="border-radius: 12px;">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title fw-bold"><i class="fas fa-history me-2"></i> LỊCH SỬ THAY ĐỔI</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-light">
                <div id="historyTimeline" class="position-relative">
                    <!-- History items go here -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Split Cell Modal -->
<div class="modal fade" id="splitModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content shadow-lg border-0" style="border-radius: 12px;">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold small"><i class="fas fa-columns me-2"></i> TÁCH Ô</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="small fw-bold mb-1">Số hàng</label>
                    <input type="number" id="splitRows" class="form-control form-control-sm" value="1" min="1">
                </div>
                <div class="mb-3">
                    <label class="small fw-bold mb-1">Số cột</label>
                    <input type="number" id="splitCols" class="form-control form-control-sm" value="2" min="1">
                </div>
                <div class="d-grid">
                    <button class="btn btn-primary btn-sm fw-bold w-100" onclick="executeAdvancedSplit()">Xác nhận tách</button>
                </div>
            </div>
        </div>
    </div>
</div>
