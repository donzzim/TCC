<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\MeuCertificadoResource\Pages;
use App\Models\Escola\Comendas;
use App\Models\Escola\CursoAlunos;
use App\Models\Escola\CursoAtividades;
use App\Models\Escola\CursoCertificados;
use App\Models\Escola\Cursos;
use App\Models\Escola\FormAvaliacao;
use App\Models\Escola\FormSubmission;
use App\Models\Escola\Instituicoes;
use App\Models\Escola\Mencoes;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

class MeuCertificadoResource extends Resource
{
    protected static ?string $model = CursoCertificados::class;

    protected static ?string $modelLabel = 'Meus Certificados';

    protected static ?string $puralModelLabel = 'Meus Certificados';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-folder';

    protected static ?string $navigationLabel = 'Meus Certificados';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('titulo')
                    ->required()
                    ->columnSpan(2)
                    ->label('Titulo do Certificado'),

                Select::make('instituicao')
                    ->options(Instituicoes::all()->pluck('nome', 'id'))
                    ->searchable(['nome'])
                    ->label('Instituição')
                    ->preload()
                    ->live()
                    ->required()
                    ->columnSpan(2),
                Select::make('curso_id')
                    ->options(function (Get $get) {
                        $instituicaoId = $get('instituicao');
                        if (! $instituicaoId) {
                            return [];
                        }
                        // dd($instituicaoId);
                        $options = Cursos::where('instituicao_id', $instituicaoId)
                            ->where('externo', true)
                            ->pluck('titulo', 'id')->toArray();
                        // dd($options);
                        $options[0] = 'Outros...';

                        return $options;
                    })
                    ->searchable(['nome'])
                    ->label('Curso')
                    ->required()
                    ->preload()
                    ->live()
                    ->columnSpan(2),

                Grid::make()
                    ->schema([

                        Forms\Components\TextInput::make('curso_outros_nome')
                            ->label('Informe o nome do curso')
                            ->required(),
                        Forms\Components\TextInput::make('curso_outros_aprovacao')
                            ->label('Aprovação')
                            ->suffix('%')
                            ->required()
                            ->numeric()
                            ->inputMode('decimal')
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('curso_outros_carga_horaria')
                            ->label('Carga Horária')
                            ->required()
                            ->suffix('horas')
                            ->maxLength(255)
                            ->columnSpan(1),

                    ])->visible(function (Get $get) {
                        $cursoId = $get('curso_id');

                        return $cursoId === '0' || $cursoId === 0;
                    })->columnSpan(2),

                Forms\Components\FileUpload::make('arquivo')
                    ->label('Arquivo')
                    ->required()
                    ->columnSpan(3),
            ])

            ->columns(4);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $userId = Filament::auth()->id();
                $query->where('pessoa_id', $userId);
            })
            ->columns([
                Tables\Columns\TextColumn::make('titulo')
                    ->label('Titulo')
                    ->searchable()
                    ->getStateUsing(function ($record) {
                        if ($record->titulo) {
                            return $record->titulo;
                        } else {
                            return 'Sem Titulo Disponivel';
                        }
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('curso_id')
                    ->label('Curso')
                    ->getStateUsing(function ($record) {

                        if ($record->certificable_type == Comendas::class || $record->certificable_type == Mencoes::class) {
                            return 'Comenda/Menção';
                        }

                        if ($record->curso_id == 0) {
                            return 'Outros Cursos';
                        }

                        // dd($record);
                        return $record->certificable->titulo ?? 'Outros Cursos';
                    })
                    // ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('disponivel')
                    ->label('Disponibilidade')
                    ->badge()
                    // ->searchable()
                    ->sortable()
                    ->formatStateUsing(function ($state, $record) {
                        if ($record->externo) {
                            switch ($record->validado) {
                                case config('settings.validacao.aguardando'):
                                    return 'Aguardando validação';
                                case config('settings.validacao.validado'):
                                    return 'Validado';
                                case config('settings.validacao.invalidado'):
                                    return 'Invalidado';
                                default:
                                    return 'Indisponível';
                            }
                        }

                        return $state == 1 ? 'Disponível' : 'Indisponível';
                    })
                    ->color(function ($state, $record) {
                        if ($record->externo) {
                            switch ($record->validado) {
                                case config('settings.validacao.aguardando'):
                                    return 'warning';
                                case config('settings.validacao.validado'):
                                    return 'success';
                                case config('settings.validacao.invalidado'):
                                    return 'danger';
                                default:
                                    return 'secondary';
                            }
                        }

                        return $state == 1 ? 'success' : 'warning';
                    }),
                Tables\Columns\TextColumn::make('modelo_id')
                    ->label('Modelo')
                    ->getStateUsing(function ($record) {
                        if ($record->externo) {
                            // dd($record);
                            return 'Certificado Externo';
                        }

                        return $record->modelo->titulo;
                    })
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('visualizar certificado')
                    ->label('')
                    ->tooltip('visualizar certificado')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->visible(function ($record) {
                        if ($record->externo) {
                            return $record->validado == config('settings.validacao.validado');
                        }
                        if ($record->disponivel) {
                            return true;
                        }

                        return false;
                    })
                    ->url(fn ($record): string => route('imprimir.certificado', ['uuid' => $record->uuid])),

                Action::make('preencherFormulario')
                    ->label('')
                    ->tooltip('Preencher Formulário de Avaliação')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->color('primary')
                    ->visible(function ($record) {
                        if ($record->certificable_type == Comendas::class || $record->certificable_type == Mencoes::class) {
                            return false;
                        }
                        if ($record->externo) {
                            return false;
                        }

                        $evento = $record->certificable;
                        if ($evento) {

                            if ($record->certificable::class == CursoAtividades::class) {
                                $curso = $record->certificable->curso;
                            } else {
                                $curso = $record->certificable;
                            }
                        } else {
                            $curso = Cursos::find($record->curso_id);
                            if (! $curso) {
                                return false;
                            }
                        }
                        $submitido = FormSubmission::where('user_id', $record->pessoa_id)
                            ->where('curso_id', $curso->id)
                            ->first();
                        if (! $submitido) {
                            return true;
                        } else {
                            return false;
                        }
                    })
                    ->url(function ($record) {
                        if ($record->certificable) {
                            if ($record->certificable::class == CursoAtividades::class) {
                                $curso = $record->certificable->curso;
                            } else {
                                $curso = $record->certificable;
                            }
                        } else {
                            $curso = Cursos::find($record->curso_id);
                        }
                        $aluno = CursoAlunos::where('aluno_id', $record->pessoa_id)
                            ->where('curso_id', $curso->id)
                            ->first();
                        $form = FormAvaliacao::where('ativo', true)->first();
                        // Log::info([$aluno, $form]);

                        if ($aluno && $form) {
                            return route('forms.show', ['slug' => $form->slug, 'uuid' => $aluno->uuid]);
                        }

                        // dd($record, $form, $aluno, $curso);
                        return '#';
                    })
                    ->openUrlInNewTab(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListMeuCertificados::route('/'),
            // 'create' => Pages\CreateMeuCertificado::route('/create'),
            // 'edit' => Pages\EditMeuCertificado::route('/{record}/edit'),
        ];
    }
}
