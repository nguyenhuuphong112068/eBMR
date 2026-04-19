@extends('layout.master')

@section('title', 'Soạn hồ sơ eBMR (Document Mode)')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection

@section('mainContent')
    <div class="content-wrapper" style="background-color: #f1f3f4; min-height: 100vh;">
        <div class="container-fluid py-4">
            <div class="row justify-content-center">
                <div class="col-lg-9">
                    <!-- Header Control -->
                    <div class="card shadow-sm border-0 mb-3" style="border-radius: 12px;">
                        <div class="card-body d-flex justify-content-between align-items-center py-2">
                            <div class="d-flex align-items-center gap-3">
                                <i class="fas fa-file-alt text-primary fs-4"></i>
                                <select id="templateSelector" class="form-select border-0 fw-bold"
                                    style="width: 400px; box-shadow: none;">
                                    <option value="">-- Chọn loại hồ sơ --</option>
                                    @foreach ($templates as $template)
                                        <option value="{{ $template->id }}" data-schema='@json($template->schema)'>
                                            {{ $template->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <button class="btn btn-navy px-4" id="btnSave" style="border-radius: 20px;">
                                <i class="fas fa-check-circle me-2"></i> LƯU & HOÀN TẤT
                            </button>
                        </div>
                    </div>

                    <!-- Paper Container -->
                    <div id="noTemplate" class="text-center py-5">
                        <img src="https://cdni.iconscout.com/illustration/premium/thumb/empty-state-2130362-1800532.png"
                            style="width: 200px; opacity: 0.5;">
                        <p class="text-muted mt-3">Chọn hồ sơ để bắt đầu nhập liệu</p>
                    </div>

                    <div class="page-a4 shadow d-none" id="document-page">
                        <form id="ebmrForm" class="p-5">
                            <input type="hidden" name="template_id" id="template_id">
                            <div id="dynamic-content">
                                <!-- Sequential content goes here -->
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .page-a4 {
            background: white;
            min-height: 1100px;
            width: 100%;
            border-radius: 2px;
            margin: 0 auto;
            position: relative;
        }

        .btn-navy {
            background: #003A4F;
            color: white;
            font-weight: 600;
            transition: 0.3s;
        }

        .btn-navy:hover {
            background: #002a3a;
            transform: translateY(-1px);
        }

        .record-block {
            margin-bottom: 25px;
        }

        .record-label {
            display: block;
            font-weight: 700;
            color: #5f6368;
            font-size: 0.85rem;
            text-transform: uppercase;
            margin-bottom: 8px;
            border-left: 3px solid #003A4F;
            padding-left: 10px;
        }

        .record-input {
            width: 100%;
            border: 1px solid #dadce0;
            padding: 10px;
            border-radius: 4px;
            font-size: 1rem;
            background: #fcfcfc;
        }

        .record-input:focus {
            border-color: #1a73e8;
            outline: none;
            background: white;
            box-shadow: 0 0 0 2px rgba(26, 115, 232, 0.1);
        }

        .record-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .record-table th {
            background: #f8f9fa;
            border: 1px solid #dadce0;
            padding: 8px;
            font-size: 0.85rem;
            text-align: center;
            color: #3c4043;
        }

        .record-table td {
            border: 1px solid #dadce0;
            padding: 0;
        }

        .record-table input {
            width: 100%;
            border: none;
            padding: 10px;
            background: transparent;
        }

        .record-table input:focus {
            background: #fffbeb;
            outline: none;
        }

        .add-row-btn {
            font-size: 0.8rem;
            color: #1a73e8;
            cursor: pointer;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .add-row-btn:hover {
            text-decoration: underline;
        }

        .sig-box {
            border: 2px dashed #dadce0;
            background: #f8f9fa;
            border-radius: 6px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
        }

        .sig-box:hover {
            border-color: #1a73e8;
            background: #f1f3f4;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const selector = document.getElementById('templateSelector');
            const page = document.getElementById('document-page');
            const content = document.getElementById('dynamic-content');
            const noTemplate = document.getElementById('noTemplate');
            const templateIdInput = document.getElementById('template_id');

            selector.onchange = function() {
                if (!this.value) {
                    page.classList.add('d-none');
                    noTemplate.classList.remove('d-none');
                    return;
                }

                const schema = JSON.parse(this.options[this.selectedIndex].getAttribute('data-schema'));
                templateIdInput.value = this.value;
                renderDocument(schema);

                page.classList.remove('d-none');
                noTemplate.classList.add('d-none');
            };

            function renderDocument(schema) {
                content.innerHTML = '';
                const fields = schema.fields || [];

                fields.forEach(f => {
                    const block = document.createElement('div');
                    block.className = 'record-block';
                    block.innerHTML = `<label class="record-label">${f.label}</label>`;

                    if (f.type === 'table') {
                        const table = document.createElement('table');
                        table.className = 'record-table';
                        table.id = `tbl_${f.id}`;

                        let header = '<tr>';
                        f.columns.forEach(c => header += `<th>${c.label}</th>`);
                        header += '</tr>';
                        table.innerHTML = header;

                        addTableRow(table, f.columns); // Initial row

                        const addBtn = document.createElement('div');
                        addBtn.className = 'add-row-btn mt-2';
                        addBtn.innerHTML = '<i class="fas fa-plus-circle"></i> THÊM DÒNG MỚI';
                        addBtn.onclick = () => addTableRow(table, f.columns);

                        block.appendChild(table);
                        block.appendChild(addBtn);
                    } else if (f.type === 'signature') {
                        block.innerHTML += `
                    <div class="sig-box" onclick="sign(this)">
                        <div class="sig-placeholder text-muted"><i class="fas fa-pen-nib me-2"></i>Nhấp vào đây để ký xác nhận điện tử</div>
                        <input type="hidden" name="${f.id}" class="sig-input">
                    </div>
                `;
                    } else {
                        block.innerHTML +=
                            `<input type="${f.type}" name="${f.id}" class="record-input" placeholder="Nhập nội dung...">`;
                    }

                    content.appendChild(block);
                });
            }

            function addTableRow(table, columns) {
                const row = table.insertRow();
                columns.forEach(c => {
                    const cell = row.insertCell();
                    cell.innerHTML =
                        `<input type="${c.type === 'number' ? 'number' : 'text'}" name="tbl_val[]" placeholder="...">`;
                });
            }

            window.sign = function(el) {
                Swal.fire({
                    title: 'Ký hồ sơ điện tử',
                    text: 'Bằng việc nhấn "Ký tên", bạn xác nhận dữ liệu trên là chính xác.',
                    icon: 'info',
                    showCancelButton: true,
                    confirmButtonColor: '#003A4F',
                    confirmButtonText: 'Ký tên'
                }).then((result) => {
                    if (result.isConfirmed) {
                        el.innerHTML =
                            `<div class="p-2"><i class="fas fa-check-circle text-success fs-4 mb-2"></i><br><b>ĐÃ KÝ XÁC NHẬN</b><br><small class="text-muted">${new Date().toLocaleString()}</small></div>`;
                        el.onclick = null;
                        el.style.borderColor = '#1a73e8';
                        el.style.background = '#fff';
                    }
                });
            };

            document.getElementById('btnSave').onclick = function() {
                Swal.fire({
                    icon: 'success',
                    title: 'Tuyệt vời!',
                    text: 'Hồ sơ điện tử đã được lưu trữ thành công theo chuẩn Google Docs!',
                    confirmButtonColor: '#003A4F'
                });
            };
        });
    </script>
    </div>
@endsection
