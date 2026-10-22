<?php

namespace App\Http\Controllers\Egap\RelatoriosController\Services\TCE;

use App\Http\Controllers\Egap\RelatoriosController\Services\TCE\Support\ResumoInventarioAlmoxarifadoTce;

/**
 * TCE - Tabela 14: Resumo do Inventario do Almoxarifado - Material de Consumo.
 *
 * Legado: relatorios-prints-queries/tce/tabela-14.php
 *
 * dr.id_tipo_material <> 'P'. Ver ResumoInventarioAlmoxarifadoTce.
 */
final class Tabela14Service extends ResumoInventarioAlmoxarifadoTce
{
    protected function operadorTipoMaterial(): string
    {
        return '<>';
    }
}
