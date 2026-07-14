@extends('layout.master')

@section('title', 'Thực Thi Sản Xuất')

@section('leftNAV')

@section('mainContent')
    <div class="content-wrapper">
        <div class="card border-0 shadow-sm m-3">
            <div class="card-body p-4">
                <!-- view 1: Chọn công đoạn -->
                <div id="stages-view" class="transition-view">
                    <div class="d-flex align-items-center mb-4">
                        <div class="p-3 bg-navy text-white rounded-3 shadow-sm me-3">
                            <i class="fas fa-industry fa-2x"></i>
                        </div>
                        <div>
                            <h3 class="mb-0 text-navy fw-bold">THỰC THI SẢN XUẤT</h3>
                            <p class="text-muted mb-0 small">Chọn công đoạn để bắt đầu cập nhật dữ liệu và kiểm tra trạng
                                thái
                                phòng</p>
                        </div>
                    </div>

                    <!-- Workshop Selector -->
                    <div class="mb-4 d-flex justify-content-between align-items-center flex-wrap">
                        <div class="btn-group shadow-sm mb-2 mb-md-0" role="group" aria-label="Workshop Selection">
                            @foreach ($workshopsList as $ws)
                                <button type="button"
                                    class="btn btn-outline-primary workshop-btn {{ $loop->first ? 'active' : '' }}"
                                    data-workshop="{{ $ws }}" onclick="selectWorkshop('{{ $ws }}', this)">
                                    <i class="fas fa-building me-1"></i> {{ $ws }}
                                </button>
                            @endforeach
                        </div>

                        <div class="d-flex align-items-center gap-3 small bg-white px-3 py-2 rounded-pill shadow-sm">
                            <span class="fw-bold text-navy"><i class="fas fa-info-circle me-1"></i> Chú thích:</span>
                            <span class="text-secondary"><i class="fas fa-circle text-success ms-1"></i> Đang sản
                                xuất</span>
                            <span class="text-secondary"><i class="fas fa-circle text-info ms-1"></i> Sẵn sàng</span>
                            <span class="text-secondary"><i class="fas fa-circle text-warning ms-1"></i> Đang vệ sinh</span>
                            <span class="text-secondary"><i class="fas fa-circle text-danger ms-1"></i> Bảo trì</span>
                        </div>
                    </div>

                    <!-- Dynamic Stage Cards Grouped by Workshop -->
                    @foreach ($stageProductions as $workshop => $stages)
                        <div class="row g-4 mt-2 workshop-stages-container" id="workshop-stages-{{ $workshop }}"
                            {!! $loop->first ? '' : 'style="display: none;"' !!}>
                            @foreach ($stages as $stage)
                                @php
                                    // Pass arrays correctly to JS
                                    $codesJson = json_encode($stage->stage_codes);
                                @endphp
                                <div class="col-12 col-md-6 col-lg-4">
                                    <div class="stage-card h-100 border-0 shadow-sm rounded-4 overflow-hidden position-relative"
                                        data-workshop="{{ $workshop }}" data-stage-codes='{{ $codesJson }}'
                                        onclick='selectStage("{{ $stage->stage_name }}", {{ $codesJson }}, "{{ $stage->icon_class }}", "{{ $stage->gradient_class }}")'>
                                        <div class="card-bg {{ $stage->gradient_class }}"></div>
                                        <div
                                            class="card-body p-4 d-flex flex-column justify-content-between position-relative z-index-1">
                                            <div class="d-flex justify-content-between align-items-start mb-4">
                                                <div
                                                    class="stage-icon bg-white text-dark rounded-3 p-3 shadow-sm {{ str_contains($stage->icon_class, 'notch') ? 'animate-spin-slow' : '' }}">
                                                    <i class="fas {{ $stage->icon_class }} fa-lg"></i>
                                                </div>
                                                <span class="badge bg-white text-dark rounded-pill px-3 py-1 shadow-sm">
                                                    @if (count($stage->stage_codes) == 1)
                                                        CĐ 0{{ $stage->stage_codes[0] }}
                                                    @else
                                                        CĐ 0{{ implode('-0', $stage->stage_codes) }}
                                                    @endif
                                                </span>
                                            </div>
                                            <div>
                                                <h4 class="fw-bold text-white mb-2 text-uppercase">{{ $stage->stage_name }}
                                                </h4>
                                                <div class="d-flex flex-wrap gap-1 mt-2">
                                                    @foreach ($rooms as $room)
                                                        @if ($room->deparment_code == $workshop && in_array($room->stage_code, $stage->stage_codes))
                                                            @php
                                                                $hasActive = count($room->active_records) > 0;
                                                                if ($hasActive) {
                                                                    $sColor = 'text-success';
                                                                    $sTitle = 'Đang sản xuất';
                                                                } elseif ($room->id % 7 == 0) {
                                                                    $sColor = 'text-danger';
                                                                    $sTitle = 'Bảo trì';
                                                                } elseif ($room->id % 5 == 0) {
                                                                    $sColor = 'text-warning';
                                                                    $sTitle = 'Đang vệ sinh';
                                                                } else {
                                                                    $sColor = 'text-info';
                                                                    $sTitle = 'Sẵn sàng';
                                                                }
                                                            @endphp
                                                            <span class="badge bg-white text-dark shadow-sm"
                                                                title="{{ $sTitle }}" style="opacity: 0.95;">
                                                                {{ $room->code }}
                                                                <i class="fas fa-circle {{ $sColor }} ms-1"
                                                                    style="font-size: 0.55rem; vertical-align: middle;"></i>
                                                            </span>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>

                <!-- view 2: Danh sách phòng theo công đoạn (Ẩn mặc định) -->
                <div id="rooms-view" class="transition-view" style="display: none;">
                    <div
                        class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 pb-3 border-bottom">
                        <div class="d-flex align-items-center mb-3 mb-md-0">
                            <button class="btn btn-outline-secondary rounded-pill px-3 py-2 shadow-sm me-3 bg-white"
                                onclick="showStagesView()">
                                <i class="fas fa-chevron-left me-1"></i> Quay lại
                            </button>
                            <div>
                                <h3 class="mb-0 text-navy fw-bold d-flex align-items-center">
                                    <i id="active-stage-icon" class="fas fa-cog me-2 text-primary"></i>
                                    CÔNG ĐOẠN: <span id="active-stage-title" class="ms-2 text-primary">Cân Nguyên
                                        Liệu</span>
                                </h3>
                                <p class="text-muted mb-0 small">Khai báo và theo dõi nhật ký hoạt động của các phòng sản
                                    xuất
                                </p>
                            </div>
                        </div>
                        <div class="badge bg-navy rounded-pill px-4 py-2 shadow-sm fs-6 text-white" id="rooms-count-badge">0
                            Phòng</div>
                    </div>

                    <!-- Grid phòng -->
                    <div class="row g-4" id="rooms-grid">
                        @forelse($rooms as $room)
                            @php
                                // Phải tính lại $hasActive riêng cho từng phòng ở đây — biến này KHÔNG
                                // được kế thừa từ vòng lặp thẻ công đoạn phía trên (biến PHP thường không
                                // scope theo từng vòng lặp Blade khác nhau).
                                $hasActive = count($room->active_records) > 0;

                                // Mock sensor readings dynamically based on room ID (fluctuating with time)
                                $envReading = \App\Services\ProductionEnvironmentService::simulateReading($room->id);
                                $mockTemp = number_format($envReading['temperature'], 1);
                                $mockHumid = number_format($envReading['humidity'], 0);
                                $mockPressure = '+' . number_format($envReading['pressure'], 0);

                                // Map real status to UI classes
                                $rs = $room->room_status ?? 'ready';
                                if ($rs === 'producing') {
                                    $statusText = 'Đang sản xuất';
                                    $statusClass = 'status-active'; // blue
                                } elseif ($rs === 'maintenance') {
                                    $statusText = 'Bảo trì';
                                    $statusClass = 'status-maintenance'; // red
                                } elseif ($rs === 'cleaning') {
                                    $statusText = 'Đang vệ sinh';
                                    $statusClass = 'status-cleaning'; // yellow
                                } elseif ($rs === 'dirty' || $rs === 'line_clearance_required') {
                                    $statusText = 'Cần vệ sinh';
                                    $statusClass = 'status-cleaning'; // yellow
                                } elseif ($rs === 'needs_reclean') {
                                    $statusText = 'Cần vệ sinh lại';
                                    $statusClass = 'status-cleaning'; // yellow
                                } elseif ($rs === 'cleaned') {
                                    $statusText = 'Đã vệ sinh';
                                    $statusClass = 'status-ready'; // green
                                } else {
                                    $statusText = 'Sẵn sàng';
                                    $statusClass = 'status-ready'; // green
                                }
                            @endphp
                            <div id="room-card-{{ $room->id }}" class="col-12 col-md-6 col-lg-4 room-card-col"
                                data-room-id="{{ $room->id }}" data-stage-code="{{ $room->stage_code }}"
                                data-workshop-code="{{ $room->deparment_code }}">
                                <div
                                    class="card h-100 border-0 shadow-sm rounded-4 room-card transition-all position-relative">
                                    <div
                                        class="card-header bg-white pt-4 px-4 border-0 d-flex justify-content-between align-items-start">
                                        <div>
                                            <span
                                                class="badge bg-navy text-white rounded-pill px-3 py-1 font-monospace mb-1">{{ $room->code }}</span>
                                            <h5 class="fw-bold text-navy mb-0 mt-1">{{ $room->name }}</h5>
                                        </div>
                                        <div class="position-absolute" style="top: 1.2rem; right: 1.2rem;">
                                            <button class="status-indicator {{ $statusClass }} px-3 py-1 rounded-pill fw-bold text-uppercase border-0 shadow-sm transition-all btn-room-label"
                                                style="font-size: 0.75rem; cursor: pointer;"
                                                onclick="showLabel('room', '{{ $room->id }}')" title="Nhấp để xem Nhãn phòng">
                                                <i class="fas fa-tag me-1"></i>{{ $statusText }}
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-body px-4 pb-4 pt-2">
                                        <!-- Sensor display panel (styled like premium telemetry) -->
                                        <div
                                            class="telemetry-panel rounded-3 p-3 bg-light d-flex justify-content-between mb-3 shadow-inner">
                                            <div class="text-center flex-fill border-end px-1">
                                                <div class="small text-muted mb-1 font-monospace d-flex justify-content-center align-items-center"
                                                    style="font-size: 0.65rem;">
                                                    <span>NHIỆT ĐỘ</span>
                                                    <span
                                                        class="badge bg-danger text-white px-1 py-0 ms-1 rounded warning-badge d-none"
                                                        id="warning-temp-{{ $room->id }}"
                                                        style="font-size: 0.55rem;">CẢNH
                                                        BÁO</span>
                                                </div>
                                                <div class="fw-bold text-navy mb-1" style="font-size: 1.1rem;"><span
                                                        class="temp-val"
                                                        id="room-temp-{{ $room->id }}">{{ $mockTemp }}</span><span
                                                        style="font-size: 0.8rem;">°C</span></div>
                                                <canvas id="chart-temp-{{ $room->id }}" class="sparkline-canvas"
                                                    width="200" height="80" data-room-id="{{ $room->id }}"
                                                    data-metric="temp" data-min="{{ $room->limits['temp_min'] }}"
                                                    data-max="{{ $room->limits['temp_max'] }}"></canvas>
                                                <div class="text-muted font-monospace mt-1"
                                                    style="font-size: 0.55rem; opacity: 0.8;">
                                                    {{ $room->limits['temp_min'] }} - {{ $room->limits['temp_max'] }}°C
                                                </div>
                                            </div>
                                            <div class="text-center flex-fill border-end px-1">
                                                <div class="small text-muted mb-1 font-monospace d-flex justify-content-center align-items-center"
                                                    style="font-size: 0.65rem;">
                                                    <span>ĐỘ ẨM</span>
                                                    <span
                                                        class="badge bg-danger text-white px-1 py-0 ms-1 rounded warning-badge d-none"
                                                        id="warning-humid-{{ $room->id }}"
                                                        style="font-size: 0.55rem;">CẢNH BÁO</span>
                                                </div>
                                                <div class="fw-bold text-navy mb-1" style="font-size: 1.1rem;"><span
                                                        class="humid-val"
                                                        id="room-humid-{{ $room->id }}">{{ $mockHumid }}</span><span
                                                        style="font-size: 0.8rem;">%</span></div>
                                                <canvas id="chart-humid-{{ $room->id }}" class="sparkline-canvas"
                                                    width="200" height="80" data-room-id="{{ $room->id }}"
                                                    data-metric="humid" data-min="{{ $room->limits['humid_min'] }}"
                                                    data-max="{{ $room->limits['humid_max'] }}"></canvas>
                                                <div class="text-muted font-monospace mt-1"
                                                    style="font-size: 0.55rem; opacity: 0.8;">
                                                    {{ $room->limits['humid_min'] }} - {{ $room->limits['humid_max'] }}%
                                                </div>
                                            </div>
                                            <div class="text-center flex-fill px-1">
                                                <div class="small text-muted mb-1 font-monospace d-flex justify-content-center align-items-center"
                                                    style="font-size: 0.65rem;">
                                                    <span>ÁP SUẤT</span>
                                                    <span
                                                        class="badge bg-danger text-white px-1 py-0 ms-1 rounded warning-badge d-none"
                                                        id="warning-pressure-{{ $room->id }}"
                                                        style="font-size: 0.55rem;">CẢNH BÁO</span>
                                                </div>
                                                <div class="fw-bold text-navy mb-1" style="font-size: 1.1rem;"><span
                                                        class="pressure-val"
                                                        id="room-pressure-{{ $room->id }}">{{ $mockPressure }}</span><span
                                                        style="font-size: 0.8rem;">Pa</span></div>
                                                <canvas id="chart-pressure-{{ $room->id }}" class="sparkline-canvas"
                                                    width="200" height="80" data-room-id="{{ $room->id }}"
                                                    data-metric="pressure" data-min="{{ $room->limits['press_min'] }}"
                                                    data-max="{{ $room->limits['press_max'] }}"></canvas>
                                                <div class="text-muted font-monospace mt-1"
                                                    style="font-size: 0.55rem; opacity: 0.8;">
                                                    {{ $room->limits['press_min'] }} - {{ $room->limits['press_max'] }} Pa
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Current active operations or notes -->
                                        @if ($hasActive)
                                            <div class="active-record-list mt-3">
                                                <div class="small text-muted fw-bold mb-2 text-uppercase"
                                                    style="font-size: 0.7rem;"><i class="fas fa-tasks me-1"></i> Hồ sơ
                                                    đang
                                                    sản xuất:</div>
                                                @foreach ($room->active_records as $rec)
                                                    <div
                                                        class="active-record-item p-3 mb-2 rounded-3 border-start border-4 border-success bg-soft-success">
                                                        <div
                                                            class="d-flex justify-content-between align-items-center mb-1">
                                                            <span class="fw-bold text-navy small"><i
                                                                    class="fas fa-barcode me-1 text-success"></i> Số lô:
                                                                {{ $rec->batch_number }}</span>
                                                            @if ($rec->can_write)
                                                                <span class="badge bg-success text-white"
                                                                    style="font-size: 0.65rem;">
                                                                    <i class="fas fa-cogs me-1"></i>Đang sản xuất</span>
                                                            @elseif ($rec->can_resume_clearance)
                                                                <span class="badge bg-warning text-dark"
                                                                    style="font-size: 0.65rem;"
                                                                    title="Đã bắt đầu sản xuất — cần hoàn tất dọn quang phòng &amp; thiết bị trước khi ghi chép">
                                                                    <i class="fas fa-broom me-1"></i>Đang dọn quang
                                                                </span>
                                                            @elseif ($rec->can_start)
                                                                <span class="badge bg-primary text-white"
                                                                    style="font-size: 0.65rem;"
                                                                    title="Phòng &amp; thiết bị đã vệ sinh, hồ sơ sẵn sàng — có thể bắt đầu sản xuất">
                                                                    <i class="fas fa-check-double me-1"></i>Đủ điều kiện sản xuất
                                                                </span>
                                                            @elseif ($rec->is_distributed)
                                                                <span class="badge bg-info text-white"
                                                                    style="font-size: 0.65rem;"
                                                                    title="Đã phân phối xuống phòng &#10;Sản phẩm: {{ $rec->product_name }} &#10;Số lô: {{ $rec->batch_number }} &#10;Người nhận: {{ $rec->assigned_user_names ?: 'Chưa gán người' }}">
                                                                    <i class="fas fa-check-circle me-1"></i>Hồ sơ sẵn sàng
                                                                </span>
                                                            @endif
                                                        </div>
                                                        <p class="mb-1 text-muted text-truncate fw-medium"
                                                            style="font-size: 0.75rem;" title="{{ $rec->product_name }}">
                                                            {{ $rec->product_name }}</p>
                                                        @if ($rec->is_distributed)
                                                            <p class="mb-2 text-truncate" style="font-size: 0.68rem;"
                                                                title="Người nhận: {{ $rec->assigned_user_names ?: 'Chưa gán người' }}">
                                                                <i class="fas fa-user-check me-1 text-info"></i>
                                                                <span class="text-muted">Người nhận:</span>
                                                                <span class="fw-medium text-navy">{{ $rec->assigned_user_names ?: 'Chưa gán người' }}</span>
                                                            </p>
                                                        @endif
                                                        {{-- Điều kiện còn thiếu phải hiện thẳng lên card (không chỉ trong tooltip)
                                                             để người dùng biết ngay cần làm gì — chỉ áp dụng khi đã phân phối +
                                                             được phân công nhưng phòng/thiết bị chưa vệ sinh xong. --}}
                                                        @if ($rec->is_distributed && $rec->is_assigned && !$rec->production_started && !empty($rec->cleaning_missing))
                                                            <div class="mb-2 p-2 rounded-3 bg-soft-warning border border-warning-subtle"
                                                                style="font-size: 0.68rem;">
                                                                <div class="fw-bold text-warning-emphasis mb-1">
                                                                    <i class="fas fa-exclamation-triangle me-1"></i>Thiếu điều kiện sản xuất:
                                                                </div>
                                                                <ul class="mb-0 ps-3">
                                                                    @foreach ($rec->cleaning_missing as $reason)
                                                                        <li>{{ $reason }}</li>
                                                                    @endforeach
                                                                </ul>
                                                            </div>
                                                        @endif
                                                        {{-- Luồng 3 bước: (1) đủ điều kiện vệ sinh → "Bắt đầu sản xuất"; (2) sau khi
                                                             bắt đầu, dọn quang phòng + thiết bị là bước bắt buộc kế tiếp → "Tiếp tục
                                                             dọn quang"; (3) dọn quang xong mới mở "Ghi chép dữ liệu". --}}
                                                        @if ($rec->can_write)
                                                            <a href="{{ route('pages.ebmr.execute', $rec->id) }}?section={{ $rec->section_id }}&dist={{ $rec->distribution_id }}"
                                                                class="btn btn-sm btn-navy w-100 py-1 text-white shadow-sm font-weight-bold mb-1"
                                                                style="font-size: 0.75rem; border-radius: 6px;">
                                                                <i class="fas fa-edit me-1 text-white"></i> Ghi chép dữ liệu
                                                            </a>
                                                            @if (!$rec->production_ended)
                                                                <button type="button"
                                                                    class="btn btn-sm btn-outline-danger w-100 py-1 fw-bold js-end-production"
                                                                    style="font-size: 0.72rem; border-radius: 6px;"
                                                                    data-distribution-id="{{ $rec->distribution_id }}"
                                                                    data-batch="{{ $rec->batch_number }}"
                                                                    data-room="{{ $rec->room_id === $room->id ? $room->code : '' }}">
                                                                    <i class="fas fa-flag-checkered me-1"></i> Kết thúc sản xuất
                                                                </button>
                                                            @else
                                                                <span class="badge bg-secondary w-100 py-1 d-block" style="font-size: 0.68rem;">
                                                                    <i class="fas fa-check-double me-1"></i>Đã kết thúc sản xuất
                                                                </span>
                                                            @endif
                                                        @elseif ($rec->can_resume_clearance)
                                                            <button type="button"
                                                                class="btn-resume-clearance js-start-production w-100 d-flex align-items-center justify-content-center gap-2"
                                                                data-mode="resume"
                                                                data-distribution-id="{{ $rec->distribution_id }}">
                                                                <span class="start-btn-icon"><i class="fas fa-broom"></i></span>
                                                                <span>Tiếp tục dọn quang</span>
                                                            </button>
                                                        @elseif ($rec->can_start)
                                                            <button type="button"
                                                                class="btn-start-production js-start-production w-100 d-flex align-items-center justify-content-center gap-2"
                                                                data-mode="start"
                                                                data-distribution-id="{{ $rec->distribution_id }}"
                                                                data-batch="{{ $rec->batch_number }}"
                                                                data-product="{{ $rec->product_name }}"
                                                                data-section="{{ $rec->section_label ?: 'Công đoạn ' . $rec->section_id }}"
                                                                data-clearance-missing="{{ json_encode($rec->clearance_process_missing ?? [], JSON_UNESCAPED_UNICODE) }}">
                                                                <span class="start-btn-icon"><i class="fas fa-play"></i></span>
                                                                <span>Bắt đầu sản xuất</span>
                                                            </button>
                                                        @else
                                                            @php
                                                                if (!$rec->is_distributed) {
                                                                    $viewReason = 'Chưa được phân phối tới phòng này';
                                                                    $viewReasonShort = '(Chưa phân phối)';
                                                                } elseif (!$rec->is_assigned) {
                                                                    $viewReason = 'Bạn không nằm trong danh sách được phân công ghi chép công đoạn này';
                                                                    $viewReasonShort = '(Không được phân công)';
                                                                } elseif (!$rec->room_cleaning_ready) {
                                                                    $viewReason = 'Phòng/thiết bị chưa đủ điều kiện vệ sinh';
                                                                    $viewReasonShort = '(Chưa đủ điều kiện vệ sinh)';
                                                                } else {
                                                                    $viewReason = 'Chỉ xem hồ sơ';
                                                                    $viewReasonShort = '(Chỉ xem)';
                                                                }
                                                            @endphp
                                                            <a href="{{ route('pages.ebmr.execute', $rec->id) }}?section={{ $rec->section_id }}"
                                                                class="btn btn-sm btn-outline-secondary w-100 py-1 fw-bold"
                                                                style="font-size: 0.75rem; border-radius: 6px;"
                                                                title="{{ $viewReason }}">
                                                                <i class="fas fa-eye me-1"></i> Xem hồ sơ
                                                                <span class="d-block" style="font-size: 0.62rem; opacity: 0.85;">
                                                                    {{ $viewReasonShort }}
                                                                </span>
                                                            </a>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                        @if (!$hasActive || count($room->equipments) > 0)
                                            <!-- Danh sách thiết bị — luôn hiện kể cả khi phòng đang có lô, vì thẻ
                                                 "Thiếu điều kiện sản xuất" phía trên có thể chỉ đích danh 1 thiết bị
                                                 chưa vệ sinh; người dùng cần xem/nhấn ngay tại đây để xử lý. -->
                                            <div class="equipment-list-container mt-3 pt-2"
                                                style="border-top: 1px dashed rgba(0,0,0,0.1);">
                                                <div class="small text-muted fw-bold mb-2 text-uppercase d-flex justify-content-between align-items-center"
                                                    style="font-size: 0.7rem;">
                                                    <span><i class="fas fa-cogs me-1"></i> Thiết bị trong phòng</span>
                                                    <span
                                                        class="badge bg-light text-dark border">{{ count($room->equipments) }}</span>
                                                </div>
                                                @forelse($room->equipments as $eq)
                                                    <div class="row align-items-center mb-2 p-2 bg-light rounded equipment-item mx-0" data-code="{{ $eq->code }}">
                                                        <!-- Cột 1: Mã thiết bị -->
                                                        <div class="col-4 px-1 text-navy fw-bold text-truncate" style="font-size: 0.75rem;" title="{{ $eq->name }}">
                                                            <i class="fas fa-cog me-1 text-secondary"></i>{{ $eq->code }}
                                                        </div>
                                                        <!-- Cột 2: Nhãn vệ sinh -->
                                                        <div class="col-3 px-1 text-center">
                                                            @php
                                                                $eqClass = ($eq->eq_status === 'cleaned') ? 'bg-success text-white' : 'bg-warning text-dark';
                                                                $eqText = ($eq->eq_status === 'cleaned') ? 'Đã vệ sinh' : 'Cần vệ sinh';
                                                            @endphp
                                                            <span class="badge {{ $eqClass }} border btn-eq-label text-truncate w-100" style="cursor: pointer; font-size: 0.65rem;" onclick="showLabel('equipment', '{{ $eq->id }}')">
                                                                <i class="fas fa-soap"></i> {{ $eqText }}
                                                            </span>
                                                        </div>
                                                        <!-- Cột 3: Hiệu chuẩn bảo trì -->
                                                        <div class="col-5 px-1 d-flex gap-1 justify-content-end" style="font-size: 0.65rem;">
                                                            <span class="badge bg-secondary cal-badge text-truncate" style="cursor: pointer; max-width: 50%;" title="Đang tải..."><i class="fas fa-spinner fa-spin"></i> HC</span>
                                                            <span class="badge bg-secondary maint-badge text-truncate" style="cursor: pointer; max-width: 50%;" title="Đang tải..."><i class="fas fa-spinner fa-spin"></i> BT</span>
                                                        </div>
                                                    </div>
                                                @empty
                                                    <div class="text-muted text-center small py-2"
                                                        style="font-size: 0.7rem;">
                                                        <i class="fas fa-info-circle me-1"></i> Chưa khai báo thiết bị
                                                    </div>
                                                @endforelse
                                            </div>
                                        @endif
                                    </div>
                                    @if (in_array($rs, ['dirty', 'line_clearance_required', 'needs_reclean', 'cleaning']))
                                        <div class="card-footer bg-transparent border-0 px-4 pb-4 pt-0">
                                            @if ($rs === 'cleaning')
                                                <a href="{{ route('pages.manu_env.cleaning_process.campaign.open', ['room_id' => $room->id]) }}"
                                                    class="btn-cleaning-link d-flex align-items-center justify-content-center gap-2 w-100 text-decoration-none">
                                                    <span class="cleaning-btn-icon">
                                                        <i class="fas fa-broom"></i>
                                                    </span>
                                                    <span class="cleaning-btn-text">Tiếp Tục Vệ Sinh</span>
                                                    <i class="fas fa-chevron-right cleaning-btn-arrow ms-auto"></i>
                                                </a>
                                            @elseif ($rs === 'needs_reclean')
                                                <a href="javascript:void(0)" onclick="openStartCleaningModal({{ $room->id }}, 3, '{{ $room->code }} - {{ $room->name }}')"
                                                    class="btn-cleaning-link d-flex align-items-center justify-content-center gap-2 w-100 text-decoration-none bg-danger text-white border-danger">
                                                    <span class="cleaning-btn-icon text-white">
                                                        <i class="fas fa-exclamation-triangle"></i>
                                                    </span>
                                                    <span class="cleaning-btn-text">Vệ Sinh Lại</span>
                                                    <i class="fas fa-chevron-right cleaning-btn-arrow ms-auto text-white"></i>
                                                </a>
                                            @else
                                                <div class="dropup w-100">
                                                    <button class="btn-cleaning-link d-flex align-items-center justify-content-center gap-2 w-100 text-decoration-none dropdown-toggle" type="button" id="cleaningDropdown{{ $room->id }}" data-toggle="dropdown" aria-expanded="false">
                                                        <span class="cleaning-btn-icon">
                                                            <i class="fas fa-broom"></i>
                                                        </span>
                                                        <span class="cleaning-btn-text">Vệ Sinh Phòng</span>
                                                    </button>
                                                    <ul class="dropdown-menu w-100 text-center shadow border-0" aria-labelledby="cleaningDropdown{{ $room->id }}">
                                                        <li><a class="dropdown-item fw-bold text-navy py-2" href="javascript:void(0)" onclick="openStartCleaningModal({{ $room->id }}, 1, '{{ $room->code }} - {{ $room->name }}')"><i class="fas fa-broom me-2 text-primary"></i> Vệ Sinh Cấp 1</a></li>
                                                        <li><a class="dropdown-item fw-bold text-navy py-2" href="javascript:void(0)" onclick="openStartCleaningModal({{ $room->id }}, 2, '{{ $room->code }} - {{ $room->name }}')"><i class="fas fa-shower me-2 text-warning"></i> Vệ Sinh Cấp 2</a></li>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li><a class="dropdown-item fw-bold text-danger py-2" href="javascript:void(0)" onclick="openStartCleaningModal({{ $room->id }}, 3, '{{ $room->code }} - {{ $room->name }}')"><i class="fas fa-exclamation-triangle me-2"></i> Vệ Sinh Lại</a></li>
                                                    </ul>
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="col-12 text-center py-5">
                                <i class="fas fa-door-closed fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">Không tìm thấy phòng nào hoạt động</h5>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
    <style>
        .bg-navy {
            background-color: #003A4F !important;
        }

        .text-navy {
            color: #003A4F !important;
        }

        .btn-navy {
            background-color: #003A4F !important;
            border-color: #003A4F !important;
            color: #fff !important;
        }

        .btn-navy:hover {
            background-color: #002837 !important;
            color: #fff !important;
        }

        .bg-soft-success {
            background-color: rgba(40, 167, 69, 0.08);
        }

        .text-teal {
            color: #0d9488 !important;
        }

        .text-purple {
            color: #8b5cf6 !important;
        }

        .text-orange {
            color: #f97316 !important;
        }

        .text-rose {
            color: #f43f5e !important;
        }

        /* Telemetry styles */
        .telemetry-panel {
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .shadow-inner {
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.03);
        }

        /* Transitions */
        .transition-view {
            animation: fadeIn 0.4s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Stage Cards styling */
        .stage-card {
            cursor: pointer;
            min-height: 220px;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            border: 1px solid rgba(0, 0, 0, 0.05) !important;
        }

        .stage-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 25px rgba(0, 58, 79, 0.15) !important;
        }

        .stage-card .card-bg {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            opacity: 0.9;
            transition: all 0.3s;
        }

        .stage-card:hover .card-bg {
            opacity: 1;
            filter: brightness(1.05);
        }

        .stage-card .stage-icon {
            transition: all 0.3s;
        }

        .stage-card:hover .stage-icon {
            transform: scale(1.1) rotate(5deg);
        }

        /* Stage Gradient Backgrounds */
        .gradient-blue {
            background: linear-gradient(135deg, #0d47a1, #1976d2);
        }

        .gradient-teal {
            background: linear-gradient(135deg, #004d40, #00796b);
        }

        .gradient-purple {
            background: linear-gradient(135deg, #4a148c, #7b1fa2);
        }

        .gradient-orange {
            background: linear-gradient(135deg, #e65100, #f57c00);
        }

        .gradient-rose {
            background: linear-gradient(135deg, #880e4f, #c2185b);
        }

        .gradient-slate {
            background: linear-gradient(135deg, #263238, #455a64);
        }

        .gradient-success {
            background: linear-gradient(135deg, #1b5e20, #2e7d32);
        }

        .gradient-secondary {
            background: linear-gradient(135deg, #424242, #616161);
        }

        /* Slow spinning for bao phim icon */
        .animate-spin-slow {
            animation: spin 8s linear infinite;
        }

        @keyframes spin {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        /* Room Cards styling */
        .room-card {
            border: 1px solid rgba(0, 0, 0, 0.06) !important;
            transition: all 0.3s;
        }

        .room-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 58, 79, 0.1) !important;
            border-color: #003A4F !important;
        }

        /* Làm nổi bật thẻ phòng khi quay lại từ trang thực thi (?room=) để người dùng
           thấy ngay mình vừa rời phòng nào, không phải dò lại giữa cả lưới phòng. */
        .room-card-highlight {
            animation: room-card-pulse 1.2s ease-out 2;
            border-color: #003A4F !important;
        }

        @keyframes room-card-pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(0, 58, 79, 0.5);
            }

            70% {
                box-shadow: 0 0 0 14px rgba(0, 58, 79, 0);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(0, 58, 79, 0);
            }
        }

        .border-dashed {
            border-style: dashed !important;
            border-width: 2px !important;
        }

        /* Room Status Colors */
        .status-indicator {
            font-size: 0.7rem;
        }

        .status-active {
            background-color: rgba(40, 167, 69, 0.1);
            color: #28a745;
            border: 1px solid rgba(40, 167, 69, 0.2);
        }

        .status-maintenance {
            background-color: rgba(220, 53, 69, 0.1);
            color: #dc3545;
            border: 1px solid rgba(220, 53, 69, 0.2);
        }

        .status-cleaning {
            background-color: rgba(255, 193, 7, 0.1);
            color: #b58100;
            border: 1px solid rgba(255, 193, 7, 0.2);
        }

        .status-ready {
            background-color: rgba(23, 162, 184, 0.1);
            color: #17a2b8;
            border: 1px solid rgba(23, 162, 184, 0.2);
        }

        /* Sparkline canvases */
        .sparkline-canvas {
            width: 100%;
            height: 38px;
            margin-top: 8px;
            display: block;
        }

        /* Telemetry Flash Animation */
        @keyframes flash-green {
            0% {
                color: #28a745;
                text-shadow: 0 0 8px rgba(40, 167, 69, 0.6);
            }

            100% {
                color: #003A4F;
                text-shadow: none;
            }
        }

        .telemetry-update-flash {
            animation: flash-green 1.5s ease-out;
        }

        /* Warning Badge Pulse Animation */
        @keyframes pulse-red {
            0% {
                background-color: #dc3545;
                box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7);
            }

            70% {
                background-color: #dc3545;
                box-shadow: 0 0 0 6px rgba(220, 53, 69, 0);
            }

            100% {
                background-color: #dc3545;
                box-shadow: 0 0 0 0 rgba(220, 53, 69, 0);
            }
        }

        .warning-badge {
            animation: pulse-red 1.2s infinite;
        }

        /* Warning Text Blinking Animation */
        @keyframes blink-red-text {
            0% {
                color: #dc3545 !important;
                text-shadow: 0 0 6px rgba(220, 53, 69, 0.5);
            }

            50% {
                color: #003A4F !important;
                text-shadow: none;
            }

            100% {
                color: #dc3545 !important;
                text-shadow: 0 0 6px rgba(220, 53, 69, 0.5);
            }
        }

        .text-danger-blink {
            animation: blink-red-text 1.2s infinite !important;
        }

        /* ── CLEANING BUTTON ─────────────────────────────────── */
        .btn-cleaning-link {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: #fff;
            font-weight: 700;
            font-size: 0.82rem;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            padding: 11px 18px;
            border-radius: 12px;
            border: 1px solid rgba(255,255,255,0.2);
            box-shadow: 0 4px 14px rgba(245, 158, 11, 0.35);
            transition: all 0.28s cubic-bezier(0.25, 0.8, 0.25, 1);
            position: relative;
            overflow: hidden;
        }
        .btn-cleaning-link::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.18) 0%, transparent 60%);
            border-radius: inherit;
            pointer-events: none;
        }
        .btn-cleaning-link:hover {
            background: linear-gradient(135deg, #fbbf24 0%, #b45309 100%);
            box-shadow: 0 8px 24px rgba(245, 158, 11, 0.55);
            transform: translateY(-2px);
            color: #fff;
        }
        .btn-cleaning-link:active {
            transform: translateY(0);
            box-shadow: 0 2px 8px rgba(245, 158, 11, 0.4);
        }
        .cleaning-btn-icon {
            width: 30px;
            height: 30px;
            background: rgba(255,255,255,0.2);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            flex-shrink: 0;
            transition: transform 0.3s;
        }
        .btn-cleaning-link:hover .cleaning-btn-icon {
            transform: rotate(-15deg) scale(1.1);
        }
        .cleaning-btn-arrow {
            font-size: 0.75rem;
            opacity: 0.7;
            transition: transform 0.25s, opacity 0.25s;
        }
        .btn-cleaning-link:hover .cleaning-btn-arrow {
            transform: translateX(4px);
            opacity: 1;
        }

        /* ── START PRODUCTION BUTTON ─────────────────────────── */
        /* Nút to, đậm — production thao tác trên tablet cảm ứng */
        .btn-start-production {
            background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
            color: #fff;
            font-weight: 700;
            font-size: 0.8rem;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            padding: 10px 16px;
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 4px 14px rgba(22, 163, 74, 0.35);
            transition: all 0.28s cubic-bezier(0.25, 0.8, 0.25, 1);
            position: relative;
            overflow: hidden;
            animation: start-production-glow 2s ease-in-out infinite;
        }
        .btn-start-production::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.18) 0%, transparent 60%);
            border-radius: inherit;
            pointer-events: none;
        }
        .btn-start-production:hover {
            background: linear-gradient(135deg, #22c55e 0%, #166534 100%);
            box-shadow: 0 8px 24px rgba(22, 163, 74, 0.55);
            transform: translateY(-2px);
            color: #fff;
        }
        .btn-start-production:active {
            transform: translateY(0);
            box-shadow: 0 2px 8px rgba(22, 163, 74, 0.4);
        }
        .btn-start-production .start-btn-icon {
            width: 26px;
            height: 26px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            flex-shrink: 0;
            transition: transform 0.3s;
        }
        .btn-start-production:hover .start-btn-icon {
            transform: scale(1.15);
        }
        @keyframes start-production-glow {
            0%, 100% { box-shadow: 0 4px 14px rgba(22, 163, 74, 0.35); }
            50% { box-shadow: 0 4px 20px rgba(22, 163, 74, 0.6); }
        }

        /* ── RESUME CLEARANCE BUTTON ─────────────────────────── */
        .btn-resume-clearance {
            background: linear-gradient(135deg, #f59e0b 0%, #b45309 100%);
            color: #fff;
            font-weight: 700;
            font-size: 0.8rem;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            padding: 10px 16px;
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 4px 14px rgba(245, 158, 11, 0.35);
            transition: all 0.28s cubic-bezier(0.25, 0.8, 0.25, 1);
        }
        .btn-resume-clearance:hover {
            background: linear-gradient(135deg, #fbbf24 0%, #92400e 100%);
            box-shadow: 0 8px 24px rgba(245, 158, 11, 0.55);
            transform: translateY(-2px);
            color: #fff;
        }
        .btn-resume-clearance .start-btn-icon {
            width: 26px; height: 26px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.7rem; flex-shrink: 0;
        }

        /* ── MISSING CONDITIONS BLOCK ────────────────────────── */
        .bg-soft-warning { background-color: rgba(245, 158, 11, 0.08); }
        .border-warning-subtle { border-color: rgba(245, 158, 11, 0.35) !important; }
        .text-warning-emphasis { color: #92400e !important; }
    </style>
@endsection

<!-- Calibration Label Modal -->
<div class="modal fade" id="calLabelModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-tag mr-2"></i>NHÃN HIỆU CHUẨN THIẾT BỊ</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="text-center py-5" id="calLabelLoading" style="display: none;">
                    <div class="spinner-border text-info" role="status"></div>
                    <div class="mt-2 text-muted">Đang tải dữ liệu...</div>
                </div>
                <div id="calLabelContent" style="display: none;">
                    <div class="cal-label-container">
                        <div class="cal-label-info">
                            <table style="width: 100%; border: none;">
                                <tr>
                                    <td style="width: 30%; font-weight: bold; font-size: 15px; padding: 4px;">Tên thiết bị</td>
                                    <td style="width: 70%; font-size: 15px; padding: 4px;">: <span id="lblEquipName"></span></td>
                                </tr>
                                <tr>
                                    <td style="font-weight: bold; font-size: 15px; padding: 4px;">Mã số thiết bị</td>
                                    <td style="font-size: 15px; padding: 4px;">: <span id="lblEquipCode"></span></td>
                                </tr>
                            </table>
                        </div>

                        <table class="cal-label-table table table-bordered mt-2">
                            <thead class="bg-light">
                                <tr>
                                    <th style="width: 8%;">STT</th>
                                    <th style="width: 24%;">Mã số</th>
                                    <th style="width: 24%;">Tên</th>
                                    <th style="width: 22%;">Ngày hiệu chuẩn</th>
                                    <th style="width: 22%;">Hạn sử dụng</th>
                                </tr>
                            </thead>
                            <tbody id="calLabelTableBody">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Maintenance Label Modal -->
<div class="modal fade" id="maintLabelModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-wrench mr-2"></i>NHÃN BẢO TRÌ THIẾT BỊ</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="text-center py-5" id="maintLabelLoading" style="display: none;">
                    <div class="spinner-border text-primary" role="status"></div>
                    <div class="mt-2 text-muted">Đang tải dữ liệu...</div>
                </div>
                <div id="maintLabelContent" style="display: none;">
                    <div class="cal-label-container">
                        <div class="cal-label-info">
                            <table style="width: 100%; border: none;">
                                <tr>
                                    <td style="width: 30%; font-weight: bold; font-size: 15px; padding: 4px;">Tên thiết bị</td>
                                    <td style="width: 70%; font-size: 15px; padding: 4px;">: <span id="lblMaintEquipName"></span></td>
                                </tr>
                                <tr>
                                    <td style="font-weight: bold; font-size: 15px; padding: 4px;">Mã số thiết bị</td>
                                    <td style="font-size: 15px; padding: 4px;">: <span id="lblMaintEquipCode"></span></td>
                                </tr>
                            </table>
                        </div>

                        <table class="cal-label-table table table-bordered mt-2">
                            <thead class="bg-light">
                                <tr>
                                    <th style="width: 8%;">STT</th>
                                    <th style="width: 18%;">Mã số</th>
                                    <th style="width: 20%;">Tên</th>
                                    <th style="width: 15%;">Chu kỳ</th>
                                    <th style="width: 19%;">Ngày bảo trì</th>
                                    <th style="width: 20%;">Hạn bảo trì</th>
                                </tr>
                            </thead>
                            <tbody id="maintLabelTableBody">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@section('model')
    @include('pages.ebmr.production.modals.labelModal')
    @include('pages.ebmr.production.modals.cleaningCampaignModal')
@endsection

@section('script')
    <script>
        // Client-side mapping variables
        let selectedWorkshop = '{{ $workshopsList->first() ?? '' }}';
        let selectedStageTitle = '';
        let selectedStageCodes = [];
        let bmsPollInterval = null;

        // Sparkline chart data tracking
        const sparklineCharts = {};
        const animationFrames = {};

        // Xưởng + mã công đoạn của từng phòng — dùng để đưa người dùng quay lại đúng vị trí
        // phòng (nút "Quay lại" ở trang thực thi, xem navigateToRoom()).
        const roomMetaMap = @json($rooms->mapWithKeys(function ($r) {
            return [$r->id => ['workshop' => $r->deparment_code, 'stageCode' => (int) $r->stage_code]];
        }));

        $(document).ready(function() {
            initSparklines();

            // Hiển thị lỗi flash (nếu bị redirect từ campaign)
            @if(session('error'))
            Swal.fire({
                icon: 'warning',
                title: 'Không thể mở vệ sinh',
                text: '{{ session("error") }}',
                confirmButtonColor: '#003A4F'
            });
            @endif

            // Quay lại từ trang thực thi (?room=ID): tự chọn đúng xưởng + công đoạn rồi cuộn
            // tới đúng thẻ phòng thay vì bỏ người dùng ở màn hình chọn công đoạn trống.
            const targetRoomId = new URLSearchParams(window.location.search).get('room');
            if (targetRoomId) navigateToRoom(targetRoomId);
        });

        function navigateToRoom(roomId) {
            const meta = roomMetaMap[roomId];
            if (!meta) return;

            const $wsBtn = $('.workshop-btn[data-workshop="' + meta.workshop + '"]');
            if ($wsBtn.length) selectWorkshop(meta.workshop, $wsBtn.get(0));

            const $stageCard = $('.stage-card').filter(function() {
                const codes = $(this).data('stage-codes');
                return $(this).data('workshop') === meta.workshop && Array.isArray(codes) &&
                    codes.includes(meta.stageCode);
            }).first();
            if ($stageCard.length) $stageCard.trigger('click');

            // selectStage() chuyển màn hình bằng fadeOut/fadeIn 200ms — đợi xong mới cuộn để
            // scrollIntoView không bị tính sai vị trí lúc #rooms-view còn ẩn.
            setTimeout(function() {
                const $card = $('#room-card-' + roomId);
                if (!$card.length) return;
                $card.get(0).scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
                $card.addClass('room-card-highlight');
                setTimeout(function() {
                    $card.removeClass('room-card-highlight');
                }, 2500);
            }, 500);
        }

        function calculateMockValue(roomId, metric, timeSecs) {
            roomId = parseInt(roomId);
            if (metric === 'temp') {
                const baseTemp = 20.0 + (roomId % 5) * 0.8;
                const fluctTemp = Math.sin(timeSecs / 30.0 + roomId) * 0.4;
                return parseFloat((baseTemp + fluctTemp).toFixed(1));
            } else if (metric === 'humid') {
                const baseHumid = 42 + (roomId % 6) * 1.5;
                const fluctHumid = Math.cos(timeSecs / 45.0 + roomId) * 2;
                return parseFloat((baseHumid + fluctHumid).toFixed(0));
            } else if (metric === 'pressure') {
                const basePressure = 8 + (roomId % 8);
                const fluctPressure = Math.sin(timeSecs / 60.0 + roomId) * 1;
                return parseFloat((basePressure + fluctPressure).toFixed(0));
            }
            return 0;
        }

        function initSparklines() {
            $('.sparkline-canvas').each(function() {
                const canvas = $(this);
                const roomId = canvas.data('room-id');
                const metric = canvas.data('metric');
                const limitMin = parseFloat(canvas.data('min'));
                const limitMax = parseFloat(canvas.data('max'));

                const values = [];
                const targetValues = [];
                const now = Math.floor(Date.now() / 1000);

                // Seed 10 historical values using the mock simulation formula back-dated
                for (let i = 0; i < 10; i++) {
                    const tickTime = now - (9 - i) * 5;
                    const val = calculateMockValue(roomId, metric, tickTime);
                    values.push(val);
                    targetValues.push(val);
                }

                const chartId = roomId + '-' + metric;
                sparklineCharts[chartId] = {
                    canvas: canvas[0],
                    ctx: canvas[0].getContext('2d'),
                    values: values,
                    targetValues: targetValues,
                    limits: {
                        min: limitMin,
                        max: limitMax
                    },
                    metric: metric,
                    roomId: roomId
                };

                drawSparkline(sparklineCharts[chartId]);
            });

            // Initial room limit warning checks
            const roomIds = [];
            $('.sparkline-canvas').each(function() {
                const roomId = $(this).data('room-id');
                if (!roomIds.includes(roomId)) {
                    roomIds.push(roomId);
                }
            });
            roomIds.forEach(function(roomId) {
                const tempVal = $('#room-temp-' + roomId).text();
                const humidVal = $('#room-humid-' + roomId).text();
                const pressVal = $('#room-pressure-' + roomId).text();
                checkRoomLimits(roomId, tempVal, humidVal, pressVal);
            });
        }

        function drawSparkline(chart) {
            const ctx = chart.ctx;
            const width = chart.canvas.width;
            const height = chart.canvas.height;
            const values = chart.values;
            const limits = chart.limits;

            ctx.clearRect(0, 0, width, height);

            // Find min/max boundaries including the limit thresholds to scale appropriately
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

            // 1. Draw standard limit dashed lines
            ctx.strokeStyle = 'rgba(220, 53, 69, 0.35)'; // red dashboard indicator
            ctx.lineWidth = 1.5;
            ctx.setLineDash([4, 4]);

            // Min limit line
            ctx.beginPath();
            ctx.moveTo(getX(0), getY(limits.min));
            ctx.lineTo(getX(9), getY(limits.min));
            ctx.stroke();

            // Max limit line
            ctx.beginPath();
            ctx.moveTo(getX(0), getY(limits.max));
            ctx.lineTo(getX(9), getY(limits.max));
            ctx.stroke();

            ctx.setLineDash([]); // Reset dashed pattern

            // 2. Draw gradient fill area under data line
            const fillGrad = ctx.createLinearGradient(0, 0, 0, height);
            let colorTheme = '#003A4F';
            let startFill = 'rgba(0, 58, 79, 0.2)';
            let endFill = 'rgba(0, 58, 79, 0)';

            if (chart.metric === 'temp') {
                colorTheme = '#0d47a1';
                startFill = 'rgba(13, 71, 161, 0.18)';
            } else if (chart.metric === 'humid') {
                colorTheme = '#00796b';
                startFill = 'rgba(0, 121, 107, 0.18)';
            } else if (chart.metric === 'pressure') {
                colorTheme = '#7b1fa2';
                startFill = 'rgba(123, 31, 162, 0.18)';
            }

            fillGrad.addColorStop(0, startFill);
            fillGrad.addColorStop(1, endFill);

            ctx.beginPath();
            ctx.moveTo(getX(0), height);
            for (let i = 0; i < 10; i++) {
                ctx.lineTo(getX(i), getY(values[i]));
            }
            ctx.lineTo(getX(9), height);
            ctx.closePath();
            ctx.fillStyle = fillGrad;
            ctx.fill();

            // 3. Draw data line stroke
            ctx.beginPath();
            for (let i = 0; i < 10; i++) {
                if (i === 0) {
                    ctx.moveTo(getX(i), getY(values[i]));
                } else {
                    ctx.lineTo(getX(i), getY(values[i]));
                }
            }
            ctx.strokeStyle = colorTheme;
            ctx.lineWidth = 2.5;
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';
            ctx.stroke();

            // 4. Draw data points
            for (let i = 0; i < 10; i++) {
                ctx.beginPath();
                ctx.arc(getX(i), getY(values[i]), i === 9 ? 4 : 2, 0, 2 * Math.PI);
                ctx.fillStyle = i === 9 ? '#28a745' : colorTheme;
                ctx.fill();
                ctx.strokeStyle = '#ffffff';
                ctx.lineWidth = i === 9 ? 1.5 : 0.8;
                ctx.stroke();
            }
        }

        function updateSparklineData(roomId, metric, newValue) {
            const chartId = roomId + '-' + metric;
            const chart = sparklineCharts[chartId];
            if (!chart) return;

            const floatVal = parseFloat(newValue);
            if (isNaN(floatVal)) return;

            // Shift targets
            chart.targetValues.shift();
            chart.targetValues.push(floatVal);

            // Roll visual values left
            const prevLatest = chart.values[9];
            chart.values.shift();
            chart.values.push(prevLatest);

            // Animate LERP convergence
            triggerLerpAnimation(chart);
        }

        function triggerLerpAnimation(chart) {
            const chartId = chart.roomId + '-' + chart.metric;

            if (animationFrames[chartId]) {
                cancelAnimationFrame(animationFrames[chartId]);
            }

            function tick() {
                let changed = false;
                for (let i = 0; i < 10; i++) {
                    const diff = chart.targetValues[i] - chart.values[i];
                    if (Math.abs(diff) > 0.01) {
                        chart.values[i] += diff * 0.15; // smooth morph speed
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

        function selectWorkshop(workshopCode, btnElement) {
            selectedWorkshop = workshopCode;

            // Update active button styling
            $('.workshop-btn').removeClass('active');
            $(btnElement).addClass('active');

            // Show only the selected workshop's stages
            $('.workshop-stages-container').hide();
            $('#workshop-stages-' + workshopCode).fadeIn(300);
        }

        function selectStage(title, codes, iconClass, gradientClass) {
            selectedStageTitle = title;
            selectedStageCodes = codes;

            // 1. Update UI headers
            $('#active-stage-title').text(title);
            $('#active-stage-icon').attr('class', 'fas ' + iconClass + ' me-2 text-primary');

            // 2. Filter room grid
            let roomCount = 0;
            $('.room-card-col').each(function() {
                const card = $(this);
                const cardStageCode = parseInt(card.data('stage-code'));
                const cardWorkshopCode = card.data('workshop-code');

                // Check if card belongs to selected stage AND selected workshop
                if (codes.includes(cardStageCode) && cardWorkshopCode === selectedWorkshop) {
                    card.show();
                    roomCount++;
                } else {
                    card.hide();
                }
            });

            // 3. Update count badge
            $('#rooms-count-badge').text(roomCount + ' Phòng');

            // 4. Smooth slide transition from stages to rooms view
            $('#stages-view').fadeOut(200, function() {
                $('#rooms-view').fadeIn(200);
                startBmsPolling();

                // Draw sparklines for visible room cards immediately
                Object.keys(sparklineCharts).forEach(function(chartId) {
                    const chart = sparklineCharts[chartId];
                    if ($(chart.canvas).is(':visible')) {
                        drawSparkline(chart);
                    }
                });
            });
        }

        function showStagesView() {
            stopBmsPolling();
            $('#rooms-view').fadeOut(200, function() {
                $('#stages-view').fadeIn(200);
            });
        }

        function startBmsPolling() {
            stopBmsPolling();
            updateBmsTelemetry();
            bmsPollInterval = setInterval(updateBmsTelemetry, 5000);
        }

        function stopBmsPolling() {
            if (bmsPollInterval) {
                clearInterval(bmsPollInterval);
                bmsPollInterval = null;
            }
        }

        function updateBmsTelemetry() {
            $.ajax({
                url: "{{ route('pages.ebmr.productionBmsData') }}",
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.data) {
                        const data = response.data;
                        Object.keys(data).forEach(function(roomId) {
                            const roomData = data[roomId];

                            updateElementValue($('#room-temp-' + roomId), roomData.temperature);
                            updateElementValue($('#room-humid-' + roomId), roomData.humidity);
                            updateElementValue($('#room-pressure-' + roomId), roomData.pressure);

                            // Update sparklines
                            updateSparklineData(roomId, 'temp', roomData.temperature);
                            updateSparklineData(roomId, 'humid', roomData.humidity);
                            updateSparklineData(roomId, 'pressure', roomData.pressure.replace('+', ''));

                            // Verify warning limits
                            checkRoomLimits(roomId, roomData.temperature, roomData.humidity, roomData
                                .pressure);
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error("Error fetching BMS telemetry data:", error);
                }
            });
        }

        function updateElementValue(el, newValue) {
            if (el.length === 0) return;

            const oldValue = el.text().trim();
            const formattedNew = String(newValue).trim();

            if (oldValue !== formattedNew) {
                el.text(newValue);

                el.removeClass('telemetry-update-flash');
                void el[0].offsetWidth;
                el.addClass('telemetry-update-flash');
            }
        }

        function checkRoomLimits(roomId, tempVal, humidVal, pressVal) {
            const tempCanvas = $('#chart-temp-' + roomId);
            if (tempCanvas.length === 0) return;

            const tempMin = parseFloat(tempCanvas.data('min'));
            const tempMax = parseFloat(tempCanvas.data('max'));
            const humidMin = parseFloat($('#chart-humid-' + roomId).data('min'));
            const humidMax = parseFloat($('#chart-humid-' + roomId).data('max'));
            const pressMin = parseFloat($('#chart-pressure-' + roomId).data('min'));
            const pressMax = parseFloat($('#chart-pressure-' + roomId).data('max'));

            const cleanPress = parseFloat(String(pressVal).replace('+', ''));
            const cleanTemp = parseFloat(tempVal);
            const cleanHumid = parseFloat(humidVal);

            const isTempOut = (!isNaN(cleanTemp) && (cleanTemp < tempMin || cleanTemp > tempMax));
            const isHumidOut = (!isNaN(cleanHumid) && (cleanHumid < humidMin || cleanHumid > humidMax));
            const isPressOut = (!isNaN(cleanPress) && (cleanPress < pressMin || cleanPress > pressMax));

            // Update specific warning badges
            if (isTempOut) {
                $('#warning-temp-' + roomId).removeClass('d-none');
            } else {
                $('#warning-temp-' + roomId).addClass('d-none');
            }

            if (isHumidOut) {
                $('#warning-humid-' + roomId).removeClass('d-none');
            } else {
                $('#warning-humid-' + roomId).addClass('d-none');
            }

            if (isPressOut) {
                $('#warning-pressure-' + roomId).removeClass('d-none');
            } else {
                $('#warning-pressure-' + roomId).addClass('d-none');
            }

            // Update individual values color blinking
            toggleValueWarning($('#room-temp-' + roomId), isTempOut);
            toggleValueWarning($('#room-humid-' + roomId), isHumidOut);
            toggleValueWarning($('#room-pressure-' + roomId), isPressOut);
        }

        function toggleValueWarning(el, isOut) {
            if (isOut) {
                el.addClass('text-danger-blink');
            } else {
                el.removeClass('text-danger-blink');
            }
        }

        function loadEquipmentStatuses() {
            let codes = [];
            $('.equipment-item').each(function() {
                codes.push($(this).data('code'));
            });

            if (codes.length === 0) return;

            $.ajax({
                url: "{{ route('pages.manu_env.equipment.getStatusBatch') }}",
                method: "POST",
                data: {
                    codes: codes,
                    _token: "{{ csrf_token() }}"
                },
                success: function(response) {
                    Object.keys(response).forEach(function(code) {
                        let status = response[code];
                        let item = $('.equipment-item[data-code="' + code + '"]');

                        let calBadge = item.find('.cal-badge');
                        if (status.has_cal === false) {
                            calBadge.hide();
                        } else {
                            calBadge.show();
                            if (status.cal_expired) {
                                calBadge.removeClass('bg-secondary bg-success').addClass('bg-danger').html(
                                    '<i class="fas fa-times-circle"></i> Hiệu chuẩn: Không đạt');
                            } else {
                                calBadge.removeClass('bg-secondary bg-danger').addClass('bg-success').html(
                                    '<i class="fas fa-check-circle"></i> Hiệu chuẩn: Đạt');
                            }
                        }

                        let maintBadge = item.find('.maint-badge');
                        if (status.has_maint === false) {
                            maintBadge.hide();
                        } else {
                            maintBadge.show();
                            if (status.maint_warning === 2) {
                                maintBadge.removeClass('bg-secondary bg-success bg-warning').addClass(
                                    'bg-danger').html(
                                    '<i class="fas fa-wrench"></i> Bảo trì: Không đạt');
                            } else if (status.maint_warning === 1) {
                                maintBadge.removeClass('bg-secondary bg-success bg-danger').addClass(
                                    'bg-warning text-dark').html(
                                    '<i class="fas fa-exclamation-triangle"></i> Bảo trì: Sắp đến hạn');
                            } else {
                                maintBadge.removeClass('bg-secondary bg-danger bg-warning').addClass(
                                    'bg-success').html(
                                    '<i class="fas fa-check-circle"></i> Bảo trì: Đạt');
                            }
                        }
                    });
                }
            });
        }

        $(document).ready(function() {
            loadEquipmentStatuses();

            // Calibration Modal
            $(document).on('click', '.cal-badge', function() {
                const item = $(this).closest('.equipment-item');
                const code = item.data('code');
                $('#calLabelModal').modal('show');
                $('#calLabelContent').hide();
                $('#calLabelLoading').show();

                $.ajax({
                    url: "{{ route('pages.manu_env.equipment.getCalibrationLabel', ':code') }}".replace(':code', code),
                    type: "GET",
                    success: function(res) {
                        $('#calLabelLoading').hide();
                        if (res.success) {
                            $('#lblEquipName').text(res.parent.name);
                            $('#lblEquipCode').text(res.parent.code);

                            let hasExpired = false;
                            let tbody = '';
                            if (res.children.length === 0) {
                                tbody = '<tr><td colspan="5" class="text-center">Không có thiết bị con</td></tr>';
                            } else {
                                res.children.forEach((child, idx) => {
                                    if (child.is_expired) hasExpired = true;
                                    let rowClass = child.is_expired ? 'class="table-danger text-danger font-weight-bold"' : '';
                                    tbody += `
                                        <tr ${rowClass}>
                                            <td>${idx + 1}.</td>
                                            <td>${child.id}</td>
                                            <td>${child.name}</td>
                                            <td>${child.calibrated_on}</td>
                                            <td>${child.exp_date}</td>
                                        </tr>
                                    `;
                                });
                            }

                            const headerTitle = $('#calLabelModal .modal-header');
                            if (hasExpired) {
                                headerTitle.removeClass('bg-info').css('background-color', '').addClass('bg-danger');
                            } else {
                                headerTitle.removeClass('bg-info bg-danger').css('background-color', '#8bc34a');
                            }

                            $('#calLabelTableBody').html(tbody);
                            $('#calLabelContent').show();
                        } else {
                            Swal.fire('Thông báo', res.message || 'Có lỗi xảy ra', 'info');
                            $('#calLabelModal').modal('hide');
                        }
                    },
                    error: function() {
                        $('#calLabelLoading').hide();
                        Swal.fire('Lỗi', 'Không thể kết nối đến máy chủ', 'error');
                        $('#calLabelModal').modal('hide');
                    }
                });
            });

            // Maintenance Modal
            $(document).on('click', '.maint-badge', function() {
                const item = $(this).closest('.equipment-item');
                const code = item.data('code');
                $('#maintLabelModal').modal('show');
                $('#maintLabelContent').hide();
                $('#maintLabelLoading').show();

                $.ajax({
                    url: "{{ route('pages.manu_env.equipment.getMaintenanceLabel', ':code') }}".replace(':code', code),
                    type: "GET",
                    success: function(res) {
                        $('#maintLabelLoading').hide();
                        if (res.success) {
                            $('#lblMaintEquipName').text(res.parent.name);
                            $('#lblMaintEquipCode').text(res.parent.code);

                            let tbody = '';
                            if (res.children.length === 0) {
                                tbody = '<tr><td colspan="6" class="text-center">Không có thiết bị con</td></tr>';
                            } else {
                                res.children.forEach((child, idx) => {
                                    let rowClass = '';
                                    if (child.warning_level === 2) {
                                        rowClass = 'class="table-danger text-danger font-weight-bold"';
                                    } else if (child.warning_level === 1) {
                                        rowClass = 'style="background-color: #fff3cd; color: #856404; font-weight: bold;"';
                                    }

                                    tbody += `
                                        <tr ${rowClass}>
                                            <td>${idx + 1}.</td>
                                            <td>${child.id}</td>
                                            <td>${child.name}</td>
                                            <td>${child.cycle}</td>
                                            <td>${child.calibrated_on}</td>
                                            <td>${child.exp_date}</td>
                                        </tr>
                                    `;
                                });
                            }

                            const headerTitle = $('#maintLabelModal .modal-header');
                            headerTitle.removeClass('bg-primary bg-danger bg-warning').css('background-color', '');

                            if (res.header_warning === 2) {
                                headerTitle.addClass('bg-danger').css('color', '#fff');
                            } else if (res.header_warning === 1) {
                                headerTitle.css({ 'background-color': '#fd7e14', 'color': '#fff' }); 
                            } else {
                                headerTitle.css({ 'background-color': '#8bc34a', 'color': '#000' }); 
                            }

                            $('#maintLabelTableBody').html(tbody);
                            $('#maintLabelContent').show();
                        } else {
                            Swal.fire('Thông báo', res.message || 'Có lỗi xảy ra', 'info');
                            $('#maintLabelModal').modal('hide');
                        }
                    },
                    error: function() {
                        $('#maintLabelLoading').hide();
                        Swal.fire('Lỗi', 'Không thể kết nối đến máy chủ', 'error');
                        $('#maintLabelModal').modal('hide');
                    }
                });
            });

            window.showLabel = function(type, id) {
                Swal.fire({
                    title: 'Đang tải...',
                    text: 'Vui lòng chờ trong giây lát',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                $.ajax({
                    url: "{{ route('pages.ebmr.getLogbookLabel') }}",
                    type: 'GET',
                    data: {
                        type: type,
                        id: id
                    },
                    success: function(response) {
                        Swal.close();
                        if (response.success) {
                            const data = response.data;
                            const labelTitle = type === 'room' ? 'NHÃN PHÒNG' : 'NHÃN THIẾT BỊ';
                            const labelSub = type === 'room' ? 'ROOM LABEL' : 'EQUIPMENT LABEL';
                            
                            // Check if state is cleaning or something else (for now assume cleaning implies "to be cleaned")
                            // Usually, if it's currently producing or dirty, it needs cleaning.
                            // If it's 'cleaned', it shows the green modal.
                            if (data.current_status !== 'cleaned') {
                                $('#cleanRequiredLabelType').text(labelTitle);
                                $('#cleanRequiredLabelType').next('small').text(labelSub);
                                
                                $('#lblReqName').text(data.entity_name);
                                $('#lblReqCode').text(data.entity_code);
                                
                                $('#reqLevel1').prop('checked', data.clean_level === 'level_1');
                                $('#reqLevel2').prop('checked', data.clean_level === 'level_2');
                                $('#reqReClean').prop('checked', data.clean_level === 're_cleaning');
                                
                                $('#reqFinishedDate').text(data.end_time || '-');
                                $('#reqCleanBefore').text(data.to_be_cleaned_before || '-');
                                $('#reqDoneBy').text(data.done_by || '-');

                                $('#modalCleanRequired').modal('show');
                            } else {
                                $('#cleanedLabelType').text(labelTitle);
                                $('#cleanedLabelType').next('small').text(labelSub);

                                $('#lblCldName').text(data.entity_name);
                                $('#lblCldCode').text(data.entity_code);

                                $('#cldLevel1').prop('checked', data.clean_level === 'level_1');
                                $('#cldLevel2').prop('checked', data.clean_level === 'level_2');
                                $('#cldReClean').prop('checked', data.clean_level === 're_cleaning');

                                $('#cldFinishedDate').text(data.end_time || '-');
                                $('#cldValidUntil').text(data.clean_expiry_date || '-');
                                $('#cldDoneBy').text(data.done_by || '-');
                                $('#cldCheckedBy').text(data.checked_by || '-');
                                
                                $('#cldNextProduct').text(data.next_product_name || '-');
                                $('#cldNextBatch').text(data.next_batch_number || '-');
                                $('#cldAttachedBy').text(data.attached_by || '-');

                                $('#modalCleaned').modal('show');
                            }
                        } else {
                            Swal.fire('Lỗi!', response.message, 'error');
                        }
                    },
                    error: function() {
                        Swal.close();
                        Swal.fire('Lỗi!', 'Không thể tải dữ liệu nhãn', 'error');
                    }
                });
            };
        });

        // ── BẮT ĐẦU SẢN XUẤT / TIẾP TỤC DỌN QUANG ───────────────────────────
        // Nút chỉ render khi server đã xác nhận đủ điều kiện vệ sinh, hồ sơ đã phân
        // phối, user được phân công; endpoint startProduction kiểm tra lại toàn bộ
        // trước khi chốt phiên. mode=start hỏi xác nhận trước (thao tác lần đầu, có
        // thể đổi ý); mode=resume gọi thẳng (chỉ là quay lại dọn quang dở dang).
        $(document).on('click', '.js-start-production', function() {
            const btn = $(this);
            const mode = btn.data('mode') || 'start';
            const distributionId = btn.data('distribution-id');

            if (mode === 'resume') {
                callStartProduction(distributionId, 'Đang mở trang dọn quang...');
                return;
            }

            // Chặn NGAY nếu phòng/thiết bị chưa được thiết kế quy trình dọn quang — không mở
            // dialog xác nhận "đủ điều kiện" xanh gây hiểu lầm. (Server vẫn kiểm tra lại lần nữa
            // trong startProduction để phòng trường hợp dữ liệu đổi giữa lúc render và lúc bấm.)
            const clearanceMissing = btn.data('clearance-missing');
            if (Array.isArray(clearanceMissing) && clearanceMissing.length > 0) {
                warnClearanceProcessMissing(clearanceMissing);
                return;
            }

            confirmStartProduction(
                distributionId,
                String(btn.data('batch') ?? ''),
                String(btn.data('product') ?? ''),
                String(btn.data('section') ?? '')
            );
        });

        function escapeHtml(str) {
            return String(str).replace(/[&<>"']/g, function(c) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
            });
        }

        function warnClearanceProcessMissing(reasons) {
            const items = reasons.map(r => `<li>${escapeHtml(r)}</li>`).join('');
            Swal.fire({
                title: 'Chưa thể bắt đầu sản xuất',
                html: `
                    <div class="text-start" style="font-size: 0.9rem;">
                        <p class="mb-2">Phòng hoặc thiết bị chưa được thiết kế <strong>quy trình dọn quang</strong>. Cần bổ sung trước khi bắt đầu sản xuất:</p>
                        <ul class="mb-2 ps-3 text-danger">${items}</ul>
                        <div class="small text-muted"><i class="fas fa-info-circle me-1"></i>Thiết kế tại <strong>Môi Trường &gt; Dọn Quang Phòng</strong> và <strong>Dọn Quang Thiết Bị</strong>.</div>
                    </div>`,
                icon: 'warning',
                confirmButtonText: 'Đã hiểu',
                confirmButtonColor: '#003A4F'
            });
        }

        function confirmStartProduction(distributionId, batchNumber, productName, sectionLabel) {
            Swal.fire({
                title: 'Bắt đầu sản xuất?',
                html: `
                    <div class="text-start" style="font-size: 0.9rem;">
                        <div class="mb-3 p-3 bg-light rounded-3">
                            <div class="mb-1"><strong>Sản phẩm:</strong> ${escapeHtml(productName)}</div>
                            <div class="mb-1"><strong>Số lô:</strong> <span class="font-monospace">${escapeHtml(batchNumber)}</span></div>
                            <div><strong>Công đoạn:</strong> ${escapeHtml(sectionLabel)}</div>
                        </div>
                        <div class="mb-1"><i class="fas fa-check-circle text-success me-2"></i>Phòng đã vệ sinh</div>
                        <div class="mb-1"><i class="fas fa-check-circle text-success me-2"></i>Thiết bị trong phòng đã vệ sinh</div>
                        <div class="mb-1"><i class="fas fa-check-circle text-success me-2"></i>Hồ sơ đã phân phối, sẵn sàng ghi chép</div>
                        <div class="mt-3 small text-muted"><i class="fas fa-broom me-1"></i>Sau khi bắt đầu, bạn cần hoàn tất <strong>dọn quang phòng &amp; thiết bị</strong> trước khi mở trang ghi chép hồ sơ.</div>
                    </div>`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-play me-1"></i> Bắt đầu sản xuất',
                cancelButtonText: 'Hủy bỏ',
                confirmButtonColor: '#15803d',
                focusCancel: true
            }).then((result) => {
                // SweetAlert2 của layout là v9 — chưa có result.isConfirmed (v10+), phải dùng result.value
                if (!result.value) return;
                callStartProduction(distributionId, 'Đang khởi tạo phiên sản xuất...');
            });
        }

        function callStartProduction(distributionId, loadingTitle) {
            Swal.fire({
                title: loadingTitle,
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            $.ajax({
                url: "{{ route('pages.ebmr.startProduction') }}",
                method: 'POST',
                dataType: 'json', // ép jQuery JSON.parse — tránh trường hợp Content-Type không phải
                                  // application/json khiến res là chuỗi, res.success = undefined và
                                  // rơi nhầm vào nhánh cảnh báo thay vì điều hướng sang trang dọn quang.
                data: {
                    distribution_id: distributionId,
                    _token: "{{ csrf_token() }}"
                },
                success: function(res) {
                    // Phòng thủ thêm: nếu vì lý do nào đó res vẫn về dạng chuỗi thì tự parse.
                    if (typeof res === 'string') {
                        try { res = JSON.parse(res); } catch (e) { res = {}; }
                    }
                    if (res && res.success && res.redirect_url) {
                        window.location.href = res.redirect_url;
                    } else {
                        // Điều kiện đổi giữa chừng (vd nhãn vệ sinh vừa hết hạn) — tải
                        // lại trang để card phản ánh đúng trạng thái mới nhất.
                        Swal.fire({
                            icon: 'warning',
                            title: 'Không thể tiếp tục',
                            text: (res && res.message) || 'Có lỗi xảy ra',
                            confirmButtonColor: '#003A4F'
                        }).then(() => window.location.reload());
                    }
                },
                error: function(xhr) {
                    // 419 = CSRF hết hạn (trang mở lâu). Tách riêng để người dùng biết cần tải lại.
                    if (xhr && xhr.status === 419) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Phiên làm việc đã hết hạn',
                            text: 'Vui lòng tải lại trang rồi thử lại.',
                            confirmButtonColor: '#003A4F'
                        }).then(() => window.location.reload());
                        return;
                    }
                    Swal.fire('Lỗi', 'Không thể kết nối đến máy chủ', 'error');
                }
            });
        }

        // ── KẾT THÚC SẢN XUẤT ────────────────────────────────────────────────
        // Chốt mốc dừng của phiên sản xuất — dùng để "Lịch sử môi trường sản xuất"
        // biết dừng ghi nhận ở đâu, giải phóng phòng + thiết bị sang trạng thái "Cần vệ
        // sinh" cho lô/công đoạn kế tiếp, VÀ khoá ghi chép cho riêng công đoạn này (xem
        // computeIsReadOnly()) — card công đoạn này sẽ không còn hiện ở phòng nữa, chuyển
        // sang trang "Hồ Sơ Hoàn Thành".
        $(document).on('click', '.js-end-production', function() {
            const btn = $(this);
            const distributionId = btn.data('distribution-id');
            const batchNumber = String(btn.data('batch') ?? '');

            Swal.fire({
                title: 'Kết thúc sản xuất?',
                html: `
                    <div class="text-start" style="font-size: 0.9rem;">
                        <div class="mb-3 p-3 bg-light rounded-3">
                            <div><strong>Số lô:</strong> <span class="font-monospace">${escapeHtml(batchNumber)}</span></div>
                        </div>
                        <div class="small text-muted"><i class="fas fa-info-circle me-1"></i>Phòng &amp; thiết bị sẽ chuyển sang trạng thái "Cần vệ sinh" cho lô kế tiếp. Công đoạn này sẽ bị khoá, không thể ghi chép thêm và sẽ chuyển sang trang "Hồ Sơ Hoàn Thành".</div>
                    </div>`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-flag-checkered me-1"></i> Kết thúc sản xuất',
                cancelButtonText: 'Hủy bỏ',
                confirmButtonColor: '#dc3545',
                focusCancel: true
            }).then((result) => {
                // SweetAlert2 của layout là v9 — chưa có result.isConfirmed (v10+), phải dùng result.value
                if (!result.value) return;

                Swal.fire({ title: 'Đang kết thúc sản xuất...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

                $.ajax({
                    url: "{{ route('pages.ebmr.endProduction') }}",
                    method: 'POST',
                    data: {
                        distribution_id: distributionId,
                        _token: "{{ csrf_token() }}"
                    },
                    success: function(res) {
                        if (res.success) {
                            Swal.fire({ icon: 'success', title: 'Đã kết thúc sản xuất!', text: res.message, timer: 1800, showConfirmButton: false })
                                .then(() => window.location.reload());
                        } else if (res.missing_variables && res.missing_variables.length) {
                            // Còn biến số chưa nhập & chưa gạch chéo N/A — liệt kê rõ cho người dùng biết
                            // cần quay lại phần ghi chép để hoàn tất trước khi kết thúc sản xuất.
                            const items = res.missing_variables.map(v =>
                                `<li>${escapeHtml(v.label || '')}${v.section ? ` <span class="text-muted">(${escapeHtml(v.section)})</span>` : ''}</li>`
                            ).join('');
                            Swal.fire({
                                icon: 'warning',
                                title: 'Chưa thể kết thúc sản xuất',
                                html: `
                                    <div class="text-start" style="font-size: 0.85rem;">
                                        <div class="mb-2">${escapeHtml(res.message)}</div>
                                        <ul class="ps-3 mb-0" style="max-height: 260px; overflow-y: auto;">${items}</ul>
                                    </div>`,
                                confirmButtonColor: '#003A4F'
                            });
                        } else {
                            Swal.fire('Không thể kết thúc', res.message, 'warning');
                        }
                    },
                    error: function() {
                        Swal.fire('Lỗi', 'Không thể kết nối đến máy chủ', 'error');
                    }
                });
            });
        });

        function openStartCleaningModal(roomId, type, roomName) {
            $('#startCleaningRoomName').text(roomName);
            $('#startCleaningType').val(type);
            
            let actionUrl = "{{ route('pages.manu_env.cleaning_process.campaign.open', ['room_id' => ':roomId']) }}";
            actionUrl = actionUrl.replace(':roomId', roomId);
            $('#startCleaningForm').attr('action', actionUrl);
            
            if (!$('#startCleaningEmployees').hasClass("select2-hidden-accessible")) {
                $('#startCleaningEmployees').select2({
                    placeholder: "Chọn người thực hiện",
                    allowClear: true,
                    dropdownParent: $('#startCleaningModal')
                });
            }
            
            $('#startCleaningModal').modal('show');
        }
    </script>

    <!-- Start Cleaning Modal -->
    <div class="modal fade" id="startCleaningModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content border-0 shadow">
                <form id="startCleaningForm" method="GET" action="">
                    <input type="hidden" name="type" id="startCleaningType" value="">
                    <div class="modal-header bg-navy text-white">
                        <h5 class="modal-title"><i class="fas fa-broom me-2 text-warning"></i> Bắt đầu Vệ sinh</h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-3">Xác nhận bắt đầu quy trình vệ sinh cho phòng: <br>
                        <strong id="startCleaningRoomName" class="text-navy fs-5"></strong></p>
                        
                        <div class="form-group">
                            <label class="fw-bold">Người thực hiện:</label>
                            <select class="form-control select2" name="employee_ids[]" id="startCleaningEmployees" multiple="multiple" style="width: 100%;" required>
                                @php $currentUserId = session('user')['id'] ?? session('user.id') ?? 1; @endphp
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" {{ $user->id == $currentUserId ? 'selected' : '' }}>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted mt-1 d-block"><i class="fas fa-info-circle me-1"></i> Có thể chọn thêm người thực hiện cùng.</small>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy bỏ</button>
                        <button type="submit" class="btn btn-primary fw-bold"><i class="fas fa-check me-1"></i> Xác nhận Bắt đầu</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
