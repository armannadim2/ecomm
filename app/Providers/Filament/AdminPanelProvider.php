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
            // Tot & Més brand guide (logo kit in public/brand): official logo, Coral actions on Navy text,
            // Bricolage Grotesque self-hosted by the theme (no Google / Bunny request).
            ->brandLogo(asset('brand/totimes-horizontal-color.svg'))
            ->darkModeBrandLogo(asset('brand/totimes-horizontal-reversed.svg'))
            ->brandLogoHeight('2.25rem')
            ->favicon(asset('favicon.svg'))
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->font('Bricolage Grotesque Variable', provider: LocalFontProvider::class)
            ->colors([
                // Brand Coral. Shade 600 is the deepened coral (5:1 with white) and 500 sits just below brand #E4572E,
                // so Filament draws buttons as coral with white text (with 500 = #E4572E it would pick a pale coral with dark text).
                'primary' => array_map(Color::convertToOklch(...), [
                    50 => '#fdf1ec', 100 => '#fce0d6', 200 => '#f9c4b1', 300 => '#f5a68b', 400 => '#f08a6b',
                    500 => '#d64d24', 600 => '#c4431c', 700 => '#a93716', 800 => '#862c14', 900 => '#6d2613', 950 => '#3b1108',
                ]),
                'gray' => Color::Slate,
            ])
            ->navigationGroups([
                NavigationGroup::make('Pedidos'),
                NavigationGroup::make('Catálogo'),
                NavigationGroup::make('Clientes'),
                NavigationGroup::make('Contenido'),
                NavigationGroup::make('Equipo'),
                NavigationGroup::make('Integraciones')->collapsed(),
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
