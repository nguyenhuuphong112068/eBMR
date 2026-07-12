{{-- Bản PDF (dompdf) của Báo Cáo Môi Trường Sản Xuất — sinh tự động khi "Kết thúc sản
     xuất" (ProductionEnvironmentReportService::attachReportPdf). Khác bản in HTML
     (production_environment_print.blade.php): không Bootstrap/Chart.js, chỉ CSS inline
     + bảng + biểu đồ SVG server-side để dompdf render được; font DejaVu Sans có sẵn
     trong dompdf và đủ dấu tiếng Việt. --}}
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Báo Cáo Môi Trường Sản Xuất - {{ $room->code ?? '' }} - {{ $dist->batch_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #212529; }
        .navy { color: #003A4F; }
        h1 { font-size: 15px; color: #003A4F; margin-bottom: 2px; }
        .subtitle { color: #6c757d; font-size: 10px; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        .box { border: 1px solid #dee2e6; margin-bottom: 12px; }
        .box-title { background: #003A4F; color: #ffffff; font-weight: bold; padding: 5px 8px; font-size: 10px; }
        .data-table th { background: #f1f3f5; border: 1px solid #dee2e6; padding: 4px 6px; text-align: center; font-size: 9.5px; }
        .data-table td { border: 1px solid #dee2e6; padding: 4px 6px; font-size: 9.5px; }
        .text-center { text-align: center; }
        .fw-bold { font-weight: bold; }
        .text-muted { color: #6c757d; }
        .text-danger { color: #c0392b; }
        .text-success { color: #1e8449; }
        .oob-row td { background: #fdecea; }
        .badge { display: inline-block; padding: 1px 6px; border-radius: 8px; font-size: 8.5px; color: #ffffff; }
        .badge-danger { background: #c0392b; }
        .badge-success { background: #1e8449; }
        .info-table td { padding: 3px 8px; vertical-align: top; font-size: 10px; }
        .info-table .lbl { color: #6c757d; width: 130px; }
        .chart-block { padding: 6px 8px 2px 8px; }
        .chart-label { font-size: 10px; font-weight: bold; margin-bottom: 2px; }
        .footer { margin-top: 14px; font-size: 8.5px; color: #6c757d; border-top: 1px solid #dee2e6; padding-top: 5px; }
    </style>
</head>
<body>
    <h1>BÁO CÁO MÔI TRƯỜNG SẢN XUẤT — {{ $room->code ?? $dist->room_code }}</h1>
    <div class="subtitle">
        {{ $room->name ?? $dist->room_name }} — Lô <span class="fw-bold">{{ $dist->batch_number }}</span>
        @if ($dist->section_label) — {{ $dist->section_label }} @endif
    </div>

    <div class="box">
        <div class="box-title">Thông Tin Phiên Sản Xuất</div>
        <table class="info-table">
            <tr>
                <td class="lbl">Sản phẩm</td>
                <td class="fw-bold">{{ $productName }}</td>
                <td class="lbl">Người thực hiện</td>
                <td>{{ $executorName }}</td>
            </tr>
            <tr>
                <td class="lbl">Phòng sản xuất</td>
                <td>{{ $room->code ?? $dist->room_code }} — {{ $room->name ?? $dist->room_name }}</td>
                <td class="lbl">Phân đoạn</td>
                <td>{{ $dist->section_label ?: $dist->section_id }}</td>
            </tr>
            <tr>
                <td class="lbl">Bắt đầu sản xuất</td>
                <td>{{ \Carbon\Carbon::parse($dist->started_at)->format('d/m/Y H:i:s') }}</td>
                <td class="lbl">Kết thúc sản xuất</td>
                <td>
                    @if ($dist->production_ended_at)
                        {{ \Carbon\Carbon::parse($dist->production_ended_at)->format('d/m/Y H:i:s') }}
                    @else
                        <span class="text-success fw-bold">Đang sản xuất</span>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <div class="box">
        <div class="box-title">Bảng Tổng Kết</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="text-align:left;">Thông số</th>
                    <th>Giá trị nhỏ nhất</th>
                    <th>Giá trị lớn nhất</th>
                    <th>Ngưỡng cho phép</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Nhiệt độ (°C)</td>
                    <td class="text-center fw-bold">{{ $stats['temp_min'] ?? '-' }}</td>
                    <td class="text-center fw-bold">{{ $stats['temp_max'] ?? '-' }}</td>
                    <td class="text-center text-muted">{{ $thresholds['temp_min'] }} - {{ $thresholds['temp_max'] }}</td>
                </tr>
                <tr>
                    <td>Độ ẩm (%)</td>
                    <td class="text-center fw-bold">{{ $stats['humid_min'] ?? '-' }}</td>
                    <td class="text-center fw-bold">{{ $stats['humid_max'] ?? '-' }}</td>
                    <td class="text-center text-muted">{{ $thresholds['humid_min'] }} - {{ $thresholds['humid_max'] }}</td>
                </tr>
                <tr>
                    <td>Chênh áp (Pa)</td>
                    <td class="text-center fw-bold">{{ $stats['press_min'] ?? '-' }}</td>
                    <td class="text-center fw-bold">{{ $stats['press_max'] ?? '-' }}</td>
                    <td class="text-center text-muted">{{ $thresholds['press_min'] }} - {{ $thresholds['press_max'] }}</td>
                </tr>
                <tr>
                    <td class="fw-bold">Số lần vượt ngưỡng</td>
                    <td colspan="3" class="text-center fw-bold {{ $stats['out_of_bounds'] > 0 ? 'text-danger' : 'text-success' }}">
                        {{ $stats['out_of_bounds'] }} / {{ $stats['count'] }} lần ghi nhận
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    @if (!empty($chartSvgs))
        <div class="box">
            <div class="box-title">Biểu Đồ Diễn Biến Môi Trường</div>
            @foreach ($chartSvgs as $chart)
                <div class="chart-block">
                    <div class="chart-label" style="color: {{ $chart['color'] }};">{{ $chart['label'] }}</div>
                    <img src="{{ $chart['uri'] }}" style="width: 680px; height: 170px;">
                </div>
            @endforeach
            <div class="text-muted" style="padding: 0 8px 6px 8px; font-size: 8.5px;">Đường đứt nét đỏ: ngưỡng cho phép (min/max).</div>
        </div>
    @endif

    <div class="box">
        <div class="box-title">Chi Tiết Từng Lần Ghi Nhận</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>STT</th>
                    <th>Thời điểm</th>
                    <th>Nhiệt độ (°C)</th>
                    <th>Độ ẩm (%)</th>
                    <th>Chênh áp (Pa)</th>
                    <th>Trạng thái</th>
                    <th>Nguồn</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($readings as $r)
                    <tr class="{{ $r->is_out_of_bounds ? 'oob-row' : '' }}">
                        <td class="text-center">{{ $loop->iteration }}</td>
                        <td class="text-center">{{ \Carbon\Carbon::parse($r->captured_at)->format('d/m/Y H:i:s') }}</td>
                        <td class="text-center fw-bold">{{ $r->temperature }}</td>
                        <td class="text-center fw-bold">{{ $r->humidity }}</td>
                        <td class="text-center fw-bold">{{ $r->pressure }}</td>
                        <td class="text-center">
                            @if ($r->is_out_of_bounds)
                                <span class="badge badge-danger">Vượt ngưỡng</span>
                            @else
                                <span class="badge badge-success">Đạt</span>
                            @endif
                        </td>
                        <td class="text-center text-muted">{{ $r->recorded_type === 'manual' ? 'Thủ công' : 'Tự động' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted" style="padding: 14px;">Chưa có lần ghi nhận nào cho phiên sản xuất này.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="footer">
        Báo cáo được hệ thống tạo tự động khi Kết thúc sản xuất — {{ $generatedAt->format('d/m/Y H:i:s') }}.
    </div>
</body>
</html>
