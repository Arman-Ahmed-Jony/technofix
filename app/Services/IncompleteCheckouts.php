<?php

namespace App\Services;

use App\Enums\LeadStatus;
use App\Enums\LeadType;
use App\Models\CheckoutDraft;
use App\Models\Lead;
use App\Models\Order;
use App\Models\Product;
use App\Settings\DeliverySettings;
use App\Settings\OrderSettings;
use App\Support\Phone;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Customers who start checkout but leave without ordering. The checkout page saves what they have typed once the
 * mobile number is valid (a draft per visit); drafts left for OrderSettings::$incomplete_checkout_minutes become
 * "Incomplete checkout" leads (scheduler, `checkouts:capture`). Any order from that number closes the drafts and
 * marks their open leads Converted.
 */
class IncompleteCheckouts
{
    public const SESSION_KEY = 'checkout_draft';

    /** Drafts are deleted after this many days. */
    public const KEEP_DAYS = 30;

    /** An order this close before a draft still counts (the draft's last save can arrive after the order). */
    private const ORDER_OVERLAP_MINUTES = 10;

    public function __construct(private OrderSettings $settings, private DeliverySettings $delivery) {}

    public function enabled(): bool
    {
        return $this->settings->capture_incomplete_checkouts;
    }

    /** Saves the checkout form as it is now. Items are priced from the catalog; nothing else is trusted. */
    public function save(Request $request): ?CheckoutDraft
    {
        $phone = Phone::normalize($request->input('phone'));
        $items = $this->items($request->input('items'));

        if (! $this->enabled() || ! Phone::isValid($phone) || $items === []) {
            return null;
        }

        $draft = CheckoutDraft::whereKey($request->session()->get(self::SESSION_KEY))->whereNull('order_id')->first()
            ?? new CheckoutDraft(['token' => Str::random(40)]);

        $email = trim((string) $request->input('email'));

        $draft->fill([
            'customer_id' => $request->user('customer')?->id,
            'name' => $this->text($request->input('name'), 120),
            'phone' => $phone,
            'email' => filter_var($email, FILTER_VALIDATE_EMAIL) ? Str::limit($email, 255, '') : null,
            'delivery_code' => $this->text($request->input('delivery'), 40),
            'district' => $this->text($request->input('district'), 60),
            'area' => $this->text($request->input('area'), 80),
            'address' => $this->text($request->input('address'), 255),
            'note' => $this->text($request->input('note'), 500),
            'items' => $items,
            'subtotal' => collect($items)->sum(fn (array $item) => $item['price'] * $item['qty']),
            'attribution' => $request->session()->get('attribution') ?? $draft->attribution,
            'ip' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 250, ''),
        ])->save();

        $request->session()->put(self::SESSION_KEY, $draft->id);

