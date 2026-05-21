<div class="content-wrapper">
    <div class="container-fluid py-4" style="font-family: 'Arimo', sans-serif;">
        <div class="card border-0 shadow-sm"
            style="border-radius: 24px; background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px); border: 1px solid rgba(0,0,0,0.05);">

            <div class="card-header border-0 bg-transparent pt-4 pb-0 px-4">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <h5 class="fw-bold mb-0 text-dark" style="font-family: 'Arimo', sans-serif; letter-spacing: 0.5px;">QUẢN LÝ NGƯỜI DÙNG</h5>
                    <button class="btn btn-success btn-create d-flex align-items-center px-4 fw-bold shadow-sm rounded-pill" data-toggle="modal" data-target="#createModal">
                        <i class="fas fa-plus-circle me-2"></i> Thêm
                    </button>
                </div>
            </div>

            <!-- /.card-Body -->
            <div class="card-body p-4">
                <div class="table-responsive rounded-3 overflow-hidden" style="border: 1px solid #f1f5f9;">
                    <table id="example1" class="table table-hover border-0 align-middle w-100">
                        <thead style="background-color: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                            <tr>
                                <th class="text-center py-3">STT</th>
                                <th>Tên Đăng Nhập</th>
                                <th>Nhóm Người Dùng</th>
                                <th>Tên Người Dùng</th>
                                <th>Phòng Ban</th>
                                <th>Tổ</th>
                                <th>Mail</th>
                                <th>Người Tạo</th>
                                <th>Ngày Tạo</th>
                                <th class="text-center">Chữ ký</th>
                                <th class="text-center">Thu thập chữ ký</th>
                                <th class="text-center">Sửa</th>
                                <th class="text-center">Vô hiệu hóa</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($datas as $data)
                                <tr>
                                    <td class="text-center fw-bold text-muted small">{{ $loop->iteration }} </td>
                                    <td class="fw-bold text-primary">{{ $data->userName }}</td>
                                    <td>
                                        <span class="badge bg-light text-dark border px-2 py-1" style="font-size: 0.85rem; font-weight: 500;">{{ $data->role_names }}</span>
                                    </td>
                                    <td class="fw-bold text-dark">{{ $data->fullName }}</td>
                                    <td>
                                        <span class="badge bg-primary-soft text-primary px-2 py-1" style="font-size: 0.85rem; font-weight: 500;">{{ $data->deparment }}</span>
                                    </td>
                                    <td>{{ $data->groupName }}</td>
                                    <td>{{ $data->mail }}</td>
                                    <td class="small text-muted">{{ $data->prepareBy }}</td>
                                    <td class="small">{{ \Carbon\Carbon::parse($data->created_at)->format('d/m/Y') }}</td>
                                    
                                    <td class="text-center align-middle">
                                        @if(!empty($data->signature_image))
                                            <img src="{{ $data->signature_image }}" style="max-height: 40px; max-width: 120px; mix-blend-mode: multiply; object-fit: contain;" alt="Signature">
                                        @else
                                            <span class="text-muted small"><i>Chưa có</i></span>
                                        @endif
                                    </td>
                                    
                                    <td class="text-center align-middle">
                                        <button type="button" class="btn btn-sm btn-icon btn-light-primary border shadow-sm"
                                            onclick="openSignatureSetupModal(event, {{ $data->id }}, {{ !empty($data->signature_image) ? 'true' : 'false' }})"
                                            title="Thu thập chữ ký cho {{ $data->fullName }}">
                                            <i class="fas fa-signature"></i>
                                        </button>
                                    </td>

                                    <td class="text-center align-middle">
                                        <button type="button" class="btn btn-sm btn-icon btn-light-warning btn-edit border shadow-sm" data-id="{{ $data->id }}"
                                            data-username="{{ $data->userName }}" data-usergroup='@json($data->role_ids)'
                                            data-fullname="{{ $data->fullName }}" data-deparment="{{ $data->deparment }}"
                                            data-groupname="{{ $data->groupName }}" data-mail="{{ $data->mail }}"
                                            data-toggle="modal" data-target="#UpdateModal"
                                            title="Chỉnh sửa">
                                            <i class="fas fa-pen"></i>
                                        </button>
                                    </td>

                                    <td class="text-center align-middle">
                                        <form class="form-deActive d-inline"
                                            action="{{ route('pages.User.user.deActive', ['id' => $data->id]) }}"
                                            method="post">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-icon btn-light-danger border shadow-sm" data-name="{{ $data->userName }}" title="Vô hiệu hóa">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
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


