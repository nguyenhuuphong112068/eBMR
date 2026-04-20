@extends('layout.master')

@section('title', 'Ghi chép Hồ Sơ BMR')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection

@section('mainContent')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <div class="content-wrapper" id="mainContent" style="background-color: #f1f3f4; min-height: 100vh;">
        <!-- Top Action Bar for Execution -->
        <div class="bg-white border-bottom px-4 py-3 d-flex justify-content-between align-items-center sticky-top shadow-sm" style="z-index: 1020;">
            <div>
                <h5 class="mb-0 fw-bold text-navy"><i class="fas fa-edit me-2"></i> Ghi chép: Số lô <span class="text-primary">{{ $record->batch_number }}</span></h5>
                <div class="small text-muted mt-1">Mẫu hồ sơ: <strong>{{ $template->name }}</strong> ({{ $template->document_code }})</div>
            </div>
            <div>
                <button class="btn btn-outline-secondary me-2" onclick="saveRecordData('draft')">
                    <i class="fas fa-save me-1"></i> Lưu bản nháp
                </button>
                <button class="btn btn-success" onclick="saveRecordData('completed')">
                    <i class="fas fa-check-circle me-1"></i> Hoàn Thành Nhập Liệu
                </button>
            </div>
        </div>

        @include('pages.ebmr.designer.partials.canvas')
    </div>

    <!-- Modal for Data Input in Execution Mode -->
    <div class="modal fade" id="executionInputModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 15px;">
                <div class="modal-header bg-navy text-white" style="border-radius: 15px 15px 0 0;">
                    <h5 class="modal-title fw-bold" id="execModalTitle">Nhập dữ liệu</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body p-4">
                    <div id="execInputContainer">
                        <!-- Dynamic input based on type -->
                        <div class="mb-3" id="textInputGroup">
                            <label class="form-label fw-bold small text-muted">Nội dung ghi chép</label>
                            <textarea class="form-control" id="execTextContent" rows="4" placeholder="Nhập kết quả tại đây..."></textarea>
                        </div>
                        <div class="mb-3 d-none" id="signatureInputGroup">
                            <p class="text-center py-3 border rounded bg-light mb-3">
                                <i class="fas fa-id-badge fa-3x text-navy mb-2 d-block"></i>
                                <span class="fw-bold">Xác nhận danh tính:</span><br>
                                <span class="text-primary h5">{{ session('user')['fullName'] ?? 'Người dùng' }}</span>
                            </p>
                            <label class="form-label fw-bold small text-muted">Vui lòng nhập mật khẩu để ký</label>
                            <div class="input-group">
                                <input type="password" id="execPassConfirm" class="form-control" placeholder="••••••••">
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary" type="button" onclick="toggleExecPassword()">
                                        <i class="fas fa-eye" id="passToggleIcon"></i>
                                    </button>
                                </div>
                            </div>
                            <p class="small text-center text-muted mt-2">Bằng cách nhấn xác nhận, bạn đồng ý đính kèm chữ ký điện tử của mình vào hồ sơ này.</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light px-4" data-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-navy px-4" id="confirmExecBtn" onclick="submitExecutionValue()">Xác nhận</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentExecContext = null;

        function toggleExecPassword() {
            const input = document.getElementById('execPassConfirm');
            const icon = document.getElementById('passToggleIcon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        function openExecutionInputModal(blockId, row, col, type) {
            currentExecContext = { blockId, row, col, type };
            
            const title = type === 'signature' ? 'Xác nhận chữ ký điện tử' : 'Nhập dữ liệu';
            document.getElementById('execModalTitle').innerText = title;

            // Reset password field
            document.getElementById('execPassConfirm').value = '';

            // Toggle UI groups
            if (type === 'signature') {
                document.getElementById('textInputGroup').classList.add('d-none');
                document.getElementById('signatureInputGroup').classList.remove('d-none');
            } else {
                document.getElementById('textInputGroup').classList.remove('d-none');
                document.getElementById('signatureInputGroup').classList.add('d-none');
                
                // Load existing value if any
                const existing = (window.executionValues[blockId] && window.executionValues[blockId][`${row}_${col}`]) 
                                 ? window.executionValues[blockId][`${row}_${col}`] : '';
                document.getElementById('execTextContent').value = existing;
            }

            $('#executionInputModal').modal('show');
        }

        async function submitExecutionValue() {
            if (!currentExecContext) return;
            const { blockId, row, col, type } = currentExecContext;
            let value = "";

            if (type === 'signature') {
                const password = document.getElementById('execPassConfirm').value;
                if (!password) {
                    Swal.fire('Lỗi', 'Vui lòng nhập mật khẩu xác nhận', 'warning');
                    return;
                }

                // Call Backend to verify
                const btn = document.getElementById('confirmExecBtn');
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Đang xác thực...';

                try {
                    const response = await fetch('{{ route('pages.ebmr.verifyPassword') }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                        body: JSON.stringify({ password: password, _token: '{{ csrf_token() }}' })
                    });
                    const res = await response.json();
                    
                    if (!res.success) {
                        Swal.fire('Thất bại', res.message || 'Mật khẩu không chính xác', 'error');
                        btn.disabled = false;
                        btn.innerHTML = 'Xác nhận';
                        return;
                    }
                    
                    value = "{{ session('user')['fullName'] ?? 'Signed' }}";
                } catch (err) {
                    Swal.fire('Lỗi', 'Không thể kết nối đến máy chủ', 'error');
                    btn.disabled = false;
                    btn.innerHTML = 'Xác nhận';
                    return;
                }
                btn.disabled = false;
                btn.innerHTML = 'Xác nhận';
            } else {
                value = document.getElementById('execTextContent').value;
            }

            // Update local state
            if (!window.executionValues[blockId]) window.executionValues[blockId] = {};
            window.executionValues[blockId][`${row}_${col}`] = value;

            renderBlocks();
            $('#executionInputModal').modal('hide');
        }

        window.isReadOnly = false;
        window.isExecutionMode = true;
        window.templateComments = [];
        window.currentRecordId = {{ $record->id }};


        function saveRecordData(status) {
            // Đảm bảo dữ liệu gửi đi luôn là Object dể tránh lỗi JSON.stringify bỏ qua string keys trong Array
            let dataToSend = window.executionValues;
            if (Array.isArray(dataToSend)) {
                dataToSend = Object.assign({}, dataToSend);
            }

            console.log("Dữ liệu thực tế gửi đi:", {
                record_id: window.currentRecordId,
                data: dataToSend,
                status: status
            });

            Swal.fire({
                title: 'Đang lưu dữ liệu...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading()
                }
            });

            fetch('{{ route('pages.ebmr.updateRecordData') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({
                    record_id: window.currentRecordId,
                    data: dataToSend,
                    status: status,
                    _token: '{{ csrf_token() }}'
                })
            })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    Swal.fire({ title: 'Thành công', text: 'Đã lưu dữ liệu hồ sơ lô!', icon: 'success', showConfirmButton: false, timer: 1500 }).then(() => {
                        if (status === 'completed') {
                            window.location.href = "{{ route('pages.ebmr.indexRecords') }}";
                        }
                    });
                } else {
                    Swal.fire('Lỗi', res.message || 'Có lỗi xảy ra', 'error');
                }
            })
            .catch(err => {
                Swal.fire('Lỗi mạng', 'Không thể kết nối đến máy chủ', 'error');
            });
        }
    </script>

    @include('pages.ebmr.designer.partials.modals')
    @include('pages.ebmr.designer.partials.styles')

    <style>
        /* Specific styles for Execution Mode to make it look like a real document */
        .execution-mode.block-item {
            border: none !important;
            padding: 0 !important;
            margin-bottom: 5px !important;
        }
        .execution-input-cell {
            cursor: pointer !important;
            position: relative;
            transition: all 0.2s ease;
        }
        .execution-input-cell:hover {
            background-color: rgba(0, 123, 255, 0.05) !important;
        }
        .execution-badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            gap: 5px;
            transition: 0.2s;
            border: 1px solid transparent;
        }
        .execution-badge.input {
            background-color: #e7f3ff;
            color: #007bff;
            border-color: #b8daff;
        }
        .execution-badge.signature {
            background-color: #fff4e5;
            color: #fd7e14;
            border-color: #ffe5b4;
        }
        .execution-badge:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .e-signature-done {
            font-family: 'Dancing Script', cursive, serif;
            font-size: 1.1rem;
            color: #2c3e50;
        }
        
        /* Hide UI components not needed in execution */
        .editor-ruler, .outline-sidebar, #sidebar-col, #floating-comment-btn, .insert-divider {
            display: none !important;
        }
        #canvas-col {
            flex: 0 0 100% !important;
            max-width: 100% !important;
        }
        #document-page {
            margin: 0 auto !important;
        }
        .doc-header {
            display: none !important;
        }
    </style>

    {{-- Script Modules --}}
    @include('pages.ebmr.designer.scripts.state')
    @include('pages.ebmr.designer.scripts.ui_handlers')
    @include('pages.ebmr.designer.scripts.render')
    @include('pages.ebmr.designer.scripts.table_ops')
    @include('pages.ebmr.designer.scripts.table_advanced')
    @include('pages.ebmr.designer.scripts.events')
@endsection
