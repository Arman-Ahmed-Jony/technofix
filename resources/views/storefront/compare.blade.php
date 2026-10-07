@use('App\Enums\StockStatus')
@php
    $store = app(\App\Settings\StoreSettings::class);
    $value = fn ($product, string $key) => $specs[$product->id][$key] ?? null;

    // Each row: label, per-product values (for the "differences only" check), and a renderer.
    $rows = collect([
        ['Brand', fn ($p) => $p->brand_name],
        ['Model', fn ($p) => $p->model],
        ['Warranty', fn ($p) => $p->warranty],
        ...$specKeys->map(fn ($key) => [$key, fn ($p) => $value($p, $key)])->all(),
    ])->map(function (array $row) use ($products) {
        $values = $products->map(fn ($p) => $row[1]($p));

        return ['label' => $row[0], 'values' => $values, 'same' => $products->count() > 1 && $values->map(fn ($v) => (string) $v)->unique()->count() === 1];
    });
@endphp

<x-layouts.storefront title="Compare products" :noindex="true">
    <div class="container-x pt-5 lg:pt-7" x-data="savedList('compare', @js($hasIds))">
        <div x-data="{ diffOnly: false }">
            <x-breadcrumbs :items="[['label' => 'Compare', 'url' => null]]" />

            <div class="mt-4 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="eyebrow">Compare</p>
                    <h1 class="mt-2.5 font-display text-[28px] leading-tight font-semibold tracking-[-0.03em] text-ink sm:text-[36px]">Compare products</h1>
                    <p class="mt-1.5 text-sm text-muted">Up to 4 products side by side. Tap <x-icon name="arrows-exchange" class="inline size-4 align-[-3px]" /> on any product to add it.</p>
                </div>
                @if ($products->count() > 1)
                    <div class="flex items-center gap-2">
                        <label class="flex cursor-pointer items-center gap-2.5 rounded-xl bg-card px-3.5 py-2 text-[13px] font-medium text-ink ring-1 ring-line">
                            <input type="checkbox" x-model="diffOnly" class="checkbox"> Show only differences
                        </label>
                        <button type="button" @click="clear()" class="btn btn-sm btn-soft"><x-icon name="trash" class="size-4" /> Clear</button>
                    </div>
                @endif
            </div>

            @if ($products->isEmpty())
                <div class="mesh-dark grain relative isolate mt-6 overflow-hidden rounded-[28px] px-6 py-14 text-center text-white">
                    <div class="grid-lines absolute inset-0 -z-10"></div>
                    <span class="glass mx-auto grid size-20 place-items-center rounded-3xl"><x-icon name="arrows-exchange" class="size-9 [stroke:url(#tf-grad)] [stroke-width:1.4]" /></span>
                    <p class="mt-6 font-display text-2xl font-semibold tracking-tight">Nothing to compare yet</p>
                    <p class="mx-auto mt-2 max-w-sm text-sm text-white/65">Add two or more products — laptops, monitors, routers… — and see their specs side by side.</p>
                    <a href="{{ route('home') }}" class="btn btn-lg btn-primary mt-7">Browse products</a>
                </div>
            @else
                @if ($mixedCategories)
                    <p class="mt-5 flex items-center gap-2 rounded-2xl border border-amber-500/25 bg-amber-500/10 px-4 py-3 text-[13px] text-amber-800 dark:text-amber-300">
                        <x-icon name="info-circle" class="size-4 shrink-0" /> These products are from different categories, so some specifications won’t line up.
                    </p>
                @endif

                <div class="scrollbar-none mt-6 overflow-x-auto rounded-[24px] border border-line bg-card shadow-card">
                    <table class="w-full min-w-[680px] table-fixed border-collapse text-left text-[13.5px]">
                        <colgroup>
                            <col class="w-28 sm:w-48">
                            @foreach ($products as $product)
                                <col>
                            @endforeach
                        </colgroup>
                        <thead>
                            <tr class="align-top">
                                <th class="sticky left-0 z-10 bg-card p-4 text-left align-bottom">
                                    <span class="font-mono text-[12px] font-medium text-faint">{{ $products->count() }} / 4</span>
                                </th>
                                @foreach ($products as $product)
                                    <th class="border-l border-line p-4 font-normal">
                                        <div class="relative mx-auto max-w-[210px]">
                                            <button type="button" @click="remove({{ $product->id }})" class="absolute top-1.5 right-1.5 z-10 grid size-8 place-items-center rounded-full bg-card/90 text-muted ring-1 ring-line backdrop-blur hover:text-rose-500" aria-label="Remove {{ $product->name }}">
                                                <x-icon name="x" class="size-4" />
                                            </button>
                                            <a href="{{ $product->url }}" class="block overflow-hidden rounded-xl" tabindex="-1" aria-hidden="true"><x-product.image :product="$product" /></a>
                                        </div>
                                        <p class="mt-3 text-[10.5px] font-semibold tracking-[0.12em] text-faint uppercase">{{ $product->brand_name }}</p>
                                        <a href="{{ $product->url }}" class="mt-1 line-clamp-2 min-h-[2.7em] text-[13.5px] leading-snug font-medium text-ink hover:text-highlight">{{ $product->name }}</a>
                                        <x-product.price :product="$product" class="mt-2.5" />
                                        <div class="mt-3">
                                            @if ($product->stock->isPurchasable())
                                                <button type="button" class="btn btn-sm btn-ink w-full" @click="$store.cart.add(@js($product->cartItem()))">
                                                    <x-icon name="shopping-bag-plus" class="size-4" /> Add to cart
                                                </button>
                                            @else
                                                <a href="{{ $store->telHref() }}" class="btn btn-sm btn-outline w-full"><x-icon name="phone-call" class="size-4" /> {{ $product->stock === StockStatus::CallForPrice ? 'Call for price' : $product->stock->label() }}</a>
                                            @endif
                                        </div>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="border-t border-line">
                                <th class="sticky left-0 z-10 bg-card-2 px-4 py-3 text-[12.5px] font-semibold text-muted">Availability</th>
                                @foreach ($products as $product)
                                    <td class="border-l border-line px-4 py-3"><x-stock-badge :status="$product->stock" /></td>
                                @endforeach
                            </tr>
                            @foreach ($rows as $row)
                                <tr class="border-t border-line" @if ($row['same']) x-show="!diffOnly" @endif>
                                    <th class="sticky left-0 z-10 bg-card-2 px-4 py-3 text-[12.5px] font-semibold text-muted">{{ $row['label'] }}</th>
                                    @foreach ($row['values'] as $cell)
                                        <td @class(['border-l border-line px-4 py-3', 'text-ink' => filled($cell), 'text-faint' => blank($cell)])>{{ filled($cell) ? $cell : '—' }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                            <tr class="border-t border-line align-top">
                                <th class="sticky left-0 z-10 bg-card-2 px-4 py-3 text-[12.5px] font-semibold text-muted">Key features</th>
                                @foreach ($products as $product)
                                    <td class="border-l border-line px-4 py-3">
                                        <ul class="space-y-1.5 text-[12.5px] text-ink-2">
                                            @foreach ($product->key_features ?? [] as $feature)
                                                <li class="flex gap-2"><x-icon name="check" class="mt-0.5 size-3.5 text-emerald-500" /> {{ $feature }}</li>
                                            @endforeach
                                        </ul>
                                    </td>
                                @endforeach
                            </tr>
                        </tbody>
                    </table>
                </div>
                @if ($products->count() < 4)
                    <p class="mt-4 text-center text-[13px] text-muted">You can add {{ 4 - $products->count() }} more — tap <x-icon name="arrows-exchange" class="inline size-4 align-[-3px]" /> on any product.</p>
                @endif
            @endif
        </div>
    </div>
</x-layouts.storefront>
