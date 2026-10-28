<?php

namespace App\Http\Controllers;

use App\Models\SavingsAndCurrentAccount;
use App\Models\NominalAccountsTrxPostingHistory;
use App\Imports\SavingsAndCurrentAccountImport;
use App\Imports\NominalAccountsTrxPostingHistoryImport;
use App\Exports\StatementOfAccountExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Routing\Controller;

class ArchiveRepositoryController extends Controller
{
    /**
     * Access control.
     *
     * Previously only four pages checked a permission; the import, clear-data
     * and log-clearing actions only required a login, so any user could
     * replace or wipe the archive. Every action is now gated:
     *  - archive index:      the archive dashboard
     *  - archive statements: look up accounts and produce statements
     *  - archive manage:     import data, clear data, import logs (Super Admin only)
     */
    public function __construct()
    {
        $this->middleware(['permission:archive index'])->only(['index']);

        $this->middleware(['permission:archive statements'])->only([
            'showStatementForm', 'previewStatement', 'exportExcel', 'exportPdf',
            'searchAccounts', 'getAccountDetails',
        ]);

        $this->middleware(['permission:archive manage'])->only([
            'showAccountImport', 'importAccounts',
            'showTransactionImport', 'importTransactions',
            'viewImportLogs', 'clearImportLogs', 'clearData',
        ]);
    }
    /**
     * Display archive repository dashboard
     */
    public function index()
    {
        $totalAccounts = SavingsAndCurrentAccount::count();
        $totalTransactions = NominalAccountsTrxPostingHistory::count();
        $activeAccounts = SavingsAndCurrentAccount::active()->count();

        $accounts = SavingsAndCurrentAccount::with('transactionHistory')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('backend.archive.index', compact(
            'totalAccounts',
            'totalTransactions',
            'activeAccounts',
            'accounts'
        ));
    }

    /**
     * Show import form for accounts
     */
    public function showAccountImport()
    {
        $totalAccounts = SavingsAndCurrentAccount::count();
        return view('backend.archive.import-accounts', compact('totalAccounts'));
    }

