<script>
/**
 * eBMR Scale Reader Module
 * Tích hợp đọc dữ liệu từ Cân Điện Tử (A&D, Mettler Toledo, Sartorius) qua RS-232/USB-Serial.
 * Sử dụng Web Serial API (Chrome/Edge 89+).
 * Chế độ: Streaming (lắng nghe liên tục, tự động cập nhật giá trị).
 */

// ============================================================
// CÀI ĐẶT HÃNG CÂN (SCALE PRESETS)
// ============================================================
window.SCALE_PRESETS = {
    'and': {
        label: 'A&D (AND)',
        icon: 'fa-balance-scale',
        baudRate: 9600,
        dataBits: 7,
        parity: 'even',
        stopBits: 1,
        description: 'Cân A&D/AND — Định dạng 17 ký tự ASCII'
    },
    'mettler': {
        label: 'Mettler Toledo',
        icon: 'fa-weight-hanging',
        baudRate: 9600,
        dataBits: 8,
        parity: 'none',
        stopBits: 1,
        description: 'Mettler Toledo — Giao thức MT-SICS'
    },
    'sartorius': {
        label: 'Sartorius',
        icon: 'fa-flask',
        baudRate: 9600,
        dataBits: 8,
        parity: 'none',
        stopBits: 1,
        description: 'Sartorius — Định dạng SBI/chuẩn'
    },
    'custom': {
        label: 'Tùy chỉnh',
        icon: 'fa-cog',
        baudRate: 9600,
        dataBits: 8,
        parity: 'none',
        stopBits: 1,
        description: 'Cài đặt thủ công cho hãng cân khác'
    }
};

// ============================================================
// PARSERS CHO TỪNG HÃNG CÂN
// ============================================================
window.ScaleParsers = {
    /**
     * Parse chuỗi từ cân A&D (AND)
     * Định dạng 17 ký tự: "ST,GS, +00123.45 g\r\n"
     * Hoặc: "US,   , +00123.45 g\r\n" (Unstable)
     */
    parseAND(rawLine) {
        if (!rawLine || rawLine.length < 10) return null;
        const line = rawLine.trim();
        // Nhận diện: bắt đầu bằng ST, US, OL, Err
        if (!/^(ST|US|OL|Err)/i.test(line)) return null;
        const stable = /^ST/i.test(line);
        // Tách phần số từ vị trí ký tự 7 đến 17 (ví dụ: " +00123.45")
        const numMatch = line.match(/([+-]?\s*[\d]+\.?[\d]*)\s*([a-zA-Z]+)?/);
        if (!numMatch) return null;
        const value = parseFloat(numMatch[1].replace(/\s/g, ''));
        const unit = numMatch[2] || 'g';
        if (isNaN(value)) return null;
        return { value, unit, stable, brand: 'and' };
    },

    /**
     * Parse chuỗi từ cân Mettler Toledo (MT-SICS Protocol)
     * Định dạng: "S S      +   123.45 g\r\n" hoặc "S D +00123.45 g\r\n"
     * S S = Stable, S D = Dynamic, S I = Invalid
     */
    parseMettlerToledo(rawLine) {
        if (!rawLine || rawLine.length < 8) return null;
        const line = rawLine.trim();
        if (!/^S\s+[SDI]/i.test(line)) return null;
        const stable = /^S\s+S/i.test(line);
        const numMatch = line.match(/([+-]?\s*[\d]+\.?[\d]*)\s*([a-zA-Z]+)?/);
        if (!numMatch) return null;
        const value = parseFloat(numMatch[1].replace(/\s/g, ''));
        const unit = numMatch[2] || 'g';
        if (isNaN(value)) return null;
        return { value, unit, stable, brand: 'mettler' };
    },

    /**
     * Parse chuỗi từ cân Sartorius
     * Định dạng SBI: "+  123.450 g \r\n" hoặc "N +  123.450 g \r\n"
     * Hoặc dạng ngắn gọn chỉ gồm số và đơn vị
     */
    parseSartorius(rawLine) {
        if (!rawLine || rawLine.length < 5) return null;
        const line = rawLine.trim();
        // Dạng: "N +123.45 g" (N = Net) hoặc dạng chỉ số
        if (!/[+-]?\s*[\d]/.test(line)) return null;
        const stable = !/^[Uu]/.test(line); // Unstable thường có prefix 'U'
        const numMatch = line.match(/([+-]?\s*[\d]+\.?[\d]*)\s*([a-zA-Z]+)?/);
        if (!numMatch) return null;
        const value = parseFloat(numMatch[1].replace(/\s/g, ''));
        const unit = numMatch[2] || 'g';
        if (isNaN(value)) return null;
        return { value, unit, stable, brand: 'sartorius' };
    },

    /**
     * Tự động nhận diện hãng cân từ chuỗi raw
     */
    parseAutoDetect(rawLine) {
        return this.parseAND(rawLine)
            || this.parseMettlerToledo(rawLine)
            || this.parseSartorius(rawLine)
            || null;
    },

    /**
     * Parse theo hãng đã chọn trong cài đặt
     */
    parse(rawLine, brand) {
        switch (brand) {
            case 'and':      return this.parseAND(rawLine);
            case 'mettler':  return this.parseMettlerToledo(rawLine);
            case 'sartorius': return this.parseSartorius(rawLine);
            default:         return this.parseAutoDetect(rawLine);
        }
    }
};

