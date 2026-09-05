<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained('products')->onDelete('restrict'); // NULL for temporary products
            $table->integer('quantity');
            $table->decimal('price', 10, 2); // Harga jual per unit pada saat transaksi
            $table->decimal('discount', 5, 2)->default(0.00); // Diskon yang diterapkan pada item ini
            $table->decimal('subtotal', 10, 2);
            $table->string('product_name'); // Nama produk (bisa produk sementara)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};