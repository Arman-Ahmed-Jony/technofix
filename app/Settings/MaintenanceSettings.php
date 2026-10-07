<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Maintenance mode — Admin → Settings → Maintenance mode. Customers see a "back soon" page; signed-in staff and the
 * admin keep working (App\Http\Middleware\MaintenanceMode).
 */
class MaintenanceSettings extends Settings
{
    public bool $enabled;

    public string $headline;

    public ?string $message;

    /** Expected back ("Y-m-d H:i"), shown to customers; optional. */
    public ?string $back_at;

    public static function group(): string
    {
        return 'maintenance';
    }
}
