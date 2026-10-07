<?php

namespace App\Support;

use Illuminate\Support\Str;

/** Warranty text as staff type it ("3 Years", "Lifetime", "No Warranty") turned into a label. */
class Warranty
{
    /** "3 Years" → "3 Years warranty"; "No Warranty" stays as it is; empty → null. */
    public static function label(?string $warranty): ?string
    {
        $warranty = trim((string) $warranty);

        return match (true) {
            $warranty === '' => null,
            Str::contains($warranty, 'warranty', ignoreCase: true) => $warranty,
            default => $warranty.' warranty',
        };
    }

    public static function none(?string $warranty): bool
    {
        return (bool) preg_match('/^(no|without|none)\b/i', trim((string) $warranty));
    }
}
