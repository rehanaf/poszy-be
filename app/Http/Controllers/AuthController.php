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
     * Handle user registration.
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
                'role' => 'kasir',
                'store_id' => \App\Models\Store::defaultId(),
            ]);

            // Untuk register, bisa langsung login dan generate token atau hanya register
            // Kita akan generate token untuk kemudahan, mirip dengan alur login
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'User registered successfully.',
                'user' => $user,
                'token' => $token, // Mengembalikan token juga
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