<?php

namespace App\Support\PcBuilder;

use App\Models\Product;
use App\Support\CatalogCache;

/**
 * Reads the PC Builder system fields (Attribute::SYSTEM) from a product's spec values (eager-load `specValues`).
 * Values are free text typed by staff, so everything here is forgiving: "LGA 1700", "lga1700" and "LGA1700" match.
 */
class Specs
{
    public static function raw(Product $product, string $code): ?string
    {
        $id = CatalogCache::data()['system_fields'][$code] ?? null;
        $value = $id ? $product->specValues->firstWhere('attribute_id', $id)?->value : null;

        return filled($value) ? trim($value) : null;
    }

    /** First number in the value (TDP, wattage, lengths), or null. */
    public static function number(Product $product, string $code): ?int
    {
        return preg_match('/\d+(?:\.\d+)?/', (string) self::raw($product, $code), $m) ? (int) round((float) $m[0]) : null;
    }

    /**
     * Sockets listed in the value ("LGA1700, AM5 / AM4") — upper-case, no spaces. "LGA115X" stands for LGA1150/1151/….
     *
     * @return list<string>
     */
    public static function sockets(Product $product): array
    {
        $value = self::raw($product, 'cpu_socket');

        return $value === null ? [] : collect(preg_split('/\s*(?:[,\/;|&+]|\band\b|\bor\b)\s*/i', $value))
            ->map(fn ($s) => strtoupper(preg_replace('/[\s-]+/', '', $s)))
            ->filter()->unique()->values()->all();
    }

    /** Whether two socket lists share a socket (with "X" wildcards, e.g. LGA115X ↔ LGA1151). */
    public static function socketsMatch(array $a, array $b): bool
    {
        foreach ($a as $x) {
            foreach ($b as $y) {
                if ($x === $y || self::wildcard($x, $y) || self::wildcard($y, $x)) {
                    return true;
                }
            }
        }

        return false;
    }

    protected static function wildcard(string $pattern, string $socket): bool
    {
        return preg_match('/^([A-Z]+\d+)X+$/', $pattern, $m) === 1
            && str_starts_with($socket, $m[1])
            && strlen($socket) === strlen($pattern);
    }

    /**
     * System memory generations in the value ("DDR5 4800 / DDR4 3200" → DDR5, DDR4). Graphics memory (GDDR) is ignored.
     *
     * @return list<string>
     */
    public static function memoryTypes(Product $product): array
    {
        preg_match_all('/(?<![A-Z])DDR\s?(\d)/i', (string) self::raw($product, 'memory_type'), $m);

        return collect($m[1])->map(fn ($n) => 'DDR'.$n)->unique()->values()->all();
    }

    /** Board size: Mini-ITX 1 < Micro-ATX 2 < ATX 3 < E-ATX 4 (the largest one listed), or null. */
    public static function boardSize(Product $product): ?int
    {
        $value = self::raw($product, 'form_factor');

        if ($value === null) {
            return null;
        }

        $sizes = collect(preg_split('/\s*[,\/;|&]\s*/', strtolower($value)))->map(function (string $part) {
            $part = preg_replace('/[\s._-]+/', '', $part);

            return match (true) {
                str_contains($part, 'eatx') || str_contains($part, 'extendedatx') => 4,
                str_contains($part, 'microatx') || str_contains($part, 'matx') || str_contains($part, 'uatx') || str_contains($part, 'µatx') => 2,
                str_contains($part, 'itx') => 1,
                str_contains($part, 'atx') || str_contains($part, 'midtower') || str_contains($part, 'fulltower') => 3,
                default => null,
            };
        })->filter();

        return $sizes->max();
    }

    public static function sizeName(int $size): string
    {
        return [1 => 'Mini-ITX', 2 => 'Micro-ATX', 3 => 'ATX', 4 => 'E-ATX'][$size] ?? '?';
    }
}
