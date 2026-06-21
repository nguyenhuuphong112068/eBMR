<script>
    // Master Data for BOM
    const materialRoles = @json($materialRoles ?? []);
    const materialSpecs = @json($materialSpecs ?? []);

    // Ensure roleOptionsList datalist exists
    $(document).ready(function() {
        if ($('#roleOptionsList').length === 0) {
            let dl = '<datalist id="roleOptionsList">';
            materialRoles.forEach(role => {
                dl += `<option value="${role.name}"></option>`;
            });
            dl += '</datalist>';
            $('body').append(dl);
        }
    });

    // Initialize Summernote
    if ($.fn.summernote) {
        $('.summernote').summernote({
            minHeight: 100,
            placeholder: 'Nhập nội dung...',
            toolbar: [
                ['style', ['bold', 'italic', 'underline', 'clear']],
                ['font', ['strikethrough', 'superscript', 'subscript']],
                ['insert', ['picture']],
                ['view', ['fullscreen', 'codeview']]
            ],
            dialogsInBody: true
        });

        // Sync Summernote div content to hidden inputs on form submit
        $('#metadataForm').on('submit', function() {
            if ($('#create_description_editor').length) {
                $('#create_description_input').val($('#create_description_editor').summernote('code'));
            }
            if ($('#create_storage_conditions_editor').length) {
                $('#create_storage_conditions_input').val($('#create_storage_conditions_editor').summernote(
                    'code'));
            }
        });
    }

    // Add BOM row logic
    let bomRowIndex = 0;

    function addBOMRow(type, targetTableId, item = null) {
        let code = item ? (item.MatID || '') : '';
        let name = item ? (item.MaterialName || '') : '';
        let qty = item ? (item.MatQty || '') : '';
        let uom = item ? (item.uom || '') : '';

        // We no longer build roleOptions for select, we use datalist 'roleOptionsList'


        let specOptions = '<option value="">-Chọn-</option>';
        materialSpecs.forEach(spec => {
            specOptions += `<option value="${spec.name}">${spec.name}</option>`;
        });

        const tr = `
                    <tr class="bom-row" data-index="${bomRowIndex}">
                        <td class="text-center align-middle stt-col" style="font-weight:bold;"></td>
                        <input type="hidden" name="bom[${bomRowIndex}][type]" value="${type}">
                        <td class="align-middle p-1">
                            <div class="materials-col-code">
                                <div class="material-group mt-1" data-mat-index="0">
                                    <textarea class="form-control auto-resize" name="bom[${bomRowIndex}][materials][0][code]" placeholder="Mã NL" rows="1">${code}</textarea>
                                </div>
                            </div>
                        </td>
                        <td class="align-middle p-1">
                            <div class="materials-col-name">
                                <div class="material-group mt-1" data-mat-index="0">
                                    <textarea class="form-control auto-resize" name="bom[${bomRowIndex}][materials][0][name]" placeholder="Thành phần" rows="2">${name}</textarea>
                                </div>
                            </div>
                        </td>
                        <td class="align-middle">
                            <textarea class="form-control auto-resize" name="bom[${bomRowIndex}][role]" placeholder="-Nhập-" rows="1" autocomplete="off">${item ? (item.role || '') : ''}</textarea>
                        </td>
                        <td class="align-middle p-1">
                            <div class="materials-col-manufacturer">
                                <div class="material-group mt-1" data-mat-index="0">
                                    <textarea class="form-control auto-resize" name="bom[${bomRowIndex}][materials][0][manufacturer]" placeholder="Nhà SX" rows="1"></textarea>
                                </div>
                            </div>
                        </td>
                        <td class="align-middle p-1">
                            <div class="materials-col-spec">
                                <div class="material-group mt-1 d-flex align-items-start position-relative" data-mat-index="0">
                                    <select class="form-control custom-select" name="bom[${bomRowIndex}][materials][0][Spec]">
                                        ${specOptions}
                                    </select>
                                    <button type="button" class="btn btn-xs btn-outline-primary ms-1 mt-1 btn_add_material" title="Thêm mã nguyên liệu"><i class="fa fa-plus"></i></button>
                                </div>
                            </div>
                        </td>
                        <td class="align-middle">
                            <div class="d-flex align-items-center mb-1">
                                <input type="number" step="any" class="form-control total-amount-unit-input" name="bom[${bomRowIndex}][total_amount_per_unit]" placeholder="Tổng" value="${qty}">
                                <input type="text" class="form-control ms-1 text-center px-1" name="bom[${bomRowIndex}][uom]" placeholder="ĐV" style="width: 50px;" value="${uom}">
                                <button type="button" class="btn btn-xs btn-outline-info ms-1 btn_add_sub_amount" title="Chia phần"><i class="fa fa-plus"></i></button>
                            </div>
                            <div class="d-flex align-items-center justify-content-center mt-1 pb-1">
                                <label class="mb-0 text-muted d-flex align-items-center" style="font-size: 0.75rem; cursor: pointer;">
                                    <input type="checkbox" name="bom[${bomRowIndex}][not_calculator]" value="1" class="not-calculator-cb me-1" style="width: 14px; height: 14px; cursor: pointer; margin: 0;" title="Đánh dấu để KHÔNG tính vào tổng và tỉ lệ" ${item && item.not_calculator == 1 ? 'checked' : ''}>
                                    Không tính tổng
                                </label>
                            </div>
                            <div class="sub-amounts-container"></div>
                        </td>
                        <td class="align-middle"><input type="text" class="form-control ratio-display text-center text-success fw-bold" placeholder="%" readonly style="background-color: transparent;"></td>
                        <td class="align-middle">
                            <div class="d-flex align-items-center mb-1">
                                <input type="text" class="form-control total-amount-batch-input" name="bom[${bomRowIndex}][total_amount_per_batch]" placeholder="1 lô" readonly style="background-color: transparent;">
                            </div>
                            <div class="sub-amounts-batch-container"></div>
                        </td>
                        <td class="text-center align-middle">
                            <button type="button" class="btn btn-xs btn-danger btn_remove_bom_row"><i class="fa fa-trash"></i></button>
                        </td>
                    </tr>
                `;
        const $tr = $(tr);
        $(`#${targetTableId}`).append($tr);
        bomRowIndex++;
        updateBOMSTT();

        if (window.isConfigReadOnly) {
            $tr.find('input, select, textarea').prop('disabled', true);
            $tr.find('.btn_add_material, .btn_add_sub_amount, .btn_remove_bom_row').hide();
        }
    }

    // Handle adding sub-amounts
    $(document).on('click', '.btn_add_sub_amount', function() {
        const row = $(this).closest('.bom-row');
        const rowIndex = row.data('index');
        const container = row.find('.sub-amounts-container');
        const batchContainer = row.find('.sub-amounts-batch-container');
        const subIndex = container.find('.sub-amount-item').length;

        const subHtml = `
                    <div class="sub-amount-item d-flex align-items-center mt-1">
                        <input type="number" step="any" class="form-control py-0" 
                            name="bom[${rowIndex}][sub_amounts][${subIndex}][amount_per_unit]" 
                            placeholder="Lượng" style="height: 28px; font-size: 0.8rem; min-width: 60px;">
                        <input type="hidden" name="bom[${rowIndex}][sub_amounts][${subIndex}][note]" class="sub-amount-note-input" value="">
                        <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 ms-1 btn_edit_sub_note" title="Thêm ghi chú" style="height: 28px;">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2 ms-1 btn_remove_sub_amount" style="height: 28px;">
                            <i class="fa fa-times"></i>
                        </button>
                    </div>
                `;
        container.append(subHtml);

        const subBatchHtml = `
                    <div class="sub-amount-batch-item d-flex align-items-center mt-1" style="height: 28px;">
                        <input type="number" step="any" class="form-control py-0 text-center" 
                            name="bom[${rowIndex}][sub_amounts][${subIndex}][amount_per_batch]" 
                            placeholder="Lô" readonly style="height: 28px; font-size: 0.8rem; background-color: transparent;">
                    </div>
                `;
        batchContainer.append(subBatchHtml);

        calculateRowValues(row);
    });

    // Handle editing sub-amount note
    let currentSubNoteBtn = null;
    let currentSubNoteInput = null;

    $(document).on('click', '.btn_edit_sub_note', function() {
        currentSubNoteBtn = $(this);
        currentSubNoteInput = currentSubNoteBtn.siblings('.sub-amount-note-input');
        const currentValue = currentSubNoteInput.val();

        $('#subNoteTextarea').val(currentValue);
        $('#subNoteModal').modal('show');
    });

    $('#btnSaveSubNote').click(function() {
        if (currentSubNoteInput && currentSubNoteBtn) {
            const newValue = $('#subNoteTextarea').val();
            currentSubNoteInput.val(newValue);
            if (newValue.trim() !== '') {
                currentSubNoteBtn.removeClass('btn-outline-secondary').addClass('btn-secondary text-white');
            } else {
                currentSubNoteBtn.removeClass('btn-secondary text-white').addClass('btn-outline-secondary');
            }
        }
        $('#subNoteModal').modal('hide');
    });

    // Auto-resize logic
    $(document).on('input', '.auto-resize', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });

    $(document).on('click', '.btn_remove_sub_amount', function() {
        const item = $(this).closest('.sub-amount-item');
        const row = item.closest('.bom-row');
        const index = item.index();
        row.find('.sub-amounts-batch-container .sub-amount-batch-item').eq(index).remove();
        item.remove();
        reindexSubAmounts(row);
        calculateRowValues(row);
    });

    // Handle adding/removing materials within a BOM row
    $(document).on('click', '.btn_add_material', function() {
        const row = $(this).closest('tr.bom-row');
        const rowIndex = row.data('index');

        let nextMatIndex = 0;
        row.find('.materials-col-code .material-group').each(function() {
            const idx = parseInt($(this).data('mat-index'));
            if (idx >= nextMatIndex) nextMatIndex = idx + 1;
        });

        // Copy spec options from the first row
        const specOptionsHTML = row.find('select[name^="bom["][name$="][Spec]"]').first().html();

        row.find('.materials-col-code').append(`
                    <div class="material-group mt-1" data-mat-index="${nextMatIndex}">
                        <textarea class="form-control auto-resize border-top border-info pt-2" name="bom[${rowIndex}][materials][${nextMatIndex}][code]" placeholder="Mã NL" rows="1"></textarea>
                    </div>
                `);

        row.find('.materials-col-name').append(`
                    <div class="material-group mt-1" data-mat-index="${nextMatIndex}">
                        <textarea class="form-control auto-resize border-top border-info pt-2" name="bom[${rowIndex}][materials][${nextMatIndex}][name]" placeholder="Thành phần" rows="2"></textarea>
                    </div>
                `);

        row.find('.materials-col-manufacturer').append(`
                    <div class="material-group mt-1" data-mat-index="${nextMatIndex}">
                        <textarea class="form-control auto-resize border-top border-info pt-2" name="bom[${rowIndex}][materials][${nextMatIndex}][manufacturer]" placeholder="Nhà SX" rows="1"></textarea>
                    </div>
                `);

        row.find('.materials-col-spec').append(`
                    <div class="material-group mt-1 d-flex align-items-start position-relative border-top border-info pt-2" data-mat-index="${nextMatIndex}">
                        <select class="form-control custom-select" name="bom[${rowIndex}][materials][${nextMatIndex}][Spec]">
                            ${specOptionsHTML}
                        </select>
                        <button type="button" class="btn btn-xs btn-outline-danger ms-1 mt-1 btn_remove_material" title="Xóa mã NL"><i class="fa fa-times"></i></button>
                    </div>
                `);
    });

    $(document).on('click', '.btn_remove_material', function() {
        const group = $(this).closest('.material-group');
        const matIndex = group.data('mat-index');
        const row = $(this).closest('tr.bom-row');

        row.find(`.material-group[data-mat-index="${matIndex}"]`).remove();
    });

    // Handle Mã Nguyên Liệu auto-fill
    $(document).on('change', 'textarea[name$="[code]"]', function() {
        const codeInput = $(this);
        const code = codeInput.val().trim();
        if (!code) return;

        const row = codeInput.closest('tr.bom-row');
        const group = codeInput.closest('.material-group');
        const matIndex = group.data('mat-index');

        $.get('{{ route('pages.ebmr.getMaterialInfo') }}', {
            code: code
        }, function(res) {
            if (res.success) {
                const nameInput = row.find(
                    `.materials-col-name .material-group[data-mat-index="${matIndex}"] textarea`);
                const manufInput = row.find(
                    `.materials-col-manufacturer .material-group[data-mat-index="${matIndex}"] textarea`
                );

                if (res.name) {
                    nameInput.val(res.name).trigger('input');
                }
                if (res.manufacturer) {
                    manufInput.val(res.manufacturer).trigger('input');
                }
            }
        });
    });

    $('#btn_add_bom_row_type_0').click(function() {
        addBOMRow(0, 'bom_table_body_type_0');
    });

    $('#btn_add_bom_row_type_1').click(function() {
        addBOMRow(1, 'bom_table_body_type_1');
    });

    $(document).on('click', '.btn_remove_bom_row', function() {
        $(this).closest('tr').remove();
        updateBOMSTT();
    });

    function updateBOMSTT() {
        $('#bom_table_body_type_0 tr').each(function(index) {
            $(this).find('.stt-col').text(index + 1);
        });
        $('#bom_table_body_type_1 tr').each(function(index) {
            $(this).find('.stt-col').text(index + 1);
        });
    }

    window.renderBOMRows = function(bomData) {
        bomRowIndex = 0;
        $('#bom_table_body_type_0').empty();
        $('#bom_table_body_type_1').empty();
        function generateNoteUI(noteValue, inputName) {
            const hasNote = noteValue && noteValue.trim() !== '';
            const color = hasNote ? '#17a2b8' : '#ccc';
            return `
                <div class="position-absolute cell-note-container" style="top: 2px; right: 2px; z-index: 10;">
                    <button type="button" class="btn btn-xs btn_cell_note" title="Ghi chú" style="padding: 0 4px; font-size: 11px; border: none; background: transparent; color: ${color};">
                        <i class="fa fa-comment${hasNote ? '' : '-dots'}"></i>
                    </button>
                    <div class="cell-note-popover d-none position-absolute bg-white border rounded shadow p-2" style="width: 200px; right: 0; top: 100%; z-index: 1000;">
                        <textarea class="form-control form-control-sm cell-note-input" name="${inputName}" rows="2" placeholder="Nhập ghi chú...">${noteValue || ''}</textarea>
                        <div class="mt-1 text-end">
                            <button type="button" class="btn btn-xs btn-primary btn_close_cell_note">Lưu</button>
                        </div>
                    </div>
                </div>
            `;
        }

        bomData.forEach((formula, formulaIdx) => {
            const targetTableId = formula.type == 0 ? 'bom_table_body_type_0' : 'bom_table_body_type_1';

            let cellNotesForm = formula.cell_notes || {};
            if (typeof cellNotesForm === 'string') {
                try { cellNotesForm = JSON.parse(cellNotesForm); } catch(e) { cellNotesForm = {}; }
            }


            let materialsCodeHtml = '';
            let materialsNameHtml = '';
            let materialsManufHtml = '';
            let materialsSpecHtml = '';

            const mats = formula.materials && formula.materials.length > 0 ?
                formula.materials : [{
                    code: formula.code || '',
                    name: formula.name || '',
                    manufacturer: formula.manufacturer || '',
                    Spec: formula.Spec || ''
                }];

            mats.forEach((mat, mIdx) => {
                let cellNotesMat = mat.cell_notes || {};
                if (typeof cellNotesMat === 'string') {
                    try { cellNotesMat = JSON.parse(cellNotesMat); } catch(e) { cellNotesMat = {}; }
                }

                let specOptionsHtml = '<option value="">-Chọn-</option>';
                materialSpecs.forEach(spec => {
                    specOptionsHtml +=
                        `<option value="${spec.name}" ${mat.Spec == spec.name ? 'selected' : ''}>${spec.name}</option>`;
                });

                materialsCodeHtml += `
                            <div class="material-group mt-1 position-relative" data-mat-index="${mIdx}">
                                <textarea class="form-control auto-resize pe-4" name="bom[${bomRowIndex}][materials][${mIdx}][code]" placeholder="Mã NL" rows="1">${mat.code || ''}</textarea>
                                ${generateNoteUI(cellNotesMat.code, `bom[${bomRowIndex}][materials][${mIdx}][cell_notes][code]`)}
                            </div>
                        `;
                materialsNameHtml += `
                            <div class="material-group mt-1 position-relative" data-mat-index="${mIdx}">
                                <textarea class="form-control auto-resize pe-4" name="bom[${bomRowIndex}][materials][${mIdx}][name]" placeholder="Thành phần" rows="2">${mat.name || ''}</textarea>
                                ${generateNoteUI(cellNotesMat.name, `bom[${bomRowIndex}][materials][${mIdx}][cell_notes][name]`)}
                            </div>
                        `;
                materialsManufHtml += `
                            <div class="material-group mt-1 position-relative" data-mat-index="${mIdx}">
                                <textarea class="form-control auto-resize pe-4" name="bom[${bomRowIndex}][materials][${mIdx}][manufacturer]" placeholder="Nhà SX" rows="1">${mat.manufacturer || ''}</textarea>
                                ${generateNoteUI(cellNotesMat.manufacturer, `bom[${bomRowIndex}][materials][${mIdx}][cell_notes][manufacturer]`)}
                            </div>
                        `;
                materialsSpecHtml += `
                            <div class="material-group mt-1 d-flex align-items-start position-relative" data-mat-index="${mIdx}">
                                <div class="position-relative flex-grow-1">
                                    <select class="form-control custom-select pe-4" name="bom[${bomRowIndex}][materials][${mIdx}][Spec]">
                                        ${specOptionsHtml}
                                    </select>
                                    ${generateNoteUI(cellNotesMat.Spec, `bom[${bomRowIndex}][materials][${mIdx}][cell_notes][Spec]`)}
                                </div>
                                ${mIdx === 0 
                                    ? `<button type="button" class="btn btn-xs btn-outline-primary ms-1 mt-1 btn_add_material" title="Thêm mã nguyên liệu"><i class="fa fa-plus"></i></button>` 
                                    : `<button type="button" class="btn btn-xs btn-outline-danger ms-1 mt-1 btn_remove_material" title="Xóa mã NL"><i class="fa fa-times"></i></button>`}
                            </div>
                        `;
            });

            let tr = `
                        <tr class="bom-row" data-index="${bomRowIndex}">
                            <td class="text-center align-middle stt-col" style="font-weight:bold;"></td>
                            ${formula.id ? `<input type="hidden" name="bom[${bomRowIndex}][id]" value="${formula.id}">` : ''}
                            <input type="hidden" name="bom[${bomRowIndex}][type]" value="${formula.type}">
                            <td class="align-middle p-1">
                                <div class="materials-col-code">${materialsCodeHtml}</div>
                            </td>
                            <td class="align-middle p-1">
                                <div class="materials-col-name">${materialsNameHtml}</div>
                            </td>
                            <td class="align-middle">
                                <textarea class="form-control auto-resize" name="bom[${bomRowIndex}][role]" placeholder="-Nhập-" rows="1" autocomplete="off">${formula.role || ''}</textarea>
                            </td>
                            <td class="align-middle p-1">
                                <div class="materials-col-manufacturer">${materialsManufHtml}</div>
                            </td>
                            <td class="align-middle p-1">
                                <div class="materials-col-spec">${materialsSpecHtml}</div>
                            </td>
                            <td class="align-middle position-relative">
                                ${generateNoteUI(cellNotesForm.total_amount_per_unit, `bom[${bomRowIndex}][cell_notes][total_amount_per_unit]`)}
                                <div class="d-flex align-items-center mb-1 pe-4">
                                    <input type="number" step="any" class="form-control total-amount-unit-input" name="bom[${bomRowIndex}][total_amount_per_unit]" placeholder="Tổng" value="${formula.total_amount_per_unit || ''}">
                                    <input type="text" class="form-control ms-1 text-center px-1" name="bom[${bomRowIndex}][uom]" placeholder="ĐV" style="width: 50px;" value="${formula.uom || ''}">
                                    <button type="button" class="btn btn-xs btn-outline-info ms-1 btn_add_sub_amount" title="Chia phần"><i class="fa fa-plus"></i></button>
                                </div>
                                <div class="d-flex align-items-center justify-content-center mt-1 pb-1">
                                    <label class="mb-0 text-muted d-flex align-items-center" style="font-size: 0.75rem; cursor: pointer;">
                                        <input type="checkbox" name="bom[${bomRowIndex}][not_calculator]" value="1" class="not-calculator-cb me-1" style="width: 14px; height: 14px; cursor: pointer; margin: 0;" title="Đánh dấu để KHÔNG tính vào tổng và tỉ lệ" ${formula.not_calculator == 1 ? 'checked' : ''}>
                                        Không tính tổng
                                    </label>
                                </div>
                                <div class="sub-amounts-container"></div>
                            </td>
                            <td class="align-middle"><input type="text" class="form-control ratio-display text-center text-success fw-bold" placeholder="%" readonly style="background-color: transparent;"></td>
                            <td class="align-middle position-relative">
                                ${generateNoteUI(cellNotesForm.total_amount_per_batch, `bom[${bomRowIndex}][cell_notes][total_amount_per_batch]`)}
                                <div class="d-flex align-items-center mb-1 pe-4">
                                    <input type="text" class="form-control total-amount-batch-input fw-bold" name="bom[${bomRowIndex}][total_amount_per_batch]" placeholder="1 lô" value="${formula.total_amount_per_batch || ''}" readonly style="background-color: transparent;">
                                </div>
                                <div class="d-flex align-items-center justify-content-center mt-1">
                                    <label class="mb-0 text-muted d-flex align-items-center" style="font-size: 0.75rem; cursor: pointer;">
                                        <input type="checkbox" name="bom[${bomRowIndex}][has_split_batches]" value="1" class="has-split-batches-cb me-1" style="width: 14px; height: 14px; cursor: pointer; margin: 0;" ${formula.number_of_lots > 1 ? 'checked' : ''}>
                                        Chia mẻ
                                    </label>
                                </div>
                                <div class="split-batch-container d-flex align-items-center mt-1" style="display: ${formula.number_of_lots > 1 ? 'flex' : 'none'} !important;">
                                    <input type="number" min="1" step="1" class="form-control number-of-lots-input form-control-sm text-center px-1" name="bom[${bomRowIndex}][number_of_lots]" value="${formula.number_of_lots || 1}" title="Số mẻ" placeholder="Mẻ" style="width: 45px;">
                                    <span class="mx-1 text-muted small">x</span>
                                    <input type="text" class="form-control amounts-of-lots-input form-control-sm px-1 text-primary" name="bom[${bomRowIndex}][amounts_of_lots]" value="${formula.amounts_of_lots || ''}" title="Lượng / mẻ" placeholder="Lượng/mẻ" readonly style="background-color: transparent;">
                                </div>
                                <div class="sub-amounts-batch-container"></div>
                            </td>
                            <td class="text-center align-middle">
                                <button type="button" class="btn btn-xs btn-danger btn_remove_bom_row"><i class="fa fa-trash"></i></button>
                            </td>
                        </tr>
                    `;

            const $tr = $(tr);

            // Render sub-amounts
            if (formula.sub_amounts && formula.sub_amounts.length > 0) {
                const container = $tr.find('.sub-amounts-container');
                const batchContainer = $tr.find('.sub-amounts-batch-container');
                formula.sub_amounts.forEach((sub, subIdx) => {
                    const subHtml = `
                                <div class="sub-amount-item d-flex align-items-center mt-1">
                                    <input type="number" step="any" class="form-control py-0" 
                                        name="bom[${bomRowIndex}][sub_amounts][${subIdx}][amount_per_unit]" 
                                        placeholder="Lượng" style="height: 28px; font-size: 0.8rem; min-width: 60px;" value="${sub.amount_per_unit || ''}">
                                    <input type="hidden" name="bom[${bomRowIndex}][sub_amounts][${subIdx}][note]" class="sub-amount-note-input" value="${sub.note || ''}">
                                    <button type="button" class="btn btn-sm ${sub.note ? 'btn-secondary text-white' : 'btn-outline-secondary'} py-0 px-2 ms-1 btn_edit_sub_note" title="Thêm ghi chú" style="height: 28px;">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2 ms-1 btn_remove_sub_amount" style="height: 28px;">
                                        <i class="fa fa-times"></i>
                                    </button>
                                </div>
                            `;
                    container.append(subHtml);

                    const subBatchHtml = `
                                <div class="sub-amount-batch-item d-flex align-items-center mt-1" style="height: 28px;">
                                    <input type="number" step="any" class="form-control py-0 text-center" 
                                        name="bom[${bomRowIndex}][sub_amounts][${subIdx}][amount_per_batch]" 
                                        placeholder="Lô" value="${sub.amount_per_batch || ''}" readonly style="height: 28px; font-size: 0.8rem; background-color: transparent;">
                                </div>
                            `;
                    batchContainer.append(subBatchHtml);
                });
            }

            $(`#${targetTableId}`).append($tr);
            bomRowIndex++;
        });

        updateBOMSTT();
        updateBOMNotes();
        checkTableSum('type_0');
        checkTableSum('type_1');

        if (window.isConfigReadOnly) {
            $('#bom_table_body_type_0, #bom_table_body_type_1').find('input:not([type="hidden"]), select, textarea')
                .prop('disabled', true);
            $('#bom_table_body_type_0, #bom_table_body_type_1').find(
                '.btn_add_material, .btn_remove_material, .btn_add_sub_amount, .btn_remove_sub_amount, .btn_edit_sub_note, .btn_remove_bom_row'
            ).hide();
            $('#btn_add_bom_row_type_0, #btn_add_bom_row_type_1').hide();
            $('#update_avg_core, #update_average_unit_weight').prop('disabled', true);
        } else {
            $('#btn_add_bom_row_type_0, #btn_add_bom_row_type_1').show();
            $('#update_avg_core, #update_average_unit_weight').prop('disabled', false);
        }

        setTimeout(function() {
            $('.auto-resize').each(function() {
                this.style.height = 'auto';
                if (this.scrollHeight > 0) {
                    this.style.height = (this.scrollHeight) + 'px';
                }
            });
        }, 100);
    };

    // --- AUTO-CALCULATION & NOTES LOGIC ---

    // Auto-calculate Tổng when Lượng is changed
    $(document).on('input', '.sub-amount-item input[type="number"]', function() {
        const row = $(this).closest('tr.bom-row');
        let sum = 0;
        let hasValue = false;
        row.find('.sub-amount-item input[type="number"]').each(function() {
            const val = parseFloat($(this).val());
            if (!isNaN(val)) {
                sum += val;
                hasValue = true;
            }
        });

        const totalInput = row.find('input[name$="[total_amount_per_unit]"]');
        if (hasValue) {
            totalInput.val(sum).trigger('input');
        } else {
            totalInput.val('').trigger('input');
        }
    });

    function reindexSubAmounts(row) {
        const rowIndex = row.data('index');

        row.find('.sub-amounts-container .sub-amount-item').each(function(subIndex) {
            $(this).find('input[name$="[amount_per_unit]"]').attr('name',
                `bom[${rowIndex}][sub_amounts][${subIndex}][amount_per_unit]`);
            $(this).find('input[name$="[note]"]').attr('name',
                `bom[${rowIndex}][sub_amounts][${subIndex}][note]`);
        });

        row.find('.sub-amounts-batch-container .sub-amount-batch-item').each(function(subIndex) {
            $(this).find('input[name$="[amount_per_batch]"]').attr('name',
                `bom[${rowIndex}][sub_amounts][${subIndex}][amount_per_batch]`);
        });
    }

    function calculateRowValues(row) {
        const totalInput = row.find('input[name$="[total_amount_per_unit]"]');
        const batchInput = row.find('input[name$="[total_amount_per_batch]"]');
        const ratioInput = row.find('.ratio-display');
        const unitVal = parseFloat(totalInput.val());

        const isType0 = row.closest('tbody').attr('id') === 'bom_table_body_type_0';
        const avgInputId = isType0 ? '#update_avg_core' : '#update_average_unit_weight';
        const avgWeight = parseFloat($(avgInputId).val());
        const notCalculator = row.find('.not-calculator-cb').is(':checked');
        const isCalculate = !notCalculator;

        if (!isNaN(unitVal)) {
            // 1. Calculate Ratio (%)
            if (isCalculate && !isNaN(avgWeight) && avgWeight > 0) {
                const ratio = (unitVal / avgWeight) * 100;
                ratioInput.val(ratio.toFixed(2));
            } else if (!isCalculate) {
                ratioInput.val('NA');
            } else {
                ratioInput.val('');
            }

            // 2. Calculate Lô tiêu chuẩn (kg)
            let batchVal = 0;
            if (!isNaN(avgWeight) && avgWeight > 0 && window.currentBatchSize > 0) {
                batchVal = (unitVal * window.currentBatchSize) / avgWeight;
            } else if (window.currentBatchQty > 0) {
                batchVal = (unitVal * window.currentBatchQty) / 1000000;
            }

            const numberOfLotsInput = row.find('.number-of-lots-input');
            const amountsOfLotsInput = row.find('.amounts-of-lots-input');
            const hasSplitCb = row.find('.has-split-batches-cb').is(':checked');

            let numLots = 1;
            if (hasSplitCb) {
                numLots = parseInt(numberOfLotsInput.val());
                if (isNaN(numLots) || numLots < 1) numLots = 1;
            } else {
                numberOfLotsInput.val(1);
            }

            if (batchVal > 0) {
                batchInput.val(isCalculate ? batchVal.toFixed(3) : `(${batchVal.toFixed(3)})`);
                amountsOfLotsInput.val((batchVal / numLots).toFixed(3));
            } else {
                batchInput.val('');
                amountsOfLotsInput.val('');
            }

            // Wrap unit in parens if not calculated
            const unitInput = row.find('.total-amount-unit-input');
            // We only format the display value? No, if we format the input value, it breaks parsing.
            // Let's keep input clean, we only format it on Word export, but for UI we can just show parens next to it?
            // The user said "Cột 1 viên sẽ tự động được bọc trong dấu ngoặc đơn", but it's an input type="number".
            // We can't put parens inside an input type="number". We should change it to type="text" if we want to format, OR just use CSS/JS to show parens around it.
            // I will change the logic here to just handle the sum exclusion first.
        } else {
            ratioInput.val('');
            batchInput.val('');
            row.find('.amounts-of-lots-input').val('');
        }

        // Calculate batch values for each sub-amount
        row.find('.sub-amounts-container .sub-amount-item').each(function(subIndex) {
            const subUnitVal = parseFloat($(this).find('input[name$="[amount_per_unit]"]').val());
            const subBatchInput = row.find('.sub-amounts-batch-container .sub-amount-batch-item').eq(subIndex)
                .find('input[name$="[amount_per_batch]"]');

            if (!isNaN(subUnitVal)) {
                if (!isNaN(avgWeight) && avgWeight > 0 && window.currentBatchSize > 0) {
                    const subBatchVal = (subUnitVal * window.currentBatchSize) / avgWeight;
                    subBatchInput.val(subBatchVal.toFixed(3));
                } else if (window.currentBatchQty > 0) {
                    const subBatchVal = (subUnitVal * window.currentBatchQty) / 1000000;
                    subBatchInput.val(subBatchVal.toFixed(3));
                } else {
                    subBatchInput.val('');
                }
            } else {
                subBatchInput.val('');
            }
        });

        checkTableSum(isType0 ? 'type_0' : 'type_1');
    }

    function checkTableSum(type) {
        const isType0 = type === 'type_0';
        const tbodyId = isType0 ? '#bom_table_body_type_0' : '#bom_table_body_type_1';
        const avgInputId = isType0 ? '#update_avg_core' : '#update_average_unit_weight';
        const warningId = isType0 ? '#warning_type_0' : '#warning_type_1';

        const avgWeight = parseFloat($(avgInputId).val());
        if (isNaN(avgWeight) || avgWeight <= 0) {
            $(warningId).hide();
            $(avgInputId).removeClass('border-danger text-danger');
            return;
        }

        let sum = 0;
        $(tbodyId).find('tr.bom-row').each(function() {
            const notCalculator = $(this).find('.not-calculator-cb').is(':checked');
            if (!notCalculator) {
                const val = parseFloat($(this).find('.total-amount-unit-input').val());
                if (!isNaN(val)) sum += val;
            }
        });

        sum = parseFloat(sum.toFixed(4));
        const roundedAvg = parseFloat(avgWeight.toFixed(4));

        if (sum > roundedAvg) {
            $(warningId).find('.sum-val').text(sum);
            $(warningId).find('.avg-val').text(roundedAvg);
            $(warningId).show();
            $(avgInputId).addClass('border-danger text-danger');
        } else {
            $(warningId).hide();
            $(avgInputId).removeClass('border-danger text-danger');
        }
    }

    // Auto-calculate Lô tiêu chuẩn and Ratio when 1 viên (mg) is changed
    $(document).on('input', 'input[name$="[total_amount_per_unit]"]', function() {
        calculateRowValues($(this).closest('tr.bom-row'));
    });

    $(document).on('input', '.number-of-lots-input', function() {
        calculateRowValues($(this).closest('tr.bom-row'));
    });

    $(document).on('change', '.has-split-batches-cb', function() {
        const row = $(this).closest('tr.bom-row');
        if ($(this).is(':checked')) {
            row.find('.split-batch-container').attr('style', 'display: flex !important;');
        } else {
            row.find('.split-batch-container').attr('style', 'display: none !important;');
        }
        calculateRowValues(row);
    });

    $(document).on('change', '.not-calculator-cb', function() {
        calculateRowValues($(this).closest('tr.bom-row'));
    });

    // Re-calculate all rows when Khối lượng TB is changed
    $('#update_avg_core').on('input', function() {
        $('#bom_table_body_type_0 tr.bom-row').each(function() {
            calculateRowValues($(this));
        });
    });

    $('#update_average_unit_weight').on('input', function() {
        $('#bom_table_body_type_1 tr.bom-row').each(function() {
            calculateRowValues($(this));
        });
    });

    // Update BOM Notes display
    function updateBOMNotes() {
        ['type_0', 'type_1'].forEach(type => {
            let html = '';
            let rowIndexBase = 1;
            let cellNoteCounter = 0;

            $(`#bom_table_body_${type} tr.bom-row`).each(function() {
                const row = $(this);
                let subNoteIndex = 0;

                row.find('.sub-amount-item').each(function() {
                    const noteInput = $(this).find('.sub-amount-note-input');
                    const noteVal = noteInput.val().trim();

                    $(this).find('.note-superscript').remove();

                    if (noteVal) {
                        const idxLabel =
                            `${rowIndexBase}${String.fromCharCode(97 + subNoteIndex)}`; // e.g., 1a, 1b
                        $(this).find('.btn_edit_sub_note').after(
                            `<sup class="note-superscript ms-1 text-danger fw-bold">(${idxLabel})</sup>`
                        );
                        html +=
                            `<div><span class="text-danger fw-bold">(${idxLabel})</span> ${noteVal.replace(/\n/g, '<br>')}</div>`;
                        subNoteIndex++;
                    }
                });

                row.find('.cell-note-container').each(function() {
                    const container = $(this);
                    const noteInput = container.find('.cell-note-input');
                    const noteVal = noteInput.val() ? noteInput.val().trim() : '';
                    const btn = container.find('.btn_cell_note');

                    container.find('.cell-note-superscript').remove();

                    if (noteVal) {
                        cellNoteCounter++;
                        const idxLabel = `*${cellNoteCounter}`;
                        btn.html(`<span class="fw-bold" style="font-size: 11px;">(${idxLabel})</span>`);
                        btn.css('color', '#17a2b8');
                        html +=
                            `<div class="mt-1"><span class="text-info fw-bold">(${idxLabel})</span> ${noteVal.replace(/\n/g, '<br>')}</div>`;
                    } else {
                        btn.html('<i class="fa fa-comment-dots"></i>');
                        btn.css('color', '#ccc');
                    }
                });

                rowIndexBase++;
            });

            $(`#bom_notes_${type}`).html(html);
        });
    }

    // Hook up updateBOMNotes when sub note is saved
    $('#btnSaveSubNote').click(function() {
        updateBOMNotes();
    });

    // Hook up updateBOMNotes when sub-amount row is removed
    $(document).on('click', '.btn_remove_sub_amount', function() {
        updateBOMNotes();
    });

    $(document).on('click', '.btn_remove_bom_row', function() {
        updateBOMNotes();
        checkTableSum('type_0');
        checkTableSum('type_1');
    });

    // Initial render hook if needed
    window.addEventListener('load', function() {
        setTimeout(updateBOMNotes, 500);
    });

    // Import from Word Logic
    window.openImportBomModal = function(type) {
        $('#importBomType').val(type);
        $('#importBomPasteArea').empty();
        $('#importBomModal').modal('show');
    };

    window.processImportedBom = function() {
        const pasteArea = document.getElementById('importBomPasteArea');
        const html = pasteArea.innerHTML;
        const type = $('#importBomType').val();
        const targetTableId = 'bom_table_body_type_' + type;

        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = html;
        const table = tempDiv.querySelector('table');
        if (!table) {
            Swal.fire('Lỗi', 'Không tìm thấy bảng. Vui lòng copy và dán một bảng từ Word hoặc Excel.', 'error');
            return;
        }

        const rows = Array.from(table.rows);
        const data = [];
        const rowspanTracker = {};

        rows.forEach((row, rIdx) => {
            const rowData = [];
            let colIndex = 0;
            let cellIdx = 0;
            const cells = Array.from(row.cells);

            let maxTrackedCol = -1;
            for (let key in rowspanTracker) {
                if (rowspanTracker[key].remaining > 0 && parseInt(key) > maxTrackedCol) {
                    maxTrackedCol = parseInt(key);
                }
            }

            while (cellIdx < cells.length || colIndex <= maxTrackedCol) {
                if (rowspanTracker[colIndex] && rowspanTracker[colIndex].remaining > 0) {
                    rowData[colIndex] = rowspanTracker[colIndex].value;
                    rowspanTracker[colIndex].remaining--;
                    colIndex++;
                } else if (cellIdx < cells.length) {
                    const cell = cells[cellIdx];
                    const cellText = cell.innerText.trim().replace(/\n/g, ' ').replace(/\s+/g, ' ');
                    rowData[colIndex] = cellText;

                    const rowspan = parseInt(cell.getAttribute('rowspan') || 1);
                    const colspan = parseInt(cell.getAttribute('colspan') || 1);

                    if (rowspan > 1) {
                        for (let c = 0; c < colspan; c++) {
                            rowspanTracker[colIndex + c] = {
                                remaining: rowspan - 1,
                                value: cellText
                            };
                        }
                    }
                    colIndex += colspan;
                    cellIdx++;
                } else {
                    colIndex++;
                }
            }

            if (rowData.length >= 5) {
                const joinedRow = rowData.join('').toLowerCase();
                if (!joinedRow.includes('stt') && !joinedRow.includes('thành phần') && !joinedRow.includes(
                        'tổng cộng') && joinedRow.trim() !== '') {
                    data.push(rowData);
                }
            }
        });

        if (data.length === 0) {
            Swal.fire('Lỗi', 'Bảng trống hoặc không đúng định dạng.', 'error');
            return;
        }

        // Clean data: remove rows that have no Role, Manufacturer, Spec, and Amount
        // These are usually junk rows caused by page breaks or rowspan artifacts in Word HTML
        const cleanedData = [];
        data.forEach(rowData => {
            const hasRole = rowData[3] && rowData[3].toString().trim() !== '';
            const hasManuf = rowData[4] && rowData[4].toString().trim() !== '';
            const hasSpec = rowData[5] && rowData[5].toString().trim() !== '';
            const hasAmount = rowData[6] && rowData[6].toString().trim() !== '';

            if (hasRole || hasManuf || hasSpec || hasAmount) {
                cleanedData.push(rowData);
            }
        });

        if (cleanedData.length === 0) {
            Swal.fire('Lỗi', 'Không tìm thấy dữ liệu nguyên liệu hợp lệ trong bảng.', 'error');
            return;
        }

        let currentSTT = null;
        let lastBOMRow = null;

        cleanedData.forEach(rowData => {
            const stt = rowData[0] ? rowData[0].toString().trim() : '';
            const code = rowData[1] ? rowData[1].toString().trim() : '';
            const name = rowData[2] ? rowData[2].toString().trim() : '';

            let action = 'CREATE_ROW';

            if (lastBOMRow && (stt === currentSTT || stt === '')) {
                let lastCode = '';
                const lastCodeTextarea = lastBOMRow.find('textarea[name$="[code]"]').last();
                if (lastCodeTextarea.length > 0) {
                    lastCode = lastCodeTextarea.val().trim();
                }

                if (code !== '' && code === lastCode) {
                    action = 'UPDATE_MATERIAL';
                } else {
                    action = 'ADD_MATERIAL';
                }
            }

            if (action === 'CREATE_ROW') {
                addBOMRow(type, targetTableId, null);
                lastBOMRow = $(`#${targetTableId} .bom-row`).last();
                currentSTT = stt || currentSTT;

                if (code) lastBOMRow.find('textarea[name$="[code]"]').val(code);
                if (name) lastBOMRow.find('textarea[name$="[name]"]').val(name);

                if (rowData[3]) {
                    const funcText = rowData[3].trim();
                    let matchedRole = funcText;
                    materialRoles.forEach(r => {
                        if (r.name.toLowerCase() === funcText.toLowerCase() || funcText
                            .toLowerCase().includes(r.name.toLowerCase()) || r.name.toLowerCase()
                            .includes(funcText.toLowerCase())) {
                            matchedRole = r.name;
                        }
                    });
                    lastBOMRow.find('textarea[name$="[role]"]').val(matchedRole);
                }

                if (rowData[4]) lastBOMRow.find('textarea[name$="[manufacturer]"]').val(rowData[4].trim());

                if (rowData[5]) {
                    const specText = rowData[5].toLowerCase();
                    lastBOMRow.find('select[name$="[Spec]"] option').each(function() {
                        if ($(this).text().toLowerCase().includes(specText) || specText.includes($(
                                this).text().toLowerCase())) {
                            $(this).prop('selected', true);
                        }
                    });
                }

                if (rowData[6]) {
                    let numStr = rowData[6].replace(/,/g, '.').replace(/[^0-9.]/g, '');
                    if (numStr) {
                        lastBOMRow.find('input[name$="[total_amount_per_unit]"]').val(numStr).trigger(
                            'input');
                    }
                }

                if (rowData[8]) {
                    const batchStr = rowData[8].replace(/,/g, '.');
                    let match = batchStr.match(/([\d.]+)\s*[xX*]\s*(\d+)/);
                    if (match) {
                        let amountsOfLots = parseFloat(match[1]);
                        let numberOfLots = parseInt(match[2]);
                        if (!isNaN(amountsOfLots) && !isNaN(numberOfLots)) {
                            lastBOMRow.find('input[name$="[number_of_lots]"]').val(numberOfLots);
                            lastBOMRow.find('input[name$="[amounts_of_lots]"]').val(amountsOfLots);
                            if (numberOfLots > 1) {
                                lastBOMRow.find('.has-split-batches-cb').prop('checked', true).trigger('change');
                            }
                        }
                    }
                }
            } else if (action === 'UPDATE_MATERIAL') {
                const targetRole = lastBOMRow.find('textarea[name$="[role]"]');
                if (rowData[3] && !targetRole.val()) {
                    const funcText = rowData[3].trim();
                    let matchedRole = funcText;
                    materialRoles.forEach(r => {
                        if (r.name.toLowerCase() === funcText.toLowerCase() || funcText
                            .toLowerCase().includes(r.name.toLowerCase()) || r.name.toLowerCase()
                            .includes(funcText.toLowerCase())) {
                            matchedRole = r.name;
                        }
                    });
                    targetRole.val(matchedRole);
                }

                const targetManuf = lastBOMRow.find('textarea[name$="[manufacturer]"]').last();
                if (rowData[4] && !targetManuf.val()) targetManuf.val(rowData[4].trim());

                const targetSpec = lastBOMRow.find('select[name$="[Spec]"]').last();
                if (rowData[5] && !targetSpec.val()) {
                    const specText = rowData[5].toLowerCase();
                    targetSpec.find('option').each(function() {
                        if ($(this).text().toLowerCase().includes(specText) || specText.includes($(
                                this).text().toLowerCase())) {
                            $(this).prop('selected', true);
                        }
                    });
                }

                const targetAmount = lastBOMRow.find('input[name$="[total_amount_per_unit]"]');
                if (rowData[6] && !targetAmount.val()) {
                    let numStr = rowData[6].replace(/,/g, '.').replace(/[^0-9.]/g, '');
                    if (numStr) {
                        targetAmount.val(numStr).trigger('input');
                    }
                }
            } else if (action === 'ADD_MATERIAL') {
                lastBOMRow.find('.btn_add_material').first().trigger('click');

                let newMatIndex = 0;
                lastBOMRow.find('.materials-col-code .material-group').each(function() {
                    const idx = parseInt($(this).data('mat-index'));
                    if (idx > newMatIndex) newMatIndex = idx;
                });

                if (code) lastBOMRow.find(`textarea[name$="[materials][${newMatIndex}][code]"]`).val(code);
                if (name) lastBOMRow.find(`textarea[name$="[materials][${newMatIndex}][name]"]`).val(name);
                if (rowData[4]) lastBOMRow.find(
                    `textarea[name$="[materials][${newMatIndex}][manufacturer]"]`).val(rowData[4]
                    .trim());

                if (rowData[5]) {
                    const specText = rowData[5].toLowerCase();
                    lastBOMRow.find(`select[name$="[materials][${newMatIndex}][Spec]"] option`).each(
                        function() {
                            if ($(this).text().toLowerCase().includes(specText) || specText.includes($(
                                    this).text().toLowerCase())) {
                                $(this).prop('selected', true);
                            }
                        });
                }
            }
        });

        updateBOMSTT();
        checkTableSum('type_' + type);

        setTimeout(function() {
            $('.auto-resize').each(function() {
                this.style.height = 'auto';
                if (this.scrollHeight > 0) {
                    this.style.height = (this.scrollHeight) + 'px';
                }
            });
        }, 100);

        $('#importBomModal').modal('hide');
        Swal.fire('Thành công', `Đã nhập ${data.length} dòng công thức!`, 'success');
    };

    // --- Cell-level Note Logic ---
    $(document).on('click', '.btn_cell_note', function(e) {
        e.stopPropagation();
        const container = $(this).closest('.cell-note-container');
        const popover = container.find('.cell-note-popover');
        
        // Hide all other popovers
        $('.cell-note-popover').not(popover).addClass('d-none');
        
        // Toggle this one
        popover.toggleClass('d-none');
        if (!popover.hasClass('d-none')) {
            popover.find('textarea').focus();
        }
    });

    $(document).on('click', '.btn_close_cell_note', function(e) {
        e.stopPropagation();
        const container = $(this).closest('.cell-note-container');
        const popover = container.find('.cell-note-popover');
        
        popover.addClass('d-none');
        updateBOMNotes();
    });

    // Close popovers when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.cell-note-popover, .btn_cell_note').length) {
            $('.cell-note-popover').addClass('d-none');
        }
    });
</script>
