<?php

namespace App\Monitoramento\Leitura;

final readonly class ResumoDoPeriodo
{
    public function __construct(
        public int $qtd,
        public int $erros5xx,
        public int $erros4xx,
        public ?float $p50,
        public ?float $p95,
        public ?float $p99,
        public float $reqPorMin,
        public float $taxaErro5xx,
    ) {
    }
}
