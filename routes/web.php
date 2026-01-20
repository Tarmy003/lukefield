<?php
// routes/web.php

use App\Http\Controllers\Admin\AssignRoleController;
use App\Http\Controllers\Admin\RolePermissionController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ArchiveRepositoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerImportController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Guest routes (not authenticated)
Route::middleware('guest')->group(function () {
    Route::get('/', function () {
        return view('welcome');
    })->name('home');
    Route::get('/login', [TwoFactorController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [TwoFactorController::class, 'login'])->name('login.post');
    Route::get('/2fa/verify', [TwoFactorController::class, 'showVerifyForm'])->name('2fa.verify.form');
    Route::post('/2fa/verify', [TwoFactorController::class, 'verify'])->name('2fa.verify');
    Route::post('/2fa/resend', [TwoFactorController::class, 'resend'])->name('2fa.resend');
});

// Authenticated routes
Route::middleware(['auth', 'account.active', 'account.locked'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Archive Repository Routes
    Route::prefix('archive')->name('archive.')->group(function () {
        Route::get('/', [ArchiveRepositoryController::class, 'index'])->name('index');
        
        // Account imports
        Route::get('/import/accounts', [ArchiveRepositoryController::class, 'showAccountImport'])->name('import.accounts');
        Route::post('/import/accounts', [ArchiveRepositoryController::class, 'importAccounts'])->name('import.accounts.post');
        
        // Transaction imports
        Route::get('/import/transactions', [ArchiveRepositoryController::class, 'showTransactionImport'])->name('import.transactions');
        Route::post('/import/transactions', [ArchiveRepositoryController::class, 'importTransactions'])->name('import.transactions.post');
        
        // Statement generation
        Route::get('/statement/generate', [ArchiveRepositoryController::class, 'showStatementForm'])->name('statement.form');
        Route::post('/statement/preview', [ArchiveRepositoryController::class, 'previewStatement'])->name('statement.preview');
        Route::post('/statement/export/excel', [ArchiveRepositoryController::class, 'exportExcel'])->name('statement.excel');
        Route::post('/statement/export/pdf', [ArchiveRepositoryController::class, 'exportPdf'])->name('statement.pdf');
        
        // AJAX routes
        Route::get('/accounts/search', [ArchiveRepositoryController::class, 'searchAccounts'])->name('accounts.search');
        Route::get('/accounts/{accountNumber}/details', [ArchiveRepositoryController::class, 'getAccountDetails'])->name('accounts.details');
        
        // Data management
        Route::post('/clear-data', [ArchiveRepositoryController::class, 'clearData'])->name('clear.data');
        
        // ============================================
        // NEW: LOGGING ROUTES - ADD THESE
        // ============================================
        Route::get('/logs', [ArchiveRepositoryController::class, 'viewImportLogs'])->name('logs');
        Route::post('/logs/clear', [ArchiveRepositoryController::class, 'clearImportLogs'])->name('logs.clear');
    });

    Route::resource('users', UserController::class);
    
    // Additional routes
    Route::post('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
    Route::post('users/{user}/send-welcome', [UserController::class, 'sendWelcome'])->name('users.send-welcome');
    Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
    Route::post('users/bulk-action', [UserController::class, 'bulkAction'])->name('users.bulk-action');

    Route::get('role', [RolePermissionController::class, 'index'])->name('role.index');
    Route::get('role/create', [RolePermissionController::class, 'create'])->name('role.create');
    Route::post('role/create', [RolePermissionController::class, 'store'])->name('role.store');
    Route::get('role/{id}/edit', [RolePermissionController::class, 'edit'])->name('role.edit');
    
    // Add these remaining routes:
    Route::put('role/{id}', [RolePermissionController::class, 'update'])->name('role.update');
    Route::delete('role/{id}', [RolePermissionController::class, 'destroy'])->name('role.destroy');
    Route::get('role/{id}', [RolePermissionController::class, 'show'])->name('role.show');
    Route::get('role/{id}/details', [RolePermissionController::class, 'details'])->name('role.details');
    Route::post('role/bulk-delete', [RolePermissionController::class, 'bulkDelete'])->name('role.bulk-delete');
    Route::get('role/{id}/permissions', [RolePermissionController::class, 'managePermissions'])->name('role.permissions');
    Route::put('role/{id}/permissions', [RolePermissionController::class, 'updatePermissions'])->name('role.permissions.update');

    Route::get('users-without-roles', [AssignRoleController::class, 'getUsersWithoutRoles'])->name('users.without-roles');
    Route::get('role/{id}/permissions', [AssignRoleController::class, 'getRolePermissions'])->name('role.permissions.ajax');

    Route::get('role-users', [AssignRoleController::class, 'index'])
    ->name('role-users.index');

    // Create
    Route::get('role-users/create', [AssignRoleController::class, 'create'])
    ->name('role-users.create');

    // Store
    Route::post('role-users', [AssignRoleController::class, 'store'])
    ->name('role-users.store');

    // Edit
    Route::get('role-users/{role_user}/edit', [AssignRoleController::class, 'edit'])
    ->name('role-users.edit');

    // Update
    Route::put('role-users/{role_user}', [AssignRoleController::class, 'update'])
    ->name('role-users.update');

    // Destroy
    Route::delete('role-users/{role_user}', [AssignRoleController::class, 'destroy'])
    ->name('role-users.destroy');

    // Customer Routes
    Route::prefix('customers')->name('customers.')->group(function () {
        Route::get('/search', [CustomerController::class, 'search'])->name('search');
        Route::get('/{id}', [CustomerController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [CustomerController::class, 'edit'])->name('edit');
        Route::put('/{id}', [CustomerController::class, 'update'])->name('update');
        Route::get('/export/data', [CustomerController::class, 'export'])->name('export');
        
        // Import routes
        Route::get('/import/form', [CustomerImportController::class, 'showImportForm'])->name('import.form');
        Route::post('/import', [CustomerImportController::class, 'import'])->name('import.post');
        Route::get('/import/template', [CustomerImportController::class, 'downloadTemplate'])->name('download.template');
    });

    Route::get('/customers/{id}/debug', [CustomerController::class, 'debug'])->name('customers.debug');
    
    Route::post('/logout', [TwoFactorController::class, 'logout'])->name('logout');
});