<div id="bmr_bom_tables_container" style="display: {{ request('type') == 'BMR' ? 'block' : 'none' }};">
    <div class="d-flex justify-content-between align-items-center mb-1">
        <label class="fw-bold small text-uppercase text-primary mb-0">1. NGUYÊN LIỆU PHA CHẾ</label>
        <button type="button" class="btn btn-sm btn-outline-success rounded-pill fw-bold" onclick="openImportBomModal(0)">
            <i class="fas fa-file-word me-1"></i> Nhập từ Word
        </button>
    </div>
    <div class="table-responsive bg-white border rounded">
        <table class="table table-sm table-bordered mb-0">
            <thead class="bg-light text-center align-middle" style="font-size: 0.8rem;">
                <tr>
                    <th style="width: 40px; min-width: 40px;">STT</th>
                    <th style="min-width: 150px; width: 150px;">Mã nguyên liệu</th>
                    <th style="min-width: 300px;">Thành phần</th>
                    <th style="min-width: 130px; width: 110px;">Chức năng</th>
                    <th style="min-width: 150px;">Nhà sản xuất</th>
                    <th style="min-width: 150px;">Tiêu chuẩn</th>
                    <th style="width: 250px; min-width: 250px;">
                        1 viên (mg)
                        <input type="number" step="any"
                            class="form-control form-control-sm mt-1 text-center border-primary" name="avg_core"
                            id="update_avg_core" value="{{ old('avg_core') }}" placeholder="Nhân TB"
                            title="Khối lượng nhân trung bình">
                        <div id="warning_type_0" class="text-danger small mt-1"
                            style="display: none; line-height: 1.1;"><i class="fas fa-exclamation-triangle"></i> Tổng
                            <span class="sum-val fw-bold"></span> > <span class="avg-val fw-bold"></span>
                        </div>
                    </th>
                    <th style="width: 100px;">Tỉ lệ (%)</th>
                    <th style="width: 150px;">Lô tiêu chuẩn</th>
                    <th style="width: 40px;">
                        <button type="button" class="btn btn-xs btn-success" id="btn_add_bom_row_type_0"
                            title="Thêm dòng loại 0">
                            <i class="fa fa-plus"></i>
                        </button>
                    </th>
                </tr>
            </thead>
            <tbody id="bom_table_body_type_0">
                <!-- BOM rows for type 0 will be appended here -->
            </tbody>
        </table>
    </div>
    <div id="bom_notes_type_0" class="mb-4 small text-muted fst-italic px-2"></div>

    <div class="d-flex justify-content-between align-items-center mb-1">
        <label class="fw-bold small text-uppercase text-primary mb-0">2. NGUYÊN LIỆU KHÁC (BAO PHIM/NANG)</label>
        <button type="button" class="btn btn-sm btn-outline-success rounded-pill fw-bold"
            onclick="openImportBomModal(1)">
            <i class="fas fa-file-word me-1"></i> Nhập từ Word
        </button>
    </div>
    <div class="table-responsive bg-white border rounded">
        <table class="table table-sm table-bordered mb-0">
            <thead class="bg-light text-center align-middle" style="font-size: 0.8rem;">
                <tr>
                    <th style="width: 40px; min-width: 40px;">STT</th>
                    <th style="min-width: 150px; width: 150px;">Mã nguyên liệu</th>
                    <th style="min-width: 350px;">Thành phần</th>
                    <th style="min-width: 110px; width: 110px;">Chức năng</th>
                    <th style="min-width: 150px;">Nhà sản xuất</th>
                    <th style="min-width: 150px;">Tiêu chuẩn</th>
                    <th style="width: 250px; min-width: 250px;">
                        1 viên (mg)
                        <input type="number" step="any"
                            class="form-control form-control-sm mt-1 text-center border-primary"
                            name="average_unit_weight" id="update_average_unit_weight"
                            value="{{ old('average_unit_weight') }}" placeholder="Viên TB"
                            title="Khối lượng viên trung bình">
                        <div id="warning_type_1" class="text-danger small mt-1"
                            style="display: none; line-height: 1.1;"><i class="fas fa-exclamation-triangle"></i> Tổng
                            <span class="sum-val fw-bold"></span> > <span class="avg-val fw-bold"></span>
                        </div>
                    </th>
                    <th style="width: 100px;">Tỉ lệ (%)</th>
                    <th style="width: 150px;">Lô tiêu chuẩn</th>
                    <th style="width: 40px;">
                        <button type="button" class="btn btn-xs btn-success" id="btn_add_bom_row_type_1"
                            title="Thêm dòng loại 1">
                            <i class="fa fa-plus"></i>
                        </button>
                    </th>
                </tr>
            </thead>
            <tbody id="bom_table_body_type_1">
                <!-- BOM rows for type 1 will be appended here -->
            </tbody>
        </table>
    </div>
    <div id="bom_notes_type_1" class="mb-4 small text-muted fst-italic px-2"></div>
</div>

<!-- Import BOM Modal -->
<div class="modal fade" id="importBomModal" tabindex="-1" role="dialog" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-light d-flex justify-content-between align-items-center">
                <h5 class="modal-title font-weight-bold text-success mb-0">
                    <i class="fas fa-file-import me-2"></i> Nhập Công Thức Từ Word
                </h5>
                <div class="d-flex align-items-center">
                    <button type="button" class="btn btn-success btn-sm rounded-pill px-3 shadow-sm text-white me-3"
                        onclick="processImportedBom()">
                        <i class="fas fa-check me-1 text-white"></i> Xác nhận Import
                    </button>
                    <button type="button" class="close" onclick="$('#importBomModal').modal('hide')" aria-label="Close" style="margin: -1rem -1rem -1rem auto;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" id="importBomType" value="0">
                <div class="alert alert-info border-0 shadow-none mb-3">
                    <i class="fas fa-info-circle me-2"></i> <strong>Hướng dẫn:</strong> Mở file Word hoặc Excel, bôi
                    đen toàn bộ bảng công thức, nhấn <strong>Ctrl+C</strong> (Copy). Sau đó nhấp chuột vào khung bên
                    dưới và nhấn <strong>Ctrl+V</strong> (Paste).
                </div>
                <div id="importBomPasteArea" contenteditable="true" class="form-control"
                    style="min-height: 250px; height: auto; max-height: 800px; overflow-y: auto; border: 2px dashed #28a745; background-color: #f8fff9; padding: 15px; border-radius: 8px; outline: none; cursor: text;"
                    data-placeholder="Dán (Paste) bảng công thức vào đây...">
                </div>
            </div>
            <div class="modal-footer bg-light p-2">
                <button type="button" class="btn btn-white btn-sm rounded-pill px-4"
                    onclick="$('#importBomModal').modal('hide')">Đóng</button>
            </div>
        </div>
    </div>
</div>
<style>
    #importBomPasteArea:empty:before {
        content: attr(data-placeholder);
        color: #6c757d;
        font-style: italic;
    }

    #importBomPasteArea table {
        width: 100% !important;
        border-collapse: collapse;
        font-size: 1.15rem;
        color: #0056b3;
    }

    #importBomPasteArea table,
    #importBomPasteArea th,
    #importBomPasteArea td {
        border: 1px solid #0056b3;
    }

    #importBomPasteArea th,
    #importBomPasteArea td {
        padding: 8px;
    }

    textarea.auto-resize {
        overflow: hidden;
        resize: none;
        min-height: 42px;
        line-height: 1.4;
    }
</style>
