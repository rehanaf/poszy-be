<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->decimal('tax_rate', 5, 2)->default(0)->after('is_active');
            $table->integer('low_stock_threshold')->default(5)->after('tax_rate');
            $table->string('receipt_prefix', 10)->nullable()->after('low_stock_threshold');
            $table->unsignedTinyInteger('receipt_seq_digits')->default(3)->after('receipt_prefix');
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn(['receipt_seq_digits', 'receipt_prefix', 'low_stock_threshold', 'tax_rate']);
        });
    }
};