<?php

namespace App\Providers\Filament;

use App\Domain\Staff\Models\Staff;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\FontProviders\LocalFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->brandName(config('app.name'))
            ->authGuard(Staff::GUARD)
            ->authPasswordBroker(Staff::GUARD)
            ->login()
            ->passwordReset()
            ->profile(isSimple: false)
            // Every staff member must use an authenticator app (Google Authenticator, Microsoft Authenticator...).
            ->multiFactorAuthentication(
                [AppAuthentication::make()->recoverable()->brandName(config('app.name'))],
                isRequired: true,
            )
            // Look & feel from the Stitch "Afegir Producte" design; the theme self-hosts the font.
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->font('Plus Jakarta Sans Variable', provider: LocalFontProvider::class)
            ->colors([
                'primary' => Color::hex('#bf4317'),
                'gray' => Color::Slate,
            ])
            ->navigationGroups([
                NavigationGroup::make('Catálogo'),
                NavigationGroup::make('Clientes'),
                NavigationGroup::make('Contenido'),
                NavigationGroup::make('Equipo'),
                NavigationGroup::make('Sistema')->collapsed(),
            ])
            ->databaseNotifications()
            ->databaseNotificationsPolling('60s')
            ->sidebarCollapsibleOnDesktop()
            ->unsavedChangesAlerts()
            ->globalSearchKeyBindings(['ctrl+k', 'command+k'])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
