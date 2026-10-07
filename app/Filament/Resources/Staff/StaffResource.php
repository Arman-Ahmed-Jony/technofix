<?php

namespace App\Filament\Resources\Staff;

use App\Filament\Resources\Roles\RoleResource;
use App\Filament\Resources\Staff\Pages\CreateStaff;
use App\Filament\Resources\Staff\Pages\EditStaff;
use App\Filament\Resources\Staff\Pages\ListStaff;
use App\Models\Role;
use App\Models\User;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\Rules\Password;
use UnitEnum;

/**
 * Staff accounts for the admin. What each person can open comes from their role (RoleResource). Only owners manage
 * owners, nobody switches off or deletes themselves, and the shop always keeps an active owner (User model).
 */
class StaffResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Staff';

    protected static ?string $modelLabel = 'staff member';

    protected static ?string $pluralModelLabel = 'staff';

    protected static ?string $slug = 'staff';

    protected static ?int $navigationSort = 20;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Account')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    self::avatarField()->columnSpanFull(),
                    TextInput::make('name')->required()->maxLength(100),
                    TextInput::make('email')->label('Email (to sign in)')->email()->required()->maxLength(120)->unique(ignoreRecord: true),
                    Select::make('role_id')
                        ->label('Role')
                        ->relationship('role', 'name', fn (Builder $query) => $query->orderBy('sort')->when(! auth()->user()?->isOwner(), fn ($q) => $q->where('is_owner', false)))
                        ->required()
                        ->native(false)
                        ->disabled(fn (?User $record) => $record?->is(auth()->user()))
                        ->helperText(fn (?User $record) => $record?->is(auth()->user())
                            ? 'You can’t change your own role.'
                            : new HtmlString('What they can open — see <a href="'.RoleResource::getUrl().'" class="underline">Roles</a>.')),
                    Toggle::make('is_active')
                        ->label('Can sign in')
                        ->default(true)
                        ->inline(false)
                        ->disabled(fn (?User $record) => $record?->is(auth()->user()))
                        ->helperText('Switch off when someone leaves — their name stays on the orders and history they worked on.'),
                ]),
            Section::make(fn (string $operation) => $operation === 'create' ? 'Password' : 'Set a new password')
                ->columnSpanFull()
                ->description(fn (string $operation) => $operation === 'create'
                    ? 'Give it to them privately. They can change it any time on My profile.'
                    : 'Only if they forgot it — leave empty to keep their current password. They can also use “Forgot password?” on the sign-in page.')
                ->schema([
                    TextInput::make('password')
                        ->password()
                        ->revealable()
                        ->required(fn (string $operation) => $operation === 'create')
                        ->rule(User::passwordRule())
                        ->helperText(User::PASSWORD_HINT)
                        ->dehydrated(fn (?string $state) => filled($state))
                        ->autocomplete('new-password'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('role'))
            ->defaultSort('name')
            ->columns([
                ImageColumn::make('avatar_path')
                    ->label('')
                    ->disk('public')
                    ->circular()
                    ->imageSize(38)
                    ->defaultImageUrl(fn (User $record) => filament()->getUserAvatarUrl($record)),
                TextColumn::make('name')
                    ->searchable(['name', 'email'])
                    ->weight('medium')
                    ->description(fn (User $record) => $record->email)
                    ->suffix(fn (User $record) => $record->is(auth()->user()) ? ' (you)' : null),
                TextColumn::make('role.name')->label('Role')->badge()->color(fn (User $record) => $record->isOwner() ? 'warning' : 'info'),
                IconColumn::make('two_step')
                    ->label('Two-step sign-in')
                    ->state(fn (User $record) => filled($record->app_authentication_secret))
                    ->boolean()
                    ->alignCenter(),
                TextColumn::make('last_login_at')->label('Last signed in')->since()->placeholder('Never')->sortable(),
                ToggleColumn::make('is_active')
                    ->label('Can sign in')
                    ->disabled(fn (User $record) => $record->is(auth()->user()) || ($record->isOwner() && ! auth()->user()?->isOwner())),
            ])
            ->filters([
                SelectFilter::make('role')->relationship('role', 'name'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->modalDescription('Their account is removed. To keep their name on past orders and history, switch "Can sign in" off instead.'),
            ]);
    }

    /** Profile photo: cropped to a circle and shrunk to 300 px in the browser before upload. */
    public static function avatarField(): FileUpload
    {
        return FileUpload::make('avatar_path')
            ->label('Photo')
            ->avatar()
            ->image()
            ->imageEditor()
            ->circleCropper()
            ->imageResizeMode('cover')
            ->imageResizeTargetWidth('300')
            ->imageResizeTargetHeight('300')
            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
            ->maxSize(4096)
            ->disk('public')
            ->directory('avatars')
            ->visibility('public')
            ->helperText('JPG, PNG or WebP.');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStaff::route('/'),
            'create' => CreateStaff::route('/create'),
            'edit' => EditStaff::route('/{record}/edit'),
        ];
    }
}
