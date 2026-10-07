<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Support\Phone;
use Closure;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Arr;

/**
 * Order forms: editing an order's details (items and status have their own actions, so totals and stock stay
 * consistent) and taking a new order by phone / Facebook / WhatsApp / walk-in.
 */
class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(['default' => 1, 'lg' => 3])
                ->columnSpanFull()
                ->schema([
                    Grid::make(1)
                        ->columnSpan(['lg' => 2])
                        ->schema([
                            Section::make('Customer')->columns(2)->schema(self::customerFields()),
                            Section::make('Delivery address')
                                ->columns(2)
                                ->visible(fn (?Order $record) => ! $record?->is_pickup)
                                ->schema(self::addressFields()),
                            Section::make('Notes')->schema(self::noteFields()),
                        ]),
                    Grid::make(1)
                        ->columnSpan(1)
                        ->schema([
                            Section::make('Shipping')
                                ->schema([
                                    TextInput::make('courier')
                                        ->maxLength(60)
                                        ->datalist(['Steadfast', 'Pathao', 'RedX', 'Sundarban', 'SA Paribahan', 'Own delivery']),
                                    TextInput::make('tracking_code')->label('Tracking no.')->maxLength(80),
                                ]),
                            // Payment status follows the order's payments ("Record payment" on the order page).
                            Section::make('Source')
                                ->schema([
                                    Select::make('source')->label('Order came from')->options(OrderSource::class)->selectablePlaceholder(false)->required(),
                                ]),
                        ]),
                ]),
        ]);
    }

    /** "New order" — taken by staff. */
    public static function create(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(['default' => 1, 'lg' => 3])
                ->columnSpanFull()
                ->schema([
                    Section::make('Customer')
                        ->description('Typing a known mobile number fills in the rest from their last order.')
                        ->columnSpan(['lg' => 2])
                        ->columns(['default' => 1, 'md' => 3])
                        ->schema(self::customerFields(lookup: true)),
                    Section::make('Order')
                        ->columnSpan(1)
                        ->schema([
                            Select::make('source')
                                ->label('Order came from')
                                ->options(collect(OrderSource::cases())->reject(fn ($s) => $s === OrderSource::Web)->mapWithKeys(fn ($s) => [$s->value => $s->getLabel()]))
                                ->default(OrderSource::Phone->value)
                                ->selectablePlaceholder(false)
                                ->required(),
                            Select::make('status')
                                ->label('Start as')
                                ->options([
                                    OrderStatus::Confirmed->value => OrderStatus::Confirmed->getLabel().' (already agreed with the customer)',
                                    OrderStatus::Pending->value => OrderStatus::Pending->getLabel().' (still to confirm)',
                                ])
                                ->default(OrderStatus::Confirmed->value)
                                ->selectablePlaceholder(false)
                                ->required(),
                        ]),
                    Section::make('Products')
                        ->columnSpanFull()
                        ->schema([OrderItemsFields::items()->hiddenLabel(), ...OrderItemsFields::charges()]),
                    Section::make('Delivery address')
                        ->columnSpan(['lg' => 2])
                        ->columns(2)
                        ->visible(fn (Get $get) => filled($get('delivery_code')) && ! OrderItemsFields::isPickup($get('delivery_code')))
                        ->schema(self::addressFields(required: true)),
                    Section::make('Notes')
                        ->columnSpan(1)
                        ->schema(self::noteFields()),
                ]),
        ]);
    }

    /**
     * @return array<int, mixed>
     */
    public static function customerFields(bool $lookup = false): array
    {
        return [
            TextInput::make('phone')
                ->label('Mobile')
                ->tel()
                ->required()
                ->dehydrateStateUsing(fn (?string $state) => Phone::normalize($state))
                ->rules([fn (): Closure => function (string $attribute, $value, Closure $fail) {
                    if (! Phone::isValid($value)) {
                        $fail('Enter a valid Bangladeshi mobile number, e.g. 01712345678.');
                    }
                }])
                ->live(onBlur: $lookup)
                ->afterStateUpdated(function (Set $set, Get $get, ?string $state) use ($lookup) {
                    if (! $lookup || ! Phone::isValid($state)) {
                        return;
                    }

                    $previous = Order::where('phone', Phone::normalize($state))->latest('id')->first();

                    foreach (['name', 'email', 'district', 'area', 'address'] as $field) {
                        if ($previous && blank($get($field)) && filled($previous->{$field})) {
                            $set($field, $previous->{$field});
                        }
                    }
                }),
            TextInput::make('name')->required()->maxLength(120),
            TextInput::make('email')->email()->maxLength(120),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    public static function addressFields(bool $required = false): array
    {
        return [
            Select::make('district')
                ->options(fn () => collect(Arr::flatten(require database_path('data/bd-districts.php')))->sort()->mapWithKeys(fn ($d) => [$d => $d])->all())
                ->searchable()
                ->required($required),
            TextInput::make('area')->label('Area / thana')->maxLength(80),
            Textarea::make('address')->rows(2)->maxLength(255)->required($required)->columnSpanFull(),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    public static function noteFields(): array
    {
        return [
            Textarea::make('customer_note')->label('Note from the customer')->rows(2)->maxLength(500),
            Textarea::make('staff_note')->label('Staff note')->helperText('Only visible in the admin.')->rows(2)->maxLength(2000),
        ];
    }
}
