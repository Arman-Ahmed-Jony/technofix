<?php

namespace App\Filament\Resources\WarrantyClaims\Pages;

use App\Filament\Actions\HistoryAction;
use App\Filament\Resources\WarrantyClaims\WarrantyClaimResource;
use App\Services\WarrantyDesk;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditWarrantyClaim extends EditRecord
{
    protected static string $resource = WarrantyClaimResource::class;

    public function getTitle(): string
    {
        return 'Claim #'.$this->record->number;
    }

    protected function getHeaderActions(): array
    {
        return [HistoryAction::make(), DeleteAction::make()];
    }

    /** Defaults only apply on create: tick "Email the customer" here too. */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        return [...$data, 'notify' => true];
    }

    protected function afterSave(): void
    {
        $event = app(WarrantyDesk::class)->record($this->record, (bool) ($this->data['notify'] ?? true));

        if ($event?->emailed_at) {
            Notification::make()->info()->title('The customer will get an email about this update.')->send();
        }

        $this->data['notify'] = true;
    }
}
