/**
 * eBMR Designer V2 — Cân Điện Tử (RS-232 / Web Serial / WebSocket)
 * =================================================================
 * Port từ resources/views/pages/ebmr/designer/scripts/scale_reader.blade.php.
 * Giữ nguyên toàn bộ logic đọc/parse cân (Web Serial + WebSocket bridge) —
 * chỉ thay các điểm nối với dữ liệu biến số bằng API của V2:
 *   - fieldsConfig / executionValues -> BOOT.fieldsConfig / BOOT.executionValues
 *   - ghi giá trị + vẽ lại field phụ thuộc -> BOOT.repaintAllFields()
 * Modal HTML (#scaleConnectionModal) được khai báo trong designer_v2.blade.php,
 * dùng lại NGUYÊN VẸN các id DOM để module này gắn sự kiện.
 */

// ============================================================
// CÀI ĐẶT HÃNG CÂN (SCALE PRESETS)
// ============================================================
const SCALE_PRESETS = {
    and: { label: 'A&D (AND)', baudRate: 2400, dataBits: 7, parity: 'even', stopBits: 1 },
    mettler: { label: 'Mettler Toledo', baudRate: 9600, dataBits: 8, parity: 'none', stopBits: 1 },
    sartorius: { label: 'Sartorius', baudRate: 9600, dataBits: 8, parity: 'none', stopBits: 1 },
    custom: { label: 'Tùy chỉnh', baudRate: 9600, dataBits: 8, parity: 'none', stopBits: 1 },
};

// ============================================================
// PARSERS CHO TỪNG HÃNG CÂN
// ============================================================
const ScaleParsers = {
    parseAND(rawLine) {
        if (!rawLine || rawLine.length < 10) return null;
        const line = rawLine.trim();
        if (!/^(ST|US|OL|Err)/i.test(line)) return null;
        const stable = /^ST/i.test(line);
        const numMatch = line.match(/([+-]?\s*[\d]+\.?[\d]*)\s*([a-zA-Z]+)?/);
        if (!numMatch) return null;
        const value = parseFloat(numMatch[1].replace(/\s/g, ''));
        const unit = numMatch[2] || 'g';
        if (isNaN(value)) return null;
        return { value, unit, stable, brand: 'and' };
    },
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
    parseSartorius(rawLine) {
        if (!rawLine || rawLine.length < 5) return null;
        const line = rawLine.trim();
        if (!/[+-]?\s*[\d]/.test(line)) return null;
        const stable = !/^[Uu]/.test(line);
        const numMatch = line.match(/([+-]?\s*[\d]+\.?[\d]*)\s*([a-zA-Z]+)?/);
        if (!numMatch) return null;
        const value = parseFloat(numMatch[1].replace(/\s/g, ''));
        const unit = numMatch[2] || 'g';
        if (isNaN(value)) return null;
        return { value, unit, stable, brand: 'sartorius' };
    },
    parseAutoDetect(rawLine) {
        return this.parseAND(rawLine) || this.parseMettlerToledo(rawLine) || this.parseSartorius(rawLine) || null;
    },
    parse(rawLine, brand) {
        let result = null;
        switch (brand) {
            case 'and': result = this.parseAND(rawLine); break;
            case 'mettler': result = this.parseMettlerToledo(rawLine); break;
            case 'sartorius': result = this.parseSartorius(rawLine); break;
        }
        if (result) return result;
        return this.parseAutoDetect(rawLine);
    },
};

