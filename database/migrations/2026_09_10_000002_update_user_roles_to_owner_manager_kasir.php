<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Tidak me-rename tabel utuh: SQLite akan mengarahkan ulang FK dari tabel
    // anak (orders, purchases, shifts, expenses) ke nama baru. Sebagai gantinya
    // kolom role di-bend dan dibangun ulang agar CHECK constraint lama hilang.
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role_new', 20)->default('kasir');
        });

        DB::statement(
            "UPDATE users SET role_new = CASE role
                 WHEN 'admin' THEN 'owner'
                 WHEN 'cashier' THEN 'kasir'
                 ELSE 'manager' END"
        );

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('role_new', 'role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role_old', 20)->default('user');
        });

        DB::statement(
            "UPDATE users SET role_old = CASE role
                 WHEN 'owner' THEN 'admin'
                 WHEN 'kasir' THEN 'cashier'
                 ELSE 'user' END"
        );

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('role_old', 'role');
        });
    }
};