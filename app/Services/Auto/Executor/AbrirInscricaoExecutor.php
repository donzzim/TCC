<?php

namespace App\Services\Auto\Executor;

use App\Enums\Auto\AutoSeverity;
use App\Enums\Auto\AutoStepStatus;
use App\Models\Escola\Cursos;
use App\Services\Auto\Support\AutoStructureBuilder;
use Illuminate\Support\Facades\Auth;

class AbrirInscricaoExecutor implements AutoStepExecutorInterface
{
    public static function stepKey(): string
    {
        return 'abrir_inscricao';
    }

    public function execute(Cursos $curso, array $step, array $autoConfig, array $context = []): array
    {
        if (! $this->booleanValue(data_get($autoConfig, 'inscricao.abertura_automatica'))) {
            return $this->skip('Abertura automatica de inscricoes desativada na configuracao.');
        }

        if (! $curso->inicio_inscricao) {
            return $this->block(
                code: 'missing_inicio_inscricao',
                message: 'Defina a data de inicio da inscricao para permitir a abertura automatica.',
                reason: 'Data de inicio da inscricao nao configurada.',
            );
        }

        if (now()->lt($curso->inicio_inscricao)) {
            return $this->skip('Data de inicio da inscricao ainda nao foi alcancada.');
        }

        if ($curso->termino_inscricao && now()->gte($curso->termino_inscricao)) {
            return $this->skip(
                reason: 'Janela de inscricao ja encerrada. A abertura automatica foi ignorada.',
                completed: true,
            );
        }

        if ($this->requiresMeetingLink($curso) && blank($curso->link)) {
            return $this->block(
                code: 'missing_link_inscricao_remota',
                message: 'Informe o link de reuniao/webinar antes de liberar inscricoes para cursos remotos ou hibridos.',
                reason: 'Curso remoto/hibrido sem link de reuniao/webinar.',
            );
        }

        $wasOpen = (bool) $curso->liberar_inscricao;

        if (! $wasOpen) {
            $this->updateRegistrationAvailability($curso, true);
        }

        return [
            'step' => [
                'status' => AutoStepStatus::COMPLETED->value,
                'completed' => true,
                'awaiting_action' => false,
            ],
            'meta' => [
                'action' => 'opened_registration',
                'was_open' => $wasOpen,
                'opened_at' => now(),
                'inicio_inscricao' => $curso->inicio_inscricao,
                'termino_inscricao' => $curso->termino_inscricao,
            ],
            'should_reanalyze' => true,
        ];
    }

    private function skip(string $reason, bool $completed = false): array
    {
        return [
            'step' => [
                'status' => AutoStepStatus::SKIPPED->value,
                'completed' => $completed,
                'awaiting_action' => false,
                'analysis' => [
                    'blocked' => false,
                    'eligible' => false,
                    'reasons' => [$reason],
                ],
            ],
            'meta' => [
                'action' => 'skipped_open_registration',
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
                'action' => 'blocked_open_registration',
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

    private function requiresMeetingLink(Cursos $curso): bool
    {
        $remoteModalities = array_filter([
            config('settings.modalidade.remoto'),
            config('settings.modalidade.hibrido'),
        ], fn(mixed $value): bool => filled($value));

        return in_array(
            (string) $curso->modalidade_id,
            array_map('strval', $remoteModalities),
            true,
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
