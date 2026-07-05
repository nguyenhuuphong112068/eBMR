{{-- ===================================================================
   eBMR Designer Toolbar — Dạng nhóm chức năng (Ribbon style)
   Nhóm: Lịch Sử | Định Dạng | Chèn | Biến Số | Hồ Sơ
=================================================================== --}}

<style>
    /* Toolbar group layout */
    .toolbar-group {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 0 3px;
    }
    .toolbar-group-btns {
        display: flex;
        align-items: center;
        gap: 2px;
        flex-wrap: nowrap;
    }
    .toolbar-group-label {
        font-size: 9px;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-top: 3px;
        white-space: nowrap;
        line-height: 1;
        user-select: none;
    }
    .toolbar-group-sep {
        width: 1px;
        background: #e2e8f0;
        align-self: stretch;
        margin: 0 5px;
        min-height: 36px;
    }
    .toolbar-mini-sep {
        display: inline-block;
        width: 1px;
        height: 18px;
        background: #e2e8f0;
        margin: 0 3px;
        vertical-align: middle;
    }
</style>

<div class="editor-toolbar shadow-sm flex-column px-3 py-2 bg-white {{ $isReadOnly ? 'd-none' : 'd-flex' }}">

    {{-- ================================================================
         ROW 1 — Hành động chính
    ================================================================ --}}
    <div class="d-flex align-items-end w-100">

        {{-- ── Nhóm: Lịch Sử ── --}}
        <div class="toolbar-group">
            <div class="toolbar-group-btns">
                <button class="btn btn-toolbar" onclick="undo()" title="Hoàn tác (Ctrl+Z)" {{ $isReadOnly ? 'disabled' : '' }}>
                    <i class="fas fa-undo"></i>
                </button>
                <button class="btn btn-toolbar" onclick="redo()" title="Làm lại (Ctrl+Y)" {{ $isReadOnly ? 'disabled' : '' }}>
                    <i class="fas fa-redo"></i>
                </button>
            </div>
            <div class="toolbar-group-label">Lịch Sử</div>
        </div>
        <div class="toolbar-group-sep"></div>

        {{-- ── Nhóm: Định Dạng ── --}}
        <div class="toolbar-group">
            <div class="toolbar-group-btns">
                {{-- Format Painter & Clear Format --}}
                <button class="btn btn-toolbar" id="btn-format-painter" onclick="toggleFormatPainter()"
                    title="Sao chép định dạng" {{ $isReadOnly ? 'disabled' : '' }}>
                    <i class="fas fa-paint-roller"></i>
                </button>
                <button class="btn btn-toolbar" onclick="clearFormatting()"
                    title="Xóa định dạng" {{ $isReadOnly ? 'disabled' : '' }}>
                    <i class="fas fa-remove-format"></i>
                </button>

                <span class="toolbar-mini-sep"></span>

                {{-- Clipboard: Copy / Cut / Paste khối --}}
                <button class="btn btn-toolbar text-primary" onclick="copyBlock()"
                    title="Sao chép Khối (Ctrl+C)" {{ $isReadOnly ? 'disabled' : '' }}>
                    <i class="fas fa-copy"></i>
                </button>
                <button class="btn btn-toolbar text-warning" onclick="cutBlock()"
                    title="Cắt Khối (Ctrl+X)" {{ $isReadOnly ? 'disabled' : '' }}>
                    <i class="fas fa-cut"></i>
                </button>
                <button class="btn btn-toolbar text-success" onclick="pasteBlock()"
                    title="Dán Khối (Ctrl+V)" {{ $isReadOnly ? 'disabled' : '' }}>
                    <i class="fas fa-paste"></i>
                </button>

                <span class="toolbar-mini-sep"></span>

                {{-- Bảng --}}
                <div class="dropdown d-inline-block">
                    <button class="btn btn-toolbar-action dropdown-toggle" type="button"
                        id="tableDropdown" data-toggle="dropdown" aria-expanded="false" title="Chèn Bảng">
                        <i class="fas fa-table"></i>
                    </button>
                    <div class="dropdown-menu table-selector-dropdown p-3" aria-labelledby="tableDropdown">
                        <div class="grid-container" id="table-grid"></div>
                        <div class="grid-label" id="grid-selection-label">1 x 1 Table</div>
                    </div>
                </div>
                <button class="btn btn-toolbar-action" onclick="mergeSelectedCells()" title="Gộp ô">
                    <i class="fas fa-object-group"></i>
                </button>
                <button class="btn btn-toolbar-action" onclick="openSplitModal()" title="Tách ô">
                    <i class="fas fa-columns"></i>
                </button>
            </div>
            <div class="toolbar-group-label">Định Dạng</div>
        </div>
        <div class="toolbar-group-sep"></div>

        {{-- ── Nhóm: Chèn ── --}}
        <div class="toolbar-group">
            <div class="toolbar-group-btns">
                {{-- Các khối nội dung --}}
                <button class="btn btn-toolbar-action" onmousedown="event.preventDefault();"
                    onclick="addItem('static-text')" title="Chèn Mô tả">
                    <i class="fas fa-paragraph"></i>
                </button>
                <button class="btn btn-toolbar-action" onmousedown="event.preventDefault();"
                    onclick="openLinkGfModal()" title="BM Chung">
                    <i class="fas fa-link"></i>
                </button>
                <button class="btn btn-toolbar-action text-danger fw-bold" onmousedown="event.preventDefault();"
                    onclick="$('#weightChartCreatorModal').modal('show')" title="Bảng KT Khối lượng Trung bình">
                    <i class="fas fa-balance-scale"></i>
                </button>
                @if(!isset($template) || !in_array($template->type, ['MF', 'GF']))
                <button class="btn btn-toolbar-action text-info fw-bold" onmousedown="event.preventDefault();"
                    onclick="addSection()" title="Thêm Phân đoạn">
                    <i class="fas fa-layer-group"></i>
                </button>
                @endif
                <button class="btn btn-toolbar-action text-secondary fw-bold" onmousedown="event.preventDefault();"
                    onclick="addItem('page-break')" title="Chèn Ngắt trang (Page Break) — Phân tách bước/trang">
                    <i class="fas fa-cut"></i>
                </button>
                <button class="btn btn-toolbar-action text-success fw-bold" onmousedown="event.preventDefault();"
                    onclick="openMasterFormModal()" title="Nhập từ Biểu mẫu gốc (MF)">
                    <i class="fas fa-file-import"></i>
                </button>
                <button class="btn btn-toolbar-action text-info fw-bold" onmousedown="event.preventDefault();"
                    onclick="toggleEquipmentSidebar()" title="Thêm danh sách thiết bị liên quan">
                    <i class="fas fa-tools"></i>
                </button>
                <button class="btn btn-toolbar-action text-info fw-bold" onmousedown="event.preventDefault();"
                    onclick="toggleComponentSidebar()" title="Chèn Thành phần (CO)">
                    <i class="fas fa-layer-group"></i>
                </button>
                <button class="btn btn-toolbar-action text-warning fw-bold" onmousedown="event.preventDefault();"
                    onclick="openBlockLoopModal()" title="Cài đặt Lặp nhóm khối">
                    <i class="fas fa-redo"></i>
                </button>

                <span class="toolbar-mini-sep"></span>

                {{-- Nhập Word --}}
                <button class="btn btn-toolbar-action" onclick="document.getElementById('wordImporter').click()"
                    title="Nhập từ Word (.doc, .docx)">
                    <i class="fas fa-file-word text-primary"></i>
                </button>
                <input type="file" id="wordImporter" class="d-none" accept=".doc, .docx" onchange="importWordFile(this)">

                <span class="toolbar-mini-sep"></span>

                {{-- Ký hiệu, PDF, Hủy link --}}
                <button class="btn btn-toolbar-action text-muted" onmousedown="event.preventDefault();"
                    onclick="openSymbolModal()" title="Ký hiệu đặc biệt">
                    <i class="fas fa-omega"></i>
                </button>
                <button class="btn btn-toolbar-action text-danger fw-bold" onmousedown="event.preventDefault();"
                    onclick="insertDocumentNetworkLink()" title="Liên kết tài liệu mạng (PDF)">
                    <i class="fas fa-file-pdf"></i>
                </button>
                <button class="btn btn-toolbar-action text-secondary" onmousedown="event.preventDefault();"
                    onclick="removeDocumentNetworkLink()" title="Hủy liên kết tài liệu mạng">
                    <i class="fas fa-unlink"></i>
                </button>
            </div>
            <div class="toolbar-group-label">Chèn</div>
        </div>
        <div class="toolbar-group-sep"></div>

        {{-- ── Nhóm: Biến Số ── --}}
        <div class="toolbar-group">
            <div class="toolbar-group-btns">
                {{-- Insert variable dropdown --}}
                <div class="btn-group">
                    <button class="btn btn-toolbar-action text-primary fw-bold" onmousedown="event.preventDefault();"
                        onclick="insertDynamicField()" title="Chèn Biến số">
                        <i class="fas fa-keyboard"></i>
                    </button>
                    <button type="button" class="btn btn-toolbar-action text-primary dropdown-toggle dropdown-toggle-split"
                        data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <span class="sr-only">Toggle Dropdown</span>
                    </button>
                    <div class="dropdown-menu shadow-lg p-2" style="border-radius: 12px; min-width: 190px;">
                        <button class="dropdown-item rounded mb-1" onclick="insertDynamicField('text')">✒️ Nhập Văn bản</button>
                        <button class="dropdown-item rounded mb-1" onclick="insertDynamicField('number')">🔢 Nhập Số</button>
                        <button class="dropdown-item rounded mb-1" onclick="insertDynamicField('date')">📅 Nhập Ngày</button>
                        <button class="dropdown-item rounded mb-1" onclick="insertDynamicField('signature')">✍️ Nhập Chữ ký</button>
                        <button class="dropdown-item rounded mb-1" onclick="insertDynamicField('checkbox')">☑️ Nhập Tick</button>
                        <button class="dropdown-item rounded mb-1" onclick="insertDynamicField('select')">🔘 Nhập Lựa chọn</button>
                        <button class="dropdown-item rounded mb-1" onclick="insertDynamicField('formula')">🧮 Thiết lập Công thức</button>
                    </div>
                </div>
                {{-- Paste variable --}}
                <button class="btn btn-toolbar-action text-primary" onmousedown="event.preventDefault();"
                    onclick="pasteVariable()" title="Dán Biến số (Ctrl+V)">
                    <i class="fas fa-paste"></i>
                </button>
                {{-- Link criteria --}}
                <button class="btn btn-toolbar-action text-success fw-bold" onmousedown="event.preventDefault();"
                    onclick="toggleCriteriaSidebar()" title="Liên kết Tiêu chuẩn (Data-Binding)">
                    <i class="fas fa-vial"></i>
                </button>
                {{-- Variable summary --}}
                <button class="btn btn-toolbar-action text-primary" onmousedown="event.preventDefault();"
                    onclick="openVariableSummaryModal()" title="Danh sách biến số đã cài đặt">
                    <i class="fas fa-tasks"></i>
                </button>
            </div>
            <div class="toolbar-group-label">Biến Số</div>
        </div>
        <div class="toolbar-group-sep"></div>

        {{-- ── Nhóm: Hồ Sơ ── --}}
        <div class="toolbar-group">
            <div class="toolbar-group-btns">
                <button class="btn btn-toolbar" onclick="openTemplateModal()" title="Mở hồ sơ">
                    <i class="fas fa-folder-open"></i>
                </button>
                <button class="btn btn-toolbar" onclick="openHistoryModal()" title="Lịch sử thay đổi">
                    <i class="fas fa-history"></i>
                </button>
                <button class="btn btn-toolbar" onclick="window.print()" title="In hồ sơ">
                    <i class="fas fa-print"></i>
                </button>
                <button class="btn btn-toolbar-action text-info fw-bold" onmousedown="event.preventDefault();"
                    onclick="togglePropertiesSidebar()" title="Thuộc tính tài liệu (Document Properties)">
                    <i class="fas fa-file-alt"></i>
                </button>
                <button class="btn btn-toolbar-action text-primary fw-bold" onmousedown="event.preventDefault();"
                    onclick="toggleOutlineVisibility()" title="Hiển thị / Ẩn Mục lục">
                    <i class="fas fa-list-ul"></i>
                </button>
            </div>
            <div class="toolbar-group-label">Hồ Sơ</div>
        </div>

        {{-- ── Phải: Mode / View / Save ── --}}
        <div class="ms-auto d-flex gap-2 align-items-center pb-1">
            {{-- Thử trình soạn thảo mới (Pilot ProseMirror/TipTap) --}}
            @if (isset($template) && $template->id)
                <a href="{{ route('pages.ebmr.designer', $template->id) }}?editor=v2"
                    class="btn btn-sm btn-outline-warning px-3 fw-bold" style="border-radius: 20px;"
                    title="Mở bản thử nghiệm trình soạn thảo mới (engine ProseMirror/TipTap). Dữ liệu dùng chung — lưu ở đâu cũng mở được ở cả hai bản.">
                    <i class="fas fa-flask me-1"></i> Editor V2 (Beta)
                </a>
            @endif

            {{-- N/A Zone (ẩn mặc định, hiện khi execution mode) --}}
            <button class="btn btn-sm btn-outline-danger px-3 d-none fw-bold" id="btn-na-zone"
                onclick="markSelectedZoneAsNA()" style="border-radius: 20px;"
                title="Đánh dấu Không áp dụng (N/A) cho vùng đang chọn">
                <i class="fas fa-ban me-1"></i> N/A Vùng Chọn (Z)
            </button>

            {{-- Extract Data Tree Button (ẩn mặc định, hiện khi execution mode) --}}
            {{-- <button class="btn btn-sm btn-outline-info px-3 d-none fw-bold" id="btn-extract-data-tree"
                onclick="extractDataTree()" style="border-radius: 20px;"
                title="Trích xuất Cây Dữ Liệu Ngữ Cảnh">
                <i class="fas fa-sitemap me-1"></i> Xem Cây Dữ Liệu
            </button> --}}

            {{-- Thu phóng trang (giống Google Docs) --}}
            <div class="d-flex align-items-center gap-1 zoom-control" title="Thu phóng trang">
                <button class="btn btn-toolbar-action p-0 d-flex align-items-center justify-content-center"
                    style="width:28px; height:28px; border-radius:4px;" onclick="changeZoomLevel(-10)"
                    title="Thu nhỏ">
                    <i class="fas fa-search-minus" style="font-size:0.75rem;"></i>
                </button>
                <div class="dropdown d-inline-block" title="Mức thu phóng">
                    <button class="btn btn-toolbar dropdown-toggle px-2 fw-bold" type="button"
                        data-toggle="dropdown" aria-expanded="false" style="min-width:58px;">
                        <span id="zoomDisplay">100%</span>
                    </button>
                    <div class="dropdown-menu shadow-lg p-2" style="min-width:150px;">
                        <button class="dropdown-item rounded mb-1" onclick="setZoomLevel(50)">50%</button>
                        <button class="dropdown-item rounded mb-1" onclick="setZoomLevel(75)">75%</button>
                        <button class="dropdown-item rounded mb-1" onclick="setZoomLevel(90)">90%</button>
                        <button class="dropdown-item rounded mb-1" onclick="setZoomLevel(100)">100%</button>
                        <button class="dropdown-item rounded mb-1" onclick="setZoomLevel(125)">125%</button>
                        <button class="dropdown-item rounded mb-1" onclick="setZoomLevel(150)">150%</button>
                        <button class="dropdown-item rounded mb-1" onclick="setZoomLevel(200)">200%</button>
                        <hr class="my-1">
                        <button class="dropdown-item rounded fw-bold text-primary" onclick="zoomToFit()">
                            <i class="fas fa-arrows-alt-h me-1"></i> Vừa khung hình
                        </button>
                    </div>
                </div>
                <button class="btn btn-toolbar-action p-0 d-flex align-items-center justify-content-center"
                    style="width:28px; height:28px; border-radius:4px;" onclick="changeZoomLevel(10)"
                    title="Phóng to">
                    <i class="fas fa-search-plus" style="font-size:0.75rem;"></i>
                </button>
            </div>

            {{-- Designer / Execution mode toggle --}}
            <button type="button" id="btn-mode-toggle"
                class="btn btn-sm px-3 {{ empty($isExecutionMode) ? 'btn-primary' : 'btn-success' }}"
                onclick="setDesignerMode(!window.isExecutionMode)" style="border-radius: 20px;"
                title="Chuyển chế độ (Ctrl + E)">
                <i class="fas {{ empty($isExecutionMode) ? 'fa-edit' : 'fa-play' }} me-1"></i>
                <span id="mode-text">{{ empty($isExecutionMode) ? 'Thiết kế' : 'Chạy thử' }}</span>
            </button>

            {{-- View mode toggle --}}
            <button id="viewModeToggle"
                class="btn {{ empty($activeSectionId) ? 'btn-info' : 'btn-outline-info' }} px-3"
                onclick="toggleViewMode()" style="border-radius: 20px;" title="Thay đổi chế độ xem">
                @if (empty($activeSectionId))
                    <i class="fas fa-th-list"></i>
                @else
                    <i class="fas fa-expand-arrows-alt"></i>
                @endif
            </button>

            {{-- Save --}}
            <button class="btn btn-navy px-3" onclick="saveTemplate()" style="border-radius: 20px;"
                {{ $isReadOnly ? 'disabled' : '' }} title="Lưu hồ sơ mẫu">
                <i class="fas fa-cloud-upload-alt"></i>
            </button>
        </div>
    </div>{{-- End Row 1 --}}

    {{-- ================================================================
         ROW 2 — Định dạng văn bản
    ================================================================ --}}
    <div class="d-flex align-items-end w-100 mt-1 pt-2 border-top {{ $isReadOnly ? 'd-none' : '' }}">

        {{-- ── Nhóm: Kiểu & Cỡ ── --}}
        <div class="toolbar-group">
            <div class="toolbar-group-btns">
                <select class="form-select form-select-sm" style="width: 140px; font-size: 0.8rem;"
                    onchange="formatDoc('formatBlock', this.value); this.selectedIndex=0;"
                    title="Định dạng Tiêu đề / Thẻ">
                    <option value="">Kiểu tài liệu...</option>
                    <option value="H1">Tiêu đề cấp 1 (16pt)</option>
                    <option value="H2">Tiêu đề cấp 2 (15pt)</option>
                    <option value="H3">Tiêu đề cấp 3 (14pt)</option>
                    <option value="H4">Tiêu đề cấp 4 (14pt)</option>
                    <option value="P">Đoạn văn (14pt)</option>
                </select>
                <span class="toolbar-mini-sep"></span>
                <button class="btn btn-toolbar-action p-0 d-flex align-items-center justify-content-center"
                    style="width:24px; height:24px; border-radius:4px;" onclick="changeFontSize(-1)"
                    title="Giảm cỡ chữ">
                    <i class="fas fa-minus" style="font-size:0.7rem;"></i>
                </button>
                <div class="dropdown d-inline-block" title="Cỡ chữ">
                    <button class="btn btn-toolbar dropdown-toggle px-2 fw-bold" type="button"
                        data-toggle="dropdown" aria-expanded="false" style="min-width:45px;" onmousedown="saveCurrentSelection();">
                        <span id="fontSizeDisplay">16</span>
                    </button>
                    <div class="dropdown-menu shadow-lg p-2" style="min-width:80px; max-height:300px; overflow-y:auto;">
                        <button class="dropdown-item rounded mb-1" onclick="applyCustomFontSize(10)">10</button>
                        <button class="dropdown-item rounded mb-1" onclick="applyCustomFontSize(12)">12</button>
                        <button class="dropdown-item rounded mb-1" onclick="applyCustomFontSize(14)">14</button>
                        <button class="dropdown-item rounded mb-1" onclick="applyCustomFontSize(16)">16</button>
                        <button class="dropdown-item rounded mb-1" onclick="applyCustomFontSize(18)">18</button>
                        <button class="dropdown-item rounded mb-1" onclick="applyCustomFontSize(20)">20</button>
                        <button class="dropdown-item rounded mb-1" onclick="applyCustomFontSize(24)">24</button>
                        <button class="dropdown-item rounded mb-1" onclick="applyCustomFontSize(28)">28</button>
                        <button class="dropdown-item rounded mb-1" onclick="applyCustomFontSize(32)">32</button>
                        <button class="dropdown-item rounded mb-1" onclick="applyCustomFontSize(36)">36</button>
                        <button class="dropdown-item rounded mb-1" onclick="applyCustomFontSize(48)">48</button>
                    </div>
                </div>
                <button class="btn btn-toolbar-action p-0 d-flex align-items-center justify-content-center"
                    style="width:24px; height:24px; border-radius:4px;" onclick="changeFontSize(1)"
                    title="Tăng cỡ chữ">
                    <i class="fas fa-plus" style="font-size:0.7rem;"></i>
                </button>
            </div>
            <div class="toolbar-group-label">Kiểu & Cỡ</div>
        </div>
        <div class="toolbar-group-sep"></div>

        {{-- ── Nhóm: Định Dạng Chữ ── --}}
        <div class="toolbar-group">
            <div class="toolbar-group-btns">
                <button class="btn btn-toolbar" onmousedown="event.preventDefault();" onclick="formatDoc('bold')" title="In đậm (Ctrl+B)">
                    <i class="fas fa-bold"></i>
                </button>
                <button class="btn btn-toolbar" onmousedown="event.preventDefault();" onclick="formatDoc('italic')" title="In nghiêng (Ctrl+I)">
                    <i class="fas fa-italic"></i>
                </button>
                <button class="btn btn-toolbar" onmousedown="event.preventDefault();" onclick="formatDoc('underline')" title="Gạch chân (Ctrl+U)">
                    <i class="fas fa-underline"></i>
                </button>
                <button class="btn btn-toolbar" onmousedown="event.preventDefault();" onclick="formatDoc('strikethrough')" title="Gạch ngang">
                    <i class="fas fa-strikethrough"></i>
                </button>
                <button class="btn btn-toolbar" onmousedown="event.preventDefault();" onclick="formatDoc('superscript')" title="Chỉ số trên">
                    <i class="fas fa-superscript"></i>
                </button>
                <button class="btn btn-toolbar" onmousedown="event.preventDefault();" onclick="formatDoc('subscript')" title="Chỉ số dưới">
                    <i class="fas fa-subscript"></i>
                </button>

                {{-- Change Case dropdown --}}
                <div class="dropdown d-inline-block" title="Thay đổi kiểu chữ (Change case)">
                    <button class="btn btn-toolbar dropdown-toggle px-2" type="button"
                        id="changeCaseDropdown" data-toggle="dropdown" aria-expanded="false" onmousedown="saveCurrentSelection();">
                        <span class="fw-bold">Aa</span>
                    </button>
                    <div class="dropdown-menu shadow-lg p-2" aria-labelledby="changeCaseDropdown"
                        style="min-width:180px; z-index:2000;">
                        <button class="dropdown-item rounded mb-1" onclick="applyTextChangeCase('sentence')">Sentence case.</button>
                        <button class="dropdown-item rounded mb-1" onclick="applyTextChangeCase('lower')">lowercase</button>
                        <button class="dropdown-item rounded mb-1" onclick="applyTextChangeCase('upper')">UPPERCASE</button>
                        <button class="dropdown-item rounded mb-1" onclick="applyTextChangeCase('title')">Capitalize Each Word</button>
                        <button class="dropdown-item rounded mb-1" onclick="applyTextChangeCase('toggle')">tOGGLE cASE</button>
                    </div>
                </div>

                {{-- Typography Normalization --}}
                <button class="btn btn-toolbar text-primary" onmousedown="event.preventDefault();" onclick="openTypographySettings()" title="Chuẩn hóa cỡ chữ (Typography)">
                    <i class="fas fa-text-height"></i>
                </button>

                {{-- Text Color (dual action: click apply / dropdown choose) (TẠM ẨN THEO YÊU CẦU) --}}
                <div class="btn-group d-none" role="group">
                    <button type="button" class="btn btn-toolbar px-2" onclick="applyCurrentTextColor()"
                        title="Tô màu chữ (Màu hiện tại)" onmousedown="event.preventDefault()">
                        <div style="position:relative;">
                            <i class="fas fa-font text-dark"></i>
                            <div id="textColorIndicator"
                                style="position:absolute; bottom:-4px; left:0; width:100%; height:3px; background:#ff0000; border-radius:2px;"></div>
                        </div>
                    </button>
                    <button type="button" class="btn btn-light btn-sm dropdown-toggle dropdown-toggle-split"
                        data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Chọn màu chữ" onmousedown="saveCurrentSelection();">
                        <span class="sr-only">Toggle Dropdown</span>
                    </button>
                    <div class="dropdown-menu p-2 shadow-lg" style="min-width:250px; z-index:2000;"
                        onclick="event.stopPropagation()">
                        <div class="small fw-bold text-muted mb-2">Bảng màu chủ đề</div>
                        <div id="text-color-palette-container"></div>
                        <script>
                            (function() {
                                const initPalette = () => {
                                    const container = document.getElementById('text-color-palette-container');
                                    if (container && typeof getThemeColorsHTML === 'function') {
                                        container.innerHTML = getThemeColorsHTML('updateTextColorPickerWrapper');
                                        return true;
                                    }
                                    return false;
                                };
                                if (!initPalette()) {
                                    document.addEventListener('DOMContentLoaded', initPalette);
                                    setTimeout(initPalette, 500);
                                }
                            })();
                            window.updateTextColorPickerWrapper = function(color) {
                                updateTextColorPicker(color);
                                if (window.jQuery) $('.dropdown-menu.show').parent().find('.dropdown-toggle').dropdown('toggle');
                            };
                        </script>
                        <hr class="my-2">
                        <div class="d-flex align-items-center gap-2">
                            <input type="color" class="form-control form-control-color p-1"
                                style="height:30px; width:40px;" id="customTextColor" value="#ff0000"
                                onchange="updateTextColorPickerWrapper(this.value)">
                            <label class="small text-muted mb-0" for="customTextColor">Màu tuỳ chỉnh...</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="toolbar-group-label">Định Dạng</div>
        </div>
        <div class="toolbar-group-sep"></div>

        {{-- ── Nhóm: Căn Lề ── --}}
        <div class="toolbar-group">
            <div class="toolbar-group-btns">
                <button class="btn btn-toolbar" onmousedown="event.preventDefault();" onclick="formatDoc('justifyLeft')" title="Canh trái">
                    <i class="fas fa-align-left"></i>
                </button>
                <button class="btn btn-toolbar" onmousedown="event.preventDefault();" onclick="formatDoc('justifyCenter')" title="Canh giữa">
                    <i class="fas fa-align-center"></i>
                </button>
                <button class="btn btn-toolbar" onmousedown="event.preventDefault();" onclick="formatDoc('justifyRight')" title="Canh phải">
                    <i class="fas fa-align-right"></i>
                </button>
                <button class="btn btn-toolbar" onmousedown="event.preventDefault();" onclick="formatDoc('justifyFull')" title="Canh đều">
                    <i class="fas fa-align-justify"></i>
                </button>
                {{-- Text Direction dropdown --}}
                <div class="dropdown d-inline-block" title="Hướng chữ">
                    <button class="btn btn-toolbar dropdown-toggle" type="button"
                        id="textDirectionDropdown" data-toggle="dropdown" aria-expanded="false" onmousedown="saveCurrentSelection();">
                        <i class="fas fa-font"></i>
                        <i class="fas fa-arrows-alt-v" style="font-size:0.6rem; margin-left:2px;"></i>
                    </button>
                    <div class="dropdown-menu p-2 shadow-lg" aria-labelledby="textDirectionDropdown"
                        style="min-width:200px;">
                        <button class="dropdown-item rounded mb-1" onclick="changeTextDirection('horizontal')">
                            <i class="fas fa-align-left me-2"></i> Ngang (Mặc định)
                        </button>
                        <button class="dropdown-item rounded mb-1" onclick="changeTextDirection('vertical-down')">
                            <i class="fas fa-long-arrow-alt-down me-2"></i> Xoay dọc xuống (90°)
                        </button>
                        <button class="dropdown-item rounded mb-1" onclick="changeTextDirection('vertical-up')">
                            <i class="fas fa-long-arrow-alt-up me-2"></i> Xoay dọc lên (270°)
                        </button>
                    </div>
                </div>
            </div>
            <div class="toolbar-group-label">Căn Lề</div>
        </div>
        <div class="toolbar-group-sep"></div>

        {{-- ── Nhóm: Danh Sách & Ghi Chú ── --}}
        <div class="toolbar-group">
            <div class="toolbar-group-btns">
                {{-- Bullet List dropdown --}}
                <div class="dropdown d-inline-block" title="Danh sách gạch đầu dòng">
                    <button class="btn btn-toolbar dropdown-toggle px-2" type="button"
                        id="bulletDropdown" data-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-list-ul"></i>
                    </button>
                    <div class="dropdown-menu p-2 shadow-lg" aria-labelledby="bulletDropdown"
                        style="min-width:260px; z-index:2000;" onclick="event.stopPropagation()">
                        <div class="small fw-bold text-muted mb-2 px-1">Ký tự đầu dòng</div>
                        <div class="d-flex flex-wrap gap-1 mb-2">
                            <button class="btn btn-sm btn-outline-secondary" style="min-width:44px;font-size:1rem;"
                                onclick="applyLineBullet('none')" title="Không có">
                                <span class="text-muted small">None</span>
                            </button>
                            <button class="btn btn-sm btn-outline-secondary" style="min-width:44px;font-size:1rem;"
                                onclick="applyLineBullet('•')" title="Chấm tròn đặc">•</button>
                            <button class="btn btn-sm btn-outline-secondary" style="min-width:44px;font-size:1rem;"
                                onclick="applyLineBullet('○')" title="Chấm tròn rỗng">○</button>
                            <button class="btn btn-sm btn-outline-secondary" style="min-width:44px;font-size:1rem;"
                                onclick="applyLineBullet('■')" title="Hình vuông đặc">■</button>
                            <button class="btn btn-sm btn-outline-secondary" style="min-width:44px;font-size:1rem;"
                                onclick="applyLineBullet('□')" title="Hình vuông rỗng">□</button>
                            <button class="btn btn-sm btn-outline-secondary" style="min-width:44px;font-size:1rem;"
                                onclick="applyLineBullet('➤')" title="Mũi tên">➤</button>
                            <button class="btn btn-sm btn-outline-secondary" style="min-width:44px;font-size:1rem;"
                                onclick="applyLineBullet('✓')" title="Dấu tick">✓</button>
                            <button class="btn btn-sm btn-outline-secondary" style="min-width:44px;font-size:1rem;"
                                onclick="applyLineBullet('✗')" title="Dấu X">✗</button>
                            <button class="btn btn-sm btn-outline-secondary" style="min-width:44px;font-size:1rem;"
                                onclick="applyLineBullet('◆')" title="Hình thoi">◆</button>
                            <button class="btn btn-sm btn-outline-secondary" style="min-width:44px;font-size:1rem;"
                                onclick="applyLineBullet('–')" title="Gạch ngang">–</button>
                        </div>
                        <hr class="my-1">
                        <div class="small fw-bold text-muted mb-1 px-1">Danh sách có số</div>
                        <button class="dropdown-item rounded" onclick="applyLineBullet('ordered')">
                            <i class="fas fa-list-ol me-2"></i>1. 2. 3. &nbsp;
                            <span class="text-muted small">(tự đánh số)</span>
                        </button>
                    </div>
                </div>
                <button class="btn btn-toolbar" onmousedown="event.preventDefault();"
                    onclick="addNote()" title="Thêm ghi chú">
                    <i class="fas fa-sticky-note text-warning"></i>
                </button>
                <button class="btn btn-toolbar" onclick="addComment()" title="Thêm bình luận">
                    <i class="far fa-comment-dots"></i>
                </button>
                <button class="btn btn-toolbar text-danger" id="btn-toggle-comments"
                    onclick="toggleCommentsVisibility()" title="Hiện/Ẩn bình luận trong văn bản">
                    <i class="fas fa-eye-slash"></i>
                </button>
                <button class="btn btn-toolbar" id="btn-split-workspace"
                    onclick="toggleWorkspaceSplit()" title="Chia đôi màn hình (Split View)">
                    <i class="fas fa-columns fa-rotate-90"></i>
                </button>
            </div>
            <div class="toolbar-group-label">Danh Sách & Ghi Chú</div>
        </div>
        <div class="toolbar-group-sep"></div>

        {{-- ── Nhóm: Ký Hiệu & Ảnh ── --}}
        <div class="toolbar-group">
            <div class="toolbar-group-btns">
                {{-- Quick symbol insert dropdown --}}
                <div class="dropdown d-inline-block">
                    <button class="btn btn-toolbar dropdown-toggle" type="button"
                        data-toggle="dropdown" aria-expanded="false" title="Ký tự nhanh (Ω, α, β...)">
                        <span style="font-family: 'Times New Roman', Times, serif; font-weight: bold; font-size: 1.1em; line-height: 1;">&Omega;</span>
                    </button>
                    <div class="dropdown-menu p-2 shadow" style="min-width: 280px;">
                        <div class="small fw-bold text-muted mb-1 px-1">Toán & Khoa học</div>
                        <div class="d-flex flex-wrap mb-2">
                            @foreach(['±', '≠', '≤', '≥', '÷', '×', '∞', '√', '∫', '≈', '≡', '°', '℃', '℉', '‰', '¼', '½', '¾'] as $sym)
                                <button class="btn btn-light border m-0" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center; font-size: 14px; margin-right: 2px !important; margin-bottom: 2px !important;" onmousedown="event.preventDefault();" onclick="formatDoc('insertText', '{{ $sym }}')">{{ $sym }}</button>
                            @endforeach
                        </div>
                        
                        <div class="small fw-bold text-muted mb-1 px-1">Ký tự Hy Lạp</div>
                        <div class="d-flex flex-wrap mb-2">
                            @foreach(['α', 'β', 'γ', 'Δ', 'Ω', 'Σ', 'Φ', 'π', 'θ', 'μ', 'λ', 'ρ', 'σ', 'ω'] as $sym)
                                <button class="btn btn-light border m-0" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center; font-size: 14px; margin-right: 2px !important; margin-bottom: 2px !important;" onmousedown="event.preventDefault();" onclick="formatDoc('insertText', '{{ $sym }}')">{{ $sym }}</button>
                            @endforeach
                        </div>
                        
                        <div class="small fw-bold text-muted mb-1 px-1">Ký hiệu khác</div>
                        <div class="d-flex flex-wrap">
                            @foreach(['©', '®', '™', '€', '£', '¥', '✓', '✗', '•', '○', '●', '□', '■', '←', '↑', '→', '↓', '↔', '↕'] as $sym)
                                <button class="btn btn-light border m-0" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center; font-size: 14px; margin-right: 2px !important; margin-bottom: 2px !important;" onmousedown="event.preventDefault();" onclick="formatDoc('insertText', '{{ $sym }}')">{{ $sym }}</button>
                            @endforeach
                        </div>
                    </div>
                </div>
                <button class="btn btn-toolbar" onmousedown="event.preventDefault();"
                    onclick="addAbbreviation()" title="Thêm danh mục chữ viết tắt">
                    <i class="fas fa-spell-check text-primary"></i>
                </button>
                <button class="btn btn-toolbar" onclick="document.getElementById('imageUploader').click()"
                    title="Chèn hình ảnh">
                    <i class="fas fa-image"></i>
                </button>
                <input type="file" id="imageUploader" class="d-none" accept="image/*"
                    onchange="uploadImageBase64(this)">
            </div>
            <div class="toolbar-group-label">Ký Hiệu & Ảnh</div>
        </div>

    </div>{{-- End Row 2 --}}

</div>
