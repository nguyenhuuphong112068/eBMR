<style>
    /* Giao diện Bảng Xem Nhanh Công Thức theo chuẩn Google Docs / Báo cáo sản xuất */
    .recipe-modal-size {
        max-width: 90% !important;
        width: 96% !important;
    }

    .recipe-modal-content {
        background-color: #ffffff !important;
        border: none !important;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2) !important;
        overflow: hidden;
        font-family: inherit;
    }

    .recipe-header {
        background-color: #ffffff;
        border-bottom: 1px solid #e0e0e0;
        padding: 16px 28px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .recipe-header-left {
        display: flex;
        align-items: center;
        gap: 18px;
    }

    .recipe-logo {
        width: 40px;
        height: 40px;
        object-fit: contain;
    }

    .recipe-title-bar {
        display: flex;
        flex-direction: column;
    }

    .recipe-doc-name {
        font-size: 20px;
        font-weight: 600;
        color: #202124;
        margin: 0;
        line-height: 1.2;
    }

    .recipe-doc-status {
        font-size: 13px;
        color: #5f6368;
        margin-top: 4px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .recipe-close-btn {
        background: transparent;
        border: none;
        font-size: 26px;
        color: #5f6368;
        cursor: pointer;
        padding: 4px 14px;
        border-radius: 50%;
        transition: background 0.2s;
    }

    .recipe-close-btn:hover {
        background-color: #f1f3f4;
        color: #202124;
    }

    .recipe-body {
        padding: 36px 40px;
        background-color: #ffffff;
        overflow-y: auto;
        max-height: 84vh;
    }

    .recipe-page-title {
        font-size: 26px;
        font-weight: 700;
        color: #202124;
        margin-bottom: 8px;
        text-align: center;
        text-transform: uppercase;
    }

    .recipe-page-subtitle {
        font-size: 17px;
        font-weight: 500;
        color: #5f6368;
        margin-bottom: 36px;
        text-align: center;
        border-bottom: 2px solid #f1f3f4;
        padding-bottom: 18px;
    }

    .recipe-section-title {
        font-size: 18px;
        font-weight: 700;
        color: #1a73e8;
        margin-top: 32px;
        margin-bottom: 16px;
        text-transform: uppercase;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    /* Bảng công thức chuẩn theo hình ảnh yêu cầu */
    .recipe-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 15px;
        color: #202124;
        margin-bottom: 32px;
    }

    .recipe-table th,
    .recipe-table td {
        border: 1px solid #ced4da;
        padding: 12px 16px;
        vertical-align: middle;
    }

    .recipe-table th {
        background-color: #f8f9fa;
        font-weight: 700;
        text-align: center;
        font-size: 15px;
        color: #202124;
    }

    .recipe-table td {
        font-size: 15px;
        line-height: 1.6;
    }

    .recipe-table tr:hover {
        background-color: #f8f9fa;
    }

    .recipe-table sup {
        color: #0056b3;
        font-weight: 700;
        font-size: 11px;
        padding-left: 1px;
    }

    /* Tùy chỉnh thanh cuộn */
    .recipe-body::-webkit-scrollbar {
        width: 8px;
    }

    .recipe-body::-webkit-scrollbar-track {
        background: #f1f3f4;
    }

    .recipe-body::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 4px;
    }

    .recipe-body::-webkit-scrollbar-thumb:hover {
        background: #a8a8a8;
    }
</style>

<div class="modal fade" id="intermediateRecipeModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog recipe-modal-size" role="document">
        <div class="modal-content recipe-modal-content">

            <!-- Header Bar -->
            <div class="recipe-header">
                <div class="recipe-header-left">
                    <a href="{{ route('pages.general.home') }}">
                        <img src="{{ asset('img/iconstella.svg') }}" class="recipe-logo" alt="Logo">
                    </a>
                    <div class="recipe-title-bar">
                        <h4 class="recipe-doc-name">Bảng Công Thức</h4>

                    </div>
                </div>
                <button type="button" class="recipe-close-btn" data-dismiss="modal" aria-label="Đóng"
                    title="Đóng tài liệu">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Main Content -->
            <div class="recipe-body">
                <div class="recipe-page-title">CÔNG THỨC BÁN THÀNH PHẨM</div>
                <div class="recipe-page-subtitle" id="recipe_intermediate_code"></div>

                <!-- Bảng 1: Nguyên liệu pha chế -->
                <div class="recipe-section-title"><i class="fas fa-flask"></i> 1. NGUYÊN LIỆU PHA CHẾ</div>
                <div class="table-responsive" style="overflow: visible;">
                    <table class="recipe-table mb-1">
                        <thead>
                            <tr>
                                <th style="width: 5%;">STT</th>
                                <th style="width: 13%;">Mã NL</th>
                                <th style="width: 19%;">Thành phần</th>
                                <th style="width: 11%;">Chức năng</th>
                                <th style="width: 8%;">Tiêu chuẩn</th>
                                <th style="width: 12%;">Nhà sản xuất</th>
                                <th style="width: 11%;">1 viên (mg)</th>
                                <th style="width: 5%;">Tỉ lệ (%)</th>
                                <th style="width: 26%;" id="recipe_batch_header_0">Lô tiêu chuẩn</th>
                            </tr>
                        </thead>
                        <tbody id="recipe_table_body_type_0">
                            <!-- Dữ liệu Ajax -->
                        </tbody>
                    </table>
                </div>
                <div id="recipe_notes_type_0" class="recipe-notes mt-2 mb-4" style="font-size: 0.95rem; color: #5f6368;"></div>

                <!-- Bảng 2: Nguyên liệu khác -->
                <div class="recipe-section-title"><i class="fas fa-capsules"></i> 2. NGUYÊN LIỆU KHÁC (BAO PHIM/NANG)
                </div>
                <div class="table-responsive" style="overflow: visible;">
                    <table class="recipe-table mb-1">
                        <thead>
                            <tr>
                                <th style="width: 5%;">STT</th>
                                <th style="width: 13%;">Mã NL</th>
                                <th style="width: 19%;">Thành phần</th>
                                <th style="width: 11%;">Chức năng</th>
                                <th style="width: 8%;">Tiêu chuẩn</th>
                                <th style="width: 12%;">Nhà sản xuất</th>
                                <th style="width: 11%;">1 viên (mg)</th>
                                <th style="width: 5%;">Tỉ lệ (%)</th>
                                <th style="width: 26%;" id="recipe_batch_header_1">Lô tiêu chuẩn</th>
                            </tr>
                        </thead>
                        <tbody id="recipe_table_body_type_1">
                            <!-- Dữ liệu Ajax -->
                        </tbody>
                    </table>
                </div>
                <div id="recipe_notes_type_1" class="recipe-notes mt-2 mb-4" style="font-size: 0.95rem; color: #5f6368;"></div>

            </div>

        </div>
    </div>
</div>