// ============================================================
// SCALE MANAGER — Quản lý kết nối và streaming RS-232
// ============================================================
window.ScaleManager = (function () {
    let _port = null;
    let _reader = null;
    let _readableStreamClosed = null;
    let _textDecoder = null;
    let _isReading = false;
    let _lineBuffer = '';
    let _callbacks = [];           // Danh sách callbacks đăng ký nhận dữ liệu
    let _lastParsedResult = null;
    let _currentBrand = 'auto';

    // Kiểm tra trình duyệt hỗ trợ Web Serial API
    function _isSupported() {
        return 'serial' in navigator;
    }

    // Thêm dòng log vào vùng test kết nối trong modal
    function _log(msg, type = 'info') {
        const logEl = document.getElementById('scale-raw-log');
        if (!logEl) return;
        const colors = { info: '#6c757d', success: '#198754', error: '#dc3545', data: '#0d6efd' };
        const now = new Date().toLocaleTimeString();
        const entry = document.createElement('div');
        entry.style.cssText = `color: ${colors[type] || colors.info}; font-size: 0.75rem; font-family: monospace; border-bottom: 1px solid #f0f0f0; padding: 2px 0;`;
        entry.textContent = `[${now}] ${msg}`;
        logEl.insertBefore(entry, logEl.firstChild);
        // Giới hạn 50 dòng log
        while (logEl.children.length > 50) logEl.removeChild(logEl.lastChild);
    }

    // Cập nhật UI trạng thái kết nối
    function _updateStatus(connected) {
        const statusDot = document.getElementById('scale-status-dot');
        const statusText = document.getElementById('scale-status-text');
        const connectBtn = document.getElementById('scale-connect-btn');
        const disconnectBtn = document.getElementById('scale-disconnect-btn');

        if (statusDot) {
            statusDot.className = `scale-status-dot ${connected ? 'connected' : 'disconnected'}`;
        }
        if (statusText) {
            statusText.textContent = connected ? 'Đã kết nối' : 'Chưa kết nối';
            statusText.className = `small fw-bold ms-2 ${connected ? 'text-success' : 'text-danger'}`;
        }
        if (connectBtn) connectBtn.classList.toggle('d-none', connected);
        if (disconnectBtn) disconnectBtn.classList.toggle('d-none', !connected);
    }

    // Cập nhật trạng thái floating status pill
    function _updateFloatingPill(visible, result = null) {
        let pill = document.getElementById('scale-floating-status');
        if (!visible) {
            if (pill) pill.classList.add('d-none');
            return;
        }

        if (!pill) {
            pill = document.createElement('div');
            pill.id = 'scale-floating-status';
            pill.className = 'scale-floating-status';
            pill.title = 'Nhấp vào đây để cấu hình kết nối Cân điện tử';
            pill.onclick = function() {
                window.openScaleConnectionModal(window._scaleTargetFieldId || '');
            };
            pill.innerHTML = `
                <i class="fas fa-balance-scale text-success"></i>
                <span id="scale-floating-val">—.— g</span>
                <span class="scale-status-dot connected"></span>
            `;
            document.body.appendChild(pill);
        }

        pill.classList.remove('d-none');
        if (result) {
            const valEl = document.getElementById('scale-floating-val');
            if (valEl) valEl.textContent = `${result.value} ${result.unit}`;
            const dotEl = pill.querySelector('.scale-status-dot');
            if (dotEl) {
                dotEl.className = `scale-status-dot ${result.stable ? 'connected' : 'unstable-dot'}`;
            }
        } else {
            const valEl = document.getElementById('scale-floating-val');
            if (valEl) valEl.textContent = 'Đang kết nối...';
            const dotEl = pill.querySelector('.scale-status-dot');
            if (dotEl) {
                dotEl.className = 'scale-status-dot connected';
            }
        }
    }

    // Vòng lặp đọc streaming liên tục
    async function _startReading() {
        _isReading = true;
        _textDecoder = new TextDecoderStream();
        _readableStreamClosed = _port.readable.pipeTo(_textDecoder.writable);
        _reader = _textDecoder.readable.getReader();
        _log('Bắt đầu lắng nghe dữ liệu từ cân...', 'info');

        try {
            while (_isReading) {
                const { value, done } = await _reader.read();
                if (done) break;
                if (value) {
                    _lineBuffer += value;
                    // Xử lý từng dòng (kết thúc bằng \n hoặc \r\n)
                    const lines = _lineBuffer.split(/\r?\n/);
                    _lineBuffer = lines.pop(); // Giữ phần chưa hoàn chỉnh lại

                    for (const line of lines) {
                        const trimmed = line.trim();
                        if (!trimmed) continue;
                        _log(`← ${trimmed}`, 'data');

                        const result = window.ScaleParsers.parse(trimmed, _currentBrand);
                        if (result) {
                            _lastParsedResult = result;
                            // Gọi tất cả callbacks đã đăng ký
                            _callbacks.forEach(cb => {
                                try { cb(result); } catch(e) { console.error('Scale callback error:', e); }
                            });
                            // Cập nhật hiển thị giá trị mới nhất trong modal
                            const liveVal = document.getElementById('scale-live-value');
                            if (liveVal) {
                                liveVal.textContent = `${result.value} ${result.unit}`;
                                liveVal.className = `scale-live-value ${result.stable ? 'stable' : 'unstable'}`;
                            }

                            // Cập nhật floating status pill
                            _updateFloatingPill(true, result);
                        }
                    }
                }
            }
        } catch (err) {
            if (_isReading) {
                _log(`Lỗi đọc dữ liệu: ${err.message}`, 'error');
                console.error('Scale read error:', err);
            }
        } finally {
            _reader.releaseLock();
        }
    }

    return {
        isSupported: _isSupported,
        isConnected: () => _port !== null && _isReading,
        getLastResult: () => _lastParsedResult,
        getCurrentBrand: () => _currentBrand,

        /**
         * Kết nối đến cân
         * @param {string} brand - 'and' | 'mettler' | 'sartorius' | 'custom'
         * @param {object} customConfig - Cài đặt tùy chỉnh (chỉ dùng khi brand = 'custom')
         */
        async connect(brand, customConfig = null) {
            if (!_isSupported()) {
                Swal.fire('Không hỗ trợ', 'Trình duyệt của bạn không hỗ trợ Web Serial API.<br>Vui lòng dùng Chrome hoặc Edge phiên bản 89+.', 'error');
                return false;
            }
            if (_port) {
                _log('Đã kết nối rồi! Vui lòng ngắt kết nối trước.', 'error');
                return false;
            }

            const preset = window.SCALE_PRESETS[brand] || window.SCALE_PRESETS['custom'];
            const config = brand === 'custom' && customConfig ? customConfig : preset;

            try {
                // Mở popup chọn cổng COM — bắt buộc gọi từ gesture của người dùng
                _port = await navigator.serial.requestPort();
                await _port.open({
                    baudRate: Number(config.baudRate) || 9600,
                    dataBits: Number(config.dataBits) || 8,
                    parity: config.parity || 'none',
                    stopBits: Number(config.stopBits) || 1,
                    flowControl: 'none'
                });
                _currentBrand = brand;
                _isReading = true;
                _updateStatus(true);
                _updateFloatingPill(true); // Hiển thị status pill ở góc màn hình
                _log(`✅ Đã kết nối! Hãng cân: ${preset.label} | Baud: ${config.baudRate}`, 'success');

                // Lưu vào cài đặt toàn cục phiên làm việc
                window.scaleConfig = { brand, ...config };

                // Bắt đầu đọc streaming
                _startReading(); // Không await — chạy nền
                return true;
            } catch (err) {
                _port = null;
                _isReading = false;
                _updateFloatingPill(false);
                if (err.name === 'NotFoundError') {
                    _log('Người dùng đã hủy chọn cổng COM.', 'info');
                } else {
                    _log(`❌ Không thể kết nối: ${err.message}`, 'error');
                    Swal.fire('Lỗi kết nối', `Không thể mở cổng serial.<br><small>${err.message}</small>`, 'error');
                }
                return false;
            }
        },

        /**
         * Ngắt kết nối
         */
        async disconnect() {
            _isReading = false;
            _callbacks = [];
            _lineBuffer = '';
            _lastParsedResult = null;

            try {
                if (_reader) {
                    await _reader.cancel();
                    _reader = null;
                }
                if (_port) {
                    await _port.close();
                    _port = null;
                }
                _updateStatus(false);
                _updateFloatingPill(false); // Ẩn status pill
                _log('🔌 Đã ngắt kết nối.', 'info');
            } catch (err) {
                _port = null;
                _updateStatus(false);
                _updateFloatingPill(false);
                console.warn('Scale disconnect error:', err);
            }
        },

        /**
         * Đăng ký callback nhận giá trị mới từ cân (streaming)
         * Callback được gọi mỗi khi cân gửi 1 dòng dữ liệu hợp lệ.
         * @param {Function} cb - function({ value, unit, stable, brand })
         * @returns {Function} unsubscribe - Hủy đăng ký
         */
        onData(cb) {
            _callbacks.push(cb);
            return function unsubscribe() {
                _callbacks = _callbacks.filter(c => c !== cb);
            };
        }
    };
})();

