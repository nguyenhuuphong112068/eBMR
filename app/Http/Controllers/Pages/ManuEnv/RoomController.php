<?php

namespace App\Http\Controllers\Pages\ManuEnv;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RoomController extends Controller
{
    public static $stages = [
        1 => [
            'name' => 'Cân Nguyên Liệu',
            'group' => 'Trung Tâm Cân',
            'group_code' => 1
        ],
        3 => [
            'name' => 'Pha Chế',
            'group' => 'Pha Chế',
            'group_code' => 3
        ],
        4 => [
            'name' => 'Trộn Hoàn Tất',
            'group' => 'Trộn Hoàn Tất',
            'group_code' => 4
        ],
        5 => [
            'name' => 'Định Hình',
            'group' => 'Định Hình',
            'group_code' => 5
        ],
        6 => [
            'name' => 'Bao Phim',
            'group' => 'Bao Phim',
            'group_code' => 6
        ],
        7 => [
            'name' => 'Đóng Gói Sơ Cấp',
            'group' => 'Đóng Gói Sơ Cấp',
            'group_code' => 7
        ],
        8 => [
            'name' => 'Đóng Gói Thứ Cấp',
            'group' => 'Đóng Gói Thứ Cấp',
            'group_code' => 8
        ]
    ];

    public function index(Request $request)
    {
        $selectedDept = $request->query('department', 'PXV1');

        $datas = DB::connection('pms')->table('room')
            ->where('deparment_code', $selectedDept)
            ->where('stage_code', '!=', 8)
            ->orderBy('order_by', 'asc')
            ->orderBy('code', 'asc')
            ->get();
            
        $latestRoomLogs = DB::table('room_logbooks')
            ->whereNull('equipment_id')
            ->select('room_id', DB::raw('MAX(id) as max_id'))
            ->groupBy('room_id');

        $roomStatuses = DB::table('room_logbooks')
            ->joinSub($latestRoomLogs, 'latest_logs', function ($join) {
                $join->on('room_logbooks.id', '=', 'latest_logs.max_id');
            })
            ->select('room_logbooks.room_id', 'current_status as room_status')
            ->get()
            ->keyBy('room_id');

        foreach ($datas as $room) {
            $room->room_status = $roomStatuses->has($room->id) ? $roomStatuses->get($room->id)->room_status : 'ready';
        }
        
        // Fetch all assigned equipments
        $equipments = DB::table('equipment_in_room')
            ->join('instrument', 'equipment_in_room.equipment_id', '=', 'instrument.id')
            ->select('equipment_in_room.room_id', 'instrument.code', 'instrument.name')
            ->get()
            ->groupBy('room_id');

        // Fetch all room conditions
        $conditions = DB::table('room_manufactured_condition')
            ->orderBy('id', 'asc')
            ->get()
            ->groupBy('room_id');

        // Fetch all related forms
        $relatedForms = DB::table('Realated_Form_of_room')
            ->join('ebmr_templates', 'Realated_Form_of_room.ebmr_templace_id', '=', 'ebmr_templates.id')
            ->leftJoin('gf_category', 'ebmr_templates.caterogy_id', '=', 'gf_category.id')
            ->select('Realated_Form_of_room.room_id', 'Realated_Form_of_room.type', 'ebmr_templates.doc_code', 'gf_category.name as category_name')
            ->get()
            ->groupBy('room_id');

        // Map equipments, conditions and forms to rooms
        foreach ($datas as $room) {
            $room->equipments = $equipments->get($room->id, collect());
            $room->conditions = $conditions->get($room->id, collect());
            
            $forms = $relatedForms->get($room->id, collect());
            $room->line_clearance_form = $forms->firstWhere('type', 'line_clearance');
            $room->cleaning_form = $forms->firstWhere('type', 'cleaning');
        }

        // Fetch all workshops (the 5 production workshops)
        $workshops = DB::table('deparments')
            ->whereIn('shortName', ['PXV1', 'PXV2', 'PXVH', 'PXTN', 'PXDN'])
            ->get();

        session()->put(['title' => 'MÔI TRƯỜNG SẢN XUẤT - PHÒNG SẢN XUẤT']);
        return view('pages.manu_env.room.list', [
            'datas' => $datas,
            'stages' => self::$stages,
            'selectedDept' => $selectedDept,
            'workshops' => $workshops
        ]);
    }