        return $draft;
    }

    /** An order was placed (website or staff): drafts for that number are done and open follow-ups are converted. */
    public function ordered(Order $order, ?int $draftId = null): void
    {
        CheckoutDraft::whereNull('order_id')
            ->where(fn ($query) => $query->where('phone', $order->phone)->when($draftId, fn ($query) => $query->orWhere('id', $draftId)))
            ->update(['order_id' => $order->id]);

        Lead::where('type', LeadType::IncompleteCheckout)
            ->where('phone', $order->phone)
            ->whereIn('status', [LeadStatus::New, LeadStatus::Contacted])
            ->where('created_at', '>=', now()->subDays(self::KEEP_DAYS))
            ->each(fn (Lead $lead) => $lead->update([
                'status' => LeadStatus::Converted,
                'notes' => trim($lead->notes."\nOrdered: {$order->number} (".now()->format('d M Y, h:i A').').'),
            ]));
    }

    /**
     * Copies drafts left without an order to Leads — a new lead, or the number's lead that is still New (a customer
     * who comes back and leaves again updates it). Returns how many drafts were copied.
     */
    public function capture(): int
    {
        CheckoutDraft::where('updated_at', '<', now()->subDays(self::KEEP_DAYS))->delete();

        if (! $this->enabled()) {
            return 0;
        }

        $copied = 0;

        CheckoutDraft::query()
            ->whereNull('order_id')
            ->where('updated_at', '<=', now()->subMinutes(max(5, $this->settings->incomplete_checkout_minutes)))
            ->where('updated_at', '>=', now()->subDays(2)) // older ones are stale (e.g. the setting was off)
            ->where(fn ($query) => $query->whereNull('captured_at')->orWhereColumn('updated_at', '>=', 'captured_at'))
            ->orderBy('id')
            ->each(function (CheckoutDraft $draft) use (&$copied) {
                $order = Order::where('phone', $draft->phone)
                    ->where('created_at', '>=', $draft->created_at->copy()->subMinutes(self::ORDER_OVERLAP_MINUTES))
                    ->latest('id')
                    ->first();

                if ($order) {
                    CheckoutDraft::withoutTimestamps(fn () => $draft->update(['order_id' => $order->id]));

                    return;
                }

                $lead = Lead::where('type', LeadType::IncompleteCheckout)
                    ->where('phone', $draft->phone)
                    ->where('status', LeadStatus::New)
                    ->where('created_at', '>=', now()->subDays(2))
                    ->latest('id')
                    ->first() ?? new Lead(['type' => LeadType::IncompleteCheckout]);

                $lead->fill($this->leadFields($draft))->save();

                CheckoutDraft::withoutTimestamps(fn () => $draft->update(['lead_id' => $lead->id, 'captured_at' => now()]));
                $copied++;
            });

        return $copied;
    }

    /** The staff follow-up message (Admin → Settings → Stock & orders) for WhatsApp. */
    public function message(CheckoutDraft $draft): string
    {
        return strtr($this->settings->incomplete_checkout_message, [
            '{name}' => $draft->name ?: 'there',
            '{items}' => Str::limit($draft->itemsSummary(), 160),
            '{link}' => $draft->link(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function leadFields(CheckoutDraft $draft): array
    {
        $count = collect($draft->items)->sum('qty');
        $option = $this->delivery->find($draft->delivery_code);

        return [
            'name' => Str::limit($draft->name ?: 'Customer', 100, ''),
            'phone' => $draft->phone,
            'email' => $draft->email,
            'subject' => 'Didn’t finish checkout — BDT '.number_format($draft->subtotal).' ('.$count.' '.Str::plural('item', $count).')',
            'message' => collect($draft->items)
                ->map(fn (array $item) => $item['qty'].' × '.$item['name'].(filled($item['color'] ?? null) ? ' ('.$item['color'].')' : '').' — BDT '.number_format($item['price'] * $item['qty']))
                ->push($draft->note ? "\nNote: {$draft->note}" : null)
                ->filter()
                ->join("\n"),
            'meta' => array_filter([
                'Cart value' => 'BDT '.number_format($draft->subtotal),
                'Delivery' => $option['label'] ?? null,
                'Location' => collect([$draft->address, $draft->area, $draft->district])->filter()->join(', ') ?: null,
                'Last active' => $draft->updated_at->format('d M Y, h:i A'),
                'Account' => $draft->customer_id ? 'Signed in' : null,
                'Cart link' => $draft->link(),
            ]),
            'page_url' => route('checkout'),
            'attribution' => $draft->attribution,
            'ip' => $draft->ip,
            'user_agent' => $draft->user_agent,
        ];
    }

    /**
     * Cart lines from the browser ({id, qty, color?}), priced from the catalog.
     *
     * @return array<int, array{id: int, name: string, color: ?string, price: int, qty: int}>
     */
    private function items(mixed $json): array
    {
        $rows = json_decode((string) $json, true);
        $rows = collect(is_array($rows) ? $rows : [])
            ->filter(fn ($row) => is_array($row) && is_numeric($row['id'] ?? null) && is_numeric($row['qty'] ?? null))
            ->take(50);
        $products = Product::query()->published()->with('colors')->findMany($rows->pluck('id')->map(fn ($id) => (int) $id))->keyBy('id');

        return $rows
            ->filter(fn (array $row) => ($product = $products->get((int) $row['id'])) && $product->sale_price !== null)
            ->map(fn (array $row) => [...$row, 'color' => rescue(fn () => $products[(int) $row['id']]->pickColor(is_string($row['color'] ?? null) ? $row['color'] : null, allowSoldOut: true), null, false)])
            ->groupBy(fn (array $row) => (int) $row['id'].'|'.$row['color'])
            ->map(function ($group) use ($products) {
                $product = $products[(int) $group->first()['id']];

                $line = ['id' => $product->id, 'name' => $product->name, 'color' => $group->first()['color'], 'price' => (int) $product->sale_price, 'qty' => min(99, max(1, (int) $group->sum('qty')))];

                return $line['color'] === null ? array_diff_key($line, ['color' => true]) : $line; // no color key for products without colors
            })
            ->values()
            ->all();
    }

    private function text(mixed $value, int $max): ?string
    {
        $value = trim((string) (is_string($value) ? $value : ''));

        return $value === '' ? null : Str::limit($value, $max, '');
    }
}
