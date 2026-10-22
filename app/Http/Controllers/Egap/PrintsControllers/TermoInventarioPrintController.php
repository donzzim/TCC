<?php

namespace App\Http\Controllers\Egap\PrintsControllers;

use App\Helper\CpfHelper;
use App\Http\Controllers\Controller;
use App\Models\egap\Patrimonio\BensMoveis\ItemInventario;
use App\Models\egap\Patrimonio\BensMoveis\Termo;
use Illuminate\Support\Carbon;

class TermoInventarioPrintController extends PrintController
{
    public function print($id)
    {
        $termo = Termo::query()
            ->with(['arquivoDigital.validadoPor.infoUser', 'responsavelRef.infoUser'])
            ->findOrFail($id);

        $itens = ItemInventario::query()
            ->where('termo', $termo->id)
            ->with('complementoSetorRef')
            ->orderBy('num_patrimonio')
            ->get();

        $primeiroItem = $itens->first();

        $arquivoDigital = $termo->arquivoDigital;
        $responsavel = $arquivoDigital?->validadoPor ?? $termo->responsavelRef;
        $infoResponsavel = $responsavel?->infoUser;

        return view('egap.patrimonio.termo_inventario_impresso', [
            'termo' => $termo,
            'itens' => $itens,
            'unidade' => $primeiroItem?->unidade_localizado ?: 'NÃO INFORMADO',
            'setor' => $primeiroItem?->setor_localizado ?: 'NÃO INFORMADO',
            'complemento' => $primeiroItem?->complementoSetorRef?->descricao ?: ($primeiroItem?->complemento_localizado ?: 'NÃO INFORMADO'),
            'responsavel' => $responsavel?->name ?: 'NÃO INFORMADO',
            'cargo' => $infoResponsavel?->cargo ?: 'SERVIDOR',
            'cpf' => CpfHelper::format($infoResponsavel?->cpf),
            'dataAssinatura' => Carbon::parse($arquivoDigital?->data_validacao ?? $termo->date_time ?? now())->format('d/m/Y H:i:s'),
        ]);
    }
}
