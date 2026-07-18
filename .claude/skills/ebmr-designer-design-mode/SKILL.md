---
name: ebmr-designer-design-mode
description: >
  Kiến trúc + toàn bộ tính năng CHẾ ĐỘ THIẾT KẾ của trình soạn thảo eBMR V2
  (TipTap, block-based, resources/js/designer-v2/). Dùng skill này khi cần:
  thêm/sửa tính năng soạn thảo, toolbar, bảng, biến số, khối ảo, bình luận,
  lưu template, hoặc tra cứu hàm trong main.js. Khi tạo tính năng MỚI cho
  Designer, PHẢI cập nhật lại skill này.
---

# Trình Thiết Kế eBMR V2 — Chế độ Thiết kế

Trình soạn thảo V1 (`designer.blade.php`, `execute.blade.php`, `designer/scripts/*` cũ) **đã bị xóa**. Chỉ còn V2. Cả 3 chế độ (Thiết kế / Chạy thử / Thực thi) dùng chung **một** blade + **một** bundle JS.

## Kiến trúc & File

| File | Vai trò |
|---|---|
| [main.js](resources/js/designer-v2/main.js) | ~9.000 dòng — toàn bộ logic Designer: render, toolbar, bảng, biến số, comment, save… |
| [selection.js](resources/js/designer-v2/selection.js) | Chọn đối tượng kiểu Word (ô/hàng/cột/bảng/biến số) — checklist test ở skill `verify-designer-v2` |
| [ebmr-field.js](resources/js/designer-v2/ebmr-field.js) | TipTap NodeView cho badge biến số (`ebmr-field-badge`) |
| [media-nodes.js](resources/js/designer-v2/media-nodes.js) | Node hình ảnh/media trong editor |
| [na-marks.js](resources/js/designer-v2/na-marks.js) | Gạch chéo N/A (chỉ chạy lúc Chạy thử/Thực thi) |
| [scale-reader.js](resources/js/designer-v2/scale-reader.js) | Cân điện tử RS-232/WebSocket (skill `peripheral-balance`) |
| [mms-barcode.js](resources/js/designer-v2/mms-barcode.js) | Quét barcode nguyên liệu từ DB MMS (skill `mms-database-schema`) |
| [env-monitor.js](resources/js/designer-v2/env-monitor.js) | Nhiệt độ/Độ ẩm/Chênh áp phòng (BMS) trên toolbar lúc thực thi |
| [attachments.js](resources/js/designer-v2/attachments.js) | Đính kèm PDF theo phân đoạn (trang thực thi) |
| [designer_v2.blade.php](resources/views/pages/ebmr/designer_v2.blade.php) | ~4.500 dòng — HTML toolbar, modal, CSS, và object `window.__V2__` (BOOT) |
| [virtual_blocks_v2.blade.php](resources/views/pages/ebmr/designer/scripts/virtual_blocks_v2.blade.php) | Khối ảo: header GF/BMR, bảng chữ ký phê duyệt, CÔNG THỨC PHA CHẾ, MÔ TẢ SẢN PHẨM, LỊCH SỬ THAY ĐỔI ẤN BẢN |
| [EbmrDesignerController.php](app/Http/Controllers/Pages/Ebmr/Designer/EbmrDesignerController.php) | `designer()` (mở trang), `save()`, comment APIs, `getDynamicOptions`, `getEquipmentList` |

- Mọi API JS gắn dưới namespace `window.__V2__` (KHÔNG gắn thẳng vào `window`, trừ các hàm modal cân/onclick inline).
- Build bằng Vite: `npm run build` → `public/build/assets/main-*.js`. Sau khi build phải **Ctrl+F5**.
- Boot object `window.__V2__` khai báo ở cuối `designer_v2.blade.php` (~dòng 4093): `templateId, items, fieldsConfig, isReadOnly, isExecutionMode, recordId, executionValues, recordStructures, docProperties, comments, urls{...}`.

## Quyền chỉnh sửa (quan trọng)

