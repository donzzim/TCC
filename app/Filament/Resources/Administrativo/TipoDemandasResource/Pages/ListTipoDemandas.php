<?php

namespace App\Filament\Resources\Administrativo\TipoDemandasResource\Pages;

use App\Filament\Resources\Administrativo\TipoDemandasResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTipoDemandas extends ListRecords
{
    protected static string $resource = TipoDemandasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
