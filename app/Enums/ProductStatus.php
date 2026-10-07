<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ProductStatus: string implements HasColor, HasLabel
{
    case Draft = 'draft';
    case Published = 'published';

    public function getLabel(): string
    {
        return $this === self::Draft ? 'Draft' : 'Published';
    }

    public function getColor(): string
    {
        return $this === self::Draft ? 'gray' : 'success';
    }
}
