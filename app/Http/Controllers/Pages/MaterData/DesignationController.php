<?php

namespace App\Http\Controllers\Pages\MaterData;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DesignationController extends Controller
{
    public function index()
    {
        $datas = DB::table('designations')->orderBy('name', 'asc')->get();
        session()->put(['title' => 'DỮ LIỆU GỐC - CHỨC VỤ']);
        return view('pages.materData.Designation.list', ['datas' => $datas]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'shortName' => 'required|unique:designations,shortName',
            'name' => 'required|unique:designations,name',
        ], [
            'name.required' => 'Vui lòng nhập Tên Chức Vụ',
            'name.unique' => 'Tên Chức Vụ đã tồn tại.',
            'shortName.required' => 'Vui lòng nhập Tên Viết Tắt',
            'shortName.unique' => 'Tên Viết Tắt đã tồn tại.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'createErrors')->withInput();
        }

        DB::table('designations')->insert([
            'shortName' => $request->shortName,
            'name' => $request->name,
            'active' => true,
            'prepareBy' => session('user')['fullName'] ?? 'Admin',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return redirect()->back()->with('success', 'Đã thêm thành công!');
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'shortName' => 'required|unique:designations,shortName,' . $request->id,
            'name' => 'required|unique:designations,name,' . $request->id,
        ], [
            'name.required' => 'Vui lòng nhập Tên Chức Vụ',
            'name.unique' => 'Tên Chức Vụ đã tồn tại.',
            'shortName.required' => 'Vui lòng nhập Tên Viết Tắt',
            'shortName.unique' => 'Tên Viết Tắt đã tồn tại.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'updateErrors')->withInput();
        }

        DB::table('designations')->where('id', $request->id)->update([
            'shortName' => $request->shortName,
            'name' => $request->name,
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Cập nhật thành công!');
    }

    public function deActive(Request $request)
    {
        $id = $request->id;
        $active = $request->active;

        DB::table('designations')->where('id', $id)->update([
            'active' => !$active,
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Đã thay đổi trạng thái thành công!');
    }
}
