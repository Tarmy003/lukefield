<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Imports\CustomersImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class CustomerImportController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::with('accounts');

        // Search
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('customer_type')) {
            $query->where('customer_type', $request->customer_type);
        }

        if ($request->filled('sex')) {
            $query->where('sex', $request->sex);
        }

        if ($request->filled('pep')) {
            $query->where('pep', $request->pep);
        }

        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->whereBetween('date_opened', [
                $request->date_from,
                $request->date_to
            ]);
        }

        // Get counts for each account
        $query->withCount('accounts');

        // Sort
        $query->orderBy('created_at', 'desc');

        // Pagination
        $perPage = $request->get('per_page', 25);
        $customers = $query->paginate($perPage)->withQueryString();

        // Statistics
        $stats = [
            'total' => Customer::count(),
            'active' => Customer::where('status', 'Active')->count(),
            'individual' => Customer::where('customer_type', 'Individual')->count(),
            'pep' => Customer::where('pep', 'YES')->count(),
        ];

        return view('backend.customers.index', compact('customers', 'stats'));
    }
    public function showImportForm()
    {
        $totalCustomers = Customer::count();
        $activeCustomers = Customer::where('status', 'Active')->count();
        
        return view('backend.customers.import', compact('totalCustomers', 'activeCustomers'));
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:20480', // 20MB
        ]);

        try {
            $file = $request->file('file');
            $replaceData = $request->has('replace_data');
            
            // Log import start
            Log::info('Customer import started', [
                'filename' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'replace_mode' => $replaceData,
                'user' => auth()->user()->name ?? 'System',
            ]);

            // Create import instance
            $import = new CustomersImport($replaceData);
            
            // Increase timeout for large files
            set_time_limit(600); // 10 minutes
            ini_set('memory_limit', '512M');
            
            // Execute import
            Excel::import($import, $file);
            
            // Get statistics
            $stats = $import->getStats();
            
            // Log completion
            Log::info('Customer import completed', $stats);
            
            return redirect()
                ->route('customers.import.form')
                ->with('success', 'Customer import completed successfully!')
                ->with('stats', $stats);
                
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            
            $errorMessage = "Import validation failed:\n";
            foreach ($failures as $failure) {
                $errorMessage .= "Row {$failure->row()}: " . implode(', ', $failure->errors()) . "\n";
            }
            
            Log::error('Customer import validation failed', ['errors' => $errorMessage]);
            
            return redirect()
                ->back()
                ->with('error', 'Import validation failed. Please check your file format.')
                ->withInput();
                
        } catch (\Exception $e) {
            Log::error('Customer import failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()
                ->back()
                ->with('error', 'Import failed: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function downloadTemplate()
    {
        // Create sample data for template
        $headers = [
            'CustomerID',
            'Title',
            'FirstName',
            'Surname',
            'OtherName',
            'CustomerType',
            'Sex',
            'StateOfOrigin',
            'DoB',
            'ResidentialAddress',
            'ResidentialState',
            'ResidentialTown',
            'Email',
            'MobilePhone',
            'BusinessPhoneNo',
            'OfficeName',
            'OfficeAddress',
            'OfficePhone',
            'AcctOfficer',
            'Occupation',
            'EduLevel',
            'Status',
            'DateOpened',
            'BVN',
            'NIN',
            'TIN',
            'IDType',
            'IDNo',
            'IDIssueDate',
            'IDExpiryDate',
            'MaritalStatus',
            'NextOfKin',
            'NextOfKinPhoneNo',
            'NextOfKinAddress',
            'RegNo',
            'Sector',
            'PEP',
            'PEPDetails'
        ];

        $sampleData = [
            [
                '1',
                'Mr.',
                'BABATUNJI',
                'FABAMIGBE',
                'NULL',
                'Individual',
                'MALE',
                'Lagos',
                '11/16/1974',
                '10B, PROF. KIUMI AKINGBEYIN STR. LEKKI PHASE 1. LAGOS',
                'Lagos',
                'Lekki',
                'fabamigbet@yahoo.com',
                '8182141204',
                'NULL',
                'NULL',
                'NULL',
                'NULL',
                'RETAIL 1',
                'Business',
                'UNK',
                'Active',
                '1/21/2019',
                '22142117912',
                '98785898310',
                'NULL',
                '0',
                'A05525652',
                'NULL',
                'NULL',
                '0',
                'NULL',
                'NULL',
                'NULL',
                'NULL',
                'Education',
                'NO',
                'NULL'
            ]
        ];

        $csvContent = implode(',', $headers) . "\n";
        foreach ($sampleData as $row) {
            $csvContent .= '"' . implode('","', $row) . '"' . "\n";
        }

        return response($csvContent)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="customers_import_template.csv"');
    }
}