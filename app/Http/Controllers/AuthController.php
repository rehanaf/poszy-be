<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\PaymentMethod;
use App\Models\PointSetting;
use App\Models\Store;
use App\Models\User;
use App\Support\CurrentStore;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Handle user registration (buka untuk umum).
     * Otomatis membuatkan 1 toko baru gratis (Free Plan) beserta data dasar toko.
     */
    public function register(Request $request)
    {
        try {
            $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
                'store_name' => ['nullable', 'string', 'max:255'],
            ]);

            return DB::transaction(function () use ($request) {
                $user = User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                    'role' => 'user',
                ]);

                // Buat toko pertama (Paket Free)
                $storeName = trim($request->input('store_name') ?? '') ?: ($user->name . ' Store');
                $store = Store::create([
                    'name' => $storeName,
                    'tagline' => 'Point Of Sale',
                    'default_receipt_size' => '80',
                    'is_active' => true,
                    'plan' => 'free',
                ]);

                $store->users()->attach($user->id, ['role' => 'owner']);
                $store->owner_id = $user->id;
                $store->save();

                // Inisialisasi data dasar untuk toko baru
                PaymentMethod::create([
                    'store_id' => $store->id,
                    'name' => 'Tunai',
                    'description' => 'Pembayaran tunai langsung',
                    'is_active' => true,
                ]);

                PaymentMethod::create([
                    'store_id' => $store->id,
                    'name' => 'QRIS',
                    'description' => 'Pembayaran digital QRIS',
                    'is_active' => true,
                ]);

                Category::create([
                    'store_id' => $store->id,
                    'name' => 'Umum',
                    'description' => 'Kategori produk umum',
                ]);

                PointSetting::create([
                    'store_id' => $store->id,
                    'earn_min_amount' => 100000,
                    'earn_points' => 10,
                    'earn_multiple' => true,
                    'exchange_points' => 100,
                    'exchange_discount_value' => 10,
                    'exchange_discount_type' => 'percent',
                ]);

                $token = $user->createToken('auth_token')->plainTextToken;

                return response()->json([
                    'message' => 'Registrasi berhasil. Toko Anda siap digunakan!',
                    'user' => $user->makeHidden('password'),
                    'stores' => $this->userStores($user),
                    'store' => $store,
                    'token' => $token,
                    'token_type' => 'Bearer',
                ], 201);
            });
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred during registration.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Handle user login.
     */
    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => ['required', 'string', 'email'],
                'password' => ['required', 'string'],
            ]);

            if (!Auth::attempt($request->only('email', 'password'))) {
                return response()->json([
                    'message' => 'Invalid email or password.',
                ], 401);
            }

            $user = $request->user();
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Login successful.',
                'user' => $user->makeHidden('password'),
                'stores' => $this->userStores($user),
                'token' => $token,
                'token_type' => 'Bearer',
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception | AuthenticationException $e) {
            return response()->json([
                'message' => 'An error occurred during login.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Handle user logout.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logout successful. Token revoked.'], 200);
    }

    /**
     * Get authenticated user details.
     */
    public function user(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'user' => $user->makeHidden('password'),
            'stores' => $this->userStores($user),
            'store' => $this->storeById(CurrentStore::current()),
        ], 200);
    }

    /**
     * Set/validasi toko aktif yang dipilih user.
     */
    public function switchStore(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'store_id' => ['required', 'integer'],
        ]);

        $storeId = $request->input('store_id');
        if (! $user->hasStoreAccess($storeId)) {
            return response()->json(['message' => 'You do not have access to this store.'], 403);
        }

        return response()->json([
            'message' => 'Store switched.',
            'store' => $this->storeById($storeId),
        ], 200);
    }

    /**
     * Daftar toko yang bisa diakses user (dengan role per toko dari pivot).
     * Superadmin: seluruh toko.
     */
    private function userStores(User $user): array
    {
        if ($user->isSuperAdmin()) {
            return Store::query()
                ->orderBy('id')
                ->get()
                ->map(fn (Store $s) => $s->only(['id', 'name', 'logo_url', 'tagline', 'is_active', 'default_receipt_size', 'plan']) + ['role' => 'superadmin'])
                ->all();
        }

        return $user->stores()
            ->orderBy('stores.id')
            ->get()
            ->map(fn (Store $s) => $s->only(['id', 'name', 'logo_url', 'tagline', 'is_active', 'default_receipt_size', 'plan']) + ['role' => $s->pivot->role])
            ->all();
    }

    private function storeById(?int $id): ?Store
    {
        if ($id === null) {
            return null;
        }

        return Store::find($id);
    }
}