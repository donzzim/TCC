<?php

namespace App\Filament\Resources\Escola;




use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkAction;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Pages\Enums\SubNavigationPosition;
use UnitEnum;
use BackedEnum;
use App\Filament\Actions\Tables\ViewCursoStatAction;
use App\Filament\Resources\Administrativo\DemandaResource;
use App\Filament\Resources\Administrativo\RegistroFuncionalResource;
use App\Filament\Resources\Escola\CursosResource\Pages;
use App\Filament\Resources\CursosResource\RelationManagers;
use App\Filament\Resources\TagResource;
use App\Filament\Resources\UserResource;
use App\Forms\Components\DateRangePicker;
use App\Forms\Components\DateRangePickerV2;
use App\Models\Administrativo\Agenda;
use App\Models\Escola\CursoComentarios;
use App\Models\Escola\Cursos;
use App\Models\Administrativo\Demanda;
use App\Models\Administrativo\RegistroFuncional;
use App\Models\Cadastro\Tabelas;
use App\Models\Contratacao\ProcessoDocumento;
use App\Models\Escola\AreaTematica;
use App\Models\Escola\CursoAlunos;
use App\Models\Escola\CursoAtividades;
use App\Models\Escola\CursoFrequencias;
use App\Models\Escola\Modalidade;
use App\Models\Escola\TipoGenero;
use App\Models\Escola\TipoLocal;
use App\Models\Escola\TipoPublico;
use App\Models\Escola\TipoRaca;
use App\Models\Escola\TipoRecursos;
use App\Models\Escola\TipoSexo;
use App\Models\Lotacao;
use App\Models\Perfil;
use App\Models\Tags;
use App\Models\User;
use App\Services\AgendaService;
use App\Services\DocumentoContratacaoService;
use App\Services\EmailService;
use App\Services\Pedagogico\CertificadoService;
use App\Services\Pedagogico\FrequenciaService;
use Carbon\Carbon;
use Closure;
use Exception;
use Filament\Actions\ActionGroup;
use Filament\Forms;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\FormsComponent;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\Width;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Contracts\Session\Session;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;
use NunoMaduro\Collision\Adapters\Phpunit\State;
use Filament\Support\Enums\ActionSize;


use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Group;

class CursosResource extends Resource
{
    protected static ?string $model = Cursos::class;
    protected static ?string $recordTitleAttribute = 'titulo';
    protected static ?string $modelLabel = 'Curso/Eventos';
    protected static ?string $puralModelLabel = 'Cursos';
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-book-open';
    protected static ?string $navigationLabel = 'Cursos/Eventos';

    // protected static ?string $navigationParentItem = 'Cadastro';
    protected static string | UnitEnum | null $navigationGroup = 'Projeto Pedagógico';
    protected static ?int $navigationSort = 1;


