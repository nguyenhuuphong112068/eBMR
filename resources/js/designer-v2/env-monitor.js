/**
 * Nhiệt độ/Độ ẩm/Chênh áp phòng — chạy thử/thực thi lô V2.
 * =========================================================
 * 3 nút trên toolbar (#v2-env-monitor-group) hiển thị giá trị BMS giả lập cập nhật
 * liên tục (poll cùng endpoint productionBmsData dùng ở trang Phòng Sản Xuất) để
 * người ghi chép theo dõi môi trường phòng trong lúc sản xuất. Bấm vào 1 nút mở hộp
 * thoại nhỏ #v2EnvQuickPanel — KHÔNG phải Bootstrap modal (không backdrop, không chặn
 * thao tác form phía sau) mà là hộp thoại nổi tự do, kéo được bằng tiêu đề (chuột lẫn
 * chạm). Bố cục telemetry-panel giống hệt card phòng ở trang Phòng Sản Xuất (giá trị +
 * cảnh báo vượt ngưỡng + mini biểu đồ + khoảng thiết kế cho cả 3 chỉ số cùng lúc). Trong
 * hộp thoại nhỏ có nút "Xem lịch sử chi tiết" mở modal biểu đồ + bảng TOÀN BỘ lần đọc đã
 * ghi nhận cho phiên sản xuất hiện tại
 * (#v2EnvHistoryModal, nguồn dữ liệu ProductionEnvironmentController::readingsJson,
 * xem urls.environmentReadingsBase). Chỉ hoạt động khi có đủ envRoomId + envDistId
 * (đang ghi chép đúng 1 phòng/phiên).
 */

const METRIC_META = {
    temperature: { label: 'Nhiệt Độ', unit: '°C', color: '#e74c3c', icon: 'fa-thermometer-half', thresholdKey: 'temp' },
    humidity: { label: 'Độ Ẩm', unit: '%', color: '#3498db', icon: 'fa-tint', thresholdKey: 'humid' },
    pressure: { label: 'Chênh Áp', unit: 'Pa', color: '#2ecc71', icon: 'fa-wind', thresholdKey: 'press' },
};

// Màu mini biểu đồ (sparkline) trong hộp thoại nhỏ — mượn đúng bảng màu của card phòng
// (telemetry-panel) ở trang Phòng Sản Xuất để trông "giống card phòng" như yêu cầu.
const SPARK_THEME = {
    temperature: { line: '#0d47a1', fill: 'rgba(13, 71, 161, 0.18)' },
    humidity: { line: '#00796b', fill: 'rgba(0, 121, 107, 0.18)' },
    pressure: { line: '#7b1fa2', fill: 'rgba(123, 31, 162, 0.18)' },
};

const POLL_INTERVAL_MS = 5000;

