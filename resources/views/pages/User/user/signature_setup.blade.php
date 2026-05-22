<!-- Modal Cấu hình Chữ ký Cá nhân -->
<div class="modal fade" id="signatureSetupModal" tabindex="-1" aria-labelledby="signatureSetupModalLabel" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
            <div class="modal-header bg-light border-0 px-4 py-3">
                <h5 class="modal-title fw-bold text-navy" id="signatureSetupModalLabel">
                    <i class="fas fa-signature me-2 text-primary"></i>CẤU HÌNH CHỮ KÝ CÁ NHÂN
                </h5>
                <button type="button" class="close border-0 bg-transparent text-dark" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close" style="font-size: 1.5rem; outline: none; opacity: 0.7;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body px-4 py-3">
                <div class="mb-3 text-center">
                    <p class="text-muted small mb-3">Vẽ chữ ký của bạn vào khung bên dưới hoặc tải lên tệp ảnh chữ ký có sẵn.</p>
                    
                    <!-- Khung vẽ Canvas -->
                    <div class="position-relative" style="max-width: 100%;">
                        <canvas id="signature-setup-canvas" width="440" height="180"></canvas>
                    </div>
                </div>

                <!-- Điều khiển và Màu sắc -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="small fw-bold text-muted me-1">Màu mực:</span>
                        <button type="button" class="btn btn-sm rounded-circle sig-color-btn active" style="width: 28px; height: 28px; background-color: #0000aa; border: 2px solid #fff; box-shadow: 0 0 0 1px #cbd5e1;" onclick="changeSigColor('#0000aa')" title="Mực xanh"></button>
                        <button type="button" class="btn btn-sm rounded-circle sig-color-btn" style="width: 28px; height: 28px; background-color: #f406f8ff; border: 2px solid #fff; box-shadow: 0 0 0 1px #cbd5e1;" onclick="changeSigColor('#f406f8ff')" title="Mực đen"></button>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <!-- Nút tải ảnh lên -->
                        <label class="btn btn-outline-secondary btn-sm mb-0 d-flex align-items-center" style="border-radius: 8px; cursor: pointer; font-weight: 500;">
                            <i class="fas fa-upload me-1"></i> Tải ảnh lên
                            <input type="file" id="signature-file-upload" accept="image/*" class="d-none" onchange="handleSignatureUpload(event)">
                        </label>
                        <!-- Nút xóa nét vẽ -->
                        <button type="button" class="btn btn-outline-danger btn-sm d-flex align-items-center" style="border-radius: 8px; font-weight: 500;" onclick="clearSignatureCanvas()">
                            <i class="fas fa-eraser me-1"></i> Xóa vẽ
                        </button>
                    </div>
                </div>

                <div id="sig-setup-alert-success" class="alert alert-success d-flex align-items-center p-2 mb-0" style="border-radius: 8px; font-size: 0.8rem; background-color: rgba(40, 167, 69, 0.08); border: 1px solid rgba(40, 167, 69, 0.2); {{ (session('user') && !empty(session('user')['signature_image'])) ? '' : 'display: none !important;' }}">
                    <i class="fas fa-check-circle me-2 text-success" style="font-size: 1rem;"></i>
                    <div class="text-success fw-medium">Người dùng đã có cấu hình chữ ký mẫu trước đó. Lưu chữ ký mới sẽ ghi đè lên chữ ký cũ.</div>
                </div>
                <div id="sig-setup-alert-warning" class="alert alert-warning d-flex align-items-center p-2 mb-0" style="border-radius: 8px; font-size: 0.8rem; background-color: rgba(255, 193, 7, 0.08); border: 1px solid rgba(255, 193, 7, 0.2); {{ (session('user') && !empty(session('user')['signature_image'])) ? 'display: none !important;' : '' }}">
                    <i class="fas fa-exclamation-triangle me-2 text-warning" style="font-size: 1rem;"></i>
                    <div class="text-warning fw-medium">Người dùng chưa cấu hình chữ ký mẫu cá nhân. Hệ thống sẽ tự động dùng Họ tên khi ký.</div>
                </div>
            </div>
            <div class="modal-footer bg-light border-0 px-4 py-3">
                <button type="button" class="btn btn-secondary px-3 py-2" data-dismiss="modal" data-bs-dismiss="modal" style="border-radius: 10px; font-weight: 500;">Hủy</button>
                <button type="button" class="btn btn-primary px-4 py-2" id="saveSignatureSetupBtn" onclick="saveSignatureSetup()" style="border-radius: 10px; font-weight: 600; background: linear-gradient(135deg, #0d6efd, #0b5ed7);">Lưu chữ ký</button>
            </div>
        </div>
    </div>
</div>

