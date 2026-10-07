<?php

namespace App\Http\Requests\Storefront;

use App\Enums\LeadType;
use App\Enums\ProductStatus;
use App\Support\Blocklist;
use App\Support\Phone;
use App\Support\Turnstile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Every storefront enquiry form (all become leads in Admin → Leads). `website` is a honeypot (hidden from people).
 *
 * - contact · service_request: contact page, repair pages
 * - quote: /request-quote (a business or reseller buyer is filed as Corporate / bulk)
 * - site_visit: /site-visit
 * - call_for_price · stock_alert: product page (need `product_id`)
 */
class EnquiryRequest extends FormRequest
{
    /** Options shown on the forms (also validated). */
    public const BUYERS = ['personal' => 'Myself / home', 'business' => 'A company or office', 'reseller' => 'I resell (shop / dealer)'];

    public const SITE_SERVICES = ['CCTV / security cameras', 'Networking & WiFi', 'Access control & attendance', 'Intercom / PABX', 'Other'];

    public const PROPERTIES = ['Home / flat', 'Shop / showroom', 'Office', 'Factory / warehouse', 'Other'];

    public const TIME_SLOTS = ['Morning (10–1)', 'Afternoon (1–5)', 'Evening (5–8)'];

    public const TYPES = ['contact', 'service_request', 'quote', 'site_visit', 'call_for_price', 'stock_alert'];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['phone' => Phone::normalize($this->input('phone'))]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $type = $this->input('type');
        $productForm = in_array($type, ['call_for_price', 'stock_alert'], true);

        return [
            'type' => ['required', Rule::in(self::TYPES)],
            'name' => [$type === 'stock_alert' ? 'nullable' : 'required', 'string', 'min:2', 'max:100'],
            'phone' => ['required', 'regex:'.Phone::PATTERN, Blocklist::rule()],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'string'],
            ...Turnstile::rules('enquiry'),

            // contact / repair
            'subject' => ['nullable', 'string', 'max:150'],
            'service_id' => ['nullable', 'required_if:type,service_request', Rule::exists('services', 'id')->where('is_active', true)],
            'device' => ['nullable', 'string', 'max:120'],
            'message' => [in_array($type, ['contact', 'service_request'], true) ? 'required' : 'nullable', 'string', 'min:10', 'max:2000'],

            // quote
            'buyer' => ['nullable', 'required_if:type,quote', Rule::in(array_keys(self::BUYERS))],
            'company' => ['nullable', 'string', 'max:120', Rule::requiredIf($type === 'quote' && in_array($this->input('buyer'), ['business', 'reseller'], true))],
            'items' => ['nullable', 'required_if:type,quote', 'string', 'min:5', 'max:2000'],
            'budget' => ['nullable', 'string', 'max:60'],
            'district' => ['nullable', 'required_if:type,site_visit', 'string', 'max:60'],

            // site visit
            'site_service' => ['nullable', 'required_if:type,site_visit', Rule::in(self::SITE_SERVICES)],
            'property' => ['nullable', 'required_if:type,site_visit', Rule::in(self::PROPERTIES)],
            'area' => ['nullable', 'required_if:type,site_visit', 'string', 'max:80'],
            'address' => ['nullable', 'string', 'max:255'],
            'preferred_date' => ['nullable', 'date', 'after_or_equal:today'],
            'time_slot' => ['nullable', Rule::in(self::TIME_SLOTS)],

            // product page
            'product_id' => [$productForm ? 'required' : 'nullable', Rule::exists('products', 'id')->where('status', ProductStatus::Published->value)],
        ];
    }

    /** Product-page forms: come back to the price box, not the top of the page. */
    protected function getRedirectUrl(): string
    {
        $url = parent::getRedirectUrl();

        return in_array($this->input('type'), ['call_for_price', 'stock_alert'], true) ? $url.'#price-box' : $url;
    }

    public function leadType(): LeadType
    {
        return match ($this->input('type')) {
            'quote' => in_array($this->input('buyer'), ['business', 'reseller'], true) ? LeadType::Corporate : LeadType::Quote,
            'site_visit' => LeadType::SiteVisit,
            'call_for_price' => LeadType::CallForPrice,
            'stock_alert' => LeadType::StockAlert,
            default => LeadType::from($this->input('type')),
        };
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...Turnstile::messages(),
            'name.required' => 'Please enter your name.',
            'phone.required' => 'Please enter your mobile number.',
            'phone.regex' => 'Enter a valid Bangladeshi mobile number, e.g. 01712345678.',
            'service_id.required_if' => 'Please choose the service you need.',
            'message.required' => 'Please tell us a little about what you need.',
            'message.min' => 'Please add a little more detail (at least 10 characters).',
            'buyer.required_if' => 'Please tell us who you’re buying for.',
            'company.required' => 'Please enter the company or shop name.',
            'items.required_if' => 'Please list what you need (products and quantities).',
            'items.min' => 'Please add a little more detail.',
            'site_service.required_if' => 'Please choose what you need installed.',
            'property.required_if' => 'Please choose the type of place.',
            'district.required_if' => 'Please choose your district.',
            'area.required_if' => 'Please enter your area.',
            'preferred_date.after_or_equal' => 'Please pick today or a later date.',
        ];
    }
}
