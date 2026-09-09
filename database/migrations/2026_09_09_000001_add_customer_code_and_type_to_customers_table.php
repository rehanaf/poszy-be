<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('customer_code', 30)->nullable()->after('id');
            $table->string('customer_type', 50)->nullable()->after('profile_image_url');
        });

        // Backfill kode customer untuk data lama: CR00001-2026, dst (urutan by id, tahun = tahun dibuat)
        $customers = DB::table('customers')->orderBy('id')->get();
        $n = 0;
        foreach ($customers as $customer) {
            $n++;
            $year = $customer->created_at ? Carbon::parse($customer->created_at)->year : now()->year;
            DB::table('customers')->where('id', $customer->id)->update([
                'customer_code' => 'CR' . str_pad((string) $n, 5, '0', STR_PAD_LEFT) . '-' . $year,
            ]);
        }

        Schema::table('customers', function (Blueprint $table) {
            $table->unique('customer_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropUnique(['customer_code']);
            $table->dropColumn(['customer_code', 'customer_type']);
        });
    }
};