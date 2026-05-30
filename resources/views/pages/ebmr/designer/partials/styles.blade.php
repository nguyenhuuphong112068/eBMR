<style>
    .editor-toolbar {
        position: sticky;
        top: 57px;
        /* AdminLTE header height */
        z-index: 1000;
        border-bottom: 1px solid #ddd;
    }

    .btn-toolbar {
        width: 34px;
        height: 34px;
        padding: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        border: none;
        background: transparent;
        border-radius: 4px;
        color: #444;
    }

    .btn-toolbar:hover {
        background: #f1f3f4;
    }

    .btn-toolbar-action {
        padding: 4px 12px;
        border: 1px solid transparent;
        background: transparent;
        border-radius: 4px;
        font-size: 0.85rem;
        font-weight: 500;
        color: #444;
    }

    .btn-toolbar-action:hover {
        background: #e8f0fe;
        color: #1a73e8;
    }

    .doc-title-input {
        background: transparent;
        border: 1px solid transparent;
        font-size: 1.25rem;
        font-weight: 500;
        padding: 5px 10px;
        border-radius: 4px;
        transition: 0.2s;
    }

    .doc-title-input:hover {
        background: #fff;
        border-color: #ddd;
    }

    .doc-title-input:focus {
        background: #fff;
        border-color: #1a73e8;
        outline: none;
        box-shadow: 0 0 0 2px rgba(26, 115, 232, 0.1);
    }

    .page-a4 {
        background: white;
        min-height: 100vh;
        width: 100% !important;
        max-width: none !important;
        border-radius: 4px;
        margin: 0 !important;
        position: relative;
        padding: 40px 40px !important;
        /* Reduced margins for even more space */
        transition: padding 0.3s ease;
        box-shadow: none !important;
        border: 1px solid #ddd;
    }

    .btn-navy {
        background: #003A4F;
        color: white;
        transition: 0.3s;
        font-weight: 600;
    }

    .btn-navy:hover {
        background: #002a3a;
        box-shadow: 0 4px 12px rgba(0, 58, 79, 0.2);
    }

    .text-navy {
        color: #003A4F;
    }

    /* Component Styling in Doc Mode */
    .block-item {
        position: relative;
        padding: 5px;
        border: 1px solid #eef0f2;
        /* Viền mờ mặc định */
        border-radius: 4px;
        margin-bottom: 2px;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .block-item:hover {
        border-color: #cbd5e0;
        background: #fdfdfd;
    }

    .block-item.active {
        border-color: #1a73e8;
        background: #f8faff;
        box-shadow: 0 0 0 1px #1a73e8;
    }

    /* Ẩn viền hoàn toàn ở chế độ ghi chép/in */
    .execution-mode.block-item,
    .page-a4.readonly .block-item {
        border: 1px solid transparent !important;
        padding: 0 !important;
        margin-bottom: 0 !important;
        background: transparent !important;
    }

    .block-label {
        font-weight: bold;
        color: #5f6368;
        font-size: 0.85rem;
        margin-bottom: 5px;
        display: block;
    }

    .block-mock {
        min-height: 40px;
        background: #fdfdfd;
        border: 1px solid #dadce0;
        border-radius: 4px;
        border-left: 4px solid #003A4F;
    }

    .block-actions {
        position: absolute;
        right: -40px;
        top: 10px;
        display: flex;
        flex-direction: column;
        gap: 5px;
        opacity: 0;
        transition: 0.2s;
    }

    .block-item:hover .block-actions {
        opacity: 1;
        right: -45px;
    }

    .mini-table {
        width: auto;
        min-width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
    }

    .mini-table th {
        background: #f8f9fa;
        border: 1px solid #dadce0;
        padding: 8px;
        font-size: 0.75rem;
        text-align: center;
        color: #333;
        font-weight: 600;
    }

    .mini-table td {
        border: 1px solid #dadce0;
        padding: 5px 12px;
        text-align: left;
        color: #333;
        font-size: 0.95rem;
        min-height: 40px;
        vertical-align: middle;
    }

    .locked-cell {
        background-color: #fafafa !important;
        cursor: default !important;
    }

    .block-item[locked="true"] {
        cursor: default !important;
    }

    .block-item[locked="true"]:hover {
        border-color: transparent !important;
        background: transparent !important;
    }

    .mini-table th[contenteditable="true"],
    .mini-table td[contenteditable="true"] {
        cursor: text;
        outline: none;
        transition: 0.2s;
    }

    .mini-table th[contenteditable="true"]:focus,
    .mini-table td[contenteditable="true"]:focus {
        background: #fff;
        box-shadow: inset 0 0 0 2px #1a73e8;
    }

    .mini-table td.editable-empty:empty::before {
        content: "Nhập nội dung...";
        color: #adb5bd;
        font-style: italic;
        font-weight: normal;
    }

    /* Table Grid Selector */
    .table-selector-dropdown {
        min-width: 220px;
        padding: 15px;
        border-radius: 8px;
        border: none;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        z-index: 2000 !important;
    }

    .grid-container {
        display: grid;
        grid-template-columns: repeat(10, 18px);
        grid-gap: 2px;
        margin-bottom: 10px;
        background: #fff;
    }

    .grid-square {
        width: 18px;
        height: 18px;
        border: 1px solid #ccc;
        border-radius: 2px;
        cursor: pointer;
        background: #fdfdfd;
    }

    .grid-square.active {
        background-color: #e8f0fe;
        border-color: #1a73e8;
    }

    .grid-square.highlighted {
        background-color: #c2d7ff;
        border-color: #1a73e8;
    }

    .grid-label {
        font-size: 0.8rem;
        font-weight: 600;
        color: #1a73e8;
        text-align: center;
    }

    /* Spreadsheet Block Styling */
    .spreadsheet-table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
    }

    .spreadsheet-table th {
        background: #f1f3f4;
        color: #5f6368;
        font-size: 0.7rem;
        font-weight: bold;
        text-align: center;
        border: 1px solid #dadce0;
        padding: 2px 5px;
    }

    .spreadsheet-table td {
        border: 1px solid #dadce0;
        padding: 4px 8px;
        font-size: 0.9rem;
        min-height: 28px;
        vertical-align: middle;
        position: relative;
    }

    .spreadsheet-cell-input {
        width: 100%;
        border: none;
        outline: none;
        background: transparent;
        font-family: inherit;
        font-size: inherit;
        text-align: inherit;
    }

    .spreadsheet-cell-input:focus {
        background: #fff;
        box-shadow: inset 0 0 0 2px #1a73e8;
        z-index: 10;
    }

    .spreadsheet-formula-indicator {
        position: absolute;
        top: 0;
        left: 0;
        width: 0;
        height: 0;
        border-style: solid;
        border-width: 6px 6px 0 0;
        border-color: #1a73e8 transparent transparent transparent;
        pointer-events: none;
    }

    .spreadsheet-value {
        word-break: break-all;
    }

    .spreadsheet-row-index {
        background: #f1f3f4 !important;
        font-weight: bold;
        color: #5f6368 !important;
        width: 30px;
        text-align: center;
        font-size: 0.7rem;
    }

    /* Static Text Styling (Seamless Editor Mode) */
    .block-item.type-static-text {
        padding: 2px 0;
        /* Removed border: none !important to allow borderMode classes */
        box-shadow: none !important;
        background: transparent !important;
        margin-bottom: 0;
    }

    .type-static-text .block-actions {
        top: 0;
        right: -35px;
    }

    .type-static-text:hover .block-actions {
        right: -40px;
    }

    .static-text-display {
        font-size: 1.15rem;
        /* ~16px usually, let's keep it close to 14pt */
        color: #3c4043;
        line-height: 1.6;
        white-space: normal;
        /* Changed from pre-wrap to support natural wrapping and fix unwanted breaks on paste */
        outline: none;
        padding: 0;
        min-height: 1.6em;
        border: 1px solid transparent;
        /* Default border */
        border-radius: 4px;
    }

    /* Hierarchical Heading Numbering */
    #editor-content {
        counter-reset: hb1 hb2 hb3 hb4;
    }

    #editor-content h1 {
        counter-increment: hb1;
        counter-reset: hb2 hb3 hb4;
        font-size: 22pt !important;
        margin: 10px 0 !important;
        font-weight: bold;
        color: #000 !important;
    }

    #editor-content h1::before {
        content: counter(hb1) ". ";
    }

    #editor-content h2 {
        counter-increment: hb2;
        counter-reset: hb3 hb4;
        font-size: 18pt !important;
        margin: 10px 0 !important;
        font-weight: bold;
        color: #000 !important;
    }

    #editor-content h2::before {
        content: counter(hb1) "." counter(hb2) " ";
    }

    #editor-content h3 {
        counter-increment: hb3;
        counter-reset: hb4;
        font-size: 16pt !important;
        margin: 10px 0 !important;
        font-weight: bold;
        color: #000 !important;
    }

    #editor-content h3::before {
        content: counter(hb1) "." counter(hb2) "." counter(hb3) " ";
    }

    #editor-content h4 {
        counter-increment: hb4;
        font-size: 16pt !important;
        margin: 10px 0 !important;
        font-weight: bold;
        color: #000 !important;
    }

    #editor-content h4::before {
        content: counter(hb1) "." counter(hb2) "." counter(hb3) "." counter(hb4) " ";
    }

    #editor-content p {
        font-size: 14pt !important;
        margin-bottom: 0.5rem;
        color: #000 !important;
    }

    .static-text-display[contenteditable="true"]:focus {
        background: transparent;
        box-shadow: none;
        border-color: transparent;
        outline: none;
    }

    .static-text-placeholder {
        /* This class is now primarily handled by :empty pseudo-selectors below */
    }

    .static-text-display:empty:before {
        content: "Nhập nội dung văn bản tại đây...";
        color: #9aa0a6;
        font-style: italic;
    }

    /* Support placeholders even when a heading tag is present but empty */
    .static-text-display h1:empty:before {
        content: "Nhập Tiêu đề Cấp 1...";
        color: #9aa0a6;
        font-style: italic;
        font-weight: normal;
    }

    .static-text-display h2:empty:before {
        content: "Nhập Tiêu đề Cấp 2...";
        color: #9aa0a6;
        font-style: italic;
        font-weight: normal;
    }

    .static-text-display h3:empty:before {
        content: "Nhập Tiêu đề Cấp 3...";
        color: #9aa0a6;
        font-style: italic;
        font-weight: normal;
    }

    .static-text-display h4:empty:before {
        content: "Nhập Tiêu đề Cấp 4...";
        color: #9aa0a6;
        font-style: italic;
        font-weight: normal;
    }

    /* For contenteditable, ensure the cursor stays visible */
    [contenteditable]:empty:before {
        display: inline-block;
    }

    /* Insertion Points */
    .insert-divider {
        height: 12px;
        margin: 0;
        position: relative;
        z-index: 10;
        cursor: pointer;
        opacity: 0;
        transition: 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .insert-divider:hover {
        opacity: 1;
    }

    .insert-divider::before {
        content: "";
        position: absolute;
        left: 0;
        right: 0;
        height: 2px;
        background: #1a73e8;
    }

    .insert-btn {
        background: #1a73e8;
        color: white;
        border: none;
        border-radius: 50%;
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        z-index: 11;
        transition: 0.2s;
        transform: scale(0.8);
    }

    .insert-divider:hover .insert-btn {
        transform: scale(1);
    }

    .insert-menu {
        position: absolute;
        background: white;
        border: 1px solid #dadce0;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        padding: 8px;
        z-index: 100;
        display: none;
        flex-direction: column;
        min-width: 150px;
    }

    .insert-menu.show {
        display: flex;
    }

    .insert-menu button {
        text-align: left;
        background: none;
        border: none;
        padding: 6px 12px;
        font-size: 0.85rem;
        border-radius: 4px;
        color: #3c4043;
    }

    .insert-menu button:hover {
        background: #f1f3f4;
    }

    /* Table Selection */
    .selected-cell {
        background-color: rgba(26, 115, 232, 0.1) !important;
        box-shadow: inset 0 0 0 1px rgba(26, 115, 232, 0.5);
    }

    .mini-table td[contenteditable="true"]:focus,
    .mini-table th[contenteditable="true"]:focus {
        outline: 2px solid #1a73e8;
        outline-offset: -2px;
        background: #fff;
        z-index: 5;
        position: relative;
    }

    /* Table Resize & Borders */
    .mini-table {
        position: relative;
        border-collapse: separate;
        border-spacing: 0;
        table-layout: fixed;
    }

    .mini-table th,
    .mini-table td {
        position: relative;
        border: 1px solid #dadce0;
        min-width: 20px;
        min-height: 20px;
        overflow: hidden;
        word-wrap: break-word;
    }

    /* Global Border Classes */
    .border-dashed {
        border-style: dashed !important;
        border-color: #ddd !important;
    }

    .mini-table.border-dashed th,
    .mini-table.border-dashed td {
        border-style: dashed !important;
        border-color: #ddd !important;
    }

    .border-none {
        border-color: transparent !important;
        border-width: 0 !important;
    }

    .mini-table.border-none th,
    .mini-table.border-none td {
        border-color: transparent !important;
    }

    .border-visible {
        border: 1px solid #dadce0 !important;
        border-style: solid !important;
    }

    .static-text-display.border-visible,
    .static-text-display.border-dashed {
        padding: 8px 12px !important;
    }

    @media print {
        @page {
            size: A4 portrait;
            margin: 0;
        }

        body.printing-landscape @page {
            size: A4 landscape;
        }

        .page-a4 {
            box-shadow: none !important;
            margin: 0 !important;
            width: 100% !important;
            padding: 0 !important;
        }

        .no-print {
            display: none !important;
        }
    }

    .resize-h {
        position: absolute;
        right: -5px;
        top: 0;
        bottom: 0;
        width: 10px;
        cursor: col-resize;
        z-index: 100;
        background: transparent;
    }

    .resize-v {
        position: absolute;
        bottom: -5px;
        left: 0;
        right: 0;
        height: 10px;
        cursor: row-resize;
        z-index: 100;
        background: transparent;
    }

    .resize-h:hover,
    .resize-h:active {
        background: rgba(26, 115, 232, 0.3);
        border-right: 2px solid #1a73e8;
    }

    .resize-v:hover,
    .resize-v:active {
        background: rgba(26, 115, 232, 0.3);
        border-bottom: 2px solid #1a73e8;
    }

    /* Document Outline */
    .outline-sidebar {
        position: sticky;
        top: 150px;
        max-height: calc(100vh - 170px);
        overflow-y: auto;
        padding-right: 10px;
        scrollbar-width: thin;
        z-index: 100;
    }

    .outline-item {
        display: block;
        padding: 6px 10px;
        color: #5f6368;
        text-decoration: none;
        border-radius: 4px;
        font-size: 0.85rem;
        margin-bottom: 2px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        transition: 0.2s;
        cursor: pointer;
    }


    /* Sidebar Columns & Layout Custom Widths (Reduced by 30% for cleaner presentation) */
    #outline-col.col-lg-2 {
        width: 175px !important;
        flex: 0 0 175px !important;
        max-width: 175px !important;
    }

    #sidebar-col.col-lg-2,
    #sidebar-col.col-lg-3 {
        width: 266px !important;
        flex: 0 0 266px !important;
        max-width: 266px !important;
    }

    #outline-col.col-lg-1,
    #sidebar-col.col-lg-1 {
        width: 45px !important;
        flex: 0 0 45px !important;
        max-width: 45px !important;
    }

    #canvas-col {
        flex: 1 1 0% !important;
        max-width: none !important;
        width: auto !important;
    }

    /* Right Property Panel */
    #property-panel {
        position: sticky;
        top: 150px;
        max-height: calc(100vh - 170px);
        overflow-y: auto;
        overflow-x: hidden;
        scrollbar-width: thin;
        z-index: 100;
        padding-right: 5px;
    }

    #property-panel::-webkit-scrollbar {
        width: 4px;
    }

    #property-panel::-webkit-scrollbar-track {
        background: transparent;
    }

    #property-panel::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 10px;
    }

    #property-panel::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }

    /* Custom Scrollbar for Sidebar */
    .outline-sidebar::-webkit-scrollbar {
        width: 4px;
    }

    .outline-sidebar::-webkit-scrollbar-track {
        background: #f1f1f1;
    }

    .outline-sidebar::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 10px;
    }

    .outline-sidebar::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }

    .outline-item:hover {
        background-color: #f1f3f4;
        color: #1a73e8;
    }

    .outline-h1 {
        font-weight: bold;
    }

    .outline-h2 {
        padding-left: 20px !important;
    }

    .outline-h3 {
        padding-left: 35px !important;
    }

    .outline-h4 {
        padding-left: 50px !important;
        font-style: italic;
    }

    .outline-h1 {
        margin-left: 0;
        font-weight: 600;
        color: #202124;
    }

    .outline-h2 {
        margin-left: 15px;
    }

    .outline-h3 {
        margin-left: 30px;
        font-size: 0.8rem;
    }

    .outline-empty {
        color: #9aa0a6;
        font-style: italic;
        font-size: 0.85rem;
        padding: 10px;
    }

    /* Clickable insert zone */
    .insert-click-zone {
        position: absolute;
        top: 0;
        bottom: 0;
        left: 0;
        right: 0;
        z-index: 5;
    }

    /* Color Picker Palette */
    .color-swatch {
        width: 22px;
        height: 22px;
        border: 1px solid rgba(0, 0, 0, 0.1);
        border-radius: 2px;
        padding: 0;
        cursor: pointer;
        transition: 0.1s;
    }

    .color-swatch:hover {
        border-color: #000;
        transform: scale(1.1);
        z-index: 10;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }

    .color-swatch.light-color {
        border: 1px solid #dadce0;
    }

    /* Editor Ruler */
    .editor-ruler {
        position: sticky;
        top: 170px;
        /* 57px header + ~113px toolbar/header margins */
        z-index: 990;
        height: 24px;
        background-color: #f8f9fa;
        border: 1px solid #dadce0;
        border-bottom: none;
        border-radius: 4px 4px 0 0;
        display: flex;
        align-items: flex-end;
        overflow: hidden;
    }

    .ruler-scale {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 10px;
        background-image: repeating-linear-gradient(to right, transparent, transparent 9px, #ccc 9px, #ccc 10px);
    }

    .ruler-scale::after {
        content: "";
        position: absolute;
        top: -4px;
        bottom: 0;
        left: 0;
        right: 0;
        background-image: repeating-linear-gradient(to right, transparent, transparent 49px, #888 49px, #888 50px);
    }

    .ruler-marker-left,
    .ruler-marker-right {
        position: absolute;
        top: 0;
        width: 0;
        height: 0;
        border-style: solid;
        z-index: 20;
        cursor: ew-resize;
        border-width: 10px 5px 0 5px;
        border-color: #1a73e8 transparent transparent transparent;
    }

    .ruler-marker-left {
        left: 48px;
        /* default p-5 padding ~ 48px */
        transform: translateX(-50%);
    }

    .ruler-marker-right {
        right: 48px;
        transform: translateX(50%);
    }

    .ruler-margin-left,
    .ruler-margin-right {
        position: absolute;
        top: 0;
        bottom: 0;
        background-color: #e8eaed;
        z-index: 10;
        width: 48px;
    }

    .ruler-margin-left {
        left: 0;
        border-right: 1px solid #aaa;
    }

    .ruler-margin-right {
        right: 0;
        border-left: 1px solid #aaa;
    }

    /* Layout Transitions */
    .transition-all {
        transition: all 0.3s ease-in-out;
    }

    .cursor-pointer {
        cursor: pointer;
    }

    #outline-minimized:hover,
    #sidebar-minimized:hover {
        background-color: #f1f3f4 !important;
    }

    /* Comments Gutter Styling */
    .comment-gutter {
        /* No longer absolute, part of flex flow */
        width: 320px;
        pointer-events: none;
    }

    .comment-item {
        position: absolute;
        width: 100%;
        background: white;
        border: 1px solid #e0e0e0;
        border-radius: 12px;
        padding: 14px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        font-size: 0.88rem;
        transition: all 0.25s ease;
        pointer-events: auto;
        border-left: 5px solid #1a73e8;
    }

    .comment-item:hover,
    .comment-item.active {
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        border-color: #1a73e8;
        transform: scale(1.02);
        z-index: 2000;
    }

    .comment-connector-line {
        position: absolute;
        height: 1px;
        border-top: 1.5px dashed #4285f4;
        width: 40px;
        left: -40px;
        opacity: 0.5;
        pointer-events: none;
        z-index: -1;
        transition: all 0.2s ease;
    }

    .comment-item:hover .comment-connector-line,
    .comment-item.active .comment-connector-line {
        opacity: 0.9;
        border-top-style: solid;
        border-top-color: #1a73e8;
    }

    .comment-avatar {
        width: 32px;
        height: 32px;
        background: #1a73e8;
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 0.8rem;
        flex-shrink: 0;
    }

    .comment-user {
        font-weight: 600;
        color: #202124;
        font-size: 0.9rem;
    }

    .comment-date {
        font-size: 0.75rem;
        color: #70757a;
    }

    .comment-content {
        margin-top: 8px;
        color: #3c4043;
        line-height: 1.5;
    }

    .ebmr-comment-highlight {
        background-color: #fef08a !important;
        color: #1e293b !important;
        border-bottom: 2px solid #eab308 !important;
        cursor: pointer;
        padding: 2px 2px !important;
        border-radius: 2px !important;
        transition: all 0.2s ease !important;
        display: inline !important;
    }

    .ebmr-comment-highlight.active,
    .ebmr-comment-highlight:hover {
        background-color: #fde047 !important;
        box-shadow: 0 0 0 2px rgba(234, 179, 8, 0.3) !important;
    }

    /* Override when comment highlights are hidden */
    .hide-comment-highlights .ebmr-comment-highlight {
        background-color: transparent !important;
        color: inherit !important;
        border-bottom: none !important;
        padding: 0 !important;
        border-radius: 0 !important;
        cursor: text !important;
        box-shadow: none !important;
    }

    /* Note Badge Styling */
    .ebmr-note-badge {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        background-color: #f59e0b !important;
        /* Màu vàng cam */
        color: #ffffff !important;
        border-radius: 4px !important;
        font-size: 10px !important;
        padding: 1px 4px !important;
        margin: 0 2px !important;
        cursor: pointer !important;
        vertical-align: super !important;
        user-select: none !important;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.15) !important;
        transition: background-color 0.2s, transform 0.2s !important;
        line-height: 1 !important;
    }

    .ebmr-note-badge:hover {
        background-color: #d97706 !important;
        transform: scale(1.1) !important;
        text-decoration: none !important;
    }

    @media print {
        .ebmr-note-badge {
            display: inline-flex !important;
            background-color: transparent !important;
            color: #d97706 !important;
            border: 1px dashed #d97706 !important;
            padding: 1px 2px !important;
            font-size: 9px !important;
            box-shadow: none !important;
        }

        .ebmr-note-badge::after {
            content: " [" attr(data-note) "] " !important;
            font-size: 9px !important;
            color: #475569 !important;
            font-weight: normal !important;
        }
    }

    .page-break-divider {
        height: 25px;
        background: #f1f3f4 !important;
        /* Match the main workspace background */
        margin-left: -41px !important;
        /* Pull out to cover page padding */
        margin-right: -41px !important;
        margin-top: 50px !important;
        margin-bottom: 50px !important;
        border-top: 1px solid #ddd;
        border-bottom: 1px solid #ddd;
        box-shadow: inset 0 4px 6px -4px rgba(0, 0, 0, 0.1), inset 0 -4px 6px -4px rgba(0, 0, 0, 0.1);
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .page-break-divider span {
        background-color: #cbd5e1 !important;
        color: white !important;
        font-size: 0.65rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    /* Section Group Highlighting */
    .section-group-wrapper {
        border: 2px solid transparent;
        border-radius: 8px;
        padding: 40px;
        margin: 0 -40px;
        transition: all 0.3s ease;
        position: relative;
    }

    .section-group-wrapper.active {
        border-color: #0ea5e9;
        background-color: rgba(14, 165, 233, 0.02);
        box-shadow: 0 0 15px rgba(14, 165, 233, 0.1);
    }

    .section-group-wrapper.active::before {
        content: "PHÂN ĐOẠN ĐANG CHỌN";
        position: absolute;
        top: -12px;
        right: 20px;
        background: #0ea5e9;
        color: white;
        font-size: 0.6rem;
        font-weight: bold;
        padding: 2px 12px;
        border-radius: 10px;
        z-index: 20;
        text-transform: uppercase;
        letter-spacing: 1px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    /* Variable Badges (Dynamic Fields) */
    .ebmr-field-badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 0.85rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
        border: 2px solid #d97706 !important;
        /* Vàng đậm */
        background-color: #fffbeb !important;
        /* Vàng cực nhạt (amber-50) */
        color: #92400e !important;
        /* Chữ hổ phách đậm */
        box-shadow: 0 2px 4px rgba(217, 119, 6, 0.1);
        user-select: none;
        white-space: nowrap;
        margin: 2px;
        min-width: 80px;
        text-align: center;
        position: relative !important;
    }

    /* Drag handles for variables */
    .ebmr-field-badge .badge-drag-handle {
        position: absolute;
        top: 0;
        bottom: 0;
        width: 6px;
        background-color: rgba(217, 119, 6, 0.3);
        cursor: ew-resize;
        opacity: 0;
        transition: opacity 0.15s ease, background-color 0.15s ease, width 0.15s ease;
        z-index: 10;
    }

    .ebmr-field-badge:hover .badge-drag-handle,
    .ebmr-field-badge.active .badge-drag-handle {
        opacity: 1;
    }

    .ebmr-field-badge .badge-drag-handle:hover {
        background-color: rgba(217, 119, 6, 0.85);
        width: 8px;
    }

    .ebmr-field-badge .badge-left-handle {
        left: 0;
        border-top-left-radius: 4px;
        border-bottom-left-radius: 4px;
    }

    .ebmr-field-badge .badge-right-handle {
        right: 0;
        border-top-right-radius: 4px;
        border-bottom-right-radius: 4px;
    }

    /* Make variable fill the whole table cell */
    td .ebmr-field-badge {
        display: flex !important;
        width: 100% !important;
        min-height: 38px;
        margin: 0 !important;
        border-radius: 0 !important;
        border-width: 0 0 0 4px !important;
        /* Chỉ để lại viền trái làm điểm nhấn */
        justify-content: center;
        align-items: center;
        background-color: #fff9db !important;
        /* Màu vàng nhận diện vùng nhập liệu */
        white-space: normal;
        /* Cho phép xuống dòng nếu ô hẹp */
        text-align: center;
        padding: 5px !important;
    }

    /* Định dạng nổi bật cho Biến số kiểu Tính toán công thức (Auto Formula) */
    .formula-result {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 0.85rem;
        font-weight: bold;
        border: 2px dashed #4f46e5 !important;
        /* Đường viền đứt màu Indigo biểu thị tự động tính */
        background-color: #f5f3ff !important;
        /* Màu nền tím cực nhạt */
        color: #4c1d95 !important;
        /* Màu chữ tím đậm */
        box-shadow: 0 2px 4px rgba(79, 70, 229, 0.08);
        text-align: center;
        min-width: 80px;
        margin: 2px;
    }

    /* Khi Công thức tự động nằm trong ô Bảng */
    td .formula-result {
        display: flex !important;
        width: 100% !important;
        min-height: 38px;
        margin: 0 !important;
        border-radius: 0 !important;
        border-width: 0 0 0 4px !important;
        /* Chỉ để viền bên trái làm điểm nhấn */
        border-style: solid !important;
        border-color: #4f46e5 !important;
        /* Màu viền trái tím */
        justify-content: center;
        align-items: center;
        background-color: #f5f3ff !important;
        /* Màu nền tím */
        white-space: normal;
        text-align: center;
        padding: 5px !important;
    }

    /* Thiết kế riêng cho nhãn Công thức trong màn hình Thiết kế (Designer Mode) */
    .ebmr-field-badge.formula-preview {
        border: 2px solid #4f46e5 !important;
        /* Viền tím trơn */
        background-color: #f5f3ff !important;
        /* Nền tím nhạt */
        color: #4c1d95 !important;
        /* Chữ tím */
        box-shadow: 0 2px 4px rgba(79, 70, 229, 0.1);
    }

    .ebmr-field-badge.formula-preview:hover {
        background-color: #ede9fe !important;
    }

    .ebmr-field-badge.formula-preview.active {
        background-color: #ddd6fe !important;
        box-shadow: inset 0 0 0 2px #4f46e5;
    }

    td .ebmr-field-badge.formula-preview {
        background-color: #f5f3ff !important;
        border-color: #4f46e5 !important;
    }

    td:has(.ebmr-field-badge) {
        padding: 0 !important;
        vertical-align: stretch;
    }

    .ebmr-field-badge:hover {
        background-color: #fef3c7 !important;
        /* amber-100 */
    }

    .ebmr-field-badge.active {
        background-color: #fde68a !important;
        /* amber-200 */
        box-shadow: inset 0 0 0 2px #d97706;
    }

    .ebmr-field-badge i {
        margin-right: 5px;
        font-size: 0.9em;
    }

    .execution-delete-cell button {
        opacity: 0.3;
        transition: all 0.2s;
        font-size: 1.1rem;
    }

    .execution-delete-cell:hover button {
        opacity: 1;
        transform: scale(1.2);
    }

    /* Execution Mode Overrides */
    .execution-mode-active .editor-toolbar>div:nth-child(2),
    .execution-mode-active #editor-ruler,
    .execution-mode-active #sidebar-col,
    .execution-mode-active .insert-divider,
    .execution-mode-active #drop-hint,
    .execution-mode-active .block-actions {
        display: none !important;
    }

    .execution-mode-active .block-item {
        border-color: transparent !important;
        padding: 0 !important;
        margin-bottom: 0 !important;
    }

    .execution-mode-active .block-item:hover {
        background: transparent !important;
    }

    .test-mode-badge {
        position: fixed;
        bottom: 30px;
        right: 30px;
        background: #28a745;
        color: white;
        padding: 12px 24px;
        border-radius: 50px;
        font-weight: bold;
        box-shadow: 0 4px 20px rgba(40, 167, 69, 0.4);
        z-index: 9999;
        display: none;
        pointer-events: none;
        animation: pulseTest 2s infinite;
        border: 2px solid rgba(255, 255, 255, 0.2);
    }

    @keyframes pulseTest {
        0% {
            transform: scale(1);
            box-shadow: 0 4px 20px rgba(40, 167, 69, 0.4);
        }

        50% {
            transform: scale(1.05);
            box-shadow: 0 4px 30px rgba(40, 167, 69, 0.6);
        }

        100% {
            transform: scale(1);
            box-shadow: 0 4px 20px rgba(40, 167, 69, 0.4);
        }
    }

    .execution-mode-active .test-mode-badge {
        display: block;
    }

    .execution-input-test:empty::before {
        content: attr(data-placeholder);
        color: #6c757d;
        pointer-events: none;
        font-style: italic;
    }

    .execution-mode-active .page-a4 {
        box-shadow: 0 0 50px rgba(0, 0, 0, 0.05) !important;
        border: 1px solid #eee;
    }

    .section-group-wrapper {
        padding: 20px;
        margin-bottom: 30px;
        border: 2px solid transparent;
        border-radius: 12px;
        transition: all 0.3s ease;
        position: relative;
        background-color: transparent;
    }

    .section-group-wrapper:hover {
        background-color: rgba(248, 250, 252, 0.5);
        border-color: #e2e8f0;
    }

    .section-group-wrapper.active {
        border-color: #3b82f6;
        background-color: #f8fafc;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.08);
    }

    /* Đảm bảo divider giữa các section cũng đẹp */
    .page-break-divider {
        position: relative;
        z-index: 5;
    }

    .section-group-wrapper:hover {
        border-color: rgba(13, 110, 253, 0.2);
        background-color: rgba(13, 110, 253, 0.01);
    }

    .section-group-wrapper.active {
        border-color: #0d6efd;
        background-color: rgba(13, 110, 253, 0.03);
        box-shadow: 0 4px 20px rgba(13, 110, 253, 0.1);
    }

    .execution-meta {
        font-size: 8px;
        color: #999;
        display: block;
        line-height: 1.2;
        margin-top: 2px;
        font-weight: normal;
        font-style: italic;
        text-align: right;
        pointer-events: none;
        white-space: nowrap;
    }

    /* Table Resizer Drag Handles */
    .col-resizer {
        position: absolute;
        top: 0;
        bottom: 0;
        right: -3px;
        width: 6px;
        cursor: col-resize;
        z-index: 100;
        background-color: transparent;
        transition: background-color 0.2s;
    }

    .col-resizer:hover,
    .col-resizer:active {
        background-color: #007bff;
    }

    .row-resizer {
        position: absolute;
        bottom: -3px;
        left: 0;
        right: 0;
        height: 6px;
        cursor: row-resize;
        z-index: 100;
        background-color: transparent;
        transition: background-color 0.2s;
    }

    .row-resizer:hover,
    .row-resizer:active {
        background-color: #007bff;
    }

    .execution-badge.time {
        color: #17a2b8;
        background-color: #e0f4f7;
    }

    .execution-badge.executor {
        color: #6610f2;
        background-color: #f0e6ff;
    }

    .execution-badge.checker {
        color: #fd7e14;
        background-color: #fff0e6;
    }

    /* =====================================================
       CRITERIA DATA-BINDING STYLES
       ===================================================== */

    /* Badge hiển thị chữ tiêu chuẩn (chế độ chỉ xem) */
    span.criteria-display {
        display: inline-block;
        background: transparent !important;
        color: inherit !important;
        border: none !important;
        border-radius: 0 !important;
        padding: 0 !important;
        font-size: 100% !important;
        font-weight: normal !important;
        cursor: default;
        user-select: text;
        transition: none !important;
    }

    span.criteria-display:hover {
        background: transparent !important;
        border-color: transparent !important;
    }

    /* Ô nhập kết quả kiểm nghiệm ở chế độ thiết kế */
    input.result-input.criteria-design-mode {
        background: #fff8e1 !important;
        border: 1.5px dashed #ffc107 !important;
        color: #856404 !important;
        cursor: not-allowed !important;
        opacity: 0.8;
    }

    /* Ô nhập kết quả kiểm nghiệm ở chế độ chạy thử – chưa nhập */
    input.result-input.criteria-exec-mode {
        background: #fff !important;
        border: 1.5px solid #17a2b8 !important;
        color: #000 !important;
        cursor: text !important;
        transition: background 0.2s, border-color 0.2s, color 0.2s;
    }

    input.result-input.criteria-exec-mode:focus {
        outline: none !important;
        box-shadow: 0 0 0 2px rgba(23, 162, 184, 0.25) !important;
    }

    /* Kết quả ĐẠT */
    input.result-input.criteria-pass {
        background: #d4edda !important;
        border-color: #28a745 !important;
        color: #155724 !important;
        font-weight: 700;
    }

    /* Kết quả KHÔNG ĐẠT */
    input.result-input.criteria-fail {
        background: #f8d7da !important;
        border-color: #dc3545 !important;
        color: #721c24 !important;
        font-weight: 700;
        animation: criteria-fail-shake 0.3s ease;
    }

    @keyframes criteria-fail-shake {

        0%,
        100% {
            transform: translateX(0);
        }

        25% {
            transform: translateX(-3px);
        }

        75% {
            transform: translateX(3px);
        }
    }

    @media print {
        span.criteria-display {
            background: transparent !important;
            border: none !important;
            color: inherit !important;
            font-weight: normal;
            padding: 0;
        }

        input.result-input {
            border: none !important;
            border-bottom: 1px solid #000 !important;
            border-radius: 0 !important;
            box-shadow: none !important;
            background: transparent !important;
            color: #000 !important;
        }

        .ebmr-property-badge {
            background-color: transparent !important;
            color: inherit !important;
            border: none !important;
            padding: 0 !important;
            margin: 0 !important;
            border-radius: 0 !important;
            font-family: inherit !important;
            font-weight: inherit !important;
            cursor: text !important;
            display: inline !important;
            text-decoration: none !important;
            box-shadow: none !important;
        }
    }

    /* Criteria Sidebar Panel (Drawer style) */
    .criteria-sidebar {
        position: fixed;
        top: 57px;
        /* Below AdminLTE header height */
        right: 0;
        width: 300px;
        height: calc(100vh - 57px);
        z-index: 1045;
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        display: flex;
        flex-direction: column;
        background: #ffffff;
        box-shadow: -10px 0 25px rgba(0, 0, 0, 0.15) !important;
        border-left: 1px solid #e2e8f0;
    }

    .criteria-sidebar.d-none {
        transform: translateX(100%);
        display: none !important;
    }

    /* Properties Sidebar Panel (Drawer style) */
    .properties-sidebar {
        position: fixed;
        top: 57px;
        /* Below AdminLTE header height */
        right: 0;
        width: 300px;
        height: calc(100vh - 57px);
        z-index: 1045;
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        display: flex;
        flex-direction: column;
        background: #ffffff;
        box-shadow: -10px 0 25px rgba(0, 0, 0, 0.15) !important;
        border-left: 1px solid #e2e8f0;
    }

    .properties-sidebar.d-none {
        transform: translateX(100%);
        display: none !important;
    }

    /* Document Properties Badge in editor - unstyled by default (inherits parent styling) */
    .ebmr-property-badge {
        background-color: transparent !important;
        color: inherit !important;
        border: none !important;
        padding: 0 !important;
        margin: 0 !important;
        border-radius: 0 !important;
        font-family: inherit !important;
        font-weight: inherit !important;
        cursor: text !important;
        display: inline !important;
        transition: none !important;
        box-shadow: none !important;
        text-decoration: none !important;
    }

    /* Highlight style: active only when show-property-highlight is enabled on mainContent and we are NOT in execution/preview mode */
    #mainContent.show-property-highlight:not(.execution-mode-active) .ebmr-property-badge,
    .show-property-highlight:not(.execution-mode-active) .ebmr-property-badge {
        background-color: #f0fdf4 !important;
        color: #166534 !important;
        border-bottom: 2px dashed #15803d !important;
        padding: 2px 6px !important;
        margin: 0 2px !important;
        border-radius: 4px !important;
        font-family: inherit !important;
        font-weight: 600 !important;
        cursor: text !important;
        display: inline-block !important;
        transition: all 0.2s ease !important;
    }

    #mainContent.show-property-highlight:not(.execution-mode-active) .ebmr-property-badge:hover,
    .show-property-highlight:not(.execution-mode-active) .ebmr-property-badge:hover {
        background-color: #dcfce7 !important;
    }

    #mainContent.show-property-highlight:not(.execution-mode-active) .ebmr-property-badge:focus,
    .show-property-highlight:not(.execution-mode-active) .ebmr-property-badge:focus {
        outline: 2px solid #16a34a !important;
        background-color: #ffffff !important;
    }

    /* Draggable Pills Styling */
    .criteria-card {
        transition: all 0.2s ease;
        border-radius: 8px;
        border: 1px solid #e2e8f0 !important;
        background-color: #f8fafc;
    }

    .criteria-card:hover {
        border-color: #cbd5e1 !important;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
    }

    .draggable-pill {
        padding: 5px 10px;
        border-radius: 6px;
        cursor: grab;
        display: flex;
        align-items: center;
        transition: all 0.15s ease;
        user-select: none;
        background-color: #ffffff;
        border: 1px solid #cbd5e1 !important;
        font-size: 0.75rem;
    }

    .draggable-pill:hover {
        background-color: #f1f5f9;
        border-color: #10b981 !important;
        /* Green hover for spec-binding theme */
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
    }

    .draggable-pill:active {
        cursor: grabbing;
        background-color: #ecfdf5;
        border-color: #059669 !important;
    }

    /* Highlight cell on drag over */
    .criteria-drag-over {
        background-color: rgba(16, 185, 129, 0.15) !important;
        border: 2px dashed #10b981 !important;
        box-shadow: inset 0 0 0 2px rgba(16, 185, 129, 0.2);
    }

    /* Lightbox Carousel Premium CSS */
    .lightbox-carousel-modal {
        max-width: 1150px;
        width: 95%;
    }

    .lightbox-carousel-modal .modal-content {
        background: rgba(255, 255, 255, 0.98) !important;
        backdrop-filter: blur(20px);
        border: 1px solid rgba(0, 0, 0, 0.08);
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15) !important;
    }

    .lightbox-carousel-header {
        background: rgba(248, 250, 252, 0.85) !important;
        border-bottom: 1px solid rgba(0, 0, 0, 0.06);
    }

    .carousel-item-premium {
        height: calc(100% - 150px);
        text-align: center;
        background-color: transparent;
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .carousel-item-premium img {
        max-height: 100%;
        max-width: 100%;
        object-fit: contain;
        border-radius: 8px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12);
        transition: transform 0.3s ease;
    }

    .carousel-caption-premium {
        position: absolute;
        bottom: 15px;
        left: 50%;
        transform: translateX(-50%);
        background: rgba(15, 23, 42, 0.85) !important;
        backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, 0.15);
        color: #fff;
        padding: 12px 24px;
        text-align: left;
        width: 90%;
        max-width: 800px;
        border-radius: 14px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
    }

    .carousel-caption-premium h6 {
        font-size: 0.95rem;
        font-weight: 700;
        margin-bottom: 4px;
        color: #f8fafc;
    }

    .carousel-caption-premium p {
        font-size: 0.8rem;
        color: #cbd5e1;
        margin-bottom: 0;
        line-height: 1.4;
    }

    .carousel-control-prev-premium,
    .carousel-control-next-premium {
        width: 48px;
        height: 48px;
        background: rgba(15, 23, 42, 0.06);
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        position: absolute;
        top: calc(50% - 75px);
        transform: translateY(-50%);
        transition: all 0.2s ease;
        color: #1e293b;
        opacity: 0.8;
    }

    .carousel-control-prev-premium:hover,
    .carousel-control-next-premium:hover {
        background: rgba(15, 23, 42, 0.12);
        opacity: 1;
        color: #0f172a;
        text-decoration: none;
    }

    .carousel-control-prev-premium {
        left: 20px;
    }

    .carousel-control-next-premium {
        right: 20px;
    }

    .lightbox-carousel-modal .carousel-indicators li {
        background-color: #94a3b8 !important;
    }

    .lightbox-carousel-modal .carousel-indicators li.active {
        background-color: #0f172a !important;
    }

    .lightbox-toolbar {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .lightbox-btn {
        background: rgba(15, 23, 42, 0.06);
        border: 1px solid rgba(15, 23, 42, 0.08);
        color: #334155;
        border-radius: 8px;
        padding: 6px 12px;
        font-size: 0.85rem;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .lightbox-btn:hover {
        background: rgba(15, 23, 42, 0.12);
        color: #0f172a;
        text-decoration: none;
    }

    /* Style for criteria display that has images */
    span.criteria-display[data-has-images="true"] {
        cursor: pointer !important;
        position: relative;
        text-decoration: underline dotted #ffc107 2px !important;
    }

    /* Định dạng liên kết tài liệu mạng PDF */
    .ebmr-doc-link {
        color: #1a73e8 !important;
        text-decoration: underline dashed #1a73e8 1.5px !important;
        text-underline-offset: 3px;
        font-weight: 600 !important;
        cursor: pointer !important;
        transition: all 0.2s ease-in-out;
    }

    .ebmr-doc-link:hover {
        color: #1557b0 !important;
        text-decoration: underline solid #1557b0 1.5px !important;
        background-color: rgba(26, 115, 232, 0.08);
        border-radius: 4px;
        padding: 0 4px;
        margin: 0 -4px;
    }

    /* ============================================================
       SCALE READER — Nút Đọc Cân & Modal Kết Nối
       ============================================================ */

    /* Nút ⚖️ đọc cân inline cạnh ô biến số */
    .btn-read-scale {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 22px;
        height: 22px;
        border: none;
        border-radius: 50%;
        background: linear-gradient(135deg, #dc2626, #b91c1c);
        color: white;
        font-size: 10px;
        cursor: pointer;
        margin-left: 4px;
        vertical-align: middle;
        transition: all 0.2s ease;
        box-shadow: 0 1px 4px rgba(220, 38, 38, 0.3);
        flex-shrink: 0;
        position: relative;
    }

    .btn-read-scale:hover {
        background: linear-gradient(135deg, #b91c1c, #991b1b);
        box-shadow: 0 2px 8px rgba(220, 38, 38, 0.5);
        transform: scale(1.1);
    }

    /* Trạng thái đang đọc (animation xoay) */
    .btn-read-scale.reading {
        background: linear-gradient(135deg, #ef4444, #dc2626);
        animation: scale-reading-pulse-red 0.8s ease-in-out infinite;
    }

    .btn-read-scale.reading i {
        animation: spin 1s linear infinite;
    }

    @keyframes scale-reading-pulse-red {

        0%,
        100% {
            box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.6);
        }

        50% {
            box-shadow: 0 0 0 6px rgba(239, 68, 68, 0);
        }
    }

    @keyframes spin {
        from {
            transform: rotate(0deg);
        }

        to {
            transform: rotate(360deg);
        }
    }

    /* Chấm tròn trạng thái kết nối */
    .scale-status-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        display: inline-block;
        flex-shrink: 0;
    }

    .scale-status-dot.connected {
        background: #16a34a;
        box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.25);
        animation: scale-blink 2s ease-in-out infinite;
    }

    .scale-status-dot.disconnected {
        background: #dc2626;
    }

    @keyframes scale-blink {

        0%,
        100% {
            opacity: 1;
        }

        50% {
            opacity: 0.5;
        }
    }

    /* Hiển thị giá trị live từ cân */
    .scale-live-value {
        display: block;
        font-size: 2.2rem;
        font-weight: 700;
        font-family: 'Courier New', monospace;
        text-align: center;
        padding: 12px 0;
        letter-spacing: 0.05em;
        transition: color 0.3s ease;
        color: #dc2626;
    }

    .scale-live-value.stable {
        color: #dc2626;
    }

    .scale-live-value.unstable {
        color: #dc2626;
        animation: scale-live-pulse-red 0.5s ease-in-out infinite;
    }

    @keyframes scale-live-pulse-red {

        0%,
        100% {
            opacity: 1;
        }

        50% {
            opacity: 0.4;
        }
    }

    /* Card chọn hãng cân */
    .scale-brand-card {
        border: 2px solid #e5e7eb;
        border-radius: 10px;
        padding: 12px 8px;
        text-align: center;
        cursor: pointer;
        transition: all 0.2s ease;
        background: #fafafa;
    }

    .scale-brand-card:hover {
        border-color: #16a34a;
        background: #f0fdf4;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(22, 163, 74, 0.15);
    }

    .scale-brand-card.selected {
        border-color: #16a34a;
        background: #dcfce7;
    }

    /* Vùng log raw data */
    #scale-raw-log {
        background: #0f172a;
        color: #94a3b8;
        border-radius: 6px;
        padding: 8px 10px;
        height: 120px;
        overflow-y: auto;
        font-family: 'Courier New', monospace;
        font-size: 0.72rem;
        scrollbar-width: thin;
    }

    /* Hiển thị nút đọc cân trong print thì ẩn */
    @media print {
        .btn-read-scale {
            display: none !important;
        }
    }

    /* Popover đọc cân inline */
    .scale-reader-popover {
        position: absolute;
        z-index: 1050;
        background: white;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        padding: 8px 12px;
        width: 190px;
        display: flex;
        flex-direction: column;
        gap: 6px;
        font-family: inherit;
        pointer-events: auto;
    }

    .scale-reader-popover::after {
        content: "";
        position: absolute;
        bottom: -6px;
        left: 50%;
        transform: translateX(-50%);
        border-width: 6px 6px 0;
        border-style: solid;
        border-color: white transparent;
        display: block;
        width: 0;
    }

    .scale-reader-popover-live {
        font-family: 'Courier New', monospace;
        font-size: 1.3rem;
        font-weight: bold;
        text-align: center;
        padding: 4px;
        border-radius: 4px;
        background: #f1f5f9;
        color: #475569;
        border: 1px solid #e2e8f0;
    }

    .scale-reader-popover-live.stable {
        color: #16a34a;
        background: #dcfce7;
        border-color: #bbf7d0;
    }

    .scale-reader-popover-live.unstable {
        color: #d97706;
        background: #fef3c7;
        border-color: #fde68a;
    }

    .scale-reader-popover-buttons {
        display: flex;
        gap: 4px;
    }

    .scale-reader-popover-btn {
        flex: 1;
        padding: 4px 6px;
        font-size: 0.72rem;
        font-weight: bold;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 4px;
        transition: background 0.15s ease;
    }

    .scale-reader-popover-btn-primary {
        background: #16a34a;
        color: white;
    }

    .scale-reader-popover-btn-primary:hover {
        background: #15803d;
    }

    .scale-reader-popover-btn-secondary {
        background: #e2e8f0;
        color: #475569;
    }

    .scale-reader-popover-btn-secondary:hover {
        background: #cbd5e1;
    }

    /* Floating Status Pill for Scale */
    .scale-floating-status {
        position: fixed;
        bottom: 20px;
        right: 20px;
        z-index: 1040;
        background: rgba(15, 23, 42, 0.85);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        color: white;
        padding: 8px 16px;
        border-radius: 30px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        display: flex;
        align-items: center;
        gap: 8px;
        font-family: 'Courier New', monospace;
        font-weight: bold;
        font-size: 0.85rem;
        cursor: pointer;
        transition: all 0.3s ease;
        border: 1px solid rgba(255, 255, 255, 0.15);
    }

    .scale-floating-status:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
        background: rgba(15, 23, 42, 0.95);
        border-color: rgba(22, 163, 74, 0.5);
    }

    .scale-status-dot.unstable-dot {
        background: #f59e0b;
        box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.25);
        animation: scale-blink 1s ease-in-out infinite;
    }

    /* ============================================================
     SPLIT WORKSPACE LAYOUT STYLES
     ============================================================ */
    body.workspace-split-active {
        overflow: hidden !important;
    }

    body.workspace-split-active #editor-ruler {
        display: none !important;
    }

    body.workspace-split-active #mainContent {
        height: 100vh !important;
        max-height: 100vh !important;
        overflow: hidden !important;
        display: flex;
        flex-direction: column;
    }

    body.workspace-split-active #mainContent>.container-fluid {
        flex-grow: 1;
        height: calc(100vh - 60px - 50px) !important;
        /* Subtract topNAV & toolbar heights */
        overflow: hidden !important;
        padding-top: 0 !important;
        padding-bottom: 0 !important;
    }

    body.workspace-split-active #mainContent>.container-fluid>.row {
        height: 100% !important;
        overflow: hidden !important;
    }

    body.workspace-split-active #canvas-col {
        height: 100% !important;
        display: flex !important;
        flex-direction: column !important;
        overflow: hidden !important;
    }

    body.workspace-split-active #outline-col,
    body.workspace-split-active #sidebar-col {
        height: 100% !important;
        overflow-y: auto !important;
    }

    .split-workspace-container {
        display: flex;
        flex-direction: column;
        height: 100%;
        flex-grow: 1;
        overflow: hidden;
        border: 2px solid #cbd5e1;
        border-radius: 8px;
        background: #f1f3f4;
    }

    .split-pane {
        height: 50%;
        overflow-y: auto;
        position: relative;
        padding: 15px;
        background: #f1f3f4;
        transition: height 0.3s ease;
    }

    .split-pane:first-child {
        border-bottom: 3px solid #cbd5e1;
    }

    .split-pane-header {
        position: sticky;
        top: 0;
        left: 0;
        right: 0;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(4px);
        -webkit-backdrop-filter: blur(4px);
        z-index: 1000;
        padding: 8px 16px;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 15px;
        border-radius: 6px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    }

    /* Hide interactive elements in preview workspace */
    #preview-workspace .block-actions,
    #preview-workspace .insert-divider,
    #preview-workspace .col-actions,
    #preview-workspace .row-actions,
    #preview-workspace .drag-handle,
    #preview-workspace .designer-loop-group-wrapper .position-absolute {
        display: none !important;
    }

    #preview-workspace .page-a4 {
        margin-top: 0 !important;
        pointer-events: none;
        user-select: none;
    }

    #preview-workspace .designer-loop-group-wrapper {
        border: 1px solid #cbd5e1 !important;
        background-color: #fafafa !important;
        padding: 10px !important;
    }

    /* Highlight active search container */
    .has-search-match {
        outline: 3px solid #f59e0b !important;
        outline-offset: -3px;
    }

    .has-search-match .cell-wrapper {
        background-color: transparent !important;
    }

    /* CSS Custom Highlight API — không modify DOM */
    ::highlight(sr-all) {
        background-color: #fde68a;
        color: #000;
    }

    ::highlight(sr-current) {
        background-color: #f97316;
        color: #fff;
        text-decoration: underline;
    }
</style>
