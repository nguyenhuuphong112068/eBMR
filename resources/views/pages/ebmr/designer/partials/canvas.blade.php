<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <!-- Outline Sidebar -->
        <div id="outline-col" class="col-lg-2 transition-all">
            <div class="p-3 bg-white rounded shadow-sm outline-sidebar h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0 text-muted fw-bold" style="font-size: 0.85rem;"><i class="fas fa-list-ul me-2"></i>MỤC
                        LỤC & THẺ</h6>
                    <button class="btn btn-sm btn-light text-muted border-0" onclick="toggleOutline(true)"
                        title="Thu nhỏ"><i class="fas fa-chevron-left"></i></button>
                </div>
                <div id="outline-content">
                    <div id="document-outline">
                        <div class="outline-empty">Chưa có nội dung. Nhập văn bản và định dạng Tiêu đề (H1, H2,...) để
                            tạo thẻ.</div>
                    </div>
                </div>
            </div>
            <!-- Minimized ToC -->
            <div id="outline-minimized"
                class="d-none bg-white rounded-end shadow-sm py-3 text-center cursor-pointer transition-all"
                onclick="toggleOutline(false)"
                style="width: 34px; position: sticky; top: 200px; min-height: 200px; display: flex; flex-direction: column; align-items: center; border: 1px solid #ddd; border-left: none; z-index: 1040;">
                <i class="fas fa-list-ul text-muted mb-3 mt-1"></i>
                <div
                    style="writing-mode: vertical-rl; transform: rotate(180deg); font-size: 0.75rem; font-weight: bold; color: #6c757d; white-space: nowrap; flex-grow: 1; display: flex; align-items: center; justify-content: center;">
                    MỤC LỤC & THẺ</div>
                <i class="fas fa-chevron-right text-muted mb-1 mt-3"></i>
            </div>
        </div>

        <!-- Document Canvas -->
        <div id="canvas-col" class="col-lg-9 transition-all">
            <div class="doc-header mb-3">
                <input type="text" id="templateName" class="form-control doc-title-input"
                    placeholder="Tài liệu không có tiêu đề" {{ $isReadOnly ? 'readonly' : '' }}>
            </div>

            @if (!$isReadOnly)
                <!-- Editor Ruler -->
                <div class="editor-ruler" id="editor-ruler">
                    <div class="ruler-scale"></div>
                    <div class="ruler-margin-left" id="ruler-margin-left"></div>
                    <div class="ruler-margin-right" id="ruler-margin-right"></div>
                    <div class="ruler-marker-left" id="ruler-marker-left" title="Kéo lề trái"></div>
                    <div class="ruler-marker-right" id="ruler-marker-right" title="Kéo lề phải"></div>
                </div>
            @endif

            <!-- Fluid Workplace -->
            <div id="designer-workspace" class="d-flex justify-content-start align-items-start"
                style="width: 100%; margin: 0; padding-right: 20px;">
                <!-- Main A4 Page -->
                <div class="page-a4 shadow mt-2" style="border-radius: 4px; flex-shrink: 0; position: relative;"
                    id="document-page"
                    ondblclick="if(event.target.id === 'document-page' || event.target.id === 'editor-content' || event.target.id === 'drop-hint') { if(typeof quickAddText === 'function') quickAddText(event, typeof items !== 'undefined' ? items.length : 0); }">
                    <div id="editor-content" class="p-0">
                        <!-- Elements flow here like a real document -->
                        <div id="drop-hint" class="text-center py-5 opacity-25" style="pointer-events: none;">
                            <i class="fas fa-plus-circle fa-3x mb-3"></i>
                            <h4>Bắt đầu thiết kế hồ sơ</h4>
                            <p>Click đúp vào giấy để bắt đầu gõ hoặc chọn linh kiện từ thanh công cụ bên trên</p>
                        </div>
                    </div>
                </div>

                <!-- Comment Gutter (Always to the right of the page) -->
                <div id="comment-gutter" class="comment-gutter ms-4 d-none"
                    style="position: relative; flex-grow: 1; min-width: 300px; height: 100%;">
                    <!-- Comments render here via JS -->
                </div>
            </div>
        </div>

        <!-- Property Panel Slot -->
        <div id="sidebar-col" class="col-lg-1 transition-all p-0">
            @if (!$isReadOnly)
                @include('pages.ebmr.designer.partials.sidebar')
            @endif

        </div>
    </div>
</div>
