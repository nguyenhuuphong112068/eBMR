<div class="modal fade" id="createModal" tabindex="-1" role="dialog" aria-labelledby="createModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createModalLabel">Thêm mới Tên Sản Phẩm</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('pages.materData.productName.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label for="name">Tên Sản Phẩm <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}" required>
                        @if ($errors->createErrors->has('name'))
                            <span class="text-danger">{{ $errors->createErrors->first('name') }}</span>
                        @endif
                    </div>
                    <div class="form-group">
                        <label for="shortName">Tên Viết Tắt <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="shortName" name="shortName" value="{{ old('shortName') }}" required>
                        @if ($errors->createErrors->has('shortName'))
                            <span class="text-danger">{{ $errors->createErrors->first('shortName') }}</span>
                        @endif
                    </div>
                    <div class="form-group">
                        <label for="productType">Loại Sản Phẩm <span class="text-danger">*</span></label>
                        <select class="form-control" id="productType" name="productType" required>
                            <option value="">-- Chọn Loại Sản Phẩm --</option>
                            @foreach($productTypes as $type)
                                <option value="{{ $type }}" {{ old('productType') == $type ? 'selected' : '' }}>{{ $type }}</option>
                            @endforeach
                        </select>
                        @if ($errors->createErrors->has('productType'))
                            <span class="text-danger">{{ $errors->createErrors->first('productType') }}</span>
                        @endif
                    </div>
                    <div class="form-group">
                        <label for="deparment_code">Bộ Phận <span class="text-danger">*</span></label>
                        <select class="form-control" id="deparment_code" name="deparment_code" required>
                            <option value="">-- Chọn Bộ Phận --</option>
                            @foreach($deparments as $dept)
                                <option value="{{ $dept->shortName }}" {{ old('deparment_code') == $dept->shortName ? 'selected' : '' }}>
                                    {{ $dept->name }} ({{ $dept->shortName }})
                                </option>
                            @endforeach
                        </select>
                        @if ($errors->createErrors->has('deparment_code'))
                            <span class="text-danger">{{ $errors->createErrors->first('deparment_code') }}</span>
                        @endif
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">Lưu</button>
                </div>
            </form>
        </div>
    </div>
</div>

@if($errors->createErrors->any())
    <script>
        $(document).ready(function() {
            $('#createModal').modal('show');
        });
    </script>
@endif
