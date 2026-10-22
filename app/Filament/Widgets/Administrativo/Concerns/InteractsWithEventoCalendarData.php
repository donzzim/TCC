<?php

namespace App\Filament\Widgets\Administrativo\Concerns;

use App\Models\Administrativo\Agenda;
use App\Models\Escola\Acao;
use App\Models\Escola\CursoAtividades;
use App\Models\Escola\Cursos;
use App\Models\Escola\TipoLocal;
use Carbon\Carbon;

use Illuminate\Support\Facades\Cache;

use Illuminate\Support\Facades\Session;


trait InteractsWithEventoCalendarData
{
    public function loadEvents(bool $force = false): void
    {
        $this->agendaCronogramaCache = [];
        $this->calendarEventsByDate = [];
        $this->listEventDays = [];
        $this->realocacaoAgendaablesCache = [];
        $this->realocacaoAgendasCache = [];
        $this->setCalendarMetadata();

        $start = $this->currentMonth->copy()->startOfMonth()->startOfDay();
        $end = $this->currentMonth->copy()->endOfMonth()->endOfDay();
        $cacheKey = $this->getEventosCacheKey();

        if ($force) {
            Cache::store('file')->forget($cacheKey);
        }

        $this->events = Cache::store('file')->remember(
            $cacheKey,
            now()->addSeconds(self::EVENTOS_CACHE_TTL_SECONDS),
            fn(): array => $this->buscarEventosDoPeriodo($start, $end),
        );
        $this->eventsLoaded = true;
    }

    protected function buscarEventosDoPeriodo(Carbon $start, Carbon $end): array
    {
        $eventos = Agenda::query()
            ->with('agendaable')
            ->where('inicio', '<=', $end)
            ->where('termino', '>=', $start)
            ->orderBy('inicio')
            ->get();

        $eventos->loadMorph('agendaable', [
            Cursos::class => ['tipoLocal', 'userInstrutores'],
            CursoAtividades::class => ['curso.tipoLocal', 'curso.userInstrutores', 'tipoLocal', 'instrutores'],
        ]);

        $acaoIds = $eventos
            ->filter(fn($evento) => $evento->agendaable instanceof Acao)
            ->pluck('agendaable_id')
            ->filter()
            ->map(fn($id) => (string) $id)
            ->unique()
            ->values();

        if ($acaoIds->isNotEmpty()) {
            $acaoIdsComCurso = Cursos::query()
                ->whereIn('acao_id', $acaoIds)
                ->pluck('acao_id')
                ->filter()
                ->map(fn($id) => (string) $id)
                ->unique()
                ->flip();

            $eventos = $eventos
                ->reject(fn($evento) => $evento->agendaable instanceof Acao
                    && $acaoIdsComCurso->has((string) $evento->agendaable_id))
                ->values();
        }

        return $eventos->map(function ($evento) {
            $descricao = null;
            $instrutores = null;
            $local = null;

            $agendaable = $evento->agendaable;
            $category = match (true) {
                $agendaable instanceof CursoAtividades => 'atividade',
                $agendaable instanceof Cursos => 'curso',
                $agendaable instanceof Acao => 'acao',
                default => 'evento',
            };

            $cursoPai = $agendaable instanceof CursoAtividades ? $agendaable->curso : null;
            $titulo = isset($agendaable->titulo) ? $agendaable->titulo : $evento->titulo;
            $tituloLista = $titulo;
            $categoriaLista = $category;
            $agendaableListaType = $evento->agendaable_type;
            $agendaableListaId = $evento->agendaable_id;
            $tituloAtividade = null;

            if ($evento->agendaable) {
                if ($evento->agendaable_type === 'App\Models\Escola\Cursos') {
                    $instrutores = $evento->agendaable->userInstrutores->pluck('name')->join(', ');
                    $local = $evento->agendaable->tipoLocal->descricao ?? 'A definir';
                }
                // Adicione lógica para CursoAtividades se necessário
            }

            if ($agendaable instanceof CursoAtividades) {
                $tituloAtividade = $agendaable->titulo ?: $evento->descricao;
                $titulo = $this->juntarPartesCronograma([$cursoPai?->titulo, $tituloAtividade], ' - ');
                $tituloLista = $cursoPai?->titulo ?: $titulo;
                $categoriaLista = 'curso';
                $agendaableListaType = Cursos::class;
                $agendaableListaId = $cursoPai?->id ?? $agendaable->curso_id;
                $instrutores = $agendaable->instrutores->pluck('name')->join(', ')
                    ?: $cursoPai?->userInstrutores?->pluck('name')->join(', ');
                $local = $agendaable->tipoLocal->descricao
                    ?? $cursoPai?->tipoLocal?->descricao
                    ?? 'A definir';
            }

            if ($agendaable instanceof Acao) {
                $metadados = $this->normalizarArray($agendaable->metadados ?? []);
                $titulo = $this->getTituloAcaoCronograma($agendaable);
                $tituloLista = $titulo;
                $instrutores = $this->formatarInstrutoresMetadados($metadados['instrutores'] ?? []) ?: 'A definir';
                $local = $this->getLocalAcaoCronograma($agendaable);
                $descricao = $this->richTextParaResumo($metadados['informacoes_adicionais'] ?? '') ?: $evento->descricao;
            }

            $registroInline = $cursoPai ?: $agendaable;
            $metadadosInline = $registroInline instanceof Acao
                ? $this->normalizarArray($registroInline->metadados ?? [])
                : [];
            $inlineLocal = $this->getInlineLocalEventoLista($evento, $registroInline, $metadadosInline);

            $inicio = Carbon::parse($evento->inicio);
            $termino = Carbon::parse($evento->termino);
            $diaTodo = (bool) $evento->dia_todo;

            return [
                'id' => $evento->id,
                'title' => $titulo,
                'description' => $descricao ?? $evento->descricao,
                'location' => $local ?? 'Remoto/Presencial',
                'instructors' => $instrutores ?? 'Não informado',
                'start_datetime' => $inicio->toDateTimeString(),
                'end_datetime' => $termino->toDateTimeString(),
                'start_date' => $inicio->toDateString(),
                'end_date' => $termino->toDateString(),
                'start_time' => $inicio->format('H:i'),
                'end_time' => $termino->format('H:i'),
                'start' => $inicio->toDateString(),
                'end' => $termino->toDateString(),
                'tipo' => $evento->tipo,
                'category' => $category,
                'is_all_day' => $diaTodo,
                'agendaable_type' => $evento->agendaable_type,
                'agendaable_id' => $evento->agendaable_id,
                'list_title' => $tituloLista,
                'list_category' => $categoriaLista,
                'list_agendaable_type' => $agendaableListaType,
                'list_agendaable_id' => $agendaableListaId,
                'inline_title' => $this->getTituloInlineEventoLista($evento, $registroInline),
                'inline_instructors' => $this->getInlineInstrutoresEventoLista($registroInline, $metadadosInline),
                'inline_local_items' => $inlineLocal ? [$inlineLocal] : [],
                'activity_title' => $tituloAtividade,
                'parent_title' => $cursoPai?->titulo,
            ];
        })->toArray();
    }

