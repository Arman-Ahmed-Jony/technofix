<?php

namespace App\Payments;

use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Settings\PaymentSettings;

/**
 * The courier (or the shop, for pickups) collects the amount due; the order becomes paid when it's marked
 * delivered (OrderService::changeStatus()).
 */
class CashOnDelivery implements PaymentGateway
{
    public function __construct(protected PaymentSettings $settings) {}

    public function method(): PaymentMethod
    {
        return PaymentMethod::CashOnDelivery;
    }

    public function label(): string
    {
        return $this->settings->cod_label;
    }

    public function description(): string
    {
        return $this->settings->cod_description;
    }

    public function icon(): string
    {
        return 'cash';
    }

    public function isAvailable(): bool
    {
        return true; // the only way to pay until online gateways are added
    }

    public function start(Order $order): ?string
    {
        return null;
    }
}
