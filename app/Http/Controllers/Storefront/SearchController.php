<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Support\Catalog;
use App\Support\Money;
use App\Support\ProductListing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Fluent;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function __construct(protected Catalog $catalog) {}

    public function index(Request $request): View
    {
        $query = Str::limit(trim((string) $request->query('q', '')), 100, '');

        return view('storefront.category', [
            'title' => $query === '' ? 'Search' : "Search results for “{$query}”",
            'heading' => $query === '' ? 'Search' : "Results for “{$query}”",
            'eyebrow' => 'Search',
            'category' => null,
            'searchQuery' => $query,
            'breadcrumbs' => [['label' => 'Search', 'url' => null]],
            'subcategories' => $this->catalog->searchCategories($query)->take(12),
            ...ProductListing::make($request, $this->catalog->search($query)),
        ]);
    }

    /**
     * Instant-search suggestions for the header search box.
     */
    public function suggest(Request $request): JsonResponse
    {
        $query = Str::limit(trim((string) $request->query('q', '')), 100, '');

        if (mb_strlen($query) < 2) {
            return response()->json(['products' => [], 'categories' => []]);
        }

        $results = $this->catalog->search($query);

        return response()->json([
            'products' => ProductListing::applySort(clone $results, 'default')->limit(6)->get()->map(fn (Product $p) => [
                'name' => $p->name,
                'url' => $p->url,
                'brand' => $p->brand_name,
                'price' => $p->sale_price ? Money::format($p->sale_price) : $p->stock->label(),
                'regular' => $p->discount ? Money::format($p->list_price) : null,
                'image' => $p->getFirstMediaUrl('images', 'thumb') ?: null,
                'icon' => svg('tabler-'.$p->icon, 'size-6')->toHtml(),
            ])->values(),
            'categories' => $this->catalog->searchCategories($query)->take(4)->map(fn (Fluent $c) => [
                'name' => $c->name,
                'url' => $c->url,
            ])->values(),
            'total' => $results->count(),
        ]);
    }
}
