<?php

namespace App\Filament\Pages;

use App\Enums\StaffArea;
use App\Settings\FooterSettings;
use App\Support\IconSprite;
use BackedEnum;
use Closure;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;
use UnitEnum;

/** Footer text, promise badges, the "talk to an expert" banner and the copyright line. */
class ManageFooter extends SettingsPage
{
    protected static string $settings = FooterSettings::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-bars-3-bottom-left';

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'Footer';

    protected static ?string $title = 'Footer';

    protected static ?int $navigationSort = 7;

    protected static ?string $slug = 'footer';

    public static function canAccess(): bool
    {
        return StaffArea::Content->allowed();
    }

    public function getSubheading(): ?string
    {
        return 'Footer links come from the menu, Pages and Repair & services; phone numbers, email, address and social links from Settings → Store & contact; the logo from Settings → Branding.';
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('About')
                ->columnSpanFull()
                ->schema([
                    Textarea::make('about')->label('Short text under the logo')->rows(2)->maxLength(300),
                    TextInput::make('copyright')->label('Copyright line')->required()->maxLength(150)->helperText('{year} becomes the current year.'),
                ]),
            Section::make('Facebook page box')
                ->description('A card with your cover photo, logo and name that opens your Facebook page, on the right of the footer.')
                ->columns(['default' => 1, 'md' => 3])
                ->columnSpanFull()
                ->schema([
                    Toggle::make('facebook_box')->label('Show the Facebook card')->live(),
                    TextInput::make('facebook_page_url')->label('Facebook page address')->url()->maxLength(255)
                        ->placeholder('https://www.facebook.com/yourpage')
                        ->helperText('Open your page on a computer and copy the address bar (a “share” link doesn’t work here). Empty = the Facebook link in Store & contact.')
                        ->rules([fn (): Closure => function (string $attribute, $value, Closure $fail) {
                            if (filled($value) && ! FooterSettings::isFacebookPage($value)) {
                                $fail('Use the page’s facebook.com address, e.g. https://www.facebook.com/yourpage (not a share link).');
                            }
                        }])
                        ->visible(fn (Get $get) => $get('facebook_box'))
                        ->columnSpan(['default' => 1, 'md' => 2]),
                    FileUpload::make('facebook_cover')->label('Cover photo (optional)')
                        ->disk('public')->directory('brand')->visibility('public')
                        ->image()->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])->maxSize(4096)
                        ->imagePreviewHeight('110')
                        ->helperText('Background of the card, behind your logo and name. Empty = brand blue. On Facebook: open your cover photo → ⋯ → Download. Any wide photo works (e.g. 820 × 312 px).')
                        ->visible(fn (Get $get) => $get('facebook_box'))
                        ->columnSpanFull(),
                ]),
            Section::make('Promise badges')
                ->description('The row of icons just above the footer. Up to 4 look best.')
                ->columnSpanFull()
                ->schema([
                    Repeater::make('badges')
                        ->hiddenLabel()
                        ->schema([
                            TextInput::make('icon')
                                ->required()
                                ->maxLength(50)
                                ->placeholder('truck-delivery')
                                ->helperText(new HtmlString('<a href="https://tabler.io/icons" target="_blank" rel="noopener" class="underline">Tabler icon</a> name'))
                                ->rules([fn (): Closure => function (string $attribute, $value, Closure $fail) {
                                    if (filled($value) && ! IconSprite::exists($value)) {
                                        $fail('There is no Tabler icon with this name.');
                                    }
                                }]),
                            TextInput::make('title')->required()->maxLength(40),
                            TextInput::make('text')->maxLength(60),
                        ])
                        ->columns(3)
                        ->maxItems(6)
                        ->reorderable()
                        ->addActionLabel('Add badge'),
                ]),
            Section::make('“Talk to an expert” banner')
                ->description('The dark banner above the footer on every page, with your Call and WhatsApp buttons.')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    Toggle::make('cta_enabled')->label('Show the banner')->live()->columnSpanFull(),
                    TextInput::make('cta_eyebrow')->label('Small label')->maxLength(40)->visible(fn (Get $get) => $get('cta_enabled')),
                    TextInput::make('cta_title')->label('Headline')->required()->maxLength(80)->visible(fn (Get $get) => $get('cta_enabled')),
                    TextInput::make('cta_highlight')->label('Coloured part of the headline')->maxLength(60)->visible(fn (Get $get) => $get('cta_enabled')),
                    Textarea::make('cta_text')->label('Text')->rows(2)->maxLength(250)->visible(fn (Get $get) => $get('cta_enabled'))->columnSpanFull(),
                ]),
        ]);
    }
}
