<?php

namespace App\Filament\Egap\Interno\Groups\Relatorios;

use App\Http\Controllers\Egap\RelatoriosController\RelatorioController;
use App\Services\Egap\CentroCustoSetor;
use App\Models\egap\Cadastro\ContaContabil;
use App\Models\egap\Cadastro\DescricaoDetalhada;
use App\Models\egap\Patrimonio\BensMoveis\BemMovel;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Js;
class RelatoriosGerais extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'egap.livewire.support.filament-page-form';

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationLabel = 'Relatórios Gerais';

    protected static ?string $title = 'Relatórios Gerais';

    protected static ?string $slug = 'relatorios-gerais';

    protected static string | \UnitEnum | null $navigationGroup = 'Relatórios';

    protected ?string $subheading = 'Aqui você encontra diversos relatórios relacionados ao sistema do Tribunal de Justiça';

    protected static ?int $navigationSort = 1;

    /** @var array<string, mixed> */
    public ?array $data = [];

    /** Campos do form que correspondem a filtros do RelatorioController. */
    private const CAMPOS_FILTRO = [
        'data_inicio', 'data_termino', 'conta_contabil', 'situacao_contabil',
        'centro_custo', 'numero_processo', 'nota_fiscal',
        'grupo', 'acuracia', 'materiais',
    ];

    /** token de filtro (RelatorioController) => campo(s) do form. */
    private const TOKEN_PARA_CAMPOS = [
        'periodo' => ['data_inicio', 'data_termino'],
        'conta' => ['conta_contabil'],
        'conta_req' => ['conta_contabil'],
        'situacao' => ['situacao_contabil'],
        'centro_custo' => ['centro_custo'],
        'processo' => ['numero_processo'],
        'nota_fiscal' => ['nota_fiscal'],
        'grupo' => ['grupo'],
        'acuracia' => ['acuracia'],
        'materiais' => ['materiais'],
    ];

    public function mount(): void
    {
        $this->form->fill(['situacao_contabil' => 'Todos']);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Relatório')
                    ->schema([
                        Select::make('relatorio')
                            ->label('Selecione o relatório')
                            ->options(RelatorioController::opcoesAgrupadas())
                            ->searchable()
                            ->native(false)
                            ->required()
                            ->live()
                            ->columnSpanFull(),
                    ]),

                Section::make('Filtros do relatório')
                    ->columns(2)
                    ->visible(fn (Get $get): bool => filled($get('relatorio')) && RelatorioController::temFiltros((string) $get('relatorio')))
                    ->schema($this->camposFiltro()),

                Actions::make([
                    Action::make('imprimir')
                        ->label('Imprimir')
                        ->icon('heroicon-o-printer')
                        ->color('primary')
                        ->action(fn () => $this->gerar(excel: false)),
                    Action::make('excel')
                        ->label('Exportar Excel')
                        ->icon('heroicon-o-table-cells')
                        ->color('success')
                        ->action(fn () => $this->gerar(excel: true)),
                ]),
            ]);
    }

    /**
     * @return list<\Filament\Forms\Components\Field>
     */
    protected function camposFiltro(): array
    {
        $temToken = fn (Get $get, string $token): bool => in_array(
            $token,
            RelatorioController::filtros((string) $get('relatorio')),
            true,
        );

        $temPeriodo = fn (Get $get): bool => $temToken($get, 'periodo');
        $temConta = fn (Get $get): bool => $temToken($get, 'conta') || $temToken($get, 'conta_req');
        $contaObrigatoria = fn (Get $get): bool => $temToken($get, 'conta_req');

        return [
            DatePicker::make('data_inicio')
                ->label('Data início')
                ->native(false)
                ->displayFormat('d/m/Y')
                ->visible($temPeriodo)
                ->required($temPeriodo),

            DatePicker::make('data_termino')
                ->label('Data término')
                ->native(false)
                ->displayFormat('d/m/Y')
                ->afterOrEqual('data_inicio')
                ->visible($temPeriodo)
                ->required($temPeriodo),

            // Busca no servidor: o plano de contas tem ~14 mil linhas, e embuti-las
            // como opções gerava ~2 MB de HTML a cada troca de relatório.
            Select::make('conta_contabil')
                ->label('Conta contábil')
                ->searchable()
                ->native(false)
                ->searchPrompt('Digite o código ou o título da conta')
                ->getSearchResultsUsing(fn (string $search): array => ContaContabil::query()
                    ->where(fn ($q) => $q
                        ->where('codigo', 'like', "{$search}%")
                        ->orWhere('titulo', 'like', "%{$search}%")
                        // aceita o código digitado sem os pontos (12311 -> 1.2.3.1.1)
                        ->when(preg_match('/^[\d.]+$/', $search), fn ($q) => $q
                            ->orWhereRaw("REPLACE(codigo, '.', '') LIKE ?", [str_replace('.', '', $search) . '%'])))
                    ->orderBy('codigo')
                    ->limit(50)
                    ->get(['id', 'codigo', 'titulo'])
                    ->mapWithKeys(fn (ContaContabil $c): array => [$c->id => "{$c->codigo} - {$c->titulo}"])
                    ->all())
                ->getOptionLabelUsing(fn ($value): ?string => ($c = ContaContabil::query()->find($value, ['id', 'codigo', 'titulo']))
                    ? "{$c->codigo} - {$c->titulo}"
                    : null)
                ->visible($temConta)
                ->required($contaObrigatoria),

            Select::make('situacao_contabil')
                ->label('Situação do inventário')
                ->native(false)
                ->options(['Todos' => 'Todos', 'LOCALIZADO' => 'Localizado', 'NÃO LOCALIZADO' => 'Não localizado'])
                ->default('Todos')
                ->visible(fn (Get $get): bool => $temToken($get, 'situacao')),

            Select::make('centro_custo')
                ->label('Centro de custo')
                ->searchable()
                ->native(false)
                ->options(fn (): array => CentroCustoSetor::opcoesFiltro())
                ->visible(fn (Get $get): bool => $temToken($get, 'centro_custo')),

            TextInput::make('numero_processo')
                ->label('Número do processo')
                ->visible(fn (Get $get): bool => $temToken($get, 'processo')),

            TextInput::make('nota_fiscal')
                ->label('Nota fiscal')
                ->visible(fn (Get $get): bool => $temToken($get, 'nota_fiscal')),

            Select::make('grupo')
                ->label('Grupo de inventário')
                ->native(false)
                ->options([
                    'A' => 'Inventariado a partir de 2015',
                    'B' => 'Inventariado antes de 2015',
                    'C' => 'Inventário online',
                    'D' => 'A inventariar',
                ])
                ->visible(fn (Get $get): bool => $temToken($get, 'grupo')),

            Select::make('acuracia')
                ->label('Acurácia')
                ->searchable()
                ->native(false)
                // DISTINCT varre ~190 mil bens (~1,3 s); a lista quase não muda.
                ->options(fn (): array => Cache::remember('egap.relatorios-gerais.acuracias', now()->addHour(), fn (): array => BemMovel::query()
                    ->whereNotNull('acuracia')
                    ->where('acuracia', '!=', '')
                    ->orderBy('acuracia')
                    ->distinct()
                    ->pluck('acuracia', 'acuracia')
                    ->all()))
                ->visible(fn (Get $get): bool => $temToken($get, 'acuracia')),

            Select::make('materiais')
                ->label('Materiais')
                ->multiple()
                ->searchable()
                ->native(false)
                ->options(fn () => DescricaoDetalhada::query()
                    // Coluna varchar: comparar com string usa o índice (545 ms -> 11 ms).
                    ->where('item_estoque', '1')
                    ->orderBy('descricao_detalhada')
                    ->pluck('descricao_detalhada', 'id'))
                ->visible(fn (Get $get): bool => $temToken($get, 'materiais'))
                ->columnSpanFull(),
        ];
    }

    /**
     * Valida o form e abre a impressão numa nova aba.
     */
    protected function gerar(bool $excel): void
    {
        $estado = $this->form->getState();
        $slug = (string) ($estado['relatorio'] ?? '');

        // Melhoria do legado (bens-patrimonio): exige ao menos um filtro.
        if ($slug === 'bens-patrimoniais'
            && blank($estado['numero_processo'] ?? null)
            && blank($estado['nota_fiscal'] ?? null)
            && blank($estado['grupo'] ?? null)
            && blank($estado['acuracia'] ?? null)) {
            Notification::make()
                ->warning()
                ->title('Informe ao menos um filtro (processo, nota fiscal, grupo ou acurácia).')
                ->send();

            return;
        }

        $params = ['relatorio' => $slug];

        foreach ($this->camposAtivos($slug) as $campo) {
            $valor = $estado[$campo] ?? null;
            if ($valor !== null && $valor !== '' && $valor !== []) {
                $params[$campo] = $valor;
            }
        }

        if ($excel) {
            $params['tipo'] = 'excel';
        }

        $url = route('relatorios.gerais.imprimir', $params);
        $this->js('window.open(' . Js::from($url) . ", '_blank')");
    }

    /**
     * Campos do form que o relatório selecionado realmente consome.
     *
     * @return list<string>
     */
    protected function camposAtivos(string $slug): array
    {
        $campos = [];

        foreach (RelatorioController::filtros($slug) as $token) {
            foreach (self::TOKEN_PARA_CAMPOS[$token] ?? [] as $campo) {
                $campos[$campo] = true;
            }
        }

        return array_values(array_intersect(self::CAMPOS_FILTRO, array_keys($campos)));
    }
}
