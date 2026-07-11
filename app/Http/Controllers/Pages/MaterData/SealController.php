<?php

namespace App\Http\Controllers\Pages\MaterData;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SealController extends Controller
{
    public function index()
    {
        $datas = DB::table('seals')->orderBy('name', 'asc')->get();
        session()->put(['title' => 'DỮ LIỆU GỐC - CON DẤU']);
        return view('pages.materData.seal.list', ['datas' => $datas]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:seals,name',
            'content' => 'required',
            'color' => 'required',
            'border_style' => 'nullable|in:single,double',
            'size' => 'nullable|integer|min:50|max:200',
        ], [
            'name.required' => 'Vui lòng nhập Tên Con Dấu',
            'name.unique' => 'Tên Con Dấu đã tồn tại.',
            'content.required' => 'Vui lòng nhập Nội Dung Chính',
            'color.required' => 'Vui lòng chọn Màu Dấu',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'createErrors')->withInput();
        }

        DB::table('seals')->insert([
            'name' => $request->name,
            'header' => $request->header ?: null,
            'content' => $request->content,
            'footer' => $request->footer ?: null,
            'color' => $request->color,
            'border_style' => $request->border_style ?: 'double',
            'size' => (int) ($request->size ?: 100),
            'active' => true,
            'created_by' => session('user')['fullName'] ?? 'Admin',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return redirect()->back()->with('success', 'Đã thêm con dấu thành công!');
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:seals,name,' . $request->id,
            'content' => 'required',
            'color' => 'required',
            'border_style' => 'nullable|in:single,double',
            'size' => 'nullable|integer|min:50|max:200',
        ], [
            'name.required' => 'Vui lòng nhập Tên Con Dấu',
            'name.unique' => 'Tên Con Dấu đã tồn tại.',
            'content.required' => 'Vui lòng nhập Nội Dung Chính',
            'color.required' => 'Vui lòng chọn Màu Dấu',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'updateErrors')->withInput();
        }

        DB::table('seals')->where('id', $request->id)->update([
            'name' => $request->name,
            'header' => $request->header ?: null,
            'content' => $request->content,
            'footer' => $request->footer ?: null,
            'color' => $request->color,
            'border_style' => $request->border_style ?: 'double',
            'size' => (int) ($request->size ?: 100),
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Cập nhật con dấu thành công!');
    }

    public function deActive(Request $request)
    {
        DB::table('seals')->where('id', $request->id)->update([
            'active' => !$request->active,
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Đã cập nhật trạng thái thành công!');
    }
}
