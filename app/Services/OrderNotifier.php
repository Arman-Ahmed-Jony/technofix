<?php

namespace App\Services;

use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Mail\NewOrderForStaff;
use App\Mail\OrderConfirmation;
use App\Mail\OrderStatusUpdate;
use App\Models\Order;
use App\Settings\OrderSettings;
use Illuminate\Support\Facades\Mail;

/**
 * Order emails (queued; they go out when the cron-driven queue worker runs). Who gets what is set in
 * Admin → Settings → Stock & orders.
 */
class OrderNotifier
{
    public function __construct(protected OrderSettings $settings) {}

    public function placed(Order $order): void
    {
        // Staff already know about orders they took themselves.
        $staff = array_values(array_filter($this->settings->notify_emails ?? []));
        if ($order->source === OrderSource::Web && $staff) {
            Mail::to($staff)->queue(new NewOrderForStaff($order));
        }

        if ($this->emailsCustomer($order)) {
            Mail::to($order->email)->queue(new OrderConfirmation($order));
        }
    }

    public function statusChanged(Order $order, OrderStatus $status): void
    {
        if (array_key_exists($status->value, OrderStatusUpdate::SUBJECTS) && $this->emailsCustomer($order)) {
            Mail::to($order->email)->queue(new OrderStatusUpdate($order, $status));
        }
    }

    protected function emailsCustomer(Order $order): bool
    {
        return $this->settings->email_customers && filled($order->email);
    }
}
