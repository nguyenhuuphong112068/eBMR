<?php

namespace App\Http\Controllers\Pages\Category;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CoCategoryController extends Controller
{
    public function index()
    {
        $datas = DB::table('co_category')->orderBy('code', 'asc')->get();
        session()->put(['title' => 'DANH MỤC THÀNH PHẦN (COMPONENT)']);

        return view('pages.category.co.list', [
            'datas' => $datas
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|unique:co_category,code',
            'name' => 'required',
        ], [
            'code.required' => 'Vui lòng nhập mã danh mục.',
            'code.unique' => 'Mã danh mục đã tồn tại.',
            'name.required' => 'Vui lòng nhập tên danh mục.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'createErrors')->withInput();
        }

        DB::table('co_category')->insert([
            'code' => $request->code,
            'name' => $request->name,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Thêm mới danh mục thành công');
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'code' => 'required|unique:co_category,code,' . $request->id,
            'name' => 'required',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'updateErrors')->withInput();
        }

        DB::table('co_category')->where('id', $request->id)->update([
            'code' => $request->code,
            'name' => $request->name,
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Cập nhật danh mục thành công');
    }

    public function delete(Request $request)
    {
        DB::table('co_category')->where('id', $request->query('id'))->delete();
        return redirect()->back()->with('success', 'Xóa danh mục thành công');
    }
}
