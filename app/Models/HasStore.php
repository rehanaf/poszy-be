<?php

namespace App\Models;

use App\Support\CurrentStore;
use Illuminate\Database\Eloquent\Builder;

trait HasStore
{
    /**
     * Otomatis: filter semua query berdasarkan store user yang sedang login.
     * Konteks store diambil dari CurrentStore (di-set middleware),
     * BUKAN auth()->user(), agar tidak terjadi recursion pada model User.
     * null = tanpa filter (superadmin, CLI, seeder).
     */
    protected static function bootHasStore(): void
    {
        static::addGlobalScope('store', function (Builder $query) {
            $storeId = CurrentStore::current();

            if ($storeId === null) {
                return;
            }

            $table = $query->getModel()->getTable();
            $query->where($table . '.store_id', $storeId);
        });

        // Auto-set store_id saat membuat record. Store_id dari client TIDAK dipakai:
        // selalu ikut store user login (atau toko default untuk konteks non-login).
        static::creating(function ($model) {
            $storeId = CurrentStore::current();

            if ($storeId !== null) {
                $model->store_id = $storeId;
                return;
            }

            $model->store_id = $model->store_id ?? Store::defaultId();
        });
    }

    /**
     * Relasi ke toko pemilik data.
     */
    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }
}