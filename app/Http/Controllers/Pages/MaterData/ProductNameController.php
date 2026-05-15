<?php

namespace App\Http\Controllers\Pages\MaterData;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProductNameController extends Controller
{
    public function index()
    {
        $datas = DB::table('product_name')->orderBy('name', 'asc')->get();
        $deparments = DB::table('deparments')->orderBy('name', 'asc')->get();
        $productTypes = ['Thành Phẩm', 'Bán Thành Phẩm', 'Tá Dược', 'Bao Bì']; // Common types
        session()->put(['title' => 'DỮ LIỆU GỐC - TÊN SẢN PHẨM']);
        return view('pages.materData.ProductName.list', [
            'datas' => $datas,
            'deparments' => $deparments,
            'productTypes' => $productTypes
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'shortName' => 'required',
            'productType' => 'required',
            'deparment_code' => 'required',
        ], [
            'name.required' => 'Vui lòng nhập Tên Sản Phẩm',
            'shortName.required' => 'Vui lòng nhập Tên Viết Tắt',
            'productType.required' => 'Vui lòng chọn Loại Sản Phẩm',
            'deparment_code.required' => 'Vui lòng nhập Mã Bộ Phận',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'createErrors')->withInput();
        }

        DB::table('product_name')->insert([
            'name' => $request->name,
            'shortName' => $request->shortName,
            'productType' => $request->productType,
            'deparment_code' => $request->deparment_code,
            'prepareBy' => session('user')['fullName'] ?? 'Admin',
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return redirect()->back()->with('success', 'Đã thêm thành công!');
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'shortName' => 'required',
            'productType' => 'required',
            'deparment_code' => 'required',
        ], [
            'name.required' => 'Vui lòng nhập Tên Sản Phẩm',
            'shortName.required' => 'Vui lòng nhập Tên Viết Tắt',
            'productType.required' => 'Vui lòng chọn Loại Sản Phẩm',
            'deparment_code.required' => 'Vui lòng nhập Mã Bộ Phận',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'updateErrors')->withInput();
        }

        DB::table('product_name')->where('id', $request->id)->update([
            'name' => $request->name,
            'shortName' => $request->shortName,
            'productType' => $request->productType,
            'deparment_code' => $request->deparment_code,
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Cập nhật thành công!');
    }

    public function deActive(Request $request)
    {
        $id = $request->id;
        $active = $request->active;

        DB::table('product_name')->where('id', $id)->update([
            'active' => !$active,
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Đã thay đổi trạng thái thành công!');
    }
}
