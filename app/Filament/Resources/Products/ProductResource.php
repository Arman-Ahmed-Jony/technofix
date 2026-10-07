<?php

namespace App\Filament\Resources\Products;

use App\Enums\ProductStatus;
use App\Filament\Resources\Products\Pages\ArrangeFeatured;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ImportProducts;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Resources\Products\RelationManagers\StockMovementsRelationManager;
use App\Filament\Resources\Products\Schemas\ProductForm;
use App\Filament\Resources\Products\Tables\ProductsTable;
use App\Models\Product;
use App\Support\Slugs;
use BackedEnum;
use Filament\Actions\ReplicateAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cube';

    protected static string|UnitEnum|null $navigationGroup = 'Catalog';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return ProductForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductsTable::configure($table);
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    /** @return list<string> */
    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'sku', 'model'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return ['Code' => $record->sku, 'Price' => $record->price ? '৳'.number_format($record->price) : '—'];
    }

    /**
     * "Duplicate": copies the product with its specs (not its photos) as a draft, then opens it.
     */
    public static function duplicateAction(): ReplicateAction
    {
        return ReplicateAction::make()
            ->label('Duplicate')
            ->modalDescription('A draft copy is created with the same details, specifications, related products and accessories (photos are not copied).')
            ->excludeAttributes(['slug', 'sku', 'views', 'sold_count', 'deleted_at'])
            ->beforeReplicaSaved(function (Product $replica) {
                $replica->name = $replica->name.' (copy)';
                $replica->slug = Slugs::unique($replica->name);
                $replica->status = ProductStatus::Draft;
            })
            ->after(function (Product $replica, Product $record) {
                $replica->syncSpecs($record->specValues()->pluck('value', 'attribute_id')->all());
                $replica->relatedProducts()->attach($record->relatedProducts()->pluck('products.id'));
                $replica->accessories()->attach($record->accessories()->pluck('products.id'));
            })
            ->successRedirectUrl(fn (Product $replica) => static::getUrl('edit', ['record' => $replica]));
    }

    public static function getRelations(): array
    {
        return [StockMovementsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'import' => ImportProducts::route('/import'),
            'featured' => ArrangeFeatured::route('/featured'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }
}
