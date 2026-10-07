<?php

namespace App\Filament\Resources\Brands\RelationManagers;

use App\Filament\Schemas\CategoryBrandPageSchema;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

/** "Dell Laptop", "Dell Monitor"… pages of this brand. */
class CategoryPagesRelationManager extends RelationManager
{
    protected static string $relationship = 'categoryPages';

    protected static ?string $title = 'Category pages';

    public function form(Schema $schema): Schema
    {
        return CategoryBrandPageSchema::form($schema, 'category');
    }

    public function table(Table $table): Table
    {
        return CategoryBrandPageSchema::table($table, 'category');
    }
}
