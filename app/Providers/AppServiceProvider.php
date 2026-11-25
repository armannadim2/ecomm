<?php

namespace App\Providers;

use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductVariant;
use App\Domain\Content\Models\Page;
use App\Domain\Integrations\WebhookObserver;
use App\Domain\Marketing\Models\Campaign;
use App\Domain\Orders\Cart\Cart;
use App\Domain\Orders\Models\Order;
use App\Domain\Staff\Models\Staff;
use App\Domain\Staff\Policies\ActivityPolicy;
use App\Domain\Staff\Policies\RolePolicy;
use App\Http\View\StoreLayoutComposer;
use Filament\Support\Facades\FilamentTimezone;
use Illuminate\Auth\Events\Login;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // One cart per request, so its lines are loaded once even when many views ask for it.
        $this->app->scoped(Cart::class);
    }

    public function boot(): void
    {
        $this->configureSafety();
        $this->configureAuthorization();
        $this->configureAuditing();
        $this->configureStorefront();
        $this->configureApi();

        // Dates are stored in UTC and shown to staff in Spanish time.
        FilamentTimezone::set('Europe/Madrid');
    }

    /** REST API for outside systems: 120 requests a minute per API key; webhooks on catalog and order changes. */
    private function configureApi(): void
    {
        RateLimiter::for('api-clients', fn (Request $request) => Limit::perMinute(120)
            ->by('api-client:'.($request->user()?->getKey() ?? $request->ip())));

        Product::observe(WebhookObserver::class);
        ProductVariant::observe(WebhookObserver::class);
        Order::observe(WebhookObserver::class);
    }

    private function configureStorefront(): void
    {
        View::composer('store.*', StoreLayoutComposer::class);

        // Menu, footer and sitemap are cached; refresh them as soon as staff change something.
        $flush = function (): void {
            Cache::forget('store.menu');
            Cache::forget('store.footer');
            Cache::forget('store.page-slugs');
            Cache::forget('sitemap.xml');
            Cache::forget('store.campaign');
        };

        foreach ([Category::class, Page::class, Product::class, Campaign::class] as $model) {
            $model::saved($flush);
            $model::deleted($flush);
        }
    }

    private function configureSafety(): void
    {
        $production = $this->app->isProduction();

        // Catch lazy loading, discarded attributes and missing attributes during development.
        Model::shouldBeStrict(! $production);

        // Block migrate:fresh / db:wipe on the live database.
        DB::prohibitDestructiveCommands($production);

        if ($production) {
            URL::forceHttps();
        }

        Password::defaults(fn () => $production
            ? Password::min(12)->letters()->numbers()->uncompromised()
            : Password::min(8));
    }

    private function configureAuthorization(): void
    {
        // Owners get every dotted permission ("products.view", ...).
        // Policy-level rules (e.g. "cannot delete yourself") still apply to them.
        Gate::before(function ($user, string $ability) {
            if ($user instanceof Staff && str_contains($ability, '.') && $user->isOwner()) {
                return true;
            }

            return null;
        });

        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(Activity::class, ActivityPolicy::class);
    }

    private function configureAuditing(): void
    {
        Event::listen(function (Login $event): void {
            if ($event->guard !== Staff::GUARD || ! $event->user instanceof Staff) {
                return;
            }

            $event->user->forceFill(['last_login_at' => now()])->saveQuietly();

            activity('auth')
                ->causedBy($event->user)
                ->performedOn($event->user)
                ->withProperties([
                    'ip' => request()->ip(),
                    'user_agent' => substr((string) request()->userAgent(), 0, 255),
                ])
                ->event('login')
                ->log('Inicio de sesión');
        });
    }
}
