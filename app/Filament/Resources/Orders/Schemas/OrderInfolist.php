<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Enums\TransactionStatus;
use App\Filament\Resources\Customers\CustomerResource;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Support\Warranty;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\TextSize;

class OrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        $money = fn (?int $amount) => 'BDT '.number_format((int) $amount); // the admin font has no ৳

        return $schema->components([
            Grid::make(['default' => 1, 'lg' => 3])
                ->columnSpanFull()
                ->schema([
                    Grid::make(1)
                        ->columnSpan(['lg' => 2])
                        ->schema([
                            Section::make('Items')
                                ->schema([
                                    RepeatableEntry::make('items')
                                        ->hiddenLabel()
                                        ->table([
                                            TableColumn::make('Product'),
                                            TableColumn::make('Price')->width('130px'),
                                            TableColumn::make('Qty')->width('70px'),
                                            TableColumn::make('Total')->width('130px'),
                                        ])
                                        ->schema([
                                            TextEntry::make('name')
                                                ->state(fn (OrderItem $record) => $record->label())
                                                ->weight('medium')
                                                ->helperText(fn (OrderItem $record) => collect([$record->sku, Warranty::label($record->warranty)])->filter()->join(' · '))
                                                ->url(fn (OrderItem $record) => $record->product && ! $record->product->trashed() ? $record->product->url : null, shouldOpenInNewTab: true),
                                            TextEntry::make('price')->formatStateUsing(fn ($state) => $money($state)),
                                            TextEntry::make('qty'),
                                            TextEntry::make('total')->formatStateUsing(fn ($state) => $money($state))->weight('medium'),
                                        ]),
                                    Grid::make(['default' => 2, 'md' => 4])->schema([
                                        TextEntry::make('subtotal')->formatStateUsing(fn ($state) => $money($state)),
                                        TextEntry::make('delivery_fee')->label('Delivery')->formatStateUsing(fn ($state) => $state ? $money($state) : 'Free'),
                                        TextEntry::make('discount')
                                            ->formatStateUsing(fn ($state) => $state ? '− '.$money($state) : '—')
                                            ->helperText(fn (Order $record) => $record->coupon_code ? 'Coupon '.$record->coupon_code : null),
                                        TextEntry::make('total')
                                            ->label(fn (Order $record) => $record->paid_amount ? 'Total' : 'Total to collect')
                                            ->formatStateUsing(fn ($state) => $money($state))
                                            ->helperText(fn (Order $record) => $record->paid_amount ? 'Paid '.$money($record->paid_amount).' · to collect '.$money($record->amountDue()) : null)
                                            ->weight('bold')
                                            ->size(TextSize::Large),
                                    ]),
                                ]),

                            // Payments and refunds (App\Services\PaymentService); added with "Record payment" or on delivery.
                            Section::make('Payments')
                                ->visible(fn (Order $record) => $record->payments()->exists())
                                ->schema([
                                    RepeatableEntry::make('payments')
                                        ->hiddenLabel()
                                        ->table([
                                            TableColumn::make('Date')->width('170px'),
                                            TableColumn::make('Method'),
                                            TableColumn::make('Amount')->width('130px'),
                                            TableColumn::make('Reference'),
                                        ])
                                        ->schema([
                                            TextEntry::make('paid_at')
                                                ->dateTime('d M Y, h:i A')
                                                ->helperText(fn (Payment $record) => $record->user?->name),
                                            TextEntry::make('method')
                                                ->helperText(fn (Payment $record) => $record->status === TransactionStatus::Completed ? null : $record->status->getLabel()),
                                            TextEntry::make('amount')
                                                ->formatStateUsing(fn ($state, Payment $record) => ($record->is_refund ? '− ' : '').$money($state))
                                                ->helperText(fn (Payment $record) => $record->is_refund ? 'Refund' : null)
                                                ->color(fn (Payment $record) => $record->is_refund ? 'danger' : null)
                                                ->weight('medium'),
                                            TextEntry::make('trx_id')
                                                ->placeholder('—')
                                                ->copyable()
                                                ->fontFamily('mono')
                                                ->helperText(fn (Payment $record) => $record->note),
                                        ]),
                                ]),

                            Section::make('History')
                                ->schema([
                                    RepeatableEntry::make('history')
                                        ->hiddenLabel()
                                        ->contained(false)
                                        ->schema([
                                            TextEntry::make('to_status')
                                                ->hiddenLabel()
                                                ->badge()
                                                ->helperText(fn (OrderStatusHistory $record) => collect([
                                                    $record->created_at?->format('d M Y, h:i A'),
                                                    $record->user?->name ?? ($record->from_status ? null : 'Customer'),
                                                    $record->note,
                                                ])->filter()->join(' · ')),
                                        ]),
                                ]),
                        ]),

                    Grid::make(1)
                        ->columnSpan(1)
                        ->schema([
                            Section::make('Status')
                                ->columns(2)
                                ->schema([
                                    TextEntry::make('status')->badge(),
                                    TextEntry::make('payment_status')->label('Payment')->badge(),
                                    TextEntry::make('created_at')->label('Placed')->dateTime('d M Y, h:i A')->columnSpanFull(),
                                    TextEntry::make('source')->badge()->color('gray'),
                                    TextEntry::make('payment_method')->label('Pay by'),
                                ]),
                            Section::make('Customer')
                                ->schema([
                                    TextEntry::make('name')->hiddenLabel()->weight('bold'),
                                    TextEntry::make('phone')
                                        ->hiddenLabel()
                                        ->icon('heroicon-o-phone')
                                        ->copyable()
                                        ->url(fn (Order $record) => $record->telHref()),
                                    TextEntry::make('email')->hiddenLabel()->icon('heroicon-o-envelope')->copyable()->visible(fn (Order $record) => filled($record->email)),
                                    TextEntry::make('customer.name')
                                        ->hiddenLabel()
                                        ->icon('heroicon-o-user-circle')
                                        ->formatStateUsing(fn () => 'Customer account')
                                        ->color('primary')
                                        ->url(fn (Order $record) => $record->customer_id ? CustomerResource::getUrl('edit', ['record' => $record->customer_id]) : null)
                                        ->visible(fn (Order $record) => (bool) $record->customer_id),
                                ]),
                            Section::make('Delivery')
                                ->schema([
                                    TextEntry::make('delivery_label')
                                        ->hiddenLabel()
                                        ->weight('medium')
                                        ->helperText(fn (Order $record) => $record->delivery_eta),
                                    TextEntry::make('address')
                                        ->hiddenLabel()
                                        ->state(fn (Order $record) => $record->fullAddress())
                                        ->icon('heroicon-o-map-pin')
                                        ->copyable(),
                                    TextEntry::make('courier')->placeholder('—')->inlineLabel(),
                                    TextEntry::make('tracking_code')->label('Tracking no.')->placeholder('—')->copyable()->inlineLabel(),
                                ]),
                            Section::make('Notes')
                                ->visible(fn (Order $record) => filled($record->customer_note) || filled($record->staff_note))
                                ->schema([
                                    TextEntry::make('customer_note')->label('From the customer')->visible(fn (Order $record) => filled($record->customer_note)),
                                    TextEntry::make('staff_note')->label('Staff note')->visible(fn (Order $record) => filled($record->staff_note)),
                                ]),
                            Section::make('Where they came from')
                                ->collapsible()
                                ->collapsed()
                                ->schema([
                                    KeyValueEntry::make('attribution')
                                        ->hiddenLabel()
                                        ->keyLabel('Tag')
                                        ->valueLabel('Value')
                                        ->placeholder('Direct visit / unknown'),
                                    TextEntry::make('ip')->label('IP address')->placeholder('—')->inlineLabel(),
                                ]),
                        ]),
                ]),
        ]);
    }
}
