<?php

namespace App\Filament\Resources\Escola;

use UnitEnum;
use BackedEnum;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\BulkAction;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs\Tab;
use App\Filament\Actions\Tables\ViewCursoStatAction;
use App\Filament\Resources\Administrativo\RegistroFuncionalResource;
use App\Filament\Resources\Escola\CursoAtividadesResource\Pages;
use App\Filament\Resources\TagResource;
use App\Forms\Components\DateRangePicker;
use App\Models\Administrativo\Agenda;
use App\Models\Cadastro\Tabelas;
use App\Models\Escola\AreaTematica;
use App\Models\Escola\CursoAtividades;
use App\Models\Escola\Cursos;
use App\Models\Escola\FormAvaliacao;
use App\Models\Escola\LocalRealizacao;
use App\Models\Escola\Modalidade;
use App\Models\Escola\TipoAtividade;
use App\Models\Escola\TipoEvento;
use App\Models\Escola\TipoLocal;
use App\Models\Escola\TipoPublico;
use App\Models\Setores;
use App\Services\AgendaService;
use Carbon\Carbon;
use Doctrine\DBAL\Query\QueryBuilder;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Resources\Resource;
use Filament\Support\Enums\ActionSize;
use Filament\Support\Enums\Width;
use Filament\Tables;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\HtmlString;

class CursoAtividadesResource extends Resource
{
    protected static ?string $model = CursoAtividades::class;
    // protected static ?string $recordTitleAttribute = 'titulo';
    protected static ?string $modelLabel = 'Atividades';
    protected static ?string $puralModelLabel = 'Atividades';
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-calendar';
    protected static ?string $navigationLabel = 'Atividades';

