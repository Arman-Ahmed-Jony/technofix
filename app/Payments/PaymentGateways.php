<?php

namespace App\Payments;

use App\Enums\PaymentMethod;

/**
 * The payment methods the checkout can offer. A singleton (AppServiceProvider): add a driver to DRIVERS, or
 * register() one at runtime (tests do).
 */
class PaymentGateways
{
    /** @var array<string, class-string<PaymentGateway>> */
    public const DRIVERS = [
        'cod' => CashOnDelivery::class,
    ];

    /** @var array<string, class-string<PaymentGateway>|PaymentGateway> */
    protected array $drivers = self::DRIVERS;

    public function register(PaymentMethod $method, PaymentGateway|string $driver): void
    {
        $this->drivers[$method->value] = $driver;
    }

    /**
     * The methods customers can choose now, in checkout order.
     *
     * @return list<PaymentGateway>
     */
    public function available(): array
    {
        return collect($this->drivers)
            ->map(fn ($driver) => $driver instanceof PaymentGateway ? $driver : app($driver))
            ->filter(fn (PaymentGateway $gateway) => $gateway->isAvailable())
            ->values()
            ->all();
    }

    public function for(PaymentMethod|string|null $method): ?PaymentGateway
    {
        $code = $method instanceof PaymentMethod ? $method->value : $method;

        return collect($this->available())->first(fn (PaymentGateway $gateway) => $gateway->method()->value === $code);
    }

    /** @return list<string> */
    public function codes(): array
    {
        return array_map(fn (PaymentGateway $gateway) => $gateway->method()->value, $this->available());
    }
}
