<?php

namespace App\Monitoramento\Coleta;

use Closure;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ColetorDeMetricas
{
    private const ATRIBUTO = 'monitoramento.medicao';

    public function handle(Request $request, Closure $next): Response
    {
        if (! config('monitoramento.ativo') || $request->attributes->has(self::ATRIBUTO)) {
            return $next($request);
        }

        $ignorar = rescue(fn () => ClassificadorDeContexto::deveIgnorar($request), true, false);

        if ($ignorar) {
            return $next($request);
        }

        $medicao = new MedicaoDaRequisicao(
            inicio: defined('LARAVEL_START') ? (float) LARAVEL_START : microtime(true),
            requestId: (string) ($request->headers->get('X-Request-Id') ?: Str::uuid()),
        );
        $request->attributes->set(self::ATRIBUTO, $medicao);
        $request->headers->set('X-Request-Id', $medicao->requestId);

        rescue(fn () => DB::listen(function (QueryExecuted $query) use ($medicao): void {
            $medicao->registrarConsulta($query->connectionName ?: $query->connection->getName(), $query->sql, (float) $query->time);
        }), null, false);

        try {
            $response = $next($request);
        } catch (Throwable $e) {
            $medicao->encerrar(500, $e);

            throw $e;
        }

        $medicao->encerrar($response->getStatusCode(), $response->exception ?? null);

        if (! $response->headers->has('X-Request-Id')) {
            $response->headers->set('X-Request-Id', $medicao->requestId);
        }

        return $response;
    }

    public function terminate(Request $request, Response $response): void
    {
        $medicao = $request->attributes->get(self::ATRIBUTO);

        if (! $medicao instanceof MedicaoDaRequisicao) {
            return;
        }

        rescue(function () use ($request, $medicao): void {
            app(BufferDeRequisicoes::class)->anexar($medicao->paraRegistro(
                contexto: ClassificadorDeContexto::contexto($request),
                rota: ClassificadorDeContexto::rota($request),
                metodo: $request->method(),
                livewire: array_column(ClassificadorDeContexto::componentes($request), 'name'),
            ));
        }, null, false);
    }
}
