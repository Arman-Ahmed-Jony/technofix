<?php

/*
| Technical settings only. Everything the owner manages (contact details, social links,
| delivery charges…) lives in the database and is edited in Admin → Settings
| (see app/Settings/*).
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Admin Panel Path
    |--------------------------------------------------------------------------
    |
    | URL path of the Filament control panel. Use a non-obvious value in
    | production (e.g. "tf-panel") so the login page is harder to find.
    |
    */

    'admin_path' => env('ADMIN_PATH', 'admin'),

    /*
    |--------------------------------------------------------------------------
    | Page cache (App\Support\PageCache)
    |--------------------------------------------------------------------------
    |
    | Finished storefront pages are kept for guests, so most visits skip the
    | database. Signed-in visitors and pages with form messages are never
    | cached. Any change in the admin clears it.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Trusted proxies
    |--------------------------------------------------------------------------
    |
    | Who may tell the app the visitor's real address and https (X-Forwarded-*).
    | "cloudflare" = Cloudflare's ranges (site behind Cloudflare), a comma list of
    | addresses, or empty = nobody. Never "*" on shared hosting: visitors could
    | fake their address and slip past rate limits and the blocklist.
    |
    */

    'trusted_proxies' => env('TRUSTED_PROXIES', ''),

    'cloudflare_ips' => [
        '173.245.48.0/20', '103.21.244.0/22', '103.22.200.0/22', '103.31.4.0/22', '141.101.64.0/18', '108.162.192.0/18',
        '190.93.240.0/20', '188.114.96.0/20', '197.234.240.0/22', '198.41.128.0/17', '162.158.0.0/15', '104.16.0.0/13',
        '104.24.0.0/14', '172.64.0.0/13', '131.0.72.0/22',
        '2400:cb00::/32', '2606:4700::/32', '2803:f800::/32', '2405:b500::/32', '2405:8100::/32', '2a06:98c0::/29', '2c0f:f248::/32',
    ],

    'page_cache' => [
        'enabled' => (bool) env('PAGE_CACHE', true),
        'minutes' => (int) env('PAGE_CACHE_MINUTES', 30),
        'store' => 'pages',
    ],

];
