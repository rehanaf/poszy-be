<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Perbaiki constraint nama kategori agar unik per toko (bukan global),
     * karena POSZY adalah aplikasi multi-toko. Tanpa ini, registrasi toko
     * baru yang otomatis membuat kategori default "Umum" akan gagal
     * (UNIQUE constraint failed: categories.name).
     */
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('DROP INDEX IF EXISTS categories_name_unique;');
            DB::statement('CREATE UNIQUE INDEX categories_store_id_name_unique ON categories (store_id, name);');
        } else {
            Schema::table('categories', function (Blueprint $table) {
                $table->dropUnique('categories_name_unique');
                $table->unique(['store_id', 'name'], 'categories_store_id_name_unique');
            });
        }
    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('DROP INDEX IF EXISTS categories_store_id_name_unique;');
            DB::statement('CREATE UNIQUE INDEX categories_name_unique ON categories (name);');
        } else {
            Schema::table('categories', function (Blueprint $table) {
                $table->dropUnique('categories_store_id_name_unique');
                $table->unique('name');
            });
        }
    }
};