    protected function setCalendarMetadata(): void
    {
        $this->currentMonth = $this->resolveCurrentMonth();
        $this->daysInMonth = $this->currentMonth->daysInMonth;
        $this->monthName = $this->currentMonth->translatedFormat('F Y');
        $this->firstDayOfWeek = $this->currentMonth->copy()->startOfMonth()->dayOfWeek;
    }

    protected function resolveCurrentMonth(): Carbon
    {
        if ($this->currentMonth instanceof Carbon) {
            return $this->currentMonth->copy()->startOfMonth();
        }

        return Carbon::parse($this->currentMonth ?: now())->startOfMonth();
    }

    protected function getEventosCacheKey(): string
    {
        return 'evento-calendar-widget:events:' . Session::get('setorprincipal', 'sem-setor') . ':' . $this->resolveCurrentMonth()->format('Y-m');
    }

    protected function getEventsForRender(): array
    {
        if (! $this->eventsLoaded) {
            $this->loadEvents();
        }

        return $this->events;
    }

    protected function getCalendarEventsByDate(): array
    {
        if (! empty($this->calendarEventsByDate)) {
            return $this->calendarEventsByDate;
        }

        $days = [];
        $monthStart = $this->currentMonth->copy()->startOfMonth()->startOfDay();
        $monthEnd = $this->currentMonth->copy()->endOfMonth()->startOfDay();

        for ($day = 1; $day <= $this->daysInMonth; $day++) {
            $date = $this->currentMonth->copy()->day($day)->toDateString();
            $days[$date] = [];
        }

        foreach ($this->getEventsForRender() as $event) {
            $eventStartValue = $event['start'] ?? $event['start_date'] ?? null;

            if (! $eventStartValue) {
                continue;
            }

            $eventStart = Carbon::parse($eventStartValue)->startOfDay();
            $eventEnd = Carbon::parse($event['end'] ?? $event['end_date'] ?? $eventStartValue)->startOfDay();

            if ($eventEnd->lessThan($monthStart) || $eventStart->greaterThan($monthEnd)) {
                continue;
            }

            $date = $eventStart->greaterThan($monthStart)
                ? $eventStart->copy()
                : $monthStart->copy();
            $limit = $eventEnd->lessThan($monthEnd)
                ? $eventEnd->copy()
                : $monthEnd->copy();

            while ($date->lessThanOrEqualTo($limit)) {
                $days[$date->toDateString()][] = $event;
                $date->addDay();
            }
        }

        return $this->calendarEventsByDate = $days;
    }

