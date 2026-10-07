<?php

use App\Http\Controllers\Account\AccountController;
use App\Http\Controllers\Account\AddressController;
use App\Http\Controllers\Account\AuthController;
use App\Http\Controllers\Account\PasswordController;
use App\Http\Controllers\Account\SavedBuildController;
use App\Http\Controllers\Admin\PrintOrdersController;
use App\Http\Controllers\Storefront\BrandController;
use App\Http\Controllers\Storefront\CartController;
use App\Http\Controllers\Storefront\CatalogController;
use App\Http\Controllers\Storefront\CheckoutController;
use App\Http\Controllers\Storefront\EnquiryController;
use App\Http\Controllers\Storefront\FaqController;
use App\Http\Controllers\Storefront\HomeController;
use App\Http\Controllers\Storefront\OffersController;
use App\Http\Controllers\Storefront\PageController;
use App\Http\Controllers\Storefront\PcBuilderController;
use App\Http\Controllers\Storefront\ProductFeedbackController;
use App\Http\Controllers\Storefront\SavedListController;
use App\Http\Controllers\Storefront\SearchController;
use App\Http\Controllers\Storefront\ServiceController;
use App\Http\Controllers\Storefront\SiteFilesController;
use App\Http\Controllers\Storefront\TrackOrderController;
use App\Http\Controllers\Storefront\WarrantyController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->middleware('page.cache')->name('home');

// For crawlers (App\Support\Robots, Sitemap, LlmsTxt).
Route::get('/robots.txt', [SiteFilesController::class, 'robots'])->name('robots');
Route::get('/sitemap.xml', [SiteFilesController::class, 'sitemap'])->name('sitemap');
Route::get('/sitemaps/{name}.xml', [SiteFilesController::class, 'sitemapFile'])->where('name', '[a-z0-9-]+')->name('sitemap.file');
Route::get('/llms.txt', [SiteFilesController::class, 'llms'])->name('llms');

Route::get('/search', [SearchController::class, 'index'])->middleware('page.cache')->name('search');
Route::get('/search/suggest', [SearchController::class, 'suggest'])->middleware('throttle:120,1')->name('search.suggest');

Route::get('/offers', OffersController::class)->middleware('page.cache')->name('offers');
Route::get('/brands', [BrandController::class, 'index'])->middleware('page.cache')->name('brand.index');
Route::get('/brand/{slug}', [BrandController::class, 'show'])->middleware('page.cache')->name('brand.show');

Route::get('/pc-builder', [PcBuilderController::class, 'show'])->middleware('page.cache')->name('pc-builder');
Route::get('/pc-builder/state', [PcBuilderController::class, 'state'])->middleware('throttle:120,1')->name('pc-builder.state');
Route::get('/pc-builder/slots/{slot}', [PcBuilderController::class, 'options'])->middleware('throttle:120,1')->name('pc-builder.options');
Route::get('/pc-builder/quote', [PcBuilderController::class, 'quote'])->middleware('throttle:10,1')->name('pc-builder.quote'); // PDF: heavy
Route::post('/pc-builder/save', [PcBuilderController::class, 'save'])->middleware('throttle:20,1')->name('pc-builder.save');
Route::post('/pc-builder/enquiry', [PcBuilderController::class, 'enquiry'])->middleware('throttle:5,1')->name('pc-builder.enquiry');

Route::get('/cart', CartController::class)->name('cart');
Route::get('/compare', [SavedListController::class, 'compare'])->name('compare');
Route::get('/wishlist', [SavedListController::class, 'wishlist'])->name('wishlist');
Route::get('/recently-viewed', [SavedListController::class, 'recent'])->middleware('throttle:60,1')->name('recently-viewed');
Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout');
Route::post('/checkout', [CheckoutController::class, 'store'])->middleware('throttle:10,1')->name('checkout.store');
Route::post('/checkout/coupon', [CheckoutController::class, 'coupon'])->middleware('throttle:15,1')->name('checkout.coupon');
Route::get('/checkout/success', [CheckoutController::class, 'success'])->name('checkout.success');
Route::post('/checkout/draft', [CheckoutController::class, 'draft'])->middleware('throttle:30,1')->name('checkout.draft');
Route::get('/checkout/resume/{token}', [CheckoutController::class, 'resume'])->middleware('throttle:20,1')->name('checkout.resume');

Route::get('/track-order', [TrackOrderController::class, 'form'])->name('orders.track');
Route::post('/track-order', [TrackOrderController::class, 'lookup'])->middleware('throttle:10,1')->name('orders.track.lookup');
Route::get('/track-order/{number}', [TrackOrderController::class, 'show'])->name('orders.track.show');

