<?php

use Illuminate\Support\Facades\DB;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Branding (Settings → Branding): uploaded files on the public disk; empty = the built-in TechnoFix files.
        $this->migrator->add('brand.logo', null);
        $this->migrator->add('brand.logo_dark', null);
        $this->migrator->add('brand.icon', null);
        $this->migrator->add('brand.email_logo', null);

        // Footer (Content → Footer).
        $this->migrator->add('footer.about', 'Providing a more efficient computing experience — laptops, PC components, CCTV, networking and expert repair service in one place.');
        $this->migrator->add('footer.badges', [
            ['icon' => 'rosette-discount-check', 'title' => '100% Genuine', 'text' => 'Official products & warranty'],
            ['icon' => 'truck-delivery', 'title' => 'Nationwide Delivery', 'text' => 'Fast delivery across Bangladesh'],
            ['icon' => 'cash', 'title' => 'Cash on Delivery', 'text' => 'Pay when you receive'],
            ['icon' => 'headset', 'title' => 'Expert Support', 'text' => 'Sales & repair specialists'],
        ]);
        $this->migrator->add('footer.cta_enabled', true);
        $this->migrator->add('footer.cta_eyebrow', 'Expert help');
        $this->migrator->add('footer.cta_title', 'Not sure what to buy?');
        $this->migrator->add('footer.cta_highlight', 'Talk to an expert.');
        $this->migrator->add('footer.cta_text', 'Our team helps you pick the right laptop, build a PC within budget or plan a CCTV setup — free, no pressure.');
        $this->migrator->add('footer.copyright', '© {year} TechnoFix. All rights reserved.');

        // More phone numbers (Settings → Store & contact): [{label, number}].
        $this->migrator->add('store.extra_phones', []);

        // The shop's address is technofixbd.com: replace the placeholder address wherever it was saved.
        foreach (DB::table('settings')->where('payload', 'like', '%technofix.com.bd%')->get() as $row) {
            DB::table('settings')->where('id', $row->id)->update(['payload' => str_replace('technofix.com.bd', 'technofixbd.com', $row->payload)]);
        }
    }
};
