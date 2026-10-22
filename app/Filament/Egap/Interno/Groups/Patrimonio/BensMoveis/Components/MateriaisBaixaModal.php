<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Components;

use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use App\Filament\Egap\Support\TableModalComponent;
use App\Models\egap\Patrimonio\BensMoveis\ItemBaixa;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MateriaisBaixaModal extends TableModalComponent implements HasActions
{
    use InteractsWithActions;

    public int $baixaId;

    public function mount(int $baixaId): void
    {
        $this->baixaId = $baixaId;
    }

    public function table(Table $table): Table
    {
        return TableDefaults::apply($table)
            ->query($this->getMateriaisQuery())
            ->columns([
                TableColumns::text('bem.NumPatrimonio', 'Processo Baixa', isFirstColumn: true)
                    ->badge()
                    ->color('primary'),

                TableColumns::text('descricao', 'Descrição')
                    ->getStateUsing(fn(ItemBaixa $record): string => $record->bem?->descricaoDetalhadaRef?->descricao_detalhada
                        ?: $record->bem?->descricao_detalhada
                        ?: '-')
                    ->weight('medium')
                    ->wrap(),

                TableColumns::text('situacaoDestino.descricao_completa', 'Situação')
                    ->badge()
                    ->color(fn(ItemBaixa $record): string => (int) $record->id_situacao === 3 ? 'danger' : 'warning'),
            ])
            ->defaultSort('id')
            ->emptyStateIcon('heroicon-o-archive-box')
            ->emptyStateHeading('Nenhum material está vinculado a esta baixa')
            ->recordActions([])
            ->toolbarActions([]);
    }

    private function getMateriaisQuery(): Builder
    {
        return ItemBaixa::query()
            ->where('id_baixa', $this->baixaId)
            ->with([
                'bem.descricaoDetalhadaRef',
                'bem.situacaoBemRef',
                'situacaoDestino',
            ]);
    }
}
