<script>
/**
 * eBMR Scale Reader Module
 * Tích hợp đọc dữ liệu từ Cân Điện Tử (A&D, Mettler Toledo, Sartorius) qua RS-232/USB-Serial.
 * Sử dụng Web Serial API (Chrome/Edge 89+).
 * Chế độ: Streaming (lắng nghe liên tục, tự động cập nhật giá trị).
 */

// Tải danh sách thiết bị cân từ DB
window.SCALE_DEVICES = @json(\DB::table('instrument')->where('type', 'scale')->get());

// Khôi phục scaleConfig từ localStorage nếu có
try {
    const stored = localStorage.getItem('ebmr_scale_config');
    if (stored) {
        window.scaleConfig = JSON.parse(stored);
    }
} catch (e) {
    console.error('Failed to load scale config from localStorage', e);
}

// ============================================================
// CÀI ĐẶT HÃNG CÂN (SCALE PRESETS)
// ============================================================
window.SCALE_PRESETS = {
    'and': {
        label: 'A&D (AND)',
        icon: 'fa-balance-scale',
        baudRate: 2400,
        dataBits: 7,
        parity: 'even',
        stopBits: 1,
        description: 'Cân A&D/AND — Định dạng 17 ký tự ASCII (Mặc định 2400 bps)'
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
        let result = null;
        switch (brand) {
            case 'and':      result = this.parseAND(rawLine); break;
            case 'mettler':  result = this.parseMettlerToledo(rawLine); break;
            case 'sartorius': result = this.parseSartorius(rawLine); break;
        }
        if (result) return result;
        return this.parseAutoDetect(rawLine);
    }
};

