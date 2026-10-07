<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/*
| Starting values — the owner edits them in Admin → Settings.
| Contact details are placeholders until the owner fills them in.
*/
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('store.hotline', '01XXX-XXXXXX');
        $this->migrator->add('store.whatsapp', null);
        $this->migrator->add('store.email', 'info@technofixbd.com');
        $this->migrator->add('store.address', 'Dhaka, Bangladesh');
        $this->migrator->add('store.hours', 'Sat – Thu, 10:00 AM – 8:00 PM');
        $this->migrator->add('store.facebook', null);
        $this->migrator->add('store.youtube', null);
        $this->migrator->add('store.instagram', null);
        $this->migrator->add('store.tiktok', null);

        $this->migrator->add('delivery.options', [
            ['code' => 'inside_dhaka', 'label' => 'Inside Dhaka city', 'eta' => '1–2 days', 'fee' => 80, 'type' => 'delivery', 'area' => 'only', 'districts' => ['Dhaka'], 'active' => true],
            ['code' => 'outside_dhaka', 'label' => 'Outside Dhaka', 'eta' => '2–4 days', 'fee' => 150, 'type' => 'delivery', 'area' => 'except', 'districts' => ['Dhaka'], 'active' => true],
            ['code' => 'pickup', 'label' => 'Store pickup', 'eta' => 'Ready in 2 hours', 'fee' => 0, 'type' => 'pickup', 'area' => 'all', 'districts' => [], 'active' => true],
        ]);
        $this->migrator->add('delivery.free_delivery_over', null);
    }
};