    // protected static ?string $navigationParentItem = 'Cadastro';
    protected static string | UnitEnum | null $navigationGroup = 'Projeto Pedagógico';
    protected static ?int $navigationSort = 2;
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereNotNull('curso_id');
    }

    public static function getFormfields(): array
    {
        return [

            Grid::make()
                ->columns(4)
                ->columnSpanFull()
                ->schema([
                    Forms\Components\Select::make('tipoatividade_id')
                        ->options(
                            TipoAtividade::all()->pluck('descricao', 'id')
                        )
                        ->live()
                        ->required()
                        ->label('Tipo de Atividade')
                        ->default(config('settings.tipoatividade.curso')) // 2902 = Curso
                        ->preload()
                        ->columnSpan(1),

                    Forms\Components\Select::make('tipoevento_id')
                        ->options(
                            TipoEvento::all()->pluck('descricao', 'id')
                        )
                        // ->searchable(['descricao'])
                        ->label('Tipo de Evento')
                        ->preload()
                        ->hidden(fn(Get $get): bool => $get('tipoatividade_id') != config('settings.tipoatividade.curso'))
                        ->columnSpan(1),

                    Group::make()
                        ->columns(3)
                        ->columnSpan(1)
                        ->schema([

                            Forms\Components\Toggle::make('externo')
                                ->label('Curso Externo')
                                ->live()
                                ->required()
                                ->inline(false)
                                ->hidden(fn(Get $get): bool => ($get('tipoatividade_id') == config('settings.tipoatividade.curso')))
                                ->columnSpan(1),

                            Forms\Components\Toggle::make('remoto')
                                ->label('Curso Remoto')
                                ->live()
                                // ->afterStateUpdated(function (?bool $state, Set $set, Get $get) {
                                //     if ($state) {
                                //         Se for remoto, aplicar padroes nos outros campos
                                //     }
                                // })
                                ->inline(false)
                                ->columnSpan(1),
                        ]),

                    // Forms\Components\Select::make('instituicao_id')
                    //     ->relationship(
                    //         name: 'instituicao',
                    //         titleAttribute: 'nome',
                    //     )
                    //     ->searchable(['nome'])
                    //     ->label('Instituição')
                    //     ->label('Instituição')
                    //     ->preload()
                    //     ->live()
                    //     ->hidden(fn(Get $get): bool => ($get('externo') != 1))
                    //     ->createOptionForm(
                    //         InstituicoesResource::getFormFields()
                    //     )
                    //     ->columnSpan(2),
                ]),

            Tabs::make('Tabs')
                ->columnSpanFull()
                ->tabs([
                    Tab::make('Projeto Pedagógico')
                        ->icon('heroicon-m-book-open')
                        ->columns(6)
                        ->schema([

                            Grid::make()

                                ->schema([
                                    Forms\Components\TextInput::make('titulo')
                                        ->label('Tí­tulo')
                                        ->hint('site')
                                        ->hintIcon('heroicon-o-globe-alt')
                                        ->hintColor('info')
                                        ->required()
                                        ->maxLength(255)
                                        ->columnSpan(4),

                                    Forms\Components\RichEditor::make('descricao')
                                        ->label('Descrição')
                                        ->hint('site')
                                        ->hintIcon('heroicon-o-globe-alt')
                                        ->hintColor('info')
                                        ->columnSpan(4),

                                    Forms\Components\Select::make('tag_id')
                                        ->label('Tags')
                                        ->multiple()
                                        ->relationship('tags', 'descricao')
                                        ->searchable()
                                        ->preload()
                                        ->createOptionForm(
                                            TagResource::getFormFields()
                                        )
                                        ->columnSpan(4),

                                ])
                                ->columnSpan(4),

                            Grid::make()
                                ->columnSpan(2)
                                ->schema([

                                    Forms\Components\TextInput::make('aprovacao')
                                        ->label('Frequência aprovação')
                                        ->suffix('%')
                                        ->required()
                                        ->numeric()
                                        ->default(100)
                                        ->inputMode('decimal')
                                        ->columnSpan(1),

                                    Forms\Components\TextInput::make('carga_horaria')
                                        ->label('Carga Horária')
                                        ->hint('site')
                                        ->required()
                                        ->suffix('horas')
                                        ->maxLength(255)
                                        ->columnSpan(1),

                                    Forms\Components\TextInput::make('numero_vagas')
                                        ->label('Número de Vagas')
                                        ->numeric()
                                        ->columnSpan(1),

                                    Forms\Components\Select::make('modalidade_id')
                                        ->options(
                                            Modalidade::all()->pluck('descricao', 'id')
                                        )
                                        // ->searchable(['descricao'])
                                        ->label('Modalidade')
                                        ->preload()
                                        ->live()
                                        // ->createOptionForm(
                                        //     SetorResource::getFormFields()
                                        // )
                                        ->columnSpan(1),

                                    Forms\Components\TextInput::make('link')
                                        ->label('Link Reunião/Webnar')
                                        ->hidden(fn(Get $get): bool => $get('modalidade_id') != config('settings.modalidade.remoto'))
                                        ->columnSpan(2),

                                    Forms\Components\Select::make('publico')
                                        ->label('Público Alvo')
                                        ->hint('site')
                                        ->hintIcon('heroicon-o-globe-alt')
                                        ->hintColor('info')
                                        ->multiple()
                                        ->options(
                                            TipoPublico::all()->pluck('descricao', 'id')
                                        )
                                        // ->searchable()
                                        ->preload()
                                        ->createOptionForm(
                                            TipoRecursoResource::getFormFields()
                                        )
                                        ->columnSpan(2),
                                ]),

                            Grid::make()
                                ->columns(4)
                                ->columnSpanFull()
                                ->schema([

                                    Forms\Components\Select::make('areatematica_id')
                                        ->options(
                                            AreaTematica::all()->pluck('descricao', 'id')
                                        )
                                        // ->searchable(['descricao'])
                                        ->label('Ãrea Temática')
                                        ->preload()
                                        // ->createOptionForm(
                                        //     AreaTematicaResource::getFormFields()
                                        // )
                                        ->columnSpan(1),


                                    Forms\Components\Select::make('realizacao_id')
                                        ->options(
                                            LocalRealizacao::all()->pluck('descricao', 'id')
                                        )
                                        // ->searchable(['descricao'])
                                        ->label('Realizado na')
                                        ->preload()
                                        // ->createOptionForm(
                                        //     SetorResource::getFormFields()
                                        // )
                                        ->columnSpan(1),

                                    Forms\Components\Select::make('tipolocal_id')
                                        ->options(function () {
                                            return TipoLocal::get()->mapWithKeys(function ($local) {
                                                if (!$local->json || !isset($local->json['capacidade'])) {
                                                    return [$local->id => "{$local->descricao}"];
                                                }
                                                return [$local->id => "{$local->descricao} - Capacidade: {$local->json['capacidade']}"];
                                            });
                                        })
                                        // ->searchable(['descricao'])
                                        ->label('Sala/Auditório')
                                        ->preload()
                                        ->required()
                                        ->default(config('settings.sala.adefinir'))
                                        ->live()
                                        ->createOptionForm(
                                            TipoLocalResource::getFormFields()
                                        )
                                        ->columnSpan(1),

                                    Forms\Components\TextInput::make('criterio_selecao')
                                        ->label('Critério de Seleção')
                                        ->columnSpan(1),

                                    Forms\Components\Select::make('setor_id')
                                        ->options(
                                            Setores::all()->pluck('setor', 'id')
                                        )
                                        ->searchable(['setor'])
                                        ->label('Setor')
                                        ->preload()
                                        // ->createOptionForm(
                                        //     SetorResource::getFormFields()
                                        // )
                                        ->columnSpan(2),

                                ]),
                        ]),

                    Tab::make('Instrutoria')
                        ->icon('heroicon-m-users')
                        ->schema([
                            // instrutoria
                            Forms\Components\Repeater::make('instrutores')
                                ->defaultItems(0)
                                ->relationship()
                                ->collapsible()
                                ->cloneable()
                                ->columns(7)
                                ->itemLabel(fn(array $state): ?string => isset($state['titulo']) ? $state['titulo'] : 'Nenhum')
                                ->schema(
                                    CursosResource::getFormfieldsInstrutoria()
                                ),
                        ]),

                    Tab::make('Agendamento')
                        ->icon('heroicon-m-calendar')
                        ->schema([

                            Grid::make()
                                ->columns(8)
                                ->schema([

                                    DateRangePicker::make('periodo_selecionado')
                                        ->label('Selecione o PerÃ­odo')
                                        ->afterStateUpdated(function ($state, Set $set) {
                                            if (empty($state['start']) || empty($state['end'])) {
                                                return;
                                            }

                                            $set('inicio', $state['start'] . ' 00:00:00');
                                            $set('termino', $state['end'] . ' 23:59:59');
                                        })
                                        ->live()
                                        ->columnSpan(2),

                                    Grid::make()
                                        ->columns(6)
                                        ->columnSpanFull()
                                        ->schema([
                                            Section::make('Programação')
                                                ->columns(2)
                                                ->columnSpan(2)
                                                ->schema([

                                                    Forms\Components\DateTimePicker::make('inicio')
                                                        ->label('Início')
                                                        ->inlineLabel()
                                                        ->live()
                                                        ->rule(function (Get $get, Set $set) {
                                                            return function ($attribute, $value, $fail) use ($get, $set) {
                                                                $termino = $get('termino');
                                                                if (isset($termino)) {
                                                                    if ($termino < $value) {
                                                                        $fail('Data inÃ­cio não pode ser maior que a data término');
                                                                        return;
                                                                    } else {
                                                                        if (AgendaService::verificaConflito($get('tipolocal_id'), $value, $termino, [], [$get('id')] ?? [])) {
                                                                            $fail('Data conflitante');
                                                                            return;
                                                                        }
                                                                    }
                                                                }
                                                            };
                                                        })
                                                        ->reactive()
                                                        ->columnSpan(2),

                                                    Forms\Components\DateTimePicker::make('termino')
                                                        ->label('Término')
                                                        ->inlineLabel()
                                                        ->live()
                                                        ->columnSpan(2),

                                                    Forms\Components\Select::make('turno')
                                                        ->options([
                                                            'Matutino' => 'Matutino',
                                                            'Vespertino' => 'Vespertino',
                                                            'Noturno' => 'Noturno',
                                                        ])
                                                        ->label('Turno')
                                                        ->inlineLabel()
                                                        ->columnSpan(2),

                                                ]),


                                            Section::make('Inscrição')
                                                ->columns(2)
                                                ->columnSpan(2)
                                                ->schema([
                                                    Forms\Components\DateTimePicker::make('inicio_inscricao')
                                                        ->label('Início')
                                                        ->inlineLabel()
                                                        ->live()
                                                        ->columnSpan(2),

                                                    Forms\Components\DateTimePicker::make('termino_inscricao')
                                                        ->label('Término')
                                                        ->inlineLabel()
                                                        ->live()
                                                        ->columnSpan(2),
                                                ]),

                                            Section::make('Frequência')
                                                ->columns(2)
                                                ->columnSpan(2)
                                                ->schema([
                                                    Forms\Components\TextInput::make('inicio_tolerancia')
                                                        ->label('Tolerância início')
                                                        ->inlineLabel()
                                                        ->suffix('min')
                                                        ->numeric()
                                                        ->default(10)
                                                        ->columnSpan(2),

                                                    Forms\Components\TextInput::make('termino_tolerancia')
                                                        ->label('TolerÃ¢ncia término')
                                                        ->inlineLabel()
                                                        ->suffix('min')
                                                        ->numeric()
                                                        ->default(10)
                                                        ->columnSpan(2),
                                                ]),

                                        ]),

                                ]),

                        ]),

                    Tab::make('Divulgação')
                        ->icon('heroicon-m-cloud-arrow-down')
                        ->schema([

                            Forms\Components\Repeater::make('arquivos')
                                ->defaultItems(0)
                                ->relationship()
                                ->schema([

                                    Forms\Components\TextInput::make('descricao')
                                        ->label('Descrição')
                                        ->maxLength(255)
                                        ->columnSpan(2),

                                    Forms\Components\FileUpload::make('arquivo')
                                        ->label('Arquivos')
                                        ->columnSpan(2),
                                ])
                                ->columns(4),
                        ]),

                    Tab::make('Equipe de Trabalho')
                        ->icon('heroicon-m-newspaper')
                        ->schema([
                            Forms\Components\Repeater::make('registros')
                                ->defaultItems(0)
                                ->relationship()
                                ->schema(
                                    RegistroFuncionalResource::getFormfieldsEquipeTrabalho()
                                )
                                ->columnSpan(6)
                                ->columns(6),
                        ]),

                ]),
        ];
    }

    public static function getTablefields(): array
    {
        return [
            Tables\Columns\TextColumn::make('curso.titulo')
                ->label('Curso')
                ->wrap()
                ->toggleable()
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('titulo')
                ->label('Atividade')
                ->wrap()
                ->description(fn(Model $record) => new HtmlString("InÃ­cio: {$record->inicio->format('d/m/Y H:i:s')}<br/>Término: {$record->termino->format('d/m/Y H:i:s')}"))
                ->searchable()
                ->html()
                ->sortable(),
            Tables\Columns\TextColumn::make('inicio_inscricao')
                ->label(new HtmlString('Inscrição'))
                ->html()
                ->state(function (Model $record) {
                    $html = '';
                    if ($record->inicio_inscricao) {
                        $data = Carbon::parse($record->inicio_inscricao);
                        $html .= "Início: {$data->format('d/m/Y H:i:s')}";
                    }
                    if ($record->termino_inscricao) {
                        $data = Carbon::parse($record->termino_inscricao);
                        $html .= "<br/>Término: {$data->format('d/m/Y H:i:s')}";
                    }
                    return new HtmlString($html);
                })
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('numero_vagas')
                ->label('Vagas')
                ->badge()
                ->toggleable()
                ->color('success')
                ->sortable(),
            Tables\Columns\TextColumn::make('inicio_tolerancia')
                ->label(new HtmlString('Tolerância<br/>início'))
                ->suffix(' min')
                ->toggleable()
                ->description(fn(Model $record) => ($record->tolerancia_termino ? "Término: {$record->tolerancia_termino} min" : ""))
                ->searchable()
                ->sortable(),

            Tables\Columns\ToggleColumn::make('ativo')
                ->label('Ativo')
                ->searchable()
                ->sortable(),


            Tables\Columns\ToggleColumn::make('liberar_divulgacao')
                ->label('Divulgação')
                ->searchable()
                ->sortable(),
            Tables\Columns\ToggleColumn::make('liberar_inscricao')
                ->label('Inscrição')
                ->searchable()
                ->sortable(),
            Tables\Columns\ToggleColumn::make('liberar_frequencia')
                ->label('Frequência')
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('tags.descricao')
                ->label('Tags')
                ->searchable()
                ->toggleable()
                ->sortable()
                ->badge()
                ->separator(','),
        ];
    }
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components(
                CursoAtividadesResource::getFormfields(),
            )
            ->columns(6);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(
                static::getTablefields()
            )
            ->defaultSort('ativo', 'desc')
            ->filters([
                Filter::make('ativo')
                    ->label('Ativo')
                    ->toggle()
                    ->query(fn(Builder $query): Builder => $query->where('ativo', 1))
                    ->columnSpan(2)
                    ->default(),

                SelectFilter::make('curso.titulo')
                    ->relationship(name: 'curso', titleAttribute: 'titulo')
                    ->searchable(['titulo'])
                    ->preload()
                    ->attribute('curso_id')
                    ->default(request()->get('curso_id'))
                    ->columnSpan(6),

                Filter::make('liberar_divulgacao')
                    ->label('Divulgação')
                    ->toggle()
                    ->query(fn(Builder $query): Builder => $query->where('liberar_divulgacao', 1))
                    ->columnSpan(2),

                Filter::make('liberar_inscricao')
                    ->label('Inscrição')
                    ->toggle()
                    ->query(fn(Builder $query): Builder => $query->where('liberar_inscricao', 1))
                    ->columnSpan(2),

                Filter::make('liberar_frequencia')
                    ->label('Frequência')
                    ->toggle()
                    ->query(fn(Builder $query): Builder => $query->where('liberar_frequencia', 1))
                    ->columnSpan(2),

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
                ActionGroup::make([
                    ViewCursoStatAction::make()
                        ->hiddenLabel()
                        ->color('info')
                        ->tooltip('EstatÃ­sticas e Detalhes')
                        ->size('xl'),

                    Action::make('gerenciarinscricoes')
                        ->hiddenLabel()
                        ->color('info')
                        ->tooltip('Gerenciar Inscrições')
                        ->size('xl')
                        ->hidden(function (Model $record) {
                            return !$record->ativo && !$record->liberar_inscricao;
                        })
                        ->icon('heroicon-o-users')
                        ->modalWidth('7xl')
                        ->modalHeading(fn(Model $record) => 'Gerenciar Inscrições - ' . $record->titulo)
                        ->modalContent(
                            fn(Model $record) =>
                            view('filament.resources.partials.gerenciar-inscricoes', [
                                'curso' => $record
                            ])
                        )
                        ->modalCancelAction(false)
                        ->modalSubmitAction(false),
                    Action::make('inscricao')
                        ->hiddenLabel()
                        ->size('xl')
                        ->icon('heroicon-o-link')
                        // ->color('success')
                        ->tooltip('Link da Inscrição do curso')
                        ->url(fn(Model $record): string => route('realizar.inscricao', $record->uuid))
                        ->openUrlInNewTab(),

                    Action::make('frequencia')
                        ->hiddenLabel()
                        ->size('xl')
                        ->icon('heroicon-o-cursor-arrow-ripple')
                        // ->color('info')
                        ->tooltip('Link da Frequência')
                        ->url(fn(Model $record): string => route('realizar.frequencia', $record->uuid))
                        ->openUrlInNewTab(),

                    Action::make('imprimir')
                        ->hiddenLabel()
                        ->size('xl')
                        ->icon('heroicon-o-qr-code')
                        // ->color('danger')
                        ->tooltip('Imprimir QRcode da Frequência')
                        ->url(fn(Model $record): string => route('qrcode.frequencia', $record->curso->uuid))
                        ->openUrlInNewTab(),

                    Action::make('avaliacao')
                        ->hiddenLabel()
                        ->size('xl')
                        ->icon('heroicon-o-clipboard-document-check')
                        // ->color('danger')
                        ->tooltip('Avaliação de Reação')
                        // ->url(function ($record) {
                        //     $form = FormAvaliacao::find(1);
                        //     if ($form->slug) {
                        //         return route('forms.show', ['slug' => $form->slug, 'atividade' => $record->id]);
                        //     }
                        //     return;
                        // })
                        ->openUrlInNewTab(),
                ])
                    ->size('lg')
                    ->color('info')
                    ->tooltip('Ações')
                    ->icon('heroicon-o-bars-arrow-down'),
            ])
            ->toolBarActions([
                // BulkActionGroup::make([
                DeleteBulkAction::make(),

                BulkAction::make('divulgar')
                    ->label('Liberar divulgação')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->action(function (Collection $records): void {
                        $records->each(function ($record) {
                            if (!$record->liberar_divulgacao) {
                                $record->liberar_divulgacao = 1;
                                $record->save();
                            }
                        });
                    }),
                BulkAction::make('inscricao')
                    ->label('Liberar inscrição')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (Collection $records): void {
                        $records->each(function ($record) {
                            if (!$record->liberar_inscricao) {
                                $record->liberar_inscricao = 1;
                                $record->save();
                            }
                        });
                    }),
                BulkAction::make('frequencia')
                    ->label('Liberar frequência')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function (Collection $records): void {
                        $records->each(function ($record) {
                            if (!$record->liberar_frequencia) {
                                $record->liberar_frequencia = 1;
                                $record->save();
                            }
                        });
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
            'index' => Pages\ListCursoAtividades::route('/'),
            'create' => Pages\CreateCursoAtividades::route('/create'),
            'edit' => Pages\EditCursoAtividades::route('/{record}/edit'),
        ];
    }
    public static function getGlobalSearchResultTitle(Model $record): string|Htmlable
    {
        return $record->curso->titulo;
    }
    public static function getGloballySearchableAttributes(): array
    {
        return ['curso.titulo', 'turno'];
    }
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Curso' => $record->curso->titulo,
            'Turno' => $record->turno,
            'Período' => "{$record->inicio->format('d/m/Y H:i')} {$record->termino->format('d/m/Y H:i')}",
        ];
    }
}
