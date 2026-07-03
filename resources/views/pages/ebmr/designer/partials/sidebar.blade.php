<!-- Property Panel (Floating/Fixed) -->
<div id="property-panel" class="bg-transparent shadow-none border-0" style="border-radius: 12px; box-shadow: none;">
    <div id="sidebar-full" class="d-none">
        <div class="card-header border-0 py-2 d-flex align-items-center shadow-sm" id="sidebar-drag-handle" style="cursor: move; position: sticky; top: 0; z-index: 1045; background-color: #e3f2fd;">
            <h6 class="mb-0 fw-bold me-auto" style="color: #0d6efd;"><i class="fas fa-cog me-2"></i> CÀI ĐẶT</h6>
            <button class="btn btn-sm border-0 ms-auto p-0 px-1" style="background: rgba(13, 110, 253, 0.1); color: #0d6efd;" onclick="toggleSidebar(true)" title="Thu nhỏ"><i
                    class="fas fa-chevron-right"></i></button>
        </div>
        <div class="card-body" id="prop-body">
            <!-- Dynamic Props populated by selectItem() -->
        </div>
    </div>

    <!-- Minimized Sidebar Trigger -->
    <div id="sidebar-minimized" class="rounded-start shadow-sm px-3 py-2 text-center cursor-pointer transition-all"
        onclick="toggleSidebar(false)"
        style="position: fixed; right: 0; top: 60px; display: flex; align-items: center; border: 1px solid #dadce0; border-right: none; z-index: 1040; background-color: #e3f2fd; color: #0d6efd;">
        <i class="fas fa-cog me-2"></i>
        <div style="font-size: 0.85rem; font-weight: bold; white-space: nowrap; letter-spacing: 1px;">
            CÀI ĐẶT
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        setTimeout(function() {
            const sidebarCol = document.getElementById('sidebar-col');
            const dragHandle = document.getElementById('sidebar-drag-handle');
            
            if (sidebarCol && dragHandle) {
                let isDragging = false;
                let initialX, initialY;
                
                if (typeof sidebarCol.dataset.dragX === 'undefined') {
                    sidebarCol.dataset.dragX = 0;
                    sidebarCol.dataset.dragY = 0;
                }

                dragHandle.addEventListener('mousedown', function(e) {
                    if (e.target.closest('button')) return;
                    
                    initialX = e.clientX - parseFloat(sidebarCol.dataset.dragX);
                    initialY = e.clientY - parseFloat(sidebarCol.dataset.dragY);
                    isDragging = true;
                    // Tắt transition để kéo mượt mà, không bị lag (delay do animation)
                    sidebarCol.style.transition = 'none';
                    e.preventDefault();
                });

                document.addEventListener('mousemove', function(e) {
                    if (isDragging) {
                        e.preventDefault();
                        const currentX = e.clientX - initialX;
                        const currentY = e.clientY - initialY;
                        
                        sidebarCol.dataset.dragX = currentX;
                        sidebarCol.dataset.dragY = currentY;
                        
                        sidebarCol.style.transform = `translate(${currentX}px, ${currentY}px)`;
                    }
                });

                document.addEventListener('mouseup', function() {
                    if (isDragging) {
                        isDragging = false;
                        // Phục hồi lại transition
                        sidebarCol.style.transition = '';
                    }
                });
            }
        }, 500);
    });
</script>
