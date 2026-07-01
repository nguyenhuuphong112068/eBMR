<!-- Equipment Sidebar -->
<div id="equipmentSidebar" class="components-sidebar shadow-lg border-end bg-white d-none">
    <div class="components-sidebar-header d-flex align-items-center justify-content-between p-3 text-white" style="background: #198754;">
        <h6 class="mb-0 fw-bold small">
            <i class="fas fa-tools me-2 text-warning"></i>THIẾT BỊ LIÊN QUAN
        </h6>
        <button type="button" class="btn-close-white text-white border-0 bg-transparent cursor-pointer" onclick="toggleEquipmentSidebar()" title="Đóng thanh thiết bị" style="font-size: 1.1rem; opacity: 0.8; outline: none;">
            <i class="fas fa-times"></i>
        </button>
    </div>
    
    <div class="p-2 border-bottom" style="background: #f8fafc;">
        <div class="input-group input-group-sm mb-2">
            <span class="input-group-text bg-white border-end-0"><i class="fas fa-filter text-muted"></i></span>
            <select id="equipmentSidebarDepartmentFilter" class="form-select form-select-sm border-start-0 ps-0" onchange="loadEquipmentSidebarList()">
                <option value="">-- Tất cả Phân Xưởng --</option>
                <!-- Dữ liệu phòng ban sẽ được load qua AJAX -->
            </select>
        </div>
        <div class="input-group input-group-sm">
            <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
            <input type="text" id="equipmentSidebarSearch" class="form-control border-start-0 ps-0" placeholder="Tìm kiếm thiết bị..." onkeyup="filterEquipmentSidebarList()">
        </div>
    </div>

    <div id="equipmentSidebarList" class="components-sidebar-body p-2" style="overflow-y: auto; height: calc(100vh - 220px);">
        <!-- Danh sách thiết bị render qua AJAX -->
        <div class="text-center py-4 text-muted small">
            <div class="spinner-border spinner-border-sm text-info me-2"></div> Đang tải dữ liệu...
        </div>
    </div>
</div>
