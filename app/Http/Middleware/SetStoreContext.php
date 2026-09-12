<?php

namespace App\Http\Middleware;

use App\Support\CurrentStore;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetStoreContext
{
    /**
     * Simpan store user login sebagai konteks global scope (HasStore).
     * superadmin => null (tanpa filter).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        CurrentStore::set($user && $user->role !== 'superadmin' ? $user->store_id : null);

        try {
            return $next($request);
        } finally {
            CurrentStore::reset();
        }
    }
}