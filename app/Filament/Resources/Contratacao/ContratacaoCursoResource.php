<?php

namespace App\Filament\Resources\Contratacao;

use App\Filament\Resources\Contratacao\ContratacaoCursoResource\Pages;
use App\Filament\Resources\Contratacao\ContratacaoCursoResource\RelationManagers;
use App\Models\Contratacao\Processo;
use App\Models\Contratacao\ProcessoCursos;
use App\Models\Escola\Cursos;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ContratacaoCursoResource extends Resource
{
    protected static ?string $model = Cursos::class;

    protected static ?string $recordTitleAttribute = 'titulo';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';

    protected static string|\UnitEnum|null $navigationGroup = 'Contratação';

    protected static ?string $navigationLabel = 'Contratação por Curso';

    protected static ?string $modelLabel = 'Contratação por Curso';

    protected static ?string $pluralModelLabel = 'Contratação por Cursos';

    protected static ?int $navigationSort = 3;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount(['userInstrutores', 'processosCursos as processos_count'])
            ->with(['processosCursos.processo.tipo']);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('titulo')
                    ->label('Nome do Curso')
                    ->wrap()
                    ->searchable(),

                Tables\Columns\TextColumn::make('user_instrutores_count')
                    ->label('Instrutores')
                    ->badge()
                    ->color(fn($state): string => $state > 0 ? 'success' : 'gray')
                    ->sortable(),

                Tables\Columns\TextColumn::make('tipos_processos')
                    ->label('Processos por Tipo')
                    ->badge()
                    ->color('info')
                    ->getStateUsing(function (Cursos $record): array {
                        return $record->processosCursos
                            ->map(fn(ProcessoCursos $processoCurso) => $processoCurso->processo?->tipo?->descricao)
                            ->filter()
                            ->countBy()
                            ->map(fn($count, $tipo): string => "{$tipo}: {$count}")
                            ->values()
                            ->all();
                    }),

                Tables\Columns\TextColumn::make('inicio')
                    ->label('Início')
                    ->dateTime('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('termino')
                    ->label('Término')
                    ->dateTime('d/m/Y'),
            ])
            ->filters([
                Tables\Filters\Filter::make('ano_atual')
                    ->label('Cursos deste ano (' . date('Y') . ')')
                    ->toggle()
                    ->default()
                    ->query(fn(Builder $query): Builder => $query->where('ano', date('Y'))),

                Tables\Filters\SelectFilter::make('instrutor')
                    ->label('Filtrar por Instrutor')
                    ->relationship('userInstrutores', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('numero_processo')
                    ->form([
                        Forms\Components\TextInput::make('numero_processo')
                            ->label('Número do Processo'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            filled($data['numero_processo'] ?? null),
                            fn(Builder $query, $numero): Builder => $query->whereHas(
                                'processosCursos.processo',
                                fn(Builder $processoQuery): Builder => $processoQuery->where('numero', 'like', "%{$numero}%")
                            )
                        );
                    }),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                        ->label('Gerenciar')
                        ->icon('heroicon-o-cog-8-tooth')
                        ->color('gray'),

                    Action::make('vincular_contratacao')
                        ->label('Vincular Processo Existente')
                        ->icon('heroicon-o-link')
                        ->color('success')
                        ->modalHeading('Vincular Processo ao Curso')
                        ->form([
                            Forms\Components\Select::make('instrutor_id')
                                ->label('Instrutor Associado (opcional)')
                                ->options(fn(Cursos $record): array => $record->userInstrutores()->pluck('users.name', 'users.id')->all())
                                ->searchable()
                                ->preload(),

                            Forms\Components\Select::make('processo_id')
                                ->label('Processo Base')
                                ->options(fn(): array => Processo::query()->pluck('numero', 'id')->all())
                                ->searchable()
                                ->preload()
                                ->required(),
                        ])
                        ->action(function (Cursos $record, array $data): void {
                            ProcessoCursos::firstOrCreate([
                                'curso_id' => $record->id,
                                'processo_id' => $data['processo_id'],
                                'instrutor_id' => $data['instrutor_id'] ?? null,
                            ]);
                        }),

                    Action::make('cadastrar_processo')
                        ->label('Cadastrar Novo Processo')
                        ->icon('heroicon-o-plus-circle')
                        ->color('primary')
                        ->modalHeading('Registrar Novo Processo de Contratação')
                        ->model(Processo::class)
                        ->form(ProcessoResource::getFormfields())
                        ->action(function (Cursos $record, array $data): void {
                            DB::transaction(function () use ($record, $data): void {
                                $processo = self::createProcessoComVinculos($record, $data);

                                \Filament\Notifications\Notification::make()
                                    ->title('Processo criado e vinculado com sucesso!')
                                    ->success()
                                    ->send();
                            });
                        }),
                ])
                    ->size('lg')
                    ->color('info')
                    ->icon('heroicon-o-bars-arrow-down')
                    ->tooltip('Ações'),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ])
            ->selectCurrentPageOnly()
            ->paginated([50, 100, 150, 200, 'all'])
            ->defaultPaginationPageOption(50)
            ->striped()
            ->deferLoading();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(fn(?Cursos $record): string => $record ? "Resumo do Curso: {$record->titulo}" : 'Resumo do Curso')
                    ->schema([
                        Forms\Components\TextInput::make('titulo')
                            ->label('Título')
                            ->disabled()
                            ->columnSpanFull(),

                        Forms\Components\DatePicker::make('inicio')
                            ->disabled(),

                        Forms\Components\DatePicker::make('termino')
                            ->disabled(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('Instrutores pendentes de vínculo')
                    ->description('Instrutores do curso que ainda não possuem processo de contratação vinculado.')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->iconColor('warning')
                    ->visible(function (?Cursos $record): bool {
                        if (! $record) {
                            return false;
                        }

                        $instrutoresCurso = $record->userInstrutores->pluck('id');
                        $instrutoresProcesso = $record->processosCursos
                            ->whereNotNull('instrutor_id')
                            ->pluck('instrutor_id');

                        return $instrutoresCurso->diff($instrutoresProcesso)->isNotEmpty();
                    })
                    ->schema([
                        Actions::make([
                            Action::make('vincular_instrutores_pendentes')
                                ->label('Resolver Pendências Agora')
                                ->color('warning')
                                ->icon('heroicon-o-link')
                                ->form(function (?Cursos $record): array {
                                    $instrutoresCurso = $record?->userInstrutores->pluck('id')->all() ?? [];
                                    $instrutoresProcesso = $record?->processosCursos
                                        ->whereNotNull('instrutor_id')
                                        ->pluck('instrutor_id')
                                        ->all() ?? [];
                                    $missingIds = array_values(array_diff($instrutoresCurso, $instrutoresProcesso));

                                    return [
                                        Forms\Components\Select::make('instrutor_ids')
                                            ->label('Instrutores pendentes')
                                            ->multiple()
                                            ->options(User::query()->whereIn('id', $missingIds)->pluck('name', 'id'))
                                            ->default($missingIds)
                                            ->required(),
                                        Forms\Components\Select::make('processo_id')
                                            ->label('Processo base')
                                            ->options(fn(): array => Processo::query()->pluck('numero', 'id')->all())
                                            ->searchable()
                                            ->preload()
                                            ->required(),
                                    ];
                                })
                                ->action(function (Cursos $record, array $data, \Livewire\Component $livewire): void {
                                    foreach ($data['instrutor_ids'] as $instrutorId) {
                                        ProcessoCursos::firstOrCreate([
                                            'curso_id' => $record->id,
                                            'processo_id' => $data['processo_id'],
                                            'instrutor_id' => $instrutorId,
                                        ]);
                                    }

                                    \Filament\Notifications\Notification::make()
                                        ->title('Instrutores vinculados com sucesso!')
                                        ->success()
                                        ->send();

                                    $livewire->js('window.location.reload()');
                                }),
                        ]),
                    ])
                    ->columnSpanFull(),

                Forms\Components\Textarea::make('descricao')
                    ->label('Descrição')
                    ->dehydrateStateUsing(fn($state) => strip_tags((string) $state))
                    ->disabled()
                    ->columnSpanFull(),
            ])
            ->columns(4);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ProcessosCursosRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContratacaoCursos::route('/'),
            'edit' => Pages\EditContratacaoCurso::route('/{record}/edit'),
        ];
    }

    public static function createProcessoComVinculos(Cursos $curso, array $data): Processo
    {
        $processo = Processo::create([
            'numero' => $data['numero'] ?? null,
            'tipoprocesso_id' => $data['tipoprocesso_id'] ?? null,
            'modalidade_id' => $data['modalidade_id'] ?? null,
            'fisica_juridica' => $data['fisica_juridica'] ?? null,
            'fornecedor_id' => $data['fornecedor_id'] ?? null,
            'descricao' => $data['descricao'] ?? null,
            'ativo' => true,
            'setor_id' => $curso->setor_id,
        ]);

        foreach ($data['gestores'] ?? [] as $gestor) {
            if (empty($gestor['gestor_id'])) {
                continue;
            }

            $gestorIds = is_array($gestor['gestor_id']) ? $gestor['gestor_id'] : [$gestor['gestor_id']];
            foreach ($gestorIds as $gestorId) {
                $processo->gestores()->attach($gestorId, [
                    'principal' => $gestor['principal'] ?? false,
                ]);
            }
        }

        $instrutorIdPrincipal = null;
        foreach ($data['cursos'] ?? [] as $cursoData) {
            if (empty($cursoData['curso_id'])) {
                continue;
            }

            if ((int) $cursoData['curso_id'] === (int) $curso->id) {
                $instrutorIdPrincipal = $cursoData['instrutor_id'] ?? null;
                continue;
            }

            ProcessoCursos::create([
                'curso_id' => $cursoData['curso_id'],
                'instrutor_id' => $cursoData['instrutor_id'] ?? null,
                'processo_id' => $processo->id,
            ]);
        }

        ProcessoCursos::firstOrCreate([
            'curso_id' => $curso->id,
            'processo_id' => $processo->id,
        ], [
            'instrutor_id' => $instrutorIdPrincipal,
        ]);

        return $processo;
    }
}
