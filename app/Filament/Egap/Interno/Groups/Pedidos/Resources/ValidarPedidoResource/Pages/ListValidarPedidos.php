<?php

namespace App\Filament\Egap\Interno\Groups\Pedidos\Resources\ValidarPedidoResource\Pages;

use App\Filament\Egap\Interno\Groups\Pedidos\Resources\ValidarPedidoResource;
use App\Filament\Egap\Support\CreateActionModal;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;

class ListValidarPedidos extends ListRecords
{
    protected static string $resource = ValidarPedidoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateActionModal::createAction()
                ->modalWidth(Width::SixExtraLarge),
        ];
    }
}
