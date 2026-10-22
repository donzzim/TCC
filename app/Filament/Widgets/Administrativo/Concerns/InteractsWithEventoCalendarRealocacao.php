<?php

namespace App\Filament\Widgets\Administrativo\Concerns;


use App\Models\Administrativo\Agenda;
use App\Models\Escola\Acao;
use App\Models\Escola\CursoFrequencias;
use App\Models\Escola\Cursos;
use App\Services\AgendaService;
use Carbon\Carbon;
use Filament\Notifications\Notification;

trait InteractsWithEventoCalendarRealocacao
{
    protected function getRegistroRealocacaoEventoLista(?int $eventId = null)
    {
        $eventId ??= $this->eventoListaRealocarAgendaId;

        if (!$eventId) {
            return null;
        }

        $agenda = $this->getAgendaCronograma((int) $eventId);

        if (!$agenda || !$agenda->agendaable) {
            return null;
        }

        return $this->getRegistroMacroEventoLista($agenda);
    }

    protected function getRealocarEventoListaFormData($registro): array
    {
        return [
            'agendas' => $this->getAgendasRealocacaoEventoLista($registro),
            'agendaable' => $registro->id,
            'periodo_selecionado' => [
                'agendaable_id' => $registro->id,
                'modo_persistencia' => 'operacoes',
                'dias' => [],
            ],
        ];
    }

    protected function getAgendaableOptionsRealocacaoEventoLista($registro = null): array
    {
        return collect($this->getAgendaablesRealocacaoEventoLista($registro))
            ->pluck('titulo', 'id')
            ->toArray();
    }

    protected function getRealocacaoCacheKey($registro): string
    {
        return get_class($registro) . ':' . ($registro->id ?? 'novo');
    }

    protected function getAgendaablesRealocacaoEventoLista($registro = null): array
    {
        $registro ??= $this->getRegistroRealocacaoEventoLista();

        if (!$registro) {
            return [];
        }

        $cacheKey = $this->getRealocacaoCacheKey($registro);

        if (array_key_exists($cacheKey, $this->realocacaoAgendaablesCache)) {
            return $this->realocacaoAgendaablesCache[$cacheKey];
        }

        if ($registro instanceof Acao) {
            return $this->realocacaoAgendaablesCache[$cacheKey] = [[
                'id' => $registro->id,
                'type' => Acao::class,
                'titulo' => $registro->descricao ?: 'Ação prevista',
                'local_id' => null,
            ]];
        }

        if ($registro instanceof Cursos) {
            $atividades = $registro->temAtividades()
                ? $registro->atividades()->get()
                : collect();

            return $this->realocacaoAgendaablesCache[$cacheKey] = $atividades
                ->push($registro)
                ->map(fn($agendaable) => [
                    'id' => $agendaable->id,
                    'type' => get_class($agendaable),
                    'titulo' => $agendaable->titulo,
                    'local_id' => $agendaable->tipolocal_id,
                ])
                ->values()
                ->all();
        }

        return $this->realocacaoAgendaablesCache[$cacheKey] = [[
            'id' => $registro->id,
            'type' => get_class($registro),
            'titulo' => $registro->titulo ?? 'Curso',
            'local_id' => $registro->tipolocal_id ?? null,
        ]];
    }

    protected function getAgendasRealocacaoEventoLista($registro = null): array
    {
        $registro ??= $this->getRegistroRealocacaoEventoLista();

        if (!$registro) {
            return [];
        }

        $cacheKey = $this->getRealocacaoCacheKey($registro);

        if (array_key_exists($cacheKey, $this->realocacaoAgendasCache)) {
            return $this->realocacaoAgendasCache[$cacheKey];
        }

        if ($registro instanceof Acao) {
            return $this->realocacaoAgendasCache[$cacheKey] = $this->getAgendasRealocacaoAcao($registro);
        }

        $agendas = $registro instanceof Cursos
            ? AgendaService::buscarAgendas($registro)
            : $registro->agendas()->orderBy('inicio')->get();

        $agendasBloqueadas = $this->getAgendaIdsComFrequenciaPresente(
            $agendas->pluck('id')->filter()->all(),
        );

        return $this->realocacaoAgendasCache[$cacheKey] = $agendas
            ->map(fn(Agenda $agenda): array => $this->mapAgendaRealocacaoEventoLista($agenda, $agendasBloqueadas))
            ->values()
            ->all();
    }

