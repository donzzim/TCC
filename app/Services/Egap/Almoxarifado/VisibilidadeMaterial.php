<?php

namespace App\Services\Egap\Almoxarifado;

class VisibilidadeMaterial
{
    public static function permitidas(?int $unidadeJudiciaria): array
    {
        return in_array($unidadeJudiciaria, [766, 866], true)
            ? [2, 3]
            : [1, 3];
    }
}
