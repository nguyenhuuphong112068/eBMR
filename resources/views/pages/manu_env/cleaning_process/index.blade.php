@extends('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection

@section('mainContent')
    <style>
        /* Bỏ bo góc và viền của Summernote */
        #cleaning-process-table .note-editor.note-frame {
            border: none !important;
            border-radius: 0 !important;
            margin-bottom: 0 !important;
        }

        #cleaning-process-table .note-editor .note-toolbar {
            border-radius: 0 !important;
            border-bottom: 1px solid #dee2e6;
            background-color: #f8f9fa;
        }

        #cleaning-process-table .note-editor .note-statusbar {
            border-radius: 0 !important;
        }

        /* Bỏ padding của ô chứa editor và ô tiêu chuẩn */
        #cleaning-process-table td.editor-cell,
        #cleaning-process-table td.standard-cell {
            padding: 0 !important;
        }

        /* Xóa viền và bo góc của textarea tiêu chuẩn */
        #cleaning-process-table .step-standard {
            border: none !important;
            border-radius: 0 !important;
            box-shadow: none !important;
            height: 100%;
            min-height: 200px;
            resize: vertical;
        }
    </style>
    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-12 d-flex justify-content-between align-items-center">
                        <h1 class="m-0 text-uppercase fw-bold text-navy">Thiết kế Quy trình vệ sinh - {{ $entityCode }}
                            ({{ $entityName }}) - Ấn bản V.{{ $list->version }}</h1>
                        <div>
                            <a href="{{ route('pages.manu_env.cleaning_process.list', ['type' => $type, 'id' => $id]) }}"
                                class="btn btn-outline-secondary me-2">
                                <i class="fas fa-arrow-left me-1"></i> Quay lại Danh sách
                            </a>
                            @if ($list->status === 'draft')
                                <button class="btn btn-primary" id="btn-save-process">
                                    <i class="fas fa-save me-1"></i> Lưu quy trình
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="content">
            <div class="container-fluid">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-0">
                        <table class="table table-bordered mb-0" id="cleaning-process-table">
                            <thead class="bg-light">
                                <tr>
                                    <th style="width: 80px;" class="text-center">Bước</th>
                                    <th>Nội dung quy trình</th>
                                    <th style="width: 250px;">Tiêu chuẩn</th>
                                    @if ($list->status === 'draft')
                                        <th style="width: 80px;" class="text-center">Thao tác</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody id="process-list">
                                @if (count($processes) > 0)
                                    @foreach ($processes as $p)
                                        <tr class="process-step">
                                            <td class="text-center align-middle">
                                                <div class="d-flex align-items-center justify-content-center">
                                                    <input type="number" class="form-control text-center step-number p-1"
                                                        style="width: 50px;" value="{{ $p->step }}" />
                                                </div>
                                            </td>
                                            <td class="editor-cell">
                                                <div class="summernote-editor">{!! $p->content !!}</div>
                                            </td>
                                            <td class="standard-cell">
                                                <div class="summernote-editor step-standard">{!! $p->standard !!}</div>
                                            </td>
                                            @if ($list->status === 'draft')
                                                <td class="text-center align-middle">
                                                    <div class="d-flex flex-column gap-2 align-items-center">
                                                        <button class="btn btn-sm btn-outline-success btn-insert-step"
                                                            title="Chèn bước xuống dưới">
                                                            <i class="fas fa-plus"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-danger btn-remove-step"
                                                            title="Xóa bước">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            @endif
                                        </tr>
                                    @endforeach
                                @endif
                            </tbody>
                            @if ($list->status === 'draft')
                                <tfoot>
                                    <tr>
                                        <td colspan="4" class="text-center p-3">
                                            <div class="d-flex justify-content-center gap-3">
                                                <button class="btn btn-outline-primary" id="btn-add-step">
                                                    <i class="fas fa-plus me-1"></i> Thêm bước mới
                                                </button>
                                                <button class="btn btn-primary" id="btn-save-process-bottom">
                                                    <i class="fas fa-save me-1"></i> Lưu quy trình
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tfoot>
                            @endif
                        </table>

                        {{-- Khung Ký duyệt quy trình --}}
                        @include('components.signature_block', ['wfType' => 'cleaning', 'type' => $type, 'listId' => $list_id])
                    </div>
                </div>
            </div>
        </div>

        <!-- Template for new step -->
        <template id="step-template">
            <tr class="process-step">
                <td class="text-center align-middle">
                    <div class="d-flex align-items-center justify-content-center">
                        <input type="number" class="form-control text-center step-number p-1" style="width: 50px;"
                            value="" />
                    </div>
                </td>
                <td class="editor-cell">
                    <div class="summernote-editor"></div>
                </td>
                <td class="standard-cell">
                    <div class="summernote-editor step-standard"></div>
                </td>
                <td class="text-center align-middle">
                    <div class="d-flex flex-column gap-2 align-items-center">
                        <button class="btn btn-sm btn-outline-success btn-insert-step" title="Chèn bước xuống dưới">
                            <i class="fas fa-plus"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger btn-remove-step" title="Xóa bước">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        </template>
    </div>
@endsection

