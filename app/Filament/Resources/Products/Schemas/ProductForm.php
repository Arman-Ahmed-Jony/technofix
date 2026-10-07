<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Enums\ProductStatus;
use App\Enums\StockStatus;
use App\Models\Attribute;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductSpec;
use App\Support\CatalogCache;
use App\Support\Slugs;
use Closure;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(['default' => 1, 'lg' => 3])
                ->columnSpanFull()
                ->schema([
                    Tabs::make()
                        ->columnSpan(['lg' => 2])
                        ->persistTabInQueryString()
                        ->tabs([
                            self::detailsTab(),
                            self::imagesTab(),
                            self::colorsTab(),
                            self::specificationsTab(),
                            self::relatedTab(),
                            Tab::make('Description')
                                ->icon('heroicon-o-document-text')
                                ->schema([
                                    RichEditor::make('description')
                                        ->hiddenLabel()
                                        ->toolbarButtons([
                                            ['bold', 'italic', 'underline', 'link'],
                                            ['h2', 'h3'],
                                            ['bulletList', 'orderedList', 'blockquote'],
                                            ['table'],
                                            ['undo', 'redo'],
                                        ]),
                                ]),
                            Tab::make('SEO')
                                ->icon('heroicon-o-magnifying-glass')
                                ->schema([
                                    TextInput::make('meta_title')
                                        ->maxLength(70)
                                        ->helperText('Empty = the template in Settings → SEO & sharing.'),
                                    Textarea::make('meta_description')
                                        ->rows(3)
                                        ->maxLength(160)
                                        ->helperText('Empty = the template in Settings → SEO & sharing.'),
                                ]),
                        ]),

                    Grid::make(1)
                        ->columnSpan(1)
                        ->schema([
                            Section::make('Visibility')
                                ->schema([
                                    ToggleButtons::make('status')
                                        ->hiddenLabel()
                                        ->options(ProductStatus::class)
                                        ->default(ProductStatus::Published)
                                        ->inline()
                                        ->required(),
                                    Grid::make(2)->schema([
                                        Toggle::make('is_featured')->label('Featured'),
                                        Toggle::make('is_new')->label('New arrival'),
                                        Toggle::make('is_best_seller')->label('Best seller'),
                                        Toggle::make('is_hot_deal')->label('Hot deal'),
                                    ]),
                                ]),
                            Section::make('Price')
                                ->schema([
                                    TextInput::make('price')
                                        ->label('Selling price')
                                        ->prefix('BDT')
                                        ->integer()
                                        ->minValue(0)
                                        ->maxValue(99999999)
                                        ->required(fn (Get $get) => self::purchasable($get('stock_status')))
                                        ->helperText('Leave empty to show “Call for price”.')
                                        ->live(onBlur: true),
                                    TextInput::make('regular_price')
                                        ->label('Regular price')
                                        ->prefix('BDT')
                                        ->integer()
                                        ->minValue(0)
                                        ->maxValue(99999999)
                                        ->helperText('Optional. Shown struck-through with the discount %.')
                                        ->rules([fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                            if (filled($value) && filled($get('price')) && (int) $value <= (int) $get('price')) {
                                                $fail('The regular price must be higher than the selling price (or leave it empty).');
                                            }
                                        }]),
                                    TextInput::make('cost_price')
                                        ->label('Cost price')
                                        ->prefix('BDT')
                                        ->integer()
                                        ->minValue(0)
                                        ->helperText('Staff only — never shown on the website.'),
                                    Toggle::make('emi_available')->label('EMI available'),
                                ]),
                            Section::make('Stock')
                                ->columns(2)
                                ->schema([
                                    Select::make('stock_status')
                                        ->label('Availability')
                                        ->options(StockStatus::class)
                                        ->default(StockStatus::InStock)
                                        ->selectablePlaceholder(false)
                                        ->required()
                                        ->live(),
                                    TextInput::make('stock_qty')
                                        ->label('Quantity')
                                        ->integer()
                                        ->default(0)
                                        ->required(),
                                ]),
                        ]),
                ]),
        ]);
    }

    protected static function detailsTab(): Tab
    {
        return Tab::make('Details')
            ->icon('heroicon-o-cube')
            ->columns(2)
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (Set $set, ?string $state, string $operation) => $operation === 'create' ? $set('slug', Slugs::unique((string) $state)) : null)
                    ->columnSpanFull(),
                TextInput::make('slug')
                    ->label('URL')
                    ->prefix(url('/').'/')
                    ->required()
                    ->maxLength(190)
                    ->rules(fn (?Product $record) => [Slugs::rule($record)])
                    ->columnSpanFull(),
                Select::make('category_id')
                    ->label('Category')
                    ->options(fn () => Category::treeOptions())
                    ->searchable()
                    ->required()
                    ->live()
                    ->helperText('Pick the most specific category — its spec fields appear on the Specifications tab.'),
                Select::make('brand_id')
                    ->label('Brand')
                    ->relationship('brand', 'name')
                    ->searchable()
                    ->preload()
                    ->createOptionForm([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(80)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug((string) $state))),
                        TextInput::make('slug')->label('URL')->prefix(url('brand').'/')->required()->alphaDash()->unique('brands', 'slug'),
                    ])
                    ->createOptionModalHeading('New brand'),
                TextInput::make('model')->maxLength(120),
                TextInput::make('sku')
                    ->label('Product code')
                    ->placeholder('Automatic, e.g. TF-01092')
                    ->maxLength(40)
                    ->unique(ignoreRecord: true),
                TextInput::make('warranty')
                    ->maxLength(60)
                    ->datalist(['No warranty', '6 Months', '1 Year', '2 Years', '3 Years', '5 Years', 'Lifetime']),
                Repeater::make('key_features')
                    ->label('Key features')
                    ->helperText('Short bullet points shown next to the price (4–6 work best).')
                    ->simple(TextInput::make('feature')->required()->maxLength(120))
                    ->reorderable()
                    ->maxItems(8)
                    ->defaultItems(0)
                    ->addActionLabel('Add feature')
                    ->columnSpanFull(),
            ]);
    }

    protected static function imagesTab(): Tab
    {
        return Tab::make('Images')
            ->icon('heroicon-o-photo')
            ->schema([
                SpatieMediaLibraryFileUpload::make('images')
                    ->hiddenLabel()
                    ->collection('images')
                    ->multiple()
                    ->reorderable()
                    ->appendFiles()
                    ->image()
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->maxFiles(10)
                    ->maxSize(4096)
                    ->panelLayout('grid')
                    ->helperText('The first image is the main photo — drag to reorder. Square photos on a white background look best (at least 800 × 800 px, max 4 MB). Web-optimised copies are made automatically.'),
                SpatieMediaLibraryFileUpload::make('social_image')
                    ->label('Picture for Facebook, WhatsApp & other shares (optional)')
                    ->collection('social')
                    ->image()
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->maxSize(4096)
                    ->helperText('Shown when someone shares this product’s link. Best size 1200 × 630 px (wide). Leave empty to use the main photo.'),
            ]);
    }

    /** Optional color versions: the customer picks one on the product page; it is saved on the order and invoice. */
    protected static function colorsTab(): Tab
    {
        return Tab::make('Colors')
            ->icon('heroicon-o-swatch')
            ->badge(fn (?Product $record) => $record?->colors()->count() ?: null)
            ->schema([
                Text::make('Only for products sold in several colors — leave empty otherwise. All colors share this product’s price, photos and stock count; mark a color sold out when you run out of it.'),
                Repeater::make('colors')
                    ->hiddenLabel()
                    ->relationship()
                    ->orderColumn('sort')
                    ->reorderable()
                    ->table([
                        TableColumn::make('Color name')->markAsRequired(),
                        TableColumn::make('Swatch')->width('150px'),
                        TableColumn::make('Available')->width('110px'),
                    ])
                    ->schema([
                        TextInput::make('name')->required()->maxLength(40)->placeholder('e.g. Black, Space Grey')
                            ->distinct()
                            ->validationMessages(['distinct' => 'Each color can only be listed once.']),
                        ColorPicker::make('hex')->placeholder('#000000'),
                        Toggle::make('is_available')->default(true)->inline(false),
                    ])
                    ->defaultItems(0)
                    ->maxItems(20)
                    ->addActionLabel('Add a color'),
            ]);
    }

    protected static function specificationsTab(): Tab
    {
        return Tab::make('Specifications')
            ->icon('heroicon-o-list-bullet')
            ->schema([
                // Spec fields come from the chosen category's template and are stored in product_specs.
                Group::make()
                    ->statePath('specs')
                    ->schema(fn (Get $get) => self::specFields($get('category_id')))
                    ->dehydrated(false)
                    ->loadStateFromRelationshipsUsing(fn (Group $component, Product $record) => $component->state(
                        $record->specValues()->pluck('value', 'attribute_id')->mapWithKeys(fn ($value, $id) => ["a{$id}" => $value])->all()
                    ))
                    ->saveRelationshipsUsing(fn (Product $record, ?array $state) => $record->syncSpecs(
                        collect($state ?? [])->mapWithKeys(fn ($value, $key) => [(int) ltrim((string) $key, 'a') => $value])->all()
                    )),

                Repeater::make('extra_specs')
                    ->label('Other specifications')
                    ->helperText('Extra rows for this product only (not filterable). Rows with the same group name as a spec field group appear together.')
                    ->table([
                        TableColumn::make('Group')->width('28%'),
                        TableColumn::make('Label')->width('28%')->markAsRequired(),
                        TableColumn::make('Value')->markAsRequired(),
                    ])
                    ->schema([
                        TextInput::make('group')->default('Other')->maxLength(60)->datalist(['Basic Information', 'Display', 'Memory', 'Storage', 'Connectivity', 'Physical Specification', 'Power', 'Other']),
                        TextInput::make('label')->required()->maxLength(80),
                        TextInput::make('value')->required()->maxLength(250),
                    ])
                    ->reorderable()
                    ->defaultItems(0)
                    ->addActionLabel('Add row'),
            ]);
    }

    protected static function relatedTab(): Tab
    {
        // Search and labels query all products directly: Filament's relationship lookups keep the pivot's
        // "type" constraint and would only offer products that are already linked.
        $label = fn (Product $product) => "{$product->name} ({$product->sku})";
        $picker = fn (string $relationship) => Select::make($relationship)
            ->relationship($relationship, 'name')
            ->multiple()
            ->searchable()
            ->getSearchResultsUsing(fn (string $search, ?Product $record) => Product::query()
                ->when($record?->exists, fn ($q) => $q->whereKeyNot($record->getKey()))
                ->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%")->orWhere('model', 'like', "%{$search}%"))
                ->orderBy('name')
                ->limit(50)
                ->get()
                ->mapWithKeys(fn (Product $product) => [$product->getKey() => $label($product)])
                ->all())
            ->getOptionLabelsUsing(fn (array $values) => Product::query()->whereKey($values)->get()
                ->mapWithKeys(fn (Product $product) => [$product->getKey() => $label($product)])
                ->all())
            ->maxItems(12)
            ->placeholder('Search by name, code or model');

        return Tab::make('Related')
            ->icon('heroicon-o-link')
            ->schema([
                $picker('relatedProducts')
                    ->label('Related products')
                    ->helperText('Alternatives shown first under “You may also like”. Leave empty and similar products from the same category are shown automatically.'),
                $picker('accessories')
                    ->label('Accessories')
                    ->helperText('Add-ons shown as “Accessories for this product” — e.g. a mouse pad for a mouse, a UPS for a desktop, a hard disk for a DVR.'),
            ]);
    }

    /**
     * Inputs for the category's spec template, grouped like the storefront spec table. Each field suggests
     * values already used by other products, which keeps filter values consistent ("DDR4", not "ddr-4").
     *
     * @return array<int, Component>
     */
    public static function specFields(mixed $categoryId): array
    {
        $template = filled($categoryId) ? (CatalogCache::data()['templates'][(int) $categoryId] ?? []) : [];

        if (! $template) {
            return [Text::make(filled($categoryId)
                ? 'This category has no spec fields yet. Add them in Catalog → Categories, or use “Other specifications” below.'
                : 'Choose a category on the Details tab to see its spec fields.')->color('gray')];
        }

        $ids = array_column($template, 'attribute_id');
        $help = Attribute::whereKey($ids)->pluck('help', 'id');
        $suggestions = ProductSpec::query()->whereIn('attribute_id', $ids)->select('attribute_id', 'value')->distinct()->orderBy('value')->get()->groupBy('attribute_id');

        return collect($template)
            ->groupBy('group')
            ->map(fn ($rows, string $group) => Fieldset::make($group)
                ->columns(2)
                ->schema($rows->map(fn (array $row) => TextInput::make('a'.$row['attribute_id'])
                    ->label($row['name'])
                    ->suffix($row['unit'])
                    ->maxLength(250)
                    ->helperText($help[$row['attribute_id']] ?? null)
                    ->hint($row['filterable'] ? 'Filter' : null)
                    ->hintIcon($row['filterable'] ? 'heroicon-m-funnel' : null)
                    ->datalist(collect($suggestions->get($row['attribute_id'], []))->pluck('value')->take(60)->all()))
                    ->all()))
            ->values()
            ->all();
    }

    protected static function purchasable(mixed $state): bool
    {
        $status = $state instanceof StockStatus ? $state : StockStatus::tryFrom((string) $state);

        return (bool) $status?->isPurchasable();
    }
}
