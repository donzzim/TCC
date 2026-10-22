<?php

namespace App\Auth;

use App\Notifications\CustomResetPasswordNotification;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Auth\Pages\PasswordReset\RequestPasswordReset;
use Filament\Facades\Filament;
use Filament\Models\Contracts\FilamentUser;
use Filament\Notifications\Notification;

use Illuminate\Auth\Events\PasswordResetLinkSent;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;

class RequestPasswordResetCustom extends RequestPasswordReset
{
    public function request(): void
    {
        try {
            $this->rateLimit(2);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return;
        }

        $data = $this->form->getState();

        Log::info('RESET PASSWORD - dados recebidos no form', [
            'email_digitado' => $data['email'] ?? null,
            'panel' => Filament::getCurrentPanel()?->getId(),
            'broker' => Filament::getAuthPasswordBroker(),
        ]);

        $status = Password::broker(Filament::getAuthPasswordBroker())->sendResetLink(
            $this->getCredentialsFromFormData($data),
            function (CanResetPassword $user, string $token): void {
                Log::info('RESET PASSWORD - usuário encontrado', [
                    'user_class' => $user::class,
                    'user_id' => $user->id ?? null,
                    'email_model' => $user->email ?? null,
                    'route_mail' => method_exists($user, 'routeNotificationForMail')
                        ? $user->routeNotificationForMail()
                        : null,
                ]);

                if (
                    ($user instanceof FilamentUser) &&
                    (! $user->canAccessPanel(Filament::getCurrentPanel()))
                ) {
                    Log::warning('RESET PASSWORD - usuário sem acesso ao painel', [
                        'user_id' => $user->id ?? null,
                        'email_model' => $user->email ?? null,
                    ]);

                    return;
                }

                if (! method_exists($user, 'notify')) {
                    $userClass = $user::class;

                    throw new Exception("Model [{$userClass}] does not have a [notify()] method.");
                }

                $resetUrl = Filament::getResetPasswordUrl($token, $user);

                Log::info('RESET PASSWORD - enviando custom notification', [
                    'user_id' => $user->id ?? null,
                    'email_model' => $user->email ?? null,
                    'reset_url' => $resetUrl,
                    'mail_default' => config('mail.default'),
                    'from_address' => config('mail.from.address'),
                    'from_name' => config('mail.from.name'),
                    'queue_default' => config('queue.default'),
                ]);



                $user->notify(new CustomResetPasswordNotification(
                    token: $token,
                    resetUrl: $resetUrl,
                ));

                if (class_exists(PasswordResetLinkSent::class)) {
                    event(new PasswordResetLinkSent($user));
                }
            },
        );

        Log::info('RESET PASSWORD - status final', [
            'status' => $status,
        ]);

        if ($status !== Password::RESET_LINK_SENT) {
            $this->getFailureNotification($status)?->send();

            return;
        }

        $this->getSentNotification($status)?->send();

        $this->form->fill();
    }
}
