<?php

namespace App\Services\Auto;

use App\Models\Escola\Cursos;
use Carbon\Carbon;
use DateTimeInterface;
use Throwable;

class AutoConfigFlowService
{
    public function getStepOptions(): array
    {
        return collect($this->getStepDefinitions())
            ->filter(fn(array $definition): bool => $definition['breakpointable'] ?? false)
            ->mapWithKeys(fn(array $definition, string $key): array => [$key => $definition['label']])
            ->all();
    }

    public function buildPreview(Cursos $curso, array $state): array
    {
        $autoConfig = $this->normalizeConfigArray(data_get($state, 'auto_config', []));
        $autoSteps = $this->normalizeConfigArray($curso->auto_steps);
        $autoStatus = $this->normalizeConfigArray($curso->auto_status);
        $breakpoints = $this->normalizeBreakpoints(data_get($autoConfig, 'fluxo.breakpoints', []));

        $inscricaoAutomatica = $this->booleanValue(data_get($autoConfig, 'inscricao.abertura_automatica'));
        $encerramentoAutomatico = $this->booleanValue(
            data_get($autoConfig, 'inscricao.encerramento_automatico'),
            $inscricaoAutomatica,
        );
        $confirmacaoAutomatica = $this->booleanValue(data_get($autoConfig, 'inscricao.regras.aprovacao_automatica'));
        $confirmacaoEmData = $this->booleanValue(data_get($autoConfig, 'inscricao.regras.data_confirmacao_automatica'));
        $aprovacaoAutomatica = $this->booleanValue(data_get($autoConfig, 'aprovacao.aprovacao_automatica'));
        $aprovacaoEmData = $this->booleanValue(data_get($autoConfig, 'aprovacao.processar_em_data_especifica'));
        $certificadosAlunos = $this->booleanValue(data_get($autoConfig, 'certificacao.gerar_certificado_automaticamente-alunos'));
        $certificadosAlunosEmData = $this->booleanValue(data_get($autoConfig, 'certificacao.geracao-alunos.gerar_em_data_especifica'));
        $certificadosInstrutores = $this->booleanValue(data_get($autoConfig, 'certificacao.gerar_certificado_automaticamente-instrutores'));
        $certificadosInstrutoresEmData = $this->booleanValue(data_get($autoConfig, 'certificacao.geracao-instrutores.gerar_em_data_especifica_instrutores'));
        $relatoriosAutomaticos = $this->booleanValue(data_get($autoConfig, 'relatorios.gerar_automaticamente'));
        $relatoriosEmData = $this->booleanValue(data_get($autoConfig, 'relatorios.gerar_em_data_especifica'));

        $events = [
            $this->makeEvent(
                stepKey: 'abrir_inscricao',
                enabled: $inscricaoAutomatica,
                date: data_get($state, 'inicio_inscricao'),
                autoSteps: $autoSteps,
                fallbackDateLabel: 'Defina a data de inicio das inscricoes',
                source: 'Formulario: inicio_inscricao',
            ),
            $this->makeEvent(
                stepKey: 'encerrar_inscricao',
                enabled: $encerramentoAutomatico,
                date: data_get($state, 'termino_inscricao'),
                autoSteps: $autoSteps,
                fallbackDateLabel: 'Defina a data de termino das inscricoes',
                source: 'Formulario: termino_inscricao',
            ),
            $this->makeEvent(
                stepKey: 'confirmar_inscricao',
                enabled: $confirmacaoAutomatica,
                date: $confirmacaoEmData ? data_get($autoConfig, 'inscricao.regras.data_confirmacao') : null,
                autoSteps: $autoSteps,
                fallbackDateLabel: $confirmacaoEmData ? 'Defina a data de confirmacao automatica' : 'No momento da inscricao',
                source: 'Formulario: regras de confirmacao',
                expectsDate: $confirmacaoEmData,
            ),
            $this->makeEvent(
                stepKey: 'curso_inicio',
                enabled: true,
                date: $curso->inicio,
                autoSteps: $autoSteps,
                fallbackDateLabel: 'Data de inicio do curso nao definida',
                source: 'Cadastro do curso',
                fallbackStatus: 'context',
                expectsDate: false,
            ),
            $this->makeEvent(
                stepKey: 'curso_termino',
                enabled: true,
                date: $curso->termino,
                autoSteps: $autoSteps,
                fallbackDateLabel: 'Data de termino do curso nao definida',
                source: 'Cadastro do curso',
                fallbackStatus: 'context',
                expectsDate: false,
            ),
            $this->makeEvent(
                stepKey: 'processar_aprovacoes',
                enabled: $aprovacaoAutomatica,
                date: $aprovacaoEmData ? data_get($autoConfig, 'aprovacao.data_processamento') : $curso->termino,
                autoSteps: $autoSteps,
                fallbackDateLabel: $aprovacaoEmData ? 'Defina a data de processamento' : 'Apos o termino do curso',
                source: 'Formulario: aprovacao',
            ),
            $this->makeEvent(
                stepKey: 'gerar_certificados',
                enabled: $certificadosAlunos,
                date: $certificadosAlunosEmData ? data_get($autoConfig, 'certificacao.geracao-alunos.data_geracao') : null,
                autoSteps: $autoSteps,
                fallbackDateLabel: $certificadosAlunosEmData ? 'Defina a data de geracao' : 'Apos a aprovacao dos alunos',
                source: 'Formulario: certificacao de alunos',
                expectsDate: $certificadosAlunosEmData,
            ),
            $this->makeEvent(
                stepKey: 'gerar_certificados_instrutores',
                enabled: $certificadosInstrutores,
                date: $certificadosInstrutoresEmData ? data_get($autoConfig, 'certificacao.geracao-instrutores.data_geracao_instrutores') : $curso->termino,
                autoSteps: $autoSteps,
                fallbackDateLabel: $certificadosInstrutoresEmData ? 'Defina a data de geracao dos instrutores' : 'Apos o termino do curso',
                source: 'Formulario: certificacao de instrutores',
                expectsDate: $certificadosInstrutoresEmData,
            ),
            $this->makeEvent(
                stepKey: 'gerar_relatorios',
                enabled: $relatoriosAutomaticos,
                date: $relatoriosEmData ? data_get($autoConfig, 'relatorios.data_geracao') : null,
                autoSteps: $autoSteps,
                fallbackDateLabel: $relatoriosEmData ? 'Defina a data de geracao dos relatorios' : 'Apos a certificacao dos alunos',
                source: 'Formulario: relatorios',
                expectsDate: $relatoriosEmData,
            ),
        ];

        $events = $this->attachBreakpoints($events, $breakpoints, $autoSteps, $autoStatus);
        $events = $this->sortEvents($events);
        $warnings = $this->makeWarnings($events, $autoStatus);

        return [
            'status' => $this->makeGlobalStatus($autoStatus, $warnings),
            'events' => $events,
            'warnings' => $warnings,
            'summary' => [
                'enabled_steps' => collect($events)->where('enabled', true)->where('is_context', false)->count(),
                'configured_breakpoints' => collect($breakpoints)->where('enabled', true)->count(),
                'pending_warnings' => count($warnings),
                'last_analyzed_at' => $this->formatDate(data_get($autoStatus, 'last_analyzed_at')),
            ],
        ];
    }

