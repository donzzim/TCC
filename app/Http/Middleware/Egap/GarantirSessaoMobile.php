<?php

namespace App\Http\Middleware\Egap;

use App\Filament\Egap\Auth\MobileEgapSession;
use Closure;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

/**
 * Roda depois de auth:sanctum. Exige que o token tenha uma linha em
 * egap_mobile_sessoes (tokens antigos não têm) e reavalia o acesso ao EGAP a
 * cada requisição. Com o parâmetro "setor" (mobile.setor) exige também uma
 * lotação resolvida, pois as rotas de dados filtram por setor/unidade.
 */
class GarantirSessaoMobile
{
    public function handle(Request $request, Closure $next, ?string $exigir = null): Response
    {
        $token = $request->user()?->currentAccessToken();
        $sessao = $token instanceof PersonalAccessToken ? MobileEgapSession::doToken($token) : null;

        if ($sessao === null) {
            return $this->sessaoInvalida();
        }

        try {
            $user = $sessao->user();
        } catch (ModelNotFoundException) {
            return $this->sessaoInvalida();
        }

        if (! $user->ativo || ! ($user->can('egap_access') || $user->isAdminEgap())) {
            return response()->json([
                'message' => 'Usuário sem acesso ao aplicativo.',
                'code' => 'sem_acesso',
            ], 403);
        }

        if ($exigir === 'setor' && $sessao->escopo() === null) {
            return response()->json([
                'message' => 'Usuário sem lotação válida para esta operação.',
                'code' => 'sem_lotacao',
            ], 409);
        }

        app()->instance(MobileEgapSession::class, $sessao);

        return $next($request);
    }

    private function sessaoInvalida(): Response
    {
        return response()->json([
            'message' => 'Sessão inválida. Faça login novamente.',
            'code' => 'sessao_invalida',
        ], 401);
    }
}
