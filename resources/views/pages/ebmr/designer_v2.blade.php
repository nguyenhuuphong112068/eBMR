@extends('layout.master')

@section('title', 'eR Editor V2 (TipTap - Beta)')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection

@section('mainContent')
    {{-- Trang THỰC THI LÔ (có $record) dùng chung blade này: class execution-mode-active
         phải gắn từ server vì không có nút toggle để JS tự thêm như lúc Chạy thử.
         readonly-active: hồ sơ đang trong luồng kiểm tra/phê duyệt/ban hành — không được sửa
         nội dung, thanh công cụ chỉ còn bình luận và Chạy thử. --}}
    <div class="content-wrapper @if(!empty($isExecutionMode)) execution-mode-active @endif @if(!empty($isReadOnly)) readonly-active @endif"
        style="background-color: #f1f3f4; min-height: 100vh;">

        {{-- ===== Toolbar V2 ===== --}}
        <div id="v2-toolbar" class="shadow-sm bg-white px-3 py-2 d-flex align-items-center flex-wrap gap-2"
            style="position: sticky; top: 0; z-index: 1030; border-bottom: 1px solid #e2e8f0;">

            <button class="btn btn-sm btn-light" id="v2-btn-toc" title="Mục lục (danh sách công đoạn)">
                <i class="fas fa-list-ul"></i>
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
            <button class="btn btn-sm btn-light" id="v2-btn-link-gf" title="Biểu mẫu chung GF (kéo thả vào tài liệu)"><i
                    class="fas fa-link"></i></button>
            <button class="btn btn-sm btn-light" id="v2-btn-docprop" title="Chèn Document Property"><i
                    class="fas fa-tags"></i></button>
            <button class="btn btn-sm btn-light" id="v2-btn-split" title="Chia đôi màn hình (Split View)"><i
                    class="fas fa-columns"></i></button>
            @php
                // Chỉ hiện nút "Lịch sử thay đổi ấn bản" khi doc này có ấn bản liền kề trước đó
                $hasPrevVersion = \DB::table('ebmr_templates')
                    ->where('caterogy_id', $template->caterogy_id)
                    ->where('type', $template->type)
                    ->where('version', '<', $template->version)
                    ->exists();
            @endphp
            @if ($hasPrevVersion && empty($isExecutionMode))
                <button class="btn btn-sm btn-light" id="v2-btn-version-diff"
                    title="Lịch sử thay đổi ấn bản — so sánh tự động với ấn bản liền kề trước">
                    <i class="fas fa-history text-primary"></i>
                </button>
            @endif

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
                {{-- Bình luận đứng cạnh nhóm hành động bên phải. Nút THÊM bình luận nằm ở menu
                     chuột phải (xem section 5c trong main.js), đây chỉ là ẩn/hiện và điều hướng.
                     Mở từ tab "Nhận Ban Hành" (isIssuanceView): ẩn toàn bộ nút bình luận. --}}
                @if (empty($isIssuanceView))
                    <button class="btn btn-sm btn-light" id="v2-btn-comments" title="Ẩn/hiện toàn bộ bình luận">
                        <i class="fas fa-comment-dots"></i>
                    </button>
                    <button class="btn btn-sm btn-light" id="v2-btn-next-comment" title="Tới bình luận kế tiếp">
                        <i class="fas fa-angle-double-down"></i>
                    </button>
                    <div class="vr mx-1"></div>
                @endif

                {{-- Gạch chéo KHÔNG SỬ DỤNG (N/A): chỉ dùng được lúc Chạy thử/Thực thi và hồ sơ
                     chưa khóa — main.js (na-marks.js) tự ẩn/hiện qua refreshButton(). --}}
                <button id="v2-btn-na-mode" class="btn btn-sm btn-outline-danger px-2 d-none"
                    title="Gạch chéo phần không sử dụng (ghi lý do) — chạm chọn ô / khối / bảng rồi bấm Gạch chéo">
                    <i class="fas fa-ban me-1"></i> Gạch chéo N/A
                </button>

                @if (!empty($record))
                    {{-- ===== Trang THỰC THI LÔ: chỉ có các nút lưu hồ sơ, KHÔNG có toggle Thiết kế ===== --}}
                    {{-- Số lô: đỏ, to, rõ ràng để người ghi chép không nhầm lô --}}
                    <span class="badge bg-danger text-white me-1 px-3 py-2" title="Số lô đang thực thi"
                        style="font-size: 1.1rem; letter-spacing: 1.5px; font-weight: 700;">
                        <i class="fas fa-barcode me-1"></i>{{ $record->batch_number ?? '' }}
                    </span>
                    @if ($activeSectionId ?? false)
                        <a class="btn btn-sm btn-outline-info" href="{{ route('pages.ebmr.execute', $record->id) }}">
                            <i class="fas fa-eye me-1"></i> Xem tất cả công đoạn
                        </a>
                    @endif
                    @if ($isReadOnly)
                        @if ($record->status === 'completed')
                            <button id="v2-btn-record-confirm-read" class="btn btn-sm btn-primary text-white px-2">
                                <i class="fas fa-check-double me-1"></i> Xác nhận đã Đọc hồ sơ
                            </button>
                        @elseif ($record->status === 'reviewed')
                            <span class="badge bg-success p-2"><i class="fas fa-check me-1"></i> Hồ sơ đã được duyệt</span>
                        @endif
                    @else
                        <button id="v2-btn-record-draft" class="btn btn-sm btn-outline-secondary px-2">
                            <i class="fas fa-save me-1"></i> Lưu bản nháp
                        </button>
                        <button id="v2-btn-record-complete" class="btn btn-sm btn-success text-white px-2">
                            <i class="fas fa-check-circle me-1"></i> Hoàn Thành Nhập Liệu
                        </button>
                    @endif
                @else
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
                    @if (!empty($myApprovalWorkflow))
                        {{-- Hồ sơ đang chờ CHÍNH user này duyệt: cho xử lý ngay tại đây,
                             không cần quay về trang "Hồ Sơ Thiết Kế Chờ Bạn Duyệt". --}}
                        @php
                            $myWfRoleLabel = ['reviewer' => 'Kiểm tra', 'approver' => 'Phê duyệt', 'authorizer' => 'Ban hành'][$myApprovalWorkflow->role] ?? 'Duyệt';
                        @endphp
                        <div class="vr mx-1"></div>
                        <button id="v2-btn-approve" class="btn btn-sm btn-success text-white px-3"
                            title="Đồng ý ({{ $myWfRoleLabel }}) hồ sơ này">
                            <i class="fas fa-check me-1"></i> Đồng ý
                        </button>
                        <button id="v2-btn-reject" class="btn btn-sm btn-danger text-white px-3"
                            title="Từ chối hồ sơ — toàn bộ tiến trình trình ký sẽ bị hủy">
                            <i class="fas fa-times me-1"></i> Từ chối
                        </button>
                    @endif
                @endif
            </div>
        </div>

        {{-- ===== Canvas: mỗi section là 1 trang riêng (tự ngắt trang) =====
             #v2-canvas-wrap làm mốc toạ độ (position:relative) cho rail bình luận và lớp SVG vẽ
             đường nối — nhờ vậy card/đường nối chỉ tính lại khi render, không phải bám sự kiện scroll. --}}
        <div id="v2-canvas-wrap">
            <div class="d-flex flex-column align-items-center py-4 gap-4" id="v2-pages"></div>
            <svg id="v2-cmt-links" xmlns="http://www.w3.org/2000/svg"></svg>
            <div id="v2-cmt-rail"></div>
        </div>


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

        {{-- ===== Sidebar Biểu mẫu chung GF (kéo thả chèn liên kết SỐNG theo doc_code) ===== --}}
        <div id="v2-gf" class="v2-toc shadow-lg">
            <div class="v2-panel-head" style="background: #6f42c1;">
                <span><i class="fas fa-link me-2"></i>Biểu mẫu chung (GF)</span>
                <button class="btn-close-panel" data-close-panel="v2-gf"><i class="fas fa-times"></i></button>
            </div>
            <div class="p-2 border-bottom bg-light">
                <input type="text" id="v2-gf-search" class="form-control form-control-sm"
                    placeholder="Tìm biểu mẫu chung...">
                <div class="text-muted mt-1" style="font-size: 0.68rem; font-style: italic;">
                    <i class="fas fa-info-circle me-1"></i>Kéo thẻ thả vào đúng vị trí bất kỳ trong tài liệu.
                </div>
            </div>
            <div id="v2-gf-list" class="v2-panel-body overflow-auto" style="height: calc(100% - 120px);"></div>
        </div>

    </div>

    {{-- ===== Modal: Tạo Bảng KT Khối lượng Trung bình (port từ V1 weightChartCreatorModal) ===== --}}
    <div class="modal fade" id="v2WeightChartModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg border-0" style="border-radius: 12px;">
                <div class="modal-header bg-danger text-white border-0 py-2 px-3">
                    <h5 class="modal-title fw-bold small" id="v2-wc-modal-title"><i class="fas fa-balance-scale me-2"></i> BẢNG KT KHỐI LƯỢNG
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

    {{-- ===== Modal: Lịch sử thay đổi ấn bản (diff tự động với ấn bản liền kề) ===== --}}
    <div class="modal fade" id="v2VersionDiffModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable" style="max-width: 95vw; width: 95vw;">
            <div class="modal-content shadow-lg border-0" style="border-radius: 12px;">
                <div class="modal-header text-white border-0 py-2 px-3" style="background: #1e3a5f;">
                    <h5 class="modal-title fw-bold small"><i class="fas fa-history me-2"></i> LỊCH SỬ THAY ĐỔI ẤN BẢN</h5>
                    <button type="button" class="close text-white" data-bs-dismiss="modal" data-dismiss="modal"
                        aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div id="v2-vdiff-searchbar" class="px-3 py-2 border-bottom bg-white d-flex align-items-center gap-2"
                    hidden>
                    <i class="fas fa-search text-muted small"></i>
                    <input type="text" id="v2-vdiff-search" class="form-control form-control-sm"
                        placeholder="Tìm trong lịch sử thay đổi (tên mục, nội dung, giá trị cũ/mới...)"
                        style="max-width: 420px;">
                    <span id="v2-vdiff-search-count" class="small text-muted"></span>
                </div>
                <div id="v2-vdiff-noresult" class="px-3 py-2 small text-muted" hidden>
                    <i class="fas fa-search me-1"></i>Không tìm thấy kết quả phù hợp với từ khoá đang tìm.
                </div>
                <div class="modal-body" id="v2-vdiff-body" style="background: #f8fafc;">
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-spinner fa-spin me-2"></i>Đang so sánh với ấn bản liền kề…
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 py-2">
                    <button type="button" class="btn btn-secondary btn-sm px-4" data-bs-dismiss="modal"
                        data-dismiss="modal">Đóng</button>
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

        /* Trang chỉ chứa block/section hệ thống (HEADER, PHÊ DUYỆT...): co theo nội dung,
           không ép cao bằng 1 trang A4 để tránh dư khoảng trắng phía dưới. */
        .v2-page.v2-page-auto {
            min-height: 0;
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

        /* Các con dấu chọn lúc ban hành lô: đóng CẠNH NHAU lên góc trên bên phải của mỗi
           phân đoạn (chỉ hiện ở trang thực thi/xem hồ sơ — main.js chỉ gắn khi isExecutionMode).
           Wrapper neo vị trí; từng dấu format do người dùng thiết kế: viền đơn/đôi +
           tối đa 3 dòng (tiêu đề trên / nội dung chính / dòng phụ dưới) + kích thước %. */
        .v2-seal-stamps {
            position: absolute;
            top: -14px;
            right: 0;
            display: flex;
            gap: 12px;
            align-items: flex-start;
            pointer-events: none;
            z-index: 5;
        }

        /* Tem "SỐ LÔ" — góc trên bên TRÁI mỗi phân đoạn (đối xứng với con dấu bên
           phải). Luôn hiện ở hồ sơ đã ban hành (nhận ban hành / thực thi / hoàn
           thành). Màu đỏ, in ra vẫn giữ màu (print-color-adjust). */
        .v2-batch-stamp {
            position: absolute;
            top: -30px;
            left: 0;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 0;
            font-size: 0.8rem;
            font-weight: 800;
            letter-spacing: 0.3px;
            line-height: 1.3;
            color: #dc3545;
            white-space: nowrap;
            pointer-events: none;
            z-index: 6;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .v2-batch-stamp .v2-batch-label {
            font-weight: 700;
        }

        /* Con dấu ban hành đặt TRONG hàng cuối bảng HEADER (xem buildHeaderBatchRowV2
           trong virtual_blocks_v2.blade.php) — dòng chảy bình thường trong ô bảng,
           không định vị tuyệt đối như .v2-seal-stamps (dùng cho phân đoạn). */
        .v2-seal-stamps-row {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
        }

        /* Cột cuối hàng "Số lô" chỉ rộng 25% (1/4 bảng) — nới con dấu để chữ tự xuống
           dòng thay vì bị cắt (khác với con dấu trên phân đoạn, có nguyên chiều rộng). */
        .v2-seal-stamps-row .v2-seal-stamp {
            white-space: normal;
            overflow: visible;
            max-width: 100%;
        }

        .v2-seal-stamp {
            border: 2px solid currentColor;
            border-radius: 6px;
            text-align: center;
            opacity: 0.85;
            white-space: nowrap;
            overflow: hidden;
            /* Kích thước dấu: main.js nhân thêm size (%) đã lưu vào font-size này,
               các dòng bên trong dùng em nên phóng to/thu nhỏ đồng bộ */
            font-size: 0.85rem;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .v2-seal-stamp.seal-border-double {
            border: 4px double currentColor;
        }

        .v2-seal-stamp .seal-line-header {
            display: block;
            font-size: 0.7em;
            font-weight: 700;
            padding: 0.15em 1.1em;
            border-bottom: 1.5px solid currentColor;
        }

        .v2-seal-stamp .seal-line-content {
            display: block;
            font-size: 0.95em;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 0.2em 1em;
        }

        .v2-seal-stamp .seal-line-footer {
            display: block;
            font-size: 0.7em;
            font-weight: 600;
            padding: 0.15em 1.1em;
            border-top: 1.5px solid currentColor;
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

        /* Cầu nối vô hình lấp khoảng trống giữa mép phải section và cụm nút (đặt ở -34px) để
           khi rê chuột từ tiêu đề sang nút KHÔNG bị rớt hover → nút không còn "ẩn rất nhanh".
           Trải suốt chiều cao cụm nút (thường xếp dọc dài hơn section) nên bấm nút dưới cũng dễ. */
        .v2-section-toolbar::before {
            content: '';
            position: absolute;
            top: -6px;
            bottom: -6px;
            left: -44px;
            right: -6px;
            z-index: -1;
        }

        .v2-section-toolbar .v2-block-action-btn {
            opacity: 0;
            transition: opacity 0.15s;
        }

        /* Hiện nút khi hover VÀO section HOẶC vào chính cụm nút (giữ nút sáng suốt lúc di
           chuột tới bấm — cụm nút xếp dọc thường vượt quá đáy section). */
        .v2-section:hover .v2-section-toolbar .v2-block-action-btn,
        .v2-section-toolbar:hover .v2-block-action-btn {
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

        /* Thiết kế: dấu hiệu góc bảng đã bật "Thêm dòng (Cấp 2)" — xem renderTable() (main.js) */
        .v2-table-canaddrows-badge {
            position: absolute;
            top: -9px;
            right: 6px;
            width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #0d6efd;
            color: #fff;
            border-radius: 50%;
            font-size: 0.7rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.25);
            pointer-events: none;
            z-index: 5;
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

        /* ===== Bình luận vùng chọn (kiểu Word) =====
           Card nằm trong rail cạnh phải trang giấy, nối tới đoạn được bình luận bằng
           đường polyline vẽ trên lớp SVG phủ toàn canvas. */
        #v2-canvas-wrap {
            position: relative;
        }

        /* Bật rail: chừa lề phải để trang giấy dịch trái, không bị card đè lên */
        body.v2-cmt-on #v2-pages {
            padding-right: 380px;
            transition: padding-right 0.2s ease;
        }

        #v2-cmt-rail {
            position: absolute;
            top: 0;
            left: 0;
            width: 320px;
            display: none;
            z-index: 6;
        }

        #v2-cmt-links {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            display: none;
            pointer-events: none;
            z-index: 5;
            overflow: visible;
        }

        body.v2-cmt-on #v2-cmt-rail,
        body.v2-cmt-on #v2-cmt-links {
            display: block;
        }

        .v2-cmt-link {
            fill: none;
            stroke: #c4b5fd;
            stroke-width: 1;
        }

        .v2-cmt-link.active {
            stroke: #7c3aed;
            stroke-width: 1.5;
        }

        /* Đoạn văn bản được bình luận */
        .v2-cmt-hl {
            background: #ede9fe;
            border-bottom: 1px solid #c4b5fd;
            cursor: pointer;
        }

        .v2-cmt-hl.active {
            background: #ddd6fe;
        }

        .v2-cmt-hl.v2-cmt-pending {
            background: #fef08a;
            border-bottom-color: #eab308;
        }

        .v2-comment-card {
            position: absolute;
            left: 0;
            width: 320px;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 10px 12px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06);
            transition: top 0.15s ease, box-shadow 0.15s;
            cursor: pointer;
        }

        .v2-comment-card.active {
            border-color: #7c3aed;
            box-shadow: 0 0 0 2px rgba(124, 58, 237, 0.18);
        }

        /* Bình luận không còn tìm thấy đoạn neo (đoạn đã bị xoá/sửa hết) */
        .v2-comment-card.v2-cmt-orphan {
            border-style: dashed;
            border-color: #f59e0b;
            background: #fffbeb;
        }

        .v2-comment-quote {
            font-size: 0.7rem;
            color: #6d28d9;
            background: #f5f3ff;
            border-left: 3px solid #c4b5fd;
            padding: 2px 6px;
            margin-bottom: 6px;
            border-radius: 0 4px 4px 0;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
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

        .v2-cmt-editor textarea {
            font-size: 0.82rem;
            resize: vertical;
        }

        .v2-cmt-editor .v2-cmt-editor-btns {
            display: flex;
            justify-content: flex-end;
            gap: 6px;
            margin-top: 6px;
        }

        /* Bình luận mặc định ẩn -> badge cho biết hồ sơ đang có bao nhiêu bình luận */
        #v2-btn-comments {
            position: relative;
        }

        #v2-btn-comments.active {
            background: #ede9fe;
            color: #6d28d9;
        }

        #v2-btn-comments .v2-cmt-badge {
            position: absolute;
            top: -4px;
            right: -4px;
            background: #7c3aed;
            color: #fff;
            border-radius: 8px;
            font-size: 0.58rem;
            font-weight: 700;
            line-height: 1.3;
            padding: 0 4px;
            min-width: 14px;
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

        .v2-block.v2-block-active .v2-block-action-btn {
            opacity: 1;
        }

        .v2-block-action-btn:hover {
            background: #003A4F;
            color: #fff;
        }

        .v2-block.v2-block-active {
            outline: 2px solid rgba(14, 165, 233, 0.4);
            border-radius: 4px;
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

        /* Luồng kiểm tra / phê duyệt / ban hành (chỉ-đọc): mọi công cụ SOẠN THẢO đều vô nghĩa
           vì nội dung không được sửa. Chỉ giữ nhóm bên phải — nó chứa nút bình luận (để góp ý)
           và các nút hành động (Chạy thử / Xác nhận đã đọc...). */
        .readonly-active #v2-toolbar > * {
            display: none !important;
        }

        /* margin-left:auto phải đặt tường minh: bình thường nhóm này bị đám nút soạn thảo đẩy
           sang phải nên trông như đã canh phải, nhưng khi ẩn hết thì nó tụt về sát lề trái. */
        .readonly-active #v2-toolbar > .ms-auto {
            display: flex !important;
            margin-left: auto !important;
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

        /* ===== Gạch chéo "KHÔNG SỬ DỤNG" (N/A) — xem na-marks.js ===== */
        /* Lớp phủ 2 đường chéo đỏ (SVG để in ấn được) + chip lý do */
        td.v2-na-cell { position: relative; }
        .v2-na-block { position: relative; }

        .v2-na-x {
            position: absolute;
            inset: 0;
            z-index: 40;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            background: rgba(220, 53, 69, 0.04);
        }

        .v2-na-x svg {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
        }

        .v2-na-x svg line {
            stroke: #dc3545;
            stroke-width: 1.6;
        }

        .v2-na-reason {
            position: relative;
            z-index: 1;
            max-width: 94%;
            background: #fff;
            border: 1px solid #f1aeb5;
            color: #b02a37;
            border-radius: 4px;
            padding: 1px 6px;
            font-size: 0.68rem;
            font-weight: 600;
            line-height: 1.3;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.08);
        }

        .v2-na-x-block .v2-na-reason {
            font-size: 0.82rem;
            padding: 4px 12px;
            white-space: normal;
            text-align: center;
        }

        .v2-na-x-block .v2-na-reason small {
            display: block;
            font-weight: 400;
            color: #6c757d;
            font-size: 0.7rem;
        }

        /* Nội dung dưới lớp gạch chéo mờ đi — thấy được nhưng rõ là không còn hiệu lực */
        td.v2-na-cell > .v2-cell,
        .v2-na-block > :not(.v2-na-x) {
            opacity: 0.55;
        }

        /* — Chế độ gạch chéo (chọn bằng chạm, tối ưu máy tính bảng) — */
        body.v2-na-mode #v2-pages {
            cursor: pointer;
        }

        body.v2-na-mode #v2-pages td[data-row],
        body.v2-na-mode #v2-pages .v2-block {
            -webkit-user-select: none;
            user-select: none;
        }

        .v2-na-picked {
            outline: 3px dashed #0d6efd !important;
            outline-offset: -3px;
            background-color: rgba(13, 110, 253, 0.08) !important;
        }

        /* Thanh hành động đáy màn hình — nút cỡ ngón tay (>=46px) */
        #v2-na-bar {
            position: fixed;
            left: 50%;
            transform: translateX(-50%);
            bottom: 14px;
            z-index: 10500;
            display: none;
            flex-wrap: wrap;
            justify-content: center;
            align-items: center;
            gap: 8px;
            max-width: 96vw;
            padding: 10px 12px;
            background: #212529;
            border-radius: 14px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.35);
        }

        #v2-na-bar.show { display: flex; }

        #v2-na-bar button {
            min-height: 46px;
            min-width: 56px;
            border: none;
            border-radius: 10px;
            background: #495057;
            color: #fff;
            font-size: 0.95rem;
            font-weight: 500;
            padding: 6px 14px;
            touch-action: manipulation;
        }

        #v2-na-bar button:disabled { opacity: 0.35; }
        #v2-na-bar button:not(:disabled):active { filter: brightness(1.25); }
        #v2-na-bar .v2-na-btn-danger { background: #dc3545; font-weight: 700; }
        #v2-na-bar .v2-na-btn-done { background: #198754; font-weight: 700; }

        #v2-na-bar .v2-na-bar-info {
            color: #ffc107;
            font-weight: 600;
            padding: 0 8px;
            white-space: nowrap;
        }

        @media print {
            #v2-na-bar { display: none !important; }
            .v2-na-x { cursor: default; background: transparent; }
            .v2-na-picked { outline: none !important; background: transparent !important; }
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
        /* ===== Lịch sử thay đổi ấn bản (version diff) ===== */
        #v2-vdiff-body .vdiff-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 4px;
        }

        #v2-vdiff-body .vdiff-legend {
            font-size: 12px;
            color: #64748b;
            margin-bottom: 12px;
        }

        #v2-vdiff-body .vdiff-chip {
            display: inline-block;
            font-size: 12px;
            font-weight: 600;
            border-radius: 999px;
            padding: 2px 10px;
            margin-left: 6px;
        }

        #v2-vdiff-body .vdiff-chip.vdiff-added { background: #dcfce7; color: #166534; }
        #v2-vdiff-body .vdiff-chip.vdiff-removed { background: #fee2e2; color: #991b1b; }
        #v2-vdiff-body .vdiff-chip.vdiff-modified { background: #fef9c3; color: #854d0e; }

        #v2-vdiff-body .vdiff-group-title {
            margin: 18px 0 8px;
            padding-bottom: 4px;
            border-bottom: 2px solid #e2e8f0;
            color: #1e3a5f;
            font-weight: 700;
            font-size: 13px;
            text-transform: uppercase;
        }

        #v2-vdiff-body .vdiff-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-left: 4px solid #94a3b8;
            border-radius: 8px;
            padding: 10px 12px;
            margin-bottom: 8px;
            font-size: 13px;
        }

        #v2-vdiff-body .vdiff-card.vdiff-added { border-left-color: #22c55e; }
        #v2-vdiff-body .vdiff-card.vdiff-removed { border-left-color: #ef4444; }
        #v2-vdiff-body .vdiff-card.vdiff-modified { border-left-color: #eab308; }

        #v2-vdiff-body .vdiff-card-title {
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 4px;
        }

        #v2-vdiff-body .vdiff-kind {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            border-radius: 4px;
            padding: 1px 6px;
            margin-right: 6px;
        }

        #v2-vdiff-body .vdiff-added .vdiff-kind { background: #dcfce7; color: #166534; }
        #v2-vdiff-body .vdiff-removed .vdiff-kind { background: #fee2e2; color: #991b1b; }
        #v2-vdiff-body .vdiff-modified .vdiff-kind { background: #fef9c3; color: #854d0e; }

        #v2-vdiff-body .vdiff-type {
            font-size: 11px;
            color: #64748b;
            background: #f1f5f9;
            border-radius: 4px;
            padding: 1px 6px;
            margin-right: 4px;
        }

        #v2-vdiff-body .vdiff-text {
            word-break: break-word;
            background: #f8fafc;
            border-radius: 6px;
            padding: 6px 8px;
            margin-top: 4px;
            line-height: 1.6;
        }

        #v2-vdiff-body .vdiff-line {
            white-space: pre-wrap;
        }

        #v2-vdiff-body .vdiff-line.vdiff-ctx {
            color: #94a3b8;
        }

        #v2-vdiff-body .vdiff-line.vdiff-chgline {
            background: #fefce8;
            border-radius: 4px;
        }

        #v2-vdiff-body .vdiff-fold {
            display: block;
            width: 100%;
            border: 1px dashed #cbd5e1;
            background: #f1f5f9;
            color: #64748b;
            font-size: 11px;
            border-radius: 4px;
            padding: 2px 8px;
            margin: 3px 0;
            cursor: pointer;
            text-align: center;
        }

        #v2-vdiff-body .vdiff-fold:hover {
            background: #e2e8f0;
            color: #334155;
        }

        #v2-vdiff-body .vdiff-hidden {
            white-space: pre-wrap;
        }

        #v2-vdiff-body .vdiff-skip {
            color: #94a3b8;
            font-weight: 600;
        }

        #v2-vdiff-body mark.vdiff-hit {
            background: #fde047;
            color: #713f12;
            border-radius: 2px;
            padding: 0 1px;
        }

        #v2-vdiff-body .vdiff-prop {
            margin-top: 4px;
            color: #334155;
        }

        #v2-vdiff-body ins {
            background: #dcfce7;
            color: #14532d;
            text-decoration: none;
            border-radius: 3px;
            padding: 0 2px;
        }

        #v2-vdiff-body del {
            background: #fee2e2;
            color: #7f1d1d;
            border-radius: 3px;
            padding: 0 2px;
        }
    </style>
