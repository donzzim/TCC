<?php

namespace App\Filament\Widgets\Administrativo\Concerns;

use App\Models\Administrativo\Agenda;
use App\Models\Escola\Acao;
use App\Models\Escola\CursoAtividades;
use App\Models\Escola\Cursos;
use App\Models\User;
use App\Services\AgendaService;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

trait InteractsWithEventoListaEditing
{
    public function toggleEventoLista(int $eventId): void
    {
        if ($this->eventoListaAbertoId === $eventId) {
            $this->fecharEventoLista();
            return;
        }

        $this->abrirEventoLista($eventId);
    }

    public function cancelarEdicaoEventoLista(): void
    {
        $this->fecharEventoLista();
    }

    public function salvarEventoLista(): void
    {
        if (!$this->eventoListaAbertoId) {
            return;
        }

        $this->salvarEventoListaData(
            $this->eventoListaAbertoId,
            $this->eventoListaEditForm->getState(),
            true,
        );
    }

    protected function salvarEventoListaData(?int $eventId, array $data, bool $manterAberto = false): ?Agenda
    {
        if (!$eventId) {
            return null;
        }

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

        $titulo = trim((string) ($data['titulo'] ?? ''));

        if (blank($titulo)) {
            Notification::make()
                ->title('Informe o título.')
                ->danger()
                ->send();

            return null;
        }

        if ($registro instanceof Acao) {
            $agenda = $this->salvarEventoListaAcao($registro, $agenda, $data);
        } elseif ($registro instanceof Cursos) {
            $agenda = $this->salvarEventoListaCurso($registro, $agenda, $data);
        } else {
            $agenda->update([
                'titulo' => $titulo,
                'descricao' => $this->richTextParaResumo($data['informacoes_adicionais'] ?? '') ?: $titulo,
            ]);
        }

        $this->loadEvents(true);

        if ($manterAberto) {
            $this->abrirEventoLista($agenda->id);
        } else {
            $this->sairPresencaEventoLista($eventId, 'formulario');
            $this->fecharEventoLista();
        }

        Notification::make()
            ->title('Cronograma atualizado.')
            ->success()
            ->send();

        return $agenda;
    }

    protected function salvarEventoListaAcao(Acao $acao, Agenda $agenda, array $data): Agenda
    {
        $titulo = trim((string) ($data['titulo'] ?? ''));
        $localId = $this->normalizarIdOpcional($data['local_id'] ?? null);
        $localNome = $this->getNomeLocalPorId($localId);
        $informacoesAdicionais = $this->richTextParaString($data['informacoes_adicionais'] ?? '');
        $metadados = $this->normalizarArray($acao->metadados ?? []);

        $metadados['titulo'] = $titulo;
        $metadados['instrutores'] = $this->montarInstrutoresMetadados(
            $data['instrutor_user_ids'] ?? [],
            $data['instrutores_sem_cadastro'] ?? [],
        );
        $metadados['local_id'] = $localId;
        $metadados['local_nome'] = $localNome;
        $metadados['informacoes_adicionais'] = $informacoesAdicionais;
        $metadados['comentarios'] = $this->normalizarComentariosEventoLista($data);
        $metadados['atualizado_em'] = now()->toDateTimeString();

        $acao->fill([
            'descricao' => $titulo,
            'local_atividade' => $localNome,
            'observacao' => $this->richTextParaResumo($informacoesAdicionais),
            'metadados' => $metadados,
        ]);
        $acao->save();

        $agenda = $acao->agendas()->orderBy('id')->first() ?? $agenda;
        $agenda->update([
            'titulo' => $this->juntarPartesCronograma(['Acao prevista', $titulo], ': '),
            'descricao' => $this->richTextParaResumo($informacoesAdicionais) ?: $titulo,
            'local_id' => null,
            'atualizado_por' => Auth::id(),
        ]);

        return $agenda;
    }

    protected function salvarEventoListaCurso(Cursos $curso, Agenda $agenda, array $data): Agenda
    {
        $titulo = trim((string) ($data['titulo'] ?? ''));
        $localId = $this->normalizarIdOpcional($data['local_id'] ?? null);
        $informacoesAdicionais = $this->richTextParaString($data['informacoes_adicionais'] ?? '');
        $autoConfig = $this->normalizarArray($curso->auto_config ?? []);
        $autoConfig['cronograma'] = array_replace(
            $this->normalizarArray($autoConfig['cronograma'] ?? []),
            [
                'informacoes_adicionais' => $informacoesAdicionais,
                'instrutores_sem_cadastro' => $this->normalizarInstrutoresLivres($data['instrutores_sem_cadastro'] ?? []),
                'comentarios' => $this->normalizarComentariosEventoLista($data),
                'atualizado_em' => now()->toDateTimeString(),
            ],
        );

        $curso->titulo = $titulo;
        $curso->tipolocal_id = $localId;
        if ($this->cursoPossuiColuna('providencias')) {
            $curso->providencias = $informacoesAdicionais;
        }
        $curso->auto_config = $autoConfig;
        $curso->save();

        $this->sincronizarInstrutoresCurso($curso, $data['instrutor_user_ids'] ?? []);

        $resumo = $this->richTextParaResumo($informacoesAdicionais) ?: $titulo;
        $curso->agendas()->update([
            'descricao' => $resumo,
            'local_id' => $localId,
            'atualizado_por' => Auth::id(),
        ]);

        return AgendaService::buscarAgendas($curso)->first() ?? $agenda;
    }

