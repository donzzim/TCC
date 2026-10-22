<?php

namespace App\Http\Controllers\Egap\PrintsControllers;

use App\Filament\Egap\Interno\Groups\Pedidos\Components\EntregaRecolhimentoTable;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class EntregaRecolhimentoPrintController extends Controller
{
    public function print(): View
    {
        return view('egap.pedidos.entrega_recolhimento_impressao', [
            'termos' => EntregaRecolhimentoTable::getQuery()
                ->orderByDesc('date_time')
                ->get(),
        ]);
    }
}
