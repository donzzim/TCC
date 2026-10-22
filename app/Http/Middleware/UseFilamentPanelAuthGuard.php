<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class UseFilamentPanelAuthGuard
{
    public function handle(Request $request, Closure $next): Response
    {
        Auth::shouldUse(Filament::getCurrentPanel()->getAuthGuard());

        return $next($request);
    }
}
