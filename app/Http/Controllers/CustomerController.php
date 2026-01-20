<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\SavingsAndCurrentAccount;
use App\Models\NominalAccountsTrxPostingHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Routing\Controller;

class CustomerController extends Controller
{
    public function __construct()
    {
        $this->middleware(['permission:customers search'])->only(['search']);
        $this->middleware(['permission:customers show'])->only(['show']);
    }
    public function search(Request $request)
    {
        $accountOfficers = Customer::whereNotNull('acct_officer')
            ->distinct()
            ->pluck('acct_officer')
            ->sort();
        
        $branches = SavingsAndCurrentAccount::whereNotNull('branch')
            ->distinct()
            ->pluck('branch')
            ->sort();
        
        $sectors = Customer::whereNotNull('sector')
            ->distinct()
            ->pluck('sector')
            ->sort();

        if (!$request->hasAny(['customer_name', 'customer_id', 'account_no', 'status', 'customer_type', 'bvn', 'branch', 'phone', 'email', 'pep', 'sector', 'account_officer'])) {
            return view('backend.customers.search', compact('accountOfficers', 'branches', 'sectors'));
        }

        $query = Customer::query();

        if ($request->filled('customer_name')) {
            $query->where(function($q) use ($request) {
                $q->where('first_name', 'like', "%{$request->customer_name}%")
                  ->orWhere('surname', 'like', "%{$request->customer_name}%")
                  ->orWhere('other_name', 'like', "%{$request->customer_name}%")
                  ->orWhereRaw("CONCAT(first_name, ' ', surname) LIKE ?", ["%{$request->customer_name}%"]);
            });
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', 'like', "%{$request->customer_id}%");
        }

        if ($request->filled('account_no')) {
            $customerIds = SavingsAndCurrentAccount::where('old_acct_no', 'like', "%{$request->account_no}%")
                ->pluck('customerid')
                ->map(function($id) {
                    return ltrim($id, '0'); // Remove leading zeros
                })
                ->toArray();
            
            if (!empty($customerIds)) {
                $query->whereIn('customer_id', $customerIds);
            }
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('customer_type')) {
            $query->where('customer_type', $request->customer_type);
        }

        if ($request->filled('bvn')) {
            $query->where('bvn', 'like', "%{$request->bvn}%");
        }

        if ($request->filled('branch')) {
            $customerIds = SavingsAndCurrentAccount::where('branch', $request->branch)
                ->pluck('customerid')
                ->map(function($id) {
                    return ltrim($id, '0');
                })
                ->toArray();
            
            if (!empty($customerIds)) {
                $query->whereIn('customer_id', $customerIds);
            }
        }

        if ($request->filled('phone')) {
            $query->where(function($q) use ($request) {
                $q->where('mobile_phone', 'like', "%{$request->phone}%")
                  ->orWhere('business_phone_no', 'like', "%{$request->phone}%");
            });
        }

        if ($request->filled('email')) {
            $query->where('email', 'like', "%{$request->email}%");
        }

        if ($request->filled('pep')) {
            $query->where('pep', $request->pep);
        }

        if ($request->filled('sector')) {
            $query->where('sector', $request->sector);
        }

        if ($request->filled('account_officer')) {
            $query->where('acct_officer', $request->account_officer);
        }

        $customers = $query->paginate(25)->withQueryString();
        
        // Load accounts for each customer
        foreach ($customers as $customer) {
            // Pad customer_id to 6 digits with leading zeros
            $paddedCustomerId = str_pad($customer->customer_id, 6, '0', STR_PAD_LEFT);
            $accounts = SavingsAndCurrentAccount::where('customerid', $paddedCustomerId)->get();
            $customer->accounts = $accounts;
            $customer->accounts_count = $accounts->count();
        }

        return view('backend.customers.search', compact('customers', 'accountOfficers', 'branches', 'sectors'));
    }

    public function show($id)
    {
        $customer = Customer::findOrFail($id);
        
        // Pad customer_id to 6 digits with leading zeros to match accounts table format
        $paddedCustomerId = str_pad($customer->customer_id, 6, '0', STR_PAD_LEFT);
        
        // Get accounts for this customer
        $accounts = SavingsAndCurrentAccount::where('customerid', $paddedCustomerId)
            ->orderBy('created_at', 'desc')
            ->get();
        
        $customer->accounts = $accounts;
        
        return view('backend.customers.show', compact('customer'));
    }

    public function export(Request $request)
    {
        $query = Customer::query();

        if ($request->filled('customer_name')) {
            $query->where(function($q) use ($request) {
                $q->where('first_name', 'like', "%{$request->customer_name}%")
                  ->orWhere('surname', 'like', "%{$request->customer_name}%");
            });
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', 'like', "%{$request->customer_id}%");
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $customers = $query->get();
        
        foreach ($customers as $customer) {
            $paddedCustomerId = str_pad($customer->customer_id, 6, '0', STR_PAD_LEFT);
            $accounts = SavingsAndCurrentAccount::where('customerid', $paddedCustomerId)->get();
            $customer->accounts = $accounts;
        }

        $format = $request->get('format', 'csv');
        $filename = 'customers_export_' . now()->format('Y-m-d_His') . '.' . $format;

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($customers) {
            $file = fopen('php://output', 'w');

            fputcsv($file, [
                'Customer ID', 'Name', 'Customer Type', 'Phone', 'Email',
                'BVN', 'Branch', 'Status', 'Number of Accounts'
            ]);

            foreach ($customers as $customer) {
                fputcsv($file, [
                    $customer->customer_id,
                    $customer->full_name,
                    $customer->customer_type,
                    $customer->mobile_phone,
                    $customer->email,
                    $customer->bvn,
                    $customer->accounts->first()->branch ?? 'N/A',
                    $customer->status,
                    $customer->accounts->count(),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}