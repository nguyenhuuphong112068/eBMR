<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Suy ra tên sản phẩm hiển thị từ (type, caterogy_id) của 1 ebmr_templates — logic
 * tra cứu khác nhau theo từng loại tài liệu (GF/MF/BPR/còn lại). Gộp lại từ chỗ trước
 * đây chỉ có ở EbmrExecutionController::resolveProductName() để ProductionEnvironmentController
 * (trang Lịch Sử Môi Trường Sản Xuất) dùng lại được.
 */
class EbmrProductResolver
{
    public static function resolveName(?string $type, $caterogyId): string
    {
        if ($type === 'GF') {
            return DB::table('gf_category')->where('id', $caterogyId)->value('name') ?? 'N/A';
        }
        if ($type === 'MF') {
            return DB::table('mf_category')->where('id', $caterogyId)->value('name') ?? 'N/A';
        }
        if ($type === 'BPR') {
            return DB::table('finished_product_category')
                ->leftJoin('product_name', 'finished_product_category.product_name_id', '=', 'product_name.id')
                ->where('finished_product_category.id', $caterogyId)
                ->value('product_name.name') ?? 'N/A';
        }
        return DB::table('intermediate_category')
            ->leftJoin('product_name', 'intermediate_category.product_name_id', '=', 'product_name.id')
            ->where('intermediate_category.id', $caterogyId)
            ->value('product_name.name') ?? 'N/A';
    }
}
