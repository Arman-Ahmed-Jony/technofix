{{-- ─── Scrolling notice (Admin → Content → Home page → Scrolling notice) ─── --}}
{{-- The messages are printed twice in one track that slides left by half its width, so the loop is seamless.
     Hovering or focusing pauses it; with "reduce motion" switched on it doesn't move and can be swiped instead. --}}
<section class="container-x mt-4 lg:mt-6" aria-label="{{ $label ?: 'Notice' }}">
    <div class="flex items-center gap-2 rounded-full bg-card p-1.5 shadow-card ring-1 ring-line sm:gap-3">
        @if ($label)
            <span class="flex shrink-0 items-center gap-1.5 rounded-full bg-accent-800 py-1.5 pr-3.5 pl-2.5 text-[12px] max-sm:px-2.5 font-semibold tracking-wide text-white uppercase sm:text-[12.5px]">
                <x-icon :name="$icon" class="size-4" /> <span class="max-sm:sr-only">{{ $label }}</span>
            </span>
        @else
            <span class="grid size-8 shrink-0 place-items-center rounded-full bg-accent-800 text-white"><x-icon :name="$icon" class="size-4" /></span>
        @endif

        <div class="fade-x group min-w-0 flex-1 overflow-hidden motion-reduce:scrollbar-none motion-reduce:overflow-x-auto">
            <div class="flex w-max animate-marquee items-center group-hover:[animation-play-state:paused] group-focus-within:[animation-play-state:paused] motion-reduce:animate-none"
                style="animation-duration: {{ $seconds }}s">
                @foreach ([0, 1] as $copy)
                    <ul class="flex shrink-0 items-center" @if ($copy) aria-hidden="true" @endif>
                        @foreach ($messages as $message)
                            <li class="flex items-center whitespace-nowrap text-[14px] text-ink-2 sm:text-[14.5px]">
                                @if ($message['url'])
                                    <a href="{{ $message['url'] }}" @if ($copy) tabindex="-1" @endif class="font-medium text-ink underline decoration-line-strong underline-offset-4 transition hover:text-highlight hover:decoration-current">{{ $message['text'] }}</a>
                                @else
                                    <span>{{ $message['text'] }}</span>
                                @endif
                                <span class="mx-6 size-1.5 rounded-full bg-accent-500" aria-hidden="true"></span>
                            </li>
                        @endforeach
                    </ul>
                @endforeach
            </div>
        </div>
    </div>
</section>
