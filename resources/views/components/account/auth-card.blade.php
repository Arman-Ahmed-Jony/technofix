@props(['title', 'eyebrow' => 'Account', 'intro' => null])
{{-- Sign-in style pages: brand panel + form card. --}}
<x-layouts.storefront :title="$title" :noindex="true">
    <div class="container-x pt-5 lg:pt-7">
        <div class="mx-auto mt-4 grid max-w-4xl items-stretch gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.1fr)]">
            <section class="mesh-dark grain relative isolate hidden overflow-hidden rounded-[28px] px-8 py-10 text-white lg:block">
                <div class="grid-lines absolute inset-0 -z-10"></div>
                <span class="glass grid size-14 place-items-center rounded-2xl"><x-icon name="user" class="size-7 [stroke:url(#tf-grad)] [stroke-width:1.5]" /></span>
                <p class="eyebrow mt-6 text-accent-300">{{ $eyebrow }}</p>
                <p class="mt-3 font-display text-[30px] leading-tight font-semibold tracking-[-0.03em]">Your TechnoFix account</p>
                <ul class="mt-6 space-y-3 text-[14px] text-white/75">
                    @foreach ([['package', 'See all your orders and where they are'], ['map-pin', 'Save addresses for one-tap checkout'], ['shield-check', 'Keep your invoices for warranty']] as [$icon, $text])
                        <li class="flex items-center gap-3"><span class="glass grid size-8 shrink-0 place-items-center rounded-xl"><x-icon :name="$icon" class="size-4" /></span> {{ $text }}</li>
                    @endforeach
                </ul>
            </section>

            <section class="card p-6 sm:p-8">
                <h1 class="font-display text-[26px] leading-tight font-semibold tracking-[-0.02em] text-ink">{{ $title }}</h1>
                @if ($intro)
                    <p class="mt-1.5 text-sm text-muted">{{ $intro }}</p>
                @endif
                @if (session('status'))
                    <p class="mt-5 flex items-start gap-2 rounded-2xl bg-emerald-500/10 p-3.5 text-[13.5px] text-emerald-800 ring-1 ring-emerald-500/20 dark:text-emerald-300" role="status">
                        <x-icon name="circle-check" class="mt-0.5 size-4 shrink-0" /> {{ session('status') }}
                    </p>
                @endif
                <div class="mt-6">{{ $slot }}</div>
            </section>
        </div>
    </div>
</x-layouts.storefront>
