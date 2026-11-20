<?php
// app/Rules/PasswordNotPersonal.php

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;

/**
 * Rejects passwords built from the user's own name or email address, and
 * the company name, which are the first things an attacker tries.
 */
class PasswordNotPersonal implements ValidationRule
{
    public function __construct(private User $user)
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            return;
        }

        $password = Str::lower($value);

        $words = collect(preg_split('/[\s.\-_@]+/', Str::lower($this->user->name.' '.Str::before($this->user->email, '@'))))
            ->push('lukefield')
            ->filter(fn ($word) => is_string($word) && Str::length($word) >= 4)
            ->unique();

        foreach ($words as $word) {
            if (Str::contains($password, $word)) {
                $fail('Your password must not contain your name, your email address or the company name.');
                return;
            }
        }
    }
}
