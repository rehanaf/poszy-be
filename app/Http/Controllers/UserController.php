<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\StoreInvitation;
use App\Models\User;
use App\Notifications\UserInvitedToStore;
use App\Support\CurrentStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // Pastikan ini di-import
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $storeId = \App\Support\CurrentStore::current();
        $authUser = $request->user();

        $query = User::query();

        // Superadmin: lihat SEMUA user (tanpa konteks toko).
        // Owner/manager: hanya user yang menjadi anggota toko aktif.
        if ($authUser->isSuperAdmin()) {
            $query->with('stores:id,name');
        } else {
            $query->whereHas('stores', fn ($q) => $q->where('store_user.store_id', $storeId))
                ->with(['stores' => fn ($q) => $q->where('store_user.store_id', $storeId)->select('stores.id', 'stores.name')]);
        }

        // Search by name or email
        if ($request->has('search') && $request->search != '') {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        // Filter by role (role per toko dari pivot)
        if ($request->has('role') && in_array($request->role, ['owner', 'manager', 'kasir'])) {
            if ($authUser->isSuperAdmin()) {
                $query->whereHas('stores', fn ($q) => $q->where('store_user.role', $request->role));
            } else {
                $query->whereHas('stores', fn ($q) => $q->where('store_user.store_id', $storeId)->where('store_user.role', $request->role));
            }
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'name'); // Default sort by name
        $sortOrder = $request->get('sort_order', 'asc'); // Default sort order asc

        // Validate sort_by column
        $allowedSortColumns = ['id', 'name', 'email', 'role', 'created_at', 'updated_at'];
        if (!in_array($sortBy, $allowedSortColumns)) {
            $sortBy = 'name'; // Fallback
        }
        $sortOrder = in_array(strtolower($sortOrder), ['asc', 'desc']) ? $sortOrder : 'asc';

        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 10); // Default 10 items per page
        $users = $query->paginate($perPage);

        // Lampirkan role & keanggotaan toko ke tiap user
        $users->getCollection()->transform(function ($user) use ($storeId) {
            $user->setAttribute('store_role', $user->isSuperAdmin() ? 'superadmin' : $user->roleInStore($storeId));
            $user->setAttribute('store_memberships', $this->membershipsOf($user));
            return $user->makeHidden(['password', 'stores']);
        });

        return response()->json($users, 200);
    }

    /**
     * Daftar keanggotaan user di toko-toko (dari relasi stores yang sudah di-load).
     */
    private function membershipsOf(User $user): array
    {
        return $user->stores
            ->map(fn ($s) => [
                'store_id' => $s->id,
                'name' => $s->name,
                'role' => $s->pivot->role,
            ])
            ->values()
            ->all();
    }

    /**
     * Store a newly created resource in storage.
     * Hanya bisa diakses oleh admin.
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
                'profile_image_url' => ['nullable', 'url'],
                'role' => [$request->user()->isSuperAdmin() ? 'nullable' : 'required', 'string', 'in:owner,manager,kasir'],
                'stores' => ['nullable', 'array'],
                'stores.*.store_id' => ['required', 'integer', 'exists:stores,id'],
                'stores.*.role' => ['required', 'string', 'in:owner,manager,kasir'],
            ]);

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'user',
                'profile_image_url' => $request->profile_image_url,
            ]);

            if ($request->user()->isSuperAdmin()) {
                foreach ($request->input('stores', []) as $m) {
                    $user->stores()->attach($m['store_id'], ['role' => $m['role']]);
                }
            } else {
                $storeId = \App\Support\CurrentStore::current();
                if ($storeId === null) {
                    return response()->json(['message' => 'No store selected.'], 403);
                }
                $user->stores()->attach($storeId, ['role' => $request->role]);
            }

            $user->setAttribute('role', $request->role ?? 'user');

            return response()->json([
                'message' => 'User created successfully.',
                'user' => $user->makeHidden('password')
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while creating the user.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     * Hanya bisa diakses oleh admin.
     */
    public function show(User $user, Request $request)
    {
        if ($request->user()->isSuperAdmin()) {
            $user->load('stores:id,name');
            $user->setAttribute('store_role', $user->isSuperAdmin() ? 'superadmin' : null);
            $user->setAttribute('store_memberships', $this->membershipsOf($user));
            return response()->json($user->makeHidden(['password', 'stores']), 200);
        }

        $user->setAttribute('store_role', $user->roleInStore(\App\Support\CurrentStore::current()));
        return response()->json($user->makeHidden('password'), 200);
    }

    /**
     * Update the specified resource in storage.
     * Hanya bisa diakses oleh admin.
     */
    public function update(Request $request, User $user)
    {
        try {
            $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
                'password' => ['nullable', 'string', 'min:8', 'confirmed'],
                'profile_image_url' => ['nullable', 'url'],
                'role' => [$request->user()->isSuperAdmin() ? 'nullable' : 'required', 'string', 'in:owner,manager,kasir'],
                'stores' => ['nullable', 'array'],
                'stores.*.store_id' => ['required', 'integer', 'exists:stores,id'],
                'stores.*.role' => ['required', 'string', 'in:owner,manager,kasir'],
            ]);

            $data = $request->except(['password', 'role', 'stores']);
            if ($request->filled('password')) {
                $data['password'] = Hash::make($request->password);
            }

            $user->update($data);

            if ($request->user()->isSuperAdmin()) {
                // Superadmin: timpa semua keanggotaan toko user.
                $map = [];
                foreach ($request->input('stores', []) as $m) {
                    $map[$m['store_id']] = ['role' => $m['role']];
                }
                $user->stores()->sync($map);
                $user->setAttribute('store_role', count($map) > 0 ? reset($map)['role'] : null);
                $user->setAttribute('store_memberships', collect($map)
                    ->map(fn ($r, $sid) => ['store_id' => (int) $sid, 'role' => $r['role']])
                    ->values()
                    ->all());
            } else {
                // Owner: update role pada toko aktif (tidak melepas ke toko lain).
                $storeId = \App\Support\CurrentStore::current();
                if ($storeId !== null) {
                    $user->stores()->syncWithoutDetaching([$storeId => ['role' => $request->role]]);
                    $user->setAttribute('store_role', $request->role);
                }
            }

            return response()->json([
                'message' => 'User updated successfully.',
                'user' => $user->makeHidden('password')
            ], 200);
        } catch (ValidationException | \Exception $e) { // Gabungkan exception
            return response()->json([
                'message' => 'An error occurred while updating the user.',
                'errors' => $e instanceof ValidationException ? $e->errors() : [$e->getMessage()], // Tampilkan errors validasi atau pesan umum
            ], $e instanceof ValidationException ? 422 : 500);
        }
    }

    /**
     * Undang pengguna (yang sudah terdaftar) ke toko aktif berdasarkan email.
     * Hanya owner. User yang diundang akan menerima notifikasi in-app.
     */
    public function invite(Request $request)
    {
        try {
            $request->validate([
                'email' => ['required', 'email'],
                'role' => ['required', 'in:owner,manager,kasir'],
            ]);

            $storeId = CurrentStore::current();
            if ($storeId === null) {
                return response()->json(['message' => 'No store selected.'], 403);
            }

            $email = strtolower(trim($request->email));

            $target = User::whereRaw('LOWER(email) = ?', [$email])->first();
            if (! $target) {
                return response()->json([
                    'message' => "Email $email belum terdaftar sebagai pengguna.",
                ], 422);
            }

            if ($target->isSuperAdmin()) {
                return response()->json(['message' => 'Superadmin tidak dapat diundang.'], 422);
            }

            if ($target->stores()->where('store_user.store_id', $storeId)->exists()) {
                return response()->json(['message' => 'Pengguna tersebut sudah menjadi anggota toko ini.'], 422);
            }

            $pending = StoreInvitation::where('email', $email)
                ->where('store_id', $storeId)
                ->whereNull('accepted_at')
                ->exists();

            if ($pending) {
                return response()->json(['message' => 'Pengguna tersebut sudah memiliki undangan yang belum diterima.'], 422);
            }

            $token = Str::random(40);
            StoreInvitation::create([
                'store_id' => $storeId,
                'invited_by' => $request->user()->id,
                'email' => $email,
                'role' => $request->role,
                'token' => $token,
            ]);

            $storeName = Store::find($storeId)?->name ?? 'toko';
            $inviterName = $request->user()->name;
            $roleLabel = ucfirst($request->role);

            $target->notify(new UserInvitedToStore([
                'icon' => 'user-plus',
                'title' => 'Undangan bergabung ke ' . $storeName,
                'message' => $inviterName . ' mengundang Anda sebagai ' . $roleLabel . ' di toko "' . $storeName . '".',
                'store_id' => $storeId,
                'store_name' => $storeName,
                'role' => $request->role,
                'token' => $token,
                'action' => 'accept_invite',
            ]));

            return response()->json([
                'message' => 'Undangan terkirim ke ' . $email . '.',
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Remove the specified resource from storage.
     * Hanya bisa diakses oleh admin.
     */
    public function destroy(Request $request, User $user)
    {
        try {
            // Mengubah auth()->id() menjadi Auth::id()
            if (Auth::id() == $user->id) {
                return response()->json([
                    'message' => 'You cannot delete your own account.',
                ], 403);
            }

            $storeId = \App\Support\CurrentStore::current();

            if ($request->user()->isSuperAdmin()) {
                // Superadmin: hapus user sepenuhnya (termasuk relasinya ke semua toko).
                $user->stores()->detach();
                $user->delete();
            } elseif ($storeId !== null) {
                // Owner: hapus keterkaitan user dari toko aktif (data global user tetap).
                $user->stores()->detach($storeId);
            } else {
                $user->delete();
            }

            return response()->json([
                'message' => 'User deleted successfully.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while deleting the user.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}