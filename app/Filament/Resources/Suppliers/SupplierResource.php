<?php

namespace App\Filament\Resources\Suppliers;

use App\Filament\Resources\Suppliers\Pages\ManageSuppliers;
use App\Models\Supplier;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/** Who TechnoFix buys stock from; picked on each stock-in. */
class SupplierResource extends Resource
{
    protected static ?string $model = Supplier::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-truck';

    protected static string|UnitEnum|null $navigationGroup = 'Inventory';

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components(self::fields());
    }

    /**
     * @return array<int, mixed>
     */
    public static function fields(): array
    {
        return [
            TextInput::make('name')->label('Company / shop')->required()->maxLength(120),
            TextInput::make('contact_person')->maxLength(120),
            TextInput::make('phone')->tel()->maxLength(30),
            TextInput::make('email')->email()->maxLength(255),
            TextInput::make('address')->maxLength(255)->columnSpanFull(),
            Textarea::make('notes')->rows(2)->columnSpanFull()->placeholder('Payment terms, what they supply…'),
            Toggle::make('is_active')->label('Active')->default(true),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->withCount(['receipts' => fn ($q) => $q->whereNull('cancelled_at')])
                ->withSum(['receipts' => fn ($q) => $q->whereNull('cancelled_at')], 'total_cost'))
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')->searchable()->weight('medium')->description(fn (Supplier $record) => $record->contact_person),
                TextColumn::make('phone')->searchable()->placeholder('—')->url(fn (Supplier $record) => $record->phone ? 'tel:'.$record->phone : null),
                TextColumn::make('receipts_count')->label('Stock-ins')->numeric()->alignEnd(),
                TextColumn::make('receipts_sum_total_cost')->label('Bought (BDT)')->numeric()->alignEnd()->placeholder('0'),
                ToggleColumn::make('is_active')->label('Active'),
            ])
            ->filters([TernaryFilter::make('is_active')->label('Active')])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageSuppliers::route('/')];
    }
}
