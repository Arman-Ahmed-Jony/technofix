<?php

namespace App\Support;

class Money
{
    /**
     * Format a whole-taka amount for display, e.g. 15500 → "৳15,500".
     */
    public static function format(?int $amount): string
    {
        return $amount === null ? '' : '৳'.number_format($amount);
    }
}
