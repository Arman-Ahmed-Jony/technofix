{{-- Announcement bar (Admin → Content → Banners → Announcement bar). Several messages rotate; closing it hides this
     set of messages in this browser until they change (checked by the inline script before paint — no flash). --}}
@php
    $messages = \App\Models\Banner::announcements();
    $key = $messages->map(fn ($m) => $m->id.'-'.$m->updated_at?->timestamp)->join('.');
    $bars = [
        'blue' => 'bg-[linear-gradient(90deg,#1d71b8,#009fe3)]',
        'orange' => 'bg-[linear-gradient(90deg,#e94e1b,#f39200)]',
        'teal' => 'bg-[linear-gradient(90deg,#0f766e,#14b8a6)]',
        'indigo' => 'bg-[linear-gradient(90deg,#4338ca,#6366f1)]',
        'navy' => 'bg-[linear-gradient(90deg,#033851,#0b4865)]',
    ];
@endphp
@if ($messages->isNotEmpty())
    <div id="tf-announcement" class="relative z-50 text-white" role="region" aria-label="Announcement"
        x-data="{ active: 0, count: {{ $messages->count() }}, timer: null,
            init() { if (this.count > 1) this.timer = setInterval(() => this.active = (this.active + 1) % this.count, 5000) },
            close() { clearInterval(this.timer); try { localStorage.setItem('tf-announcement', @js($key)) } catch (e) {} this.$root.remove() } }">
        <div class="relative h-10 overflow-hidden">
            @foreach ($messages as $i => $message)
                @php($link = \App\Models\Banner::link($message->button_url))
                <div class="absolute inset-0 flex items-center justify-center px-12 transition duration-500 {{ $bars[$message->color] ?? $bars['blue'] }}"
                    @if ($i > 0) style="opacity:0;transform:translateY(100%)" @endif
                    :style="active === {{ $i }} ? { opacity: 1, transform: 'none' } : { opacity: 0, transform: 'translateY(' + (active > {{ $i }} ? '-100%' : '100%') + ')' }">
                    <p class="flex min-w-0 items-center gap-2 text-[13px] font-medium">
                        @if ($message->icon)
                            <x-icon :name="$message->icon" class="size-4 shrink-0 opacity-90" />
                        @endif
                        <span class="truncate">{{ $message->title }}</span>
                        @if ($link)
                            <a href="{{ $link }}" class="inline-flex shrink-0 items-center gap-1 font-semibold underline decoration-white/40 underline-offset-2 hover:decoration-white">{{ $message->button_label ?: 'Learn more' }} <x-icon name="arrow-right" class="size-3.5" /></a>
                        @endif
                    </p>
                </div>
            @endforeach
            <button type="button" @click="close()" class="absolute top-1/2 right-2 grid size-7 -translate-y-1/2 place-items-center rounded-full text-white/80 transition hover:bg-white/15 hover:text-white" aria-label="Close announcement">
                <x-icon name="x" class="size-4" />
            </button>
        </div>
    </div>
    <script>try { if (localStorage.getItem('tf-announcement') === @js($key)) document.getElementById('tf-announcement').remove(); } catch (e) {}</script>
@endif
