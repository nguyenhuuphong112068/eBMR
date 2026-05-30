<div class="modal fade" id="updateModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <form action="{{ route('pages.manu_env.equipment.update') }}" method="POST">
            @csrf
            <input type="hidden" name="id" id="update_id">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">
                <div class="modal-header bg-light border-0 py-3 px-4">
                    <h5 class="modal-title fw-bold text-warning"><i class="fas fa-edit me-1"></i> Cập Nhật Thiết Bị</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-4">
                    <div class="row">
                        <!-- Cột Trái: Thông tin chung -->
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="fw-bold small text-uppercase text-muted mb-2">Mã Thiết Bị</label>
                                <input type="text" class="form-control" name="code" id="update_code" required>
                                @error('code', 'updateErrors')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group mb-3">
                                <label class="fw-bold small text-uppercase text-muted mb-2">Tên Thiết Bị</label>
                                <input type="text" class="form-control" name="name" id="update_name" required>
                                @error('name', 'updateErrors')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group mb-3">
                                <label class="fw-bold small text-uppercase text-muted mb-2">Công Đoạn</label>
                                <select class="form-control" name="stage_id" id="update_stage_id">
                                    <option value="">-- Chọn Công Đoạn --</option>
                                    @foreach ($stages as $id => $label)
                                        <option value="{{ $id }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('stage_id', 'updateErrors')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group mb-3">
                                <label class="fw-bold small text-uppercase text-muted mb-2">Loại Thiết Bị</label>
                                <select class="form-control" name="type" id="update_type" required>
                                    <option value="other">Khác</option>
                                    <option value="scale">Cân Điện Tử</option>
                                </select>
                                @error('type', 'updateErrors')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group mb-3">
                                <label class="fw-bold small text-uppercase text-muted mb-2">SOP Vận Hành</label>
                                <input type="text" class="form-control" name="operation_SOP_code" id="update_operation_SOP_code" placeholder="Ví dụ: SOP-VH-01">
                                @error('operation_SOP_code', 'updateErrors')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group mb-3">
                                <label class="fw-bold small text-uppercase text-muted mb-2">SOP Vệ Sinh</label>
                                <input type="text" class="form-control" name="clearing_SOP_code" id="update_clearing_SOP_code" placeholder="Ví dụ: SOP-VS-01">
                                @error('clearing_SOP_code', 'updateErrors')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group mb-3">
                                <div class="custom-control custom-checkbox mt-2">
                                    <input type="checkbox" class="custom-control-input" id="update_is_Portable_equipment" name="is_Portable_equipment" value="1">
                                    <label class="custom-control-label fw-bold text-dark small" for="update_is_Portable_equipment">Thiết bị di động (Portable)</label>
                                </div>
                            </div>
                        </div>

                        <!-- Cột Phải: Cấu hình kết nối cân -->
                        <div class="col-md-6">
                            <div id="update_scale_config_section" style="display: none;" class="p-3 border rounded bg-light h-100">
                                <h6 class="fw-bold text-success mb-3"><i class="fas fa-wifi me-1"></i> Cấu hình kết nối Cân</h6>
                                
                                <div class="form-group mb-3">
                                    <label class="fw-bold small text-uppercase text-muted mb-2">Hãng Cân</label>
                                    <select class="form-control" name="brand" id="update_brand">
                                        <option value="and">A&D (AND)</option>
                                        <option value="mettler">Mettler Toledo</option>
                                        <option value="sartorius">Sartorius</option>
                                        <option value="custom">Tùy chỉnh (hãng khác)</option>
                                    </select>
                                    @error('brand', 'updateErrors')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group mb-3">
                                    <label class="fw-bold small text-uppercase text-muted mb-2">Phương thức kết nối</label>
                                    <select class="form-control" name="connection_type" id="update_connection_type">
                                        <option value="serial">Cáp vật lý (Web Serial)</option>
                                        <option value="websocket">WebSocket (Wifi)</option>
                                    </select>
                                    @error('connection_type', 'updateErrors')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- WebSocket Fields -->
                                <div id="update_websocket_fields" style="display: none;">
                                    <div class="row">
                                        <div class="col-8">
                                            <div class="form-group mb-3">
                                                <label class="fw-bold small text-uppercase text-muted mb-2">Địa chỉ IP</label>
                                                <input type="text" class="form-control" name="ip" id="update_ip" placeholder="192.168.1.100">
                                                @error('ip', 'updateErrors')
                                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="form-group mb-3">
                                                <label class="fw-bold small text-uppercase text-muted mb-2">Cổng (Port)</label>
                                                <input type="text" class="form-control" name="port" id="update_port" placeholder="8080">
                                                @error('port', 'updateErrors')
                                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Serial Fields -->
                                <div id="update_serial_fields">
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="form-group mb-2">
                                                <label class="x-small text-uppercase text-muted mb-1">Baud Rate</label>
                                                <select class="form-control form-control-sm" name="baud_rate" id="update_baud_rate">
                                                    <option value="1200">1200</option>
                                                    <option value="2400">2400</option>
                                                    <option value="4800">4800</option>
                                                    <option value="9600">9600</option>
                                                    <option value="19200">19200</option>
                                                    <option value="38400">38400</option>
                                                    <option value="57600">57600</option>
                                                    <option value="115200">115200</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="form-group mb-2">
                                                <label class="x-small text-uppercase text-muted mb-1">Data Bits</label>
                                                <select class="form-control form-control-sm" name="data_bits" id="update_data_bits">
                                                    <option value="7">7</option>
                                                    <option value="8">8</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="form-group mb-2">
                                                <label class="x-small text-uppercase text-muted mb-1">Parity</label>
                                                <select class="form-control form-control-sm" name="parity" id="update_parity">
                                                    <option value="none">None</option>
                                                    <option value="even">Even</option>
                                                    <option value="odd">Odd</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="form-group mb-2">
                                                <label class="x-small text-uppercase text-muted mb-1">Stop Bits</label>
                                                <select class="form-control form-control-sm" name="stop_bits" id="update_stop_bits">
                                                    <option value="1">1</option>
                                                    <option value="2">2</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 py-3 px-4">
                    <button type="button" class="btn btn-light px-4" data-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-warning px-4 fw-bold text-white">Cập nhật</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
