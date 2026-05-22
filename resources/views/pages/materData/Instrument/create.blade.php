<div class="modal fade" id="createModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <form action="{{ route('pages.materData.instrument.store') }}" method="POST">
            @csrf
            <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">
                <div class="modal-header bg-light border-0 py-3 px-4">
                    <h5 class="modal-title fw-bold text-primary"><i class="fas fa-plus-circle me-1"></i> Thêm Thiết Bị Sản Xuất</h5>
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
                                <input type="text" class="form-control" name="code" value="{{ old('code') }}" required placeholder="Ví dụ: TB01">
                                @error('code', 'createErrors')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group mb-3">
                                <label class="fw-bold small text-uppercase text-muted mb-2">Tên Thiết Bị</label>
                                <input type="text" class="form-control" name="name" value="{{ old('name') }}" required placeholder="Ví dụ: Cân phân tích 01">
                                @error('name', 'createErrors')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group mb-3">
                                <label class="fw-bold small text-uppercase text-muted mb-2">Công Đoạn</label>
                                <select class="form-control" name="stage_id">
                                    <option value="">-- Chọn Công Đoạn --</option>
                                    @foreach ($stages as $id => $label)
                                        <option value="{{ $id }}" {{ old('stage_id') == $id ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('stage_id', 'createErrors')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group mb-3">
                                <label class="fw-bold small text-uppercase text-muted mb-2">Loại Thiết Bị</label>
                                <select class="form-control" name="type" id="create_type" required>
                                    <option value="other" {{ old('type') == 'other' ? 'selected' : '' }}>Khác</option>
                                    <option value="scale" {{ old('type') == 'scale' ? 'selected' : '' }}>Cân Điện Tử</option>
                                </select>
                                @error('type', 'createErrors')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Cột Phải: Cấu hình kết nối cân -->
                        <div class="col-md-6">
                            <div id="create_scale_config_section" style="display: none;" class="p-3 border rounded bg-light h-100">
                                <h6 class="fw-bold text-success mb-3"><i class="fas fa-wifi me-1"></i> Cấu hình kết nối Cân</h6>
                                
                                <div class="form-group mb-3">
                                    <label class="fw-bold small text-uppercase text-muted mb-2">Hãng Cân</label>
                                    <select class="form-control" name="brand" id="create_brand">
                                        <option value="and" {{ old('brand') == 'and' ? 'selected' : '' }}>A&D (AND)</option>
                                        <option value="mettler" {{ old('brand') == 'mettler' ? 'selected' : '' }}>Mettler Toledo</option>
                                        <option value="sartorius" {{ old('brand') == 'sartorius' ? 'selected' : '' }}>Sartorius</option>
                                        <option value="custom" {{ old('brand') == 'custom' ? 'selected' : '' }}>Tùy chỉnh (hãng khác)</option>
                                    </select>
                                    @error('brand', 'createErrors')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group mb-3">
                                    <label class="fw-bold small text-uppercase text-muted mb-2">Phương thức kết nối</label>
                                    <select class="form-control" name="connection_type" id="create_connection_type">
                                        <option value="serial" {{ old('connection_type') == 'serial' ? 'selected' : '' }}>Cáp vật lý (Web Serial)</option>
                                        <option value="websocket" {{ old('connection_type') == 'websocket' ? 'selected' : '' }}>WebSocket (Wifi)</option>
                                    </select>
                                    @error('connection_type', 'createErrors')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- WebSocket Fields -->
                                <div id="create_websocket_fields" style="display: none;">
                                    <div class="row">
                                        <div class="col-8">
                                            <div class="form-group mb-3">
                                                <label class="fw-bold small text-uppercase text-muted mb-2">Địa chỉ IP</label>
                                                <input type="text" class="form-control" name="ip" id="create_ip" value="{{ old('ip') }}" placeholder="192.168.1.100">
                                                @error('ip', 'createErrors')
                                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="form-group mb-3">
                                                <label class="fw-bold small text-uppercase text-muted mb-2">Cổng (Port)</label>
                                                <input type="text" class="form-control" name="port" id="create_port" value="{{ old('port') }}" placeholder="8080">
                                                @error('port', 'createErrors')
                                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Serial Fields -->
                                <div id="create_serial_fields">
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="form-group mb-2">
                                                <label class="x-small text-uppercase text-muted mb-1">Baud Rate</label>
                                                <select class="form-control form-control-sm" name="baud_rate" id="create_baud_rate">
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
                                        </div>
                                        <div class="col-6">
                                            <div class="form-group mb-2">
                                                <label class="x-small text-uppercase text-muted mb-1">Data Bits</label>
                                                <select class="form-control form-control-sm" name="data_bits" id="create_data_bits">
                                                    <option value="7">7</option>
                                                    <option value="8" selected>8</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="form-group mb-2">
                                                <label class="x-small text-uppercase text-muted mb-1">Parity</label>
                                                <select class="form-control form-control-sm" name="parity" id="create_parity">
                                                    <option value="none" selected>None</option>
                                                    <option value="even">Even</option>
                                                    <option value="odd">Odd</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="form-group mb-2">
                                                <label class="x-small text-uppercase text-muted mb-1">Stop Bits</label>
                                                <select class="form-control form-control-sm" name="stop_bits" id="create_stop_bits">
                                                    <option value="1" selected>1</option>
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
                    <button type="submit" class="btn btn-primary px-4 fw-bold">Lưu lại</button>
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

    function toggleCreateFields() {
        const type = $('#create_type').val();
        const connType = $('#create_connection_type').val();

        if (type === 'scale') {
            $('#create_scale_config_section').show();
            if (connType === 'websocket') {
                $('#create_websocket_fields').show();
                $('#create_serial_fields').hide();
            } else {
                $('#create_websocket_fields').hide();
                $('#create_serial_fields').show();
            }
        } else {
            $('#create_scale_config_section').hide();
        }
    }

    function applyBrandPresets() {
        const brand = $('#create_brand').val();
        if (brand !== 'custom' && presets[brand]) {
            const config = presets[brand];
            $('#create_baud_rate').val(config.baudRate);
            $('#create_data_bits').val(config.dataBits);
            $('#create_parity').val(config.parity);
            $('#create_stop_bits').val(config.stopBits);
        }
    }

    $('#create_type').change(toggleCreateFields);
    $('#create_connection_type').change(toggleCreateFields);
    $('#create_brand').change(applyBrandPresets);

    // Initial triggers
    toggleCreateFields();
    if ($('#create_brand').val() !== 'custom') {
        applyBrandPresets();
    }
});
</script>
