<?php

namespace App\Services\Auto;

use App\Enums\Auto\AutoSeverity;
use App\Enums\Auto\AutoStepStatus;
use App\Models\Escola\Cursos;
use App\Services\Auto\Support\AutoStructureBuilder;
use App\Services\Auto\Support\StepStrategyFactory;
use Illuminate\Support\Facades\Log;


class CursoAnalyzerService
{
    public StepStrategyFactory $factory;

    public function __construct(StepStrategyFactory $factory = null)
    {
        if ($factory) {
            $this->factory = $factory;
        } else {
            $this->factory = app(StepStrategyFactory::class);
        }
    }


    public function generateDefaultStep(): array
    {
        return AutoStructureBuilder::generateDefaultStep();
    }


    public function generateDefaultSteps(): array
    {
        return AutoStructureBuilder::makeDefaultSteps();
    }


    public function resetAnalysisStep($step)
    {
        $step['analysis'] = AutoStructureBuilder::makeDefaultStepAnalysis();

        return $step;
    }

    public function normalizeSteps(array $auto_steps): array
    {
        $defaultSteps = $this->generateDefaultSteps();

        foreach ($auto_steps as $stepKey => $step) {
            if (! is_array($step)) {
                continue;
            }

            $defaultSteps[$stepKey] = array_replace_recursive(
                $this->generateDefaultStep(),
                $defaultSteps[$stepKey] ?? [],
                $step,
            );
        }

        return $defaultSteps;
    }

    public function reconcileSteps($curso, $auto_steps, $auto_config, &$pending_actions): array
    {
        foreach ($auto_steps as $step_key => $step) {
            try {
                $strategy = $this->factory->make($step_key);
            } catch (\Exception $e) {
                Log::warning("Nenhuma estratégia encontrada para o passo {$step_key}, pulando análise deste passo.", ['curso_id' => $curso->id]);
                continue;
            }

            try {
                $result = $strategy->reconcile($curso, $step, $auto_config, $pending_actions);
                $updatedStep = $result['step'] ?? $result;

                if (isset($result['step']) && isset($result['pending_actions'])) {
                    $pending_actions = array_merge($pending_actions, $result['pending_actions']);
                }

                $auto_steps[$step_key] = array_replace_recursive(
                    $this->generateDefaultStep(),
                    $step,
                    $updatedStep,
                );

                if ($this->hasPersistedExecutionError($auto_steps[$step_key])) {
                    $auto_steps[$step_key]['status'] = AutoStepStatus::ERROR->value;
                    $auto_steps[$step_key]['awaiting_action'] = true;
                    $auto_steps[$step_key]['analysis']['blocked'] = true;
                    $auto_steps[$step_key]['analysis']['eligible'] = false;
                    $auto_steps[$step_key]['analysis']['has_error'] = true;

                    $pending_actions[] = AutoStructureBuilder::makePendingAction(
                        code: "execution_error_{$step_key}",
                        message: "Ocorreu um erro durante a execucao do passo {$step_key}: " . ($auto_steps[$step_key]['last_error_message'] ?? 'erro nao informado'),
                        step: $step_key,
                        blocking: true,
                        severity: AutoSeverity::ERROR->value,
                    );
                }
            } catch (\Exception $e) {
                Log::error("Erro ao analisar passo {$step_key}: " . $e->getMessage(), ['curso_id' => $curso->id]);

                $auto_steps[$step_key]['status'] = AutoStepStatus::ERROR->value;
                $auto_steps[$step_key]['awaiting_action'] = true;
                $auto_steps[$step_key]['last_error_at'] = now();
                $auto_steps[$step_key]['last_error_message'] = $e->getMessage();
                $auto_steps[$step_key]['analysis']['blocked'] = true;
                $auto_steps[$step_key]['analysis']['has_error'] = true;
                $auto_steps[$step_key]['analysis']['last_analyzed_at'] = now();
                $auto_steps[$step_key]['analysis']['reasons'][] = "Erro na análise: " . $e->getMessage();

                $pending_actions[] = AutoStructureBuilder::makePendingAction(
                    code: "error_{$step_key}",
                    message: "Ocorreu um erro durante a análise do passo {$step_key}: " . $e->getMessage(),
                    step: $step_key,
                    blocking: true,
                    severity: AutoSeverity::ERROR->value,
                );
                continue;
            }
        }

        return $auto_steps;
    }




