<?php

namespace App\Filament\Widgets;

use App\Enums\LeadStatus;
use App\Enums\OrderStatus;
use App\Enums\StaffArea;
use App\Filament\Resources\Leads\LeadResource;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Products\ProductResource;
use App\Models\Lead;
use App\Models\Order;
use App\Support\SalesReport;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class WelcomeBanner extends Widget
{
    protected string $view = 'filament.widgets.welcome-banner';

    protected static ?int $sort = -3;

    protected int|string|array $columnSpan = 'full';

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $hour = now()->hour;
        $today = SalesReport::between(Carbon::today(), now());

        return [
            'greeting' => $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening'),
            'name' => Str::before((string) auth()->user()?->name, ' ') ?: 'there',
            'date' => now()->format('l, j F Y'),
            'newOrders' => Order::where('status', OrderStatus::Pending)->count(),
            'newLeads' => Lead::where('status', LeadStatus::New)->count(),
            'today' => $today,
            // Only what this staff member's role opens (Admin → Settings → Roles).
            'can' => [
                'orders' => StaffArea::Orders->allowed(),
                'leads' => StaffArea::Leads->allowed(),
                'catalog' => StaffArea::Catalog->allowed(),
                'reports' => StaffArea::Reports->allowed(),
            ],
            'urls' => [
                'newOrders' => OrderResource::getUrl('index', ['tab' => 'new']),
                'leads' => LeadResource::getUrl('index'),
                'createOrder' => OrderResource::getUrl('create'),
                'createProduct' => ProductResource::getUrl('create'),
                'store' => url('/'),
            ],
        ];
    }
}
