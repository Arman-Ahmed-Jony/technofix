<?php

namespace App\Filament\Resources\Brands;

use App\Filament\Resources\Brands\Pages\CreateBrand;
use App\Filament\Resources\Brands\Pages\EditBrand;
use App\Filament\Resources\Brands\Pages\ListBrands;
use App\Filament\Resources\Brands\RelationManagers\CategoryPagesRelationManager;
use App\Models\Brand;
use App\Support\ZoneThemes;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use UnitEnum;

class BrandResource extends Resource
{
    protected static ?string $model = Brand::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    protected static string|UnitEnum|null $navigationGroup = 'Catalog';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(['default' => 1, 'lg' => 3])
                ->columnSpanFull()
                ->schema([
                    Section::make()
                        ->columnSpan(['lg' => 2])
                        ->columns(2)
                        ->schema([
                            TextInput::make('name')
                                ->required()
                                ->maxLength(80)
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn (Set $set, ?string $state, string $operation) => $operation === 'create' ? $set('slug', Str::slug((string) $state)) : null),
                            TextInput::make('slug')
                                ->label('URL')
                                ->prefix(url('brand').'/')
                                ->required()
                                ->maxLength(100)
                                ->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
                                ->unique(ignoreRecord: true),
                            SpatieMediaLibraryFileUpload::make('logo')
                                ->collection('logo')
                                ->image()
                                ->maxSize(2048)
                                ->helperText('PNG with a transparent or white background works best. Also shown in a white box on the Exclusive Zone card.')
                                ->columnSpanFull(),
                            RichEditor::make('description')
                                ->label('About the brand')
                                ->helperText('Shown at the bottom of the brand page.')
                                ->toolbarButtons([['bold', 'italic', 'link'], ['bulletList'], ['undo', 'redo']])
                                ->columnSpanFull(),
                        ]),
                    Grid::make(1)
                        ->columnSpan(1)
                        ->schema([
                            Section::make('Visibility')
                                ->schema([
                                    Toggle::make('is_active')->label('Show on website')->default(true),
                                    Toggle::make('is_featured')
                                        ->label('Show in home “Official brands” strip'),
                                    TextInput::make('sort')
                                        ->label('Position')
                                        ->helperText('Order in the brands strip and the Exclusive Zones.')
                                        ->numeric()
                                        ->minValue(0)
                                        ->default(100),
                                ]),
                            Section::make('Exclusive Zone')
                                ->description('A highlighted brand store with a coloured card next to the home banner (the first three zones are shown).')
                                ->schema([
                                    Toggle::make('is_zone')->label('Exclusive Zone brand')->live(),
                                    TextInput::make('zone_tagline')
                                        ->label('Tagline')
                                        ->placeholder('Chargers, hubs & cables')
                                        ->maxLength(40)
                                        ->required(fn (Get $get) => $get('is_zone'))
                                        ->visible(fn (Get $get) => $get('is_zone')),
                                    Select::make('zone_theme')
                                        ->label('Card colour')
                                        ->options(ZoneThemes::options())
                                        ->helperText('“White (light card)” = a white card with a soft shadow and dark text.')
                                        ->default('navy')
                                        ->selectablePlaceholder(false)
                                        ->visible(fn (Get $get) => $get('is_zone')),
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

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount('products'))
            ->paginated([50, 100, 'all'])
            ->defaultPaginationPageOption(50)
            ->columns([
                SpatieMediaLibraryImageColumn::make('logo')
                    ->collection('logo')
                    ->conversion('logo')
                    ->height(28)
                    ->width(56)
                    ->extraImgAttributes(['class' => 'object-contain bg-white rounded'])
                    ->toggleable(),
                TextColumn::make('name')
                    ->description(fn (Brand $record) => $record->is_zone ? '★ Exclusive Zone · '.$record->zone_tagline : null)
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                TextColumn::make('products_count')->label('Products')->numeric()->sortable(),
                TextColumn::make('sort')->label('Position')->numeric()->sortable()->toggleable(isToggledHiddenByDefault: true),
                ToggleColumn::make('is_featured')->label('Home strip'),
                ToggleColumn::make('is_active')->label('On site'),
            ])
            ->filters([
                TernaryFilter::make('is_featured')->label('In home strip'),
                TernaryFilter::make('is_zone')->label('Exclusive Zone'),
                TernaryFilter::make('is_active')->label('On site'),
            ])
            ->recordActions([
                Action::make('open')
                    ->label('View')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->url(fn (Brand $record) => $record->url(), shouldOpenInNewTab: true)
                    ->visible(fn (Brand $record) => $record->is_active),
                EditAction::make(),
                ActionGroup::make([static::deleteAction()]),
            ]);
    }

    /**
     * Delete action that refuses while products still use the brand.
     */
    public static function deleteAction(): DeleteAction
    {
        return DeleteAction::make()->before(function (DeleteAction $action, Brand $record) {
            if ($count = $record->products()->withTrashed()->count()) {
                Notification::make()
                    ->danger()
                    ->title("“{$record->name}” can’t be deleted yet")
                    ->body("{$count} product".($count === 1 ? ' uses' : 's use').' this brand. Change their brand first — or just hide the brand.')
                    ->persistent()
                    ->send();

                $action->cancel();
            }
        });
    }

    public static function getRelations(): array
    {
        return [CategoryPagesRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBrands::route('/'),
            'create' => CreateBrand::route('/create'),
            'edit' => EditBrand::route('/{record}/edit'),
        ];
    }
}
