<?php

namespace App\Services\Auto\Executor;

use App\Enums\Auto\AutoSeverity;
use App\Enums\Auto\AutoStepStatus;
use App\Models\Escola\CursoAlunos;
use App\Models\Escola\Cursos;
use App\Services\Auto\Support\AutoStructureBuilder;
use App\Services\Pedagogico\AprovacaoService;
use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use RuntimeException;

class ProcessarAprovacoesExecutor implements AutoStepExecutorInterface
{
    public static function stepKey(): string
    {
        return 'processar_aprovacoes';
    }

    public function execute(Cursos $curso, array $step, array $autoConfig, array $context = []): array
    {
        if (! $this->booleanValue(data_get($autoConfig, 'aprovacao.aprovacao_automatica'))) {
            return $this->skip('Processamento automatico das aprovacoes desativado na configuracao.');
        }

        if (! $curso->termino) {
            return $this->block(
                code: 'missing_termino_curso',
                message: 'Defina a data de termino do curso para permitir o processamento automatico das aprovacoes.',
                reason: 'Data de termino do curso nao configurada.',
            );
        }

        if (! $this->inscritoStatusId()) {
            return $this->block(
                code: 'missing_status_inscrito',
                message: 'Configure o status de inscrito nas configuracoes do sistema.',
                reason: 'Status de inscrito nao configurado.',
            );
        }

        if (! $this->validApprovalRate($curso->aprovacao)) {
            return $this->block(
                code: 'invalid_aprovacao_minima',
                message: 'Defina uma frequencia minima de aprovacao entre 0 e 100.',
                reason: 'Frequencia minima de aprovacao invalida.',
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

        $pendingBefore = $this->pendingApprovalCount($curso);

        if ($pendingBefore <= 0) {
            return [
                'step' => [
                    'status' => AutoStepStatus::COMPLETED->value,
                    'completed' => true,
                    'awaiting_action' => false,
                    'analysis' => [
                        'blocked' => false,
                        'eligible' => false,
                        'reasons' => ['Nao ha alunos inscritos pendentes de aprovacao.'],
                    ],
                ],
                'meta' => [
                    'action' => 'processed_approvals',
                    'processed_at' => now(),
                    'pending_before_execution' => 0,
                    'approved_count' => 0,
                    'pending_after_execution' => 0,
                    'approval_rate' => (float) $curso->aprovacao,
                ],
                'should_reanalyze' => true,
            ];
        }

        $approvedCount = AprovacaoService::aprovarAlunos(
            curso: $curso,
            users: [],
            observacao: 'Aluno aprovado automaticamente pelo sistema.',
        );

        if ($approvedCount < 0) {
            throw new RuntimeException('AprovacaoService retornou erro ao processar aprovacoes automaticas.');
        }

        $curso = $curso->fresh(['atividades', 'alunos']) ?? $curso;
        $pendingAfter = $this->pendingApprovalCount($curso);
        $completed = $pendingAfter <= 0;

        return [
            'step' => [
                'status' => $completed
                    ? AutoStepStatus::COMPLETED->value
                    : AutoStepStatus::PENDING->value,
                'completed' => $completed,
                'completed_at' => $completed ? now() : null,
                'awaiting_action' => false,
                'analysis' => [
                    'blocked' => false,
                    'eligible' => false,
                    'reasons' => [
                        $completed
                            ? 'Aprovacoes automaticas processadas.'
                            : 'Aprovacoes automaticas processadas, mas ainda ha alunos pendentes.',
                    ],
                ],
            ],
            'meta' => [
                'action' => 'processed_approvals',
                'processed_at' => now(),
                'pending_before_execution' => $pendingBefore,
                'approved_count' => $approvedCount,
                'pending_after_execution' => $pendingAfter,
                'approval_rate' => (float) $curso->aprovacao,
                'processar_em_data_especifica' => $this->booleanValue(data_get(
                    $autoConfig,
                    'aprovacao.processar_em_data_especifica'
                )),
                'data_processamento' => data_get($autoConfig, 'aprovacao.data_processamento'),
            ],
            'should_reanalyze' => true,
        ];
    }

    private function pendingApprovalCount(Cursos $curso): int
    {
        $statusId = $this->inscritoStatusId();

        if (! $statusId) {
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

    /**
     * @return array<string, mixed>
     */
    private function resolveExecutionWindow(Cursos $curso, array $autoConfig): array
    {
        $processarEmDataEspecifica = $this->booleanValue(data_get(
            $autoConfig,
            'aprovacao.processar_em_data_especifica'
        ));

        if (! $processarEmDataEspecifica) {
            if (now()->lt($curso->termino)) {
                return [
                    'eligible' => false,
                    'reason' => 'O curso ainda nao chegou a data de termino.',
                ];
            }

            return ['eligible' => true];
        }

        $dataProcessamento = data_get($autoConfig, 'aprovacao.data_processamento');

        if (! $dataProcessamento) {
            return [
                'blocked' => true,
                'code' => 'missing_data_processamento_aprovacoes',
                'message' => 'Defina a data de processamento automatico das aprovacoes.',
                'reason' => 'Data especifica de processamento das aprovacoes nao configurada.',
            ];
        }

        try {
            $dataProcessamento = $dataProcessamento instanceof Carbon
                ? $dataProcessamento
                : Carbon::parse($dataProcessamento);
        } catch (InvalidFormatException) {
            return [
                'blocked' => true,
                'code' => 'invalid_data_processamento_aprovacoes',
                'message' => 'A data de processamento automatico das aprovacoes e invalida.',
                'reason' => 'Data especifica de processamento das aprovacoes invalida.',
            ];
        }

        if ($dataProcessamento->lt($curso->termino)) {
            return [
                'blocked' => true,
                'code' => 'invalid_data_processamento_aprovacoes',
                'message' => 'A data de processamento das aprovacoes deve ser igual ou posterior ao termino do curso.',
                'reason' => 'Data especifica de processamento anterior ao termino do curso.',
            ];
        }

        if (now()->lt($dataProcessamento)) {
            return [
                'eligible' => false,
                'reason' => 'Data especifica de processamento das aprovacoes ainda nao foi alcancada.',
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
                'action' => 'skipped_process_approvals',
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
                'action' => 'blocked_process_approvals',
                'reason' => $reason,
                'blocked_at' => now(),
            ],
            'should_reanalyze' => true,
        ];
    }

    private function inscritoStatusId(): ?string
    {
        $status = config('settings.inscricao.inscrito');

        return filled($status) ? (string) $status : null;
    }

    private function validApprovalRate(mixed $value): bool
    {
        return is_numeric($value)
            && (float) $value >= 0
            && (float) $value <= 100;
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
