<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth; // Untuk mendapatkan user yang sedang login

class ShiftController extends Controller
{
    /**
     * Display a listing of the resource with pagination, sorting, and filtering.
     */
    public function index(Request $request)
    {
        $query = Shift::with('user');

        // Filter by user_id
        if ($request->has('user_id') && $request->user_id != '') {
            $query->where('user_id', $request->user_id);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'start_time'); // Default sort by start_time
        $sortOrder = $request->get('sort_order', 'desc'); // Default sort order desc (newest first)

        // Validate sort_by column
        $allowedSortColumns = ['id', 'user_id', 'start_time', 'end_time', 'starting_cash', 'ending_cash', 'created_at', 'updated_at'];
        if (!in_array($sortBy, $allowedSortColumns)) {
            $sortBy = 'start_time'; // Fallback
        }
        $sortOrder = in_array(strtolower($sortOrder), ['asc', 'desc']) ? $sortOrder : 'desc';

        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 10); // Default 10 items per page
        $shifts = $query->paginate($perPage);

        return response()->json($shifts, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'start_time' => ['required', 'date'],
                'end_time' => ['nullable', 'date', 'after_or_equal:start_time'],
                'starting_cash' => ['required', 'numeric', 'min:0'],
                'ending_cash' => ['nullable', 'numeric', 'min:0', 'gte:starting_cash'], // Greater than or equal to starting_cash
                'notes' => ['nullable', 'string', 'max:500'],
            ]);

            // Dapatkan ID user yang sedang login
            $userId = Auth::id();

            $shift = Shift::create(array_merge($request->all(), ['user_id' => $userId]));

            return response()->json([
                'message' => 'Shift created successfully.',
                'shift' => $shift->load('user') // Muat data user untuk respons
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while creating the shift.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Shift $shift)
    {
        return response()->json($shift->load('user'), 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Shift $shift)
    {
        try {
            $request->validate([
                'start_time' => ['required', 'date'],
                'end_time' => ['nullable', 'date', 'after_or_equal:start_time'],
                'starting_cash' => ['required', 'numeric', 'min:0'],
                'ending_cash' => ['nullable', 'numeric', 'min:0', 'gte:starting_cash'],
                'notes' => ['nullable', 'string', 'max:500'],
                // user_id tidak perlu diupdate dari frontend, karena ini shift milik user tersebut
            ]);

            $shift->update($request->all());

            return response()->json([
                'message' => 'Shift updated successfully.',
                'shift' => $shift->load('user')
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while updating the shift.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Shift $shift)
    {
        try {
            $shift->delete();

            return response()->json([
                'message' => 'Shift deleted successfully.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while deleting the shift.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}