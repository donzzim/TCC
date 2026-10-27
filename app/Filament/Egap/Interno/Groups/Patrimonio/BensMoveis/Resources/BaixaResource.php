<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources;

use App\Filament\Egap\Interno\Clusters\PatrimonioCluster;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\BaixaResource\Pages\CreateBaixa;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\BaixaResource\Pages\EditBaixa;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\BaixaResource\Pages\ListBaixas;
use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use App\Filament\Egap\Support\TableModalAction;
use App\Models\egap\Patrimonio\BensMoveis\Baixa;
use App\Models\egap\Patrimonio\BensMoveis\BemMovel;
use App\Models\egap\Patrimonio\BensMoveis\ItemBaixa;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Enums\SubNavigationPosition;
use App\Filament\Egap\Interno\Permissions\Patrimonio\PatrimonioResource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\HtmlString;
use Livewire\Livewire;

class BaixaResource extends PatrimonioResource
{
    private const TERMO_BAIXA_IMPRIMIR_ROUTE = 'termo.baixa.imprimir';

    protected static ?string $cluster = PatrimonioCluster::class;

    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?string $model = Baixa::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-archive-box-x-mark';

    protected static string | \UnitEnum | null $navigationGroup = 'Bens Móveis';

    protected static ?string $navigationLabel = 'Baixa de bens';

    protected static ?string $breadcrumb = 'Baixa de bens';

    protected static ?string $modelLabel = 'Baixa';

    protected static ?string $pluralModelLabel = 'Baixas de Bens';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'bens-moveis/baixas';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Dados da Baixa')
                    ->description('Informe o processo, a data e os dados do requisitante.')
                    ->icon('heroicon-o-archive-box-x-mark')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('NumeroProcesso')
                                ->label('Processo Nº')
                                ->options(fn() => Baixa::query()->whereNotNull('NumeroProcesso')->distinct()->pluck('NumeroProcesso', 'NumeroProcesso')->toArray())
                                ->searchable()
                                ->native(false)
                                ->placeholder('Selecione ou informe o processo')
                                ->required()
                                ->unique(table: 'egap_mat_baixa', column: 'NumeroProcesso', ignoreRecord: true)
                                ->validationMessages(['unique' => 'Processo já baixado! Verifique o histórico do Tribunal.']),

