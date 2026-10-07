<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Filament\Resources\Orders\OrderActions;
use App\Models\Order;
use App\Services\OrderService;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount('items'))
            ->searchPlaceholder('Order no., name or mobile')
            ->paginated([25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->poll('60s')
            ->columns([
                TextColumn::make('number')
                    ->label('Order')
                    ->searchable()
                    ->weight('medium')
                    ->fontFamily('mono')
                    ->description(fn (Order $record) => $record->created_at->format('d M, h:i A')),
                TextColumn::make('name')
                    ->label('Customer')
                    ->searchable(['name', 'phone'])
                    ->description(fn (Order $record) => $record->phone),
                TextColumn::make('district')
                    ->label('Delivery')
                    ->state(fn (Order $record) => $record->is_pickup ? 'Pickup' : ($record->district ?? '—'))
                    ->description(fn (Order $record) => $record->delivery_label)
                    ->toggleable(),
                TextColumn::make('items_count')->label('Items')->numeric()->alignCenter()->toggleable(),
                TextColumn::make('total')
                    ->formatStateUsing(fn (int $state) => 'BDT '.number_format($state))
                    ->sortable()
                    ->weight('medium'),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('payment_status')->label('Payment')->badge()->toggleable(),
                TextColumn::make('source')->badge()->color('gray')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->label('Placed')->since()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->options(OrderStatus::class)->multiple(),
                SelectFilter::make('payment_status')->label('Payment')->options(PaymentStatus::class),
                SelectFilter::make('source')->options(OrderSource::class),
                Filter::make('placed')
                    ->schema([
                        DatePicker::make('from')->label('Placed from'),
                        DatePicker::make('until')->label('Placed until'),
                    ])
                    ->columns(2)
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['from'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '<=', $date))),
            ])
            ->filtersFormColumns(2)
            ->recordUrl(fn (Order $record) => route('filament.admin.resources.orders.view', $record))
            ->recordActions([
                OrderActions::advance()->button()->size('sm'),
                ActionGroup::make([
                    ViewAction::make(),
                    OrderActions::call(),
                    OrderActions::whatsapp(),
                    OrderActions::changeStatus(),
                    OrderActions::print('invoice'),
                    OrderActions::print('slip'),
                    EditAction::make(),
                ]),
            ])
            ->toolbarActions([
                OrderActions::bulkPrint('slip'),
                OrderActions::bulkPrint('invoice'),
                BulkAction::make('bulkStatus')
                    ->label('Change status')
                    ->icon('heroicon-o-arrow-path')
                    ->schema([
                        Select::make('status')->options(OrderStatus::class)->required(),
                        Textarea::make('note')->label('Note (optional)')->rows(2)->maxLength(500),
                    ])
                    ->action(function (Collection $records, array $data, OrderService $orders) {
                        $status = $data['status'] instanceof OrderStatus ? $data['status'] : OrderStatus::from($data['status']);
                        $records->each(fn (Order $order) => $orders->changeStatus($order, $status, $data['note'] ?? null, auth()->user()));

                        Notification::make()->success()->title($records->count().' orders: '.$status->getLabel())->send();
                    })
                    ->deselectRecordsAfterCompletion(),
            ]);
    }
}
