<?php

namespace App\Filament\Egap\Auth;

use App\Models\egap\User;
use Filament\Auth\Http\Controllers\LogoutController;
use Filament\Auth\Http\Responses\Contracts\LogoutResponse;
use Filament\Facades\Filament;
class LogoutEgapController extends LogoutController
{
    public function __invoke(): LogoutResponse
    {
        if (Filament::getCurrentPanel()?->getId() !== 'egap') {
            return parent::__invoke();
        }

        Filament::auth()->logout();

        session()->forget([
            EgapSession::SESSION_KEY,
            'password_hash_' . User::GUARD,   // gravado pelo AuthenticateSession
        ]);

        // Novo id e novo token CSRF, sem apagar a sessão do EMES.
        session()->regenerate();
        session()->regenerateToken();

        return app(LogoutResponse::class);
    }
}