    public function getEquipments(Request $request)
    {
        $roomId = $request->query('room_id');
        $assigned = DB::table('equipment_in_room')
            ->join('instrument', 'equipment_in_room.equipment_id', '=', 'instrument.id')
            ->where('equipment_in_room.room_id', $roomId)
            ->select('instrument.id', 'instrument.code', 'instrument.name', 'instrument.operation_SOP_code', 'instrument.clearing_SOP_code')
            ->get();

        $pmsDb = config('database.connections.pms.database', 'pms');

        // Get the department_code of this room
        $room = DB::connection('pms')->table('room')->where('id', $roomId)->first();
        $targetDept = $room ? $room->deparment_code : null;

        // Only load instruments that are not assigned to other workshops
        $allFixed = DB::table('instrument')
            ->where('is_Portable_equipment', 0)
            ->where('department_code', $targetDept)
            ->whereNotExists(function($query) use ($targetDept, $pmsDb) {
                $query->select(DB::raw(1))
                    ->from('equipment_in_room')
                    ->join("{$pmsDb}.room as room", 'equipment_in_room.room_id', '=', 'room.id')
                    ->whereColumn('equipment_in_room.equipment_id', 'instrument.id')
                    ->where('room.deparment_code', '<>', $targetDept);
            })
            ->select('id', 'code', 'name')
            ->get();

        return response()->json([
            'assigned' => $assigned,
            'allFixed' => $allFixed
        ]);
    }