    public function normalizeForSave(array $state): array
    {
        data_set(
            $state,
            'auto_config.inscricao.encerramento_automatico',
            $this->booleanValue(data_get($state, 'auto_config.inscricao.abertura_automatica')),
        );

        $breakpoints = data_get($state, 'auto_config.fluxo.breakpoints', []);

        if (is_array($breakpoints)) {
            data_set($state, 'auto_config.fluxo.breakpoints', array_values($breakpoints));
        }

        if ($this->booleanValue(data_get($state, 'auto_config.relatorios.gerar_automaticamente'))) {
            data_set(
                $state,
                'auto_config.relatorios.destinatarios.enviar_responsaveis',
                $this->booleanValue(
                    data_get($state, 'auto_config.relatorios.destinatarios.enviar_responsaveis'),
                    true,
                ),
            );

            foreach ([
                'auto_config.relatorios.tipos',
                'auto_config.relatorios.destinatarios.usuarios',
            ] as $arrayPath) {
                $value = data_get($state, $arrayPath, []);

                if (! is_array($value)) {
                    data_set($state, $arrayPath, filled($value) ? [$value] : []);
                }
            }
        }

        return $state;
    }

    private function getStepDefinitions(): array
    {
        return [
            'abrir_inscricao' => [
                'label' => 'Abrir inscricoes',
                'group' => 'Inscricao',
                'description' => 'Libera a janela de inscricoes conforme a data configurada.',
                'breakpointable' => true,
                'order' => 10,
            ],
            'encerrar_inscricao' => [
                'label' => 'Encerrar inscricoes',
                'group' => 'Inscricao',
                'description' => 'Fecha a janela de inscricoes conforme a data configurada.',
                'breakpointable' => true,
                'order' => 20,
            ],
            'confirmar_inscricao' => [
                'label' => 'Confirmar inscricoes',
                'group' => 'Inscricao',
                'description' => 'Confirma inscricoes automaticamente usando os criterios definidos.',
                'breakpointable' => true,
                'order' => 30,
            ],
            'curso_inicio' => [
                'label' => 'Inicio do curso',
                'group' => 'Curso',
                'description' => 'Marco informativo vindo do cadastro principal do curso.',
                'breakpointable' => false,
                'order' => 40,
            ],
            'curso_termino' => [
                'label' => 'Termino do curso',
                'group' => 'Curso',
                'description' => 'Marco informativo usado como referencia para etapas posteriores.',
                'breakpointable' => false,
                'order' => 50,
            ],
            'processar_aprovacoes' => [
                'label' => 'Processar aprovacoes',
                'group' => 'Aprovacao',
                'description' => 'Calcula aprovacao dos alunos de acordo com a frequencia minima.',
                'breakpointable' => true,
                'order' => 60,
            ],
            'gerar_certificados' => [
                'label' => 'Gerar certificados de alunos',
                'group' => 'Certificacao',
                'description' => 'Gera certificados dos alunos aprovados conforme o modelo configurado.',
                'breakpointable' => true,
                'order' => 70,
            ],
            'gerar_certificados_instrutores' => [
                'label' => 'Gerar certificados de instrutores',
                'group' => 'Certificacao',
                'description' => 'Gera certificados dos instrutores vinculados ao curso.',
                'breakpointable' => true,
                'order' => 80,
            ],
            'gerar_relatorios' => [
                'label' => 'Gerar relatorios',
                'group' => 'Relatorios',
                'description' => 'Gera e envia os relatorios selecionados para os destinatarios configurados.',
                'breakpointable' => true,
                'order' => 90,
            ],
        ];
    }

