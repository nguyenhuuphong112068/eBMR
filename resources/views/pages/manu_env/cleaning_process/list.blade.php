@extends('layout.master')

@section('title', 'Danh sách Quy trình Vệ sinh')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection

@section('mainContent')
    <div class="content-wrapper">
        <div class="container-fluid py-4">
            <div class="row">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 text-navy fw-bold">
                                <i class="fas fa-clipboard-list me-2"></i>
                                Danh sách Quy trình Vệ sinh - {{ $entityCode }} ({{ $entityName }})
                            </h5>
                            <div class="d-flex align-items-center gap-3">
                                <div class="btn-group view-toggle-group shadow-sm p-0.5 bg-light rounded-pill border"
                                    role="group" aria-label="View Toggle" style="display: flex;">
                                    <button type="button" class="btn btn-sm rounded-pill px-3 active" id="btnTableView"
                                        onclick="switchView('table')" style="transition: all 0.3s;">
                                        <i class="fas fa-table me-1"></i> Bảng
                                    </button>
                                    <button type="button" class="btn btn-sm rounded-pill px-3" id="btnCardView"
                                        onclick="switchView('card')" style="transition: all 0.3s;">
                                        <i class="fas fa-th-large me-1"></i> Thẻ
                                    </button>
                                </div>

                                <div id="dynamic-action-buttons"></div>
                            </div>
                        </div>

                        <div class="card-body p-4">
                            <!-- Filters -->
                            <div class="mb-4 d-flex justify-content-between align-items-center">
                                <ul class="nav nav-pills custom-pills" id="cleaningTypeFilter">
                                    <li class="nav-item">
                                        <a class="nav-link active" href="#" data-type="all">Tất cả</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" href="#" data-type="1">Cấp 1</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" href="#" data-type="2">Cấp 2</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" href="#" data-type="3">Vệ sinh lại</a>
                                    </li>
                                </ul>
                            </div>

                            <!-- Table View -->
                            <div class="table-responsive" id="tableViewContainer">
                                <table class="table table-hover align-middle w-100 table-bordered">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>STT</th>
                                            <th>Mã quy trình</th>
                                            <th>Tên quy trình</th>
                                            <th>Loại</th>
                                            <th>Ấn bản</th>
                                            <th>Trạng thái</th>
                                            <th>Ngày tạo</th>
                                            <th>Ngày HL</th>
                                            <th class="text-center">Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($processesList as $idx => $item)
                                            <tr class="process-row" data-type="{{ $item->cleaning_type }}">
                                                <td>{{ $idx + 1 }}</td>
                                                <td class="fw-bold text-primary">{{ $item->process_code }}</td>
                                                <td>{{ $item->process_name }}</td>
                                                <td>
                                                    @if($item->cleaning_type == 1)
                                                        <span class="badge bg-secondary">Cấp 1</span>
                                                    @elseif($item->cleaning_type == 2)
                                                        <span class="badge bg-info text-dark">Cấp 2</span>
                                                    @elseif($item->cleaning_type == 3)
                                                        <span class="badge bg-danger">Vệ sinh lại</span>
                                                    @else
                                                        <span class="badge bg-secondary">Cấp 1</span>
                                                    @endif
                                                </td>
                                                <td><span class="badge bg-soft-info">V.{{ $item->version }}</span></td>
                                                <td>
                                                    @if ($item->status === 'draft')
                                                        <span class="badge bg-secondary"><i class="fas fa-edit me-1"></i>
                                                            Nháp</span>
                                                    @elseif($item->status === 'submitted')
                                                        <span class="badge bg-warning text-dark" style="cursor: pointer;"
                                                            onclick="showWorkflowHistory('cleaning_{{ $type }}', {{ $item->id }})"
                                                            title="Xem lịch sử duyệt"><i class="fas fa-clock me-1"></i> Chờ
                                                            duyệt</span>
                                                        @if ($item->current_workflow_step)
                                                            <div class="mt-1 small text-muted"><i
                                                                    class="fas fa-user-clock me-1"></i>{{ $item->current_workflow_step }}
                                                            </div>
                                                        @endif
                                                    @elseif($item->status === 'approved')
                                                        <span class="badge bg-success" style="cursor: pointer;"
                                                            onclick="showWorkflowHistory('cleaning_{{ $type }}', {{ $item->id }})"
                                                            title="Xem lịch sử duyệt"><i
                                                                class="fas fa-check-circle me-1"></i> Đã duyệt</span>
                                                    @elseif($item->status === 'issued')
                                                        <span class="badge bg-info"><i
                                                                class="fas fa-hourglass-half me-1"></i> Chờ hiệu lực</span>
                                                    @elseif($item->status === 'active')
                                                        <span class="badge bg-primary"><i
                                                                class="fas fa-check-double me-1"></i> Hiện hành</span>
                                                    @elseif($item->status === 'expired')
                                                        <span class="badge bg-light text-muted border"><i
                                                                class="fas fa-history me-1"></i> Hết hạn</span>
                                                    @endif
                                                </td>
                                                <td>{{ \Carbon\Carbon::parse($item->created_at)->format('d/m/Y') }}</td>
                                                <td>
                                                    @if ($item->effective_date)
                                                        <span
                                                            class="text-dark fw-bold">{{ \Carbon\Carbon::parse($item->effective_date)->format('d/m/Y') }}</span>
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    <div class="btn-group shadow-sm rounded-pill overflow-hidden">
                                                        @if ($item->status === 'draft')
                                                            <a href="{{ route('pages.manu_env.cleaning_process.index', ['type' => $type, 'list_id' => $item->id]) }}"
                                                                class="btn btn-sm btn-white text-navy" title="Thiết kế">
                                                                <i class="fas fa-pencil-ruler"></i> Thiết kế
                                                            </a>
                                                            <button class="btn btn-sm btn-white text-success"
                                                                onclick="submitApproval({{ $item->id }})" title="Gửi duyệt">
                                                                <i class="fas fa-paper-plane"></i> Gửi duyệt
                                                            </button>
                                                        @else
                                                            @if ($item->status === 'approved' && !$item->effective_date && $item->created_by == session('user')['userId'])
                                                                <button class="btn btn-sm btn-white text-warning fw-bold"
                                                                    onclick="openEffectiveDateModal('{{ $type }}', {{ $item->id }}, '{{ $item->process_code }}')"
                                                                    title="Định nghĩa ngày hiệu lực">
                                                                    <i class="fas fa-calendar-alt"></i> Ngày HL
                                                                </button>
                                                            @endif
                                                            <a href="{{ route('pages.manu_env.cleaning_process.index', ['type' => $type, 'list_id' => $item->id]) }}"
                                                                class="btn btn-sm btn-white text-primary" title="Xem hồ sơ">
                                                                <i class="fas fa-eye"></i> Xem hồ sơ
                                                            </a>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="8" class="text-center text-muted py-4">Chưa có quy trình vệ
                                                    sinh nào. Hãy tạo mới.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <!-- Card View -->
                            <div class="d-none" id="cardViewContainer">
                                <div class="row g-4">
                                    @forelse($processesList as $idx => $item)
                                        <div class="col-12 col-md-6 col-xl-4 process-card" data-type="{{ $item->cleaning_type }}">
                                            <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden card-hover">
                                                <div
                                                    class="card-header bg-transparent border-0 pt-4 pb-0 px-4 d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <span
                                                            class="badge bg-soft-info fs-7 px-2.5 py-1 mb-1">V.{{ $item->version }}</span>
                                                        <div class="text-muted small fw-semibold text-uppercase tracking-wider"
                                                            style="font-size: 0.65rem;">{{ $item->process_code }}</div>
                                                    </div>
                                                    <div>
                                                        @if ($item->status === 'draft')
                                                            <span class="badge bg-secondary"><i
                                                                    class="fas fa-edit me-1"></i> Nháp</span>
                                                        @elseif($item->status === 'submitted')
                                                            <span class="badge bg-warning text-dark"
                                                                style="cursor: pointer;"
                                                                onclick="showWorkflowHistory('cleaning_{{ $type }}', {{ $item->id }})"
                                                                title="Xem lịch sử duyệt"><i class="fas fa-clock me-1"></i>
                                                                Chờ duyệt</span>
                                                            @if ($item->current_workflow_step)
                                                                <div class="mt-1 small text-muted"><i
                                                                        class="fas fa-user-clock me-1"></i>{{ $item->current_workflow_step }}
                                                                </div>
                                                            @endif
                                                        @elseif($item->status === 'approved')
                                                            <span class="badge bg-success" style="cursor: pointer;"
                                                                onclick="showWorkflowHistory('cleaning_{{ $type }}', {{ $item->id }})"
                                                                title="Xem lịch sử duyệt"><i
                                                                    class="fas fa-check-circle me-1"></i> Đã duyệt</span>
                                                        @elseif($item->status === 'issued')
                                                            <span class="badge bg-info"><i
                                                                    class="fas fa-hourglass-half me-1"></i> Chờ hiệu lực</span>
                                                        @elseif($item->status === 'active')
                                                            <span class="badge bg-primary"><i
                                                                    class="fas fa-check-double me-1"></i> Hiện hành</span>
                                                        @elseif($item->status === 'expired')
                                                            <span class="badge bg-light text-muted border"><i
                                                                    class="fas fa-history me-1"></i> Hết hạn</span>
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="card-body px-4 py-3 d-flex flex-column">
                                                    <h6 class="card-title fw-bold text-navy mb-2 line-clamp-2"
                                                        style="font-size: 1rem;">
                                                        {{ $item->process_name }}
                                                    </h6>
                                                    <div class="mb-2">
                                                        @if($item->cleaning_type == 1)
                                                            <span class="badge bg-secondary">Cấp 1</span>
                                                        @elseif($item->cleaning_type == 2)
                                                            <span class="badge bg-info text-dark">Cấp 2</span>
                                                        @elseif($item->cleaning_type == 3)
                                                            <span class="badge bg-danger">Vệ sinh lại</span>
                                                        @else
                                                            <span class="badge bg-secondary">Cấp 1</span>
                                                        @endif
                                                    </div>
                                                    <div class="mt-auto pt-3 border-top"
                                                        style="border-top: 1px dashed #e2e8f0 !important;">
                                                        <div class="d-flex justify-content-between">
                                                            <div class="text-muted small">
                                                                <i class="far fa-calendar-alt me-2 text-info"></i>
                                                                {{ \Carbon\Carbon::parse($item->created_at)->format('d/m/Y') }}
                                                            </div>
                                                            <div class="text-muted small">
                                                                HL:
                                                                <strong>{{ $item->effective_date ? \Carbon\Carbon::parse($item->effective_date)->format('d/m/Y') : '-' }}</strong>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div
                                                    class="card-footer bg-light border-0 py-3 px-4 d-flex justify-content-end">
                                                    <div class="btn-group shadow-sm rounded-pill overflow-hidden">
                                                        @if ($item->status === 'draft')
                                                            <a href="{{ route('pages.manu_env.cleaning_process.index', ['type' => $type, 'list_id' => $item->id]) }}"
                                                                class="btn btn-sm btn-white text-navy" title="Thiết kế">
                                                                <i class="fas fa-pencil-ruler"></i> Thiết kế
                                                            </a>
                                                            <button class="btn btn-sm btn-white text-success"
                                                                onclick="submitApproval({{ $item->id }})" title="Gửi duyệt">
                                                                <i class="fas fa-paper-plane"></i> Gửi duyệt
                                                            </button>
                                                        @else
                                                            @if ($item->status === 'approved' && !$item->effective_date && $item->created_by == session('user')['userId'])
                                                                <button
                                                                    class="btn btn-sm btn-white text-warning fw-bold"
                                                                    onclick="openEffectiveDateModal('{{ $type }}', {{ $item->id }}, '{{ $item->process_code }}')"
                                                                    title="Định nghĩa ngày hiệu lực">
                                                                    <i class="fas fa-calendar-alt"></i> Ngày HL
                                                                </button>
                                                            @endif
                                                            <a href="{{ route('pages.manu_env.cleaning_process.index', ['type' => $type, 'list_id' => $item->id]) }}"
                                                                class="btn btn-sm btn-white text-primary" title="Xem hồ sơ">
                                                                <i class="fas fa-eye"></i> Xem hồ sơ
                                                            </a>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="col-12 text-center text-muted">Không có dữ liệu.</div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Create -->
    <div class="modal fade" id="modalCreate" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-light">
                    <h5 class="modal-title font-weight-bold text-navy">Tạo mới Quy trình vệ sinh</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-4">
                    <form id="frmCreate">
                        <div class="form-group">
                            <label class="fw-bold">Mã quy trình <span class="text-danger">*</span></label>
                            <input type="text" class="form-control rounded-pill" name="process_code"
                                id="process_code" required>
                        </div>
                        <div class="form-group">
                            <label class="fw-bold">Tên quy trình <span class="text-danger">*</span></label>
                            <input type="text" class="form-control rounded-pill" name="process_name"
                                id="process_name" required>
                        </div>
                        <div class="form-group">
                            <label class="fw-bold">Loại quy trình vệ sinh <span class="text-danger">*</span></label>
                            <select class="form-control rounded-pill" name="cleaning_type" id="cleaning_type" required>
                                <option value="1">Vệ Sinh Cấp 1</option>
                                <option value="2">Vệ Sinh Cấp 2</option>
                                <option value="3">Vệ Sinh Lại</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-white rounded-pill px-4" data-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-primary rounded-pill px-4 shadow-sm"
                        onclick="saveNewProcess()">Lưu và Thiết kế</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Workflow Modal -->
    <div class="modal fade" id="workflowModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
                <form id="workflowForm">
                    @csrf
                    <input type="hidden" id="workflowListId" name="list_id">
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
                        <div class="workflow-stepper position-relative ps-2">
                            <div class="stepper-line"
                                style="position: absolute; left: 24px; top: 15px; bottom: 30px; width: 2px; background-color: #e2e8f0; z-index: 1;">
                            </div>

                            <!-- Step 1: Reviewers -->
                            <div class="workflow-step position-relative mb-4 pb-1" style="z-index: 2;">
                                <div class="d-flex align-items-center mb-2">
                                    <span class="step-badge text-white"
                                        style="width: 32px; height: 32px; background-color: #6c757d; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold;">1</span>
                                    <label class="form-label fw-bold text-navy mb-0 ms-3">Người kiểm tra
                                        (Reviewers)</label>
                                </div>
                                <div class="ms-5">
                                    <select class="form-select" name="reviewers[]" id="wfReviewers" multiple="multiple"
                                        data-placeholder="Chọn người kiểm tra...">
                                        @foreach ($users as $user)
                                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <!-- Step 2: Approver -->
                            <div class="workflow-step position-relative mb-4 pb-1" style="z-index: 2;">
                                <div class="d-flex align-items-center mb-2">
                                    <span class="step-badge text-white"
                                        style="width: 32px; height: 32px; background-color: #6c757d; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold;">2</span>
                                    <label class="form-label fw-bold text-navy mb-0 ms-3">Người phê duyệt (Approver) <span
                                            class="text-danger">*</span></label>
                                </div>
                                <div class="ms-5">
                                    <select class="form-select" name="approver" id="wfApprover" required>
                                        <option value="">-- Chọn người phê duyệt --</option>
                                        @foreach ($users as $user)
                                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <!-- Step 3: Authorizer -->
                            <div class="workflow-step position-relative" style="z-index: 2;">
                                <div class="d-flex align-items-center mb-2">
                                    <span class="step-badge text-white"
                                        style="width: 32px; height: 32px; background-color: #6c757d; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold;">3</span>
                                    <label class="form-label fw-bold text-navy mb-0 ms-3">Người ban hành (Authorizer) <span
                                            class="text-danger">*</span></label>
                                </div>
                                <div class="ms-5">
                                    <select class="form-select" name="authorizer" id="wfAuthorizer" required>
                                        <option value="">-- Chọn người ban hành --</option>
                                        @foreach ($users as $user)
                                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Hạn hoàn thành & lý do theo từng người -->
                        <div class="mt-4 pt-3" id="wfAssignmentDetails"
                            style="border-top: 1px dashed #dae0e5; display:none;">
                            <label class="form-label fw-bold text-navy mb-2" style="font-size: 0.85rem;">
                                <i class="far fa-calendar-check me-1"></i> Hạn hoàn thành &amp; lý do (tuỳ chọn, theo
                                từng người)
                            </label>
                            <div id="wfAssignmentRows"></div>
                        </div>
                    </div>
                    <div class="modal-footer bg-white border-top py-3 px-4 rounded-bottom">
                        <button type="button" class="btn btn-light rounded-pill px-4" data-dismiss="modal">Hủy
                            bỏ</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">
                            <i class="fas fa-paper-plane me-2"></i> Gửi trình ký
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@section('script')
    <script>
        const categoryData = {
            1: { hasProcess: false, latestId: null, canUpVersion: false },
            2: { hasProcess: false, latestId: null, canUpVersion: false },
            3: { hasProcess: false, latestId: null, canUpVersion: false }
        };
        
        @php
            $latestByType = [];
            foreach($processesList as $p) {
                if(!isset($latestByType[$p->cleaning_type])) {
                    $latestByType[$p->cleaning_type] = $p;
                }
            }
        @endphp
        
        @foreach([1, 2, 3] as $t)
            @if(isset($latestByType[$t]))
                categoryData[{{$t}}].hasProcess = true;
                categoryData[{{$t}}].latestId = {{ $latestByType[$t]->id }};
                categoryData[{{$t}}].canUpVersion = {{ in_array($latestByType[$t]->status, ['active', 'approved', 'issued']) ? 'true' : 'false' }};
            @endif
        @endforeach

        function openCreateModal(type) {
            $('#cleaning_type').val(type);
            $('#modalCreate').modal('show');
        }

        function switchView(view) {
            if (view === 'table') {
                $('#btnTableView').addClass('active btn-primary').removeClass('btn-light');
                $('#btnCardView').removeClass('active btn-primary').addClass('btn-light');
                $('#tableViewContainer').removeClass('d-none');
                $('#cardViewContainer').addClass('d-none');
            } else {
                $('#btnCardView').addClass('active btn-primary').removeClass('btn-light');
                $('#btnTableView').removeClass('active btn-primary').addClass('btn-light');
                $('#cardViewContainer').removeClass('d-none');
                $('#tableViewContainer').addClass('d-none');
            }
        }

        // Default init view toggle styles
        switchView('table');

        function saveNewProcess() {
            let code = $('#process_code').val();
            let name = $('#process_name').val();
            let type_val = $('#cleaning_type').val();
            if (!code || !name) {
                Swal.fire('Lỗi', 'Vui lòng nhập đủ Mã và Tên quy trình', 'warning');
                return;
            }

            $.ajax({
                url: '{{ route('pages.manu_env.cleaning_process.createList', ['type' => $type, 'id' => $id]) }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    process_code: code,
                    process_name: name,
                    cleaning_type: type_val
                },
                success: function(res) {
                    if (res.success) {
                        window.location.href = '/manu_env/cleaning-process/{{ $type }}/' + res
                            .list_id + '/design';
                    } else {
                        Swal.fire('Lỗi', res.message || 'Lỗi tạo quy trình', 'error');
                    }
                },
                error: function() {
                    Swal.fire('Lỗi', 'Có lỗi kết nối hệ thống', 'error');
                }
            });
        }

        function upVersion(list_id) {
            Swal.fire({
                title: 'Lên ấn bản?',
                text: "Quy trình mới sẽ sao chép toàn bộ nội dung của phiên bản hiện tại.",
                icon: 'info',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Đồng ý',
                cancelButtonText: 'Hủy'
            }).then((result) => {
                if (result.value) {
                    $.ajax({
                        url: '{{ route('pages.manu_env.cleaning_process.upVersion', ['type' => $type, 'list_id' => 'LIST_ID']) }}'
                            .replace('LIST_ID', list_id),
                        method: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(res) {
                            if (res.success) {
                                window.location.reload();
                            } else {
                                Swal.fire('Lỗi', res.message, 'error');
                            }
                        }
                    });
                }
            })
        }

        // Hạn hoàn thành & lý do theo từng người được gán (reviewer/approver/authorizer).
        // State lưu theo key "role_userId" để giữ lại giá trị đã nhập khi người dùng
        // đổi lựa chọn qua lại giữa các select2.
        let wfAssignmentState = {};
        const wfRoleLabels = {
            reviewer: 'Kiểm tra',
            approver: 'Phê duyệt',
            authorizer: 'Ban hành'
        };

        function wfEscapeHtml(str) {
            return String(str ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
        }

        function wfGetSelectedAssignments() {
            const list = [];
            $('#wfReviewers option:selected').each(function() {
                list.push({ role: 'reviewer', userId: String($(this).val()), name: $(this).text() });
            });
            const approverId = $('#wfApprover').val();
            if (approverId) list.push({ role: 'approver', userId: String(approverId), name: $('#wfApprover option:selected').text() });
            const authorizerId = $('#wfAuthorizer').val();
            if (authorizerId) list.push({ role: 'authorizer', userId: String(authorizerId), name: $('#wfAuthorizer option:selected').text() });
            return list;
        }

        function wfRenderAssignmentRows() {
            const assignments = wfGetSelectedAssignments();
            const $container = $('#wfAssignmentRows');
            $container.empty();
            $('#wfAssignmentDetails').toggle(assignments.length > 0);

            assignments.forEach(a => {
                const key = a.role + '_' + a.userId;
                if (!wfAssignmentState[key]) wfAssignmentState[key] = { due_date: '', reason: '' };
                const state = wfAssignmentState[key];

                const $row = $(`
                    <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                        <div class="small text-muted" style="min-width: 150px;">${wfRoleLabels[a.role]}: <strong class="text-navy">${wfEscapeHtml(a.name)}</strong></div>
                        <input type="date" class="form-control form-control-sm wf-due-date" style="max-width: 150px;" value="${wfEscapeHtml(state.due_date)}" title="Ngày mong muốn hoàn thành">
                        <input type="text" class="form-control form-control-sm wf-reason" style="max-width: 220px;" placeholder="Lý do mong muốn hoàn thành sớm (nếu có)" value="${wfEscapeHtml(state.reason)}">
                    </div>
                `);

                $row.find('.wf-due-date').on('change', function() { state.due_date = $(this).val(); });
                $row.find('.wf-reason').on('input', function() { state.reason = $(this).val(); });

                $container.append($row);
            });
        }

        function wfApplyAssignmentsToForm() {
            $('#workflowForm .wf-hidden-field').remove();

            wfGetSelectedAssignments().forEach(a => {
                const key = a.role + '_' + a.userId;
                const state = wfAssignmentState[key] || { due_date: '', reason: '' };
                const dueName = a.role === 'reviewer' ? `reviewer_due_dates[${a.userId}]` : `${a.role}_due_date`;
                const reasonName = a.role === 'reviewer' ? `reviewer_reasons[${a.userId}]` : `${a.role}_reason`;

                $('#workflowForm').append(`<input type="hidden" class="wf-hidden-field" name="${dueName}" value="${wfEscapeHtml(state.due_date)}">`);
                $('#workflowForm').append(`<input type="hidden" class="wf-hidden-field" name="${reasonName}" value="${wfEscapeHtml(state.reason)}">`);
            });
        }

        $('#wfReviewers, #wfApprover, #wfAuthorizer').on('change', wfRenderAssignmentRows);

        function submitApproval(list_id) {
            $('#workflowForm')[0].reset();
            $('#workflowListId').val(list_id);
            wfAssignmentState = {};

            // Reset selections
            $('#wfReviewers').val([]).trigger('change');
            $('#wfApprover').val('').trigger('change');
            $('#wfAuthorizer').val('').trigger('change');

            // Load existing workflows if any
            let getUrl =
                '{{ route('pages.manu_env.cleaning_process.getWorkflow', ['type' => $type, 'list_id' => 'LIST_ID']) }}'
                .replace('LIST_ID', list_id);
            $.get(getUrl, function(data) {
                let reviewers = [];
                data.forEach(item => {
                    wfAssignmentState[item.role + '_' + item.user_id] = {
                        due_date: item.due_date || '',
                        reason: item.reason || ''
                    };
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
            const list_id = $('#workflowListId').val();
            wfApplyAssignmentsToForm();
            const data = $(this).serialize();

            let postUrl =
                '{{ route('pages.manu_env.cleaning_process.storeWorkflow', ['type' => $type, 'list_id' => 'LIST_ID']) }}'
                .replace('LIST_ID', list_id);

            $.post(postUrl, data, function(res) {
                if (res.success) {
                    Swal.fire('Thành công', res.message, 'success').then(() => {
                        $('#workflowModal').modal('hide');
                        location.reload();
                    });
                } else {
                    Swal.fire('Lỗi', res.message || 'Lỗi khi lưu', 'error');
                }
            });
        });

        $(document).ready(function() {
            $('#wfReviewers, #wfApprover, #wfAuthorizer').select2({
                dropdownParent: $('#workflowModal')
            });
        });

        function openEffectiveDateModal(type, id, code) {
            Swal.fire({
                icon: 'info',
                title: 'Xác Định Ngày hiệu lực',
                html: `
                <p>Chọn ngày hiệu lực cho quy trình <b>${code}</b></p>
                <input type="date" id="effectiveDateInput" class="form-control" min="${new Date().toISOString().split('T')[0]}">
            `,
                showCancelButton: true,
                confirmButtonText: 'Lưu',
                cancelButtonText: 'Hủy',
                didOpen: () => {
                    // set default today
                    document.getElementById('effectiveDateInput').value = new Date().toISOString().split('T')[
                        0];
                },
                preConfirm: () => {
                    const date = document.getElementById('effectiveDateInput').value;
                    if (!date) {
                        Swal.showValidationMessage('Vui lòng chọn ngày hiệu lực');
                        return false;
                    }
                    return date;
                }
            }).then((result) => {
                if (result.value) {
                    Swal.fire({
                        title: 'Đang xử lý...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    $.ajax({
                        url: `/manu_env/cleaning-process/${type}/${id}/effective-date`,
                        method: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            effective_date: result.value
                        },
                        success: function(res) {
                            if (res.success) {
                                Swal.fire('Thành công', res.message, 'success').then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire('Lỗi', res.message, 'error');
                            }
                        },
                        error: function(xhr) {
                            let msg = 'Lỗi khi lưu ngày hiệu lực';
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                msg = xhr.responseJSON.message;
                            } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                                msg = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                            }
                            Swal.fire('Lỗi', msg, 'error');
                        }
                    });
                }
            });
        }
        
        // Filter logic
        $(document).ready(function() {
            $('#cleaningTypeFilter a').on('click', function(e) {
                e.preventDefault();
                $('#cleaningTypeFilter a').removeClass('active');
                $(this).addClass('active');
                
                let type = $(this).data('type');
                
                // Update action buttons
                let btnHtml = '';
                if (type !== 'all') {
                    let data = categoryData[type];
                    if (!data.hasProcess) {
                        btnHtml = `<button class="btn btn-primary rounded-pill px-4 shadow-sm fw-bold" onclick="openCreateModal(${type})"><i class="fas fa-plus me-2"></i> Tạo mới</button>`;
                    } else if (data.canUpVersion) {
                        btnHtml = `<button class="btn btn-warning rounded-pill px-4 shadow-sm fw-bold" onclick="upVersion(${data.latestId})"><i class="fas fa-copy me-2"></i> Lên ấn bản</button>`;
                    }
                }
                $('#dynamic-action-buttons').html(btnHtml);
                
                // Filter table rows
                $('#tableViewContainer tbody tr.process-row').each(function() {
                    if (type === 'all' || $(this).data('type') == type) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
                
                // Filter cards
                $('#cardViewContainer .process-card').each(function() {
                    if (type === 'all' || $(this).data('type') == type) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            });
        });
    </script>
@endsection

@section('css')
    <style>
        .bg-soft-info {
            background-color: rgba(23, 162, 184, 0.1);
            color: #17a2b8;
        }

        .text-navy {
            color: #001f3f;
        }

        .fs-7 {
            font-size: 0.8rem;
        }

        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .btn-white {
            background: #fff;
            border: 1px solid #e2e8f0;
        }

        .btn-navy {
            background-color: #001f3f;
            color: #fff;
        }

        .btn-navy:hover {
            background-color: #00152a;
            color: #fff;
        }
    </style>
@endsection
