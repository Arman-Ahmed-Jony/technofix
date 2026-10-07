<?php

namespace App\Models;

use App\Enums\BuilderRole;
use App\Models\Concerns\LogsActivity;
use App\Support\Catalog;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A PC Builder slot (e.g. "Processor"): the products that can go in it come from its categories (and their
 * sub-categories), optionally narrowed by one spec field. Managed in Admin → Catalog → PC Builder.
 */
#[Fillable(['name', 'description', 'icon', 'section', 'role', 'category_ids', 'filter_attribute_id', 'filter_values', 'is_required', 'max_qty', 'is_active', 'sort'])]
class PcBuilderSlot extends Model
{
    use LogsActivity;

    public const SECTIONS = ['core' => 'Core components', 'peripheral' => 'Peripherals & others'];

    protected function casts(): array
    {
        return [
            'role' => BuilderRole::class,
            'category_ids' => 'array',
            'filter_values' => 'array',
            'is_required' => 'boolean',
            'is_active' => 'boolean',
            'max_qty' => 'integer',
        ];
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('sort')->orderBy('id');
    }

    public function filterAttribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class, 'filter_attribute_id');
    }

    /**
     * Category ids this slot draws from, including active sub-categories.
     *
     * @return list<int>
     */
    public function allCategoryIds(): array
    {
        $catalog = app(Catalog::class);

        return collect($this->category_ids ?? [])
            ->map(fn ($id) => $catalog->categoryById((int) $id))
            ->filter()
            ->flatMap(fn ($category) => $catalog->descendantIds($category))
            ->unique()->values()->all();
    }

    /** Published products that fit this slot (with what product cards need). */
    public function products(): Builder
    {
        $values = collect($this->filter_values ?? [])->map(fn ($v) => Attribute::normalise((string) $v, $this->filterAttribute?->unit))->filter()->values()->all();

        return app(Catalog::class)->products()
            ->whereIn('category_id', $this->allCategoryIds() ?: [0])
            ->when($this->filter_attribute_id && $values, fn (Builder $q) => $q->whereHas('specValues', fn (Builder $s) => $s
                ->where('attribute_id', $this->filter_attribute_id)
                ->whereIn('value', $values)));
    }
}
