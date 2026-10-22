<?php

namespace App\Livewire;

use App\Filament\Pages\Escola\RelatoriosEscola;
use App\Models\Escola\CertificadoModelos;
use App\Models\Escola\CursoAtividades;
use App\Models\Escola\Cursos;
use App\Models\Escola\TipoPublico;
use App\Models\Setores;
use App\Models\User;
use App\Services\Auto\AutoBreakpointService;
use App\Services\Auto\AutoConfigFlowService;
use App\Services\Auto\Support\AutoStructureBuilder;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\View;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Maatwebsite\Excel\Concerns\ToArray;

class AutoConfigForm extends Component implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    public Cursos $curso;

    public ?array $data = [];
    public Collection $all_users;
    public Collection $instrutores;

    public function mount(Cursos $curso): void
    {
        Log::info('Montando AutoConfigForm');
        $this->curso = $curso;

        $this->all_users = User::orderBy('name')->get();
        $this->instrutores = $curso->instrutores()->get();


        Log::info('Preenchendo formulário com dados do curso', ['curso_id' => $curso->id, 'inicio_inscricao' => $curso->inicio_inscricao, 'termino_inscricao' => $curso->termino_inscricao]);

        // dd($curso->auto_config);
        if ($curso->auto_config) {
            Log::info('Preenchendo formulário com configuração existente do curso', ['curso_id' => $curso->id]);
            $config = $curso->auto_config;
            $inicio_inscricao = $curso->inicio_inscricao;
            $termino_inscricao = $curso->termino_inscricao;
            $this->form->fill([
                'auto_config' => $config ?? [],
                'inicio_inscricao' => $inicio_inscricao ?? null,
                'termino_inscricao' => $termino_inscricao ?? null,
                'aprovacao_min' => $this->curso->aprovacao,
            ]);
        } else {
            Log::info('Nenhuma configuração existente encontrada para o curso', ['curso_id' => $curso->id]);
            $this->form->fill([
                'auto_config' => [],
                'inicio_inscricao' => $this->curso->inicio_inscricao,
                'termino_inscricao' => $this->curso->termino_inscricao,
                'aprovacao_min' => $this->curso->aprovacao,
            ]);
        }
        Log::info('AutoConfigForm montado com sucesso', ['curso_id' => $curso->id]);
        Log::info('Estado inicial do formulário', ['form_state' => $this->form->getState()]);
    }

    public function configFill(array $config, Get $get, Set $set)
    {
        $cert_config_alunos = $get('auto_config.certificacao.geracao-alunos');
        $cert_config_instrutores = $get('auto_config.certificacao.geracao-instrutores');
        $cert_config_alunos = is_array($cert_config_alunos) ? $cert_config_alunos : [];
        $cert_config_instrutores = is_array($cert_config_instrutores) ? $cert_config_instrutores : [];
        $config = $this->normalizeReusableConfig($config);

        $set('auto_config', $config);
        $set('inicio_inscricao', $this->curso->inicio_inscricao);
        $set('termino_inscricao', $this->curso->termino_inscricao);
        $set('aprovacao_min', $this->curso->aprovacao);

        $set('auto_config.certificacao.geracao-alunos', $cert_config_alunos);
        $set('auto_config.certificacao.geracao-instrutores', $cert_config_instrutores);
        $this->form->clearCachedDefaultChildSchemas();
        // dd($get('auto_config.certificacao.geracao-alunos'), $get('auto_config.certificacao.geracao-instrutores'));
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->statePath('data')
            ->schema([
                Select::make('reutilizar_configuracao')
                    ->label('Reutilizar configuração de outro curso')
                    ->options(fn() => Cursos::where('id', '!=', $this->curso->id)->orderBy('titulo')->pluck('titulo', 'id'))
                    ->searchable()
                    ->suffixAction(
                        Action::make('aplicar')
                            ->icon('heroicon-o-cloud-arrow-down')
                            ->action(function (Get $get, Set $set) {
                                Log::info('Aplicar configuração de outro curso', ['curso_id' => $this->curso->id, 'curso_origem_id' => $get('reutilizar_configuracao')]);
                                $cursoId = $get('reutilizar_configuracao');

                                // dd($get('reutilizar_configuracao'));
                                if ($cursoId) {
                                    $cursoSelecionado = Cursos::find($cursoId);

                                    if ($cursoSelecionado && $cursoSelecionado->auto_config) {
                                        Log::info('Configuração encontrada para o curso selecionado', ['curso_id' => $cursoSelecionado->id]);
                                        $this->configFill($cursoSelecionado->auto_config, $get, $set);
                                    } else {
                                        Notification::make()
                                            ->title('Curso selecionado não possui configuração para reutilizar.')
                                            ->warning()
                                            ->send();
                                    }
                                }
                            }),

                    ),
                Tabs::make('Configuracoes')
                    ->id('config-tabs')
                    ->persistTab()
                    ->tabs([
                        Tab::make('Notificações')
                            ->schema([
                                Grid::make([
                                    'default' => 1,
                                    'lg' => 2,
                                ])->schema([
                                    Section::make('Geral')
                                        ->columns(1)
                                        ->schema([

                                            Toggle::make('auto_config.notificacao.confirmacao_inscricao')
                                                ->label('Email de confirmação de inscrição'),

                                            Toggle::make('auto_config.notificacao.lembrete')
                                                ->label('Lembrete de início do curso'),

                                            Toggle::make('auto_config.notificacao.notificar_instrutores')
                                                ->label('Notificar por email instrutores ao serem vinculados ao curso'),

                                            Toggle::make('auto_config.notificacao.avaliacao_reacao')
                                                ->label('Email de notificando a aprovação'),

                                            Toggle::make('auto_config.notificacao.disponibilizacao')
                                                ->label('Email informando a disponibilização dos certificados após o preenchimento do formulário de avaliação'),

                                        ]),

                                    Section::make('Responsáveis')
                                        ->columns(1)
                                        ->schema([
                                            Select::make('auto_config.notificacao.responsaveis.list')
                                                ->label('Responsáveis da equipe que serão notificados')
                                                ->options(fn() => $this->all_users->pluck('name', 'id'))
                                                ->multiple()
                                                ->searchable()
                                                ->preload(),

                                            Toggle::make('auto_config.notificacao.responsaveis.email_responsaveis')
                                                ->label('Notificar responsáveis da equipe por email'),

                                            Toggle::make('auto_config.notificacao.notificar_status')
                                                ->label('Notificar por email os responsaveis sobre mudanças no status do curso'),
                                        ]),
                                ]),
                            ]),

                        Tab::make('Inscrição')->schema([
                            Section::make('Janela de inscrições')
                                ->columns([
                                    'default' => 1,
                                    'md' => 2,
                                ])
                                ->schema([
                                    Toggle::make('auto_config.inscricao.abertura_automatica')
                                        ->label('Abrir e encerrar inscrições automaticamente com base nas datas configuradas')
                                        ->columnSpanFull(),

                                    DateTimePicker::make('inicio_inscricao')
                                        ->label('Data e hora de início das inscrições')
                                        ->live(),

                                    DateTimePicker::make('termino_inscricao')
                                        ->label('Data e hora de término das inscrições')
                                        ->live(),
                                ]),

                            Section::make('Confirmação automática')
                                ->schema([
                                    Toggle::make('auto_config.inscricao.regras.aprovacao_automatica')
                                        ->label('Ativar confirmação automática de inscrição')
                                        ->live(),

                                    Toggle::make('auto_config.inscricao.regras.exceder_vagas')
                                        ->label('Permitir confirmação automática mesmo quando o número de inscrições confirmadas exceder o número de vagas disponíveis')
                                        ->live()
                                        ->visible(fn($get) => $get('auto_config.inscricao.regras.aprovacao_automatica') === true),

                                    Grid::make([
                                        'default' => 1,
                                        'md' => 12,
                                    ])
                                        ->schema([
                                            Toggle::make('auto_config.inscricao.regras.data_confirmacao_automatica')
                                                ->label('Confirmar as inscrições em uma data específica')
                                                ->hint('caso desmarcado, as inscrições serão confirmadas automaticamente no momento da inscrição')
                                                ->live()
                                                ->columnSpan([
                                                    'default' => 1,
                                                    'md' => 7,
                                                ]),

                                            DateTimePicker::make('auto_config.inscricao.regras.data_confirmacao')
                                                ->label('Data e hora para confirmação automática das inscrições')

                                                // ->inlineLabel()
                                                ->hint('A partir dessa data, as inscrições serão confirmadas automaticamente.')
                                                ->live()
                                                ->visible(fn(Get $get) => $get('auto_config.inscricao.regras.data_confirmacao_automatica') === true)
                                                ->extraFieldWrapperAttributes(['class' => 'md:max-w-sm'])
                                                ->columnSpan([
                                                    'default' => 1,
                                                    'md' => 4,
                                                ]),
                                        ])
                                        ->visible(fn($get) => $get('auto_config.inscricao.regras.aprovacao_automatica') === true),

                                    Select::make('auto_config.inscricao.regras.criterios')
                                        ->label('Critérios para confirmação automática')
                                        ->multiple()
                                        ->hint('Selecione ao menos um critério.')
                                        ->options([
                                            'publico-alvo' => 'Público-alvo',
                                            'setor' => 'Lotação/Setor',
                                            'sem-criterio' => 'Sem critério - confirmar todas as inscrições automaticamente',
                                        ])
                                        ->required()
                                        ->live()
                                        ->visible(fn($get) => $get('auto_config.inscricao.regras.aprovacao_automatica') === true),
                                ]),

                            Section::make('Critérios de confirmação')
                                ->columns([
                                    'default' => 1,
                                    'md' => 2,
                                ])
                                ->schema([
                                    Select::make('auto_config.inscricao.regras.criterio.publico-alvo')
                                        ->label('Públicos alvo para confirmação automática')
                                        ->hint('Somente as inscrições de usuários que se enquadrem nos públicos-alvo selecionados serão confirmadas automaticamente.')
                                        ->options(function () {
                                            return TipoPublico::all()->pluck('descricao', 'id');
                                        })
                                        ->multiple()
                                        ->live()
                                        ->preload()
                                        ->visible(function ($get) {
                                            $criterios = (array) $get('auto_config.inscricao.regras.criterios');
                                            if (in_array('publico-alvo', $criterios)) {
                                                return true;
                                            }
                                            return false;
                                        }),

                                    Select::make('auto_config.inscricao.regras.criterio.setor')
                                        ->label('Setores para confirmação automática')
                                        ->hint('Somente as inscrições de usuários lotados nos setores selecionados serão confirmadas automaticamente.')
                                        ->options(function () {
                                            return Setores::all()->pluck('setor', 'id');
                                        })
                                        ->multiple()
                                        ->live()
                                        ->searchable()
                                        // ->preload()
                                        ->visible(function ($get) {
                                            $criterios = (array) $get('auto_config.inscricao.regras.criterios');
                                            if (in_array('setor', $criterios)) {
                                                return true;
                                            }
                                            return false;
                                        }),
                                ])
                                ->visible(function (Get $get) {
                                    if ($get('auto_config.inscricao.regras.aprovacao_automatica') !== true) {
                                        return false;
                                    }

                                    $criterios = (array) $get('auto_config.inscricao.regras.criterios');
                                    if (empty($criterios) || in_array('sem-criterio', $criterios)) {
                                        return false;
                                    } else {
                                        return true;
                                    }
                                }),
                        ]),
                        Tab::make('Aprovação')->schema([
                            Section::make('Regras de Processamento Automático')
                                ->schema([
                                    Toggle::make('auto_config.aprovacao.aprovacao_automatica')
                                        ->label('Ativar processamento automático das aprovações')
                                        ->live(),

                                    TextInput::make('aprovacao_min')
                                        ->label('Frequência para aprovação')
                                        ->suffix('%')
                                        ->required()
                                        ->numeric()
                                        ->default(100)
                                        ->inputMode('decimal')
                                        ->extraFieldWrapperAttributes(['class' => 'md:max-w-sm'])
                                        ->visible(fn($get) => $get('auto_config.aprovacao.aprovacao_automatica') === true),

                                    Toggle::make('auto_config.aprovacao.processar_em_data_especifica')
                                        ->label('Processar as aprovações em uma data específica')
                                        ->hint('caso desmarcado, as aprovações serão processadas automaticamente no termino do curso')
                                        ->live()
                                        ->visible(fn($get) => $get('auto_config.aprovacao.aprovacao_automatica') === true),

                                    Grid::make([
                                        'default' => 1,
                                        'md' => 3,
                                    ])
                                        ->schema([

                                            DateTimePicker::make('auto_config.aprovacao.data_processamento')
                                                ->label('Data e hora para processamento automático das aprovações')

                                                ->rule(function () {
                                                    return function ($attribute, $value, $fail) {
                                                        $termino = $this->curso->termino;

                                                        if (isset($termino)) {
                                                            if ($termino > $value) {
                                                                $fail('A data de processamento automático das aprovações deve ser igual ou posterior à data de término do curso, Termino: ' . $termino->format('Y-m-d H:i'));
                                                                return;
                                                            }
                                                        }
                                                    };
                                                })
                                                // ->inlineLabel()
                                                ->hint('As aprovações serão processadas automaticamente na data selecionada.')
                                                ->live()
                                                ->visible(fn(Get $get) => $get('auto_config.aprovacao.processar_em_data_especifica') === true)
                                                ->extraFieldWrapperAttributes(['class' => 'md:max-w-sm']),
                                        ])
                                        ->visible(fn($get) => $get('auto_config.aprovacao.aprovacao_automatica') === true),
                                ]),
                        ]),
                        Tab::make('Certificação')->schema([
                            Section::make('Certificados de alunos')
                                ->schema([
                                    Toggle::make('auto_config.certificacao.gerar_certificado_automaticamente-alunos')
                                        ->label('Ativar geração automática dos certificados - Alunos')
                                        ->live(),

                                    Grid::make([
                                        'default' => 1,
                                        'md' => 2,
                                    ])
                                        ->schema([
                                            Toggle::make('auto_config.certificacao.geracao-alunos.gerar_em_data_especifica')
                                                ->label('Gerar os certificados em uma data específica')
                                                ->hint('caso desmarcado, os certificados serão gerados automaticamente após a aprovação dos alunos')
                                                ->live(),

                                            Grid::make([
                                                'default' => 1,
                                                'md' => 3,
                                            ])
                                                ->schema([
                                                    DateTimePicker::make('auto_config.certificacao.geracao-alunos.data_geracao')
                                                        ->label('Data e hora para geração automática dos certificados')

                                                        // ->inlineLabel()
                                                        ->hint('Os certificados serão gerados automaticamente na data selecionada.')
                                                        ->live()
                                                        ->visible(fn(Get $get) => $get('auto_config.certificacao.geracao-alunos.gerar_em_data_especifica') === true)
                                                        ->extraFieldWrapperAttributes(['class' => 'md:max-w-sm']),
                                                ])
                                                ->columnSpanFull(),

                                            Select::make('auto_config.certificacao.geracao-alunos.form.curso_id')
                                                ->label('Curso')
                                                ->options(function () {
                                                    return [
                                                        $this->curso->id => $this->curso->titulo,
                                                    ];
                                                })
                                                ->afterStateHydrated(function (Set $set, $state) {
                                                    if (empty($state)) {
                                                        $set('auto_config.certificacao.geracao-alunos.form.curso_id', $this->curso->id);
                                                    }
                                                })
                                                ->disabled()
                                                ->dehydrated(),

                                            Toggle::make('auto_config.certificacao.geracao-alunos.form.totalatvd')
                                                ->label('Somar Participações')
                                                ->hintColor('primary')
                                                ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Certificar somente a soma da carga horária de atividades do curso/evento onde o aluno foi aprovado em um unico certificado')
                                                ->live(),

                                            Select::make('auto_config.certificacao.geracao-alunos.form.cursoatividade_id')
                                                ->label('Atividades para certificação')
                                                ->hintColor('primary')
                                                ->disabled(function () {
                                                    return !$this->curso->temAtividades();
                                                })
                                                ->dehydrated(true)
                                                ->dehydrateStateUsing(function ($state) {
                                                    return $state ?? [];
                                                })
                                                ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Serão emitidos certificados somente para as atividades selecionadas, caso nenhuma seja selecionada, será gerado somente 1 certificado para o curso como um todo considerando a carga horaria total')
                                                ->multiple(true)
                                                ->options(function (Get $get) {
                                                    return $this->curso->atividades()->orderBy('titulo')->pluck('titulo', 'id');
                                                }),

                                            Select::make('auto_config.certificacao.geracao-alunos.form.modelo_id')
                                                ->label('Modelo de Certificado')
                                                ->options(function () {
                                                    return CertificadoModelos::orderBy('titulo')->pluck('titulo', 'id');
                                                })
                                                ->afterStateHydrated(function (Set $set, $state) {
                                                    if (empty($state)) {
                                                        $set('auto_config.certificacao.geracao-alunos.form.modelo_id', 1);
                                                    }
                                                }),

                                            Select::make('auto_config.certificacao.geracao-alunos.form.assinado_por')
                                                ->label('Assinado por')
                                                ->searchable()
                                                ->options(function () {
                                                    return $this->all_users->pluck('name', 'id');
                                                })
                                                ->afterStateHydrated(function (Set $set, $state) {
                                                    if (empty($state)) {
                                                        $set('auto_config.certificacao.geracao-alunos.form.assinado_por', 13);
                                                    }
                                                }),

                                            Toggle::make('auto_config.certificacao.geracao-alunos.form.disponivel')
                                                ->label('Disponibilizar certificado na geração')
                                                ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Caso desmarcado, o certificado será disponibilizado no preenchimento do formulario de avaliação')
                                                ->live(),

                                            RichEditor::make('auto_config.certificacao.geracao-alunos.form.observacao')
                                                ->label('Observação')
                                                ->live()
                                                ->columnSpanFull(),

                                        ])
                                        ->visible(function ($get) {
                                            return $get('auto_config.certificacao.gerar_certificado_automaticamente-alunos') === true;
                                        }),
                                ]),

                            Section::make('Certificados de instrutores')

                                ->schema([
                                    Toggle::make('auto_config.certificacao.gerar_certificado_automaticamente-instrutores')
                                        ->label('Ativar geração automática dos certificados - Instrutores')
                                        ->live(),

                                    Grid::make([
                                        'default' => 1,
                                        'md' => 3,
                                    ])
                                        ->schema([
                                            Toggle::make('auto_config.certificacao.geracao-instrutores.gerar_em_data_especifica_instrutores')
                                                ->label('Gerar os certificados em uma data específica')
                                                ->hint('caso desmarcado, os certificados serão gerados automaticamente após o término do curso')
                                                ->live(),

                                            DateTimePicker::make('auto_config.certificacao.geracao-instrutores.data_geracao_instrutores')
                                                ->label('Data e hora para geração automática dos certificados dos instrutores')

                                                // ->inlineLabel()
                                                ->hint('Os certificados dos instrutores serão gerados automaticamente na data selecionada.')
                                                ->live()
                                                ->visible(fn(Get $get) => $get('auto_config.certificacao.geracao-instrutores.gerar_em_data_especifica_instrutores') === true)
                                                ->extraFieldWrapperAttributes(['class' => 'md:max-w-sm']),
                                        ])
                                        ->visible(fn($get) => $get('auto_config.certificacao.gerar_certificado_automaticamente-instrutores') === true),


                                    Repeater::make('auto_config.certificacao.geracao-instrutores.instrutores')
                                        ->label('Instrutores')
                                        ->afterStateHydrated(function (Set $set, $state) {
                                            if ($state && is_array($state) && count($state) > 0) {
                                                return;
                                            }

                                            $instrutores = $this->instrutores;

                                            if ($instrutores->isEmpty()) {
                                                return [];
                                            }

                                            $set(('auto_config.certificacao.geracao-instrutores.instrutores'), $instrutores->map(function ($instrutor) {
                                                return [
                                                    'curso_id' => $this->curso->id,
                                                    'aluno_id' => [$instrutor->instrutor_id],
                                                    'modelo_id' => 3,
                                                    'assinado_por' => 13,
                                                    'disponivel' => true,
                                                    'carga_horaria' => $instrutor->carga_horaria ?? 10,
                                                    'cursoatividade_id' => [],
                                                ];
                                            })->toArray());
                                        })

                                        ->addable(false)
                                        ->deletable(false)
                                        ->reorderable(false)

                                        ->schema([
                                            Select::make('curso_id')
                                                ->label('Curso')
                                                ->options(function () {
                                                    return [
                                                        $this->curso->id => $this->curso->titulo,
                                                    ];
                                                })
                                                ->afterStateHydrated(function (Set $set, $state) {
                                                    if (empty($state)) {
                                                        $set('curso_id', $this->curso->id);
                                                    }
                                                })
                                                ->disabled()
                                                ->dehydrated(),

                                            Select::make('cursoatividade_id')
                                                ->label('Atividades para certificação')
                                                ->hintColor('primary')
                                                ->live()
                                                ->disabled(function () {
                                                    return !$this->curso->temAtividades();
                                                })
                                                ->dehydrated(true)
                                                ->dehydrateStateUsing(function ($state) {
                                                    return $state ?? [];
                                                })
                                                ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Serão emitidos certificados somente para as atividades selecionadas, caso nenhuma seja selecionada, será gerado somente 1 certificado para o curso como um todo considerando a carga horaria do campo ao lado')
                                                ->multiple(true)
                                                ->options(function (Get $get) {
                                                    return $this->curso->atividades()->orderBy('titulo')->pluck('titulo', 'id');
                                                }),
                                            TextInput::make('carga_horaria')
                                                ->label('Carga Horária')
                                                ->suffix('horas')
                                                ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Esse campo é desconsiderado caso sejam selecionadas atividades para certificação.')
                                                ->numeric()
                                                ->live()
                                                ->disabled(function (Get $get) {
                                                    $atividades = $get('cursoatividade_id') ?? [];
                                                    return count($atividades) > 0;
                                                })
                                                ->dehydrated(true)
                                                ->inputMode('decimal')
                                                ->columnSpan(1),

                                            Select::make('modelo_id')
                                                ->label('Modelo de Certificado')
                                                ->options(function () {
                                                    return CertificadoModelos::orderBy('titulo')->pluck('titulo', 'id');
                                                })
                                                ->afterStateHydrated(function (Set $set, $state) {
                                                    if (empty($state)) {
                                                        $set('modelo_id', 2);
                                                    }
                                                }),

                                            Select::make('assinado_por')
                                                ->label('Assinado por')
                                                ->searchable()
                                                ->options(function () {
                                                    return $this->all_users->pluck('name', 'id');
                                                })
                                                ->afterStateHydrated(function (Set $set, $state) {
                                                    if (empty($state)) {
                                                        $set('assinado_por', 13);
                                                    }
                                                }),

                                            Select::make('aluno_id')
                                                ->options(function () {
                                                    return $this->all_users->pluck('name', 'id');
                                                })
                                                ->disabled(true)
                                                ->dehydrated(true)
                                                ->multiple(true)
                                                ->label('Instrutor')
                                                ->columnSpan(3),

                                            Toggle::make('disponivel')
                                                ->label('Disponibilizar')
                                                ->afterStateHydrated(function (Set $set, $state) {
                                                    if (!isset($state)) {
                                                        $set('disponivel', true);
                                                    }
                                                })
                                                ->columnSpan(3),

                                            RichEditor::make('observacao')
                                                ->label('Observação')
                                                ->columnSpanFull(),
                                        ])
                                        ->visible(fn($get) => $get('auto_config.certificacao.gerar_certificado_automaticamente-instrutores') === true),
                                ]),
                        ]),
                        Tab::make('Relatorios')->schema([
                            Section::make('Geração e envio')
                                ->schema([
                                    Toggle::make('auto_config.relatorios.gerar_automaticamente')
                                        ->label('Ativar geração e envio automático de relatórios')
                                        ->live(),

                                    Select::make('auto_config.relatorios.tipos')
                                        ->label('Relatórios')
                                        ->options(fn() => RelatoriosEscola::getReportTypeOptions())
                                        ->multiple()
                                        ->searchable()
                                        ->preload()
                                        ->required()
                                        ->visible(fn($get) => $get('auto_config.relatorios.gerar_automaticamente') === true),

                                    Grid::make([
                                        'default' => 1,
                                        'md' => 2,
                                    ])
                                        ->schema([
                                            Toggle::make('auto_config.relatorios.gerar_em_data_especifica')
                                                ->label('Gerar e enviar em uma data específica')
                                                ->hint('caso desmarcado, os relatórios serão gerados após a certificação dos alunos')
                                                ->live(),

                                            DateTimePicker::make('auto_config.relatorios.data_geracao')
                                                ->label('Data e hora para geração e envio dos relatórios')
                                                ->live()
                                                ->visible(fn(Get $get) => $get('auto_config.relatorios.gerar_em_data_especifica') === true)
                                                ->extraFieldWrapperAttributes(['class' => 'md:max-w-sm']),
                                        ])
                                        ->visible(fn($get) => $get('auto_config.relatorios.gerar_automaticamente') === true),
                                ]),

                            Section::make('Destinatários')
                                ->schema([
                                    Toggle::make('auto_config.relatorios.destinatarios.enviar_responsaveis')
                                        ->label('Enviar para os responsáveis configurados')
                                        ->default(true)
                                        ->live()
                                        ->visible(fn($get) => $get('auto_config.relatorios.gerar_automaticamente') === true),

                                    Select::make('auto_config.relatorios.destinatarios.usuarios')
                                        ->label('Usuários adicionais que receberão os relatórios')
                                        ->options(fn() => $this->all_users->pluck('name', 'id'))
                                        ->multiple()
                                        ->searchable()
                                        ->preload()
                                        ->visible(fn($get) => $get('auto_config.relatorios.gerar_automaticamente') === true),
                                ])
                                ->visible(fn($get) => $get('auto_config.relatorios.gerar_automaticamente') === true),
                        ]),
                        Tab::make('Fluxo')->schema([
                            View::make('livewire.partials.auto-config-flow')
                                ->viewData([
                                    'autoConfigForm' => $this,
                                ])
                                ->columnSpanFull(),
                        ]),

                        Tab::make('Breakpoints')->schema([
                            Section::make('Breakpoints manuais')
                                ->description('Adicione pontos de revisao ao fluxo. Cada breakpoint pode apenas sinalizar a equipe ou pausar a execucao.')
                                ->contained(false)
                                ->schema([
                                    Repeater::make('auto_config.fluxo.breakpoints')
                                        ->label('Pontos de revisao')
                                        ->helperText('A ordem dos itens define a ordem em que eles aparecem na configuracao. A etapa escolhida define onde o ponto sera exibido na timeline.')
                                        ->defaultItems(0)
                                        ->generateUuidUsing(false)
                                        ->addActionLabel('Adicionar ponto de revisao')
                                        ->itemNumbers()
                                        ->reorderable(false)
                                        ->itemHeaders()
                                        ->itemLabel(function (?array $state): ?string {
                                            $stepLabel = $this->getFlowStepOptions()[$state['step'] ?? null] ?? 'Etapa';

                                            return filled($state['label'] ?? null)
                                                ? $state['label']
                                                : "Revisao em {$stepLabel}";
                                        })
                                        ->schema([
                                            TextInput::make('label')
                                                ->label('Titulo da revisao')
                                                ->placeholder('Ex.: Revisar lista de aprovados')
                                                ->maxLength(120)
                                                ->columnSpanFull(),

                                            Grid::make([
                                                'default' => 1,
                                                'md' => 12,
                                            ])->schema([
                                                Fieldset::make('Localizacao no fluxo')
                                                    ->contained(false)
                                                    ->columnSpan([
                                                        'default' => 1,
                                                        'md' => 8,
                                                    ])
                                                    ->schema([
                                                        Select::make('step')
                                                            ->label('Etapa')
                                                            ->options(fn() => $this->getFlowStepOptions())
                                                            ->required()
                                                            ->searchable()
                                                            ->preload()
                                                            ->live()
                                                            ->columnSpanFull(),

                                                        Select::make('position')
                                                            ->label('Momento')
                                                            ->options([
                                                                'before' => 'Antes da etapa',
                                                                'after' => 'Depois da etapa',
                                                            ])
                                                            ->default('before')
                                                            ->required()
                                                            ->columnSpanFull(),

                                                        Select::make('severity')
                                                            ->label('Nivel de atencao')
                                                            ->options([
                                                                'info' => 'Informativo',
                                                                'warning' => 'Requer atencao',
                                                                'error' => 'Bloqueante',
                                                            ])
                                                            ->default('warning')
                                                            ->required()
                                                            ->columnSpanFull(),
                                                    ]),

                                                Fieldset::make('Opcoes de execucao')
                                                    ->contained(false)
                                                    ->columnSpan([
                                                        'default' => 1,
                                                        'md' => 4,
                                                    ])
                                                    ->schema([
                                                        Toggle::make('enabled')
                                                            ->label('Ativo')
                                                            ->default(true),

                                                        Toggle::make('blocking')
                                                            ->label('Pausar execucao automatica')
                                                            ->default(true),

                                                        Toggle::make('notify')
                                                            ->label('Notificar responsaveis')
                                                            ->default(true),
                                                    ]),
                                            ]),

                                            Textarea::make('description')
                                                ->label('Contexto para a equipe')
                                                ->placeholder('Explique o que precisa ser conferido quando o fluxo chegar neste ponto.')
                                                ->rows(3)
                                                ->columnSpanFull(),
                                        ]),
                                ]),
                        ]),
                    ]),
            ]);
    }

    public function getFlowStepOptions(): array
    {
        return $this->flowService()->getStepOptions();
    }

    public function getFluxoPreview(Get $get): array
    {
        return $this->flowService()->buildPreview($this->curso, [
            'auto_config' => $get('auto_config') ?? [],
            'inicio_inscricao' => $get('inicio_inscricao'),
            'termino_inscricao' => $get('termino_inscricao'),
        ]);
    }

    public function continueBreakpoint(string $breakpointCode): void
    {
        $result = app(AutoBreakpointService::class)->release($this->curso, $breakpointCode);

        if (! ($result['released'] ?? false)) {
            Notification::make()
                ->title($result['message'] ?? 'Nao foi possivel continuar o fluxo.')
                ->warning()
                ->send();

            $this->curso->refresh();

            return;
        }

        $this->curso->refresh();

        Notification::make()
            ->title('Fluxo liberado')
            ->body('A etapa sera reanalisada e continuara automaticamente quando estiver pronta.')
            ->success()
            ->send();
    }

    private function flowService(): AutoConfigFlowService
    {
        return app(AutoConfigFlowService::class);
    }

    private function normalizeReusableConfig(array $config): array
    {
        $config = $this->flowService()->normalizeForSave([
            'auto_config' => $config,
        ])['auto_config'] ?? [];

        foreach (
            [
                'notificacao.responsaveis.list',
                'inscricao.regras.criterios',
                'inscricao.regras.criterio.publico-alvo',
                'inscricao.regras.criterio.setor',
                'certificacao.geracao-alunos.form.cursoatividade_id',
                'relatorios.tipos',
                'relatorios.destinatarios.usuarios',
            ] as $path
        ) {
            $value = data_get($config, $path, []);

            if (! is_array($value)) {
                data_set($config, $path, filled($value) ? [$value] : []);
            }
        }

        $breakpoints = data_get($config, 'fluxo.breakpoints', []);
        data_set(
            $config,
            'fluxo.breakpoints',
            collect(is_array($breakpoints) ? $breakpoints : [])
                ->filter(fn(mixed $breakpoint): bool => is_array($breakpoint))
                ->values()
                ->all(),
        );

        $instrutores = data_get($config, 'certificacao.geracao-instrutores.instrutores', []);
        data_set(
            $config,
            'certificacao.geracao-instrutores.instrutores',
            collect(is_array($instrutores) ? $instrutores : [])
                ->filter(fn(mixed $instrutor): bool => is_array($instrutor))
                ->map(function (array $instrutor): array {
                    foreach (['aluno_id', 'cursoatividade_id'] as $field) {
                        $value = $instrutor[$field] ?? [];

                        if (! is_array($value)) {
                            $instrutor[$field] = filled($value) ? [$value] : [];
                        }
                    }

                    return $instrutor;
                })
                ->values()
                ->all(),
        );

        return $config;
    }

    public function submit(): void
    {

        Log::info('Salvando configuração automática para o curso', ['curso_id' => $this->curso->id]);
        $state = $this->form->getState();
        Log::info('Estado do formulário ao salvar', ['state' => $state]);

        $state = $this->flowService()->normalizeForSave($state);

        $auto_steps = $this->curso->auto_steps ?? [];
        $auto_steps_sample = AutoStructureBuilder::makeDefaultSteps();
        if (count($auto_steps) != count($auto_steps_sample)) {
            $auto_steps = array_merge($auto_steps_sample, $auto_steps);
        }

        $this->curso->update([
            'auto_config' => $state['auto_config'] ?? [],
            'inicio_inscricao' => $state['inicio_inscricao'] ?? null,
            'auto_steps' => $auto_steps,
            'termino_inscricao' => $state['termino_inscricao'] ?? null,
            'aprovacao' => $state['aprovacao_min'] ?? $this->curso->aprovacao,
        ]);

        Log::info('Configuração automática salva com sucesso para o curso', ['curso_id' => $this->curso->id]);
        Log::info('Configuração automática salva', ['auto_config' => $this->curso->auto_config]);

        Notification::make()
            ->title('Configurações salvas com sucesso!')
            ->success()
            ->send();

        // $this->dispatch('close-modal', id: 'auto-config-modal');
    }

    public function render()
    {
        return view('livewire.auto-config-form');
    }
}
