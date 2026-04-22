<!-- Property Panel (Floating/Fixed) -->
<div id="property-panel" class="bg-transparent shadow-none border-0"
    style="border-radius: 12px; position: sticky; top: 170px; box-shadow: none;">
    <div id="sidebar-full" class="d-none">
        <div class="card-header bg-light border-0 py-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold text-navy"><i class="fas fa-cog me-2"></i> CÀI ĐẶT</h6>
            <button class="btn btn-sm btn-light text-muted border-0" onclick="toggleSidebar(true)" title="Thu nhỏ"><i class="fas fa-chevron-right"></i></button>
        </div>
        <div class="card-body" id="prop-body">
            <!-- Dynamic Props populated by selectItem() -->
        </div>
    </div>

    <!-- Minimized Sidebar Trigger -->
    <div id="sidebar-minimized" class="bg-white rounded-start shadow-sm py-3 text-center cursor-pointer transition-all" onclick="toggleSidebar(false)" 
        style="width: 30px; position: fixed; right: 0; top: 250px; height: 180px; display: flex; flex-direction: column; align-items: center; border: 1px solid #dadce0; border-right: none; z-index: 1040;">
        <i class="fas fa-chevron-left text-muted mt-2"></i>
        <div style="writing-mode: vertical-rl; transform: rotate(180deg); font-size: 0.75rem; font-weight: bold; color: #6c757d; white-space: nowrap; flex-grow: 1; display: flex; align-items: center; justify-content: center; letter-spacing: 1px;">CÀI ĐẶT</div>
    </div>
</div>
