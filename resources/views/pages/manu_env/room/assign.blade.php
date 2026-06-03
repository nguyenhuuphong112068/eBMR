<div class="modal fade" id="assignModal" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">
            <div class="modal-header bg-light border-0 py-3 px-4">
                <h5 class="modal-title fw-bold text-success" id="assign_room_title"><i class="fas fa-desktop me-1"></i>
                    Khai báo thiết bị trong phòng</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" id="assign_room_id">

                <!-- Form khai báo thiết bị mới -->
                <div class="row align-items-end mb-4 p-3 border rounded bg-light">
                    <div class="col-md-9">
                        <div class="form-group mb-0">
                            <label class="fw-bold small text-uppercase text-muted mb-2"><i class="fas fa-plug me-1"></i>
                                Chọn thiết bị cố định để thêm</label>
                            <select class="form-control select2" id="assign_equipment_id" multiple="multiple"
                                style="width: 100%;">
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <button type="button"
                            class="btn btn-success w-100 fw-bold d-flex align-items-center justify-content-center"
                            id="btn_assign_confirm" style="height: 38px;">
                            <i class="fas fa-plus-circle me-2"></i> Khai báo
                        </button>
                    </div>
                </div>

                <!-- Danh sách thiết bị hiện tại -->
                <h6 class="fw-bold text-dark mb-2"><i class="fas fa-list me-1"></i> Thiết bị đang có trong phòng:</h6>
                <div class="table-responsive" style="max-height: 350px;">
                    <table class="table table-hover border">
                        <thead class="bg-light">
                            <tr>
                                <th>Mã Thiết Bị</th>
                                <th>Tên Thiết Bị</th>
                                <th>SOP Vận Hành</th>
                                <th>SOP Vệ Sinh</th>
                                <th class="text-center" style="width: 100px;">Thao Tác</th>
                            </tr>
                        </thead>
                        <tbody id="assigned_equipments_body">
                            <tr>
                                <td colspan="5" class="text-center text-muted py-3">Đang tải danh sách...</td>
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
        // Thiết lập CSRF Token cho tất cả các yêu cầu AJAX
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        let assignedIds = [];
        let hasChanges = false;

        // Reset changes flag on modal open
        $('#assignModal').on('show.bs.modal', function() {
            hasChanges = false;
        });

        $('#assign_equipment_id').select2({
            theme: 'bootstrap4',
            placeholder: "Chọn các thiết bị để thêm",
            allowClear: true,
            dropdownParent: $('#assignModal')
        });

        // Reload page on modal close if changes were made
        $('#assignModal').on('hidden.bs.modal', function() {
            if (hasChanges) {
                location.reload();
            }
        });

        // Hàm load thiết bị của phòng
        window.loadRoomEquipments = function(roomId) {
            $('#assigned_equipments_body').html(
                '<tr><td colspan="5" class="text-center text-muted py-3"><i class="fas fa-spinner fa-spin me-2"></i> Đang tải danh sách thiết bị...</td></tr>'
            );
            $('#assign_equipment_id').html('').trigger('change');
            assignedIds = [];

            $.ajax({
                url: "{{ route('pages.manu_env.room.getEquipments') }}",
                type: 'GET',
                data: {
                    room_id: roomId
                },
                success: function(response) {
                    // 1. Hiển thị danh sách thiết bị đã gán
                    let html = '';
                    if (response.assigned && response.assigned.length > 0) {
                        response.assigned.forEach(function(item) {
                            assignedIds.push(item.id);
                            html += `
                            <tr>
                                <td class="fw-bold text-primary">${item.code}</td>
                                <td class="fw-medium">${item.name}</td>
                                <td>${item.operation_SOP_code || '-'}</td>
                                <td>${item.clearing_SOP_code || '-'}</td>
                                <td class="text-center align-middle">
                                    <button type="button" class="btn btn-sm btn-icon btn-light-danger border btn-remove-eq" data-eq-id="${item.id}" data-code="${item.code}" data-name="${item.name}">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        `;
                        });
                    } else {
                        html =
                            '<tr><td colspan="5" class="text-center text-muted py-3">Chưa có thiết bị cố định nào được khai báo trong phòng này.</td></tr>';
                    }
                    $('#assigned_equipments_body').html(html);

                    // 2. Điền dữ liệu vào dropdown (chỉ hiển thị những thiết bị chưa gán)
                    let selectHtml = '';
                    if (response.allFixed && response.allFixed.length > 0) {
                        let hasAvailable = false;
                        response.allFixed.forEach(function(item) {
                            if (!assignedIds.includes(item.id)) {
                                hasAvailable = true;
                                selectHtml +=
                                    `<option value="${item.id}">${item.code} - ${item.name}</option>`;
                            }
                        });
                        if (!hasAvailable) {
                            selectHtml =
                                '<option value="" disabled>-- Tất cả thiết bị đã được khai báo --</option>';
                        }
                    } else {
                        selectHtml =
                            '<option value="" disabled>-- Không tìm thấy thiết bị nào --</option>';
                    }
                    $('#assign_equipment_id').html(selectHtml).trigger('change');
                },
                error: function() {
                    $('#assigned_equipments_body').html(
                        '<tr><td colspan="5" class="text-center text-danger py-3">Lỗi khi tải danh sách thiết bị. Vui lòng thử lại.</td></tr>'
                    );
                }
            });
        };

        // Nút khai báo thiết bị vào phòng
        $('#btn_assign_confirm').click(function() {
            const roomId = $('#assign_room_id').val();
            const equipmentIds = $('#assign_equipment_id').val();

            if (!equipmentIds || equipmentIds.length === 0) {
                Swal.fire({
                    title: 'Cảnh báo!',
                    text: 'Vui lòng chọn ít nhất một thiết bị.',
                    icon: 'warning',
                    timer: 2000,
                    showConfirmButton: false
                });
                return;
            }

            $.ajax({
                url: "{{ route('pages.manu_env.room.assignEquipment') }}",
                type: 'POST',
                data: {
                    room_id: roomId,
                    equipment_ids: equipmentIds
                },
                success: function(response) {
                    if (response.success) {
                        hasChanges = true;
                        Swal.fire({
                            title: 'Thành công!',
                            text: response.message,
                            icon: 'success',
                            timer: 1500,
                            showConfirmButton: false
                        });
                        loadRoomEquipments(roomId);
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

        // Nút xóa thiết bị khỏi phòng (sử dụng Event Delegation)
        $(document).on('click', '.btn-remove-eq', function() {
            const button = $(this);
            const equipmentId = button.data('eq-id');
            const code = button.data('code');
            const name = button.data('name');
            const roomId = $('#assign_room_id').val();

            Swal.fire({
                title: 'Xác nhận gỡ bỏ?',
                text: `Bạn có chắc chắn muốn gỡ thiết bị: ${code} - ${name} khỏi phòng này?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Gỡ ngay',
                cancelButtonText: 'Hủy'
            }).then((result) => {

                if (result.value) {
                    $.ajax({
                        url: "{{ route('pages.manu_env.room.removeEquipment') }}",
                        type: 'POST',
                        data: {
                            room_id: roomId,
                            equipment_id: equipmentId
                        },
                        success: function(response) {
                            if (response.success) {
                                hasChanges = true;
                                Swal.fire({
                                    title: 'Thành công!',
                                    text: response.message,
                                    icon: 'success',
                                    timer: 1500,
                                    showConfirmButton: false
                                });
                                loadRoomEquipments(roomId);
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
                }
            });
        });
    });
</script>
