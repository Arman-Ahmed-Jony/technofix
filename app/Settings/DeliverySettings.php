<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Delivery options shown at checkout — edited in Admin → Settings → Delivery.
 *
 * Each option: code, label, eta, fee (৳), type (delivery|pickup), area (all|only|except),
 * districts (list, used by only/except), active (bool).
 */
class DeliverySettings extends Settings
{
    // List of option arrays (see class docblock). No @var here: spatie/laravel-settings
    // treats docblock types as casts and can't resolve nested array shapes.
    public array $options;

    /** Free home delivery at or above this subtotal (null = off). */
    public ?int $free_delivery_over;

    public static function group(): string
    {
        return 'delivery';
    }

    /**
     * Active options in admin order, keyed by code, with defaults filled in.
     *
     * @return array<string, array<string, mixed>>
     */
    public function active(): array
    {
        return collect($this->options)
            ->map(fn (array $option) => [
                'code' => (string) $option['code'],
                'label' => (string) $option['label'],
                'eta' => (string) ($option['eta'] ?? ''),
                'fee' => (int) ($option['fee'] ?? 0),
                'type' => ($option['type'] ?? 'delivery') === 'pickup' ? 'pickup' : 'delivery',
                'area' => in_array($option['area'] ?? 'all', ['only', 'except'], true) ? $option['area'] : 'all',
                'districts' => array_values((array) ($option['districts'] ?? [])),
                'active' => (bool) ($option['active'] ?? true),
            ])
            ->filter(fn (array $option) => $option['active'])
            ->keyBy('code')
            ->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(?string $code): ?array
    {
        return $code === null ? null : ($this->active()[$code] ?? null);
    }

    /**
     * Whether an option can deliver to the given district (pickup and "all districts" always can).
     *
     * @param  array<string, mixed>  $option
     */
    public static function serves(array $option, ?string $district): bool
    {
        if ($option['type'] === 'pickup' || $option['area'] === 'all' || ! $district) {
            return true;
        }

        $listed = in_array($district, $option['districts'], true);

        return $option['area'] === 'only' ? $listed : ! $listed;
    }

    /**
     * @param  array<string, mixed>  $option
     */
    public function feeFor(array $option, int $subtotal): int
    {
        if ($option['type'] === 'delivery' && $this->free_delivery_over && $subtotal >= $this->free_delivery_over) {
            return 0;
        }

        return $option['fee'];
    }
}
