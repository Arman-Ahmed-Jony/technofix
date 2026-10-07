<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Models\Product;
use App\Models\ProductColor;
use App\Settings\DeliverySettings;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\HtmlString;

/**
 * Items, delivery charge and discount fields for orders taken by staff and for editing an order's items.
 * The server recalculates every total from these lines (OrderService); nothing here is trusted as a total.
 */
class OrderItemsFields
{
    public static function items(): Repeater
    {
        return Repeater::make('items')
            ->label('Products')
            ->table([
                TableColumn::make('Product')->markAsRequired(),
                TableColumn::make('Color')->width('160px'),
                TableColumn::make('Price (BDT)')->width('140px')->markAsRequired(),
                TableColumn::make('Qty')->width('90px')->markAsRequired(),
                TableColumn::make('Total')->width('120px'),
            ])
            ->schema([
                Select::make('product_id')
                    ->searchable()
                    ->required()
                    ->getSearchResultsUsing(fn (string $search) => Product::query()
                        ->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%")->orWhere('model', 'like', "%{$search}%"))
                        ->orderBy('name')
                        ->limit(50)
                        ->get()
                        ->mapWithKeys(fn (Product $p) => [$p->id => self::label($p)])
                        ->all())
                    ->getOptionLabelUsing(fn ($value) => ($p = Product::withTrashed()->find($value)) ? self::label($p) : null)
                    ->live()
                    ->afterStateUpdated(function (Set $set, Get $get, $state) {
                        $product = Product::with('colors')->find($state);
                        $set('price', $product?->sale_price); // campaign deal price while a sale runs
                        $set('color', $product?->colors->firstWhere('is_available', true)?->name ?? $product?->colors->first()?->name);
                        self::updateDeliveryFee($set, $get, '../../');
                    }),
                // Only for products with color versions (Products → Colors); staff may pick a sold-out color.
                Select::make('color')
                    ->options(fn (Get $get) => ProductColor::where('product_id', $get('product_id'))->orderBy('sort')->orderBy('id')->get()
                        ->mapWithKeys(fn (ProductColor $c) => [$c->name => $c->name.($c->is_available ? '' : ' (sold out)')])->all())
                    ->placeholder(fn (Get $get) => ProductColor::where('product_id', $get('product_id'))->exists() ? 'Choose…' : '—')
                    ->disabled(fn (Get $get) => ! ProductColor::where('product_id', $get('product_id'))->exists())
                    ->required(fn (Get $get) => ProductColor::where('product_id', $get('product_id'))->exists()),
                TextInput::make('price')->integer()->minValue(0)->maxValue(99999999)->required()->live(onBlur: true),
                TextInput::make('qty')->integer()->minValue(1)->maxValue(999)->default(1)->required()->live(onBlur: true),
                TextInput::make('line_total')
                    ->disabled()
                    ->dehydrated(false)
                    ->placeholder(fn (Get $get) => number_format((int) $get('price') * (int) $get('qty'))),
            ])
            ->minItems(1)
            ->defaultItems(1)
            ->reorderable(false)
            ->addActionLabel('Add product')
            ->live()
            ->columnSpanFull();
    }

    /**
     * @return array<int, mixed>
     */
    public static function charges(): array
    {
        return [
            Grid::make(['default' => 1, 'md' => 3])->schema([
                Select::make('delivery_code')
                    ->label('Delivery')
                    ->options(fn () => collect(app(DeliverySettings::class)->active())->mapWithKeys(fn (array $o) => [$o['code'] => $o['label'].' — BDT '.number_format($o['fee'])])->all())
                    ->default(fn () => array_key_first(app(DeliverySettings::class)->active()))
                    ->required()
                    ->selectablePlaceholder(false)
                    ->live()
                    ->afterStateUpdated(fn (Set $set, Get $get) => self::updateDeliveryFee($set, $get)),
                TextInput::make('delivery_fee')
                    ->label('Delivery charge (BDT)')
                    ->integer()
                    ->minValue(0)
                    ->required()
                    ->default(fn () => collect(app(DeliverySettings::class)->active())->first()['fee'] ?? 0)
                    ->live(onBlur: true)
                    ->helperText('Filled in from the delivery option; change it if needed.'),
                TextInput::make('discount')
                    ->label('Discount (BDT)')
                    ->integer()
                    ->minValue(0)
                    ->default(0)
                    ->live(onBlur: true),
            ]),
            Text::make(fn (Get $get) => self::summary($get))->columnSpanFull(),
        ];
    }

    public static function isPickup(?string $code): bool
    {
        return (app(DeliverySettings::class)->find($code)['type'] ?? null) === 'pickup';
    }

    protected static function label(Product $product): string
    {
        return $product->name.' ('.$product->sku.') — '.($product->sale_price ? 'BDT '.number_format($product->sale_price) : 'no price');
    }

    /** Sets the charge for the chosen delivery option (free above the threshold, like the website). */
    protected static function updateDeliveryFee(Set $set, Get $get, string $path = ''): void
    {
        $delivery = app(DeliverySettings::class);
        $option = $delivery->find($get($path.'delivery_code'));

        if ($option) {
            $set($path.'delivery_fee', $delivery->feeFor($option, self::subtotal($get($path.'items'))));
        }
    }

    protected static function subtotal(?array $items): int
    {
        return collect($items ?? [])->sum(fn ($item) => (int) ($item['price'] ?? 0) * (int) ($item['qty'] ?? 0));
    }

    protected static function summary(Get $get): HtmlString
    {
        $subtotal = self::subtotal($get('items'));
        $fee = (int) $get('delivery_fee');
        $discount = min((int) $get('discount'), $subtotal + $fee);

        return new HtmlString(sprintf(
            'Subtotal <b>BDT %s</b> &nbsp;+&nbsp; delivery <b>BDT %s</b>%s &nbsp;=&nbsp; <b style="font-size:1.1em">Total BDT %s</b>',
            number_format($subtotal),
            number_format($fee),
            $discount ? ' &nbsp;−&nbsp; discount <b>BDT '.number_format($discount).'</b>' : '',
            number_format($subtotal + $fee - $discount),
        ));
    }
}
