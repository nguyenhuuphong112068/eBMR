@extends('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection

@section('mainContent')
    <style>
        .text-navy { color: #003A4F !important; }
        .bg-navy { background-color: #003A4F !important; }

        .room-card {
            border-radius: 14px;
            border: 1px solid #e8ecf0;
            transition: all 0.25s;
            overflow: hidden;
        }
        .room-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.1);
        }
        .room-card-header {
            padding: 18px 20px 14px;
            background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
        }
        .room-card-body { padding: 16px 20px; }
        .badge-active { background: #dcfce7; color: #16a34a; border: 1px solid #bbf7d0; }
        .badge-inactive { background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0; }
    </style>

    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0 text-uppercase fw-bold text-navy" style="font-size: 1.15rem;">
                            Phòng Vệ Sinh Chung
                        </h1>
                    </div>
                    <div class="col-sm-6 text-end">
                        <button class="btn btn-primary rounded-pill px-4" onclick="$('#modalCreate').modal('show')">
                            <i class="fas fa-plus me-1"></i> Thêm Phòng VS Chung
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <section class="content">
            <div class="container-fluid">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle me-2"></i>{{ session('success') }}<button type="button" class="close" data-dismiss="alert">&times;</button></div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}<button type="button" class="close" data-dismiss="alert">&times;</button></div>
                @endif

                @if($rooms->isEmpty())
                    <div class="text-center py-5 text-muted">
                        <p class="mb-0">Chưa có phòng vệ sinh chung nào.</p>
                        <button class="btn btn-primary rounded-pill mt-3 px-4" onclick="$('#modalCreate').modal('show')">
                            <i class="fas fa-plus me-1"></i> Tạo phòng đầu tiên
                        </button>
                    </div>
                @else
                    <div class="row g-3">
                        @foreach($rooms as $room)
                            <div class="col-md-4 col-lg-3">
                                <div class="room-card shadow-sm h-100 d-flex flex-column">
                                    <div class="room-card-header">
                                        <div class="d-flex align-items-center gap-2">
                                            <div>
                                                <div class="text-white fw-bold" style="font-size: 1rem;">{{ $room->code }}</div>
                                                <div class="text-white opacity-75" style="font-size: 0.75rem;">{{ $room->area ?? 'Chưa xác định khu vực' }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="room-card-body flex-grow-1">
                                        <div class="fw-semibold text-navy mb-1">{{ $room->name }}</div>
                                        @if($room->description)
                                            <div class="text-muted small mb-2">{{ Str::limit($room->description, 60) }}</div>
                                        @endif
                                        <div class="d-flex align-items-center justify-content-between mt-2">
                                            <span class="badge {{ $room->status === 'active' ? 'badge-active' : 'badge-inactive' }} rounded-pill px-3 py-1" style="font-size: 0.7rem;">
                                                {{ $room->status === 'active' ? 'Hoạt động' : 'Dừng hoạt động' }}
                                            </span>
                                            @if($room->in_progress_count > 0)
                                                <span class="badge bg-warning text-dark rounded-pill px-2" style="font-size: 0.7rem;">
                                                    <i class="fas fa-tools me-1"></i> {{ $room->in_progress_count }} thiết bị đang VS
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="d-flex border-top" style="flex-shrink: 0;">
                                        <a href="{{ route('pages.manu_env.room_clearing.dashboard', $room->id) }}"
                                            class="btn btn-sm btn-link text-navy fw-semibold flex-grow-1 py-2 rounded-0"
                                            style="font-size: 0.8rem;">
                                            <i class="fas fa-tachometer-alt me-1"></i> Dashboard
                                        </a>
                                        <button class="btn btn-sm btn-link text-secondary py-2 rounded-0 border-start"
                                            onclick="openEdit({{ $room->id }}, '{{ addslashes($room->code) }}', '{{ addslashes($room->name) }}', '{{ addslashes($room->area ?? '') }}', '{{ addslashes($room->description ?? '') }}', '{{ $room->status }}')">
                                            <i class="fas fa-pen"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>
    </div>

    {{-- Modal Tạo mới --}}
    <div class="modal fade" id="modalCreate" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-navy text-white">
                    <h5 class="modal-title"><i class="fas fa-plus me-2"></i> Thêm Phòng VS Chung</h5>
                    <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Mã phòng <span class="text-danger">*</span></label>
                        <input type="text" id="create-code" class="form-control" placeholder="VD: VS-CHUNG-01">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Tên phòng <span class="text-danger">*</span></label>
                        <input type="text" id="create-name" class="form-control" placeholder="VD: Phòng Vệ Sinh Chung A">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Khu vực / Phân xưởng</label>
                        <input type="text" id="create-area" class="form-control" placeholder="VD: Phân Xưởng 1">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Mô tả</label>
                        <textarea id="create-description" class="form-control" rows="2" placeholder="Mô tả ngắn gọn..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-primary px-4" onclick="createRoom()">
                        <i class="fas fa-save me-1"></i> Lưu
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Chỉnh sửa --}}
    <div class="modal fade" id="modalEdit" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-navy text-white">
                    <h5 class="modal-title"><i class="fas fa-pen me-2"></i> Chỉnh Sửa Phòng VS Chung</h5>
                    <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="edit-id">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Mã phòng <span class="text-danger">*</span></label>
                        <input type="text" id="edit-code" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Tên phòng <span class="text-danger">*</span></label>
                        <input type="text" id="edit-name" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Khu vực / Phân xưởng</label>
                        <input type="text" id="edit-area" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Mô tả</label>
                        <textarea id="edit-description" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Trạng thái</label>
                        <select id="edit-status" class="form-select">
                            <option value="active">Hoạt động</option>
                            <option value="inactive">Dừng hoạt động</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-primary px-4" onclick="updateRoom()">
                        <i class="fas fa-save me-1"></i> Cập nhật
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script>
    const CSRF = '{{ csrf_token() }}';

    function createRoom() {
        const code = $('#create-code').val().trim();
        const name = $('#create-name').val().trim();
        if (!code || !name) { Swal.fire('Thiếu thông tin', 'Vui lòng nhập Mã và Tên phòng.', 'warning'); return; }

        $.ajax({
            url: '{{ route("pages.manu_env.room_clearing.store") }}',
            method: 'POST',
            data: { _token: CSRF, code, name, area: $('#create-area').val(), description: $('#create-description').val() },
            success: res => {
                if (res.success) {
                    Swal.fire({ icon: 'success', title: 'Thành công!', text: res.message, timer: 1500, showConfirmButton: false })
                        .then(() => location.reload());
                } else {
                    Swal.fire('Lỗi', res.message || 'Không thể tạo phòng.', 'error');
                }
            },
            error: xhr => { Swal.fire('Lỗi', xhr.responseJSON?.message || 'Lỗi không xác định.', 'error'); }
        });
    }

    function openEdit(id, code, name, area, description, status) {
        $('#edit-id').val(id);
        $('#edit-code').val(code);
        $('#edit-name').val(name);
        $('#edit-area').val(area);
        $('#edit-description').val(description);
        $('#edit-status').val(status);
        $('#modalEdit').modal('show');
    }

    function updateRoom() {
        const id   = $('#edit-id').val();
        const code = $('#edit-code').val().trim();
        const name = $('#edit-name').val().trim();
        if (!code || !name) { Swal.fire('Thiếu thông tin', 'Vui lòng nhập Mã và Tên phòng.', 'warning'); return; }

        $.ajax({
            url: `/manu_env/room-clearing/${id}`,
            method: 'PUT',
            data: {
                _token: CSRF, code, name,
                area: $('#edit-area').val(),
                description: $('#edit-description').val(),
                status: $('#edit-status').val()
            },
            success: res => {
                if (res.success) {
                    Swal.fire({ icon: 'success', title: 'Đã cập nhật!', text: res.message, timer: 1500, showConfirmButton: false })
                        .then(() => location.reload());
                } else {
                    Swal.fire('Lỗi', res.message || 'Không thể cập nhật.', 'error');
                }
            },
            error: xhr => { Swal.fire('Lỗi', xhr.responseJSON?.message || 'Lỗi không xác định.', 'error'); }
        });
    }
</script>
@endsection