export function createEnvMonitorV2(BOOT) {
    let pollTimer = null;
    let chartInstance = null;
    let lastMetric = 'temperature';
    let quickInitialized = false;
    const sparklineCharts = {};
    const animationFrames = {};

    function isEnabled() {
        return !!(BOOT.isExecutionMode && BOOT.envRoomId && BOOT.envDistId && BOOT.urls);
    }

    function setLiveValue(metric, value) {
        const el = document.querySelector(`#v2-env-btn-${metric} .v2-env-value`);
        if (el && value !== undefined && value !== null && value !== '') el.textContent = value;
    }

    function pollLiveValues() {
        fetch(BOOT.urls.productionBmsData, { headers: { Accept: 'application/json' } })
            .then((r) => r.json())
            .then((res) => {
                const t = res && res.success ? res.data[BOOT.envRoomId] : null;
                if (!t) return;
                setLiveValue('temperature', t.temperature);
                setLiveValue('humidity', t.humidity);
                setLiveValue('pressure', t.pressure);

                if (quickInitialized) {
                    updateQuickMetric('temperature', t.temperature);
                    updateQuickMetric('humidity', t.humidity);
                    updateQuickMetric('pressure', String(t.pressure).replace('+', ''));
                }
            })
            .catch(() => { /* noop — giữ giá trị hiển thị gần nhất */ });
    }

    function startPolling() {
        pollLiveValues();
        pollTimer = setInterval(pollLiveValues, POLL_INTERVAL_MS);
    }

    function escHtml(s) {
        return String(s).replace(/[&<>"']/g, (c) => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
        }[c]));
    }

    function formatTime(raw) {
        const d = new Date(String(raw).replace(' ', 'T'));
        return isNaN(d.getTime()) ? raw : d.toLocaleString('vi-VN');
    }

    function renderTable(metric, readings) {
        const tbody = document.getElementById('v2-env-history-tbody');
        if (!tbody) return;
        if (!readings.length) {
            tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted py-4">Chưa có lần ghi nhận nào.</td></tr>';
            return;
        }
        tbody.innerHTML = readings.slice().reverse().map((r) => {
            const bad = !!r.is_out_of_bounds;
            return `<tr class="${bad ? 'table-danger' : ''}">
                <td class="small font-monospace">${escHtml(formatTime(r.captured_at))}</td>
                <td class="text-center fw-bold">${escHtml(r[metric])}</td>
                <td class="text-center">${bad
                    ? '<span class="badge bg-danger">Vượt ngưỡng</span>'
                    : '<span class="badge bg-success">Đạt</span>'}</td>
            </tr>`;
        }).join('');
    }

    function renderChart(metric, meta, readings, thresholds) {
        const canvas = document.getElementById('v2-env-history-chart');
        if (!canvas || typeof window.Chart === 'undefined') return;
        if (chartInstance) {
            chartInstance.destroy();
            chartInstance = null;
        }

        const labels = readings.map((r) => {
            const d = new Date(String(r.captured_at).replace(' ', 'T'));
            return isNaN(d.getTime()) ? r.captured_at : d.toLocaleTimeString('vi-VN');
        });
        const min = thresholds ? thresholds[`${meta.thresholdKey}_min`] : undefined;
        const max = thresholds ? thresholds[`${meta.thresholdKey}_max`] : undefined;

        chartInstance = new window.Chart(canvas, {
            type: 'line',
            data: {
                labels,
                datasets: [{
                    label: `${meta.label} (${meta.unit})`,
                    data: readings.map((r) => r[metric]),
                    borderColor: meta.color,
                    backgroundColor: 'transparent',
                    pointRadius: 2,
                    tension: 0.2,
                }],
            },
            options: {
                maintainAspectRatio: false,
                scales: {
                    xAxes: [{ ticks: { maxTicksLimit: 12, autoSkip: true } }],
                    yAxes: [{ ticks: { suggestedMin: min, suggestedMax: max } }],
                },
                tooltips: { mode: 'index', intersect: false },
            },
        });
    }

    function openHistory(metric) {
        const meta = METRIC_META[metric];
        if (!meta) return;

        const titleEl = document.getElementById('v2-env-history-title');
        const tbody = document.getElementById('v2-env-history-tbody');
        if (titleEl) titleEl.innerHTML = `<i class="fas ${meta.icon} me-2"></i>Lịch Sử ${meta.label} (${meta.unit})`;
        if (tbody) tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted py-4">Đang tải...</td></tr>';

        if (window.jQuery) window.jQuery('#v2EnvHistoryModal').modal('show');

        fetch(`${BOOT.urls.environmentReadingsBase}/${BOOT.envDistId}`, { headers: { Accept: 'application/json' } })
            .then((r) => r.json())
            .then((res) => {
                if (!res || !res.success) throw new Error('failed');
                const readings = res.readings || [];
                renderTable(metric, readings);
                renderChart(metric, meta, readings, res.thresholds);
            })
            .catch(() => {
                if (tbody) tbody.innerHTML = '<tr><td colspan="3" class="text-center text-danger py-4">Không tải được dữ liệu.</td></tr>';
            });
    }

    // ===== Hộp thoại nhỏ modeless (#v2EnvQuickPanel) — bố cục + mini biểu đồ giống card phòng =====

    function drawSparkline(chart) {
        const ctx = chart.ctx;
        const width = chart.canvas.width;
        const height = chart.canvas.height;
        const values = chart.values;
        const limits = chart.limits;

        ctx.clearRect(0, 0, width, height);

        const allVals = values.concat([limits.min, limits.max]);
        const minVal = Math.min(...allVals);
        const maxVal = Math.max(...allVals);
        const range = maxVal - minVal || 1.0;

        const padding = range * 0.15;
        const yMin = minVal - padding;
        const yMax = maxVal + padding;
        const yRange = yMax - yMin;

        function getX(index) {
            return (index / 9) * (width - 10) + 5;
        }

        function getY(val) {
            return height - ((val - yMin) / yRange) * (height - 16) - 8;
        }

        // 1. Đường ngưỡng min/max (nét đứt đỏ)
        ctx.strokeStyle = 'rgba(220, 53, 69, 0.35)';
        ctx.lineWidth = 1.5;
        ctx.setLineDash([4, 4]);

        ctx.beginPath();
        ctx.moveTo(getX(0), getY(limits.min));
        ctx.lineTo(getX(9), getY(limits.min));
        ctx.stroke();

        ctx.beginPath();
        ctx.moveTo(getX(0), getY(limits.max));
        ctx.lineTo(getX(9), getY(limits.max));
        ctx.stroke();

        ctx.setLineDash([]);

        // 2. Vùng gradient dưới đường dữ liệu
        const theme = SPARK_THEME[chart.metric] || SPARK_THEME.temperature;
        const fillGrad = ctx.createLinearGradient(0, 0, 0, height);
        fillGrad.addColorStop(0, theme.fill);
        fillGrad.addColorStop(1, 'rgba(0, 0, 0, 0)');

        ctx.beginPath();
        ctx.moveTo(getX(0), height);
        for (let i = 0; i < 10; i++) {
            ctx.lineTo(getX(i), getY(values[i]));
        }
        ctx.lineTo(getX(9), height);
        ctx.closePath();
        ctx.fillStyle = fillGrad;
        ctx.fill();

        // 3. Đường dữ liệu
        ctx.beginPath();
        for (let i = 0; i < 10; i++) {
            if (i === 0) ctx.moveTo(getX(i), getY(values[i]));
            else ctx.lineTo(getX(i), getY(values[i]));
        }
        ctx.strokeStyle = theme.line;
        ctx.lineWidth = 2.5;
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';
        ctx.stroke();

        // 4. Các điểm dữ liệu
        for (let i = 0; i < 10; i++) {
            ctx.beginPath();
            ctx.arc(getX(i), getY(values[i]), i === 9 ? 4 : 2, 0, 2 * Math.PI);
            ctx.fillStyle = i === 9 ? '#28a745' : theme.line;
            ctx.fill();
            ctx.strokeStyle = '#ffffff';
            ctx.lineWidth = i === 9 ? 1.5 : 0.8;
            ctx.stroke();
        }
    }

    function triggerLerpAnimation(chart) {
        const chartId = chart.metric;

        if (animationFrames[chartId]) {
            cancelAnimationFrame(animationFrames[chartId]);
        }

        function tick() {
            let changed = false;
            for (let i = 0; i < 10; i++) {
                const diff = chart.targetValues[i] - chart.values[i];
                if (Math.abs(diff) > 0.01) {
                    chart.values[i] += diff * 0.15;
                    changed = true;
                } else {
                    chart.values[i] = chart.targetValues[i];
                }
            }

            drawSparkline(chart);

            if (changed) {
                animationFrames[chartId] = requestAnimationFrame(tick);
            } else {
                animationFrames[chartId] = null;
            }
        }

        tick();
    }

    function seedValues(metric, readings, midValue) {
        const key = metric;
        const nums = readings
            .map((r) => parseFloat(r[key]))
            .filter((n) => !isNaN(n));

        if (!nums.length) return new Array(10).fill(midValue);

        const last10 = nums.slice(-10);
        while (last10.length < 10) last10.unshift(last10[0]);
        return last10;
    }

    function checkQuickLimits(metric, value) {
        const chart = sparklineCharts[metric];
        if (!chart) return;

        const floatVal = parseFloat(value);
        const isOut = !isNaN(floatVal) && (floatVal < chart.limits.min || floatVal > chart.limits.max);

        const badge = document.getElementById(`v2-env-quick-warn-${metric}`);
        if (badge) badge.classList.toggle('d-none', !isOut);

        const valEl = document.getElementById(`v2-env-quick-val-${metric}`);
        if (valEl) valEl.classList.toggle('text-danger-blink', isOut);
    }

    function initQuickCharts(readings, thresholds) {
        Object.keys(METRIC_META).forEach((metric) => {
            const meta = METRIC_META[metric];
            const canvas = document.getElementById(`v2-env-quick-spark-${metric}`);
            if (!canvas) return;

            const min = thresholds ? thresholds[`${meta.thresholdKey}_min`] : 0;
            const max = thresholds ? thresholds[`${meta.thresholdKey}_max`] : 100;
            const mid = (min + max) / 2;

            const values = seedValues(metric, readings, mid);

            sparklineCharts[metric] = {
                canvas,
                ctx: canvas.getContext('2d'),
                values: values.slice(),
                targetValues: values.slice(),
                limits: { min, max },
                metric,
            };

            drawSparkline(sparklineCharts[metric]);

            const rangeEl = document.getElementById(`v2-env-quick-range-${metric}`);
            if (rangeEl) rangeEl.textContent = `${min} - ${max}${meta.unit}`;

            const latest = values[values.length - 1];
            const valEl = document.getElementById(`v2-env-quick-val-${metric}`);
            if (valEl) valEl.textContent = metric === 'humidity' ? Math.round(latest) : latest;

            checkQuickLimits(metric, latest);
        });
    }

    function updateQuickMetric(metric, rawValue) {
        const chart = sparklineCharts[metric];
        if (!chart) return;

        const floatVal = parseFloat(rawValue);
        if (isNaN(floatVal)) return;

        chart.targetValues.shift();
        chart.targetValues.push(floatVal);
        const prevLatest = chart.values[9];
        chart.values.shift();
        chart.values.push(prevLatest);
        triggerLerpAnimation(chart);

        const valEl = document.getElementById(`v2-env-quick-val-${metric}`);
        if (valEl) {
            const formatted = String(rawValue).trim();
            if (valEl.textContent.trim() !== formatted) {
                valEl.textContent = formatted;
                valEl.classList.remove('telemetry-update-flash');
                void valEl.offsetWidth;
                valEl.classList.add('telemetry-update-flash');
            }
        }

        checkQuickLimits(metric, floatVal);
    }

    // Hộp thoại nhỏ là MODELESS (không dùng Bootstrap modal/backdrop) để người ghi chép
    // vẫn thao tác được form phía sau trong lúc theo dõi môi trường phòng — chỉ ẩn/hiện
    // + kéo vị trí bằng tiêu đề (xem makeQuickPanelDraggable), không chặn tương tác trang.
    function showQuickPanel() {
        const panel = document.getElementById('v2EnvQuickPanel');
        if (!panel) return;
        panel.style.display = 'block';
        panel.setAttribute('aria-hidden', 'false');
    }

    function hideQuickPanel() {
        const panel = document.getElementById('v2EnvQuickPanel');
        if (!panel) return;
        panel.style.display = 'none';
        panel.setAttribute('aria-hidden', 'true');
    }

    function openQuickPanel(metric) {
        lastMetric = metric;
        showQuickPanel();
        if (quickInitialized) return;

        fetch(`${BOOT.urls.environmentReadingsBase}/${BOOT.envDistId}`, { headers: { Accept: 'application/json' } })
            .then((r) => r.json())
            .then((res) => {
                if (!res || !res.success) throw new Error('failed');
                initQuickCharts(res.readings || [], res.thresholds || {});
                quickInitialized = true;
            })
            .catch(() => { /* noop — hộp thoại giữ trạng thái mặc định "--" */ });
    }

    // Kéo hộp thoại bằng tiêu đề — hỗ trợ cả chuột lẫn chạm (trang thực thi chạy trên
    // tablet không có chuột, xem [[ebmr-na-strike-feature]]). Chuyển từ neo top/right sang
    // toạ độ top/left theo px ngay khi bắt đầu kéo để không bị giật vị trí, và giữ hộp
    // thoại luôn nằm trong khung nhìn.
    function makeQuickPanelDraggable(panel, handle) {
        let dragging = false;
        let offsetX = 0;
        let offsetY = 0;

        function startDrag(clientX, clientY) {
            const rect = panel.getBoundingClientRect();
            offsetX = clientX - rect.left;
            offsetY = clientY - rect.top;
            panel.style.left = `${rect.left}px`;
            panel.style.top = `${rect.top}px`;
            panel.style.right = 'auto';
            dragging = true;
        }

        function moveDrag(clientX, clientY) {
            if (!dragging) return;
            const maxX = window.innerWidth - panel.offsetWidth;
            const maxY = window.innerHeight - panel.offsetHeight;
            const x = Math.max(0, Math.min(clientX - offsetX, maxX));
            const y = Math.max(0, Math.min(clientY - offsetY, maxY));
            panel.style.left = `${x}px`;
            panel.style.top = `${y}px`;
        }

        function endDrag() {
            dragging = false;
        }

        handle.addEventListener('mousedown', (e) => {
            startDrag(e.clientX, e.clientY);
            e.preventDefault();
        });
        document.addEventListener('mousemove', (e) => moveDrag(e.clientX, e.clientY));
        document.addEventListener('mouseup', endDrag);

        handle.addEventListener('touchstart', (e) => {
            const t = e.touches[0];
            if (t) startDrag(t.clientX, t.clientY);
        }, { passive: true });
        document.addEventListener('touchmove', (e) => {
            if (!dragging) return;
            const t = e.touches[0];
            if (t) moveDrag(t.clientX, t.clientY);
        }, { passive: true });
        document.addEventListener('touchend', endDrag);
    }

    function init() {
        if (!isEnabled()) return;
        document.querySelectorAll('#v2-env-monitor-group [data-metric]').forEach((btn) => {
            btn.addEventListener('click', () => openQuickPanel(btn.dataset.metric));
        });

        const panel = document.getElementById('v2EnvQuickPanel');
        const dragHandle = document.getElementById('v2-env-quick-drag-handle');
        if (panel && dragHandle) makeQuickPanelDraggable(panel, dragHandle);

        const closeBtn = document.getElementById('v2-env-quick-close');
        if (closeBtn) closeBtn.addEventListener('click', hideQuickPanel);

        const historyBtn = document.getElementById('v2-env-quick-history-btn');
        if (historyBtn) {
            historyBtn.addEventListener('click', () => {
                hideQuickPanel();
                openHistory(lastMetric);
            });
        }

        startPolling();
    }

    return { init };
}
