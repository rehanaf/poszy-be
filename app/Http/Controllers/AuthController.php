<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
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

            // Tolak login jika store nonaktif (superadmin tidak terpengaruh).
            if ($user->role !== 'superadmin') {
                $store = $user->store;
                if (! $store || ! $store->is_active) {
                    Auth::logout();
                    $user->currentAccessToken()?->delete();

                    return response()->json([
                        'message' => 'Akun toko sedang nonaktif. Hubungi administrator.',
                    ], 403);
                }
            }

            // Hasilkan token personal access token untuk user yang berhasil login
            $token = $user->createToken('auth_token')->plainTextToken; // 'auth_token' adalah nama token

            return response()->json([
                'message' => 'Login successful.',
                'user' => $user,
                'store' => $user->store,
                'token' => $token, // Kirimkan token ke frontend
                'token_type' => 'Bearer',
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception | AuthenticationException $e) { // Tangani AuthenticationException juga
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
        // Hapus token yang digunakan saat ini
        // Jika menggunakan Personal Access Token (Bearer Token), ini adalah cara logoutnya
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logout successful. Token revoked.'], 200);
    }

    /**
     * Get authenticated user details.
     */
    public function user(Request $request)
    {
        return response()->json([
            'user' => $request->user(),
            'store' => $request->user()->store,
        ], 200);
    }
}