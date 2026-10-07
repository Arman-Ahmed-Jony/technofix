<?php

namespace App\Filament\Resources\Customers;

use App\Enums\OrderStatus;
use App\Filament\Resources\Customers\Pages\EditCustomer;
use App\Filament\Resources\Customers\Pages\ListCustomers;
use App\Filament\Resources\Customers\RelationManagers\AddressesRelationManager;
use App\Filament\Resources\Customers\RelationManagers\OrdersRelationManager;
use App\Models\Customer;
use App\Support\Phone;
use BackedEnum;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rules\Password;
use UnitEnum;

/**
 * Storefront customer accounts. Customers sign up themselves; staff can correct details, block an account or set a
 * new password (until SMS password resets exist).
 */
class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static string|UnitEnum|null $navigationGroup = 'Sales';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'name';

    /** @return list<string> */
    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'phone', 'email'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return ['Mobile' => $record->phone];
    }

    public static function canCreate(): bool
    {
        return false; // customers create their own accounts
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Account')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    TextInput::make('name')->required()->maxLength(80),
                    TextInput::make('phone')
                        ->label('Mobile (sign-in)')
                        ->required()
                        ->dehydrateStateUsing(fn (?string $state) => Phone::normalize($state))
                        ->unique(ignoreRecord: true)
                        ->rules([fn (): Closure => function (string $attribute, $value, Closure $fail) {
                            if (! Phone::isValid($value)) {
                                $fail('Enter a valid Bangladeshi mobile number, e.g. 01712345678.');
                            }
                        }]),
                    TextInput::make('email')->email()->maxLength(120)->unique(ignoreRecord: true),
                    Toggle::make('is_active')
                        ->label('Can sign in')
                        ->helperText('Turn off to block this account (their orders stay).')
                        ->inline(false),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        $counted = [OrderStatus::Cancelled->value, OrderStatus::Returned->value];

        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->withCount('orders')
                ->withSum(['orders as spent' => fn (Builder $q) => $q->whereNotIn('status', $counted)], 'total'))
            ->searchPlaceholder('Name, mobile or email')
            ->columns([
                TextColumn::make('name')
                    ->searchable(['name', 'phone', 'email'])
                    ->weight('medium')
                    ->description(fn (Customer $record) => $record->phone.($record->email ? ' · '.$record->email : '')),
                TextColumn::make('orders_count')->label('Orders')->numeric()->sortable(),
                TextColumn::make('spent')
                    ->label('Spent')
                    ->formatStateUsing(fn ($state) => 'BDT '.number_format((int) $state))
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('created_at')->label('Joined')->date('d M Y')->sortable(),
                TextColumn::make('last_login_at')->label('Last sign-in')->since()->placeholder('—')->sortable()->toggleable(),
                ToggleColumn::make('is_active')->label('Can sign in'),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('Can sign in'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    /** Staff set a password for customers who can't reset it by email. */
    public static function setPasswordAction(): Action
    {
        return Action::make('setPassword')
            ->label('Set new password')
            ->icon('heroicon-o-key')
            ->color('gray')
            ->modalWidth('md')
            ->modalDescription('Tell the customer their new password by phone. They can change it in their account.')
            ->schema([
                TextInput::make('password')->label('New password')->password()->revealable()->required()->rule(Password::min(8)),
            ])
            ->action(function (Customer $record, array $data) {
                $record->forceFill(['password' => $data['password']])->setRememberToken(null);
                $record->save();

                Notification::make()->success()->title('Password changed for '.$record->name)->send();
            });
    }

    public static function getRelations(): array
    {
        return [OrdersRelationManager::class, AddressesRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomers::route('/'),
            'edit' => EditCustomer::route('/{record}/edit'),
        ];
    }
}
