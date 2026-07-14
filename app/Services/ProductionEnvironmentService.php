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

        // Lấy ngưỡng cấu hình của phòng để sinh giá trị nằm trong ngưỡng đạt
        try {
            $condition = \Illuminate\Support\Facades\DB::table('room_manufactured_condition')
                ->where('room_id', $roomId)
                ->first();
            $t = self::thresholds($condition);
        } catch (\Throwable $e) {
            // Cấu hình mặc định khi không truy vấn được DB
            $t = self::thresholds(null);
        }

        // Nhiệt độ: Luôn nằm trong khoảng [temp_min, temp_max]
        $tempMin = $t['temp_min'];
        $tempMax = $t['temp_max'];
        $midTemp = ($tempMin + $tempMax) / 2.0;
        $rangeTemp = $tempMax - $tempMin;
        $maxFluctTemp = $rangeTemp > 0 ? min(0.5, $rangeTemp * 0.2) : 0.0;
        $fluctTemp = sin($time / 30.0 + $roomId) * $maxFluctTemp;
        $temperature = round($midTemp + $fluctTemp, 1);

        // Độ ẩm: Luôn nằm trong khoảng [humid_min, humid_max]
        $humidMin = $t['humid_min'];
        $humidMax = $t['humid_max'];
        $midHumid = ($humidMin + $humidMax) / 2.0;
        $rangeHumid = $humidMax - $humidMin;
        $maxFluctHumid = $rangeHumid > 0 ? min(3.0, $rangeHumid * 0.2) : 0.0;
        $fluctHumid = cos($time / 45.0 + $roomId) * $maxFluctHumid;
        $humidity = round($midHumid + $fluctHumid, 0);

        // Áp suất: Luôn nằm trong khoảng [press_min, press_max]
        $pressMin = $t['press_min'];
        $pressMax = $t['press_max'];
        $midPress = ($pressMin + $pressMax) / 2.0;
        $rangePress = $pressMax - $pressMin;
        $maxFluctPress = $rangePress > 0 ? min(2.0, $rangePress * 0.2) : 0.0;
        $fluctPress = sin($time / 60.0 + $roomId) * $maxFluctPress;
        $pressure = round($midPress + $fluctPress, 0);

        return [
            'temperature' => $temperature,
            'humidity' => $humidity,
            'pressure' => $pressure,
        ];
    }

    /**
     * Trích ngưỡng cấu hình của phòng (room_manufactured_condition, bộ ngưỡng 1) — dùng
     * chung logic mặc định (20-25°C, 35-60%, 5-15Pa) đã áp dụng sẵn ở
     * productionIndex()/production/index.blade.php khi phòng chưa khai báo ngưỡng riêng.
     */
    public static function thresholds(?object $condition): array
    {
        return [
            'temp_min' => ($condition && $condition->temp_min_1 !== null) ? (float) $condition->temp_min_1 : 20.0,
            'temp_max' => ($condition && $condition->temp_max_1 !== null) ? (float) $condition->temp_max_1 : 25.0,
            'humid_min' => ($condition && $condition->humidity_min_1 !== null) ? (float) $condition->humidity_min_1 : 35.0,
            'humid_max' => ($condition && $condition->humidity_max_1 !== null) ? (float) $condition->humidity_max_1 : 60.0,
            'press_min' => ($condition && ($condition->diff_press_corridor_min ?? $condition->diff_press_pal_min ?? $condition->diff_press_mal_min) !== null)
                ? (float) ($condition->diff_press_corridor_min ?? $condition->diff_press_pal_min ?? $condition->diff_press_mal_min) : 5.0,
            'press_max' => ($condition && ($condition->diff_press_corridor_max ?? $condition->diff_press_pal_max ?? $condition->diff_press_mal_max) !== null)
                ? (float) ($condition->diff_press_corridor_max ?? $condition->diff_press_pal_max ?? $condition->diff_press_mal_max) : 15.0,
        ];
    }

    /**
     * So khớp 1 lần đọc với ngưỡng cấu hình của phòng.
     */
    public static function isOutOfBounds(array $reading, ?object $condition): bool
    {
        $t = self::thresholds($condition);

        return $reading['temperature'] < $t['temp_min'] || $reading['temperature'] > $t['temp_max']
            || $reading['humidity'] < $t['humid_min'] || $reading['humidity'] > $t['humid_max']
            || $reading['pressure'] < $t['press_min'] || $reading['pressure'] > $t['press_max'];
    }
}
