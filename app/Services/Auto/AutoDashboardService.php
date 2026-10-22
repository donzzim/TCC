<?php

namespace App\Services\Auto;

use App\Enums\Auto\AutoStatus;
use App\Models\Escola\Cursos;
use Illuminate\Support\Collection;

class AutoDashboardService
{
    /**
     * @var array<string, string>
     */
    public const STATUS_LABELS = [
        'ativo' => 'Ativo',
        'pausado' => 'Pausado',
        'cancelado' => 'Cancelado',
        'aguardando_acao' => 'Aguardando ação',
        'concluido' => 'Concluído',
        'erro' => 'Com erro',
        'sem_status' => 'Sem status',
    ];

    /**
     * @var array<string, string>
     */
    public const STEP_LABELS = [
        'abrir_inscricao' => 'Abrir inscrições',
        'encerrar_inscricao' => 'Encerrar inscrições',
        'confirmar_inscricao' => 'Confirmar inscrições',
        'processar_aprovacoes' => 'Processar aprovações',
        'gerar_certificados' => 'Gerar certificados',
        'gerar_certificados_instrutores' => 'Certificados de instrutores',
        'gerar_relatorios' => 'Gerar relatórios',
    ];

    public static function statusOptions(): array
    {
        return collect(AutoStatus::cases())
            ->mapWithKeys(fn(AutoStatus $status): array => [
                $status->value => self::STATUS_LABELS[$status->value] ?? $status->value,
            ])
            ->all();
    }

