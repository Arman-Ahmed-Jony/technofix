<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('reviews.reviews_enabled', true);
        $this->migrator->add('reviews.questions_enabled', true);
        $this->migrator->add('reviews.buyers_only', true);
        $this->migrator->add('reviews.notify_staff', true);
        $this->migrator->add('reviews.request_reviews', true);
        $this->migrator->add('reviews.request_after_days', 3);
    }
};
