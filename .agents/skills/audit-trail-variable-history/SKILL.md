---
name: "Audit Trail & Lịch sử thay đổi Biến số (eBMR)"
description: "Lưu trữ quy tắc thiết kế, cơ chế hoạt động của Audit Trail (Lịch sử thay đổi giá trị) đối với tất cả các biến số trong eBMR, bao gồm cả Text, Checkbox, Select, Signature... theo tiêu chuẩn GMP."
---

# Lịch sử thay đổi Biến số (Audit Trail)

Module này đảm bảo tính tuân thủ Audit Trail của GMP (Sản xuất tốt) trong việc lưu trữ hồ sơ lô điện tử (eBMR), áp dụng cho toàn bộ GF, MF, BMR, BPR khi có sử dụng tính năng **Biến Số**.

## 1. Cơ chế hoạt động

Bất kỳ khi nào một giá trị của biến số đã được nhập và lưu trữ vào CSDL (bảng `ebmr_run_data`), nếu người dùng tiếp theo (hoặc chính người đó) muốn sửa lại giá trị:
- Hệ thống **bắt buộc** người dùng phải cung cấp **Lý do thay đổi**.
- Hệ thống sẽ ghi lại Log thay đổi vào bảng `ebmr_run_data_history`.
- Trên giao diện Frontend, nếu biến số đó đã có lịch sử thay đổi, hệ thống sẽ tự động vẽ thêm 1 **Badge cảnh báo (Màu vàng)** báo hiệu số lần đã bị thay đổi bên cạnh tên người nhập, góc trên bên phải biến số đó.

## 2. Bảng cơ sở dữ liệu (`ebmr_run_data_history`)

- `ebmr_run_data_id`: ID tham chiếu đến bản ghi hiện tại trong `ebmr_run_data`.
- `record_id`: ID của hồ sơ thực thi.
- `block_uuid`: ID của khối chứa biến.
- `cell_id`: Vị trí cell (`default` cho biến đơn, `<row>_<col>` cho biến trong bảng).
- `old_raw_value`: Giá trị cũ (được mã hoá AES-256).
- `new_raw_value`: Giá trị mới (được mã hoá AES-256).
- `reason`: Lý do thay đổi (bắt buộc nhập).
- `changed_by`: Tên đầy đủ (FullName) của người đã thực hiện hành động đổi.
- `changed_at`: Timestamp (ngày giờ thực hiện thay đổi).

## 3. Quy trình tương tác UI (Frontend)

- **Các ô nhập liệu Text/Ký tên (`type=signature|text`)**: Khi người dùng bấm vào một biến đã có dữ liệu, Modal `executionInputModal` sẽ xuất hiện với khung nhập **"Lý do thay đổi"** màu đỏ yêu cầu người dùng phải nhập thì mới bấm lưu được.
- **Biến số chọn nhanh (`type=checkbox|select`)**: Do các biến này được thay đổi trực tiếp ngay trên giao diện văn bản (không qua Modal), khi phát hiện có thay đổi so với giá trị cũ, một `SweetAlert` (popup) sẽ xuất hiện chặn thao tác và yêu cầu người dùng gõ lý do thay đổi. Nếu Hủy, trạng thái hiển thị của checkbox/select sẽ được hoàn nguyên (revert) về ban đầu.

## 4. API & Controller tham chiếu

- `EbmrExecutionController@updateRecordData`: Xử lý lưu và cập nhật dữ liệu. Khi nhận `data` và `reasons` từ payload, sẽ so sánh với `raw_value` đã có (sau khi decrypt). Nếu có khác biệt và có `reason`, Insert vào `ebmr_run_data_history`.
- `EbmrExecutionController@getRunDataHistory`: API trả về lịch sử thay đổi để hiển thị trên Modal.

## 5. Lưu ý bảo trì
- **Mã hoá dữ liệu**: Dữ liệu lưu trong cột `old_raw_value` và `new_raw_value` **BẮT BUỘC** phải được mã hoá qua hàm `RunDataEncryptionService::encrypt()`. Quá trình giải mã (decrypt) được thực hiện trong API khi trả JSON cho Frontend.
- Không được phép sửa/xóa trực tiếp bảng `ebmr_run_data_history` dưới mọi hình thức để đảm bảo tính toàn vẹn của Audit Trail.
