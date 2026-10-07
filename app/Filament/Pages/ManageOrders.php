<?php

namespace App\Filament\Pages;

use App\Enums\StaffArea;
use App\Settings\OrderSettings;
use BackedEnum;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use UnitEnum;

class ManageOrders extends SettingsPage
{
    protected static string $settings = OrderSettings::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shopping-bag';

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Stock & orders';

    protected static ?string $title = 'Order settings';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'order-settings';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Emails')
                ->columnSpanFull()
                ->description('Emails are queued and go out within a minute (the server runs the queue every minute).')
                ->schema([
                    TagsInput::make('notify_emails')
                        ->label('Send “New order” emails to')
                        ->placeholder('Add an email and press Enter')
                        ->nestedRecursiveRules(['email'])
                        ->helperText('For website orders. Leave empty to turn these emails off.'),
                    Toggle::make('email_customers')
                        ->label('Email customers their order confirmation and updates')
                        ->helperText('Only customers who give an email address at checkout (it’s optional). They hear when their order is confirmed, shipped, delivered or cancelled.'),
                ]),
            Section::make('Stock')
                ->columnSpanFull()
                ->description('Orders always count stock down when placed and back up when cancelled or returned — see "Stock history" on each product.')
                ->schema([
                    Toggle::make('auto_stock_status')
                        ->label('Switch products to “Out of Stock” automatically when their quantity reaches 0')
                        ->helperText('And back to “In Stock” when stock is added. Turn this on only once product quantities are kept up to date — otherwise products with quantity 0 go out of stock after their first order. Pre-order, Upcoming and Call for Price products are never changed.'),
                    TextInput::make('low_stock_at')
                        ->label('Warn about low stock at')
                        ->integer()
                        ->minValue(0)
                        ->maxValue(1000)
                        ->suffix('or fewer left')
                        ->required()
                        ->helperText('Shown on the dashboard and in Inventory → Stock overview.'),
                ]),
            Section::make('Incomplete checkouts')
                ->columnSpanFull()
                ->columns(['default' => 1, 'md' => 3])
                ->description('Customers who type their mobile number at checkout but leave without ordering. They appear in Leads as “Incomplete checkout”, with their cart and address, so you can call them. When they (or you, from the lead) place the order, the lead is marked Converted.')
                ->schema([
                    Toggle::make('capture_incomplete_checkouts')
                        ->label('Save incomplete checkouts')
                        ->live()
                        ->columnSpan(['md' => 2]),
                    TextInput::make('incomplete_checkout_minutes')
                        ->label('Add to Leads after')
                        ->integer()
                        ->minValue(10)
                        ->maxValue(1440)
                        ->suffix('minutes')
                        ->required()
                        ->visible(fn (Get $get) => $get('capture_incomplete_checkouts')),
                    Textarea::make('incomplete_checkout_message')
                        ->label('WhatsApp follow-up message')
                        ->rows(3)
                        ->maxLength(600)
                        ->required()
                        ->helperText('Used by the WhatsApp button on the lead. {name} = the customer’s name, {items} = what was in the cart, {link} = a link that refills their cart and opens the checkout.')
                        ->visible(fn (Get $get) => $get('capture_incomplete_checkouts'))
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function canAccess(): bool
    {
        return StaffArea::Settings->allowed();
    }
}
