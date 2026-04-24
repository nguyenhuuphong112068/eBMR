<!-- Open Template Modal -->
<div class="modal fade" id="openTemplateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content shadow-lg border-0" style="border-radius: 12px;">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold text-navy"><i class="fas fa-folder-open me-2"></i> MỞ HỒ SƠ MẪU</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="input-group mb-4">
                    <span class="input-group-text bg-white border-end-0"><i
                            class="fas fa-search text-muted"></i></span>
                    <input type="text" class="form-control border-start-0" id="templateSearch"
                        placeholder="Tìm kiếm tên hồ sơ..." oninput="filterTemplates(this.value)">
                </div>
                <div class="list-group list-group-flush" id="templateListLoading"
                    style="max-height: 400px; overflow-y: auto;">
                    <div class="text-center py-5">
                        <div class="spinner-border text-navy" role="status"></div>
                        <p class="mt-2 text-muted">Đang tải danh sách...</p>
                    </div>
                </div>
                <div class="list-group list-group-flush d-none" id="templateList"
                    style="max-height: 400px; overflow-y: auto;">
                    <!-- Templates go here -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- History Modal -->
<div class="modal fade" id="historyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content shadow-lg border-0" style="border-radius: 12px;">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title fw-bold"><i class="fas fa-history me-2"></i> LỊCH SỬ THAY ĐỔI</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-light">
                <div id="historyTimeline" class="position-relative">
                    <!-- History items go here -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Split Cell Modal -->
<div class="modal fade" id="splitModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content shadow-lg border-0" style="border-radius: 12px;">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold small"><i class="fas fa-columns me-2"></i> TÁCH Ô</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="small fw-bold mb-1">Số hàng</label>
                    <input type="number" id="splitRows" class="form-control form-control-sm" value="1" min="1">
                </div>
                <div class="mb-3">
                    <label class="small fw-bold mb-1">Số cột</label>
                    <input type="number" id="splitCols" class="form-control form-control-sm" value="2" min="1">
                </div>
                <div class="d-grid">
                    <button class="btn btn-primary btn-sm fw-bold w-100" onclick="executeAdvancedSplit()">Xác nhận tách</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Link GF Modal -->
<div class="modal fade" id="linkGfModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content shadow-lg border-0" style="border-radius: 12px;">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold text-navy"><i class="fas fa-link me-2"></i> CHÈN BIỂU MẪU CHUNG</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="input-group mb-4">
                    <span class="input-group-text bg-white border-end-0"><i
                            class="fas fa-search text-muted"></i></span>
                    <input type="text" class="form-control border-start-0" id="gfSearch"
                        placeholder="Tìm kiếm biểu mẫu chung..." oninput="filterGfs(this.value)">
                </div>
                <div class="list-group list-group-flush" id="gfListLoading"
                    style="max-height: 400px; overflow-y: auto;">
                    <div class="text-center py-5">
                        <div class="spinner-border text-navy" role="status"></div>
                        <p class="mt-2 text-muted">Đang tải danh sách...</p>
                    </div>
                </div>
                <div class="list-group list-group-flush d-none" id="gfList"
                    style="max-height: 400px; overflow-y: auto;">
                    <!-- GFs go here -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart Creator Modal -->
