{{-- A record's activity log (App\Filament\Actions\HistoryAction). --}}
@if ($entries->isEmpty())
    <div class="rounded-2xl border border-dashed border-gray-200 px-6 py-10 text-center text-sm text-gray-500 dark:border-white/10 dark:text-gray-400">
        Nothing recorded yet — changes made from now on show up here.
    </div>
@else
    <ol class="relative space-y-5 border-s border-gray-200 ps-6 dark:border-white/10">
        @foreach ($entries as $entry)
            <li class="relative">
                <span class="absolute -start-[2.05rem] top-0.5 grid size-5 place-items-center rounded-full bg-white ring-2 ring-gray-200 dark:bg-gray-900 dark:ring-white/10">
                    <span @class([
                        'size-2 rounded-full',
                        'bg-emerald-500' => $entry->event === 'created',
                        'bg-rose-500' => $entry->event === 'deleted',
                        'bg-primary-500' => ! in_array($entry->event, ['created', 'deleted'], true),
                    ])></span>
                </span>
                <div class="flex items-center gap-2 text-sm">
                    @if ($entry->user)
                        <img src="{{ filament()->getUserAvatarUrl($entry->user) }}" alt="" class="size-6 rounded-full object-cover">
                    @endif
                    <span class="font-semibold text-gray-950 dark:text-white">{{ $entry->user_name ?? 'System' }}</span>
                    <span class="text-gray-500 dark:text-gray-400">{{ Str::lcfirst($entry->action()) }}</span>
                </div>
                <p class="mt-0.5 text-xs text-gray-400" title="{{ $entry->created_at->format('d M Y, h:i A') }}">{{ $entry->created_at->diffForHumans() }} · {{ $entry->created_at->format('d M Y, h:i A') }}</p>
                @if ($entry->changes)
                    <ul class="mt-2 space-y-1 rounded-xl bg-gray-50 px-3 py-2 text-[13px] dark:bg-white/5">
                        @foreach ($entry->changes as $change)
                            <li>
                                <span class="text-gray-500 dark:text-gray-400">{{ $change['field'] }}:</span>
                                @if ($change['old'] !== null)
                                    <span class="text-gray-500 line-through decoration-gray-300 dark:text-gray-400">{{ $change['old'] }}</span> →
                                @endif
                                <span class="font-medium text-gray-950 dark:text-white">{{ $change['new'] ?? '—' }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </li>
        @endforeach
    </ol>
@endif
