/**
 * eBMR Designer V2 — Quét Barcode & tra cứu MMS
 * =================================================================
 * Port từ resources/views/pages/ebmr/designer/scripts/ui_handlers.blade.php
 * (startMmsBarcodeScan / initMmsBarcodeScan / fetchMmsDataAndShowModal).
 * Dùng lại nguyên route generic GET /ebmr/mms/stock/{barcode} và thư viện
 * html5-qrcode (asset('libs/html5-qrcode.min.js')) đã có sẵn trong dự án.
 */

export function initMmsBarcodeV2(BOOT) {
    BOOT.startMmsBarcodeScan = function (fieldId) {
        window.Swal?.close();
        if (typeof window.Html5QrcodeScanner === 'undefined') {
            const script = document.createElement('script');
            script.src = '/libs/html5-qrcode.min.js';
            script.onload = () => BOOT.initMmsBarcodeScan(fieldId);
            document.head.appendChild(script);
        } else {
            BOOT.initMmsBarcodeScan(fieldId);
        }
    };

    BOOT.initMmsBarcodeScan = function (fieldId) {
        window.Swal.fire({
            title: 'Quét Barcode',
            html: '<div id="mms-reader" style="width:100%; min-height: 250px;"></div>',
            showCancelButton: true,
            cancelButtonText: 'Hủy',
            showConfirmButton: false,
            didOpen: () => {
                const scanner = new window.Html5QrcodeScanner('mms-reader', { fps: 10, qrbox: { width: 250, height: 100 } }, false);
                scanner.render((decodedText) => {
                    scanner.clear();
                    BOOT.fetchMmsDataAndShowModal(decodedText, fieldId);
                }, () => {});
                window.currentMmsScanner = scanner;
            },
            willClose: () => {
                if (window.currentMmsScanner) {
                    window.currentMmsScanner.clear().catch(() => {});
                    window.currentMmsScanner = null;
                }
            },
        }).then((result) => {
            if (result.dismiss === window.Swal.DismissReason.cancel) {
                BOOT.openExecutionModal(fieldId);
            }
        });
    };

    BOOT.fetchMmsDataAndShowModal = async function (barcode, fieldId) {
        window.Swal.fire({ title: 'Đang tra cứu MMS...', allowOutsideClick: false, didOpen: () => window.Swal.showLoading() });
        try {
            const res = await fetch(`/ebmr/mms/stock/${barcode}`);
            const json = await res.json();
            if (!json.success) {
                window.Swal.fire('Thông báo', json.message || 'Không tìm thấy Barcode', 'info')
                    .then(() => BOOT.openExecutionModal(fieldId));
                return;
            }
            const data = json.data;
            const fieldConf = BOOT.fieldsConfig[fieldId] || {};
            const matchValue = fieldConf.barcodeMatchValue ? fieldConf.barcodeMatchValue.trim() : '';

            let isMatched = true;
            let warningHtml = '<div class="alert alert-success fw-bold text-center mb-3 shadow-sm" style="background-color: #ecfdf5; color: #065f46; border-color: #a7f3d0;">APPROVED</div>';
            if (matchValue) {
                const values = Object.values(data).map((v) => String(v).toLowerCase());
                const target = matchValue.toLowerCase();
                isMatched = values.some((v) => v.includes(target));
                if (!isMatched) {
                    warningHtml = `<div class="alert alert-danger fw-bold text-center mb-3 shadow-sm" style="background-color: #fef2f2; color: #991b1b; border-color: #fecaca;">
                        <i class="fas fa-exclamation-triangle me-1"></i> KHÔNG KHỚP MÃ ĐỐI CHIẾU (${matchValue})
                    </div>`;
                }
            }
            const chkState = isMatched ? '' : 'disabled';
            const row = (label, value) => `<tr><td class="fw-bold text-end text-secondary">${label}:</td><td class="text-start fw-medium text-dark">${value ?? ''}</td><td class="text-center"><input type="checkbox" class="mms-chk form-check-input m-0" ${chkState} value="${value ?? ''}"></td></tr>`;

            const html = `
                <div class="text-start" style="font-size: 0.95rem;">
                    <style>
                        .mms-chk { width: 1.4rem; height: 1.4rem; cursor: pointer; border: 2px solid #a5b4fc; transition: all 0.2s; }
                        .mms-chk:checked { background-color: #4f46e5; border-color: #4f46e5; box-shadow: 0 0 0 0.25rem rgba(79, 70, 229, 0.25); }
                        .mms-chk:disabled { background-color: #e2e8f0; border-color: #cbd5e1; cursor: not-allowed; opacity: 0.5; }
                        .mms-table td { vertical-align: middle; padding: 0.6rem 0.5rem; border-color: #e2e8f0; }
                        .mms-table th { border-color: #e2e8f0; background-color: #f8fafc; }
                        .mms-table tr:hover td { background-color: #f1f5f9; }
                    </style>
                    ${warningHtml}
                    <table class="table table-sm table-bordered mms-table ${isMatched ? '' : 'opacity-75'}">
                        <thead><tr><th class="text-center text-muted" style="width: 35%;">Thông tin</th><th class="text-center text-muted">Giá trị</th><th class="text-center text-muted" style="width: 60px;">Chọn</th></tr></thead>
                        <tbody>
                            ${row('GRN No / Barcode', `${data.GRN_No ?? ''} ${data.Barcode_No ?? ''}`)}
                            ${row('LOT', data.LOT)}
                            ${row('Material Code', data.Material_Code)}
                            ${row('Số PKN (ARNO)', data.ARNO)}
                            ${row('Material Name', data.Material_Name)}
                            ${row('Expiry Date', data.Expiry_Date)}
                            ${row('Retest Date', data.Retest_Date)}
                            ${row('Mfg. Name', data.Mfg_Name)}
                            ${row('MFG Date', data.MFG_Date)}
                            ${row('Supplier Name', data.Supplier_Name)}
                            ${row('MFG Batch', data.MFG_Batch)}
                            ${row('Qty', data.Qty !== undefined ? parseFloat(data.Qty) : '')}
                            ${row('Sample Type', data.Sample_Type)}
                            ${row('Sample By / On', `${data.SampleBy ?? ''} / ${data.sample_On ?? ''}`)}
                            ${row('COA No', data.COA_No)}
                            ${row('COA Date', data.COA_Date)}
                        </tbody>
                    </table>
                </div>`;

            window.Swal.fire({
                title: 'Nhãn - ' + (data.Material_Name || ''),
                html,
                width: '650px',
                showCancelButton: true,
                confirmButtonText: 'Áp dụng',
                cancelButtonText: 'Hủy',
                preConfirm: () => Array.from(window.Swal.getPopup().querySelectorAll('.mms-chk:checked')).map((cb) => cb.value),
            }).then((result) => {
                if (result.isConfirmed) {
                    const finalStr = (result.value || []).join('\n');
                    BOOT.applyExecutionValue(fieldId, finalStr, () => {
                        window.Swal.fire({ icon: 'success', title: 'Thành công', text: 'Đã điền thông tin vào tài liệu', timer: 1500, showConfirmButton: false });
                    });
                } else {
                    BOOT.openExecutionModal(fieldId);
                }
            });
        } catch (e) {
            window.Swal.fire('Lỗi', 'Không thể kết nối API MMS', 'error');
        }
    };

    window.__V2__ = BOOT;
}