<div class="modal fade" id="chartCreatorModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content shadow-lg border-0" style="border-radius: 12px;">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-chart-bar me-2"></i> CẤU HÌNH BIỂU ĐỒ</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="small fw-bold mb-1">Loại biểu đồ</label>
                    <select id="chartType" class="form-control">
                        <option value="line">Biểu đồ đường (Line)</option>
                        <option value="bar">Biểu đồ cột (Bar)</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="small fw-bold mb-1">Tiêu đề biểu đồ</label>
                    <input type="text" id="chartTitle" class="form-control" placeholder="Nhập tiêu đề...">
                </div>
                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="small fw-bold mb-1">Cột Nhãn (X)</label>
                        <select id="chartXAxis" class="form-control">
                            <!-- Options populated dynamically -->
                        </select>
                    </div>
                    <div class="col-6 mb-3">
                        <label class="small fw-bold mb-1">Cột Dữ liệu (Y)</label>
                        <select id="chartYAxis" class="form-control">
                            <!-- Options populated dynamically -->
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="small fw-bold mb-1">Giới hạn dưới (Min Y)</label>
                        <input type="number" id="chartMinY" class="form-control" placeholder="Tự động">
                    </div>
                    <div class="col-6 mb-3">
                        <label class="small fw-bold mb-1">Giới hạn trên (Max Y)</label>
                        <input type="number" id="chartMaxY" class="form-control" placeholder="Tự động">
                    </div>
                </div>
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="isMatrixChart" onchange="toggleMatrixOptions(this.checked)">
                        <label class="form-check-label small fw-bold" for="isMatrixChart">Bảng dạng ma trận (Nhiều cột dữ liệu)</label>
                    </div>
                    <p class="text-muted mb-0" style="font-size: 0.65rem;">Chọn nếu bạn muốn gộp dữ liệu từ nhiều cột và hàng thành một chuỗi thời gian.</p>
                </div>
                <div class="d-grid mt-2">
                    <button class="btn btn-info text-white fw-bold w-100" onclick="generateChartFromTable()">Tạo biểu đồ</button>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Symbol Modal -->
<div class="modal fade" id="symbolModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg border-0" style="border-radius: 12px;">
            <div class="modal-header bg-light border-0">
                <h5 class="modal-title fw-bold text-navy small"><i class="fas fa-omega me-2"></i> CHÈN KÝ HIỆU</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-0">
                <ul class="nav nav-tabs nav-justified bg-light border-0" id="symbolTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active small fw-bold border-0" id="math-tab" data-toggle="tab" href="#math-sym" role="tab">Toán học</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link small fw-bold border-0" id="greek-tab" data-toggle="tab" href="#greek-sym" role="tab">Hy Lạp</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link small fw-bold border-0" id="misc-tab" data-toggle="tab" href="#misc-sym" role="tab">Khác</a>
                    </li>
                </ul>
                <div class="tab-content p-3" id="symbolTabContent" style="max-height: 300px; overflow-y: auto;">
                    <div class="tab-pane fade show active" id="math-sym" role="tabpanel">
                        <div class="symbol-grid d-flex flex-wrap gap-1">
                            <!-- Math symbols -->
                        </div>
                    </div>
                    <div class="tab-pane fade" id="greek-sym" role="tabpanel">
                        <div class="symbol-grid d-flex flex-wrap gap-1">
                            <!-- Greek symbols -->
                        </div>
                    </div>
                    <div class="tab-pane fade" id="misc-sym" role="tabpanel">
                        <div class="symbol-grid d-flex flex-wrap gap-1">
                            <!-- Misc symbols -->
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light border-0 py-2">
                <span class="small text-muted me-auto">Chọn một ký hiệu để chèn</span>
                <button type="button" class="btn btn-secondary btn-sm px-4" data-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<!-- Find & Replace Modal (Word Style) -->
