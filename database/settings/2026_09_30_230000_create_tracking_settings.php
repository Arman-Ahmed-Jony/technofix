<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('tracking.gtm_id', null);
        $this->migrator->add('tracking.ga4_id', null);
        $this->migrator->add('tracking.meta_pixel_id', null);
        $this->migrator->addEncrypted('tracking.meta_capi_token', null);
        $this->migrator->add('tracking.meta_test_event_code', null);
        $this->migrator->add('tracking.tiktok_pixel_id', null);
        $this->migrator->add('tracking.load_scripts', 'after_load');
        $this->migrator->add('tracking.head_code', null);
        $this->migrator->add('tracking.body_code', null);
    }
};
