<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

/**
 * Where a warranty claim stands. The Excel sheet's REMARKS column holds sheetValue() ("STOCK IN", "DELIVERED",
 * "STOCK OUT" come from the original sheet; an empty REMARKS meant still in progress).
 */
enum WarrantyStatus: string implements HasColor, HasIcon, HasLabel
{
    case Received = 'received';
    case Claimed = 'claimed';
    case Ready = 'ready';
    case Delivered = 'delivered';
    case Closed = 'closed';

    /** Staff wording (admin). */
    public function getLabel(): string
    {
        return match ($this) {
            self::Received => 'Received',
            self::Claimed => 'Sent for claim',
            self::Ready => 'Stock in (ready)',
            self::Delivered => 'Delivered',
            self::Closed => 'Stock out',
        };
    }

    /** What the customer sees on the website and in emails. */
    public function customerLabel(): string
    {
        return match ($this) {
            self::Received => 'Received at our shop',
            self::Claimed => 'Sent for warranty claim',
            self::Ready => 'Ready to collect',
            self::Delivered => 'Delivered',
            self::Closed => 'Closed',
        };
    }

    public function customerText(): string
    {
        return match ($this) {
            self::Received => 'We have your product and will check it shortly.',
            self::Claimed => 'Your product is with the brand / supplier for warranty service. This usually takes a few days to a few weeks.',
            self::Ready => 'Your product is back at our shop. Please bring your receipt to collect it.',
            self::Delivered => 'Your product was handed back to you. Thank you!',
            self::Closed => 'This claim is closed. Call us if you have any questions.',
        };
    }

    /** REMARKS column of the Excel sheet. */
    public function sheetValue(): string
    {
        return match ($this) {
            self::Received => 'RECEIVED',
            self::Claimed => 'IN CLAIM',
            self::Ready => 'STOCK IN',
            self::Delivered => 'DELIVERED',
            self::Closed => 'STOCK OUT',
        };
    }

    /** Still open (shown first, counted in the menu badge). */
    public function isOpen(): bool
    {
        return in_array($this, [self::Received, self::Claimed, self::Ready], true);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Received => 'gray',
            self::Claimed => 'warning',
            self::Ready => 'success',
            self::Delivered => 'info',
            self::Closed => 'danger',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Received => 'heroicon-o-inbox-arrow-down',
            self::Claimed => 'heroicon-o-truck',
            self::Ready => 'heroicon-o-check-badge',
            self::Delivered => 'heroicon-o-hand-thumb-up',
            self::Closed => 'heroicon-o-archive-box',
        };
    }

    /** Excel REMARKS text → status (empty: sent for claim if a claim date exists, else received). */
    public static function fromSheet(?string $remarks, bool $claimed = false): self
    {
        $text = strtoupper(trim((string) $remarks));

        foreach (self::cases() as $status) {
            if ($text === $status->sheetValue()) {
                return $status;
            }
        }

        return match (true) {
            str_contains($text, 'DELIVER') => self::Delivered,
            str_contains($text, 'STOCK IN'), str_contains($text, 'READY') => self::Ready,
            str_contains($text, 'STOCK OUT'), str_contains($text, 'CLOSE') => self::Closed,
            $claimed || str_contains($text, 'CLAIM') => self::Claimed,
            default => self::Received,
        };
    }
}
