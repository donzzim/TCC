<?php

namespace App\Filament\Egap\Interno\Groups\Almoxarifado\Resources\PedidosResource\Pages;

use App\Filament\Egap\Interno\Groups\Almoxarifado\Resources\PedidosResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPedidos extends ListRecords
{
    protected static string $resource = PedidosResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Novo')
        ];
    }
}