// ============================================================
// HÀM ĐIỀN GIÁ TRỊ CÂN VÀO BIẾN SỐ
// ============================================================

let currentActivePopover = null;
let currentActiveUnsubscribe = null;
let currentActiveTimeout = null;

/**
 * Đóng bất kỳ popover đọc cân nào đang mở và dọn dẹp kết nối tạm thời
 */
window.closeScalePopover = function() {
    if (currentActivePopover) {
        currentActivePopover.remove();
        currentActivePopover = null;
    }
    if (currentActiveUnsubscribe) {
        currentActiveUnsubscribe();
        currentActiveUnsubscribe = null;
    }
    if (currentActiveTimeout) {
        clearTimeout(currentActiveTimeout);
        currentActiveTimeout = null;
    }
    // Khôi phục trạng thái nút đọc trên giao diện
    document.querySelectorAll('.btn-read-scale.reading').forEach(btn => {
        btn.classList.remove('reading');
        btn.title = 'Đọc giá trị từ Cân điện tử (RS-232)';
    });
};

/**
 * Hàm chung ghi giá trị từ cân vào ô biến số và tính toán lại công thức/charts
 */
window.writeScaleValueToField = function(fieldId, value, unit) {
    const field = fieldsConfig ? fieldsConfig[fieldId] : null;
    if (!field) return;

    // Làm tròn theo decimal_places của biến số
    let finalValue = value;
    const dPlaces = field.validation && field.validation.decimal_places !== null
        ? parseInt(field.validation.decimal_places)
        : null;
    if (dPlaces !== null && !isNaN(dPlaces)) {
        finalValue = parseFloat(Number(value).toFixed(dPlaces));
    }

    // Đảm bảo cấu trúc đồng nhất với Server (cell_id = 'default')
    if (typeof window.executionValues[fieldId] !== 'object' || window.executionValues[fieldId] === null) {
        window.executionValues[fieldId] = {};
    }
    window.executionValues[fieldId]['default'] = String(finalValue);

    // Kích hoạt tính lại công thức & vẽ lại giao diện
    if (typeof window.recalculateAllFormulas === 'function') {
        window.recalculateAllFormulas();
    }
    if (typeof renderBlocks === 'function') {
        renderBlocks();
    }
    if (field.block_id && typeof syncLinkedCharts === 'function') {
        syncLinkedCharts(field.block_id);
    }
};

