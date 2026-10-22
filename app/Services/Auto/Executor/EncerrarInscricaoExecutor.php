<?php

namespace App\Services\Auto\Executor;

use App\Enums\Auto\AutoSeverity;
use App\Enums\Auto\AutoStepStatus;
use App\Models\Escola\Cursos;
use App\Services\Auto\Support\AutoStructureBuilder;
use Illuminate\Support\Facades\Auth;

class EncerrarInscricaoExecutor implements AutoStepExecutorInterface
{
    public static function stepKey(): string
    {
        return 'encerrar_inscricao';
    }

    public function execute(Cursos $curso, array $step, array $autoConfig, array $context = []): array
    {
        $enabled = $this->booleanValue(
            data_get($autoConfig, 'inscricao.encerramento_automatico'),
            $this->booleanValue(data_get($autoConfig, 'inscricao.abertura_automatica')),
        );

        if (! $enabled) {
            return $this->skip('Encerramento automatico de inscricoes desativado na configuracao.');
        }

        if (! $curso->termino_inscricao) {
            return $this->block(
                code: 'missing_termino_inscricao',
                message: 'Defina a data de termino da inscricao para permitir o encerramento automatico.',
                reason: 'Data de termino da inscricao nao configurada.',
            );
        }

        if (now()->lt($curso->termino_inscricao)) {
            return $this->skip('Data de termino da inscricao ainda nao foi alcancada.');
        }

        $wasOpen = (bool) $curso->liberar_inscricao;

        if ($wasOpen) {
            $this->updateRegistrationAvailability($curso, false);
        }

        return [
            'step' => [
                'status' => AutoStepStatus::COMPLETED->value,
                'completed' => true,
                'awaiting_action' => false,
            ],
            'meta' => [
                'action' => 'closed_registration',
                'was_open' => $wasOpen,
                'closed_at' => now(),
                'inicio_inscricao' => $curso->inicio_inscricao,
                'termino_inscricao' => $curso->termino_inscricao,
            ],
            'should_reanalyze' => true,
        ];
    }

    private function skip(string $reason): array
    {
        return [
            'step' => [
                'status' => AutoStepStatus::SKIPPED->value,
                'completed' => false,
                'awaiting_action' => false,
                'analysis' => [
                    'blocked' => false,
                    'eligible' => false,
                    'reasons' => [$reason],
                ],
            ],
            'meta' => [
                'action' => 'skipped_close_registration',
                'reason' => $reason,
                'skipped_at' => now(),
            ],
            'should_reanalyze' => true,
        ];
    }

    private function block(string $code, string $message, string $reason): array
    {
        return [
            'step' => [
                'status' => AutoStepStatus::AWAITING_ACTION->value,
                'completed' => false,
                'awaiting_action' => true,
                'analysis' => [
                    'blocked' => true,
                    'eligible' => false,
                    'reasons' => [$reason],
                ],
            ],
            'pending_actions' => [
                AutoStructureBuilder::makePendingAction(
                    code: $code,
                    message: $message,
                    step: self::stepKey(),
                    blocking: true,
                    severity: AutoSeverity::WARNING->value,
                ),
            ],
            'meta' => [
                'action' => 'blocked_close_registration',
                'reason' => $reason,
                'blocked_at' => now(),
            ],
            'should_reanalyze' => true,
        ];
    }

    private function updateRegistrationAvailability(Cursos $curso, bool $available): void
    {
        Cursos::withoutEvents(function () use ($curso, $available): void {
            $curso->forceFill([
                'liberar_inscricao' => $available,
                'atualizado_por' => Auth::id() ?? config('settings.auto.sistemaID') ?? 1,
            ])->save();
        });
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
