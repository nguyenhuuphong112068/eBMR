---
name: project-standards
description: Các tiêu chuẩn chung bắt buộc của dự án eBMR (Font chữ, màu sắc, code convention, v.v.)
---

# Tiêu Chuẩn Dự Án (Project Standards)

Tài liệu này lưu trữ các yêu cầu và quy định thiết kế bắt buộc mang tính toàn hệ thống của dự án eBMR. Khi thực hiện các thay đổi, tạo mới giao diện hoặc lập trình tính năng mới, Agent **BẮT BUỘC** phải tuân thủ các quy tắc dưới đây.

## 1. Font Chữ (Typography)
- **Font Mặc Định Toàn Hệ Thống**: `Arimo`
- **Quy tắc áp dụng**: 
  - KHÔNG sử dụng `Inter`, `Times New Roman` hay các font khác làm mặc định.
  - Khi thiết kế giao diện UI/UX chung (css, blade template), luôn sử dụng `font-family: 'Arimo', sans-serif;`.
  - Trong các template, các block được tạo sinh tự động (như TÍNH TOÁN CÔNG THỨC) cũng phải dùng font `Arimo` hoặc kế thừa (`inherit`) từ body.
  - Font chữ phải đảm bảo đồng nhất tuyệt đối với tài liệu gốc (như văn bản Word).

## 2. Không Sử Dụng Thư Viện Online (No Online CDNs)
- **Quy tắc bắt buộc**: 
  - Toàn bộ các thư viện CSS, JS, Fonts, Images... phải được tải về máy cục bộ và lưu trữ trong thư mục `public` (ví dụ: `public/fonts`, `public/css`, `public/js`).
  - KHÔNG sử dụng các liên kết CDN trực tuyến như `fonts.googleapis.com`, `cdnjs.cloudflare.com`, `cdn.jsdelivr.net`...
  - Lý do: Máy chủ chạy môi trường Production thực tế của dự án eBMR được cách ly hoàn toàn với internet để đảm bảo an toàn bảo mật.

## 3. Các Tiêu Chuẩn Khác
*(Sẽ được bổ sung trong quá trình làm việc cùng user)*
