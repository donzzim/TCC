<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources;

use App\Filament\Egap\Interno\Clusters\PatrimonioCluster;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\ConciliacaoResource\Pages\CreateConciliacao;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\ConciliacaoResource\Pages\EditConciliacao;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\ConciliacaoResource\Pages\ListConciliacaos;
use App\Filament\Egap\Support\MoneyInput;
use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use App\Models\egap\Cadastro\Fornecedores;
use App\Models\egap\Patrimonio\BensMoveis\BemMovel;
use App\Models\egap\Patrimonio\BensMoveis\Conciliacao;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Enums\SubNavigationPosition;
use App\Filament\Egap\Interno\Permissions\Patrimonio\PatrimonioResource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Throwable;

class ConciliacaoResource extends PatrimonioResource
{
    protected static ?string $cluster = PatrimonioCluster::class;

    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?string $model = Conciliacao::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static string | \UnitEnum | null $navigationGroup = 'Bens Móveis';

    protected static ?string $navigationLabel = 'Conciliação';

    protected static ?string $modelLabel = 'Conciliação';

    protected static ?string $pluralModelLabel = 'Conciliações';

    protected static ?int $navigationSort = 7;

    protected static ?string $slug = 'bens-moveis/conciliacoes';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Conciliação')
                    ->persistTabInQueryString()
                    ->tabs([
                        Tab::make('Patrimônio')
                            ->icon('heroicon-o-identification')
                            ->schema([
                                Section::make('Identificação')
                                    ->description('Dados principais usados para identificar o patrimônio conciliado.')
                                    ->icon('heroicon-o-qr-code')
                                    ->schema([
                                        TextInput::make('numero_patrimonio')
                                            ->label('Número do Patrimônio')
                                            ->required(),
                                        TextInput::make('patrimonio_desmembrado')
                                            ->label('Patrimônio Desmembrado')
                                            ->required(),
                                        Textarea::make('descricao')
                                            ->label('Descrição')
                                            ->rows(4)
                                            ->required()
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2),
                            ]),

                        Tab::make('Aquisição')
                            ->icon('heroicon-o-receipt-percent')
                            ->schema([
                                Section::make('Dados de Aquisição')
                                    ->description('Informações financeiras e documentais do patrimônio.')
                                    ->icon('heroicon-o-document-check')
                                    ->schema([
                                        DatePicker::make('data_aquisicao')
                                            ->label('Data de aquisição')
                                            ->displayFormat('d/m/Y')
                                            ->native(false)
                                            ->required(),
                                        MoneyInput::make('valor_aquisicao')
                                            ->label('Valor de aquisição')
                                            ->required(),
                                        TextInput::make('forma_aquisicao')
                                            ->label('Forma de aquisição')
                                            ->required(),
                                        TextInput::make('numero_documento')
                                            ->label('Número do Documento')
                                            ->required(),
                                        TextInput::make('fornecedor')
                                            ->label('Fornecedor')
                                            ->required()
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2),
                            ]),

                        Tab::make('Localização')
                            ->icon('heroicon-o-map-pin')
                            ->schema([
                                Section::make('Localização')
                                    ->description('Local e comarca vinculados ao patrimônio.')
                                    ->icon('heroicon-o-building-office')
                                    ->schema([
                                        TextInput::make('local')
                                            ->label('Local')
                                            ->required(),
                                        TextInput::make('comarca')
                                            ->label('Comarca')
                                            ->required(),
                                    ])
                                    ->columns(2),
                            ]),

                        Tab::make('Conciliação')
                            ->icon('heroicon-o-clipboard-document-check')
                            ->schema([
                                Section::make('Dados da Conciliação')
                                    ->description('Dados finais usados no registro conciliado.')
                                    ->icon('heroicon-o-check-badge')
                                    ->schema([
                                        Select::make('patrimonio')
                                            ->label('Número do Patrimônio')
                                            ->placeholder('Busque pelo número do patrimônio')
                                            ->relationship('patrimonioRef', 'NumPatrimonio')
                                            ->searchable()
                                            ->getOptionLabelFromRecordUsing(fn(BemMovel $record): string => "{$record->NumPatrimonio} - {$record->Descricao}")
                                            ->live()
                                            ->required(),
                                        DatePicker::make('data_conciliacao')
                                            ->label('Data de conciliação')
                                            ->default(now())
                                            ->displayFormat('d/m/Y')
                                            ->native(false)
                                            ->required(),
                                    ])
                                    ->columns(2),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return TableDefaults::apply($table)
            ->modifyQueryUsing(fn($query) => $query->with('patrimonioRef'))
            ->columns([
                TableColumns::text('numero_patrimonio', 'Patrimônio', true)
                    ->badge()
                    ->copyable()
                    ->copyMessage('Patrimônio copiado')
                    ->weight('medium'),
                TableColumns::text('descricao', 'Descrição')
                    ->limit(45)
                    ->wrap()
                    ->tooltip(fn(Conciliacao $record): ?string => $record->descricao),
                TableColumns::text('patrimonioRef.NumPatrimonio', 'Conciliado')
                    ->badge()
                    ->color('success')
                    ->formatStateUsing(fn(?string $state, Conciliacao $record): string => $record->patrimonioRef
                        ? "{$record->patrimonioRef->NumPatrimonio} - {$record->patrimonioRef->Descricao}"
                        : ($state ?? '-'))
                    ->searchable(false)
                    ->sortable(false),
                TableColumns::date('data_conciliacao', 'Conciliação')
                    ->badge()
                    ->color('primary'),
                TableColumns::text('valor_aquisicao', 'Valor')
                    ->formatStateUsing(fn($state): string => self::formatarValorMonetario($state))
                    ->weight('medium'),
                TableColumns::text('numero_documento', 'Documento')
                    ->badge()
                    ->color('gray')
                    ->copyable(),
                TableColumns::text('fornecedor', 'Fornecedor')
                    ->limit(35)
                    ->wrap()
                    ->tooltip(fn(Conciliacao $record): ?string => $record->fornecedor),
                TableColumns::date('data_aquisicao', 'Aquisição'),
                TableColumns::text('forma_aquisicao', 'Forma de aquisição')
                    ->badge()
                    ->color('gray'),
                TableColumns::text('local', 'Local')
                    ->limit(30)
                    ->tooltip(fn(Conciliacao $record): ?string => $record->local),
                TableColumns::text('comarca', 'Comarca')
                    ->limit(30)
                    ->tooltip(fn(Conciliacao $record): ?string => $record->comarca),
                TableColumns::text('patrimonio_desmembrado', 'Desmembrado')
                    ->badge()
                    ->color('warning'),
            ])
            ->recordActions([
                ...TableDefaults::actions(),
                self::conciliarTableAction(),
            ])
            ->toolbarActions([
                self::conciliarTableBulkAction(),
                ...TableDefaults::bulkActions(),
            ]);
    }

    private static function conciliarTableAction(): Action
    {
        return Action::make('conciliarAction')
            ->tooltip('Conciliar')
            ->hiddenLabel()
            ->icon('heroicon-o-wrench-screwdriver')
            ->color('gray')
            ->requiresConfirmation()
            ->modalHeading('Conciliar patrimônio')
            ->modalDescription('Os dados desta conciliação serão aplicados ao bem móvel selecionado.')
            ->action(function (Conciliacao $record): void {
                $resultado = self::conciliarRegistro($record);

                if (! $resultado['conciliado']) {
                    Notification::make()
                        ->title('Não foi possível conciliar.')
                        ->body($resultado['mensagem'])
                        ->danger()
                        ->send();

                    return;
                }

                Notification::make()
                    ->title('Patrimônio conciliado com sucesso.')
                    ->body($resultado['mensagem'])
                    ->success()
                    ->send();
            });
    }

    private static function conciliarTableBulkAction(): BulkAction
    {
        return BulkAction::make('conciliarBulkAction')
            ->label('Conciliar')
            ->icon('heroicon-o-wrench-screwdriver')
            ->requiresConfirmation()
            ->modalHeading('Conciliar patrimônios')
            ->modalDescription('Os dados das conciliações selecionadas serão aplicados aos respectivos bens móveis.')
            ->deselectRecordsAfterCompletion()
            ->action(function (Collection $records): void {
                $conciliados = 0;
                $ignorados = 0;
                $avisos = [];

                foreach ($records as $record) {
                    $resultado = self::conciliarRegistro($record);

                    if (! $resultado['conciliado']) {
                        $ignorados++;
                        $avisos[] = $resultado['mensagem'];

                        continue;
                    }

                    $conciliados++;

                    if ($resultado['mensagem'] !== null) {
                        $avisos[] = $resultado['mensagem'];
                    }
                }

                $notification = Notification::make()
                    ->title("{$conciliados} conciliação(ões) aplicada(s).")
                    ->body(self::formatarAvisosConciliacao($avisos, $ignorados));

                $conciliados > 0
                    ? $notification->success()
                    : $notification->warning();

                $notification->send();
            });
    }

    private static function conciliarRegistro(Conciliacao $conciliacao): array
    {
        $bem = $conciliacao->patrimonioRef()->first();

        if (! $bem) {
            return [
                'conciliado' => false,
                'mensagem' => "A conciliação {$conciliacao->getKey()} não possui patrimônio vinculado.",
            ];
        }

        $dados = self::dadosConciliadosParaBem($conciliacao);
        $fornecedorId = self::resolverFornecedor($conciliacao->fornecedor);
        $fornecedorNaoEncontrado = ! blank($conciliacao->fornecedor) && $fornecedorId === null;

        if ($fornecedorId !== null) {
            $dados['Fornecedor'] = $fornecedorId;
        }

        $bem->fill($dados);
        $bem->save();

        return [
            'conciliado' => true,
            'mensagem' => $fornecedorNaoEncontrado
                ? "Fornecedor '{$conciliacao->fornecedor}' não localizado; fornecedor do patrimônio {$bem->NumPatrimonio} foi mantido."
                : "Patrimônio {$bem->NumPatrimonio} atualizado.",
        ];
    }

    private static function dadosConciliadosParaBem(Conciliacao $conciliacao): array
    {
        $dados = [];
        $dataAquisicao = self::normalizarData($conciliacao->data_aquisicao);
        $valorAquisicao = MoneyInput::normalizeState($conciliacao->valor_aquisicao);

        self::preencherSeNaoVazio($dados, 'TomboSmarapd', $conciliacao->numero_patrimonio);
        self::preencherSeNaoVazio($dados, 'DatadeIncorporacao', $dataAquisicao);
        self::preencherSeNaoVazio($dados, 'DataDisponibilizacao', $dataAquisicao);
        self::preencherSeNaoVazio($dados, 'ValorAquisicao', $valorAquisicao);
        self::preencherSeNaoVazio($dados, 'Valor', $valorAquisicao);
        self::preencherSeNaoVazio($dados, 'FormaAquisicao', self::normalizarTexto($conciliacao->forma_aquisicao));
        self::preencherSeNaoVazio($dados, 'NotaFiscal', $conciliacao->numero_documento);

        return $dados;
    }

    private static function resolverFornecedor(mixed $fornecedor): ?int
    {
        if (blank($fornecedor)) {
            return null;
        }

        $fornecedorNormalizado = self::normalizarTextoComparacao($fornecedor);

        if ($fornecedorNormalizado === null) {
            return null;
        }

        $fornecedorId = Fornecedores::query()
            ->get(['id', 'NomeFornecedor'])
            ->first(fn(Fornecedores $fornecedor): bool => self::normalizarTextoComparacao($fornecedor->NomeFornecedor) === $fornecedorNormalizado)
            ?->id;

        return $fornecedorId === null ? null : (int) $fornecedorId;
    }

    private static function normalizarData(mixed $data): ?string
    {
        if (blank($data)) {
            return null;
        }

        try {
            $data = trim((string) $data);

            return str_contains($data, '/')
                ? Carbon::createFromFormat('d/m/Y', $data)->toDateString()
                : Carbon::parse($data)->toDateString();
        } catch (Throwable) {
            return null;
        }
    }

    private static function normalizarTexto(mixed $texto): ?string
    {
        return blank($texto)
            ? null
            : mb_strtoupper(trim((string) $texto));
    }

    private static function normalizarTextoComparacao(mixed $texto): ?string
    {
        $texto = self::normalizarTexto($texto);

        return $texto === null
            ? null
            : preg_replace('/\s+/', ' ', $texto);
    }

    private static function formatarValorMonetario(mixed $valor): string
    {
        $valor = MoneyInput::normalizeState($valor);

        return $valor === null
            ? '-'
            : 'R$ ' . number_format((float) $valor, 2, ',', '.');
    }

    private static function preencherSeNaoVazio(array &$dados, string $campo, mixed $valor): void
    {
        if (! blank($valor)) {
            $dados[$campo] = $valor;
        }
    }

    private static function formatarAvisosConciliacao(array $avisos, int $ignorados): ?string
    {
        $avisos = array_values(array_unique(array_filter($avisos)));

        if ($ignorados > 0) {
            array_unshift($avisos, "{$ignorados} registro(s) ignorado(s).");
        }

        return $avisos === []
            ? null
            : implode(PHP_EOL, array_slice($avisos, 0, 5));
    }

    public static function getPages(): array
    {
        return [
            'index' => ListConciliacaos::route('/'),
            'create' => CreateConciliacao::route('/create'),
            'edit' => EditConciliacao::route('/{record}/edit'),
        ];
    }
}
