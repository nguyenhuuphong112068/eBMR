<script>
            // Master Data for BOM
            const materialRoles = @json($materialRoles ?? []);
            const materialSpecs = @json($materialSpecs ?? []);
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
                    if($('#create_description_editor').length) {
                        $('#create_description_input').val($('#create_description_editor').summernote('code'));
                    }
                    if($('#create_storage_conditions_editor').length) {
                        $('#create_storage_conditions_input').val($('#create_storage_conditions_editor').summernote('code'));
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

                let roleOptions = '<option value="">-Chọn-</option>';
                materialRoles.forEach(role => {
                    roleOptions += `<option value="${role.name}">${role.name}</option>`;
                });

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
                            <select class="form-control custom-select" name="bom[${bomRowIndex}][role]">
                                ${roleOptions}
                            </select>
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
                                <input type="number" step="any" class="form-control" name="bom[${bomRowIndex}][total_amount_per_unit]" placeholder="Tổng" value="${qty}">
                                <input type="text" class="form-control ms-1 text-center px-1" name="bom[${bomRowIndex}][uom]" placeholder="ĐV" style="width: 50px;" value="${uom}">
                                <button type="button" class="btn btn-xs btn-outline-info ms-1 btn_add_sub_amount" title="Chia phần"><i class="fa fa-plus"></i></button>
                            </div>
                            <div class="sub-amounts-container"></div>
                        </td>
                        <td class="align-middle"><input type="number" step="any" class="form-control ratio-display text-center text-success fw-bold" placeholder="%" readonly style="background-color: transparent;"></td>
                        <td class="align-middle">
                            <div class="d-flex align-items-center mb-1">
                                <input type="number" step="any" class="form-control" name="bom[${bomRowIndex}][total_amount_per_batch]" placeholder="1 lô" readonly style="background-color: transparent;">
                            </div>
                            <div class="sub-amounts-batch-container"></div>
                        </td>
                        <td class="text-center align-middle">
                            <button type="button" class="btn btn-xs btn-danger btn_remove_bom_row"><i class="fa fa-trash"></i></button>
                        </td>
                    </tr>
                `;
                $(`#${targetTableId}`).append(tr);
                bomRowIndex++;
                updateBOMSTT();
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

                $.get('{{ route("pages.ebmr.getMaterialInfo") }}', { code: code }, function(res) {
                    if (res.success) {
                        const nameInput = row.find(`.materials-col-name .material-group[data-mat-index="${matIndex}"] textarea`);
                        const manufInput = row.find(`.materials-col-manufacturer .material-group[data-mat-index="${matIndex}"] textarea`);
                        
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
                
                bomData.forEach((formula, formulaIdx) => {
                    const targetTableId = formula.type == 0 ? 'bom_table_body_type_0' : 'bom_table_body_type_1';
                    
                    let roleOptions = '<option value="">-Chọn-</option>';
                    materialRoles.forEach(role => {
                        roleOptions += `<option value="${role.name}" ${formula.role == role.name ? 'selected' : ''}>${role.name}</option>`;
                    });

                    let materialsCodeHtml = '';
                    let materialsNameHtml = '';
                    let materialsManufHtml = '';
                    let materialsSpecHtml = '';

                    const mats = formula.materials && formula.materials.length > 0 
                        ? formula.materials 
                        : [{code: formula.code || '', name: formula.name || '', manufacturer: formula.manufacturer || '', Spec: formula.Spec || ''}];

                    mats.forEach((mat, mIdx) => {
                        let specOptionsHtml = '<option value="">-Chọn-</option>';
                        materialSpecs.forEach(spec => {
                            specOptionsHtml += `<option value="${spec.name}" ${mat.Spec == spec.name ? 'selected' : ''}>${spec.name}</option>`;
                        });

                        materialsCodeHtml += `
                            <div class="material-group mt-1" data-mat-index="${mIdx}">
                                <textarea class="form-control auto-resize" name="bom[${bomRowIndex}][materials][${mIdx}][code]" placeholder="Mã NL" rows="1">${mat.code || ''}</textarea>
                            </div>
                        `;
                        materialsNameHtml += `
                            <div class="material-group mt-1" data-mat-index="${mIdx}">
                                <textarea class="form-control auto-resize" name="bom[${bomRowIndex}][materials][${mIdx}][name]" placeholder="Thành phần" rows="2">${mat.name || ''}</textarea>
                            </div>
                        `;
                        materialsManufHtml += `
                            <div class="material-group mt-1" data-mat-index="${mIdx}">
                                <textarea class="form-control auto-resize" name="bom[${bomRowIndex}][materials][${mIdx}][manufacturer]" placeholder="Nhà SX" rows="1">${mat.manufacturer || ''}</textarea>
                            </div>
                        `;
                        materialsSpecHtml += `
                            <div class="material-group mt-1 d-flex align-items-start position-relative" data-mat-index="${mIdx}">
                                <select class="form-control custom-select" name="bom[${bomRowIndex}][materials][${mIdx}][Spec]">
                                    ${specOptionsHtml}
                                </select>
                                ${mIdx === 0 
                                    ? `<button type="button" class="btn btn-xs btn-outline-primary ms-1 mt-1 btn_add_material" title="Thêm mã nguyên liệu"><i class="fa fa-plus"></i></button>` 
                                    : `<button type="button" class="btn btn-xs btn-outline-danger ms-1 mt-1 btn_remove_material" title="Xóa mã NL"><i class="fa fa-times"></i></button>`}
                            </div>
                        `;
                    });

                    let tr = `
                        <tr class="bom-row" data-index="${bomRowIndex}">
                            <td class="text-center align-middle stt-col" style="font-weight:bold;"></td>
                            <input type="hidden" name="bom[${bomRowIndex}][type]" value="${formula.type}">
                            <td class="align-middle p-1">
                                <div class="materials-col-code">${materialsCodeHtml}</div>
                            </td>
                            <td class="align-middle p-1">
                                <div class="materials-col-name">${materialsNameHtml}</div>
                            </td>
                            <td class="align-middle">
                                <select class="form-control custom-select" name="bom[${bomRowIndex}][role]">
                                    ${roleOptions}
                                </select>
                            </td>
                            <td class="align-middle p-1">
                                <div class="materials-col-manufacturer">${materialsManufHtml}</div>
                            </td>
                            <td class="align-middle p-1">
                                <div class="materials-col-spec">${materialsSpecHtml}</div>
                            </td>
                            <td class="align-middle">
                                <div class="d-flex align-items-center mb-1">
                                    <input type="number" step="any" class="form-control" name="bom[${bomRowIndex}][total_amount_per_unit]" placeholder="Tổng" value="${formula.total_amount_per_unit || ''}">
                                    <input type="text" class="form-control ms-1 text-center px-1" name="bom[${bomRowIndex}][uom]" placeholder="ĐV" style="width: 50px;" value="${formula.uom || ''}">
                                    <button type="button" class="btn btn-xs btn-outline-info ms-1 btn_add_sub_amount" title="Chia phần"><i class="fa fa-plus"></i></button>
                                </div>
                                <div class="sub-amounts-container"></div>
                            </td>
                            <td class="align-middle"><input type="number" step="any" class="form-control ratio-display text-center text-success fw-bold" placeholder="%" readonly style="background-color: transparent;"></td>
                            <td class="align-middle">
                                <div class="d-flex align-items-center mb-1">
                                    <input type="number" step="any" class="form-control" name="bom[${bomRowIndex}][total_amount_per_batch]" placeholder="1 lô" value="${formula.total_amount_per_batch || ''}" readonly style="background-color: transparent;">
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
                    $(this).find('input[name$="[amount_per_unit]"]').attr('name', `bom[${rowIndex}][sub_amounts][${subIndex}][amount_per_unit]`);
                    $(this).find('input[name$="[note]"]').attr('name', `bom[${rowIndex}][sub_amounts][${subIndex}][note]`);
                });
                
                row.find('.sub-amounts-batch-container .sub-amount-batch-item').each(function(subIndex) {
                    $(this).find('input[name$="[amount_per_batch]"]').attr('name', `bom[${rowIndex}][sub_amounts][${subIndex}][amount_per_batch]`);
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
                
                if (!isNaN(unitVal)) {
                    // 1. Calculate Ratio (%)
                    if (!isNaN(avgWeight) && avgWeight > 0) {
                        const ratio = (unitVal / avgWeight) * 100;
                        ratioInput.val(ratio.toFixed(2));
                    } else {
                        ratioInput.val('');
                    }
                    
                    // 2. Calculate Lô tiêu chuẩn (kg)
                    // Lô tiêu chuẩn = (1 viên * Cỡ Lô) / Khối lượng TB
                    if (!isNaN(avgWeight) && avgWeight > 0 && window.currentBatchSize > 0) {
                        const batchVal = (unitVal * window.currentBatchSize) / avgWeight;
                        batchInput.val(batchVal.toFixed(3));
                    } else if (window.currentBatchQty > 0) {
                        // Fallback: if no avgWeight, but we have batch_qty (e.g. from DB)
                        const batchVal = (unitVal * window.currentBatchQty) / 1000000;
                        batchInput.val(batchVal.toFixed(3));
                    }
                } else {
                    ratioInput.val('');
                    batchInput.val('');
                }

                // Calculate batch values for each sub-amount
                row.find('.sub-amounts-container .sub-amount-item').each(function(subIndex) {
                    const subUnitVal = parseFloat($(this).find('input[name$="[amount_per_unit]"]').val());
                    const subBatchInput = row.find('.sub-amounts-batch-container .sub-amount-batch-item').eq(subIndex).find('input[name$="[amount_per_batch]"]');
                    
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
                $(tbodyId).find('input[name$="[total_amount_per_unit]"]').each(function() {
                    const val = parseFloat($(this).val());
                    if (!isNaN(val)) sum += val;
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
                    
                    $(`#bom_table_body_${type} tr.bom-row`).each(function() {
                        const row = $(this);
                        let subNoteIndex = 0;
                        
                        row.find('.sub-amount-item').each(function() {
                            const noteInput = $(this).find('.sub-amount-note-input');
                            const noteVal = noteInput.val().trim();
                            
                            $(this).find('.note-superscript').remove();
                            
                            if (noteVal) {
                                const idxLabel = `${rowIndexBase}${String.fromCharCode(97 + subNoteIndex)}`; // e.g., 1a, 1b
                                $(this).find('.btn_edit_sub_note').after(`<sup class="note-superscript ms-1 text-danger fw-bold">(${idxLabel})</sup>`);
                                html += `<div><span class="text-danger fw-bold">(${idxLabel})</span> ${noteVal}</div>`;
                                subNoteIndex++;
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
</script>