    protected function salvarTituloInlineCronograma(Agenda $agenda, $registro, string $titulo): void
    {
        $titulo = trim($titulo);

        if (blank($titulo)) {
            throw new \InvalidArgumentException('Informe o título.');
        }

        if ($registro instanceof Acao) {
            $metadados = $this->normalizarArray($registro->metadados ?? []);
            $metadados['titulo'] = $titulo;
            $metadados['atualizado_em'] = now()->toDateTimeString();

            $registro->fill([
                'descricao' => $titulo,
                'metadados' => $metadados,
            ]);
            $registro->save();

            $registro->agendas()->update([
                'titulo' => $this->juntarPartesCronograma(['Acao prevista', $titulo], ': '),
                'atualizado_por' => Auth::id(),
            ]);

            return;
        }

        if ($registro instanceof Cursos) {
            $registro->titulo = $titulo;
            $registro->save();

            return;
        }

        $agenda->update([
            'titulo' => $titulo,
            'atualizado_por' => Auth::id(),
        ]);
    }

    protected function salvarInstrutoresInlineCronograma($registro, $valor): void
    {
        if ($registro instanceof CursoAtividades) {
            $registro = $registro->curso ?: $registro;
        }

        $instrutores = $this->normalizarInlineInstrutoresPayload($valor);

        if ($registro instanceof Acao) {
            $metadados = $this->normalizarArray($registro->metadados ?? []);
            $metadados['instrutores'] = $instrutores;
            $metadados['atualizado_em'] = now()->toDateTimeString();

            $registro->metadados = $metadados;
            $registro->save();

            return;
        }

        if ($registro instanceof Cursos) {
            $ids = collect($instrutores)
                ->pluck('user_id')
                ->filter(fn($id): bool => filled($id))
                ->map(fn($id): int => (int) $id)
                ->unique()
                ->values()
                ->all();
            $livres = collect($instrutores)
                ->filter(fn(array $instrutor): bool => blank($instrutor['user_id'] ?? null))
                ->map(fn(array $instrutor): array => array_filter([
                    'nome' => $instrutor['nome'] ?? null,
                    'comentario' => $instrutor['comentario'] ?? null,
                ], fn($item): bool => filled($item)))
                ->values()
                ->all();
            $autoConfig = $this->normalizarArray($registro->auto_config ?? []);
            $autoConfig['cronograma'] = array_replace(
                $this->normalizarArray($autoConfig['cronograma'] ?? []),
                [
                    'instrutores_sem_cadastro' => $livres,
                    'atualizado_em' => now()->toDateTimeString(),
                ],
            );

            $this->sincronizarInstrutoresCurso($registro, $ids);
            $registro->auto_config = $autoConfig;
            $registro->save();
        }
    }

    protected function salvarLocalInlineCronograma(Agenda $agenda, $registro, $valor): void
    {
        if ($registro instanceof CursoAtividades) {
            $registro = $registro->curso ?: $registro;
        }

        $localId = $this->normalizarInlineLocalId($valor);
        $localNome = $this->getNomeLocalPorId($localId);

        if ($registro instanceof Acao) {
            $metadados = $this->normalizarArray($registro->metadados ?? []);
            $metadados['local_id'] = $localId;
            $metadados['local_nome'] = $localNome;
            $metadados['atualizado_em'] = now()->toDateTimeString();

            $registro->fill([
                'local_atividade' => $localNome,
                'metadados' => $metadados,
            ]);
            $registro->save();

            $registro->agendas()->update([
                'local_id' => null,
                'atualizado_por' => Auth::id(),
            ]);

            return;
        }

        if ($registro instanceof Cursos) {
            $registro->tipolocal_id = $localId;
            $registro->save();
            $registro->agendas()->update([
                'local_id' => $localId,
                'atualizado_por' => Auth::id(),
            ]);

            return;
        }

        $agenda->update([
            'local_id' => $localId,
            'atualizado_por' => Auth::id(),
        ]);
    }

