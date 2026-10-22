<?php

namespace App\Filament\Egap\Interno\Groups\GestaoOrcamentaria\Resources\PlanoOrcamentario\Tables;

use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use Filament\Tables\Table;

class PlanoOrcamentarioTable
{
    public static function configure(Table $table): Table
    {
        return TableDefaults::apply($table)
            ->defaultSort('date_time', 'DESC')
            ->columns([
                TableColumns::text('codigo', 'Código', isFirstColumn: true)
                    ->badge()
                    ->color('primary'),

                TableColumns::text('nome', 'Nome')
                    ->alignLeft(),

                TableColumns::text('setorResponsavelRef.setor', 'Setor Responsável')
                    ->badge()
                    ->color('gray')
                    ->wrap(),

                TableColumns::updatedBy('atualizadoPorRef.name'),
            ])
            ->filters([

            ]);
    }
}
