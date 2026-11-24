<?php

namespace App\Providers;

use App\Domain\Staff\Models\Staff;
use App\Domain\Staff\Policies\ActivityPolicy;
use App\Domain\Staff\Policies\RolePolicy;
use Filament\Support\Facades\FilamentTimezone;
use Illuminate\Auth\Events\Login;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->configureSafety();
        $this->configureAuthorization();
        $this->configureAuditing();

        // Dates are stored in UTC and shown to staff in Spanish time.
        FilamentTimezone::set('Europe/Madrid');
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