    public function determineDispatchableSteps($auto_steps): array
    {
        $dispatchable = [];

        foreach ($auto_steps as $step_key => $step) {
            if (
                ($step['enabled'] ?? false)
                && ($step['status'] ?? null) === AutoStepStatus::READY->value
                && ! ($step['completed'] ?? false)
                && ! ($step['awaiting_action'] ?? false)
                && ! ($step['manual_breakpoint'] ?? false)
                && ! data_get($step, 'analysis.blocked', false)
                && ! $this->hasPersistedExecutionError($step)
                && data_get($step, 'analysis.eligible', false)
            ) {
                $dispatchable[] = $step_key;
            }
        }

        return $dispatchable;
    }

    public function applyConfiguredBreakpoints(
        array $auto_steps,
        array $auto_config,
        array &$pending_actions,
        array $released_breakpoints = [],
    ): array
    {
        $breakpoints = data_get($auto_config, 'fluxo.breakpoints', []);

        if (! is_array($breakpoints)) {
            return $auto_steps;
        }

        foreach ($breakpoints as $index => $breakpoint) {
            if (! is_array($breakpoint)) {
                continue;
            }

            if (! $this->booleanValue($breakpoint['enabled'] ?? null, true)) {
                continue;
            }

            $blocking = $this->booleanValue($breakpoint['blocking'] ?? null, true);
            $notify = $this->booleanValue($breakpoint['notify'] ?? null, true);

            if (! $blocking && ! $notify) {
                continue;
            }

            $stepKey = $breakpoint['step'] ?? null;

            if (! $stepKey || ! isset($auto_steps[$stepKey])) {
                continue;
            }

            if (! ($auto_steps[$stepKey]['enabled'] ?? false)) {
                continue;
            }

            $position = $breakpoint['position'] ?? 'before';

            $breakpointCode = $this->breakpointCode($stepKey, $position, $index);
            $breakpointFingerprint = $this->breakpointFingerprint($breakpoint);

            if ($this->isBreakpointReleased($released_breakpoints, $breakpointCode, $breakpointFingerprint)) {
                continue;
            }

            if ($position === 'before' && (
                ($auto_steps[$stepKey]['completed'] ?? false)
                || ! data_get($auto_steps[$stepKey], 'analysis.eligible', false)
            )) {
                continue;
            }

            if ($position === 'after' && ! ($auto_steps[$stepKey]['completed'] ?? false)) {
                continue;
            }

            $label = filled($breakpoint['label'] ?? null)
                ? (string) $breakpoint['label']
                : "Breakpoint em {$stepKey}";

            if ($blocking) {
                $auto_steps[$stepKey]['manual_breakpoint'] = true;
                $auto_steps[$stepKey]['awaiting_action'] = true;
                $auto_steps[$stepKey]['status'] = AutoStepStatus::AWAITING_ACTION->value;
                $auto_steps[$stepKey]['analysis']['blocked'] = true;
                $auto_steps[$stepKey]['analysis']['eligible'] = false;
                $auto_steps[$stepKey]['analysis']['reasons'][] = "Pausa manual configurada: {$label}.";
            } else {
                $auto_steps[$stepKey]['analysis']['reasons'][] = "Breakpoint informativo configurado: {$label}.";
            }

            $pendingAction = AutoStructureBuilder::makePendingAction(
                code: $breakpointCode,
                message: $label,
                step: $stepKey,
                blocking: $blocking,
                severity: $breakpoint['severity'] ?? AutoSeverity::WARNING->value,
            );

            $pendingAction['type'] = 'breakpoint';
            $pendingAction['notify'] = $notify;
            $pendingAction['breakpoint'] = [
                'code' => $breakpointCode,
                'fingerprint' => $breakpointFingerprint,
                'step' => $stepKey,
                'position' => $position,
                'label' => $label,
                'description' => $breakpoint['description'] ?? null,
                'blocking' => $blocking,
                'severity' => $breakpoint['severity'] ?? AutoSeverity::WARNING->value,
            ];

            $pending_actions[] = $pendingAction;
        }

        return $auto_steps;
    }

    public function mount_diagnostics($auto_steps, $pending_actions): array
    {
        $enabledSteps = collect($auto_steps)->filter(fn($step) => $step['enabled'] ?? false);

        return [
            'has_blocking_pending_actions' => collect($pending_actions)->contains(fn($action) => $action['blocking'] ?? false),
            'has_errors' => collect($auto_steps)->contains(fn($step) => ($step['status'] ?? null) === AutoStepStatus::ERROR->value || data_get($step, 'analysis.has_error', false)),
            'all_enabled_steps_completed' => $enabledSteps->isNotEmpty()
                && $enabledSteps->every(fn($step) => $step['completed'] ?? false)
        ];
    }

