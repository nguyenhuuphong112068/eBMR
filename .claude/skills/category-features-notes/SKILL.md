---
name: category-features-notes
description: Lưu trữ các chức năng và lưu ý quan trọng khi lập trình và bảo trì module Category (Danh mục bán thành phẩm, v.v.) trong eBMR.
---

# 📚 Category Features & Development Notes

Kỹ năng này ghi lại các thông tin nghiệp vụ, cấu trúc dữ liệu, và lưu ý quan trọng khi thao tác với module **Category (Danh mục)** trong hệ thống eBMR, đặc biệt là `Intermediate Category` (Danh mục Bán thành phẩm).

## 1. Cấu trúc CSDL (Database Schema)

### 1.1. Bảng `intermediate_category`
- Lưu trữ thông tin cơ bản về bán thành phẩm: Tên, mã, hàm lượng, dạng bào chế, cỡ lô, công đoạn bao gồm (Cân, Pha chế, Trộn, Định hình, Bao phim), điều kiện bảo quản.

### 1.2. Bảng `preparation_formula` (Công thức BOM)
Lưu thông tin công thức pha chế nguyên liệu.
- `intermediate_category_id`: Khóa ngoại liên kết với Bán thành phẩm.
- `type`: Phân loại nguyên liệu (**0**: Nguyên liệu cho quá trình pha chế viên, **1**: Nguyên liệu Bao phim/Nang).
- `code`: Mã nguyên liệu.
- `name`: Thành phần/Tên nguyên liệu.
- `role`: Chức năng (Tá dược độn, Tá dược dính, v.v.).
- `manufacturer`: Nhà sản xuất.
- `Spec`: Tiêu chuẩn (Ph.Eur., USP, v.v.).
- `total_amount_per_unit`: Khối lượng/1 đơn vị (viên/mg).
- `total_amount_per_batch`: Khối lượng/1 Lô tiêu chuẩn (kg).

### 1.3. Bảng `ingredient_amount`
Lưu chi tiết các phép tính khối lượng nguyên liệu trong 1 lô (breakdown chi tiết nếu cần thiết).

## 2. Các Lưu Ý Kỹ Thuật Khi Phát Triển UI/UX

- **Sử dụng Layout chia cột**: Modal "Tạo mới/Cập nhật" Danh mục thường chứa rất nhiều dữ liệu. Để tối ưu, sử dụng `max-width: 95%` cho `modal-dialog` và chia làm 2 cột: Cột trái (Thông tin cơ bản) - `col-lg-4`, Cột phải (BOM) - `col-lg-8`.
- **Trình soạn thảo văn bản (Summernote)**:
  - Khi khởi tạo Summernote trong Modal, thẻ `div` phải được sử dụng thay vì `textarea` để tránh lỗi đụng độ CSS.
  - Phải có logic `z-index: 1060 !important` cho thẻ `.note-modal` để hộp thoại phụ (như chèn link/ảnh) không bị chìm dưới modal chính.
  - Để lấy dữ liệu khi submit, phải copy `code` từ Summernote sang 1 `input type="hidden"` lúc submit form.
  - Tắt bo góc (border-radius: 0) để tạo cảm giác giao diện vuông vắn, cứng cáp.

## 3. Quản lý Thêm/Xóa Động (Dynamic Form)

- **Thêm dòng BOM động**: Sử dụng JS để thêm mới/xóa dòng trong bảng BOM. Index của mảng input (`bom[index][field]`) cần được duy trì độc lập và tăng liên tục để PHP backend tự động map thành mảng khi gửi request (`$request->bom`).
- **Phân loại BOM**: Trong bảng thêm mới BOM, phải có trường phân loại (`type`) bằng `<select>` để chọn đúng loại (0: Pha chế, 1: Bao phim) đồng bộ với Migration.
