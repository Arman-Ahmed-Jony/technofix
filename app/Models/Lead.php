<?php

namespace App\Models;

use App\Enums\LeadStatus;
use App\Enums\LeadType;
use App\Filament\Widgets\OrderSources;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * An enquiry from the storefront (contact form, repair request, …) followed up in Admin → Leads.
 */
#[Fillable(['type', 'status', 'name', 'phone', 'email', 'subject', 'message', 'meta', 'page_url', 'attribution', 'ip', 'user_agent', 'notes', 'contacted_at'])]
class Lead extends Model
{
    use LogsActivity;

    protected $attributes = ['status' => 'new'];

    protected function casts(): array
    {
        return [
            'type' => LeadType::class,
            'status' => LeadStatus::class,
            'meta' => 'array',
            'attribution' => 'array',
            'contacted_at' => 'datetime',
        ];
    }

    /** Where the customer came from, e.g. "Facebook / Instagram" (see CaptureAttribution). */
    public function source(): string
    {
        return OrderSources::fromAttribution($this->attribution);
    }

    /** Incomplete checkouts: the customer's latest saved checkout (cart, address, "finish your order" link). */
    public function checkoutDraft(): HasOne
    {
        return $this->hasOne(CheckoutDraft::class)->latestOfMany();
    }
}
