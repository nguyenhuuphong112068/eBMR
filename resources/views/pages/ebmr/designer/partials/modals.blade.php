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
<style>
    #searchReplaceModal {
        pointer-events: none; /* Allow clicking the document behind the modal container */
    }
    #searchReplaceModal .modal-dialog {
        pointer-events: auto; /* Re-enable clicks for the modal itself */
        margin: 0;
        position: absolute;
        top: 100px;
        left: calc(50% - 250px);
        width: 500px;
    }
    #searchReplaceModal .modal-content {
        cursor: default;
    }
    #searchReplaceModal .modal-header {
        cursor: move; /* Indicate draggability */
        user-select: none;
    }
</style>
<div class="modal fade" id="searchReplaceModal" tabindex="-1" aria-hidden="true" data-backdrop="false">
    <div class="modal-dialog">
        <div class="modal-content shadow-lg border" style="border-radius: 8px; overflow: hidden;">
            <div class="modal-header bg-light py-2 px-3 border-bottom d-flex align-items-center">
                <h6 class="modal-title fw-bold text-muted mb-0" style="font-size: 0.85rem;">Find and Replace</h6>
                <button type="button" class="close ml-auto" style="font-size: 1.2rem; line-height: 1;" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
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
                </ul>
                
                <div class="tab-content p-4 bg-white" id="searchTabContent" style="border-top: 1px solid #dee2e6;">
                    <!-- Common Search Input (Shared or duplicated for ease) -->
                    <div class="mb-3 row align-items-center">
                        <label class="col-sm-3 small fw-normal text-muted mb-0">Find what:</label>
                        <div class="col-sm-9">
                            <input type="text" id="findInput" class="form-control form-control-sm rounded-0 shadow-sm" style="font-family: inherit;" onkeydown="if(event.key==='Enter') executeSearch(false)">
                        </div>
                    </div>

                    <!-- Replace Specific Input -->
                    <div class="tab-pane fade" id="replace-pane" role="tabpanel">
                        <div class="mb-4 row align-items-center">
                            <label class="col-sm-3 small fw-normal text-muted mb-0">Replace with:</label>
                            <div class="col-sm-9">
                                <input type="text" id="replaceInput" class="form-control form-control-sm rounded-0 shadow-sm" style="font-family: inherit;">
                            </div>
                        </div>
                    </div>
                    
                    <div class="tab-pane fade show active" id="find-pane" role="tabpanel">
                        <!-- Empty for Find tab as it only uses the shared "Find what" -->
                    </div>

                    <!-- Actions Footer Area -->
                    <div class="d-flex justify-content-end align-items-center mt-4 pt-3 border-top gap-2">
                        <div class="gap-2" id="actionButtonsFind" style="display: flex;">
                             <button class="btn btn-light border btn-sm px-4" onclick="executeSearch(false)" style="font-size: 0.75rem; border-radius: 4px;">Find Next</button>
                             <button class="btn btn-light border btn-sm px-4" data-dismiss="modal" style="font-size: 0.75rem; border-radius: 4px;">Cancel</button>
                        </div>
                        
                        <div class="gap-2" id="actionButtonsReplace" style="display: none;">
                             <button class="btn btn-light border btn-sm px-3" onclick="executeReplace()" style="font-size: 0.75rem; border-radius: 4px;">Replace</button>
                             <button class="btn btn-light border btn-sm px-3" onclick="executeReplaceAll()" style="font-size: 0.75rem; border-radius: 4px;">Replace All</button>
                             <button class="btn btn-light border btn-sm px-3" data-dismiss="modal" style="font-size: 0.75rem; border-radius: 4px;">Cancel</button>
                        </div>
                    </div>
                    <div id="searchStats" class="mt-2 text-end text-muted" style="font-size: 0.65rem; font-style: italic; min-height: 15px;"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('searchReplaceModal');
        const findTab = document.getElementById('find-tab');
        const replaceTab = document.getElementById('replace-tab');
        const findBtns = document.getElementById('actionButtonsFind');
        const replaceBtns = document.getElementById('actionButtonsReplace');

        window.updateSearchButtons = function(isReplace) {
            if (!findBtns || !replaceBtns) return;
            if (isReplace) {
                findBtns.style.display = 'none';
                replaceBtns.style.display = 'flex';
            } else {
                findBtns.style.display = 'flex';
                replaceBtns.style.display = 'none';
            }
        };

        if(findTab) {
            $(findTab).on('shown.bs.tab', function () {
                updateSearchButtons(false);
            });
        }
        if(replaceTab) {
            $(replaceTab).on('shown.bs.tab', function () {
                updateSearchButtons(true);
            });
        }

        // Draggable Implementation
        const dialog = modal.querySelector('.modal-dialog');
        const header = modal.querySelector('.modal-header');
        let isDragging = false;
        let offset = { x: 0, y: 0 };

        header.addEventListener('mousedown', (e) => {
            if (e.target.closest('button')) return; // Don't drag if clicking close button
            isDragging = true;
            offset.x = e.clientX - dialog.offsetLeft;
            offset.y = e.clientY - dialog.offsetTop;
            header.style.cursor = 'grabbing';
        });

        document.addEventListener('mousemove', (e) => {
            if (!isDragging) return;
            dialog.style.left = (e.clientX - offset.x) + 'px';
            dialog.style.top = (e.clientY - offset.y) + 'px';
        });

        document.addEventListener('mouseup', () => {
            isDragging = false;
            header.style.cursor = 'move';
        });
    });
