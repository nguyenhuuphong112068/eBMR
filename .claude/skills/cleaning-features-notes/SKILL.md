---
name: cleaning-features-notes
description: >
  Lưu trữ toàn bộ chức năng, cấu trúc dữ liệu, luồng nghiệp vụ và lưu ý kỹ thuật
  của module Vệ Sinh Phòng & Thiết Bị trong eBMR. Sử dụng skill này khi cần:
  - Thêm/sửa logic vệ sinh phòng hoặc thiết bị
  - Debug luồng campaign (tạo, thực hiện bước, hoàn thành)
  - Mở rộng sang phòng/thiết bị mới
  - Tích hợp quy trình ký duyệt (workflow) vào vệ sinh
  - Thêm/sửa bảng DB liên quan đến vệ sinh
---

# 🧹 Cleaning Module — Vệ Sinh Phòng & Thiết Bị

## 1. Tổng quan kiến trúc

Module Vệ Sinh gồm **2 nhánh song song**:
- **Vệ sinh Phòng** (Room Cleaning) — phòng cố định, do PMS quản lý
- **Vệ sinh Thiết bị** (Equipment Cleaning) — thiết bị cố định hoặc di động

Hai nhánh có thể chạy **song song cùng lúc** (thiết bị cố định VS cùng phòng), hoặc **độc lập** (thiết bị di động mang đến Phòng VS Chung).

---

## 2. Cơ sở dữ liệu (DB Schema)

### 2.1. Phòng sản xuất
- Nguồn gốc: Bảng `room` từ **PMS DB** (connection `pms`), **không chỉnh sửa được**.
- Kết nối bằng: `DB::connection('pms')->table('room')`

### 2.2. Quy trình vệ sinh Phòng

| Bảng | Mô tả |
|------|-------|
| `cleaning_room_process_lists` | Danh sách phiên bản quy trình VS phòng (mỗi phòng có nhiều phiên bản, có `status`: draft/submitted/approved/active) |
| `cleaning_room_processes` | Các bước trong 1 quy trình phòng (`content` HTML, `standard`, `step` số thứ tự) |
| `cleaning_room_campaigns` | Phiên VS phòng thực tế (`room_id`, `process_list_id`, `status`: in_progress/completed) |
| `cleaning_room_campaign_steps` | Bước thực hiện thực tế (`is_done`, `is_passed`, `result_note`, `attached_images`, `done_by`, `done_at`) |

**Model liên kết:**
```
CleaningRoomProcessList → hasMany CleaningRoomProcess (steps)
CleaningRoomCampaign   → hasMany CleaningRoomCampaignStep
                        → hasMany CleaningEquipCampaign (equipCampaigns)
                        → belongsTo CleaningRoomProcessList
CleaningRoomCampaignStep → belongsTo User (doneByUser)
```

### 2.3. Quy trình vệ sinh Thiết bị

| Bảng | Mô tả |
|------|-------|
| `cleaning_equip_process_lists` | Danh sách phiên bản quy trình VS thiết bị (theo `equipment_id` từ bảng `instrument`) |
| `cleaning_equip_processes` | Các bước trong 1 quy trình thiết bị |
| `cleaning_equip_campaigns` | Phiên VS thiết bị thực tế |
| `cleaning_equip_campaign_steps` | Bước thực hiện VS thiết bị thực tế |

#### Bảng `cleaning_equip_campaigns` — các trường quan trọng:
```
equipment_id     → ID thiết bị (bảng instrument trong eBMR)
process_list_id  → FK → cleaning_equip_process_lists
room_campaign_id → FK → cleaning_room_campaigns (nếu VS cùng phòng)
clean_location   → 'in_room' | 'clearing_room'
source_room_id   → Phòng xuất phát (PMS room.id)
clearing_room_id → FK → room_clearings (nếu di động)
status           → 'in_progress' | 'completed'
cleaning_type    → 1: Cấp 1 (3 ngày), 2: Cấp 2 (7 ngày), 3: VS lại (24h)
employee_ids     → JSON array user IDs
started_by / completed_by / started_at / completed_at
```

**Model liên kết:**
```
CleaningEquipCampaign → hasMany CleaningEquipCampaignStep (steps)
                       → belongsTo CleaningRoomCampaign (roomCampaign)
                       → belongsTo CleaningEquipProcessList (processList)
                       → belongsTo RoomClearing (clearingRoom)
CleaningEquipCampaignStep → belongsTo User (doneByUser)
```

