<?php

namespace App\Http\Middleware;

use App\Filament\Pages\CompleteTjesProfile;
use App\Models\User;
use App\Services\Auth\TjesLdapService;
use App\Services\Auth\TjesProfileCompletionService;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTjesProfileCompleted
{
    public function __construct(private readonly TjesProfileCompletionService $completion)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = Filament::auth()->user();

        if (! $user instanceof User) {
            return $next($request);
        }

        if ($request->routeIs('filament.*.pages.completar-cadastro')) {
            return $next($request);
        }

        if (! $this->completion->needsCompletion($user)) {
            session()->forget([
                TjesLdapService::SESSION_AUTHENTICATED_KEY,
                TjesLdapService::SESSION_ATTRIBUTES_KEY,
            ]);

            return $next($request);
        }

        return redirect()->to(CompleteTjesProfile::getUrl(
            panel: Filament::getCurrentPanel()->getId(),
        ));
    }
}
