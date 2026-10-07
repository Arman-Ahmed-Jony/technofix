<?php

namespace App\Filament\Resources\HomeSections\Schemas;

use App\Models\Category;
use App\Models\HomeSection;
use App\Support\IconSprite;
use Closure;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class HomeSectionForm
{
    /** Which heading fields each type uses. */
    private const HEADINGS = [
        'categories' => ['eyebrow', 'title'],
        'departments' => ['eyebrow', 'title'],
        'deals' => ['eyebrow', 'title'],
        'product_tabs' => ['eyebrow', 'title'],
        'pc_builder' => ['eyebrow', 'title', 'subtitle'],
        'category_row' => ['eyebrow', 'title'],
        'services' => ['eyebrow', 'title', 'subtitle'],
        'brands' => ['title'],
        'about' => ['title'],
    ];

    public static function configure(Schema $schema): Schema
    {
        $is = fn (string ...$types) => fn (Get $get) => in_array($get('type'), $types, true);
        $uses = fn (string $field) => fn (Get $get) => in_array($field, self::HEADINGS[$get('type')] ?? [], true);
        $categories = fn () => Category::treeOptions();

        return $schema->components([
            Section::make('Section')
                ->columnSpanFull()
                ->schema([
                    Select::make('type')
                        ->label('What it shows')
                        ->options(collect(HomeSection::TYPES)->map(fn (array $type) => $type[0]))
                        ->required()
                        ->live()
                        ->disabledOn('edit')
                        ->helperText(fn (Get $get) => HomeSection::TYPES[$get('type')][2] ?? 'Pick a type — its options appear below.'),
                    Toggle::make('is_active')->label('Show on the home page')->default(true),
                ]),

            Section::make('Headings')
                ->columns(2)
                ->columnSpanFull()
                ->visible(fn (Get $get) => isset(self::HEADINGS[$get('type')]))
                ->schema([
                    TextInput::make('eyebrow')->label('Small label above')->maxLength(80)->visible($uses('eyebrow')),
                    TextInput::make('title')
                        ->label('Heading')
                        ->maxLength(160)
                        ->placeholder(fn (Get $get) => $get('type') === 'category_row' ? 'Empty = the category name' : null)
                        ->visible($uses('title')),
                    Textarea::make('subtitle')->label('Short text')->rows(2)->maxLength(300)->visible($uses('subtitle'))->columnSpanFull(),
                ]),

            Section::make('Content')
                ->columnSpanFull()
                ->visible(fn (Get $get) => filled($get('type')))
                ->schema([
                    // hero
                    Toggle::make('settings.brand_zones')
                        ->label('Show the Exclusive Zone brand cards beside the slider')
                        ->helperText('Brands marked “Exclusive Zone” in Catalog → Brands. Slides: Content → Banners.')
                        ->default(true)
                        ->visible($is('hero')),

                    // categories
                    Repeater::make('settings.category_ids')
                        ->label('Categories, in order')
                        ->simple(Select::make('id')->options($categories)->searchable()->required())
                        ->reorderable()
                        ->addActionLabel('Add category')
                        ->minItems(1)
                        ->maxItems(24)
                        ->visible($is('categories')),

                    // departments
                    ...collect([
                        'big' => ['Big card (left)', 'Heading', null],
                        'wide' => ['Wide card (top right)', 'Heading', 'Short text'],
                        'small1' => ['Small card 1', 'Heading (empty = category name)', 'Small label'],
                        'small2' => ['Small card 2', 'Heading (empty = category name)', 'Small label'],
                    ])->map(fn (array $card, string $slot) => Fieldset::make($card[0])
                        ->columns(3)
                        ->visible($is('departments'))
                        ->schema(array_filter([
                            Select::make("settings.{$slot}.category_id")->label('Category')->options($categories)->searchable()->required(),
                            TextInput::make("settings.{$slot}.heading")->label($card[1])->maxLength(80),
                            $card[2] ? TextInput::make("settings.{$slot}.text")->label($card[2])->maxLength(80) : null,
                        ])))->values()->all(),

                    // deals
                    Text::make('Shows products marked “Hot deal” (Catalog → Products), with a countdown to the end of the day.')->visible($is('deals')),

                    // product tabs
                    Repeater::make('settings.tabs')
                        ->label('Tabs')
                        ->schema([
                            TextInput::make('label')->maxLength(40)->placeholder('e.g. Featured'),
                            Select::make('source')->label('Products')->options(HomeSection::PRODUCT_SOURCES)->required(),
                        ])
                        ->columns(2)
                        ->reorderable()
                        ->addActionLabel('Add tab')
                        ->minItems(1)
                        ->maxItems(5)
                        ->visible($is('product_tabs')),
                    TextInput::make('settings.limit')
                        ->label('Products per tab / row')
                        ->numeric()->integer()->minValue(4)->maxValue(20)->default(10)
                        ->visible($is('product_tabs', 'category_row')),

                    // category row
                    Select::make('settings.category_id')->label('Category')->options($categories)->searchable()->required()->visible($is('category_row')),

                    // pc builder
                    TextInput::make('settings.highlight')
                        ->label('Words of the heading to highlight')
                        ->maxLength(80)
                        ->helperText('Write them exactly as in the heading.')
                        ->visible($is('pc_builder')),
                    Repeater::make('settings.steps')
                        ->label('Steps')
                        ->schema([
                            TextInput::make('icon')->placeholder('e.g. shield-check')->maxLength(50),
                            TextInput::make('title')->required()->maxLength(40),
                            Textarea::make('text')->rows(2)->maxLength(160)->columnSpanFull(),
                        ])
                        ->columns(2)
                        ->maxItems(3)
                        ->reorderable()
                        ->addActionLabel('Add step')
                        ->visible($is('pc_builder')),

                    // ticker
                    Repeater::make('settings.messages')
                        ->label('Messages')
                        ->helperText('They scroll one after another in a loop. A message disappears by itself after its “show until” time.')
                        ->schema([
                            TextInput::make('text')->label('Message')->required()->maxLength(200)->columnSpan(['default' => 1, 'md' => 2])
                                ->placeholder('e.g. Friday 10 October: all branches open from 2 PM'),
                            TextInput::make('url')->label('Link (optional)')->maxLength(500)
                                ->placeholder('/offers, {call}, {whatsapp}'),
                            DateTimePicker::make('until')->label('Show until (optional)')->seconds(false),
                        ])
                        ->columns(['default' => 1, 'md' => 4])
                        ->defaultItems(1)
                        ->minItems(1)
                        ->maxItems(10)
                        ->reorderable()
                        ->addActionLabel('Add message')
                        ->visible($is('ticker')),
                    Fieldset::make('Look')
                        ->columns(['default' => 1, 'md' => 3])
                        ->schema([
                            TextInput::make('settings.label')->label('Label at the start')->maxLength(20)->default('Notice')
                                ->helperText('e.g. Notice, News, Offer. Empty = icon only.'),
                            TextInput::make('settings.icon')->label('Icon')->maxLength(50)->default('speakerphone')
                                ->helperText('A Tabler icon name, e.g. speakerphone, news, bell-ringing.')
                                ->rules([fn (): Closure => function (string $attribute, $value, Closure $fail) {
                                    if (filled($value) && ! IconSprite::exists($value)) {
                                        $fail('There is no Tabler icon with this name.');
                                    }
                                }]),
                            Select::make('settings.speed')->label('Speed')->options(['slow' => 'Slow', 'normal' => 'Normal', 'fast' => 'Fast'])
                                ->default('normal')->selectablePlaceholder(false),
                        ])
                        ->visible($is('ticker')),

                    // services / brands
                    Text::make('Shows up to 6 services from Content → Repair & services.')->visible($is('services')),
                    Text::make('Shows the brands marked “featured” in Catalog → Brands.')->visible($is('brands')),

                    // about
                    Textarea::make('body')
                        ->label('Text')
                        ->rows(6)
                        ->maxLength(3000)
                        ->helperText('Leave an empty line between paragraphs.')
                        ->visible($is('about')),
                ]),
        ]);
    }
}
