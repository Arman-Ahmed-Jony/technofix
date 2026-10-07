<x-layouts.storefront title="Page not found" :noindex="true">
    <div class="container-x py-12 sm:py-20">
        <div class="mesh-dark grain relative isolate mx-auto max-w-3xl overflow-hidden rounded-[28px] px-6 py-14 text-center sm:px-12">
            <div class="grid-lines absolute inset-0 -z-10"></div>
            <span class="glass mx-auto grid size-20 place-items-center rounded-3xl"><x-icon name="plug-connected-x" class="size-10 [stroke:url(#tf-grad-accent)] [stroke-width:1.4]" /></span>
            <p class="mt-7 font-mono text-sm text-accent-300">Error 404</p>
            <h1 class="mt-2 font-display text-[30px] leading-tight font-semibold tracking-[-0.03em] text-white sm:text-[40px]">This page got <span class="text-gradient">disconnected</span></h1>
            <p class="mx-auto mt-3 max-w-md text-[15px] text-white/65">The page you’re looking for doesn’t exist or is coming soon. Try a search, or head back to the homepage.</p>
            <form action="{{ route('search') }}" class="mx-auto mt-8 flex max-w-md gap-2">
                <input type="search" name="q" placeholder="Search products…" class="input h-12 border-white/15 bg-white/10 text-white placeholder:text-white/40" aria-label="Search products">
                <button class="btn btn-lg btn-primary">Search</button>
            </form>
            <a href="{{ route('home') }}" class="mt-6 inline-flex items-center gap-1 text-sm font-semibold text-white/80 hover:text-white"><x-icon name="arrow-left" class="size-4" /> Back to homepage</a>
        </div>
    </div>
</x-layouts.storefront>
