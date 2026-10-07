<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum StockStatus: string implements HasColor, HasLabel
{
    case InStock = 'in_stock';
    case OutOfStock = 'out_of_stock';
    case PreOrder = 'pre_order';
    case Upcoming = 'upcoming';
    case CallForPrice = 'call_for_price';

    public function label(): string
    {
        return match ($this) {
            self::InStock => 'In Stock',
            self::OutOfStock => 'Out of Stock',
            self::PreOrder => 'Pre-Order',
            self::Upcoming => 'Upcoming',
            self::CallForPrice => 'Call for Price',
        };
    }

    public function getLabel(): string
    {
        return $this->label();
    }

    public function getColor(): string
    {
        return match ($this) {
            self::InStock => 'success',
            self::OutOfStock => 'danger',
            self::PreOrder => 'warning',
            self::Upcoming => 'info',
            self::CallForPrice => 'gray',
        };
    }

    /**
     * Tailwind classes for the storefront badge.
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::InStock => 'bg-emerald-50 text-emerald-700 ring-emerald-600/15 dark:bg-emerald-400/10 dark:text-emerald-300 dark:ring-emerald-400/20',
            self::OutOfStock => 'bg-rose-50 text-rose-700 ring-rose-600/15 dark:bg-rose-400/10 dark:text-rose-300 dark:ring-rose-400/20',
            self::PreOrder => 'bg-amber-50 text-amber-700 ring-amber-600/20 dark:bg-amber-400/10 dark:text-amber-300 dark:ring-amber-400/20',
            self::Upcoming => 'bg-sky-50 text-sky-700 ring-sky-600/15 dark:bg-sky-400/10 dark:text-sky-300 dark:ring-sky-400/20',
            self::CallForPrice => 'bg-slate-100 text-slate-700 ring-slate-600/15 dark:bg-slate-400/10 dark:text-slate-300 dark:ring-slate-400/20',
        };
    }

    /**
     * Colour of the small status dot used on product cards.
     */
    public function dotClass(): string
    {
        return match ($this) {
            self::InStock => 'bg-emerald-500',
            self::OutOfStock => 'bg-rose-500',
            self::PreOrder => 'bg-amber-500',
            self::Upcoming => 'bg-sky-500',
            self::CallForPrice => 'bg-slate-400',
        };
    }

    /**
     * Whether the product can be added to the cart.
     */
    public function isPurchasable(): bool
    {
        return in_array($this, [self::InStock, self::PreOrder], true);
    }
}
