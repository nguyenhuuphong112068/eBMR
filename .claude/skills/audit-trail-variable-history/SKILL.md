---
name: audit-trail-variable-history
description: >
  Quy tắc thiết kế và cơ chế hoạt động của Audit Trail (Lịch sử thay đổi giá
  trị) đối với tất cả biến số eBMR (text, number, date, signature, checkbox,
  select, formula) theo tiêu chuẩn GMP — bảng ebmr_run_data_history, bắt buộc
  lý do thay đổi, badge cảnh báo số lần sửa.
---

# Lịch sử thay đổi Biến số (Audit Trail)

Module này đảm bảo tính tuân thủ Audit Trail của GMP trong lưu trữ hồ sơ lô điện tử (eBMR), áp dụng cho toàn bộ GF, MF, BMR, BPR khi có sử dụng tính năng **Biến Số**.

## 1. Cơ chế hoạt động

Bất kỳ khi nào một giá trị biến số đã được lưu vào DB (bảng `ebmr_run_data`), nếu người dùng (bất kỳ ai) muốn sửa lại giá trị:
- Hệ thống **bắt buộc** phải cung cấp **Lý do thay đổi**.
- Ghi log vào bảng `ebmr_run_data_history`.
- Frontend: biến đã có lịch sử thay đổi tự vẽ thêm 1 **Badge cảnh báo (màu vàng)** hiển thị số lần đã sửa, cạnh tên người nhập ở góc trên phải biến số.

## 2. Bảng `ebmr_run_data_history`

- `ebmr_run_data_id`: ID tham chiếu bản ghi hiện tại trong `ebmr_run_data`.
- `record_id`: ID hồ sơ thực thi.
- `block_uuid`: ID khối chứa biến (`__na__` cho lịch sử gạch/hủy gạch chéo N/A).
- `cell_id`: vị trí cell (`default` cho biến đơn, `<row>_<col>` cho biến trong bảng).
- `old_raw_value` / `new_raw_value`: giá trị cũ/mới — **mã hoá AES-256** qua `RunDataEncryptionService::encrypt()` (xem skill `ebmr-data-encryption`).
- `reason`: lý do thay đổi (bắt buộc).
- `changed_by`: FullName người thực hiện.
- `changed_at`: timestamp.

## 3. Quy trình tương tác UI (Frontend — designer-v2)

- **Biến `text`/`number`/`date`/`signature`**: click vào biến đã có dữ liệu → modal `executionInputModal` hiện khung **"Lý do thay đổi"** màu đỏ, phải nhập mới lưu được.
- **Biến `checkbox`/`select`**: đổi trực tiếp trên văn bản (không qua modal) → khi phát hiện khác giá trị cũ, một SweetAlert chặn thao tác yêu cầu gõ lý do. Nếu Hủy → revert hiển thị về trạng thái cũ.
- Xem lịch sử: modal render bởi `renderFieldHistoryModal` ([main.js](resources/js/designer-v2/main.js)).
- Lưu ý SweetAlert v9 trong layout: dùng `result.value`, KHÔNG dùng `result.isConfirmed`.

## 4. API & Controller

Controller: [EbmrExecutionController.php](app/Http/Controllers/Pages/Ebmr/Records/EbmrExecutionController.php)
- `updateRecordData`: nhận `data` + `reasons` từ payload, so sánh với `raw_value` đã có (sau decrypt). Nếu khác và có `reason` → insert `ebmr_run_data_history`.
- `getRunDataHistory` (GET `/ebmr/run-data-history/{record_id}/{block_uuid}/{cell_id}`): trả lịch sử (decrypt trong API trước khi trả JSON) để hiển thị Modal.

## 5. Lưu ý bảo trì

- `old_raw_value`/`new_raw_value` **BẮT BUỘC** mã hoá khi ghi; decrypt khi trả JSON cho Frontend.
- **Không được phép** sửa/xóa trực tiếp bảng `ebmr_run_data_history` dưới mọi hình thức — toàn vẹn Audit Trail.
