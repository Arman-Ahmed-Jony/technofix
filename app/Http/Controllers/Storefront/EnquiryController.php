<?php

namespace App\Http\Controllers\Storefront;

use App\Enums\LeadType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\EnquiryRequest;
use App\Models\Lead;
use App\Models\Product;
use App\Models\Service;
use App\Models\StockAlert;
use App\Support\Catalog;
use App\Support\Tracking;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Contact, repair, quote and site-visit pages + saving every storefront enquiry into Admin → Leads.
 */
class EnquiryController extends Controller
{
    public function contact(): View
    {
        return view('storefront.contact');
    }

    /** /request-quote — optionally started from a product page (?product=slug). */
    public function quote(Request $request, Catalog $catalog): View
    {
        return view('storefront.request-quote', [
            'product' => $request->filled('product') ? $catalog->product((string) $request->query('product')) : null,
        ]);
    }

    public function siteVisit(): View
    {
        return view('storefront.site-visit', ['districts' => Arr::flatten(require database_path('data/bd-districts.php'))]);
    }

    public function store(EnquiryRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Honeypot filled in → almost certainly a bot. Pretend it worked, store nothing.
        if (filled($data['website'] ?? null)) {
            return $this->sent($request);
        }

        $type = $request->leadType();
        $product = filled($data['product_id'] ?? null) ? Product::find($data['product_id']) : null;
        $service = $type === LeadType::ServiceRequest ? Service::find($data['service_id']) : null;

        $lead = Lead::create([
            'type' => $type,
            'name' => $data['name'] ?? 'Customer',
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
            'subject' => Str::limit((string) $this->subject($type, $data, $service, $product), 147) ?: null,
            'message' => $this->message($type, $data),
            'meta' => array_filter([
                'Service' => $service?->name,
                'Device / model' => $data['device'] ?? null,
                'Buying for' => isset($data['buyer']) ? EnquiryRequest::BUYERS[$data['buyer']] : null,
                'Company / shop' => $data['company'] ?? null,
                'Budget' => $data['budget'] ?? null,
                'Install' => $data['site_service'] ?? null,
                'Place' => $data['property'] ?? null,
                'Location' => collect([$data['address'] ?? null, $data['area'] ?? null, $data['district'] ?? null])->filter()->join(', ') ?: null,
                'Preferred visit' => collect([isset($data['preferred_date']) ? Carbon::parse($data['preferred_date'])->format('D j M Y') : null, $data['time_slot'] ?? null])->filter()->join(' · ') ?: null,
                'Product' => $product?->name,
                'Product link' => $product?->url,
                'Price shown' => $product ? ($product->sale_price ? 'BDT '.number_format($product->sale_price) : 'Call for price') : null,
            ]) ?: null,
            'page_url' => Str::limit((string) url()->previous(), 500, ''),
            'attribution' => Tracking::attribution($request),
            'ip' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 500, ''),
        ]);
        app(Tracking::class)->lead($lead, $request);

        if ($type === LeadType::StockAlert && $product) {
            StockAlert::create([
                'product_id' => $product->id, 'lead_id' => $lead->id,
                'name' => $data['name'] ?? null, 'phone' => $data['phone'], 'email' => $data['email'] ?? null,
            ]);
        }

        return $this->sent($request);
    }

    /** Back to the form with a thank-you (product forms land back on their card). */
    protected function sent(Request $request): RedirectResponse
    {
        $anchor = in_array($request->input('type'), ['call_for_price', 'stock_alert'], true) ? '#price-box' : '';

        return redirect()->to(url()->previous().$anchor)->with('enquiry_sent', $request->input('type'));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function subject(LeadType $type, array $data, ?Service $service, ?Product $product): ?string
    {
        return match ($type) {
            LeadType::ServiceRequest => $service ? "Service: {$service->name}" : null,
            LeadType::Quote, LeadType::Corporate => ($type === LeadType::Corporate ? 'Bulk / corporate quote' : 'Quote request').(filled($data['company'] ?? null) ? ' — '.$data['company'] : ''),
            LeadType::SiteVisit => 'Site visit: '.$data['site_service'].' ('.$data['area'].', '.$data['district'].')',
            LeadType::CallForPrice => 'Price request: '.$product?->name,
            LeadType::StockAlert => 'Notify when available: '.$product?->name,
            default => $data['subject'] ?? null,
        };
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function message(LeadType $type, array $data): string
    {
        return match ($type) {
            LeadType::Quote, LeadType::Corporate => trim($data['items']."\n\n".($data['message'] ?? '')),
            default => (string) ($data['message'] ?? ''),
        };
    }
}
