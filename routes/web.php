<?php
// routes/web.php

use App\Http\Controllers\Account\PasswordController as AccountPasswordController;
use App\Http\Controllers\Account\SessionController;
use App\Http\Controllers\Admin\AssignRoleController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\RolePermissionController;
use App\Http\Controllers\Admin\SystemHealthController;
use App\Http\Controllers\ArchiveRepositoryController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerImportController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Guest routes (not signed in)
Route::middleware('guest')->group(function () {
    Route::get('/', function () {
        return view('welcome');
    })->name('home');
    Route::get('/login', [TwoFactorController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [TwoFactorController::class, 'login'])->name('login.post');
    Route::get('/2fa/verify', [TwoFactorController::class, 'showVerifyForm'])->name('2fa.verify.form');
    Route::post('/2fa/verify', [TwoFactorController::class, 'verify'])->name('2fa.verify');
    Route::post('/2fa/resend', [TwoFactorController::class, 'resend'])->name('2fa.resend');

    // Forgotten password and the page behind every emailed password link
    Route::get('/forgot-password', [PasswordResetController::class, 'showForgotForm'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])
        ->where('token', '[A-Za-z0-9]{64}')
        ->name('password.reset.form');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])->name('password.store');
});

// Signed-in routes. Every request here is checked for: active account, not
// locked, inactivity timeout and required password change (bootstrap/app.php),
// and changes or views of customer data are written to the audit log.
Route::middleware(['auth', 'account.active', 'account.locked', 'log.activity'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Own account: change password, see own sign-ins
    Route::get('/account/password', [AccountPasswordController::class, 'edit'])->name('account.password.edit');
    Route::put('/account/password', [AccountPasswordController::class, 'update'])->name('account.password.update');
    Route::post('/session/ping', [SessionController::class, 'ping'])->name('session.ping');

    // Archive Repository (permission checks are in ArchiveRepositoryController)
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

        // Import logs
        Route::get('/logs', [ArchiveRepositoryController::class, 'viewImportLogs'])->name('logs');
        Route::post('/logs/clear', [ArchiveRepositoryController::class, 'clearImportLogs'])->name('logs.clear');
    });

    // Users (permission checks are in UserController)
    Route::post('users/bulk-action', [UserController::class, 'bulkAction'])->name('users.bulk-action');
    Route::resource('users', UserController::class);
    Route::post('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
    Route::post('users/{user}/send-welcome', [UserController::class, 'sendWelcome'])->name('users.send-welcome');
    Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
    Route::post('users/{user}/unlock', [UserController::class, 'unlock'])->name('users.unlock');
    Route::post('users/{user}/require-password-change', [UserController::class, 'requirePasswordChange'])->name('users.require-password-change');

    // Roles and permissions. RolePermissionController only checks its basic
    // pages, so the extra actions are checked here. Before this update any
    // signed-in user could change a role's permissions or delete roles.
    Route::get('role', [RolePermissionController::class, 'index'])->name('role.index');
    Route::get('role/create', [RolePermissionController::class, 'create'])->name('role.create');
    Route::post('role/create', [RolePermissionController::class, 'store'])->name('role.store');
    Route::get('role/{id}/edit', [RolePermissionController::class, 'edit'])->name('role.edit');
    Route::put('role/{id}', [RolePermissionController::class, 'update'])->name('role.update');
    Route::delete('role/{id}', [RolePermissionController::class, 'destroy'])->name('role.destroy');
    Route::get('role/{id}', [RolePermissionController::class, 'show'])->name('role.show');
    Route::get('role/{id}/details', [RolePermissionController::class, 'details'])
        ->middleware('permission:roles-permissions index')->name('role.details');
    Route::post('role/bulk-delete', [RolePermissionController::class, 'bulkDelete'])
        ->middleware('permission:roles-permissions destroy')->name('role.bulk-delete');
    Route::get('role/{id}/permissions', [RolePermissionController::class, 'managePermissions'])
        ->middleware('permission:roles-permissions update')->name('role.permissions');
    Route::put('role/{id}/permissions', [RolePermissionController::class, 'updatePermissions'])
        ->middleware('permission:roles-permissions update')->name('role.permissions.update');

    Route::get('users-without-roles', [AssignRoleController::class, 'getUsersWithoutRoles'])
        ->middleware('permission:roles-users index')->name('users.without-roles');
    // Same address as role.permissions above (kept as it was): this later
    // registration is the one Laravel actually serves.
    Route::get('role/{id}/permissions', [AssignRoleController::class, 'getRolePermissions'])
        ->middleware('permission:roles-users index')->name('role.permissions.ajax');

    Route::get('role-users', [AssignRoleController::class, 'index'])->name('role-users.index');
    Route::get('role-users/create', [AssignRoleController::class, 'create'])->name('role-users.create');
    Route::post('role-users', [AssignRoleController::class, 'store'])->name('role-users.store');
    Route::get('role-users/{role_user}/edit', [AssignRoleController::class, 'edit'])->name('role-users.edit');
    Route::put('role-users/{role_user}', [AssignRoleController::class, 'update'])->name('role-users.update');
    Route::delete('role-users/{role_user}', [AssignRoleController::class, 'destroy'])->name('role-users.destroy');

    // Customers. CustomerController checks search and show; editing, the
    // full export, the import and the debug page are for "archive manage".
    Route::prefix('customers')->name('customers.')->group(function () {
        Route::get('/search', [CustomerController::class, 'search'])->name('search');

        Route::middleware('permission:archive manage')->group(function () {
            Route::get('/export/data', [CustomerController::class, 'export'])->name('export');
            Route::get('/import/form', [CustomerImportController::class, 'showImportForm'])->name('import.form');
            Route::post('/import', [CustomerImportController::class, 'import'])->name('import.post');
            Route::get('/import/template', [CustomerImportController::class, 'downloadTemplate'])->name('download.template');
            Route::get('/{id}/edit', [CustomerController::class, 'edit'])->name('edit');
            Route::put('/{id}', [CustomerController::class, 'update'])->name('update');
            Route::get('/{id}/debug', [CustomerController::class, 'debug'])->name('debug');
        });

        Route::get('/{id}', [CustomerController::class, 'show'])->name('show');
    });

    // System Health (permission: system health)
    Route::get('/system/health', [SystemHealthController::class, 'index'])->name('system.health');
    Route::post('/system/health/test-email', [SystemHealthController::class, 'sendTestEmail'])
        ->middleware('throttle:5,10')->name('system.health.test-email');

    // Audit log and sign-in history (permission: system audit)
    Route::get('/audit/activity', [AuditLogController::class, 'activity'])->name('audit.activity');
    Route::get('/audit/activity/export', [AuditLogController::class, 'exportActivity'])->name('audit.activity.export');
    Route::get('/audit/logins', [AuditLogController::class, 'logins'])->name('audit.logins');
    Route::get('/audit/logins/export', [AuditLogController::class, 'exportLogins'])->name('audit.logins.export');

    Route::post('/logout', [TwoFactorController::class, 'logout'])->name('logout');
});
