<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Storage; // Import Storage Facade

class ProductController extends Controller
{
    /**
     * Display a listing of the resource with search, pagination, sorting, and filtering.
     */
    public function index(Request $request)
    {
        $query = Product::with('category'); // Always eager load category

        // Search by name or SKU
        if ($request->has('search') && $request->search != '') {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('sku', 'like', '%' . $request->search . '%');
            });
        }

        // Filter by category_ids
        if ($request->filled('category_ids') && is_array($request->category_ids)) {
            $query->whereIn('category_id', $request->category_ids);
        }

        // Filter by is_active status
        if ($request->has('is_active') && in_array($request->is_active, ['0', '1'])) {
            $query->where('is_active', (bool)$request->is_active);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'name'); // Default sort by name
        $sortOrder = $request->get('sort_order', 'asc'); // Default sort order asc

        // Validate sort_by column
        $allowedSortColumns = ['id', 'name', 'price', 'stock', 'created_at', 'updated_at'];
        if (!in_array($sortBy, $allowedSortColumns)) {
            $sortBy = 'name'; // Fallback
        }
        $sortOrder = in_array(strtolower($sortOrder), ['asc', 'desc']) ? $sortOrder : 'asc';

        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 10); // Default 10 items per page
        $products = $query->paginate($perPage);

        return response()->json($products, 200);
    }

    /**
     * Return all active products (no pagination) for POS/order screens.
     */
    public function all()
    {
        $products = Product::with('category')->where('is_active', true)->get();
        return response()->json($products, 200);
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
                'category_id' => ['nullable', 'exists:categories,id'], // Harus ada di tabel categories
                'name' => ['required', 'string', 'max:255'],
                'sku' => ['nullable', 'string', 'max:255', 'unique:products'],
                'description' => ['nullable', 'string'],
                'price' => ['required', 'numeric', 'min:0'],
                'stock' => ['nullable', 'integer', 'min:0'], // NULL indicates unlimited stock
                'unit' => ['nullable', 'string', 'max:50'],
                'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048'], // Validasi untuk file gambar (max 2MB)
                'is_active' => ['boolean'],
                'discount' => ['numeric', 'min:0', 'max:100'],
            ]);

            // Ambil semua data dari request kecuali 'image'
            $data = $request->except('image');

            // Tangani upload gambar jika ada
            if ($request->hasFile('image')) {
                // Simpan gambar ke disk 'public' di dalam folder 'products'
                // Nama file akan di-generate secara unik oleh Laravel
                $path = $request->file('image')->store('products', 'public');
                // Simpan URL publik file ke database
                $data['image_url'] = Storage::url($path);
            } else {
                // Pastikan image_url null jika tidak ada gambar diupload
                $data['image_url'] = null;
            }

            // Buat produk baru di database
            $product = Product::create($data);

            return response()->json([
                'message' => 'Product created successfully.',
                'product' => $product
            ], 201); // 201 Created
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422); // 422 Unprocessable Entity
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while creating the product.',
                'error' => $e->getMessage(),
            ], 500); // 500 Internal Server Error
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Product $product)
    {
        // Mengembalikan produk yang ditemukan dengan relasi kategori dimuat
        return response()->json($product->load('category'), 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Product $product)
    {
        try {
            // Memvalidasi input request
            $request->validate([
                'category_id' => ['nullable', 'exists:categories,id'],
                'name' => ['required', 'string', 'max:255'],
                'sku' => ['nullable', 'string', 'max:255', 'unique:products,sku,' . $product->id], // SKU unik kecuali untuk dirinya sendiri
                'description' => ['nullable', 'string'],
                'price' => ['required', 'numeric', 'min:0'],
                'stock' => ['nullable', 'integer', 'min:0'],
                'unit' => ['nullable', 'string', 'max:50'],
                'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048'], // Validasi untuk file gambar
                'is_active' => ['boolean'],
                'discount' => ['numeric', 'min:0', 'max:100'],
                'remove_image' => ['boolean'], // Field opsional untuk menghapus gambar tanpa upload baru
            ]);

            // Ambil semua data dari request kecuali 'image' dan 'remove_image'
            $data = $request->except(['image', 'remove_image']);

            if ($request->hasFile('image')) {
                // Hapus gambar lama jika ada
                if ($product->image_url) {
                    // Dapatkan path relatif dari URL penuh
                    $oldPath = str_replace(Storage::url(''), '', $product->image_url);
                    Storage::disk('public')->delete($oldPath);
                }
                // Simpan gambar baru
                $path = $request->file('image')->store('products', 'public');
                $data['image_url'] = Storage::url($path);
            } elseif ($request->boolean('remove_image')) {
                // Jika frontend mengirim 'remove_image' true, hapus gambar yang ada
                if ($product->image_url) {
                    $oldPath = str_replace(Storage::url(''), '', $product->image_url);
                    Storage::disk('public')->delete($oldPath);
                }
                $data['image_url'] = null; // Set image_url menjadi null
            }


            // Perbarui produk di database
            $product->update($data);

            return response()->json([
                'message' => 'Product updated successfully.',
                'product' => $product
            ], 200); // 200 OK
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while updating the product.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Product $product)
    {
        try {
            // Hapus gambar terkait dari storage jika ada
            if ($product->image_url) {
                // Dapatkan path relatif dari URL penuh
                $path = str_replace(Storage::url(''), '', $product->image_url);
                Storage::disk('public')->delete($path);
            }

            // Hapus produk dari database
            $product->delete();

            return response()->json([
                'message' => 'Product deleted successfully.'
            ], 200); // 200 OK
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while deleting the product.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}