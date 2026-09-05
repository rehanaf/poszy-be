<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SupplierController extends Controller
{
    /**
     * Display a listing of the resource with search, pagination, and sorting.
     */
    public function index(Request $request)
    {
        $query = Supplier::query();

        // Search by name, email, phone, or contact_person
        if ($request->has('search') && $request->search != '') {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%')
                  ->orWhere('phone', 'like', '%' . $request->search . '%')
                  ->orWhere('contact_person', 'like', '%' . $request->search . '%');
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'name'); // Default sort by name
        $sortOrder = $request->get('sort_order', 'asc'); // Default sort order asc

        // Validate sort_by column
        $allowedSortColumns = ['id', 'name', 'email', 'phone', 'created_at', 'updated_at'];
        if (!in_array($sortBy, $allowedSortColumns)) {
            $sortBy = 'name'; // Fallback
        }
        $sortOrder = in_array(strtolower($sortOrder), ['asc', 'desc']) ? $sortOrder : 'asc';

        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 10); // Default 10 items per page
        $suppliers = $query->paginate($perPage);

        return response()->json($suppliers, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'contact_person' => ['nullable', 'string', 'max:255'],
                'email' => ['nullable', 'email', 'max:255', 'unique:suppliers'],
                'phone_number' => ['nullable', 'string', 'max:20', 'unique:suppliers'],
                'address' => ['nullable', 'string', 'max:500'],
            ]);

            $supplier = Supplier::create($request->all());

            return response()->json([
                'message' => 'Supplier created successfully.',
                'supplier' => $supplier
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while creating the supplier.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Supplier $supplier)
    {
        return response()->json($supplier, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Supplier $supplier)
    {
        try {
            $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'contact_person' => ['nullable', 'string', 'max:255'],
                'email' => ['nullable', 'email', 'max:255', 'unique:suppliers,email,' . $supplier->id],
                'phone_number' => ['nullable', 'string', 'max:20', 'unique:suppliers,phone_number,' . $supplier->id],
                'address' => ['nullable', 'string', 'max:500'],
            ]);

            $supplier->update($request->all());

            return response()->json([
                'message' => 'Supplier updated successfully.',
                'supplier' => $supplier
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while updating the supplier.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Supplier $supplier)
    {
        try {
            $supplier->delete();

            return response()->json([
                'message' => 'Supplier deleted successfully.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while deleting the supplier.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}