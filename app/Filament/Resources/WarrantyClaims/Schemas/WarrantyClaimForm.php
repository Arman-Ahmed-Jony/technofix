<?php

namespace App\Filament\Resources\WarrantyClaims\Schemas;

use App\Enums\WarrantyStatus;
use App\Models\Supplier;
use App\Models\WarrantyClaim;
use App\Services\WarrantyDesk;
use App\Support\Phone;
use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

/** Sales → Warranty claims form: the sheet's columns, grouped, plus the customer's email and notes. */
class WarrantyClaimForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Grid::make(1)->columnSpan(['lg' => 2])->schema([
                Section::make('Customer')
                    ->columns(2)
                    ->schema([
                        TextInput::make('customer_name')->label('Customer name (person)')->required()->maxLength(120)
                            ->placeholder('e.g. Rahim or Rahman Traders'),
                        TextInput::make('customer_phone')->label('Mobile number')->tel()->maxLength(20)->placeholder('01XXXXXXXXX')
                            ->rule(fn () => function (string $attribute, mixed $value, Closure $fail) {
                                if (filled($value) && ! Phone::isValid((string) $value)) {
                                    $fail('Enter a Bangladeshi mobile number, e.g. 01712345678.');
                                }
                            })
                            ->helperText('The customer checks this claim on the website with this number.'),
                        TextInput::make('customer_email')->label('Email (optional)')->email()->maxLength(255)->live(onBlur: true)
                            ->helperText('They get an email when the status or the note for them changes.'),
                        Toggle::make('is_internal')->label('Our own stock (not a customer’s product)')->live()->inline(false)
                            ->helperText('Hidden from the warranty status page, and no emails.'),
                    ]),
                Section::make('Product')
                    ->columns(3)
                    ->schema([
                        TextInput::make('product')->label('Product (description)')->required()->maxLength(255)->columnSpan(2)
                            ->placeholder('e.g. WD 1TB SATA HARD DISK'),
                        TextInput::make('qty')->label('Qty')->integer()->minValue(1)->maxValue(9999)->default(1)->required(),
                        TextInput::make('serial')->label('Product serial (SN)')->maxLength(120)->placeholder('N/A if none'),
                        Textarea::make('problem')->label('Problem')->rows(2)->maxLength(2000)->columnSpan(2)
                            ->placeholder('e.g. No power, over heat'),
                    ]),
                Section::make('Claim')
                    ->columns(3)
                    ->schema([
                        TextInput::make('number')->label('Claim no.')->integer()->minValue(1)
                            ->default(fn () => WarrantyClaim::nextNumber())
                            ->helperText('The NO column of the sheet.'),
                        DatePicker::make('received_on')->label('Received from customer')->default(now())->displayFormat('d-m-Y')->native(false),
                        TextInput::make('vendor')->label('Vendor (claimed to)')->maxLength(120)->datalist(fn () => self::vendors()),
                        DatePicker::make('claimed_on')->label('Claim date')->displayFormat('d-m-Y')->native(false),
                        DatePicker::make('returned_on')->label('Received back')->displayFormat('d-m-Y')->native(false),
                        TextInput::make('new_serial')->label('New SN')->maxLength(120)->datalist(['NEW', 'SAME', 'RETURN', 'SALES RETURN'])
                            ->helperText('New serial, or NEW / SAME / RETURN.'),
                    ]),
            ]),
            Grid::make(1)->columnSpan(1)->schema([
                Section::make('Status')
                    ->schema([
                        ToggleButtons::make('status')->hiddenLabel()->options(WarrantyStatus::class)->default(WarrantyStatus::Received)
                            ->required()->live(),
                        Textarea::make('customer_note')->label('Note for the customer')->rows(3)->maxLength(2000)
                            ->placeholder('e.g. The brand replaced it with a new one. Please collect it with your receipt.')
                            ->helperText('Shown on the website and in the email.'),
                        Textarea::make('staff_note')->label('Staff note (internal)')->rows(3)->maxLength(5000)
                            ->helperText('Charges, passwords, who brought it… Never shown to the customer.'),
                        Toggle::make('notify')->label('Email the customer about this update')->default(true)->dehydrated(false)
                            ->visible(fn (Get $get) => app(WarrantyDesk::class)->wouldEmail($get('customer_email'), (bool) $get('is_internal')))
                            ->helperText('Sent when the status or the note for the customer changes.'),
                    ]),
                Section::make('Updates the customer was given')
                    ->visible(fn (?WarrantyClaim $record) => $record?->events()->exists())
                    ->schema([
                        TextEntry::make('timeline')->hiddenLabel()->html()
                            ->state(fn (WarrantyClaim $record) => self::timeline($record)),
                    ]),
            ]),
        ]);
    }

    /** @return list<string> */
    public static function vendors(): array
    {
        return WarrantyClaim::query()->whereNotNull('vendor')->distinct()->pluck('vendor')
            ->merge(Supplier::query()->pluck('name'))
            ->map(fn ($name) => trim((string) $name))->filter()->unique(fn ($name) => strtoupper($name))->sort()->values()->all();
    }

    protected static function timeline(WarrantyClaim $record): HtmlString
    {
        $items = $record->events()->with('user:id,name')->limit(20)->get()->map(fn ($event) => '<li class="py-2">'
            .'<div class="flex items-center justify-between gap-2"><strong>'.e($event->status->getLabel()).'</strong>'
            .'<span class="text-xs text-gray-500">'.e($event->created_at->format('d M Y, g:i A')).'</span></div>'
            .($event->note ? '<p class="mt-1 text-sm whitespace-pre-line text-gray-600 dark:text-gray-300">'.e($event->note).'</p>' : '')
            .'<p class="mt-0.5 text-xs text-gray-500">'.e(collect([$event->user?->name, $event->emailed_at ? 'emailed' : null])->filter()->join(' · ')).'</p>'
            .'</li>');

        return new HtmlString('<ul class="divide-y divide-gray-200 dark:divide-white/10">'.$items->join('').'</ul>');
    }
}
