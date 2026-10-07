<?php

namespace App\Filament\Resources\Roles;

use App\Enums\StaffArea;
use App\Filament\Resources\Roles\Pages\CreateRole;
use App\Filament\Resources\Roles\Pages\EditRole;
use App\Filament\Resources\Roles\Pages\ListRoles;
use App\Models\Role;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Staff roles: which admin areas (StaffArea) their members can open. The Owner role has full access and is fixed.
 */
class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 21;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    TextInput::make('name')->required()->maxLength(60)->unique(ignoreRecord: true)->placeholder('e.g. Store manager'),
                    TextInput::make('description')->label('Short description')->maxLength(255)->placeholder('What this person does'),
                ]),
            Section::make('What they can open')
                ->columnSpanFull()
                ->description('Everyone sees the dashboard and their own profile. Parts that aren’t ticked are hidden from the menu and can’t be opened by typing the address.')
                ->schema([
                    CheckboxList::make('areas')
                        ->hiddenLabel()
                        ->options(StaffArea::options())
                        ->descriptions(collect(StaffArea::cases())->mapWithKeys(fn (StaffArea $area) => [$area->value => $area->description()])->all())
                        ->columns(2)
                        ->gridDirection('row')
                        ->bulkToggleable()
                        ->required()
                        ->validationMessages(['required' => 'Tick at least one part of the admin.']),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount('users'))
            ->defaultSort('sort')
            ->reorderable('sort')
            ->columns([
                TextColumn::make('name')->weight('medium')->description(fn (Role $record) => $record->description),
                TextColumn::make('areas')
                    ->label('Can open')
                    ->state(fn (Role $record) => $record->areaLabels())
                    ->badge()
                    ->color(fn (Role $record) => $record->is_owner ? 'warning' : 'gray')
                    ->wrap(),
                TextColumn::make('users_count')->label('Staff')->numeric()->alignEnd(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRoles::route('/'),
            'create' => CreateRole::route('/create'),
            'edit' => EditRole::route('/{record}/edit'),
        ];
    }
}
