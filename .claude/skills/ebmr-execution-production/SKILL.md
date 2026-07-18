---
name: ebmr-execution-production
description: >
  Chế độ THỰC THI lô thật của eBMR: ban hành lô, phân phối công đoạn, bắt đầu
  sản xuất (điều kiện phòng/dọn quang), ghi chép hồ sơ (ebmr_run_data + mã hóa
  + audit trail), người kiểm tra thứ 2, đính kèm, môi trường BMS, kết thúc sản
  xuất. Dùng khi sửa EbmrExecutionController, trang Thực Thi Sản Xuất, hoặc
  luồng lưu dữ liệu ghi chép.
---

# eBMR — Chế độ Thực thi (Ghi chép lô thật)

Trang thực thi (`/ebmr/execute/{recordId}?section=&dist=`) **dùng chung** `designer_v2.blade.php` với Designer — server bật sẵn `isExecutionMode=true` + truyền `recordId`, `main.js` tự chuyển sang luồng lưu hồ sơ lô. Controller chính: [EbmrExecutionController.php](app/Http/Controllers/Pages/Ebmr/Records/EbmrExecutionController.php) (~3.000 dòng).

## Luồng tổng thể

```
Template ACTIVE
  → Ban Hành lô (Issue Center, EbmrIssuanceController::publish)
      • cấp số lô (batch_number, checkBatchNumber/getSuggestion), chọn con dấu (seals)
      • tạo ebmr_records (status ban đầu in_progress)
  → Phân phối công đoạn (distributeSections)
      • ebmr_record_distributions: record_id, section_id, room_id, user_ids (JSON), started_at…
  → Trang "Thực Thi Sản Xuất" (productionIndex) — mỗi người thấy công đoạn được phân
  → startProduction (POST /ebmr/production/start)
      1. Chặn nếu record completed/reviewed; chặn nếu user không nằm trong user_ids
      2. getRoomCleaningReadiness(room_id) — phòng phải đủ điều kiện vệ sinh
      3. Phòng + MỌI thiết bị trong phòng phải có quy trình DỌN QUANG active
         (ClearanceRoomProcessList / ClearanceEquipProcessList) — thiếu là chặn ngay
      4. Tạo clearance campaign → redirect needs_clearance (dọn quang trước), xong mới
         vào trang execute
  → Ghi chép tại /ebmr/execute/{id}?section=...&dist=...
  → endProduction khi xong công đoạn
```

Trạng thái `ebmr_records.status`: `in_progress` → `completed` → `reviewed`.

## BOOT bổ sung ở trang thực thi

`recordId, recordStatus, batchNumber, recordSeals` (con dấu chọn lúc ban hành — đóng góc trên phải mỗi phân đoạn), `envRoomId`/`envDistId` (cho env-monitor), `executionValues` (dữ liệu đã ghi, decrypt sẵn), `recordStructures` (dòng động/lặp), `sectionAttachments`, `activeSectionId` (có thể `"id1,id2"` khi nối trang), `activeSectionNumber` (bù số thứ tự công đoạn khi DOM chỉ còn 1 phân đoạn).

`execute()` còn truyền: vòng trình ký mới nhất (bảng chữ ký khối ảo), `version_history` (khối ảo LỊCH SỬ THAY ĐỔI ẤN BẢN — chỉ hiện ấn bản ≤ ấn bản đang xem), category theo loại BMR/BPR/MF/GF, công thức pha chế (`formula_preparation` + `formula_materials` + `formula_ingredient_amount`), tiêu chuẩn `testing` + `ebmr_properties` (cả template nhúng qua `LinkedGfResolver`).

## Ghi & đọc dữ liệu ghi chép

- **Ghi**: `saveRecordDataV2` (main.js) → POST `/ebmr/update-record-data` → `updateRecordData()`:
  - Mỗi biến 1 dòng `ebmr_run_data` (`record_id`, `block_uuid`, `cell_id`; `default` cho biến đơn, `r_c` cho ô bảng).
  - `value`/`raw_value` **mã hóa AES-256** qua `RunDataEncryptionService` (skill `ebmr-data-encryption`).
  - Sửa giá trị đã có → **bắt buộc `reason`** → insert `ebmr_run_data_history` (skill `audit-trail-variable-history`); gạch/hủy N/A đi cùng đường này với `block_uuid='__na__'`.
- **Đọc**: `execute()` decrypt toàn bộ → `executionValues`. Lịch sử 1 ô: GET `/ebmr/run-data-history/{record}/{blockUuid}/{cellId}` → modal `renderFieldHistoryModal`.
- **Cấu trúc động** (dòng thêm, số lần lặp): POST `/ebmr/record-structure` → bảng cấu trúc record, nạp lại qua `recordStructures`.

## Xác nhận danh tính (GMP)

- `verifyPassword` — người dùng hiện tại nhập lại mật khẩu (ký `signature`): trả `fullName` + `signature_image`.
- `verifyChecker` — **người kiểm tra thứ 2** nhập username+password của chính họ: xác nhận độc lập, trả tên + chữ ký. Cả hai check `Hash::check` với `user_management`.

## Tính năng phụ trợ trang thực thi

- **Đính kèm PDF theo phân đoạn**: [attachments.js](resources/js/designer-v2/attachments.js) + `uploadSectionAttachment`/`deleteSectionAttachment`; form ẩn khi `isReadOnly`.
- **Môi trường BMS**: [env-monitor.js](resources/js/designer-v2/env-monitor.js) poll `productionBmsData` + lịch sử `/ebmr/production/environment-readings/{distribution_id}` (`ProductionEnvironmentController`) — hiện live Nhiệt độ/Độ ẩm/Chênh áp trên toolbar.
- **Nhật ký/nhãn logbook**: `getLogbookLabel`; xem tài liệu liên quan theo mã: `viewDocumentByCode`, `checkDocumentExists`.
- Cân điện tử + barcode MMS hoạt động như Chạy thử (skills `peripheral-balance`, `mms-database-schema`).
- Phân phối: `getRecordDistribution`, `getRecordDistributionHistory`, `getRecordWorkshopUsers`, `getRoomOptions`.

## Ràng buộc & lưu ý

- Người ghi chép KHÔNG dùng bình luận (ẩn khi `isExecutionMode` + không `isIssuanceView`).
- Không được sửa/xóa trực tiếp `ebmr_run_data_history` (toàn vẹn Audit Trail GMP).
- Mọi thay đổi luồng lưu phải giữ nguyên hợp đồng `executionValues`/`recordStructures` giữa controller và main.js — hai đầu decrypt/encrypt phải khớp.
- Routes: [routes/ebmrRoute.php](routes/ebmrRoute.php) dòng 62–87.
