/**
 * Nhiệt độ/Độ ẩm/Chênh áp phòng — chạy thử/thực thi lô V2.
 * =========================================================
 * 3 nút trên toolbar (#v2-env-monitor-group) hiển thị giá trị BMS giả lập cập nhật
 * liên tục (poll cùng endpoint productionBmsData dùng ở trang Phòng Sản Xuất) để
 * người ghi chép theo dõi môi trường phòng trong lúc sản xuất. Bấm vào 1 nút mở
 * modal #v2EnvHistoryModal vẽ biểu đồ + bảng TOÀN BỘ lần đọc đã ghi nhận cho phiên
 * sản xuất hiện tại (ebmr_records_bms, từ lúc Bắt đầu sản xuất) — nguồn dữ liệu lấy
 * qua ProductionEnvironmentController::readingsJson (xem urls.environmentReadingsBase).
 * Chỉ hoạt động khi có đủ envRoomId + envDistId (đang ghi chép đúng 1 phòng/phiên).
 */

const METRIC_META = {
    temperature: { label: 'Nhiệt Độ', unit: '°C', color: '#e74c3c', icon: 'fa-thermometer-half', thresholdKey: 'temp' },
    humidity: { label: 'Độ Ẩm', unit: '%', color: '#3498db', icon: 'fa-tint', thresholdKey: 'humid' },
    pressure: { label: 'Chênh Áp', unit: 'Pa', color: '#2ecc71', icon: 'fa-wind', thresholdKey: 'press' },
};

const POLL_INTERVAL_MS = 5000;

export function createEnvMonitorV2(BOOT) {
    let pollTimer = null;
    let chartInstance = null;

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

    function init() {
        if (!isEnabled()) return;
        document.querySelectorAll('#v2-env-monitor-group [data-metric]').forEach((btn) => {
            btn.addEventListener('click', () => openHistory(btn.dataset.metric));
        });
        startPolling();
    }

    return { init };
}
