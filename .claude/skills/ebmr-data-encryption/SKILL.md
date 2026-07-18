---
name: ebmr-data-encryption
description: >
  Hỗ trợ bảo trì và mở rộng tính năng mã hoá dữ liệu nhập liệu BMR trong hệ thống eBMR.
  Mục tiêu: Ngăn DBA (Admin DB) đọc trực tiếp nội dung nhạy cảm từ SQL client (phpMyAdmin, MySQL Workbench).
  Sử dụng skill này khi cần:
  - Mở rộng mã hoá sang bảng/cột khác trong eBMR
  - Debug lỗi giải mã (DecryptException)
  - Hiểu luồng encrypt/decrypt trong controller
  - Xử lý migration dữ liệu cũ (plaintext → encrypted)
  - Rotate APP_KEY hoặc re-encrypt data
---

# Skill: Mã Hoá Dữ Liệu eBMR (Selective Encryption)

## Tổng Quan

Hệ thống dùng **Laravel Crypt** (AES-256-CBC + HMAC signature) để mã hoá **có chọn lọc** các cột chứa nội dung người dùng nhập. Tất cả logic tập trung trong một Service class.

### Nguyên tắc "Selective Encryption"
- ✅ **Mã hoá**: Các cột chứa nội dung nhập liệu (value, raw_value)
- ❌ **Không mã hoá**: Các cột dùng để query, join, index (id, record_id, block_uuid, cell_id, timestamps)

---

## Các File Liên Quan

| File | Vai trò |
|------|---------|
| [RunDataEncryptionService.php](app/Services/RunDataEncryptionService.php) | Service class duy nhất — toàn bộ logic encrypt/decrypt tập trung đây |
| [EbmrExecutionController.php](app/Http/Controllers/Pages/Ebmr/Records/EbmrExecutionController.php) | Controller duy nhất ghi/đọc `ebmr_run_data` trong quá trình thực thi BMR (`updateRecordData`, `execute`, `getRunDataHistory`) |

> `EbmrController` legacy đã bị xóa cùng trình soạn thảo V1 — mọi đường ghi/đọc giờ chỉ qua `EbmrExecutionController`.

---

## Bảng Được Mã Hoá

### `ebmr_run_data`

| Cột | Kiểu | Trạng thái | Ghi chú |
|-----|------|-----------|---------|
| `value` | longtext | ✅ **Encrypted** | JSON đầy đủ `{cellId: rawValue}`, dùng `encryptJson()` |
| `raw_value` | text | ✅ **Encrypted** | Giá trị thô người dùng nhập, dùng `encrypt()` |
| `id` | bigint | ❌ Plaintext | Primary key |
| `record_id` | bigint | ❌ Plaintext | FK — dùng để `WHERE record_id = ?` |
| `block_uuid` | varchar | ❌ Plaintext | Dùng để lookup và group dữ liệu (`__na__` cho gạch chéo N/A) |
| `cell_id` | varchar | ❌ Plaintext | Dùng để phân biệt ô trong bảng |
| `filled_by` | varchar | ❌ Plaintext | User ID (số nguyên) |
| `updated_by` | varchar | ❌ Plaintext | Tên người cập nhật |
| `filled_at`, `*_at` | timestamp | ❌ Plaintext | Dùng để sort/filter |

### `ebmr_run_data_history`
`old_raw_value` và `new_raw_value` cũng **bắt buộc** mã hoá qua `encrypt()` (xem skill `audit-trail-variable-history`).

---

## API của `RunDataEncryptionService`

```php
use App\Services\RunDataEncryptionService;

// Mã hoá chuỗi đơn (raw_value)
RunDataEncryptionService::encrypt(string|null $value): ?string

// Giải mã chuỗi (có fallback cho data cũ plaintext)
RunDataEncryptionService::decrypt(string|null $value): ?string

// Mã hoá JSON: array/object → JSON string → encrypt
RunDataEncryptionService::encryptJson(mixed $value): ?string

// Giải mã JSON: decrypt → json_decode (có fallback cho JSON cũ plaintext)
RunDataEncryptionService::decryptJson(string|null $value, bool $assoc = true): mixed
```

### Fallback tự động (data cũ)
Tất cả hàm `decrypt*` đều có `try/catch`: nếu chuỗi không phải định dạng mã hoá của Laravel → trả về nguyên gốc. **Không cần migration dữ liệu cũ.**

