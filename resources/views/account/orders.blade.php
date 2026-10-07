<x-account.shell title="My orders" active="account.orders" :breadcrumbs="[['label' => 'Orders', 'url' => null]]">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="font-display text-[24px] font-semibold tracking-tight text-ink">My orders</h1>
            <p class="mt-1 text-sm text-muted">Tap an order to see its progress, items and delivery details.</p>
        </div>
        @if (Route::has('orders.track'))
            <a href="{{ route('orders.track') }}" class="btn btn-sm btn-soft"><x-icon name="search" class="size-4" /> Track a guest order</a>
        @endif
    </div>

    {{-- Status filters --}}
    <nav class="scrollbar-none -mx-4 mt-4 flex gap-2 overflow-x-auto px-4 py-1 sm:-mx-1 sm:px-1" aria-label="Filter orders">
        @foreach ($filters as $key => $item)
            @php $isActive = ($filter ?? '') === $key; @endphp
            <a href="{{ route('account.orders', array_filter(['status' => $key])) }}" @if ($isActive) aria-current="page" @endif @class([
                'chip shrink-0 gap-2 py-2 pr-2.5 pl-3.5 text-[13px] font-medium ring-1 transition',
                'bg-ink text-canvas ring-ink' => $isActive,
                'bg-card text-ink-2 ring-line hover:text-ink hover:ring-line-strong' => ! $isActive,
            ])>
                {{ $item['label'] }}
                <span @class(['rounded-full px-1.5 font-mono text-[11px]', 'bg-canvas/20' => $isActive, 'bg-card-2 text-muted' => ! $isActive])>{{ $item['count'] }}</span>
            </a>
        @endforeach
    </nav>

    <section class="card mt-4 overflow-hidden">
        @forelse ($orders as $order)
            <div @class(['border-t border-line' => ! $loop->first])>@include('account.partials.order-row')</div>
        @empty
            <div class="px-5 py-14 text-center">
                <span class="mx-auto grid size-14 place-items-center rounded-2xl bg-card-2 ring-1 ring-line"><x-icon name="package" class="size-7 [stroke:url(#tf-grad)] [stroke-width:1.5]" /></span>
                @if ($filter)
                    <p class="mt-4 font-semibold text-ink">No {{ Str::lower($filters[$filter]['label']) }} orders</p>
                    <a href="{{ route('account.orders') }}" class="link mt-1 inline-block text-sm">Show all orders</a>
                @else
                    <p class="mt-4 font-semibold text-ink">No orders yet</p>
                    <p class="mt-1 text-[13px] text-muted">Orders placed as a guest can be followed with <a href="{{ route('orders.track') }}" class="link">Track order</a>.</p>
                    <a href="{{ route('home') }}" class="btn btn-sm btn-primary mt-5">Start shopping</a>
                @endif
            </div>
        @endforelse
    </section>
    @if ($orders->hasPages())
        <div class="mt-6">{{ $orders->links('storefront.partials.pagination') }}</div>
    @endif
</x-account.shell>
