<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use App\Filament\Widgets\OverviewStats;
use App\Filament\Widgets\RevenueChart;
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

class ModerationPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('moderation')
            ->path('moderation')
            ->login()
            // Not asset(): that resolves the scheme from the current
            // request, but this value is fixed once when the panel is
            // registered — earlier in the boot cycle than trustProxies
            // detects HTTPS from Render's proxy. It came out http://,
            // which the browser blocks as mixed content on the https://
            // page (silently — no error without opening devtools, just a
            // missing logo). config('app.url') is a static, already-correct
            // scheme, independent of request timing.
            ->brandLogo(rtrim(config('app.url'), '/').'/images/logo-light.svg')
            ->darkModeBrandLogo(rtrim(config('app.url'), '/').'/images/logo-dark.svg')
            ->brandLogoHeight('2rem')
            ->colors([
                'primary' => Color::Amber,
            ])
            // Nécessaire pour que la notification "export terminé"
            // (déclenchée par ExportAction sur Transactions/Comptes)
            // apparaisse dans la cloche du panel.
            ->databaseNotifications()
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                OverviewStats::class,
                RevenueChart::class,
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
