<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'store_id')) {
            Schema::table('users', function ($table) {
                $table->dropForeign(['store_id']);
                if (\Illuminate\Support\Facades\DB::getDriverName() === 'sqlite' && Schema::hasIndex('users', 'users_store_id_index')) {
                    $table->dropIndex('users_store_id_index');
                }
                $table->dropColumn('store_id');
            });
        }
    }

    public function down(): void
    {
        // Tidak perlu rollback (dipisah dari migrasi utama).
    }
};