<?php

namespace App\Support;

use App\Settings\SecuritySettings;
use App\Settings\StoreSettings;
use Closure;
use Symfony\Component\HttpFoundation\IpUtils;

/**
 * Phone numbers and internet addresses that may not order or send forms (fake orders, abuse) —
 * Admin → Settings → Security, or "Block this number" on an order / lead.
 */
class Blocklist
{
    protected static function settings(): ?SecuritySettings
    {
        return rescue(fn () => app(SecuritySettings::class), null, report: false);
    }

    public static function phoneBlocked(?string $phone): bool
    {
        $phone = Phone::normalize($phone);

        return $phone !== '' && in_array($phone, self::phones(), true);
    }

    /** @return array<int, string> normalised numbers */
    public static function phones(): array
    {
        return collect(self::settings()?->blocked_phones ?? [])
            ->map(fn ($row) => Phone::normalize(is_array($row) ? ($row['phone'] ?? '') : $row))
            ->filter()
            ->values()
            ->all();
    }

    public static function ipBlocked(?string $ip): bool
    {
        $ranges = collect(self::settings()?->blocked_ips ?? [])
            ->map(fn ($row) => trim((string) (is_array($row) ? ($row['ip'] ?? '') : $row)))
            ->filter()
            ->values()
            ->all();

        return $ip && $ranges && IpUtils::checkIp($ip, $ranges);
    }

    public static function message(): string
    {
        $message = self::settings()?->blocked_message ?: 'Sorry, we can’t accept this request online.';

        return str_replace('{hotline}', (string) rescue(fn () => app(StoreSettings::class)->hotline, '', report: false), $message);
    }

    /** Validation rule for a phone field. */
    public static function rule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) {
            if (self::phoneBlocked((string) $value)) {
                $fail(self::message());
            }
        };
    }

    /** Add a number (from an order or lead page). Returns false when it was already on the list. */
    public static function block(string $phone, ?string $note = null): bool
    {
        $phone = Phone::normalize($phone);
        if ($phone === '' || self::phoneBlocked($phone)) {
            return false;
        }

        $settings = app(SecuritySettings::class);
        $settings->blocked_phones = [...$settings->blocked_phones, ['phone' => $phone, 'note' => $note]];
        $settings->save();

        return true;
    }

    public static function unblock(string $phone): void
    {
        $phone = Phone::normalize($phone);
        $settings = app(SecuritySettings::class);
        $settings->blocked_phones = collect($settings->blocked_phones)
            ->reject(fn ($row) => Phone::normalize(is_array($row) ? ($row['phone'] ?? '') : $row) === $phone)
            ->values()
            ->all();
        $settings->save();
    }
}
