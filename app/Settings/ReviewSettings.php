<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/** Product reviews and questions — Admin → Settings → Reviews & questions. Used by App\Services\ProductFeedback. */
class ReviewSettings extends Settings
{
    public bool $reviews_enabled;

    public bool $questions_enabled;

    /** Only customers with a delivered order of the product may review it. */
    public bool $buyers_only;

    /** Email the order recipients (Settings → Stock & orders) about new reviews and questions. */
    public bool $notify_staff;

    /** Email customers a few days after delivery, asking them to review what they bought. */
    public bool $request_reviews;

    public int $request_after_days;

    public static function group(): string
    {
        return 'reviews';
    }
}
