<div class="modal fade" id="relatedFormModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-md" role="document">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">
            <div class="modal-header bg-light border-0 py-3 px-4">
                <h5 class="modal-title fw-bold text-primary" id="related_form_title">
                    <i class="fas fa-file-alt me-1"></i> Liên kết biểu mẫu
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" id="related_room_id">
                
                <form id="form_related_forms">
                    <!-- Biểu mẫu dọn quang (line_clearance) -->
                    <div class="form-group mb-4">
                        <label class="fw-bold small text-uppercase text-muted mb-2">
                            <i class="fas fa-broom mr-1 text-success"></i> Biểu mẫu dọn quang (Line Clearance Form)
                        </label>
                        <select id="select_line_clearance" class="form-control" style="width: 100%; height: 38px;">
                            <option value="">-- Chọn biểu mẫu dọn quang --</option>
                        </select>
                        <small class="text-muted">Chọn từ danh mục biểu mẫu chung (GF)</small>
                    </div>

                    <!-- Biểu mẫu vệ sinh (cleaning) -->
                    <div class="form-group mb-4">
                        <label class="fw-bold small text-uppercase text-muted mb-2">
                            <i class="fas fa-soap mr-1 text-info"></i> Biểu mẫu vệ sinh (Cleaning Form)
                        </label>
                        <select id="select_cleaning" class="form-control" style="width: 100%; height: 38px;">
                            <option value="">-- Chọn biểu mẫu vệ sinh --</option>
                        </select>
                        <small class="text-muted">Chọn từ danh mục biểu mẫu chung (GF)</small>
                    </div>

                    <div class="text-right mt-4 pt-2 border-top">
                        <button type="button" class="btn btn-light px-4 mr-2" data-dismiss="modal">Đóng</button>
                        <button type="submit" class="btn btn-primary px-4 fw-bold shadow-sm">
                            <i class="fas fa-save mr-2"></i> Lưu liên kết
                        </button>
                    </div>
                </form>
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

    let formsChanged = false;

    // Reset when modal opens
    $('#relatedFormModal').on('show.bs.modal', function () {
        formsChanged = false;
    });

    // Reload parent page on close if modifications occurred
    $('#relatedFormModal').on('hidden.bs.modal', function () {
        if (formsChanged) {
            location.reload();
        }
    });

    // Load available templates and currently selected templates
    window.loadRoomRelatedForms = function(roomId) {
        // Reset selectors
        $('#select_line_clearance').html('<option value="">-- Đang tải biểu mẫu... --</option>');
        $('#select_cleaning').html('<option value="">-- Đang tải biểu mẫu... --</option>');

        $.ajax({
            url: "{{ route('pages.manu_env.room.getRelatedForms') }}",
            type: 'GET',
            data: { room_id: roomId },
            success: function(response) {
                let optionsHtml = '<option value="">-- Chọn biểu mẫu --</option>';
                if (response.templates && response.templates.length > 0) {
                    response.templates.forEach(function(tpl) {
                        let nameLabel = tpl.category_name ? `${tpl.doc_code} - ${tpl.category_name}` : tpl.doc_code;
                        optionsHtml += `<option value="${tpl.id}">${nameLabel}</option>`;
                    });
                } else {
                    optionsHtml = '<option value="">-- Không có biểu mẫu chung nào trong hệ thống --</option>';
                }

                $('#select_line_clearance').html(optionsHtml);
                $('#select_cleaning').html(optionsHtml);

                // Set current values
                if (response.current) {
                    if (response.current.line_clearance) {
                        $('#select_line_clearance').val(response.current.line_clearance);
                    } else {
                        $('#select_line_clearance').val('');
                    }

                    if (response.current.cleaning) {
                        $('#select_cleaning').val(response.current.cleaning);
                    } else {
                        $('#select_cleaning').val('');
                    }
                }
            },
            error: function() {
                Swal.fire({
                    title: 'Lỗi!',
                    text: 'Không thể tải dữ liệu liên kết biểu mẫu.',
                    icon: 'error'
                });
            }
        });
    };

    // Form submit
    $('#form_related_forms').submit(function(e) {
        e.preventDefault();
        const roomId = $('#related_room_id').val();
        const lineClearanceVal = $('#select_line_clearance').val();
        const cleaningVal = $('#select_cleaning').val();

        $.ajax({
            url: "{{ route('pages.manu_env.room.saveRelatedForms') }}",
            type: 'POST',
            data: {
                room_id: roomId,
                line_clearance_template_id: lineClearanceVal,
                cleaning_template_id: cleaningVal
            },
            success: function(response) {
                if (response.success) {
                    formsChanged = true;
                    Swal.fire({
                        title: 'Thành công!',
                        text: response.message,
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false
                    });
                    $('#relatedFormModal').modal('hide');
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
