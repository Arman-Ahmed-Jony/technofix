<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Models\Service;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function index(): View
    {
        return view('storefront.services.index', [
            'services' => Service::active()->get(),
            'faqs' => Faq::active()->where('group', 'like', '%repair%')->get(),
        ]);
    }

    public function show(string $slug): View
    {
        $services = Service::active()->get();

        return view('storefront.services.show', [
            'service' => $services->firstWhere('slug', $slug) ?? abort(404),
            'services' => $services,
        ]);
    }
}
