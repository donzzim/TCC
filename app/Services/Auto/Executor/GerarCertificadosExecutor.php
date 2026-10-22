<?php

namespace App\Services\Auto\Executor;

use App\Enums\Auto\AutoSeverity;
use App\Enums\Auto\AutoStepStatus;
use App\Models\Escola\CursoAlunos;
use App\Models\Escola\CursoAtividades;
use App\Models\Escola\CursoCertificados;
use App\Models\Escola\Cursos;
use App\Services\Auto\Support\AutoStructureBuilder;
use App\Services\Pedagogico\CertificadoService;
use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;

class GerarCertificadosExecutor implements AutoStepExecutorInterface
{
    public static function stepKey(): string
    {
        return 'gerar_certificados';
    }

    public function execute(Cursos $curso, array $step, array $autoConfig, array $context = []): array
    {
        if (! $this->booleanValue(data_get($autoConfig, 'certificacao.gerar_certificado_automaticamente-alunos'))) {
            return $this->skip('Geracao automatica de certificados dos alunos desativada na configuracao.');
        }

        $data = $this->certificateData($curso, $autoConfig);

        if (! filled($data['modelo_id'] ?? null)) {
            return $this->block(
                code: 'missing_modelo_certificado_alunos',
                message: 'Defina o modelo de certificado dos alunos.',
                reason: 'Modelo de certificado dos alunos nao configurado.',
            );
        }

        if (! filled($data['assinado_por'] ?? null)) {
            return $this->block(
                code: 'missing_assinatura_certificado_alunos',
                message: 'Defina quem assina os certificados dos alunos.',
                reason: 'Assinatura dos certificados dos alunos nao configurada.',
            );
        }

        $window = $this->resolveExecutionWindow($autoConfig);

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

        $targetResult = $this->resolveTargets($curso, $data);

        if ($targetResult['blocked'] ?? false) {
            return $this->block(
                code: $targetResult['code'],
                message: $targetResult['message'],
                reason: $targetResult['reason'],
            );
        }

        $targets = $targetResult['targets'];
        $eligibleBefore = $this->eligibleRecipientIds($curso, $data, $targets);
        $pendingBefore = $this->pendingCertificateCount($curso, $data, $targets);

        if (count($eligibleBefore) <= 0) {
            return $this->skip('Nao ha alunos aprovados para geracao de certificados.');
        }

        if ($pendingBefore <= 0) {
            return $this->completed(
                reason: 'Todos os alunos aprovados ja possuem certificado gerado.',
                meta: [
                    'eligible_recipients_count' => count($eligibleBefore),
                    'pending_before_execution' => 0,
                    'generated_count' => 0,
                    'pending_after_execution' => 0,
                    'target_ids' => $this->targetIds($targets),
                    'form' => $data,
                ],
            );
        }

        $certificatesBefore = $this->certificateCount($targets);
        $this->generateCertificates($data, $targets);
        $curso = $curso->fresh(['atividades', 'alunos']) ?? $curso;
        $certificatesAfter = $this->certificateCount($targets);
        $pendingAfter = $this->pendingCertificateCount($curso, $data, $targets);
        $generatedCount = max(0, $certificatesAfter - $certificatesBefore);

        if ($pendingAfter > 0 && $generatedCount <= 0) {
            return $this->block(
                code: 'certificados_alunos_nao_gerados',
                message: 'A automacao tentou gerar certificados dos alunos, mas ainda ha aprovados sem certificado.',
                reason: 'Nenhum certificado novo foi gerado para os alunos aprovados.',
                meta: [
                    'eligible_recipients_count' => count($eligibleBefore),
                    'pending_before_execution' => $pendingBefore,
                    'generated_count' => $generatedCount,
                    'pending_after_execution' => $pendingAfter,
                    'target_ids' => $this->targetIds($targets),
                    'form' => $data,
                ],
            );
        }

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
                            ? 'Certificados dos alunos gerados.'
                            : 'Certificados dos alunos gerados parcialmente; ainda ha aprovados sem certificado.',
                    ],
                ],
            ],
            'meta' => [
                'action' => 'generated_student_certificates',
                'processed_at' => now(),
                'eligible_recipients_count' => count($eligibleBefore),
                'pending_before_execution' => $pendingBefore,
                'generated_count' => $generatedCount,
                'pending_after_execution' => $pendingAfter,
                'target_ids' => $this->targetIds($targets),
                'form' => $data,
            ],
            'should_reanalyze' => true,
        ];
    }

    private function certificateData(Cursos $curso, array $autoConfig): array
    {
        $form = data_get($autoConfig, 'certificacao.geracao-alunos.form', []);
        $form = is_array($form) ? $form : [];

        return [
            'curso_id' => $curso->id,
            'cursoatividade_id' => $this->normalizeIds($form['cursoatividade_id'] ?? []),
            'modelo_id' => $form['modelo_id'] ?? null,
            'assinado_por' => $form['assinado_por'] ?? null,
            'disponivel' => $this->booleanValue($form['disponivel'] ?? null),
            'observacao' => $form['observacao'] ?? 'Certificado gerado automaticamente pelo sistema.',
            'totalatvd' => $this->booleanValue($form['totalatvd'] ?? null),
            'auto' => true,
        ];
    }

    private function resolveExecutionWindow(array $autoConfig): array
    {
        if (! $this->booleanValue(data_get($autoConfig, 'certificacao.geracao-alunos.gerar_em_data_especifica'))) {
            return ['eligible' => true];
        }

        $dataGeracao = data_get($autoConfig, 'certificacao.geracao-alunos.data_geracao');

        if (! $dataGeracao) {
            return [
                'blocked' => true,
                'code' => 'missing_data_geracao_certificados_alunos',
                'message' => 'Defina a data de geracao automatica dos certificados dos alunos.',
                'reason' => 'Data especifica de geracao dos certificados dos alunos nao configurada.',
            ];
        }

        try {
            $dataGeracao = $dataGeracao instanceof Carbon
                ? $dataGeracao
                : Carbon::parse($dataGeracao);
        } catch (InvalidFormatException) {
            return [
                'blocked' => true,
                'code' => 'invalid_data_geracao_certificados_alunos',
                'message' => 'A data de geracao automatica dos certificados dos alunos e invalida.',
                'reason' => 'Data especifica de geracao dos certificados dos alunos invalida.',
            ];
        }

        if (now()->lt($dataGeracao)) {
            return [
                'eligible' => false,
                'reason' => 'Data especifica de geracao dos certificados dos alunos ainda nao foi alcancada.',
            ];
        }

        return ['eligible' => true];
    }

    /**
     * @return array{targets?: array<int, Cursos|CursoAtividades>, blocked?: bool, code?: string, message?: string, reason?: string}
     */
    private function resolveTargets(Cursos $curso, array $data): array
    {
        $activityIds = $data['cursoatividade_id'] ?? [];

        if (count($activityIds) <= 0) {
            return ['targets' => [$curso]];
        }

        $activities = CursoAtividades::query()
            ->where('curso_id', $curso->id)
            ->whereIn('id', $activityIds)
            ->get();

        if ($activities->count() !== count(array_unique($activityIds))) {
            return [
                'blocked' => true,
                'code' => 'invalid_atividades_certificado_alunos',
                'message' => 'Uma ou mais atividades selecionadas para certificacao dos alunos nao pertencem ao curso.',
                'reason' => 'Atividades de certificacao dos alunos invalidas.',
            ];
        }

        return ['targets' => $activities->all()];
    }

    /**
     * @param  array<int, Cursos|CursoAtividades>  $targets
     * @return array<int, int>
     */
    private function eligibleRecipientIds(Cursos $curso, array $data, array $targets): array
    {
        if ($this->usesTotalActivities($curso, $data, $targets)) {
            $parentUserIds = $curso->alunos()
                ->where('situacaoinscricao_id', config('settings.inscricao.inscrito'))
                ->pluck('aluno_id');

            $activityIds = $curso->atividades()
                ->where('certificavel', true)
                ->pluck('id');

            return CursoAlunos::query()
                ->whereIn('curso_id', $activityIds)
                ->whereIn('aluno_id', $parentUserIds)
                ->where('aprovado', true)
                ->distinct()
                ->pluck('aluno_id')
                ->map(fn(mixed $id): int => (int) $id)
                ->values()
                ->all();
        }

        return collect($targets)
            ->flatMap(fn(Cursos|CursoAtividades $target) => $target->alunos()
                ->where('situacaoinscricao_id', config('settings.inscricao.inscrito'))
                ->where('aprovado', true)
                ->pluck('aluno_id'))
            ->map(fn(mixed $id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array<int, Cursos|CursoAtividades>  $targets
     */
    private function pendingCertificateCount(Cursos $curso, array $data, array $targets): int
    {
        if ($this->usesTotalActivities($curso, $data, $targets)) {
            return collect($this->eligibleRecipientIds($curso, $data, $targets))
                ->reject(fn(int $userId): bool => $this->hasCertificate($curso->id, $userId))
                ->count();
        }

        return collect($targets)
            ->sum(function (Cursos|CursoAtividades $target): int {
                return $target->alunos()
                    ->where('situacaoinscricao_id', config('settings.inscricao.inscrito'))
                    ->where('aprovado', true)
                    ->pluck('aluno_id')
                    ->reject(fn(mixed $userId): bool => $this->hasCertificate((int) $target->id, (int) $userId))
                    ->count();
            });
    }

    /**
     * @param  array<int, Cursos|CursoAtividades>  $targets
     */
    private function generateCertificates(array $data, array $targets): void
    {
        foreach ($targets as $target) {
            CertificadoService::gerarCertificados($data, $target);
        }
    }

    /**
     * @param  array<int, Cursos|CursoAtividades>  $targets
     */
    private function certificateCount(array $targets): int
    {
        return CursoCertificados::query()
            ->whereIn('curso_id', $this->targetIds($targets))
            ->count();
    }

    private function hasCertificate(int $cursoId, int $userId): bool
    {
        return CursoCertificados::query()
            ->where('curso_id', $cursoId)
            ->where('pessoa_id', $userId)
            ->exists();
    }

    /**
     * @param  array<int, Cursos|CursoAtividades>  $targets
     * @return array<int, int>
     */
    private function targetIds(array $targets): array
    {
        return collect($targets)
            ->pluck('id')
            ->map(fn(mixed $id): int => (int) $id)
            ->values()
            ->all();
    }

    /**
     * @param  array<int, Cursos|CursoAtividades>  $targets
     */
    private function usesTotalActivities(Cursos $curso, array $data, array $targets): bool
    {
        return $this->booleanValue($data['totalatvd'] ?? null)
            && count($targets) === 1
            && ($targets[0] instanceof Cursos)
            && $curso->temAtividades();
    }

    private function completed(string $reason, array $meta = []): array
    {
        return [
            'step' => [
                'status' => AutoStepStatus::COMPLETED->value,
                'completed' => true,
                'awaiting_action' => false,
                'analysis' => [
                    'blocked' => false,
                    'eligible' => false,
                    'reasons' => [$reason],
                ],
            ],
            'meta' => array_merge([
                'action' => 'generated_student_certificates',
                'processed_at' => now(),
            ], $meta),
            'should_reanalyze' => true,
        ];
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
                'action' => 'skipped_generate_student_certificates',
                'reason' => $reason,
                'skipped_at' => now(),
            ],
            'should_reanalyze' => true,
        ];
    }

    private function block(string $code, string $message, string $reason, array $meta = []): array
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
            'meta' => array_merge([
                'action' => 'blocked_generate_student_certificates',
                'reason' => $reason,
                'blocked_at' => now(),
            ], $meta),
            'should_reanalyze' => true,
        ];
    }

    /**
     * @return array<int, int>
     */
    private function normalizeIds(mixed $value): array
    {
        if (! is_array($value)) {
            $value = filled($value) ? [$value] : [];
        }

        return collect($value)
            ->filter(fn(mixed $id): bool => filled($id))
            ->map(fn(mixed $id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
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
