{{-- Backups on this server (App\Filament\Pages\ManageBackups). --}}
@php
    $latest = $backups->first();
    $stale = ! $latest || $latest->date()->lt(now()->subDays(2));
    $size = fn (int $bytes) => $bytes >= 1048576 ? number_format($bytes / 1048576, 1).' MB' : number_format(max(1, $bytes / 1024)).' KB';
@endphp
<div class="space-y-4">
    <div @class([
        'flex items-start gap-3 rounded-xl px-4 py-3 text-sm',
        'bg-emerald-50 text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-300' => ! $stale,
        'bg-amber-50 text-amber-800 dark:bg-amber-500/10 dark:text-amber-300' => $stale,
    ])>
        <x-filament::icon :icon="$stale ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-shield-check'" class="mt-0.5 size-5 shrink-0" />
        <div>
            @if ($running)
                <b>A backup is running…</b> It appears below in a few minutes.
            @elseif ($latest)
                <b>Last backup {{ $latest->date()->diffForHumans() }}</b> ({{ $latest->date()->format('D j M Y, h:i A') }}, {{ $size($latest->sizeInBytes()) }}).
                {{ $backups->count() }} {{ Str::plural('backup', $backups->count()) }} kept, {{ $size($backups->sum(fn ($b) => $b->sizeInBytes())) }} in all.
                @if ($stale)
                    That’s more than 2 days ago — check that the server’s cron job runs.
                @endif
            @else
                <b>No backups yet.</b> Press “Back up now” to make the first one.
            @endif
        </div>
    </div>

    @if ($backups->isNotEmpty())
        <div class="overflow-hidden rounded-xl ring-1 ring-gray-950/5 dark:ring-white/10">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold tracking-wide text-gray-500 uppercase dark:bg-white/5 dark:text-gray-400">
                    <tr><th class="px-4 py-2.5">Made</th><th class="px-4 py-2.5 text-right">Size</th><th class="px-4 py-2.5"></th></tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                    @foreach ($backups->take(30) as $backup)
                        <tr>
                            <td class="px-4 py-2.5">
                                <span class="font-medium text-gray-950 dark:text-white">{{ $backup->date()->format('D j M Y, h:i A') }}</span>
                                <span class="ms-2 text-gray-400">{{ $backup->date()->diffForHumans() }}</span>
                            </td>
                            <td class="px-4 py-2.5 text-right font-mono text-gray-600 dark:text-gray-300">{{ $size($backup->sizeInBytes()) }}</td>
                            <td class="px-4 py-2.5 text-right whitespace-nowrap">
                                <button type="button" wire:click="download(@js($backup->path()))" class="font-medium text-primary-600 hover:underline dark:text-primary-400">Download</button>
                                @if ($backups->count() > 1)
                                    <button type="button" wire:click="deleteBackup(@js($backup->path()))" wire:confirm="Delete this backup?" class="ms-4 font-medium text-danger-600 hover:underline dark:text-danger-400">Delete</button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <details class="rounded-xl bg-gray-50 px-4 py-3 text-sm text-gray-600 dark:bg-white/5 dark:text-gray-300">
        <summary class="cursor-pointer font-medium text-gray-950 dark:text-white">How to restore a backup</summary>
        <ol class="mt-2 list-decimal space-y-1 ps-5">
            <li>Download the backup and open the zip (it asks for the backup password if you set one).</li>
            <li><b>Database:</b> <code>db-dumps/mysql-technofix.sql.gz</code> — unzip it and import the <code>.sql</code> file in cPanel → phpMyAdmin → Import (into an empty database).</li>
            <li><b>Photos:</b> copy the <code>storage/app/public</code> folder back to the same place on the server.</li>
            <li>Keep the site’s <code>.env</code> file (especially <code>APP_KEY</code>) safe separately — it isn’t in the backup, and saved passwords such as the Facebook token can only be read with it.</li>
        </ol>
    </details>
</div>
