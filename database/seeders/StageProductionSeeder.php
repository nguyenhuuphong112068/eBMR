<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StageProductionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $stages_dict = [
            'can_nl' => [
                'stage_name' => 'Cân Nguyên Liệu',
                'stage_codes' => [1],
                'icon_class' => 'fa-balance-scale',
                'gradient_class' => 'gradient-blue',
                'description' => 'Phân phối nguyên liệu và kiểm tra khối lượng cho từng lô sản xuất.'
            ],
            'xu_ly_bao_bi' => [
                'stage_name' => 'Xử lý bao bì',
                'stage_codes' => [2],
                'icon_class' => 'fa-pump-medical',
                'gradient_class' => 'gradient-secondary',
                'description' => 'Xử lý, làm sạch và chuẩn bị bao bì sản xuất.'
            ],
            'pha_che' => [
                'stage_name' => 'Pha Chế',
                'stage_codes' => [3],
                'icon_class' => 'fa-flask',
                'gradient_class' => 'gradient-teal',
                'description' => 'Khai báo trộn hoàn tất, hòa tan và nhào trộn dịch thuốc.'
            ],
            'tron_hoan_tat' => [
                'stage_name' => 'Trộn Hoàn Tất',
                'stage_codes' => [4],
                'icon_class' => 'fa-blender',
                'gradient_class' => 'gradient-success',
                'description' => 'Trộn đồng nhất sản phẩm trước khi chuyển qua công đoạn tiếp theo.'
            ],
            'dinh_hinh' => [
                'stage_name' => 'Định Hình',
                'stage_codes' => [5],
                'icon_class' => 'fa-tablets',
                'gradient_class' => 'gradient-purple',
                'description' => 'Dập viên, đóng nang hoặc định hình bán thành phẩm.'
            ],
            'bao_phim' => [
                'stage_name' => 'Bao Phim',
                'stage_codes' => [6],
                'icon_class' => 'fa-circle-notch',
                'gradient_class' => 'gradient-orange',
                'description' => 'Bao màng phim bảo vệ hoặc bao đường cải thiện cảm quan.'
            ],
            'dg_so_cap' => [
                'stage_name' => 'Đóng Gói Sơ Cấp',
                'stage_codes' => [7],
                'icon_class' => 'fa-box-open',
                'gradient_class' => 'gradient-rose',
                'description' => 'Đóng vỉ, đóng chai hoặc tiếp xúc trực tiếp sản phẩm.'
            ],
            'dg_thu_cap' => [
                'stage_name' => 'Đóng Gói Thứ Cấp',
                'stage_codes' => [8],
                'icon_class' => 'fa-boxes',
                'gradient_class' => 'gradient-slate',
                'description' => 'Đóng hộp carton, dán nhãn toa và xếp pallet hoàn thiện.'
            ]
        ];

        $workshops = [
            'PXV1' => ['can_nl', 'pha_che', 'tron_hoan_tat', 'dinh_hinh', 'bao_phim', 'dg_so_cap', 'dg_thu_cap'],
            'PXV2' => ['can_nl', 'pha_che', 'tron_hoan_tat', 'dinh_hinh', 'bao_phim', 'dg_so_cap', 'dg_thu_cap'],
            'PXVH' => ['can_nl', 'pha_che', 'tron_hoan_tat', 'dinh_hinh', 'bao_phim', 'dg_so_cap', 'dg_thu_cap'],
            'PXTN' => ['can_nl', 'xu_ly_bao_bi', 'pha_che', 'dg_so_cap', 'dg_thu_cap'],
            'PXDN' => ['can_nl', 'pha_che', 'dg_so_cap', 'dg_thu_cap']
        ];

        \App\Models\StageProduction::truncate();

        foreach ($workshops as $workshop_code => $stage_keys) {
            foreach ($stage_keys as $index => $key) {
                $stage = $stages_dict[$key];
                \App\Models\StageProduction::create([
                    'workshop_code' => $workshop_code,
                    'stage_name' => $stage['stage_name'],
                    'stage_codes' => $stage['stage_codes'],
                    'icon_class' => $stage['icon_class'],
                    'gradient_class' => $stage['gradient_class'],
                    'description' => $stage['description'],
                    'order_num' => $index + 1
                ]);
            }
        }
    }
}
