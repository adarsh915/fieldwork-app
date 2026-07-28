<?php

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ActiveFieldWorker implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $user = User::find($value);

        if (! $user) {
            $fail('The selected employee does not exist.');
            return;
        }

        if (! $user->is_active) {
            $fail('The selected employee is inactive and cannot be assigned parties.');
            return;
        }

        // Must have the Field Worker role to be assignable
        if (! $user->hasRole('Field Worker')) {
            $fail('Parties can only be assigned to employees with the Field Worker role.');
        }
    }
}