/**
 * Lấy ngay kết quả hiện tại (không cần ổn định) và điền vào ô biến số
 */
window.takeScaleValueImmediately = function(fieldId) {
    const result = window.ScaleManager.getLastResult();
    if (result && result.value !== undefined) {
        window.writeScaleValueToField(fieldId, result.value, result.unit);
        window.closeScalePopover();
        if (typeof toastr !== 'undefined') {
            toastr.success(`✅ Lấy giá trị: ${result.value} ${result.unit} (Lấy ngay)`, 'Cân điện tử', { timeOut: 3000 });
        }
    } else {
        if (typeof toastr !== 'undefined') {
            toastr.warning('Chưa nhận được số liệu nào từ cân để lấy ngay.', 'Cân điện tử');
        }
    }
};

/**
 * Lắng nghe và điền giá trị từ cân vào biến số fieldId.
 * Mở popover hiển thị số cân liên tục và nút "Lấy ngay".
 * @param {string} fieldId
 */
window.readScaleValueIntoField = async function(fieldId) {
    const field = fieldsConfig ? fieldsConfig[fieldId] : null;
    if (!field) return;

    // Nếu chưa kết nối, mở modal kết nối
    if (!window.ScaleManager.isConnected()) {
        window.openScaleConnectionModal(fieldId);
        return;
    }

    // Đóng popover cũ nếu có
    window.closeScalePopover();

    // Tìm nút bấm để hiển thị trạng thái và định vị popover
    const badgeEl = document.querySelector(`.ebmr-field-badge[data-field-id="${fieldId}"]`);
    const readBtn = badgeEl ? badgeEl.querySelector('.btn-read-scale') : null;
    if (readBtn) {
        readBtn.classList.add('reading');
        readBtn.title = 'Đang đọc dữ liệu từ cân...';
    }

    // Tạo popover mới
    const popover = document.createElement('div');
    popover.className = 'scale-reader-popover';
    popover.innerHTML = `
        <div style="font-size: 0.72rem; font-weight: bold; color: #475569; margin-bottom: 2px;">
            <i class="fas fa-balance-scale"></i> Đọc cân vào ô số
        </div>
        <div class="scale-reader-popover-live" id="scale-popover-live-val">—.—</div>
        <div style="font-size: 0.65rem; color: #64748b; text-align: center;" id="scale-popover-status">
            Đang nhận dữ liệu...
        </div>
        <div class="scale-reader-popover-buttons">
            <button class="scale-reader-popover-btn scale-reader-popover-btn-primary" onclick="window.takeScaleValueImmediately('${fieldId}')">
                <i class="fas fa-check"></i> Lấy ngay
            </button>
            <button class="scale-reader-popover-btn scale-reader-popover-btn-secondary" onclick="window.closeScalePopover()">
                Hủy
            </button>
        </div>
    `;
    
    document.body.appendChild(popover);
    currentActivePopover = popover;

    // Hàm định vị popover phía trên nút bấm
    function positionPopover() {
        if (!readBtn || !popover) return;
        const rect = readBtn.getBoundingClientRect();
        const popoverRect = popover.getBoundingClientRect();
        
        const top = rect.top + window.scrollY - popoverRect.height - 8;
        const left = rect.left + window.scrollX + (rect.width / 2) - (popoverRect.width / 2);
        
        popover.style.top = `${top}px`;
        popover.style.left = `${left}px`;
    }
    
    positionPopover();
    setTimeout(positionPopover, 50); // Chạy lại sau 50ms phòng trường hợp font/layout chưa tải xong

    // Lắng nghe dữ liệu liên tục từ cân
    currentActiveUnsubscribe = window.ScaleManager.onData(function(result) {
        const liveValEl = document.getElementById('scale-popover-live-val');
        const statusEl = document.getElementById('scale-popover-status');
        
        if (liveValEl) {
            liveValEl.textContent = `${result.value} ${result.unit}`;
            liveValEl.className = `scale-reader-popover-live ${result.stable ? 'stable' : 'unstable'}`;
        }
        
        if (statusEl) {
            statusEl.innerHTML = result.stable 
                ? '<span class="text-success"><i class="fas fa-check-circle"></i> Ổn định (Tự động điền...)</span>' 
                : '<span class="text-warning"><i class="fas fa-spinner fa-spin"></i> Đang dao động...</span>';
        }

        // Nếu cân báo ổn định -> tự động điền và đóng popover
        if (result.stable) {
            window.writeScaleValueToField(fieldId, result.value, result.unit);
            window.closeScalePopover();
            if (typeof toastr !== 'undefined') {
                toastr.success(`✅ Đã đọc: ${result.value} ${result.unit} (Ổn định)`, 'Cân điện tử', { timeOut: 3000 });
            }
        }
    });

    // Tự động đóng nếu không có phản hồi từ cân sau 30 giây
    currentActiveTimeout = setTimeout(() => {
        window.closeScalePopover();
        if (typeof toastr !== 'undefined') {
            toastr.warning('Đã ngưng chờ dữ liệu từ cân.', 'Cân điện tử');
        }
    }, 30000);
};