// ============================================================
// SCALE MANAGER — Quản lý kết nối và streaming (RS-232 / WebSocket)
// ============================================================
const ScaleManager = (function () {
    let _port = null;
    let _socket = null;
    let _connType = 'serial';
    let _reader = null;
    let _isReading = false;
    let _lineBuffer = '';
    let _callbacks = [];
    let _lastParsedResult = null;
    let _currentBrand = 'auto';
    let _lastDataTime = 0;
    let _smartQueryTimer = null;

    function _isSupported() { return 'serial' in navigator; }

    function _log(msg, type = 'info') {
        const logEl = document.getElementById('scale-raw-log');
        if (!logEl) return;
        const colors = { info: '#6c757d', success: '#198754', error: '#dc3545', data: '#0d6efd' };
        const now = new Date().toLocaleTimeString();
        const entry = document.createElement('div');
        entry.style.cssText = `color: ${colors[type] || colors.info}; font-size: 0.75rem; font-family: monospace; border-bottom: 1px solid #f0f0f0; padding: 2px 0;`;
        entry.textContent = `[${now}] ${msg}`;
        logEl.insertBefore(entry, logEl.firstChild);
        while (logEl.children.length > 50) logEl.removeChild(logEl.lastChild);
    }

    function _updateStatus(connected) {
        const statusDot = document.getElementById('scale-status-dot');
        const statusText = document.getElementById('scale-status-text');
        const connectBtn = document.getElementById('scale-connect-btn');
        const disconnectBtn = document.getElementById('scale-disconnect-btn');
        const headerEl = document.getElementById('scale-modal-header');
        const iconContainer = document.getElementById('scale-modal-icon-container');
        const closeBtn = document.getElementById('scale-modal-close-btn');

        if (statusDot) statusDot.className = `scale-status-dot ${connected ? 'connected' : 'disconnected'}`;
        if (statusText) statusText.textContent = connected ? 'Đã kết nối' : 'Chưa kết nối';
        if (connectBtn) connectBtn.classList.toggle('d-none', connected);
        if (disconnectBtn) disconnectBtn.classList.toggle('d-none', !connected);
        if (headerEl) {
            if (connected) { headerEl.style.background = 'linear-gradient(135deg, #15803d, #22c55e)'; headerEl.style.color = '#ffffff'; }
            else { headerEl.style.background = '#fffbeb'; headerEl.style.color = '#78350f'; }
        }
        if (iconContainer) iconContainer.style.background = connected ? 'rgba(255, 255, 255, 0.2)' : 'rgba(120, 53, 15, 0.1)';
        if (closeBtn) closeBtn.classList.toggle('btn-close-white', connected);
    }

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
            pill.onclick = function () { window.__V2__.openScaleConnectionModal(window.__V2__._scaleTargetFieldId || ''); };
            pill.innerHTML = `<i class="fas fa-balance-scale text-success"></i><span id="scale-floating-val">—.— g</span><span class="scale-status-dot connected"></span>`;
            document.body.appendChild(pill);
        }
        pill.classList.remove('d-none');
        const valEl = document.getElementById('scale-floating-val');
        const dotEl = pill.querySelector('.scale-status-dot');
        if (result) {
            if (valEl) valEl.textContent = `${result.value} ${result.unit}`;
            if (dotEl) dotEl.className = `scale-status-dot ${result.stable ? 'connected' : 'unstable-dot'}`;
        } else {
            if (valEl) valEl.textContent = 'Đang kết nối...';
            if (dotEl) dotEl.className = 'scale-status-dot connected';
        }
    }

    function _handleParsedLine(trimmed) {
        _log(`← ${trimmed}`, 'data');
        _lastDataTime = Date.now();
        const result = ScaleParsers.parse(trimmed, _currentBrand);
        if (!result) return;
        _lastParsedResult = result;
        _callbacks.forEach((cb) => { try { cb(result); } catch (e) { console.error('Scale callback error:', e); } });

        const liveVal = document.getElementById('scale-live-value');
        const liveStatus = document.getElementById('scale-live-status-text');
        if (liveVal) {
            liveVal.textContent = `${result.value} ${result.unit}`;
            liveVal.className = `scale-live-value ${result.stable ? 'stable' : 'unstable'}`;
        }
        if (liveStatus) {
            liveStatus.innerHTML = result.stable
                ? '<i class="fas fa-check-circle me-1 text-success"></i> <span class="text-success">Trạng thái: Ổn định</span>'
                : '<i class="fas fa-exclamation-triangle me-1 text-warning"></i> <span class="text-warning">Trạng thái: Đang rung lắc</span>';
        }
        _updateFloatingPill(true, result);
    }

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
                    _lineBuffer += decoder.decode(value, { stream: true });
                    const lines = _lineBuffer.split(/\r\n|\r|\n/);
                    _lineBuffer = lines.pop();
                    for (const line of lines) {
                        const trimmed = line.trim();
                        if (trimmed) _handleParsedLine(trimmed);
                    }
                }
            }
        } catch (err) {
            if (_isReading) { _log(`Lỗi đọc dữ liệu: ${err.message}`, 'error'); console.error('Scale read error:', err); }
        } finally {
            _reader.releaseLock();
        }
    }

    async function _write(command) {
        if (_connType === 'serial') {
            if (!_port || !_port.writable) return;
            try {
                const writer = _port.writable.getWriter();
                await writer.write(new TextEncoder().encode(command));
                writer.releaseLock();
                _log(`→ Gửi lệnh: ${JSON.stringify(command)}`, 'info');
            } catch (err) { _log(`❌ Lỗi gửi lệnh: ${err.message}`, 'error'); }
        } else if (_connType === 'websocket') {
            if (!_socket || _socket.readyState !== WebSocket.OPEN) return;
            try { _socket.send(command); _log(`→ Gửi lệnh: ${JSON.stringify(command)}`, 'info'); }
            catch (err) { _log(`❌ Lỗi gửi lệnh: ${err.message}`, 'error'); }
        }
    }

    function _startSmartQuery() {
        _lastDataTime = Date.now();
        _stopSmartQuery();
        _smartQueryTimer = setInterval(async () => {
            const connected = _connType === 'serial' ? (_port !== null && _isReading) : (_socket !== null && _socket.readyState === WebSocket.OPEN);
            if (!connected) { _stopSmartQuery(); return; }
            if (Date.now() - _lastDataTime > 1500) {
                if (_currentBrand === 'and') await _write('Q\r\n');
                else if (_currentBrand === 'mettler') await _write('SI\r\n');
                else if (_currentBrand === 'sartorius') await _write(String.fromCharCode(27) + 'P\r\n');
                _lastDataTime = Date.now();
            }
        }, 1000);
    }

    function _stopSmartQuery() {
        if (_smartQueryTimer) { clearInterval(_smartQueryTimer); _smartQueryTimer = null; }
    }

    return {
        isSupported: _isSupported,
        isConnected: () => (_connType === 'serial' ? (_port !== null && _isReading) : (_socket !== null && _socket.readyState === WebSocket.OPEN)),
        updateStatus: _updateStatus,
        getLastResult: () => _lastParsedResult,

        async connect(brand, config = null) {
            const connType = config ? config.connectionType : 'serial';

            if (connType === 'websocket') {
                const ip = config.ip;
                const port = config.port;
                const bridgeIp = window.location.hostname;
                const wsUrl = (ip !== 'localhost' && ip !== '127.0.0.1' && ip !== bridgeIp)
                    ? `ws://${bridgeIp}:8090/?targetIp=${ip}&targetPort=${port || 4001}`
                    : `ws://${ip}:${port}`;

                if (_socket || _port) { _log('Đã có kết nối đang hoạt động! Vui lòng ngắt kết nối trước.', 'error'); return false; }

                return new Promise((resolve) => {
                    _log(`Đang kết nối tới WebSocket ${wsUrl}...`, 'info');
                    try { _socket = new WebSocket(wsUrl); }
                    catch (e) {
                        _log(`❌ Lỗi khởi tạo WebSocket: ${e.message}`, 'error');
                        window.Swal?.fire('Lỗi khởi tạo', `Không thể tạo kết nối WebSocket tới ${wsUrl}.<br>${e.message}`, 'error');
                        _socket = null; resolve(false); return;
                    }
                    let resolved = false;
                    _socket.onopen = () => {
                        _connType = 'websocket'; _currentBrand = brand;
                        _updateStatus(true); _updateFloatingPill(true);
                        _log(`✅ Đã kết nối WebSocket! IP: ${ip}:${port} | Hãng cân: ${brand}`, 'success');
                        window.scaleConfig = { brand, ...config };
                        try { localStorage.setItem('ebmr_scale_config', JSON.stringify(window.scaleConfig)); } catch (err) { /* noop */ }
                        resolved = true; resolve(true);
                        if (brand === 'mettler') _write('SIR\r\n');
                        _startSmartQuery();
                    };
                    _socket.onmessage = (event) => {
                        _lineBuffer += event.data;
                        const lines = _lineBuffer.split(/\r\n|\r|\n/);
                        _lineBuffer = lines.pop();
                        for (const line of lines) {
                            const trimmed = line.trim();
                            if (trimmed) _handleParsedLine(trimmed);
                        }
                    };
                    _socket.onerror = (err) => {
                        console.error('WebSocket scale error:', err);
                        _log(`❌ Lỗi kết nối WebSocket tới ${wsUrl}.`, 'error');
                        if (!resolved) {
                            _socket = null; resolved = true;
                            window.Swal?.fire('Lỗi kết nối', `Không thể kết nối WebSocket tới ${ip}:${port}`, 'error');
                            resolve(false);
                        }
                    };
                    _socket.onclose = (event) => {
                        _log(`🔌 WebSocket đóng kết nối (${event.code}).`, 'info');
                        _updateStatus(false); _updateFloatingPill(false);
                        _socket = null; _lineBuffer = ''; _lastParsedResult = null;
                        _stopSmartQuery();
                    };
                });
            }

            // Serial (Web Serial API)
            if (!_isSupported()) {
                window.Swal?.fire('Không hỗ trợ', 'Trình duyệt của bạn không hỗ trợ Web Serial API.<br>Vui lòng dùng Chrome hoặc Edge phiên bản 89+.', 'error');
                return false;
            }
            if (_port || _socket) { _log('Đã có kết nối đang hoạt động! Vui lòng ngắt kết nối trước.', 'error'); return false; }

            const preset = SCALE_PRESETS[brand] || SCALE_PRESETS.custom;
            const serialConfig = brand === 'custom' && config ? config : preset;

            try {
                _port = await navigator.serial.requestPort();
                await _port.open({
                    baudRate: Number(serialConfig.baudRate) || 9600,
                    dataBits: Number(serialConfig.dataBits) || 8,
                    parity: serialConfig.parity || 'none',
                    stopBits: Number(serialConfig.stopBits) || 1,
                    flowControl: 'none',
                });
                try {
                    await _port.setSignals({ dataTerminalReady: true, requestToSend: true });
                    _log('✅ Khởi tạo tín hiệu DTR/RTS thành công', 'success');
                } catch (signalErr) { console.warn('Không thể cài đặt tín hiệu RTS/DTR:', signalErr); }

                _connType = 'serial'; _currentBrand = brand; _isReading = true;
                _updateStatus(true); _updateFloatingPill(true);
                _log(`✅ Đã kết nối Serial! Hãng cân: ${preset.label} | Baud: ${serialConfig.baudRate}`, 'success');

                window.scaleConfig = { brand, connectionType: 'serial', ...serialConfig };
                try { localStorage.setItem('ebmr_scale_config', JSON.stringify(window.scaleConfig)); } catch (err) { /* noop */ }

                _startReading();
                if (brand === 'mettler') await _write('SIR\r\n');
                _startSmartQuery();
                return true;
            } catch (err) {
                _port = null; _isReading = false; _updateFloatingPill(false);
                if (err.name === 'NotFoundError') _log('Người dùng đã hủy chọn cổng COM.', 'info');
                else {
                    _log(`❌ Không thể kết nối: ${err.message}`, 'error');
                    window.Swal?.fire('Lỗi kết nối', `Không thể mở cổng serial.<br><small>${err.message}</small>`, 'error');
                }
                return false;
            }
        },

        async disconnect() {
            _isReading = false; _stopSmartQuery(); _callbacks = []; _lineBuffer = ''; _lastParsedResult = null;
            try { if (_reader) { await _reader.cancel(); _reader = null; } if (_port) { await _port.close(); _port = null; } }
            catch (err) { _port = null; console.warn('Scale serial disconnect error:', err); }
            try { if (_socket) { _socket.close(); _socket = null; } }
            catch (err) { _socket = null; console.warn('Scale websocket disconnect error:', err); }
            _updateStatus(false); _updateFloatingPill(false);
            _log('🔌 Đã ngắt kết nối.', 'info');
        },

        onData(cb) {
            _callbacks.push(cb);
            return function unsubscribe() { _callbacks = _callbacks.filter((c) => c !== cb); };
        },
    };
})();

