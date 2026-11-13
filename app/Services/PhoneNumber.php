<?php

namespace App\Services;

/**
 * Bangladeshi mobile numbers, the login identifier for Krishi Bondhu.
 * Numbers are stored in the local 11-digit form: 01XXXXXXXXX.
 */
class PhoneNumber
{
    /** 01, then an operator digit 3–9, then 8 digits. */
    public const PATTERN = '/^01[3-9][0-9]{8}$/';

    /**
     * Turn what people type ("+880 1712-345678", "8801712345678", "০১৭১২৩৪৫৬৭৮")
     * into 01XXXXXXXXX. Anything that still is not a valid number is returned
     * as typed (without spaces) so validation can reject it.
     */
    public static function normalize(?string $input): ?string
    {
        if ($input === null) {
            return null;
        }

        // Bangla digits → ASCII digits, then drop spaces, dashes and brackets.
        $number = strtr($input, array_combine(
            ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'],
            ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'],
        ));
        $number = preg_replace('/[\s\-().]/u', '', $number);

        // Country code: +8801… or 8801… → 01…
        if (preg_match('/^\+?880(1[0-9]{9})$/', $number, $matches)) {
            $number = '0'.$matches[1];
        }

        return $number;
    }

    public static function isValid(?string $number): bool
    {
        return $number !== null && preg_match(self::PATTERN, $number) === 1;
    }
}
