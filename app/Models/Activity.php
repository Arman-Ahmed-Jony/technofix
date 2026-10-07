<?php

namespace App\Models;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/**
 * One entry of the activity log (Admin → Settings → Activity log), written by App\Support\ActivityLog.
 * Kept for a year (`model:prune`).
 */
class Activity extends Model
{
    use Prunable;

    public const UPDATED_AT = null;

    protected $table = 'activity_log';

    protected $guarded = [];

    /** What a record is called in the log (default: the model name in lower case). */
    public const TYPES = [
        Campaign::class => 'flash sale',
        User::class => 'staff account',
        Attribute::class => 'spec field',
        Faq::class => 'FAQ',
        HomeSection::class => 'home page section',
        PcBuilderSlot::class => 'PC Builder slot',
    ];

    protected function casts(): array
    {
        return [
            'changes' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function prunable(): Builder
    {
        return static::where('created_at', '<', now()->subYear());
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    #[Scope]
    protected function about(Builder $query, Model $subject): void
    {
        $query->where('subject_type', $subject::class)->where('subject_id', $subject->getKey());
    }

    public static function typeLabel(?string $type): string
    {
        return match (true) {
            $type === null => '',
            $type === 'settings' => 'settings',
            default => self::TYPES[$type] ?? Str::of(class_basename($type))->snake(' ')->toString(),
        };
    }

    /** "Changed product", "Added coupon", "Signed in" … */
    public function action(): string
    {
        $type = self::typeLabel($this->subject_type);

        return match ($this->event) {
            'created' => 'Added '.$type,
            'updated' => 'Changed '.$type,
            'deleted' => 'Deleted '.$type,
            'settings' => 'Changed settings',
            'imported' => 'Imported products',
            'reordered' => 'Arranged featured products',
            'warranty_imported' => 'Imported warranty list',
            'login' => 'Signed in',
            'backup' => 'Backups',
            'exported' => 'Exported',
            default => Str::headline($this->event).($type ? ' '.$type : ''),
        };
    }

    /** "Price 15,500 → 14,999 · Stock qty 5 → 3" (the first $limit changes, then "+2 more"). */
    public function changesHtml(?int $limit = null): HtmlString
    {
        // getAttribute(): inside the model, $this->changes is Eloquent's own dirty-tracking property.
        $changes = collect($this->getAttribute('changes') ?? []);
        $shown = $limit ? $changes->take($limit) : $changes;

        $html = $shown->map(fn (array $c) => '<span class="text-gray-500 dark:text-gray-400">'.e($c['field']).':</span>&nbsp;'
            .($c['old'] !== null ? '<span class="text-gray-500 line-through decoration-gray-300 dark:text-gray-400">'.e($c['old']).'</span> → ' : '')
            .'<span class="font-medium text-gray-950 dark:text-white">'.e($c['new'] ?? '—').'</span>')
            ->join('<span class="mx-1.5 text-gray-300">·</span>');

        if ($limit && $changes->count() > $limit) {
            $html .= ' <span class="text-gray-400">+'.($changes->count() - $limit).' more</span>';
        }

        return new HtmlString($html);
    }

    /** The record's admin page, when it still exists and this staff member may open it. */
    public function subjectUrl(): ?string
    {
        if (! $this->subject_type || ! $this->subject_id || ! class_exists($this->subject_type) || $this->event === 'deleted') {
            return null;
        }

        $resource = Filament::getPanel('admin')->getModelResource($this->subject_type);
        $record = $resource ? $this->subject_type::query()->find($this->subject_id) : null;

        if (! $record || ! $resource::canAccess()) {
            return null;
        }

        foreach (['view', 'edit'] as $page) {
            if ($resource::hasPage($page)) {
                return $resource::getUrl($page, ['record' => $record]);
            }
        }

        return null;
    }
}
