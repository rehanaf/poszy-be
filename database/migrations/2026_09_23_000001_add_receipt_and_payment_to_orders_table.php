<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('receipt_number', 30)->nullable()->after('id');
            $table->decimal('amount_paid', 10, 2)->nullable()->after('total_amount');
            $table->decimal('change_due', 10, 2)->nullable()->after('amount_paid');
            $table->string('discount_type', 12)->default('rupiah')->after('discount_amount');
        });

        // Backfill nomor struk untuk data lama (per toko & bulan: NoUrut-Bulan-Tahun, mis. 029092026)
        $seq = [];
        $orders = DB::table('orders')->orderBy('id')->get();
        foreach ($orders as $order) {
            $date = $order->order_date ?? $order->created_at;
            $m = (int) date('n', strtotime($date));
            $y = (int) date('y', strtotime($date));
            $key = ($order->store_id ?? 'none') . '-' . $y . '-' . $m;
            $seq[$key] = ($seq[$key] ?? 0) + 1;
            $number = str_pad((string) $seq[$key], 3, '0', STR_PAD_LEFT)
                . str_pad((string) $m, 2, '0', STR_PAD_LEFT)
                . str_pad((string) $y, 2, '0', STR_PAD_LEFT);

            DB::table('orders')->where('id', $order->id)->update([
                'receipt_number' => $number,
                'amount_paid' => $order->total_amount,
                'change_due' => 0.00,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['discount_type', 'change_due', 'amount_paid', 'receipt_number']);
        });
    }
};