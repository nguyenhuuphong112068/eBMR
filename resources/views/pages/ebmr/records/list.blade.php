@extends('layout.master')

@section('title', $mode == 'history' ? 'Lịch Sử Ban Hành' : 'Hồ Sơ Sản Xuất')

@section('mainContent')
<div class="content-wrapper">
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <div class="card border-0 shadow-sm overflow-hidden">
                    <div class="card-header {{ $mode == 'history' ? 'bg-secondary' : 'bg-navy' }} py-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 text-white fw-bold">
                            <i class="fas {{ $mode == 'history' ? 'fa-history' : 'fa-clipboard-list' }} me-2"></i> 
                            {{ $mode == 'history' ? 'Lịch Sử BMR Đã Ban Hành (Số Lô)' : 'Hồ Sơ Đã Nhận Ban Hành & Thực Hiện' }}
                        </h5>
                        <div class="badge bg-white {{ $mode == 'history' ? 'text-secondary' : 'text-navy' }} rounded-pill px-3">{{ $records->count() }} Hồ sơ</div>
                    </div>
                    <div class="card-body p-4">
                        <div class="table-responsive">
                            <table id="recordsTable" class="table table-hover align-middle" style="width:100%">
                                <thead class="bg-light text-navy">
                                    <tr>
                                        <th>Số Lô (Batch No.)</th>
                                        <th>Mã Hồ Sơ</th>
                                        <th>Tên Hồ Sơ Mẫu</th>
                                        <th>Người Ban Hành</th>
                                        <th>Ngày Ban Hành</th>
                                        <th>Trạng Thái</th>
                                        @if($mode != 'history')
                                        <th class="text-center">Thao tác</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($records as $r)
                                    <tr>
                                        <td class="fw-bold text-primary" style="font-size: 1.1rem;">{{ $r->batch_number }}</td>
                                        <td>{{ $r->document_code }}</td>
                                        <td>{{ $r->template_name }}</td>
                                        <td><i class="fas fa-user-tie me-1 text-muted"></i> {{ $r->issuer_name ?? 'Hệ thống' }}</td>
                                        <td><i class="far fa-calendar-alt me-1 text-muted"></i> {{ \Carbon\Carbon::parse($r->created_at)->format('d/m/Y H:i') }}</td>
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
                                                <button class="btn btn-navy btn-sm px-3" title="Mở hồ sơ ghi chép" onclick="window.location.href='{{ route('pages.ebmr.execute', $r->id) }}'">
                                                    <i class="fas fa-edit me-1"></i> Ghi chép
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
</style>
@endsection

@section('script')
<script>
    $(document).ready(function() {
        $('#recordsTable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.10.24/i18n/Vietnamese.json'
            },
            order: [[3, 'desc']] // Sort by created_at desc
        });
    });
</script>
@endsection
