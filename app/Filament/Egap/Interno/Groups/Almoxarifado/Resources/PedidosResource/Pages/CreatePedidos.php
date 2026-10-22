<?php

namespace App\Filament\Egap\Interno\Groups\Almoxarifado\Resources\PedidosResource\Pages;

use App\Filament\Egap\Interno\Groups\Almoxarifado\Resources\PedidosResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePedidos extends CreateRecord
{
    protected static string $resource = PedidosResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['date_time'] = now();

        return $data;
    }
}
