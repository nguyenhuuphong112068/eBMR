<!-- Criteria Drag & Drop Right Sidebar -->
<div id="criteriaSidebar" class="criteria-sidebar shadow-lg border-start bg-white d-none">
    <div class="criteria-sidebar-header d-flex align-items-center justify-content-between p-3 text-white" style="background: #164e63;">
        <h6 class="mb-0 fw-bold small">
            <i class="fas fa-vial me-2 text-warning"></i>LIÊN KẾT TIÊU CHUẨN
        </h6>
        <button type="button" class="btn-close-white text-white border-0 bg-transparent cursor-pointer" onclick="closeCriteriaSidebar()" title="Đóng thanh liên kết" style="font-size: 1.1rem; opacity: 0.8; outline: none;">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <div class="criteria-sidebar-body p-3" style="overflow-y: auto; height: calc(100vh - 120px);">
        <!-- Quick Help Alert -->
        <div class="alert alert-info py-2 px-3 mb-3 border-0" style="font-size: 0.75rem; background-color: #ecfeff; color: #0891b2; border-radius: 8px;">
            <i class="fas fa-info-circle me-1 text-info"></i> 
            <strong>Kéo & Thả:</strong> Chọn 1 trong các thẻ (Chỉ tiêu, Tiêu chuẩn, Giới hạn) của dòng tương ứng và kéo thả trực tiếp vào ô trong bảng.
        </div>

        <!-- Filters Section -->
        <div class="mb-3 p-2 bg-light rounded" style="border: 1px solid #e2e8f0;">
            <div class="input-group input-group-sm mb-2 shadow-sm">
                <span class="input-group-text bg-white border-end-0 text-muted"><i class="fas fa-search"></i></span>
                <input type="text" id="criteriaSidebarSearch" class="form-control border-start-0" placeholder="Tìm tên chỉ tiêu..." style="font-size: 0.8rem;">
            </div>
            <div class="form-group mb-0">
                <select id="criteriaSidebarStageFilter" class="form-select form-select-sm shadow-sm" style="font-size: 0.8rem; cursor: pointer;">
                    <option value="">-- Tất cả công đoạn --</option>
                </select>
            </div>
        </div>

        <!-- Criteria List Container -->
        <div id="criteriaSidebarList" class="d-flex flex-column gap-2">
            <div class="text-center py-4 text-muted small">
                <div class="spinner-border spinner-border-sm text-info me-2"></div> Đang tải tiêu chuẩn...
            </div>
        </div>
    </div>
</div>