<script src="{{ asset('js/vendor/jquery-1.12.4.min.js') }}"></script>
<script src="{{ asset('js/popper.min.js') }}"></script>
<script src="{{ asset('js/bootstrap.min.js') }}"></script>
<script src="{{ asset('js/sweetalert2.all.min.js') }}"></script>

@if (session('success'))
    <script>
        Swal.fire({
            title: 'Thành công!',
            text: '{{ session('success') }}',
            icon: 'success',
            timer: 2000, // tự đóng sau 2 giây
            showConfirmButton: false
        });
    </script>
@endif

<script>
    $(document).ready(function() {
        document.body.style.overflowY = "auto";

        // Khởi tạo Select2 cho các modal
        $('.select2').select2({
            theme: 'bootstrap4',
            width: '100%'
        });

        $('.btn-edit').click(function() {
            const button = $(this);
            const modal = $('#UpdateModal');
            // Gán dữ liệu vào input
            modal.find('input[name="id"]').val(button.data('id'));
            modal.find('input[name="userName"]').val(button.data('username'));

            // Gán mảng role IDs và trigger change cho Select2
            var roleIds = button.data('usergroup');
            modal.find('select[name="userGroup[]"]').val(roleIds).trigger('change');

            modal.find('input[name="fullName"]').val(button.data('fullname'));
            modal.find('select[name="deparment"]').val(button.data('deparment'));
            modal.find('select[name="groupName"]').val(button.data('groupname'));
            modal.find('input[name="mail"]').val(button.data('mail'));
        });

        $('.form-deActive').on('submit', function(e) {
            e.preventDefault();
            const form = this;
            const name = $(form).find('button[type="submit"]').data('name');


            Swal.fire({
                title: 'Bạn chắc chắn muốn vô hiệu?',
                text: `User: ${name}`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Đồng ý',
                cancelButtonText: 'Hủy'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });

        // Toggle Password Visibility
        $(document).on('click', '.toggle-password', function() {
            var target = $(this).data('target');
            var input = $(target);
            var icon = $(this).find('i');

            if (input.attr('type') === 'password') {
                input.attr('type', 'text');
                icon.removeClass('fa-eye').addClass('fa-eye-slash');
            } else {
                input.attr('type', 'password');
                icon.removeClass('fa-eye-slash').addClass('fa-eye');
            }
        });

    });
</script>

@section('css')
    <style>
        .bg-primary-soft {
            background: rgba(8, 145, 178, 0.1);
        }

        .btn-light-warning {
            background: #fffcf0;
            color: #b45309;
        }

        .btn-light-warning:hover {
            background: #fef3c7;
            color: #92400e;
        }

        .btn-light-info {
            background: #ecfeff;
            color: #0891b2;
        }

        .btn-light-info:hover {
            background: #cffafe;
            color: #0e7490;
        }

        .btn-light-primary {
            background: #f0f9ff;
            color: #0369a1;
        }

        .btn-light-primary:hover {
            background: #e0f2fe;
            color: #075985;
        }

        .btn-light-success {
            background: #f0fdf4;
            color: #15803d;
        }

        .btn-light-success:hover {
            background: #dcfce7;
            color: #166534;
        }

        .btn-light-danger {
            background: #fff1f2;
            color: #be123c;
        }

        .btn-light-danger:hover {
            background: #ffe4e6;
            color: #9f1239;
        }

        .btn-icon {
            width: 32px;
            height: 32px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            transition: all 0.2s;
        }

        .btn-icon:hover {
            transform: translateY(-2px);
        }

        #example1_wrapper .dataTables_scroll {
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid #f1f5f9;
            margin-bottom: 20px;
        }

        #example1 thead th {
            background: #f8fafc;
            color: #475569;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.5px;
            font-weight: 700;
            border-bottom: 2px solid #e2e8f0;
            padding: 15px 12px;
            font-family: 'Arimo', sans-serif;
            text-align: center;
            vertical-align: middle;
        }

        #example1 tbody td {
            font-family: 'Arimo', sans-serif;
            font-size: 14px;
            vertical-align: middle;
        }

        .dataTables_filter input {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            padding: 8px 20px;
            font-size: 13px;
            min-width: 250px;
            transition: all 0.3s;
        }

        .dataTables_filter input:focus {
            background: #fff;
            border-color: #22d3ee;
            box-shadow: 0 0 0 4px rgba(34, 211, 238, 0.1);
            outline: none;
        }

        .page-item.active .page-link {
            background-color: #0891b2;
            border-color: #0891b2;
            box-shadow: 0 4px 12px rgba(8, 145, 178, 0.2);
        }
    </style>
@append
