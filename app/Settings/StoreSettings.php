<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Store identity and contact details — edited in Admin → Settings → Store & contact.
 */
class StoreSettings extends Settings
{
    public string $hotline;

    /** International format without "+", e.g. 8801712345678 (null = no WhatsApp buttons). */
    public ?string $whatsapp;

    public string $email;

    public string $address;

    public string $hours;

    public ?string $facebook;

    public ?string $youtube;

    public ?string $instagram;

    public ?string $tiktok;

    /** More numbers besides the hotline: [{label, number}] (footer, contact page, maintenance page). */
    public array $extra_phones;

    /** On invoices: the registered business name, trade licence and VAT BIN, and the closing text. */
    public string $legal_name;

    public ?string $trade_licence;

    public ?string $bin;

    public ?string $invoice_footer;

    public static function group(): string
    {
        return 'store';
    }

    public function telHref(): string
    {
        return 'tel:'.preg_replace('/[^0-9+]/', '', $this->hotline);
    }

    /**
     * Every number to show: the hotline first, then the extra ones.
     *
     * @return list<array{label: string, number: string, href: string}>
     */
    public function phones(): array
    {
        return collect([['label' => 'Hotline', 'number' => $this->hotline], ...($this->extra_phones ?? [])])
            ->filter(fn ($phone) => filled($phone['number'] ?? null))
            ->map(fn ($phone) => ['label' => (string) ($phone['label'] ?? '') ?: 'Phone', 'number' => $phone['number'], 'href' => 'tel:'.preg_replace('/[^0-9+]/', '', $phone['number'])])
            ->values()
            ->all();
    }

    /**
     * WhatsApp chat link (optionally with a pre-filled message), or the contact page when no number is set.
     */
    public function whatsappUrl(?string $message = null): string
    {
        if (! $this->whatsapp) {
            return url('contact');
        }

        return 'https://wa.me/'.$this->whatsapp.($message ? '?text='.rawurlencode($message) : '');
    }

    /**
     * @return array<string, string> network => URL, only the ones that are filled in
     */
    public function socialLinks(): array
    {
        return array_filter([
            'facebook' => $this->facebook,
            'youtube' => $this->youtube,
            'instagram' => $this->instagram,
            'tiktok' => $this->tiktok,
        ]);
    }

    /**
     * "01712 345678", "+880 1712-345678" → "8801712345678"; null when it isn't a Bangladeshi mobile number.
     */
    public static function normalizeWhatsapp(?string $number): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $number);

        if (preg_match('/^(?:880|0)?(1[3-9]\d{8})$/', $digits, $m)) {
            return '880'.$m[1];
        }

        return null;
    }
}
