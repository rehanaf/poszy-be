<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $scopedTables = [
        'users',
        'categories',
        'products',
        'customers',
        'payment_methods',
        'suppliers',
        'orders',
        'order_items',
        'purchases',
        'purchase_items',
        'shifts',
        'expenses',
        'point_settings',
    ];

    public function up(): void
    {
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('POSZY');
            $table->string('tagline')->nullable();
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('nip')->nullable();
            $table->string('logo_url')->nullable();
            $table->text('footer')->nullable();
            $table->string('default_receipt_size', 8)->default('80');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Tambahkan kolom store_id (nullable agar migrasi aman pada data lama)
        foreach ($this->scopedTables as $table) {
            if (Schema::hasTable($table) && !Schema::hasColumn($table, 'store_id')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->unsignedBigInteger('store_id')->nullable()->index();
                });
            }
        }

        // Toko default: semua data lama milik toko pertama
        $storeId = DB::table('stores')->insertGetId([
            'name' => 'POSZY',
            'tagline' => 'Point Of Sale',
            'address' => null,
            'phone' => null,
            'nip' => null,
            'logo_url' => null,
            'footer' => null,
            'default_receipt_size' => '80',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ($this->scopedTables as $table) {
            if (Schema::hasColumn($table, 'store_id')) {
                DB::table($table)->whereNull('store_id')->update(['store_id' => $storeId]);
            }
        }
    }

    public function down(): void
    {
        foreach ($this->scopedTables as $table) {
            if (Schema::hasColumn($table, 'store_id')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->dropColumn('store_id');
                });
            }
        }
        Schema::dropIfExists('stores');
    }
};