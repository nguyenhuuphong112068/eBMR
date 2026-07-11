---
name: verify-designer-v2
description: Checklist kiểm thử thủ công chức năng CHỌN đối tượng (selection kiểu Word) trong trình thiết kế eBMR V2. Dùng sau khi sửa resources/js/designer-v2/ (main.js, selection.js, ebmr-field.js) hoặc designer_v2.blade.php.
---

# Verify — Chức năng CHỌN trong Trình thiết kế V2

## Chuẩn bị

1. `npm run build` (Vite) — bundle nằm ở `public/build/assets/main-*.js`.
2. Mở trang thiết kế V2 của một template có ít nhất 1 bảng ≥3×3 và vài biến số. **Ctrl+F5** để nạp bundle mới.
3. Đảm bảo đang ở chế độ **Thiết kế** (nút toggle `v2-btn-toggle-mode` màu xanh dương, chưa bật Chạy thử).

## Checklist 11 thao tác chọn (theo spec)

| # | Thao tác | Cách làm | Kết quả mong đợi |
|---|---------|----------|------------------|
| 1 | Chọn 1 bảng | Rê chuột vào bảng → click nút ⊕ góc trên-trái | Cả bảng viền xanh, mọi ô bôi xanh |
| 2 | Chọn 1 ô | Rê chuột sát mép trái/trên ô (con trỏ thành mũi tên đen) → click | Ô đó viền xanh + nền xanh nhạt; KHÔNG mở editor |
| 3 | Ctrl+Click | Ctrl+click lần lượt vài ô rời rạc | Từng ô toggle chọn/bỏ, không liên tục |
| 4 | Shift+Click | Chọn 1 ô (mép), rồi Shift+click ô khác cùng bảng | Chọn cả khối chữ nhật giữa 2 ô |
| 5 | Chọn cả hàng | Rê chuột vào dải hẹp BÊN TRÁI bảng (mũi tên đen) → nhấp đúp | Cả hàng được chọn |
| 6 | Chọn cả cột | Rê chuột vào dải hẹp PHÍA TRÊN bảng (mũi tên đen) → nhấp đúp | Cả cột được chọn (đúng cả khi hàng đầu có ô gộp) |
| 7 | Kéo chọn khối | Đặt chuột trong 1 ô, ghì trái và kéo sang ô khác | Vùng chữ nhật bôi xanh theo chuột; không bôi đen chữ |
| 8 | Đặt con trỏ | Click vào GIỮA 1 ô hoặc đoạn văn | Mở editor TipTap, gõ được ngay (hành vi cũ giữ nguyên) |
| 9 | Bôi đen văn bản | Trong editor đang mở, ghì chuột kéo qua chữ | Bôi đen chữ native bình thường, KHÔNG thành chọn ô |
| 10 | Chọn 1 biến số | Click vào 1 badge biến số | Badge có viền xanh + mở panel thuộc tính |
| 11 | Chọn nhiều biến số | Ctrl+Alt+ghì kéo khung quét qua nhiều badge | Khung marquee nét đứt; nhả chuột → các badge viền xanh + mở panel sửa hàng loạt |

## Thao tác trên vùng chọn

- **Escape** → bỏ chọn tất cả (nếu đang mở editor thì đóng editor trước).
- **Delete/Backspace** khi đang chọn ô → xóa nội dung các ô; **Ctrl+Z** khôi phục.
- **Ctrl+C / Ctrl+X** khi đang chọn ô → sao chép/cắt vùng chữ nhật (nội dung + định dạng nền/căn/đậm/nghiêng). Cắt = copy rồi xóa nội dung. Nếu đang bôi đen chữ trong editor thì nhường copy văn bản native.
- **Ctrl+V** → chọn ô đích (click mép để chọn) rồi dán: dán lưới bắt đầu từ ô anchor; nếu clipboard 1 ô mà chọn nhiều ô đích thì tô đều tất cả. **Ô đích giữ nguyên rs/cs/hidden** (chỉ nhận nội dung + định dạng). **Ctrl+Z** khôi phục.
- **Dán biến số**: ô nguồn chứa biến → mỗi ô dán ra được nhân bản thành biến ID MỚI (config clone riêng), 2 ô KHÔNG trỏ chung 1 biến. Sửa biến ở ô này không ảnh hưởng ô kia.
- Nếu chưa chọn ô đích (hoặc đang gõ trong editor) → Ctrl+V chạy dán Word/Excel native như cũ.
- Click lề trang (ngoài bảng) → bỏ chọn.
- Toolbar **đậm / nghiêng / căn lề / màu nền (highlight)** áp dụng cho TẤT CẢ ô đang chọn; nút phản chiếu trạng thái ô anchor.
- Nút **Gộp ô** (fa-object-group): chọn ≥2 ô cùng bảng → gộp, nội dung nối lại, badge không mất. **Ctrl+Z** hủy gộp.
- Nút **Tách ô** (fa-object-ungroup): chọn đúng 1 ô đã gộp → khôi phục lưới.
- Panel hàng loạt: đổi Kiểu dữ liệu → badge repaint; "Xóa tất cả N biến" → badge + config sạch.

## Guard chế độ

- Bật **Chạy thử** (`v2-btn-toggle-mode`): mọi thao tác chọn phải CHẾT (không gutter, không nút ⊕, không chọn ô); click badge mở modal nhập liệu. Selection đang có phải bị xóa khi chuyển mode.
- Chế độ **read-only**: như trên.
- Bảng **locked/virtual** (header hệ thống): không có nút ⊕/gutter, không chọn được ô.

## Regression bắt buộc

- Lưu (`v2-btn-save`) → mở lại: style ô (đậm/căn/nền) và ô gộp (rs/cs/hidden) giữ nguyên.
- Dán Word/Excel (cả khi đang gõ trong ô lẫn khi chưa mở editor) vẫn hoạt động.
- Kéo resize cột/hàng (`.v2-col-resizer`/`.v2-row-resizer`) không bị thao tác chọn nuốt mất.
- Nút bình luận trên block, thanh chèn khối (inserter), kéo-thả Thiết bị/Thành phần CO vẫn chạy.
