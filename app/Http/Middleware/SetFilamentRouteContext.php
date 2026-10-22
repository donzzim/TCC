<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

class SetFilamentRouteContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $rotaAtual = Route::currentRouteName();

        // Só atualiza a sessão se NÃO for uma rota interna de update ou debugbar
        // e se realmente existir um nome de rota.
        if ($rotaAtual &&
            $rotaAtual !== 'livewire.update' &&
            $rotaAtual !== 'livewire.upload-file' &&
            !str_contains($rotaAtual, 'debugbar')
        ) {
            // "Variável Global" persistente
            session(['filament_rota_atual' => $rotaAtual]);

            // Opcional: Salvar também a URL completa se precisar
            session(['filament_url_atual' => $request->fullUrl()]);
        }

        return $next($request);
    }
}
