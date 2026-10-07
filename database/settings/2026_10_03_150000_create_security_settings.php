<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('security.turnstile_enabled', false);
        $this->migrator->add('security.turnstile_site_key', null);
        $this->migrator->addEncrypted('security.turnstile_secret_key', null);
        $this->migrator->add('security.turnstile_forms', ['checkout', 'enquiry', 'pc_builder', 'register', 'login', 'password', 'track_order']);
        $this->migrator->add('security.blocked_phones', []);
        $this->migrator->add('security.blocked_ips', []);
        $this->migrator->add('security.blocked_message', 'Sorry, we can’t accept this request online. Please call us on {hotline} and we’ll be happy to help.');
    }
};
