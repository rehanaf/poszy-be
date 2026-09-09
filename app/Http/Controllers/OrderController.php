<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product; // Penting untuk update stok
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; // Untuk transaksi database

class OrderController extends Controller
{
    /**
     * Display a listing of the resource with search, pagination, sorting, and filtering.
     */
    public function index(Request $request)
    {
        $query = Order::with(['customer', 'paymentMethod', 'orderItems.product']);

        // Search by customer_name or cashier_name
        if ($request->has('search') && $request->search != '') {
            $query->where(function ($q) use ($request) {
                $q->where('customer_name', 'like', '%' . $request->search . '%')
                  ->orWhere('cashier_name', 'like', '%' . $request->search . '%');
            });
        }

        // Filter by user_id (cashier)
        if ($request->has('user_id') && $request->user_id != '') {
            $query->where('user_id', $request->user_id);
        }

        // Filter by customer_id
        if ($request->has('customer_id') && $request->customer_id != '') {
            $query->where('customer_id', $request->customer_id);
        }

        // Filter by payment_method_id
        if ($request->has('payment_method_id') && $request->payment_method_id != '') {
            $query->where('payment_method_id', $request->payment_method_id);
        }

        // Filter by payment_status
        if ($request->has('payment_status') && in_array($request->payment_status, ['pending', 'paid', 'refunded'])) {
            $query->where('payment_status', $request->payment_status);
        }

        // Filter by order_status
        if ($request->has('status') && in_array($request->status, ['completed', 'pending', 'cancelled'])) {
            $query->where('status', $request->status);
        }

        // Filter by order_date (specific date)
        if ($request->has('order_date') && $request->order_date != '') {
            $query->whereDate('order_date', $request->order_date);
        }
        // Filter by date range (optional)
        // if ($request->has('start_date') && $request->has('end_date')) {
        //     $query->whereBetween('order_date', [$request->start_date, $request->end_date]);
        // }

        // Sorting
        $sortBy = $request->get('sort_by', 'order_date'); // Default sort by order_date
        $sortOrder = $request->get('sort_order', 'desc'); // Default sort order desc (newest first)

        // Validate sort_by column
        $allowedSortColumns = ['id', 'order_date', 'total_amount', 'payment_status', 'status', 'created_at', 'updated_at'];
        if (!in_array($sortBy, $allowedSortColumns)) {
            $sortBy = 'order_date'; // Fallback
        }
        $sortOrder = in_array(strtolower($sortOrder), ['asc', 'desc']) ? $sortOrder : 'desc';

        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 10); // Default 10 items per page
        $orders = $query->paginate($perPage);

