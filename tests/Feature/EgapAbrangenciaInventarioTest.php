<?php

namespace Tests\Feature;

use App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\InventarioResource\Pages\ListInventarios;
use App\Models\egap\Cadastro\Setores;
use App\Models\egap\Patrimonio\BensMoveis\BemMovel;
use App\Models\egap\Patrimonio\BensMoveis\Inventario;
use App\Models\egap\User;
use App\Services\Egap\Inventario\AbrangenciaInventario;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Setores cobertos por um inventário (cascata em todos os níveis) e seus prazos.
 */
class EgapAbrangenciaInventarioTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['emes', 'egap'];

    private function novoInventario(array $extra = []): Inventario
    {
        return Inventario::query()->create($extra + [
            'num_inventario' => '99',
            'ano_inventario' => 2026,
            'situacao' => Inventario::SITUACAO_A_INVENTARIAR,
        ]);
    }

    /** Unidade inteira que tem ao menos um neto (setor cujo pai não é a unidade). */
    private function unidadeComNetos(): array
    {
        foreach (Setores::query()->unidadesInventariaveis()->get() as $unidade) {
            $ids = array_map('intval', Setores::descendentesIds((int) $unidade->id));
            $netos = Setores::query()->whereIn('id', $ids)->where('codigopai', '<>', $unidade->id)
                ->pluck('id')->map(fn ($id): int => (int) $id)->all();

            if ($netos !== []) {
                return [$unidade, $ids, $netos];
            }
        }

        $this->markTestSkipped('nenhuma unidade com netos neste banco');
    }

    public function test_unidade_inteira_cobre_todos_os_niveis(): void
    {
        [$unidade, $descendentes, $netos] = $this->unidadeComNetos();
        $inventario = $this->novoInventario();
        $inventario->unidadesInventariadas()->create(['unidades' => $unidade->id, 'situacao' => Inventario::SITUACAO_A_INVENTARIAR]);

        $cobertos = AbrangenciaInventario::setoresCobertos($inventario);

        $this->assertTrue($cobertos->has((int) $unidade->id));
        foreach ($descendentes as $id) {
            $this->assertTrue($cobertos->has($id));
        }
        $this->assertTrue($cobertos->has($netos[0]));
    }

    public function test_prazo_da_linha_ou_do_inventario(): void
    {
        [$unidade, , $netos] = $this->unidadeComNetos();
        $inventario = $this->novoInventario(['termino_inventario' => '2026-12-20 00:00:00']);
        $inventario->unidadesInventariadas()->create(['unidades' => $netos[0], 'data_termino' => '2026-11-30 00:00:00', 'situacao' => Inventario::SITUACAO_A_INVENTARIAR]);
        $inventario->unidadesInventariadas()->create(['unidades' => $unidade->id, 'situacao' => Inventario::SITUACAO_A_INVENTARIAR]);

        $cobertos = AbrangenciaInventario::setoresCobertos($inventario);

        // a linha de setor específico vence a da unidade, e usa o prazo dela
        $this->assertSame('2026-11-30', $cobertos[$netos[0]]['prazo']->toDateString());
        // a unidade não tem prazo próprio: vale o do inventário
        $this->assertSame('2026-12-20', $cobertos[(int) $unidade->id]['prazo']->toDateString());
    }

    public function test_registrar_expande_filhos_e_nao_duplica(): void
    {
        $pai = Setores::query()->whereColumn('id', '<>', 'codigopai')->get()
            ->first(fn (Setores $s): bool => Setores::descendentesIds((int) $s->id) !== []);

        if ($pai === null) {
            $this->markTestSkipped('nenhum setor com filhos neste banco');
        }

        $inventario = $this->novoInventario();
        $n = count(Setores::descendentesIds((int) $pai->id));
        $setores = [['setor_id' => (int) $pai->id, 'incluir_filhos' => true]];

        $this->assertSame(1 + $n, AbrangenciaInventario::registrar($inventario, [], $setores));
        $this->assertSame(0, AbrangenciaInventario::registrar($inventario, [], $setores));
        $this->assertSame(1 + $n, $inventario->unidadesInventariadas()->count());
    }

    public function test_atualizar_situacao_alcanca_netos(): void
    {
        $admin = User::query()->get()->first(fn (User $u): bool => $u->isAdminEgap());
        if (! $admin) {
            $this->markTestSkipped('nenhum administrador do EGAP neste banco');
        }
        $this->actingAs($admin, User::GUARD);
        Filament::setCurrentPanel('egap');

        [$unidade, , $netos] = $this->unidadeComNetos();
        $bem = BemMovel::query()->whereIn('SituacaoBem', [1, 7, 8])->whereIn('Setor', $netos)->first();

        if ($bem === null) {
            $this->markTestSkipped('nenhum bem ativo em neto de unidade');
        }

        $bem->forceFill(['sit_inventario' => '', 'id_inventario' => null])->saveQuietly();
        $inventario = $this->novoInventario();
        $inventario->unidadesInventariadas()->create(['unidades' => $unidade->id, 'situacao' => Inventario::SITUACAO_A_INVENTARIAR]);

        Livewire::test(ListInventarios::class)
            ->callAction(TestAction::make('atualizar_situacao')->table($inventario));

        $this->assertSame('A INVENTARIAR', $bem->fresh()->sit_inventario);
        $this->assertSame((int) $inventario->id, (int) $bem->fresh()->id_inventario);
    }
}
