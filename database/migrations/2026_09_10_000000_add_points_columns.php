<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->integer('points_earn')->default(0)->after('discount');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->integer('points_balance')->default(0)->after('profile_image_url');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->integer('points_earned')->default(0)->after('tax_amount');
            $table->integer('points_redeemed')->default(0)->after('points_earned');
            $table->decimal('points_discount', 10, 2)->default(0.00)->after('points_redeemed');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['points_discount', 'points_redeemed', 'points_earned']);
        });
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('points_balance');
        });
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('points_earn');
        });
    }
};