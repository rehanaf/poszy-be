<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CustomerController extends Controller
{
    /**
     * Pesan validasi khusus dalam Bahasa Indonesia supaya jelas bagi kasir/owner.
     */
    private function validationMessages(): array
    {
        return [
            'name.required' => 'Nama pelanggan wajib diisi.',
            'name.min' => 'Nama pelanggan minimal :min karakter.',
            'name.max' => 'Nama pelanggan maksimal :max karakter.',
            'email.email' => 'Format email tidak valid.',
            'email.max' => 'Email maksimal :max karakter.',
            'email.unique' => 'Email sudah digunakan pelanggan lain. Gunakan email lain atau kosongkan.',
            'phone.max' => 'Nomor telepon maksimal :max karakter.',
            'phone.unique' => 'Nomor telepon sudah digunakan pelanggan lain. Gunakan nomor lain atau kosongkan.',
            'address.max' => 'Alamat maksimal :max karakter.',
            'customer_type.max' => 'Jenis pelanggan maksimal :max karakter.',
            'customer_code.regex' => 'Format ID Cust harus seperti CR00001-2026.',
            'customer_code.max' => 'ID Cust maksimal :max karakter.',
            'customer_code.unique' => 'ID Cust sudah digunakan pelanggan lain.',
        ];
    }
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
                'customer_type' => ['nullable', 'string', 'max:50'],
                'customer_code' => ['nullable', 'string', 'max:30', 'regex:/^CR\d{5,}-\d{4}$/', 'unique:customers'],
            ], $this->validationMessages());

            // Jika customer_code tidak dikirim, generate otomatis
            $customerCode = $request->input('customer_code')
                ?: $this->generateNextCode();

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
     * Return the next auto-generated customer code (untuk isian default di form).
     */
    public function nextCode()
    {
        return response()->json([
            'customer_code' => $this->generateNextCode(),
        ], 200);
    }

    /**
     * Generate kode customer otomatis: CR00001-2026, CR00002-2026, ...
     * (urutan global, tahun = tahun daftar)
     */
    private function generateNextCode(): string
    {
        $lastCode = Customer::orderBy('id', 'desc')->value('customer_code');
        $lastNumber = 0;
        if ($lastCode) {
            $digits = ltrim((string) preg_replace('/[^0-9]/', '', explode('-', $lastCode)[0]), '0');
            $lastNumber = ($digits === '') ? 0 : (int) $digits;
        }
        $next = $lastNumber + 1;
        $pad = max(5, strlen((string) $next));
        return 'CR' . str_pad((string) $next, $pad, '0', STR_PAD_LEFT) . '-' . now()->year;
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
                'customer_type' => ['nullable', 'string', 'max:50'],
                'customer_code' => ['nullable', 'string', 'max:30', 'regex:/^CR\d{5,}-\d{4}$/', 'unique:customers,customer_code,' . $customer->id], // Unik kecuali untuk dirinya sendiri
            ], $this->validationMessages());

            $data = $request->all();
            // Jika customer_code dikosongkan, pertahankan kode lama
            if (empty($data['customer_code'])) {
                unset($data['customer_code']);
            }

            // Memperbarui pelanggan
            $customer->update($data);

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