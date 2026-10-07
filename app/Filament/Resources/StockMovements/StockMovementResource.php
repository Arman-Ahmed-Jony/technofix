<?php

namespace App\Filament\Resources\StockMovements;

use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\StockMovements\Pages\ListStockMovements;
use App\Filament\Resources\StockReceipts\StockReceiptResource;
use App\Models\StockMovement;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/** Every change to every product's quantity (read-only ledger). */
class StockMovementResource extends Resource
{
    protected static ?string $model = StockMovement::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrows-up-down';

    protected static string|UnitEnum|null $navigationGroup = 'Inventory';

    protected static ?string $navigationLabel = 'Stock movements';

    protected static ?string $slug = 'stock-movements';

    protected static ?int $navigationSort = 3;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['product:id,name,sku,deleted_at', 'order:id,number', 'receipt:id,number', 'user:id,name']))
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('When')->dateTime('d M Y, h:i A')->sortable(),
                TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable()
                    ->limit(48)
                    ->description(fn (StockMovement $record) => $record->product?->sku)
                    ->url(fn (StockMovement $record) => $record->product && ! $record->product->trashed() ? ProductResource::getUrl('edit', ['record' => $record->product]) : null),
                TextColumn::make('type')->formatStateUsing(fn (string $state) => StockMovement::TYPES[$state] ?? $state)->badge()
                    ->color(fn (string $state) => match ($state) {
                        'purchase' => 'success', 'order' => 'info', 'adjustment' => 'warning', default => 'gray'
                    })
                    ->description(fn (StockMovement $record) => $record->reason ? StockMovement::REASONS[$record->reason] ?? $record->reason : null),
                TextColumn::make('change')
                    ->formatStateUsing(fn (int $state) => ($state > 0 ? '+' : '').$state)
                    ->color(fn (int $state) => $state > 0 ? 'success' : 'danger')
                    ->weight('bold')
                    ->alignEnd(),
                TextColumn::make('balance')->label('Left')->alignEnd(),
                TextColumn::make('reference')
                    ->label('For')
                    ->state(fn (StockMovement $record) => $record->order?->number ?? $record->receipt?->number)
                    ->placeholder('—')
                    ->url(fn (StockMovement $record) => match (true) {
                        (bool) $record->order => OrderResource::getUrl('view', ['record' => $record->order]),
                        (bool) $record->receipt => StockReceiptResource::getUrl('view', ['record' => $record->receipt]),
                        default => null,
                    }),
                TextColumn::make('user.name')->label('By')->placeholder('Customer / system'),
                TextColumn::make('note')->placeholder('—')->limit(40)->toggleable(),
            ])
            ->filters([
                SelectFilter::make('type')->options(StockMovement::TYPES),
                SelectFilter::make('reason')->options(StockMovement::REASONS),
                SelectFilter::make('user_id')->label('Staff member')->relationship('user', 'name'),
                Filter::make('when')
                    ->schema([DatePicker::make('from'), DatePicker::make('until')])
                    ->columns(2)
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['from'] ?? null, fn ($q, $d) => $q->where('created_at', '>=', $d))
                        ->when($data['until'] ?? null, fn ($q, $d) => $q->where('created_at', '<', now()->parse($d)->addDay()))),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListStockMovements::route('/')];
    }
}