/**
 * Khởi tạo module Cân điện tử cho V2: gắn ScaleManager + các hàm điều khiển
 * modal kết nối vào window.__V2__, giống hệt hành vi trình soạn thảo V1.
 */
export function initScaleReaderV2(BOOT) {
    try {
        const stored = localStorage.getItem('ebmr_scale_config');
        if (stored) window.scaleConfig = JSON.parse(stored);
    } catch (e) { console.error('Failed to load scale config from localStorage', e); }

    BOOT.ScaleManager = ScaleManager;
    BOOT.ScaleParsers = ScaleParsers;

    /** Ghi giá trị đọc được từ cân vào biến số — KHÔNG qua cổng "lý do thay đổi" (giống V1: đọc cân là thao tác thiết bị tự động). */
    BOOT.writeScaleValueToField = function (fieldId, value, unit) {
        const field = BOOT.fieldsConfig[fieldId];
        if (!field) return;

        let finalValue = value;
        const dPlaces = field.validation && field.validation.decimal_places !== null && field.validation.decimal_places !== undefined
            ? parseInt(field.validation.decimal_places) : null;
        if (dPlaces !== null && !isNaN(dPlaces)) finalValue = parseFloat(Number(value).toFixed(dPlaces));

        if (typeof BOOT.executionValues[fieldId] !== 'object' || BOOT.executionValues[fieldId] === null) {
            BOOT.executionValues[fieldId] = {};
        }
        const rec = BOOT.executionValues[fieldId];
        rec.default = String(finalValue);
        if (!rec._meta) rec._meta = {};
        if (!rec._meta.default) rec._meta.default = {};

        const deviceSelect = document.getElementById('scale-device-select');
        let deviceName = 'Cân điện tử';
        let deviceCode = '---';
        if (deviceSelect && deviceSelect.options.length > 0 && deviceSelect.selectedIndex > -1) {
            const optText = deviceSelect.options[deviceSelect.selectedIndex].text;
            if (optText.includes('-- Tự nhập')) {
                deviceName = 'Cân tùy chỉnh';
                deviceCode = 'Custom';
            } else {
                const match = optText.match(/^(.*?)\s*\((.*?)\)$/);
                if (match) { deviceName = match[1].trim(); deviceCode = match[2].trim(); }
                else { deviceName = optText.trim(); }
            }
        }

        rec._meta.default.device = deviceName;
        rec._meta.default.deviceCode = deviceCode;
        rec._meta.default.unit = unit || '';
        rec._meta.default.at = new Date().toLocaleString('vi-VN');
        rec._meta.default.by = BOOT.currentUserName || 'Hệ thống';

        BOOT.repaintAllFields();
    };

    /** LUÔN mở modal kết nối cân theo yêu cầu — không tự động điền trực tiếp. */
    BOOT.readScaleValueIntoField = function (fieldId) {
        if (!BOOT.fieldsConfig[fieldId]) return;
        BOOT.openScaleConnectionModal(fieldId);
    };

    BOOT.openScaleConnectionModal = function (fieldId) {
        BOOT._scaleTargetFieldId = fieldId;

        const field = BOOT.fieldsConfig[fieldId];
        const fieldLabel = field ? (field.label || fieldId) : fieldId;
        const titleEl = document.getElementById('scale-modal-field-label');
        if (titleEl) titleEl.textContent = `→ "${fieldLabel}"`;

        const isConnected = ScaleManager.isConnected();
        ScaleManager.updateStatus(isConnected);

        const savedDeviceId = localStorage.getItem('ebmr_selected_scale_device_id');
        const deviceSelect = document.getElementById('scale-device-select');
        let loadedFromDevice = false;

        if (deviceSelect) {
            if (savedDeviceId && (window.SCALE_DEVICES || []).some((d) => String(d.id) === String(savedDeviceId))) {
                deviceSelect.value = savedDeviceId;
                BOOT.onScaleDeviceSelected(savedDeviceId);
                loadedFromDevice = true;
            } else {
                deviceSelect.value = '';
            }
        }

        if (!loadedFromDevice) {
            const savedBrand = window.scaleConfig ? window.scaleConfig.brand : 'and';
            const brandSelect = document.getElementById('scale-brand-select');
            if (brandSelect) brandSelect.value = savedBrand;

            const savedType = window.scaleConfig ? (window.scaleConfig.connectionType || 'serial') : 'serial';
            const serialRadio = document.getElementById('scale-conn-type-serial');
            const wsRadio = document.getElementById('scale-conn-type-websocket');
            if (savedType === 'websocket') { if (wsRadio) wsRadio.checked = true; BOOT.onChangeScaleConnectionType('websocket'); }
            else { if (serialRadio) serialRadio.checked = true; BOOT.onChangeScaleConnectionType('serial'); }

            const ipInput = document.getElementById('scale-websocket-ip');
            const portInput = document.getElementById('scale-websocket-port');
            if (ipInput) ipInput.value = window.scaleConfig ? (window.scaleConfig.ip || '') : '';
            if (portInput) portInput.value = window.scaleConfig ? (window.scaleConfig.port || '') : '';

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
            BOOT.toggleCustomScaleFields(savedBrand);
        }

        const liveVal = document.getElementById('scale-live-value');
        const liveStatus = document.getElementById('scale-live-status-text');
        const btnRead = document.getElementById('scale-read-now-btn');
        const lastResult = ScaleManager.getLastResult();
        if (liveVal) {
            if (lastResult) {
                liveVal.textContent = `${lastResult.value} ${lastResult.unit}`;
                liveVal.className = `scale-live-value ${lastResult.stable ? 'stable' : 'unstable'}`;
                if (btnRead) btnRead.disabled = !lastResult.stable;
                if (liveStatus) {
                    liveStatus.innerHTML = lastResult.stable
                        ? '<i class="fas fa-check-circle me-1 text-success"></i> <span class="text-success">Trạng thái: Ổn định - Nhấn "Đọc giá trị" để lưu</span>'
                        : '<i class="fas fa-exclamation-triangle me-1 text-warning"></i> <span class="text-warning">Trạng thái: Đang rung lắc</span>';
                }
            } else {
                liveVal.textContent = '—.— g';
                liveVal.className = 'scale-live-value stable';
                if (btnRead) btnRead.disabled = true;
                if (liveStatus) liveStatus.innerHTML = '<i class="fas fa-info-circle me-1"></i> Đang chờ dữ liệu...';
            }
        }

        if (BOOT._scaleModalUnsubscribe) { BOOT._scaleModalUnsubscribe(); BOOT._scaleModalUnsubscribe = null; }
        if (fieldId) {
            BOOT._scaleModalUnsubscribe = ScaleManager.onData(function (result) {
                if (btnRead) btnRead.disabled = !result.stable;
                if (liveVal) {
                    liveVal.textContent = `${result.value} ${result.unit}`;
                    liveVal.className = `scale-live-value ${result.stable ? 'stable' : 'unstable'}`;
                    if (liveStatus) {
                        liveStatus.innerHTML = result.stable
                            ? '<i class="fas fa-check-circle me-1 text-success"></i> <span class="text-success">Trạng thái: Ổn định - Nhấn "Đọc giá trị" để lưu</span>'
                            : '<i class="fas fa-exclamation-triangle me-1 text-warning"></i> <span class="text-warning">Trạng thái: Đang rung lắc</span>';
                    }
                }
            });
        }

        const modalEl = document.getElementById('scaleConnectionModal');
        if (modalEl && !modalEl._hasHideListener) {
            modalEl._hasHideListener = true;
            modalEl.addEventListener('hidden.bs.modal', () => {
                if (BOOT._scaleModalUnsubscribe) { BOOT._scaleModalUnsubscribe(); BOOT._scaleModalUnsubscribe = null; }
            });
        }

        if (modalEl && typeof bootstrap !== 'undefined' && typeof bootstrap.Modal !== 'undefined') {
            const isVisible = modalEl.classList.contains('show');
            if (!isVisible) {
                const modal = bootstrap.Modal.getOrCreateInstance ? bootstrap.Modal.getOrCreateInstance(modalEl) : new bootstrap.Modal(modalEl);
                modal.show();
            }
        }
    };

    BOOT.onChangeScaleConnectionType = function (type) {
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
            if (customFields) customFields.classList.toggle('d-none', brand !== 'custom');
            if (serialInfo) serialInfo.classList.remove('d-none');
            if (webSocketInfo) webSocketInfo.classList.add('d-none');
        }
    };

    BOOT.toggleCustomScaleFields = function (brand) {
        const customFields = document.getElementById('scale-custom-fields');
        if (!customFields) return;
        const connTypeInput = document.querySelector('input[name="scale-connection-type"]:checked');
        const connType = connTypeInput ? connTypeInput.value : 'serial';
        customFields.classList.toggle('d-none', !(connType === 'serial' && brand === 'custom'));
    };

    BOOT.connectScaleFromModal = async function () {
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
            const ip = document.getElementById('scale-websocket-ip')?.value.trim() || '';
            const port = document.getElementById('scale-websocket-port')?.value.trim() || '';
            if (!ip) { window.Swal?.fire('Lỗi nhập liệu', 'Vui lòng điền địa chỉ IP của thiết bị.', 'warning'); return; }
            if (!port) { window.Swal?.fire('Lỗi nhập liệu', 'Vui lòng điền cổng (Port) kết nối.', 'warning'); return; }
            config.ip = ip; config.port = port;
        }

        const success = await ScaleManager.connect(brand, config);
        if (success && BOOT._scaleTargetFieldId) {
            setTimeout(() => BOOT.readScaleValueIntoField(BOOT._scaleTargetFieldId), 500);
        }
    };

    BOOT.readScaleFromModal = function () {
        if (!BOOT._scaleTargetFieldId) return;
        const result = ScaleManager.getLastResult();
        if (!result || result.value === undefined) return;
        BOOT.writeScaleValueToField(BOOT._scaleTargetFieldId, result.value, result.unit);

        const modalEl = document.getElementById('scaleConnectionModal');
        if (modalEl && typeof bootstrap !== 'undefined' && typeof bootstrap.Modal !== 'undefined') {
            const inst = bootstrap.Modal.getInstance(modalEl) || (bootstrap.Modal.getOrCreateInstance ? bootstrap.Modal.getOrCreateInstance(modalEl) : null);
            if (inst) inst.hide();
        }
    };

    BOOT.toggleScaleDetails = function () {
        if (!BOOT.isAdmin) return;
        const container = document.getElementById('scale-hardware-details-container');
        const icon = document.getElementById('scale-details-icon');
        if (!container) return;
        const show = container.style.display === 'none';
        container.style.display = show ? 'block' : 'none';
        if (icon) { icon.classList.toggle('fa-chevron-down', !show); icon.classList.toggle('fa-chevron-up', show); }
    };

    BOOT.onScaleDeviceSelected = function (deviceId) {
        const inputsToToggle = [
            'scale-conn-type-serial', 'scale-conn-type-websocket', 'scale-brand-select',
            'scale-websocket-ip', 'scale-websocket-port',
            'scale-custom-baud', 'scale-custom-databits', 'scale-custom-parity', 'scale-custom-stopbits',
        ].map((id) => document.getElementById(id));

        if (!deviceId) {
            localStorage.removeItem('ebmr_selected_scale_device_id');
            inputsToToggle.forEach((input) => { if (input) input.disabled = false; });
            return;
        }

        const device = (window.SCALE_DEVICES || []).find((d) => String(d.id) === String(deviceId));
        if (!device) return;
        localStorage.setItem('ebmr_selected_scale_device_id', deviceId);

        const connType = device.connection_type || 'serial';
        const serialRadio = document.getElementById('scale-conn-type-serial');
        const wsRadio = document.getElementById('scale-conn-type-websocket');
        if (connType === 'websocket') { if (wsRadio) wsRadio.checked = true; } else { if (serialRadio) serialRadio.checked = true; }
        BOOT.onChangeScaleConnectionType(connType);

        const brand = device.brand || 'and';
        const brandSelect = document.getElementById('scale-brand-select');
        if (brandSelect) brandSelect.value = brand;
        BOOT.toggleCustomScaleFields(brand);

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

        inputsToToggle.forEach((input) => { if (input) input.disabled = true; });
    };

    // Expose lên window để dùng trực tiếp từ onclick= trong blade markup
    window.__V2__ = BOOT;
    window.onChangeScaleConnectionType = BOOT.onChangeScaleConnectionType;
    window.toggleCustomScaleFields = BOOT.toggleCustomScaleFields;
    window.connectScaleFromModal = BOOT.connectScaleFromModal;
    window.readScaleFromModal = BOOT.readScaleFromModal;
    window.toggleScaleDetails = BOOT.toggleScaleDetails;
    window.onScaleDeviceSelected = BOOT.onScaleDeviceSelected;
}
