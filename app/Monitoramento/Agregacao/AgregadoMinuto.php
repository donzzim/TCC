<?php

namespace App\Monitoramento\Agregacao;

final readonly class AgregadoMinuto
{
    public function __construct(
        public int $minuto,
        public string $contexto,
        public string $rota,
        public int $qtd,
        public int $erros4xx,
        public int $erros5xx,
        public float $somaMs,
        public float $maxMs,
        public float $somaSqlMs,
        public int $somaSqlQtd,
        public float $maxMemoriaMb,
        public int $nMaisUm,
        public array $faixas,
    ) {
    }
}