    /**
     * Import savings and current accounts
     *
     * The server default is 30 seconds, which is what stopped the account
     * import. Large loads should use `php artisan archive:import accounts ...`
     * from the server shell; this web route is kept for small top-ups.
     */
    public function importAccounts(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:51200', // 50MB
        ]);

        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        try {
            DB::beginTransaction();

            $import = new SavingsAndCurrentAccountImport();
            Excel::import($import, $request->file('file'));

            DB::commit();

            return redirect()->route('archive.import.accounts')
                ->with('success', 'Accounts imported successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Account import error: ' . $e->getMessage());

            return redirect()->back()
                ->with('error', 'Error importing accounts: ' . $e->getMessage());
        }
    }

    /**
     * Show import form for transaction history
     */
    public function showTransactionImport()
    {
        $totalTransactions = NominalAccountsTrxPostingHistory::count();

        // Get recent import logs
        $logFile = storage_path('logs/import-transactions.log');
        $recentLogs = '';
        if (file_exists($logFile)) {
            $logs = file($logFile);
            $recentLogs = implode('', array_slice($logs, -20)); // Last 20 lines
        }

        return view('backend.archive.import-transactions', compact('totalTransactions', 'recentLogs'));
    }

    /**
     * Import transaction posting history (handles large files)
     * WITH DETAILED LOGGING
     */
    public function importTransactions(Request $request)
    {
        $sessionId = uniqid('import_', true);

        $logImport = function($message, $level = 'info') use ($sessionId) {
            $timestamp = now()->format('Y-m-d H:i:s');
            $logMessage = "[{$timestamp}] [{$sessionId}] {$message}";

            Storage::disk('local')->append('logs/import-transactions.log', $logMessage);
            Log::channel('single')->{$level}($logMessage);

            return $logMessage;
        };

        try {
            $logImport("========== NEW IMPORT SESSION STARTED ==========");

            // Initial count
            $initialCount = NominalAccountsTrxPostingHistory::count();
            $logImport("Initial transaction count: {$initialCount}");

            // Validate file
            $request->validate([
                'file' => 'required|mimes:xlsx,xls,csv|max:204800', // 200MB max
            ]);

            $file = $request->file('file');
            $fileName = $file->getClientOriginalName();
            $fileSize = round($file->getSize() / 1024 / 1024, 2);

            $logImport("File: {$fileName} ({$fileSize} MB)");

            // CRITICAL: Remove execution time limit (timeout proof)
            set_time_limit(0); // UNLIMITED execution time
            ini_set('max_execution_time', '0'); // UNLIMITED
            ini_set('memory_limit', '2048M'); // 2GB memory

            // Disable MySQL strict mode temporarily for better compatibility
            DB::statement("SET SESSION sql_mode=''");

            $logImport("PHP limits: Execution time = UNLIMITED, Memory = 2GB");

            $startTime = microtime(true);

            // **CRITICAL: NO TRANSACTION WRAPPER**
            // Each batch insert will auto-commit immediately
            // This way, if it times out, inserted records stay in database

            $logImport("Starting import WITHOUT transaction wrapper (auto-commit mode)");

            $import = new NominalAccountsTrxPostingHistoryImport();

            try {
                Excel::import($import, $file);
                $logImport("Excel import completed successfully");
            } catch (\Exception $e) {
                $logImport("Excel import encountered error: " . $e->getMessage(), 'error');

                // Even if there's an error, some records may have been inserted
                // So we don't throw - we continue to get stats
            }

            // Get statistics
            $stats = $import->getStats();

            // Verify actual database state
            $finalCount = NominalAccountsTrxPostingHistory::count();
            $actualImported = $finalCount - $initialCount;

            $logImport("Final count: {$finalCount} | Actually added: {$actualImported}");

            $duration = round(microtime(true) - $startTime, 2);
            $memoryUsed = round(memory_get_peak_usage(true) / 1024 / 1024, 2);

            $logImport("Duration: {$duration}s | Peak memory: {$memoryUsed} MB");
            $logImport("========== IMPORT SESSION COMPLETED ==========");

            $successMessage = "Import completed! " .
                "Processed: {$stats['total_rows']} | " .
                "Inserted: {$stats['inserted']} | " .
                "Duplicates skipped: {$stats['duplicates_skipped']} | " .
                "Errors: {$stats['errors']} | " .
                "Time: {$duration}s";

            return redirect()->route('archive.import.transactions')
                ->with('success', $successMessage)
                ->with('info', "Session ID: {$sessionId} - Check logs for details");

        } catch (\Illuminate\Validation\ValidationException $e) {
            $logImport("Validation failed", 'error');

            return redirect()->back()
                ->withErrors($e->errors())
                ->with('error', 'File validation failed');

        } catch (\Exception $e) {
            $logImport("CRITICAL ERROR: " . $e->getMessage(), 'error');
            $logImport("File: " . $e->getFile() . " Line: " . $e->getLine(), 'error');

            // Check what actually got imported despite the error
            $currentCount = NominalAccountsTrxPostingHistory::count();
            $logImport("Records in DB after error: {$currentCount}", 'info');

            Log::error('Transaction import error', [
                'session_id' => $sessionId,
                'file' => $fileName ?? 'unknown',
                'exception' => $e
            ]);

            return redirect()->back()
                ->with('error', 'Error during import: ' . $e->getMessage())
                ->with('info', 'Some records may have been imported. Check logs (Session: ' . $sessionId . ')');
        }
    }

    /**
     * View import logs
     */
    public function viewImportLogs()
    {
        $logFile = storage_path('logs/import-transactions.log');

        $logs = '';
        $logExists = false;

        if (file_exists($logFile)) {
            $logs = file_get_contents($logFile);
            $logExists = true;
        }

        return view('backend.archive.import-logs', compact('logs', 'logExists'));
    }

    /**
     * Clear import logs
     */
    public function clearImportLogs()
    {
        $logFile = storage_path('logs/import-transactions.log');

        if (file_exists($logFile)) {
            unlink($logFile);
        }

        return redirect()->back()->with('success', 'Import logs cleared successfully');
    }

    /**
     * Show statement generation form
     */
    public function showStatementForm()
    {
        $accounts = SavingsAndCurrentAccount::select('id', 'old_acct_no', 'customerid', 'branch')
            ->orderBy('old_acct_no')
            ->get();

        return view('backend.archive.generate-statement', compact('accounts'));
    }

    /**
     * Preview statement
     */
    public function previewStatement(Request $request)
    {
        $request->validate([
            'account_number' => 'required|exists:savings_and_current_accounts,old_acct_no',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $account = SavingsAndCurrentAccount::where('old_acct_no', $request->account_number)->first();

        $transactions = $this->statementTransactions($request);

        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);

        // Calculate totals
        $totalDebit = $transactions->sum('debit');
        $totalCredit = $transactions->sum('credit');
        $openingBalance = $transactions->first()->opening_balance ?? 0;
        $closingBalance = $transactions->last()->balance_after ?? $account->bk_balance_corrected;

        return view('backend.archive.preview-statement', compact(
            'account',
            'transactions',
            'startDate',
            'endDate',
            'totalDebit',
            'totalCredit',
            'openingBalance',
            'closingBalance'
        ));
    }

    /**
     * Export statement to Excel
     */
    public function exportExcel(Request $request)
    {
        $request->validate([
            'account_number' => 'required|exists:savings_and_current_accounts,old_acct_no',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $account = SavingsAndCurrentAccount::where('old_acct_no', $request->account_number)->first();
        $fileName = 'statement_' . $request->account_number . '_' . now()->format('YmdHis') . '.xlsx';

        // Set logo path - change this to your actual logo location
        $logoPath = public_path('images/lukefield-logo.png'); // Custom logo
        // OR use default: $logoPath = null; (will use public/images/logo.png)

        return Excel::download(
            new StatementOfAccountExport(
                $request->account_number,
                $request->start_date,
                $request->end_date,
                $logoPath  // Pass logo path here
            ),
            $fileName
        );
    }

    /**
     * Export statement to PDF
     */
    public function exportPdf(Request $request)
    {
        $request->validate([
            'account_number' => 'required|exists:savings_and_current_accounts,old_acct_no',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $account = SavingsAndCurrentAccount::where('old_acct_no', $request->account_number)->first();

        $transactions = $this->statementTransactions($request);

        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);

        $totalDebit = $transactions->sum('debit');
        $totalCredit = $transactions->sum('credit');
        $openingBalance = $transactions->first()->opening_balance ?? 0;
        $closingBalance = $transactions->last()->balance_after ?? $account->bk_balance_corrected;

        $pdf = Pdf::loadView('backend.archive.pdf-statement', compact(
            'account',
            'transactions',
            'startDate',
            'endDate',
            'totalDebit',
            'totalCredit',
            'openingBalance',
            'closingBalance'
        ));

        $fileName = 'statement_' . $request->account_number . '_' . now()->format('YmdHis') . '.pdf';

        return $pdf->download($fileName);
    }

    /**
     * Transactions for a statement, oldest first.
     *
     * transaction_order comes from the BankOne extract, where 1 is the MOST
     * RECENT posting on the account. It must therefore be sorted descending to
     * read oldest-to-newest. Sorting it ascending (as before) listed same-day
     * postings newest-first, so when the first or last day of a statement had
     * several postings, the opening and closing balances were taken from the
     * wrong row.
     */
    private function statementTransactions(Request $request)
    {
        return NominalAccountsTrxPostingHistory::forAccount($request->account_number)
            ->dateRange($request->start_date, $request->end_date)
            ->active()
            ->orderBy('transaction_date', 'asc')
            ->orderBy('transaction_order', 'desc')
            ->orderBy('id', 'desc') // file rows are newest-first, so a higher id is an older posting
            ->get();
    }

    /**
     * Search accounts via AJAX
     */
    public function searchAccounts(Request $request)
    {
        $search = $request->get('q');

        $accounts = SavingsAndCurrentAccount::where('old_acct_no', 'like', "%{$search}%")
            ->orWhere('customerid', 'like', "%{$search}%")
            ->limit(10)
            ->get(['id', 'old_acct_no', 'customerid', 'branch']);

        return response()->json($accounts);
    }

    /**
     * Get account details via AJAX
     */
    public function getAccountDetails($accountNumber)
    {
        $account = SavingsAndCurrentAccount::where('old_acct_no', $accountNumber)->first();

        if (!$account) {
            return response()->json(['error' => 'Account not found'], 404);
        }

        $transactionCount = NominalAccountsTrxPostingHistory::forAccount($accountNumber)->count();

        $firstTransaction = NominalAccountsTrxPostingHistory::forAccount($accountNumber)
            ->orderBy('transaction_date', 'asc')
            ->first();

        $lastTransaction = NominalAccountsTrxPostingHistory::forAccount($accountNumber)
            ->orderBy('transaction_date', 'desc')
            ->first();

        return response()->json([
            'account' => $account,
            'transaction_count' => $transactionCount,
            'first_transaction_date' => $firstTransaction ? $firstTransaction->transaction_date->format('Y-m-d') : null,
            'last_transaction_date' => $lastTransaction ? $lastTransaction->transaction_date->format('Y-m-d') : null,
        ]);
    }

    /**
     * Clear all data
     */
    public function clearData(Request $request)
    {
        $type = $request->input('type');

        try {
            DB::beginTransaction();

            if ($type === 'accounts') {
                SavingsAndCurrentAccount::truncate();
                $message = 'All accounts deleted successfully!';
            } elseif ($type === 'transactions') {
                NominalAccountsTrxPostingHistory::truncate();
                $message = 'All transactions deleted successfully!';
            } elseif ($type === 'all') {
                NominalAccountsTrxPostingHistory::truncate();
                SavingsAndCurrentAccount::truncate();
                $message = 'All data deleted successfully!';
            } else {
                return response()->json(['error' => 'Invalid type'], 400);
            }

            DB::commit();

            return redirect()->back()->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Clear data error: ' . $e->getMessage());

            return redirect()->back()->with('error', 'Error clearing data: ' . $e->getMessage());
        }
    }
}
