<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Support\Catalog;
use App\Support\ProductListing;
use App\Support\Seo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Brand store: every published product of one brand. "Exclusive Zone" brands get their themed header.
 */
class BrandController extends Controller
{
    /** A–Z list of brands that have published products. */
    public function index(): View
    {
        $brands = Brand::active()
            ->withCount(['products' => fn (Builder $q) => $q->published()])
            ->orderBy('name')
            ->get()
            ->where('products_count', '>', 0);

        return view('storefront.brands', [
            'zones' => $brands->where('is_zone', true)->sortBy('sort')->values(),
            'groups' => $brands->groupBy(fn (Brand $b) => ctype_alpha($l = Str::upper(Str::substr($b->name, 0, 1))) ? $l : '#')->sortKeys(),
        ]);
    }

    public function show(Request $request, Catalog $catalog, string $slug): View
    {
        $brand = $catalog->brand($slug) ?? abort(404);

        return view('storefront.category', [
            ...app(Seo::class)->brand($brand, $brand->meta_title, $brand->meta_description),
            'heading' => $brand->name,
            'eyebrow' => $brand->is_zone ? 'Exclusive Zone' : 'Brand',
            'category' => null,
            'brand' => $brand,
            'categoryDescription' => $brand->description,
            'breadcrumbs' => [['label' => 'Brands', 'url' => route('brand.index')], ['label' => $brand->name, 'url' => null]],
            'subcategories' => $catalog->brandCategories($brand),
            ...ProductListing::make($request, $catalog->products()->where('brand_id', $brand->id), brandFacet: false),
        ]);
    }
}
