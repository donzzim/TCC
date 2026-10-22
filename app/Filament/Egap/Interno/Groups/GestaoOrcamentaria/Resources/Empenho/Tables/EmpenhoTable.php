<?php

namespace App\Filament\Egap\Interno\Groups\GestaoOrcamentaria\Resources\Empenho\Tables;

use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use Filament\Tables\Table;

class EmpenhoTable
{
    public static function configure(Table $table): Table
    {
        return TableDefaults::apply($table)
            ->defaultSort('data_empenho', 'DESC')
            ->columns([
                TableColumns::text('no_empenho', 'Nº Empenho', isFirstColumn: true)
                    ->badge()
                    ->color('primary'),

                TableColumns::text('processoRef.num_processo', 'Processo'),

                TableColumns::text('elementoDespesaRef.DescricaodaClasse', 'Elemento de Despesa'),

                TableColumns::money('valor_total', 'Valor Total'),

                TableColumns::date('data_empenho', 'Data do Empenho'),

                TableColumns::text('gestorTitularRef.name', 'Gestor Titular'),

                TableColumns::updatedBy('atualizadoPorRef.name'),
            ])
            ->filters([

            ]);
    }
}