    protected function getListEventDays(): array
    {
        if (! empty($this->listEventDays)) {
            return $this->listEventDays;
        }

        $days = [];
        $monthStart = $this->currentMonth->copy()->startOfMonth()->startOfDay();
        $monthEnd = $this->currentMonth->copy()->endOfMonth()->startOfDay();

        foreach ($this->agruparEventosLista($this->getEventsForRender()) as $event) {
            $category = $event['category'] ?? 'evento';
            $eventStartValue = $event['start'] ?? $event['start_date'] ?? null;

            if (! $eventStartValue) {
                continue;
            }

            $start = Carbon::parse($eventStartValue)->startOfDay();
            $end = Carbon::parse($event['end'] ?? $event['end_date'] ?? $eventStartValue)->startOfDay();

            if ($end->lessThan($monthStart) || $start->greaterThan($monthEnd)) {
                continue;
            }

            if (in_array($category, ['atividade', 'curso', 'acao'], true)) {
                $date = $start->toDateString();
                $days[$date][] = $event;
                continue;
            }

            $date = $start->greaterThan($monthStart) ? $start->copy() : $monthStart->copy();
            $limit = $end->lessThan($monthEnd) ? $end->copy() : $monthEnd->copy();

            while ($date->lessThanOrEqualTo($limit)) {
                $days[$date->toDateString()][] = $event;
                $date->addDay();
            }
        }

        ksort($days);

        return $this->listEventDays = collect($days)
            ->map(function (array $events, string $date): array {
                usort(
                    $events,
                    fn(array $a, array $b): int => ($a['start_datetime'] ?? (($a['start'] ?? '') . ' ' . ($a['start_time'] ?? '00:00')))
                        <=> ($b['start_datetime'] ?? (($b['start'] ?? '') . ' ' . ($b['start_time'] ?? '00:00')))
                );

                $carbonDate = Carbon::parse($date);

                return [
                    'date' => $date,
                    'day' => $carbonDate->day,
                    'weekday' => $carbonDate->translatedFormat('D'),
                    'events' => $events,
                ];
            })
            ->values()
            ->toArray();
    }

    public function getListEvents(): array
    {
        return $this->agruparEventosLista($this->getEventsForRender());
    }

    /**
     * Agrupa os eventos do cronograma para a listagem.
     */
    protected function agruparEventosLista(array $eventos): array
    {
        return collect($eventos)
            ->groupBy(fn(array $evento): string => $this->chaveAgrupamentoEventoLista($evento))
            ->map(fn($grupo): array => $this->prepararEventoListaAgrupado($grupo))
            ->filter()
            ->sortBy(fn(array $evento): string => $evento['start_datetime'] ?? ($evento['start'] ?? ''))
            ->values()
            ->toArray();
    }

    protected function chaveAgrupamentoEventoLista(array $evento): string
    {
        if (
            $this->isEventoCronogramaLista($evento)
            && filled($evento['list_agendaable_type'] ?? $evento['agendaable_type'] ?? null)
            && filled($evento['list_agendaable_id'] ?? $evento['agendaable_id'] ?? null)
        ) {
            $type = $evento['list_agendaable_type'] ?? $evento['agendaable_type'];
            $id = $evento['list_agendaable_id'] ?? $evento['agendaable_id'];

            return "cronograma:{$type}:{$id}";
        }

        return "agenda:{$evento['id']}";
    }

