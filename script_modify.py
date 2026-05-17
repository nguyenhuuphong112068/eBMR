import re

with open(r'd:\LEMP\eBMR\resources\views\pages\category\intermediate\dataTable.blade.php', 'r', encoding='utf-8') as f:
    content = f.read()

new_btn_edit = '''$('.btn-edit').click(function() {
                    const button = $(this);
                    const modal = $('#update_modal');

                    modal.find('input[name="id"]').val(button.data('id'));
                    modal.find('input[name="intermediate_code"]').val(button.data('intermediate_code'));
                    modal.find('select[name="product_name_id"]').val(button.data('product_name_id'));
                    modal.find('input[name="batch_size"]').val(button.data('batch_size'));
                    modal.find('select[name="unit_batch_size"]').val(button.data('unit_batch_size'));
                    modal.find('input[name="batch_qty"]').val(button.data('batch_qty'));
                    modal.find('select[name="unit_batch_qty"]').val(button.data('unit_batch_qty'));

                    const switchInput = modal.find('input[name="quarantine_time_unit"]');
                    const state = button.data('quarantine_time_unit') == 1;
                    if (typeof $.fn.bootstrapSwitch === 'function') {
                        switchInput.bootstrapSwitch('state', state);
                    } else {
                        switchInput.prop('checked', state);
                    }
                });'''

content = re.sub(r"\$\('\.btn-edit'\)\.click\(function\(\)\s*\{.*?\n\s*\}\);\s*window\.renderBOMRows\s*=\s*function\(formulas,\s*isUpdate\s*=\s*false\).*?(?=\$\('\.btn-edit-hypothesis'\)\.click)", new_btn_edit + '\n\n                ', content, flags=re.DOTALL)

new_btn_edit_hypo = '''$('.btn-edit-hypothesis').click(function() {
                    const button = $(this);
                    const modal = $('#update_hypothesis_modal');

                    modal.find('input[name="id"]').val(button.data('id'));
                    modal.find('input[name="intermediate_code"]').val(button.data('intermediate_code'));
                    modal.find('select[name="product_name_id"]').val(button.data('product_name_id'));
                    modal.find('input[name="batch_size"]').val(button.data('batch_size'));
                    modal.find('select[name="unit_batch_size"]').val(button.data('unit_batch_size'));
                    modal.find('input[name="batch_qty"]').val(button.data('batch_qty'));
                    modal.find('select[name="unit_batch_qty"]').val(button.data('unit_batch_qty'));
                });'''

content = re.sub(r"\$\('\.btn-edit-hypothesis'\)\.click\(function\(\)\s*\{.*?\n\s*\}\);", new_btn_edit_hypo, content, flags=re.DOTALL)

# Delete from `// Sync Summernote div content` to the end of BOM logic before `</script>`
content = re.sub(r"// Sync Summernote div content.*?\}\);(?=\s*</script>)", "", content, flags=re.DOTALL)

with open(r'd:\LEMP\eBMR\resources\views\pages\category\intermediate\dataTable.blade.php', 'w', encoding='utf-8') as f:
    f.write(content)
