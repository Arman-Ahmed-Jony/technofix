<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Automatic backups — Admin → Settings → Backups (owners only). Applied by App\Support\Backups::configure().
 */
class BackupSettings extends Settings
{
    public bool $enabled;

    /** Daily time ("HH:MM", shop time). */
    public string $run_at;

    /** Every backup is kept this many days; after that one per day, week, month and year (spatie default strategy). */
    public int $keep_days;

    /** Who hears about failed or missing backups. */
    public array $notify_emails;

    /** Locks the backup zip (AES-256); needed to open it. Stored encrypted. */
    public ?string $archive_password;

    /** Off-site copy over SFTP (another hosting account, a VPS, a NAS…). */
    public bool $remote_enabled;

    public ?string $remote_host;

    public int $remote_port;

    public ?string $remote_username;

    public ?string $remote_password;

    public ?string $remote_folder;

    public static function group(): string
    {
        return 'backups';
    }

    public static function encrypted(): array
    {
        return ['archive_password', 'remote_password'];
    }
}
