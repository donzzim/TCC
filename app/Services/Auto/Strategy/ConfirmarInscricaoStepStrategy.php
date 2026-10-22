<?php

namespace App\Services\Auto\Strategy;

use App\Enums\Auto\AutoSeverity;
use App\Enums\Auto\AutoStepStatus;
use App\Models\Escola\Cursos;
use App\Services\Auto\Support\AutoStructureBuilder;
use App\Services\Auto\Support\InscricaoConfirmationSelector;
use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;

class ConfirmarInscricaoStepStrategy implements StepStrategyInterface
{
    public static string $step_key = 'confirmar_inscricao';

    public function __construct(
        private InscricaoConfirmationSelector $selector,
    ) {}

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
        $step['enabled'] = $this->booleanValue(data_get(
            $auto_config,
            'inscricao.regras.aprovacao_automatica'
        ));
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

        if (! $curso->inicio_inscricao || ! $curso->termino_inscricao) {
            $this->blockWithPendingAction(
                step: $step,
                pending_actions: $pending_actions,
                code: 'missing_periodo_inscricao',
                message: 'Defina inicio e termino da inscricao para permitir a confirmacao automatica.',
                reason: 'Periodo de inscricao nao configurado.'
            );

            return;
        }

        if (now()->gt($curso->termino_inscricao)) {
            $step['completed'] = true;
            $step['analysis']['reasons'][] = 'Periodo de inscricao encerrado; confirmacao automatica finalizada.';
            return;
        }

        if ($missingSelection = $this->selector->missingSelection($auto_config)) {
            $this->blockWithPendingAction(
                step: $step,
                pending_actions: $pending_actions,
                code: $missingSelection['code'],
                message: $missingSelection['message'],
                reason: $missingSelection['reason']
            );

            return;
        }

        if (! $this->selector->preInscricaoStatusId() || ! $this->selector->inscritoStatusId()) {
            $this->blockWithPendingAction(
                step: $step,
                pending_actions: $pending_actions,
                code: 'missing_status_inscricao',
                message: 'Configure os status de pre-inscricao e inscrito nas configuracoes do sistema.',
                reason: 'Status de inscricao nao configurados.'
            );

            return;
        }

        $window = $this->resolveExecutionWindow($curso, $auto_config);

        if ($window['blocked'] ?? false) {
            $this->blockWithPendingAction(
                step: $step,
                pending_actions: $pending_actions,
                code: $window['code'],
                message: $window['message'],
                reason: $window['reason']
            );

            return;
        }

        if (! ($window['eligible'] ?? false)) {
            $step['analysis']['reasons'][] = $window['reason'];
            return;
        }

        $preInscricoes = $this->selector->preInscricoes($curso);

        if ($preInscricoes->isEmpty()) {
            $step['analysis']['reasons'][] = 'Nao ha pre-inscricoes pendentes para confirmacao.';
            return;
        }

        $matchingPreInscricoes = $this->selector->matchingPreInscricoes($curso, $auto_config, $preInscricoes);

        if ($matchingPreInscricoes->isEmpty()) {
            $step['analysis']['reasons'][] = 'Nenhuma pre-inscricao pendente atende aos criterios de confirmacao automatica.';
            return;
        }

        $confirmablePreInscricoes = $this->selector->confirmablePreInscricoes(
            curso: $curso,
            autoConfig: $auto_config,
            matchingPreInscricoes: $matchingPreInscricoes,
        );

        if ($confirmablePreInscricoes->isEmpty()) {
            $step['analysis']['reasons'][] = 'Nao ha vagas disponiveis para confirmar as pre-inscricoes elegiveis.';
            return;
        }

        $step['analysis']['eligible'] = true;
        $step['analysis']['reasons'][] = sprintf(
            'Ha %d pre-inscricao(s) elegivel(is) para confirmacao automatica.',
            $confirmablePreInscricoes->count()
        );
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

    /**
     * @return array<string, mixed>
     */
    private function resolveExecutionWindow(Cursos $curso, array $auto_config): array
    {
        if (now()->lt($curso->inicio_inscricao)) {
            return [
                'eligible' => false,
                'reason' => 'Periodo de inscricao ainda nao foi iniciado.',
            ];
        }

        $confirmarEmDataEspecifica = $this->booleanValue(data_get(
            $auto_config,
            'inscricao.regras.data_confirmacao_automatica'
        ));

        if (! $confirmarEmDataEspecifica) {
            return ['eligible' => true];
        }

        $dataConfirmacao = data_get($auto_config, 'inscricao.regras.data_confirmacao');

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
