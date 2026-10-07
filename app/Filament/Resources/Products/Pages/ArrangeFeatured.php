<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Models\HomeSection;
use App\Models\Product;
use App\Support\ActivityLog;
use App\Support\PageCache;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\DB;

/**
 * Products → Arrange featured: the order of the home page's "Featured" tab (products.featured_sort, 1 = first).
 * Drag rows after "Reorder records"; Filament saves with a query, so the page cache is cleared here.
 */
class ArrangeFeatured extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected static ?string $title = 'Arrange featured products';

    protected static ?string $breadcrumb = 'Arrange featured';

    public function getSubheading(): string|Htmlable|null
    {
        $shown = (int) (HomeSection::query()->where('type', 'product_tabs')->first()?->setting('limit', 10) ?? 10);

        return "Drag to set the order of the home page’s Featured tab — it shows the first {$shown}. Click “Reorder records”, drag, then “Finish reordering”.";
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Product::query()->where('is_featured', true)->with('media'))
            ->reorderable('featured_sort')
            ->defaultSort('featured_sort')
            ->paginated(false)
            ->afterReordering(fn () => $this->ordered())
            ->reorderRecordsTriggerAction(fn (Action $action, bool $isReordering) => $action->button()
                ->label($isReordering ? 'Finish reordering' : 'Reorder records')
                ->icon($isReordering ? 'heroicon-o-check' : 'heroicon-o-arrows-up-down')
                ->color($isReordering ? 'primary' : 'gray'))
            ->filters([])
            ->toolbarActions([])
            ->columns([
                TextColumn::make('position')->label('#')->rowIndex()->color('gray')->width('3rem'),
                SpatieMediaLibraryImageColumn::make('images')
                    ->label('')
                    ->collection('images')
                    ->conversion('thumb')
                    ->limit(1)
                    ->square()
                    ->size(44)
                    ->extraImgAttributes(['class' => 'bg-white object-contain rounded-lg']),
                TextColumn::make('name')
                    ->weight('medium')
                    ->wrap()
                    ->lineClamp(2)
                    ->description(fn (Product $record) => collect([$record->sku, $record->brand_name, $record->category_name])->filter()->join(' · ')),
                TextColumn::make('sale_price')->label('Price')
                    ->state(fn (Product $record) => $record->sale_price ? 'BDT '.number_format($record->sale_price) : 'Call for price'),
                TextColumn::make('stock_status')->label('Availability')->badge(),
                TextColumn::make('status')->badge(),
            ])
            ->recordActions([
                Action::make('top')
                    ->label('Move to top')
                    ->icon('heroicon-o-arrow-up')
                    ->color('gray')
                    ->hidden(fn (Product $record) => $record->id === ($this->orderedIds()[0] ?? null))
                    ->action(fn (Product $record) => $this->saveOrder(collect($this->orderedIds())->reject(fn ($id) => $id === $record->id)->prepend($record->id)->all())),
                Action::make('remove')
                    ->label('Remove')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Remove from featured?')
                    ->modalDescription('The product stays in the shop; it just leaves the Featured tab.')
                    ->action(function (Product $record) {
                        $record->update(['is_featured' => false]);
                        Notification::make()->success()->title('Removed from featured')->send();
                    }),
            ])
            ->emptyStateIcon('heroicon-o-star')
            ->emptyStateHeading('No featured products yet')
            ->emptyStateDescription('Use “Add products”, or switch on “Featured” on a product.');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('add')
                ->label('Add products')
                ->icon('heroicon-o-plus')
                ->modalHeading('Add featured products')
                ->modalDescription('They are added at the end of the list; drag them into place afterwards.')
                ->modalSubmitActionLabel('Add')
                ->schema([
                    Select::make('products')
                        ->label('Products')
                        ->multiple()
                        ->required()
                        ->searchable()
                        ->getSearchResultsUsing(fn (string $search) => Product::query()->where('is_featured', false)
                            ->where(fn ($query) => $query->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%"))
                            ->orderBy('name')->limit(50)->pluck('name', 'id')->all())
                        ->getOptionLabelsUsing(fn (array $values) => Product::query()->whereKey($values)->pluck('name', 'id')->all()),
                ])
                ->action(function (array $data) {
                    $products = Product::query()->whereKey($data['products'])->where('is_featured', false)->orderBy('name')->get();
                    $products->each(fn (Product $product) => $product->update(['is_featured' => true])); // goes to the end
                    Notification::make()->success()->title($products->count().' added to featured')->send();
                }),
            Action::make('products')
                ->label('All products')
                ->color('gray')
                ->url(ProductResource::getUrl()),
        ];
    }

    /** @var list<int>|null per request */
    protected ?array $order = null;

    /** @return list<int> featured product ids, first to last */
    protected function orderedIds(): array
    {
        return $this->order ??= Product::query()->where('is_featured', true)
            ->orderByRaw('featured_sort is null')->orderBy('featured_sort')->orderBy('id')->pluck('id')->all();
    }

    /** @param  list<int>  $ids */
    protected function saveOrder(array $ids): void
    {
        DB::transaction(fn () => collect($ids)->each(fn ($id, $i) => Product::query()->whereKey($id)->update(['featured_sort' => $i + 1])));
        $this->ordered();
    }

    protected function ordered(): void
    {
        $this->order = null;
        PageCache::invalidate();
        ActivityLog::record('reordered', label: 'Featured products', type: Product::class);
    }
}
