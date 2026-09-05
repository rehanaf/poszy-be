<?php

namespace App\Http\Controllers;

use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PaymentMethodController extends Controller
{
    /**
     * Display a listing of the resource with search, pagination, sorting, and filtering.
     */
    public function index(Request $request)
    {
        $query = PaymentMethod::query();

        // Search by name or type
        if ($request->has('search') && $request->search != '') {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('type', 'like', '%' . $request->search . '%');
            });
        }

        // Filter by is_active status
        if ($request->has('is_active') && in_array($request->is_active, ['0', '1'])) {
            $query->where('is_active', (bool)$request->is_active);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'name'); // Default sort by name
        $sortOrder = $request->get('sort_order', 'asc'); // Default sort order asc

        // Validate sort_by column
        $allowedSortColumns = ['id', 'name', 'type', 'created_at', 'updated_at'];
        if (!in_array($sortBy, $allowedSortColumns)) {
            $sortBy = 'name'; // Fallback
        }
        $sortOrder = in_array(strtolower($sortOrder), ['asc', 'desc']) ? $sortOrder : 'asc';

        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 10); // Default 10 items per page
        $paymentMethods = $query->paginate($perPage);

        return response()->json($paymentMethods, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => ['required', 'string', 'max:255', 'unique:payment_methods'],
                'description' => ['nullable', 'string', 'max:500'],
                'is_active' => ['boolean'],
            ]);

            $paymentMethod = PaymentMethod::create($request->all());

            return response()->json([
                'message' => 'Payment method created successfully.',
                'payment_method' => $paymentMethod
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while creating the payment method.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(PaymentMethod $paymentMethod)
    {
        return response()->json($paymentMethod, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PaymentMethod $paymentMethod)
    {
        try {
            $request->validate([
                'name' => ['required', 'string', 'max:255', 'unique:payment_methods,name,' . $paymentMethod->id],
                'description' => ['nullable', 'string', 'max:500'],
                'is_active' => ['boolean'],
            ]);

            $paymentMethod->update($request->all());

            return response()->json([
                'message' => 'Payment method updated successfully.',
                'payment_method' => $paymentMethod
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while updating the payment method.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PaymentMethod $paymentMethod)
    {
        try {
            $paymentMethod->delete();

            return response()->json([
                'message' => 'Payment method deleted successfully.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while deleting the payment method.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}