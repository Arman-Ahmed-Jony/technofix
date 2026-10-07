<?php

namespace App\Support\PcBuilder;

use App\Enums\BuilderRole;
use App\Models\PcBuilderSlot;
use App\Models\Product;
use App\Support\Catalog;
use Illuminate\Support\Collection;

/**
 * A PC build: the active slots and the product (and quantity) picked for each. Only published products that
 * fit their slot are kept, so a build loaded from a link or the browser is always clean.
 *
 * Share/storage format: "slotId:productId" or "slotId:productIdxQty", comma-separated — e.g. "1:12,3:40x2".
 */
class Build
{
    /**
     * @param  Collection<int, PcBuilderSlot>  $slots  active slots keyed by id, in order
     * @param  array<int, array{product: Product, qty: int}>  $items  slot id => pick
     */
    public function __construct(public readonly Collection $slots, public readonly array $items = []) {}

    /**
     * @return array<int, array{0: int, 1: int}> slot id => [product id, qty]
     */
    public static function decode(?string $code): array
    {
        $picks = [];

        foreach (explode(',', (string) $code) as $part) {
            if (preg_match('/^\s*(\d+):(\d+)(?:x(\d+))?\s*$/', $part, $m)) {
                $picks[(int) $m[1]] = [(int) $m[2], max(1, (int) ($m[3] ?? 1))];
            }
        }

        return array_slice($picks, 0, 40, true);
    }

    public static function fromCode(?string $code): self
    {
        return self::load(self::decode($code));
    }

    /**
     * @param  array<int, array{0: int, 1: int}>  $picks  slot id => [product id, qty]
     */
    public static function load(array $picks): self
    {
        $slots = PcBuilderSlot::query()->active()->with('filterAttribute')->get()->keyBy('id');
        $products = app(Catalog::class)->products()->with(['brand', 'category'])
            ->findMany(collect($picks)->pluck(0)->unique()->all())->keyBy('id');

        $items = [];

        foreach ($picks as $slotId => [$productId, $qty]) {
            $slot = $slots->get($slotId);
            $product = $products->get($productId);

            if ($slot && $product && self::accepts($slot, $product)) {
                $items[$slotId] = ['product' => $product, 'qty' => min(max(1, $qty), $slot->max_qty)];
            }
        }

        return new self($slots, $items);
    }

    /** Whether a product (with specValues loaded) belongs in the slot. */
    public static function accepts(PcBuilderSlot $slot, Product $product): bool
    {
        if (! in_array($product->category_id, $slot->allCategoryIds(), true)) {
            return false;
        }

        $values = collect($slot->filter_values ?? [])->filter()->map(fn ($v) => mb_strtolower(trim((string) $v)));

        if (! $slot->filter_attribute_id || $values->isEmpty()) {
            return true;
        }

        $value = $product->specValues->firstWhere('attribute_id', $slot->filter_attribute_id)?->value;

        return $value !== null && $values->contains(mb_strtolower($value));
    }

    /** The same build with one slot changed. */
    public function with(PcBuilderSlot $slot, Product $product, int $qty = 1): self
    {
        $items = $this->items;
        $items[$slot->id] = ['product' => $product, 'qty' => min(max(1, $qty), $slot->max_qty)];

        return new self($this->slots, $items);
    }

    public function without(PcBuilderSlot $slot): self
    {
        $items = $this->items;
        unset($items[$slot->id]);

        return new self($this->slots, $items);
    }

    public function slotFor(BuilderRole $role): ?PcBuilderSlot
    {
        return $this->slots->first(fn (PcBuilderSlot $slot) => $slot->role === $role);
    }

    /** @return array{slot: PcBuilderSlot, product: Product, qty: int}|null */
    public function pick(BuilderRole $role): ?array
    {
        $slot = $this->slotFor($role);

        return $slot && isset($this->items[$slot->id]) ? ['slot' => $slot, ...$this->items[$slot->id]] : null;
    }

    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    public function subtotal(): int
    {
        return (int) collect($this->items)->sum(fn (array $item) => (int) $item['product']->sale_price * $item['qty']);
    }

    /** @return Collection<int, PcBuilderSlot> required slots still empty */
    public function missing(): Collection
    {
        return $this->slots->filter(fn (PcBuilderSlot $slot) => $slot->is_required && ! isset($this->items[$slot->id]))->values();
    }

    /** Link that opens this build in the PC Builder (the code is left readable: ":" and "," are fine in a query). */
    public static function link(string $code, string $route = 'pc-builder'): string
    {
        return route($route).'?b='.$code;
    }

    public function encode(): string
    {
        return collect($this->items)
            ->map(fn (array $item, int $slotId) => $slotId.':'.$item['product']->id.($item['qty'] > 1 ? 'x'.$item['qty'] : ''))
            ->join(',');
    }
}
