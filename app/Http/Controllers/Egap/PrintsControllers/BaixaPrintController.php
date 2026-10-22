<?php

namespace App\Http\Controllers\Egap\PrintsControllers;

use App\Http\Controllers\Controller;
use App\Models\egap\Patrimonio\BensMoveis\Baixa;
use App\Models\egap\Patrimonio\BensMoveis\ItemBaixa;
use Illuminate\Support\Facades\DB;

class BaixaPrintController extends PrintController
{
    public function print($id)
    {
        $baixa = Baixa::where('id', $id)
            ->first();

        if (!$baixa) {
            abort(404, 'Processo de baixa não encontrado.');
        }

        $numeroProcesso = $baixa->NumeroProcesso ?? $baixa->numero_processo ?? 'NÃO INFORMADO';

        $itens =ItemBaixa::join('egap_mat_patrimonio', 'egap_mat_itembaixa.id_bem', '=', 'egap_mat_patrimonio.id')
            ->where('egap_mat_itembaixa.id_baixa', $id)
            ->select([
                'egap_mat_patrimonio.NumPatrimonio',
                'egap_mat_patrimonio.Descricao as MaterialDescricao',
                'egap_mat_patrimonio.ValorAquisicao',
                'egap_mat_patrimonio.ValordaReavaliacao',
                'egap_mat_patrimonio.NumerodeSerie',
            ])
            ->get();

        $totalAquisicao = $itens->sum('ValorAquisicao');
        $totalReavaliado = $itens->sum('ValordaReavaliacao');

        return view('egap.patrimonio.impressao-baixa', [
            'baixa'          => $baixa,
            'numeroProcesso' => $numeroProcesso,
            'itens'          => $itens,
            'totalAquisicao' => $totalAquisicao,
            'totalReavaliado'=> $totalReavaliado
        ]);
    }
}
