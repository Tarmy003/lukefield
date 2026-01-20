<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AccountResource;
use App\Http\Resources\TransactionResource;
use App\Models\SavingsAndCurrentAccount;
use App\Models\NominalAccountsTrxPostingHistory;
use Illuminate\Http\Request;
use Carbon\Carbon;

/**
 * @group Account Management
 * 
 * APIs for managing accounts and transactions
 */
class AccountApiController extends Controller
{
    /**
     * List all accounts
     * 
     * Get a paginated list of all accounts.
     * 
     * @queryParam page integer Page number. Example: 1
     * @queryParam per_page integer Items per page (max 100). Example: 25
     * 
     * @response 200 {
     *   "data": [
     *     {
     *       "account_number": "1234567890",
     *       "branch": "Head Office",
     *       "balance": "50,000.00",
     *       "status": "Active"
     *     }
     *   ]
     * }
     */
    public function index(Request $request)
    {
        $perPage = min($request->input('per_page', 25), 100);
        
        $accounts = SavingsAndCurrentAccount::query()
            ->withCount('transactionHistory')
            ->paginate($perPage);
        
        return AccountResource::collection($accounts);
    }

    /**
     * Search accounts
     * 
     * Search and filter accounts by various criteria.
     * 
     * @queryParam account_number string Search by account number. Example: 1234567890
     * @queryParam customer_id string Filter by customer ID. Example: 000123
     * @queryParam branch string Filter by branch name. Example: Head Office
     * @queryParam status integer Filter by status (1=Active, 2=Closed, 3=Dormant, 4=Frozen). Example: 1
     * @queryParam per_page integer Items per page (max 100). Example: 25
     * 
     * @response 200 {
     *   "data": []
     * }
     */
    public function search(Request $request)
    {
        $query = SavingsAndCurrentAccount::query();

        if ($request->filled('account_number')) {
            $query->where('old_acct_no', 'like', "%{$request->account_number}%");
        }

        if ($request->filled('customer_id')) {
            $query->where('customerid', 'like', "%{$request->customer_id}%");
        }

        if ($request->filled('branch')) {
            $query->where('branch', $request->branch);
        }

        if ($request->filled('status')) {
            $query->where('account_status', $request->status);
        }

        $perPage = min($request->input('per_page', 25), 100);
        $accounts = $query->withCount('transactionHistory')->paginate($perPage);

        return AccountResource::collection($accounts);
    }

    /**
     * Get single account
     * 
     * Retrieve detailed information about a specific account.
     * 
     * @urlParam accountNumber string required The account number. Example: 1234567890
     * 
     * @response 200 {
     *   "data": {
     *     "account_number": "1234567890",
     *     "branch": "Head Office",
     *     "balance": "50,000.00",
     *     "status": "Active",
     *     "transaction_count": 150
     *   }
     * }
     * 
     * @response 404 {
     *   "message": "No query results for model [App\\Models\\SavingsAndCurrentAccount]."
     * }
     */
    public function show($accountNumber)
    {
        $account = SavingsAndCurrentAccount::where('old_acct_no', $accountNumber)
            ->withCount('transactionHistory')
            ->firstOrFail();

        return new AccountResource($account);
    }

    /**
     * Get account transactions
     * 
     * Get all transactions for a specific account.
     * 
     * @urlParam accountNumber string required The account number. Example: 1234567890
     * @queryParam start_date date Filter from this date (Y-m-d). Example: 2024-01-01
     * @queryParam end_date date Filter to this date (Y-m-d). Example: 2024-12-31
     * @queryParam per_page integer Items per page (max 100). Example: 50
     * 
     * @response 200 {
     *   "data": [
     *     {
     *       "transaction_date": "2024-01-15 10:30:00",
     *       "narration": "Transfer",
     *       "debit": "5,000.00",
     *       "credit": "0.00"
     *     }
     *   ]
     * }
     */
    public function transactions($accountNumber, Request $request)
    {
        $account = SavingsAndCurrentAccount::where('old_acct_no', $accountNumber)->firstOrFail();

        $query = NominalAccountsTrxPostingHistory::forAccount($accountNumber)
            ->orderBy('transaction_date', 'desc');

        if ($request->filled('start_date')) {
            $query->whereDate('transaction_date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('transaction_date', '<=', $request->end_date);
        }

        $perPage = min($request->input('per_page', 50), 100);
        $transactions = $query->paginate($perPage);

        return TransactionResource::collection($transactions);
    }

    /**
     * Get account statement
     * 
     * Generate a statement of account for a specific period.
     * 
     * @urlParam accountNumber string required The account number. Example: 1234567890
     * @queryParam start_date date required Statement start date (Y-m-d). Example: 2024-01-01
     * @queryParam end_date date required Statement end date (Y-m-d). Example: 2024-12-31
     * 
     * @response 200 {
     *   "account": {
     *     "account_number": "1234567890",
     *     "balance": "50,000.00"
     *   },
     *   "period": {
     *     "start_date": "2024-01-01",
     *     "end_date": "2024-12-31"
     *   },
     *   "summary": {
     *     "opening_balance": "25,000.00",
     *     "total_debit": "100,000.00",
     *     "total_credit": "125,000.00",
     *     "closing_balance": "50,000.00",
     *     "transaction_count": 150
     *   },
     *   "transactions": []
     * }
     * 
     * @response 422 {
     *   "message": "The start date field is required.",
     *   "errors": {}
     * }
     */
    public function statement($accountNumber, Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $account = SavingsAndCurrentAccount::where('old_acct_no', $accountNumber)->firstOrFail();

        $transactions = NominalAccountsTrxPostingHistory::forAccount($accountNumber)
            ->dateRange($request->start_date, $request->end_date)
            ->active()
            ->orderBy('transaction_date', 'asc')
            ->get();

        $totalDebit = $transactions->sum('debit');
        $totalCredit = $transactions->sum('credit');
        $openingBalance = $transactions->first()->opening_balance ?? 0;
        $closingBalance = $transactions->last()->balance_after ?? $account->bk_balance_corrected;

        return response()->json([
            'account' => new AccountResource($account),
            'period' => [
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
            ],
            'summary' => [
                'opening_balance' => number_format($openingBalance, 2),
                'total_debit' => number_format($totalDebit, 2),
                'total_credit' => number_format($totalCredit, 2),
                'closing_balance' => number_format($closingBalance, 2),
                'transaction_count' => $transactions->count(),
            ],
            'transactions' => TransactionResource::collection($transactions),
        ]);
    }
}