Được sửa thiết kế khi **và chỉ khi** `ebmr_user_can_edit_template()` (Dược sĩ phụ trách `owner_id` / người được ủy quyền trong `ebmr_template_editors` / Admin) **VÀ** `status === 'draft'`. Ngược lại `$isReadOnly = true` ([EbmrDesignerController.php:113-117](app/Http/Controllers/Pages/Ebmr/Designer/EbmrDesignerController.php#L113-L117)). Xem skill `ebmr-approval-workflow` cho vòng đời trạng thái và ủy quyền.

## Cấu trúc tài liệu

- **Block-based**: `items[]` gồm section (phân đoạn), `static-text` (Mô tả), `table`, `linked-template` (BM Chung/GF nhúng), bảng thiết bị, Bảng KT KL TB (weight chart), nhóm khối lặp, bảng chữ viết tắt (lưu riêng cột `abbreviations_List`), khối ảo (`isVirtual`).
- Loại hồ sơ: **BMR** (bán thành phẩm), **BPR** (đóng gói), **MF** (biểu mẫu gốc), **GF** (biểu mẫu dùng chung), **CO** (thành phần) — mỗi loại lấy category khác nhau (`intermediate_category`, `finished_product_category`, `mf_category`, `gf_category`, `co_category`).
- Lưu DB: `ebmr_templates` + `ebmr_template_blocks` (mỗi block 1 dòng, cột `properties` JSON) + `ebmr_content_blocks` (nội dung văn bản, placeholder `[[CONTENT_id]]`).
- **Lưu incremental**: `saveTemplate()` chỉ gửi block dirty (`markDirty`/`markSaved`) → POST `/ebmr/store-template`.

## Tính năng chế độ Thiết kế (tra cứu theo hàm trong main.js)

### Soạn thảo văn bản (TipTap per-block)
- `mountEditor`/`unmountEditor`: editor chỉ mount vào block/ô đang click; `cmd()`/`applyCellCommand()` cho toolbar.
- Định dạng: B/I/U/S, chỉ số trên/dưới, đổi kiểu chữ, màu chữ (`bindColorControlV2`), H1–H4/Paragraph (`setBlockTextTagV2`), cỡ chữ, căn lề, hướng chữ, bullet/số.
- **Format Painter** (`captureFormatPainterV2`, single-click 1 lần / double-click khóa, Esc thoát), Xóa định dạng.
- Undo/Redo cấp tài liệu bằng snapshot: `saveDocState`/`undoDoc`/`redoDoc` + `smartUndo`/`smartRedo` (ưu tiên undo trong editor nếu đang gõ).
- **Đánh số tiêu đề tự động**: `updateHeadingNumbersV2`/`toggleHeadingNumberingV2`.
- Ký hiệu đặc biệt (`renderSymbolGridV2`, quick symbols), chữ viết tắt (`addAbbreviationV2`), phương trình (`openEquationEditorV2`), chèn ảnh (`insertImageV2`).
- **Tìm & Thay thế** kiểu Word: `openFindV2`, `replaceCurrentV2`, `replaceAllV2`.
- **Nhập từ Word/Excel**: `handleEditorPaste` + `cleanWordHtml`, `parseHtmlTableToGrid`, `convertSymbolFontsV2` — dán bảng thành block table.

### Khối & Phân đoạn
- Thêm/xóa/di chuyển block: `addBlock`, `deleteBlock`, `moveBlock`, `insertBlockAndFocusV2`, thanh chèn (`makeInserter`).
- Copy/Cut/Paste **cả khối**: `pickBlockV2` (chọn 1/nhiều block), `copyPickedBlocksV2`, `pasteBlockClipboardV2`.
- Phân đoạn: `renameSectionV2`, `toggleSectionPageBreakV2`, tách hướng phòng `splitSectionIntoRoomTrackV2` (room track — mỗi nhánh phòng 1 bản phân đoạn).
- **Lặp nhóm khối**: `openBlockLoopModalV2`, `renderLoopGroupDesignV2` (thiết kế) / `renderLoopGroupExecutionV2` (tab "Lần i" khi chạy).
- **BM Chung (GF nhúng)**: `loadGfListV2`, `insertLinkedGfV2`, preview `fetchAndRenderGfPreviewV2`; backend resolve qua `App\Services\LinkedGfResolver`.
- Bảng thiết bị: `loadEquipmentList`, `insertEquipmentTableV2` (kéo-thả từ sidebar). Bảng KT KL TB + biểu đồ: `generateWeightChartTableV2`, `renderChartV2`.
- Mục lục: `buildToc`. Split view: `toggleWorkspaceSplitV2`. Thuộc tính tài liệu: `insertDocPropV2` (+ bảng `ebmr_properties`, tiêu chuẩn `testing` chèn span động).

### Bảng (table)
- `renderTable`, resize cột/hàng (`attachTableResizers`, `.v2-col-resizer`/`.v2-row-resizer`), sizer góc (`attachTableSizerV2`).
- Gộp/tách ô (`mergeSelectedCellsV2`/`splitCellV2` — dùng rs/cs/hidden), thêm/xóa hàng cột (`insertTableRowV2`…), chia đều (`distributeRowsV2/ColsV2`), auto-fit, căn dọc (`setCellVAlignV2`), viền (`applyBorderToSelectedCellsV2`).
- Chọn ô/hàng/cột/bảng, kéo chọn khối, Ctrl+C/X/V vùng ô: xem `selection.js` + skill `verify-designer-v2`.

### Biến số (variables)
- Loại: `text`, `number`, `date`, `signature`, `checkbox`, `select` (+ select động qua `getDynamicOptions`), `formula`.
- `insertVariable`, panel thuộc tính `openFieldPanel` (~500 dòng), panel hàng loạt `openBatchFieldPanelV2`/`batchSyncFieldConfigV2`/`batchDeleteFieldsV2`.
- Công thức: editor token (`insertFormulaTokenV2`, `serializeFormulaElementV2`), highlight biến tham chiếu (`highlightFormulaReferencesV2`), chọn biến bằng click (`toggleSelectFormulaVarModeV2`).
- Copy/cut/paste biến: **mỗi lần dán sinh ID MỚI + clone config riêng** (`buildFieldDuplicateMapV2`, `remapFormulaStringV2`, `applyFieldDuplicateMapV2`) — 2 badge không bao giờ trỏ chung 1 biến.
- Đồng bộ config: `syncFieldConfigV2`, kiểm tra nhất quán badge↔config: `verifyFieldBadgesConsistencyV2`, `collectUsedFieldIds`.

### Bình luận (prefix `cmt*`)
- Rail bên phải, anchor theo text offset (`cmtCaptureSelection`, `cmtLocate`, `cmtQueueReanchor` khi nội dung đổi), reply/xóa, ẩn/hiện (`cmtToggle`). API: store/reply/delete/reanchor-comment trong `EbmrDesignerController`.
- Chế độ thực thi lô KHÔNG dùng bình luận (ẩn trong blade).

### Khác
- **So sánh ấn bản**: nút `v2-btn-version-diff` — diff LCS với ấn bản liền kề, API `getVersionDiff` (EbmrTemplateController).
- In hồ sơ, mở hồ sơ, lịch sử thay đổi (`getHistory`).
- Ngôn ngữ: **chỉ Tiếng Việt** (đa ngôn ngữ đã bỏ; `?lang=dual` chỉ để xem read-only). Route `aiTranslate` còn tồn tại nhưng không dùng trên UI.

## Quy ước bắt buộc
- Hàm mới đặt hậu tố `V2`, gắn vào `window.__V2__` nếu cần gọi từ blade.
- Mọi thao tác sửa nội dung phải `markDirty(blockId)` và (nếu cần undo) `saveDocState()` trước khi đổi.
- Guard 3 trạng thái ở đầu mọi handler: `BOOT.isReadOnly`, `BOOT.isExecutionMode`, block `locked/virtual`.
- Sau khi sửa selection/field: chạy checklist skill `verify-designer-v2`.
