<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('warranty.lookup_enabled', true);
        $this->migrator->add('warranty.emails_enabled', true);
        $this->migrator->add('warranty.page_intro', 'Enter the mobile number you gave us when you dropped off your product. You’ll see every product we’re handling for you under warranty, with its latest status.');
        $this->migrator->add('warranty.sheet_banner', null);
    }
};
