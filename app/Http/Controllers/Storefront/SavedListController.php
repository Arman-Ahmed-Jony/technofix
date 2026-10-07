<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Support\Catalog;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Compare and wishlist pages. The lists live in the visitor's browser; the page URL carries the ids
 * (?ids=3,7,12) so it can be rendered on the server and shared.
 */
class SavedListController extends Controller
{
    public function __construct(protected Catalog $catalog) {}

    public function compare(Request $request): View
    {
        $products = $this->products($request, 4);
        $specs = $products->mapWithKeys(fn (Product $p) => [$p->id => $p->specMap()]);

        return view('storefront.compare', [
            'products' => $products,
            'specs' => $specs,
            // Spec rows: every spec field any of the products has, in first-seen order.
            'specKeys' => $specs->flatMap(fn (array $map) => array_keys($map))->unique()->values(),
            'mixedCategories' => $products->pluck('category_id')->unique()->count() > 1,
            'hasIds' => $request->has('ids'),
        ]);
    }

    public function wishlist(Request $request): View
    {
        return view('storefront.wishlist', [
            'products' => $this->products($request, 50),
            'hasIds' => $request->has('ids'),
        ]);
    }

    /** "Recently viewed" row on product pages: the ids come from the browser (most recent first). */
    public function recent(Request $request): Response
    {
        $products = $this->products($request, 12);

        return $products->isEmpty()
            ? response()->noContent()
            : response()->view('storefront.partials.recently-viewed', ['products' => $products]);
    }

    /**
     * @return Collection<int, Product>
     */
    protected function products(Request $request, int $max): Collection
    {
        $ids = collect(explode(',', (string) $request->query('ids', '')))
            ->map(fn (string $id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->take($max)
            ->values()
            ->all();

        return $this->catalog->productsByIds($ids);
    }
}
