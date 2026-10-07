<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Invoices (Settings → Store & contact).
        $this->migrator->add('store.legal_name', 'TechnoFix');
        $this->migrator->add('store.trade_licence', null);
        $this->migrator->add('store.bin', null);
        $this->migrator->add('store.invoice_footer', 'Thank you for shopping with TechnoFix. Products carry the official brand warranty shown above — please keep this invoice for warranty claims.');

        // Email sending (Settings → Email sending). Off = the server's .env mail settings.
        $this->migrator->add('mail.enabled', false);
        $this->migrator->add('mail.host', null);
        $this->migrator->add('mail.port', 587);
        $this->migrator->add('mail.encryption', 'tls');
        $this->migrator->add('mail.username', null);
        $this->migrator->addEncrypted('mail.password', null);
        $this->migrator->add('mail.from_address', null);
        $this->migrator->add('mail.from_name', 'TechnoFix');

        // Maintenance mode (Settings → Maintenance mode).
        $this->migrator->add('maintenance.enabled', false);
        $this->migrator->add('maintenance.headline', 'We’ll be back shortly');
        $this->migrator->add('maintenance.message', 'We’re improving the shop. You can still order by phone or WhatsApp in the meantime.');
        $this->migrator->add('maintenance.back_at', null);
    }
};
