<?php

namespace App\Filament\Pages;

use App\Enums\StaffArea;
use App\Settings\ReviewSettings;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use UnitEnum;

/** Product reviews and questions: on/off, who may review, staff alerts and review requests after delivery. */
class ManageReviews extends SettingsPage
{
    protected static string $settings = ReviewSettings::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-star';

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Reviews & questions';

    protected static ?string $title = 'Reviews & questions';

    protected static ?int $navigationSort = 5;

    protected static ?string $slug = 'review-settings';

    public static function canAccess(): bool
    {
        return StaffArea::Settings->allowed();
    }

    public function getSubheading(): ?string
    {
        return 'Customers must be signed in to write. Nothing appears on the site until your team approves a review or answers a question (Catalog → Reviews / Questions).';
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Reviews')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    Toggle::make('reviews_enabled')->label('Show reviews on product pages')->live()->columnSpanFull(),
                    Toggle::make('buyers_only')->label('Only customers who bought the product can review it')
                        ->helperText('A delivered order with that product, under their account or the phone number they ordered with. Reviews from buyers get a “Verified purchase” badge either way.')
                        ->visible(fn (Get $get) => $get('reviews_enabled'))->columnSpanFull(),
                    Toggle::make('request_reviews')->label('Email customers asking for a review after delivery')->live()
                        ->visible(fn (Get $get) => $get('reviews_enabled')),
                    TextInput::make('request_after_days')->label('Days after delivery')->integer()->minValue(1)->maxValue(60)->required()
                        ->suffix('days')
                        ->visible(fn (Get $get) => $get('reviews_enabled') && $get('request_reviews')),
                ]),
            Section::make('Questions')
                ->columnSpanFull()
                ->schema([
                    Toggle::make('questions_enabled')->label('Let customers ask questions on product pages'),
                ]),
            Section::make('Alerts for your team')
                ->columnSpanFull()
                ->schema([
                    Toggle::make('notify_staff')->label('Email the team about each new review and question')
                        ->helperText('Sent to the order email addresses in Settings → Stock & orders.'),
                ]),
        ]);
    }
}
