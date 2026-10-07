@php
    $buyable = $products->filter(fn ($p) => $p->stock->isPurchasable())
        ->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'price' => $p->sale_price, 'url' => $p->url])->values();
@endphp

<x-layouts.storefront title="Wishlist" :noindex="true">
    <div class="container-x pt-5 lg:pt-7" x-data="savedList('wishlist', @js($hasIds))">
        <x-breadcrumbs :items="[['label' => 'Wishlist', 'url' => null]]" />

        <div class="mt-4 flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="eyebrow">Saved for later</p>
                <h1 class="mt-2.5 font-display text-[28px] leading-tight font-semibold tracking-[-0.03em] text-ink sm:text-[36px]">
                    Wishlist <span class="font-mono text-lg font-medium text-faint">({{ $products->count() }})</span>
                </h1>
            </div>
            @if ($products->isNotEmpty())
                <div class="flex flex-wrap gap-2">
                    @if ($buyable->isNotEmpty())
                        <button type="button" class="btn btn-sm btn-primary" @click="@js($buyable).forEach((item) => $store.cart.add(item, 1, false)); $store.ui.cartOpen = true">
                            <x-icon name="shopping-bag-plus" class="size-4" /> Add all to cart
                        </button>
                    @endif
                    <button type="button" @click="navigator.share ? navigator.share({ title: 'My TechnoFix wishlist', url: location.href }) : navigator.clipboard.writeText(location.href).then(() => $store.toast.show('Link copied'))" class="btn btn-sm btn-soft">
                        <x-icon name="share" class="size-4" /> Share
                    </button>
                    <button type="button" @click="clear()" class="btn btn-sm btn-soft"><x-icon name="trash" class="size-4" /> Clear</button>
                </div>
            @endif
        </div>

        @if ($products->isEmpty())
            <div class="mesh-dark grain relative isolate mt-6 overflow-hidden rounded-[28px] px-6 py-14 text-center text-white">
                <div class="grid-lines absolute inset-0 -z-10"></div>
                <span class="glass mx-auto grid size-20 place-items-center rounded-3xl"><x-icon name="heart" class="size-9 [stroke:url(#tf-grad-accent)] [stroke-width:1.4]" /></span>
                <p class="mt-6 font-display text-2xl font-semibold tracking-tight">Your wishlist is empty</p>
                <p class="mx-auto mt-2 max-w-sm text-sm text-white/65">Tap the heart on any product to save it here for later.</p>
                <a href="{{ route('home') }}" class="btn btn-lg btn-primary mt-7">Browse products</a>
            </div>
        @else
            <div class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-4 lg:grid-cols-4 xl:grid-cols-5">
                @foreach ($products as $product)
                    <div class="flex flex-col gap-2">
                        <x-product.card :product="$product" class="flex-1" />
                        <button type="button" @click="remove({{ $product->id }})" class="flex items-center justify-center gap-1.5 rounded-xl py-1.5 text-[12.5px] font-medium text-muted transition hover:bg-card-2 hover:text-rose-500">
                            <x-icon name="trash" class="size-3.5" /> Remove
                        </button>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-layouts.storefront>
