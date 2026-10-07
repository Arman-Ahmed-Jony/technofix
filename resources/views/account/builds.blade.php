<x-account.shell title="Saved PC builds" active="account.builds" :breadcrumbs="[['label' => 'Saved PC builds', 'url' => null]]">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="font-display text-xl font-semibold tracking-tight text-ink">Saved PC builds</h1>
            <p class="mt-1 text-sm text-muted">Open a build to change it, order it or print a quotation — prices are always today’s.</p>
        </div>
        <a href="{{ route('pc-builder') }}" class="btn btn-sm btn-ink"><x-icon name="cpu" class="size-4" /> PC Builder</a>
    </div>

    <div class="mt-5 grid gap-4 md:grid-cols-2">
        @forelse ($builds as $build)
            @php $saved = $build['model']; @endphp
            <article class="card flex flex-col p-5">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="truncate font-semibold text-ink">{{ $saved->name }}</p>
                        <p class="mt-0.5 text-[12.5px] text-muted">Saved {{ $saved->created_at->format('d M Y') }} · {{ $build['parts'] }} {{ Str::plural('part', $build['parts']) }}</p>
                    </div>
                    <p class="price shrink-0 font-display text-lg">৳{{ number_format($build['total']) }}</p>
                </div>
                <ul class="mt-3 flex-1 space-y-1 text-[13px] text-muted">
                    @foreach ($build['names'] as $name)
                        <li class="truncate">· {{ $name }}</li>
                    @endforeach
                    @if ($build['parts'] > $build['names']->count())
                        <li class="text-faint">and more…</li>
                    @endif
                </ul>
                @if ($build['problems'])
                    <p class="mt-3 flex items-center gap-1.5 text-[12.5px] font-medium text-rose-600 dark:text-rose-400"><x-icon name="alert-triangle" class="size-4" /> {{ $build['problems'] }} compatibility {{ Str::plural('problem', $build['problems']) }}</p>
                @endif
                <div class="mt-4 flex flex-wrap gap-2 border-t border-line pt-3">
                    <a href="{{ $saved->url() }}" class="btn btn-sm btn-ink">Open</a>
                    <a href="{{ \App\Support\PcBuilder\Build::link($saved->code, 'pc-builder.quote') }}" download class="btn btn-sm btn-soft"><x-icon name="file-download" class="size-4" /> PDF quotation</a>
                    <form method="post" action="{{ route('account.builds.destroy', $saved->id) }}" x-data @submit="if (! confirm('Delete this build?')) $event.preventDefault()" class="ml-auto">
                        @csrf
                        @method('delete')
                        <button type="submit" class="btn btn-sm btn-soft hover:text-rose-600" aria-label="Delete build"><x-icon name="trash" class="size-4" /></button>
                    </form>
                </div>
            </article>
        @empty
            <div class="card col-span-full px-6 py-12 text-center">
                <span class="mx-auto grid size-14 place-items-center rounded-2xl bg-card-2 ring-1 ring-line"><x-icon name="cpu" class="size-6 [stroke:url(#tf-grad)]" /></span>
                <p class="mt-4 font-semibold text-ink">No saved builds yet</p>
                <p class="mt-1 text-sm text-muted">Put a PC together in the PC Builder and tap “Save”.</p>
                <a href="{{ route('pc-builder') }}" class="btn btn-sm btn-primary mt-5">Start building</a>
            </div>
        @endforelse
    </div>
</x-account.shell>
