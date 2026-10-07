<?php

namespace App\Services;

use App\Enums\WarrantyStatus;
use App\Mail\WarrantyUpdate;
use App\Models\WarrantyClaim;
use App\Models\WarrantyClaimEvent;
use App\Settings\WarrantySettings;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Mail;

/**
 * Warranty claims: what customers see when they look up their mobile number, and the update trail (+ emails) staff
 * create by changing a claim's status or its note for the customer.
 */
class WarrantyDesk
{
    public function __construct(private WarrantySettings $settings) {}

    /**
     * A customer's claims, open ones first, newest first.
     *
     * @return Collection<int, WarrantyClaim>
     */
    public function lookup(string $phone): Collection
    {
        $open = collect(WarrantyStatus::cases())->filter->isOpen()->map(fn (WarrantyStatus $s) => "'{$s->value}'")->join(',');

        return WarrantyClaim::query()->forCustomer($phone)
            ->with(['events' => fn ($query) => $query->oldest('id')])
            ->orderByRaw("case when status in ({$open}) then 0 else 1 end")
            ->orderByDesc('received_on')->orderByDesc('id')
            ->limit(50)
            ->get();
    }

    /**
     * Call after staff create or save a claim: records the new status / note for the customer's timeline and emails
     * them when they gave an email (and the owner hasn't switched emails off). Nothing happens if neither changed.
     */
    public function record(WarrantyClaim $claim, bool $notify = true, bool $created = false): ?WarrantyClaimEvent
    {
        $noteChanged = $created ? filled($claim->customer_note) : $claim->wasChanged('customer_note');

        if (! $created && ! $claim->wasChanged('status') && ! $noteChanged) {
            return null;
        }

        $event = $claim->events()->create([
            'status' => $claim->status,
            'note' => $noteChanged ? $claim->customer_note : null,
            'user_id' => auth('web')->id(),
        ]);

        if ($notify && $this->settings->emails_enabled && $claim->canEmail()) {
            Mail::to($claim->customer_email)->queue(new WarrantyUpdate($claim, $event, $created));
            $event->update(['emailed_at' => now()]);
        }

        return $event;
    }

    /** Whether saving with these values would email the customer (for the form's hint). */
    public function wouldEmail(?string $email, bool $internal): bool
    {
        return $this->settings->emails_enabled && ! $internal && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}
