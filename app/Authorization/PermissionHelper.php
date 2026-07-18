<?php

use Illuminate\Support\Facades\DB;

if (! function_exists('user_has_permission')) {
    function user_has_permission($userId, $permissionName, $typeReturn)
    {
        $result = DB::table('permissions')
            ->join('role_permission', 'permissions.id', '=', 'role_permission.permission_id')
            ->join('user_role', 'role_permission.role_id', '=', 'user_role.role_id')
            ->where('user_role.user_id', $userId)
            ->where('permissions.name', $permissionName)
            ->exists();

        //dd ($result, $userId, $permissionName);

        //dd ($userId);
        if ($typeReturn == "boolean") {
            return $result;
        } elseif ($typeReturn == "disabled") {
            if ($result) {
                return "";
            } else {
                return "disabled";
            }
        }
    }
}

if (! function_exists('ebmr_user_can_edit_template')) {
    /**
     * Được chỉnh sửa (nội dung/thiết kế) hồ sơ eBMR khi: là Dược sĩ phụ trách (owner_id),
     * là Admin, hoặc được ủy quyền chỉnh sửa (có dòng trong ebmr_template_editors).
     *
     * @param object|null $template Bản ghi ebmr_templates (cần có id, owner_id).
     * @param int|null    $userId
     */
    function ebmr_user_can_edit_template($template, $userId): bool
    {
        if (! $template || ! $userId) {
            return false;
        }

        if ((int) ($template->owner_id ?? 0) === (int) $userId) {
            return true;
        }

        if ((session('user')['userGroup'] ?? '') === 'Admin') {
            return true;
        }

        return DB::table('ebmr_template_editors')
            ->where('template_id', $template->id)
            ->where('user_id', $userId)
            ->exists();
    }
}

if (! function_exists('ebmr_user_can_delegate')) {
    /**
     * Được ủy quyền / bỏ ủy quyền chỉnh sửa khi: là Dược sĩ phụ trách (owner_id) của hồ sơ,
     * hoặc có quyền linh động 'ebmr_delegate_edit'.
     *
     * @param object|null $template Bản ghi ebmr_templates (cần có owner_id).
     * @param int|null    $userId
     */
    function ebmr_user_can_delegate($template, $userId): bool
    {
        if (! $template || ! $userId) {
            return false;
        }

        if ((int) ($template->owner_id ?? 0) === (int) $userId) {
            return true;
        }

        return user_has_permission($userId, 'ebmr_delegate_edit', 'boolean');
    }
}
