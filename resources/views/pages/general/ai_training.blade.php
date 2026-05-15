@extends('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection

@section('mainContent')
<div class="content-wrapper">
<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <h2>Giao diện Huấn luyện AI Agent</h2>
            <p>Thêm các quy tắc, định nghĩa, thông tin đặc biệt để "dạy" cho AI.</p>
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
        </div>
    </div>

    <div class="row mt-3">
        <!-- Cột trái: Thêm quy tắc mới -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">Thêm Tri thức Mới (Knowledge)</div>
                <div class="card-body">
                    <form action="{{ route('ai_training.store') }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label>Từ khóa (Khi user hỏi trúng từ này, AI sẽ nhớ quy tắc)</label>
                            <input type="text" name="keyword" class="form-control" required placeholder="VD: BATCH-002, Tổ Phân Liều, Quy định nghỉ phép...">
                        </div>
                        <div class="form-group mt-2">
                            <label>Nội dung kiến thức AI cần học</label>
                            <textarea name="content" class="form-control" rows="4" required placeholder="VD: Lô BATCH-002 đã bị hủy bỏ vào ngày hôm qua. Không được phép tra cứu thông tin chi tiết."></textarea>
                        </div>
                        <button type="submit" class="btn btn-success mt-3">Dạy AI</button>
                    </form>
                </div>
            </div>

            <!-- Danh sách Tri thức hiện có -->
            <div class="card mt-4">
                <div class="card-header bg-secondary text-white">Các Tri thức đã học</div>
                <ul class="list-group list-group-flush">
                    @forelse($knowledges as $kb)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong>{{ $kb->keyword }}</strong><br>
                                <small>{{ $kb->content }}</small>
                            </div>
                            <form action="{{ route('ai_training.delete', $kb->id) }}" method="POST" onsubmit="return confirm('Xóa tri thức này?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger">Xóa</button>
                            </form>
                        </li>
                    @empty
                        <li class="list-group-item text-muted">AI chưa được dạy quy tắc nào.</li>
                    @endforelse
                </ul>
            </div>
        </div>

        <!-- Cột phải: Log & Learn (Các câu hỏi chưa hiểu) -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-warning text-dark">Log Câu Hỏi AI Chưa Trả Lời Được</div>
                <div class="card-body p-0">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Người hỏi</th>
                                <th>Câu hỏi</th>
                                <th>Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($unhandledQueries as $q)
                                <tr>
                                    <td>{{ $q->user_name }}</td>
                                    <td>{{ $q->query_text }}</td>
                                    <td>
                                        @if($q->status == 'pending')
                                            <form action="{{ route('ai_training.resolve', $q->id) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-success">Xong</button>
                                            </form>
                                        @else
                                            <span class="badge bg-success text-white">Đã xử lý</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted">Chưa có dữ liệu.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="alert alert-info mt-3">
                <small><strong>Gợi ý:</strong> Khi có một câu hỏi bị đánh dấu "Chưa trả lời được", bạn hãy xem User đang hỏi về chủ đề gì (VD: "PXVH ở đâu?"). Sau đó, bạn hãy nhập từ khóa "PXVH" và định nghĩa vào cột bên trái để dạy AI. Lần sau AI sẽ trả lời được!</small>
            </div>
        </div>
    </div>
</div>
</div>
@endsection
