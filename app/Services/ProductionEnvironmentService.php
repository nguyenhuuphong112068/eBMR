<?php

namespace App\Services;

/**
 * Nguồn dữ liệu môi trường sản xuất (nhiệt độ/độ ẩm/chênh áp) theo phòng. Hiện đang MÔ
 * PHỎNG bằng công thức sin/cos ổn định theo room_id + thời gian (gộp lại từ chỗ trước
 * đây bị lặp lại ở cả EbmrExecutionController::getBmsData() lẫn JS calculateMockValue()
 * trong production/index.blade.php). Khi có API BMS thật, chỉ cần sửa nội dung
 * simulateReading() ở ĐÚNG 1 nơi này — mọi nơi gọi (hiển thị real-time lẫn ghi nhận
 * lịch sử định kỳ) đều tự động dùng nguồn dữ liệu mới.
 */
class ProductionEnvironmentService
{
    /**
     * Sinh 1 lần đọc tức thời cho 1 phòng tại thời điểm $timestamp (mặc định hiện tại).
     * Trả về số thực (không format chuỗi hiển thị) để tiện lưu DB / so sánh ngưỡng.
     */
    public static function simulateReading(int $roomId, ?int $timestamp = null): array
    {
        $time = $timestamp ?? time();

        $baseTemp = 20.0 + ($roomId % 5) * 0.8;
        $fluctTemp = sin($time / 30.0 + $roomId) * 0.4;
        $temperature = round($baseTemp + $fluctTemp, 1);

        $baseHumid = 42 + ($roomId % 6) * 1.5;
        $fluctHumid = cos($time / 45.0 + $roomId) * 2;
        $humidity = round($baseHumid + $fluctHumid, 0);

        $basePressure = 8 + ($roomId % 8);
        $fluctPressure = sin($time / 60.0 + $roomId) * 1;
        $pressure = round($basePressure + $fluctPressure, 0);

        return [
            'temperature' => $temperature,
            'humidity' => $humidity,
            'pressure' => $pressure,
        ];
    }

    /**
     * So khớp 1 lần đọc với ngưỡng cấu hình của phòng (room_manufactured_condition,
     * bộ ngưỡng 1) — dùng chung logic mặc định (20-25°C, 35-60%, 5-15Pa) đã áp dụng sẵn
     * ở productionIndex()/production/index.blade.php khi phòng chưa khai báo ngưỡng riêng.
     */
    public static function isOutOfBounds(array $reading, ?object $condition): bool
    {
        $tempMin = ($condition && $condition->temp_min_1 !== null) ? (float) $condition->temp_min_1 : 20.0;
        $tempMax = ($condition && $condition->temp_max_1 !== null) ? (float) $condition->temp_max_1 : 25.0;
        $humidMin = ($condition && $condition->humidity_min_1 !== null) ? (float) $condition->humidity_min_1 : 35.0;
        $humidMax = ($condition && $condition->humidity_max_1 !== null) ? (float) $condition->humidity_max_1 : 60.0;
        $pressMin = ($condition && ($condition->diff_press_corridor_min ?? $condition->diff_press_pal_min ?? $condition->diff_press_mal_min) !== null)
            ? (float) ($condition->diff_press_corridor_min ?? $condition->diff_press_pal_min ?? $condition->diff_press_mal_min) : 5.0;
        $pressMax = ($condition && ($condition->diff_press_corridor_max ?? $condition->diff_press_pal_max ?? $condition->diff_press_mal_max) !== null)
            ? (float) ($condition->diff_press_corridor_max ?? $condition->diff_press_pal_max ?? $condition->diff_press_mal_max) : 15.0;

        return $reading['temperature'] < $tempMin || $reading['temperature'] > $tempMax
            || $reading['humidity'] < $humidMin || $reading['humidity'] > $humidMax
            || $reading['pressure'] < $pressMin || $reading['pressure'] > $pressMax;
    }
}