// ============================================================
// SCALE MANAGER — Quản lý kết nối và streaming (RS-232 / WebSocket)
// ============================================================
window.ScaleManager = (function () {
    let _port = null;
    let _socket = null;            // WebSocket reference
    let _connType = 'serial';      // 'serial' | 'websocket'
    let _reader = null;
    let _readableStreamClosed = null;
    let _textDecoder = null;
    let _isReading = false;
    let _lineBuffer = '';
    let _callbacks = [];           // Danh sách callbacks đăng ký nhận dữ liệu
    let _lastParsedResult = null;
    let _currentBrand = 'auto';
    let _lastDataTime = 0;
    let _smartQueryTimer = null;

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
        const headerEl = document.getElementById('scale-modal-header');
        const iconContainer = document.getElementById('scale-modal-icon-container');
        const closeBtn = document.getElementById('scale-modal-close-btn');

        if (statusDot) {
            statusDot.className = `scale-status-dot ${connected ? 'connected' : 'disconnected'}`;
        }
        if (statusText) {
            statusText.textContent = connected ? 'Đã kết nối' : 'Chưa kết nối';
            statusText.className = `small fw-bold ms-1`;
        }
        if (connectBtn) connectBtn.classList.toggle('d-none', connected);
        if (disconnectBtn) disconnectBtn.classList.toggle('d-none', !connected);

        if (headerEl) {
            if (connected) {
                headerEl.style.background = 'linear-gradient(135deg, #15803d, #22c55e)';
                headerEl.style.color = '#ffffff';
            } else {
                headerEl.style.background = '#fffbeb';
                headerEl.style.color = '#78350f';
            }
        }
        if (iconContainer) {
            if (connected) {
                iconContainer.style.background = 'rgba(255, 255, 255, 0.2)';
            } else {
                iconContainer.style.background = 'rgba(120, 53, 15, 0.1)';
            }
        }
        if (closeBtn) {
            if (connected) {
                closeBtn.classList.add('btn-close-white');
            } else {
                closeBtn.classList.remove('btn-close-white');
            }
        }
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

    // Vòng lặp đọc streaming liên tục cho Web Serial
    async function _startReading() {
        _isReading = true;
        const decoder = new TextDecoder();
        _reader = _port.readable.getReader();
        _log('Bắt đầu lắng nghe dữ liệu từ cân...', 'info');

        try {
            while (_isReading) {
                const { value, done } = await _reader.read();
                if (done) break;
                if (value) {
                    const text = decoder.decode(value, { stream: true });
                    _lineBuffer += text;
                    // Xử lý từng dòng (kết thúc bằng \r, \n hoặc \r\n)
                    const lines = _lineBuffer.split(/\r\n|\r|\n/);
                    _lineBuffer = lines.pop(); // Giữ phần chưa hoàn chỉnh lại

                    for (const line of lines) {
                        const trimmed = line.trim();
                        if (!trimmed) continue;
                        _log(`← ${trimmed}`, 'data');

                        _lastDataTime = Date.now();

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

    async function _write(command) {
        if (_connType === 'serial') {
            if (!_port || !_port.writable) return;
            try {
                const encoder = new TextEncoder();
                const writer = _port.writable.getWriter();
                await writer.write(encoder.encode(command));
                writer.releaseLock();
                _log(`→ Gửi lệnh: ${JSON.stringify(command)}`, 'info');
            } catch (err) {
                console.error('Lỗi gửi lệnh đến cân:', err);
                _log(`❌ Lỗi gửi lệnh: ${err.message}`, 'error');
            }
        } else if (_connType === 'websocket') {
            if (!_socket || _socket.readyState !== WebSocket.OPEN) return;
            try {
                _socket.send(command);
                _log(`→ Gửi lệnh: ${JSON.stringify(command)}`, 'info');
            } catch (err) {
                console.error('Lỗi gửi lệnh đến cân qua WebSocket:', err);
                _log(`❌ Lỗi gửi lệnh: ${err.message}`, 'error');
            }
        }
    }

    function _startSmartQuery() {
        _lastDataTime = Date.now();
        _stopSmartQuery();
        _smartQueryTimer = setInterval(async () => {
            const connected = _connType === 'serial'
                ? (_port !== null && _isReading)
                : (_socket !== null && _socket.readyState === WebSocket.OPEN);
            
            if (!connected) {
                _stopSmartQuery();
                return;
            }
            
            const timeSinceLastData = Date.now() - _lastDataTime;
            if (timeSinceLastData > 1500) {
                // Đã hơn 1.5 giây chưa nhận được dữ liệu, gửi lệnh truy vấn tương ứng
                if (_currentBrand === 'and') {
                    await _write("Q\r\n");
                } else if (_currentBrand === 'mettler') {
                    await _write("SI\r\n");
                } else if (_currentBrand === 'sartorius') {
                    await _write(String.fromCharCode(27) + "P\r\n");
                }
                // Cập nhật lại thời gian để tránh gửi quá dồn dập
                _lastDataTime = Date.now();
            }
        }, 1000);
    }

    function _stopSmartQuery() {
        if (_smartQueryTimer) {
            clearInterval(_smartQueryTimer);
            _smartQueryTimer = null;
        }
    }

    return {
        isSupported: _isSupported,
        isConnected: () => {
            if (_connType === 'serial') {
                return _port !== null && _isReading;
            } else if (_connType === 'websocket') {
                return _socket !== null && _socket.readyState === WebSocket.OPEN;
            }
            return false;
        },
        updateStatus: _updateStatus,
        getLastResult: () => _lastParsedResult,
        getCurrentBrand: () => _currentBrand,

        /**
         * Kết nối đến cân
         * @param {string} brand - 'and' | 'mettler' | 'sartorius' | 'custom'
         * @param {object} config - Cài đặt tùy chỉnh (custom serial hoặc websocket IP/port)
         */
        async connect(brand, config = null) {
            const connType = config ? config.connectionType : 'serial';

            if (connType === 'websocket') {
                const ip = config.ip;
                const port = config.port;
                const bridgeIp = window.location.hostname;
                const wsUrl = (ip !== 'localhost' && ip !== '127.0.0.1' && ip !== bridgeIp)
                    ? `ws://${bridgeIp}:8090/?targetIp=${ip}&targetPort=${port || 4001}`
                    : `ws://${ip}:${port}`;

                if (_socket) {
                    _log('Đã kết nối WebSocket rồi! Vui lòng ngắt kết nối trước.', 'error');
                    return false;
                }
                if (_port) {
                    _log('Cổng Serial đang hoạt động! Vui lòng ngắt kết nối trước.', 'error');
                    return false;
                }

                return new Promise((resolve) => {
                    _log(`Đang kết nối tới WebSocket ${wsUrl}...`, 'info');
                    try {
                        _socket = new WebSocket(wsUrl);
                    } catch (e) {
                        _log(`❌ Lỗi khởi tạo WebSocket: ${e.message}`, 'error');
                        Swal.fire('Lỗi khởi tạo', `Không thể tạo kết nối WebSocket tới ${wsUrl}.<br>${e.message}`, 'error');
                        _socket = null;
                        resolve(false);
                        return;
                    }
                    
                    let resolved = false;

                    _socket.onopen = () => {
                        _connType = 'websocket';
                        _currentBrand = brand;
                        _updateStatus(true);
                        _updateFloatingPill(true);
                        _log(`✅ Đã kết nối WebSocket! IP: ${ip}:${port} | Hãng cân: ${brand}`, 'success');
                        
                        // Lưu vào cài đặt toàn cục và localStorage
                        window.scaleConfig = { brand, ...config };
                        try {
                            localStorage.setItem('ebmr_scale_config', JSON.stringify(window.scaleConfig));
                        } catch (err) {
                            console.error('Failed to save scale config', err);
                        }

                        resolved = true;
                        resolve(true);

                        // Kích hoạt các lệnh ban đầu và smart query
                        if (brand === 'mettler') {
                            _write("SIR\r\n");
                        }
                        _startSmartQuery();
                    };

                    _socket.onmessage = (event) => {
                        const text = event.data;
                        _lineBuffer += text;
                        const lines = _lineBuffer.split(/\r\n|\r|\n/);
                        _lineBuffer = lines.pop(); // Giữ lại phần chưa hoàn chỉnh

                        for (const line of lines) {
                            const trimmed = line.trim();
                            if (!trimmed) continue;
                            _log(`← ${trimmed}`, 'data');
                            _lastDataTime = Date.now();

                            const result = window.ScaleParsers.parse(trimmed, _currentBrand);
                            if (result) {
                                _lastParsedResult = result;
                                _callbacks.forEach(cb => {
                                    try { cb(result); } catch(e) { console.error('Scale callback error:', e); }
                                });

                                const liveVal = document.getElementById('scale-live-value');
                                if (liveVal) {
                                    liveVal.textContent = `${result.value} ${result.unit}`;
                                    liveVal.className = `scale-live-value ${result.stable ? 'stable' : 'unstable'}`;
                                }
                                _updateFloatingPill(true, result);
                            }
                        }
                    };

                    _socket.onerror = (err) => {
                        console.error('WebSocket scale error:', err);
                        _log(`❌ Lỗi kết nối WebSocket tới ${wsUrl}.`, 'error');
                        if (!resolved) {
                            _socket = null;
                            resolved = true;
                            Swal.fire('Lỗi kết nối', `Không thể kết nối WebSocket tới ${ip}:${port}`, 'error');
                            resolve(false);
                        }
                    };

                    _socket.onclose = (event) => {
                        _log(`🔌 WebSocket đóng kết nối (${event.code}).`, 'info');
                        _updateStatus(false);
                        _updateFloatingPill(false);
                        _socket = null;
                        _lineBuffer = '';
                        _lastParsedResult = null;
                        _stopSmartQuery();
                    };
                });
            } else {
                // Kết nối Serial (Web Serial API)
                if (!_isSupported()) {
                    Swal.fire('Không hỗ trợ', 'Trình duyệt của bạn không hỗ trợ Web Serial API.<br>Vui lòng dùng Chrome hoặc Edge phiên bản 89+.', 'error');
                    return false;
                }
                if (_port) {
                    _log('Đã kết nối serial rồi! Vui lòng ngắt kết nối trước.', 'error');
                    return false;
                }
                if (_socket) {
                    _log('Kết nối WebSocket đang hoạt động! Vui lòng ngắt kết nối trước.', 'error');
                    return false;
                }

                const preset = window.SCALE_PRESETS[brand] || window.SCALE_PRESETS['custom'];
                const serialConfig = brand === 'custom' && config ? config : preset;

                try {
                    // Mở popup chọn cổng COM — bắt buộc gọi từ gesture của người dùng
                    _port = await navigator.serial.requestPort();
                    await _port.open({
                        baudRate: Number(serialConfig.baudRate) || 9600,
                        dataBits: Number(serialConfig.dataBits) || 8,
                        parity: serialConfig.parity || 'none',
                        stopBits: Number(serialConfig.stopBits) || 1,
                        flowControl: 'none'
                    });

                    // Thiết lập các tín hiệu RTS/DTR cho kết nối RS-232/USB-to-Serial
                    try {
                        await _port.setSignals({ dataTerminalReady: true, requestToSend: true });
                        _log('✅ Khởi tạo tín hiệu DTR/RTS thành công', 'success');
                    } catch (signalErr) {
                        console.warn('Không thể cài đặt tín hiệu RTS/DTR:', signalErr);
                    }

                    _connType = 'serial';
                    _currentBrand = brand;
                    _isReading = true;
                    _updateStatus(true);
                    _updateFloatingPill(true); // Hiển thị status pill ở góc màn hình
                    _log(`✅ Đã kết nối Serial! Hãng cân: ${preset.label} | Baud: ${serialConfig.baudRate}`, 'success');

                    // Lưu vào cài đặt toàn cục và localStorage
                    window.scaleConfig = { brand, connectionType: 'serial', ...serialConfig };
                    try {
                        localStorage.setItem('ebmr_scale_config', JSON.stringify(window.scaleConfig));
                    } catch (err) {
                        console.error('Failed to save scale config', err);
                    }

                    // Bắt đầu đọc streaming
                    _startReading(); // Không await — chạy nền

                    // Kích hoạt các lệnh ban đầu và smart query
                    if (brand === 'mettler') {
                        await _write("SIR\r\n");
                    }
                    _startSmartQuery();

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
            }
        },

        /**
         * Ngắt kết nối
         */
        async disconnect() {
            _isReading = false;
            _stopSmartQuery();
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
            } catch (err) {
                _port = null;
                console.warn('Scale serial disconnect error:', err);
            }

            try {
                if (_socket) {
                    _socket.close();
                    _socket = null;
                }
            } catch (err) {
                _socket = null;
                console.warn('Scale websocket disconnect error:', err);
            }

            _updateStatus(false);
            _updateFloatingPill(false); // Ẩn status pill
            _log('🔌 Đã ngắt kết nối.', 'info');
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
    let originalId = fieldId;
    let loopSuffix = '';
    const loopMatch = fieldId.match(/(.+)(_loop_\d+)$/);
    if (loopMatch) {
        originalId = loopMatch[1];
        loopSuffix = loopMatch[2];
    }
    const field = fieldsConfig ? fieldsConfig[originalId] : null;
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
        syncLinkedCharts(field.block_id + loopSuffix);
    }
};

/**
 * Lắng nghe và điền giá trị từ cân vào biến số fieldId.
 * LUÔN LUÔN mở modal kết nối theo yêu cầu mới.
 * @param {string} fieldId
 */
window.readScaleValueIntoField = async function(fieldId) {
    let originalId = fieldId;
    let loopSuffix = '';
    const loopMatch = fieldId.match(/(.+)(_loop_\d+)$/);
    if (loopMatch) {
        originalId = loopMatch[1];
        loopSuffix = loopMatch[2];
    }
    const field = fieldsConfig ? fieldsConfig[originalId] : null;
    if (!field) return;

    // Luôn mở modal kết nối
    window.openScaleConnectionModal(fieldId);
};

// ============================================================
// MỞ MODAL KẾT NỐI CÂN
// ============================================================
window.openScaleConnectionModal = function(fieldId) {
    window._scaleTargetFieldId = fieldId;
    window.closeScalePopover(); // Đóng popover đang mở nếu có

    let originalId = fieldId;
    let loopSuffix = '';
    const loopMatch = fieldId.match(/(.+)(_loop_\d+)$/);
    if (loopMatch) {
        originalId = loopMatch[1];
        loopSuffix = loopMatch[2];
    }

    // Cập nhật tiêu đề modal với tên biến số
    const field = fieldsConfig ? fieldsConfig[originalId] : null;
    const fieldLabel = field ? (field.label || originalId) : originalId;
    const titleEl = document.getElementById('scale-modal-field-label');
    if (titleEl) titleEl.textContent = `→ "${fieldLabel}"`;

    // Cập nhật UI nút kết nối theo trạng thái hiện tại
    const isConnected = window.ScaleManager.isConnected();
    window.ScaleManager.updateStatus(isConnected);

    // Khôi phục lựa chọn thiết bị và cấu hình tương ứng
    const savedDeviceId = localStorage.getItem('ebmr_selected_scale_device_id');
    const deviceSelect = document.getElementById('scale-device-select');
    let loadedFromDevice = false;

    if (deviceSelect) {
        if (savedDeviceId && (window.SCALE_DEVICES || []).some(d => String(d.id) === String(savedDeviceId))) {
            deviceSelect.value = savedDeviceId;
            window.onScaleDeviceSelected(savedDeviceId);
            loadedFromDevice = true;
        } else {
            deviceSelect.value = '';
        }
    }

    if (!loadedFromDevice) {
        // Khôi phục lựa chọn hãng cân từ cài đặt phiên
        const savedBrand = window.scaleConfig ? window.scaleConfig.brand : 'and';
        const brandSelect = document.getElementById('scale-brand-select');
        if (brandSelect) {
            brandSelect.value = savedBrand;
        }

        // Khôi phục phương thức kết nối, IP và Port từ cài đặt phiên
        const savedType = window.scaleConfig ? (window.scaleConfig.connectionType || 'serial') : 'serial';
        const serialRadio = document.getElementById('scale-conn-type-serial');
        const wsRadio = document.getElementById('scale-conn-type-websocket');
        if (savedType === 'websocket') {
            if (wsRadio) wsRadio.checked = true;
            window.onChangeScaleConnectionType('websocket');
        } else {
            if (serialRadio) serialRadio.checked = true;
            window.onChangeScaleConnectionType('serial');
        }

        const savedIp = window.scaleConfig ? (window.scaleConfig.ip || '') : '';
        const savedPort = window.scaleConfig ? (window.scaleConfig.port || '') : '';
        const ipInput = document.getElementById('scale-websocket-ip');
        const portInput = document.getElementById('scale-websocket-port');
        if (ipInput) ipInput.value = savedIp;
        if (portInput) portInput.value = savedPort;

        // Khôi phục serial tùy chỉnh nếu có
        if (savedType === 'serial' && savedBrand === 'custom' && window.scaleConfig) {
            const baudSelect = document.getElementById('scale-custom-baud');
            const databitsSelect = document.getElementById('scale-custom-databits');
            const paritySelect = document.getElementById('scale-custom-parity');
            const stopbitsSelect = document.getElementById('scale-custom-stopbits');
            if (baudSelect && window.scaleConfig.baudRate) baudSelect.value = window.scaleConfig.baudRate;
            if (databitsSelect && window.scaleConfig.dataBits) databitsSelect.value = window.scaleConfig.dataBits;
            if (paritySelect && window.scaleConfig.parity) paritySelect.value = window.scaleConfig.parity;
            if (stopbitsSelect && window.scaleConfig.stopBits) stopbitsSelect.value = window.scaleConfig.stopBits;
        }

        // Cập nhật hiển thị cài đặt tùy chỉnh
        toggleCustomScaleFields(savedBrand);
    }

    // Hiển thị ngay giá trị cuối cùng từ cân nếu có
    const liveVal = document.getElementById('scale-live-value');
    if (liveVal) {
        const lastResult = window.ScaleManager.getLastResult();
        if (lastResult) {
            liveVal.textContent = `${lastResult.value} ${lastResult.unit}`;
            liveVal.className = `scale-live-value ${lastResult.stable ? 'stable' : 'unstable'}`;
        } else {
            liveVal.textContent = '—.— g';
            liveVal.className = 'scale-live-value stable';
        }
    }

    // Hủy đăng ký listener cũ nếu có để tránh trùng lặp
    if (window._scaleModalUnsubscribe) {
        window._scaleModalUnsubscribe();
        window._scaleModalUnsubscribe = null;
    }

    // Đăng ký lắng nghe dữ liệu để tự động điền khi cân báo ổn định
    if (fieldId) {
        window._scaleModalUnsubscribe = window.ScaleManager.onData(function(result) {
            if (result.stable) {
                window.writeScaleValueToField(fieldId, result.value, result.unit);
                
                // Đóng modal
                const modalEl = document.getElementById('scaleConnectionModal');
                if (modalEl) {
                    if (typeof bootstrap !== 'undefined' && typeof bootstrap.Modal !== 'undefined' && typeof bootstrap.Modal.getInstance === 'function') {
                        const inst = bootstrap.Modal.getInstance(modalEl) || (bootstrap.Modal.getOrCreateInstance ? bootstrap.Modal.getOrCreateInstance(modalEl) : null);
                        if (inst) inst.hide();
                        else if (typeof $ !== 'undefined') {
                            $(modalEl).modal('hide');
                        }
                    } else if (typeof $ !== 'undefined') {
                        $(modalEl).modal('hide');
                    }
                }
                
                if (typeof toastr !== 'undefined') {
                     toastr.success(`✅ Đã đọc: ${result.value} ${result.unit} (Ổn định)`, 'Cân điện tử', { timeOut: 3000 });
                }
            }
        });

        // Lắng nghe sự kiện ẩn modal để hủy đăng ký listener
        const modalEl = document.getElementById('scaleConnectionModal');
        if (modalEl && !modalEl._hasHideListener) {
            modalEl._hasHideListener = true;
            const cleanup = () => {
                if (window._scaleModalUnsubscribe) {
                    window._scaleModalUnsubscribe();
                    window._scaleModalUnsubscribe = null;
                }
            };
            if (typeof $ !== 'undefined') {
                $(modalEl).on('hidden.bs.modal', cleanup);
            } else if (typeof bootstrap !== 'undefined' && typeof bootstrap.Modal !== 'undefined') {
                modalEl.addEventListener('hidden.bs.modal', cleanup);
            }
        }
    }

    // Hiện modal nếu chưa hiển thị
    const modalEl = document.getElementById('scaleConnectionModal');
    let isModalVisible = false;
    if (modalEl) {
        isModalVisible = modalEl.classList.contains('show');
    }
    if (!isModalVisible) {
        if (typeof bootstrap !== 'undefined' && typeof bootstrap.Modal !== 'undefined') {
            const modal = bootstrap.Modal.getOrCreateInstance ? bootstrap.Modal.getOrCreateInstance(modalEl) : new bootstrap.Modal(modalEl);
            modal.show();
        } else if (typeof $ !== 'undefined') {
            $('#scaleConnectionModal').modal('show');
        }
    }
};

/**
 * Xử lý thay đổi phương thức kết nối
 */
window.onChangeScaleConnectionType = function(type) {
    const webSocketFields = document.getElementById('scale-websocket-fields');
    const customFields = document.getElementById('scale-custom-fields');
    const serialInfo = document.getElementById('scale-serial-info');
    const webSocketInfo = document.getElementById('scale-websocket-info');
    const brandSelect = document.getElementById('scale-brand-select');
    const brand = brandSelect ? brandSelect.value : 'and';

    if (type === 'websocket') {
        if (webSocketFields) webSocketFields.classList.remove('d-none');
        if (customFields) customFields.classList.add('d-none');
        if (serialInfo) serialInfo.classList.add('d-none');
        if (webSocketInfo) webSocketInfo.classList.remove('d-none');
    } else {
        if (webSocketFields) webSocketFields.classList.add('d-none');
        if (customFields) {
            customFields.classList.toggle('d-none', brand !== 'custom');
        }
        if (serialInfo) serialInfo.classList.remove('d-none');
        if (webSocketInfo) webSocketInfo.classList.add('d-none');
    }
};

/**
 * Hiện/ẩn form cài đặt tùy chỉnh khi chọn hãng "custom"
 */
window.toggleCustomScaleFields = function(brand) {
    const customFields = document.getElementById('scale-custom-fields');
    if (customFields) {
        const connTypeInput = document.querySelector('input[name="scale-connection-type"]:checked');
        const connType = connTypeInput ? connTypeInput.value : 'serial';
        
        if (connType === 'serial') {
            customFields.classList.toggle('d-none', brand !== 'custom');
        } else {
            customFields.classList.add('d-none');
        }
    }
};

/**
 * Thực hiện kết nối từ modal
 */
window.connectScaleFromModal = async function() {
    const brandSelect = document.getElementById('scale-brand-select');
    const brand = brandSelect ? brandSelect.value : 'and';

    const connTypeInput = document.querySelector('input[name="scale-connection-type"]:checked');
    const connectionType = connTypeInput ? connTypeInput.value : 'serial';

    let config = { connectionType };

    if (connectionType === 'serial') {
        if (brand === 'custom') {
            config.baudRate = parseInt(document.getElementById('scale-custom-baud').value) || 9600;
            config.dataBits = parseInt(document.getElementById('scale-custom-databits').value) || 8;
            config.parity = document.getElementById('scale-custom-parity').value || 'none';
            config.stopBits = parseInt(document.getElementById('scale-custom-stopbits').value) || 1;
        }
    } else {
        const ipInput = document.getElementById('scale-websocket-ip');
        const portInput = document.getElementById('scale-websocket-port');
        const ip = ipInput ? ipInput.value.trim() : '';
        const port = portInput ? portInput.value.trim() : '';

        if (!ip) {
            Swal.fire('Lỗi nhập liệu', 'Vui lòng điền địa chỉ IP của thiết bị.', 'warning');
            return;
        }
        if (!port) {
            Swal.fire('Lỗi nhập liệu', 'Vui lòng điền cổng (Port) kết nối.', 'warning');
            return;
        }

        config.ip = ip;
        config.port = port;
    }

    const success = await window.ScaleManager.connect(brand, config);
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
                if (typeof bootstrap !== 'undefined' && typeof bootstrap.Modal !== 'undefined' && typeof bootstrap.Modal.getInstance === 'function') {
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

/**
 * Ẩn/hiện chi tiết cấu hình phần cứng
 */
window.toggleScaleDetails = function() {
    const isAdmin = @json(session('user') && session('user')['userGroup'] == 'Admin');
    if (!isAdmin) return;
    const container = document.getElementById('scale-hardware-details-container');
    const icon = document.getElementById('scale-details-icon');
    if (container) {
        if (container.style.display === 'none') {
            container.style.display = 'block';
            if (icon) {
                icon.classList.remove('fa-chevron-down');
                icon.classList.add('fa-chevron-up');
            }
        } else {
            container.style.display = 'none';
            if (icon) {
                icon.classList.remove('fa-chevron-up');
                icon.classList.add('fa-chevron-down');
            }
        }
    }
};

/**
 * Xử lý khi chọn thiết bị từ dropdown
 */
window.onScaleDeviceSelected = function(deviceId) {
    const inputsToToggle = [
        document.getElementById('scale-conn-type-serial'),
        document.getElementById('scale-conn-type-websocket'),
        document.getElementById('scale-brand-select'),
        document.getElementById('scale-websocket-ip'),
        document.getElementById('scale-websocket-port'),
        document.getElementById('scale-custom-baud'),
        document.getElementById('scale-custom-databits'),
        document.getElementById('scale-custom-parity'),
        document.getElementById('scale-custom-stopbits')
    ];

    if (!deviceId) {
        localStorage.removeItem('ebmr_selected_scale_device_id');
        // Cho phép chỉnh sửa khi tự nhập cấu hình
        inputsToToggle.forEach(input => {
            if (input) input.disabled = false;
        });
        return;
    }

    const device = (window.SCALE_DEVICES || []).find(d => String(d.id) === String(deviceId));
    if (!device) return;

    localStorage.setItem('ebmr_selected_scale_device_id', deviceId);

    // Điền cấu hình vào modal
    const connType = device.connection_type || 'serial';
    const serialRadio = document.getElementById('scale-conn-type-serial');
    const wsRadio = document.getElementById('scale-conn-type-websocket');
    if (connType === 'websocket') {
        if (wsRadio) wsRadio.checked = true;
    } else {
        if (serialRadio) serialRadio.checked = true;
    }
    window.onChangeScaleConnectionType(connType);

    const brand = device.brand || 'and';
    const brandSelect = document.getElementById('scale-brand-select');
    if (brandSelect) {
        brandSelect.value = brand;
    }
    window.toggleCustomScaleFields(brand);

    const ipInput = document.getElementById('scale-websocket-ip');
    const portInput = document.getElementById('scale-websocket-port');
    if (ipInput) ipInput.value = device.ip || '';
    if (portInput) portInput.value = device.port || '';

    const baudSelect = document.getElementById('scale-custom-baud');
    const databitsSelect = document.getElementById('scale-custom-databits');
    const paritySelect = document.getElementById('scale-custom-parity');
    const stopbitsSelect = document.getElementById('scale-custom-stopbits');

    if (baudSelect && device.baud_rate) baudSelect.value = device.baud_rate;
    if (databitsSelect && device.data_bits) databitsSelect.value = device.data_bits;
    if (paritySelect && device.parity) paritySelect.value = device.parity;
    if (stopbitsSelect && device.stop_bits) stopbitsSelect.value = device.stop_bits;

    // Vô hiệu hóa chỉnh sửa khi chọn thiết bị có sẵn từ dữ liệu gốc
    inputsToToggle.forEach(input => {
        if (input) input.disabled = true;
    });
};
</script>
