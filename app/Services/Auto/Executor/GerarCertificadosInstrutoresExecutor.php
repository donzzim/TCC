<?php

namespace App\Services\Auto\Executor;

use App\Enums\Auto\AutoSeverity;
use App\Enums\Auto\AutoStepStatus;
use App\Models\Escola\CursoAtividades;
use App\Models\Escola\CursoCertificados;
use App\Models\Escola\Cursos;
use App\Models\User;
use App\Services\Auto\Support\AutoStructureBuilder;
use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Support\Facades\Auth;
use Ramsey\Uuid\Uuid;

class GerarCertificadosInstrutoresExecutor implements AutoStepExecutorInterface
{
    public static function stepKey(): string
    {
        return 'gerar_certificados_instrutores';
    }

    public function execute(Cursos $curso, array $step, array $autoConfig, array $context = []): array
    {
        if (! $this->booleanValue(data_get($autoConfig, 'certificacao.gerar_certificado_automaticamente-instrutores'))) {
            return $this->skip('Geracao automatica de certificados dos instrutores desativada na configuracao.');
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

        $rows = $this->certificateRows($curso, $autoConfig);

        if (count($rows) <= 0) {
            return $this->block(
                code: 'missing_config_certificados_instrutores',
                message: 'Configure ao menos um instrutor para geracao automatica de certificados.',
                reason: 'Nenhuma configuracao de certificado de instrutor encontrada.',
            );
        }

        $validation = $this->validateRows($rows);

        if ($validation['blocked'] ?? false) {
            return $this->block(
                code: $validation['code'],
                message: $validation['message'],
                reason: $validation['reason'],
            );
        }

        $targetValidation = $this->validateTargets($curso, $rows);

        if ($targetValidation['blocked'] ?? false) {
            return $this->block(
                code: $targetValidation['code'],
                message: $targetValidation['message'],
                reason: $targetValidation['reason'],
            );
        }

        $pendingBefore = $this->pendingCertificateCount($curso, $rows);

        if ($pendingBefore <= 0) {
            return $this->completed(
                reason: 'Todos os instrutores configurados ja possuem certificado gerado.',
                meta: [
                    'configured_rows_count' => count($rows),
                    'pending_before_execution' => 0,
                    'generated_count' => 0,
                    'pending_after_execution' => 0,
                    'rows' => $this->metaRows($rows),
                ],
            );
        }

        $generatedCount = $this->generateCertificates($curso, $rows);
        $pendingAfter = $this->pendingCertificateCount($curso, $rows);

        if ($pendingAfter > 0 && $generatedCount <= 0) {
            return $this->block(
                code: 'certificados_instrutores_nao_gerados',
                message: 'A automacao tentou gerar certificados dos instrutores, mas ainda ha instrutores sem certificado.',
                reason: 'Nenhum certificado novo foi gerado para os instrutores configurados.',
                meta: [
                    'configured_rows_count' => count($rows),
                    'pending_before_execution' => $pendingBefore,
                    'generated_count' => $generatedCount,
                    'pending_after_execution' => $pendingAfter,
                    'rows' => $this->metaRows($rows),
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
                            ? 'Certificados dos instrutores gerados.'
                            : 'Certificados dos instrutores gerados parcialmente; ainda ha instrutores sem certificado.',
                    ],
                ],
            ],
            'meta' => [
                'action' => 'generated_instructor_certificates',
                'processed_at' => now(),
                'configured_rows_count' => count($rows),
                'pending_before_execution' => $pendingBefore,
                'generated_count' => $generatedCount,
                'pending_after_execution' => $pendingAfter,
                'rows' => $this->metaRows($rows),
            ],
            'should_reanalyze' => true,
        ];
    }

