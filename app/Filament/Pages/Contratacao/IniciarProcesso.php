<?php

namespace App\Filament\Pages\Contratacao;

use UnitEnum;
use BackedEnum;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Wizard\Step;
use App\Forms\Components\ButtonGroupRadio;
use App\Models\Contratacao\TipoProcesso;
use App\Models\Escola\AreaTematica;
use App\Models\Escola\Cursos;
use App\Models\Escola\Instrutores;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms;
use Filament\Actions\Action;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class IniciarProcesso extends Page
{
    public ?array $data = [];
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Iniciar Processo';
    protected static ?string $modelLabel = 'Iniciar Processo';
    protected static ?string $puralModelLabel = 'Iniciar Processo';
    protected static string | UnitEnum | null $navigationGroup = 'Contratação';
    protected static ?int $navigationSort = 3;
    protected string $view = 'filament.pages.contratacao.iniciar-processo';

    public function getCursos(Get $get, Set $set, $curso)
    {
        $get('data.areatematica_id', $curso->areatematica_id);
    }

    public function getGrupos(string $texto)
    {
        $dados = [];

        return $dados;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make([
                    Step::make('Contratação')
                        ->columns(6)
                        ->schema([

                            Forms\Components\Select::make('catalogo')
                                ->label('Catálogo')
                                ->searchable()
                                ->live(debounce: 800)
                                ->afterStateUpdated(fn(callable $set) => $set('servicos', null))
                                ->placeholder('Digite para pesquisar um grupo...')
                                ->getSearchResultsUsing(function (string $search) {
                                    $search = strtoupper($search);
                                    $grupos = Cache::remember('compras_net_grupos', now()->addDay(), function () use ($search) {
                                        $response = Http::get("https://cnbs.estaleiro.serpro.gov.br/cnbs-api/item/v1/hint?palavra={$search}");
                                        return $response->successful() ? $response->json() : [];
                                    });
                                    $resultadosFiltrados = collect($grupos)->filter(function ($grupo) use ($search) {
                                        return Str::contains(strtoupper($grupo['nome']), $search);
                                    });
                                    return $resultadosFiltrados->pluck('nome', 'codigo')->all();
                                })
                                ->getOptionLabelUsing(function ($value): ?string {
                                    // return Cache::remember("compras_net_grupo_{$value}", now()->addDay(), function () use ($value) {
                                    //     $response = Http::get("https://cnbs.estaleiro.serpro.gov.br/cnbs-api/item/v1/hint?palavra={$value}");
                                    //     return $response->successful() ? $response->json()['nome'] : null;
                                    // });
                                    return '';
                                })
                                ->columnSpan(2),

                            Forms\Components\Select::make('servicos')
                                ->label('Serviços')
                                // ->searchable()
                                ->live()
                                ->placeholder('Selecione um grupo para ver os serviços')
                                ->options(function (callable $get): array {
                                    $grupoId = $get('grupo_servicos');
                                    if (! $grupoId) {
                                        return [];
                                    }
                                    $servicos = Cache::remember("compras_net_servicos_do_grupo_{$grupoId}", now()->addDay(), function () use ($grupoId) {
                                        $response = Http::get("https://compras.dados.gov.br/servicos/v1/servicos.json?grupo={$grupoId}");
                                        return $response->successful() ? $response->json()['_embedded']['servicos'] : [];
                                    });
                                    return collect($servicos)->pluck('descricao', 'codigo')->all();
                                })
                                // ->disabled(fn(callable $get): bool => ! $get('grupo_servicos'))
                                ->columnSpan(2),

                            Forms\Components\Select::make('tipoprocesso_id')
                                ->options(
                                    TipoProcesso::all()->pluck('descricao', 'id')
                                )
                                ->label('Tipo de Processo')
                                ->required()
                                ->suffixAction(
                                    Action::make('ativarCampos')
                                        ->icon('heroicon-m-magnifying-glass')
                                        ->action(function (Set $set, $state) {
                                            // $set();
                                        }),
                                )
                                ->live(debounce: 800)
                                ->preload()
                                ->columnSpan(2),

                            Section::make('Curso')
                                ->live()
                                ->schema([

                                    Forms\Components\Select::make('curso_id')
                                        ->options(
                                            Cursos::where('inicio', '>', now())->pluck('titulo', 'id')
                                        )
                                        ->searchable(['titulo'])
                                        ->label('Curso')
                                        ->live()
                                        ->preload()
                                        ->columnSpan(3)
                                        ->afterStateUpdated(function (Set $set, $state) {
                                            $instrutores = Instrutores::where('curso_id', '=', $state)->get()->pluck('instutor.name', 'instrutor.id');
                                            $set('instrutor_id', $instrutores);
                                        }),

                                    Forms\Components\Select::make('areatematica_id')
                                        ->options(
                                            AreaTematica::all()->pluck('descricao', 'id')
                                        )
                                        ->label('Área Temática')
                                        ->live()
                                        ->preload()
                                        ->columnSpan(3),

                                    Forms\Components\Select::make('instrutor_id')
                                        ->options([])
                                        ->label('Instrutor')
                                        ->live()
                                        ->preload()
                                        ->columnSpan(3),

                                ]),
                        ]),
                    Step::make('Material')
                        ->schema([
                            // ...
                        ]),
                    Step::make('Serviço')
                        ->schema([
                            // ...
                        ]),
                ])->submitAction(new HtmlString(Blade::render(<<<BLADE
                    <x-filament::button
                        type="submit"
                        size="sm"
                    >
                        Submit
                    </x-filament::button>
                BLADE))),

            ])
            ->statePath('data');
    }

    public function create(): void {}
}
