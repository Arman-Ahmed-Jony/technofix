<?php

namespace App\Filament\Resources\Leads\Schemas;

use App\Enums\LeadStatus;
use App\Enums\LeadType;
use App\Support\Phone;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Used to log phone/WhatsApp enquiries manually and to update follow-up status and notes.
 */
class LeadForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Enquiry')
                ->columns(2)
                ->schema([
                    Select::make('type')
                        ->options(LeadType::class)
                        ->default(LeadType::Contact)
                        ->required()
                        ->native(false),
                    TextInput::make('name')->required()->maxLength(100),
                    TextInput::make('phone')
                        ->label('Mobile number')
                        ->tel()
                        ->required()
                        ->placeholder('01712345678')
                        ->dehydrateStateUsing(fn (?string $state) => Phone::normalize($state))
                        ->rule('regex:/^(\+?880|0)?\s*1[3-9][\d\s-]{8,10}$/'),
                    TextInput::make('email')->email()->maxLength(255),
                    TextInput::make('subject')->maxLength(150)->columnSpanFull(),
                    Textarea::make('message')->rows(4)->columnSpanFull(),
                ]),
            Section::make('Follow-up')
                ->columns(2)
                ->schema([
                    Select::make('status')
                        ->options(LeadStatus::class)
                        ->default(LeadStatus::New)
                        ->required()
                        ->native(false),
                    Textarea::make('notes')
                        ->label('Internal notes')
                        ->placeholder('What was discussed, quote given, next step…')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
