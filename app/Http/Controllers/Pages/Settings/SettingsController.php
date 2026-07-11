<?php

namespace App\Http\Controllers\Pages\Settings;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\Request;

/**
 * Chính Sách Chung: trang cấu hình các thông số hệ thống dùng chung, độc lập với dữ
 * liệu nghiệp vụ (quy trình, phòng, thiết bị...). Hiện chỉ có 1 tham số (tần suất ghi
 * nhận môi trường sản xuất) — thiết kế dạng key-value (bảng system_settings) để mở rộng
 * thêm tham số khác sau này mà không cần thêm cột/migration mới.
 */
class SettingsController extends Controller
{
    /**
     * Danh sách các key setting được phép chỉnh ở trang này, kèm nhãn/mô tả/mặc định —
     * khai báo tập trung để form + validate + hiển thị đều dựa vào đúng 1 nguồn.
     */
    private const DEFINITIONS = [
        'env_recording_frequency_minutes' => [
            'label' => 'Tần suất ghi nhận môi trường sản xuất',
            'description' => 'Khoảng cách thời gian (phút) giữa 2 lần ghi nhận Nhiệt độ/Độ ẩm/Chênh áp cho mỗi phòng đang sản xuất, từ lúc "Bắt đầu sản xuất" đến "Kết thúc sản xuất".',
            'type' => 'select',
            'options' => [5 => '5 phút', 10 => '10 phút', 15 => '15 phút', 30 => '30 phút', 60 => '60 phút'],
            'default' => 15,
        ],
    ];

    public function index()
    {
        session(['title' => 'Chính Sách Chung']);

        $settings = [];
        foreach (self::DEFINITIONS as $key => $def) {
            $settings[$key] = [
                ...$def,
                'value' => SystemSetting::get($key, $def['default']),
            ];
        }

        return view('pages.settings.general_policy', compact('settings'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'env_recording_frequency_minutes' => 'required|integer|in:5,10,15,30,60',
        ]);

        $userId = session('user')['userId'] ?? null;
        foreach ($validated as $key => $value) {
            if (!array_key_exists($key, self::DEFINITIONS)) continue;
            SystemSetting::set($key, (string) $value, $userId);
        }

        return redirect()->route('pages.settings.general_policy')->with('success', 'Đã lưu chính sách chung.');
    }
}
