<?php

namespace App\Filament\Egap\Interno\Groups\GestaoOrcamentaria\Resources\Reserva\Tables;

use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use Filament\Tables\Table;

class ReservaTable
{
    public static function configure(Table $table): Table
    {
        return TableDefaults::apply($table)
            ->defaultSort('data_reserva', 'DESC')
            ->columns([
                TableColumns::text('no_reserva', 'Nº Reserva', isFirstColumn: true)
                    ->badge()
                    ->color('primary'),

                TableColumns::text('processoRef.num_processo', 'Processo'),

                TableColumns::text('elementoDespesaRef.DescricaodaClasse', 'Elemento de Despesa'),

                TableColumns::money('valor_total', 'Valor Total'),

                TableColumns::date('data_reserva', 'Data da Reserva'),

                TableColumns::text('gestorTitularRef.name', 'Gestor Titular'),

                TableColumns::updatedBy('atualizadoPorRef.name'),
            ])
            ->filters([

            ]);
    }
}
