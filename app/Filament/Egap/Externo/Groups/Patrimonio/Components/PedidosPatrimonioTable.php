<?php

namespace App\Filament\Egap\Externo\Groups\Patrimonio\Components;

use App\Filament\Egap\Externo\Groups\Almoxarifado\Components\PedidoItensModal;
use App\Filament\Egap\Externo\Utils\PedidosTable;
use App\Filament\Egap\Support\TableDefaults;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;

class PedidosPatrimonioTable extends PedidosTable implements HasActions
{
    use InteractsWithActions;
    protected const SETOR_PATRIMONIO = 1239;

    #[On('pedido-item-cancelado')]
    public function refreshTable(): void
    {
        // Apenas força o rerender, para atualizar a contagem de itens e o status do pedido.
    }

    public function table(Table $table): Table
    {
        return TableDefaults::apply($table)
            ->query($this->getQuery())
            ->columns([
                $this->colunaNumeroPedido(),
                $this->colunaData(),
                $this->colunaSolicitante(),
                $this->colunaComplementoSetor(),
                $this->colunaMateriais(),
                $this->colunaStatus($this->statusColor(...)),
            ])
            ->filters([
                SelectFilter::make('situacao_grupo')
                    ->label('Situação')
                    ->options([
                        'pendente' => 'Pendentes',
                        'concluido' => 'Concluídos',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'pendente' => $query->whereIn('idSituacao', [6, 8, 9, 10]),
                            'concluido' => $query->whereIn('idSituacao', [3, 4, 5, 7]),
                            default => $query,
                        };
                    }),

                $this->filtroLocalizacao(),
            ])
            ->filtersFormColumns(3)
            ->recordActions([
                $this->acaoVerItens('externo-patrimonio.pedido-itens-modal'),
            ])
            ->toolbarActions([])
            ->defaultSort('date_time', 'desc')
            ->emptyStateHeading('Nenhum pedido encontrado');
    }

    protected function statusColor(int $status): string
    {
        return match ($status) {
            3, 7 => 'success',
            4, 10 => 'gray',
            5 => 'danger',
            6, 9 => 'warning',
            8 => 'info',
            default => 'gray',
        };
    }

    protected function setorResponsavel(): int
    {
        return self::SETOR_PATRIMONIO;
    }
}
