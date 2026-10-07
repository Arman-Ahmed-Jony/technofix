<?php

namespace App\Providers;

use App\Enums\StaffArea;
use App\Models\Activity;
use App\Models\Role;
use App\Models\StockReceipt;
use App\Models\User;
use App\Payments\PaymentGateways;
use App\Support\ActivityLog;
use App\Support\Backups;
use App\Support\Catalog;
use App\Support\IconSprite;
use App\Support\MailSetup;
use App\Support\PageCache;
use App\Support\Tracking;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Illuminate\Auth\Events\Login;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\ServiceProvider;
use Spatie\LaravelSettings\Events\SavingSettings;
use Spatie\LaravelSettings\Events\SettingsSaved;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(Catalog::class);
        $this->app->scoped(IconSprite::class);
        $this->app->scoped(Tracking::class);
        $this->app->singleton(PaymentGateways::class);

        // Email settings from the admin (Settings → Email sending), read only when something is about to be sent.
        $this->app->afterResolving('mail.manager', fn () => MailSetup::apply());

        // PDFs: dompdf looks for base_path('public'), which the cPanel layout doesn't have (public_html sits next to the
        // app) — "Cannot resolve public path". Point it at the real public folder when a PDF is made.
        $this->app->beforeResolving('dompdf', fn () => config(['dompdf.public_path' => $this->app->publicPath()]));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Product photos, brand logos and banners must be web-visible: Filament's upload field would otherwise use the
        // default "local" (private) disk instead of the media library's public disk.
        SpatieMediaLibraryFileUpload::configureUsing(fn (SpatieMediaLibraryFileUpload $upload) => $upload
            ->disk(config('media-library.disk_name'))
            ->visibility('public'));

        View::composer(
            ['storefront.partials.header', 'storefront.partials.mobile-menu', 'storefront.partials.footer'],
            fn ($view) => $view->with('menu', once(fn () => $this->app->make(Catalog::class)->menu())),
        );

        $this->staffAccess();
        $this->security();

        // Guest page cache: saving storefront content (models, uploads) or any setting clears it.
        Event::listen(['eloquent.saved: *', 'eloquent.deleted: *'], fn (string $event, array $data) => PageCache::modelChanged($data[0] ?? null));
        Event::listen(SettingsSaved::class, fn () => PageCache::invalidate());
        $this->optimizes(optimize: 'pages:clear', clear: 'pages:clear', key: 'pages'); // deploys run `php artisan optimize`

        // Backups: the owner's choices (Admin → Settings → Backups) apply to every backup:* command.
        Event::listen(CommandStarting::class, function (CommandStarting $event) {
            if (str_starts_with((string) $event->command, 'backup:')) {
                Backups::configure();
            }
        });
    }

    /** Proxies allowed to pass on the visitor's address (config technofix.trusted_proxies) and password rules. */
    protected function security(): void
    {
        $proxies = trim((string) config('technofix.trusted_proxies'));
        if ($proxies !== '') {
            TrustProxies::at($proxies === 'cloudflare' ? config('technofix.cloudflare_ips') : array_map('trim', explode(',', $proxies)));
        }

        // Customers: at least 8 characters. Staff accounts ask for more (StaffResource, My profile).
        Password::defaults(fn () => Password::min(8));
    }

    /**
     * What staff may do in the admin, by role (Admin → Settings → Roles). Filament asks the gate before every list,
     * form, action and URL of a resource; the model says which area it belongs to (StaffArea::models()).
     */
    protected function staffAccess(): void
    {
        Gate::before(function ($user, string $ability, array $arguments) {
            $subject = $arguments[0] ?? null;

            if (! $user instanceof User || $subject === null) {
                return null;
            }

            $area = StaffArea::forModel(is_object($subject) ? $subject::class : (string) $subject);

            if ($area && ! $user->canUse($area)) {
                return false;
            }

            // The activity log and stock-ins can be read, never edited or deleted (a stock-in is cancelled instead).
            if (in_array(is_object($subject) ? $subject::class : $subject, [Activity::class, StockReceipt::class], true) && in_array($ability, ['update', 'delete', 'deleteAny', 'forceDelete', 'restore', 'replicate'], true)) {
                return false;
            }

            if (($subject instanceof Activity || $subject === Activity::class) && ! in_array($ability, ['viewAny', 'view'], true)) {
                return false;
            }

            // Staff accounts: only owners change owners; nobody deletes their own account.
            if ($subject instanceof User && $ability !== 'view' && (($subject->isOwner() && ! $user->isOwner()) || ($ability === 'delete' && $subject->is($user)))) {
                return false;
            }

            // The owner role is fixed; a role still in use can't be deleted.
            if ($subject instanceof Role && (($subject->is_owner && in_array($ability, ['update', 'delete'], true)) || ($ability === 'delete' && $subject->users()->exists()))) {
                return false;
            }

            return null;
        });

        Event::listen(Login::class, function (Login $event) {
            if ($event->user instanceof User) {
                $event->user->forceFill(['last_login_at' => now()])->saveQuietly();
                ActivityLog::record('login', $event->user, by: $event->user);
            }
        });

        // Activity log: settings pages (the models log themselves, Concerns\LogsActivity).
        Event::listen(SavingSettings::class, fn (SavingSettings $event) => ActivityLog::settingsSaved(
            $event->settings::group(), $event->properties, $event->originalValues, $event->settings::encrypted(),
        ));
    }
}
