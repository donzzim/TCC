<?php

namespace App\Filament\Resources\Escola;

use UnitEnum;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkAction;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use App\Filament\Resources\Escola\CursoCertificadosResource\Pages;
use App\Filament\Resources\UserResource;
use App\Models\Escola\CertificadoModelos;
use App\Models\Escola\CursoAtividades;
use App\Models\Escola\CursoCertificados;
use App\Models\Escola\Comendas;
use App\Models\Escola\Cursos;
use App\Models\Escola\Mencoes;
use App\Models\User;
use App\Services\Pedagogico\CertificadoService;
use Filament\Forms;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CursoCertificadosResource extends Resource
{
    protected static ?string $model = CursoCertificados::class;
    // protected static ?string $recordTitleAttribute = 'titulo';
    protected static ?string $modelLabel = 'Certificado';
    protected static ?string $puralModelLabel = 'Certificados';
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-identification';
    protected static ?string $navigationLabel = 'Certificados';

    // protected static ?string $navigationParentItem = 'Cadastro';
    protected static string | UnitEnum | null $navigationGroup = 'Projeto Pedagógico';
    protected static ?int $navigationSort = 5;


    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['certificable', 'curso', 'pessoa', 'modelo', 'assinado'])
            ->orderBy('created_at', 'desc');
    }

    public static function getTablefields(): array
    {
        return [
            Tables\Columns\TextColumn::make('curso.titulo')
                ->label('Curso')
                ->wrap()
                ->getStateUsing(function ($record) {
                    try {


                        if ($record->certificable_type == Comendas::class) {
                            return "COMENDA";
                        }
                        if ($record->certificable_type == Mencoes::class) {
                            return "MENÃ‡ÃƒO";
                        }
                        if ($record->certificable_id > 1) {
                            if ($record->certificable->curso_id) {
                                return $record->certificable->curso->titulo . ' - ' . $record->certificable->titulo;
                            }
                            return $record->certificable->titulo;
                        }
                        return 'Outros Cursos';
                    } catch (\Exception $e) {
                        return 'ERRO AO BUSCAR TITULO';
                    }
                })
                ->description(function (CursoCertificados $record) {
                    try {
                        if ($record->certificable_type == Comendas::class) {
                            return 'Comenda emitida em: ' . $record->created_at;
                        }
                        if ($record->certificable_type == Mencoes::class) {
                            return 'Menção emitida em: ' . $record->created_at;
                            return 'Menção emitida em: ' . $record->created_at;
                        }
                        if ($record->certificable_id > 1) {
                            if ($record->carga_horaria) {
                                return $record->carga_horaria . 'h'  . ' Emitido em: ' . $record->created_at;
                            } else {
                                return $record->certificable->carga_horaria . 'h'  . ' Emitido em: ' . $record->created_at;
                            }
                        }
                    } catch (\Exception $e) {
                        return 'Erro ao buscar descrição';
                    }
                })
                ->searchable()
                ->sortable()
                ->toggleable(),
            Tables\Columns\ImageColumn::make('pessoa.avatar_url')
                ->label('Foto')
                ->disk('public')
                ->defaultImageUrl(url('/images/admin-tie.png'))
                ->circular()
                ->toggleable(),
            Tables\Columns\TextColumn::make('pessoa.name')
                ->label('Pessoa')
                ->description(fn(CursoCertificados $record): string => "Nome impresso: {$record->nome}")
                ->searchable()
                ->sortable()
                ->toggleable(),
            Tables\Columns\TextColumn::make('modelo.titulo')
                ->label('Modelo')
                ->getStateUsing(function ($record) {
                    try {
                        if ($record->modelo) {
                            return $record->modelo->titulo;
                        }
                        return 'Modelo Externo';
                    } catch (\Exception $e) {
                        return 'Erro ao buscar modelo';
                    }
                })
                ->description(fn(CursoCertificados $record): string => "Tí­tulo impresso: {$record->titulo}")
                ->searchable()
                ->wrap()
                ->sortable()
                ->toggleable(),
            Tables\Columns\TextColumn::make('assinado.name')
                ->label('Assinado por')
                ->searchable()
                ->getStateUsing(function ($record) {
                    try {
                        if ($record->assinado) {
                            return $record->assinado->name;
                        }
                        return 'Assinatura Externa';
                    } catch (\Exception $e) {
                        return 'Erro ao buscar assinatura';
                    }
                })
                ->sortable()
                ->toggleable(),
            Tables\Columns\ToggleColumn::make('disponivel')
                ->label('Disponível')
                ->searchable()
                ->sortable()
                ->toggleable(),

            Tables\Columns\TextColumn::make('externo')
                ->label('Externo')
                ->getStateUsing(function ($record) {
                    try {
                        return $record->externo ? 'Externo' : 'Interno';
                    } catch (\Exception $e) {
                        return 'Erro ao buscar externo';
                    }
                })
                ->color(function ($state) {
                    if ($state == 'Externo') {
                        return 'info';
                    }
                    return 'success';
                })
                ->badge()
                ->searchable()
                ->searchable()
                ->sortable()
                ->toggleable(),

            Tables\Columns\TextColumn::make('validado')
                ->label('Validação')
                ->searchable()
                ->getStateUsing(function ($record) {
                    try {
                        // dd($record->validado, config('settings.validacao.validado'), config('settings.validacao.aguardando'), config('settings.validacao.invalidado'));
                        if ($record->validado == 0 || $record->validado == config('settings.validacao.validado')) {
                            return 'Validado';
                        }
                        if ($record->validado == config('settings.validacao.aguardando')) {
                            return 'Aguardando validação';
                        }
                        return 'Invalidado';
                    } catch (\Exception $e) {
                        return 'Erro ao buscar validação';
                    }
                })
                ->badge()
                ->color(function ($state) {
                    if ($state == 'Validado') {
                        return 'success';
                    }
                    if ($state == 'Aguardando validação') {
                        return 'warning';
                    }
                    return 'danger';
                })
                ->wrap()
                ->toggleable(),

            Tables\Columns\TextColumn::make('validado_por')
                ->label('Validado por')
                ->searchable()
                ->getStateUsing(function ($record) {
                    try {
                        if ($record->validado_por) {
                            return User::find($record->validado_por)->name;
                        }
                        if (!$record->externo) {
                            return 'Gerado pelo sistema';
                        }
                        return 'Sem Validação';
                    } catch (\Exception $e) {
                        return 'Erro ao buscar validador';
                    }
                })
                ->sortable()
                ->toggleable(),
        ];
    }
    public static function getFormfields(): array
    {
        return [
            Section::make('Dados Pessoais')
                ->schema([

                    Forms\Components\Select::make('certificable_type')
                        ->options([
                            Cursos::class => 'Curso',
                            CursoAtividades::class => 'Atividade',
                            Comendas::class => 'Comenda',
                            Mencoes::class => 'Menção',
                        ])
                        ->label('Tipo de Certificado')
                        ->live()
                        ->required()

                        ->columnSpan(2),

                    Forms\Components\Select::make('certificable_id')
                        ->options(function (Get $get) {
                            if ($get('certificable_type') == Cursos::class) {
                                return Cursos::all()->pluck('titulo', 'id');
                            }
                            if ($get('certificable_type') == CursoAtividades::class) {
                                return CursoAtividades::all()->pluck('titulo', 'id');
                            }
                        })
                        ->searchable(['titulo'])
                        ->label('Evento')

                        ->createOptionForm(
                            CursosResource::getFormfieldsAtividades()
                        )
                        ->hidden(function (Get $get) {
                            if ($get('certificable_type') == Comendas::class || $get('certificable_type') == Mencoes::class) {
                                return true;
                            }
                        })
                        ->columnSpan(3),

                    TextInput::make('carga_horaria')
                        ->label('Carga Horária')
                        ->suffix('horas')
                        ->numeric()
                        ->inputMode('decimal')
                        ->default(10)
                        ->columnSpan(1),

                    Forms\Components\Select::make('pessoa_id')
                        // ->options(function (Get $get) {
                        //     return DB::table('curso_alunos as ca')
                        //         ->join('users as u', 'ca.aluno_id', 'u.id')
                        //         ->where('ca.curso_id', $get('curso_id'))
                        //         ->where('ca.aprovado', 1)
                        //         ->pluck('u.name', 'u.id');
                        // })
                        // ->hint('Somente alunos inscritos e aprovados????')
                        // ->hintColor('danger')
                        ->relationship('pessoa', 'name')
                        ->searchable(['name'])
                        ->searchPrompt('Selecione o Curso primeiro')
                        ->label('Nome')
                        ->required()

                        ->createOptionForm(
                            UserResource::getFormFields()
                        )
                        ->columnSpan(4),

                    Forms\Components\TextInput::make('nome')
                        ->label('Nome impresso')
                        ->columnSpan(3),

                    Forms\Components\TextInput::make('titulo')
                        ->label('Tí­tulo impresso')
                        ->columnSpan(3),

                ])
                ->columns(6)
                ->columnSpan(4),

            Section::make('Certificado')
                ->schema([

                    Forms\Components\Select::make('modelo_id')
                        ->relationship(
                            name: 'modelo',
                            titleAttribute: 'titulo',
                        )
                        ->searchable(['titulo'])
                        ->label('Modelo')
                        ->required()
                        ->live()

                        ->afterStateUpdated(function (?string $state, Set $set) {
                            if ($state) {
                                $user = CertificadoModelos::where('id', '=', $state)->first();
                                if ($user->assinado_por)
                                    $set('assinado_por', $user->assinado_por);
                            }
                        })
                        ->createOptionForm(
                            CertificadoModeloResource::getFormFields()
                        )
                        ->columnSpan(3),

                    Forms\Components\Select::make('assinado_por')
                        ->relationship(
                            name: 'assinado',
                            titleAttribute: 'name',
                        )
                        ->searchable(['name'])
                        ->label('Assinado por')

                        ->createOptionForm(
                            UserResource::getFormFields()
                        )
                        ->columnSpan(3),

                    Forms\Components\FileUpload::make('arquivo')
                        ->label('Arquivo do Certificado')
                        ->columnSpan(2),

                ])
                ->columnSpan(2),

            Forms\Components\RichEditor::make('observacao')
                ->label('Observação')
                ->columnSpanFull(),
        ];
    }
    public static function getFormfieldsGerarCertificados(): array
    {
        return [
            Grid::make()
                ->columns(5)
                ->schema([
                    Grid::make()
                        ->columns(5)
                        ->schema([
                            Toggle::make('totalatvd')
                                ->label('Somar Participações')
                                ->hintColor('primary')
                                ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Certificar somente a soma da carga horária de atividades do curso/evento onde o aluno foi aprovado')
                                ->default(false)
                                ->columnSpan(3),


                            Forms\Components\Select::make('curso_id')
                                ->options(
                                    function () {
                                        return Cursos::all()->pluck('titulo', 'id');
                                    }
                                )
                                ->searchable(['titulo'])
                                ->label('Curso')
                                ->required()
                                ->live()

                                ->columnSpan(3),
                        ]),
                    Forms\Components\Select::make('cursoatividade_id')
                        ->multiple(true)
                        ->options(function (Get $get) {
                            return CursoAtividades::where('curso_id', $get('curso_id'))->pluck('titulo', 'id');
                        })
                        // ->searchable(['titulo'])
                        ->label('Atividade')

                        ->columnSpan(3),

                    Forms\Components\Select::make('modelo_id')
                        ->options(
                            function () {
                                return CertificadoModelos::all()->pluck('titulo', 'id');
                            }
                            // name: 'modelo',
                            // titleAttribute: 'titulo',
                        )
                        ->label('Modelo')
                        ->required()
                        ->live()

                        ->afterStateUpdated(function (?string $state, Set $set) {
                            if ($state) {
                                $user = CertificadoModelos::where('id', '=', $state)->first();
                                if ($user->assinado_por)
                                    $set('assinado_por', $user->assinado_por);
                            }
                        })
                        ->columnSpan(3),

                    Forms\Components\Select::make('assinado_por')
                        ->options(function () {
                            return User::all()->pluck('name', 'id');
                        })
                        // ->relationship(
                        //     name: 'assinado',
                        //     titleAttribute: 'name',
                        // )
                        ->searchable(['name'])
                        ->label('Assinado por')

                        ->columnSpan(3),
                    Forms\Components\Toggle::make('disponivel')
                        ->label('Disponibilizar')
                        ->default(false)
                        ->columnSpan(3),

                    Forms\Components\RichEditor::make('observacao')
                        ->label('Observação')
                        ->columnSpanFull(),
                ]),
        ];
    }



    public static function getFormfieldsGerarComendas(): array
    {
        return [
            Grid::make()
                ->columns(5)
                ->schema([

                    Forms\Components\Select::make('modelo_id')
                        ->options(function () {
                            return CertificadoModelos::whereIn('id', [5, 6])->get()->pluck('titulo', 'id');
                        })
                        //->searchable(['titulo'])
                        ->label('Modelo')
                        ->required()
                        ->live()

                        ->afterStateUpdated(function (?string $state, Set $set) {
                            if ($state) {
                                $user = CertificadoModelos::where('id', '=', $state)->first();
                                if ($user->assinado_por)
                                    $set('assinado_por', $user->assinado_por);
                            }
                        })
                        ->createOptionForm(
                            CertificadoModeloResource::getFormFields()
                        )
                        ->columnSpan(3),

                    TextInput::make('nome')
                        ->label('Nome Exibido')
                        ->columnSpan(3),



                    Forms\Components\Select::make('assinado_por')
                        ->relationship(
                            name: 'assinado',
                            titleAttribute: 'name',
                        )
                        ->searchable(['name'])
                        ->label('Assinado por')

                        ->createOptionForm(
                            UserResource::getFormFields()
                        )
                        ->columnSpan(3),

                    Forms\Components\Select::make('aluno_id')
                        ->options(function () {
                            return User::all()->pluck('name', 'id');
                        })
                        ->searchable(['name'])
                        ->label('Aluno')

                        ->createOptionForm(
                            UserResource::getFormFields()
                        )
                        ->columnSpan(3),

                    Forms\Components\Toggle::make('disponivel')
                        ->label('Disponibilizar')
                        ->default(true)
                        ->columnSpan(3),

                    Forms\Components\RichEditor::make('observacao')
                        ->label('Observação')
                        ->columnSpanFull(),
                ]),
        ];
    }


    public static function getFormfieldsGerarCertificadoIndividual(): array
    {
        return [
            Grid::make()
                ->columns(5)
                ->schema([
                    Forms\Components\Select::make('curso_id')
                        ->relationship(
                            name: 'curso',
                            titleAttribute: 'titulo',
                        )
                        ->searchable(['titulo'])
                        ->label('Curso')
                        ->required()
                        ->live()

                        // ->createOptionForm(
                        //     CursosResource::getFormFields()
                        // )
                        ->columnSpan(3),

                    Forms\Components\Select::make('cursoatividade_id')
                        ->options(function (Get $get) {
                            return CursoAtividades::where('curso_id', $get('curso_id'))->pluck('titulo', 'id');
                        })
                        // ->searchable(['titulo'])
                        ->multiple(true)
                        ->label('Atividade')

                        ->createOptionForm(
                            CursosResource::getFormfieldsAtividades()
                        )
                        ->columnSpan(3),

                    TextInput::make('carga_horaria')
                        ->label('Carga Horária')
                        ->suffix('horas')
                        ->numeric()
                        ->inputMode('decimal')
                        ->default(10)
                        ->columnSpan(1),

                    Forms\Components\Select::make('modelo_id')
                        ->relationship(
                            name: 'modelo',
                            titleAttribute: 'titulo',
                        )
                        //->searchable(['titulo'])
                        ->label('Modelo')
                        ->required()
                        ->live()

                        ->afterStateUpdated(function (?string $state, Set $set) {
                            if ($state) {
                                $user = CertificadoModelos::where('id', '=', $state)->first();
                                if ($user->assinado_por)
                                    $set('assinado_por', $user->assinado_por);
                            }
                        })
                        ->createOptionForm(
                            CertificadoModeloResource::getFormFields()
                        )
                        ->columnSpan(3),

                    Forms\Components\Select::make('assinado_por')
                        ->relationship(
                            name: 'assinado',
                            titleAttribute: 'name',
                        )
                        ->searchable(['name'])
                        ->label('Assinado por')

                        ->createOptionForm(
                            UserResource::getFormFields()
                        )
                        ->columnSpan(3),

                    Forms\Components\Select::make('aluno_id')
                        ->options(function () {
                            return User::all()->pluck('name', 'id');
                        })
                        ->searchable(['name'])
                        ->multiple(true)
                        ->label('Aluno')

                        ->createOptionForm(
                            UserResource::getFormFields()
                        )
                        ->columnSpan(3),
                    Forms\Components\Toggle::make('disponivel')
                        ->label('Disponibilizar')
                        ->default(false)
                        ->columnSpan(3),

                    Forms\Components\RichEditor::make('observacao')
                        ->label('Observação')
                        ->columnSpanFull(),
                ]),
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components(
                static::getFormfields(),
            )
            ->columns(6);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(
                static::getTablefields()
            )
            ->filters([
                TernaryFilter::make('status_curso')
                    ->label('Status do Curso')
                    ->placeholder('Todos os Cursos')
                    ->trueLabel('Somente Ativos')
                    ->falseLabel('Somente Inativos')
                    ->default(true)
                    ->queries(
                        true: function (Builder $query) {
                            return $query->where(function ($q) {
                                $q->whereHas('curso', fn($sq) => $sq->where('ativo', true))
                                    ->orWhereHas('atividade.curso', fn($sq) => $sq->where('ativo', true));
                            });
                        },
                        false: function (Builder $query) {
                            return $query->where(function ($q) {
                                $q->whereHas('curso', fn($sq) => $sq->where('ativo', false))
                                    ->orWhereHas('atividade.curso', fn($sq) => $sq->where('ativo', false));
                            });
                        },
                    )
                    ->columnSpan(2),

                SelectFilter::make('externo')
                    ->label('Tipo de Certificado')
                    ->query(function (Builder $query, array $data) {
                        if ($data['value'] === 'interno') {
                            $query->where('externo', false);
                        } elseif ($data['value'] === 'externo') {
                            $query->where('externo', true);
                        }
                    })
                    ->options([
                        'interno' => 'Interno',
                        'externo' => 'Externo',
                    ])
                    ->default('interno')
                    ->columnSpan(2),

                SelectFilter::make('curso')
                    ->relationship(name: 'curso', titleAttribute: 'titulo')
                    ->searchable(['titulo'])
                    ->query(function (Builder $query, $data) {
                        if ($data['value'] == null) {
                            return;
                        }
                        $curso = Cursos::find($data['value']);
                        if ($curso->temAtividades()) {
                            $atividades_ids = $curso->atividades->pluck('id')->toArray();
                            $atividades_ids[] = $curso->id;
                            return $query->whereIn('curso_id', $atividades_ids)->orWhereIn('certificable_id', $atividades_ids);
                        }

                        return $query->where('curso_id', $data['value']);
                    })
                    ->attribute('curso_id')
                    ->default(request()->get('curso_id'))
                    ->columnSpan(6),

                SelectFilter::make('pessoa')
                    ->relationship(
                        name: 'pessoa',
                        titleAttribute: 'name',
                    )
                    ->searchable(['name'])
                    ->attribute('pessoa_id')
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
            ->headerActions([
                Action::make('certificados')
                    ->label('Gerar Certificado dos aprovados')
                    ->color('gray')
                    ->size('sm')
                    ->icon('heroicon-o-document-magnifying-glass')
                    ->action(function (array $data): void {
                        if (count($data['cursoatividade_id']) > 0) {
                            $atividades_id = $data['cursoatividade_id'];
                            $atividades = CursoAtividades::whereIn('id', $atividades_id)->get();
                            foreach ($atividades as $atividade) {
                                CertificadoService::gerarCertificados($data, $atividade);
                            }
                        } else {
                            $curso = Cursos::find($data['curso_id']);
                            CertificadoService::gerarCertificados($data, $curso);
                        }
                    })
                    ->form(
                        static::getFormfieldsGerarCertificados(),
                    )
                    ->slideOver(),

                Action::make('certificado_avulso')
                    ->label('Gerar Certificado Individual')
                    ->color('danger')
                    ->size('sm')
                    ->icon('heroicon-o-document-plus')
                    ->action(function (array $data): void {

                        if (count($data['cursoatividade_id']) > 0) {
                        } else {
                        }

                        $curso = Cursos::find($data['curso_id']);

                        CertificadoService::gerarCertificadoIndividual($data);
                    })
                    ->form(
                        static::getFormfieldsGerarCertificadoIndividual(),
                    ),

                Action::make('comenda_avulsa')
                    ->label('Gerar Comenda ou Menção')
                    ->color('danger')
                    ->size('sm')
                    ->icon('heroicon-o-document-plus')
                    ->action(function (array $data): void {
                        CertificadoService::gerarComenda($data);
                    })
                    ->form(
                        static::getFormfieldsGerarComendas(),
                    ),
            ])


            ->recordActions([
                ActionGroup::make([

                    EditAction::make()
                        ->hiddenLabel()
                        ->icon('heroicon-o-pencil-square')
                        ->size('xl')
                        ->tooltip('editar'),
                    ViewAction::make()
                        ->hiddenLabel()
                        ->icon('heroicon-o-eye')
                        ->size('xl')
                        ->tooltip('visualizar')
                        ->modal(),
                    DeleteAction::make()
                        ->hiddenLabel()
                        ->icon('heroicon-o-trash')
                        ->size('xl')
                        ->tooltip('excluir'),

                    Action::make('imprimir')
                        ->hiddenLabel()
                        ->tooltip('Imprimir certificado')
                        ->icon('heroicon-o-printer')
                        ->url(fn(CursoCertificados $record): string => route('imprimir.certificado', $record->uuid))
                        ->openUrlInNewTab(),

                    Action::make('validar')
                        ->hiddenLabel()
                        ->tooltip('Validar certificado')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->action(function (CursoCertificados $record) {

                            $success = CertificadoService::validarCertificados(true, [$record]);
                            if ($success) {
                                Notification::make()
                                    ->title('Certificados validados com sucesso!')
                                    ->success()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('Erro ao validar certificados!')
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->requiresConfirmation()
                        ->modalHeading("Atenção")
                        ->modalDescription('Tem certeza que deseja validar este certificado?'),

                    Action::make('invalidar')
                        ->hiddenLabel()
                        ->tooltip('Invalidar certificado')
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->action(function (CursoCertificados $record) {
                            $success = CertificadoService::validarCertificados(false, [$record]);
                            if ($success) {
                                Notification::make()
                                    ->title('Certificados Invalidados com sucesso!')
                                    ->success()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('Erro ao invalidar certificados!')
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->requiresConfirmation()
                        ->modalHeading("Atenção")
                        ->modalDescription('Tem certeza que deseja Invalidar este certificado?')
                ])
            ])
            ->toolBarActions([
                // BulkActionGroup::make([
                DeleteBulkAction::make(),

                BulkAction::make('atualizar_carga_horaria')
                    ->label('Modificar Carga Horária')
                    ->form([
                        TextInput::make('carga_horaria')
                            ->label('Nova Carga Horária')
                            ->numeric()
                            ->inputMode('decimal')
                            ->required()
                            ->suffix('horas'),
                    ])
                    ->action(function (array $data, $records) {
                        foreach ($records as $record) {
                            $record->update(['carga_horaria' => $data['carga_horaria']]);
                        }
                        Notification::make()
                            ->title('Carga horária atualizada para ' . $data['carga_horaria'] . ' horas em ' . $records->count() . ' certificados.')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading("Atenção")
                    ->modalDescription('Tem certeza que deseja atualizar a carga horária dos certificados selecionados?')
                    ->color('warning')
                    ->icon('heroicon-o-clock'),

                BulkAction::make('validar')
                    ->hiddenLabel()
                    ->tooltip('Validar certificado')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->action(function ($records) {
                        $success = CertificadoService::validarCertificados(true, $records);
                        if ($success) {
                            Notification::make()
                                ->title('Certificados validados com sucesso!')
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Erro ao validar certificados!')
                                ->danger()
                                ->send();
                        }
                    })
                    ->requiresConfirmation()
                    ->modalHeading("Atenção")
                    ->modalDescription('Tem certeza que deseja validar este certificado?'),

                BulkAction::make('invalidar')
                    ->hiddenLabel()
                    ->tooltip('Invalidar certificado')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->action(function ($records) {
                        $success = CertificadoService::validarCertificados(false, $records);
                        if ($success) {
                            Notification::make()
                                ->title('Certificados Invalidados com sucesso!')
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Erro ao invalidar certificados!')
                                ->danger()
                                ->send();
                        }
                    })
                    ->requiresConfirmation()
                    ->modalHeading("Atenção")
                    ->modalDescription('Tem certeza que deseja Invalidar este certificado?')
                // ]),
            ])
            ->persistFiltersInSession()
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
    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
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
            'index' => Pages\ListCursoCertificados::route('/'),
            'create' => Pages\CreateCursoCertificados::route('/create'),
            'edit' => Pages\EditCursoCertificados::route('/{record}/edit'),
        ];
    }
    public static function getGlobalSearchResultTitle(Model $record): string|Htmlable
    {
        return $record->pessoa->name;
    }
    public static function getGloballySearchableAttributes(): array
    {
        return ['atividade.curso.titulo', 'pessoa.name', 'modelo.titulo', 'assinado.name'];
    }
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Curso' => $record->atividade->curso->titulo,
            'Pessoa' => $record->pessoa->name,
            'Assinado por' => $record->assinado->name,
        ];
    }
}
