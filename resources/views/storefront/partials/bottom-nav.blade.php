{{-- Mobile bottom navigation (frosted). --}}
<nav class="fixed inset-x-0 bottom-0 z-40 border-t border-line bg-canvas/85 pb-[env(safe-area-inset-bottom)] backdrop-blur-xl backdrop-saturate-150 lg:hidden" aria-label="Quick links">
    <ul class="grid h-16 grid-cols-5 text-[10.5px] font-medium text-muted">
        <li>
            <a href="{{ route('home') }}" @class(['flex h-full flex-col items-center justify-center gap-1', 'text-ink' => request()->routeIs('home')])>
                <x-icon name="home" class="size-[22px]" /> Home
            </a>
        </li>
        <li>
            <button type="button" @click="$store.ui.openMenu()" class="flex h-full w-full flex-col items-center justify-center gap-1">
                <x-icon name="layout-grid" class="size-[22px]" /> Categories
            </button>
        </li>
        <li>
            <a href="{{ url('pc-builder') }}" class="flex h-full flex-col items-center justify-center gap-1 font-semibold text-accent-800 dark:text-accent-400">
                <span class="btn-primary -mt-6 grid size-12 place-items-center rounded-2xl ring-4 ring-canvas">
                    <x-icon name="cpu" class="size-[22px]" />
                </span>
                PC Build
            </a>
        </li>
        <li>
            <button type="button" @click="$store.ui.cartOpen = true" class="flex h-full w-full flex-col items-center justify-center gap-1">
                <span class="relative">
                    <x-icon name="shopping-bag" class="size-[22px]" />
                    <span x-show="$store.cart.count" x-text="$store.cart.count" x-cloak class="absolute -top-1.5 -right-2.5 grid h-4 min-w-4 place-items-center rounded-full bg-accent-600 px-1 text-[9.5px] font-bold text-white ring-2 ring-canvas"></span>
                </span>
                Cart
            </button>
        </li>
        <li>
            <a href="{{ route('account') }}" class="flex h-full flex-col items-center justify-center gap-1">
                <x-icon name="user" class="size-[22px]" /> Account
            </a>
        </li>
    </ul>
</nav>
