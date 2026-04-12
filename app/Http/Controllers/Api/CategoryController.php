<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     * GET /api/categories
     */
    public function index()
    {
        // Get all categories for authenticated user
        $categories = Category::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    /**
     * Store a newly created resource in storage.
     * POST /api/categories
     */
    public function store(Request $request)
    {
        // Validate incoming data
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'type' => 'required|in:expense,income',
            'color' => 'nullable|regex:/^#[0-9A-Fa-f]{6}$/',
            'icon' => 'nullable|string|max:255',
        ]);

        // Return errors if validation fails
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Create category
        $category = Category::create([
            'user_id' => auth()->id(),
            'name' => $request->name,
            'type' => $request->type,
            'color' => $request->color,
            'icon' => $request->icon,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Category created successfully',
            'data' => $category
        ], 201);
    }

    /**
     * Display the specified resource.
     * GET /api/categories/{id}
     */
    public function show($id)
    {
        // Find category
        $category = Category::where('user_id', auth()->id())
            ->find($id);

        // Return 404 if not found
        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $category
        ]);
    }

    /**
     * Update the specified resource in storage.
     * PUT /api/categories/{id}
     */
    public function update(Request $request, $id)
    {
        // Find category
        $category = Category::where('user_id', auth()->id())
            ->find($id);

        // Return 404 if not found
        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found'
            ], 404);
        }

        // Validate incoming data
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'type' => 'sometimes|required|in:expense,income',
            'color' => 'nullable|regex:/^#[0-9A-Fa-f]{6}$/',
            'icon' => 'nullable|string|max:255',
        ]);

        // Return errors if validation fails
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Update category
        $category->update($request->only(['name', 'type', 'color', 'icon']));

        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully',
            'data' => $category
        ]);
    }

    /**
     * Remove the specified resource from storage.
     * DELETE /api/categories/{id}
     */
    public function destroy($id)
    {
        // Find category
        $category = Category::where('user_id', auth()->id())
            ->find($id);

        // Return 404 if not found
        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found'
            ], 404);
        }

        // Delete category
        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully'
        ]);
    }
}