    public function analyze(Cursos $curso): array
    {

        try {
            Log::info('Iniciando análise do curso', ['curso_id' => $curso->id]);

            $auto_steps = is_array($curso->auto_steps) ? $curso->auto_steps : [];
            $auto_config = is_array($curso->auto_config) ? $curso->auto_config : [];

            if (!$auto_steps || $auto_steps == []) {
                Log::info('Nenhum passo de automação encontrado, gerando passos padrão');
            }

            $auto_steps = $this->normalizeSteps($auto_steps);

            if (!$auto_config) {
                Log::info('Nenhuma configração de automação encontrada durante analise do curso' . $curso->titulo . ' (' . $curso->id . ')');

                $pending_actions = [
                    AutoStructureBuilder::makePendingAction(
                        code: 'missing_auto_config',
                        message: 'Configuração de automação não encontrada. Por favor, configure as etapas de automação para este curso.',
                        blocking: true,
                        severity: AutoSeverity::ERROR->value,
                    ),
                ];

                return AutoStructureBuilder::makeAnalysisResult(
                    steps: $auto_steps,
                    pendingActions: $pending_actions,
                    diagnostics: AutoStructureBuilder::makeDiagnostics(
                        hasBlockingPendingActions: true,
                    ),
                );
            }

            $pending_actions = [];

            $auto_steps = $this->reconcileSteps($curso, $auto_steps, $auto_config, $pending_actions);
            $auto_steps = $this->applyConfiguredBreakpoints(
                auto_steps: $auto_steps,
                auto_config: $auto_config,
                pending_actions: $pending_actions,
                released_breakpoints: $this->normalizeReleasedBreakpoints(data_get($curso->auto_status, 'released_breakpoints', [])),
            );

            $dispachable_steps = $this->determineDispatchableSteps($auto_steps);

            $diagnostics = $this->mount_diagnostics($auto_steps, $pending_actions);


            return AutoStructureBuilder::makeAnalysisResult(
                steps: $auto_steps,
                pendingActions: $pending_actions,
                dispatchableSteps: $dispachable_steps,
                diagnostics: $diagnostics,
            );
        } catch (\Exception $e) {
            Log::error('Erro ao analisar curso', ['curso_id' => $curso->id, 'error' => $e->getMessage()]);

            $pending_actions = [
                AutoStructureBuilder::makePendingAction(
                    code: 'analysis_error',
                    message: 'Ocorreu um erro durante a análise do curso: ' . $e->getMessage(),
                    blocking: true,
                    severity: AutoSeverity::ERROR->value,
                ),
            ];

            return AutoStructureBuilder::makeAnalysisResult(
                pendingActions: $pending_actions,
                diagnostics: AutoStructureBuilder::makeDiagnostics(
                    hasBlockingPendingActions: true,
                    hasErrors: true,
                    extra: ['missing_auto_config' => false],
                ),
            );
        }
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

    private function breakpointCode(string $stepKey, string $position, int|string $index): string
    {
        return "manual_breakpoint_{$stepKey}_{$position}_{$index}";
    }

    private function breakpointFingerprint(array $breakpoint): string
    {
        return sha1(json_encode([
            'step' => $breakpoint['step'] ?? null,
            'position' => $breakpoint['position'] ?? 'before',
            'label' => $breakpoint['label'] ?? null,
            'description' => $breakpoint['description'] ?? null,
            'severity' => $breakpoint['severity'] ?? 'warning',
            'blocking' => $this->booleanValue($breakpoint['blocking'] ?? null, true),
            'notify' => $this->booleanValue($breakpoint['notify'] ?? null, true),
        ], JSON_UNESCAPED_UNICODE));
    }

    private function isBreakpointReleased(array $releasedBreakpoints, string $code, string $fingerprint): bool
    {
        $release = $releasedBreakpoints[$code] ?? null;

        if (is_array($release)) {
            return ($release['fingerprint'] ?? null) === $fingerprint;
        }

        return is_string($release) && $release === $fingerprint;
    }

    private function normalizeReleasedBreakpoints(mixed $releasedBreakpoints): array
    {
        return is_array($releasedBreakpoints) ? $releasedBreakpoints : [];
    }

    private function hasPersistedExecutionError(array $step): bool
    {
        return filled($step['last_error_at'] ?? null)
            || filled($step['last_error_message'] ?? null)
            || data_get($step, 'analysis.has_error', false);
    }
}
