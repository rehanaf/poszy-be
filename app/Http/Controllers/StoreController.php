<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\User;
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
     * Pengaturan toko milik user yang sedang login (semua role POS).
     */
    public function mine(Request $request)
    {
        $store = $request->user()->store;
        if (! $store) {
            return response()->json(['message' => 'Store not found.'], 404);
        }
        return response()->json($store, 200);
    }

    /**
     * Perbarui branding toko milik user (owner / superadmin).
     */
    public function updateMine(Request $request)
    {
        try {
            $store = $request->user()->store;
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
     * Upload logo toko (owner / superadmin).
     */
    public function uploadLogo(Request $request)
    {
        try {
            $store = $request->user()->store;
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

    // ===================== SUPERADMIN (kelola semua toko) =====================

    public function index()
    {
        $stores = Store::query()
            ->withCount('users')
            ->paginate(10);

        return response()->json($stores, 200);
    }

    public function show(Store $store)
    {
        return response()->json(['store' => $store->loadCount('users')], 200);
    }

    /**
     * Buat toko baru beserta akun owner-nya.
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'owner_name' => ['required', 'string', 'max:255'],
                'owner_email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
                'owner_password' => ['required', 'string', 'min:8'],
                'tagline' => ['nullable', 'string', 'max:255'],
                'address' => ['nullable', 'string', 'max:255'],
                'phone' => ['nullable', 'string', 'max:40'],
                'nip' => ['nullable', 'string', 'max:40'],
                'default_receipt_size' => ['nullable', 'string', 'in:58,80,a4'],
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

            $owner = User::create([
                'name' => $request->owner_name,
                'email' => $request->owner_email,
                'password' => Hash::make($request->owner_password),
                'role' => 'owner',
                'store_id' => $store->id,
            ]);

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
     * Perbarui toko apa pun (superadmin).
     */
    public function update(Request $request, Store $store)
    {
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