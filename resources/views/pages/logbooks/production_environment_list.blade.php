@extends('layout.master')

@section('title', 'Lịch Sử Môi Trường Sản Xuất')

@section('mainContent')
    <div class="content-wrapper">
        <div class="container-fluid py-4">
            <div class="d-flex align-items-center mb-4">
                <div class="p-3 bg-navy text-white rounded-3 shadow-sm me-3">
                    <i class="fas fa-thermometer-half fa-2x"></i>
                </div>
                <div>
                    <h3 class="mb-0 text-navy fw-bold">LỊCH SỬ MÔI TRƯỜNG SẢN XUẤT</h3>
                    <p class="text-muted mb-0 small">Nhiệt độ / Độ ẩm / Chênh áp ghi nhận theo từng phiên sản xuất (từ lúc Bắt đầu đến lúc Kết thúc sản xuất)</p>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="py-3 border-0">Phòng</th>
                                    <th class="py-3 border-0">Sản Phẩm</th>
                                    <th class="py-3 border-0">Công Đoạn</th>
                                    <th class="py-3 border-0">Số Lô</th>
                                    <th class="py-3 border-0">Nhân Sự Thực Hiện</th>
                                    <th class="py-3 border-0">Bắt Đầu SX</th>
                                    <th class="py-3 border-0">Kết Thúc SX</th>
                                    <th class="text-center py-3 border-0">Số Lần Ghi Nhận</th>
                                    <th class="text-center py-3 border-0">Vượt Ngưỡng</th>
                                    <th class="text-center py-3 border-0">Hành Động</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($sessions as $s)
                                    <tr>
                                        <td>
                                            <span class="badge bg-navy text-white font-monospace">{{ $s->room_code }}</span>
                                            <span class="small text-muted d-block mt-1">{{ $s->room_name }}</span>
                                        </td>
                                        <td class="small">{{ $s->product_name }}</td>
                                        <td class="small">{{ $s->section_label ?: '-' }}</td>
                                        <td><span class="badge bg-danger font-monospace">{{ $s->batch_number }}</span></td>
                                        <td class="small">{{ $s->executor_name }}</td>
                                        <td class="small">{{ \Carbon\Carbon::parse($s->started_at)->format('d/m/Y H:i') }}</td>
                                        <td class="small">
                                            @if ($s->production_ended_at)
                                                {{ \Carbon\Carbon::parse($s->production_ended_at)->format('d/m/Y H:i') }}
                                            @else
                                                <span class="badge bg-success"><i class="fas fa-cogs me-1"></i>Đang sản xuất</span>
                                            @endif
                                        </td>
                                        <td class="text-center fw-bold">{{ $s->reading_count }}</td>
                                        <td class="text-center">
                                            @if ($s->out_of_bounds_count > 0)
                                                <span class="badge bg-danger">{{ $s->out_of_bounds_count }} lần</span>
                                            @else
                                                <span class="badge bg-success"><i class="fas fa-check"></i></span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('pages.ebmr.logbooks.production_environment.show', $s->id) }}"
                                                class="btn btn-sm btn-outline-primary rounded-pill">
                                                <i class="fas fa-chart-line me-1"></i> Xem chi tiết
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center py-5 text-muted">
                                            <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                            Chưa có phiên sản xuất nào được ghi nhận.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if ($sessions->hasPages())
                    <div class="card-footer bg-white border-0 py-3">
                        {{ $sessions->links() }}
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
    </style>
@endsection
