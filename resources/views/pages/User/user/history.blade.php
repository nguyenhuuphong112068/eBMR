<div class="modal fade" id="historyModal" tabindex="-1" role="dialog" aria-labelledby="historyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 90%; width: 90%;">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 24px; overflow: hidden; background: rgba(255, 255, 255, 0.98); border: 1px solid rgba(0,0,0,0.05);">
            <div class="modal-header border-0 bg-transparent pt-4 px-4 pb-2">
                <div class="d-flex align-items-center justify-content-between w-100">
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-1" id="historyModalLabel" style="font-family: 'Arimo', sans-serif; letter-spacing: 0.5px;">
                            LỊCH SỬ THAY ĐỔI THÔNG TIN
                        </h5>
                        <p class="text-muted mb-0 small" id="historyModalUserSubtitle"></p>
                    </div>
                    <button type="button" class="close text-dark opacity-75" data-dismiss="modal" aria-label="Close" style="font-size: 1.75rem; border: none; background: transparent; padding: 0; outline: none;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            </div>
            
            <div class="modal-body px-4 py-3">
                <div id="historyLoading" class="text-center py-5">
                    <div class="spinner-border text-info" role="status" style="width: 2.5rem; height: 2.5rem;">
                        <span class="sr-only">Đang tải...</span>
                    </div>
                    <p class="text-muted mt-3 small">Đang tải dữ liệu lịch sử...</p>
                </div>
                
                <div id="historyEmpty" class="text-center py-5 d-none">
                    <div class="mb-3 text-muted">
                        <i class="fas fa-history fa-3x opacity-50"></i>
                    </div>
                    <p class="text-muted mb-0">Chưa ghi nhận lịch sử thay đổi thông tin của người dùng này.</p>
                </div>
                
                <div id="historyContent" class="table-responsive rounded-3 overflow-hidden d-none" style="border: 1px solid #f1f5f9; max-height: 480px; overflow-y: auto;">
                    <table class="table table-hover border-0 align-middle mb-0">
                        <thead style="background-color: #f8fafc; border-bottom: 2px solid #e2e8f0; position: sticky; top: 0; z-index: 10;">
                            <tr>
                                <th class="text-center py-3" style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: #475569; letter-spacing: 0.5px; vertical-align: middle;">STT</th>
                                <th style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: #475569; letter-spacing: 0.5px; vertical-align: middle;">Tên Đăng Nhập</th>
                                <th style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: #475569; letter-spacing: 0.5px; vertical-align: middle;">Nhóm</th>
                                <th style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: #475569; letter-spacing: 0.5px; vertical-align: middle;">Tên Người Dùng</th>
                                <th style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: #475569; letter-spacing: 0.5px; vertical-align: middle;">Phòng Ban / Tổ</th>
                                <th style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: #475569; letter-spacing: 0.5px; vertical-align: middle;">Chức vụ</th>
                                <th style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: #475569; letter-spacing: 0.5px; vertical-align: middle;">Mail</th>
                                <th class="text-center" style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: #475569; letter-spacing: 0.5px; vertical-align: middle;">Trạng thái</th>
                                <th class="text-center" style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: #475569; letter-spacing: 0.5px; vertical-align: middle;">Chữ ký</th>
                                <th style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: #475569; letter-spacing: 0.5px; vertical-align: middle;">Người Tạo</th>
                                <th style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: #475569; letter-spacing: 0.5px; vertical-align: middle;">Ngày Tạo</th>
                            </tr>
                        </thead>
                        <tbody id="historyTableBody" style="font-size: 13.5px;">
                            <!-- Dynamic data loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="modal-footer border-0 bg-transparent px-4 pb-4">
                <button type="button" class="btn btn-secondary px-4 rounded-pill fw-bold shadow-sm" data-dismiss="modal" style="border: 1px solid rgba(0,0,0,0.1); background-color: #f8fafc; color: #475569;">Đóng</button>
            </div>
        </div>
    </div>
</div>
