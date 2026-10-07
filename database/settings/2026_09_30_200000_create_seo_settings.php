<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('seo.site_name', 'TechnoFix');
        $this->migrator->add('seo.home_title', 'TechnoFix | Laptop, PC Components, CCTV & IT Shop in Bangladesh');
        $this->migrator->add('seo.home_description', 'Genuine laptops, PC components, CCTV, networking and accessories with official warranty, cash on delivery across Bangladesh and expert repair service.');

        $this->migrator->add('seo.product_title', '{name} Price in Bangladesh');
        $this->migrator->add('seo.product_description', 'Buy {name} at the best price in Bangladesh — {price}. Genuine products and cash on delivery across Bangladesh from {site}.');
        $this->migrator->add('seo.category_title', '{name} Price in Bangladesh');
        $this->migrator->add('seo.category_description', 'Buy {name} at the best price in Bangladesh from {site} — genuine products, official warranty and cash on delivery nationwide.');
        $this->migrator->add('seo.brand_title', '{name} Products Price in Bangladesh');
        $this->migrator->add('seo.brand_description', 'Genuine {name} products at the best price in Bangladesh from {site} — official warranty and cash on delivery nationwide.');
        $this->migrator->add('seo.category_brand_title', '{brand} {category} Price in Bangladesh');
        $this->migrator->add('seo.category_brand_description', 'Buy genuine {brand} {category} at the best price in Bangladesh from {site} — official warranty, cash on delivery nationwide.');

        $this->migrator->add('seo.pages', []);
        $this->migrator->add('seo.share_image', null);
        $this->migrator->add('seo.business_type', 'ElectronicsStore');
    }
};
