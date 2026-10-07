<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Facebook's page box in the footer (cover photo + live Follow button). Empty URL = the Store & contact link.
        $this->migrator->add('footer.facebook_box', true);
        $this->migrator->add('footer.facebook_page_url', 'https://www.facebook.com/technofixuttara/');
    }
};
