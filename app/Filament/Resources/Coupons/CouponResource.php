<?php

namespace App\Filament\Resources\Coupons;

use App\Enums\CouponType;
use App\Enums\OrderStatus;
use App\Filament\Resources\Coupons\Pages\CreateCoupon;
use App\Filament\Resources\Coupons\Pages\EditCoupon;
use App\Filament\Resources\Coupons\Pages\ListCoupons;
use App\Models\Coupon;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use UnitEnum;

/**
 * Coupon codes customers type at checkout. Checked and priced by App\Services\CouponService.
 */
class CouponResource extends Resource
{
    protected static ?string $model = Coupon::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-ticket';

    protected static string|UnitEnum|null $navigationGroup = 'Sales';

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'code';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Code')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    TextInput::make('code')
                        ->required()
                        ->maxLength(40)
                        ->unique(ignoreRecord: true)
                        ->regex('/^[A-Za-z0-9_-]+$/')
                        ->validationMessages(['regex' => 'Use letters, numbers, - and _ only (no spaces).'])
                        ->dehydrateStateUsing(fn (?string $state) => Str::upper(trim((string) $state)))
                        ->extraInputAttributes(['style' => 'text-transform: uppercase; font-family: ui-monospace, monospace'])
                        ->placeholder('EID10')
                        ->helperText('What customers type at checkout (capital letters or not).')
                        ->suffixAction(Action::make('generate')
                            ->icon('heroicon-m-sparkles')
                            ->tooltip('Make a random code')
                            ->action(fn (Set $set) => $set('code', 'TF'.Str::upper(Str::random(6))))),
                    TextInput::make('description')
                        ->label('Note for staff')
                        ->maxLength(255)
                        ->placeholder('e.g. Eid campaign on Facebook'),
                    Toggle::make('is_active')->label('On')->default(true)->helperText('Turn off to stop the code working straight away.'),
                ]),

            Section::make('Discount')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    ToggleButtons::make('type')
                        ->options(CouponType::class)
                        ->icons([
                            CouponType::Percent->value => 'heroicon-m-receipt-percent',
                            CouponType::Fixed->value => 'heroicon-m-banknotes',
                            CouponType::FreeDelivery->value => 'heroicon-m-truck',
                        ])
                        ->default(CouponType::Percent)
                        ->inline()
                        ->required()
                        ->live()
                        ->helperText('Free delivery takes the delivery charge off; pickup orders get nothing off.')
                        ->columnSpanFull(),
                    TextInput::make('value')
                        ->label(fn (Get $get) => self::typeOf($get) === CouponType::Percent ? 'Percent off' : 'Amount off')
                        ->numeric()
                        ->integer()
                        ->required()
                        ->minValue(1)
                        ->maxValue(fn (Get $get) => self::typeOf($get) === CouponType::Percent ? 100 : null)
                        ->prefix(fn (Get $get) => self::typeOf($get) === CouponType::Percent ? null : 'BDT')
                        ->suffix(fn (Get $get) => self::typeOf($get) === CouponType::Percent ? '%' : null)
                        ->helperText(fn (Get $get) => self::typeOf($get) === CouponType::Fixed ? 'Taken off the products, not the delivery charge.' : null)
                        ->hidden(fn (Get $get) => self::typeOf($get) === CouponType::FreeDelivery), // the model sets 0
                    TextInput::make('max_discount')
                        ->label('Maximum discount')
                        ->numeric()
                        ->integer()
                        ->minValue(1)
                        ->prefix('BDT')
                        ->helperText('Leave empty for no limit.')
                        ->visible(fn (Get $get) => self::typeOf($get) === CouponType::Percent),
                    TextInput::make('min_subtotal')
                        ->label('Minimum order')
                        ->numeric()
                        ->integer()
                        ->minValue(1)
                        ->prefix('BDT')
                        ->helperText('Products only, before delivery. Leave empty for any amount.'),
                ]),

            Section::make('Dates & limits')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    DateTimePicker::make('starts_at')->label('Starts')->seconds(false)->helperText('Leave empty to start now.'),
                    DateTimePicker::make('ends_at')->label('Ends')->seconds(false)->after('starts_at')->helperText('Leave empty for no end date.'),
                    TextInput::make('usage_limit')
                        ->label('Total uses')
                        ->numeric()
                        ->integer()
                        ->minValue(1)
                        ->helperText('Leave empty for unlimited. Cancelled and returned orders give their use back.'),
                    TextInput::make('per_customer_limit')
                        ->label('Uses per customer')
                        ->numeric()
                        ->integer()
                        ->minValue(1)
                        ->helperText('Counted by mobile number. Leave empty for unlimited.'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount([
                'orders as uses_count' => fn (Builder $q) => $q->whereNotIn('status', [OrderStatus::Cancelled->value, OrderStatus::Returned->value]),
            ]))
            ->columns([
                TextColumn::make('code')
                    ->searchable(['code', 'description'])
                    ->fontFamily('mono')
                    ->weight('medium')
                    ->copyable()
                    ->description(fn (Coupon $record) => $record->description),
                TextColumn::make('type')
                    ->label('Discount')
                    ->formatStateUsing(fn (Coupon $record) => $record->summary('BDT ')),
                TextColumn::make('min_subtotal')
                    ->label('Min. order')
                    ->formatStateUsing(fn ($state) => 'BDT '.number_format((int) $state))
                    ->placeholder('—'),
                TextColumn::make('uses_count')
                    ->label('Used')
                    ->formatStateUsing(fn (Coupon $record) => $record->uses_count.($record->usage_limit ? ' / '.$record->usage_limit : ''))
                    ->sortable(),
                TextColumn::make('ends_at')
                    ->label('Ends')
                    ->dateTime('d M Y, h:i A')
                    ->placeholder('No end date')
                    ->sortable(),
                TextColumn::make('state')
                    ->label('Status')
                    ->state(fn (Coupon $record) => $record->state($record->uses_count))
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'Active' => 'success',
                        'Scheduled' => 'info',
                        'Used up' => 'warning',
                        default => 'gray',
                    }),
                ToggleColumn::make('is_active')->label('On'),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('On'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    protected static function typeOf(Get $get): ?CouponType
    {
        $type = $get('type');

        return $type instanceof CouponType ? $type : CouponType::tryFrom((string) $type);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCoupons::route('/'),
            'create' => CreateCoupon::route('/create'),
            'edit' => EditCoupon::route('/{record}/edit'),
        ];
    }
}
