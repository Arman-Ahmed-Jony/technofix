<?php

namespace App\Filament\Resources\Campaigns\Schemas;

use App\Models\Product;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;

class CampaignForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(['default' => 1, 'lg' => 3])
                ->columnSpanFull()
                ->schema([
                    Section::make('Sale')
                        ->columns(2)
                        ->columnSpan(['lg' => 2])
                        ->schema([
                            TextInput::make('name')->required()->maxLength(80)->placeholder('e.g. Happy Hour, Eid Flash Sale'),
                            TextInput::make('badge')
                                ->label('Badge on products')
                                ->default('Flash sale')
                                ->required()
                                ->maxLength(30)
                                ->helperText('Short, e.g. Flash sale, Happy Hour, Eid deal.'),
                            TextInput::make('headline')
                                ->maxLength(160)
                                ->placeholder('e.g. Today’s hottest prices')
                                ->helperText('Shown under the name on the home page and the Offers page.')
                                ->columnSpanFull(),
                        ]),

                    Section::make('When')
                        ->columnSpan(1)
                        ->schema([
                            DateTimePicker::make('starts_at')->label('Starts')->seconds(false)->required()->default(fn () => now()->startOfHour()),
                            DateTimePicker::make('ends_at')->label('Ends')->seconds(false)->required()->after('starts_at')->default(fn () => now()->endOfDay()->startOfMinute()),
                            Toggle::make('is_active')->label('Switched on')->default(true),
                            Toggle::make('show_on_home')->label('Show in the home page deals section')->default(true),
                        ])
                        ->headerActions([
                            Action::make('happyHour')
                                ->label('Tonight 6–10 PM')
                                ->link()
                                ->action(function (Set $set) {
                                    $set('starts_at', Carbon::today()->setTime(18, 0)->toDateTimeString());
                                    $set('ends_at', Carbon::today()->setTime(22, 0)->toDateTimeString());
                                }),
                        ]),
                ]),

            Section::make('Products & deal prices')
                ->description('While the sale runs these prices are shown everywhere and charged at checkout. When it ends, the normal prices come back by themselves.')
                ->columnSpanFull()
                ->schema([
                    Repeater::make('items')
                        ->relationship()
                        ->hiddenLabel()
                        ->orderColumn('sort')
                        ->reorderable()
                        ->addActionLabel('Add product')
                        ->minItems(1)
                        ->table([
                            TableColumn::make('Product')->markAsRequired(),
                            TableColumn::make('Normal price')->width('150px'),
                            TableColumn::make('Deal price (BDT)')->width('170px')->markAsRequired(),
                        ])
                        ->schema([
                            Select::make('product_id')
                                ->searchable()
                                ->required()
                                ->distinct()
                                ->getSearchResultsUsing(fn (string $search) => Product::query()
                                    ->whereNotNull('price')
                                    ->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%")->orWhere('model', 'like', "%{$search}%"))
                                    ->orderBy('name')->limit(50)->get()
                                    ->mapWithKeys(fn (Product $p) => [$p->id => $p->name.' ('.$p->sku.')'])
                                    ->all())
                                ->getOptionLabelUsing(fn ($value) => ($p = Product::withTrashed()->find($value)) ? $p->name.' ('.$p->sku.')' : null)
                                ->live(),
                            TextInput::make('normal_price')
                                ->hiddenLabel()
                                ->disabled()
                                ->dehydrated(false)
                                ->prefix('BDT')
                                ->formatStateUsing(fn (Get $get) => ($price = Product::find($get('product_id'))?->price) ? number_format($price) : '—')
                                ->placeholder(fn (Get $get) => ($price = Product::find($get('product_id'))?->price) ? number_format($price) : '—'),
                            TextInput::make('deal_price')
                                ->integer()
                                ->required()
                                ->minValue(1)
                                ->prefix('BDT')
                                ->rules([fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                    $price = Product::find($get('product_id'))?->price;

                                    if ($price && (int) $value >= $price) {
                                        $fail('The deal price must be lower than the normal price (BDT '.number_format($price).').');
                                    }
                                }]),
                        ]),
                ]),
        ]);
    }
}
