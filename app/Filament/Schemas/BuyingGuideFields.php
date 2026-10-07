<?php

namespace App\Filament\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

/**
 * Form pieces shared by the buying-guide editors (defaults, categories, category + brand pages).
 */
class BuyingGuideFields
{
    public const NAME_HINT = '“{name}” is replaced with the page name, e.g. “Laptop” or “Dell Laptop”.';

    public static function faqs(string $name = 'faqs'): Repeater
    {
        return Repeater::make($name)
            ->label('Questions & answers')
            ->schema([
                TextInput::make('question')->required()->maxLength(200),
                Textarea::make('answer')->required()->rows(3)->maxLength(1000),
            ])
            ->itemLabel(fn (array $state) => $state['question'] ?? null)
            ->collapsible()
            ->reorderable()
            ->defaultItems(0)
            ->maxItems(10)
            ->addActionLabel('Add question');
    }
}
