<script>
    (function() {
        window.workspaceSplitActive = false;
        
        // Define storageKey or templateId (same as comments.blade.php)
        const templateId = '{{ $template->id ?? 0 }}';

        window.toggleWorkspaceSplit = function() {
            window.workspaceSplitActive = !window.workspaceSplitActive;
            
            const canvasCol = document.getElementById('canvas-col');
            const designerWorkspace = document.getElementById('designer-workspace');
            const splitBtn = document.getElementById('btn-split-workspace');
            
            if (!canvasCol || !designerWorkspace) return;
            
            if (window.workspaceSplitActive) {
                // Add split active class to body
                document.body.classList.add('workspace-split-active');
                if (splitBtn) {
                    splitBtn.classList.add('text-success', 'border-success');
                    splitBtn.title = "Tắt chia màn hình";
                }
                
                // Create split container
                const wrapper = document.createElement('div');
                wrapper.id = 'split-workspace-wrapper';
                wrapper.className = 'split-workspace-container';
                wrapper.innerHTML = `
                    <div id="split-pane-top" class="split-pane">
                        <div class="split-pane-header">
                            <span class="badge bg-primary text-white py-2 px-3 fw-bold" style="font-size: 0.8rem;"><i class="fas fa-edit me-1"></i>Vùng soạn thảo (Active)</span>
                            <div>
                                <button class="btn btn-xs btn-outline-primary me-2 py-1 px-2 fw-bold" onclick="swapSplitWorkspace()" title="Đổi vùng soạn thảo"><i class="fas fa-exchange-alt fa-rotate-90"></i> Đổi bên</button>
                                <button class="btn btn-xs btn-outline-danger py-1 px-2 fw-bold" onclick="toggleWorkspaceSplit()" title="Đóng chia màn hình"><i class="fas fa-times"></i> Đóng</button>
                            </div>
                        </div>
                        <!-- Live Workspace will be appended here -->
                    </div>
                    <div id="split-pane-bottom" class="split-pane">
                        <div class="split-pane-header">
                            <span class="badge bg-secondary text-white py-2 px-3 fw-bold" style="font-size: 0.8rem;"><i class="fas fa-eye me-1"></i>Vùng so sánh (Xem/Read-only)</span>
                            <button class="btn btn-xs btn-outline-primary py-1 px-2 fw-bold" onclick="swapSplitWorkspace()" title="Soạn thảo tại đây"><i class="fas fa-exchange-alt fa-rotate-90"></i> Soạn thảo tại đây</button>
                        </div>
                        <div id="preview-workspace" class="d-flex justify-content-start align-items-start" style="width: 100%; margin: 0; padding-right: 20px;">
                            <div class="page-a4 shadow mt-2" style="border-radius: 4px; flex-shrink: 0; position: relative;" id="preview-document-page">
                                <div id="preview-editor-content" class="p-0"></div>
                            </div>
                        </div>
                    </div>
                `;
                
                // Append wrapper to canvas-col
                canvasCol.appendChild(wrapper);
                
                // Move real designer-workspace to top pane
                const topPane = document.getElementById('split-pane-top');
                if (topPane) {
                    topPane.appendChild(designerWorkspace);
                }
                
                // Sync content to preview
                syncPreviewContent();
                
            } else {
                // Remove split active class
                document.body.classList.remove('workspace-split-active');
                if (splitBtn) {
                    splitBtn.classList.remove('text-success', 'border-success');
                    splitBtn.title = "Chia đôi màn hình (Split View)";
                }
                
                // Move designer-workspace back to canvas-col
                canvasCol.appendChild(designerWorkspace);
                
                // Remove split wrapper
                const wrapper = document.getElementById('split-workspace-wrapper');
                if (wrapper) {
                    wrapper.remove();
                }
            }
            
            // Adjust comments positions in the new viewport height
            if (typeof window.renderComments === 'function') {
                setTimeout(window.renderComments, 100);
            }
        };
        
        window.swapSplitWorkspace = function() {
            const paneTop = document.getElementById('split-pane-top');
            const paneBottom = document.getElementById('split-pane-bottom');
            const designerWorkspace = document.getElementById('designer-workspace');
            const previewWorkspace = document.getElementById('preview-workspace');
            
            if (!paneTop || !paneBottom || !designerWorkspace || !previewWorkspace) return;
            
            const designerIsTop = paneTop.contains(designerWorkspace);
            
            const headerTop = paneTop.querySelector('.split-pane-header');
            const headerBottom = paneBottom.querySelector('.split-pane-header');
            
            if (designerIsTop) {
                // Move designer to bottom
                paneBottom.appendChild(designerWorkspace);
                paneTop.appendChild(previewWorkspace);
                
                // Update headers
                headerTop.innerHTML = `
                    <span class="badge bg-secondary text-white py-2 px-3 fw-bold" style="font-size: 0.8rem;"><i class="fas fa-eye me-1"></i>Vùng so sánh (Xem/Read-only)</span>
                    <button class="btn btn-xs btn-outline-primary py-1 px-2 fw-bold" onclick="swapSplitWorkspace()" title="Soạn thảo tại đây"><i class="fas fa-exchange-alt fa-rotate-90"></i> Soạn thảo tại đây</button>
                `;
                headerBottom.innerHTML = `
                    <span class="badge bg-primary text-white py-2 px-3 fw-bold" style="font-size: 0.8rem;"><i class="fas fa-edit me-1"></i>Vùng soạn thảo (Active)</span>
                    <div>
                        <button class="btn btn-xs btn-outline-primary me-2 py-1 px-2 fw-bold" onclick="swapSplitWorkspace()" title="Đổi vùng soạn thảo"><i class="fas fa-exchange-alt fa-rotate-90"></i> Đổi bên</button>
                        <button class="btn btn-xs btn-outline-danger py-1 px-2 fw-bold" onclick="toggleWorkspaceSplit()" title="Đóng chia màn hình"><i class="fas fa-times"></i> Đóng</button>
                    </div>
                `;
            } else {
                // Move designer to top
                paneTop.appendChild(designerWorkspace);
                paneBottom.appendChild(previewWorkspace);
                
                // Update headers
                headerTop.innerHTML = `
                    <span class="badge bg-primary text-white py-2 px-3 fw-bold" style="font-size: 0.8rem;"><i class="fas fa-edit me-1"></i>Vùng soạn thảo (Active)</span>
                    <div>
                        <button class="btn btn-xs btn-outline-primary me-2 py-1 px-2 fw-bold" onclick="swapSplitWorkspace()" title="Đổi vùng soạn thảo"><i class="fas fa-exchange-alt fa-rotate-90"></i> Đổi bên</button>
                        <button class="btn btn-xs btn-outline-danger py-1 px-2 fw-bold" onclick="toggleWorkspaceSplit()" title="Đóng chia màn hình"><i class="fas fa-times"></i> Đóng</button>
                    </div>
                `;
                headerBottom.innerHTML = `
                    <span class="badge bg-secondary text-white py-2 px-3 fw-bold" style="font-size: 0.8rem;"><i class="fas fa-eye me-1"></i>Vùng so sánh (Xem/Read-only)</span>
                    <button class="btn btn-xs btn-outline-primary py-1 px-2 fw-bold" onclick="swapSplitWorkspace()" title="Soạn thảo tại đây"><i class="fas fa-exchange-alt fa-rotate-90"></i> Soạn thảo tại đây</button>
                `;
            }
            
            // Re-sync content
            syncPreviewContent();
            
            // Adjust comments positions in the new viewport height
            if (typeof window.renderComments === 'function') {
                setTimeout(window.renderComments, 100);
            }
        };
        
        window.syncPreviewContent = function() {
            if (!window.workspaceSplitActive) return;
            
            const previewContent = document.getElementById('preview-editor-content');
            const liveContent = document.getElementById('editor-content');
            
            if (!previewContent || !liveContent) return;
            
            // Clone the HTML
            let html = liveContent.innerHTML;
            
            // Rewrite IDs to prevent clashing
            html = html.replace(/id="([^"]+)"/g, 'id="preview-$1"');
            
            // Disable editing in preview
            html = html.replace(/contenteditable="true"/g, 'contenteditable="false"');
            
            previewContent.innerHTML = html;
        };
    })();
</script>
