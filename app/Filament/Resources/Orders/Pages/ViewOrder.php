<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Actions\BlockPhoneAction;
use App\Filament\Actions\HistoryAction;
use App\Filament\Resources\Orders\OrderActions;
use App\Filament\Resources\Orders\OrderResource;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    public function getTitle(): string
    {
        return 'Order '.$this->getRecord()->number;
    }

    protected function getHeaderActions(): array
    {
        return [
            HistoryAction::make(),
            OrderActions::call(),
            OrderActions::whatsapp(),
            ActionGroup::make([
                OrderActions::print('invoice'),
                OrderActions::print('slip'),
            ])->button()->label('Print')->icon('heroicon-o-printer')->color('gray'),
            OrderActions::advance()->after(fn () => $this->refreshFormData(['status', 'payment_status', 'paid_amount'])),
            ActionGroup::make([
                OrderActions::recordPayment()->after(fn () => $this->refreshFormData(['payment_status', 'paid_amount'])),
                OrderActions::editItems()->after(fn () => $this->refreshFormData(['subtotal', 'delivery_fee', 'discount', 'total', 'delivery_label', 'delivery_eta', 'payment_status', 'paid_amount'])),
                OrderActions::changeStatus()->after(fn () => $this->refreshFormData(['status', 'payment_status', 'paid_amount'])),
                EditAction::make()->label('Edit details'),
                BlockPhoneAction::make(),
            ])->button()->label('More')->color('gray'),
        ];
    }
}
