@extends('layout.master')

@section('title', 'Hồ Sơ Sản Xuất BMR')

@section('mainContent')
    <div class="content-wrapper">
        <div class="container-fluid py-4">
            <div class="row">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 text-navy fw-bold">
                                <i
                                    class="fas {{ request('type') == 'GF' ? 'fa-layer-group' : (request('type') == 'BPR' ? 'fa-box-open' : (request('type') == 'MF' ? 'fa-file-invoice' : 'fa-file-medical')) }} me-2"></i>
                                {{ request('type') == 'GF' ? 'Danh Sách Biểu Mẫu Dùng Chung' : (request('type') == 'BPR' ? 'Danh Sách Hồ Sơ Đóng Gói' : (request('type') == 'MF' ? 'Danh Sách Biểu Mẫu Gốc' : 'Danh Sách Hồ Sơ Sản Xuất BMR')) }}
                            </h5>
                            <div class="d-flex gap-2">
                                <button class="btn btn-outline-primary rounded-pill px-4 shadow-sm fw-bold"
                                    onclick="openBtpListModal()">
                                    <i class="fas fa-file-signature me-2"></i> Tạo mới
                                </button>
                            </div>
                        </div>
                        <div class="card-body p-4">
                            <div class="table-responsive">
                                <table id="draftingTable" class="table table-hover align-middle bmr-datatable"
                                    style="width:100%">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>Mã danh mục</th>
                                            <th>{{ request('type') == 'BMR' ? 'Số BMR' : (request('type') == 'BPR' ? 'Số BPR' : 'Số BM gốc') }}</th>
                                            <th>Tên nội dung</th>
                                            <th>Phiên bản</th>
                                            <th>Công đoạn</th>
                                            <th>Trạng thái</th>
                                            <th>Dược sĩ phụ trách</th>
                                            <th>Ngày ban hành</th>
                                            <th>Ngày hiệu lực</th>
                                            <th class="text-center">Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($templates as $t)
                                            <tr>
                                                <td class="fw-bold text-navy">{{ $t->category_code }}</td>
                                                <td class="fw-bold text-primary">{{ $t->doc_code ?? '-' }}</td>
                                                <td>{{ $t->category_name }}</td>
                                                <td><span class="badge bg-soft-info">V.{{ $t->version }}</span></td>
                                                <td>
                                                    <div class="d-flex flex-wrap gap-1">
                                                        @forelse($t->sections as $s)
                                                            <button
                                                                class="btn btn-xs btn-outline-info rounded-pill py-0 px-2"
                                                                style="font-size: 0.7rem;"
                                                                onclick="window.location.href='{{ route('pages.ebmr.designer', $t->id) }}?section={{ $s['id'] }}'">
                                                                {{ $s['label'] }}
                                                            </button>
                                                        @empty
                                                            <span class="text-muted small">N/A</span>
                                                        @endforelse
                                                    </div>
                                                </td>
                                                <td>
                                                    @if ($t->status === 'draft')
                                                        <span class="badge bg-secondary"><i class="fas fa-edit me-1"></i>
                                                            Nháp</span>
                                                    @elseif($t->status === 'submitted')
                                                        <span class="badge bg-warning text-dark"><i
                                                                class="fas fa-clock me-1"></i> Chờ duyệt</span>
                                                    @elseif($t->status === 'approved')
                                                        <span class="badge bg-success"><i
                                                                class="fas fa-check-circle me-1"></i> Đã duyệt</span>
                                                    @elseif($t->status === 'issued')
                                                        @if ($t->effective_date)
                                                            <span class="badge bg-warning text-dark"><i
                                                                    class="fas fa-hourglass-half me-1"></i> Chờ hiệu
                                                                lực</span>
                                                        @else
                                                            <span class="badge bg-info"><i class="fas fa-rocket me-1"></i>
                                                                Đã ban hành</span>
                                                        @endif
                                                    @elseif($t->status === 'active')
                                                        <span class="badge bg-primary"><i
                                                                class="fas fa-check-double me-1"></i>
                                                            Hiệu lực</span>
                                                    @elseif($t->status === 'expired')
                                                        <span class="badge bg-danger"><i class="fas fa-history me-1"></i>
                                                            Hết hiệu lực</span>
                                                    @endif
                                                </td>
                                                <td><i class="fas fa-user-circle me-1 text-muted"></i>
                                                    {{ $t->owner_name ?? 'N/A' }}</td>
                                                <td>{{ $t->issued_date ? \Carbon\Carbon::parse($t->issued_date)->format('d/m/Y') : '-' }}
                                                </td>
                                                <td>{{ $t->effective_date ? \Carbon\Carbon::parse($t->effective_date)->format('d/m/Y') : '-' }}
                                                </td>
                                                <td class="text-center">
                                                    <div class="btn-group shadow-sm rounded-pill overflow-hidden">
                                                        @if ($t->status === 'draft')
                                                            <a href="{{ route('pages.ebmr.designer', $t->id) }}"
                                                                class="btn btn-sm btn-white text-navy"
                                                                title="Thiết kế nội dung">
                                                                <i class="fas fa-pencil-ruler"></i> Thiết kế
                                                            </a>
                                                            <button class="btn btn-sm btn-white text-success"
                                                                onclick="openWorkflowModal({{ $t->id }})"
                                                                title="Trình ký">
                                                                <i class="fas fa-paper-plane"></i> Gửi duyệt
                                                            </button>
                                                        @else
                                                            <a href="{{ route('pages.ebmr.designer', $t->id) }}?mode=review"
                                                                class="btn btn-sm btn-white text-primary"
                                                                title="Xem nội dung">
                                                                <i class="fas fa-eye"></i> Xem hồ sơ
                                                            </a>
                                                        @endif

                                                        @if ($t->issued_date && !$t->effective_date && $t->owner_id == session('user')['userId'])
                                                            <button class="btn btn-sm btn-white text-warning"
                                                                onclick="openEffectiveDateModal({{ $t->id }})"
                                                                title="Xác định ngày hiệu lực">
                                                                <i class="fas fa-calendar-check"></i> Hiệu lực
                                                            </button>
                                                        @endif

                                                        <button class="btn btn-sm btn-white text-info"
                                                            onclick="openEditModal({{ $t->id }})"
                                                            title="Cập nhật thông tin gốc">
                                                            <i class="fas fa-edit"></i> Sửa
                                                        </button>

                                                        @if ($current_type === 'BMR')
                                                            <button class="btn btn-sm btn-white text-warning fw-bold"
                                                                onclick="openTestingModal({{ $t->id }}, '{{ addslashes($t->category_name) }}')"
                                                                title="Tiêu chuẩn kiểm nghiệm">
                                                                <i class="fas fa-clipboard-check text-warning me-1"></i> Tiêu chuẩn
                                                            </button>
                                                        @endif
                                                    </div>
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

        <!-- Modal 1: Danh Sách Bán Thành Phẩm -->
        <div class="modal fade" id="modalBtpList" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header">
                        <h5 class="modal-title font-weight-bold text-info">
                            <i class="fas fa-list me-2"></i>
                            {{ $current_type == 'GF' ? 'Danh Mục Biểu Mẫu Dùng Chung' : ($current_type == 'MF' ? 'Danh Mục Biểu Mẫu Gốc' : ($current_type == 'BPR' ? 'Danh Mục Thành Phẩm (BPR)' : 'Danh Mục Bán Thành Phẩm (BMR)')) }}
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="table-responsive">
                            <table id="btpSelectionTable" class="table table-hover align-middle w-100">
                                <thead class="bg-light">
                                    @if ($current_type == 'GF')
                                        <tr>
                                            <th>Mã Biểu Mẫu</th>
                                            <th>Tên Biểu Mẫu</th>
                                            <th>SOP Liên Quan</th>
                                            <th class="text-center">Hành động</th>
                                        </tr>
                                    @elseif($current_type == 'MF')
                                        <tr>
                                            <th>Mã Biểu Mẫu</th>
                                            <th>Tên Biểu Mẫu</th>
                                            <th>Công Đoạn</th>
                                            <th class="text-center">Hành động</th>
                                        </tr>
                                    @elseif($current_type == 'BPR')
                                        <tr>
                                            <th>Mã Thành Phẩm</th>
                                            <th>Tên Thành Phẩm</th>
                                            <th>Cỡ Lô</th>
                                            <th class="text-center">Hành động</th>
                                        </tr>
                                    @else
                                        <tr>
                                            <th>Mã BTP</th>
                                            <th>Tên Sản Phẩm</th>
                                            <th>Cỡ Lô</th>
                                            <th>Dạng Bào Chế</th>
                                            <th class="text-center">Hành động</th>
                                        </tr>
                                    @endif
                                </thead>
                                <tbody>
                                    @foreach ($category_items as $item)
                                        @if ($current_type == 'GF')
                                            <tr>
                                                <td class="fw-bold text-primary">{{ $item->code }}</td>
                                                <td>{{ $item->name }}</td>
                                                <td>{{ $item->relatived_sop_no }}</td>
                                                <td class="text-center">
                                                    <button class="btn btn-sm btn-primary rounded-pill px-3"
                                                        onclick="selectCategory({{ $item->id }}, '{{ $item->code }}', '{{ addslashes($item->name) }}', 'SOP: {{ $item->relatived_sop_no }}')">
                                                        <i class="fas fa-check me-1"></i> Chọn
                                                    </button>
                                                </td>
                                            </tr>
                                        @elseif($current_type == 'MF')
                                            <tr>
                                                <td class="fw-bold text-primary">{{ $item->code }}</td>
                                                <td>{{ $item->name }}</td>
                                                <td>{{ $item->stage_name }}</td>
                                                <td class="text-center">
                                                    <button class="btn btn-sm btn-primary rounded-pill px-3"
                                                        onclick="selectCategory({{ $item->id }}, '{{ $item->code }}', '{{ addslashes($item->name) }}', 'Công đoạn: {{ $item->stage_name }}')">
                                                        <i class="fas fa-check me-1"></i> Chọn
                                                    </button>
                                                </td>
                                            </tr>
                                        @elseif($current_type == 'BPR')
                                            <tr>
                                                <td class="fw-bold text-primary">{{ $item->finished_product_code }}</td>
                                                <td>{{ $item->product_name }}</td>
                                                <td>{{ $item->batch_qty }}</td>
                                                <td class="text-center">
                                                    <button class="btn btn-sm btn-primary rounded-pill px-3"
                                                        onclick="selectCategory({{ $item->id }}, '{{ $item->finished_product_code }}', '{{ addslashes($item->product_name) }}', 'Cỡ lô: {{ $item->batch_qty }}', {
                                                            7: 1,
                                                            8: 1
                                                        })">
                                                        <i class="fas fa-check me-1"></i> Chọn
                                                    </button>
                                                </td>
                                            </tr>
                                        @else
                                            <tr>
                                                <td class="fw-bold text-primary">{{ $item->intermediate_code }}</td>
                                                <td>{{ $item->product_name }}</td>
                                                <td>{{ $item->batch_size }} {{ $item->unit_batch_size }}</td>
                                                <td>{{ $item->dosage_name ?? 'N/A' }}</td>
                                                <td class="text-center">
                                                    <button class="btn btn-sm btn-primary rounded-pill px-3"
                                                        onclick="selectCategory({{ $item->id }}, '{{ $item->intermediate_code }}', '{{ addslashes($item->product_name) }}', 'Cỡ lô: {{ $item->batch_size }} {{ $item->unit_batch_size }} | Dạng: {{ $item->dosage_name ?? 'N/A' }}', {
                                                            1: 1,
                                                            2: 1,
                                                            3: 1,
                                                            4: 1,
                                                            5: 1,
                                                            6: 1
                                                        }, {{ $item->IsHypothesis ?? 0 }}, {{ $item->batch_qty ?? 0 }}, {{ $item->batch_size ?? 0 }})">
                                                        <i class="fas fa-check me-1"></i> Chọn
                                                    </button>
                                                </td>
                                            </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal 2: Soạn mới / Cập nhật Hồ Sơ -->
        <div class="modal fade" id="templateMetadataModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document" style="max-width: 95%;">
                <form id="metadataForm" class="modal-content border-0 shadow-lg">
                        @csrf
                        <input type="hidden" id="templateId" name="id">
                        <input type="hidden" id="caterogyId" name="caterogy_id">
                        <input type="hidden" id="templateType" name="type" value="{{ request('type', 'BMR') }}">
                        <div class="modal-header">
                            <h5 class="modal-title font-weight-bold text-info" id="modalTitle">
                                <i class="fas fa-file-medical me-2"></i>
                                {{ request('type') == 'GF' ? 'Tạo Biểu Mẫu Dùng Chung' : (request('type') == 'BPR' ? 'Tạo Hồ Sơ Đóng Gói' : (request('type') == 'MF' ? 'Tạo Biểu Mẫu Gốc' : 'Tạo Hồ Sơ Lô Sản Xuất')) }}
                            </h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body p-4">
                            <div class="row">
                                <!-- Left Column (30% / col-lg-4) -->
                                <div class="{{ request('type') == 'BMR' ? 'col-lg-3 border-right pr-4' : 'col-lg-12' }}">
                                    <div class="row align-items-center mb-3">
                                        <div class="col-md-6">
                                            <div class="alert alert-cyan border-0 shadow-none mb-0 p-3">
                                                <div class="d-flex align-items-center">
                                                    <i class="fas fa-info-circle fa-2x me-3 text-primary"></i>
                                                    <div class="w-100" style="min-width: 0;">
                                                        <h6 class="mb-1 fw-bold text-navy small">Sản phẩm đang chọn:</h6>
                                                        <div id="selectedBtpName"
                                                            class="fs-6 text-primary fw-bold text-truncate">Chưa chọn sản
                                                            phẩm</div>
                                                        <div id="selectedBtpInfo" class="small text-muted mt-1"
                                                            style="font-size: 0.75rem;">Cỡ lô: - | Dạng bào chế: -</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group mb-0">
                                                <label class="form-label fw-bold small">{{ request('type') == 'BMR' ? 'Số BMR' : (request('type') == 'BPR' ? 'Số BPR' : 'Số BM gốc') }}</label>
                                                <input type="text"
                                                    class="form-control rounded-pill text-center fw-bold" name="doc_code"
                                                    id="docCode" placeholder="Nhập số...">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group mb-0">
                                                <label class="form-label fw-bold small">Phiên Bản <span
                                                        class="text-danger">*</span></label>
                                                <input type="number"
                                                    class="form-control rounded-pill text-center fw-bold" name="version"
                                                    id="version" required value="1" min="1">
                                                <small class="text-muted d-block mt-1 text-center"
                                                    style="font-size: 0.7rem;"><i class="fas fa-magic me-1"></i> Tự
                                                    động</small>
                                            </div>
                                        </div>
                                    </div>

                                    @include('pages.ebmr.templates.partials.bmr_metadata')

                                </div>
                                <!-- End Left Column -->

                                <!-- Right Column (70% / col-lg-8): BOM Tables -->
                                <div class="col-lg-9 pl-4" id="bmr_bom_tables_container"
                                    style="display: {{ request('type') == 'BMR' ? 'block' : 'none' }};">
                                    @include('pages.ebmr.templates.partials.bmr_bom_tables')
                                </div>
                                <!-- End Right Column -->
                            </div>
                        </div>
                        <div class="modal-footer bg-light p-3">
                            <button type="button" class="btn btn-white rounded-pill px-4" data-dismiss="modal">Hủy
                                bỏ</button>
                            <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm text-white">
                                <i class="fas fa-save me-2 text-white"></i> Lưu hồ sơ
                            </button>
                        </div>
                    </form>
            </div>
        </div>

        <!-- Modal Trình Ký (Workflow) -->
        <div class="modal fade" id="workflowModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content border-0 shadow-lg">
                    <form id="workflowForm">
                        @csrf
                        <input type="hidden" id="workflowTemplateId" name="template_id">
                        <div class="modal-header">
                            <h5 class="modal-title font-weight-bold text-info"><i class="fas fa-paper-plane me-2"></i>
                                Thiết lập luồng Trình Ký</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body p-4">
                            <div class="alert alert-info border-0 shadow-none small mb-4">
                                <i class="fas fa-info-circle me-1"></i> Sau khi trình ký, hồ sơ thiết kế sẽ được gửi đến
                                những người liên quan để xem xét và ban hành.
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold text-navy"><i class="fas fa-search me-1"></i> Người kiểm
                                    tra (Reviewers)</label>
                                <select class="form-select select2-workflow" name="reviewers[]" id="wfReviewers"
                                    multiple="multiple" data-placeholder="Chọn một hoặc nhiều người...">
                                    @foreach ($users as $user)
                                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                                    @endforeach
                                </select>
                                <small class="form-text text-muted">Có thể chọn nhiều người kiểm tra (VD: QA, QC, Sản
                                    xuất).</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold text-navy"><i class="fas fa-check-double me-1"></i> Người
                                    phê duyệt (Approver) <span class="text-danger">*</span></label>
                                <select class="form-select select2-workflow" name="approver" id="wfApprover" required>
                                    <option value="">-- Chọn một người phê duyệt --</option>
                                    @foreach ($users as $user)
                                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                                    @endforeach
                                </select>
                                <small class="form-text text-muted">Trưởng phòng/Giám đốc phê duyệt nội dung.</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold text-navy"><i class="fas fa-file-signature me-1"></i>
                                    Người cho phép ban hành <span class="text-danger">*</span></label>
                                <select class="form-select select2-workflow" name="authorizer" id="wfAuthorizer"
                                    required>
                                    <option value="">-- Chọn một người ban hành --</option>
                                    @foreach ($users as $user)
                                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                                    @endforeach
                                </select>
                                <small class="form-text text-muted">Giám đốc chất lượng/Người đại diện ban hành chính
                                    thức.</small>
                            </div>
                        </div>
                        <div class="modal-footer bg-light p-3">
                            <button type="button" class="btn btn-white rounded-pill px-4" data-dismiss="modal">Hủy
                                bỏ</button>
                            <button type="submit" class="btn btn-success rounded-pill px-4 shadow-sm">
                                <i class="fas fa-paper-plane me-2"></i> Gửi trình ký
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <style>
            :root {
                --primary-navy: #003A4F;
            }

            .bg-navy {
                background-color: var(--primary-navy) !important;
            }

            .text-navy {
                color: var(--primary-navy) !important;
            }

            .btn-navy {
                background-color: var(--primary-navy) !important;
                color: white !important;
            }

            .btn-navy:hover {
                background-color: #002a3a !important;
                color: white !important;
                opacity: 1;
            }

            .bg-soft-info {
                background-color: rgba(23, 162, 184, 0.1);
                color: #17a2b8;
            }

            .form-control:focus {
                border-color: var(--primary-navy);
                box-shadow: 0 0 0 0.2rem rgba(0, 58, 79, 0.15);
            }

            .rounded-pill {
                border-radius: 50px !important;
            }

            /* Bỏ bo góc khối bao Summernote editor */
            .note-editor.note-frame.card {
                border-radius: 0 !important;
                box-shadow: none !important;
                border: 1px solid #ced4da !important;
            }

            .note-editor .note-toolbar {
                border-radius: 0 !important;
                border-bottom: 1px solid #ced4da !important;
                background: #f8f9fa !important;
            }

            /* Tăng kích thước Modal */
            @media (min-width: 1200px) {
                .modal-xl {
                    max-width: 95% !important;
                }
            }
        </style>

        <!-- Modal Ban Hành (Issue Record) -->
        <div class="modal fade" id="issueModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content border-0 shadow-lg">
                    <form id="issueForm">
                        @csrf
                        <input type="hidden" id="issueTemplateId" name="template_id">
                        <div class="modal-header">
                            <h5 class="modal-title font-weight-bold text-info"><i class="fas fa-file-export me-2"></i> Ban
                                Hành Hồ
                                Sơ Lô
                            </h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body p-4">
                            <div class="mb-3">
                                <label class="form-label fw-bold small text-muted text-uppercase">Hồ sơ mẫu đang
                                    chọn</label>
                                <div id="issueTemplateNameDisplay"
                                    class="p-3 mb-3 bg-light rounded text-navy fw-bold border" style="font-size: 1.1rem;">
                                </div>
                            </div>

                            <div class="mb-1">
                                <label class="form-label fw-bold">Số Lô Sản Xuất (Batch No.) <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control rounded-pill px-4" name="batch_number"
                                    id="batchNumber" required placeholder="VD: 010126"
                                    style="height: 50px; border: 2px solid #007bff; font-weight: bold; font-size: 1.2rem;">
                            </div>
                            <p class="text-muted small mt-2"><i class="fas fa-info-circle me-1"></i> Số lô này sẽ được
                                dùng để
                                định danh cho hồ sơ lô sản xuất thực tế trên hệ thống.</p>
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
        <!-- Modal Ghi chú chia phần -->
        <div class="modal fade" id="subNoteModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header">
                        <h5 class="modal-title font-weight-bold text-info"><i class="fas fa-edit me-2"></i> Ghi chú chia
                            phần</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="form-group mb-0">
                            <label class="form-label fw-bold">Nội dung ghi chú</label>
                            <textarea class="form-control" id="subNoteTextarea" rows="4" placeholder="Nhập ghi chú tại đây..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer bg-light p-3">
                        <button type="button" class="btn btn-light rounded-pill px-4" data-dismiss="modal">Hủy
                            bỏ</button>
                        <button type="button" class="btn btn-navy rounded-pill px-4 shadow-sm" id="btnSaveSubNote">
                            <i class="fas fa-save me-2"></i> Lưu Ghi Chú
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal 3: Thiết lập Tiêu chuẩn kiểm nghiệm (Testing Criteria) -->
        <div class="modal fade" id="modalTesting" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document" style="max-width: 95%;">
                <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
                    <div class="modal-header bg-navy text-white py-3">
                        <h5 class="modal-title font-weight-bold text-white d-flex align-items-center">
                            <i class="fas fa-clipboard-check me-2 text-warning fs-4"></i>
                            <div>
                                <span>Thiết Lập Tiêu Chuẩn Kiểm Nghiệm</span>
                                <span class="d-block small text-light fw-normal mt-1" id="testingTemplateNameDisplay" style="font-size: 0.85rem; opacity: 0.85;">Hồ sơ: ...</span>
                            </div>
                        </h5>
                        <button type="button" class="close text-white border-0 bg-transparent fs-4" data-dismiss="modal" aria-label="Close" style="outline: none;">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body p-0 d-flex" style="height: 70vh; min-height: 500px;">
                        <!-- Left Stage Sidebar -->
                        <div class="border-end bg-light p-3" style="width: 280px; overflow-y: auto;">
                            <h6 class="text-uppercase text-muted fw-bold mb-3 small" style="letter-spacing: 1px;">Công đoạn qui trình</h6>
                            <div class="list-group list-group-flush testing-stage-list" id="testingStageList">
                                <!-- Dynamic stage tabs -->
                            </div>
                        </div>

                        <!-- Right Panel -->
                        <div class="flex-grow-1 p-4 d-flex flex-column" style="overflow-y: auto;">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="fw-bold text-navy mb-0 d-flex align-items-center">
                                    <i class="fas fa-flask me-2 text-info"></i>
                                    <span id="activeStageTitle">Chọn công đoạn</span>
                                </h5>
                                <div class="text-muted small">
                                    Cấu hình các chỉ tiêu kiểm nghiệm cho công đoạn này.
                                </div>
                            </div>

                            <!-- Table container -->
                            <div class="table-responsive flex-grow-1 border rounded" style="background-color: #fff;">
                                <table class="table align-middle mb-0 testing-table">
                                    <thead>
                                        <tr>
                                            <th style="width: 60px;" class="text-center">STT</th>
                                            <th style="width: 180px;">Chỉ tiêu kiểm</th>
                                            <th style="width: 300px;">Tiêu chuẩn</th>
                                            <th style="width: 180px;">Giới hạn</th>
                                            <th style="width: 300px;">Ghi chú</th>
                                            <th style="width: 150px;" class="text-center">Hình ảnh</th>
                                            <th style="width: 60px;" class="text-center">Xóa</th>
                                        </tr>
                                    </thead>
                                    <tbody id="testingTableBody">
                                        <!-- Dynamic rows for active stage -->
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-3">
                                <button type="button" class="btn btn-outline-primary rounded-pill px-4 shadow-sm fw-bold btn-add-row-action">
                                    <i class="fas fa-plus me-1"></i> Thêm chỉ tiêu kiểm
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light p-3 border-top">
                        <button type="button" class="btn btn-white rounded-pill px-4" data-dismiss="modal">Hủy bỏ</button>
                        <button type="button" class="btn btn-navy rounded-pill px-4 shadow-sm" id="btnSaveTesting">
                            <i class="fas fa-save me-2"></i> Lưu Tiêu Chuẩn
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal 3A: Quản lý hình ảnh (Manage Images Sub-Modal) -->
        <div class="modal fade" id="modalManageImages" tabindex="-1" role="dialog" aria-hidden="true" style="z-index: 1060;">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document">
                <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; height: 80vh; max-height: 700px;">
                    <div class="modal-header bg-info text-white py-3">
                        <h5 class="modal-title font-weight-bold text-white d-flex align-items-center">
                            <i class="fas fa-images me-2"></i>
                            <div>
                                <span>Cấu Hình Hình Ảnh Đính Kèm</span>
                                <span class="d-block small text-light fw-normal mt-1" id="manageImagesRowTitle" style="font-size: 0.8rem; opacity: 0.85;">Chỉ tiêu: ...</span>
                            </div>
                        </h5>
                        <button type="button" class="close text-white border-0 bg-transparent fs-4" onclick="$('#modalManageImages').modal('hide');" aria-label="Close" style="outline: none;">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body p-4" style="overflow-y: auto;">
                        <input type="file" id="testingImageFileInput" accept="image/*" class="d-none" multiple>
                        <div id="manageImagesList" class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-3">
                            <!-- Dynamic image cards + upload card -->
                        </div>
                    </div>
                    <div class="modal-footer bg-light p-3 border-top">
                        <button type="button" class="btn btn-secondary rounded-pill px-4 fw-bold shadow-sm" onclick="$('#modalManageImages').modal('hide');">Đồng ý & Đóng</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal 3B: Carousel Viewer (Xem Hình Ảnh Carousel Card) -->
        <div class="modal fade" id="modalCarouselViewer" tabindex="-1" role="dialog" aria-hidden="true" style="z-index: 1070;">
            <div class="modal-dialog modal-dialog-centered modal-xl lightbox-carousel-modal" role="document">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header border-0 text-dark py-3 d-flex justify-content-between align-items-center lightbox-carousel-header">
                        <h5 class="modal-title font-weight-bold text-dark d-flex align-items-center">
                            <i class="fas fa-eye me-2 text-warning"></i>
                            <span id="carouselViewerTitle" style="font-size: 1.1rem; letter-spacing: 0.3px;">Xem hình ảnh minh họa</span>
                        </h5>
                        <div class="lightbox-toolbar">
                            <button type="button" class="close text-dark border-0 bg-transparent fs-4 p-0 m-0" onclick="$('#modalCarouselViewer').modal('hide');" aria-label="Close" style="outline: none; opacity: 0.85; line-height: 1;">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    </div>
                    <div class="modal-body p-0 d-flex justify-content-center align-items-center" style="background-color: transparent; min-height: 650px; height: 75vh; position: relative;">
                        <div id="testingCarousel" class="carousel slide w-100 h-100" data-ride="carousel">
                            <ol class="carousel-indicators" id="testingCarouselIndicators" style="bottom: 120px;">
                                <!-- Dynamic indicators -->
                            </ol>
                            <div class="carousel-inner" id="testingCarouselInner">
                                <!-- Dynamic slides -->
                            </div>
                            <a class="carousel-control-prev-premium" href="#testingCarousel" role="button" data-slide="prev" title="Ảnh trước">
                                <i class="fas fa-chevron-left fa-lg"></i>
                            </a>
                            <a class="carousel-control-next-premium" href="#testingCarousel" role="button" data-slide="next" title="Ảnh sau">
                                <i class="fas fa-chevron-right fa-lg"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <style>
            /* Lightbox Carousel Premium CSS */
            .lightbox-carousel-modal {
                max-width: 1150px;
                width: 95%;
            }
            .lightbox-carousel-modal .modal-content {
                background: rgba(255, 255, 255, 0.98) !important;
                backdrop-filter: blur(20px);
                border: 1px solid rgba(0, 0, 0, 0.08);
                border-radius: 16px;
                overflow: hidden;
                box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15) !important;
            }
            .lightbox-carousel-header {
                background: rgba(248, 250, 252, 0.85) !important;
                border-bottom: 1px solid rgba(0, 0, 0, 0.06);
            }
            .carousel-item-premium {
                height: calc(100% - 150px);
                text-align: center;
                background-color: transparent;
                position: relative;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .carousel-item-premium img {
                max-height: 100%;
                max-width: 100%;
                object-fit: contain;
                border-radius: 8px;
                box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12);
                transition: transform 0.3s ease;
            }
            .carousel-caption-premium {
                position: absolute;
                bottom: 15px;
                left: 50%;
                transform: translateX(-50%);
                background: rgba(15, 23, 42, 0.85) !important;
                backdrop-filter: blur(12px);
                border: 1px solid rgba(255, 255, 255, 0.15);
                color: #fff;
                padding: 12px 24px;
                text-align: left;
                width: 90%;
                max-width: 800px;
                border-radius: 14px;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            }
            .carousel-caption-premium h6 {
                font-size: 0.95rem;
                font-weight: 700;
                margin-bottom: 4px;
                color: #f8fafc;
            }
            .carousel-caption-premium p {
                font-size: 0.8rem;
                color: #cbd5e1;
                margin-bottom: 0;
                line-height: 1.4;
            }
            .carousel-control-prev-premium,
            .carousel-control-next-premium {
                width: 48px;
                height: 48px;
                background: rgba(15, 23, 42, 0.06);
                border: 1px solid rgba(15, 23, 42, 0.08);
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                position: absolute;
                top: calc(50% - 75px);
                transform: translateY(-50%);
                transition: all 0.2s ease;
                color: #1e293b;
                opacity: 0.8;
            }
            .carousel-control-prev-premium:hover,
            .carousel-control-next-premium:hover {
                background: rgba(15, 23, 42, 0.12);
                opacity: 1;
                color: #0f172a;
                text-decoration: none;
            }
            .carousel-control-prev-premium {
                left: 20px;
            }
            .carousel-control-next-premium {
                right: 20px;
            }
            .lightbox-carousel-modal .carousel-indicators li {
                background-color: #94a3b8 !important;
            }
            .lightbox-carousel-modal .carousel-indicators li.active {
                background-color: #0f172a !important;
            }
            .lightbox-toolbar {
                display: flex;
                align-items: center;
                gap: 12px;
            }
            .lightbox-btn {
                background: rgba(15, 23, 42, 0.06);
                border: 1px solid rgba(15, 23, 42, 0.08);
                color: #334155;
                border-radius: 8px;
                padding: 6px 12px;
                font-size: 0.85rem;
                transition: all 0.2s ease;
                display: flex;
                align-items: center;
                gap: 6px;
            }
            .lightbox-btn:hover {
                background: rgba(15, 23, 42, 0.12);
                color: #0f172a;
                text-decoration: none;
            }
            .border-dashed {
                border-style: dashed !important;
            }

            .testing-stage-list .list-group-item {
                border: none;
                border-radius: 10px !important;
                margin-bottom: 6px;
                font-weight: 600;
                color: #555;
                transition: all 0.2s ease;
                cursor: pointer;
                padding: 10px 15px;
            }
            .testing-stage-list .list-group-item:hover {
                background-color: #e9ecef;
                color: #003A4F;
            }
            .testing-stage-list .list-group-item.active {
                background-color: #003A4F !important;
                color: #fff !important;
                box-shadow: 0 4px 8px rgba(0, 58, 79, 0.15);
            }
            .testing-stage-list .list-group-item .badge {
                font-size: 0.75rem;
                padding: 4px 8px;
            }
            .testing-table th {
                background-color: #f8f9fa;
                color: #003A4F;
                font-weight: bold;
                font-size: 0.8rem;
                border-bottom: 2px solid #dee2e6;
                padding: 12px 10px;
            }
            .testing-table td {
                padding: 10px;
                vertical-align: top;
            }
            .btn-xs {
                padding: 1px 5px;
                font-size: 0.75rem;
                line-height: 1.5;
                border-radius: 3px;
            }
            .spec-input-group {
                margin-bottom: 4px;
            }
            .autofit-textarea {
                resize: none;
                overflow-y: hidden;
                min-height: 31px;
                padding-top: 5px;
                padding-bottom: 5px;
                line-height: 1.5;
            }
            .note-editor.note-frame {
                border: 1px solid #dee2e6 !important;
                border-radius: 8px !important;
            }
            .note-editor.note-frame .note-editable {
                padding: 8px 12px !important;
                line-height: 1.5 !important;
                min-height: 48px !important;
            }
            .note-editor.note-frame .note-placeholder {
                padding: 8px 12px !important;
            }
        </style>
    @endsection

    @section('script')
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            $(document).ready(function() {
                // Initialize the main drafting table
                $('.bmr-datatable').DataTable({
                    language: {
                        url: '{{ asset('vendor/datatables/i18n/Vietnamese.json') }}'
                    },
                    order: [
                        [0, 'asc']
                    ]
                });

                // Initialize the BTP selection table in modal
                $('#btpSelectionTable').DataTable({
                    language: {
                        url: '{{ asset('vendor/datatables/i18n/Vietnamese.json') }}'
                    },
                    pageLength: 10
                });

                $('#metadataForm').submit(function(e) {
                    e.preventDefault();
                    
                    const data = $(this).serialize();

                    $.post('{{ route('pages.ebmr.storeTemplateMetadata') }}', data, function(res) {
                        if (res.success) {
                            Swal.fire('Thành công', res.message, 'success').then(() => {
                                location.reload();
                            });
                        }
                    });
                });


                // Prefill logic from URL if needed
                const urlParams = new URLSearchParams(window.location.search);
                const prefillBtpId = urlParams.get('prefill_btp_id');
                if (prefillBtpId) {
                    openBtpListModal();
                    window.history.replaceState({}, document.title, window.location.pathname);
                }
            });

            function openBtpListModal() {
                $('#modalBtpList').modal('show');
            }

            function selectCategory(id, code, name, info, stages = {}, isHypothesis = 0, batchQty = 0, batchSize = 0) {
                $('#modalBtpList').modal('hide');

                openCreateModal();
                $('#caterogyId').val(id);
                $('#templateType').val(new URLSearchParams(window.location.search).get('type') || 'BMR');
                $('#selectedBtpName').html(code + ' - ' + name);
                $('#selectedBtpInfo').html(`<i class="fas fa-info-circle me-1"></i> ${info}`);
                window.currentBatchQty = batchQty;
                window.currentBatchSize = batchSize;

                const type = $('#templateType').val();

                // Fetch next version
                $('#version').val('...').prop('disabled', true);
                $.get('{{ route('pages.ebmr.getNextVersion') }}', {
                    category_id: id,
                    type: $('#templateType').val()
                }, function(res) {
                    $('#version').val(res.next_version).prop('disabled', false);
                });

                // Fetch ERP Recipe and auto-populate BOM table type 0
                $('#bom_table_body_type_0').empty();
                $('#bom_table_body_type_1').empty();
                bomRowIndex = 0; // Reset index

                if (type === 'BMR') {
                    addBOMRow(0, 'bom_table_body_type_0');
                    addBOMRow(1, 'bom_table_body_type_1');
                }
            }

            function openIssueModal(id, name) {
                $('#issueTemplateId').val(id);
                $('#issueTemplateNameDisplay').text(name);
                $('#batchNumber').val('');
                $('#issueModal').modal('show');
            }

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

            function openCreateModal() {
                $('#metadataForm')[0].reset();
                $('#templateId').val('');
                if ($.fn.summernote) {
                    if ($('#create_description_editor').length) $('#create_description_editor').summernote('code', '');
                    if ($('#create_storage_conditions_editor').length) $('#create_storage_conditions_editor').summernote('code', '');
                }
                $('#enable_recalculation').prop('checked', false);
                $('#modalTitle').html('<i class="fas fa-file-medical me-2"></i> Soạn Mới Hồ Sơ Gốc');
                $('#templateMetadataModal').modal('show');
            }

            function openEditModal(id) {
                $('#metadataForm')[0].reset();
                $('#modalTitle').html('<i class="fas fa-cog me-2"></i> Cập Nhật Thông Tin Hồ Sơ Gốc');

                $.get(`/ebmr/templates/${id}/data`, function(data) {
                    $('#templateId').val(data.id);
                    $('#caterogyId').val(data.caterogy_id);
                    $('#version').val(data.version);
                    $('#docCode').val(data.doc_code || '');
                    $('#statusDisplay').val(data.status);

                    if (data.type === 'BMR') {
                        window.currentBatchQty = data.batch_qty || 0;
                        window.currentBatchSize = data.batch_size || 0;
                        $('#bmr_specific_fields').show();
                        $('select[name="dosage_id"]').val(data.dosage_id);
                        $('input[name="avg_core"]').val(data.avg_core);
                        $('input[name="average_unit_weight"]').val(data.average_unit_weight);
                        $('input[name="API_name"]').val(data.API_name);
                        $('input[name="content"]').val(data.content);
                        if ($('#create_description_editor').length) {
                            $('#create_description_editor').summernote('code', data.description || '');
                            $('#create_description_input').val(data.description || '');
                        }
                        if ($('#create_storage_conditions_editor').length) {
                            $('#create_storage_conditions_editor').summernote('code', data.storage_conditions || '');
                            $('#create_storage_conditions_input').val(data.storage_conditions || '');
                        }

                        if (data.bom && window.renderBOMRows) {
                            window.renderBOMRows(data.bom);
                        }
                        
                        // Handle recalculation loading
                        $('#enable_recalculation').prop('checked', data.is_recalculation == 1);
                    } else {
                        $('#bmr_specific_fields').hide();
                    }

                    $('#templateMetadataModal').modal('show');
                });
            }

            function openWorkflowModal(id) {
                $('#workflowForm')[0].reset();
                $('#workflowTemplateId').val(id);

                // Reset selections
                $('#wfReviewers').val([]).trigger('change');
                $('#wfApprover').val('').trigger('change');
                $('#wfAuthorizer').val('').trigger('change');

                // Load existing workflows if any
                $.get(`/ebmr/templates/${id}/workflow`, function(data) {
                    let reviewers = [];
                    data.forEach(item => {
                        if (item.role === 'reviewer') reviewers.push(item.user_id);
                        if (item.role === 'approver') $('#wfApprover').val(item.user_id).trigger('change');
                        if (item.role === 'authorizer') $('#wfAuthorizer').val(item.user_id).trigger('change');
                    });
                    $('#wfReviewers').val(reviewers).trigger('change');
                    $('#workflowModal').modal('show');
                });
            }

            $('#workflowForm').submit(function(e) {
                e.preventDefault();
                const id = $('#workflowTemplateId').val();
                const data = $(this).serialize();

                $.post(`/ebmr/templates/${id}/workflow`, data, function(res) {
                    if (res.success) {
                        Swal.fire('Thành công', res.message, 'success').then(() => {
                            $('#workflowModal').modal('hide');
                            location.reload();
                        });
                    }
                });
            });

            function openEffectiveDateModal(id) {
                Swal.fire({
                    title: 'Xác định ngày hiệu lực',
                    html: `
                    <div class="text-left mt-3">
                        <label class="form-label fw-bold text-navy">Chọn ngày hiệu lực:</label>
                        <input type="date" id="swalEffDate" class="form-control rounded-pill" value="${new Date().toISOString().split('T')[0]}">
                        <small class="text-muted mt-2 d-block"><i class="fas fa-info-circle me-1"></i> Hồ sơ sẽ chính thức có hiệu lực từ ngày này.</small>
                    </div>
                `,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: '<i class="fas fa-check me-1"></i> Xác nhận',
                    cancelButtonText: 'Hủy bỏ',
                    confirmButtonColor: '#f0ad4e',
                    reverseButtons: true,
                    preConfirm: () => {
                        const date = document.getElementById('swalEffDate').value;
                        if (!date) {
                            Swal.showValidationMessage('Vui lòng chọn ngày hiệu lực');
                        }
                        return date;
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Đang xử lý...',
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        $.post('{{ route('pages.ebmr.updateEffectiveDate') }}', {
                            _token: '{{ csrf_token() }}',
                            id: id,
                            effective_date: result.value
                        }, function(res) {
                            if (res.success) {
                                Swal.fire('Thành công', res.message, 'success').then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire('Lỗi', res.message, 'error');
                            }
                        }).fail(function() {
                            Swal.fire('Lỗi', 'Không thể kết nối đến máy chủ', 'error');
                        });
                    }
                });
            }

            // JavaScript implementation for BMR Template Testing Criteria
            let currentTestingTemplateId = null;
            let testingStages = [];
            let testingData = [];
            let activeStageId = null;
            let testingRowImages = {}; // Format: { row_xxxx: [ { image_path, image_name, image_description } ] }
            let currentManagingRowId = null;

            function openTestingModal(templateId, templateName) {
                currentTestingTemplateId = templateId;
                $('#testingTemplateNameDisplay').text('Hồ sơ BMR: ' + templateName);
                
                // Show loading overlay
                Swal.fire({
                    title: 'Đang tải dữ liệu...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                $.get(`/ebmr/templates/${templateId}/testing-data`, function(res) {
                    Swal.close();
                    if (res.success) {
                        testingStages = res.sections;
                        testingData = res.testing;
                        testingRowImages = {}; // Reset image map

                        // Render sidebar stage list
                        const stageList = $('#testingStageList');
                        stageList.empty();

                        if (testingStages.length === 0) {
                            stageList.html('<div class="text-muted p-3 text-center small">Không có công đoạn nào trong thiết kế.</div>');
                            $('#activeStageTitle').text('Không có công đoạn');
                            $('#testingTableBody').empty();
                            $('.btn-add-row-action').hide();
                            $('#modalTesting').modal('show');
                            return;
                        }

                        $('.btn-add-row-action').show();

                        // Count how many criteria items are already saved for each stage
                        testingStages.forEach((stage, idx) => {
                            const count = testingData.filter(d => d.stage === stage.id).length;
                            const badgeHtml = count > 0 ? `<span class="badge bg-soft-info badge-pill ml-auto">${count}</span>` : '';
                            
                            const activeClass = idx === 0 ? 'active' : '';
                            stageList.append(`
                                <div class="list-group-item list-group-item-action ${activeClass} d-flex justify-content-between align-items-center" 
                                     data-stage-id="${stage.id}" onclick="selectTestingStage('${stage.id}', '${escapeHtml(stage.label)}')">
                                    <span>${stage.label}</span>
                                    ${badgeHtml}
                                </div>
                            `);
                        });

                        // Select the first stage by default
                        selectTestingStage(testingStages[0].id, testingStages[0].label);

                        $('#modalTesting').modal('show');
                    } else {
                        Swal.fire('Lỗi', res.message || 'Không thể tải dữ liệu tiêu chuẩn', 'error');
                    }
                }).fail(function() {
                    Swal.close();
                    Swal.fire('Lỗi', 'Không thể kết nối đến máy chủ', 'error');
                });
            }

            function selectTestingStage(stageId, stageLabel) {
                // 1. Save current active stage data if any
                if (activeStageId !== null) {
                    saveActiveStageToLocalMemory();
                }

                // 2. Set new active stage
                activeStageId = stageId;
                $('#activeStageTitle').text(stageLabel);

                // Update active class in list-group
                $('#testingStageList .list-group-item').removeClass('active');
                $(`#testingStageList .list-group-item[data-stage-id="${stageId}"]`).addClass('active');

                // 3. Render the table body with current stage data
                renderTestingRows(stageId);
            }

            function generateRowId() {
                return 'row_' + Math.random().toString(36).substr(2, 9);
            }

            function initRowEditors(rowEl) {
                rowEl.find('.testing-editor').each(function() {
                    const editor = $(this);
                    editor.summernote({
                        height: 48, // 2 lines default height
                        focus: false,
                        dialogsInBody: true,
                        toolbar: [],
                        placeholder: 'Nhập nội dung...'
                    });
                });
            }

            function destroyRowEditors(rowEl) {
                rowEl.find('.testing-editor').each(function() {
                    if ($.fn.summernote) {
                        $(this).summernote('destroy');
                    }
                });
            }

            function destroyAllEditors() {
                $('#testingTableBody .testing-editor').each(function() {
                    if ($.fn.summernote) {
                        $(this).summernote('destroy');
                    }
                });
            }

            function saveActiveStageToLocalMemory() {
                if (activeStageId === null) return;

                const rows = [];
                $('#testingTableBody tr').each(function() {
                    const row = $(this);
                    const rowId = row.attr('data-row-id');
                    if (!rowId) return;

                    const stt = row.find('input[name="stt"]').val();
                    const name = row.find('[name="indicator_name"]').val().trim();
                    
                    // Retrieve HTML from summernote
                    const specification = row.find('textarea[name="specification"]').summernote('code');
                    const note = row.find('textarea[name="note"]').summernote('code');

                    // Collect limits
                    const op = row.find('select[name="limit_operator"]').val();
                    const val = row.find('[name="limit_value"]').val().trim();
                    const valHigh = row.find('[name="limit_value_high"]').val().trim();
                    const unit = row.find('[name="limit_unit"]').val().trim();
                    const limits = {
                        operator: op,
                        value: val,
                        value_high: valHigh,
                        unit: unit
                    };

                    // Retrieve images from our global map
                    const images = testingRowImages[rowId] || [];
                    const dbId = row.attr('data-id');

                    // Only save rows that have at least some data
                    const isSpecEmpty = (specification === '<p><br></p>' || specification.trim() === '');
                    const isNoteEmpty = (note === '<p><br></p>' || note.trim() === '');

                    if (name || !isSpecEmpty || val || valHigh || !isNoteEmpty || images.length > 0) {
                        rows.push({
                            id: dbId ? parseInt(dbId) : null,
                            stage: activeStageId,
                            stt: parseInt(stt) || 1,
                            name: name,
                            specifictions: isSpecEmpty ? '' : specification,
                            limits: limits,
                            note: isNoteEmpty ? '' : note,
                            images: images
                        });
                    }
                });

                // Remove previous records for this stage in our local array
                testingData = testingData.filter(d => d.stage !== activeStageId);
                
                // Add the updated ones
                testingData.push(...rows);

                // Update the badge count for the active stage tab
                updateStageBadgeCount(activeStageId, rows.length);
            }

            function updateStageBadgeCount(stageId, count) {
                const item = $(`#testingStageList .list-group-item[data-stage-id="${stageId}"]`);
                item.find('.badge').remove();
                if (count > 0) {
                    item.append(`<span class="badge bg-soft-info badge-pill ml-auto">${count}</span>`);
                }
            }

            function renderTestingRows(stageId) {
                // Destroy existing editors to avoid memory leak
                destroyAllEditors();

                const body = $('#testingTableBody');
                body.empty();

                // Get rows for this stage
                const rows = testingData.filter(d => d.stage === stageId);

                if (rows.length === 0) {
                    // Add one default empty row
                    addTestingRow(1, null);
                } else {
                    // Render existing rows
                    rows.forEach((row, idx) => {
                        addTestingRow(row.stt || (idx + 1), row);
                    });
                }

                // Global resize of textareas after loading the stage
                setTimeout(() => {
                    $('.autofit-textarea').each(function() {
                        this.style.height = 'auto';
                        this.style.height = (this.scrollHeight) + 'px';
                    });
                }, 100);
            }

            function addTestingRow(stt, data = null) {
                const body = $('#testingTableBody');
                const rowId = generateRowId();
                
                const name = data ? escapeHtml(data.name) : '';
                const op = data && data.limits ? data.limits.operator : '=';
                const limitVal = data && data.limits ? escapeHtml(data.limits.value) : '';
                const limitValHigh = data && data.limits ? escapeHtml(data.limits.value_high || '') : '';
                const limitUnit = data && data.limits ? escapeHtml(data.limits.unit || '') : '';
                
                // Specifications HTML (either string or array from legacy data)
                let specsVal = '';
                if (data) {
                    if (Array.isArray(data.specifictions)) {
                        specsVal = '<ul>' + data.specifictions.map(s => `<li>${s}</li>`).join('') + '</ul>';
                    } else {
                        specsVal = data.specifictions || '';
                    }
                }
                const noteVal = data ? (data.note || '') : '';
                
                // Images list
                const rowImages = data && Array.isArray(data.images) ? data.images : [];
                testingRowImages[rowId] = rowImages;

                const hasImages = rowImages.length > 0;
                const carouselBtnStyle = hasImages ? '' : 'display: none;';

                // Determine double input placeholders and visibilities
                const isTwoInputs = (op === 'range' || op === '±');
                const valPlaceholder = op === 'range' ? 'Từ...' : 'Giá trị...';
                const valHighPlaceholder = op === 'range' ? 'Đến...' : '±...';
                const valHighDisplay = isTwoInputs ? 'block' : 'none';

                const rowHtml = `
                    <tr class="testing-row-tr" data-row-id="${rowId}" data-id="${data && data.id ? data.id : ''}">
                        <td class="text-center">
                            <input type="number" class="form-control text-center px-1" name="stt" value="${stt}" style="width: 60px;">
                        </td>
                        <td>
                            <textarea class="form-control autofit-textarea" name="indicator_name" rows="1" placeholder="VD: Khối lượng trung bình, Độ rã...">${name}</textarea>
                        </td>
                        <td>
                            <textarea class="form-control testing-editor" name="specification">${specsVal}</textarea>
                        </td>
                        <td>
                            <div class="d-flex align-items-start limit-wrapper" style="gap: 4px; width: 100%;">
                                <select class="form-select form-control" name="limit_operator" style="width: 80px; flex-shrink: 0; height: 31px !important; padding: 4px 6px; font-size: 0.85rem;">
                                    <option value="=" ${op === '=' ? 'selected' : ''}>=</option>
                                    <option value=">" ${op === '>' ? 'selected' : ''}>&gt;</option>
                                    <option value="<" ${op === '<' ? 'selected' : ''}>&lt;</option>
                                    <option value=">=" ${op === '>=' ? 'selected' : ''}>&ge;</option>
                                    <option value="<=" ${op === '<=' ? 'selected' : ''}>&le;</option>
                                    <option value="range" ${op === 'range' ? 'selected' : ''}>Khoảng</option>
                                    <option value="±" ${op === '±' ? 'selected' : ''}>&plusmn;</option>
                                    <option value="N/A" ${op === 'N/A' ? 'selected' : ''}>N/A</option>
                                </select>
                                <textarea class="form-control form-control-sm autofit-textarea limit-val-input" name="limit_value" rows="1" placeholder="${valPlaceholder}" style="flex-grow: 1; width: 0; min-width: 50px;">${limitVal}</textarea>
                                <textarea class="form-control form-control-sm autofit-textarea limit-val-high-input" name="limit_value_high" rows="1" placeholder="${valHighPlaceholder}" style="flex-grow: 1; width: 0; min-width: 50px; display: ${valHighDisplay};">${limitValHigh}</textarea>
                                <textarea class="form-control form-control-sm autofit-textarea limit-unit-input" name="limit_unit" rows="1" placeholder="Đơn vị" style="width: 65px; flex-shrink: 0; min-width: 65px;">${limitUnit}</textarea>
                            </div>
                        </td>
                        <td>
                            <textarea class="form-control testing-editor" name="note">${noteVal}</textarea>
                        </td>
                        <td class="text-center">
                            <div class="d-flex flex-column align-items-center">
                                <button type="button" class="btn btn-sm btn-outline-info btn-manage-images mb-1" style="font-weight: bold; font-size: 0.8rem; padding: 4px 10px;" title="Quản lý ảnh đính kèm">
                                    <i class="fas fa-images me-1"></i>Ảnh (<span class="images-count">${rowImages.length}</span>)
                                </button>
                                <button type="button" class="btn btn-sm btn-warning text-white btn-view-carousel" style="${carouselBtnStyle}; font-weight: bold; font-size: 0.8rem; padding: 4px 10px;" title="Xem Carousel">
                                    <i class="fas fa-eye me-1"></i>Xem hình
                                </button>
                            </div>
                        </td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-outline-danger btn-remove-testing-row" title="Xóa chỉ tiêu này">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </td>
                    </tr>
                `;

                const tr = $(rowHtml);
                body.append(tr);
                
                // Initialize editors for this row
                initRowEditors(tr);

                // Auto-resize textareas to fit current content
                setTimeout(() => {
                    tr.find('.autofit-textarea').each(function() {
                        this.style.height = 'auto';
                        this.style.height = (this.scrollHeight) + 'px';
                    });
                }, 50);
            }

            function escapeHtml(text) {
                if (!text) return '';
                return text
                    .toString()
                    .replace(/&/g, "&amp;")
                    .replace(/</g, "&lt;")
                    .replace(/>/g, "&gt;")
                    .replace(/"/g, "&quot;")
                    .replace(/'/g, "&#039;");
            }

            $(document).on('click', '.btn-remove-testing-row', function() {
                const tr = $(this).closest('tr');
                const rowId = tr.attr('data-row-id');
                
                tr.fadeOut(200, function() {
                    // Destroy editors first
                    destroyRowEditors(tr);
                    tr.remove();

                    // Remove images from memory
                    if (rowId) {
                        delete testingRowImages[rowId];
                    }

                    if ($('#testingTableBody tr').length === 0) {
                        addTestingRow(1);
                    }
                });
            });

            $('.btn-add-row-action').on('click', function() {
                let maxStt = 0;
                $('#testingTableBody tr').each(function() {
                    const sttVal = parseInt($(this).find('input[name="stt"]').val()) || 0;
                    if (sttVal > maxStt) maxStt = sttVal;
                });
                addTestingRow(maxStt + 1);
            });

            // Bind Image Management Button Click
            $(document).on('click', '.btn-manage-images', function() {
                const row = $(this).closest('tr');
                currentManagingRowId = row.attr('data-row-id');
                const name = row.find('input[name="indicator_name"]').val().trim() || 'Chỉ tiêu không tên';

                $('#manageImagesRowTitle').text('Chỉ tiêu: ' + name);
                
                // Render images list
                renderManageImagesList();

                $('#modalManageImages').modal('show');
            });

            function renderManageImagesList() {
                const listContainer = $('#manageImagesList');
                listContainer.empty();

                const images = testingRowImages[currentManagingRowId] || [];
                $('#manageImagesCount').text(images.length);

                // Render each image card
                images.forEach((img, idx) => {
                    listContainer.append(`
                        <div class="col">
                            <div class="card h-100 shadow-sm position-relative border-light" style="border-radius: 12px; overflow: hidden; border: 1px solid #e2e8f0; transition: transform 0.2s ease, box-shadow 0.2s ease;">
                                <div class="position-relative bg-dark" style="height: 160px; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                                    <img src="${img.image_path}" style="width: 100%; height: 100%; object-fit: cover;">
                                    <button type="button" class="btn btn-danger btn-sm rounded-circle position-absolute" style="top: 8px; right: 8px; width: 32px; height: 32px; padding: 0; box-shadow: 0 2px 5px rgba(0,0,0,0.3); border: none; display: flex; align-items: center; justify-content: center;" onclick="removeAttachedImage(${idx})" title="Xóa hình ảnh này">
                                        <i class="fas fa-trash-alt" style="font-size: 0.85rem;"></i>
                                    </button>
                                </div>
                                <div class="card-body p-3 bg-white d-flex flex-column justify-content-between" style="min-height: 150px;">
                                    <div class="form-group mb-2">
                                        <label class="small fw-bold text-muted mb-1" style="font-size: 0.75rem;">Tên hình ảnh:</label>
                                        <input type="text" class="form-control form-control-sm img-name-input" value="${escapeHtml(img.image_name)}" placeholder="Nhập tên..." style="font-size: 0.8rem; border-radius: 6px;" onchange="updateImageMetadata(${idx}, 'name', this.value)">
                                    </div>
                                    <div class="form-group mb-0">
                                        <label class="small fw-bold text-muted mb-1" style="font-size: 0.75rem;">Mô tả hình ảnh:</label>
                                        <textarea class="form-control form-control-sm img-desc-input" rows="2" placeholder="Nhập mô tả..." style="font-size: 0.8rem; resize: none; border-radius: 6px;" onchange="updateImageMetadata(${idx}, 'description', this.value)">${escapeHtml(img.image_description)}</textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `);
                });

                // Render "+" card at the end of the grid
                listContainer.append(`
                    <div class="col">
                        <div class="card h-100 border-dashed text-center d-flex flex-column align-items-center justify-content-center p-4 animate-upload-placeholder" style="border: 2px dashed #cbd5e1; min-height: 310px; background-color: #f8fafc; cursor: pointer; border-radius: 12px; transition: all 0.2s;" onclick="document.getElementById('testingImageFileInput').click();" onmouseover="this.style.backgroundColor='#e0f2fe'; this.style.borderColor='#0288d1';" onmouseout="this.style.backgroundColor='#f8fafc'; this.style.borderColor='#cbd5e1';">
                            <div class="mb-3 p-3 rounded-circle bg-soft-info d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                <i class="fas fa-cloud-upload-alt text-info fa-2x"></i>
                            </div>
                            <span class="fw-bold text-navy mb-1" style="font-size: 0.9rem;">Thêm hình ảnh</span>
                            <span class="text-muted" style="font-size: 0.75rem; max-width: 150px;">Định dạng JPG, PNG, GIF, WEBP</span>
                        </div>
                    </div>
                `);
            }

            window.updateImageMetadata = function(idx, field, value) {
                const images = testingRowImages[currentManagingRowId] || [];
                if (images[idx]) {
                    if (field === 'name') images[idx].image_name = value;
                    if (field === 'description') images[idx].image_description = value;
                }
            };

            window.removeAttachedImage = function(idx) {
                Swal.fire({
                    title: 'Xác nhận xóa?',
                    text: 'Bạn có chắc chắn muốn gỡ bỏ hình ảnh này?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Đồng ý',
                    cancelButtonText: 'Hủy',
                    confirmButtonColor: '#d33'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const images = testingRowImages[currentManagingRowId] || [];
                        images.splice(idx, 1);
                        testingRowImages[currentManagingRowId] = images;
                        
                        // Re-render
                        renderManageImagesList();
                        updateRowImageButtons(currentManagingRowId);
                    }
                });
            };

            function updateRowImageButtons(rowId) {
                const row = $(`.testing-row-tr[data-row-id="${rowId}"]`);
                const images = testingRowImages[rowId] || [];
                row.find('.images-count').text(images.length);
                if (images.length > 0) {
                    row.find('.btn-view-carousel').show();
                } else {
                    row.find('.btn-view-carousel').hide();
                }
            }

            // Image File Input Upload Handler
            $('#testingImageFileInput').on('change', function() {
                const fileInput = this;
                if (fileInput.files.length === 0) return;

                const files = Array.from(fileInput.files);
                const totalFiles = files.length;
                let uploadedCount = 0;
                let failedCount = 0;

                // Show loading spinner
                Swal.fire({
                    title: `Đang tải lên ${totalFiles} hình ảnh...`,
                    html: `Tiến trình: <b>0</b>/${totalFiles}`,
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                function uploadNext(index) {
                    if (index >= totalFiles) {
                        Swal.close();
                        if (failedCount > 0) {
                            Swal.fire('Thông báo', `Đã tải lên thành công ${uploadedCount} hình ảnh. Thất bại: ${failedCount}.`, 'warning');
                        } else {
                            Swal.fire('Thành công', `Đã tải lên toàn bộ ${uploadedCount} hình ảnh!`, 'success');
                        }
                        // Re-render
                        renderManageImagesList();
                        updateRowImageButtons(currentManagingRowId);
                        fileInput.value = ''; // Reset input file
                        return;
                    }

                    const file = files[index];
                    const formData = new FormData();
                    formData.append('image', file);
                    formData.append('_token', '{{ csrf_token() }}');

                    // Update spinner text
                    const progressEl = Swal.getHtmlContainer().querySelector('b');
                    if (progressEl) progressEl.textContent = index + 1;

                    $.ajax({
                        url: '{{ route('pages.ebmr.uploadTestingImage') }}',
                        method: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(res) {
                            if (res.success) {
                                if (!testingRowImages[currentManagingRowId]) {
                                    testingRowImages[currentManagingRowId] = [];
                                }
                                testingRowImages[currentManagingRowId].push({
                                    image_path: res.url,
                                    image_name: res.name,
                                    image_description: ''
                                });
                                uploadedCount++;
                            } else {
                                failedCount++;
                            }
                            uploadNext(index + 1);
                        },
                        error: function() {
                            failedCount++;
                            uploadNext(index + 1);
                        }
                    });
                }

                // Start sequential upload
                uploadNext(0);
            });

            // Bind Carousel Viewer Click
            $(document).on('click', '.btn-view-carousel', function() {
                const row = $(this).closest('tr');
                const rowId = row.attr('data-row-id');
                const name = row.find('[name="indicator_name"]').val().trim() || 'Chỉ tiêu không tên';
                const images = testingRowImages[rowId] || [];

                if (images.length === 0) return;

                $('#carouselViewerTitle').text('Hình ảnh minh họa: ' + name);

                const indicators = $('#testingCarouselIndicators');
                const inner = $('#testingCarouselInner');
                
                indicators.empty();
                inner.empty();

                images.forEach((img, idx) => {
                    const activeClass = idx === 0 ? 'active' : '';
                    indicators.append(`
                        <li data-target="#testingCarousel" data-slide-to="${idx}" class="${activeClass}"></li>
                    `);

                    const descHtml = img.image_description 
                        ? `<p class="mb-0 small">${escapeHtml(img.image_description)}</p>` 
                        : '';

                    inner.append(`
                        <div class="carousel-item ${activeClass} h-100" style="position: relative;">
                            <div class="carousel-item-premium">
                                <img src="${img.image_path}" alt="${escapeHtml(img.image_name)}">
                            </div>
                            <div class="carousel-caption-premium">
                                <h6>${escapeHtml(img.image_name)}</h6>
                                ${descHtml}
                            </div>
                        </div>
                    `);
                });

                // Initialize bootstrap carousel
                $('#testingCarousel').carousel({
                    interval: false
                }).carousel(0);

                $('#modalCarouselViewer').modal('show');
            });

            $('#btnSaveTesting').on('click', function() {
                saveActiveStageToLocalMemory();

                if (currentTestingTemplateId === null) return;

                const btn = $(this);
                const originalHtml = btn.html();
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2 text-white"></i> Đang lưu...');

                $.ajax({
                    url: `/ebmr/templates/${currentTestingTemplateId}/testing-data`,
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        criteria: testingData
                    },
                    success: function(res) {
                        btn.prop('disabled', false).html(originalHtml);
                        if (res.success) {
                            Swal.fire({
                                title: 'Thành công!',
                                text: res.message,
                                icon: 'success',
                                confirmButtonColor: '#003A4F'
                            }).then(() => {
                                $('#modalTesting').modal('hide');
                            });
                        } else {
                            Swal.fire('Lỗi', res.message || 'Không thể lưu tiêu chuẩn', 'error');
                        }
                    },
                    error: function(err) {
                        btn.prop('disabled', false).html(originalHtml);
                        let msg = 'Không thể kết nối đến máy chủ';
                        if (err.responseJSON && err.responseJSON.message) msg = err.responseJSON.message;
                        Swal.fire('Lỗi', msg, 'error');
                    }
                });
            });

            $('#modalTesting').on('hidden.bs.modal', function () {
                destroyAllEditors();
                currentTestingTemplateId = null;
                testingStages = [];
                testingData = [];
                activeStageId = null;
                testingRowImages = {};
                currentManagingRowId = null;
                $('#testingStageList').empty();
                $('#testingTableBody').empty();
            });

            // Bind select operator change handler
            $(document).on('change', 'select[name="limit_operator"]', function() {
                const select = $(this);
                const wrapper = select.closest('tr');
                const op = select.val();
                const valInput = wrapper.find('[name="limit_value"]');
                const valHighInput = wrapper.find('[name="limit_value_high"]');

                if (op === 'range') {
                    valInput.attr('placeholder', 'Từ...');
                    valHighInput.attr('placeholder', 'Đến...').show();
                } else if (op === '±') {
                    valInput.attr('placeholder', 'Giá trị...');
                    valHighInput.attr('placeholder', '±...').show();
                } else {
                    valInput.attr('placeholder', 'Giá trị...');
                    valHighInput.hide().val(''); // hide and clear
                }

                // Trigger input for auto-resize
                setTimeout(() => {
                    valInput.trigger('input');
                    valHighInput.trigger('input');
                }, 50);
            });

            // Dynamic textareas input listener for live resize
            $(document).on('input', '.autofit-textarea', function() {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
            });
        </script>
        @include('pages.ebmr.templates.partials.bmr_scripts')
    @endsection
