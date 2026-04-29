<!-- Google Docs Style Toolbar -->
<div class="editor-toolbar shadow-sm d-flex flex-column px-4 py-2 bg-white">
    <div class="d-flex align-items-center w-100">
        <div class="d-flex align-items-center border-end pe-3 me-3 gap-1 {{ $isReadOnly ? 'opacity-50 pointer-events-none' : '' }}">
            <button class="btn btn-toolbar" onclick="undo()" title="Undo" {{ $isReadOnly ? 'disabled' : '' }}><i class="fas fa-undo"></i></button>
            <button class="btn btn-toolbar" onclick="redo()" title="Redo" {{ $isReadOnly ? 'disabled' : '' }}><i class="fas fa-redo"></i></button>
            <button class="btn btn-toolbar" id="btn-format-painter" onclick="toggleFormatPainter()" title="Sao chép định dạng" {{ $isReadOnly ? 'disabled' : '' }}><i class="fas fa-paint-roller"></i></button>
            <button class="btn btn-toolbar" onclick="clearFormatting()" title="Xóa định dạng" {{ $isReadOnly ? 'disabled' : '' }}><i class="fas fa-remove-format"></i></button>
            <button class="btn btn-toolbar" onclick="openTemplateModal()" title="Mở hồ sơ">
                <i class="fas fa-folder-open"></i>
            </button>
            <button class="btn btn-toolbar" onclick="openHistoryModal()" title="Lịch sử thay đổi">
                <i class="fas fa-history"></i>
            </button>
            <button class="btn btn-toolbar" onclick="window.print()" title="In hồ sơ"><i
                    class="fas fa-print"></i></button>
            <button class="btn btn-toolbar" onclick="document.getElementById('wordImporter').click()" title="Nhập từ Word (.doc, .docx)">
                <i class="fas fa-file-word text-primary"></i>
            </button>
            <input type="file" id="wordImporter" class="d-none" accept=".doc, .docx" onchange="importWordFile(this)">
        </div>

        <div class="d-flex align-items-center border-end pe-3 me-3 gap-2 {{ $isReadOnly ? 'd-none' : '' }}">
            <!-- ... existing items ... -->
            <div class="dropdown d-inline-block">
                <button class="btn btn-toolbar-action dropdown-toggle" type="button" id="tableDropdown"
                    data-toggle="dropdown" aria-expanded="false" title="Chèn Bảng">
                    <i class="fas fa-table"></i>
                </button>
                    <div class="dropdown-menu table-selector-dropdown p-3" aria-labelledby="tableDropdown">
                    <div class="grid-container" id="table-grid">
                    </div>
                    <div class="grid-label" id="grid-selection-label">1 x 1 Table</div>
                </div>
            </div>

            <div class="d-flex align-items-center gap-1 border-start ps-2 ms-2 me-2 border-end pe-2">
                <button class="btn btn-toolbar-action" onclick="mergeSelectedCells()" title="Gộp ô">
                    <i class="fas fa-object-group"></i>
                </button>
                <button class="btn btn-toolbar-action" onclick="openSplitModal()" title="Tách ô">
                    <i class="fas fa-columns"></i>
                </button>
            </div>

            <button class="btn btn-toolbar-action" onmousedown="event.preventDefault();"
                onclick="addItem('static-text')" title="Chèn Mô tả">
                <i class="fas fa-paragraph"></i>
            </button>


            <button class="btn btn-toolbar-action" onmousedown="event.preventDefault();" onclick="openLinkGfModal()" title="BM Chung">
                <i class="fas fa-link"></i>
            </button>

            <button class="btn btn-toolbar-action text-info fw-bold" onmousedown="event.preventDefault();"
                onclick="addSection()" title="Thêm Phân đoạn">
                <i class="fas fa-layer-group"></i>
            </button>

            <div class="btn-group">
                <button class="btn btn-toolbar-action text-primary fw-bold" onmousedown="event.preventDefault();"
                    onclick="insertDynamicField()" title="Chèn Biến số">
                    <i class="fas fa-keyboard"></i>
                </button>
                <button type="button" class="btn btn-toolbar-action text-primary dropdown-toggle dropdown-toggle-split" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <span class="sr-only">Toggle Dropdown</span>
                </button>
                <div class="dropdown-menu shadow-lg p-2" style="border-radius: 12px; min-width: 180px;">
                    <button class="dropdown-item rounded mb-1" onclick="insertDynamicField('text')">✒️ Nhập Văn bản</button>
                    <button class="dropdown-item rounded mb-1" onclick="insertDynamicField('number')">🔢 Nhập Số</button>
                    <button class="dropdown-item rounded mb-1" onclick="insertDynamicField('date')">📅 Nhập Ngày</button>
                    <button class="dropdown-item rounded mb-1" onclick="insertDynamicField('signature')">✍️ Nhập Chữ ký</button>
                    <button class="dropdown-item rounded mb-1" onclick="insertDynamicField('checkbox')">☑️ Nhập Tick</button>
                    <button class="dropdown-item rounded mb-1" onclick="insertDynamicField('select')">🔘 Nhập Lựa chọn</button>
                    <button class="dropdown-item rounded mb-1" onclick="insertDynamicField('formula')">🧮 Thiết lập Công thức</button>
                </div>
            </div>
            <button class="btn btn-toolbar-action text-muted ms-1" onmousedown="event.preventDefault();" onclick="openSymbolModal()" title="Ký hiệu đặc biệt">
                <i class="fas fa-omega"></i>
            </button>

            <button class="btn btn-toolbar-action text-primary ms-2 border-start ps-2" onmousedown="event.preventDefault();" onclick="openVariableSummaryModal()" title="Danh sách biến số đã cài đặt">
                <i class="fas fa-tasks"></i>
            </button>
        </div>

        <div class="ms-auto d-flex gap-2">
            <!-- Designer / Execute Mode Toggle -->
            <div class="btn-group me-2" role="group" aria-label="Mode toggle">
                <button type="button" id="btn-mode-designer" 
                    class="btn btn-sm px-3 {{ empty($isExecutionMode) ? 'btn-primary' : 'btn-outline-primary' }}" 
                    onclick="setDesignerMode(false)" style="border-radius: 20px 0 0 20px;">
                    <i class="fas fa-edit me-1"></i> Thiết kế
                </button>
                <button type="button" id="btn-mode-execute" 
                    class="btn btn-sm px-3 {{ !empty($isExecutionMode) ? 'btn-success' : 'btn-outline-success' }}" 
                    onclick="setDesignerMode(true)" style="border-radius: 0 20px 20px 0;">
                    <i class="fas fa-play me-1"></i> Chạy thử
                </button>
            </div>

            <!-- Language Selector -->
            <div class="btn-group" role="group">
                <button id="langModeBtn" type="button" class="btn btn-outline-secondary px-2 dropdown-toggle" data-toggle="dropdown" style="border-radius: 20px;" title="Ngôn ngữ">
                    <i class="fas fa-language"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-right shadow-lg p-2" style="border-radius: 12px; min-width: 200px;">
                    <button class="dropdown-item rounded mb-1" onclick="setLanguageMode('vi')">
                        <i class="fas fa-check me-2 text-success" id="check-vi"></i> 1. Tiếng Việt (Gốc)
                    </button>
                    <button class="dropdown-item rounded mb-1" onclick="setLanguageMode('en')">
                        <i class="fas fa-check me-2 d-none" id="check-en"></i> 2. Tiếng Anh (Dịch)
                    </button>
                    <button class="dropdown-item rounded mb-1" onclick="setLanguageMode('dual')">
                        <i class="fas fa-check me-2 d-none" id="check-dual"></i> 3. Song ngữ (Xem)
                    </button>
                    <div class="dropdown-divider"></div>
                    <button class="dropdown-item rounded text-primary fw-bold" onclick="translateCurrentDocument()">
                        <i class="fas fa-robot me-2"></i> Phiên dịch bằng AI...
                    </button>
                </div>
            </div>

            <button id="viewModeToggle" class="btn {{ empty($activeSectionId) ? 'btn-info' : 'btn-outline-info' }} px-3"
                onclick="toggleViewMode()" style="border-radius: 20px;" title="Thay đổi chế độ xem">
                @if (empty($activeSectionId))
                    <i class="fas fa-th-list"></i>
                @else
                    <i class="fas fa-expand-arrows-alt"></i>
                @endif
            </button>
            <button class="btn btn-navy px-3" onclick="saveTemplate()" style="border-radius: 20px;" {{ $isReadOnly ? 'disabled' : '' }} title="Lưu hồ sơ mẫu">
                <i class="fas fa-cloud-upload-alt"></i>
            </button>
        </div>
    </div>

    <!-- Additional RTE formatting toolbar -->
    <div class="d-flex align-items-center w-100 mt-2 pt-2 border-top gap-1 {{ $isReadOnly ? 'd-none' : '' }}">
        <!-- ... all the formatting tools ... -->
        <select class="form-select form-select-sm" style="width: 140px; font-size: 0.8rem;"
            onchange="formatDoc('formatBlock', this.value); this.selectedIndex=0;" title="Định dạng Tiêu đề / Thẻ">
            <option value="">Kiểu tài liệu...</option>
            <option value="H1">Tiêu đề cấp 1 (16pt)</option>
            <option value="H2">Tiêu đề cấp 2 (15pt)</option>
            <option value="H3">Tiêu đề cấp 3 (14pt)</option>
            <option value="H4">Tiêu đề cấp 4 (14pt)</option>
            <option value="P">Đoạn văn (14pt)</option>
        </select>
        <div class="d-flex align-items-center gap-1 border-start ps-2 ms-2 border-end pe-2">
            <button class="btn btn-toolbar-action p-0 d-flex align-items-center justify-content-center" style="width: 24px; height: 24px; border-radius: 4px;" onclick="changeFontSize(-1)" title="Giảm cỡ chữ">
                <i class="fas fa-minus" style="font-size: 0.7rem;"></i>
            </button>
            <div class="dropdown d-inline-block" title="Cỡ chữ">
                <button class="btn btn-toolbar dropdown-toggle px-2 fw-bold" type="button" data-toggle="dropdown" aria-expanded="false" style="min-width: 45px;">
                    <span id="fontSizeDisplay">16</span>
                </button>
                <div class="dropdown-menu shadow-lg p-2" style="min-width: 80px; max-height: 300px; overflow-y: auto;">
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
            <button class="btn btn-toolbar-action p-0 d-flex align-items-center justify-content-center" style="width: 24px; height: 24px; border-radius: 4px;" onclick="changeFontSize(1)" title="Tăng cỡ chữ">
                <i class="fas fa-plus" style="font-size: 0.7rem;"></i>
            </button>
        </div>
        <button class="btn btn-toolbar" onclick="formatDoc('bold')" title="In đậm (Ctrl+B)"><i
                class="fas fa-bold"></i></button>
        <button class="btn btn-toolbar" onclick="formatDoc('italic')" title="In nghiêng (Ctrl+I)"><i
                class="fas fa-italic"></i></button>
        <button class="btn btn-toolbar" onclick="formatDoc('underline')" title="Gạch chân (Ctrl+U)"><i
                class="fas fa-underline"></i></button>
        <button class="btn btn-toolbar" onclick="formatDoc('strikethrough')" title="Gạch ngang"><i
                class="fas fa-strikethrough"></i></button>

        <button class="btn btn-toolbar" onclick="formatDoc('superscript')" title="Chỉ số trên"><i
                class="fas fa-superscript"></i></button>
        <button class="btn btn-toolbar" onclick="formatDoc('subscript')" title="Chỉ số dưới"><i
                class="fas fa-subscript"></i></button>

        <!-- Text Color (Dual Action) -->
        <div class="btn-group" role="group">
            <button type="button" class="btn btn-toolbar px-2" onclick="applyCurrentTextColor()"
                title="Tô màu chữ (Màu hiện tại)" onmousedown="event.preventDefault()">
                <div style="position: relative;">
                    <i class="fas fa-font text-dark"></i>
                    <div id="textColorIndicator"
                        style="position: absolute; bottom: -4px; left: 0; width: 100%; height: 3px; background: #ff0000; border-radius: 2px;">
                    </div>
                </div>
            </button>
            <button type="button" class="btn btn-light btn-sm dropdown-toggle dropdown-toggle-split"
                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Chọn màu chữ">
                <span class="sr-only">Toggle Dropdown</span>
            </button>
            <div class="dropdown-menu p-2 shadow-lg" style="min-width: 250px; z-index: 2000;"
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
                            // Fallback if DOMContentLoaded already fired
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
                        style="height: 30px; width: 40px;" id="customTextColor" value="#ff0000"
                        onchange="updateTextColorPickerWrapper(this.value)">
                    <label class="small text-muted mb-0" for="customTextColor">Màu tuỳ chỉnh...</label>
                </div>
            </div>
        </div>

        <div class="border-end mx-1" style="height: 18px;"></div>
        <button class="btn btn-toolbar" onclick="formatDoc('justifyLeft')" title="Canh trái"><i
                class="fas fa-align-left"></i></button>
        <button class="btn btn-toolbar" onclick="formatDoc('justifyCenter')" title="Canh giữa"><i
                class="fas fa-align-center"></i></button>
        <button class="btn btn-toolbar" onclick="formatDoc('justifyRight')" title="Canh phải"><i
                class="fas fa-align-right"></i></button>
        <button class="btn btn-toolbar" onclick="formatDoc('justifyFull')" title="Canh đều"><i
                class="fas fa-align-justify"></i></button>
        <div class="border-end mx-1" style="height: 18px;"></div>

        <button class="btn btn-toolbar" onclick="formatDoc('insertUnorderedList')" title="Danh sách dạng dấu chấm"><i
                class="fas fa-list-ul"></i></button>
        <button class="btn btn-toolbar" onclick="formatDoc('insertOrderedList')" title="Danh sách đánh số"><i
                class="fas fa-list-ol"></i></button>
        <button class="btn btn-toolbar ms-1" onclick="addComment()" title="Thêm bình luận">
            <i class="far fa-comment-dots"></i>
        </button>
        <div class="border-end mx-1" style="height: 18px;"></div>

        <div class="dropdown d-inline-block">
            <button class="btn btn-toolbar dropdown-toggle" type="button" data-toggle="dropdown"
                aria-expanded="false" title="Ký tự đặc biệt">
                <i class="fas fa-omega"></i>
            </button>
            <div class="dropdown-menu p-2" style="min-width: 180px;">
                <button class="btn btn-sm btn-light m-1" onclick="formatDoc('insertText', 'α')">α</button>
                <button class="btn btn-sm btn-light m-1" onclick="formatDoc('insertText', 'β')">β</button>
                <button class="btn btn-sm btn-light m-1" onclick="formatDoc('insertText', 'γ')">γ</button>
                <button class="btn btn-sm btn-light m-1" onclick="formatDoc('insertText', 'Δ')">Δ</button>
                <button class="btn btn-sm btn-light m-1" onclick="formatDoc('insertText', '°')">°</button>
                <button class="btn btn-sm btn-light m-1" onclick="formatDoc('insertText', '±')">±</button>
                <button class="btn btn-sm btn-light m-1" onclick="formatDoc('insertText', '≤')">≤</button>
                <button class="btn btn-sm btn-light m-1" onclick="formatDoc('insertText', '≥')">≥</button>
                <button class="btn btn-sm btn-light m-1" onclick="formatDoc('insertText', 'µ')">µ</button>
                <button class="btn btn-sm btn-light m-1" onclick="formatDoc('insertText', '©')">©</button>
            </div>
        </div>

        <button class="btn btn-toolbar" onclick="document.getElementById('imageUploader').click()"
            title="Chèn hình ảnh"><i class="fas fa-image"></i></button>
        <input type="file" id="imageUploader" class="d-none" accept="image/*"
            onchange="uploadImageBase64(this)">


    </div>
</div>
