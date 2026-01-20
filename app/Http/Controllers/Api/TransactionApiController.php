<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionResource;
use App\Models\NominalAccountsTrxPostingHistory;
use Illuminate\Http\Request;

/**
 * @group Transaction Management
 * 
 * APIs for managing transactions
 */
class TransactionApiController extends Controller
{
    /**
     * List all transactions
     * 
     * Get a paginated list of all transactions.
     * 
     * @queryParam page integer Page number. Example: 1
     * @queryParam per_page integer Items per page (max 100). Example: 50
     * @queryParam start_date date Filter from date (Y-m-d). Example: 2024-01-01
     * @queryParam end_date date Filter to date (Y-m-d). Example: 2024-12-31
     * 
     * @response 200 {
     *   "data": []
     * }
     */
    public function index(Request $request)
    {
        $query = NominalAccountsTrxPostingHistory::query()
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
     * Get single transaction
     * 
     * Retrieve detailed information about a specific transaction.
     * 
     * @urlParam id integer required The transaction ID. Example: 1
     * 
     * @response 200 {
     *   "data": {
     *     "id": 1,
     *     "transaction_date": "2024-01-15 10:30:00",
     *     "narration": "Transfer",
     *     "debit": "5,000.00",
     *     "credit": "0.00"
     *   }
     * }
     */
    public function show($id)
    {
        $transaction = NominalAccountsTrxPostingHistory::findOrFail($id);
        return new TransactionResource($transaction);
    }
}