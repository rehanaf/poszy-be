<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource with search, pagination, and sorting.
     */
    public function index(Request $request)
    {
        $query = Category::query();

        // Search by name
        if ($request->has('search') && $request->search != '') {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'name'); // Default sort by name
        $sortOrder = $request->get('sort_order', 'asc'); // Default sort order asc

        // Validate sort_by column
        $allowedSortColumns = ['id', 'name', 'created_at', 'updated_at'];
        if (!in_array($sortBy, $allowedSortColumns)) {
            $sortBy = 'name'; // Fallback to default
        }
        $sortOrder = in_array(strtolower($sortOrder), ['asc', 'desc']) ? $sortOrder : 'asc';

        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 10); // Default 10 items per page
        $categories = $query->paginate($perPage);

        return response()->json($categories, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            // Memvalidasi input request
            $request->validate([
                'name' => ['required', 'string', 'max:255', 'unique:categories'],
                'description' => ['nullable', 'string'],
            ]);

            // Membuat kategori baru
            $category = Category::create($request->all());

            return response()->json([
                'message' => 'Category created successfully.',
                'category' => $category
            ], 201); // 201 Created
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422); // 422 Unprocessable Entity
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while creating the category.',
                'error' => $e->getMessage(),
            ], 500); // 500 Internal Server Error
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Category $category)
    {
        // Mengembalikan kategori yang ditemukan
        return response()->json($category, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Category $category)
    {
        try {
            // Memvalidasi input request
            $request->validate([
                'name' => ['required', 'string', 'max:255', 'unique:categories,name,' . $category->id], // Unique except for its own ID
                'description' => ['nullable', 'string'],
            ]);

            // Memperbarui kategori
            $category->update($request->all());

            return response()->json([
                'message' => 'Category updated successfully.',
                'category' => $category
            ], 200); // 200 OK
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while updating the category.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category)
    {
        try {
            // Menghapus kategori
            $category->delete();

            return response()->json([
                'message' => 'Category deleted successfully.'
            ], 200); // 200 OK
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while deleting the category.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
