<?php

namespace App\Support;

use App\Settings\MailSettings;

/**
 * Applies the owner's email settings (Admin → Settings → Email sending) to Laravel's mail config. Called when the mail
 * manager is first resolved, so pages that send nothing never read them. Switched off = the .env settings stay.
 */
class MailSetup
{
    public static function apply(): void
    {
        $settings = rescue(fn () => app(MailSettings::class), null, report: false);

        if (! $settings || ! rescue(fn () => $settings->enabled, false, report: false) || blank($settings->host)) {
            return;
        }

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => $settings->host,
            'mail.mailers.smtp.port' => $settings->port,
            'mail.mailers.smtp.scheme' => $settings->encryption === 'ssl' ? 'smtps' : 'smtp',
            'mail.mailers.smtp.username' => $settings->username,
            'mail.mailers.smtp.password' => $settings->password,
            'mail.mailers.smtp.timeout' => 20,
        ]);

        if (filled($settings->from_address)) {
            config(['mail.from' => ['address' => $settings->from_address, 'name' => $settings->from_name ?: 'TechnoFix']]);
        }
    }
}
