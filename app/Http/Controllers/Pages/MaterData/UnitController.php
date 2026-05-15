<?php

namespace App\Http\Controllers\Pages\MaterData;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class UnitController extends Controller
{
    public function index()
    {
        $datas = DB::table('unit')->orderBy('name', 'asc')->get();
        session()->put(['title' => 'DỮ LIỆU GỐC - ĐƠN VỊ']);
        return view('pages.materData.Unit.list', ['datas' => $datas]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|unique:unit,code',
            'name' => 'required|unique:unit,name',
        ], [
            'name.required' => 'Vui lòng nhập Tên Đơn Vị',
            'name.unique' => 'Tên Đơn Vị đã tồn tại.',
            'code.required' => 'Vui lòng nhập Mã Đơn Vị',
            'code.unique' => 'Mã Đơn Vị đã tồn tại.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'createErrors')->withInput();
        }

        DB::table('unit')->insert([
            'code' => $request->code,
            'name' => $request->name,
            'active' => true,
            'created_by' => session('user')['fullName'] ?? 'Admin',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return redirect()->back()->with('success', 'Đã thêm thành công!');
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|unique:unit,code,' . $request->id,
            'name' => 'required|unique:unit,name,' . $request->id,
        ], [
            'name.required' => 'Vui lòng nhập Tên Đơn Vị',
            'name.unique' => 'Tên Đơn Vị đã tồn tại.',
            'code.required' => 'Vui lòng nhập Mã Đơn Vị',
            'code.unique' => 'Mã Đơn Vị đã tồn tại.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'updateErrors')->withInput();
        }

        DB::table('unit')->where('id', $request->id)->update([
            'code' => $request->code,
            'name' => $request->name,
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Cập nhật thành công!');
    }

    public function deActive(Request $request)
    {
        $id = $request->id;
        $active = $request->active;

        DB::table('unit')->where('id', $id)->update([
            'active' => !$active,
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Đã thay đổi trạng thái thành công!');
    }
}
