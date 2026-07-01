@extends('layout.master')

@section('title', 'Hồ Sơ Sản Xuất BMR')

@section('mainContent')
    @php
        $hasTemplatesForCode = false;
        $codeParam = request('code');
        $selectedCategoryItem = null;
        if ($codeParam) {
            $hasTemplatesForCode = $templates->contains(function ($t) use ($codeParam) {
                return strtolower(trim($t->category_code)) === strtolower(trim($codeParam));
            });
            if (isset($category_items)) {
                $selectedCategoryItem = collect($category_items)->first(function ($item) use ($codeParam) {
                    if (isset($item->intermediate_code)) {
                        return strtolower(trim($item->intermediate_code)) === strtolower(trim($codeParam));
                    }
                    if (isset($item->code)) {
                        return strtolower(trim($item->code)) === strtolower(trim($codeParam));
                    }
                    if (isset($item->finished_product_code)) {
                        return strtolower(trim($item->finished_product_code)) === strtolower(trim($codeParam));
                    }
                    return false;
                });
            }
        }
    @endphp
    <div class="content-wrapper">
        <div class="container-fluid py-4">
            <div class="row">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 text-navy fw-bold">
                                <i
                                    class="fas {{ request('type') == 'GF' ? 'fa-layer-group' : (request('type') == 'BPR' ? 'fa-box-open' : (request('type') == 'MF' ? 'fa-file-invoice' : (request('type') == 'CO' ? 'fa-cube' : 'fa-file-medical'))) }} me-2"></i>
                                {{ request('type') == 'GF' ? 'Danh Sách Biểu Mẫu Dùng Chung' : (request('type') == 'BPR' ? 'Danh Sách Hồ Sơ Đóng Gói' : (request('type') == 'MF' ? 'Danh Sách Biểu Mẫu Gốc' : (request('type') == 'CO' ? 'Danh Sách Thành Phần' : 'Danh Sách Hồ Sơ Sản Xuất BMR'))) }}
                            </h5>
                            <div class="d-flex align-items-center gap-3">
                                <!-- View Toggle Button Group -->
                                <div class="btn-group view-toggle-group shadow-sm p-0.5 bg-light rounded-pill border"
                                    role="group" aria-label="View Toggle" style="display: flex;">
                                    <button type="button" class="btn btn-sm rounded-pill px-3 active" id="btnCardView"
                                        onclick="switchView('card')" style="transition: all 0.3s;">
                                        <i class="fas fa-th-large me-1"></i> Thẻ
                                    </button>
                                    <button type="button" class="btn btn-sm rounded-pill px-3" id="btnTableView"
                                        onclick="switchView('table')" style="transition: all 0.3s;">
                                        <i class="fas fa-table me-1"></i> Bảng
                                    </button>
                                </div>
                                @if (!request('code') || !$hasTemplatesForCode)
                                    @if (request('code') && $selectedCategoryItem)
                                        @php
                                            $type = request('type', 'BMR');
                                            $id = $selectedCategoryItem->id;
                                            if ($type == 'GF' || $type == 'MF') {
                                                $code = $selectedCategoryItem->code;
                                                $name = addslashes($selectedCategoryItem->name);
                                                $info =
                                                    $type == 'GF'
                                                        ? 'SOP: ' . $selectedCategoryItem->relatived_sop_no
                                                        : 'Công đoạn: ' . $selectedCategoryItem->stage_name;
                                                $onClick = "selectCategory($id, '$code', '$name', '$info')";
                                            } elseif ($type == 'BPR') {
                                                $code = $selectedCategoryItem->finished_product_code;
                                                $name = addslashes($selectedCategoryItem->product_name);
                                                $info = 'Cỡ lô: ' . $selectedCategoryItem->batch_qty;
                                                $onClick = "selectCategory($id, '$code', '$name', '$info', {7: 1, 8: 1})";
                                            } else {
                                                $code = $selectedCategoryItem->intermediate_code;
                                                $name = addslashes($selectedCategoryItem->product_name);
                                                $info =
                                                    'Cỡ lô: ' .
                                                    $selectedCategoryItem->batch_size .
                                                    ' ' .
                                                    $selectedCategoryItem->unit_batch_size .
                                                    ' | Dạng: ' .
                                                    ($selectedCategoryItem->dosage_name ?? 'N/A');
                                                $isHypothesis = $selectedCategoryItem->IsHypothesis ?? 0;
                                                $batchQty = $selectedCategoryItem->batch_qty ?? 0;
                                                $batchSize = $selectedCategoryItem->batch_size ?? 0;
                                                $onClick = "selectCategory($id, '$code', '$name', '$info', {1: 1, 2: 1, 3: 1, 4: 1, 5: 1, 6: 1}, $isHypothesis, $batchQty, $batchSize)";
                                            }
                                        @endphp
                                        <button class="btn btn-outline-primary rounded-pill px-4 shadow-sm fw-bold"
                                            onclick="{!! $onClick !!}">
                                            <i class="fas fa-file-signature me-2"></i> Tạo mới
                                        </button>
                                    @else
                                        @if ($current_type === 'CO')
                                            <button class="btn btn-outline-primary rounded-pill px-4 shadow-sm fw-bold"
                                                onclick="openCreateCoCategoryModal()">
                                                <i class="fas fa-file-signature me-2"></i> Tạo mới
                                            </button>
                                        @else
                                            <button class="btn btn-outline-primary rounded-pill px-4 shadow-sm fw-bold"
                                                onclick="openBtpListModal()">
                                                <i class="fas fa-file-signature me-2"></i> Tạo mới
                                            </button>
                                        @endif
                                    @endif
                                @endif
                            </div>
                        </div>
                        <div class="card-body p-4">
                            <!-- Table View Container -->
                            <div class="table-responsive d-none" id="tableViewContainer">
                                <table id="draftingTable" class="table table-hover align-middle bmr-datatable"
                                    style="width:100%">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>Mã danh mục</th>
                                            <th>{{ request('type') == 'BMR' ? 'Số BMR' : (request('type') == 'BPR' ? 'Số BPR' : 'Số BM gốc') }}
                                            </th>
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
                                            <tr
                                                class="{{ $t->status === 'expired' ? 'replaced-version-row' : ($t->status === 'active' ? 'active-version-row' : 'normal-version-row') }}">
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
                                                        <span class="badge bg-warning text-dark" style="cursor: pointer;"
                                                            onclick="showWorkflowHistory('ebmr', {{ $t->id }})"
                                                            title="Xem lịch sử duyệt"><i class="fas fa-clock me-1"></i> Chờ
                                                            duyệt</span>
                                                        @if ($t->current_workflow_step)
                                                            <div class="mt-1 small text-muted"><i
                                                                    class="fas fa-user-clock me-1"></i>{{ $t->current_workflow_step }}
                                                            </div>
                                                        @endif
                                                    @elseif($t->status === 'approved')
                                                        <span class="badge bg-success" style="cursor: pointer;"
                                                            onclick="showWorkflowHistory('ebmr', {{ $t->id }})"
                                                            title="Xem lịch sử duyệt"><i
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
                                                            Hiện hành</span>
                                                    @elseif($t->status === 'expired')
                                                        <span class="badge bg-light text-muted border"><i
                                                                class="fas fa-history me-1"></i>
                                                            Đã được thay thế</span>
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
                                                        @if ($current_type === 'BMR')
                                                            <button class="btn btn-sm btn-white text-info fw-bold"
                                                                onclick="openConfigSXModal({{ $t->id }}, '{{ addslashes($t->category_name) }}')"
                                                                title="Cấu hình sản xuất">
                                                                <i class="fas fa-cogs text-info me-1"></i> Cấu hình SX
                                                            </button>
                                                        @else
                                                            <button class="btn btn-sm btn-white text-info"
                                                                onclick="openEditModal({{ $t->id }})"
                                                                title="Cập nhật thông tin gốc">
                                                                <i class="fas fa-edit"></i> Sửa
                                                            </button>
                                                        @endif

                                                        @if ($t->status === 'draft')
                                                            <a href="{{ route('pages.ebmr.designer', $t->id) }}"
                                                                class="btn btn-sm btn-white text-navy"
                                                                title="Thiết kế nội dung">
                                                                <i class="fas fa-pencil-ruler"></i> Thiết kế
                                                            </a>
                                                            @if ($current_type !== 'CO')
                                                            <button class="btn btn-sm btn-white text-success"
                                                                onclick="openWorkflowModal({{ $t->id }})"
                                                                title="Trình ký">
                                                                <i class="fas fa-paper-plane"></i> Gửi duyệt
                                                            </button>
                                                            @endif
                                                        @else
                                                            <a href="{{ route('pages.ebmr.designer', $t->id) }}?mode=review"
                                                                class="btn btn-sm btn-white text-primary"
                                                                title="Xem nội dung">
                                                                <i class="fas fa-eye"></i> Xem hồ sơ
                                                            </a>
                                                            @if ($t->status === 'active' && !$t->has_pending_version)
                                                                <button class="btn btn-sm btn-white text-warning fw-bold"
                                                                    onclick="duplicateTemplate({{ $t->id }})"
                                                                    title="Lên ấn bản">
                                                                    <i class="fas fa-copy"></i> Lên ấn bản
                                                                </button>
                                                            @endif
                                                        @endif

                                                        @if ($t->issued_date && !$t->effective_date && $t->owner_id == session('user')['userId'])
                                                            <button class="btn btn-sm btn-white text-warning"
                                                                onclick="openEffectiveDateModal({{ $t->id }})"
                                                                title="Xác định ngày hiệu lực">
                                                                <i class="fas fa-calendar-check"></i> Hiệu lực
                                                            </button>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <!-- Card Grid View Container -->
                            <div id="cardViewContainer" class="d-block">
                                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4" id="templateCardGrid">
                                    @foreach ($templates as $t)
                                        <div class="col template-card-item" data-id="{{ $t->id }}">
                                            <div
                                                class="card h-100 template-card {{ $t->status === 'expired' ? 'replaced-version-card' : ($t->status === 'active' ? 'active-version-card' : 'normal-version-card') }}">
                                                <!-- Card Top Header -->
                                                <div
                                                    class="card-header bg-transparent border-0 pt-4 pb-0 px-4 d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <span
                                                            class="badge bg-soft-info fs-7 px-2.5 py-1 mb-1">V.{{ $t->version }}</span>
                                                        <div class="text-muted small fw-semibold text-uppercase tracking-wider"
                                                            style="font-size: 0.65rem; letter-spacing: 0.05em;">
                                                            {{ $t->category_code }}</div>
                                                    </div>
                                                    <div class="status-badge">
                                                        @if ($t->status === 'draft')
                                                            <span class="badge bg-secondary"><i
                                                                    class="fas fa-edit me-1"></i> Nháp</span>
                                                        @elseif($t->status === 'submitted')
                                                            <span class="badge bg-warning text-dark"
                                                                style="cursor: pointer;"
                                                                onclick="showWorkflowHistory('ebmr', {{ $t->id }})"
                                                                title="Xem lịch sử duyệt"><i
                                                                    class="fas fa-clock me-1"></i> Chờ duyệt</span>
                                                            @if ($t->current_workflow_step)
                                                                <div class="mt-1 small text-muted"><i
                                                                        class="fas fa-user-clock me-1"></i>{{ $t->current_workflow_step }}
                                                                </div>
                                                            @endif
                                                        @elseif($t->status === 'approved')
                                                            <span class="badge bg-success" style="cursor: pointer;"
                                                                onclick="showWorkflowHistory('ebmr', {{ $t->id }})"
                                                                title="Xem lịch sử duyệt"><i
                                                                    class="fas fa-check-circle me-1"></i> Đã
                                                                duyệt</span>
                                                        @elseif($t->status === 'issued')
                                                            @if ($t->effective_date)
                                                                <span class="badge bg-warning text-dark"><i
                                                                        class="fas fa-hourglass-half me-1"></i> Chờ
                                                                    hiệu lực</span>
                                                            @else
                                                                <span class="badge bg-info"><i
                                                                        class="fas fa-rocket me-1"></i> Đã ban
                                                                    hành</span>
                                                            @endif
                                                        @elseif($t->status === 'active')
                                                            <span class="badge bg-primary"><i
                                                                    class="fas fa-check-double me-1"></i> Hiện hành</span>
                                                        @elseif($t->status === 'expired')
                                                            <span class="badge bg-light text-muted border"><i
                                                                    class="fas fa-history me-1"></i> Đã được thay
                                                                thế</span>
                                                        @endif
                                                    </div>
                                                </div>

                                                <!-- Card Body -->
                                                <div class="card-body px-4 py-3 d-flex flex-column">
                                                    <h6 class="card-title fw-bold text-navy mb-2 line-clamp-2"
                                                        style="font-size: 1rem; line-height: 1.4;">
                                                        {{ $t->category_name }}
                                                    </h6>

                                                    <!-- Doc Code -->
                                                    <div class="mb-2">
                                                        <span
                                                            class="small text-muted me-1">{{ request('type') == 'BMR' ? 'Số BMR:' : (request('type') == 'BPR' ? 'Số BPR:' : 'Số BM gốc:') }}</span>
                                                        <span
                                                            class="fw-bold text-primary">{{ $t->doc_code ?? '-' }}</span>
                                                    </div>

                                                    <!-- Labeled Strength -->
                                                    @if (!empty($t->labeled_strength))
                                                        <div class="mb-2">
                                                            <span class="small text-muted me-1">Hàm lượng nhãn:</span>
                                                            <span class="fw-semibold text-navy"
                                                                style="font-size: 0.85rem;">{{ $t->labeled_strength }}</span>
                                                        </div>
                                                    @endif

                                                    <!-- Sections / stages timeline list -->
                                                    <div class="mb-4 flex-grow-1">
                                                        <div class="small fw-semibold text-navy mb-2"><i
                                                                class="fas fa-project-diagram me-1 text-info"></i> Công
                                                            đoạn:</div>
                                                        <div class="d-flex flex-wrap gap-1.5"
                                                            style="max-height: 80px; overflow-y: auto;">
                                                            @forelse($t->sections as $s)
                                                                <button
                                                                    class="btn btn-xs btn-outline-info rounded-pill py-0.5 px-2 bg-light text-nowrap"
                                                                    style="font-size: 0.65rem; border-color: rgba(23, 162, 184, 0.2);"
                                                                    onclick="window.location.href='{{ route('pages.ebmr.designer', $t->id) }}?section={{ $s['id'] }}'">
                                                                    {{ $s['label'] }}
                                                                </button>
                                                            @empty
                                                                <span class="text-muted small italic">Không có công
                                                                    đoạn</span>
                                                            @endforelse
                                                        </div>
                                                    </div>

                                                    <!-- Meta details -->
                                                    <div class="pt-3 border-top mt-auto"
                                                        style="border-top: 1px dashed #e2e8f0 !important;">
                                                        <div class="row g-2">
                                                            <div class="col-12 d-flex align-items-center text-muted small">
                                                                <i class="fas fa-user-circle me-2 text-info"
                                                                    style="font-size: 0.9rem;"></i>
                                                                <span class="text-truncate">Dược sĩ: <strong
                                                                        class="text-dark">{{ $t->owner_name ?? 'N/A' }}</strong></span>
                                                            </div>
                                                            <div class="col-6 d-flex align-items-center text-muted small">
                                                                <i class="far fa-calendar-alt me-2 text-info"
                                                                    style="font-size: 0.9rem;"></i>
                                                                <span>Ban hành: <strong
                                                                        class="text-dark">{{ $t->issued_date ? \Carbon\Carbon::parse($t->issued_date)->format('d/m/Y') : '-' }}</strong></span>
                                                            </div>
                                                            <div class="col-6 d-flex align-items-center text-muted small">
                                                                <i class="far fa-calendar-check me-2 text-info"
                                                                    style="font-size: 0.9rem;"></i>
                                                                <span>Hiệu lực: <strong
                                                                        class="text-dark">{{ $t->effective_date ? \Carbon\Carbon::parse($t->effective_date)->format('d/m/Y') : '-' }}</strong></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Card Footer Actions -->
                                                <div
                                                    class="card-footer bg-light border-0 py-3 px-4 d-flex justify-content-end">
                                                    <div class="d-flex gap-1">
                                                        @if ($current_type === 'BMR')
                                                            <button
                                                                class="btn btn-sm btn-outline-info rounded-pill px-3 py-1 fw-bold"
                                                                onclick="openConfigSXModal({{ $t->id }}, '{{ addslashes($t->category_name) }}')"
                                                                title="Cấu hình sản xuất">
                                                                <i class="fas fa-cogs me-1"></i> Cấu hình SX
                                                            </button>
                                                        @else
                                                            <button
                                                                class="btn btn-sm btn-outline-info rounded-pill px-3 py-1"
                                                                onclick="openEditModal({{ $t->id }})"
                                                                title="Cập nhật thông tin gốc">
                                                                <i class="fas fa-edit me-1"></i> Sửa
                                                            </button>
                                                        @endif

                                                        @if ($t->status === 'draft')
                                                            <a href="{{ route('pages.ebmr.designer', $t->id) }}"
                                                                class="btn btn-sm btn-navy rounded-pill px-3 py-1"
                                                                title="Thiết kế nội dung">
                                                                <i class="fas fa-pencil-ruler me-1"></i> Thiết kế
                                                            </a>
                                                            @if ($current_type !== 'CO')
                                                            <button class="btn btn-sm btn-success rounded-pill px-3 py-1"
                                                                onclick="openWorkflowModal({{ $t->id }})"
                                                                title="Trình ký">
                                                                <i class="fas fa-paper-plane me-1"></i> Gửi duyệt
                                                            </button>
                                                            @endif
                                                        @else
                                                            <a href="{{ route('pages.ebmr.designer', $t->id) }}?mode=review"
                                                                class="btn btn-sm btn-primary text-white rounded-pill px-3 py-1"
                                                                title="Xem nội dung">
                                                                <i class="fas fa-eye me-1"></i> Xem hồ sơ
                                                            </a>
                                                            @if ($t->status === 'active' && !$t->has_pending_version)
                                                                <button
                                                                    class="btn btn-sm btn-outline-warning rounded-pill px-3 py-1"
                                                                    onclick="duplicateTemplate({{ $t->id }})"
                                                                    title="Lên ấn bản">
                                                                    <i class="fas fa-copy me-1"></i> Lên ấn bản
                                                                </button>
                                                            @endif
                                                        @endif

                                                        @if ($t->issued_date && !$t->effective_date && $t->owner_id == session('user')['userId'])
                                                            <button class="btn btn-sm btn-warning rounded-pill px-3 py-1"
                                                                onclick="openEffectiveDateModal({{ $t->id }})"
                                                                title="Xác định ngày hiệu lực">
                                                                <i class="fas fa-calendar-check me-1"></i> Hiệu lực
                                                            </button>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Tạo Mới Danh Mục Thành Phần (CO) -->
        <div class="modal fade" id="modalCreateCoCategory" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header">
                        <h5 class="modal-title font-weight-bold text-info">
                            <i class="fas fa-plus-circle me-2"></i> Tạo Mới Danh Mục Thành Phần
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form id="formCreateCoCategory" enctype="multipart/form-data">
                        @csrf
                        <div class="modal-body p-4">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Mã Thành Phần <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="code" required placeholder="Nhập mã thành phần...">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Tên Thành Phần <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="name" required placeholder="Nhập tên thành phần...">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Sử dụng chung</label>
                                    <div class="custom-control custom-switch custom-switch-lg">
                                        <input type="checkbox" class="custom-control-input" id="co_is_private" name="is_private" value="1">
                                        <label class="custom-control-label fw-bold text-navy" for="co_is_private" style="cursor: pointer;">
                                            Chia sẻ cho nhiều người dùng chung
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Ảnh đại diện</label>
                                    <input type="file" class="form-control" name="avatar" accept="image/*">
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer bg-light p-3">
                            <button type="button" class="btn btn-white rounded-pill px-4" data-dismiss="modal">Hủy bỏ</button>
                            <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm text-white">
                                <i class="fas fa-save me-2 text-white"></i> Lưu thành phần
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        @if ($current_type !== 'CO')
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
        @endif

        <form id="metadataForm" class="m-0">
            @csrf
            <input type="hidden" id="templateId" name="id">
            <input type="hidden" id="caterogyId" name="caterogy_id">
            <input type="hidden" id="templateType" name="type" value="{{ request('type', 'BMR') }}">

            <div class="modal fade" id="templateMetadataModal" tabindex="-1" role="dialog" aria-hidden="true">
                @if ((isset($current_type) && $current_type === 'BMR') || request('type', 'BMR') === 'BMR')
                    <button type="button" class="modal-nav-btn modal-nav-left"
                        onclick="navigateConfigSX('left', '#templateMetadataModal', this)"
                        title="Khai báo phòng sản xuất">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <button type="button" class="modal-nav-btn modal-nav-right"
                        onclick="navigateConfigSX('right', '#templateMetadataModal', this)" title="Công thức">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                @endif
                <div class="modal-dialog {{ request('type') == 'MF' ? 'modal-lg' : 'modal-xl' }} modal-dialog-centered modal-dialog-scrollable" role="document"
                    style="{{ request('type') == 'MF' ? '' : 'max-width: 95%;' }}">
                    <div class="modal-content border-0 shadow-lg">
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
                            <div class="row align-items-center mb-3">
                                <div class="{{ request('type') == 'MF' ? 'col-md-8' : 'col-md-6' }}">
                                    <div class="p-3 rounded border" style="background: linear-gradient(145deg, #ffffff, #f8f9fa); border-color: #e2e8f0 !important; border-left: 4px solid #0d6efd !important; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
                                        <div class="w-100" style="min-width: 0;">
                                            <div class="text-uppercase fw-bold mb-1" style="color: #64748b; font-size: 0.65rem; letter-spacing: 0.5px;">Sản phẩm đang chọn</div>
                                            <div id="selectedBtpName" class="text-primary fw-bolder text-truncate mb-1" style="font-size: 1.15rem; line-height: 1.3;">
                                                Chưa chọn sản phẩm</div>
                                            <div id="selectedBtpInfo" class="fw-semibold"
                                                style="color: #475569; font-size: 0.85rem;">Cỡ lô: - | Dạng bào chế: -</div>
                                        </div>
                                    </div>
                                </div>
                                @if (request('type') != 'MF')
                                <div class="col-md-3">
                                    <div class="form-group mb-0">
                                        <label
                                            class="form-label fw-bold small">{{ request('type') == 'BMR' ? 'Số BMR' : (request('type') == 'BPR' ? 'Số BPR' : 'Số BM gốc') }}</label>
                                        <input type="text" class="form-control rounded-pill text-center fw-bold"
                                            name="doc_code" id="docCode" placeholder="Nhập số...">
                                    </div>
                                </div>
                                @endif
                                <div class="{{ request('type') == 'MF' ? 'col-md-4' : 'col-md-3' }}">
                                    <div class="form-group mb-0">
                                        <label class="form-label fw-bold small">Phiên Bản <span
                                                class="text-danger">*</span></label>
                                        <input type="number" class="form-control rounded-pill text-center fw-bold"
                                            name="version" id="version" required value="1" min="1">
                                        <small class="text-muted d-block mt-1 text-center" style="font-size: 0.7rem;">
                                        </small>
                                    </div>
                                </div>
                            </div>

                            @include('pages.ebmr.templates.partials.bmr_metadata')

                        </div>
                        <div class="modal-footer bg-light p-3">
                            <button type="button" class="btn btn-white rounded-pill px-4" data-dismiss="modal">Hủy
                                bỏ</button>
                            <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm text-white">
                                <i class="fas fa-save me-2 text-white"></i> Lưu hồ sơ
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Công thức -->
            <div class="modal fade" id="modalFormula" tabindex="-1" role="dialog" aria-hidden="true">
                @if ((isset($current_type) && $current_type === 'BMR') || request('type', 'BMR') === 'BMR')
                    <button type="button" class="modal-nav-btn modal-nav-left"
                        onclick="navigateConfigSX('left', '#modalFormula', this)" title="Thông tin sản phẩm">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <button type="button" class="modal-nav-btn modal-nav-right"
                        onclick="navigateConfigSX('right', '#modalFormula', this)" title="Tiêu chuẩn kiểm nghiệm">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                @endif
                <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document"
                    style="max-width: 95%;">
                    <div class="modal-content border-0 shadow-lg">
                        <div class="modal-header">
                            <h5 class="modal-title font-weight-bold text-info" id="modalFormulaTitle">
                                <i class="fas fa-flask me-2"></i>
                                Cập Nhật Công Thức Thiết Kế
                            </h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body p-4">
                            <!-- Thiết lập tính toán lại công thức -->
                            <div class="form-group mb-3 pb-3 border-bottom" id="recalculation_container"
                                style="display: {{ request('type') == 'BMR' ? 'block' : 'none' }};">
                                <div class="custom-control custom-switch custom-switch-lg">
                                    <input type="checkbox" class="custom-control-input" id="enable_recalculation"
                                        name="is_recalculation" value="1">
                                    <label class="custom-control-label fw-bold text-navy" for="enable_recalculation"
                                        style="cursor: pointer;">
                                        <i class="fas fa-calculator text-info me-1"></i> Thiết lập tính toán lại công thức
                                    </label>
                                </div>
                            </div>

                            @include('pages.ebmr.templates.partials.bmr_bom_tables')
                        </div>
                        <div class="modal-footer bg-light p-3">
                            <button type="button" class="btn btn-white rounded-pill px-4" data-dismiss="modal">Hủy
                                bỏ</button>
                            <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm text-white">
                                <i class="fas fa-save me-2 text-white"></i> Lưu hồ sơ
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <!-- Modal Trình Ký (Workflow) -->
        <div class="modal fade" id="workflowModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
                    <form id="workflowForm">
                        @csrf
                        <input type="hidden" id="workflowTemplateId" name="template_id">
                        <div
                            class="modal-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                            <h5 class="modal-title fw-bold text-navy mb-0" style="font-size: 1.1rem;">
                                <i class="fas fa-paper-plane text-primary me-2"></i> Trình Ký
                            </h5>
                            <button type="button" class="close border-0 bg-transparent text-muted fs-4 p-0 m-0"
                                data-dismiss="modal" aria-label="Close" style="line-height: 1; outline: none;">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body p-4" style="background-color: #f8fafc;">

                            <!-- Stepper Container -->
                            <div class="workflow-stepper position-relative ps-2">
                                <div class="stepper-line"></div>

                                <!-- Step 1: Reviewers -->
                                <div class="workflow-step position-relative mb-4 pb-1">
                                    <div class="d-flex align-items-center mb-2">
                                        <span class="step-badge text-white">1</span>
                                        <label class="form-label fw-bold text-navy mb-0 ms-3"
                                            style="margin-left: 12px !important;">
                                            Người kiểm tra (Reviewers)
                                        </label>
                                    </div>
                                    <div class="ms-5" style="margin-left: 44px !important;">
                                        <select class="form-select select2-workflow" name="reviewers[]" id="wfReviewers"
                                            multiple="multiple" data-placeholder="Chọn một hoặc nhiều người...">
                                            @foreach ($users as $user)
                                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                                            @endforeach
                                        </select>
                                        <small class="form-text text-muted d-block mt-1" style="font-size: 0.73rem;">
                                            <i class="far fa-lightbulb me-1"></i> Xem xét & kiểm tra nội dung (Có thể chọn
                                            nhiều người).
                                        </small>
                                    </div>
                                </div>

                                <!-- Step 2: Approver -->
                                <div class="workflow-step position-relative mb-4 pb-1">
                                    <div class="d-flex align-items-center mb-2">
                                        <span class="step-badge text-white">2</span>
                                        <label class="form-label fw-bold text-navy mb-0 ms-3"
                                            style="margin-left: 12px !important;">
                                            Người phê duyệt (Approver) <span class="text-danger">*</span>
                                        </label>
                                    </div>
                                    <div class="ms-5" style="margin-left: 44px !important;">
                                        <select class="form-select select2-workflow" name="approver" id="wfApprover"
                                            required>
                                            <option value="">-- Chọn một người phê duyệt --</option>
                                            @foreach ($users as $user)
                                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                                            @endforeach
                                        </select>
                                        <small class="form-text text-muted d-block mt-1" style="font-size: 0.73rem;">
                                            <i class="far fa-lightbulb me-1"></i> Trưởng phòng/Giám đốc phê duyệt nội dung.
                                        </small>
                                    </div>
                                </div>

                                <!-- Step 3: Authorizer -->
                                <div class="workflow-step position-relative mb-1">
                                    <div class="d-flex align-items-center mb-2">
                                        <span class="step-badge text-white">3</span>
                                        <label class="form-label fw-bold text-navy mb-0 ms-3"
                                            style="margin-left: 12px !important;">
                                            Người cho phép ban hành <span class="text-danger">*</span>
                                        </label>
                                    </div>
                                    <div class="ms-5" style="margin-left: 44px !important;">
                                        <select class="form-select select2-workflow" name="authorizer" id="wfAuthorizer"
                                            required>
                                            <option value="">-- Chọn một người ban hành --</option>
                                            @foreach ($users as $user)
                                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                                            @endforeach
                                        </select>
                                        <small class="form-text text-muted d-block mt-1" style="font-size: 0.73rem;">
                                            <i class="far fa-lightbulb me-1"></i> Giám đốc chất lượng/Người đại diện ban
                                            hành chính thức.
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer bg-white border-top py-3 px-4 d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-outline-secondary rounded-pill px-4"
                                data-dismiss="modal" style="font-size: 0.85rem; font-weight: 600;">Hủy bỏ</button>
                            <button type="submit" class="btn btn-success rounded-pill px-4 shadow-sm"
                                style="font-size: 0.85rem; font-weight: 600;">
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
            }

            /* Style cho Workflow Modal */
            .workflow-stepper {
                position: relative;
            }

            .workflow-stepper .stepper-line {
                position: absolute;
                left: 16px;
                top: 20px;
                bottom: 30px;
                width: 2px;
                border-left: 2px dashed #cbd5e1;
                z-index: 1;
            }

            .workflow-step {
                position: relative;
                z-index: 2;
            }

            .workflow-step .step-badge {
                width: 32px;
                height: 32px;
                border-radius: 50%;
                background-color: var(--primary-navy) !important;
                color: white !important;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                font-weight: bold;
                font-size: 0.85rem;
                border: 2px solid white;
                box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            }

            .workflow-step .form-label {
                margin-bottom: 0;
            }

            /* Custom Select2 styling inside workflow modal */
            #workflowModal .select2-container--bootstrap4 .select2-selection {
                border-radius: 8px !important;
                border: 1px solid #cbd5e1 !important;
                min-height: 38px !important;
                display: flex;
                align-items: center;
                transition: all 0.2s ease-in-out;
            }

            #workflowModal .select2-container--bootstrap4.select2-container--focus .select2-selection {
                border-color: var(--primary-navy) !important;
                box-shadow: 0 0 0 0.2rem rgba(0, 58, 79, 0.15) !important;
            }

            #workflowModal .select2-container--bootstrap4 .select2-selection--multiple .select2-selection__choice {
                background-color: rgba(0, 58, 79, 0.08) !important;
                border: 1px solid rgba(0, 58, 79, 0.15) !important;
                color: var(--primary-navy) !important;
                border-radius: 6px !important;
                font-weight: 500 !important;
                padding: 2px 8px !important;
                margin-top: 4px !important;
            }

            #workflowModal .select2-container--bootstrap4 .select2-selection--multiple .select2-selection__choice__remove {
                color: #ef4444 !important;
                margin-right: 5px !important;
                font-weight: bold !important;
            }

            #workflowModal .select2-container--bootstrap4 .select2-selection--multiple .select2-selection__choice__remove:hover {
                color: #b91c1c !important;
            }

            /* Tăng kích thước các modal cấu hình lên 90% chiều rộng và 100% chiều cao viewport, lệch top 20px */
            #templateMetadataModal .modal-dialog,
            #modalFormula .modal-dialog,
            #modalTesting .modal-dialog,
            #modalRooms .modal-dialog {
                max-width: 90% !important;
                width: 90% !important;
                height: calc(100vh - 20px) !important;
                margin: 20px auto 0 auto !important;
                padding: 0 !important;
                display: flex;
                align-items: stretch;
            }

            #templateMetadataModal .modal-content,
            #modalFormula .modal-content,
            #modalTesting .modal-content,
            #modalRooms .modal-content {
                height: calc(100vh - 20px) !important;
                border-top-left-radius: 16px !important;
                border-top-right-radius: 16px !important;
                border-bottom-left-radius: 0 !important;
                border-bottom-right-radius: 0 !important;
                overflow: hidden !important;
                display: flex;
                flex-direction: column;
                border: none !important;
            }

            #templateMetadataModal .modal-body,
            #modalFormula .modal-body,
            #modalTesting .modal-body,
            #modalRooms .modal-body {
                flex: 1 1 auto !important;
                overflow-y: auto !important;
                height: auto !important;
                min-height: 0 !important;
            }

            .modal-nav-btn {
                position: fixed;
                top: 50%;
                transform: translateY(-50%);
                width: 54px;
                height: 54px;
                border-radius: 50%;
                background-color: var(--primary-navy);
                color: white !important;
                border: 3px solid white;
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 2000;
                /* Phao nổi trên tất cả các lớp của modal */
                transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
                cursor: pointer;
                outline: none !important;
                pointer-events: auto;
                /* Đảm bảo click được */
            }

            .modal-nav-btn:hover {
                background-color: #002a3a;
                transform: translateY(-50%) scale(1.1);
                box-shadow: 0 6px 20px rgba(0, 0, 0, 0.45);
            }

            .modal-nav-btn i {
                font-size: 1.5rem;
            }

            .modal-nav-left {
                left: 20px;
            }

            .modal-nav-right {
                right: 20px;
            }

            /* Hiệu ứng chuyển cảnh mượt mà giữa các modal */
            .modal-content.slide-out-left-content {
                animation: slideOutLeftKey 0.25s forwards cubic-bezier(0.4, 0, 0.2, 1);
            }

            .modal-content.slide-in-right-content {
                animation: slideInRightKey 0.25s forwards cubic-bezier(0.4, 0, 0.2, 1);
            }

            .modal-content.slide-out-right-content {
                animation: slideOutRightKey 0.25s forwards cubic-bezier(0.4, 0, 0.2, 1);
            }

            .modal-content.slide-in-left-content {
                animation: slideInLeftKey 0.25s forwards cubic-bezier(0.4, 0, 0.2, 1);
            }

            @keyframes slideOutLeftKey {
                from {
                    transform: translateX(0);
                    opacity: 1;
                }

                to {
                    transform: translateX(-150px);
                    opacity: 0;
                }
            }

            @keyframes slideInRightKey {
                from {
                    transform: translateX(150px);
                    opacity: 0;
                }

                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }

            @keyframes slideOutRightKey {
                from {
                    transform: translateX(0);
                    opacity: 1;
                }

                to {
                    transform: translateX(150px);
                    opacity: 0;
                }
            }

            @keyframes slideInLeftKey {
                from {
                    transform: translateX(-150px);
                    opacity: 0;
                }

                to {
                    transform: translateX(0);
                    opacity: 1;
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

        <div class="modal fade" id="modalRooms" tabindex="-1" role="dialog" aria-hidden="true">
            @if ((isset($current_type) && $current_type === 'BMR') || request('type', 'BMR') === 'BMR')
                <button type="button" class="modal-nav-btn modal-nav-left"
                    onclick="navigateConfigSX('left', '#modalRooms', this)" title="Tiêu chuẩn kiểm nghiệm">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <button type="button" class="modal-nav-btn modal-nav-right"
                    onclick="navigateConfigSX('right', '#modalRooms', this)" title="Thông tin hồ sơ gốc">
                    <i class="fas fa-chevron-right"></i>
                </button>
            @endif
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document"
                style="max-width: 95%;">
                <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
                    <div class="modal-header">
                        <h5 class="modal-title font-weight-bold text-info d-flex align-items-center" id="modalRoomsTitle">
                            <i class="fas fa-door-open me-2 text-info fs-4"></i>
                            <div>
                                <span>Khai Báo Phòng Sản Xuất & Điều Kiện</span>
                                <span class="d-block small text-muted fw-normal mt-1" id="roomsTemplateNameDisplay"
                                    style="font-size: 0.85rem; opacity: 0.85;">Hồ sơ: ...</span>
                            </div>
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body p-0 d-flex" style="height: 70vh; min-height: 500px;">
                        <!-- Left Sidebar for Sections/Stages -->
                        <div class="border-end bg-light p-3" style="width: 280px; overflow-y: auto;">
                            <h6 class="text-uppercase text-muted fw-bold mb-3 small" style="letter-spacing: 1px;">Công
                                đoạn quy trình</h6>
                            <div class="list-group list-group-flush rooms-stage-list" id="roomsStageList">
                                <!-- Dynamic section tabs -->
                            </div>
                        </div>

                        <!-- Right Panel for Room Assignment -->
                        <div class="flex-grow-1 p-4 d-flex flex-column" style="overflow-y: auto;">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="fw-bold text-navy mb-0 d-flex align-items-center">
                                    <i class="fas fa-door-closed me-2 text-info"></i>
                                    <span id="activeRoomsStageTitle">Chọn công đoạn</span>
                                </h5>
                                <div class="text-muted small">
                                    Cấu hình các phòng sản xuất có thể thực hiện công đoạn này và điều kiện sản xuất tương
                                    ứng.
                                </div>
                            </div>

                            <!-- Table container -->
                            <div class="table-responsive flex-grow-1 border rounded" style="background-color: #fff;">
                                <table class="table align-middle mb-0 rooms-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 60px;" class="text-center">STT</th>
                                            <th>Phòng sản xuất <span class="text-danger">*</span></th>
                                            <th>Điều kiện sản xuất</th>
                                            <th style="width: 80px;" class="text-center">Hành động</th>
                                        </tr>
                                    </thead>
                                    <tbody id="roomsTableBody">
                                        <!-- Dynamic room rows for active stage -->
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-3">
                                <button type="button"
                                    class="btn btn-outline-primary rounded-pill px-4 shadow-sm fw-bold btn-add-room-row">
                                    <i class="fas fa-plus me-1"></i> Thêm phòng sản xuất
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light p-3 border-top">
                        <button type="button" class="btn btn-white rounded-pill px-4" data-dismiss="modal">Hủy
                            bỏ</button>
                        <button type="button" class="btn btn-navy rounded-pill px-4 shadow-sm" id="btnSaveRooms">
                            <i class="fas fa-save me-2 text-white"></i> Lưu Cấu Hình
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="modalTesting" tabindex="-1" role="dialog" aria-hidden="true">
            @if ((isset($current_type) && $current_type === 'BMR') || request('type', 'BMR') === 'BMR')
                <button type="button" class="modal-nav-btn modal-nav-left"
                    onclick="navigateConfigSX('left', '#modalTesting', this)" title="Thông tin hồ sơ gốc">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <button type="button" class="modal-nav-btn modal-nav-right"
                    onclick="navigateConfigSX('right', '#modalTesting', this)" title="Khai báo phòng sản xuất">
                    <i class="fas fa-chevron-right"></i>
                </button>
            @endif
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document"
                style="max-width: 95%;">
                <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
                    <div class="modal-header">
                        <h5 class="modal-title font-weight-bold text-info d-flex align-items-center"
                            id="modalTestingTitle">
                            <i class="fas fa-clipboard-check me-2 text-info fs-4"></i>
                            <div>
                                <span>Thiết Lập Tiêu Chuẩn Kiểm Nghiệm</span>
                                <span class="d-block small text-muted fw-normal mt-1" id="testingTemplateNameDisplay"
                                    style="font-size: 0.85rem; opacity: 0.85;">Hồ sơ: ...</span>
                            </div>
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body p-0 d-flex" style="height: 70vh; min-height: 500px;">
                        <!-- Left Stage Sidebar -->
                        <div class="border-end bg-light p-3" style="width: 280px; overflow-y: auto;">
                            <h6 class="text-uppercase text-muted fw-bold mb-3 small" style="letter-spacing: 1px;">Công
                                đoạn quy trình</h6>
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
                                <button type="button"
                                    class="btn btn-outline-primary rounded-pill px-4 shadow-sm fw-bold btn-add-row-action">
                                    <i class="fas fa-plus me-1"></i> Thêm chỉ tiêu kiểm
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light p-3 border-top">
                        <button type="button" class="btn btn-white rounded-pill px-4" data-dismiss="modal">Hủy
                            bỏ</button>
                        <button type="button" class="btn btn-navy rounded-pill px-4 shadow-sm" id="btnSaveTesting">
                            <i class="fas fa-save me-2"></i> Lưu Tiêu Chuẩn
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal 3A: Quản lý hình ảnh (Manage Images Sub-Modal) -->
        <div class="modal fade" id="modalManageImages" tabindex="-1" role="dialog" aria-hidden="true"
            style="z-index: 1060;">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document">
                <div class="modal-content border-0 shadow-lg"
                    style="border-radius: 12px; height: 80vh; max-height: 700px;">
                    <div class="modal-header bg-info text-white py-3">
                        <h5 class="modal-title font-weight-bold text-white d-flex align-items-center">
                            <i class="fas fa-images me-2"></i>
                            <div>
                                <span>Cấu Hình Hình Ảnh Đính Kèm</span>
                                <span class="d-block small text-light fw-normal mt-1" id="manageImagesRowTitle"
                                    style="font-size: 0.8rem; opacity: 0.85;">Chỉ tiêu: ...</span>
                            </div>
                        </h5>
                        <button type="button" class="close text-white border-0 bg-transparent fs-4"
                            onclick="$('#modalManageImages').modal('hide');" aria-label="Close" style="outline: none;">
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
                        <button type="button" class="btn btn-secondary rounded-pill px-4 fw-bold shadow-sm"
                            onclick="$('#modalManageImages').modal('hide');">Đồng ý & Đóng</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal 3B: Carousel Viewer (Xem Hình Ảnh Carousel Card) -->
        <div class="modal fade" id="modalCarouselViewer" tabindex="-1" role="dialog" aria-hidden="true"
            style="z-index: 1070;">
            <div class="modal-dialog modal-dialog-centered modal-xl lightbox-carousel-modal" role="document">
                <div class="modal-content border-0 shadow-lg">
                    <div
                        class="modal-header border-0 text-dark py-3 d-flex justify-content-between align-items-center lightbox-carousel-header">
                        <h5 class="modal-title font-weight-bold text-dark d-flex align-items-center">
                            <i class="fas fa-eye me-2 text-warning"></i>
                            <span id="carouselViewerTitle" style="font-size: 1.1rem; letter-spacing: 0.3px;">Xem hình ảnh
                                minh họa</span>
                        </h5>
                        <div class="lightbox-toolbar">
                            <button type="button" class="close text-dark border-0 bg-transparent fs-4 p-0 m-0"
                                onclick="$('#modalCarouselViewer').modal('hide');" aria-label="Close"
                                style="outline: none; opacity: 0.85; line-height: 1;">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    </div>
                    <div class="modal-body p-0 d-flex justify-content-center align-items-center"
                        style="background-color: transparent; min-height: 650px; height: 75vh; position: relative;">
                        <div id="testingCarousel" class="carousel slide w-100 h-100" data-ride="carousel">
                            <ol class="carousel-indicators" id="testingCarouselIndicators" style="bottom: 120px;">
                                <!-- Dynamic indicators -->
                            </ol>
                            <div class="carousel-inner" id="testingCarouselInner">
                                <!-- Dynamic slides -->
                            </div>
                            <a class="carousel-control-prev-premium" href="#testingCarousel" role="button"
                                data-slide="prev" title="Ảnh trước">
                                <i class="fas fa-chevron-left fa-lg"></i>
                            </a>
                            <a class="carousel-control-next-premium" href="#testingCarousel" role="button"
                                data-slide="next" title="Ảnh sau">
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

            .testing-stage-list .list-group-item,
            .rooms-stage-list .list-group-item {
                border: none;
                border-radius: 10px !important;
                margin-bottom: 6px;
                font-weight: 600;
                color: #555;
                transition: all 0.2s ease;
                cursor: pointer;
                padding: 10px 15px;
            }

            .testing-stage-list .list-group-item:hover,
            .rooms-stage-list .list-group-item:hover {
                background-color: #e9ecef;
                color: #003A4F;
            }

            .testing-stage-list .list-group-item.active,
            .rooms-stage-list .list-group-item.active {
                background-color: #003A4F !important;
                color: #fff !important;
                box-shadow: 0 4px 8px rgba(0, 58, 79, 0.15);
            }

            .testing-stage-list .list-group-item .badge,
            .rooms-stage-list .list-group-item .badge {
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

            /* View Toggle styling */
            .view-toggle-group .btn {
                border: none !important;
                background: transparent;
                color: #64748b;
                font-weight: 600;
                font-size: 0.85rem;
                padding: 6px 16px;
            }

            .view-toggle-group .btn.active {
                background-color: var(--primary-navy) !important;
                color: white !important;
                box-shadow: 0 2px 6px rgba(0, 58, 79, 0.2);
            }

            /* Card Grid styling */
            .template-card {
                border-radius: 16px;
                border: 1px solid #e2e8f0 !important;
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                overflow: hidden;
            }

            .template-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 12px 24px rgba(0, 58, 79, 0.08) !important;
                border-color: rgba(0, 58, 79, 0.25) !important;
            }

            .line-clamp-2 {
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
                overflow: hidden;
            }

            /* Highlight row and card for active (max version & status active) */
            .active-version-row {
                background-color: #f0fdf4 !important;
                /* light green/mint */
            }

            .active-version-row td {
                background-color: #f0fdf4 !important;
            }

            .active-version-row:hover td {
                background-color: #dcfce7 !important;
            }

            .active-version-card {
                background-color: #f0fdf4 !important;
                /* light green/mint */
                border: 1px solid #bbf7d0 !important;
            }

            .active-version-card:hover {
                border-color: #86efac !important;
                box-shadow: 0 12px 24px rgba(22, 163, 74, 0.08) !important;
            }

            /* Draft / Normal versions (white background) */
            .normal-version-row {
                background-color: #ffffff !important;
            }

            .normal-version-row td {
                background-color: #ffffff !important;
            }

            .normal-version-card {
                background-color: #ffffff !important;
                border: 1px solid #e2e8f0 !important;
            }

            /* Replaced versions (light gray background) */
            .replaced-version-row {
                background-color: #f8fafc !important;
                color: #64748b !important;
            }

            .replaced-version-row td {
                background-color: #f8fafc !important;
                color: #64748b !important;
            }

            .replaced-version-card {
                background-color: #f8fafc !important;
                border: 1px solid #cbd5e1 !important;
                opacity: 0.85;
            }

            .replaced-version-card .card-title,
            .replaced-version-card .text-navy {
                color: #475569 !important;
            }
        </style>
    @endsection

    @section('script')
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            function switchView(view) {
                if (view === 'card') {
                    $('#tableViewContainer').addClass('d-none').removeClass('d-block');
                    $('#cardViewContainer').addClass('d-block').removeClass('d-none');
                    $('#btnCardView').addClass('active');
                    $('#btnTableView').removeClass('active');
                    localStorage.setItem('templateViewMode', 'card');
                } else {
                    $('#cardViewContainer').addClass('d-none').removeClass('d-block');
                    $('#tableViewContainer').addClass('d-block').removeClass('d-none');
                    $('#btnTableView').addClass('active');
                    $('#btnCardView').removeClass('active');
                    localStorage.setItem('templateViewMode', 'table');
                }
            }

            function filterCards(searchVal) {
                if (!searchVal) {
                    $('.template-card-item').show();
                    return;
                }

                $('.template-card-item').each(function() {
                    const card = $(this);
                    const cardText = card.text().toLowerCase();
                    if (cardText.indexOf(searchVal) > -1) {
                        card.show();
                    } else {
                        card.hide();
                    }
                });
            }

            $(document).ready(function() {
                // Khởi tạo Select2 cho Workflow Modal
                $('.select2-workflow').select2({
                    theme: 'bootstrap4',
                    width: '100%',
                    dropdownParent: $('#workflowModal')
                });

                // Initialize the main drafting table
                const draftingTable = $('.bmr-datatable').DataTable({
                    language: {
                        url: '{{ asset('vendor/datatables/i18n/Vietnamese.json') }}'
                    },
                    order: [
                        [0, 'asc']
                    ]
                });

                // Sync DataTable search with Card Grid
                draftingTable.on('search.dt', function() {
                    const searchVal = draftingTable.search().toLowerCase();
                    filterCards(searchVal);
                });

                // Load initial view mode preference (defaults to card)
                let savedViewMode = localStorage.getItem('templateViewMode') || 'card';
                switchView(savedViewMode);

                // Tự động lọc theo tham số code từ URL
                const urlParams = new URLSearchParams(window.location.search);
                const codeParam = urlParams.get('code');
                if (codeParam) {
                    draftingTable.search(codeParam).draw();
                }

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
                const prefillBtpId = urlParams.get('prefill_btp_id');
                if (prefillBtpId) {
                    openBtpListModal();
                    window.history.replaceState({}, document.title, window.location.pathname);
                }
            });

            function openBtpListModal() {
                $('#modalBtpList').modal('show');
            }

            function openCreateCoCategoryModal() {
                $('#modalCreateCoCategory').modal('show');
            }

            $(document).ready(function() {
                $('#formCreateCoCategory').submit(function(e) {
                    e.preventDefault();
                    let formData = new FormData(this);
                    
                    $.ajax({
                        url: '{{ route('pages.ebmr.storeCoCategory') }}',
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(res) {
                            if (res.success) {
                                Swal.fire('Thành công', res.message, 'success').then(() => {
                                    window.location.reload();
                                });
                            } else {
                                Swal.fire('Lỗi', res.message, 'error');
                            }
                        },
                        error: function(err) {
                            console.error(err);
                            Swal.fire('Lỗi', 'Có lỗi xảy ra khi tạo danh mục', 'error');
                        }
                    });
                });
            });

            function selectCategory(id, code, name, info, stages = {}, isHypothesis = 0, batchQty = 0, batchSize = 0) {
                $('#modalBtpList').modal('hide');

                $('#caterogyId').val(id);
                $('#templateType').val(new URLSearchParams(window.location.search).get('type') || 'BMR');
                $('#selectedBtpName').html(code + ' - ' + name);
                $('#selectedBtpInfo').html(info);
                window.currentBatchQty = batchQty;
                window.currentBatchSize = batchSize;

                const type = $('#templateType').val();

                // Fetch next version
                $('#version').val('...').prop('disabled', true);
                $.get('{{ route('pages.ebmr.getNextVersion') }}', {
                    category_id: id,
                    type: type
                }, function(res) {
                    if (type === 'MF') {
                        // For MF, bypass the modal and create immediately
                        const data = {
                            _token: '{{ csrf_token() }}',
                            id: '',
                            caterogy_id: id,
                            version: res.next_version,
                            doc_code: '',
                            type: type
                        };

                        $.post('{{ route('pages.ebmr.storeTemplateMetadata') }}', data, function(storeRes) {
                            if (storeRes.success) {
                                Swal.fire('Thành công', 'Đã tạo Biểu mẫu gốc thành công!', 'success').then(() => {
                                    if (storeRes.new_id) {
                                        window.location.href = `/ebmr/designer/${storeRes.new_id}`;
                                    } else {
                                        location.reload();
                                    }
                                });
                            } else {
                                Swal.fire('Lỗi', storeRes.message || 'Có lỗi xảy ra', 'error');
                            }
                        }).fail(function() {
                            Swal.fire('Lỗi', 'Lỗi kết nối hoặc lỗi server.', 'error');
                        });
                        return;
                    }

                    $('#version').val(res.next_version).prop('disabled', false);
                    openCreateModal();
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
                    if ($('#create_storage_conditions_editor').length) $('#create_storage_conditions_editor').summernote('code',
                        '');
                }
                $('#enable_recalculation').prop('checked', false);
                $('#modalTitle').html('<i class="fas fa-file-medical me-2"></i> Soạn Mới Hồ Sơ Gốc');
                $('#templateMetadataModal').modal('show');
            }

            function duplicateTemplate(id) {
                Swal.fire({
                    title: 'Xác nhận lên ấn bản?',
                    text: "Hệ thống sẽ nhân bản toàn bộ nội dung của phiên bản hiện hành này sang một phiên bản nháp (Draft) mới để bạn chỉnh sửa.",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Đồng ý',
                    cancelButtonText: 'Hủy'
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Đang xử lý...',
                            html: 'Vui lòng chờ trong giây lát.',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        $.post('{{ route('pages.ebmr.duplicateTemplate') }}', {
                            _token: '{{ csrf_token() }}',
                            id: id
                        }, function(res) {
                            if (res.success) {
                                Swal.fire({
                                    title: 'Thành công!',
                                    text: res.message,
                                    icon: 'success'
                                }).then(() => {
                                    window.location.href = `/ebmr/designer/${res.new_id}`;
                                });
                            } else {
                                Swal.fire('Lỗi', res.message || 'Có lỗi xảy ra khi lên ấn bản.', 'error');
                            }
                        }).fail(function(xhr) {
                            Swal.fire('Lỗi', xhr.responseJSON?.message || 'Không thể kết nối đến máy chủ.',
                                'error');
                        });
                    }
                });
            }

            function openEditModal(id, onReadyCallback = null) {
                $('#metadataForm')[0].reset();
                $('#modalTitle').html('<i class="fas fa-cog me-2"></i> Cập Nhật Thông Tin Hồ Sơ Gốc');

                $.get(`/ebmr/templates/${id}/data`, function(data) {
                    window.isConfigReadOnly = (data.status !== 'draft');
                    const isReadOnly = window.isConfigReadOnly;

                    $('#templateId').val(data.id);
                    $('#caterogyId').val(data.caterogy_id);
                    $('#version').val(data.version);
                    $('#docCode').val(data.doc_code || '');
                    $('#statusDisplay').val(data.status);

                    if (data.type === 'BMR') {
                        $('#selectedBtpName').html((data.product_code || '') + ' - ' + (data.product_name || ''));
                        let info = 'Cỡ lô: ' + (data.batch_size || 0) + ' ' + (data.unit_batch_size || '') + ' | Dạng bào chế: ' + (data.dosage_form_name || '-');
                        $('#selectedBtpInfo').html(info);

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
                            if (isReadOnly) {
                                $('#create_description_editor').summernote('disable');
                            } else {
                                $('#create_description_editor').summernote('enable');
                            }
                        }
                        if ($('#create_storage_conditions_editor').length) {
                            $('#create_storage_conditions_editor').summernote('code', data.storage_conditions || '');
                            $('#create_storage_conditions_input').val(data.storage_conditions || '');
                            if (isReadOnly) {
                                $('#create_storage_conditions_editor').summernote('disable');
                            } else {
                                $('#create_storage_conditions_editor').summernote('enable');
                            }
                        }

                        if (data.bom && window.renderBOMRows) {
                            window.renderBOMRows(data.bom);
                        }

                        // Handle recalculation loading
                        $('#enable_recalculation').prop('checked', data.is_recalculation == 1);
                    } else {
                        $('#bmr_specific_fields').hide();
                    }

                    // Apply read-only state to all form inputs
                    $('#metadataForm').find('input:not([type="hidden"]), select, textarea').prop('disabled',
                        isReadOnly);
                    if (isReadOnly) {
                        $('#metadataForm button[type="submit"]').hide();
                    } else {
                        $('#metadataForm button[type="submit"]').show();
                    }

                    if (onReadyCallback) {
                        onReadyCallback();
                    } else {
                        $('#templateMetadataModal').modal('show');
                    }
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

            function openTestingModal(templateId, templateName, onReadyCallback = null) {
                currentTestingTemplateId = templateId;
                $('#testingTemplateNameDisplay').text('Hồ sơ BMR: ' + templateName);

                if (!onReadyCallback) {
                    // Show loading overlay
                    Swal.fire({
                        title: 'Đang tải dữ liệu...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                }

                $.get(`/ebmr/templates/${templateId}/testing-data`, function(res) {
                    if (!onReadyCallback) {
                        Swal.close();
                    }
                    if (res.success) {
                        testingStages = res.sections;
                        testingData = res.testing;
                        testingRowImages = {}; // Reset image map

                        // Render sidebar stage list
                        const stageList = $('#testingStageList');
                        stageList.empty();

                        if (testingStages.length === 0) {
                            stageList.html(
                                '<div class="text-muted p-3 text-center small">Không có công đoạn nào trong thiết kế.</div>'
                            );
                            $('#activeStageTitle').text('Không có công đoạn');
                            $('#testingTableBody').empty();
                            $('.btn-add-row-action').hide();
                            $('#btnSaveTesting').hide();
                            $('#modalTesting').modal('show');
                            return;
                        }

                        $('.btn-add-row-action').toggle(!window.isConfigReadOnly);
                        $('#btnSaveTesting').toggle(!window.isConfigReadOnly);

                        // Count how many criteria items are already saved for each stage
                        testingStages.forEach((stage, idx) => {
                            const count = testingData.filter(d => d.stage === stage.id).length;
                            const badgeHtml = count > 0 ?
                                `<span class="badge bg-soft-info badge-pill ml-auto">${count}</span>` : '';

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

                        if (onReadyCallback) {
                            onReadyCallback();
                        } else {
                            $('#modalTesting').modal('show');
                        }
                    } else {
                        Swal.fire('Lỗi', res.message || 'Không thể tải dữ liệu tiêu chuẩn', 'error');
                    }
                }).fail(function() {
                    if (!onReadyCallback) {
                        Swal.close();
                    }
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
                    if (window.isConfigReadOnly) {
                        editor.summernote('disable');
                    } else {
                        editor.summernote('enable');
                    }
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

                if (window.isConfigReadOnly) {
                    tr.find('input, select, textarea').prop('disabled', true);
                    tr.find('.btn-remove-testing-row, .btn-manage-images').hide();
                }

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
                const nameObj = row.find('[name="indicator_name"]');
                const name = nameObj.length ? nameObj.val().trim() : 'Chỉ tiêu không tên';

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
                            Swal.fire('Thông báo',
                                `Đã tải lên thành công ${uploadedCount} hình ảnh. Thất bại: ${failedCount}.`,
                                'warning');
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

                    const descHtml = img.image_description ?
                        `<p class="mb-0 small">${escapeHtml(img.image_description)}</p>` :
                        '';

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

            $('#modalTesting').on('hidden.bs.modal', function() {
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

            // --- BMR ROOM & CONDITIONS INTEGRATION ---
            let currentRoomsTemplateId = null;
            let roomsData = [];
            let roomsList = [];
            let conditionsList = [];
            let assignmentsList = [];
            let activeRoomsSectionId = null;

            window.openRoomsModal = function(templateId, templateName, onReadyCallback = null) {
                currentRoomsTemplateId = templateId;
                $('#roomsTemplateNameDisplay').text('Hồ sơ: ' + templateName);

                // Clear UI
                $('#roomsStageList').empty();
                $('#roomsTableBody').empty();
                $('#activeRoomsStageTitle').text('Chọn công đoạn');

                // Load config from server
                $.ajax({
                    url: `/ebmr/templates/${templateId}/rooms`,
                    method: 'GET',
                    success: function(res) {
                        if (res.success) {
                            roomsData = res.sections;
                            roomsList = res.rooms;
                            conditionsList = res.conditions;
                            assignmentsList = res.assignments;

                            if (roomsData.length === 0) {
                                $('#roomsStageList').html(
                                    '<div class="p-3 text-muted small text-center">Hồ sơ không có công đoạn nào</div>'
                                );
                                return;
                            }

                            // Render sidebar section list
                            roomsData.forEach(function(sec, idx) {
                                const activeClass = idx === 0 ? 'active' : '';
                                $('#roomsStageList').append(`
                                    <button type="button" class="list-group-item list-group-item-action rooms-stage-tab ${activeClass} d-flex justify-content-between align-items-center" 
                                        data-section-id="${sec.id}">
                                        <span>${sec.label}</span>
                                        <span class="badge bg-soft-info rooms-count-badge" id="badge_${sec.id}">0</span>
                                    </button>
                                `);
                            });

                            // Update count badges
                            updateAllRoomsCountBadges();

                            $('.btn-add-room-row').toggle(!window.isConfigReadOnly);
                            $('#btnSaveRooms').toggle(!window.isConfigReadOnly);

                            if (onReadyCallback) {
                                onReadyCallback();
                            } else {
                                $('#modalRooms').modal('show');
                            }

                            // Load first stage
                            selectRoomsStage(roomsData[0].id);
                        } else {
                            Swal.fire('Lỗi', res.message || 'Không thể tải cấu hình phòng', 'error');
                        }
                    },
                    error: function(err) {
                        let msg = 'Không thể kết nối đến máy chủ';
                        if (err.responseJSON && err.responseJSON.message) msg = err.responseJSON.message;
                        Swal.fire('Lỗi', msg, 'error');
                    }
                });
            };

            function updateAllRoomsCountBadges() {
                roomsData.forEach(function(sec) {
                    const count = assignmentsList.filter(a => a.section_id === sec.id).length;
                    $(`#badge_${sec.id}`).text(count);
                });
            }

            function selectRoomsStage(sectionId) {
                // Save current active stage rows before switching
                saveCurrentRoomsStageToMemory();

                activeRoomsSectionId = sectionId;
                $('.rooms-stage-tab').removeClass('active');
                $(`.rooms-stage-tab[data-section-id="${sectionId}"]`).addClass('active');

                const sec = roomsData.find(s => s.id === sectionId);
                if (!sec) return;

                $('#activeRoomsStageTitle').html(`<i class="fas fa-door-open me-2 text-primary"></i>${sec.label}`);

                // Fetch eligible rooms for this section code
                const eligibleRooms = getEligibleRooms(sec.code);

                // Render current assignments for this section
                $('#roomsTableBody').empty();
                const activeAssignments = assignmentsList.filter(a => a.section_id === sectionId);

                if (activeAssignments.length === 0) {
                    addRoomsRow(sectionId, null, null, eligibleRooms);
                } else {
                    activeAssignments.forEach(function(assign) {
                        addRoomsRow(sectionId, assign.room_id, assign.condition_id, eligibleRooms);
                    });
                }
            }

            function getEligibleRooms(sectionCode) {
                let targetStageCode = sectionCode;
                if (sectionCode === 1 || sectionCode === 2) {
                    targetStageCode = 1;
                } else if (sectionCode === 7 || sectionCode === 8) {
                    targetStageCode = 7;
                }
                return roomsList.filter(r => parseInt(r.stage_code) === parseInt(targetStageCode));
            }

            function addRoomsRow(sectionId, selectedRoomId, selectedCondId, eligibleRooms) {
                const trCount = $('#roomsTableBody tr').length;
                const index = trCount + 1;

                // Build room dropdown options
                let roomOptionsHtml = `<option value="">-- Chọn phòng sản xuất --</option>`;
                eligibleRooms.forEach(function(r) {
                    const selected = (selectedRoomId !== null && parseInt(r.id) === parseInt(selectedRoomId)) ?
                        'selected' : '';
                    roomOptionsHtml += `<option value="${r.id}" ${selected}>${r.code} - ${r.name}</option>`;
                });

                // Build row HTML
                const rowHtml = `
                    <tr class="room-assign-row" data-section-id="${sectionId}">
                        <td class="text-center align-middle fw-bold row-stt">${index}</td>
                        <td>
                            <select class="form-select select-assigned-room shadow-sm" style="border-radius: 8px; height: 38px;">
                                ${roomOptionsHtml}
                            </select>
                        </td>
                        <td>
                            <select class="form-select select-assigned-condition shadow-sm" style="border-radius: 8px; height: 38px;" disabled>
                                <option value="">Mặc định (Không có bộ điều kiện)</option>
                            </select>
                        </td>
                        <td class="text-center align-middle">
                            <button type="button" class="btn btn-sm btn-icon btn-light-danger border shadow-sm btn-delete-room-row" title="Xóa">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;

                const $row = $(rowHtml);
                $('#roomsTableBody').append($row);

                // Bind select change handler for room to load conditions
                const $roomSelect = $row.find('.select-assigned-room');
                const $condSelect = $row.find('.select-assigned-condition');

                $roomSelect.on('change', function() {
                    const roomId = $(this).val();
                    loadConditionsForRoomSelect(roomId, $condSelect, selectedCondId);
                });

                // Trigger initial conditions load if room was selected
                if (selectedRoomId) {
                    $roomSelect.trigger('change');
                }

                if (window.isConfigReadOnly) {
                    $roomSelect.prop('disabled', true);
                    $condSelect.prop('disabled', true);
                    $row.find('.btn-delete-room-row').hide();
                }
            }

            function loadConditionsForRoomSelect(roomId, $condSelect, selectedCondId) {
                $condSelect.empty();
                $condSelect.append(`<option value="">Mặc định (Không có bộ điều kiện)</option>`);

                if (!roomId) {
                    $condSelect.prop('disabled', true);
                    return;
                }

                const roomConditions = conditionsList.filter(c => parseInt(c.room_id) === parseInt(roomId));

                if (roomConditions.length === 0) {
                    $condSelect.prop('disabled', true);
                } else {
                    $condSelect.prop('disabled', window.isConfigReadOnly);
                    roomConditions.forEach(function(c) {
                        const selected = (selectedCondId !== null && parseInt(c.id) === parseInt(selectedCondId)) ?
                            'selected' : '';
                        $condSelect.append(`<option value="${c.id}" ${selected}>${c.name}</option>`);
                    });
                }
            }

            function saveCurrentRoomsStageToMemory() {
                if (activeRoomsSectionId === null) return;

                // Read all row values
                const currentRows = [];
                $('#roomsTableBody tr.room-assign-row').each(function() {
                    const roomId = $(this).find('.select-assigned-room').val();
                    const condId = $(this).find('.select-assigned-condition').val();

                    if (roomId) {
                        currentRows.push({
                            section_id: activeRoomsSectionId,
                            room_id: parseInt(roomId),
                            condition_id: condId ? parseInt(condId) : null
                        });
                    }
                });

                // Remove old assignments for activeRoomsSectionId
                assignmentsList = assignmentsList.filter(a => a.section_id !== activeRoomsSectionId);

                // Append new ones
                assignmentsList = assignmentsList.concat(currentRows);

                // Update tab badge count
                $(`#badge_${activeRoomsSectionId}`).text(currentRows.length);
            }

            // Click sidebar tab handler
            $(document).on('click', '.rooms-stage-tab', function() {
                const sectionId = $(this).data('section-id');
                if (sectionId === activeRoomsSectionId) return;
                selectRoomsStage(sectionId);
            });

            // Add room row click handler
            $('.btn-add-room-row').on('click', function() {
                if (activeRoomsSectionId === null) return;
                const sec = roomsData.find(s => s.id === activeRoomsSectionId);
                if (!sec) return;
                const eligibleRooms = getEligibleRooms(sec.code);
                addRoomsRow(activeRoomsSectionId, null, null, eligibleRooms);
            });

            // Delete room row click handler
            $(document).on('click', '.btn-delete-room-row', function() {
                const tr = $(this).closest('tr');
                tr.remove();

                // Re-number STT
                $('#roomsTableBody tr').each(function(idx) {
                    $(this).find('.row-stt').text(idx + 1);
                });
            });

            // Save rooms click handler
            $('#btnSaveRooms').on('click', function() {
                // Save current stage rows first
                saveCurrentRoomsStageToMemory();

                if (currentRoomsTemplateId === null) return;

                const btn = $(this);
                const originalHtml = btn.html();
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2 text-white"></i> Đang lưu...');

                $.ajax({
                    url: `/ebmr/templates/${currentRoomsTemplateId}/rooms`,
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        mappings: assignmentsList
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
                                $('#modalRooms').modal('hide');
                            });
                        } else {
                            Swal.fire('Lỗi', res.message || 'Không thể lưu cấu hình phòng', 'error');
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

            $('#modalRooms').on('hidden.bs.modal', function() {
                currentRoomsTemplateId = null;
                roomsData = [];
                roomsList = [];
                conditionsList = [];
                assignmentsList = [];
                activeRoomsSectionId = null;
                $('#roomsStageList').empty();
                $('#roomsTableBody').empty();
            });

            // --- CAROUSEL MODAL NAVIGATION FOR CẤU HÌNH SX ---
            let currentConfigTemplateName = '';
            window.isConfigReadOnly = false;

            window.openConfigSXModal = function(id, name) {
                currentConfigTemplateName = name;
                openEditModal(id);
            };

            function transitionModal(fromSelector, toSelector, direction, callback, onComplete) {
                const $fromModal = $(fromSelector);
                const $toModal = $(toSelector);
                const $fromContent = $fromModal.find('.modal-content');

                // Lấy các class hiệu ứng dựa vào hướng chuyển
                const outClass = direction === 'right' ? 'slide-out-left-content' : 'slide-out-right-content';
                const inClass = direction === 'right' ? 'slide-in-right-content' : 'slide-in-left-content';

                // Bắt đầu hiệu ứng trượt ra cho modal hiện tại
                $fromContent.addClass(outClass);

                // Chờ hiệu ứng trượt ra hoàn tất (250ms)
                setTimeout(function() {
                    // Tạm thời bỏ class fade của cả 2 modal để triệt tiêu hiện tượng nháy backdrop
                    $fromModal.removeClass('fade');
                    $toModal.removeClass('fade');

                    // Ẩn modal cũ và hiển thị modal mới đồng thời
                    $fromModal.modal('hide');

                    if (callback) {
                        callback(); // Hiển thị modal mới lên
                    }

                    // Áp dụng hiệu ứng trượt vào cho modal mới
                    const $toContent = $toModal.find('.modal-content');
                    $toContent.addClass(inClass);

                    // Phục hồi lại class fade và gỡ bỏ class hiệu ứng sau khi hoàn tất trượt vào
                    setTimeout(function() {
                        $fromModal.addClass('fade');
                        $toModal.addClass('fade');
                        $fromContent.removeClass(outClass);
                        $toContent.removeClass(inClass);
                        if (onComplete) onComplete();
                    }, 250); // Chờ hiệu ứng trượt vào
                }, 250); // Chờ hiệu ứng trượt ra
            }

            window.navigateConfigSX = function(direction, currentModalSelector, btnElement) {
                const id = $('#templateId').val() || currentTestingTemplateId || currentRoomsTemplateId;
                const name = currentConfigTemplateName || $('#selectedBtpName').text();

                if (!id) {
                    Swal.fire('Lỗi', 'Không xác định được ID hồ sơ mẫu', 'error');
                    return;
                }

                const $btn = $(btnElement);
                let originalHtml = '';
                if (btnElement) {
                    originalHtml = $btn.html();
                    $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
                }

                function restoreBtn() {
                    if (btnElement) {
                        $btn.prop('disabled', false).html(originalHtml);
                    }
                }

                if (direction === 'right') {
                    if (currentModalSelector === '#templateMetadataModal') {
                        openEditModal(id, function() {
                            transitionModal('#templateMetadataModal', '#modalFormula', 'right', function() {
                                $('#modalFormula').modal('show');
                            }, restoreBtn);
                        });
                    } else if (currentModalSelector === '#modalFormula') {
                        openTestingModal(id, name, function() {
                            transitionModal('#modalFormula', '#modalTesting', 'right', function() {
                                $('#modalTesting').modal('show');
                            }, restoreBtn);
                        });
                    } else if (currentModalSelector === '#modalTesting') {
                        saveActiveStageToLocalMemory();
                        openRoomsModal(id, name, function() {
                            transitionModal('#modalTesting', '#modalRooms', 'right', function() {
                                $('#modalRooms').modal('show');
                            }, restoreBtn);
                        });
                    } else if (currentModalSelector === '#modalRooms') {
                        saveCurrentRoomsStageToMemory();
                        openEditModal(id, function() {
                            transitionModal('#modalRooms', '#templateMetadataModal', 'right', function() {
                                $('#templateMetadataModal').modal('show');
                            }, restoreBtn);
                        });
                    }
                } else if (direction === 'left') {
                    if (currentModalSelector === '#templateMetadataModal') {
                        openRoomsModal(id, name, function() {
                            transitionModal('#templateMetadataModal', '#modalRooms', 'left', function() {
                                $('#modalRooms').modal('show');
                            }, restoreBtn);
                        });
                    } else if (currentModalSelector === '#modalFormula') {
                        openEditModal(id, function() {
                            transitionModal('#modalFormula', '#templateMetadataModal', 'left', function() {
                                $('#templateMetadataModal').modal('show');
                            }, restoreBtn);
                        });
                    } else if (currentModalSelector === '#modalTesting') {
                        saveActiveStageToLocalMemory();
                        openEditModal(id, function() {
                            transitionModal('#modalTesting', '#modalFormula', 'left', function() {
                                $('#modalFormula').modal('show');
                            }, restoreBtn);
                        });
                    } else if (currentModalSelector === '#modalRooms') {
                        saveCurrentRoomsStageToMemory();
                        openTestingModal(id, name, function() {
                            transitionModal('#modalRooms', '#modalTesting', 'left', function() {
                                $('#modalTesting').modal('show');
                            }, restoreBtn);
                        });
                    }
                }
            };
        </script>
        @include('pages.ebmr.templates.partials.bmr_scripts')
    @endsection
