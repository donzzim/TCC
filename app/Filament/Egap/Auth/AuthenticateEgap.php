<?php

namespace App\Filament\Egap\Auth;

use App\Models\egap\User;
use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate;
use Filament\Notifications\Notification;

class AuthenticateEgap extends Authenticate
{
    protected function authenticate($request, array $guards): void
    {
        $guard = Filament::auth();
        $user = $guard->user();

        if ($user instanceof User && ! $user->canAccessPanel(Filament::getCurrentOrDefaultPanel())) {
            $guard->logout();

            session()->forget([
                EgapSession::SESSION_KEY,
                'password_hash_' . User::GUARD,
            ]);

            Notification::make()
                ->title('Seu usuário não tem acesso ao EGAP.')
                ->body('Procure a administração do sistema para liberar o acesso.')
                ->danger()
                ->persistent()
                ->send();

            $this->unauthenticated($request, $guards);
        }

        parent::authenticate($request, $guards);
    }
}