    public function assignEquipment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'room_id' => 'required|exists:pms.room,id',
            'equipment_ids' => 'required|array',
            'equipment_ids.*' => 'exists:instrument,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()]);
        }

        $pmsDb = config('database.connections.pms.database', 'pms');

        // Get the department_code of the target room
        $targetRoom = DB::connection('pms')->table('room')->where('id', $request->room_id)->first();
        $targetDept = $targetRoom ? $targetRoom->deparment_code : null;

        $insertedCount = 0;
        $errors = [];

        foreach ($request->equipment_ids as $eq_id) {
            // Check if already assigned
            $exists = DB::table('equipment_in_room')
                ->where('room_id', $request->room_id)
                ->where('equipment_id', $eq_id)
                ->exists();

            if ($exists) {
                continue;
            }

            // Check if this equipment is already assigned to any room in a different department
            $otherDeptAssignment = DB::table('equipment_in_room')
                ->join("{$pmsDb}.room as room", 'equipment_in_room.room_id', '=', 'room.id')
                ->where('equipment_in_room.equipment_id', $eq_id)
                ->where('room.deparment_code', '<>', $targetDept)
                ->select('room.code as room_code', 'room.name as room_name', 'room.deparment_code')
                ->first();

            if ($otherDeptAssignment) {
                $errors[] = "Thiết bị ID $eq_id đã khai báo tại phòng {$otherDeptAssignment->room_code} (phân xưởng {$otherDeptAssignment->deparment_code}).";
                continue;
            }

            DB::table('equipment_in_room')->insert([
                'room_id' => $request->room_id,
                'equipment_id' => $eq_id,
                'created_by' => session('user')['fullName'] ?? 'Admin',
                'created_at' => now(),
            ]);
            $insertedCount++;
        }

        if (count($errors) > 0 && $insertedCount == 0) {
            return response()->json(['success' => false, 'message' => implode('<br>', $errors)]);
        } elseif (count($errors) > 0 && $insertedCount > 0) {
            return response()->json(['success' => true, 'message' => "Khai báo $insertedCount thiết bị thành công.<br>Lỗi: " . implode('<br>', $errors)]);
        }

        return response()->json(['success' => true, 'message' => "Khai báo $insertedCount thiết bị vào phòng thành công!"]);
    }

    public function removeEquipment(Request $request)
    {
        $deleted = DB::table('equipment_in_room')
            ->where('room_id', $request->room_id)
            ->where('equipment_id', $request->equipment_id)
            ->delete();

        if ($deleted === 0) {
            return response()->json([
                'success' => false, 
                'message' => "Lỗi: Không tìm thấy liên kết giữa thiết bị ($request->equipment_id) và phòng ($request->room_id). Có thể đã bị xóa trước đó."
            ]);
        }

        return response()->json(['success' => true, 'message' => 'Đã gỡ thiết bị khỏi phòng thành công!']);
    }

    public function getConditions(Request $request)
    {
        $roomId = $request->query('room_id');
        $conditions = DB::table('room_manufactured_condition')
            ->where('room_id', $roomId)
            ->orderBy('id', 'asc')
            ->get();

        return response()->json([
            'conditions' => $conditions
        ]);
    }

    public function storeCondition(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'room_id' => 'required|exists:room,id',
            'name' => 'required|string|max:255',
            'temp_val1_1' => 'nullable|numeric',
            'temp_val2_1' => 'nullable|numeric',
            'temp_val1_2' => 'nullable|numeric',
            'temp_val2_2' => 'nullable|numeric',
            'humidity_val1_1' => 'nullable|numeric',
            'humidity_val2_1' => 'nullable|numeric',
            'humidity_val1_2' => 'nullable|numeric',
            'humidity_val2_2' => 'nullable|numeric',
            'diff_press_corridor_val1' => 'nullable|numeric',
            'diff_press_corridor_val2' => 'nullable|numeric',
            'diff_press_pal_val1' => 'nullable|numeric',
            'diff_press_pal_val2' => 'nullable|numeric',
            'diff_press_mal_val1' => 'nullable|numeric',
            'diff_press_mal_val2' => 'nullable|numeric',
            'hepa_filter_val1' => 'nullable|numeric',
            'hepa_filter_val2' => 'nullable|numeric',
            'note' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()]);
        }

        // Calculate limits dynamically
        list($temp_min_1, $temp_max_1) = $this->calculateBounds($request->temp_op_1, $request->temp_val1_1, $request->temp_val2_1);
        list($temp_min_2, $temp_max_2) = $this->calculateBounds($request->temp_op_2, $request->temp_val1_2, $request->temp_val2_2);
        
        list($humidity_min_1, $humidity_max_1) = $this->calculateBounds($request->humidity_op_1, $request->humidity_val1_1, $request->humidity_val2_1);
        list($humidity_min_2, $humidity_max_2) = $this->calculateBounds($request->humidity_op_2, $request->humidity_val1_2, $request->humidity_val2_2);
        
        list($diff_press_corridor_min, $diff_press_corridor_max) = $this->calculateBounds($request->diff_press_corridor_op, $request->diff_press_corridor_val1, $request->diff_press_corridor_val2);
        list($diff_press_pal_min, $diff_press_pal_max) = $this->calculateBounds($request->diff_press_pal_op, $request->diff_press_pal_val1, $request->diff_press_pal_val2);
        list($diff_press_mal_min, $diff_press_mal_max) = $this->calculateBounds($request->diff_press_mal_op, $request->diff_press_mal_val1, $request->diff_press_mal_val2);
        
        list($hepa_filter_min, $hepa_filter_max) = $this->calculateBounds($request->hepa_filter_op, $request->hepa_filter_val1, $request->hepa_filter_val2);

        DB::table('room_manufactured_condition')->insert([
            'room_id' => $request->room_id,
            'name' => $request->name,
            
            'temp_op_1' => $request->temp_op_1,
            'temp_val1_1' => $request->temp_val1_1,
            'temp_val2_1' => $request->temp_val2_1,
            'temp_min_1' => $temp_min_1,
            'temp_max_1' => $temp_max_1,
            
            'temp_op_2' => $request->temp_op_2,
            'temp_val1_2' => $request->temp_val1_2,
            'temp_val2_2' => $request->temp_val2_2,
            'temp_min_2' => $temp_min_2,
            'temp_max_2' => $temp_max_2,
            
            'humidity_op_1' => $request->humidity_op_1,
            'humidity_val1_1' => $request->humidity_val1_1,
            'humidity_val2_1' => $request->humidity_val2_1,
            'humidity_min_1' => $humidity_min_1,
            'humidity_max_1' => $humidity_max_1,
            
            'humidity_op_2' => $request->humidity_op_2,
            'humidity_val1_2' => $request->humidity_val1_2,
            'humidity_val2_2' => $request->humidity_val2_2,
            'humidity_min_2' => $humidity_min_2,
            'humidity_max_2' => $humidity_max_2,
            
            'diff_press_corridor_op' => $request->diff_press_corridor_op,
            'diff_press_corridor_val1' => $request->diff_press_corridor_val1,
            'diff_press_corridor_val2' => $request->diff_press_corridor_val2,
            'diff_press_corridor_min' => $diff_press_corridor_min,
            'diff_press_corridor_max' => $diff_press_corridor_max,
            
            'diff_press_pal_op' => $request->diff_press_pal_op,
            'diff_press_pal_val1' => $request->diff_press_pal_val1,
            'diff_press_pal_val2' => $request->diff_press_pal_val2,
            'diff_press_pal_min' => $diff_press_pal_min,
            'diff_press_pal_max' => $diff_press_pal_max,
            
            'diff_press_mal_op' => $request->diff_press_mal_op,
            'diff_press_mal_val1' => $request->diff_press_mal_val1,
            'diff_press_mal_val2' => $request->diff_press_mal_val2,
            'diff_press_mal_min' => $diff_press_mal_min,
            'diff_press_mal_max' => $diff_press_mal_max,
            
            'hepa_filter_op' => $request->hepa_filter_op,
            'hepa_filter_val1' => $request->hepa_filter_val1,
            'hepa_filter_val2' => $request->hepa_filter_val2,
            'hepa_filter_min' => $hepa_filter_min,
            'hepa_filter_max' => $hepa_filter_max,
            
            'note' => $request->note,
            'created_by' => session('user')['fullName'] ?? 'Admin',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['success' => true, 'message' => 'Thêm bộ điều kiện sản xuất thành công!']);
    }

    public function deleteCondition(Request $request)
    {
        DB::table('room_manufactured_condition')
            ->where('id', $request->id)
            ->where('room_id', $request->room_id)
            ->delete();

        return response()->json(['success' => true, 'message' => 'Đã xóa bộ điều kiện sản xuất thành công!']);
    }

    public function updateCondition(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:room_manufactured_condition,id',
            'room_id' => 'required|exists:room,id',
            'name' => 'required|string|max:255',
            'temp_val1_1' => 'nullable|numeric',
            'temp_val2_1' => 'nullable|numeric',
            'temp_val1_2' => 'nullable|numeric',
            'temp_val2_2' => 'nullable|numeric',
            'humidity_val1_1' => 'nullable|numeric',
            'humidity_val2_1' => 'nullable|numeric',
            'humidity_val1_2' => 'nullable|numeric',
            'humidity_val2_2' => 'nullable|numeric',
            'diff_press_corridor_val1' => 'nullable|numeric',
            'diff_press_corridor_val2' => 'nullable|numeric',
            'diff_press_pal_val1' => 'nullable|numeric',
            'diff_press_pal_val2' => 'nullable|numeric',
            'diff_press_mal_val1' => 'nullable|numeric',
            'diff_press_mal_val2' => 'nullable|numeric',
            'hepa_filter_val1' => 'nullable|numeric',
            'hepa_filter_val2' => 'nullable|numeric',
            'note' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()]);
        }

        // Calculate limits dynamically
        list($temp_min_1, $temp_max_1) = $this->calculateBounds($request->temp_op_1, $request->temp_val1_1, $request->temp_val2_1);
        list($temp_min_2, $temp_max_2) = $this->calculateBounds($request->temp_op_2, $request->temp_val1_2, $request->temp_val2_2);
        
        list($humidity_min_1, $humidity_max_1) = $this->calculateBounds($request->humidity_op_1, $request->humidity_val1_1, $request->humidity_val2_1);
        list($humidity_min_2, $humidity_max_2) = $this->calculateBounds($request->humidity_op_2, $request->humidity_val1_2, $request->humidity_val2_2);
        
        list($diff_press_corridor_min, $diff_press_corridor_max) = $this->calculateBounds($request->diff_press_corridor_op, $request->diff_press_corridor_val1, $request->diff_press_corridor_val2);
        list($diff_press_pal_min, $diff_press_pal_max) = $this->calculateBounds($request->diff_press_pal_op, $request->diff_press_pal_val1, $request->diff_press_pal_val2);
        list($diff_press_mal_min, $diff_press_mal_max) = $this->calculateBounds($request->diff_press_mal_op, $request->diff_press_mal_val1, $request->diff_press_mal_val2);
        
        list($hepa_filter_min, $hepa_filter_max) = $this->calculateBounds($request->hepa_filter_op, $request->hepa_filter_val1, $request->hepa_filter_val2);

        DB::table('room_manufactured_condition')
            ->where('id', $request->id)
            ->where('room_id', $request->room_id)
            ->update([
                'name' => $request->name,
                
                'temp_op_1' => $request->temp_op_1,
                'temp_val1_1' => $request->temp_val1_1,
                'temp_val2_1' => $request->temp_val2_1,
                'temp_min_1' => $temp_min_1,
                'temp_max_1' => $temp_max_1,
                
                'temp_op_2' => $request->temp_op_2,
                'temp_val1_2' => $request->temp_val1_2,
                'temp_val2_2' => $request->temp_val2_2,
                'temp_min_2' => $temp_min_2,
                'temp_max_2' => $temp_max_2,
                
                'humidity_op_1' => $request->humidity_op_1,
                'humidity_val1_1' => $request->humidity_val1_1,
                'humidity_val2_1' => $request->humidity_val2_1,
                'humidity_min_1' => $humidity_min_1,
                'humidity_max_1' => $humidity_max_1,
                
                'humidity_op_2' => $request->humidity_op_2,
                'humidity_val1_2' => $request->humidity_val1_2,
                'humidity_val2_2' => $request->humidity_val2_2,
                'humidity_min_2' => $humidity_min_2,
                'humidity_max_2' => $humidity_max_2,
                
                'diff_press_corridor_op' => $request->diff_press_corridor_op,
                'diff_press_corridor_val1' => $request->diff_press_corridor_val1,
                'diff_press_corridor_val2' => $request->diff_press_corridor_val2,
                'diff_press_corridor_min' => $diff_press_corridor_min,
                'diff_press_corridor_max' => $diff_press_corridor_max,
                
                'diff_press_pal_op' => $request->diff_press_pal_op,
                'diff_press_pal_val1' => $request->diff_press_pal_val1,
                'diff_press_pal_val2' => $request->diff_press_pal_val2,
                'diff_press_pal_min' => $diff_press_pal_min,
                'diff_press_pal_max' => $diff_press_pal_max,
                
                'diff_press_mal_op' => $request->diff_press_mal_op,
                'diff_press_mal_val1' => $request->diff_press_mal_val1,
                'diff_press_mal_val2' => $request->diff_press_mal_val2,
                'diff_press_mal_min' => $diff_press_mal_min,
                'diff_press_mal_max' => $diff_press_mal_max,
                
                'hepa_filter_op' => $request->hepa_filter_op,
                'hepa_filter_val1' => $request->hepa_filter_val1,
                'hepa_filter_val2' => $request->hepa_filter_val2,
                'hepa_filter_min' => $hepa_filter_min,
                'hepa_filter_max' => $hepa_filter_max,
                
                'note' => $request->note,
                'updated_at' => now(),
            ]);

        return response()->json(['success' => true, 'message' => 'Cập nhật bộ điều kiện sản xuất thành công!']);
    }

    private function calculateBounds($op, $val1, $val2)
    {
        $min = null;
        $max = null;

        if (is_null($val1) || $val1 === '') {
            return [$min, $max];
        }

        $val1 = (float)$val1;
        $val2 = (!is_null($val2) && $val2 !== '') ? (float)$val2 : null;

        switch ($op) {
            case '<=':
            case '≤':
                $max = $val1;
                break;
            case '>=':
            case '≥':
                $min = $val1;
                break;
            case '=':
                $min = $val1;
                $max = $val1;
                break;
            case '±':
                if (!is_null($val2)) {
                    $min = $val1 - $val2;
                    $max = $val1 + $val2;
                } else {
                    $min = $val1;
                    $max = $val1;
                }
                break;
            case 'between':
            case 'khoảng':
                if (!is_null($val2)) {
                    $min = min($val1, $val2);
                    $max = max($val1, $val2);
                } else {
                    $min = $val1;
                }
                break;
        }

        return [$min, $max];
    }

    public function getRelatedForms(Request $request)
    {
        $roomId = $request->query('room_id');

        // Fetch all GF templates
        $gfTemplates = DB::table('ebmr_templates')
            ->leftJoin('gf_category', 'ebmr_templates.caterogy_id', '=', 'gf_category.id')
            ->where('ebmr_templates.type', 'GF')
            ->select('ebmr_templates.id', 'ebmr_templates.doc_code', 'gf_category.name as category_name')
            ->get();

        // Fetch current relations
        $currentRelations = DB::table('Realated_Form_of_room')
            ->where('room_id', $roomId)
            ->get()
            ->pluck('ebmr_templace_id', 'type');

        return response()->json([
            'templates' => $gfTemplates,
            'current' => $currentRelations
        ]);
    }

    public function saveRelatedForms(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'room_id' => 'required|exists:room,id',
            'line_clearance_template_id' => 'nullable|exists:ebmr_templates,id',
            'cleaning_template_id' => 'nullable|exists:ebmr_templates,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()]);
        }

        $roomId = $request->room_id;
        $user = session('user')['fullName'] ?? 'Admin';

        DB::transaction(function () use ($roomId, $request, $user) {
            // Handle Line Clearance
            if ($request->line_clearance_template_id) {
                DB::table('Realated_Form_of_room')->updateOrInsert(
                    ['room_id' => $roomId, 'type' => 'line_clearance'],
                    [
                        'ebmr_templace_id' => $request->line_clearance_template_id,
                        'created_by' => $user,
                        'updated_at' => now(),
                        'created_at' => DB::raw('COALESCE(created_at, NOW())')
                    ]
                );
            } else {
                DB::table('Realated_Form_of_room')
                    ->where('room_id', $roomId)
                    ->where('type', 'line_clearance')
                    ->delete();
            }

            // Handle Cleaning
            if ($request->cleaning_template_id) {
                DB::table('Realated_Form_of_room')->updateOrInsert(
                    ['room_id' => $roomId, 'type' => 'cleaning'],
                    [
                        'ebmr_templace_id' => $request->cleaning_template_id,
                        'created_by' => $user,
                        'updated_at' => now(),
                        'created_at' => DB::raw('COALESCE(created_at, NOW())')
                    ]
                );
            } else {
                DB::table('Realated_Form_of_room')
                    ->where('room_id', $roomId)
                    ->where('type', 'cleaning')
                    ->delete();
            }
        });

        return response()->json(['success' => true, 'message' => 'Liên kết biểu mẫu thành công!']);
    }
}
