<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerResource;
use App\Http\Resources\AccountResource;
use App\Http\Resources\TransactionResource;
use App\Models\Customer;
use App\Models\SavingsAndCurrentAccount;
use App\Models\NominalAccountsTrxPostingHistory;
use Illuminate\Http\Request;

/**
 * @group Customer Management
 * 
 * APIs for managing customers and their accounts
 */
class CustomerApiController extends Controller
{
    /**
     * List all customers
     * 
     * Get a paginated list of all customers in the system.
     * 
     * @queryParam page integer Page number for pagination. Example: 1
     * @queryParam per_page integer Number of items per page (max 100). Example: 25
     * 
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "customer_id": "123456",
     *       "full_name": "Mr John Doe",
     *       "first_name": "John",
     *       "surname": "Doe",
     *       "email": "john@example.com",
     *       "mobile_phone": "08012345678",
     *       "status": "Active"
     *     }
     *   ],
     *   "links": {},
     *   "meta": {}
     * }
     */
    public function index(Request $request)
    {
        $perPage = min($request->input('per_page', 25), 100);
        
        $customers = Customer::query()
            ->paginate($perPage);
        
        return CustomerResource::collection($customers);
    }

    /**
     * Search customers
     * 
     * Search and filter customers by various criteria.
     * 
     * @queryParam customer_name string Search by customer name. Example: John
     * @queryParam customer_id string Search by customer ID. Example: 123456
     * @queryParam email string Search by email address. Example: john@example.com
     * @queryParam phone string Search by phone number. Example: 08012345678
     * @queryParam bvn string Search by BVN. Example: 12345678901
     * @queryParam status string Filter by status. Example: Active
     * @queryParam pep string Filter by PEP status (YES/NO). Example: NO
     * @queryParam per_page integer Items per page (max 100). Example: 25
     * 
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "customer_id": "123456",
     *       "full_name": "Mr John Doe",
     *       "email": "john@example.com"
     *     }
     *   ]
     * }
     */
    public function search(Request $request)
    {
        $query = Customer::query();

        if ($request->filled('customer_name')) {
            $query->where(function($q) use ($request) {
                $q->where('first_name', 'like', "%{$request->customer_name}%")
                  ->orWhere('surname', 'like', "%{$request->customer_name}%")
                  ->orWhereRaw("CONCAT(first_name, ' ', surname) LIKE ?", ["%{$request->customer_name}%"]);
            });
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', 'like', "%{$request->customer_id}%");
        }

        if ($request->filled('email')) {
            $query->where('email', 'like', "%{$request->email}%");
        }

        if ($request->filled('phone')) {
            $query->where(function($q) use ($request) {
                $q->where('mobile_phone', 'like', "%{$request->phone}%")
                  ->orWhere('business_phone_no', 'like', "%{$request->phone}%");
            });
        }

        if ($request->filled('bvn')) {
            $query->where('bvn', $request->bvn);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('pep')) {
            $query->where('pep', $request->pep);
        }

        $perPage = min($request->input('per_page', 25), 100);
        $customers = $query->paginate($perPage);

        return CustomerResource::collection($customers);
    }

    /**
     * Get single customer
     * 
     * Retrieve detailed information about a specific customer.
     * 
     * @urlParam id integer required The customer ID. Example: 1
     * 
     * @response 200 {
     *   "data": {
     *     "id": 1,
     *     "customer_id": "123456",
     *     "full_name": "Mr John Doe",
     *     "first_name": "John",
     *     "surname": "Doe",
     *     "email": "john@example.com",
     *     "mobile_phone": "08012345678",
     *     "status": "Active",
     *     "bvn": "12345678901"
     *   }
     * }
     * 
     * @response 404 {
     *   "message": "No query results for model [App\\Models\\Customer]."
     * }
     */
    public function show($id)
    {
        $customer = Customer::findOrFail($id);
        return new CustomerResource($customer);
    }

    /**
     * Get customer's accounts
     * 
     * Get all accounts belonging to a specific customer.
     * 
     * @urlParam id integer required The customer ID. Example: 1
     * 
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "account_number": "1234567890",
     *       "branch": "Head Office",
     *       "balance": "50,000.00",
     *       "status": "Active"
     *     }
     *   ]
     * }
     */
    public function accounts($id)
    {
        $customer = Customer::findOrFail($id);
        $paddedCustomerId = str_pad($customer->customer_id, 6, '0', STR_PAD_LEFT);
        
        $accounts = SavingsAndCurrentAccount::where('customerid', $paddedCustomerId)
            ->withCount('transactionHistory')
            ->get();

        return AccountResource::collection($accounts);
    }

    /**
     * Get customer's transactions
     * 
     * Get all transactions across all accounts for a specific customer.
     * 
     * @urlParam id integer required The customer ID. Example: 1
     * @queryParam start_date date Filter transactions from this date (Y-m-d). Example: 2024-01-01
     * @queryParam end_date date Filter transactions up to this date (Y-m-d). Example: 2024-12-31
     * @queryParam per_page integer Items per page (max 100). Example: 50
     * 
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "transaction_date": "2024-01-15 10:30:00",
     *       "narration": "Transfer",
     *       "debit": "5,000.00",
     *       "credit": "0.00",
     *       "balance_after": "45,000.00"
     *     }
     *   ]
     * }
     */
    public function transactions($id, Request $request)
    {
        $customer = Customer::findOrFail($id);
        $paddedCustomerId = str_pad($customer->customer_id, 6, '0', STR_PAD_LEFT);
        
        $accountNumbers = SavingsAndCurrentAccount::where('customerid', $paddedCustomerId)
            ->pluck('old_acct_no');

        $query = NominalAccountsTrxPostingHistory::whereIn('old_acct_no', $accountNumbers)
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
     * Login and get API token
     * 
     * @group Authentication
     * @bodyParam email string required User email
     * @bodyParam password string required User password
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = \App\Models\User::where('email', $request->email)->first();

        if (!$user || !\Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
            ]
        ]);
    }
}