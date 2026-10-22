<?php

namespace App\Services\Auto\Executor;

use App\Enums\Auto\AutoSeverity;
use App\Enums\Auto\AutoStepStatus;
use App\Models\Escola\Cursos;
use App\Models\User;
use App\Services\Auto\Support\AutoStructureBuilder;
use App\Services\Auto\Support\InscricaoConfirmationSelector;
use App\Services\Pedagogico\InscricaoService;
use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Support\Collection;

class ConfirmarInscricaoExecutor implements AutoStepExecutorInterface
{
    public function __construct(
        private InscricaoConfirmationSelector $selector,
    ) {}

    public static function stepKey(): string
    {
        return 'confirmar_inscricao';
    }

    public function execute(Cursos $curso, array $step, array $autoConfig, array $context = []): array
    {
        if (! $this->booleanValue(data_get($autoConfig, 'inscricao.regras.aprovacao_automatica'))) {
            return $this->skip('Confirmacao automatica de inscricoes desativada na configuracao.');
        }

        if (! $curso->inicio_inscricao || ! $curso->termino_inscricao) {
            return $this->block(
                code: 'missing_periodo_inscricao',
                message: 'Defina inicio e termino da inscricao para permitir a confirmacao automatica.',
                reason: 'Periodo de inscricao nao configurado.',
            );
        }

        if ($missingSelection = $this->selector->missingSelection($autoConfig)) {
            return $this->block(
                code: $missingSelection['code'],
                message: $missingSelection['message'],
                reason: $missingSelection['reason'],
            );
        }

        $window = $this->resolveExecutionWindow($curso, $autoConfig);

        if ($window['blocked'] ?? false) {
            return $this->block(
                code: $window['code'],
                message: $window['message'],
                reason: $window['reason'],
            );
        }

        if (! ($window['eligible'] ?? false)) {
            return $this->skip($window['reason']);
        }

        $preInscricoes = $this->selector->preInscricoes($curso);
        $matchingPreInscricoes = $this->selector->matchingPreInscricoes($curso, $autoConfig, $preInscricoes);
        $confirmablePreInscricoes = $this->selector->confirmablePreInscricoes(
            curso: $curso,
            autoConfig: $autoConfig,
            matchingPreInscricoes: $matchingPreInscricoes,
        );

        if ($preInscricoes->isEmpty()) {
            return $this->skip('Nao ha pre-inscricoes pendentes para confirmacao.');
        }

        if ($matchingPreInscricoes->isEmpty()) {
            return $this->skip('Nenhuma pre-inscricao pendente atende aos criterios de confirmacao automatica.');
        }

        if ($confirmablePreInscricoes->isEmpty()) {
            return $this->skip('Nao ha vagas disponiveis para confirmar as pre-inscricoes elegiveis.');
        }

        $availableSlotsBeforeExecution = $this->selector->mayExceedVagas($autoConfig)
            ? null
            : $this->selector->availableSlots($curso);
        $confirmedUsers = $this->confirm($curso, $confirmablePreInscricoes);
        $completed = now()->gt($curso->termino_inscricao);

        return [
            'step' => [
                'status' => $completed
                    ? AutoStepStatus::COMPLETED->value
                    : AutoStepStatus::PENDING->value,
                'completed' => $completed,
                'awaiting_action' => false,
                'analysis' => [
                    'blocked' => false,
                    'eligible' => false,
                    'reasons' => [
                        $completed
                            ? 'Confirmacao automatica finalizada apos o termino da inscricao.'
                            : 'Confirmacao automatica executada; a etapa permanecera ativa ate o termino da inscricao.',
                    ],
                ],
            ],
            'meta' => [
                'action' => 'confirmed_registrations',
                'confirmed_at' => now(),
                'confirmed_count' => $confirmedUsers->count(),
                'confirmed_user_ids' => $confirmedUsers->pluck('id')->values()->all(),
                'pending_pre_inscricoes_count' => $preInscricoes->count(),
                'matching_pre_inscricoes_count' => $matchingPreInscricoes->count(),
                'confirmable_pre_inscricoes_count' => $confirmablePreInscricoes->count(),
                'available_slots_before_execution' => $availableSlotsBeforeExecution,
                'exceeded_vagas' => $this->selector->mayExceedVagas($autoConfig),
                'criteria' => $this->selector->criteria($autoConfig),
            ],
            'should_reanalyze' => true,
        ];
    }

    /**
     * @param  Collection<int, \App\Models\Escola\CursoAlunos>  $preInscricoes
     * @return Collection<int, User>
     */
    private function confirm(Cursos $curso, Collection $preInscricoes): Collection
    {
        $users = $preInscricoes
            ->pluck('aluno')
            ->filter(fn(mixed $user): bool => $user instanceof User)
            ->unique('id')
            ->values();

        if ($users->isNotEmpty()) {
            InscricaoService::inscrever($curso, $users);
        }

        return $users;
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveExecutionWindow(Cursos $curso, array $autoConfig): array
    {
        $confirmarEmDataEspecifica = $this->booleanValue(data_get(
            $autoConfig,
            'inscricao.regras.data_confirmacao_automatica'
        ));

        if (now()->gt($curso->termino_inscricao)) {
            return [
                'eligible' => false,
                'reason' => 'Periodo de inscricao encerrado; nenhuma confirmacao automatica sera executada.',
            ];
        }

        if (now()->lt($curso->inicio_inscricao)) {
            return [
                'eligible' => false,
                'reason' => 'Periodo de inscricao ainda nao foi iniciado.',
            ];
        }

        if (! $confirmarEmDataEspecifica) {
            return ['eligible' => true];
        }

        $dataConfirmacao = data_get($autoConfig, 'inscricao.regras.data_confirmacao');

        if (! $dataConfirmacao) {
            return [
                'blocked' => true,
                'code' => 'missing_data_confirmacao',
                'message' => 'Defina a data de confirmacao automatica das inscricoes.',
                'reason' => 'Data especifica de confirmacao automatica nao configurada.',
            ];
        }

        try {
            $dataConfirmacao = $dataConfirmacao instanceof Carbon
                ? $dataConfirmacao
                : Carbon::parse($dataConfirmacao);
        } catch (InvalidFormatException) {
            return [
                'blocked' => true,
                'code' => 'invalid_data_confirmacao',
                'message' => 'A data de confirmacao automatica das inscricoes e invalida.',
                'reason' => 'Data especifica de confirmacao automatica invalida.',
            ];
        }

        if ($dataConfirmacao->gt($curso->termino_inscricao)) {
            return [
                'blocked' => true,
                'code' => 'data_confirmacao_after_termino',
                'message' => 'A data de confirmacao automatica deve ocorrer antes do termino da inscricao.',
                'reason' => 'Data de confirmacao automatica posterior ao termino da inscricao.',
            ];
        }

        if (now()->lt($dataConfirmacao)) {
            return [
                'eligible' => false,
                'reason' => 'Data especifica de confirmacao automatica ainda nao foi alcancada.',
            ];
        }

        return ['eligible' => true];
    }

    private function skip(string $reason): array
    {
        return [
            'step' => [
                'status' => AutoStepStatus::PENDING->value,
                'completed' => false,
                'awaiting_action' => false,
                'analysis' => [
                    'blocked' => false,
                    'eligible' => false,
                    'reasons' => [$reason],
                ],
            ],
            'meta' => [
                'action' => 'skipped_confirm_registration',
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
                'action' => 'blocked_confirm_registration',
                'reason' => $reason,
                'blocked_at' => now(),
            ],
            'should_reanalyze' => true,
        ];
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
