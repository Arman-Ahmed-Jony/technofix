<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/** Where an order came from (orders taken by staff can be any of these). */
enum OrderSource: string implements HasLabel
{
    case Web = 'web';
    case Phone = 'phone';
    case Facebook = 'facebook';
    case WhatsApp = 'whatsapp';
    case WalkIn = 'walk_in';
    case Other = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::Web => 'Website',
            self::Phone => 'Phone call',
            self::Facebook => 'Facebook',
            self::WhatsApp => 'WhatsApp',
            self::WalkIn => 'Walk-in',
            self::Other => 'Other',
        };
    }
}
