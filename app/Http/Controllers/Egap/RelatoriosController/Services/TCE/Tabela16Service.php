<?php

namespace App\Http\Controllers\Egap\RelatoriosController\Services\TCE;

use App\Http\Controllers\Egap\RelatoriosController\Services\TCE\Support\ResumoInventarioAlmoxarifadoTce;

/**
 * TCE - Tabela 16: Resumo do Inventario do Almoxarifado - Material Permanente.
 *
 * Legado: relatorios-prints-queries/tce/tabela-16.php
 *
 * Identica a Tabela 14, porem dr.id_tipo_material = 'P'.
 * Ver ResumoInventarioAlmoxarifadoTce.
 */
final class Tabela16Service extends ResumoInventarioAlmoxarifadoTce
{
    protected function operadorTipoMaterial(): string
    {
        return '=';
    }
}
