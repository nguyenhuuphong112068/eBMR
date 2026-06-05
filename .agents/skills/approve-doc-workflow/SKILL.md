---
name: approve-doc-workflow
description: Lưu trữ các quy tắc và định nghĩa về 6 trạng thái vòng đời của một hồ sơ/quy trình (BMR, Vệ sinh, Dọn quang) trong eBMR.
---

# Quy tắc vòng đời phê duyệt hồ sơ (Document Workflow)

Trong hệ thống eBMR, một hồ sơ (như biểu mẫu BMR, Quy trình vệ sinh, Quy trình dọn quang) có vòng đời gồm 6 trạng thái cơ bản. Khi thiết kế tính năng liên quan đến tài liệu, các Agent bắt buộc phải tuân theo luồng logic trạng thái này:

## 1. Bản nháp (`draft`)
- **Định nghĩa**: Hồ sơ mới được khởi tạo hoặc đang trong quá trình soạn thảo bởi người dùng.
- **Hành động cho phép**: Chỉnh sửa toàn bộ nội dung, lưu nháp, xóa.
- **Hành động chuyển tiếp**: Gửi "Trình ký", lúc này trạng thái sẽ đổi thành `submitted`.

## 2. Trình ký / Chờ kiểm tra (`submitted`)
- **Định nghĩa**: Hồ sơ đã hoàn thành việc soạn thảo và được gửi đi theo luồng workflow (workflow có các vai trò: reviewer, approver, authorizer).
- **Hành động cho phép**: Người có thẩm quyền (reviewer, approver) thực hiện thao tác kiểm tra, phê duyệt.
- **Hành động chuyển tiếp**:
  - Nếu bị từ chối (reject): Trả về `draft`.
  - Nếu tất cả reviewer/approver/authorizer trong luồng đều đồng ý: Chuyển sang `approved`.

## 3. Đã phê duyệt (`approved`)
- **Định nghĩa**: Hồ sơ đã hoàn thành quy trình phê duyệt điện tử nhưng **chưa** được xác định ngày hiệu lực (hoặc chờ thao tác phân bổ).
- **Hành động cho phép**: Lên lịch "Ngày hiệu lực" (Effective Date).
- **Hành động chuyển tiếp**: 
  - Nếu xác định ngày hiệu lực ở tương lai: Chuyển sang `issued`.
  - Nếu xác định ngày hiệu lực là hôm nay (hoặc quá khứ): Chuyển thẳng sang `active`.

## 4. Ban hành - Chờ hiệu lực (`issued`)
- **Định nghĩa**: Hồ sơ đã được ban hành và gán một "Ngày hiệu lực" nằm ở **tương lai**. Nó đang ở chế độ chờ (pending active) và chưa được áp dụng vào sản xuất.
- **Lưu ý**: Các phiên bản `active` cũ vẫn tiếp tục được sử dụng bình thường trong khi phiên bản mới này đang `issued`.
- **Hành động chuyển tiếp**: 
  - Khi thời gian hệ thống (`now()`) lớn hơn hoặc bằng ngày hiệu lực (`effective_date`), hệ thống tự động gọi `DocumentActivationService` để chuyển trạng thái sang `active`.

## 5. Hiện hành (`active`)
- **Định nghĩa**: Hồ sơ đang có hiệu lực chính thức và được sử dụng cho các quy trình sản xuất thực tế.
- **Hành động chuyển tiếp**:
  - Khi có một phiên bản mới của cùng một loại hồ sơ (ví dụ: phiên bản 2.0) chuyển thành `active`, hệ thống sẽ tự động chuyển phiên bản cũ (ví dụ: phiên bản 1.0) sang trạng thái `expired`.

## 6. Hết hạn (`expired`)
- **Định nghĩa**: Hồ sơ đã bị thay thế bởi một phiên bản mới hoặc bị thu hồi, không còn giá trị áp dụng trong sản xuất.
- **Hành động cho phép**: Chỉ có thể xem lại (view/readonly) để tham khảo lịch sử.

---
**Lưu ý lập trình**:
Hệ thống sử dụng chung `App\Services\DocumentActivationService::activateAllIssuedDocuments()` để quét và chuyển đổi tự động các hồ sơ từ `issued` sang `active` mỗi khi người dùng truy cập trang danh sách hoặc chạy qua Cron Job (`php artisan document:activate-issued`).
