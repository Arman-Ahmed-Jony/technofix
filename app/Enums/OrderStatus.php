<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum OrderStatus: string implements HasColor, HasIcon, HasLabel
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Processing = 'processing';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';
    case Returned = 'returned';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'New',
            self::Confirmed => 'Confirmed',
            self::Processing => 'Packing',
            self::Shipped => 'Shipped',
            self::Delivered => 'Delivered',
            self::Cancelled => 'Cancelled',
            self::Returned => 'Returned',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Confirmed, self::Processing => 'info',
            self::Shipped => 'primary',
            self::Delivered => 'success',
            self::Cancelled => 'gray',
            self::Returned => 'danger',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Pending => 'heroicon-o-sparkles',
            self::Confirmed => 'heroicon-o-phone',
            self::Processing => 'heroicon-o-archive-box',
            self::Shipped => 'heroicon-o-truck',
            self::Delivered => 'heroicon-o-check-circle',
            self::Cancelled => 'heroicon-o-x-circle',
            self::Returned => 'heroicon-o-arrow-uturn-left',
        };
    }

    /** The usual next step, for the one-click button in the admin. */
    public function next(): ?self
    {
        return match ($this) {
            self::Pending => self::Confirmed,
            self::Confirmed => self::Processing,
            self::Processing => self::Shipped,
            self::Shipped => self::Delivered,
            default => null,
        };
    }

    /** While an order is in one of these, its items are taken out of stock. */
    public function holdsStock(): bool
    {
        return ! in_array($this, [self::Cancelled, self::Returned], true);
    }

    /** Still to be handled by staff. */
    public function isOpen(): bool
    {
        return in_array($this, [self::Pending, self::Confirmed, self::Processing, self::Shipped], true);
    }
}
