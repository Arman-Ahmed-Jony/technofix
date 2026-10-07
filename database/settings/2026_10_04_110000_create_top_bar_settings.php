<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Starts with exactly what the top bar showed before it became editable.
        $this->migrator->add('top_bar.show_hotline', true);
        $this->migrator->add('top_bar.show_hours', true);
        $this->migrator->add('top_bar.message', 'Cash on delivery all over Bangladesh');
        $this->migrator->add('top_bar.message_icon', 'truck-delivery');
        $this->migrator->add('top_bar.links', [
            ['label' => 'Track Order', 'url' => '/track-order'],
            ['label' => 'Repair Service', 'url' => '/services'],
            ['label' => 'Offers', 'url' => '/offers'],
        ]);
    }
};