    protected function prepararEventoListaAgrupado($grupo): array
    {
        $ordenado = collect($grupo)
            ->sortBy(fn(array $evento): string => $evento['start_datetime'] ?? ($evento['start'] ?? ''))
            ->values();

        $evento = $ordenado->firstWhere('category', 'curso') ?? $ordenado->first();

        if (!$evento) {
            return [];
        }

        $inicio = Carbon::parse($ordenado->min('start_datetime') ?? $evento['start']);
        $termino = Carbon::parse($ordenado->max('end_datetime') ?? ($evento['end'] ?? $evento['start']));
        $diaTodo = $ordenado->every(fn(array $item): bool => (bool) ($item['is_all_day'] ?? false));
        $multiDia = !$inicio->isSameDay($termino);
        $horarios = $ordenado
            ->reject(fn(array $item): bool => (bool) ($item['is_all_day'] ?? false))
            ->map(fn(array $item): string => "{$item['start_time']} - {$item['end_time']}")
            ->unique()
            ->values();
        $horarioAgrupado = $horarios->count() === 1 ? $horarios->first() : 'Horários variados';
        $dataAgrupada = $multiDia
            ? "{$inicio->format('d/m/Y')} até {$termino->format('d/m/Y')}"
            : $inicio->format('d/m/Y');
        $horarioLista = $diaTodo
            ? 'Prevista'
            : ($multiDia ? $horarioAgrupado : "{$inicio->format('H:i')} até {$termino->format('H:i')}");

        $evento['start_datetime'] = $inicio->toDateTimeString();
        $evento['end_datetime'] = $termino->toDateTimeString();
        $evento['start_date'] = $inicio->toDateString();
        $evento['end_date'] = $termino->toDateString();
        $evento['start_time'] = $inicio->format('H:i');
        $evento['end_time'] = $termino->format('H:i');
        $evento['start'] = $inicio->toDateString();
        $evento['end'] = $termino->toDateString();
        $evento['is_all_day'] = $diaTodo;
        $evento['title'] = $evento['list_title'] ?? $evento['title'];
        $evento['category'] = $evento['list_category'] ?? $evento['category'];
        $evento['agendaable_type'] = $evento['list_agendaable_type'] ?? $evento['agendaable_type'];
        $evento['agendaable_id'] = $evento['list_agendaable_id'] ?? $evento['agendaable_id'];
        $evento['list_primary_label'] = $dataAgrupada;
        $evento['list_secondary_label'] = $horarioLista;
        $evento['list_date_label'] = $dataAgrupada;
        $evento['list_time_label'] = $horarioLista;
        $evento['list_activity_summary'] = $this->montarResumoAtividadesLista($ordenado);
        $evento['list_agenda_ids'] = $ordenado->pluck('id')->values()->all();
        $evento['is_list_grouped'] = $ordenado->count() > 1 || $multiDia;
        $evento['presence_key'] = $this->chaveAgrupamentoEventoLista($evento);

        return $evento;
    }

    protected function montarResumoAtividadesLista($eventos): ?string
    {
        $atividades = collect($eventos)
            ->filter(fn(array $evento): bool => ($evento['category'] ?? null) === 'atividade');

        if ($atividades->isEmpty()) {
            return null;
        }

        return $atividades
            ->groupBy(fn(array $evento): string => $this->normalizarTextoCronograma($evento['activity_title'] ?? $evento['title'] ?? 'Atividade'))
            ->map(function ($grupo, string $titulo): string {
                $datas = collect($grupo)
                    ->sortBy(fn(array $evento): string => $evento['start_datetime'] ?? ($evento['start'] ?? ''))
                    ->map(fn(array $evento): string => Carbon::parse($evento['start_datetime'] ?? $evento['start'])->format('d/m'))
                    ->unique()
                    ->values()
                    ->join(', ');

                return "{$titulo}: {$datas}";
            })
            ->values()
            ->join('; ');
    }

    protected function isEventoCronogramaLista(array $evento): bool
    {
        return in_array($evento['list_category'] ?? $evento['category'] ?? null, ['atividade', 'curso', 'acao'], true);
    }

    /**
     * Carrega as tarefas com base no perfil (Gestor vs Usuário) e contexto
     */
    /*
    ** Formulário de Filtro
    */
    protected function getTipoLocalOptions(): array
    {
        return Cache::store('file')->remember(
            'evento-calendar-widget:tipo-local-options',
            now()->addMinutes(10),
            fn(): array => TipoLocal::query()
                ->orderBy('descricao')
                ->pluck('descricao', 'id')
                ->toArray(),
        );
    }
}
