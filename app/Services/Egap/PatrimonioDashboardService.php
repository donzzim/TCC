<?php

namespace App\Services\Egap;

use App\Models\egap\Cadastro\ContaContabil;
use App\Models\egap\Cadastro\DescricaoDetalhada;
use App\Models\egap\Cadastro\DescricaoResumida;
use App\Models\egap\Patrimonio\BensImoveis\BemImovel;
use App\Models\egap\Patrimonio\BensMoveis\BemMovel;
use App\Models\egap\Patrimonio\BensMoveis\SituacaoBemMovel;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Throwable;

class PatrimonioDashboardService
{
    public function getSummary(?array $filters = null): array
    {
        $normalizedFilters = $this->normalizeFilters($filters);

        return $this->remember('summary', $normalizedFilters, function () use ($normalizedFilters): array {
            $movableQuery = $this->movableQuery($normalizedFilters);
            $immovableQuery = $this->immovableQuery($normalizedFilters);

            return [
                'moveis_total' => (clone $movableQuery)->count(),
                'imoveis_total' => (clone $immovableQuery)->count(),
                'moveis_ativos_total' => (clone $movableQuery)
                    ->whereIn('SituacaoBem', [1, 7, 8])
                    ->count(),
                'moveis_valor_aquisicao' => (float) (clone $movableQuery)->sum('ValorAquisicao'),
            ];
        });
    }

    public function getMoveisPorSituacao(?array $filters = null): array
    {
        $normalizedFilters = $this->normalizeFilters($filters);

        return $this->remember('moveis_por_situacao', $normalizedFilters, function () use ($normalizedFilters): array {
            $situacoes = SituacaoBemMovel::query()
                ->select(['id', 'descricao'])
                ->withCount([
                    'bensMoveis as total' => fn(Builder $query): Builder => $this->applyMovableFilters($query, $normalizedFilters),
                ])
                ->get()
                ->filter(fn(SituacaoBemMovel $situacao): bool => (int) $situacao->total > 0)
                ->mapWithKeys(fn(SituacaoBemMovel $situacao): array => [
                    ($situacao->descricao ?: 'Sem situacao') => (int) $situacao->total,
                ]);

            $situacaoIds = SituacaoBemMovel::query()->pluck('id')->all();

            $semSituacao = $this->movableQuery($normalizedFilters)
                ->where(function (Builder $query) use ($situacaoIds): void {
                    $query->whereNull('SituacaoBem');

                    if ($situacaoIds !== []) {
                        $query->orWhereNotIn('SituacaoBem', $situacaoIds);
                    }
                })
                ->count();

            if ($semSituacao > 0) {
                $situacoes['Sem situacao'] = ((int) ($situacoes['Sem situacao'] ?? 0)) + $semSituacao;
            }

            $counts = $situacoes->sortDesc();

            return [
                'labels' => $counts->keys()->values()->all(),
                'values' => $counts->values()->map(fn($value) => (int) $value)->all(),
            ];
        });
    }

    public function getMoveisPorAno(?array $filters = null): array
    {
        $normalizedFilters = $this->normalizeFilters($filters);

        return $this->remember('moveis_por_ano', $normalizedFilters, function () use ($normalizedFilters): array {
            [$startDate, $endDate] = $this->movableDateRange($normalizedFilters);

            if (! $startDate || ! $endDate) {
                return [
                    'labels' => [],
                    'values' => [],
                ];
            }

            $counts = collect();

            for ($year = (int) $startDate->format('Y'); $year <= (int) $endDate->format('Y'); $year++) {
                $total = $this->movableQuery($normalizedFilters)
                    ->where('DatadeIncorporacao', '>=', CarbonImmutable::create($year, 1, 1)->startOfDay())
                    ->where('DatadeIncorporacao', '<=', CarbonImmutable::create($year, 12, 31)->endOfDay())
                    ->count();

                if ($total > 0) {
                    $counts[$year] = (int) $total;
                }
            }

            return [
                'labels' => $counts->keys()->map(fn($value) => (string) $value)->all(),
                'values' => $counts->values()->map(fn($value) => (int) $value)->all(),
            ];
        });
    }

