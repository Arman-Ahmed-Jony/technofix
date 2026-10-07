<?php

namespace App\Filament\Resources\Categories\Tables;

use App\Filament\Resources\Categories\CategoryResource;
use App\Models\Category;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            // The tree, in menu order: parents directly followed by their children.
            ->defaultSort('tree_path')
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount(['products', 'templateAttributes']))
            ->paginated([50, 100, 'all'])
            ->defaultPaginationPageOption(100)
            ->columns([
                TextColumn::make('name')
                    ->formatStateUsing(fn (Category $record) => $record->treeLabel())
                    ->description(fn (Category $record) => '/'.$record->slug)
                    ->weight(fn (Category $record) => $record->depth === 0 ? 'bold' : 'medium')
                    ->searchable(['name', 'slug']),
                TextColumn::make('products_count')
                    ->label('Products')
                    ->numeric()
                    ->color(fn (int $state) => $state ? null : 'gray'),
                TextColumn::make('template_attributes_count')
                    ->label('Spec fields')
                    ->numeric()
                    ->color('gray')
                    ->toggleable(),
                TextInputColumn::make('sort')
                    ->label('Position')
                    ->type('number')
                    ->rules(['required', 'integer', 'min:0'])
                    ->width('100px'),
                ToggleColumn::make('is_active')->label('On site'),
                ToggleColumn::make('show_in_menu')->label('In menu'),
            ])
            ->filters([
                SelectFilter::make('department')
                    ->label('Department')
                    ->options(fn () => Category::whereNull('parent_id')->orderBy('sort')->pluck('name', 'id'))
                    ->query(fn (Builder $query, array $data) => $data['value'] ? $query->whereIn('id', Category::withDescendantIds((int) $data['value'])) : $query),
                TernaryFilter::make('is_active')->label('On site'),
            ])
            ->recordActions([
                Action::make('open')
                    ->label('View')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->url(fn (Category $record) => $record->url(), shouldOpenInNewTab: true)
                    ->visible(fn (Category $record) => $record->is_active),
                EditAction::make(),
                ActionGroup::make([CategoryResource::deleteAction()]),
            ]);
    }
}
