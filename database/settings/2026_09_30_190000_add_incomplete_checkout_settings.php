<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('orders.capture_incomplete_checkouts', true);
        $this->migrator->add('orders.incomplete_checkout_minutes', 30);
        $this->migrator->add('orders.incomplete_checkout_message', 'Hi {name}, this is TechnoFix. You were ordering {items} on our website — can we help you finish your order? Your cart is saved here: {link}');
    }
};
