---
name: ebmr-designer-features
description: Danh sách toàn bộ các tính năng hiện có của eBMR Designer để Agent tra cứu và cập nhật.
---

# Danh Sách Tính Năng eBMR Designer

Tài liệu này ghi lại toàn bộ các tính năng đã được triển khai trong trình soạn thảo hồ sơ (Designer) của hệ thống eBMR. Khi có tính năng mới được tạo ra, Agent **PHẢI** cập nhật ngay vào file này.

---

## Cấu trúc Thanh Công Cụ (Toolbar)

Toolbar được tổ chức theo kiểu **Ribbon** gồm 2 hàng, mỗi hàng chia thành các **nhóm chức năng** với nhãn nhỏ bên dưới.

### Hàng 1 — Hành động chính

| Nhóm | Chức năng |
|---|---|
| **Lịch Sử** | Undo (Ctrl+Z), Redo (Ctrl+Y) |
| **Định Dạng** | Sao chép định dạng (Format Painter), Xóa định dạng \| Copy khối, Cut khối, Paste khối \| Chèn Bảng (Grid), Gộp ô, Tách ô |
| **Chèn** | Mô tả (static-text), BM Chung, Bảng KT KL TB, Thêm Phân đoạn, Nhập từ MF, Lặp nhóm khối \| Nhập từ Word \| Ký hiệu đặc biệt (modal), Liên kết PDF, Hủy liên kết |
| **Biến Số** | Chèn Biến số (dropdown: text/number/date/signature/checkbox/select/formula), Dán Biến số, Liên kết Tiêu chuẩn, Danh sách Biến số |
| **Hồ Sơ** | Mở hồ sơ, Lịch sử thay đổi, In hồ sơ, Thuộc tính tài liệu |

Góc phải (ms-auto): N/A Vùng Chọn (ẩn mặc định), Chuyển chế độ (Thiết kế/Chạy thử), Thay đổi chế độ xem, Lưu hồ sơ.

### Hàng 2 — Định dạng văn bản

| Nhóm | Chức năng |
|---|---|
| **Kiểu & Cỡ** | Kiểu tài liệu (H1–H4, Đoạn văn), Giảm cỡ, Cỡ chữ (dropdown), Tăng cỡ |
| **Định Dạng** | B, I, U, S, Chỉ trên (X²), Chỉ dưới (X₂), Đổi kiểu chữ (Aa), Màu chữ |
| **Căn Lề** | Canh trái, giữa, phải, đều; Hướng chữ (ngang/dọc xuống/dọc lên) |
| **Danh Sách & Ghi Chú** | Bullet list (nhiều ký tự), Danh sách số; Thêm ghi chú, Bình luận, Hiện/Ẩn bình luận, Split View |
| **Ký Hiệu & Ảnh** | Quick symbols (α β γ Δ ° ± ≤ ≥ µ ©), Chữ viết tắt, Chèn hình ảnh |

### CSS quan trọng của Toolbar
```css
.toolbar-group          { display:flex; flex-direction:column; align-items:center; padding:0 3px; }
.toolbar-group-btns     { display:flex; align-items:center; gap:2px; }
.toolbar-group-label    { font-size:9px; color:#94a3b8; text-transform:uppercase; margin-top:3px; }
.toolbar-group-sep      { width:1px; background:#e2e8f0; align-self:stretch; margin:0 5px; }
.toolbar-mini-sep       { display:inline-block; width:1px; height:18px; background:#e2e8f0; margin:0 3px; }
```

### Tính năng ĐÃ BỎ
- ~~Hỗ trợ Tiếng Anh / Song ngữ / Dịch AI~~: Bị xóa. Hệ thống chỉ dùng Tiếng Việt.
  - ~~`setLanguageMode()`~~ trong `persistence.blade.php`
  - ~~`translateCurrentDocument()`~~ trong `persistence.blade.php`
  - `window.currentLangMode` được giữ lại nhưng hardcode = `'vi'`.

---

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
- **Sao chép định dạng (Format Painter)**: Sao chép style từ đoạn này sang đoạn khác. Hỗ trợ **single-click** (áp dụng 1 lần, tự tắt) và **double-click** (khoá chế độ, dán nhiều lần, nhấn Esc để tắt). Lấy đúng `inline style` từ DOM thay vì `getComputedStyle`. Sync về model qua `oninput()`.
- **Xóa định dạng (Clear Formatting)**: Trả văn bản về trạng thái mặc định. Sync model qua `oninput()`.
- **Ký hiệu đặc biệt (Symbols)**: Chèn ký hiệu Toán học, Hy Lạp và các ký tự đặc biệt khác (modal đầy đủ hoặc quick dropdown).

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
- **Đánh dấu N/A (Không áp dụng) - Context Menu**: Trong chế độ Execution, right-click vào ô trong bảng để đánh dấu N/A.
- **Đánh dấu N/A Vùng chọn (Gạch chéo Z)**: Quét vùng nhiều ô (bằng Shift/Ctrl + Click) và sử dụng công cụ "N/A Vùng Chọn" để gạch chéo.
- **Logic Điều kiện N/A (Conditional Logic)**: Cấu hình điều kiện phụ thuộc ở Sidebar để tự động đóng băng và hiển thị N/A.

## 7. Chế độ Xem
- **Chế độ Thiết kế vs. Chạy thử (Execution Mode)**: Chuyển đổi giữa việc soạn mẫu và giả lập ghi chép hồ sơ thực tế.
- **Chế độ Xem Tất cả vs. Xem 1 Phân đoạn**: Toggle giữa xem toàn bộ hồ sơ hoặc chỉ xem phân đoạn đang làm.
- **Split View**: Chia đôi màn hình soạn thảo.
- **Ngôn ngữ**: Chỉ Tiếng Việt (tính năng đa ngôn ngữ đã bị loại bỏ).

## 8. In ấn và Lưu trữ
- **Lưu (Save)**: Lưu trạng thái hồ sơ vào Database (incremental save – chỉ gửi các block bị dirty).
- **Mở hồ sơ (Open)**: Danh sách các mẫu đã có để chỉnh sửa tiếp.
- **In hồ sơ (Print)**: Định dạng tối ưu cho việc in ấn văn bản hành chính.
