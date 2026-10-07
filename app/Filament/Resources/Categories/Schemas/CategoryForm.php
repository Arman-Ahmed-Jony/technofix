<?php

namespace App\Filament\Resources\Categories\Schemas;

use App\Filament\Resources\Attributes\AttributeResource;
use App\Filament\Schemas\BuyingGuideFields;
use App\Models\Category;
use App\Models\CategoryAttribute;
use App\Support\IconSprite;
use App\Support\Slugs;
use Closure;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class CategoryForm
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
                            Section::make()
                                ->columns(2)
                                ->schema([
                                    TextInput::make('name')
                                        ->required()
                                        ->maxLength(120)
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(fn (Set $set, ?string $state, string $operation) => $operation === 'create' ? $set('slug', Slugs::unique((string) $state)) : null),
                                    TextInput::make('slug')
                                        ->label('URL')
                                        ->prefix(url('/').'/')
                                        ->required()
                                        ->maxLength(190)
                                        ->rules(fn (?Category $record) => [Slugs::rule($record)])
                                        ->helperText('Changing the URL of a live category breaks old links and search results.'),
                                    Select::make('parent_id')
                                        ->label('Parent category')
                                        ->options(fn (?Category $record) => Category::treeOptions($record))
                                        ->searchable()
                                        ->placeholder('None — top-level (menu department)'),
                                    TextInput::make('icon')
                                        ->helperText(new HtmlString('A <a href="https://tabler.io/icons" target="_blank" rel="noopener" class="underline">Tabler icon</a> name, e.g. <code>device-laptop</code>. Empty = same as the parent.'))
                                        ->maxLength(50)
                                        ->rules([fn (): Closure => function (string $attribute, $value, Closure $fail) {
                                            if (filled($value) && ! IconSprite::exists($value)) {
                                                $fail('There is no Tabler icon with this name.');
                                            }
                                        }]),
                                ]),

                            Section::make('Buying guide')
                                ->description('The box at the bottom of the category page (good for SEO). Leave empty to use the defaults from Content → Buying guide. For pages like “/laptop/dell”, use Brand pages below the form.')
                                ->collapsible()
                                ->schema([
                                    RichEditor::make('description')
                                        ->label('Text')
                                        ->toolbarButtons([['bold', 'italic', 'link'], ['h3'], ['bulletList', 'orderedList'], ['undo', 'redo']]),
                                    BuyingGuideFields::faqs(),
                                ]),

                            Section::make('Specification fields')
                                ->description('The specs every product in this category has. They become inputs on the product form, rows in the spec table, and — when “Filter” is on — filters on the category page.')
                                ->schema([
                                    Repeater::make('templateAttributes')
                                        ->hiddenLabel()
                                        ->relationship()
                                        ->orderColumn('sort')
                                        ->reorderable()
                                        ->addActionLabel('Add spec field')
                                        ->defaultItems(0)
                                        ->table([
                                            TableColumn::make('Field')->markAsRequired()->width('42%'),
                                            TableColumn::make('Group'),
                                            TableColumn::make('Filter')->width('70px'),
                                            TableColumn::make('On card')->width('82px'),
                                        ])
                                        ->schema([
                                            Select::make('attribute_id')
                                                ->relationship('attribute', 'name')
                                                ->getOptionLabelFromRecordUsing(fn ($record) => $record->name.($record->unit ? " ({$record->unit})" : '').($record->is_system ? ' ★' : ''))
                                                ->searchable()
                                                ->preload()
                                                ->required()
                                                ->distinct()
                                                ->createOptionForm(AttributeResource::formFields())
                                                ->createOptionModalHeading('New spec field'),
                                            TextInput::make('group')
                                                ->default('Basic Information')
                                                ->required()
                                                ->maxLength(60)
                                                ->datalist(fn () => CategoryAttribute::query()->distinct()->orderBy('group')->pluck('group')->all()),
                                            Toggle::make('is_filterable'),
                                            Toggle::make('show_on_card'),
                                        ]),
                                ])
                                ->footer('★ = PC Builder field (used for compatibility checks). “On card” shows up to three values as chips on product cards.'),
                        ]),

                    Grid::make(1)
                        ->columnSpan(1)
                        ->schema([
                            Section::make('Visibility')
                                ->schema([
                                    Toggle::make('is_active')->label('Show on website')->default(true),
                                    Toggle::make('show_in_menu')->label('Show in menus')->default(true)
                                        ->helperText('Hidden from the mega menu but still reachable by URL.'),
                                    TextInput::make('sort')
                                        ->label('Position')
                                        ->helperText('Lower numbers come first among its siblings.')
                                        ->numeric()
                                        ->minValue(0)
                                        ->default(fn () => (Category::max('sort') ?? 0) + 1),
                                ]),
                            Section::make('Menu brands')
                                ->description('Brand links listed under this category in the mega menu.')
                                ->schema([
                                    Select::make('brands')
                                        ->hiddenLabel()
                                        ->relationship('brands', 'name')
                                        ->multiple()
                                        ->searchable()
                                        ->preload(),
                                ]),
                            Section::make('SEO')
                                ->collapsible()
                                ->collapsed()
                                ->schema([
                                    TextInput::make('meta_title')->maxLength(70)->helperText('Empty = the template in Settings → SEO & sharing.'),
                                    Textarea::make('meta_description')->rows(3)->maxLength(160)->helperText('Empty = the template in Settings → SEO & sharing.'),
                                ]),
                        ]),
                ]),
        ]);
    }
}
