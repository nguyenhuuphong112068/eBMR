<?php

namespace App\Http\Controllers\Pages\MaterData;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MaterialSpecController extends Controller
{
    public function index()
    {
        $datas = DB::table('material_spec')->orderBy('name', 'asc')->get();
        session()->put(['title' => 'DỮ LIỆU GỐC - TIÊU CHUẨN NGUYÊN LIỆU']);
        return view('pages.materData.MaterialSpec.list', ['datas' => $datas]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:material_spec,name',
        ], [
            'name.required' => 'Vui lòng nhập Tên Tiêu Chuẩn',
            'name.unique' => 'Tên Tiêu Chuẩn đã tồn tại.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'createErrors')->withInput();
        }

        DB::table('material_spec')->insert([
            'name' => $request->name,
            'created_by' => session('user')['fullName'] ?? 'Admin',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return redirect()->back()->with('success', 'Đã thêm thành công!');
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:material_spec,name,' . $request->id,
        ], [
            'name.required' => 'Vui lòng nhập Tên Tiêu Chuẩn',
            'name.unique' => 'Tên Tiêu Chuẩn đã tồn tại.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'updateErrors')->withInput();
        }

        DB::table('material_spec')->where('id', $request->id)->update([
            'name' => $request->name,
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Cập nhật thành công!');
    }

    public function delete(Request $request)
    {
        DB::table('material_spec')->where('id', $request->id)->delete();
        return redirect()->back()->with('success', 'Đã xóa thành công!');
    }
}
