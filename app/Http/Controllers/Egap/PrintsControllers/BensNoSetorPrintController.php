<?php

namespace App\Http\Controllers\Egap\PrintsControllers;

use App\Filament\Egap\Externo\Groups\Patrimonio\Components\BensNoSetorTable;
use App\Filament\Egap\Support\SetorSelecionado;
use App\Http\Controllers\Controller;
use App\Models\egap\Cadastro\Setores;
use Illuminate\Contracts\View\View;

class BensNoSetorPrintController extends Controller
{
    /**
     * Imprime os bens do setor escolhido no seletor da barra superior. O setor vem
     * da sessão (mesma resolução da tabela), nunca da URL, para que o usuário só
     * imprima bens de uma lotação sua.
     */
    public function print(): View
    {
        $setorId = SetorSelecionado::resolverAtual();

        abort_if(blank($setorId), 403, 'Não foi possível identificar o setor do usuário atual.');

        $setor = Setores::query()->find($setorId);
        $unidadeId = SetorSelecionado::resolverUnidadeAtual();
        $unidade = $unidadeId ? Setores::query()->find($unidadeId) : null;

        return view('egap.patrimonio.bens_no_setor_impressao', [
            'bens' => BensNoSetorTable::queryDoSetor($setorId)
                ->select([
                    'id',
                    'NumPatrimonio',
                    'NumerodePatAnterior',
                    'Descricao',
                    'Marca',
                    'Modelo',
                    'ComplementoSetor',
                    'EstadodeConservacao',
                    'SituacaoBem',
                ])
                ->orderBy('Descricao')
                ->get(),
            'setor' => $setor?->setor ?? "Setor {$setorId}",
            'unidade' => $unidade?->unidadeorganizacional ?: $unidade?->setor,
        ]);
    }
}
