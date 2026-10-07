<?php

namespace App\Services;

use App\Mail\BackInStock;
use App\Models\Product;
use App\Models\StockAlert;
use Illuminate\Support\Facades\Mail;

/**
 * "Notify me" requests: when a product can be ordered again, everyone waiting with an email gets a (queued) message.
 * People who only left a mobile number are followed up by staff from Admin → Leads.
 */
class StockAlerts
{
    public static function productAvailable(Product $product): void
    {
        if (! $product->stock->isPurchasable() || $product->sale_price === null) {
            return;
        }

        StockAlert::query()
            ->where('product_id', $product->id)
            ->whereNull('notified_at')
            ->whereNotNull('email')
            ->each(function (StockAlert $alert) use ($product) {
                Mail::to($alert->email)->queue(new BackInStock($product, $alert->name));
                $alert->update(['notified_at' => now()]);
            });
    }

    public static function waiting(Product $product): int
    {
        return StockAlert::query()->where('product_id', $product->id)->whereNull('notified_at')->count();
    }
}
