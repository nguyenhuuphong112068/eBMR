<style>
    #conditionModal .form-control, 
    #conditionModal .form-control-sm {
        height: 38px !important;
        line-height: 1.5 !important;
        padding: 6px 12px !important;
        font-size: 14px !important;
    }
    #conditionModal select.form-control,
    #conditionModal select.form-control-sm {
        height: 38px !important;
        padding: 4px 8px !important;
    }
</style>
<div class="modal fade" id="conditionModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document" style="max-width: 70%;">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">
            <div class="modal-header bg-light border-0 py-3 px-4">
                <h5 class="modal-title fw-bold text-success" id="condition_room_title"><i class="fas fa-thermometer-half me-1"></i> Thiết lập điều kiện sản xuất</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" id="condition_room_id">
                
                <!-- Form khai báo bộ điều kiện mới / chỉnh sửa -->
                <form id="form_add_condition" class="mb-4 p-3 border rounded bg-light">
                    <input type="hidden" id="cond_id">
                    <h6 class="fw-bold text-success border-bottom pb-2 mb-3"><i class="fas fa-plus-circle me-1"></i> Thêm bộ điều kiện mới</h6>
                    
                    <div class="row">
                        <!-- Tên bộ điều kiện -->
                        <div class="col-md-8 col-sm-12 mb-3">
                            <div class="form-group mb-0">
                                <label class="fw-bold small text-uppercase text-muted mb-1">Tên bộ điều kiện / Tên sản phẩm <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="cond_name" placeholder="Ví dụ: Sản xuất Viên nén A / Trạng thái chờ..." required>
                            </div>
                        </div>
                        <div class="col-md-4 col-sm-12 mb-3">
                            <div class="form-group mb-0">
                                <label class="fw-bold small text-uppercase text-muted mb-1">Ghi chú thêm</label>
                                <input type="text" class="form-control" id="cond_note" placeholder="Thông tin ghi chú...">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Nhiệt độ -->
                        <div class="col-md-6 mb-3 border-right">
                            <div class="p-3 bg-white rounded border h-100 shadow-sm">
                                <label class="fw-bold small text-primary mb-3 d-block"><i class="fas fa-temperature-high me-1"></i> Nhiệt độ (°C)</label>
                                
                                <!-- ĐK 1 -->
                                <div class="row align-items-center mb-3">
                                    <div class="col-4 pr-1">
                                        <label class="small fw-bold text-muted mb-1 d-block">ĐK 1 - Toán tử</label>
                                        <select class="form-control form-control-sm op-selector" id="cond_temp_op_1">
                                            <option value="≤">≤</option>
                                            <option value="≥">≥</option>
                                            <option value="±">±</option>
                                            <option value="between">khoảng</option>
                                            <option value="=">=</option>
                                        </select>
                                    </div>
                                    <div class="col-4 px-1">
                                        <label class="small text-muted mb-1 d-block">Giá trị 1</label>
                                        <input type="number" step="any" class="form-control form-control-sm" id="cond_temp_val1_1" placeholder="Số">
                                    </div>
                                    <div class="col-4 pl-1 val2-container">
                                        <label class="small text-muted mb-1 d-block">Giá trị 2 / Sai số</label>
                                        <input type="number" step="any" class="form-control form-control-sm" id="cond_temp_val2_1" placeholder="Số 2">
                                    </div>
                                </div>

                                <!-- ĐK 2 -->
                                <div class="row align-items-center">
                                    <div class="col-4 pr-1">
                                        <label class="small fw-bold text-muted mb-1 d-block">ĐK 2 - Toán tử</label>
                                        <select class="form-control form-control-sm op-selector" id="cond_temp_op_2">
                                            <option value="±" selected>±</option>
                                            <option value="≤">≤</option>
                                            <option value="≥">≥</option>
                                            <option value="between">khoảng</option>
                                            <option value="=">=</option>
                                        </select>
                                    </div>
                                    <div class="col-4 px-1">
                                        <label class="small text-muted mb-1 d-block">Giá trị 1</label>
                                        <input type="number" step="any" class="form-control form-control-sm" id="cond_temp_val1_2" placeholder="Số">
                                    </div>
                                    <div class="col-4 pl-1 val2-container">
                                        <label class="small text-muted mb-1 d-block">Giá trị 2 / Sai số</label>
                                        <input type="number" step="any" class="form-control form-control-sm" id="cond_temp_val2_2" placeholder="Số 2">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Độ ẩm -->
                        <div class="col-md-6 mb-3">
                            <div class="p-3 bg-white rounded border h-100 shadow-sm">
                                <label class="fw-bold small text-primary mb-3 d-block"><i class="fas fa-tint me-1"></i> Độ ẩm (%)</label>
                                
                                <!-- ĐK 1 -->
                                <div class="row align-items-center mb-3">
                                    <div class="col-4 pr-1">
                                        <label class="small fw-bold text-muted mb-1 d-block">ĐK 1 - Toán tử</label>
                                        <select class="form-control form-control-sm op-selector" id="cond_humidity_op_1">
                                            <option value="≤">≤</option>
                                            <option value="≥">≥</option>
                                            <option value="±">±</option>
                                            <option value="between">khoảng</option>
                                            <option value="=">=</option>
                                        </select>
                                    </div>
                                    <div class="col-4 px-1">
                                        <label class="small text-muted mb-1 d-block">Giá trị 1</label>
                                        <input type="number" step="any" class="form-control form-control-sm" id="cond_humidity_val1_1" placeholder="Số">
                                    </div>
                                    <div class="col-4 pl-1 val2-container">
                                        <label class="small text-muted mb-1 d-block">Giá trị 2 / Sai số</label>
                                        <input type="number" step="any" class="form-control form-control-sm" id="cond_humidity_val2_1" placeholder="Số 2">
                                    </div>
                                </div>

                                <!-- ĐK 2 -->
                                <div class="row align-items-center">
                                    <div class="col-4 pr-1">
                                        <label class="small fw-bold text-muted mb-1 d-block">ĐK 2 - Toán tử</label>
                                        <select class="form-control form-control-sm op-selector" id="cond_humidity_op_2">
                                            <option value="±" selected>±</option>
                                            <option value="≤">≤</option>
                                            <option value="≥">≥</option>
                                            <option value="between">khoảng</option>
                                            <option value="=">=</option>
                                        </select>
                                    </div>
                                    <div class="col-4 px-1">
                                        <label class="small text-muted mb-1 d-block">Giá trị 1</label>
                                        <input type="number" step="any" class="form-control form-control-sm" id="cond_humidity_val1_2" placeholder="Số">
                                    </div>
                                    <div class="col-4 pl-1 val2-container">
                                        <label class="small text-muted mb-1 d-block">Giá trị 2 / Sai số</label>
                                        <input type="number" step="any" class="form-control form-control-sm" id="cond_humidity_val2_2" placeholder="Số 2">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-2">
                        <!-- Chênh áp -->
                        <div class="col-md-9 mb-3 border-right">
                            <div class="p-3 bg-white rounded border h-100 shadow-sm">
                                <label class="fw-bold small text-primary mb-3 d-block"><i class="fas fa-compress-arrows-alt me-1"></i> Áp suất chênh lệch (Pa)</label>
                                <div class="row">
                                    <!-- P/ Hành lang -->
                                    <div class="col-4">
                                        <div class="row align-items-center">
                                            <div class="col-12 mb-1">
                                                <label class="small fw-bold text-muted mb-0">P/ Hành Lang</label>
                                            </div>
                                            <div class="col-5 pr-1">
                                                <select class="form-control form-control-sm op-selector" id="cond_diff_press_corridor_op">
                                                    <option value="≥">≥</option>
                                                    <option value="≤">≤</option>
                                                    <option value="±">±</option>
                                                    <option value="between">khoảng</option>
                                                    <option value="=">=</option>
                                                </select>
                                            </div>
                                            <div class="col-7 pl-1">
                                                <div class="input-group input-group-sm align-items-center">
                                                    <input type="number" step="any" class="form-control form-control-sm" id="cond_diff_press_corridor_val1" placeholder="Số">
                                                    <input type="number" step="any" class="form-control form-control-sm val2-input ml-1" id="cond_diff_press_corridor_val2" placeholder="Số 2" style="display: none; width: 45px;">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- P/ PAL -->
                                    <div class="col-4">
                                        <div class="row align-items-center">
                                            <div class="col-12 mb-1">
                                                <label class="small fw-bold text-muted mb-0">P/ PAL</label>
                                            </div>
                                            <div class="col-5 pr-1">
                                                <select class="form-control form-control-sm op-selector" id="cond_diff_press_pal_op">
                                                    <option value="≥">≥</option>
                                                    <option value="≤">≤</option>
                                                    <option value="±">±</option>
                                                    <option value="between">khoảng</option>
                                                    <option value="=">=</option>
                                                </select>
                                            </div>
                                            <div class="col-7 pl-1">
                                                <div class="input-group input-group-sm align-items-center">
                                                    <input type="number" step="any" class="form-control form-control-sm" id="cond_diff_press_pal_val1" placeholder="Số">
                                                    <input type="number" step="any" class="form-control form-control-sm val2-input ml-1" id="cond_diff_press_pal_val2" placeholder="Số 2" style="display: none; width: 45px;">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- P/ MAL -->
                                    <div class="col-4">
                                        <div class="row align-items-center">
                                            <div class="col-12 mb-1">
                                                <label class="small fw-bold text-muted mb-0">P/ MAL</label>
                                            </div>
                                            <div class="col-5 pr-1">
                                                <select class="form-control form-control-sm op-selector" id="cond_diff_press_mal_op">
                                                    <option value="≥">≥</option>
                                                    <option value="≤">≤</option>
                                                    <option value="±">±</option>
                                                    <option value="between">khoảng</option>
                                                    <option value="=">=</option>
                                                </select>
                                            </div>
                                            <div class="col-7 pl-1">
                                                <div class="input-group input-group-sm align-items-center">
                                                    <input type="number" step="any" class="form-control form-control-sm" id="cond_diff_press_mal_val1" placeholder="Số">
                                                    <input type="number" step="any" class="form-control form-control-sm val2-input ml-1" id="cond_diff_press_mal_val2" placeholder="Số 2" style="display: none; width: 45px;">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Chênh áp Lọc HEPA -->
                        <div class="col-md-3 mb-3">
                            <div class="p-3 bg-white rounded border h-100 shadow-sm">
                                <label class="fw-bold small text-primary mb-3 d-block"><i class="fas fa-filter me-1"></i> Lọc HEPA Buồng cân (Pa)</label>
                                <div class="row align-items-center">
                                    <div class="col-5 pr-1">
                                        <select class="form-control form-control-sm op-selector" id="cond_hepa_filter_op">
                                            <option value="≤">≤</option>
                                            <option value="≥">≥</option>
                                            <option value="±">±</option>
                                            <option value="between">khoảng</option>
                                            <option value="=">=</option>
                                        </select>
                                    </div>
                                    <div class="col-7 pl-1">
                                        <div class="input-group input-group-sm align-items-center">
                                            <input type="number" step="any" class="form-control form-control-sm" id="cond_hepa_filter_val1" placeholder="Số">
                                            <input type="number" step="any" class="form-control form-control-sm val2-input ml-1" id="cond_hepa_filter_val2" placeholder="Số 2" style="display: none; width: 45px;">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="text-right mt-3">
                        <button type="button" class="btn btn-secondary fw-bold px-4 mr-2" id="btn_cancel_edit" style="display: none;">
                            <i class="fas fa-times-circle me-2"></i> Hủy chỉnh sửa
                        </button>
                        <button type="submit" class="btn btn-success fw-bold px-4 shadow-sm" id="btn_add_condition_confirm">
                            <i class="fas fa-plus-circle me-2"></i> Lưu bộ điều kiện
                        </button>
                    </div>
                </form>

                <!-- Danh sách điều kiện hiện tại -->
                <h6 class="fw-bold text-dark mb-2"><i class="fas fa-list me-1"></i> Danh sách các bộ điều kiện của phòng:</h6>
                <div class="table-responsive" style="max-height: 350px;">
                    <table class="table table-hover border">
                        <thead class="bg-light">
                            <tr>
                                <th>Tên Bộ Điều Kiện</th>
                                <th>Nhiệt Độ (°C)</th>
                                <th>Độ Ẩm (%)</th>
                                <th>Chênh áp P/HL (Pa)</th>
                                <th>Chênh áp P/PAL (Pa)</th>
                                <th>Chênh áp P/MAL (Pa)</th>
                                <th>Chênh áp HEPA (Pa)</th>
                                <th>Ghi Chú</th>
                                <th class="text-center" style="width: 80px;">Thao Tác</th>
                            </tr>
                        </thead>
                        <tbody id="room_conditions_body">
                            <tr>
                                <td colspan="9" class="text-center text-muted py-3">Đang tải danh sách...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light border-0 py-3 px-4">
                <button type="button" class="btn btn-light px-4" data-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    let conditionChanges = false;
    window.currentRoomConditions = [];

    // Reset thay đổi khi mở modal
    $('#conditionModal').on('show.bs.modal', function () {
        conditionChanges = false;
        resetConditionForm();
    });

    // Reload trang khi đóng modal nếu có thay đổi
    $('#conditionModal').on('hidden.bs.modal', function () {
        if (conditionChanges) {
            location.reload();
        }
    });

    // Bắt sự kiện thay đổi toán tử để ẩn/hiện ô input thứ 2
    $(document).on('change', '.op-selector', function() {
        const select = $(this);
        const row = select.closest('.row');
        const val = select.val();
        
        // 1. Đối với nhóm Nhiệt độ / Độ ẩm có class .val2-container
        const container = row.find('.val2-container');
        if (container.length > 0) {
            const val2Input = container.find('input');
            if (val === '≤' || val === '≥' || val === '=') {
                container.css('opacity', '0.2');
                val2Input.prop('disabled', true).val('');
            } else {
                container.css('opacity', '1');
                val2Input.prop('disabled', false);
            }
        }
        
        // 2. Đối với nhóm Áp suất dạng input-group
        const val2InputGroup = row.find('.val2-input');
        if (val2InputGroup.length > 0) {
            if (val === '≤' || val === '≥' || val === '=') {
                val2InputGroup.hide().val('').prop('disabled', true);
            } else {
                val2InputGroup.show().prop('disabled', false);
            }
        }
    });

    // Helper format điều kiện hiển thị
    function formatConditionJS(op, val1, val2, unit) {
        if (val1 === null || val1 === undefined || val1 === '') {
            return '-';
        }
        if (op === '≤' || op === '≥' || op === '=') {
            return `${op} ${val1}${unit}`;
        }
        if (op === '±') {
            return `${val1} &plusmn; ${val2}${unit}`;
        }
        if (op === 'between') {
            return `${val1} - ${val2}${unit}`;
        }
        return `${val1}${unit}`;
    }

    // Hàm reset form về trạng thái Thêm mới
    function resetConditionForm() {
        $('#cond_id').val('');
        $('#form_add_condition')[0].reset();
        $('.op-selector').trigger('change');
        $('#form_add_condition h6').html('<i class="fas fa-plus-circle me-1"></i> Thêm bộ điều kiện mới');
        $('#btn_add_condition_confirm').html('<i class="fas fa-plus-circle me-2"></i> Lưu bộ điều kiện').removeClass('btn-warning').addClass('btn-success');
        $('#btn_cancel_edit').hide();
    }

    // Hàm load các bộ điều kiện của phòng
    window.loadRoomConditions = function(roomId) {
        $('#room_conditions_body').html('<tr><td colspan="9" class="text-center text-muted py-3"><i class="fas fa-spinner fa-spin me-2"></i> Đang tải dữ liệu...</td></tr>');
        window.currentRoomConditions = [];
        
        $.ajax({
            url: "{{ route('pages.manu_env.room.getConditions') }}",
            type: 'GET',
            data: { room_id: roomId },
            success: function(response) {
                let html = '';
                if(response.conditions && response.conditions.length > 0) {
                    window.currentRoomConditions = response.conditions;
                    response.conditions.forEach(function(item) {
                        // Format nhiệt độ
                        let t1 = formatConditionJS(item.temp_op_1, item.temp_val1_1, item.temp_val2_1, '°C');
                        let t2 = formatConditionJS(item.temp_op_2, item.temp_val1_2, item.temp_val2_2, '°C');
                        let tempFormatted = '-';
                        if (t1 !== '-' && t2 !== '-') tempFormatted = `${t1} & ${t2}`;
                        else if (t1 !== '-') tempFormatted = t1;
                        else tempFormatted = t2;

                        // Format độ ẩm
                        let h1 = formatConditionJS(item.humidity_op_1, item.humidity_val1_1, item.humidity_val2_1, '%');
                        let h2 = formatConditionJS(item.humidity_op_2, item.humidity_val1_2, item.humidity_val2_2, '%');
                        let humidityFormatted = '-';
                        if (h1 !== '-' && h2 !== '-') humidityFormatted = `${h1} & ${h2}`;
                        else if (h1 !== '-') humidityFormatted = h1;
                        else humidityFormatted = h2;

                        // Format các chênh áp
                        let pCorridor = formatConditionJS(item.diff_press_corridor_op, item.diff_press_corridor_val1, item.diff_press_corridor_val2, ' Pa');
                        let pPal = formatConditionJS(item.diff_press_pal_op, item.diff_press_pal_val1, item.diff_press_pal_val2, ' Pa');
                        let pMal = formatConditionJS(item.diff_press_mal_op, item.diff_press_mal_val1, item.diff_press_mal_val2, ' Pa');
                        let pHepa = formatConditionJS(item.hepa_filter_op, item.hepa_filter_val1, item.hepa_filter_val2, ' Pa');

                        html += `
                            <tr>
                                <td class="fw-bold text-success">${item.name}</td>
                                <td class="fw-medium">${tempFormatted}</td>
                                <td class="fw-medium">${humidityFormatted}</td>
                                <td>${pCorridor}</td>
                                <td>${pPal}</td>
                                <td>${pMal}</td>
                                <td>${pHepa}</td>
                                <td class="small text-muted">${item.note || '-'}</td>
                                <td class="text-center align-middle">
                                    <button type="button" class="btn btn-sm btn-icon btn-light-warning border btn-edit-cond" data-cond-id="${item.id}" title="Chỉnh sửa">
                                        <i class="fas fa-pen"></i>
                                    </button>
                                </td>
                            </tr>
                        `;
                    });
                } else {
                    html = '<tr><td colspan="9" class="text-center text-muted py-3">Chưa khai báo bộ điều kiện sản xuất nào cho phòng này.</td></tr>';
                }
                $('#room_conditions_body').html(html);
            },
            error: function() {
                $('#room_conditions_body').html('<tr><td colspan="9" class="text-center text-danger py-3">Lỗi tải dữ liệu. Vui lòng thử lại.</td></tr>');
            }
        });
    };

    // Bấm nút sửa điều kiện
    $(document).on('click', '.btn-edit-cond', function() {
        const condId = $(this).data('cond-id');
        const item = window.currentRoomConditions.find(c => c.id == condId);
        if (!item) return;

        // Điền dữ liệu vào form
        $('#cond_id').val(item.id);
        $('#cond_name').val(item.name);
        $('#cond_note').val(item.note || '');

        // Nhiệt độ ĐK 1
        $('#cond_temp_op_1').val(item.temp_op_1 || '≤').trigger('change');
        $('#cond_temp_val1_1').val(item.temp_val1_1);
        $('#cond_temp_val2_1').val(item.temp_val2_1);
        
        // Nhiệt độ ĐK 2
        $('#cond_temp_op_2').val(item.temp_op_2 || '±').trigger('change');
        $('#cond_temp_val1_2').val(item.temp_val1_2);
        $('#cond_temp_val2_2').val(item.temp_val2_2);

        // Độ ẩm ĐK 1
        $('#cond_humidity_op_1').val(item.humidity_op_1 || '≤').trigger('change');
        $('#cond_humidity_val1_1').val(item.humidity_val1_1);
        $('#cond_humidity_val2_1').val(item.humidity_val2_1);
        
        // Độ ẩm ĐK 2
        $('#cond_humidity_op_2').val(item.humidity_op_2 || '±').trigger('change');
        $('#cond_humidity_val1_2').val(item.humidity_val1_2);
        $('#cond_humidity_val2_2').val(item.humidity_val2_2);

        // Chênh áp Corridor
        $('#cond_diff_press_corridor_op').val(item.diff_press_corridor_op || '≥').trigger('change');
        $('#cond_diff_press_corridor_val1').val(item.diff_press_corridor_val1);
        $('#cond_diff_press_corridor_val2').val(item.diff_press_corridor_val2);

        // Chênh áp PAL
        $('#cond_diff_press_pal_op').val(item.diff_press_pal_op || '≥').trigger('change');
        $('#cond_diff_press_pal_val1').val(item.diff_press_pal_val1);
        $('#cond_diff_press_pal_val2').val(item.diff_press_pal_val2);

        // Chênh áp MAL
        $('#cond_diff_press_mal_op').val(item.diff_press_mal_op || '≥').trigger('change');
        $('#cond_diff_press_mal_val1').val(item.diff_press_mal_val1);
        $('#cond_diff_press_mal_val2').val(item.diff_press_mal_val2);

        // Lọc HEPA
        $('#cond_hepa_filter_op').val(item.hepa_filter_op || '≤').trigger('change');
        $('#cond_hepa_filter_val1').val(item.hepa_filter_val1);
        $('#cond_hepa_filter_val2').val(item.hepa_filter_val2);

        // Cập nhật giao diện nút và tiêu đề form
        $('#form_add_condition h6').html('<i class="fas fa-edit me-1"></i> Cập nhật bộ điều kiện sản xuất');
        $('#btn_add_condition_confirm').html('<i class="fas fa-save me-2"></i> Cập nhật').removeClass('btn-success').addClass('btn-warning');
        $('#btn_cancel_edit').show();
        
        // Cuộn mượt lên đầu form
        $('#conditionModal').animate({ scrollTop: 0 }, 'fast');
    });

    // Bấm nút Hủy sửa
    $('#btn_cancel_edit').click(function() {
        resetConditionForm();
    });

    // Gửi form (Thêm mới hoặc Cập nhật)
    $('#form_add_condition').submit(function(e) {
        e.preventDefault();
        const roomId = $('#condition_room_id').val();
        const condId = $('#cond_id').val();
        const url = condId ? "{{ route('pages.manu_env.room.updateCondition') }}" : "{{ route('pages.manu_env.room.storeCondition') }}";

        $.ajax({
            url: url,
            type: 'POST',
            data: {
                id: condId,
                room_id: roomId,
                name: $('#cond_name').val(),
                note: $('#cond_note').val(),
                
                temp_op_1: $('#cond_temp_op_1').val(),
                temp_val1_1: $('#cond_temp_val1_1').val(),
                temp_val2_1: $('#cond_temp_val2_1').val(),
                
                temp_op_2: $('#cond_temp_op_2').val(),
                temp_val1_2: $('#cond_temp_val1_2').val(),
                temp_val2_2: $('#cond_temp_val2_2').val(),
                
                humidity_op_1: $('#cond_humidity_op_1').val(),
                humidity_val1_1: $('#cond_humidity_val1_1').val(),
                humidity_val2_1: $('#cond_humidity_val2_1').val(),
                
                humidity_op_2: $('#cond_humidity_op_2').val(),
                humidity_val1_2: $('#cond_humidity_val1_2').val(),
                humidity_val2_2: $('#cond_humidity_val2_2').val(),
                
                diff_press_corridor_op: $('#cond_diff_press_corridor_op').val(),
                diff_press_corridor_val1: $('#cond_diff_press_corridor_val1').val(),
                diff_press_corridor_val2: $('#cond_diff_press_corridor_val2').val(),
                
                diff_press_pal_op: $('#cond_diff_press_pal_op').val(),
                diff_press_pal_val1: $('#cond_diff_press_pal_val1').val(),
                diff_press_pal_val2: $('#cond_diff_press_pal_val2').val(),
                
                diff_press_mal_op: $('#cond_diff_press_mal_op').val(),
                diff_press_mal_val1: $('#cond_diff_press_mal_val1').val(),
                diff_press_mal_val2: $('#cond_diff_press_mal_val2').val(),
                
                hepa_filter_op: $('#cond_hepa_filter_op').val(),
                hepa_filter_val1: $('#cond_hepa_filter_val1').val(),
                hepa_filter_val2: $('#cond_hepa_filter_val2').val()
            },
            success: function(response) {
                if(response.success) {
                    conditionChanges = true;
                    Swal.fire({
                        title: 'Thành công!',
                        text: response.message,
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false
                    });
                    resetConditionForm();
                    // Reload danh sách
                    loadRoomConditions(roomId);
                } else {
                    Swal.fire({
                        title: 'Lỗi!',
                        text: response.message,
                        icon: 'error'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    title: 'Lỗi!',
                    text: 'Không thể kết nối đến máy chủ. Vui lòng thử lại.',
                    icon: 'error'
                });
            }
        });
    });
});
</script>
