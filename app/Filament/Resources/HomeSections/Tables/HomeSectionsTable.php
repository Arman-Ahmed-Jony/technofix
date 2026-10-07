<?php

namespace App\Filament\Resources\HomeSections\Tables;

use App\Models\HomeSection;
use App\Support\Catalog;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class HomeSectionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->reorderable('sort')
            ->paginated(false)
            ->description('The home page from top to bottom — drag to reorder, switch sections on or off, or add another product row.')
            ->columns([
                TextColumn::make('type')
                    ->label('Section')
                    ->formatStateUsing(fn (HomeSection $record) => $record->typeLabel())
                    ->icon(fn (HomeSection $record) => HomeSection::TYPES[$record->type][1] ?? null)
                    ->iconColor('primary')
                    ->weight('medium'),
                TextColumn::make('title')
                    ->label('Shows')
                    ->state(fn (HomeSection $record) => self::summary($record))
                    ->description(fn (HomeSection $record) => $record->eyebrow)
                    ->wrap(),
                ToggleColumn::make('is_active')->label('On'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    protected static function summary(HomeSection $section): string
    {
        $catalog = app(Catalog::class);
        $name = fn ($id) => $id ? ($catalog->categoryById((int) $id)?->name ?? 'hidden category') : '—';

        return match ($section->type) {
            'hero' => 'Hero slides'.($section->setting('brand_zones', true) ? ' + Exclusive Zone brands' : ''),
            'categories' => count($section->setting('category_ids', [])).' categories',
            'departments' => collect(['big', 'wide', 'small1', 'small2'])->map(fn ($slot) => $name($section->setting("{$slot}.category_id")))->join(' · '),
            'product_tabs' => collect($section->setting('tabs', []))->pluck('label')->filter()->join(' · '),
            'category_row' => $section->title ?: $name($section->setting('category_id')),
            'ticker' => ($count = count($section->setting('messages', []))).' '.str('message')->plural($count).': '
                .str((string) ($section->setting('messages')[0]['text'] ?? ''))->limit(60),
            default => $section->title ?: $section->typeLabel(),
        };
    }
}
