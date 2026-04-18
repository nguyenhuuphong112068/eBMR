@extends('layout.master')

@section('title', 'eBMR Editor (Document Style)')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection

@section('mainContent')
<div class="content-wrapper" style="background-color: #f1f3f4; min-height: 100vh;">
    <!-- Google Docs Style Toolbar -->
    <div class="editor-toolbar shadow-sm d-flex align-items-center px-4 py-2 bg-white">
        <div class="d-flex align-items-center border-end pe-3 me-3 gap-1">
            <button class="btn btn-toolbar" onclick="undo()" title="Undo"><i class="fas fa-undo"></i></button>
            <button class="btn btn-toolbar" onclick="redo()" title="Redo"><i class="fas fa-redo"></i></button>
            <button class="btn btn-toolbar" onclick="window.print()" title="Print"><i class="fas fa-print"></i></button>
        </div>

        <div class="d-flex align-items-center border-end pe-3 me-3 gap-2">
            <span class="small fw-bold text-muted me-2">CHÈN:</span>
            <button class="btn btn-toolbar-action" onclick="addItem('text')"><i class="fas fa-align-left me-1"></i> Text</button>
            <button class="btn btn-toolbar-action" onclick="addItem('number')"><i class="fas fa-hashtag me-1"></i> Number</button>
            <button class="btn btn-toolbar-action" onclick="addItem('table')"><i class="fas fa-table me-1"></i> Bảng</button>
            <button class="btn btn-toolbar-action" onclick="addItem('signature')"><i class="fas fa-signature me-1"></i> Chữ ký</button>
        </div>

        <div class="ms-auto">
            <button class="btn btn-navy px-4" onclick="saveTemplate()" style="border-radius: 20px;">
                <i class="fas fa-cloud-upload-alt me-2"></i> LƯU HỒ SƠ MẪU
            </button>
        </div>
    </div>

    <div class="container-fluid py-4">
        <div class="row justify-content-center">
            <!-- Document Canvas -->
            <div class="col-lg-8">
                <div class="doc-header mb-3">
                    <input type="text" id="templateName" class="form-control doc-title-input" placeholder="Tài liệu không có tiêu đề">
                </div>
                
                <div class="page-a4 shadow" id="document-page">
                    <div id="editor-content" class="p-5">
                        <!-- Elements flow here like a real document -->
                         <div id="drop-hint" class="text-center py-5 opacity-25">
                            <i class="fas fa-plus-circle fa-3x mb-3"></i>
                            <h4>Bắt đầu thiết kế hồ sơ</h4>
                            <p>Chọn các linh kiện từ thanh công cụ bên trên</p>
                         </div>
                    </div>
                </div>
            </div>

            <!-- Property Panel (Floating/Fixed) -->
            <div class="col-lg-3">
                <div id="property-panel" class="card border-0 shadow-sm d-none" style="border-radius: 12px; position: sticky; top: 100px;">
                    <div class="card-header bg-light border-0 py-3">
                        <h6 class="mb-0 fw-bold text-navy"><i class="fas fa-cog me-2"></i> CÀI ĐẶT Ô NHẬP</h6>
                    </div>
                    <div class="card-body" id="prop-body">
                        <!-- Dynamic Props -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .editor-toolbar { position: sticky; top: 0; z-index: 1000; border-bottom: 1px solid #ddd; }
    .btn-toolbar { width: 34px; height: 34px; padding: 0; display: flex; align-items: center; justify-content: center; border: none; background: transparent; border-radius: 4px; color: #444; }
    .btn-toolbar:hover { background: #f1f3f4; }
    .btn-toolbar-action { padding: 4px 12px; border: 1px solid transparent; background: transparent; border-radius: 4px; font-size: 0.85rem; font-weight: 500; color: #444; }
    .btn-toolbar-action:hover { background: #e8f0fe; color: #1a73e8; }
    
    .doc-title-input { background: transparent; border: 1px solid transparent; font-size: 1.25rem; font-weight: 500; padding: 5px 10px; border-radius: 4px; transition: 0.2s; }
    .doc-title-input:hover { background: #fff; border-color: #ddd; }
    .doc-title-input:focus { background: #fff; border-color: #1a73e8; outline: none; box-shadow: 0 0 0 2px rgba(26,115,232,0.1); }

    .page-a4 { background: white; min-height: 1100px; width: 100%; border-radius: 2px; margin: 0 auto; position: relative; }
    
    .btn-navy { background: #003A4F; color: white; transition: 0.3s; font-weight: 600; }
    .btn-navy:hover { background: #002a3a; box-shadow: 0 4px 12px rgba(0,58,79,0.2); }
    .text-navy { color: #003A4F; }

    /* Component Styling in Doc Mode */
    .block-item { position: relative; padding: 10px; border: 1px solid transparent; border-radius: 4px; margin-bottom: 5px; cursor: pointer; transition: 0.2s; }
    .block-item:hover { border-color: #1a73e8; background: #f8faff; }
    .block-item.active { border-color: #1a73e8; box-shadow: 0 0 0 1px #1a73e8; }
    
    .block-label { font-weight: bold; color: #5f6368; font-size: 0.85rem; margin-bottom: 5px; display: block; }
    .block-mock { min-height: 40px; background: #fdfdfd; border: 1px solid #dadce0; border-radius: 4px; border-left: 4px solid #003A4F; }
    
    .block-actions { position: absolute; right: -40px; top: 10px; display: flex; flex-direction: column; gap: 5px; opacity: 0; transition: 0.2s; }
    .block-item:hover .block-actions { opacity: 1; right: -45px; }

    .mini-table { width: 100%; border-collapse: collapse; }
    .mini-table th { background: #f8f9fa; border: 1px solid #dadce0; padding: 5px; font-size: 0.75rem; text-align: center; }
    .mini-table td { border: 1px solid #dadce0; padding: 10px; text-align: center; color: #ccc; }
</style>

<script>
let items = [];
let selectedId = null;

function addItem(type) {
    document.getElementById('drop-hint').classList.add('d-none');
    const id = 'blk_' + Date.now();
    const item = {
        id: id, type: type, label: 'Tiêu đề ' + type,
        columns: type === 'table' ? [{label: 'Cột 1', type: 'text'}, {label: 'Cột 2', type: 'number'}] : []
    };
    items.push(item);
    renderBlocks();
    selectItem(id);
}

function renderBlocks() {
    const container = document.getElementById('editor-content');
    const hint = document.getElementById('drop-hint');
    container.innerHTML = '';
    container.appendChild(hint);

    items.forEach((item, idx) => {
        const div = document.createElement('div');
        div.className = `block-item ${selectedId === item.id ? 'active' : ''}`;
        div.onclick = (e) => { e.stopPropagation(); selectItem(item.id); };

        let content = `<div class="block-mock"></div>`;
        if (item.type === 'table') {
            content = `
                <table class="mini-table">
                    <thead><tr>${item.columns.map(c => `<th>${c.label}</th>`).join('')}</tr></thead>
                    <tbody><tr>${item.columns.map(() => `<td>...</td>`).join('')}</tr></tbody>
                </table>
            `;
        } else if (item.type === 'signature') {
            content = `<div class="block-mock d-flex align-items-center justify-content-center text-muted"><i class="fas fa-pen-nib me-2"></i>Khu vực ký xác nhận</div>`;
        }

        div.innerHTML = `
            <div class="block-actions">
                <button class="btn btn-sm btn-light border shadow-sm text-danger" onclick="removeItem('${item.id}')"><i class="fas fa-trash"></i></button>
                <button class="btn btn-sm btn-light border shadow-sm" onclick="moveItem(${idx}, -1)"><i class="fas fa-chevron-up"></i></button>
                <button class="btn btn-sm btn-light border shadow-sm" onclick="moveItem(${idx}, 1)"><i class="fas fa-chevron-down"></i></button>
            </div>
            <span class="block-label">${item.label}</span>
            ${content}
        `;
        container.appendChild(div);
    });
}

function selectItem(id) {
    selectedId = id;
    renderBlocks();
    const item = items.find(i => i.id === id);
    const panel = document.getElementById('property-panel');
    const body = document.getElementById('prop-body');
    panel.classList.remove('d-none');

    let html = `
        <div class="mb-3">
            <label class="small fw-bold">Nhãn hiển thị</label>
            <input type="text" class="form-control" value="${item.label}" oninput="updateItemProp('label', this.value)">
        </div>
    `;

    if (item.type === 'table') {
        html += `<label class="small fw-bold mb-2">Quản lý Cột</label>`;
        item.columns.forEach((col, cIdx) => {
            html += `
                <div class="input-group input-group-sm mb-1">
                    <input type="text" class="form-control" value="${col.label}" oninput="updateCol(${cIdx}, 'label', this.value)">
                    <button class="btn btn-outline-danger" onclick="removeCol(${cIdx})"><i class="fas fa-times"></i></button>
                </div>
            `;
        });
        html += `<button class="btn btn-sm btn-outline-primary w-100 mt-2" onclick="addCol()"><i class="fas fa-plus me-1"></i>Thêm cột</button>`;
    }

    body.innerHTML = html;
}

function updateItemProp(prop, val) {
    const item = items.find(i => i.id === selectedId);
    item[prop] = val;
    renderBlocks();
}

function updateCol(cIdx, prop, val) {
    const item = items.find(i => i.id === selectedId);
    item.columns[cIdx][prop] = val;
    renderBlocks();
}

function addCol() {
    const item = items.find(i => i.id === selectedId);
    item.columns.push({label: 'Cột mới', type: 'text'});
    selectItem(selectedId);
}

function removeCol(cIdx) {
    const item = items.find(i => i.id === selectedId);
    item.columns.splice(cIdx, 1);
    selectItem(selectedId);
}

function removeItem(id) {
    items = items.filter(i => i.id !== id);
    selectedId = null;
    document.getElementById('property-panel').classList.add('d-none');
    renderBlocks();
}

function moveItem(idx, dir) {
    const newIdx = idx + dir;
    if (newIdx < 0 || newIdx >= items.length) return;
    const temp = items[idx];
    items[idx] = items[newIdx];
    items[newIdx] = temp;
    renderBlocks();
}

function saveTemplate() {
    const name = document.getElementById('templateName').value || 'Hồ sơ không tên';
    const schema = {
        type: 'document-flow',
        fields: items.map(i => ({
            id: i.id, type: i.type, label: i.label, columns: i.columns || []
        }))
    };

    fetch('{{ route("pages.ebmr.storeTemplate") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ name: name, schema: schema, _token: '{{ csrf_token() }}' })
    })
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            Swal.fire('Thành công', 'Đã lưu hồ sơ mẫu dạng văn bản!', 'success').then(() => {
                window.location.href = '{{ route("pages.ebmr.draft") }}';
            });
        }
    });
}

// Click outside to deselect
document.getElementById('mainContent').onclick = (e) => {
    if (!e.target.closest('.block-item') && !e.target.closest('#property-panel') && !e.target.closest('.editor-toolbar')) {
        selectedId = null;
        renderBlocks();
        document.getElementById('property-panel').classList.add('d-none');
    }
};
</script>
@endsection
