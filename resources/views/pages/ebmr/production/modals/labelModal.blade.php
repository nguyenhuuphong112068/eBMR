<!-- Modal Cần Vệ Sinh (Màu Vàng) -->
<div class="modal fade" id="modalCleanRequired" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content" style="border: 3px solid #ffc107; border-radius: 8px;">
            <div class="modal-header text-center d-block bg-light" style="border-bottom: 2px solid #ddd;">
                <h5 class="modal-title fw-bold text-uppercase w-100 mb-0" id="cleanRequiredLabelType">NHÃN THIẾT BỊ</h5>
                <small class="text-muted">EQUIPMENT LABEL</small>
            </div>
            <div class="modal-body p-0">
                <table class="table table-bordered mb-0">
                    <tbody>
                        <tr>
                            <th class="bg-light" style="width: 40%;">Tên phòng/ thiết bị<br><small>Name</small></th>
                            <td id="lblReqName" class="fw-bold align-middle">-</td>
                        </tr>
                        <tr>
                            <th class="bg-light">Mã số/ Code</th>
                            <td id="lblReqCode" class="fw-bold text-primary align-middle">-</td>
                        </tr>
                        <tr>
                            <td colspan="2" class="p-0 border-0">
                                <div class="bg-warning text-dark text-center fw-bold py-2 border-bottom">
                                    CẦN VỆ SINH / TO BE CLEANED
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <div class="d-flex justify-content-between mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="reqLevel1" disabled>
                                        <label class="form-check-label fw-bold" for="reqLevel1">Cấp / Level I</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="reqLevel2" disabled>
                                        <label class="form-check-label fw-bold" for="reqLevel2">Cấp / Level II</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="reqReClean" disabled>
                                        <label class="form-check-label fw-bold" for="reqReClean">Vệ sinh lại / Re-Cleaning</label>
                                    </div>
                                </div>
                                <div class="small text-muted mb-2 border-bottom pb-2">
                                    Đánh dấu "v" vào nội dung áp dụng/ Check "v" in used content<br>
                                    Cấp/ Level I : Phải thực hiện vệ sinh trong vòng 24 giờ/ Level I: should be done within 24 hours<br>
                                    Cấp/ Level II : Phải thực hiện vệ sinh trong vòng 3 ngày/ Level II: should be done within 3 days
                                </div>
                                <div class="row mb-2">
                                    <div class="col-6 border-end">
                                        <span class="small fw-bold">Thời gian hoàn tất sản xuất/ Process finished on:</span><br>
                                        Ngày/ Date: <span id="reqFinishedDate" class="fw-bold text-primary">-</span>
                                    </div>
                                    <div class="col-6">
                                        <span class="small fw-bold">Vệ sinh trước/ To be cleaned before:</span><br>
                                        Ngày/ Date: <span id="reqCleanBefore" class="fw-bold text-danger">-</span>
                                    </div>
                                </div>
                                <div class="border-top pt-2">
                                    <span class="small fw-bold">Người thực hiện/ Done by:</span>
                                    <span id="reqDoneBy" class="ms-2 fst-italic text-primary">-</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="modal-footer p-2 d-flex justify-content-center bg-light">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Đã Vệ Sinh (Màu Xanh) -->
<div class="modal fade" id="modalCleaned" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content" style="border: 3px solid #28a745; border-radius: 8px;">
            <div class="modal-header text-center d-block bg-light" style="border-bottom: 2px solid #ddd;">
                <h5 class="modal-title fw-bold text-uppercase w-100 mb-0" id="cleanedLabelType">NHÃN THIẾT BỊ</h5>
                <small class="text-muted">EQUIPMENT LABEL</small>
            </div>
            <div class="modal-body p-0">
                <table class="table table-bordered mb-0">
                    <tbody>
                        <tr>
                            <th class="bg-light" style="width: 40%;">Tên phòng/ thiết bị<br><small>Name</small></th>
                            <td id="lblCldName" class="fw-bold align-middle">-</td>
                        </tr>
                        <tr>
                            <th class="bg-light">Mã số/ Code</th>
                            <td id="lblCldCode" class="fw-bold text-primary align-middle">-</td>
                        </tr>
                        <tr>
                            <td colspan="2" class="p-0 border-0">
                                <div class="bg-success text-white text-center fw-bold py-2 border-bottom">
                                    ĐÃ VỆ SINH / CLEANED
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <div class="d-flex justify-content-between mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="cldLevel1" disabled>
                                        <label class="form-check-label fw-bold" for="cldLevel1">Cấp / Level I</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="cldLevel2" disabled>
                                        <label class="form-check-label fw-bold" for="cldLevel2">Cấp / Level II</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="cldReClean" disabled>
                                        <label class="form-check-label fw-bold" for="cldReClean">Vệ sinh lại / Re-Cleaning</label>
                                    </div>
                                </div>
                                <div class="small text-muted mb-2 border-bottom pb-2">
                                    Đánh dấu "v" vào nội dung áp dụng/ Check "v" in used content<br>
                                    Cấp/ Level I : Hiệu lực trong vòng 3 ngày / valid for 3 days<br>
                                    Cấp/ Level II : Hiệu lực trong vòng 7 ngày / valid for 7 days<br>
                                    Vệ sinh lại/ Re-cleaning : Hiệu lực trong 24 giờ / valid for 24 hours
                                </div>
                                <div class="row mb-2 border-bottom pb-2">
                                    <div class="col-6 border-end">
                                        <span class="small fw-bold">Thời gian hoàn tất vệ sinh/<br>Cleaning finished on:</span><br>
                                        Ngày/ Date: <span id="cldFinishedDate" class="fw-bold text-primary">-</span>
                                    </div>
                                    <div class="col-6">
                                        <span class="small fw-bold">Hiệu lực đến/<br>Valid until:</span><br>
                                        Ngày/ Date: <span id="cldValidUntil" class="fw-bold text-danger">-</span>
                                    </div>
                                </div>
                                <div class="row mb-2 border-bottom pb-2">
                                    <div class="col-6 border-end">
                                        <span class="small fw-bold">Người thực hiện/ Done by:</span><br>
                                        <span id="cldDoneBy" class="fst-italic text-primary">-</span>
                                    </div>
                                    <div class="col-6">
                                        <span class="small fw-bold">Người kiểm tra/ Checked by:</span><br>
                                        <span id="cldCheckedBy" class="fst-italic text-success">-</span>
                                    </div>
                                </div>
                                <div class="small fw-bold text-muted mb-1">
                                    Nhãn này được đính kèm vào hồ sơ lô sản phẩm tiếp theo<br>
                                    This label will be attached into next product batch documentation
                                </div>
                                <table class="table table-sm table-borderless mb-0">
                                    <tr>
                                        <td style="width: 30%;" class="small fw-bold px-0">Tên sản phẩm/<br><small>Product's name:</small></td>
                                        <td id="cldNextProduct" class="fw-bold text-primary px-0 align-middle">-</td>
                                    </tr>
                                    <tr>
                                        <td class="small fw-bold px-0">Số lô/<br><small>Batch no.:</small></td>
                                        <td id="cldNextBatch" class="fw-bold text-primary px-0 align-middle">-</td>
                                    </tr>
                                    <tr>
                                        <td class="small fw-bold px-0">Người nhận/<br><small>Received by:</small></td>
                                        <td id="cldAttachedBy" class="fst-italic text-primary px-0 align-middle">-</td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="modal-footer p-2 d-flex justify-content-center bg-light">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>
