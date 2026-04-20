# Project Coding Rules: Hồ Sơ Lô Điện Tử (eBMR)

## 1. Project Vision & Core Philosophy
- **Name**: eBMR - Electronic Batch Manufacturing Record.
- **Objective**: provide a flexible system to design and generate various documents, forms, and profiles.
- **Dynamic Nature**: Forms are NOT hard-coded. Users design forms through a "Form Builder" interface.
- **Data Strategy**: Use a **Dynamic JSON** structure for both form definitions (schema) and user-submitted data. This allows for total flexibility without migrating the database for every new form type.

## 2. Technical Stack
- **Backend**: Laravel 12+ (Eloquent, JSON field support).
- **Frontend**: React 19+ (Vite, Inertia/React-Router).
- **UI Components**: PrimeReact (Tables, Inputs), React Bootstrap (Layout), SweetAlert2 (Dialogs).
- **Styling**: Vanilla CSS + Tailwind (for utilities). Focus on modern aesthetics.

## 3. Database & Data Pattern (JSON First)
- **JSON Storage**: Detailed form structures and submission data must be stored in `json` or `jsonb` columns.
- **Querying**: Use Laravel's JSON path arrows `->` or `->>` for querying specific keys within the dynamic data.
- **Migration Policy**: Only migrate core metabolic fields (ID, code, status, timestamps, user_id). Avoid adding content-specific columns; use the JSON payload instead.
- **Standard Return Format**:
  ```json
  {
    "success": boolean,
    "data": any,
    "message": string
  }
  ```

## 4. Form Designer Components
- **Modularity**: Every form element (Input, Radio, Checkbox, Text Editor, File Upload) must be a standalone React component.
- **Persistence**: Components must support a unified `value` and `onChange` prop to sync with the central JSON state.
- **Rich Text**: Use a high-quality editor (e.g., Quill or CKEditor integrated into PrimeReact) for descriptions.
- **Validation Schema**: Form designs should include metadata for validation (required, regex, min/max).

## 5. UI/UX Standards (Premium & Professional)
- **Design Philosophy**: Modern, High-Precision, and Professional. 
- **Color Palette**: 
    - *Primary*: Deep Navy (`#003A4F`) - Symbolizes stability and authority.
    - *Secondary*: Gold/Antique Gold (`#CDC717`) - For highlights and call-to-actions.
    - *Background*: Neutral light grey (`#F8F9FA`) or glassmorphic layers.
- **Aesthetics**:
    - **Glassmorphism**: Use for sidebars, cards, and modal backdrops.
    - **Rounded Corners**: Strict `border-radius: 12px` for all elements (cards, inputs, buttons).
    - **Shadows**: Soft, multi-layered shadows (`0 8px 30px rgba(0,0,0,0.04)`).
    - **Transitions**: Global `0.3s ease-in-out` for all interactive states.
- **Typography**: 
    - **Arimo** (Sans-serif) is the official project font.
    - Captions and module headers should be **UPPERCASE** with `1px` letter spacing.

## 6. Coding Logic & Performance
- **Separation of Concerns**: Keep business logic out of the view layer. Use Hooks for complex state and Services for API logic.
- **Audit Trails**: Since eBMR is critical, every change to a dynamic record must be logged (who, when, what changed).
- **Version Control**: Form templates should be versioned. Submitting data to an old template version must still work.
- **Performance**: Use memoization (`useMemo`, `useCallback`) for the form builder to prevent lag with large schemas.

## 7. Error & Data Integrity
- **Safety**: Always wrap JSON parsing/encoding in try/catch to handle malformed data.
- **Permissions**: Implement strict check-access logic. Certain forms may be "Locked" after signing/completion.
- **Feedback**: Every button click should have visual feedback (loading spinners, success toasts).

## 8. Designer Architecture (Blade-based)
- **Monolith Avoidance**: Large designer files (>1000 lines) must be split into a modular directory structure under `resources/views/pages/ebmr/designer/`.
- **Directory Structure**:
    - `partials/`: Contains reusable HTML fragments (toolbar, sidebar, canvas, modals).
    - `scripts/`: Contains functional logic split by domain (state, render, table-logic, events).
- **Style Management**: 
    - Styles should be placed in `partials/styles.blade.php` and included via `@include`.
- **JS Integration**: 
    - Since Blade partials are included server-side, maintain a specific inclusion order to ensure global state variables (in `scripts/state.blade.php`) are initialized before other functional scripts.
- **Naming Convention**: 
    - Use snake_case for partial filenames (e.g., `table_ops.blade.php`).
- **State Management**: 
    - Centralize the `items` array and `saveState()` logic in a core state partial to maintain a single source of truth across modules.

## 9. Testing & Development Credentials
- **Admin Access**: 
    - *Username*: `Admin`
    - *Password*: `Abc@1234`

## 10. Operation Modes (3 Chế Độ Hoạt Động)
Hệ thống eBMR có 3 chế độ hoạt động cơ bản với quyền hạn và thao tác phân biệt rõ ràng:
1. **Chế độ "Soạn hồ sơ" (Drafting Mode)**: 
   - Dành cho người dùng cấp 1 (Thiết kế / QA). 
   - Cho phép toàn quyền thay đổi nội dung, cấu trúc biểu mẫu (thêm/sửa/xoá bảng, chữ).
   - Dữ liệu cấu trúc lưu vào bảng `ebmr_template_blocks` thuộc `ebmr_templates`.
2. **Chế độ "Kiểm tra phê duyệt" (Approval Mode)**: 
   - Không cho phép thay đổi bất kỳ nội dung hay cấu trúc biểu mẫu nào.
   - Chỉ được phép thao tác tính năng **Bình luận (Comment)** để làm rõ, yêu cầu sửa đổi hoặc phản hồi, có chức năng highlight vị trí được chọn.
3. **Chế độ "Ghi chép" (Execution Mode)**: 
   - Dành cho người dùng cấp 2 (Sản xuất / Vận hành).
   - Không cho phép thay đổi cấu trúc biểu mẫu.
   - Chỉ được phép ghi nội dung (nhập dữ liệu văn bản) vào các **ô dữ liệu (cells)** trong bảng chưa bị khoá và click **Ký tên**.
   - Dữ liệu thu thập ở bước này KHÔNG BỊ LƯU đè vào bảng `template_block` chứa cấu trúc mẫu. Thay vào đó, nó được tách biệt và lưu rải rác từng ô dữ liệu vào bảng `ebmr_run_data` mapping với `record_id` trong `ebmr_records`.
