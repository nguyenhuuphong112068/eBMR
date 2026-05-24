<!-- Document Properties Right Sidebar -->
<div id="propertiesSidebar" class="properties-sidebar shadow-lg border-start bg-white d-none">
    <div class="properties-sidebar-header d-flex align-items-center justify-content-between p-3 text-white" style="background: #0f766e;">
        <h6 class="mb-0 fw-bold small">
            <i class="fas fa-file-alt me-2 text-warning"></i>THUỘC TÍNH TÀI LIỆU
        </h6>
        <button type="button" class="btn-close-white text-white border-0 bg-transparent cursor-pointer" onclick="closePropertiesSidebar()" title="Đóng thanh thuộc tính" style="font-size: 1.1rem; opacity: 0.8; outline: none;">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <div class="properties-sidebar-body p-3" style="overflow-y: auto; height: calc(100vh - 120px);">
        <!-- Quick Help Alert -->
        <!-- <div class="alert alert-info py-2 px-3 mb-3 border-0" style="font-size: 0.75rem; background-color: #f0fdfa; color: #0d9488; border-radius: 8px;">
            <i class="fas fa-info-circle me-1 text-teal"></i> 
            <strong>Liên kết thuộc tính:</strong> Click nút "Chèn" để chèn thuộc tính vào vị trí con trỏ. Bạn có thể sửa đổi nội dung trực tiếp trên văn bản, mọi vị trí khác sẽ tự động đồng bộ theo.
        </div> -->

        <!-- Add Property Button & Input -->
        <div class="mb-3 p-2 bg-light rounded" style="border: 1px solid #e2e8f0;">
            <button class="btn btn-sm w-100 text-white fw-bold shadow-sm mb-2" onclick="addNewPropertyPrompt()" style="background-color: #0f766e;">
                <i class="fas fa-plus me-1"></i> Tạo thuộc tính mới
            </button>
            <div class="d-flex align-items-center justify-content-between pt-2 border-top" style="border-top: 1px dashed #cbd5e1 !important;">
                <span class="small fw-bold text-secondary"><i class="fas fa-highlighter me-1 text-warning"></i> Làm nổi bật (Highlight)</span>
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input cursor-pointer" type="checkbox" id="togglePropertyHighlight" checked onchange="handlePropertyHighlightChange(this.checked)">
                </div>
            </div>
        </div>

        <!-- Properties List Container -->
        <div id="propertiesSidebarList" class="d-flex flex-column gap-2">
            <div class="text-center py-4 text-muted small">
                <div class="spinner-border spinner-border-sm text-info me-2"></div> Đang tải thuộc tính...
            </div>
        </div>
    </div>
</div>
