@extends('layout.master')

@php
    $issuanceTitleMap = [
        'BMR' => 'Ban Hành Hồ Sơ Lô Sản Xuất',
        'BPR' => 'Ban Hành Hồ Sơ Đóng Gói',
        'GF' => 'Ban Hành Biểu Mẫu Chung',
    ];
    $issuanceHeaderMap = [
        'BMR' => 'Hồ Sơ Sản Xuất Hiện Hành & Ban Hành Lô',
        'BPR' => 'Hồ Sơ Đóng Gói Hiện Hành & Ban Hành Lô',
        'GF' => 'Biểu Mẫu Chung Hiện Hành & Ban Hành',
    ];
    $issuanceIconMap = [
        'BMR' => 'fa-file-export',
        'BPR' => 'fa-box-open',
        'GF' => 'fa-layer-group',
    ];
@endphp

@section('title', $issuanceTitleMap[$current_type] ?? 'Ban Hành Hồ Sơ Lô')

@section('mainContent')
    <div class="content-wrapper">
        <div class="container-fluid py-4">
            <div class="row">
                <div class="col-12">
                    <div class="card border-0 shadow-sm overflow-hidden">
                        <div class="card-header bg-primary py-3 d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 text-white fw-bold"><i class="fas {{ $issuanceIconMap[$current_type] ?? 'fa-file-export' }} me-2"></i>
                                {{ $issuanceHeaderMap[$current_type] ?? 'Hồ Sơ Hiện Hành & Ban Hành Lô' }}</h5>
                            <div class="badge bg-white text-primary rounded-pill px-3">{{ $templates->count() }} Hồ sơ sẵn
                                sàng</div>
                        </div>
                        <div class="card-body p-4">

                            <div class="table-responsive">
                                <table id="issuanceTable" class="table table-hover align-middle" style="width:100%">
                                    <thead class="bg-light text-navy text-center">
                                        <tr>
                                            <th rowspan="2" class="align-middle">Mã Hồ Sơ</th>
                                            <th rowspan="2" class="align-middle">Tên Hồ Sơ</th>
                                            <th rowspan="2" class="align-middle">Ấn Bản</th>
                                            @if ($current_type !== 'GF')
                                                <th rowspan="2" class="align-middle">Cỡ Lô #</th>
                                            @endif
                                            @if ($current_type === 'BMR')
                                                <th rowspan="2" class="align-middle">Dạng Bào Chế</th>
                                                <th colspan="6" class="border-bottom">Công Đoạn Sản Xuất</th>
                                            @endif
                                            @if ($current_type !== 'GF')
                                                <th rowspan="2" class="align-middle">Phân Xưởng</th>
                                            @endif
                                            <th rowspan="2" class="align-middle">Ngày Hiệu Lực</th>
                                            <th rowspan="2" class="align-middle">Thao tác</th>
                                        </tr>
                                        @if ($current_type === 'BMR')
                                            <tr style="font-size: 0.75rem;">
                                                <th class="py-1">Cân NL</th>
                                                <th class="py-1">Cân NL Khác</th>
                                                <th class="py-1">PC</th>
                                                <th class="py-1">THT</th>
                                                <th class="py-1">ĐH</th>
                                                <th class="py-1">BP</th>
                                            </tr>
                                        @endif
                                    </thead>
                                    <tbody>
                                        @foreach ($templates as $t)
                                            <tr>
                                                <td class="fw-bold text-navy">{{ $t->document_code }}</td>
                                                <td style="max-width: 200px;">{{ $t->name }}</td>
                                                <td><span class="badge bg-soft-info">V.{{ $t->version }}</span></td>
                                                @if ($current_type !== 'GF')
                                                    <td>
                                                        <div class="fw-bold text-primary">{{ $t->batch_size }}#</div>
                                                        <div class="small text-muted">{{ str_replace('#', '', $t->batch_size) }}
                                                        </div>
                                                    </td>
                                                @endif

                                                @if ($current_type === 'BMR')
                                                    <td><span
                                                            class="badge bg-light text-dark border">{{ $t->dosage_form }}</span>
                                                    </td>

                                                    {{-- Stages --}}
                                                    @foreach (['pre', 'pre_other', 'pc', 'tht', 'dh', 'bp'] as $sKey)
                                                        <td class="text-center">
                                                            @if (isset($t->stages[$sKey]) && $t->stages[$sKey] !== null)
                                                                <i class="fas fa-check-circle text-primary mb-1"></i>
                                                                <div style="font-size: 0.7rem;" class="text-muted">
                                                                    {{ $t->stages[$sKey] }} ngày</div>
                                                            @endif
                                                        </td>
                                                    @endforeach
                                                @endif

                                                @if ($current_type !== 'GF')
                                                    <td class="text-center"><span
                                                            class="badge bg-secondary">{{ $t->department }}</span></td>
                                                @endif
                                                <td>{{ \Carbon\Carbon::parse($t->effective_date)->format('d/m/Y') }}</td>
                                                <td class="text-center">
                                                    <button class="btn btn-primary rounded-pill px-3 shadow-sm btn-sm"
                                                        onclick="openIssueModal({{ $t->id }}, '{{ $t->name }}', '{{ $t->document_code }}')">
                                                        <i class="fas fa-rocket me-1"></i> Ban hành
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Ban Hành (Issue Record) -->
        <div class="modal fade" id="issueModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content border-0 shadow-lg">
                    <form id="issueForm">
                        @csrf
                        <input type="hidden" id="issueTemplateId" name="template_id">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title font-weight-bold"><i class="fas fa-file-export me-2"></i> Ban Hành Hồ Sơ
                                Lô</h5>
                            <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body p-4">
                            <div class="row">
                                <div class="col-md-7">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold small text-muted text-uppercase">Hồ sơ: <span
                                                id="issueTemplateCodeDisplay" class="text-primary"></span></label>
                                        <div id="issueTemplateNameDisplay"
                                            class="p-2 bg-light rounded text-navy fw-bold border" style="font-size: 1rem;">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-5">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold small text-muted text-uppercase mb-2">Định dạng số
                                            lô</label>
                                        <div class="format-switch">
                                            <input type="radio" name="format" id="format1" value="AAMMYY" checked
                                                onchange="suggestBatchNumber()">
                                            <label for="format1">AAMMYY</label>

                                            <input type="radio" name="format" id="format2" value="YWWAA"
                                                onchange="suggestBatchNumber()">
                                            <label for="format2">YWWAA</label>

                                            <span class="format-switch-slider"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <style>
                                .format-switch {
                                    background: #e9ecef;
                                    border-radius: 50px;
                                    padding: 2px;
                                    position: relative;
                                    display: flex;
                                    width: 100%;
                                    border: 1px solid #dee2e6;
                                }

                                .format-switch input {
                                    display: none;
                                }

                                .format-switch label {
                                    flex: 1;
                                    text-align: center;
                                    padding: 8px 0;
                                    margin: 0;
                                    cursor: pointer;
                                    font-weight: bold;
                                    font-size: 0.85rem;
                                    z-index: 2;
                                    color: #495057;
                                    transition: color 0.3s;
                                }

                                .format-switch-slider {
                                    position: absolute;
                                    width: 50%;
                                    height: calc(100% - 4px);
                                    background: #007bff;
                                    border-radius: 50px;
                                    top: 2px;
                                    left: 2px;
                                    transition: all 0.3s cubic-bezier(0.18, 0.89, 0.32, 1.28);
                                    z-index: 1;
                                    box-shadow: 0 2px 8px rgba(0, 123, 255, 0.3);
                                }

                                #format2:checked~.format-switch-slider {
                                    left: calc(50% - 2px);
                                }

                                #format1:checked~label[for="format1"],
                                #format2:checked~label[for="format2"] {
                                    color: white;
                                }
                            </style>

                            <hr class="my-3">

                            <div class="row">
                                <div class="col-md-8">
                                    <div class="mb-1">
                                        <label class="form-label fw-bold">Số Lô <span id="batchLabelSuffix">Sản
                                                Xuất</span> (Batch No.) <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="text" class="form-control px-3" name="batch_number"
                                                id="batchNumber" required placeholder="VD: 010126"
                                                style="height: 45px; border: 2px solid #007bff; font-weight: bold; font-size: 1.1rem;">
                                            <div class="input-group-append">
                                                <button class="btn btn-outline-primary" type="button"
                                                    onclick="suggestBatchNumber()" title="Gợi ý số lô tiếp theo">
                                                    <i class="fas fa-magic"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div id="batchNumberWarning" class="small mt-1 d-none fw-bold"></div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-1">
                                        <label class="form-label fw-bold">Số lượng lô</label>
                                        <input type="number" class="form-control" name="quantity" id="issueQuantity"
                                            value="1" min="1" max="50" style="height: 45px;">
                                    </div>
                                </div>
                            </div>

                            <p class="text-muted small mt-2"><i class="fas fa-info-circle me-1"></i> Hệ thống sẽ tự động
                                tăng số lô nếu bạn chọn ban hành nhiều lô cùng lúc.</p>
                        </div>
                        <div class="modal-footer bg-light p-3">
                            <button type="button" class="btn btn-light rounded-pill px-4" data-dismiss="modal">Hủy
                                bỏ</button>
                            <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">
                                <i class="fas fa-rocket me-2"></i> Xác nhận Ban Hành
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <style>
        .bg-navy {
            background-color: #003A4F !important;
        }

        .text-navy {
            color: #003A4F !important;
        }

        .bg-soft-info {
            background-color: rgba(23, 162, 184, 0.1);
            color: #17a2b8;
        }
    </style>
