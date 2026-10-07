<?php

use Illuminate\Support\Facades\DB;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Problems are emailed to the store email to start with (Settings → Store & contact).
        $storeEmail = json_decode((string) DB::table('settings')->where('group', 'store')->where('name', 'email')->value('payload'), true);

        $this->migrator->add('backups.enabled', true);
        $this->migrator->add('backups.run_at', '03:00');
        $this->migrator->add('backups.keep_days', 14);
        $this->migrator->add('backups.notify_emails', array_values(array_filter([$storeEmail])));
        $this->migrator->addEncrypted('backups.archive_password', null);
        $this->migrator->add('backups.remote_enabled', false);
        $this->migrator->add('backups.remote_host', null);
        $this->migrator->add('backups.remote_port', 22);
        $this->migrator->add('backups.remote_username', null);
        $this->migrator->addEncrypted('backups.remote_password', null);
        $this->migrator->add('backups.remote_folder', '/backups/technofix');
    }
};
