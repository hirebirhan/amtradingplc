<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PhoneNumber implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!preg_match('/^\+?[0-9]+$/', $value)) {
            $fail('The :attribute must be a valid phone number containing only digits and an optional leading +.');
        }

        if (strlen($value) < 7 || strlen($value) > 20) {
            $fail('The :attribute must be between 7 and 20 characters.');
        }
    }
}
