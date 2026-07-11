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
        if (Schema::hasTable('designations')) {
            Schema::table('designations', function (Blueprint $table) {
                if (!Schema::hasColumn('designations', 'shortName')) {
                    $table->string('shortName')->nullable()->after('id');
                }
                if (!Schema::hasColumn('designations', 'active')) {
                    $table->boolean('active')->default(true)->after('name');
                }
                if (!Schema::hasColumn('designations', 'prepareBy')) {
                    $table->string('prepareBy')->nullable()->after('active');
                }
                if (!Schema::hasColumn('designations', 'updated_at')) {
                    $table->timestamp('updated_at')->nullable();
                }
            });
        } else {
            Schema::create('designations', function (Blueprint $table) {
                $table->id();
                $table->string('shortName')->nullable();
                $table->string('name');
                $table->boolean('active')->default(true);
                $table->string('prepareBy')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('designations')) {
            Schema::table('designations', function (Blueprint $table) {
                $table->dropColumn(['shortName', 'active', 'prepareBy', 'updated_at']);
            });
        }
    }
};
