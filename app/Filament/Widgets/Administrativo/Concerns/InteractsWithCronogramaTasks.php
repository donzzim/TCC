<?php

namespace App\Filament\Widgets\Administrativo\Concerns;

use App\Models\Administrativo\Demanda;
use App\Models\Administrativo\RegistroFuncional;
use App\Models\Administrativo\StatusTarefa;
use App\Models\Administrativo\Tarefas;
use App\Models\Contratacao\Processo;
use App\Models\Contratacao\ProcessoCursos;
use App\Models\Escola\Cursos;
use App\Models\User;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

trait InteractsWithCronogramaTasks
{
    protected function getTaskListForRender(): array
    {
        if (! $this->taskListLoaded) {
            $this->loadTasks();
        }

        return $this->taskList;
    }

    public function loadTasks(): void
    {
        $query = Tarefas::query()
            ->with([
                'demanda',
                'demanda.cursos.processos',
                'demanda.tipodemanda',
                'registro.pessoa',
                'status'
            ])
            ->where('ativo', true);

        if (!$this->isManager) {
            $query->whereHas('registro', function (Builder $q) {
                $q->where('pessoa_id', Auth::id());
            });
        }

        $filters = $this->taskFilters;

        if (!empty($filters['status_id'])) {
            $query->where('status_id', $filters['status_id']);
        }

        if (!empty($filters['user_id'])) {
            $query->whereHas('registro', function ($q) use ($filters) {
                $q->where('pessoa_id', $filters['user_id']);
            });
        }
        if (!empty($filters['data_inicio'])) {
            $query->whereDate('created_at', '>=', $filters['data_inicio']);
        }
        if (!empty($filters['data_fim'])) {
            $query->whereDate('created_at', '<=', $filters['data_fim']);
        }

        $sortOption = $filters['ordenacao'] ?? 'created_at_desc';

        switch ($sortOption) {
            case 'created_at_asc':
                $query->orderBy('created_at', 'asc');
                break;
            case 'status_asc':
                $query->orderBy('status_id', 'asc');
                break;
            case 'prazo_asc':
                $query->orderByRaw("CAST(JSON_UNQUOTE(JSON_EXTRACT(json, '$.data_prevista')) AS DATE) ASC");
                break;
            case 'prazo_desc':
                $query->orderByRaw("CAST(JSON_UNQUOTE(JSON_EXTRACT(json, '$.data_prevista')) AS DATE) DESC");
                break;
            case 'created_at_desc':
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        $this->taskList = $query->get()
            ->map(function ($tarefa) {
                $taskJson = $this->normalizarArray($tarefa->json ?? []);
                $dataPrevista = data_get($taskJson, 'data_prevista');

                if (filled($dataPrevista)) {
                    $data_prevista = Carbon::parse($dataPrevista)->format('d/m/Y');
                } else {
                    $data_prevista = '';
                }
                return [
                    'id' => $tarefa->id,
                    'descricao' => $tarefa->descricao,
                    // Cor definida na Tarefa ou fallback baseada no tipo de demanda
                    'cor' => $tarefa->cor ?? ($tarefa->demanda?->tipodemanda_id == 3101 ? 'gray' : 'primary'),

                    // Aqui chamamos os Accessors criados no Model Tarefas.php
                    'contexto' => $tarefa->contexto,
                    'tipo_contexto' => $tarefa->tipo_contexto,

                    'status_descricao' => $tarefa->status->descricao ?? 'Pendente',
                    'responsavel' => $tarefa->registro?->pessoa?->name ?? 'Não Atribuído',
                    'responsavel_avatar' => $this->getUsuarioAvatarUrl($tarefa->registro?->pessoa),
                    'is_assigned' => !is_null($tarefa->registrofuncional_id),
                    'situacao' => $tarefa->status_id,
                    'demanda_id' => $tarefa->demanda_id,
                    'data_prevista' => $data_prevista,
                    'json' => $this->normalizarHistoricoTarefa($taskJson),

                    'created_at' => $tarefa->created_at->format('d/m/Y'),
                ];
            })->values()->toArray();
        $this->taskListLoaded = true;
    }

    public function setTaskViewType($type)
    {
        $this->taskViewType = $type;
    }

    public function defaultTaskFiltersForm(Schema $schema): Schema
    {
        return $schema
            ->statePath('taskFilters');
    }

    public function taskFiltersForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make([
                    'default' => 1,
                    'sm' => 2,
                    'md' => 3,
                    'lg' => 6,
                ])->schema([
                    Select::make('status_id')
                        ->label('Situação')
                        ->options(fn(): array => $this->getStatusTarefaOptions())
                        ->placeholder('Todas')
                        ->live(),

                    Select::make('user_id')
                        ->label('Responsável')
                        ->options(fn(): array => $this->getUsuarioAtivoOptions())
                        ->searchable()
                        ->placeholder('Todos')
                        ->live(),

                    DatePicker::make('data_inicio')
                        ->label('De')
                        ->live(),

                    DatePicker::make('data_fim')
                        ->label('Até')
                        ->live(),

                    Select::make('ordenacao')
                        ->label('Ordenação')
                        ->options([
                            'created_at_desc' => 'Mais recentes',
                            'created_at_asc' => 'Mais antigas',
                            'status_asc' => 'Status',
                            'prazo_asc' => 'Prazo crescente',
                            'prazo_desc' => 'Prazo decrescente',
                        ])
                        ->default('created_at_desc')
                        ->live(),
                ]),
            ]);
    }

    protected function getStatusTarefaOptions(): array
    {
        return Cache::store('file')->remember(
            'evento-calendar-widget:status-tarefa-options',
            now()->addMinutes(10),
            fn(): array => StatusTarefa::query()
                ->orderBy('descricao')
                ->pluck('descricao', 'id')
                ->toArray(),
        );
    }

    protected function getUsuarioAtivoOptions(?int $exceptId = null): array
    {
        $options = Cache::store('file')->remember(
            'evento-calendar-widget:usuario-ativo-options',
            now()->addMinutes(5),
            fn(): array => User::query()
                ->where('ativo', true)
                ->orderBy('name')
                ->pluck('name', 'id')
                ->toArray(),
        );

        if ($exceptId) {
            unset($options[$exceptId]);
        }

        return $options;
    }

    protected function getDemandaAtivaOptions(): array
    {
        return Cache::store('file')->remember(
            'evento-calendar-widget:demanda-ativa-options:' . Session::get('setorprincipal', 'sem-setor'),
            now()->addMinutes(5),
            fn(): array => Demanda::query()
                ->where('ativo', true)
                ->orderBy('titulo')
                ->pluck('titulo', 'id')
                ->toArray(),
        );
    }

    protected function getUsuarioAvatarUrl(?User $user): string
    {
        return $user?->getFilamentAvatarUrl() ?: asset('images/admin-tie.png');
    }

    protected function normalizarHistoricoTarefa(array $json): array
    {
        $historico = data_get($json, 'historico');

        if (! is_array($historico)) {
            $historico = collect($json)
                ->filter(fn($item): bool => is_array($item) && isset($item['evento']))
                ->values()
                ->all();
        }

        return collect($historico)
            ->filter(fn($item): bool => is_array($item))
            ->slice(-20)
            ->values()
            ->all();
    }

    protected function buscarCursosTarefa(string $search): array
    {
        return Cursos::query()
            ->where('ativo', true)
            ->when(
                filled($search),
                fn(Builder $query): Builder => $query->where('titulo', 'like', "%{$search}%"),
            )
            ->orderBy('titulo')
            ->limit(50)
            ->pluck('titulo', 'id')
            ->toArray();
    }

    protected function getCursoTarefaLabel($value): ?string
    {
        if (blank($value)) {
            return null;
        }

        return Cursos::query()
            ->whereKey((int) $value)
            ->value('titulo');
    }

    public function updatedTaskFilters(): void
    {
        // Reseta a paginação se você estiver usando (opcional, mas recomendado)
        // $this->resetPage();

        // Recarrega as tarefas com os novos filtros
        $this->loadTasks();
    }

    public function assignTaskAction(): Action
    {
        return Action::make('assignTask')
            ->label('Atribuir Responsável')
            ->icon('heroicon-o-user-plus')
            ->color('primary')
            ->visible(fn() => $this->isManager) // Só gestor vê
            ->modalHeading('Atribuir Tarefa')
            ->modalDescription('Selecione o colaborador. Será criado um vínculo de Escala de Trabalho na demanda se não existir.')
            ->schema([
                Select::make('user_id')
                    ->label('Colaborador')
                    ->options(fn(): array => $this->getUsuarioAtivoOptions())
                    ->searchable()
                    ->required(),

                Select::make('prazo_tipo')
                    ->label('Prazo')
                    ->options([
                        'demanda' => 'Seguir prazo da Demanda',
                        'hoje' => 'Para hoje',
                        'personalizado' => 'Personalizado'
                    ])
                    ->default('demanda')
                    ->reactive(),
            ])
            ->action(function (array $data, array $arguments) {
                $tarefa = Tarefas::find($arguments['id']);
                if (!$tarefa) return;

                $userId = $data['user_id'];
                $demanda = $tarefa->demanda;

                // 1. Verifica se já existe Registro Funcional de Escala (2409) para este User nesta Demanda
                $registro = RegistroFuncional::where('pessoa_id', $userId)
                    ->where('demanda_id', $demanda->id)
                    ->where('tipo_id', 2409) // REGISTRO_FUNCIONAL - Escala de Trabalho
                    ->first();

                // 2. Se não existir, cria o Registro Funcional
                if (!$registro) {
                    $inicio = $demanda->data_prevista ?? now();
                    $termino = $demanda->data_termino ?? now()->addDays(7);

                    $registro = RegistroFuncional::create([
                        'tipo_id' => 2409, // Escala de Trabalho
                        'pessoa_id' => $userId,
                        'demanda_id' => $demanda->id,
                        'curso_id' => $demanda->cursos()->first()?->id, // Vincula curso se houver (via Demanda)
                        'atividade_id' => null, // Ajustar se necessário
                        'inicio' => $inicio,
                        'termino' => $termino,
                        'situacao' => true, // Ativo
                        'processo' => 'Atribuição automática via Tarefa',
                        'atualizado_por' => Auth::id(),
                    ]);

                    $usuarioResponsavel = User::find($userId);
                    if ($usuarioResponsavel && $usuarioResponsavel->id !== Auth::id()) {
                        Notification::make()
                            ->title('Você foi atribuído a uma tarefa')
                            ->body("Tarefa: {$tarefa->descricao}")
                            ->actions([
                                Action::make('ver')
                                    ->button()
                                    ->markAsRead()
                            ])
                            ->sendToDatabase($usuarioResponsavel);
                    }

                    Notification::make()
                        ->title('Escala de Trabalho criada para o usuário.')
                        ->info()
                        ->send();
                }

                // 3. Vincula a Tarefa ao Registro Funcional
                $tarefa->update([
                    'registrofuncional_id' => $registro->id,
                    'status_id' => config('settings.situacaotarefa.pendente') ?? 2, // Define status inicial
                    'atualizado_por' => Auth::id()
                ]);

                Notification::make()
                    ->title('Tarefa atribuída com sucesso!')
                    ->success()
                    ->send();
                $this->loadTasks();
            });
    }

    public function reassignTaskAction(): Action
    {
        return Action::make('reassignTask')
            ->label('Reatribuir')
            ->icon('heroicon-o-arrow-path-rounded-square')
            ->color('warning')
            ->visible(fn() => $this->isManager) // Apenas gestor
            ->modalHeading('Reatribuir Tarefa')
            ->modalDescription('Selecione o novo responsável. O histórico será registrado na descrição da tarefa.')
            ->schema([
                // 1. Campo Oculto para segurar o ID da tarefa dentro do formulário
                Hidden::make('task_id'),

                // 2. Select usando 'Get' para ler o ID do campo oculto
                Select::make('user_id')
                    ->label('Novo Responsável')
                    ->options(function (Get $get) {
                        // Recupera o ID que foi preenchido no mountUsing
                        $taskId = $get('task_id');

                        // Busca o responsável atual para excluí-lo da lista
                        $tarefa = Tarefas::find($taskId);
                        $atualId = $tarefa?->registro?->pessoa_id;

                        return $this->getUsuarioAtivoOptions($atualId);
                    })
                    ->searchable()
                    ->required(),

                Textarea::make('motivo')
                    ->label('Motivo da Reatribuição')
                    ->required()
                    ->rows(2)
                    ->placeholder('Ex: Mudança de setor, férias, rebalanceamento de carga...'),
            ])
            ->mountUsing(function (Schema $form, array $arguments) {
                // 3. Preenche o formulário (incluindo o Hidden) com os argumentos recebidos do Blade
                $form->fill([
                    'task_id' => $arguments['id'] ?? null,
                ]);
            })
            ->action(function (array $data) {
                $tarefa = Tarefas::find($data['task_id']);
                if (!$tarefa) return;

                $novoUserId = $data['user_id'];
                $motivo = $data['motivo'];
                $demanda = $tarefa->demanda;

                // Dados do antigo responsável
                $antigoRegistro = $tarefa->registro;
                $antigoNome = $antigoRegistro ?$antigoRegistro->pessoa->name : 'Não atribuído';
                $novoNome = User::find($novoUserId)->name;
                $autorNome = Auth::user()->name;

                // 1. Lógica de Escala (Registro Funcional)
                $novoRegistro = RegistroFuncional::where('pessoa_id', $novoUserId)
                    ->where('demanda_id', $demanda->id)
                    ->where('tipo_id', 2409)
                    ->first();

                if (!$novoRegistro) {
                    $inicio = $demanda->data_prevista ?Carbon::parse($demanda->data_prevista) : now();
                    $termino = $demanda->data_termino ?Carbon::parse($demanda->data_termino) : now()->addDays(7);

                    $novoRegistro = RegistroFuncional::create([
                        'tipo_id' => 2409,
                        'pessoa_id' => $novoUserId,
                        'demanda_id' => $demanda->id,
                        'curso_id' => $demanda->cursos()->first()?->id,
                        'atividade_id' => null,
                        'inicio' => $inicio,
                        'termino' => $termino,
                        'situacao' => true,
                        'processo' => 'Reatribuição de Tarefa (Origem: ' . $antigoNome . ')',
                        'atualizado_por' => Auth::id(),
                    ]);
                }

                // 2. LOG DE HISTÓRICO EM JSON
                $json = $tarefa->json ?? [];
                if (!is_array($json)) {
                    $json = [];
                }
                $historicoAtual = $json['historico'] ?? [];
                if (empty($historicoAtual) && !empty($json) && isset($json[0])) {
                    $historicoAtual = $json; // Legado: assume que tudo era histórico
                }
                $json['historico'] = $historicoAtual;

                // Adiciona o novo evento no topo (array_unshift) ou final (array_push/[])
                // Usando [] adiciona ao final.
                $historicoAtual[] = [
                    'evento' => 'REATRIBUIÇÃO',
                    'data' => now()->toDateTimeString(),
                    'de' => $antigoNome,
                    'para' => $novoNome,
                    'por' => $autorNome,
                    'motivo' => $motivo
                ];

                // 3. Atualiza a Tarefa
                $tarefa->update([
                    'registrofuncional_id' => $novoRegistro->id,
                    'json' => $json,
                    'atualizado_por' => Auth::id()
                ]);

                // Opcional: Log no registro antigo
                if ($antigoRegistro) {
                    $antigoRegistro->update([
                        'observacao' => ($antigoRegistro->observacao ?? '') . "\n[Sistema] Tarefa transferida em " . now()->format('d/m/Y')
                    ]);
                }

                Notification::make()
                    ->title('Tarefa reatribuída com sucesso!')
                    ->success()
                    ->send();

                $this->loadTasks();
            });
    }

    public function updateTaskStatusAction(): Action
    {
        return Action::make('updateTaskStatus')
            ->label('Atualizar Status')
            ->icon('heroicon-o-pencil-square')
            ->modalHeading('Atualizar Andamento')
            ->schema([
                Select::make('status_id')
                    ->label('Situação')
                    ->options(fn(): array => $this->getStatusTarefaOptions())
                    ->required(),

                Textarea::make('observacao')
                    ->label('Observações / Entregáveis')
                    ->rows(3)
            ])
            ->action(function (array $data, array $arguments) {
                $tarefa = Tarefas::find($arguments['id']);
                if (!$tarefa) return;

                $tarefa->update([
                    'status_id' => $data['status_id'],
                    'descricao' => $tarefa->descricao . ($data['observacao'] ?"\nObs: " . $data['observacao'] : ''), // Append obs
                    'data_concluida' => $data['status_id'] == config('settings.situacaotarefa.concluido') ?now() : null,
                    'atualizado_por' => Auth::id()
                ]);

                $gestorId = $tarefa->registro?->atualizado_por;
                $gestor = $gestorId ?User::find($gestorId) : null;
                $usuarioNome = Auth::user()->name;

                if ($gestor && $gestor->id !== Auth::id()) {
                    Notification::make()
                        ->title('Tarefa Atualizada')
                        ->body("{$usuarioNome} atualizou a tarefa: " . Str::limit($tarefa->descricao, 30))
                        ->success()
                        ->sendToDatabase($gestor);
                }
                Notification::make()
                    ->title('Tarefa atualizada!')
                    ->success()
                    ->send();
                $this->loadTasks();
            });
    }

    public function editTaskAction(): Action
    {
        return Action::make('editTask')
            ->label('Editar Tarefa Completa')
            ->icon('heroicon-o-pencil')
            ->color('primary')
            ->visible(fn() => $this->isManager) // Segurança: Apenas gestores
            ->modalHeading('Edição Completa da Tarefa')
            ->modalWidth('2xl')
            ->schema([
                Grid::make(2)->schema([
                    // Edição da Descrição
                    Textarea::make('descricao')
                        ->label('Descrição')
                        ->required()
                        ->rows(3)
                        ->columnSpanFull(),

                    // Edição da Demanda (Relacionamento Pai)
                    Select::make('demanda_id')
                        ->label('Demanda Vinculada')
                        ->options(fn(): array => $this->getDemandaAtivaOptions())
                        ->searchable()
                        ->preload()
                        ->required()
                        ->columnSpanFull(),

                    // Edição de Data (Campo JSON)
                    DatePicker::make('data_prevista')
                        ->label('Data Prevista')
                        ->displayFormat('d/m/Y')
                        ->format('Y-m-d') // Formato banco/json
                        ->columnSpan(1),

                    // Edição de Status
                    Select::make('status_id')
                        ->label('Situação')
                        ->options(fn(): array => $this->getStatusTarefaOptions())
                        ->required()
                        ->columnSpan(1),

                    // Edição de Cor (Visualização)
                    Select::make('cor')
                        ->label('Cor do Cartão')
                        ->options([
                            'gray' => 'Cinza (Padrão)',
                            'primary' => 'Azul (Pedagógico)',
                            'warning' => 'Amarelo (Contratação)',
                            'danger' => 'Vermelho (Urgente)',
                            'success' => 'Verde'
                        ])
                        ->columnSpan(1),
                ])
            ])
            ->mountUsing(function (Schema $form, array $arguments) {
                $tarefa = Tarefas::find($arguments['id']);
                if (!$tarefa) return;

                // Extrai a data prevista do JSON
                $json = $tarefa->json ?? [];
                $dataPrevista = $json['data_prevista'] ?? null;

                $form->fill([
                    'descricao' => $tarefa->descricao,
                    'demanda_id' => $tarefa->demanda_id,
                    'status_id' => $tarefa->status_id,
                    'cor' => $tarefa->cor,
                    'data_prevista' => $dataPrevista,
                ]);
            })
            ->action(function (array $data, array $arguments) {
                $tarefa = Tarefas::find($arguments['id']);
                if (!$tarefa) return;

                $json = $tarefa->json ?? [];
                if (!is_array($json)) {
                    $json = [];
                }
                $json['data_prevista'] = $data['data_prevista'];

                $tarefa->update([
                    'descricao' => $data['descricao'],
                    'demanda_id' => $data['demanda_id'],
                    'status_id' => $data['status_id'],
                    'cor' => $data['cor'],
                    'json' => $json, // Salva o JSON atualizado
                    'atualizado_por' => Auth::id()
                ]);


                $usuarioResponsavel = $tarefa->registro?->pessoa;
                if ($usuarioResponsavel && $usuarioResponsavel->id !== Auth::id()) {
                    Notification::make()
                        ->title('Tarefa Atualizada pelo Gestor')
                        ->body("A tarefa '{$tarefa->descricao}' sofreu alterações.")
                        ->warning() // Cor diferente para chamar aten??o
                        ->sendToDatabase($usuarioResponsavel);
                }
                Notification::make()
                    ->title('Tarefa editada com sucesso!')
                    ->success()
                    ->send();
                $this->loadTasks();
            });
    }

    public function toggleTaskStatusAction(): Action
    {
        return Action::make('toggleTaskStatus')
            ->label(fn(array $arguments) => $arguments['status'] ?'Reabrir Tarefa' : 'Concluir Tarefa')
            ->icon(fn(array $arguments) => $arguments['status'] ?'heroicon-o-arrow-path' : 'heroicon-o-check')
            ->color(fn(array $arguments) => $arguments['status'] ?'gray' : 'success')
            ->requiresConfirmation(fn(array $arguments) => !$arguments['status']) // Pede confirmação apenas para concluir
            ->modalHeading('Concluir Tarefa')
            ->modalDescription('Deseja marcar este registro como concluído?? Você pode adicionar uma observação final.')
            ->schema([
                Textarea::make('observacao')
                    ->label('Observação de Conclusão')
                    ->rows(3)
            ])
            ->action(function (array $arguments, array $data) {
                $registro = RegistroFuncional::find($arguments['id']);

                if (!$registro) {
                    Notification::make()->title('Erro')->body('Registro não encontrado')->danger()->send();
                    return;
                }

                $novoStatus = !$registro->situacao;

                $updateData = [
                    'situacao' => $novoStatus,
                    'atualizado_por' => Auth::id(), // Rastreabilidade
                ];

                // Se houver observação no modal, anexa ? existente ou substitui
                if (!empty($data['observacao'])) {
                    $updateData['observacao'] = ($registro->observacao ?$registro->observacao . "\n\n" : "") .
                        "Conclusão: " . $data['observacao'];
                }

                $registro->update($updateData);


                $gestorId = $registro->atualizado_por;
                $tarefa = Tarefas::where('registrofuncional_id', $registro->id)->first();
                $gestor = $registro->demanda?->atualizado?->id ?User::find($registro->demanda->atualizado_por) : null;
                if ($gestor && $gestor->id !== Auth::id()) {
                    $estado = $novoStatus ?'Reaberta' : 'Concluída';
                    Notification::make()
                        ->title("Tarefa {$estado}")
                        ->body(Auth::user()->name . " alterou o status da tarefa.")
                        ->sendToDatabase($gestor);
                }

                Notification::make()
                    ->title($novoStatus ?'Tarefa Concluída!' : 'Tarefa Reaberta!')
                    ->success()
                    ->send();

                $this->loadTasks(); // Recarrega a lista
            });
    }

    public function createTaskAction(): Action
    {
        return Action::make('createTask')
            ->label('Nova Tarefa')
            ->icon('heroicon-o-plus-circle')
            ->color('primary')
            ->modalHeading('Nova Tarefa e Escala de Trabalho')
            ->modalWidth('2xl')
            ->schema([
                Grid::make(2)->schema([
                    // 1. Seleção do Fluxo (Regra de Negócio)
                    Select::make('tipo_fluxo')
                        ->label('Tipo de Fluxo')
                        ->options([
                            'adm' => 'Administrativa',
                            'contratacao' => 'Contratação',
                            'pedagogica' => 'Pedagógica',
                        ])
                        ->default('adm')
                        // ->live() // Reage para mostrar/esconder campos
                        ->required()
                        ->columnSpanFull(),

                    // 2. Dados Básicos da Tarefa
                    Select::make('user_id')
                        ->label('Responsável (Pessoa)')
                        ->options(
                            User::whereHas('lotacao', function (Builder $query) {
                                $query->where('setor_id', Session::get('setorprincipal'));
                            })->pluck('name', 'id')
                        )
                        ->searchable()
                        ->required()
                        ->columnSpan(2),

                    Textarea::make('descricao')
                        ->label('Descrição da Tarefa')
                        ->required()
                        ->rows(2)
                        ->columnSpan(2),

                    // 3. Demanda (Comum a todos, mas filtra tipo por fluxo)
                    Select::make('demanda_id')
                        ->label('Demanda')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->options(function (Get $get) {
                            // Filtra demandas existentes pelo tipo selecionado
                            $tipoId = match ($get('tipo_fluxo')) {
                                'adm' => 3101,
                                'contratacao' => 3103,
                                'pedagogica' => 3102,
                                default => 3101
                            };
                            return Demanda::where('tipodemanda_id', $tipoId)->pluck('titulo', 'id');
                        })
                        // PERMITIR CRIAR DEMANDA SE NÃO EXISTIR
                        ->createOptionForm([
                            TextInput::make('titulo')
                                ->label('Título da Demanda')
                                ->required()
                                ->maxLength(255),
                            Textarea::make('descricao')
                                ->label('Descrição')
                                ->required()
                                ->rows(2),
                        ])
                        ->createOptionUsing(function (array $data, Get $get) {
                            $tipoId = match ($get('tipo_fluxo')) {
                                'adm' => 3101,
                                'contratacao' => 3103,
                                'pedagogica' => 3102,
                                default => 3101
                            };
                            $demanda = Demanda::create([
                                'titulo' => $data['titulo'],
                                'descricao' => $data['descricao'],
                                'tipodemanda_id' => $tipoId,
                                'list_id' => 1, // Padrão
                                'card_id' => 1, // Padrão
                                'ativo' => true,
                                'atualizado_por' => Auth::id(),
                            ]);
                            return $demanda->id;
                        })
                        ->columnSpan(2),

                    // 4. Processo (Apenas Contratação e Pedagógica)
                    Select::make('processo_id')
                        ->label('Processo')
                        // ->visible(fn(Get $get) => in_array($get('tipo_fluxo'), ['contratacao', 'pedagogica']))
                        ->required(fn(Get $get) => in_array($get('tipo_fluxo'), ['contratacao', 'pedagogica']))
                        ->searchable()
                        ->options(Processo::where('ativo', true)->pluck('numero', 'id'))
                        ->createOptionForm([
                            TextInput::make('numero')
                                ->label('Número do Processo')
                                ->required(),
                            TextInput::make('descricao')
                                ->label('Objeto/Descrição')
                                ->required(),
                        ])
                        ->createOptionUsing(function (array $data) {
                            $processo = Processo::create([
                                'numero' => $data['numero'],
                                'descricao' => $data['descricao'],
                                'ativo' => true,
                                'atualizado_por' => Auth::id(),
                                // Adicione setor_id ou tipoprocesso_id padrões se necessário
                            ]);
                            return $processo->id;
                        })
                        ->columnSpan(2),

                    // 5. Curso (Apenas Pedagógica)
                    Select::make('curso_id')
                        ->label('Curso')
                        // ->visible(fn(Get $get) => $get('tipo_fluxo') === 'pedagogica')
                        ->required(fn(Get $get) => $get('tipo_fluxo') === 'pedagogica')
                        ->searchable()
                        ->getSearchResultsUsing(fn(string $search): array => $this->buscarCursosTarefa($search))
                        ->getOptionLabelUsing(fn($value): ?string => $this->getCursoTarefaLabel($value))
                        ->createOptionForm([
                            TextInput::make('titulo')
                                ->label('Nome do Curso')
                                ->required()
                                ->maxLength(255),
                            TextInput::make('carga_horaria')
                                ->numeric()
                                ->label('Carga Horária'),
                        ])
                        ->createOptionUsing(function (array $data, Get $get) {
                            // Cria curso vinculado ? Demanda selecionada (se já selecionada no form principal,
                            // mas aqui no createOption é isolado, então criamos o curso e vinculamos depois)
                            $curso = Cursos::create([
                                'titulo' => $data['titulo'],
                                'carga_horaria' => $data['carga_horaria'] ?? 0,
                                'ativo' => true,
                                'modalidade_id' => config('settings.modalidade.presencial') ?? 1, // Valor padrão
                                'atualizado_por' => Auth::id(),
                            ]);
                            return $curso->id;
                        })
                        ->columnSpan(2),

                    DatePicker::make('data_prevista')
                        ->label('Data Prevista')
                        ->columnSpan(1),
                ])
            ])
            ->action(function (array $data) {
                // 1. Recupera ou cria os IDs principais
                $userId = $data['user_id'];
                $demandaId = $data['demanda_id'];
                $processoId = $data['processo_id'] ?? null;
                $cursoId = $data['curso_id'] ?? null;
                $data_prevista = $data['data_prevista'] ?? null;

                $jsonContent = [
                    'data_prevista' => $data_prevista,
                    'historico' => [] // Inicializa array vazio para o histórico
                ];

                // Lógica Específica por Fluxo
                //if ($data['tipo_fluxo'] === 'pedagogica') {
                // Atualiza o Curso para pertencer ? Demanda (se criado agora)
                if ($cursoId) {
                    Cursos::where('id', $cursoId)->update(['demanda_id' => $demandaId]);

                    // REGRA 3: Relacionar curso ao processo (Tabela pivot cnt_processocursos)
                    if ($processoId) {
                        // Verifica se já existe o vínculo para não duplicar
                        $existeVinculo = ProcessoCursos::where('processo_id', $processoId)
                            ->where('curso_id', $cursoId)
                            ->exists();

                        if (!$existeVinculo) {
                            ProcessoCursos::create([
                                'processo_id' => $processoId,
                                'curso_id' => $cursoId,
                                // 'instrutor_id' => ... se houver
                            ]);
                        }
                    }
                }
                //}

                // 2. Cria o Registro Funcional (Escala de Trabalho - 2409)
                // Verifica se já existe uma escala aberta para esse usuário nessa demanda
                $registro = RegistroFuncional::where('pessoa_id', $userId)
                    ->where('demanda_id', $demandaId)
                    ->where('tipo_id', 2409) // Escala de Trabalho
                    ->first();

                if (!$registro) {
                    $registro = RegistroFuncional::create([
                        'tipo_id' => 2409, // Escala de Trabalho
                        'pessoa_id' => $userId,
                        'demanda_id' => $demandaId,
                        'curso_id' => $cursoId, // Se pedagógica
                        'atividade_id' => null,
                        'inicio' => now(), // Define início agora
                        'termino' => now()->addDays(15), // Prazo padrão sugerido
                        'situacao' => true, // Ativo
                        'processo' => "Tarefa: " . \Illuminate\Support\Str::limit($data['descricao'], 50),
                        'atualizado_por' => Auth::id(),
                    ]);
                }

                // 3. Cria a Tarefa vinculada ao Registro
                Tarefas::create([
                    'setor_id' => User::find(Auth::user()->id)->setorPrincipal()->first()?->id ?? 1, // Setor do logado ou padrão
                    'demanda_id' => $demandaId,
                    'registrofuncional_id' => $registro->id, // Vincula ? pessoa via registro
                    'descricao' => $data['descricao'],
                    'status_id' => config('settings.situacaotarefa.pendente') ?? 1,
                    'cor' => match ($data['tipo_fluxo']) {
                        'adm' => 'gray',
                        'contratacao' => 'warning',
                        'pedagogica' => 'primary',
                        default => 'gray'
                    },
                    'json' => $jsonContent,
                    'ativo' => true,
                    'atualizado_por' => Auth::id(),
                    'data_iniciada' => now(),
                    'list_id' => 1, // Padrão Kanban
                    'card_id' => 1, // Padrão Kanban
                ]);


                $usuarioResponsavel = User::find($userId);

                if ($usuarioResponsavel && $usuarioResponsavel->id !== Auth::id()) {
                    Notification::make()
                        ->title('Nova Tarefa Atribuída')
                        ->body("Você recebeu uma nova tarefa: {$data['descricao']}")
                        ->actions([
                            Action::make('visualizar')
                                ->button()
                                ->markAsRead() // Marca como lida ao clicar
                        ])
                        ->sendToDatabase($usuarioResponsavel);
                }

                Notification::make()
                    ->title('Tarefa criada com sucesso!')
                    ->body("Registro funcional vinculado ao usuário.")
                    ->success()
                    ->send();

                $this->loadTasks(); // Atualiza a lista
            });
    }
}