```php
RunDataEncryptionService::decrypt('old-plain-text'); // → 'old-plain-text'
RunDataEncryptionService::decryptJson('{"r0c1":"old"}'); // → ['r0c1' => 'old']
```

---

## Luồng Hoạt Động

```
[Người dùng nhập liệu BMR]
    → Frontend gửi POST với raw data (plaintext)
    → EbmrExecutionController::updateRecordData()
        → RunDataEncryptionService::encryptJson($jsonValue)  ← mã hoá value
        → RunDataEncryptionService::encrypt($rawValue)       ← mã hoá raw_value
        → DB::table('ebmr_run_data')->updateOrInsert(...)    ← lưu chuỗi mã hoá

[Người dùng mở lại form BMR]
    → EbmrExecutionController::execute($id)
        → DB::table('ebmr_run_data')->where('record_id', $id)->get()
        → RunDataEncryptionService::decrypt($rd->raw_value)  ← giải mã
        → $executionValues → truyền xuống View → hiển thị đúng
```

---

## Khi Mở Rộng Mã Hoá Sang Bảng/Cột Khác

### Checklist bắt buộc

1. **Xác định cột nào KHÔNG mã hoá** (cột dùng để `WHERE`, `JOIN`, `ORDER BY`, `GROUP BY`)
2. **Thêm use statement** vào controller: `use App\Services\RunDataEncryptionService;`
3. **Khi ghi (INSERT/UPDATE)**:
   ```php
   'column' => RunDataEncryptionService::encrypt($value),        // chuỗi đơn
   'column' => RunDataEncryptionService::encryptJson($arrayValue), // JSON/array
   ```
4. **Khi đọc (SELECT)**:
   ```php
   $plain = RunDataEncryptionService::decrypt($row->column);
   $arr   = RunDataEncryptionService::decryptJson($row->column);
   ```
5. **Tăng kích thước cột** nếu cần — chuỗi mã hoá dài hơn plaintext ~50-80%:
   - `varchar(255)` → cần ít nhất `varchar(400)` hoặc đổi sang `text`
   - `text` → giữ nguyên (đủ dung lượng)

---

## Quản Lý APP_KEY (Quan Trọng Nhất)

> [!CAUTION]
> `APP_KEY` trong `.env` là **khoá duy nhất** để giải mã toàn bộ dữ liệu.
> Mất key = mất data vĩnh viễn, không khôi phục được.

```bash
php artisan key:generate --show   # Xem key hiện tại
# File .env — KHÔNG commit lên Git
```

**Backup key:** Lưu `APP_KEY` vào nơi an toàn tách biệt; backup `.env` định kỳ cùng backup DB.

### Nếu cần đổi APP_KEY (rotate):
1. Decrypt toàn bộ data cũ, lưu tạm vào cột phụ.
2. `php artisan key:generate` (đổi key).
3. Re-encrypt lại từ cột phụ với key mới.
4. Xoá cột phụ.

> ⚠️ Rotate key cần downtime và backup đầy đủ trước khi thực hiện.

---

## Debug Lỗi Thường Gặp

1. **`DecryptException: The payload is invalid`** — APP_KEY bị đổi sau khi data đã mã hoá → khôi phục APP_KEY cũ từ backup.
2. **Hiển thị chuỗi `eyJpdiI6Ii...` thay vì giá trị thực** — quên `decrypt()` khi đọc → tìm chỗ đọc cột đó trong controller.
3. **`Column too long` khi INSERT** — cột `varchar(N)` quá ngắn → `ALTER TABLE ... MODIFY COLUMN raw_value TEXT;`
4. **Data cũ hiển thị sai sau khi bật mã hoá** — data cũ plaintext được fallback tự động; nếu vẫn sai, kiểm tra đúng cột đang decrypt không.

---

## Lưu Ý Quan Trọng

> Hệ thống eBMR chạy mạng **nội bộ (intranet)** — không cần HTTPS. Mã hoá ở tầng DB là đủ để ngăn DBA đọc trực tiếp từ SQL client.

> Chỉ mã hoá cột **nội dung nhập liệu**, tuyệt đối không mã hoá cột dùng để `WHERE`, `JOIN`, `ORDER BY` — sẽ phá vỡ toàn bộ truy vấn.

> Laravel `Crypt` đã bao gồm **HMAC signature** — nếu chuỗi bị sửa trực tiếp trong DB, `decrypt()` ném `DecryptException` ngay: đây là cơ chế phát hiện tampering (giả mạo dữ liệu).
