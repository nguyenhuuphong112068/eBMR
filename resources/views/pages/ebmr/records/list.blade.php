@extends('layout.master')

@section('title', $mode == 'history' ? 'Lịch Sử Ban Hành' : 'Hồ Sơ Sản Xuất')

@section('mainContent')
<div class="content-wrapper">
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <div class="card border-0 shadow-sm overflow-hidden">
                    <div class="card-header {{ $mode == 'history' ? 'bg-primary shadow-sm' : 'bg-navy' }} py-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 text-white fw-bold">
                            <i class="fas {{ $mode == 'history' ? 'fa-history' : 'fa-clipboard-list' }} me-2"></i> 
                            {{ $mode == 'history' ? 'Lịch Sử BMR Đã Ban Hành (Số Lô)' : 'Hồ Sơ Đã Nhận Ban Hành & Thực Hiện' }}
                        </h5>
                        <div class="badge bg-white {{ $mode == 'history' ? 'text-primary' : 'text-navy' }} rounded-pill px-3 shadow-sm">{{ $records->count() }} Hồ sơ</div>
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
                                        @if($mode != 'history')
                                        <th class="text-center">Thao tác</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($records as $index => $r)
                                    <tr>
                                        <td class="text-center fw-bold text-muted">{{ $index + 1 }}</td>
                                        <td>{{ $r->document_code }}</td>
                                        <td>{{ $r->template_name }}</td>
                                        <td class="fw-bold text-primary" style="font-size: 1.1rem;">{{ $r->batch_number }}</td>
                                        <td>
                                            <div class="d-flex flex-wrap gap-1">
                                                @forelse($r->sections as $s)
                                                    <a href="{{ route('pages.ebmr.execute', $r->id) }}?section={{ $s['id'] }}" 
                                                       class="badge bg-soft-info text-info border border-info rounded-pill py-1 px-2 text-decoration-none hover-glow" 
                                                       style="font-size: 0.75rem; cursor: pointer; transition: all 0.2s;">
                                                        <i class="fas fa-play-circle me-1"></i> {{ $s['label'] }}
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
                                            @if($r->status === 'active')
                                                <span class="badge bg-soft-success text-success border border-success"><i class="fas fa-spinner fa-spin me-1"></i> Đang sản xuất</span>
                                            @elseif($r->status === 'draft')
                                                <span class="badge bg-light text-muted border">Chưa bắt đầu</span>
                                            @else
                                                <span class="badge bg-soft-info text-info border border-info">{{ $r->status }}</span>
                                            @endif
                                        </td>
                                        @if($mode != 'history')
                                        <td class="text-center">
                                            <div class="btn-group shadow-sm rounded-pill overflow-hidden">
                                                <button class="btn btn-navy btn-sm px-3" title="Mở hồ sơ" onclick="window.location.href='{{ route('pages.ebmr.execute', $r->id) }}'">
                                                    @if(in_array($r->status, ['completed', 'reviewed']))
                                                        <i class="fas fa-eye me-1"></i> Xem hồ sơ
                                                    @else
                                                        <i class="fas fa-edit me-1"></i> Ghi chép
                                                    @endif
                                                </button>
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

<style>
    .bg-navy { background-color: #003A4F !important; }
    .text-navy { color: #003A4F !important; }
    .bg-soft-success { background-color: rgba(40, 167, 69, 0.1); }
    .bg-soft-info { background-color: rgba(23, 162, 184, 0.1); }
    .hover-glow:hover {
        background-color: #003A4F !important;
        color: #fff !important;
        box-shadow: 0 4px 10px rgba(0, 58, 79, 0.2);
        transform: translateY(-1px);
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
</script>
@endsection
