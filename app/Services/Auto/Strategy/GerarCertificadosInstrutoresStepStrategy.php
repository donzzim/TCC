<?php

namespace App\Services\Auto\Strategy;

use App\Enums\Auto\AutoSeverity;
use App\Enums\Auto\AutoStepStatus;
use App\Models\Escola\Cursos;
use App\Services\Auto\Support\AutoStructureBuilder;
use Carbon\Carbon;

class GerarCertificadosInstrutoresStepStrategy implements StepStrategyInterface
{
    public static string $step_key = 'gerar_certificados_instrutores';

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
        $step['enabled'] = data_get($auto_config, 'certificacao.gerar_certificado_automaticamente-instrutores', false);
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
            $step['analysis']['reasons'][] = 'Certificados dos instrutores gerados.';
            return;
        }

        if (! $curso->instrutores()->exists()) {
            $this->blockWithPendingAction(
                step: $step,
                pending_actions: $pending_actions,
                code: 'missing_instrutores_certificacao',
                message: 'Vincule ao menos um instrutor ao curso para gerar certificados de instrutores.',
                reason: 'Nenhum instrutor vinculado ao curso.'
            );

            return;
        }

        $instrutoresConfig = data_get($auto_config, 'certificacao.geracao-instrutores.instrutores', []);

        if (! is_array($instrutoresConfig) || count($instrutoresConfig) <= 0) {
            $this->blockWithPendingAction(
                step: $step,
                pending_actions: $pending_actions,
                code: 'missing_config_certificados_instrutores',
                message: 'Configure ao menos um instrutor para gerar certificados de instrutores.',
                reason: 'Configuracao de certificados dos instrutores nao encontrada.'
            );

            return;
        }

        foreach ($instrutoresConfig as $instrutorConfig) {
            if (! is_array($instrutorConfig)) {
                continue;
            }

            $instrutorIds = $instrutorConfig['aluno_id'] ?? [];
            $instrutorIds = is_array($instrutorIds) ? $instrutorIds : (filled($instrutorIds) ? [$instrutorIds] : []);

            if (count($instrutorIds) <= 0) {
                $this->blockWithPendingAction(
                    step: $step,
                    pending_actions: $pending_actions,
                    code: 'missing_instrutor_certificado',
                    message: 'Defina o instrutor que recebera o certificado.',
                    reason: 'Instrutor do certificado nao configurado.'
                );

                return;
            }

            if (! filled($instrutorConfig['modelo_id'] ?? null)) {
                $this->blockWithPendingAction(
                    step: $step,
                    pending_actions: $pending_actions,
                    code: 'missing_modelo_certificado_instrutores',
                    message: 'Defina o modelo de certificado dos instrutores.',
                    reason: 'Modelo de certificado dos instrutores nao configurado.'
                );

                return;
            }

            if (! filled($instrutorConfig['assinado_por'] ?? null)) {
                $this->blockWithPendingAction(
                    step: $step,
                    pending_actions: $pending_actions,
                    code: 'missing_assinatura_certificado_instrutores',
                    message: 'Defina quem assina os certificados dos instrutores.',
                    reason: 'Assinatura dos certificados dos instrutores nao configurada.'
                );

                return;
            }
        }

        $gerarEmDataEspecifica = data_get($auto_config, 'certificacao.geracao-instrutores.gerar_em_data_especifica_instrutores', false);

        if ($gerarEmDataEspecifica) {
            $dataGeracao = data_get($auto_config, 'certificacao.geracao-instrutores.data_geracao_instrutores');

            if (! $dataGeracao) {
                $this->blockWithPendingAction(
                    step: $step,
                    pending_actions: $pending_actions,
                    code: 'missing_data_geracao_certificados_instrutores',
                    message: 'Defina a data de geração automática dos certificados dos instrutores.',
                    reason: 'Data específica de geração dos certificados dos instrutores não configurada.'
                );

                return;
            }

            if (now()->gte(Carbon::parse($dataGeracao))) {
                $this->markEligible($step, 'Data específica de geração dos certificados dos instrutores alcançada.');
                return;
            }

            $step['analysis']['reasons'][] = 'Data específica de geração dos certificados dos instrutores ainda não alcançada.';
            return;
        }

        if (! $curso->termino) {
            $this->blockWithPendingAction(
                step: $step,
                pending_actions: $pending_actions,
                code: 'missing_termino_curso_certificados_instrutores',
                message: 'Defina a data de término do curso para gerar certificados dos instrutores automaticamente.',
                reason: 'Data de término do curso não configurada.'
            );

            return;
        }

        if (now()->gte($curso->termino)) {
            $this->markEligible($step, 'Curso encerrado. Certificados dos instrutores podem ser gerados.');
            return;
        }

        $step['analysis']['reasons'][] = 'O curso ainda não chegou à data de término.';
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