// ============================================================
// MỞ MODAL KẾT NỐI CÂN
// ============================================================
window.openScaleConnectionModal = function(fieldId) {
    window._scaleTargetFieldId = fieldId;
    window.closeScalePopover(); // Đóng popover đang mở nếu có

    // Cập nhật tiêu đề modal với tên biến số
    const field = fieldsConfig ? fieldsConfig[fieldId] : null;
    const fieldLabel = field ? (field.label || fieldId) : fieldId;
    const titleEl = document.getElementById('scale-modal-field-label');
    if (titleEl) titleEl.textContent = `→ "${fieldLabel}"`;

    // Cập nhật UI nút kết nối theo trạng thái hiện tại
    const isConnected = window.ScaleManager.isConnected();
    const connectBtn = document.getElementById('scale-connect-btn');
    const disconnectBtn = document.getElementById('scale-disconnect-btn');
    const statusDot = document.getElementById('scale-status-dot');
    const statusText = document.getElementById('scale-status-text');

    if (statusDot) statusDot.className = `scale-status-dot ${isConnected ? 'connected' : 'disconnected'}`;
    if (statusText) {
        statusText.textContent = isConnected ? 'Đã kết nối' : 'Chưa kết nối';
        statusText.className = `small fw-bold ms-2 ${isConnected ? 'text-success' : 'text-danger'}`;
    }
    if (connectBtn) connectBtn.classList.toggle('d-none', isConnected);
    if (disconnectBtn) disconnectBtn.classList.toggle('d-none', !isConnected);

    // Khôi phục lựa chọn hãng cân từ cài đặt phiên
    const savedBrand = window.scaleConfig ? window.scaleConfig.brand : 'and';
    const brandSelect = document.getElementById('scale-brand-select');
    if (brandSelect) {
        brandSelect.value = savedBrand;
        toggleCustomScaleFields(savedBrand);
    }

    // Hiện modal
    if (typeof bootstrap !== 'undefined') {
        const modal = new bootstrap.Modal(document.getElementById('scaleConnectionModal'));
        modal.show();
    } else if (typeof $ !== 'undefined') {
        $('#scaleConnectionModal').modal('show');
    }
};

