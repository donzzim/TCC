<?php

namespace Tests\Feature;

use App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\InventarioResource;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\InventarioResource\Pages\CreateInventario;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\InventarioResource\Pages\EditInventario;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\InventarioResource\Pages\ListInventarios;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\InventarioResource\RelationManagers\UnidadesInventariadasRelationManager;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\InventarioUnidadeResource;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\InventarioUnidadeResource\Pages\ListInventarioUnidades;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\ItemInventarioResource\Pages\ListItemInventarios;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\AtividadeInventarioResource\Pages\EditAtividadeInventario;
use App\Models\egap\Cadastro\Setores;
use App\Models\egap\Patrimonio\BensMoveis\AtividadeInventario;
use Filament\Forms\Components\Select;
use App\Models\egap\Patrimonio\BensMoveis\BemMovel;
use App\Models\egap\Patrimonio\BensMoveis\Inventario;
use App\Models\egap\Patrimonio\BensMoveis\InventarioComissao;
use App\Models\egap\Patrimonio\BensMoveis\InventarioUnidade;
use App\Models\egap\Patrimonio\BensMoveis\ItemInventario;
use App\Models\egap\Patrimonio\BensMoveis\TransferenciaBemMovel;
use App\Models\egap\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Telas de abertura do inventário no painel interno (como administrador).
 */
class EgapInventarioPainelTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['emes', 'egap'];

    protected function setUp(): void
    {
        parent::setUp();

        $admin = User::query()->get()->first(fn (User $u): bool => $u->isAdminEgap());

        if (! $admin) {
            $this->markTestSkipped('nenhum administrador do EGAP neste banco');
        }

        $this->actingAs($admin, User::GUARD);
        Filament::setCurrentPanel('egap');
    }

    public function test_telas_de_inventario_abrem(): void
    {
        $inventario = Inventario::query()->latest('id')->firstOrFail();
        $unidade = InventarioUnidade::query()->latest('id')->firstOrFail();

        $this->get(InventarioResource::getUrl('edit', ['record' => $inventario]))->assertOk();
        $this->get(InventarioUnidadeResource::getUrl('create'))->assertOk();
        $this->get(InventarioUnidadeResource::getUrl('edit', ['record' => $unidade]))->assertOk();

        Livewire::test(ListInventarios::class)->mountAction(TestAction::make('view')->table($inventario))->assertOk();
        Livewire::test(ListInventarioUnidades::class)->mountAction(TestAction::make('view')->table($unidade))->assertOk();
    }

    public function test_unidades_do_inventario_ficam_numa_tabela_paginada(): void
    {
        $grande = Inventario::query()->withCount('unidadesInventariadas')->orderByDesc('unidades_inventariadas_count')->firstOrFail();

        Livewire::test(UnidadesInventariadasRelationManager::class, [
            'ownerRecord' => $grande,
            'pageClass' => EditInventario::class,
        ])
            ->assertOk()
            ->assertCountTableRecords($grande->unidades_inventariadas_count);
    }


    public function test_adiciona_unidade_pelo_modal_com_datas_validas(): void
    {
        $inventario = Inventario::query()->latest('id')->firstOrFail();
        $setor = Setores::query()->setoresInventariaveis()->firstOrFail();
        $antes = $inventario->unidadesInventariadas()->count();
        $dados = fn(string $inicio, string $termino): array => [
            'tipo_abrangencia' => 'setor',
            'unidades' => $setor->id,
            'data_inicio' => $inicio,
            'data_termino' => $termino,
            'situacao' => Inventario::SITUACAO_A_INVENTARIAR,
        ];
        $tabela = fn() => Livewire::test(UnidadesInventariadasRelationManager::class, [
            'ownerRecord' => $inventario,
            'pageClass' => EditInventario::class,
        ]);

        $tabela()->callAction(TestAction::make('create')->table(), $dados('2026-11-10', '2026-11-01'))
            ->assertHasActionErrors(['data_termino' => 'after_or_equal']);

        $tabela()->callAction(TestAction::make('create')->table(), $dados('2026-11-01', '2026-11-10'))
            ->assertHasNoActionErrors();

        $nova = $inventario->unidadesInventariadas()->latest('id')->firstOrFail();
        $this->assertSame($antes + 1, $inventario->unidadesInventariadas()->count());
        $this->assertSame((int) $setor->id, (int) $nova->unidades);
        $this->assertSame(9, (int) $nova->dias);
    }

    public function test_unidades_aparecem_pelo_nome(): void
    {
        $unidade = Setores::query()->unidadesInventariaveis()->whereNotNull('unidadeorganizacional')->firstOrFail();

        $this->assertSame((int) $unidade->id, (int) $unidade->codigopai);
        $this->assertSame($unidade->unidadeorganizacional, $unidade->rotuloInventario());

        $setor = Setores::query()->filhosDe((int) $unidade->id)->first();

        if ($setor !== null) {
            $this->assertNotSame((int) $setor->id, (int) $setor->codigopai);
            $this->assertTrue(Setores::query()->setoresInventariaveis()->whereKey($setor->id)->exists());
        }

        // a tabela de unidades mostra o nome, não "Unidade 123"
        $vinculo = InventarioUnidade::query()->with('unidade')->get()
            ->first(fn(InventarioUnidade $u): bool => (bool) $u->unidade?->unidadeorganizacional);

        Livewire::test(UnidadesInventariadasRelationManager::class, [
            'ownerRecord' => $vinculo->inventario,
            'pageClass' => EditInventario::class,
        ])
            ->assertSee(e($vinculo->rotuloUnidadeInventariada()), false);
    }

    public function test_finalizar_levantamento_guarda_o_local_anterior_na_transferencia(): void
    {
        $bem = BemMovel::query()->whereIn('SituacaoBem', [1, 7, 8])->whereNotNull('NumPatrimonio')->where('Setor', '>', 0)->firstOrFail();
        $destino = Setores::query()->setoresInventariaveis()->where('id', '<>', $bem->Setor)->firstOrFail();
        $item = ItemInventario::query()->create([
            'date_time' => now(),
            'id_bem' => $bem->id,
            'num_patrimonio' => $bem->NumPatrimonio,
            'unidades' => $destino->codigopai,
            'setor' => $destino->id,
            'id_inventario' => Inventario::query()->latest('id')->value('id'),
            'situacao' => 'LOCALIZADO',
        ]);

        Livewire::test(ListItemInventarios::class)
            ->callAction(TestAction::make('finalizar_levantamento')->table($item));

        $transferencia = TransferenciaBemMovel::query()->where('NumPatrimonio', $bem->id)->latest('id')->firstOrFail();

        $this->assertSame((string) $bem->Setor, (string) $transferencia->SetorAnterior);
        $this->assertSame((string) $bem->UnidadeJudiciaria, (string) $transferencia->UnidadeAnterior);
        $this->assertSame((string) $destino->id, (string) $transferencia->SetorAtual);
        $this->assertSame((int) $destino->id, (int) $bem->fresh()->Setor);
    }

    public function test_so_um_inventario_em_execucao_por_vez(): void
    {
        [$a, $b] = Inventario::query()->ativos()->latest('id')->take(2)->get()->all();
        $a->update(['situacao' => Inventario::SITUACAO_EM_EXECUCAO]);

        Livewire::test(EditInventario::class, ['record' => $b->getKey()])
            ->fillForm(['situacao' => Inventario::SITUACAO_EM_EXECUCAO])
            ->call('save')
            ->assertHasFormErrors(['situacao']);

        // o próprio inventário em execução continua podendo ser salvo
        Livewire::test(EditInventario::class, ['record' => $a->getKey()])
            ->call('save')
            ->assertHasNoFormErrors();
    }

    public function test_atualizar_situacao_preserva_o_que_ja_foi_conferido(): void
    {
        // inventário novo, só com o setor do teste (os reais abrangem milhares de bens)
        $inventario = Inventario::query()->create([
            'num_inventario' => '99',
            'ano_inventario' => 2026,
            'situacao' => Inventario::SITUACAO_A_INVENTARIAR,
        ]);
        $setor = (int) BemMovel::query()->whereIn('SituacaoBem', [1, 7, 8])->where('Setor', '>', 0)
            ->groupBy('Setor')->havingRaw('count(*) >= 3')->value('Setor');
        [$conferido, $pendente, $baixado] = BemMovel::query()->where('Setor', $setor)->whereIn('SituacaoBem', [1, 7, 8])->take(3)->get()->all();
        $baixado->forceFill(['SituacaoBem' => 4, 'sit_inventario' => ''])->saveQuietly();
        $conferido->forceFill(['id_inventario' => $inventario->id, 'sit_inventario' => 'LOCALIZADO'])->saveQuietly();
        $pendente->forceFill(['sit_inventario' => ''])->saveQuietly();
        $inventario->unidadesInventariadas()->create(['unidades' => $setor, 'situacao' => Inventario::SITUACAO_A_INVENTARIAR]);

        Livewire::test(ListInventarios::class)
            ->callAction(TestAction::make('atualizar_situacao')->table($inventario));

        $this->assertSame('LOCALIZADO', $conferido->fresh()->sit_inventario);
        $this->assertSame('A INVENTARIAR', $pendente->fresh()->sit_inventario);
        $this->assertSame((int) $inventario->id, (int) $pendente->fresh()->id_inventario);
        $this->assertSame('', (string) $baixado->fresh()->sit_inventario);
    }

    public function test_situacao_da_atividade_e_uma_lista_que_mantem_o_valor_legado(): void
    {
        $legada = AtividadeInventario::query()->where('situacao', 'sem efeito')->firstOrFail();

        Livewire::test(EditAtividadeInventario::class, ['record' => $legada->getKey()])
            ->assertFormFieldExists('situacao', fn($campo): bool => $campo instanceof Select
                && array_key_exists('Finalizado', $campo->getOptions())
                && array_key_exists('carga efetuada', $campo->getOptions())
                && array_key_exists('sem efeito', $campo->getOptions()))
            ->assertSchemaStateSet(['situacao' => 'sem efeito']);
    }

    public function test_inventario_com_conferencia_nao_pode_ser_excluido(): void
    {
        $comItens = Inventario::query()->has('itens')->latest('id')->firstOrFail();
        $vazio = Inventario::query()->create([
            'num_inventario' => '99',
            'ano_inventario' => 2026,
            'situacao' => Inventario::SITUACAO_A_INVENTARIAR,
        ]);

        $this->assertFalse(InventarioResource::canDelete($comItens));
        $this->assertTrue(InventarioResource::canDelete($vazio));

        Livewire::test(ListInventarios::class)
            ->assertActionHidden(TestAction::make('delete')->table($comItens))
            ->callAction(TestAction::make('delete')->table($vazio));

        $this->assertNull(Inventario::query()->find($vazio->id));

        $this->expectException(\RuntimeException::class);
        $comItens->delete();
    }

    public function test_cria_inventario_com_unidade_inteira_e_setor_com_filhos(): void
    {
        $pai = Setores::query()->setoresInventariaveis()
            ->whereExists(fn ($q) => $q->from('setores as filho')->whereColumn('filho.codigopai', 'setores.id'))
            ->first();

        if (! $pai) {
            $this->markTestSkipped('nenhum setor com filhos neste banco');
        }

        $filhos = array_map('intval', Setores::descendentesIds($pai->id));

        $unidade = Setores::query()->unidadesInventariaveis()
            ->whereKeyNot($pai->id)
            ->whereNotIn('id', $filhos)
            ->get()
            ->first(fn (Setores $s): bool => ! in_array((int) $pai->id, array_map('intval', Setores::descendentesIds($s->id)), true));

        $this->assertNotNull($unidade);

        Livewire::test(CreateInventario::class)
            ->fillForm([
                'num_inventario' => '987654',
                'ano_inventario' => (int) date('Y'),
                'inicio_inventario' => '2030-01-10',
                'termino_inventario' => '2030-01-20',
                'situacao' => Inventario::SITUACAO_A_INVENTARIAR,
                'selecao_unidades' => [$unidade->id],
                'selecao_setores' => [['setor_id' => $pai->id, 'incluir_filhos' => true]],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $inventario = Inventario::query()->where('num_inventario', '987654')->firstOrFail();

        $this->assertSame(1 + 1 + count($filhos), $inventario->unidadesInventariadas()->count());
        $this->assertSame(1, $inventario->unidadesInventariadas()->where('unidades', $unidade->id)->count());
    }

    public function test_membro_antigo_inexistente_nao_impede_salvar(): void
    {
        $comissao = InventarioComissao::query()->whereDoesntHave('membroRef')->latest('id')->firstOrFail();

        Livewire::test(EditInventario::class, ['record' => $comissao->id_inventario])
            ->call('save')
            ->assertHasNoFormErrors();
    }
}
