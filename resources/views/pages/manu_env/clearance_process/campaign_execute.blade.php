@extends('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection

@section('mainContent')
    <style>
        .text-navy {
            color: #003A4F !important;
        }

        .bg-navy {
            background-color: #003A4F !important;
        }

        /* ── LAYOUT ─────────────────────────────── */
        .campaign-wrapper {
            display: flex;
            height: calc(100vh - 56px);
            overflow: hidden;
        }

        /* ── SIDEBAR ─────────────────────────────── */
        .campaign-sidebar {
            width: 260px;
            min-width: 260px;
            background: #fff;
            border-right: 1px solid #e8ecf0;
            display: flex;
            flex-direction: column;
            box-shadow: 2px 0 12px rgba(0, 0, 0, 0.05);
            z-index: 2;
        }

        .sidebar-header {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            padding: 20px 16px 16px;
            position: relative;
        }

        .sidebar-header::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            right: 0;
            height: 8px;
            background: linear-gradient(to bottom, rgba(0, 0, 0, 0.04), transparent);
        }

        .sidebar-progress {
            padding: 14px 16px 10px;
            border-bottom: 1px solid #f0f2f5;
        }

        .sidebar-steps {
            flex: 1;
            overflow-y: auto;
            padding: 8px 0;
        }

        .step-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 12px 16px;
            cursor: pointer;
            transition: background 0.2s;
            border-left: 3px solid transparent;
        }

        .step-item:hover {
            background: #fafafa;
        }

        .step-item.active {
            background: rgba(245, 158, 11, 0.08);
            border-left-color: #f59e0b;
        }

        .step-item.done {
            opacity: 0.75;
        }

        .step-circle {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #e8ecf0;
            color: #64748b;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 700;
            flex-shrink: 0;
            transition: all 0.3s;
        }

        .step-item.active .step-circle {
            background: #f59e0b;
            color: #fff;
            box-shadow: 0 4px 10px rgba(245, 158, 11, 0.4);
        }

        .step-item.done .step-circle {
            background: #22c55e;
            color: #fff;
        }

        .step-label {
            font-size: 0.8rem;
            font-weight: 500;
            color: #374151;
            line-height: 1.35;
            padding-top: 6px;
        }

        /* ── MAIN CONTENT ──────────────────────── */
        .campaign-main {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            background: #f8fafc;
        }

        .campaign-topbar {
            background: #fff;
            border-bottom: 1px solid #e8ecf0;
            padding: 14px 28px;
            display: flex;
            align-items: center;
            gap: 12px;
            flex-shrink: 0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }

        .campaign-content {
            flex: 1;
            overflow-y: auto;
            padding: 28px;
        }

        .campaign-footer {
            background: #fff;
            border-top: 1px solid #e8ecf0;
            padding: 16px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-shrink: 0;
            box-shadow: 0 -2px 8px rgba(0, 0, 0, 0.04);
        }

        /* ── STEP CONTENT CARD ─────────────────── */
        .step-content-card {
            background: #fff;
            border-radius: 16px;
            padding: 28px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
            margin-bottom: 20px;
        }

        .step-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: #fff;
            font-weight: 700;
            font-size: 0.85rem;
            padding: 6px 16px;
            border-radius: 999px;
            margin-bottom: 16px;
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
        }

        .step-done-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(34, 197, 94, 0.1);
            color: #16a34a;
            font-size: 0.78rem;
            font-weight: 600;
            padding: 4px 12px;
            border-radius: 999px;
            border: 1px solid rgba(34, 197, 94, 0.25);
            margin-left: 10px;
        }

        .content-section {
            background: #f8fafc;
            border-radius: 10px;
            padding: 18px 20px;
            margin-bottom: 16px;
            border: 1px solid #e8ecf0;
        }

        .content-section img {
            max-width: 100%;
            height: auto !important;
            border-radius: 6px;
        }

        .standard-section {
            background: #eff6ff;
            border-left: 4px solid #3b82f6;
            border-radius: 0 10px 10px 0;
            padding: 16px 20px;
            margin-bottom: 16px;
        }

        .note-area {
            background: #fff;
            border: 2px solid #e8ecf0;
            border-radius: 10px;
            transition: border-color 0.2s;
        }

        .note-area:focus {
            border-color: #f59e0b;
            outline: none;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.12);
        }

        /* ── BUTTONS ─────────────────────────── */
        .btn-complete-step {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: #fff;
            border: none;
            padding: 10px 24px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 0.85rem;
            box-shadow: 0 4px 14px rgba(245, 158, 11, 0.35);
            transition: all 0.25s;
            cursor: pointer;
        }

        .btn-complete-step:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(245, 158, 11, 0.5);
            color: #fff;
        }

        .btn-complete-step:disabled {
            opacity: 0.5;
            transform: none;
            cursor: not-allowed;
        }

        .btn-finish {
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: #fff;
            border: none;
            padding: 10px 24px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 0.85rem;
            box-shadow: 0 4px 14px rgba(34, 197, 94, 0.35);
            transition: all 0.25s;
            cursor: pointer;
        }

        .btn-finish:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(34, 197, 94, 0.5);
            color: #fff;
        }

        /* ── PROGRESS BAR ─────────────────────── */
        .prog-bar-track {
            height: 6px;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 999px;
            overflow: hidden;
        }

        .prog-bar-fill {
            height: 100%;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 999px;
            transition: width 0.5s ease;
        }
    </style>

    <div class="content-wrapper">
        <div class="campaign-wrapper">

            {{-- ═══ SIDEBAR ═══ --}}
            <aside class="campaign-sidebar">
                <div class="sidebar-header">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div>
                            <div class="text-white fw-bold" style="font-size: 0.9rem; line-height: 1.2;">DỌN QUANG PHÒNG</div>
                            <div class="text-white opacity-75" style="font-size: 0.72rem;">{{ $room->code }} –
                                {{ $room->name }}</div>
                        </div>
                    </div>
                    <div class="text-white opacity-80 mb-2" style="font-size: 0.75rem;">
                        {{ $processList->process_name }} | V.{{ $processList->version }}
                    </div>
                    <div class="prog-bar-track">
                        <div class="prog-bar-fill" id="sidebar-progress-fill"
                            style="width: {{ ($campaignSteps->where('is_done', true)->count() / max($campaignSteps->count(), 1)) * 100 }}%">
                        </div>
                    </div>
                    <div class="text-white opacity-75 mt-1" style="font-size: 0.7rem;">
                        <span id="sidebar-done-count">{{ $campaignSteps->where('is_done', true)->count() }}</span>
                        / {{ $campaignSteps->count() }} bước hoàn thành
                    </div>
                </div>

                <div class="sidebar-steps" id="steps-sidebar">
                    @foreach ($campaignSteps as $idx => $step)
                        <div class="step-item align-items-center {{ $idx === 0 ? 'active' : '' }} {{ $step->is_done ? 'done' : '' }}"
                            id="sidebar-item-{{ $idx }}" onclick="goToStep({{ $idx }})">
                            <div class="step-circle" id="sidebar-circle-{{ $idx }}">
                                @if ($step->is_done)
                                    <i class="fas fa-check" style="font-size: 0.65rem;"></i>
                                @else
                                    {{ $step->step }}
                                @endif
                            </div>
                            <div class="step-label mb-0 pt-0">Bước {{ $step->step }}</div>
                            <div class="ms-auto" id="sidebar-badge-{{ $idx }}">
                                @if ($step->is_done)
                                    <span class="badge {{ $step->is_passed ? 'bg-success' : 'bg-danger' }}"
                                        style="font-size: 0.65rem;">
                                        {{ $step->is_passed ? 'ĐẠT' : 'KHÔNG ĐẠT' }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                @if (isset($equipCampaigns) && $equipCampaigns->count() > 0 && $campaign->status !== 'completed')
                    <div class="p-3 border-top mt-auto" style="background: #f8fafc;">
                        <h6 class="fw-bold text-info mb-3" style="font-size: 0.85rem;">
                            <i class="fas fa-tools me-1"></i> THIẾT BỊ CẦN DỌN QUANG
                        </h6>
                        @foreach ($equipCampaigns as $ec)
                            <div class="bg-white p-2 rounded border mb-2 shadow-sm d-flex flex-column gap-2">
                                <div class="d-flex align-items-center justify-content-between">
                                    <span class="fw-bold text-dark"
                                        style="font-size: 0.8rem;">{{ $ec->equipment_code }}</span>
                                    <span id="badge-equip-{{ $ec->id }}"
                                        class="badge {{ $ec->status === 'completed' ? 'bg-success' : 'bg-warning text-dark' }}"
                                        style="font-size: 0.65rem;">
                                        {{ $ec->status === 'completed' ? 'Hoàn thành' : 'Chưa xong' }}
                                    </span>
                                </div>
                                <div class="text-muted"
                                    style="font-size: 0.75rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    {{ $ec->equipment_name }}
                                </div>
                                <a href="{{ route('pages.manu_env.clearance_process.equip.campaign.open', ['equip_id' => $ec->equipment_id]) }}?campaign_id={{ $ec->id }}&room_campaign_id={{ $campaign->id }}"
                                    target="_blank" class="btn btn-sm btn-outline-info w-100 mt-1"
                                    style="font-size: 0.75rem;"
                                    onclick="this.classList.remove('btn-outline-info'); this.classList.add('btn-info', 'text-white');">
                                    <i class="fas fa-external-link-alt me-1"></i> Thực hiện (Mở tab mới)
                                </a>
                            </div>
                        @endforeach
                    </div>
                @endif
            </aside>

            {{-- ═══ MAIN ═══ --}}
            <div class="campaign-main">

                {{-- Topbar --}}
                <div class="campaign-topbar">
                    <a href="{{ route('pages.ebmr.production') }}"
                        class="btn btn-outline-secondary rounded-pill px-3 py-2 shadow-sm me-2" style="font-size: 0.82rem;">
                        <i class="fas fa-chevron-left me-1"></i> Quay lại
                    </a>
                    <div>
                        <h5 class="mb-0 fw-bold text-navy" style="font-size: 1rem;">
                            <i class="fas fa-broom me-2 text-success"></i>
                            Thực Hiện Dọn Quang – {{ $room->code }} – {{ $room->name }}
                        </h5>
                        <div class="text-muted small">{{ $processList->process_code }} | {{ $processList->process_name }}
                            | Ấn
                            bản V.{{ $processList->version }}</div>
                    </div>
                    <div class="ms-auto d-flex align-items-center gap-3">
                        <button type="button" class="btn btn-outline-info rounded-pill px-3 py-2 shadow-sm"
                            style="font-size: 0.82rem;" data-toggle="modal" data-target="#fullProcessModal">
                            <i class="fas fa-file-alt me-1"></i> Xem toàn bộ quy trình
                        </button>
                        <div class="fw-bold text-navy bg-light px-3 py-2 rounded-pill border" style="font-size: 0.9rem;">
                            Bước <span id="topbar-current-step">1</span> / {{ $campaignSteps->count() }}
                        </div>

                    </div>
                </div>

                {{-- Content --}}
                <div class="campaign-content">
                    @foreach ($campaignSteps as $idx => $step)
                        <div class="step-panel" id="panel-{{ $idx }}"
                            style="{{ $idx !== 0 ? 'display:none;' : '' }}">

                            <div class="step-content-card">
                                <div class="d-flex align-items-center flex-wrap gap-2 mb-4">
                                    <div class="step-badge">
                                        <i class="fas fa-list-check"></i> Bước {{ $step->step }}
                                    </div>
                                    @if ($step->is_done)
                                        <div class="step-done-badge">
                                            <i class="fas fa-check-circle"></i> Đã hoàn thành
                                        </div>
                                    @endif
                                </div>

                                {{-- Nội dung quy trình --}}
                                <div class="mb-2 small text-muted fw-bold text-uppercase"
                                    style="font-size: 0.68rem; letter-spacing: 0.06em;">
                                    <i class="fas fa-clipboard-list me-1 text-success"></i> Nội dung thực hiện
                                </div>
                                <div class="content-section">
                                    {!! $step->content ?? '<span class="text-muted">Không có nội dung</span>' !!}
                                </div>

                                @php $hasStandard = !empty($step->standard) && trim(strip_tags($step->standard)) !== ''; @endphp
                                <div class="row gx-4 mt-3">
                                    @if ($hasStandard)
                                        <div class="col-md-6 mb-4 mb-md-0 d-flex flex-column">
                                            <div class="mb-2 small text-muted fw-bold text-uppercase"
                                                style="font-size: 0.68rem; letter-spacing: 0.06em;">
                                                <i class="fas fa-ruler-combined me-1 text-info"></i> Tiêu chuẩn
                                            </div>
                                            <div class="standard-section flex-grow-1 mb-0">
                                                {!! $step->standard !!}
                                            </div>
                                        </div>
                                    @endif

                                    <div class="{{ $hasStandard ? 'col-md-6' : 'col-12' }}">
                                        @if (!$step->is_done)
                                            <div
                                                class="mb-3 p-3 bg-white border rounded-3 d-flex align-items-center justify-content-between">
                                                <div class="small text-muted fw-bold text-uppercase"
                                                    style="font-size: 0.75rem; letter-spacing: 0.05em;">
                                                    <i class="fas fa-check-double me-1 text-success"></i> Đánh giá kết quả
                                                </div>
                                                <div class="form-check form-switch fs-5 mb-0 d-flex align-items-center">
                                                    <input class="form-check-input mt-0" type="checkbox" role="switch"
                                                        id="is-passed-{{ $idx }}" checked
                                                        onchange="togglePassedLabel({{ $idx }})"
                                                        style="cursor: pointer;">
                                                    <label class="form-check-label fs-6 ms-2 fw-bold text-success"
                                                        for="is-passed-{{ $idx }}"
                                                        id="is-passed-label-{{ $idx }}"
                                                        style="cursor: pointer;">ĐẠT</label>
                                                </div>
                                            </div>

                                            <div id="confirm-info-{{ $idx }}"
                                                class="mb-3 p-3 bg-light rounded-3 border d-flex align-items-center gap-3">
                                                @php
                                                    $username =
                                                        session('user')['userName'] ??
                                                        (session('user')['user_name'] ?? null);
                                                    $userSig = $username
                                                        ? \Illuminate\Support\Facades\DB::table('user_management')
                                                            ->where('userName', $username)
                                                            ->value('signature_image')
                                                        : session('user')['signature_image'] ?? null;
                                                @endphp
                                                @if (!empty($userSig))
                                                    <img src="{{ $userSig }}" alt="Chữ ký"
                                                        style="max-height: 40px; mix-blend-mode: multiply; object-fit: contain;">
                                                @else
                                                    <div class="fw-bold text-navy" style=" font-size: 1.5rem;">
                                                        {{ session('user')['fullName'] ?? (session('user')['user_name'] ?? 'Bạn') }}
                                                    </div>
                                                @endif
                                                <div class="small text-muted border-start ps-3 border-secondary">
                                                    <div><i class="fas fa-user-check me-1 text-success"></i> Xác nhận bởi:
                                                        <strong
                                                            class="text-navy">{{ session('user')['fullName'] ?? (session('user')['user_name'] ?? 'Bạn') }}</strong>
                                                    </div>
                                                    <div><i class="far fa-clock me-1 text-info mt-1"></i> Lúc: <span
                                                            id="confirm-time-{{ $idx }}"></span></div>
                                                </div>
                                            </div>

                                            <div class="mb-2 small text-muted fw-bold text-uppercase"
                                                style="font-size: 0.68rem; letter-spacing: 0.06em;">
                                                <i class="fas fa-pen me-1 text-secondary"></i> Ghi chú kết quả (tuỳ chọn)
                                            </div>
                                            <textarea class="form-control note-area mb-3" id="note-{{ $idx }}" rows="2"
                                                placeholder="Nhập ghi chú kết quả bước này..."></textarea>

                                            <div class="mt-3">
                                                <div class="mb-2 small text-muted fw-bold text-uppercase"
                                                    style="font-size: 0.68rem; letter-spacing: 0.06em;">
                                                    <i class="fas fa-camera me-1 text-secondary"></i> Chụp hình (Tối đa 5
                                                    hình)
                                                </div>
                                                <input type="file" id="file-input-{{ $idx }}" class="d-none"
                                                    multiple accept="image/*"
                                                    onchange="handleFileSelect(event, {{ $idx }})">
                                                <div class="d-flex gap-2 flex-wrap align-items-center">
                                                    <button type="button"
                                                        class="btn btn-primary btn-sm rounded-pill shadow-sm"
                                                        onclick="openCamera({{ $idx }})">
                                                        <i class="fas fa-camera me-1"></i> Chụp hình
                                                    </button>
                                                    <button type="button"
                                                        class="btn btn-outline-secondary btn-sm rounded-pill"
                                                        onclick="document.getElementById('file-input-{{ $idx }}').click()">
                                                        <i class="fas fa-image me-1"></i> Tải ảnh lên
                                                    </button>
                                                    <div id="image-preview-container-{{ $idx }}"
                                                        class="d-flex gap-2 flex-wrap w-100 mt-2"></div>
                                                </div>
                                            </div>
                                        @else
                                            <div class="mb-3 d-flex align-items-center gap-2">
                                                <div class="small text-muted fw-bold text-uppercase"
                                                    style="font-size: 0.68rem;">
                                                    Kết
                                                    quả:</div>
                                                @if ($step->is_passed)
                                                    <span class="badge bg-success"><i class="fas fa-check me-1"></i>
                                                        ĐẠT</span>
                                                @else
                                                    <span class="badge bg-danger"><i class="fas fa-times me-1"></i> KHÔNG
                                                        ĐẠT</span>
                                                @endif
                                            </div>
                                            @if (!empty($step->result_note))
                                                <div class="mb-2 small text-muted fw-bold text-uppercase"
                                                    style="font-size: 0.68rem; letter-spacing: 0.06em;">
                                                    <i class="fas fa-pen me-1 text-secondary"></i> Ghi chú đã lưu
                                                </div>
                                                <div class="p-3 bg-light rounded-3 text-navy small">
                                                    {{ $step->result_note }}</div>
                                            @endif
                                            @if (!empty($step->attached_images))
                                                <div class="mb-2 mt-3 small text-muted fw-bold text-uppercase"
                                                    style="font-size: 0.68rem; letter-spacing: 0.06em;">
                                                    <i class="fas fa-camera me-1 text-secondary"></i> Hình ảnh đính kèm
                                                </div>
                                                <div class="d-flex flex-wrap gap-2">
                                                    @foreach ($step->attached_images as $imgUrl)
                                                        <a href="{{ $imgUrl }}" target="_blank">
                                                            <img src="{{ $imgUrl }}" alt="Attachment"
                                                                class="img-thumbnail"
                                                                style="width: 100px; height: 100px; object-fit: cover;">
                                                        </a>
                                                    @endforeach
                                                </div>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Footer --}}
                <div class="campaign-footer">
                    <button class="btn btn-outline-secondary rounded-pill px-4" id="btn-prev" onclick="prevStep()"
                        disabled>
                        <i class="fas fa-chevron-left me-1"></i> Bước trước
                    </button>

                    <div class="d-flex gap-2">
                        <button class="btn-complete-step" id="btn-complete-step" onclick="completeCurrentStep()">
                            <i class="fas fa-check me-2"></i> Ghi nhận & Bước tiếp
                        </button>
                        <button class="btn-finish d-none" id="btn-finish" onclick="finishCampaign()">
                            <i class="fas fa-flag-checkered me-2"></i> Hoàn thành Dọn Quang
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Modal Xem toàn bộ quy trình --}}
        <div class="modal fade" id="fullProcessModal" tabindex="-1" aria-labelledby="fullProcessModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-dialog-scrollable" style="max-width: 90%;">
                <div class="modal-content">
                    <div class="modal-header bg-navy text-white">
                        <h5 class="modal-title mb-0" id="fullProcessModalLabel">
                            <i class="fas fa-file-alt me-2 text-success"></i> Toàn Bộ Quy Trình Dọn Quang
                        </h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body bg-white p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="text-center align-middle" style="width: 8%;">Bước</th>
                                        <th class="align-middle" style="width: 46%;">Nội dung thực hiện</th>
                                        <th class="align-middle" style="width: 46%;">Tiêu chuẩn</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($campaignSteps as $idx => $step)
                                        <tr>
                                            <td class="text-center align-middle fw-bold fs-5">
                                                {{ $step->step }}<br>
                                                @if ($step->is_done)
                                                    <span class="badge bg-success mt-2" style="font-size: 0.7rem;"><i
                                                            class="fas fa-check"></i> Đã xong</span>
                                                @endif
                                            </td>
                                            <td class="content-section-table bg-white">
                                                {!! $step->content ?? '<span class="text-muted">Không có nội dung</span>' !!}
                                            </td>
                                            <td class="standard-section-table bg-white border-start border-info border-3">
                                                @if (!empty($step->standard) && trim(strip_tags($step->standard)) !== '')
                                                    {!! $step->standard !!}
                                                @else
                                                    <span class="text-muted fst-italic">Không có tiêu chuẩn</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- Khung Ký duyệt quy trình --}}
                        @include('components.signature_block', ['wfType' => 'cleaning', 'type' => 'room', 'listId' => $campaign->process_list_id])
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Camera Modal --}}
        <div class="modal fade" id="cameraModal" tabindex="-1" aria-hidden="true" data-backdrop="static">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-dark text-white">
                        <h5 class="modal-title mb-0"><i class="fas fa-camera me-2 text-info"></i> Chụp hình thực tế</h5>
                        <button type="button" class="close text-white" data-dismiss="modal"
                            onclick="stopCamera()"><span>&times;</span></button>
                    </div>
                    <div class="modal-body bg-black p-0 text-center position-relative">
                        <video id="camera-video" autoplay playsinline
                            style="width: 100%; max-height: 60vh; object-fit: cover;"></video>
                        <canvas id="camera-canvas" class="d-none"></canvas>
                        <div class="position-absolute bottom-0 w-100 p-3"
                            style="background: linear-gradient(transparent, rgba(0,0,0,0.8));">
                            <button type="button" class="btn btn-info btn-lg rounded-circle shadow"
                                style="width: 60px; height: 60px;" onclick="takePicture()">
                                <i class="fas fa-camera fs-3"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endsection

    @section('script')
        <script>
            // ── DATA ────────────────────────────────────────────────────────
            const CAMPAIGN_ID = {{ $campaign->id }};
            const CAMPAIGN_STATUS = '{{ $campaign->status }}';
            const TOTAL_STEPS = {{ $campaignSteps->count() }};
            const CSRF_TOKEN = '{{ csrf_token() }}';

            @php
                $stepsDataArray = $campaignSteps
                    ->map(
                        fn($s) => [
                            'id' => $s->id,
                            'step' => $s->step,
                            'is_done' => (bool) $s->is_done,
                        ],
                    )
                    ->values()
                    ->toArray();
            @endphp
            const stepsData = @json($stepsDataArray);

            @php
                $equipCampaignsDataArray = isset($equipCampaigns)
                    ? $equipCampaigns
                        ->map(
                            fn($ec) => [
                                'id' => $ec->id,
                                'equipment_id' => $ec->equipment_id,
                                'equipment_code' => $ec->equipment_code,
                                'status' => $ec->status,
                            ],
                        )
                        ->values()
                        ->toArray()
                    : [];
            @endphp
            let equipCampaignsData = @json($equipCampaignsDataArray);

            let currentIndex = (() => {
                const i = stepsData.findIndex(s => !s.is_done);
                return i >= 0 ? i : 0;
            })();

            // ── INIT ─────────────────────────────────────────────────────────
            $(document).ready(function() {
                renderStep(currentIndex);
                updateProgress();
                checkFinishButton(true);

                // Poll equipment status every 3 seconds if there are equipments
                if (equipCampaignsData.length > 0) {
                    setInterval(syncEquipStatus, 3000);
                }
            });

            function syncEquipStatus() {
                const hasPending = equipCampaignsData.some(e => e.status !== 'completed');
                if (!hasPending) return;

                $.ajax({
                    url: '/manu_env/clearance-process/room-campaign/' + CAMPAIGN_ID + '/equip-status',
                    method: 'GET',
                    success: function(res) {
                        if (res.success) {
                            res.data.forEach(updated => {
                                const match = equipCampaignsData.find(e => e.id === updated.id);
                                if (match && match.status !== updated.status) {
                                    match.status = updated.status;
                                    // Update UI badge
                                    const badge = $('#badge-equip-' + match.id);
                                    if (updated.status === 'completed') {
                                        badge.removeClass('bg-warning text-dark').addClass('bg-success')
                                            .text('Hoàn thành');
                                    } else {
                                        badge.removeClass('bg-success').addClass('bg-warning text-dark')
                                            .text('Chưa xong');
                                    }
                                }
                            });
                            checkFinishButton(true);
                        }
                    }
                });
            }

            // ── NAVIGATION ───────────────────────────────────────────────────
            function goToStep(idx) {
                if (idx < 0 || idx >= TOTAL_STEPS) return;

                if (CAMPAIGN_STATUS === 'completed') {
                    // Cuộn tới bước tương ứng thay vì chuyển trang
                    const target = $('#panel-' + idx);
                    if (target.length) {
                        const container = $('.campaign-content');
                        container.animate({
                            scrollTop: target.position().top + container.scrollTop() - 20
                        }, 500);
                        
                        // Highlight nhẹ
                        $('.step-panel').removeClass('shadow-lg border border-primary');
                        target.addClass('shadow-lg border border-primary');
                        setTimeout(() => target.removeClass('shadow-lg border border-primary'), 2000);
                    }
                    return;
                }

                currentIndex = idx;
                renderStep(idx);
            }

            function prevStep() {
                if (currentIndex > 0) goToStep(currentIndex - 1);
            }

            function renderStep(idx) {
                if (CAMPAIGN_STATUS === 'completed') {
                    // Show all panels
                    $('.step-panel').show().addClass('mb-4');
                    $('.campaign-footer').hide(); // Hide footer buttons
                    $('#topbar-current-step').parent().html('<i class="fas fa-check-circle text-success me-1"></i> Toàn bộ quy trình');
                    return;
                }

                // Hide all panels
                $('.step-panel').hide();
                $('#panel-' + idx).show();

                // Cập nhật số bước trên topbar
                const step = stepsData[idx];
                $('#topbar-current-step').text(step.step);

                // Sidebar active
                $('.step-item').removeClass('active');
                $('#sidebar-item-' + idx).addClass('active');

                // Prev button
                $('#btn-prev').prop('disabled', idx === 0);

                // Complete / Finish button visibility
                if (step.is_done) {
                    $('#btn-complete-step').addClass('d-none');
                    $('#btn-finish').addClass('d-none');
                } else if (idx === TOTAL_STEPS - 1) {
                    // Last step & not done
                    $('#btn-complete-step').removeClass('d-none').html(
                        '<i class="fas fa-check me-2"></i> Ghi nhận & Hoàn thành');
                    $('#btn-finish').addClass('d-none');
                } else {
                    $('#btn-complete-step').removeClass('d-none').html(
                        '<i class="fas fa-check me-2"></i> Ghi nhận & Bước tiếp');
                    $('#btn-finish').addClass('d-none');
                }

                // If all done, show only finish
                checkFinishButton();
            }

            function checkFinishButton(silent = true) {
                if (CAMPAIGN_STATUS === 'completed') {
                    $('#btn-complete-step').addClass('d-none');
                    $('#btn-finish').addClass('d-none');
                    return;
                }

                const allStepsDone = stepsData.every(s => s.is_done);
                const allEquipDone = equipCampaignsData.length === 0 || equipCampaignsData.every(e => e.status === 'completed');

                if (allStepsDone && allEquipDone) {
                    $('#btn-complete-step').addClass('d-none');
                    $('#btn-finish').removeClass('d-none');
                } else if (allStepsDone && !allEquipDone) {
                    $('#btn-complete-step').addClass('d-none');
                    $('#btn-finish').addClass('d-none');
                    if (!silent) {
                        const pending = equipCampaignsData.filter(e => e.status !== 'completed').map(e => e.equipment_code)
                            .join(
                                ', ');
                        Swal.fire({
                            icon: 'warning',
                            title: 'Còn thiết bị chưa dọn quang xong!',
                            html: `Thiết bị sau cần hoàn thành trước khi kết thúc phòng:<br><strong class="text-danger">${pending}</strong>`,
                            confirmButtonText: 'OK'
                        });
                    }
                }
            }

            // ── COMPLETE STEP ────────────────────────────────────────────────
            function togglePassedLabel(idx) {
                const isChecked = $('#is-passed-' + idx).is(':checked');
                const label = $('#is-passed-label-' + idx);
                const infoBox = $('#confirm-info-' + idx);
                const timeSpan = $('#confirm-time-' + idx);

                if (isChecked) {
                    label.text('ĐẠT').removeClass('text-danger').addClass('text-success');
                    infoBox.removeClass('d-none').addClass('d-flex');
                    const now = new Date();
                    timeSpan.text(now.toLocaleDateString('en-GB') + ' ' + now.toLocaleTimeString('en-GB', {
                        hour12: false
                    }));
                } else {
                    label.text('KHÔNG ĐẠT').removeClass('text-success').addClass('text-danger');
                    infoBox.addClass('d-none').removeClass('d-flex');
                }
            }

            // ── CAMERA & FILE UPLOAD ─────────────────────────────────────────
            let cameraStream = null;
            let currentCameraIdx = null;
            const selectedFiles = {};

            function openCamera(idx) {
                currentCameraIdx = idx;
                $('#cameraModal').modal('show');
                navigator.mediaDevices.getUserMedia({
                        video: {
                            facingMode: 'environment'
                        }
                    })
                    .then(stream => {
                        cameraStream = stream;
                        document.getElementById('camera-video').srcObject = stream;
                    })
                    .catch(err => {
                        console.error("Camera error:", err);
                        Swal.fire('Lỗi', 'Không thể truy cập camera. Vui lòng kiểm tra quyền.', 'error');
                        $('#cameraModal').modal('hide');
                    });
            }

            function stopCamera() {
                if (cameraStream) {
                    cameraStream.getTracks().forEach(track => track.stop());
                    cameraStream = null;
                }
            }

            $('#cameraModal').on('hidden.bs.modal', function() {
                stopCamera();
            });

            function takePicture() {
                const video = document.getElementById('camera-video');
                const canvas = document.getElementById('camera-canvas');
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                canvas.getContext('2d').drawImage(video, 0, 0);

                canvas.toBlob(blob => {
                    const file = new File([blob], `cam_capture_${Date.now()}.jpg`, {
                        type: 'image/jpeg'
                    });
                    addFileToState(currentCameraIdx, file);
                    $('#cameraModal').modal('hide');
                }, 'image/jpeg', 0.85);
            }

            function addFileToState(idx, file) {
                if (!selectedFiles[idx]) selectedFiles[idx] = [];
                if (selectedFiles[idx].length >= 5) {
                    Swal.fire('Lỗi', 'Chỉ được đính kèm tối đa 5 hình ảnh cho mỗi bước.', 'warning');
                    return;
                }
                selectedFiles[idx].push(file);
                renderImagePreviews(idx);
                syncFileInput(idx);
            }

            function handleFileSelect(event, idx) {
                const files = event.target.files;
                if (!selectedFiles[idx]) selectedFiles[idx] = [];

                for (let i = 0; i < files.length; i++) {
                    if (selectedFiles[idx].length >= 5) {
                        Swal.fire('Lỗi', 'Chỉ được đính kèm tối đa 5 hình ảnh cho mỗi bước.', 'warning');
                        break;
                    }
                    selectedFiles[idx].push(files[i]);
                }
                renderImagePreviews(idx);
                syncFileInput(idx);
                event.target.value = '';
            }

            function removeFile(idx, fileIndex) {
                selectedFiles[idx].splice(fileIndex, 1);
                renderImagePreviews(idx);
                syncFileInput(idx);
            }

            function syncFileInput(idx) {
                const dt = new DataTransfer();
                if (selectedFiles[idx]) {
                    selectedFiles[idx].forEach(file => dt.items.add(file));
                }
                document.getElementById('file-input-' + idx).files = dt.files;
            }

            function renderImagePreviews(idx) {
                const container = $('#image-preview-container-' + idx);
                container.empty();
                const files = selectedFiles[idx] || [];

                files.forEach((file, fileIndex) => {
                    const url = URL.createObjectURL(file);
                    const thumb = `
                    <div class="position-relative" style="width: 80px; height: 80px;">
                        <img src="${url}" class="img-thumbnail" style="width: 100%; height: 100%; object-fit: cover;">
                        <button type="button" class="btn btn-danger btn-sm rounded-circle position-absolute" 
                            style="top: -5px; right: -5px; width: 20px; height: 20px; padding: 0; line-height: 1;"
                            onclick="removeFile(${idx}, ${fileIndex})">
                            <i class="fas fa-times" style="font-size: 0.6rem;"></i>
                        </button>
                    </div>
                `;
                    container.append(thumb);
                });
            }

            function completeCurrentStep() {
                const step = stepsData[currentIndex];
                if (!step || step.is_done) return;

                const note = $('#note-' + currentIndex).val() || '';
                const isPassed = $('#is-passed-' + currentIndex).length ? $('#is-passed-' + currentIndex).is(':checked') : true;

                const formData = new FormData();
                formData.append('_token', CSRF_TOKEN);
                formData.append('result_note', note);
                formData.append('is_passed', isPassed);

                const fileInput = document.getElementById('file-input-' + currentIndex);
                if (fileInput && fileInput.files.length > 0) {
                    for (let i = 0; i < fileInput.files.length; i++) {
                        formData.append('images[]', fileInput.files[i]);
                    }
                }

                $('#btn-complete-step').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i> Đang lưu...');

                $.ajax({
                    url: '/manu_env/clearance-process/campaign/' + CAMPAIGN_ID + '/step/' + step.id + '/complete',
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(res) {
                        if (res.success) {
                            // Mark done in local data
                            stepsData[currentIndex].is_done = true;

                            // Update sidebar circle
                            const circle = $('#sidebar-circle-' + currentIndex);
                            circle.html('<i class="fas fa-check" style="font-size:0.65rem;"></i>');
                            $('#sidebar-item-' + currentIndex).addClass('done');

                            // Update sidebar badge
                            const badgeHtml = isPassed ?
                                '<span class="badge bg-success" style="font-size: 0.65rem;">ĐẠT</span>' :
                                '<span class="badge bg-danger" style="font-size: 0.65rem;">KHÔNG ĐẠT</span>';
                            $('#sidebar-badge-' + currentIndex).html(badgeHtml);

                            updateProgress(res.done_steps, res.total_steps);

                            // Move to next undone step, or stay if all done
                            const nextIdx = stepsData.findIndex((s, i) => i > currentIndex && !s.is_done);
                            if (nextIdx >= 0) {
                                goToStep(nextIdx);
                            } else {
                                // All steps done
                                checkFinishButton(false);
                                renderStep(currentIndex);

                                // Tự động gọi màn hình hoàn thành chiến dịch nếu đây là bước cuối cùng vừa làm xong
                                finishCampaign();
                            }
                        } else {
                            Swal.fire('Lỗi', res.message, 'error');
                            $('#btn-complete-step').prop('disabled', false)
                                .html('<i class="fas fa-check me-2"></i> Ghi nhận & Bước tiếp');
                        }
                    },
                    error: function() {
                        Swal.fire('Lỗi', 'Không thể kết nối máy chủ', 'error');
                        $('#btn-complete-step').prop('disabled', false)
                            .html('<i class="fas fa-check me-2"></i> Ghi nhận & Bước tiếp');
                    }
                });
            }

            // ── PROGRESS ─────────────────────────────────────────────────────
            function updateProgress(done, total) {
                done = done ?? stepsData.filter(s => s.is_done).length;
                total = total ?? TOTAL_STEPS;
                const pct = total > 0 ? Math.round(done / total * 100) : 0;
                $('#sidebar-progress-fill').css('width', pct + '%');
                $('#sidebar-done-count').text(done);
            }

            // ── FINISH CAMPAIGN ──────────────────────────────────────────────
            function finishCampaign() {
                Swal.fire({
                    icon: 'question',
                    title: 'Xác nhận hoàn thành dọn quang?',
                    html: 'Sau khi xác nhận, trạng thái phòng <strong>{{ $room->code }}</strong> sẽ được cập nhật thành <span class="text-success fw-bold">Đã dọn quang</span>.',
                    showCancelButton: true,
                    confirmButtonColor: '#22c55e',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<i class="fas fa-flag-checkered me-1"></i> Hoàn thành',
                    cancelButtonText: 'Kiểm tra lại'
                }).then(function(result) {
                    console.log(result);
                    if (!result.value) return;

                    $.ajax({
                        url: '/manu_env/clearance-process/campaign/' + CAMPAIGN_ID + '/complete',
                        method: 'POST',
                        data: {
                            _token: CSRF_TOKEN
                        },
                        success: function(res) {
                            if (res.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Hoàn thành!',
                                    text: res.message,
                                    timer: 2500,
                                    showConfirmButton: false
                                }).then(function() {
                                    window.location.href = '{{ route('pages.ebmr.production') }}';
                                });
                            } else {
                                Swal.fire('Lỗi', res.message, 'error');
                            }
                        },
                        error: function() {
                            Swal.fire('Lỗi', 'Không thể kết nối máy chủ', 'error');
                        }
                    });
                });
            }
        </script>
    @endsection
