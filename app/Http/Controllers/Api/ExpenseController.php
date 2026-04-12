<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Category;
use App\Services\AIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ExpenseController extends Controller
{
    /**
     * Display a listing of expenses for authenticated user.
     * GET /api/expenses
     */
    public function index(Request $request)
    {
        // Get all expenses for authenticated user with category and receipt relationships
        $query = Expense::where('user_id', auth()->id())
            ->with(['category', 'receipt'])
            ->orderBy('expense_date', 'desc');

        // Optional filters
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->has('from_date')) {
            $query->where('expense_date', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $query->where('expense_date', '<=', $request->to_date);
        }

        $expenses = $query->get();

        return response()->json([
            'success' => true,
            'data' => $expenses
        ]);
    }

    /**
     * Store a newly created expense.
     * POST /api/expenses
     */
    public function store(Request $request)
    {
        // Validate incoming data
        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:categories,id',
            'amount' => 'required|numeric|min:0.01',
            'expense_date' => 'required|date',
            'payment_method' => 'required|in:cash,card,bank_transfer,digital_wallet,other',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Return errors if validation fails
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Create expense
        $expense = Expense::create([
            'user_id' => auth()->id(),
            'category_id' => $request->category_id,
            'amount' => $request->amount,
            'expense_date' => $request->expense_date,
            'payment_method' => $request->payment_method,
            'notes' => $request->notes,
        ]);

        // Load relationships
        $expense->load(['category', 'receipt']);

        return response()->json([
            'success' => true,
            'message' => 'Expense created successfully',
            'data' => $expense
        ], 201);
    }

    /**
     * Display the specified expense.
     * GET /api/expenses/{id}
     */
    public function show($id)
    {
        // Find expense with relationships
        $expense = Expense::where('user_id', auth()->id())
            ->with(['category', 'receipt', 'items'])
            ->find($id);

        // Return 404 if not found
        if (!$expense) {
            return response()->json([
                'success' => false,
                'message' => 'Expense not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $expense
        ]);
    }

    /**
     * Update the specified expense.
     * PUT /api/expenses/{id}
     */
    public function update(Request $request, $id)
    {
        // Find expense
        $expense = Expense::where('user_id', auth()->id())
            ->find($id);

        // Return 404 if not found
        if (!$expense) {
            return response()->json([
                'success' => false,
                'message' => 'Expense not found'
            ], 404);
        }

        // Validate incoming data
        $validator = Validator::make($request->all(), [
            'category_id' => 'sometimes|required|exists:categories,id',
            'amount' => 'sometimes|required|numeric|min:0.01',
            'expense_date' => 'sometimes|required|date',
            'payment_method' => 'sometimes|required|in:cash,card,bank_transfer,digital_wallet,other',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Return errors if validation fails
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Update expense
        $expense->update($request->only(['category_id', 'amount', 'expense_date', 'payment_method', 'notes']));

        // Reload relationships
        $expense->load(['category', 'receipt']);

        return response()->json([
            'success' => true,
            'message' => 'Expense updated successfully',
            'data' => $expense
        ]);
    }

    /**
     * Remove the specified expense.
     * DELETE /api/expenses/{id}
     */
    public function destroy($id)
    {
        // Find expense
        $expense = Expense::where('user_id', auth()->id())
            ->find($id);

        // Return 404 if not found
        if (!$expense) {
            return response()->json([
                'success' => false,
                'message' => 'Expense not found'
            ], 404);
        }

        // Delete expense (cascade will delete related receipt and items)
        $expense->delete();

        return response()->json([
            'success' => true,
            'message' => 'Expense deleted successfully'
        ]);
    }

    /**
     * Import expenses from CSV data.
     * POST /api/expenses/import-csv
     */
    public function importCsv(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'transactions' => 'required|array',
            'transactions.*.amount' => 'required|numeric|min:0.01',
            'transactions.*.expense_date' => 'required|date',
            'transactions.*.category_id' => 'required|exists:categories,id',
            'transactions.*.payment_method' => 'required|in:cash,card,bank_transfer,digital_wallet,other',
            'transactions.*.notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $imported = 0;
        $duplicates = 0;
        $userId = auth()->id();

        foreach ($request->transactions as $transaction) {
            // Check for potential duplicates (same amount, date, and category within 1 second)
            $exists = Expense::where('user_id', $userId)
                ->where('amount', $transaction['amount'])
                ->where('expense_date', $transaction['expense_date'])
                ->where('category_id', $transaction['category_id'])
                ->exists();

            if ($exists) {
                $duplicates++;
                continue;
            }

            // Create the expense
            Expense::create([
                'user_id' => $userId,
                'category_id' => $transaction['category_id'],
                'amount' => $transaction['amount'],
                'expense_date' => $transaction['expense_date'],
                'payment_method' => $transaction['payment_method'],
                'notes' => $transaction['notes'] ?? 'Imported from CSV',
            ]);

            $imported++;
        }

        return response()->json([
            'success' => true,
            'message' => "Import completed! Imported {$imported} transactions, skipped {$duplicates} duplicates.",
            'imported' => $imported,
            'duplicates' => $duplicates
        ]);
    }

    /**
     * AI-powered categorization of transactions.
     * POST /api/expenses/ai-categorize
     */
    public function aiCategorize(Request $request, AIService $aiService)
    {
        $validator = Validator::make($request->all(), [
            'transactions' => 'required|array',
            'transactions.*.description' => 'required|string',
            'transactions.*.amount' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Get user's categories
        $categories = Category::where('user_id', auth()->id())
            ->get()
            ->map(fn($cat) => [
                'id' => $cat->id,
                'name' => $cat->name,
                'type' => $cat->type,
                'icon' => $cat->icon
            ])
            ->toArray();

        $transactions = $request->transactions;

        // Use batch categorization for better performance
        $categoryIds = $aiService->batchCategorizeTransactions($transactions, $categories);

        // Build response with category IDs
        $results = [];
        foreach ($transactions as $index => $transaction) {
            $categoryId = $categoryIds[$index] ?? null;
            $category = collect($categories)->firstWhere('id', $categoryId);
            
            $results[] = [
                'description' => $transaction['description'],
                'category_id' => $categoryId,
                'category_name' => $category['name'] ?? null,
                'category_icon' => $category['icon'] ?? null,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $results
        ]);
    }

    /**
     * Analyze PDF bank statement using AI to extract and categorize transactions.
     * POST /api/expenses/analyze-pdf
     */
    public function analyzePDF(Request $request, AIService $aiService)
    {
        $validator = Validator::make($request->all(), [
            'pdf' => 'required|string', // base64 encoded PDF
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Get user's categories
            $categories = Category::where('user_id', auth()->id())
                ->get()
                ->map(fn($cat) => [
                    'id' => $cat->id,
                    'name' => $cat->name,
                    'type' => $cat->type,
                    'icon' => $cat->icon
                ])
                ->toArray();

            // Analyze PDF with AI
            $transactions = $aiService->analyzePDFStatement($request->pdf, $categories);

            return response()->json([
                'success' => true,
                'data' => $transactions,
                'message' => 'Successfully extracted ' . count($transactions) . ' transactions from PDF'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
