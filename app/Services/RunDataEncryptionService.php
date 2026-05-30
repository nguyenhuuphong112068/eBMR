<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;

/**
 * Mã hoá dữ liệu nhập liệu BMR để ngăn DBA đọc trực tiếp từ SQL client.
 * Dùng Laravel Crypt (AES-256-CBC + HMAC) — không cần thư viện bên ngoài.
 */
class RunDataEncryptionService
{
    /**
     * Mã hoá một chuỗi. Trả về null nếu đầu vào là null.
     */
    public static function encrypt(?string $value): ?string
    {
        if (is_null($value)) return null;
        return Crypt::encryptString($value);
    }

    /**
     * Giải mã một chuỗi.
     * Nếu chuỗi không phải định dạng mã hoá (data cũ) → trả về nguyên gốc (fallback).
     */
    public static function decrypt(?string $value): ?string
    {
        if (is_null($value)) return null;
        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            // Data cũ chưa mã hoá — trả về nguyên gốc
            return $value;
        }
    }

    /**
     * Mã hoá JSON (encode array → JSON string → encrypt).
     */
    public static function encryptJson(mixed $value): ?string
    {
        if (is_null($value)) return null;
        $json = is_string($value) ? $value : json_encode($value);
        return Crypt::encryptString($json);
    }

    /**
     * Giải mã JSON → decode thành array/object.
     * Fallback nếu là JSON thuần (data cũ).
     */
    public static function decryptJson(?string $value, bool $assoc = true): mixed
    {
        if (is_null($value)) return null;
        try {
            $decrypted = Crypt::decryptString($value);
            return json_decode($decrypted, $assoc);
        } catch (\Exception $e) {
            // Data cũ chưa mã hoá — decode trực tiếp
            return json_decode($value, $assoc);
        }
    }
}