    private function makeEvent(
        string $stepKey,
        bool $enabled,
        mixed $date,
        array $autoSteps,
        string $fallbackDateLabel,
        string $source,
        string $fallbackStatus = 'configured',
        bool $expectsDate = true,
    ): array {
        $definition = $this->getStepDefinitions()[$stepKey] ?? [
            'label' => $stepKey,
            'group' => 'Fluxo',
            'description' => null,
            'order' => 999,
        ];

        $stepState = $autoSteps[$stepKey] ?? [];
        $dateInfo = $this->normalizeDate($date);
        $statusKey = $this->resolveStatus(
            stepState: $stepState,
            enabled: $enabled,
            hasDate: filled($dateInfo['label']),
            expectsDate: $expectsDate,
            fallbackStatus: $fallbackStatus,
        );
        $status = $this->getStatusMeta($statusKey);

        return [
            'step_key' => $stepKey,
            'title' => $definition['label'],
            'group' => $definition['group'],
            'description' => $definition['description'],
            'enabled' => $enabled,
            'is_context' => ! ($definition['breakpointable'] ?? true),
            'date_label' => $dateInfo['label'] ?? $fallbackDateLabel,
            'has_date' => filled($dateInfo['label']),
            'sort_at' => $dateInfo['timestamp'],
            'sort_order' => $definition['order'] ?? 999,
            'source' => $source,
            'status_key' => $statusKey,
            'status_label' => $status['label'],
            'tone' => $status['tone'],
            'details' => $this->makeEventDetails($stepState, $enabled, $source),
            'breakpoints' => [],
        ];
    }

    private function attachBreakpoints(
        array $events,
        array $breakpoints,
        array $autoSteps,
        array $autoStatus,
    ): array
    {
        $pendingActions = collect(data_get($autoStatus, 'pending_actions', []))
            ->filter(fn (mixed $action): bool => is_array($action))
            ->keyBy(fn (array $action): string => (string) ($action['code'] ?? ''));

        return collect($events)
            ->map(function (array $event) use ($breakpoints, $autoSteps, $pendingActions): array {
                $event['breakpoints'] = collect($breakpoints)
                    ->where('step_key', $event['step_key'])
                    ->where('enabled', true)
                    ->map(function (array $breakpoint) use ($pendingActions): array {
                        $pendingAction = $pendingActions->get($breakpoint['code'] ?? '');

                        return array_merge($breakpoint, [
                            'pending' => is_array($pendingAction),
                            'pending_blocking' => is_array($pendingAction)
                                && (bool) ($pendingAction['blocking'] ?? false),
                        ]);
                    })
                    ->values()
                    ->all();

                if ($this->booleanValue(data_get($autoSteps, "{$event['step_key']}.manual_breakpoint"))) {
                    $event['breakpoints'][] = [
                        'label' => 'Breakpoint operacional ativo',
                        'description' => 'Este step ja esta marcado em auto_steps como pausa manual.',
                        'position_label' => 'Estado atual',
                        'severity' => 'warning',
                        'enabled' => true,
                        'blocking' => true,
                        'notify' => false,
                    ];
                }

                return $event;
            })
            ->all();
    }

