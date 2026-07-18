---
name: peripheral-balance
description: >
  Hỗ trợ tích hợp và bảo trì module đọc dữ liệu từ Cân Điện Tử (A&D, Mettler Toledo, Sartorius)
  vào eBMR Designer V2 qua RS-232 (Web Serial API) hoặc WebSocket Bridge (NPort). Sử dụng khi cần:
  - Thêm/sửa parser cân mới
  - Debug kết nối serial / bridge
  - Thêm hãng cân mới vào hệ thống
  - Điều chỉnh logic điền giá trị vào biến số
---

# Skill: Peripheral / Balance (Cân Điện Tử RS-232)

## Tổng quan kiến trúc

Module dùng **Web Serial API** của trình duyệt đọc dữ liệu trực tiếp từ cân qua RS-232 (hoặc USB-to-Serial), điền vào biến số kiểu `number` trong eBMR Designer V2. Trình soạn thảo V1 đã bị xoá — module chỉ còn trong V2, namespaced dưới `window.__V2__`.

| File | Vai trò |
|---|---|
| [scale-reader.js](resources/js/designer-v2/scale-reader.js) | Module cốt lõi: ScaleManager + ScaleParsers + hàm điều phối, khởi tạo qua `initScaleReaderV2(BOOT)` |
| [designer_v2.blade.php](resources/views/pages/ebmr/designer_v2.blade.php) | HTML Modal `#scaleConnectionModal` + CSS; `window.SCALE_DEVICES` nạp từ bảng `instrument` (type='scale') |
| `scale-bridge.js` (gốc dự án) | WebSocket Bridge chạy trên server (cổng 8090) chuyển tiếp browser ↔ TCP NPort |

## Public API

> V2 gắn vào `window.__V2__` (VD: `window.__V2__.ScaleManager.connect(...)`). Riêng các hàm điều khiển modal (`connectScaleFromModal`, `readScaleFromModal`, `toggleCustomScaleFields`, `toggleScaleDetails`, `onScaleDeviceSelected`, `onChangeScaleConnectionType`) vẫn gắn thẳng `window` để khớp `onclick=""` inline trong modal.

### `window.__V2__.ScaleManager` (singleton)

```javascript
ScaleManager.isSupported()   // trình duyệt hỗ trợ Web Serial API? → boolean
ScaleManager.isConnected()   // → boolean
ScaleManager.getLastResult() // → { value, unit, stable, brand } | null

// Kết nối (bắt buộc gọi từ gesture người dùng)
await ScaleManager.connect(brand, customConfig)
// brand: 'and' | 'mettler' | 'sartorius' | 'custom'
// customConfig: { baudRate, dataBits, parity, stopBits } (chỉ khi brand='custom')

await ScaleManager.disconnect()

// Streaming (trả về hàm hủy đăng ký)
const unsubscribe = ScaleManager.onData((r) => console.log(r.value, r.unit, r.stable));
```

### `ScaleParsers` + `SCALE_PRESETS` (module-scope trong scale-reader.js)

```javascript
ScaleParsers.parse(rawLine, brand)      // → { value, unit, stable, brand } | null
ScaleParsers.parseAutoDetect(rawLine)   // thử tuần tự A&D → Mettler → Sartorius
ScaleParsers.parseAND / parseMettlerToledo / parseSartorius
```

### Hàm tiện ích

```javascript
window.openScaleConnectionModal(fieldId) // mở modal nhắm vào biến fieldId
window.readScaleValueIntoField(fieldId)  // đọc giá trị ổn định điền vào biến (tự mở modal nếu chưa kết nối)
```

## Giao thức từng hãng cân

### A&D — Baud 9600, Data 7, Parity Even, Stop 1; nhận diện dòng bắt đầu `ST`/`US`/`OL`
```
ST,GS, +00123.45 g\r\n    ← Stable
US,   , +00123.45 g\r\n    ← Unstable
OL,   ,  ----------\r\n    ← Overload
```

### Mettler Toledo (MT-SICS) — Baud 9600, Data 8, Parity None, Stop 1; nhận diện `S S`/`S D`/`S I`
```
S S      +   123.45 g\r\n  ← Stable
S D      +   123.45 g\r\n  ← Dynamic
S I \r\n                    ← Invalid
```
Commands gửi đến cân: `S` (lấy stable), `SI` (lấy ngay), `SIR` (liên tục).

### Sartorius — Baud 9600, Data 8, Parity None, Stop 1; nhận diện `+`/`-` trước số
```
N +  123.450 g \r\n
+  123.450 g \r\n
```

## Luồng hoạt động

```
[Chạy thử / Thực thi] → biến 'number' hiện nút ⚖️ đỏ gradient
  → Click ⚖️ → #scaleConnectionModal (hiện ngay giá trị cache gần nhất nếu có)
  → Chưa kết nối: chọn hãng → "Kết nối" → requestPort() + open() → streaming
  → Ghi nhận:
      • Tự động: nhận giá trị stable đầu tiên → điền vào biến + đóng modal
      • Thủ công: "Đọc giá trị ngay" → lấy giá trị hiện tại + đóng modal
  → Cleanup listener khi ẩn modal (hidden.bs.modal)
```

Cấu hình theo biến số: `fieldsConfig[fieldId] = { scaleEnabled: true, scalePreset: 'and' | ... }`.

## Thêm hãng cân mới

1. Thêm preset vào hằng `SCALE_PRESETS` (module-scope) trong `scale-reader.js`.
2. Thêm `parseMyBrand(rawLine)` vào `ScaleParsers` → trả `{ value, unit, stable, brand } | null`.
3. Thêm `case 'mybrand':` vào `ScaleParsers.parse()`.
4. Cập nhật `<select id="scale-brand-select">` trong `designer_v2.blade.php` (modal `#scaleConnectionModal`).

## WebSocket Bridge (NPort, chạy ngầm bằng PM2)

Triển khai server Linux nhiều máy trạm → nhiều cân qua NPort: script `scale-bridge.js` (cổng `8090`) tại gốc dự án chuyển tiếp WebSocket ↔ TCP, không cần map COM ảo trên máy con.

```bash
sudo npm install -g pm2
cd /var/www/eBMR
pm2 start scale-bridge.js --name "ebmr-scale-bridge"
pm2 save && pm2 startup   # tự chạy khi reboot (chạy thêm lệnh sinh ra nếu có)
# Quản lý: pm2 list | pm2 logs ebmr-scale-bridge | pm2 stop/restart ebmr-scale-bridge
```

## Lưu ý quan trọng

> **Web Serial API**: chỉ Chrome/Edge 89+. Firefox/Safari không hỗ trợ — nút ⚖️ tự ẩn.

> **Secure Context**: cần HTTPS hoặc `localhost`. Truy cập qua IP nội bộ (http://192.168.x.x) → API không khả dụng. Giải pháp: Chrome flag `--unsafely-treat-insecure-origin-as-secure=http://192.168.x.x --user-data-dir=/tmp/chrome_dev`, hoặc dùng WebSocket Bridge.

> **Streaming Mode**: kết nối liên tục; mỗi lần "Đọc giá trị" chờ giá trị **stable** đầu tiên trong 15 giây.

> **Ngắt kết nối tự động**: đóng tab/cửa sổ → trình duyệt tự ngắt serial, không cần xử lý thêm.
