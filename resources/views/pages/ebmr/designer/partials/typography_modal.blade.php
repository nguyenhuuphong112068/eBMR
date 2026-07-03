
<!-- Typography Settings Modal -->
<div class="modal fade" id="typographySettingsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content shadow-lg border-0" style="border-radius: 12px;">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold text-navy"><i class="fas fa-text-height me-2"></i> Chuẩn Hóa Cỡ Chữ (Typography)</h5>
                <button type="button" class="close text-dark border-0 bg-transparent" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close" style="font-size: 1.5rem; outline: none; opacity: 0.8;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info small mb-4">
                    <i class="fas fa-info-circle me-1"></i> Cài đặt này sẽ ghi đè mọi cỡ chữ bị sai lệch do copy từ Word, giúp toàn bộ hồ sơ đồng nhất theo chuẩn.
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-bold small">Tiêu đề cấp 1 (H1)</label>
                    <div class="input-group input-group-sm">
                        <select class="form-select" id="typo-h1">
                            <option value="14pt">14pt</option>
                            <option value="16pt">16pt</option>
                            <option value="18pt">18pt</option>
                            <option value="20pt">20pt</option>
                            <option value="24pt">24pt</option>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold small">Tiêu đề cấp 2 (H2)</label>
                    <div class="input-group input-group-sm">
                        <select class="form-select" id="typo-h2">
                            <option value="12pt">12pt</option>
                            <option value="14pt">14pt</option>
                            <option value="16pt">16pt</option>
                            <option value="18pt">18pt</option>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold small">Văn bản & Bảng (Normal Text)</label>
                    <div class="input-group input-group-sm">
                        <select class="form-select" id="typo-normal">
                            <option value="10pt">10pt</option>
                            <option value="11pt">11pt</option>
                            <option value="12pt">12pt</option>
                            <option value="13pt">13pt</option>
                            <option value="14pt">14pt</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal" data-bs-dismiss="modal">Đóng</button>
                <button type="button" class="btn btn-primary btn-sm px-4 fw-bold" onclick="applyTypographySettings()">Áp dụng</button>
            </div>
        </div>
    </div>
</div>
