@extends('layout.master') {{-- Kế thừa layout gốc của hệ thống (Master Layout) --}}

@section('title', 'eR Editor (Document Style)') {{-- Đặt tiêu đề cho trang web hiển thị trên trình duyệt --}}

@section('topNAV') {{-- Định nghĩa vùng nội dung cho thanh điều hướng phía trên --}}
    @include('layout.topNAV') {{-- Nhúng file thanh điều hướng trên cùng --}}
@endsection

@section('leftNAV') {{-- Định nghĩa vùng nội dung cho thanh menu bên trái --}}
    @include('layout.leftNAV') {{-- Nhúng file thanh menu điều hướng trái --}}
@endsection

@section('mainContent') {{-- Bắt đầu nội dung chính của trang thiết kế --}}

    <div class="content-wrapper hide-comment-highlights" id="mainContent" style="background-color: #f1f3f4; min-height: 100vh;"> {{-- Vùng bao quanh toàn bộ nội dung, đặt màu nền xám nhạt, mặc định ẩn bình luận --}}
        @include('pages.ebmr.designer.partials.toolbar') {{-- Nhúng thanh công cụ (Toolbar) chứa các nút Bold, Italic, Chèn bảng... --}}
        @include('pages.ebmr.designer.partials.canvas') {{-- Nhúng vùng làm việc chính (Canvas) nơi hiển thị trang giấy A4 --}}
        @include('pages.ebmr.designer.partials.criteria_sidebar') {{-- Nhúng thanh trượt liên kết tiêu chuẩn --}}
        @include('pages.ebmr.designer.partials.properties_sidebar') {{-- Nhúng thanh quản lý thuộc tính tài liệu --}}
    </div>

    <script> {{-- Khởi tạo các biến Javascript toàn cục từ dữ liệu phía Backend (Laravel) --}}
        window.isReadOnly = {{ $isReadOnly ? 'true' : 'false' }}; {{-- Biến kiểm tra xem người dùng chỉ được xem hay có quyền sửa --}}
        window.templateComments = @json($comments); {{-- Chuyển danh sách các phản hồi/comment từ PHP sang định dạng JSON cho Javascript xử lý --}}
        window.importantVars = @json($importantVars ?? []); {{-- Danh sách các biến số quan trọng (CPP, CMA...) --}}
        window.isAdmin = {{ $isAdmin ? 'true' : 'false' }}; {{-- Quyền Admin của người dùng --}}
    </script>

    @include('pages.ebmr.designer.partials.modals') {{-- Nhúng các cửa sổ popup (Modals) như: chèn ảnh, cấu hình bảng, AI translate... --}}
    @include('pages.ebmr.designer.partials.master_form_modal')
    @include('pages.ebmr.designer.partials.styles') {{-- Nhúng các định dạng CSS riêng cho trình thiết kế (layout trang giấy, hiệu ứng hover...) --}}

@endsection

@section('script')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> {{-- Nhúng thư viện SweetAlert2 v11 để hỗ trợ showDenyButton và các tính năng mới --}}
    
    {{-- Nhóm các Module Script - Chia nhỏ code Javascript để dễ quản lý --}}
    @include('pages.ebmr.designer.scripts.state') {{-- Quản lý trạng thái dữ liệu (mảng items, lịch sử undo/redo) --}}
    @include('pages.ebmr.designer.scripts.render') {{-- Chứa hàm renderBlocks() để vẽ dữ liệu ra màn hình --}}
    @include('pages.ebmr.designer.scripts.table_ops') {{-- Các thao tác cơ bản với bảng (thêm/xóa hàng cột) --}}
    @include('pages.ebmr.designer.scripts.table_advanced') {{-- Các thao tác bảng nâng cao (gộp ô, tách ô) --}}
    @include('pages.ebmr.designer.scripts.ui_handlers') {{-- Xử lý các sự kiện giao diện (click, ẩn hiện sidebar, ruler) --}}
    @include('pages.ebmr.designer.scripts.variable_ops') {{-- Quản lý các biến số động và thẻ badge trong tài liệu --}}
    @include('pages.ebmr.designer.scripts.batch_field_ops') {{-- Các thao tác hàng loạt cho các thẻ biến --}}
    @include('pages.ebmr.designer.scripts.persistence') {{-- Xử lý lưu trữ dữ liệu (gửi request AJAX lên server để lưu DB) --}}
    @include('pages.ebmr.designer.scripts.outline') {{-- Xử lý thanh mục lục bên trái (Outline) để điều hướng nhanh --}}
    @include('pages.ebmr.designer.scripts.comments') {{-- Xử lý tính năng phản hồi/comment trên từng khối nội dung --}}
    @include('pages.ebmr.designer.scripts.chart_ops') {{-- Xử lý hiển thị và cấu hình biểu đồ (Charts) --}}
    @include('pages.ebmr.designer.scripts.events') {{-- Quản lý các sự kiện phím tắt (Ctrl+S, Ctrl+Z) và các sự kiện chung --}}
    @include('pages.ebmr.designer.scripts.symbol_ops') {{-- Xử lý chèn các ký tự đặc biệt (Symbol Picker) --}}
    @include('pages.ebmr.designer.scripts.import_word') {{-- Xử lý import file Word (.docx) --}}
    @include('pages.ebmr.designer.scripts.import_master')
    @include('pages.ebmr.designer.scripts.scale_reader') {{-- Tích hợp đọc dữ liệu từ Cân Điện Tử qua RS-232 --}}
    @include('pages.ebmr.designer.scripts.properties') {{-- Xử lý Document Properties đồng bộ --}}
    @include('pages.ebmr.designer.scripts.split_view') {{-- Tính năng chia đôi màn hình (Split View) so sánh tài liệu --}}
@endsection
