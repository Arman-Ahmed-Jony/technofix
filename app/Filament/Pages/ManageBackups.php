<?php

namespace App\Filament\Pages;

use App\Jobs\RunBackup;
use App\Models\User;
use App\Settings\BackupSettings;
use App\Support\ActivityLog;
use App\Support\Backups;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;
use Spatie\Backup\BackupDestination\Backup;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

/**
 * Backups (owners only — a backup holds every order and customer): nightly schedule, how long to keep them, the
 * off-site copy, and the list of backups on this server with download / delete / "Back up now".
 */
class ManageBackups extends SettingsPage
{
    protected static string $settings = BackupSettings::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-circle-stack';

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Backups';

    protected static ?string $title = 'Backups';

    protected static ?int $navigationSort = 23;

    protected static ?string $slug = 'backups';

    public static function canAccess(): bool
    {
        $user = auth('web')->user();

        return $user instanceof User && $user->is_active && $user->isOwner();
    }

    public function mount(): void
    {
        parent::mount();
        Backups::configure();
    }

    public function getSubheading(): ?string
    {
        return 'The database (orders, customers, products, settings) and uploaded photos are backed up every night. Download one now and then and keep it somewhere safe.';
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Backups on this server')
                ->columnSpanFull()
                ->schema([View::make('filament.backups.list')->viewData(fn () => [
                    'backups' => Backups::local(),
                    'running' => Backups::isRunning(),
                ])]),
            Section::make('Schedule')
                ->columns(['default' => 1, 'md' => 3])
                ->columnSpanFull()
                ->schema([
                    Toggle::make('enabled')->label('Back up every night')->inline(false)->live(),
                    TimePicker::make('run_at')->label('At')->seconds(false)->format('H:i')->required()->visible(fn (Get $get) => $get('enabled'))
                        ->helperText('A quiet time — the shop keeps running during a backup.'),
                    TextInput::make('keep_days')->label('Keep every backup for')->integer()->minValue(3)->maxValue(90)->suffix('days')->required()
                        ->helperText('After that, one a day for 16 days, then one a week, a month and a year.'),
                    TagsInput::make('notify_emails')
                        ->label('Email when a backup fails or none has been made for 2 days')
                        ->placeholder('Add an email and press Enter')
                        ->nestedRecursiveRules(['email'])
                        ->columnSpanFull(),
                    TextInput::make('archive_password')
                        ->label('Backup password (optional)')
                        ->password()
                        ->revealable()
                        ->maxLength(100)
                        ->helperText('Locks the backup file. You’ll need it to open a backup — keep it somewhere safe. Strongly advised with an off-site copy.')
                        ->columnSpanFull(),
                ]),
            Section::make('Off-site copy (SFTP)')
                ->columns(['default' => 1, 'md' => 3])
                ->columnSpanFull()
                ->description('A second copy on another server — another hosting account, a VPS or a NAS — so a problem with this hosting doesn’t take the backups with it.')
                ->schema([
                    Toggle::make('remote_enabled')->label('Also copy backups off-site')->live()->columnSpanFull(),
                    TextInput::make('remote_host')->label('Server')->placeholder('backup.example.com')->required(fn (Get $get) => $get('remote_enabled'))->visible(fn (Get $get) => $get('remote_enabled')),
                    TextInput::make('remote_port')->label('Port')->integer()->minValue(1)->maxValue(65535)->required()->visible(fn (Get $get) => $get('remote_enabled')),
                    TextInput::make('remote_folder')->label('Folder')->placeholder('/backups/technofix')->visible(fn (Get $get) => $get('remote_enabled')),
                    TextInput::make('remote_username')->label('Username')->required(fn (Get $get) => $get('remote_enabled'))->visible(fn (Get $get) => $get('remote_enabled')),
                    TextInput::make('remote_password')->label('Password')->password()->revealable()->visible(fn (Get $get) => $get('remote_enabled'))
                        ->helperText('Stored encrypted. Use the "Test off-site copy" button after saving.'),
                ]),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('runNow')
                ->label('Back up now')
                ->icon('heroicon-o-arrow-path')
                ->disabled(fn () => Backups::isRunning())
                ->action(function () {
                    RunBackup::dispatch();
                    ActivityLog::record('backup', label: 'Backup started from the admin', type: 'settings');
                    Notification::make()->success()->title('Backup started')->body('It runs in the background and appears in the list within a few minutes.')->send();
                }),
            Action::make('testRemote')
                ->label('Test off-site copy')
                ->icon('heroicon-o-signal')
                ->color('gray')
                ->visible(fn () => app(BackupSettings::class)->remote_enabled)
                ->action(fn () => $this->testRemote()),
        ];
    }

    /** Download one of the listed backups (only names from the list are accepted). */
    public function download(string $path): ?StreamedResponse
    {
        if (! $this->listed($path)) {
            return null;
        }

        ActivityLog::record('backup', label: 'Downloaded '.basename($path), type: 'settings');

        return Storage::disk(Backups::DISK)->download($path);
    }

    public function deleteBackup(string $path): void
    {
        if ($this->listed($path) && Backups::local()->count() > 1) {
            Storage::disk(Backups::DISK)->delete($path);
            ActivityLog::record('backup', label: 'Deleted '.basename($path), type: 'settings');
            Notification::make()->success()->title('Backup deleted')->send();
        }
    }

    protected function listed(string $path): bool
    {
        return Backups::local()->contains(fn (Backup $backup) => $backup->path() === $path);
    }

    protected function testRemote(): void
    {
        Backups::configure();

        try {
            $disk = Storage::disk(Backups::REMOTE_DISK);
            $disk->put('connection-test.txt', 'TechnoFix backup test '.now());
            $disk->delete('connection-test.txt');
            Notification::make()->success()->title('Off-site copy works')->body('The server accepted a test file.')->send();
        } catch (\Throwable $e) {
            Notification::make()->danger()->title('Couldn’t reach the off-site server')->body($e->getMessage())->persistent()->send();
        }
    }
}
