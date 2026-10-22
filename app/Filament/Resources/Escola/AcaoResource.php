<?php

namespace App\Filament\Resources\Escola;

use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs\Tab;
use App\Filament\Clusters\Escola\ProjetoPedagogicoCluster;
use App\Filament\Resources\Escola\AcaoResource\Pages;
use App\Filament\Resources\AcaoResource\RelationManagers;
use App\Filament\Resources\Administrativo\DemandaResource;
use App\Models\Escola\Acao;
use App\Models\Escola\CursoAtividades;
use App\Models\Cadastro\Tabelas;
use App\Models\Escola\CursoCertificados;
use App\Models\Escola\CursoFrequencias;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Support\Facades\FilamentColor;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Support\Enums\Width;
use Filament\Tables;
use Filament\Actions\Action;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Support\Enums\FontWeight;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AcaoResource extends Resource
{
    protected static ?string $model = Acao::class;
    protected static ?string $cluster = ProjetoPedagogicoCluster::class;
    protected static null|SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?string $recordTitleAttribute = 'descricao';
    protected static ?string $modelLabel = 'Ações Pedagógicas';
    protected static ?string $puralModelLabel = 'Ações Pedagógicas ';
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-presentation-chart-line';
    protected static ?string $navigationLabel = 'Ações Pedagógicas';
    protected static ?int $navigationSort = 1;

    public static function getFormfields(): array
    {
        return [
            Section::make('Dados da ação')
                ->description('Detalhamento da ação')
                ->schema([
                    Forms\Components\TextInput::make('descricao')
                        ->label('Descrição')
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(4),

                    Forms\Components\TextInput::make('codigo')
                        ->label('Código')
                        ->columnSpan(1),

                    Forms\Components\TextInput::make('ano')
                        ->label('Ano')
                        ->required()
                        ->numeric()
                        ->columnSpan(1),

                    Forms\Components\Select::make('demanda_id')
                        ->relationship(name: 'demanda', titleAttribute: 'titulo')
                        ->searchable(['titulo'])
                        ->label('Demanda')
                        ->preload()
                        ->createOptionForm(
                            DemandaResource::getFormFields()
                        )
                        ->columnSpan(2),
                ])
                ->columns(4)
                ->columnSpan(4),

            Section::make('PerÃ­odo')
                ->description('PerÃ­odo previsto para a realização desta ação')
                ->schema([
                    Forms\Components\DatePicker::make('data_inicio')
                        ->label('Início'),
                    Forms\Components\DatePicker::make('data_termino')
                        ->afterOrEqual('data_inicio')
                        ->label('Término'),
                    Forms\Components\TextInput::make('local_atividade')
                        ->label('Local previsto')
                        ->maxLength(255)
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->columnSpan(2),

            Tabs::make('tabs')
                ->tabs([
                    Tab::make('Despesas')
                        ->schema([
                            Forms\Components\Repeater::make('despesas')
                                ->hiddenLabel()
                                ->relationship('despesas')
                                ->defaultItems(0)
                                ->schema([
                                    Forms\Components\TextInput::make('descricao')
                                        ->label('Descrição da Despesa')
                                        ->columnSpan(3),

                                    Forms\Components\Select::make('natureza_id')
                                        ->label('Natureza')
                                        ->relationship(
                                            name: 'natureza',
                                            titleAttribute: 'descricao',
                                            modifyQueryUsing: fn(Builder $query) => $query->where('grupo', '=', 'NATUREZA_DESPESA'),
                                        )
                                        ->getOptionLabelFromRecordUsing(fn(Model $record) => "{$record->codigo}: {$record->descricao}")
                                        ->searchable()
                                        ->columnSpan(2),

                                    Forms\Components\Select::make('tipo')
                                        ->label('Tipo')
                                        ->options([
                                            1 => 'Hora Aula',
                                            2 => 'Unidade',
                                        ])
                                        ->live()
                                        ->columnSpan(1),

                                    Forms\Components\TextInput::make('horas_aula')
                                        ->label('Horas aula')
                                        ->hidden(fn(Get $get): bool => $get('tipo') == 2)
                                        ->columnSpan(1),

                                    Forms\Components\TextInput::make('quantidade')
                                        ->label('Quantidade')
                                        ->hidden(fn(Get $get): bool => $get('tipo') == 1)
                                        ->columnSpan(1),

                                    Forms\Components\TextInput::make('valor')
                                        ->label('Valor')
                                        ->numeric()
                                        ->prefix('R$')
                                        ->inputMode('decimal')
                                        ->columnSpan(1),
                                ])
                                ->columns(8)
                        ]),
                    Tab::make('Informações')
                        ->schema([
                            Forms\Components\RichEditor::make('informacao')
                                ->label('Informações Preliminares')
                                ->label('Informações Preliminares')
                                ->columnSpanFull(),
                        ]),
                    Tab::make('Objetivo')
                        ->schema([
                            Forms\Components\RichEditor::make('objetivo')
                                ->label('Objetivo')
                                ->columnSpanFull(),
                        ]),

                    Tab::make('Ementa')
                        ->schema([
                            Forms\Components\RichEditor::make('Ementa')
                                ->label('Ementa')
                                ->columnSpanFull(),
                        ]),
                    Tab::make('Observação')
                        ->schema([
                            Forms\Components\RichEditor::make('observacao')
                                ->label('Observação')
                                ->columnSpanFull(),
                        ]),
                ])
                ->columnSpanFull(),
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components(static::getFormFields())
            ->columns(6);
    }

    public static function table(Table $table): Table
    {

        FilamentColor::register([

            'roxo' => [
                '50' => '#F3E8FF'
            ]
        ]);
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('codigo')
                    ->label('Código')
                    ->badge()
                    ->searchable(),
                Tables\Columns\TextColumn::make('descricao')
                    ->label('Descrição')
                    ->wrap()
                    ->html()
                    ->searchable(),
                Tables\Columns\TextColumn::make('ano')
                    ->label('Ano')
                    ->badge()
                    ->searchable(),
                Tables\Columns\TextColumn::make('data_inicio')
                    ->label('Início')
                    ->date('d/m/Y')
                    ->searchable(),
                Tables\Columns\TextColumn::make('data_termino')
                    ->label('Término')
                    ->date('d/m/Y')
                    ->searchable(),
                Tables\Columns\TextColumn::make('cursos_count')
                    ->label('Cursos')
                    ->counts('cursos')
                    ->sortable()
                    ->badge()
                    ->color('success'),
            ])
            ->filters([
                SelectFilter::make('ano')
                    ->multiple()
                    ->options(fn() => Acao::select('ano')->distinct()->pluck('ano', 'ano')->toArray())
                    ->searchable(['ano'])
                    ->preload()
                    ->attribute('ano')
                    ->columnSpan(6),
            ], layout: FiltersLayout::Modal)
            ->filtersFormColumns(6)
            ->filtersFormWidth('2xl')
            ->filtersTriggerAction(
                fn(Action $action) => $action
                    ->button()
                    ->label('Filtrar'),
            )
            ->deferFilters()
            ->selectable()
            ->recordActions([
                ViewAction::make()
                    // ->label('Ver Detalhes')
                    ->hiddenLabel()
                    ->tooltip("Ver Detalhes")
                    ->icon('heroicon-o-eye')
                    ->modalHeading("Detalhes da Ação")
                    ->modalWidth('7xl')
                    ->infolist([
                        Section::make('Informações da Ação')
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        TextEntry::make('codigo')
                                            ->label('Código')
                                            ->badge()
                                            ->color('primary'),

                                        TextEntry::make('ano')
                                            ->label('Ano')
                                            ->badge()
                                            ->color('success'),
                                        TextEntry::make('demanda.titulo')
                                            ->label('Demanda Vinculada')
                                            ->getStateUsing(fn($record) => $record->demanda ? $record->demanda->titulo : 'Nenhuma demanda vinculada')
                                            ->badge()
                                            ->color('info')
                                            ->weight(FontWeight::Medium),
                                    ]),

                                TextEntry::make('descricao')
                                    ->label('Descrição')
                                    ->weight(FontWeight::Bold)
                                    ->html()
                                    ->size('lg')
                                    ->columnSpanFull(),

                                Grid::make(2)
                                    ->schema([
                                        TextEntry::make('data_inicio')
                                            ->label('Data de Início')
                                            ->date('d/m/Y')
                                            ->icon('heroicon-o-calendar')
                                            ->iconColor('success'),

                                        TextEntry::make('data_termino')
                                            ->label('Data de Término')
                                            ->date('d/m/Y')
                                            ->icon('heroicon-o-calendar')
                                            ->iconColor('danger'),
                                    ]),
                            ]),

                        Section::make('Resumo Estatística')
                            ->schema([
                                Grid::make(4)
                                    ->schema([

                                        Grid::make(1)
                                            ->extraAttributes(['class' => 'ring-1 ring-cyan-400 rounded-lg p-4 dark:bg-gray-900'])
                                            ->schema([
                                                TextEntry::make('total_cursos')
                                                    ->label('Total de Cursos')
                                                    ->getStateUsing(fn($record) => $record->cursos->count())
                                                    ->badge()
                                                    ->color('info')
                                                    ->icon('heroicon-o-academic-cap'),
                                            ])
                                            ->columnSpan(2),

                                        Grid::make(4)
                                            ->extraAttributes(['class' => 'bg-purple-50 shadown-md ring-1 ring-purple-200 rounded-lg p-4 dark:bg-gray-900'])
                                            ->schema([
                                                TextEntry::make('total_alunos')
                                                    ->label('Total Alunos')
                                                    ->getStateUsing(function ($record) {
                                                        $alunosList = collect();

                                                        // Coleta todos os alunos de todos os cursos
                                                        foreach ($record->cursos as $curso) {
                                                            $alunosList = $alunosList->concat($curso->alunos);
                                                        }

                                                        // Remove duplicatas usando unique() pelo ID do aluno
                                                        $alunosUnicos = $alunosList->unique('id');

                                                        return $alunosUnicos->count();
                                                    })
                                                    ->badge()
                                                    ->color('roxo')
                                                    ->icon('heroicon-o-users'),
                                            ])
                                            ->columnSpan(2),

                                        Grid::make(4)
                                            ->extraAttributes(['class' => 'bg-green-50 shadown-md ring-1 ring-green-200 rounded-lg p-4 dark:bg-gray-900'])
                                            ->schema([
                                                TextEntry::make('total_aprovados')
                                                    ->label('Total de Aprovados')
                                                    ->getStateUsing(function ($record) {
                                                        return $record->cursos->sum(function ($curso) {
                                                            return $curso->alunos->where('aprovado', 1)->count();
                                                        });
                                                    })
                                                    ->badge()
                                                    ->color('success')
                                                    ->icon('heroicon-o-check-circle'),
                                            ])
                                            ->columnSpan(2),
                                        Grid::make(4)
                                            ->extraAttributes(['class' => 'bg-yellow-50 shadown-md ring-1 ring-yellow-200 rounded-lg p-4 dark:bg-gray-900'])
                                            ->schema([
                                                TextEntry::make('total_certificados')
                                                    ->label('Certificados Emitidos')
                                                    ->getStateUsing(function ($record) {
                                                        if ($record->cursos->isEmpty()) {
                                                            return 0;
                                                        }
                                                        $cursosId = $record->cursos->pluck('id');
                                                        $atividadesIds = $record->cursos->flatMap(function ($curso) {
                                                            if ($curso->atividades->isEmpty()) {
                                                                return [];
                                                            }
                                                            return $curso->atividades->pluck('id');
                                                        });
                                                        if ($atividadesIds->isEmpty()) {
                                                            return CursoCertificados::whereIn('curso_id', $cursosId)
                                                                ->where('disponivel', 1)
                                                                ->count();
                                                        }
                                                        return CursoCertificados::whereIn('curso_id', $atividadesIds)
                                                            ->where('disponivel', 1)
                                                            ->count();
                                                    })
                                                    ->badge()
                                                    ->color('warning')
                                                    ->icon('heroicon-o-trophy'),
                                            ])
                                            ->columnSpan(2),
                                    ]),
                            ])
                            ->collapsible(),
                        // Detalhes dos Cursos
                        Section::make('Cursos Vinculados Ã  Ação')
                            ->schema([
                                RepeatableEntry::make('cursos')
                                    ->label('')
                                    ->schema([
                                        Section::make(fn($record) => $record->titulo)
                                            ->extraAttributes(function () {
                                                static $lastColor = null;
                                                $cores = [
                                                    'ring-green-400',
                                                    'ring-blue-400',
                                                    'ring-orange-400',
                                                    'ring-purple-400',
                                                    'ring-yellow-400',
                                                ];

                                                // Remove a última cor usada para evitar repetição consecutiva
                                                if ($lastColor !== null) {
                                                    $cores = array_values(array_diff($cores, [$lastColor]));
                                                }

                                                $cor = $cores[array_rand($cores)];
                                                $lastColor = $cor;

                                                return [
                                                    'class' => "ring-2 ring-blue-300 rounded-xl shadow-xl"
                                                ];
                                            })
                                            ->schema([
                                                // Cabeçalho do Curso
                                                Group::make([
                                                    Grid::make(3)
                                                        ->schema([
                                                            TextEntry::make('titulo')
                                                                ->label('Curso')
                                                                ->weight(FontWeight::Bold)
                                                                ->size('lg')
                                                                ->icon('heroicon-o-academic-cap')
                                                                ->iconColor('primary'),

                                                            TextEntry::make('inicio')
                                                                ->label('PerÃ­odo')
                                                                ->getStateUsing(function ($record) {
                                                                    $output = 'Não definido';
                                                                    if ($record->inicio) {
                                                                        $data = Carbon::parse($record->inicio);
                                                                        $output = $data->format('d/m/Y');
                                                                    }
                                                                    if ($record->inicio && $record->termino) {
                                                                        $data = Carbon::parse($record->termino);
                                                                        $output .= ' a ' . $data->format('d/m/Y');
                                                                    }
                                                                    return $output;
                                                                })
                                                                ->icon('heroicon-o-calendar')
                                                                ->iconColor('success'),

                                                            TextEntry::make('carga_horaria')
                                                                ->label('Carga Horária')
                                                                ->suffix('h')
                                                                ->icon('heroicon-o-clock')
                                                                ->iconColor('warning'),
                                                        ]),

                                                    // EstatÃ­sticas do Curso
                                                    Grid::make(4)
                                                        ->schema([
                                                            Grid::make(4)
                                                                ->extraAttributes(['class' => 'bg-cyan-50 shadown-md ring-1 ring-cyan-200 rounded-lg p-4 dark:bg-gray-900'])
                                                                ->schema([
                                                                    TextEntry::make('total_alunos_curso')
                                                                        ->label('Alunos Inscritos')
                                                                        ->getStateUsing(function ($record) {

                                                                            return $record->alunos()->distinct()->get()->count();

                                                                            //return $alunoList->distinct()->get()->count();
                                                                        })
                                                                        ->badge()
                                                                        ->color('info'),
                                                                    TextEntry::make('total_presencas')
                                                                        ->label('Total Presenças')
                                                                        ->getStateUsing(function ($record) {


                                                                            $alunos = $record->alunos()->distinct()->get();

                                                                            if ($alunos->isEmpty()) {
                                                                                return 0;
                                                                            }
                                                                            $cursoeatividadesId = $record->atividades->pluck('id');
                                                                            $cursoeatividadesId[] = $record->id;
                                                                            return $alunos->sum(function ($aluno) use ($cursoeatividadesId) {


                                                                                return CursoFrequencias::whereIn('curso_id', $cursoeatividadesId)
                                                                                    ->where('aluno_id', $aluno->aluno->id)
                                                                                    ->where('situacaofrequencia_id', config('settings.frequencia.presente'))
                                                                                    ->count();
                                                                            });
                                                                        })->badge()
                                                                        ->color('success'),
                                                                    TextEntry::make('total_ausencias')
                                                                        ->label('Total Ausencias')
                                                                        ->getStateUsing(function ($record) {


                                                                            $alunos = $record->alunos()->distinct()->get();

                                                                            if ($alunos->isEmpty()) {
                                                                                return 'Sem Presenças';
                                                                            }
                                                                            $cursoeatividadesId = $record->atividades->pluck('id');
                                                                            $cursoeatividadesId[] = $record->id;
                                                                            return $alunos->sum(function ($aluno) use ($cursoeatividadesId) {

                                                                                return CursoFrequencias::whereIn('curso_id', $cursoeatividadesId)
                                                                                    ->where('aluno_id', $aluno->aluno->id)
                                                                                    ->where('situacaofrequencia_id', config('settings.frequencia.ausente'))
                                                                                    ->count();
                                                                            });
                                                                        })->badge()
                                                                        ->color('danger'),
                                                                ]),

                                                            Grid::make(4)
                                                                ->extraAttributes(['class' => 'bg-purple-50 shadown-md ring-1 ring-purple-200 rounded-lg p-4 dark:bg-gray-900'])
                                                                ->schema([

                                                                    TextEntry::make('aprovados_curso')
                                                                        ->label('Aprovados')
                                                                        ->getStateUsing(fn($record) => $record->alunos->where('aprovado', 1)->count())
                                                                        ->badge()
                                                                        ->color('success'),

                                                                    TextEntry::make('atividades_curso')
                                                                        ->label('Atividades')
                                                                        ->getStateUsing(fn($record) => $record->atividades->count())
                                                                        ->badge()
                                                                        ->color('warning'),

                                                                    TextEntry::make('certificados_curso')
                                                                        ->label('Certificados')
                                                                        ->getStateUsing(function ($record) {
                                                                            if ($record->atividades->isEmpty()) {
                                                                                return CursoCertificados::where('curso_id', $record->id)
                                                                                    ->where('disponivel', 1)
                                                                                    ->count();
                                                                            }
                                                                            return CursoCertificados::whereIn('curso_id', $record->atividades->pluck('id'))
                                                                                ->where('disponivel', 1)
                                                                                ->count();
                                                                        })
                                                                        ->badge()
                                                                        ->color('primary'),
                                                                ]),
                                                        ]), //grid estatistica
                                                ])
                                                    ->columnSpanFull(),

                                                Group::make([
                                                    Section::make('Atividades')
                                                        ->schema([
                                                            TextEntry::make('atividades_detalhes')
                                                                ->label('Atividades do Curso')

                                                                ->getStateUsing(function ($record) {
                                                                    //dd($record);
                                                                    if ($record->atividades->isEmpty()) {
                                                                        return 'Nenhuma atividade cadastrada';
                                                                    }


                                                                    return $record->atividades->map(function ($atividade) {
                                                                        $titulo = $atividade->titulo ?: 'Atividade Principal';
                                                                        $data = $atividade->inicio->format('d/m/Y H:i');
                                                                        $turno = $atividade->turno;


                                                                        $frequencias = CursoFrequencias::where('curso_id', $atividade->id)->count();
                                                                        // if ($frequencias == 0) {
                                                                        //     return " <strong>{$titulo}</strong><br>" .
                                                                        //         "ðŸ“… {$data} ({$turno})<br>" .
                                                                        //         "ðŸ‘¥ Frequências: Nenhuma frequência registrada";
                                                                        // }
                                                                        $presentes = CursoFrequencias::where('curso_id', $atividade->id)
                                                                            ->where('situacaofrequencia_id', config('settings.frequencia.presente'))
                                                                            ->count();

                                                                        return " <strong>{$titulo}</strong><br>" .
                                                                            "ðŸ“… {$data} {$turno}<br>" .
                                                                            "ðŸ‘¥ Frequências: {$presentes}/{$frequencias} presentes";
                                                                    })->join('<br><br>');
                                                                })
                                                                ->html()
                                                                ->columnSpanFull(),
                                                        ])->collapsed(),
                                                ])
                                                    ->visible(fn($record) => $record->atividades->count() > 0)
                                                    ->columnSpanFull(),

                                                Section::make('Certificados DisponÃ­veis')
                                                    ->schema([
                                                        TextEntry::make('certificados_detalhes')
                                                            ->getStateUsing(function ($record) {
                                                                $certificados = CursoCertificados::where('curso_id', $record->id)
                                                                    ->where('disponivel', 1)
                                                                    ->with(['atividade', 'pessoa'])
                                                                    ->get();

                                                                if ($certificados->isEmpty()) {
                                                                    return ' Nenhum certificado disponÃ­vel';
                                                                }

                                                                $grouped = $certificados->groupBy(function ($cert) {
                                                                    if ($cert->cursoatividade_id) {
                                                                        return 'Atividade: ' . ($cert->atividade->titulo ?? 'Atividade Principal');
                                                                    }
                                                                    return 'Curso Completo';
                                                                });

                                                                return $grouped->map(function ($certs, $tipo) {
                                                                    return " <strong>{$tipo}</strong> ({$certs->count()} certificados)<br>" .
                                                                        $certs->map(function ($cert) {
                                                                            $nome = $cert->pessoa->name ?? 'Aluno desconhecido';
                                                                            $link = route('imprimir.certificado', $cert->uuid);
                                                                            $modelo = 'Externo';
                                                                            if ($cert->modelo_id == 1) {
                                                                                $modelo = 'Assinatura Diretor';
                                                                            } else if ($cert->modelo_id == 2) {
                                                                                $modelo = 'Assinatura Coordenador';
                                                                            }
                                                                            return "ðŸ‘¤ <a href='{$link}' target='_blank' class='font-medium text-primary-600 hover:text-primary-800'>{$nome} - {$modelo}</a> ";
                                                                        })->join('<br>');
                                                                })->join('<br><br>');
                                                            })
                                                            ->html()
                                                            ->columnSpanFull(),
                                                    ])
                                                    ->collapsed()
                                                    ->visible(function ($record) {
                                                        return CursoCertificados::where('curso_id', $record->id)
                                                            ->where('disponivel', 1)
                                                            ->exists();
                                                    })
                                                    ->columnSpanFull(),


                                                Section::make('Frequencia por Aluno')
                                                    ->schema([
                                                        RepeatableEntry::make('alunosUser')
                                                            ->schema([
                                                                Section::make(fn($record) => $record->name)
                                                                    ->schema([
                                                                        TextEntry::make('frequencia_detalhad')
                                                                            ->label('Frequência Detalhada')
                                                                            ->getStateUsing(function ($record) {
                                                                                $totalFreq = CursoFrequencias::where('curso_id', $record->id)
                                                                                    ->where('aluno_id', $record->id)
                                                                                    ->count();

                                                                                $presentes = CursoFrequencias::where('curso_id', $record->id)
                                                                                    ->where('aluno_id', $record->id)
                                                                                    ->whereHas('situacaoFrequencia', fn($q) => $q->where('descricao', 'Presente'))
                                                                                    ->count();

                                                                                $ausentes = CursoFrequencias::where('curso_id', $record->id)
                                                                                    ->where('aluno_id', $record->id)
                                                                                    ->whereHas('situacaoFrequencia', fn($q) => $q->where('descricao', 'Ausente'))
                                                                                    ->count();
                                                                                $porcentagemPresenca = $totalFreq > 0 ? round(($presentes / $totalFreq) * 100, 2) : 0;
                                                                                return "ðŸ‘¤ <strong>{$record->name}</strong>: presenças: {$presentes}, Ausencias: {$ausentes} (Total: %{$porcentagemPresenca} de Presença)";
                                                                            })->html()
                                                                            ->columnSpanFull(),
                                                                        TextEntry::make('frequencia_atividades')
                                                                            ->label('Frequência por Atividade')
                                                                            ->label('Frequência por Atividade')
                                                                            ->getStateUsing(function ($record) {
                                                                                //dd($record);
                                                                                $atividades = CursoAtividades::where('curso_id', $record->pivot->curso_id)->get();
                                                                                if ($atividades->isEmpty()) {
                                                                                    return 'Nenhuma atividade cadastrada';
                                                                                }

                                                                                return $atividades->map(function ($atividade) use ($record) {
                                                                                    $freq = CursoFrequencias::where('cursoatividade_id', $atividade->id)
                                                                                        ->where('aluno_id', $record->id)->first();
                                                                                    if (!$freq)
                                                                                        return "ðŸ“… {$atividade->titulo}: Não há frequência registrada";

                                                                                    if ($freq->situacaofrequencia_id == 66) {
                                                                                        $situacao = "<span class='badge bg-red-500 text-white rounded px-2 py-0.5'>Ausente</span>";
                                                                                    } else if ($freq->situacaofrequencia_id == 65) {
                                                                                        $situacao = "<span class='badge bg-green-500 text-white rounded px-2 py-0.5'>Presente</span>";
                                                                                    } else {
                                                                                        $situacao = "<span class='badge bg-gray-400 rounded px-2 py-0.5'>Indeferido</span>";
                                                                                    }
                                                                                    return "ðŸ“… {$atividade->titulo}: <span>{$situacao}</span>";
                                                                                })->join('<br>');
                                                                            })->html()
                                                                    ])->collapsed()
                                                            ])
                                                    ])->collapsed(),

                                            ])
                                            ->collapsed(),
                                    ])
                                    ->visible(fn($record) => $record->cursos->count() > 0),


                            ])
                            ->collapsible(),

                        // Informações Complementares
                        Section::make('Informações Complementares')
                            ->schema([
                                TextEntry::make('objetivo')
                                    ->label('Objetivo')
                                    ->html()
                                    ->columnSpanFull()
                                    ->visible(fn($record) => !empty($record->objetivo)),

                                TextEntry::make('informacao')
                                    ->label('Informações Preliminares')
                                    ->html()
                                    ->columnSpanFull()
                                    ->visible(fn($record) => !empty($record->informacao)),

                                TextEntry::make('ementa')
                                    ->label('Ementa')
                                    ->html()
                                    ->columnSpanFull()
                                    ->visible(fn($record) => !empty($record->ementa)),

                                TextEntry::make('observacao')
                                    ->label('Observações')
                                    ->html()
                                    ->columnSpanFull()
                                    ->visible(fn($record) => !empty($record->observacao)),
                            ])
                            ->collapsible()
                            ->collapsed(),
                    ]),

                EditAction::make()
                    ->hiddenLabel()
                    ->icon('heroicon-o-pencil-square')
                    ->size('xl')
                    ->tooltip('editar'),
                DeleteAction::make()
                    ->hiddenLabel()
                    ->icon('heroicon-o-trash')
                    ->size('xl')
                    ->tooltip('excluir'),
                Action::make('despesas')
                    ->hiddenLabel()
                    ->size('xl')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->tooltip('Despesas da Ação')
                    ->url(fn($record) => route('filament.admin.projeto-pedagogico.resources.escola.acao-despesas.index', [
                        'acao_id' => $record->id,
                    ])),
            ])
            ->toolBarActions([
                DeleteBulkAction::make(),
            ])
            ->selectCurrentPageOnly()
            ->paginated([50, 100, 150, 200, 'all'])
            ->defaultPaginationPageOption(50)
            ->striped()
            ->deferLoading();
    }
    public static function getNavigationBadge(): ?string
    {
        $count = number_format(static::getModel()::count(), 0, ',', '.');
        return $count;
    }
    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAcaos::route('/'),
            'create' => Pages\CreateAcao::route('/create'),
            'edit' => Pages\EditAcao::route('/{record}/edit'),
        ];
    }
}
