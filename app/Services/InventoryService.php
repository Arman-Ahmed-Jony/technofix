<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\StockReceipt;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Stock-in (goods arriving), cancelling a stock-in, and staff adjustments with a reason. Quantities always move through
 * StockService (ledger + automatic in / out of stock).
 */
class InventoryService
{
    /**
     * Records goods arriving: adds the stock and updates each product's cost price to the average cost of what's on
     * hand (so the profit report stays right).
     *
     * @param  array{supplier_id?: ?int, reference?: ?string, received_on?: mixed, note?: ?string, items: list<array{product_id: int, qty: int, unit_cost: int}>}  $data
     */
    public function receive(array $data, ?User $by = null): StockReceipt
    {
        $lines = collect($data['items'])
            ->filter(fn ($line) => (int) ($line['qty'] ?? 0) > 0 && filled($line['product_id'] ?? null))
            ->groupBy(fn ($line) => (int) $line['product_id'])
            ->map(function ($rows, int $productId) {
                $qty = (int) $rows->sum('qty');
                $total = (int) $rows->sum(fn ($line) => (int) $line['qty'] * (int) $line['unit_cost']);

                return ['product_id' => $productId, 'qty' => $qty, 'unit_cost' => (int) round($total / $qty), 'total' => $total];
            })
            ->values();

        if ($lines->isEmpty()) {
            throw new InvalidArgumentException('A stock-in needs at least one product with a quantity.');
        }

        return DB::transaction(function () use ($data, $by, $lines) {
            $receipt = StockReceipt::create([
                'number' => StockReceipt::nextNumber(),
                'supplier_id' => $data['supplier_id'] ?? null,
                'reference' => $data['reference'] ?? null,
                'received_on' => $data['received_on'] ?? now(),
                'total_cost' => $lines->sum('total'),
                'note' => $data['note'] ?? null,
                'user_id' => $by?->id,
            ]);
            $receipt->items()->createMany($lines->all());

            foreach ($lines as $line) {
                $product = Product::withTrashed()->lockForUpdate()->findOrFail($line['product_id']);
                $onHand = max(0, (int) $product->stock_qty);
                $cost = $product->cost_price && $onHand > 0
                    ? (int) round(($onHand * $product->cost_price + $line['total']) / ($onHand + $line['qty']))
                    : $line['unit_cost'];

                // Query update: the receipt is the record of this change (no per-product log noise).
                Product::withTrashed()->whereKey($product->id)->update(['cost_price' => $cost]);
                StockService::change($product->id, $line['qty'], 'purchase', note: $receipt->number.($receipt->supplier ? ' · '.$receipt->supplier->name : ''), receipt: $receipt);
            }

            return $receipt;
        });
    }

    /** A stock-in entered by mistake: its quantities come back out (cost prices stay as they are). */
    public function cancel(StockReceipt $receipt, ?string $note = null): void
    {
        if ($receipt->isCancelled()) {
            return;
        }

        DB::transaction(function () use ($receipt, $note) {
            foreach ($receipt->items as $item) {
                StockService::change($item->product_id, -$item->qty, 'purchase_cancelled', note: trim($receipt->number.' '.$note), receipt: $receipt);
            }

            $receipt->update(['cancelled_at' => now()]);
        });
    }

    /**
     * Staff adjustment: add, remove or set the counted quantity, with a reason (StockMovement::REASONS).
     *
     * @param  'add'|'remove'|'set'  $mode
     */
    public function adjust(Product $product, string $mode, int $qty, string $reason, ?string $note = null): int
    {
        abort_unless(array_key_exists($reason, StockMovement::REASONS), 422, 'Unknown reason.');

        $change = match ($mode) {
            'add' => abs($qty),
            'remove' => -abs($qty),
            'set' => $qty - (int) $product->stock_qty,
            default => throw new InvalidArgumentException("Unknown mode [{$mode}]."),
        };

        StockService::change($product->id, $change, 'adjustment', note: $note, reason: $reason);

        return $change;
    }
}
