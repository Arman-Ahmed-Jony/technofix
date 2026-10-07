<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('payments.cod_label', 'Cash on Delivery');
        $this->migrator->add('payments.cod_description', 'Pay in cash when you receive your order.');
        $this->migrator->add('payments.coming_soon', ['bKash', 'Nagad', 'Card']);
    }
};