/**
 * Hiện/ẩn form cài đặt tùy chỉnh khi chọn hãng "custom"
 */
window.toggleCustomScaleFields = function(brand) {
    const customFields = document.getElementById('scale-custom-fields');
    if (customFields) {
        customFields.classList.toggle('d-none', brand !== 'custom');
    }
};

/**
 * Thực hiện kết nối từ modal
 */
window.connectScaleFromModal = async function() {
    const brandSelect = document.getElementById('scale-brand-select');
    const brand = brandSelect ? brandSelect.value : 'and';

    let customConfig = null;
    if (brand === 'custom') {
        customConfig = {
            baudRate: parseInt(document.getElementById('scale-custom-baud').value) || 9600,
            dataBits: parseInt(document.getElementById('scale-custom-databits').value) || 8,
            parity: document.getElementById('scale-custom-parity').value || 'none',
            stopBits: parseInt(document.getElementById('scale-custom-stopbits').value) || 1
        };
    }

    const success = await window.ScaleManager.connect(brand, customConfig);
    if (success) {
        // Nếu có fieldId đang chờ → tự động đọc luôn sau 500ms
        if (window._scaleTargetFieldId) {
            setTimeout(() => {
                window.readScaleValueIntoField(window._scaleTargetFieldId);
            }, 500);
        }
    }
};

