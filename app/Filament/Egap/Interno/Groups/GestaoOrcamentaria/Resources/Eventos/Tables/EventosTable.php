<?php

namespace App\Filament\Egap\Interno\Groups\GestaoOrcamentaria\Resources\Eventos\Tables;

use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use Filament\Tables\Table;

class EventosTable
{
    public static function configure(Table $table): Table
    {
        return TableDefaults::apply($table)
            ->defaultSort('date_time', 'DESC')
            ->columns([
                TableColumns::text('tipo_evento', 'Tipo de Evento', isFirstColumn: true),

                TableColumns::text('orcamentoRef.projetoRef.descricao', 'Projeto'),

                TableColumns::text('processoRef.num_processo', 'Processo'),

                TableColumns::money('valor_total', 'Valor Total'),

                TableColumns::updatedBy('atualizadoPorRef.name'),
            ])
            ->filters([

            ]);
    }
}
