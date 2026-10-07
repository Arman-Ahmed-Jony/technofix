<?php

namespace App\Payments;

use App\Enums\PaymentMethod;
use App\Models\Order;

/**
 * A way to pay at checkout. Cash on delivery is the first driver; bKash, Nagad and SSLCommerz become drivers
 * registered in PaymentGateways — the checkout and orders don't change when one is added.
 *
 * An online driver will also get a return/IPN route of its own that verifies the payment with the provider and
 * records it with App\Services\PaymentService (pending → completed/failed).
 */
interface PaymentGateway
{
    public function method(): PaymentMethod;

    /** Name and one-line explanation shown at checkout (admin-managed). */
    public function label(): string;

    public function description(): string;

    /** Tabler icon for the checkout option. */
    public function icon(): string;

    /** Switched on in the admin and, for online gateways, configured with credentials. */
    public function isAvailable(): bool;

    /**
     * Called right after the order is saved. Offline methods return null (the customer sees the confirmation page);
     * online gateways create the payment with the provider and return the URL to send the customer to.
     */
    public function start(Order $order): ?string;
}
