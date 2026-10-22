<?php

namespace App\Http\Middleware;

use App\Models\Portal\CMSTemplates;
use App\Models\Setores;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class ApplyActiveTemplate
{
    public function handle(Request $request, Closure $next): Response
    {
        // Para este exemplo, estamos pegando o primeiro setor
        // Em um sistema real com subdomínios, você identificaria o setor pelo host
        // ex: $host = $request->getHost(); $setor = Setores::where('domain', $host)->firstOrFail();
        $setor = Setores::first(); // Adapte para sua lógica de seleção de setor

        if ($setor) {
            // Define um escopo global para todos os models que usam HasSetorScope
            // Isso garante que todas as queries (CMSPages, CMSPosts, etc.) sejam filtradas por este setor
            session(['setor_id' => $setor->id]);

            // Busca o template ativo para o setor atual
            $activeTemplate = CMSTemplates::where('is_active', true)->first();

            if ($activeTemplate) {
                // Compartilha a variável $activeTemplate com todas as views
                View::share('activeTemplate', $activeTemplate);
            } else {
                // Se nenhum template estiver ativo, aborte ou mostre uma página de erro.
                abort(503, 'Nenhum template ativo definido para este site.');
            }
        } else {
            abort(404, 'Setor não encontrado.');
        }

        return $next($request);
    }
}
