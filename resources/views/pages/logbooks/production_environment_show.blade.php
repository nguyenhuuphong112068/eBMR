@extends('layout.master')

@section('title', 'Lịch Sử Môi Trường Sản Xuất')

@section('mainContent')
    <div class="content-wrapper">
        <div class="container-fluid py-4">
            <div class="mb-3">
                <a href="{{ route('pages.ebmr.logbooks.production_environment') }}" class="btn btn-sm btn-outline-secondary rounded-pill shadow-sm">
                    <i class="fas fa-arrow-left me-1"></i> Quay lại
                </a>
            </div>
            <div class="d-flex align-items-center mb-4">
                <div class="p-3 bg-navy text-white rounded-3 shadow-sm me-3">
                    <i class="fas fa-thermometer-half fa-2x"></i>
                </div>
                <div>
                    <h3 class="mb-0 text-navy fw-bold">MÔI TRƯỜNG SẢN XUẤT — {{ $room->code ?? '' }}</h3>
                    <p class="text-muted mb-0 small">
                        {{ $room->name ?? '' }} — Lô <span class="font-monospace fw-bold">{{ $dist->batch_number }}</span>
                        @if ($dist->section_label) — {{ $dist->section_label }} @endif
                    </p>
                </div>
            </div>

            <!-- Session Info + Stats -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body p-3">
                            <div class="small text-muted fw-bold text-uppercase mb-1">Thời gian sản xuất</div>
                            <div class="small"><i class="fas fa-play text-success me-1"></i>{{ \Carbon\Carbon::parse($dist->started_at)->format('d/m/Y H:i') }}</div>
                            <div class="small">
                                @if ($dist->production_ended_at)
                                    <i class="fas fa-stop text-danger me-1"></i>{{ \Carbon\Carbon::parse($dist->production_ended_at)->format('d/m/Y H:i') }}
                                @else
                                    <span class="badge bg-success mt-1"><i class="fas fa-cogs me-1"></i>Đang sản xuất</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body p-3 text-center">
                            <div class="small text-muted fw-bold text-uppercase mb-1">Nhiệt độ (°C)</div>
                            <div class="fw-bold text-navy">{{ $stats['temp_min'] ?? '-' }} — {{ $stats['temp_max'] ?? '-' }}</div>
                            <div class="small text-muted">Ngưỡng: {{ $condition->temp_min_1 ?? 20 }} - {{ $condition->temp_max_1 ?? 25 }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body p-3 text-center">
                            <div class="small text-muted fw-bold text-uppercase mb-1">Độ ẩm (%) / Chênh áp (Pa)</div>
                            <div class="fw-bold text-navy">{{ $stats['humid_min'] ?? '-' }}—{{ $stats['humid_max'] ?? '-' }}% / {{ $stats['press_min'] ?? '-' }}—{{ $stats['press_max'] ?? '-' }}Pa</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm rounded-4 h-100 {{ $stats['out_of_bounds'] > 0 ? 'border-danger' : '' }}">
                        <div class="card-body p-3 text-center">
                            <div class="small text-muted fw-bold text-uppercase mb-1">Số lần vượt ngưỡng</div>
                            <div class="fw-bold {{ $stats['out_of_bounds'] > 0 ? 'text-danger' : 'text-success' }}" style="font-size: 1.4rem;">
                                {{ $stats['out_of_bounds'] }} / {{ $stats['count'] }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Readings Table -->
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white rounded-top-4 py-3 border-bottom">
                    <h6 class="mb-0 fw-bold text-navy"><i class="fas fa-list me-2"></i>Chi Tiết Từng Lần Ghi Nhận</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 65vh; overflow-y: auto;">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light" style="position: sticky; top: 0; z-index: 1;">
                                <tr>
                                    <th class="py-3 border-0">Thời điểm</th>
                                    <th class="text-center py-3 border-0">Nhiệt độ (°C)</th>
                                    <th class="text-center py-3 border-0">Độ ẩm (%)</th>
                                    <th class="text-center py-3 border-0">Chênh áp (Pa)</th>
                                    <th class="text-center py-3 border-0">Trạng thái</th>
                                    <th class="text-center py-3 border-0">Nguồn</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($readings as $r)
                                    <tr class="{{ $r->is_out_of_bounds ? 'table-danger' : '' }}">
                                        <td class="small font-monospace">{{ \Carbon\Carbon::parse($r->captured_at)->format('d/m/Y H:i:s') }}</td>
                                        <td class="text-center fw-bold">{{ $r->temperature }}</td>
                                        <td class="text-center fw-bold">{{ $r->humidity }}</td>
                                        <td class="text-center fw-bold">{{ $r->pressure }}</td>
                                        <td class="text-center">
                                            @if ($r->is_out_of_bounds)
                                                <span class="badge bg-danger"><i class="fas fa-exclamation-triangle me-1"></i>Vượt ngưỡng</span>
                                            @else
                                                <span class="badge bg-success"><i class="fas fa-check me-1"></i>Đạt</span>
                                            @endif
                                        </td>
                                        <td class="text-center small text-muted">{{ $r->recorded_type === 'manual' ? 'Thủ công' : 'Tự động' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-muted">
                                            <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                            Chưa có lần ghi nhận nào cho phiên sản xuất này.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('style')
    <style>
        .bg-navy { background-color: #003A4F !important; }
        .text-navy { color: #003A4F !important; }
    </style>
@endsection
