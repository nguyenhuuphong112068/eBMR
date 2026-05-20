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
    
    <div class="content-wrapper execution-mode-active" id="mainContent" style="background-color: #f1f3f4; min-height: 100vh;">

        <!-- Top Action Bar for Execution -->
        <div class="bg-white border-bottom px-4 py-3 d-flex justify-content-between align-items-center sticky-top shadow-sm" style="z-index: 1020;">
            <div>
                <h5 class="mb-0 fw-bold text-navy">
                    <i class="fas fa-edit me-2"></i> Ghi chép: Số lô <span class="text-primary">{{ $record->batch_number }}</span>
                    @if($activeSectionLabel)
                        <span class="ms-2 badge bg-info rounded-pill" style="font-size: 0.8rem; font-weight: 500;">
                            <i class="fas fa-layer-group me-1"></i> {{ $activeSectionLabel }}
                        </span>
                    @endif
                </h5>
                <div class="small text-muted mt-1">Mẫu hồ sơ: <strong>{{ $template->name }}</strong> ({{ $template->document_code }})</div>
            </div>
            <div>
                @if($activeSectionId)
                <button class="btn btn-outline-info me-2" onclick="window.location.href='{{ route('pages.ebmr.execute', $record->id) }}'">
                    <i class="fas fa-eye me-1"></i> Xem tất cả công đoạn
                </button>
                @endif
                
                @if($isReadOnly)
                    @if($record->status === 'completed')
                    <button class="btn btn-primary" onclick="confirmReadRecord()">
                        <i class="fas fa-check-double me-1"></i> Xác nhận đã Đọc hồ sơ
                    </button>
                    @elseif($record->status === 'reviewed')
                    <span class="badge bg-success p-2 fs-6"><i class="fas fa-check me-1"></i> Hồ sơ đã được duyệt</span>
                    @endif
                @else
                    <button class="btn btn-outline-secondary me-2" onclick="saveRecordData('draft')">
                        <i class="fas fa-save me-1"></i> Lưu bản nháp
                    </button>
                    <button class="btn btn-success" onclick="saveRecordData('completed')">
                        <i class="fas fa-check-circle me-1"></i> Hoàn Thành Nhập Liệu
                    </button>
                @endif
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

    <!-- Modal: Xác thực Người kiểm tra (Cross-verification) -->
    <div class="modal fade" id="checkerAuthModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content shadow-lg border-0" style="border-radius: 12px;">
                <div class="modal-header bg-dark text-white border-0 py-2 px-3">
                    <h5 class="modal-title fw-bold small"><i class="fas fa-user-shield me-2"></i> XÁC THỰC NGƯỜI KIỂM TRA</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="small fw-bold mb-1">Tài khoản (Username)</label>
                        <input type="text" id="checkerUsername" class="form-control" placeholder="Nhập tên đăng nhập">
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold mb-1">Mật khẩu</label>
                        <input type="password" id="checkerPassword" class="form-control" placeholder="Nhập mật khẩu">
                    </div>
                    <input type="hidden" id="checkerBlockId">
                    <input type="hidden" id="checkerRowIdx">
                    <input type="hidden" id="checkerColIdx">
                    <div id="checkerAuthError" class="text-danger small mt-2 d-none fw-bold"></div>
                </div>
                <div class="modal-footer bg-light border-0 py-2">
                    <button type="button" class="btn btn-secondary btn-sm px-3" data-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-primary btn-sm px-4 fw-bold" onclick="submitCheckerAuth()">Xác nhận</button>
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
            if (window.isReadOnly) return;
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
            if (typeof syncLinkedCharts === 'function') syncLinkedCharts(blockId);
            
            $('#executionInputModal').modal('hide');
        }

        window.isReadOnly = @json($isReadOnly ?? false);
        window.isExecutionMode = true;
        window.templateComments = [];
        window.currentRecordId = {{ $record->id }};

        function confirmReadRecord() {
            Swal.fire({
                title: 'Xác nhận Đọc hồ sơ',
                text: 'Bạn có chắc chắn đã xem xét kỹ các số liệu trong hồ sơ này?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Xác nhận',
                cancelButtonText: 'Hủy'
            }).then((result) => {
                const isConfirmed = result.isConfirmed || (result.value !== undefined && !result.dismiss);
                if (isConfirmed) {
                    saveRecordData('reviewed');
                }
            });
        }

        function saveRecordData(status) {
            // Đảm bảo dữ liệu gửi đi luôn là Object dể tránh lỗi JSON.stringify bỏ qua string keys trong Array
            let dataToSend = {};
            if (window.executionValues) {
                Object.keys(window.executionValues).forEach(key => {
                    dataToSend[key] = window.executionValues[key];
                });
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
                },
                onOpen: () => { // Hỗ trợ phiên bản cũ
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
                    // Cập nhật metadata cục bộ để hiển thị ngay
                    if (window.executionValues && res.updated_by) {
                        Object.keys(window.executionValues).forEach(blockId => {
                            const blockData = window.executionValues[blockId];
                            if (blockData && typeof blockData === 'object') {
                                blockData._meta = blockData._meta || {};
                                Object.keys(blockData).forEach(cellId => {
                                    if (cellId !== '_meta') {
                                        blockData._meta[cellId] = { by: res.updated_by, at: res.updated_at };
                                    }
                                });
                            }
                        });
                        renderBlocks();
                    }

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
        .execution-badge.time {
            color: #17a2b8;
            background-color: #e0f4f7;
        }
        .execution-badge.executor {
            color: #6610f2;
            background-color: #f0e6ff;
        }
        .execution-badge.checker {
            color: #fd7e14;
            background-color: #fff0e6;
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
        .editor-ruler, #floating-comment-btn, .insert-divider, .test-mode-badge {
            display: none !important;
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
    @include('pages.ebmr.designer.scripts.chart_ops')
    @include('pages.ebmr.designer.scripts.events')
@endsection
