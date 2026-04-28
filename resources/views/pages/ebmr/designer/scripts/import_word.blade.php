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

    /**
     * Hàm xử lý khi người dùng chọn file Word (.docx)
     */
    async function importWordFile(input) {


        try {
            if (!input.files || input.files.length === 0) return;
            const file = input.files[0];

            if (typeof mammoth === 'undefined') {
                const errMsg =
                    'Thư viện Mammoth.js chưa được tải thành công. Vui lòng kiểm tra kết nối mạng hoặc CDN.';
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

            // Hỏi người dùng muốn ghi đè hay chèn nối tiếp
            const confirmResult = await Swal.fire({
                title: 'Nhập dữ liệu từ Word',
                text: 'Bạn muốn ghi đè toàn bộ nội dung hiện tại hay chèn nối tiếp vào cuối?',
                icon: 'question',
                showCancelButton: true,
                showDenyButton: true,
                confirmButtonText: 'Chèn nối tiếp',
                denyButtonText: 'Ghi đè tất cả',
                cancelButtonText: 'Hủy'
            });


            if (!confirmResult.isConfirmed && !confirmResult.isDenied) {
                input.value = '';
                return;
            }

            const isReplace = confirmResult.isDenied;

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
                        const messages = result.messages; // Các cảnh báo nếu có

                        if (isReplace) {
                            items = []; // Xóa hết dữ liệu cũ nếu chọn ghi đè
                        }
                        const oldLength = items.length;

                        try {
                            parseHtmlToEbrmItems(html);
                        } catch (parseErr) {
                            logToLaravel('Lỗi khi phân tích HTML từ Word', parseErr.stack);
                            throw parseErr;
                        }

                        const newItemsCount = items.length - oldLength;

                        console.log("Mammoth HTML Output:", html);
                        console.log("Parsed " + newItemsCount + " new items.");

                        saveStateDebounced();
                        renderBlocks();

                        Swal.fire('Thành công', 'Đã bóc tách được ' + newItemsCount +
                            ' khối nội dung từ file Word.', 'success');
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
     * Chuyển đổi chuỗi HTML thành mảng cấu trúc items của eBMR
     */
    function parseHtmlToEbrmItems(htmlString) {
        const parser = new DOMParser();
        const doc = parser.parseFromString(htmlString, 'text/html');

        const childNodes = doc.body.childNodes;

        childNodes.forEach(node => {
            if (node.nodeType !== Node.ELEMENT_NODE) return;

            const tagName = node.tagName.toLowerCase();

            // Xử lý Tiêu đề (Headings) và Đoạn văn (Paragraphs)
            if (tagName.match(/^h[1-6]$/) || tagName === 'p') {
                const textContent = node.innerHTML.trim();
                if (!textContent) return; // Bỏ qua đoạn văn trống

                const newItemId = 'item_' + Date.now() + Math.floor(Math.random() * 1000);
                let label = tagName === 'p' ? 'Nội dung' : tagName.toUpperCase();

                // Bao bọc nội dung bằng thẻ tương ứng để giữ style mặc định của eBMR
                let finalContent = `<${tagName}>${node.innerHTML}</${tagName}>`;
                if (tagName === 'p') {
                    finalContent = node.innerHTML; // P thì không cần bọc để tránh margin dư thừa
                }

                items.push({
                    id: newItemId,
                    type: 'static-text',
                    label: label,
                    content: finalContent,
                    borderMode: 'none',
                    section_id: window.activeSectionId || 'section_0'
                });
            }
            // Xử lý Danh sách (ul, ol)
            else if (tagName === 'ul' || tagName === 'ol') {
                const textContent = node.outerHTML.trim();
                if (!textContent) return;

                const newItemId = 'item_' + Date.now() + Math.floor(Math.random() * 1000);
                items.push({
                    id: newItemId,
                    type: 'static-text',
                    label: 'Danh sách',
                    content: textContent,
                    borderMode: 'none',
                    section_id: window.activeSectionId || 'section_0'
                });
            }
            // Xử lý Bảng biểu (Table)
            else if (tagName === 'table') {
                const rows = node.querySelectorAll('tr');
                if (rows.length === 0) return;

                // Chuyển đổi dữ liệu bảng: Sử dụng ma trận để xử lý rowspan/colspan chính xác
                const rowCount = rows.length;
                const matrix = [];
                for (let i = 0; i < rowCount; i++) matrix[i] = [];

                rows.forEach((tr, rIdx) => {
                    const cells = tr.querySelectorAll('td, th');
                    let cIdx = 0;

                    cells.forEach(cell => {
                        // Tìm vị trí cột trống tiếp theo trong dòng này (tránh ô đã bị chiếm bởi rowspan)
                        while (matrix[rIdx][cIdx]) {
                            cIdx++;
                        }

                        const colspan = parseInt(cell.getAttribute('colspan') || 1);
                        const rowspan = parseInt(cell.getAttribute('rowspan') || 1);

                        // Điền vào ma trận
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

                // Xác định số cột lớn nhất thực tế sau khi đã tính toán rowspan/colspan
                let maxCols = 0;
                matrix.forEach(row => {
                    if (row.length > maxCols) maxCols = row.length;
                });

                if (maxCols === 0) maxCols = 1;

                // Chuẩn hóa dữ liệu tableData từ ma trận
                const tableData = [];
                for (let r = 0; r < rowCount; r++) {
                    const rowData = [];
                    for (let c = 0; c < maxCols; c++) {
                        if (matrix[r][c]) {
                            rowData.push(matrix[r][c]);
                        } else {
                            // Ô trống bổ sung nếu dòng ngắn hơn maxCols
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

                const newItemId = 'item_' + Date.now() + Math.floor(Math.random() * 1000);
                items.push({
                    id: newItemId,
                    type: 'table',
                    label: 'Bảng (Imported)',
                    rows: rows.length,
                    cols: maxCols,
                    data: tableData,
                    columns: columnConfig,
                    borderMode: 'visible',
                    hideHeader: true, // Mặc định ẩn header do dữ liệu đã nằm trong tableData
                    canAddRows: false,
                    section_id: window.activeSectionId || 'section_0'
                });
            }
        });
    }
</script>
