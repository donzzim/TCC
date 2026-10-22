<?php

namespace App\Services\Auto;

use App\Enums\Auto\AutoSeverity;
use App\Enums\Auto\AutoStepStatus;
use App\Models\Escola\Cursos;
use App\Services\Auto\Support\AutoStructureBuilder;
use App\Services\Auto\Support\StepExecutionFactory;
use Illuminate\Support\Facades\Log;
use Throwable;

class AutoExecutionService
{
    public function __construct(
        protected StepExecutionFactory $factory,
    ) {}

    public function execute(Cursos $curso, string $stepKey, array $context = []): array
    {
        $autoSteps = $this->normalizeSteps($curso->auto_steps);
        $autoConfig = is_array($curso->auto_config) ? $curso->auto_config : [];
        $step = $autoSteps[$stepKey] ?? null;

        if (! is_array($step)) {
            return $this->persistExecutionError(
                curso: $curso,
                autoSteps: $autoSteps,
                stepKey: $stepKey,
                throwable: new \InvalidArgumentException("Step {$stepKey} nao encontrado em auto_steps."),
            );
        }

        if (! $this->isExecutable($step)) {
            Log::info('Etapa automatica ignorada porque nao esta mais executavel.', [
                'curso_id' => $curso->id,
                'step' => $stepKey,
                'status' => $step['status'] ?? null,
            ]);

            return [
                'executed' => false,
                'step_key' => $stepKey,
                'step' => $step,
                'pending_actions' => [],
                'should_reanalyze' => false,
                'reason' => 'Etapa nao esta mais executavel.',
            ];
        }

        try {
            $step = $this->markRunning($curso, $autoSteps, $stepKey, $step);
            $executor = $this->factory->make($stepKey);

            $result = $executor->execute(
                curso: $curso->fresh(),
                step: $step,
                autoConfig: $autoConfig,
                context: array_merge([
                    'auto_status' => is_array($curso->auto_status) ? $curso->auto_status : [],
                ], $context),
            );

            return $this->persistExecutionSuccess(
                curso: $curso,
                stepKey: $stepKey,
                currentStep: $step,
                result: $result,
            );
        } catch (Throwable $throwable) {
            $freshCurso = $curso->fresh() ?? $curso;

            return $this->persistExecutionError(
                curso: $freshCurso,
                autoSteps: $this->normalizeSteps($freshCurso->auto_steps),
                stepKey: $stepKey,
                throwable: $throwable,
            );
        }
    }

    private function markRunning(Cursos $curso, array $autoSteps, string $stepKey, array $step): array
    {
        $step = array_replace_recursive($step, [
            'status' => AutoStepStatus::RUNNING->value,
            'last_execution_at' => now(),
            'last_error_at' => null,
            'last_error_message' => null,
        ]);

        $autoSteps[$stepKey] = $step;
        $this->persistSteps($curso, $autoSteps);

        return $step;
    }

    private function persistExecutionSuccess(
        Cursos $curso,
        string $stepKey,
        array $currentStep,
        array $result,
    ): array {
        $autoSteps = $this->normalizeSteps($curso->fresh()?->auto_steps);
        $pendingActions = $this->normalizePendingActions($result['pending_actions'] ?? []);
        $hasBlockingPendingAction = collect($pendingActions)->contains(
            fn(array $action): bool => $action['blocking'] ?? false
        );

        $resultStep = is_array($result['step'] ?? null) ? $result['step'] : [];
        $meta = is_array($result['meta'] ?? null) ? $result['meta'] : [];
        $currentMeta = is_array($currentStep['meta'] ?? null) ? $currentStep['meta'] : [];
        $completed = $resultStep['completed'] ?? ! $hasBlockingPendingAction;
        $awaitingAction = ($resultStep['awaiting_action'] ?? false) || $hasBlockingPendingAction;

        $updatedStep = array_replace_recursive(
            AutoStructureBuilder::generateDefaultStep(),
            $currentStep,
            $resultStep,
            [
                'executed_once' => true,
                'completed' => $completed,
                'awaiting_action' => $awaitingAction,
                'last_execution_at' => now(),
                'last_success_at' => $hasBlockingPendingAction ? null : now(),
                'last_error_at' => null,
                'last_error_message' => null,
                'meta' => array_replace_recursive($currentMeta, $meta),
            ],
        );

        if ($completed) {
            $updatedStep['status'] = $resultStep['status'] ?? AutoStepStatus::COMPLETED->value;
            $updatedStep['completed_at'] = $resultStep['completed_at'] ?? now();
        } elseif ($awaitingAction) {
            $updatedStep['status'] = $resultStep['status'] ?? AutoStepStatus::AWAITING_ACTION->value;
            $updatedStep['analysis']['blocked'] = true;
            $updatedStep['analysis']['eligible'] = false;
        } else {
            $updatedStep['status'] = $resultStep['status'] ?? AutoStepStatus::PENDING->value;
        }

        $autoSteps[$stepKey] = $updatedStep;
        $this->persistSteps($curso, $autoSteps);

        Log::info('Etapa automatica executada.', [
            'curso_id' => $curso->id,
            'step' => $stepKey,
            'status' => $updatedStep['status'] ?? null,
        ]);

        return [
            'executed' => true,
            'step_key' => $stepKey,
            'step' => $updatedStep,
            'pending_actions' => $pendingActions,
            'should_reanalyze' => $result['should_reanalyze'] ?? true,
        ];
    }