    protected function normalizarInlineInstrutoresPayload($valor): array
    {
        $itens = collect($this->normalizarArray($valor));
        $ids = $itens
            ->filter(fn($item): bool => is_array($item))
            ->filter(fn(array $item): bool => ($item['type'] ?? null) !== 'free')
            ->pluck('id')
            ->filter(fn($id): bool => filled($id))
            ->map(fn($id): int => (int) $id)
            ->unique()
            ->values();
        $nomesUsuarios = $ids->isEmpty()
            ? collect()
            : User::query()->whereIn('id', $ids)->pluck('name', 'id');

        return $itens
            ->map(function ($item) use ($nomesUsuarios): ?array {
                if (is_string($item)) {
                    $nome = trim($item);

                    return filled($nome) ? ['user_id' => null, 'nome' => $nome] : null;
                }

                if (!is_array($item)) {
                    return null;
                }

                $tipo = $item['type'] ?? null;
                $id = $tipo === 'free' ? null : $this->normalizarIdOpcional($item['id'] ?? $item['user_id'] ?? null);
                $nome = trim((string) ($item['label'] ?? $item['nome'] ?? ''));

                if ($id) {
                    $nome = (string) ($nomesUsuarios[$id] ?? $nome ?: "Usuário {$id}");
                }

                if (blank($nome) && !$id) {
                    return null;
                }

                return array_filter([
                    'user_id' => $id,
                    'nome' => $nome,
                    'comentario' => trim((string) ($item['comentario'] ?? '')) ?: null,
                ], fn($item): bool => filled($item));
            })
            ->filter()
            ->unique(fn(array $instrutor): string => filled($instrutor['user_id'] ?? null)
                ? 'user:' . $instrutor['user_id']
                : 'free:' . Str::lower($instrutor['nome'] ?? ''))
            ->values()
            ->all();
    }

    protected function normalizarInlineLocalId($valor): ?int
    {
        if (is_array($valor)) {
            $item = isset($valor[0]) && is_array($valor[0])
                ? $valor[0]
                : $valor;

            return $this->normalizarIdOpcional($item['id'] ?? $item['value'] ?? null);
        }

        return $this->normalizarIdOpcional($valor);
    }

    protected function invalidarEventosCronograma(): void
    {
        Cache::store('file')->forget($this->getEventosCacheKey());

        $this->agendaCronogramaCache = [];
        $this->calendarEventsByDate = [];
        $this->listEventDays = [];
        $this->events = [];
        $this->eventsLoaded = false;
    }

    protected function sincronizarInstrutoresCurso(Cursos $curso, array $instrutorIds): void
    {
        $syncData = collect($instrutorIds)
            ->filter(fn($id): bool => filled($id))
            ->map(fn($id): int => (int) $id)
            ->unique()
            ->mapWithKeys(fn(int $id): array => [$id => ['atualizado_por' => Auth::id()]])
            ->all();

        $curso->userInstrutores()->sync($syncData);
    }

    protected function montarInstrutoresMetadados(array $instrutorIds, array $instrutoresLivres): array
    {
        $ids = collect($instrutorIds)
            ->filter(fn($id): bool => filled($id))
            ->map(fn($id): int => (int) $id)
            ->unique()
            ->values();
        $nomesUsuarios = $ids->isEmpty()
            ? collect()
            : User::query()->whereIn('id', $ids)->pluck('name', 'id');

        return $ids
            ->map(fn(int $id): array => [
                'user_id' => $id,
                'nome' => (string) ($nomesUsuarios[$id] ?? "Usuario {$id}"),
            ])
            ->merge($this->normalizarInstrutoresLivres($instrutoresLivres))
            ->filter(fn(array $instrutor): bool => filled($instrutor['nome'] ?? null) || filled($instrutor['user_id'] ?? null))
            ->values()
            ->all();
    }

    protected function normalizarInstrutoresLivres(array $instrutores): array
    {
        return collect($instrutores)
            ->map(function ($instrutor): ?array {
                if (is_string($instrutor)) {
                    $nome = trim($instrutor);

                    return filled($nome) ? ['user_id' => null, 'nome' => $nome] : null;
                }

                if (!is_array($instrutor)) {
                    return null;
                }

                $nome = trim((string) ($instrutor['nome'] ?? ''));

                if (blank($nome)) {
                    return null;
                }

                $comentario = trim((string) ($instrutor['comentario'] ?? ''));

                return array_filter([
                    'user_id' => null,
                    'nome' => $nome,
                    'comentario' => $comentario ?: null,
                ], fn($valor): bool => filled($valor));
            })
            ->filter()
            ->values()
            ->all();
    }

    protected function normalizarComentariosEventoLista(array $data): array
    {
        return collect([
            'titulo' => $data['comentario_titulo'] ?? null,
            'instrutores' => $data['comentario_instrutores'] ?? null,
            'local' => $data['comentario_local'] ?? null,
        ])
            ->map(fn($valor): string => trim((string) $valor))
            ->filter(fn(string $valor): bool => filled($valor))
            ->all();
    }
}
