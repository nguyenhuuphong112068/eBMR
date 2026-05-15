<div class="modal fade" id="updateModal" tabindex="-1" role="dialog" aria-labelledby="updateModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="updateModalLabel">Cập nhật Tên Sản Phẩm</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('pages.materData.productName.update') }}" method="POST">
                @csrf
                <input type="hidden" name="id" id="update_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="update_name">Tên Sản Phẩm <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="update_name" name="name" required>
                        @if ($errors->updateErrors->has('name'))
                            <span class="text-danger">{{ $errors->updateErrors->first('name') }}</span>
                        @endif
                    </div>
                    <div class="form-group">
                        <label for="update_shortName">Tên Viết Tắt <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="update_shortName" name="shortName" required>
                        @if ($errors->updateErrors->has('shortName'))
                            <span class="text-danger">{{ $errors->updateErrors->first('shortName') }}</span>
                        @endif
                    </div>
                    <div class="form-group">
                        <label for="update_productType">Loại Sản Phẩm <span class="text-danger">*</span></label>
                        <select class="form-control" id="update_productType" name="productType" required>
                            <option value="">-- Chọn Loại Sản Phẩm --</option>
                            @foreach($productTypes as $type)
                                <option value="{{ $type }}">{{ $type }}</option>
                            @endforeach
                        </select>
                        @if ($errors->updateErrors->has('productType'))
                            <span class="text-danger">{{ $errors->updateErrors->first('productType') }}</span>
                        @endif
                    </div>
                    <div class="form-group">
                        <label for="update_deparment_code">Bộ Phận <span class="text-danger">*</span></label>
                        <select class="form-control" id="update_deparment_code" name="deparment_code" required>
                            <option value="">-- Chọn Bộ Phận --</option>
                            @foreach($deparments as $dept)
                                <option value="{{ $dept->shortName }}">
                                    {{ $dept->name }} ({{ $dept->shortName }})
                                </option>
                            @endforeach
                        </select>
                        @if ($errors->updateErrors->has('deparment_code'))
                            <span class="text-danger">{{ $errors->updateErrors->first('deparment_code') }}</span>
                        @endif
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">Cập nhật</button>
                </div>
            </form>
        </div>
    </div>
</div>

@if($errors->updateErrors->any())
    <script>
        $(document).ready(function() {
            $('#updateModal').modal('show');
        });
    </script>
@endif
