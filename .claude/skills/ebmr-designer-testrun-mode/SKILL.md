---
name: ebmr-designer-testrun-mode
description: >
  Chế độ CHẠY THỬ (giả lập ghi chép) của trình soạn thảo eBMR V2: toggle
  Thiết kế↔Chạy thử, sandbox nhập thử cho hồ sơ đã khóa, nhập liệu biến số,
  công thức tự tính, lặp nhóm khối, dòng thêm động, gạch chéo N/A, cân điện
  tử/barcode. Dùng khi sửa hành vi nhập liệu/hiển thị biến số lúc chạy thử
  hoặc debug khác biệt giữa Chạy thử và Thực thi lô thật.
---

# Trình Thiết Kế eBMR V2 — Chế độ Chạy thử

Chạy thử là chế độ **giả lập ghi chép** ngay trong trang thiết kế — cùng bundle với chế độ Thiết kế, bật/tắt bằng nút `#v2-btn-toggle-mode`.

## Cơ chế chuyển chế độ

`toggleExecutionModeV2()` ([main.js:6814](resources/js/designer-v2/main.js#L6814)):
1. Đảo `BOOT.isExecutionMode`; đổi nút (xanh dương "Chạy thử" ↔ xanh lá "Thiết kế").
2. `unmountEditor()` + `selection.clearAll()` + tắt block-pick — mọi thao tác chọn/soạn thảo phải CHẾT khi chạy thử.
3. Dọn field clone của lặp nhóm (`loopClonedFieldIds`), reset tab "Lần i" — mỗi phiên chạy thử bắt đầu lại từ Lần 1.
4. `cleanupDynamicRowsV2()` — dọn DÒNG THÊM tạo lúc chạy thử, không được lọt vào cấu trúc Thiết kế.
5. Thêm/bỏ class `execution-mode-active` trên `.content-wrapper`; `naMarks.refreshButton()`; `renderDocument()` repaint toàn bộ badge.

## Sandbox nhập thử (hồ sơ đã khóa)

`canEnterExecDataV2()` = `BOOT.isExecutionMode && (!BOOT.isReadOnly || !BOOT.recordId)`:
- Hồ sơ **đã trình ký/đã duyệt** (read-only) mở trong trang thiết kế **vẫn nhập thử được** khi bật Chạy thử — an toàn vì trang thiết kế không có `recordId` nên `saveRecordDataV2`/auto-save là no-op; giá trị chỉ nằm trong `BOOT.executionValues` (mất khi F5). Có toast SweetAlert nói rõ "dữ liệu thử chỉ lưu tạm trên trình duyệt".
- Trang thực thi lô thật (có `recordId`) giữ luật cũ: read-only là không nhập được.

## Nhập liệu biến số

- Badge biến số render bởi NodeView trong [ebmr-field.js](resources/js/designer-v2/ebmr-field.js). Click badge lúc chạy thử → modal nhập liệu (`executionInputModal`) thay vì panel thuộc tính.
- Giá trị lưu `BOOT.executionValues[fieldId] = {default|<cellId>: rawValue}`; mặc định lấy qua `getExecDefaultV2`.
- **Field paint registry**: đổi `executionValues` không tự trigger update của TipTap NodeView → mỗi NodeView đăng ký `window.__V2__.registerFieldPaint(fieldId, fn)`; sau mỗi lần nhập gọi `window.__V2__.repaintAllFields()` để công thức phụ thuộc + biểu đồ (`refreshChartsV2`) cập nhật.
- **Công thức**: `calculateFormulaV2` (+ `parseNumberSafeV2`) tính lại từ các biến tham chiếu.
- `checkbox`/`select` đổi trực tiếp trên văn bản; `text`/`number`/`date`/`signature` qua modal. Lịch sử thay đổi hiển thị bằng `renderFieldHistoryModal` (chỉ có dữ liệu thật khi thực thi lô).
- Chữ ký/`signature`: xác nhận lại mật khẩu qua API `verifyPassword`; người kiểm tra thứ 2 qua `verifyChecker` (xem skill `ebmr-execution-production`).

## Lặp nhóm khối & Dòng thêm động

- Lặp nhóm: `renderLoopGroupExecutionV2` render tab "Lần i"; field mỗi lần lặp là **bản clone ID riêng** (`getLoopIterFieldMapV2`, cache `loopIterFieldMapCache`). Block trong nhóm lặp dùng chung `data-id` → gạch N/A áp dụng cho MỌI tab.
- Dòng thêm (Cấp 2) trong bảng lúc chạy: `addRuntimeTableRowV2` / `deleteRuntimeTableRowV2`; cấu trúc lưu qua `syncRecordStructureV2`/`mergeRecordStructuresV2` (chỉ ghi DB khi thực thi thật — bảng `ebmr_record_structures` qua API `saveRecordStructure`).

## Gạch chéo N/A ([na-marks.js](resources/js/designer-v2/na-marks.js))

- Nút `#v2-btn-na-mode` chỉ hiện lúc Chạy thử/Thực thi và hồ sơ chưa khóa. Bật chế độ: chạm chọn ô/khối; thanh `#v2-na-bar` đáy màn hình: [Hàng] [Cột] [Bảng] [Vùng] [Gạch chéo…] [Hủy gạch…] [Xong] — tối ưu máy tính bảng, không cần chuột.
- Gạch = 2 đường chéo đỏ SVG (in được) + chip lý do; nội dung mờ, chặn nhập. Gạch & hủy đều **bắt buộc nhập lý do** (GMP). Ngoài chế độ: chạm vùng gạch xem lý do/người/thời gian.
- Lưu: `BOOT.executionValues.__na__[targetKey] = {reason, by, at}` (hủy = `''`); `targetKey` = `blockId` hoặc `blockId:r_c`. Khi thực thi thật đi chung `updateRecordData` → mỗi key 1 dòng `ebmr_run_data` (`block_uuid='__na__'`, `cell_id=targetKey` ≤100 ký tự), có lịch sử trong `ebmr_run_data_history`.

## Cảnh báo lấy mẫu định kỳ (Bảng KT KL TB)

- `scheduleWeightSampleAlertV2` + countdown (`startSampleCountdownV2`, `renderSampleCountdownV2`) + beep liên tục (`startContinuousBeepV2`, cần `primeSampleAudioV2` từ gesture đầu tiên) nhắc tần suất cân mẫu; tìm bảng tần suất qua `findFreqTableForFieldV2`.

## Thiết bị ngoại vi (khởi tạo cả lúc chạy thử)

- `initScaleReaderV2(BOOT)` — cân điện tử (skill `peripheral-balance`), nút ⚖️ trên biến `number`.
- `initMmsBarcodeV2(BOOT)` — quét barcode nguyên liệu tra DB MMS (skill `mms-database-schema`).
- `createEnvMonitorV2(BOOT).init()` — nhiệt độ/độ ẩm/chênh áp: chỉ có dữ liệu khi có `envRoomId`/`envDistId` (trang thực thi có `?dist=`).

## Checklist nhanh khi sửa chế độ Chạy thử

1. Chuyển qua lại Thiết kế ↔ Chạy thử nhiều lần: không rác field clone, không sót dòng động, selection chết hẳn.
2. Nhập số vào biến nuôi công thức → công thức + biểu đồ cập nhật ngay (repaintAllFields).
3. Hồ sơ đã duyệt (read-only, không recordId): nhập thử được, F5 mất dữ liệu, KHÔNG có request ghi DB.
4. Gạch N/A: bắt buộc lý do; khối trong nhóm lặp gạch 1 tab phải áp dụng mọi tab.
