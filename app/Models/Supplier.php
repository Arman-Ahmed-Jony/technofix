<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Who TechnoFix buys stock from (Admin → Inventory → Suppliers). */
#[Fillable(['name', 'contact_person', 'phone', 'email', 'address', 'notes', 'is_active'])]
class Supplier extends Model
{
    use LogsActivity;

    protected $attributes = ['is_active' => true];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(StockReceipt::class);
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
