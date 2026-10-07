<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * What a visitor has typed at checkout so far (see App\Services\IncompleteCheckouts).
 */
#[Fillable(['token', 'customer_id', 'name', 'phone', 'email', 'delivery_code', 'district', 'area', 'address', 'note', 'items', 'subtotal',
    'attribution', 'ip', 'user_agent', 'order_id', 'lead_id', 'captured_at'])]
class CheckoutDraft extends Model
{
    protected function casts(): array
    {
        return [
            'items' => 'array',
            'attribution' => 'array',
            'captured_at' => 'datetime',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** "Finish your order" link: refills the customer's cart and details on the checkout page. */
    public function link(): string
    {
        return route('checkout.resume', $this->token);
    }

    /**
     * The cart as the storefront keeps it ({id, name, price, url, qty}) at today's prices — items that are no
     * longer for sale are left out.
     *
     * @return array<int, array<string, mixed>>
     */
    public function cartItems(): array
    {
        $products = Product::query()->published()->findMany(collect($this->items)->pluck('id'))->keyBy('id');

        return collect($this->items)
            ->filter(fn (array $item) => ($product = $products[$item['id']] ?? null) && $product->stock->isPurchasable() && $product->sale_price !== null)
            ->map(fn (array $item) => [...$products[$item['id']]->cartItem(), ...(filled($item['color'] ?? null) ? ['color' => $item['color']] : []), 'qty' => $item['qty']])
            ->values()
            ->all();
    }

    /** "2 × Gigabyte B760M …, 1 × Logitech G102" */
    public function itemsSummary(): string
    {
        return collect($this->items)->map(fn (array $item) => $item['qty'].' × '.$item['name'].(filled($item['color'] ?? null) ? ' ('.$item['color'].')' : ''))->join(', ');
    }
}
