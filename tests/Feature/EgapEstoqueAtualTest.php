<?php

namespace Tests\Feature;

use App\Models\egap\Almoxarifado\MovimentacaoEstoque;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EgapEstoqueAtualTest extends TestCase
{
    public function test_estoques_atuais_trazem_a_ultima_movimentacao_de_cada_material(): void
    {
        $materiais = DB::connection('emes')->table('egap_alm_estoque')
            ->whereNotNull('material')
            ->groupBy('material')
            ->orderByRaw('count(*) desc')
            ->limit(15)
            ->pluck('material')
            ->map(fn ($id): int => (int) $id)
            ->all();

        if ($materiais === []) {
            $this->markTestSkipped('nenhuma movimentação de estoque neste banco');
        }

        $esperado = DB::connection('emes')->table('egap_alm_estoque')
            ->whereIn('material', $materiais)
            ->groupBy('material')
            ->selectRaw('material, max(id) as id')
            ->pluck('id', 'material')
            ->map(fn ($id): int => (int) $id)
            ->sortKeys()
            ->all();

        $obtido = MovimentacaoEstoque::estoquesAtuais($materiais)
            ->map(fn (MovimentacaoEstoque $m): int => (int) $m->id)
            ->sortKeys()
            ->all();

        $this->assertSame($esperado, $obtido);
        $this->assertSame([], MovimentacaoEstoque::estoquesAtuais([])->all());
    }
}
