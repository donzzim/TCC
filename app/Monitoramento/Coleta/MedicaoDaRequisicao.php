<?php

namespace App\Monitoramento\Coleta;

use Illuminate\Support\Str;
use Throwable;

final class MedicaoDaRequisicao
{
    private float $sqlMs = 0;

    private int $sqlQtd = 0;

    private array $consultas = [];

    private ?float $fim = null;

    private int $status = 0;

    private ?Throwable $erro = null;

    public function __construct(
        public readonly float $inicio,
        public readonly string $requestId,
    ) {
    }

    public function registrarConsulta(string $conexao, string $sql, float $tempoMs): void
    {
        if ($this->fim !== null) {
            return;
        }

        $this->sqlMs += $tempoMs;
        $this->sqlQtd++;

        $normalizada = self::normalizarSql($sql);
        $chave = $conexao.':'.$normalizada;

        if (! isset($this->consultas[$chave])) {
            $this->consultas[$chave] = ['connection' => $conexao, 'SQL' => $normalizada, 'count' => 0, 'time_ms' => 0.0];
        }

        $this->consultas[$chave]['count']++;
        $this->consultas[$chave]['time_ms'] += $tempoMs;
    }

    public function encerrar(int $status, ?Throwable $erro): void
    {
        $this->fim ??= microtime(true);
        $this->status = $status;
        $this->erro = $erro;
    }

    public function paraRegistro(string $contexto, string $rota, string $metodo, array $livewire): RegistroDeRequisicao
    {
        $duracaoMs = round((($this->fim ?? microtime(true)) - $this->inicio) * 1000, 2);
        $limiarNMaisUm = (int) config('monitoramento.limiar_n_mais_um', 20);
        $nMaisUm = collect($this->consultas)->contains(fn (array $c): bool => $c['count'] >= $limiarNMaisUm);

        return new RegistroDeRequisicao(
            instante: $this->inicio,
            contexto: $contexto,
            rota: $rota,
            metodo: $metodo,
            status: $this->status,
            duracaoMs: $duracaoMs,
            sqlMs: round($this->sqlMs, 2),
            sqlQtd: $this->sqlQtd,
            memoriaMb: round(memory_get_peak_usage(true) / 1048576, 2),
            livewire: $livewire,
            nMaisUm: $nMaisUm,
            requestId: $this->requestId,
            detalhe: $this->deveDetalhar($duracaoMs) ? $this->detalhe() : null,
        );
    }

    public static function normalizarSql(string $sql): string
    {
        return mb_strimwidth((string) preg_replace('/\s+/', ' ', trim($sql)), 0, 1200, '...');
    }

    private function deveDetalhar(float $duracaoMs): bool
    {
        if ($duracaoMs >= (float) config('monitoramento.limiar_lenta_ms', 1000) || $this->status >= 500) {
            return true;
        }

        $amostragem = (float) config('monitoramento.amostragem', 0.01);

        return $amostragem > 0 && mt_rand() / mt_getrandmax() < $amostragem;
    }

    private function detalhe(): array
    {
        $consultas = array_values($this->consultas);
        usort($consultas, fn (array $a, array $b): int => $b['time_ms'] <=> $a['time_ms']);

        return [
            'top_consultas' => array_map(
                fn (array $c): array => [...$c, 'time_ms' => round($c['time_ms'], 2)],
                array_slice($consultas, 0, 10)
            ),
            'erro' => $this->erro === null ? null : [
                'class' => $this->erro::class,
                'message' => Str::limit($this->erro->getMessage(), 300),
                'file' => $this->erro->getFile(),
                'line' => $this->erro->getLine(),
            ],
        ];
    }
}
