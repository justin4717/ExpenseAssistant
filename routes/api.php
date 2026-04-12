<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\ReceiptController;
use App\Http\Controllers\Api\ExpenseItemController;
use App\Http\Controllers\Api\BudgetController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Public routes (no authentication required)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes (authentication required)
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Category API routes
    Route::apiResource('categories', CategoryController::class);
    
    // Expense API routes
    Route::apiResource('expenses', ExpenseController::class);
    Route::post('/expenses/import-csv', [ExpenseController::class, 'importCsv']);
    Route::post('/expenses/ai-categorize', [ExpenseController::class, 'aiCategorize']);
    Route::post('/expenses/analyze-pdf', [ExpenseController::class, 'analyzePDF']);
    
    // Receipt API routes
    Route::apiResource('receipts', ReceiptController::class);
    
    // Expense Item API routes
    Route::apiResource('expense-items', ExpenseItemController::class);
    
    // Budget API routes
    Route::apiResource('budgets', BudgetController::class);
    Route::get('/budgets-status', [BudgetController::class, 'status']);
});