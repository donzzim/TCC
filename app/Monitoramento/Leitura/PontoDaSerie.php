<?php

namespace App\Monitoramento\Leitura;

final readonly class PontoDaSerie
{
    public function __construct(
        public int $instante,
        public array $valores,
    ) {
    }
}
