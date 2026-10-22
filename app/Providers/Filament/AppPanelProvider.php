<?php

namespace App\Providers\Filament;

use App\Auth\RequestPasswordResetCustom;
use App\Filament\App\Pages\Dashboard;
use App\Filament\App\Pages\Auth\LoginApp;
use App\Filament\Pages\CompleteTjesProfile;
use App\Livewire\CustomProfileComponent;
use Filament\Forms\Components\FileUpload;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\MaxWidth;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Jeffgreco13\FilamentBreezy\BreezyCore;
use Jeffgreco13\FilamentBreezy\Livewire\PersonalInfo;
use Jeffgreco13\FilamentBreezy\Livewire\TwoFactorAuthentication;
use Jeffgreco13\FilamentBreezy\Livewire\UpdatePassword;

class AppPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('app')
            ->path('/app')
            ->login(LoginApp::class)
            ->passwordReset(RequestPasswordResetCustom::class)
            // ->passwordReset()
            ->emailVerification()
            ->profile(isSimple: false)
            ->globalSearch(false)
            ->colors([
                'primary' => Color::Teal,
            ])
            ->brandName('Educa EMES - Área Externa')
            // ->brandLogo(asset('images/logo.png'))
            ->brandLogoHeight('50px')
            ->favicon(asset('images/favicon.ico'))
            // ->darkMode(false)
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->maxContentWidth('full')
            ->topNavigation()
            ->discoverResources(in: app_path('Filament/App/Resources'), for: 'App\\Filament\\App\\Resources')
            ->discoverPages(in: app_path('Filament/App/Pages'), for: 'App\\Filament\\App\\Pages')
            ->discoverWidgets(in: app_path('Filament/App/Widgets'), for: 'App\\Filament\\App\\Widgets')
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->pages([
                Dashboard::class,
                CompleteTjesProfile::class,
            ])
            ->widgets([
                // Widgets\AccountWidget::class,
                // Widgets\FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                \App\Http\Middleware\UseFilamentPanelAuthGuard::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                \App\Http\Middleware\EnsureTjesProfileCompleted::class,
            ])
            ->authGuard('pessoa')
            ->plugins([
                BreezyCore::make()
                    ->myProfile(
                        shouldRegisterUserMenu: true, // Sets the 'account' link in the panel User Menu (default = true)
                        userMenuLabel: 'Meu perfil', // Customizes the 'account' link label in the panel User Menu (default = null)
                        shouldRegisterNavigation: false, // Adds a main navigation item for the My Profile page (default = false)
                        navigationGroup: 'Controle de Acesso', // Sets the navigation group for the My Profile page (default = null)
                        hasAvatars: true, // Enables the avatar upload form component (default = false)
                        slug: 'perfil' // Sets the slug for the profile page (default = 'my-profile')
                    )
                    ->enableTwoFactorAuthentication(
                        force: false, // force the user to enable 2FA before they can use the application (default = false)
                        // action: CustomTwoFactorPage::class, // optionally, use a custom 2FA page
                        // authMiddleware: MustTwoFactor::class, // optionally, customize 2FA auth middleware or disable it to register manually by setting false
                    )
                    ->myProfileComponents([
                        'personal_info' => PersonalInfo::class,
                        'custom_profile' => CustomProfileComponent::class,
                        'update_password' => UpdatePassword::class,
                        'two_factor_authentication' => TwoFactorAuthentication::class,
                        // 'sanctum_tokens' => SanctumTokens::class,

                    ])
                    ->avatarUploadComponent(fn() => FileUpload::make('avatar_url')->disk('public')),
            ])
            ->spa();
    }
}
