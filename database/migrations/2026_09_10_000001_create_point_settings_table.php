<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('point_settings', function (Blueprint $table) {
            $table->id();
            // Poin dari transaksi (berdasarkan nominal)
            $table->unsignedInteger('earn_min_amount')->default(0); // Minimal nominal (Rp) untuk dapat poin; 0 = nonaktif
            $table->unsignedInteger('earn_points')->default(0);    // Poin yang didapat saat memenuhi minimal
            $table->boolean('earn_multiple')->default(false);      // Berlaku kelipatan minimal transaksi
            // Penukaran poin menjadi diskon
            $table->unsignedInteger('exchange_points')->default(0); // Jumlah poin yang ditukar
            $table->decimal('exchange_discount_value', 10, 2)->default(0.00); // Besar diskon
            $table->enum('exchange_discount_type', ['percent', 'nominal'])->default('percent'); // % atau Rupiah
            $table->timestamps();
        });

        DB::table('point_settings')->insert([
            'earn_min_amount' => 0,
            'earn_points' => 0,
            'earn_multiple' => false,
            'exchange_points' => 0,
            'exchange_discount_value' => 0.00,
            'exchange_discount_type' => 'percent',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('point_settings');
    }
};