<?php

namespace App\Helpers;

class SepaReference
{
    /**
     * Cleans the reference by removing non-alphanumeric characters and converting to uppercase.
     */
    public static function clean(string $reference): string
    {
        return strtoupper(preg_replace("/[^0-9a-zA-Z]/s", "", $reference));
    }

    /**
     * Converts the reference to a numeric form for modulo calculations.
     */
    public static function toNumeric(string $reference): string
    {
        return preg_replace_callback(
            "/([0-9])|([A-Z])|([a-z])|./s",
            function ($m) {
                if (!empty($m[2])) return ord($m[2]) - ord("A") + 10;
                if (!empty($m[3])) return ord($m[3]) - ord("a") + 10;
                return $m[1] ?? "";
            },
            $reference
        );
    }

    /**
     * Calculates modulo 97 for a large number.
     */
    public static function mod97(string $number): int
    {
        // Use GMP if available, otherwise use bcmath or manual calculation
        if (function_exists('gmp_mod')) {
            return (int) gmp_mod($number, 97);
        } elseif (function_exists('bcmod')) {
            return (int) bcmod($number, '97');
        } else {
            // Manual modulo for large numbers
            $remainder = 0;
            for ($i = 0; $i < strlen($number); $i++) {
                $remainder = ($remainder * 10 + (int)$number[$i]) % 97;
            }
            return $remainder;
        }
    }

    /**
     * Generates a complete SEPA RF reference based on the base part.
     */
    public static function generate(string $basePart, bool $group = true): string
    {
        $basePart = self::clean($basePart);
        $numeric = self::toNumeric($basePart . "RF00");
        $checkDigits = sprintf("%02d", 98 - self::mod97($numeric));
        $reference = "RF" . $checkDigits . $basePart;

        return $group ? self::group($reference) : $reference;
    }

    /**
     * Groups the reference into 4-character blocks.
     */
    public static function group(string $reference): string
    {
        $reference = self::clean($reference);
        return trim(chunk_split($reference, 4, " "));
    }

    /**
     * Validates if the reference is correct.
     */
    public static function validate(string $reference): bool
    {
        $reference = self::clean($reference);

        if (strlen($reference) > 25 || strlen($reference) <= 4) {
            return false;
        }

        $numeric = self::toNumeric(substr($reference, 4) . substr($reference, 0, 4));
        return self::mod97($numeric) === 1;
    }
}
