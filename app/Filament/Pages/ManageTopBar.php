<?php

namespace App\Filament\Pages;

use App\Enums\StaffArea;
use App\Settings\StoreSettings;
use App\Settings\TopBarSettings;
use App\Support\IconSprite;
use BackedEnum;
use Closure;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;
use UnitEnum;

/** The dark bar above the header (computers): hotline, opening hours, a short message and quick links. */
class ManageTopBar extends SettingsPage
{
    protected static string $settings = TopBarSettings::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-bars-2';

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'Top bar';

    protected static ?string $title = 'Top bar';

    protected static ?int $navigationSort = 6;

    protected static ?string $slug = 'top-bar';

    public static function canAccess(): bool
    {
        return StaffArea::Content->allowed();
    }

    public function getSubheading(): ?string
    {
        return 'The thin dark bar at the very top of every page on computers. Phones show these links in the menu instead.';
    }

    public function form(Schema $schema): Schema
    {
        $store = app(StoreSettings::class);

        return $schema->components([
            Section::make('Left side')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    Toggle::make('show_hotline')->label('Show the hotline')
                        ->helperText(fn () => 'Now: '.$store->hotline.' — change it in Settings → Store & contact.'),
                    Toggle::make('show_hours')->label('Show the opening hours')
                        ->helperText(fn () => 'Now: '.$store->hours.' — change it in Settings → Store & contact.'),
                    TextInput::make('message')->label('Short message')->maxLength(60)
                        ->placeholder('Cash on delivery all over Bangladesh')
                        ->helperText('Leave empty for none. Shown on wider screens only, so the bar never wraps.'),
                    TextInput::make('message_icon')->label('Icon for the message')->maxLength(50)->placeholder('truck-delivery')
                        ->helperText(new HtmlString('<a href="https://tabler.io/icons" target="_blank" rel="noopener" class="underline">Tabler icon</a> name'))
                        ->rules([fn (): Closure => function (string $attribute, $value, Closure $fail) {
                            if (filled($value) && ! IconSprite::exists($value)) {
                                $fail('There is no Tabler icon with this name.');
                            }
                        }]),
                ]),

            Section::make('Links on the right')
                ->description('Link = a page on this site starting with / (e.g. /offers, /page/warranty-policy) or a full https:// address. Drag to change the order; keep the texts short — on smaller laptops about four links fit. The glowing “Download software” link is set in Content → Software download.')
                ->columnSpanFull()
                ->schema([
                    Repeater::make('links')
                        ->hiddenLabel()
                        ->schema([
                            TextInput::make('label')->label('Text')->required()->maxLength(25)->placeholder('Track Order'),
                            TextInput::make('url')->label('Link')->required()->maxLength(500)->placeholder('/track-order')
                                ->rules([fn (): Closure => function (string $attribute, $value, Closure $fail) {
                                    $value = trim((string) $value);
                                    $internal = str_starts_with($value, '/') && ! str_starts_with($value, '//');
                                    $external = preg_match('#^https?://#i', $value) && filter_var($value, FILTER_VALIDATE_URL);
                                    if (! $internal && ! $external) {
                                        $fail('Start with / for a page on this site, or use a full https:// address.');
                                    }
                                }]),
                        ])
                        ->columns(2)
                        ->defaultItems(0)
                        ->maxItems(5)
                        ->reorderable()
                        ->addActionLabel('Add a link'),
                ]),
        ]);
    }
}
