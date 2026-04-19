<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <!-- Outline Sidebar -->
        <div class="col-lg-2">
            <div class="p-3 bg-white rounded shadow-sm outline-sidebar">
                <h6 class="mb-3 text-muted fw-bold" style="font-size: 0.85rem;"><i class="fas fa-list-ul me-2"></i>MỤC LỤC & THẺ</h6>
                <div id="document-outline">
                    <div class="outline-empty">Chưa có nội dung. Nhập văn bản và định dạng Tiêu đề (H1, H2,...) để tạo thẻ.</div>
                </div>
            </div>
        </div>

        <!-- Document Canvas -->
        <div class="col-lg-7">
            <div class="doc-header mb-3">
                <input type="text" id="templateName" class="form-control doc-title-input"
                    placeholder="Tài liệu không có tiêu đề">
            </div>

            <!-- Editor Ruler -->
            <div class="editor-ruler" id="editor-ruler">
                <div class="ruler-scale"></div>
                <div class="ruler-margin-left" id="ruler-margin-left"></div>
                <div class="ruler-margin-right" id="ruler-margin-right"></div>
                <div class="ruler-marker-left" id="ruler-marker-left" title="Kéo lề trái"></div>
                <div class="ruler-marker-right" id="ruler-marker-right" title="Kéo lề phải"></div>
            </div>

            <div class="page-a4 shadow" style="border-radius: 0 0 4px 4px;" id="document-page" ondblclick="if(event.target.id === 'document-page' || event.target.id === 'editor-content' || event.target.id === 'drop-hint') { if(typeof quickAddText === 'function') quickAddText(event, typeof items !== 'undefined' ? items.length : 0); }">
                <div id="editor-content" class="p-5">
                    <!-- Elements flow here like a real document -->
                    <div id="drop-hint" class="text-center py-5 opacity-25" style="pointer-events: none;">
                        <i class="fas fa-plus-circle fa-3x mb-3"></i>
                        <h4>Bắt đầu thiết kế hồ sơ</h4>
                        <p>Click đúp vào giấy để bắt đầu gõ hoặc chọn linh kiện từ thanh công cụ bên trên</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Property Panel Slot -->
        <div class="col-lg-3">
            @include('pages.ebmr.designer.partials.sidebar')
        </div>
    </div>
</div>
