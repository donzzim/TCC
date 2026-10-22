<?php

namespace App\Services\Auto\Strategy;

use App\Enums\Auto\AutoSeverity;
use App\Enums\Auto\AutoStepStatus;
use App\Models\Escola\Cursos;
use App\Services\Auto\Support\AutoStructureBuilder;
use Carbon\Carbon;

class GerarRelatoriosStepStrategy implements StepStrategyInterface
{
    public static string $step_key = 'gerar_relatorios';

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
        $step['enabled'] = data_get($auto_config, 'relatorios.gerar_automaticamente', false);
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

        $tipos = (array) data_get($auto_config, 'relatorios.tipos', []);

        if (empty($tipos)) {
            $this->blockWithPendingAction(
                step: $step,
                pending_actions: $pending_actions,
                code: 'missing_tipos_relatorios',
                message: 'Selecione ao menos um relatório para geração automática.',
                reason: 'Nenhum relatório selecionado.'
            );

            return;
        }

        $enviarResponsaveis = data_get($auto_config, 'relatorios.destinatarios.enviar_responsaveis', true);
        $usuarios = (array) data_get($auto_config, 'relatorios.destinatarios.usuarios', []);

        if (! $enviarResponsaveis && empty($usuarios)) {
            $this->blockWithPendingAction(
                step: $step,
                pending_actions: $pending_actions,
                code: 'missing_destinatarios_relatorios',
                message: 'Selecione responsáveis ou usuários adicionais para receber os relatórios.',
                reason: 'Nenhum destinatário configurado para os relatórios.'
            );

            return;
        }

        $gerarEmDataEspecifica = data_get($auto_config, 'relatorios.gerar_em_data_especifica', false);

        if ($gerarEmDataEspecifica) {
            $dataGeracao = data_get($auto_config, 'relatorios.data_geracao');

            if (! $dataGeracao) {
                $this->blockWithPendingAction(
                    step: $step,
                    pending_actions: $pending_actions,
                    code: 'missing_data_geracao_relatorios',
                    message: 'Defina a data de geração e envio dos relatórios.',
                    reason: 'Data específica de geração dos relatórios não configurada.'
                );

                return;
            }

            if (now()->gte(Carbon::parse($dataGeracao))) {
                $this->markEligible($step, 'Data específica de geração e envio dos relatórios alcançada.');
                return;
            }

            $step['analysis']['reasons'][] = 'Data específica de geração e envio dos relatórios ainda não alcançada.';
            return;
        }

        if (data_get($auto_config, 'certificacao.gerar_certificado_automaticamente-alunos', false)) {
            if (data_get($curso->auto_steps, 'gerar_certificados.completed', false)) {
                $this->markEligible($step, 'Certificação dos alunos concluída. Relatórios podem ser gerados.');
                return;
            }

            $step['analysis']['reasons'][] = 'Aguardando conclusão da certificação dos alunos.';
            return;
        }

        if (! $curso->termino) {
            $this->blockWithPendingAction(
                step: $step,
                pending_actions: $pending_actions,
                code: 'missing_termino_curso_relatorios',
                message: 'Defina a data de término do curso para usar a geração padrão dos relatórios.',
                reason: 'Data de término do curso não configurada.'
            );

            return;
        }

        if (now()->gte($curso->termino)) {
            $this->markEligible($step, 'Curso encerrado. Relatórios podem ser gerados.');
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
