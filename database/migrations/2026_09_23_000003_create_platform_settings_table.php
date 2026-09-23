<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->json('value')->nullable();
            $table->timestamps();
        });

        // Default: branding "Powered by SemestaPOS" aktif, tanpa logo.
        $defaults = [
            'powered_by_enabled' => true,
            'powered_by_text' => 'Powered by SemestaPOS',
            'powered_by_logo' => null,
        ];
        foreach ($defaults as $key => $value) {
            DB::table('platform_settings')->insert([
                'key' => $key,
                'value' => json_encode($value),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_settings');
    }
};