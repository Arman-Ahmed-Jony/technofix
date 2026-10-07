<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use App\Support\Deals;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A flash sale / Happy Hour: its products sell at their deal price while it runs (Admin → Sales → Campaigns).
 */
#[Fillable(['name', 'headline', 'badge', 'starts_at', 'ends_at', 'is_active', 'show_on_home'])]
class Campaign extends Model
{
    use LogsActivity;

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'is_active' => 'boolean', 'show_on_home' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::saved(fn () => Deals::flush());
        static::deleted(fn () => Deals::flush());
    }

    #[Scope]
    protected function live(Builder $query): void
    {
        $query->where('is_active', true)->where('starts_at', '<=', now())->where('ends_at', '>', now());
    }

    #[Scope]
    protected function upcoming(Builder $query): void
    {
        $query->where('is_active', true)->where('starts_at', '>', now());
    }

    public function items(): HasMany
    {
        return $this->hasMany(CampaignProduct::class)->orderBy('sort')->orderBy('id');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'campaign_products')->withPivot('deal_price', 'sort')->orderByPivot('sort');
    }

    /** Live, Scheduled, Ended or Off — for the admin list. */
    public function state(): string
    {
        return match (true) {
            ! $this->is_active => 'Off',
            $this->starts_at->isFuture() => 'Scheduled',
            $this->ends_at->isPast() => 'Ended',
            default => 'Live',
        };
    }
}
