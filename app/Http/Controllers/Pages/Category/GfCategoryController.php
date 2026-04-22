<?php

namespace App\Http\Controllers\Pages\Category;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class GfCategoryController extends Controller
{
    public function index()
    {
        $datas = DB::table('gf_category')->orderBy('code', 'asc')->get();
        session()->put(['title' => 'DANH MỤC BIỂU MẪU DÙNG CHUNG']);

        return view('pages.category.gf.list', [
            'datas' => $datas
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|unique:gf_category,code',
            'name' => 'required',
        ], [
            'code.required' => 'Vui lòng nhập mã biểu mẫu.',
            'code.unique' => 'Mã biểu mẫu đã tồn tại.',
            'name.required' => 'Vui lòng nhập tên biểu mẫu.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'createErrors')->withInput();
        }

        DB::table('gf_category')->insert([
            'code' => $request->code,
            'name' => $request->name,
            'relatived_sop_no' => $request->relatived_sop_no,
            'active' => true,
            'status_code' => 'Active',
            'created_by_code' => session('user')['fullName'] ?? 'System',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Thêm mới danh mục thành công');
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'code' => 'required|unique:gf_category,code,' . $request->id,
            'name' => 'required',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'updateErrors')->withInput();
        }

        DB::table('gf_category')->where('id', $request->id)->update([
            'code' => $request->code,
            'name' => $request->name,
            'relatived_sop_no' => $request->relatived_sop_no,
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Cập nhật danh mục thành công');
    }

    public function delete(Request $request)
    {
        DB::table('gf_category')->where('id', $request->query('id'))->delete();
        return redirect()->back()->with('success', 'Xóa danh mục thành công');
    }
}
