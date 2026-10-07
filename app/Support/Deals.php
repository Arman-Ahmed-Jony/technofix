<?php

namespace App\Support;

use App\Models\CampaignProduct;
use Illuminate\Support\Carbon;
use Illuminate\Support\Once;

/**
 * Deal prices from the campaigns running right now, per product (the lowest if a product is in several). Read once
 * per request; schedules are checked on every request, so a sale starts and ends exactly on time.
 */
class Deals
{
    /**
     * @return array<int, array{price: int, ends_at: Carbon, campaign: string, badge: string, campaign_id: int}>
     */
    public static function map(): array
    {
        return once(fn () => CampaignProduct::query()
            ->join('campaigns', 'campaigns.id', '=', 'campaign_products.campaign_id')
            ->where('campaigns.is_active', true)
            ->where('campaigns.starts_at', '<=', now())
            ->where('campaigns.ends_at', '>', now())
            ->orderBy('campaign_products.deal_price')
            ->get(['campaign_products.product_id', 'campaign_products.deal_price', 'campaigns.id as campaign_id', 'campaigns.name', 'campaigns.badge', 'campaigns.ends_at'])
            ->unique('product_id')
            ->mapWithKeys(fn ($row) => [(int) $row->product_id => [
                'price' => (int) $row->deal_price,
                'ends_at' => Carbon::parse($row->ends_at),
                'campaign' => $row->name,
                'badge' => $row->badge,
                'campaign_id' => (int) $row->campaign_id,
            ]])
            ->all());
    }

    /**
     * @return array{price: int, ends_at: Carbon, campaign: string, badge: string, campaign_id: int}|null
     */
    public static function for(int $productId): ?array
    {
        return self::map()[$productId] ?? null;
    }

    public static function flush(): void
    {
        Once::flush();
    }
}
