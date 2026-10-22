<?php

namespace App\Filament\App\Pages\Auth;

use App\Services\Auth\TjesLdapService;
use App\Services\Auth\TjesUserProvisioningService;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login;
use Filament\Facades\Filament;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Models\Contracts\FilamentUser;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * @property Schema $form
 */
class LoginApp extends Login
{
    use InteractsWithFormActions;
    use WithRateLimiting;

    /**
     * @var view-string
     */
    // protected string $view = 'filament-panels::pages.auth.login';

    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    public function mount(): void
    {
        if (Filament::auth()->check()) {
            redirect()->intended(Filament::getUrl());
        }

        $this->form->fill();
    }

    public function authenticate(): ?LoginResponse
    {

        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        $data = $this->form->getState();
        Log::info('auth.filament.login_attempt', [
            'panel' => 'app',
            'identifier_type' => filter_var($data['login'] ?? null, FILTER_VALIDATE_EMAIL)
                ? 'email'
                : (is_numeric($data['login'] ?? null) ? 'cpf' : 'login'),
        ]);

        $remember = (bool) ($data['remember'] ?? false);
        $credentials = $this->getCredentialsFromFormData($data);
        session()->forget(TjesLdapService::SESSION_PENDING_REGISTRATION_KEY);

        $authenticated = Filament::auth()->attempt($credentials, $remember);

        if (! $authenticated) {
            $pending = session()->pull(TjesLdapService::SESSION_PENDING_REGISTRATION_KEY);

            if (is_array($pending)) {
                $identifier = $pending['identifier'] ?? null;
                $attributes = $pending['attributes'] ?? null;
                $password = $data['password'] ?? null;

                if (is_string($identifier) && is_array($attributes) && is_string($password) && $password !== '') {
                    $ldapUser = app(TjesLdapService::class)->authenticateUser($identifier, $password);

                    if ($ldapUser !== null) {
                        try {
                            $user = app(TjesUserProvisioningService::class)->provision(
                                $identifier,
                                $ldapUser['attributes'],
                            );

                            session()->put(TjesLdapService::SESSION_AUTHENTICATED_KEY, true);
                            session()->put(TjesLdapService::SESSION_ATTRIBUTES_KEY, $ldapUser['attributes']);
                            Filament::auth()->login($user, $remember);
                            $authenticated = true;

                            Log::info('auth.hybrid.app_user_provisioned', [
                                'user_id' => $user->getAuthIdentifier(),
                            ]);
                        } catch (Throwable $exception) {
                            Log::error('auth.hybrid.app_user_provisioning_failed', [
                                'exception' => $exception::class,
                                'message' => $exception->getMessage(),
                            ]);
                            $this->throwFailureValidationException();
                        }
                    } else {
                        Log::warning('auth.hybrid.app_pending_ldap_password_failed');
                        $this->throwFailureValidationException();
                    }
                }
            }

            if (! $authenticated) {
                Log::warning('auth.filament.login_failed', [
                    'panel' => 'app',
                ]);
                $this->throwFailureValidationException();
            }
        }

        $user = Filament::auth()->user();

        if (
            ($user instanceof FilamentUser) &&
            (! $user->canAccessPanel(Filament::getCurrentPanel()))
        ) {
            Log::warning('auth.filament.panel_access_denied', [
                'panel' => 'app',
                'user_id' => $user->getAuthIdentifier(),
            ]);
            Filament::auth()->logout();

            $this->throwFailureValidationException();
        }

        session()->regenerate();

        Log::info('auth.filament.login_succeeded', [
            'panel' => 'app',
            'user_id' => $user?->getAuthIdentifier(),
        ]);

        return app(LoginResponse::class);
    }

    protected function getRateLimitedNotification(TooManyRequestsException $exception): ?Notification
    {
        return Notification::make()
            ->title(__('filament-panels::pages/auth/login.notifications.throttled.title', [
                'seconds' => $exception->secondsUntilAvailable,
                'minutes' => $exception->minutesUntilAvailable,
            ]))
            ->body(array_key_exists('body', __('filament-panels::pages/auth/login.notifications.throttled') ?: []) ? __('filament-panels::pages/auth/login.notifications.throttled.body', [
                'seconds' => $exception->secondsUntilAvailable,
                'minutes' => $exception->minutesUntilAvailable,
            ]) : null)
            ->danger();
    }

    protected function throwFailureValidationException(): never
    {
        throw ValidationException::withMessages([
            'data.login' => __('filament-panels::pages/auth/login.messages.failed'),
            // 'data.email' => __('filament-panels::pages/auth/login.messages.failed'),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                // $this->getEmailFormComponent(),
                $this->getLoginFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getRememberFormComponent(),
            ])
            ->statePath('data');
    }

    protected function getLoginFormComponent(): Component
    {
        return TextInput::make('login')
            ->label('Login')
            ->required()
            ->autocomplete()
            ->autofocus()
            ->hint('CPF, login EMES, login TJES ou e-mail')
            ->hintColor('sky')
            ->extraInputAttributes(['tabindex' => 1]);
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label(__('filament-panels::pages/auth/login.form.email.label'))
            ->email()
            ->required()
            ->autocomplete()
            ->autofocus()
            ->extraInputAttributes(['tabindex' => 1]);
    }

    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label(__('filament-panels::pages/auth/login.form.password.label'))
            ->hint(filament()->hasPasswordReset() ? new HtmlString(Blade::render('<x-filament::link :href="filament()->getRequestPasswordResetUrl()" tabindex="3"> {{ __(\'filament-panels::pages/auth/login.actions.request_password_reset.label\') }}</x-filament::link>')) : null)
            ->password()
            ->revealable(filament()->arePasswordsRevealable())
            ->autocomplete('current-password')
            ->required()
            ->extraInputAttributes(['tabindex' => 2]);
    }

    protected function getRememberFormComponent(): Component
    {
        return Checkbox::make('remember')
            ->label(__('filament-panels::pages/auth/login.form.remember.label'));
    }

    public function registerAction(): Action
    {
        return Action::make('register')
            ->link()
            ->label(__('filament-panels::pages/auth/login.actions.register.label'))
            ->url(filament()->getRegistrationUrl());
    }

    public function getTitle(): string|Htmlable
    {
        return __('filament-panels::pages/auth/login.title');
    }

    public function getHeading(): string|Htmlable
    {
        return __('filament-panels::pages/auth/login.heading');
    }

    /**
     * @return array<Action | ActionGroup>
     */
    protected function getFormActions(): array
    {
        return [
            $this->getAuthenticateFormAction(),
        ];
    }

    protected function getAuthenticateFormAction(): Action
    {
        return Action::make('authenticate')
            ->label(__('filament-panels::pages/auth/login.form.actions.authenticate.label'))
            ->submit('authenticate');
    }

    protected function hasFullWidthFormActions(): bool
    {
        return true;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    // protected function getCredentialsFromFormData(array $data): array
    // {
    //     return [
    //         'email' => $data['email'],
    //         'password' => $data['password'],
    //     ];
    // }
    protected function getCredentialsFromFormData(array $data): array
    {
        if (filter_var($data['login'], FILTER_VALIDATE_EMAIL)) {
            $login_type = 'email';
        } elseif (is_numeric($data['login'])) {
            $login_type = 'cpf';
        } else {
            $login_type = 'login';
        }

        // $login_type = filter_var($data['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'name';
        return [
            $login_type => $data['login'],
            '_panel' => 'app',
            // $login_type => $data['email'],
            'password' => $data['password'],
        ];
    }
}
