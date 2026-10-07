<?php

namespace App\Filament\Resources\Leads\Tables;

use App\Enums\LeadStatus;
use App\Enums\LeadType;
use App\Models\Lead;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LeadsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Received')
                    ->since()
                    ->dateTimeTooltip('d M Y, h:i A')
                    ->sortable(),
                TextColumn::make('type')->badge()->sortable(),
                TextColumn::make('name')
                    ->description(fn (Lead $record) => $record->phone)
                    ->searchable(['name', 'phone', 'email'])
                    ->weight('medium'),
                TextColumn::make('subject')
                    ->label('Enquiry')
                    ->state(fn (Lead $record) => $record->subject ?: $record->message)
                    ->limit(60)
                    ->tooltip(fn (Lead $record) => $record->message)
                    ->placeholder('—'),
                TextColumn::make('source')
                    ->label('Came from')
                    ->state(fn (Lead $record) => $record->source())
                    ->color('gray')
                    ->toggleable(),
                SelectColumn::make('status')
                    ->options(LeadStatus::class)
                    ->selectablePlaceholder(false)
                    ->afterStateUpdated(function (Lead $record, $state) {
                        if ($state !== LeadStatus::New->value && ! $record->contacted_at) {
                            $record->update(['contacted_at' => now()]);
                        }
                    })
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(LeadStatus::class)->default(null),
                SelectFilter::make('type')->options(LeadType::class),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('call')
                        ->icon('heroicon-o-phone')
                        ->url(fn (Lead $record) => 'tel:'.$record->phone),
                    Action::make('whatsapp')
                        ->label('WhatsApp')
                        ->icon('heroicon-o-chat-bubble-left-right')
                        ->url(fn (Lead $record) => 'https://wa.me/88'.$record->phone, shouldOpenInNewTab: true),
                    ViewAction::make(),
                    EditAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No leads yet')
            ->emptyStateDescription('Contact messages and repair requests from the website will appear here.')
            ->emptyStateIcon('heroicon-o-inbox');
    }
}
