{{-- PC Builder promo: headings and the three steps come from Admin → Content → Home page. --}}
@php
    $store = app(\App\Settings\StoreSettings::class);
    $title = e($section->title ?: 'Build a PC that fits your budget — and just works.');
    if (filled($highlight = $section->setting('highlight')) && str_contains($title, e($highlight))) {
        $title = str_replace(e($highlight), '<span class="text-highlight">'.e($highlight).'</span>', $title);
    }
    $steps = collect($section->setting('steps', []))->filter(fn ($step) => filled($step['title'] ?? null))->take(3);
@endphp
<section class="container-x mt-12 lg:mt-16">
    <div class="relative isolate overflow-hidden rounded-[28px] border border-line bg-card p-6 shadow-card sm:p-10 lg:p-12">
        <div class="absolute -top-32 -right-24 -z-10 size-[28rem] rounded-full bg-brand-500/10 blur-3xl"></div>
        <div class="absolute -bottom-40 -left-24 -z-10 size-[26rem] rounded-full bg-accent-500/10 blur-3xl"></div>
        <div @class(['grid items-center gap-10', 'lg:grid-cols-[minmax(0,1fr)_minmax(0,1.35fr)]' => $steps->isNotEmpty()])>
            <div>
                <p class="eyebrow">{{ $section->eyebrow ?: 'PC Builder' }}</p>
                <h2 class="mt-3 font-display text-[28px] leading-[1.1] font-semibold tracking-[-0.03em] text-ink sm:text-[38px]">{!! $title !!}</h2>
                @if ($section->subtitle)
                    <p class="mt-4 max-w-md text-[15px] leading-relaxed text-muted">{{ $section->subtitle }}</p>
                @endif
                <div class="mt-7 flex flex-wrap gap-3">
                    <a href="{{ Route::has('pc-builder') ? route('pc-builder') : url('pc-builder') }}" class="btn btn-lg btn-primary">Start building <x-icon name="arrow-right" class="size-4" /></a>
                    <a href="{{ $store->telHref() }}" class="btn btn-lg btn-outline"><x-icon name="headset" class="size-5" /> Ask an expert</a>
                </div>
            </div>
            @if ($steps->isNotEmpty())
                <ol class="grid gap-3 sm:grid-cols-3">
                    @foreach ($steps as $step)
                        <li class="relative rounded-2xl bg-card-2 p-5 ring-1 ring-line">
                            <div class="flex items-center justify-between">
                                <span class="grid size-11 place-items-center rounded-2xl bg-card ring-1 ring-line"><x-icon :name="\App\Support\IconSprite::exists($step['icon'] ?? '') ? $step['icon'] : 'circle-check'" class="size-5.5 [stroke:url(#tf-grad)] [stroke-width:1.6]" /></span>
                                <span class="font-mono text-[13px] text-faint">0{{ $loop->iteration }}</span>
                            </div>
                            <h3 class="mt-5 text-[15px] font-semibold text-ink">{{ $step['title'] }}</h3>
                            <p class="mt-1.5 text-[13px] leading-relaxed text-muted">{{ $step['text'] ?? '' }}</p>
                        </li>
                    @endforeach
                </ol>
            @endif
        </div>
    </div>
</section>
