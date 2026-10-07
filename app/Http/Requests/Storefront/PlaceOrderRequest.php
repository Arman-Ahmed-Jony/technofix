<?php

namespace App\Http\Requests\Storefront;

use App\Models\Product;
use App\Payments\PaymentGateways;
use App\Settings\DeliverySettings;
use App\Support\Blocklist;
use App\Support\Phone;
use App\Support\Turnstile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class PlaceOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalise the phone number and decode the cart (sent as JSON by the prototype's browser cart).
     */
    protected function prepareForValidation(): void
    {
        $items = json_decode((string) $this->input('items', ''), true);

        $this->merge([
            'phone' => Phone::normalize($this->input('phone')),
            'items' => is_array($items) ? $items : [],
        ]);
    }

    /**
     * The delivery option the customer picked (admin-managed), if it exists and is active.
     *
     * @return array<string, mixed>|null
     */
    public function deliveryOption(): ?array
    {
        return app(DeliverySettings::class)->find($this->input('delivery'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $districts = Arr::flatten(require database_path('data/bd-districts.php'));
        $needsAddress = fn () => ($this->deliveryOption()['type'] ?? 'delivery') !== 'pickup';

        return [
            'name' => ['required', 'string', 'min:3', 'max:80'],
            'phone' => ['required', 'regex:'.Phone::PATTERN, Blocklist::rule()],
            'email' => ['nullable', 'email', 'max:120'],
            'delivery' => ['required', Rule::in(array_keys(app(DeliverySettings::class)->active()))],
            'district' => ['nullable', Rule::requiredIf($needsAddress), Rule::in($districts)],
            'area' => ['nullable', Rule::requiredIf($needsAddress), 'string', 'max:80'],
            'address' => ['nullable', Rule::requiredIf($needsAddress), 'string', 'min:8', 'max:255'],
            'note' => ['nullable', 'string', 'max:500'],
            'save_address' => ['nullable', 'boolean'],
            'coupon' => ['nullable', 'string', 'max:40'], // checked and priced by OrderService
            'payment' => ['required', Rule::in(app(PaymentGateways::class)->codes())],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.id' => ['required', 'integer'],
            'items.*.qty' => ['required', 'integer', 'min:1', 'max:99'],
            'items.*.color' => ['nullable', 'string', 'max:40'],
            ...Turnstile::rules('checkout'),
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $option = $this->deliveryOption();
                $district = $this->input('district');

                if ($option && is_string($district) && ! DeliverySettings::serves($option, $district)) {
                    $validator->errors()->add('delivery', "{$option['label']} isn’t available for {$district}. Please choose another delivery option.");
                }

                $items = array_filter((array) $this->input('items'), fn ($item) => is_array($item) && isset($item['id']) && is_numeric($item['id']));
                $products = Product::query()->published()->with('colors')->findMany(array_map(fn ($item) => (int) $item['id'], $items))->keyBy('id');

                foreach ((array) $this->input('items') as $item) {
                    $product = is_array($item) && isset($item['id']) && is_numeric($item['id']) ? $products->get((int) $item['id']) : null;

                    if (! $product || ! $product->stock->isPurchasable() || $product->sale_price === null) {
                        $validator->errors()->add('items', 'One of the items in your cart is no longer available. Please review your cart.');
                        break;
                    }

                    // The chosen color must still be offered (an empty choice gets the first available color).
                    try {
                        $product->pickColor(is_string($item['color'] ?? null) ? $item['color'] : null);
                    } catch (\InvalidArgumentException $e) {
                        $validator->errors()->add('items', $e->getMessage());
                        break;
                    }
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...Turnstile::messages(),
            'name.required' => 'Please enter your full name.',
            'phone.required' => 'Please enter your mobile number.',
            'phone.regex' => 'Enter a valid Bangladeshi mobile number, e.g. 01712345678.',
            'delivery.required' => 'Please choose a delivery option.',
            'delivery.in' => 'Please choose a delivery option.',
            'district.required' => 'Please choose your district.',
            'area.required' => 'Please enter your area or thana.',
            'address.required' => 'Please enter your full delivery address.',
            'address.min' => 'Please add a bit more detail (house, road, landmark).',
            'items.required' => 'Your cart is empty.',
            'items.min' => 'Your cart is empty.',
            'payment.required' => 'Please choose how you’d like to pay.',
            'payment.in' => 'That payment method isn’t available right now. Please choose another.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['phone' => 'mobile number', 'area' => 'area / thana'];
    }
}
