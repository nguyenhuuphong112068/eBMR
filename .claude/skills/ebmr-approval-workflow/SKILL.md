---
name: ebmr-approval-workflow
description: >
  Vòng đời trạng thái hồ sơ eBMR (draft→submitted→issued→active→expired) và quy
  trình KIỂM TRA / PHÊ DUYỆT / BAN HÀNH: luồng trình ký reviewer→approver→
  authorizer, từ chối, đổi người trình ký, ủy quyền chỉnh sửa, đổi Dược sĩ phụ
  trách, ngày hiệu lực + DocumentActivationService. Áp dụng cả cho quy trình
  Vệ sinh và Dọn quang (dùng chung trang Phê Duyệt).
---

# Quy trình Kiểm tra & Phê duyệt hồ sơ eBMR

Controller trung tâm: [EbmrApprovalController.php](app/Http/Controllers/Pages/Ebmr/Approvals/EbmrApprovalController.php). Trang: `/ebmr/approvals` (xử lý chung 4 loại workflow: `ebmr`, `cleaning`, `clearance_room`, `clearance_equip`).

## Vòng đời trạng thái `ebmr_templates.status` (5 trạng thái)

> LƯU Ý: KHÔNG còn trạng thái `approved` riêng cho eBMR — duyệt xong chuyển thẳng `issued`. (Quy trình Vệ sinh/Dọn quang thì vẫn dùng `approved` trước khi ban hành.)

| Trạng thái | Nghĩa | Chuyển tiếp |
|---|---|---|
| `draft` (Nháp) | Đang soạn thảo. Chỉ owner/người được ủy quyền/Admin sửa được thiết kế; chỉ draft mới sửa được lý do ấn bản (`saveChangeReason`) | Gửi duyệt (lưu luồng trình ký) → `submitted` |
| `submitted` (Trình ký) | Đã khóa thiết kế, đang chờ duyệt | Đủ chữ ký → `issued`; bị từ chối → về `draft` |
| `issued` (Ban hành - chờ hiệu lực) | Duyệt xong (`issued_date` = lúc authorizer ký). Owner/Admin đặt `effective_date` | effective_date tương lai → giữ `issued`; hôm nay/quá khứ → `active` ngay |
| `active` (Hiện hành) | Đang áp dụng sản xuất — chỉ bản active mới ban hành lô được | Có ấn bản mới cùng category+type active → bản cũ `expired` |
| `expired` (Hết hạn) | Bị thay thế, chỉ xem lại | — |

Tự động kích hoạt: `DocumentActivationService::activateAllIssuedDocuments()` quét `issued` có `effective_date <= hôm nay` → `active` + `expirePreviousEbmrVersions()` (expire bản `version` nhỏ hơn, cùng `caterogy_id` + `type`). Chạy khi mở trang danh sách hoặc cron `php artisan document:activate-issued`.

## Luồng trình ký (`ebmr_template_workflows`)

Mỗi vòng trình ký là 1 batch dòng cùng `created_at`; **vòng mới nhất** = các dòng cùng `created_at` với dòng id lớn nhất (cách nhóm ở `getTemplateWorkflow` và `execute()`).

| Cột | Ghi chú |
|---|---|
| `role` | `reviewer` (step_order=1, nhiều người) / `approver` (2, một người) / `authorizer` (3, một người) |
| `status` | `pending` / `approved` / `rejected` / `cancelled` |
| `due_date`, `reason`, `comment` | hạn xử lý, lý do chọn người, ý kiến khi duyệt/từ chối |

- **Gửi duyệt** (`storeTemplateWorkflow`): hủy (`cancelled`) mọi dòng pending cũ → insert batch mới → template `draft`→`submitted` → `ApprovalWorkflowNotifier::notifyActionableStep`.
- **Xử lý** (`process`, action approve/reject):
  - `reject`: dòng đó `rejected`, mọi pending còn lại `cancelled`, template về `draft`, thông báo owner (`notifyOwnerRejected`).
  - `approve`: dòng đó `approved`; nếu là `authorizer` → set `issued_date`; khi **không còn dòng pending** → template `issued`.
  - Không có ràng buộc cứng theo thứ tự step trong code — UI trình tự, backend chỉ đếm pending.
