<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('seo.allow_ai_crawlers', true);
        $this->migrator->add('seo.robots_extra', '');
        $this->migrator->add('seo.llms_enabled', true);
        $this->migrator->add('seo.llms_intro', 'TechnoFix is an online computer and IT shop in Bangladesh selling laptops, PC components, monitors, CCTV and security systems, networking gear, accessories and software — genuine products with official warranty and cash on delivery nationwide. We also repair laptops and desktops and install CCTV and office networks.');
    }
};
