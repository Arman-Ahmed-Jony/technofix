<?php

namespace App\Filament\Resources\Categories;

use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Filament\Resources\Categories\Pages\EditCategory;
use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Filament\Resources\Categories\RelationManagers\BrandPagesRelationManager;
use App\Filament\Resources\Categories\Schemas\CategoryForm;
use App\Filament\Resources\Categories\Tables\CategoriesTable;
use App\Models\Category;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static string|UnitEnum|null $navigationGroup = 'Catalog';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return CategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CategoriesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [BrandPagesRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCategories::route('/'),
            'create' => CreateCategory::route('/create'),
            'edit' => EditCategory::route('/{record}/edit'),
        ];
    }

    /**
     * Delete action that refuses while the category still has sub-categories or products.
     */
    public static function deleteAction(): DeleteAction
    {
        return DeleteAction::make()->before(function (DeleteAction $action, Category $record) {
            $children = $record->children()->count();
            $products = $record->products()->withTrashed()->count();

            if ($children || $products) {
                Notification::make()
                    ->danger()
                    ->title("“{$record->name}” can’t be deleted yet")
                    ->body(collect([
                        $children ? "{$children} sub-categor".($children === 1 ? 'y' : 'ies') : null,
                        $products ? "{$products} product".($products === 1 ? '' : 's').' (including trashed)' : null,
                    ])->filter()->join(' and ').' still belong to it. Move or delete them first — or just hide the category.')
                    ->persistent()
                    ->send();

                $action->cancel();
            }
        });
    }
}
