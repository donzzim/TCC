<?php

namespace App\Services\Auto\Strategy;

use App\Enums\Auto\AutoSeverity;
use App\Enums\Auto\AutoStepStatus;
use App\Models\Escola\CursoAlunos;
use App\Models\Escola\Cursos;
use App\Services\Auto\Support\AutoStructureBuilder;
use Carbon\Carbon;

class ProcessarAprovacoesStepStrategy implements StepStrategyInterface
{
    public static string $step_key = 'processar_aprovacoes';

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
        $step['enabled'] = $this->booleanValue(data_get($auto_config, 'aprovacao.aprovacao_automatica'));
    }

    public function analyzeConditions(
        Cursos $curso,
        array &$step,
        array $auto_config,
        array &$pending_actions
    ): void {
        if (! $step['enabled']) {
            $step['analysis']['reasons'][] = 'Etapa desabilitada no autoconfig.';
            return;
        }

        if (! $curso->termino) {
            $this->blockWithPendingAction(
                step: $step,
                pending_actions: $pending_actions,
                code: 'missing_termino_curso',
                message: 'Defina a data de termino do curso para permitir o processamento automatico das aprovacoes.',
                reason: 'Data de termino do curso nao configurada.'
            );

            return;
        }

        $processarEmDataEspecifica = $this->booleanValue(data_get(
            $auto_config,
            'aprovacao.processar_em_data_especifica'
        ));

        if ($processarEmDataEspecifica) {
            $dataProcessamento = data_get($auto_config, 'aprovacao.data_processamento');

            if (! $dataProcessamento) {
                $this->blockWithPendingAction(
                    step: $step,
                    pending_actions: $pending_actions,
                    code: 'missing_data_processamento_aprovacoes',
                    message: 'Defina a data de processamento automatico das aprovacoes.',
                    reason: 'Data especifica de processamento das aprovacoes nao configurada.'
                );

                return;
            }

            $dataProcessamento = Carbon::parse($dataProcessamento);

            if ($dataProcessamento->lt($curso->termino)) {
                $this->blockWithPendingAction(
                    step: $step,
                    pending_actions: $pending_actions,
                    code: 'invalid_data_processamento_aprovacoes',
                    message: 'A data de processamento das aprovacoes deve ser igual ou posterior ao termino do curso.',
                    reason: 'Data especifica de processamento anterior ao termino do curso.'
                );

                return;
            }

            if (now()->gte($dataProcessamento)) {
                $this->markEligibleOrComplete(
                    curso: $curso,
                    step: $step,
                    reason: 'Data especifica de processamento das aprovacoes alcancada.',
                );
                return;
            }

            $step['analysis']['reasons'][] = 'Data especifica de processamento das aprovacoes ainda nao alcancada.';
            return;
        }

        if (now()->gte($curso->termino)) {
            $this->markEligibleOrComplete(
                curso: $curso,
                step: $step,
                reason: 'Curso encerrado. Aprovacoes automaticas podem ser processadas.',
            );
            return;
        }

        $step['analysis']['reasons'][] = 'O curso ainda nao chegou a data de termino.';
    }

    public function resolveStatus(
        Cursos $curso,
        array &$step,
        array $auto_config,
        array &$pending_actions
    ): void {
        if ($step['completed']) {
            $step['status'] = AutoStepStatus::COMPLETED->value;
            return;
        }

        if (($step['analysis']['has_error'] ?? false) === true) {
            $step['status'] = AutoStepStatus::ERROR->value;
            return;
        }

        if ($step['manual_breakpoint'] || $step['awaiting_action']) {
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

    private function markEligibleOrComplete(Cursos $curso, array &$step, string $reason): void
    {
        $pendingApprovalCount = $this->pendingApprovalCount($curso);

        if ($pendingApprovalCount <= 0) {
            $step['completed'] = true;
            $step['analysis']['reasons'][] = 'Nao ha alunos inscritos pendentes de aprovacao.';

            return;
        }

        if ($step['completed'] ?? false) {
            $step['completed'] = false;
            $step['completed_at'] = null;
            $step['analysis']['reasons'][] = 'Ha alunos inscritos pendentes; a etapa sera reaberta para novo processamento.';
        }

        $step['analysis']['eligible'] = true;
        $step['analysis']['reasons'][] = $reason;
    }

    private function pendingApprovalCount(Cursos $curso): int
    {
        $statusId = config('settings.inscricao.inscrito');

        if (! filled($statusId)) {
            return 0;
        }

        $count = $curso->alunos()
            ->where('situacaoinscricao_id', $statusId)
            ->where('aprovado', false)
            ->count();

        if (! $curso->temAtividades()) {
            return $count;
        }

        $activityIds = $curso->relationLoaded('atividades')
            ? $curso->atividades->pluck('id')
            : $curso->atividades()->pluck('id');

        $activityPending = CursoAlunos::query()
            ->whereIn('curso_id', $activityIds)
            ->where('situacaoinscricao_id', $statusId)
            ->where('aprovado', false)
            ->count();

        return $count + $activityPending;
    }

    private function blockWithPendingAction(
        array &$step,
        array &$pending_actions,
        string $code,
        string $message,
        string $reason
    ): void {
        $step['analysis']['blocked'] = true;
        $step['awaiting_action'] = true;
        $step['analysis']['reasons'][] = $reason;

        $pending_actions[] = AutoStructureBuilder::makePendingAction(
            code: $code,
            message: $message,
            step: self::$step_key,
            blocking: true,
            severity: AutoSeverity::WARNING->value
        );
    }

    private function booleanValue(mixed $value, bool $default = false): bool
    {
        if ($value === null) {
            return $default;
        }

        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value === 1;
        }

        if (is_string($value)) {
            return in_array(strtolower($value), ['1', 'true', 'on', 'yes'], true);
        }

        return (bool) $value;
    }
}
