<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Own buying-guide text, questions and SEO for one category + brand page, e.g. /laptop/dell.
 */
#[Fillable(['category_id', 'brand_id', 'content', 'faqs', 'meta_title', 'meta_description'])]
class CategoryBrandPage extends Model
{
    protected function casts(): array
    {
        return ['faqs' => 'array'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function url(): ?string
    {
        return $this->category && $this->brand ? route('catalog.brand', [$this->category->slug, $this->brand->slug]) : null;
    }
}
