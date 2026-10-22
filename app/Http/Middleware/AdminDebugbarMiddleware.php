<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminDebugbarMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Garante que o debugbar comece desligado
        \app('debugbar')->disable();

        // 2. Verifica se o usuário está logado e é um administrador
        // DICA: Ajuste essa condição para a forma como você verifica admins no seu sistema
        if (auth()->check() && auth()->user()->email === 'admin@tjes.jus.br') {
            \app('debugbar')->enable();
        }

        return $next($request);
    }
}
