<!-- Thư viện Mammoth.js để đọc file .docx trên trình duyệt -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/mammoth/1.6.0/mammoth.browser.min.js"></script>

<script>
    /**
     * Hàm gửi log lỗi về server Laravel để in vào file laravel.log
     */
    function logToLaravel(message, details = null) {
        console.error(message, details);
        fetch('{{ route('pages.ebmr.logError') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                message: message,
                url: window.location.href,
                details: details
            })
        });
    }

    // Biến toàn cục chứa các block phân tích từ file Word
    let windowParsedBlocks = [];

    /**
     * Hàm xử lý khi người dùng chọn file Word (.docx)
     */
    async function importWordFile(input) {
        try {
            if (!input.files || input.files.length === 0) return;
            const file = input.files[0];

            if (typeof mammoth === 'undefined') {
                const errMsg = 'Thư viện Mammoth.js chưa được tải thành công. Vui lòng kiểm tra kết nối mạng hoặc CDN.';
                logToLaravel(errMsg);
                Swal.fire('Lỗi hệ thống', errMsg, 'error');
                return;
            }

            // Kiểm tra định dạng
            if (file.name.endsWith('.doc')) {
                Swal.fire({
                    title: 'Định dạng cũ không được hỗ trợ',
                    text: 'File .doc (Word 97-2003) là định dạng cũ và không thể bóc tách trên trình duyệt. Vui lòng mở file này bằng Word và chọn "Save As" sang định dạng .docx, sau đó thử lại.',
                    icon: 'warning'
                });
                input.value = ''; // Reset input
                return;
            } else if (!file.name.endsWith('.docx')) {
                Swal.fire('Lỗi định dạng', 'Hệ thống chỉ hỗ trợ định dạng Word (.docx).', 'error');
                input.value = ''; // Reset input
                return;
            }

            // Hiển thị loading
            Swal.fire({
                title: 'Đang xử lý...',
                text: 'Hệ thống đang bóc tách cấu trúc file Word, vui lòng đợi.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            const reader = new FileReader();
            reader.onload = function(event) {
                const arrayBuffer = event.target.result;

                // Dùng mammoth để chuyển đổi .docx sang HTML
                mammoth.convertToHtml({
                        arrayBuffer: arrayBuffer
                    })
                    .then(function(result) {
                        const html = result.value; // Chuỗi HTML thô

                        // Bóc tách HTML thành các blocks
                        windowParsedBlocks = parseHtmlToWordBlocks(html);

                        Swal.close(); // Đóng loading

                        if (windowParsedBlocks.length === 0) {
                            Swal.fire('Thông báo', 'Không tìm thấy nội dung khả dụng nào trong file Word để nhập.', 'info');
                            input.value = '';
                            return;
                        }

                        // Mở Modal Xem trước
                        showWordImportPreviewModal();
                        input.value = ''; // Reset input
                    })
                    .catch(function(err) {
                        logToLaravel('Lỗi Mammoth Parsing', err.message || err);
                        Swal.fire('Lỗi',
                            'Không thể bóc tách file Word này. Vui lòng kiểm tra lại cấu trúc file.',
                            'error');
                        input.value = '';
                    });
            };

            reader.onerror = function(err) {
                logToLaravel('Lỗi FileReader', err);
                Swal.fire('Lỗi', 'Không thể đọc file từ máy tính của bạn.', 'error');
            };

            reader.readAsArrayBuffer(file);
        } catch (globalErr) {
            logToLaravel('Lỗi thực thi importWordFile', globalErr.stack);
            Swal.fire('Lỗi hệ thống', 'Đã xảy ra lỗi không xác định. Chi tiết đã được ghi vào log hệ thống.',
                'error');
        }
    }

    /**
     * Bóc tách chuỗi HTML từ Word thành danh sách các khối độc lập kèm dữ liệu cấu trúc eBMR
     */
    function parseHtmlToWordBlocks(htmlString) {
        const parser = new DOMParser();
        const doc = parser.parseFromString(htmlString, 'text/html');
        const childNodes = doc.body.childNodes;
        const blocks = [];

        childNodes.forEach(node => {
            if (node.nodeType !== Node.ELEMENT_NODE) return;

            const tagName = node.tagName.toLowerCase();

            // Xử lý Tiêu đề (Headings) và Đoạn văn (Paragraphs)
            if (tagName.match(/^h[1-6]$/) || tagName === 'p') {
                const textContent = node.innerHTML.trim();
                if (!textContent) return; // Bỏ qua đoạn văn trống

                let label = tagName === 'p' ? 'Nội dung' : tagName.toUpperCase();
                let finalContent = `<${tagName}>${node.innerHTML}</${tagName}>`;
                if (tagName === 'p') {
                    finalContent = node.innerHTML; // Tránh margin dư thừa cho thẻ p
                }

                blocks.push({
                    type: 'static-text',
                    label: label,
                    preview: node.outerHTML,
                    plainText: node.textContent || '',
                    selected: true,
                    data: {
                        type: 'static-text',
                        label: label,
                        content: finalContent,
                        borderMode: 'none'
                    }
                });
            }
            // Xử lý Danh sách (ul, ol)
            else if (tagName === 'ul' || tagName === 'ol') {
                const textContent = node.outerHTML.trim();
                if (!textContent) return;

                blocks.push({
                    type: 'static-text',
                    label: 'Danh sách',
                    preview: node.outerHTML,
                    plainText: node.textContent || '',
                    selected: true,
                    data: {
                        type: 'static-text',
                        label: 'Danh sách',
                        content: textContent,
                        borderMode: 'none'
                    }
                });
            }
            // Xử lý Bảng biểu (Table)
            else if (tagName === 'table') {
                const rows = node.querySelectorAll('tr');
                if (rows.length === 0) return;

                const rowCount = rows.length;
                const matrix = [];
                for (let i = 0; i < rowCount; i++) matrix[i] = [];

                rows.forEach((tr, rIdx) => {
                    const cells = tr.querySelectorAll('td, th');
                    let cIdx = 0;

                    cells.forEach(cell => {
                        while (matrix[rIdx][cIdx]) {
                            cIdx++;
                        }

                        const colspan = parseInt(cell.getAttribute('colspan') || 1);
                        const rowspan = parseInt(cell.getAttribute('rowspan') || 1);

                        for (let r = 0; r < rowspan; r++) {
                            for (let c = 0; c < colspan; c++) {
                                const targetR = rIdx + r;
                                const targetC = cIdx + c;

                                if (targetR < rowCount) {
                                    matrix[targetR][targetC] = {
                                        content: (r === 0 && c === 0) ? cell.innerHTML.trim() : '',
                                        rs: (r === 0 && c === 0) ? rowspan : 1,
                                        cs: (r === 0 && c === 0) ? colspan : 1,
                                        hidden: (r > 0 || c > 0)
                                    };
                                }
                            }
                        }
                        cIdx += colspan;
                    });
                });

                let maxCols = 0;
                matrix.forEach(row => {
                    if (row.length > maxCols) maxCols = row.length;
                });

                if (maxCols === 0) maxCols = 1;

                const tableData = [];
                for (let r = 0; r < rowCount; r++) {
                    const rowData = [];
                    for (let c = 0; c < maxCols; c++) {
                        if (matrix[r][c]) {
                            rowData.push(matrix[r][c]);
                        } else {
                            rowData.push({
                                content: '',
                                rs: 1,
                                cs: 1,
                                hidden: false
                            });
                        }
                    }
                    tableData.push(rowData);
                }

                const columnConfig = [];
                for (let i = 0; i < maxCols; i++) {
                    columnConfig.push({
                        label: `Cột ${i+1}`,
                        width: 'auto'
                    });
                }

                blocks.push({
                    type: 'table',
                    label: 'Bảng (Imported)',
                    preview: node.outerHTML,
                    plainText: node.textContent || '',
                    selected: true,
                    data: {
                        type: 'table',
                        label: 'Bảng (Imported)',
                        rows: rows.length,
                        cols: maxCols,
                        data: tableData,
                        columns: columnConfig,
                        borderMode: 'visible',
                        hideHeader: true,
                        canAddRows: false
                    }
                });
            }
        });

        return blocks;
    }

    /**
     * Mở modal xem trước tài liệu Word
     */
    function showWordImportPreviewModal() {
        const modalEl = document.getElementById('wordImportPreviewModal');
        if (!modalEl) return;

        // Vẽ lại danh sách block
        renderWordBlocksList();

        // Mở Modal
        if (window.bootstrap && bootstrap.Modal && typeof bootstrap.Modal.getInstance === 'function') {
            const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
            modal.show();
        } else if (window.$) {
            $(modalEl).modal('show');
        }
    }

    /**
     * Vẽ danh sách các khối Word ra màn hình xem trước
     */
    function renderWordBlocksList(searchQuery = '') {
        const listContainer = document.getElementById('wordBlocksPreviewList');
        if (!listContainer) return;

        listContainer.innerHTML = '';
        const query = searchQuery.trim().toLowerCase();

        let visibleCount = 0;
        let selectedCount = 0;

        windowParsedBlocks.forEach((block, index) => {
            // Lọc kết quả tìm kiếm
            if (query && !block.plainText.toLowerCase().includes(query) && !block.label.toLowerCase().includes(query)) {
                return;
            }

            visibleCount++;
            if (block.selected) {
                selectedCount++;
            }

            let badgeClass = 'bg-secondary text-white';
            if (block.label.startsWith('H')) badgeClass = 'bg-primary text-white';
            else if (block.label === 'Danh sách') badgeClass = 'bg-info text-white';
            else if (block.label === 'Bảng (Imported)') badgeClass = 'bg-warning text-dark';

            const card = document.createElement('div');
            card.className = 'card shadow-sm border-0 mb-2 word-block-card';
            card.style.borderRadius = '8px';
            card.style.transition = 'all 0.15s ease-in-out';
            card.style.borderLeft = block.selected ? '5px solid #007bff' : '5px solid #dee2e6';
            card.style.marginBottom = '10px';
            card.style.backgroundColor = block.selected ? '#f1f8ff' : '#fff';
            
            card.innerHTML = `
                <div class="card-body p-3 d-flex align-items-start" style="padding: 15px; display: flex; align-items: flex-start;">
                    <div class="form-check" style="margin-right: 15px; margin-top: 4px;">
                        <input class="form-check-input word-block-checkbox" type="checkbox" data-index="${index}" ${block.selected ? 'checked' : ''} style="width: 1.25rem; height: 1.25rem; cursor: pointer;">
                    </div>
                    <div class="flex-grow-1" style="flex-grow: 1; min-width: 0;">
                        <div class="d-flex align-items-center mb-2" style="display: flex; align-items: center; margin-bottom: 8px; gap: 10px;">
                            <span class="badge ${badgeClass} text-uppercase px-2 py-1 fw-bold" style="font-size: 0.7rem; border-radius: 4px; padding: 4px 8px;">${block.label}</span>
                            <span class="text-muted small" style="color: #6c757d; font-size: 0.8rem;">Khối #${index + 1}</span>
                        </div>
                        <div class="word-block-content-preview border rounded bg-white p-3" style="padding: 15px; font-size: 0.95rem; border: 1px solid #dee2e6; border-radius: 8px; background-color: #fff; box-shadow: inset 0 1px 2px rgba(0,0,0,0.02);">
                            ${block.preview}
                        </div>
                    </div>
                </div>
            `;
            listContainer.appendChild(card);
        });

        // Cập nhật số lượng đếm
        const countSpan = document.getElementById('selectedWordBlocksCount');
        const totalSpan = document.getElementById('totalWordBlocksCount');
        if (countSpan) countSpan.textContent = selectedCount;
        if (totalSpan) totalSpan.textContent = windowParsedBlocks.length;

        // Cập nhật nút Chọn tất cả
        const selectAllCheckbox = document.getElementById('selectAllWordBlocks');
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = (selectedCount === windowParsedBlocks.length && windowParsedBlocks.length > 0);
        }
    }

    // Thiết lập sự kiện lắng nghe sau khi DOM load
    document.addEventListener('DOMContentLoaded', () => {
        const modalContainer = document.createElement('div');
        modalContainer.innerHTML = `
            <div class="modal fade" id="wordImportPreviewModal" tabindex="-1" aria-hidden="true" data-backdrop="static" style="z-index: 2050;">
                <div class="modal-dialog modal-xl modal-dialog-scrollable" style="max-width: 96%; margin: 10px auto; height: calc(100vh - 20px);">
                    <div class="modal-content shadow-lg border-0" style="border-radius: 12px; display: flex; flex-direction: column; height: 100%;">
                        <div class="modal-header bg-primary text-white py-3" style="display: flex; justify-content: space-between; align-items: center; padding: 15px 20px; border-top-left-radius: 12px; border-top-right-radius: 12px; background-color: #007bff; color: #fff;">
                            <h5 class="modal-title fw-bold" style="margin-bottom: 0; font-size: 1.15rem;">
                                <i class="fas fa-file-word mr-2" style="margin-right: 8px;"></i> XEM TRƯỚC & CHỌN NỘI DUNG NHẬP TỪ WORD
                            </h5>
                            <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close" style="opacity: 0.8; font-size: 1.5rem; background: none; border: none; color: #fff; cursor: pointer;">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body p-4 bg-light" style="padding: 24px; background-color: #f8f9fa; overflow-y: auto; flex-grow: 1; display: flex; flex-direction: column;">
                            <div class="row align-items-center mb-3" style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px; flex-wrap: wrap; gap: 10px;">
                                <div style="display: flex; align-items: center;">
                                    <div class="form-check" style="display: flex; align-items: center;">
                                        <input class="form-check-input" type="checkbox" id="selectAllWordBlocks" checked style="width: 1.2rem; height: 1.2rem; cursor: pointer;">
                                        <label class="form-check-label fw-bold text-dark" for="selectAllWordBlocks" style="cursor: pointer; user-select: none; margin-left: 8px; font-weight: bold;">
                                            Chọn tất cả các phần (<span id="selectedWordBlocksCount">0</span>/<span id="totalWordBlocksCount">0</span>)
                                        </label>
                                    </div>
                                </div>
                                <div style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
                                    <div style="display: flex; align-items: center;">
                                        <span class="fw-bold text-dark small" style="font-weight: bold; font-size: 0.85rem; margin-right: 10px;">Chế độ nhập:</span>
                                        <div class="form-check form-check-inline" style="display: inline-flex; align-items: center; margin-right: 15px;">
                                            <input class="form-check-input" type="radio" name="wordImportMode" id="importModeAppend" value="append" checked style="cursor: pointer;">
                                            <label class="form-check-label small" for="importModeAppend" style="cursor: pointer; margin-left: 5px; font-size: 0.85rem;">Chèn tiếp nối</label>
                                        </div>
                                        <div class="form-check form-check-inline" style="display: inline-flex; align-items: center;">
                                            <input class="form-check-input" type="radio" name="wordImportMode" id="importModeOverwrite" value="overwrite" style="cursor: pointer;">
                                            <label class="form-check-label text-danger small" for="importModeOverwrite" style="cursor: pointer; margin-left: 5px; font-size: 0.85rem; color: #dc3545; font-weight: 500;">Ghi đè tất cả</label>
                                        </div>
                                    </div>
                                    <div class="input-group" style="max-width: 220px;">
                                        <input type="text" id="searchWordBlocks" class="form-control form-control-sm" placeholder="Tìm kiếm nội dung..." style="padding: 4px 8px; font-size: 0.85rem; border: 1px solid #ced4da; border-radius: 4px; width: 100%;">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Danh sách các block xem trước -->
                            <div id="wordBlocksPreviewList" style="display: flex; flex-direction: column; gap: 12px; overflow-y: auto; padding-right: 5px; flex-grow: 1;">
                                <!-- Render tự động -->
                            </div>
                        </div>
                        <div class="modal-footer bg-white border-top py-3 px-4 style-select-wrapper" style="display: flex; justify-content: space-between; align-items: center; padding: 15px 20px; border-top: 1px solid #dee2e6; border-bottom-left-radius: 12px; border-bottom-right-radius: 12px; background-color: #fff;">
                            <div>
                                <span class="text-muted small" style="color: #6c757d; font-size: 0.8rem;"><i class="fas fa-info-circle mr-1" style="margin-right: 4px;"></i> Các khối được tích chọn sẽ được chèn vào phân đoạn đang kích hoạt (active section).</span>
                            </div>
                            <div style="display: flex; gap: 10px;">
                                <button type="button" class="btn btn-secondary px-4" data-dismiss="modal" style="padding: 6px 16px; border: 1px solid #ced4da; background-color: #6c757d; color: #fff; border-radius: 4px; cursor: pointer; margin-right: 8px;">Hủy bỏ</button>
                                <button type="button" id="confirmWordImportBtn" class="btn btn-primary px-4 fw-bold shadow-sm" style="padding: 6px 16px; font-weight: bold; background-color: #007bff; border: 1px solid #007bff; color: #fff; border-radius: 4px; cursor: pointer; box-shadow: 0 2px 4px rgba(0,123,255,.25);">
                                    <i class="fas fa-file-import mr-1" style="margin-right: 4px;"></i> Nhập Phần Đã Chọn
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <style>
                .word-block-card {
                    cursor: pointer;
                    border-radius: 8px;
                    transition: all 0.15s ease-in-out;
                    border: 1px solid #dee2e6 !important;
                }
                .word-block-card:hover {
                    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.08) !important;
                    transform: translateY(-1px);
                    border-color: #b8daff !important;
                }
                .word-block-content-preview table {
                    width: 100% !important;
                    border-collapse: collapse;
                    margin-top: 5px;
                }
                .word-block-content-preview table th,
                .word-block-content-preview table td {
                    border: 1px solid #dee2e6;
                    padding: 6px 10px;
                    font-size: 0.85rem;
                }
            </style>
        `;
        document.body.appendChild(modalContainer);

        // Đăng ký sự kiện nút Chọn tất cả
        const selectAllCheckbox = document.getElementById('selectAllWordBlocks');
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', (e) => {
                const checked = e.target.checked;
                windowParsedBlocks.forEach(block => {
                    block.selected = checked;
                });
                const query = document.getElementById('searchWordBlocks').value;
                renderWordBlocksList(query);
            });
        }

        // Đăng ký sự kiện ô tìm kiếm/lọc nội dung
        const searchInput = document.getElementById('searchWordBlocks');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                renderWordBlocksList(e.target.value);
            });
        }

        // Đăng ký sự kiện chọn checkbox từng block riêng lẻ
        const previewList = document.getElementById('wordBlocksPreviewList');
        if (previewList) {
            // Click vào bất kỳ vị trí nào trên card ngoại trừ vùng preview hoặc checkbox để bật/tắt chọn nhanh
            previewList.addEventListener('click', (e) => {
                if (e.target.closest('.word-block-content-preview') || e.target.classList.contains('word-block-checkbox')) {
                    return;
                }
                const card = e.target.closest('.word-block-card');
                if (card) {
                    const checkbox = card.querySelector('.word-block-checkbox');
                    if (checkbox) {
                        checkbox.checked = !checkbox.checked;
                        checkbox.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                }
            });

            previewList.addEventListener('change', (e) => {
                if (e.target.classList.contains('word-block-checkbox')) {
                    const idx = parseInt(e.target.getAttribute('data-index'));
                    if (!isNaN(idx) && windowParsedBlocks[idx]) {
                        windowParsedBlocks[idx].selected = e.target.checked;
                        
                        // Cập nhật viền & nền cho card tương ứng để tạo điểm nhấn
                        const card = e.target.closest('.word-block-card');
                        if (card) {
                            card.style.borderLeft = e.target.checked ? '5px solid #007bff' : '5px solid #dee2e6';
                            card.style.backgroundColor = e.target.checked ? '#f1f8ff' : '#fff';
                        }
                        
                        // Tính toán lại số block được chọn
                        const selectedCount = windowParsedBlocks.filter(b => b.selected).length;
                        const countSpan = document.getElementById('selectedWordBlocksCount');
                        if (countSpan) countSpan.textContent = selectedCount;

                        if (selectAllCheckbox) {
                            selectAllCheckbox.checked = (selectedCount === windowParsedBlocks.length);
                        }
                    }
                }
            });
        }

        // Xác nhận import các khối đã chọn vào editor
        const confirmBtn = document.getElementById('confirmWordImportBtn');
        if (confirmBtn) {
            confirmBtn.addEventListener('click', () => {
                const selectedBlocks = windowParsedBlocks.filter(b => b.selected);
                if (selectedBlocks.length === 0) {
                    Swal.fire('Thông báo', 'Vui lòng tích chọn ít nhất 1 phần để nhập.', 'warning');
                    return;
                }

                const isReplace = document.getElementById('importModeOverwrite').checked;

                if (isReplace) {
                    items = [];
                }

                // Xác định vị trí chèn
                let insertIndex = items.length;
                if (!isReplace) {
                    if (selectedId) {
                        const currentIdx = items.findIndex(i => i.id === selectedId);
                        if (currentIdx !== -1) insertIndex = currentIdx + 1;
                    } else if (window.activeSectionId) {
                        // Chèn vào cuối phân đoạn đang active
                        const secIdx = items.findIndex(item => item.type === 'section' && item.id === window.activeSectionId);
                        if (secIdx !== -1) {
                            let lastIdxInSection = secIdx;
                            for (let i = secIdx + 1; i < items.length; i++) {
                                if (items[i].type === 'section') {
                                    break;
                                }
                                lastIdxInSection = i;
                            }
                            insertIndex = lastIdxInSection + 1;
                        }
                    }
                }

                // Chèn các khối đã chọn
                selectedBlocks.forEach(block => {
                    const newItem = Object.assign({}, block.data, {
                        id: 'item_' + Date.now() + '_' + Math.floor(Math.random() * 100000) + Math.random().toString(36).substr(2, 5),
                        section_id: window.activeSectionId || 'section_0'
                    });
                    items.splice(insertIndex++, 0, newItem);
                });

                // Đóng Modal
                const modalEl = document.getElementById('wordImportPreviewModal');
                if (window.bootstrap && bootstrap.Modal && typeof bootstrap.Modal.getInstance === 'function') {
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    if (modal) modal.hide();
                } else if (window.$) {
                    $(modalEl).modal('hide');
                }

                // Lưu trạng thái và vẽ lại Canvas
                saveStateDebounced();
                renderBlocks();

                Swal.fire('Thành công', `Đã nhập thành công ${selectedBlocks.length} khối nội dung vào phân đoạn.`, 'success');
            });
        }
    });
</script>