    public static function attentionOptions(): array
    {
        return [
            'all' => 'Todos os cursos',
            'blocking' => 'Com pendências bloqueantes',
            'breakpoints' => 'Com breakpoints pendentes',
            'ready' => 'Com etapas prontas',
            'errors' => 'Com erro',
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function courseRows(array $filters = []): Collection
    {
        return Cursos::query()
            ->where('auto', true)
            ->get()
            ->map(fn(Cursos $curso): array => $this->courseSnapshot($curso))
            ->filter(fn(array $row): bool => $this->matchesFilters($row, $filters))
            ->values();
    }

    /**
     * @return array<string, mixed>
     */
    public function summary(array $filters = []): array
    {
        $rows = $this->courseRows($filters);

        return [
            'total' => $rows->count(),
            'active' => $rows->where('course_active', true)->count(),
            'waiting' => $rows->whereIn('status', ['aguardando_acao', 'pausado'])->count(),
            'errors' => $rows->where('has_error', true)->count(),
            'completed' => $rows->where('status', 'concluido')->count(),
            'blocking' => $rows->where('blocking_count', '>', 0)->count(),
            'ready_steps' => $rows->sum('ready_count'),
            'pending_actions' => $rows->sum('pending_count'),
        ];
    }

    /**
     * @return array{labels: array<int, string>, data: array<int, int>}
     */
    public function statusDistribution(array $filters = []): array
    {
        $counts = $this->courseRows($filters)->countBy('status');

        return [
            'labels' => $counts->keys()
                ->map(fn(string $status): string => self::STATUS_LABELS[$status] ?? $status)
                ->values()
                ->all(),
            'data' => $counts->values()->map(fn(mixed $count): int => (int) $count)->all(),
        ];
    }

    /**
     * @return array{labels: array<int, string>, data: array<int, int>}
     */
    public function stepStatusDistribution(array $filters = []): array
    {
        $counts = [];

        foreach ($this->courseRows($filters) as $row) {
            foreach ($row['steps'] as $step) {
                if (! ($step['enabled'] ?? false)) {
                    continue;
                }

                $status = (string) ($step['status'] ?? 'pending');
                $counts[$status] = ($counts[$status] ?? 0) + 1;
            }
        }

        arsort($counts);

        $labels = [
            'completed' => 'Concluídas',
            'ready' => 'Prontas',
            'running' => 'Executando',
            'awaiting_action' => 'Aguardando ação',
            'blocked' => 'Bloqueadas',
            'error' => 'Com erro',
            'pending' => 'Pendentes',
            'skipped' => 'Ignoradas',
        ];

        return [
            'labels' => collect($counts)->keys()->map(fn(string $status): string => $labels[$status] ?? $status)->all(),
            'data' => array_values($counts),
        ];
    }

    /**
     * @return array{labels: array<int, string>, data: array<int, int>}
     */
    public function pendingDistribution(array $filters = []): array
    {
        $counts = [];

        foreach ($this->courseRows($filters) as $row) {
            foreach ($row['pending_actions'] as $action) {
                $step = (string) ($action['step'] ?? 'geral');
                $label = self::STEP_LABELS[$step] ?? $step;
                $counts[$label] = ($counts[$label] ?? 0) + 1;
            }
        }

        arsort($counts);
        $counts = array_slice($counts, 0, 8, true);

        return [
            'labels' => array_keys($counts),
            'data' => array_values($counts),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function courseSnapshot(Cursos $curso): array
    {
        $autoStatus = is_array($curso->auto_status) ? $curso->auto_status : [];
        $autoSteps = is_array($curso->auto_steps) ? $curso->auto_steps : [];
        $pendingActions = collect($autoStatus['pending_actions'] ?? [])
            ->filter(fn(mixed $action): bool => is_array($action))
            ->values();
        $steps = collect($autoSteps)
            ->filter(fn(mixed $step): bool => is_array($step))
            ->map(fn(array $step, string $key): array => array_merge($step, [
                'key' => $key,
                'label' => self::STEP_LABELS[$key] ?? $key,
            ]))
            ->values();
        $readySteps = $steps->filter(fn(array $step): bool => ($step['status'] ?? null) === 'ready');
        $blockingActions = $pendingActions->filter(fn(array $action): bool => (bool) ($action['blocking'] ?? false));
        $breakpoints = $pendingActions->filter(fn(array $action): bool => ($action['type'] ?? null) === 'breakpoint');

        return [
            'id' => $curso->id,
            'title' => $curso->titulo,
            'course_active' => (bool) $curso->ativo,
            'status' => (string) ($autoStatus['status'] ?? 'sem_status'),
            'status_label' => self::STATUS_LABELS[$autoStatus['status'] ?? 'sem_status'] ?? 'Sem status',
            'reason' => $autoStatus['reason'] ?? null,
            'last_analyzed_at' => $autoStatus['last_analyzed_at'] ?? null,
            'pending_actions' => $pendingActions->all(),
            'pending_count' => $pendingActions->count(),
            'blocking_count' => $blockingActions->count(),
            'breakpoint_count' => $breakpoints->count(),
            'ready_count' => $readySteps->count(),
            'ready_steps' => $readySteps->pluck('label')->values()->all(),
            'next_step' => $this->nextStep($autoStatus, $steps),
            'has_error' => ($autoStatus['status'] ?? null) === 'erro'
                || $steps->contains(fn(array $step): bool => ($step['status'] ?? null) === 'error'),
            'steps' => $steps->all(),
        ];
    }

    private function nextStep(array $autoStatus, Collection $steps): ?string
    {
        $confirmed = collect($autoStatus['confirmed_dispatchable_steps'] ?? [])
            ->filter(fn(mixed $step): bool => is_string($step) && filled($step))
            ->first();

        if ($confirmed) {
            return self::STEP_LABELS[$confirmed] ?? $confirmed;
        }

        $next = $steps->first(fn(array $step): bool => ($step['status'] ?? null) === 'ready');

        return is_array($next) ? ($next['label'] ?? null) : null;
    }

    private function matchesFilters(array $row, array $filters): bool
    {
        $statuses = $filters['status'] ?? [];
        $statuses = is_array($statuses) ? $statuses : (filled($statuses) ? [$statuses] : []);

        if ($statuses !== [] && ! in_array($row['status'], $statuses, true)) {
            return false;
        }

        return match ($filters['attention'] ?? 'all') {
            'blocking' => $row['blocking_count'] > 0,
            'breakpoints' => $row['breakpoint_count'] > 0,
            'ready' => $row['ready_count'] > 0,
            'errors' => $row['has_error'],
            default => true,
        };
    }
}
