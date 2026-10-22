<?php

namespace App\Services\Auto\Strategy;

use App\Enums\Auto\AutoStepStatus;
use App\Models\Escola\Cursos;

class EncerrarInscricaoStepStrategy implements StepStrategyInterface
{
    public static string $step_key = 'encerrar_inscricao';

    public function reconcile(Cursos $curso, array $step, array $auto_config, array &$pending_actions): array
    {
        $this->resetAnalysis($step);
        $this->resolveEnabled($step, $auto_config);
        $this->analyzeConditions($curso, $step, $auto_config, $pending_actions);
        $this->resolveStatus($curso, $step, $auto_config, $pending_actions);
        return $step;
    }

    public function resetAnalysis(array &$step): void
    {
        $step['analysis'] = [
            'last_analyzed_at' => now(),
            'eligible' => false,

            'blocked' => false,
            'has_error' => false,
            'reasons' => [],
        ];

        $step['awaiting_action'] = false;
    }

    public function resolveEnabled(array &$step, array $auto_config): void
    {
        $step['enabled'] = data_get(
            $auto_config,
            'inscricao.encerramento_automatico',
            data_get($auto_config, 'inscricao.abertura_automatica', false),
        );
    }

    public function analyzeConditions(Cursos $curso, array &$step, array $auto_config, array &$pending_actions): void
    {
        if (! $step['enabled']) {
            $step['analysis']['reasons'][] = 'Etapa desabilitada no autoconfig.';
            return;
        }

        if (!$curso->termino_inscricao) {
            $step['analysis']['blocked'] = true;
            $step['awaiting_action'] = true;
            $step['analysis']['reasons'][] = 'Data de termino da inscrição não configurada.';

            $pending_actions[] = [
                'code' => 'missing_termino_inscricao',
                'message' => 'Defina a data de termino da inscrição para permitir o encerramento automático.',
                'blocking' => true,
                'severity' => 'warning',
                'step' => self::$step_key,
            ];

            return;
        }

        if (now()->gte($curso->termino_inscricao)) {
            $step['analysis']['eligible'] = true;
            $step['analysis']['reasons'][] = 'Data de termino da inscrição alcançada.';
            return;
        }

        $step['analysis']['reasons'][] = 'A data de termino da inscrição ainda não foi alcançada.';
    }

    public function resolveStatus(Cursos $curso, array &$step, array $auto_config, array &$pending_actions): void
    {

        if ($step['completed']) {
            $step['status'] = AutoStepStatus::COMPLETED->value;
            return;
        }

        if (!empty($step['last_error_at']) || !empty($step['last_error_message'])) {
            $step['status'] = AutoStepStatus::ERROR->value;
            return;
        }

        if ($step['manual_breakpoint']) {
            $step['status'] = AutoStepStatus::AWAITING_ACTION->value;
            return;
        }

        if ($step['awaiting_action']) {
            $step['status'] = AutoStepStatus::AWAITING_ACTION->value;
            return;
        }

        if ($step['analysis']['blocked']) {
            $step['status'] = AutoStepStatus::BLOCKED->value;
            return;
        }

        if ($step['enabled'] && $step['analysis']['eligible']) {
            $step['status'] = AutoStepStatus::READY->value;
            return;
        }

        $step['status'] = AutoStepStatus::PENDING->value;
    }
}