@endsection

@section('script')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @if (!empty($myApprovalWorkflow))
        {{-- Đồng ý / Từ chối hồ sơ ngay trong trình soạn thảo (cùng endpoint với trang Phê Duyệt) --}}
        <script>
            (function() {
                const processUrl = "{{ route('pages.ebmr.processApproval') }}";
                const approvalsUrl = "{{ route('pages.ebmr.approvals') }}";

                function handleApproval(action) {
                    const isApprove = action === 'approve';
                    Swal.fire({
                        title: isApprove ? 'Đồng ý phê duyệt hồ sơ?' : 'Từ chối hồ sơ?',
                        html: isApprove ? '' :
                            '<div class="text-danger" style="font-size:0.9rem;">Khi từ chối, toàn bộ tiến trình trình ký của hồ sơ sẽ bị hủy bỏ và hồ sơ chuyển về trạng thái Nháp.</div>',
                        icon: isApprove ? 'question' : 'warning',
                        input: 'textarea',
                        inputPlaceholder: 'Ý kiến / Ghi chú (không bắt buộc)...',
                        showCancelButton: true,
                        confirmButtonText: isApprove ? 'Phê Duyệt' : 'Từ Chối',
                        cancelButtonText: 'Hủy',
                        confirmButtonColor: isApprove ? '#28a745' : '#dc3545',
                        showLoaderOnConfirm: true,
                        allowOutsideClick: () => !Swal.isLoading(),
                        preConfirm: (comment) => fetch(processUrl, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': "{{ csrf_token() }}",
                                    'Accept': 'application/json',
                                },
                                body: JSON.stringify({
                                    workflow_id: {{ $myApprovalWorkflow->id }},
                                    workflow_type: 'ebmr',
                                    action: action,
                                    comment: comment || null,
                                }),
                            })
                            .then((r) => r.json())
                            .catch(() => ({ success: false, message: 'Có lỗi xảy ra khi thực hiện xử lý.' })),
                    }).then((result) => {
                        if (!result.isConfirmed) return;
                        const res = result.value || {};
                        if (res.success) {
                            Swal.fire('Thành công', res.message, 'success').then(() => {
                                window.location.href = approvalsUrl;
                            });
                        } else {
                            Swal.fire('Lỗi', res.message || 'Có lỗi xảy ra khi thực hiện xử lý.', 'error');
                        }
                    });
                }

                document.getElementById('v2-btn-approve')?.addEventListener('click', () => handleApproval('approve'));
                document.getElementById('v2-btn-reject')?.addEventListener('click', () => handleApproval('reject'));
            })();
        </script>
    @endif
    {{-- Virtual blocks (header/phê duyệt/công thức...) — trích nguyên văn logic V1 --}}
    @include('pages.ebmr.designer.scripts.virtual_blocks_v2')
    <script>
        window.__V2__ = {
            templateId: {{ $template->id }},
            items: @json($template->schema->fields ?? []),
            fieldsConfig: @json($template->schema->fieldsConfig ?? (object) []),
            isReadOnly: @json($isReadOnly),
            // Trang THỰC THI LÔ (mở từ /ebmr/execute/{id}) dùng chung blade này với Designer:
            // isExecutionMode bật sẵn từ server + recordId để main.js chuyển sang luồng lưu hồ sơ lô.
            isExecutionMode: @json(!empty($isExecutionMode)),
            recordId: @json($record->id ?? null),
            // Số thứ tự THẬT của công đoạn trong toàn hồ sơ gốc, chỉ có khi mở qua ?section=
            // (VD: xem trước 1 công đoạn trong modal Phân phối) — bù trừ cho updateHeadingNumbersV2()
            // vì DOM lúc này chỉ còn đúng 1 công đoạn nên đếm lại sẽ luôn ra "1." nếu không bù.
            activeSectionNumber: @json($activeSectionNumber ?? null),
            recordStatus: @json($record->status ?? null),
            batchNumber: @json($record->batch_number ?? null),
            // Các con dấu chọn lúc ban hành — đóng cạnh nhau lên góc trên bên phải mỗi phân đoạn
            recordSeals: @json($recordSeals ?? []),
            executionValues: @json($executionValues ?? (object) []),
            recordStructures: @json($recordStructures ?? (object) []),
            pageOrientation: @json($template->schema->pageOrientation ?? 'portrait'),
            docProperties: @json(json_decode($template->doc_properties ?? '{}') ?: (object) []),
            saveUrl: "{{ route('pages.ebmr.storeTemplate') }}",
            csrf: "{{ csrf_token() }}",
            comments: @json($comments ?? []),
            commentUrls: {
                store: "{{ route('pages.ebmr.storeComment') }}",
                reply: "{{ route('pages.ebmr.replyComment') }}",
                remove: "{{ route('pages.ebmr.deleteComment') }}",
                reanchor: "{{ route('pages.ebmr.reanchorComment') }}",
            },
            importantVars: @json($importantVars ?? []),
            currentUserName: @json(session('user')['fullName'] ?? ''),
            templateDepartmentCode: @json($template->department_code ?? ''),
            isAdmin: @json(session('user') && session('user')['userGroup'] == 'Admin'),
            urls: {
                equipmentList: "{{ route('pages.ebmr.designerEquipmentList') }}",
                templates: "{{ route('pages.ebmr.getTemplates') }}",
                templateBlocksBase: "{{ url('/ebmr/templates') }}", // + /{id}/blocks
                gfBlocksByDocCode: "{{ route('pages.ebmr.getGfBlocksByDocCode') }}", // ?doc_code=...
                docViewBase: "{{ route('pages.ebmr.viewDocumentByCode', ['code' => '__CODE__']) }}",
                verifyPassword: "{{ route('pages.ebmr.verifyPassword') }}",
                verifyChecker: "{{ route('pages.ebmr.verifyChecker') }}",
                dynamicOptions: "{{ route('pages.ebmr.dynamicOptions') }}",
                updateRecordData: "{{ route('pages.ebmr.updateRecordData') }}",
                saveRecordStructure: "{{ route('pages.ebmr.saveRecordStructure') }}",
                runDataHistoryBase: "{{ url('/ebmr/run-data-history') }}", // + /{record}/{blockUuid}/{cellId}
                recordsIndex: "{{ route('pages.ebmr.indexRecords') }}",
                versionDiff: "{{ route('pages.ebmr.getVersionDiff', ['id' => $template->id]) }}",
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
    <script>
        // ===== Lịch sử thay đổi ấn bản: so sánh tự động với ấn bản liền kề =====
        (function() {
            const btn = document.getElementById('v2-btn-version-diff');
            if (!btn) return;
            const body = document.getElementById('v2-vdiff-body');
            const loadingHtml = '<div class="text-center text-muted py-5">' +
                '<i class="fas fa-spinner fa-spin me-2"></i>Đang so sánh với ấn bản liền kề…</div>';

            const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
            }[c]));

            // LCS tổng quát trên mảng chuỗi -> chuỗi thao tác [type, value]
            // type: eq (giữ nguyên) | del (xoá) | ins (thêm)
            function lcsOps(a, b) {
                const m = a.length, n = b.length;
                const dp = new Uint32Array((m + 1) * (n + 1));
                for (let i = m - 1; i >= 0; i--) {
                    for (let j = n - 1; j >= 0; j--) {
                        dp[i * (n + 1) + j] = a[i] === b[j] ?
                            dp[(i + 1) * (n + 1) + j + 1] + 1 :
                            Math.max(dp[(i + 1) * (n + 1) + j], dp[i * (n + 1) + j + 1]);
                    }
                }
                let i = 0, j = 0;
                const ops = [];
                while (i < m && j < n) {
                    if (a[i] === b[j]) { ops.push(['eq', a[i]]); i++; j++; }
                    else if (dp[(i + 1) * (n + 1) + j] >= dp[i * (n + 1) + j + 1]) { ops.push(['del', a[i]]); i++; }
                    else { ops.push(['ins', b[j]]); j++; }
                }
                while (i < m) ops.push(['del', a[i++]]);
                while (j < n) ops.push(['ins', b[j++]]);
                return ops;
            }

            let foldSeq = 0;
            function foldButton(hiddenHtml, label) {
                const id = 'vdf_' + (++foldSeq);
                return '<button type="button" class="vdiff-fold" data-vdiff-fold="' + id + '">⋯ ' +
                    label + ' — bấm để xem ⋯</button>' +
                    '<div class="vdiff-hidden" id="' + id + '" hidden>' + hiddenHtml + '</div>';
            }

            // Diff theo TỪ trong 1 dòng đã bị sửa; đoạn không đổi quá dài rút gọn bằng "…"
            function wordDiffLine(oldLine, newLine) {
                const a = String(oldLine).split(/(\s+)/).filter((t) => t !== '');
                const b = String(newLine).split(/(\s+)/).filter((t) => t !== '');
                if (a.length * b.length > 250000) {
                    return '<del>' + esc(oldLine) + '</del> <ins>' + esc(newLine) + '</ins>';
                }
                // Gộp các thao tác cùng loại liền nhau thành đoạn
                const segs = [];
                lcsOps(a, b).forEach(([t, v]) => {
                    if (segs.length && segs[segs.length - 1].t === t) segs[segs.length - 1].v += v;
                    else segs.push({ t, v });
                });
                return segs.map((s, idx) => {
                    if (s.t === 'del') return '<del>' + esc(s.v) + '</del>';
                    if (s.t === 'ins') return '<ins>' + esc(s.v) + '</ins>';
                    // Đoạn giữ nguyên quá dài: giữ đầu/cuối đủ ngữ cảnh, rút gọn phần giữa
                    const toks = s.v.split(/(\s+)/).filter((t) => t !== '');
                    if (toks.length <= 24) return esc(s.v);
                    const head = idx === 0 ? '' : esc(toks.slice(0, 10).join(''));
                    const tail = idx === segs.length - 1 ? '' : esc(toks.slice(-10).join(''));
                    return head + ' <span class="vdiff-skip" title="Đoạn không thay đổi đã được rút gọn">(…)</span> ' + tail;
                }).join('');
            }

            const line = (cls, inner) => '<div class="vdiff-line' + (cls ? ' ' + cls : '') + '">' + inner + '</div>';

            // Khối chỉ có 1 phía (block thêm mới / xoá hẳn): hiện tối đa 8 dòng đầu, còn lại gấp gọn
            function oneSideHtml(text, tag) {
                const lines = String(text).split('\n');
                if (lines.length <= 10) {
                    return line('', '<' + tag + '>' + esc(text) + '</' + tag + '>');
                }
                return line('', '<' + tag + '>' + esc(lines.slice(0, 8).join('\n')) + '</' + tag + '>') +
                    foldButton(line('', '<' + tag + '>' + esc(lines.slice(8).join('\n')) + '</' + tag + '>'),
                        (lines.length - 8) + ' dòng nữa');
            }

            // Hiển thị RÚT GỌN kiểu unified-diff: diff theo DÒNG (mỗi hàng bảng = 1 dòng),
            // chỉ hiện dòng có thay đổi + 1 dòng ngữ cảnh; dòng không đổi gấp lại được.
            const CONTEXT_LINES = 1;
            function textDiffHtml(oldText, newText) {
                if (oldText == null && newText == null) return '';
                let inner;
                if (oldText == null) inner = oneSideHtml(newText, 'ins');
                else if (newText == null) inner = oneSideHtml(oldText, 'del');
                else {
                    const a = String(oldText).split('\n');
                    const b = String(newText).split('\n');
                    if (a.length * b.length > 4000000) {
                        inner = oneSideHtml(oldText, 'del') + oneSideHtml(newText, 'ins');
                    } else {
                        // Ghép run del + run ins liền kề thành cặp "dòng bị sửa" để diff theo từ
                        const ops = lcsOps(a, b);
                        const items = [];
                        let k = 0;
                        while (k < ops.length) {
                            if (ops[k][0] === 'eq') { items.push({ t: 'eq', s: ops[k][1] }); k++; continue; }
                            const dels = [], inss = [];
                            while (k < ops.length && ops[k][0] === 'del') { dels.push(ops[k][1]); k++; }
                            while (k < ops.length && ops[k][0] === 'ins') { inss.push(ops[k][1]); k++; }
                            const np = Math.min(dels.length, inss.length);
                            for (let x = 0; x < np; x++) items.push({ t: 'chg', a: dels[x], b: inss[x] });
                            for (let x = np; x < dels.length; x++) items.push({ t: 'del', s: dels[x] });
                            for (let x = np; x < inss.length; x++) items.push({ t: 'ins', s: inss[x] });
                        }

                        const parts = [];
                        let eqRun = [];
                        let sawChange = false;
                        const flushEq = (changeFollows) => {
                            if (!eqRun.length) return;
                            const keepStart = sawChange ? CONTEXT_LINES : 0;
                            const keepEnd = changeFollows ? CONTEXT_LINES : 0;
                            if (eqRun.length <= keepStart + keepEnd + 1) {
                                eqRun.forEach((s) => parts.push(line('vdiff-ctx', esc(s))));
                            } else {
                                eqRun.slice(0, keepStart).forEach((s) => parts.push(line('vdiff-ctx', esc(s))));
                                const hidden = eqRun.slice(keepStart, eqRun.length - keepEnd || undefined);
                                parts.push(foldButton(
                                    hidden.map((s) => line('vdiff-ctx', esc(s))).join(''),
                                    hidden.length + ' dòng không thay đổi'));
                                if (keepEnd) eqRun.slice(-keepEnd).forEach((s) => parts.push(line('vdiff-ctx', esc(s))));
                            }
                            eqRun = [];
                        };
                        items.forEach((it) => {
                            if (it.t === 'eq') { eqRun.push(it.s); return; }
                            flushEq(true);
                            sawChange = true;
                            if (it.t === 'chg') parts.push(line('vdiff-chgline', wordDiffLine(it.a, it.b)));
                            else if (it.t === 'del') parts.push(line('', '<del>' + esc(it.s) + '</del>'));
                            else parts.push(line('', '<ins>' + esc(it.s) + '</ins>'));
                        });
                        flushEq(false);
                        inner = parts.join('');
                    }
                }
                return '<div class="vdiff-text">' + inner + '</div>';
            }

            const KIND = {
                added: { label: 'Thêm mới', cls: 'vdiff-added', icon: 'fa-plus-circle' },
                removed: { label: 'Đã xoá', cls: 'vdiff-removed', icon: 'fa-minus-circle' },
                modified: { label: 'Chỉnh sửa', cls: 'vdiff-modified', icon: 'fa-pen' },
            };

            function propRows(changes) {
                return (changes || []).map((pc) =>
                    '<div class="vdiff-prop"><b>' + esc(pc.label) + ':</b> <del>' + esc(pc.old) +
                    '</del> → <ins>' + esc(pc.new) + '</ins></div>'
                ).join('');
            }

            function simpleCard(kind, title, typeLabel, extra) {
                const k = KIND[kind];
                return '<div class="vdiff-card ' + k.cls + '"><div class="vdiff-card-title">' +
                    '<i class="fas ' + k.icon + ' me-1"></i><span class="vdiff-kind">' + k.label + '</span>' +
                    (typeLabel ? '<span class="vdiff-type">' + esc(typeLabel) + '</span> ' : '') +
                    esc(title) + '</div>' + (extra || '') + '</div>';
            }

            function chip(n, label, cls) {
                return n ? '<span class="vdiff-chip ' + cls + '">' + n + ' ' + label + '</span>' : '';
            }

            // Bọc 1 nhóm (tiêu đề + các thẻ thay đổi bên trong) để tìm kiếm có thể
            // ẩn/hiện CẢ NHÓM khi không có thẻ nào khớp từ khoá. Nhóm rỗng bỏ qua.
            function group(headerHtml, cardsHtml) {
                const cards = cardsHtml.filter(Boolean);
                if (!cards.length) return '';
                return '<div class="vdiff-group">' + headerHtml + cards.join('') + '</div>';
            }

            const searchBar = document.getElementById('v2-vdiff-searchbar');
            const searchInput = document.getElementById('v2-vdiff-search');
            const searchCountEl = document.getElementById('v2-vdiff-search-count');
            const noResultEl = document.getElementById('v2-vdiff-noresult');
            let cardOriginalHtml = new WeakMap();

            function cacheCardHtml() {
                cardOriginalHtml = new WeakMap();
                body.querySelectorAll('.vdiff-card').forEach((c) => cardOriginalHtml.set(c, c.innerHTML));
            }

            function render(d) {
                searchBar.hidden = true;
                noResultEl.hidden = true;
                if (!d.has_previous) {
                    body.innerHTML = '<div class="alert alert-info m-0">' + esc(d.message) + '</div>';
                    return;
                }
                const s = d.summary;
                const total = s.added + s.removed + s.modified +
                    s.variables_added + s.variables_removed + s.variables_modified +
                    s.testing_added + s.testing_removed + s.testing_modified + s.metadata_changed;

                let html = '<div class="vdiff-head"><div>So sánh ' +
                    '<span class="badge bg-primary">Ấn bản ' + esc(d.current.version) + ' (đang mở)</span> với ' +
                    '<span class="badge bg-secondary">Ấn bản ' + esc(d.previous.version) + '</span></div>' +
                    '<div>' + chip(s.added + s.variables_added + s.testing_added, 'thêm', 'vdiff-added') +
                    chip(s.removed + s.variables_removed + s.testing_removed, 'xoá', 'vdiff-removed') +
                    chip(s.modified + s.variables_modified + s.testing_modified + s.metadata_changed, 'sửa', 'vdiff-modified') +
                    '</div></div>' +
                    '<div class="vdiff-legend">Chú thích: <ins>chữ thêm mới</ins> · <del>chữ bị xoá</del> — ' +
                    'lịch sử được tính tự động từ nội dung thật của 2 ấn bản.</div>';

                if (total === 0) {
                    body.innerHTML = html + '<div class="alert alert-success mt-2"><i class="fas fa-check-circle me-1"></i>' +
                        'Chưa có khác biệt nào so với ấn bản ' + esc(d.previous.version) + '.</div>';
                    return;
                }

                if (d.metadata.length) {
                    html += group(
                        '<h6 class="vdiff-group-title"><i class="fas fa-info-circle me-1"></i>Thông tin chung</h6>',
                        d.metadata.map((mc) =>
                            '<div class="vdiff-card vdiff-modified"><div class="vdiff-card-title">' +
                            '<i class="fas fa-pen me-1"></i><span class="vdiff-kind">Chỉnh sửa</span>' + esc(mc.label) +
                            '</div>' + textDiffHtml(mc.old, mc.new) + '</div>')
                    );
                }

                d.sections.forEach((sec) => {
                    html += group(
                        '<h6 class="vdiff-group-title"><i class="fas fa-layer-group me-1"></i>' + esc(sec.name) + '</h6>',
                        sec.changes.map((c) => {
                            const k = KIND[c.kind];
                            return '<div class="vdiff-card ' + k.cls + '"><div class="vdiff-card-title">' +
                                '<i class="fas ' + k.icon + ' me-1"></i><span class="vdiff-kind">' + k.label + '</span>' +
                                '<span class="vdiff-type">' + esc(c.type_label) + '</span> ' + esc(c.title) + '</div>' +
                                textDiffHtml(c.old_text, c.new_text) + propRows(c.prop_changes) + '</div>';
                        })
                    );
                });

                const v = d.variables;
                if (v.added.length || v.removed.length || v.modified.length) {
                    html += group(
                        '<h6 class="vdiff-group-title"><i class="fas fa-i-cursor me-1"></i>Biến số / ô nhập liệu</h6>',
                        [
                            ...v.added.map((x) => simpleCard('added', x.label || x.name || x.key, x.type)),
                            ...v.removed.map((x) => simpleCard('removed', x.label || x.name || x.key, x.type)),
                            ...v.modified.map((x) => simpleCard('modified', x.label || x.name || x.key, null, propRows(x.changes))),
                        ]
                    );
                }

                const t = d.testing;
                if (t.added.length || t.removed.length || t.modified.length) {
                    const tTitle = (x) => (x.stage ? x.stage + ' — ' : '') + (x.name || ('STT ' + x.stt));
                    html += group(
                        '<h6 class="vdiff-group-title"><i class="fas fa-vial me-1"></i>Tiêu chuẩn kiểm nghiệm</h6>',
                        [
                            ...t.added.map((x) => simpleCard('added', tTitle(x))),
                            ...t.removed.map((x) => simpleCard('removed', tTitle(x))),
                            ...t.modified.map((x) => simpleCard('modified', tTitle(x), null, propRows(x.changes))),
                        ]
                    );
                }

                body.innerHTML = html;

                searchBar.hidden = false;
                searchInput.value = '';
                searchCountEl.textContent = '';
                cacheCardHtml();
            }

            // Đánh dấu (highlight) mọi chỗ khớp từ khoá trong 1 thẻ, CHỈ chạm vào text
            // node (không đụng cấu trúc thẻ <ins>/<del> đang tô màu diff sẵn có).
            function highlightMatches(root, query) {
                const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT);
                const nodes = [];
                let n;
                while ((n = walker.nextNode())) nodes.push(n);
                nodes.forEach((node) => {
                    const text = node.nodeValue;
                    const lower = text.toLowerCase();
                    if (!lower.includes(query)) return;
                    const frag = document.createDocumentFragment();
                    let idx = 0, pos;
                    while ((pos = lower.indexOf(query, idx)) !== -1) {
                        if (pos > idx) frag.appendChild(document.createTextNode(text.slice(idx, pos)));
                        const mark = document.createElement('mark');
                        mark.className = 'vdiff-hit';
                        mark.textContent = text.slice(pos, pos + query.length);
                        frag.appendChild(mark);
                        idx = pos + query.length;
                    }
                    if (idx < text.length) frag.appendChild(document.createTextNode(text.slice(idx)));
                    node.parentNode.replaceChild(frag, node);
                });
            }

            // Lọc + đánh dấu theo từ khoá. Thẻ khớp: tự MỞ các đoạn "N dòng không đổi"
            // đang gấp bên trong (chỗ khớp có thể nằm trong phần đã gấp lại).
            function applySearch(rawQuery) {
                const query = rawQuery.trim().toLowerCase();
                let totalCards = 0, visibleCards = 0;

                body.querySelectorAll('.vdiff-group').forEach((grp) => {
                    let groupHasVisible = false;
                    grp.querySelectorAll('.vdiff-card').forEach((card) => {
                        totalCards++;
                        const original = cardOriginalHtml.get(card);
                        if (original !== undefined) card.innerHTML = original;

                        const matches = !query || card.textContent.toLowerCase().includes(query);
                        card.style.display = matches ? '' : 'none';
                        if (!matches) return;

                        groupHasVisible = true;
                        visibleCards++;
                        if (query) {
                            card.querySelectorAll('.vdiff-hidden[hidden]').forEach((h) => { h.hidden = false; });
                            card.querySelectorAll('.vdiff-fold').forEach((b) => b.remove());
                            highlightMatches(card, query);
                        }
                    });
                    grp.style.display = groupHasVisible ? '' : 'none';
                });

                searchCountEl.textContent = query ? (visibleCards + '/' + totalCards + ' mục khớp') : '';
                noResultEl.hidden = !(query && visibleCards === 0 && totalCards > 0);
            }

            let searchTimer = null;
            searchInput.addEventListener('input', () => {
                clearTimeout(searchTimer);
                searchTimer = setTimeout(() => applySearch(searchInput.value), 120);
            });

            // Mở các đoạn "N dòng không thay đổi" khi bấm
            body.addEventListener('click', (ev) => {
                const foldBtn = ev.target.closest('[data-vdiff-fold]');
                if (!foldBtn) return;
                const hidden = document.getElementById(foldBtn.getAttribute('data-vdiff-fold'));
                if (hidden) hidden.hidden = false;
                foldBtn.remove();
            });

            btn.addEventListener('click', async () => {
                // Không cache: người dùng có thể vừa lưu chỉnh sửa mới — mỗi lần mở so sánh lại
                body.innerHTML = loadingHtml;
                searchBar.hidden = true;
                noResultEl.hidden = true;
                window.jQuery('#v2VersionDiffModal').modal('show');
                try {
                    const res = await fetch(window.__V2__.urls.versionDiff, {
                        headers: { 'Accept': 'application/json' }
                    });
                    const data = await res.json();
                    if (!data.success) throw new Error(data.message || 'Không so sánh được ấn bản.');
                    render(data);
                } catch (e) {
                    body.innerHTML = '<div class="alert alert-danger m-0"><i class="fas fa-exclamation-triangle me-1"></i>' +
                        esc(e.message || 'Lỗi khi so sánh ấn bản.') + '</div>';
                }
            });
        })();
    </script>
    @vite('resources/js/designer-v2/main.js')
@endsection
