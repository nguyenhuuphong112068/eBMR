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

        /* Accent: xanh lam (phân biệt với vệ sinh phòng = vàng) */
        :root {
            --equip-accent: #0ea5e9;
            --equip-accent-dark: #0284c7;
        }

        .campaign-wrapper {
            display: flex;
            height: calc(100vh - 56px);
            overflow: hidden;
        }

        /* SIDEBAR */
        .campaign-sidebar {
            width: 260px;
            min-width: 260px;
            background: #fff;
            border-right: 1px solid #e8ecf0;
            display: flex;
            flex-direction: column;
            box-shadow: 2px 0 12px rgba(0, 0, 0, 0.05);
            z-index: 2;
            overflow-y: auto;
        }

        .sidebar-header {
            background: linear-gradient(135deg, var(--equip-accent) 0%, var(--equip-accent-dark) 100%);
            padding: 20px 16px 16px;
            position: relative;
            flex-shrink: 0;
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
            background: #f0f9ff;
        }

        .step-item.active {
            background: rgba(14, 165, 233, 0.08);
            border-left-color: var(--equip-accent);
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
            background: var(--equip-accent);
            color: #fff;
            box-shadow: 0 4px 10px rgba(14, 165, 233, 0.4);
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

        /* MAIN */
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
            background: linear-gradient(135deg, var(--equip-accent), var(--equip-accent-dark));
            color: #fff;
            font-weight: 700;
            font-size: 0.85rem;
            padding: 6px 16px;
            border-radius: 999px;
            margin-bottom: 16px;
            box-shadow: 0 4px 12px rgba(14, 165, 233, 0.3);
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
            border-color: var(--equip-accent);
            outline: none;
            box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.12);
        }

        .btn-complete-step {
            background: linear-gradient(135deg, var(--equip-accent) 0%, var(--equip-accent-dark) 100%);
            color: #fff;
            border: none;
            padding: 10px 24px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 0.85rem;
            box-shadow: 0 4px 14px rgba(14, 165, 233, 0.35);
            transition: all 0.25s;
            cursor: pointer;
        }

        .btn-complete-step:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(14, 165, 233, 0.5);
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

            {{-- SIDEBAR --}}
            <aside class="campaign-sidebar">
                <div class="sidebar-header">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div>
                            <div class="text-white fw-bold" style="font-size: 0.9rem; line-height: 1.2;">VỆ SINH THIẾT BỊ
                            </div>
                            <div class="text-white opacity-75" style="font-size: 0.72rem;">{{ $equip->code }} –
                                {{ $equip->name }}</div>
                        </div>
                    </div>
                    <div class="text-white opacity-80 mb-2" style="font-size: 0.75rem;">
                        {{ $processList->process_name }} | V.{{ $processList->version }}
                    </div>
                    @if ($sourceRoom)
                        <div class="text-white opacity-70 mb-2" style="font-size: 0.7rem;">
                            <i class="fas fa-door-open me-1"></i> Phòng: {{ $sourceRoom->code }}
                        </div>
                    @endif
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

                {{-- Link quay lại campaign phòng nếu có --}}
                @if ($roomCampaign && $campaign->status !== 'completed')
                    <div class="p-3 border-top" style="flex-shrink: 0;">
                        <a href="{{ route('pages.manu_env.cleaning_process.campaign.open', ['room_id' => $campaign->source_room_id]) }}"
                            class="btn btn-sm btn-outline-primary w-100 rounded-pill">
                            <i class="fas fa-arrow-left me-1"></i> Quay lại VS Phòng
                        </a>
                    </div>
                @endif
            </aside>

            {{-- MAIN --}}
            <div class="campaign-main">
                {{-- Topbar --}}
                <div class="campaign-topbar">
                    <a href="{{ $sourceRoom ? route('pages.manu_env.cleaning_process.campaign.open', ['room_id' => $campaign->source_room_id]) : route('pages.ebmr.production') }}"
                        class="btn btn-outline-secondary rounded-pill px-3 py-2 shadow-sm me-2" style="font-size: 0.82rem;">
                        <i class="fas fa-chevron-left me-1"></i> Quay lại
                    </a>
                    <div>
                        <h5 class="mb-0 fw-bold text-navy" style="font-size: 1rem;">
                            <i class="fas fa-tools me-2" style="color: var(--equip-accent);"></i>
                            Vệ Sinh Thiết Bị – {{ $equip->code }} – {{ $equip->name }}
                        </h5>
                        <div class="text-muted small">
                            {{ $processList->process_code }} | {{ $processList->process_name }} |
                            V.{{ $processList->version }}
                            <span class="ms-2 border-start ps-2">
                                <i class="far fa-clock me-1"></i> Bắt đầu lúc:
                                <strong>{{ $campaign->started_at ? $campaign->started_at->format('d/m/Y H:i:s') : 'N/A' }}</strong>
                            </span>
                            @if ($sourceRoom)
                                <span class="ms-2 border-start ps-2">
                                    <i class="fas fa-door-open me-1"></i> {{ $sourceRoom->code }} –
                                    {{ $sourceRoom->name }}
                                </span>
                            @endif
                        </div>
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
                                        <span class="step-done-badge">
                                            <i class="fas fa-check-circle"></i> Đã hoàn thành
                                        </span>
                                    @endif
                                </div>

                                {{-- Nội dung bước --}}
                                <div class="mb-2 small text-muted fw-bold text-uppercase"
                                    style="font-size: 0.68rem; letter-spacing: 0.06em;">
                                    <i class="fas fa-clipboard-list me-1 text-secondary"></i> Nội dung thực hiện
                                </div>
                                <div class="content-section mb-4">{!! $step->content ?? '<span class="text-muted">Không có nội dung</span>' !!}</div>

                                @php $hasStandard = !empty($step->standard) && trim(strip_tags($step->standard)) !== ''; @endphp
                                <div class="row gx-4 mt-3">
                                    @if ($hasStandard)
                                        <div class="col-md-6 mb-4 mb-md-0 d-flex flex-column">
                                            <div class="mb-2 small text-muted fw-bold text-uppercase"
                                                style="font-size: 0.68rem; letter-spacing: 0.06em;">
                                                <i class="fas fa-ruler-combined me-1 text-info"></i> Tiêu chuẩn
                                            </div>
                                            <div class="standard-section flex-grow-1 mb-0">{!! $step->standard !!}</div>
                                        </div>
                                    @endif

                                    <div class="{{ $hasStandard ? 'col-md-6' : 'col-12' }}">
                                        @if (!$step->is_done)
                                            {{-- Chưa làm: form xác nhận --}}
                                            <div class="mb-3">
                                                <div class="mb-2 small text-muted fw-bold text-uppercase"
                                                    style="font-size: 0.68rem; letter-spacing: 0.06em;">
                                                    <i class="fas fa-clipboard-check me-1 text-primary"></i> Đánh giá kết
                                                    quả
                                                </div>
                                                <div class="d-flex align-items-center gap-3 p-3 bg-light rounded-3 border">
                                                    <div class="form-check form-switch fs-5 mb-0 d-flex align-items-center">
                                                        <input class="form-check-input mt-0" type="checkbox" role="switch"
                                                            id="is-passed-{{ $idx }}"
                                                            onchange="togglePassedLabel({{ $idx }})"
                                                            style="cursor: pointer;">
                                                        <label class="form-check-label fs-6 ms-2 fw-bold text-danger"
                                                            for="is-passed-{{ $idx }}"
                                                            id="is-passed-label-{{ $idx }}">KHÔNG ĐẠT</label>
                                                    </div>
                                                </div>
                                            </div>

                                            <div id="confirm-info-{{ $idx }}"
                                                class="d-none mb-3 p-3 bg-light rounded-3 border align-items-center gap-3">
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
                                                    <div class="fw-bold text-navy"
                                                        style="font-family: 'Brush Script MT', cursive; font-size: 1.5rem;">
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
                                                <i class="fas fa-pen me-1 text-secondary"></i> Ghi chú kết quả (tùy chọn)
                                            </div>
                                            <textarea id="result-note-{{ $idx }}" class="note-area form-control mb-3" rows="2"
                                                placeholder="Nhập ghi chú nếu cần..."></textarea>

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
                                            {{-- Đã làm: hiển thị kết quả --}}
                                            <div class="mb-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="small text-muted fw-bold text-uppercase"
                                                        style="font-size: 0.68rem;">Kết quả:</div>
                                                    @if ($step->is_passed)
                                                        <span class="badge bg-success"><i class="fas fa-check me-1"></i>
                                                            ĐẠT</span>
                                                    @else
                                                        <span class="badge bg-danger"><i class="fas fa-times me-1"></i> KHÔNG
                                                            ĐẠT</span>
                                                    @endif
                                                </div>
                                                <div class="d-flex gap-2">
                                                    @if ($campaign->status !== 'completed')
                                                        <button type="button" class="btn btn-sm btn-outline-warning rounded-pill"
                                                            style="font-size: 0.7rem;" onclick="openEditStepModal({{ $idx }})">
                                                            <i class="fas fa-edit me-1"></i> Sửa kết quả
                                                        </button>
                                                    @endif
                                                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill"
                                                        style="font-size: 0.7rem;" onclick="openStepHistoryModal({{ $idx }})">
                                                        <i class="fas fa-history me-1"></i> Lịch sử
                                                    </button>
                                                </div>
                                            </div>
                                            @if ($step->doneByUser)
                                                <div class="mb-3 p-3 bg-light rounded-3 border">
                                                    <div class="d-flex align-items-center gap-3">
                                                        @if (!empty($step->doneByUser->signature_image))
                                                            <img src="{{ $step->doneByUser->signature_image }}"
                                                                alt="Chữ ký"
                                                                style="max-height: 40px; mix-blend-mode: multiply; object-fit: contain;">
                                                        @else
                                                            <div class="fw-bold text-navy"
                                                                style="font-family: 'Brush Script MT', cursive; font-size: 1.5rem;">
                                                                {{ $step->doneByUser->fullName ?? $step->doneByUser->name }}
                                                            </div>
                                                        @endif
                                                        <div class="small text-muted border-start ps-3 border-secondary">
                                                            <div><i class="fas fa-user-check me-1 text-success"></i> Xác
                                                                nhận bởi: <strong
                                                                    class="text-navy">{{ $step->doneByUser->fullName ?? $step->doneByUser->name }}</strong>
                                                            </div>
                                                            <div><i class="far fa-clock me-1 text-info mt-1"></i> Lúc:
                                                                {{ $step->done_at ? $step->done_at->format('d/m/Y H:i:s') : '' }}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                            @if (!empty($step->result_note))
                                                <div class="mb-2 small text-muted fw-bold text-uppercase"
                                                    style="font-size: 0.68rem; letter-spacing: 0.06em;">
                                                    <i class="fas fa-pen me-1 text-secondary"></i> Ghi chú đã lưu
                                                </div>
                                                <div class="p-3 bg-light rounded-3 text-navy small mb-3">
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
                            <i class="fas fa-flag-checkered me-2"></i> Hoàn thành Vệ Sinh Thiết Bị
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Modal toàn bộ quy trình --}}
        <div class="modal fade" id="fullProcessModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-scrollable" style="max-width: 90%;">
                <div class="modal-content">
                    <div class="modal-header bg-navy text-white">
                        <h5 class="modal-title mb-0"><i class="fas fa-tools me-2 text-info"></i> Toàn Bộ Quy Trình Vệ Sinh
                            Thiết Bị</h5>
                        <button type="button" class="close text-white"
                            data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body bg-white p-0">
                        <table class="table table-bordered mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="text-center" style="width: 8%;">Bước</th>
                                    <th style="width: 55%;">Nội dung thực hiện</th>
                                    <th style="width: 37%;">Tiêu chuẩn</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($campaignSteps as $step)
                                    <tr>
                                        <td class="text-center align-middle fw-bold fs-5">
                                            {{ $step->step }}
                                            @if ($step->is_done)
                                                <span class="badge bg-success mt-2"
                                                    style="font-size: 0.7rem; display: block;"><i
                                                        class="fas fa-check"></i></span>
                                            @endif
                                        </td>
                                        <td>{!! $step->content ?? '<span class="text-muted">Không có nội dung</span>' !!}</td>
                                        <td class="border-start border-info border-3">
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

                        {{-- Khung Ký duyệt quy trình --}}
                        @include('components.signature_block', ['wfType' => 'cleaning', 'type' => 'equipment', 'listId' => $campaign->process_list_id])
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
    </div>
