import re

with open(r'd:\LEMP\eBMR\resources\views\pages\category\intermediate\dataTable.blade.php', 'r', encoding='utf-8') as f:
    lines = f.readlines()

new_lines = []
skip = False
for i, line in enumerate(lines):
    if "window.renderBOMRows = function(" in line:
        skip = True
    
    if skip and "</script>" in line:
        skip = False
        new_lines.append(line)
        continue
        
    if not skip:
        new_lines.append(line)

with open(r'd:\LEMP\eBMR\resources\views\pages\category\intermediate\dataTable.blade.php', 'w', encoding='utf-8') as f:
    f.writelines(new_lines)
