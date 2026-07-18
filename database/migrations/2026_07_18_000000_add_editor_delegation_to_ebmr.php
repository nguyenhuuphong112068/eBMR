<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Danh sách 2 quyền linh động thêm mới cho tính năng ủy quyền / đổi Dược sĩ phụ trách.
     * permission_group = 11 (nhóm eBMR).
     */
    private array $permissions = [
        [
            'name' => 'ebmr_delegate_edit',
            'display_name' => 'Ủy quyền chỉnh sửa hồ sơ eBMR',
            'description' => 'Ủy quyền / bỏ ủy quyền chỉnh sửa hồ sơ eBMR cho người khác',
        ],
        [
            'name' => 'ebmr_change_owner',
            'display_name' => 'Thay đổi Dược sĩ phụ trách hồ sơ eBMR',
            'description' => 'Chuyển quyền Dược sĩ phụ trách (chủ sở hữu) của hồ sơ eBMR',
        ],
    ];

    public function up(): void
    {
        // a. Bảng ủy quyền chỉnh sửa
        if (!Schema::hasTable('ebmr_template_editors')) {
            Schema::create('ebmr_template_editors', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('template_id')->index();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('granted_by')->nullable();
                $table->timestamps();
                $table->unique(['template_id', 'user_id']);
            });
        }

        // b. Thêm 2 permission (idempotent theo name)
        $adminRoleId = DB::table('roles')->where('name', 'Admin')->value('id') ?? 1;
        foreach ($this->permissions as $perm) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $perm['name']],
                [
                    'permission_group' => 11,
                    'display_name' => $perm['display_name'],
                    'description' => $perm['description'],
                    'updated_at' => now(),
                ]
            );

            // c. Gán cho Admin
            $permId = DB::table('permissions')->where('name', $perm['name'])->value('id');
            if ($permId) {
                DB::table('role_permission')->updateOrInsert(
                    ['role_id' => $adminRoleId, 'permission_id' => $permId]
                );
            }
        }
    }

    public function down(): void
    {
        $permIds = DB::table('permissions')
            ->whereIn('name', array_column($this->permissions, 'name'))
            ->pluck('id')
            ->toArray();

        if (!empty($permIds)) {
            DB::table('role_permission')->whereIn('permission_id', $permIds)->delete();
            DB::table('permissions')->whereIn('id', $permIds)->delete();
        }

        Schema::dropIfExists('ebmr_template_editors');
    }
};
