<?php

namespace App\Support;

use App\Settings\BackupSettings;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Spatie\Backup\BackupDestination\Backup;
use Spatie\Backup\BackupDestination\BackupDestination;

/**
 * Automatic backups (spatie/laravel-backup): the database and uploaded files, every night, kept on this server in
 * storage/app/backups and — when set up — copied off-site over SFTP. config/backup.php holds the fixed parts; the
 * owner's choices (Admin → Settings → Backups) are applied here before any backup command runs.
 */
class Backups
{
    public const DISK = 'backups';

    public const REMOTE_DISK = 'backups-remote';

    /** Cache flag while "Back up now" runs. */
    public const RUNNING = 'backups.running';

    public static function configure(): void
    {
        $settings = app(BackupSettings::class);
        $remote = $settings->remote_enabled && filled($settings->remote_host) && filled($settings->remote_username);

        if ($remote) {
            config(['filesystems.disks.'.self::REMOTE_DISK => [
                'driver' => 'sftp',
                'host' => $settings->remote_host,
                'port' => $settings->remote_port ?: 22,
                'username' => $settings->remote_username,
                'password' => $settings->remote_password,
                'root' => $settings->remote_folder ?: '/backups/technofix',
                'timeout' => 30,
                'throw' => true,
            ]]);
        }

        $disks = $remote ? [self::DISK, self::REMOTE_DISK] : [self::DISK];

        config([
            'backup.backup.destination.disks' => $disks,
            'backup.backup.password' => $settings->archive_password ?: null,
            'backup.cleanup.default_strategy.keep_all_backups_for_days' => max(1, $settings->keep_days),
            'backup.notifications.mail.to' => $settings->notify_emails,
            'backup.monitor_backups.0.disks' => $disks,
        ]);
    }

    /**
     * The backups on this server, newest first.
     *
     * @return Collection<int, Backup>
     */
    public static function local(): Collection
    {
        return collect(BackupDestination::create(self::DISK, config('backup.backup.name'))->backups()->all())
            ->sortByDesc(fn (Backup $backup) => $backup->date()->timestamp)
            ->values();
    }

    public static function isRunning(): bool
    {
        return (bool) Cache::get(self::RUNNING);
    }
}