@endsection

@section('script')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).ready(function() {
            $('#issuanceTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.10.24/i18n/Vietnamese.json'
                },
                order: [
                    [0, 'asc']
                ]
            });
        });

        function openIssueModal(id, name, code) {
            $('#issueTemplateId').val(id);
            $('#issueTemplateNameDisplay').text(name);
            $('#issueTemplateCodeDisplay').text(code);
            $('#batchNumber').val('').css('border-color', '#007bff');
            $('#batchNumberWarning').addClass('d-none');
            $('#format1').prop('checked', true); // Reset về AAMMYY mặc định
            $('#issueQuantity').val(1);
            $('#issueModal').modal('show');

            // Tự động gợi ý khi mở
            suggestBatchNumber();
        }

        function suggestBatchNumber() {
            const templateId = $('#issueTemplateId').val();
            const format = $('input[name="format"]:checked').val();

            if (format === 'MANUAL') {
                return;
            }

            $.get('{{ url('ebmr/templates') }}/' + templateId + '/next-batch?format=' + format, function(res) {
                if (res.suggestion) {
                    $('#batchNumber').val(res.suggestion).trigger('keyup');
                }
            });
        }

        let checkTimeout = null;
        $('#batchNumber').on('keyup', function() {
            const batchNumber = $(this).val().trim();
            const templateId = $('#issueTemplateId').val();
            const warningEl = $('#batchNumberWarning');
            const input = $(this);

            if (checkTimeout) clearTimeout(checkTimeout);

            if (batchNumber.length === 0) {
                warningEl.addClass('d-none');
                input.css('border-color', '#007bff');
                return;
            }

            checkTimeout = setTimeout(() => {
                $.post('{{ route('pages.ebmr.checkBatchNumber') }}', {
                    _token: '{{ csrf_token() }}',
                    template_id: templateId,
                    batch_number: batchNumber
                }, function(res) {
                    if (res.exists) {
                        warningEl.removeClass('d-none').addClass('text-danger').html(
                            '<i class="fas fa-exclamation-triangle me-1"></i> Số lô này đã tồn tại cho mẫu hồ sơ này!'
                        );
                        input.css('border-color', '#dc3545');
                    } else {
                        warningEl.removeClass('d-none').removeClass('text-danger').addClass(
                            'text-success').html(
                            '<i class="fas fa-check-circle me-1"></i> Số lô hợp lệ');
                        input.css('border-color', '#28a745');
                    }
                });
            }, 300);
        });

        $('#issueForm').on('submit', function(e) {
            e.preventDefault();
            const btn = $(this).find('button[type="submit"]');
            const originalHtml = btn.html();

            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i> Đang xử lý...');

            $.ajax({
                url: '{{ route('pages.ebmr.issueTemplate') }}',
                method: 'POST',
                data: $(this).serialize(),
                success: function(res) {
                    if (res.success) {
                        Swal.fire({
                            title: 'Thành công!',
                            text: res.message,
                            icon: 'success',
                            confirmButtonColor: '#003A4F'
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Lỗi', res.message, 'error');
                        btn.prop('disabled', false).html(originalHtml);
                    }
                },
                error: function(err) {
                    let msg = 'Đã có lỗi xảy ra';
                    if (err.responseJSON && err.responseJSON.message) msg = err.responseJSON.message;
                    Swal.fire('Lỗi', msg, 'error');
                    btn.prop('disabled', false).html(originalHtml);
                }
            });
        });
    </script>
@endsection
