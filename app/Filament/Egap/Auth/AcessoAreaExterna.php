<?php

namespace App\Filament\Egap\Auth;

use App\Models\egap\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AcessoAreaExterna
{
    public const ROTA = 'filament.egap.externo';

    public function handle(Request $request, Closure $next): Response
    {
        $rota = (string) $request->route()?->getName();

        if ($rota === self::ROTA || str_starts_with($rota, self::ROTA . '.')) {
            $user = auth(User::GUARD)->user();

            abort_unless($user instanceof User && $user->acessaAreaExterna(), 403);
        }

        return $next($request);
    }
}
