<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * How an order is paid. Checkout offers the methods that have a driver in App\Payments\PaymentGateways (cash on
 * delivery for now); staff can record payments by any of them (e.g. a bKash advance sent to the shop's number).
 */
enum PaymentMethod: string implements HasLabel
{
    case CashOnDelivery = 'cod';
    case Cash = 'cash';
    case Bkash = 'bkash';
    case Nagad = 'nagad';
    case Rocket = 'rocket';
    case BankTransfer = 'bank';
    case Card = 'card';

    public function getLabel(): string
    {
        return match ($this) {
            self::CashOnDelivery => 'Cash on delivery',
            self::Cash => 'Cash at the shop',
            self::Bkash => 'bKash',
            self::Nagad => 'Nagad',
            self::Rocket => 'Rocket',
            self::BankTransfer => 'Bank transfer',
            self::Card => 'Card',
        };
    }
}
