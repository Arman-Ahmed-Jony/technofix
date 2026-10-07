<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('pc_builder.quote_note', 'Prices and stock can change — please confirm with us before ordering. Our team is happy to check this build for you.');
    }
};
