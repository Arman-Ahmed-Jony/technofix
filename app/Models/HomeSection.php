<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * One block of the home page (Admin → Content → Home page). What each type shows is set in `settings`
 * (see App\Support\HomePage for how they are rendered).
 */
#[Fillable(['type', 'eyebrow', 'title', 'subtitle', 'body', 'settings', 'is_active', 'sort'])]
class HomeSection extends Model
{
    use LogsActivity;

    /** type => [label, icon, what it shows] */
    public const TYPES = [
        'hero' => ['Hero slider', 'heroicon-o-rectangle-group', 'The slides from Content → Banners, with the Exclusive Zone brand cards beside them.'],
        'ticker' => ['Scrolling notice', 'heroicon-o-megaphone', 'A line of short messages that scrolls continuously — holiday opening hours, a running offer, delivery news.'],
        'categories' => ['Category tiles', 'heroicon-o-squares-2x2', 'A row of category icons.'],
        'departments' => ['Departments', 'heroicon-o-view-columns', 'Four big department cards with live product counts and “from” prices.'],
        'deals' => ['Deals', 'heroicon-o-fire', 'Products marked “Hot deal”, with an end-of-day countdown.'],
        'product_tabs' => ['Product tabs', 'heroicon-o-queue-list', 'Tabs such as Featured / New arrivals / Best sellers.'],
        'pc_builder' => ['PC Builder promo', 'heroicon-o-cpu-chip', 'The “how it works” block that leads to the PC Builder.'],
        'category_row' => ['Category products', 'heroicon-o-rectangle-stack', 'A scrolling row of products from one category.'],
        'services' => ['Repair & services', 'heroicon-o-wrench-screwdriver', 'Cards for the services in Content → Repair & services.'],
        'brands' => ['Brand strip', 'heroicon-o-tag', 'A moving strip of the brands marked “featured”.'],
        'about' => ['About text', 'heroicon-o-document-text', 'A short text about the shop (good for search engines).'],
    ];

    /** Sources for product tabs. */
    public const PRODUCT_SOURCES = ['featured' => 'Featured', 'new' => 'New arrivals', 'best' => 'Best sellers', 'deal' => 'Hot deals'];

    protected function casts(): array
    {
        return ['settings' => 'array', 'is_active' => 'boolean'];
    }

    public function setting(string $key, mixed $default = null): mixed
    {
        return data_get($this->settings, $key, $default);
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type][0] ?? $this->type;
    }
}
