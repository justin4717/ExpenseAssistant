<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Budget;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BudgetController extends Controller
{
    public function index()
    {
        $budgets = Auth::user()->budgets()
            ->with('category')
            ->where('is_active', true)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $budgets
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'nullable|exists:categories,id',
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'period' => 'required|in:daily,weekly,monthly,yearly',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
        ]);

        $budget = Auth::user()->budgets()->create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Budget created successfully',
            'data' => $budget->load('category')
        ], 201);
    }

    public function show($id)
    {
        $budget = Auth::user()->budgets()
            ->with('category')
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $budget
        ]);
    }

    public function update(Request $request, $id)
    {
        $budget = Auth::user()->budgets()->findOrFail($id);

        $validated = $request->validate([
            'category_id' => 'nullable|exists:categories,id',
            'name' => 'string|max:255',
            'amount' => 'numeric|min:0',
            'period' => 'in:daily,weekly,monthly,yearly',
            'start_date' => 'date',
            'end_date' => 'nullable|date|after:start_date',
            'is_active' => 'boolean'
        ]);

        $budget->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Budget updated successfully',
            'data' => $budget->load('category')
        ]);
    }

    public function destroy($id)
    {
        $budget = Auth::user()->budgets()->findOrFail($id);
        $budget->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Budget deleted successfully'
        ]);
    }

    public function status()
    {
        $budgets = Auth::user()->budgets()
            ->with('category')
            ->where('is_active', true)
            ->get();

        $budgetStatus = $budgets->map(function ($budget) {
            $spent = $this->calculateSpent($budget);
            $percentage = $budget->amount > 0 ? ($spent / $budget->amount) * 100 : 0;

            return [
                'id' => $budget->id,
                'name' => $budget->name,
                'category' => $budget->category,
                'amount' => $budget->amount,
                'spent' => $spent,
                'remaining' => $budget->amount - $spent,
                'percentage' => round($percentage, 2),
                'period' => $budget->period,
                'status' => $this->getBudgetStatus($percentage)
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $budgetStatus
        ]);
    }

    private function calculateSpent($budget)
    {
        $query = Auth::user()->expenses()
            ->whereBetween('expense_date', [$budget->start_date, $budget->end_date ?? now()]);

        if ($budget->category_id) {
            $query->where('category_id', $budget->category_id);
        }

        // Only count expenses, not income
        $query->whereHas('category', function ($q) {
            $q->where('type', 'expense');
        });

        return $query->sum('amount');
    }

    private function getBudgetStatus($percentage)
    {
        if ($percentage >= 100) {
            return 'exceeded';
        } elseif ($percentage >= 80) {
            return 'warning';
        } else {
            return 'good';
        }
    }
}
