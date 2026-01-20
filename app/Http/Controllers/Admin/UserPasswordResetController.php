<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Department;
use App\Notifications\PasswordResetNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class UserPasswordResetController extends Controller
{
    
    /**
     * Display the password reset management page
     */
    public function index(Request $request)
    {
        // Check if user has permission
        if (!auth()->user()->can('password-reset index')) {
            flash()
            ->option('position', 'bottom-right')
            ->error('You do not have permission to access this page.');
            return redirect()->route('dashboard');
        }
        $query = User::with(['department']);

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('employee', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            switch ($request->status) {
                case 'locked':
                    $query->where('locked_until', '>', now());
                    break;
                case 'force_change':
                    $query->where('password_change_required', true);
                    break;
                case 'failed_attempts':
                    $query->where('password_attempts', '>', 0);
                    break;
            }
        }

        // Show users that might need password reset (using existing columns only)
        if (!$request->filled('search') && !$request->filled('status')) {
            $query->where(function($q) {
                $q->where('password_change_required', true)
                  ->orWhere('locked_until', '>', now())
                  ->orWhere('password_attempts', '>', 0);
            });
        }

        $users = $query->orderBy('updated_at', 'desc')->paginate(20);

        // Calculate stats using existing columns
        $totalResets = User::whereDate('password_changed_at', today())->count();
        $pendingResets = User::where('password_change_required', true)->count();
        $lockedAccounts = User::where('locked_until', '>', now())->count();
        $temporaryPasswords = 0; // Will be 0 until we add the column

        return view('admin.password-change.index', compact([
            'users',
            'totalResets',
            'pendingResets', 
            'lockedAccounts',
            'temporaryPasswords'
        ]));
    }

    /**
     * Get user details for password reset modal
     */
    public function getUserDetails($userId)
    {
        try {
            $user = User::with('department')->findOrFail($userId);
            
            // Get recent password changes from login_history
            $passwordHistory = $this->getPasswordHistory($user);
            
            return response()->json([
                'success' => true,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->preferred_name ?: ($user->first_name . ' ' . $user->last_name) ?: $user->name,
                    'email' => $user->email,
                    'department' => $user->department->name ?? null,
                    'employee_id' => $user->employee,
                    'avatar' => $user->profile_photo ? asset('storage/' . $user->profile_photo) : null,
                    'password_history' => $passwordHistory,
                    'last_password_change' => $user->password_changed_at,
                    'can_reset' => true
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting user details for password reset: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error loading user details'
            ], 500);
        }
    }

    /**
     * Reset single user password
     */
    public function resetPassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
                'password' => 'required|string|min:8|max:255',
                'method' => 'required|in:auto,manual,temporary',
                'send_email' => 'boolean',
                'send_sms' => 'boolean',
                'force_password_change' => 'boolean',
                'logout_all_sessions' => 'boolean',
                'custom_message' => 'nullable|string|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            $user = User::findOrFail($request->user_id);
            $adminUser = auth()->user();

            DB::beginTransaction();

            // Store password reset info in login history
            $resetInfo = [
                'date' => now()->toISOString(),
                'method' => $request->method,
                'reset_by' => $adminUser->name,
                'reason' => 'Admin password reset',
                'admin_id' => $adminUser->id,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent()
            ];

            // Update login history
            $loginHistory = $user->login_history ?? [];
            array_unshift($loginHistory, $resetInfo);
            $loginHistory = array_slice($loginHistory, 0, 10); // Keep last 10 entries

            // Update user password and related fields
            $updateData = [
                'password' => Hash::make($request->password),
                'password_changed_at' => now(),
                'password_change_required' => $request->force_password_change ?? true,
                'login_history' => $loginHistory,
                'updated_by' => $adminUser->id,
                'password_attempts' => 0, // Reset failed attempts
                'locked_until' => null, // Unlock account if locked
            ];

            // Handle temporary password (if column exists)
            if (\Schema::hasColumn('users', 'is_temporary_password')) {
                $updateData['is_temporary_password'] = ($request->method === 'temporary');
                if ($request->method === 'temporary') {
                    $updateData['password_expires_at'] = now()->addDays(7);
                }
            }

            // Logout from all sessions if requested
            if ($request->logout_all_sessions) {
                $updateData['force_logout'] = true;
                $this->logoutUserFromAllSessions($user);
            }

            $user->update($updateData);

            // Send notifications
            if ($request->send_email) {
                $this->sendPasswordResetEmail($user, $request->password, $request->custom_message, $request->method);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Password reset successfully',
                'details' => $this->getResetSuccessMessage($request)
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error resetting password: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error resetting password. Please try again.'
            ], 500);
        }
    }

    /**
     * Unlock user account
     */
    public function unlockAccount($userId)
    {
        try {
            $user = User::findOrFail($userId);
            $adminUser = auth()->user();

            $user->update([
                'locked_until' => null,
                'password_attempts' => 0,
                'updated_by' => $adminUser->id
            ]);

            Log::info("Account unlocked for user {$user->id} by admin {$adminUser->id}");

            return response()->json([
                'success' => true,
                'message' => 'Account unlocked successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error unlocking account: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error unlocking account'
            ], 500);
        }
    }

    /**
     * Force password change on next login
     */
    public function forcePasswordChange($userId)
    {
        try {
            $user = User::findOrFail($userId);
            $adminUser = auth()->user();

            $user->update([
                'password_change_required' => true,
                'updated_by' => $adminUser->id
            ]);

            Log::info("Force password change set for user {$user->id} by admin {$adminUser->id}");

            return response()->json([
                'success' => true,
                'message' => 'User will be required to change password on next login'
            ]);

        } catch (\Exception $e) {
            Log::error('Error setting force password change: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error setting force password change'
            ], 500);
        }
    }

    /**
     * Clear failed login attempts
     */
    public function clearFailedAttempts($userId)
    {
        try {
            $user = User::findOrFail($userId);
            $adminUser = auth()->user();

            $user->update([
                'password_attempts' => 0,
                'locked_until' => null,
                'updated_by' => $adminUser->id
            ]);

            Log::info("Failed attempts cleared for user {$user->id} by admin {$adminUser->id}");

            return response()->json([
                'success' => true,
                'message' => 'Failed login attempts cleared successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error clearing failed attempts: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error clearing failed attempts'
            ], 500);
        }
    }

    /**
     * Get password history for user
     */
    public function getPasswordHistory($userId)
    {
        try {
            $user = User::findOrFail($userId);
            
            // Get password history from login_history
            $loginHistory = $user->login_history ?? [];
            $passwordHistory = collect($loginHistory)->filter(function($entry) {
                return isset($entry['method']) && in_array($entry['method'], ['auto', 'manual', 'temporary']);
            })->take(20);

            $html = view('admin.password-change.partials.password-history', [
                'user' => $user,
                'history' => $passwordHistory
            ])->render();

            return response()->json([
                'success' => true,
                'html' => $html
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting password history: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error loading password history'
            ], 500);
        }
    }

    /**
     * Get bulk user details for bulk password reset
     */
    public function getBulkUserDetails(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_ids' => 'required|array',
                'user_ids.*' => 'exists:users,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid user selection'
                ], 400);
            }

            $users = User::with('department')
                ->whereIn('id', $request->user_ids)
                ->get()
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->preferred_name ?: ($user->first_name . ' ' . $user->last_name) ?: $user->name,
                        'email' => $user->email,
                        'department' => $user->department->name ?? 'No Department',
                        'avatar' => $user->profile_photo ? asset('storage/' . $user->profile_photo) : null,
                    ];
                });

            return response()->json([
                'success' => true,
                'users' => $users
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting bulk user details: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error loading user details'
            ], 500);
        }
    }

    /**
     * Generate secure password
     */
    private function generateSecurePassword($length = 12)
    {
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $numbers = '0123456789';
        $symbols = '!@#$%^&*()_+-=[]{}|;:,.<>?';
        
        $chars = $lowercase . $uppercase . $numbers . $symbols;
        
        $password = '';
        
        // Ensure at least one character from each type
        $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];
        $password .= $symbols[random_int(0, strlen($symbols) - 1)];
        
        // Fill the rest randomly
        for ($i = 4; $i < $length; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }
        
        return str_shuffle($password);
    }

    /**
     * Get password history for user (private method)
     */
    // private function getPasswordHistory($user)
    // {
    //     $loginHistory = $user->login_history ?? [];
        
    //     // Filter for password reset entries
    //     return array_filter($loginHistory, function($entry) {
    //         return isset($entry['method']) && in_array($entry['method'], ['auto', 'manual', 'temporary']);
    //     });
    // }

    /**
     * Send password reset email
     */
    private function sendPasswordResetEmail($user, $password, $customMessage = null, $method = 'manual')
    {
        try {
            $user->notify(new PasswordResetNotification($password, $customMessage, $method));
        } catch (\Exception $e) {
            Log::error("Failed to send password reset email to user {$user->id}: " . $e->getMessage());
        }
    }

    /**
     * Logout user from all sessions
     */
    private function logoutUserFromAllSessions($user)
    {
        try {
            // Invalidate all sessions for this user
            DB::table('sessions')->where('user_id', $user->id)->delete();
            
            // Set force_logout flag
            $user->update(['force_logout' => true]);
            
        } catch (\Exception $e) {
            Log::error("Failed to logout user {$user->id} from all sessions: " . $e->getMessage());
        }
    }

    /**
     * Get success message based on reset type
     */
    private function getResetSuccessMessage($request)
    {
        $messages = [];
        
        if ($request->send_email) {
            $messages[] = 'Email notification sent';
        }
        
        if ($request->send_sms) {
            $messages[] = 'SMS notification sent';
        }
        
        if ($request->force_password_change) {
            $messages[] = 'User will be required to change password on next login';
        }
        
        if ($request->logout_all_sessions) {
            $messages[] = 'User logged out from all devices';
        }

        return empty($messages) ? 'Password reset completed' : implode('. ', $messages) . '.';
    }

    /**
     * Bulk password reset
     */
    public function bulkResetPassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_ids' => 'required|array|min:1',
                'user_ids.*' => 'exists:users,id',
                'password_type' => 'required|in:auto,pattern,temporary',
                'password_length' => 'required|integer|min:8|max:32',
                'send_email' => 'boolean',
                'force_password_change' => 'boolean',
                'logout_all_sessions' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            $users = User::whereIn('id', $request->user_ids)->get();
            $adminUser = auth()->user();
            $successCount = 0;
            $failedCount = 0;
            $results = [];

            DB::beginTransaction();

            foreach ($users as $user) {
                try {
                    // Generate password based on type
                    $password = $this->generateBulkPassword($request->password_type, $request->password_length, $user);
                    
                    // Store bulk reset info in login history
                    $resetInfo = [
                        'date' => now()->toISOString(),
                        'method' => 'bulk_' . $request->password_type,
                        'reset_by' => $adminUser->name,
                        'reason' => 'Bulk password reset',
                        'admin_id' => $adminUser->id,
                        'ip' => request()->ip(),
                        'user_agent' => request()->userAgent()
                    ];

                    $loginHistory = $user->login_history ?? [];
                    array_unshift($loginHistory, $resetInfo);
                    $loginHistory = array_slice($loginHistory, 0, 10);
                    
                    // Update user
                    $updateData = [
                        'password' => Hash::make($password),
                        'password_changed_at' => now(),
                        'password_change_required' => $request->force_password_change ?? true,
                        'updated_by' => $adminUser->id,
                        'password_attempts' => 0,
                        'locked_until' => null,
                        'login_history' => $loginHistory,
                    ];

                    // Handle temporary password if column exists
                    if (\Schema::hasColumn('users', 'is_temporary_password') && $request->password_type === 'temporary') {
                        $updateData['is_temporary_password'] = true;
                        $updateData['password_expires_at'] = now()->addDays(7);
                    }

                    if ($request->logout_all_sessions) {
                        $updateData['force_logout'] = true;
                        $this->logoutUserFromAllSessions($user);
                    }

                    $user->update($updateData);

                    // Send email notification
                    if ($request->send_email) {
                        $this->sendPasswordResetEmail($user, $password, null, 'bulk_reset');
                    }

                    $results[] = [
                        'user_id' => $user->id,
                        'user_name' => $user->name,
                        'success' => true,
                        'password' => $password
                    ];

                    $successCount++;

                } catch (\Exception $e) {
                    Log::error("Failed to reset password for user {$user->id}: " . $e->getMessage());
                    
                    $results[] = [
                        'user_id' => $user->id,
                        'user_name' => $user->name,
                        'success' => false,
                        'error' => $e->getMessage()
                    ];

                    $failedCount++;
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Bulk password reset completed. {$successCount} successful, {$failedCount} failed.",
                'successful_count' => $successCount,
                'failed_count' => $failedCount,
                'results' => $results
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in bulk password reset: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error performing bulk password reset. Please try again.'
            ], 500);
        }
    }

    /**
     * Generate password for bulk operations
     */
    private function generateBulkPassword($type, $length, $user)
    {
        switch ($type) {
            case 'auto':
                return $this->generateSecurePassword($length);
                
            case 'pattern':
                // Pattern: FirstnameYYMM + random numbers
                $firstName = $user->first_name ?: explode(' ', $user->name)[0];
                $firstName = substr(ucfirst($firstName), 0, 6);
                $dateCode = now()->format('ym');
                $randomNumbers = str_pad(random_int(0, 999), 3, '0', STR_PAD_LEFT);
                return $firstName . $dateCode . $randomNumbers;
                
            case 'temporary':
                // Temporary: Easy to remember but secure
                $adjectives = ['Quick', 'Smart', 'Bright', 'Fast', 'Cool', 'Strong', 'Safe', 'Swift'];
                $nouns = ['Cat', 'Dog', 'Bird', 'Fish', 'Star', 'Moon', 'Sun', 'Tree'];
                $adjective = $adjectives[array_rand($adjectives)];
                $noun = $nouns[array_rand($nouns)];
                $numbers = str_pad(random_int(0, 999), 3, '0', STR_PAD_LEFT);
                return $adjective . $noun . $numbers;
                
            default:
                return $this->generateSecurePassword($length);
        }
    }

    /**
     * Lock user account
     */
    public function lockAccount(Request $request, $userId)
    {
        try {
            $validator = Validator::make($request->all(), [
                'duration' => 'nullable|integer|min:1|max:168', // Max 1 week
                'reason' => 'nullable|string|max:255'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid input data'
                ], 400);
            }

            $user = User::findOrFail($userId);
            $adminUser = auth()->user();

            // Default lock for 24 hours if no duration specified
            $lockDuration = $request->duration ?? 24;
            $lockUntil = now()->addHours($lockDuration);

            $user->update([
                'locked_until' => $lockUntil,
                'updated_by' => $adminUser->id
            ]);

            Log::info("Account locked for user {$user->id} by admin {$adminUser->id} until {$lockUntil}");

            return response()->json([
                'success' => true,
                'message' => "Account locked until {$lockUntil->format('M d, Y H:i')}"
            ]);

        } catch (\Exception $e) {
            Log::error('Error locking account: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error locking account'
            ], 500);
        }
    }

    /**
     * Generate temporary password for specific user
     */
    public function generateTemporaryPassword(Request $request, $userId)
    {
        try {
            $validator = Validator::make($request->all(), [
                'expires_in_hours' => 'nullable|integer|min:1|max:168',
                'send_email' => 'boolean',
                'custom_message' => 'nullable|string|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid input data'
                ], 400);
            }

            $user = User::findOrFail($userId);
            $adminUser = auth()->user();

            // Generate temporary password
            $adjectives = ['Quick', 'Smart', 'Bright', 'Fast', 'Cool', 'Strong', 'Safe', 'Swift', 'Bold', 'Prime'];
            $nouns = ['Cat', 'Dog', 'Bird', 'Fish', 'Star', 'Moon', 'Sun', 'Tree', 'Rock', 'Wave'];
            $adjective = $adjectives[array_rand($adjectives)];
            $noun = $nouns[array_rand($nouns)];
            $numbers = str_pad(random_int(0, 999), 3, '0', STR_PAD_LEFT);
            $tempPassword = $adjective . $noun . $numbers;

            // Set expiry (default 24 hours)
            $expiresInHours = $request->expires_in_hours ?? 24;
            $expiresAt = now()->addHours($expiresInHours);

            // Store reset info in login history
            $resetInfo = [
                'date' => now()->toISOString(),
                'method' => 'temporary',
                'reset_by' => $adminUser->name,
                'reason' => 'Temporary password generated',
                'admin_id' => $adminUser->id,
                'expires_at' => $expiresAt->toISOString()
            ];

            $loginHistory = $user->login_history ?? [];
            array_unshift($loginHistory, $resetInfo);
            $loginHistory = array_slice($loginHistory, 0, 10);

            // Update user
            $updateData = [
                'password' => Hash::make($tempPassword),
                'password_changed_at' => now(),
                'password_change_required' => true,
                'updated_by' => $adminUser->id,
                'password_attempts' => 0,
                'locked_until' => null,
                'login_history' => $loginHistory,
            ];

            // Add temporary password fields if they exist
            if (\Schema::hasColumn('users', 'is_temporary_password')) {
                $updateData['is_temporary_password'] = true;
                $updateData['password_expires_at'] = $expiresAt;
            }

            $user->update($updateData);

            // Send email if requested
            if ($request->send_email) {
                $this->sendPasswordResetEmail($user, $tempPassword, $request->custom_message, 'temporary');
            }

            return response()->json([
                'success' => true,
                'message' => 'Temporary password generated successfully',
                'password' => $tempPassword,
                'expires_at' => $expiresAt->format('M d, Y H:i')
            ]);

        } catch (\Exception $e) {
            Log::error('Error generating temporary password: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error generating temporary password'
            ], 500);
        }
    }
}