<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Order handling — edited in Admin → Settings → Orders.
 */
class OrderSettings extends Settings
{
    /**
     * Switch products to "Out of Stock" when orders use up their quantity (and back to "In Stock" when cancelled
     * orders or staff add stock). Off by default: turn on once product quantities are kept up to date.
     */
    public bool $auto_stock_status;

    /** A product counts as "low stock" at this quantity or less (dashboard, stock overview). */
    public int $low_stock_at;

    /** Staff addresses that get a "New order" email (empty = nobody). */
    public array $notify_emails;

    /** Email customers (who gave an address) their confirmation and status updates. */
    public bool $email_customers;

    /** Keep what visitors type at checkout, and list those who leave without ordering in Leads (App\Services\IncompleteCheckouts). */
    public bool $capture_incomplete_checkouts;

    /** Minutes without an order before an incomplete checkout goes to Leads. */
    public int $incomplete_checkout_minutes;

    /** WhatsApp text for following up an incomplete checkout: {name}, {items}, {link}. */
    public string $incomplete_checkout_message;

    public static function group(): string
    {
        return 'orders';
    }
}
