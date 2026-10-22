<?php

namespace App\Http\Controllers\Egap\PrintsControllers;

use App\Http\Controllers\Controller;
use App\Models\egap\Almoxarifado\Pedidos;

class PedidosPrintController extends Controller
{
    public function show(Pedidos $record)
    {
        $pedido = $record->load([
            'solicitante_get',
            'responsavel_atendimento',
            'setor_get',
            'complementoSetor',
            'itens.materialRef',
            'itens.descricaoDetalhadaRef',
        ]);

        return view('egap.pedidos.pedidos_impressao', compact('pedido'));
    }
}