    public static function validarData(Get $get, $inicio, $termino, $attribute)
    {
        preg_match('/data\.atividades\.([^.]+)\.inicio/', $attribute, $matches);
        $index = $matches[1] ?? null;
        $atividades = $get('../../atividades');
        $attId = $get('id') ?? -1;
        $paiId = $get('../../id') ?? -1;
        // dd($atividades, $index, $paiId);


        $inicioAtual = Carbon::parse($inicio);
        $terminoAtual = Carbon::parse($termino);
        $horaInicioAtual = $inicioAtual->format('H:i:s');
        $horaTerminoAtual = $terminoAtual->format('H:i:s');
        // dd($get('../../id'), $paiId, $index, $matches, $atividades, $inicioAtual, $terminoAtual);

        foreach ($atividades as $chave => $atividade) {
            if ($chave == $index) {
                continue;
            }

            $inicioOutra = Carbon::parse($atividade['inicio']);
            $terminoOutra = Carbon::parse($atividade['termino']);

            $horaInicioOutra = $inicioOutra->format('H:i:s');
            $horaTerminoOutra = $terminoOutra->format('H:i:s');

            // dd($get('id'), $get('../../tipolocal_id'));
            // if ($inicioAtual >= $inicioOutra && $inicioAtual <= $terminoOutra) {
            //     if ($horaInicioAtual < $horaTerminoOutra && $horaTerminoAtual > $horaInicioOutra) {
            //         return true;
            //     }
            // }
            if (AgendaService::verificaConflito(
                $get('../../tipolocal_id'),
                $terminoAtual,
                $inicioAtual,
                [],
                [$paiId, $attId]
            )) {
                return true;
            }
        }
    }
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereNull('curso_id')->with(['atividades', 'alunos', 'frequencias', 'userInstrutores', 'publico', 'tags', 'atualizado']);
    }
    public static function getTablefields(): array
    {
        return [
            Tables\Columns\ImageColumn::make('arquivos.arquivo')
                ->label('Arquivos')
                // ->circular()
                ->height(60)
                ->toggleable()
                ->stacked(),

            Tables\Columns\TextColumn::make('titulo')
                ->label('Curso')
                ->description(fn(Cursos $record) => $record->carga_horaria)
                ->wrap()
                ->searchable()
                ->toggleable()
                ->sortable(),
            Tables\Columns\TextColumn::make('status')
                ->label('Status')
                ->wrap()
                ->toggleable()
                ->state(function (Cursos $record): HtmlString {
                    $html = '';
                    if ($record->atividades->count()) {
                        $badge = '<div class="flex"><span class="text-nowrap inline-flex items-center m-0.5 px-3 py-1 text-xs font-medium text-center ring-1 ring-slate-300 dark:ring-slate-500 text-slate-500 bg-slate-100 rounded-md dark:bg-slate-800 dark:text-slate-200">{messages} <span class="inline-flex items-center justify-center w-4 h-4 text-xs font-semibold rounded-full text-sky-900 bg-slate-200 ms-2">{count}</span></span></div>';
                        $html .= str_replace(['{messages}', '{count}'], [
                            'Atividades',
                            $record->atividades->count()
                        ], $badge);
                    }
                    if ($record->alunos->count()) {
                        $html .= '<div class="flex">';

                        $badge = '<span class="text-nowrap inline-flex items-center m-0.5 px-3 py-1 text-xs font-medium text-center ring-1 ring-green-300 dark:ring-green-700 text-green-500 bg-green-50 rounded-md dark:bg-green-800 dark:text-green-200">{messages} <span class="inline-flex items-center justify-center w-4 h-4 text-xs font-semibold bg-green-200 rounded-full text-sky-900 ms-2">{count}</span></span>';
                        $html .= str_replace(['{messages}', '{count}'], [
                            'Pré-inscritos',
                            $record->alunos->where('situacaoinscricao_id', config('settings.inscricao.preinscricao'))->count()
                        ], $badge);

                        $badge = '<span class="text-nowrap inline-flex items-center m-0.5 px-3 py-1 text-xs font-medium text-center ring-1 ring-orange-300 dark:ring-orange-700 text-orange-500 bg-orange-50 rounded-md dark:bg-orange-800 dark:text-orange-200">{messages} <span class="inline-flex items-center justify-center w-4 h-4 text-xs font-semibold bg-orange-200 rounded-full text-sky-900 ms-2">{count}</span></span>';
                        $html .= str_replace(['{messages}', '{count}'], [
                            'Inscritos',
                            $record->alunos->where('situacaoinscricao_id', config('settings.inscricao.inscrito'))->count()
                        ], $badge);

                        $html .= '</div>';
                    }
                    if ($record->frequencias->count()) {
                        $html .= '<div class="flex">';

                        $badge = '<span class="text-nowrap inline-flex items-center m-0.5 px-3 py-1 text-xs font-medium text-center ring-1 ring-green-300 dark:ring-green-700 text-green-500 bg-green-50 rounded-md dark:bg-green-800 dark:text-green-200">{messages} <span class="inline-flex items-center justify-center w-4 h-4 text-xs font-semibold bg-green-200 rounded-full text-sky-900 ms-2">{count}</span></span>';
                        $html .= str_replace(['{messages}', '{count}'], [
                            'Presentes',
                            $record->frequencias->where('situacaofrequencia_id', config('settings.frequencia.presente'))->count()
                        ], $badge);

                        $badge = '<span class="text-nowrap inline-flex items-center m-0.5 px-3 py-1 text-xs font-medium text-center ring-1 ring-red-300 dark:ring-red-700 text-red-500 bg-red-50 rounded-md dark:bg-red-800 dark:text-red-200">{messages} <span class="inline-flex items-center justify-center w-4 h-4 text-xs font-semibold bg-red-200 rounded-full text-sky-900 ms-2">{count}</span></span>';
                        $html .= str_replace(['{messages}', '{count}'], [
                            'Ausentes',
                            $record->frequencias->where('situacaofrequencia_id', config('settings.frequencia.ausente'))->count()
                        ], $badge);

                        $html .= '</div>';
                    }
                    return new HtmlString($html);
                }),

            Tables\Columns\ImageColumn::make('ead')
                ->label('EaD')
                ->height(40)
                ->toggleable()
                ->circular(),

            Tables\Columns\ToggleColumn::make('liberar_divulgacao')
                ->label('Divulgação')
                ->searchable()
                ->toggleable()
                ->sortable(),

            Tables\Columns\ToggleColumn::make('liberar_inscricao')
                ->label('Inscrição')
                ->searchable()
                ->toggleable()
                ->sortable(),

            Tables\Columns\ToggleColumn::make('liberar_frequencia')
                ->label('Frequência')
                ->searchable()
                ->toggleable()
                ->sortable(),

            Tables\Columns\ToggleColumn::make('ativo')
                ->label('Ativo')
                ->searchable()
                ->toggleable()
                ->sortable(),

            Tables\Columns\ToggleColumn::make('auto')
                ->label('Execução Automatica')
                ->searchable()
                ->toggleable()
                ->sortable(),

            Tables\Columns\TextColumn::make('inicio')
                ->label('Data de Inicio')
                ->searchable()
                ->date('d/m/Y H:i')
                ->color('info')
                ->badge()
                ->toggleable()
                ->sortable(),

            Tables\Columns\TextColumn::make('userInstrutores.name')
                ->label('Instrutores')
                ->wrap()
                ->searchable()
                ->toggleable()
                // ->sortable()
                ->badge(),

            Tables\Columns\TextColumn::make('publico.descricao')
                ->label(new HtmlString('Público<br/>Alvo'))
                ->toggleable()
                ->searchable()
                // ->sortable()
                ->badge(),

            Tables\Columns\TextColumn::make('tags.descricao')
                ->label('Tags')
                ->searchable()
                ->toggleable()
                // ->sortable()
                ->badge()
                ->separator(','),

            Tables\Columns\TextColumn::make('atualizado.name')
                ->label('Atualizado por')
                ->searchable()
                ->toggleable()
                ->sortable(),

            Tables\Columns\TextColumn::make('created_at')
                ->label('Data de Criação')
                ->searchable()
                ->toggleable()
                ->date('d/m/Y H:i')
                ->sortable(),

        ];
    }
    public static function calcularTotal($get, $set)
    {
        $carga_horaria = (float) ($get('carga_horaria')) ?? 0;
        $valor_hora = (float) ($get('valor_hora')) ?? 0;
        $total = $carga_horaria * $valor_hora;
        $set('valor_total', $total);
    }
    public static function getFormfieldsInstrutoria(): array
    {
        return [
            Forms\Components\Select::make('instrutor_id')
                ->label('Instrutor')
                ->hint('site')
                ->required()
                ->hintIcon('heroicon-o-globe-alt')
                ->hintColor('info')
                ->options(function () {
                    return User::get()->mapWithKeys(function ($instrutor) {
                        return [$instrutor->id => "{$instrutor->name} - CPF: {$instrutor->cpf}"];
                    });
                })
                ->afterStateHydrated(function ($state, Set $set) {
                    if ($state) {
                        $perfil = Perfil::where('user_id', $state)->first();
                        if ($perfil) {
                            $set('sexo', $perfil->sexo);
                            $set('genero', $perfil->genero);
                            $set('raca', $perfil->raca);
                        }
                    } else {
                        $set('sexo', null);
                        $set('genero', null);
                        $set('raca', null);
                    }
                })
                ->afterStateUpdated(function ($state, Set $set) {
                    if ($state) {
                        $perfil = Perfil::where('user_id', $state)->first();
                        if ($perfil) {
                            $set('sexo', $perfil->sexo);
                            $set('genero', $perfil->genero);
                            $set('raca', $perfil->raca);
                        }
                    } else {
                        $set('sexo', null);
                        $set('genero', null);
                        $set('raca', null);
                    }
                })
                ->live()
                ->searchable(['name'])
                //
                ->createOptionForm(
                    UserResource::getFormfieldsCadastro()
                )
                ->createOptionUsing(function (array $data) {


                    return User::create($data)->getKey();
                })
                ->columnSpanFull(),

            Forms\Components\Select::make('sexo')
                ->label('Sexo')
                ->options(function () {
                    return TipoSexo::all()->pluck('descricao', 'id');
                }),
            Forms\Components\Select::make('genero')
                ->label('Gênero')
                ->options(function () {
                    return TipoGenero::all()->pluck('descricao', 'id');
                }),
            Forms\Components\Select::make('raca')
                ->label('Raça')
                ->options(function () {
                    return TipoRaca::all()->pluck('descricao', 'id');
                }),



            Forms\Components\Select::make('tipoatividade_id')
                ->label('Atividade')
                ->hint('site')
                ->hintIcon('heroicon-o-globe-alt')
                ->hintColor('info')
                ->options(
                    Tabelas::where('grupo', '=', 'ATIVIDADE_EDUCACIONAL')->pluck('descricao', 'id')
                )
                ->live()

                ->columnSpan(1),

            Forms\Components\Select::make('titulacao_id')
                ->label('Titulação')
                ->hint('site')
                ->hintIcon('heroicon-o-globe-alt')
                ->hintColor('info')
                ->options(
                    Tabelas::where('grupo', '=', 'TIPO_TITULACAO')->pluck('descricao', 'id')
                )
                ->live()

                ->columnSpan(1),


            Forms\Components\TextInput::make('carga_horaria')
                ->label('CH')
                ->required()
                ->live(onBlur: true)
                ->numeric()
                ->inputMode('decimal')
                ->default(0)
                ->afterStateUpdated(function (Set $set, Get $get) {
                    self::calcularTotal($get, $set);
                })
                ->columnSpan(1),

            Forms\Components\TextInput::make('valor_hora')
                ->label('Valor da Hora')
                ->required()
                ->live(onBlur: true)
                ->numeric()
                ->inputMode('decimal')
                ->default(0)
                ->afterStateUpdated(function (Set $set, Get $get) {
                    self::calcularTotal($get, $set);
                })
                ->columnSpan(1),

            Forms\Components\TextInput::make('valor_total')
                ->label('Total')
                ->required()
                ->live()
                ->numeric()
                ->inputMode('decimal')
                ->default(0)
                ->afterStateUpdated(function (Set $set, Get $get) {
                    self::calcularTotal($get, $set);
                })
                ->columnSpan(1),
        ];
    }
    public static function getFormfieldsAtividades(): array
    {
        return [
            Forms\Components\TextInput::make('titulo')
                ->label('Título')
                ->hint('site')
                ->hintIcon('heroicon-o-globe-alt')
                ->hintColor('info')
                ->required()
                ->maxLength(255)
                ->columnSpan(2),

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
                ->hintIcon('heroicon-o-globe-alt')
                ->hintColor('info')
                ->required()
                ->suffix('horas')
                ->numeric()
                ->inputMode('decimal')
                ->default(10)
                ->columnSpan(1),

            Forms\Components\DateTimePicker::make('inicio')
                ->label('Data início')
                ->live()
                ->rule(function (Get $get, Set $set) {
                    return function ($attribute, $value, $fail) use ($get, $set) {
                        $termino = $get('termino');
                        if (isset($termino)) {
                            if ($termino < $value) {
                                $fail('Data início não pode ser maior que a data término');
                                return;
                            } else {
                                if (static::validarData($get, $value, $termino, $attribute)) {
                                    $fail('Data conflitante');
                                    return;
                                }
                            }
                        }
                    };
                })
                ->hidden(function ($record) {
                    if ($record) {
                        return true;
                    }
                    return false;
                })
                ->reactive()
                ->required()
                ->columnSpan(1),

            Forms\Components\DateTimePicker::make('termino')
                ->label('Data término')
                ->required()
                ->live()
                ->hidden(function ($record) {
                    if ($record) {
                        return true;
                    }
                    return false;
                })
                ->columnSpan(1),

            Select::make('tipolocal_id')
                ->label('Local')
                ->options(function (Get $get) {
                    return TipoLocal::get()->mapWithKeys(function ($local) {
                        if (!$local->json || !isset($local->json['capacidade'])) {
                            return [$local->id => "{$local->descricao}"];
                        }
                        return [$local->id => "{$local->descricao} - Capacidade: {$local->json['capacidade']}"];
                    });
                })

                ->default(function (Get $get) {
                    return $get('../../tipolocal_id');
                })
                ->live()
                ->reactive()
                ->required()
                ->columnSpan(1),

            Forms\Components\Checkbox::make('certificavel')
                ->label('Certificar')
                ->live()
                ->default(true)
                ->columnSpan(1),
        ];
    }

    public static function getFormfields(): array
    {
        return [

            Section::make('Dados do Curso')
                ->columnSpanFull()
                ->schema([
                    Grid::make()
                        ->columns(4)
                        ->schema([
                            Select::make('tipoatividade_id')
                                ->relationship(
                                    name: 'tipoatividade',
                                    titleAttribute: 'descricao',
                                )
                                ->live()
                                ->required()
                                ->label('Tipo de Atividade')
                                ->default(config('settings.tipoatividade.curso')) // 2902 = Curso

                                ->columnSpan(1),

                            Select::make('tipoevento_id')
                                ->relationship(
                                    name: 'tipoevento',
                                    titleAttribute: 'descricao',
                                )
                                // ->searchable(['descricao'])
                                ->label('Tipo de Evento')

                                ->required()
                                ->hidden(fn(Get $get): bool => $get('tipoatividade_id') == config('settings.tipoatividade.curso'))
                                ->columnSpan(1),

                            // Group::make()
                            //     ->columns(3)
                            //     ->columnSpan(1)
                            //     ->schema([
                            //         Toggle::make('externo')
                            //             ->label('Curso Externo')
                            //             ->live()
                            //             ->required()
                            //             ->inline(false)
                            //             ->hidden(fn(Get $get): bool => ($get('tipoatividade_id') != config('settings.tipoatividade.curso')))
                            //             ->columnSpan(1),
                            //     ]),

                            // Select::make('instituicao_id')
                            //     ->relationship(
                            //         name: 'instituicao',
                            //         titleAttribute: 'nome',
                            //     )
                            //     ->searchable(['nome'])
                            //     ->label('Instituição')
                            //
                            //     ->live()
                            //     ->hidden(fn(Get $get): bool => ($get('externo') != 1))
                            //     // ->createOptionForm(
                            //     //     InstituicoesResource::getFormFields()
                            //     // )
                            //     ->columnSpan(2),
                        ]),

                    Grid::make()
                        ->columns(4)
                        ->schema([

                            Select::make('demanda_id')
                                ->relationship(
                                    name: 'demanda',
                                    titleAttribute: 'titulo',
                                    modifyQueryUsing: fn(Builder $query) => $query->where('tipodemanda_id', '=', config('settings.tipodemanda.pedagogica')),
                                )
                                ->searchable(['titulo'])
                                ->label('Demanda')
                                //
                                ->live()
                                // ->createOptionForm(
                                //     DemandaResource::getFormFields()
                                // )
                                ->afterStateUpdated(function (?string $state, Set $set, Get $get) {
                                    $demanda = Demanda::find($state);
                                    if ($demanda) {
                                        $set('titulo', $demanda->titulo);
                                        $set('descricao', $demanda->descricao);
                                        // $set('carga_horaria', $demanda->qtde_horas);
                                    }
                                })
                                ->columnSpan(2),

                            Select::make('acao_id')
                                ->relationship(
                                    name: 'acao',
                                    titleAttribute: 'descricao',
                                )
                                ->searchable(['descricao'])
                                ->label('Ação Pedagógica')
                                //
                                // ->createOptionForm(
                                //     AcaoResource::getFormFields()
                                // )
                                ->columnSpan(2),

                        ]),
                ]),

            Tabs::make('Tabs')
                ->columnSpanFull()
                ->tabs([
                    Tabs\Tab::make('Projeto Pedagógico')
                        ->icon('heroicon-m-book-open')
                        ->columns(6)
                        ->schema([

                            Grid::make()
                                ->schema([
                                    TextInput::make('titulo')
                                        ->label('Título')
                                        ->hint('site')
                                        ->hintIcon('heroicon-o-globe-alt')
                                        ->hintColor('info')
                                        ->required()
                                        ->maxLength(255)
                                        ->columnSpan(4),

                                    RichEditor::make('descricao')
                                        ->label('Descrição')
                                        ->hint('site')
                                        ->hintIcon('heroicon-o-globe-alt')
                                        ->hintColor('info')
                                        ->columnSpan(4),

                                    Select::make('tags')
                                        ->label('Tags')
                                        ->multiple()
                                        ->relationship('tags', 'descricao')
                                        ->searchable()

                                        ->createOptionForm(
                                            TagResource::getFormFields()
                                        )
                                        ->columnSpan(4),

                                ])
                                ->columnSpan(4),

                            Grid::make()
                                ->columnSpan(2)
                                ->schema([

                                    TextInput::make('aprovacao')
                                        ->label('Frequência aprovação')
                                        ->suffix('%')
                                        ->required()
                                        ->numeric()
                                        ->default(100)
                                        ->inputMode('decimal')
                                        ->columnSpan(1),

                                    TextInput::make('carga_horaria')
                                        ->label('Carga Horária')
                                        ->hint('site')
                                        ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Caso não definida 0')
                                        ->hintIcon('heroicon-o-globe-alt')
                                        ->hintColor('info')
                                        ->numeric()
                                        ->required()
                                        ->suffix('horas')
                                        ->columnSpan(1),

                                    TextInput::make('numero_vagas')
                                        ->label('Número de Vagas')
                                        ->hintColor('primary')
                                        ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Ao manter o campo vazio será considerada a capacidade do local definido')
                                        ->numeric()
                                        ->columnSpan(1),

                                    Select::make('modalidade_id')
                                        ->options(
                                            Modalidade::all()->pluck('descricao', 'id')
                                        )
                                        ->label('Modalidade')

                                        ->live()
                                        // ->createOptionForm(
                                        //     SetorResource::getFormFields()
                                        // )
                                        ->columnSpan(1),

                                    TextInput::make('link')
                                        ->label('Link Reunião/Webnar')
                                        // ->hidden(fn(Get $get): bool => $get('modalidade_id') != config('settings.modalidade.remoto'))
                                        ->hidden(function (Get $get): bool {
                                            $modalidade = $get('modalidade_id');
                                            if ($modalidade == config('settings.modalidade.remoto') || $modalidade == config('settings.modalidade.hibrido')) {
                                                return false;
                                            }

                                            return true;
                                        })
                                        ->columnSpan(1),

                                    TextInput::make('webnar_password')
                                        ->label('Senha Reunião/Webnar')
                                        // ->hidden(fn(Get $get): bool => $get('modalidade_id') != config('settings.modalidade.remoto'))
                                        ->hidden(function (Get $get): bool {
                                            $modalidade = $get('modalidade_id');
                                            if ($modalidade == config('settings.modalidade.remoto') || $modalidade == config('settings.modalidade.hibrido')) {
                                                return false;
                                            }
                                            return true;
                                        })
                                        ->columnSpan(1),

                                    Select::make('publico')
                                        ->label('Público Alvo')
                                        ->hint('site')
                                        ->hintIcon('heroicon-o-globe-alt')
                                        ->hintColor('info')
                                        ->multiple()
                                        ->relationship(name: 'publico', titleAttribute: 'descricao',)
                                        ->searchable()

                                        ->createOptionForm(
                                            TipoPublicoResource::getFormFields()
                                        )
                                        ->columnSpan(2),
                                ]),

                            Grid::make()
                                ->columnSpanFull()
                                ->schema([

                                    Select::make('areatematica_id')
                                        ->relationship(
                                            name: 'areatematica',
                                            titleAttribute: 'descricao',
                                        )
                                        // ->searchable(['descricao'])
                                        ->label('Área Temática')

                                        // ->createOptionForm(
                                        //     AreaTematicaResource::getFormFields()
                                        // )
                                        ->columnSpan(1),


                                    Select::make('realizacao_id')
                                        ->relationship(
                                            name: 'localrealizacao',
                                            titleAttribute: 'descricao',
                                        )
                                        ->label('Realizado na')

                                        ->columnSpan(1),

                                    Select::make('tipolocal_id')
                                        ->options(function () {
                                            return TipoLocal::get()->mapWithKeys(function ($local) {
                                                if (!$local->json || !isset($local->json['capacidade'])) {
                                                    return [$local->id => "{$local->descricao}"];
                                                }
                                                return [$local->id => "{$local->descricao} - Capacidade: {$local->json['capacidade']}"];
                                            });
                                        })
                                        ->label('Sala/Auditório')

                                        ->required()
                                        ->default(config('settings.sala.adefinir'))
                                        ->live()
                                        // ->createOptionForm(
                                        //     TipoLocalResource::getFormFields()
                                        // )
                                        ->columnSpan(1),

                                    TextInput::make('criterio_selecao')
                                        ->label('Critério de Seleção')
                                        ->columnSpan(1),

                                    Select::make('setor_id')
                                        ->relationship(
                                            name: 'setor',
                                            titleAttribute: 'setor',
                                        )
                                        ->searchable(['setor'])
                                        ->label('Setor')
                                        ->columnSpan(2),

                                ]),
                        ]),

                    Tabs\Tab::make('Plano de Curso')
                        ->icon('heroicon-m-academic-cap')
                        ->live()
                        // ->hidden(fn(Get $get): bool => $get('tipoatividade_id') != config('settings.tipoatividade.curso'))
                        ->schema([
                            RichEditor::make('justificativa')
                                ->label('Justificativa')
                                ->columnSpanFull(),

                            RichEditor::make('objetivo_geral')
                                ->label('Objetivo Geral')
                                ->columnSpanFull(),

                            RichEditor::make('conteudo_programatico')
                                ->label('Conteúdo Programático')
                                ->columnSpanFull(),

                            RichEditor::make('abordagem_pedagogica')
                                ->label('Abordagem Pedagógica')
                                ->columnSpanFull(),

                            RichEditor::make('avaliacao_cursistas')
                                ->label('Avaliação Cursistas')
                                ->columnSpanFull(),

                            RichEditor::make('referencias_bibliograficas')
                                ->label('Referências Bibliográficas')
                                ->columnSpanFull(),

                            RichEditor::make('curriculo_resumido')
                                ->label('Currículo Resumido')
                                ->columnSpanFull(),

                            Select::make('recursos')
                                ->label('Recursos Instrucionais')
                                ->multiple()
                                ->relationship(
                                    name: 'recursos',
                                    titleAttribute: 'descricao',
                                    modifyQueryUsing: fn(Builder $query) => $query->where('grupo', '=', 'TIPO_RECURSOS'),
                                )
                                ->searchable()

                                ->createOptionForm(
                                    TipoRecursoResource::getFormFields()
                                )
                                ->columnSpan(2),

                        ]),

                    Tabs\Tab::make('Instrutoria')
                        ->icon('heroicon-m-users')
                        ->schema([
                            // instrutoria
                            Repeater::make('instrutores')
                                ->defaultItems(0)
                                ->relationship()
                                ->collapsible()
                                ->cloneable()
                                ->columns(7)
                                ->mutateRelationshipDataBeforeSaveUsing(function ($data) {
                                    Perfil::updateOrCreate(
                                        ['user_id' => $data['instrutor_id']],
                                        [
                                            'user_id' => $data['instrutor_id'],
                                            'sexo' => $data['sexo'] ?? null,
                                            'genero' => $data['genero'] ?? null,
                                            'raca' => $data['raca'] ?? null,
                                        ]
                                    );
                                })
                                ->itemLabel(fn(array $state): ?string => User::find($state['instrutor_id'])?->name ?? 'Novo Instrutor')
                                ->schema(
                                    static::getFormfieldsInstrutoria()
                                ),
                        ]),
                    Tabs\Tab::make('Atividades')
                        ->icon('heroicon-m-calendar-days')
                        ->schema([
                            Repeater::make('atividades')
                                ->defaultItems(0)
                                ->relationship()
                                ->live()
                                ->columns(6)
                                ->afterStateUpdated(function ($state, Get $get) {
                                    if (count($state) >= 1) {
                                        // dd($state);
                                    }
                                })
                                ->schema(
                                    static::getFormfieldsAtividades()
                                ),
                        ]),

                    // Tabs\Tab::make('Agendamento')
                    //     ->icon('heroicon-m-calendar')
                    //     ->schema([

                    //         Grid::make()
                    //             ->columns(8)
                    //             ->schema([

                    //                 Grid::make()
                    //                     ->columns(6)
                    //                     ->columnSpan(6)
                    //                     ->schema([
                    //                         Section::make('Programação')
                    //                             ->columnSpan(3)
                    //                             ->hidden(function (Get $get, $record): bool {
                    //                                 if (count($get('atividades')) > 0) {
                    //                                     return true;
                    //                                 }
                    //                                 if ($record != null) {
                    //                                     return true;
                    //                                 }
                    //                                 return false;
                    //                             })
                    //                             ->schema([

                    //                                 Select::make('agendaable')
                    //                                     ->label('Atividade')
                    //                                     ->live()
                    //                                     ->default(fn($record) => $record->id ?? null)
                    //                                     ->options(function ($record) {
                    //                                         if ($record) {

                    //                                             $atividades = $record->temAtividades()
                    //                                                 ? $record->atividades()->get()
                    //                                                 : collect();

                    //                                             return $atividades
                    //                                                 ->pluck('titulo', 'id')
                    //                                                 ->put($record->id, $record->titulo)
                    //                                                 ->toArray();
                    //                                         }
                    //                                         return [];
                    //                                     })
                    //                                     ->columnSpan(3),

                    //                                 DateRangePickerV2::make('periodo_selecionado')
                    //                                     ->label('Selecione o Período')
                    //                                     ->agendaableField('agendaable')
                    //                                     ->agendaables(function ($record) {
                    //                                         if ($record) {

                    //                                             $atividades = $record->temAtividades()
                    //                                                 ? $record->atividades()->get()
                    //                                                 : collect();

                    //                                             return $atividades
                    //                                                 ->push($record)
                    //                                                 ->map(fn($agendaable) => [
                    //                                                     'id' => $agendaable->id,
                    //                                                     'type' => get_class($agendaable),
                    //                                                     'titulo' => $agendaable->titulo,
                    //                                                     'local_id' => $agendaable->tipolocal_id,
                    //                                                 ])
                    //                                                 ->values()
                    //                                                 ->all();
                    //                                         }
                    //                                         return [];
                    //                                     })
                    //                                     ->agendas(function ($record) {
                    //                                         if ($record) {

                    //                                             return AgendaService::buscarAgendas($record)
                    //                                                 ->map(fn($agenda) => [
                    //                                                     'id' => $agenda->id,
                    //                                                     'agendaable_id' => $agenda->agendaable_id,
                    //                                                     'agendaable_type' => $agenda->agendaable_type,
                    //                                                     'data' => Carbon::parse($agenda->inicio)->toDateString(),
                    //                                                     'inicio' => Carbon::parse($agenda->inicio)->format('Y-m-d H:i:s'),
                    //                                                     'termino' => Carbon::parse($agenda->termino)->format('Y-m-d H:i:s'),
                    //                                                     'titulo' => $agenda->titulo ?? 'Curso',
                    //                                                     'descricao' => $agenda->descricao,
                    //                                                     'local_id' => $agenda->local_id,
                    //                                                     'atualizado_por' => $agenda->atualizado_por,
                    //                                                     'bloqueado' => $agenda->frequencias()
                    //                                                         ->where('situacaofrequencia_id', config('settings.frequencia.presente'))
                    //                                                         ->exists(),
                    //                                                 ])
                    //                                                 ->values()
                    //                                                 ->all();
                    //                                         }
                    //                                         return [];
                    //                                     })
                    //                                     ->columnSpanFull(),




                    //                                 Select::make('turno')
                    //                                     ->options([
                    //                                         'Matutino' => 'Matutino',
                    //                                         'Vespertino' => 'Vespertino',
                    //                                         'Noturno' => 'Noturno',
                    //                                         'Integral' => 'Integral',
                    //                                     ])
                    //                                     ->label('Turno')
                    //                                     ->inlineLabel()
                    //                                     ->columnSpanFull(),

                    //                             ]),


                    //                         Section::make('Inscrição')
                    //                             ->columns(2)
                    //                             ->columnSpan(2)
                    //                             ->schema([
                    //                                 DateTimePicker::make('inicio_inscricao')
                    //                                     ->label('Início')
                    //                                     ->inlineLabel()
                    //                                     ->live()
                    //                                     ->columnSpan(2),

                    //                                 DateTimePicker::make('termino_inscricao')
                    //                                     ->label('Término')
                    //                                     ->inlineLabel()
                    //                                     ->live()
                    //                                     ->columnSpan(2),
                    //                             ]),

                    //                         Section::make('Frequência')
                    //                             ->columns(2)
                    //                             ->columnSpan(2)
                    //                             ->schema([
                    //                                 TextInput::make('inicio_tolerancia')
                    //                                     ->label('Tolerância início')
                    //                                     ->inlineLabel()
                    //                                     ->suffix('min')
                    //                                     ->numeric()
                    //                                     ->default(10)
                    //                                     ->columnSpan(2),

                    //                                 TextInput::make('termino_tolerancia')
                    //                                     ->label('Tolerância término')
                    //                                     ->inlineLabel()
                    //                                     ->suffix('min')
                    //                                     ->numeric()
                    //                                     ->default(10)
                    //                                     ->columnSpan(2),
                    //                             ]),

                    //                     ]),

                    //             ]),

                    //     ]),

                    Tabs\Tab::make('Divulgação')
                        ->icon('heroicon-m-cloud-arrow-down')
                        ->schema([

                            Repeater::make('arquivos')
                                ->defaultItems(0)
                                ->relationship()
                                ->schema([

                                    TextInput::make('descricao')
                                        ->label('Descrição')
                                        ->maxLength(255)
                                        ->required()
                                        ->columnSpan(2),

                                    FileUpload::make('arquivo')
                                        ->label('Arquivos')
                                        ->disk('public')
                                        ->columnSpan(2),
                                ])
                                ->columns(4),
                        ]),

                    Tabs\Tab::make('Equipe de Trabalho')
                        ->icon('heroicon-m-newspaper')
                        ->schema([
                            Repeater::make('registros')
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
    public static function getFormfieldsEad(): array
    {
        return [

            Tabs::make('Tabs')
                ->columnSpanFull()
                ->tabs([
                    Tabs\Tab::make('Projeto Pedagógico')
                        ->icon('heroicon-m-book-open')
                        ->columns(6)
                        ->schema([

                            Hidden::make('tipoatividade_id')
                                ->default(config('settings.tipoatividade.curso')),
                            Hidden::make('modalidade_id')
                                ->default(config('settings.modalidade.ead')),
                            Hidden::make('externo')
                                ->default(false),
                            Hidden::make('ativo')
                                ->default(true),
                            Hidden::make('liberar_divulgacao')
                                ->default(false),
                            Hidden::make('liberar_inscricao')
                                ->default(false),
                            Hidden::make('liberar_frequencia')
                                ->default(false),
                            Hidden::make('aprovacao')
                                ->default(0),

                            Grid::make()
                                ->schema([
                                    TextInput::make('titulo')
                                        ->label('Título')
                                        ->hint('site')
                                        ->hintIcon('heroicon-o-globe-alt')
                                        ->hintColor('info')
                                        ->required()
                                        ->maxLength(255)
                                        ->columnSpan(4),

                                    RichEditor::make('descricao')
                                        ->label('Descrição')
                                        ->hint('site')
                                        ->hintIcon('heroicon-o-globe-alt')
                                        ->hintColor('info')
                                        ->columnSpan(4),

                                    TextInput::make('carga_horaria')
                                        ->label('Carga Horaria')
                                        ->numeric()
                                        ->inputMode('decimal')
                                        ->default(0)
                                        ->required()
                                        ->suffix('horas')
                                        ->columnSpan(2),

                                    TextInput::make('numero_vagas')
                                        ->label('Numero de Vagas')
                                        ->numeric()
                                        ->default(0)
                                        ->required()
                                        ->columnSpan(2),

                                    DateTimePicker::make('inicio')
                                        ->label('Data de Inicio')
                                        ->required()
                                        ->seconds(false)
                                        ->native(false)
                                        ->columnSpan(2),

                                    DateTimePicker::make('termino')
                                        ->label('Data de Termino')
                                        ->seconds(false)
                                        ->native(false)
                                        ->after('inicio')
                                        ->helperText('Opcional para cursos EaD sem prazo final definido.')
                                        ->columnSpan(2),

                                    DateTimePicker::make('inicio_inscricao')
                                        ->label('Inicio das Inscricoes')
                                        ->seconds(false)
                                        ->native(false)
                                        ->columnSpan(2),

                                    DateTimePicker::make('termino_inscricao')
                                        ->label('Termino das Inscricoes')
                                        ->seconds(false)
                                        ->native(false)
                                        ->after('inicio_inscricao')
                                        ->columnSpan(2),

                                    Select::make('tipolocal_id')
                                        ->label('Local (EaD)')
                                        ->options(function () {
                                            return TipoLocal::get()->mapWithKeys(function ($local) {
                                                if (!$local->json || !isset($local->json['capacidade'])) {
                                                    return [$local->id => "{$local->descricao}"];
                                                }

                                                return [$local->id => "{$local->descricao} - Capacidade: {$local->json['capacidade']}"];
                                            });
                                        })
                                        ->default(3412)
                                        ->searchable()

                                        ->required()
                                        ->helperText('Use o local do EaD. Na falta dele, mantenha "A definir".')
                                        ->columnSpan(2),

                                    Checkbox::make('certificavel')
                                        ->label('Certificavel')
                                        ->default(true)
                                        ->columnSpan(2),

                                ])
                                ->columnSpan(4),

                            Grid::make()
                                ->columnSpan(2)
                                ->schema([

                                    TextInput::make('link')
                                        ->label('Link Moodle')
                                        ->hint('site')
                                        ->hintIcon('heroicon-o-globe-alt')
                                        ->hintColor('info')
                                        ->columnSpan(2),

                                    TextInput::make('moodle_course_id')
                                        ->label('Moodle Course ID')
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->helperText('Preenchido automaticamente apos sincronizacao com o Moodle.')
                                        ->columnSpan(1),

                                    Select::make('publico')
                                        ->label('Público Alvo')
                                        ->hint('site')
                                        ->hintIcon('heroicon-o-globe-alt')
                                        ->hintColor('info')
                                        ->multiple()
                                        ->relationship(name: 'publico', titleAttribute: 'descricao',)
                                        ->searchable()

                                        ->createOptionForm(
                                            TipoPublicoResource::getFormFields()
                                        )
                                        ->columnSpan(2),
                                ]),

                        ]),

                    Tabs\Tab::make('Plano de Curso')
                        ->icon('heroicon-m-academic-cap')
                        ->live()
                        ->schema([
                            RichEditor::make('objetivo_geral')
                                ->label('Objetivo Geral')
                                ->columnSpanFull(),

                            RichEditor::make('conteudo_programatico')
                                ->label('Conteúdo Programático')
                                ->columnSpanFull(),

                        ]),

                    Tabs\Tab::make('Divulgação')
                        ->icon('heroicon-m-cloud-arrow-down')
                        ->schema([

                            Repeater::make('arquivos')
                                ->defaultItems(0)
                                ->relationship()
                                ->schema([

                                    TextInput::make('descricao')
                                        ->label('Descrição')
                                        ->maxLength(255)
                                        ->required()
                                        ->columnSpan(2),

                                    FileUpload::make('arquivo')
                                        ->label('Arquivos')
                                        ->disk('public')
                                        ->columnSpan(2),
                                ])
                                ->columns(4),
                        ]),

                ]),
        ];
    }

    public static function getFormfieldsExterno(): array
    {
        return [

            Tabs::make('Tabs')
                ->columnSpanFull()
                ->tabs([
                    Tabs\Tab::make('Projeto Pedagógico')
                        ->icon('heroicon-m-book-open')
                        ->columns(4)
                        ->schema([

                            Hidden::make('tipoatividade_id')
                                ->default(config('settings.tipoatividade.curso')),
                            Hidden::make('modalidade_id')
                                ->default(config('settings.modalidade.remoto')),
                            Hidden::make('externo')
                                ->default(true),
                            Hidden::make('ativo')
                                ->default(true),
                            Hidden::make('liberar_divulgacao')
                                ->default(false),
                            Hidden::make('liberar_inscricao')
                                ->default(false),
                            Hidden::make('liberar_frequencia')
                                ->default(false),
                            Hidden::make('aprovacao')
                                ->default(0),

                            Grid::make()
                                ->schema([
                                    TextInput::make('titulo')
                                        ->label('Título')
                                        ->hint('site')
                                        ->hintIcon('heroicon-o-globe-alt')
                                        ->hintColor('info')
                                        ->required()
                                        ->maxLength(255)
                                        ->columnSpan(4),

                                    RichEditor::make('descricao')
                                        ->label('Descrição')
                                        ->hint('site')
                                        ->hintIcon('heroicon-o-globe-alt')
                                        ->hintColor('info')
                                        ->required()
                                        ->columnSpan(4),

                                    Select::make('instituicao_id')
                                        ->relationship(
                                            name: 'instituicao',
                                            titleAttribute: 'nome',
                                        )
                                        ->searchable(['nome'])
                                        ->label('Instituição')

                                        ->required()
                                        ->columnSpan(3),

                                    TextInput::make('carga_horaria')
                                        ->label('Carga Horaria')
                                        ->numeric()
                                        ->inputMode('decimal')
                                        ->default(0)
                                        ->required()
                                        ->suffix('horas')
                                        ->columnSpan(1),

                                ])
                                ->columnSpan(4),

                        ]),

                    Tabs\Tab::make('Divulgação')
                        ->icon('heroicon-m-cloud-arrow-down')
                        ->schema([

                            Repeater::make('arquivos')
                                ->defaultItems(0)
                                ->relationship()
                                ->schema([

                                    TextInput::make('descricao')
                                        ->label('Descrição')
                                        ->maxLength(255)
                                        ->required()
                                        ->columnSpan(2),

                                    FileUpload::make('arquivo')
                                        ->label('Arquivos')
                                        ->disk('public')
                                        ->columnSpan(2),
                                ])
                                ->columns(4),
                        ]),

                ]),
        ];
    }

    public static function getFormfieldsCursoComentario(Cursos $curso): array
    {
        return [
            Fieldset::make('Curso')
                ->columnSpanFull()
                ->columns(6)
                ->schema([
                    Placeholder::make('curso.titulo')
                        ->label('Título')
                        ->inlineLabel()
                        ->content(new HtmlString($curso->titulo))
                        ->columnSpan(3),
                    Placeholder::make('curso.carga_horaria')
                        ->label('Carga Horária')
                        ->inlineLabel()
                        ->content(new HtmlString($curso->carga_horaria))
                        ->columnSpan(3),
                ]),
            Fieldset::make('Descrição')
                ->columnSpanFull()
                ->columns(3)
                ->schema([
                    Placeholder::make('curso.descricao')
                        ->hiddenLabel()
                        ->content(new HtmlString('<span class="h-24 overflow-auto font-semibold text-red-400">' . $curso->descricao . '</span>'))
                        ->columnSpanFull(),
                    RichEditor::make('descricao')
                        ->hiddenLabel()
                        ->columnSpanFull(),
                ]),

            Fieldset::make('justificativa')
                ->columnSpanFull()
                ->columns(3)
                ->schema([
                    Placeholder::make('curso.justificativa')
                        ->hiddenLabel()
                        ->content(new HtmlString('<span class="h-24 overflow-auto font-semibold text-red-400">' . $curso->justificativa . '</span>'))
                        ->columnSpanFull(),
                    RichEditor::make('justificativa')
                        ->hiddenLabel()
                        ->columnSpanFull(),
                ]),

            Fieldset::make('Objetivo Geral')
                ->columnSpanFull()
                ->columns(3)
                ->schema([
                    Placeholder::make('curso.objetivo_geral')
                        ->hiddenLabel()
                        ->content(new HtmlString('<span class="h-24 overflow-auto font-semibold text-red-400">' . $curso->objetivo_geral . '</span>'))
                        ->columnSpanFull(),
                    RichEditor::make('objetivo_geral')
                        ->hiddenLabel()
                        ->columnSpanFull(),
                ]),

            Fieldset::make('Conteúdo Programático')
                ->columnSpanFull()
                ->columns(3)
                ->schema([
                    Placeholder::make('curso.conteudo_programatico')
                        ->hiddenLabel()
                        ->content(new HtmlString('<span class="h-24 overflow-auto font-semibold text-red-400">' . $curso->conteudo_programatico . '</span>'))
                        ->columnSpanFull(),
                    RichEditor::make('conteudo_programatico')
                        ->hiddenLabel()
                        ->columnSpanFull(),
                ]),

            Fieldset::make('Abordagem Pedagógica')
                ->columnSpanFull()
                ->columns(3)
                ->schema([
                    Placeholder::make('curso.abordagem_pedagogica')
                        ->hiddenLabel()
                        ->content(new HtmlString('<span class="h-24 overflow-auto font-semibold text-red-400">' . $curso->abordagem_pedagogica . '</span>'))
                        ->columnSpanFull(),
                    RichEditor::make('abordagem_pedagogica')
                        ->hiddenLabel()
                        ->columnSpanFull(),
                ]),

            Fieldset::make('Avaliação Cursistas')
                ->columnSpanFull()
                ->columns(3)
                ->schema([
                    Placeholder::make('curso.avaliacao_cursistas')
                        ->hiddenLabel()
                        ->content(new HtmlString('<span class="h-24 overflow-auto font-semibold text-red-400">' . $curso->avaliacao_cursistas . '</span>'))
                        ->columnSpanFull(),
                    RichEditor::make('avaliacao_cursistas')
                        ->hiddenLabel()
                        ->columnSpanFull(),
                ]),

            Fieldset::make('Referências Bibliográficas')
                ->columnSpanFull()
                ->columns(3)
                ->schema([
                    Placeholder::make('curso.referencias_bibliograficas')
                        ->hiddenLabel()
                        ->content(new HtmlString('<span class="h-24 overflow-auto font-semibold text-red-400">' . $curso->referencias_bibliograficas . '</span>'))
                        ->columnSpanFull(),
                    RichEditor::make('referencias_bibliograficas')
                        ->hiddenLabel()
                        ->columnSpanFull(),
                ]),
        ];
    }
    public static function form(Schema $form): Schema
    {
        $record = $form->getRecord();

        if ($record && $record->modalidade_id == config('settings.modalidade.ead')) {
            return $form
                ->schema(
                    static::getFormfieldsEad()
                )
                ->columns(7);
        }

        return $form
            ->schema(
                static::getFormfields(),
            )
            ->columns(7);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(
                static::getTablefields()
            )
            ->heading(new HtmlString('
                <style>
                /* 1. Configuração do Container da Tabela */
                .fi-ta-content {
                    overflow-y: auto !important;
                    max-height: calc(100vh - 19rem) !important;

                    /* Firefox: Scrollbar fina */
                    scrollbar-width: thin;
                    scrollbar-color: #cbd5e1 transparent; /* Thumb e Track */
                }

                /* 2. Cabeçalho Fixo (Sticky Header) */
                .fi-ta-table thead {
                    position: sticky !important;
                    top: 0 !important;
                    z-index: 10 !important;
                }
                .fi-ta-table thead th {
                    background-color: rgb(255, 255, 255) !important;
                }
                :is(.dark) .fi-ta-table thead th {
                    background-color: rgb(24, 24, 27) !important;
                }

                /* 3. Estilização da Scrollbar (Chrome, Edge, Safari) */
                .fi-ta-content::-webkit-scrollbar {
                    width: 6px;  /* Largura vertical */
                    height: 6px; /* Altura horizontal */
                }

                .fi-ta-content::-webkit-scrollbar-track {
                    background: transparent;
                }

                .fi-ta-content::-webkit-scrollbar-thumb {
                    background-color: #cbd5e1; /* Cor da barra (slate-300) */
                    border-radius: 3px;       /* Arredondamento */
                }

                .fi-ta-content::-webkit-scrollbar-thumb:hover {
                    background-color: #94a3b8; /* Cor ao passar o mouse (slate-400) */
                }

                /* Ajuste para Dark Mode na Scrollbar */
                :is(.dark) .fi-ta-content {
                    scrollbar-color: #4b5563 transparent;
                }
                :is(.dark) .fi-ta-content::-webkit-scrollbar-thumb {
                    background-color: #4b5563; /* gray-600 */
                }
                :is(.dark) .fi-ta-content::-webkit-scrollbar-thumb:hover {
                    background-color: #6b7280; /* gray-500 */
                }
            </style>
            '))
            ->defaultSort('ativo', 'desc')
            ->filters([
                Filter::make('ativo')
                    ->label('Ativo')
                    ->toggle()
                    ->query(fn(Builder $query): Builder => $query->where('ativo', 1))
                    ->columnSpan(2)
                    ->default(),

                Filter::make('externo')
                    ->label('Curso Externo')
                    ->toggle()
                    ->query(fn(Builder $query): Builder => $query->where('externo', 1))
                    ->columnSpan(2),

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

                // SelectFilter::make('instrutores')
                //     ->relationship(name: 'instrutores', titleAttribute: 'name')
                //     ->searchable(['name'])
                //     ->multiple()
                //
                //     ->attribute('instrutor_id')
                //     ->columnSpan(8),

                SelectFilter::make('publico')
                    ->relationship(name: 'publico', titleAttribute: 'descricao')
                    ->searchable(['descricao'])
                    ->multiple()

                    ->attribute('tipopublico_id')
                    ->columnSpan(8),

            ], layout: FiltersLayout::Modal)
            ->filtersFormColumns(8)
            ->filtersFormWidth('2xl')
            ->filtersTriggerAction(
                fn(Action $action) => $action
                    ->button()
                    ->label('Filtrar'),
            )
            ->deferFilters()
            ->selectable()
            ->actions([
                ActionGroup::make([
                    Action::make('realocar')
                        ->hiddenLabel()
                        ->label('Reagendar Ação')
                        ->tooltip('Reagendar Ação')
                        ->mountUsing(function (Schema $form, Model $record): void {
                            $form->fill([
                                'agendas' => AgendaService::buscarAgendas($record)
                                    ->map(
                                        function ($agenda) {
                                            return [
                                                'id' => $agenda->id,
                                                'agendaable_id' => $agenda->agendaable_id,
                                                'data' => Carbon::parse($agenda->inicio)->toDateString(),
                                                'hora_inicio' => Carbon::parse($agenda->inicio)->format('H:i'),
                                                'hora_termino' => Carbon::parse($agenda->termino)->format('H:i'),
                                            ];
                                        }
                                    )

                                    ->values()
                                    ->all(),
                                'agendaable' => $record->id,
                                'periodo_selecionado' => [
                                    'agendaable_id' => $record->id,
                                    'modo_persistencia' => 'operacoes',
                                    'dias' => [],
                                ],
                            ]);
                        })
                        ->icon('heroicon-o-clock')
                        ->form([

                            Tabs::make('Agendamentos')
                                ->tabs([
                                    Tab::make('Agendamento')
                                        ->schema([
                                            Repeater::make('agendas')
                                                ->label('Agendamentos')
                                                ->default(function ($record) {

                                                    if ($record->temAtividades()) {
                                                        $atividades_id = $record->atividades()->pluck('id');
                                                        $atividades_id[] = $record->id;
                                                        $types = [
                                                            Cursos::class,
                                                            CursoAtividades::class,
                                                        ];
                                                        $agendas = Agenda::whereIn('agendaable_id', $atividades_id)->whereIn('agendaable_type', $types)->orderBy('inicio')->get();
                                                    } else {
                                                        $agendas = Agenda::where('agendaable_id', $record->id)
                                                            ->where('agendaable_type', Cursos::class)->orderBy('inicio')->get();
                                                        // dd($agendas);
                                                    }
                                                    // dump($agendas);
                                                    return $agendas->map(function ($agenda) {
                                                        return [
                                                            'id'            => $agenda->id,
                                                            'agendaable_id' => $agenda->agendaable_id,
                                                            'data'          => \Carbon\Carbon::parse($agenda->inicio)->toDateString(),
                                                            'hora_inicio'   => \Carbon\Carbon::parse($agenda->inicio)->format('H:i'),
                                                            'hora_termino'  => \Carbon\Carbon::parse($agenda->termino)->format('H:i'),
                                                        ];
                                                    })->values()->all();
                                                })
                                                ->columns(6)
                                                ->columnSpanFull()
                                                ->schema([

                                                    Hidden::make('id'),

                                                    Select::make('agendaable_id')
                                                        ->label('Atividade')
                                                        ->selectablePlaceholder(false)
                                                        ->options(function ($record) {
                                                            $atividades = $record->temAtividades()
                                                                ? $record->atividades()->get()
                                                                : collect();

                                                            return $atividades
                                                                ->pluck('titulo', 'id')
                                                                ->put($record->id, $record->titulo)
                                                                ->toArray();
                                                        })
                                                        ->columnSpan(3),


                                                    DatePicker::make('data')
                                                        ->label('Data')
                                                        ->required()
                                                        ->native(false)
                                                        ->columnSpan(1),

                                                    TimePicker::make('hora_inicio')
                                                        ->label('Hora de Início')
                                                        ->required()
                                                        ->columnSpan(1),

                                                    TimePicker::make('hora_termino')
                                                        ->label('Hora de Término')
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
                                                        ->label('Atividade')
                                                        ->live()
                                                        ->default(fn($record) => $record->id)
                                                        ->options(function ($record) {
                                                            $atividades = $record->temAtividades()
                                                                ? $record->atividades()->get()
                                                                : collect();

                                                            return $atividades
                                                                ->pluck('titulo', 'id')
                                                                ->put($record->id, $record->titulo)
                                                                ->toArray();
                                                        })
                                                        ->columnSpan(3),

                                                    DateRangePickerV2::make('periodo_selecionado')
                                                        ->label('Selecione o Período')
                                                        ->agendaableField('agendaable')
                                                        ->agendaables(function ($record) {
                                                            $atividades = $record->temAtividades()
                                                                ? $record->atividades()->get()
                                                                : collect();

                                                            return $atividades
                                                                ->push($record)
                                                                ->map(fn($agendaable) => [
                                                                    'id' => $agendaable->id,
                                                                    'type' => get_class($agendaable),
                                                                    'titulo' => $agendaable->titulo,
                                                                    'local_id' => $agendaable->tipolocal_id,
                                                                ])
                                                                ->values()
                                                                ->all();
                                                        })
                                                        ->agendas(function ($record) {
                                                            return AgendaService::buscarAgendas($record)
                                                                ->map(fn($agenda) => [
                                                                    'id' => $agenda->id,
                                                                    'agendaable_id' => $agenda->agendaable_id,
                                                                    'agendaable_type' => $agenda->agendaable_type,
                                                                    'data' => Carbon::parse($agenda->inicio)->toDateString(),
                                                                    'inicio' => Carbon::parse($agenda->inicio)->format('Y-m-d H:i:s'),
                                                                    'termino' => Carbon::parse($agenda->termino)->format('Y-m-d H:i:s'),
                                                                    'titulo' => $agenda->titulo ?? 'Curso',
                                                                    'descricao' => $agenda->descricao,
                                                                    'local_id' => $agenda->local_id,
                                                                    'atualizado_por' => $agenda->atualizado_por,
                                                                    'bloqueado' => $agenda->frequencias()
                                                                        ->where('situacaofrequencia_id', config('settings.frequencia.presente'))
                                                                        ->exists(),
                                                                ])
                                                                ->values()
                                                                ->all();
                                                        })
                                                        ->columnSpanFull(),



                                                ])
                                        ])
                                ])
                        ])
                        ->action(function ($record, $data) {
                            AgendaService::realocarAgenda($record, $data);
                        }),

                    Action::make('autoConfig')
                        ->hiddenLabel()
                        ->color('info')
                        ->label('Configurações de Automação')
                        ->tooltip('Configurações de Automação')
                        ->size('xl')
                        ->icon('heroicon-o-cog-6-tooth')
                        ->modalWidth('7xl')
                        ->modalHeading(fn($record) => 'Configurações para - ' . $record->titulo)
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Fechar')
                        ->modalContent(
                            fn(Model $record) =>
                            view('filament.resources.partials.auto-config-partial', [
                                'curso' => $record
                            ])
                        ),

                    ViewCursoStatAction::make(),

                    Action::make('gerenciarInscricoes')
                        ->hiddenLabel()
                        ->color('info')
                        ->label('Gerenciar Inscrições')
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

                    Action::make('gerenciarFrequencia')
                        ->hiddenLabel()
                        ->label('Gerenciar Frequência')
                        ->tooltip('Gerenciar Frequência')
                        ->size('xl')
                        ->icon('heroicon-o-user-group')
                        ->modalWidth('7xl')
                        ->modalContent(function (Model $record) {
                            $temAtividades = $record->temAtividades();
                            $dadosView = [];
                            $emailsExternos = []; // 1. Inicializa o array de e-mails

                            if ($temAtividades) {
                                $atividades = $record->atividades()->get();
                                foreach ($atividades as $atividade) {
                                    $alunos = [];
                                    $alunosOrdenados = $atividade->alunos->sortBy(function ($item) {
                                        return $item->aluno->name;
                                    }, SORT_NATURAL | SORT_FLAG_CASE);

                                    foreach ($alunosOrdenados as $aluno) {
                                        // 2. Captura e filtra os e-mails
                                        $email = $aluno->aluno->email;
                                        if (!empty($email) && !str_ends_with(strtolower($email), '@tjes.jus.br')) {
                                            $emailsExternos[] = $email;
                                        }

                                        $frequenciasAluno = [];
                                        $frequenciasOrdenadas = $aluno->frequencias->sortBy(function ($freq) {
                                            return $freq->data->inicio;
                                        });
                                        foreach ($frequenciasOrdenadas as $frequencia) {
                                            $frequenciasAluno[] = [
                                                'id' => $frequencia->id,
                                                'data' => $frequencia->data->inicio->format('d/m/Y H:i'),
                                                'dataFrequencia' => $frequencia->updated_at->format('d/m/Y H:i'),
                                                'situacaofrequencia_id' => $frequencia->situacaofrequencia_id,
                                                'situacao' => $frequencia->situacaofrequencia->descricao,
                                            ];
                                        }
                                        $alunos[] = [
                                            'nome' => $aluno->aluno->name,
                                            'cpf' => $aluno->aluno->cpf,
                                            'email' => $aluno->aluno->email,
                                            'situacaoinscricao' => $aluno->situacaoinscricao->descricao,
                                            'frequencias' => $frequenciasAluno,
                                        ];
                                    }
                                    $dadosView[] = [
                                        'titulo' => $atividade->titulo,
                                        'alunos' => $alunos,
                                    ];
                                }
                            } else {
                                $alunos = [];
                                $alunosOrdenados = $record->alunos->sortBy(function ($item) {
                                    return $item->aluno->name;
                                }, SORT_NATURAL | SORT_FLAG_CASE);

                                foreach ($alunosOrdenados as $aluno) {
                                    // 2. Captura e filtra os e-mails (para cursos sem atividades)
                                    $email = $aluno->aluno->email;
                                    if (!empty($email) && !str_ends_with(strtolower($email), '@tjes.jus.br')) {
                                        $emailsExternos[] = $email;
                                    }

                                    $frequenciasAluno = [];
                                    $frequenciasOrdenadas = $aluno->frequencias->sortBy(function ($freq) {
                                        return $freq->data->inicio;
                                    });
                                    foreach ($frequenciasOrdenadas as $frequencia) {
                                        $frequenciasAluno[] = [
                                            'id' => $frequencia->id,
                                            'data' => $frequencia->data->inicio->format('d/m/Y H:i'),
                                            'dataFrequencia' => $frequencia->updated_at->format('d/m/Y H:i'),
                                            'situacaofrequencia_id' => $frequencia->situacaofrequencia_id,
                                            'situacao' => $frequencia->situacaofrequencia->descricao,
                                        ];
                                    }
                                    $alunos[] = [
                                        'nome' => $aluno->aluno->name,
                                        'cpf' => $aluno->aluno->cpf,
                                        'email' => $aluno->aluno->email,
                                        'situacaoinscricao' => $aluno->situacaoinscricao->descricao,
                                        'frequencias' => $frequenciasAluno,
                                    ];
                                }
                                $dadosView[] = [
                                    'alunos' => $alunos,
                                ];
                            }

                            // 3. Remove os duplicados e junta tudo separado por vírgula e espaço
                            $stringEmailsExternos = implode(', ', array_unique($emailsExternos));

                            return view('filament.resources.partials.gerenciar-frequencias', [
                                'temAtividades'  => $temAtividades,
                                'dados'          => $dadosView,
                                'titulo'         => $record->titulo,
                                'emailsExternos' => $stringEmailsExternos, // 4. Passa os e-mails para a view
                            ]);
                        })
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Fechar'),

                    Action::make('registrar_frequencia')
                        ->label('Registrar Frequência')
                        ->icon('heroicon-o-check-circle')
                        ->form([
                            Select::make('users')
                                ->label('Aluno')
                                ->multiple()
                                ->searchable()
                                ->getSearchResultsUsing(fn (string $search): array => User::query()
                                    ->where('name', 'like', "%{$search}%")
                                    ->orWhere('cpf', 'like', "%{$search}%")
                                    ->limit(50)
                                    ->get()
                                    ->mapWithKeys(fn (User $user): array => [
                                        $user->id => "{$user->name} - {$user->cpf}",
                                    ])
                                    ->toArray())
                                ->getOptionLabelsUsing(fn (array $values): array => User::whereIn('id', $values)
                                    ->get()
                                    ->mapWithKeys(fn (User $user): array => [
                                        $user->id => "{$user->name} - {$user->cpf}",
                                    ])
                                    ->toArray())
                                ->required()
                                ->columnSpanFull(),
                            Select::make('datas')
                                ->label('Datas de Frequência')
                                ->options(fn (Model $record): array => AgendaService::buscarAgendas($record)
                                    ->mapWithKeys(fn (Agenda $agenda): array => [
                                        $agenda->id => Carbon::parse($agenda->inicio)->format('d/m/Y H:i')
                                            . ' - ' . Carbon::parse($agenda->termino)->format('H:i'),
                                    ])
                                    ->toArray())
                                ->multiple()
                                ->required()
                                ->columnSpanFull(),
                            RichEditor::make('observacao')
                                ->label('Observação')
                                ->maxLength(500)
                                ->columnSpanFull(),
                        ])
                        ->action(function (Model $record, array $data): void {
                            DB::transaction(function () use ($record, $data): void {
                                $alunos = User::whereIn('id', $data['users'])->get();
                                $agendaIds = AgendaService::buscarAgendas($record)->pluck('id');
                                $agendas = Agenda::whereIn('id', $data['datas'])
                                    ->whereIn('id', $agendaIds)
                                    ->with('agendaable')
                                    ->get();

                                foreach ($agendas as $agenda) {
                                    if (!$agenda->agendaable) {
                                        continue;
                                    }

                                    foreach ($alunos as $aluno) {
                                        CertificadoService::validarVinculo(
                                            $agenda->agendaable->id,
                                            $aluno->id,
                                            false,
                                            $agenda->agendaable,
                                        );
                                    }

                                    FrequenciaService::sincronizarFrequenciasDaAgenda($agenda);
                                }

                                CursoFrequencias::whereIn('agenda_id', $agendas->pluck('id'))
                                    ->whereIn('aluno_id', $alunos->pluck('id'))
                                    ->get()
                                    ->each(function (CursoFrequencias $frequencia) use ($data): void {
                                        $frequencia->situacaofrequencia_id = config('settings.frequencia.presente');
                                        $frequencia->observacao = $data['observacao'] ?? null;
                                        $frequencia->save();
                                    });
                            });

                            Notification::make()
                                ->title('Frequência registrada com sucesso!')
                                ->success()
                                ->send();
                        }),

                    Action::make('comunicar_instrutores')
                        ->label('Comunicar Instrutores')
                        ->icon('heroicon-o-users')
                        ->form([
                            Select::make('instrutores')
                                ->label('Instrutores que devem receber o email')
                                ->options(function ($record) {
                                    return User::whereIn('id', $record->instrutores()->pluck('instrutor_id'))->pluck('name', 'id');
                                })
                                ->searchable(['name'])
                                ->multiple()
                                ->required()
                                ->columnSpanFull(),
                        ])
                        ->action(function (Model $record, array $data) {
                            $instrutores = $record->instrutores()->whereIn('instrutor_id', $data['instrutores'])
                                ->where('curso_id', $record->id)
                                ->get();

                            EmailService::dispararEmailInstrutor($instrutores);

                            Notification::make()
                                ->title('E-mails disparados para os instrutores selecionados!')
                                ->success()
                                ->send();
                        }),

                    Action::make('inscricao')
                        ->hiddenLabel()
                        ->label('Link da inscrição')
                        ->tooltip('Link da Inscrição do curso')
                        ->size('xl')
                        ->icon('heroicon-o-link')
                        // ->color('success')
                        ->url(fn(Cursos $record): string => route('realizar.inscricao', $record->uuid))
                        ->openUrlInNewTab(),

                    Action::make('frequencia')
                        ->hiddenLabel()
                        ->label('Link da Frequência')
                        ->tooltip('Link da Frequência')
                        ->size('xl')
                        ->icon('heroicon-o-cursor-arrow-ripple')
                        // ->color('info')
                        ->url(fn(Cursos $record): string => route('realizar.frequencia', $record->uuid))
                        ->openUrlInNewTab(),

                    Action::make('imprimir')
                        ->hiddenLabel()
                        ->label('Imprimir QRcode')
                        ->tooltip('Imprimir QRcode da Frequência')
                        ->size('xl')
                        ->icon('heroicon-o-qr-code')
                        // ->color('danger')
                        ->url(fn(Cursos $record): string => route('qrcode.frequencia', $record->uuid))
                        ->openUrlInNewTab(),

                    Action::make('cartaz')
                        ->hiddenLabel()
                        ->label('Cartaz com QRcode')
                        ->tooltip('Imprimir QRcode da Frequência')
                        ->size('xl')
                        ->icon('heroicon-o-qr-code')
                        // ->color('danger')
                        ->url(fn(Cursos $record): string => route('imprimir.cartaz-qrcode-frequencia', $record->uuid))
                        ->openUrlInNewTab(),

                    Action::make('atividades')
                        ->hiddenLabel()
                        ->label('Atividades do Curso')
                        ->tooltip('Atividades do Curso')
                        ->size('xl')
                        ->icon('heroicon-o-calendar')
                        // ->color('danger')
                        ->url(fn($record) => route('filament.admin.resources.escola.curso-atividades.index', [
                            'curso_id' => $record->id,
                        ]))
                        ->openUrlInNewTab(),

                    Action::make('alunos')
                        ->hiddenLabel()
                        ->label('Alunos do Curso')
                        ->tooltip('Alunos do Curso')
                        ->size('xl')
                        ->icon('heroicon-o-user-group')
                        // ->color('danger')
                        ->url(fn($record) => route('filament.admin.resources.escola.curso-alunos.index', [
                            'curso_id' => $record->id,
                        ]))
                        ->openUrlInNewTab(),

                    Action::make('alunos_frequencia')
                        ->hiddenLabel()
                        ->label('Frequência dos Alunos')
                        ->tooltip('Frequência dos Alunos')
                        ->size('xl')
                        ->icon('heroicon-o-document-check')
                        // ->color('danger')
                        ->url(fn($record) => route('filament.admin.resources.escola.curso-frequencias.index', [
                            'curso_id' => $record->id,
                        ]))
                        ->openUrlInNewTab(),

                    Action::make('certificado')
                        ->hiddenLabel()
                        ->label('Certificados dos Alunos')
                        ->tooltip('Certificados dos Alunos')
                        ->size('xl')
                        ->icon('heroicon-o-identification')
                        // ->color('danger')
                        ->url(fn($record) => route('filament.admin.resources.escola.curso-certificados.index', [
                            'curso_id' => $record->id,
                        ]))
                        ->openUrlInNewTab(),

                    Action::make('avaliacao')
                        ->hiddenLabel()
                        ->label('Respostas da Avaliação de Reação')
                        ->tooltip('Respostas da Avaliação de Reação')
                        ->size('xl')
                        ->icon('heroicon-o-clipboard-document-check')
                        // ->color('danger')
                        ->url(fn($record) => route('filament.admin.resources.escola.submissions.index', [
                            'curso_id' => $record->id,
                        ]))
                        ->openUrlInNewTab(),
                    Action::make('consideracoes')
                        ->label('Considerações')
                        ->hiddenLabel()
                        ->icon('heroicon-o-chat-bubble-bottom-center-text')
                        ->size('xl')
                        ->tooltip('Considerações sobre o Plano de Curso')
                        // ->color('warning')
                        ->accessSelectedRecords()
                        ->slideOver()
                        ->form(fn(Model $record) => static::getFormfieldsCursoComentario($record),)
                        ->action(function (array $data, Model $record): void {
                            try {
                                $transacao = new CursoComentarios();
                                $transacao->curso_id = $record['id'];
                                $transacao->atualizado_por = Auth::user()->id;
                                $transacao->descricao = $data['descricao'];
                                $transacao->justificativa = $data['justificativa'];
                                $transacao->objetivo_geral = $data['objetivo_geral'];
                                $transacao->conteudo_programatico = $data['conteudo_programatico'];
                                $transacao->abordagem_pedagogica = $data['abordagem_pedagogica'];
                                $transacao->avaliacao_cursistas = $data['avaliacao_cursistas'];
                                $transacao->referencias_bibliograficas = $data['referencias_bibliograficas'];
                                $transacao->save();

                                Notification::make()
                                    ->title('Comentário registrado corretamente!')
                                    ->success()
                                    ->send();
                            } catch (Exception $e) {
                                Notification::make()
                                    ->title('ERRO!' . $e)
                                    ->danger()
                                    ->send();
                            }
                        }),
                ])
                    ->size('lg')
                    ->color('info')
                    ->tooltip('Ações')
                    ->icon('heroicon-o-bars-arrow-down'),
            ])
            ->toolbarActions([
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

                BulkAction::make('attachTags')
                    ->label('Incluir Tags')
                    ->icon('heroicon-o-tag')
                    ->color('primary')
                    ->form([
                        Select::make('tags')
                            ->label('Selecionar Tags')
                            ->options(Tags::all()->pluck('descricao', 'id')) // Adjust 'descricao' if your column name is different
                            ->multiple()
                            ->searchable()

                            ->required(),
                    ])
                    ->action(function (Collection $records, array $data): void {
                        foreach ($records as $record) {
                            $record->tags()->syncWithoutDetaching($data['tags']);
                        }

                        Notification::make()
                            ->title('Tags incluídas com sucesso!')
                            ->success()
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion(),
                // ]),
            ])
            ->persistFiltersInSession()
            ->selectCurrentPageOnly()
            ->paginated([50, 100, 150, 200, 'all'])
            ->defaultPaginationPageOption(50)
            ->striped()
            ->deferLoading(false);
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
            'index' => Pages\ListCursos::route('/'),
            'create' => Pages\CreateCursos::route('/create'),
            'edit' => Pages\EditCursos::route('/{record}/edit'),
        ];
    }
    public static function getNavigationBadge(): ?string
    {
        $count = number_format(static::getModel()::count(), 0, ',', '.');
        return $count;
    }
    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }
    public static function getGlobalSearchResultTitle(Model $record): string|Htmlable
    {
        return $record->titulo;
    }
    public static function getGloballySearchableAttributes(): array
    {
        return [
            'titulo',
            'userInstrutores.name',
            'recursos.descricao',
            'publico.descricao',
        ];
    }
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Curso' => $record->titulo,
            'Instrutores' => $record->userInstrutores->pluck('name')->join(', '),
            // 'Recursos' => $record->recursos->tiporecurso->descricao,
        ];
    }
    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with([
            'userInstrutores',
            'recursos',
            'publico',
        ]);
    }
}
