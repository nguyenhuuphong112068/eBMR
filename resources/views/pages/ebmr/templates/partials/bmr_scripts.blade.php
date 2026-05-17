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
                        <td class="align-middle"><textarea class="form-control auto-resize" name="bom[${bomRowIndex}][code]" placeholder="Mã NL" rows="1">${code}</textarea></td>
                        <td class="align-middle"><textarea class="form-control auto-resize" name="bom[${bomRowIndex}][name]" placeholder="Thành phần" rows="1">${name}</textarea></td>
                        <td class="align-middle">
                            <select class="form-control custom-select" name="bom[${bomRowIndex}][role]">
                                ${roleOptions}
                            </select>
                        </td>
                        <td class="align-middle"><textarea class="form-control auto-resize" name="bom[${bomRowIndex}][manufacturer]" placeholder="Nhà SX" rows="1"></textarea></td>
                        <td class="align-middle">
                            <select class="form-control custom-select" name="bom[${bomRowIndex}][Spec]">
                                ${specOptions}
                            </select>
                        </td>
                        <td class="align-middle">
                            <div class="d-flex align-items-center mb-1">
                                <input type="number" step="any" class="form-control" name="bom[${bomRowIndex}][total_amount_per_unit]" placeholder="Tổng" value="${qty}">
                                <input type="text" class="form-control ms-1 text-center px-1" name="bom[${bomRowIndex}][uom]" placeholder="ĐV" style="width: 50px;" value="${uom}">
                                <button type="button" class="btn btn-xs btn-outline-info ms-1 btn_add_sub_amount" title="Chia phần"><i class="fa fa-plus"></i></button>
                            </div>
                            <div class="sub-amounts-container"></div>
                        </td>
                        <td class="align-middle"><input type="number" step="any" class="form-control" name="bom[${bomRowIndex}][total_amount_per_batch]" placeholder="1 lô"></td>
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
                const subIndex = container.find('.sub-amount-item').length;

                const subHtml = `
                    <div class="sub-amount-item d-flex align-items-center mt-1">
                        <input type="number" step="any" class="form-control form-control-sm py-0" 
                            name="bom[${rowIndex}][sub_amounts][${subIndex}][amount_per_unit]" 
                            placeholder="Lượng" style="height: 22px; font-size: 0.7rem; width: 80px;">
                        <textarea class="form-control form-control-sm py-0 ms-1 auto-resize" 
                            name="bom[${rowIndex}][sub_amounts][${subIndex}][note]" 
                            placeholder="Ghi chú" rows="1" style="min-height: 22px; font-size: 0.7rem;"></textarea>
                        <button type="button" class="btn btn-xs btn-link text-danger p-0 ms-1 btn_remove_sub_amount"><i class="fa fa-times"></i></button>
                    </div>
                `;
                container.append(subHtml);
            });

            // Auto-resize logic
            $(document).on('input', '.auto-resize', function() {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
            });

            $(document).on('click', '.btn_remove_sub_amount', function() {
                $(this).closest('.sub-amount-item').remove();
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
</script>