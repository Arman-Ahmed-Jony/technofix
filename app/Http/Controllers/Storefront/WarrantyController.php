<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Services\WarrantyDesk;
use App\Settings\WarrantySettings;
use App\Support\Phone;
use App\Support\Turnstile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Customers check their warranty claims with their mobile number. The number is kept in the session only, so the
 * results page (/warranty/status) opens for whoever typed it — never from a link.
 */
class WarrantyController extends Controller
{
    public const SESSION_KEY = 'warranty_phone';

    public function form(WarrantySettings $settings): View
    {
        return view('storefront.warranty', ['settings' => $settings]);
    }

    public function lookup(Request $request, WarrantySettings $settings): RedirectResponse
    {
        abort_unless($settings->lookup_enabled, 404);

        $request->merge(['phone' => Phone::normalize($request->input('phone'))]);
        $request->validate(
            ['phone' => ['required', 'regex:'.Phone::PATTERN], ...Turnstile::rules('warranty')],
            [...Turnstile::messages(), 'phone.required' => 'Enter the mobile number you gave us.', 'phone.regex' => 'Enter a valid Bangladeshi mobile number, e.g. 01712345678.'],
        );

        $request->session()->put(self::SESSION_KEY, $request->input('phone'));

        return redirect()->route('warranty.status');
    }

    public function status(Request $request, WarrantySettings $settings, WarrantyDesk $desk): View|RedirectResponse
    {
        $phone = $request->session()->get(self::SESSION_KEY);

        if (! $settings->lookup_enabled || ! $phone) {
            return redirect()->route('warranty');
        }

        return view('storefront.warranty-status', [
            'phone' => $phone,
            'claims' => $desk->lookup($phone),
        ]);
    }
}