<div class="modal fade" id="searchReplaceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg border-0" style="border-radius: 8px; overflow: hidden;">
            <div class="modal-header bg-light py-2 px-3 border-bottom">
                <h6 class="modal-title fw-bold text-muted mb-0" style="font-size: 0.85rem;">Find and Replace</h6>
                <button type="button" class="btn-close" style="font-size: 0.6rem;" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <!-- Word-style Tabs -->
                <ul class="nav nav-tabs border-0 bg-light px-3 pt-2" id="searchTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active py-1 px-4 small fw-bold border-bottom-0" id="find-tab" data-toggle="tab" href="#find-pane" role="tab" style="font-size: 0.75rem; border-radius: 4px 4px 0 0;">Find</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link py-1 px-4 small fw-bold border-bottom-0" id="replace-tab" data-toggle="tab" href="#replace-pane" role="tab" style="font-size: 0.75rem; border-radius: 4px 4px 0 0;">Replace</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link py-1 px-4 small fw-bold border-bottom-0 disabled" id="goto-tab" data-toggle="tab" href="#goto-pane" role="tab" style="font-size: 0.75rem; border-radius: 4px 4px 0 0;">Go To</button>
                    </li>
                </ul>
                
                <div class="tab-content p-4 bg-white" id="searchTabContent" style="border-top: 1px solid #dee2e6;">
                    <!-- Common Search Input (Shared or duplicated for ease) -->
                    <div class="mb-3 row align-items-center">
                        <label class="col-sm-3 small fw-normal text-muted mb-0">Find what:</label>
                        <div class="col-sm-9">
                            <div class="input-group input-group-sm shadow-sm">
                                <input type="text" id="findInput" class="form-control rounded-0" style="font-family: inherit;" onkeydown="if(event.key==='Enter') executeSearch(false)">
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary dropdown-toggle px-1" type="button" data-toggle="dropdown" aria-expanded="false"></button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Replace Specific Input -->
                    <div class="tab-pane fade" id="replace-pane" role="tabpanel">
                        <div class="mb-4 row align-items-center">
                            <label class="col-sm-3 small fw-normal text-muted mb-0">Replace with:</label>
                            <div class="col-sm-9">
                                <div class="input-group input-group-sm shadow-sm">
                                    <input type="text" id="replaceInput" class="form-control rounded-0" style="font-family: inherit;">
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-secondary dropdown-toggle px-1" type="button" data-toggle="dropdown" aria-expanded="false"></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="tab-pane fade show active" id="find-pane" role="tabpanel">
                        <!-- Empty for Find tab as it only uses the shared "Find what" -->
                    </div>

                    <!-- Actions Footer Area (Internal to body like Word) -->
                    <div class="d-flex justify-content-between align-items-center mt-5 pt-3 border-top">
                        <button class="btn btn-light border btn-sm px-3" style="font-size: 0.7rem;">More &gt;&gt;</button>
                        
                        <div class="d-flex gap-1" id="actionButtonsFind">
                             <button class="btn btn-light border btn-sm px-3" onclick="executeSearch(false)" style="font-size: 0.7rem;">Find Next</button>
                             <button class="btn btn-light border btn-sm px-3" data-bs-dismiss="modal" style="font-size: 0.7rem;">Cancel</button>
                        </div>
                        
                        <div class="d-flex gap-1 d-none" id="actionButtonsReplace">
                             <button class="btn btn-light border btn-sm px-3" onclick="executeReplace()" style="font-size: 0.7rem;">Replace</button>
                             <button class="btn btn-light border btn-sm px-3" onclick="executeReplaceAll()" style="font-size: 0.7rem;">Replace All</button>
                             <button class="btn btn-light border btn-sm px-3" onclick="executeSearch(false)" style="font-size: 0.7rem;">Find Next</button>
                             <button class="btn btn-light border btn-sm px-3" data-bs-dismiss="modal" style="font-size: 0.7rem;">Cancel</button>
                        </div>
                    </div>
                    <div id="searchStats" class="mt-2 text-end text-muted" style="font-size: 0.65rem; font-style: italic; min-height: 15px;"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Toggle footer buttons based on tab
    document.addEventListener('DOMContentLoaded', function() {
        const findTab = document.getElementById('find-tab');
        const replaceTab = document.getElementById('replace-tab');
        const findBtns = document.getElementById('actionButtonsFind');
        const replaceBtns = document.getElementById('actionButtonsReplace');

        if(findTab) {
            $(findTab).on('shown.bs.tab', function () {
                findBtns.classList.remove('d-none');
                replaceBtns.classList.add('d-none');
            });
        }
        if(replaceTab) {
            $(replaceTab).on('shown.bs.tab', function () {
                findBtns.classList.add('d-none');
                replaceBtns.classList.remove('d-none');
            });
        }
    });
</script>
