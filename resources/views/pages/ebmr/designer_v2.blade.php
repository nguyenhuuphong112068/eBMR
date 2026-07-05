@extends('layout.master')

@section('title', 'eR Editor V2 (TipTap - Beta)')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection

@section('mainContent')
    <div class="content-wrapper" style="background-color: #f1f3f4; min-height: 100vh;">

        {{-- ===== Toolbar V2 ===== --}}
        <div id="v2-toolbar" class="shadow-sm bg-white px-3 py-2 d-flex align-items-center flex-wrap gap-2"
            style="position: sticky; top: 0; z-index: 1030; border-bottom: 1px solid #e2e8f0;">

            <button class="btn btn-sm btn-light" id="v2-btn-toc" title="Mục lục (danh sách công đoạn)">
                <i class="fas fa-list-ul"></i>
            </button>
            <button class="btn btn-sm btn-light" id="v2-btn-comments" title="Bình luận">
                <i class="fas fa-comment-dots"></i>
            </button>
            <button class="btn btn-sm btn-light" id="v2-btn-equipment" title="Thiết bị liên quan (kéo thả vào tài liệu)">
                <i class="fas fa-tools"></i>
            </button>
            <button class="btn btn-sm btn-light" id="v2-btn-components" title="Thành phần CO (kéo thả vào tài liệu)">
                <i class="fas fa-cubes"></i>
            </button>

            <div class="vr mx-2"></div>

            <button class="btn btn-sm btn-light" data-cmd="undo" title="Hoàn tác (trong vùng đang gõ)"><i class="fas fa-undo"></i></button>
            <button class="btn btn-sm btn-light" data-cmd="redo" title="Làm lại"><i class="fas fa-redo"></i></button>

            <div class="vr mx-2"></div>

            {{-- Kiểu đoạn / Font / Cỡ chữ (giống Google Docs) --}}
            <select id="v2-sel-style" data-fmt class="form-select form-select-sm v2-fmt-select" style="width: 120px;" title="Kiểu văn bản">
                <option value="p">Văn bản thường</option>
                <option value="h1">Tiêu đề 1</option>
                <option value="h2">Tiêu đề 2</option>
                <option value="h3">Tiêu đề 3</option>
            </select>
            <select id="v2-sel-font" data-fmt class="form-select form-select-sm v2-fmt-select" style="width: 140px;" title="Phông chữ">
                <option value="">Font mặc định</option>
                <option value="Times New Roman" style="font-family:'Times New Roman'">Times New Roman</option>
                <option value="Arial" style="font-family:Arial">Arial</option>
                <option value="Calibri" style="font-family:Calibri">Calibri</option>
                <option value="Tahoma" style="font-family:Tahoma">Tahoma</option>
                <option value="Courier New" style="font-family:'Courier New'">Courier New</option>
            </select>
            <select id="v2-sel-size" data-fmt class="form-select form-select-sm v2-fmt-select" style="width: 72px;" title="Cỡ chữ">
                <option value="">Cỡ</option>
                @foreach ([8, 9, 10, 11, 12, 14, 16, 18, 20, 24, 28, 36] as $sz)
                    <option value="{{ $sz }}pt">{{ $sz }}</option>
                @endforeach
            </select>

            <div class="vr mx-2"></div>

            <button class="btn btn-sm btn-light" data-cmd="bold" title="Đậm (Ctrl+B)"><i class="fas fa-bold"></i></button>
            <button class="btn btn-sm btn-light" data-cmd="italic" title="Nghiêng (Ctrl+I)"><i class="fas fa-italic"></i></button>
            <button class="btn btn-sm btn-light" data-cmd="underline" title="Gạch chân (Ctrl+U)"><i class="fas fa-underline"></i></button>
            <button class="btn btn-sm btn-light" data-cmd="strike" title="Gạch ngang"><i class="fas fa-strikethrough"></i></button>
            <button class="btn btn-sm btn-light" data-cmd="subscript" title="Chỉ số dưới (H₂O)"><i class="fas fa-subscript"></i></button>
            <button class="btn btn-sm btn-light" data-cmd="superscript" title="Chỉ số trên (m²)"><i class="fas fa-superscript"></i></button>

            {{-- Màu chữ / Màu nền (highlight) --}}
            <label class="v2-color-wrap" title="Màu chữ">
                <i class="fas fa-font"></i>
                <input type="color" id="v2-color-text" value="#000000">
            </label>
            <label class="v2-color-wrap" title="Màu nền chữ (highlight)">
                <i class="fas fa-highlighter"></i>
                <input type="color" id="v2-color-highlight" value="#ffff00">
            </label>
            <button class="btn btn-sm btn-light" id="v2-btn-unhighlight" title="Bỏ màu nền chữ"><i class="fas fa-eraser"></i></button>
            <button class="btn btn-sm btn-light" id="v2-btn-link" title="Chèn liên kết (Ctrl+K)"><i class="fas fa-link"></i></button>

            <div class="vr mx-2"></div>

            {{-- Căn lề / Giãn dòng / Danh sách / Thụt lề --}}
            <button class="btn btn-sm btn-light" data-cmd="align-left" title="Căn trái"><i class="fas fa-align-left"></i></button>
            <button class="btn btn-sm btn-light" data-cmd="align-center" title="Căn giữa"><i class="fas fa-align-center"></i></button>
            <button class="btn btn-sm btn-light" data-cmd="align-right" title="Căn phải"><i class="fas fa-align-right"></i></button>
            <button class="btn btn-sm btn-light" data-cmd="align-justify" title="Căn đều 2 bên"><i class="fas fa-align-justify"></i></button>
            <select id="v2-sel-lineheight" data-fmt class="form-select form-select-sm v2-fmt-select" style="width: 82px;" title="Giãn dòng">
                <option value="">Giãn</option>
                <option value="1">1</option>
                <option value="1.15">1.15</option>
                <option value="1.5">1.5</option>
                <option value="2">2</option>
            </select>
            <button class="btn btn-sm btn-light" data-cmd="bullet" title="Danh sách chấm"><i class="fas fa-list-ul"></i></button>
            <button class="btn btn-sm btn-light" data-cmd="ordered" title="Danh sách số"><i class="fas fa-list-ol"></i></button>
            <button class="btn btn-sm btn-light" data-cmd="outdent" title="Giảm thụt lề (trong danh sách)"><i class="fas fa-outdent"></i></button>
            <button class="btn btn-sm btn-light" data-cmd="indent" title="Tăng thụt lề (trong danh sách)"><i class="fas fa-indent"></i></button>
            <button class="btn btn-sm btn-light" data-cmd="clear-format" title="Xóa toàn bộ định dạng"><i class="fas fa-remove-format"></i></button>

            <div class="vr mx-2"></div>

            {{-- Gộp / Tách ô (thao tác trên vùng ô đang chọn) --}}
            <button class="btn btn-sm btn-light" data-cmd="merge-cells" title="Gộp các ô đang chọn"><i class="fas fa-object-group"></i></button>
            <button class="btn btn-sm btn-light" data-cmd="split-cell" title="Tách ô đã gộp"><i class="fas fa-object-ungroup"></i></button>

            <div class="vr mx-2"></div>

            {{-- Chèn biến số --}}
            <div class="dropdown">
                <button class="btn btn-sm btn-navy text-white px-2" data-bs-toggle="dropdown" data-toggle="dropdown"
                    title="Chèn biến số vào vị trí con trỏ">
                    <i class="fas fa-plus-circle"></i> <i class="fas fa-caret-down ms-1"></i>
                </button>
                <div class="dropdown-menu shadow border-0">
                    <a class="dropdown-item" href="javascript:void(0)" data-insert-field="text"><i class="fas fa-font me-2 text-muted"></i>Văn bản</a>
                    <a class="dropdown-item" href="javascript:void(0)" data-insert-field="number"><i class="fas fa-hashtag me-2 text-muted"></i>Số</a>
                    <a class="dropdown-item" href="javascript:void(0)" data-insert-field="date"><i class="fas fa-calendar-alt me-2 text-muted"></i>Thời gian</a>
                    <a class="dropdown-item" href="javascript:void(0)" data-insert-field="signature"><i class="fas fa-signature me-2 text-muted"></i>Chữ ký</a>
                    <a class="dropdown-item" href="javascript:void(0)" data-insert-field="checkbox"><i class="fas fa-check-square me-2 text-muted"></i>Tick chọn</a>
                    <a class="dropdown-item" href="javascript:void(0)" data-insert-field="select"><i class="fas fa-list-ul me-2 text-muted"></i>Lựa chọn</a>
                    <a class="dropdown-item" href="javascript:void(0)" data-insert-field="formula"><i class="fas fa-square-root-alt me-2 text-muted"></i>Công thức</a>
                </div>
            </div>

            <div class="ms-auto d-flex align-items-center gap-2">
                <button id="v2-btn-toggle-mode" class="btn btn-sm btn-primary text-white px-2" title="Chuyển đổi Thiết kế / Chạy thử">
                    <i class="fas fa-play"></i>
                </button>
                @if (!$isReadOnly)
                    <button id="v2-btn-save" class="btn btn-sm btn-success text-white px-2" title="Lưu lại (Không có thay đổi)">
                        <i class="fas fa-save"></i>
                    </button>
                @endif
            </div>
        </div>

        {{-- ===== Canvas: mỗi section là 1 trang riêng (tự ngắt trang) ===== --}}
        <div class="d-flex flex-column align-items-center py-4 gap-4" id="v2-pages"></div>

        {{-- ===== Panel cấu hình biến số ===== --}}
        <div id="v2-field-panel" class="v2-field-panel shadow-lg"></div>

        {{-- ===== Mục lục (TOC) bên trái ===== --}}
        <div id="v2-toc" class="v2-toc shadow-lg">
            <div class="v2-panel-head">
                <span><i class="fas fa-list-ul me-2"></i>Mục lục</span>
                <button class="btn-close-panel" id="v2-toc-close"><i class="fas fa-times"></i></button>
            </div>
            <div class="v2-panel-body" id="v2-toc-body"></div>
        </div>

        {{-- ===== Sidebar Thiết bị liên quan (kéo thả tạo bảng) ===== --}}
        <div id="v2-equipment" class="v2-toc shadow-lg">
            <div class="v2-panel-head" style="background: #198754;">
                <span><i class="fas fa-tools me-2"></i>Thiết bị liên quan</span>
                <button class="btn-close-panel" data-close-panel="v2-equipment"><i class="fas fa-times"></i></button>
            </div>
            <div class="p-2 border-bottom bg-light">
                <select id="v2-eq-dept" class="form-control form-control-sm mb-1"><option value="">-- Tất cả Phân Xưởng --</option></select>
                <input type="text" id="v2-eq-search" class="form-control form-control-sm" placeholder="Tìm thiết bị...">
                <div class="text-muted mt-1" style="font-size: 0.68rem; font-style: italic;">
                    <i class="fas fa-info-circle me-1"></i>Tick chọn thiết bị rồi kéo thả vào thanh chèn khối giữa các block.
                </div>
            </div>
            <div id="v2-eq-list" class="v2-panel-body overflow-auto" style="height: calc(100% - 150px);"></div>
        </div>

        {{-- ===== Sidebar Thành phần CO (kéo thả chèn nội dung) ===== --}}
        <div id="v2-components" class="v2-toc shadow-lg">
            <div class="v2-panel-head" style="background: #0284c7;">
                <span><i class="fas fa-cubes me-2"></i>Thành phần (CO)</span>
                <button class="btn-close-panel" data-close-panel="v2-components"><i class="fas fa-times"></i></button>
            </div>
            <div class="p-2 border-bottom bg-light">
                <input type="text" id="v2-co-search" class="form-control form-control-sm" placeholder="Tìm thành phần...">
                <div class="text-muted mt-1" style="font-size: 0.68rem; font-style: italic;">
                    <i class="fas fa-info-circle me-1"></i>Kéo thẻ thả vào thanh chèn khối giữa các block.
                </div>
            </div>
            <div id="v2-co-list" class="v2-panel-body overflow-auto" style="height: calc(100% - 120px);"></div>
        </div>

        {{-- ===== Panel Bình luận bên phải (kiểu Google Docs) ===== --}}
        <div id="v2-comments" class="v2-comments shadow-lg">
            <div class="v2-panel-head">
                <span><i class="fas fa-comment-dots me-2"></i>Bình luận</span>
                <button class="btn-close-panel" id="v2-comments-close"><i class="fas fa-times"></i></button>
            </div>
            <div class="v2-panel-body p-0 d-flex flex-column" style="height: calc(100% - 42px);">
                <div id="v2-comments-list" class="flex-grow-1 overflow-auto p-3"></div>
                <div class="p-3 border-top bg-light" id="v2-comment-composer">
                    <div class="small text-muted mb-1" id="v2-comment-target">Bình luận chung</div>
                    <textarea id="v2-comment-input" class="form-control form-control-sm" rows="2"
                        placeholder="Viết bình luận..."></textarea>
                    <button class="btn btn-sm btn-navy text-white w-100 mt-2" id="v2-comment-send">
                        <i class="fas fa-paper-plane me-1"></i> Gửi bình luận
                    </button>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* CSS tự động ẩn topNAV (main-header) ở màn hình thiết kế */
        .main-header.navbar {
            position: fixed;
            width: 100%;
            transform: translateY(-100%);
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 1050;
        }
        .main-header.navbar.designer-show {
            transform: translateY(0);
        }
        .content-wrapper { margin-top: 0 !important; padding-top: 0 !important; }

        .btn-navy { background-color: #003A4F; border-color: #003A4F; }
        .btn-navy:hover { background-color: #00506e; border-color: #00506e; }

        #v2-toolbar .btn.active { background-color: #003A4F; color: #fff; }
        #v2-toolbar .btn:disabled { opacity: 0.4; }
        #v2-toolbar .v2-fmt-select { display: inline-block; font-size: 0.78rem; padding: 2px 6px; height: 30px; }
        #v2-toolbar .v2-fmt-select:disabled { opacity: 0.4; }

        /* Nút chọn màu kiểu Google Docs: icon + vệt màu bên dưới */
        .v2-color-wrap {
            display: inline-flex; flex-direction: column; align-items: center; justify-content: center;
            width: 30px; height: 30px; border-radius: 4px; cursor: pointer; margin: 0;
            color: #475569; font-size: 0.8rem; position: relative;
        }
        .v2-color-wrap:hover { background: #f1f5f9; }
        .v2-color-wrap input[type="color"] {
            width: 18px; height: 6px; padding: 0; border: none; cursor: pointer; background: none;
        }
        .v2-color-wrap input[type="color"]::-webkit-color-swatch-wrapper { padding: 0; }
        .v2-color-wrap input[type="color"]::-webkit-color-swatch { border: none; border-radius: 1px; }

        /* Nội dung định dạng bên trong editor */
        .v2-editable u { text-decoration: underline; }
        .v2-editable a { color: #2563eb; text-decoration: underline; }
        .v2-editable mark { padding: 0 1px; }

        .v2-page {
            background: #fff;
            width: 1123px;
            max-width: 100%;
            min-height: 794px;
            padding: 48px 56px;
            border-radius: 4px;
            position: relative;
        }
        /* Số trang góc dưới phải */
        .v2-page::after {
            content: attr(data-page-label);
            position: absolute; bottom: 12px; right: 20px;
            font-size: 0.7rem; color: #94a3b8;
        }
        @media print {
            .v2-page { page-break-after: always; box-shadow: none !important; }
            #v2-toolbar, .v2-inserter, .v2-field-panel { display: none !important; }
        }

        /* Block ảo / bị khóa: không cho sửa, có huy hiệu ổ khóa khi rê chuột */
        .v2-block.v2-locked { position: relative; }
        .v2-block.v2-locked::before {
            content: '\f023  Khối hệ thống (khóa)';
            font-family: 'Font Awesome 5 Free', 'Font Awesome 6 Free', sans-serif;
            font-weight: 900; font-size: 0.62rem; color: #94a3b8;
            position: absolute; top: -14px; right: 0;
            opacity: 0; transition: opacity 0.15s;
        }
        .v2-block.v2-locked:hover::before { opacity: 1; }
        .v2-block.v2-locked .v2-editable { cursor: default; }
        .v2-block.v2-locked .v2-editable:hover { box-shadow: none; }

        /* Thanh công cụ cố định khi cuộn (JS gắn class này) */
        .v2-toolbar-fixed {
            position: fixed !important;
            top: 0; left: 0; right: 0;
            z-index: 2000;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.12) !important;
        }
        /* Ẩn topNAV khi cuộn xuống để toolbar không bị che */
        body.v2-scrolled .main-header { display: none !important; }
        body.v2-scrolled .content-wrapper { margin-top: 0 !important; }

        /* Kéo thay đổi kích thước cột/hàng bảng */
        .v2-table th, .v2-table td { position: relative; }
        .v2-col-resizer {
            position: absolute; top: 0; right: -3px; width: 6px; height: 100%;
            cursor: col-resize; z-index: 5;
        }
        .v2-col-resizer:hover, .v2-col-resizer.resizing { background: rgba(14, 165, 233, 0.45); }
        .v2-row-resizer {
            position: absolute; left: 0; bottom: -3px; width: 100%; height: 6px;
            cursor: row-resize; z-index: 5;
        }
        .v2-row-resizer:hover, .v2-row-resizer.resizing { background: rgba(14, 165, 233, 0.45); }

        /* Kéo-thả từ sidebar: inserter nổi rõ khi đang kéo */
        body.v2-dragging .v2-inserter { opacity: 1; height: 30px; }
        .v2-inserter.v2-drop-active .v2-inserter-line { border-top: 2px solid #0ea5e9; }
        .v2-inserter.v2-drop-active .v2-inserter-btns button { background: #0ea5e9; color: #fff; border-color: #0ea5e9; }

        /* Thẻ kéo trong sidebar thiết bị / thành phần */
        .v2-drag-card {
            display: flex; align-items: flex-start; gap: 8px;
            border: 1px solid #e2e8f0; border-radius: 8px;
            padding: 8px 10px; margin-bottom: 6px; background: #fff;
            cursor: grab; font-size: 0.8rem;
        }
        .v2-drag-card:hover { border-color: #0ea5e9; background: #f0f9ff; }
        .v2-drag-card .fw-bold { font-size: 0.8rem; }
        .v2-drag-card .small-muted { font-size: 0.7rem; color: #94a3b8; }

        /* Section header — đồng bộ style V1 (icon tròn + gạch gradient) */
        .v2-section { display: flex; align-items: center; margin: 26px 0 14px; }
        .v2-section-icon {
            width: 40px; height: 40px; min-width: 40px; border-radius: 50%;
            background: #0dcaf0; color: #fff;
            display: flex; align-items: center; justify-content: center;
            margin-right: 12px;
        }
        .v2-section-body { flex: 1; }
        .v2-section-title {
            font-size: 1.2rem; color: #164e63; letter-spacing: 1px;
            font-weight: 700; text-transform: uppercase;
        }
        .v2-section-line {
            height: 3px; margin-top: 4px; border-radius: 2px;
            background: linear-gradient(to right, #0ea5e9, transparent);
        }

        .v2-block { margin-bottom: 10px; }

        .v2-editable {
            min-height: 1.4em;
            border-radius: 4px;
            transition: box-shadow 0.15s;
            cursor: text;
        }
        .v2-editable:hover:not(.v2-editing) { box-shadow: inset 0 0 0 1px #cbd5e1; }
        .v2-editable.v2-editing { box-shadow: inset 0 0 0 2px #003A4F; background: #fbfdff; }
        .v2-editable .ProseMirror { outline: none; min-height: 1.4em; }
        .v2-editable p { margin-bottom: 0.35rem; }

        .v2-static-text .v2-editable { padding: 6px 8px; }

        .v2-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .v2-table th, .v2-table td {
            border: 1px solid #64748b;
            padding: 4px 6px;
            font-size: 0.85rem;
            vertical-align: top;
            word-wrap: break-word;
        }
        .v2-table th { background: #f1f5f9; font-weight: 700; text-align: center; }
        .v2-cell { min-height: 1.2em; }

        /* Thanh chèn khối mới giữa các block (hiện khi rê chuột) */
        .v2-inserter {
            position: relative; height: 14px; margin: 2px 0;
            display: flex; align-items: center; justify-content: center;
            opacity: 0; transition: opacity 0.15s;
        }
        .v2-inserter:hover { opacity: 1; height: 30px; }
        .v2-inserter-line {
            position: absolute; left: 0; right: 0; top: 50%;
            border-top: 1px dashed #94a3b8;
        }
        .v2-inserter-btns { position: relative; z-index: 1; display: flex; gap: 6px; }
        .v2-inserter-btns button {
            background: #fff; border: 1px solid #cbd5e1; border-radius: 14px;
            font-size: 0.72rem; font-weight: 600; color: #475569;
            padding: 2px 10px; cursor: pointer; transition: all 0.15s;
        }
        .v2-inserter-btns button:hover { background: #003A4F; color: #fff; border-color: #003A4F; }

        .v2-unsupported {
            border: 1px dashed #cbd5e1; border-radius: 6px;
            padding: 10px 14px; color: #94a3b8; font-size: 0.8rem; background: #f8fafc;
        }

        /* Badge biến số (cả trong editor lẫn chế độ xem tĩnh) */
        .v2-field-badge {
            display: inline-flex; align-items: center;
            background: #e0f2fe; color: #075985;
            border: 1px solid #7dd3fc; border-radius: 12px;
            padding: 1px 8px; font-size: 0.78em; font-weight: 600;
            margin: 0 2px; cursor: pointer; user-select: none;
            white-space: nowrap; vertical-align: baseline;
        }
        .v2-field-badge:hover { background: #bae6fd; }
        .v2-editing .v2-field-badge.ProseMirror-selectednode {
            outline: 2px solid #003A4F; outline-offset: 1px;
        }

        /* Trạng thái lưu */
        .v2-status { font-size: 0.75rem; font-weight: 600; }
        .v2-status--saved { color: #16a34a; }
        .v2-status--dirty { color: #d97706; }

        /* Panel cấu hình biến */
        .v2-field-panel {
            position: fixed; top: 70px; right: -340px; width: 320px;
            background: #fff; border-radius: 10px 0 0 10px;
            transition: right 0.25s ease; z-index: 1040;
            border: 1px solid #e2e8f0; border-right: none;
            display: flex; flex-direction: column;
        }
        .v2-field-panel.open { right: 0; }
        .v2-panel-head {
            background: #003A4F; color: #fff; font-weight: 700; font-size: 0.85rem;
            padding: 10px 14px; border-radius: 10px 0 0 0;
            display: flex; justify-content: space-between; align-items: center;
        }
        .v2-panel-head .btn-close-panel {
            background: none; border: none; color: #fff; cursor: pointer; padding: 0 4px;
        }
        .v2-panel-body { padding: 14px; }
        .v2-panel-body label { font-size: 0.72rem; font-weight: 700; text-transform: uppercase; color: #64748b; margin-bottom: 4px; }

        /* ===== Mục lục (TOC) — trượt từ trái ===== */
        .v2-toc {
            position: fixed; top: 70px; bottom: 20px; left: -300px; width: 280px;
            background: #fff; border-radius: 0 10px 10px 0;
            border: 1px solid #e2e8f0; border-left: none;
            transition: left 0.25s ease; z-index: 1990; overflow: hidden;
        }
        .v2-toc.open { left: 0; }
        .v2-toc .v2-panel-head { border-radius: 0 10px 0 0; }
        .v2-toc-item {
            display: flex; align-items: center; justify-content: space-between;
            padding: 7px 10px; border-radius: 6px; cursor: pointer;
            font-size: 0.82rem; font-weight: 600; color: #164e63;
        }
        .v2-toc-item:hover { background: #e0f2fe; }
        .v2-toc-item .v2-toc-page { font-size: 0.68rem; color: #94a3b8; font-weight: 400; }

        /* ===== Bình luận (kiểu Google Docs) — trượt từ phải ===== */
        .v2-comments {
            position: fixed; top: 70px; bottom: 20px; right: -360px; width: 340px;
            background: #fff; border-radius: 10px 0 0 10px;
            border: 1px solid #e2e8f0; border-right: none;
            transition: right 0.25s ease; z-index: 1990; overflow: hidden;
        }
        .v2-comments.open { right: 0; }
        .v2-comment-card {
            background: #fff; border: 1px solid #e2e8f0; border-radius: 10px;
            padding: 10px 12px; margin-bottom: 10px; box-shadow: 0 1px 4px rgba(0,0,0,0.05);
        }
        .v2-comment-card.highlight { border-color: #0ea5e9; box-shadow: 0 0 0 2px rgba(14,165,233,0.2); }
        .v2-comment-author { font-weight: 700; font-size: 0.8rem; color: #164e63; }
        .v2-comment-time { font-size: 0.68rem; color: #94a3b8; }
        .v2-comment-text { font-size: 0.82rem; margin: 4px 0; white-space: pre-wrap; }
        .v2-comment-block-ref {
            font-size: 0.68rem; color: #0369a1; background: #e0f2fe;
            border-radius: 8px; padding: 1px 8px; display: inline-block; margin-bottom: 4px;
            cursor: pointer;
        }
        .v2-comment-reply { border-left: 3px solid #e2e8f0; padding-left: 8px; margin-top: 6px; }
        .v2-comment-actions { display: flex; gap: 10px; margin-top: 4px; }
        .v2-comment-actions a { font-size: 0.72rem; color: #0369a1; cursor: pointer; text-decoration: none; }
        .v2-comment-actions a.text-danger { color: #dc3545; }

        /* Nút bình luận trên từng block (hiện khi rê chuột) */
        .v2-block { position: relative; }
        .v2-comment-btn {
            position: absolute; top: 2px; right: -34px;
            width: 28px; height: 28px; border-radius: 50%;
            border: 1px solid #e2e8f0; background: #fff; color: #64748b;
            font-size: 0.72rem; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            opacity: 0; transition: opacity 0.15s;
        }
        .v2-block:hover .v2-comment-btn { opacity: 1; }
        .v2-comment-btn:hover { background: #003A4F; color: #fff; }
        .v2-comment-btn .v2-cmt-count {
            position: absolute; top: -6px; right: -6px;
            background: #f59e0b; color: #fff; border-radius: 8px;
            font-size: 0.58rem; font-weight: 700; padding: 0 4px; min-width: 14px;
        }
        .v2-comment-btn.has-comments { opacity: 1; border-color: #f59e0b; }

        /* ===== CSS cho panel biến số (v2-field-panel) ===== */
        .v2-prop-label {
            display: block; font-size: 0.72rem; font-weight: 700;
            text-transform: uppercase; color: #64748b; margin-bottom: 4px;
        }
        .v2-prop-sublabel {
            display: block; font-size: 0.68rem; font-weight: 600;
            color: #94a3b8; margin-bottom: 3px;
        }
        .v2-formula-editor {
            min-height: 72px; height: auto; background: #fff;
            cursor: text; white-space: pre-wrap;
            overflow-wrap: break-word; word-break: break-word;
            line-height: 1.6;
        }
        .v2-formula-var {
            display: inline-block; background: #dbeafe; color: #1e40af;
            border: 1px solid #93c5fd; border-radius: 10px;
            padding: 1px 7px; font-size: 0.75em; font-weight: 700;
            margin: 0 1px; cursor: default; white-space: nowrap;
        }
        .v2fp-tabs .nav-link { border: none; border-top: 3px solid transparent; }
        .v2fp-tabs .nav-link:not(.active):hover { background: #f1f5f9 !important; }

        /* ===== CSS cho CHỌN đối tượng (Selection — kiểu Word/Excel) ===== */
        .v2-table td.v2-cell-selected {
            background-color: rgba(59, 130, 246, 0.18) !important;
            outline: 2px solid #2563eb;   /* viền xanh rõ, nổi trên border ô kề */
            outline-offset: -2px;
            z-index: 5;                    /* td đã position:relative — đè lên ô lân cận */
        }
        .v2-table td.v2-cell-selected .v2-field-badge,
        .v2-table td.v2-cell-selected .ebmr-field-badge {
            outline: 2px solid #2563eb;
            outline-offset: 1px;
            border-radius: 4px;
        }
        body.v2-cell-dragging, body.v2-cell-dragging .v2-table { user-select: none; }
        .v2-table-wrap.v2-table-selected .v2-table { outline: 2.5px solid #2563eb; outline-offset: 2px; border-radius: 2px; }

        /* Nút ⊕ chọn cả bảng (góc trên-trái, hiện khi hover — kiểu Word) */
        .v2-table-handle {
            position: absolute; top: -13px; left: -13px; width: 22px; height: 22px;
            display: flex; align-items: center; justify-content: center;
            background: #fff; border: 1px solid #cbd5e1; border-radius: 5px;
            color: #64748b; font-size: 0.62rem; cursor: pointer;
            opacity: 0; transition: opacity 0.15s; z-index: 20;
            box-shadow: 0 1px 3px rgba(0,0,0,0.15);
        }
        .v2-table-wrap:hover .v2-table-handle { opacity: 1; }
        .v2-table-handle:hover { background: #eff6ff; color: #1d4ed8; border-color: #93c5fd; }

        /* Gutter chọn HÀNG (dải trái) / CỘT (dải trên) — D-Click để chọn */
        .v2-row-gutter {
            position: absolute; left: -16px; top: 0; bottom: 0; width: 16px; z-index: 15;
            cursor: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="20" height="14" viewBox="0 0 20 14"><path d="M2 7 L14 1 L14 5 L19 5 L19 9 L14 9 L14 13 Z" transform="rotate(180 10 7)" fill="black"/></svg>') 10 7, e-resize;
        }
        .v2-col-gutter {
            position: absolute; top: -14px; left: 0; right: 0; height: 14px; z-index: 15;
            cursor: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="14" height="20" viewBox="0 0 14 20"><path d="M7 19 L1 7 L5 7 L5 1 L9 1 L9 7 L13 7 Z" fill="black"/></svg>') 7 10, s-resize;
        }
        .v2-row-gutter:hover { background: rgba(59, 130, 246, 0.08); border-radius: 3px; }
        .v2-col-gutter:hover { background: rgba(59, 130, 246, 0.08); border-radius: 3px; }

        /* Cursor mũi tên đen khi rê gần mép trái/trên của ô (chọn ô) */
        .v2-table td.v2-cur-cellsel {
            cursor: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16"><path d="M3 14 L3 2 L13 10 L8 10 L10 15 L7 16 L6 11 Z" fill="black" stroke="white" stroke-width="1"/></svg>') 3 2, cell;
        }

        /* Khung marquee quét biến số (Ctrl+Alt+kéo) */
        .v2-marquee {
            position: fixed; z-index: 999999; pointer-events: none;
            background: rgba(59, 130, 246, 0.12);
            border: 1.5px dashed #3b82f6; border-radius: 2px;
        }

        /* Biến số đang được chọn (đơn lẻ hoặc hàng loạt) — viền xanh đồng bộ */
        .v2-field-badge.v2-field-selected,
        .ebmr-field-badge.v2-field-selected {
            outline: 2px solid #2563eb !important;
            outline-offset: 1px;
            border-radius: 4px;
            box-shadow: 0 0 6px rgba(37, 99, 235, 0.5);
            background-color: rgba(59, 130, 246, 0.12);
        }

        /* ===== CSS cho Chế độ Chạy thử (Execution Mode) ===== */
        .execution-mode-active .v2-editable {
            outline: none !important;
            cursor: default !important;
        }
        .execution-mode-active .v2-field-badge {
            background-color: transparent !important;
            border: none !important;
            color: inherit !important;
            cursor: pointer;
            padding: 0 4px;
            box-shadow: none !important;
            border-bottom: 1px dashed #94a3b8 !important;
            border-radius: 0 !important;
            font-weight: 500;
        }
        .execution-mode-active .v2-field-badge:hover {
            background-color: #f8fafc !important;
            border-bottom-color: #3b82f6 !important;
        }
    </style>
@endsection

@section('script')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    {{-- Virtual blocks (header/phê duyệt/công thức...) — trích nguyên văn logic V1 --}}
    @include('pages.ebmr.designer.scripts.virtual_blocks_v2')
    <script>
        window.__V2__ = {
            templateId: {{ $template->id }},
            items: @json($template->schema->fields ?? []),
            fieldsConfig: @json($template->schema->fieldsConfig ?? (object) []),
            isReadOnly: @json($isReadOnly),
            isExecutionMode: false,
            executionValues: {},
            pageOrientation: @json($template->schema->pageOrientation ?? 'portrait'),
            saveUrl: "{{ route('pages.ebmr.storeTemplate') }}",
            csrf: "{{ csrf_token() }}",
            comments: @json($comments ?? []),
            commentUrls: {
                store: "{{ route('pages.ebmr.storeComment') }}",
                reply: "{{ route('pages.ebmr.replyComment') }}",
                remove: "{{ route('pages.ebmr.deleteComment') }}",
            },
            importantVars: @json($importantVars ?? []),
            currentUserName: @json(session('user')['fullName'] ?? ''),
            templateDepartmentCode: @json($template->department_code ?? ''),
            urls: {
                equipmentList: "{{ route('pages.ebmr.designerEquipmentList') }}",
                templates: "{{ route('pages.ebmr.getTemplates') }}",
                templateBlocksBase: "{{ url('/ebmr/templates') }}", // + /{id}/blocks
                docViewBase: "{{ route('pages.ebmr.viewDocumentByCode', ['code' => '__CODE__']) }}",
            },
        };

        // Logic ẩn/hiện thanh topNAV ở chế độ thiết kế
        document.addEventListener('DOMContentLoaded', () => {
            const header = document.querySelector('.main-header.navbar');
            if (header) {
                document.addEventListener('mousemove', (e) => {
                    // Hiện khi chuột di chuyển lên 30px phía trên cùng màn hình, 
                    // hoặc nếu chuột vẫn nằm trong phạm vi của topNAV đang hiển thị
                    if (e.clientY < 30 || header.contains(e.target)) {
                        header.classList.add('designer-show');
                    } else {
                        const rect = header.getBoundingClientRect();
                        // Nếu chuột kéo ra khỏi phạm vi Y của header, thu hồi nó
                        if (e.clientY > rect.bottom) {
                            header.classList.remove('designer-show');
                        }
                    }
                });
            }
        });
    </script>
    @vite('resources/js/designer-v2/main.js')
@endsection
