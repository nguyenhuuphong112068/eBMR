<div class="container-fluid py-4">
    <div class="row justify-content-center">
        @if (!($isExecutionMode ?? false))
            <!-- Outline Sidebar -->
            <div id="outline-col" class="col-lg-2 transition-all">
                <div class="p-3 bg-white rounded shadow-sm outline-sidebar h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0 text-muted fw-bold" style="font-size: 0.85rem;">
                            <i class="fas fa-list-ul me-2"></i>MỤC LỤC & THẺ
                        </h6>
                        <button class="btn btn-sm btn-light text-muted border-0" onclick="toggleOutline(true)"
                            title="Thu nhỏ"><i class="fas fa-chevron-left"></i></button>
                    </div>
                    <div id="outline-content">
                        <div id="document-outline">
                            <div class="outline-empty">
                                Chưa có nội dung. Nhập văn bản và định dạng Tiêu đề (H1, H2,...) để tạo thẻ.</div>
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
        @endif

        <!-- Document Canvas -->
        <div id="canvas-col" class="{{ $isExecutionMode ?? false ? 'col-lg-12' : 'col-lg-9' }} transition-all">


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

        @if (!($isExecutionMode ?? false))
            <!-- Property Panel Slot -->
            <div id="sidebar-col" class="col-lg-1 transition-all p-0">
                @if (!$isReadOnly)
                    @include('pages.ebmr.designer.partials.sidebar')
                @endif

            </div>
        @endif
        <div class="test-mode-badge">
            <i class="fas fa-flask me-2"></i> Đang ở chế độ Chạy thử (Preview)
        </div>
    </div>

    <!-- ============================================================
     MODAL KẾT NỐI CÂN ĐIỆN TỬ (RS-232 / Web Serial API)
     ============================================================ -->
    <div class="modal fade" id="scaleConnectionModal" tabindex="-1" aria-labelledby="scaleConnectionModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">

                <!-- Header -->
                <div class="modal-header py-3 px-4"
                    style="background: linear-gradient(135deg, #14532d, #16a34a); color: white; border: none;">
                    <div class="d-flex align-items-center gap-3">
                        <div
                            style="width: 42px; height: 42px; background: rgba(255,255,255,0.15); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-balance-scale fa-lg"></i>
                        </div>
                        <div>
                            <h5 class="modal-title mb-0 fw-bold" id="scaleConnectionModalLabel">Kết nối Cân Điện Tử</h5>
                            <div class="small opacity-75">
                                Đọc dữ liệu trực tiếp vào biến số <span id="scale-modal-field-label"
                                    class="fw-bold"></span>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal"
                        data-dismiss="modal" aria-label="Đóng"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="row g-3">

                        <!-- CỘT TRÁI: Cài đặt kết nối -->
                        <div class="col-lg-6">

                            <!-- Trạng thái kết nối -->
                            <div class="d-flex align-items-center gap-2 mb-3 p-3 rounded-3"
                                style="background: #f8fafc; border: 1px solid #e2e8f0;">
                                <span id="scale-status-dot" class="scale-status-dot disconnected"></span>
                                <span id="scale-status-text" class="small fw-bold ms-2 text-danger">Chưa kết nối</span>
                                <div class="ms-auto">
                                    <button id="scale-connect-btn" class="btn btn-success btn-sm px-3"
                                        onclick="window.connectScaleFromModal()">
                                        <i class="fas fa-plug me-1"></i> Kết nối
                                    </button>
                                    <button id="scale-disconnect-btn" class="btn btn-outline-danger btn-sm px-3 d-none"
                                        onclick="window.ScaleManager.disconnect()">
                                        <i class="fas fa-times me-1"></i> Ngắt kết nối
                                    </button>
                                </div>
                            </div>

                            <!-- Chọn hãng cân -->
                            <div class="mb-3">
                                <label class="small fw-bold mb-2 text-muted text-uppercase">
                                    <i class="fas fa-building me-1"></i> Hãng cân
                                </label>
                                <select class="form-select form-select-sm" id="scale-brand-select"
                                    onchange="window.toggleCustomScaleFields(this.value)">
                                    <option value="and">⚖️ A&D (AND) — Định dạng 17 ký tự</option>
                                    <option value="mettler">🏋️ Mettler Toledo — Giao thức MT-SICS</option>
                                    <option value="sartorius">🔬 Sartorius — Định dạng SBI</option>
                                    <option value="custom">⚙️ Tùy chỉnh (hãng khác)</option>
                                </select>
                            </div>

                            <!-- Cài đặt tùy chỉnh (ẩn mặc định) -->
                            <div id="scale-custom-fields" class="d-none mb-3 p-3 rounded-3"
                                style="background: #fffbeb; border: 1px solid #fde68a;">
                                <label class="small fw-bold mb-2 text-warning-emphasis">
                                    <i class="fas fa-cog me-1"></i> Cài đặt serial tùy chỉnh
                                </label>
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

                            <!-- Ghi chú trình duyệt -->
                            <div class="alert alert-info py-2 px-3 small mb-0"
                                style="font-size: 0.78rem; border-left: 3px solid #0ea5e9; background: #f0f9ff; border-color: #bae6fd;">
                                <i class="fas fa-info-circle me-1"></i>
                                <strong>Yêu cầu:</strong> Chrome hoặc Edge 89+.
                                Khi bấm <em>"Kết nối"</em>, trình duyệt sẽ hiển thị hộp thoại chọn cổng COM.
                                Cần cài driver cho USB-to-Serial Adapter nếu sử dụng.
                            </div>
                        </div>

                        <!-- CỘT PHẢI: Giá trị live & Log -->
                        <div class="col-lg-6">

                            <!-- Hiển thị giá trị live -->
                            <div class="text-center mb-3 p-3 rounded-3"
                                style="background: #fef2f2; border: 2px solid #fecaca;">
                                <div class="small text-muted mb-1 fw-bold text-uppercase"
                                    style="font-size: 0.7rem; letter-spacing: 0.08em;">
                                    <i class="fas fa-satellite-dish me-1"></i> Dữ liệu cân (Live)
                                </div>
                                <span id="scale-live-value" class="scale-live-value stable">
                                    —.— g
                                </span>
                                <div class="small text-muted" style="font-size: 0.72rem;">
                                    Đứng yên = Ổn định ✓ &nbsp;|&nbsp; Nhấp nháy = Đang rung động
                                </div>
                            </div>

                            <!-- Log raw data -->
                            <div class="mb-2">
                                <label class="small fw-bold text-muted text-uppercase mb-1"
                                    style="font-size: 0.68rem;">
                                    <i class="fas fa-terminal me-1"></i> Raw Data Log
                                </label>
                                <div id="scale-raw-log">
                                    <div
                                        style="color: #475569; font-size: 0.72rem; font-style: italic; padding: 2px 0;">
                                        Chưa kết nối. Dữ liệu từ cân sẽ hiện tại đây...
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="modal-footer px-4 py-3" style="background: #f8fafc; border-top: 1px solid #e2e8f0;">
                    <div class="d-flex justify-content-between align-items-center w-100">
                        <div class="small text-muted">
                            <i class="fas fa-lightbulb text-warning me-1"></i>
                            Hệ thống sẽ tự động điền giá trị <strong>ổn định</strong> vào biến số.
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal"
                                data-dismiss="modal">
                                Đóng
                            </button>
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