    protected function getAgendaIdsComFrequenciaPresente(array $agendaIds): array
    {
        if (empty($agendaIds)) {
            return [];
        }

        return CursoFrequencias::query()
            ->whereIn('agenda_id', $agendaIds)
            ->where('situacaofrequencia_id', config('settings.frequencia.presente'))
            ->pluck('agenda_id')
            ->mapWithKeys(fn($agendaId): array => [(int) $agendaId => true])
            ->all();
    }

    protected function mapAgendaRealocacaoEventoLista(Agenda $agenda, array $agendasBloqueadas = []): array
    {
        return [
            'id' => $agenda->id,
            'agendaable_id' => $agenda->agendaable_id,
            'agendaable_type' => $agenda->agendaable_type,
            'data' => Carbon::parse($agenda->inicio)->toDateString(),
            'inicio' => Carbon::parse($agenda->inicio)->format('Y-m-d H:i:s'),
            'termino' => Carbon::parse($agenda->termino)->format('Y-m-d H:i:s'),
            'hora_inicio' => Carbon::parse($agenda->inicio)->format('H:i'),
            'hora_termino' => Carbon::parse($agenda->termino)->format('H:i'),
            'titulo' => $agenda->titulo ?? 'Curso',
            'descricao' => $agenda->descricao,
            'local_id' => $agenda->local_id,
            'atualizado_por' => $agenda->atualizado_por,
            'bloqueado' => isset($agendasBloqueadas[(int) $agenda->id]),
        ];
    }

    protected function getAgendasRealocacaoAcao(Acao $acao): array
    {
        $agenda = $acao->agendas()->orderBy('inicio')->first();

        return $this->getDatasPeriodoAcao($acao)
            ->map(fn(string $data): array => [
                'id' => $agenda?->id,
                'agendaable_id' => $acao->id,
                'agendaable_type' => Acao::class,
                'data' => $data,
                'inicio' => "{$data} 00:00:00",
                'termino' => "{$data} 23:59:59",
                'hora_inicio' => '00:00',
                'hora_termino' => '23:59',
                'titulo' => 'Ação prevista',
                'descricao' => $acao->descricao,
                'local_id' => null,
                'atualizado_por' => $agenda?->atualizado_por,
                'bloqueado' => false,
            ])
            ->values()
            ->all();
    }

    protected function getDatasPeriodoAcao(Acao $acao)
    {
        $inicioBase = $acao->data_inicio ?? $acao->agendas()->min('inicio');

        if (blank($inicioBase)) {
            return collect();
        }

        $inicio = Carbon::parse($inicioBase)->startOfDay();
        $termino = Carbon::parse($acao->data_termino ?? $inicioBase)->startOfDay();
        $datas = collect();

        while ($inicio->lessThanOrEqualTo($termino)) {
            $datas->push($inicio->toDateString());
            $inicio->addDay();
        }

        return $datas;
    }

    protected function getAgendaPrincipalRegistro($registro): ?Agenda
    {
        if (!$registro || !method_exists($registro, 'agendas')) {
            return null;
        }

        if ($registro instanceof Cursos) {
            return AgendaService::buscarAgendas($registro)->first();
        }

        return $registro->agendas()->orderBy('inicio')->first();
    }

    protected function realocarAgendaAcao(Acao $acao, array $data): void
    {
        $datas = $this->resolverDatasRealocacaoAcao($acao, $data);

        if (empty($datas)) {
            Notification::make()
                ->title('Informe ao menos uma data.')
                ->danger()
                ->send();

            return;
        }

        $acao->update([
            'data_inicio' => $datas[0],
            'data_termino' => $datas[array_key_last($datas)],
        ]);

        Notification::make()
            ->title('Agendamento atualizado.')
            ->success()
            ->send();
    }

    protected function resolverDatasRealocacaoAcao(Acao $acao, array $data): array
    {
        $diasCalendario = collect(data_get($data, 'periodo_selecionado.dias', []));

        if ($diasCalendario->isEmpty() && array_key_exists('agendas', $data)) {
            return collect($data['agendas'])
                ->pluck('data')
                ->filter()
                ->unique()
                ->sort()
                ->values()
                ->all();
        }

        $datas = $this->getDatasPeriodoAcao($acao)->unique()->values();

        foreach ($diasCalendario as $dia) {
            $dataDia = $dia['data'] ?? null;

            if (blank($dataDia)) {
                continue;
            }

            if (($dia['operacao'] ?? null) === 'delete') {
                $datas = $datas->reject(fn(string $dataExistente): bool => $dataExistente === $dataDia)->values();
                continue;
            }

            $datas->push($dataDia);
        }

        return $datas
            ->unique()
            ->sort()
            ->values()
            ->all();
    }
}
