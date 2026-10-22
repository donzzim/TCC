<?php

namespace App\Filament\Egap\Interno\Groups\Almoxarifado\Resources\PedidosResource\Pages;

use App\Filament\Egap\Interno\Groups\Almoxarifado\Resources\PedidosResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPedidos extends EditRecord
{
    protected static string $resource = PedidosResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['date_time'] = now();

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
