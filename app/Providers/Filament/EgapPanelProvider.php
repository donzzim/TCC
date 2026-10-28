<?php

namespace App\Providers\Filament;

use App\Filament\Egap\Auth\AcessoAreaExterna;
use App\Filament\Egap\Auth\LoginEgap;
use App\Filament\Egap\Auth\AuthenticateEgap;
use App\Filament\Egap\Auth\EmailVerificationPromptEgap;
use App\Filament\Egap\Auth\LoginResponse;
use App\Filament\Egap\Auth\LogoutEgapController;
use App\Filament\Egap\Auth\RequestPasswordResetCustom;
use App\Filament\Egap\Auth\ResetPasswordEgap;
use App\Filament\Egap\Pages\Auth\EditProfile;
use App\Models\egap\User;
// use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Assets\Css;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\View\View;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Vite;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class EgapPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('egap')
            ->renderHook(PanelsRenderHook::GLOBAL_SEARCH_BEFORE, fn (): View => view('egap.navigation-bar.topbar'))
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->path('/egap')
            ->login(LoginEgap::class)
            ->brandLogo(fn () => Storage::url('egap/brand-dark.png'))
            ->darkModeBrandLogo(fn () => Storage::url('egap/brand-light.png'))
            ->brandLogoHeight('85px')
            ->favicon(fn () => asset('images/egap/favicon.png'))
            ->passwordReset(RequestPasswordResetCustom::class, ResetPasswordEgap::class)
            ->maxContentWidth(Width::Full)
            ->simplePageMaxContentWidth(Width::Small)
            ->emailVerification(EmailVerificationPromptEgap::class)
            ->profile(EditProfile::class, isSimple: false)
            ->globalSearch(false)
            ->brandName('EGAP')
            ->colors([
                'primary' => Color::Blue,
            ])
            ->darkMode(true)
            ->maxContentWidth(Width::Full)
            ->discoverResources(in: app_path('Filament/Egap'), for: 'App\\Filament\\Egap')
            ->discoverPages(in: app_path('Filament/Egap'), for: 'App\\Filament\\Egap')
            ->discoverClusters(in: app_path('Filament/Egap'), for: 'App\\Filament\\Egap')
            ->discoverWidgets(in: app_path('Filament/Egap'), for: 'App\\Filament\\Egap')
            ->widgets([])
            ->assets([
                Css::make('app', resource_path('css/filament/egap/app.css'))->html(fn (): string => Vite::asset('resources/css/filament/egap/app.css')),
                Css::make('modal', resource_path('css/filament/egap/modal.css')),
                Css::make('table', resource_path('css/filament/egap/table.css')),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                AuthenticateEgap::class,
                AcessoAreaExterna::class,
            ], isPersistent: true)
            ->authGuard(User::GUARD)
            ->authPasswordBroker('egap_users')
            ->bootUsing(fn () => config(['filament-shield.panel_user.enabled' => false]))
            ->spa();
    }

    public function boot(): void
    {
        $this->app->bind(\Filament\Auth\Http\Responses\Contracts\LoginResponse::class, LoginResponse::class);

        // A rota de logout é a mesma classe em todos os painéis; a subclasse
        // só muda o comportamento quando o painel atual é o EGAP.
        $this->app->bind(\Filament\Auth\Http\Controllers\LogoutController::class, LogoutEgapController::class);
    }
}
