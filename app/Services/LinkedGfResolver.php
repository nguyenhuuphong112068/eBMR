<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class LinkedGfResolver
{
    /**
     * Trả về template_id của GF (biểu mẫu dùng chung) đang áp dụng cho 1 block
     * type='linked-template'. Nếu $recordId có và đã được chốt (pin) trước đó
     * (ebmr_record_linked_templates), luôn trả về đúng bản đã chốt cho lô đó —
     * bất kể GF đã lên ấn bản active mới hơn. Chưa chốt (hoặc không có $recordId,
     * ví dụ đang ở chế độ thiết kế/xem trước) thì luôn lấy bản active mới nhất.
     */
    public static function resolveTemplateId(array $props, ?int $recordId, int $hostBlockId): ?int
    {
        $docCode = $props['ref_doc_code'] ?? null;
        if (!$docCode) {
            return null;
        }

        if ($recordId) {
            $pinned = DB::table('ebmr_record_linked_templates')
                ->where('record_id', $recordId)
                ->where('host_block_id', $hostBlockId)
                ->value('gf_template_id');
            if ($pinned) {
                return (int) $pinned;
            }
        }

        return DB::table('ebmr_templates')
            ->where('doc_code', $docCode)
            ->where('type', 'GF')
            ->where('status', 'active')
            ->orderByDesc('version')
            ->value('id');
    }

    /**
     * Namespace id của field thuộc GF được liên kết theo host-block, để cùng 1 GF
     * chèn lặp lại ở nhiều vị trí trong 1 BMR không bị đụng block_uuid khi lưu
     * ebmr_run_data (unique record_id+block_uuid+cell_id).
     */
    public static function namespaceLinkedField(array $field, int $hostBlockId): array
    {
        if (isset($field['id'])) {
            $field['id'] = $hostBlockId . '__gf' . $field['id'];
        }
        return $field;
    }

    /**
     * Namespace toàn bộ key + field['id'] của 1 fieldsConfig map (field_key => config)
     * thuộc GF được liên kết, đồng bộ quy tắc với namespaceLinkedField().
     */
    public static function namespaceLinkedFieldsConfig(array $config, int $hostBlockId): array
    {
        $result = [];
        foreach ($config as $key => $field) {
            $newKey = $hostBlockId . '__gf' . $key;
            $result[$newKey] = is_array($field) ? self::namespaceLinkedField($field, $hostBlockId) : $field;
        }
        return $result;
    }

    /**
     * Chốt (pin) GF-version active hiện tại cho 1 vị trí liên kết của 1 lô, nếu
     * chưa được chốt trước đó. An toàn khi có nhiều request ghi đồng thời nhờ
     * insertOrIgnore + unique(record_id, host_block_id).
     */
    public static function pinIfMissing(int $recordId, int $hostBlockId, ?int $userId = null): void
    {
        $exists = DB::table('ebmr_record_linked_templates')
            ->where('record_id', $recordId)
            ->where('host_block_id', $hostBlockId)
            ->exists();
        if ($exists) {
            return;
        }

        $hostBlock = DB::table('ebmr_template_blocks')->where('id', $hostBlockId)->first();
        if (!$hostBlock) {
            return;
        }
        $props = json_decode($hostBlock->properties, true);
        $docCode = $props['ref_doc_code'] ?? null;
        if (!$docCode) {
            return;
        }

        $activeGf = DB::table('ebmr_templates')
            ->where('doc_code', $docCode)
            ->where('type', 'GF')
            ->where('status', 'active')
            ->orderByDesc('version')
            ->first();
        if (!$activeGf) {
            return;
        }

        $now = now();
        DB::table('ebmr_record_linked_templates')->insertOrIgnore([
            'record_id' => $recordId,
            'host_block_id' => $hostBlockId,
            'gf_template_id' => $activeGf->id,
            'gf_doc_code' => $docCode,
            'gf_version' => $activeGf->version,
            'pinned_at' => $now,
            'pinned_by' => $userId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