    private function normalizeBreakpoints(mixed $rawBreakpoints): array
    {
        $breakpoints = $this->normalizeConfigArray($rawBreakpoints);
        $definitions = $this->getStepDefinitions();

        return collect($breakpoints)
            ->filter(fn(mixed $breakpoint): bool => is_array($breakpoint) && filled($breakpoint['step'] ?? null))
            ->map(function (array $breakpoint, int|string $index) use ($definitions): array {
                $stepKey = (string) $breakpoint['step'];
                $stepLabel = $definitions[$stepKey]['label'] ?? $stepKey;
                $position = $breakpoint['position'] ?? 'before';

                return [
                    'code' => $this->breakpointCode($stepKey, $position, $index),
                    'fingerprint' => $this->breakpointFingerprint($breakpoint),
                    'step_key' => $stepKey,
                    'step_label' => $stepLabel,
                    'label' => filled($breakpoint['label'] ?? null)
                        ? (string) $breakpoint['label']
                        : "Breakpoint em {$stepLabel}",
                    'description' => $breakpoint['description'] ?? null,
                    'position' => $position,
                    'position_label' => $position === 'after' ? 'Depois da etapa' : 'Antes da etapa',
                    'severity' => $breakpoint['severity'] ?? 'warning',
                    'enabled' => $this->booleanValue($breakpoint['enabled'] ?? null, true),
                    'blocking' => $this->booleanValue($breakpoint['blocking'] ?? null, true),
                    'notify' => $this->booleanValue($breakpoint['notify'] ?? null, true),
                ];
            })
            ->values()
            ->all();
    }

    private function breakpointCode(string $stepKey, string $position, int|string $index): string
    {
        return "manual_breakpoint_{$stepKey}_{$position}_{$index}";
    }

    private function breakpointFingerprint(array $breakpoint): string
    {
        return sha1(json_encode([
            'step' => $breakpoint['step'] ?? null,
            'position' => $breakpoint['position'] ?? 'before',
            'label' => $breakpoint['label'] ?? null,
            'description' => $breakpoint['description'] ?? null,
            'severity' => $breakpoint['severity'] ?? 'warning',
            'blocking' => $this->booleanValue($breakpoint['blocking'] ?? null, true),
            'notify' => $this->booleanValue($breakpoint['notify'] ?? null, true),
        ], JSON_UNESCAPED_UNICODE));
    }

    private function makeWarnings(array $events, array $autoStatus): array
    {
        $warnings = collect($events)
            ->filter(fn(array $event): bool => $event['enabled'] && $event['status_key'] === 'needs_configuration')
            ->map(fn(array $event): array => [
                'severity' => 'warning',
                'message' => "A etapa \"{$event['title']}\" esta ativa, mas ainda precisa de uma data.",
            ]);

        $pendingActions = collect(data_get($autoStatus, 'pending_actions', []))
            ->filter(fn(mixed $action): bool => is_array($action))
            ->map(fn(array $action): array => [
                'severity' => $action['severity'] ?? 'warning',
                'message' => $action['message'] ?? $action['code'] ?? 'Pendencia da automacao.',
            ]);

        return $warnings
            ->merge($pendingActions)
            ->values()
            ->all();
    }

    private function makeGlobalStatus(array $autoStatus, array $warnings): array
    {
        $status = data_get($autoStatus, 'status');

        if (filled($status)) {
            $meta = $this->getStatusMeta((string) $status);

            return [
                'label' => $meta['label'],
                'tone' => $meta['tone'],
                'description' => data_get($autoStatus, 'reason') ?: 'Status consolidado vindo de auto_status.',
            ];
        }

        if (count($warnings) > 0) {
            return [
                'label' => 'Revisar configuracao',
                'tone' => 'amber',
                'description' => 'A timeline encontrou pontos ativos que ainda precisam de complemento.',
            ];
        }

        return [
            'label' => 'Previa do fluxo',
            'tone' => 'blue',
            'description' => 'Visualizacao montada a partir do formulario atual. O motor de automacao ainda pode refinar estes status.',
        ];
    }

