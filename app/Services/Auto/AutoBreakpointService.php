<?php

namespace App\Services\Auto;

use App\Enums\Auto\AutoStatus;
use App\Enums\Auto\AutoStepStatus;
use App\Jobs\ProcessarCursoAutomacaoJob;
use App\Models\Escola\Cursos;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AutoBreakpointService
{
    public function release(Cursos $curso, string $breakpointCode): array
    {
        $autoStatus = is_array($curso->auto_status) ? $curso->auto_status : [];
        $autoSteps = is_array($curso->auto_steps) ? $curso->auto_steps : [];
        $pendingActions = collect(data_get($autoStatus, 'pending_actions', []))
            ->filter(fn (mixed $action): bool => is_array($action))
            ->values();

        $action = $pendingActions->first(
            fn (array $pendingAction): bool => ($pendingAction['code'] ?? null) === $breakpointCode
                && ($pendingAction['type'] ?? null) === 'breakpoint'
        );

        if (! $action) {
            $released = data_get($autoStatus, "released_breakpoints.{$breakpointCode}");

            if ($released) {
                return [
                    'released' => false,
                    'already_released' => true,
                    'message' => 'Este breakpoint ja foi liberado.',
                ];
            }

            return [
                'released' => false,
                'already_released' => false,
                'message' => 'Breakpoint nao encontrado ou ja nao esta aguardando acao.',
            ];
        }

        $stepKey = (string) ($action['step'] ?? data_get($action, 'breakpoint.step', ''));
        $breakpointData = is_array(data_get($action, 'breakpoint'))
            ? data_get($action, 'breakpoint')
            : [];
        $fingerprint = (string) data_get($breakpointData, 'fingerprint', '');

        if (blank($fingerprint)) {
            $fingerprint = $this->breakpointFingerprint([
                'step' => $breakpointData['step'] ?? $stepKey,
                'position' => $breakpointData['position'] ?? 'before',
                'label' => $breakpointData['label'] ?? $action['message'] ?? null,
                'description' => $breakpointData['description'] ?? null,
                'severity' => $breakpointData['severity'] ?? $action['severity'] ?? 'warning',
                'blocking' => $breakpointData['blocking'] ?? $action['blocking'] ?? true,
                'notify' => $breakpointData['notify'] ?? $action['notify'] ?? false,
            ]);
        }
        $releasedBreakpoints = is_array(data_get($autoStatus, 'released_breakpoints'))
            ? data_get($autoStatus, 'released_breakpoints')
            : [];

        $releasedBreakpoints[$breakpointCode] = [
            'fingerprint' => $fingerprint,
            'released_at' => now()->toIso8601String(),
            'released_by' => Auth::id(),
        ];

        $autoStatus['released_breakpoints'] = $releasedBreakpoints;
        $autoStatus['pending_actions'] = $pendingActions
            ->reject(fn (array $pendingAction): bool => ($pendingAction['code'] ?? null) === $breakpointCode)
            ->values()
            ->all();
        $autoStatus['status'] = collect($autoStatus['pending_actions'])
            ->contains(fn (array $pendingAction): bool => ($pendingAction['blocking'] ?? false))
            ? AutoStatus::AGUARDANDO_ACAO->value
            : AutoStatus::ATIVO->value;
        $autoStatus['reason'] = "Breakpoint liberado: {$action['message']}. Reanalisando o fluxo.";
        $autoStatus['last_analyzed_at'] = now();
        $autoStatus['confirmed_dispatchable_steps'] = [];

        if (isset($autoSteps[$stepKey]) && is_array($autoSteps[$stepKey])) {
            $autoSteps[$stepKey]['manual_breakpoint'] = false;
            $autoSteps[$stepKey]['awaiting_action'] = false;
            $autoSteps[$stepKey]['status'] = AutoStepStatus::PENDING->value;
            $autoSteps[$stepKey]['analysis']['blocked'] = false;
            $autoSteps[$stepKey]['analysis']['eligible'] = false;
            $autoSteps[$stepKey]['analysis']['reasons'][] = 'Breakpoint liberado para continuidade do fluxo.';
        }

        Cursos::withoutEvents(function () use ($curso, $autoStatus, $autoSteps): void {
            $curso->forceFill([
                'auto_status' => $autoStatus,
                'auto_steps' => $autoSteps,
            ])->save();
        });

        ProcessarCursoAutomacaoJob::dispatch($curso->id);

        Log::info('Breakpoint de automacao liberado para continuidade.', [
            'curso_id' => $curso->id,
            'breakpoint' => $breakpointCode,
            'step' => $stepKey,
            'released_by' => Auth::id(),
        ]);

        return [
            'released' => true,
            'already_released' => false,
            'message' => 'Breakpoint liberado. O fluxo sera reanalisado.',
            'step' => $stepKey,
        ];
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