$(document).ready(function() {
    const presets = {
        'and': { baudRate: 2400, dataBits: 7, parity: 'even', stopBits: 1 },
        'mettler': { baudRate: 9600, dataBits: 8, parity: 'none', stopBits: 1 },
        'sartorius': { baudRate: 9600, dataBits: 8, parity: 'none', stopBits: 1 },
        'custom': { baudRate: 9600, dataBits: 8, parity: 'none', stopBits: 1 }
    };

    function toggleUpdateFields() {
        const type = $('#update_type').val();
        const connType = $('#update_connection_type').val();

        if (type === 'scale') {
            $('#update_scale_config_section').show();
            if (connType === 'websocket') {
                $('#update_websocket_fields').show();
                $('#update_serial_fields').hide();
            } else {
                $('#update_websocket_fields').hide();
                $('#update_serial_fields').show();
            }
        } else {
            $('#update_scale_config_section').hide();
        }
    }

    function applyBrandPresets() {
        const brand = $('#update_brand').val();
        if (brand !== 'custom' && presets[brand]) {
            const config = presets[brand];
            $('#update_baud_rate').val(config.baudRate);
            $('#update_data_bits').val(config.dataBits);
            $('#update_parity').val(config.parity);
            $('#update_stop_bits').val(config.stopBits);
        }
    }

    $('#update_type').change(toggleUpdateFields);
    $('#update_connection_type').change(toggleUpdateFields);
    $('#update_brand').change(applyBrandPresets);

    // Make global for open / init triggers
    window.initUpdateModalScaleFields = function() {
        toggleUpdateFields();
    };
});
</script>
