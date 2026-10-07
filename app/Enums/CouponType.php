<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum CouponType: string implements HasLabel
{
    case Percent = 'percent';
    case Fixed = 'fixed';
    case FreeDelivery = 'free_delivery';

    public function getLabel(): string
    {
        return match ($this) {
            self::Percent => 'Percent off',
            self::Fixed => 'Amount off',
            self::FreeDelivery => 'Free delivery',
        };
    }
}
