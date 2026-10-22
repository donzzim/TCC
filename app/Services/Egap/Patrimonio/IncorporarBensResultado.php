<?php

namespace App\Services\Egap\Patrimonio;

final readonly class IncorporarBensResultado
{
    public function __construct(
        public int $termoId,
        public int $numeroTermo,
        public int $anoTermo,
        public int $quantidadeBens,
    ) {}
}
