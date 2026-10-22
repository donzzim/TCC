<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Components;

use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use App\Filament\Egap\Support\TableModalComponent;
use App\Models\egap\Patrimonio\BensMoveis\Inventario;
use App\Models\egap\Patrimonio\BensMoveis\InventarioUnidade;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UnidadesModal extends TableModalComponent implements HasActions
{
    use InteractsWithActions;

    public int $inventarioId;

    public function mount(int $inventarioId): void
    {
        $this->inventarioId = $inventarioId;
    }

    public function table(Table $table): Table
    {
        return TableDefaults::apply($table)
            ->query($this->getUnidadesQuery())
            ->columns([
                TableColumns::text('unidade_inventariada', 'Unidade/Setor', isFirstColumn: true)
                    ->state(fn(InventarioUnidade $record): string => $record->rotuloUnidadeInventariada())
                    ->icon('heroicon-o-building-office')
                    ->weight('medium')
                    ->wrap(),

                TableColumns::text('setores_count', 'Setores')
                    ->badge()
                    ->counts('setores')
                    ->weight('medium'),

                TableColumns::text('dias', 'Dias')->suffix(' dias'),

                TableColumns::text('situacao', 'Situação')
                    ->badge()
                    ->formatStateUsing(fn(?string $state): string => Inventario::rotuloSituacao($state))
                    ->color(fn(?string $state): string => Inventario::corSituacao($state)),
            ])
            ->defaultSort('id')
            ->emptyStateIcon('heroicon-o-building-office')
            ->emptyStateHeading('Nenhuma unidade vinculada a este inventário')
            ->recordActions([])
            ->toolbarActions([]);
    }

    private function getUnidadesQuery(): Builder
    {
        return InventarioUnidade::query()
            ->where('id_inventario', $this->inventarioId)
            ->with('unidade.pai');
    }
}