### 2.4. Phòng VS Chung

| Bảng | Mô tả |
|------|-------|
| `room_clearings` | Phòng vệ sinh chung cho thiết bị di động (code, name, area, status, created_by) |

> **Lý do tạo riêng**: Phòng VS chung không có trong PMS, phải quản lý trong eBMR DB.

### 2.5. Nhật ký phòng

Bảng `room_logbooks` là **trung tâm lưu trạng thái** phòng và thiết bị.

| Cột quan trọng | Mô tả |
|----------------|-------|
| `room_id` | PMS room.id |
| `campaign_id` | FK → cleaning_room_campaigns |
| `campaign_equip_id` | FK → cleaning_equip_campaigns (thêm mới) |
| `equipment_id` | ID thiết bị (instrument) nếu là nhật ký thiết bị |
| `action_type` | 'cleaning' / 'production' / v.v. |
| `current_status` | 'dirty' / 'cleaning' / 'cleaned' |
| `clean_level` | 'Cấp 1' / 'Cấp 2' / 'Vệ sinh lại' |
| `clean_expiry_date` | Hạn sử dụng sau VS |

### 2.6. Workflow ký duyệt

Bảng `cleaning_process_workflows` dùng chung cho cả phòng và thiết bị:
```
process_list_id → ID của process_list
type            → 'room' | 'equipment'
user_id         → người ký duyệt
step_order      → thứ tự bước duyệt
status          → 'pending' | 'approved' | 'rejected'
```

### 2.7. Phân biệt thiết bị cố định / di động
```php
// Cột trong bảng 'instrument' (eBMR DB, không phải PMS)
$table->boolean('is_Portable_equipment')->default(0);
// 0 = cố định, 1 = di động (portable)
```

---

## 3. Luồng Nghiệp Vụ

### 3.1. Vệ sinh Phòng

```
Nhấn [Mở Vệ Sinh] (trang Sản Xuất / Nhật Ký Phòng)
  └─► CleaningProcessController::openCampaignPage($room_id)
        ├── Tìm campaign 'in_progress' → nếu có thì load lại
        └── Nếu chưa có → tạo mới (DB transaction):
              ├── Tạo CleaningRoomCampaign
              ├── Ghi room_logbooks (status: cleaning)
              ├── Tạo CleaningRoomCampaignStep (1 bước/process step)
              └── [BẮT BUỘC] Tạo CleaningEquipCampaign cho thiết bị CỐ ĐỊNH trong phòng
                    ├── Query: equipment_in_room JOIN instrument WHERE is_Portable_equipment=0
                    └── Mỗi thiết bị có quy trình active → tạo campaign + steps + logbook entry
        └── Trả về view campaign_execute với $equipCampaigns
```

### 3.2. Thực hiện bước VS phòng

```
Bước chưa làm: Toggle switch "Đạt/Không Đạt" (default: KHÔNG ĐẠT)
  └─► POST /campaign/{id}/step/{step_id}/complete
        └── CleaningProcessController::completeStep()
              └── Lưu is_done=true, is_passed, result_note, attached_images (max 5), done_by, done_at
  └─► JS cập nhật sidebar badge + progress bar
  └─► Chuyển sang bước tiếp theo tự động
```

### 3.3. Hoàn thành VS Phòng

```
Khi tất cả bước phòng done:
  └─► JS checkFinishButton():
        ├── Kiểm tra stepsData.every(is_done) → true
        └── Kiểm tra equipCampaignsData.every(status==='completed') → true
              ├── Nếu đủ → hiện nút [Hoàn thành Vệ Sinh]
              └── Nếu thiếu → SweetAlert cảnh báo tên thiết bị chưa xong

Nhấn [Hoàn thành Vệ Sinh]:
  └─► POST /campaign/{id}/complete
        └── CleaningProcessController::completeCampaign()
              ├── Cập nhật campaign.status = 'completed'
              ├── Ghi room_logbooks (status: cleaned, clean_level, clean_expiry_date)
              └── Tính hạn VS: Cấp 1=3 ngày, Cấp 2=7 ngày, VS lại=24h
```

### 3.4. Vệ sinh Thiết bị (từ sidebar phòng)

