<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Orders\Schemas\OrderForm;
use App\Models\CheckoutDraft;
use App\Services\OrderService;
use App\Settings\DeliverySettings;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

/** An order taken by staff: phone, Facebook, WhatsApp or walk-in. */
class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;

    protected static ?string $title = 'New order';

    public function form(Schema $schema): Schema
    {
        return OrderForm::create($schema);
    }

    /** From an incomplete-checkout lead (`?lead=`): their cart (today's prices), details and address filled in. */
    protected function afterFill(): void
    {
        $draft = CheckoutDraft::where('lead_id', request()->integer('lead'))->latest('id')->first();

        if (! $draft) {
            return;
        }

        $delivery = app(DeliverySettings::class);
        $items = collect($draft->cartItems())->map(fn (array $item) => ['product_id' => $item['id'], 'color' => $item['color'] ?? null, 'price' => $item['price'], 'qty' => $item['qty']]);
        $option = $delivery->find($draft->delivery_code) ?? collect($delivery->active())->first();

        $this->form->fill([
            ...$this->form->getRawState(),
            'phone' => $draft->phone,
            'name' => $draft->name,
            'email' => $draft->email,
            'items' => $items->all() ?: [['product_id' => null, 'price' => null, 'qty' => 1]],
            'delivery_code' => $option['code'] ?? null,
            'delivery_fee' => $option ? $delivery->feeFor($option, $items->sum(fn (array $item) => $item['price'] * $item['qty'])) : 0,
            'district' => $draft->district,
            'area' => $draft->area,
            'address' => $draft->address,
            'customer_note' => $draft->note,
            'staff_note' => 'Called back after an incomplete checkout.',
        ]);
    }

    protected function handleRecordCreation(array $data): Model
    {
        return app(OrderService::class)->createManual($data, auth()->user());
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Order '.$this->getRecord()->number.' created';
    }
}
