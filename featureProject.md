# Bản Đồ Chức Năng Dự Án eBMR (Feature Map)

Tài liệu này mô tả các chức năng đã triển khai trong dự án eBMR. **Quy tắc: Khi thêm chức năng mới, bắt buộc phải cập nhật vào file này.**

---

## 1. Phân Hệ eBMR (Cốt lõi)
Quản lý quy trình sản xuất điện tử (Electronic Batch Manufacturing Record).

| Chức năng chính | Chức năng con | File liên quan | Trạng thái |
| :--- | :--- | :--- | :--- |
| **Thiết kế Biểu mẫu (Designer V2)** | Thiết kế bảng (kiểu Google Docs), Editor TipTap/ProseMirror, Comment, Dịch thuật AI | `Pages/Ebmr/Designer/EbmrDesignerController.php`, `ebmrRoute.php`, `resources/views/pages/ebmr/designer_v2.blade.php`, `resources/js/designer-v2/` | Hoàn thành (V1 đã gỡ bỏ) |
| **Quản lý Template** | Lưu metadata, phiên bản (versioning), lịch sử thay đổi | `Pages/Ebmr/Templates/EbmrTemplateController.php`, `EbmrTemplate.php` | Hoàn thành |
| **Phê duyệt (Approval)** | Luồng phê duyệt (Workflow), ký duyệt điện tử | `Pages/Ebmr/Approvals/EbmrApprovalController.php`, `ebmr_approvals` table | Hoàn thành |
| **Cấp phát (Issuance)** | Phát hành lệnh sản xuất từ template đã duyệt | `Pages/Ebmr/Issuance/EbmrIssuanceController.php` | Hoàn thành |
| **Thực thi (Execution)** | Ghi chép số liệu sản xuất thực tế, xác thực mật khẩu | `Pages/Ebmr/Records/EbmrExecutionController.php`, `designer_v2.blade.php` (chế độ thực thi) | Hoàn thành (V1 đã gỡ bỏ) |

---

## 2. Quản Lý Dữ Liệu Gốc (Master Data)
Các danh mục dùng chung toàn hệ thống.

| Chức năng chính | Chức năng con | File liên quan | Trạng thái |
| :--- | :--- | :--- | :--- |
| **Phòng ban & Nhân sự** | Quản lý Phòng ban (Department), Tổ/Nhóm | `DepartmentController.php`, `materDataRoute.php` | Hoàn thành |
| **Tài liệu & Trạng thái** | Loại tài liệu (Document Type), Trạng thái (Status) | `DocumentTypeController.php`, `StatusController.php` | Hoàn thành |
| **Nhập liệu (Import)** | Import danh mục từ Excel/File | `UploadDataController.php` | Hoàn thành |

---

## 4. Các Hệ Thống Hỗ Trợ
| Chức năng | Mô tả | File liên quan |
| :--- | :--- | :--- |
| **Chat & Thảo luận** | Hệ thống chat nội bộ về dự án/biểu mẫu | `ChatController.php`, `ChatRoute.php` |
| **Thông báo** | Thông báo đẩy, nhắc việc phê duyệt | `NotificationController.php` |
| **Lịch sử hệ thống** | Nhật ký thao tác người dùng (Audit Trail) | `AuditTrialRoute.php` |

---

## 5. Chi Tiết Kỹ Thuật Các Chức Năng Trọng Điểm

### 5.1 Thiết kế Biểu mẫu (Designer)
Trình soạn thảo cốt lõi để tạo ra các template hồ sơ điện tử.
*   **Trải nghiệm người dùng:** Giao diện phong cách Google Docs, hỗ trợ thao tác bảng nâng cao (Gộp ô, Tách ô, Màu nền), Editor dạng khối (Block-based) có thể kéo thả.
*   **Thành phần dữ liệu (Dynamic Fields):** Chèn các trường nhập liệu động (Văn bản, Số, Ngày, Tickbox, Dropdown) và các ô Chữ ký điện tử (Signature) vào trong văn bản.
*   **Tính năng AI & Đa ngôn ngữ:** 
    *   Tự động dịch nội dung từ Tiếng Việt sang Tiếng Anh chuyên ngành dược bằng AI (Ollama/Qwen 2.5).
    *   Hỗ trợ chế độ xem Song ngữ (Dual language) bằng cách tiêm nội dung động vào placeholder.
