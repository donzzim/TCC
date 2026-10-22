<?php

namespace App\Services\Auto\Strategy;

use App\Enums\Auto\AutoSeverity;
use App\Enums\Auto\AutoStepStatus;
use App\Models\Escola\CursoAlunos;
use App\Models\Escola\CursoAtividades;
use App\Models\Escola\CursoCertificados;
use App\Models\Escola\Cursos;
use App\Services\Auto\Support\AutoStructureBuilder;
use Carbon\Carbon;

class GerarCertificadosStepStrategy implements StepStrategyInterface
{
    public static string $step_key = 'gerar_certificados';

    public function reconcile(Cursos $curso, array $step, array $auto_config, array &$pending_actions): array
    {
        $this->resetAnalysis($step);
        $this->resolveEnabled($step, $auto_config);
        $this->analyzeConditions($curso, $step, $auto_config, $pending_actions);
        $this->resolveStatus($curso, $step, $auto_config, $pending_actions);

        return $step;
    }

    public function resetAnalysis(array &$step): void
    {
        $step['analysis'] = [
            'last_analyzed_at' => now(),
            'eligible' => false,
            'blocked' => false,
            'has_error' => false,
            'reasons' => [],
        ];

        $step['awaiting_action'] = false;
    }

    public function resolveEnabled(array &$step, array $auto_config): void
    {
        $step['enabled'] = data_get($auto_config, 'certificacao.gerar_certificado_automaticamente-alunos', false);
    }

    public function analyzeConditions(
        Cursos $curso,
        array &$step,
        array $auto_config,
        array &$pending_actions
    ): void {
        if (! $step['enabled']) {
            $step['analysis']['reasons'][] = 'Etapa desabilitada no autoconfig.';
            return;
        }

        if ($step['completed'] ?? false) {
            $step['analysis']['reasons'][] = 'Certificados dos alunos gerados.';
            return;
        }

        if (! data_get($auto_config, 'certificacao.geracao-alunos.form.modelo_id')) {
            $this->blockWithPendingAction(
                step: $step,
                pending_actions: $pending_actions,
                code: 'missing_modelo_certificado_alunos',
                message: 'Defina o modelo de certificado dos alunos.',
                reason: 'Modelo de certificado dos alunos não configurado.'
            );

            return;
        }

        if (! data_get($auto_config, 'certificacao.geracao-alunos.form.assinado_por')) {
            $this->blockWithPendingAction(
                step: $step,
                pending_actions: $pending_actions,
                code: 'missing_assinatura_certificado_alunos',
                message: 'Defina quem assina os certificados dos alunos.',
                reason: 'Assinatura dos certificados dos alunos não configurada.'
            );

            return;
        }

        $gerarEmDataEspecifica = data_get($auto_config, 'certificacao.geracao-alunos.gerar_em_data_especifica', false);
        $certificateState = $this->certificateState($curso, $auto_config);

        if ($gerarEmDataEspecifica) {
            $dataGeracao = data_get($auto_config, 'certificacao.geracao-alunos.data_geracao');

            if (! $dataGeracao) {
                $this->blockWithPendingAction(
                    step: $step,
                    pending_actions: $pending_actions,
                    code: 'missing_data_geracao_certificados_alunos',
                    message: 'Defina a data de geração automática dos certificados dos alunos.',
                    reason: 'Data específica de geração dos certificados dos alunos não configurada.'
                );

                return;
            }

            if (now()->gte(Carbon::parse($dataGeracao)) && $certificateState['pending_count'] > 0) {
                $this->markEligible($step, 'Data específica de geração dos certificados dos alunos alcançada.');
                return;
            }

            $step['analysis']['reasons'][] = now()->gte(Carbon::parse($dataGeracao))
                ? $this->pendingReason($certificateState)
                : 'Data especifica de geracao dos certificados dos alunos ainda nao alcancada.';
            return;
        }

        if ($certificateState['pending_count'] > 0) {
            $this->markEligible($step, 'Há alunos aprovados para geração de certificados.');
            return;
        }

        $step['analysis']['reasons'][] = $this->pendingReason($certificateState);
    }

