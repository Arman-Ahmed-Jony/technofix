<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Product;
use App\Support\Catalog;
use Illuminate\View\View;

/** /offers — running campaigns (deal prices + countdown), the next ones coming up, and other hot deals. */
class OffersController extends Controller
{
    public function __invoke(Catalog $catalog): View
    {
        $live = Campaign::query()->live()->with('items')->orderBy('ends_at')->get()
            ->map(fn (Campaign $campaign) => [
                'campaign' => $campaign,
                'products' => $catalog->products()->whereIn('id', $campaign->items->pluck('product_id'))->get()
                    // only products whose best running deal is this campaign's (a product in two sales shows once)
                    ->filter(fn (Product $product) => ($product->deal()['campaign_id'] ?? null) === $campaign->id)
                    ->sortBy(fn (Product $product) => $campaign->items->firstWhere('product_id', $product->id)?->sort)
                    ->values(),
            ])
            ->filter(fn (array $row) => $row['products']->isNotEmpty())
            ->values();

        $inCampaigns = $live->flatMap(fn (array $row) => $row['products']->pluck('id'))->all();

        return view('storefront.offers', [
            'live' => $live,
            'upcoming' => Campaign::query()->upcoming()->orderBy('starts_at')->limit(3)->get(),
            'hotDeals' => $catalog->flagged('deal', 24)->reject(fn (Product $product) => in_array($product->id, $inCampaigns, true))->values(),
        ]);
    }
}
