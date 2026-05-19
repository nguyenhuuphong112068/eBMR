<div id="bmr_bom_tables_container" style="display: {{ request('type') == 'BMR' ? 'block' : 'none' }};">
    <label class="fw-bold small text-uppercase text-primary mb-1">1. NGUYÊN LIỆU PHA CHẾ</label>
    <div class="table-responsive bg-white border rounded">
        <table class="table table-sm table-bordered mb-0">
            <thead class="bg-light text-center align-middle" style="font-size: 0.8rem;">
                <tr>
                    <th style="width: 40px; min-width: 40px;">STT</th>
                    <th style="min-width: 120px; width: 120px;">Mã nguyên liệu</th>
                    <th style="min-width: 350px;">Thành phần</th>
                    <th style="min-width: 150px;">Chức năng</th>
                    <th style="min-width: 150px;">Nhà sản xuất</th>
                    <th style="min-width: 150px;">Tiêu chuẩn</th>
                    <th style="width: 250px; min-width: 250px;">
                        1 viên (mg)
                        <input type="number" step="any" class="form-control form-control-sm mt-1 text-center border-primary" 
                               name="avg_core" id="update_avg_core" value="{{ old('avg_core') }}" placeholder="Nhân TB" title="Khối lượng nhân trung bình">
                        <div id="warning_type_0" class="text-danger small mt-1" style="display: none; line-height: 1.1;"><i class="fas fa-exclamation-triangle"></i> Tổng <span class="sum-val fw-bold"></span> > <span class="avg-val fw-bold"></span></div>
                    </th>
                    <th style="width: 100px;">Tỉ lệ (%)</th>
                    <th style="width: 150px;">Lô tiêu chuẩn</th>
                    <th style="width: 40px;">
                        <button type="button" class="btn btn-xs btn-success" id="btn_add_bom_row_type_0" title="Thêm dòng loại 0">
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

    <label class="fw-bold small text-uppercase text-primary mb-1">2. NGUYÊN LIỆU KHÁC (BAO PHIM/NANG)</label>
    <div class="table-responsive bg-white border rounded">
        <table class="table table-sm table-bordered mb-0">
            <thead class="bg-light text-center align-middle" style="font-size: 0.8rem;">
                <tr>
                    <th style="width: 40px; min-width: 40px;">STT</th>
                    <th style="min-width: 120px; width: 120px;">Mã nguyên liệu</th>
                    <th style="min-width: 350px;">Thành phần</th>
                    <th style="min-width: 150px;">Chức năng</th>
                    <th style="min-width: 150px;">Nhà sản xuất</th>
                    <th style="min-width: 150px;">Tiêu chuẩn</th>
                    <th style="width: 250px; min-width: 250px;">
                        1 viên (mg)
                        <input type="number" step="any" class="form-control form-control-sm mt-1 text-center border-primary" 
                               name="average_unit_weight" id="update_average_unit_weight" value="{{ old('average_unit_weight') }}" placeholder="Viên TB" title="Khối lượng viên trung bình">
                        <div id="warning_type_1" class="text-danger small mt-1" style="display: none; line-height: 1.1;"><i class="fas fa-exclamation-triangle"></i> Tổng <span class="sum-val fw-bold"></span> > <span class="avg-val fw-bold"></span></div>
                    </th>
                    <th style="width: 100px;">Tỉ lệ (%)</th>
                    <th style="width: 150px;">Lô tiêu chuẩn</th>
                    <th style="width: 40px;">
                        <button type="button" class="btn btn-xs btn-success" id="btn_add_bom_row_type_1" title="Thêm dòng loại 1">
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
