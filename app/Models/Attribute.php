<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use App\Support\CatalogCache;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A specification field ("Chipset", "Memory Type", "Wattage"…), shared across categories.
 * System fields have fixed codes that the PC Builder compatibility rules rely on.
 */
#[Fillable(['name', 'code', 'unit', 'help', 'is_system'])]
class Attribute extends Model
{
    use LogsActivity;

    /** code => [name, unit, help] — created by the catalog seeder, locked in the admin. */
    public const SYSTEM = [
        'cpu_socket' => ['Socket', null, 'CPU socket, e.g. LGA1700, AM5. Must match between processor, motherboard and CPU cooler.'],
        'memory_type' => ['Memory Type', null, 'DDR4 / DDR5. Must match between RAM and motherboard.'],
        'form_factor' => ['Form Factor', null, 'ATX, Micro-ATX, Mini-ITX. Motherboard must fit the casing.'],
        'tdp' => ['TDP', 'W', 'Power the part draws, used to estimate PSU size.'],
        'psu_wattage' => ['Wattage', 'W', 'Power supply output.'],
        'gpu_length' => ['Card Length', 'mm', 'Graphics card length.'],
        'max_gpu_length' => ['Max GPU Length', 'mm', 'Longest graphics card the casing fits.'],
    ];

    protected function casts(): array
    {
        return ['is_system' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::saved(fn () => CatalogCache::flush());
        static::deleted(fn () => CatalogCache::flush());
    }

    /** A stored value as shown to customers: bare numbers get the unit ("650" → "650 W"). */
    public static function display(string $value, ?string $unit): string
    {
        return $unit && is_numeric($value) ? "{$value} {$unit}" : $value;
    }

    /** A value as entered, ready to store: trimmed, and "650W" / "650 W" → "650" when the field has that unit. */
    public static function normalise(?string $value, ?string $unit): string
    {
        $value = trim(preg_replace('/\s+/', ' ', (string) $value));

        if ($unit && preg_match('/^(\d+(?:\.\d+)?)\s*'.preg_quote($unit, '/').'$/i', $value, $m)) {
            return $m[1];
        }

        return $value;
    }

    public function templates(): HasMany
    {
        return $this->hasMany(CategoryAttribute::class);
    }

    public function values(): HasMany
    {
        return $this->hasMany(ProductSpec::class);
    }
}
