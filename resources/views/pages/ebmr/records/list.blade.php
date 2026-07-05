@extends('layout.master')

@section('title', $mode == 'history' ? 'Lịch Sử Ban Hành' : 'Hồ Sơ Sản Xuất')

@section('mainContent')
    <div class="content-wrapper">
        <div class="container-fluid py-4">
            <div class="row">
                <div class="col-12">
                    <div class="card border-0 shadow-sm overflow-hidden">
                        <div
                            class="card-header {{ $mode == 'history' ? 'bg-primary shadow-sm' : 'bg-navy' }} py-3 d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 text-white fw-bold">
                                <i class="fas {{ $mode == 'history' ? 'fa-history' : 'fa-clipboard-list' }} me-2"></i>
                                {{ $mode == 'history' ? 'Lịch Sử BMR Đã Ban Hành (Số Lô)' : 'Hồ Sơ Đã Nhận Ban Hành' }}
                            </h5>
                            <div
                                class="badge bg-white {{ $mode == 'history' ? 'text-primary' : 'text-navy' }} rounded-pill px-3 shadow-sm">
                                {{ $records->count() }} Hồ sơ</div>
                        </div>
                        <div class="card-body p-4">
                            <div class="table-responsive">
                                <table id="recordsTable" class="table table-hover align-middle" style="width:100%">
                                    <thead class="bg-light text-navy">
                                        <tr>
                                            <th class="text-center" style="width: 50px;">STT</th>
                                            <th>Mã Hồ Sơ</th>
                                            <th>Tên Sản Phẩm</th>
                                            <th>Số Lô (Batch No.)</th>
                                            <th>Công đoạn</th>
                                            <th>Ngày Ban Hành</th>
                                            <th>Người Ban Hành</th>
                                            <th>Trạng Thái</th>
                                            @if ($mode != 'history')
                                                <th class="text-center">Thao tác</th>
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($records as $index => $r)
                                            <tr>
                                                <td class="text-center fw-bold text-muted">{{ $index + 1 }}</td>
                                                <td>{{ $r->document_code }}</td>
                                                <td>{{ $r->template_name }}</td>
                                                <td class="fw-bold text-primary" style="font-size: 1.1rem;">
                                                    {{ $r->batch_number }}</td>
                                                <td>
                                                    <div class="d-flex flex-wrap gap-1">
                                                        @forelse($r->sections as $s)
                                                            @php $sDist = $r->distributions->get($s['id']); @endphp
                                                            <a href="{{ route('pages.ebmr.execute', $r->id) }}?section={{ $s['id'] }}"
                                                                class="badge {{ $sDist ? 'bg-soft-success text-success border-success' : 'bg-soft-info text-info border-info' }} border rounded-pill py-1 px-2 text-decoration-none hover-glow"
                                                                style="font-size: 0.75rem; cursor: pointer; transition: all 0.2s;"
                                                                title="{{ $sDist ? 'Đã phân phối tới phòng ' . $sDist->room_code : 'Chưa phân phối' }}">
                                                                <i class="fas {{ $sDist ? 'fa-check-circle' : 'fa-play-circle' }} me-1"></i> {{ $s['label'] }}
                                                                @if ($sDist)
                                                                    <span class="fw-bold">({{ $sDist->room_code }})</span>
                                                                @endif
                                                            </a>
                                                        @empty
                                                            <span class="text-muted small">N/A</span>
                                                        @endforelse
                                                    </div>
                                                </td>
                                                <td class="small text-muted">
                                                    {{ \Carbon\Carbon::parse($r->created_at)->format('d/m/Y H:i') }}
                                                </td>
                                                <td class="small fw-bold">
                                                    <i class="fas fa-user-circle me-1 text-muted"></i>
                                                    {{ $r->issuer_name ?? 'N/A' }}
                                                </td>
                                                <td>
                                                    @if ($r->status === 'active')
                                                        <span
                                                            class="badge bg-soft-success text-success border border-success"><i
                                                                class="fas fa-spinner fa-spin me-1"></i> Đang sản
                                                            xuất</span>
                                                    @elseif($r->status === 'draft')
                                                        <span class="badge bg-light text-muted border">Chưa bắt đầu</span>
                                                    @else
                                                        <span
                                                            class="badge bg-soft-info text-info border border-info">{{ $r->status }}</span>
                                                    @endif
                                                </td>
                                                @if ($mode != 'history')
                                                    <td class="text-center">
                                                        <div class="btn-group shadow-sm rounded-pill overflow-hidden">
                                                            {{-- Trang này do văn thư phân xưởng quản lý — chỉ xem, chưa ghi
                                                                 chép được. Ghi chép chỉ mở khi đã Phân phối + phòng đủ
                                                                 điều kiện, thực hiện từ trang "Phòng Sản Xuất". --}}
                                                            <button class="btn btn-navy btn-sm px-3" title="Xem hồ sơ (chỉ xem)"
                                                                onclick="window.location.href='{{ route('pages.ebmr.execute', $r->id) }}'">
                                                                <i class="fas fa-eye me-1"></i> Xem hồ sơ
                                                            </button>
                                                            @if ($mode == 'working')
                                                                <button class="btn btn-outline-navy btn-sm px-3" title="Phân phối công đoạn tới phòng sản xuất"
                                                                    onclick='openDistributeModal({{ $r->id }}, {{ json_encode($r->sections) }})'>
                                                                    <i class="fas fa-share-square me-1"></i> Phân phối
                                                                </button>
                                                            @endif
                                                        </div>
                                                    </td>
                                                @endif
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Phân phối công đoạn tới phòng sản xuất -->
    <div class="modal fade" id="distributeModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-navy text-white">
                    <h5 class="modal-title fw-bold"><i class="fas fa-share-square me-2"></i> Phân Phối Công Đoạn</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-4 bg-light">
                    <div class="alert alert-info small mb-4 border-0 shadow-sm">
                        <i class="fas fa-info-circle me-1"></i> Chọn phòng sản xuất và người được phép ghi chép cho từng
                        công đoạn của lô này. Danh sách phòng đã được lọc theo đúng công đoạn; danh sách người đã được lọc
                        theo phân xưởng của sản phẩm. Chỉ những công đoạn có chọn phòng mới được phân phối.
                    </div>
                    <div id="distributeSectionsContainer"></div>
                </div>
                <div class="modal-footer bg-white">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy bỏ</button>
                    <button type="button" class="btn btn-navy fw-bold px-4" onclick="submitDistribution()">
                        <i class="fas fa-check me-1"></i> Xác nhận Phân phối
                    </button>
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

        .btn-outline-navy {
            color: #003A4F;
            border-color: #003A4F;
        }

        .btn-outline-navy:hover {
            background-color: #003A4F;
            color: #fff;
        }

        .bg-soft-success {
            background-color: rgba(40, 167, 69, 0.1);
        }

        .bg-soft-info {
            background-color: rgba(23, 162, 184, 0.1);
        }

        .hover-glow:hover {
            background-color: #003A4F !important;
            color: #fff !important;
            box-shadow: 0 4px 10px rgba(0, 58, 79, 0.2);
            transform: translateY(-1px);
        }

        .distribute-section-row {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-left: 4px solid #003A4F;
            border-radius: 10px;
            padding: 18px 20px;
            margin-bottom: 16px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
        }

        .distribute-section-row.no-rooms-warning {
            border-left-color: #dc3545;
        }

        .distribute-field-label {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            color: #6c757d;
            letter-spacing: 0.03em;
            margin-bottom: 6px;
            display: block;
        }

        #distributeModal .select2-container {
            width: 100% !important;
        }
    </style>
