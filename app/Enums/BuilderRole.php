<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * The part a PC Builder slot holds, as far as the compatibility checks are concerned. Slots without a role
 * (storage, monitor, keyboard…) are never checked.
 */
enum BuilderRole: string implements HasLabel
{
    case Processor = 'processor';
    case CpuCooler = 'cpu_cooler';
    case Motherboard = 'motherboard';
    case Memory = 'memory';
    case GraphicsCard = 'graphics_card';
    case PowerSupply = 'power_supply';
    case Casing = 'casing';

    public function getLabel(): string
    {
        return match ($this) {
            self::Processor => 'Processor',
            self::CpuCooler => 'CPU cooler',
            self::Motherboard => 'Motherboard',
            self::Memory => 'RAM',
            self::GraphicsCard => 'Graphics card',
            self::PowerSupply => 'Power supply',
            self::Casing => 'Casing',
        };
    }

    /** Used mid-sentence in compatibility messages ("The processor uses…"). */
    public function noun(): string
    {
        return match ($this) {
            self::CpuCooler => 'CPU cooler',
            self::Memory => 'RAM',
            default => strtolower($this->getLabel()),
        };
    }
}
