<?php

namespace App\Jobs;

use App\Support\Backups;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

/**
 * "Back up now" (Admin → Settings → Backups): runs a full backup on the queue, so the page doesn't wait for it.
 */
class RunBackup implements ShouldQueue
{
    use Queueable;

    public int $timeout = 1800;

    public int $tries = 1;

    public function handle(): void
    {
        Cache::put(Backups::RUNNING, true, now()->addMinutes(30));

        try {
            Artisan::call('backup:run');
        } finally {
            Cache::forget(Backups::RUNNING);
        }
    }
}
