<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\CustomerAddress;
use App\Support\Phone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Saved delivery addresses (used to fill in checkout).
 */
class AddressController extends Controller
{
    public function index(Request $request): View
    {
        return view('account.addresses', ['addresses' => $request->user('customer')->addresses]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $customer = $request->user('customer');

        if ($customer->addresses()->count() >= CustomerAddress::MAX_PER_CUSTOMER) {
            return redirect()->route('account.addresses')->with('status', 'You can save up to '.CustomerAddress::MAX_PER_CUSTOMER.' addresses. Delete one to add another.');
        }

        return view('account.address-form', ['address' => new CustomerAddress(['name' => $customer->name, 'phone' => $customer->phone])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $customer = $request->user('customer');
        abort_if($customer->addresses()->count() >= CustomerAddress::MAX_PER_CUSTOMER, 422);

        $customer->addresses()->create($this->validated($request));

        return redirect()->route('account.addresses')->with('status', 'Address saved.');
    }

    public function edit(Request $request, CustomerAddress $address): View
    {
        $this->authorizeAddress($request, $address);

        return view('account.address-form', ['address' => $address]);
    }

    public function update(Request $request, CustomerAddress $address): RedirectResponse
    {
        $this->authorizeAddress($request, $address);
        $address->update($this->validated($request));

        return redirect()->route('account.addresses')->with('status', 'Address updated.');
    }

    public function destroy(Request $request, CustomerAddress $address): RedirectResponse
    {
        $this->authorizeAddress($request, $address);
        $address->delete();

        return redirect()->route('account.addresses')->with('status', 'Address deleted.');
    }

    public function makeDefault(Request $request, CustomerAddress $address): RedirectResponse
    {
        $this->authorizeAddress($request, $address);
        $address->update(['is_default' => true]);

        return redirect()->route('account.addresses');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request): array
    {
        $request->merge(['phone' => Phone::normalize($request->input('phone'))]);

        return [
            ...$request->validate([
                'label' => ['nullable', 'string', 'max:40'],
                'name' => ['required', 'string', 'min:3', 'max:120'],
                'phone' => ['required', 'regex:'.Phone::PATTERN],
                'district' => ['required', Rule::in(Arr::flatten(require database_path('data/bd-districts.php')))],
                'area' => ['nullable', 'string', 'max:80'],
                'address' => ['required', 'string', 'min:8', 'max:255'],
            ], [
                'phone.regex' => 'Enter a valid Bangladeshi mobile number, e.g. 01712345678.',
                'address.min' => 'Please add a bit more detail (house, road, landmark).',
            ]),
            'is_default' => $request->boolean('is_default'),
        ];
    }

    protected function authorizeAddress(Request $request, CustomerAddress $address): void
    {
        abort_unless($address->customer_id === $request->user('customer')->id, 404);
    }
}
