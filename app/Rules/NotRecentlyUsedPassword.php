<?php
// app/Rules/NotRecentlyUsedPassword.php

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Hash;

/**
 * Rejects the user's current password and their last N passwords.
 */
class NotRecentlyUsedPassword implements ValidationRule
{
    public function __construct(private User $user)
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            return;
        }

        if ($this->user->password && Hash::check($value, $this->user->password)) {
            $fail('Your new password must be different from your current password.');
            return;
        }

        $limit = (int) config('security.password.history', 5);

        $recent = $this->user->passwordHistories()
            ->latest('created_at')
            ->latest('id')
            ->limit($limit)
            ->pluck('password');

        foreach ($recent as $hash) {
            if (Hash::check($value, $hash)) {
                $fail("You cannot reuse any of your last {$limit} passwords.");
                return;
            }
        }
    }
}
