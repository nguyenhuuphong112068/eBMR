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
            <div class="modal-dialog modal-xl modal-dialog-centered" role="document" style="max-width: 95%;">
                <div class="modal-content border-0 shadow-lg">
                    <form id="metadataForm">
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
                                        <div class="col-md-8">
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
                                        <div class="col-md-4">
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
                    $.ajax({
                        url: "{{ route('pages.category.intermediate.recipe') }}",
                        type: 'post',
                        data: {
                            IsHypothesis: isHypothesis,
                            product_caterogy_id: id,
                            intermediate_code: code,
                            _token: "{{ csrf_token() }}"
                        },
                        success: function(res) {
                            if (res && res.length > 0) {
                                res.forEach((item) => {
                                    addBOMRow(0, 'bom_table_body_type_0', item);
                                });
                            } else {
                                // If no formula, just add 1 empty row to start
                                addBOMRow(0, 'bom_table_body_type_0');
                            }
                        }
                    });
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
                    if ($('#create_storage_conditions_editor').length) $('#create_storage_conditions_editor').summernote('code',
                        '');
                }
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
        </script>
        @include('pages.ebmr.templates.partials.bmr_scripts')
    @endsection
