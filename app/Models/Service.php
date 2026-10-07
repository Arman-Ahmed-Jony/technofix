<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use App\Models\Concerns\RedirectsWhenMoved;
use App\Support\SiteNav;
use Filament\Forms\Components\RichEditor\RichContentRenderer;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Repair / IT service (no prices — customers call, WhatsApp or send a request).
 */
#[Fillable(['name', 'slug', 'icon', 'summary', 'content', 'sort', 'is_active', 'meta_title', 'meta_description'])]
class Service extends Model
{
    use LogsActivity, RedirectsWhenMoved;

    /** Tabler icons offered in the admin (key => label). */
    public const ICONS = [
        'device-laptop' => 'Laptop',
        'device-desktop' => 'Desktop / monitor',
        'device-cctv' => 'CCTV camera',
        'router' => 'Router / network',
        'printer' => 'Printer',
        'database' => 'Storage / data',
        'cpu' => 'Processor / PC build',
        'tool' => 'Tools / general repair',
        'shield-check' => 'Security',
        'fingerprint' => 'Access control',
        'device-mobile' => 'Mobile',
        'plug-connected' => 'Cabling',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'sort' => 'integer'];
    }

    protected static function booted(): void
    {
        static::saved(fn () => SiteNav::flush());
        static::deleted(fn () => SiteNav::flush());
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('sort')->orderBy('id');
    }

    public function url(): string
    {
        return route('services.show', $this->slug);
    }

    public function html(): string
    {
        return filled($this->content) ? RichContentRenderer::make($this->content)->toHtml() : '';
    }

    public static function pathForSlug(string $slug): string
    {
        return '/services/'.$slug;
    }
}
