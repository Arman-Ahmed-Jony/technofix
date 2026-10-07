<?php

namespace App\Models\Concerns;

use App\Support\ActivityLog;
use Illuminate\Database\Eloquent\Model;

/**
 * Staff additions, changes and deletions of this model go to the activity log (App\Support\ActivityLog).
 * A model can leave fields out with `public array $activityIgnore = [...]`.
 */
trait LogsActivity
{
    protected static function bootLogsActivity(): void
    {
        static::created(fn (Model $model) => ActivityLog::record('created', $model));

        static::updated(function (Model $model) {
            if ($changes = ActivityLog::diff($model)) {
                ActivityLog::record('updated', $model, changes: $changes);
            }
        });

        static::deleted(fn (Model $model) => ActivityLog::record('deleted', $model));
    }
}
