<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth; // Pastikan ini di-import

class UserController extends Controller
{
    public function index(Request $request)
    {
        $storeId = \App\Support\CurrentStore::current();

        // User yang menjadi anggota toko aktif (via pivot store_user).
        $query = User::query()->whereHas('stores', fn ($q) => $q->where('store_user.store_id', $storeId));

        // Search by name or email
        if ($request->has('search') && $request->search != '') {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        // Filter by role (role per toko dari pivot)
        if ($request->has('role') && in_array($request->role, ['owner', 'manager', 'kasir'])) {
            $query->whereHas('stores', fn ($q) => $q->where('store_user.store_id', $storeId)->where('store_user.role', $request->role));
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

        // Masukkan role per toko ke tiap user
        $users->getCollection()->transform(function ($user) {
            $user->setAttribute('role', $user->roleInStore(\App\Support\CurrentStore::current()));
            return $user->makeHidden('password');
        });

        return response()->json($users, 200);
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
                'role' => ['required', 'string', 'in:owner,manager,kasir'],
                'profile_image_url' => ['nullable', 'url'],
            ]);

            $storeId = \App\Support\CurrentStore::current();
            if ($storeId === null) {
                return response()->json(['message' => 'No store selected.'], 403);
            }

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'user',
                'profile_image_url' => $request->profile_image_url,
            ]);

            // Kaitkan user ke toko aktif dengan role dari pivot.
            $user->stores()->attach($storeId, ['role' => $request->role]);

            $user->setAttribute('role', $request->role);

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
    public function show(User $user)
    {
        $user->setAttribute('role', $user->roleInStore(\App\Support\CurrentStore::current()));
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
                'role' => ['required', 'string', 'in:owner,manager,kasir'],
                'profile_image_url' => ['nullable', 'url'],
            ]);

            $data = $request->except(['password', 'role']);
            if ($request->filled('password')) {
                $data['password'] = Hash::make($request->password);
            }

            $user->update($data);

            // Update role per toko (pivot store_user) pada toko aktif.
            $storeId = \App\Support\CurrentStore::current();
            if ($storeId !== null) {
                $user->stores()->syncWithoutDetaching([$storeId => ['role' => $request->role]]);
                $user->setAttribute('role', $request->role);
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
     * Remove the specified resource from storage.
     * Hanya bisa diakses oleh admin.
     */
    public function destroy(User $user)
    {
        try {
            // Mengubah auth()->id() menjadi Auth::id()
            if (Auth::id() == $user->id) {
                return response()->json([
                    'message' => 'You cannot delete your own account.',
                ], 403);
            }

            $storeId = \App\Support\CurrentStore::current();

            // Hapus keterkaitan user dari toko aktif (data global user tetap).
            if ($storeId !== null) {
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