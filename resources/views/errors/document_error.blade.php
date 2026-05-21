@extends('layout.master')

@section('title', 'Lỗi truy cập tài liệu')

@section('mainContent')
<div class="content-wrapper d-flex align-items-center justify-content-center" style="min-height: 80vh; background-color: #f4f6f9;">
    <div class="card shadow-lg p-5 text-center" style="max-width: 600px; border-radius: 16px; border: none; background-color: white;">
        <div class="mb-4">
            <i class="fas fa-file-pdf text-danger" style="font-size: 5rem; opacity: 0.8;"></i>
            <div style="margin-top: -20px; margin-left: 50px;">
                <i class="fas fa-exclamation-triangle text-warning" style="font-size: 2rem;"></i>
            </div>
        </div>
        <h3 class="text-dark font-weight-bold mb-3" style="font-family: 'Montserrat', 'Segoe UI', sans-serif;">KHÔNG TÌM THẤY TÀI LIỆU</h3>
        <p class="text-muted mb-4" style="font-size: 1.1rem; line-height: 1.6;">
            {{ $message ?? 'Đã xảy ra lỗi không xác định khi cố gắng truy cập file tài liệu từ máy chủ chia sẻ mạng.' }}
        </p>
        <div class="d-flex justify-content-center gap-3">
            <button onclick="window.close()" class="btn btn-navy btn-lg rounded-pill px-4 shadow-sm" style="min-width: 160px;">
                <i class="fas fa-times me-2"></i> Đóng Cửa Sổ
            </button>
            <a href="javascript:history.back()" class="btn btn-outline-secondary btn-lg rounded-pill px-4 shadow-sm ms-2" style="min-width: 160px;">
                <i class="fas fa-arrow-left me-2"></i> Quay Lại
            </a>
        </div>
    </div>
</div>
@endsection
