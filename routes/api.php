<?php

use App\Http\Controllers\Api\CustomerApiController;
use App\Http\Controllers\Api\AccountApiController;
use App\Http\Controllers\Api\TransactionApiController;
use Illuminate\Support\Facades\Route;

// Public routes (if needed)
Route::post('/login', [CustomerApiController::class, 'login']);

// Protected API routes
Route::middleware(['auth:sanctum'])->group(function () {
    
    // Customers
    Route::prefix('customers')->group(function () {
        Route::get('/', [CustomerApiController::class, 'index']); // List all
        Route::get('/search', [CustomerApiController::class, 'search']); // Search
        Route::get('/{id}', [CustomerApiController::class, 'show']); // Single customer
        Route::get('/{id}/accounts', [CustomerApiController::class, 'accounts']); // Customer's accounts
        Route::get('/{id}/transactions', [CustomerApiController::class, 'transactions']); // All transactions
    });
    
    // Accounts
    Route::prefix('accounts')->group(function () {
        Route::get('/', [AccountApiController::class, 'index']); // List all
        Route::get('/search', [AccountApiController::class, 'search']); // Search
        Route::get('/{accountNumber}', [AccountApiController::class, 'show']); // Single account
        Route::get('/{accountNumber}/transactions', [AccountApiController::class, 'transactions']); // Account transactions
        Route::get('/{accountNumber}/statement', [AccountApiController::class, 'statement']); // Statement
    });
    
    // Transactions
    Route::prefix('transactions')->group(function () {
        Route::get('/', [TransactionApiController::class, 'index']); // List all
        Route::get('/{id}', [TransactionApiController::class, 'show']); // Single transaction
    });
});