@endsection

@section('script')
    <script>
        const CAMPAIGN_ID = {{ $campaign->id }};
        const CAMPAIGN_STATUS = '{{ $campaign->status }}';
        const TOTAL_STEPS = {{ $campaignSteps->count() }};
        const CSRF_TOKEN = '{{ csrf_token() }}';

        @php
            $stepsArr = $campaignSteps->map(fn($s) => ['id' => $s->id, 'step' => $s->step, 'is_done' => (bool) $s->is_done, 'is_passed' => (bool) $s->is_passed, 'note' => $s->result_note])->values()->toArray();
        @endphp
        const stepsData = @json($stepsArr);
        const EDIT_STEP_URL_BASE = "{{ url('/manu_env/cleaning-process/equip-campaign') }}";
        const STEP_HISTORY_URL_BASE = "{{ url('/manu_env/cleaning-process/equip-campaign/step') }}";

        let currentIndex = (() => {
            const i = stepsData.findIndex(s => !s.is_done);
            return i >= 0 ? i : 0;
        })();

        $(document).ready(function() {
            renderStep(currentIndex);
            updateProgress();
            checkFinishButton();
        });

        function goToStep(idx) {
            if (idx < 0 || idx >= TOTAL_STEPS) return;

            if (CAMPAIGN_STATUS === 'completed') {
                const target = $('#panel-' + idx);
                if (target.length) {
                    const container = $('.campaign-content');
                    container.animate({
                        scrollTop: target.position().top + container.scrollTop() - 20
                    }, 500);
                    
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
                $('.step-panel').show().addClass('mb-4');
                $('.campaign-footer').hide();
                $('#topbar-current-step').parent().html('<i class="fas fa-check-circle text-success me-1"></i> Toàn bộ quy trình');
                return;
            }

            $('.step-panel').hide();
            $('#panel-' + idx).show();
            const step = stepsData[idx];
            $('#topbar-current-step').text(step.step);
            $('.step-item').removeClass('active');
            $('#sidebar-item-' + idx).addClass('active');
            $('#btn-prev').prop('disabled', idx === 0);
            $('#btn-complete-step').prop('disabled', false);
            if (step.is_done) {
                $('#btn-complete-step').addClass('d-none');
                $('#btn-finish').addClass('d-none');
            } else if (idx === TOTAL_STEPS - 1) {
                $('#btn-complete-step').removeClass('d-none').html(
                    '<i class="fas fa-check me-2"></i> Ghi nhận & Hoàn thành');
                $('#btn-finish').addClass('d-none');
            } else {
                $('#btn-complete-step').removeClass('d-none').html(
                '<i class="fas fa-check me-2"></i> Ghi nhận & Bước tiếp');
                $('#btn-finish').addClass('d-none');
            }
            checkFinishButton();
        }

        function checkFinishButton() {
            if (CAMPAIGN_STATUS === 'completed') {
                $('#btn-finish').addClass('d-none');
                return;
            }
            if (stepsData.every(s => s.is_done)) {
                $('#btn-complete-step').addClass('d-none');
                $('#btn-finish').removeClass('d-none');
            }
        }

        function updateProgress() {
            const done = stepsData.filter(s => s.is_done).length;
            const pct = (done / TOTAL_STEPS) * 100;
            $('#sidebar-progress-fill').css('width', pct + '%');
            $('#sidebar-done-count').text(done);
        }

        function togglePassedLabel(idx) {
            const isChecked = $('#is-passed-' + idx).is(':checked');
            const label = $('#is-passed-label-' + idx);
            const infoBox = $('#confirm-info-' + idx);
            if (isChecked) {
                label.text('ĐẠT').removeClass('text-danger').addClass('text-success');
                infoBox.removeClass('d-none').addClass('d-flex');
                const now = new Date();
                $('#confirm-time-' + idx).text(now.toLocaleDateString('en-GB') + ' ' + now.toLocaleTimeString('en-GB', {
                    hour12: false
                }));
            } else {
                label.text('KHÔNG ĐẠT').removeClass('text-success').addClass('text-danger');
                infoBox.addClass('d-none').removeClass('d-flex');
            }
        }

        function completeCurrentStep() {
            const step = stepsData[currentIndex];
            const stepId = step.id;
            const isPassed = $('#is-passed-' + currentIndex).is(':checked');
            const note = $('#result-note-' + currentIndex).val();
            const files = $('#file-input-' + currentIndex)[0]?.files;

            const formData = new FormData();
            formData.append('_token', CSRF_TOKEN);
            formData.append('is_passed', isPassed ? '1' : '0');
            formData.append('result_note', note);
            if (files) {
                for (let i = 0; i < Math.min(files.length, 5); i++) formData.append('images[]', files[i]);
            }

            const btn = $('#btn-complete-step');
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i> Đang lưu...');

            $.ajax({
                url: `/manu_env/cleaning-process/equip-campaign/${CAMPAIGN_ID}/step/${stepId}/complete`,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(res) {
                    if (res.success) {
                        stepsData[currentIndex].is_done = true;
                        updateProgress();

                        // Update sidebar badge
                        const isP = $('#is-passed-' + currentIndex).is(':checked');
                        $('#sidebar-circle-' + currentIndex).html(
                            '<i class="fas fa-check" style="font-size:0.65rem;"></i>').css('background',
                            '#22c55e').css('color', '#fff');
                        $('#sidebar-badge-' + currentIndex).html(
                            `<span class="badge ${isP ? 'bg-success' : 'bg-danger'}" style="font-size:0.65rem;">${isP ? 'ĐẠT' : 'KHÔNG ĐẠT'}</span>`
                            );
                        $('#sidebar-item-' + currentIndex).addClass('done');

                        checkFinishButton();
                        if (!stepsData.every(s => s.is_done) && currentIndex < TOTAL_STEPS - 1) {
                            setTimeout(() => goToStep(currentIndex + 1), 300);
                        }
                    } else {
                        Swal.fire('Lỗi', res.message, 'error');
                        btn.prop('disabled', false).html(
                            '<i class="fas fa-check me-2"></i> Ghi nhận & Bước tiếp');
                    }
                },
                error: function() {
                    Swal.fire('Lỗi', 'Không thể ghi nhận. Vui lòng thử lại.', 'error');
                    btn.prop('disabled', false).html('<i class="fas fa-check me-2"></i> Ghi nhận & Bước tiếp');
                }
            });
        }

        // ── SỬA LẠI KẾT QUẢ 1 BƯỚC ĐÃ XÁC NHẬN ────────────────────────────
        function openEditStepModal(idx) {
            const step = stepsData[idx];
            Swal.fire({
                title: 'Sửa kết quả Bước ' + step.step,
                html: `
                    <div class="text-start">
                        <div class="mb-3 p-2 bg-light rounded-3 d-flex align-items-center justify-content-between">
                            <span class="small fw-bold">Kết quả:</span>
                            <div class="form-check form-switch fs-5 mb-0 d-flex align-items-center">
                                <input class="form-check-input mt-0" type="checkbox" role="switch" id="edit-is-passed" ${step.is_passed ? 'checked' : ''}>
                                <label class="form-check-label ms-2 fw-bold ${step.is_passed ? 'text-success' : 'text-danger'}" id="edit-is-passed-label">${step.is_passed ? 'ĐẠT' : 'KHÔNG ĐẠT'}</label>
                            </div>
                        </div>
                        <label class="small fw-bold d-block mb-1">Ghi chú kết quả</label>
                        <textarea id="edit-note" class="form-control mb-3" rows="2">${step.note ?? ''}</textarea>
                        <label class="small fw-bold d-block mb-1 text-danger">Lý do sửa (bắt buộc)</label>
                        <textarea id="edit-reason" class="form-control" rows="2" placeholder="VD: Lỡ bấm nhầm Không đạt, thực tế đã Đạt"></textarea>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-save me-1"></i> Lưu thay đổi',
                cancelButtonText: 'Hủy',
                confirmButtonColor: '#f59e0b',
                focusConfirm: false,
                didOpen: () => {
                    document.getElementById('edit-is-passed').addEventListener('change', function() {
                        const label = document.getElementById('edit-is-passed-label');
                        label.textContent = this.checked ? 'ĐẠT' : 'KHÔNG ĐẠT';
                        label.classList.toggle('text-success', this.checked);
                        label.classList.toggle('text-danger', !this.checked);
                    });
                },
                preConfirm: () => {
                    const reason = document.getElementById('edit-reason').value.trim();
                    if (!reason) {
                        Swal.showValidationMessage('Vui lòng nhập lý do sửa');
                        return false;
                    }
                    return {
                        is_passed: document.getElementById('edit-is-passed').checked,
                        result_note: document.getElementById('edit-note').value,
                        reason: reason,
                    };
                }
            }).then((result) => {
                if (!result.isConfirmed) return;

                Swal.fire({ title: 'Đang lưu...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

                $.ajax({
                    url: EDIT_STEP_URL_BASE + '/' + CAMPAIGN_ID + '/step/' + step.id + '/edit',
                    method: 'POST',
                    data: {
                        _token: CSRF_TOKEN,
                        is_passed: result.value.is_passed,
                        result_note: result.value.result_note,
                        reason: result.value.reason,
                    },
                    success: function(res) {
                        if (res.success) {
                            Swal.fire({ icon: 'success', title: 'Đã cập nhật!', timer: 1500, showConfirmButton: false })
                                .then(() => window.location.reload());
                        } else {
                            Swal.fire('Không thể sửa', res.message, 'warning');
                        }
                    },
                    error: function() {
                        Swal.fire('Lỗi', 'Không thể kết nối máy chủ', 'error');
                    }
                });
            });
        }

        function openStepHistoryModal(idx) {
            const step = stepsData[idx];
            Swal.fire({ title: 'Đang tải lịch sử...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

            $.ajax({
                url: STEP_HISTORY_URL_BASE + '/' + step.id + '/history',
                method: 'GET',
                success: function(res) {
                    if (!res.success || res.history.length === 0) {
                        Swal.fire({ icon: 'info', title: 'Lịch sử sửa', text: 'Bước này chưa từng được sửa lại.' });
                        return;
                    }
                    const rows = res.history.map(h => `
                        <tr>
                            <td class="small text-nowrap">${h.changed_at}</td>
                            <td class="small">${h.changed_by_name}</td>
                            <td class="small text-nowrap">
                                <span class="badge ${h.old_is_passed ? 'bg-success' : 'bg-danger'}">${h.old_is_passed ? 'ĐẠT' : 'KHÔNG ĐẠT'}</span>
                                →
                                <span class="badge ${h.new_is_passed ? 'bg-success' : 'bg-danger'}">${h.new_is_passed ? 'ĐẠT' : 'KHÔNG ĐẠT'}</span>
                            </td>
                            <td class="small">${h.reason ?? ''}</td>
                        </tr>
                    `).join('');
                    Swal.fire({
                        title: 'Lịch sử sửa Bước ' + step.step,
                        html: `<div class="table-responsive text-start"><table class="table table-sm table-bordered">
                            <thead><tr><th>Thời gian</th><th>Người sửa</th><th>Thay đổi</th><th>Lý do</th></tr></thead>
                            <tbody>${rows}</tbody></table></div>`,
                        width: 700,
                        confirmButtonText: 'Đóng'
                    });
                },
                error: function() {
                    Swal.fire('Lỗi', 'Không thể tải lịch sử', 'error');
                }
            });
        }

        function finishCampaign() {
            Swal.fire({
                title: 'Hoàn thành vệ sinh thiết bị?',
                text: 'Xác nhận thiết bị {{ $equip->code }} đã được vệ sinh sạch sẽ.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#22c55e',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Xác nhận hoàn thành',
                cancelButtonText: 'Hủy'
            }).then(result => {
                if (!result.value) return;
                $.ajax({
                    url: `/manu_env/cleaning-process/equip-campaign/${CAMPAIGN_ID}/complete`,
                    method: 'POST',
                    data: {
                        _token: CSRF_TOKEN
                    },
                    success: function(res) {
                        if (res.success) {
                            Swal.fire({
                                    icon: res.has_failed ? 'warning' : 'success',
                                    title: res.has_failed ? 'Kết thúc quy trình' : 'Hoàn thành!',
                                    text: res.message,
                                    timer: res.has_failed ? 4000 : 2500,
                                    showConfirmButton: false
                                })
                                .then(() => {
                                    window.close();
                                });
                        } else {
                            Swal.fire('Chưa thể hoàn thành', res.message, 'warning');
                        }
                    },
                    error: function() {
                        Swal.fire('Lỗi', 'Không thể hoàn thành. Vui lòng thử lại.', 'error');
                    }
                });
            });
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
                    <i class="fas fa-times"></i>
                </button>
            </div>
            `;
                container.append(thumb);
            });
        }
    </script>
@endsection
