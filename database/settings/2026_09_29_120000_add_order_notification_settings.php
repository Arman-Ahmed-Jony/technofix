<?php

use Illuminate\Support\Facades\DB;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Start with the store email from Settings → Store & contact; the owner can change the list.
        $storeEmail = DB::table('settings')->where('group', 'store')->where('name', 'email')->value('payload');

        $this->migrator->add('orders.notify_emails', array_values(array_filter([json_decode((string) $storeEmail, true)])));
        $this->migrator->add('orders.email_customers', true);
    }
};
