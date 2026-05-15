<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AiTrainingController extends Controller
{
    public function index()
    {
        $knowledges = DB::table('ai_knowledge_base')->orderBy('id', 'desc')->get();
        $unhandledQueries = DB::table('ai_unhandled_queries')->orderBy('id', 'desc')->limit(100)->get();

        return view('pages.general.ai_training', compact('knowledges', 'unhandledQueries'));
    }

    public function storeKnowledge(Request $request)
    {
        $request->validate([
            'keyword' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        DB::table('ai_knowledge_base')->insert([
            'keyword' => $request->keyword,
            'content' => $request->content,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Đã thêm quy tắc huấn luyện mới thành công!');
    }

    public function deleteKnowledge($id)
    {
        DB::table('ai_knowledge_base')->where('id', $id)->delete();
        return redirect()->back()->with('success', 'Đã xóa quy tắc!');
    }

    public function resolveQuery($id)
    {
        DB::table('ai_unhandled_queries')->where('id', $id)->update(['status' => 'resolved']);
        return redirect()->back()->with('success', 'Đã đánh dấu xử lý xong câu hỏi!');
    }
}
