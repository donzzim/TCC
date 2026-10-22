<?php

namespace App\Filament\Egap\Interno\Groups\GestaoOrcamentaria\Resources\Acao\Tables;

use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use Filament\Tables\Table;

class AcaoTable
{
    public static function configure(Table $table): Table
    {
        return TableDefaults::apply($table)
            ->defaultSort('date_time', 'DESC')
            ->columns([
                TableColumns::text('descricao', 'Descrição', isFirstColumn: true)
                    ->alignLeft(),

                TableColumns::updatedBy('atualizadoPorRef.name'),
            ])
            ->filters([

            ]);
    }
}
