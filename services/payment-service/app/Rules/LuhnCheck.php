<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

// Checks a card number with the Luhn formula, the same check real card forms use
// to catch typing mistakes before sending the card to the bank.
class LuhnCheck implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! ctype_digit($value) || ! $this->passesLuhn($value)) {
            $fail('The card number is not valid.');
        }
    }

    // From the right side, double every second digit. If the result is more than 9, take away 9.
    // The total of all digits must end with 0.
    private function passesLuhn(string $cardNumber): bool
    {
        $total = 0;
        $digits = array_reverse(str_split($cardNumber));

        foreach ($digits as $position => $digit) {
            $digit = (int) $digit;

            if ($position % 2 === 1) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }

            $total += $digit;
        }

        return $total % 10 === 0;
    }
}