@section('script')
    <script>
        $(document).ready(function() {
            const isDraft = {{ $list->status === 'draft' ? 'true' : 'false' }};

            const initSummernote = function(element, isStandard = false) {
                let toolbarOptions = [
                    ['style', ['style']],
                    ['font', ['bold', 'italic', 'underline', 'clear']],
                    ['fontname', ['fontname']],
                    ['color', ['color']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['table', ['table']],
                    ['insert', ['link', 'picture']],
                    ['view', ['fullscreen', 'codeview']]
                ];

                if (isStandard) {
                    toolbarOptions = [
                        ['font', ['bold', 'italic', 'underline', 'clear']],
                        ['table', ['table']]
                    ];
                }

                if (!isDraft) {
                    toolbarOptions = false;
                }

                $(element).summernote({
                    minHeight: isDraft ? 150 : null,
                    toolbar: toolbarOptions,
                    dialogsInBody: true,
                    callbacks: {
                        onImageUpload: function(files) {
                            for (let i = 0; i < files.length; i++) {
                                uploadImage(files[i], this);
                            }
                        },
                        onChange: function() {
                            syncHeights();
                        },
                        onInit: function() {
                            syncHeights();
                        }
                    }
                });
            };

            const syncHeights = function() {
                $('.process-step').each(function() {
                    const contentEditable = $(this).find('.editor-cell .note-editable');
                    const standardEditable = $(this).find('.standard-cell .note-editable');

                    if (contentEditable.length && standardEditable.length) {
                        // Reset first to allow shrinking
                        standardEditable.css('min-height', isDraft ? '150px' : '0px');
                        // Set standard editor's min-height to match content editor's height
                        const contentHeight = contentEditable.outerHeight();
                        if (contentHeight > (isDraft ? 150 : 0)) {
                            standardEditable.css('min-height', contentHeight + 'px');
                        }
                    }
                });
            };

            const uploadImage = function(file, editor) {
                let data = new FormData();
                data.append('image', file);
                data.append('_token', '{{ csrf_token() }}');

                $.ajax({
                    url: '{{ route('pages.manu_env.cleaning_process.upload_image') }}',
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    data: data,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.url) {
                            $(editor).summernote('insertImage', response.url);
                        } else {
                            Swal.fire('Lỗi', 'Không thể tải ảnh lên', 'error');
                        }
                    },
                    error: function(xhr) {
                        console.error("Upload error:", xhr.responseText);
                        Swal.fire('Lỗi', 'Có lỗi khi tải ảnh lên (Xem console log)', 'error');
                    }
                });
            };

            const updateStepNumbers = function() {
                $('#process-list .process-step').each(function(index) {
                    $(this).find('.step-number').val(index + 1);
                });
            };

            // Initialize existing summernotes
            $('.editor-cell .summernote-editor').each(function() {
                initSummernote(this, false);
                if (!isDraft) $(this).summernote('disable');
            });
            $('.standard-cell .step-standard').each(function() {
                initSummernote(this, true);
                if (!isDraft) $(this).summernote('disable');
            });

            if ($('#process-list .process-step').length === 0) {
                $('#btn-add-step').click();
            }

            $('#btn-add-step').click(function() {
                const template = document.getElementById('step-template');
                const clone = template.content.cloneNode(true);
                $('#process-list').append(clone);

                const newStep = $('#process-list .process-step').last();
                initSummernote(newStep.find('.editor-cell .summernote-editor'), false);
                initSummernote(newStep.find('.standard-cell .step-standard'), true);
                updateStepNumbers();
            });

            $(document).on('click', '.btn-remove-step', function() {
                const step = $(this).closest('.process-step');
                Swal.fire({
                    title: 'Bạn có chắc chắn muốn xóa bước này?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Đồng ý',
                    cancelButtonText: 'Hủy'
                }).then((result) => {
                    if (result.value) {
                        step.find('.summernote-editor').summernote('destroy');
                        step.remove();
                        updateStepNumbers();
                    }
                });
            });

            $(document).on('click', '.btn-insert-step', function() {
                const currentStep = $(this).closest('.process-step');
                const template = document.getElementById('step-template');
                const clone = template.content.cloneNode(true);

                // Insert after the current step
                currentStep.after(clone);

                const newStep = currentStep.next('.process-step');
                initSummernote(newStep.find('.editor-cell .summernote-editor'), false);
                initSummernote(newStep.find('.standard-cell .step-standard'), true);

                updateStepNumbers();
            });

            // Simple sorting
            if (typeof Sortable !== 'undefined') {
                new Sortable(document.getElementById('process-list'), {
                    handle: '.handle',
                    animation: 150,
                    onEnd: function() {
                        updateStepNumbers();
                    }
                });
            }

            $('#btn-save-process, #btn-save-process-bottom').click(function() {
                const processes = [];
                let valid = true;

                $('#process-list .process-step').each(function() {
                    const step = $(this).find('.step-number').val();
                    // Vì có 2 editor (content và standard), cần phải find chính xác
                    const content = $(this).find('.editor-cell .summernote-editor').summernote(
                        'code');
                    const standard = $(this).find('.standard-cell .step-standard').summernote(
                        'code');

                    if (!step || !content || content === '<p><br></p>') {
                        valid = false;
                    }

                    processes.push({
                        step: step,
                        content: content,
                        standard: standard
                    });
                });

                // Gửi AJAX lưu
                Swal.fire({
                    title: 'Đang lưu...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                $.ajax({
                    url: '{{ route('pages.manu_env.cleaning_process.store', ['type' => $type, 'list_id' => $list_id]) }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        processes: processes
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('Thành công', response.message, 'success');
                        } else {
                            Swal.fire('Lỗi', response.message, 'error');
                        }
                    },
                    error: function(xhr) {
                        Swal.fire('Lỗi', 'Có lỗi khi lưu quy trình', 'error');
                    }
                });
            });
        });
    </script>
@endsection
