<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemSetting extends Model
{
    protected $table = 'system_settings';

    protected $fillable = ['key', 'value', 'label', 'type', 'description', 'updated_by'];

    /**
     * Đọc 1 giá trị cấu hình theo key, cache 10 phút vì command ghi nhận môi trường
     * (chạy mỗi phút) đọc giá trị này liên tục — tránh query DB mỗi lần chạy.
     */
    public static function get(string $key, $default = null)
    {
        return Cache::remember("system_setting.$key", 600, function () use ($key, $default) {
            return static::where('key', $key)->value('value') ?? $default;
        });
    }

    public static function set(string $key, $value, ?int $updatedBy = null): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value, 'updated_by' => $updatedBy]);
        Cache::forget("system_setting.$key");
    }
}
