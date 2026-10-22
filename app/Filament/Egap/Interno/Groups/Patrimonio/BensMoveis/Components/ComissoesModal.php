<?php

namespace App\Filament\Egap\Interno\Groups\Patrimonio\BensMoveis\Components;

use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use App\Filament\Egap\Support\TableModalComponent;
use App\Models\egap\Patrimonio\BensMoveis\InventarioComissao;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ComissoesModal extends TableModalComponent implements HasActions
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
            ->query($this->getComissoesQuery())
            ->columns([
                TableColumns::text('inventario_name', 'Inventário', isFirstColumn: true)
                    ->badge()
                    ->state(fn(InventarioComissao $record): string => $record->inventario
                        ? "{$record->inventario->num_inventario}/{$record->inventario->ano_inventario}"
                        : '-'),
                TableColumns::text('comissao', 'Comissão')
                    ->weight('medium'),

                TableColumns::text('funcao', 'Função')
                    ->badge()
                    ->color('gray'),

                TableColumns::text('membroRef.name', 'Membro')
                    ->weight('medium'),

                TableColumns::dateTime('date_time', 'Atualizado em'),
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }

    private function getComissoesQuery(): Builder
    {
        return InventarioComissao::query()
            ->where('id_inventario', $this->inventarioId)
            ->with(['inventario', 'membroRef']);
    }
}
