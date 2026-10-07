<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Facebook's page box doesn't include the cover photo for newer-style pages, so the owner can upload it here.
        $this->migrator->add('footer.facebook_cover', null);
    }
};
