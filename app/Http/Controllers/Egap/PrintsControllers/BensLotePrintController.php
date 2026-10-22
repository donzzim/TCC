<?php

namespace App\Http\Controllers\Egap\PrintsControllers;

use App\Http\Controllers\Controller;
use App\Models\egap\Patrimonio\BensMoveis\BemMovel;
use Illuminate\Contracts\View\View;

class BensLotePrintController extends Controller
{
    /**
     * Imprime os bens selecionados, recebidos como ids separados por vírgula (ex.: 10,25,31).
     */
    public function print(string $ids): View
    {
        $itemIds = collect(explode(',', $ids))
            ->map(fn (string $id): int => (int) trim($id))
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        abort_if($itemIds->isEmpty(), 404, 'Nenhum bem informado para impressão.');

        $bens = BemMovel::query()
            ->with([
                'marcaRef',
                'modeloRef',
                'unidadeJudiciariaRef',
                'setorRef',
                'situacaoBemRef',
            ])
            ->whereIn('id', $itemIds)
            ->get();

        return view('egap.patrimonio.relatorio-bens-lote', [
            'bens' => $bens,
        ]);
    }
}
