<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('download.enabled', true);
        $this->migrator->add('download.label', 'Download software');
        $this->migrator->add('download.url', 'https://drive.google.com/open?id=10n045SALhseduA4quDZjF_PhCECz7GEg&usp=drive_fs');
        $this->migrator->add('download.title', 'Need drivers or software?');
        $this->migrator->add('download.text', 'Drivers, tools and setup files our technicians recommend — free to download.');
        $this->migrator->add('download.show_top_bar', true);
        $this->migrator->add('download.show_services', true);
        $this->migrator->add('download.show_footer', true);
    }
};
