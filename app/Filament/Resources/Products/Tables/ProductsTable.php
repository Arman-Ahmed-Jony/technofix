<?php

namespace App\Filament\Resources\Products\Tables;

use App\Enums\ProductStatus;
use App\Enums\StockStatus;
use App\Filament\Actions\AdjustStockAction;
use App\Filament\Resources\Products\ProductResource;
use App\Models\Category;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ProductsTable
{
    /** Flag column => label, for the flag filters and the bulk "Flags" action. */
    public const FLAGS = [
        'is_featured' => 'Featured',
        'is_new' => 'New arrival',
        'is_best_seller' => 'Best seller',
        'is_hot_deal' => 'Hot deal',
    ];

    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with('media'))
            ->searchPlaceholder('Search name, code or model')
            ->paginated([25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->columns([
                SpatieMediaLibraryImageColumn::make('images')
                    ->label('')
                    ->collection('images')
                    ->conversion('thumb')
                    ->limit(1)
                    ->square()
                    ->size(44)
                    ->extraImgAttributes(['class' => 'bg-white object-contain rounded-lg']),
                TextColumn::make('name')
                    ->searchable(['name', 'sku', 'model'])
                    ->sortable()
                    ->weight('medium')
                    ->wrap()
                    ->lineClamp(2)
                    ->extraAttributes(['style' => 'min-width: 17rem'])
                    ->description(fn (Product $record) => collect([$record->sku, $record->brand_name, $record->category_name])->filter()->join(' · ')),
                TextInputColumn::make('price')
                    ->label('Price (BDT)')
                    ->type('number')
                    ->rules(['nullable', 'integer', 'min:0', 'max:99999999'])
                    ->placeholder('Call')
                    ->sortable()
                    ->extraAttributes(['style' => 'width: 8rem; min-width: 8rem']),
                SelectColumn::make('stock_status')
                    ->label('Availability')
                    ->options(StockStatus::class)
                    ->selectablePlaceholder(false)
                    ->sortable()
                    ->extraAttributes(['style' => 'width: 10rem; min-width: 10rem']),
                TextInputColumn::make('stock_qty')
                    ->label('Qty')
                    ->type('number')
                    ->rules(['required', 'integer', 'min:-9999', 'max:999999'])
                    ->sortable()
                    ->extraAttributes(['style' => 'width: 6rem; min-width: 6rem']),
                TextColumn::make('status')->badge()->sortable(),
                IconColumn::make('is_hot_deal')->label('Deal')->boolean()->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_featured')->label('Featured')->boolean()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('regular_price')->label('Regular')->numeric()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('cost_price')->label('Cost')->numeric()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')->label('Updated')->since()->sortable()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->label('Category')
                    ->options(fn () => Category::treeOptions())
                    ->searchable()
                    ->query(fn (Builder $query, array $data) => $data['value'] ? $query->whereIn('category_id', Category::withDescendantIds((int) $data['value'])) : $query),
                SelectFilter::make('brand_id')->label('Brand')->relationship('brand', 'name')->searchable()->preload(),
                SelectFilter::make('stock_status')->label('Availability')->options(StockStatus::class)->multiple(),
                SelectFilter::make('status')->options(ProductStatus::class),
                ...collect(self::FLAGS)->map(fn (string $label, string $column) => TernaryFilter::make($column)->label($label))->values()->all(),
                Filter::make('no_photo')->label('Missing photos')->toggle()->query(fn (Builder $query) => $query->whereDoesntHave('media', fn (Builder $media) => $media->where('collection_name', 'images'))),
                Filter::make('no_price')->label('No price (call for price)')->toggle()->query(fn (Builder $query) => $query->whereNull('price')),
                TrashedFilter::make(),
            ])
            ->filtersFormColumns(2)
            ->recordActions([
                AdjustStockAction::make()->iconButton()->tooltip('Adjust stock'),
                Action::make('open')
                    ->label('View on site')
                    ->iconButton()
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->url(fn (Product $record) => $record->url, shouldOpenInNewTab: true)
                    ->visible(fn (Product $record) => $record->status === ProductStatus::Published && ! $record->trashed()),
                EditAction::make(),
                ActionGroup::make([
                    ProductResource::duplicateAction(),
                    DeleteAction::make(),
                    RestoreAction::make(),
                    ForceDeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('availability')
                        ->label('Set availability')
                        ->icon('heroicon-o-archive-box')
                        ->schema([Select::make('stock_status')->label('Availability')->options(StockStatus::class)->required()])
                        ->action(fn (Collection $records, array $data) => self::bulkUpdate($records, ['stock_status' => $data['stock_status']]))
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('publish')
                        ->label('Publish')
                        ->icon('heroicon-o-eye')
                        ->action(fn (Collection $records) => self::bulkUpdate($records, ['status' => ProductStatus::Published]))
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('draft')
                        ->label('Move to drafts')
                        ->icon('heroicon-o-eye-slash')
                        ->action(fn (Collection $records) => self::bulkUpdate($records, ['status' => ProductStatus::Draft]))
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('flags')
                        ->label('Set a flag')
                        ->icon('heroicon-o-flag')
                        ->schema([
                            Select::make('flag')->options(self::FLAGS)->required(),
                            ToggleButtons::make('on')->label('Set to')->boolean('On', 'Off')->default(true)->inline()->required(),
                        ])
                        ->action(fn (Collection $records, array $data) => self::bulkUpdate($records, [$data['flag'] => (bool) $data['on']]))
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * @param  Collection<int, Product>  $records
     * @param  array<string, mixed>  $values
     */
    protected static function bulkUpdate(Collection $records, array $values): void
    {
        $allowed = ['stock_status', 'status', ...array_keys(self::FLAGS)];
        abort_if(array_diff(array_keys($values), $allowed), 422);

        $count = Product::withTrashed()->whereKey($records->modelKeys())->update([...$values, 'updated_at' => now()]);

        if (array_key_exists('is_featured', $values)) {
            Product::syncFeaturedOrder();
        }

        Notification::make()->success()->title("{$count} product".($count === 1 ? '' : 's').' updated')->send();
    }
}
