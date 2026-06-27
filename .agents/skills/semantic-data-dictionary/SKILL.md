---
name: Semantic Data Dictionary
description: "Quy tắc và thuật toán để bóc tách biến số eBMR thành Cây dữ liệu ngữ cảnh (Semantic Data Tree) hỗ trợ làm báo cáo tự động (PVR/PQR)"
---

# Semantic Data Dictionary (Từ điển dữ liệu Ngữ cảnh)

Kỹ năng này mô tả cơ chế trích xuất "Đường dẫn ngữ cảnh" (Semantic Path) cho từng ô nhập liệu (cell_id) trong hệ thống eBMR, giúp thay thế việc gán tag thủ công bằng thuật toán bóc tách cấu trúc văn bản.

## Mục đích
Khi tạo Form BMR, sẽ có hàng ngàn biến số (`field_xxx`). Nếu muốn tái sử dụng các biến số này ở một báo cáo độc lập khác (Ví dụ báo cáo PVR - Process Validation Report hoặc PQR - Product Quality Review), việc tra cứu ID là bất khả thi. Thay vì người dùng phải gán "Data Tag" cho từng biến, hệ thống sẽ tự động sinh Đường dẫn Dữ liệu dựa vào cấu trúc thiết kế.

## Cơ chế bóc tách (Parser Algorithm)
Hệ thống sẽ tra cứu mảng dữ liệu gốc `items` trong eBMR Designer hoặc JSON gốc.
Với mỗi biến số nằm trong một khối `blockId`:
1. **Phân đoạn (Step/Section):** Xác định `section_id` của khối đó. Tìm kiếm tên Phân đoạn (Ví dụ: `3.3 COMPRESSION`).
2. **Tiêu đề Khối (Block Label):** Lấy giá trị `label` của block đó. (Ví dụ: `In-process control (IPC 2)`).
3. **Cột (Column):** Nếu biến nằm trong Bảng (Table), xác định vị trí cột và lấy tên cột từ `columns[colIndex]`. (Ví dụ: `Khối lượng viên`).
4. **Dòng (Row):** Xác định chỉ số dòng trong bảng (`rowIndex`).

**Semantic Path thu được:** 
`[Step Name] > [Block Label] > [Column Name] > [Row Index]`
Ví dụ: `COMPRESSION > IPC 2 > Khối lượng viên > Dòng 1`.

## Ứng dụng
- **PVR Builder:** Giao diện cho phép chọn một điểm trên Cây Ngữ Cảnh và map (gắn) với một placeholder trên file Word `.docx` báo cáo.
- **Data Aggregation:** Dùng Path này để truy xuất và gom nhóm dữ liệu (Trung bình, Min, Max) của N lô khác nhau.