    private function makeEventDetails(array $stepState, bool $enabled, string $source): array
    {
        $details = [$source];

        if (! $enabled) {
            $details[] = 'Etapa desativada na configuracao atual.';
        }

        if ($lastSuccess = $this->formatDate(data_get($stepState, 'last_success_at'))) {
            $details[] = "Ultimo sucesso: {$lastSuccess}";
        }

        if ($lastError = data_get($stepState, 'last_error_message')) {
            $details[] = "Erro: {$lastError}";
        }

        foreach ((array) data_get($stepState, 'analysis.reasons', []) as $reason) {
            if (filled($reason)) {
                $details[] = (string) $reason;
            }
        }

        return array_values(array_unique(array_filter($details)));
    }

    private function sortEvents(array $events): array
    {
        usort($events, function (array $first, array $second): int {
            if ($first['sort_at'] === $second['sort_at']) {
                return $first['sort_order'] <=> $second['sort_order'];
            }

            if ($first['sort_at'] === null) {
                return 1;
            }

            if ($second['sort_at'] === null) {
                return -1;
            }

            return $first['sort_at'] <=> $second['sort_at'];
        });

        return $events;
    }

    private function resolveStatus(
        array $stepState,
        bool $enabled,
        bool $hasDate,
        bool $expectsDate,
        string $fallbackStatus,
    ): string {
        if (! $enabled) {
            return 'disabled';
        }

        if (
            $this->booleanValue(data_get($stepState, 'manual_breakpoint')) ||
            $this->booleanValue(data_get($stepState, 'awaiting_action'))
        ) {
            return 'awaiting_action';
        }

        $status = data_get($stepState, 'status');

        if (filled($status)) {
            return (string) $status;
        }

        if ($expectsDate && ! $hasDate) {
            return 'needs_configuration';
        }

        return $fallbackStatus;
    }

    private function getStatusMeta(string $status): array
    {
        return [
            'ativo' => ['label' => 'Ativo', 'tone' => 'emerald'],
            'pausado' => ['label' => 'Pausado', 'tone' => 'amber'],
            'cancelado' => ['label' => 'Cancelado', 'tone' => 'gray'],
            'aguardando_acao' => ['label' => 'Aguardando acao', 'tone' => 'amber'],
            'concluido' => ['label' => 'Concluido', 'tone' => 'emerald'],
            'erro' => ['label' => 'Erro', 'tone' => 'rose'],
            'context' => ['label' => 'Marco do curso', 'tone' => 'slate'],
            'configured' => ['label' => 'Configurado', 'tone' => 'blue'],
            'needs_configuration' => ['label' => 'Configurar', 'tone' => 'amber'],
            'pending' => ['label' => 'Pendente', 'tone' => 'gray'],
            'ready' => ['label' => 'Pronto', 'tone' => 'emerald'],
            'running' => ['label' => 'Executando', 'tone' => 'sky'],
            'completed' => ['label' => 'Concluido', 'tone' => 'emerald'],
            'blocked' => ['label' => 'Bloqueado', 'tone' => 'amber'],
            'awaiting_action' => ['label' => 'Aguardando acao', 'tone' => 'amber'],
            'error' => ['label' => 'Erro', 'tone' => 'rose'],
            'skipped' => ['label' => 'Ignorado', 'tone' => 'gray'],
            'disabled' => ['label' => 'Desativado', 'tone' => 'gray'],
        ][$status] ?? [
            'label' => str($status)->headline()->toString(),
            'tone' => 'gray',
        ];
    }

    private function normalizeDate(mixed $value): array
    {
        if (blank($value)) {
            return [
                'label' => null,
                'timestamp' => null,
            ];
        }

        try {
            $date = $value instanceof DateTimeInterface
                ? Carbon::instance($value)
                : Carbon::parse((string) $value);

            return [
                'label' => $date->format('d/m/Y H:i'),
                'timestamp' => $date->timestamp,
            ];
        } catch (Throwable) {
            return [
                'label' => (string) $value,
                'timestamp' => null,
            ];
        }
    }

    private function formatDate(mixed $value): ?string
    {
        return $this->normalizeDate($value)['label'];
    }

    private function normalizeConfigArray(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value) && filled($value)) {
            $decoded = json_decode($value, true);

            return is_array($decoded) ? $decoded : [];
        }

        if (is_object($value) && method_exists($value, 'toArray')) {
            return $value->toArray();
        }

        return [];
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
