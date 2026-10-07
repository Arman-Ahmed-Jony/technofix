<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;

/**
 * An address on the site that visitors or crawlers asked for but doesn't exist (Admin → Content → Broken links).
 * Written by App\Support\NotFound; forgotten after 90 days without a visit (`model:prune`, daily).
 */
class NotFoundUrl extends Model
{
    use Prunable;

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }

    public function prunable(): Builder
    {
        return static::where('last_seen_at', '<', now()->subDays(90));
    }

    /** Visits by people (not search engines or other bots). */
    public function peopleHits(): int
    {
        return max(0, $this->hits - $this->bot_hits);
    }
}