    public function resolveStatus(
        Cursos $curso,
        array &$step,
        array $auto_config,
        array &$pending_actions
    ): void {
        if ($step['completed']) {
            $step['status'] = AutoStepStatus::COMPLETED->value;
            return;
        }

        if (($step['analysis']['has_error'] ?? false) === true) {
            $step['status'] = AutoStepStatus::ERROR->value;
            return;
        }

        if ($step['manual_breakpoint'] || $step['awaiting_action']) {
            $step['status'] = AutoStepStatus::AWAITING_ACTION->value;
            return;
        }

        if ($step['analysis']['blocked']) {
            $step['status'] = AutoStepStatus::BLOCKED->value;
            return;
        }

        if ($step['enabled'] && $step['analysis']['eligible']) {
            $step['status'] = AutoStepStatus::READY->value;
            return;
        }

        $step['status'] = AutoStepStatus::PENDING->value;
    }

    private function markEligible(array &$step, string $reason): void
    {
        $step['analysis']['eligible'] = true;
        $step['analysis']['reasons'][] = $reason;
    }

    /**
     * @return array{eligible_count: int, pending_count: int}
     */
    private function certificateState(Cursos $curso, array $auto_config): array
    {
        $activityIds = data_get($auto_config, 'certificacao.geracao-alunos.form.cursoatividade_id', []);

        if (! is_array($activityIds)) {
            $activityIds = filled($activityIds) ? [$activityIds] : [];
        }

        $activityIds = collect($activityIds)
            ->filter(fn(mixed $id): bool => filled($id))
            ->map(fn(mixed $id): int => (int) $id)
            ->unique()
            ->values();

        if ($activityIds->isNotEmpty()) {
            $validActivityIds = CursoAtividades::query()
                ->where('curso_id', $curso->id)
                ->whereIn('id', $activityIds)
                ->pluck('id');

            $approved = CursoAlunos::query()
                ->whereIn('curso_id', $validActivityIds)
                ->where('situacaoinscricao_id', config('settings.inscricao.inscrito'))
                ->where('aprovado', true)
                ->get(['curso_id', 'aluno_id']);

            return [
                'eligible_count' => $approved->count(),
                'pending_count' => $approved
                    ->reject(fn(CursoAlunos $aluno): bool => $this->hasCertificate((int) $aluno->curso_id, (int) $aluno->aluno_id))
                    ->count(),
            ];
        }

        if (data_get($auto_config, 'certificacao.geracao-alunos.form.totalatvd', false) && $curso->temAtividades()) {
            $parentUserIds = $curso->alunos()
                ->where('situacaoinscricao_id', config('settings.inscricao.inscrito'))
                ->pluck('aluno_id');

            $certificableActivityIds = $curso->atividades()
                ->where('certificavel', true)
                ->pluck('id');

            $approvedUserIds = CursoAlunos::query()
                ->whereIn('curso_id', $certificableActivityIds)
                ->whereIn('aluno_id', $parentUserIds)
                ->where('aprovado', true)
                ->distinct()
                ->pluck('aluno_id');

            return [
                'eligible_count' => $approvedUserIds->count(),
                'pending_count' => $approvedUserIds
                    ->reject(fn(mixed $userId): bool => $this->hasCertificate($curso->id, (int) $userId))
                    ->count(),
            ];
        }

        $approvedUserIds = $curso->alunos()
            ->where('situacaoinscricao_id', config('settings.inscricao.inscrito'))
            ->where('aprovado', true)
            ->pluck('aluno_id');

        return [
            'eligible_count' => $approvedUserIds->count(),
            'pending_count' => $approvedUserIds
                ->reject(fn(mixed $userId): bool => $this->hasCertificate($curso->id, (int) $userId))
                ->count(),
        ];
    }

    private function pendingReason(array $certificateState): string
    {
        if (($certificateState['eligible_count'] ?? 0) > 0) {
            return 'Todos os alunos aprovados ja possuem certificado gerado.';
        }

        return 'Aguardando alunos aprovados para geracao dos certificados.';
    }

    private function hasCertificate(int $cursoId, int $userId): bool
    {
        return CursoCertificados::query()
            ->where('curso_id', $cursoId)
            ->where('pessoa_id', $userId)
            ->exists();
    }

    private function blockWithPendingAction(
        array &$step,
        array &$pending_actions,
        string $code,
        string $message,
        string $reason
    ): void {
        $step['analysis']['blocked'] = true;
        $step['awaiting_action'] = true;
        $step['analysis']['reasons'][] = $reason;

        $pending_actions[] = AutoStructureBuilder::makePendingAction(
            code: $code,
            message: $message,
            step: self::$step_key,
            blocking: true,
            severity: AutoSeverity::WARNING->value
        );
    }
}
