<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\User;
use App\Support\CurrentStore;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Handle user registration (buka untuk umum).
     * User baru belum punya toko; langkah berikutnya "Buat Toko Pertama".
     */
    public function register(Request $request)
    {
        try {
            $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
            ]);

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'user',
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'User registered successfully.',
                'user' => $user->makeHidden('password'),
                'stores' => [],
                'token' => $token,
                'token_type' => 'Bearer',
            ], 201);
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
                ->map(fn (Store $s) => $s->only(['id', 'name', 'logo_url', 'tagline', 'is_active', 'default_receipt_size']) + ['role' => 'superadmin'])
                ->all();
        }

        return $user->stores()
            ->orderBy('stores.id')
            ->get()
            ->map(fn (Store $s) => $s->only(['id', 'name', 'logo_url', 'tagline', 'is_active', 'default_receipt_size']) + ['role' => $s->pivot->role])
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