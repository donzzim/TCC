<?php

namespace App\Filament\Egap\Interno\Groups\GestaoOrcamentaria\Resources\Projeto\Tables;

use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use Filament\Tables\Table;

class ProjetoTable
{
    public static function configure(Table $table): Table
    {
        return TableDefaults::apply($table)
            ->defaultSort('date_time', 'DESC')
            ->columns([
                TableColumns::text('descricao', 'Descrição', isFirstColumn: true)
                    ->alignLeft(),

                TableColumns::text('acaoRef.descricao', 'Ação'),

                TableColumns::text('planoOrcamentarioRef.nome', 'Plano Orçamentário'),

                TableColumns::text('unidadeJudiciariaRef.setor', 'Unidade Judiciária')
                    ->badge()
                    ->color('gray')
                    ->wrap(),

                TableColumns::updatedBy('atualizadoPorRef.name'),
            ])
            ->filters([

            ]);
    }
}