*   **Kiểm soát & Quản lý:** 
    *   **Revision History:** Tự động so sánh và ghi lại nhật ký thay đổi (Thêm/Xóa/Sửa).
    *   **Commenting:** Hệ thống bình luận và phản hồi ngay trên từng vùng văn bản/bảng.
    *   **ReadOnly Logic:** Tự động khóa biên tập khi hồ sơ đang trong luồng phê duyệt hoặc không thuộc quyền sở hữu.

*   **Cấu trúc giao diện (Frontend Partials):**
    *   `toolbar.blade.php`: Thanh công cụ định dạng và chèn đối tượng (Google Docs style).
    *   `canvas.blade.php`: Không gian làm việc chính (Trang A4, Mục lục, Lề bình luận).
    *   `sidebar.blade.php`: Bảng cài đặt thuộc tính (Property Panel) cho các đối tượng được chọn.
    *   `modals.blade.php`: Các hộp thoại pop-up (Mở hồ sơ, Lịch sử, Ký tự đặc biệt).
    *   `styles.blade.php`: Định nghĩa toàn bộ CSS tùy chỉnh cho trình thiết kế.

*   **Cấu trúc Logic (Frontend Scripts):**
    *   `state.blade.php`: Quản lý trạng thái (items, orientation, undo/redo).
    *   `render.blade.php`: Logic hiển thị (vẽ blocks lên trang, xử lý kéo thả).
    *   `persistence.blade.php`: Giao tiếp máy chủ (Save template, AI translation, Load history).
    *   `ui_handlers.blade.php`: Xử lý tương tác (định dạng văn bản, phím tắt, toolbar).
        *   `selectItem()`: Kích hoạt khối nội dung, hiển thị sidebar thuộc tính và Ruler.
        *   `formatDoc()`: Định dạng văn bản/ô bảng (Bold, Italic, Align...).
        *   `addItem()` / `addSection()`: Chèn khối nội dung hoặc phân đoạn mới.
        *   `toggleSidebar()` / `toggleOutline()`: Ẩn/hiện các thanh điều hướng và cài đặt.
        *   `updateTableCellProp()`: Cập nhật thuộc tính ô bảng (ID ô, giá trị mặc định).
        *   `translateBlockWithAI()`: Gọi AI dịch thuật cho khối hoặc ô đang chọn.
        *   `toggleFormatPainter()`: Sao chép định dạng giữa các khối/văn bản.
        *   `executeReplaceAll()`: Tìm kiếm và thay thế văn bản toàn cục.
    *   `table_ops.blade.php` & `table_advanced.blade.php`: Thao tác bảng (Thêm/Xóa dòng, Gộp ô, Tách ô, Resize).
    *   `variable_ops.blade.php`: Quản lý các biến số động (Input fields, Signatures).
    *   `outline.blade.php`: Tự động tạo mục lục điều hướng tài liệu.
    *   `comments.blade.php`: Hệ thống bình luận và phản hồi trên tài liệu.
    *   `events.blade.php`: Lắng nghe sự kiện (Click, Paste, Resize).

---

## Ghi chú quan trọng cho AI
- Trước khi bắt đầu một yêu cầu mới: **Đọc file này** để không làm hỏng các đường dẫn (routes) và controller hiện có.
- Khi sửa đổi logic: Kiểm tra xem các file trong cột "File liên quan" có bị ảnh hưởng chéo hay không.
- Sau khi hoàn thành: Cập nhật file này nếu có thêm file mới hoặc thay đổi trạng thái.