<style>
    #signature-setup-canvas {
        border: 2px dashed #cbd5e1;
        border-radius: 12px;
        background-color: #f8fafc;
        cursor: crosshair;
        max-width: 100%;
        box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);
        background-image: linear-gradient(rgba(0, 0, 0, 0.03) 1px, transparent 1px),
                          linear-gradient(90deg, rgba(0, 0, 0, 0.03) 1px, transparent 1px);
        background-size: 16px 16px;
        transition: border-color 0.2s;
    }
    #signature-setup-canvas:hover {
        border-color: #94a3b8;
    }
    .sig-color-btn {
        transition: transform 0.15s, box-shadow 0.15s;
    }
    .sig-color-btn.active {
        transform: scale(1.15);
        box-shadow: 0 0 0 2px #3b82f6 !important;
    }
</style>

<script>
    (function() {
        let signatureCanvas, sigCtx, sigDrawing = false;
        let sigLastX = 0, sigLastY = 0;
        let sigStrokeColor = '#0000aa'; // Mực xanh dương mặc định

        window.initSignatureCanvas = function() {
            signatureCanvas = document.getElementById('signature-setup-canvas');
            if (!signatureCanvas) return;
            sigCtx = signatureCanvas.getContext('2d');
            
            // Xóa canvas
            sigCtx.clearRect(0, 0, signatureCanvas.width, signatureCanvas.height);
            sigCtx.lineWidth = 3.5;
            sigCtx.lineJoin = 'round';
            sigCtx.lineCap = 'round';
            sigCtx.strokeStyle = sigStrokeColor;
            
            // Mouse events
            signatureCanvas.addEventListener('mousedown', (e) => {
                sigDrawing = true;
                [sigLastX, sigLastY] = getMousePos(signatureCanvas, e);
            });
            signatureCanvas.addEventListener('mousemove', drawSignature);
            signatureCanvas.addEventListener('mouseup', () => sigDrawing = false);
            signatureCanvas.addEventListener('mouseout', () => sigDrawing = false);
            
            // Touch events for mobile/tablet
            signatureCanvas.addEventListener('touchstart', (e) => {
                sigDrawing = true;
                const touch = e.touches[0];
                [sigLastX, sigLastY] = getTouchPos(signatureCanvas, touch);
                e.preventDefault();
            });
            signatureCanvas.addEventListener('touchmove', (e) => {
                if (!sigDrawing) return;
                const touch = e.touches[0];
                const [x, y] = getTouchPos(signatureCanvas, touch);
                sigCtx.beginPath();
                sigCtx.moveTo(sigLastX, sigLastY);
                sigCtx.lineTo(x, y);
                sigCtx.stroke();
                [sigLastX, sigLastY] = [x, y];
                e.preventDefault();
            });
            signatureCanvas.addEventListener('touchend', () => sigDrawing = false);
        };

        function getMousePos(canvasDom, mouseEvent) {
            const rect = canvasDom.getBoundingClientRect();
            return [
                mouseEvent.clientX - rect.left,
                mouseEvent.clientY - rect.top
            ];
        }

        function getTouchPos(canvasDom, touchEvent) {
            const rect = canvasDom.getBoundingClientRect();
            return [
                touchEvent.clientX - rect.left,
                touchEvent.clientY - rect.top
            ];
        }

        function drawSignature(e) {
            if (!sigDrawing) return;
            const [x, y] = getMousePos(signatureCanvas, e);
            sigCtx.beginPath();
            sigCtx.moveTo(sigLastX, sigLastY);
            sigCtx.lineTo(x, y);
            sigCtx.stroke();
            [sigLastX, sigLastY] = [x, y];
        }

        window.changeSigColor = function(color) {
            sigStrokeColor = color;
            if (sigCtx) sigCtx.strokeStyle = color;
            document.querySelectorAll('.sig-color-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            if (event && event.currentTarget) {
                event.currentTarget.classList.add('active');
            }
        };

        window.clearSignatureCanvas = function() {
            if (sigCtx && signatureCanvas) {
                sigCtx.clearRect(0, 0, signatureCanvas.width, signatureCanvas.height);
                const fileInput = document.getElementById('signature-file-upload');
                if (fileInput) fileInput.value = '';
            }
        };

        window.handleSignatureUpload = function(e) {
            const file = e.target.files[0];
            if (!file) return;
            
            if (!file.type.match('image.*')) {
                Swal.fire('Lỗi', 'Vui lòng chọn một tệp hình ảnh!', 'warning');
                return;
            }
            
            const reader = new FileReader();
            reader.onload = function(evt) {
                const img = new Image();
                img.onload = function() {
                    clearSignatureCanvas();
                    // Vẽ ảnh vừa khít vào canvas (giữ tỷ lệ)
                    const scale = Math.min(signatureCanvas.width / img.width, signatureCanvas.height / img.height);
                    const w = img.width * scale;
                    const h = img.height * scale;
                    const x = (signatureCanvas.width - w) / 2;
                    const y = (signatureCanvas.height - h) / 2;
                    sigCtx.drawImage(img, x, y, w, h);
                };
                img.src = evt.target.result;
            };
            reader.readAsDataURL(file);
        };

        let currentSigSetupUserId = null;

        window.saveSignatureSetup = function() {
            if (!signatureCanvas) return;
            const dataURL = signatureCanvas.toDataURL('image/png');
            
            const saveBtn = document.getElementById('saveSignatureSetupBtn');
            const oldText = saveBtn.innerHTML;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Đang lưu...';
            saveBtn.disabled = true;
            
            const payload = { signature_image: dataURL };
            if (currentSigSetupUserId) {
                payload.user_id = currentSigSetupUserId;
            }
            
            fetch('/User/user/update-signature', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').content : ''
                },
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(res => {
                saveBtn.innerHTML = oldText;
                saveBtn.disabled = false;
                
                if (res.success) {
                    Swal.fire({
                        title: 'Thành công',
                        text: res.message || 'Cấu hình chữ ký thành công!',
                        icon: 'success',
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        $('#signatureSetupModal').modal('hide');
                        window.location.reload(); // Tải lại để cập nhật session hoặc datatable
                    });
                } else {
                    Swal.fire('Lỗi', res.message || 'Có lỗi xảy ra khi lưu chữ ký.', 'error');
                }
            })
            .catch(err => {
                saveBtn.innerHTML = oldText;
                saveBtn.disabled = false;
                Swal.fire('Lỗi', 'Không thể kết nối đến máy chủ.', 'error');
            });
        };

        window.openSignatureSetupModal = function(e, userId = null, hasSignature = false) {
            if (e) e.preventDefault();
            
            Swal.fire({
                title: 'Xác thực mật khẩu',
                html: `
                    <div class="mb-3 text-muted text-center" style="font-size: 0.95rem; line-height: 1.5;">Vui lòng nhập mật khẩu đăng nhập của bạn để tiếp tục cấu hình chữ ký:</div>
                    <div style="position: relative; width: 100%; max-width: 360px; margin: 0 auto; display: flex; align-items: center;">
                        <input type="password" id="swal-input-password" class="swal2-input" placeholder="Mật khẩu của bạn" autofocus style="width: 100%; box-sizing: border-box; padding-right: 40px; margin: 0;">
                        <button type="button" id="toggle-password-btn" onclick="const inp = document.getElementById('swal-input-password'); const isP = inp.type === 'password'; inp.type = isP ? 'text' : 'password'; this.innerHTML = isP ? '<i class=&quot;fas fa-eye-slash&quot;></i>' : '<i class=&quot;fas fa-eye&quot;></i>'; inp.focus();" style="position: absolute; right: 15px; background: none; border: none; cursor: pointer; color: #6b7280; font-size: 1.1rem; padding: 4px; outline: none; z-index: 10; display: flex; align-items: center; justify-content: center; line-height: 1;">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonColor: '#0891b2',
                cancelButtonColor: '#ef4444',
                confirmButtonText: 'Xác thực',
                cancelButtonText: 'Hủy',
                showLoaderOnConfirm: true,
                preConfirm: () => {
                    const passwordInput = document.getElementById('swal-input-password');
                    const password = passwordInput ? passwordInput.value : '';
                    if (!password) {
                        Swal.showValidationMessage('Vui lòng nhập mật khẩu.');
                        return false;
                    }
                    
                    return fetch("{{ route('pages.ebmr.verifyPassword') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': "{{ csrf_token() }}"
                        },
                        body: JSON.stringify({ password: password })
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(response.statusText);
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (!data.success) {
                            Swal.showValidationMessage(data.message || 'Mật khẩu không chính xác.');
                            return false;
                        }
                        return true;
                    })
                    .catch(error => {
                        Swal.showValidationMessage(`Lỗi kết nối: ${error}`);
                    });
                },
                allowOutsideClick: () => !Swal.isLoading()
            }).then((result) => {
               
                if (result) {
                    currentSigSetupUserId = userId;
                    
                    // Cập nhật trạng thái alert dựa trên việc có chữ ký hay chưa
                    const alertSuccess = document.getElementById('sig-setup-alert-success');
                    const alertWarning = document.getElementById('sig-setup-alert-warning');
                    
                    let userHasSig = false;
                    if (userId) {
                        userHasSig = hasSignature;
                    } else {
                        userHasSig = @json(session('user') && !empty(session('user')['signature_image']));
                    }
                    
                    if (userHasSig) {
                        if (alertSuccess) alertSuccess.style.setProperty('display', 'flex', 'important');
                        if (alertWarning) alertWarning.style.setProperty('display', 'none', 'important');
                    } else {
                        if (alertSuccess) alertSuccess.style.setProperty('display', 'none', 'important');
                        if (alertWarning) alertWarning.style.setProperty('display', 'flex', 'important');
                    }
                    
                    // Hỗ trợ cả BS4 và BS5
                    let modalEl = document.getElementById('signatureSetupModal');
                    if (window.bootstrap && window.bootstrap.Modal && typeof window.bootstrap.Modal.getInstance === 'function') {
                        let bsModal = window.bootstrap.Modal.getInstance(modalEl) || new window.bootstrap.Modal(modalEl);
                        bsModal.show();
                    } else {
                        $(modalEl).modal('show');
                    }
                    
                    setTimeout(initSignatureCanvas, 300);
                }
            });
        };
    })();
</script>
