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
 * Admin-managed static page (About, policies, delivery info…), shown at /page/{slug}.
 */
#[Fillable(['title', 'slug', 'excerpt', 'content', 'footer_group', 'sort', 'is_published', 'meta_title', 'meta_description'])]
class Page extends Model
{
    use LogsActivity, RedirectsWhenMoved;

    public const FOOTER_GROUPS = ['help' => 'Help', 'company' => 'Company'];

    protected function casts(): array
    {
        return ['is_published' => 'boolean', 'sort' => 'integer'];
    }

    protected static function booted(): void
    {
        static::saved(fn () => SiteNav::flush());
        static::deleted(fn () => SiteNav::flush());
    }

    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('is_published', true);
    }

    public function url(): string
    {
        return route('page.show', $this->slug);
    }

    /**
     * Sanitised HTML of the rich-text content (safe to echo unescaped).
     */
    public function html(): string
    {
        return filled($this->content) ? RichContentRenderer::make($this->content)->toHtml() : '';
    }

    public static function pathForSlug(string $slug): string
    {
        return '/page/'.$slug;
    }
}
