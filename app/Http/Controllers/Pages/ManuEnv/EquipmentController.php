<?php

namespace App\Http\Controllers\Pages\ManuEnv;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class EquipmentController extends Controller
{
    public static $stages = [
        1 => 'Cân',
        3 => 'Pha chế',
        4 => 'Trộn Hoàn Tất',
        5 => 'Định Hình',
        6 => 'Bao Phim',
        7 => 'ĐGSC',
        8 => 'ĐGTC',
    ];

    public function index()
    {
        $datas = DB::table('instrument')->orderBy('code', 'asc')->get();
        session()->put(['title' => 'MÔI TRƯỜNG SẢN XUẤT - THIẾT BỊ SẢN XUẤT']);
        return view('pages.manu_env.equipment.list', [
            'datas' => $datas,
            'stages' => self::$stages
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|unique:instrument,code',
            'name' => 'required',
            'stage_id' => 'nullable|in:1,3,4,5,6,7,8',
            'type' => 'required|in:scale,other',
            'connection_type' => 'nullable|in:serial,websocket',
            'ip' => 'nullable|string',
            'port' => 'nullable|string',
            'brand' => 'nullable|in:and,mettler,sartorius,custom',
            'baud_rate' => 'nullable|integer',
            'data_bits' => 'nullable|integer',
            'parity' => 'nullable|in:none,even,odd',
            'stop_bits' => 'nullable|integer',
            'operation_SOP_code' => 'nullable|string|max:50',
            'clearing_SOP_code' => 'nullable|string|max:50',
            'is_Portable_equipment' => 'nullable|boolean',
        ], [
            'code.required' => 'Vui lòng nhập Mã Thiết Bị',
            'code.unique' => 'Mã Thiết Bị đã tồn tại.',
            'name.required' => 'Vui lòng nhập Tên Thiết Bị',
            'stage_id.in' => 'Công đoạn không hợp lệ.',
            'type.required' => 'Vui lòng chọn Loại Thiết Bị',
            'type.in' => 'Loại Thiết Bị không hợp lệ.',
            'connection_type.in' => 'Phương thức kết nối không hợp lệ.',
            'brand.in' => 'Hãng cân không hợp lệ.',
            'baud_rate.integer' => 'Baud rate phải là số nguyên.',
            'data_bits.integer' => 'Data bits phải là số nguyên.',
            'parity.in' => 'Parity không hợp lệ.',
            'stop_bits.integer' => 'Stop bits phải là số nguyên.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'createErrors')->withInput();
        }

        DB::table('instrument')->insert([
            'code' => $request->code,
            'name' => $request->name,
            'stage_id' => $request->stage_id,
            'type' => $request->type,
            'connection_type' => $request->type === 'scale' ? $request->connection_type : null,
            'ip' => $request->type === 'scale' ? $request->ip : null,
            'port' => $request->type === 'scale' ? $request->port : null,
            'brand' => $request->type === 'scale' ? $request->brand : null,
            'baud_rate' => ($request->type === 'scale' && $request->connection_type === 'serial') ? $request->baud_rate : null,
            'data_bits' => ($request->type === 'scale' && $request->connection_type === 'serial') ? $request->data_bits : null,
            'parity' => ($request->type === 'scale' && $request->connection_type === 'serial') ? $request->parity : null,
            'stop_bits' => ($request->type === 'scale' && $request->connection_type === 'serial') ? $request->stop_bits : null,
            'operation_SOP_code' => $request->operation_SOP_code,
            'clearing_SOP_code' => $request->clearing_SOP_code,
            'is_Portable_equipment' => $request->has('is_Portable_equipment') ? 1 : 0,
            'created_by' => session('user')['fullName'] ?? 'Admin',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return redirect()->back()->with('success', 'Đã thêm thành công!');
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|unique:instrument,code,' . $request->id,
            'name' => 'required',
            'stage_id' => 'nullable|in:1,3,4,5,6,7,8',
            'type' => 'required|in:scale,other',
            'connection_type' => 'nullable|in:serial,websocket',
            'ip' => 'nullable|string',
            'port' => 'nullable|string',
            'brand' => 'nullable|in:and,mettler,sartorius,custom',
            'baud_rate' => 'nullable|integer',
            'data_bits' => 'nullable|integer',
            'parity' => 'nullable|in:none,even,odd',
            'stop_bits' => 'nullable|integer',
            'operation_SOP_code' => 'nullable|string|max:50',
            'clearing_SOP_code' => 'nullable|string|max:50',
            'is_Portable_equipment' => 'nullable|boolean',
        ], [
            'code.required' => 'Vui lòng nhập Mã Thiết Bị',
            'code.unique' => 'Mã Thiết Bị đã tồn tại.',
            'name.required' => 'Vui lòng nhập Tên Thiết Bị',
            'stage_id.in' => 'Công đoạn không hợp lệ.',
            'type.required' => 'Vui lòng chọn Loại Thiết Bị',
            'type.in' => 'Loại Thiết Bị không hợp lệ.',
            'connection_type.in' => 'Phương thức kết nối không hợp lệ.',
            'brand.in' => 'Hãng cân không hợp lệ.',
            'baud_rate.integer' => 'Baud rate phải là số nguyên.',
            'data_bits.integer' => 'Data bits phải là số nguyên.',
            'parity.in' => 'Parity không hợp lệ.',
            'stop_bits.integer' => 'Stop bits phải là số nguyên.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'updateErrors')->withInput();
        }

        DB::table('instrument')->where('id', $request->id)->update([
            'code' => $request->code,
            'name' => $request->name,
            'stage_id' => $request->stage_id,
            'type' => $request->type,
            'connection_type' => $request->type === 'scale' ? $request->connection_type : null,
            'ip' => $request->type === 'scale' ? $request->ip : null,
            'port' => $request->type === 'scale' ? $request->port : null,
            'brand' => $request->type === 'scale' ? $request->brand : null,
            'baud_rate' => ($request->type === 'scale' && $request->connection_type === 'serial') ? $request->baud_rate : null,
            'data_bits' => ($request->type === 'scale' && $request->connection_type === 'serial') ? $request->data_bits : null,
            'parity' => ($request->type === 'scale' && $request->connection_type === 'serial') ? $request->parity : null,
            'stop_bits' => ($request->type === 'scale' && $request->connection_type === 'serial') ? $request->stop_bits : null,
            'operation_SOP_code' => $request->operation_SOP_code,
            'clearing_SOP_code' => $request->clearing_SOP_code,
            'is_Portable_equipment' => $request->has('is_Portable_equipment') ? 1 : 0,
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Cập nhật thành công!');
    }

    public function delete(Request $request)
    {
        DB::table('instrument')->where('id', $request->id)->delete();
        return redirect()->back()->with('success', 'Đã xóa thành công!');
    }
}
