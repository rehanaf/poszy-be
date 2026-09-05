<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\Product; // Penting untuk update stok
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB; // Untuk transaksi database

class PurchaseController extends Controller
{
    /**
     * Display a listing of the resource with search, pagination, sorting, and filtering.
     */
    public function index(Request $request)
    {
        $query = Purchase::with(['supplier', 'purchaseItems.product']);

        // Search by supplier name or notes
        if ($request->has('search') && $request->search != '') {
            $query->where(function ($q) use ($request) {
                $q->whereHas('supplier', function ($sq) use ($request) {
                    $sq->where('name', 'like', '%' . $request->search . '%');
                })->orWhere('notes', 'like', '%' . $request->search . '%');
            });
        }

        // Filter by user_id
        if ($request->has('user_id') && $request->user_id != '') {
            $query->where('user_id', $request->user_id);
        }

        // Filter by supplier_id
        if ($request->has('supplier_id') && $request->supplier_id != '') {
            $query->where('supplier_id', $request->supplier_id);
        }

        // Filter by status
        if ($request->has('status') && in_array($request->status, ['pending', 'completed', 'cancelled'])) {
            $query->where('status', $request->status);
        }

        // Filter by purchase_date (specific date)
        if ($request->has('purchase_date') && $request->purchase_date != '') {
            $query->whereDate('purchase_date', $request->purchase_date);
        }
        // Filter by date range (optional)
        // if ($request->has('start_date') && $request->has('end_date')) {
        //     $query->whereBetween('purchase_date', [$request->start_date, $request->end_date]);
        // }

        // Sorting
        $sortBy = $request->get('sort_by', 'purchase_date'); // Default sort by purchase_date
        $sortOrder = $request->get('sort_order', 'desc'); // Default sort order desc (newest first)

        // Validate sort_by column
        $allowedSortColumns = ['id', 'purchase_date', 'total_amount', 'status', 'created_at', 'updated_at'];
        if (!in_array($sortBy, $allowedSortColumns)) {
            $sortBy = 'purchase_date'; // Fallback
        }
        $sortOrder = in_array(strtolower($sortOrder), ['asc', 'desc']) ? $sortOrder : 'desc';

        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 10); // Default 10 items per page
        $purchases = $query->paginate($perPage);

        return response()->json($purchases, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'supplier_id' => ['required', 'exists:suppliers,id'],
                'total_amount' => ['required', 'numeric', 'min:0'],
                'purchase_date' => ['required', 'date'],
                'notes' => ['nullable', 'string', 'max:500'],
                'items' => ['required', 'array', 'min:1'],
                'items.*.product_id' => ['required', 'exists:products,id'],
                'items.*.quantity' => ['required', 'integer', 'min:1'],
                'items.*.cost_price' => ['required', 'numeric', 'min:0'], // 'cost_price' is actual buying price
            ]);

            $user = Auth::user(); // User yang membuat pembelian

            // Create Purchase
            $purchase = Purchase::create([
                'supplier_id' => $request->supplier_id,
                'user_id' => $user->id,
                'purchase_date' => $request->purchase_date,
                'total_amount' => $request->total_amount, // Frontend akan menghitung total_amount
                'status' => 'completed', // Default completed
                'notes' => $request->notes,
            ]);

            foreach ($request->input('items') as $item) {
                $product = Product::find($item['product_id']);

                if (!$product) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Product not found for ID: ' . $item['product_id']
                    ], 404);
                }

                $itemSubtotal = $item['quantity'] * $item['cost_price'];

                $purchase->purchaseItems()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'cost_price' => $item['cost_price'],
                    'subtotal' => $itemSubtotal,
                ]);

                // Tambah stok produk (jika stok tidak null)
                if ($product->stock !== null) {
                    $product->increment('stock', $item['quantity']);
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Purchase created successfully.',
                'purchase' => $purchase->load(['supplier', 'purchaseItems.product'])
            ], 201);
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'An error occurred while creating the purchase.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Purchase $purchase)
    {
        return response()->json($purchase->load(['supplier', 'purchaseItems.product']), 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Purchase $purchase)
    {
        // Update purchase juga kompleks. Untuk kesederhanaan, hanya detail utama.
        // Perubahan item sebaiknya ditangani sebagai entri pembelian baru atau
        // koreksi stok manual jika ada kesalahan.
        DB::beginTransaction();
        try {
            $request->validate([
                'supplier_id' => ['required', 'exists:suppliers,id'],
                'total_amount' => ['required', 'numeric', 'min:0'],
                'purchase_date' => ['required', 'date'],
                'notes' => ['nullable', 'string', 'max:500'],
            ]);

            $purchase->update($request->all());

            DB::commit();
            return response()->json([
                'message' => 'Purchase updated successfully.',
                'purchase' => $purchase->load(['supplier', 'purchaseItems.product'])
            ], 200);
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'An error occurred while updating the purchase.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Purchase $purchase)
    {
        DB::beginTransaction(); // Mulai transaksi
        try {
            // Sebelum menghapus purchase, kurangi kembali stok produk
            foreach ($purchase->purchaseItems as $item) {
                $product = Product::find($item->product_id);
                if ($product && $product->stock !== null) {
                    // Pastikan stok tidak menjadi negatif jika dikurangi
                    if ($product->stock < $item->quantity) {
                        DB::rollBack();
                        return response()->json([
                            'message' => 'Cannot delete purchase: insufficient stock to revert product ' . $product->name . ' to original quantity.',
                            'current_stock' => $product->stock
                        ], 400);
                    }
                    $product->decrement('stock', $item->quantity);
                }
            }

            $purchase->delete();
            DB::commit(); // Commit transaksi

            return response()->json([
                'message' => 'Purchase deleted successfully and stock reverted.'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack(); // Rollback jika ada error
            return response()->json([
                'message' => 'An error occurred while deleting the purchase.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}