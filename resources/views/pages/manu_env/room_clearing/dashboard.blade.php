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

        --accent: #0ea5e9;

        .equip-card {
            border-radius: 12px;
            border: 1px solid #e8ecf0;
            transition: all 0.2s;
            background: #fff;
        }

        .equip-card:hover {
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
        }

        .equip-card-header {
            border-radius: 12px 12px 0 0;
            padding: 14px 18px 12px;
            background: linear-gradient(135deg, #0ea5e9, #0284c7);
        }

        .prog-bar-sm {
            height: 6px;
            background: #f0f2f5;
            border-radius: 999px;
            overflow: hidden;
        }

        .prog-bar-sm-fill {
            height: 100%;
            background: #0ea5e9;
            border-radius: 999px;
            transition: width 0.4s;
        }
    </style>

    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <a href="{{ route('pages.manu_env.room_clearing.index') }}"
                        class="btn btn-outline-secondary rounded-pill btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Danh sách phòng
                    </a>
                    <div>
                        <h1 class="m-0 fw-bold text-navy" style="font-size: 1.1rem;">
                            <i class="fas fa-soap me-2" style="color: #0ea5e9;"></i>
                            {{ $room->code }} – {{ $room->name }}
                        </h1>
                        @if ($room->area)
                            <div class="text-muted small">{{ $room->area }}</div>
                        @endif
                    </div>
                    <div class="ms-auto">
                        <button class="btn btn-primary rounded-pill px-4 shadow-sm"
                            onclick="$('#modalReceive').modal('show')">
                            <i class="fas fa-plus me-1"></i> Tiếp nhận Thiết Bị
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <section class="content">
            <div class="container-fluid">

                {{-- Đang vệ sinh --}}
                <div class="mb-4">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <span class="fw-bold text-navy"
                            style="font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.05em;">
                            <i class="fas fa-spinner fa-spin me-1 text-warning"></i> Đang Vệ Sinh
                        </span>
                        <span class="badge bg-warning text-dark rounded-pill">{{ $inProgress->count() }}</span>
                    </div>

                    @if ($inProgress->isEmpty())
                        <div class="p-4 bg-white rounded-3 border text-center text-muted">
                            <i class="fas fa-check-circle fa-2x mb-2 d-block text-success opacity-50"></i>
                            Không có thiết bị nào đang vệ sinh.
                        </div>
                    @else
                        <div class="row g-3">
                            @foreach ($inProgress as $ec)
                                <div class="col-md-4 col-lg-3">
                                    <div class="equip-card shadow-sm">
                                        <div class="equip-card-header">
                                            <div class="d-flex align-items-center gap-2">
                                                <div>
                                                    <span class="text-white fw-bold"
                                                        style="font-size: 0.9rem;">{{ $ec->equipment_code }}</span>
                                                    <span class="text-white opacity-75"
                                                        style="font-size: 0.7rem; line-height: 1.2;">{{ Str::limit($ec->equipment_name, 30) }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="p-3">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="small text-muted">Tiến độ</span>
                                                <span
                                                    class="small fw-bold text-navy">{{ $ec->progress_done }}/{{ $ec->progress_total }}
                                                    bước</span>
                                            </div>
                                            <div class="prog-bar-sm mb-3">
                                                <div class="prog-bar-sm-fill"
                                                    style="width: {{ $ec->progress_total > 0 ? round(($ec->progress_done / $ec->progress_total) * 100) : 0 }}%">
                                                </div>
                                            </div>
                                            <div class="text-muted" style="font-size: 0.72rem;">
                                                <i class="far fa-clock me-1"></i> Bắt đầu:
                                                {{ $ec->started_at ? $ec->started_at->format('d/m H:i') : 'N/A' }}
                                            </div>
                                        </div>
                                        <div class="border-top p-2">
                                            <a href="{{ route('pages.manu_env.cleaning_process.equip.campaign.open', ['equip_id' => $ec->equipment_id]) }}?campaign_id={{ $ec->id }}"
                                                class="btn btn-sm w-100 rounded-pill fw-semibold"
                                                style="background: #0ea5e9; color: #fff; font-size: 0.78rem;">
                                                <i class="fas fa-play me-1"></i> Tiếp tục VS
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Lịch sử --}}
                <div>
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <span class="fw-bold text-navy"
                            style="font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.05em;">
                            <i class="fas fa-history me-1 text-success"></i> Đã Hoàn Thành Gần Đây
                        </span>
                        <span class="badge bg-success rounded-pill">{{ $completed->count() }}</span>
                    </div>

                    @if ($completed->isEmpty())
                        <div class="p-3 bg-white rounded-3 border text-center text-muted small">Chưa có lịch sử vệ sinh tại
                            phòng này.</div>
                    @else
                        <div class="bg-white rounded-3 border overflow-hidden">
                            <table class="table table-hover table-sm mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="py-2 ps-3">Thiết bị</th>
                                        <th class="py-2">Cấp vệ sinh</th>
                                        <th class="py-2">Bắt đầu</th>
                                        <th class="py-2">Hoàn thành</th>
                                        <th class="py-2 text-center">Hành động</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($completed as $ec)
                                        <tr>
                                            <td class="ps-3 align-middle">
                                                <div class="fw-semibold text-navy" style="font-size: 0.85rem;">
                                                    {{ $ec->equipment_code }}</div>
                                                <div class="text-muted" style="font-size: 0.72rem;">
                                                    {{ Str::limit($ec->equipment_name, 35) }}</div>
                                            </td>
                                            <td class="align-middle">
                                                @php $cl = ['1' => 'Cấp 1', '2' => 'Cấp 2', '3' => 'VS lại'][$ec->cleaning_type] ?? '-'; @endphp
                                                <span class="badge bg-info text-white rounded-pill"
                                                    style="font-size: 0.7rem;">{{ $cl }}</span>
                                            </td>
                                            <td class="align-middle small text-muted">
                                                {{ $ec->started_at ? $ec->started_at->format('d/m H:i') : '-' }}</td>
                                            <td class="align-middle small text-muted">
                                                {{ $ec->completed_at ? $ec->completed_at->format('d/m H:i') : '-' }}</td>
                                            <td class="align-middle text-center">
                                                <a href="{{ route('pages.manu_env.cleaning_process.equip.campaign.open', ['equip_id' => $ec->equipment_id]) }}?campaign_id={{ $ec->id }}"
                                                    class="btn btn-xs btn-outline-info rounded-pill"
                                                    style="font-size: 0.7rem; padding: 2px 10px;">
                                                    <i class="fas fa-eye me-1"></i> Xem HS
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

            </div>
        </section>
    </div>

    {{-- Modal Tiếp Nhận Thiết Bị --}}
    <div class="modal fade" id="modalReceive" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-navy text-white">
                    <h5 class="modal-title"><i class="fas fa-plus me-2"></i> Tiếp Nhận Thiết Bị Vào Phòng VS</h5>
                    <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Thiết bị cần vệ sinh <span
                                class="text-danger">*</span></label>
                        <select id="recv-equip" class="form-select" onchange="loadEquipProcesses()">
                            <option value="">-- Chọn thiết bị --</option>
                            @php
                                $portableEquips = \Illuminate\Support\Facades\DB::table('instrument')
                                    ->where('is_Portable_equipment', 1)
                                    ->orderBy('code')
                                    ->get();
                            @endphp
                            @foreach ($portableEquips as $eq)
                                <option value="{{ $eq->id }}" data-code="{{ $eq->code }}"
                                    data-name="{{ $eq->name }}">
                                    {{ $eq->code }} – {{ $eq->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div id="recv-process-loading" class="text-muted small mb-3 d-none"><i
                            class="fas fa-spinner fa-spin me-1"></i> Đang tải quy trình...</div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Cấp vệ sinh</label>
                        <select id="recv-cleaning-type" class="form-select">
                            <option value="1">Cấp 1 (Hạn 3 ngày)</option>
                            <option value="2">Cấp 2 (Hạn 7 ngày)</option>
                            <option value="3">Vệ sinh lại (Hạn 24h)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-primary px-4" onclick="receiveEquip()">
                        <i class="fas fa-sign-in-alt me-1"></i> Tiếp nhận & Mở VS
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        const CSRF = '{{ csrf_token() }}';
        const ROOM_ID = {{ $room->id }};

        let loadedProcesses = [];

        function loadEquipProcesses() {
            const equipId = $('#recv-equip').val();
            loadedProcesses = [];
            if (!equipId) {
                return;
            }

            $('#recv-process-loading').removeClass('d-none');

            $.get(`/ebmr/api/equip-processes?equip_id=${equipId}`, function(res) {
                $('#recv-process-loading').addClass('d-none');
                if (res.length > 0) {
                    loadedProcesses = res;
                }
            }).fail(() => {
                $('#recv-process-loading').addClass('d-none');
            });
        }

        function receiveEquip() {
            const equipId = $('#recv-equip').val();
            const cleanType = $('#recv-cleaning-type').val();

            let processId = '';
            if (loadedProcesses.length > 0) {
                const match = loadedProcesses.find(p => p.cleaning_type == cleanType);
                if (match) {
                    processId = match.id;
                }
            }

            if (!equipId) {
                Swal.fire('Thiếu thông tin', 'Vui lòng chọn thiết bị.', 'warning');
                return;
            }
            if (!processId) {
                Swal.fire('Lỗi', 'Không tìm thấy quy trình vệ sinh tương ứng cho cấp độ này', 'warning');
                return;
            }

            $.ajax({
                url: `/manu_env/room-clearing/${ROOM_ID}/receive-equip`,
                method: 'POST',
                data: {
                    _token: CSRF,
                    equipment_id: equipId,
                    process_list_id: processId,
                    cleaning_type: cleanType,
                },
                success: res => {
                    if (res.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Đã tiếp nhận!',
                            text: res.message,
                            showCancelButton: true,
                            confirmButtonText: '<i class="fas fa-play me-1"></i> Bắt đầu VS ngay',
                            cancelButtonText: 'Về dashboard'
                        }).then(r => {
                            if (r.isConfirmed && res.open_url) window.open(res.open_url, '_blank');
                            location.reload();
                        });
                    } else {
                        Swal.fire('Lỗi', res.message, 'error');
                    }
                },
                error: xhr => {
                    Swal.fire('Lỗi', xhr.responseJSON?.message || 'Không thể tiếp nhận thiết bị.', 'error');
                }
            });
        }
    </script>
@endsection
