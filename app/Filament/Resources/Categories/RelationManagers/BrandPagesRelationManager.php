<?php

namespace App\Filament\Resources\Categories\RelationManagers;

use App\Filament\Schemas\CategoryBrandPageSchema;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

/** "Dell Laptop", "HP Laptop"… pages of this category. */
class BrandPagesRelationManager extends RelationManager
{
    protected static string $relationship = 'brandPages';

    protected static ?string $title = 'Brand pages';

    public function form(Schema $schema): Schema
    {
        return CategoryBrandPageSchema::form($schema, 'brand');
    }

    public function table(Table $table): Table
    {
        return CategoryBrandPageSchema::table($table, 'brand');
    }
}
