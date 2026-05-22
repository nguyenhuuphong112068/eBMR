---
name: peripheral-balance
description: >
  Hỗ trợ tích hợp và bảo trì module đọc dữ liệu từ Cân Điện Tử (A&D, Mettler Toledo, Sartorius)
  vào eBMR Designer thông qua cổng RS-232 bằng Web Serial API. Sử dụng skill này khi cần:
  - Thêm/sửa parser cân mới
  - Debug kết nối serial
  - Thêm hãng cân mới vào hệ thống
  - Điều chỉnh logic điền giá trị vào biến số
---

# Skill: Peripheral / Balance (Cân Điện Tử RS-232)

## Tổng quan kiến trúc

Module tích hợp cân điện tử sử dụng **Web Serial API** của trình duyệt để đọc dữ liệu trực tiếp từ thiết bị cân qua cổng RS-232 (hoặc USB-to-Serial Adapter), sau đó điền vào biến số kiểu `number` trong eBMR Designer.

### Các file liên quan

| File | Vai trò |
|---|---|
| [`scale_reader.blade.php`](file:///c:/eBMR/resources/views/pages/ebmr/designer/scripts/scale_reader.blade.php) | Module cốt lõi: ScaleManager + ScaleParsers + hàm điều phối |
| [`canvas.blade.php`](file:///c:/eBMR/resources/views/pages/ebmr/designer/partials/canvas.blade.php) | HTML Modal `#scaleConnectionModal` |
| [`render.blade.php`](file:///c:/eBMR/resources/views/pages/ebmr/designer/scripts/render.blade.php) | Nút ⚖️ `btn-read-scale` bên cạnh ô số trong execution mode |
| [`ui_handlers.blade.php`](file:///c:/eBMR/resources/views/pages/ebmr/designer/scripts/ui_handlers.blade.php) | Section "Kết nối Cân điện tử" trong Property Panel |
| [`styles.blade.php`](file:///c:/eBMR/resources/views/pages/ebmr/designer/partials/styles.blade.php) | CSS cho `.btn-read-scale`, `.scale-status-dot`, `.scale-live-value` |
| [`designer.blade.php`](file:///c:/eBMR/resources/views/pages/ebmr/designer.blade.php) | Include `scale_reader` |
| [`execute.blade.php`](file:///c:/eBMR/resources/views/pages/ebmr/execute.blade.php) | Include `scale_reader` |

---

## Các Object/API công khai (Public API)

### `window.ScaleManager`

Object singleton quản lý toàn bộ vòng đời kết nối RS-232.

```javascript
// Kiểm tra trình duyệt hỗ trợ Web Serial API
window.ScaleManager.isSupported()  // → boolean

// Kiểm tra trạng thái kết nối hiện tại
window.ScaleManager.isConnected()  // → boolean

// Lấy kết quả parse cuối cùng
window.ScaleManager.getLastResult() // → { value, unit, stable, brand } | null

// Kết nối đến cân (bắt buộc gọi từ gesture người dùng)
await window.ScaleManager.connect(brand, customConfig)
// brand: 'and' | 'mettler' | 'sartorius' | 'custom'
// customConfig: { baudRate, dataBits, parity, stopBits } (chỉ dùng khi brand='custom')
// → Promise<boolean>

// Ngắt kết nối
await window.ScaleManager.disconnect()

// Đăng ký nhận dữ liệu streaming (trả về hàm hủy đăng ký)
const unsubscribe = window.ScaleManager.onData(function(result) {
    console.log(result.value, result.unit, result.stable); // vd: 123.45, 'g', true
});
// Hủy đăng ký: unsubscribe()
```

### `window.ScaleParsers`

Bộ parser tĩnh cho từng hãng cân.

```javascript
// Parse theo hãng cụ thể
window.ScaleParsers.parse(rawLine, brand)
// → { value: number, unit: string, stable: boolean, brand: string } | null

// Parse tự động nhận diện (thử tuần tự A&D → Mettler → Sartorius)
window.ScaleParsers.parseAutoDetect(rawLine)

// Parse riêng từng hãng
window.ScaleParsers.parseAND(rawLine)
window.ScaleParsers.parseMettlerToledo(rawLine)
window.ScaleParsers.parseSartorius(rawLine)
```

### `window.SCALE_PRESETS`

Object cài đặt serial mặc định theo hãng.

```javascript
window.SCALE_PRESETS = {
    'and':      { label, baudRate: 9600, dataBits: 7, parity: 'even',  stopBits: 1 },
    'mettler':  { label, baudRate: 9600, dataBits: 8, parity: 'none',  stopBits: 1 },
    'sartorius':{ label, baudRate: 9600, dataBits: 8, parity: 'none',  stopBits: 1 },
    'custom':   { label, baudRate: 9600, dataBits: 8, parity: 'none',  stopBits: 1 }
};
```

### Hàm tiện ích toàn cục

```javascript
// Mở modal kết nối cân nhắm vào biến số fieldId
window.openScaleConnectionModal(fieldId)

// Đọc giá trị ổn định và điền vào biến số fieldId
// (Tự động mở modal nếu chưa kết nối)
window.readScaleValueIntoField(fieldId)

// Kết nối từ modal (dùng nội bộ bởi nút "Kết nối" trong modal)
await window.connectScaleFromModal()

// Đọc giá trị ngay từ modal và đóng modal
window.readScaleFromModal()

// Hiện/ẩn form cài đặt tùy chỉnh trong modal
window.toggleCustomScaleFields(brand)
```

---

## Giao thức dữ liệu từng hãng cân

### A&D (AND)
```
ST,GS, +00123.45 g\r\n    ← Stable (Ổn định)
US,   , +00123.45 g\r\n    ← Unstable (Rung)
OL,   ,  ----------\r\n    ← Overload (Quá tải)
```
- **Baud**: 9600 | **Data**: 7 | **Parity**: Even | **Stop**: 1
- Nhận diện: Bắt đầu bằng `ST`, `US`, `OL`

### Mettler Toledo (MT-SICS)
```
S S      +   123.45 g\r\n  ← Stable
S D      +   123.45 g\r\n  ← Dynamic (đang thay đổi)
S I \r\n                    ← Invalid
```
- **Baud**: 9600 | **Data**: 8 | **Parity**: None | **Stop**: 1
- Nhận diện: Bắt đầu bằng `S S`, `S D`, `S I`
- Commands gửi đến cân: `S` (lấy stable), `SI` (lấy ngay), `SIR` (liên tục)

### Sartorius
```
N +  123.450 g \r\n         ← Net stable
+  123.450 g \r\n           ← Dạng ngắn
```
- **Baud**: 9600 | **Data**: 8 | **Parity**: None | **Stop**: 1
- Nhận diện: Có ký tự `+` hoặc `-` trước số

---

## Luồng hoạt động

```
[Chế độ Thực thi / Chạy thử]
  → Biến số kiểu 'number' hiển thị nút ⚖️ xanh lá
  → Click nút ⚖️
      → Nếu chưa kết nối: mở #scaleConnectionModal
          → Chọn hãng cân → Click "Kết nối"
          → Web Serial API requestPort() + open()
          → Streaming bắt đầu
          → Giá trị live hiển thị real-time trong modal
          → Click "Đọc giá trị ngay" hoặc tự động sau 500ms
      → Nếu đã kết nối: subscribe nhận giá trị ổn định
          → timeout 15 giây
          → Nhận giá trị stable → điền window.executionValues[fieldId]
          → recalculateAllFormulas()
          → Re-render badge
```

---

## Cài đặt trong fieldsConfig

Mỗi biến số kiểu `number` có thể lưu cấu hình cân:
```javascript
fieldsConfig[fieldId] = {
    // ... các thuộc tính khác ...
    scaleEnabled: true,           // Bật/tắt tính năng cân
    scalePreset: 'and'            // Hãng cân mặc định: 'and' | 'mettler' | 'sartorius' | 'custom'
};
```

Cài đặt kết nối toàn phiên:
```javascript
window.scaleConfig = {
    brand: 'and',
    baudRate: 9600,
    dataBits: 7,
    parity: 'even',
    stopBits: 1
};
```

---

## Thêm hãng cân mới

1. **Thêm preset** vào `window.SCALE_PRESETS` trong `scale_reader.blade.php`:
```javascript
window.SCALE_PRESETS['myhrand'] = {
    label: 'Tên Hãng',
    icon: 'fa-balance-scale',
    baudRate: 9600,
    dataBits: 8,
    parity: 'none',
    stopBits: 1,
    description: 'Mô tả giao thức'
};
```

2. **Thêm parser** vào `window.ScaleParsers`:
```javascript
window.ScaleParsers.parseMyBrand = function(rawLine) {
    // Nhận dạng và tách giá trị từ chuỗi rawLine
    // Trả về: { value: number, unit: string, stable: boolean, brand: 'mybrand' } | null
};
```

3. **Cập nhật** hàm `parse()` trong ScaleParsers để gọi parser mới:
```javascript
case 'mybrand': return this.parseMyBrand(rawLine);
```

4. **Cập nhật** select trong `canvas.blade.php` (Modal) và `ui_handlers.blade.php` (Property Panel).

---

## Lưu ý quan trọng

> **Web Serial API — Yêu cầu trình duyệt**: Chỉ hoạt động trên Chrome/Edge 89+.
> Firefox và Safari không hỗ trợ. Nút ⚖️ sẽ tự ẩn trên trình duyệt không hỗ trợ.

> **Secure Context**: Web Serial API yêu cầu HTTPS hoặc `localhost`.
> Nếu truy cập qua địa chỉ IP nội bộ (http://192.168.x.x), API sẽ không khả dụng.
> **Giải pháp**: Dùng Chrome với flag `--unsafely-treat-insecure-origin-as-secure=http://192.168.x.x --user-data-dir=/tmp/chrome_dev`.

> **Streaming Mode**: Module hoạt động ở chế độ streaming — kết nối liên tục và lắng nghe toàn bộ
> dữ liệu từ cân. Mỗi lần "Đọc giá trị", hệ thống chờ giá trị **ổn định (stable)** đầu tiên trong 15 giây.

> **Ngắt kết nối tự động**: Khi tab/cửa sổ bị đóng, kết nối serial sẽ bị ngắt tự động bởi trình duyệt.
> Không cần xử lý thêm.
