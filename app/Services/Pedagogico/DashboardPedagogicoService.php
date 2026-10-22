<?php

namespace App\Services\Pedagogico;

use App\Models\Escola\CursoAtividades;
use App\Models\Escola\Cursos;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Calculation\Logical\Boolean;

class DashboardPedagogicoService
{
    public function resumo(?array $filters = null): array
    {
        [$inicio, $termino] = $this->periodo($filters);
        $cursoIds = $this->cursoIdsNoPeriodo($inicio, $termino);
        $eventoIds = $this->idsCursoEAtividades($cursoIds);
        $inscricoes = $this->queryInscricoes($eventoIds);
        $totalAvaliaveis = (clone $inscricoes)->count();
        $aprovados = (clone $inscricoes)->where('aprovado', true)->count();
        $reprovados = max($totalAvaliaveis - $aprovados, 0);
        $meses = max($this->quantidadeMeses($inicio, $termino), 1);
        $eventosEspeciais = $this->cursoIdsComAgendasEspeciais($inicio, $termino);

        return [
            'inicio' => $inicio,
            'termino' => $termino,
            'meses' => $meses,
            'acoes' => $cursoIds->count(),
            'acoes_ativas' => $this->cursoIdsNoPeriodo($inicio, $termino, true)->count(),
            'media_acoes_mensal' => $cursoIds->count() / $meses,
            'carga_horaria' => (float) DB::table('esc_cursos')->whereIn('id', $cursoIds)->sum('carga_horaria'),
            'participantes' => $totalAvaliaveis,
            'dias_com_atividades' => $this->diasComAtividades($inicio, $termino, $cursoIds),
            'eventos_especiais' => $eventosEspeciais->count(),
            'aprovados' => $aprovados,
            'reprovados' => $reprovados,
            'taxa_aprovacao' => $totalAvaliaveis > 0 ? ($aprovados / $totalAvaliaveis) * 100 : 0,
            'taxa_reprovacao' => $totalAvaliaveis > 0 ? ($reprovados / $totalAvaliaveis) * 100 : 0,
        ];
    }

    public function seriesMensais(?array $filters = null): array
    {
        [$inicio, $termino] = $this->periodo($filters);
        $labels = [];
        $acoes = [];
        $participantes = [];
        $cargaHoraria = [];
        $diasComAtividades = [];
        $eventosEspeciais = [];
        $aprovados = [];
        $reprovados = [];

        foreach ($this->mesesDoPeriodo($inicio, $termino) as $mes) {
            $mesInicio = $mes->copy()->startOfMonth()->max($inicio);
            $mesTermino = $mes->copy()->endOfMonth()->min($termino);
            $resumo = $this->resumo([
                'inicio' => $mesInicio->toDateString(),
                'termino' => $mesTermino->toDateString(),
            ]);

            $labels[] = $mes->format('m/Y');
            $acoes[] = $resumo['acoes'];
            $participantes[] = $resumo['participantes'];
            $cargaHoraria[] = round($resumo['carga_horaria'], 2);
            $diasComAtividades[] = $resumo['dias_com_atividades'];
            $eventosEspeciais[] = $resumo['eventos_especiais'];
            $aprovados[] = $resumo['aprovados'];
            $reprovados[] = $resumo['reprovados'];
        }

        return [
            'labels' => $labels,
            'acoes' => $acoes,
            'participantes' => $participantes,
            'carga_horaria' => $cargaHoraria,
            'dias_com_atividades' => $diasComAtividades,
            'eventos_especiais' => $eventosEspeciais,
            'aprovados' => $aprovados,
            'reprovados' => $reprovados,
        ];
    }

    public function tabelaResumo(?array $filters = null): array
    {
        [$inicio, $termino] = $this->periodo($filters);
        $anos = range((int) $inicio->format('Y'), (int) $termino->format('Y'));
        $resumosPorAno = [];

        foreach ($anos as $ano) {
            $anoInicio = Carbon::create($ano, 1, 1)->startOfDay()->max($inicio);
            $anoTermino = Carbon::create($ano, 12, 31)->endOfDay()->min($termino);

            if ($anoInicio->greaterThan($anoTermino)) {
                continue;
            }

            $resumosPorAno[$ano] = $this->resumo([
                'inicio' => $anoInicio->toDateString(),
                'termino' => $anoTermino->toDateString(),
            ]);
        }

        $total = $this->resumo($filters);
        $linhas = [
            [
                'label' => 'Acoes - Quantidade de cursos, eventos, seminarios, congressos e outras acoes realizadas',
                'key' => 'acoes',
                'decimals' => 0,
            ],
            [
                'label' => 'Carga horaria total dessas acoes',
                'key' => 'carga_horaria',
                'decimals' => 2,
            ],
            [
                'label' => 'Numero de participantes',
                'key' => 'participantes',
                'decimals' => 0,
            ],
            [
                'label' => 'Quantidade de dias com atividades',
                'key' => 'dias_com_atividades',
                'decimals' => 0,
            ],
            [
                'label' => 'Eventos realizados fora do horario normal, a noite, finais de semana ou feriados',
                'key' => 'eventos_especiais',
                'decimals' => 0,
            ],
        ];

        return [
            'anos' => array_keys($resumosPorAno),
            'linhas' => collect($linhas)
                ->map(function (array $linha) use ($resumosPorAno, $total): array {
                    $linha['anos'] = collect($resumosPorAno)
                        ->map(fn(array $resumo): float|int => $resumo[$linha['key']] ?? 0)
                        ->all();
                    $linha['media_mensal'] = ($total[$linha['key']] ?? 0) / max($total['meses'], 1);
                    $linha['total'] = $total[$linha['key']] ?? 0;

                    return $linha;
                })
                ->all(),
        ];
    }

