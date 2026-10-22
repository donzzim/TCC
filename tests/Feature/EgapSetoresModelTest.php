<?php

namespace Tests\Feature;

use App\Filament\Egap\Interno\Groups\Cadastro\Resources\SetoresResource;
use App\Models\egap\Cadastro\Setores;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * `App\Models\egap\Cadastro\Setores` passou de `egap_mat_setores` para
 * `emes.setores`. Como os ids foram preservados na migração, a troca é de
 * código — mas a grafia das colunas mudou, e `presidencia` mudou de
 * representação. Estes testes cobrem as duas armadilhas.
 *
 * Roda contra o banco real; a única escrita acontece dentro de uma transação
 * que é sempre desfeita.
 */
class EgapSetoresModelTest extends TestCase
{
    public function test_it_reads_from_the_new_table(): void
    {
        $this->assertSame('setores', (new Setores)->getTable());
        $this->assertSame('emes', (new Setores)->getConnectionName());
    }

    public function test_both_column_spellings_read_the_same_value(): void
    {
        $setor = Setores::query()->whereNotNull('codigopai')->firstOrFail();

        $this->assertSame($setor->setor, $setor->Setor);
        $this->assertSame($setor->unidadeorganizacional, $setor->UnidadeOrganizacional);
        $this->assertSame($setor->codigopai, $setor->CodigoPai);
        $this->assertSame($setor->codigodauo, $setor->CodigodaUO);
    }

    public function test_the_legacy_spelling_writes_to_the_real_column(): void
    {
        $novo = new Setores([
            'Setor' => 'TESTE',
            'CodigoPai' => 77,
            'UnidadeOrganizacional' => 'UO TESTE',
            'CodigodaUO' => 88,
        ]);

        // nada de `Setor`/`CodigoPai` cru: a tabela não tem essas colunas
        $this->assertSame(
            ['setor' => 'TESTE', 'codigopai' => 77, 'unidadeorganizacional' => 'UO TESTE', 'codigodauo' => 88],
            $novo->getAttributes(),
        );
    }

    public function test_presidencia_is_text_and_reads_back_as_boolean(): void
    {
        $presidencia = Setores::query()->where('presidencia', 'Sim')->first();
        $naoPresidencia = Setores::query()->where('presidencia', 'Não')->first();

        $this->assertNotNull($presidencia, 'a coluna guarda o texto Sim/Não');
        $this->assertTrue($presidencia->ehPresidencia);
        $this->assertFalse($naoPresidencia->ehPresidencia);

        // e o caminho de volta grava texto, não 0/1
        $novo = new Setores;
        $novo->ehPresidencia = true;
        $this->assertSame('Sim', $novo->getAttributes()['presidencia']);

        $novo->ehPresidencia = false;
        $this->assertSame('Não', $novo->getAttributes()['presidencia']);
    }

    /**
     * `Builder::pluck()` do Eloquent passa a rotear pelo model assim que
     * existe mutator para a coluna, e hidrata com o nome escrito no select.
     * Sem o fallback de grafia no acessor, todo `pluck('UnidadeOrganizacional',
     * 'CodigoPai')` do app devolvia rótulos nulos — e um rótulo nulo derruba o
     * Select do Filament com TypeError, não com uma tela vazia.
     */
    public function test_pluck_works_with_either_column_spelling(): void
    {
        $antiga = Setores::query()
            ->whereColumn('id', 'CodigoPai')
            ->pluck('UnidadeOrganizacional', 'CodigoPai');

        $nova = Setores::query()
            ->whereColumn('id', 'codigopai')
            ->pluck('unidadeorganizacional', 'codigopai');

        $this->assertGreaterThan(70, $antiga->count());
        $this->assertSame($nova->count(), $antiga->count());
        $this->assertCount(0, $antiga->filter(fn ($v) => $v === null), 'nenhum rótulo pode vir nulo');
        $this->assertSame($nova->sort()->values()->all(), $antiga->sort()->values()->all());
    }

    public function test_the_unidades_scope_excludes_the_sentinel_row(): void
    {
        $unidades = Setores::query()->unidadesOrganizacionais()->pluck('id');

        $this->assertGreaterThan(70, $unidades->count());
        $this->assertNotContains(0, $unidades->all(), 'a linha 0 = SETOR EXTERNO não é unidade');

        foreach (Setores::query()->unidadesOrganizacionais()->limit(5)->get() as $unidade) {
            $this->assertSame((int) $unidade->id, (int) $unidade->codigopai);
            $this->assertTrue($unidade->inventariaUnidadeInteira());
        }
    }

    public function test_relations_still_resolve(): void
    {
        $setor = Setores::query()
            ->whereColumn('id', '<>', 'codigopai')
            ->whereNotNull('codigopai')
            ->whereHas('pai')
            ->firstOrFail();

        $this->assertNotNull($setor->pai);
        $this->assertSame((int) $setor->codigopai, (int) $setor->pai->id);
        $this->assertStringContainsString($setor->setor, $setor->rotuloInventario());
    }

    public function test_the_resource_normalizer_emits_new_column_names(): void
    {
        $unidade = Setores::query()->unidadesOrganizacionais()->firstOrFail();

        $dados = SetoresResource::normalizeUnidadeOrganizacionalData([
            'unidade_organizacional_id' => $unidade->id,
            'setor' => 'Setor de Teste',
            'funcao' => 'C',
            'ehPresidencia' => true,
        ]);

        $this->assertSame($unidade->id, $dados['codigopai']);
        $this->assertSame($unidade->id, $dados['codigodauo']);
        $this->assertSame($unidade->unidadeorganizacional, $dados['unidadeorganizacional']);
        $this->assertSame('Sim', $dados['presidencia']);
        $this->assertSame('C', $dados['comarca']);

        // chaves que não são coluna não podem sobrar
        $this->assertArrayNotHasKey('unidade_organizacional_id', $dados);
        $this->assertArrayNotHasKey('funcao', $dados);
        $this->assertArrayNotHasKey('ehPresidencia', $dados);
    }

    public function test_it_actually_persists_against_the_new_table(): void
    {
        $unidade = Setores::query()->unidadesOrganizacionais()->firstOrFail();

        DB::connection('emes')->beginTransaction();

        try {
            $dados = SetoresResource::normalizeUnidadeOrganizacionalData([
                'unidade_organizacional_id' => $unidade->id,
                'setor' => 'SETOR DE TESTE AUTOMATIZADO',
                'funcao' => 'all',
                'ehPresidencia' => false,
            ]);

            $criado = Setores::query()->create($dados);

            $this->assertNotNull($criado->id);
            $this->assertSame('SETOR DE TESTE AUTOMATIZADO', $criado->fresh()->setor);
            $this->assertSame('Não', $criado->fresh()->presidencia);
            $this->assertGreaterThanOrEqual(100_000, (int) $criado->id, 'id novo tem de nascer fora da faixa legada');

            $criado->update(['setor' => 'SETOR DE TESTE EDITADO']);
            $this->assertSame('SETOR DE TESTE EDITADO', $criado->fresh()->setor);
            $this->assertNotNull($criado->fresh()->updated_at, '`updated_at` tem de ser tocado');
        } finally {
            DB::connection('emes')->rollBack();
        }

        $this->assertSame(
            0,
            Setores::query()->where('setor', 'like', 'SETOR DE TESTE%')->count(),
            'o rollback tem de ter desfeito tudo'
        );
    }
}
