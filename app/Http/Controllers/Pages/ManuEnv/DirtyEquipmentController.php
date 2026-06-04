<?php

namespace App\Http\Controllers\Pages\ManuEnv;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\CleaningEquipCampaign;

class DirtyEquipmentController extends Controller
{
    public function index(Request $request)
    {
        session(['title' => 'Thiết Bị Cần Vệ Sinh']);
        
        // Find the latest log in room_logbooks
        $latestEqLogs = DB::table('room_logbooks')
            ->whereNotNull('equipment_id')
            ->select('equipment_id', DB::raw('MAX(id) as max_id'))
            ->groupBy('equipment_id');

        // Find the latest log in instrument_logbooks
        $latestInstLogs = DB::table('instrument_logbooks')
            ->selectRaw('instrument_id, MAX(id) as max_id')
            ->groupBy('instrument_id');

        $query = DB::table('instrument as i')
            ->leftJoinSub($latestInstLogs, 'latest_inst', function ($join) {
                $join->on('i.id', '=', 'latest_inst.instrument_id')
                     ->orOn('i.code', '=', 'latest_inst.instrument_id');
            })
            ->leftJoin('instrument_logbooks as inst_log', 'inst_log.id', '=', 'latest_inst.max_id')
            ->leftJoinSub($latestEqLogs, 'latest_room', function ($join) {
                $join->on('i.id', '=', 'latest_room.equipment_id');
            })
            ->leftJoin('room_logbooks as room_log', 'room_log.id', '=', 'latest_room.max_id')
            ->select(
                'i.id', 'i.code', 'i.name', 'i.department_code', 'i.stage_id', 'i.is_Portable_equipment',
                'inst_log.current_status as inst_status', 'inst_log.start_time as inst_time',
                'room_log.current_status as room_status', 'room_log.start_time as room_time',
                'i.created_at'
            );

        $selectedDepartment = $request->has('department') ? $request->department : 'PXV1';

        if ($selectedDepartment !== '' && $selectedDepartment !== null) {
            $query->where('i.department_code', $selectedDepartment);
        }

        $instruments = $query->get();

        $equipments = collect();

        foreach ($instruments as $eq) {
            $status = 'dirty';
            $time = $eq->created_at ?? now();

            if ($eq->inst_time && $eq->room_time) {
                if ($eq->inst_time > $eq->room_time) {
                    $status = $eq->inst_status;
                    $time = $eq->inst_time;
                } else {
                    $status = $eq->room_status;
                    $time = $eq->room_time;
                }
            } elseif ($eq->inst_time) {
                $status = $eq->inst_status;
                $time = $eq->inst_time;
            } elseif ($eq->room_time) {
                $status = $eq->room_status;
                $time = $eq->room_time;
            }

            // Nếu status khác 'cleaned' thì đưa vào danh sách cần vệ sinh
            if ($status !== 'cleaned') {
                $eq->status = ($status === 'cleaning') ? 'cleaning' : 'dirty';
                $eq->status_since = $time;
                
                if ($eq->status === 'cleaning') {
                    $activeCampaign = CleaningEquipCampaign::where('equipment_id', $eq->id)
                        ->where('status', 'in_progress')
                        ->first();
                    if (!$activeCampaign) {
                        $eq->status = 'dirty'; // Fallback nếu campaign bị lỗi/bị xoá
                    } else {
                        $eq->campaign_id = $activeCampaign->id;
                        $eq->room_campaign_id = $activeCampaign->room_campaign_id;
                    }
                }
                
                $equipments->push($eq);
            }
        }
        
        $equipments = $equipments->sortByDesc('status_since');
        
        // Lấy danh sách phân xưởng để lọc
        $departments = DB::table('instrument')
            ->select('department_code')
            ->whereNotNull('department_code')
            ->where('department_code', '!=', '')
            ->distinct()
            ->orderBy('department_code', 'asc')
            ->pluck('department_code');

        // Common rooms for the modal
        $commonRooms = DB::table('room_clearings')->where('status', 'active')->get();
        $stages = EquipmentController::$stages;

        return view('pages.manu_env.dirty_equipments.index', compact('equipments', 'commonRooms', 'departments', 'stages', 'selectedDepartment'));
    }
}
