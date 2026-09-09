<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource with search, pagination, and sorting.
     */
    public function index(Request $request)
    {
        $query = Customer::query();

        // Search by name, email, or phone
        if ($request->has('search') && $request->search != '') {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%')
                  ->orWhere('phone', 'like', '%' . $request->search . '%');
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'name'); // Default sort by name
        $sortOrder = $request->get('sort_order', 'asc'); // Default sort order asc

        // Validate sort_by column
        $allowedSortColumns = ['id', 'customer_code', 'name', 'email', 'phone', 'customer_type', 'created_at', 'updated_at'];
        if (!in_array($sortBy, $allowedSortColumns)) {
            $sortBy = 'name'; // Fallback
        }
        $sortOrder = in_array(strtolower($sortOrder), ['asc', 'desc']) ? $sortOrder : 'asc';

        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 10); // Default 10 items per page
        $customers = $query->paginate($perPage);

        return response()->json($customers, 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            // Memvalidasi input request
            $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['nullable', 'email', 'max:255', 'unique:customers'],
                'phone' => ['nullable', 'string', 'max:50', 'unique:customers'],
                'address' => ['nullable', 'string', 'max:500'],
                'customer_type' => ['nullable', 'string', 'in:Keluarga / Karyawan,Komunitas,Member Gold,Member Silver,Umum,Grab,Gojek'],
            ]);

            // Generate kode customer otomatis: CR00001-2026, CR00002-2026, ... (urutan global, tahun = tahun daftar)
            $lastCode = Customer::orderBy('id', 'desc')->value('customer_code');
            $lastNumber = 0;
            if ($lastCode) {
                $numberPart = explode('-', $lastCode)[0]; // contoh: CR00019
                $lastNumber = (int) substr($numberPart, 2); // contoh: 19
            }
            $customerCode = 'CR' . str_pad((string) ($lastNumber + 1), 5, '0', STR_PAD_LEFT) . '-' . now()->year;

            // Membuat pelanggan baru
            $customer = Customer::create(array_merge($request->except('customer_code'), [
                'customer_code' => $customerCode,
            ]));

            return response()->json([
                'message' => 'Customer created successfully.',
                'customer' => $customer
            ], 201); // 201 Created
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422); // 422 Unprocessable Entity
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while creating the customer.',
                'error' => $e->getMessage(),
            ], 500); // 500 Internal Server Error
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Customer  $customer
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Customer $customer)
    {
        // Mengembalikan pelanggan yang ditemukan
        return response()->json($customer, 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Customer  $customer
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Customer $customer)
    {
        try {
            // Memvalidasi input request
            $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['nullable', 'email', 'max:255', 'unique:customers,email,' . $customer->id], // Email unik kecuali untuk ID-nya sendiri
                'phone' => ['nullable', 'string', 'max:50', 'unique:customers,phone,' . $customer->id], // Phone unik kecuali untuk ID-nya sendiri
                'address' => ['nullable', 'string', 'max:500'],
                'customer_type' => ['nullable', 'string', 'in:Keluarga / Karyawan,Komunitas,Member Gold,Member Silver,Umum,Grab,Gojek'],
            ]);

            // Memperbarui pelanggan
            $customer->update($request->all());

            return response()->json([
                'message' => 'Customer updated successfully.',
                'customer' => $customer
            ], 200); // 200 OK
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while updating the customer.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Customer  $customer
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Customer $customer)
    {
        try {
            // Menghapus pelanggan
            $customer->delete();

            return response()->json([
                'message' => 'Customer deleted successfully.'
            ], 200); // 200 OK
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while deleting the customer.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}