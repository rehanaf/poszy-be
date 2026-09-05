<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth; // Pastikan ini di-import

class UserController extends Controller
{
    /**
     * Display a listing of the resource with search, pagination, sorting, and filtering.
     */
    public function index(Request $request)
    {
        $query = User::query();

        // Search by name or email
        if ($request->has('search') && $request->search != '') {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        // Filter by role
        if ($request->has('role') && in_array($request->role, ['admin', 'cashier', 'user'])) {
            $query->where('role', $request->role);
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

        // Make sure to hide password from the paginated response
        $users->getCollection()->transform(function ($user) {
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
                'role' => ['required', 'string', 'in:admin,cashier,user'],
                'profile_image_url' => ['nullable', 'url'],
            ]);

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => $request->role,
                'profile_image_url' => $request->profile_image_url,
            ]);

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
                'role' => ['required', 'string', 'in:admin,cashier,user'],
                'profile_image_url' => ['nullable', 'url'],
            ]);

            $data = $request->except('password');
            if ($request->filled('password')) {
                $data['password'] = Hash::make($request->password);
            }

            $user->update($data);

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

            $user->delete();

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