<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\View\View;

class FaqController extends Controller
{
    public function __invoke(): View
    {
        return view('storefront.faq', [
            'groups' => Faq::active()->get()->groupBy(fn (Faq $faq) => $faq->group ?: 'General'),
        ]);
    }
}
