<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Đếm số hồ sơ đang chờ user duyệt (actionable) — cùng logic với
 * EbmrApprovalController@index: chỉ tính khi không còn bước duyệt
 * nào trước đó (step_order nhỏ hơn) đang pending.
 */
class PendingApprovalCounter
{
    public static function countForUser($userId): int
    {
        if (!$userId) {
            return 0;
        }

        $count = 0;

        // 1. eBMR templates
        $count += DB::table('ebmr_template_workflows as w')
            ->join('ebmr_templates as t', 't.id', '=', 'w.template_id')
            ->where('w.user_id', $userId)
            ->where('w.status', 'pending')
            ->where('t.status', 'submitted')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('ebmr_template_workflows as w2')
                    ->whereColumn('w2.template_id', 'w.template_id')
                    ->where('w2.status', 'pending')
                    ->whereColumn('w2.step_order', '<', 'w.step_order');
            })
            ->count();

        // 2. Cleaning processes (room / equipment dùng 2 bảng danh sách khác nhau)
        foreach (['room' => 'cleaning_room_processes_list', 'equipment' => 'cleaning_equip_processes_list'] as $type => $listTable) {
            $typeQuery = DB::table('cleaning_process_workflows as w')
                ->join("$listTable as l", 'l.id', '=', 'w.process_list_id')
                ->where('w.user_id', $userId)
                ->where('w.status', 'pending')
                ->where('l.status', 'submitted')
                ->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))
                        ->from('cleaning_process_workflows as w2')
                        ->whereColumn('w2.process_list_id', 'w.process_list_id')
                        ->whereColumn('w2.type', 'w.type')
                        ->where('w2.status', 'pending')
                        ->whereColumn('w2.step_order', '<', 'w.step_order');
                });

            // Controller phân nhánh theo type === 'room', còn lại dùng bảng equip
            $count += $type === 'room'
                ? $typeQuery->where('w.type', 'room')->count()
                : $typeQuery->where('w.type', '<>', 'room')->count();
        }

        // 3. Clearance room processes
        $count += DB::table('clearance_room_process_workflows as w')
            ->join('clearance_room_processes_list as l', 'l.id', '=', 'w.process_list_id')
            ->where('w.user_id', $userId)
            ->where('w.status', 'pending')
            ->where('l.status', 'submitted')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('clearance_room_process_workflows as w2')
                    ->whereColumn('w2.process_list_id', 'w.process_list_id')
                    ->where('w2.status', 'pending')
                    ->whereColumn('w2.step_order', '<', 'w.step_order');
            })
            ->count();

        // 4. Clearance equip processes
        $count += DB::table('clearance_equip_process_workflows as w')
            ->join('clearance_equip_processes_list as l', 'l.id', '=', 'w.process_list_id')
            ->where('w.user_id', $userId)
            ->where('w.status', 'pending')
            ->where('l.status', 'submitted')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('clearance_equip_process_workflows as w2')
                    ->whereColumn('w2.process_list_id', 'w.process_list_id')
                    ->where('w2.status', 'pending')
                    ->whereColumn('w2.step_order', '<', 'w.step_order');
            })
            ->count();

        return $count;
    }
}