```
Sidebar phòng hiện panel "THIẾT BỊ (Bắt buộc)":
  └─► Mỗi thiết bị có nút [VS] → mở tab mới (target="_blank")
        └─► GET /equip/{equip_id}/campaign/open?room_campaign_id={id}
              └── CleaningEquipCampaignController::openCampaignPage()
                    └── Load campaign in_progress hoặc tạo mới

Thực hiện bước:
  └─► POST /equip-campaign/{id}/step/{step_id}/complete
        └── CleaningEquipCampaignController::completeStep()

Hoàn thành:
  └─► POST /equip-campaign/{id}/complete
        └── CleaningEquipCampaignController::completeCampaign()
              └── Ghi room_logbooks với campaign_equip_id
              └── Sau khi xong → window.close() (tab phụ tự đóng)
```

### 3.5. Vệ sinh Thiết bị Di động (Phòng VS Chung)

```
Môi Trường → Phòng VS Chung → Dashboard phòng
  └─► Nhấn [Tiếp nhận Thiết Bị]
        └─► POST /room-clearing/{id}/receive-equip
              └── RoomClearingController::receiveEquip()
                    ├── Kiểm tra thiết bị không có campaign đang chạy
                    └── Tạo CleaningEquipCampaign với:
                          clean_location = 'clearing_room'
                          clearing_room_id = phòng VS chung
                          room_campaign_id = null (không liên kết phòng)

Thực hiện VS: Giống luồng 3.4
```

---

## 4. Controllers & Routes

### Controllers

| Controller | Namespace | Chức năng chính |
|-----------|-----------|-----------------|
| `CleaningProcessController` | `Pages\ManuEnv` | Quản lý quy trình + campaign PHÒNG |
| `CleaningEquipCampaignController` | `Pages\ManuEnv` | Campaign THIẾT BỊ (open, completeStep, completeCampaign) |
| `RoomClearingController` | `Pages\ManuEnv` | CRUD phòng VS chung + tiếp nhận thiết bị |

### Routes (prefix: `manu_env/cleaning-process`, name: `pages.manu_env.cleaning_process.`)

```php
// VS Phòng
GET  /room/{room_id}/campaign/open       → campaign.open
POST /room/{room_id}/campaign/start      → campaign.start
POST /campaign/{id}/step/{step_id}/complete → campaign.completeStep
POST /campaign/{id}/complete             → campaign.complete
GET  /campaign/{id}                      → campaign.get

// VS Thiết bị
GET  /equip/{equip_id}/campaign/open     → equip.campaign.open
POST /equip-campaign/{id}/step/{step_id}/complete → equip.campaign.completeStep
POST /equip-campaign/{id}/complete        → equip.campaign.complete
GET  /equip-campaign/{id}                → equip.campaign.get
```

```php
// Phòng VS Chung (prefix: manu_env/room-clearing, name: pages.manu_env.room_clearing.)
GET  /                    → index
GET  /{id}/dashboard      → dashboard
POST /                    → store
PUT  /{id}                → update
POST /{id}/receive-equip  → receiveEquip

// API
GET  /ebmr/api/equip-processes?equip_id={id}  → api.equip_processes
```

---

## 5. Views

| View | Mô tả |
|------|-------|
| `pages/manu_env/cleaning_process/campaign_execute.blade.php` | Trang thực hiện VS Phòng (sidebar vàng) |
| `pages/manu_env/cleaning_process/equip_campaign_execute.blade.php` | Trang thực hiện VS Thiết Bị (sidebar xanh lam) |
| `pages/manu_env/room_clearing/index.blade.php` | Danh sách phòng VS chung (card grid) |
| `pages/manu_env/room_clearing/dashboard.blade.php` | Dashboard phòng VS chung |
| `pages/manu_env/cleaning_process/index.blade.php` | Quản lý quy trình (thiết kế bước) |

### Màu sắc phân biệt
- **Vệ sinh Phòng**: Màu **vàng** (`#f59e0b`) — gradient amber
- **Vệ sinh Thiết bị**: Màu **xanh lam** (`#0ea5e9`) — gradient sky blue

---

## 6. Các JS Data Variables quan trọng

Trong `campaign_execute.blade.php`:
```javascript
const CAMPAIGN_ID = {{ $campaign->id }};
const CAMPAIGN_STATUS = '{{ $campaign->status }}';
const TOTAL_STEPS = {{ $campaignSteps->count() }};
const stepsData = @json($stepsDataArray);        // [{id, step, is_done}]
const equipCampaignsData = @json($equipCampaignsDataArray); // [{id, equipment_code, status}]
```

