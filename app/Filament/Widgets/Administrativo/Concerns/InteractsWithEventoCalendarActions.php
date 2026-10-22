<?php

namespace App\Filament\Widgets\Administrativo\Concerns;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use App\Models\Administrativo\Agenda;
use App\Models\Administrativo\Demanda;
use App\Models\Administrativo\RegistroFuncional;
use App\Models\Administrativo\StatusTarefa;
use App\Models\Administrativo\Tarefas;
use App\Models\Contratacao\Processo;
use App\Models\Contratacao\ProcessoCursos;
use App\Filament\Resources\Escola\AcaoResource;
use App\Filament\Resources\Escola\CursoAtividadesResource;
use App\Filament\Resources\Escola\CursosResource;
use App\Forms\Components\DateRangePickerV2;
use App\Models\Escola\Acao;
use App\Models\Escola\CursoAtividades;
use App\Models\Escola\CursoFrequencias;
use App\Models\Escola\Cursos;
use App\Models\Escola\TipoLocal;
use App\Models\User;
use App\Services\AgendaService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TimePicker;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema as DatabaseSchema;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Livewire\Attributes\Renderless;

trait InteractsWithEventoCalendarActions
{
    public function eventoListaEditForm(Schema $schema): Schema
    {
        return $schema
            ->statePath('eventoListaForm')
            ->components($this->getEventoListaEditSchema());
    }

    protected function getEventoListaEditSchema(): array
    {
        return [
            Hidden::make('tipo_registro'),

            Tabs::make('Cronograma')
                ->tabs([
                    Tab::make('Dados gerais')
                        ->schema([
                            Fieldset::make('Identificação')
                                ->columns([
                                    'default' => 1,
                                    'lg' => 3,
                                ])
                                ->schema([
                                    TextInput::make('titulo')
                                        ->label('Título')
                                        ->required()
                                        ->maxLength(255)
                                        ->columnSpan([
                                            'default' => 1,
                                            'lg' => 2,
                                        ]),

                                    Textarea::make('comentario_titulo')
                                        ->label('Comentário sobre o título')
                                        ->rows(2),
                                ]),

                            Fieldset::make('Local')
                                ->columns([
                                    'default' => 1,
                                    'lg' => 3,
                                ])
                                ->schema([
                                    Select::make('local_id')
                                        ->label('Local')
                                        ->options(fn(): array => $this->getTipoLocalOptions())
                                        ->searchable()
                                        ->placeholder('A definir')
                                        ->columnSpan([
                                            'default' => 1,
                                            'lg' => 2,
                                        ]),

                                    Textarea::make('comentario_local')
                                        ->label('Comentário sobre local')
                                        ->rows(2),
                                ]),

                            Fieldset::make('Informações adicionais')
                                ->schema([
                                    RichEditor::make('informacoes_adicionais')
                                        ->label('Informações adicionais')
                                        ->columnSpanFull(),
                                ]),
                        ]),

                    Tab::make('Corpo docente')
                        ->schema([
                            Fieldset::make('Docentes')
                                ->columns([
                                    'default' => 1,
                                    'lg' => 3,
                                ])
                                ->schema([
                                    Select::make('instrutor_user_ids')
                                        ->label('Usuários vinculados')
                                        ->multiple()
                                        ->searchable()
                                        ->preload()
                                        ->placeholder('Selecione um ou mais docentes')
                                        ->getSearchResultsUsing(fn(string $search): array => $this->buscarUsuariosInstrutores($search))
                                        ->getOptionLabelsUsing(fn(array $values): array => $this->getUsuarioLabels($values))
                                        ->columnSpan([
                                            'default' => 1,
                                            'lg' => 2,
                                        ]),

                                    Textarea::make('comentario_instrutores')
                                        ->label('Comentário sobre corpo docente')
                                        ->rows(2)
                                        ->placeholder('Ex.: processo de contratação em andamento'),

                                    Repeater::make('instrutores_sem_cadastro')
                                        ->label('Docentes sem cadastro')
                                        ->addActionLabel('Adicionar docente por nome')
                                        ->defaultItems(0)
                                        ->reorderable(false)
                                        ->collapsible()
                                        ->collapsed()
                                        ->columns([
                                            'default' => 1,
                                            'lg' => 2,
                                        ])
                                        ->schema([
                                            TextInput::make('nome')
                                                ->label('Nome')
                                                ->maxLength(255)
                                                ->required(),
                                            Textarea::make('comentario')
                                                ->label('Comentário')
                                                ->rows(2),
                                        ])
                                        ->columnSpanFull(),
                                ]),
                        ]),
                ])
                ->columnSpanFull(),
        ];
    }

