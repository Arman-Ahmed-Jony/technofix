<?php

namespace App\Support;

use App\Models\Activity;
use App\Models\User;
use BackedEnum;
use DateTimeInterface;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * The admin's activity log: what staff added, changed or deleted, their settings changes, imports and sign-ins.
 * Models opt in with Concerns\LogsActivity; only actions by a signed-in staff member are logged (not customers,
 * the queue or the scheduler — orders and stock have their own history).
 */
class ActivityLog
{
    /** Never logged (housekeeping, counters, secrets). */
    public const IGNORED = ['created_at', 'updated_at', 'deleted_at', 'remember_token', 'last_login_at', 'app_authentication_secret',
        'app_authentication_recovery_codes', 'views', 'sold_count', 'tree_path', 'depth', 'fingerprint', 'paid_amount', 'payment_status',
        'stock_deducted', 'hits', 'last_hit_at', 'is_prefix', 'email_verified_at'];

    /** Logged as "changed", without the values. */
    public const HIDDEN = ['password', 'avatar_path'];

    /** Field names people use (everything else: "stock_qty" → "Stock qty"). */
    public const FIELD_LABELS = [
        'avatar_path' => 'Photo',
        'meta_title' => 'SEO title',
        'meta_description' => 'SEO description',
        'is_active' => 'Active',
        'regular_price' => 'Regular price',
        'cost_price' => 'Cost price',
        'stock_qty' => 'Stock quantity',
    ];

    /** Settings groups as their admin pages are called. */
    public const SETTINGS = [
        'store' => 'Store & contact',
        'delivery' => 'Delivery charges',
        'orders' => 'Stock & orders',
        'payments' => 'Payments',
        'seo' => 'SEO & sharing',
        'tracking' => 'Tracking & pixels',
        'pc_builder' => 'PC Builder rules',
        'buying_guide' => 'Buying guide',
    ];

    private static bool $paused = false;

    public static function staff(): ?User
    {
        $user = auth('web')->user();

        return $user instanceof User ? $user : null;
    }

    /** Runs something without logging each change (e.g. an Excel import, logged once as a summary). */
    public static function quietly(callable $callback): mixed
    {
        $paused = self::$paused;
        self::$paused = true;

        try {
            return $callback();
        } finally {
            self::$paused = $paused;
        }
    }

    /**
     * @param  list<array{field: string, old: mixed, new: mixed}>  $changes
     */
    public static function record(string $event, ?Model $subject = null, ?string $label = null, array $changes = [], ?string $type = null, ?User $by = null): void
    {
        if (self::$paused || ! ($user = $by ?? self::staff())) {
            return;
        }

        Activity::create([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'event' => $event,
            'subject_type' => $type ?? ($subject ? $subject::class : null),
            'subject_id' => $subject?->getKey(),
            'subject_label' => Str::limit((string) ($label ?? ($subject ? self::label($subject) : '')), 250, '') ?: null,
            'changes' => $changes ?: null,
            'ip' => request()->ip(),
            'created_at' => now(),
        ]);
    }

    /**
     * What changed on a model that was just saved, in words ("Price: 15,500 → 14,999").
     *
     * @return list<array{field: string, old: mixed, new: mixed}>
     */
    public static function diff(Model $model): array
    {
        return collect($model->getChanges())
            ->keys()
            ->reject(fn (string $field) => in_array($field, [...self::IGNORED, ...($model->activityIgnore ?? [])], true))
            ->map(fn (string $field) => in_array($field, self::HIDDEN, true)
                ? ['field' => self::fieldLabel($field), 'old' => null, 'new' => 'changed']
                : ['field' => self::fieldLabel($field), 'old' => self::value($model, $field, true), 'new' => self::value($model, $field, false)])
            ->reject(fn (array $change) => $change['old'] === $change['new'] && $change['new'] !== 'changed')
            ->values()
            ->all();
    }

    /**
     * Settings saved on an admin page: what changed (secret values only as "changed").
     *
     * @param  Collection<string, mixed>  $new
     * @param  Collection<string, mixed>|null  $old
     * @param  list<string>  $secret
     */
    public static function settingsSaved(string $group, Collection $new, ?Collection $old, array $secret = []): void
    {
        $changes = $new
            ->filter(fn ($value, string $name) => json_encode($value) !== json_encode($old?->get($name)))
            ->map(fn ($value, string $name) => in_array($name, $secret, true) || Str::contains($name, ['token', 'secret', 'password'])
                ? ['field' => self::fieldLabel($name), 'old' => null, 'new' => 'changed']
                : ['field' => self::fieldLabel($name), 'old' => self::display($old?->get($name)), 'new' => self::display($value)])
            ->values()
            ->all();

        if ($changes) {
            self::record('settings', label: self::SETTINGS[$group] ?? Str::headline($group), changes: $changes, type: 'settings');
        }
    }

    /** A record's name at the time: order number, name, title, code… */
    public static function label(Model $model): string
    {
        foreach (['number', 'name', 'title', 'code', 'from_path', 'question', 'heading', 'email'] as $attribute) {
            if (filled($value = $model->getAttribute($attribute)) && is_scalar($value)) {
                return (string) $value;
            }
        }

        return '#'.$model->getKey();
    }

    public static function fieldLabel(string $field): string
    {
        return self::FIELD_LABELS[$field] ?? Str::ucfirst(str_replace('_', ' ', Str::beforeLast($field, '_id') === $field ? $field : Str::beforeLast($field, '_id')));
    }

    /** One value as people read it: names instead of ids, labels instead of codes, Yes/No, dates. */
    public static function display(mixed $value): ?string
    {
        return match (true) {
            $value === null || $value === '' => null,
            is_bool($value) => $value ? 'Yes' : 'No',
            $value instanceof HasLabel => (string) $value->getLabel(),
            $value instanceof BackedEnum => Str::headline((string) $value->value),
            $value instanceof DateTimeInterface => $value->format('d M Y, h:i A'),
            is_array($value) => Str::limit(collect($value)->flatten()->filter(fn ($v) => is_scalar($v))->join(', '), 150),
            is_int($value) || is_float($value) => number_format($value, is_float($value) && floor($value) != $value ? 2 : 0),
            default => Str::limit((string) $value, 150),
        };
    }

    protected static function value(Model $model, string $field, bool $original): ?string
    {
        // A related record ("brand_id" → the brand's name).
        $relation = Str::camel(Str::beforeLast($field, '_id'));
        if (str_ends_with($field, '_id') && method_exists($model, $relation) && ($belongs = $model->{$relation}()) instanceof BelongsTo) {
            $id = $original ? $model->getOriginal($field) : $model->getAttribute($field);
            $related = $id ? $belongs->getRelated()->newQuery()->find($id) : null;

            return $related ? self::label($related) : ($id ? '#'.$id : null);
        }

        $value = $original ? $model->getOriginal($field) : $model->getAttribute($field);

        // Long texts (descriptions, page content): only that they changed.
        if (is_string($value) && Str::length($value) > 150) {
            return $original ? '…' : 'changed';
        }

        return self::display($value);
    }
}
