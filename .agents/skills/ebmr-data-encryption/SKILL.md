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
| [`RunDataEncryptionService.php`](file:///c:/eBMR/app/Services/RunDataEncryptionService.php) | Service class duy nhất — toàn bộ logic encrypt/decrypt tập trung đây |
| [`EbmrExecutionController.php`](file:///c:/eBMR/app/Http/Controllers/EbmrExecutionController.php) | Controller chính ghi/đọc `ebmr_run_data` trong quá trình thực thi BMR |
| [`EbmrController.php`](file:///c:/eBMR/app/Http/Controllers/EbmrController.php) | Controller legacy — cũng ghi/đọc `ebmr_run_data` (execute cũ) |

---

## Bảng Được Mã Hoá

### `ebmr_run_data`

| Cột | Kiểu | Trạng thái | Ghi chú |
|-----|------|-----------|---------|
| `value` | longtext | ✅ **Encrypted** | JSON đầy đủ `{cellId: rawValue}`, dùng `encryptJson()` |
| `raw_value` | text | ✅ **Encrypted** | Giá trị thô người dùng nhập, dùng `encrypt()` |
| `id` | bigint | ❌ Plaintext | Primary key |
| `record_id` | bigint | ❌ Plaintext | FK — dùng để `WHERE record_id = ?` |
| `block_uuid` | varchar | ❌ Plaintext | Dùng để lookup và group dữ liệu |
| `cell_id` | varchar | ❌ Plaintext | Dùng để phân biệt ô trong bảng |
| `filled_by` | varchar | ❌ Plaintext | User ID (số nguyên) |
| `updated_by` | varchar | ❌ Plaintext | Tên người cập nhật |
| `filled_at`, `*_at` | timestamp | ❌ Plaintext | Dùng để sort/filter |

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
// Data cũ plaintext → tự fallback, không crash
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

## Kết Quả Trong phpMyAdmin

Sau khi mã hoá, DBA mở bảng `ebmr_run_data` chỉ thấy:

```
raw_value: eyJpdiI6InI5WlhmZnkwMWwzYTNGaXQ0YWVMckE9PSIsInZhbHVlIjoi...
value:     eyJpdiI6Ik9VTmpBZ2h2UWg0cHlIdktFYUxsQT09IiwidmFsdWUiOiJp...
```

---

## Khi Mở Rộng Mã Hoá Sang Bảng/Cột Khác

### Checklist bắt buộc

1. **Xác định cột nào KHÔNG mã hoá** (cột dùng để `WHERE`, `JOIN`, `ORDER BY`, `GROUP BY`)
2. **Thêm use statement** vào controller:
   ```php
   use App\Services\RunDataEncryptionService;
   ```
3. **Khi ghi (INSERT/UPDATE)**:
   ```php
   // Chuỗi đơn
   'column' => RunDataEncryptionService::encrypt($value),
   // JSON/array
   'column' => RunDataEncryptionService::encryptJson($arrayValue),
   ```
4. **Khi đọc (SELECT)**:
   ```php
   // Chuỗi đơn
   $plain = RunDataEncryptionService::decrypt($row->column);
   // JSON
   $arr = RunDataEncryptionService::decryptJson($row->column);
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
# Xem key hiện tại
php artisan key:generate --show

# File .env — KHÔNG commit lên Git
APP_KEY=base64:xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx=
```

**Backup key:**
- Lưu `APP_KEY` vào nơi an toàn tách biệt (không chỉ trong file `.env`)
- Backup `.env` định kỳ cùng với backup DB

### Nếu cần đổi APP_KEY (rotate):
```bash
# Bước 1: Decrypt toàn bộ data cũ trước khi đổi key
php artisan tinker
# Chạy script decrypt tất cả cột encrypted, lưu tạm vào cột phụ

# Bước 2: Đổi key
php artisan key:generate

# Bước 3: Re-encrypt lại với key mới
# Chạy script encrypt lại từ cột phụ

# Bước 4: Xoá cột phụ
```

> ⚠️ Rotate key là thao tác phức tạp — cần downtime và backup đầy đủ trước khi thực hiện.

---

## Debug Lỗi Thường Gặp

### 1. `DecryptException: The payload is invalid`
**Nguyên nhân**: APP_KEY bị đổi sau khi data đã được mã hoá.
**Giải pháp**: Khôi phục APP_KEY cũ từ backup.

### 2. Dữ liệu hiển thị là chuỗi `eyJpdiI6Ii...` thay vì giá trị thực
**Nguyên nhân**: Quên gọi `decrypt()` khi đọc từ DB.
**Kiểm tra**: Tìm trong controller chỗ đọc cột đó — phải có `RunDataEncryptionService::decrypt(...)`.

### 3. Lỗi `Column too long` khi INSERT
**Nguyên nhân**: Cột `varchar(N)` quá ngắn để chứa chuỗi mã hoá.
**Giải pháp**: Tăng kích thước cột hoặc đổi sang `text`.
```sql
ALTER TABLE ebmr_run_data MODIFY COLUMN raw_value TEXT;
```

### 4. Data cũ hiển thị sai sau khi bật mã hoá
**Nguyên nhân bình thường**: Data cũ là plaintext → fallback tự động xử lý → không cần lo.
**Nếu vẫn sai**: Kiểm tra xem có đúng cột đang được decrypt không.

---

## Lưu Ý Quan Trọng

> Hệ thống eBMR chạy mạng **nội bộ (intranet)** — không cần HTTPS.
> Mã hoá ở tầng DB là đủ để ngăn DBA đọc trực tiếp từ SQL client.

> Chỉ mã hoá cột **nội dung nhập liệu**, tuyệt đối không mã hoá cột dùng để `WHERE`, `JOIN`, `ORDER BY` — sẽ phá vỡ toàn bộ truy vấn.

> Laravel `Crypt` đã bao gồm **HMAC signature** — nếu chuỗi bị sửa trực tiếp trong DB, `decrypt()` sẽ ném `DecryptException` ngay lập tức. Đây là cơ chế phát hiện tampering (giả mạo dữ liệu).