    protected function buscarUsuariosInstrutores(string $search): array
    {
        return User::query()
            ->where('ativo', true)
            ->when(
                filled($search),
                fn(Builder $query): Builder => $query->where('name', 'like', "%{$search}%"),
            )
            ->orderBy('name')
            ->limit(50)
            ->pluck('name', 'id')
            ->toArray();
    }

    protected function getUsuarioLabels(array $values): array
    {
        $ids = collect($values)
            ->filter(fn($id): bool => filled($id))
            ->map(fn($id): int => (int) $id)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return [];
        }

        return User::query()
            ->whereIn('id', $ids)
            ->pluck('name', 'id')
            ->toArray();
    }

    #[Renderless]
    public function buscarOpcoesInlineCronograma(string $tipo, string $busca = ''): array
    {
        $busca = trim($busca);

        return match ($tipo) {
            'instrutores' => User::query()
                ->where('ativo', true)
                ->when(
                    filled($busca),
                    fn(Builder $query): Builder => $query->where('name', 'like', "%{$busca}%"),
                )
                ->orderBy('name')
                ->limit(8)
                ->get(['id', 'name'])
                ->map(fn(User $usuario): array => $this->getInlineOpcaoUsuario($usuario))
                ->values()
                ->all(),
            'locais' => TipoLocal::query()
                ->when(
                    filled($busca),
                    fn(Builder $query): Builder => $query->where('descricao', 'like', "%{$busca}%"),
                )
                ->orderBy('descricao')
                ->limit(8)
                ->get(['id', 'descricao'])
                ->map(fn(TipoLocal $local): array => [
                    'id' => (int) $local->id,
                    'label' => $local->descricao,
                    'type' => 'option',
                ])
                ->values()
                ->all(),
            default => [],
        };
    }

    #[Renderless]
    public function salvarCampoInlineCronograma(int $eventId, string $campo, $valor = null): array
    {
        $agenda = Agenda::with(['agendaable', 'local'])->find($eventId);

        if (!$agenda || !$agenda->agendaable) {
            Notification::make()
                ->title('Evento não encontrado.')
                ->danger()
                ->send();

            return ['ok' => false, 'message' => 'Evento não encontrado.'];
        }

        $registro = $this->getRegistroMacroEventoLista($agenda);
        $this->carregarRelacoesEventoCronograma($registro);

        try {
            match ($campo) {
                'titulo' => $this->salvarTituloInlineCronograma($agenda, $registro, (string) $valor),
                'instrutores' => $this->salvarInstrutoresInlineCronograma($registro, $valor),
                'local_id' => $this->salvarLocalInlineCronograma($agenda, $registro, $valor),
                default => throw new \InvalidArgumentException('Campo inválido.'),
            };
        } catch (\Throwable $exception) {
            report($exception);

            Notification::make()
                ->title('Não foi possível salvar.')
                ->body($exception->getMessage())
                ->danger()
                ->send();

            return ['ok' => false, 'message' => $exception->getMessage()];
        }

        $this->invalidarEventosCronograma();

        return ['ok' => true];
    }

    #[Renderless]
    public function viewEventoAction(): Action
    {
        return Action::make('viewEvento')
            ->label('Detalhes do Evento')
            ->modalHeading(fn($arguments) => $this->getEventoCronogramaHeading($arguments['event_id'] ?? null))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Fechar')
            ->modalWidth('5xl')
            ->schema($this->getEventoCronogramaSchema())
            ->mountUsing(function (Schema $form, array $arguments) {
                $eventId = $arguments['event_id'] ?? null;
                if (!$eventId) return;

                $agenda = $this->getAgendaCronograma((int) $eventId);
                if (!$agenda) return;

                $registro = $agenda->agendaable;
                $this->carregarRelacoesEventoCronograma($registro);

                $this->eventoCronogramaData = $this->getEventoCronogramaData($agenda);
                $form->fill($this->eventoCronogramaData);
            })
            ->extraModalFooterActions([
                Action::make('gerenciarRegistro')
                    ->label('Gerenciar Registro')
                    ->url(fn(array $arguments) => $this->getEventoCronogramaUrl($arguments['event_id'] ?? null))
                    ->visible(fn(array $arguments): bool => filled($this->getEventoCronogramaUrl($arguments['event_id'] ?? null)))
                    ->openUrlInNewTab()
                    ->color('primary'),
            ]);
    }

    public function editarEventoListaAction(): Action
    {
        return Action::make('editarEventoLista')
            ->label('Editar cronograma')
            ->icon('heroicon-o-pencil-square')
            ->modalHeading(fn(array $arguments): string => $this->getEditarEventoListaHeading($arguments['event_id'] ?? null))
            ->modalWidth('6xl')
            ->modalSubmitActionLabel('Salvar')
            ->modalCancelActionLabel('Cancelar')
            ->schema($this->getEventoListaEditSchema())
            ->mountUsing(function (Schema $form, array $arguments): void {
                $eventId = filled($arguments['event_id'] ?? null) ? (int) $arguments['event_id'] : null;

                if (!$eventId) {
                    return;
                }

                $this->preencherEventoListaForm($eventId, $form);
                $this->registrarPresencaEventoLista($eventId, 'formulario');
            })
            ->action(function (array $data, array $arguments): void {
                $eventId = filled($arguments['event_id'] ?? null)
                    ?(int) $arguments['event_id']
                    : $this->eventoListaAbertoId;

                $this->salvarEventoListaData($eventId, $data);
            })
            ->extraModalFooterActions(function (): array {
                return [
                    Action::make('reagendarEventoLista')
                        ->label('Reagendar')
                        ->icon('heroicon-o-calendar-days')
                        ->color('info')
                        ->visible(fn(): bool => filled($this->eventoListaAbertoId))
                        ->action(function (): void {
                            $eventId = $this->eventoListaAbertoId;

                            if (!$eventId) {
                                return;
                            }

                            $this->replaceMountedAction('realocarEventoLista', ['event_id' => $eventId]);
                        }),
                ];
            });
    }

    public function realocarEventoListaAction(): Action
    {
        return Action::make('realocarEventoLista')
            ->label('Reagendar')
            ->icon('heroicon-o-calendar-days')
            ->modalHeading(fn(array $arguments): string => $this->getRealocarEventoListaHeading($arguments['event_id'] ?? null))
            ->modalWidth('7xl')
            ->schema($this->getRealocarEventoListaSchema())
            ->mountUsing(function (Schema $form, array $arguments): void {
                $eventId = filled($arguments['event_id'] ?? null) ? (int) $arguments['event_id'] : null;
                $this->eventoListaRealocarAgendaId = $eventId;
                $this->registrarPresencaEventoLista($eventId, 'agenda');
                $registro = $this->getRegistroRealocacaoEventoLista($eventId);

                if (!$registro) {
                    Notification::make()
                        ->title('Evento não encontrado.')
                        ->danger()
                        ->send();

                    return;
                }

                $form->fill($this->getRealocarEventoListaFormData($registro));
            })
            ->action(function (array $data, array $arguments): void {
                $eventId = filled($arguments['event_id'] ?? null)
                    ?(int) $arguments['event_id']
                    : $this->eventoListaRealocarAgendaId;
                $registro = $this->getRegistroRealocacaoEventoLista($eventId);

                if (!$registro) {
                    Notification::make()
                        ->title('Evento não encontrado.')
                        ->danger()
                        ->send();

                    return;
                }

                if ($registro instanceof Acao) {
                    $this->realocarAgendaAcao($registro, $data);
                } else {
                    AgendaService::realocarAgenda($registro, $data);
                }

                $this->loadEvents(true);
                $this->sairPresencaEventoLista($eventId, 'agenda');
                $this->fecharEventoLista();
            });
    }

    protected function getRealocarEventoListaSchema(): array
    {
        return [
            Tabs::make('Agendamentos')
                ->tabs([
                    Tab::make('Agendamento')
                        ->schema([
                            Repeater::make('agendas')
                                ->label('Agendamentos')
                                ->columns(6)
                                ->columnSpanFull()
                                ->schema([
                                    Hidden::make('id'),

                                    Select::make('agendaable_id')
                                        ->label('Curso/atividade')
                                        ->selectablePlaceholder(false)
                                        ->options(fn(): array => $this->getAgendaableOptionsRealocacaoEventoLista())
                                        ->columnSpan(3),

                                    DatePicker::make('data')
                                        ->label('Data')
                                        ->required()
                                        ->native(false)
                                        ->columnSpan(1),

                                    TimePicker::make('hora_inicio')
                                        ->label('Hora de início')
                                        ->required()
                                        ->columnSpan(1),

                                    TimePicker::make('hora_termino')
                                        ->label('Hora de término')
                                        ->required()
                                        ->columnSpan(1),
                                ]),
                        ]),
                    Tab::make('Intervalos')
                        ->schema([
                            Grid::make()
                                ->columns(8)
                                ->schema([
                                    Select::make('agendaable')
                                        ->label('Curso/atividade')
                                        ->live()
                                        ->options(fn(): array => $this->getAgendaableOptionsRealocacaoEventoLista())
                                        ->columnSpan(3),

                                    DateRangePickerV2::make('periodo_selecionado')
                                        ->label('Selecione o período')
                                        ->agendaableField('agendaable')
                                        ->agendaables(fn(): array => $this->getAgendaablesRealocacaoEventoLista())
                                        ->agendas(fn(): array => $this->getAgendasRealocacaoEventoLista())
                                        ->columnSpanFull(),
                                ]),
                        ]),
                ]),
        ];
    }

    protected function getEventoCronogramaSchema(): array
    {
        return [
            Section::make('Cronograma')
                ->schema([
                    Grid::make(4)->schema([
                        Placeholder::make('data')
                            ->label('Data')
                            ->content(fn(): string => $this->eventoCronogramaData['data'] ?? '-'),
                        Placeholder::make('horario')
                            ->label('Horário')
                            ->content(fn(): string => $this->eventoCronogramaData['horario'] ?? '-'),
                        Placeholder::make('local')
                            ->label('Local')
                            ->content(fn(): string => $this->eventoCronogramaData['local'] ?? '-')
                            ->columnSpan(2),
                        Placeholder::make('acao')
                            ->label('Ação')
                            ->content(fn(): string => $this->eventoCronogramaData['acao'] ?? '-')
                            ->columnSpan(4),
                        Placeholder::make('corpo_docente')
                            ->label('Corpo docente')
                            ->content(fn(): string => $this->eventoCronogramaData['corpo_docente'] ?? '-')
                            ->columnSpan(4),
                        Placeholder::make('observacao')
                            ->label('Observação')
                            ->content(fn(): string => $this->eventoCronogramaData['observacao'] ?? '-')
                            ->columnSpan(4)
                            ->html(),
                    ]),
                ]),
        ];
    }

    protected function getAgendaCronograma(?int $eventId): ?Agenda
    {
        if (!$eventId) {
            return null;
        }

        if (!array_key_exists($eventId, $this->agendaCronogramaCache)) {
            $this->agendaCronogramaCache[$eventId] = Agenda::with(['agendaable', 'local'])->find($eventId);
        }

        return $this->agendaCronogramaCache[$eventId];
    }

    protected function getEventoCronogramaHeading($eventId): string
    {
        if (!$eventId) {
            return 'Detalhes do Evento';
        }

        $agenda = $this->getAgendaCronograma((int) $eventId);

        if (!$agenda) {
            return 'Detalhes do Evento';
        }

        return $this->getEventoCronogramaTitulo($agenda);
    }

    protected function getEditarEventoListaHeading($eventId): string
    {
        if (!$eventId) {
            return 'Editar cronograma';
        }

        $agenda = $this->getAgendaCronograma((int) $eventId);

        if (!$agenda || !$agenda->agendaable) {
            return 'Editar cronograma';
        }

        $registro = $this->getRegistroMacroEventoLista($agenda);

        if ($registro instanceof Acao) {
            return 'Editar ação - ' . ($registro->descricao ?: 'Ação prevista');
        }

        if ($registro instanceof Cursos) {
            return 'Editar curso - ' . ($registro->titulo ?: 'Curso');
        }

        return 'Editar cronograma';
    }

    protected function getEventoCronogramaData(Agenda $agenda): array
    {
        $registro = $agenda->agendaable;

        return [
            'data' => $this->formatarDataCronograma($agenda),
            'acao' => $this->getEventoCronogramaTitulo($agenda),
            'corpo_docente' => $this->getEventoCronogramaCorpoDocente($registro),
            'horario' => $this->formatarHorarioCronograma($agenda),
            'local' => $this->getEventoCronogramaLocal($agenda),
            'observacao' => $this->getEventoCronogramaObservacao($agenda),
        ];
    }

    protected function getRealocarEventoListaHeading($eventId): string
    {
        $registro = $this->getRegistroRealocacaoEventoLista(filled($eventId) ?(int) $eventId : null);

        if ($registro instanceof Acao) {
            return 'Reagendar ação - ' . ($registro->descricao ?: 'Ação prevista');
        }

        if ($registro instanceof Cursos || $registro instanceof CursoAtividades) {
            return 'Reagendar curso - ' . ($registro->titulo ?: 'Curso');
        }

        return 'Reagendar evento';
    }
}