    public function getImoveisPorContaContabil(?array $filters = null, int $limit = 10): array
    {
        $normalizedFilters = $this->normalizeFilters($filters);

        return $this->remember("imoveis_por_conta_{$limit}", $normalizedFilters, function () use ($normalizedFilters, $limit): array {
            $contas = ContaContabil::query()
                ->select(['id', 'titulo'])
                ->withCount([
                    'bensImoveis as total' => fn(Builder $query): Builder => $this->applyImmovableFilters($query, $normalizedFilters),
                ])
                ->get()
                ->filter(fn(ContaContabil $conta): bool => (int) $conta->total > 0)
                ->mapWithKeys(fn(ContaContabil $conta): array => [
                    ($conta->titulo ?: 'Sem conta contabil') => (int) $conta->total,
                ]);

            $contaIds = ContaContabil::query()->pluck('id')->all();

            $semConta = $this->immovableQuery($normalizedFilters)
                ->where(function (Builder $query) use ($contaIds): void {
                    $query->whereNull('id_planocontas');

                    if ($contaIds !== []) {
                        $query->orWhereNotIn('id_planocontas', $contaIds);
                    }
                })
                ->count();

            if ($semConta > 0) {
                $contas['Sem conta contabil'] = ((int) ($contas['Sem conta contabil'] ?? 0)) + $semConta;
            }

            $counts = $contas
                ->sortDesc()
                ->take($limit);

            return [
                'labels' => $counts->keys()->map(fn($value) => mb_strimwidth((string) $value, 0, 40, '...'))->all(),
                'values' => $counts->values()->map(fn($value) => (int) $value)->all(),
            ];
        });
    }

    public function getTopMovableMaterials(?array $filters = null, int $limit = 10): Collection
    {
        $normalizedFilters = $this->normalizeFilters($filters);

        return $this->remember("top_materiais_{$limit}", $normalizedFilters, function () use ($normalizedFilters, $limit): Collection {
            $aggregates = [];

            $this->movableQuery($normalizedFilters)
                ->whereIn('SituacaoBem', [1, 7, 8])
                ->select([
                    'id',
                    'DescricaoResumidadoBem',
                    'id_descricaodetalhada',
                    'ValorAquisicao',
                    'ValordaReavaliacao',
                ])
                ->chunkById(5000, function (Collection $bens) use (&$aggregates): void {
                    foreach ($bens as $bem) {
                        $detalhadaId = (int) ($bem->id_descricaodetalhada ?? 0);
                        $resumidaId = (int) ($bem->DescricaoResumidadoBem ?? 0);
                        $key = $detalhadaId > 0 ? "detalhada:{$detalhadaId}" : "resumida:{$resumidaId}";

                        $aggregates[$key] ??= [
                            'id' => $detalhadaId ?: $resumidaId,
                            'detalhada_id' => $detalhadaId ?: null,
                            'resumida_id' => $resumidaId ?: null,
                            'quantidade' => 0,
                            'valor_aquisicao' => 0.0,
                            'valor_atual' => 0.0,
                        ];

                        $valorAquisicao = $this->numberOrZero($bem->ValorAquisicao);

                        $aggregates[$key]['quantidade']++;
                        $aggregates[$key]['valor_aquisicao'] += $valorAquisicao;
                        $aggregates[$key]['valor_atual'] += $bem->ValordaReavaliacao !== null
                            ? $this->numberOrZero($bem->ValordaReavaliacao)
                            : $valorAquisicao;
                    }
                });

            $topRows = collect($aggregates)
                ->sortByDesc('valor_atual')
                ->take($limit)
                ->values();

            if ($topRows->isEmpty()) {
                return collect();
            }

            $descricoesDetalhadas = DescricaoDetalhada::query()
                ->select(['id', 'descricao_resumida', 'descricao_detalhada'])
                ->with('descricao_resumida_text:id,Descricao')
                ->whereIn('id', $topRows->pluck('detalhada_id')->filter()->unique()->values())
                ->get()
                ->keyBy('id');

            $descricoesResumidas = DescricaoResumida::query()
                ->select(['id', 'Descricao'])
                ->whereIn('id', $topRows->pluck('resumida_id')->filter()->unique()->values())
                ->get()
                ->keyBy('id');

            return $topRows->map(function (array $row, int $index) use ($descricoesDetalhadas, $descricoesResumidas): array {
                $descricaoDetalhada = $row['detalhada_id']
                    ? $descricoesDetalhadas->get($row['detalhada_id'])
                    : null;

                $descricaoResumida = $row['resumida_id']
                    ? $descricoesResumidas->get($row['resumida_id'])
                    : null;

                return [
                    '__key' => $row['detalhada_id']
                        ? "detalhada:{$row['detalhada_id']}"
                        : 'resumida:' . ($row['resumida_id'] ?? $index),
                    'id' => (int) $row['id'],
                    'descricao_resumida' => $descricaoDetalhada?->descricao_resumida_text?->Descricao
                        ?: $descricaoResumida?->Descricao
                        ?: 'Sem descricao',
                    'descricao_detalhada' => $descricaoDetalhada?->descricao_detalhada ?: 'Sem descricao detalhada',
                    'quantidade' => (int) $row['quantidade'],
                    'valor_aquisicao' => (float) $row['valor_aquisicao'],
                    'valor_atual' => (float) $row['valor_atual'],
                ];
            });
        });
    }

