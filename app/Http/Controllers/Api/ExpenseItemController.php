<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExpenseItem;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ExpenseItemController extends Controller
{
    /**
     * Display a listing of expense items for authenticated user.
     * GET /api/expense-items
     */
    public function index(Request $request)
    {
        // Get all expense items for authenticated user through expenses
        $query = ExpenseItem::whereHas('expense', function ($q) {
                $q->where('user_id', auth()->id());
            })
            ->with(['expense', 'receipt', 'category'])
            ->orderBy('created_at', 'desc');

        // Optional filter by expense_id
        if ($request->has('expense_id')) {
            $query->where('expense_id', $request->expense_id);
        }

        // Optional filter by receipt_id
        if ($request->has('receipt_id')) {
            $query->where('receipt_id', $request->receipt_id);
        }

        $items = $query->get();

        return response()->json([
            'success' => true,
            'data' => $items
        ]);
    }

    /**
     * Store a newly created expense item.
     * POST /api/expense-items
     */
    public function store(Request $request)
    {
        // Validate incoming data
        $validator = Validator::make($request->all(), [
            'expense_id' => 'required|exists:expenses,id',
            'receipt_id' => 'nullable|exists:receipts,id',
            'category_id' => 'nullable|exists:categories,id',
            'item_name' => 'required|string|max:255',
            'quantity' => 'nullable|numeric|min:0.01',
            'unit_price' => 'nullable|numeric|min:0',
            'total_price' => 'required|numeric|min:0',
        ]);

        // Return errors if validation fails
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Verify the expense belongs to authenticated user
        $expense = Expense::where('user_id', auth()->id())
            ->find($request->expense_id);

        if (!$expense) {
            return response()->json([
                'success' => false,
                'message' => 'Expense not found or does not belong to you'
            ], 403);
        }

        // Create expense item
        $item = ExpenseItem::create([
            'expense_id' => $request->expense_id,
            'receipt_id' => $request->receipt_id,
            'category_id' => $request->category_id,
            'item_name' => $request->item_name,
            'quantity' => $request->quantity,
            'unit_price' => $request->unit_price,
            'total_price' => $request->total_price,
        ]);

        // Load relationships
        $item->load(['expense', 'receipt', 'category']);

        return response()->json([
            'success' => true,
            'message' => 'Expense item created successfully',
            'data' => $item
        ], 201);
    }

    /**
     * Display the specified expense item.
     * GET /api/expense-items/{id}
     */
    public function show($id)
    {
        // Find expense item with relationships
        $item = ExpenseItem::whereHas('expense', function ($q) {
                $q->where('user_id', auth()->id());
            })
            ->with(['expense', 'receipt', 'category'])
            ->find($id);

        // Return 404 if not found
        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Expense item not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $item
        ]);
    }

    /**
     * Update the specified expense item.
     * PUT /api/expense-items/{id}
     */
    public function update(Request $request, $id)
    {
        // Find expense item
        $item = ExpenseItem::whereHas('expense', function ($q) {
                $q->where('user_id', auth()->id());
            })
            ->find($id);

        // Return 404 if not found
        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Expense item not found'
            ], 404);
        }

        // Validate incoming data
        $validator = Validator::make($request->all(), [
            'receipt_id' => 'nullable|exists:receipts,id',
            'category_id' => 'nullable|exists:categories,id',
            'item_name' => 'sometimes|required|string|max:255',
            'quantity' => 'nullable|numeric|min:0.01',
            'unit_price' => 'nullable|numeric|min:0',
            'total_price' => 'sometimes|required|numeric|min:0',
        ]);

        // Return errors if validation fails
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Update expense item
        $item->update($request->only(['receipt_id', 'category_id', 'item_name', 'quantity', 'unit_price', 'total_price']));

        // Reload relationships
        $item->load(['expense', 'receipt', 'category']);

        return response()->json([
            'success' => true,
            'message' => 'Expense item updated successfully',
            'data' => $item
        ]);
    }

    /**
     * Remove the specified expense item.
     * DELETE /api/expense-items/{id}
     */
    public function destroy($id)
    {
        // Find expense item
        $item = ExpenseItem::whereHas('expense', function ($q) {
                $q->where('user_id', auth()->id());
            })
            ->find($id);

        // Return 404 if not found
        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Expense item not found'
            ], 404);
        }

        // Delete expense item
        $item->delete();

        return response()->json([
            'success' => true,
            'message' => 'Expense item deleted successfully'
        ]);
    }
}
