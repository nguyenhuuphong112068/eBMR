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

    public function index(Request $request)
    {
        $latestEqLogs = DB::table('room_logbooks')
            ->whereNotNull('equipment_id')
            ->select('equipment_id', DB::raw('MAX(id) as max_id'))
            ->groupBy('equipment_id');

        $query = DB::table('instrument')
            ->leftJoinSub($latestEqLogs, 'latest_logs', function ($join) {
                $join->on('instrument.id', '=', 'latest_logs.equipment_id');
            })
            ->leftJoin('room_logbooks', 'latest_logs.max_id', '=', 'room_logbooks.id')
            ->select('instrument.*', 'room_logbooks.current_status as eq_status')
            ->orderBy('instrument.code', 'asc');
        
        $selectedDepartment = $request->has('department') ? $request->department : 'PXV1';
        
        if ($selectedDepartment !== '') {
            $query->where('department_code', $selectedDepartment);
        }
        
        $datas = $query->get();
        
        $departments = DB::table('instrument')
            ->select('department_code')
            ->whereNotNull('department_code')
            ->where('department_code', '!=', '')
            ->distinct()
            ->orderBy('department_code', 'asc')
            ->pluck('department_code');

        session()->put(['title' => 'MÔI TRƯỜNG SẢN XUẤT - THIẾT BỊ SẢN XUẤT']);
        return view('pages.manu_env.equipment.list', [
            'datas' => $datas,
            'stages' => self::$stages,
            'departments' => $departments,
            'selectedDepartment' => $selectedDepartment
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

    public function getCalibrationLabel($code)
    {
        try {
            // Determine DB connection based on department_code
            $localInst = DB::table('instrument')->where('code', $code)->first();
            $connectionName = ($localInst && in_array($localInst->department_code, ['PXV1', 'PXTN'])) ? 'cal1' : 'cal2';

            // Check in calibration DB connection for Inst_Master_1
            $inst = DB::connection($connectionName)->table('Inst_Master_1')
                ->where('Parent_Equip_id', $code)
                ->orWhere('Inst_id', $code)
                ->first();
            
            if (!$inst) {
                return response()->json(['success' => false, 'message' => 'Không có nhãn Hiệu chuẩn']);
            }

            // Determine parent_id
            $parent_id = $inst->Parent_Equip_id ?: $inst->Inst_id;
            
            // Get parent info - try cal connection first, then local DB
            $parentInst = DB::connection($connectionName)->table('Inst_Master_1')->where('Inst_id', $parent_id)->first();
            
            $parentName = $inst->Inst_Name;
            if ($parentInst) {
                $parentName = $parentInst->Inst_Name;
            } elseif ($localInst) {
                $parentName = $localInst->name;
            }

            $parentData = [
                'name' => $parentName,
                'code' => $parent_id
            ];

            // Get all children (including itself if it shares the same Parent_Equip_id)
            $children = DB::connection($connectionName)->table('Inst_Master_1')
                ->where('Parent_Equip_id', $parent_id)
                ->where('Inst_Status', 'Active')
                ->get();
            
            $childrenData = [];
            foreach ($children as $child) {
                // Get latest completed schedule (Pass or where Next_cal_date is not null)
                $schedule = DB::connection($connectionName)->table('Schedule_Master_1')
                    ->where('Inst_ID', $child->Inst_id)
                    ->whereNotNull('Next_cal_date')
                    ->orderBy('SCH_ID', 'desc')
                    ->first();
                
                $cal_date = '-';
                $exp_date = '-';
                $is_expired = false;

                if ($schedule) {
                    if ($schedule->Next_cal_date) {
                        $nextCalDate = \Carbon\Carbon::parse($schedule->Next_cal_date);
                        $exp_date = $nextCalDate->format('d/m/Y');
                        
                        // Check if expired (Next_cal_date < today)
                        if ($nextCalDate->startOfDay()->lt(\Carbon\Carbon::now()->startOfDay())) {
                            $is_expired = true;
                        }
                    }

                    // To get the actual calibration date, look for the most recent "Pass" record
                    $lastPass = DB::connection($connectionName)->table('Schedule_Master_1')
                        ->where('Inst_ID', $child->Inst_id)
                        ->where('Sch_Result_Status', 'Pass')
                        ->orderBy('SCH_ID', 'desc')
                        ->first();
                    
                    if ($lastPass && $lastPass->Sch_CalDone_On) {
                        $cal_date = \Carbon\Carbon::parse($lastPass->Sch_CalDone_On)->format('d/m/Y');
                    } elseif ($schedule->Sch_CalDone_On) {
                        $cal_date = \Carbon\Carbon::parse($schedule->Sch_CalDone_On)->format('d/m/Y');
                    }
                }

                $childrenData[] = [
                    'id' => $child->Inst_id,
                    'name' => $child->Inst_Name,
                    'calibrated_on' => $cal_date,
                    'exp_date' => $exp_date,
                    'is_expired' => $is_expired
                ];
            }

            return response()->json([
                'success' => true,
                'parent' => $parentData,
                'children' => $childrenData
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Lỗi kết nối CSDL: ' . $e->getMessage()]);
        }
    }

    public function getMaintenanceLabel($code)
    {
        try {
            // Determine DB connection based on department_code
            $localInst = DB::table('instrument')->where('code', $code)->first();
            $connectionName = ($localInst && in_array($localInst->department_code, ['PXV1', 'PXTN'])) ? 'cal1' : 'cal2';

            // Check in maintenance DB connection for Inst_Master_2
            $inst = DB::connection($connectionName)->table('Inst_Master_2')
                ->where('Parent_Equip_id', $code)
                ->orWhere('Inst_id', $code)
                ->first();
                
            if (!$inst) {
                return response()->json(['success' => false, 'message' => 'Không có nhãn Bảo trì']);
            }

            // Determine parent_id
            $parent_id = $inst->Parent_Equip_id ?: $inst->Inst_id;
            
            // Get parent info - try cal connection first, then local DB
            $parentInst = DB::connection($connectionName)->table('Inst_Master_2')->where('Inst_id', $parent_id)->first();
            
            $parentName = $inst->Inst_Name;
            if ($parentInst) {
                $parentName = $parentInst->Inst_Name;
            } elseif ($localInst) {
                $parentName = $localInst->name;
            }

            $parentData = [
                'name' => $parentName,
                'code' => $parent_id
            ];

            // Get all children (including itself if it shares the same Parent_Equip_id)
            $children = DB::connection($connectionName)->table('Inst_Master_2')
                ->where('Parent_Equip_id', $parent_id)
                ->where('Inst_Status', 'Active')
                ->get();
            
            $childrenData = [];
            $maxCyclePriority = -1;
            $headerWarning = 0; // 0: Green, 1: Orange, 2: Red

            $cyclePriorityMap = [
                'yearly' => 5,
                'half yearly' => 4,
                'quaterly' => 3,
                'monthly' => 2,
                'weekly' => 1,
                'daily' => 0
            ];

            foreach ($children as $child) {
                // Get all pending schedules for this child
                $schedules = DB::connection($connectionName)->table('Schedule_Master_2')
                    ->where('Inst_ID', $child->Inst_id)
                    ->where('Sch_Result_Status', 'Pending')
                    ->orderBy('Sch_DueDate', 'asc')
                    ->get();
                
                foreach ($schedules as $schedule) {
                    $dueDateStr = $schedule->Sch_DueDate ?: $schedule->Next_cal_date;
                    if (!$dueDateStr) continue;

                    $dueDate = \Carbon\Carbon::parse($dueDateStr);
                    $today = \Carbon\Carbon::now()->startOfDay();
                    
                    $cycleType = $schedule->Sch_Type;
                    $cycleKey = strtolower(trim($cycleType));
                    $gracePeriod = ($cycleKey === 'monthly') ? 7 : 21;
                    
                    $level = 0;
                    if ($today->gt($dueDate->copy()->startOfDay())) {
                        if ($today->lte($dueDate->copy()->startOfDay()->addDays($gracePeriod))) {
                            $level = 1; // Orange
                        } else {
                            $level = 2; // Red
                        }
                    }

                    $priority = isset($cyclePriorityMap[$cycleKey]) ? $cyclePriorityMap[$cycleKey] : 0;
                    if ($priority > $maxCyclePriority) {
                        $maxCyclePriority = $priority;
                        $headerWarning = $level;
                    } elseif ($priority === $maxCyclePriority) {
                        if ($level > $headerWarning) {
                            $headerWarning = $level;
                        }
                    }

                    // Find last pass date for this cycle
                    $lastPass = DB::connection($connectionName)->table('Schedule_Master_2')
                        ->where('Inst_ID', $child->Inst_id)
                        ->where('Sch_Type', $cycleType)
                        ->where('Sch_Result_Status', 'Pass')
                        ->orderBy('SCH_ID', 'desc')
                        ->first();
                    
                    $cal_date = '-';
                    if ($lastPass && $lastPass->Sch_CalDone_On) {
                        $cal_date = \Carbon\Carbon::parse($lastPass->Sch_CalDone_On)->format('d/m/Y');
                    } elseif ($schedule->Sch_CalDone_On) {
                        $cal_date = \Carbon\Carbon::parse($schedule->Sch_CalDone_On)->format('d/m/Y');
                    }

                    $childrenData[] = [
                        'id' => $child->Inst_id,
                        'name' => $child->Inst_Name,
                        'cycle' => $cycleType,
                        'calibrated_on' => $cal_date,
                        'exp_date' => $dueDate->format('d/m/Y'),
                        'warning_level' => $level
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'parent' => $parentData,
                'children' => $childrenData,
                'header_warning' => $headerWarning
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Lỗi kết nối CSDL: ' . $e->getMessage()]);
        }
    }

    public function getStatusBatch(Request $request)
    {
        $codes = $request->input('codes', []);
        $results = [];

        if (empty($codes)) {
            return response()->json([]);
        }

        $localInsts = DB::table('instrument')->whereIn('code', $codes)->get()->keyBy('code');

        foreach ($codes as $code) {
            $localInst = $localInsts->get($code);
            $connectionName = ($localInst && in_array($localInst->department_code, ['PXV1', 'PXTN'])) ? 'cal1' : 'cal2';

            $cal_expired = false;
            $has_cal = false;
            $maint_warning = 0; // 0=green, 1=orange, 2=red
            $has_maint = false;

            // --- Check Calibration ---
            try {
                $inst1 = DB::connection($connectionName)->table('Inst_Master_1')
                    ->where('Parent_Equip_id', $code)->orWhere('Inst_id', $code)->first();
                
                if ($inst1) {
                    $has_cal = true;
                    $parentId1 = $inst1->Parent_Equip_id ?: $inst1->Inst_id;
                    $children1 = DB::connection($connectionName)->table('Inst_Master_1')
                        ->where('Parent_Equip_id', $parentId1)->where('Inst_Status', 'Active')->pluck('Inst_id');
                    
                    foreach ($children1 as $childId) {
                        $schedule1 = DB::connection($connectionName)->table('Schedule_Master_1')
                            ->where('Inst_ID', $childId)->whereNotNull('Next_cal_date')
                            ->orderBy('SCH_ID', 'desc')->first();
                        if ($schedule1 && $schedule1->Next_cal_date) {
                            if (\Carbon\Carbon::parse($schedule1->Next_cal_date)->startOfDay()->lt(\Carbon\Carbon::now()->startOfDay())) {
                                $cal_expired = true;
                                break;
                            }
                        }
                    }
                }
            } catch (\Exception $e) {}

            // --- Check Maintenance ---
            try {
                $inst2 = DB::connection($connectionName)->table('Inst_Master_2')
                    ->where('Parent_Equip_id', $code)->orWhere('Inst_id', $code)->first();
                
                if ($inst2) {
                    $has_maint = true;
                    $parentId2 = $inst2->Parent_Equip_id ?: $inst2->Inst_id;
                    $children2 = DB::connection($connectionName)->table('Inst_Master_2')
                        ->where('Parent_Equip_id', $parentId2)->where('Inst_Status', 'Active')->pluck('Inst_id');

                    $maxCyclePriority = -1;
                    $headerWarning = 0;
                    $cyclePriorityMap = ['yearly'=>5, 'half yearly'=>4, 'quaterly'=>3, 'monthly'=>2, 'weekly'=>1, 'daily'=>0];

                    foreach ($children2 as $childId) {
                        $schedules2 = DB::connection($connectionName)->table('Schedule_Master_2')
                            ->where('Inst_ID', $childId)->where('Sch_Result_Status', 'Pending')
                            ->orderBy('Sch_DueDate', 'asc')->get();
                        
                        foreach ($schedules2 as $schedule2) {
                            $dueDateStr = $schedule2->Sch_DueDate ?: $schedule2->Next_cal_date;
                            if (!$dueDateStr) continue;

                            $dueDate = \Carbon\Carbon::parse($dueDateStr);
                            $today = \Carbon\Carbon::now()->startOfDay();
                            
                            $cycleKey = strtolower(trim($schedule2->Sch_Type));
                            $gracePeriod = ($cycleKey === 'monthly') ? 7 : 21;
                            
                            $level = 0;
                            if ($today->gt($dueDate->copy()->startOfDay())) {
                                if ($today->lte($dueDate->copy()->startOfDay()->addDays($gracePeriod))) {
                                    $level = 1;
                                } else {
                                    $level = 2;
                                }
                            }

                            $priority = isset($cyclePriorityMap[$cycleKey]) ? $cyclePriorityMap[$cycleKey] : 0;
                            if ($priority > $maxCyclePriority) {
                                $maxCyclePriority = $priority;
                                $headerWarning = $level;
                            } elseif ($priority === $maxCyclePriority) {
                                if ($level > $headerWarning) {
                                    $headerWarning = $level;
                                }
                            }
                        }
                    }
                    $maint_warning = $headerWarning;
                }
            } catch (\Exception $e) {}

            $results[$code] = [
                'has_cal' => $has_cal,
                'has_maint' => $has_maint,
                'cal_expired' => $cal_expired,
                'maint_warning' => $maint_warning
            ];
        }

        return response()->json($results);
    }
}
