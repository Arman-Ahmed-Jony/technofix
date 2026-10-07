<?php

namespace App\Support;

/**
 * Bangladeshi mobile numbers.
 */
class Phone
{
    public const PATTERN = '/^01[3-9]\d{8}$/';

    /**
     * "+880 1712-345678", "8801712345678", "01712 345678" and "1712345678" (Excel drops the leading 0) all become
     * "01712345678".
     */
    public static function normalize(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        return match (true) {
            str_starts_with($digits, '880') && strlen($digits) === 13 => substr($digits, 2),
            str_starts_with($digits, '1') && strlen($digits) === 10 => '0'.$digits,
            default => $digits,
        };
    }

    public static function isValid(?string $phone): bool
    {
        return (bool) preg_match(self::PATTERN, self::normalize($phone));
    }
}
