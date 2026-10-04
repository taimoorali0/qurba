<?php
// ===== QURBA: Filament admin panel at /admin =====
namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
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
            ->login()
            ->brandName('Qurba Admin')
            ->brandLogo(fn () => asset('brand/qurba-logo.png'))
            ->brandLogoHeight('2.75rem')
            ->favicon(asset('brand/qurba-icon-32.png'))
            ->colors(['primary' => Color::hex('#0B3B2D'), 'warning' => Color::hex('#C9A24A'), 'gray' => Color::Stone])
            ->font('Figtree')
            ->renderHook(PanelsRenderHook::HEAD_END, fn () => '<link rel="preconnect" href="https://fonts.googleapis.com"><link href="https://fonts.googleapis.com/css2?family=Marcellus&display=swap" rel="stylesheet"><link rel="stylesheet" href="' . asset('css/qurba-admin.css') . '?v=' . @filemtime(public_path('css/qurba-admin.css')) . '">')
            // Phones and tablets: always start with the menu closed (desktop keeps its remembered state)
            ->renderHook(PanelsRenderHook::BODY_END, fn () => '<script>document.addEventListener("alpine:initialized",function(){if(window.innerWidth<1024&&window.Alpine&&Alpine.store("sidebar")){Alpine.store("sidebar").close()}});</script>')
            ->defaultAvatarProvider(\App\Support\InitialsAvatar::class)
            ->sidebarCollapsibleOnDesktop()
            ->maxContentWidth('7xl')
            ->navigationGroups(['Religious content', 'Content', 'Qurba Learning', 'Users & activity'])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->pages([\App\Filament\Pages\Dashboard::class, \App\Filament\Pages\AudioFiles::class])
            ->middleware([
                EncryptCookies::class, AddQueuedCookiesToResponse::class, StartSession::class, AuthenticateSession::class,
                ShareErrorsFromSession::class, VerifyCsrfToken::class, SubstituteBindings::class,
                DisableBladeIconComponents::class, DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([Authenticate::class, \App\Http\Middleware\EnsureAdminTwoFactor::class]);
    }
}
