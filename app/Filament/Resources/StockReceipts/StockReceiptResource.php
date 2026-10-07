<?php

namespace App\Filament\Resources\StockReceipts;

use App\Filament\Resources\StockReceipts\Pages\CreateStockReceipt;
use App\Filament\Resources\StockReceipts\Pages\ListStockReceipts;
use App\Filament\Resources\StockReceipts\Pages\ViewStockReceipt;
use App\Filament\Resources\Suppliers\SupplierResource;
use App\Models\Product;
use App\Models\StockReceipt;
use App\Models\Supplier;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontFamily;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use UnitEnum;

/**
 * Stock-in: goods arriving from a supplier. Saving adds the stock and updates cost prices (App\Services\InventoryService);
 * a stock-in can't be edited, only cancelled.
 */
class StockReceiptResource extends Resource
{
    protected static ?string $model = StockReceipt::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-inbox-arrow-down';

    protected static string|UnitEnum|null $navigationGroup = 'Inventory';

    protected static ?string $navigationLabel = 'Stock-in';

    protected static ?string $modelLabel = 'stock-in';

    protected static ?string $pluralModelLabel = 'stock-ins';

    protected static ?string $slug = 'stock-in';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'number';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columns(['default' => 1, 'md' => 3])
                ->columnSpanFull()
                ->schema([
                    Select::make('supplier_id')
                        ->label('Supplier')
                        ->options(fn () => Supplier::active()->orderBy('name')->pluck('name', 'id'))
                        ->searchable()
                        ->createOptionForm(SupplierResource::fields())
                        ->createOptionUsing(fn (array $data) => Supplier::create($data)->getKey()),
                    TextInput::make('reference')->label('Their invoice / challan no.')->maxLength(80),
                    DatePicker::make('received_on')->label('Received on')->default(now())->maxDate(now())->required(),
                ]),
            Section::make('Products')
                ->columnSpanFull()
                ->description('Quantities are added to stock when you save. Each product’s cost price becomes the average cost of what you have.')
                ->schema([
                    Repeater::make('items')
                        ->hiddenLabel()
                        ->table([
                            TableColumn::make('Product')->markAsRequired(),
                            TableColumn::make('Qty')->width('110px')->markAsRequired(),
                            TableColumn::make('Cost per unit (BDT)')->width('170px')->markAsRequired(),
                            TableColumn::make('Total')->width('130px'),
                        ])
                        ->schema([
                            Select::make('product_id')
                                ->searchable()
                                ->required()
                                ->getSearchResultsUsing(fn (string $search) => Product::query()
                                    ->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%")->orWhere('model', 'like', "%{$search}%"))
                                    ->orderBy('name')->limit(50)->get()
                                    ->mapWithKeys(fn (Product $p) => [$p->id => self::productLabel($p)])->all())
                                ->getOptionLabelUsing(fn ($value) => ($p = Product::withTrashed()->find($value)) ? self::productLabel($p) : null)
                                ->live()
                                ->afterStateUpdated(fn (Set $set, $state) => $set('unit_cost', Product::find($state)?->cost_price)),
                            TextInput::make('qty')->integer()->minValue(1)->maxValue(100000)->default(1)->required()->live(onBlur: true),
                            TextInput::make('unit_cost')->integer()->minValue(0)->maxValue(99999999)->required()->live(onBlur: true),
                            TextInput::make('line_total')->disabled()->dehydrated(false)
                                ->placeholder(fn (Get $get) => number_format((int) $get('qty') * (int) $get('unit_cost'))),
                        ])
                        ->minItems(1)
                        ->defaultItems(1)
                        ->reorderable(false)
                        ->addActionLabel('Add product')
                        ->live(),
                    Text::make(fn (Get $get) => new HtmlString('Total cost <b style="font-size:1.1em">BDT '.number_format(collect($get('items'))->sum(fn ($i) => (int) ($i['qty'] ?? 0) * (int) ($i['unit_cost'] ?? 0))).'</b>')),
                ]),
            Section::make()
                ->columnSpanFull()
                ->schema([Textarea::make('note')->rows(2)->maxLength(1000)->placeholder('Anything to remember about this delivery')]),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columns(['default' => 2, 'md' => 4])
                ->columnSpanFull()
                ->schema([
                    TextEntry::make('supplier.name')->label('Supplier')->placeholder('—'),
                    TextEntry::make('reference')->label('Their invoice no.')->placeholder('—'),
                    TextEntry::make('received_on')->label('Received on')->date('D j M Y'),
                    TextEntry::make('total_cost')->label('Total cost')->formatStateUsing(fn ($state) => 'BDT '.number_format($state))->weight('bold'),
                    TextEntry::make('user.name')->label('Entered by')->placeholder('—'),
                    TextEntry::make('created_at')->label('Entered')->dateTime('d M Y, h:i A'),
                    TextEntry::make('cancelled_at')->label('Cancelled')->dateTime('d M Y, h:i A')->color('danger')->visible(fn (StockReceipt $record) => $record->isCancelled()),
                    TextEntry::make('note')->placeholder('—')->columnSpanFull(),
                ]),
            Section::make('Products')
                ->columnSpanFull()
                ->schema([
                    RepeatableEntry::make('items')
                        ->hiddenLabel()
                        ->table([
                            RepeatableEntry\TableColumn::make('Product'),
                            RepeatableEntry\TableColumn::make('Qty'),
                            RepeatableEntry\TableColumn::make('Cost per unit'),
                            RepeatableEntry\TableColumn::make('Total'),
                        ])
                        ->schema([
                            TextEntry::make('product.name'),
                            TextEntry::make('qty'),
                            TextEntry::make('unit_cost')->formatStateUsing(fn ($state) => 'BDT '.number_format($state)),
                            TextEntry::make('total')->formatStateUsing(fn ($state) => 'BDT '.number_format($state)),
                        ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['supplier', 'user'])->withCount('items')->withSum('items', 'qty'))
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('number')->fontFamily(FontFamily::Mono)->weight('medium')->searchable()
                    ->description(fn (StockReceipt $record) => $record->isCancelled() ? 'Cancelled' : null),
                TextColumn::make('received_on')->label('Received')->date('d M Y')->sortable(),
                TextColumn::make('supplier.name')->label('Supplier')->placeholder('—')->searchable(),
                TextColumn::make('reference')->label('Their invoice')->placeholder('—')->searchable()->toggleable(),
                TextColumn::make('items_sum_qty')->label('Units')->numeric()->alignEnd(),
                TextColumn::make('total_cost')->label('Total cost')->formatStateUsing(fn ($state) => 'BDT '.number_format($state))->alignEnd()->sortable()
                    ->color(fn (StockReceipt $record) => $record->isCancelled() ? 'gray' : null),
                TextColumn::make('user.name')->label('By')->placeholder('—')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('supplier_id')->label('Supplier')->relationship('supplier', 'name'),
                TernaryFilter::make('cancelled_at')->label('Cancelled')->nullable(),
            ]);
    }

    protected static function productLabel(Product $product): string
    {
        return $product->name.' ('.$product->sku.') — in stock: '.(int) $product->stock_qty;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStockReceipts::route('/'),
            'create' => CreateStockReceipt::route('/create'),
            'view' => ViewStockReceipt::route('/{record}'),
        ];
    }
}
