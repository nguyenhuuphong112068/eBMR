<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Báo Cáo Vệ Sinh Thiết Bị - {{ $equip->code }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Arimo:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Arimo', sans-serif; font-size: 14px; margin: 0; padding: 20px; }
        .text-center { text-align: center; }
        .fw-bold { font-weight: bold; }
        .title { font-size: 20px; margin-bottom: 5px; text-transform: uppercase; }
        .subtitle { font-size: 16px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #000; padding: 8px; text-align: left; vertical-align: top; }
        th { background-color: #f0f0f0; text-align: center; }
        .step-col { width: 5%; text-align: center; }
        .content-col { width: 30%; }
        .standard-col { width: 25%; }
        .result-col { width: 20%; }
        .executor-col { width: 20%; text-align: center; }
        .result-img { max-width: 100px; max-height: 100px; display: block; margin-top: 5px; }
        @media print {
            body { padding: 0; }
            button { display: none; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="text-center">
        <div class="title fw-bold">BÁO CÁO VỆ SINH THIẾT BỊ</div>
        <div class="subtitle">Mã thiết bị: {{ $equip->code }} - Tên thiết bị: {{ $equip->name }}</div>
    </div>
    
    <div style="margin-bottom: 10px;">
        <strong>Quy trình áp dụng:</strong> {{ $processList->process_name }} ({{ $processList->process_code }} - Phiên bản {{ $processList->version }})<br>
        <strong>Thời gian bắt đầu:</strong> {{ $campaign->started_at ? $campaign->started_at->format('d/m/Y H:i:s') : '' }}<br>
        <strong>Thời gian hoàn thành:</strong> {{ $campaign->completed_at ? $campaign->completed_at->format('d/m/Y H:i:s') : 'Chưa hoàn thành' }}
    </div>

    <table>
        <thead>
            <tr>
                <th class="step-col">Bước</th>
                <th class="content-col">Nội Dung Thực Hiện</th>
                <th class="standard-col">Tiêu Chuẩn</th>
                <th class="result-col">Kết Quả & Hình Đính Kèm</th>
                <th class="executor-col">Người Thực Hiện</th>
            </tr>
        </thead>
        <tbody>
            @foreach($campaignSteps as $step)
            <tr>
                <td class="text-center">{{ $step->step }}</td>
                <td>{!! strip_tags($step->content, '<br><b><i><strong><em><ul><li><ol>') !!}</td>
                <td>{!! strip_tags($step->standard, '<br><b><i><strong><em><ul><li><ol>') !!}</td>
                <td>
                    @if($step->is_done)
                        <div><strong>Kết quả:</strong> {{ $step->is_passed ? 'ĐẠT' : 'KHÔNG ĐẠT' }}</div>
                        @if($step->result_note)
                            <div><strong>Ghi chú:</strong> {{ $step->result_note }}</div>
                        @endif
                        @if($step->attached_images && is_array($step->attached_images))
                            @foreach($step->attached_images as $img)
                                <img src="{{ asset($img) }}" class="result-img" alt="Hình ảnh">
                            @endforeach
                        @endif
                    @else
                        <em>Chưa thực hiện</em>
                    @endif
                </td>
                <td class="text-center">
                    @if($step->is_done)
                        <div><strong>{{ $step->doneByUser->fullName ?? 'Unknown' }}</strong></div>
                        <div style="font-size: 12px; color: #555;">{{ $step->done_at ? $step->done_at->format('d/m/Y H:i:s') : '' }}</div>
                        @if($step->doneByUser && $step->doneByUser->signature_image)
                            @if(str_starts_with($step->doneByUser->signature_image, 'data:image'))
                                <img src="{{ $step->doneByUser->signature_image }}" alt="Chữ ký" style="max-height: 50px; margin-top: 5px; max-width: 150px;">
                            @else
                                <img src="{{ asset($step->doneByUser->signature_image) }}" alt="Chữ ký" style="max-height: 50px; margin-top: 5px; max-width: 150px;">
                            @endif
                        @else
                            <br><br><br>
                            <div><em>(Ký tên)</em></div>
                        @endif
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
