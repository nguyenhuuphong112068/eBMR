<script>
    window.isReadOnly = @json($isReadOnly ?? false);
    window.isExecutionMode = @json($isExecutionMode ?? false);
    window.executionValues = @json($executionValues ?? (object)[]);

    let items = @json($template->schema->fields ?? []);
    let fieldsConfigInit = @json($template->schema->fieldsConfig ?? (object)[]);
    
    // Ensure fieldsConfig is strictly an Object so JSON.stringify doesn't drop assigned properties
    let fieldsConfig = (!fieldsConfigInit || Array.isArray(fieldsConfigInit)) ? {} : fieldsConfigInit;
    
    let currentTemplateId = {{ $template->id ?? 'null' }};
    let historyEnabled = {{ $template->log_history ?? 0 }} == 1;
    let selectedId = null;
    let selectedFieldId = null;

    if (items.length === 0) {
        // Generate Default BMR Header if document is new
        items.push(generateDefaultBmrHeader());
    }

    if (items.length > 0) {
        // Wait for DOM
        document.addEventListener('DOMContentLoaded', () => {
            const nameField = document.getElementById('templateName');
            if (nameField) nameField.value = "{{ $template->name ?? '' }}";
            const hint = document.getElementById('drop-hint');
            if (hint) hint.classList.add('d-none');
            renderBlocks();
        });
    }

    function generateDefaultBmrHeader() {
        const id = 'blk_header_' + Date.now();
        const t = {
            id: "{{ $template->id ?? '' }}",
            code: "{{ $template->document_code ?? '' }}",
            edition: "{{ $template->edition ?? '' }}",
            name: "{{ $template->name ?? '' }}",
            dosage: "{{ $template->dosage_form ?? '' }}",
            batch_size: "{{ $template->batch_size ?? '' }}",
            effective_date: "{{ $template->effective_date ?? '' }}"
        };

        // Standard 6-row, 4-column structure (as per Stella BMR standard)
        // Col 1 & 2 combined for some rows, Col 3 & 4 for others.
        // But for simplicity, we use 4 columns and use colspan.
        
        let columns = [
            { label: 'C1', type: 'text', width: '25%' },
            { label: 'C2', type: 'text', width: '25%' },
            { label: 'C3', type: 'text', width: '25%' },
            { label: 'C4', type: 'text', width: '25%' }
        ];

        let data = [
            // Row 1: Logo and Main Title
            [
                { content: '<img src="/img/stella-pharm.jpg" style="max-height: 40px;">', rs: 1, cs: 1 },
                { content: '<div style="font-size: 1.1rem; font-weight: bold; text-align: center;">BATCH MANUFACTURING RECORD/ HỒ SƠ SẢN XUẤT GỐC</div>', rs: 1, cs: 3 },
                { content: '', hidden: true },
                { content: '', hidden: true }
            ],
            // Row 2: Product & Page No (User said no page no, but we keep the row for layout)
            [
                { content: '<span style="font-size: 0.85rem; font-style: italic;">Product/Sản phẩm</span>', rs: 1, cs: 1 },
                { content: '<strong style="font-size: 1rem;">' + t.name + '</strong>', rs: 1, cs: 1 },
                { content: '<span style="font-size: 0.85rem; font-style: italic;">Page No./Số trang</span>', rs: 1, cs: 1 },
                { content: '<strong>-</strong>', rs: 1, cs: 1 }
            ],
            // Row 3: BMR No & Dosage
            [
                { content: '<span style="font-size: 0.85rem; font-style: italic;">BMR No./Số BMR</span>', rs: 1, cs: 1 },
                { content: '<strong>' + t.code + '</strong>', rs: 1, cs: 1 },
                { content: '<span style="font-size: 0.85rem; font-style: italic;">Dosage unit / Dạng bào chế</span>', rs: 1, cs: 1 },
                { content: '<strong>' + t.dosage + '</strong>', rs: 1, cs: 1 }
            ],
             // Row 4: Version & Grade
             [
                { content: '<span style="font-size: 0.85rem; font-style: italic;">Version No./Số ấn bản</span>', rs: 1, cs: 1 },
                { content: '<strong style="color: red;">' + t.edition + '</strong>', rs: 1, cs: 1 },
                { content: '<span style="font-size: 0.85rem; font-style: italic;">Grade/Phân loại</span>', rs: 1, cs: 1 },
                { content: '<strong>NA</strong>', rs: 1, cs: 1 }
            ],
            // Row 5: Supersedes & Batch Size
            [
                { content: '<span style="font-size: 0.85rem; font-style: italic;">Supersedes/Ấn bản thay thế</span>', rs: 1, cs: 1 },
                { content: '<strong>NA</strong>', rs: 1, cs: 1 },
                { content: '<span style="font-size: 0.85rem; font-style: italic;">Batch size/Cỡ lô</span>', rs: 1, cs: 1 },
                { content: '<strong>' + t.batch_size + '</strong>', rs: 1, cs: 1 }
            ],
            // Row 6: Effective Date & Batch No
            [
                { content: '<span style="font-size: 0.85rem; font-style: italic;">Effective date/Ngày hiệu lực</span>', rs: 1, cs: 1 },
                { content: '<strong>' + t.effective_date + '</strong>', rs: 1, cs: 1 },
                { content: '<span style="font-size: 0.85rem; font-style: italic;">Batch No./Số lô</span>', rs: 1, cs: 1 },
                { content: '<strong>:</strong>', rs: 1, cs: 1 }
            ]
        ];

        return {
            id: id,
            type: 'table',
            label: 'BMR Header',
            rows: 6,
            cols: 4,
            columns: columns,
            data: data,
            rowHeights: new Array(6).fill('auto'),
            borderMode: 'visible',
            hideHeader: true,
            locked: true,
            isBmrHeader: true
        };
    }

    // Undo/Redo History
    let undoStack = [];
    let redoStack = [];
    const MAX_HISTORY = 10;
    let debounceTimer = null;

    function saveState() {
        const currentState = JSON.stringify(items);
        // Don't save if it's the same as the last snapshot
        if (undoStack.length > 0 && undoStack[undoStack.length - 1] === currentState) return;

        undoStack.push(currentState);
        if (undoStack.length > MAX_HISTORY) {
            undoStack.shift(); // Remove oldest
        }
        redoStack = []; // Clear redo on new action
    }

    function saveStateDebounced() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(saveState, 500);
    }

    function undo() {
        if (undoStack.length <= 1) {
            if (undoStack.length === 1 && items.length > 0) {
                // If we have one state left, it might be the initial non-empty state.
            } else {
                return;
            }
        }

        // Save current state to redo stack before moving back
        redoStack.push(JSON.stringify(items));
        if (redoStack.length > MAX_HISTORY) redoStack.shift();

        // Pop the "current" saved state
        undoStack.pop();
        // Get the previous one
        const prevState = undoStack[undoStack.length - 1];
        if (prevState) {
            items = JSON.parse(prevState);
            renderBlocks();
            if (selectedId) selectItem(selectedId, false);
        } else {
            items = [];
            renderBlocks();
        }
    }

    function redo() {
        if (redoStack.length === 0) return;

        const nextState = redoStack.pop();
        undoStack.push(nextState);
        if (undoStack.length > MAX_HISTORY) undoStack.shift();

        items = JSON.parse(nextState);
        renderBlocks();
        if (selectedId) selectItem(selectedId, false);
    }
</script>