        return response()->json($orders, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'customer_id' => ['nullable', 'exists:customers,id'],
                'customer_name' => ['nullable', 'string', 'max:255'],
                'payment_method_id' => ['required', 'exists:payment_methods,id'],
                'total_amount' => ['required', 'numeric', 'min:0'],
                'payment_status' => ['required', 'string', 'in:pending,paid,refunded'],
                'order_date' => ['nullable', 'date'],
                'notes' => ['nullable', 'string', 'max:500'],
                'items' => ['required', 'array', 'min:1'],
                'items.*.product_id' => ['nullable', 'exists:products,id'], // Nullable for temporary products
                'items.*.product_variant_id' => ['nullable', 'exists:product_variants,id'],
                'items.*.variant_name' => ['nullable', 'string', 'max:255'],
                'items.*.product_name' => ['required_without:items.*.product_id', 'string', 'max:255'], // Required if product_id is null
                'items.*.quantity' => ['required', 'integer', 'min:1'],
                'items.*.price' => ['required', 'numeric', 'min:0'], // 'price' is actual selling price
                'items.*.discount' => ['nullable', 'numeric', 'min:0', 'max:100'], // Discount per item
            ]);

            $user = Auth::user(); // Kasir yang membuat order
            $cashierName = $user->name;

            // Pastikan customer_name terisi, baik dari customer_id atau input manual
            $customerName = $request->input('customer_name');
            if ($request->filled('customer_id')) {
                $customer = \App\Models\Customer::find($request->customer_id);
                if ($customer) {
                    $customerName = $customer->name;
                }
            }


            // Create Order
            $order = Order::create([
                'user_id' => $user->id,
                'customer_id' => $request->customer_id,
                'customer_name' => $customerName, // Simpan nama customer di order
                'order_date' => $request->input('order_date') ?? now(),
                'total_amount' => $request->total_amount, // Frontend akan menghitung total_amount
                'payment_method_id' => $request->payment_method_id,
                'payment_status' => $request->payment_status,
                'discount_amount' => $request->discount_amount ?? 0.00, // Total diskon transaksi
                'tax_amount' => $request->tax_amount ?? 0.00, // Total pajak transaksi
                'status' => 'completed', // Default completed for successful sale
                'cashier_name' => $cashierName, // Simpan nama kasir
                'notes' => $request->notes,
            ]);

            foreach ($request->input('items') as $item) {
                $product = null;
                $variant = null;

                // Validasi: jika product_variant_id dikirim, wajib masih milik product_id tsb
                if (!empty($item['product_variant_id'])) {
                    $variant = \App\Models\ProductVariant::find($item['product_variant_id']);
                    if (!$variant || ($item['product_id'] && $variant->product_id != $item['product_id'])) {
                        DB::rollBack();
                        return response()->json([
                            'message' => 'Invalid product variant for item: ' . ($item['product_name'] ?? 'unknown')
                        ], 400);
                    }
                }

                // If product_id exists, fetch product data
                if ($item['product_id']) {
                    $product = Product::find($item['product_id']);
                    if (!$product) {
                        DB::rollBack();
                        return response()->json([
                            'message' => 'Product not found for ID: ' . $item['product_id']
                        ], 404);
                    }
                    // Cek stok (per varian jika ada varian, jika tidak cek stok produk)
                    if ($variant) {
                        if ($variant->stock !== null && $variant->stock < $item['quantity']) {
                            DB::rollBack();
                            return response()->json([
                                'message' => 'Not enough stock for variant: ' . $variant->name . ' (' . $product->name . ')',
                                'available_stock' => $variant->stock
                            ], 400);
                        }
                    } elseif ($product->stock !== null && $product->stock < $item['quantity']) {
                        DB::rollBack();
                        return response()->json([
                            'message' => 'Not enough stock for product: ' . $product->name,
                            'available_stock' => $product->stock
                        ], 400);
                    }
                }

                // Calculate subtotal for order item
                $itemPrice = $item['price'];
                $itemDiscount = $item['discount'] ?? 0;
                $itemSubtotal = $item['quantity'] * $itemPrice * (1 - ($itemDiscount / 100));

                $order->orderItems()->create([
                    'product_id' => $item['product_id'],
                    'product_variant_id' => $variant?->id,
                    'product_name' => $item['product_name'] ?? ($product ? $product->name : 'Temporary Product'), // Nama produk tetap terisi
                    'variant_name' => $item['variant_name'] ?? $variant?->name,
                    'quantity' => $item['quantity'],
                    'price' => $itemPrice,
                    'discount' => $itemDiscount,
                    'subtotal' => $itemSubtotal,
                ]);

                // Kurangi stok (varian jika ada, produk jika tidak)
                if ($variant) {
                    if ($variant->stock !== null) {
                        $variant->decrement('stock', $item['quantity']);
                    }
                } elseif ($product && $product->stock !== null) {
                    $product->decrement('stock', $item['quantity']);
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Order created successfully.',
                'order' => $order->load(['customer', 'paymentMethod', 'orderItems.product'])
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
                'message' => 'An error occurred while creating the order.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Order $order)
    {
        return response()->json($order->load(['customer', 'paymentMethod', 'orderItems.product']), 200);
    }

    /**
     * Render the receipt HTML (open in new tab; use browser print / save-as-PDF).
     */
    public function pdf(Order $order)
    {
        return view('receipt', [
            'order' => $order->load(['customer', 'paymentMethod', 'orderItems.product']),
        ]);
    }

    /**
     * Render the thermal-print receipt (same layout, print-optimized).
     */
    public function print(Order $order)
    {
        return view('receipt', [
            'order' => $order->load(['customer', 'paymentMethod', 'orderItems.product']),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Order $order)
    {
        // Update order sangat kompleks karena melibatkan perubahan stok,
        // penambahan/pengurangan item, dll.
        // Untuk kesederhanaan awal, kita hanya akan mengizinkan update pada detail order utama
        // dan status pembayaran. Perubahan item sebaiknya dilakukan via endpoint terpisah
        // atau melalui alur pembatalan/pengembalian dana jika ini adalah POS sungguhan.
        // Jika Anda ingin mengizinkan update item, ini akan memerlukan logika yang lebih rumit
        // untuk menghitung ulang stok dan sub_total.

        DB::beginTransaction();
        try {
            $request->validate([
                'customer_id' => ['nullable', 'exists:customers,id'],
                'payment_method_id' => ['required', 'exists:payment_methods,id'],
                'total_amount' => ['required', 'numeric', 'min:0'],
                'payment_status' => ['required', 'string', 'in:pending,paid,cancelled,refunded'],
                'order_date' => ['required', 'date'],
                'notes' => ['nullable', 'string', 'max:500'],
            ]);

            // Update order utama
            $order->update($request->all());

            // Catatan: Jika Anda ingin mengizinkan perubahan item pada update,
            // Anda harus:
            // 1. Kembalikan stok produk lama ke stok asli.
            // 2. Hapus semua order_items lama.
            // 3. Buat order_items baru dan sesuaikan stok lagi.
            // Ini bisa sangat rawan kesalahan. Untuk kebanyakan POS,
            // order yang sudah jadi biasanya tidak di-update item-nya,
            // melainkan dibuat order baru atau proses refund/cancellation.

            DB::commit();

            return response()->json([
                'message' => 'Order updated successfully.',
                'order' => $order->load(['customer', 'paymentMethod', 'orderItems.product'])
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
                'message' => 'An error occurred while updating the order.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Order $order)
    {
        DB::beginTransaction(); // Mulai transaksi
        try {
            // Sebelum menghapus order, kembalikan stok produk/varian
            foreach ($order->orderItems as $item) {
                $product = Product::find($item->product_id);
                if ($item->product_variant_id) {
                    $variant = \App\Models\ProductVariant::find($item->product_variant_id);
                    if ($variant && $variant->stock !== null) {
                        $variant->increment('stock', $item->quantity);
                    }
                } elseif ($product && $product->stock !== null) { // Hanya jika stok tidak null
                    $product->increment('stock', $item->quantity);
                }
            }

            $order->delete();
            DB::commit(); // Commit transaksi

            return response()->json([
                'message' => 'Order deleted successfully and stock returned.'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack(); // Rollback jika ada error
            return response()->json([
                'message' => 'An error occurred while deleting the order.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}