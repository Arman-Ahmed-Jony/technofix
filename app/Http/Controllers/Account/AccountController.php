<?php

namespace App\Http\Controllers\Account;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Storefront\TrackOrderController;
use App\Models\Customer;
use App\Models\SavedBuild;
use App\Support\Phone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * The signed-in customer's area: overview, orders, profile and password.
 */
class AccountController extends Controller
{
    public function dashboard(Request $request): View
    {
        $customer = $this->customer($request);

        $open = array_map(fn (OrderStatus $s) => $s->value, array_filter(OrderStatus::cases(), fn (OrderStatus $s) => $s->isOpen()));
        $active = $customer->orders()->whereIn('status', $open)->with(['items.product.media', 'history'])->first();
        $delivered = $customer->orders()->where('status', OrderStatus::Delivered);

        return view('account.dashboard', [
            'customer' => $customer,
            'orders' => $customer->orders()->with('items.product.media')->withCount('items')->limit(4)->get(),
            'active' => $active,
            'steps' => $active ? TrackOrderController::viewData($active, [])['steps'] : null,
            'stats' => [
                'orders' => $customer->orders()->count(),
                'open' => $customer->orders()->whereIn('status', $open)->count(),
                'delivered' => (clone $delivered)->count(),
                'spent' => (int) (clone $delivered)->sum('total'),
                'builds' => $customer->savedBuilds()->count(),
            ],
            'builds' => $customer->savedBuilds()->limit(2)->get()->map(fn (SavedBuild $saved) => ['model' => $saved, 'build' => $saved->build()]),
            'address' => $customer->addresses()->first(),
        ]);
    }

    /** Status filters on My orders: key => [label, statuses]. */
    public const ORDER_FILTERS = [
        'open' => ['In progress', ['pending', 'confirmed', 'processing', 'shipped']],
        'delivered' => ['Delivered', ['delivered']],
        'cancelled' => ['Cancelled', ['cancelled', 'returned']],
    ];

    public function orders(Request $request): View
    {
        $customer = $this->customer($request);
        $filter = array_key_exists((string) $request->query('status'), self::ORDER_FILTERS) ? $request->query('status') : null;
        $counts = $customer->orders()->reorder()->selectRaw('status, COUNT(*) as n')->groupBy('status')->pluck('n', 'status');

        return view('account.orders', [
            'orders' => $customer->orders()
                ->when($filter, fn ($q) => $q->whereIn('status', self::ORDER_FILTERS[$filter][1]))
                ->with('items.product.media')->withCount('items')
                ->paginate(10)->withQueryString(),
            'filter' => $filter,
            'filters' => collect(self::ORDER_FILTERS)->map(fn (array $f) => ['label' => $f[0], 'count' => (int) $counts->only($f[1])->sum()])
                ->prepend(['label' => 'All', 'count' => (int) $counts->sum()], ''),
        ]);
    }

    public function order(Request $request, string $number): View
    {
        $order = $this->customer($request)->orders()->with('items.product', 'history')->where('number', $number)->firstOrFail();

        return view('storefront.track-order-show', TrackOrderController::viewData($order, [
            ['label' => 'My account', 'url' => route('account')],
            ['label' => 'Orders', 'url' => route('account.orders')],
            ['label' => $order->number, 'url' => null],
        ]));
    }

    public function profile(Request $request): View
    {
        return view('account.profile', ['customer' => $this->customer($request)]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $customer = $this->customer($request);
        $request->merge(['phone' => Phone::normalize($request->input('phone')), 'email' => Str::lower(trim((string) $request->input('email'))) ?: null]);

        $customer->update($request->validate([
            'name' => ['required', 'string', 'min:3', 'max:80'],
            'phone' => ['required', 'regex:'.Phone::PATTERN, Rule::unique('customers', 'phone')->ignore($customer)],
            'email' => ['nullable', 'email', 'max:120', Rule::unique('customers', 'email')->ignore($customer)],
        ], [
            'phone.regex' => 'Enter a valid Bangladeshi mobile number, e.g. 01712345678.',
            'phone.unique' => 'Another account already uses this mobile number.',
            'email.unique' => 'Another account already uses this email.',
        ]));

        return back()->with('status', 'Your details were saved.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validateWithBag('password', [
            'current_password' => ['required', 'current_password:customer'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], ['current_password.current_password' => 'That isn’t your current password.']);

        $this->customer($request)->update(['password' => $request->input('password')]);

        return back()->with('status', 'Your password was changed.');
    }

    protected function customer(Request $request): Customer
    {
        return $request->user('customer');
    }
}
