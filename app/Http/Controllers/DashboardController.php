<?php
// app/Http/Controllers/DashboardController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        
        // Check if this is the user's first login
        $isFirstLogin = false;
        if ($user->last_login_at === null || $user->last_login_at->eq($user->updated_at)) {
            $isFirstLogin = true;
        }

        // Get quick stats (these will be replaced with real data later)
        $stats = [
            'total_accounts' => 0,
            'total_transactions' => 0,
            'total_customers' => 0,
            'data_size' => '0 GB',
        ];

        // Recent activities (placeholder)
        $recentActivities = collect([]);

        return view('backend.dashboard.index', compact('user', 'isFirstLogin', 'stats', 'recentActivities'));
    }
}