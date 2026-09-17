<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\User;
use App\Support\CurrentStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class StoreController extends Controller
{
    /**
     * Bangun URL publik absolut (selalu https) untuk aset storage.
     */
    private function publicUrl(string $path): string
    {
        $url = preg_replace('#^https?://#', 'https://', (string) config('app.url'));
        return rtrim($url, '/') . '/' . ltrim($path, '/');
    }

    /**
     * Toko aktif user (berdasarkan CurrentStore yang di-set middleware).
     */
    private function currentStore(): ?Store
    {
        $id = CurrentStore::current();
        return $id ? Store::find($id) : null;
    }

    /**
     * Pengaturan toko aktif (semua user POS).
     */
    public function mine(Request $request)
    {
        $store = $this->currentStore();
        if (! $store) {
            return response()->json(['message' => 'Store not found.'], 404);
        }
        return response()->json($store, 200);
    }

    /**
     * Perbarui branding toko aktif.
     */
    public function updateMine(Request $request)
    {
        try {
            $store = $this->currentStore();
            if (! $store) {
                return response()->json(['message' => 'Store not found.'], 404);
            }

            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'tagline' => ['nullable', 'string', 'max:255'],
                'address' => ['nullable', 'string', 'max:255'],
                'phone' => ['nullable', 'string', 'max:40'],
                'nip' => ['nullable', 'string', 'max:40'],
                'footer' => ['nullable', 'string', 'max:1000'],
                'default_receipt_size' => ['nullable', 'string', 'in:58,80,a4'],
            ]);

            $store->update($validated);

            return response()->json([
                'message' => 'Store updated successfully.',
                'store' => $store,
            ], 200);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Upload logo toko aktif.
     */
    public function uploadLogo(Request $request)
    {
        try {
            $store = $this->currentStore();
            if (! $store) {
                return response()->json(['message' => 'Store not found.'], 404);
            }

            $request->validate([
                'logo' => ['required', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            ]);

            $path = $request->file('logo')->store('logos', 'public');
            $store->logo_url = $this->publicUrl(Storage::url($path));
            $store->save();

            return response()->json([
                'message' => 'Logo updated successfully.',
                'store' => $store,
            ], 200);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred.', 'error' => $e->getMessage()], 500);
        }
    }

    // ===================== Daftar / buat toko =====================

    /**
     * Daftar toko: superadmin lihat semua, user biasa lihat toko miliknya.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Store::query();

        if (! $user->isSuperAdmin()) {
            $query->whereHas('users', fn ($q) => $q->where('store_user.user_id', $user->id));
        }

        $stores = $query->withCount('users')->with('owner:id,name,email')->paginate(10);

        return response()->json($stores, 200);
    }

    public function show(Store $store, Request $request)
    {
        if (! $request->user()->hasStoreAccess($store->id)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        return response()->json(['store' => $store->loadCount('users')->load('owner')], 200);
    }

    /**
     * Buat toko baru. Semua user login boleh buat:
     * - Tanpa owner_email => user yang login otomatis jadi owner.
     * - Dengan owner_email (superadmin) => buat user baru dan jadikan owner.
     */
    public function store(Request $request)
    {
        $user = $request->user();

        // Cek limit toko untuk pengguna biasa (Non-Superadmin)
        if (! $user->isSuperAdmin()) {
            $ownedStoresCount = $user->stores()->wherePivot('role', 'owner')->count();
            if ($ownedStoresCount >= 1) {
                return response()->json([
                    'message' => 'Paket Free hanya dapat memiliki 1 toko. Silakan upgrade ke paket Pro untuk menambah cabang atau toko baru.',
                    'limit_reached' => true,
                    'plan' => 'free',
                ], 403);
            }
        }

        try {
            $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'tagline' => ['nullable', 'string', 'max:255'],
                'address' => ['nullable', 'string', 'max:255'],
                'phone' => ['nullable', 'string', 'max:40'],
                'nip' => ['nullable', 'string', 'max:40'],
                'default_receipt_size' => ['nullable', 'string', 'in:58,80,a4'],
                'owner_name' => ['nullable', 'string', 'max:255'],
                'owner_email' => ['nullable', 'string', 'email', 'max:255', 'unique:users,email'],
                'owner_password' => ['nullable', 'string', 'min:8'],
            ]);

            $store = Store::create([
                'name' => $request->name,
                'tagline' => $request->tagline,
                'address' => $request->address,
                'phone' => $request->phone,
                'nip' => $request->nip,
                'default_receipt_size' => $request->default_receipt_size ?? '80',
                'is_active' => true,
            ]);

            if ($request->filled('owner_email')) {
                $owner = User::create([
                    'name' => $request->input('owner_name', $request->name),
                    'email' => $request->owner_email,
                    'password' => Hash::make($request->input('owner_password', random_bytes(16))),
                    'role' => 'user',
                ]);
            } else {
                $owner = $request->user();
            }

            $store->users()->attach($owner->id, ['role' => 'owner']);
            $store->owner_id = $owner->id;
            $store->save();

            return response()->json([
                'message' => 'Store created successfully.',
                'store' => $store,
                'owner' => $owner->makeHidden(['password']),
            ], 201);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Perbarui toko apa pun (superadmin / owner toko tsb).
     */
    public function update(Request $request, Store $store)
    {
        $user = $request->user();

        if (! $user->isSuperAdmin() && $user->roleInStore($store->id) !== 'owner') {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        try {
            $validated = $request->validate([
                'name' => ['sometimes', 'string', 'max:255'],
                'tagline' => ['nullable', 'string', 'max:255'],
                'address' => ['nullable', 'string', 'max:255'],
                'phone' => ['nullable', 'string', 'max:40'],
                'nip' => ['nullable', 'string', 'max:40'],
                'footer' => ['nullable', 'string', 'max:1000'],
                'default_receipt_size' => ['nullable', 'string', 'in:58,80,a4'],
                'is_active' => ['nullable', 'boolean'],
            ]);

            $store->update($validated);

            return response()->json([
                'message' => 'Store updated successfully.',
                'store' => $store,
            ], 200);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred.', 'error' => $e->getMessage()], 500);
        }
    }
}