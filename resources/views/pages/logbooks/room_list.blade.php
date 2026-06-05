@extends('layout.master')

@section('title', 'Danh sách Sổ Nhật Ký Phòng')

@section('mainContent')
    <div class="content-wrapper">
        <div class="container-fluid py-4">
            <div class="d-flex align-items-center mb-4">
                <div class="p-3 bg-navy text-white rounded-3 shadow-sm me-3">
                    <i class="fas fa-book fa-2x"></i>
                </div>
                <div>
                    <h3 class="mb-0 text-navy fw-bold">DANH SÁCH SỔ NHẬT KÝ PHÒNG</h3>
                    <p class="text-muted mb-0 small">Chọn một phòng để xem sổ nhật ký của phòng đó</p>
                </div>
            </div>

            <!-- Workshop Filter -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-3 d-flex flex-wrap align-items-center gap-3">
                    <h6 class="mb-0 fw-bold text-navy"><i class="fas fa-filter me-2"></i>Lọc theo phân xưởng:</h6>
                    <div class="btn-group shadow-sm" role="group">
                        @foreach ($workshopsList as $ws)
                            <a href="{{ route('pages.ebmr.logbooks.room', ['workshop' => $ws]) }}"
                                class="btn btn-outline-primary {{ $workshop == $ws ? 'active' : '' }}">
                                <i class="fas fa-building me-1"></i> {{ $ws }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Notebooks Grid Grouped by Stage -->
            @forelse($rooms as $stage => $stageRooms)
                <div class="card border-0 shadow-sm rounded-4 mb-5">
                    <div class="card-header bg-stage-header rounded-top-4 py-3">
                        <h5 class="mb-0 fw-bold"><i class="fas fa-layer-group me-2"></i>{{ $stage ?: 'Công đoạn khác' }}
                        </h5>
                    </div>
                    <div class="card-body p-4 bg-light rounded-bottom-4">
                        <div class="row">
                            @foreach ($stageRooms as $room)
                                <div class="col-12 col-sm-6 col-md-4 col-lg-3 mb-4">
                                    <a href="{{ route('pages.ebmr.logbooks.room.show', ['room_id' => $room->id]) }}"
                                        class="text-decoration-none">
                                        <div
                                            class="card h-100 border-0 shadow-sm rounded-4 notebook-card position-relative overflow-hidden">
                                            <div class="notebook-binding"></div>
                                            <div class="notebook-stitch"></div>

                                            <div
                                                class="card-body p-4 text-center d-flex flex-column justify-content-center align-items-center card-body-content">
                                                <div class="mb-3">
                                                    <i class="fas fa-book-medical fa-3x text-navy opacity-75"></i>
                                                </div>
                                                <h5 class="fw-bold text-navy mb-1">{{ $room->code }}</h5>
                                                <p class="text-muted small mb-0">{{ $room->name ?? 'Phòng ' . $room->code }}
                                                </p>
                                            </div>
                                            <div class="card-footer border-0 text-center py-2 notebook-footer">
                                                <span class="small text-primary fw-bold">Mở sổ <i
                                                        class="fas fa-arrow-right ms-1"></i></span>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12 text-center py-5">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Không có phòng nào trong phân xưởng này.</h5>
                </div>
            @endforelse
        </div>
    </div>
@endsection

@section('style')
    <style>
        .bg-navy {
            background-color: #003A4F !important;
        }

        .text-navy {
            color: #003A4F !important;
        }

        .bg-stage-header {
            background-color: #116a82 !important; /* Sidebar active item color */
            color: white !important;
        }

        .btn-outline-primary {
            border-color: #003A4F;
            color: #003A4F;
        }

        .btn-outline-primary:hover,
        .btn-outline-primary.active {
            background-color: #003A4F;
            color: white;
            border-color: #003A4F;
        }

        .notebook-card {
            background: #ffffff;
            border: 1px solid #e0e0e0 !important;
            border-radius: 6px 12px 12px 6px !important;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            box-shadow: 4px 5px 12px rgba(0, 0, 0, 0.06) !important;
            position: relative;
        }

        .notebook-card::before {
            content: '';
            position: absolute;
            top: 0;
            bottom: 0;
            left: 24px;
            width: 1px;
            background: rgba(0, 58, 79, 0.1);
            z-index: 1;
        }

        .notebook-card:hover {
            transform: translateY(-6px);
            box-shadow: 6px 15px 25px rgba(0, 58, 79, 0.12) !important;
            border-color: rgba(0, 58, 79, 0.3) !important;
        }

        .notebook-binding {
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 22px;
            background: #003A4F;
            background-image: linear-gradient(90deg, rgba(255, 255, 255, 0.15) 0%, rgba(0, 0, 0, 0.15) 100%);
            box-shadow: inset -2px 0 4px rgba(0, 0, 0, 0.2), 2px 0 4px rgba(0, 0, 0, 0.05);
            border-right: 1px solid #002233;
            border-radius: 6px 0 0 6px;
            z-index: 2;
        }

        .notebook-stitch {
            position: absolute;
            left: 8px;
            top: 15px;
            bottom: 15px;
            width: 2px;
            background: repeating-linear-gradient(0deg,
                    rgba(255, 255, 255, 0.6),
                    rgba(255, 255, 255, 0.6) 8px,
                    transparent 8px,
                    transparent 16px);
            z-index: 3;
        }

        .watermark-icon {
            position: absolute;
            right: -10px;
            bottom: -15px;
            font-size: 6rem;
            color: rgba(0, 166, 90, 0.04);
            /* Medical green subtle watermark */
            z-index: 0;
            transform: rotate(-15deg);
        }

        .card-body-content {
            padding-left: 12px;
            z-index: 2;
            position: relative;
        }

        .pharma-badge {
            position: absolute;
            top: 12px;
            right: 12px;
            background-color: rgba(0, 166, 90, 0.1);
            color: #00A65A;
            font-size: 0.65rem;
            padding: 3px 8px;
            border-radius: 12px;
            font-weight: bold;
            z-index: 2;
        }

        .notebook-footer {
            background: rgba(0, 58, 79, 0.02) !important;
            border-top: 1px dashed #e0e0e0 !important;
            border-radius: 0 0 12px 0 !important;
            padding-left: 24px;
            z-index: 2;
            position: relative;
        }
    </style>
@endsection
