<?php
// routes/api.php
//
// The public POST /api/login route has been removed. It handed out a token
// that never expired to anyone with an email and password, skipping the
// emailed sign-in code, the lockout, deactivation and every permission.
//
// API access now needs a token issued deliberately by IT to an account that
// holds the "api access" permission (given to nobody by default).

use App\Http\Controllers\Api\AccountApiController;
use App\Http\Controllers\Api\CustomerApiController;
use App\Http\Controllers\Api\TransactionApiController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'can:api access', 'throttle:60,1'])->group(function () {

    // Customers
    Route::prefix('customers')->group(function () {
        Route::get('/', [CustomerApiController::class, 'index']);
        Route::get('/search', [CustomerApiController::class, 'search']);
        Route::get('/{id}', [CustomerApiController::class, 'show']);
        Route::get('/{id}/accounts', [CustomerApiController::class, 'accounts']);
        Route::get('/{id}/transactions', [CustomerApiController::class, 'transactions']);
    });

    // Accounts
    Route::prefix('accounts')->group(function () {
        Route::get('/', [AccountApiController::class, 'index']);
        Route::get('/search', [AccountApiController::class, 'search']);
        Route::get('/{accountNumber}', [AccountApiController::class, 'show']);
        Route::get('/{accountNumber}/transactions', [AccountApiController::class, 'transactions']);
        Route::get('/{accountNumber}/statement', [AccountApiController::class, 'statement']);
    });

    // Transactions
    Route::prefix('transactions')->group(function () {
        Route::get('/', [TransactionApiController::class, 'index']);
        Route::get('/{id}', [TransactionApiController::class, 'show']);
    });
});
