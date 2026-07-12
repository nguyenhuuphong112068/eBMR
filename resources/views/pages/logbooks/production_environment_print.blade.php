<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Báo Cáo Môi Trường Sản Xuất - {{ $room->code ?? '' }} - {{ $dist->batch_number }}</title>
    <link rel="stylesheet" href="{{ asset('fonts/Arimo/arimo.css') }}">
    <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/fontawesome-all.min.css') }}" rel="stylesheet">
    <style>
        body { font-family: 'Arimo', sans-serif; background: #fff; padding: 20px; color: #212529; }
        .bg-navy { background-color: #003A4F !important; }
        .text-navy { color: #003A4F !important; }
        .card { break-inside: avoid; }
        .table th, .table td { vertical-align: middle; }
        .out-of-bounds { background-color: #fdecea; }
        #printBtn { position: fixed; top: 16px; right: 16px; }
        @media print {
            #printBtn { display: none; }
            body { padding: 0; }
        }
    </style>
</head>
<body>
    <button id="printBtn" class="btn btn-sm btn-outline-danger rounded-pill shadow-sm" onclick="window.print()">
        <i class="fas fa-print me-1"></i> In
    </button>

    <div class="d-flex align-items-center mb-4">
        <div class="p-3 bg-navy text-white rounded-3 shadow-sm me-3">
            <i class="fas fa-thermometer-half fa-2x"></i>
        </div>
        <div>
            <h3 class="mb-0 text-navy fw-bold">BÁO CÁO MÔI TRƯỜNG SẢN XUẤT — {{ $room->code ?? '' }}</h3>
            <p class="text-muted mb-0 small">
                {{ $room->name ?? '' }} — Lô <span class="font-monospace fw-bold">{{ $dist->batch_number }}</span>
                @if ($dist->section_label) — {{ $dist->section_label }} @endif
            </p>
        </div>
    </div>

    <!-- Session Info -->
    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-3">
                    <div class="small text-muted fw-bold text-uppercase mb-1">Sản phẩm / Người thực hiện</div>
                    <div class="small"><i class="fas fa-flask text-navy me-1"></i>{{ $productName }}</div>
                    <div class="small"><i class="fas fa-user text-navy me-1"></i>{{ $executorName }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-3">
                    <div class="small text-muted fw-bold text-uppercase mb-1">Thời gian sản xuất</div>
                    <div class="small"><i class="fas fa-play text-success me-1"></i>{{ \Carbon\Carbon::parse($dist->started_at)->format('d/m/Y H:i:s') }}</div>
                    <div class="small">
                        @if ($dist->production_ended_at)
                            <i class="fas fa-stop text-danger me-1"></i>{{ \Carbon\Carbon::parse($dist->production_ended_at)->format('d/m/Y H:i:s') }}
                        @else
                            <span class="badge bg-success mt-1"><i class="fas fa-cogs me-1"></i>Đang sản xuất</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-3 text-center">
                    <div class="small text-muted fw-bold text-uppercase mb-1">Nhiệt độ (°C)</div>
                    <div class="fw-bold text-navy">{{ $stats['temp_min'] ?? '-' }} — {{ $stats['temp_max'] ?? '-' }}</div>
                    <div class="small text-muted">Ngưỡng: {{ $thresholds['temp_min'] }} - {{ $thresholds['temp_max'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-3 text-center">
                    <div class="small text-muted fw-bold text-uppercase mb-1">Độ ẩm (%) / Chênh áp (Pa)</div>
                    <div class="fw-bold text-navy">{{ $stats['humid_min'] ?? '-' }}—{{ $stats['humid_max'] ?? '-' }}% / {{ $stats['press_min'] ?? '-' }}—{{ $stats['press_max'] ?? '-' }}Pa</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 {{ $stats['out_of_bounds'] > 0 ? 'border-danger' : '' }}">
                <div class="card-body p-3 text-center">
                    <div class="small text-muted fw-bold text-uppercase mb-1">Số lần vượt ngưỡng</div>
                    <div class="fw-bold {{ $stats['out_of_bounds'] > 0 ? 'text-danger' : 'text-success' }}" style="font-size: 1.4rem;">
                        {{ $stats['out_of_bounds'] }} / {{ $stats['count'] }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white rounded-top-4 py-3 border-bottom">
            <h6 class="mb-0 fw-bold text-navy"><i class="fas fa-chart-line me-2"></i>Biểu Đồ Diễn Biến Môi Trường</h6>
        </div>
        <div class="card-body">
            <canvas id="envChart" height="90"></canvas>
        </div>
    </div>

    <!-- Bảng tổng kết -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white rounded-top-4 py-3 border-bottom">
            <h6 class="mb-0 fw-bold text-navy"><i class="fas fa-table me-2"></i>Bảng Tổng Kết</h6>
        </div>
        <div class="card-body p-0">
            <table class="table align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="py-3 border-0">Thông số</th>
                        <th class="text-center py-3 border-0">Giá trị nhỏ nhất</th>
                        <th class="text-center py-3 border-0">Giá trị lớn nhất</th>
                        <th class="text-center py-3 border-0">Ngưỡng</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Nhiệt độ (°C)</td>
                        <td class="text-center fw-bold">{{ $stats['temp_min'] ?? '-' }}</td>
                        <td class="text-center fw-bold">{{ $stats['temp_max'] ?? '-' }}</td>
                        <td class="text-center small text-muted">{{ $thresholds['temp_min'] }} - {{ $thresholds['temp_max'] }}</td>
                    </tr>
                    <tr>
                        <td>Độ ẩm (%)</td>
                        <td class="text-center fw-bold">{{ $stats['humid_min'] ?? '-' }}</td>
                        <td class="text-center fw-bold">{{ $stats['humid_max'] ?? '-' }}</td>
                        <td class="text-center small text-muted">{{ $thresholds['humid_min'] }} - {{ $thresholds['humid_max'] }}</td>
                    </tr>
                    <tr>
                        <td>Chênh áp (Pa)</td>
                        <td class="text-center fw-bold">{{ $stats['press_min'] ?? '-' }}</td>
                        <td class="text-center fw-bold">{{ $stats['press_max'] ?? '-' }}</td>
                        <td class="text-center small text-muted">{{ $thresholds['press_min'] }} - {{ $thresholds['press_max'] }}</td>
                    </tr>
                </tbody>
            </table>
            <div class="row g-3 p-3 border-top">
                <div class="col-md-4">
                    <div class="small text-muted fw-bold text-uppercase mb-1">Thời gian bắt đầu</div>
                    <div><i class="fas fa-play text-success me-1"></i>{{ \Carbon\Carbon::parse($dist->started_at)->format('d/m/Y H:i:s') }}</div>
                </div>
                <div class="col-md-4">
                    <div class="small text-muted fw-bold text-uppercase mb-1">Thời gian kết thúc</div>
                    <div>
                        @if ($dist->production_ended_at)
                            <i class="fas fa-stop text-danger me-1"></i>{{ \Carbon\Carbon::parse($dist->production_ended_at)->format('d/m/Y H:i:s') }}
                        @else
                            <span class="badge bg-success"><i class="fas fa-cogs me-1"></i>Đang sản xuất</span>
                        @endif
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="small text-muted fw-bold text-uppercase mb-1">Số lần vượt ngưỡng</div>
                    <div class="fw-bold {{ $stats['out_of_bounds'] > 0 ? 'text-danger' : 'text-success' }}">{{ $stats['out_of_bounds'] }} / {{ $stats['count'] }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Readings Table -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white rounded-top-4 py-3 border-bottom">
            <h6 class="mb-0 fw-bold text-navy"><i class="fas fa-list me-2"></i>Chi Tiết Từng Lần Ghi Nhận</h6>
        </div>
        <div class="card-body p-0">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="py-3 border-0">Thời điểm</th>
                        <th class="text-center py-3 border-0">Nhiệt độ (°C)</th>
                        <th class="text-center py-3 border-0">Độ ẩm (%)</th>
                        <th class="text-center py-3 border-0">Chênh áp (Pa)</th>
                        <th class="text-center py-3 border-0">Trạng thái</th>
                        <th class="text-center py-3 border-0">Nguồn</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($readings as $r)
                        <tr class="{{ $r->is_out_of_bounds ? 'out-of-bounds' : '' }}">
                            <td class="small font-monospace">{{ \Carbon\Carbon::parse($r->captured_at)->format('d/m/Y H:i:s') }}</td>
                            <td class="text-center fw-bold">{{ $r->temperature }}</td>
                            <td class="text-center fw-bold">{{ $r->humidity }}</td>
                            <td class="text-center fw-bold">{{ $r->pressure }}</td>
                            <td class="text-center">
                                @if ($r->is_out_of_bounds)
                                    <span class="badge bg-danger"><i class="fas fa-exclamation-triangle me-1"></i>Vượt ngưỡng</span>
                                @else
                                    <span class="badge bg-success"><i class="fas fa-check me-1"></i>Đạt</span>
                                @endif
                            </td>
                            <td class="text-center small text-muted">{{ $r->recorded_type === 'manual' ? 'Thủ công' : 'Tự động' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">Chưa có lần ghi nhận nào cho phiên sản xuất này.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <script src="{{ asset('dataTable/plugins/chart.js/Chart.min.js') }}"></script>
    <script>
        (function() {
            const ctx = document.getElementById('envChart');
            if (!ctx) return;

            const labels = @json($chartData['labels']);
            const temperature = @json($chartData['temperature']);
            const humidity = @json($chartData['humidity']);
            const pressure = @json($chartData['pressure']);

            const chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Nhiệt độ (°C)',
                            data: temperature,
                            borderColor: '#e74c3c',
                            backgroundColor: 'transparent',
                            yAxisID: 'y-temp',
                            pointRadius: 2,
                            tension: 0.2,
                        },
                        {
                            label: 'Độ ẩm (%)',
                            data: humidity,
                            borderColor: '#3498db',
                            backgroundColor: 'transparent',
                            yAxisID: 'y-humid',
                            pointRadius: 2,
                            tension: 0.2,
                        },
                        {
                            label: 'Chênh áp (Pa)',
                            data: pressure,
                            borderColor: '#2ecc71',
                            backgroundColor: 'transparent',
                            yAxisID: 'y-press',
                            pointRadius: 2,
                            tension: 0.2,
                        },
                    ],
                },
                options: {
                    animation: false,
                    maintainAspectRatio: false,
                    scales: {
                        xAxes: [{
                            ticks: { maxTicksLimit: 12, autoSkip: true },
                        }],
                        yAxes: [
                            { id: 'y-temp', position: 'left', scaleLabel: { display: true, labelString: 'Nhiệt độ (°C)' } },
                            { id: 'y-humid', position: 'right', scaleLabel: { display: true, labelString: 'Độ ẩm (%)' }, gridLines: { drawOnChartArea: false } },
                            { id: 'y-press', position: 'right', scaleLabel: { display: true, labelString: 'Chênh áp (Pa)' }, gridLines: { drawOnChartArea: false } },
                        ],
                    },
                    tooltips: { mode: 'index', intersect: false },
                },
            });

            window.onload = function() {
                setTimeout(function() { window.print(); }, 300);
            };
        })();
    </script>
</body>
</html>
