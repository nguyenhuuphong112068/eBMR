{{-- =============================================================
   VIRTUAL BLOCKS cho Designer V2 (TipTap) — TRÍCH NGUYÊN VĂN từ
   state.blade.php của V1 (injectVirtualBlocks + các generator).
   KHÔNG sửa logic ở đây; khi V1 bị gỡ bỏ thì hợp nhất lại một nơi.
   Xuất ra: window.__V2_buildVirtualBlocks() -> mảng block ảo (locked)
            window.__V2_sysLabels -> nhãn block hệ thống cần lọc bỏ
============================================================= --}}
<script>
(function () {
    const catId = "{{ $template->caterogy_id ?? 0 }}";

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
                    content: `Số SOP đối chiếu: ${t.sop}`,
                    rs: 1,
                    cs: 1,
                    textAlign: 'left',
                    fontStyle: 'italic',
                    fontSize: '1rem',
                    backgroundColor: '#dcdcdc'
                },
                {
                    content: ` Số biểu mẫu: ${t.format}-${t.version}`,
                    rs: 1,
                    cs: 1,
                    textAlign: 'right',
                    fontStyle: 'italic',
                    fontSize: '1rem',
                    backgroundColor: '#dcdcdc'
                }
            ],

            // Row 2: Main Title (UPPERCASE, Centered)
            [{
                    content: t.name,
                    rs: 1,
                    cs: 2,
                    textAlign: 'center',
                    fontSize: '1.2rem',
                    fontWeight: 'bold',
                    textTransform: 'uppercase',
                    backgroundColor: '#dcdcdc'
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
            rows: 3,
            cols: 2,
            columns: columns,
            data: data,
            rowHeights: ['auto', '5px', 'auto'],
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
            code: "{{ $template->doc_code ?? ($template->category_code ?? '') }}",
            edition: "{{ $template->version ?? '1' }}",
            pre_version: "{{ $template->pre_version ?? '' }}",
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

        const type = "{{ $template->type ?? 'BMR' }}";

        if (type === 'MF') {
            let data = [
                // Row 1: Logo and Main Title
                [{
                        content: '<img src="/img/stella-pharm.jpg" style="max-height: 40px;">',
                        rs: 1,
                        cs: 1
                    },
                    {
                        content: '<div style="font-size: 1.1rem; font-weight: bold; text-align: center;">BIỂU MẪU GỐC</div>',
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
                // Row 2: Edition & Effective Date
                [{
                        content: '<span style="font-size: 0.85rem;">Số ấn bản</span>',
                        rs: 1,
                        cs: 1
                    },
                    {
                        content: '<strong style="color: red;">: ' + String(t.edition).padStart(2, '0') +
                            '</strong>',
                        rs: 1,
                        cs: 1
                    },
                    {
                        content: '<span style="font-size: 0.85rem;">Ngày hiệu lực</span>',
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
                label: 'MF Header',
                rows: 2,
                cols: 4,
                columns: columns,
                data: data,
                rowHeights: new Array(2).fill('auto'),
                borderMode: 'visible',
                hideHeader: true,
                locked: true,
                isBmrHeader: true,
                section_id: "{{ $template->caterogy_id ?? 0 }}"
            };
        }

        let data = [
            // Row 1: Logo and Main Title
            [{
                    content: '<img src="/img/stella-pharm.jpg" style="max-height: 40px;">',
                    rs: 1,
                    cs: 1
                },
                {
                    content: '<div style="font-size: 1.1rem; font-weight: bold; text-align: center;">HỒ SƠ SẢN XUẤT GỐC</div>',
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
                    content: '<span style="font-size: 0.85rem;">Sản phẩm</span>',
                    rs: 1,
                    cs: 1
                },
                {
                    content: '<strong style="font-size: 1rem;">: ' + t.name + '</strong>',
                    rs: 1,
                    cs: 1
                },
                {
                    content: '<span style="font-size: 0.85rem;">Dạng bào chế</span>',
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
                    content: '<span style="font-size: 0.85rem;">Số BMR</span>',
                    rs: 1,
                    cs: 1
                },
                {
                    content: '<strong>: ' + t.code + '</strong>',
                    rs: 1,
                    cs: 1
                },
                {
                    content: '<span style="font-size: 0.85rem;">Phân loại</span>',
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
                    content: '<span style="font-size: 0.85rem;">Số ấn bản</span>',
                    rs: 1,
                    cs: 1
                },
                {
                    content: '<strong style="color: red;">: ' + t.edition + '</strong>',
                    rs: 1,
                    cs: 1
                },
                {
                    content: '<span style="font-size: 0.85rem;">Cỡ lô</span>',
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
                    content: '<span style="font-size: 0.85rem;">Ấn bản thay thế</span>',
                    rs: 1,
                    cs: 1
                },
                {
                    content: '<strong>: ' + (t.pre_version !== '' ? String(t.pre_version).padStart(2, '0') : '00') +
                        '</strong>',
                    rs: 1,
                    cs: 1
                },
                {
                    content: '<span style="font-size: 0.85rem;">Ngày hiệu lực</span>',
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

    function generateProductDescriptionTable() {
        const id = 'blk_desc_' + Date.now();
        const formulas = @json($template->formulas ?? []);
        let labelClaimsHtml = '';

        let activeIngredients = formulas.filter(f => f.role && f.role.trim().toLowerCase() === 'hoạt chất');
        if (activeIngredients.length > 0) {
            let dosageUnit = "{{ mb_strtolower($template->dosage_name ?? 'viên') }}";
            let lines = [`Mỗi ${dosageUnit} chứa:<br>`];
            activeIngredients.forEach(f => {
                let matName = (f.materials && f.materials.length > 0) ? f.materials[0].name : '';
                if (matName.includes('(')) {
                    matName = matName.split('(')[0].trim();
                }
                let amount = Number(f.total_amount_per_unit).toLocaleString('vi-VN', {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 2
                });
                lines.push(`<div style="display: flex; width: 100%; max-width: 450px; margin-left: 20px;">
                                <span>${matName}</span>
                                <span style="flex-grow: 1; border-bottom: 1px dotted #000; margin: 0 5px; position: relative; top: -5px;"></span>
                                <span>${amount} mg</span>
                            </div>`);
            });
            labelClaimsHtml = lines.join('');
        } else {
            labelClaimsHtml = {!! json_encode($template->content ?? '') !!};
        }

        const t = {
            name: "{{ $template->category_name ?? '' }}",
            code: "{{ $template->category_code ?? '' }}",
            content: labelClaimsHtml,
            description: {!! json_encode($template->description ?? '') !!},
            storage_conditions: {!! json_encode($template->storage_conditions ?? '') !!}
        };

        let columns = [{
                label: 'C1',
                type: 'text',
                width: '25%'
            },
            {
                label: 'C2',
                type: 'text',
                width: '75%'
            }
        ];

        let data = [
            // Row 1: MÔ TẢ SẢN PHẨM
            [{
                    content: 'MÔ TẢ SẢN PHẨM',
                    rs: 1,
                    cs: 2,
                    textAlign: 'left',
                    fontWeight: 'bold',
                    fontSize: '1.1rem'
                },
                {
                    content: '',
                    hidden: true
                }
            ],
            // Row 2: Tên sản phẩm
            [{
                    content: 'Tên sản phẩm',
                    rs: 1,
                    cs: 1,
                    textAlign: 'left'
                },
                {
                    content: ': <strong>' + t.name + '</strong>',
                    rs: 1,
                    cs: 1,
                    textAlign: 'left'
                }
            ],
            // Row 3: Mã sản phẩm
            [{
                    content: 'Mã sản phẩm',
                    rs: 1,
                    cs: 1,
                    textAlign: 'left'
                },
                {
                    content: ': ' + t.code,
                    rs: 1,
                    cs: 1,
                    textAlign: 'left'
                }
            ],
            // Row 4: Hàm lượng nhãn
            [{
                    content: 'Hàm lượng nhãn',
                    rs: 1,
                    cs: 1,
                    textAlign: 'left'
                },
                {
                    content: ': ' + t.content,
                    rs: 1,
                    cs: 1,
                    textAlign: 'left'
                }
            ],
            // Row 5: Mô tả
            [{
                    content: 'Mô tả',
                    rs: 1,
                    cs: 1,
                    textAlign: 'left'
                },
                {
                    content: ': ' + t.description,
                    rs: 1,
                    cs: 1,
                    textAlign: 'left'
                }
            ]
        ];

        return {
            id: id,
            type: 'table',
            label: 'MÔ TẢ SẢN PHẨM',
            rows: 5,
            cols: 2,
            columns: columns,
            data: data,
            rowHeights: new Array(5).fill('auto'),
            borderMode: 'none',
            hideHeader: true,
            locked: true,
            isBmrHeader: true,
            section_id: "{{ $template->caterogy_id ?? 0 }}"
        };
    }

    function generateRecipeTables() {
        const t = {
            raw_batch_size: {{ (float) ($template->raw_batch_size ?? 0) }},
            unit_batch_size: "{{ $template->unit_batch_size ?? '' }}",
            batch_qty: "{{ $template->batch_qty ?? '' }}"
        };
        const formulas = @json($template->formulas ?? []);
        let resultBlocks = [];

        // Hỗ trợ 2 nhóm: 0 (Nguyên liệu pha chế), 1 (Nguyên liệu khác)
        [0, 1].forEach(targetType => {
            const typeFormulas = formulas.filter(f => (f.type || 0) == targetType);
            if (targetType === 1 && typeFormulas.length === 0) return; // Bỏ qua nếu nhóm 1 không có dữ liệu

            const tableId = 'blk_recipe_' + targetType + '_' + Date.now() + Math.floor(Math.random() * 1000);
            const titleLabel = targetType === 0 ? '1. NGUYÊN LIỆU PHA CHẾ' :
                '2. NGUYÊN LIỆU KHÁC (BAO PHIM/NANG)';

            let columns = [{
                    label: 'STT',
                    type: 'text',
                    width: '5%'
                },
                {
                    label: 'Mã NL',
                    type: 'text',
                    width: '13%'
                },
                {
                    label: 'Thành phần',
                    type: 'text',
                    width: '19%'
                },
                {
                    label: 'Chức năng',
                    type: 'text',
                    width: '11%'
                },
                {
                    label: 'Tiêu chuẩn',
                    type: 'text',
                    width: '8%'
                },
                {
                    label: 'Nhà sản xuất',
                    type: 'text',
                    width: '12%'
                },
                {
                    label: '1 viên (mg)',
                    type: 'text',
                    width: '11%'
                },
                {
                    label: 'Tỉ lệ (%)',
                    type: 'text',
                    width: '5%'
                },
                {
                    label: `Lô tiêu chuẩn ${t.raw_batch_size > 0 ? Number(t.raw_batch_size).toLocaleString('vi-VN', {minimumFractionDigits: 2, maximumFractionDigits: 2}) : ''} (${t.unit_batch_size})<br><span style="font-weight: 600;">${t.batch_qty} viên</span>`,
                    type: 'text',
                    width: '26%'
                }
            ];

            let data = [
                // Thêm dòng tiêu đề vào ngay trong bảng
                [{
                        content: titleLabel,
                        rs: 1,
                        cs: 9,
                        textAlign: 'left',
                        fontWeight: 'bold',
                        fontSize: '1.05rem',
                        backgroundColor: '#f8f9fa'
                    },
                    {
                        content: '',
                        hidden: true
                    }, {
                        content: '',
                        hidden: true
                    }, {
                        content: '',
                        hidden: true
                    }, {
                        content: '',
                        hidden: true
                    }, {
                        content: '',
                        hidden: true
                    }, {
                        content: '',
                        hidden: true
                    }, {
                        content: '',
                        hidden: true
                    }, {
                        content: '',
                        hidden: true
                    }
                ]
            ];
            let count = 0;
            let sumUnit = 0,
                sumPercent = 0,
                sumBatch = 0;
            let notesHtml = '';
            let cellNoteCounter = 0;

            typeFormulas.forEach(formula => {
                const stt = (++count);
                const materials = formula.materials && formula.materials.length > 0 ? formula
                    .materials : [{}];
                const matCount = materials.length;

                let unitVal = parseFloat(formula.total_amount_per_unit) || 0;
                let batchVal = parseFloat(formula.total_amount_per_batch) || 0;
                let percentVal = t.raw_batch_size > 0 ? (batchVal / t.raw_batch_size) * 100 : 0;

                let isCalculate = !(formula.not_calculator == 1);

                if (isCalculate) {
                    sumUnit += unitVal;
                    sumPercent += percentVal;
                    sumBatch += batchVal;
                }

                let unitText = '';
                let batchText = '';
                let percentText = '';

                if (isCalculate) {
                    percentText = percentVal > 0 ? Number(percentVal).toLocaleString('vi-VN', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    }) : '';
                } else {
                    percentText = 'NA';
                }

                if (formula.sub_amounts && formula.sub_amounts.length > 0) {
                    let unitParts = [];
                    let batchParts = [];
                    formula.sub_amounts.forEach((sub, sIdx) => {
                        let subLabel = sIdx === 0 ? 'a' : (sIdx === 1 ? 'b' : (sIdx === 2 ?
                            'c' : String.fromCharCode(97 + sIdx)));
                        let uVal = parseFloat(sub.amount_per_unit) || 0;
                        let bVal = parseFloat(sub.amount_per_batch) || 0;
                        unitParts.push(
                            `${Number(uVal).toLocaleString('vi-VN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}<sup>(${stt}${subLabel})</sup>`
                        );
                        batchParts.push(
                            `${Number(bVal).toLocaleString('vi-VN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}<sup>(${stt}${subLabel})</sup>`
                        );

                        if (sub.note) {
                            notesHtml +=
                                `<div class="mb-1"><i class="fas fa-info-circle me-1" style="color: #0056b3;"></i> <strong style="color: #0056b3;">${stt}${subLabel}:</strong> ${sub.note}</div>`;
                        }
                    });
                    unitText =
                        `${unitParts.join(' + ')} = ${Number(unitVal).toLocaleString('vi-VN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
                    batchText =
                        `${batchParts.join(' + ')} = ${Number(batchVal).toLocaleString('vi-VN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
                } else {
                    unitText = unitVal > 0 ? Number(unitVal).toLocaleString('vi-VN', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    }) : '';
                    batchText = batchVal > 0 ? Number(batchVal).toLocaleString('vi-VN', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    }) : '';

                    if (formula.number_of_lots > 1 && formula.amounts_of_lots > 0) {
                        let amountOfLotsVal = parseFloat(formula.amounts_of_lots);
                        batchText = `${Number(batchVal).toLocaleString('vi-VN', {minimumFractionDigits: 2, maximumFractionDigits: 2})} = ${Number(amountOfLotsVal).toLocaleString('vi-VN', {minimumFractionDigits: 2, maximumFractionDigits: 4})} x ${formula.number_of_lots}`;
                    } else if (batchVal > 0 && t.batch_qty > 0 && unitVal > 0 && t.raw_batch_size > 0) {
                        let singleBatchVal = (unitVal * t.raw_batch_size) / t.avg_core;
                        if (!singleBatchVal) singleBatchVal = batchVal / t.batch_qty;
                        if (Math.abs(singleBatchVal * t.batch_qty - batchVal) < 0.1) {
                            batchText =
                                `${Number(batchVal).toLocaleString('vi-VN', {minimumFractionDigits: 2, maximumFractionDigits: 2})} = ${Number(singleBatchVal).toLocaleString('vi-VN', {minimumFractionDigits: 2, maximumFractionDigits: 4})} x ${t.batch_qty}`;
                        }
                    }
                }

                if (!isCalculate) {
                    if (unitText) unitText = `(${unitText})`;
                    if (batchText) batchText = `(${batchText})`;
                }

                let cellNotesForm = formula.cell_notes || {};
                if (typeof cellNotesForm === 'string') {
                    try { cellNotesForm = JSON.parse(cellNotesForm); } catch(e) { cellNotesForm = {}; }
                }
                if (cellNotesForm.total_amount_per_unit) {
                    cellNoteCounter++;
                    unitText += `<sup>(*${cellNoteCounter})</sup>`;
                    notesHtml += `<div class="mb-1"><i class="fas fa-info-circle me-1" style="color: #17a2b8;"></i> <strong style="color: #17a2b8;">(*${cellNoteCounter}):</strong> ${cellNotesForm.total_amount_per_unit.replace(/\n/g, '<br>')}</div>`;
                }
                if (cellNotesForm.total_amount_per_batch) {
                    cellNoteCounter++;
                    batchText += `<sup>(*${cellNoteCounter})</sup>`;
                    notesHtml += `<div class="mb-1"><i class="fas fa-info-circle me-1" style="color: #17a2b8;"></i> <strong style="color: #17a2b8;">(*${cellNoteCounter}):</strong> ${cellNotesForm.total_amount_per_batch.replace(/\n/g, '<br>')}</div>`;
                }

                materials.forEach((mat, mIdx) => {
                    let cellNotesMat = mat.cell_notes || {};
                    if (typeof cellNotesMat === 'string') {
                        try { cellNotesMat = JSON.parse(cellNotesMat); } catch(e) { cellNotesMat = {}; }
                    }

                    let codeContent = (mat.code || '').replace(/\n/g, '<br>');
                    if (cellNotesMat.code) {
                        cellNoteCounter++;
                        codeContent += `<sup>(*${cellNoteCounter})</sup>`;
                        notesHtml += `<div class="mb-1"><i class="fas fa-info-circle me-1" style="color: #17a2b8;"></i> <strong style="color: #17a2b8;">(*${cellNoteCounter}):</strong> ${cellNotesMat.code.replace(/\n/g, '<br>')}</div>`;
                    }
                    
                    let nameContent = (mat.name || '').replace(/\n/g, '<br>');
                    if (cellNotesMat.name) {
                        cellNoteCounter++;
                        nameContent += `<sup>(*${cellNoteCounter})</sup>`;
                        notesHtml += `<div class="mb-1"><i class="fas fa-info-circle me-1" style="color: #17a2b8;"></i> <strong style="color: #17a2b8;">(*${cellNoteCounter}):</strong> ${cellNotesMat.name.replace(/\n/g, '<br>')}</div>`;
                    }

                    let manufContent = (mat.manufacturer || '').replace(/\n/g, '<br>');
                    if (cellNotesMat.manufacturer) {
                        cellNoteCounter++;
                        manufContent += `<sup>(*${cellNoteCounter})</sup>`;
                        notesHtml += `<div class="mb-1"><i class="fas fa-info-circle me-1" style="color: #17a2b8;"></i> <strong style="color: #17a2b8;">(*${cellNoteCounter}):</strong> ${cellNotesMat.manufacturer.replace(/\n/g, '<br>')}</div>`;
                    }

                    let specContent = (mat.Spec || '').replace(/\n/g, '<br>');
                    if (cellNotesMat.Spec) {
                        cellNoteCounter++;
                        specContent += `<sup>(*${cellNoteCounter})</sup>`;
                        notesHtml += `<div class="mb-1"><i class="fas fa-info-circle me-1" style="color: #17a2b8;"></i> <strong style="color: #17a2b8;">(*${cellNoteCounter}):</strong> ${cellNotesMat.Spec.replace(/\n/g, '<br>')}</div>`;
                    }

                    if (mIdx === 0) {
                        data.push([{
                                content: String(stt),
                                rs: matCount,
                                cs: 1,
                                textAlign: 'center',
                                fontWeight: 'bold'
                            },
                            {
                                content: codeContent,
                                rs: 1,
                                cs: 1,
                                textAlign: 'center'
                            },
                            {
                                content: nameContent,
                                rs: 1,
                                cs: 1,
                                textAlign: 'left'
                            },
                            {
                                content: formula.role || '',
                                rs: matCount,
                                cs: 1,
                                textAlign: 'center'
                            },
                            {
                                content: specContent,
                                rs: 1,
                                cs: 1,
                                textAlign: 'center'
                            },
                            {
                                content: manufContent,
                                rs: 1,
                                cs: 1,
                                textAlign: 'left'
                            },
                            {
                                content: unitText,
                                rs: matCount,
                                cs: 1,
                                textAlign: 'center'
                            },
                            {
                                content: percentText,
                                rs: matCount,
                                cs: 1,
                                textAlign: 'center'
                            },
                            {
                                content: batchText,
                                rs: matCount,
                                cs: 1,
                                textAlign: 'center'
                            }
                        ]);
                    } else {
                        data.push([{
                                content: '',
                                hidden: true
                            },
                            {
                                content: codeContent,
                                rs: 1,
                                cs: 1,
                                textAlign: 'center'
                            },
                            {
                                content: nameContent,
                                rs: 1,
                                cs: 1,
                                textAlign: 'left'
                            },
                            {
                                content: '',
                                hidden: true
                            },
                            {
                                content: specContent,
                                rs: 1,
                                cs: 1,
                                textAlign: 'center'
                            },
                            {
                                content: manufContent,
                                rs: 1,
                                cs: 1,
                                textAlign: 'left'
                            },
                            {
                                content: '',
                                hidden: true
                            },
                            {
                                content: '',
                                hidden: true
                            },
                            {
                                content: '',
                                hidden: true
                            }
                        ]);
                    }
                });
            });

            if (count > 0) {
                data.push([{
                        content: 'Tổng cộng:',
                        rs: 1,
                        cs: 6,
                        textAlign: 'center',
                        fontWeight: 'bold',
                        backgroundColor: '#f8f9fa'
                    },
                    {
                        content: '',
                        hidden: true
                    },
                    {
                        content: '',
                        hidden: true
                    },
                    {
                        content: '',
                        hidden: true
                    },
                    {
                        content: '',
                        hidden: true
                    },
                    {
                        content: '',
                        hidden: true
                    },
                    {
                        content: Number(sumUnit).toLocaleString('vi-VN', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        }),
                        rs: 1,
                        cs: 1,
                        textAlign: 'center',
                        fontWeight: 'bold',
                        backgroundColor: '#f8f9fa'
                    },
                    {
                        content: Number(sumPercent).toLocaleString('vi-VN', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        }),
                        rs: 1,
                        cs: 1,
                        textAlign: 'center',
                        fontWeight: 'bold',
                        backgroundColor: '#f8f9fa'
                    },
                    {
                        content: Number(sumBatch).toLocaleString('vi-VN', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        }),
                        rs: 1,
                        cs: 1,
                        textAlign: 'center',
                        fontWeight: 'bold',
                        backgroundColor: '#f8f9fa'
                    }
                ]);
            } else {
                data.push([{
                        content: `Không có dữ liệu nguyên liệu ${targetType === 0 ? 'pha chế' : 'khác'}`,
                        rs: 1,
                        cs: 9,
                        textAlign: 'center',
                        fontStyle: 'italic'
                    },
                    {
                        content: '',
                        hidden: true
                    }, {
                        content: '',
                        hidden: true
                    }, {
                        content: '',
                        hidden: true
                    }, {
                        content: '',
                        hidden: true
                    }, {
                        content: '',
                        hidden: true
                    }, {
                        content: '',
                        hidden: true
                    }, {
                        content: '',
                        hidden: true
                    }, {
                        content: '',
                        hidden: true
                    }
                ]);
            }

            resultBlocks.push({
                id: tableId,
                type: 'table',
                label: titleLabel,
                rows: data.length,
                cols: 9,
                columns: columns,
                data: data,
                rowHeights: new Array(data.length).fill('auto'),
                borderMode: 'visible',
                hideHeader: false,
                locked: true,
                isBmrHeader: true,
                section_id: "{{ $template->caterogy_id ?? 0 }}"
            });

            if (notesHtml) {
                resultBlocks.push({
                    id: 'blk_notes_' + targetType + '_' + Date.now() + Math.floor(Math.random() * 1000),
                    type: 'static-text',
                    label: `Ghi chú nguyên liệu ${targetType === 0 ? 'pha chế' : 'khác'}`,
                    content: `<div class="recipe-notes mt-2 mb-4" style="font-size: 0.95rem; color: #5f6368;">${notesHtml}</div>`,
                    locked: true,
                    isBmrHeader: true,
                    section_id: "{{ $template->caterogy_id ?? 0 }}"
                });
            }
        });

        return resultBlocks;
    }

    function generateSignatureTable() {
        const id = 'sys_bmr_tbl_signatures';
        const workflows = @json($template->workflows ?? []);
        const createdByName = "{{ $template->owner_name ?? '' }}";
        const createdAtStr = "{{ $template->created_at ?? '' }}";
        const createdBySignature = "{{ $template->owner_signature ?? '' }}";
        const createdByDesignation = "{{ $template->owner_designation ?? '' }}";

        let columns = [{
                label: '-',
                type: 'text',
                width: '20%'
            },
            {
                label: 'HỌ VÀ TÊN',
                type: 'text',
                width: '40%'
            },
            {
                label: 'CHỮ KÝ',
                type: 'text',
                width: '20%'
            },
            {
                label: 'NGÀY',
                type: 'text',
                width: '20%'
            }
        ];

        let data = [
            [{
                    content: '-',
                    rs: 1,
                    cs: 1,
                    textAlign: 'center',
                    fontWeight: 'bold'
                },
                {
                    content: 'HỌ VÀ TÊN',
                    rs: 1,
                    cs: 1,
                    textAlign: 'center',
                    fontWeight: 'bold'
                },
                {
                    content: 'CHỮ KÝ',
                    rs: 1,
                    cs: 1,
                    textAlign: 'center',
                    fontWeight: 'bold'
                },
                {
                    content: 'NGÀY',
                    rs: 1,
                    cs: 1,
                    textAlign: 'center',
                    fontWeight: 'bold'
                }
            ]
        ];

        function formatDate(dateStr) {
            if (!dateStr) return '';
            try {
                // Handle YYYY-MM-DD HH:MM:SS format
                const parts = dateStr.split(' ');
                const dateParts = parts[0].split('-');
                if (dateParts.length === 3) {
                    return `${dateParts[2]}/${dateParts[1]}/${dateParts[0]}`;
                }
                const d = new Date(dateStr);
                if (isNaN(d.getTime())) return dateStr;
                const day = String(d.getDate()).padStart(2, '0');
                const month = String(d.getMonth() + 1).padStart(2, '0');
                const year = d.getFullYear();
                return `${day}/${month}/${year}`;
            } catch (e) {
                return dateStr;
            }
        }

        // 1. Soạn thảo
        let authorSigContent = `<span class="text-success"><i class="fas fa-check-circle me-1"></i> Đã ký</span>`;
        if (createdBySignature) {
            authorSigContent =
                `<img src="${createdBySignature}" style="max-height: 40px; max-width: 120px;" alt="Chữ ký">`;
        }

        data.push([{
                content: 'Người soạn thảo',
                rs: 1,
                cs: 1,
                textAlign: 'left'
            },
            {
                content: `<div class="mt-2" style="font-weight: 500;">${createdByName}</div><div class="small text-muted">${createdByDesignation}</div>`,
                rs: 1,
                cs: 1,
                textAlign: 'left'
            },
            {
                content: authorSigContent,
                rs: 1,
                cs: 1,
                textAlign: 'center'
            },
            {
                content: formatDate(createdAtStr),
                rs: 1,
                cs: 1,
                textAlign: 'center'
            }
        ]);

        if (workflows && workflows.length > 0) {
            workflows.forEach(wf => {
                let roleText = 'Người kiểm tra';
                if (wf.role === 'approver') roleText = 'Người phê duyệt';
                if (wf.role === 'authorizer') roleText = 'Người duyệt ban hành';
                if (wf.role === 'reviewer') roleText = 'Người kiểm tra';

                let nameText =
                    `<div class="mt-2" style="font-weight: 500;">${wf.fullName || ''}</div><div class="small text-muted">${wf.designation_name || ''}</div>`;

                let dateText = '';
                let sigText = '';
                if (wf.status === 'approved') {
                    dateText = formatDate(wf.created_at);
                    if (wf.signature_image) {
                        sigText =
                            `<img src="${wf.signature_image}" style="max-height: 40px; max-width: 120px;" alt="Chữ ký">`;
                    } else {
                        sigText =
                            `<span class="text-success" style="font-weight: bold;"><i class="fas fa-check-circle me-1"></i> Đã ký</span>`;
                    }
                } else if (wf.status === 'rejected') {
                    sigText =
                        `<span class="text-danger" style="font-weight: bold;"><i class="fas fa-times-circle me-1"></i> Từ chối</span>`;
                }

                data.push([{
                        content: roleText,
                        rs: 1,
                        cs: 1,
                        textAlign: 'left'
                    },
                    {
                        content: nameText,
                        rs: 1,
                        cs: 1,
                        textAlign: 'left'
                    },
                    {
                        content: sigText,
                        rs: 1,
                        cs: 1,
                        textAlign: 'center'
                    },
                    {
                        content: dateText,
                        rs: 1,
                        cs: 1,
                        textAlign: 'center'
                    }
                ]);
            });
        } else {
            // Fallback empty rows
            data.push([{
                    content: 'Người kiểm tra',
                    rs: 1,
                    cs: 1,
                    textAlign: 'left'
                },
                {
                    content: '',
                    rs: 1,
                    cs: 1,
                    textAlign: 'left'
                },
                {
                    content: '',
                    rs: 1,
                    cs: 1,
                    textAlign: 'center'
                },
                {
                    content: '',
                    rs: 1,
                    cs: 1,
                    textAlign: 'center'
                }
            ]);
        }

        return {
            id: id,
            type: 'table',
            label: 'Trình Ký',
            rows: data.length,
            cols: 4,
            columns: columns,
            data: data,
            rowHeights: new Array(data.length).fill('auto'),
            borderMode: 'visible',
            hideHeader: true,
            locked: true,
            isVirtual: true,
            section_id: "{{ $template->caterogy_id ?? 0 }}"
        };
    }

    window.__V2_buildVirtualBlocks = function () {
        const type = "{{ $template->type ?? 'BMR' }}";
        // catId đã khai báo ở closure ngoài

        let virtualBlocks = [];

        if (type === 'GF') {
            // 1. GF HEADER (Section) — không hiện tiêu đề "HEADER" (chỉ giữ làm mốc điều hướng)
            virtualBlocks.push({
                id: 'sys_gf_sec_header',
                type: 'section',
                label: 'HEADER',
                section_id: catId,
                locked: true,
                isVirtual: true,
                hideTitle: true
            });

            // 2. GF Header (Table)
            let gfHeader = generateDefaultGfHeader();
            gfHeader.id = 'sys_gf_tbl_header';
            gfHeader.isVirtual = true;
            virtualBlocks.push(gfHeader);

            // 3. QUY TRÌNH PHÊ DUYỆT (Section) — nối liền trang với HEADER, không ngắt trang riêng
            virtualBlocks.push({
                id: 'sys_gf_sec_approval',
                type: 'section',
                label: 'PHÊ DUYỆT',
                section_id: catId,
                locked: true,
                isVirtual: true,
                noPageBreak: true
            });

            // 4. Trình Ký (Table)
            let sigTable = generateSignatureTable();
            sigTable.id = 'sys_gf_tbl_signatures';
            virtualBlocks.push(sigTable);
        } else if (type === 'CO') {
            // Không sinh virtual block nào cho Component
        } else {
            // 1. BMR HEADER (Section) — không hiện tiêu đề "HEADER" (chỉ giữ làm mốc điều hướng)
            virtualBlocks.push({
                id: 'sys_bmr_sec_header',
                type: 'section',
                label: 'HEADER',
                section_id: catId,
                locked: true,
                isVirtual: true,
                hideTitle: true
            });

            // 2. BMR Header (Table)
            let bmrHeader = generateDefaultBmrHeader();
            bmrHeader.id = 'sys_bmr_tbl_header';
            bmrHeader.isVirtual = true;
            virtualBlocks.push(bmrHeader);

            // QUY TRÌNH PHÊ DUYỆT — nối liền trang với HEADER, không ngắt trang riêng
            virtualBlocks.push({
                id: 'sys_bmr_sec_approval',
                type: 'section',
                label: 'PHÊ DUYỆT',
                section_id: catId,
                noPageBreak: true,
                locked: true,
                isVirtual: true
            });

            let sigTable = generateSignatureTable();
            sigTable.id = 'sys_bmr_tbl_signatures';
            virtualBlocks.push(sigTable);


            if (type !== 'MF' && type !== 'BPR') {
                // 3. THÔNG TIN CHUNG SẢN PHẨM (Section)
                virtualBlocks.push({
                    id: 'sys_bmr_sec_common_info',
                    type: 'section',
                    label: 'THÔNG TIN SẢN PHẨM',
                    section_id: catId,
                    locked: true,
                    isVirtual: true
                });

                // 4. MÔ TẢ SẢN PHẨM (Table)
                let descTable = generateProductDescriptionTable();
                descTable.id = 'sys_bmr_tbl_desc';
                descTable.isVirtual = true;
                virtualBlocks.push(descTable);

                // 5. CÔNG THỨC PHA CHẾ (Section)
                virtualBlocks.push({
                    id: 'sys_bmr_sec_recipe',
                    type: 'section',
                    label: 'CÔNG THỨC PHA CHẾ',
                    section_id: catId,
                    locked: true,
                    isVirtual: true
                });

                // 6. Recipe Tables & Notes
                let recipes = generateRecipeTables();
                recipes.forEach((tbl, idx) => {
                    tbl.id = 'sys_bmr_tbl_recipe_' + idx;
                    tbl.isVirtual = true;
                    virtualBlocks.push(tbl);
                });
            }
        }
        // Mọi block ảo đều bị khóa (không cho sửa trong V2)
        virtualBlocks.forEach(function (b) { b.locked = true; });
        return virtualBlocks;
    };

    // Nhãn các block hệ thống bản cũ còn lưu trong DB cần lọc bỏ (trừ GF)
        window.__V2_sysLabels = ['BMR HEADER', 'BMR Header', 'GF Header', 'QUY TRÌNH PHÊ DUYỆT', 'THÔNG TIN CHUNG SẢN PHẨM',
            'MÔ TẢ SẢN PHẨM', 'CÔNG THỨC PHA CHẾ', '1. NGUYÊN LIỆU PHA CHẾ', '2. NGUYÊN LIỆU KHÁC (BAO PHIM/NANG)',
            'Ghi chú nguyên liệu pha chế', 'Ghi chú nguyên liệu khác', 'THÔNG TIN CHUNG', 'Trình Ký'
        ];
    if ("{{ $template->type ?? 'BMR' }}" === "GF") { window.__V2_sysLabels = []; }
})();
</script>
