<?php

namespace App\Filament\Resources\StockReceipts\Pages;

use App\Filament\Resources\StockReceipts\StockReceiptResource;
use App\Services\InventoryService;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateStockReceipt extends CreateRecord
{
    protected static string $resource = StockReceiptResource::class;

    protected static ?string $title = 'Receive stock';

    protected static bool $canCreateAnother = false;

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()->label('Save & add to stock');
    }

    protected function handleRecordCreation(array $data): Model
    {
        return app(InventoryService::class)->receive($data, auth('web')->user());
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Stock added — '.$this->getRecord()->number;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
