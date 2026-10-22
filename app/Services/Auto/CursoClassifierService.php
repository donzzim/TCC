<?php

namespace App\Services\Auto;

use App\Enums\Auto\AutoStatus;
use App\Enums\Auto\AutoStepStatus;
use App\Models\Escola\Cursos;
use App\Services\Auto\Support\AutoStructureBuilder;

class CursoClassifierService
{
    public function classify(Cursos $curso, array $analysis): array
    {
        $steps = $this->normalizeSteps($analysis['steps'] ?? []);
        $pendingActions = array_values($analysis['pending_actions'] ?? []);
        $dispatchableSteps = $this->normalizeDispatchableSteps($analysis['dispatchable_steps'] ?? []);
        $diagnostics = $analysis['diagnostics'] ?? [];
        $lastAnalyzedAt = $analysis['last_analyzed_at'] ?? now();

        if ($manualStatus = $this->preserveManualStatus($curso, $pendingActions, $lastAnalyzedAt, $dispatchableSteps)) {
            return $manualStatus;
        }

        if (
            ($diagnostics['has_errors'] ?? false)
            || $this->hasErrorStep($steps)
        ) {
            return $this->makeStatus(
                status: AutoStatus::ERRO,
                reason: 'Uma ou mais etapas da automação apresentaram erro.',
                pendingActions: $pendingActions,
                lastAnalyzedAt: $lastAnalyzedAt,
                dispatchableSteps: $dispatchableSteps,
            );
        }

        if (
            ($diagnostics['has_blocking_pending_actions'] ?? false)
            || $this->hasAwaitingAction($steps)
        ) {
            return $this->makeStatus(
                status: AutoStatus::AGUARDANDO_ACAO,
                reason: 'Há pendências bloqueantes ou pausas manuais aguardando ação.',
                pendingActions: $pendingActions,
                lastAnalyzedAt: $lastAnalyzedAt,
                dispatchableSteps: $dispatchableSteps,
            );
        }

        if (
            ($diagnostics['all_enabled_steps_completed'] ?? false)
            || $this->allEnabledStepsCompleted($steps)
        ) {
            return $this->makeStatus(
                status: AutoStatus::CONCLUIDO,
                reason: 'Todas as etapas habilitadas foram concluídas.',
                pendingActions: $pendingActions,
                lastAnalyzedAt: $lastAnalyzedAt,
                dispatchableSteps: $dispatchableSteps,
            );
        }

        if (! empty($dispatchableSteps)) {
            return $this->makeStatus(
                status: AutoStatus::ATIVO,
                reason: 'Há etapas prontas para execução.',
                pendingActions: $pendingActions,
                lastAnalyzedAt: $lastAnalyzedAt,
                dispatchableSteps: $dispatchableSteps,
            );
        }

        if ($this->hasEnabledSteps($steps)) {
            return $this->makeStatus(
                status: AutoStatus::ATIVO,
                reason: 'Há etapas habilitadas aguardando as condições de execução.',
                pendingActions: $pendingActions,
                lastAnalyzedAt: $lastAnalyzedAt,
                dispatchableSteps: $dispatchableSteps,
            );
        }

        return $this->makeStatus(
            status: AutoStatus::AGUARDANDO_ACAO,
            reason: 'Nenhuma etapa de automação está habilitada na configuração atual.',
            pendingActions: $pendingActions,
            lastAnalyzedAt: $lastAnalyzedAt,
            dispatchableSteps: $dispatchableSteps,
        );
    }

    private function preserveManualStatus(
        Cursos $curso,
        array $pendingActions,
        mixed $lastAnalyzedAt,
        array $dispatchableSteps,
    ): ?array {
        $currentStatus = data_get($curso->auto_status, 'status');

        if ($currentStatus === AutoStatus::CANCELADO->value) {
            return $this->makeStatus(
                status: AutoStatus::CANCELADO,
                reason: data_get($curso->auto_status, 'reason') ?: 'Automação cancelada manualmente.',
                pendingActions: $pendingActions,
                lastAnalyzedAt: $lastAnalyzedAt,
                dispatchableSteps: $dispatchableSteps,
                extra: ['preserved_manual_status' => true],
            );
        }

        if ($currentStatus === AutoStatus::PAUSADO->value) {
            return $this->makeStatus(
                status: AutoStatus::PAUSADO,
                reason: data_get($curso->auto_status, 'reason') ?: 'Automação pausada manualmente.',
                pendingActions: $pendingActions,
                lastAnalyzedAt: $lastAnalyzedAt,
                dispatchableSteps: $dispatchableSteps,
                extra: ['preserved_manual_status' => true],
            );
        }

        return null;
    }

    private function hasErrorStep(array $steps): bool
    {
        return collect($steps)->contains(
            fn(array $step): bool => ($step['status'] ?? null) === AutoStepStatus::ERROR->value
                || data_get($step, 'analysis.has_error', false)
                || filled($step['last_error_at'] ?? null)
                || filled($step['last_error_message'] ?? null)
        );
    }

    private function hasAwaitingAction(array $steps): bool
    {
        return collect($steps)->contains(
            fn(array $step): bool => ($step['status'] ?? null) === AutoStepStatus::AWAITING_ACTION->value
                || ($step['awaiting_action'] ?? false)
                || ($step['manual_breakpoint'] ?? false)
        );
    }

    private function hasEnabledSteps(array $steps): bool
    {
        return collect($steps)->contains(fn(array $step): bool => $step['enabled'] ?? false);
    }

    private function allEnabledStepsCompleted(array $steps): bool
    {
        $enabledSteps = collect($steps)->filter(fn(array $step): bool => $step['enabled'] ?? false);

        return $enabledSteps->isNotEmpty()
            && $enabledSteps->every(fn(array $step): bool => $step['completed'] ?? false);
    }

    private function normalizeSteps(mixed $steps): array
    {
        if (! is_array($steps)) {
            return [];
        }

        return collect($steps)
            ->filter(fn(mixed $step): bool => is_array($step))
            ->all();
    }

    private function normalizeDispatchableSteps(mixed $dispatchableSteps): array
    {
        if (! is_array($dispatchableSteps)) {
            return [];
        }

        return collect($dispatchableSteps)
            ->filter(fn(mixed $step): bool => is_string($step) && filled($step))
            ->values()
            ->all();
    }

    private function makeStatus(
        AutoStatus $status,
        string $reason,
        array $pendingActions,
        mixed $lastAnalyzedAt,
        array $dispatchableSteps = [],
        array $extra = [],
    ): array {
        return AutoStructureBuilder::makeAutoStatus(
            status: $status->value,
            reason: $reason,
            pendingActions: $pendingActions,
            lastAnalyzedAt: $lastAnalyzedAt,
            extra: array_merge([
                'confirmed_dispatchable_steps' => $status === AutoStatus::ATIVO
                    ? $dispatchableSteps
                    : [],
            ], $extra),
        );
    }
}
