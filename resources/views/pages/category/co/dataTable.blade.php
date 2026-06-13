<div class="content-wrapper">
    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card mt-2">
                        <div class="card-header">
                            <h3 class="card-title">Danh sách biểu mẫu thành phần</h3>
                            <button type="button" class="btn btn-outline-primary btn-sm float-right" data-toggle="modal"
                                data-target="#modal-create">
                                Thêm mới
                            </button>
                        </div>
                        <!-- /.card-header -->
                        <div class="card-body">
                            <table id="example1" class="table table-bordered table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th class="text-center" style="width: 20px">STT</th>
                                        <th>Mã Thành phần</th>
                                        <th>Tên Thành phần</th>
                                        <th class="text-center">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $count = 1;
                                    @endphp

                                    @foreach ($datas as $item)
                                        <tr>
                                            <td class="text-center">{{ $count++ }}</td>
                                            <td>{{ $item->code }}</td>
                                            <td>{{ $item->name }}</td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-outline-warning btn-xs mr-2"
                                                    data-toggle="modal" data-target="#modal-update"
                                                    onclick="loadDataUpdate('{{ $item->id }}', '{{ $item->code }}', '{{ $item->name }}')">
                                                    Sửa
                                                </button>
                                                <a href="{{ route('pages.category.co.delete', ['id' => $item->id]) }}"
                                                    class="btn btn-outline-danger btn-xs"
                                                    onclick="return confirm('Bạn có chắc chắn muốn xóa bản ghi này?');">Xóa</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <!-- /.card-body -->
                    </div>
                    <!-- /.card -->
                </div>
                <!-- /.col -->
            </div>
            <!-- /.row -->
        </div>
        <!-- /.container-fluid -->
    </section>
    <!-- /.content -->
</div>

@section('script')
<script>
    function loadDataUpdate(id, code, name) {
        $('#up_id').val(id);
        $('#up_code').val(code);
        $('#up_name').val(name);
    }
</script>
@append
