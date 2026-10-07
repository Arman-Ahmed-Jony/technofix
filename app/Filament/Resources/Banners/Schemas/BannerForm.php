<?php

namespace App\Filament\Resources\Banners\Schemas;

use App\Models\Banner;
use App\Support\IconSprite;
use Closure;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class BannerForm
{
    public static function configure(Schema $schema): Schema
    {
        $hero = fn (Get $get) => $get('placement') === 'hero';
        $designed = fn (Get $get) => $get('placement') === 'hero' && $get('style') !== 'image';
        $fullImage = fn (Get $get) => $get('placement') === 'hero' && $get('style') === 'image';
        $announcement = fn (Get $get) => $get('placement') === 'announcement';

        return $schema->components([
            Grid::make(['default' => 1, 'lg' => 3])
                ->columnSpanFull()
                ->schema([
                    Grid::make(1)
                        ->columnSpan(['lg' => 2])
                        ->schema([
                            Section::make('Banner')
                                ->columns(2)
                                ->schema([
                                    ToggleButtons::make('placement')
                                        ->label('Where')
                                        ->options(Banner::PLACEMENTS)
                                        ->icons(['hero' => 'heroicon-m-rectangle-group', 'announcement' => 'heroicon-m-megaphone'])
                                        ->default('hero')
                                        ->inline()
                                        ->required()
                                        ->live()
                                        ->columnSpanFull(),
                                    ToggleButtons::make('style')
                                        ->options(['designed' => 'Designed (text + picture)', 'image' => 'Full image (a finished design)'])
                                        ->default('designed')
                                        ->inline()
                                        ->live()
                                        ->visible($hero)
                                        ->columnSpanFull(),
                                    TextInput::make('title')
                                        ->label(fn (Get $get) => match (true) {
                                            $get('placement') === 'announcement' => 'Message',
                                            $get('style') === 'image' => 'Description (for search engines & screen readers)',
                                            default => 'Headline',
                                        })
                                        ->required()
                                        ->maxLength(160)
                                        ->columnSpanFull(),
                                    TextInput::make('highlight')
                                        ->label('Words to highlight')
                                        ->maxLength(80)
                                        ->placeholder('e.g. 15% off')
                                        ->helperText('Shown in the brand gradient — write them exactly as in the headline.')
                                        ->visible($designed),
                                    TextInput::make('eyebrow')->label('Small label above')->maxLength(60)->placeholder('e.g. Happy Hour')->visible($designed),
                                    Textarea::make('text')->label('Short text')->rows(2)->maxLength(300)->visible($designed)->columnSpanFull(),
                                    TextInput::make('icon')
                                        ->maxLength(50)
                                        ->placeholder('e.g. flame')
                                        ->helperText(new HtmlString('Optional <a href="https://tabler.io/icons" target="_blank" rel="noopener" class="underline">Tabler icon</a> name.'))
                                        ->hidden($fullImage)
                                        ->rules([fn (): Closure => function (string $attribute, $value, Closure $fail) {
                                            if (filled($value) && ! IconSprite::exists($value)) {
                                                $fail('There is no Tabler icon with this name.');
                                            }
                                        }]),
                                    Select::make('color')
                                        ->label(fn (Get $get) => $get('placement') === 'announcement' ? 'Bar colour' : 'Glow colour')
                                        ->options(Banner::COLORS)
                                        ->default('blue')
                                        ->selectablePlaceholder(false)
                                        ->hidden($fullImage),
                                ]),

                            Section::make(fn (Get $get) => $get('style') === 'image' && $get('placement') === 'hero' ? 'Link' : 'Buttons')
                                ->columns(2)
                                ->schema([
                                    TextInput::make('button_label')
                                        ->label(fn (Get $get) => $get('placement') === 'announcement' ? 'Link text' : 'Main button')
                                        ->maxLength(40)
                                        ->placeholder('e.g. Shop deals')
                                        ->hidden($fullImage),
                                    TextInput::make('button_url')
                                        ->label('Link')
                                        ->maxLength(500)
                                        ->placeholder('/offers')
                                        ->helperText('A page like /offers or /laptop, a full https:// link, or {call} / {whatsapp} for the store’s numbers.'),
                                    TextInput::make('button2_label')->label('Second button')->maxLength(40)->placeholder('e.g. Ask an expert')->visible($designed),
                                    TextInput::make('button2_url')->label('Second link')->maxLength(500)->placeholder('{call}')->visible($designed),
                                ]),

                            Section::make('Picture')
                                ->columns(2)
                                ->visible($designed)
                                ->schema([
                                    Select::make('visual')
                                        ->label('Picture on the right')
                                        ->options(Banner::VISUALS)
                                        ->default('none')
                                        ->selectablePlaceholder(false)
                                        ->live()
                                        ->helperText('Shown on large screens; phones show the text only.'),
                                    SpatieMediaLibraryFileUpload::make('picture')
                                        ->collection('picture')
                                        ->image()
                                        ->maxSize(3072)
                                        ->helperText('A product photo with a transparent background (PNG) looks best — about 700 × 550 px.')
                                        ->visible(fn (Get $get) => $get('visual') === 'picture'),
                                ]),

                            Section::make('Banner images')
                                ->description('Upload a finished design — the whole banner links to the address above.')
                                ->columns(2)
                                ->visible($fullImage)
                                ->schema([
                                    SpatieMediaLibraryFileUpload::make('desktop')
                                        ->label('Desktop image')
                                        ->collection('desktop')
                                        ->image()
                                        ->maxSize(4096)
                                        ->required($fullImage)
                                        ->helperText('2:1 shape, about 2000 × 1000 px. Keep text away from the bottom-left corner (slide buttons).'),
                                    SpatieMediaLibraryFileUpload::make('mobile')
                                        ->label('Phone image (optional)')
                                        ->collection('mobile')
                                        ->image()
                                        ->maxSize(3072)
                                        ->helperText('About 900 × 850 px. Without it, phones show the whole desktop image, smaller.'),
                                ]),
                        ]),

                    Section::make('When')
                        ->columnSpan(1)
                        ->schema([
                            Toggle::make('is_active')->label('Switched on')->default(true),
                            DateTimePicker::make('starts_at')->label('Start')->seconds(false)->helperText('Empty = straight away.'),
                            DateTimePicker::make('ends_at')->label('End')->seconds(false)->after('starts_at')->helperText('Empty = until switched off.'),
                        ]),
                ]),
        ]);
    }
}
