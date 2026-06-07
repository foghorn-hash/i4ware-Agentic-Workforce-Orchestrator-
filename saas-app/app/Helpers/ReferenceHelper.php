<?php

namespace App\Helpers;

class ReferenceHelper
{
    /**
     * Generates a Finnish reference number with mod10 check digit.
     *
     * @param string|int $baseNumber Base number without check digit
     * @return string Reference number with check digit
     */
    public static function generate(string|int $baseNumber): string
    {
        $digits = str_split(strrev((string)$baseNumber));
        $multipliers = [7, 3, 1];
        $sum = 0;

        foreach ($digits as $i => $digit) {
            $sum += intval($digit) * $multipliers[$i % 3];
        }

        $remainder = $sum % 10;
        $checkDigit = ($remainder === 0) ? 0 : 10 - $remainder;

        return $baseNumber . $checkDigit;
    }

    /**
     * Validates a Finnish reference number.
     *
     * @param string $reference Reference number with check digit
     * @return bool True if valid, false otherwise
     */
    public static function validate(string $reference): bool
    {
        if (strlen($reference) < 2) {
            return false;
        }

        $baseNumber = substr($reference, 0, -1);
        $expected = self::generate($baseNumber);

        return $expected === $reference;
    }
}
