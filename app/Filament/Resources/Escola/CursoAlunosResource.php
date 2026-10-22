<?php

namespace App\Filament\Resources\Escola;

use UnitEnum;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use App\Filament\Resources\Escola\CursoAlunosResource\Pages;
use App\Filament\Resources\CursoAlunosResource\RelationManagers;
use App\Filament\Resources\UserResource;
use App\Http\Controllers\PrincipalController;
use App\Models\CursoAlunos;
use App\Models\CursoFrequencias;
use App\Models\Escola\CertificadoModelos;
use App\Models\Escola\CursoAlunos as EscolaCursoAlunos;
use App\Models\Escola\CursoAtividades;
use App\Models\Escola\CursoCertificados;
use App\Models\Escola\CursoFrequencias as EscolaCursoFrequencias;
use App\Models\Escola\Cursos;
use App\Models\Escola\TipoLocal;
use App\Models\User;
use App\Services\AprovacaoService;
use App\Services\Pedagogico\CertificadoService;
use App\Services\Pedagogico\InscricaoService;
use Filament\Forms;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\Width;
use Filament\Support\Exceptions\Halt;
use Filament\Tables;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Filament\Actions\ExportAction;
use App\Filament\Exports\EscolaCursoAlunosExporter;
use App\Filament\Exports\SynchronousExportAction;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Tables\Filters\TernaryFilter;

class CursoAlunosResource extends Resource
{
    protected static ?string $model = EscolaCursoAlunos::class;
    // protected static ?string $recordTitleAttribute = 'titulo';
    protected static ?string $modelLabel = 'Aluno';
    protected static ?string $puralModelLabel = 'Alunos';
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Alunos';

