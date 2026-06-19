<!-- Components Sidebar -->
<div id="componentsSidebar" class="components-sidebar shadow-lg border-end bg-white d-none">
    <div class="components-sidebar-header d-flex align-items-center justify-content-between p-3 text-white" style="background: #0284c7;">
        <h6 class="mb-0 fw-bold small">
            <i class="fas fa-layer-group me-2 text-warning"></i>THÀNH PHẦN (CO)
        </h6>
        <button type="button" class="btn-close-white text-white border-0 bg-transparent cursor-pointer" onclick="toggleComponentSidebar()" title="Đóng thanh thành phần" style="font-size: 1.1rem; opacity: 0.8; outline: none;">
            <i class="fas fa-times"></i>
        </button>
    </div>
    
    <div class="p-2 border-bottom" style="background: #f8fafc;">
        <div class="input-group input-group-sm">
            <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
            <input type="text" id="componentSidebarSearch" class="form-control border-start-0 ps-0" placeholder="Tìm kiếm thành phần...">
        </div>
        <div class="mt-2 text-muted" style="font-size: 0.7rem; font-style: italic;">
            <i class="fas fa-info-circle text-info me-1"></i> Kéo thẻ (Drag) và thả (Drop) vào dải phân cách giữa các khối.
        </div>
    </div>

    <div id="componentsSidebarList" class="components-sidebar-body p-2" style="overflow-y: auto; height: calc(100vh - 150px);">
        <div class="text-center py-4 text-muted small">
            <div class="spinner-border spinner-border-sm text-info me-2"></div> Đang tải...
        </div>
    </div>
</div>
