<?php

namespace App\Filament\Egap\Externo\Utils;

use App\Filament\Egap\Externo\Groups\Almoxarifado\Components\PedidosAlmoxarifadoTable;
use App\Filament\Egap\Externo\Groups\Patrimonio\Components\PedidosPatrimonioTable;
use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableModalAction;
use App\Models\egap\Almoxarifado\Pedidos;
use App\Models\egap\Cadastro\Setores;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Livewire\Component;
use Livewire\Livewire;

/**
 * Base da tabela de Acompanhamento de Pedidos do Ambiente Externo,
 * compartilhada entre
 * {@see PedidosAlmoxarifadoTable} e
 * {@see PedidosPatrimonioTable}
 * (legado: consultar_pedidos.php + status_pedidos.api.php).
 *
 * Guarda os column/filter/action builders e a query base que são idênticos
 * nos dois (filtro de localização, coluna de nº do pedido/data/solicitante/
 * complemento do setor/qtd. de materiais, e a action que abre o modal de
 * itens). Colunas extras (ex.: Justificativa, só no Almoxarifado), filtros de
 * situação e a cor do badge de status ficam a cargo de cada subclasse, pois
 * os agrupamentos de situação (idSituacao) divergem de verdade entre os dois
 * setores responsáveis.
 */
abstract class PedidosTable extends Component implements HasForms, HasTable, HasActions
{
    use InteractsWithActions;
    use InteractsWithForms;
    use InteractsWithTable;

    abstract protected function setorResponsavel(): int;

    protected function colunaNumeroPedido(): TextColumn
    {
        return TableColumns::text('id', 'Nº Pedido', isFirstColumn: true, isIdColumn: true)
            ->formatStateUsing(fn(Pedidos $record): string => "{$record->id}/{$record->date_time?->format('Y')}");
    }

    protected function colunaData(): TextColumn
    {
        return TableColumns::date('date_time', 'Data do Pedido');
    }

    protected function colunaSolicitante(): TextColumn
    {
        return TableColumns::text('solicitante_get.name', 'Solicitante')
            ->wrap();
    }

    protected function colunaComplementoSetor(): TextColumn
    {
        return TableColumns::text('complemento_setor', 'Complemento do Setor')
            ->wrap()
            ->getStateUsing(fn(Pedidos $record): ?string => $record->complementoSetor?->descricao);
    }

    protected function colunaMateriais(): TextColumn
    {
        return TableColumns::text('itens_count', 'Materiais')
            ->counts('itens')
            ->badge()
            ->color('gray')
            ->formatStateUsing(fn(int $state): string => $state === 1 ? '1 item' : "{$state} itens");
    }

    /**
     * @param  callable(int): string  $colorResolver
     */
    protected function colunaStatus(callable $colorResolver): TextColumn
    {
        return TableColumns::text('situacao.Descricao', 'Status')
            ->badge()
            ->color(fn(Pedidos $record): string => $colorResolver((int) $record->idSituacao));
    }

    protected function filtroLocalizacao(): Filter
    {
        return Filter::make('localizacao')
            ->label('Unidade Judiciária / Setor')
            ->columns(2)
            ->columnSpan(2)
            ->schema([
                Select::make('unidade_judiciaria')
                    ->label('Unidade Judiciária')
                    ->options(fn(): array => Setores::query()
                        ->whereColumn('id', 'codigopai')
                        ->orderBy('unidadeorganizacional')
                        ->pluck('unidadeorganizacional', 'codigopai')
                        ->toArray())
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->live()
                    ->afterStateUpdated(fn(Set $set) => $set('setor', null)),

                Select::make('setor')
                    ->label('Setor')
                    ->options(fn(Get $get): array => Setores::query()
                        ->where('codigopai', $get('unidade_judiciaria'))
                        ->orderBy('setor')
                        ->pluck('setor', 'id')
                        ->toArray())
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->disabled(fn(Get $get): bool => blank($get('unidade_judiciaria')))
                    ->hint(fn(Get $get): ?string => blank($get('unidade_judiciaria')) ? 'Selecione a Unidade Judiciária primeiro' : null),
            ])
            ->query(function (Builder $query, array $data): Builder {
                return $query
                    ->when(
                        filled($data['unidade_judiciaria'] ?? null),
                        fn(Builder $query) => $query->where('UnidadeJudiciaria', $data['unidade_judiciaria'])
                    )
                    ->when(
                        filled($data['setor'] ?? null),
                        fn(Builder $query) => $query->where('Setor', $data['setor'])
                    );
            })
            ->indicateUsing(function (array $data): array {
                $indicators = [];

                if (filled($data['unidade_judiciaria'] ?? null)) {
                    $indicators[] = 'Unidade: ' . Setores::find($data['unidade_judiciaria'])?->unidadeorganizacional;
                }

                if (filled($data['setor'] ?? null)) {
                    $indicators[] = 'Setor: ' . Setores::find($data['setor'])?->setor;
                }

                return $indicators;
            });
    }

    protected function acaoVerItens(string $componenteAlias): Action
    {
        return TableModalAction::make('ver_itens')
            ->label('Ver itens')
            ->action(function (array $data, $record): void {
                $record->update($data);
            })
            ->icon('heroicon-o-eye')
            ->modalHeading(fn(Pedidos $record): string => "Pedido Nº {$record->id}/{$record->date_time?->format('Y')}")
            ->modalContent(fn(Pedidos $record): HtmlString => new HtmlString(
                Livewire::mount(
                    $componenteAlias,
                    ['pedidoId' => (int) $record->getKey()],
                    "pedido-itens-{$record->getKey()}",
                )
            ));
    }

    protected function getQuery(): Builder
    {
        return Pedidos::query()
            ->with(['solicitante_get', 'complementoSetor', 'situacao'])
            ->where(function (Builder $query): void {
                $setorResponsavel = $this->setorResponsavel();

                $query->where('setor_responsavel', $setorResponsavel);

                if ($setorResponsavel === 1239) {
                    $query->orWhereNull('setor_responsavel');
                }
            });
    }

    public function render(): View
    {
        return view('egap.livewire.support.table');
    }
}
