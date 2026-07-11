@extends('layout.master')

@section('title', 'Chính Sách Chung')

@section('mainContent')
    <div class="content-wrapper">
        <div class="container-fluid py-4">
            <div class="d-flex align-items-center mb-4">
                <div class="p-3 bg-navy text-white rounded-3 shadow-sm me-3">
                    <i class="fas fa-sliders-h fa-2x"></i>
                </div>
                <div>
                    <h3 class="mb-0 text-navy fw-bold">CHÍNH SÁCH CHUNG</h3>
                    <p class="text-muted mb-0 small">Cấu hình các thông số chung áp dụng cho toàn hệ thống</p>
                </div>
            </div>

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show shadow-sm rounded-3" role="alert">
                    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
            @endif

            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white rounded-top-4 py-3 border-bottom">
                    <h6 class="mb-0 fw-bold text-navy"><i class="fas fa-industry me-2"></i>Môi Trường Sản Xuất</h6>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('pages.settings.general_policy.update') }}">
                        @csrf
                        @foreach ($settings as $key => $s)
                            <div class="row align-items-center mb-3 pb-3 border-bottom">
                                <div class="col-md-6">
                                    <label class="fw-bold text-navy mb-1" for="setting-{{ $key }}">{{ $s['label'] }}</label>
                                    <p class="text-muted small mb-0">{{ $s['description'] }}</p>
                                </div>
                                <div class="col-md-6">
                                    @if ($s['type'] === 'select')
                                        <select class="form-control" id="setting-{{ $key }}" name="{{ $key }}">
                                            @foreach ($s['options'] as $optValue => $optLabel)
                                                <option value="{{ $optValue }}" {{ (int) $s['value'] === $optValue ? 'selected' : '' }}>
                                                    {{ $optLabel }}
                                                </option>
                                            @endforeach
                                        </select>
                                    @endif
                                </div>
                            </div>
                        @endforeach

                        <div class="text-end">
                            <button type="submit" class="btn btn-navy px-4 fw-bold shadow-sm">
                                <i class="fas fa-save me-1"></i> Lưu Thay Đổi
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('style')
    <style>
        .bg-navy { background-color: #003A4F !important; }
        .text-navy { color: #003A4F !important; }
        .btn-navy { background-color: #003A4F !important; border-color: #003A4F !important; color: #fff !important; }
        .btn-navy:hover { background-color: #002837 !important; color: #fff !important; }
    </style>
@endsection
