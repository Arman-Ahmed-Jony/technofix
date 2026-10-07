<?php

namespace App\Filament\Resources\Leads\Schemas;

use App\Models\Lead;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LeadInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(['default' => 1, 'lg' => 3])
                ->columnSpanFull()
                ->schema([
                    Section::make('Enquiry')
                        ->columnSpan(['lg' => 2])
                        ->columns(2)
                        ->schema([
                            TextEntry::make('type')->badge(),
                            TextEntry::make('created_at')->label('Received')->dateTime('d M Y, h:i A')->since(),
                            TextEntry::make('subject')->placeholder('—')->columnSpanFull(),
                            TextEntry::make('message')
                                ->placeholder('—')
                                ->formatStateUsing(fn (?string $state) => nl2br(e((string) $state))) // keep the customer's line breaks (e.g. PC build part lists)
                                ->html()
                                ->prose()
                                ->columnSpanFull(),
                            KeyValueEntry::make('meta')
                                ->label('Details')
                                ->keyLabel('Field')
                                ->valueLabel('Value')
                                ->visible(fn (Lead $record) => filled($record->meta))
                                ->columnSpanFull(),
                            TextEntry::make('source')
                                ->label('Came from')
                                ->state(fn (Lead $record) => $record->source())
                                ->helperText(fn (Lead $record) => collect([$record->attribution['utm_campaign'] ?? null, $record->attribution['utm_content'] ?? null])->filter()->join(' · ') ?: null),
                            TextEntry::make('page_url')->label('Sent from page')->url(fn (Lead $record) => $record->page_url)->openUrlInNewTab()->placeholder('—')->columnSpanFull(),
                        ]),
                    Section::make('Customer')
                        ->columnSpan(1)
                        ->schema([
                            TextEntry::make('name')->weight('bold'),
                            TextEntry::make('phone')
                                ->label('Mobile')
                                ->copyable()
                                ->url(fn (Lead $record) => 'tel:'.$record->phone)
                                ->icon('heroicon-o-phone'),
                            TextEntry::make('email')->copyable()->placeholder('—')->icon('heroicon-o-envelope'),
                            TextEntry::make('status')->badge(),
                            TextEntry::make('contacted_at')->label('Contacted')->dateTime('d M Y, h:i A')->placeholder('Not yet'),
                            TextEntry::make('notes')->label('Internal notes')->placeholder('—'),
                        ]),
                ]),
        ]);
    }
}