- **Đổi người trình ký** (`reassignWorkflowUser` + `getEligibleUsers`): chỉ **chủ sở hữu hồ sơ** đổi được người trên dòng pending (frontend biết nhờ `owner_id` trả kèm trong `getWorkflowHistory`).
- Lịch sử ký hiển thị timeline qua `getWorkflowHistory/{type}/{id}`; chữ ký ảnh `signature_image` in vào bảng chữ ký (khối ảo `generateSignatureTable`).

## Ủy quyền & Đổi Dược sĩ phụ trách (tính năng 07/2026)

Migration: [2026_07_18_000000_add_editor_delegation_to_ebmr.php](database/migrations/2026_07_18_000000_add_editor_delegation_to_ebmr.php); helpers: [PermissionHelper.php](app/Authorization/PermissionHelper.php).

- Bảng `ebmr_template_editors` (`template_id`, `user_id`, `granted_by`, unique cặp).
- `ebmr_user_can_edit_template($template, $userId)`: owner **hoặc** Admin **hoặc** có dòng editor → được sửa thiết kế (vẫn phải `status='draft'`).
- `ebmr_user_can_delegate()`: owner hoặc có quyền linh động `ebmr_delegate_edit` → được thêm/bỏ ủy quyền (nút "Ủy quyền" ở list.blade).
- Đổi Dược sĩ phụ trách (`changeOwner`, nút "Đổi DS phụ trách"): cần quyền `ebmr_change_owner`. 2 quyền thuộc `permission_group = 11`, gán sẵn cho role Admin.
- API: `getEditors` / `addEditor` / `removeEditor` / `changeOwner` (EbmrTemplateController).

## Ban hành lô (sau khi active)

Template `active` mới xuất hiện ở Issue Center (`EbmrIssuanceController`): cấp số lô (`checkBatchNumber`, `getSuggestion`), chọn con dấu, `publish` tạo `ebmr_records` → sang skill `ebmr-execution-production`.

## Vệ sinh / Dọn quang (dùng chung trang phê duyệt)

- `cleaning`: bảng `cleaning_process_workflows` (`type`: room/equipment) → list `cleaning_room|equip_processes_list`; duyệt đủ → list `approved` (sau đó ban hành/hiệu lực riêng, xem skill `cleaning-features-notes`).
- `clearance_room`/`clearance_equip`: bảng `clearance_*_process_workflows` → list `clearance_*_processes_list`, cùng logic reject-về-draft / đủ-chữ-ký-thành-approved.
- `DocumentActivationService` cũng activate các process list `issued` này, expire bản `active` cũ **cùng loại** (`cleaning_type`/`clearance_type`) trên cùng phòng/thiết bị.

## Quy tắc khi code tính năng liên quan

1. Đổi trạng thái template phải đi qua đúng chuỗi trên — không nhảy cóc, không tạo trạng thái mới.
2. Sau thao tác duyệt phải gọi `ApprovalWorkflowNotifier` tương ứng (notify người kế tiếp hoặc owner khi bị từ chối).
3. Kiểm tra quyền bằng helpers (`ebmr_user_can_edit_template`, `ebmr_user_can_delegate`, `user_has_permission`) — không hardcode check Admin/owner rải rác.
4. UI danh sách (`resources/views/pages/ebmr/templates/list.blade.php`): nút theo trạng thái — Nháp: Thiết kế/Gửi duyệt; Đã duyệt trở đi: Xem hồ sơ; mọi trạng thái: Lịch sử ấn bản, Cấu hình SX, Ủy quyền, Đổi DS phụ trách.
