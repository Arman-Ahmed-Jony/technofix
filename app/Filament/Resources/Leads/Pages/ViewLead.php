<?php

namespace App\Filament\Resources\Leads\Pages;

use App\Enums\LeadStatus;
use App\Filament\Actions\BlockPhoneAction;
use App\Filament\Resources\Leads\LeadResource;
use App\Filament\Resources\Orders\OrderResource;
use App\Services\IncompleteCheckouts;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewLead extends ViewRecord
{
    protected static string $resource = LeadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('call')
                ->icon('heroicon-o-phone')
                ->color('gray')
                ->url(fn () => 'tel:'.$this->record->phone),
            Action::make('whatsapp')
                ->label('WhatsApp')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->color('success')
                ->url(fn () => 'https://wa.me/88'.$this->record->phone.$this->whatsappText(), shouldOpenInNewTab: true),
            // Incomplete checkouts: take the order by phone with their cart and address filled in.
            Action::make('createOrder')
                ->label('Create order')
                ->icon('heroicon-o-shopping-bag')
                ->visible(fn () => $this->record->checkoutDraft !== null && ! in_array($this->record->status, [LeadStatus::Converted, LeadStatus::Lost], true))
                ->url(fn () => OrderResource::getUrl('create', ['lead' => $this->record->id])),
            // PC Builder enquiries: open the customer's build (today's prices + compatibility).
            Action::make('openBuild')
                ->label('Open build')
                ->icon('heroicon-o-cpu-chip')
                ->color('gray')
                ->visible(fn () => filled($this->record->meta['Build link'] ?? null))
                ->url(fn () => $this->record->meta['Build link'] ?? null, shouldOpenInNewTab: true),
            // Price requests / stock alerts: the product they asked about.
            Action::make('openProduct')
                ->label('Open product')
                ->icon('heroicon-o-cube')
                ->color('gray')
                ->visible(fn () => filled($this->record->meta['Product link'] ?? null))
                ->url(fn () => $this->record->meta['Product link'] ?? null, shouldOpenInNewTab: true),
            Action::make('markContacted')
                ->label('Mark contacted')
                ->icon('heroicon-o-check-circle')
                ->visible(fn () => $this->record->status === LeadStatus::New)
                ->action(function () {
                    $this->record->update(['status' => LeadStatus::Contacted, 'contacted_at' => now()]);
                    $this->refreshFormData(['status', 'contacted_at']);
                }),
            EditAction::make(),
            ActionGroup::make([BlockPhoneAction::make()])->icon('heroicon-o-ellipsis-vertical')->color('gray'),
        ];
    }

    /** Incomplete checkouts start the chat with the follow-up text from Settings → Stock & orders. */
    protected function whatsappText(): string
    {
        $draft = $this->record->checkoutDraft;

        return $draft ? '?text='.rawurlencode(app(IncompleteCheckouts::class)->message($draft)) : '';
    }
}