    public function periodo(?array $filters = null): array
    {
        $inicio = Carbon::parse($filters['inicio'] ?? now()->startOfYear()->toDateString())->startOfDay();
        $termino = Carbon::parse($filters['termino'] ?? now()->toDateString())->endOfDay();

        if ($inicio->greaterThan($termino)) {
            return [$termino->copy()->startOfDay(), $inicio->copy()->endOfDay()];
        }

        return [$inicio, $termino];
    }

    public function aplicarPeriodoCursos($query, Carbon $inicio, Carbon $termino)
    {
        return $query
            ->whereDate('inicio', '>=', $inicio->toDateString())
            ->whereDate('termino', '<=', $termino->toDateString());
    }

    public function cursoIdsNoPeriodo(Carbon $inicio, Carbon $termino, bool $ativo = false): Collection
    {
        if ($ativo) {
            return $this->aplicarPeriodoCursos(
                DB::table('esc_cursos')->whereNull('curso_id')->where('ativo', true),
                $inicio,
                $termino
            )
                ->pluck('id')
                ->map(fn(mixed $id): int => (int) $id)
                ->values();
        }
        return $this->aplicarPeriodoCursos(
            DB::table('esc_cursos')->whereNull('curso_id'),
            $inicio,
            $termino
        )
            ->pluck('id')
            ->map(fn(mixed $id): int => (int) $id)
            ->values();
    }

    public function idsCursoEAtividades(Collection $cursoIds): Collection
    {
        if ($cursoIds->isEmpty()) {
            return collect();
        }

        return DB::table('esc_cursos')
            ->whereIn('id', $cursoIds)
            ->orWhereIn('curso_id', $cursoIds)
            ->pluck('id')
            ->map(fn(mixed $id): int => (int) $id)
            ->values();
    }

    protected function queryInscricoes(Collection $eventoIds)
    {
        return DB::table('esc_cursoalunos')
            ->whereIn('curso_id', $eventoIds)
            ->where('situacaoinscricao_id', config('settings.inscricao.inscrito'));
    }

    protected function diasComAtividades(Carbon $inicio, Carbon $termino, Collection $cursoIds): int
    {
        $eventoIds = $this->idsCursoEAtividades($cursoIds);

        if ($eventoIds->isEmpty()) {
            return 0;
        }

        $datas = collect();

        DB::table('adm_agenda')
            ->whereIn('agendaable_id', $eventoIds)
            ->whereIn('agendaable_type', [Cursos::class, CursoAtividades::class])
            ->whereDate('inicio', '>=', $inicio->toDateString())
            ->whereDate('termino', '<=', $termino->toDateString())
            ->get(['inicio', 'termino'])
            ->each(function (object $agenda) use ($datas): void {
                foreach ($this->diasDaAgenda(Carbon::parse($agenda->inicio), Carbon::parse($agenda->termino)) as $dia) {
                    $datas->push($dia->toDateString());
                }
            });

        return $datas->unique()->count();
    }


    protected function cursoIdsComAgendasEspeciais(Carbon $inicio, Carbon $termino): Collection
    {
        $feriados = $this->feriados();

        return DB::table('adm_agenda as a')
            ->join('esc_cursos as e', 'e.id', '=', 'a.agendaable_id')
            ->whereIn('a.agendaable_type', [Cursos::class, CursoAtividades::class])
            ->whereDate('a.inicio', '>=', $inicio->toDateString())
            ->whereDate('a.termino', '<=', $termino->toDateString())
            ->get([
                'a.inicio',
                'a.termino',
                DB::raw('COALESCE(e.curso_id, e.id) as curso_id'),
            ])
            ->filter(fn(object $agenda): bool => $this->agendaEspecial($agenda, $feriados))
            ->pluck('curso_id')
            ->map(fn(mixed $id): int => (int) $id)
            ->unique()
            ->values();
    }

    protected function agendaEspecial(object $agenda, array $feriados): bool
    {
        $inicio = Carbon::parse($agenda->inicio);
        $termino = Carbon::parse($agenda->termino);

        if ($inicio->format('H:i:s') > '18:00:00' || $termino->format('H:i:s') > '18:00:00') {
            return true;
        }

        foreach ($this->diasDaAgenda($inicio, $termino) as $dia) {
            if ($dia->isWeekend() || isset($feriados[$dia->toDateString()])) {
                return true;
            }
        }

        return false;
    }

    protected function feriados(): array
    {
        $csv = base_path('feriados_vitoria_es_2025_ate_2026-08-14.csv');

        if (! is_file($csv)) {
            return [];
        }

        $handle = fopen($csv, 'rb');

        if ($handle === false) {
            return [];
        }

        $feriados = [];
        fgetcsv($handle, 0, ';');

        while (($linha = fgetcsv($handle, 0, ';')) !== false) {
            $data = trim((string) ($linha[0] ?? ''));

            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
                $feriados[$data] = true;
            }
        }

        fclose($handle);

        return $feriados;
    }

    protected function diasDaAgenda(Carbon $inicio, Carbon $termino): CarbonPeriod
    {
        return CarbonPeriod::create($inicio->copy()->startOfDay(), $termino->copy()->startOfDay());
    }

    protected function mesesDoPeriodo(Carbon $inicio, Carbon $termino): CarbonPeriod
    {
        return CarbonPeriod::create($inicio->copy()->startOfMonth(), '1 month', $termino->copy()->startOfMonth());
    }

    protected function quantidadeMeses(Carbon $inicio, Carbon $termino): int
    {
        return collect($this->mesesDoPeriodo($inicio, $termino))->count();
    }
}
