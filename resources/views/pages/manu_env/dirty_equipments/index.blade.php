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
            height: 100%;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .equip-card:hover {
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
        }

        .equip-card-header {
            border-radius: 12px 12px 0 0;
            padding: 14px 18px 12px;
        }

        .bg-cleaning {
            background: linear-gradient(135deg, #f59e0b, #d97706);
        }

        .bg-dirty {
            background: linear-gradient(135deg, #f59e0b, #f59e0b);
        }
    </style>

    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="d-flex align-items-center gap-3 flex-wrap justify-content-between w-100">
                    <h1 class="m-0 fw-bold text-navy" style="font-size: 1.25rem;">
                        <i class="fas fa-tools me-2" style="color: var(--accent);"></i>
                        Thiết Bị Cần Vệ Sinh
                    </h1>

                    <div class="d-flex gap-2 align-items-center flex-wrap">
                        <div class="input-group input-group-sm shadow-sm" style="width: auto; min-width: 250px;">
                            <span class="input-group-text bg-white border-secondary rounded-start-pill border-end-0">
                                <i class="fas fa-search text-muted"></i>
                            </span>
                            <input type="text" id="searchEquip" class="form-control border-secondary rounded-end-pill border-start-0" placeholder="Tìm tên, mã thiết bị...">
                        </div>
                        <select class="form-select form-select-sm border-secondary shadow-sm"
                        onchange="window.location.href='?department=' + this.value"
                        style="width: auto; min-width: 200px; padding: 0.375rem 2.25rem 0.375rem 0.75rem; border-radius: 50rem;">
                        <option value="">-- Tất cả Phân Xưởng --</option>
                        @foreach ($departments as $dept)
                            <option value="{{ $dept }}"
                                {{ isset($selectedDepartment) && $selectedDepartment == $dept ? 'selected' : '' }}>
                                Phân xưởng {{ $dept }}</option>
                        @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <section class="content">
            <div class="container-fluid">
                @if ($equipments->isEmpty())
                    <div class="p-5 bg-white rounded-3 border text-center text-muted shadow-sm">
                        <i class="fas fa-check-circle fa-3x mb-3 d-block text-success opacity-50"></i>
                        <h5>Tuyệt vời!</h5>
                        <p>Hiện không có thiết bị nào đang cần vệ sinh.</p>
                    </div>
                @else
                    <div class="row g-3">
                        @foreach ($equipments as $eq)
                            <div class="col-md-4 col-lg-3 equip-col" data-search="{{ strtolower($eq->code . ' ' . $eq->name) }}">
                                <div class="equip-card shadow-sm mt-2">
                                    <div
                                        class="equip-card-header {{ $eq->status === 'cleaning' ? 'bg-cleaning' : 'bg-dirty' }}">
                                                <div class="d-flex align-items-center gap-2">
                                                    <div>
                                                        <div class="text-white fw-bold" style="font-size: 0.9rem;">
                                                            {{ $eq->code }}</div>
                                                        <div class="text-white opacity-75"
                                                            style="font-size: 0.7rem; line-height: 1.2;">
                                                            {{ Str::limit($eq->name, 30) }}</div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="p-3 flex-grow-1">
                                                <div class="mb-2">
                                                    @if ($eq->status === 'cleaning')
                                                        <span class="badge bg-warning text-dark"><i
                                                                class="fas fa-spinner fa-spin me-1"></i> Đang vệ sinh</span>
                                                    @else
                                                        <span class="badge bg-danger"><i
                                                                class="fas fa-exclamation-triangle me-1"></i> Cần vệ
                                                            sinh</span>
                                                    @endif

                                                    @if ($eq->is_Portable_equipment)
                                                        <span class="badge bg-info text-white"><i
                                                                class="fas fa-people-carry me-1"></i> Di động</span>
                                                    @else
                                                        <span class="badge bg-secondary"><i class="fas fa-anchor me-1"></i>
                                                            Cố định</span>
                                                    @endif
                                                </div>
                                                <div class="text-muted" style="font-size: 0.72rem;">
                                                    <i class="far fa-clock me-1"></i> Từ:
                                                    {{ \Carbon\Carbon::parse($eq->status_since)->format('d/m/Y H:i') }}
                                                </div>
                                            </div>
                                            <div class="border-top p-2 mt-auto">
                                                @if ($eq->status === 'cleaning' && $eq->campaign_id)
                                                    <a href="{{ route('pages.manu_env.cleaning_process.equip.campaign.open', ['equip_id' => $eq->id]) }}?campaign_id={{ $eq->campaign_id }}&room_campaign_id={{ $eq->room_campaign_id }}"
                                                        class="btn btn-sm w-100 rounded-pill fw-semibold"
                                                        style="background: #fff3cd; color: #856404; border: 1px solid #ffeeba;"
                                                        target="_blank">
                                                        <i class="fas fa-arrow-right me-1"></i> Tiếp tục vệ sinh
                                                    </a>
                                                @else
                                                    <button type="button" class="btn btn-sm w-100 rounded-pill fw-semibold"
                                                        style="background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;"
                                                        onclick="openReceiveModal('{{ $eq->id }}', '{{ $eq->code }}', '{{ $eq->name }}')">
                                                        <i class="fas fa-plus me-1"></i> Bắt đầu vệ sinh
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                        @endforeach
                    </div>
                @endif
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
                    <input type="hidden" id="recv-equip-id">

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Thiết bị</label>
                        <input type="text" id="recv-equip-name" class="form-control" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Phòng vệ sinh chung <span
                                class="text-danger">*</span></label>
                        <select id="recv-room" class="form-select">
                            <option value="">-- Chọn phòng --</option>
                            @foreach ($commonRooms as $rm)
                                <option value="{{ $rm->id }}">{{ $rm->code }} – {{ $rm->name }}</option>
                            @endforeach
                        </select>
                        <div class="text-muted small mt-1">Lưu ý: Thường chỉ dành cho thiết bị di động. Thiết bị cố định sẽ
                            được vệ sinh tại phòng.</div>
                    </div>

                    <div id="recv-process-loading" class="text-muted small mb-3 d-none"><i class="fas fa-spinner fa-spin me-1"></i> Đang tải quy trình...</div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Cấp vệ sinh</label>
                        <select id="recv-level" class="form-select">
                            <option value="1">Vệ Sinh Cấp I</option>
                            <option value="2">Vệ Sinh Cấp II</option>
                            <option value="3">Vệ Sinh Lại</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary rounded-pill px-3" data-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-primary rounded-pill px-4" id="btn-submit-receive"
                        onclick="submitReceive()">
                        <i class="fas fa-check me-1"></i> Bắt đầu
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        const CSRF_TOKEN = '{{ csrf_token() }}';

        function openReceiveModal(id, code, name) {
            $('#recv-equip-id').val(id);
            $('#recv-equip-name').val(code + ' – ' + name);
            $('#recv-room').val('');
            $('#recv-level').val('1');

            $('#modalReceive').modal('show');
            loadEquipProcesses(id);
        }

        $(document).ready(function() {
            $('#searchEquip').on('input', function() {
                let val = $(this).val().toLowerCase();
                $('.equip-col').each(function() {
                    let search = $(this).data('search');
                    if (search.includes(val)) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            });
        });

        let loadedProcesses = [];

        function loadEquipProcesses(equipId) {
            if (!equipId) return;
            $('#recv-process-loading').removeClass('d-none');
            loadedProcesses = [];

            $.ajax({
                url: '/ebmr/api/equip-processes?equip_id=' + equipId,
                method: 'GET',
                success: function(res) {
                    $('#recv-process-loading').addClass('d-none');
                    if (res && res.length > 0) {
                        loadedProcesses = res;
                    }
                },
                error: function() {
                    $('#recv-process-loading').addClass('d-none');
                }
            });
        }

        function submitReceive() {
            const roomId = $('#recv-room').val();
            const equipId = $('#recv-equip-id').val();
            const level = $('#recv-level').val();

            let processId = '';
            if (loadedProcesses.length > 0) {
                const match = loadedProcesses.find(p => p.cleaning_type == level);
                if (match) {
                    processId = match.id;
                }
            }

            if (!roomId) {
                Swal.fire('Lỗi', 'Vui lòng chọn phòng vệ sinh chung', 'warning');
                return;
            }
            if (!processId) {
                Swal.fire('Lỗi', 'Không tìm thấy quy trình vệ sinh tương ứng cho cấp độ này', 'warning');
                return;
            }

            $('#btn-submit-receive').prop('disabled', true).html(
                '<i class="fas fa-spinner fa-spin me-1"></i> Đang xử lý...');

            $.ajax({
                url: '/manu_env/room-clearing/' + roomId + '/receive-equip',
                method: 'POST',
                data: {
                    _token: CSRF_TOKEN,
                    equipment_id: equipId,
                    process_list_id: processId,
                    cleaning_type: level
                },
                success: function(res) {
                    if (res.success) {
                        if (res.open_url) {
                            window.open(res.open_url, '_blank');
                        }
                        window.location.reload();
                    } else {
                        Swal.fire('Lỗi', res.message, 'error');
                        $('#btn-submit-receive').prop('disabled', false).html(
                            '<i class="fas fa-check me-1"></i> Bắt đầu');
                    }
                },
                error: function(xhr) {
                    Swal.fire('Lỗi', 'Không thể kết nối máy chủ', 'error');
                    $('#btn-submit-receive').prop('disabled', false).html(
                        '<i class="fas fa-check me-1"></i> Bắt đầu');
                }
            });
        }
    </script>
@endsection
