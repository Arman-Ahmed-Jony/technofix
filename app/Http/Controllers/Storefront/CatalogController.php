<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\CategoryBrandPage;
use App\Models\Product;
use App\Support\BuyingGuide;
use App\Support\Catalog;
use App\Support\CatalogCache;
use App\Support\ProductListing;
use App\Support\Seo;
use Illuminate\Http\Request;
use Illuminate\Support\Fluent;
use Illuminate\View\View;

/**
 * Resolves flat storefront URLs (/{slug}) to a category or a product. Slugs are unique across both
 * (the admin enforces it).
 */
class CatalogController extends Controller
{
    public function __construct(protected Catalog $catalog) {}

    public function show(Request $request, string $slug): View
    {
        if ($category = $this->catalog->category($slug)) {
            return $this->category($request, $category);
        }

        if ($product = $this->catalog->product($slug)) {
            return $this->product($product);
        }

        abort(404);
    }

    protected function category(Request $request, Fluent $category): View
    {
        $children = $this->catalog->children($category);
        $seo = Category::query()->find($category->id, ['id', 'description', 'faqs', 'meta_title', 'meta_description']);
        $stats = $this->catalog->stats($category);

        return view('storefront.category', [
            ...app(Seo::class)->category($category, $stats, $seo?->meta_title, $seo?->meta_description),
            'heading' => $category->name,
            'category' => $category,
            'guide' => BuyingGuide::make($category->name, $seo?->description, $seo?->faqs),
            'breadcrumbs' => $this->catalog->ancestors($category)->map(fn (Fluent $c) => ['label' => $c->name, 'url' => $c->url])->all(),
            'subcategories' => $children,
            'stats' => $stats,
            // Spec filters only on leaf categories, where every product shares the same template.
            ...ProductListing::make(
                $request,
                $this->catalog->productsIn($category),
                $children->isEmpty() ? (CatalogCache::data()['templates'][$category->id] ?? []) : [],
            ),
        ]);
    }

    /**
     * A brand's products in a category ("Gigabyte Motherboard Price in Bangladesh"). Valid pairs without products
     * still render (menu links may point here before stock arrives) but are kept out of search engines.
     */
    public function categoryBrand(Request $request, string $category, string $brand): View
    {
        $category = $this->catalog->category($category) ?? abort(404);
        $brand = $this->catalog->brand($brand) ?? abort(404);
        $base = $this->catalog->productsIn($category)->where('brand_id', $brand->id);
        $children = $this->catalog->children($category);
        $name = "{$brand->name} {$category->name}";
        $page = CategoryBrandPage::query()->where(['category_id' => $category->id, 'brand_id' => $brand->id])->first();
        $stats = (clone $base)->toBase()->selectRaw('count(*) as total, min(price) as low')->first();

        return view('storefront.category', [
            ...app(Seo::class)->categoryBrand($category, $brand, $page?->meta_title, $page?->meta_description),
            'noindex' => ! $stats->total,
            'heading' => $name,
            'eyebrow' => $category->name,
            'category' => $category,
            'brand' => $brand,
            'guide' => BuyingGuide::make($name, $page?->content, $page?->faqs),
            'breadcrumbs' => [
                ...$this->catalog->ancestors($category)->map(fn (Fluent $c) => ['label' => $c->name, 'url' => $c->url])->all(),
                ['label' => $brand->name, 'url' => null],
            ],
            'subcategories' => $this->catalog->categoryBrands($category, except: $brand),
            'subcategoriesLabel' => 'Other brands',
            'stats' => ['count' => (int) $stats->total, 'from' => $stats->low !== null ? (int) $stats->low : null],
            ...ProductListing::make(
                $request,
                $base,
                $children->isEmpty() ? (CatalogCache::data()['templates'][$category->id] ?? []) : [],
                brandFacet: false,
            ),
        ]);
    }

    protected function product(Product $product): View
    {
        $category = $this->catalog->categoryById($product->category_id);

        return view('storefront.product', [
            'product' => $product,
            ...app(Seo::class)->product($product),
            'breadcrumbs' => [
                ...($category ? $this->catalog->ancestors($category)->map(fn (Fluent $c) => ['label' => $c->name, 'url' => $c->url])->all() : []),
                ['label' => $product->name, 'url' => null],
            ],
            'related' => $this->catalog->related($product),
            'accessories' => $this->catalog->accessories($product),
        ]);
    }
}
