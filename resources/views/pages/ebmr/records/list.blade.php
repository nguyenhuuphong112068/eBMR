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
                            @php
                                // Tab "Nhận Ban Hành": phân loại hồ sơ theo phân xưởng của sản phẩm
                                $groupedRecords =
                                    $mode == 'working'
                                        ? $records->groupBy(fn($r) => $r->workshop ?: '__none__')->sortKeys()
                                        : collect(['__all__' => $records]);
                            @endphp
                            @if ($mode == 'working' && $groupedRecords->count() > 1)
                                <div class="d-flex align-items-center mb-4">
                                    <label for="workshopFilter" class="fw-bold text-navy me-2 mb-0">
                                        <i class="fas fa-industry me-1"></i> Phân xưởng:
                                    </label>
                                    <select id="workshopFilter" class="form-control shadow-sm" style="max-width: 320px;">
                                        @foreach ($groupedRecords as $workshopCode => $workshopRecords)
                                            <option value="{{ $workshopCode }}">
                                                {{ $workshopCode === '__none__' ? 'Chưa gắn phân xưởng' : 'Phân Xưởng ' . $workshopCode }}
                                                ({{ $workshopRecords->count() }} hồ sơ)
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                            @foreach ($groupedRecords as $workshopCode => $workshopRecords)
                                <div class="workshop-group" data-workshop="{{ $workshopCode }}">
                                    @if ($mode == 'working')
                                        <div class="workshop-group-header d-flex align-items-center mt-2 mb-3">
                                            <i class="fas fa-industry me-2"></i>
                                            <span class="fw-bold">
                                                {{ $workshopCode === '__none__' ? 'Chưa gắn phân xưởng' : 'Phân Xưởng ' . $workshopCode }}
                                            </span>
                                            <span
                                                class="badge bg-white text-navy rounded-pill ms-2">{{ $workshopRecords->count() }}
                                                hồ sơ</span>
                                        </div>
                                    @endif
                                    <div class="table-responsive mb-4">
                                        <table class="table table-hover align-middle recordsTable" style="width:100%">
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
                                                @foreach ($workshopRecords->values() as $index => $r)
                                                    <tr>
                                                        <td class="text-center fw-bold text-muted">{{ $index + 1 }}</td>
                                                        <td>{{ $r->document_code }}</td>
                                                        <td>{{ $r->template_name }}</td>
                                                        <td class="fw-bold text-danger"
                                                            style="font-size: 1.35rem; letter-spacing: 1px;">
                                                            {{ $r->batch_number }}</td>
                                                        <td>
                                                            <div class="d-flex flex-wrap gap-1">
                                                                @forelse($r->sections as $s)
                                                                    @php
                                                                        $sDist = $r->distributions->get($s['id']);
                                                                        // Công đoạn nối trang (member_ids > 1): gộp id bằng dấu phẩy để trang
                                                                        // Ghi chép (?section=id1,id2) hiển thị đúng nội dung gộp của cả trang in.
                                                                        $sSectionParam = implode(',', $s['member_ids'] ?? [$s['id']]);
                                                                    @endphp
                                                                    @php
                                                                        $sEnded = $sDist && !is_null($sDist->production_ended_at);
                                                                        $sBadgeClass = $sEnded
                                                                            ? 'bg-soft-secondary text-secondary border-secondary'
                                                                            : ($sDist ? 'bg-soft-success text-success border-success' : 'bg-soft-info text-info border-info');
                                                                        $sIcon = $sEnded ? 'fa-lock' : ($sDist ? 'fa-check-circle' : 'fa-play-circle');
                                                                        $sTitle = $sEnded
                                                                            ? 'Đã kết thúc sản xuất tại phòng ' . $sDist->room_code . ' — công đoạn đã khoá'
                                                                            : ($sDist ? 'Đã phân phối tới phòng ' . $sDist->room_code : 'Chưa phân phối');
                                                                    @endphp
                                                                    <a href="{{ route('pages.ebmr.execute', $r->id) }}?section={{ $sSectionParam }}{{ $mode == 'working' ? '&from=issuance' : '' }}"
                                                                        class="badge {{ $sBadgeClass }} border rounded-pill py-1 px-2 text-decoration-none hover-glow"
                                                                        style="font-size: 0.75rem; cursor: pointer; transition: all 0.2s;"
                                                                        title="{{ $sTitle }}">
                                                                        <i class="fas {{ $sIcon }} me-1"></i>
                                                                        {{ $s['label'] }}
                                                                        @if ($sDist)
                                                                            <span
                                                                                class="fw-bold">({{ $sDist->room_code }})</span>
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
                                                            @if (!empty($r->is_partial_completion))
                                                                <span
                                                                    class="badge bg-soft-warning text-warning border border-warning"
                                                                    title="Có công đoạn đã kết thúc sản xuất, các công đoạn khác vẫn đang ghi chép">
                                                                    <i class="fas fa-hourglass-half me-1"></i> Chờ hoàn
                                                                    thành</span>
                                                            @elseif ($r->status === 'active')
                                                                <span
                                                                    class="badge bg-soft-success text-success border border-success"><i
                                                                        class="fas fa-spinner fa-spin me-1"></i> Đang sản
                                                                    xuất</span>
                                                            @elseif($r->status === 'draft')
                                                                <span class="badge bg-light text-muted border">Chưa bắt
                                                                    đầu</span>
                                                            @else
                                                                <span
                                                                    class="badge bg-soft-info text-info border border-info">{{ $r->status }}</span>
                                                            @endif
                                                        </td>
                                                        @if ($mode != 'history')
                                                            <td class="text-center">
                                                                <div
                                                                    class="btn-group shadow-sm rounded-pill overflow-hidden">
                                                                    {{-- Trang này do văn thư phân xưởng quản lý — chỉ xem, chưa ghi
                                                                 chép được. Ghi chép chỉ mở khi đã Phân phối + phòng đủ
                                                                 điều kiện, thực hiện từ trang "Phòng Sản Xuất". --}}
                                                                    <button class="btn btn-navy btn-sm px-3"
                                                                        title="Xem hồ sơ (chỉ xem)"
                                                                        onclick="window.location.href='{{ route('pages.ebmr.execute', $r->id) }}{{ $mode == 'working' ? '?from=issuance' : '' }}'">
                                                                        <i class="fas fa-eye me-1"></i> Xem hồ sơ
                                                                    </button>
                                                                    @if ($mode == 'working')
                                                                        <button class="btn btn-outline-navy btn-sm px-3"
                                                                            title="Phân phối công đoạn tới phòng sản xuất"
                                                                            onclick='openDistributeModal({{ $r->id }}, {{ json_encode($r->sections) }}, {{ json_encode($r->workshop) }})'>
                                                                            <i class="fas fa-share-square me-1"></i> Phân
                                                                            phối
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
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Phân phối công đoạn tới phòng sản xuất -->
    <div class="modal fade" id="distributeModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-distribute modal-dialog-scrollable" role="document">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-navy text-white">
                    <h5 class="modal-title fw-bold"><i class="fas fa-share-square me-2"></i> Phân Phối Công Đoạn</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-0 bg-light">
                    <div class="distribute-split">
                        {{-- Cột trái: xem trước phân đoạn hồ sơ đang được chọn để phân phối --}}
                        <div class="distribute-preview-pane">
                            <div class="distribute-preview-header">
                                <span class="fw-bold text-navy">
                                    <i class="fas fa-eye me-1"></i> Xem trước phân đoạn
                                </span>
                                <span id="distributePreviewLabel"
                                    class="badge bg-soft-info text-info border border-info ms-2"></span>
                                <div class="btn-group btn-group-sm ms-auto" role="group">
                                    <button type="button" id="distributeTabPreview"
                                        class="btn btn-outline-navy active" onclick="switchDistributeTab('preview')">
                                        <i class="fas fa-eye me-1"></i> Xem trước
                                    </button>
                                    <button type="button" id="distributeTabHistory" class="btn btn-outline-navy"
                                        onclick="switchDistributeTab('history')">
                                        <i class="fas fa-history me-1"></i> Lịch sử phân phối
                                    </button>
                                </div>
                            </div>
                            <div class="distribute-preview-body">
                                <div id="distributePreviewEmpty" class="distribute-preview-empty">
                                    <i class="fas fa-mouse-pointer mb-2" style="font-size: 1.8rem; opacity: .5;"></i>
                                    <div>Chọn một công đoạn ở cột bên phải để xem trước nội dung hồ sơ.</div>
                                </div>
                                <div id="distributePreviewLoading" class="distribute-preview-empty" style="display:none;">
                                    <div class="spinner-border text-navy mb-2"></div>
                                    <div>Đang tải nội dung phân đoạn…</div>
                                </div>
                                <iframe id="distributePreviewFrame" class="distribute-preview-frame" style="display:none;"
                                    title="Xem trước phân đoạn hồ sơ"></iframe>
                                <div id="distributeHistoryPane" class="distribute-history-pane" style="display:none;"></div>
                            </div>
                        </div>
                        {{-- Cột phải: cấu hình phòng + người ghi chép cho từng công đoạn --}}
                        <div class="distribute-config-pane">

                            <div id="distributeSectionsContainer"></div>
                        </div>
                    </div>
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

        .workshop-group-header {
            background: linear-gradient(90deg, #003A4F, #00647f);
            color: #fff;
            padding: 10px 16px;
            border-radius: 8px;
            font-size: 0.95rem;
            box-shadow: 0 2px 6px rgba(0, 58, 79, 0.15);
        }

        .text-navy {
            color: #003A4F !important;
        }

        .btn-outline-navy {
            color: #003A4F;
            border-color: #003A4F;
        }

        .btn-outline-navy:hover,
        .btn-outline-navy.active {
            background-color: #003A4F;
            color: #fff;
        }

        .bg-soft-success {
            background-color: rgba(40, 167, 69, 0.1);
        }

        .bg-soft-info {
            background-color: rgba(23, 162, 184, 0.1);
        }

        .bg-soft-secondary {
            background-color: rgba(108, 117, 125, 0.1);
        }

        .bg-soft-warning {
            background-color: rgba(255, 193, 7, 0.15);
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

        /* Modal Phân phối mở rộng 95% màn hình */
        .modal-dialog-distribute {
            max-width: 95vw;
            width: 95vw;
            margin: 1.75rem auto;
        }

        .distribute-split {
            display: flex;
            align-items: stretch;
            min-height: 70vh;
        }

        .distribute-preview-pane {
            flex: 0 0 55%;
            max-width: 55%;
            display: flex;
            flex-direction: column;
            border-right: 1px solid #e2e8f0;
            background: #f8fafc;
        }

        .distribute-config-pane {
            flex: 1 1 45%;
            max-width: 45%;
            padding: 20px;
            overflow-y: auto;
            max-height: 82vh;
        }

        .distribute-preview-header {
            padding: 12px 18px;
            border-bottom: 1px solid #e2e8f0;
            background: #fff;
            display: flex;
            align-items: center;
        }

        .distribute-preview-body {
            position: relative;
            flex: 1 1 auto;
            min-height: 0;
        }

        .distribute-preview-frame {
            width: 100%;
            height: 100%;
            border: 0;
            background: #f1f3f4;
        }

        .distribute-preview-empty {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: #94a3b8;
            padding: 24px;
        }

        .distribute-section-row {
            cursor: pointer;
        }

        .distribute-history-pane {
            position: absolute;
            inset: 0;
            overflow-y: auto;
            background: #f8fafc;
        }

        .distribute-history-item {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-left: 4px solid #003A4F;
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 12px;
        }

        .distribute-history-item:first-child {
            border-left-color: #28a745;
        }

        .distribute-history-item .small strong {
            color: #003A4F;
        }

        .distribute-section-row.active-preview {
            border-left-color: #0d6efd;
            box-shadow: 0 4px 14px rgba(13, 110, 253, 0.18);
        }

        /* Trên màn hình hẹp: xếp dọc, preview nằm trên */
        @media (max-width: 992px) {
            .distribute-split {
                flex-direction: column;
            }

            .distribute-preview-pane,
            .distribute-config-pane {
                flex: 1 1 100%;
                max-width: 100%;
            }

            .distribute-preview-pane {
                border-right: 0;
                border-bottom: 1px solid #e2e8f0;
                min-height: 45vh;
            }
        }
    </style>
@endsection

@section('script')
    <script>
        $(document).ready(function() {
            // Mỗi phân xưởng có 1 bảng riêng -> khởi tạo theo class
            $('.recordsTable').each(function() {
                $(this).DataTable({
                    language: {
                        url: '//cdn.datatables.net/plug-ins/1.10.24/i18n/Vietnamese.json'
                    },
                    order: [] // Keep backend sorting
                });
            });

            // Chỉ hiển thị 1 phân xưởng tại 1 thời điểm, chọn qua dropdown
            const workshopFilter = $('#workshopFilter');
            if (workshopFilter.length) {
                function showWorkshop(code) {
                    $('.workshop-group').hide();
                    const group = $('.workshop-group[data-workshop="' + code + '"]').show();
                    // Bảng render lúc đang ẩn sẽ sai độ rộng cột -> tính lại khi hiện
                    group.find('.recordsTable').each(function() {
                        $(this).DataTable().columns.adjust();
                    });
                }
                workshopFilter.on('change', function() {
                    showWorkshop($(this).val());
                });
                showWorkshop(workshopFilter.val());
            }
        });

        let distributeRoomOptions = null; // cache toàn bộ phòng active, lọc lại theo công đoạn ở client
        let distributeCurrentRecordId = null;
        let distributeHistoryBySection = {}; // { section_id: [log, ...] } cache lịch sử phân phối, tải 1 lần khi mở modal
        let distributeWorkshopUsersCache = []; // cache danh sách người phân xưởng để đổi user_id -> tên trong lịch sử
        let distributeActiveSectionId = null;
        let distributeActiveTab = 'preview';

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

        function openDistributeModal(recordId, sections, workshopCode) {
            distributeCurrentRecordId = recordId;
            distributeActiveSectionId = null;
            const container = $('#distributeSectionsContainer');
            container.html('<div class="text-center py-4"><div class="spinner-border text-navy"></div></div>');
            $('#distributeModal').modal('show');

            const loadRoomOptions = distributeRoomOptions ?
                Promise.resolve(distributeRoomOptions) :
                $.get("{{ route('pages.ebmr.getRoomOptions') }}").then(function(rooms) {
                    distributeRoomOptions = rooms;
                    return rooms;
                });

            const loadExistingDist = $.get("{{ url('ebmr/records') }}/" + recordId + "/distribution");
            const loadWorkshopUsers = $.get("{{ url('ebmr/records') }}/" + recordId + "/workshop-users");
            const loadHistory = $.get("{{ url('ebmr/records') }}/" + recordId + "/distribution-history");

            Promise.all([loadRoomOptions, loadExistingDist, loadWorkshopUsers, loadHistory]).then(function(results) {
                const allRooms = results[0];
                const existing = results[1];
                const workshopUsers = results[2];
                const historyLogs = results[3] || [];
                distributeWorkshopUsersCache = workshopUsers || [];

                distributeHistoryBySection = {};
                historyLogs.forEach(function(log) {
                    if (!distributeHistoryBySection[log.section_id]) distributeHistoryBySection[log.section_id] = [];
                    distributeHistoryBySection[log.section_id].push(log);
                });

                switchDistributeTab('preview');

                const existingBySection = {};
                (existing || []).forEach(function(d) {
                    existingBySection[d.section_id] = d;
                });

                if (!sections || sections.length === 0) {
                    container.html(
                        '<div class="text-muted text-center py-3">Hồ sơ này không có công đoạn nào để phân phối.</div>'
                        );
                    return;
                }

                let html = '';
                sections.forEach(function(s, idx) {
                    const dist = existingBySection[s.id];
                    const selectedRoomId = dist ? String(dist.room_id) : '';
                    const selectedUserIds = dist ? (dist.user_ids || []).map(String) : [];

                    // Lọc phòng theo công đoạn (stage_code) và theo phân xưởng (deparment_code)
                    // của sản phẩm/hồ sơ này — chỉ hiện phòng thuộc đúng phân xưởng.
                    // GF/MF không gắn phân xưởng (workshopCode rỗng) -> không lọc theo phân xưởng.
                    const stageCode = getStageCodeFromSectionId(s.id);
                    const rooms = allRooms.filter(r =>
                        (stageCode === null || parseInt(r.stage_code) === stageCode) &&
                        (!workshopCode || r.deparment_code === workshopCode)
                    );
                    const noRoomsMatch = rooms.length === 0;

                    let roomOptionsHtml = '<option value=""></option>';
                    rooms.forEach(function(r) {
                        const sel = String(r.id) === selectedRoomId ? 'selected' : '';
                        roomOptionsHtml +=
                            `<option value="${r.id}" ${sel}>${r.code} - ${r.name}</option>`;
                    });

                    let userOptionsHtml = '';
                    workshopUsers.forEach(function(u) {
                        const sel = selectedUserIds.includes(String(u.id)) ? 'selected' : '';
                        userOptionsHtml += `<option value="${u.id}" ${sel}>${u.name}</option>`;
                    });

                    const histCount = (distributeHistoryBySection[s.id] || []).length;
                    const histBadge = histCount > 0 ?
                        `<span class="badge bg-soft-info text-info border border-info ms-2"><i class="fas fa-history me-1"></i>Đã phân phối ${histCount} lần</span>` :
                        '';
                    const memberIds = s.member_ids && s.member_ids.length ? s.member_ids : [s.id];
                    const joinedBadge = memberIds.length > 1 ?
                        `<span class="badge bg-soft-success text-success border border-success ms-2" title="Các công đoạn này nằm chung 1 trang in nên được phân phối chung tới 1 phòng"><i class="fas fa-link me-1"></i>Nối trang (${memberIds.length} công đoạn)</span>` :
                        '';

                    const displayLabel = s.display_label || s.label;
                    html += `
                        <div class="distribute-section-row ${noRoomsMatch ? 'no-rooms-warning' : ''}" data-section-id="${s.id}" data-section-label="${s.label}" data-section-display-label="${displayLabel}" data-member-ids='${JSON.stringify(memberIds)}' onclick="previewDistributeSection(this)">
                            <div class="fw-bold text-navy mb-3" style="font-size: 1.05rem;"><i class="fas fa-layer-group me-2"></i> ${displayLabel}${joinedBadge}${histBadge}</div>
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

                // Tự động xem trước công đoạn đầu tiên khi mở modal
                const firstRow = container.find('.distribute-section-row').first();
                if (firstRow.length) previewDistributeSection(firstRow[0]);
            }).catch(function() {
                container.html(
                    '<div class="text-danger text-center py-3">Không thể tải dữ liệu phân phối. Vui lòng thử lại.</div>'
                    );
            });
        }

        // Nạp phần xem trước (iframe) cho công đoạn được chọn ở cột bên phải.
        // Dùng lại chính trang Ghi chép hồ sơ ở chế độ chỉ xem (?from=issuance) + nhúng
        // (?embed=1) để ẩn khung điều hướng, chỉ hiển thị nội dung tài liệu.
        function previewDistributeSection(rowEl) {
            const row = $(rowEl);
            const sectionId = row.data('section-id');
            if (!sectionId || !distributeCurrentRecordId) return;

            // Bỏ qua nếu đang xem đúng công đoạn này rồi
            if (row.hasClass('active-preview')) return;

            $('#distributeSectionsContainer .distribute-section-row').removeClass('active-preview');
            row.addClass('active-preview');

            distributeActiveSectionId = String(sectionId);
            $('#distributePreviewLabel').text(row.data('section-display-label') || row.data('section-label') || '');

            // Đổi công đoạn -> luôn quay về tab "Xem trước" để tránh hiển thị lịch sử cũ của công đoạn trước.
            switchDistributeTab('preview');

            $('#distributePreviewEmpty').hide();
            $('#distributePreviewFrame').hide();
            $('#distributePreviewLoading').show();

            // Công đoạn nối trang (member-ids > 1): gộp id bằng dấu phẩy để trang thực thi
            // (?section=id1,id2) hiển thị gộp đúng nội dung của cả trang in.
            let memberIds = [];
            try {
                memberIds = JSON.parse(row.attr('data-member-ids') || '[]');
            } catch (e) {}
            const sectionParam = memberIds.length ? memberIds.join(',') : sectionId;

            const url = "{{ url('ebmr/execute') }}/" + distributeCurrentRecordId +
                "?section=" + encodeURIComponent(sectionParam) + "&from=issuance&embed=1";

            const frame = document.getElementById('distributePreviewFrame');
            frame.onload = function() {
                $('#distributePreviewLoading').hide();
                $('#distributePreviewFrame').show();
            };
            frame.src = url;
        }

        // Chuyển đổi giữa tab "Xem trước" (iframe nội dung hồ sơ) và tab "Lịch sử phân phối"
        // (ai phân phối, phân phối tới phòng nào, cho những ai, lần thứ mấy) của công đoạn đang chọn.
        function switchDistributeTab(tab) {
            distributeActiveTab = tab;
            $('#distributeTabPreview').toggleClass('active', tab === 'preview');
            $('#distributeTabHistory').toggleClass('active', tab === 'history');

            if (tab === 'history') {
                $('#distributePreviewEmpty, #distributePreviewLoading, #distributePreviewFrame').hide();
                $('#distributeHistoryPane').show();
                renderDistributeHistory(distributeActiveSectionId);
            } else {
                $('#distributeHistoryPane').hide();
                if (distributeActiveSectionId) {
                    $('#distributePreviewFrame').show();
                } else {
                    $('#distributePreviewEmpty').show();
                }
            }
        }

        // Hiển thị danh sách các lần phân phối/phân phối lại của 1 công đoạn: phòng, người
        // được phép ghi chép, người thực hiện phân phối và thời điểm — mới nhất hiển thị trước.
        function renderDistributeHistory(sectionId) {
            const pane = $('#distributeHistoryPane');
            const logs = (distributeHistoryBySection[sectionId] || []).slice().reverse();

            if (!logs.length) {
                pane.html(
                    '<div class="distribute-preview-empty" style="position:static; height:100%;">' +
                    '<i class="fas fa-history mb-2" style="font-size: 1.8rem; opacity: .5;"></i>' +
                    '<div>Công đoạn này chưa được phân phối lần nào.</div></div>'
                );
                return;
            }

            let html = '<div class="p-3">';
            logs.forEach(function(log) {
                const userNames = (log.user_ids || []).map(function(uid) {
                    const u = distributeWorkshopUsersCache.find(function(x) {
                        return String(x.id) === String(uid);
                    });
                    return u ? u.name : ('#' + uid);
                }).join(', ') || '<span class="text-muted">Không có</span>';

                const dt = log.distributed_at ? new Date(log.distributed_at.replace(' ', 'T')) : null;
                const dtStr = dt ? dt.toLocaleString('vi-VN') : '';
                const roomStr = log.room_name ?
                    ((log.room_code ? log.room_code + ' - ' : '') + log.room_name) :
                    '<span class="text-muted">—</span>';

                html += `
                    <div class="distribute-history-item">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="badge bg-navy text-white">Lần ${log.attempt_no}</span>
                            <span class="small text-muted"><i class="far fa-clock me-1"></i>${dtStr}</span>
                        </div>
                        <div class="small mb-1"><i class="fas fa-door-open me-1 text-navy"></i><strong>Phòng:</strong> ${roomStr}</div>
                        <div class="small mb-1"><i class="fas fa-users me-1 text-navy"></i><strong>Người được ghi chép:</strong> ${userNames}</div>
                        <div class="small"><i class="fas fa-user-check me-1 text-navy"></i><strong>Người phân phối:</strong> ${log.distributed_by_name || (log.distributed_by ? ('#' + log.distributed_by) : '—')}</div>
                    </div>`;
            });
            html += '</div>';
            pane.html(html);
        }

        function submitDistribution() {
            const distributions = [];
            $('#distributeSectionsContainer .distribute-section-row').each(function() {
                const row = $(this);
                const roomId = row.find('.dist-room-select').val();
                if (!roomId) return; // bỏ qua công đoạn chưa chọn phòng

                const userIds = row.find('.dist-user-select').val() || [];

                let memberIds = [];
                try {
                    memberIds = JSON.parse(row.attr('data-member-ids') || '[]');
                } catch (e) {}

                distributions.push({
                    section_id: row.data('section-id').toString(),
                    section_label: row.data('section-label'),
                    room_id: roomId,
                    user_ids: userIds,
                    member_section_ids: memberIds
                });
            });

            if (distributions.length === 0) {
                Swal.fire('Chưa chọn phòng', 'Vui lòng chọn ít nhất 1 phòng cho 1 công đoạn trước khi phân phối.',
                    'warning');
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
