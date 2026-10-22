<?php

namespace Tests\Unit;

use App\Models\Escola\CursoAtividades;
use App\Models\Escola\Cursos;
use App\Services\Auto\Support\InscricaoConfirmationSelector;
use Illuminate\Support\Collection;
use Tests\TestCase;

class InscricaoConfirmationSelectorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('settings.inscricao.preinscricao', 2801);
        config()->set('settings.inscricao.inscrito', 2802);
    }

    public function test_it_accepts_only_supported_criteria_and_removes_duplicates(): void
    {
        $selector = app(InscricaoConfirmationSelector::class);

        $this->assertSame(
            ['publico-alvo', 'setor', 'sem-criterio'],
            $selector->criteria([
                'inscricao' => [
                    'regras' => [
                        'criterios' => [
                            'publico-alvo',
                            'criterio-inexistente',
                            'setor',
                            'publico-alvo',
                            'sem-criterio',
                        ],
                    ],
                ],
            ]),
        );
    }

    public function test_it_reports_missing_selection_for_criteria_that_need_configuration(): void
    {
        $selector = app(InscricaoConfirmationSelector::class);

        $this->assertSame('missing_criterios_confirmacao', $selector->missingSelection([])['code']);
        $this->assertSame('missing_publicos_confirmacao', $selector->missingSelection([
            'inscricao' => ['regras' => ['criterios' => ['publico-alvo']]],
        ])['code']);
        $this->assertSame('missing_setores_confirmacao', $selector->missingSelection([
            'inscricao' => ['regras' => ['criterios' => ['setor']]],
        ])['code']);
        $this->assertNull($selector->missingSelection([
            'inscricao' => ['regras' => ['criterios' => ['sem-criterio']]],
        ]));
    }

    public function test_it_returns_course_and_activity_ids_without_duplicate_ids(): void
    {
        $curso = new Cursos();
        $curso->id = 10;
        $atividadePrincipal = new CursoAtividades();
        $atividadePrincipal->id = 10;
        $atividadeSecundaria = new CursoAtividades();
        $atividadeSecundaria->id = 11;
        $curso->setRelation('atividades', new Collection([$atividadePrincipal, $atividadeSecundaria]));

        $this->assertSame([10, 11], app(InscricaoConfirmationSelector::class)->courseIds($curso));
    }

    public function test_it_normalizes_the_exceed_vagas_flag(): void
    {
        $selector = app(InscricaoConfirmationSelector::class);

        $this->assertTrue($selector->mayExceedVagas(['inscricao' => ['regras' => ['exceder_vagas' => 'yes']]]));
        $this->assertTrue($selector->mayExceedVagas(['inscricao' => ['regras' => ['exceder_vagas' => 1]]]));
        $this->assertFalse($selector->mayExceedVagas(['inscricao' => ['regras' => ['exceder_vagas' => 'no']]]));
    }
}