### Logic checkFinishButton() — QUAN TRỌNG
```javascript
function checkFinishButton() {
    const allStepsDone = stepsData.every(s => s.is_done);
    const allEquipDone = equipCampaignsData.length === 0
                      || equipCampaignsData.every(e => e.status === 'completed');

    if (allStepsDone && allEquipDone) {
        // → Hiện nút "Hoàn thành Vệ Sinh"
    } else if (allStepsDone && !allEquipDone) {
        // → Cảnh báo SweetAlert tên thiết bị chưa xong
    }
}
```

---

## 7. Ghi nhớ Nghiệp vụ Quan trọng

### 7.1. Thiết bị cố định VS cùng phòng (BẮT BUỘC)
- Khi mở campaign VS phòng → **tự động** tạo `CleaningEquipCampaign` cho tất cả thiết bị có `is_Portable_equipment=0` trong phòng đó.
- Nguồn thiết bị: `equipment_in_room JOIN instrument WHERE is_Portable_equipment=0`
- Bỏ qua nếu: thiết bị chưa có quy trình active, hoặc đã có campaign đang chạy.
- **Không thể hoàn thành VS phòng** nếu còn thiết bị cố định chưa VS xong.

### 7.2. Thiết bị di động
- Có thể VS tại **phòng VS chung** (`clean_location='clearing_room'`, `clearing_room_id` được set)
- Hoặc VS tại phòng bất kỳ riêng lẻ (không liên kết room_campaign)
- Kiểm tra: `instrument.is_Portable_equipment = 1`

### 7.3. Hạn vệ sinh sau khi hoàn thành
```php
// Trong completeCampaign() (cả phòng và thiết bị)
match($cleaningType) {
    1 => now()->addDays(3),   // Cấp 1
    2 => now()->addDays(7),   // Cấp 2
    3 => now()->addHours(24), // VS lại
}
```

### 7.4. Kết quả bước mặc định là KHÔNG ĐẠT
- Switch toggle mặc định ở trạng thái **tắt** (KHÔNG ĐẠT / đỏ).
- Người dùng phải **chủ động bật** sang ĐẠT (xanh lá).
- Lý do: đảm bảo người thực hiện phải kiểm tra có chủ ý.

### 7.5. Chữ ký số trong bước đã xác nhận
```php
// Hiển thị chữ ký nếu user có signature_image, không thì dùng cursive font
@if (!empty($step->doneByUser->signature_image))
    <img src="{{ $step->doneByUser->signature_image }}" ...>
@else
    <div style="font-family: 'Brush Script MT', cursive; font-size: 1.5rem;">
        {{ $step->doneByUser->fullName }}
    </div>
@endif
```

### 7.6. Xem lại hồ sơ từ Nhật ký Phòng
- Nhật ký phòng có nút **[Xem Hồ Sơ VS]** → mở `campaign_execute.blade.php` (chế độ readonly khi campaign completed).
- Khi xem từ logbook → **ẩn** nút [Hoàn thành Vệ Sinh].
- Truyền `$fromLogbook = true` từ controller để view biết chế độ.

### 7.7. Workflow ký duyệt quy trình
- Quy trình phòng và thiết bị đều dùng bảng `cleaning_process_workflows`.
- `type = 'room'` hoặc `type = 'equipment'`.
- Quy trình phải được `status='approved'` hoặc `status='active'` mới cho phép tạo campaign.
- Chữ ký số của người duyệt hiển thị trong modal "Qui Trình Ký Duyệt Hồ Sơ" (timeline).

### 7.8. Upload hình ảnh kết quả bước
- Max **5 hình** mỗi bước.
- Lưu tại: `public/upLoadData/img/cleaning_result/`
- Tên file: `cleaning_room_campaign_steps_{step_id}_{time}_{uniqid}.{ext}`
- Lưu trong cột `attached_images` dạng JSON array.

### 7.9. Quy tắc thay thế phiên bản quy trình Hiện hành (Active)
- Khi một quy trình (Phòng hoặc Thiết bị) được phê duyệt và được chuyển sang trạng thái `active` (Hiện hành), hệ thống sẽ **chỉ thay thế (chuyển sang `expired`) các phiên bản cũ có CÙNG LOẠI** (`cleaning_type` đối với vệ sinh hoặc `clearance_type` đối với dọn quang).
- Các quy trình khác loại (ví dụ Cấp 1, Cấp 2, Vệ sinh lại) hoạt động hoàn toàn độc lập và có thể cùng ở trạng thái `active` đồng thời cho cùng một phòng/thiết bị.

