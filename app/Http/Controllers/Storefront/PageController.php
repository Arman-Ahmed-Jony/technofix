<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\View\View;

class PageController extends Controller
{
    public function show(string $slug): View
    {
        $page = Page::published()->where('slug', $slug)->firstOrFail();

        return view('storefront.page', [
            'page' => $page,
            'siblings' => Page::published()->whereNotNull('footer_group')->orderBy('sort')->get(['title', 'slug', 'footer_group']),
        ]);
    }
}
