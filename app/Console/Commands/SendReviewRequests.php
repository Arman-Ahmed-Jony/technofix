<?php

namespace App\Console\Commands;

use App\Services\ProductFeedback;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('reviews:request')]
#[Description('Email customers a few days after delivery, asking them to review what they bought (runs daily)')]
class SendReviewRequests extends Command
{
    public function handle(ProductFeedback $feedback): int
    {
        $sent = $feedback->sendReviewRequests();
        $this->info($sent.' review '.str('request')->plural($sent).' queued.');

        return self::SUCCESS;
    }
}
