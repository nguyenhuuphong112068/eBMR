<!-- Google Docs Style Toolbar -->
<div class="editor-toolbar shadow-sm d-flex flex-column px-4 py-2 bg-white">
    <div class="d-flex align-items-center w-100">
        <div class="d-flex align-items-center border-end pe-3 me-3 gap-1">
            <button class="btn btn-toolbar" onclick="undo()" title="Undo"><i class="fas fa-undo"></i></button>
            <button class="btn btn-toolbar" onclick="redo()" title="Redo"><i class="fas fa-redo"></i></button>
            <button class="btn btn-toolbar" onclick="openTemplateModal()" title="Mở hồ sơ">
                <i class="fas fa-folder-open"></i>
            </button>
            <button class="btn btn-toolbar" onclick="openHistoryModal()" title="Lịch sử thay đổi">
                <i class="fas fa-history"></i>
            </button>
            <button class="btn btn-toolbar" onclick="window.print()" title="Print"><i
                    class="fas fa-print"></i></button>
        </div>

        <div class="d-flex align-items-center border-end pe-3 me-3 gap-2">
            <span class="small fw-bold text-muted me-2">CHÈN:</span>

            <!-- Table Dropdown -->
            <div class="dropdown d-inline-block">
                <button class="btn btn-toolbar-action dropdown-toggle" type="button" id="tableDropdown"
                    data-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-table me-1"></i> Bảng
                </button>
                <div class="dropdown-menu table-selector-dropdown p-3" aria-labelledby="tableDropdown">
                    <div class="grid-container" id="table-grid">
                    </div>
                    <div class="grid-label" id="grid-selection-label">1 x 1 Table</div>
                </div>
            </div>

            <button class="btn btn-toolbar-action" onclick="addItem('static-text')"><i
                    class="fas fa-paragraph me-1"></i>
                Mô tả</button>

            <button class="btn btn-toolbar-action" onclick="addItem('signature')"><i class="fas fa-signature me-1"></i>
                Chữ ký</button>
        </div>

        <div class="ms-auto">
            <button class="btn btn-navy px-4" onclick="saveTemplate()" style="border-radius: 20px;">
                <i class="fas fa-cloud-upload-alt me-2"></i> LƯU HỒ SƠ MẪU
            </button>
        </div>
    </div>

    <!-- Additional RTE formatting toolbar -->
    <div class="d-flex align-items-center w-100 mt-2 pt-2 border-top gap-1">
        <select class="form-select form-select-sm" style="width: 140px; font-size: 0.8rem;"
            onchange="formatDoc('formatBlock', this.value); this.selectedIndex=0;" title="Định dạng Tiêu đề / Thẻ">
            <option value="">Kiểu tài liệu...</option>
            <option value="H1">Tiêu đề cấp 1 (22pt)</option>
            <option value="H2">Tiêu đề cấp 2 (18pt)</option>
            <option value="H3">Tiêu đề cấp 3 (16pt)</option>
            <option value="H4">Tiêu đề cấp 4 (16pt)</option>
            <option value="P">Đoạn văn (14pt)</option>
        </select>
        <div class="border-end mx-1" style="height: 18px;"></div>
        <div class="input-group input-group-sm" style="width: 70px;" title="Cỡ chữ">
            <input type="number" class="form-control text-center px-1" id="customFontSize" value="16"
                min="8" max="72" step="1" onmousedown="saveCurrentSelection()"
                onchange="applyCustomFontSize(this.value)">
        </div>
        <div class="border-end mx-1" style="height: 18px;"></div>
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

        <div class="ms-auto d-flex align-items-center">
            <div class="input-group input-group-sm" style="width: 200px;">
                <input type="text" class="form-control" id="searchBox" placeholder="Tìm kiếm Text...">
                <button class="btn btn-outline-secondary" type="button" onclick="searchDoc()"><i
                        class="fas fa-search"></i></button>
            </div>
        </div>
    </div>
</div>
