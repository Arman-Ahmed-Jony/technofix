<?php

namespace App\Filament\Resources\Redirects;

use App\Filament\Resources\Redirects\Pages\CreateRedirect;
use App\Filament\Resources\Redirects\Pages\EditRedirect;
use App\Filament\Resources\Redirects\Pages\ListRedirects;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use App\Models\Redirect;
use App\Models\Service;
use App\Support\CatalogCache;
use BackedEnum;
use Closure;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontFamily;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

/**
 * Redirects for addresses that no longer exist. Renamed products, categories, brands, pages and services get one
 * automatically (Concerns\RedirectsWhenMoved); staff add others here or from Broken links.
 */
class RedirectResource extends Resource
{
    protected static ?string $model = Redirect::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-uturn-right';

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?int $navigationSort = 90;

    protected static ?string $recordTitleAttribute = 'from_path';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columnSpanFull()
                ->description('A redirect only applies when nothing on the site answers the old address — a live page is always shown.')
                ->schema([
                    self::fromField(),
                    ...self::targetFields(),
                    TextInput::make('note')->label('Note for staff')->maxLength(255)->placeholder('e.g. Old website link'),
                    Toggle::make('is_active')->label('On')->default(true),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('from_path')
                    ->label('Old address')
                    ->fontFamily(FontFamily::Mono)
                    ->searchable()
                    ->wrap()
                    ->description(fn (Redirect $record) => $record->note),
                TextColumn::make('to_url')
                    ->label('Goes to')
                    ->fontFamily(FontFamily::Mono)
                    ->searchable()
                    ->wrap()
                    ->url(fn (Redirect $record) => str_contains($record->to_url, '*') ? null : url($record->to_url), shouldOpenInNewTab: true),
                TextColumn::make('status')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (int $state) => $state === 301 ? 'Permanent' : 'Temporary')
                    ->color(fn (int $state) => $state === 301 ? 'success' : 'warning'),
                TextColumn::make('hits')->label('Used')->numeric()->sortable()->alignEnd(),
                TextColumn::make('last_hit_at')->label('Last used')->since()->placeholder('Never')->sortable(),
                ToggleColumn::make('is_active')->label('On'),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('On'),
                SelectFilter::make('status')->label('Type')->options(Redirect::STATUSES),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function fromField(): TextInput
    {
        return TextInput::make('from_path')
            ->label('Old address')
            ->required()
            ->maxLength(255)
            ->placeholder('/old-product-name or https://old-site.com/product/old-product/')
            ->helperText('Paste the whole old link or just the part after the domain. End it with * to catch everything under it, e.g. /product-category/laptops/*')
            ->extraInputAttributes(['class' => 'font-mono'])
            ->dehydrateStateUsing(fn (?string $state) => Redirect::normalize($state))
            ->rules([fn (?Redirect $record): Closure => function (string $attribute, $value, Closure $fail) use ($record) {
                $path = Redirect::normalize($value);

                if (in_array($path, ['/', '/*'], true)) {
                    $fail('The home page can’t be redirected.');
                } elseif (Redirect::where('from_path', $path)->whereKeyNot($record?->getKey())->exists()) {
                    $fail('There is already a redirect for this address.');
                }
            }]);
    }

    /**
     * "Find a page" search + the new address + permanent/temporary.
     *
     * @return array<int, mixed>
     */
    public static function targetFields(): array
    {
        return [
            Select::make('find')
                ->label('Find the new page')
                ->placeholder('Search products, categories, brands, pages, services…')
                ->searchable()
                ->getSearchResultsUsing(fn (string $search) => self::searchTargets($search))
                ->getOptionLabelUsing(fn (?string $value) => $value)
                ->dehydrated(false)
                ->live()
                ->afterStateUpdated(fn (Set $set, ?string $state) => $state ? $set('to_url', $state) : null),
            TextInput::make('to_url')
                ->label('New address')
                ->required()
                ->maxLength(500)
                ->placeholder('/laptop')
                ->helperText('A page on this site (/laptop) or a full link. If the old address ends with *, a * here is replaced by the rest of the old address.')
                ->extraInputAttributes(['class' => 'font-mono'])
                ->rules(['regex:~^(/|https?://)~i', fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                    if (filled($get('from_path')) && Redirect::normalize($value) === Redirect::normalize($get('from_path'))) {
                        $fail('The new address must be different from the old one.');
                    }
                }])
                ->validationMessages(['regex' => 'Start with / for a page on this site, or with https:// for another website.']),
            Radio::make('status')
                ->label('Type')
                ->options([301 => 'Permanent — the page has moved for good (search engines pass its ranking on)', 302 => 'Temporary — it will come back'])
                ->default(301)
                ->required(),
        ];
    }

    /**
     * @return array<string, string> path => "Product · Name"
     */
    public static function searchTargets(string $search): array
    {
        $like = '%'.$search.'%';

        return [
            ...Product::query()->published()->where('name', 'like', $like)->orderBy('name')->limit(12)->pluck('name', 'slug')
                ->mapWithKeys(fn (string $name, string $slug) => [Product::pathForSlug($slug) => 'Product · '.$name])->all(),
            ...collect(CatalogCache::data()['categories'])->filter(fn (array $c) => Str::contains($c['name'], $search, ignoreCase: true))->take(8)
                ->mapWithKeys(fn (array $c) => [Category::pathForSlug($c['slug']) => 'Category · '.$c['name']])->all(),
            ...Brand::active()->where('name', 'like', $like)->orderBy('name')->limit(5)->pluck('name', 'slug')
                ->mapWithKeys(fn (string $name, string $slug) => [Brand::pathForSlug($slug) => 'Brand · '.$name])->all(),
            ...Page::published()->where('title', 'like', $like)->limit(5)->pluck('title', 'slug')
                ->mapWithKeys(fn (string $title, string $slug) => [Page::pathForSlug($slug) => 'Page · '.$title])->all(),
            ...Service::active()->where('name', 'like', $like)->limit(5)->pluck('name', 'slug')
                ->mapWithKeys(fn (string $name, string $slug) => [Service::pathForSlug($slug) => 'Service · '.$name])->all(),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRedirects::route('/'),
            'create' => CreateRedirect::route('/create'),
            'edit' => EditRedirect::route('/{record}/edit'),
        ];
    }
}
