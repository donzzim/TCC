<?php

namespace App\Services\Egap\Patrimonio;

final readonly class RecalcularDepreciacaoResultado
{
    public function __construct(
        public int $patrimonioId,
        public int $parcelasGeradas,
        public float $depreciacaoMensal,
        public float $valorResidual,
    ) {}
}