@endsection

@section('script')
    <script>
        $(document).ready(function() {
            $('#recordsTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.10.24/i18n/Vietnamese.json'
                },
                order: [] // Keep backend sorting
            });
        });

        let distributeRoomOptions = null; // cache toàn bộ phòng active, lọc lại theo công đoạn ở client
        let distributeCurrentRecordId = null;

        // Suy ra mã công đoạn (1-7) từ section_id, khớp đúng logic phía server
        // (EbmrTemplateController::getRoomsConfig): lấy đoạn cuối sau dấu "_".
        // GF/MF/CO có hậu tố chữ (vd "_GF") -> không lọc theo công đoạn, hiện tất cả phòng.
        function getStageCodeFromSectionId(sectionId) {
            if (!sectionId) return null;
            const parts = String(sectionId).split('_');
            const code = parts[parts.length - 1];
            const num = parseInt(code, 10);
            if (!isNaN(num) && num >= 1 && num <= 7 && String(num) === code) return num;
            return null;
        }

        function openDistributeModal(recordId, sections) {
            distributeCurrentRecordId = recordId;
            const container = $('#distributeSectionsContainer');
            container.html('<div class="text-center py-4"><div class="spinner-border text-navy"></div></div>');
            $('#distributeModal').modal('show');

            const loadRoomOptions = distributeRoomOptions
                ? Promise.resolve(distributeRoomOptions)
                : $.get("{{ route('pages.ebmr.getRoomOptions') }}").then(function(rooms) {
                    distributeRoomOptions = rooms;
                    return rooms;
                });

            const loadExistingDist = $.get("{{ url('ebmr/records') }}/" + recordId + "/distribution");
            const loadWorkshopUsers = $.get("{{ url('ebmr/records') }}/" + recordId + "/workshop-users");

            Promise.all([loadRoomOptions, loadExistingDist, loadWorkshopUsers]).then(function(results) {
                const allRooms = results[0];
                const existing = results[1];
                const workshopUsers = results[2];
                const existingBySection = {};
                (existing || []).forEach(function(d) {
                    existingBySection[d.section_id] = d;
                });

                if (!sections || sections.length === 0) {
                    container.html('<div class="text-muted text-center py-3">Hồ sơ này không có công đoạn nào để phân phối.</div>');
                    return;
                }

                let html = '';
                sections.forEach(function(s, idx) {
                    const dist = existingBySection[s.id];
                    const selectedRoomId = dist ? String(dist.room_id) : '';
                    const selectedUserIds = dist ? (dist.user_ids || []).map(String) : [];

                    const stageCode = getStageCodeFromSectionId(s.id);
                    const rooms = stageCode !== null ?
                        allRooms.filter(r => parseInt(r.stage_code) === stageCode) : allRooms;
                    const noRoomsMatch = rooms.length === 0;

                    let roomOptionsHtml = '<option value=""></option>';
                    rooms.forEach(function(r) {
                        const sel = String(r.id) === selectedRoomId ? 'selected' : '';
                        roomOptionsHtml += `<option value="${r.id}" ${sel}>${r.code} - ${r.name}</option>`;
                    });

                    let userOptionsHtml = '';
                    workshopUsers.forEach(function(u) {
                        const sel = selectedUserIds.includes(String(u.id)) ? 'selected' : '';
                        userOptionsHtml += `<option value="${u.id}" ${sel}>${u.name}</option>`;
                    });

                    html += `
                        <div class="distribute-section-row ${noRoomsMatch ? 'no-rooms-warning' : ''}" data-section-id="${s.id}" data-section-label="${s.label}">
                            <div class="fw-bold text-navy mb-3" style="font-size: 1.05rem;"><i class="fas fa-layer-group me-2"></i> ${s.label}</div>
                            <div class="row g-3">
                                <div class="col-md-5">
                                    <label class="distribute-field-label">Phòng sản xuất${stageCode !== null ? ' (Công đoạn ' + stageCode + ')' : ''}</label>
                                    <select class="form-control dist-room-select" style="width:100%">${roomOptionsHtml}</select>
                                    ${noRoomsMatch ? '<div class="small text-danger mt-1"><i class="fas fa-exclamation-triangle me-1"></i>Không có phòng nào khớp công đoạn này</div>' : ''}
                                </div>
                                <div class="col-md-7">
                                    <label class="distribute-field-label">Người được phép ghi chép (theo phân xưởng)</label>
                                    <select class="form-control dist-user-select" multiple style="width:100%">${userOptionsHtml}</select>
                                </div>
                            </div>
                        </div>`;
                });

                container.html(html);

                // Khởi tạo select2 cho các select vừa render (phải chạy sau khi đã gắn vào DOM)
                $('#distributeModal .dist-room-select').select2({
                    theme: 'bootstrap4',
                    dropdownParent: $('#distributeModal'),
                    placeholder: '-- Chưa chọn phòng --',
                    allowClear: true
                });
                $('#distributeModal .dist-user-select').select2({
                    theme: 'bootstrap4',
                    dropdownParent: $('#distributeModal'),
                    placeholder: '-- Chọn người được phép ghi chép --',
                    closeOnSelect: false
                });
            }).catch(function() {
                container.html('<div class="text-danger text-center py-3">Không thể tải dữ liệu phân phối. Vui lòng thử lại.</div>');
            });
        }

        function submitDistribution() {
            const distributions = [];
            $('#distributeSectionsContainer .distribute-section-row').each(function() {
                const row = $(this);
                const roomId = row.find('.dist-room-select').val();
                if (!roomId) return; // bỏ qua công đoạn chưa chọn phòng

                const userIds = row.find('.dist-user-select').val() || [];

                distributions.push({
                    section_id: row.data('section-id').toString(),
                    section_label: row.data('section-label'),
                    room_id: roomId,
                    user_ids: userIds
                });
            });

            if (distributions.length === 0) {
                Swal.fire('Chưa chọn phòng', 'Vui lòng chọn ít nhất 1 phòng cho 1 công đoạn trước khi phân phối.', 'warning');
                return;
            }

            $.ajax({
                url: "{{ route('pages.ebmr.distributeSections') }}",
                method: 'POST',
                data: {
                    _token: "{{ csrf_token() }}",
                    record_id: distributeCurrentRecordId,
                    distributions: distributions
                },
                success: function(res) {
                    if (res.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Thành công',
                            text: res.message,
                            timer: 1800,
                            showConfirmButton: false
                        }).then(function() {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Không thể phân phối', res.message, 'error');
                    }
                },
                error: function() {
                    Swal.fire('Lỗi', 'Không thể kết nối máy chủ', 'error');
                }
            });
        }
    </script>
@endsection
