<?php

namespace App\Filament\Resources\Orders;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Filament\Resources\Orders\Schemas\OrderItemsFields;
use App\Http\Controllers\Admin\PrintOrdersController;
use App\Models\Order;
use App\Services\OrderService;
use App\Services\PaymentService;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Js;
use Livewire\Component;

/**
 * Status actions shared by the order list and the order page.
 */
class OrderActions
{
    /** One click to the usual next step: New → Confirmed → Packing → Shipped → Delivered. */
    public static function advance(): Action
    {
        return Action::make('advance')
            ->label(fn (Order $record) => 'Mark '.strtolower($record->status->next()?->getLabel() ?? ''))
            ->icon(fn (Order $record) => $record->status->next()?->getIcon())
            ->color('primary')
            ->visible(fn (Order $record) => $record->status->next() !== null)
            ->requiresConfirmation()
            ->modalHeading(fn (Order $record) => "Mark {$record->number} as ".strtolower($record->status->next()?->getLabel() ?? '').'?')
            ->modalDescription(null)
            ->schema([
                Textarea::make('note')->label('Note (optional)')->rows(2)->maxLength(500),
            ])
            ->action(function (Order $record, array $data, OrderService $orders) {
                $status = $record->status->next();
                $orders->changeStatus($record, $status, $data['note'] ?? null, auth()->user());

                Notification::make()->success()->title("{$record->number} marked ".strtolower($status->getLabel()))->send();
            });
    }

    /** Any status, e.g. cancel or return (stock is given back automatically). */
    public static function changeStatus(): Action
    {
        return Action::make('changeStatus')
            ->label('Change status')
            ->icon('heroicon-o-arrow-path')
            ->color('gray')
            ->modalWidth('md')
            ->fillForm(fn (Order $record) => ['status' => $record->status])
            ->schema([
                Select::make('status')
                    ->options(OrderStatus::class)
                    ->required()
                    ->selectablePlaceholder(false)
                    ->helperText('Cancelling or returning puts the items back in stock.'),
                Textarea::make('note')
                    ->label('Note (optional)')
                    ->placeholder('e.g. Customer asked to cancel, courier: Steadfast #123')
                    ->rows(2)
                    ->maxLength(500),
            ])
            ->action(function (Order $record, array $data, OrderService $orders) {
                $status = $data['status'] instanceof OrderStatus ? $data['status'] : OrderStatus::from($data['status']);

                if ($status === $record->status) {
                    return;
                }

                $orders->changeStatus($record, $status, $data['note'] ?? null, auth()->user());

                Notification::make()->success()->title("{$record->number}: ".$status->getLabel())->send();
            });
    }

    /** Change products, quantities, prices, delivery or discount before the order ships (stock moves by the difference). */
    public static function editItems(): Action
    {
        return Action::make('editItems')
            ->label('Edit items & charges')
            ->icon('heroicon-o-pencil-square')
            ->color('gray')
            ->modalWidth('5xl')
            ->visible(fn (Order $record) => OrderService::itemsEditable($record) && $record->items->every(fn ($item) => $item->product_id))
            ->fillForm(fn (Order $record) => [
                'items' => $record->items->map(fn ($item) => ['product_id' => $item->product_id, 'color' => $item->color, 'price' => $item->price, 'qty' => $item->qty])->all(),
                'delivery_code' => $record->delivery_code,
                'delivery_fee' => $record->delivery_fee,
                'discount' => $record->discount,
            ])
            ->schema([OrderItemsFields::items(), ...OrderItemsFields::charges()])
            ->action(function (Order $record, array $data, OrderService $orders) {
                $orders->updateItems($record, $data, auth()->user());

                Notification::make()->success()->title("{$record->number} updated — total BDT ".number_format($record->refresh()->total))->send();
            });
    }

