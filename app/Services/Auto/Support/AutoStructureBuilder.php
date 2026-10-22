<?php

namespace App\Services\Auto\Support;

class AutoStructureBuilder
{
    public static function generateDefaultStep(): array
    {
        return [
            'enabled' => false,
            'status' => 'pending',
            'executed_once' => false,
            'completed' => false,
            'awaiting_action' => false,
            'manual_breakpoint' => false,

            'last_execution_at' => null,
            'last_success_at' => null,
            'last_error_at' => null,
            'last_error_message' => null,
            'completed_at' => null,

            'meta' => [],
            'analysis' => self::makeDefaultStepAnalysis(),
        ];
    }


    public static function makeDefaultSteps(): array
    {
        return [
            'abrir_inscricao' => self::generateDefaultStep(),
            'encerrar_inscricao' => self::generateDefaultStep(),
            'confirmar_inscricao' => self::generateDefaultStep(),
            'processar_aprovacoes' => self::generateDefaultStep(),
            'gerar_certificados' => self::generateDefaultStep(),
            'gerar_certificados_instrutores' => self::generateDefaultStep(),
            'gerar_relatorios' => self::generateDefaultStep(),
        ];
    }

    public static function makeDefaultStepAnalysis(): array
    {
        return [
            'last_analyzed_at' => null,
            'eligible' => false,
            'blocked' => false,
            'has_error' => false,
            'reasons' => [],
        ];
    }

    public static function makePendingAction(
        string $code,
        string $message,
        ?string $step = null,
        bool $blocking = true,
        string $severity = 'warning',
    ): array {
        return [
            'code' => $code,
            'message' => $message,
            'blocking' => $blocking,
            'severity' => $severity,
            'step' => $step,
        ];
    }

    public static function makeDiagnostics(
        bool $hasBlockingPendingActions = false,
        bool $hasErrors = false,
        bool $allEnabledStepsCompleted = false,
        array $extra = [],
    ): array {
        return array_merge([
            'has_blocking_pending_actions' => $hasBlockingPendingActions,
            'has_errors' => $hasErrors,
            'all_enabled_steps_completed' => $allEnabledStepsCompleted,
        ], $extra);
    }

    public static function makeAnalysisResult(
        array $steps = [],
        array $pendingActions = [],
        array $dispatchableSteps = [],
        array $diagnostics = [],
        $lastAnalyzedAt = null,
    ): array {
        return [
            'steps' => $steps,
            'pending_actions' => $pendingActions,
            'dispatchable_steps' => $dispatchableSteps,
            'diagnostics' => $diagnostics,
            'last_analyzed_at' => $lastAnalyzedAt ?? now(),
        ];
    }

    public static function makeAutoStatus(
        string $status,
        ?string $reason = null,
        array $pendingActions = [],
        $lastAnalyzedAt = null,
        array $extra = [],
    ): array {
        return array_merge([
            'status' => $status,
            'reason' => $reason,
            'pending_actions' => $pendingActions,
            'last_analyzed_at' => $lastAnalyzedAt ?? now(),
        ], $extra);
    }
}
