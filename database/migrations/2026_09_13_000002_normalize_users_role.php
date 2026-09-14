<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Normalisasi kolom role: hanya 'superadmin' yang berarti platform admin,
        // sisanya 'user' biasa (role per toko disimpan di pivot store_user).
        DB::table('users')
            ->where('role', '!=', 'superadmin')
            ->update(['role' => 'user']);
    }

    public function down(): void
    {
        // Perubahan normalisasi nilai tidak di-rollback.
    }
};