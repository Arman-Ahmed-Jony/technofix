<?php

namespace App\Filament\Resources\WarrantyClaims\Tables;

use App\Enums\WarrantyStatus;
use App\Filament\Resources\WarrantyClaims\Schemas\WarrantyClaimForm;
use App\Models\WarrantyClaim;
use App\Services\WarrantyDesk;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class WarrantyClaimsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc') // newest first; NO isn't unique in the old sheet
            ->searchPlaceholder('Search product, serial, customer or phone')
            ->paginated([25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->columns([
                TextColumn::make('number')->label('No')->sortable()->searchable()->color('gray')->width('4rem'),
                TextColumn::make('received_on')->label('Date')->date('d-m-y')->sortable()->placeholder('—'),
                TextColumn::make('customer_name')->label('Customer')
                    ->searchable(['customer_name', 'customer_phone', 'customer_email'])
                    ->weight('medium')
                    ->wrap()
                    ->description(fn (WarrantyClaim $record) => $record->is_internal ? 'Own stock' : ($record->customer_phone ?: 'No mobile number'))
                    ->color(fn (WarrantyClaim $record) => $record->is_internal ? 'gray' : null),
                TextColumn::make('product')->label('Product')
                    ->searchable(['product', 'serial', 'new_serial'])
                    ->wrap()
                    ->lineClamp(2)
                    ->extraAttributes(['style' => 'min-width: 15rem'])
                    ->description(fn (WarrantyClaim $record) => collect([
                        $record->qty > 1 ? $record->qty.' pcs' : null,
                        $record->serial ? 'SN '.$record->serial : null,
                    ])->filter()->join(' · ') ?: null),
                TextColumn::make('vendor')->searchable()->toggleable()->placeholder('—')->wrap(),
                TextColumn::make('claimed_on')->label('Claimed')->date('d-m-y')->sortable()->toggleable()->placeholder('—'),
                TextColumn::make('returned_on')->label('Back')->date('d-m-y')->sortable()->toggleable()->placeholder('—'),
                TextColumn::make('new_serial')->label('New SN')->toggleable(isToggledHiddenByDefault: true)->placeholder('—'),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('status_changed_at')->label('Status since')->since()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->options(WarrantyStatus::class)->multiple(),
                SelectFilter::make('vendor')->options(fn () => collect(WarrantyClaimForm::vendors())->mapWithKeys(fn ($v) => [$v => $v])->all())->searchable(),
                TernaryFilter::make('is_internal')->label('Whose product')
                    ->trueLabel('Our own stock')->falseLabel('Customers’ products')->placeholder('All'),
                TernaryFilter::make('customer_email')->label('Has email')->nullable(),
            ])
            ->recordActions([self::statusAction()]) // a click on the row opens the claim
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('setStatus')
                        ->label('Set status')
                        ->icon('heroicon-o-arrow-path')
                        ->schema([
                            ToggleButtons::make('status')->options(WarrantyStatus::class)->required()->inline(),
                            Toggle::make('notify')->label('Email customers who have an email')->default(true),
                        ])
                        ->action(function (Collection $records, array $data) {
                            $desk = app(WarrantyDesk::class);
                            $records->each(function (WarrantyClaim $claim) use ($data, $desk) {
                                $claim->update(['status' => $data['status']]);
                                $desk->record($claim, (bool) $data['notify']);
                            });
                            Notification::make()->success()->title($records->count().' claim'.($records->count() === 1 ? '' : 's').' updated')->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /** Quick update from the list: status + note for the customer (+ email). */
    public static function statusAction(): Action
    {
        return Action::make('updateStatus')
            ->label('Update')
            ->icon('heroicon-o-arrow-path')
            ->color('gray')
            ->modalHeading(fn (WarrantyClaim $record) => 'Update claim #'.$record->number)
            ->modalDescription(fn (WarrantyClaim $record) => $record->product.($record->is_internal ? ' · own stock' : ' · '.$record->customer_name))
            ->modalSubmitActionLabel('Save update')
            ->fillForm(fn (WarrantyClaim $record) => ['status' => $record->status, 'customer_note' => $record->customer_note, 'notify' => true])
            ->schema(fn (WarrantyClaim $record) => [
                ToggleButtons::make('status')->options(WarrantyStatus::class)->required()->inline(),
                Textarea::make('customer_note')->label('Note for the customer')->rows(3)->maxLength(2000)
                    ->helperText('Shown on the website and in the email.')
                    ->hidden($record->is_internal),
                Toggle::make('notify')->label('Email '.($record->customer_email ?: 'the customer').' about this update')
                    ->visible(fn () => app(WarrantyDesk::class)->wouldEmail($record->customer_email, $record->is_internal)),
            ])
            ->action(function (WarrantyClaim $record, array $data) {
                $record->update(array_intersect_key($data, array_flip(['status', 'customer_note'])));
                $event = app(WarrantyDesk::class)->record($record, (bool) ($data['notify'] ?? false));

                Notification::make()->success()
                    ->title($event ? 'Claim updated' : 'Nothing changed')
                    ->body($event?->emailed_at ? 'The customer will get an email.' : null)
                    ->send();
            });
    }

    /** "Open" tab: still being handled. Own stock that's back in stock ("Stock in") is done, not waiting for pickup. */
    public static function openScope(Builder $query): Builder
    {
        return $query->where(fn (Builder $q) => $q
            ->whereIn('status', [WarrantyStatus::Received, WarrantyStatus::Claimed])
            ->orWhere(fn (Builder $q) => $q->where('status', WarrantyStatus::Ready)->where('is_internal', false)));
    }
}
