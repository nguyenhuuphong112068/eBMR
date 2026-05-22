<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Tạo bảng designations (Chức vụ)
        if (!Schema::hasTable('designations')) {
            Schema::create('designations', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('created_by')->nullable();
                $table->timestamp('created_at')->nullable();
            });
        }

        // Thêm cột designation_id vào bảng user_management
        if (!Schema::hasColumn('user_management', 'designation_id')) {
            Schema::table('user_management', function (Blueprint $table) {
                $table->unsignedBigInteger('designation_id')->nullable()->after('groupName');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('user_management', 'designation_id')) {
            Schema::table('user_management', function (Blueprint $table) {
                $table->dropColumn('designation_id');
            });
        }

        Schema::dropIfExists('designations');
    }
};
