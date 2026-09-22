<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Unique customer dipindah jadi per-toko.
     *
     * Sebelumnya customer_code/email/phone unique di SELURUH tabel, padahal
     * data aplikasi di-scope per store (global scope HasStore) — sehingga
     * toko berbeda tidak bisa punya kode/email/telepon sama meski aman.
     */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropUnique('customers_customer_code_unique');
            $table->dropUnique('customers_email_unique');
            $table->dropUnique('customers_phone_unique');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->unique(['store_id', 'customer_code']);
            $table->unique(['store_id', 'email']);
            $table->unique(['store_id', 'phone']);
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropUnique(['store_id', 'customer_code']);
            $table->dropUnique(['store_id', 'email']);
            $table->dropUnique(['store_id', 'phone']);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->unique('customer_code');
            $table->unique('email');
            $table->unique('phone');
        });
    }
};
