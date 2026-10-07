<?php

namespace App\Console\Commands;

use App\Services\IncompleteCheckouts;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('checkouts:capture')]
#[Description('Copy checkouts left without an order to Leads (runs every 5 minutes)')]
class CaptureIncompleteCheckouts extends Command
{
    public function handle(IncompleteCheckouts $checkouts): int
    {
        $copied = $checkouts->capture();
        $this->info($copied.' incomplete '.str('checkout')->plural($copied).' copied to Leads.');

        return self::SUCCESS;
    }
}
