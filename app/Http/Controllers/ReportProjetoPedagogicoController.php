<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Escola\Cursos;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ReportProjetoPedagogicoController extends Controller
{
    public function generate(Request $request)
    {
        $filters = $request->all();
        $reportType = $filters['report_type'] ?? null;

        return match ($reportType) {
            'cursos_ativos' => $this->reportCursosAtivos($filters),
            'cursos_por_periodo' => $this->reportCursosPorPeriodo($filters),
            'cursos_por_periodo_capacitados_mag_serv' => $this->reportCursosPorPeriodoCapacitadosMagistradosServidores($filters),
            'cursos_por_titulo' => $this->reportCursosPorTitulo($filters),
            'cursos_por_tematica' => $this->reportCursosPorTematica($filters),
            'cursos_aprovados-cargo-setor-simplificado' => $this->reportCursosAprovadosCargoSetorSimplificado($filters),
            'cursos_aprovados-cargo-setor',
            'cursos_aprovados-cargo-setor-detalhado' => $this->reportCursosAprovadosCargoSetorDetalhado($filters),
            'cursos_inscritos-cargo-setor' => $this->reportCursosInscritosCargoSetor($filters),
            'avaliacao_reacao_quantitativo' => $this->reportAvaliacaoReacaoQuantitativo($filters),
            'avaliacao_reacao_descritivo_aprovados' => $this->reportAvaliacaoReacaoDescritivoAprovados($filters),
            default => abort(404, 'Tipo de relatório não encontrado.'),
        };
    }

    private function reportCursosAtivos(array $filters)
    {
        $query = $this->getBaseQuery();
        $query->where('ativo', true);

        $this->filterByCursosIds($query, $filters);

        $data = $query->get();

        return view('reports.escola.report-cursos-ativos', compact('data', 'filters'));
    }

    private function reportCursosPorPeriodo(array $filters)
    {
        $query = $this->getBaseQuery();

        $this->filterByPeriodo($query, $filters);
        $this->filterByAtivo($query, $filters);
        $this->filterByCursosIds($query, $filters);

        $data = $query->get();

        return view('reports.escola.report-cursos-por-periodo', compact('data', 'filters'));
    }

    private function reportCursosPorPeriodoCapacitadosMagistradosServidores(array $filters)
    {
        $alunoRelations = $this->getAlunoPerfilRelations();

        $query = Cursos::with([
            'alunos' => function ($q) use ($alunoRelations) {
                $q->where('aprovado', true)->with($alunoRelations);
            },
            'atividades.alunos' => function ($q) use ($alunoRelations) {
                $q->where('aprovado', true)->with($alunoRelations);
            },
        ]);

        $this->filterByPeriodo($query, $filters);
        $this->filterByCursosIds($query, $filters);

        $cursos = $query->get();
        $todosAlunos = collect();

        foreach ($cursos as $curso) {
            foreach ($curso->alunos as $inscricao) {
                if ($inscricao->aluno) {
                    $todosAlunos->push($inscricao->aluno);
                }
            }

            foreach ($curso->atividades as $atividade) {
                foreach ($atividade->alunos as $inscricao) {
                    if ($inscricao->aluno && $inscricao->aprovado) {
                        $todosAlunos->push($inscricao->aluno);
                    }
                }
            }
        }

        $pessoasUnicas = $todosAlunos->unique('cpf');

        $pessoasAgrupadas = $pessoasUnicas->groupBy(function ($user) {
            $cargo = strtoupper(trim($user->recadastro?->cargo ?? ''));
            $funcao = strtoupper(trim($user->recadastro?->funcao ?? ''));
            $tipoVinc = strtoupper(trim($user->recadastro?->tipovinc ?? ''));

            if (
                str_contains($cargo, 'JUIZ DE DIREITO')
                || str_contains($cargo, 'DESEMBARGADOR')
                || str_contains($cargo, 'JUIZ DE DIREITO ADJUNTO')
                || $tipoVinc === 'MAGISTRADO'
            ) {
                return 'Magistrados';
            }

            if (
                !str_contains($cargo, 'JUIZ DE DIREITO')
                && !str_contains($cargo, 'DESEMBARGADOR')
                && !str_contains($cargo, 'JUIZ DE DIREITO ADJUNTO')
                && in_array($tipoVinc, [
                    'ESTABILIZADO',
                    'EFETIVO',
                    'COMISSIONADO',
                    'CONTRATO TEMPORARIO',
                    'JUIZ LEIGO',
                    'MEDI CONC',
                    'RESIDENTE JURIDICO',
                ])
            ) {
                return 'Servidores';
            }

            return 'Outros';
        });

        $magistrados = $pessoasAgrupadas->get('Magistrados', collect())->sortBy([
            ['recadastro.cargo', 'asc'],
            ['name', 'asc'],
        ]);

        $servidores = $pessoasAgrupadas->get('Servidores', collect())->sortBy([
            ['recadastro.tipovinc', 'asc'],
            ['name', 'asc'],
        ]);

        $outros = $pessoasAgrupadas->get('Outros', collect())->sortBy([
            ['recadastro.tipovinc', 'asc'],
            ['name', 'asc'],
        ]);

        return $this->renderCapacitadosPdf($magistrados, $servidores, $outros, $cursos, $filters);
    }

    private function renderCapacitadosPdf(
        Collection $magistrados,
        Collection $servidores,
        Collection $outros,
        Collection $cursos,
        array $filters,
    ) {
        $totalGeral = $magistrados->count() + $servidores->count() + $outros->count();
        $periodo = $this->formatReportPeriod($filters);

        $html = '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8">'
            . '<style>'
            . 'body{font-family:DejaVu Sans,sans-serif;font-size:10px;color:#263238;margin:24px}'
            . 'h1{font-size:18px;margin:0 0 4px;color:#123b5d}'
            . 'h2{font-size:13px;margin:20px 0 6px;border-bottom:2px solid #78909c;padding-bottom:4px}'
            . '.meta{color:#607d8b;margin-bottom:18px}'
            . '.cards{width:100%;border-collapse:separate;border-spacing:6px;margin:0 0 16px}'
            . '.card{border:1px solid #cfd8dc;padding:8px;text-align:center;background:#f5f7f8}'
            . '.card strong{display:block;font-size:16px;color:#123b5d;margin-top:3px}'
            . 'table{width:100%;border-collapse:collapse;margin-bottom:12px}'
            . 'th,td{border:1px solid #cfd8dc;padding:5px;text-align:left}'
            . 'th{background:#eceff1;color:#263238}'
            . '.center{text-align:center}.courses{margin:0 0 12px;padding-left:18px}'
            . '.empty{text-align:center;color:#607d8b}'
            . '</style></head><body>';

        $html .= '<h1>Relatório de Pessoas Capacitadas</h1>'
            . '<div class="meta">Capacitados por período — Magistrados, Servidores e Outros<br>'
            . 'Período: ' . $this->escapeReportValue($periodo) . '</div>';

        $html .= '<table class="cards"><tr>'
            . $this->renderReportCard('Cursos ofertados', $cursos->count())
            . $this->renderReportCard('Total capacitados', $totalGeral)
            . $this->renderReportCard('Magistrados', $magistrados->count())
            . $this->renderReportCard('Servidores', $servidores->count())
            . $this->renderReportCard('Outros', $outros->count())
            . '</tr></table>';

        $html .= '<h2>Relação de cursos ofertados</h2><ul class="courses">';
        if ($cursos->isEmpty()) {
            $html .= '<li>Nenhum curso ofertado neste período.</li>';
        } else {
            foreach ($cursos as $curso) {
                $html .= '<li>' . $this->escapeReportValue(Str::upper($curso->titulo)) . '</li>';
            }
        }
        $html .= '</ul>';

        $html .= $this->renderCapacitadosTable(
            'Lista de magistrados',
            $magistrados,
            'MAGISTRADO',
            'Nenhum magistrado capacitado neste período.'
        );
        $html .= $this->renderCapacitadosTable(
            'Lista de servidores',
            $servidores,
            null,
            'Nenhum servidor capacitado neste período.'
        );
        $html .= $this->renderCapacitadosTable(
            'Lista de outros capacitados',
            $outros,
            null,
            'Nenhuma outra pessoa capacitada neste período.'
        );

        $html .= '<div style="margin-top:18px;color:#607d8b">Gerado em '
            . $this->escapeReportValue(now()->format('d/m/Y H:i')) . '</div></body></html>';

        return Pdf::loadHTML($html)
            ->setPaper('a4', 'landscape')
            ->stream('relatorio-capacitados-magistrados-servidores.pdf');
    }

    private function renderReportCard(string $label, int $value): string
    {
        return '<td class="card">' . $this->escapeReportValue($label)
            . '<strong>' . $value . '</strong></td>';
    }

    private function renderCapacitadosTable(
        string $title,
        Collection $users,
        ?string $defaultType,
        string $emptyMessage,
    ): string {
        $html = '<h2>' . $this->escapeReportValue($title) . '</h2>'
            . '<table><thead><tr>'
            . '<th class="center">CPF</th><th>Nome completo</th><th class="center">Tipo</th>'
            . '<th>Cargo/Função</th><th>Setor</th></tr></thead><tbody>';

        if ($users->isEmpty()) {
            return $html . '<tr><td colspan="5" class="empty">'
                . $this->escapeReportValue($emptyMessage) . '</td></tr></tbody></table>';
        }

        foreach ($users as $user) {
            $recadastro = $user->recadastro;
            $tipo = $defaultType ?? ($recadastro?->tipovinc ?: 'NÃO INFORMADO');
            $cargo = $recadastro?->cargo ?: ($recadastro?->funcao ?: 'NÃO INFORMADO');
            $setor = $recadastro?->nomesetor ?: 'SETOR NÃO INFORMADO';

            $html .= '<tr><td class="center">' . $this->escapeReportValue($user->cpf)
                . '</td><td>' . $this->escapeReportValue(Str::upper($user->name))
                . '</td><td class="center">' . $this->escapeReportValue($tipo)
                . '</td><td>' . $this->escapeReportValue($cargo)
                . '</td><td>' . $this->escapeReportValue($setor) . '</td></tr>';
        }

        return $html . '</tbody></table>';
    }

    private function formatReportPeriod(array $filters): string
    {
        if (empty($filters['data_inicio']) || empty($filters['data_fim'])) {
            return 'Todos os períodos';
        }

        return Carbon::parse($filters['data_inicio'])->format('d/m/Y')
            . ' a ' . Carbon::parse($filters['data_fim'])->format('d/m/Y');
    }

    private function escapeReportValue(mixed $value): string
    {
        return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function reportCursosPorTitulo(array $filters)
    {
        $query = $this->getBaseQuery();

        $this->filterByAtivo($query, $filters);
        $this->filterByCursosIds($query, $filters);

        $data = $query->get();

        return view('reports.escola.report-cursos-por-titulo', compact('data', 'filters'));
    }

    private function reportCursosPorTematica(array $filters)
    {
        $query = $this->getBaseQuery();

        $this->filterByPeriodo($query, $filters);
        $this->filterByAtivo($query, $filters);
        $this->filterByCursosIds($query, $filters);

        $data = $query->get();

        return view('reports.escola.report-cursos-por-tematica', compact('data', 'filters'));
    }

    private function reportCursosAprovadosCargoSetorSimplificado(array $filters)
    {
        $query = $this->getCursosAprovadosQuery();

        $this->filterByPeriodo($query, $filters);
        $this->filterByCursosIds($query, $filters);

        $data = $query->get();

        return view('reports.escola.report-cursos-aprovados-cargo-setor-simplificado', compact('data', 'filters'));
    }

    private function reportCursosAprovadosCargoSetorDetalhado(array $filters)
    {
        $query = $this->getCursosAprovadosQuery();

        $this->filterByPeriodo($query, $filters);
        $this->filterByCursosIds($query, $filters);

        $data = $query->get();

        return view('reports.escola.report-cursos-aprovados-cargo-setor-detalhado', compact('data', 'filters'));
    }

    private function reportCursosInscritosCargoSetor(array $filters)
    {
        $query = $this->getBaseQuery();

        $this->filterByPeriodo($query, $filters);
        $this->filterByCursosIds($query, $filters);

        $data = $query->get();

        return view('reports.escola.report-cursos-inscritos-cargo-setor', compact('data', 'filters'));
    }

    private function reportAvaliacaoReacaoQuantitativo(array $filters)
    {
        $query = $this->getAvaliacaoQuery();

        $this->filterByPeriodo($query, $filters);
        $this->filterByCursosIds($query, $filters);

        $data = $this->buildAvaliacaoQuantitativaData($query->get());

        return view('reports.escola.report-avaliacao-reacao-quantitativo', compact('data', 'filters'));
    }

    private function reportAvaliacaoReacaoDescritivoAprovados(array $filters)
    {
        $query = $this->getAvaliacaoQuery(true);

        $this->filterByPeriodo($query, $filters);
        $this->filterByCursosIds($query, $filters);

        $data = $this->buildAvaliacaoDescritivaData($query->get());

        return view('reports.escola.report-avaliacao-reacao-descritivo-aprovados', compact('data', 'filters'));
    }

    private function getBaseQuery()
    {
        $alunoRelations = $this->getAlunoPerfilRelations();

        return Cursos::with([
            'alunos' => fn($q) => $q->with($alunoRelations),
            'frequencias',
            'atividades.alunos' => fn($q) => $q->with($alunoRelations),
        ]);
    }

    private function getCursosAprovadosQuery()
    {
        $alunoRelations = $this->getAlunoPerfilRelations();

        return Cursos::with([
            'alunos' => function ($q) use ($alunoRelations) {
                $q->where('aprovado', true)->with($alunoRelations);
            },
            'frequencias',
            'atividades.alunos' => function ($q) use ($alunoRelations) {
                $q->where('aprovado', true)->with($alunoRelations);
            },
        ]);
    }

    private function getAvaliacaoQuery(bool $withAlunosAprovados = false)
    {
        $with = [
            'avaliacao' => fn($q) => $q->with(['values.field', 'form']),
        ];

        if ($withAlunosAprovados) {
            $alunoRelations = $this->getAlunoPerfilRelations();

            $with['alunos'] = function ($q) use ($alunoRelations) {
                $q->where('aprovado', true)->with($alunoRelations);
            };

            $with['atividades.alunos'] = function ($q) use ($alunoRelations) {
                $q->where('aprovado', true)->with($alunoRelations);
            };
        }

        return Cursos::with($with);
    }

    private function getAlunoPerfilRelations(): array
    {
        return [
            'aluno.recadastro',
            'aluno.setorPrincipal',
            'aluno.setores',
            'aluno.perfil.tipoPessoa',
            'aluno.perfil.tipoSexo',
            'aluno.perfil.tipoRaca',
        ];
    }

    private function buildAvaliacaoQuantitativaData(Collection $cursos): Collection
    {
        return $cursos
            ->map(fn($curso) => $this->summarizeAvaliacao($curso, null, $curso->avaliacao ?? collect()))
            ->values();
    }

    private function summarizeAvaliacao($curso, $atividade, Collection $submissions): array
    {
        $aspectos = $this->summarizeSubmissionFields($submissions);
        $scores = $aspectos->reduce(function ($carry, array $aspecto) {
            $carry['sum'] += $aspecto['score_sum'];
            $carry['count'] += $aspecto['score_count'];
            $carry['boas'] += $aspecto['boas'];
            $carry['ruins'] += $aspecto['ruins'];

            return $carry;
        }, ['sum' => 0, 'count' => 0, 'boas' => 0, 'ruins' => 0]);

        return [
            'curso' => $curso,
            'atividade' => $atividade,
            'total_avaliacoes' => $submissions->count(),
            'media_geral' => $this->formatMedia($scores['sum'], $scores['count']),
            'boas' => $submissions->filter(fn($submission) => ($this->submissionAverageScore($submission) ?? 0) >= 4)->count(),
            'ruins' => $submissions->filter(fn($submission) => ($this->submissionAverageScore($submission) ?? 99) <= 2)->count(),
            'respostas_boas' => $scores['boas'],
            'respostas_ruins' => $scores['ruins'],
            'aspectos' => $aspectos,
        ];
    }

    private function submissionAverageScore($submission): ?float
    {
        $scores = $submission->values
            ->map(fn($value) => $this->scoreAvaliacaoValue($value->value))
            ->filter(fn($score) => $score !== null);

        return $scores->isNotEmpty() ? $scores->avg() : null;
    }

    private function summarizeSubmissionFields(Collection $submissions): Collection
    {
        $aspectos = [];

        foreach ($submissions as $submission) {
            foreach ($submission->values as $value) {
                $field = $value->field;
                $key = $field?->id ?? 'campo_' . $value->form_field_id;
                $resposta = $this->cleanText($value->value) ?? 'Sem resposta';
                $score = $this->scoreAvaliacaoValue($resposta);

                if (!isset($aspectos[$key])) {
                    $aspectos[$key] = [
                        'label' => $field?->label ?? 'Pergunta não identificada',
                        'type' => $field?->type ?? null,
                        'order' => $field?->order ?? 9999,
                        'total_respostas' => 0,
                        'score_sum' => 0,
                        'score_count' => 0,
                        'boas' => 0,
                        'ruins' => 0,
                        'distribuicao' => [],
                    ];
                }

                $aspectos[$key]['total_respostas']++;
                $aspectos[$key]['distribuicao'][$resposta] = ($aspectos[$key]['distribuicao'][$resposta] ?? 0) + 1;

                if ($score !== null) {
                    $aspectos[$key]['score_sum'] += $score;
                    $aspectos[$key]['score_count']++;

                    if ($score >= 4) {
                        $aspectos[$key]['boas']++;
                    } elseif ($score <= 2) {
                        $aspectos[$key]['ruins']++;
                    }
                }
            }
        }

        return collect($aspectos)
            ->map(function (array $aspecto) {
                arsort($aspecto['distribuicao']);
                $aspecto['media'] = $this->formatMedia($aspecto['score_sum'], $aspecto['score_count']);

                return $aspecto;
            })
            ->sortBy(fn(array $aspecto) => str_pad((string) $aspecto['order'], 6, '0', STR_PAD_LEFT) . $aspecto['label'])
            ->values();
    }

    private function buildAvaliacaoDescritivaData(Collection $cursos): Collection
    {
        $rows = collect();

        foreach ($cursos as $curso) {
            $inscricoesAprovadas = collect($curso->alunos ?? collect())
                ->merge(
                    collect($curso->atividades ?? collect())
                        ->flatMap(fn($atividade) => $atividade->alunos ?? collect())
                )
                ->unique('aluno_id')
                ->values();

            $rows = $rows->merge($this->buildAvaliacaoDescritivaRows($curso, null, $inscricoesAprovadas, $curso->avaliacao ?? collect()));
        }

        return $rows->values();
    }

    private function buildAvaliacaoDescritivaRows($curso, $atividade, Collection $inscricoes, Collection $submissions): Collection
    {
        $rows = collect();
        $submissionsByUser = $submissions->groupBy('user_id');

        foreach ($inscricoes as $inscricao) {
            $avaliacoes = $submissionsByUser->get($inscricao->aluno_id, collect());

            foreach ($avaliacoes as $submission) {
                $respostas = $this->formatSubmissionAnswers($submission);
                $scoreSum = $respostas->sum(fn(array $resposta) => $resposta['score'] ?? 0);
                $scoreCount = $respostas->filter(fn(array $resposta) => $resposta['score'] !== null)->count();

                $rows->push([
                    'curso' => $curso,
                    'atividade' => $atividade,
                    'inscricao' => $inscricao,
                    'aluno' => $inscricao->aluno,
                    'submission' => $submission,
                    'respostas' => $respostas,
                    'media' => $this->formatMedia($scoreSum, $scoreCount),
                ]);
            }
        }

        return $rows;
    }

    private function formatSubmissionAnswers($submission): Collection
    {
        return $submission->values
            ->sortBy(fn($value) => $value->field?->order ?? 9999)
            ->map(function ($value) {
                $resposta = $this->cleanText($value->value) ?? 'Sem resposta';

                return [
                    'pergunta' => $value->field?->label ?? 'Pergunta não identificada',
                    'tipo' => $value->field?->type ?? null,
                    'resposta' => $resposta,
                    'score' => $this->scoreAvaliacaoValue($resposta),
                ];
            })
            ->values();
    }

    private function scoreAvaliacaoValue(mixed $value): ?float
    {
        $value = $this->cleanText($value);

        if ($value === null) {
            return null;
        }

        $numeric = str_replace(',', '.', $value);

        if (is_numeric($numeric)) {
            return (float) $numeric;
        }

        $key = Str::of($value)->lower()->ascii()->squish()->toString();

        return [
            'excelente' => 5,
            'otimo' => 5,
            'bom' => 4,
            'regular' => 3,
            'parcialmente' => 3,
            'ruim' => 2,
            'pessimo' => 1,
            'totalmente' => 5,
            'sim' => 5,
            'nao' => 1,
            'sem resposta' => null,
            'nao se aplica' => null,
            'n/a' => null,
        ][$key] ?? null;
    }

    private function formatMedia(float|int $sum, int $count): string
    {
        return $count > 0 ? number_format($sum / $count, 2, ',', '.') : 'N/A';
    }

    private function cleanText(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value)) {
            $value = implode(', ', $value);
        }

        $value = html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = trim(preg_replace('/\s+/', ' ', strip_tags($value)));

        return $value !== '' ? $value : null;
    }

    private function filterByPeriodo($query, array $filters)
    {
        if (!empty($filters['data_inicio']) && !empty($filters['data_fim'])) {
            $query->whereDate('inicio', '>=', Carbon::parse($filters['data_inicio']))
                ->whereDate('termino', '<=', Carbon::parse($filters['data_fim']));
        }
    }

    private function filterByAtivo($query, array $filters)
    {
        if (isset($filters['ativo']) && $filters['ativo'] !== null) {
            $query->where('ativo', $filters['ativo']);
        }
    }

    private function filterByCursosIds($query, array $filters)
    {
        if (!empty($filters['cursos_ids'])) {
            $ids = is_array($filters['cursos_ids']) ? $filters['cursos_ids'] : [$filters['cursos_ids']];
            $query->whereIn('id', $ids);
        }
    }
}
