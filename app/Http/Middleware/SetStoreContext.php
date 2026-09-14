<?php

namespace App\Http\Middleware;

use App\Support\CurrentStore;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetStoreContext
{
    /**
     * Set konteks toko untuk request ini berdasarkan header X-Store-Id.
     * - superadmin: selalu null (tanpa filter / lihat semua toko).
     * - user biasa: pakai X-Store-Id (wajib punya akses via pivot store_user),
     *   fallback ke toko pertama miliknya bila header kosong.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            CurrentStore::set(null);
        } elseif ($user->isSuperAdmin()) {
            // superadmin: tanpa X-Store-Id => lihat semua; dengan X-Store-Id => fokus ke toko itu.
            $storeId = $request->header('X-Store-Id') ?? $request->query('store_id');
            CurrentStore::set($storeId && \App\Models\Store::whereKey((int) $storeId)->exists() ? (int) $storeId : null);
        } else {
            $storeId = $request->header('X-Store-Id') ?? $request->query('store_id');

            if ($storeId && $user->hasStoreAccess((int) $storeId)) {
                CurrentStore::set((int) $storeId);
            } else {
                $first = $user->stores()->orderBy('id')->first();
                CurrentStore::set($first?->id);
            }
        }

        try {
            return $next($request);
        } finally {
            CurrentStore::reset();
        }
    }
}