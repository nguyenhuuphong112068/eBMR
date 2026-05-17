<div id="bmr_bom_tables_container" style="display: {{ request('type') == 'BMR' ? 'block' : 'none' }};">
    <label class="fw-bold small text-uppercase text-primary mb-1">1. NGUYÊN LIỆU PHA CHẾ</label>
    <div class="table-responsive bg-white border rounded mb-4">
        <table class="table table-sm table-bordered mb-0">
            <thead class="bg-light text-center align-middle" style="font-size: 0.8rem;">
                <tr>
                    <th style="width: 40px;">STT</th>
                    <th>Mã nguyên liệu</th>
                    <th>Thành phần</th>
                    <th>Chức năng</th>
                    <th>Nhà sản xuất</th>
                    <th>Tiêu chuẩn</th>
                    <th style="width: 150px;">
                        1 viên (mg)
                        <input type="number" step="any" class="form-control form-control-sm mt-1 text-center border-primary" 
                               name="avg_core" id="update_avg_core" value="{{ old('avg_core') }}" placeholder="Nhân TB" title="Khối lượng nhân trung bình">
                    </th>
                    <th style="width: 80px;">Lô tiêu chuẩn</th>
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

    <label class="fw-bold small text-uppercase text-primary mb-1">2. NGUYÊN LIỆU KHÁC (BAO PHIM/NANG)</label>
    <div class="table-responsive bg-white border rounded">
        <table class="table table-sm table-bordered mb-0">
            <thead class="bg-light text-center align-middle" style="font-size: 0.8rem;">
                <tr>
                    <th style="width: 40px;">STT</th>
                    <th>Mã nguyên liệu</th>
                    <th>Thành phần</th>
                    <th>Chức năng</th>
                    <th>Nhà sản xuất</th>
                    <th>Tiêu chuẩn</th>
                    <th style="width: 150px;">
                        1 viên (mg)
                        <input type="number" step="any" class="form-control form-control-sm mt-1 text-center border-primary" 
                               name="average_unit_weight" id="update_average_unit_weight" value="{{ old('average_unit_weight') }}" placeholder="Viên TB" title="Khối lượng viên trung bình">
                    </th>
                    <th style="width: 80px;">Lô tiêu chuẩn</th>
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
</div>
