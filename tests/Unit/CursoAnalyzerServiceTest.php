<?php

namespace Tests\Unit;

use App\Enums\Auto\AutoStepStatus;
use App\Models\Escola\Cursos;
use App\Services\Auto\CursoAnalyzerService;
use App\Services\Auto\Support\AutoStructureBuilder;
use App\Services\Auto\Support\StepStrategyFactory;
use Mockery;
use Tests\TestCase;

class CursoAnalyzerServiceTest extends TestCase
{
    public function test_it_normalizes_partial_steps_over_the_default_contract(): void
    {
        $service = $this->service();

        $steps = $service->normalizeSteps([
            'abrir_inscricao' => [
                'enabled' => true,
                'analysis' => ['eligible' => true],
            ],
            'step_desconhecido' => 'ignorado',
        ]);

        $this->assertCount(7, $steps);
        $this->assertTrue($steps['abrir_inscricao']['enabled']);
        $this->assertTrue($steps['abrir_inscricao']['analysis']['eligible']);
        $this->assertArrayNotHasKey('step_desconhecido', $steps);
        $this->assertSame('pending', $steps['encerrar_inscricao']['status']);
    }

    public function test_it_dispatches_only_ready_eligible_steps_without_errors_or_breakpoints(): void
    {
        $service = $this->service();
        $steps = AutoStructureBuilder::makeDefaultSteps();

        $steps['abrir_inscricao'] = array_replace_recursive($steps['abrir_inscricao'], [
            'enabled' => true,
            'status' => AutoStepStatus::READY->value,
            'analysis' => ['eligible' => true],
        ]);
        $steps['encerrar_inscricao'] = array_replace_recursive($steps['encerrar_inscricao'], [
            'enabled' => true,
            'status' => AutoStepStatus::READY->value,
            'analysis' => ['eligible' => true],
            'manual_breakpoint' => true,
        ]);
        $steps['confirmar_inscricao'] = array_replace_recursive($steps['confirmar_inscricao'], [
            'enabled' => true,
            'status' => AutoStepStatus::READY->value,
            'analysis' => ['eligible' => true],
            'last_error_message' => 'erro persistido',
        ]);

        $this->assertSame(['abrir_inscricao'], $service->determineDispatchableSteps($steps));
    }

    public function test_it_applies_a_blocking_breakpoint_only_when_the_step_is_eligible(): void
    {
        $service = $this->service();
        $steps = AutoStructureBuilder::makeDefaultSteps();
        $steps['abrir_inscricao']['enabled'] = true;
        $steps['abrir_inscricao']['analysis']['eligible'] = true;
        $pendingActions = [];

        $steps = $service->applyConfiguredBreakpoints($steps, [
            'fluxo' => [
                'breakpoints' => [
                    [
                        'step' => 'abrir_inscricao',
                        'position' => 'before',
                        'label' => 'Validar divulgação',
                        'blocking' => true,
                        'notify' => true,
                    ],
                    [
                        'step' => 'encerrar_inscricao',
                        'position' => 'before',
                        'label' => 'Não deve ser aplicado',
                        'blocking' => true,
                    ],
                ],
            ],
        ], $pendingActions);

        $this->assertSame(AutoStepStatus::AWAITING_ACTION->value, $steps['abrir_inscricao']['status']);
        $this->assertTrue($steps['abrir_inscricao']['manual_breakpoint']);
        $this->assertTrue($steps['abrir_inscricao']['analysis']['blocked']);
        $this->assertCount(1, $pendingActions);
        $this->assertSame('manual_breakpoint_abrir_inscricao_before_0', $pendingActions[0]['code']);
        $this->assertSame([], $steps['encerrar_inscricao']['analysis']['reasons']);
    }

    public function test_it_returns_a_blocking_diagnostic_when_automation_config_is_missing(): void
    {
        $service = $this->service();
        $curso = new Cursos(['titulo' => 'Curso sem configuração']);

        $result = $service->analyze($curso);

        $this->assertTrue($result['diagnostics']['has_blocking_pending_actions']);
        $this->assertSame('missing_auto_config', $result['pending_actions'][0]['code']);
        $this->assertCount(7, $result['steps']);
    }

    private function service(): CursoAnalyzerService
    {
        return new CursoAnalyzerService(Mockery::mock(StepStrategyFactory::class));
    }
}