---

## 8. Lưu ý Kỹ thuật

### 8.1. Kết nối DB dual
```php
// Phòng sản xuất: PMS DB
DB::connection('pms')->table('room')->where('id', $room_id)->first()

// Thiết bị: eBMR DB (local)
DB::table('instrument')->where('id', $equipment_id)->first()
```

### 8.2. Xử lý URL ảnh cứng trong nội dung HTML
Khi load nội dung bước từ DB, phải xóa domain cứng:
```php
$content = preg_replace('/(src|href)=["\']?(https?:\/\/[^\/]+)(\/cleaning_images\/)/i', '$1="$3', $content);
$content = str_replace('http://127.0.0.1:8001', '', $content);
$content = str_replace('http://localhost:8001', '', $content);
```

### 8.3. Kiểm tra campaign tồn tại trước khi tạo mới
```php
// Trong openCampaignPage — tránh tạo duplicate
$campaign = CleaningRoomCampaign::where('room_id', $room_id)
    ->where('status', 'in_progress')->first();

if (!$campaign) {
    // Tạo mới trong DB::transaction
}
```

### 8.4. Thứ tự ưu tiên quy trình khi tạo equip campaign tự động
```php
CleaningEquipProcessList::where('equipment_id', $id)
    ->whereIn('status', ['active', 'approved', 'submitted'])
    ->orderByRaw("FIELD(status, 'active', 'approved', 'submitted')")
    ->orderBy('version', 'desc')
    ->first();
```

### 8.5. Route tạo equip campaign cho thiết bị di động (không có room_campaign)
```php
// Khi clean_location = 'clearing_room'
CleaningEquipCampaign::create([
    'room_campaign_id' => null,   // Không liên kết phòng sản xuất
    'clearing_room_id' => $clearingRoomId,
    'clean_location'   => 'clearing_room',
    ...
]);
```

---

## 9. Files Quan Trọng Cần Biết

| File | Vai trò |
|------|---------|
| [`CleaningProcessController.php`](app/Http/Controllers/Pages/ManuEnv/CleaningProcessController.php) | Controller chính VS phòng |
| [`CleaningEquipCampaignController.php`](app/Http/Controllers/Pages/ManuEnv/CleaningEquipCampaignController.php) | Controller campaign thiết bị |
| [`RoomClearingController.php`](app/Http/Controllers/Pages/ManuEnv/RoomClearingController.php) | CRUD phòng VS chung |
| [`CleaningRoomCampaign.php`](app/Models/CleaningRoomCampaign.php) | Model campaign phòng |
| [`CleaningEquipCampaign.php`](app/Models/CleaningEquipCampaign.php) | Model campaign thiết bị |
| [`CleaningEquipCampaignStep.php`](app/Models/CleaningEquipCampaignStep.php) | Model bước VS thiết bị |
| [`RoomClearing.php`](app/Models/RoomClearing.php) | Model phòng VS chung |
| [`campaign_execute.blade.php`](resources/views/pages/manu_env/cleaning_process/campaign_execute.blade.php) | View thực hiện VS phòng |
| [`equip_campaign_execute.blade.php`](resources/views/pages/manu_env/cleaning_process/equip_campaign_execute.blade.php) | View thực hiện VS thiết bị |
| [`room_clearing/index.blade.php`](resources/views/pages/manu_env/room_clearing/index.blade.php) | Quản lý phòng VS chung |
| [`room_clearing/dashboard.blade.php`](resources/views/pages/manu_env/room_clearing/dashboard.blade.php) | Dashboard phòng VS chung |
| [`manuEnvRoute.php`](routes/manuEnvRoute.php) | Toàn bộ routes VS |

---

## 10. Migrations đã chạy (theo thứ tự)

```
2026_05_31 — create_room_logbooks_table
2026_06_02 — create_cleaning_room_process_lists_table
2026_06_02 — create_cleaning_room_processes_table
2026_06_02 — create_cleaning_room_campaigns_table
2026_06_02 — create_cleaning_room_campaign_steps_table
2026_06_02 — create_cleaning_equip_processes_table (tên dùng cả list + steps)
2026_06_03 — add_cleaning_type_to_process_lists_tables
2026_06_04 — create_room_clearings_table
2026_06_04 — create_cleaning_equip_campaigns_table
2026_06_04 — create_cleaning_equip_campaign_steps_table
2026_06_04 — add_campaign_equip_id_to_room_logbooks_table
```
