<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Sends an address that no longer exists to a new one (Admin → Content → Redirects). Only used when nothing else
 * answers the address (App\Support\NotFound), so a live page is never redirected away.
 */
#[Fillable(['from_path', 'to_url', 'status', 'is_active', 'note'])]
class Redirect extends Model
{
    use LogsActivity;

    public const STATUSES = [301 => 'Permanent (301)', 302 => 'Temporary (302)'];

    protected $attributes = ['status' => 301, 'is_active' => true];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_prefix' => 'boolean',
            'last_hit_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Redirect $redirect) {
            $redirect->from_path = self::normalize($redirect->from_path);
            $redirect->to_url = trim($redirect->to_url);
            $redirect->is_prefix = str_ends_with($redirect->from_path, '*');
        });

        // Redirected now, so no longer a broken link.
        static::saved(fn (Redirect $redirect) => NotFoundUrl::query()
            ->when($redirect->is_prefix,
                fn ($query) => $query->where('path', 'like', addcslashes(rtrim($redirect->from_path, '*'), '%_\\').'%'),
                fn ($query) => $query->where('path', $redirect->from_path))
            ->delete());
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * "https://old-site.com/Product/Asus-X515/?ID=5" → "/product/asus-x515?id=5". Addresses are compared decoded and in
     * lower case, without the trailing slash.
     */
    public static function normalize(?string $address): string
    {
        $address = trim((string) $address);
        $parts = parse_url(preg_match('~^[a-z][a-z0-9+.-]*://~i', $address) ? $address : '/'.ltrim($address, '/')) ?: [];
        $path = Str::lower(rawurldecode($parts['path'] ?? '/'));
        $path = $path === '/' ? '/' : rtrim($path, '/');
        $query = Str::lower(rawurldecode($parts['query'] ?? ''));

        return ($path === '' ? '/' : $path).($query !== '' ? '?'.$query : '');
    }

    /**
     * Where a request should go, or null. Exact matches win (with the query string first), then the longest "…*" rule.
     *
     * @return array{0: Redirect, 1: string}|null the rule and the target (query string kept for path-only rules)
     */
    public static function match(string $pathWithQuery, string $query): ?array
    {
        $path = Str::before($pathWithQuery, '?');
        $exact = self::query()->active()->whereIn('from_path', array_unique([$pathWithQuery, $path]))->get()
            ->sortByDesc(fn (Redirect $r) => strlen($r->from_path))
            ->first();

        if ($exact) {
            $target = $exact->to_url;

            return [$exact, str_contains($exact->from_path, '?') ? $target : self::withQuery($target, $query)];
        }

        $rule = self::query()->active()->where('is_prefix', true)->get()
            ->filter(fn (Redirect $r) => str_starts_with($path, rtrim($r->from_path, '*')) && strlen($path) > strlen(rtrim($r->from_path, '*')))
            ->sortByDesc(fn (Redirect $r) => strlen($r->from_path))
            ->first();

        return $rule ? [$rule, self::withQuery(str_replace('*', substr($path, strlen(rtrim($rule->from_path, '*'))), $rule->to_url), $query)] : null;
    }

    /** An address moved (a slug was changed): redirect the old one, and keep earlier redirects pointing at the new one. */
    public static function moved(string $from, string $to, bool $withChildren = false): void
    {
        $from = self::normalize($from);
        $to = self::normalize($to);

        if ($from === $to) {
            return;
        }

        // No chains: redirects that pointed at the old address now point at the new one…
        self::where('to_url', $from)->update(['to_url' => $to]);
        self::where('to_url', $from.'/*')->update(['to_url' => $to.'/*']);
        // …and the new address is live again, so a redirect away from it can go (moving back and forth can't loop).
        self::whereIn('from_path', [$to, $to.'/*'])->delete();

        self::updateOrCreate(['from_path' => $from], ['to_url' => $to, 'status' => 301, 'is_active' => true, 'note' => 'Address changed']);

        if ($withChildren) {
            self::updateOrCreate(['from_path' => $from.'/*'], ['to_url' => $to.'/*', 'status' => 301, 'is_active' => true, 'note' => 'Address changed']);
        }
    }

    public function recordHit(): void
    {
        DB::table($this->getTable())->where('id', $this->id)->update(['hits' => DB::raw('hits + 1'), 'last_hit_at' => now()]);
    }

    private static function withQuery(string $target, string $query): string
    {
        return $query !== '' && ! str_contains($target, '?') ? $target.'?'.$query : $target;
    }
}
