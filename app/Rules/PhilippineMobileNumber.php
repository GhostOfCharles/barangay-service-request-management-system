<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * A Philippine mobile number: 09XXXXXXXXX or +639XXXXXXXXX (11 digits starting 09).
 *
 * Form Requests call normalize() before validating, so residents may type spaces, dashes, or the +63
 * prefix, and the number is always stored the same way (09XXXXXXXXX).
 */
class PhilippineMobileNumber implements ValidationRule
{
    public const PATTERN = '/^09\d{9}$/';

    /**
     * Strip spaces, dashes, dots, and parentheses, and turn +639… or 639… into 09….
     */
    public static function normalize(mixed $value): mixed
    {
        if (! is_string($value) || trim($value) === '') {
            return $value;
        }

        $number = preg_replace('/[\s\-.()]/', '', $value);

        return match (true) {
            str_starts_with($number, '+639') => '0'.substr($number, 3),
            str_starts_with($number, '639') && strlen($number) === 12 => '0'.substr($number, 2),
            default => $number,
        };
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! preg_match(self::PATTERN, $value)) {
            $fail('Enter a Philippine mobile number, for example 09171234567 or +639171234567.');
        }
    }
}
