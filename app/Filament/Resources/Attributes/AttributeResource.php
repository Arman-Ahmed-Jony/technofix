<?php

namespace App\Filament\Resources\Attributes;

use App\Filament\Resources\Attributes\Pages\ManageAttributes;
use App\Models\Attribute;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use UnitEnum;

/**
 * Specification fields ("Chipset", "Capacity", "Wattage"…), shared by categories' spec templates.
 */
class AttributeResource extends Resource
{
    protected static ?string $model = Attribute::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static string|UnitEnum|null $navigationGroup = 'Catalog';

    protected static ?string $navigationLabel = 'Spec fields';

    protected static ?string $slug = 'spec-fields';

    protected static ?string $modelLabel = 'spec field';

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'name';

    /**
     * Also used by the "create field" modal on the category form.
     *
     * @return array<int, Component>
     */
    public static function formFields(): array
    {
        return [
            TextInput::make('name')
                ->required()
                ->maxLength(60)
                ->live(onBlur: true)
                ->afterStateUpdated(fn (Set $set, ?string $state, ?Attribute $record) => $record ? null : $set('code', Str::slug((string) $state, '_'))),
            TextInput::make('unit')
                ->maxLength(20)
                ->helperText('Optional, e.g. GB, W, MHz. Numbers are shown with it (“650 W”).'),
            TextInput::make('code')
                ->required()
                ->maxLength(60)
                ->regex('/^[a-z0-9_]+$/')
                ->unique(ignoreRecord: true)
                ->disabled(fn (?Attribute $record) => (bool) $record?->is_system)
                ->helperText('Internal key (lowercase, underscores).'),
            TextInput::make('help')
                ->label('Hint for staff')
                ->maxLength(255)
                ->helperText('Shown under the field on the product form.'),
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components(self::formFields())->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount(['templates', 'values']))
            ->paginated([50, 100, 'all'])
            ->defaultPaginationPageOption(50)
            ->columns([
                TextColumn::make('name')
                    ->description(fn (Attribute $record) => $record->help ? Str::limit($record->help, 70) : null)
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                TextColumn::make('unit')->placeholder('—'),
                TextColumn::make('code')->fontFamily('mono')->color('gray')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('templates_count')->label('Categories')->numeric()->sortable(),
                TextColumn::make('values_count')->label('Products')->numeric()->sortable(),
                IconColumn::make('is_system')
                    ->label('PC Builder')
                    ->icon(fn (bool $state) => $state ? 'heroicon-s-star' : null)
                    ->color('warning')
                    ->tooltip(fn (Attribute $record) => $record->is_system ? 'Used for PC Builder compatibility checks — can’t be deleted.' : null),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->hidden(fn (Attribute $record) => $record->is_system)
                    ->modalDescription(fn (Attribute $record) => "This removes the field from {$record->templates_count} categories and deletes its value on {$record->values_count} products. This can’t be undone."),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageAttributes::route('/'),
        ];
    }
}
