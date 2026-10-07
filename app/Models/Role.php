<?php

namespace App\Models;

use App\Enums\StaffArea;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A staff role: the admin areas (StaffArea) its members can open. The owner role has full access and can't change.
 */
#[Fillable(['name', 'description', 'areas', 'sort'])]
class Role extends Model
{
    use LogsActivity;

    protected function casts(): array
    {
        return [
            'areas' => 'array',
            'is_owner' => 'boolean',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function allows(StaffArea $area): bool
    {
        return $this->is_owner || in_array($area->value, $this->areas ?? [], true);
    }

    /**
     * @return list<string> area labels, or "Full access"
     */
    public function areaLabels(): array
    {
        return $this->is_owner
            ? ['Full access']
            : collect($this->areas ?? [])->map(fn (string $area) => StaffArea::tryFrom($area)?->label())->filter()->values()->all();
    }
}