    // protected static ?string $navigationParentItem = 'Cadastro';
    protected static string | UnitEnum | null $navigationGroup = 'Projeto Pedagógico';
    protected static ?int $navigationSort = 3;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('curso')
            ->with('atividade.curso')
            ->with('aluno.perfil.tipoPessoa')
            ->with('situacaoinscricao')
            ->with('atualizado');
    }

    public static function getTablefields(): array
    {
        return [
            Tables\Columns\TextColumn::make('curso.titulo')
                ->label('Curso')
                ->wrap()
                ->getStateUsing(function (Model $record) {
                    if ($record->curso) {
                        return $record->curso ? $record->curso->titulo : 'Sem Curso';
                    }
                    return "{$record->atividade->curso->titulo} - {$record->atividade->titulo}" ?? 'Sem Curso';
                })
                ->searchable()
                ->sortable()
                ->toggleable(),

            Tables\Columns\ImageColumn::make('aluno.avatar_url')
                ->label('Foto')
                ->disk('public')
                ->defaultImageUrl(url('/images/admin-tie.png'))
                ->circular()
                ->toggleable(),

            Tables\Columns\TextColumn::make('aluno.name')
                ->label('Aluno')
                ->searchable()
                ->description(function ($record) {
                    $perfil = $record->aluno->perfil ?? null;
                    if ($perfil) {
                        return $perfil->tipoPessoa ? $perfil->tipoPessoa->descricao : "Sem tipo";
                    }
                })
                ->sortable()
                ->toggleable(),
            Tables\Columns\TextColumn::make('situacaoinscricao.descricao')
                ->label('Situação')
                ->label('Situação')
                ->searchable()
                ->badge()
                ->color(function (string $state) {
                    switch ($state) {
                        case 'Pré-inscrito':
                        case 'Pré-inscrito':
                            $cor = 'warning';
                            break;
                        case 'Inscrito':
                            $cor = 'success';
                            break;
                        case 'Indeferido':
                            $cor = 'danger';
                            break;
                        default:
                            $cor = 'default';
                            break;
                    }
                    return $cor;
                })
                ->sortable()
                ->toggleable(),
            Tables\Columns\ToggleColumn::make('aprovado')
                ->label('Aprovado')
                ->searchable()
                ->sortable()
                ->toggleable(),
            Tables\Columns\TextColumn::make('data_inscricao')
                ->label('Data Inscrição')
                ->searchable()
                ->date('d/m/Y H:i')
                ->sortable()
                ->toggleable(),
            Tables\Columns\TextColumn::make('atualizado.name')
                ->label('Atualizado por')
                ->searchable()
                ->sortable()
                ->toggleable(),
        ];
    }
    public static function getFormfields(): array
    {
        return [
            Section::make('Atividade')
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
                        ->preload()
                        // ->createOptionForm(
                        //     CursosResource::getFormFields()
                        // )
                        ->columnSpan(3),

                    Forms\Components\Select::make('aluno_id')
                        ->relationship(
                            name: 'aluno',
                            titleAttribute: 'name',
                        )
                        ->searchable(['name'])
                        ->label('Alunos')
                        ->required()
                        ->preload()
                        // ->createOptionForm(
                        //     UserResource::getFormFields()
                        // )
                        ->columnSpan(3),

                    Forms\Components\Select::make('situacaoinscricao_id')
                        ->relationship(
                            name: 'situacaoinscricao',
                            titleAttribute: 'descricao',
                            modifyQueryUsing: fn(Builder $query) => $query->where('grupo', '=', 'SITUACAO_INSCRICAO'),
                        )
                        ->searchable(['descricao'])
                        ->label('Situação')
                        ->required()
                        ->preload()
                        ->columnSpan(3),

                    Forms\Components\DateTimePicker::make('data_inscricao')
                        ->label('Data Inscrição')
                        ->required()
                        ->columnSpan(1),

                ])
                ->columns(6)
                ->columnSpanFull(),

            Forms\Components\RichEditor::make('observacao')
                ->label('Observação')
                ->columnSpanFull(),
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

    public static function getFormfieldsGerarAprovacao(): array
    {
        return [
            Tabs::make('aprovaTabs')
                ->tabs([
                    Tab::make('Aprovação')->schema([

                        Grid::make()
                            ->columns(6)
                            ->schema([

                                Forms\Components\Select::make('curso_id')
                                    ->relationship(
                                        name: 'curso',
                                        titleAttribute: 'titulo',
                                        modifyQueryUsing: fn(Builder $query) => $query->where('ativo', true),
                                    )
                                    ->searchable(['titulo'])
                                    ->label('Curso')
                                    ->required()
                                    ->live()
                                    ->preload()
                                    // ->createOptionForm(
                                    //     CursosResource::getFormFields()
                                    // )
                                    ->columnSpan(3),

                                Forms\Components\Toggle::make('certificar')
                                    ->label('Gerar Certificados')
                                    ->live()
                                    ->default(false)
                                    ->columnSpan(3),


                                Forms\Components\RichEditor::make('observacao')
                                    ->label('Observação')
                                    ->columnSpanFull(),
                            ])
                            ->columnSpanFull()
                    ]),
                    Tab::make('Certificação')
                        ->schema([
                            Toggle::make('totalatvd')
                                ->label('Somar Participações')
                                ->hintColor('primary')
                                ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Certificar somente a soma da carga horária de atividades do curso/evento onde o aluno foi aprovado')
                                ->default(false)
                                ->columnSpan(3),


                            // Forms\Components\Select::make('curso_id')
                            //     ->options(
                            //         function () {
                            //             return Cursos::where('ativo', true)->pluck('id', 'titulo');
                            //         }
                            //     )
                            //     // ->searchable(['titulo'])
                            //     ->label('Curso')
                            //     ->required()
                            //     ->live()
                            //     ->preload()
                            //     ->columnSpan(3),
                            Forms\Components\Select::make('cursoatividade_id')
                                // ->required()
                                ->multiple(true)
                                ->options(function (Get $get) {
                                    return CursoAtividades::where('curso_id', $get('curso_id'))->pluck('titulo', 'id');
                                })
                                // ->searchable(['titulo'])
                                ->label('Atividade')
                                ->preload()
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
                                ->preload()
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
                                    $assinaturas = CertificadoModelos::all()->pluck('assinado_por');
                                    return User::whereIn('id', $assinaturas)->pluck('name', 'id');
                                })
                                // ->relationship(
                                //     name: 'assinado',
                                //     titleAttribute: 'name',
                                // )
                                // ->searchable(['name'])
                                ->label('Assinado por')
                                ->preload()
                                ->columnSpan(3),
                            Forms\Components\Toggle::make('disponivel')
                                ->label('Disponibilizar')
                                ->default(false)
                                ->columnSpan(3),

                            Forms\Components\RichEditor::make('observacao')
                                ->label('Observação')
                                ->columnSpanFull(),
                        ])
                        ->columns(6)
                        ->live()
                        ->hidden(function (Get $get) {
                            return !$get('certificar');
                        }),
                ])
                ->columnSpanFull()

        ];
    }
    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query->with([
                'curso',
                'atividade.curso',
                'aluno.perfil.tipoPessoa',
                'situacaoinscricao',
                'atualizado'
            ]))
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
                SelectFilter::make('curso')
                    ->relationship(
                        name: 'curso',
                        titleAttribute: 'titulo',
                    )
                    ->searchable(['titulo'])
                    ->query(function (Builder $query, $data) {
                        if ($data['value'] == null) {
                            return;
                        }
                        $curso = Cursos::find($data['value']);
                        if ($curso->temAtividades()) {
                            $atividades_ids = $curso->atividades()->pluck('id')->toArray();
                            $atividades_ids[] = $curso->id;
                            // dd($query->whereIn('curso_id', $atividades_ids)->get());
                            return $query->whereIn('curso_id', $atividades_ids);
                        }
                        return $query->where('curso_id', $data['value']);
                    })
                    // ->preload()
                    ->attribute('curso_id')
                    ->default(request()->get('curso_id'))
                    ->columnSpan(6),


                // SelectFilter::make('atividade')
                //     ->options(function () {
                //         // nome da atividade - nome do curso pai
                //         $atividades = CursoAtividades::with('curso')->get();
                //         $options = [];
                //         foreach ($atividades as $atividade) {
                //             $options[$atividade->id] = "{$atividade->titulo} - {$atividade->curso->titulo}";
                //         }
                //         return $options;
                //     })
                //     // ->searchable(['titulo'])
                //     ->preload()
                //     ->multiple()
                //     ->attribute('curso_id')
                //     ->default(request()->get('curso_id'))
                //     ->columnSpan(6),

                SelectFilter::make('aluno')
                    ->relationship(
                        name: 'aluno',
                        titleAttribute: 'name',
                    )
                    ->searchable(['name'])
                    // ->preload()
                    ->attribute('aluno_id')
                    ->columnSpan(6),

                SelectFilter::make('Situação')
                    ->relationship(
                        name: 'situacaoinscricao',
                        titleAttribute: 'descricao',
                        modifyQueryUsing: fn(Builder $query) => $query->where('grupo', '=', 'SITUACAO_INSCRICAO'),
                    )
                    // ->searchable(['descricao'])
                    ->preload()
                    ->attribute('situacaoinscricao_id')
                    ->columnSpan(3),

                Filter::make('aprovado')
                    ->query(fn(Builder $query): Builder => $query->where('aprovado', true))

            ], layout: FiltersLayout::Modal)
            ->filtersFormColumns(6)
            ->filtersFormWidth('2xl')
            ->filtersTriggerAction(
                fn(Action $action) => $action
                    ->button()
                    ->label('Filtrar'),
            )




            ->headerActions([
                Action::make('aprovados')
                    ->label('Marcar aprovados')
                    ->color('gray')
                    ->size('sm')
                    ->icon('heroicon-o-document-magnifying-glass')
                    ->action(function (array $data): void {
                        $curso = Cursos::find($data['curso_id']);
                        if (!$curso) {
                            Notification::make()
                                ->title('Curso não encontrado!')
                                ->danger()
                                ->send();
                            return;
                        }

                        $aprovados = AprovacaoService::aprovarAlunos($curso, [], $data['observacao']);

                        if ($aprovados == -1) {
                            Notification::make()
                                ->title('Erro ao processar aprovações!')
                                ->danger()
                                ->send();
                        } elseif ($aprovados == 0) {
                            Notification::make()
                                ->title('Nenhum aluno aprovado!')
                                ->warning()
                                ->send();
                        } else {
                            Notification::make()
                                ->title("{$aprovados} alunos aprovados!")
                                ->success()
                                ->send();
                        }


                        if (count($data['cursoatividade_id']) > 0) {
                            $atividadesId = $data['cursoatividade_id'];
                            $atividades = CursoAtividades::whereIn('id', $atividadesId)->get();
                            foreach ($atividades as $atividade) {
                                CertificadoService::gerarCertificados($data, $atividade);
                            }
                        } else {
                            CertificadoService::gerarCertificados($data, $curso);
                        }
                    })
                    ->form(
                        static::getFormfieldsGerarAprovacao(),
                    )
                    ->slideOver(),
                SynchronousExportAction::make('export')
                    ->exporter(EscolaCursoAlunosExporter::class)
                    ->label('Exportar')
                    ->color('primary')
                    ->icon('heroicon-o-arrow-down-tray'),
            ])
            ->recordActions([
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



                Action::make('inscrever')
                    ->hiddenLabel()
                    ->tooltip('Inscrever')
                    ->icon('heroicon-o-check-circle')
                    ->size('xl')
                    ->color('success')
                    ->visible(fn(EscolaCursoAlunos $record) => ($record->situacaoinscricao_id != config('settings.inscricao.inscrito')))
                    ->action(function (array $data, Model $record): void {
                        try {
                            $users[] = $record->aluno;

                            InscricaoService::inscrever($record->curso ?? $record->atividade, $users);

                            Notification::make()
                                ->title('Inscrição aprovada corretamente!')
                                ->success()
                                ->send();
                        } catch (Halt $e) {
                            Notification::make()
                                ->title('ERRO!' . $e)
                                ->danger()
                                ->send();
                        }
                    })
                    ->requiresConfirmation(),

                Action::make('indeferir')
                    ->hiddenLabel()
                    ->tooltip('Indeferir')
                    ->icon('heroicon-o-x-circle')
                    ->size('xl')
                    ->color('danger')
                    ->visible(fn(EscolaCursoAlunos $record) => ($record->situacaoinscricao_id != config('settings.inscricao.indeferido')))
                    ->action(function (Model $record): void {
                        try {
                            $transacao = EscolaCursoAlunos::find($record->id);
                            $transacao->situacaoinscricao_id = config('settings.inscricao.indeferido');
                            $transacao->save();

                            Notification::make()
                                ->title('Indeferido com Sucesso!')
                                ->success()
                                ->send();
                        } catch (Halt $e) {
                            Notification::make()
                                ->title('ERRO!' . $e)
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('preinscrever')
                    ->hiddenLabel()
                    ->tooltip('Tornar pré-inscrito')
                    ->icon('heroicon-o-x-circle')
                    ->size('xl')
                    ->color('warning')
                    ->visible(fn(EscolaCursoAlunos $record) => ($record->situacaoinscricao_id != config('settings.inscricao.preinscricao')))
                    ->action(function (Model $record): void {
                        try {
                            $transacao = EscolaCursoAlunos::find($record->id);
                            $transacao->situacaoinscricao_id = config('settings.inscricao.preinscricao');
                            $transacao->save();

                            Notification::make()
                                ->title('Pré Inscrito com Sucesso!')
                                ->success()
                                ->send();
                        } catch (Halt $e) {
                            Notification::make()
                                ->title('ERRO!' . $e)
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->toolBarActions([
                DeleteBulkAction::make(),
                BulkAction::make('aprovar')
                    ->requiresConfirmation()
                    ->modalHeading("Atenção")
                    ->modalHeading("Atenção")
                    ->modalDescription(function ($records): string {
                        foreach ($records as $aluno) {
                            if ($aluno->situacaoinscricao_id != config('settings.inscricao.inscrito')) {
                                return "Existem alunos selecionados que não estão inscritos. Você tem certeza que deseja prosseguir? Caso sim, os alunos não inscritos serão inscritos e aprovados.";
                            }
                        }
                        $count = count($records);
                        return "Você está prestes a aprovar {$count} aluno(s). Deseja continuar?";
                    })
                    ->label('Aprovar')
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->action(function ($records): void {
                        try {
                            $count = $records->count();

                            EscolaCursoAlunos::whereKey($records->pluck('id'))->update([
                                'aprovado' => true,
                                'updated_at' => now(),
                            ]);

                            Notification::make()
                                ->title("{$count} aprovados com Sucesso!")
                                ->success()
                                ->send();
                        } catch (Halt $e) {
                            Notification::make()
                                ->title('ERRO!' . $e)
                                ->danger()
                                ->send();
                        }
                    }),

                BulkAction::make('reprovar')
                    ->requiresConfirmation()
                    ->modalHeading("Atenção")
                    ->modalDescription(function ($records): string {
                        $count = count($records);
                        return "Você está prestes a reprovar {$count} aluno(s). Deseja continuar?";
                    })
                    ->label('Reprovar')
                    ->icon('heroicon-o-x-circle')
                    ->color('warning')
                    ->action(function ($records): void {
                        try {
                            $count = $records->count();

                            EscolaCursoAlunos::whereKey($records->pluck('id'))->update([
                                'aprovado' => false,
                                'updated_at' => now(),
                            ]);

                            Notification::make()
                                ->title("{$count} reprovados com Sucesso!")
                                ->success()
                                ->send();
                        } catch (Halt $e) {
                            Notification::make()
                                ->title('ERRO!' . $e)
                                ->danger()
                                ->send();
                        }
                    }),

                BulkAction::make('inscrever')
                    ->requiresConfirmation()
                    ->modalHeading("Atenção")
                    ->label('Inscrever')
                    ->icon('heroicon-o-check-circle')
                    ->action(function ($records): void {
                        try {

                            foreach ($records as $aluno) {
                                if ($aluno->situacaoinscricao_id != config('settings.inscricao.inscrito')) {
                                    InscricaoService::inscrever($aluno->curso ?? $aluno->atividade, [$aluno->aluno]);
                                }
                            }

                            Notification::make()
                                ->title("Inscrições efetuadas corretamente!")
                                ->success()
                                ->send();
                        } catch (Halt $e) {
                            Notification::make()
                                ->title('ERRO!' . $e)
                                ->danger()
                                ->send();
                        }
                    }),

                BulkAction::make('preinscrever')
                    ->requiresConfirmation()
                    ->modalHeading("Atenção")
                    ->modalDescription(function ($records): string {
                        $count = count($records);
                        return "Você está prestes a pré-inscrever {$count} aluno(s). Deseja continuar?";
                    })
                    ->label('Pré Inscrever')
                    ->icon('heroicon-o-check-circle')
                    ->action(function ($records): void {
                        try {
                            foreach ($records as $aluno) {
                                $transacao = EscolaCursoAlunos::find($aluno->id);
                                $transacao->situacaoinscricao_id = config('settings.inscricao.preinscricao');
                                $transacao->save();
                            }
                            $preinscritos = count($records);

                            Notification::make()
                                ->title("{$preinscritos} alunos alterados com Sucesso!")
                                ->success()
                                ->send();
                        } catch (Halt $e) {
                            Notification::make()
                                ->title('ERRO!' . $e)
                                ->danger()
                                ->send();
                        }
                    }),


            ])
            ->persistFiltersInSession()
            ->selectCurrentPageOnly()
            ->paginated([50, 100, 150, 200, 'all'])
            ->defaultPaginationPageOption(50)
            ->striped()
            ->deferFilters()
            ->selectable()
            ->deferLoading();
    }
    public static function getNavigationBadge(): ?string
    {
        $count = number_format(static::getModel()::count(), 0, ',', '.');
        return $count;
    }
    public static function getNavigationBadgeColor(): ?string
    {
        return 'info';
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
            'index' => Pages\ListCursoAlunos::route('/'),
            'create' => Pages\CreateCursoAlunos::route('/create'),
            'edit' => Pages\EditCursoAlunos::route('/{record}/edit'),
        ];
    }

    // public static function getGlobalSearchResultTitle(Model $record): string|Htmlable
    // {
    //     return $record->aluno->name;
    // }
    // public static function getGloballySearchableAttributes(): array
    // {
    //     return ['atividade.curso.titulo', 'aluno.name', 'situacaoinscricao.descricao'];
    // }
    // public static function getGlobalSearchResultDetails(Model $record): array
    // {
    //     return [
    //         'Curso' => $record->curso->titulo ?? $record->atividade->titulo,
    //         'Aluno' => $record->aluno->name,
    //         'Situação' => $record->situacaoinscricao->descricao,
    //     ];
    // }
}
