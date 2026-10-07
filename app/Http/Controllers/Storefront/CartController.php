<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Settings\DeliverySettings;
use App\Support\Catalog;
use Illuminate\View\View;

/**
 * The cart lives in the browser; this page renders it and suggests add-ons.
 */
class CartController extends Controller
{
    public function __invoke(Catalog $catalog, DeliverySettings $delivery): View
    {
        return view('storefront.cart', [
            'suggestions' => $catalog->flagged('best', 10),
            'freeOver' => $delivery->free_delivery_over,
            'lowestFee' => collect($delivery->active())->where('type', 'delivery')->pluck('fee')->min(),
        ]);
    }
}
