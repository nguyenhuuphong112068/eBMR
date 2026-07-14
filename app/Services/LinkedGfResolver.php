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

        // Badge biến số được nhúng THẲNG vào HTML nội dung (injectContent chạy TRƯỚC hàm này)
        // vẫn mang data-field-id/onclick với field-key GỐC "field_...". Phải namespace luôn các
        // chuỗi này cho khớp fieldsConfig đã đổi tên ({hostBlockId}__gf...), nếu không frontend
        // tra fieldsConfig[id-gốc] không ra -> bỏ qua badge, không kích hoạt ô nhập
        // (activateStaticBadgesIn: `if (!fid || !fieldsConfig[fid]) return;`).
        $prefix = $hostBlockId . '__gf';
        if (!empty($field['content']) && is_string($field['content'])) {
            $field['content'] = self::namespaceFieldIdsInHtml($field['content'], $prefix);
        }
        if (($field['type'] ?? null) === 'table' && !empty($field['data']) && is_array($field['data'])) {
            foreach ($field['data'] as &$row) {
                if (!is_array($row)) continue;
                foreach ($row as &$cell) {
                    if (is_array($cell) && !empty($cell['content']) && is_string($cell['content'])) {
                        $cell['content'] = self::namespaceFieldIdsInHtml($cell['content'], $prefix);
                    }
                }
                unset($cell);
            }
            unset($row);
        }

        return $field;
    }

    /**
     * Đổi tên mọi field-key gốc "field_..." nhúng trong 1 đoạn HTML (data-field-id + tham số
     * selectField) sang dạng đã namespace theo host-block. Bỏ qua sớm nếu đoạn HTML không chứa
     * "field_" để không tốn regex. Dùng callback để prefix (chứa số) không bị hiểu là backreference.
     */
    private static function namespaceFieldIdsInHtml(string $html, string $prefix): string
    {
        if (strpos($html, 'field_') === false) {
            return $html;
        }
        $html = preg_replace_callback('/data-field-id="(field_[a-zA-Z0-9_]+)"/', function ($m) use ($prefix) {
            return 'data-field-id="' . $prefix . $m[1] . '"';
        }, $html);
        $html = preg_replace_callback("/selectField\\(event,\\s*'(field_[a-zA-Z0-9_]+)'\\)/", function ($m) use ($prefix) {
            return "selectField(event, '" . $prefix . $m[1] . "')";
        }, $html);
        return $html;
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
