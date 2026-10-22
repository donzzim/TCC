<?php

namespace Tests\Unit;

use App\Services\Auto\Support\AutoStructureBuilder;
use Carbon\Carbon;
use Tests\TestCase;

class AutoStructureBuilderTest extends TestCase
{
    public function test_default_step_has_a_stable_contract(): void
    {
        $step = AutoStructureBuilder::generateDefaultStep();

        $this->assertSame('pending', $step['status']);
        $this->assertFalse($step['enabled']);
        $this->assertFalse($step['executed_once']);
        $this->assertFalse($step['completed']);
        $this->assertSame([], $step['meta']);
        $this->assertSame([
            'last_analyzed_at' => null,
            'eligible' => false,
            'blocked' => false,
            'has_error' => false,
            'reasons' => [],
        ], $step['analysis']);
    }

    public function test_default_steps_register_every_automation_stage_without_shared_nested_state(): void
    {
        $steps = AutoStructureBuilder::makeDefaultSteps();

        $this->assertSame([
            'abrir_inscricao',
            'encerrar_inscricao',
            'confirmar_inscricao',
            'processar_aprovacoes',
            'gerar_certificados',
            'gerar_certificados_instrutores',
            'gerar_relatorios',
        ], array_keys($steps));

        $steps['abrir_inscricao']['analysis']['reasons'][] = 'mutated';

        $this->assertSame([], $steps['encerrar_inscricao']['analysis']['reasons']);
    }

    public function test_pending_action_diagnostics_and_status_keep_their_domain_contract(): void
    {
        $action = AutoStructureBuilder::makePendingAction(
            code: 'missing_field',
            message: 'Campo obrigatório',
            step: 'abrir_inscricao',
            blocking: true,
            severity: 'error',
        );

        $diagnostics = AutoStructureBuilder::makeDiagnostics(
            hasBlockingPendingActions: true,
            hasErrors: true,
            allEnabledStepsCompleted: false,
            extra: ['source' => 'test'],
        );

        $status = AutoStructureBuilder::makeAutoStatus(
            status: 'awaiting_action',
            reason: 'Há pendência',
            pendingActions: [$action],
            lastAnalyzedAt: Carbon::parse('2026-10-02 10:00:00'),
        );

        $this->assertSame('missing_field', $action['code']);
        $this->assertTrue($action['blocking']);
        $this->assertSame('error', $action['severity']);
        $this->assertSame('test', $diagnostics['source']);
        $this->assertTrue($diagnostics['has_errors']);
        $this->assertSame('awaiting_action', $status['status']);
        $this->assertCount(1, $status['pending_actions']);
        $this->assertSame('2026-10-02 10:00:00', $status['last_analyzed_at']->format('Y-m-d H:i:s'));
    }
}