/**
 * Đọc giá trị ngay từ modal (lấy giá trị hiện tại ngay lập tức, đóng modal)
 */
window.readScaleFromModal = function() {
    if (window._scaleTargetFieldId) {
        const result = window.ScaleManager.getLastResult();
        if (result && result.value !== undefined) {
            window.writeScaleValueToField(window._scaleTargetFieldId, result.value, result.unit);
            
            // Thông báo thành công
            if (typeof toastr !== 'undefined') {
                toastr.success(`✅ Đã đọc: ${result.value} ${result.unit} (Lấy ngay từ modal)`, 'Cân điện tử', { timeOut: 3000 });
            }
            
            // Đóng modal
            const modalEl = document.getElementById('scaleConnectionModal');
            if (modalEl) {
                if (typeof bootstrap !== 'undefined') {
                    const inst = bootstrap.Modal.getInstance(modalEl);
                    if (inst) inst.hide();
                    else {
                        if (typeof $ !== 'undefined') $('#scaleConnectionModal').modal('hide');
                    }
                } else if (typeof $ !== 'undefined') {
                    $('#scaleConnectionModal').modal('hide');
                }
            }
        } else {
            if (typeof toastr !== 'undefined') {
                toastr.warning('Chưa nhận được số liệu nào từ cân để đọc ngay.', 'Cân điện tử');
            }
        }
    }
};
</script>
