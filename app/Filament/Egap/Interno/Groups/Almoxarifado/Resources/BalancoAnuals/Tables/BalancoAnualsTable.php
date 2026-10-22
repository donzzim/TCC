<?php

namespace App\Filament\Egap\Interno\Groups\Almoxarifado\Resources\BalancoAnuals\Tables;

use App\Filament\Egap\Support\TableColumns;
use App\Filament\Egap\Support\TableDefaults;
use Filament\Tables\Table;

class BalancoAnualsTable
{
    public static function configure(Table $table): Table
    {
        return TableDefaults::apply($table)
            ->defaultSort('termino', 'desc')
            ->columns([
                TableColumns::date('data_inicio', 'Início', isFirstColumn: true)
                    ->badge(),
                TableColumns::date('termino', 'Término', isFirstColumn: true)
                    ->badge(),
                TableColumns::text('mensagem', 'Mensagem', true)
                    ->limit(50)
                    ->tooltip(fn($record) => $record->mensagem),
            ]);
    }
}