    private function resolveExecutionWindow(Cursos $curso, array $autoConfig): array
    {
        if ($this->booleanValue(data_get(
            $autoConfig,
            'certificacao.geracao-instrutores.gerar_em_data_especifica_instrutores'
        ))) {
            $dataGeracao = data_get($autoConfig, 'certificacao.geracao-instrutores.data_geracao_instrutores');

            if (! $dataGeracao) {
                return [
                    'blocked' => true,
                    'code' => 'missing_data_geracao_certificados_instrutores',
                    'message' => 'Defina a data de geracao automatica dos certificados dos instrutores.',
                    'reason' => 'Data especifica de geracao dos certificados dos instrutores nao configurada.',
                ];
            }

            try {
                $dataGeracao = $dataGeracao instanceof Carbon
                    ? $dataGeracao
                    : Carbon::parse($dataGeracao);
            } catch (InvalidFormatException) {
                return [
                    'blocked' => true,
                    'code' => 'invalid_data_geracao_certificados_instrutores',
                    'message' => 'A data de geracao automatica dos certificados dos instrutores e invalida.',
                    'reason' => 'Data especifica de geracao dos certificados dos instrutores invalida.',
                ];
            }

            if (now()->lt($dataGeracao)) {
                return [
                    'eligible' => false,
                    'reason' => 'Data especifica de geracao dos certificados dos instrutores ainda nao foi alcancada.',
                ];
            }

            return ['eligible' => true];
        }

        if (! $curso->termino) {
            return [
                'blocked' => true,
                'code' => 'missing_termino_curso_certificados_instrutores',
                'message' => 'Defina a data de termino do curso para gerar certificados dos instrutores automaticamente.',
                'reason' => 'Data de termino do curso nao configurada.',
            ];
        }

        if (now()->lt($curso->termino)) {
            return [
                'eligible' => false,
                'reason' => 'O curso ainda nao chegou a data de termino.',
            ];
        }

        return ['eligible' => true];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function certificateRows(Cursos $curso, array $autoConfig): array
    {
        $rows = data_get($autoConfig, 'certificacao.geracao-instrutores.instrutores', []);

        if (! is_array($rows)) {
            return [];
        }

        return collect($rows)
            ->filter(fn(mixed $row): bool => is_array($row))
            ->map(function (array $row) use ($curso): array {
                return [
                    'curso_id' => $curso->id,
                    'cursoatividade_id' => $this->normalizeIds($row['cursoatividade_id'] ?? []),
                    'aluno_id' => $this->normalizeIds($row['aluno_id'] ?? []),
                    'modelo_id' => $row['modelo_id'] ?? null,
                    'assinado_por' => $row['assinado_por'] ?? null,
                    'disponivel' => $this->booleanValue($row['disponivel'] ?? null, true),
                    'carga_horaria' => $row['carga_horaria'] ?? null,
                    'observacao' => $row['observacao'] ?? 'Certificado de instrutoria gerado automaticamente pelo sistema.',
                ];
            })
            ->values()
            ->all();
    }

    private function validateRows(array $rows): array
    {
        foreach ($rows as $row) {
            if (count($row['aluno_id'] ?? []) <= 0) {
                return [
                    'blocked' => true,
                    'code' => 'missing_instrutor_certificado',
                    'message' => 'Defina o instrutor que recebera o certificado.',
                    'reason' => 'Instrutor do certificado nao configurado.',
                ];
            }

            if (! filled($row['modelo_id'] ?? null)) {
                return [
                    'blocked' => true,
                    'code' => 'missing_modelo_certificado_instrutores',
                    'message' => 'Defina o modelo de certificado dos instrutores.',
                    'reason' => 'Modelo de certificado dos instrutores nao configurado.',
                ];
            }

            if (! filled($row['assinado_por'] ?? null)) {
                return [
                    'blocked' => true,
                    'code' => 'missing_assinatura_certificado_instrutores',
                    'message' => 'Defina quem assina os certificados dos instrutores.',
                    'reason' => 'Assinatura dos certificados dos instrutores nao configurada.',
                ];
            }
        }

        return ['blocked' => false];
    }

    private function validateTargets(Cursos $curso, array $rows): array
    {
        foreach ($rows as $row) {
            $activityIds = $row['cursoatividade_id'] ?? [];

            if (count($activityIds) <= 0) {
                continue;
            }

            $count = CursoAtividades::query()
                ->where('curso_id', $curso->id)
                ->whereIn('id', $activityIds)
                ->count();

            if ($count !== count(array_unique($activityIds))) {
                return [
                    'blocked' => true,
                    'code' => 'invalid_atividades_certificado_instrutores',
                    'message' => 'Uma ou mais atividades selecionadas para certificacao dos instrutores nao pertencem ao curso.',
                    'reason' => 'Atividades de certificacao dos instrutores invalidas.',
                ];
            }
        }

        return ['blocked' => false];
    }

    private function pendingCertificateCount(Cursos $curso, array $rows): int
    {
        return collect($rows)->sum(function (array $row) use ($curso): int {
            return collect($this->targetsForRow($curso, $row))->sum(function (Cursos|CursoAtividades $target) use ($row): int {
                return collect($row['aluno_id'])
                    ->filter(fn(int $userId): bool => $this->canGenerateForUser($userId, (int) $row['assinado_por']))
                    ->reject(fn(int $userId): bool => $this->hasCertificate(
                        cursoId: (int) $target->id,
                        userId: $userId,
                        modeloId: (int) $row['modelo_id'],
                    ))
                    ->count();
            });
        });
    }

    private function generateCertificates(Cursos $curso, array $rows): int
    {
        $generated = 0;

        foreach ($rows as $row) {
            foreach ($this->targetsForRow($curso, $row) as $target) {
                foreach ($row['aluno_id'] as $userId) {
                    if (! $this->canGenerateForUser($userId, (int) $row['assinado_por'])) {
                        continue;
                    }

                    $user = User::query()->find($userId);

                    if (! $user) {
                        continue;
                    }

                    $generated += $this->storeCertificate($target, $user, $row) ? 1 : 0;
                }
            }
        }

        return $generated;
    }

    /**
     * @return array<int, Cursos|CursoAtividades>
     */
    private function targetsForRow(Cursos $curso, array $row): array
    {
        $activityIds = $row['cursoatividade_id'] ?? [];

        if (count($activityIds) <= 0) {
            return [$curso];
        }

        return CursoAtividades::query()
            ->where('curso_id', $curso->id)
            ->whereIn('id', $activityIds)
            ->get()
            ->all();
    }

    private function storeCertificate(Cursos|CursoAtividades $target, User $user, array $row): bool
    {
        $attributes = [
            'curso_id' => $target->id,
            'certificable_id' => $target->id,
            'certificable_type' => $target::class,
            'pessoa_id' => $user->id,
            'modelo_id' => (int) $row['modelo_id'],
        ];

        return CursoCertificados::withoutEvents(function () use ($attributes, $target, $user, $row): bool {
            $certificate = CursoCertificados::query()->firstOrNew($attributes);
            $created = ! $certificate->exists;

            if ($created && blank($certificate->uuid)) {
                $certificate->uuid = Uuid::uuid4()->toString();
            }

            $certificate->forceFill(array_merge($attributes, [
                'assinado_por' => (int) $row['assinado_por'],
                'atualizado_por' => $this->actorId(),
                'titulo' => $target->titulo,
                'nome' => $user->name,
                'carga_horaria' => $this->resolveCargaHoraria($target, $row),
                'disponivel' => $this->booleanValue($row['disponivel'] ?? null, true),
                'observacao' => $row['observacao'] ?? null,
                'externo' => false,
            ]))->save();

            return $created;
        });
    }

    private function resolveCargaHoraria(Cursos|CursoAtividades $target, array $row): mixed
    {
        if ($target instanceof CursoAtividades) {
            return $target->carga_horaria;
        }

        return filled($row['carga_horaria'] ?? null)
            ? $row['carga_horaria']
            : $target->carga_horaria;
    }

    private function canGenerateForUser(int $userId, int $assinaturaId): bool
    {
        return $userId > 0 && $userId !== $assinaturaId;
    }

    private function hasCertificate(int $cursoId, int $userId, int $modeloId): bool
    {
        return CursoCertificados::query()
            ->where('curso_id', $cursoId)
            ->where('pessoa_id', $userId)
            ->where('modelo_id', $modeloId)
            ->exists();
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
                'action' => 'generated_instructor_certificates',
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
                'action' => 'skipped_generate_instructor_certificates',
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
                'action' => 'blocked_generate_instructor_certificates',
                'reason' => $reason,
                'blocked_at' => now(),
            ], $meta),
            'should_reanalyze' => true,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function metaRows(array $rows): array
    {
        return collect($rows)
            ->map(fn(array $row): array => [
                'curso_id' => $row['curso_id'] ?? null,
                'cursoatividade_id' => $row['cursoatividade_id'] ?? [],
                'aluno_id' => $row['aluno_id'] ?? [],
                'modelo_id' => $row['modelo_id'] ?? null,
                'assinado_por' => $row['assinado_por'] ?? null,
                'disponivel' => $row['disponivel'] ?? null,
                'carga_horaria' => $row['carga_horaria'] ?? null,
            ])
            ->values()
            ->all();
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

    private function actorId(): int
    {
        return Auth::id() ?? config('settings.auto.sistemaID') ?? 1;
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
