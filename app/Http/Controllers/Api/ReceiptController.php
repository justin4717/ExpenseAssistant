<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Receipt;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class ReceiptController extends Controller
{
    /**
     * Display a listing of receipts for authenticated user.
     * GET /api/receipts
     */
    public function index(Request $request)
    {
        // Get all receipts for authenticated user through expenses
        $query = Receipt::whereHas('expense', function ($q) {
                $q->where('user_id', auth()->id());
            })
            ->with(['expense', 'items'])
            ->orderBy('created_at', 'desc');

        // Optional filter by OCR status
        if ($request->has('ocr_status')) {
            $query->where('ocr_status', $request->ocr_status);
        }

        $receipts = $query->get();

        return response()->json([
            'success' => true,
            'data' => $receipts
        ]);
    }

    /**
     * Store a newly created receipt.
     * POST /api/receipts
     */
    public function store(Request $request)
    {
        // Validate incoming data
        $validator = Validator::make($request->all(), [
            'expense_id' => 'required|exists:expenses,id',
            'file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120', // 5MB max
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

        // Check if expense already has a receipt
        if ($expense->receipt) {
            return response()->json([
                'success' => false,
                'message' => 'Expense already has a receipt attached'
            ], 422);
        }

        // Store the file
        $file = $request->file('file');
        $fileName = time() . '_' . $file->getClientOriginalName();
        $filePath = $file->storeAs('receipts', $fileName, 'public');

        // Create receipt
        $receipt = Receipt::create([
            'expense_id' => $request->expense_id,
            'file_path' => $filePath,
            'ocr_status' => 'pending',
        ]);

        // Load relationships
        $receipt->load(['expense', 'items']);

        return response()->json([
            'success' => true,
            'message' => 'Receipt uploaded successfully',
            'data' => $receipt
        ], 201);
    }

    /**
     * Display the specified receipt.
     * GET /api/receipts/{id}
     */
    public function show($id)
    {
        // Find receipt with relationships
        $receipt = Receipt::whereHas('expense', function ($q) {
                $q->where('user_id', auth()->id());
            })
            ->with(['expense', 'items'])
            ->find($id);

        // Return 404 if not found
        if (!$receipt) {
            return response()->json([
                'success' => false,
                'message' => 'Receipt not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $receipt
        ]);
    }

    /**
     * Update the specified receipt (mainly OCR data).
     * PUT /api/receipts/{id}
     */
    public function update(Request $request, $id)
    {
        // Find receipt
        $receipt = Receipt::whereHas('expense', function ($q) {
                $q->where('user_id', auth()->id());
            })
            ->find($id);

        // Return 404 if not found
        if (!$receipt) {
            return response()->json([
                'success' => false,
                'message' => 'Receipt not found'
            ], 404);
        }

        // Validate incoming data
        $validator = Validator::make($request->all(), [
            'ocr_status' => 'sometimes|required|in:pending,processing,completed,failed',
            'ocr_raw_text' => 'nullable|string',
        ]);

        // Return errors if validation fails
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Update receipt
        $receipt->update($request->only(['ocr_status', 'ocr_raw_text']));

        // Reload relationships
        $receipt->load(['expense', 'items']);

        return response()->json([
            'success' => true,
            'message' => 'Receipt updated successfully',
            'data' => $receipt
        ]);
    }

    /**
     * Remove the specified receipt.
     * DELETE /api/receipts/{id}
     */
    public function destroy($id)
    {
        // Find receipt
        $receipt = Receipt::whereHas('expense', function ($q) {
                $q->where('user_id', auth()->id());
            })
            ->find($id);

        // Return 404 if not found
        if (!$receipt) {
            return response()->json([
                'success' => false,
                'message' => 'Receipt not found'
            ], 404);
        }

        // Delete file from storage
        if (Storage::disk('public')->exists($receipt->file_path)) {
            Storage::disk('public')->delete($receipt->file_path);
        }

        // Delete receipt (cascade will delete related items)
        $receipt->delete();

        return response()->json([
            'success' => true,
            'message' => 'Receipt deleted successfully'
        ]);
    }
}
