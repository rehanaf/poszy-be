<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Buat tabel pivot store_user
        Schema::create('store_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 20)->default('kasir'); // owner / manager / kasir
            $table->timestamps();

            $table->unique(['store_id', 'user_id']);
            $table->index('user_id');
        });

        // 2. Tambah owner_id di stores
        Schema::table('stores', function (Blueprint $table) {
            $table->foreignId('owner_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
        });

        // 3. Backfill: pindahkan data users.store_id ke pivot + set owner_id
        $users = DB::table('users')
            ->whereNotNull('store_id')
            ->select('id', 'store_id', 'role')
            ->get();

        foreach ($users as $user) {
            $role = in_array($user->role, ['owner', 'manager', 'kasir']) ? $user->role : 'kasir';
            DB::table('store_user')->insert([
                'store_id' => $user->store_id,
                'user_id'  => $user->id,
                'role'     => $role,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Set owner_id: cari user dengan role='owner' di setiap toko
        $stores = DB::table('stores')->get();
        foreach ($stores as $store) {
            $owner = DB::table('store_user')
                ->where('store_id', $store->id)
                ->where('role', 'owner')
                ->orderBy('id')
                ->first();
            if ($owner) {
                DB::table('stores')->where('id', $store->id)->update(['owner_id' => $owner->user_id]);
            }
        }

        // 4. Drop store_id dari users (SQLite butuh hapus index lebih dulu)
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['store_id']);
            if (Schema::hasIndex('users', 'users_store_id_index')) {
                $table->dropIndex('users_store_id_index');
            }
            $table->dropColumn('store_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('store_id')->nullable()->after('role')->constrained()->nullOnDelete();
        });

        // Backfill store_id dari pivot
        $pivots = DB::table('store_user')
            ->select('user_id', 'store_id')
            ->groupBy('user_id')
            ->selectRaw('user_id, MIN(store_id) as store_id')
            ->get();

        foreach ($pivots as $p) {
            DB::table('users')->where('id', $p->user_id)->update(['store_id' => $p->store_id]);
        }

        Schema::table('stores', function (Blueprint $table) {
            $table->dropForeign(['owner_id']);
            $table->dropColumn('owner_id');
        });

        Schema::dropIfExists('store_user');
    }
};