// Warranty claims: customers check theirs with their mobile number (Sales → Warranty claims).
Route::get('/warranty', [WarrantyController::class, 'form'])->middleware('page.cache')->name('warranty');
Route::post('/warranty', [WarrantyController::class, 'lookup'])->middleware('throttle:10,1')->name('warranty.lookup');
Route::get('/warranty/status', [WarrantyController::class, 'status'])->name('warranty.status');

// Content pages (all managed in Admin → Content)
Route::get('/page/{slug}', [PageController::class, 'show'])->middleware('page.cache')->name('page.show');
Route::get('/faq', FaqController::class)->middleware('page.cache')->name('faq');
Route::get('/services', [ServiceController::class, 'index'])->middleware('page.cache')->name('services.index');
Route::get('/services/{slug}', [ServiceController::class, 'show'])->middleware('page.cache')->name('services.show');
Route::get('/contact', [EnquiryController::class, 'contact'])->middleware('page.cache')->name('contact');
Route::get('/request-quote', [EnquiryController::class, 'quote'])->middleware('page.cache')->name('quote');
Route::get('/site-visit', [EnquiryController::class, 'siteVisit'])->middleware('page.cache')->name('site-visit');
Route::post('/enquiry', [EnquiryController::class, 'store'])->middleware('throttle:5,1')->name('enquiry.store');

// Product reviews and questions: signed-in customers only (App\Services\ProductFeedback).
Route::middleware(['auth:customer', 'throttle:6,1'])->group(function () {
    Route::post('/reviews/{product}', [ProductFeedbackController::class, 'review'])->whereNumber('product')->name('product.review');
    Route::post('/questions/{product}', [ProductFeedbackController::class, 'question'])->whereNumber('product')->name('product.question');
});

// Customer accounts (guard "customer"; staff use the admin panel)
Route::middleware('guest:customer')->group(function () {
    Route::get('/login', [AuthController::class, 'loginForm'])->name('account.login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::get('/register', [AuthController::class, 'registerForm'])->name('account.register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
    Route::get('/forgot-password', [PasswordController::class, 'requestForm'])->name('account.password.request');
    Route::post('/forgot-password', [PasswordController::class, 'sendLink'])->middleware('throttle:5,1')->name('account.password.email');
    Route::get('/reset-password/{token}', [PasswordController::class, 'resetForm'])->name('account.password.reset');
    Route::post('/reset-password', [PasswordController::class, 'reset'])->middleware('throttle:10,1')->name('account.password.update');
});
Route::post('/logout', [AuthController::class, 'logout'])->name('account.logout');

Route::middleware('auth:customer')->prefix('account')->name('account')->group(function () {
    Route::get('/', [AccountController::class, 'dashboard'])->name('');
    Route::get('/orders', [AccountController::class, 'orders'])->name('.orders');
    Route::get('/orders/{number}', [AccountController::class, 'order'])->name('.orders.show');
    Route::get('/profile', [AccountController::class, 'profile'])->name('.profile');
    Route::put('/profile', [AccountController::class, 'updateProfile'])->middleware('throttle:20,1')->name('.profile.update');
    Route::put('/password', [AccountController::class, 'updatePassword'])->middleware('throttle:10,1')->name('.password.change');
    Route::get('/addresses', [AddressController::class, 'index'])->name('.addresses');
    Route::get('/addresses/new', [AddressController::class, 'create'])->name('.addresses.create');
    Route::post('/addresses', [AddressController::class, 'store'])->name('.addresses.store');
    Route::get('/addresses/{address}/edit', [AddressController::class, 'edit'])->name('.addresses.edit');
    Route::put('/addresses/{address}', [AddressController::class, 'update'])->name('.addresses.update');
    Route::delete('/addresses/{address}', [AddressController::class, 'destroy'])->name('.addresses.destroy');
    Route::post('/addresses/{address}/default', [AddressController::class, 'makeDefault'])->name('.addresses.default');
    Route::get('/builds', [SavedBuildController::class, 'index'])->name('.builds');
    Route::delete('/builds/{build}', [SavedBuildController::class, 'destroy'])->name('.builds.destroy');
});

// Staff: print invoices / packing slips (signed-in admin users only).
Route::get('/'.config('technofix.admin_path').'/print/orders', PrintOrdersController::class)
    ->middleware('throttle:60,1')
    ->name('orders.print');

// Flat catalog URLs (/laptop, /asus-tuf-gaming-f15-..., /motherboard/gigabyte). Keep these routes last
// so fixed paths — including the admin panel — always win.
Route::get('/{category}/{brand}', [CatalogController::class, 'categoryBrand'])
    ->where(['category' => '[a-z0-9]+(?:-[a-z0-9]+)*', 'brand' => '[a-z0-9]+(?:-[a-z0-9]+)*'])
    ->middleware('page.cache')
    ->name('catalog.brand');
Route::get('/{slug}', [CatalogController::class, 'show'])
    ->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*')
    ->middleware('page.cache')
    ->name('catalog.show');
