<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources;

use App\Filament\Egap\Interno\Clusters\PatrimonioCluster;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\ReavaliacaoResource\Pages\CreateReavaliacao;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\ReavaliacaoResource\Pages\EditReavaliacao;
use App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Resources\ReavaliacaoResource\Pages\ListReavaliacaos;
use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use App\Models\egap\Patrimonio\BensMoveis\BemMovel;
use App\Models\egap\Patrimonio\BensMoveis\Reavaliacao;
use Filament\Forms\Components\Select;
use Filament\Pages\Enums\SubNavigationPosition;
use App\Filament\Egap\Interno\Permissions\Patrimonio\PatrimonioResource;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;

class ReavaliacaoResource extends PatrimonioResource
{
    protected static ?string $cluster = PatrimonioCluster::class;

    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?string $model = Reavaliacao::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static string | \UnitEnum | null $navigationGroup = 'Bens Móveis';

    protected static ?string $navigationLabel = 'Reavaliação';

    protected static ?string $modelLabel = 'Reavaliação';

    protected static ?string $pluralModelLabel = 'Reavaliações';

    protected static ?int $navigationSort = 11;

    protected static ?string $slug = 'bens-moveis/reavaliacoes';

    public static function table(Table $table): Table
    {
        return TableDefaults::apply($table)
            ->modifyQueryUsing(fn($query) => $query->with(['bem', 'responsavel']))
            ->emptyStateIcon('heroicon-o-funnel')
            ->emptyStateHeading(fn($livewire): string => filled(data_get($livewire, 'tableFilters.patrimonioFilter.patrimonio'))
                ? 'Nenhuma reavaliação encontrada'
                : 'Selecione um patrimônio no filtro')
            ->emptyStateDescription(fn($livewire): string => filled(data_get($livewire, 'tableFilters.patrimonioFilter.patrimonio'))
                ? 'Não há registros de reavaliação para o patrimônio selecionado.'
                : 'A tabela de reavaliações só carrega registros após a busca por um patrimônio.')
            ->columns([
                TableColumns::text('bem.NumPatrimonio', 'Patrimônio', isFirstColumn: true)
                    ->badge()
                    ->copyable()
                    ->copyMessage('Patrimônio copiado')
                    ->weight('medium'),
                TableColumns::text('bem.Descricao', 'Descrição')
                    ->limit(45)
                    ->wrap()
                    ->tooltip(fn(Reavaliacao $record): ?string => $record->bem?->Descricao),
                TableColumns::date('data_reavaliacao', 'Data da Reavaliação'),
                TableColumns::date('data_referencia', 'Data de Referência'),
                TableColumns::date('data_disponibilizacao', 'Data de Disponibilização'),
                TableColumns::money('valor_aquisicao', 'Valor de Aquisição'),
                TableColumns::money('valor_mercado', 'Valor de Mercado'),
                TableColumns::money('valor_reavaliacao', 'Valor da Reavaliação')
                    ->weight('medium'),
                TableColumns::money('ajuste_contabil', 'Ajuste Contábil'),
                TableColumns::text('vida_util', 'Vida Útil')
                    ->suffix(' meses'),
                TableColumns::text('vida_util_reavaliacao', 'Vida Útil Reavaliação')
                    ->suffix(' meses'),
                TableColumns::text('vida_util_siafi', 'Vida Útil SIAFI')
                    ->suffix(' meses'),
                TableColumns::text('tempo_utilizacao_meses', 'Tempo de Utilização')
                    ->suffix(' meses'),
                TableColumns::text('vida_util_remanescente_meses', 'Vida Útil Remanescente')
                    ->suffix(' meses'),
                TableColumns::text('utilizacao_bem_anos', 'Utilização')
                    ->suffix(' anos'),
                TableColumns::text('vida_util_estimada_anos', 'Vida Útil Estimada')
                    ->suffix(' anos'),
                TableColumns::text('estado_conservacao', 'Estado')
                    ->badge()
                    ->color(fn(?string $state): string => match (mb_strtoupper($state ?? '')) {
                        'ÓTIMO', 'OTIMO', 'BOM' => 'success',
                        'REGULAR' => 'warning',
                        default => 'danger',
                    }),
                TableColumns::text('pub1', 'PUB1'),
                TableColumns::text('puv', 'PUV'),
                TableColumns::text('fr', 'FR'),
                TableColumns::updatedBy('responsavel.name'),
            ])
            ->filters([
                Filter::make('patrimonioFilter')
                    ->columnSpan(2)
                    ->label('Patrimônio')
                    ->schema([
                        Select::make('patrimonio')
                            ->label('Patrimônio')
                            ->placeholder('Busque pelo número do patrimônio')
                            ->getSearchResultsUsing(fn(string $search): array => BemMovel::query()
                                ->where('NumPatrimonio', 'like', "%{$search}%")
                                ->orWhere('Descricao', 'like', "%{$search}%")
                                ->limit(50)
                                ->get()
                                ->mapWithKeys(fn(BemMovel $record): array => [
                                    $record->getKey() => "{$record->NumPatrimonio} - {$record->Descricao}",
                                ])
                                ->all())
                            ->getOptionLabelUsing(function ($value): ?string {
                                $record = BemMovel::query()->find($value);

                                return $record
                                    ? "{$record->NumPatrimonio} - {$record->Descricao}"
                                    : null;
                            })
                            ->searchable()
                            ->native(false),
                    ])
                    ->query(fn($query, array $data) => $query
                        ->when(
                            filled($data['patrimonio'] ?? null),
                            fn($query) => $query->where('patrimonio', $data['patrimonio']),
                            fn($query) => $query->whereKey(-1),
                        )),
            ])
            ->recordActions([])
            ->toolbarActions([])
            ->defaultSort('data_reavaliacao', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReavaliacaos::route('/'),
            'create' => CreateReavaliacao::route('/create'),
            'edit' => EditReavaliacao::route('/{record}/edit'),
        ];
    }
}
