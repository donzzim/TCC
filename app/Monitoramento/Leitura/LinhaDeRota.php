<?php

namespace App\Monitoramento\Leitura;

final readonly class LinhaDeRota
{
    public function __construct(
        public string $contexto,
        public string $rota,
        public int $qtd,
        public ?float $p50,
        public ?float $p95,
        public float $maxMs,
        public float $tempoTotalMs,
        public float $sqlMediaQtd,
        public float $percentualSql,
        public float $maxMemoriaMb,
        public int $nMaisUm,
        public int $erros5xx,
    ) {
    }
}
