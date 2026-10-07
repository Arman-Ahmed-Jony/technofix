<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/** A question is shown on the product page only once staff have answered it (Published). */
enum QuestionStatus: string implements HasColor, HasLabel
{
    case Pending = 'pending';
    case Published = 'published';
    case Hidden = 'hidden';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'Needs an answer',
            self::Published => 'Answered & published',
            self::Hidden => 'Hidden',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Published => 'success',
            self::Hidden => 'gray',
        };
    }
}
