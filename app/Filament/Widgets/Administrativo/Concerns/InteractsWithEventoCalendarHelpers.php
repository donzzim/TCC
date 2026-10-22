<?php

namespace App\Filament\Widgets\Administrativo\Concerns;

use Filament\Schemas\Schema;
use App\Models\Administrativo\Agenda;

use App\Filament\Resources\Escola\AcaoResource;
use App\Filament\Resources\Escola\CursoAtividadesResource;
use App\Filament\Resources\Escola\CursosResource;
use App\Models\Escola\Acao;
use App\Models\Escola\CursoAtividades;
use App\Models\Escola\Cursos;
use App\Models\User;
use App\Services\AgendaService;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema as DatabaseSchema;

trait InteractsWithEventoCalendarHelpers
{
    protected function normalizarArray($valor): array
    {
        if (is_array($valor)) {
            return $valor;
        }

        if (is_string($valor) && filled($valor)) {
            $decoded = json_decode($valor, true);

            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    protected function normalizarIdOpcional($valor): ?int
    {
        return filled($valor) ? (int) $valor : null;
    }

    protected function richTextParaString($valor): string
    {
        if (is_array($valor)) {
            return json_encode($valor, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
        }

        return trim((string) $valor);
    }

    protected function richTextParaResumo($valor): string
    {
        return trim(html_entity_decode(strip_tags($this->richTextParaString($valor))));
    }

    protected function getNomeLocalPorId(?int $localId): ?string
    {
        if (!$localId) {
            return null;
        }

        return $this->getTipoLocalOptions()[$localId] ?? null;
    }

    protected function cursoPossuiColuna(string $coluna): bool
    {
        return Cache::store('file')->remember(
            "evento-calendar-widget:esc-cursos-column:{$coluna}",
            now()->addMinutes(10),
            fn(): bool => DatabaseSchema::hasColumn('esc_cursos', $coluna),
        );
    }

    protected function getTituloInlineEventoLista(Agenda $agenda, $registro): string
    {
        if ($registro instanceof Acao) {
            return $this->getTituloAcaoCronograma($registro);
        }

        if ($registro instanceof Cursos) {
            return $registro->titulo ?: 'Curso';
        }

        if ($registro instanceof CursoAtividades) {
            return $registro->curso?->titulo ?: $registro->titulo ?: 'Curso';
        }

        return strip_tags((string) ($agenda->titulo ?: $agenda->descricao ?: 'Evento'));
    }

    protected function getInlineInstrutoresEventoLista($registro, array $metadados = []): array
    {
        if ($registro instanceof CursoAtividades) {
            $registro = $registro->curso ?: $registro;
        }

        if ($registro instanceof Cursos) {
            $usuarios = $registro->relationLoaded('userInstrutores')
                ? $registro->userInstrutores
                : $registro->userInstrutores()->get();
            $cronogramaCurso = $this->normalizarArray(
                data_get($this->normalizarArray($registro->auto_config ?? []), 'cronograma', []),
            );
            $instrutoresLivres = collect($this->normalizarInstrutoresLivres($cronogramaCurso['instrutores_sem_cadastro'] ?? []))
                ->map(fn(array $instrutor): array => $this->getInlineOpcaoLivre($instrutor['nome'] ?? '', $instrutor['comentario'] ?? null));

            return $usuarios
                ->map(fn(User $usuario): array => $this->getInlineOpcaoUsuario($usuario))
                ->merge($instrutoresLivres)
                ->filter(fn(array $instrutor): bool => filled($instrutor['label'] ?? null))
                ->values()
                ->all();
        }

        if ($registro instanceof Acao) {
            return collect($this->normalizarArray($metadados['instrutores'] ?? []))
                ->map(function ($instrutor): ?array {
                    if (is_string($instrutor)) {
                        return $this->getInlineOpcaoLivre($instrutor);
                    }

                    if (!is_array($instrutor)) {
                        return null;
                    }

                    if (filled($instrutor['user_id'] ?? null)) {
                        return [
                            'id' => (int) $instrutor['user_id'],
                            'label' => trim((string) ($instrutor['nome'] ?? "Usuário {$instrutor['user_id']}")),
                            'type' => 'user',
                            'comentario' => trim((string) ($instrutor['comentario'] ?? '')) ?: null,
                        ];
                    }

                    return $this->getInlineOpcaoLivre($instrutor['nome'] ?? '', $instrutor['comentario'] ?? null);
                })
                ->filter(fn($instrutor): bool => is_array($instrutor) && filled($instrutor['label'] ?? null))
                ->values()
                ->all();
        }

        return [];
    }

    protected function getInlineLocalEventoLista(Agenda $agenda, $registro, array $metadados = []): ?array
    {
        if ($registro instanceof CursoAtividades) {
            $registro = $registro->curso ?: $registro;
        }

        $localId = $this->getEventoListaLocalId($agenda, $registro, $metadados);
        $localNome = $localId ? $this->getNomeLocalPorId($localId) : null;

        if (!$localNome && $registro instanceof Acao) {
            $localNome = $metadados['local_nome'] ?? $registro->local_atividade ?? null;
        }

        if (blank($localNome)) {
            return null;
        }

        return [
            'id' => $localId,
            'label' => $localNome,
            'type' => 'option',
        ];
    }

    protected function getInlineOpcaoUsuario(User $usuario): array
    {
        return [
            'id' => (int) $usuario->id,
            'label' => $usuario->name,
            'type' => 'user',
        ];
    }

    protected function getInlineOpcaoLivre(?string $nome, ?string $comentario = null): array
    {
        return [
            'id' => null,
            'label' => trim((string) $nome),
            'type' => 'free',
            'comentario' => trim((string) $comentario) ?: null,
        ];
    }

    protected function formatarInstrutoresMetadados($instrutores): string
    {
        return collect($this->normalizarArray($instrutores))
            ->map(function ($instrutor): ?string {
                if (is_string($instrutor)) {
                    return trim($instrutor);
                }

                if (!is_array($instrutor)) {
                    return null;
                }

                return trim((string) ($instrutor['nome'] ?? ''));
            })
            ->filter()
            ->unique()
            ->join(', ');
    }

    protected function getLocalAcaoCronograma(Acao $acao): string
    {
        $metadados = $this->normalizarArray($acao->metadados ?? []);
        $localId = $this->normalizarIdOpcional($metadados['local_id'] ?? null);

        return $this->getNomeLocalPorId($localId)
            ?: ($metadados['local_nome'] ?? null)
            ?: ($acao->local_atividade ?: 'A definir');
    }

    protected function getTituloAcaoCronograma(Acao $acao): string
    {
        return data_get($this->normalizarArray($acao->metadados ?? []), 'titulo')
            ?: ($acao->descricao ?: 'Acao prevista');
    }

    protected function abrirEventoLista(int $eventId): void
    {
        $this->preencherEventoListaForm($eventId, $this->eventoListaEditForm);
    }

    protected function preencherEventoListaForm(int $eventId, Schema $form): ?Agenda
    {
        $this->eventoListaAbertoId = $eventId;
        $this->eventoListaForm = [];
        $this->eventoListaLocalOptions = [];

        $agenda = Agenda::with(['agendaable', 'local'])->find($eventId);

        if (!$agenda || !$agenda->agendaable) {
            Notification::make()
                ->title('Evento não encontrado.')
                ->danger()
                ->send();

            $this->fecharEventoLista();
            return null;
        }

        $registro = $this->getRegistroMacroEventoLista($agenda);
        $this->carregarRelacoesEventoCronograma($registro);

        $agendasDoRegistro = $this->getAgendasEventoLista($agenda);
        $form->fill(
            $this->getEventoListaFormData($agenda, $agendasDoRegistro, $registro),
        );

        return $agenda;
    }

    protected function fecharEventoLista(): void
    {
        $this->eventoListaAbertoId = null;
        $this->eventoListaForm = [];
        $this->eventoListaLocalOptions = [];
    }

    protected function getRegistroMacroEventoLista(Agenda $agenda)
    {
        $registro = $agenda->agendaable;

        if ($registro instanceof CursoAtividades) {
            $registro->loadMissing('curso');

            return $registro->curso ?: $registro;
        }

        return $registro;
    }

    protected function getAgendasEventoLista(Agenda $agenda)
    {
        $registro = $this->getRegistroMacroEventoLista($agenda);

        if ($registro instanceof Cursos) {
            return AgendaService::buscarAgendas($registro);
        }

        if (
            !($registro instanceof Acao || $registro instanceof CursoAtividades)
            || !method_exists($registro, 'agendas')
        ) {
            return collect([$agenda]);
        }

        return $registro->agendas()->orderBy('inicio')->get();
    }

    protected function getEventoListaFormData(Agenda $agenda, $agendasDoRegistro = null, $registro = null): array
    {
        $registro = $registro ?: $agenda->agendaable;
        $agendasDoRegistro = collect($agendasDoRegistro ?: [$agenda]);
        $metadados = $registro instanceof Acao
            ? $this->normalizarArray($registro->metadados ?? [])
            : [];
        $cronogramaCurso = $registro instanceof Cursos
            ? $this->normalizarArray(data_get($this->normalizarArray($registro->auto_config ?? []), 'cronograma', []))
            : [];
        $comentarios = $registro instanceof Acao
            ? $this->normalizarArray($metadados['comentarios'] ?? [])
            : $this->normalizarArray($cronogramaCurso['comentarios'] ?? []);

        return [
            'tipo_registro' => $registro instanceof Acao ? 'acao' : 'curso',
            'titulo' => $this->getEventoListaAcaoEditavel($agenda, $registro),
            'comentario_titulo' => $comentarios['titulo'] ?? '',
            'instrutor_user_ids' => $this->getEventoListaInstrutorIds($registro, $metadados),
            'instrutores_sem_cadastro' => $this->getEventoListaInstrutoresSemCadastro($registro, $metadados, $cronogramaCurso),
            'comentario_instrutores' => $comentarios['instrutores'] ?? '',
            'local_id' => $this->getEventoListaLocalId($agenda, $registro, $metadados),
            'comentario_local' => $comentarios['local'] ?? '',
            'informacoes_adicionais' => $this->getEventoListaInformacoesAdicionais($agenda, $registro, $metadados),
            'tem_atividades' => $registro instanceof Cursos && $registro->temAtividades(),
            'agenda_ids' => $agendasDoRegistro->pluck('id')->values()->all(),
        ];
    }

    protected function getEventoListaInstrutorIds($registro, array $metadados): array
    {
        if ($registro instanceof Cursos) {
            return $registro->userInstrutores
                ->pluck('id')
                ->map(fn($id): int => (int) $id)
                ->values()
                ->all();
        }

        if ($registro instanceof Acao) {
            return collect($this->normalizarArray($metadados['instrutores'] ?? []))
                ->pluck('user_id')
                ->filter(fn($id): bool => filled($id))
                ->map(fn($id): int => (int) $id)
                ->unique()
                ->values()
                ->all();
        }

        return [];
    }

    protected function getEventoListaInstrutoresSemCadastro($registro, array $metadados, array $cronogramaCurso): array
    {
        if ($registro instanceof Cursos) {
            return $this->normalizarInstrutoresLivres($cronogramaCurso['instrutores_sem_cadastro'] ?? []);
        }

        if ($registro instanceof Acao) {
            return collect($this->normalizarArray($metadados['instrutores'] ?? []))
                ->filter(fn($instrutor): bool => is_array($instrutor) && blank($instrutor['user_id'] ?? null))
                ->map(fn(array $instrutor): array => array_filter([
                    'nome' => trim((string) ($instrutor['nome'] ?? '')),
                    'comentario' => trim((string) ($instrutor['comentario'] ?? '')) ?: null,
                ], fn($valor): bool => filled($valor)))
                ->filter(fn(array $instrutor): bool => filled($instrutor['nome'] ?? null))
                ->values()
                ->all();
        }

        return [];
    }

    protected function getEventoListaLocalId(Agenda $agenda, $registro, array $metadados): ?int
    {
        if ($registro instanceof Acao) {
            return $this->normalizarIdOpcional($metadados['local_id'] ?? null);
        }

        if ($registro instanceof Cursos || $registro instanceof CursoAtividades) {
            return $this->normalizarIdOpcional($registro->tipolocal_id ?? $agenda->local_id);
        }

        return $this->normalizarIdOpcional($agenda->local_id);
    }

    protected function getEventoListaInformacoesAdicionais(Agenda $agenda, $registro, array $metadados): string
    {
        if ($registro instanceof Acao) {
            return $this->richTextParaString(
                $metadados['informacoes_adicionais']
                    ?? $registro->informacao
                    ?? $registro->observacao
                    ?? $this->getDescricaoAgendaCronograma($agenda, [$registro->descricao])
                    ?? '',
            );
        }

        if ($registro instanceof Cursos) {
            $autoConfig = $this->normalizarArray($registro->auto_config ?? []);
            $providencias = $this->cursoPossuiColuna('providencias')
                ? ($registro->providencias ?? null)
                : null;

            return $this->richTextParaString(
                $providencias
                    ?? data_get($autoConfig, 'cronograma.informacoes_adicionais')
                    ?? '',
            );
        }

        if ($registro instanceof CursoAtividades) {
            $curso = $registro->curso;
            $autoConfig = $curso ? $this->normalizarArray($curso->auto_config ?? []) : [];
            $providencias = $curso && $this->cursoPossuiColuna('providencias')
                ? ($curso->providencias ?? null)
                : null;

            return $this->richTextParaString(
                $providencias
                    ?? data_get($autoConfig, 'cronograma.informacoes_adicionais')
                    ?? $registro->descricao
                    ?? '',
            );
        }

        return $this->richTextParaString($agenda->descricao ?? '');
    }

    protected function getEventoListaAcaoEditavel(Agenda $agenda, $registro = null): string
    {
        $registro = $registro ?: $agenda->agendaable;

        if ($registro instanceof Acao) {
            return data_get($this->normalizarArray($registro->metadados ?? []), 'titulo')
                ?: ($registro->descricao ?? '');
        }

        if ($registro instanceof Cursos || $registro instanceof CursoAtividades) {
            return $registro->titulo ?? '';
        }

        return $agenda->titulo ?: $agenda->descricao ?: '';
    }

    protected function getEventoListaObservacaoEditavel(Agenda $agenda, $registro): string
    {
        if ($registro instanceof Acao) {
            return $registro->observacao ?? '';
        }

        if ($registro instanceof Cursos) {
            return $registro->descricao ?? '';
        }

        if ($registro instanceof CursoAtividades) {
            return $registro->curso?->descricao ?? $registro->descricao ?? '';
        }

        return $agenda->descricao ?? '';
    }

    protected function montarDiasAgendamentoEventoLista($registro, Carbon $inicio, Carbon $termino, ?int $localId, string $observacao): array
    {
        $agendasPorData = $registro->agendas()
            ->get()
            ->keyBy(fn(Agenda $agenda): string => Carbon::parse($agenda->inicio)->toDateString());
        $dias = [];
        $data = $inicio->copy()->startOfDay();
        $dataFinal = $termino->copy()->startOfDay();

        while ($data->lessThanOrEqualTo($dataFinal)) {
            if ($data->isWeekend()) {
                $data->addDay();
                continue;
            }

            $dataAgenda = $data->toDateString();
            $agendaAtual = $agendasPorData->get($dataAgenda);

            $dias[] = [
                'id' => $agendaAtual?->id,
                'data' => $dataAgenda,
                'hora_inicio' => $inicio->format('H:i'),
                'hora_termino' => $termino->format('H:i'),
                'titulo' => 'Curso',
                'descricao' => $observacao ?: ($registro->titulo ?? 'Curso'),
                'local_id' => $localId,
                'atualizado_por' => Auth::id(),
                'operacao' => $agendaAtual ? 'update' : 'create',
            ];

            $data->addDay();
        }

        return $dias;
    }

    protected function montarPeriodoEventoLista(): ?array
    {
        $dataInicio = $this->eventoListaForm['data_inicio'] ?? null;
        $dataTermino = $this->eventoListaForm['data_termino'] ?? $dataInicio;

        if (blank($dataInicio)) {
            Notification::make()
                ->title('Informe a data.')
                ->danger()
                ->send();

            return null;
        }

        $diaTodo = filter_var($this->eventoListaForm['dia_todo'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $horaInicio = $this->eventoListaForm['hora_inicio'] ?? null;
        $horaTermino = $this->eventoListaForm['hora_termino'] ?? null;

        try {
            $inicio = Carbon::parse($dataInicio);
            $termino = Carbon::parse($dataTermino ?: $dataInicio);

            if ($diaTodo) {
                $inicio->startOfDay();
                $termino->endOfDay();
            } else {
                $inicio = Carbon::parse($inicio->toDateString() . ' ' . ($horaInicio ?: '00:00'));
                $termino = Carbon::parse($termino->toDateString() . ' ' . ($horaTermino ?: '23:59'));
            }
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Período inválido.')
                ->danger()
                ->send();

            return null;
        }

        if ($termino->lessThanOrEqualTo($inicio)) {
            Notification::make()
                ->title('Período inválido.')
                ->body('O término precisa ser maior que o início.')
                ->danger()
                ->send();

            return null;
        }

        return [$inicio, $termino, $diaTodo];
    }

    protected function carregarRelacoesEventoCronograma($registro): void
    {
        if ($registro instanceof Cursos) {
            $registro->loadMissing(['modalidade', 'tipoLocal', 'userInstrutores']);
            return;
        }

        if ($registro instanceof CursoAtividades) {
            $registro->loadMissing(['curso.userInstrutores', 'tipoLocal', 'instrutores']);
        }
    }

    protected function getEventoCronogramaTitulo(Agenda $agenda): string
    {
        $registro = $agenda->agendaable;

        if ($registro instanceof Acao) {
            return $this->juntarPartesCronograma([
                $registro->codigo,
                'Acao',
                $this->getTituloAcaoCronograma($registro),
            ]);
        }

        if ($registro instanceof CursoAtividades) {
            return $this->juntarPartesCronograma([
                $registro->legado_id,
                'Curso',
                $registro->titulo ?: $agenda->descricao,
            ]);
        }

        if ($registro instanceof Cursos) {
            return $this->juntarPartesCronograma([
                $registro->legado_id,
                $this->juntarPartesCronograma(['Curso', $registro->modalidade?->descricao], ' '),
                $registro->titulo ?: $agenda->descricao,
            ]);
        }

        return $agenda->titulo ?: $agenda->descricao ?: 'Detalhes do Evento';
    }

    protected function getEventoCronogramaCorpoDocente($registro): string
    {
        if ($registro instanceof Acao) {
            $instrutores = $this->formatarInstrutoresMetadados(
                data_get($this->normalizarArray($registro->metadados ?? []), 'instrutores', []),
            );

            return $instrutores ?: 'A definir';
        }

        if ($registro instanceof Cursos) {
            return $registro->userInstrutores->pluck('name')->filter()->join(', ') ?: 'A definir';
        }

        if ($registro instanceof CursoAtividades) {
            $instrutores = $registro->instrutores->pluck('name')->filter()->join(', ');
            $instrutoresDoCurso = $registro->curso?->userInstrutores
                ? $registro->curso->userInstrutores->pluck('name')->filter()->join(', ')
                : null;

            return $instrutores
                ?: $instrutoresDoCurso
                ?: 'A definir';
        }

        return 'A definir';
    }

    protected function getEventoCronogramaLocal(Agenda $agenda): string
    {
        $registro = $agenda->agendaable;

        if ($registro instanceof Acao) {
            return $this->getLocalAcaoCronograma($registro);
        }

        if ($registro instanceof Cursos || $registro instanceof CursoAtividades) {
            return $registro->tipoLocal?->descricao
                ?: $agenda->local?->descricao
                ?: 'A definir';
        }

        return $agenda->local?->descricao ?: 'A definir';
    }

    protected function getEventoCronogramaObservacao(Agenda $agenda): string
    {
        $registro = $agenda->agendaable;

        if ($registro instanceof Acao) {
            $metadados = $this->normalizarArray($registro->metadados ?? []);

            return $this->primeiroTextoCronograma([
                $metadados['informacoes_adicionais'] ?? null,
                $registro->observacao,
                $registro->informacao,
                $this->getDescricaoAgendaCronograma($agenda, [$registro->descricao]),
            ]);
        }

        if ($registro instanceof CursoAtividades) {
            return $this->primeiroTextoCronograma([
                $this->getDescricaoAgendaCronograma($agenda, [$registro->titulo]),
                $registro->descricao,
                $registro->curso?->descricao,
            ]);
        }

        if ($registro instanceof Cursos) {
            $autoConfig = $this->normalizarArray($registro->auto_config ?? []);

            return $this->primeiroTextoCronograma([
                $this->cursoPossuiColuna('providencias') ? ($registro->providencias ?? null) : null,
                data_get($autoConfig, 'cronograma.informacoes_adicionais'),
                $this->getDescricaoAgendaCronograma($agenda, [$registro->titulo]),
                $registro->descricao,
                $registro->justificativa,
            ]);
        }

        return $this->primeiroTextoCronograma([$agenda->descricao]);
    }

    protected function formatarDataCronograma(Agenda $agenda): string
    {
        if (!$agenda->inicio) {
            return '-';
        }

        $inicio = Carbon::parse($agenda->inicio);
        $termino = $agenda->termino ? Carbon::parse($agenda->termino) : null;

        if (!$termino || $inicio->isSameDay($termino)) {
            return $inicio->format('d/m/Y');
        }

        return "{$inicio->format('d/m/Y')} a {$termino->format('d/m/Y')}";
    }

    protected function formatarHorarioCronograma(Agenda $agenda): string
    {
        if (!$agenda->inicio || !$agenda->termino || $agenda->dia_todo) {
            return 'A definir';
        }

        $inicio = Carbon::parse($agenda->inicio);
        $termino = Carbon::parse($agenda->termino);

        if (
            $inicio->format('H:i:s') === '00:00:00'
            && in_array($termino->format('H:i:s'), ['23:59:00', '23:59:59'], true)
        ) {
            return 'A definir';
        }

        return "{$inicio->format('H:i')} às {$termino->format('H:i')}";
    }

    protected function getEventoCronogramaUrl($eventId): ?string
    {
        if (!$eventId) {
            return null;
        }

        $agenda = $this->getAgendaCronograma((int) $eventId);
        $registro = $agenda?->agendaable;

        if ($registro instanceof Acao) {
            return AcaoResource::getUrl('edit', ['record' => $registro->id]);
        }

        if ($registro instanceof CursoAtividades) {
            return $registro->curso_id
                ? CursosResource::getUrl('edit', ['record' => $registro->curso_id])
                : CursoAtividadesResource::getUrl('edit', ['record' => $registro->id]);
        }

        if ($registro instanceof Cursos) {
            return CursosResource::getUrl('edit', ['record' => $registro->id]);
        }

        return null;
    }

    protected function juntarPartesCronograma(array $partes, string $separador = ' - '): string
    {
        return collect($partes)
            ->filter(fn($parte) => filled($parte))
            ->map(fn($parte) => trim((string) $parte))
            ->filter()
            ->join($separador) ?: '-';
    }

    protected function primeiroTextoCronograma(array $valores): string
    {
        foreach ($valores as $valor) {
            if (filled($valor)) {
                return (string) $valor;
            }
        }

        return '-';
    }

    protected function getDescricaoAgendaCronograma(Agenda $agenda, array $titulosIgnorados = []): ?string
    {
        if (blank($agenda->descricao)) {
            return null;
        }

        $descricao = trim((string) $agenda->descricao);
        $descricaoNormalizada = $this->normalizarTextoCronograma($descricao);

        foreach ($titulosIgnorados as $titulo) {
            if (filled($titulo) && $descricaoNormalizada === $this->normalizarTextoCronograma((string) $titulo)) {
                return null;
            }
        }

        return $descricao;
    }

    protected function normalizarTextoCronograma(string $texto): string
    {
        return trim((string) preg_replace('/\s+/', ' ', strip_tags($texto)));
    }
}
