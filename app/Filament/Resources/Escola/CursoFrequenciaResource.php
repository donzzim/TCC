<?php

namespace App\Filament\Resources\Escola;

use App\Filament\Exports\EscolaCursoFrequenciasExporter;
use App\Filament\Exports\SynchronousExportAction;
use UnitEnum;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Schemas\Components\Section;
use App\Filament\Resources\Escola\CursoFrequenciaResource\Pages;
use App\Filament\Resources\CursoFrequenciaResource\RelationManagers;
use App\Filament\Resources\UserResource;
use App\Models\Escola\CursoAlunos;
use App\Models\Escola\CursoAtividades;
use App\Models\Escola\CursoFrequencias;
use App\Models\Escola\Cursos;
use App\Models\User;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\Width;
use Filament\Support\Exceptions\Halt;
use Filament\Tables;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CursoFrequenciaResource extends Resource
{
    protected static ?string $model = CursoFrequencias::class;
    // protected static ?string $recordTitleAttribute = 'titulo';
    protected static ?string $modelLabel = 'Frequência';
    protected static ?string $puralModelLabel = 'Frequências';
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-document-check';
    protected static ?string $navigationLabel = 'Frequências';

    // protected static ?string $navigationParentItem = 'Cadastro';
    protected static string | UnitEnum | null $navigationGroup = 'Projeto Pedagógico';
    protected static ?int $navigationSort = 4;

    public static function getTablefields(): array
    {
        return [
            Tables\Columns\TextColumn::make('curso.titulo')
                ->label('Curso')
                ->getStateUsing(function (Model $record) {
                    if (!$record->atividade) {
                        return $record->curso ? $record->curso->titulo : 'Sem Curso';
                    }
                    return "{$record->atividade->curso->titulo} - {$record->atividade->titulo}" ?? 'Sem Curso';
                })
                ->wrap()
                // ->description(function (CursoFrequencias $record) {
                //     $html = '';
                //     if (isset($record->curso->turno))
                //         $html .= "{$record->curso->turno}: ";
                //     $html .= "{$record->curso->inicio->format('d/m/Y H:i')} {$record->curso->termino->format('d/m/Y H:i')}";
                //     return $html;
                // })
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
                ->sortable()
                ->toggleable(),
            Tables\Columns\TextColumn::make('situacaofrequencia.descricao')
                ->label('Situação')
                ->label('Situação')
                ->searchable()
                ->badge()
                ->color(function (string $state) {
                    switch ($state) {
                        case 'Presente':
                            $cor = 'success';
                            break;
                        case 'Ausente':
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
            Tables\Columns\TextColumn::make('updated_at')
                ->label('Data do Registro')
                ->searchable()
                ->getStateUsing(function ($record) {
                    if ($record->situacaofrequencia_id == 2702) {
                        return null;
                    }
                    return $record->updated_at;
                })
                ->date('d/m/Y H:i')
                ->sortable()
                ->toggleable(),


            Tables\Columns\TextColumn::make('data_frequencia')
                ->label('Data Frequência')
                ->getStateUsing(function (Model $record) {
                    if (!$record->data) {
                        return $record->data_frequencia;
                    }
                    return $record->data->inicio;
                })
                ->searchable()
                ->date('d/m/Y H:i')
                ->sortable()
                ->toggleable(),

            Tables\Columns\TextColumn::make('atualizado.name')
                ->label('Atualizado Por')
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
                    Select::make('curso_id')
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

                    Select::make('cursoatividade_id')
                        ->options(function (Get $get) {
                            $cursoid = $get('curso_id');
                            if (!$cursoid) {
                                return [];
                            }

                            $curso = Cursos::find($cursoid);
                            if (!$curso) {
                                return [];
                            }
                            $atividades = $curso->atividades()->get();
                            return $atividades->pluck('titulo', 'id');
                        })
                        // ->searchable(['titulo'])
                        ->label('Atividade')
                        // ->required(function (Get $get) {
                        //     if (Cursos::find($get('curso_id'))?->atividades()->count() > 0) {
                        //         return true;
                        //     }
                        //     return false;
                        // })
                        ->preload()
                        ->createOptionForm(
                            CursosResource::getFormfieldsAtividades()
                        )
                        ->columnSpan(3),

                    Select::make('aluno_id')
                        ->options(function (Get $get) {
                            $cursoid = $get('curso_id');
                            if (!$cursoid) {
                                return [];
                            }
                            $curso = Cursos::find($cursoid);
                            if (!$curso) {
                                return [];
                            }
                            // dd($cursoid);
                            $alunos = $curso->alunos()
                                ->where('situacaoinscricao_id', config('settings.inscricao.inscrito'))
                                ->get();

                            return $alunos->map(function ($aluno) {
                                return [
                                    'id' => $aluno->aluno->id,
                                    'nome' => $aluno->aluno->name,
                                ];
                            })->pluck('nome', 'id');
                        })
                        ->hint('Somente alunos inscritos')
                        ->hintColor('danger')
                        ->searchable(['name'])
                        ->searchPrompt('Selecione o Curso primeiro')
                        ->label('Alunos')
                        ->required()
                        ->preload()
                        ->createOptionForm(
                            UserResource::getFormFields()
                        )
                        ->columnSpan(3),

                    Select::make('situacaofrequencia_id')
                        ->options(function (Get $get) {
                            $situacoes = config('settings.frequencia');
                            // dd($situacoes);
                            return collect($situacoes)->mapWithKeys(function ($descricao, $id) {
                                return [$descricao => $id];
                            });
                        })
                        ->label('Situação')
                        ->required()
                        ->default(config('settings.frequencia.presente'))
                        ->preload()
                        ->columnSpan(3),

                    DateTimePicker::make('data_frequencia')
                        ->label('Data Frequência')
                        ->required()
                        ->default(date('Y-m-d H:i:s'))
                        ->columnSpan(1),

                ])
                ->columns(6),

            RichEditor::make('observacao')
                ->label('Observação')
                ->columnSpanFull(),
        ];
    }
    public static function form(Schema $form): Schema
    {
        return $form
            ->schema(
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

                Filter::make('data_frequencia')
                    ->form([
                        Forms\Components\DatePicker::make('inicio')
                            ->label('Data Inicial'),
                        Forms\Components\DatePicker::make('fim')
                            ->label('Data Final'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if ($data['inicio'] && $data['fim']) {
                            $query->whereBetween('data_frequencia', [$data['inicio'], Carbon::parse($data['fim'])->endOfDay()]);
                        } elseif ($data['inicio']) {
                            $query->where('data_frequencia', '>=', $data['inicio']);
                        } elseif ($data['fim']) {
                            $query->where('data_frequencia', '<=', Carbon::parse($data['fim'])->endOfDay());
                        }
                    })
                    ->columnSpan(2),

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

                TernaryFilter::make('status_frequencia')
                    ->label('Status da frequência')
                    ->placeholder('Todas as frequências')
                    ->trueLabel('Somente presentes')
                    ->falseLabel('Somente ausentes')
                    ->default(true)
                    ->queries(
                        true: function (Builder $query) {
                            return $query->where('situacaofrequencia_id', config('settings.frequencia.presente'));
                        },
                        false: function (Builder $query) {
                            return $query->where('situacaofrequencia_id', config('settings.frequencia.ausente'));
                        },
                    )
                    ->columnSpan(2),

                SelectFilter::make('curso')
                    ->relationship(name: 'curso', titleAttribute: 'titulo')
                    ->searchable(['titulo'])
                    ->preload()
                    // ->attribute('curso_id')
                    ->default(request()->get('curso_id'))
                    ->query(function (Builder $query, $data) {
                        $value = $data['value'];
                        // dd($value);
                        if ($value === null) {
                            return;
                        }
                        $curso = Cursos::find($value);
                        if ($curso->temAtividades()) {
                            $atividadesIds = $curso->atividades()->pluck('id')->toArray();
                            $atividadesIds[] = $curso->id;
                            $query->whereIn('curso_id', $atividadesIds);
                        } else {
                            $query->where('curso_id', $value);
                        }
                    })
                    ->columnSpan(6),
                SelectFilter::make('atividade')
                    ->relationship(
                        name: 'atividade',
                        titleAttribute: 'inicio',
                    )
                    ->getOptionLabelFromRecordUsing(function (Model $record) {
                        $titulo = $record->curso ? $record->curso->titulo : 'Sem Curso';
                        $datainicio = $record->inicio ? Carbon::parse($record->inicio)->format('d/m/Y H:i') : 'Sem Data';
                        $datatermino = $record->termino ? Carbon::parse($record->termino)->format('d/m/Y H:i') : 'Sem Data';
                        return "{$titulo}: {$datainicio} a {$datatermino}";
                    })
                    ->searchable(['inicio'])
                    ->preload()
                    ->attribute('cursoatividade_id')
                    ->columnSpan(6),
                SelectFilter::make('aluno')
                    ->relationship(
                        name: 'aluno',
                        titleAttribute: 'name',
                    )
                    ->searchable(['name'])
                    ->preload()
                    ->attribute('aluno_id')
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
                SynchronousExportAction::make('export')
                    ->exporter(EscolaCursoFrequenciasExporter::class)
                    ->label('Exportar')
                    ->color('primary')
                    ->icon('heroicon-o-arrow-down-tray'),
            ])

            ->actions([
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

                Action::make('Presente')
                    ->hiddenLabel()
                    ->tooltip('Presente')
                    ->icon('heroicon-o-check-circle')
                    ->size('xl')
                    ->color('success')
                    ->visible(fn(CursoFrequencias $record) => ($record->situacaofrequencia_id != config('settings.frequencia.presente')))
                    ->action(function (array $data, Model $record): void {
                        try {
                            $record->presente();

                            Notification::make()
                                ->title('Frequência registrada corretamente!')
                                ->success()
                                ->send();
                        } catch (Halt $e) {
                            Notification::make()
                                ->title('ERRO!' . $e)
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('ausente')
                    ->hiddenLabel()
                    ->tooltip('Ausente')
                    ->icon('heroicon-o-x-circle')
                    ->size('xl')
                    ->color('danger')
                    ->visible(fn(CursoFrequencias $record) => ($record->situacaofrequencia_id != config('settings.frequencia.ausente')))
                    ->action(function (array $data, Model $record): void {
                        try {
                            $record->ausente();

                            Notification::make()
                                ->title('Ausência registrada corretamente!')
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
            ->toolbarActions([
                // BulkActionGroup::make([
                DeleteBulkAction::make(),

                BulkAction::make('presente')
                    ->label('Marcar como Presente')
                    ->requiresConfirmation()
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(function ($records): void {
                        try {
                            foreach ($records as $record) {
                                $record->presente();
                            }

                            Notification::make()
                                ->title('Frequências atualizadas corretamente!')
                                ->success()
                                ->send();
                        } catch (Halt $e) {
                            Notification::make()
                                ->title('ERRO!' . $e)
                                ->danger()
                                ->send();
                        }
                    }),

                BulkAction::make('ausente')
                    ->label('Marcar como Ausente')
                    ->icon('heroicon-o-x-circle')
                    ->requiresConfirmation()
                    ->color('danger')
                    ->action(function ($records): void {
                        try {
                            foreach ($records as $record) {
                                $record->ausente();
                            }
                            Notification::make()
                                ->title('Frequências atualizadas corretamente!')
                                ->success()
                                ->send();
                        } catch (Halt $e) {
                            Notification::make()
                                ->title('ERRO!' . $e)
                                ->danger()
                                ->send();
                        }
                    }),



                // ]),
            ])
            ->selectCurrentPageOnly()
            ->paginated([50, 100, 150, 200, 'all'])
            ->defaultPaginationPageOption(50)
            ->striped()
            ->deferLoading();
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
            'index' => Pages\ListCursoFrequencias::route('/'),
            'create' => Pages\CreateCursoFrequencia::route('/create'),
            'edit' => Pages\EditCursoFrequencia::route('/{record}/edit'),
        ];
    }
    public static function getGlobalSearchResultTitle(Model $record): string|Htmlable
    {
        return $record->aluno->name;
    }
    public static function getGloballySearchableAttributes(): array
    {
        return ['atividade.curso.titulo', 'aluno.name'];
    }
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Curso' => $record->atividade->curso->titulo,
            'Aluno' => $record->aluno->name,
        ];
    }
}
