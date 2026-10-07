<?php

namespace App\Enums;

use App\Models;
use App\Models\User;

/**
 * The parts of the admin a role can open (Admin → Settings → Roles). Resources are guarded through their model
 * (Gate::before in AppServiceProvider, so every list, form, action and URL is covered); settings pages and dashboard
 * widgets call allowed() in canAccess() / canView().
 */
enum StaffArea: string
{
    case Orders = 'orders';
    case Warranty = 'warranty';
    case Leads = 'leads';
    case Customers = 'customers';
    case Marketing = 'marketing';
    case Catalog = 'catalog';
    case Reviews = 'reviews';
    case Inventory = 'inventory';
    case Content = 'content';
    case Seo = 'seo';
    case Reports = 'reports';
    case Settings = 'settings';
    case Staff = 'staff';
    case Activity = 'activity';

    public function label(): string
    {
        return match ($this) {
            self::Orders => 'Orders',
            self::Warranty => 'Warranty claims',
            self::Leads => 'Leads & enquiries',
            self::Customers => 'Customers',
            self::Marketing => 'Coupons & flash sales',
            self::Catalog => 'Catalog',
            self::Reviews => 'Reviews & questions',
            self::Inventory => 'Inventory',
            self::Content => 'Website content',
            self::Seo => 'SEO & tracking',
            self::Reports => 'Sales figures',
            self::Settings => 'Store settings',
            self::Staff => 'Staff & roles',
            self::Activity => 'Activity log',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Orders => 'See and manage orders: take phone orders, change status, record payments, print invoices.',
            self::Warranty => 'The warranty list: add claims, update their status and notes (customers get an email), Excel download and import.',
            self::Leads => 'Enquiries, quote and site-visit requests, incomplete checkouts, stock alerts.',
            self::Customers => 'Customer accounts, their addresses and saved PC builds.',
            self::Marketing => 'Coupon codes and flash sales / Happy Hour.',
            self::Catalog => 'Products (prices, stock, photos), categories, brands, spec fields, PC Builder, Excel import.',
            self::Reviews => 'Publish or hide customer reviews, reply to them and answer product questions. No access to prices or products.',
            self::Inventory => 'Stock-in from suppliers, stock adjustments with a reason, stock movements, stock value.',
            self::Content => 'Home page, banners, pages, repair services, FAQs, buying guide.',
            self::Seo => 'SEO & sharing, redirects, broken links, tracking & pixels.',
            self::Reports => 'Sales totals and charts on the dashboard.',
            self::Settings => 'Store & contact, delivery charges, stock & orders, payments.',
            self::Staff => 'Add staff, change their roles, switch accounts off.',
            self::Activity => 'Who changed what and when: prices, stock, orders, settings, staff.',
        };
    }

    /**
     * Models that belong to each area (their resources, relation managers and actions follow the area).
     *
     * @return list<class-string>
     */
    public function models(): array
    {
        return match ($this) {
            self::Orders => [Models\Order::class, Models\OrderItem::class, Models\OrderStatusHistory::class, Models\Payment::class],
            self::Warranty => [Models\WarrantyClaim::class, Models\WarrantyClaimEvent::class],
            self::Leads => [Models\Lead::class, Models\CheckoutDraft::class, Models\StockAlert::class],
            self::Customers => [Models\Customer::class, Models\CustomerAddress::class, Models\SavedBuild::class],
            self::Marketing => [Models\Coupon::class, Models\Campaign::class, Models\CampaignProduct::class],
            self::Catalog => [Models\Product::class, Models\Category::class, Models\Brand::class, Models\Attribute::class, Models\CategoryAttribute::class,
                Models\ProductSpec::class, Models\CategoryBrandPage::class, Models\PcBuilderSlot::class],
            self::Reviews => [Models\ProductReview::class, Models\ProductQuestion::class],
            self::Inventory => [Models\StockMovement::class, Models\StockReceipt::class, Models\StockReceiptItem::class, Models\Supplier::class],
            self::Content => [Models\HomeSection::class, Models\Banner::class, Models\Page::class, Models\Service::class, Models\Faq::class],
            self::Seo => [Models\Redirect::class, Models\NotFoundUrl::class],
            self::Staff => [User::class, Models\Role::class],
            self::Activity => [Models\Activity::class],
            self::Reports, self::Settings => [],
        };
    }

    public static function forModel(string $class): ?self
    {
        foreach (self::cases() as $area) {
            if (in_array($class, $area->models(), true)) {
                return $area;
            }
        }

        return null;
    }

    /** Whether the signed-in staff member may use this area. */
    public function allowed(): bool
    {
        $user = auth('web')->user();

        return $user instanceof User && $user->canUse($this);
    }

    /**
     * @return array<string, string> value => label (for checkbox lists)
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $area) => [$area->value => $area->label()])->all();
    }
}
