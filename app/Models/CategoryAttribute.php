<?php

namespace App\Models;

use App\Support\CatalogCache;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row of a category's spec template.
 */
#[Fillable(['category_id', 'attribute_id', 'group', 'sort', 'is_filterable', 'show_on_card'])]
class CategoryAttribute extends Model
{
    protected $table = 'category_attribute';

    public $timestamps = false;

    protected function casts(): array
    {
        return ['is_filterable' => 'boolean', 'show_on_card' => 'boolean', 'sort' => 'integer'];
    }

    protected static function booted(): void
    {
        static::saved(fn () => CatalogCache::flush());
        static::deleted(fn () => CatalogCache::flush());
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }
}