    /** Money received outside the checkout (a bKash advance, cash at the shop…) or a refund. */
    public static function recordPayment(): Action
    {
        return Action::make('recordPayment')
            ->label('Record payment')
            ->icon('heroicon-o-banknotes')
            ->color('gray')
            ->modalWidth('lg')
            ->modalDescription(fn (Order $record) => 'Total BDT '.number_format($record->total).' · paid BDT '.number_format($record->paid_amount).' · still due BDT '.number_format($record->amountDue()))
            ->fillForm(fn (Order $record) => ['kind' => 'payment', 'method' => PaymentMethod::Bkash, 'amount' => $record->amountDue() ?: null, 'paid_at' => now()])
            ->schema([
                ToggleButtons::make('kind')
                    ->hiddenLabel()
                    ->options(['payment' => 'Payment received', 'refund' => 'Refund'])
                    ->colors(['payment' => 'success', 'refund' => 'danger'])
                    ->inline()
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn (Set $set, ?string $state, Order $record) => $set('amount', ($state === 'refund' ? $record->paid_amount : $record->amountDue()) ?: null)),
                Select::make('method')->options(PaymentMethod::class)->required()->selectablePlaceholder(false),
                TextInput::make('amount')
                    ->numeric()
                    ->integer()
                    ->required()
                    ->minValue(1)
                    ->maxValue(fn (Get $get, Order $record) => $get('kind') === 'refund' ? max(1, $record->paid_amount) : null)
                    ->prefix('BDT'),
                TextInput::make('trx_id')
                    ->label('Transaction ID / reference')
                    ->maxLength(80)
                    ->helperText('bKash / Nagad TrxID, bank reference or receipt number.'),
                DateTimePicker::make('paid_at')->label('Date')->seconds(false)->required(),
                TextInput::make('note')->maxLength(255)->placeholder('e.g. Delivery charge paid in advance'),
            ])
            ->action(function (Order $record, array $data, PaymentService $payments) {
                $method = $data['method'] instanceof PaymentMethod ? $data['method'] : PaymentMethod::from($data['method']);
                $refund = $data['kind'] === 'refund';

                $payments->record($record, $method, (int) $data['amount'], [
                    'is_refund' => $refund,
                    'trx_id' => $data['trx_id'] ?? null,
                    'note' => $data['note'] ?? null,
                    'paid_at' => $data['paid_at'],
                ], auth()->user());

                Notification::make()->success()
                    ->title(($refund ? 'Refund' : 'Payment').' of BDT '.number_format((int) $data['amount']).' recorded')
                    ->body('Payment: '.$record->payment_status->getLabel().' · still due BDT '.number_format($record->amountDue()))
                    ->send();
            });
    }

    public static function print(string $type = 'invoice'): Action
    {
        return Action::make('print'.ucfirst($type))
            ->label(PrintOrdersController::TYPES[$type])
            ->icon($type === 'invoice' ? 'heroicon-o-document-text' : 'heroicon-o-clipboard-document-list')
            ->color('gray')
            ->url(fn (Order $record) => PrintOrdersController::url($record, $type), shouldOpenInNewTab: true);
    }

    /** Opens one print page with every selected order (one per sheet). */
    public static function bulkPrint(string $type = 'invoice'): BulkAction
    {
        return BulkAction::make('bulkPrint'.ucfirst($type))
            ->label('Print '.strtolower(PrintOrdersController::TYPES[$type]).'s')
            ->icon($type === 'invoice' ? 'heroicon-o-document-text' : 'heroicon-o-clipboard-document-list')
            ->action(fn (Collection $records, Component $livewire) => $livewire->js('window.open('.Js::from(PrintOrdersController::url($records, $type)).', "_blank")'))
            ->deselectRecordsAfterCompletion();
    }

    public static function call(): Action
    {
        return Action::make('call')
            ->icon('heroicon-o-phone')
            ->color('gray')
            ->url(fn (Order $record) => $record->telHref());
    }

    public static function whatsapp(): Action
    {
        return Action::make('whatsapp')
            ->label('WhatsApp')
            ->icon('heroicon-o-chat-bubble-left-right')
            ->color('success')
            ->url(fn (Order $record) => $record->whatsappUrl(), shouldOpenInNewTab: true);
    }
}
