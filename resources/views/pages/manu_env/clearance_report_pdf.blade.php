{{-- Bản PDF (dompdf) của "Báo Cáo Dọn Quang" (phòng hoặc thiết bị) — sinh tự động khi
     "Kết thúc sản xuất" (ClearanceReportService::attachReportsPdf). Dùng chung cho cả
     dọn quang phòng lẫn dọn quang thiết bị: mọi khác biệt cột (is_done/is_checked,
     done_by/checked_by...) đã được chuẩn hoá phía service, blade chỉ nhận mảng $steps
     thống nhất. Ảnh kết quả + chữ ký nhúng dạng data URI để dompdf render offline được;
     nội dung/tiêu chuẩn strip_tags giữ định dạng cơ bản. Font DejaVu Sans đủ dấu TV. --}}
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }} - {{ $entityCode }} - {{ $batchNumber }}</title>
    <style>
        @page { margin: 15mm 12mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #212529; }
        h1 { font-size: 15px; color: #003A4F; margin-bottom: 2px; }
        .subtitle { color: #6c757d; font-size: 10px; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        .box { border: 1px solid #dee2e6; margin-bottom: 12px; }
        .box-title { background: #003A4F; color: #ffffff; font-weight: bold; padding: 5px 8px; font-size: 10px; }
        .info-table td { padding: 3px 8px; vertical-align: top; font-size: 10px; }
        .info-table .lbl { color: #6c757d; width: 130px; }
        .fw-bold { font-weight: bold; }
        .text-center { text-align: center; }
        .text-muted { color: #6c757d; }
        .text-danger { color: #c0392b; }
        .text-success { color: #1e8449; }
        .data-table th { background: #f1f3f5; border: 1px solid #dee2e6; padding: 4px 6px; text-align: center; font-size: 9.5px; }
        .data-table td { border: 1px solid #dee2e6; padding: 4px 6px; font-size: 9.5px; vertical-align: top; }
        .step-col { width: 5%; text-align: center; }
        .content-col { width: 30%; }
        .standard-col { width: 22%; }
        .result-col { width: 23%; }
        .executor-col { width: 20%; text-align: center; }
        .result-img { max-width: 90px; max-height: 90px; display: block; margin-top: 4px; }
        .sign-img { max-height: 42px; max-width: 130px; margin-top: 4px; }
        .badge { display: inline-block; padding: 1px 6px; border-radius: 8px; font-size: 8.5px; color: #ffffff; }
        .badge-danger { background: #c0392b; }
        .badge-success { background: #1e8449; }
        .footer { margin-top: 14px; font-size: 8.5px; color: #6c757d; border-top: 1px solid #dee2e6; padding-top: 5px; }
    </style>
</head>
<body>
    <h1>{{ $title }} — {{ $entityCode }}</h1>
    <div class="subtitle">
        {{ $entityName }} — Lô <span class="fw-bold">{{ $batchNumber }}</span>
        @if ($sectionLabel) — {{ $sectionLabel }} @endif
    </div>

    <div class="box">
        <div class="box-title">Thông Tin Dọn Quang</div>
        <table class="info-table">
            <tr>
                <td class="lbl">Sản phẩm</td>
                <td class="fw-bold">{{ $productName }}</td>
                <td class="lbl">{{ $entityTypeLabel }}</td>
                <td>{{ $entityCode }} — {{ $entityName }}</td>
            </tr>
            <tr>
                <td class="lbl">Quy trình áp dụng</td>
                <td>{{ $processName }} ({{ $processCode }} — Phiên bản {{ $version }})</td>
                <td class="lbl">Phân đoạn</td>
                <td>{{ $sectionLabel ?: '-' }}</td>
            </tr>
            <tr>
                <td class="lbl">Bắt đầu</td>
                <td>{{ $startedAt ? $startedAt->format('d/m/Y H:i:s') : '-' }} @if($startedByName) — {{ $startedByName }} @endif</td>
                <td class="lbl">Hoàn thành</td>
                <td>
                    @if ($completedAt)
                        {{ $completedAt->format('d/m/Y H:i:s') }} @if($completedByName) — {{ $completedByName }} @endif
                    @else
                        <span class="text-danger fw-bold">Chưa hoàn thành</span>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <div class="box">
        <div class="box-title">Chi Tiết Các Bước Thực Hiện</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th class="step-col">Bước</th>
                    <th class="content-col" style="text-align:left;">Nội Dung Thực Hiện</th>
                    <th class="standard-col" style="text-align:left;">Tiêu Chuẩn</th>
                    <th class="result-col" style="text-align:left;">Kết Quả &amp; Hình Đính Kèm</th>
                    <th class="executor-col">Người Thực Hiện</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($steps as $step)
                    <tr>
                        <td class="text-center fw-bold">{{ $step['step'] }}</td>
                        <td>{!! $step['content'] !!}</td>
                        <td>{!! $step['standard'] !!}</td>
                        <td>
                            @if ($step['is_done'])
                                <div>
                                    <span class="fw-bold">Kết quả: </span>
                                    @if ($step['is_passed'] === false)
                                        <span class="badge badge-danger">KHÔNG ĐẠT</span>
                                    @else
                                        <span class="badge badge-success">ĐẠT</span>
                                    @endif
                                </div>
                                @if (!empty($step['note']))
                                    <div style="margin-top:3px;"><span class="fw-bold">Ghi chú:</span> {{ $step['note'] }}</div>
                                @endif
                                @foreach ($step['images'] as $img)
                                    <img src="{{ $img }}" class="result-img" alt="Hình kết quả">
                                @endforeach
                            @else
                                <em class="text-muted">Chưa thực hiện</em>
                            @endif
                        </td>
                        <td class="text-center">
                            @if ($step['is_done'])
                                <div class="fw-bold">{{ $step['doneByName'] ?: '-' }}</div>
                                <div class="text-muted" style="font-size:8.5px;">{{ $step['doneAt'] ? $step['doneAt']->format('d/m/Y H:i:s') : '' }}</div>
                                @if (!empty($step['signatureUri']))
                                    <img src="{{ $step['signatureUri'] }}" class="sign-img" alt="Chữ ký">
                                @endif
                            @else
                                <em class="text-muted">-</em>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted" style="padding:14px;">Không có bước nào cho quy trình dọn quang này.</td>
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
