<?php

namespace App\Http\Controllers\Pages\MaterData;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MarketController extends Controller
{
    public function index()
    {
        $datas = DB::table('market')->orderBy('name', 'asc')->get();
        session()->put(['title' => 'DỮ LIỆU GỐC - THỊ TRƯỜNG']);
        return view('pages.materData.market.list', ['datas' => $datas]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|unique:market,code',
            'name' => 'required|unique:market,name',
        ], [
            'name.required' => 'Vui lòng nhập Tên Thị Trường',
            'name.unique' => 'Tên Thị Trường đã tồn tại.',
            'code.required' => 'Vui lòng nhập Mã Thị Trường',
            'code.unique' => 'Mã Thị Trường đã tồn tại.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'createErrors')->withInput();
        }

        DB::table('market')->insert([
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
            'code' => 'required|unique:market,code,' . $request->id,
            'name' => 'required|unique:market,name,' . $request->id,
        ], [
            'name.required' => 'Vui lòng nhập Tên Thị Trường',
            'name.unique' => 'Tên Thị Trường đã tồn tại.',
            'code.required' => 'Vui lòng nhập Mã Thị Trường',
            'code.unique' => 'Mã Đơn Vị đã tồn tại.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'updateErrors')->withInput();
        }

        DB::table('market')->where('id', $request->id)->update([
            'code' => $request->code,
            'name' => $request->name,
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Cập nhật thành công!');
    }

    public function delete(Request $request)
    {
        $id = $request->id;

        DB::table('market')->where('id', $id)->delete();

        return redirect()->back()->with('success', 'Đã xóa thành công!');
    }
}
