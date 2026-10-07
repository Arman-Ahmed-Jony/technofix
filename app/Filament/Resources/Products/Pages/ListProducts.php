<?php

namespace App\Filament\Resources\Products\Pages;

use App\Enums\ProductStatus;
use App\Enums\StockStatus;
use App\Filament\Resources\Products\ProductResource;
use App\Models\Category;
use App\Models\Product;
use App\Support\ProductSheet\Exporter;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('arrangeFeatured')
                ->label('Arrange featured')
                ->icon('heroicon-o-arrows-up-down')
                ->color('gray')
                ->url(ProductResource::getUrl('featured')),
            ActionGroup::make([
                Action::make('import')
                    ->label('Import from Excel')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->url(ProductResource::getUrl('import')),
                Action::make('export')
                    ->label('Export to Excel')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->modalHeading('Export products')
                    ->modalDescription('Downloads an Excel file you can edit and import back — rows are matched by Product code.')
                    ->modalSubmitActionLabel('Download')
                    ->schema([
                        Select::make('category')
                            ->label('Category')
                            ->options(fn () => Category::treeOptions())
                            ->placeholder('All categories')
                            ->searchable()
                            ->helperText('Includes its sub-categories. The file gets a column for each of their spec fields.'),
                        Toggle::make('with_products')
                            ->label('Include products')
                            ->helperText('Turn off to get an empty template for adding new products.')
                            ->default(true),
                    ])
                    ->action(function (array $data) {
                        $categoryIds = filled($data['category'] ?? null) ? Category::withDescendantIds((int) $data['category']) : null;
                        $name = filled($data['category'] ?? null) ? Str::slug(Category::find($data['category'])?->name ?? 'category') : 'all';
                        $path = tempnam(sys_get_temp_dir(), 'tf').'.xlsx';

                        (new Exporter($categoryIds, (bool) ($data['with_products'] ?? true)))->writeTo($path);

                        return response()
                            ->download($path, "technofix-products-{$name}-".now()->format('Y-m-d').'.xlsx')
                            ->deleteFileAfterSend();
                    }),
            ])
                ->label('Import / Export')
                ->icon('heroicon-o-table-cells')
                ->button()
                ->color('gray'),
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All'),
            'published' => Tab::make('Published')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', ProductStatus::Published)),
            'drafts' => Tab::make('Drafts')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', ProductStatus::Draft))
                ->badge(fn () => Product::where('status', ProductStatus::Draft)->count() ?: null)
                ->badgeColor('gray'),
            'out_of_stock' => Tab::make('Out of stock')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('stock_status', StockStatus::OutOfStock))
                ->badge(fn () => Product::where('stock_status', StockStatus::OutOfStock)->count() ?: null)
                ->badgeColor('danger'),
        ];
    }
}
