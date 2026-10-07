<?php

namespace App\Jobs;

use App\Settings\TrackingSettings;
use App\Support\Tracking;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sends one server-side event to Meta's Conversions API (built by App\Support\Tracking). Queued, so the customer never
 * waits for Facebook; retried when Meta is down, logged (not retried) when Meta rejects it.
 */
class SendMetaEvent implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [60, 300];

    public function __construct(public array $event)
    {
        $this->afterCommit();
    }

    public function handle(TrackingSettings $settings): void
    {
        if (blank($settings->meta_pixel_id) || blank($settings->meta_capi_token)) {
            return;
        }

        $response = Http::asJson()->timeout(15)->post(
            'https://graph.facebook.com/'.Tracking::GRAPH_VERSION.'/'.$settings->meta_pixel_id.'/events',
            array_filter([
                'data' => [$this->event],
                'access_token' => $settings->meta_capi_token,
                'test_event_code' => $settings->meta_test_event_code ?: null,
            ]),
        );

        if ($response->serverError()) {
            $response->throw(); // retry later
        }

        if ($response->failed()) {
            Log::warning('Meta Conversions API rejected an event', [
                'event' => $this->event['event_name'] ?? null,
                'error' => $response->json('error.message') ?? $response->body(),
            ]);
        }
    }
}
