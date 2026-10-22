<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\MeusCursosResource\Pages;
use App\Models\Escola\CursoAlunos;
use App\Models\Escola\CursoCertificados;
use App\Models\Escola\Cursos;
use App\Models\User;
use App\Services\Pedagogico\InscricaoService;
use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\RichEditor;
use Filament\Infolists;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class MeusCursosResource extends Resource
{
    protected static ?string $model = Cursos::class;

    protected static ?string $recordTitleAttribute = 'titulo';

    protected static ?string $modelLabel = 'Meus Curso';

    protected static ?string $pluralModelLabel = 'Meus Cursos';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationLabel = 'Meus Cursos';

    protected static ?int $navigationSort = 1;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('titulo')
                    ->label('Título')
                    ->wrap()
                    ->searchable()
                    ->html()
                    ->sortable()
                    ->weight(FontWeight::Bold),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(function ($state) {
                        return match ($state) {
                            'Concluído' => 'gray',
                            'Em Andamento' => 'success',
                            'Não Iniciado' => 'info',
                            default => 'secondary',
                        };
                    })
                    ->getStateUsing(function ($record) {
                        if ($record->termino && Carbon::now()->greaterThan(Carbon::parse($record->termino))) {
                            return 'Concluído';
                        } elseif ($record->inicio && Carbon::now()->lessThan(Carbon::parse($record->inicio))) {
                            return 'Não Iniciado';
                        }

                        return 'Em Andamento';
                    }),
                Tables\Columns\TextColumn::make('atividades_count')
                    ->label('Atividades')
                    ->getStateUsing(function ($record) {
                        return $record->atividades()->count();
                    }),

                Tables\Columns\TextColumn::make('situacao')
                    ->label('Situação da Inscrição')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'Não Inscrito' => 'danger',
                        'Pré-inscrito' => 'warning',
                        'Inscrito' => 'success',
                        'Instrutor' => 'info',
                        default => 'secondary',
                    })
                    ->getStateUsing(function ($record) {
                        $userId = Filament::auth()->id();
                        if ($record->instrutores()->where('instrutor_id', $userId)->exists()) {
                            return 'Instrutor';
                        }
                        if ($record->alunos()->where('aluno_id', $userId)->where('situacaoinscricao_id', config('settings.inscricao.inscrito'))->exists()) {
                            return 'Inscrito';
                        }
                        if ($record->alunos()->where('aluno_id', $userId)->where('situacaoinscricao_id', config('settings.inscricao.preinscricao'))->exists()) {
                            return 'Pré-inscrito';
                        }

                        return 'Não Inscrito';
                    }),

                Tables\Columns\TextColumn::make('inicio')
                    ->label('Início')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('carga_horaria')
                    ->label('C.H.')
                    ->suffix('h')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('alunos_count')
                    ->label('Alunos')
                    ->getStateUsing(function ($record) {
                        return $record->alunos()->count();
                    })
                    ->badge()
                    ->color('success'),

            ])
            ->modifyQueryUsing(function (Builder $query) {
                $userId = Filament::auth()->id();

                $query->where(function ($q) use ($userId) {
                    $q->whereHas('instrutores', fn ($q2) => $q2->where('instrutor_id', $userId))
                        ->orWhereHas('alunos', fn ($q2) => $q2->where('aluno_id', $userId));
                })
                    ->with(['atividades.tipoLocal', 'alunos', 'instrutores', 'comentarios']);
            })

            ->filters([
                SelectFilter::make('uuid')
                    ->hidden()
                    ->default(request()->get('curso'))
                    ->options(fn () => Cursos::where('uuid', request()->get('curso'))->pluck('titulo', 'uuid')),

                SelectFilter::make('tipo_de_participacao')
                    ->label('Tipo de Participação')
                    ->selectablePlaceholder(false)
                    ->options([
                        'instrutor' => 'Instrutor',
                        'aluno' => 'Aluno',
                        'todos' => 'Todos',
                    ])
                    ->default('todos')
                    ->hidden(fn () => User::find(Filament::auth()->id())?->isInstrutor() !== true)
                    ->query(function (Builder $query, array $data) {
                        $userId = Filament::auth()->id();
                        $tipo = $data['value'] ?? 'todos';

                        if ($tipo === 'instrutor') {
                            $query->whereHas('instrutores', fn (Builder $q) => $q->where('instrutor_id', $userId));
                        } elseif ($tipo === 'aluno') {
                            $query->whereHas('alunos', fn (Builder $q) => $q->where('aluno_id', $userId));
                        } elseif ($tipo === 'todos') {
                            $query->where(function (Builder $q) use ($userId) {
                                $q->whereHas('instrutores', fn (Builder $sub) => $sub->where('instrutor_id', $userId))
                                    ->orWhereHas('alunos', fn (Builder $sub) => $sub->where('aluno_id', $userId));
                            });
                        }
                    }),
            ])
            ->recordActions([
                Action::make('plano_curso')
                    ->label('Editar Plano de Curso')
                    ->icon('heroicon-o-pencil-square')
                    ->form(
                        function ($record) {
                            return static::getInstrutorFormFields($record);
                        }
                    )
                    // modal em tela cheia
                    ->size('full')
                    ->modalWidth('full')
                    ->action(function ($record, $data) {
                        $record->update([
                            'justificativa' => $data['justificativa'] ?? null,
                            'objetivo_geral' => $data['objetivo_geral'] ?? null,
                            'conteudo_programatico' => $data['conteudo_programatico'] ?? null,
                            'abordagem_pedagogica' => $data['abordagem_pedagogica'] ?? null,
                            'avaliacao_cursistas' => $data['avaliacao_cursistas'] ?? null,
                            'referencias_bibliograficas' => $data['referencias_bibliograficas'] ?? null,
                            'curriculo_resumido' => $data['curriculo_resumido'] ?? null,
                        ]);
                    })
                    ->color('primary')
                    ->visible(function ($record) {
                        $userId = Filament::auth()->id();
                        $isInstrutor = $record->instrutores()->where('instrutor_id', $userId)->exists();

                        return $isInstrutor;
                    }),
                Action::make('Cancelar Inscrição')
                    ->tooltip('Cancelar Inscrição')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(function ($record) {

                        $cursoaluno = CursoAlunos::where('curso_id', $record->id)
                            ->where('aluno_id', Filament::auth()->id())
                            ->where('situacaoinscricao_id', config('settings.inscricao.inscrito'))
                            ->exists();

                        return $cursoaluno;
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Cancelar inscrição?')
                    ->modalDescription('Tem certeza que deseja cancelar sua inscrição neste curso? Esta ação não poderá ser desfeita.')
                    ->modalWidth('sm')
                    ->action(function ($record) {
                        $inscricao = CursoAlunos::where('curso_id', $record->id)
                            ->where('aluno_id', Filament::auth()->id())
                            ->first();

                        InscricaoService::cancelarInscricao($record, [$inscricao->aluno]);
                    }),

                Action::make('detalhes')
                    // ->label('Ver Detalhes')
                    ->icon('heroicon-o-eye')
                    ->modalHeading(fn ($record) => 'Detalhes: '.$record->titulo)
                    ->modalWidth('7xl')
                    ->modalCancelAction(false)
                    ->modalSubmitAction(false)
                    ->infolist(fn ($record) => [
                        Section::make('Informações Gerais')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        Infolists\Components\TextEntry::make('titulo')
                                            ->label('Título do Curso')
                                            ->weight(FontWeight::Bold)
                                            ->size('lg'),

                                        Infolists\Components\TextEntry::make('ano')
                                            ->label('Ano')
                                            ->badge()
                                            ->color('primary'),
                                    ]),
                                Grid::make(3)
                                    ->schema([

                                        Infolists\Components\TextEntry::make('descricao')
                                            ->label('Descrição')
                                            ->html()
                                            ->columnSpanFull(),

                                        Infolists\Components\TextEntry::make('local')
                                            ->label('Local')
                                            ->icon('heroicon-o-building-office')
                                            ->getStateUsing(function ($record) {
                                                return $record->tipoLocal()->first()->descricao ?? 'A definir';
                                            })
                                            ->visible(fn ($record) => ! $record->temAtividades()),

                                        Infolists\Components\TextEntry::make('presencas')
                                            ->label('Presenças')
                                            ->badge()
                                            ->getStateUsing(function ($record) {
                                                $user = User::find(Filament::auth()->id());
                                                $frequencias_aluno = $user->frequencias()
                                                    ->where('curso_id', $record->id)
                                                    ->where('situacaofrequencia_id', config('settings.frequencia.presente'))
                                                    ->count();
                                                $total_frequencias = $record->agendas()->count();

                                                return "{$frequencias_aluno} / {$total_frequencias}";
                                            })
                                            ->color('info'),

                                        Infolists\Components\TextEntry::make('aprovacao')
                                            ->label('Presença Mínima')
                                            ->icon('heroicon-o-building-office')
                                            ->getStateUsing(function ($record) {
                                                return "{$record->aprovacao}%";
                                            }),
                                    ])
                                    ->visible(function ($record) {
                                        return ! $record->temAtividades();
                                    }),

                                Grid::make(4)
                                    ->schema([
                                        Infolists\Components\TextEntry::make('inicio')
                                            ->label('Data de Início')
                                            ->date('d/m/Y H:i')
                                            ->icon('heroicon-o-calendar'),

                                        Infolists\Components\TextEntry::make('termino')
                                            ->label('Data de Término')
                                            ->date('d/m/Y H:i')
                                            ->icon('heroicon-o-calendar'),

                                        Infolists\Components\TextEntry::make('carga_horaria')
                                            ->label('Carga Horária')
                                            ->suffix(' horas')
                                            ->icon('heroicon-o-clock'),

                                    ]),
                            ]),

                        Section::make('Detalhes Pedagógicos')
                            ->schema([
                                Infolists\Components\TextEntry::make('objetivo_geral')
                                    ->label('Objetivo Geral')
                                    ->html()
                                    ->columnSpanFull()
                                    ->visible(fn ($record) => ! empty($record->objetivo_geral)),

                                Infolists\Components\TextEntry::make('conteudo_programatico')
                                    ->label('Conteúdo Programático')
                                    ->html()
                                    ->columnSpanFull()
                                    ->visible(fn ($record) => ! empty($record->conteudo_programatico)),

                                Grid::make(2)
                                    ->schema([
                                        Infolists\Components\TextEntry::make('publico_alvo')
                                            ->label('Público Alvo')
                                            ->visible(fn ($record) => ! empty($record->publico_alvo)),

                                        Infolists\Components\TextEntry::make('numero_vagas')
                                            ->label('Número de Vagas')
                                            ->visible(fn ($record) => ! empty($record->numero_vagas)),
                                    ]),
                            ])->visible(function ($record) {
                                if ($record->objetivo_geral || $record->conteudo_programatico || $record->publico_alvo || $record->numero_vagas) {
                                    return true;
                                }

                                return false;
                            })
                            ->collapsible(),

                        Section::make('Participantes')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        Infolists\Components\RepeatableEntry::make('instrutores')
                                            ->label('Instrutores')
                                            ->schema([
                                                Infolists\Components\TextEntry::make('name')
                                                    ->label('')
                                                    ->icon('heroicon-o-user')
                                                    ->getStateUsing(function ($record) {
                                                        return $record->instrutor->name;
                                                    })
                                                    ->weight(FontWeight::Medium),
                                            ])
                                            ->visible(fn ($record) => $record->instrutores()->count() > 0),

                                        Infolists\Components\TextEntry::make('alunos_count')
                                            ->label('Total de Alunos')
                                            ->getStateUsing(fn ($record) => $record->alunos()->count())
                                            ->badge()
                                            ->color('success')
                                            ->icon('heroicon-o-users'),
                                    ]),
                            ])->collapsible(),

                        Section::make('Atividades')
                            ->schema([
                                Infolists\Components\RepeatableEntry::make('atividades')
                                    ->label('')
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                Infolists\Components\TextEntry::make('titulo')
                                                    ->label('Atividade')
                                                    ->weight(FontWeight::Bold)
                                                    ->size('lg')
                                                    ->icon('heroicon-o-academic-cap')
                                                    ->iconColor('primary'),

                                                Infolists\Components\TextEntry::make('inicio_termino')
                                                    ->label('Data/Hora')
                                                    ->icon('heroicon-o-calendar')
                                                    ->getStateUsing(function ($record) {
                                                        if ($record->inicio && $record->termino) {

                                                            $inicio = Carbon::parse($record->inicio)->format('d/m/Y H:i');
                                                            $termino = Carbon::parse($record->termino)->format('d/m/Y H:i');

                                                            return "{$inicio} - {$termino}";
                                                        }

                                                        return 'A definir';
                                                    })
                                                    ->iconColor('success'),

                                                Infolists\Components\TextEntry::make('local')
                                                    ->label('Local')
                                                    ->icon('heroicon-o-building-office')
                                                    ->getStateUsing(function ($record) {
                                                        // dd($record);
                                                        return $record->tipoLocal->descricao ?? 'A definir';
                                                    }),

                                                Infolists\Components\TextEntry::make('presencas')
                                                    ->label('Presenças')
                                                    ->badge()
                                                    ->getStateUsing(function ($record) {
                                                        if ($record->alunos()->where('aluno_id', Filament::auth()->id())->count() == 0) {
                                                            return 'Não inscrito';
                                                        }
                                                        $user = User::find(Filament::auth()->id());
                                                        $frequencias_aluno = $user->frequencias()
                                                            ->where('curso_id', $record->id)
                                                            ->where('situacaofrequencia_id', config('settings.frequencia.presente'))
                                                            ->count();
                                                        $total_frequencias = $record->agendas()->count();

                                                        return "{$frequencias_aluno} / {$total_frequencias}";
                                                    })
                                                    ->color(function ($record) {
                                                        if ($record->alunos()->where('aluno_id', Filament::auth()->id())->count() == 0) {
                                                            return 'warning';
                                                        }

                                                        return 'info';
                                                    }),

                                                Infolists\Components\TextEntry::make('aprovacao')
                                                    ->label('Presença Mínima')
                                                    ->icon('heroicon-o-building-office')
                                                    ->getStateUsing(function ($record) {
                                                        return "{$record->aprovacao}%";
                                                    }),
                                            ]),
                                    ]),
                            ])
                            ->collapsible()
                            ->visible(function ($record) {
                                return $record->temAtividades();
                            }),

                        Section::make('Certificados')
                            ->schema([
                                Infolists\Components\RepeatableEntry::make('certificados')
                                    ->label('')
                                    ->getStateUsing(function ($record) {
                                        if ($record->temAtividades()) {
                                            $atividadesId = $record->atividades()->pluck('id');
                                            $atividadesId[] = $record->id;
                                            $certificados = CursoCertificados::whereIn('curso_id', $atividadesId)->where('pessoa_id', Filament::auth()->id())->get();

                                            // dd($record, $record->atividades, $certificados);
                                            return $certificados;
                                        } else {
                                            return $record->certificados()->where('pessoa_id', Filament::auth()->id())->get();
                                        }
                                    })
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                Infolists\Components\TextEntry::make('titulo')
                                                    ->label('Titulo')
                                                    ->weight(FontWeight::Bold)
                                                    ->getStateUsing(function ($record) {
                                                        if ($record->titulo) {
                                                            return $record->titulo;
                                                        }

                                                        return $record->curso->titulo ?? $record->atividade->titulo;
                                                    })
                                                    ->size('lg')
                                                    ->icon('heroicon-o-academic-cap')
                                                    ->iconColor('primary'),

                                                Infolists\Components\TextEntry::make('carga_horaria')
                                                    ->label('Carga Horária')
                                                    ->icon('heroicon-o-clock')
                                                    ->getStateUsing(function ($record) {
                                                        if ($record->carga_horaria) {
                                                            return $record->carga_horaria;
                                                        } else {
                                                            return $record->curso->carga_horaria ?? $record->atividade->carga_horaria;
                                                        }
                                                    }),

                                                Infolists\Components\TextEntry::make('disponivel')
                                                    ->label('Status')
                                                    ->badge()
                                                    ->tooltip(function ($record) {
                                                        if ($record->disponivel) {
                                                            return 'Certificado Disponível para Visualização e Impressão';
                                                        }

                                                        return 'Certificado Indisponível, verifique se você preencheu o formulario de avaliação após o termino do curso';
                                                    })
                                                    ->getStateUsing(function ($record) {
                                                        if ($record->disponivel) {
                                                            return 'Disponível';
                                                        }

                                                        return 'Indisponível';
                                                    })
                                                    ->color(function ($record) {
                                                        if ($record->disponivel) {
                                                            return 'success';
                                                        }

                                                        return 'warning';
                                                    }),

                                                Infolists\Components\TextEntry::make('modelo')
                                                    ->label('Modelo')
                                                    ->icon('heroicon-o-document')
                                                    ->getStateUsing(function ($record) {
                                                        return $record->modelo->titulo.' '.$record->assinado->name;
                                                    }),

                                            ]),

                                    ]),

                            ])
                            ->collapsible(),
                    ]),
            ]);
    }

    public static function getInstrutorFormFields(Cursos $curso): array
    {
        $comentarios = $curso->comentarios;

        return [
            Section::make('Justificativa')
                ->schema([

                    RichEditor::make('justificativa')
                        ->label('')
                        ->hint(function () use ($comentarios) {
                            if ($comentarios->isNotEmpty()) {
                                $c_justificativa = null;
                                foreach ($comentarios as $comentario) {
                                    $data = Carbon::parse($comentario->created_at)->format('d/m/Y H:i');
                                    if ($comentario->justificativa) {
                                        // Inserir quebra de linha entre comentários
                                        $c_justificativa .= "({$data}) - Comentário: {$comentario->justificativa} <br><br>";
                                    }
                                }
                            }
                            $texto = $c_justificativa ?? 'Nenhum comentário.';

                            return new HtmlString("
                                <div style='
                                    white-space: normal;
                                    word-break: break-word;
                                    overflow-wrap: break-word;
                                '>
                                    {$texto}
                                </div>
                            ");
                        })
                        // Adicionar via extra attributes para permitir rolagem caso haja muitos comentários
                        ->hintColor('info')
                        ->default($curso->justificativa),

                ])
                ->extraAttributes([
                    'style' => 'max-height: 600px; overflow-y: auto;',
                ]),

            Section::make('Objetivo Geral')
                ->schema([

                    RichEditor::make('objetivo_geral')
                        ->label('')
                        ->hint(function () use ($comentarios) {
                            if ($comentarios->isNotEmpty()) {
                                $c_objetivo_geral = null;
                                foreach ($comentarios as $comentario) {
                                    $data = Carbon::parse($comentario->created_at)->format('d/m/Y H:i');
                                    if ($comentario->objetivo_geral) {
                                        // Inserir quebra de linha entre comentários
                                        $c_objetivo_geral .= "({$data}) - Comentário: {$comentario->objetivo_geral} <br><br>";
                                    }
                                }
                            }
                            $texto = $c_objetivo_geral ?? 'Nenhum comentário.';

                            return new HtmlString("
                                <div style='
                                    white-space: normal;
                                    word-break: break-word;
                                    overflow-wrap: break-word;
                                '>
                                    {$texto}
                                </div>
                            ");
                        })
                        ->hintColor('info')
                        ->default($curso->objetivo_geral),
                ])->extraAttributes([
                    'style' => 'max-height: 600px; overflow-y: auto;',
                ]),

            Section::make('Conteudo Programático')->schema([

                RichEditor::make('conteudo_programatico')
                    ->label('')
                    ->hint(function () use ($comentarios) {
                        if ($comentarios->isNotEmpty()) {
                            $c_conteudo_programatico = null;
                            foreach ($comentarios as $comentario) {
                                $data = Carbon::parse($comentario->created_at)->format('d/m/Y H:i');
                                if ($comentario->conteudo_programatico) {
                                    // Inserir quebra de linha entre comentários
                                    $c_conteudo_programatico .= "({$data}) - Comentário: {$comentario->conteudo_programatico} <br><br>";
                                }
                            }
                        }
                        $texto = $c_conteudo_programatico ?? 'Nenhum comentário.';

                        return new HtmlString("
                                <div style='
                                    white-space: normal;
                                    word-break: break-word;
                                    overflow-wrap: break-word;
                                '>
                                    {$texto}
                                </div>
                            ");
                    })
                    ->hintColor('info')
                    ->default($curso->conteudo_programatico),
            ])
                ->extraAttributes([
                    'style' => 'max-height: 600px; overflow-y: auto;',
                ]),

            Section::make('Abordagem Pedagógica')->schema([

                RichEditor::make('abordagem_pedagogica')
                    ->label('')
                    ->hint(function () use ($comentarios) {
                        if ($comentarios->isNotEmpty()) {
                            $c_abordagem_pedagogica = null;
                            foreach ($comentarios as $comentario) {
                                $data = Carbon::parse($comentario->created_at)->format('d/m/Y H:i');
                                if ($comentario->abordagem_pedagogica) {
                                    // Inserir quebra de linha entre comentários
                                    $c_abordagem_pedagogica .= "({$data}) - Comentário: {$comentario->abordagem_pedagogica} <br><br>";
                                }
                            }
                        }
                        $texto = $c_abordagem_pedagogica ?? 'Nenhum comentário.';

                        return new HtmlString("
                                <div style='
                                    white-space: normal;
                                    word-break: break-word;
                                    overflow-wrap: break-word;
                                '>
                                    {$texto}
                                </div>
                            ");
                    })
                    ->hintColor('info')
                    ->default($curso->abordagem_pedagogica),

            ])->extraAttributes([
                'style' => 'max-height: 600px; overflow-y: auto;',
            ]),
            Section::make('Avaliação dos Cursistas')->schema([
                RichEditor::make('avaliacao_cursistas')
                    ->label('')
                    ->hint(function () use ($comentarios) {
                        if ($comentarios->isNotEmpty()) {
                            $c_avaliacao_cursistas = null;
                            foreach ($comentarios as $comentario) {
                                $data = Carbon::parse($comentario->created_at)->format('d/m/Y H:i');
                                if ($comentario->avaliacao_cursistas) {
                                    // Inserir quebra de linha entre comentários
                                    $c_avaliacao_cursistas .= "({$data}) - Comentário: {$comentario->avaliacao_cursistas} <br><br>";
                                }
                            }
                        }
                        $texto = $c_avaliacao_cursistas ?? 'Nenhum comentário.';

                        return new HtmlString("
                                <div style='
                                    white-space: normal;
                                    word-break: break-word;
                                    overflow-wrap: break-word;
                                '>
                                    {$texto}
                                </div>
                            ");
                    })
                    ->hintColor('info')
                    ->default($curso->avaliacao_cursistas),
            ])->extraAttributes([
                'style' => 'max-height: 600px; overflow-y: auto;',
            ]),

            Section::make('Referências Bibliográficas')->schema([

                RichEditor::make('referencias_bibliograficas')
                    ->label('')
                    ->hint(function () use ($comentarios) {
                        if ($comentarios->isNotEmpty()) {
                            $c_referencias_bibliograficas = null;
                            foreach ($comentarios as $comentario) {
                                $data = Carbon::parse($comentario->created_at)->format('d/m/Y H:i');
                                if ($comentario->referencias_bibliograficas) {
                                    // Inserir quebra de linha entre comentários
                                    $c_referencias_bibliograficas .= "({$data}) - Comentário: {$comentario->referencias_bibliograficas} <br><br>";
                                }
                            }
                        }
                        $texto = $c_referencias_bibliograficas ?? 'Nenhum comentário.';

                        return new HtmlString("
                                <div style='
                                    white-space: normal;
                                    word-break: break-word;
                                    overflow-wrap: break-word;
                                '>
                                    {$texto}
                                </div>
                            ");
                    })
                    ->hintColor('info')
                    ->default($curso->referencias_bibliograficas),
            ])->extraAttributes([
                'style' => 'max-height: 600px; overflow-y: auto;',
            ]),

            Section::make('Currículo Resumido')->schema([

                RichEditor::make('curriculo_resumido')
                    ->label('')
                    ->hint(function () use ($comentarios) {
                        if ($comentarios->isNotEmpty()) {
                            $c_curriculo_resumido = null;
                            foreach ($comentarios as $comentario) {
                                $data = Carbon::parse($comentario->created_at)->format('d/m/Y H:i');
                                if ($comentario->curriculo_resumido) {
                                    // Inserir quebra de linha entre comentários
                                    $c_curriculo_resumido .= "({$data}) - Comentário: {$comentario->curriculo_resumido} <br><br>";
                                }
                            }
                        }
                        $texto = $c_curriculo_resumido ?? 'Nenhum comentário.';

                        return new HtmlString("
                                <div style='
                                    white-space: normal;
                                    word-break: break-word;
                                    overflow-wrap: break-word;
                                '>
                                    {$texto}
                                </div>
                            ");
                    })
                    ->hintColor('info')
                    ->default($curso->curriculo_resumido),

            ])->extraAttributes([
                'style' => 'max-height: 600px; overflow-y: auto;',
            ]),
        ];
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
            'index' => Pages\ListMeusCursos::route('/'),
        ];
    }
}
