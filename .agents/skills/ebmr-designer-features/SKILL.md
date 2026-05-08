---
name: ebmr-designer-features
description: Danh sách toàn bộ các tính năng hiện có của eBMR Designer để Agent tra cứu và cập nhật.
---

# Danh Sách Tính Năng eBMR Designer

Tài liệu này ghi lại toàn bộ các tính năng đã được triển khai trong trình soạn thảo hồ sơ (Designer) của hệ thống eBMR. Khi có tính năng mới được tạo ra, Agent **PHẢI** cập nhật ngay vào file này.

## 1. Cấu trúc và Quản lý Block (Khối)
- **Hệ thống Block-based**: Hồ sơ được cấu thành từ các khối độc lập (Section, Table, Variable, Content).
- **Thêm Phân đoạn (Section)**: Chia hồ sơ thành các giai đoạn sản xuất (Stage/Section).
- **Sắp xếp (Ordering)**: Có thể thay đổi thứ tự các block bằng cách kéo thả hoặc nút điều hướng.
- **Xóa Block**: Cho phép xóa các khối không cần thiết.

## 2. Soạn thảo Văn bản (Rich Text Editor - RTE)
- **Định dạng cơ bản**: Đậm, nghiêng, gạch chân, gạch ngang, chỉ số trên/dưới.
- **Cỡ chữ & Kiểu chữ**: Hỗ trợ từ H1 đến H4 và Paragraph. Thay đổi cỡ chữ linh hoạt (pt).
- **Màu sắc**: Bảng màu chủ đề và màu tùy chỉnh cho văn bản.
- **Căn lề**: Trái, giữa, phải, căn đều.
- **Danh sách**: Dạng dấu chấm (Unordered) và đánh số (Ordered).
- **Sao chép định dạng (Format Painter)**: Sao chép style từ đoạn này sang đoạn khác.
- **Xóa định dạng (Clear Formatting)**: Trả văn bản về trạng thái mặc định.
- **Ký hiệu đặc biệt (Symbols)**: Chèn ký hiệu Toán học, Hy Lạp và các ký tự đặc biệt khác.

## 3. Quản lý Biến số (Variables)
- **Loại biến hỗ trợ**:
    - `text`: Nhập văn bản.
    - `number`: Nhập số liệu.
    - `date`: Chọn ngày tháng.
    - `signature`: Chữ ký điện tử.
    - `checkbox`: Ô đánh dấu.
    - `select`: Danh sách lựa chọn.
    - `formula`: Thiết lập công thức tính toán tự động dựa trên các biến khác.
- **Danh sách biến số (Variable Summary)**: Bảng tổng hợp toàn bộ các biến trong hồ sơ (ID, Label, Loại).
- **ID Duy nhất**: Mỗi biến có một UUID hoặc mã ID định danh để truy xuất dữ liệu.

## 4. Quản lý Bảng (Tables)
- **Grid Selector**: Chọn nhanh kích thước bảng (ví dụ: 3x4) để chèn.
- **Gộp ô (Merge Cells)**: Gộp nhiều ô được chọn thành một.
- **Tách ô (Split Cells)**: Tách một ô thành nhiều hàng/cột.
- **Định dạng bảng**: Tùy chỉnh viền, màu nền ô.

## 5. Dữ liệu và Biểu đồ
- **Tạo Biểu đồ (Chart Creator)**: Tạo biểu đồ Đường (Line) hoặc Cột (Bar) từ dữ liệu trong bảng.
- **Hỗ trợ Matrix Chart**: Gộp dữ liệu từ nhiều hàng/cột thành chuỗi thời gian trên biểu đồ.

## 6. Tính năng Nâng cao & Công cụ
- **Tìm kiếm & Thay thế (Find & Replace)**: Giao diện giống Microsoft Word, hỗ trợ tìm tiếp theo, thay thế từng cái hoặc tất cả.
- **Nhập từ Word (.docx)**: Tự động chuyển đổi file Word thành các block trong Designer.
- **Chèn Biểu mẫu chung (Linked Template/GF)**: Nhúng các mẫu đã soạn sẵn vào hồ sơ hiện tại.
- **Bình luận (Commenting)**: Thêm bình luận vào từng vị trí văn bản hoặc block.
- **Lịch sử thay đổi (Revision History)**: Ghi lại log chi tiết các thay đổi (Thêm/Xóa/Sửa) giữa các phiên bản lưu.

## 7. Chế độ Xem và Ngôn ngữ
- **Chế độ Thiết kế vs. Chạy thử (Execution Mode)**: Chuyển đổi giữa việc soạn mẫu và giả lập ghi chép hồ sơ thực tế.
- **Hỗ trợ Đa ngôn ngữ**: 
    - Tiếng Việt (Gốc)
    - Tiếng Anh (Dịch)
    - Song ngữ (Xem đồng thời)
- **Dịch thuật AI**: Tích hợp tính năng dịch toàn bộ hồ sơ bằng AI.

## 8. In ấn và Lưu trữ
- **Lưu (Save)**: Lưu trạng thái hồ sơ vào Database.
- **Mở hồ sơ (Open)**: Danh sách các mẫu đã có để chỉnh sửa tiếp.
- **In hồ sơ (Print)**: Định dạng tối ưu cho việc in ấn văn bản hành chính.
