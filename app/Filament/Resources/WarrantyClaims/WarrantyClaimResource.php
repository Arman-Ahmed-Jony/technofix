<?php

namespace App\Filament\Resources\WarrantyClaims;

use App\Filament\Resources\WarrantyClaims\Pages\CreateWarrantyClaim;
use App\Filament\Resources\WarrantyClaims\Pages\EditWarrantyClaim;
use App\Filament\Resources\WarrantyClaims\Pages\ListWarrantyClaims;
use App\Filament\Resources\WarrantyClaims\Schemas\WarrantyClaimForm;
use App\Filament\Resources\WarrantyClaims\Tables\WarrantyClaimsTable;
use App\Models\WarrantyClaim;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/** The shop's warranty list (was "Warranty List (Do Not Delete).xlsx"): claims, status for customers, Excel in/out. */
class WarrantyClaimResource extends Resource
{
    protected static ?string $model = WarrantyClaim::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static string|UnitEnum|null $navigationGroup = 'Sales';

    protected static ?string $navigationLabel = 'Warranty claims';

    protected static ?string $modelLabel = 'warranty claim';

    protected static ?int $navigationSort = 6;

    protected static ?string $slug = 'warranty-claims';

    protected static ?string $recordTitleAttribute = 'product';

    public static function getNavigationBadge(): ?string
    {
        $ready = WarrantyClaim::query()->where('status', 'ready')->where('is_internal', false)->count();

        return $ready ? (string) $ready : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Customer products ready to collect';
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['product', 'serial', 'new_serial', 'customer_name', 'customer_phone'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        /** @var WarrantyClaim $record */
        return ['Claim' => '#'.$record->number, 'Customer' => $record->customer_name, 'Status' => $record->status->getLabel()];
    }

    public static function form(Schema $schema): Schema
    {
        return WarrantyClaimForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WarrantyClaimsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWarrantyClaims::route('/'),
            'create' => CreateWarrantyClaim::route('/create'),
            'edit' => EditWarrantyClaim::route('/{record}/edit'),
        ];
    }
}
