<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth; // Untuk mendapatkan user yang sedang login

class ExpenseController extends Controller
{
    /**
     * Display a listing of the resource with search, pagination, sorting, and filtering.
     */
    public function index(Request $request)
    {
        $query = Expense::with('user');

        // Search by description or category
        if ($request->has('search') && $request->search != '') {
            $query->where(function ($q) use ($request) {
                $q->where('description', 'like', '%' . $request->search . '%')
                  ->orWhere('category', 'like', '%' . $request->search . '%');
            });
        }

        // Filter by user_id
        if ($request->has('user_id') && $request->user_id != '') {
            $query->where('user_id', $request->user_id);
        }

        // Filter by expense_date (specific date)
        if ($request->has('expense_date') && $request->expense_date != '') {
            $query->whereDate('expense_date', $request->expense_date);
        }
        // Filter by date range (optional, can be added with start_date and end_date)
        // if ($request->has('start_date') && $request->has('end_date')) {
        //     $query->whereBetween('expense_date', [$request->start_date, $request->end_date]);
        // }


        // Sorting
        $sortBy = $request->get('sort_by', 'expense_date'); // Default sort by expense_date
        $sortOrder = $request->get('sort_order', 'desc'); // Default sort order desc (newest first)

        // Validate sort_by column
        $allowedSortColumns = ['id', 'user_id', 'amount', 'expense_date', 'created_at', 'updated_at'];
        if (!in_array($sortBy, $allowedSortColumns)) {
            $sortBy = 'expense_date'; // Fallback
        }
        $sortOrder = in_array(strtolower($sortOrder), ['asc', 'desc']) ? $sortOrder : 'desc';

        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 10); // Default 10 items per page
        $expenses = $query->paginate($perPage);

        return response()->json($expenses, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'amount' => ['required', 'numeric', 'min:0'],
                'description' => ['required', 'string', 'max:500'],
                'expense_date' => ['required', 'date'],
            ]);

            // Dapatkan ID user yang sedang login
            $userId = Auth::id();

            $expense = Expense::create(array_merge($request->all(), ['user_id' => $userId]));

            return response()->json([
                'message' => 'Expense created successfully.',
                'expense' => $expense->load('user')
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while creating the expense.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Expense $expense)
    {
        return response()->json($expense->load('user'), 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Expense $expense)
    {
        try {
            $request->validate([
                'amount' => ['required', 'numeric', 'min:0'],
                'description' => ['required', 'string', 'max:500'],
                'expense_date' => ['required', 'date'],
                // user_id tidak perlu diupdate
            ]);

            $expense->update($request->all());

            return response()->json([
                'message' => 'Expense updated successfully.',
                'expense' => $expense->load('user')
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while updating the expense.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Expense $expense)
    {
        try {
            $expense->delete();

            return response()->json([
                'message' => 'Expense deleted successfully.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while deleting the expense.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}