    public function getPeriodoDescricao(?array $filters = null): string
    {
        $normalizedFilters = $this->normalizeFilters($filters);
        $startDate = $normalizedFilters['start_date'];
        $endDate = $normalizedFilters['end_date'];

        if (! $startDate && ! $endDate) {
            return 'Base consolidada';
        }

        if ($startDate && $endDate) {
            return sprintf(
                'Incorporados entre %s e %s',
                $startDate->format('d/m/Y'),
                $endDate->format('d/m/Y')
            );
        }

        if ($startDate) {
            return 'Incorporados a partir de ' . $startDate->format('d/m/Y');
        }

        return 'Incorporados ate ' . $endDate->format('d/m/Y');
    }



    protected function movableQuery(array $filters): Builder
    {
        return $this->applyMovableFilters(BemMovel::query(), $filters);
    }

    protected function applyMovableFilters(Builder $query, array $filters): Builder
    {
        return $query
            ->when(
                $filters['start_date'],
                fn(Builder $query, CarbonImmutable $date) => $query->where('DatadeIncorporacao', '>=', $date->startOfDay())
            )
            ->when(
                $filters['end_date'],
                fn(Builder $query, CarbonImmutable $date) => $query->where('DatadeIncorporacao', '<=', $date->endOfDay())
            );
    }

    protected function movableDateRange(array $filters): array
    {
        $query = $this->movableQuery($filters)
            ->whereNotNull('DatadeIncorporacao')
            ->where('DatadeIncorporacao', '>=', '1900-01-01');

        return [
            $this->parseDate((clone $query)->min('DatadeIncorporacao')),
            $this->parseDate((clone $query)->max('DatadeIncorporacao')),
        ];
    }

    protected function immovableQuery(array $filters): Builder
    {
        return $this->applyImmovableFilters(BemImovel::query(), $filters);
    }

    protected function applyImmovableFilters(Builder $query, array $filters): Builder
    {
        return $query
            ->when(
                $filters['start_date'],
                fn(Builder $query, CarbonImmutable $date) => $query->where('data_incorporacao', '>=', $date->startOfDay())
            )
            ->when(
                $filters['end_date'],
                fn(Builder $query, CarbonImmutable $date) => $query->where('data_incorporacao', '<=', $date->endOfDay())
            );
    }

    protected function normalizeFilters(?array $filters): array
    {
        $startDate = $this->parseDate($filters['start_date'] ?? null);
        $endDate = $this->parseDate($filters['end_date'] ?? null);

        if ($startDate && $endDate && $startDate->gt($endDate)) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];
    }

    protected function remember(string $key, array $filters, Closure $callback): mixed
    {
        return Cache::store('file')->remember(
            $this->dashboardCacheKey($key, $filters),
            now()->addMinutes(5),
            $callback
        );
    }

    protected function dashboardCacheKey(string $key, array $filters): string
    {
        return 'egap_dashboard:' . $key . ':' . sha1(json_encode([
            'start_date' => $filters['start_date']?->toDateString(),
            'end_date' => $filters['end_date']?->toDateString(),
        ]));
    }

    protected function parseDate(mixed $value): ?CarbonImmutable
    {
        if (blank($value)) {
            return null;
        }

        try {
            return CarbonImmutable::parse($value);
        } catch (Throwable) {
            return null;
        }
    }

    protected function numberOrZero(mixed $value): float
    {
        return is_numeric($value) ? (float) $value : 0.0;
    }
}
