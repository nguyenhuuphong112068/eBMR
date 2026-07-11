<?php

namespace App\Services;

use App\Http\Controllers\General\NotificationController;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Gửi thông báo trong app cho luồng phê duyệt tài liệu (BMR/BPR/GF/MF, vệ sinh
 * phòng/thiết bị, dọn quang phòng/thiết bị). $workflowType dùng đúng 4 giá trị
 * đã có trong EbmrApprovalController::process(): 'ebmr', 'cleaning',
 * 'clearance_room', 'clearance_equip'. $subType chỉ dùng cho 'cleaning'
 * ('room'/'equipment') vì bảng cleaning_process_workflows dùng chung cho cả hai.
 */
class ApprovalWorkflowNotifier
{
    private static array $tableMap = [
        'ebmr' => 'ebmr_template_workflows',
        'cleaning' => 'cleaning_process_workflows',
        'clearance_room' => 'clearance_room_process_workflows',
        'clearance_equip' => 'clearance_equip_process_workflows',
    ];

    private static array $roleLabels = [
        'reviewer' => 'kiểm tra',
        'approver' => 'phê duyệt',
        'authorizer' => 'ban hành',
    ];

    private static function scopeQuery(string $workflowType, int $documentId, ?string $subType = null)
    {
        $table = self::$tableMap[$workflowType];
        $query = DB::table($table);

        if ($workflowType === 'ebmr') {
            $query->where('template_id', $documentId);
        } else {
            $query->where('process_list_id', $documentId);
        }

        if ($workflowType === 'cleaning' && $subType) {
            $query->where('type', $subType);
        }

        return $query;
    }

    /**
     * Tên/mã hồ sơ + link xem, để đưa vào nội dung thông báo.
     */
    public static function resolveDocument(string $workflowType, int $documentId, ?string $subType = null): array
    {
        if ($workflowType === 'ebmr') {
            $t = DB::table('ebmr_templates')->where('id', $documentId)->first();
            if (!$t) return ['label' => "Hồ sơ #{$documentId}", 'url' => null];

            $type = $t->type ?? 'BMR';
            if ($type === 'GF') {
                $cat = DB::table('gf_category')->where('id', $t->caterogy_id)->first();
                $code = $cat->code ?? '';
                $name = $cat->name ?? '';
            } elseif ($type === 'MF') {
                $cat = DB::table('mf_category')->where('id', $t->caterogy_id)->first();
                $code = $cat->code ?? '';
                $name = $cat->name ?? '';
            } elseif ($type === 'BPR') {
                $cat = DB::table('finished_product_category')
                    ->leftJoin('product_name', 'finished_product_category.product_name_id', '=', 'product_name.id')
                    ->where('finished_product_category.id', $t->caterogy_id)
                    ->select('finished_product_category.*', 'product_name.name as product_name')
                    ->first();
                $code = $cat->finished_product_code ?? '';
                $name = $cat->product_name ?? '';
            } else {
                $cat = DB::table('intermediate_category')
                    ->leftJoin('product_name', 'intermediate_category.product_name_id', '=', 'product_name.id')
                    ->where('intermediate_category.id', $t->caterogy_id)
                    ->select('intermediate_category.*', 'product_name.name as product_name')
                    ->first();
                $code = $cat->intermediate_code ?? '';
                $name = $cat->product_name ?? '';
            }

            return [
                'label' => trim("{$type} {$code} - {$name}"),
                'url' => route('pages.ebmr.designer', $t->id),
            ];
        }

        if ($workflowType === 'cleaning') {
            $table = $subType === 'equipment' ? 'cleaning_equip_processes_list' : 'cleaning_room_processes_list';
            $list = DB::table($table)->where('id', $documentId)->first();
            if (!$list) return ['label' => "Quy trình #{$documentId}", 'url' => null];

            return [
                'label' => "{$list->process_code} - {$list->process_name}",
                'url' => route('pages.manu_env.cleaning_process.index', ['type' => $subType, 'list_id' => $documentId]),
            ];
        }

        // clearance_room / clearance_equip
        $type = $workflowType === 'clearance_equip' ? 'equipment' : 'room';
        $table = $workflowType === 'clearance_equip' ? 'clearance_equip_processes_list' : 'clearance_room_processes_list';
        $list = DB::table($table)->where('id', $documentId)->first();
        if (!$list) return ['label' => "Quy trình #{$documentId}", 'url' => null];

        return [
            'label' => "{$list->process_code} - {$list->process_name}",
            'url' => route('pages.manu_env.clearance_process.index', ['type' => $type, 'list_id' => $documentId]),
        ];
    }

    /**
     * Gửi thông báo cho tất cả người dùng ở bước "actionable" hiện tại (step_order
     * nhỏ nhất đang pending, không còn bước trước còn pending) của 1 hồ sơ.
     * Dùng cho cả lúc "Gửi duyệt" (bước 1 sẽ là actionable) lẫn sau khi 1 bước
     * được duyệt (bước kế tiếp trở thành actionable). Không có bước actionable
     * nào (đã duyệt xong/đã hủy hết) thì không gửi gì.
     */
    public static function notifyActionableStep(string $workflowType, int $documentId, ?string $subType = null): void
    {
        $minStep = (clone self::scopeQuery($workflowType, $documentId, $subType))
            ->where('status', 'pending')
            ->min('step_order');

        if ($minStep === null) return;

        $rows = self::scopeQuery($workflowType, $documentId, $subType)
            ->where('status', 'pending')
            ->where('step_order', $minStep)
            ->get();

        if ($rows->isEmpty()) return;

        $doc = self::resolveDocument($workflowType, $documentId, $subType);
        foreach ($rows as $row) {
            $roleLabel = self::$roleLabels[$row->role] ?? $row->role;
            NotificationController::sendNotification(
                "Bạn có hồ sơ \"{$doc['label']}\" đang chờ bạn {$roleLabel}.",
                'Trình ký',
                $row->id,
                [$row->user_id],
                [],
                $doc['url']
            );
        }
    }

