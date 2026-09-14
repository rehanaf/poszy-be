<?php

namespace App\Http\Middleware;

use App\Support\CurrentStore;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckUserRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // Pastikan user sudah terautentikasi
        if (! Auth::check()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $user = Auth::user();

        // superadmin platform bisa mengakses semua
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Role dari user pada toko yang sedang aktif (via pivot store_user).
        $storeId = CurrentStore::current();
        if ($storeId === null) {
            return response()->json(['message' => 'No store selected.'], 403);
        }

        $storeRole = $user->roleInStore($storeId);
        if (! $storeRole || ! in_array($storeRole, $roles)) {
            return response()->json(['message' => 'Forbidden. You do not have the necessary permissions.'], 403);
        }

        return $next($request);
    }
}