    private function persistExecutionError(
        Cursos $curso,
        array $autoSteps,
        string $stepKey,
        Throwable $throwable,
    ): array {
        $message = $throwable->getMessage();
        $step = array_replace_recursive(
            AutoStructureBuilder::generateDefaultStep(),
            $autoSteps[$stepKey] ?? [],
        );

        $step['status'] = AutoStepStatus::ERROR->value;
        $step['awaiting_action'] = true;
        $step['last_execution_at'] = now();
        $step['last_error_at'] = now();
        $step['last_error_message'] = $message;
        $step['analysis']['blocked'] = true;
        $step['analysis']['eligible'] = false;
        $step['analysis']['has_error'] = true;
        $step['analysis']['reasons'][] = "Erro na execucao: {$message}";

        $pendingActions = [
            AutoStructureBuilder::makePendingAction(
                code: "execution_error_{$stepKey}",
                message: "Ocorreu um erro durante a execucao do passo {$stepKey}: {$message}",
                step: $stepKey,
                blocking: true,
                severity: AutoSeverity::ERROR->value,
            ),
        ];

        $autoSteps[$stepKey] = $step;
        $this->persistSteps($curso, $autoSteps);

        Log::error('Erro ao executar etapa automatica.', [
            'curso_id' => $curso->id,
            'step' => $stepKey,
            'error' => $message,
            'file' => $throwable->getFile(),
            'line' => $throwable->getLine(),
        ]);

        return [
            'executed' => false,
            'step_key' => $stepKey,
            'step' => $step,
            'pending_actions' => $pendingActions,
            'should_reanalyze' => true,
            'error' => $message,
        ];
    }

    private function persistSteps(Cursos $curso, array $autoSteps): void
    {
        Cursos::withoutEvents(function () use ($curso, $autoSteps): void {
            $curso->forceFill([
                'auto_steps' => $autoSteps,
            ])->save();
        });
    }

    private function isExecutable(array $step): bool
    {
        return ($step['enabled'] ?? false)
            && ($step['status'] ?? null) === AutoStepStatus::READY->value
            && ! ($step['completed'] ?? false)
            && ! ($step['awaiting_action'] ?? false)
            && ! ($step['manual_breakpoint'] ?? false)
            && ! data_get($step, 'analysis.blocked', false)
            && ! filled($step['last_error_at'] ?? null)
            && ! filled($step['last_error_message'] ?? null);
    }

    private function normalizeSteps(mixed $steps): array
    {
        $defaultSteps = AutoStructureBuilder::makeDefaultSteps();

        if (! is_array($steps)) {
            return $defaultSteps;
        }

        foreach ($steps as $stepKey => $step) {
            if (! is_array($step)) {
                continue;
            }

            $defaultSteps[$stepKey] = array_replace_recursive(
                AutoStructureBuilder::generateDefaultStep(),
                $defaultSteps[$stepKey] ?? [],
                $step,
            );
        }

        return $defaultSteps;
    }

    private function normalizePendingActions(mixed $pendingActions): array
    {
        if (! is_array($pendingActions)) {
            return [];
        }

        return collect($pendingActions)
            ->filter(fn(mixed $action): bool => is_array($action))
            ->values()
            ->all();
    }
}
