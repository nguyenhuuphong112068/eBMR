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

            <button class="btn btn-sm btn-light" data-cmd="undo" title="Hoàn tác (trong vùng đang gõ)"><i
                    class="fas fa-undo"></i></button>
            <button class="btn btn-sm btn-light" data-cmd="redo" title="Làm lại"><i class="fas fa-redo"></i></button>

            <div class="vr mx-2"></div>

            {{-- Kiểu đoạn / Font / Cỡ chữ (giống Google Docs) --}}
            <select id="v2-sel-style" data-fmt class="form-select form-select-sm v2-fmt-select" style="width: 120px;"
                title="Kiểu văn bản">
                <option value="p">Văn bản thường</option>
                <option value="h1">Tiêu đề 1</option>
                <option value="h2">Tiêu đề 2</option>
                <option value="h3">Tiêu đề 3</option>
            </select>
            <select id="v2-sel-size" data-fmt class="form-select form-select-sm v2-fmt-select" style="width: 72px;"
                title="Cỡ chữ">
                <option value="">Cỡ</option>
                @foreach ([8, 9, 10, 11, 12, 14, 16, 18, 20, 24, 28, 36] as $sz)
                    <option value="{{ $sz }}pt">{{ $sz }}</option>
                @endforeach
            </select>
            <button class="btn btn-sm btn-light" id="v2-btn-heading-num"
                title="Đánh số tiêu đề tự động nhiều cấp (1. / 1.1. / 1.1.1. — giống Word)">
                <i class="fas fa-list-ol"></i><sup style="font-size:0.55em;">1.1</sup>
            </button>

            <div class="vr mx-2"></div>

            <button class="btn btn-sm btn-light" data-cmd="bold" title="Đậm (Ctrl+B)"><i class="fas fa-bold"></i></button>
            <button class="btn btn-sm btn-light" data-cmd="italic" title="Nghiêng (Ctrl+I)"><i
                    class="fas fa-italic"></i></button>
            <button class="btn btn-sm btn-light" data-cmd="underline" title="Gạch chân (Ctrl+U)"><i
                    class="fas fa-underline"></i></button>
            <button class="btn btn-sm btn-light" data-cmd="strike" title="Gạch ngang"><i
                    class="fas fa-strikethrough"></i></button>
            <button class="btn btn-sm btn-light" data-cmd="subscript" title="Chỉ số dưới (H₂O)"><i
                    class="fas fa-subscript"></i></button>
            <button class="btn btn-sm btn-light" data-cmd="superscript" title="Chỉ số trên (m²)"><i
                    class="fas fa-superscript"></i></button>

            {{-- Màu chữ / Màu nền (highlight) — kèm bảng mã màu định sẵn (datalist) để chọn nhanh --}}
            <label class="v2-color-wrap" title="Màu chữ">
                <i class="fas fa-font"></i>
                <input type="color" id="v2-color-text" value="#000000" list="v2-color-presets">
            </label>
            <label class="v2-color-wrap" title="Màu nền chữ (highlight)">
                <i class="fas fa-highlighter"></i>
                <input type="color" id="v2-color-highlight" value="#ffff00" list="v2-color-presets">
            </label>
            <datalist id="v2-color-presets">
                <option>#000000</option>
                <option>#444444</option>
                <option>#888888</option>
                <option>#ffffff</option>
                <option>#c00000</option>
                <option>#ff0000</option>
                <option>#ff6d01</option>
                <option>#ffff00</option>
                <option>#92d050</option>
                <option>#00b050</option>
                <option>#00b0f0</option>
                <option>#0070c0</option>
                <option>#002060</option>
                <option>#7030a0</option>
                <option>#003a4f</option>
                <option>#164e63</option>
            </datalist>
            <button class="btn btn-sm btn-light" id="v2-btn-unhighlight" title="Bỏ màu nền chữ"><i
                    class="fas fa-eraser"></i></button>
            <button class="btn btn-sm btn-light" id="v2-btn-link" title="Chèn liên kết (Ctrl+K)"><i
                    class="fas fa-link"></i></button>

            <div class="vr mx-2"></div>

            {{-- Căn lề / Giãn dòng / Danh sách / Thụt lề --}}
            <button class="btn btn-sm btn-light" data-cmd="align-left" title="Căn trái"><i
                    class="fas fa-align-left"></i></button>
            <button class="btn btn-sm btn-light" data-cmd="align-center" title="Căn giữa"><i
                    class="fas fa-align-center"></i></button>
            <button class="btn btn-sm btn-light" data-cmd="align-right" title="Căn phải"><i
                    class="fas fa-align-right"></i></button>
            <button class="btn btn-sm btn-light" data-cmd="align-justify" title="Căn đều 2 bên"><i
                    class="fas fa-align-justify"></i></button>

            {{-- Căn nội dung Ô BẢNG theo chiều dọc (trên / giữa / dưới) --}}
            <button class="btn btn-sm btn-light" id="v2-btn-valign-top" title="Căn nội dung ô: Trên">
                <i class="fas fa-long-arrow-alt-up"></i></button>
            <button class="btn btn-sm btn-light" id="v2-btn-valign-middle" title="Căn nội dung ô: Giữa">
                <i class="fas fa-grip-lines"></i></button>
            <button class="btn btn-sm btn-light" id="v2-btn-valign-bottom" title="Căn nội dung ô: Dưới">
                <i class="fas fa-long-arrow-alt-down"></i></button>
            <select id="v2-sel-lineheight" data-fmt class="form-select form-select-sm v2-fmt-select" style="width: 82px;"
                title="Giãn dòng">
                <option value="">Giãn</option>
                <option value="1">1</option>
                <option value="1.15">1.15</option>
                <option value="1.5">1.5</option>
                <option value="2">2</option>
            </select>
            <button class="btn btn-sm btn-light" data-cmd="bullet" title="Danh sách chấm"><i
                    class="fas fa-list-ul"></i></button>
            <button class="btn btn-sm btn-light" data-cmd="ordered" title="Danh sách số"><i
                    class="fas fa-list-ol"></i></button>
            <button class="btn btn-sm btn-light" data-cmd="outdent" title="Giảm thụt lề (trong danh sách)"><i
                    class="fas fa-outdent"></i></button>
            <button class="btn btn-sm btn-light" data-cmd="indent" title="Tăng thụt lề (trong danh sách)"><i
                    class="fas fa-indent"></i></button>
            <button class="btn btn-sm btn-light" data-cmd="clear-format" title="Xóa toàn bộ định dạng"><i
                    class="fas fa-remove-format"></i></button>
            <button class="btn btn-sm btn-light" id="v2-btn-format-painter"
                title="Sao chép định dạng (click = 1 lần, double-click = khoá liên tục, Esc để dừng)"><i
                    class="fas fa-paint-roller"></i></button>

            <div class="vr mx-2"></div>

            {{-- Gộp / Tách ô (thao tác trên vùng ô đang chọn) --}}
            <button class="btn btn-sm btn-light" data-cmd="merge-cells" title="Gộp các ô đang chọn"><i
                    class="fas fa-object-group"></i></button>
            <button class="btn btn-sm btn-light" data-cmd="split-cell" title="Tách ô đã gộp"><i
                    class="fas fa-object-ungroup"></i></button>
            <button class="btn btn-sm btn-light" id="v2-btn-loop-group"
                title="Cài đặt Lặp nhóm khối (nhấn nút 'Lặp nhóm' trên từng khối để chọn dải trước)"><i
                    class="fas fa-clone"></i></button>
            <button class="btn btn-sm btn-light" id="v2-btn-weight-chart"
                title="Bảng KT Khối lượng Trung bình (chèn bảng nhập + biểu đồ)"><i
                    class="fas fa-balance-scale"></i></button>
            <button class="btn btn-sm btn-light" id="v2-btn-abbreviation"
                title="Thêm danh mục chữ viết tắt (bôi đen từ viết tắt trước khi bấm)"><i
                    class="fas fa-spell-check"></i></button>

            <div class="vr mx-2"></div>

            {{-- Chèn khối mới (văn bản / bảng): CLICK = chèn tại vị trí con trỏ; KÉO-THẢ vào giữa 2 khối bất kỳ --}}
            <button class="btn btn-sm btn-light" id="v2-btn-insert-text" draggable="true"
                title="Chèn khối văn bản tại vị trí con trỏ — hoặc KÉO THẢ vào giữa 2 khối"><i
                    class="fas fa-font"></i></button>
            <div class="dropdown">
                <button class="btn btn-sm btn-light" id="v2-btn-insert-table" draggable="true" data-bs-toggle="dropdown"
                    data-toggle="dropdown"
                    title="Chèn bảng tại vị trí con trỏ (rê chuột chọn số hàng x cột) — hoặc KÉO THẢ vào giữa 2 khối">
                    <i class="fas fa-table"></i> <i class="fas fa-caret-down ms-1"></i>
                </button>
                <div class="dropdown-menu shadow border-0 p-2" id="v2-table-grid-menu">
                    <div class="small text-muted text-center mb-1" id="v2-table-grid-label">Chèn bảng</div>
                    <div id="v2-table-grid"></div>
                    <hr class="dropdown-divider my-2">
                    <a class="dropdown-item small py-1" href="javascript:void(0)" id="v2-table-grid-custom">
                        <i class="fas fa-table me-2 text-muted"></i>Chèn bảng...</a>
                </div>
            </div>

            <div class="vr mx-2"></div>

            {{-- Viền bảng (Borders) --}}
            <div class="dropdown">
                <button class="btn btn-sm btn-light" id="v2-btn-borders" data-bs-toggle="dropdown"
                    data-toggle="dropdown" title="Định dạng viền bảng">
                    <i class="fas fa-border-all"></i> <i class="fas fa-caret-down ms-1"></i>
                </button>
                <div class="dropdown-menu shadow border-0 p-2" id="v2-borders-menu">
                    <div class="small text-muted text-center mb-1">Viền bảng</div>
                    <a class="dropdown-item small py-1" href="javascript:void(0)" data-border-type="bottom">
                        <i class="fas fa-border-bottom me-2"></i>Viền dưới</a>
                    <a class="dropdown-item small py-1" href="javascript:void(0)" data-border-type="top">
                        <i class="fas fa-border-top me-2"></i>Viền trên</a>
                    <a class="dropdown-item small py-1" href="javascript:void(0)" data-border-type="left">
                        <i class="fas fa-border-left me-2"></i>Viền trái</a>
                    <a class="dropdown-item small py-1" href="javascript:void(0)" data-border-type="right">
                        <i class="fas fa-border-right me-2"></i>Viền phải</a>
                    <hr class="dropdown-divider my-1">
                    <a class="dropdown-item small py-1" href="javascript:void(0)" data-border-type="no-border">
                        <i class="fas fa-times me-2"></i>Xoá viền</a>
                    <a class="dropdown-item small py-1" href="javascript:void(0)" data-border-type="all-borders">
                        <i class="fas fa-border-all me-2"></i>Tất cả viền</a>
                    <a class="dropdown-item small py-1" href="javascript:void(0)" data-border-type="outside-borders">
                        <i class="fas fa-window-restore me-2"></i>Viền ngoài</a>
                    <a class="dropdown-item small py-1" href="javascript:void(0)" data-border-type="inside-borders">
                        <i class="fas fa-border-outer me-2"></i>Viền trong</a>
                    <a class="dropdown-item small py-1" href="javascript:void(0)" data-border-type="inside-horizontal">
                        <i class="fas fa-minus me-2"></i>Viền ngang trong</a>
                    <a class="dropdown-item small py-1" href="javascript:void(0)" data-border-type="inside-vertical">
                        <i class="fas fa-arrows-alt-v me-2"></i>Viền dọc trong</a>
                </div>
            </div>

            <div class="vr mx-2"></div>

            <button class="btn btn-sm btn-light" id="v2-btn-symbol" title="Chèn ký hiệu / Symbol"><i
                    class="fas fa-icons"></i></button>
            <button class="btn btn-sm btn-light" id="v2-btn-equation" title="Chèn công thức toán học"><i
                    class="fas fa-square-root-alt"></i></button>
            <button class="btn btn-sm btn-light" id="v2-btn-image" title="Chèn hình ảnh"><i
                    class="fas fa-image"></i></button>
            <button class="btn btn-sm btn-light" id="v2-btn-docprop" title="Chèn Document Property"><i
                    class="fas fa-tags"></i></button>
            <button class="btn btn-sm btn-light" id="v2-btn-split" title="Chia đôi màn hình (Split View)"><i
                    class="fas fa-columns"></i></button>

            <div class="vr mx-2"></div>

            {{-- Chèn biến số --}}
            <div class="dropdown">
                <button class="btn btn-sm btn-navy text-white px-2" data-bs-toggle="dropdown" data-toggle="dropdown"
                    title="Chèn biến số vào vị trí con trỏ">
                    <i class="fas fa-plus-circle"></i> <i class="fas fa-caret-down ms-1"></i>
                </button>
                <div class="dropdown-menu shadow border-0">
                    <a class="dropdown-item" href="javascript:void(0)" data-insert-field="text"><i
                            class="fas fa-font me-2 text-muted"></i>Văn bản</a>
                    <a class="dropdown-item" href="javascript:void(0)" data-insert-field="number"><i
                            class="fas fa-hashtag me-2 text-muted"></i>Số</a>
                    <a class="dropdown-item" href="javascript:void(0)" data-insert-field="date"><i
                            class="fas fa-calendar-alt me-2 text-muted"></i>Thời gian</a>
                    <a class="dropdown-item" href="javascript:void(0)" data-insert-field="signature"><i
                            class="fas fa-signature me-2 text-muted"></i>Chữ ký</a>
                    <a class="dropdown-item" href="javascript:void(0)" data-insert-field="checkbox"><i
                            class="fas fa-check-square me-2 text-muted"></i>Tick chọn</a>
                    <a class="dropdown-item" href="javascript:void(0)" data-insert-field="select"><i
                            class="fas fa-list-ul me-2 text-muted"></i>Lựa chọn</a>
                    <a class="dropdown-item" href="javascript:void(0)" data-insert-field="formula"><i
                            class="fas fa-square-root-alt me-2 text-muted"></i>Công thức</a>
                </div>
            </div>

            <div class="ms-auto d-flex align-items-center gap-2">
                <button id="v2-btn-toggle-mode" class="btn btn-sm btn-primary text-white px-2 ml-2"
                    title="Chuyển đổi Thiết kế / hạy thử">
                    <i class="fas fa-play"></i> Chạy thử
                </button>
                @if (!$isReadOnly)
                    <button id="v2-btn-save" class="btn btn-sm btn-success text-white px-2 ml-2"
                        title="Lưu lại (Không có thay đổi)">
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
                <select id="v2-eq-dept" class="form-control form-control-sm mb-1">
                    <option value="">-- Tất cả Phân Xưởng --</option>
                </select>
                <input type="text" id="v2-eq-search" class="form-control form-control-sm"
                    placeholder="Tìm thiết bị...">
                <div class="text-muted mt-1" style="font-size: 0.68rem; font-style: italic;">
                    <i class="fas fa-info-circle me-1"></i>Tick chọn thiết bị rồi kéo thả vào thanh chèn khối giữa các
                    block.
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
                <input type="text" id="v2-co-search" class="form-control form-control-sm"
                    placeholder="Tìm thành phần...">
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
                    <textarea id="v2-comment-input" class="form-control form-control-sm" rows="2" placeholder="Viết bình luận..."></textarea>
                    <button class="btn btn-sm btn-navy text-white w-100 mt-2" id="v2-comment-send">
                        <i class="fas fa-paper-plane me-1"></i> Gửi bình luận
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== Modal: Tạo Bảng KT Khối lượng Trung bình (port từ V1 weightChartCreatorModal) ===== --}}
    <div class="modal fade" id="v2WeightChartModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg border-0" style="border-radius: 12px;">
                <div class="modal-header bg-danger text-white border-0 py-2 px-3">
                    <h5 class="modal-title fw-bold small"><i class="fas fa-balance-scale me-2"></i> BẢNG KT KHỐI LƯỢNG
                        TRUNG BÌNH</h5>
                    <button type="button" class="close text-white" data-bs-dismiss="modal" data-dismiss="modal"
                        aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="small fw-bold mb-1">Khối lượng Trung bình chuẩn (g)</label>
                        <input type="number" step="0.01" id="v2-wc-weight" class="form-control"
                            placeholder="VD: 7.10" value="7.10">
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold mb-1">% Độ lệch cho phép (±%)</label>
                        <input type="number" step="0.1" id="v2-wc-dev" class="form-control" placeholder="VD: 3"
                            value="3">
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold mb-1">Tần suất lấy mẫu (Phút)</label>
                        <input type="number" id="v2-wc-freq" class="form-control" placeholder="VD: 15" value="15">
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 py-2">
                    <button type="button" class="btn btn-secondary btn-sm px-4" data-bs-dismiss="modal"
                        data-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-danger btn-sm px-4 fw-bold" id="v2-wc-generate">Chèn Bảng &amp;
                        Biểu Đồ</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== Modal: Thêm danh mục chữ viết tắt (port từ V1 abbreviationModal) ===== --}}
    <div class="modal fade" id="v2AbbreviationModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg border-0" style="border-radius: 12px;">
                <div class="modal-header bg-primary text-white border-0 py-2 px-3">
                    <h5 class="modal-title fw-bold small"><i class="fas fa-spell-check me-2"></i> THÊM TỪ VIẾT TẮT</h5>
                    <button type="button" class="close text-white" data-bs-dismiss="modal" data-dismiss="modal"
                        aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="small fw-bold mb-1">Từ viết tắt đã chọn</label>
                        <input type="text" id="v2-abbr-word" class="form-control" readonly
                            style="background-color: #f8f9fa;">
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold mb-1">Ý nghĩa (Giải nghĩa)</label>
                        <input type="text" id="v2-abbr-meaning" class="form-control"
                            placeholder="Nhập ý nghĩa của từ viết tắt...">
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 py-2">
                    <button type="button" class="btn btn-secondary btn-sm px-4" data-bs-dismiss="modal"
                        data-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-primary btn-sm px-4 fw-bold" id="v2-abbr-save">Lưu</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== Modal: Chèn Symbol (port từ V1 symbol_ops) ===== --}}
    <div class="modal fade" id="v2SymbolModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg border-0" style="border-radius: 12px;">
                <div class="modal-header bg-dark text-white border-0 py-2 px-3">
                    <h5 class="modal-title fw-bold small"><i class="fas fa-icons me-2"></i> CHÈN KÝ HIỆU</h5>
                    <button type="button" class="close text-white" data-bs-dismiss="modal" data-dismiss="modal"
                        aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <ul class="nav nav-tabs mb-2" id="v2-symbol-tabs">
                        <li class="nav-item"><a class="nav-link active" data-v2-symtab="math" href="#">Toán
                                học</a></li>
                        <li class="nav-item"><a class="nav-link" data-v2-symtab="greek" href="#">Hy Lạp</a></li>
                        <li class="nav-item"><a class="nav-link" data-v2-symtab="misc" href="#">Khác</a></li>
                    </ul>
                    <div id="v2-symbol-grid" class="d-flex flex-wrap gap-1"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== Modal: Chèn Công thức toán học (KaTeX) ===== --}}
    <div class="modal fade" id="v2EquationModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content shadow-lg border-0" style="border-radius: 12px;">
                <div class="modal-header bg-dark text-white border-0 py-2 px-3">
                    <h5 class="modal-title fw-bold small"><i class="fas fa-square-root-alt me-2"></i> CHÈN CÔNG THỨC
                        TOÁN HỌC</h5>
                    <button type="button" class="close text-white" data-bs-dismiss="modal" data-dismiss="modal"
                        aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="small fw-bold mb-1">Cú pháp LaTeX (VD: \frac{a}{b}, x^2, \sqrt{x}, \bar{X})</label>
                        <textarea id="v2-eq-input" class="form-control" rows="2" placeholder="VD: \bar{X} = \frac{\sum x_i}{n}"></textarea>
                    </div>
                    <div class="small text-muted mb-2">
                        Mẫu nhanh:
                        <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2 v2-eq-tpl"
                            data-tpl="\frac{a}{b}">a/b</button>
                        <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2 v2-eq-tpl"
                            data-tpl="x^{2}">x²</button>
                        <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2 v2-eq-tpl"
                            data-tpl="x_{2}">x₂</button>
                        <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2 v2-eq-tpl"
                            data-tpl="\sqrt{x}">√x</button>
                        <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2 v2-eq-tpl"
                            data-tpl="\bar{X}">X̄</button>
                        <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2 v2-eq-tpl"
                            data-tpl="\pm">±</button>
                        <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2 v2-eq-tpl"
                            data-tpl="\leq">≤</button>
                        <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2 v2-eq-tpl"
                            data-tpl="\geq">≥</button>
                    </div>
                    <label class="small fw-bold mb-1">Xem trước</label>
                    <div id="v2-eq-preview" class="p-3 border rounded bg-light"
                        style="min-height: 60px; font-size: 1.2rem;"></div>
                </div>
                <div class="modal-footer bg-light border-0 py-2">
                    <button type="button" class="btn btn-secondary btn-sm px-4" data-bs-dismiss="modal"
                        data-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-dark btn-sm px-4 fw-bold" id="v2-eq-insert">Chèn công
                        thức</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== Modal: Chèn hình ảnh ===== --}}
    <div class="modal fade" id="v2ImageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg border-0" style="border-radius: 12px;">
                <div class="modal-header bg-dark text-white border-0 py-2 px-3">
                    <h5 class="modal-title fw-bold small"><i class="fas fa-image me-2"></i> CHÈN HÌNH ẢNH</h5>
                    <button type="button" class="close text-white" data-bs-dismiss="modal" data-dismiss="modal"
                        aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <input type="file" id="v2-img-file" class="form-control" accept="image/*">
                    </div>
                    <div class="mb-3 text-center">
                        <img id="v2-img-preview" src="" style="display:none; max-width:100%; max-height:220px;"
                            class="border rounded p-1">
                    </div>
                    <div class="mb-2">
                        <label class="small fw-bold mb-1">Bề rộng hiển thị (%)</label>
                        <input type="range" min="10" max="100" value="60" id="v2-img-width"
                            class="form-range">
                        <div class="text-center small text-muted" id="v2-img-width-label">60%</div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 py-2">
                    <button type="button" class="btn btn-secondary btn-sm px-4" data-bs-dismiss="modal"
                        data-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-dark btn-sm px-4 fw-bold" id="v2-img-insert">Chèn hình
                        ảnh</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== Modal: Chèn bảng (xác định số hàng/cột trước khi chèn tại vị trí con trỏ) ===== --}}
    <div class="modal fade" id="v2TableInsertModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg border-0" style="border-radius: 12px;">
                <div class="modal-header bg-dark text-white border-0 py-2 px-3">
                    <h5 class="modal-title fw-bold small"><i class="fas fa-table me-2"></i> CHÈN BẢNG</h5>
                    <button type="button" class="close text-white" data-bs-dismiss="modal" data-dismiss="modal"
                        aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="small fw-bold mb-1">Số hàng</label>
                            <input type="number" min="1" max="50" value="2" id="v2-table-rows" class="form-control">
                        </div>
                        <div class="col-6">
                            <label class="small fw-bold mb-1">Số cột</label>
                            <input type="number" min="1" max="20" value="3" id="v2-table-cols" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 py-2">
                    <button type="button" class="btn btn-secondary btn-sm px-4" data-bs-dismiss="modal"
                        data-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-dark btn-sm px-4 fw-bold" id="v2-table-insert-confirm">Chèn
                        bảng</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== Modal: Document Property (danh mục tự định nghĩa, giống Word) ===== --}}
    <div class="modal fade" id="v2DocPropModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content shadow-lg border-0" style="border-radius: 12px;">
                <div class="modal-header bg-dark text-white border-0 py-2 px-3">
                    <h5 class="modal-title fw-bold small"><i class="fas fa-tags me-2"></i> DOCUMENT PROPERTY</h5>
                    <button type="button" class="close text-white" data-bs-dismiss="modal" data-dismiss="modal"
                        aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex gap-2 mb-3">
                        <input type="text" id="v2-dp-key" class="form-control form-control-sm"
                            placeholder="Tên thuộc tính (VD: Company)">
                        <input type="text" id="v2-dp-value" class="form-control form-control-sm"
                            placeholder="Giá trị (VD: Cty ABC)">
                        <button type="button" class="btn btn-sm btn-primary px-3 text-nowrap" id="v2-dp-add">
                            <i class="fas fa-plus me-1"></i>Thêm</button>
                    </div>
                    <table class="table table-sm table-bordered align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th style="width: 30%;">Tên thuộc tính</th>
                                <th>Giá trị</th>
                                <th class="text-center" style="width: 110px;">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody id="v2-dp-list"></tbody>
                    </table>
                </div>
                <div class="modal-footer bg-light border-0 py-2">
                    <button type="button" class="btn btn-secondary btn-sm px-4" data-bs-dismiss="modal"
                        data-dismiss="modal">Đóng</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================
     MODAL KẾT NỐI CÂN ĐIỆN TỬ (RS-232 / Web Serial API) — port từ trình soạn thảo V1
     ============================================================ --}}
    <div class="modal fade" id="scaleConnectionModal" tabindex="-1" aria-labelledby="scaleConnectionModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">

                <div class="modal-header py-3 px-4" id="scale-modal-header"
                    style="background: #fffbeb; color: #78350f; border: none; transition: all 0.3s ease;">
                    <div class="d-flex align-items-center gap-3">
                        <div id="scale-modal-icon-container"
                            style="width: 42px; height: 42px; background: rgba(120, 53, 15, 0.1); border-radius: 10px; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease;">
                            <i class="fas fa-balance-scale fa-lg"></i>
                        </div>
                        <div>
                            <h5 class="modal-title mb-0 fw-bold" id="scaleConnectionModalLabel" style="color: inherit;">
                                Kết nối Cân Điện Tử</h5>
                            <div class="small opacity-75" style="color: inherit;">
                                Đọc dữ liệu trực tiếp vào biến số <span id="scale-modal-field-label"
                                    class="fw-bold"></span>
                                <span class="mx-2">|</span>
                                <span id="scale-status-dot" class="scale-status-dot disconnected"
                                    style="display: inline-block; vertical-align: middle;"></span>
                                <span id="scale-status-text" class="small fw-bold ms-1" style="color: inherit;">Chưa kết
                                    nối</span>
                            </div>
                        </div>
                    </div>
                    <div class="ms-auto d-flex align-items-center gap-2">
                        <button id="scale-connect-btn" class="btn btn-sm px-3 fw-bold border-0 shadow-sm"
                            style="background: #16a34a; color: white;" onclick="window.connectScaleFromModal()">
                            <i class="fas fa-plug me-1"></i> Kết nối
                        </button>
                        <button id="scale-disconnect-btn" class="btn btn-sm px-3 fw-bold border-0 shadow-sm d-none"
                            style="background: #dc2626; color: white;" onclick="window.__V2__.ScaleManager.disconnect()">
                            <i class="fas fa-times me-1"></i> Ngắt kết nối
                        </button>
                        <button type="button" class="btn-close ms-2" id="scale-modal-close-btn" data-bs-dismiss="modal"
                            data-dismiss="modal" aria-label="Đóng"></button>
                    </div>
                </div>

                <div class="modal-body p-4" style="background: #f8fafc;">
                    <div class="row g-3">
                        <div class="col-12 mb-2">
                            <div class="text-center p-4 rounded-4 shadow-sm" id="scale-live-card"
                                style="background: #fff; border: 2px solid #e2e8f0; transition: all 0.3s ease;">
                                <div class="small text-muted mb-1 fw-bold text-uppercase"
                                    style="font-size: 0.8rem; letter-spacing: 0.08em;">
                                    <i class="fas fa-satellite-dish me-2 text-primary"></i> Dữ liệu cân (Live)
                                </div>
                                <span id="scale-live-value" class="scale-live-value stable my-2 d-inline-block"
                                    style="font-size: 4.5rem; font-weight: 800; line-height: 1; letter-spacing: -0.02em;">
                                    —.— g
                                </span>
                                <div id="scale-live-status-text" class="small text-muted mt-2 fw-bold"
                                    style="font-size: 0.85rem; height: 1.25rem;">
                                    <i class="fas fa-info-circle me-1"></i> Đang chờ dữ liệu...
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="small fw-bold mb-2 text-muted text-uppercase">
                                    <i class="fas fa-balance-scale me-1"></i> Thiết bị cân (Dữ liệu gốc)
                                </label>
                                <select class="form-select form-select-sm fw-semibold" id="scale-device-select"
                                    onchange="window.onScaleDeviceSelected(this.value)"
                                    style="border-radius: 8px; padding: 0.45rem 0.75rem;">
                                    @if (session('user') && session('user')['userGroup'] == 'Admin')
                                        <option value="">-- Tự nhập cấu hình / Chọn hãng cân --</option>
                                    @endif
                                    @php
                                        $dbScales = \DB::table('instrument')
                                            ->where('type', 'scale')
                                            ->orderBy('code', 'asc')
                                            ->get();
                                    @endphp
                                    @foreach ($dbScales as $dbScale)
                                        <option value="{{ $dbScale->id }}">{{ $dbScale->name }} ({{ $dbScale->code }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            @if (session('user') && session('user')['userGroup'] == 'Admin')
                                <div class="mb-3 text-start">
                                    <button type="button"
                                        class="btn btn-link btn-sm text-decoration-none p-0 text-success fw-semibold"
                                        onclick="window.toggleScaleDetails()">
                                        <i class="fas fa-cog me-1"></i> Cấu hình chi tiết <i
                                            class="fas fa-chevron-down ms-1" id="scale-details-icon"></i>
                                    </button>
                                </div>
                            @endif

                            <div id="scale-hardware-details-container" style="display: none;"
                                class="p-3 bg-white border rounded-3 mb-3">
                                <div class="mb-3">
                                    <label class="small fw-bold mb-2 text-muted text-uppercase">
                                        <i class="fas fa-network-wired me-1"></i> Phương thức kết nối
                                    </label>
                                    <div class="btn-group w-100" role="group" aria-label="Phương thức kết nối">
                                        <input type="radio" class="btn-check" name="scale-connection-type"
                                            id="scale-conn-type-serial" value="serial" checked
                                            onchange="window.onChangeScaleConnectionType('serial')">
                                        <label class="btn btn-outline-success btn-sm w-50 py-2 fw-semibold"
                                            for="scale-conn-type-serial"><i class="fas fa-plug me-1"></i> Cáp vật
                                            lý</label>

                                        <input type="radio" class="btn-check" name="scale-connection-type"
                                            id="scale-conn-type-websocket" value="websocket"
                                            onchange="window.onChangeScaleConnectionType('websocket')">
                                        <label class="btn btn-outline-success btn-sm w-50 py-2 fw-semibold"
                                            for="scale-conn-type-websocket"><i class="fas fa-wifi me-1"></i> WebSocket
                                            (Wifi)</label>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="small fw-bold mb-2 text-muted text-uppercase"><i
                                            class="fas fa-building me-1"></i> Hãng cân</label>
                                    <select class="form-select form-select-sm" id="scale-brand-select"
                                        onchange="window.toggleCustomScaleFields(this.value)">
                                        <option value="and">⚖️ A&D (AND) — Định dạng 17 ký tự</option>
                                        <option value="mettler">🏋️ Mettler Toledo — Giao thức MT-SICS</option>
                                        <option value="sartorius">🔬 Sartorius — Định dạng SBI</option>
                                        <option value="custom">⚙️ Tùy chỉnh (hãng khác)</option>
                                    </select>
                                </div>

                                <div id="scale-websocket-fields" class="d-none mb-3 p-3 rounded-3"
                                    style="background: #f0fdf4; border: 1px solid #bbf7d0;">
                                    <label class="small fw-bold mb-2 text-success-emphasis"
                                        style="color: #15803d !important;">
                                        <i class="fas fa-wifi me-1"></i> Thiết lập WebSocket (Wifi)
                                    </label>
                                    <div class="row g-2">
                                        <div class="col-8">
                                            <label class="x-small text-muted fw-semibold">Địa chỉ IP</label>
                                            <input type="text" class="form-control form-control-sm"
                                                id="scale-websocket-ip" placeholder="Ví dụ: 192.168.1.100"
                                                style="border-radius: 6px;">
                                        </div>
                                        <div class="col-4">
                                            <label class="x-small text-muted fw-semibold">Cổng (Port)</label>
                                            <input type="number" class="form-control form-control-sm"
                                                id="scale-websocket-port" placeholder="8080" style="border-radius: 6px;">
                                        </div>
                                    </div>
                                </div>

                                <div id="scale-custom-fields" class="d-none mb-3 p-3 rounded-3"
                                    style="background: #fffbeb; border: 1px solid #fde68a;">
                                    <label class="small fw-bold mb-2 text-warning-emphasis"><i
                                            class="fas fa-cog me-1"></i> Cài đặt serial tùy chỉnh</label>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <label class="x-small text-muted">Baud Rate</label>
                                            <select class="form-select form-select-sm" id="scale-custom-baud">
                                                <option value="1200">1200</option>
                                                <option value="2400">2400</option>
                                                <option value="4800">4800</option>
                                                <option value="9600" selected>9600</option>
                                                <option value="19200">19200</option>
                                                <option value="38400">38400</option>
                                                <option value="57600">57600</option>
                                                <option value="115200">115200</option>
                                            </select>
                                        </div>
                                        <div class="col-6">
                                            <label class="x-small text-muted">Data Bits</label>
                                            <select class="form-select form-select-sm" id="scale-custom-databits">
                                                <option value="7">7</option>
                                                <option value="8" selected>8</option>
                                            </select>
                                        </div>
                                        <div class="col-6">
                                            <label class="x-small text-muted">Parity</label>
                                            <select class="form-select form-select-sm" id="scale-custom-parity">
                                                <option value="none" selected>None</option>
                                                <option value="even">Even</option>
                                                <option value="odd">Odd</option>
                                            </select>
                                        </div>
                                        <div class="col-6">
                                            <label class="x-small text-muted">Stop Bits</label>
                                            <select class="form-select form-select-sm" id="scale-custom-stopbits">
                                                <option value="1" selected>1</option>
                                                <option value="2">2</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="alert alert-info py-2 px-3 small mb-0" id="scale-serial-info"
                                    style="font-size: 0.78rem; border-left: 3px solid #0ea5e9; background: #f0f9ff; border-color: #bae6fd;">
                                    <i class="fas fa-info-circle me-1"></i>
                                    <strong>Yêu cầu:</strong> Chrome hoặc Edge 89+. Khi bấm <em>"Kết nối"</em>, trình duyệt
                                    sẽ hiển thị hộp thoại chọn cổng COM. Cần cài driver cho USB-to-Serial Adapter nếu sử
                                    dụng.
                                </div>

                                <div class="alert alert-success py-2 px-3 small mb-0 d-none" id="scale-websocket-info"
                                    style="font-size: 0.78rem; border-left: 3px solid #16a34a; background: #f0fdf4; border-color: #bbf7d0;">
                                    <i class="fas fa-info-circle me-1"></i>
                                    <strong>Yêu cầu:</strong> Bộ chuyển đổi Serial-to-Wi-Fi đã bật WebSocket Server. Nhập
                                    chính xác IP và Cổng để kết nối.
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="mb-2">
                                <label class="small fw-bold text-muted text-uppercase mb-1" style="font-size: 0.68rem;">
                                    <i class="fas fa-terminal me-1"></i> Raw Data Log
                                </label>
                                <div id="scale-raw-log">
                                    <div style="color: #64748b; font-size: 0.72rem; font-style: italic; padding: 2px 0;">
                                        Chưa kết nối. Dữ liệu từ cân sẽ hiện tại đây...
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer px-4 py-3" style="background: #f8fafc; border-top: 1px solid #e2e8f0;">
                    <div class="d-flex justify-content-between align-items-center w-100">
                        <div class="small text-muted">
                            <i class="fas fa-lightbulb text-warning me-1"></i>
                            Bạn cần bấm nút <strong>"Đọc giá trị"</strong> để lấy số liệu vào biến số.
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal"
                                data-dismiss="modal">Đóng</button>
                            <button type="button" class="btn btn-success btn-sm px-4 fw-bold"
                                onclick="window.readScaleFromModal()" id="scale-read-now-btn">
                                <i class="fas fa-balance-scale me-1"></i> Đọc giá trị ngay
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <style>
        /* Ẩn hẳn thanh topNAV trong trình thiết kế V2 — nó che mất thanh công cụ.
           Đồng thời bỏ khoảng đệm trên mà layout-navbar-fixed thêm vào để nội dung
           bắt đầu ngay từ đỉnh trang. */
        .main-header.navbar {
            display: none !important;
        }

        .layout-navbar-fixed .wrapper .content-wrapper {
            margin-top: 0 !important;
        }

        .btn-navy {
            background-color: #003A4F;
            border-color: #003A4F;
        }

        .btn-navy:hover {
            background-color: #00506e;
            border-color: #00506e;
        }

        #v2-toolbar .btn.active {
            background-color: #003A4F;
            color: #fff;
        }

        #v2-toolbar .btn:disabled {
            opacity: 0.4;
        }

        #v2-toolbar .v2-fmt-select {
            display: inline-block;
            font-size: 0.78rem;
            padding: 2px 6px;
            height: 30px;
        }

        #v2-toolbar .v2-fmt-select:disabled {
            opacity: 0.4;
        }

        /* Nút chọn màu kiểu Google Docs: icon + vệt màu bên dưới */
        .v2-color-wrap {
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            border-radius: 4px;
            cursor: pointer;
            margin: 0;
            color: #475569;
            font-size: 0.8rem;
            position: relative;
        }

        .v2-color-wrap:hover {
            background: #f1f5f9;
        }

        .v2-color-wrap input[type="color"] {
            width: 18px;
            height: 6px;
            padding: 0;
            border: none;
            cursor: pointer;
            background: none;
        }

        .v2-color-wrap input[type="color"]::-webkit-color-swatch-wrapper {
            padding: 0;
        }

        .v2-color-wrap input[type="color"]::-webkit-color-swatch {
            border: none;
            border-radius: 1px;
        }

        /* Sao chép định dạng (Format Painter) — nút đang bật + con trỏ khi đang chọn đích dán */
        #v2-btn-format-painter.active {
            background-color: #e8f0fe;
            color: #1a73e8;
            box-shadow: 0 0 0 2px #1a73e8 inset;
        }

        body.v2-format-painter-active,
        body.v2-format-painter-active .v2-editable,
        body.v2-format-painter-active td {
            cursor: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 512 512'%3E%3Cpath fill='%231a73e8' d='M352 64h-48V32a32 32 0 0 0-64 0v32H32A32 32 0 0 0 0 96v96a32 32 0 0 0 32 32h288a32 32 0 0 0 32-32v-16h32a16 16 0 0 1 16 16v32a16 16 0 0 1-16 16H272a48 48 0 0 0-48 48v176a48 48 0 0 0 96 0V288h64a80 80 0 0 0 80-80v-32a112 112 0 0 0-112-112z'/%3E%3C/svg%3E") 0 20, crosshair !important;
        }

        /* Nội dung định dạng bên trong editor */
        .v2-editable u {
            text-decoration: underline;
        }

        .v2-editable a {
            color: #2563eb;
            text-decoration: underline;
        }

        .v2-editable mark {
            padding: 0 1px;
        }

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
            position: absolute;
            bottom: 12px;
            right: 20px;
            font-size: 0.7rem;
            color: #94a3b8;
        }

        @media print {
            .v2-page {
                page-break-after: always;
                box-shadow: none !important;
            }

            #v2-toolbar,
            .v2-inserter,
            .v2-field-panel {
                display: none !important;
            }
        }

        /* Block ảo / bị khóa: không cho sửa, có huy hiệu ổ khóa khi rê chuột */
        .v2-block.v2-locked {
            position: relative;
        }

        .v2-block.v2-locked::before {
            content: '\f023  Khối hệ thống (khóa)';
            font-family: 'Font Awesome 5 Free', 'Font Awesome 6 Free', sans-serif;
            font-weight: 900;
            font-size: 0.62rem;
            color: #94a3b8;
            position: absolute;
            top: -14px;
            right: 0;
            opacity: 0;
            transition: opacity 0.15s;
        }

        .v2-block.v2-locked:hover::before {
            opacity: 1;
        }

        .v2-block.v2-locked .v2-editable {
            cursor: default;
        }

        .v2-block.v2-locked .v2-editable:hover {
            box-shadow: none;
        }

        /* Thanh công cụ cố định khi cuộn (JS gắn class này) */
        .v2-toolbar-fixed {
            position: fixed !important;
            top: 0;
            left: 0;
            right: 0;
            z-index: 2000;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.12) !important;
        }

        /* Ẩn topNAV khi cuộn xuống bằng transform để mượt mà, không bị che toolbar */
        body.v2-scrolled .main-header {
            transform: translateY(-100%);
        }

        /* Kéo thay đổi kích thước cột/hàng bảng */
        .v2-table th,
        .v2-table td {
            position: relative;
        }

        .v2-col-resizer {
            position: absolute;
            top: 0;
            right: -3px;
            width: 6px;
            height: 100%;
            cursor: col-resize;
            z-index: 5;
        }

        .v2-col-resizer:hover,
        .v2-col-resizer.resizing {
            background: rgba(14, 165, 233, 0.45);
        }

        .v2-row-resizer {
            position: absolute;
            left: 0;
            bottom: -3px;
            width: 100%;
            height: 6px;
            cursor: row-resize;
            z-index: 5;
        }

        .v2-row-resizer:hover,
        .v2-row-resizer.resizing {
            background: rgba(14, 165, 233, 0.45);
        }

        /* Núm kéo góc dưới-phải: phóng to/thu nhỏ CẢ BẢNG theo tỉ lệ (như Word) */
        .v2-table-sizer {
            position: absolute;
            right: -7px;
            bottom: -7px;
            width: 13px;
            height: 13px;
            background: #fff;
            border: 1.5px solid #64748b;
            border-radius: 3px;
            cursor: nwse-resize;
            opacity: 0;
            transition: opacity 0.15s;
            z-index: 20;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
        }

        .v2-table-wrap:hover .v2-table-sizer {
            opacity: 1;
        }

        .v2-table-sizer:hover,
        .v2-table-sizer.resizing {
            background: #eff6ff;
            border-color: #2563eb;
        }

        /* Vạch chỉ vị trí chèn khi kéo Thiết bị/Thành phần thả trực tiếp lên trang */
        #v2-drop-indicator {
            position: fixed;
            display: none;
            height: 0;
            border-top: 3px solid #0ea5e9;
            border-radius: 2px;
            z-index: 3000;
            pointer-events: none;
            box-shadow: 0 0 6px rgba(14, 165, 233, 0.6);
        }

        /* Kéo-thả từ sidebar: inserter nổi rõ khi đang kéo (chỉ hiện đường kẻ, không hiện nút) */
        body.v2-dragging .v2-inserter {
            opacity: 1;
            height: 30px;
            pointer-events: auto;
        }

        body.v2-dragging .v2-inserter .v2-inserter-btns {
            display: none;
        }

        .v2-inserter.v2-drop-active .v2-inserter-line {
            border-top: 2px solid #0ea5e9;
        }

        .v2-inserter.v2-drop-active .v2-inserter-btns button {
            background: #0ea5e9;
            color: #fff;
            border-color: #0ea5e9;
        }

        /* Thẻ kéo trong sidebar thiết bị / thành phần */
        .v2-drag-card {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 8px 10px;
            margin-bottom: 6px;
            background: #fff;
            cursor: grab;
            font-size: 0.8rem;
        }

        .v2-drag-card:hover {
            border-color: #0ea5e9;
            background: #f0f9ff;
        }

        /* Bootstrap 4 đặt .form-check-input là position:absolute (neo vào panel thay vì
           card) -> checkbox đứng yên khi cuộn danh sách. Ép về static để nằm TRONG card. */
        .v2-drag-card .v2-eq-check {
            position: static;
            margin-left: 0;
            margin-top: 2px;
            flex-shrink: 0;
        }

        .v2-drag-card .fw-bold {
            font-size: 0.8rem;
        }

        .v2-drag-card .small-muted {
            font-size: 0.7rem;
            color: #94a3b8;
        }

        /* Section header — đồng bộ style V1 (icon tròn + gạch gradient) */
        .v2-section {
            position: relative;
            display: flex;
            align-items: center;
            margin: 26px 0 14px;
        }

        /* Toolbar Đổi tên / Tách nhánh phòng / Xóa nhánh — tái dùng style nút của
           .v2-block-toolbar, chỉ khác điều kiện hiện: hover vào section (không phụ thuộc
           activeBlockId vì section không phải .v2-block). */
        .v2-section-toolbar {
            position: absolute;
            top: 2px;
            right: -34px;
            display: flex;
            flex-direction: column;
            gap: 6px;
            z-index: 21;
        }

        .v2-section-toolbar .v2-block-action-btn {
            opacity: 0;
            transition: opacity 0.15s;
        }

        .v2-section:hover .v2-section-toolbar .v2-block-action-btn {
            opacity: 1;
        }

        .v2-section-icon {
            width: 40px;
            height: 40px;
            min-width: 40px;
            border-radius: 50%;
            background: #0dcaf0;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
        }

        .v2-section-body {
            flex: 1;
        }

        .v2-section-title {
            font-size: 1.2rem;
            color: #164e63;
            letter-spacing: 1px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .v2-section-line {
            height: 3px;
            margin-top: 4px;
            border-radius: 2px;
            background: linear-gradient(to right, #0ea5e9, transparent);
        }

        /* Section ẩn tiêu đề (VD: HEADER hệ thống) — vẫn là mốc điều hướng (TOC/click)
           nhưng không chiếm diện tích hiển thị trên tài liệu. */
        .v2-section-notitle {
            margin: 0;
            height: 0;
            overflow: hidden;
        }

        /* ── Tiêu đề 1/2/3: cỡ chữ TƯƠNG ĐỐI (em) hài hòa với cỡ chữ hiện tại, nhưng
              CHẶN TRẦN bằng min() để không bao giờ lớn hơn title section (1.2rem) ── */
        #v2-pages h1 {
            font-size: min(1.35em, 1.15rem);
            font-weight: 700;
            margin: 0.5em 0 0.25em;
        }

        #v2-pages h2 {
            font-size: min(1.2em, 1.05rem);
            font-weight: 700;
            margin: 0.45em 0 0.2em;
        }

        #v2-pages h3 {
            font-size: min(1.1em, 0.98rem);
            font-weight: 700;
            margin: 0.4em 0 0.2em;
        }

        /* Tiêu đề đứng đầu/cuối khối: bỏ margin thừa để khối ôm sát nội dung */
        #v2-pages .v2-editable>h1:first-child,
        #v2-pages .v2-editable>h2:first-child,
        #v2-pages .v2-editable>h3:first-child {
            margin-top: 0;
        }

        #v2-pages .v2-editable>h1:last-child,
        #v2-pages .v2-editable>h2:last-child,
        #v2-pages .v2-editable>h3:last-child {
            margin-bottom: 0;
        }

        /* ── Đánh số tiêu đề tự động (data-hnum do JS tính, ::before hiển thị —
              số KHÔNG nằm trong nội dung lưu, bật/tắt không đổi dữ liệu) ── */
        body.v2-heading-num #v2-pages h1[data-hnum]::before,
        body.v2-heading-num #v2-pages h2[data-hnum]::before,
        body.v2-heading-num #v2-pages h3[data-hnum]::before,
        body.v2-heading-num .v2-section-title[data-hnum]::before {
            content: attr(data-hnum);
        }

        #v2-btn-heading-num.active {
            background-color: #e8f0fe;
            border-color: #2563eb;
            color: #2563eb;
        }

        .v2-block {
            margin-bottom: 10px;
        }

        .v2-editable {
            min-height: 1.4em;
            border-radius: 4px;
            transition: box-shadow 0.15s;
            cursor: text;
        }

        .v2-editable:hover:not(.v2-editing) {
            box-shadow: inset 0 0 0 1px #cbd5e1;
        }

        .v2-editable.v2-editing {
            box-shadow: inset 0 0 0 2px #003A4F;
            background: #fbfdff;
        }

        .v2-editable .ProseMirror {
            outline: none;
            min-height: 1.4em;
        }

        .v2-editable p {
            margin-bottom: 0.35rem;
        }

        .v2-static-text .v2-editable {
            padding: 6px 8px;
        }

        .v2-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            counter-reset: v2-stt-counter;
        }

        /* Cột "STT" tự động — chữ "#STT#" trong ô được thay bằng span này lúc hiển thị
         * (xem replaceSttMarkersV2 trong main.js); số hiện ra do CSS counter đếm theo
         * đúng số span đang có trong bảng, nên thêm/xoá hàng không cần tính lại bằng JS. */
        .v2-css-stt {
            counter-increment: v2-stt-counter;
        }

        .v2-css-stt::before {
            content: counter(v2-stt-counter);
        }

        .v2-table th,
        .v2-table td {
            border: 1px solid #64748b;
            padding: 4px 6px;
            font-size: 0.85rem;
            vertical-align: top;
            word-wrap: break-word;
        }

        /* Cột phụ (không thuộc dữ liệu bảng) chỉ để hiện nút xoá dòng "Cấp 2" —
         * xem showAddRowUI trong renderTable() (main.js). */
        .v2-table th.v2-table-extra-col,
        .v2-table td.v2-table-extra-col {
            width: 28px;
            border: none;
            background: transparent;
            text-align: center;
            vertical-align: middle;
            padding: 0;
        }

        .v2-table-row-del-btn {
            border: none;
            background: transparent;
            color: #dc3545;
            cursor: pointer;
            font-size: 0.95rem;
            line-height: 1;
            padding: 2px;
        }

        .v2-table-row-del-btn:hover {
            color: #a71d2a;
        }

        .v2-table-addrow-wrap {
            margin-top: 6px;
        }

        .v2-table-addrow-btn {
            border: 1px solid #0d6efd;
            background: #fff;
            color: #0d6efd;
            border-radius: 4px;
            font-size: 0.72rem;
            font-weight: 600;
            padding: 2px 10px;
            cursor: pointer;
        }

        .v2-table-addrow-btn:hover {
            background: #0d6efd;
            color: #fff;
        }

        .v2-table th {
            background: #f1f5f9;
            font-weight: 700;
            text-align: center;
        }

        .v2-cell {
            min-height: 1.2em;
        }

        /* ── Con trỏ nhấp nháy tại ĐIỂM CHÈN (click vào trang khi chưa mở editor) ── */
        #v2-insert-caret {
            display: block;
            width: 2px;
            height: 20px;
            background: #1e293b;
            margin: 2px 0 2px 3px;
            animation: v2CaretBlink 1s steps(1) infinite;
            pointer-events: none;
        }

        @keyframes v2CaretBlink {
            50% {
                opacity: 0;
            }
        }

        /* ── Lưới chọn nhanh số hàng x cột (dropdown Chèn bảng, giống Word) ── */
        #v2-table-grid {
            display: grid;
            grid-template-columns: repeat(10, 17px);
            gap: 2px;
            padding: 2px;
        }

        #v2-table-grid .v2-tg-cell {
            width: 17px;
            height: 17px;
            border: 1px solid #cbd5e1;
            background: #fff;
            border-radius: 2px;
            cursor: pointer;
        }

        #v2-table-grid .v2-tg-cell.hot {
            background: #cfe2ff;
            border-color: #2563eb;
        }


        /* Thanh chèn khối giữa các block: ẨN theo yêu cầu (chèn khối bằng nút trên toolbar).
           Vẫn giữ phần tử làm ĐÍCH THẢ khi kéo Thiết bị/Thành phần từ sidebar (body.v2-dragging). */
        .v2-inserter {
            position: relative;
            height: 0;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.15s;
        }

        .v2-inserter-line {
            position: absolute;
            left: 0;
            right: 0;
            top: 50%;
            border-top: 1px dashed #94a3b8;
        }

        .v2-inserter-btns {
            position: relative;
            z-index: 1;
            display: flex;
            gap: 6px;
        }

        .v2-inserter-btns button {
            background: #fff;
            border: 1px solid #cbd5e1;
            border-radius: 14px;
            font-size: 0.72rem;
            font-weight: 600;
            color: #475569;
            padding: 2px 10px;
            cursor: pointer;
            transition: all 0.15s;
        }

        .v2-inserter-btns button:hover {
            background: #003A4F;
            color: #fff;
            border-color: #003A4F;
        }

        .v2-unsupported {
            border: 1px dashed #cbd5e1;
            border-radius: 6px;
            padding: 10px 14px;
            color: #94a3b8;
            font-size: 0.8rem;
            background: #f8fafc;
        }

        /* Badge biến số (cả trong editor lẫn chế độ xem tĩnh) */
        .v2-field-badge {
            display: inline-flex;
            align-items: flex-start;
            background: #fef9c3;
            color: #854d0e;
            border: 1px solid #fde047;
            border-radius: 10px;
            padding: 1px 8px;
            font-size: 0.78em;
            font-weight: 600;
            margin: 0 2px;
            cursor: pointer;
            user-select: none;
            white-space: normal;
            vertical-align: baseline;
            max-width: 100%;
            box-sizing: border-box;
        }

        .v2-field-badge i {
            flex-shrink: 0;
        }

        .v2-field-badge span {
            white-space: normal;
            word-break: break-word;
            overflow-wrap: break-word;
            min-width: 0;
        }

        .v2-field-badge:hover {
            background: #fef08a;
        }

        .v2-editing .v2-field-badge.ProseMirror-selectednode {
            outline: 2px solid #003A4F;
            outline-offset: 1px;
        }

        /* Trạng thái lưu */
        .v2-status {
            font-size: 0.75rem;
            font-weight: 600;
        }

        .v2-status--saved {
            color: #16a34a;
        }

        .v2-status--dirty {
            color: #d97706;
        }

        /* Panel cấu hình biến */
        .v2-field-panel {
            position: fixed;
            top: 70px;
            right: -340px;
            width: 320px;
            background: #fff;
            border-radius: 10px 0 0 10px;
            transition: right 0.25s ease;
            z-index: 1040;
            border: 1px solid #e2e8f0;
            border-right: none;
            display: flex;
            flex-direction: column;
        }

        .v2-field-panel.open {
            right: 0;
        }

        .v2-panel-head {
            background: #003A4F;
            color: #fff;
            font-weight: 700;
            font-size: 0.85rem;
            padding: 10px 14px;
            border-radius: 10px 0 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .v2-panel-head .btn-close-panel {
            background: none;
            border: none;
            color: #fff;
            cursor: pointer;
            padding: 0 4px;
        }

        .v2-panel-body {
            padding: 14px;
        }

        .v2-panel-body label {
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 4px;
        }

        /* ===== Mục lục (TOC) — trượt từ trái ===== */
        .v2-toc {
            position: fixed;
            top: 70px;
            bottom: 20px;
            left: -300px;
            width: 280px;
            background: #fff;
            border-radius: 0 10px 10px 0;
            border: 1px solid #e2e8f0;
            border-left: none;
            transition: left 0.25s ease;
            z-index: 1990;
            overflow: hidden;
        }

        .v2-toc.open {
            left: 0;
        }

        .v2-toc .v2-panel-head {
            border-radius: 0 10px 0 0;
        }

        .v2-toc-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 7px 10px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.82rem;
            font-weight: 600;
            color: #164e63;
        }

        .v2-toc-item:hover {
            background: #e0f2fe;
        }

        .v2-toc-item .v2-toc-page {
            font-size: 0.68rem;
            color: #94a3b8;
            font-weight: 400;
        }

        /* Dòng TIÊU ĐỀ (h1/h2/h3) trong mục lục — nhẹ hơn dòng section, thụt lề theo cấp */
        .v2-toc-item.v2-toc-h {
            font-size: 0.78rem;
            font-weight: 400;
            color: #334155;
            padding-top: 4px;
            padding-bottom: 4px;
        }

        .v2-toc-item.v2-toc-h>span:first-child {
            min-width: 0;
        }

        /* ===== Bình luận (kiểu Google Docs) — trượt từ phải ===== */
        .v2-comments {
            position: fixed;
            top: 70px;
            bottom: 20px;
            right: -360px;
            width: 340px;
            background: #fff;
            border-radius: 10px 0 0 10px;
            border: 1px solid #e2e8f0;
            border-right: none;
            transition: right 0.25s ease;
            z-index: 1990;
            overflow: hidden;
        }

        .v2-comments.open {
            right: 0;
        }

        .v2-comment-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 10px 12px;
            margin-bottom: 10px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.05);
        }

        .v2-comment-card.highlight {
            border-color: #0ea5e9;
            box-shadow: 0 0 0 2px rgba(14, 165, 233, 0.2);
        }

        .v2-comment-author {
            font-weight: 700;
            font-size: 0.8rem;
            color: #164e63;
        }

        .v2-comment-time {
            font-size: 0.68rem;
            color: #94a3b8;
        }

        .v2-comment-text {
            font-size: 0.82rem;
            margin: 4px 0;
            white-space: pre-wrap;
        }

        .v2-comment-block-ref {
            font-size: 0.68rem;
            color: #0369a1;
            background: #e0f2fe;
            border-radius: 8px;
            padding: 1px 8px;
            display: inline-block;
            margin-bottom: 4px;
            cursor: pointer;
        }

        .v2-comment-reply {
            border-left: 3px solid #e2e8f0;
            padding-left: 8px;
            margin-top: 6px;
        }

        .v2-comment-actions {
            display: flex;
            gap: 10px;
            margin-top: 4px;
        }

        .v2-comment-actions a {
            font-size: 0.72rem;
            color: #0369a1;
            cursor: pointer;
            text-decoration: none;
        }

        .v2-comment-actions a.text-danger {
            color: #dc3545;
        }

        /* Toolbar dọc bên phải mỗi block (hiện khi rê chuột): đổi vị trí / xóa / bình luận */
        .v2-block {
            position: relative;
        }

        .v2-block-toolbar {
            position: absolute;
            top: 2px;
            right: -34px;
            display: flex;
            flex-direction: column;
            gap: 6px;
            z-index: 21;
        }

        .v2-comment-btn,
        .v2-block-action-btn {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            border: 1px solid #e2e8f0;
            background: #fff;
            color: #64748b;
            font-size: 0.72rem;
            cursor: pointer;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.15s;
        }

        .v2-block.v2-block-active .v2-comment-btn,
        .v2-block.v2-block-active .v2-block-action-btn {
            opacity: 1;
        }

        .v2-comment-btn:hover,
        .v2-block-action-btn:hover {
            background: #003A4F;
            color: #fff;
        }

        .v2-block.v2-block-active {
            outline: 2px solid rgba(14, 165, 233, 0.4);
            border-radius: 4px;
        }

        .v2-comment-btn .v2-cmt-count {
            position: absolute;
            top: -6px;
            right: -6px;
            background: #f59e0b;
            color: #fff;
            border-radius: 8px;
            font-size: 0.58rem;
            font-weight: 700;
            padding: 0 4px;
            min-width: 14px;
        }

        .v2-comment-btn.has-comments {
            opacity: 1;
            border-color: #f59e0b;
        }

        .v2-block-action-btn:disabled {
            opacity: 0.35 !important;
            cursor: not-allowed;
        }

        .v2-block-action-btn:disabled:hover {
            background: #fff;
            color: #64748b;
        }

        .v2-block-action-btn.v2-block-action-danger:hover {
            background: #dc3545;
            border-color: #dc3545;
            color: #fff;
        }

        /* Chọn khối cho Lặp nhóm */
        .v2-block-action-btn[data-act="pick"].active {
            opacity: 1 !important;
            background: #0ea5e9;
            border-color: #0ea5e9;
            color: #fff;
        }

        .v2-block.v2-block-picked {
            outline: 2px dashed #0ea5e9;
            outline-offset: 2px;
            border-radius: 4px;
        }

        /* ===== Lặp nhóm khối (thiết kế) ===== */
        .v2-loop-group-wrap {
            position: relative;
            border: 2px dashed #0ea5e9;
            border-radius: 10px;
            background: rgba(14, 165, 233, 0.04);
            padding: 26px 18px 18px;
            margin: 18px 0;
        }

        .v2-loop-group-badge {
            position: absolute;
            top: 0;
            left: 16px;
            transform: translateY(-50%);
            background: #0ea5e9;
            color: #fff;
            font-size: 0.76rem;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 6px;
            cursor: pointer;
            z-index: 5;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.15);
        }

        .v2-loop-group-member {
            margin-bottom: 10px;
        }

        .v2-loop-group-member:last-child {
            margin-bottom: 0;
        }

        /* ===== Chế độ CHỌN KHỐI cho Lặp nhóm: click thẳng vào khối ===== */
        #v2-btn-loop-group.active {
            background-color: #e0f2fe;
            color: #0284c7;
            box-shadow: 0 0 0 2px #0ea5e9 inset;
        }

        body.v2-loop-pick-mode .v2-block {
            cursor: copy;
        }

        body.v2-loop-pick-mode .v2-block:hover {
            outline: 2px dashed #94a3b8;
            outline-offset: 2px;
            border-radius: 4px;
        }

        body.v2-loop-pick-mode .v2-block.v2-block-picked:hover {
            outline-color: #0ea5e9;
        }

        #v2-loop-pickbar {
            position: fixed;
            bottom: 24px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            align-items: center;
            gap: 12px;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 999px;
            padding: 8px 18px;
            box-shadow: 0 6px 24px rgba(0, 0, 0, 0.18);
            z-index: 1050;
            font-size: 0.82rem;
            font-weight: 600;
            color: #334155;
        }

        /* ===== Lặp nhóm khối (Chạy thử — tab "Lần i") ===== */
        .v2-loop-tabs-header {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 14px 0;
            flex-wrap: wrap;
        }

        .v2-loop-tab-content>.v2-block {
            margin-bottom: 10px;
        }

        /* ===== CSS cho panel biến số (v2-field-panel) ===== */
        .v2-prop-label {
            display: block;
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 4px;
        }

        .v2-prop-sublabel {
            display: block;
            font-size: 0.68rem;
            font-weight: 600;
            color: #94a3b8;
            margin-bottom: 3px;
        }

        .v2-formula-editor {
            min-height: 72px;
            height: auto;
            background: #fff;
            cursor: text;
            white-space: pre-wrap;
            overflow-wrap: break-word;
            word-break: break-word;
            line-height: 1.6;
        }

        .v2-formula-var {
            display: inline-block;
            background: #dbeafe;
            color: #1e40af;
            border: 1px solid #93c5fd;
            border-radius: 10px;
            padding: 1px 7px;
            font-size: 0.75em;
            font-weight: 700;
            margin: 0 1px;
            cursor: default;
            white-space: nowrap;
        }

        /* Highlight biến khi đang bắt từ màn hình */
        body.v2-select-formula-var-active .v2-field-badge {
            opacity: 0.5;
            transition: all 0.15s;
        }

        body.v2-select-formula-var-active .v2-field-badge:hover {
            opacity: 1;
        }

        body.v2-select-formula-var-active .ebmr-field-badge {
            opacity: 0.5;
            transition: all 0.15s;
        }

        body.v2-select-formula-var-active .ebmr-field-badge:hover {
            opacity: 1;
        }

        /* Biến đang được cài đặt công thức (giống V1: viền xanh dương/tím) */
        .v2-field-badge.v2-formula-var-highlighted,
        .ebmr-field-badge.v2-formula-var-highlighted {
            outline: 2px solid #4f46e5 !important;
            outline-offset: 1px;
            background: rgba(99, 102, 241, 0.12) !important;
            opacity: 1 !important;
        }

        /* Biến được tham chiếu trong công thức — mỗi biến 1 màu riêng (--v2-ref-color do JS gán) */
        .v2-field-badge.v2-formula-var-referenced,
        .ebmr-field-badge.v2-formula-var-referenced {
            outline: 2px solid var(--v2-ref-color, #16a34a) !important;
            outline-offset: 1px;
            box-shadow: 0 0 0 4px color-mix(in srgb, var(--v2-ref-color, #16a34a) 20%, transparent) !important;
            opacity: 1 !important;
        }

        .v2fp-tabs .nav-link {
            border: none;
            border-top: 3px solid transparent;
        }

        .v2fp-tabs .nav-link:not(.active):hover {
            background: #f1f5f9 !important;
        }

        /* ===== CSS cho CHỌN đối tượng (Selection — kiểu Word/Excel) ===== */
        .v2-table td.v2-cell-selected {
            background-color: rgba(59, 130, 246, 0.18) !important;
            outline: 2px solid #2563eb;
            /* viền xanh rõ, nổi trên border ô kề */
            outline-offset: -2px;
            z-index: 5;
            /* td đã position:relative — đè lên ô lân cận */
        }

        .v2-table td.v2-cell-selected .v2-field-badge,
        .v2-table td.v2-cell-selected .ebmr-field-badge {
            outline: 2px solid #2563eb;
            outline-offset: 1px;
            border-radius: 4px;
        }

        body.v2-cell-dragging,
        body.v2-cell-dragging .v2-table {
            user-select: none;
        }

        .v2-table-wrap.v2-table-selected .v2-table {
            outline: 2.5px solid #2563eb;
            outline-offset: 2px;
            border-radius: 2px;
        }

        /* Nút ⊕ chọn cả bảng (góc trên-trái, hiện khi hover — kiểu Word) */
        .v2-table-handle {
            position: absolute;
            top: -13px;
            left: -13px;
            width: 22px;
            height: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fff;
            border: 1px solid #cbd5e1;
            border-radius: 5px;
            color: #64748b;
            font-size: 0.62rem;
            cursor: pointer;
            opacity: 0;
            transition: opacity 0.15s;
            z-index: 20;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.15);
        }

        .v2-table-wrap:hover .v2-table-handle {
            opacity: 1;
        }

        .v2-table-handle:hover {
            background: #eff6ff;
            color: #1d4ed8;
            border-color: #93c5fd;
        }

        /* Gutter chọn HÀNG (dải trái) / CỘT (dải trên) — D-Click để chọn */
        .v2-row-gutter {
            position: absolute;
            left: -16px;
            top: 0;
            bottom: 0;
            width: 16px;
            z-index: 15;
            cursor: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="20" height="14" viewBox="0 0 20 14"><path d="M2 7 L14 1 L14 5 L19 5 L19 9 L14 9 L14 13 Z" transform="rotate(180 10 7)" fill="black"/></svg>') 10 7, e-resize;
        }

        .v2-col-gutter {
            position: absolute;
            top: -14px;
            left: 0;
            right: 0;
            height: 14px;
            z-index: 15;
            cursor: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="14" height="20" viewBox="0 0 14 20"><path d="M7 19 L1 7 L5 7 L5 1 L9 1 L9 7 L13 7 Z" fill="black"/></svg>') 7 10, s-resize;
        }

        .v2-row-gutter:hover {
            background: rgba(59, 130, 246, 0.08);
            border-radius: 3px;
        }

        .v2-col-gutter:hover {
            background: rgba(59, 130, 246, 0.08);
            border-radius: 3px;
        }

        /* Cursor mũi tên đen khi rê gần mép trái/trên của ô (chọn ô) */
        .v2-table td.v2-cur-cellsel {
            cursor: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16"><path d="M3 14 L3 2 L13 10 L8 10 L10 15 L7 16 L6 11 Z" fill="black" stroke="white" stroke-width="1"/></svg>') 3 2, cell;
        }

        /* Khung marquee quét biến số (Ctrl+Alt+kéo) */
        .v2-marquee {
            position: fixed;
            z-index: 999999;
            pointer-events: none;
            background: rgba(59, 130, 246, 0.12);
            border: 1.5px dashed #3b82f6;
            border-radius: 2px;
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

        /* Chạy thử: ẩn TOÀN BỘ thanh công cụ soạn thảo, chỉ giữ lại nút chuyển về Thiết kế
           (nút Lưu cũng ẩn — không có gì để lưu khi đang chỉ điền thử dữ liệu). */
        .execution-mode-active #v2-toolbar > * {
            display: none !important;
        }

        .execution-mode-active #v2-toolbar > .ms-auto {
            display: flex !important;
        }

        .execution-mode-active #v2-toolbar > .ms-auto > #v2-btn-save {
            display: none !important;
        }

        /* Khung ngoài của mỗi biến số khi Chạy thử: chỉ làm container xếp dọc
                                           (control + dòng meta người/giờ bên dưới) — style thật nằm ở control con. */
        .v2-field-badge.v2-field-exec {
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            background: transparent !important;
            border: none !important;
            box-shadow: none !important;
            padding: 0 !important;
            border-radius: 0 !important;
            margin: 0 2px;
            max-width: 100%;
            vertical-align: middle;
            outline: 2px solid #fbbf24;
            outline-offset: 2px;
        }

        /* text / number / date: pill gạch chân chấm, click để nhập/tự điền */
        .v2-exec-input {
            display: inline-block;
            cursor: pointer;
            font-weight: 500;
            border-bottom: 1px dashed #94a3b8;
            padding: 0 4px;
            max-width: 100%;
            white-space: normal;
            word-break: break-word;
        }

        .v2-exec-input:hover {
            background-color: #f8fafc;
            border-bottom-color: #3b82f6;
        }

        .v2-exec-input.out-of-bounds {
            color: #d93025;
            font-weight: bold;
            background-color: #fce8e6;
            border: 1px solid #fad2cf;
            border-radius: 4px;
            padding: 2px 4px;
        }

        .v2-exec-placeholder {
            color: #6c757d;
            font-style: italic;
        }

        /* công thức tự động: chỉ đọc */
        .v2-exec-formula-result {
            font-weight: 600;
            color: #0f766e;
        }

        .v2-exec-formula-result.out-of-bounds {
            color: #d93025;
            font-weight: bold;
            background-color: #fce8e6;
            border: 1px solid #fad2cf;
            border-radius: 4px;
            padding: 2px 4px;
        }

        .v2-formula-preview {
            color: #0f766e;
            font-weight: 600;
            font-size: 0.9em;
        }

        /* lựa chọn: dropdown thật (option cấu hình sẵn hoặc lấy động từ database) */
        .v2-exec-select {
            font-size: 0.85em;
            border: none;
            border-bottom: 1px dashed #94a3b8;
            background: transparent;
            max-width: 100%;
            cursor: pointer;
            padding: 0 2px;
            color: inherit;
        }

        .v2-exec-select:hover {
            border-bottom-color: #3b82f6;
        }

        /* dòng meta người/giờ + nhãn lịch sử thay đổi */
        .v2-exec-meta {
            font-size: 10px;
            color: #6c757d;
            line-height: 1.3;
            margin-top: 2px;
            text-align: center;
            white-space: normal;
        }

        .v2-exec-history-pill {
            display: inline-block;
            margin-left: 4px;
            padding: 0 6px;
            border-radius: 8px;
            background: #fef3c7;
            color: #92400e;
            font-weight: 700;
            cursor: pointer;
        }

        /* Ô tick biến số kiểu Checkbox trong Chế độ Chạy thử */
        .v2-exec-checkbox {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            user-select: none;
            vertical-align: middle;
            white-space: normal;
            max-width: 100%;
        }

        .v2-exec-checkbox-box {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 17px;
            height: 17px;
            flex-shrink: 0;
            border: 1.5px solid #94a3b8;
            border-radius: 4px;
            background: #fff;
            transition: background-color 0.15s, border-color 0.15s;
        }

        .v2-exec-checkbox-box i {
            font-size: 10px;
            color: #fff;
            opacity: 0;
            transform: scale(0.5);
            transition: opacity 0.15s, transform 0.15s;
        }

        .v2-exec-checkbox:hover .v2-exec-checkbox-box {
            border-color: #16a34a;
        }

        .v2-exec-checkbox.is-checked .v2-exec-checkbox-box {
            background: #16a34a;
            border-color: #16a34a;
        }

        .v2-exec-checkbox.is-checked .v2-exec-checkbox-box i {
            opacity: 1;
            transform: scale(1);
        }

        .v2-exec-checkbox-text {
            font-weight: 500;
            color: inherit;
            word-break: break-word;
            overflow-wrap: break-word;
            min-width: 0;
        }

        .v2-exec-checkbox.is-checked .v2-exec-checkbox-text {
            color: #15803d;
        }

        /* Checkbox khoá vì được tự động tick theo công thức — không cho tick tay, nhưng giữ màu xanh nếu checked */
        .v2-exec-checkbox.is-locked {
            cursor: not-allowed;
        }

        .v2-exec-checkbox.is-locked .v2-exec-checkbox-box {
            border-color: #cbd5e1;
        }

        .v2-exec-checkbox.is-locked.is-checked .v2-exec-checkbox-box {
            background: #16a34a;
            border-color: #16a34a;
        }

        .v2-exec-checkbox.is-locked.is-checked .v2-exec-checkbox-text {
            color: #15803d;
        }

        /* ============================================================
               SCALE READER — Nút Đọc Cân & Modal Kết Nối (port từ trình soạn thảo V1)
               ============================================================ */
        .btn-read-scale {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 22px;
            height: 22px;
            border: none;
            border-radius: 50%;
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            color: white;
            font-size: 10px;
            cursor: pointer;
            margin-left: 4px;
            vertical-align: middle;
            transition: all 0.2s ease;
            box-shadow: 0 1px 4px rgba(220, 38, 38, 0.3);
            flex-shrink: 0;
            position: relative;
        }

        .btn-read-scale:hover {
            background: linear-gradient(135deg, #b91c1c, #991b1b);
            box-shadow: 0 2px 8px rgba(220, 38, 38, 0.5);
            transform: scale(1.1);
        }

        .btn-read-scale.reading {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            animation: scale-reading-pulse-red 0.8s ease-in-out infinite;
        }

        .btn-read-scale.reading i {
            animation: v2-scale-spin 1s linear infinite;
        }

        @keyframes scale-reading-pulse-red {

            0%,
            100% {
                box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.6);
            }

            50% {
                box-shadow: 0 0 0 6px rgba(239, 68, 68, 0);
            }
        }

        @keyframes v2-scale-spin {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        .scale-status-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
            flex-shrink: 0;
        }

        .scale-status-dot.connected {
            background: #16a34a;
            box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.25);
            animation: scale-blink 2s ease-in-out infinite;
        }

        .scale-status-dot.disconnected {
            background: #dc2626;
        }

        .scale-status-dot.unstable-dot {
            background: #f59e0b;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.25);
            animation: scale-blink 1s ease-in-out infinite;
        }

        @keyframes scale-blink {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.5;
            }
        }

        .scale-live-value {
            display: block;
            font-size: 2.2rem;
            font-weight: 700;
            font-family: 'Courier New', monospace;
            text-align: center;
            padding: 12px 0;
            letter-spacing: 0.05em;
            transition: color 0.3s ease;
            color: #dc2626;
        }

        .scale-live-value.unstable {
            animation: scale-live-pulse-red 0.5s ease-in-out infinite;
        }

        @keyframes scale-live-pulse-red {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.4;
            }
        }

        #scale-raw-log {
            background: #0f172a;
            color: #94a3b8;
            border-radius: 6px;
            padding: 8px 10px;
            height: 250px;
            overflow-y: auto;
            font-family: 'Courier New', monospace;
            font-size: 0.72rem;
            scrollbar-width: thin;
        }

        @media print {
            .btn-read-scale {
                display: none !important;
            }
        }

        /* "Giấy cân" — phiếu in nhiệt hiển thị khi biến số Số đã có giá trị đọc từ Cân điện tử */
        .v2-scale-receipt {
            margin-top: 4px;
            padding: 6px 10px;
            background: #fff;
            border: 1px solid #111;
            border-radius: 2px;
            text-align: left;
            font-family: 'Courier New', Courier, monospace;
            font-size: 11px;
            color: #000;
            line-height: 1.5;
            width: 100%;
            max-width: 100%;
            box-sizing: border-box;
            box-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
            cursor: pointer;
            display: block;
            position: relative;
            overflow-wrap: break-word;
            word-break: break-word;
        }

        .v2-scale-receipt-head {
            text-align: center;
            font-weight: bold;
            font-size: 12px;
            border-bottom: 1px dashed #111;
            padding-bottom: 4px;
            margin-bottom: 4px;
            overflow-wrap: break-word;
            word-break: break-word;
        }

        .v2-scale-receipt-weight {
            font-size: 14px;
            font-weight: bold;
        }

        .v2-scale-receipt-btn {
            position: absolute;
            top: -10px;
            right: -10px;
            z-index: 10;
        }

        .scale-floating-status {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1040;
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            color: white;
            padding: 8px 16px;
            border-radius: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            display: flex;
            align-items: center;
            gap: 8px;
            font-family: 'Courier New', monospace;
            font-weight: bold;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.15);
        }

        .scale-floating-status:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
            background: rgba(15, 23, 42, 0.95);
            border-color: rgba(22, 163, 74, 0.5);
        }

        /* ===== Badge thực thi ([Tự động lấy thời gian]/[Người thực hiện]/[Người kiểm tra]) — style đồng bộ V1 ===== */
        .v2-page .execution-badge {
            display: inline-flex;
            align-items: center;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            gap: 5px;
            border: 1px solid transparent;
        }

        .v2-page .execution-badge.time {
            color: #17a2b8;
            background-color: #e0f4f7;
        }

        .v2-page .execution-badge.executor {
            color: #6610f2;
            background-color: #f0e6ff;
        }

        .v2-page .execution-badge.checker {
            color: #fd7e14;
            background-color: #fff0e6;
        }

        /* ===== Khối biểu đồ (chart) ===== */
        .v2-chart-container {
            position: relative;
            height: 300px;
            width: 100%;
            padding: 15px;
            background: #fff;
            border: 1px solid #eee;
            border-radius: 8px;
        }

        /* ===== Badge Công thức toán học (KaTeX) ===== */
        .v2-equation-badge {
            display: inline-flex;
            align-items: center;
            padding: 1px 6px;
            border-radius: 4px;
            background: #f8f5ff;
            border: 1px solid #d8c9f7;
            cursor: default;
            vertical-align: middle;
        }

        /* Document Property — chế độ THIẾT KẾ: dạng badge (viền + nền nhạt) để dễ nhận
           ra vị trí đã chèn giữa văn bản khi đang soạn. */
        .v2-docprop-badge {
            display: inline-flex;
            align-items: center;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            color: #0f766e;
            background-color: #e6fbf8;
            border: 1px solid #99e6da;
            cursor: default;
        }

        /* Chạy thử / Thực thi (dữ liệu đã "chốt"): bỏ khung/nền/icon, thừa hưởng
           font/màu/cỡ chữ của đoạn/ô cha — hài hòa với dữ liệu chung như Word. */
        .v2-docprop-badge.v2-docprop-plain {
            display: inline;
            padding: 0;
            border-radius: 0;
            font-size: inherit;
            font-weight: inherit;
            color: inherit;
            background-color: transparent;
            border: none;
        }

        .v2-docprop-badge.v2-docprop-missing {
            color: #dc2626;
        }

        .v2-docprop-badge.v2-docprop-plain.v2-docprop-missing {
            text-decoration: underline dotted;
        }

        /* ===== Hình ảnh chèn trong tài liệu =====
         * Khi đang mount editor, <img> được bọc trong .v2-image-wrap (xem addNodeView
         * trong media-nodes.js) để có tay kéo đổi kích thước ở góc dưới-phải — wrapper
         * chỉ tồn tại lúc đang sửa, HTML lưu lại vẫn là <img> thuần (renderHTML không đổi). */
        .v2-inline-image.ProseMirror-selectednode {
            outline: 2px solid #4285f4;
        }

        .v2-image-wrap {
            position: relative;
            display: block;
            margin: 8px auto;
        }

        .v2-image-wrap .v2-inline-image {
            width: 100%;
            height: auto;
            display: block;
        }

        .v2-image-wrap.v2-image-selected,
        .v2-image-wrap.ProseMirror-selectednode {
            outline: 2px solid #4285f4;
            outline-offset: 2px;
        }

        .v2-image-resize-handle {
            position: absolute;
            right: -7px;
            bottom: -7px;
            width: 14px;
            height: 14px;
            background: #4285f4;
            border: 2px solid #fff;
            border-radius: 50%;
            box-shadow: 0 1px 3px rgba(0,0,0,0.4);
            cursor: nwse-resize;
            opacity: 0;
            transition: opacity 0.15s;
        }

        .v2-image-wrap:hover .v2-image-resize-handle,
        .v2-image-wrap.v2-image-selected .v2-image-resize-handle,
        .v2-image-wrap.ProseMirror-selectednode .v2-image-resize-handle {
            opacity: 1;
        }

        /* Icon/ảnh nhỏ nằm LẪN TRONG câu chữ (giống Word) — dán Ctrl+V trực tiếp 1 ảnh
         * vào văn bản/ô bảng sẽ tạo loại này (v2InlineImage), khác hẳn ảnh block ở trên:
         * kích thước tính bằng px cố định, không phụ thuộc bề rộng cột/bảng. */
        .v2-inline-icon-wrap {
            position: relative;
            display: inline-block;
            vertical-align: middle;
        }

        .v2-inline-icon-wrap .v2-inline-icon {
            display: inline-block;
            vertical-align: middle;
        }

        .v2-inline-icon-wrap.v2-inline-icon-selected,
        .v2-inline-icon-wrap.ProseMirror-selectednode {
            outline: 2px solid #4285f4;
            outline-offset: 1px;
        }

        .v2-inline-icon-resize-handle {
            position: absolute;
            right: -6px;
            bottom: -6px;
            width: 11px;
            height: 11px;
            background: #4285f4;
            border: 2px solid #fff;
            border-radius: 50%;
            box-shadow: 0 1px 3px rgba(0,0,0,0.4);
            cursor: nwse-resize;
            opacity: 0;
            transition: opacity 0.15s;
        }

        .v2-inline-icon-wrap:hover .v2-inline-icon-resize-handle,
        .v2-inline-icon-wrap.v2-inline-icon-selected .v2-inline-icon-resize-handle,
        .v2-inline-icon-wrap.ProseMirror-selectednode .v2-inline-icon-resize-handle {
            opacity: 1;
        }

        /* ===== Chia đôi màn hình (Split View) — trái/phải, kéo thanh giữa để đổi bề rộng ===== */
        .v2-split-wrapper {
            display: flex;
            flex-direction: row;
            width: 100%;
            height: calc(100vh - 60px);
        }

        .v2-split-pane {
            flex: 1 1 50%;
            min-width: 200px;
            overflow-y: auto;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: #f1f3f4;
            min-height: 0;
        }

        .v2-split-pane-header {
            position: sticky;
            top: 0;
            z-index: 5;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 6px 12px;
            background: #fff;
            border-bottom: 1px solid #e2e8f0;
        }

        .v2-split-resizer {
            flex: 0 0 8px;
            margin: 0 3px;
            cursor: col-resize;
            background: #cbd5e1;
            border-radius: 4px;
            transition: background 0.15s;
        }

        .v2-split-resizer:hover,
        .v2-split-resizer.dragging {
            background: #4285f4;
        }

        #v2-split-preview {
            pointer-events: none;
            opacity: 0.92;
        }

        /* Viền bảng (Table Borders) — viền được render inline trên từng ô (main.js),
           bảng luôn border-collapse để cạnh 'hidden' (đã xoá) thắng cạnh của ô kề. */

        /* ── Tìm kiếm & Thay thế (Ctrl+F / Ctrl+H) ── */
        #v2-find-panel {
            position: fixed;
            top: 64px;
            right: 24px;
            z-index: 2000;
            display: none;
            flex-direction: column;
            gap: 6px;
            width: 360px;
            padding: 10px;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.18);
        }

        #v2-find-panel.open {
            display: flex;
        }

        .v2-find-row {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .v2-find-field {
            flex: 1;
            min-width: 0;
            height: 32px;
            padding: 4px 10px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 0.9rem;
            outline: none;
        }

        .v2-find-field:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.15);
        }

        .v2-find-counter {
            min-width: 44px;
            text-align: center;
            font-size: 0.78rem;
            color: #64748b;
            white-space: nowrap;
        }

        .v2-find-btn {
            width: 30px;
            height: 30px;
            border: none;
            background: transparent;
            color: #475569;
            border-radius: 6px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .v2-find-btn:hover {
            background: #f1f5f9;
        }

        .v2-find-case {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            font-size: 0.8rem;
            color: #475569;
            cursor: pointer;
            user-select: none;
            margin: 0;
        }

        .v2-find-textbtn {
            height: 32px;
            padding: 0 12px;
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            color: #1e293b;
            border-radius: 6px;
            font-size: 0.82rem;
            cursor: pointer;
            white-space: nowrap;
        }

        .v2-find-textbtn:hover {
            background: #e2e8f0;
        }

        .v2-find-hit {
            background: #fff2a8;
            border-radius: 2px;
        }

        .v2-find-current {
            background: #ffb300;
            box-shadow: 0 0 0 1px #ff8f00;
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
            docProperties: @json(json_decode($template->doc_properties ?? '{}') ?: (object) []),
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
            isAdmin: @json(session('user') && session('user')['userGroup'] == 'Admin'),
            urls: {
                equipmentList: "{{ route('pages.ebmr.designerEquipmentList') }}",
                templates: "{{ route('pages.ebmr.getTemplates') }}",
                templateBlocksBase: "{{ url('/ebmr/templates') }}", // + /{id}/blocks
                docViewBase: "{{ route('pages.ebmr.viewDocumentByCode', ['code' => '__CODE__']) }}",
                verifyPassword: "{{ route('pages.ebmr.verifyPassword') }}",
                verifyChecker: "{{ route('pages.ebmr.verifyChecker') }}",
                dynamicOptions: "{{ route('pages.ebmr.dynamicOptions') }}",
            },
        };
        // Danh sách thiết bị cân từ DB — dùng bởi scale-reader.js (window.SCALE_DEVICES)
        window.SCALE_DEVICES = @json(\DB::table('instrument')->where('type', 'scale')->get());
        // Nạp lại Danh mục chữ viết tắt (lưu riêng ở cột abbreviations_List, không nằm trong schema.fields)
        (function() {
            let abbrevListStr = @json($template->abbreviations_List ?? '');
            if (!abbrevListStr) return;
            try {
                const abbrevTable = JSON.parse(abbrevListStr);
                const already = window.__V2__.items.some((i) => i.isAbbreviationTable === true);
                if (abbrevTable && !already) {
                    let insertIdx = 0;
                    for (let i = 0; i < window.__V2__.items.length; i++) {
                        if (window.__V2__.items[i].isVirtual) insertIdx = i + 1;
                    }
                    window.__V2__.items.splice(insertIdx, 0, abbrevTable);
                }
            } catch (e) {
                console.error('Lỗi đọc abbreviations_List', e);
            }
        })();
    </script>
    @vite('resources/js/designer-v2/main.js')
@endsection