                            DatePicker::make('DataBaixa')
                                ->label('Data da Baixa')
                                ->default(now())
                                ->displayFormat('d/m/Y')
                                ->native(false)
                                ->required(),
                            TextInput::make('Requisitante')
                                ->label('Requisitante')
                                ->placeholder('Informe o requisitante'),
                            TextInput::make('RequisitanteCnpj')
                                ->label('CNPJ')
                                ->placeholder('00.000.000/0000-00'),
                        ]),
                        Textarea::make('Endereco')->label('Endereço')->rows(2)->columnSpanFull(),
                        Textarea::make('Observacao')->label('Observação')->rows(3)->columnSpanFull(),
                    ]),

                Section::make('Materiais (Itens da Baixa)')
                    ->description('Adicione os patrimônios e defina a situação de destino.')
                    ->icon('heroicon-o-rectangle-stack')
                    ->schema([
                        Repeater::make('itens')
                            ->relationship('itens')
                            ->schema([
                                Select::make('id_bem')
                                    ->label('Patrimônio')
                                    ->getSearchResultsUsing(function (string $search) {
                                        return BemMovel::query()->where('SituacaoBem', 1)
                                            ->where('NumPatrimonio', 'like', "%{$search}%")
                                            ->limit(50)->pluck('NumPatrimonio', 'id');
                                    })
                                    ->getOptionLabelUsing(fn($value) => BemMovel::find($value)?->NumPatrimonio ?? $value)
                                    ->placeholder('Busque pelo número do patrimônio')
                                    ->searchable()
                                    ->required()
                                    ->distinct()
                                    ->columnSpan(3),

                                Select::make('id_situacao')
                                    ->label('Status Destino')
                                    ->options([3 => 'Baixado', 2 => 'Inservível'])
                                    ->native(false)
                                    ->default(3)
                                    ->required()
                                    ->columnSpan(1),
                            ])->columns(4)->addActionLabel('Adicionar outro bem'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return TableDefaults::apply($table)
            ->columns([
                TableColumns::text('NumeroProcesso', 'Processo Nº', isFirstColumn: true, isIdColumn: true)
                    ->copyable()
                    ->copyMessage('Processo copiado'),
                TableColumns::date('DataBaixa', 'Data da Baixa'),
                TableColumns::text('Requisitante', 'Requisitante')
                    ->wrap()
                    ->tooltip(fn($record): ?string => $record->Requisitante),
                TableColumns::text('RequisitanteCnpj', 'CNPJ')
                    ->badge()
                    ->color('gray'),
                TableColumns::text('itens_count', 'Materiais')
                    ->counts('itens')
                    ->searchable(false)
                    ->color('primary')
                    ->weight('semibold')
                    ->extraAttributes([
                        'class' => 'cursor-pointer underline',
                    ])
                    ->tooltip('Clique para visualizar os materiais desta baixa')
                    ->action(self::materiaisTableAction()),
            ])
            ->recordActions([
                ...TableDefaults::actions(),
                ActionGroup::make([
                    self::baixarBensProcessoTableAction(),
                    self::imprimirBensBaixaTableAction(),
                    self::reativarBaixaTableAction(),
                ]),
            ])
            ->defaultSort('DataBaixa', 'desc');
    }

    public static function reativarBaixaTableAction(): Action
    {
        return Action::make('reativar_baixa')
            ->hiddenLabel()
            ->tooltip('Reativar baixa')
            ->icon('heroicon-o-arrow-path-rounded-square')
            ->color('gray')
            ->requiresConfirmation()
            ->modalHeading('Reativar baixa')
            ->modalDescription('A data da baixa deste processo será removida.')
            ->action(function (Baixa $record): void {
                Baixa::whereKey($record->getKey())
                    ->update(['DataBaixa' => null]);

                $record->refresh();

                Notification::make()
                    ->title('Baixa reativada com sucesso.')
                    ->success()
                    ->send();
            });
    }

    public static function imprimirBensBaixaTableAction(): Action
    {
        return Action::make('imprimir_bens_baixa')
            ->hiddenLabel()
            ->tooltip('Imprimir')
            ->icon('heroicon-o-printer')
            ->color('gray')
            ->disabled(fn(Baixa $record): bool => ! Route::has(self::TERMO_BAIXA_IMPRIMIR_ROUTE)
                || ! ItemBaixa::where('id_baixa', $record->id)->exists())
            ->url(fn(Baixa $record): ?string => self::termoBaixaImprimirUrl($record))
            ->openUrlInNewTab();
    }

    private static function termoBaixaImprimirUrl(Baixa $record): ?string
    {
        if (! Route::has(self::TERMO_BAIXA_IMPRIMIR_ROUTE)) {
            return null;
        }

        return route(self::TERMO_BAIXA_IMPRIMIR_ROUTE, ['id' => $record->id]);
    }

    public static function baixarBensProcessoTableAction(): Action
    {
        return Action::make('baixar_bens_processo')
            ->hiddenLabel()
            ->tooltip('Baixar bens do processo')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('gray')
            ->requiresConfirmation()
            ->modalHeading('Baixar bens do processo')
            ->modalDescription('Todos os materiais vinculados receberão a situação de destino definida na baixa.')
            ->action(function (Baixa $record): void {
                $userId = auth()->id();
                $baixadoEm = now();

                $itens = ItemBaixa::on($record->getConnectionName())
                    ->where('id_baixa', $record->getKey())
                    ->get(['id_bem', 'id_situacao']);

                Baixa::on($record->getConnectionName())
                    ->whereKey($record->getKey())
                    ->update([
                        'Usuario' => $userId,
                        'date_time' => $baixadoEm,
                        'DataBaixa' => $baixadoEm,
                    ]);

                foreach ($itens as $item) {
                    BemMovel::on($record->getConnectionName())
                        ->whereKey($item->id_bem)
                        ->update([
                            'Usuario' => $userId,
                            'date_time' => $baixadoEm,
                            'DataBaixa' => $baixadoEm,
                            'SituacaoBem' => $item->id_situacao,
                        ]);
                }

                $record->refresh();

                Notification::make()
                    ->title('Bens baixados definitivamente!')
                    ->success()
                    ->send();
            });
    }

    private static function materiaisTableAction(): Action
    {
        return TableModalAction::make('visualizar_materiais')
            ->modalHeading(fn(Baixa $record): string => "Materiais da baixa {$record->NumeroProcesso}")
            ->modalContent(fn(Baixa $record): HtmlString => new HtmlString(
                Livewire::mount(
                    'patrimonio.materiais-baixa-modal',
                    ['baixaId' => (int) $record->getKey()],
                    "materiais-baixa-{$record->getKey()}",
                )
            ));
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBaixas::route('/'),
            'create' => CreateBaixa::route('/create'),
            'edit' => EditBaixa::route('/{record}/edit'),
        ];
    }
}