    /**
     * Gửi thông báo cho đúng 1 người (dùng khi đổi người phụ trách 1 bước).
     * Chỉ gửi nếu bước này đang actionable (không còn bước trước còn pending) —
     * nếu chưa tới lượt thì người mới sẽ được nhắc khi notifyActionableStep()
     * chạy tới bước của họ.
     */
    public static function notifyUser(string $workflowType, int $documentId, int $userId, ?string $subType = null): void
    {
        $row = self::scopeQuery($workflowType, $documentId, $subType)
            ->where('user_id', $userId)
            ->where('status', 'pending')
            ->first();

        if (!$row) return;

        $hasEarlierPending = self::scopeQuery($workflowType, $documentId, $subType)
            ->where('status', 'pending')
            ->where('step_order', '<', $row->step_order)
            ->exists();

        if ($hasEarlierPending) return;

        $doc = self::resolveDocument($workflowType, $documentId, $subType);
        $roleLabel = self::$roleLabels[$row->role] ?? $row->role;
        NotificationController::sendNotification(
            "Bạn được giao {$roleLabel} hồ sơ \"{$doc['label']}\".",
            'Trình ký',
            $row->id,
            [$userId],
            [],
            $doc['url']
        );
    }

    /**
     * Báo cho chủ sở hữu hồ sơ khi hồ sơ bị từ chối trả về. $rejectedWorkflow là
     * dòng workflow vừa bị đánh rejected (cần id, user_id, role). Không gửi nếu
     * chính chủ sở hữu là người từ chối.
     */
    public static function notifyOwnerRejected(string $workflowType, int $documentId, object $rejectedWorkflow, ?string $comment = null, ?string $subType = null): void
    {
        if ($workflowType === 'ebmr') {
            $ownerId = DB::table('ebmr_templates')->where('id', $documentId)->value('owner_id');
        } elseif ($workflowType === 'cleaning') {
            $table = $subType === 'equipment' ? 'cleaning_equip_processes_list' : 'cleaning_room_processes_list';
            $ownerId = DB::table($table)->where('id', $documentId)->value('created_by');
        } else {
            $table = $workflowType === 'clearance_equip' ? 'clearance_equip_processes_list' : 'clearance_room_processes_list';
            $ownerId = DB::table($table)->where('id', $documentId)->value('created_by');
        }

        if (!$ownerId || (int) $ownerId === (int) $rejectedWorkflow->user_id) return;

        $doc = self::resolveDocument($workflowType, $documentId, $subType);
        $rejectorName = DB::table('user_management')->where('id', $rejectedWorkflow->user_id)->value('fullName') ?? 'Người duyệt';
        $roleLabel = self::$roleLabels[$rejectedWorkflow->role] ?? $rejectedWorkflow->role;

        $msg = "Hồ sơ \"{$doc['label']}\" của bạn đã bị {$rejectorName} (bước {$roleLabel}) từ chối"
            . ($comment ? " với lý do: {$comment}" : '')
            . ". Hồ sơ được trả về trạng thái soạn thảo.";

        NotificationController::sendNotification(
            $msg,
            'Từ chối phê duyệt',
            $rejectedWorkflow->id,
            [(int) $ownerId],
            [],
            $doc['url']
        );
    }

    /**
     * Quét cả 4 bảng workflow, nhắc những bước đang actionable có due_date rơi
     * vào ngày mai và chưa nhắc lần nào. Trả về số lượng nhắc nhở đã gửi.
     */
    public static function sendDueReminders(): int
    {
        $tomorrow = Carbon::tomorrow()->toDateString();
        $sent = 0;

        foreach (self::$tableMap as $workflowType => $table) {
            $due = DB::table($table)
                ->where('status', 'pending')
                ->whereDate('due_date', $tomorrow)
                ->whereNull('reminder_sent_at')
                ->get();

            foreach ($due as $row) {
                $documentId = $workflowType === 'ebmr' ? $row->template_id : $row->process_list_id;
                $subType = $workflowType === 'cleaning' ? $row->type : null;

                $hasEarlierPending = self::scopeQuery($workflowType, $documentId, $subType)
                    ->where('status', 'pending')
                    ->where('step_order', '<', $row->step_order)
                    ->exists();

                if ($hasEarlierPending) continue;

                $doc = self::resolveDocument($workflowType, $documentId, $subType);
                $roleLabel = self::$roleLabels[$row->role] ?? $row->role;
                NotificationController::sendNotification(
                    "Nhắc hạn: hồ sơ \"{$doc['label']}\" cần bạn {$roleLabel} trước ngày " . Carbon::parse($row->due_date)->format('d/m/Y') . ".",
                    'Nhắc hạn phê duyệt',
                    $row->id,
                    [$row->user_id],
                    [],
                    $doc['url']
                );

                DB::table($table)->where('id', $row->id)->update(['reminder_sent_at' => now()]);
                $sent++;
            }
        }

        return $sent;
    }
}
