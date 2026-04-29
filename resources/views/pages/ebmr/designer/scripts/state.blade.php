<script>
    window.isReadOnly = @json($isReadOnly ?? false);
    window.isExecutionMode = @json($isExecutionMode ?? false);
    window.activeSectionId = @json($activeSectionId ?? null);
    window.isViewAllMode = !window.activeSectionId;
    window.executionValues = @json($executionValues ?? (object) []);

    let items = @json($template->schema->fields ?? []);

    let fieldsConfigInit = @json($template->schema->fieldsConfig ?? (object) []);
    let pageOrientation = @json($template->schema->pageOrientation ?? 'portrait');

    // Ensure fieldsConfig is strictly an Object so JSON.stringify doesn't drop assigned properties
    let fieldsConfig = (!fieldsConfigInit || Array.isArray(fieldsConfigInit)) ? {} : fieldsConfigInit;

    let currentTemplateId = {{ $template->id ?? 'null' }};
    let historyEnabled = {{ $template->log_history ?? 0 }} == 1;
    let selectedId = null;
    let selectedFieldId = null;
    let selectedFieldIds = [];
    let cellClipboard = null;
    window.deletedBlockIds = [];

    window.initializeDefaultTemplate = function() {
        const type = "{{ $template->type ?? 'BMR' }}";
        const catId = "{{ $template->caterogy_id ?? 0 }}";

        // Push the Section Header block
        items.push({
            id: 'blk_sec_header_' + Date.now(),
            type: 'section',
            label: 'THÔNG TIN CHUNG SẢN PHẨM',
            section_id: catId,
            locked: true
        });

        if (type === 'GF') {
            items.push(generateDefaultGfHeader());
        } else {
            items.push(generateDefaultBmrHeader());
        }
    };

    if (items.length === 0) {
        initializeDefaultTemplate();
    } else {
        // Ensure Header Table has content if it was auto-created by server without properties
        items.forEach(item => {
            if ((item.isBmrHeader || item.isGfHeader) && (!item.data || item.data.length === 0)) {
                const defaults = item.isBmrHeader ? generateDefaultBmrHeader() : generateDefaultGfHeader();
                // Merge default properties into the existing item (preserving its DB id and section_id)
                const originalId = item.id;
                const originalSectionId = item.section_id;
                Object.assign(item, defaults);
                item.id = originalId;
                item.section_id = originalSectionId;
            }
        });
    }

    // Auto-unlock existing linked templates that were locked by previous logic
    items.forEach(item => {
        if (item.type === 'linked-template' && item.locked === true) {
            item.locked = false;
        }
    });

    if (items.length > 0 || pageOrientation !== 'portrait') {
        // Wait for DOM
        document.addEventListener('DOMContentLoaded', () => {
            const nameField = document.getElementById('templateName');
            if (nameField) nameField.value = "{{ $template->category_name ?? ($template->name ?? '') }}";
            const hint = document.getElementById('drop-hint');
            if (hint) {
                if (items.length > 0) hint.classList.add('d-none');
            }
            if (pageOrientation === 'landscape') {
                setOrientation('landscape');
            }
            renderBlocks();
        });
    }

    function setOrientation(orr) {
        pageOrientation = orr;
        const page = document.getElementById('document-page');
        if (!page) return;

        if (orr === 'landscape') {
            page.classList.add('page-landscape');
            document.body.classList.add('printing-landscape');
        } else {
            page.classList.remove('page-landscape');
            document.body.classList.remove('printing-landscape');
        }
    }

    function generateDefaultGfHeader() {
        const id = 'blk_header_' + Date.now();
        const t = {
            sop: "{{ $template->relatived_sop_no ?? '' }}",
            format: "{{ $template->category_code ?? '' }}",
            version: "{{ $template->version ?? '1' }}",
            name: "{{ $template->category_name ?? ($template->name ?? '') }}",
        };

        // Table with 2 columns, no border
        let columns = [{
                label: 'C1',
                type: 'text',
                width: '60%'
            },
            {
                label: 'C2',
                type: 'text',
                width: '40%'
            }
        ];

        let data = [
            // Row 1: SOP and Format No (Using cell properties for styling)
            [{
                    content: `Reference SOP / Số SOP đối chiếu: ${t.sop}`,
                    rs: 1,
                    cs: 1,
                    textAlign: 'left',
                    fontStyle: 'italic',
                    fontSize: '1.1rem'
                },
                {
                    content: `Format no. / Số biểu mẫu: ${t.format}-${t.version}`,
                    rs: 1,
                    cs: 1,
                    textAlign: 'right',
                    fontStyle: 'italic',
                    fontSize: '1.1rem'
                }
            ],
            // Row 2: Main Title (UPPERCASE, Centered)
            [{
                    content: t.name,
                    rs: 1,
                    cs: 2,
                    textAlign: 'center',
                    fontSize: '1.4rem',
                    fontWeight: 'bold',
                    textTransform: 'uppercase'
                },
                {
                    content: '',
                    hidden: true
                }
            ]
        ];

        return {
            id: id,
            type: 'table',
            label: 'GF Header',
            rows: 2,
            cols: 2,
            columns: columns,
            data: data,
            rowHeights: new Array(2).fill('auto'),
            borderMode: 'none',
            hideHeader: true,
            locked: true,
            isGfHeader: true,
            section_id: "{{ $template->caterogy_id ?? 0 }}"
        };
    }

    function generateDefaultBmrHeader() {
        const id = 'blk_header_' + Date.now();
        const t = {
            id: "{{ $template->id ?? '' }}",
            code: "{{ $template->category_code ?? '' }}",
            edition: "{{ $template->version ?? '1' }}",
            name: "{{ $template->category_name ?? '' }}",
            dosage: "{{ $template->dosage_name ?? '' }}",
            type_name: "{{ $template->type_name ?? 'Thuốc Kê Đơn' }}",
            batch_size: "{{ $template->batch_size ?? '' }}",
            effective_date: "{{ $template->effective_date ?? '' }}"
        };

        let columns = [{
                label: 'C1',
                type: 'text',
                width: '25%'
            },
            {
                label: 'C2',
                type: 'text',
                width: '25%'
            },
            {
                label: 'C3',
                type: 'text',
                width: '25%'
            },
            {
                label: 'C4',
                type: 'text',
                width: '25%'
            }
        ];

        let data = [
            // Row 1: Logo and Main Title
            [{
                    content: '<img src="/img/stella-pharm.jpg" style="max-height: 40px;">',
                    rs: 1,
                    cs: 1
                },
                {
                    content: '<div style="font-size: 1.1rem; font-weight: bold; text-align: center;">BATCH MANUFACTURING RECORD/ HỒ SƠ SẢN XUẤT GỐC</div>',
                    rs: 1,
                    cs: 3
                },
                {
                    content: '',
                    hidden: true
                },
                {
                    content: '',
                    hidden: true
                }
            ],
            // Row 2: Product & Dosage unit
            [{
                    content: '<span style="font-size: 0.85rem; font-style: italic;">Product/Sản phẩm</span>',
                    rs: 1,
                    cs: 1
                },
                {
                    content: '<strong style="font-size: 1rem;">: ' + t.name + '</strong>',
                    rs: 1,
                    cs: 1
                },
                {
                    content: '<span style="font-size: 0.85rem; font-style: italic;">Dosage unit<br>Dạng bào chế</span>',
                    rs: 1,
                    cs: 1
                },
                {
                    content: '<strong>: ' + t.dosage + '</strong>',
                    rs: 1,
                    cs: 1
                }
            ],
            // Row 3: BMR No & Grade
            [{
                    content: '<span style="font-size: 0.85rem; font-style: italic;">BMR No./Số BMR</span>',
                    rs: 1,
                    cs: 1
                },
                {
                    content: '<strong>: ' + t.code + '</strong>',
                    rs: 1,
                    cs: 1
                },
                {
                    content: '<span style="font-size: 0.85rem; font-style: italic;">Grade/Phân loại</span>',
                    rs: 1,
                    cs: 1
                },
                {
                    content: '<strong>: ' + t.type_name + '</strong>',
                    rs: 1,
                    cs: 1
                }
            ],
            // Row 4: Version & Batch Size
            [{
                    content: '<span style="font-size: 0.85rem; font-style: italic;">Version No./Số ấn bản</span>',
                    rs: 1,
                    cs: 1
                },
                {
                    content: '<strong style="color: red;">: ' + t.edition + '</strong>',
                    rs: 1,
                    cs: 1
                },
                {
                    content: '<span style="font-size: 0.85rem; font-style: italic;">Batch size/Cỡ lô</span>',
                    rs: 1,
                    cs: 1
                },
                {
                    content: '<strong>: ' + t.batch_size + '</strong>',
                    rs: 1,
                    cs: 1
                }
            ],
            // Row 5: Supersedes & Effective Date
            [{
                    content: '<span style="font-size: 0.85rem; font-style: italic;">Supersedes/<br>Ấn bản thay thế</span>',
                    rs: 1,
                    cs: 1
                },
                {
                    content: '<strong>: 00</strong>',
                    rs: 1,
                    cs: 1
                },
                {
                    content: '<span style="font-size: 0.85rem; font-style: italic;">Effective date/Ngày hiệu lực</span>',
                    rs: 1,
                    cs: 1
                },
                {
                    content: '<strong>: ' + t.effective_date + '</strong>',
                    rs: 1,
                    cs: 1
                }
            ]
        ];

        return {
            id: id,
            type: 'table',
            label: 'BMR Header',
            rows: 5,
            cols: 4,
            columns: columns,
            data: data,
            rowHeights: new Array(5).fill('auto'),
            borderMode: 'visible',
            hideHeader: true,
            locked: true,
            isBmrHeader: true,
            section_id: "{{ $template->caterogy_id ?? 0 }}"
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
