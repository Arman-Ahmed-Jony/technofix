<?php

namespace App\Filament\Pages;

use App\Enums\StaffArea;
use App\Settings\DeliverySettings;
use BackedEnum;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use UnitEnum;

class ManageDelivery extends SettingsPage
{
    protected static string $settings = DeliverySettings::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-truck';

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Delivery charges';

    protected static ?string $title = 'Delivery charges';

    protected static ?int $navigationSort = 2;

    public function form(Schema $schema): Schema
    {
        $districts = collect(require database_path('data/bd-districts.php'))
            ->mapWithKeys(fn (array $list, string $division) => ["{$division} Division" => array_combine($list, $list)])
            ->all();

        return $schema->components([
            Section::make('Free delivery')
                ->columnSpanFull()
                ->columns(['md' => 2])
                ->schema([
                    TextInput::make('free_delivery_over')
                        ->label('Free home delivery when the cart subtotal is at least')
                        ->integer()
                        ->minValue(1)
                        ->prefix('BDT')
                        ->placeholder('Leave empty to turn off')
                        ->helperText('Applies to home-delivery options only. Leave empty to always charge delivery.'),
                ]),

            Section::make('Delivery options')
                ->columnSpanFull()
                ->description('Shown at checkout in this order (drag to reorder). Customers only see the options that serve their district; when a district matches an option limited to "only these districts", that option is picked automatically.')
                ->schema([
                    Repeater::make('options')
                        ->hiddenLabel()
                        ->schema([
                            Hidden::make('code')->default(fn () => Str::lower(Str::random(10))),
                            Grid::make(['default' => 1, 'md' => 4])->schema([
                                TextInput::make('label')
                                    ->label('Name')
                                    ->required()
                                    ->maxLength(40)
                                    ->placeholder('Inside Dhaka city')
                                    ->live(onBlur: true)
                                    ->columnSpan(['md' => 2]),
                                TextInput::make('fee')
                                    ->label('Charge')
                                    ->required()
                                    ->integer()
                                    ->minValue(0)
                                    ->maxValue(100000)
                                    ->prefix('BDT')
                                    ->live(onBlur: true),
                                TextInput::make('eta')
                                    ->label('Delivery time')
                                    ->required()
                                    ->maxLength(40)
                                    ->placeholder('1–2 days'),
                            ]),
                            Grid::make(['default' => 1, 'md' => 4])->schema([
                                Select::make('type')
                                    ->label('Type')
                                    ->options(['delivery' => 'Home delivery', 'pickup' => 'Store pickup'])
                                    ->default('delivery')
                                    ->required()
                                    ->selectablePlaceholder(false)
                                    ->native(false)
                                    ->live(),
                                Select::make('area')
                                    ->label('Available for')
                                    ->options(['all' => 'All districts', 'only' => 'Only these districts', 'except' => 'All except these districts'])
                                    ->default('all')
                                    ->required()
                                    ->selectablePlaceholder(false)
                                    ->native(false)
                                    ->live()
                                    ->visible(fn (Get $get) => $get('type') !== 'pickup'),
                                Select::make('districts')
                                    ->label('Districts')
                                    ->options($districts)
                                    ->multiple()
                                    ->searchable()
                                    ->required()
                                    ->visible(fn (Get $get) => $get('type') !== 'pickup' && in_array($get('area'), ['only', 'except'], true))
                                    ->columnSpan(['md' => 2]),
                            ]),
                            Toggle::make('active')
                                ->label('Show at checkout')
                                ->default(true),
                        ])
                        ->itemLabel(fn (array $state): string => ($state['label'] ?? null ?: 'New option').' · BDT '.number_format((int) ($state['fee'] ?? 0)).(($state['active'] ?? true) ? '' : ' (hidden)'))
                        ->reorderable()
                        ->collapsible()
                        ->cloneable()
                        ->minItems(1)
                        ->addActionLabel('Add delivery option'),
                ]),
        ]);
    }

    /**
     * Normalise each option (hidden fields are not submitted) and make sure checkout always has something to offer.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $codes = [];

        $data['options'] = collect($data['options'] ?? [])
            ->values()
            ->map(function (array $option) use (&$codes) {
                $code = $option['code'] ?? '';
                if ($code === '' || in_array($code, $codes, true)) {
                    $code = Str::lower(Str::random(10));
                }
                $codes[] = $code;

                $type = ($option['type'] ?? 'delivery') === 'pickup' ? 'pickup' : 'delivery';
                $area = $type === 'pickup' ? 'all' : ($option['area'] ?? 'all');

                return [
                    'code' => $code,
                    'label' => trim($option['label']),
                    'eta' => trim($option['eta']),
                    'fee' => (int) $option['fee'],
                    'type' => $type,
                    'area' => $area,
                    'districts' => $area === 'all' ? [] : array_values($option['districts'] ?? []),
                    'active' => (bool) ($option['active'] ?? true),
                ];
            })
            ->all();

        if (! collect($data['options'])->contains('active', true)) {
            throw ValidationException::withMessages(['data.options' => 'Keep at least one delivery option visible at checkout.']);
        }

        $data['free_delivery_over'] = filled($data['free_delivery_over'] ?? null) ? (int) $data['free_delivery_over'] : null;

        return $data;
    }

    public static function canAccess(): bool
    {
        return StaffArea::Settings->allowed();
    }
}
