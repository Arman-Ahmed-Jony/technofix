<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * What the checkout says about paying — edited in Admin → Settings → Payments.
 */
class PaymentSettings extends Settings
{
    /** Cash on delivery as the customer sees it. */
    public string $cod_label;

    public string $cod_description;

    /** Methods shown greyed out as "Coming soon" at checkout (empty = none). */
    public array $coming_soon;

    public static function group(): string
    {
        return 'payments';
    }
}
