@extends('layout.master')

@section('title', 'Nhật Ký Phòng')

@section('mainContent')
<div class="content-wrapper">
    <div class="container-fluid py-4">
        <div class="d-flex align-items-center mb-4">
            <div class="p-3 bg-navy text-white rounded-3 shadow-sm me-3">
                <i class="fas fa-clipboard-list fa-2x"></i>
            </div>
            <div>
                <h3 class="mb-0 text-navy fw-bold">NHẬT KÝ PHÒNG</h3>
                <p class="text-muted mb-0 small">Theo dõi lịch sử hoạt động, sản xuất và vệ sinh của các phòng</p>
            </div>
        </div>

        <!-- Workshop Filter -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-3 d-flex flex-wrap align-items-center gap-3">
                <h6 class="mb-0 fw-bold text-navy"><i class="fas fa-filter me-2"></i>Lọc theo phân xưởng:</h6>
                <div class="btn-group shadow-sm" role="group">
                    @foreach($workshopsList as $ws)
                        <a href="{{ route('pages.ebmr.logbooks.room', ['workshop' => $ws]) }}" 
                           class="btn btn-outline-primary {{ $workshop == $ws ? 'active' : '' }}">
                            <i class="fas fa-building me-1"></i> {{ $ws }}
                        </a>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Logbooks Table -->
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="text-center py-3 border-0">ID</th>
                                <th class="py-3 border-0">Thời Gian</th>
                                <th class="py-3 border-0">Mã Phòng</th>
                                <th class="py-3 border-0">Loại Hoạt Động</th>
                                <th class="py-3 border-0">Sản Phẩm / Lô</th>
                                <th class="py-3 border-0">Mức Độ VS</th>
                                <th class="py-3 border-0">Hạn Sạch</th>
                                <th class="text-center py-3 border-0">Trạng Thái (Trước -> Sau)</th>
                                <th class="text-center py-3 border-0">Người Thực Hiện</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($logbooks as $log)
                                <tr>
                                    <td class="text-center text-muted fw-bold">#{{ $log->id }}</td>
                                    <td>
                                        <div class="small fw-bold text-navy">{{ $log->start_time->format('d/m/Y H:i') }}</div>
                                        <div class="small text-muted">đến {{ $log->end_time ? $log->end_time->format('d/m/Y H:i') : 'Đang tiếp tục' }}</div>
                                    </td>
                                    <td>
                                        <span class="badge bg-navy text-white rounded-pill px-2 py-1 font-monospace">
                                            {{ isset($rooms[$log->room_id]) ? $rooms[$log->room_id]->code : 'N/A' }}
                                        </span>
                                    </td>
                                    <td>
                                        @php
                                            $actionStyles = [
                                                'producing' => ['color' => 'success', 'icon' => 'fa-cogs', 'text' => 'Sản Xuất'],
                                                'cleaning' => ['color' => 'warning', 'icon' => 'fa-broom', 'text' => 'Vệ Sinh'],
                                                'maintenance' => ['color' => 'danger', 'icon' => 'fa-tools', 'text' => 'Bảo Trì'],
                                                'idle' => ['color' => 'info', 'icon' => 'fa-pause-circle', 'text' => 'Trống'],
                                            ];
                                            $style = $actionStyles[$log->action_type] ?? ['color' => 'secondary', 'icon' => 'fa-question', 'text' => $log->action_type];
                                        @endphp
                                        <span class="badge bg-{{ $style['color'] }} rounded-pill px-3 py-1">
                                            <i class="fas {{ $style['icon'] }} me-1"></i> {{ $style['text'] }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($log->product_name)
                                            <div class="small fw-bold text-dark">{{ $log->product_name }}</div>
                                            <div class="small text-muted font-monospace"><i class="fas fa-barcode"></i> {{ $log->batch_number }}</div>
                                        @else
                                            <span class="text-muted small">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="small fw-bold {{ $log->clean_level ? 'text-primary' : 'text-muted' }}">
                                            {{ $log->clean_level ?: '-' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="small {{ $log->clean_expiry_date ? 'text-success fw-bold' : 'text-muted' }}">
                                            {{ $log->clean_expiry_date ? $log->clean_expiry_date->format('d/m/Y H:i') : '-' }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex align-items-center justify-content-center gap-2">
                                            <span class="badge bg-light text-muted border">{{ ucfirst($log->previous_status) }}</span>
                                            <i class="fas fa-arrow-right text-muted small"></i>
                                            <span class="badge bg-light text-navy border border-primary">{{ $log->current_status ? ucfirst($log->current_status) : 'Đang xử lý' }}</span>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        @php
                                            $emps = is_array($log->employee_ids) ? $log->employee_ids : json_decode($log->employee_ids, true);
                                        @endphp
                                        <span class="badge bg-light text-dark shadow-sm border">
                                            <i class="fas fa-users text-primary me-1"></i> {{ is_array($emps) ? count($emps) : 0 }} Người
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-5 text-muted">
                                        <i class="fas fa-inbox fa-3x mb-3 text-light"></i>
                                        <h5>Không có dữ liệu nhật ký phòng cho phân xưởng này.</h5>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($logbooks->hasPages())
                <div class="card-footer bg-white border-0 pt-3 pb-3">
                    {{ $logbooks->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@section('style')
<style>
    .bg-navy { background-color: #003A4F !important; }
    .text-navy { color: #003A4F !important; }
    .btn-outline-primary { border-color: #003A4F; color: #003A4F; }
    .btn-outline-primary:hover, .btn-outline-primary.active { background-color: #003A4F; color: white; border-color: #003A4F; }
    .table > :not(caption) > * > * { padding: 1rem 1rem; }
    thead.bg-light th { font-size: 0.85rem; font-weight: 700; color: #555; text-transform: uppercase; letter-spacing: 0.5px; }
</style>
@endsection
