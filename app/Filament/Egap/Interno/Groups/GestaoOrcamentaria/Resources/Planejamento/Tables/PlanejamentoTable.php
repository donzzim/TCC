<?php

namespace App\Filament\Egap\Interno\Groups\GestaoOrcamentaria\Resources\Planejamento\Tables;

use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use Filament\Tables\Table;

class PlanejamentoTable
{
    public static function configure(Table $table): Table
    {
        return TableDefaults::apply($table)
            ->defaultSort('date_time', 'DESC')
            ->columns([
                TableColumns::text('projetoRef.descricao', 'Projeto', isFirstColumn: true),

                TableColumns::text('elementoDespesaRef.DescricaodaClasse', 'Elemento de Despesa'),

                TableColumns::number('ano', 'Ano'),

                TableColumns::money('valor_total', 'Valor Total'),

                TableColumns::money('saldo_atual', 'Saldo Atual'),

                TableColumns::text('unidadeRequisitanteRef.setor', 'Unidade Requisitante')
                    ->badge()
                    ->color('gray')
                    ->wrap(),

                TableColumns::updatedBy('atualizadoPorRef.name'),
            ])
            ->filters([

            ]);
    }
}
