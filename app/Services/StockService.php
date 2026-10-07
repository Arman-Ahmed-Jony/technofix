<?php

namespace App\Services;

use App\Enums\StockStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\StockReceipt;
use App\Settings\OrderSettings;
use App\Support\PageCache;

/**
 * Changes product quantities and writes each change to the stock ledger (stock_movements).
 * Quantities may go below zero: shops here often sell items they can source the same day.
 */
class StockService
{
    public static function change(int $productId, int $change, string $type, ?Order $order = null, ?string $note = null, ?StockReceipt $receipt = null, ?string $reason = null): void
    {
        if ($change === 0) {
            return;
        }

        // Atomic update without model events, so the "changed by staff" logging in Product doesn't fire.
        Product::withTrashed()->whereKey($productId)->increment('stock_qty', $change);
        $product = Product::withTrashed()->find($productId, ['id', 'stock_qty', 'stock_status']);

        if (! $product) {
            return;
        }

        StockMovement::create([
            'product_id' => $productId,
            'change' => $change,
            'balance' => $product->stock_qty,
            'type' => $type,
            'reason' => $reason,
            'order_id' => $order?->id,
            'stock_receipt_id' => $receipt?->id,
            'user_id' => auth('web')->id(), // staff only — never a signed-in customer
            'note' => $note,
        ]);

        self::syncStatus($product);
    }

    /**
     * With automatic stock status on: In Stock ↔ Out of Stock follows the quantity. Other statuses (pre-order,
     * upcoming, call for price) are never touched.
     */
    public static function syncStatus(Product $product): void
    {
        if (! app(OrderSettings::class)->auto_stock_status) {
            return;
        }

        $status = match (true) {
            $product->stock_qty <= 0 && $product->stock_status === StockStatus::InStock => StockStatus::OutOfStock,
            $product->stock_qty > 0 && $product->stock_status === StockStatus::OutOfStock => StockStatus::InStock,
            default => null,
        };

        if ($status) {
            Product::withTrashed()->whereKey($product->id)->update(['stock_status' => $status->value]); // query update: no model events
            PageCache::invalidate(); // product pages show the stock status

            if ($status === StockStatus::InStock) {
                StockAlerts::productAvailable($product->fresh());
            }
        }
    }
}
