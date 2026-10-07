<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Support\HomePage;
use Illuminate\View\View;

class HomeController extends Controller
{
    /** The sections from Admin → Content → Home page, in order (App\Support\HomePage). */
    public function __invoke(HomePage $home): View
    {
        return view('storefront.home', ['sections' => $home->sections()]);
    }
}