</script>
<!-- Variable Summary Modal -->
<div class="modal fade" id="variableSummaryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog" style="max-width: 95%;">
        <div class="modal-content shadow-lg border-0" style="border-radius: 12px;">
            <div class="modal-header bg-info text-white py-2 px-3">
                <h5 class="modal-title fw-bold small"><i class="fas fa-list-check me-2"></i> DANH SÁCH BIẾN SỐ ĐÃ CÀI ĐẶT</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-0" style="max-height: 70vh; overflow-y: auto;">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0" style="font-size: 0.85rem;">
                        <thead class="bg-light sticky-top shadow-sm">
                            <tr>
                                <th class="text-center py-2" style="width: 50px;">STT</th>
                                @if(session('user')['userGroup'] == 'Admin')
                                <th class="py-2">Mã ID</th>
                                @endif
                                <th class="py-2">Tên hiển thị (Label)</th>
                                <th class="py-2">Loại</th>
                                <th class="text-center py-2">Thông số quan trọng</th>
                                <th class="py-2">Cài đặt chi tiết</th>
                                <th class="text-center py-2" style="width: 80px;">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody id="variableSummaryTableBody">
                            <!-- Populated by JS -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary btn-sm px-4" data-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<!-- Abbreviation Modal -->
<div class="modal fade" id="abbreviationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg border-0" style="border-radius: 12px;">
            <div class="modal-header bg-primary text-white border-0 py-2 px-3">
                <h5 class="modal-title fw-bold small"><i class="fas fa-spell-check me-2"></i> THÊM TỪ VIẾT TẮT</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="small fw-bold mb-1">Từ viết tắt đã chọn</label>
                    <input type="text" id="abbrWord" class="form-control" readonly style="background-color: #f8f9fa;">
                </div>
                <div class="mb-3">
                    <label class="small fw-bold mb-1">Ý nghĩa (Giải nghĩa)</label>
                    <input type="text" id="abbrMeaning" class="form-control" placeholder="Nhập ý nghĩa của từ viết tắt...">
                </div>
            </div>
            <div class="modal-footer bg-light border-0 py-2">
                <button type="button" class="btn btn-secondary btn-sm px-4" data-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-primary btn-sm px-4 fw-bold" onclick="saveAbbreviation()">Lưu</button>
            </div>
        </div>
    </div>
</div>
